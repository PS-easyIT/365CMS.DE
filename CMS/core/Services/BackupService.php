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
    private const BACKUP_NAME_PATTERN = '/^[a-z0-9][a-z0-9._-]{2,120}$/i';
    
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
            error_log('BackupService::createFullBackup() Error: ' . $e->getMessage());
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
            error_log('BackupService::createDatabaseBackup() Error: ' . $e->getMessage());
            throw $e;
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
            // Table structure
            $createStmt = $this->db->query("SHOW CREATE TABLE `{$table}`");
            $create = $createStmt->fetch(\PDO::FETCH_ASSOC);
            
            $sql .= "-- Table: {$table}\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $create['Create Table'] . ";\n\n";
            
            // Table data
            $dataStmt = $this->db->query("SELECT * FROM `{$table}`");
            $rows = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                foreach ($rows as $row) {
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
                }
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
            error_log('BackupService::emailDatabaseBackup() Error: ' . $e->getMessage());
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
            error_log('BackupService::uploadToS3() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Upload to S3 using REST API (without SDK)
     */
    private function uploadToS3WithREST(string $filePath, array $config): bool
    {
        // Simplified S3 upload via PUT request
        $objectKey = basename($filePath);
        $url = "https://{$config['bucket']}.{$config['endpoint']}/{$objectKey}";
        
        $fileContent = file_get_contents($filePath);
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
                    'Content-Length: ' . strlen($fileContent),
                    'Authorization: AWS ' . $config['access_key'] . ':' . $signature,
                ],
                'userAgent' => '365CMS-BackupService/1.0',
                'maxBytes' => 1048576,
            ]);
        } catch (\Throwable $e) {
            error_log('BackupService::uploadToS3WithREST() HTTP-Fehler: ' . $e->getMessage());
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
            error_log('BackupService::getBackupHistory() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * List available backups
     */
    public function listBackups(): array
    {
        $backups = [];
        
        $backupRoot = $this->backupRoot();
        if (!is_dir($backupRoot)) {
            return $backups;
        }
        
        $items = scandir($backupRoot);
        if ($items === false) {
            return $backups;
        }
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.htaccess') {
                continue;
            }
            
            if (!$this->isAllowedBackupName($item)) {
                continue;
            }

            $path = $backupRoot . $item;
            
            if (is_dir($path)) {
                $manifestFile = $path . '/manifest.json';
                
                if (is_file($manifestFile)) {
                    $manifestSize = filesize($manifestFile);
                    if ($manifestSize === false || $manifestSize > self::MANIFEST_MAX_BYTES) {
                        continue;
                    }

                    $manifestContents = file_get_contents($manifestFile);
                    if (!is_string($manifestContents) || $manifestContents === '') {
                        continue;
                    }

                    $manifest = Json::decodeArray($manifestContents, []);
                    if ($manifest !== []) {
                        $manifest['name'] = $item;
                        $manifest['path'] = $path;
                        $backups[] = $manifest;
                    }
                }
            }
        }
        
        // Sort by timestamp (newest first)
        usort($backups, function($a, $b) {
            return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
        });
        
        return $backups;
    }
    
    /**
     * Delete backup
     */
    public function deleteBackup(string $backupName): bool
    {
        try {
            $normalizedName = $this->normalizeBackupName($backupName);
            $backupPath = $this->backupRoot() . $normalizedName;

            if (!is_dir($backupPath) || !$this->isWithinBackupRoot($backupPath)) {
                return false;
            }

            return $this->deleteDirectory($backupPath);
        } catch (\Throwable $e) {
            error_log('BackupService::deleteBackup() Error: ' . $e->getMessage());
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
            error_log('BackupService::logBackup() Error: ' . $e->getMessage());
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

        $normalizedDir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $targetDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
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

    private function isWithinBackupRoot(string $path): bool
    {
        $normalizedPath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return str_starts_with($normalizedPath, $this->backupRoot());
    }
}
