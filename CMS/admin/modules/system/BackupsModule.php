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

use CMS\Services\BackupService;

class BackupsModule
{
    private BackupService $service;

    public function __construct()
    {
        $this->service = BackupService::getInstance();
    }

    /**
     * Backup-Daten für die Übersicht
     */
    public function getData(): array
    {
        return [
            'backups' => $this->listBackupsSafe(),
            'history' => $this->getHistorySafe(),
        ];
    }

    /**
     * Vollständiges Backup erstellen (DB + Dateien)
     */
    public function createFullBackup(): array
    {
        try {
            $result = $this->service->createFullBackup();
            if (!empty($result['success'])) {
                return ['success' => true, 'message' => 'Vollständiges Backup erstellt: ' . ($result['name'] ?? '')];
            }
            return ['success' => false, 'error' => 'Backup konnte nicht erstellt werden.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Vollständiges Backup konnte nicht erstellt werden.'];
        }
    }

    /**
     * Nur Datenbank-Backup erstellen
     */
    public function createDatabaseBackup(): array
    {
        try {
            $filename = $this->service->createDatabaseBackup();
            if (!empty($filename)) {
                return ['success' => true, 'message' => 'Datenbank-Backup erstellt: ' . $filename];
            }
            return ['success' => false, 'error' => 'DB-Backup konnte nicht erstellt werden.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Datenbank-Backup konnte nicht erstellt werden.'];
        }
    }

    /**
     * Backup löschen
     */
    public function deleteBackup(string $name): array
    {
        if (empty($name)) {
            return ['success' => false, 'error' => 'Kein Backup angegeben.'];
        }

        try {
            $result = $this->service->deleteBackup($name);
            if ($result) {
                return ['success' => true, 'message' => 'Backup gelöscht: ' . $name];
            }
            return ['success' => false, 'error' => 'Backup konnte nicht gelöscht werden.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Backup konnte nicht gelöscht werden.'];
        }
    }

    private function listBackupsSafe(): array
    {
        try {
            return $this->service->listBackups();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getHistorySafe(): array
    {
        try {
            return $this->service->getBackupHistory(15);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
