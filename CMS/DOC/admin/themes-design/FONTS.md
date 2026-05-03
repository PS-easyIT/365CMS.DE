# 365CMS – Font Manager

Kurzbeschreibung: Verwaltung lokal gehosteter Schriftarten inklusive Import, Upload und CSS-Bereitstellung.

Letzte Aktualisierung: 2026-05-02 · Version 2.9.248

---

## Überblick

Der Font Manager ersetzt ältere, isolierte Font-Verwaltungslösungen und bündelt opt-in externe Google-Font-Einbindung mit DSGVO-freundlichem lokalem Hosting von Webfonts.

Mögliche Anwendungsfälle:

- Google-Fonts im Theme zunächst extern einbinden und später lokal spiegeln
- eigene WOFF/WOFF2-Dateien hochladen
- Schriftfamilien zentral verwalten
- generierte CSS-Pfade prüfen
- lokale Fonts im Frontend priorisieren und Remote-Fallback gezielt unterdrücken

---

## Datenmodell

Die aktuelle Implementierung kombiniert lokale Theme-Scans, Download-/Import-Pfade und persistente Konfigurationsdaten wie `CMS/config/fonts.json` bzw. passende Settings. Ältere Dokumentationsstände, die ausschließlich von einer einzelnen DB-Tabelle ausgehen, greifen zu kurz.

---

## Aktuelle technische Hinweise

Der aktuelle Arbeitsstand enthält zusätzliche Härtungen für den Font-Download, unter anderem robustere CSS-Endpunkt-Fallbacks, vorsichtigere Dateischreiboperationen und Audit-Logging für Scan-, Download- und Löschaktionen. Externe Google-Fonts bleiben im Theme als bewusster Fallback möglich; sobald lokale Fonts aktiviert sind, verwendet das Frontend vorrangig die lokal gespeicherten Dateien.

---

## Dokumentationshinweis

Verweise auf `admin/fonts-local.php` sind veraltet. Verwendet in aktueller Dokumentation ausschließlich `/admin/font-manager`.
