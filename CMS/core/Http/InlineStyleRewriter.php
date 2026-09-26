<?php
/**
 * Inline-Style-Rewriter für die nonce-basierte CSP.
 *
 * Die Produktiv-CSP blockiert `style="…"`-Attribute (style-src-attr). Statt die
 * Richtlinie aufzuweichen, werden serverseitig gerenderte Style-Attribute einer
 * vollständigen HTML-Antwort in generierte Klassen überführt und als ein einziger
 * `<style nonce="…">`-Block vor `</head>` ausgeliefert.
 *
 * - Nur vollständige HTML-Dokumente (mit `</head>`) und Content-Type text/html.
 * - Inhalte von script/style/textarea/title/xmp und HTML-Kommentare bleiben unangetastet.
 * - Werte mit `{`, `}`, `<`, `>`, `@`, `\` oder Kommentaren werden nicht übernommen
 *   (kein Ausbruch aus der CSS-Regel), das Attribut bleibt dann CSP-blockiert.
 * - Große bzw. gestreamte Antworten (> Chunk-Größe) werden unverändert durchgereicht.
 *
 * @package CMSv2\Core\Http
 */

declare(strict_types=1);

namespace CMS\Http;

if (!defined('ABSPATH')) {
    exit;
}

final class InlineStyleRewriter
{
    private const int CHUNK_SIZE = 4 * 1024 * 1024;
    private const array RAW_TEXT_ELEMENTS = ['script', 'style', 'textarea', 'title', 'xmp', 'noembed', 'noframes'];
    private const string TAG_PATTERN = '/\G<([a-zA-Z][a-zA-Z0-9:-]*+)((?:[^>"\']++|"[^"]*+"|\'[^\']*+\')*+)>/';
    private const string ATTRIBUTE_PATTERN = '/\G(\s*+)([^\s"\'>\/=]++)(?:(\s*+=\s*+)("[^"]*+"|\'[^\']*+\'|[^\s"\'=<>`]++))?/';

    private static bool $started = false;

    private bool $passthrough = false;

    /** @var array<string, string> Klasse => Deklarationen */
    private array $rules = [];

    private bool $changed = false;

    public function __construct(private readonly string $nonce)
    {
    }

    /**
     * Startet den Ausgabepuffer einmalig pro Request.
     */
    public static function startBuffer(string $nonce): void
    {
        if (self::$started || $nonce === '' || PHP_SAPI === 'cli') {
            return;
        }

        self::$started = true;
        $rewriter = new self($nonce);
        ob_start(static fn(string $buffer, int $phase): string => $rewriter->handle($buffer, $phase), self::CHUNK_SIZE);
    }

    /**
     * Output-Callback: transformiert nur, wenn die gesamte Antwort in einem Stück vorliegt.
     */
    public function handle(string $buffer, int $phase): string
    {
        if ($this->passthrough) {
            return $buffer;
        }

        $isFinal = ($phase & PHP_OUTPUT_HANDLER_FINAL) !== 0;
        $isFirst = ($phase & PHP_OUTPUT_HANDLER_START) !== 0;

        if (!$isFinal || !$isFirst) {
            // Teilausgabe (ob_flush, Streaming, große Downloads) → nicht mehr eingreifen.
            $this->passthrough = true;
            return $buffer;
        }

        if (!$this->isHtmlResponse()) {
            return $buffer;
        }

        return $this->rewrite($buffer);
    }

    /**
     * Wandelt Style-Attribute eines HTML-Dokuments in nonce-geschützte Klassenregeln um.
     */
    public function rewrite(string $html): string
    {
        $headClose = stripos($html, '</head>');
        if ($headClose === false || stripos($html, 'style') === false) {
            return $html;
        }

        $this->rules = [];
        $this->changed = false;
        $length = strlen($html);
        $out = '';
        $pos = 0;

        while (($lt = strpos($html, '<', $pos)) !== false) {
            $out .= substr($html, $pos, $lt - $pos);

            if (substr_compare($html, '<!--', $lt, 4) === 0) {
                $end = strpos($html, '-->', $lt + 4);
                $end = $end === false ? $length : $end + 3;
                $out .= substr($html, $lt, $end - $lt);
                $pos = $end;
                continue;
            }

            if (preg_match(self::TAG_PATTERN, $html, $match, 0, $lt) !== 1) {
                $out .= '<';
                $pos = $lt + 1;
                continue;
            }

            $tag = $match[0];
            $name = strtolower($match[1]);
            $out .= $this->rewriteTag($tag, $match[1], $match[2]);
            $pos = $lt + strlen($tag);

            if (in_array($name, self::RAW_TEXT_ELEMENTS, true) && !str_ends_with(rtrim($match[2]), '/')) {
                $close = stripos($html, '</' . $name, $pos);
                $close = $close === false ? $length : $close;
                $out .= substr($html, $pos, $close - $pos);
                $pos = $close;
            }
        }

        $out .= substr($html, $pos);

        if (!$this->changed) {
            return $html;
        }

        if ($this->rules === []) {
            return $out;
        }

        $css = '';
        foreach ($this->rules as $class => $declarations) {
            // Dreifache Klasse → Spezifität (0,3,0): schlägt übliche Theme-/Framework-Regeln
            // wie das ursprüngliche Inline-Style, CSSOM-Änderungen per JS gewinnen weiterhin.
            $css .= '.' . $class . '.' . $class . '.' . $class . '{' . $declarations . '}';
        }

        $styleTag = '<style id="cms-inline-styles" nonce="' . htmlspecialchars($this->nonce, ENT_QUOTES, 'UTF-8') . '">' . $css . '</style>';
        $insertAt = stripos($out, '</head>');

        return $insertAt === false ? $out : substr($out, 0, $insertAt) . $styleTag . substr($out, $insertAt);
    }

