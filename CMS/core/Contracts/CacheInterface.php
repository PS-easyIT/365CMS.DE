<?php
/**
 * CacheInterface – Contract für die Cache-Abstraktionsschicht
 *
 * Orientiert sich an PSR-16 (Simple Cache) ohne Composer-Abhängigkeit.
 * Ermöglicht alternative Backends (File, APCu, Redis/Valkey) und Mocking.
 *
 * @package CMSv2\Core\Contracts
 */

declare(strict_types=1);

namespace CMS\Contracts;

if (!defined('ABSPATH')) {
    exit;
}

interface CacheInterface
{
    /**
     * Liest einen Cache-Wert anhand des Schlüssels.
     *
     * @param  string $key     Cache-Schlüssel
     * @param  mixed  $default Rückgabewert, wenn Schlüssel nicht gefunden oder abgelaufen
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Speichert einen Wert im Cache.
     *
     * @param  string   $key  Cache-Schlüssel
     * @param  mixed    $value Zu speichernder Wert (muss serialisierbar sein)
     * @param  int|null $ttl  TTL in Sekunden; null = Standard-TTL der Implementierung
     * @return bool           true bei Erfolg
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Löscht einen einzelnen Cache-Eintrag.
     */
    public function delete(string $key): bool;

    /**
     * Prüft, ob ein gültiger Cache-Eintrag für den Schlüssel existiert.
     */
    public function has(string $key): bool;

    /**
     * Leert den gesamten Cache (alle Einträge).
     */
    public function clear(): bool;

    /**
     * Liest mehrere Werte in einem Aufruf (Batch-Get).
     *
     * @param  string[] $keys    Liste von Cache-Schlüsseln
     * @param  mixed    $default Standardwert für fehlende Schlüssel
     * @return array             Assoziatives Array [ key => value ]
     */
    public function getMultiple(array $keys, mixed $default = null): array;

    /**
     * Speichert mehrere Werte in einem Aufruf (Batch-Set).
     *
     * @param  array    $values Assoziatives Array [ key => value ]
     * @param  int|null $ttl    TTL in Sekunden; null = Standard-TTL
     * @return bool             true wenn alle Einträge erfolgreich gespeichert
     */
    public function setMultiple(array $values, ?int $ttl = null): bool;

    /**
     * Löscht mehrere Einträge in einem Aufruf (Batch-Delete).
     *
     * @param  string[] $keys
     * @return bool           true wenn alle Einträge erfolgreich gelöscht
     */
    public function deleteMultiple(array $keys): bool;
}
