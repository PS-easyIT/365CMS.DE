<?php
/**
 * HTML Purifier Service
 *
 * Wrapper um HTMLPurifier für sichere HTML-Sanitierung.
 * Ersetzt die rudimentäre strip_tags()-basierte Lösung in wp_kses_post().
 *
 * Verwendung:
 *   $safe = PurifierService::getInstance()->purify($unsafeHtml);
 *   $safe = PurifierService::getInstance()->purify($html, 'strict');
 *
 * Profile:
 *   - 'default'  → Standard CMS-Whitelist (Posts, Seiten)
 *   - 'strict'   → Nur Text-Formatierung (Kommentare, User-Bio)
 *   - 'minimal'  → Nur Inline-Tags (Nachrichten)
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

if (!defined('ABSPATH')) {
    exit;
}

class PurifierService
{
    private static ?self $instance = null;

    /** @var array<string, \HTMLPurifier> Gecachte Purifier-Instanzen pro Profil */
    private array $purifiers = [];

    /** @var bool Ob HTMLPurifier verfügbar ist */
    private readonly bool $available;

    /** @var string Cache-Verzeichnis für HTMLPurifier-Serializer */
    private readonly string $cacheDir;

    /**
     * Bekannte Sanitierungs-Profile mit erlaubten HTML-Elementen
     *
     * @var array<string, array{elements: string, attributes: string}>
     */
    private const PROFILES = [
        'default' => [
            'elements'   => 'p,a,strong,b,em,i,u,ul,ol,li,br,h1,h2,h3,h4,h5,h6,blockquote,pre,code,img,table,thead,tbody,tfoot,tr,th,td,hr,span,div,figure,figcaption,dl,dt,dd,sub,sup,abbr,mark,del,ins,details,summary,video,source,audio',
            'attributes' => 'a.href,a.title,a.target,a.rel,img.src,img.alt,img.width,img.height,img.loading,td.colspan,td.rowspan,th.colspan,th.rowspan,span.class,div.class,pre.class,code.class,video.src,video.controls,video.width,video.height,source.src,source.type,audio.src,audio.controls,details.open',
        ],
        'strict' => [
            'elements'   => 'p,a,strong,b,em,i,u,br,ul,ol,li,blockquote,code,pre',
            'attributes' => 'a.href,a.title,a.rel',
        ],
        'minimal' => [
            'elements'   => 'strong,b,em,i,u,br,code,a',
            'attributes' => 'a.href',
        ],
    ];

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->available = class_exists('HTMLPurifier', true);
        $this->cacheDir  = $this->resolveCacheDirectory();
    }

    /**
     * HTML sanitieren.
     *
     * @param string $dirty   Unsicherer HTML-String
     * @param string $profile Sanitierungs-Profil ('default', 'strict', 'minimal')
     * @return string          Sauberer HTML-String
     */
    public function purify(string $dirty, string $profile = 'default'): string
    {
        if ($dirty === '') {
            return '';
        }

        // Fallback wenn HTMLPurifier nicht geladen ist
        if (!$this->available) {
            return $this->fallbackSanitize($dirty, $profile);
        }

        $purifier = $this->getPurifier($profile);
        return $purifier->purify($dirty);
    }

    /**
     * Mehrere Strings auf einmal sanitieren.
     *
     * @param array<string> $dirtyArray
     * @param string        $profile
     * @return array<string>
     */
    public function purifyArray(array $dirtyArray, string $profile = 'default'): array
    {
        if (!$this->available) {
            return array_map(fn(string $s) => $this->fallbackSanitize($s, $profile), $dirtyArray);
        }

        $purifier = $this->getPurifier($profile);
        return $purifier->purifyArray($dirtyArray);
    }

    /**
     * Prüft ob HTMLPurifier verfügbar ist.
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * HTMLPurifier-Instanz für ein Profil erzeugen / aus Cache laden.
     */
    private function getPurifier(string $profile): \HTMLPurifier
    {
        if (isset($this->purifiers[$profile])) {
            return $this->purifiers[$profile];
        }

        $profileConfig = self::PROFILES[$profile] ?? self::PROFILES['default'];

        $config = \HTMLPurifier_Config::createDefault();

        // Cache-Verzeichnis für Serializer
        if ($this->cacheDir !== '') {
            $config->set('Cache.SerializerPath', $this->cacheDir);
        }

        // Encoding
        $config->set('Core.Encoding', 'UTF-8');

        // Erlaubte HTML-Elemente und -Attribute
        $config->set('HTML.Allowed', $this->buildAllowedString($profileConfig));

        // Links: noopener/noreferrer für externe Links erzwingen
        $config->set('HTML.Nofollow', true);
        $config->set('HTML.TargetBlank', true);

        // URI-Schema einschränken
        $config->set('URI.AllowedSchemes', [
            'http'  => true,
            'https' => true,
            'mailto' => true,
            'tel'   => true,
        ]);

        // AutoFormat
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.AutoParagraph', false);

        $this->purifiers[$profile] = new \HTMLPurifier($config);
        return $this->purifiers[$profile];
    }

    /**
     * Baut den HTML.Allowed-String aus dem Profil.
     */
    private function buildAllowedString(array $profileConfig): string
    {
        $elements = $profileConfig['elements'] ?? '';
        $attributes = $profileConfig['attributes'] ?? '';

        // HTMLPurifier erwartet: "p,a[href|title],strong,em,img[src|alt|width|height]"
        // Wir bauen das aus unserer Kurzform
        $elementList = array_map('trim', explode(',', $elements));
        $attrMap = [];

        if (!empty($attributes)) {
            foreach (explode(',', $attributes) as $pair) {
                $pair = trim($pair);
                if (str_contains($pair, '.')) {
                    [$tag, $attr] = explode('.', $pair, 2);
                    $attrMap[$tag][] = $attr;
                }
            }
        }

        $parts = [];
        foreach ($elementList as $el) {
            if (isset($attrMap[$el])) {
                $parts[] = $el . '[' . implode('|', $attrMap[$el]) . ']';
            } else {
                $parts[] = $el;
            }
        }

        return implode(',', $parts);
    }

    /**
     * Fallback-Sanitierung wenn HTMLPurifier nicht verfügbar ist.
     * Verwendet strip_tags() mit einer Whitelist — weniger sicher als HTMLPurifier.
     */
    private function fallbackSanitize(string $dirty, string $profile): string
    {
        $profileConfig = self::PROFILES[$profile] ?? self::PROFILES['default'];
        $elements = array_map('trim', explode(',', $profileConfig['elements'] ?? ''));

        // strip_tags Whitelist aufbauen
        $allowedTags = implode('', array_map(fn(string $el): string => "<{$el}>", $elements));

        return strip_tags($dirty, $allowedTags);
    }

    private function resolveCacheDirectory(): string
    {
        $candidates = [];

        if (defined('ABSPATH')) {
            $candidates[] = rtrim(ABSPATH, '\\/') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'htmlpurifier';
        }

        $candidates[] = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . '365cms-htmlpurifier';

        foreach ($candidates as $candidate) {
            if ($this->ensureDirectory($candidate)) {
                return $candidate;
            }
        }

        error_log('PurifierService: Kein beschreibbares Cache-Verzeichnis für HTMLPurifier verfügbar.');
        return '';
    }

    private function ensureDirectory(string $dir): bool
    {
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        return is_writable($dir);
    }
}
