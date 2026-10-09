<?php
declare(strict_types=1);

namespace CMS\Services;

if (!defined('ABSPATH')) {
    exit;
}

final class PageTemplateService
{
    private array $definitions;

    public function __construct(private string $themePath)
    {
        $this->themePath = rtrim($themePath, '\\/') . DIRECTORY_SEPARATOR;
        $this->definitions = $this->readDefinitions();
    }

    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    public function getDefinition(string $id): ?array
    {
        foreach ($this->definitions as $definition) {
            if ($definition['id'] === $id) {
                return $definition;
            }
        }
        return null;
    }

    private function readDefinitions(): array
    {
        $definitions = [
            'default' => ['id' => 'default', 'label' => 'Standard', 'description' => '', 'file' => 'page.php', 'meta_fields' => []],
        ];
        $manifestPath = $this->themePath . 'theme.json';
        if (!is_file($manifestPath)) {
            return array_values($definitions);
        }

        try {
            $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            $templates = is_array($manifest) ? ($manifest['page_templates'] ?? []) : null;
            if (!is_array($templates)) {
                throw new \UnexpectedValueException('Invalid page template registry.');
            }
            foreach ($templates as $template) {
                if (!is_array($template)) {
                    throw new \UnexpectedValueException('Invalid page template definition.');
                }
                $id = $template['id'] ?? '';
                $file = $template['file'] ?? '';
                if (!is_string($id) || preg_match('/^[a-z0-9_-]{1,80}$/D', $id) !== 1
                    || !is_string($file) || preg_match('/^[a-z0-9][a-z0-9_-]*\.php$/D', $file) !== 1
                    || !is_file($this->themePath . $file)) {
                    throw new \UnexpectedValueException('Invalid or missing page template file.');
                }
                $fields = $template['meta_fields'] ?? [];
                if (!is_array($fields)) {
                    throw new \UnexpectedValueException('Invalid page template fields.');
                }
                foreach ($fields as $key => $field) {
                    if (!is_string($key) || preg_match('/^[a-z0-9_-]{1,80}$/D', $key) !== 1
                        || !is_array($field)
                        || !in_array($field['type'] ?? 'text', ['text', 'textarea', 'date', 'url', 'select', 'array', 'array-of-objects'], true)) {
                        throw new \UnexpectedValueException('Unsupported page template field.');
                    }
                    foreach (['label', 'placeholder'] as $textKey) {
                        if (isset($field[$textKey]) && !is_string($field[$textKey])) {
                            throw new \UnexpectedValueException('Invalid page template field label.');
                        }
                    }
                    if (($field['type'] ?? '') === 'array-of-objects') {
                        if (!is_array($field['fields'] ?? null) || !array_is_list($field['fields'])) {
                            throw new \UnexpectedValueException('Invalid feature-card field list.');
                        }
                        foreach ($field['fields'] as $objectField) {
                            if (!is_string($objectField) || preg_match('/^[a-z0-9_-]{1,80}$/D', $objectField) !== 1) {
                                throw new \UnexpectedValueException('Invalid feature-card field.');
                            }
                        }
                    }
                    if (($field['type'] ?? '') === 'select' && (!is_array($field['options'] ?? null)
                        || array_filter($field['options'], static fn(mixed $option): bool => !is_string($option)) !== [])) {
                        throw new \UnexpectedValueException('Invalid page template selection options.');
                    }
                }
                if ((isset($template['label']) && !is_string($template['label']))
                    || (isset($template['description']) && !is_string($template['description']))) {
                    throw new \UnexpectedValueException('Invalid page template label.');
                }
                $definitions[$id] = [
                    'id' => $id,
                    'label' => self::plainText((string) ($template['label'] ?? $id), 120),
                    'description' => self::plainText((string) ($template['description'] ?? ''), 500),
                    'file' => $file,
                    'meta_fields' => $fields,
                ];
            }
        } catch (\JsonException|\UnexpectedValueException $exception) {
            \CMS\Logger::instance()->withChannel('pages.templates')->warning('Page template manifest could not be fully read.', [
                'exception' => $exception->getMessage(),
            ]);
        }

        return array_values($definitions);
    }

    public function encodeMetadata(mixed $raw, string $templateId): ?string
    {
        $definition = $this->getDefinition($templateId);
        if ($definition === null) {
            throw new \InvalidArgumentException('Die ausgewählte Seitenvorlage ist im aktiven Theme nicht verfügbar.');
        }
        if ($raw !== null && !is_array($raw)) {
            throw new \InvalidArgumentException('Die Zusatzfelder der Seitenvorlage sind ungültig.');
        }

        $values = [];
        foreach ($definition['meta_fields'] as $key => $field) {
            $value = $this->normalizeValue($raw[$key] ?? '', $field, (string) ($field['label'] ?? $key));
            if ($value !== '' && $value !== []) {
                $values[$key] = $value;
            }
        }
        if ($values === []) {
            return null;
        }
        $json = json_encode($values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strlen($json) > 65535) {
            throw new \InvalidArgumentException('Die Zusatzfelder sind zu groß. Bitte die Texte oder Feature-Cards kürzen.');
        }
        return $json;
    }

