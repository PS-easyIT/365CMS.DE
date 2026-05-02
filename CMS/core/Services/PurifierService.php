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
 *   - 'table'    → Tabellen-/Beschreibungstexte mit Struktur-Tags
 *   - 'table_cell' → Nur Inline-Formatierung und Links für Tabellenzellen
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
            'attributes' => 'a.href,a.title,a.target,a.rel,img.src,img.alt,img.width,img.height,img.loading,img.fetchpriority,img.decoding,td.colspan,td.rowspan,th.colspan,th.rowspan,span.class,div.class,pre.class,code.class,h1.id,h2.id,h3.id,h4.id,h5.id,h6.id,video.src,video.controls,video.width,video.height,source.src,source.type,audio.src,audio.controls,details.open',
        ],
        'hub' => [
            'elements'   => 'section,article,nav,aside,header,footer,main,p,a,strong,b,em,i,u,ul,ol,li,br,h1,h2,h3,h4,h5,h6,blockquote,pre,code,img,table,thead,tbody,tfoot,tr,th,td,hr,span,div,figure,figcaption,dl,dt,dd,sub,sup,abbr,mark,del,ins,details,summary,video,source,audio,small',
            'attributes' => 'a.href,a.title,a.target,a.rel,a.class,a.aria-label,a.aria-current,img.src,img.alt,img.width,img.height,img.loading,img.fetchpriority,img.decoding,img.class,td.colspan,td.rowspan,td.class,th.colspan,th.rowspan,th.class,span.class,div.class,div.id,p.class,section.class,section.id,article.class,article.id,nav.class,nav.id,nav.aria-label,aside.class,aside.id,header.class,header.id,footer.class,footer.id,main.class,main.id,figure.class,figcaption.class,ul.class,ol.class,li.class,pre.class,code.class,h1.id,h1.class,h2.id,h2.class,h3.id,h3.class,h4.id,h4.class,h5.id,h5.class,h6.id,h6.class,details.open,details.class,summary.class,video.src,video.controls,video.width,video.height,source.src,source.type,audio.src,audio.controls,table.class,thead.class,tbody.class,tfoot.class,tr.class,caption.class',
        ],
        'table' => [
            'elements'   => 'p,a,strong,b,em,i,u,ul,ol,li,br,blockquote,pre,code,img,span,div,small,mark,sub,sup,table,thead,tbody,tfoot,tr,th,td,caption',
            'attributes' => 'a.href,a.title,a.target,a.rel,a.class,img.src,img.alt,img.width,img.height,img.loading,img.class,td.colspan,td.rowspan,td.class,th.colspan,th.rowspan,th.class,span.class,div.class,p.class,table.class,caption.class,pre.class,code.class',
        ],
        'table_cell' => [
            'elements'   => 'a,strong,b,em,i,u',
            'attributes' => 'a.href,a.title,a.target,a.rel',
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
            return $this->hardenAnchorLinks($this->fallbackSanitize($dirty, $profile));
        }

        $purifier = $this->getPurifier($profile);
        return $this->hardenAnchorLinks($purifier->purify($dirty));
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
            return array_map(fn(string $s) => $this->hardenAnchorLinks($this->fallbackSanitize($s, $profile)), $dirtyArray);
        }

        $purifier = $this->getPurifier($profile);
        return array_map(fn(string $s) => $this->hardenAnchorLinks($s), $purifier->purifyArray($dirtyArray));
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

        $stripped = strip_tags($dirty, $allowedTags);

        return $this->sanitizeFallbackAttributes($stripped, $profileConfig);
    }

    /**
     * Bereinigt Attribute im Fallback-Pfad. strip_tags() entfernt zwar Tags,
     * lässt aber gefährliche Attribute wie onclick oder javascript:-URLs stehen.
     *
     * @param array{elements:string,attributes:string} $profileConfig
     */
    private function sanitizeFallbackAttributes(string $html, array $profileConfig): string
    {
        if ($html === '' || !str_contains($html, '<')) {
            return $html;
        }

        if (!class_exists('DOMDocument')) {
            return (string) preg_replace('/<([a-z][a-z0-9]*)(?:\s+[^>]*)>/i', '<$1>', $html);
        }

        $allowedAttributes = $this->buildAllowedAttributeMap($profileConfig);

        return $this->transformHtmlFragment($html, function (\DOMDocument $document) use ($allowedAttributes): void {
            foreach ($document->getElementsByTagName('*') as $element) {
                if (!$element instanceof \DOMElement || $element->getAttribute('data-cms-fragment-root') === '1') {
                    continue;
                }

                $tag = strtolower($element->tagName);
                $attributes = [];
                foreach ($element->attributes ?? [] as $attribute) {
                    $attributes[] = strtolower($attribute->name);
                }

                foreach ($attributes as $attributeName) {
                    $allowed = in_array($attributeName, $allowedAttributes[$tag] ?? [], true);
                    if (!$allowed || str_starts_with($attributeName, 'on')) {
                        $element->removeAttribute($attributeName);
                        continue;
                    }

                    if (in_array($attributeName, ['href', 'src'], true) && !$this->isSafeUri($element->getAttribute($attributeName))) {
                        $element->removeAttribute($attributeName);
                    }
                }
            }
        });
    }

    /**
     * Erzwingt sichere Link-Ziele und schützt target="_blank" gegen Tabnabbing.
     */
    private function hardenAnchorLinks(string $html): string
    {
        if ($html === '' || stripos($html, '<a') === false || !class_exists('DOMDocument')) {
            return $html;
        }

        return $this->transformHtmlFragment($html, function (\DOMDocument $document): void {
            foreach ($document->getElementsByTagName('a') as $link) {
                if (!$link instanceof \DOMElement) {
                    continue;
                }

                if ($link->hasAttribute('href') && !$this->isSafeUri($link->getAttribute('href'))) {
                    $link->removeAttribute('href');
                }

                if ($link->hasAttribute('target')) {
                    $target = strtolower(trim($link->getAttribute('target')));
                    if (!in_array($target, ['_blank', '_self', '_parent', '_top'], true)) {
                        $link->removeAttribute('target');
                    } elseif ($target === '_blank') {
                        $this->ensureRelTokens($link, ['noopener', 'noreferrer']);
                    }
                }
            }
        });
    }

    /**
     * @param array{elements:string,attributes:string} $profileConfig
     * @return array<string,list<string>>
     */
    private function buildAllowedAttributeMap(array $profileConfig): array
    {
        $map = [];
        foreach (explode(',', $profileConfig['attributes'] ?? '') as $pair) {
            $pair = trim($pair);
            if (!str_contains($pair, '.')) {
                continue;
            }

            [$tag, $attribute] = explode('.', $pair, 2);
            $tag = strtolower(trim($tag));
            $attribute = strtolower(trim($attribute));
            if ($tag === '' || $attribute === '') {
                continue;
            }

            $map[$tag][] = $attribute;
        }

        return array_map(static fn(array $attributes): array => array_values(array_unique($attributes)), $map);
    }

    private function isSafeUri(string $uri): bool
    {
        $uri = trim(html_entity_decode($uri, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($uri === '') {
            return false;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $uri) === 1) {
            return false;
        }

        if (str_starts_with($uri, '#') || str_starts_with($uri, '/') || str_starts_with($uri, './') || str_starts_with($uri, '../')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($uri, PHP_URL_SCHEME));
        if ($scheme === '') {
            return !str_starts_with($uri, '//');
        }

        return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true);
    }

    /**
     * @param list<string> $requiredTokens
     */
    private function ensureRelTokens(\DOMElement $link, array $requiredTokens): void
    {
        $tokens = preg_split('/\s+/', strtolower(trim($link->getAttribute('rel')))) ?: [];
        $tokens = array_values(array_filter($tokens, static fn(string $token): bool => $token !== ''));

        foreach ($requiredTokens as $token) {
            if (!in_array($token, $tokens, true)) {
                $tokens[] = $token;
            }
        }

        $link->setAttribute('rel', implode(' ', array_unique($tokens)));
    }

    private function transformHtmlFragment(string $html, callable $callback): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $wrapped = '<?xml encoding="UTF-8"><div data-cms-fragment-root="1">' . $html . '</div>';
        $options = defined('LIBXML_HTML_NOIMPLIED') && defined('LIBXML_HTML_NODEFDTD')
            ? LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            : 0;

        if (!$document->loadHTML($wrapped, $options)) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return '';
        }

        $callback($document);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = null;
        foreach ($document->getElementsByTagName('div') as $candidate) {
            if ($candidate instanceof \DOMElement && $candidate->getAttribute('data-cms-fragment-root') === '1') {
                $root = $candidate;
                break;
            }
        }

        if (!$root instanceof \DOMElement) {
            return '';
        }

        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child) ?: '';
        }

        return $result;
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

        \CMS\Logger::instance()->withChannel('purifier')->warning('No writable cache directory is available for HTMLPurifier.', [
            'candidates' => $candidates,
        ]);
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
