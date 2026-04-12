# 365CMS – Performance-Center
> **Stand:** 2026-04-07 | **Version:** 2.9.0 | **Status:** Aktuell

<!-- UPDATED: 2026-04-07 -->

## Überblick

Das Performance-Center ist seit 2.2.0 ein eigenständiger Hauptbereich mit spezialisierten
Unterseiten für Cache, Medien, Datenbank, Settings und Sessions. Alle Seiten teilen sich
das `PerformanceModule` und den CSRF-Kontext `admin_performance`.

Der Einstieg erfolgt über `/admin/performance`.

Im aktuellen Stand ist der Bereich zugleich Diagnose- und Tuning-Zentrale für Cache, Medien, DB, Sessions und Laufzeit-Snapshots.

## Verfügbare Funktionen

| Funktion | Beschreibung |
|---|---|
| Cache-Verwaltung | Leeren, Warmup und Konfiguration der Cache-Ebenen |
| Medien-Optimierung | Bildkompression, WebP-Konvertierung und Lazy Loading |
| Datenbank | Tabellenstatus, Optimierung und Bereinigung |
| Settings | Allgemeine Performance-Einstellungen und Tuning |
| Sessions | Aktive Sitzungen verwalten und bereinigen |
| Übersicht | Dashboard mit zentralen Performance-Kennzahlen |

## Benötigte Rechte

- Rolle **Admin** erforderlich
- CSRF-Kontext: `admin_performance`

## Verwandte Dokumente

- [PERFORMANCE.md](PERFORMANCE.md)
- [../diagnose/README.md](../diagnose/README.md)
- [../info/README.md](../info/README.md)
