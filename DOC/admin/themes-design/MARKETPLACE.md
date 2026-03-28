# Theme & Plugin Marketplace

Kurzbeschreibung: Beschreibt die Marketplace-Oberflächen für Themes und Plugins im aktuellen Admin-Bereich.

Letzte Aktualisierung: 2026-03-28 · Version 2.8.0 RC

---

## Überblick

365CMS enthält zwei getrennte Marketplace-Oberflächen:

- Theme Marketplace: `/admin/theme-marketplace`
- Plugin Marketplace: `/admin/plugin-marketplace`

Beide Seiten sind Admin-only und arbeiten mit dedizierten Modulen und Views.

---

## Theme Marketplace

### Route und Technik

- Route: `/admin/theme-marketplace`
- Entry Point: `CMS/admin/theme-marketplace.php`
- Modul: `CMS/admin/modules/themes/ThemeMarketplaceModule.php`
- View: `CMS/admin/views/themes/marketplace.php`

### Funktionsumfang

Die Oberfläche zeigt einen Katalog aus Theme-Metadaten und unterstützt aktuell insbesondere:

- Theme-Katalog auf Basis der Registry
- Screenshot-/Vorschaudarstellung
- Statusanzeige für
    - aktiv
    - installiert
    - Update verfügbar
- Installation per Formularaktion `install`

### Wichtige Einordnung

Der Theme Marketplace existiert, wird aber derzeit **nicht** als Standardpunkt in der Sidebar ausgegeben. Für die tägliche Arbeit ist primär die Route `/admin/themes` relevant.

---

## Plugin Marketplace

### Route und Technik

- Route: `/admin/plugin-marketplace`
- Entry Point: `CMS/admin/plugin-marketplace.php`
- Modul: `CMS/admin/modules/plugins/PluginMarketplaceModule.php`
- View: `CMS/admin/views/plugins/marketplace.php`

### Funktionsumfang

Aktuell dokumentierter und im View sichtbarer Umfang:

- Kachelübersicht verfügbarer Plugins
- KPI-Karten für
    - verfügbar
    - installiert
    - installierbar
- Volltextsuche im Frontend
- Kategoriefilter
- Installation per POST-Aktion `install`

### Sidebar-Verhalten

Der Plugin Marketplace wird in der Sidebar nur eingeblendet, wenn die Einstellung `marketplace_enabled` nicht deaktiviert wurde.

---

## Unterschied zur regulären Verwaltung

Marketplace und Verwaltung sind bewusst getrennt:

- `/admin/themes` und `/admin/plugins` sind die operativen Verwaltungsseiten
- `/admin/theme-marketplace` und `/admin/plugin-marketplace` dienen dem katalogbasierten Entdecken und Installieren

Für produktive Pflege bestehender Installationen bleiben daher die klassischen Verwaltungsseiten die wichtigste Referenz.

---

## Sicherheit

Beide Marketplace-Seiten verwenden:

- Admin-Zugriffskontrolle
- CSRF-Token-Prüfung
- Redirect nach POST
- Session-basierte Erfolgs-/Fehlermeldungen

---

## Verwandte Seiten

- [Themes & Design – Überblick](README.md)
- [Plugins – Überblick](../plugins/PLUGINS.md)
- [Updates](../system-settings/UPDATES.md)
