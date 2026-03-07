<?php
declare(strict_types=1);

/**
 * System-Info & Diagnose-Modul
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;
use CMS\SchemaManager;
use CMS\Services\SystemService;

class SystemInfoModule
{
    private SystemService $service;

    public function __construct()
    {
        $this->service = SystemService::instance();
    }

    /**
     * Alle System-Informationen laden
     */
    public function getData(): array
    {
        return [
            'system'      => $this->getSystemInfoSafe(),
            'database'    => $this->getDatabaseStatusSafe(),
            'tables'      => $this->getTablesSafe(),
            'permissions' => $this->getPermissionsSafe(),
            'directories' => $this->getDirectorySizesSafe(),
            'statistics'  => $this->getStatisticsSafe(),
            'security'    => $this->getSecurityStatusSafe(),
        ];
    }

    /**
     * Cache leeren
     */
    public function clearCache(): array
    {
        try {
            $result = $this->service->clearCache();
            $this->service->clearOldSessions();
            $this->service->clearOldFailedLogins();
            return ['success' => true, 'message' => 'Cache, alte Sessions und Login-Versuche bereinigt.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Datenbank optimieren
     */
    public function optimizeDatabase(): array
    {
        try {
            $results = $this->service->optimizeTables();
            $success = 0;
            $failed  = 0;
            foreach ($results as $table => $res) {
                if (!empty($res['success'])) {
                    $success++;
                } else {
                    $failed++;
                }
            }
            return ['success' => true, 'message' => "$success Tabelle(n) optimiert" . ($failed > 0 ? ", $failed fehlgeschlagen" : '') . '.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Fehlerlogs leeren
     */
    public function clearLogs(): array
    {
        try {
            $this->service->clearErrorLogs();
            return ['success' => true, 'message' => 'Fehlerlogs gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Fehlende Datenbank-Tabellen erstellen
     */
    public function createMissingTables(): array
    {
        try {
            $db = Database::instance();
            $schema = new SchemaManager($db);
            $schema->clearFlag();
            $schema->createTables();
            return ['success' => true, 'message' => 'Fehlende Tabellen wurden erstellt und Migrationen ausgeführt.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Datenbank-Tabellen prüfen und reparieren (CHECK TABLE + REPAIR TABLE)
     */
    public function repairTables(): array
    {
        try {
            $db = Database::instance();
            $pdo = $db->getPdo();
            $prefix = $db->getPrefix();

            $tables = $this->getTablesSafe();
            $repaired = 0;
            $errors = [];

            foreach ($tables as $info) {
                if (!is_array($info) || empty($info['exists'])) {
                    continue;
                }
                $fullTable = $prefix . $info['name'];

                $check = $pdo->query("CHECK TABLE `{$fullTable}`");
                $row = $check ? $check->fetch(\PDO::FETCH_ASSOC) : null;
                $status = $row['Msg_text'] ?? 'OK';

                if (stripos($status, 'ok') === false) {
                    $repair = $pdo->query("REPAIR TABLE `{$fullTable}`");
                    $repairRow = $repair ? $repair->fetch(\PDO::FETCH_ASSOC) : null;
                    $repairStatus = $repairRow['Msg_text'] ?? 'unknown';

                    if (stripos($repairStatus, 'ok') !== false) {
                        $repaired++;
                    } else {
                        $errors[] = $info['name'] . ': ' . $repairStatus;
                    }
                }
            }

            $msg = $repaired > 0
                ? "$repaired Tabelle(n) repariert."
                : 'Alle Tabellen sind in Ordnung — keine Reparatur nötig.';

            if (!empty($errors)) {
                $msg .= ' Fehler bei: ' . implode(', ', $errors);
            }

            return ['success' => true, 'message' => $msg];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    private function getSystemInfoSafe(): array
    {
        try { return $this->service->getSystemInfo(); }
        catch (\Throwable $e) { return ['error' => $e->getMessage()]; }
    }

    private function getDatabaseStatusSafe(): array
    {
        try { return $this->service->getDatabaseStatus(); }
        catch (\Throwable $e) { return ['error' => $e->getMessage()]; }
    }

    private function getTablesSafe(): array
    {
        try { return $this->service->checkDatabaseTables(); }
        catch (\Throwable $e) { return []; }
    }

    private function getPermissionsSafe(): array
    {
        try { return $this->service->checkFilePermissions(); }
        catch (\Throwable $e) { return []; }
    }

    private function getDirectorySizesSafe(): array
    {
        try { return $this->service->getDirectorySizes(); }
        catch (\Throwable $e) { return []; }
    }

    private function getStatisticsSafe(): array
    {
        try { return $this->service->getCMSStatistics(); }
        catch (\Throwable $e) { return []; }
    }

    private function getSecurityStatusSafe(): array
    {
        try { return $this->service->getSecurityStatus(); }
        catch (\Throwable $e) { return []; }
    }
}
