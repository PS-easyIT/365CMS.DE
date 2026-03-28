# 365CMS – Systeminfo & Dokumentation
> **Stand:** 2026-03-28 | **Version:** 2.8.0 RC | **Status:** Aktuell

<!-- UPDATED: 2026-03-28 -->

## Überblick

Der Bereich **Info** bündelt Seiten für Betriebsinformationen und die integrierte Dokumentation.
Die Seiten folgen dem üblichen Admin-Muster mit geschütztem Zugriff, serverseitiger Aufbereitung und eigener Diagnose-/Info-Darstellung.

| Route | Zweck |
|---|---|
| `/admin/info` | Systeminformationen, PHP-Version, MySQL-Status, Speicher, Extensions |
| `/admin/documentation` | Lokale Dokumentationsansicht direkt im Admin |

## Verfügbare Funktionen

| Funktion | Beschreibung |
|---|---|
| System-Info | PHP-Version, Speicher, MySQL-Status und aktive Extensions |
| PHP-Info | Detaillierte PHP-Konfiguration und Module |
| Dokumentation | Integrierter Viewer für die lokale Projektdokumentation |

## Benötigte Rechte

- Rolle **Admin** erforderlich
- CSRF-Kontext: abhängig von der jeweiligen Admin-Aktion

## Verwandte Dokumente

- [INFO.md](INFO.md)
- [../diagnose/README.md](../diagnose/README.md)
- [../performance/README.md](../performance/README.md)
