# 365CMS – Font Manager

Kurzbeschreibung: Verwaltung lokal gehosteter Schriftarten inklusive Import, Upload und CSS-Bereitstellung.

Letzte Aktualisierung: 2026-03-28 · Version 2.8.0 RC

---

## Überblick

Der Font Manager ersetzt ältere, isolierte Font-Verwaltungslösungen und bündelt DSGVO-freundliches lokales Hosting von Webfonts.

Mögliche Anwendungsfälle:

- Google-Fonts lokal spiegeln
- eigene WOFF/WOFF2-Dateien hochladen
- Schriftfamilien zentral verwalten
- generierte CSS-Pfade prüfen

---

## Datenmodell

Die aktuelle Implementierung kombiniert lokale Theme-Scans, Download-/Import-Pfade und persistente Konfigurationsdaten wie `CMS/config/fonts.json` bzw. passende Settings. Ältere Dokumentationsstände, die ausschließlich von einer einzelnen DB-Tabelle ausgehen, greifen zu kurz.

---

## Aktuelle technische Hinweise

Der aktuelle Arbeitsstand enthält zusätzliche Härtungen für den Font-Download, unter anderem robustere CSS-Endpunkt-Fallbacks, vorsichtigere Dateischreiboperationen und Audit-Logging für Scan-, Download- und Löschaktionen.

---

## Dokumentationshinweis

Verweise auf `admin/fonts-local.php` sind veraltet. Verwendet in aktueller Dokumentation ausschließlich `/admin/font-manager`.
