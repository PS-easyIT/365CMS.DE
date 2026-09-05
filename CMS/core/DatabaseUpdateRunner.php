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
    *     downgrade_detected:bool,
    *     schema_downgrade_detected:bool,
    *     schema_integrity_ok:bool
     * }
     */
    public function getStatus(): array
    {
        $installedVersion = $this->readSetting(self::INSTALLED_VERSION_OPTION);
        $installedSchemaVersion = $this->resolveHighestSchemaVersion(
            $this->readSetting(self::DATABASE_SCHEMA_OPTION),
            $this->readSetting(self::INSTALLED_SCHEMA_OPTION)
        );
        $targetVersion = Version::CURRENT;
        $targetSchemaVersion = SchemaManager::SCHEMA_VERSION;
        $coreDowngradeDetected = $installedVersion !== ''
            && version_compare($installedVersion, $targetVersion, '>');
        $schemaDowngradeDetected = $installedSchemaVersion !== ''
            && $this->compareSchemaVersions($installedSchemaVersion, $targetSchemaVersion) > 0;
        $schemaIntegrityOk = !$schemaDowngradeDetected && $this->isTargetSchemaReady();

        return [
            'installed_version' => $installedVersion,
            'target_version' => $targetVersion,
            'installed_schema_version' => $installedSchemaVersion,
            'target_schema_version' => $targetSchemaVersion,
            'version_update_required' => $installedVersion === ''
                || version_compare($targetVersion, $installedVersion, '>'),
            'schema_update_required' => !$schemaDowngradeDetected && (
                $installedSchemaVersion === ''
                || $this->compareSchemaVersions($targetSchemaVersion, $installedSchemaVersion) > 0
                || !$schemaIntegrityOk
            ),
            'downgrade_detected' => $coreDowngradeDetected || $schemaDowngradeDetected,
            'schema_downgrade_detected' => $schemaDowngradeDetected,
            'schema_integrity_ok' => $schemaIntegrityOk,
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
            if (!$this->isTargetSchemaReady()) {
                throw new \RuntimeException('Das Zielschema ist nach der Migration nicht vollständig verfügbar.');
            }

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

    private function compareSchemaVersions(string $left, string $right): int
    {
        $leftVersion = $this->extractSchemaVersionNumber($left);
        $rightVersion = $this->extractSchemaVersionNumber($right);

        if ($leftVersion !== null && $rightVersion !== null) {
            return $leftVersion <=> $rightVersion;
        }

        return version_compare($left, $right);
    }

    private function extractSchemaVersionNumber(string $version): ?int
    {
        return preg_match('/^v(\d+)$/i', trim($version), $matches) === 1
            ? (int) $matches[1]
            : null;
    }

    private function resolveHighestSchemaVersion(string $databaseSchemaVersion, string $installedSchemaVersion): string
    {
        if ($databaseSchemaVersion === '') {
            return $installedSchemaVersion;
        }
        if ($installedSchemaVersion === '') {
            return $databaseSchemaVersion;
        }

        return $this->compareSchemaVersions($databaseSchemaVersion, $installedSchemaVersion) >= 0
            ? $databaseSchemaVersion
            : $installedSchemaVersion;
    }

    private function isTargetSchemaReady(): bool
    {
        $quotaTable = $this->db->getPrefix() . 'ai_quota_usage';
        if (!$this->db->tableExists($quotaTable)) {
            return false;
        }

        foreach (['scope_name', 'period_key', 'user_id', 'provider_id', 'request_count', 'character_count'] as $column) {
            if (!$this->db->columnExists($quotaTable, $column)) {
                return false;
            }
        }

        return true;
    }

    private function sanitizeDiagnosticText(string $value): string
    {
        $value = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '');

        return function_exists('mb_substr') ? mb_substr($value, 0, 500, 'UTF-8') : substr($value, 0, 500);
    }
}
