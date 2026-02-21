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

if (!defined('ABSPATH')) {
    exit;
}

class BackupService
{
    private static ?self $instance = null;
    private Database $db;
    
    private const BACKUP_DIR = ABSPATH . 'backups/';
    
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
        $this->ensureBackupDir();
    }
    
    /**
     * Ensure backup directory exists
     */
    private function ensureBackupDir(): void
    {
        if (!is_dir(self::BACKUP_DIR)) {
            mkdir(self::BACKUP_DIR, 0755, true);
            
            // Create .htaccess to prevent direct access
            file_put_contents(
                self::BACKUP_DIR . '.htaccess',
                "deny from all\n"
            );
        }
    }
    
    /**
     * Create full backup (database + files)
     */
    public function createFullBackup(): array
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupName = "full_backup_{$timestamp}";
            $backupPath = self::BACKUP_DIR . $backupName . '/';
            
            mkdir($backupPath, 0755, true);
            
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
            
            file_put_contents(
                $backupPath . 'manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT)
            );
            
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
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Create database backup only
     */
    public function createDatabaseBackup(?string $targetDir = null): string
    {
        $targetDir = $targetDir ?? self::BACKUP_DIR;
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "database_{$timestamp}.sql";
        $filepath = $targetDir . $filename;
        
        try {
            $sql = $this->generateDatabaseDump();
            file_put_contents($filepath, $sql);
            
            // Compress
            if (extension_loaded('zlib')) {
                $gzFilepath = $filepath . '.gz';
                $gz = gzopen($gzFilepath, 'w9');
                gzwrite($gz, $sql);
                gzclose($gz);
                
                // Remove uncompressed file
                unlink($filepath);
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
            new \RecursiveDirectoryIterator($path),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $basePath . '/' . substr($filePath, strlen($path) + 1);
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
            gzwrite($gz, $sql);
            gzclose($gz);
            
            $subject = 'CMS Database Backup - ' . date('Y-m-d H:i');
            $message = "Automatisches Datenbank-Backup vom " . date('Y-m-d H:i:s') . "\n\n";
            $message .= "CMS Version: " . (CMS_VERSION ?? 'unknown') . "\n";
            $message .= "Backup Größe: " . $this->formatBytes(filesize($tempFile));
            
            // Send email with attachment
            $sent = $this->sendEmailWithAttachment(
                $email,
                $subject,
                $message,
                $tempFile,
                'database_backup.sql.gz'
            );
            
            // Delete temp file
            unlink($tempFile);
            
            if ($sent) {
                $this->logBackup('email', 'database_' . date('Y-m-d_H-i'), filesize($tempFile));
            }
            
            return $sent;
        } catch (\Exception $e) {
            error_log('BackupService::emailDatabaseBackup() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email with attachment
     */
    private function sendEmailWithAttachment(
        string $to,
        string $subject,
        string $message,
        string $filePath,
        string $fileName
    ): bool {
        $boundary = md5((string)time());
        
        $headers = [
            'From: ' . ADMIN_EMAIL,
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
        ];
        
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $message . "\r\n\r\n";
        
        // Attachment
        $content = chunk_split(base64_encode(file_get_contents($filePath)));
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: application/octet-stream; name=\"{$fileName}\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n\r\n";
        $body .= $content . "\r\n";
        $body .= "--{$boundary}--";
        
        return mail($to, $subject, $body, implode("\r\n", $headers));
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
        
        $context = stream_context_create([
            'http' => [
                'method' => 'PUT',
                'header' => [
                    'Date: ' . $timestamp,
                    'Content-Type: ' . $this->getMimeType($filePath),
                    'Content-Length: ' . strlen($fileContent),
                    'Authorization: AWS ' . $config['access_key'] . ':' . $signature,
                ],
                'content' => $fileContent,
            ],
        ]);
        
        $result = @file_get_contents($url, false, $context);
        
        return $result !== false;
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
                $data = json_decode($row->option_value, true);
                if ($data) {
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
        
        if (!is_dir(self::BACKUP_DIR)) {
            return $backups;
        }
        
        $items = scandir(self::BACKUP_DIR);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.htaccess') {
                continue;
            }
            
            $path = self::BACKUP_DIR . $item;
            
            if (is_dir($path)) {
                $manifestFile = $path . '/manifest.json';
                
                if (file_exists($manifestFile)) {
                    $manifest = json_decode(file_get_contents($manifestFile), true);
                    $manifest['name'] = $item;
                    $manifest['path'] = $path;
                    $backups[] = $manifest;
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
        $backupPath = self::BACKUP_DIR . $backupName;
        
        if (!is_dir($backupPath)) {
            return false;
        }
        
        return $this->deleteDirectory($backupPath);
    }
    
    /**
     * Delete directory recursively
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
    
    /**
     * Get directory size
     */
    private function getDirectorySize(string $dir): int
    {
        $size = 0;
        
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $file) {
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
}
