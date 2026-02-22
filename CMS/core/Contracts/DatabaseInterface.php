<?php
/**
 * DatabaseInterface – Contract für die Datenbank-Abstraktionsschicht
 *
 * Ermöglicht Dependency Injection, Mocking in Tests und künftige
 * alternative Implementierungen (z. B. SQLite für Tests).
 *
 * @package CMSv2\Core\Contracts
 */

declare(strict_types=1);

namespace CMS\Contracts;

if (!defined('ABSPATH')) {
    exit;
}

interface DatabaseInterface
{
    /**
     * Führt einen INSERT aus und gibt die neue ID zurück (oder false bei Fehler).
     */
    public function insert(string $table, array $data): int|bool;

    /**
     * Führt einen UPDATE aus.
     */
    public function update(string $table, array $data, array $where): bool;

    /**
     * Führt einen DELETE aus.
     */
    public function delete(string $table, array $where): bool;

    /**
     * Gibt eine einzelne Zeile als Objekt zurück.
     */
    public function get_row(string $query, array $params = []): ?object;

    /**
     * Gibt einen einzelnen Wert zurück.
     */
    public function get_var(string $query, array $params = []): mixed;

    /**
     * Gibt mehrere Zeilen als Objekt-Array zurück.
     */
    public function get_results(string $query, array $params = []): array;

    /**
     * Gibt eine einzelne Spalte als numerisches Array zurück.
     */
    public function get_col(string $query, array $params = []): array;

    /**
     * Führt ein Prepared Statement aus und gibt es zurück.
     */
    public function execute(string $sql, array $params = []): \PDOStatement;

    /**
     * Gibt den Tabellenpräfix zurück.
     */
    public function getPrefix(): string;

    /**
     * Gibt die letzte Insert-ID zurück.
     */
    public function insert_id(): int;

    /**
     * Gibt die Anzahl betroffener Zeilen der letzten Operation zurück.
     */
    public function affected_rows(): int;
}
