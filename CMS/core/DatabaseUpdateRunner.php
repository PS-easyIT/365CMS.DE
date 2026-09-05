<?php
declare(strict_types=1);

/**
 * Versionsgeführter Datenbank-Updater für bestehende 365CMS-Installationen.
 *
 * Führt ausschließlich den zentralen, idempotenten Schema-Reparaturpfad aus.
 * Inhalte, Benutzer und Einstellungen werden nicht gelöscht.
 *
 * @package CMSv2\Core
 */

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

final class DatabaseUpdateRunner
{
    private const INSTALLED_VERSION_OPTION = 'installed_cms_version';
    private const INSTALLED_AT_OPTION = 'installed_cms_updated_at';
    private const INSTALLED_SCHEMA_OPTION = 'installed_cms_schema_version';
    private const DATABASE_SCHEMA_OPTION = 'db_schema_version';

    public function __construct(private readonly Database $db)
    {
    }

    /**
     * @return array{
     *     installed_version:string,
     *     target_version:string,
     *     installed_schema_version:string,
     *     target_schema_version:string,
     *     version_update_required:bool,
     *     schema_update_required:bool,
     *     downgrade_detected:bool
     * }
     */
    public function getStatus(): array
    {
        $installedVersion = $this->readSetting(self::INSTALLED_VERSION_OPTION);
        $installedSchemaVersion = $this->readSetting(self::DATABASE_SCHEMA_OPTION);
        if ($installedSchemaVersion === '') {
            $installedSchemaVersion = $this->readSetting(self::INSTALLED_SCHEMA_OPTION);
        }
        $targetVersion = Version::CURRENT;
        $targetSchemaVersion = SchemaManager::SCHEMA_VERSION;
        $downgradeDetected = $installedVersion !== ''
            && version_compare($installedVersion, $targetVersion, '>');

        return [
            'installed_version' => $installedVersion,
            'target_version' => $targetVersion,
            'installed_schema_version' => $installedSchemaVersion,
            'target_schema_version' => $targetSchemaVersion,
            'version_update_required' => $installedVersion === ''
                || version_compare($targetVersion, $installedVersion, '>'),
            'schema_update_required' => $installedSchemaVersion !== $targetSchemaVersion,
            'downgrade_detected' => $downgradeDetected,
        ];
    }

    /**
     * @return array{success:bool,message:string,before:array<string,mixed>,after:array<string,mixed>}
     */
    public function run(): array
    {
        $before = $this->getStatus();

        if (!empty($before['downgrade_detected'])) {
            return [
                'success' => false,
                'message' => 'Ein Datenbank-Downgrade wird aus Sicherheitsgründen nicht automatisch ausgeführt.',
                'before' => $before,
                'after' => $before,
            ];
        }

        try {
            // repairTables() löscht nur die Schema-Flag-Datei und führt die zentralen,
            // idempotenten CREATE-/ALTER-Migrationen erneut aus. Es löscht keine Daten.
            $this->db->repairTables();

            $this->writeSetting(self::INSTALLED_VERSION_OPTION, Version::CURRENT);
            $this->writeSetting(self::INSTALLED_AT_OPTION, date(DATE_ATOM));
            $this->writeSetting(self::INSTALLED_SCHEMA_OPTION, SchemaManager::SCHEMA_VERSION);
            $this->writeSetting(self::DATABASE_SCHEMA_OPTION, SchemaManager::SCHEMA_VERSION);

            $after = $this->getStatus();

            try {
                \CMS\Services\UpdateService::getInstance()->logUpdate(
                    'schema',
                    '365CMS Datenbankschema',
                    Version::CURRENT
                );
            } catch (\Throwable) {
                // Das Schema-Update selbst bleibt erfolgreich, wenn die optionale Historie scheitert.
            }

            return [
                'success' => true,
                'message' => 'Datenbankschema und Installationsversion wurden erfolgreich aktualisiert.',
                'before' => $before,
                'after' => $after,
            ];
        } catch (\Throwable $e) {
            error_log('DatabaseUpdateRunner::run failed: ' . $this->sanitizeDiagnosticText($e->getMessage()));

            return [
                'success' => false,
                'message' => 'Das Datenbank-Update konnte nicht abgeschlossen werden. Bitte die CMS-Logs prüfen.',
                'before' => $before,
                'after' => $this->getStatus(),
            ];
        }
    }

    private function readSetting(string $optionName): string
    {
        if (!$this->db->tableExists($this->db->getPrefix() . 'settings')) {
            return '';
        }

        try {
            return trim((string) $this->db->get_var(
                "SELECT option_value FROM {$this->db->getPrefix()}settings WHERE option_name = ? LIMIT 1",
                [$optionName]
            ));
        } catch (\Throwable) {
            return '';
        }
    }

    private function writeSetting(string $optionName, string $value): void
    {
        $this->db->execute(
            "INSERT INTO {$this->db->getPrefix()}settings (option_name, option_value, autoload)
             VALUES (?, ?, 0)
             ON DUPLICATE KEY UPDATE option_value = VALUES(option_value), autoload = VALUES(autoload)",
            [$optionName, $value]
        );
    }

    private function sanitizeDiagnosticText(string $value): string
    {
        $value = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '');

        return function_exists('mb_substr') ? mb_substr($value, 0, 500, 'UTF-8') : substr($value, 0, 500);
    }
}
