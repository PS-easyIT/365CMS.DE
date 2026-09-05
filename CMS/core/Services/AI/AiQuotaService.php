<?php
declare(strict_types=1);

namespace CMS\Services\AI;

use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class AiQuotaService
{
    private const USER_DAILY_REQUESTS = 'user_daily_requests';
    private const USER_DAILY_CHARACTERS = 'user_daily_characters';
    private const PROVIDER_MONTHLY_REQUESTS = 'provider_monthly_requests';

    private Database $db;
    private string $table;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
        $this->table = $this->db->getPrefix() . 'ai_quota_usage';
        $this->ensureTable();
    }

    /**
     * Reserves a logical AI request atomically before the provider is called.
     * Failed requests remain counted, preventing repeated failure loops from bypassing quotas.
     *
     * @param array<string, mixed> $quotaConfig
     */
    public function reserve(int $userId, string $providerId, int $characterCount, array $quotaConfig, bool $countUserQuota = true): void
    {
        if ($userId <= 0) {
            throw new \RuntimeException('AI-Quota kann keinem angemeldeten Benutzer zugeordnet werden.');
        }

        $providerId = $this->normalizeProviderId($providerId);
        if ($providerId === '') {
            throw new \RuntimeException('AI-Quota kann keinem Provider zugeordnet werden.');
        }

        $characterCount = max(0, $characterCount);
        $today = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d');
        $month = substr($today, 0, 7);
        $limits = [
            self::USER_DAILY_REQUESTS => max(1, (int) ($quotaConfig['daily_requests_per_user'] ?? 40)),
            self::USER_DAILY_CHARACTERS => max(500, (int) ($quotaConfig['daily_chars_per_user'] ?? 120000)),
            self::PROVIDER_MONTHLY_REQUESTS => max(10, (int) ($quotaConfig['monthly_requests_per_provider'] ?? 5000)),
        ];
        $reservations = [[self::PROVIDER_MONTHLY_REQUESTS, $month, 0, $providerId, 1, 0]];
        if ($countUserQuota) {
            $reservations[] = [self::USER_DAILY_REQUESTS, $today, $userId, '', 1, 0];
            $reservations[] = [self::USER_DAILY_CHARACTERS, $today, $userId, '', 0, $characterCount];
        }

        usort($reservations, static fn (array $left, array $right): int => implode('|', $left) <=> implode('|', $right));

        $pdo = $this->db->getPdo();
        try {
            $pdo->beginTransaction();

            foreach ($reservations as [$scope, $period, $reservedUserId, $reservedProviderId, $requests, $characters]) {
                $this->db->execute(
                    "INSERT IGNORE INTO {$this->table} (scope_name, period_key, user_id, provider_id, request_count, character_count, updated_at)
                     VALUES (?, ?, ?, ?, 0, 0, NOW())",
                    [$scope, $period, $reservedUserId, $reservedProviderId]
                );

                $row = $this->db->get_row(
                    "SELECT request_count, character_count
                     FROM {$this->table}
                     WHERE scope_name = ? AND period_key = ? AND user_id = ? AND provider_id = ?
                     FOR UPDATE",
                    [$scope, $period, $reservedUserId, $reservedProviderId]
                );
                $currentValue = $scope === self::USER_DAILY_CHARACTERS
                    ? (int) ($row->character_count ?? 0)
                    : (int) ($row->request_count ?? 0);
                $increment = $scope === self::USER_DAILY_CHARACTERS ? $characters : $requests;

                if ($currentValue + $increment > $limits[$scope]) {
                    throw new \RuntimeException($this->getLimitMessage($scope));
                }

                $this->db->execute(
                    "UPDATE {$this->table}
                     SET request_count = request_count + ?, character_count = character_count + ?, updated_at = NOW()
                     WHERE scope_name = ? AND period_key = ? AND user_id = ? AND provider_id = ?",
                    [$requests, $characters, $scope, $period, $reservedUserId, $reservedProviderId]
                );
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Reserves the user-visible AI operation exactly once before execution starts.
     *
     * @param array<string, mixed> $quotaConfig
     */
    public function reserveUserOperation(int $userId, int $characterCount, array $quotaConfig): void
    {
        if ($userId <= 0) {
            throw new \RuntimeException('AI-Quota kann keinem angemeldeten Benutzer zugeordnet werden.');
        }

        $characterCount = max(0, $characterCount);
        $today = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d');
        $limits = [
            self::USER_DAILY_REQUESTS => max(1, (int) ($quotaConfig['daily_requests_per_user'] ?? 40)),
            self::USER_DAILY_CHARACTERS => max(500, (int) ($quotaConfig['daily_chars_per_user'] ?? 120000)),
        ];

        $this->reserveRows([
            [self::USER_DAILY_REQUESTS, $today, $userId, '', 1, 0],
            [self::USER_DAILY_CHARACTERS, $today, $userId, '', 0, $characterCount],
        ], $limits);
    }

    /**
     * Reserves every actual provider call, including a batch, retry or fallback call.
     *
     * @param array<string, mixed> $quotaConfig
     */
    public function reserveProviderCall(string $providerId, array $quotaConfig): void
    {
        $providerId = $this->normalizeProviderId($providerId);
        if ($providerId === '') {
            throw new \RuntimeException('AI-Quota kann keinem Provider zugeordnet werden.');
        }

        $month = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m');
        $this->reserveRows([
            [self::PROVIDER_MONTHLY_REQUESTS, $month, 0, $providerId, 1, 0],
        ], [
            self::PROVIDER_MONTHLY_REQUESTS => max(10, (int) ($quotaConfig['monthly_requests_per_provider'] ?? 5000)),
        ]);
    }

    /**
     * @param array<string, mixed> $quotaConfig
     * @return array{requests_24h:int,request_limit:int,chars_24h:int,char_limit:int,requests_30d:int,provider_request_limit:int}
     */
    public function getCurrentUsage(int $userId, string $providerId, array $quotaConfig): array
    {
        $providerId = $this->normalizeProviderId($providerId);
        $today = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d');
        $month = substr($today, 0, 7);

        return [
            'requests_24h' => $this->getCount(self::USER_DAILY_REQUESTS, $today, max(0, $userId), ''),
            'request_limit' => max(1, (int) ($quotaConfig['daily_requests_per_user'] ?? 40)),
            'chars_24h' => $this->getCount(self::USER_DAILY_CHARACTERS, $today, max(0, $userId), '', true),
            'char_limit' => max(500, (int) ($quotaConfig['daily_chars_per_user'] ?? 120000)),
            'requests_30d' => $providerId === '' ? 0 : $this->getCount(self::PROVIDER_MONTHLY_REQUESTS, $month, 0, $providerId),
            'provider_request_limit' => max(10, (int) ($quotaConfig['monthly_requests_per_provider'] ?? 5000)),
        ];
    }

    public function prune(int $retentionDays): void
    {
        $retentionDays = max(31, min(3650, $retentionDays));
        $cutoff = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('-' . $retentionDays . ' days')->format('Y-m-d');
        $this->db->execute("DELETE FROM {$this->table} WHERE period_key < ?", [$cutoff]);
    }

    /**
     * @param list<array{0:string,1:string,2:int,3:string,4:int,5:int}> $reservations
     * @param array<string, int> $limits
     */
    private function reserveRows(array $reservations, array $limits): void
    {
        usort($reservations, static fn (array $left, array $right): int => implode('|', $left) <=> implode('|', $right));

        $pdo = $this->db->getPdo();
        try {
            $pdo->beginTransaction();

            foreach ($reservations as [$scope, $period, $reservedUserId, $reservedProviderId, $requests, $characters]) {
                $this->db->execute(
                    "INSERT IGNORE INTO {$this->table} (scope_name, period_key, user_id, provider_id, request_count, character_count, updated_at)
                     VALUES (?, ?, ?, ?, 0, 0, NOW())",
                    [$scope, $period, $reservedUserId, $reservedProviderId]
                );

                $row = $this->db->get_row(
                    "SELECT request_count, character_count
                     FROM {$this->table}
                     WHERE scope_name = ? AND period_key = ? AND user_id = ? AND provider_id = ?
                     FOR UPDATE",
                    [$scope, $period, $reservedUserId, $reservedProviderId]
                );
                $currentValue = $scope === self::USER_DAILY_CHARACTERS
                    ? (int) ($row->character_count ?? 0)
                    : (int) ($row->request_count ?? 0);
                $increment = $scope === self::USER_DAILY_CHARACTERS ? $characters : $requests;

                if ($currentValue + $increment > ($limits[$scope] ?? 0)) {
                    throw new \RuntimeException($this->getLimitMessage($scope));
                }

                $this->db->execute(
                    "UPDATE {$this->table}
                     SET request_count = request_count + ?, character_count = character_count + ?, updated_at = NOW()
                     WHERE scope_name = ? AND period_key = ? AND user_id = ? AND provider_id = ?",
                    [$requests, $characters, $scope, $period, $reservedUserId, $reservedProviderId]
                );
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    private function ensureTable(): void
    {
        $this->db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS {$this->table} (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                scope_name VARCHAR(40) NOT NULL,
                period_key VARCHAR(10) NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
                provider_id VARCHAR(80) NOT NULL DEFAULT '',
                request_count INT UNSIGNED NOT NULL DEFAULT 0,
                character_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_ai_quota_scope_period (scope_name, period_key, user_id, provider_id),
                INDEX idx_ai_quota_updated (updated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private function getCount(string $scope, string $period, int $userId, string $providerId, bool $characters = false): int
    {
        $column = $characters ? 'character_count' : 'request_count';
        $value = $this->db->get_var(
            "SELECT {$column} FROM {$this->table} WHERE scope_name = ? AND period_key = ? AND user_id = ? AND provider_id = ? LIMIT 1",
            [$scope, $period, $userId, $providerId]
        );

        return max(0, (int) $value);
    }

    private function getLimitMessage(string $scope): string
    {
        return match ($scope) {
            self::USER_DAILY_REQUESTS => 'Das tägliche AI-Request-Kontingent für diesen Benutzer ist ausgeschöpft.',
            self::USER_DAILY_CHARACTERS => 'Das tägliche AI-Zeichenkontingent für diesen Benutzer ist ausgeschöpft.',
            self::PROVIDER_MONTHLY_REQUESTS => 'Das monatliche AI-Request-Kontingent für diesen Provider ist ausgeschöpft.',
            default => 'Das AI-Kontingent ist ausgeschöpft.',
        };
    }

    private function normalizeProviderId(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9._-]+/', '-', $value) ?? '';
        $value = preg_replace('/-+/', '-', $value) ?? '';

        return trim($value, '-');
    }
}
