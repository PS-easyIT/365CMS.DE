# 365CMS – Systeminfo & Dokumentation
> **Stand:** 2026-03-08 | **Version:** 2.5.4 | **Status:** Aktuell

<!-- ADDED: 2026-03-08 -->

## Überblick

Der Bereich **Info** bündelt Seiten für Betriebsinformationen und die integrierte Dokumentation.
Beide Seiten nutzen `SystemInfoModule` und den CSRF-Kontext `admin_system_info`.

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
- CSRF-Kontext: `admin_system_info`

## Verwandte Dokumente

- [INFO.md](INFO.md)
- [../diagnose/README.md](../diagnose/README.md)
- [../performance/README.md](../performance/README.md)
