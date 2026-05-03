<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('meridian_output_custom_styles')) {
    function meridian_output_custom_styles(): void
    {
        MeridianCMSDefaultTheme::instance()->outputCustomStyles();
    }
}

if (!function_exists('meridian_output_fonts')) {
    function meridian_output_fonts(): void
    {
        MeridianCMSDefaultTheme::instance()->outputPreconnect();
        MeridianCMSDefaultTheme::instance()->outputLocalFonts();
    }
}

if (!function_exists('meridian_reading_time')) {
    function meridian_reading_time(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));

        return max(1, (int)ceil($wordCount / 200));
    }
}

if (!function_exists('meridian_author_initials')) {
    function meridian_author_initials(string $name): string
    {
        $parts = array_filter(explode(' ', trim($name)));
        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
        }

        return strtoupper(mb_substr($name, 0, 2));
    }
}

if (!function_exists('meridian_format_date')) {
    function meridian_format_date(string $dateStr, bool $short = true): string
    {
        if ($dateStr === '') {
            return '';
        }

        $ts = strtotime($dateStr);
        if (!$ts) {
            return htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8');
        }

        $months = ['Jan.','Feb.','März','Apr.','Mai','Juni','Juli','Aug.','Sep.','Okt.','Nov.','Dez.'];
        $day = (int)date('j', $ts);
        $month = $months[(int)date('n', $ts) - 1];
        $year = (int)date('Y', $ts);

        return $day . '. ' . $month . ' ' . $year;
    }
}

if (!function_exists('meridian_excerpt')) {
    function meridian_excerpt(string $content, int $maxChars = 160): string
    {
        $text = meridian_excerpt_plain_text($content);
        if (mb_strlen($text) <= $maxChars) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }

        $cut = mb_substr($text, 0, $maxChars);
        $lastSpace = mb_strrpos($cut, ' ');
        if ($lastSpace !== false) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return htmlspecialchars($cut, ENT_QUOTES, 'UTF-8') . '…';
    }
}

if (!function_exists('meridian_excerpt_plain_text')) {
    function meridian_excerpt_plain_text(string $content): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }

        $decoded = \CMS\Json::decodeArray($content, []);
        if (is_array($decoded) && isset($decoded['blocks']) && is_array($decoded['blocks'])) {
            $html = '';
            if (class_exists('\\CMS\\Services\\EditorJsRenderer')) {
                try {
                    $html = \CMS\Services\EditorJsRenderer::getInstance()->render($decoded);
                } catch (\Throwable $e) {
                    $html = '';
                }
            }

            if ($html !== '') {
                $content = $html;
            } else {
                $parts = [];
                foreach ($decoded['blocks'] as $block) {
                    if (!is_array($block)) {
                        continue;
                    }

                    $data = $block['data'] ?? null;
                    if (!is_array($data)) {
                        continue;
                    }

                    foreach (['text', 'caption', 'message', 'title'] as $key) {
                        if (!empty($data[$key]) && is_string($data[$key])) {
                            $parts[] = $data[$key];
                        }
                    }

                    if (!empty($data['items']) && is_array($data['items'])) {
                        foreach ($data['items'] as $item) {
                            if (is_string($item) && trim($item) !== '') {
                                $parts[] = $item;
                            }
                        }
                    }
                }

                $content = implode(' ', $parts);
            }
        }

        $text = trim(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return preg_replace('/\s+/u', ' ', $text) ?? '';
    }
}

if (!function_exists('meridian_post_tags')) {
    function meridian_post_tags(string $tags): array
    {
        if ($tags === '') {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $tags)));
    }
}

if (!function_exists('meridian_cat_gradient')) {
    function meridian_cat_gradient(string $category = ''): string
    {
        $map = [
            'microsoft' => 'linear-gradient(135deg,#1e2a3e,#1a1a18)',
            'exchange' => 'linear-gradient(135deg,#1e2a2a,#1a1a18)',
            'powershell' => 'linear-gradient(135deg,#1a2a1a,#1a1a18)',
            'security' => 'linear-gradient(135deg,#2a1a1a,#1a1a18)',
            'azure' => 'linear-gradient(135deg,#1a1a2a,#1a1a18)',
            'linux' => 'linear-gradient(135deg,#2a2a1a,#1a1a18)',
            'guides' => 'linear-gradient(135deg,#2a1a2a,#1a1a18)',
            'tutorials' => 'linear-gradient(135deg,#1a2a2e,#1a1a18)',
        ];

        $key = strtolower($category);
        foreach ($map as $needle => $gradient) {
            if (str_contains($key, $needle)) {
                return $gradient;
            }
        }

        return 'linear-gradient(135deg,#2a2a26,#1a1a18)';
    }
}

if (!function_exists('meridian_copyright')) {
    function meridian_copyright(string $template = ''): string
    {
        if ($template === '') {
            $template = meridian_setting('footer', 'copyright_text', '© {year} {site_title}. Alle Rechte vorbehalten.');
        }

        $siteName = defined('SITE_NAME') ? SITE_NAME : '365CMS';
        $text = str_replace(['{year}', '{site_title}'], [date('Y'), $siteName], $template);

        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
