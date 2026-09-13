# 365CMS – Projektdokumentation | Abschnitt: Admin – Site Tables
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

The site-table administration uses the `TablesModule` and the `CMS/admin/site-tables.php` entry point. The module owns the supported table views and validates requested operations before database access.

Table administration is an internal maintenance function. Use the authenticated admin screen and never expose table names, credentials, or raw SQL through public documentation or user input.

## Deutsch

Die Site-Table-Verwaltung verwendet das `TablesModule` und den Einstieg `CMS/admin/site-tables.php`. Das Modul stellt die unterstützten Tabellenansichten bereit und prüft angeforderte Operationen vor dem Datenbankzugriff.

Die Tabellenverwaltung ist eine interne Wartungsfunktion. Verwenden Sie die authentifizierte Admin-Seite und geben Sie weder Tabellennamen, Zugangsdaten noch rohes SQL über öffentliche Dokumentation oder Benutzereingaben frei.
