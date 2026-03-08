<?php
/**
 * Zentrale Settings-Abstraktion auf Basis der vorhandenen cms_settings-Tabelle.
 *
 * Nutzt die bestehende Tabelle `{prefix}settings` (physisch meist `cms_settings`)
 * und kapselt Gruppen/Keys sowie optionale Verschlüsselung für Secrets.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;
use CMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class SettingsService
{
    private const ENCRYPTION_PREFIX = 'enc:';
    private const JSON_PREFIX = 'json:';
    private const CIPHER = 'AES-256-CBC';

    private static ?self $instance = null;

    private Database $db;
    private Logger $logger;
    private string $prefix;
    private string $keyMaterial;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = Database::instance();
        $this->logger = Logger::instance()->withChannel('settings');
        $this->prefix = $this->db->getPrefix();
        $this->keyMaterial = hash(
            'sha256',
            (defined('AUTH_KEY') ? (string) AUTH_KEY : '') . '|' . (defined('SECURE_AUTH_KEY') ? (string) SECURE_AUTH_KEY : ''),
            true
        );
    }

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $optionName = $this->buildOptionName($group, $key);

        try {
            $value = $this->db->get_var(
                "SELECT option_value FROM {$this->prefix}settings WHERE option_name = ? LIMIT 1",
                [$optionName]
            );
        } catch (\Throwable $e) {
            $this->logger->warning('Settings-Lookup fehlgeschlagen für {option}', [
                'option' => $optionName,
                'exception' => $e,
            ]);
            return $default;
        }

        if ($value === null) {
            return $default;
        }

        try {
            return $this->decodeValue((string) $value);
        } catch (\Throwable $e) {
            $this->logger->error('Settings-Wert konnte nicht dekodiert werden: {option}', [
                'option' => $optionName,
                'exception' => $e,
            ]);
            return $default;
        }
    }

    public function getString(string $group, string $key, string $default = ''): string
    {
        $value = $this->get($group, $key, $default);

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return $default;
    }

    public function getInt(string $group, string $key, int $default = 0): int
    {
        $value = $this->get($group, $key, $default);
        return is_numeric($value) ? (int) $value : $default;
    }

    public function getBool(string $group, string $key, bool $default = false): bool
    {
        $value = $this->get($group, $key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function getGroup(string $group): array
    {
        $group = $this->sanitizeSegment($group);
        $prefix = $group . '.';

        try {
            $rows = $this->db->get_results(
                "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name LIKE ? ORDER BY option_name ASC",
                [$prefix . '%']
            ) ?: [];
        } catch (\Throwable $e) {
            $this->logger->warning('Settings-Gruppe konnte nicht geladen werden: {group}', [
                'group' => $group,
                'exception' => $e,
            ]);
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $optionName = (string) ($row->option_name ?? '');
            if (!str_starts_with($optionName, $prefix)) {
                continue;
            }

            $key = substr($optionName, strlen($prefix));
            $result[$key] = $this->decodeValue((string) ($row->option_value ?? ''));
        }

        return $result;
    }

    public function set(string $group, string $key, mixed $value, bool $encrypted = false, int $autoload = 0): bool
    {
        $optionName = $this->buildOptionName($group, $key);
        $storedValue = $this->encodeValue($value, $encrypted);
        $autoload = $autoload === 1 ? 1 : 0;

        try {
            $this->db->execute(
                "INSERT INTO {$this->prefix}settings (option_name, option_value, autoload)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE option_value = VALUES(option_value), autoload = VALUES(autoload)",
                [$optionName, $storedValue, $autoload]
            );
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Setting konnte nicht gespeichert werden: {option}', [
                'option' => $optionName,
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * @param array<string, mixed> $values
     * @param list<string> $encryptedKeys
     */
    public function setMany(string $group, array $values, array $encryptedKeys = [], int $autoload = 0): bool
    {
        foreach ($values as $key => $value) {
            $isEncrypted = in_array((string) $key, $encryptedKeys, true);
            $rowAutoload = $isEncrypted ? 0 : $autoload;
            if (!$this->set($group, (string) $key, $value, $isEncrypted, $rowAutoload)) {
                return false;
            }
        }

        return true;
    }

    public function forget(string $group, string $key): bool
    {
        $optionName = $this->buildOptionName($group, $key);

        try {
            $this->db->execute(
                "DELETE FROM {$this->prefix}settings WHERE option_name = ?",
                [$optionName]
            );
            return true;
        } catch (\Throwable $e) {
            $this->logger->warning('Setting konnte nicht gelöscht werden: {option}', [
                'option' => $optionName,
                'exception' => $e,
            ]);
            return false;
        }
    }

    private function buildOptionName(string $group, string $key): string
    {
        return $this->sanitizeSegment($group) . '.' . $this->sanitizeSegment($key);
    }

    private function sanitizeSegment(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9._-]/', '', $value) ?? '';

        if ($value === '') {
            throw new \InvalidArgumentException('Ungültiger Settings-Key oder Gruppenname.');
        }

        return $value;
    }

    private function encodeValue(mixed $value, bool $encrypted): string
    {
        $normalized = $this->normalizeValue($value);

        if (!$encrypted) {
            return $normalized;
        }

        return self::ENCRYPTION_PREFIX . $this->encrypt($normalized);
    }

    private function decodeValue(string $value): mixed
    {
        $decoded = $value;
        if (str_starts_with($decoded, self::ENCRYPTION_PREFIX)) {
            $decoded = $this->decrypt(substr($decoded, strlen(self::ENCRYPTION_PREFIX)));
        }

        if (str_starts_with($decoded, self::JSON_PREFIX)) {
            return json_decode(substr($decoded, strlen(self::JSON_PREFIX)), true, 512, JSON_THROW_ON_ERROR);
        }

        return $decoded;
    }

    private function normalizeValue(mixed $value): string
    {
        if (is_array($value) || is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            return self::JSON_PREFIX . json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_object($value)) {
            return self::JSON_PREFIX . json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }

    private function encrypt(string $plainText): string
    {
        if (!function_exists('openssl_encrypt')) {
            throw new \RuntimeException('OpenSSL ist für die Verschlüsselung von Einstellungen erforderlich.');
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if (!is_int($ivLength) || $ivLength <= 0) {
            throw new \RuntimeException('Ungültige IV-Länge für Settings-Verschlüsselung.');
        }

        $iv = random_bytes($ivLength);
        $cipherText = openssl_encrypt($plainText, self::CIPHER, $this->keyMaterial, OPENSSL_RAW_DATA, $iv);

        if (!is_string($cipherText) || $cipherText === '') {
            throw new \RuntimeException('Settings-Wert konnte nicht verschlüsselt werden.');
        }

        return base64_encode($iv . $cipherText);
    }

    private function decrypt(string $payload): string
    {
        if (!function_exists('openssl_decrypt')) {
            throw new \RuntimeException('OpenSSL ist für die Entschlüsselung von Einstellungen erforderlich.');
        }

        $raw = base64_decode($payload, true);
        if (!is_string($raw) || $raw === '') {
            throw new \RuntimeException('Verschlüsselter Settings-Payload ist ungültig.');
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if (!is_int($ivLength) || strlen($raw) <= $ivLength) {
            throw new \RuntimeException('Verschlüsselter Settings-Payload ist unvollständig.');
        }

        $iv = substr($raw, 0, $ivLength);
        $cipherText = substr($raw, $ivLength);
        $plainText = openssl_decrypt($cipherText, self::CIPHER, $this->keyMaterial, OPENSSL_RAW_DATA, $iv);

        if (!is_string($plainText)) {
            throw new \RuntimeException('Settings-Wert konnte nicht entschlüsselt werden.');
        }

        return $plainText;
    }
}
