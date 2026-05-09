<?php
/**
 * Backup Service
 * 
 * Automatische Datensicherung (Datenbank + Dateien)
 * Optionen: Webspace, E-Mail (SQL only), S3
 * 
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;
use CMS\Http\Client as HttpClient;
use CMS\Json;
use CMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class BackupService
{
    private static ?self $instance = null;
    private Database $db;
    private HttpClient $httpClient;
    
    private const BACKUP_DIR = ABSPATH . 'backups/';
    private const MANIFEST_MAX_BYTES = 262144;
    private const DEFAULT_BACKUP_LIST_LIMIT = 25;
    private const MAX_BACKUP_LIST_LIMIT = 100;
    private const MAX_S3_REST_UPLOAD_BYTES = 26214400;
    private const BACKUP_NAME_PATTERN = '/^[a-z0-9][a-z0-9._-]{2,120}$/i';
    private const LEGACY_DATABASE_BACKUP_PATTERN = '/^database_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}-[0-9]{2}-[0-9]{2}\.sql(?:\.gz)?$/i';
    private const S3_BUCKET_PATTERN = '/^[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]$/';
    private const S3_ENDPOINT_PATTERN = '/^[a-z0-9.-]+$/i';
    private const FILE_BACKUP_DIRECTORIES = ['uploads', 'themes', 'plugins', 'assets'];
    private const ALLOWED_DOWNLOAD_PARTS = ['database', 'files'];
    
    /**
     * Singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct()
    {
        $this->db = Database::instance();
        $this->httpClient = HttpClient::getInstance();
        $this->ensureBackupDir();
    }
    
    /**
     * Ensure backup directory exists
     */
    private function ensureBackupDir(): void
    {
        $backupRoot = $this->backupRoot();

        if (!is_dir($backupRoot) && !mkdir($backupRoot, 0755, true) && !is_dir($backupRoot)) {
            throw new \RuntimeException('Backup-Verzeichnis konnte nicht erstellt werden.');
        }

        $htaccessPath = $backupRoot . '.htaccess';
        if (!is_file($htaccessPath) && file_put_contents($htaccessPath, "deny from all\n", LOCK_EX) === false) {
            throw new \RuntimeException('Backup-Schutzdatei konnte nicht geschrieben werden.');
        }

        $indexPath = $backupRoot . 'index.html';
        if (!is_file($indexPath) && file_put_contents($indexPath, '', LOCK_EX) === false) {
            throw new \RuntimeException('Backup-Indexdatei konnte nicht geschrieben werden.');
        }
    }
    
    /**
     * Create full backup (database + files)
     */
    public function createFullBackup(): array
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupName = $this->normalizeBackupName("full_backup_{$timestamp}");
            $backupPath = $this->createBackupDirectory($backupName);
            
            // 1. Database backup
            $dbFile = $this->createDatabaseBackup($backupPath);
            
            // 2. Files backup
            $filesZip = $this->createFilesBackup($backupPath);
            
            // 3. Create manifest
            $manifest = [
                'timestamp' => time(),
                'date' => $timestamp,
                'type' => 'full',
                'database' => $dbFile,
                'files' => $filesZip,
                'size' => $this->getDirectorySize($backupPath),
                'cms_version' => CMS_VERSION ?? 'unknown',
            ];
            
            $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (!is_string($manifestJson) || file_put_contents($backupPath . 'manifest.json', $manifestJson, LOCK_EX) === false) {
                throw new \RuntimeException('Manifest konnte nicht geschrieben werden.');
            }
            
            // Log backup
            $this->logBackup('full', $backupName, $manifest['size']);
            
            return [
                'success' => true,
                'path' => $backupPath,
                'name' => $backupName,
                'manifest' => $manifest,
            ];
        } catch (\Exception $e) {
            Logger::instance()->withChannel('backup')->error('Full backup could not be created.', [
                'exception' => $e,
            ]);
            return [
                'success' => false,
                'error' => 'Vollständiges Backup konnte nicht erstellt werden.',
            ];
        }
    }
    
    /**
     * Create database backup only
     */
    public function createDatabaseBackup(?string $targetDir = null): string
    {
        $targetDir = $this->resolveTargetDirectory($targetDir);
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "database_{$timestamp}.sql";
        $filepath = $targetDir . $filename;
        
        try {
            $sql = $this->generateDatabaseDump();
            if (file_put_contents($filepath, $sql, LOCK_EX) === false) {
                throw new \RuntimeException('SQL-Backupdatei konnte nicht geschrieben werden.');
            }
            
            // Compress
            if (extension_loaded('zlib')) {
                $gzFilepath = $filepath . '.gz';
                $gz = gzopen($gzFilepath, 'w9');
                if ($gz === false) {
                    throw new \RuntimeException('Komprimiertes Backup konnte nicht erstellt werden.');
                }
                gzwrite($gz, $sql);
                gzclose($gz);
                
                // Remove uncompressed file
                if (is_file($filepath) && !unlink($filepath)) {
                    throw new \RuntimeException('Temporäre SQL-Backupdatei konnte nicht entfernt werden.');
                }
                $filepath = $gzFilepath;
            }
            
            return basename($filepath);
        } catch (\Exception $e) {
            Logger::instance()->withChannel('backup')->error('Database backup file could not be created.', [
                'target_dir' => $targetDir,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Create standalone database backup with manifest container
     */
    public function createStandaloneDatabaseBackup(): array
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupName = $this->normalizeBackupName("database_backup_{$timestamp}");
            $backupPath = $this->createBackupDirectory($backupName);
            $dbFile = $this->createDatabaseBackup($backupPath);

            $manifest = [
                'timestamp' => time(),
                'date' => $timestamp,
                'type' => 'database',
                'database' => $dbFile,
                'files' => '',
                'size' => $this->getDirectorySize($backupPath),
                'cms_version' => CMS_VERSION ?? 'unknown',
            ];

            $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (!is_string($manifestJson) || file_put_contents($backupPath . 'manifest.json', $manifestJson, LOCK_EX) === false) {
                throw new \RuntimeException('Manifest konnte nicht geschrieben werden.');
            }

            $this->logBackup('database', $backupName, (int) $manifest['size']);

            return [
                'success' => true,
                'path' => $backupPath,
                'name' => $backupName,
                'manifest' => $manifest,
            ];
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('backup')->error('Standalone database backup could not be created.', [
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'error' => 'Datenbank-Backup konnte nicht erstellt werden.',
            ];
        }
    }
    
    /**
     * Generate SQL dump
     */
    private function generateDatabaseDump(): string
    {
        $sql = "-- CMS Database Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Database: " . DB_NAME . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $sql .= "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n\n";
        
        // Get all tables
        $stmt = $this->db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            if (!is_string($table) || $table === '' || preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
                continue;
            }

            // Table structure
            $createStmt = $this->db->query("SHOW CREATE TABLE `{$table}`");
            $create = $createStmt->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($create) || !isset($create['Create Table'])) {
                continue;
            }
            
            $sql .= "-- Table: {$table}\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $create['Create Table'] . ";\n\n";
            
            // Table data
            $dataStmt = $this->db->query("SELECT * FROM `{$table}`");
            $wroteRows = false;

            while ($row = $dataStmt->fetch(\PDO::FETCH_ASSOC)) {
                $values = array_map(function($value) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    // Handle numeric types without quotes
                    if (is_int($value) || is_float($value)) {
                        return (string)$value;
                    }
                    // Handle boolean
                    if (is_bool($value)) {
                        return $value ? '1' : '0';
                    }
                    // String values need escaping and quotes
                    return "'" . addslashes((string)$value) . "'";
                }, array_values($row));

                $columns = '`' . implode('`, `', array_keys($row)) . '`';
                $sql .= "INSERT INTO `{$table}` ({$columns}) VALUES (" . implode(', ', $values) . ");\n";
                $wroteRows = true;
            }

            if ($wroteRows) {
                $sql .= "\n";
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        return $sql;
    }
    
    /**
     * Create files backup (uploads, themes, plugins)
     */
    private function createFilesBackup(string $targetDir): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "files_{$timestamp}.zip";
        $filepath = $targetDir . $filename;
        
        if (!extension_loaded('zip')) {
            throw new \Exception('ZIP extension not loaded');
        }
        
        $zip = new \ZipArchive();
        
        if ($zip->open($filepath, \ZipArchive::CREATE) !== true) {
            throw new \Exception('Cannot create ZIP file');
        }
        
        // Add directories to backup
        $dirsToBackup = [
            'uploads',
            'themes',
            'plugins',
            'assets',
        ];
        
        foreach ($dirsToBackup as $dir) {
            $fullPath = ABSPATH . $dir;
            if (is_dir($fullPath)) {
                $this->addDirectoryToZip($zip, $fullPath, $dir);
            }
        }
        
        $zip->close();
        
        return basename($filepath);
    }
    
    /**
     * Add directory to ZIP recursively
     */
    private function addDirectoryToZip(\ZipArchive $zip, string $path, string $basePath): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if ($file->isLink() || !$file->isFile()) {
                continue;
            }

            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                if ($filePath === false) {
                    continue;
                }

                $relativePath = $basePath . '/' . str_replace('\\', '/', substr($filePath, strlen(rtrim($path, '\\/')) + 1));
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    
    /**
     * Send database backup via email
     */
    public function emailDatabaseBackup(string $email): bool
    {
        try {
            $tempFile = sys_get_temp_dir() . '/db_backup_' . time() . '.sql.gz';
            
            // Create backup in temp
            $sql = $this->generateDatabaseDump();
            
            // Compress
            $gz = gzopen($tempFile, 'w9');
            if ($gz === false) {
                throw new \RuntimeException('Temporäre Backup-Datei konnte nicht erstellt werden.');
            }
            gzwrite($gz, $sql);
            gzclose($gz);

            $backupSize = is_file($tempFile) ? (int)filesize($tempFile) : 0;
            
            $subject = 'CMS Database Backup - ' . date('Y-m-d H:i');
            $message = "Automatisches Datenbank-Backup vom " . date('Y-m-d H:i:s') . "\n\n";
            $message .= "CMS Version: " . (CMS_VERSION ?? 'unknown') . "\n";
            $message .= "Backup Größe: " . $this->formatBytes($backupSize);
            
            // Send email with attachment
            $sent = $this->sendEmailWithAttachment(
                $email,
                $subject,
                $message,
                $tempFile,
                'database_backup.sql.gz'
            );
            
            // Delete temp file
            if (is_file($tempFile)) {
                unlink($tempFile);
            }
            
            if ($sent) {
                $this->logBackup('email', 'database_' . date('Y-m-d_H-i'), $backupSize);
            }
            
            return $sent;
        } catch (\Exception $e) {
            Logger::instance()->withChannel('backup')->error('Database backup email could not be sent.', [
                'recipient' => $email,
                'exception' => $e,
            ]);
            return false;
        }
    }
    
    /**
     * Send email with attachment (delegiert an MailService)
     */
    private function sendEmailWithAttachment(
        string $to,
        string $subject,
        string $message,
        string $filePath,
        string $fileName
    ): bool {
        if (MailQueueService::getInstance()->shouldQueue()) {
            $result = MailService::getInstance()->queueWithAttachment(
                $to,
                $subject,
                $message,
                $filePath,
                $fileName,
                false,
                [
                    'X-365CMS-Test-Source' => 'backup-email',
                ],
                null,
                'backup-email'
            );

            return !empty($result['success']);
        }

        return MailService::getInstance()->sendWithAttachment(
            $to,
            $subject,
            $message,
            $filePath,
            $fileName,
            false, // Plain-Text-Nachricht
            [
                'X-365CMS-Test-Source' => 'backup-email',
            ]
        );
    }
    
    /**
     * Upload backup to S3 (AWS, DigitalOcean Spaces, etc.)
     */
    public function uploadToS3(string $backupPath, array $s3Config): bool
    {
        try {
            // Validate S3 config
            $required = ['endpoint', 'bucket', 'access_key', 'secret_key', 'region'];
            foreach ($required as $key) {
                if (!isset($s3Config[$key])) {
                    throw new \Exception("Missing S3 config: {$key}");
                }
            }
            
            // Use AWS SDK if available, otherwise use REST API
            if (class_exists('Aws\S3\S3Client')) {
                return $this->uploadToS3WithSDK($backupPath, $s3Config);
            } else {
                return $this->uploadToS3WithREST($backupPath, $s3Config);
            }
        } catch (\Exception $e) {
            Logger::instance()->withChannel('backup')->error('Backup upload to S3 failed.', [
                'backup_path' => $backupPath,
                'exception' => $e,
            ]);
            return false;
        }
    }
    
    /**
     * Upload to S3 using REST API (without SDK)
     */
    private function uploadToS3WithREST(string $filePath, array $config): bool
    {
        if (!$this->isWithinBackupRoot($filePath) || !is_file($filePath) || !is_readable($filePath)) {
            return false;
        }

        if (!$this->isValidS3RestConfig($config)) {
            return false;
        }

        // Simplified S3 upload via PUT request
        $objectKey = basename($filePath);
        $url = "https://{$config['bucket']}.{$config['endpoint']}/{$objectKey}";

        $fileSize = filesize($filePath);
        if ($fileSize === false || $fileSize < 1 || $fileSize > self::MAX_S3_REST_UPLOAD_BYTES) {
            return false;
        }

        $fileContent = file_get_contents($filePath);
        if (!is_string($fileContent) || $fileContent === '') {
            return false;
        }

        $timestamp = gmdate('D, d M Y H:i:s T');
        
        // Create signature
        $stringToSign = "PUT\n\n{$this->getMimeType($filePath)}\n{$timestamp}\n/{$config['bucket']}/{$objectKey}";
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $config['secret_key'], true));
        
        try {
            $result = $this->httpClient->put($url, $fileContent, [
                'timeout' => 120,
                'connectTimeout' => 10,
                'headers' => [
                    'Date: ' . $timestamp,
                    'Content-Type: ' . $this->getMimeType($filePath),
                    'Content-Length: ' . $fileSize,
                    'Authorization: AWS ' . $config['access_key'] . ':' . $signature,
                ],
                'userAgent' => '365CMS-BackupService/1.0',
                'maxBytes' => 1048576,
            ]);
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('backup')->error('Backup upload via S3 REST failed.', [
                'backup_path' => $filePath,
                'bucket' => (string) ($config['bucket'] ?? ''),
                'endpoint' => (string) ($config['endpoint'] ?? ''),
                'exception' => $e,
            ]);
            $result = ['success' => false];
        }
        
        return !empty($result['success']);
    }
    
    /**
     * Get backup history
     */
    public function getBackupHistory(int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM {$this->db->getPrefix()}settings
                WHERE option_name LIKE 'backup_log_%'
                ORDER BY option_name DESC
                LIMIT ?
            ");
            
            if (!$stmt) {
                return [];
            }
            
            $stmt->execute([$limit]);
            
            $history = [];
            while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
                $data = Json::decode($row->option_value ?? null, true, null);
                if (is_array($data)) {
                    $history[] = $data;
                }
            }
            
            return $history;
        } catch (\Exception $e) {
            Logger::instance()->withChannel('backup')->warning('Backup history could not be loaded.', [
                'limit' => $limit,
                'exception' => $e,
            ]);
            return [];
        }
    }
    
    /**
     * List available backups
     */
    public function listBackups(int $limit = self::DEFAULT_BACKUP_LIST_LIMIT): array
    {
        $backups = [];
        $limit = max(1, min(self::MAX_BACKUP_LIST_LIMIT, $limit));
        
        $backupRoot = $this->backupRoot();
        if (!is_dir($backupRoot)) {
            return $backups;
        }
        
        $items = scandir($backupRoot);
        if ($items === false) {
            return $backups;
        }

        $directoryCandidates = [];
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.htaccess' || $item === 'index.html') {
                continue;
            }

            $path = $backupRoot . $item;

            if (is_dir($path) && $this->isAllowedBackupName($item)) {
                $directoryCandidates[$item] = (int) (filemtime($path) ?: 0);
                continue;
            }

            if (is_file($path) && $this->isLegacyDatabaseBackupFile($item)) {
                $timestamp = (int) (filemtime($path) ?: 0);
                $size = (int) (filesize($path) ?: 0);
                $backups[] = [
                    'name' => $item,
                    'path' => $path,
                    'timestamp' => $timestamp,
                    'date' => $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '-',
                    'type' => 'database',
                    'database' => $item,
                    'files' => '',
                    'size' => $size,
                ];
            }
        }

        arsort($directoryCandidates, SORT_NUMERIC);

        foreach (array_keys($directoryCandidates) as $item) {
            if (count($backups) >= $limit) {
                break;
            }

            $path = $backupRoot . $item;
            $manifestFile = $path . '/manifest.json';

            if (!is_file($manifestFile)) {
                continue;
            }

            $manifestSize = filesize($manifestFile);
            if ($manifestSize === false || $manifestSize > self::MANIFEST_MAX_BYTES) {
                continue;
            }

            $manifestContents = file_get_contents($manifestFile);
            if (!is_string($manifestContents) || $manifestContents === '') {
                continue;
            }

            $manifest = Json::decodeArray($manifestContents, []);
            if ($manifest === []) {
                continue;
            }

            $manifest['name'] = $item;
            $manifest['path'] = $path;
            $backups[] = $manifest;
        }
        
        // Sort by timestamp (newest first)
        usort($backups, function($a, $b) {
            return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
        });
        
        return array_slice($backups, 0, $limit);
    }

    /**
     * Liefert eine herunterladbare Backup-Datei (DB oder Dateien) für den Admin-Download.
     *
     * @return array{path:string,filename:string,content_type:string}|null
     */
    public function resolveDownloadableBackupFile(string $backupName, string $part = 'database'): ?array
    {
        $record = $this->findBackupRecord($backupName);
        if ($record === null) {
            return null;
        }

        $part = $this->normalizeDownloadPart($part);
        $path = $part === 'files'
            ? $this->resolveBackupFilesArchivePath($record)
            : $this->resolveBackupDatabasePath($record);

        if ($path === '' || !is_file($path) || !is_readable($path) || !$this->isWithinBackupRoot($path)) {
            return null;
        }

        return [
            'path' => $path,
            'filename' => basename($path),
            'content_type' => $this->getMimeType($path),
        ];
    }

    /**
     * Stellt ein Datenbank- oder Vollbackup wieder her.
     *
     * @return array{success:bool, restored_database:bool, restored_files:bool, rollback_backup:string}
     */
    public function restoreBackup(string $backupName): array
    {
        $record = $this->findBackupRecord($backupName);
        if ($record === null) {
            throw new \RuntimeException('Backup wurde nicht gefunden.');
        }

        $databasePath = $this->resolveBackupDatabasePath($record);
        $filesPath = $this->resolveBackupFilesArchivePath($record);

        if ($databasePath === '' && $filesPath === '') {
            throw new \RuntimeException('Backup enthält keine wiederherstellbaren Bestandteile.');
        }

        $rollbackBackup = $this->createFullBackup();
        if (empty($rollbackBackup['success']) || empty($rollbackBackup['name'])) {
            throw new \RuntimeException('Vor der Wiederherstellung konnte kein Sicherungs-Snapshot erstellt werden.');
        }

        $restoredDatabase = false;
        $restoredFiles = false;

        if ($databasePath !== '') {
            $this->restoreDatabaseDump($databasePath);
            $restoredDatabase = true;
        }

        if ($filesPath !== '') {
            $this->restoreFilesArchive($filesPath);
            $restoredFiles = true;
        }

        return [
            'success' => true,
            'restored_database' => $restoredDatabase,
            'restored_files' => $restoredFiles,
            'rollback_backup' => (string) $rollbackBackup['name'],
        ];
    }
    
    /**
     * Delete backup
     */
    public function deleteBackup(string $backupName): bool
    {
        try {
            $normalizedName = trim(basename($backupName));
            if ($normalizedName === '') {
                return false;
            }

            $backupPath = $this->backupRoot() . $normalizedName;

            if ($this->isLegacyDatabaseBackupFile($normalizedName) && is_file($backupPath) && $this->isWithinBackupRoot($backupPath)) {
                return unlink($backupPath);
            }

            $normalizedName = $this->normalizeBackupName($normalizedName);
            $backupPath = $this->backupRoot() . $normalizedName;

            if (!is_dir($backupPath) || !$this->isWithinBackupRoot($backupPath)) {
                return false;
            }

            return $this->deleteDirectory($backupPath);
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('backup')->warning('Backup could not be deleted.', [
                'backup_name' => $backupName,
                'exception' => $e,
            ]);
            return false;
        }
    }
    
    /**
     * Delete directory recursively
     */
    private function deleteDirectory(string $dir): bool
    {
        $normalizedDir = rtrim($dir, '\\/');

        if (!is_dir($normalizedDir) || !$this->isWithinBackupRoot($normalizedDir)) {
            return false;
        }
        
        $files = scandir($normalizedDir);
        if ($files === false) {
            return false;
        }
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $normalizedDir . DIRECTORY_SEPARATOR . $file;
            if (is_link($path) || is_file($path)) {
                if (!unlink($path)) {
                    return false;
                }

                continue;
            }

            if (is_dir($path) && !$this->deleteDirectory($path)) {
                return false;
            }
        }
        
        return rmdir($normalizedDir);
    }

    /** @return array<string, mixed>|null */
    private function findBackupRecord(string $backupName): ?array
    {
        $normalizedName = trim(basename($backupName));
        if ($normalizedName === '') {
            return null;
        }

        foreach ($this->listBackups(self::MAX_BACKUP_LIST_LIMIT) as $backup) {
            if (!is_array($backup)) {
                continue;
            }

            if ((string) ($backup['name'] ?? '') === $normalizedName) {
                return $backup;
            }
        }

        return null;
    }

    private function normalizeDownloadPart(string $part): string
    {
        $part = strtolower(trim($part));

        return in_array($part, self::ALLOWED_DOWNLOAD_PARTS, true) ? $part : 'database';
    }

    /** @param array<string, mixed> $record */
    private function resolveBackupDatabasePath(array $record): string
    {
        $name = trim(basename((string) ($record['name'] ?? '')));
        if ($name !== '' && $this->isLegacyDatabaseBackupFile($name)) {
            $path = $this->backupRoot() . $name;

            return is_file($path) ? $path : '';
        }

        $databaseFile = trim(basename((string) ($record['database'] ?? '')));
        if ($name === '' || $databaseFile === '') {
            return '';
        }

        $path = $this->backupRoot() . $name . DIRECTORY_SEPARATOR . $databaseFile;

        return is_file($path) ? $path : '';
    }

    /** @param array<string, mixed> $record */
    private function resolveBackupFilesArchivePath(array $record): string
    {
        $name = trim(basename((string) ($record['name'] ?? '')));
        $filesArchive = trim(basename((string) ($record['files'] ?? '')));
        if ($name === '' || $filesArchive === '') {
            return '';
        }

        $path = $this->backupRoot() . $name . DIRECTORY_SEPARATOR . $filesArchive;

        return is_file($path) ? $path : '';
    }

    private function restoreDatabaseDump(string $filePath): void
    {
        if (!$this->isWithinBackupRoot($filePath) || !is_file($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException('Backup-Dump ist nicht lesbar.');
        }

        $sql = $this->readBackupFileContents($filePath);
        if ($sql === '') {
            throw new \RuntimeException('Backup-Dump ist leer.');
        }

        $this->executeSqlBatch($sql);
    }

    private function restoreFilesArchive(string $archivePath): void
    {
        if (!$this->isWithinBackupRoot($archivePath) || !is_file($archivePath) || !is_readable($archivePath)) {
            throw new \RuntimeException('Backup-Archiv ist nicht lesbar.');
        }

        if (!extension_loaded('zip')) {
            throw new \RuntimeException('ZIP-Unterstützung ist für die Dateiwiederherstellung nicht verfügbar.');
        }

        $zip = new \ZipArchive();
        $zipResult = $zip->open($archivePath);
        if ($zipResult !== true) {
            throw new \RuntimeException('Backup-Archiv konnte nicht geöffnet werden.');
        }

        if (!$this->validateRestoreArchiveEntries($zip)) {
            $zip->close();
            throw new \RuntimeException('Backup-Archiv enthält ungültige oder unsichere Pfade.');
        }

        $stagingRoot = $this->createManagedTemporaryDirectory(ABSPATH, '365cms_restore_stage_');
        $rollbackRoot = $this->createManagedTemporaryDirectory(ABSPATH, '365cms_restore_rollback_');

        try {
            if (!$zip->extractTo($stagingRoot)) {
                throw new \RuntimeException('Backup-Archiv konnte nicht in das Staging-Verzeichnis entpackt werden.');
            }
        } finally {
            $zip->close();
        }

        $sourceRoot = $this->resolveRestoreArchiveRoot($stagingRoot);
        $entries = $this->listDirectoryEntries($sourceRoot);
        if ($entries === []) {
            $this->removeDirectory($stagingRoot);
            $this->removeDirectory($rollbackRoot);
            throw new \RuntimeException('Backup-Archiv enthält keine wiederherstellbaren Dateien.');
        }

        $backedUpTargets = [];
        $restoredTargets = [];

        try {
            foreach ($entries as $entry) {
                if (!in_array($entry, self::FILE_BACKUP_DIRECTORIES, true)) {
                    throw new \RuntimeException('Backup-Archiv enthält nicht erlaubte Zielpfade.');
                }

                $sourcePath = $sourceRoot . DIRECTORY_SEPARATOR . $entry;
                if (!is_dir($sourcePath)) {
                    throw new \RuntimeException('Backup-Archiv enthält einen ungültigen Verzeichniseintrag.');
                }

                $targetPath = rtrim((string) ABSPATH, '/\\') . DIRECTORY_SEPARATOR . $entry;
                $rollbackPath = $rollbackRoot . DIRECTORY_SEPARATOR . $entry;

                if (file_exists($targetPath)) {
                    if (!rename($targetPath, $rollbackPath)) {
                        throw new \RuntimeException('Bestehendes Zielverzeichnis konnte nicht in das Rollback verschoben werden: ' . $entry);
                    }

                    $backedUpTargets[] = $entry;
                }

                if (!rename($sourcePath, $targetPath)) {
                    throw new \RuntimeException('Backup-Dateien konnten nicht in das Ziel verschoben werden: ' . $entry);
                }

                $restoredTargets[] = $entry;
            }
        } catch (\Throwable $e) {
            foreach (array_reverse($restoredTargets) as $entry) {
                $targetPath = rtrim((string) ABSPATH, '/\\') . DIRECTORY_SEPARATOR . $entry;
                $stagingPath = $sourceRoot . DIRECTORY_SEPARATOR . $entry;

                if (file_exists($targetPath)) {
                    @rename($targetPath, $stagingPath);
                }
            }

            foreach (array_reverse($backedUpTargets) as $entry) {
                $targetPath = rtrim((string) ABSPATH, '/\\') . DIRECTORY_SEPARATOR . $entry;
                $rollbackPath = $rollbackRoot . DIRECTORY_SEPARATOR . $entry;

                if (file_exists($rollbackPath)) {
                    @rename($rollbackPath, $targetPath);
                }
            }

            $this->removeDirectory($stagingRoot);
            $this->removeDirectory($rollbackRoot);
            throw $e;
        }

        $this->removeDirectory($stagingRoot);
        $this->removeDirectory($rollbackRoot);
    }

    private function readBackupFileContents(string $filePath): string
    {
        $extension = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension === 'gz') {
            $handle = gzopen($filePath, 'rb');
            if ($handle === false) {
                throw new \RuntimeException('Komprimierter Backup-Dump konnte nicht gelesen werden.');
            }

            $content = '';
            while (!gzeof($handle)) {
                $chunk = gzread($handle, 8192);
                if ($chunk === false) {
                    gzclose($handle);
                    throw new \RuntimeException('Komprimierter Backup-Dump konnte nicht vollständig gelesen werden.');
                }

                $content .= $chunk;
            }

            gzclose($handle);

            return $content;
        }

        $content = file_get_contents($filePath);
        if (!is_string($content)) {
            throw new \RuntimeException('Backup-Dump konnte nicht gelesen werden.');
        }

        return $content;
    }

    private function executeSqlBatch(string $sql): void
    {
        $pdo = $this->db->getPdo();
        $statement = '';
        $length = strlen($sql);
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $isEscaped = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];

            if ($isEscaped) {
                $statement .= $char;
                $isEscaped = false;
                continue;
            }

            if (($inSingleQuote || $inDoubleQuote) && $char === '\\') {
                $statement .= $char;
                $isEscaped = true;
                continue;
            }

            if ($char === "'" && !$inDoubleQuote) {
                $inSingleQuote = !$inSingleQuote;
                $statement .= $char;
                continue;
            }

            if ($char === '"' && !$inSingleQuote) {
                $inDoubleQuote = !$inDoubleQuote;
                $statement .= $char;
                continue;
            }

            if ($char === ';' && !$inSingleQuote && !$inDoubleQuote) {
                $trimmed = trim($statement);
                if ($trimmed !== '') {
                    $pdo->exec($trimmed);
                }

                $statement = '';
                continue;
            }

            $statement .= $char;
        }

        $trimmed = trim($statement);
        if ($trimmed !== '') {
            $pdo->exec($trimmed);
        }
    }

    private function validateRestoreArchiveEntries(\ZipArchive $zip): bool
    {
        $hasEntries = false;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);
            if (!is_string($entryName) || $entryName === '') {
                return false;
            }

            $normalized = str_replace('\\', '/', $entryName);
            $normalized = ltrim($normalized, '/');

            if ($normalized === ''
                || str_contains($normalized, '../')
                || str_contains($normalized, '..\\')
                || preg_match('~^[A-Za-z]:/~', $normalized) === 1
            ) {
                return false;
            }

            $hasEntries = true;
        }

        return $hasEntries;
    }

    private function resolveRestoreArchiveRoot(string $stagingRoot): string
    {
        $entries = $this->listDirectoryEntries($stagingRoot);
        if ($entries === []) {
            return $stagingRoot;
        }

        $allAllowed = array_reduce($entries, fn (bool $carry, string $entry): bool => $carry && in_array($entry, self::FILE_BACKUP_DIRECTORIES, true), true);
        if ($allAllowed) {
            return $stagingRoot;
        }

        if (count($entries) === 1) {
            $candidate = $stagingRoot . DIRECTORY_SEPARATOR . $entries[0];
            if (is_dir($candidate)) {
                $candidateEntries = $this->listDirectoryEntries($candidate);
                $candidateAllowed = $candidateEntries !== []
                    && array_reduce($candidateEntries, fn (bool $carry, string $entry): bool => $carry && in_array($entry, self::FILE_BACKUP_DIRECTORIES, true), true);

                if ($candidateAllowed) {
                    return $candidate;
                }
            }
        }

        return $stagingRoot;
    }

    private function createManagedTemporaryDirectory(string $parentDir, string $prefix): string
    {
        $path = $this->buildManagedTemporaryPath($parentDir, $prefix);
        if (!mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException('Temporäres Restore-Verzeichnis konnte nicht erstellt werden.');
        }

        return $path;
    }

    private function buildManagedTemporaryPath(string $parentDir, string $prefix): string
    {
        $parentDir = rtrim($parentDir, '/\\');

        do {
            $path = $parentDir . DIRECTORY_SEPARATOR . $prefix . bin2hex(random_bytes(8));
        } while (file_exists($path));

        return $path;
    }
    
    /**
     * Get directory size
     */
    private function getDirectorySize(string $dir): int
    {
        $size = 0;
        
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isLink()) {
                continue;
            }

            $size += $file->getSize();
        }
        
        return $size;
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Get MIME type
     */
    private function getMimeType(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'sql' => 'application/sql',
            'gz' => 'application/gzip',
            'zip' => 'application/zip',
            'json' => 'application/json',
        ];
        
        return $mimeTypes[$ext] ?? 'application/octet-stream';
    }
    
    /**
     * Log backup
     */
    private function logBackup(string $type, string $name, int $size): bool
    {
        $logEntry = [
            'type' => $type,
            'name' => $name,
            'size' => $size,
            'size_formatted' => $this->formatBytes($size),
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => $_SESSION['user_id'] ?? 'System',
        ];
        
        $optionName = 'backup_log_' . time();
        
        try {
            return $this->db->insert('settings', [
                'option_name' => $optionName,
                'option_value' => json_encode($logEntry),
                'autoload' => 0,
            ]) !== false;
        } catch (\Exception $e) {
            Logger::instance()->withChannel('backup')->warning('Backup metadata could not be persisted.', [
                'type' => $type,
                'name' => $name,
                'size' => $size,
                'exception' => $e,
            ]);
            return false;
        }
    }

    private function backupRoot(): string
    {
        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, self::BACKUP_DIR), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    private function resolveTargetDirectory(?string $targetDir): string
    {
        if ($targetDir === null || trim($targetDir) === '') {
            return $this->backupRoot();
        }

        $normalizedDir = $this->normalizeManagedPath($targetDir, false);
        if (!$this->isWithinBackupRoot($normalizedDir)) {
            throw new \RuntimeException('Ungültiges Backup-Zielverzeichnis.');
        }

        if (!is_dir($normalizedDir) && !mkdir($normalizedDir, 0755, true) && !is_dir($normalizedDir)) {
            throw new \RuntimeException('Backup-Zielverzeichnis konnte nicht erstellt werden.');
        }

        return $normalizedDir;
    }

    private function createBackupDirectory(string $backupName): string
    {
        $backupPath = $this->backupRoot() . $this->normalizeBackupName($backupName) . DIRECTORY_SEPARATOR;
        if (!$this->isWithinBackupRoot($backupPath)) {
            throw new \RuntimeException('Backup-Unterverzeichnis liegt außerhalb des Backup-Roots.');
        }

        if (!mkdir($backupPath, 0755, true) && !is_dir($backupPath)) {
            throw new \RuntimeException('Backup-Unterverzeichnis konnte nicht erstellt werden.');
        }

        return $backupPath;
    }

    private function normalizeBackupName(string $name): string
    {
        $name = trim($name);
        if (!$this->isAllowedBackupName($name)) {
            throw new \RuntimeException('Ungültiger Backup-Name.');
        }

        return $name;
    }

    private function isAllowedBackupName(string $name): bool
    {
        return preg_match(self::BACKUP_NAME_PATTERN, $name) === 1;
    }

    private function isLegacyDatabaseBackupFile(string $name): bool
    {
        return preg_match(self::LEGACY_DATABASE_BACKUP_PATTERN, trim(basename($name))) === 1;
    }

    private function isWithinBackupRoot(string $path): bool
    {
        $backupRoot = $this->normalizeManagedPath($this->backupRoot(), true);
        $normalizedPath = $this->normalizeManagedPath($path, file_exists($path));

        if ($backupRoot === '' || $normalizedPath === '') {
            return false;
        }

        return $normalizedPath === $backupRoot
            || str_starts_with($normalizedPath, $backupRoot);
    }

    private function normalizeManagedPath(string $path, bool $mustExist): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        $resolved = realpath($path);
        if (is_string($resolved)) {
            return rtrim($resolved, '\\/') . DIRECTORY_SEPARATOR;
        }

        if ($mustExist) {
            return '';
        }

        $parent = dirname($path);
        $resolvedParent = realpath($parent);
        if (!is_string($resolvedParent) || $resolvedParent === '') {
            return '';
        }

        return rtrim($resolvedParent, '\\/') . DIRECTORY_SEPARATOR . basename($path) . DIRECTORY_SEPARATOR;
    }

    /** @param array<string, mixed> $config */
    private function isValidS3RestConfig(array $config): bool
    {
        $bucket = strtolower(trim((string) ($config['bucket'] ?? '')));
        $endpoint = strtolower(trim((string) ($config['endpoint'] ?? '')));

        if ($bucket === '' || preg_match(self::S3_BUCKET_PATTERN, $bucket) !== 1) {
            return false;
        }

        if ($endpoint === '' || preg_match(self::S3_ENDPOINT_PATTERN, $endpoint) !== 1) {
            return false;
        }

        if (str_contains($endpoint, '/') || str_contains($endpoint, '@') || str_contains($endpoint, ':')) {
            return false;
        }

        return true;
    }
}
