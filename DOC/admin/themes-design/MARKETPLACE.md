# Theme & Plugin Marketplace

**Dateien:** `admin/theme-marketplace.php`, `admin/plugin-marketplace.php`

---

## Übersicht

Der integrierte Marketplace ermöglicht das Durchsuchen, Installieren und Aktivieren von Themes und Plugins direkt aus dem Admin-Bereich.

---

## Theme-Marketplace

**Datei:** `admin/theme-marketplace.php`

### Verfügbare Themes

| Theme | Zielgruppe | Hauptfarbe |
|-------|-----------|------------|
| **365Network** | Netzwerk-Plattformen | Blau |
| **academy365** | Bildung & Kurse | Grün |
| **buildbase** | Bauwesen & Handwerk | Orange |
| **business** | Unternehmensseiten | Dunkelblau |
| **cms-default** | Standard / Entwicklung | Grau |
| **logilink** | Logistik & Transport | Rot |
| **medcarepro** | Gesundheitswesen | Teal |
| **personalflow** | HR & Recruiting | Violett |
| **technexus** | IT & Technologie | Dunkelgrün |

### Theme-Karte zeigt
- Theme-Name und Beschreibung
- Screenshot-Vorschau
- Aktiv/Inaktiv Status Badge
- **Vorschau** – Öffnet Theme-Preview im neuen Tab
- **Aktivieren** – Setzt Theme als aktives Theme
- **Anpassen** – Öffnet direkt den Customizer für dieses Theme

### Theme installieren (extern)
1. Theme-ZIP hochladen
2. ZIP wird entpackt nach `/CMS/themes/`
3. Theme erscheint automatisch in der Liste
4. `theme.json` wird ausgelesen für Metadaten

---

## Plugin-Marketplace

**Datei:** `admin/plugin-marketplace.php`

### Aktueller Stand
- Der Bereich ist derzeit bewusst als **Coming-Soon-Vorschau** umgesetzt.
- Die Seite nutzt das zentrale Admin-Layout und verweist für produktive Aktionen aktuell auf:
    - `Admin → Plugins` für Upload, Aktivierung und Verwaltung
    - `Admin → Updates` für vorhandene Update-Flows

### Suche & Filter
- **Volltextsuche** in Plugin-Name und Beschreibung
- **Kategorien:** Content, Commerce, Security, SEO, Integration, Tools

### Verfügbare Plugins

| Plugin | Kategorie | Beschreibung |
|--------|-----------|--------------|
| **cms-experts** | Content | Experten-Verzeichnis mit Profilen und Suche |
| **cms-companies** | Content | Firmen-Verzeichnis mit Logos und Kontakt |
| **cms-events** | Content | Event-Management mit Kalender |
| **cms-speakers** | Content | Referenten-Verwaltung für Konferenzen |
| **cms-jobads** | Content | Stellenanzeigen-Portal |
| **cms-importer** | Tools | WordPress XML-Import, JSON-Import, CSV-Import |

### Plugin installieren (aktueller Weg)

**Schritt 1 – Upload:**
```
Admin → Plugins → Neu installieren → ZIP hochladen
```

**Schritt 2 – Aktivierung:**
```
Admin → Plugins → [Plugin-Name] → Aktivieren
```

**Schritt 3 – Konfiguration:**
Plugins zeigen nach Aktivierung ggf. eigene Menüpunkte in der Admin-Sidebar.

> Hinweis: Die eigentliche Marketplace-Registry mit Suche, Lizenzprüfung und 1-Klick-Installation ist noch nicht produktiv angebunden.

### Plugin-Struktur (Mindest-Anforderung)

```
plugins/
└── mein-plugin/
    └── mein-plugin.php   (Hauptdatei mit Plugin-Header)
```

### Plugin-Header

```php
<?php
/**
 * Plugin Name: Mein Plugin
 * Description: Kurze Beschreibung
 * Author: Entwicklername
 * Author URI: https://example.de
 */
```

---

## Updates

### Update-Manager – `admin/updates.php`

Zeigt verfügbare Updates für:
- **CMS Core** – Via GitHub Release API
- **Plugins** – Via plugin-eigene Update-URLs
- **Themes** – Via theme.json Version-Check

### Update-Prozess
1. Update-Verfügbarkeit wird beim Dashboard-Aufruf geprüft (gecacht 6h)
2. "Jetzt aktualisieren" startet den Download
3. Backup wird vor Update automatisch erstellt
4. Dateien werden ersetzt
5. Datenbank-Migration läuft automatisch (falls vorhanden)

---

## Verwandte Seiten

- [Plugins verwalten](PLUGINS.md)
- [Themes & Design](README.md)
- [Theme-Customizer](CUSTOMIZER.md)
