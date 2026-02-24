<?php
/**
 * MigrationManager – inkrementelle Spalten-Migrationen (H-10)
 *
 * Verantwortlich für:
 * - Hinzufügen fehlender Spalten / Indizes zu bestehenden Tabellen (ALTER TABLE)
 * - Öffentliche repairTables()-Methode (admin/system.php → DB-Reparatur-Tool)
 *
 * Ausgelagert aus Database.php, um die God-Klasse aufzuteilen.
 *
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

use PDOException;

if (!defined('ABSPATH')) {
    exit;
}

class MigrationManager
{
    private Database $db;
    private string $prefix;

    public function __construct(Database $db)
    {
        $this->db     = $db;
        $this->prefix = $db->getPrefix();
    }

    /**
     * Vollständige DB-Reparatur:
     * - Flag löschen → createTables() wird erneut ausgeführt
     * - Migrations laufen sowieso durch createTables → hier nur repairTables-Wrapper
     */
    public function repairTables(): void
    {
        (new SchemaManager($this->db))->clearFlag();
        (new SchemaManager($this->db))->createTables();
    }

    /**
     * Führt alle inkrementellen Migrations-SQLs aus.
     * Idempotent: PDOException für bereits vorhandene Spalten / Duplikat-Keys werden ignoriert.
     */
    public function run(): void
    {
        $p = $this->prefix;

        $migrations = [
            // RBAC: member_dashboard_access on roles
            "ALTER TABLE `{$p}roles` ADD COLUMN `member_dashboard_access` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Zugriff auf Member-Dashboard' AFTER `capabilities`",
            // RBAC: sort_order on roles
            "ALTER TABLE `{$p}roles` ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0 AFTER `member_dashboard_access`",
            // Groups: role_id on user_groups
            "ALTER TABLE `{$p}user_groups` ADD COLUMN `role_id` INT UNSIGNED NULL COMMENT 'Verknüpfte RBAC-Rolle' AFTER `description`",
            // H-05: action-Feld für DB-basiertes Rate-Limiting (Schema v5)
            "ALTER TABLE `{$p}login_attempts` ADD COLUMN `action` VARCHAR(30) NOT NULL DEFAULT 'login' AFTER `ip_address`",
            "ALTER TABLE `{$p}login_attempts` ADD INDEX `idx_ip_action` (`ip_address`, `action`)",
            // H-02: UNIQUE-Key auf user_meta(user_id, meta_key) für ON DUPLICATE KEY UPDATE (Schema v6)
            "ALTER TABLE `{$p}user_meta` ADD UNIQUE KEY `uq_user_meta` (`user_id`, `meta_key`)",
            
            // H-02: User-Display-Name bei alten Tabellen (falls fehlend)
            "ALTER TABLE `{$p}users` ADD COLUMN `display_name` VARCHAR(100) NOT NULL AFTER `password`",
            
            // H-01: Audit-Log Action-Spalte (Fix für fehlende Spalte bei Update von alter Version)
            "ALTER TABLE `{$p}audit_log` ADD COLUMN `action` VARCHAR(100) NOT NULL DEFAULT 'unknown' AFTER `category`",
            "ALTER TABLE `{$p}audit_log` ADD INDEX `idx_action` (`action`)",
        ];

        $pdo = $this->db->getPdo();
        foreach ($migrations as $sql) {
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {
                $msg = $e->getMessage();
                // Ignorierte Errors: Spalte/Key existiert bereits, Tabelle existiert noch nicht
                if (
                    !str_contains($msg, 'Duplicate column') &&
                    !str_contains($msg, 'Duplicate key name') &&
                    !str_contains($msg, "doesn't exist") &&
                    !str_contains($msg, 'Multiple definition')
                ) {
                    error_log('MigrationManager: ' . $msg);
                }
            }
        }
    }
}