    private function rewriteTag(string $tag, string $name, string $attributes): string
    {
        if ($attributes === '' || stripos($attributes, 'style') === false) {
            return $tag;
        }

        $parsed = $this->parseAttributes($attributes);
        if ($parsed === null) {
            return $tag;
        }

        $styleIndex = null;
        $classIndex = null;
        foreach ($parsed as $index => $attribute) {
            $attrName = strtolower($attribute['name']);
            if ($attrName === 'style' && $styleIndex === null) {
                $styleIndex = $index;
            } elseif ($attrName === 'class' && $classIndex === null) {
                $classIndex = $index;
            }
        }

        if ($styleIndex === null) {
            return $tag;
        }

        $declarations = $this->normalizeDeclarations($parsed[$styleIndex]['value']);
        if ($declarations === null) {
            return $tag;
        }

        $this->changed = true;

        if ($declarations === '') {
            unset($parsed[$styleIndex]);
            return '<' . $name . $this->buildAttributes($parsed) . '>';
        }

        $class = 'cms-s-' . substr(hash('sha256', $declarations), 0, 12);
        $this->rules[$class] = $declarations;
        unset($parsed[$styleIndex]);

        if ($classIndex !== null) {
            $existing = trim($parsed[$classIndex]['value'] ?? '');
            $parsed[$classIndex]['value'] = $existing === '' ? $class : $existing . ' ' . $class;
            $parsed[$classIndex]['raw'] = null;
        } else {
            $classAttribute = ['lead' => ' ', 'name' => 'class', 'value' => $class, 'raw' => null];
            $last = end($parsed);
            if ($last !== false && $last['name'] === '') {
                // Vor dem abschließenden Whitespace bzw. "/" einfügen (<img … />).
                $trailerKey = array_key_last($parsed);
                $trailer = $parsed[$trailerKey];
                unset($parsed[$trailerKey]);
                $parsed[] = $classAttribute;
                $parsed[] = $trailer;
            } else {
                $parsed[] = $classAttribute;
            }
        }

        return '<' . $name . $this->buildAttributes($parsed) . '>';
    }

    /**
     * @return list<array{lead: string, name: string, value: ?string, raw: ?string}>|null
     */
    private function parseAttributes(string $attributes): ?array
    {
        $result = [];
        $offset = 0;
        $length = strlen($attributes);

        while ($offset < $length) {
            if (trim(substr($attributes, $offset)) === '' || trim(substr($attributes, $offset)) === '/') {
                $result[] = ['lead' => substr($attributes, $offset), 'name' => '', 'value' => null, 'raw' => ''];
                break;
            }

            if (preg_match(self::ATTRIBUTE_PATTERN, $attributes, $m, 0, $offset) !== 1 || $m[0] === '') {
                return null;
            }

            $rawValue = $m[4] ?? null;
            $value = null;
            if ($rawValue !== null && $rawValue !== '') {
                $unquoted = ($rawValue[0] === '"' || $rawValue[0] === "'") ? substr($rawValue, 1, -1) : $rawValue;
                $value = html_entity_decode($unquoted, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $result[] = [
                'lead' => $m[1] === '' ? ' ' : $m[1],
                'name' => $m[2],
                'value' => $value,
                'raw' => $m[2] . ($rawValue !== null && $rawValue !== '' ? $m[3] . $rawValue : ''),
            ];
            $offset += strlen($m[0]);
        }

        return $result;
    }

    /**
     * @param array<int, array{lead: string, name: string, value: ?string, raw: ?string}> $attributes
     */
    private function buildAttributes(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $attribute) {
            if ($attribute['name'] === '') {
                $html .= $attribute['lead'];
                continue;
            }

            if ($attribute['raw'] !== null) {
                $html .= $attribute['lead'] . $attribute['raw'];
                continue;
            }

            $html .= $attribute['lead'] . $attribute['name'] . '="' . htmlspecialchars((string) $attribute['value'], ENT_QUOTES, 'UTF-8') . '"';
        }

        return $html;
    }

    /**
     * Normalisiert Deklarationen; null = unsicher, Attribut unverändert lassen.
     */
    private function normalizeDeclarations(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/[{}<>@\\\\]|\/\*|\*\//', $value) === 1 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value) === 1) {
            return null;
        }

        if (!str_contains($value, ':')) {
            return null;
        }

        $value = rtrim($value, " \t\n\r;");

        return $value === '' ? '' : $value . ';';
    }

    private function isHtmlResponse(): bool
    {
        if (http_response_code() === 304) {
            return false;
        }

        $isHtml = true;
        foreach (headers_list() as $header) {
            // Feste Länge oder Download → Antwort nicht verändern.
            if (stripos($header, 'content-length:') === 0 || stripos($header, 'content-disposition:') === 0) {
                return false;
            }
            if (stripos($header, 'content-type:') === 0) {
                $isHtml = stripos($header, 'text/html') !== false;
            }
        }

        return $isHtml;
    }
}
