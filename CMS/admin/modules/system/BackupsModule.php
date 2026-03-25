<?php
declare(strict_types=1);

/**
 * Backup-Modul – Backups erstellen, auflisten und löschen
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;
use CMS\Auth;
use CMS\Logger;
use CMS\Security;
use CMS\Services\BackupService;

class BackupsModule
{
    private BackupService $service;
    private Logger $logger;

    private const CSRF_ACTION = 'admin_backups';
    private const READ_CAPABILITIES = ['manage_settings', 'manage_system'];
    private const WRITE_CAPABILITY = 'manage_settings';
    private const MAX_ERROR_LENGTH = 180;
    private const HISTORY_LIMIT = 15;
    private const BACKUP_LIST_LIMIT = 25;
    private const BACKUP_NAME_PATTERN = '/^[a-z0-9][a-z0-9._-]{2,120}$/i';
    private const ALLOWED_BACKUP_TYPES = ['full', 'database', 'email'];

    public function __construct()
    {
        $this->service = BackupService::getInstance();
        $this->logger = Logger::instance()->withChannel('admin.backups');
    }

    /**
     * Backup-Daten für die Übersicht
     */
    public function getData(): array
    {
        if (!$this->canRead()) {
            return [
                'backups' => [],
                'history' => [],
            ];
        }

        return [
            'backups' => $this->sanitizeBackupList($this->listBackupsSafe()),
            'history' => $this->sanitizeHistory($this->getHistorySafe()),
        ];
    }

    /**
     * Vollständiges Backup erstellen (DB + Dateien)
     */
    public function createFullBackup(): array
    {
        if (!$this->assertWritableRequest()) {
            return ['success' => false, 'error' => 'Keine Berechtigung für diese Aktion.'];
        }

        try {
            $result = $this->service->createFullBackup();
            if (!empty($result['success'])) {
                $backupName = $this->normalizeBackupName((string)($result['name'] ?? ''));
                $message = 'Vollständiges Backup erstellt.';

                $this->auditAction('backup.full.created', 'Vollständiges Backup erstellt.', [
                    'name' => $backupName,
                    'size' => isset($result['manifest']['size']) ? (int)$result['manifest']['size'] : 0,
                ]);

                if ($backupName !== '') {
                    $message .= ' ' . $backupName;
                }

                return ['success' => true, 'message' => $message];
            }

            return ['success' => false, 'error' => 'Backup konnte nicht erstellt werden.'];
        } catch (\Throwable $e) {
            return $this->failResult('backup.full.create_failed', 'Vollständiges Backup konnte nicht erstellt werden.', $e);
        }
    }

    /**
     * Nur Datenbank-Backup erstellen
     */
    public function createDatabaseBackup(): array
    {
        if (!$this->assertWritableRequest()) {
            return ['success' => false, 'error' => 'Keine Berechtigung für diese Aktion.'];
        }

        try {
            $result = $this->service->createStandaloneDatabaseBackup();
            if (empty($result['success'])) {
                return ['success' => false, 'error' => 'DB-Backup konnte nicht erstellt werden.'];
            }

            $backupName = $this->normalizeBackupName((string) ($result['name'] ?? ''));
            $backupFile = $this->normalizeBackupFileName((string) ($result['manifest']['database'] ?? ''), ['sql', 'gz']);

            if ($backupName !== '') {
                $this->auditAction('backup.database.created', 'Datenbank-Backup erstellt.', [
                    'name' => $backupName,
                    'database' => $backupFile,
                    'size' => isset($result['manifest']['size']) ? (int) $result['manifest']['size'] : 0,
                ]);

                $message = 'Datenbank-Backup erstellt.';
                if ($backupName !== '') {
                    $message .= ' ' . $backupName;
                }

                return ['success' => true, 'message' => $message];
            }

            return ['success' => false, 'error' => 'DB-Backup konnte nicht erstellt werden.'];
        } catch (\Throwable $e) {
            return $this->failResult('backup.database.create_failed', 'Datenbank-Backup konnte nicht erstellt werden.', $e);
        }
    }

    /**
     * Backup löschen
     */
    public function deleteBackup(string $name): array
    {
        if (!$this->assertWritableRequest()) {
            return ['success' => false, 'error' => 'Keine Berechtigung für diese Aktion.'];
        }

        $normalizedName = $this->normalizeBackupName($name);
        if ($normalizedName === '') {
            return ['success' => false, 'error' => 'Kein Backup angegeben.'];
        }

        try {
            $result = $this->service->deleteBackup($normalizedName);
            if ($result) {
                $this->auditAction('backup.deleted', 'Backup gelöscht.', [
                    'name' => $normalizedName,
                ]);

                return ['success' => true, 'message' => 'Backup gelöscht: ' . $normalizedName];
            }

            return ['success' => false, 'error' => 'Backup konnte nicht gelöscht werden.'];
        } catch (\Throwable $e) {
            return $this->failResult('backup.delete_failed', 'Backup konnte nicht gelöscht werden.', $e);
        }
    }

    private function listBackupsSafe(): array
    {
        try {
            return $this->service->listBackups(self::BACKUP_LIST_LIMIT);
        } catch (\Throwable $e) {
            $this->logger->warning('Backup-Liste konnte nicht geladen werden.', [
                'exception' => $e::class,
                'message' => $this->sanitizeText($e->getMessage(), self::MAX_ERROR_LENGTH),
            ]);
            return [];
        }
    }

    private function getHistorySafe(): array
    {
        try {
            return $this->service->getBackupHistory(self::HISTORY_LIMIT);
        } catch (\Throwable $e) {
            $this->logger->warning('Backup-Historie konnte nicht geladen werden.', [
                'exception' => $e::class,
                'message' => $this->sanitizeText($e->getMessage(), self::MAX_ERROR_LENGTH),
            ]);
            return [];
        }
    }

    private function canRead(): bool
    {
        if (!class_exists(Auth::class) || !Auth::instance()->isAdmin()) {
            return false;
        }

        foreach (self::READ_CAPABILITIES as $capability) {
            if (Auth::instance()->hasCapability($capability)) {
                return true;
            }
        }

        return false;
    }

    private function canWrite(): bool
    {
        return class_exists(Auth::class)
            && Auth::instance()->isAdmin()
            && Auth::instance()->hasCapability(self::WRITE_CAPABILITY);
    }

    private function assertWritableRequest(): bool
    {
        return $this->canWrite() && $this->assertCsrf();
    }

    private function assertCsrf(): bool
    {
        return class_exists(Security::class)
            && Security::instance()->verifyToken((string)($_POST['csrf_token'] ?? ''), self::CSRF_ACTION);
    }

    /**
     * @param array<int, mixed> $backups
     * @return array<int, array<string, mixed>>
     */
    private function sanitizeBackupList(array $backups): array
    {
        $sanitized = [];

        foreach ($backups as $backup) {
            if (!is_array($backup)) {
                continue;
            }

            $name = $this->normalizeBackupName((string)($backup['name'] ?? ''));
            $type = $this->normalizeBackupType((string)($backup['type'] ?? 'full'));
            $date = $this->sanitizeDate((string)($backup['date'] ?? ''));
            $timestamp = max(0, (int)($backup['timestamp'] ?? 0));
            $size = max(0, (int)($backup['size'] ?? 0));

            if ($name === '' || $type === '') {
                continue;
            }

            $entry = [
                'name' => $name,
                'type' => $type,
                'date' => $date !== '' ? $date : ($timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '-'),
                'timestamp' => $timestamp,
                'size' => $size,
                'size_formatted' => $size > 0 ? $this->formatBytes($size) : '-',
                'database' => $this->normalizeBackupFileName((string)($backup['database'] ?? ''), ['sql', 'gz']),
                'files' => $this->normalizeBackupFileName((string)($backup['files'] ?? ''), ['zip']),
            ];

            $sanitized[] = $entry;
        }

        return $sanitized;
    }

    /**
     * @param array<int, mixed> $history
     * @return array<int, array<string, mixed>>
     */
    private function sanitizeHistory(array $history): array
    {
        $sanitized = [];

        foreach ($history as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $type = $this->normalizeBackupType((string)($entry['type'] ?? ''));
            if ($type === '') {
                continue;
            }

            $name = $this->normalizeBackupName((string)($entry['name'] ?? ''));
            $message = $this->sanitizeText((string)($entry['message'] ?? ''), 140);
            $timestamp = $this->sanitizeDate((string)($entry['timestamp'] ?? ''));

            $sanitized[] = [
                'type' => $type,
                'name' => $name,
                'success' => !empty($entry['success']),
                'message' => $message !== '' ? $message : ($name !== '' ? $name : '-'),
                'timestamp' => $timestamp !== '' ? $timestamp : '-',
                'size_formatted' => $this->sanitizeText((string)($entry['size_formatted'] ?? ''), 32),
            ];
        }

        return $sanitized;
    }

    private function normalizeBackupName(string $name): string
    {
        $name = trim(basename($name));

        return preg_match(self::BACKUP_NAME_PATTERN, $name) === 1 ? $name : '';
    }

    /**
     * @param array<int, string> $allowedExtensions
     */
    private function normalizeBackupFileName(string $filename, array $allowedExtensions): string
    {
        $filename = trim(basename($filename));
        if ($filename === '' || preg_match(self::BACKUP_NAME_PATTERN, $filename) !== 1) {
            return '';
        }

        $parts = explode('.', strtolower($filename));
        $extensions = array_slice($parts, 1);
        if ($extensions === []) {
            return '';
        }

        foreach ($extensions as $extension) {
            if (!in_array($extension, $allowedExtensions, true)) {
                return '';
            }
        }

        return $filename;
    }

    private function normalizeBackupType(string $type): string
    {
        $type = strtolower(trim($type));

        return in_array($type, self::ALLOWED_BACKUP_TYPES, true) ? $type : '';
    }

    private function sanitizeDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return $this->sanitizeText($value, 32);
    }

    private function sanitizeText(string $value, int $maxLength): string
    {
        $value = trim(strip_tags($value));
        if ($value === '') {
            return '';
        }

        return function_exists('mb_substr')
            ? mb_substr($value, 0, $maxLength)
            : substr($value, 0, $maxLength);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = max(0, $bytes);
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        $precision = $unitIndex === 0 ? 0 : 2;

        return number_format($size, $precision, ',', '.') . ' ' . $units[$unitIndex];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function auditAction(string $event, string $message, array $context): void
    {
        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            $event,
            $message,
            'backup',
            null,
            $context,
            'info'
        );
    }

    private function failResult(string $action, string $message, \Throwable $e): array
    {
        $sanitizedError = $this->sanitizeText($e->getMessage(), self::MAX_ERROR_LENGTH);

        $this->logger->warning($message, [
            'action' => $action,
            'exception' => $e::class,
            'message' => $sanitizedError,
        ]);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            $action,
            $message,
            'backup',
            null,
            ['exception' => $e::class, 'message' => $sanitizedError],
            'error'
        );

        return ['success' => false, 'error' => $message . ' Bitte Logs prüfen.'];
    }
}
