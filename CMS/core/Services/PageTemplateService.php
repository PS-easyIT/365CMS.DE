<?php
/**
 * Seitenvorlagen aus dem Theme-Manifest (`theme.json` → `page_templates`).
 *
 * Liest die vom aktiven Theme deklarierten Seitenvorlagen, validiert die zugehörigen
 * Zusatzfelder (`meta_fields`) beim Speichern und ordnet beim Rendern die Vorlage der
 * Theme-Datei zu. Angenommen werden ausschließlich Manifest-IDs, deren Datei als PHP-Datei
 * im Theme-Hauptverzeichnis existiert – nie eingereichte Dateipfade.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class PageTemplateService
{
    public const DEFAULT_TEMPLATE = 'default';
    public const MAX_OBJECT_ITEMS = 20;
    private const MAX_TEXT_LENGTH = 500;
    private const MAX_TEXTAREA_LENGTH = 4000;
    private const MAX_URL_LENGTH = 2048;
    private const FIELD_TYPES = ['text', 'textarea', 'url', 'array-of-objects'];

    /** @var list<array{id:string,label:string,description:string,file:string,meta_fields:array<string,array<string,mixed>>}>|null */
    private ?array $definitions = null;

    public function __construct(private readonly string $themePath)
    {
    }

    public static function forActiveTheme(): self
    {
        return new self(\CMS\ThemeManager::instance()->getThemePath());
    }

    /**
     * @return list<array{id:string,label:string,description:string,file:string,meta_fields:array<string,array<string,mixed>>}>
     */
    public function getDefinitions(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        $definitions = [];
        $themePath = rtrim($this->themePath, '\\/') . DIRECTORY_SEPARATOR;
        $manifest = $themePath . 'theme.json';

        try {
            if (is_file($manifest)) {
                $decoded = json_decode((string) file_get_contents($manifest), true, 64, JSON_THROW_ON_ERROR);
                $templates = is_array($decoded['page_templates'] ?? null) ? $decoded['page_templates'] : [];

                foreach ($templates as $template) {
                    if (!is_array($template)) {
                        continue;
                    }

                    $id = $this->sanitizeIdentifier((string) ($template['id'] ?? ''));
                    $file = basename((string) ($template['file'] ?? ''));
                    if ($id === '' || isset($definitions[$id])
                        || preg_match('/^[a-z0-9][a-z0-9_-]*\.php$/i', $file) !== 1
                        || !is_file($themePath . $file)
                    ) {
                        continue;
                    }

                    $definitions[$id] = [
                        'id' => $id,
                        'label' => $this->plainText((string) ($template['label'] ?? $id), 120),
                        'description' => $this->plainText((string) ($template['description'] ?? ''), 500),
                        'file' => $file,
                        'meta_fields' => $this->normalizeFieldDefinitions(is_array($template['meta_fields'] ?? null) ? $template['meta_fields'] : []),
                    ];
                }
            }
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('pages')->warning('Seitenvorlagen konnten nicht aus dem Theme-Manifest gelesen werden.', [
                'exception' => $e->getMessage(),
            ]);
        }

        if (!isset($definitions[self::DEFAULT_TEMPLATE])) {
            $definitions = [self::DEFAULT_TEMPLATE => [
                'id' => self::DEFAULT_TEMPLATE,
                'label' => 'Standard',
                'description' => '',
                'file' => 'page.php',
                'meta_fields' => [],
            ]] + $definitions;
        }

        return $this->definitions = array_values($definitions);
    }

    public function isRegistered(string $templateId): bool
    {
        return $this->findDefinition($templateId) !== null;
    }

    /**
     * Normalisiert die Vorlagen-ID; nicht registrierte Werte werden abgelehnt.
     */
    public function normalizeTemplate(mixed $templateId): string
    {
        if (!is_scalar($templateId) && $templateId !== null) {
            throw new \InvalidArgumentException('Ungültige Seitenvorlage.');
        }

        $id = trim((string) ($templateId ?? ''));
        if ($id === '') {
            return self::DEFAULT_TEMPLATE;
        }

        if ($this->sanitizeIdentifier($id) !== $id || !$this->isRegistered($id)) {
            throw new \InvalidArgumentException('Die gewählte Seitenvorlage ist im aktiven Theme nicht registriert.');
        }

        return $id;
    }

    /**
     * Validiert die Zusatzfelder der Vorlage und liefert sie als JSON (oder null ohne Werte).
     *
     * @param array<string,mixed> $metadata
     * @throws \InvalidArgumentException bei unbekannter Vorlage oder ungültigen Feldwerten
     */
    public function encodeMetadata(array $metadata, string $templateId): ?string
    {
        $templateId = $this->normalizeTemplate($templateId);
        $definition = $this->findDefinition($templateId);
        $fields = is_array($definition['meta_fields'] ?? null) ? $definition['meta_fields'] : [];
        $normalized = [];

        foreach ($fields as $key => $field) {
            if (!array_key_exists($key, $metadata)) {
                continue;
            }

            $value = $this->normalizeFieldValue($metadata[$key], $field, (string) ($field['label'] ?? $key));
            if ($value === '' || $value === []) {
                continue;
            }

            $normalized[$key] = $value;
        }

        if ($normalized === []) {
            return null;
        }

        return json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Rohwerte für die erneute Anzeige im Editor (z. B. nach Validierungsfehler oder Sprachwechsel).
     *
     * @param array<string,mixed> $metadata
     * @return array<string,mixed>
     */
    public function rawEditorValues(array $metadata, string $templateId): array
    {
        $definition = $this->findDefinition($templateId);
        $fields = is_array($definition['meta_fields'] ?? null) ? $definition['meta_fields'] : [];
        $values = [];
        foreach ($fields as $key => $field) {
            if (!array_key_exists($key, $metadata)) {
                continue;
            }
            $value = $metadata[$key];
            $values[$key] = is_scalar($value) ? (string) $value : (is_array($value) ? $value : '');
        }

        return $values;
    }

    /**
     * Ordnet eine gespeicherte Seite der Theme-Datei zu und dekodiert ihre Zusatzfelder in `$page['meta']`.
     *
     * @param array<string,mixed> $page
     * @return array{template:string,page:array<string,mixed>}
     */
    public function prepareForRender(array $page): array
    {
        $storedTemplate = trim((string) ($page['page_template'] ?? ''));
        $definition = $storedTemplate !== '' ? $this->findDefinition($storedTemplate) : null;
        if ($storedTemplate !== '' && $definition === null) {
            Logger::instance()->withChannel('pages')->warning('Gespeicherte Seitenvorlage ist im aktiven Theme nicht registriert; Standardlayout wird verwendet.', [
                'page_id' => (int) ($page['id'] ?? 0),
                'template' => substr($storedTemplate, 0, 80),
            ]);
        }
        $definition ??= $this->findDefinition(self::DEFAULT_TEMPLATE);

        $meta = [];
        $rawMeta = $page['page_meta_json'] ?? null;
        if (is_array($page['meta'] ?? null)) {
            $meta = $page['meta'];
        } elseif (is_string($rawMeta) && trim($rawMeta) !== '') {
            try {
                $decoded = json_decode($rawMeta, true, 16, JSON_THROW_ON_ERROR);
                $meta = is_array($decoded) ? $decoded : [];
            } catch (\Throwable $e) {
                Logger::instance()->withChannel('pages')->error('Zusatzfelder der Seitenvorlage sind beschädigt und werden ignoriert.', [
                    'page_id' => (int) ($page['id'] ?? 0),
                    'exception' => $e->getMessage(),
                ]);
                $meta = [];
            }
        }

        $page['meta'] = $meta;
        $file = (string) ($definition['file'] ?? 'page.php');
        $template = substr($file, 0, -4) ?: 'page';

        return ['template' => $template, 'page' => $page];
    }

    /** @return array<string,mixed>|null */
    private function findDefinition(string $templateId): ?array
    {
        foreach ($this->getDefinitions() as $definition) {
            if ($definition['id'] === $templateId) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed> $fields
     * @return array<string,array<string,mixed>>
     */
    private function normalizeFieldDefinitions(array $fields): array
    {
        $normalized = [];
        foreach ($fields as $key => $field) {
            $key = $this->sanitizeIdentifier((string) $key);
            if ($key === '' || !is_array($field)) {
                continue;
            }

            $type = strtolower(trim((string) ($field['type'] ?? 'text')));
            if (!in_array($type, self::FIELD_TYPES, true)) {
                $type = 'text';
            }

            $definition = [
                'label' => $this->plainText((string) ($field['label'] ?? $key), 120),
                'type' => $type,
            ];

            if ($type === 'array-of-objects') {
                $subFields = [];
                foreach ((array) ($field['fields'] ?? []) as $subField) {
                    $subKey = $this->sanitizeIdentifier(is_scalar($subField) ? (string) $subField : '');
                    if ($subKey !== '') {
                        $subFields[] = $subKey;
                    }
                }
                if ($subFields === []) {
                    continue;
                }
                $definition['fields'] = array_values(array_unique($subFields));
            }

            $normalized[$key] = $definition;
        }

        return $normalized;
    }

    /**
     * @param array<string,mixed> $field
     * @return string|list<array<string,string>>
     */
    private function normalizeFieldValue(mixed $value, array $field, string $label): string|array
    {
        $type = (string) ($field['type'] ?? 'text');

        if ($type === 'array-of-objects') {
            return $this->normalizeObjectList($value, (array) ($field['fields'] ?? []), $label);
        }

        if (!is_scalar($value) && $value !== null) {
            throw new \InvalidArgumentException('Das Feld „' . $label . '“ erwartet einen einzelnen Wert.');
        }

        $value = (string) ($value ?? '');

        return match ($type) {
            'url' => $this->normalizeUrl($value, $label),
            'textarea' => $this->plainText($value, self::MAX_TEXTAREA_LENGTH, true),
            default => $this->plainText($value, self::MAX_TEXT_LENGTH),
        };
    }

    /**
     * @param list<string> $allowedKeys
     * @return list<array<string,string>>
     */
    private function normalizeObjectList(mixed $value, array $allowedKeys, string $label): array
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return [];
            }
            try {
                $value = json_decode($value, true, 8, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw new \InvalidArgumentException('Das Feld „' . $label . '“ enthält kein gültiges JSON.');
            }
        }

        if ($value === null) {
            return [];
        }

        if (!is_array($value) || !array_is_list($value)) {
            throw new \InvalidArgumentException('Das Feld „' . $label . '“ erwartet eine Liste von Einträgen.');
        }

        if (count($value) > self::MAX_OBJECT_ITEMS) {
            throw new \InvalidArgumentException('Das Feld „' . $label . '“ erlaubt höchstens ' . self::MAX_OBJECT_ITEMS . ' Einträge.');
        }

        $items = [];
        foreach ($value as $entry) {
            if (!is_array($entry) || ($entry !== [] && array_is_list($entry))) {
                throw new \InvalidArgumentException('Jeder Eintrag im Feld „' . $label . '“ muss ein Objekt sein.');
            }

            $item = [];
            foreach ($allowedKeys as $key) {
                if (!array_key_exists($key, $entry)) {
                    continue;
                }
                $fieldValue = $entry[$key];
                if (!is_scalar($fieldValue) && $fieldValue !== null) {
                    throw new \InvalidArgumentException('Der Wert „' . $key . '“ im Feld „' . $label . '“ muss Text sein.');
                }
                $fieldValue = $key === 'url'
                    ? $this->normalizeUrl((string) ($fieldValue ?? ''), $label)
                    : $this->plainText((string) ($fieldValue ?? ''), $key === 'text' ? self::MAX_TEXTAREA_LENGTH : self::MAX_TEXT_LENGTH, $key === 'text');
                if ($fieldValue !== '') {
                    $item[$key] = $fieldValue;
                }
            }

            if ($item !== []) {
                $items[] = $item;
            }
        }

        return $items;
    }

    private function normalizeUrl(string $value, string $label): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $invalid = strlen($value) > self::MAX_URL_LENGTH
            || preg_match('/[\s\\\\<>"\'`\x00-\x1F\x7F]/', $value) === 1
            || str_starts_with($value, '//');

        if (!$invalid && str_starts_with($value, '/')) {
            return $value;
        }

        if (!$invalid && str_starts_with($value, '#')) {
            return $value;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        if ($invalid
            || !in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)
            || (in_array($scheme, ['http', 'https'], true) && filter_var($value, FILTER_VALIDATE_URL) === false)
        ) {
            throw new \InvalidArgumentException('Das Feld „' . $label . '“ enthält keine sichere URL (erlaubt: /pfad, https://…, mailto:, tel:).');
        }

        return $value;
    }

    private function plainText(string $value, int $maxLength, bool $multiline = false): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace($multiline ? '/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]+/u' : '/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
        $value = $multiline
            ? (preg_replace("/[ \t]+/u", ' ', $value) ?? $value)
            : (preg_replace('/\s+/u', ' ', $value) ?? $value);

        return trim(mb_substr($value, 0, $maxLength, 'UTF-8'));
    }

    private function sanitizeIdentifier(string $value): string
    {
        $value = strtolower(trim($value));

        return preg_match('/^[a-z0-9][a-z0-9_-]{0,79}$/', $value) === 1 ? $value : '';
    }
}
