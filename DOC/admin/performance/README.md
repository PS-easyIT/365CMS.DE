# 365CMS – Performance-Center
> **Stand:** 17.05.2026 | **Version:** 3.0.11 | **Status:** Aktuell

<!-- UPDATED: 2026-05-17 -->

## Überblick

Das Performance-Center ist seit 2.2.0 ein eigenständiger Hauptbereich mit spezialisierten
Unterseiten für Cache, Medien, Datenbank, Settings und Sessions. Alle Seiten teilen sich
das `PerformanceModule` und den CSRF-Kontext `admin_performance`.

Der Einstieg erfolgt über `/admin/performance`.

Im aktuellen Stand ist der Bereich zugleich Diagnose- und Tuning-Zentrale für Cache, Medien, DB, Sessions und Laufzeit-Snapshots.

## Seitenstruktur

Die Performance-Seiten folgen einer konsistenten Bedienstruktur:

- klarer Seitenkopf mit Metazeile
- getrennte Toolbar-/Steuerzonen vor den Inhaltskarten
- saubere Tabellen-/Listendarstellung für operative Maßnahmen
- persistente Info-Bereiche mit kurzen Texten und robustem Action-Wrapping
- alle Performance-Unterseiten (`Übersicht`, `Cache-Verwaltung`, `Medien-Optimierung`, `Datenbank-Wartung`, `Performance-Einstellungen`, `Session-Verwaltung`) wurden am 17.05.2026 sichtbar im gleichen Strukturvertrag überarbeitet
- globaler UI-Hard-Standard (17.05.2026): Buttons sowie Karten-/Boxcontainer nutzen adminweit maximal 2px Radius; verschachtelte Boxen/Panels sind durch einen leicht helleren oder dunkleren Hintergrund klar hierarchisch getrennt

Betriebslogik (Cache-, DB-, Medien- und Session-Aktionen) bleibt unverändert.

## Verfügbare Funktionen

| Funktion | Beschreibung |
|---|---|
| Cache-Verwaltung | Leeren, Warmup und Konfiguration der Cache-Ebenen |
| Medien-Optimierung | Bildkompression, WebP-Konvertierung und Lazy Loading |
| Datenbank | Tabellenstatus, Optimierung und Bereinigung |
| Settings | Allgemeine Performance-Einstellungen und Tuning ohne dekorativen GZIP-Schalter |
| Sessions | Aktive Sitzungen verwalten und bereinigen |
| Übersicht | Dashboard mit zentralen Performance-Kennzahlen |

Die Performance-Einstellungen bieten für die öffentliche Bildauslieferung über `/media-file` feste Browser-Cache-TTLs von `3 Tage`, `7 Tage` oder `31 Tage`; Standard und Fallback sind `7 Tage`.

## Benötigte Rechte

- Rolle **Admin** erforderlich
- CSRF-Kontext: `admin_performance`

## Verwandte Dokumente

- [PERFORMANCE.md](PERFORMANCE.md)
- [../diagnose/README.md](../diagnose/README.md)
- [../info/README.md](../info/README.md)
