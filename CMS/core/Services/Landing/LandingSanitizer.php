<?php
declare(strict_types=1);

namespace CMS\Services\Landing;

if (!defined('ABSPATH')) {
    exit;
}

final class LandingSanitizer
{
    public function normalizeHtml(mixed $value): string
    {
        $str = (string)($value ?? '');
        if (trim(strip_tags($str)) === '') {
            return '';
        }

        if (function_exists('sanitize_html')) {
            return (string)sanitize_html($str, 'default');
        }

        return $str;
    }

    public function sanitizePlainText(string $value, int $maxLength = 255): string
    {
        $value = trim(strip_tags($value));
        if ($value === '') {
            return '';
        }

        return mb_substr($value, 0, $maxLength);
    }

    public function sanitizeUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('esc_url_raw')) {
            $sanitized = (string)esc_url_raw($value);
            if ($sanitized !== '') {
                return $sanitized;
            }
        }

        if (preg_match('#^/[a-z0-9/_\-\.\?=&%]*$#i', $value) === 1) {
            return $value;
        }

        return '';
    }

    public function sanitizeLandingSlug(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value === '/') {
            return '';
        }

        $value = preg_replace('#[^a-z0-9/_\-]#i', '', $value) ?? '';
        $value = '/' . trim($value, '/');

        return $value === '/' ? '' : $value;
    }

    public function sanitizeColor(string $value, string $fallback): string
    {
        $value = trim($value);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $fallback;
    }

    /**
     * @param array<int, string> $allowed
     */
    public function sanitizeEnum(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    public function sanitizeCopyright(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = strip_tags($value);
        $value = preg_replace('/\{year\}/i', '{year}', $value) ?? $value;
        return mb_substr($value, 0, 255);
    }

    public function sanitizeRelativeAssetPath(string $value): string
    {
        $value = trim(str_replace('\\', '/', $value));
        if ($value === '') {
            return '';
        }

        if (str_contains($value, '..')) {
            return '';
        }

        return ltrim($value, '/');
    }

    /**
     * @param array<int, mixed> $buttons
     * @return array<int, array<string, mixed>>
     */
    public function sanitizeHeaderButtons(array $buttons): array
    {
        $cleanButtons = [];

        foreach (array_slice($buttons, 0, 4) as $button) {
            if (!is_array($button)) {
                continue;
            }

            $text = $this->sanitizePlainText((string)($button['text'] ?? ''), 40);
            $url = $this->sanitizeUrl((string)($button['url'] ?? ''));
            $icon = $this->sanitizePlainText((string)($button['icon'] ?? ''), 16);
            $target = $this->sanitizeEnum((string)($button['target'] ?? '_self'), ['_self', '_blank'], '_self');
            $outline = !empty($button['outline']);

            if ($text === '' && $url === '') {
                continue;
            }

            $cleanButtons[] = [
                'text' => $text,
                'url' => $url,
                'icon' => $icon,
                'target' => $target,
                'outline' => $outline,
            ];
        }

        return $cleanButtons;
    }

    public function sanitizePluginId(string $value): string
    {
        $value = strtolower(trim($value));
        return preg_replace('/[^a-z0-9_\-]/', '', $value) ?? '';
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function sanitizePluginSettingsArray(array $settings): array
    {
        $clean = [];
        foreach ($settings as $key => $value) {
            $cleanKey = $this->sanitizePluginId((string)$key);
            if ($cleanKey === '') {
                continue;
            }

            $clean[$cleanKey] = is_array($value)
                ? $this->sanitizePluginSettingsArray($value)
                : $this->sanitizePlainText((string)$value, 5000);
        }

        return $clean;
    }
}
