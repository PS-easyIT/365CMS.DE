<?php
/**
 * Translation Service
 *
 * Internationalisierung über Symfony Translation (wenn verfügbar)
 * mit robustem Fallback für Shared-Hosting-Umgebungen.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class TranslationService
{
    private static ?self $instance = null;

    private readonly Database $db;
    private readonly string $prefix;
    private readonly string $langPath;

    private string $locale = 'de';

    /** @var array<string, array<string, array<string, string>>> */
    private array $fallbackCatalog = [];

    /** @var object|null */
    private ?object $translator = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->langPath = ABSPATH . 'lang/';

        $this->locale = $this->resolveLocale();
        $this->initTranslator();
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return string[]
     */
    public function getAvailableLocales(): array
    {
        if (!is_dir($this->langPath)) {
            return ['de', 'en'];
        }

        $files = glob($this->langPath . '*.yaml') ?: [];
        $locales = [];

        foreach ($files as $file) {
            $locale = pathinfo($file, PATHINFO_FILENAME);
            if (preg_match('/^[a-z]{2}(?:_[A-Z]{2})?$/', $locale) === 1) {
                $locales[] = $locale;
            }
        }

        $locales = array_values(array_unique($locales));
        sort($locales);

        return $locales !== [] ? $locales : ['de', 'en'];
    }

    public function translate(string $message, string $domain = 'default', array $parameters = []): string
    {
        if ($this->translator !== null && method_exists($this->translator, 'trans')) {
            try {
                /** @var string $result */
                $result = $this->translator->trans($message, $parameters, $domain, $this->locale);
                return $result;
            } catch (\Throwable) {
                // Fallback unten
            }
        }

        $translated = $this->fallbackCatalog[$this->locale][$domain][$message]
            ?? $this->fallbackCatalog[$this->locale]['default'][$message]
            ?? $message;

        if ($parameters !== []) {
            $translated = strtr($translated, $parameters);
        }

        return $translated;
    }

    public function translatePlural(string $single, string $plural, int $number, string $domain = 'default'): string
    {
        if ($this->translator !== null && method_exists($this->translator, 'trans')) {
            try {
                $key = $single . '|'.$plural;
                /** @var string $result */
                $result = $this->translator->trans($key, ['%count%' => $number], $domain, $this->locale);

                if ($result !== $key) {
                    return $result;
                }
            } catch (\Throwable) {
                // Fallback unten
            }
        }

        $selected = ($number === 1) ? $single : $plural;
        return $this->translate($selected, $domain, ['%count%' => (string)$number]);
    }

    private function resolveLocale(): string
    {
        $available = $this->getAvailableLocales();

        try {
            $stmt = $this->db->prepare("SELECT option_value FROM {$this->prefix}settings WHERE option_name = ? LIMIT 1");
            $stmt->execute(['setting_language']);
            $value = (string) ($stmt->fetchColumn() ?: '');
            if ($value !== '' && in_array($value, $available, true)) {
                return $value;
            }
        } catch (\Throwable) {
            // Fallback zu Default
        }

        return in_array('de', $available, true) ? 'de' : ($available[0] ?? 'de');
    }

    private function initTranslator(): void
    {
        $available = $this->getAvailableLocales();

        $translatorClass = '\\Symfony\\Component\\Translation\\Translator';
        $yamlLoaderClass = '\\Symfony\\Component\\Translation\\Loader\\YamlFileLoader';

        if (class_exists($translatorClass) && class_exists($yamlLoaderClass)) {
            try {
                /** @var object $translator */
                $translator = new $translatorClass($this->locale);
                $translator->addLoader('yaml', new $yamlLoaderClass());

                foreach ($available as $locale) {
                    $file = $this->langPath . $locale . '.yaml';
                    if (!is_file($file)) {
                        continue;
                    }

                    // Domain default
                    $translator->addResource('yaml', $file, $locale, 'default');
                }

                $this->translator = $translator;
                return;
            } catch (\Throwable) {
                $this->translator = null;
            }
        }

        // Fallback-Katalog laden
        foreach ($available as $locale) {
            $file = $this->langPath . $locale . '.yaml';
            if (!is_file($file)) {
                continue;
            }
            $this->fallbackCatalog[$locale] = $this->parseSimpleYamlCatalog($file);
        }
    }

    /**
     * Minimaler YAML-Parser für flache Domain-Kataloge.
     * Unterstützt das Format:
     *
     * default:
     *   "Key": "Value"
     */
    private function parseSimpleYamlCatalog(string $file): array
    {
        $catalog = ['default' => []];
        $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        $currentDomain = 'default';

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (preg_match('/^([a-zA-Z0-9_\-]+):\s*$/', $trimmed, $m) === 1) {
                $currentDomain = $m[1];
                if (!isset($catalog[$currentDomain])) {
                    $catalog[$currentDomain] = [];
                }
                continue;
            }

            if (preg_match('/^\s*["\'](.+?)["\']\s*:\s*["\'](.*)["\']\s*$/', $line, $m) === 1) {
                $catalog[$currentDomain][$m[1]] = stripcslashes($m[2]);
                continue;
            }

            if (preg_match('/^\s*([^:\"\']+)\s*:\s*(.+)\s*$/', $line, $m) === 1) {
                $key = trim($m[1]);
                $value = trim($m[2], " \t\"'");
                $catalog[$currentDomain][$key] = stripcslashes($value);
            }
        }

        return $catalog;
    }
}
