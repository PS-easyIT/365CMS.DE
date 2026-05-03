<?php
/**
 * Table of Contents Engine
 *
 * Parst HTML-Content, fügt Anker-IDs ein und erzeugt ein TOC-Widget.
 * Liest Einstellungen aus der DB-Tabelle {prefix}settings (option_name = 'toc_settings').
 *
 * Verwendung im Theme-Template:
 *   $toc = \CMS\TableOfContents::instance();
 *   $result = $toc->process($content, 'post');   // oder 'page'
 *   // $result['toc']     = TOC-HTML (leer wenn Auto-Insert nicht aktiv oder [cms_toc] inline ersetzt)
 *   // $result['content'] = Content mit eingefügten id="…"-Ankern
 *
 * Shortcode: [cms_toc] wird direkt im Content ersetzt.
 *
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

class TableOfContents
{
    private static ?self $instance = null;

    private array $settings = [];

    private const DEFAULTS = [
        'support_types'        => ['post', 'page'],
        'auto_insert_types'    => ['post'],
        'position'             => 'before',
        'show_limit'           => 4,
        'show_header_label'    => true,
        'header_label'         => 'Inhaltsverzeichnis',
        'allow_toggle'         => true,
        'show_hierarchy'       => true,
        'show_counter'         => true,
        'smooth_scroll'        => true,
        'smooth_scroll_offset' => 30,
        'mobile_scroll_offset' => 0,
        'width'                => 'auto',
        'alignment'            => 'none',
        'theme'                => 'grey',
        'custom_bg_color'      => '#f9f9f9',
        'custom_border_color'  => '#aaaaaa',
        'custom_title_color'   => '#333333',
        'custom_link_color'    => '#0073aa',
        'headings'             => ['h2', 'h3', 'h4'],
        'exclude_headings'     => '',
        'limit_path'           => '',
        'lowercase'            => true,
        'hyphenate'            => true,
        'homepage_toc'         => false,
        'exclude_css'          => false,
        'anchor_prefix'        => '',
        'remove_toc_links'     => false,
        'sticky_toggle'        => false,
    ];

    private const ALLOWED_TYPES = ['post', 'page'];
    private const ALLOWED_POSITIONS = ['before', 'after', 'top', 'bottom'];
    private const ALLOWED_THEMES = ['grey', 'light', 'dark', 'transparent', 'custom', 'light-blue', 'white', 'black'];
    private const ALLOWED_HEADINGS = ['h2', 'h3', 'h4', 'h5', 'h6'];

    // ─── Singleton ────────────────────────────────────────────────────────────

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->loadSettings();
    }

    // ─── Settings ─────────────────────────────────────────────────────────────

    private function loadSettings(): void
    {
        try {
            $db  = Database::instance();
            $row = $db->fetchOne(
                "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'toc_settings'"
            );
            if ($row && !empty($row['option_value'])) {
                $saved = Json::decodeArray($row['option_value'] ?? null, []);
                if (is_array($saved)) {
                    $this->settings = $this->normalizeSettings(array_merge(self::DEFAULTS, $saved));
                    return;
                }
            }
        } catch (\Throwable) {
            // Fallback auf Defaults
        }
        $this->settings = $this->normalizeSettings(self::DEFAULTS);
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Verarbeitet Content: fügt Anker-IDs in Überschriften ein und erzeugt TOC-HTML.
     *
     * Rückgabe: ['toc' => string, 'content' => string]
     * - 'toc':     TOC-HTML für Auto-Insert (leer wenn Shortcode genutzt oder deaktiviert)
     * - 'content': Content mit id="…" in Überschriften; [cms_toc]-Shortcode ggf. ersetzt
     *
     * @param string $content   HTML-Content aus der DB
     * @param string $type      'post' | 'page' | sonstige
     * @param int    $id        Content-ID (derzeit nicht genutzt, für zukünftige Erweiterungen)
     */
    public function process(string $content, string $type = 'post', int $id = 0): array
    {
        if (!$this->matchesCurrentPathLimit()) {
            return ['toc' => '', 'content' => str_replace('[cms_toc]', '', $content)];
        }

        if (!(bool) ($this->settings['homepage_toc'] ?? false) && $this->isHomepageRequest()) {
            return ['toc' => '', 'content' => str_replace('[cms_toc]', '', $content)];
        }

        // Keine Unterstützung für diesen Typ?
        $supportTypes = $this->normalizeTypes((array)($this->settings['support_types'] ?? ['post', 'page']));
        if (!in_array($type, $supportTypes, true)) {
            return ['toc' => '', 'content' => str_replace('[cms_toc]', '', $content)];
        }

        $hasShortcode = str_contains($content, '[cms_toc]');
        $autoTypes    = $this->normalizeTypes((array)($this->settings['auto_insert_types'] ?? ['post']));
        $autoInsert   = !$hasShortcode && in_array($type, $autoTypes, true);

        // Weder Shortcode noch Auto-Insert → unverändert zurückgeben
        if (!$hasShortcode && !$autoInsert) {
            return ['toc' => '', 'content' => $content];
        }

        // Überschriften extrahieren
        $headings = $this->extractHeadings($content);
        $limit    = max(1, (int)($this->settings['show_limit'] ?? 4));

        // Zu wenig Überschriften → TOC unterdrücken
        if (count($headings) < $limit) {
            return ['toc' => '', 'content' => str_replace('[cms_toc]', '', $content)];
        }

        // Anker-IDs in Content einfügen
        $content = $this->addAnchors($content, $headings);

        // TOC-HTML aufbauen
        $tocHtml = $this->buildTocHtml($headings);

        if ($hasShortcode) {
            // Shortcode inline ersetzen – TOC erscheint an exakter Position im Text
            $content = str_replace('[cms_toc]', $tocHtml, $content);
            return ['toc' => '', 'content' => $content];
        }

        // Auto-Insert: TOC separat zurückgeben, Template positioniert ihn
        return ['toc' => $tocHtml, 'content' => $content];
    }

    /**
     * Shortcut: gibt nur das TOC-HTML für einen gegebenen Content zurück.
     * Ideal für Sidebar-Widgets.
     */
    public function renderFromContent(string $content): string
    {
        if (!$this->matchesCurrentPathLimit()) {
            return '';
        }

        $headings = $this->extractHeadings($content);
        $limit    = max(1, (int)($this->settings['show_limit'] ?? 4));
        if (count($headings) < $limit) {
            return '';
        }
        return $this->buildTocHtml($headings);
    }

    private function matchesCurrentPathLimit(): bool
    {
        $limitPath = trim((string) ($this->settings['limit_path'] ?? ''));
        if ($limitPath === '') {
            return true;
        }

        $limitPath = '/' . ltrim($limitPath, '/');
        $limitPath = rtrim((string) preg_replace('#/+#', '/', $limitPath), '/');
        $limitPath = $limitPath === '' ? '/' : $limitPath;

        $currentPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $currentPath = '/' . ltrim($currentPath, '/');
        $currentPath = rtrim((string) preg_replace('#/+#', '/', $currentPath), '/');
        $currentPath = $currentPath === '' ? '/' : $currentPath;

        return $currentPath === $limitPath || str_starts_with($currentPath, $limitPath . '/');
    }

    private function isHomepageRequest(): bool
    {
        $currentPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $currentPath = '/' . ltrim($currentPath, '/');
        $currentPath = rtrim((string) preg_replace('#/+#', '/', $currentPath), '/');
        $currentPath = $currentPath === '' ? '/' : $currentPath;

        if ($currentPath === '/') {
            return true;
        }

        if (!class_exists('CMS\Services\ContentLocalizationService')) {
            return false;
        }

        try {
            foreach (Services\ContentLocalizationService::getInstance()->getContentLocales() as $locale) {
                $locale = strtolower(trim((string) $locale));
                if ($locale === '' || $locale === 'de') {
                    continue;
                }

                if ($currentPath === '/' . $locale) {
                    return true;
                }
            }
        } catch (\Throwable) {
        }

        return false;
    }

    // ─── Heading Extraction ───────────────────────────────────────────────────

    private function extractHeadings(string $html): array
    {
        $levels  = $this->normalizeHeadingLevels((array)($this->settings['headings'] ?? ['h2', 'h3', 'h4']));
        $usedAnchors = [];
        $counter     = 0;
        $headings    = [];

        if ($levels === []) {
            return [];
        }

        $levelPattern = implode('|', array_map(fn($l) => preg_quote($l, '/'), $levels));
        $pattern      = '/<(' . $levelPattern . ')([^>]*)>(.*?)<\/(?:' . $levelPattern . ')>/si';

        if (!preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            return [];
        }

        // Ausschluss-Liste aufbauen
        $excludes = $this->parseExcludedHeadings((string)($this->settings['exclude_headings'] ?? ''));
        $prefix   = (string)($this->settings['anchor_prefix'] ?? '');

        foreach ($matches as $match) {
            $tag      = strtolower($match[1]);
            $attrs    = $match[2];
            $rawText  = $match[3];
            $text     = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($rawText), ENT_QUOTES, 'UTF-8')));

            if ($text === '') {
                continue;
            }

            // Ausschluss per Textmatch
            $skip = false;
            foreach ($excludes as $ex) {
                if ($ex !== '' && stripos($text, $ex) !== false) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }

            $anchor    = $this->makeAnchor($text, $usedAnchors, $counter, $prefix);
            $headings[] = [
                'tag'      => $tag,
                'level'    => (int)substr($tag, 1),
                'text'     => $text,
                'anchor'   => $anchor,
                'attrs'    => $attrs,
                'original' => $match[0],
                'children' => [],
            ];
            $counter++;
        }

        return $headings;
    }

    // ─── Anchor Generation ────────────────────────────────────────────────────

    private function makeAnchor(string $text, array &$used, int $counter, string $prefix = ''): string
    {
        static $map = [
            'ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss',
            'Ä'=>'Ae','Ö'=>'Oe','Ü'=>'Ue',
            'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','å'=>'a',
            'ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
            'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
            'ñ'=>'n','ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o',
            'ù'=>'u','ú'=>'u','û'=>'u','ý'=>'y',
        ];

        $anchor = (bool) ($this->settings['lowercase'] ?? true) ? $this->lowerUtf8($text) : $text;
        $anchor = str_replace(array_keys($map), array_values($map), $anchor);
        $separator = (bool) ($this->settings['hyphenate'] ?? true) ? '-' : '_';
        $anchor = (string)preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $anchor);
        $anchor = (string)preg_replace('/[\s\-_]+/u', $separator, $anchor);
        $anchor = trim($anchor, '-_');

        if ($prefix !== '') {
            $anchor = rtrim($prefix, '-_') . $separator . $anchor;
        }

        if ($anchor === '' || strlen($anchor) < 2) {
            $anchor = 'heading-' . $counter;
        } elseif (preg_match('/^\d/', $anchor)) {
            $anchor = 'h-' . $anchor;
        }

        if (strlen($anchor) > 60) {
            $anchor = rtrim(substr($anchor, 0, 57), '-_');
        }

        // Eindeutigkeit
        $base   = $anchor;
        $usedKeys = array_map([$this, 'lowerUtf8'], $used);
        $suffix = 1;
        while (in_array($this->lowerUtf8($anchor), $usedKeys, true)) {
            $anchor = $base . $separator . $suffix++;
        }
        $used[] = $anchor;

        return $anchor;
    }

    // ─── Add Anchors to Content ───────────────────────────────────────────────

    private function addAnchors(string $html, array $headings): string
    {
        foreach ($headings as $h) {
            // Überspringen wenn id= bereits vorhanden
            if (preg_match('/\bid\s*=/i', $h['attrs'])) {
                continue;
            }

            $anchor   = htmlspecialchars($h['anchor'], ENT_QUOTES, 'UTF-8');
            $original = $h['original'];

            // id="" als erstes Attribut direkt nach dem Tag-Namen einfügen
            $replaced = (string)preg_replace(
                '/<(' . preg_quote($h['tag'], '/') . ')([ >])/i',
                '<$1 id="' . $anchor . '"$2',
                $original,
                1
            );

            if ($replaced !== $original) {
                $pos = strpos($html, $original);
                if ($pos !== false) {
                    $html = substr_replace($html, $replaced, $pos, strlen($original));
                }
            }
        }
        return $html;
    }

    // ─── Build TOC HTML ───────────────────────────────────────────────────────

    private function buildTocHtml(array $headings): string
    {
        if (empty($headings)) {
            return '';
        }

        $showHeader   = (bool)($this->settings['show_header_label'] ?? true);
        $label        = htmlspecialchars((string)($this->settings['header_label'] ?? 'Inhaltsverzeichnis'), ENT_QUOTES, 'UTF-8');
        $allowToggle  = (bool)($this->settings['allow_toggle']      ?? true);
        $showHier     = (bool)($this->settings['show_hierarchy']     ?? true);
        $showCounter  = (bool)($this->settings['show_counter']       ?? true);
        $smoothScroll = (bool)($this->settings['smooth_scroll']      ?? true);
        $desktopOffset = (int) ($this->settings['smooth_scroll_offset'] ?? 30);
        $mobileOffset  = (int) ($this->settings['mobile_scroll_offset'] ?? $desktopOffset);
        $removeLinks  = (bool)($this->settings['remove_toc_links']   ?? false);
        $stickyToggle = (bool)($this->settings['sticky_toggle']      ?? false);
        $useInternalCss = !(bool) ($this->settings['exclude_css'] ?? false);
        $themeVariant = $this->resolveThemeVariant((string)($this->settings['theme'] ?? 'grey'));
        $theme        = htmlspecialchars($themeVariant, ENT_QUOTES, 'UTF-8');
        $alignment    = htmlspecialchars((string)($this->settings['alignment']  ?? 'none'),  ENT_QUOTES, 'UTF-8');
        $widthSetting = (string)($this->settings['width'] ?? 'auto');

        $uid     = 'toc-' . $this->generateUidSuffix();
        $classes = $useInternalCss ? ['cms-toc', 'cms-toc--' . $theme] : ['cms-toc-unstyled'];
        if ($useInternalCss && $alignment !== 'none') {
            $classes[] = 'cms-toc--align-' . $alignment;
        }
        if ($useInternalCss && $widthSetting === '100%') {
            $classes[] = 'cms-toc--w-full';
        }
        if ($useInternalCss && $stickyToggle) {
            $classes[] = 'cms-toc--sticky';
        }

        $inlineStyles = [];
        if ($useInternalCss && $themeVariant === 'custom') {
            $inlineStyles[] = '--cms-toc-bg:' . htmlspecialchars((string)($this->settings['custom_bg_color'] ?? '#f9f9f9'), ENT_QUOTES, 'UTF-8');
            $inlineStyles[] = '--cms-toc-border:' . htmlspecialchars((string)($this->settings['custom_border_color'] ?? '#aaaaaa'), ENT_QUOTES, 'UTF-8');
            $inlineStyles[] = '--cms-toc-title:' . htmlspecialchars((string)($this->settings['custom_title_color'] ?? '#333333'), ENT_QUOTES, 'UTF-8');
            $inlineStyles[] = '--cms-toc-link:' . htmlspecialchars((string)($this->settings['custom_link_color'] ?? '#0073aa'), ENT_QUOTES, 'UTF-8');
        }
        $styleAttr = $inlineStyles !== [] ? ' style="' . implode(';', $inlineStyles) . '"' : '';

        $html  = '<nav id="' . $uid . '" class="' . implode(' ', $classes) . '" data-cms-toc-root="1" aria-label="Inhaltsverzeichnis"' . $styleAttr . '>';

        $headerClass = $useInternalCss ? ' class="cms-toc__header"' : '';
        $titleClass = $useInternalCss ? ' class="cms-toc__title"' : '';
        $toggleClass = $useInternalCss ? ' class="cms-toc__toggle"' : '';
        $bodyClass = $useInternalCss ? ' class="cms-toc__body"' : '';

        if ($showHeader) {
            $html .= '<div' . $headerClass . '>';
            $html .= '<span' . $titleClass . '>' . $label . '</span>';
            if ($allowToggle) {
                $html .= '<button type="button"' . $toggleClass . ' data-cms-toc-toggle="1"'
                    . ' aria-expanded="true" aria-controls="' . $uid . '-body">';
                $html .= '<span' . ($useInternalCss ? ' class="cms-toc__toggle-icon"' : '') . ' data-cms-toc-toggle-icon="1" aria-hidden="true">−</span>';
                $html .= '<span' . ($useInternalCss ? ' class="cms-toc__toggle-label"' : '') . ' data-cms-toc-toggle-label="1">Ausblenden</span>';
                $html .= '</button>';
            }
            $html .= '</div>';
        }

        $html .= '<div id="' . $uid . '-body"' . $bodyClass . ' data-cms-toc-body="1">';

        if ($showHier) {
            $structured = $this->buildHierarchy($headings);
            $html      .= $this->renderList($structured, 0, $showCounter, $smoothScroll, $removeLinks, $useInternalCss);
        } else {
            $html .= $this->renderList($headings, 0, $showCounter, $smoothScroll, $removeLinks, $useInternalCss);
        }

        $html .= '</div>';
        $html .= '</nav>';

        // Toggle-Script
        if ($allowToggle && $showHeader) {
            $uidJson      = json_encode($uid);
            $bodyIdJson   = json_encode($uid . '-body');
            $html .= '<script>(function(){'
                . 'var n=document.getElementById(' . $uidJson . ');'
                . 'if(!n)return;'
                . 'var btn=n.querySelector("[data-cms-toc-toggle]");'
                . 'var body=document.getElementById(' . $bodyIdJson . ');'
                . 'if(!btn||!body)return;'
                . 'btn.addEventListener("click",function(){'
                .   'var open=btn.getAttribute("aria-expanded")==="true";'
                .   'btn.setAttribute("aria-expanded",open?"false":"true");'
                .   'var label=btn.querySelector("[data-cms-toc-toggle-label]");'
                .   'var icon=btn.querySelector("[data-cms-toc-toggle-icon]");'
                .   'if(label){label.textContent=open?"Anzeigen":"Ausblenden";}'
                .   'if(icon){icon.textContent=open?"+":"−";}'
                .   'body.style.display=open?"none":"";'
                . '});'
                . '})();</script>';
        }

        // Smooth Scroll
        if ($smoothScroll) {
            $html .= '<script>(function(){'
                . 'document.querySelectorAll("[data-cms-toc-root] [data-tl]").forEach(function(a){'
                .   'a.addEventListener("click",function(e){'
                .     'var id=a.getAttribute("href").slice(1);'
                .     'var el=document.getElementById(id);'
                .     'if(!el)return;'
                .     'e.preventDefault();'
                .     'var offset=window.innerWidth<=767?' . $mobileOffset . ':' . $desktopOffset . ';'
                .     'var top=el.getBoundingClientRect().top+window.scrollY-offset;'
                .     'window.scrollTo({top:top,behavior:"smooth"});'
                .     'history.pushState(null,null,"#"+id);'
                .   '});'
                . '});'
                . '})();</script>';
        }

        return $html;
    }

    // ─── Hierarchy ────────────────────────────────────────────────────────────

    private function buildHierarchy(array $flat): array
    {
        $root  = [];
        $stack = [];

        foreach ($flat as $h) {
            $h['children'] = [];

            while (!empty($stack) && end($stack)['item']['level'] >= $h['level']) {
                array_pop($stack);
            }

            if (empty($stack)) {
                $root[] = $h;
                $stack[] = ['item' => &$root[count($root) - 1]];
            } else {
                $parent = &$stack[count($stack) - 1]['item'];
                $parent['children'][] = $h;
                $idx     = count($parent['children']) - 1;
                $stack[] = ['item' => &$parent['children'][$idx]];
            }
        }

        return $root;
    }

    // ─── Render List ─────────────────────────────────────────────────────────

    private function renderList(array $headings, int $depth, bool $numbered, bool $smooth, bool $removeLinks, bool $useInternalCss = true): string
    {
        if (empty($headings)) {
            return '';
        }

        $cls  = $depth === 0 ? 'cms-toc__list' : 'cms-toc__list cms-toc__list--nested';
        $tag  = $numbered ? 'ol' : 'ul';
        $listClass = $useInternalCss ? ' class="' . $cls . '"' : '';
        $html = '<' . $tag . $listClass . '>';

        foreach ($headings as $h) {
            $href      = '#' . htmlspecialchars($h['anchor'], ENT_QUOTES, 'UTF-8');
            $text      = htmlspecialchars($h['text'], ENT_QUOTES, 'UTF-8');
            $scrollAttr = $smooth ? ' data-tl' : '';
            $hasKids   = !empty($h['children']);

            $html .= '<li' . ($useInternalCss ? ' class="cms-toc__item' . ($hasKids ? ' has-children' : '') . '"' : '') . '>';
            if ($removeLinks) {
                $html .= '<span' . ($useInternalCss ? ' class="cms-toc__label"' : '') . '>' . $text . '</span>';
            } else {
                $html .= '<a href="' . $href . '"' . $scrollAttr . '>' . $text . '</a>';
            }

            if ($hasKids) {
                $html .= $this->renderList($h['children'], $depth + 1, $numbered, $smooth, $removeLinks, $useInternalCss);
            }

            $html .= '</li>';
        }

        $html .= '</' . $tag . '>';
        return $html;
    }
    private function generateUidSuffix(): string
    {
        try {
            return substr(bin2hex(random_bytes(4)), 0, 8);
        } catch (\Throwable) {
            return substr(hash('sha256', (string) microtime(true) . '|' . random_int(0, PHP_INT_MAX)), 0, 8);
        }
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function normalizeSettings(array $settings): array
    {
        $settings['support_types'] = $this->normalizeTypes((array) ($settings['support_types'] ?? self::DEFAULTS['support_types']));
        $settings['auto_insert_types'] = $this->normalizeTypes((array) ($settings['auto_insert_types'] ?? self::DEFAULTS['auto_insert_types']));
        $settings['headings'] = $this->normalizeHeadingLevels((array) ($settings['headings'] ?? self::DEFAULTS['headings']));
        $settings['position'] = $this->normalizeEnum((string) ($settings['position'] ?? self::DEFAULTS['position']), self::ALLOWED_POSITIONS, self::DEFAULTS['position']);
        $settings['theme'] = $this->normalizeEnum((string) ($settings['theme'] ?? self::DEFAULTS['theme']), self::ALLOWED_THEMES, self::DEFAULTS['theme']);
        $settings['exclude_headings'] = implode('|', $this->parseExcludedHeadings((string) ($settings['exclude_headings'] ?? '')));
        $settings['anchor_prefix'] = $this->normalizeAnchorPrefix((string) ($settings['anchor_prefix'] ?? ''));
        $settings['show_limit'] = max(1, (int) ($settings['show_limit'] ?? self::DEFAULTS['show_limit']));

        return $settings;
    }

    /** @return list<string> */
    private function normalizeTypes(array $types): array
    {
        return array_values(array_intersect(
            array_values(array_unique(array_map(static fn (mixed $type): string => strtolower(trim((string) $type)), $types))),
            self::ALLOWED_TYPES
        ));
    }

    /** @return list<string> */
    private function normalizeHeadingLevels(array $levels): array
    {
        return array_values(array_intersect(array_values(array_unique(array_map(static fn (mixed $level): string => strtolower(trim((string) $level)), $levels))), self::ALLOWED_HEADINGS));
    }

    /** @return list<string> */
    private function parseExcludedHeadings(string $value): array
    {
        $parts = preg_split('/\s*(?:\||,|\r\n|\r|\n)\s*/u', $value) ?: [];

        return array_values(array_filter(array_map(static fn (string $part): string => trim($part), $parts), static fn (string $part): bool => $part !== ''));
    }

    /** @param list<string> $allowed */
    private function normalizeEnum(string $value, array $allowed, string $fallback): string
    {
        $value = strtolower(trim($value));

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function resolveThemeVariant(string $theme): string
    {
        return match ($this->normalizeEnum($theme, self::ALLOWED_THEMES, self::DEFAULTS['theme'])) {
            'light' => 'white',
            'dark' => 'black',
            default => $this->normalizeEnum($theme, self::ALLOWED_THEMES, self::DEFAULTS['theme']),
        };
    }

    private function normalizeAnchorPrefix(string $prefix): string
    {
        $prefix = trim($prefix);
        $prefix = (string) preg_replace('/[^\p{L}\p{N}\-_]+/u', '-', $prefix);

        return trim($prefix, '-_');
    }

    private function lowerUtf8(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }
}
