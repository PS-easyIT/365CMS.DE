# Theme & Plugin Marketplace

Kurzbeschreibung: Beschreibt die Marketplace-Oberflächen für Themes und Plugins im aktuellen Admin-Bereich.

Letzte Aktualisierung: 2026-05-03 · Version 2.9.513

---

## Überblick

365CMS enthält zwei getrennte Marketplace-Oberflächen:

- Theme Marketplace: `/admin/theme-marketplace`
- Plugin Marketplace: `/admin/plugin-marketplace`

Beide Seiten sind Admin-only und arbeiten mit dedizierten Modulen und Views. Der Theme Marketplace ist dabei stärker auf sichere Paketprüfung und kontrollierte Installation ausgelegt.

---

## Theme Marketplace

### Route und Technik

- Route: `/admin/theme-marketplace`
- Entry Point: `CMS/admin/theme-marketplace.php`
- Modul: `CMS/admin/modules/themes/ThemeMarketplaceModule.php`
- View: `CMS/admin/views/themes/marketplace.php`

### Katalogquellen

Der Theme-Katalog wird in folgender Reihenfolge aufgelöst:

1. frischer Cache für die konfigurierte Marketplace-URL
2. Remote-`index.json`
3. abgelaufener Cache als Fallback
4. lokaler `index.json`-Fallback im Theme-Umfeld

Aktueller Cache-TTL: 900 Sekunden.

### Sichtbarer Funktionsumfang

- Theme-Kacheln mit Screenshot, Version, Autor und Status
- Suche und Statusfilter im Frontend
- Kennzeichnung für aktiv, installiert, Update verfügbar, kostenpflichtig
- Hinweise zu Paketgröße, Download-Host, SHA-256 und Kompatibilität
- Installation per POST-Aktion `install`

### Voraussetzungen für Auto-Install

Ein Theme gilt nur dann als automatisch installierbar, wenn alle Bedingungen erfüllt sind:

- HTTPS-Download-URL
- Host liegt in der Marketplace-Allowlist
- erlaubte Archiv-Endung (`zip`)
- gültige SHA-256-Prüfsumme vorhanden
- Paketgröße innerhalb des Limits
- CMS- und PHP-Mindestversion passen zur aktuellen Runtime

### Paket-Härtung

Die Installation arbeitet zusätzlich mit:

- Install-Lock pro Theme-Zielordner
- Größen- und Eintragslimits für Archive
- Finalisierung des entpackten Pakets auf gültige Theme-Struktur
- Prüfung auf `style.css`, `theme.json` und `functions.php`
- Fallback-Bereinigung unvollständiger Pakete

### Wichtige Einordnung

Der Theme Marketplace ist bewusst vom operativen Theme-Management getrennt. Für Aktivieren, Health-Checks und Löschen bleibt `/admin/themes` die primäre Verwaltungsseite.

---

## Plugin Marketplace

### Route und Technik

- Route: `/admin/plugin-marketplace`
- Entry Point: `CMS/admin/plugin-marketplace.php`
- Modul: `CMS/admin/modules/plugins/PluginMarketplaceModule.php`
- View: `CMS/admin/views/plugins/marketplace.php`

### Funktionsumfang

- Kachelübersicht verfügbarer Plugins
- KPI-Karten für verfügbar, installiert und installierbar
- Volltextsuche und Kategoriefilter
- Installation per POST-Aktion `install`

Der Plugin Marketplace wird in der Sidebar nur eingeblendet, wenn `marketplace_enabled` aktiv ist.

---

## Unterschied zur regulären Verwaltung

Marketplace und Verwaltung sind bewusst getrennt:

- `/admin/themes` und `/admin/plugins` = operative Verwaltungsseiten
- `/admin/theme-marketplace` und `/admin/plugin-marketplace` = katalogbasiertes Entdecken und Installieren

---

## Sicherheit

Beide Marketplace-Seiten verwenden:

- Admin-Zugriffskontrolle
- CSRF-Token-Prüfung
- Redirect nach POST
- Session-basierte Erfolgs-/Fehlermeldungen

Der Theme Marketplace ergänzt dies um HTTPS-/Host-Allowlisting, Hash-Prüfung, Archiv-Grenzen und strukturierte Fehler-Report-Payloads.

---

## Verwandte Seiten

- [Themes & Design – Überblick](README.md)
- [Plugins – Überblick](../plugins/PLUGINS.md)
- [Updates](../system-settings/UPDATES.md)