    private function normalizeValue(mixed $value, array $field, string $label): string|array
    {
        $type = $field['type'] ?? 'text';
        if ($type === 'array-of-objects') {
            if (is_string($value)) {
                if (trim($value) === '') {
                    return [];
                }
                try {
                    $value = json_decode($value, true, 16, JSON_THROW_ON_ERROR);
                } catch (\JsonException $exception) {
                    throw new \InvalidArgumentException($label . ': Bitte ein gültiges JSON-Array eingeben.', 0, $exception);
                }
            }
            if (!is_array($value) || !array_is_list($value) || count($value) > 20) {
                throw new \InvalidArgumentException($label . ': Erlaubt ist ein Array mit höchstens 20 Feature-Cards.');
            }
            $items = [];
            foreach ($value as $item) {
                if (!is_array($item) || array_is_list($item)) {
                    throw new \InvalidArgumentException($label . ': Jede Feature-Card muss ein JSON-Objekt sein.');
                }
                $normalized = [];
                foreach ($field['fields'] ?? [] as $key) {
                    if (!is_string($key) || preg_match('/^[a-z0-9_-]{1,80}$/D', $key) !== 1) {
                        throw new \UnexpectedValueException('Invalid feature-card field definition.');
                    }
                    $normalized[$key] = $this->normalizeValue($item[$key] ?? '', [
                        'type' => $key === 'url' || str_ends_with($key, '_url') ? 'url' : 'text',
                    ], $label . ' · ' . $key);
                }
                if (array_filter($normalized, static fn(string $text): bool => $text !== '') !== []) {
                    $items[] = $normalized;
                }
            }
            return $items;
        }
        if ($type === 'array') {
            if (is_string($value)) {
                $value = preg_split('/[\r\n,]+/', $value) ?: [];
            }
            if (!is_array($value) || !array_is_list($value) || count($value) > 100) {
                throw new \InvalidArgumentException($label . ': Bitte höchstens 100 einfache Einträge eingeben.');
            }
            $items = [];
            foreach ($value as $item) {
                $item = $this->normalizeValue($item, ['type' => 'text'], $label);
                if ($item !== '') {
                    $items[] = $item;
                }
            }
            return array_values(array_unique($items));
        }
        if (!is_scalar($value) && $value !== null) {
            throw new \InvalidArgumentException($label . ': Bitte einen einfachen Textwert eingeben.');
        }
        $value = self::plainText((string) $value, $type === 'textarea' ? 5000 : 2000);
        if ($value === '') {
            return '';
        }
        if ($type === 'url') {
            if (preg_match('/[\x00-\x20\x7f\\\\]/', $value) === 1 || str_starts_with($value, '//')) {
                throw new \InvalidArgumentException($label . ': Bitte eine sichere HTTP(S)-URL oder einen lokalen Pfad eingeben.');
            }
            if (in_array($value[0], ['/', '#', '?'], true)) {
                return $value;
            }
            if (!in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)
                || filter_var($value, FILTER_VALIDATE_URL) === false) {
                throw new \InvalidArgumentException($label . ': Bitte eine gültige HTTP(S)-URL oder einen lokalen Pfad eingeben.');
            }
        } elseif ($type === 'date') {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) {
                throw new \InvalidArgumentException($label . ': Bitte ein gültiges Datum im Format JJJJ-MM-TT eingeben.');
            }
        } elseif ($type === 'select' && !in_array($value, $field['options'] ?? [], true)) {
            throw new \InvalidArgumentException($label . ': Die gewählte Option ist nicht verfügbar.');
        }
        return $value;
    }

    private static function plainText(string $value, int $length): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $value);
        if ($value === null) {
            throw new \InvalidArgumentException('Die Zusatzfelder enthalten ungültige Textzeichen.');
        }
        return function_exists('mb_substr') ? mb_substr($value, 0, $length) : substr($value, 0, $length);
    }

    public function prepareForRender(array $page): array
    {
        $id = is_string($page['page_template'] ?? null) ? $page['page_template'] : 'default';
        $definition = $this->getDefinition($id) ?? $this->getDefinition('default');
        if ($this->getDefinition($id) === null) {
            \CMS\Logger::instance()->withChannel('pages.templates')->warning('Stored page template is unavailable; using the standard page layout.', [
                'page_id' => $page['id'] ?? null, 'template' => $id,
            ]);
        }
        $page['meta'] = [];
        if (!empty($page['page_meta_json'])) {
            try {
                $raw = json_decode((string) $page['page_meta_json'], true, 16, JSON_THROW_ON_ERROR);
                $normalized = $this->encodeMetadata($raw, $definition['id']);
                $page['meta'] = $normalized === null ? [] : json_decode($normalized, true, 16, JSON_THROW_ON_ERROR);
            } catch (\JsonException|\InvalidArgumentException $exception) {
                \CMS\Logger::instance()->withChannel('pages.templates')->warning('Page template metadata could not be rendered.', [
                    'page_id' => $page['id'] ?? null,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }
        return ['template' => substr($definition['file'], 0, -4), 'page' => $page];
    }
}
