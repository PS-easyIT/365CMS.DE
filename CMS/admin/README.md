# Admin Panel

Willkommen im Admin-Bereich des CMS!

## 📁 Struktur

```
admin/
├── index.php           # Dashboard (Hauptseite)
├── pages.php           # Seiten- & Landing-Page-Verwaltung
├── users.php           # Benutzerverwaltung
├── settings.php        # Systemeinstellungen
├── plugins.php         # Plugin-Verwaltung
├── theme-editor.php    # Theme-Editor (CSS/Farben/Typografie)
├── seo.php             # SEO-Einstellungen
├── performance.php     # Performance-Einstellungen
├── analytics.php       # Analytics & Traffic-Statistiken
├── backup.php          # Backup & Wiederherstellung
├── subscriptions.php   # Abo-Pakete & Zuweisungen
├── groups.php          # Benutzergruppen-Verwaltung
├── updates.php         # System-, Plugin- & Theme-Updates
├── system.php          # System & Diagnose
├── README.md           # Diese Datei
├── includes/
│   └── sidebar.php     # Legacy-Sidebar (deprecated, → admin-menu.php)
└── partials/
    └── admin-menu.php  # Zentrale Menü-Definition & renderAdminSidebar()
```

## 🚀 Zugriff

Das Admin Panel ist unter `/admin` erreichbar.

**Voraussetzung:** Sie müssen als Administrator angemeldet sein.

## 📊 Dashboard Features

Das Dashboard zeigt:
- **Benutzer-Statistiken** – Anzahl und Aktivität
- **Seiten-Statistiken** – Veröffentlichte Seiten
- **Medien-Übersicht** – Upload-Größe
- **Aktive Sessions** – Angemeldete Benutzer
- **System-Informationen** – Server, PHP, Sicherheit
- **Performance-Daten** – Memory Usage
- **Schnellzugriff** – Häufig benötigte Aktionen

## 🔌 Plugin-Integration

Plugins können das Admin Panel erweitern durch:

1. **Eigene Menüpunkte** via `admin_menu_items` Filter
2. **Dashboard-Widgets** via `admin_dashboard_widgets` Action
3. **Settings-Sektionen** via `admin_settings_page` Action

Siehe: [../doc/admin/ADMIN-PANEL-INTEGRATION.md](../doc/admin/ADMIN-PANEL-INTEGRATION.md)

## 🔒 Bootstrap-Muster (Pflicht für Admin-Dateien)

Jede Admin-Datei **muss** folgendes Einleitungsmuster verwenden:

```php
<?php
declare(strict_types=1);

// 1) Konfiguration laden (definiert ABSPATH, CORE_PATH, SITE_URL, …)
require_once dirname(__DIR__) . '/config.php';

// 2) Autoloader laden (stellt alle CMS\* Klassen bereit)
require_once CORE_PATH . 'autoload.php';

// 3) Hilfsfunktionen laden (sanitize_text, esc_html, …)
require_once ABSPATH . 'includes/functions.php';

use CMS\Auth;

if (!defined('ABSPATH')) {
    exit; // Direktzugriff verhindern
}

// 4) Admin-Zugriff prüfen
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}
```

## 🎨 Design

Das Admin Panel nutzt:
- **Dark Sidebar** – Dunkle Navigation links
- **White Content** – Heller Content-Bereich rechts
- **Responsive Layout** – Mobile-optimiert
- **Konsistente UI** – Einheitliche Komponenten

## 📝 Vollständige Dokumentation

Siehe [../doc/admin/](../doc/admin/) für detaillierte Dokumentation aller Admin-Seiten.

CSS: `assets/css/admin.css`

## 🔒 Sicherheit

- ✅ Admin-Zugriffsprüfung auf allen Seiten
- ✅ CSRF-Token für alle Formulare
- ✅ Input-Sanitierung
- ✅ Output-Escaping

## 📖 Weitere Seiten hinzufügen

1. Erstellen Sie `admin/meine-seite.php`
2. Route wird automatisch zu `/admin/meine-seite`
3. Kopieren Sie das Template von einer bestehenden Seite
4. Fügen Sie Menüpunkt via Filter hinzu (optional)

## 🛠️ Entwicklung

Beim Entwickeln neuer Admin-Seiten:

```php
<?php
declare(strict_types=1);

use CMS\Auth;
use CMS\Hooks;

if (!defined('ABSPATH')) {
    exit;
}

// WICHTIG: Immer Admin-Check!
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

// Ihr Code hier...
```

## 💡 Best Practices

1. **Konsistenz** - Nutzen Sie vorhandene CSS-Klassen
2. **Sicherheit** - Immer Input validieren und Output escapen
3. **UX** - Erfolgs/Fehler-Meldungen via `$_SESSION`
4. **Performance** - Datenbankabfragen optimieren
5. **Dokumentation** - Code kommentieren

## 📚 Weitere Informationen

- [Admin Panel Integration Guide](../doc/admin/ADMIN-PANEL-INTEGRATION.md)
- [Hooks Reference](../doc/HOOKS-REFERENCE.md)
- [Security Guide](../doc/SECURITY.md)
- [Database Schema](../doc/DATABASE-SCHEMA.md)
