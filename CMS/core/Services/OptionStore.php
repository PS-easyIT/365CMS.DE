<?php
/**
 * Request-Cache für Einträge der Tabelle `settings`.
 *
 * Lädt beim ersten Zugriff in einer Abfrage alle Werte mit `autoload = 1` sowie die
 * Namen aller übrigen Optionen. Nicht existierende Schlüssel kosten danach keine Abfrage,
 * Optionen mit `autoload = 0` werden bei Bedarf einzeln nachgeladen und gemerkt. Schreibzugriffe auf `settings` über
 * `CMS\Database` leeren den Cache automatisch (siehe Database::noteWrite()).
 *
 * @package 365CMS
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class OptionStore
{
    private static ?self $instance = null;

    /** @var array<string, string|null> */
    private array $values = [];

    private bool $autoloaded = false;

    /** true, wenn die Namensliste vollständig geladen wurde (fehlende Schlüssel existieren nicht) */
    private bool $complete = false;

    /** @var array<string, true> Optionen mit autoload = 0, deren Wert noch nicht geladen ist */
    private array $lazy = [];

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    /**
     * Rohwert einer Option oder $default, wenn sie nicht existiert bzw. die DB nicht erreichbar ist.
     */
    public function get(string $name, ?string $default = null): ?string
    {
        $this->loadAutoload();

        if (!array_key_exists($name, $this->values)) {
            $this->values[$name] = ($this->complete && !isset($this->lazy[$name])) ? null : $this->fetchOne($name);
            unset($this->lazy[$name]);
        }

        return $this->values[$name] ?? $default;
    }

    /**
     * Mehrere Optionen auf einmal; fehlende Schlüssel werden mit einer Abfrage nachgeladen.
     *
     * @param list<string> $names
     * @return array<string, string|null>
     */
    public function getMany(array $names, ?string $default = null): array
    {
        $this->loadAutoload();

        $missing = [];
        foreach (array_unique($names) as $name) {
            if (array_key_exists($name, $this->values)) {
                continue;
            }
            if ($this->complete && !isset($this->lazy[$name])) {
                $this->values[$name] = null;
                continue;
            }
            $missing[] = $name;
            unset($this->lazy[$name]);
        }

        if ($missing !== []) {
            foreach ($missing as $name) {
                $this->values[$name] = null;
            }

            try {
                $db = Database::instance();
                $placeholders = implode(', ', array_fill(0, count($missing), '?'));
                $rows = $db->get_results(
                    "SELECT option_name, option_value FROM {$db->getPrefix()}settings WHERE option_name IN ({$placeholders})",
                    $missing
                );
                foreach ($rows as $row) {
                    $this->values[(string) $row->option_name] = $row->option_value !== null ? (string) $row->option_value : null;
                }
            } catch (\Throwable) {
                // Fehlende Werte bleiben null → Default.
            }
        }

        $result = [];
        foreach ($names as $name) {
            $result[$name] = $this->values[$name] ?? $default;
        }

        return $result;
    }

    /**
     * Verwirft den Cache (nach Schreibzugriffen auf `settings`).
     */
    public function flush(): void
    {
        $this->values = [];
        $this->lazy = [];
        $this->autoloaded = false;
        $this->complete = false;
    }

    private function loadAutoload(): void
    {
        if ($this->autoloaded) {
            return;
        }

        $this->autoloaded = true;

        try {
            $db = Database::instance();
            // Werte nur für autoload = 1; für die übrigen Optionen genügt der Name.
            $rows = $db->get_results(
                "SELECT option_name, autoload, IF(autoload = 1, option_value, NULL) AS option_value FROM {$db->getPrefix()}settings"
            );
            foreach ($rows as $row) {
                $name = (string) $row->option_name;
                if (array_key_exists($name, $this->values)) {
                    continue;
                }
                if ((int) $row->autoload === 1) {
                    $this->values[$name] = $row->option_value !== null ? (string) $row->option_value : null;
                } else {
                    $this->lazy[$name] = true;
                }
            }
            $this->complete = true;
        } catch (\Throwable) {
            // Ohne DB bleibt der Cache leer; get() fällt auf Einzelabfragen bzw. Default zurück.
        }
    }

    private function fetchOne(string $name): ?string
    {
        try {
            $db = Database::instance();
            $value = $db->get_var(
                "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ? LIMIT 1",
                [$name]
            );
        } catch (\Throwable) {
            return null;
        }

        return $value !== null && $value !== false ? (string) $value : null;
    }
}
