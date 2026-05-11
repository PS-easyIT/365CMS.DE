<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class PerformanceSafetyNetService
{
    private const SNAPSHOT_ROOT = ABSPATH . 'backups/performance-rollbacks/';
    private const CACHE_SNAPSHOT_ROOT = self::SNAPSHOT_ROOT . 'cache/';
    private const DATABASE_SNAPSHOT_ROOT = self::SNAPSHOT_ROOT . 'database/';
    private const ROLLBACK_WINDOW_SECONDS = 3600;

    private static ?self $instance = null;

    private readonly BackupService $backupService;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->backupService = BackupService::getInstance();
    }

    public function getRollbackWindowSeconds(): int
    {
        return self::ROLLBACK_WINDOW_SECONDS;
    }

    /** @return array<string, mixed> */
    public function getLatestCacheSnapshot(): array
    {
        return $this->formatSnapshotInfo($this->loadLatestSnapshot('cache'));
    }

    /** @return array<string, mixed> */
    public function getLatestDatabaseSnapshot(): array
    {
        return $this->formatSnapshotInfo($this->loadLatestSnapshot('database'));
    }

    /** @return array{success:bool,error?:string,snapshot?:array<string,mixed>} */
    public function createCacheSnapshot(string $action): array
    {
        try {
            $this->ensureDirectories();

            $files = $this->getTopLevelCacheFiles();
            $snapshotId = $this->buildSnapshotId('cache');
            $snapshotDir = self::CACHE_SNAPSHOT_ROOT . $snapshotId . DIRECTORY_SEPARATOR;
            $filesDir = $snapshotDir . 'files' . DIRECTORY_SEPARATOR;

            $this->ensureDirectory($filesDir);

            $manifestFiles = [];
            $totalSize = 0;
            foreach ($files as $filePath) {
                $basename = basename($filePath);
                $targetPath = $filesDir . $basename;
                if (!copy($filePath, $targetPath)) {
                    throw new \RuntimeException('Cache-Datei konnte nicht in den Snapshot kopiert werden: ' . $basename);
                }

                $size = (int) filesize($filePath);
                $totalSize += $size;
                $manifestFiles[] = [
                    'name' => $basename,
                    'size_bytes' => $size,
                ];
            }

            $snapshot = [
                'type' => 'cache',
                'snapshot_id' => $snapshotId,
                'action' => $action,
                'created_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', time() + self::ROLLBACK_WINDOW_SECONDS),
                'file_count' => count($manifestFiles),
                'file_size_bytes' => $totalSize,
                'files' => $manifestFiles,
                'restores_runtime_caches' => false,
                'rolled_back_at' => null,
            ];

            $manifestPath = $snapshotDir . 'manifest.json';
            $this->writeJsonFile($manifestPath, $snapshot);
            $snapshot['_path'] = $manifestPath;

            return [
                'success' => true,
                'snapshot' => $this->formatSnapshotInfo($snapshot),
            ];
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('performance')->warning('Cache snapshot could not be created.', [
                'action' => $action,
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'error' => 'Vor der Cache-Bereinigung konnte kein Rollback-Snapshot erstellt werden.',
            ];
        }
    }

    /** @return array{success:bool,message:string,error?:string,snapshot?:array<string,mixed>,restored_files?:int} */
    public function rollbackLatestCacheSnapshot(): array
    {
        $snapshot = $this->loadLatestSnapshot('cache', true);
        if ($snapshot === null) {
            return [
                'success' => false,
                'error' => 'Kein aktiver Cache-Snapshot für ein Rollback verfügbar.',
                'message' => 'Kein aktiver Cache-Snapshot für ein Rollback verfügbar.',
            ];
        }

        $manifestPath = (string) ($snapshot['_path'] ?? '');
        $snapshotDir = dirname($manifestPath);
        $filesDir = $snapshotDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;
        $fileEntries = is_array($snapshot['files'] ?? null) ? $snapshot['files'] : [];

        if ($fileEntries === []) {
            $snapshot['rolled_back_at'] = date('Y-m-d H:i:s');
            $this->writeJsonFile($manifestPath, $snapshot);

            return [
                'success' => true,
                'message' => 'Es gab keine Datei-Cache-Einträge zum Wiederherstellen.',
                'snapshot' => $this->formatSnapshotInfo($snapshot),
                'restored_files' => 0,
            ];
        }

        $sourceFiles = [];
        foreach ($fileEntries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $name = basename((string) ($entry['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $sourcePath = $filesDir . $name;
            if (!is_file($sourcePath) || !is_readable($sourcePath)) {
                return [
                    'success' => false,
                    'error' => 'Der Snapshot ist unvollständig und kann nicht sicher zurückgespielt werden.',
                    'message' => 'Der Snapshot ist unvollständig und kann nicht sicher zurückgespielt werden.',
                    'snapshot' => $this->formatSnapshotInfo($snapshot),
                ];
            }

            $sourceFiles[$name] = $sourcePath;
        }

        try {
            foreach ($this->getTopLevelCacheFiles() as $filePath) {
                if (!unlink($filePath)) {
                    throw new \RuntimeException('Vorhandene Cache-Datei konnte vor dem Rollback nicht gelöscht werden: ' . basename($filePath));
                }
            }

            $restoredFiles = 0;
            foreach ($sourceFiles as $name => $sourcePath) {
                $targetPath = ABSPATH . 'cache/' . $name;
                if (!copy($sourcePath, $targetPath)) {
                    throw new \RuntimeException('Snapshot-Datei konnte nicht wiederhergestellt werden: ' . $name);
                }

                $restoredFiles++;
            }

            $snapshot['rolled_back_at'] = date('Y-m-d H:i:s');
            $this->writeJsonFile($manifestPath, $snapshot);

            return [
                'success' => true,
                'message' => sprintf('%d Datei-Cache-Datei%s aus dem letzten Snapshot wiederhergestellt.', $restoredFiles, $restoredFiles === 1 ? '' : 'en'),
                'snapshot' => $this->formatSnapshotInfo($snapshot),
                'restored_files' => $restoredFiles,
            ];
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('performance')->error('Cache snapshot rollback failed.', [
                'snapshot_id' => (string) ($snapshot['snapshot_id'] ?? ''),
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'error' => 'Der Cache-Snapshot konnte nicht vollständig wiederhergestellt werden.',
                'message' => 'Der Cache-Snapshot konnte nicht vollständig wiederhergestellt werden.',
                'snapshot' => $this->formatSnapshotInfo($snapshot),
            ];
        }
    }

    /** @return array{success:bool,error?:string,snapshot?:array<string,mixed>} */
    public function createDatabaseSnapshot(string $action): array
    {
        try {
            $this->ensureDirectories();

            $backup = $this->backupService->createStandaloneDatabaseBackup();
            if (empty($backup['success']) || empty($backup['name'])) {
                return [
                    'success' => false,
                    'error' => 'Vor der Datenbank-Wartung konnte kein Datenbank-Backup erstellt werden.',
                ];
            }

            $snapshotId = $this->buildSnapshotId('database');
            $snapshotPath = self::DATABASE_SNAPSHOT_ROOT . $snapshotId . '.json';
            $snapshot = [
                'type' => 'database',
                'snapshot_id' => $snapshotId,
                'action' => $action,
                'created_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', time() + self::ROLLBACK_WINDOW_SECONDS),
                'backup_name' => (string) $backup['name'],
                'backup_size_bytes' => (int) ($backup['manifest']['size'] ?? 0),
                'rolled_back_at' => null,
            ];

            $this->writeJsonFile($snapshotPath, $snapshot);
            $snapshot['_path'] = $snapshotPath;

            return [
                'success' => true,
                'snapshot' => $this->formatSnapshotInfo($snapshot),
            ];
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('performance')->warning('Database maintenance snapshot could not be created.', [
                'action' => $action,
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'error' => 'Vor der Datenbank-Wartung konnte kein Rollback-Backup erstellt werden.',
            ];
        }
    }

    /** @return array{success:bool,message:string,error?:string,snapshot?:array<string,mixed>,rollback_backup?:string} */
    public function rollbackLatestDatabaseSnapshot(): array
    {
        $snapshot = $this->loadLatestSnapshot('database', true);
        if ($snapshot === null) {
            return [
                'success' => false,
                'error' => 'Kein aktiver Datenbank-Snapshot für ein Rollback verfügbar.',
                'message' => 'Kein aktiver Datenbank-Snapshot für ein Rollback verfügbar.',
            ];
        }

        $backupName = trim((string) ($snapshot['backup_name'] ?? ''));
        if ($backupName === '') {
            return [
                'success' => false,
                'error' => 'Der gespeicherte Rollback-Backup-Name fehlt.',
                'message' => 'Der gespeicherte Rollback-Backup-Name fehlt.',
                'snapshot' => $this->formatSnapshotInfo($snapshot),
            ];
        }

        try {
            $restore = $this->backupService->restoreBackup($backupName);
            if (empty($restore['success'])) {
                throw new \RuntimeException('Restore des Datenbank-Snapshots fehlgeschlagen.');
            }

            $snapshot['rolled_back_at'] = date('Y-m-d H:i:s');
            $snapshot['rollback_backup'] = (string) ($restore['rollback_backup'] ?? '');
            $this->writeJsonFile((string) $snapshot['_path'], $snapshot);

            return [
                'success' => true,
                'message' => 'Datenbank-Snapshot erfolgreich wiederhergestellt. Vor dem Restore wurde automatisch ein neuer Sicherungs-Snapshot erstellt.',
                'snapshot' => $this->formatSnapshotInfo($snapshot),
                'rollback_backup' => (string) ($restore['rollback_backup'] ?? ''),
            ];
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('performance')->error('Database maintenance rollback failed.', [
                'snapshot_id' => (string) ($snapshot['snapshot_id'] ?? ''),
                'backup_name' => $backupName,
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'error' => 'Der Datenbank-Snapshot konnte nicht wiederhergestellt werden.',
                'message' => 'Der Datenbank-Snapshot konnte nicht wiederhergestellt werden.',
                'snapshot' => $this->formatSnapshotInfo($snapshot),
            ];
        }
    }

    private function ensureDirectories(): void
    {
        $this->ensureDirectory(self::SNAPSHOT_ROOT);
        $this->ensureDirectory(self::CACHE_SNAPSHOT_ROOT);
        $this->ensureDirectory(self::DATABASE_SNAPSHOT_ROOT);
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException('Verzeichnis konnte nicht erstellt werden: ' . $path);
        }
    }

    private function buildSnapshotId(string $prefix): string
    {
        return $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
    }

    /** @return list<string> */
    private function getTopLevelCacheFiles(): array
    {
        $files = glob(ABSPATH . 'cache/*') ?: [];

        return array_values(array_filter($files, static function (string $path): bool {
            return is_file($path) && !is_link($path);
        }));
    }

    /** @return array<string, mixed>|null */
    private function loadLatestSnapshot(string $type, bool $onlyRestorable = false): ?array
    {
        $snapshotFiles = $type === 'cache'
            ? (glob(self::CACHE_SNAPSHOT_ROOT . '*/manifest.json') ?: [])
            : (glob(self::DATABASE_SNAPSHOT_ROOT . '*.json') ?: []);

        if ($snapshotFiles === []) {
            return null;
        }

        usort($snapshotFiles, static function (string $left, string $right): int {
            return ((int) filemtime($right)) <=> ((int) filemtime($left));
        });

        foreach ($snapshotFiles as $path) {
            $snapshot = $this->loadJsonFile($path);
            if ($snapshot === null) {
                continue;
            }

            $snapshot['_path'] = $path;
            if ($onlyRestorable && !$this->isSnapshotRestorable($snapshot)) {
                continue;
            }

            return $snapshot;
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    private function loadJsonFile(string $path): ?array
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /** @param array<string, mixed> $snapshot */
    private function writeJsonFile(string $path, array $snapshot): void
    {
        $payload = $snapshot;
        unset($payload['_path']);

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json) || file_put_contents($path, $json, LOCK_EX) === false) {
            throw new \RuntimeException('Snapshot-Datei konnte nicht geschrieben werden.');
        }
    }

    /** @param array<string, mixed> $snapshot */
    private function isSnapshotRestorable(array $snapshot): bool
    {
        if (!empty($snapshot['rolled_back_at'])) {
            return false;
        }

        $expiresAt = strtotime((string) ($snapshot['expires_at'] ?? ''));

        return $expiresAt !== false && $expiresAt >= time();
    }

    /** @param array<string, mixed>|null $snapshot @return array<string, mixed> */
    private function formatSnapshotInfo(?array $snapshot): array
    {
        if ($snapshot === null) {
            return [
                'available' => false,
            ];
        }

        $expiresAt = strtotime((string) ($snapshot['expires_at'] ?? ''));

        return [
            'available' => true,
            'type' => (string) ($snapshot['type'] ?? ''),
            'snapshot_id' => (string) ($snapshot['snapshot_id'] ?? ''),
            'action' => (string) ($snapshot['action'] ?? ''),
            'created_at' => (string) ($snapshot['created_at'] ?? ''),
            'expires_at' => (string) ($snapshot['expires_at'] ?? ''),
            'is_expired' => $expiresAt === false || $expiresAt < time(),
            'file_count' => (int) ($snapshot['file_count'] ?? 0),
            'file_size_bytes' => (int) ($snapshot['file_size_bytes'] ?? 0),
            'backup_name' => (string) ($snapshot['backup_name'] ?? ''),
            'backup_size_bytes' => (int) ($snapshot['backup_size_bytes'] ?? 0),
            'restores_runtime_caches' => !empty($snapshot['restores_runtime_caches']),
            'rolled_back_at' => (string) ($snapshot['rolled_back_at'] ?? ''),
            'rollback_backup' => (string) ($snapshot['rollback_backup'] ?? ''),
        ];
    }
}