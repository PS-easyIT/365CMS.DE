# 365CMS – Projektdokumentation | Abschnitt: Database schema

> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

# Database layer reference

This document describes the current database contract implemented in `CMS/core/Database.php`. It does not assume a legacy schema beyond what is visible in the code.

## Active database abstraction

`CMS/core/Database.php` is a PDO-based wrapper and the effective database abstraction layer. It opens the configured MySQL/MariaDB connection and uses native prepared statements. The primary implementation points are:

- `Database::connect()`
- `Database::prepare()`
- `Database::query()`
- `Database::execute()`
- `Database::repairTables()`

## Implementation characteristics

| Area | Current behavior |
| --- | --- |
| Connection | MySQL/MariaDB DSN built from `DB_HOST`, `DB_NAME`, and `DB_CHARSET` |
| Prepared statements | Native PDO with `PDO::ATTR_EMULATE_PREPARES => false` |
| Parameter binding | Explicit types for integers, booleans, nulls, and strings |
| Query execution | `Database::execute()` binds arguments and logs telemetry in debug mode |
| Schema management | Delegated to `SchemaManager` |
| Repair/migration | Delegated to `MigrationManager` |
| Table prefix | `DB_PREFIX` is applied at connection time |

## Schema-management facts

The code explicitly states that schema creation is no longer owned by the database object itself. The active pattern is:

```php
(new SchemaManager($this))->createTables();
(new MigrationManager($this))->repairTables();
```

This indicates that the authoritative schema logic is externalized. The runtime documentation should therefore describe the schema layer as an externalized manager layer rather than a monolithic database class.

## Operational notes

- `Database::prepare()` throws when the PDO connection is unavailable.
- `Database::query()` executes unparameterized SQL only for trusted statements.
- `Database::execute()` is the parameterized path.
- Debug mode can emit query telemetry via `Debug::query()`.

## Deutsch

# Referenz zur Datenbank-Schicht

Dieses Dokument beschreibt das aktuelle Datenbank-Contract, das in `CMS/core/Database.php` implementiert ist. Es geht nicht von einem veralteten Schema aus, sondern nur von dem, was im Code sichtbar ist.

## Aktive Datenbank-Abstraktion

`CMS/core/Database.php` ist ein PDO-basierter Wrapper und die wirksame Datenbank-Abstraktionsschicht. Er öffnet die konfigurierte MySQL/MariaDB-Verbindung und verwendet native Prepared Statements. Die wichtigsten Implementierungspunkte sind:

- `Database::connect()`
- `Database::prepare()`
- `Database::query()`
- `Database::execute()`
- `Database::repairTables()`

## Implementierungsmerkmale

| Bereich | Aktuelles Verhalten |
| --- | --- |
| Verbindung | MySQL/MariaDB-DSN aus `DB_HOST`, `DB_NAME` und `DB_CHARSET` |
| Prepared Statements | Native PDO mit `PDO::ATTR_EMULATE_PREPARES => false` |
| Parameterbindung | Explizite Typen für Integer, Boolean, Null und String |
| Abfrageausführung | `Database::execute()` bindet Argumente und protokolliert Telemetrie im Debug-Modus |
| Schema-Management | Delegiert an `SchemaManager` |
| Repair/Migration | Delegiert an `MigrationManager` |
| Tabellenpräfix | `DB_PREFIX` wird zur Verbindungszeit angewendet |

## Fakten zum Schema-Management

Der Code stellt ausdrücklich klar, dass die Schema-Erstellung nicht mehr in der Datenbankklasse selbst liegt. Das aktive Muster ist:

```php
(new SchemaManager($this))->createTables();
(new MigrationManager($this))->repairTables();
```

Das zeigt, dass die maßgebliche Schema-Logik in einer externen Manager-Schicht liegt. Die Laufzeit-Dokumentation sollte daher die Schema-Schicht als Manager-Layer beschreiben und nicht als monolithische Datenbankklasse.

## Betriebshinweise

- `Database::prepare()` wirft eine Exception, wenn die PDO-Verbindung nicht verfügbar ist.
- `Database::query()` führt nur unparametrisierte SQL-Anweisungen für vertrauenswürdige Statements aus.
- `Database::execute()` ist der parametrisierte Pfad.
- Im Debug-Modus kann Abfrage-Telemetrie über `Debug::query()` ausgegeben werden.
