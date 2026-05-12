<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;
use CMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class AntispamService
{
    private const SETTING_KEYS = [
        'antispam_enabled',
        'antispam_honeypot',
        'antispam_min_time',
        'antispam_max_links',
        'antispam_block_empty_ua',
    ];

    private static ?self $instance = null;

    private Database $db;
    private string $prefix;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * @param array<string,mixed> $context
     * @return array{rejected:bool,reason:string,message:string}
     */
    public function evaluate(array $context): array
    {
        $settings = $this->loadSettings();
        if (($settings['antispam_enabled'] ?? '0') !== '1') {
            return $this->allow();
        }

        $honeypotValue = trim((string) ($context['honeypot_value'] ?? ''));
        if (($settings['antispam_honeypot'] ?? '0') === '1' && $honeypotValue !== '') {
            return $this->rejectAndLog('honeypot', 'Spam erkannt.', $context);
        }

        $minimumSeconds = max(0, min(60, (int) ($settings['antispam_min_time'] ?? 0)));
        $startedAt = (int) ($context['started_at'] ?? 0);
        if ($minimumSeconds > 0 && $startedAt > 0 && time() - $startedAt < $minimumSeconds) {
            return $this->rejectAndLog('minimum_time', 'Bitte warten Sie einen Moment und senden Sie das Formular erneut.', $context);
        }

        $userAgent = trim((string) ($context['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '')));
        if (($settings['antispam_block_empty_ua'] ?? '0') === '1' && $userAgent === '') {
            return $this->rejectAndLog('empty_user_agent', 'Die Anfrage wurde aus Sicherheitsgründen blockiert.', $context);
        }

        $content = trim((string) ($context['content'] ?? ''));
        $maxLinks = max(0, min(50, (int) ($settings['antispam_max_links'] ?? 0)));
        if ($maxLinks > 0 && $this->countLinks($content) > $maxLinks) {
            return $this->rejectAndLog('max_links', 'Zu viele Links in der Anfrage.', $context);
        }

        $email = trim((string) ($context['email'] ?? ''));
        $authorName = $this->sanitizeText((string) ($context['author_name'] ?? ''));
        $ipAddress = $this->normalizeIpAddress((string) ($context['ip_address'] ?? ''));

        if ($this->matchesSpamBlacklist($email, $ipAddress, $authorName, $content)) {
            return $this->rejectAndLog('blacklist', 'Die Anfrage wurde aus Sicherheitsgründen blockiert.', $context);
        }

        return $this->allow();
    }

    /** @return array<string,string> */
    public function getSettings(): array
    {
        return $this->loadSettings();
    }

    /** @return array<string,string> */
    private function loadSettings(): array
    {
        $placeholders = implode(',', array_fill(0, count(self::SETTING_KEYS), '?'));
        $rows = $this->db->get_results(
            "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name IN ({$placeholders})",
            self::SETTING_KEYS
        ) ?: [];

        $settings = [
            'antispam_enabled' => '0',
            'antispam_honeypot' => '1',
            'antispam_min_time' => '3',
            'antispam_max_links' => '3',
            'antispam_block_empty_ua' => '1',
        ];

        foreach ($rows as $row) {
            $name = (string) ($row->option_name ?? '');
            if (array_key_exists($name, $settings)) {
                $settings[$name] = (string) ($row->option_value ?? '');
            }
        }

        return $settings;
    }

    private function countLinks(string $content): int
    {
        $count = preg_match_all('/https?:\/\//i', $content, $matches);
        if ($count === false) {
            return 0;
        }

        return $count;
    }

    private function matchesSpamBlacklist(string $email, string $ipAddress, string $authorName, string $content): bool
    {
        try {
            $rows = $this->db->get_results(
                "SELECT type, value FROM {$this->prefix}spam_blacklist ORDER BY id DESC LIMIT 1000"
            ) ?: [];
        } catch (\Throwable) {
            return false;
        }

        if ($rows === []) {
            return false;
        }

        $haystack = $this->lowerUtf8($authorName . "\n" . strip_tags($content));
        $email = strtolower(trim($email));
        $emailDomain = str_contains($email, '@') ? substr((string) strrchr($email, '@'), 1) : '';

        foreach ($rows as $row) {
            $type = (string) ($row->type ?? '');
            $value = trim((string) ($row->value ?? ''));
            if ($value === '') {
                continue;
            }

            if ($type === 'ip' && $ipAddress !== '' && hash_equals($value, $ipAddress)) {
                return true;
            }

            if ($type === 'email' && $email !== '' && hash_equals(strtolower($value), $email)) {
                return true;
            }

            if ($type === 'domain' && $emailDomain !== '' && hash_equals(strtolower($value), strtolower($emailDomain))) {
                return true;
            }

            if ($type === 'word' && $this->containsUtf8($haystack, $this->lowerUtf8($value))) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeText(string $value): string
    {
        $value = trim(strip_tags($value));
        return preg_replace('/\s+/u', ' ', $value) ?? '';
    }

    private function normalizeIpAddress(string $authorIp): string
    {
        $authorIp = trim($authorIp);
        if ($authorIp === '') {
            return '';
        }

        return filter_var($authorIp, FILTER_VALIDATE_IP) ? $authorIp : '';
    }

    private function lowerUtf8(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private function containsUtf8(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return false;
        }

        return function_exists('mb_stripos')
            ? mb_stripos($haystack, $needle, 0, 'UTF-8') !== false
            : stripos($haystack, $needle) !== false;
    }

    /** @return array{rejected:false,reason:string,message:string} */
    private function allow(): array
    {
        return ['rejected' => false, 'reason' => '', 'message' => ''];
    }

    /** @return array{rejected:true,reason:string,message:string} */
    private function reject(string $reason, string $message): array
    {
        return ['rejected' => true, 'reason' => $reason, 'message' => $message];
    }

    /**
     * @param array<string, mixed> $context
     * @return array{rejected:true,reason:string,message:string}
     */
    private function rejectAndLog(string $reason, string $message, array $context): array
    {
        $this->logRejection($reason, $context);

        return $this->reject($reason, $message);
    }

    /** @param array<string, mixed> $context */
    private function logRejection(string $reason, array $context): void
    {
        $ipAddress = $this->normalizeIpAddress((string) ($context['ip_address'] ?? ''));
        $requestUri = $this->sanitizeRequestPath((string) ($_SERVER['REQUEST_URI'] ?? '/'));
        $userAgent = $this->sanitizeLogText((string) ($context['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '')), 500);
        $source = $this->sanitizeLogText((string) ($context['source'] ?? 'runtime'), 40);

        try {
            $this->db->insert('security_log', [
                'action' => 'antispam_rejected',
                'ip_address' => $ipAddress !== '' ? $ipAddress : null,
                'request_uri' => $requestUri,
                'user_agent' => $userAgent,
                'rule_matched' => 'antispam:' . $this->sanitizeLogText($reason, 40),
                'user_id' => isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
                'extra' => json_encode([
                    'reason' => $this->sanitizeLogText($reason, 40),
                    'source' => $source !== '' ? $source : 'runtime',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('security.antispam')->warning('AntiSpam-Rejection konnte nicht protokolliert werden.', [
                'reason' => $reason,
                'exception' => $e::class,
            ]);
        }
    }

    private function sanitizeRequestPath(string $requestUri): string
    {
        $path = (string) parse_url($requestUri, PHP_URL_PATH);
        if ($path === '') {
            $path = '/';
        }

        return $this->sanitizeLogText($path, 255);
    }

    private function sanitizeLogText(string $value, int $maxLength): string
    {
        $value = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '');

        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength, 'UTF-8') : substr($value, 0, $maxLength);
    }
}
