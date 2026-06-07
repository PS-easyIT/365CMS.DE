# 365CMS – Dokumentation

> **Stand:** 27.03.2026 | **Version:** 3.0.0 | **Status:** Aktuell

## Inhaltsverzeichnis
- [Beschreibung](#beschreibung)
- [Systemvoraussetzungen](#systemvoraussetzungen)
- [Installation](#installation)
- [Verzeichnisstruktur](#verzeichnisstruktur)
- [Schnellstart](#schnellstart)
- [Siehe auch](#siehe-auch)

## Beschreibung

365CMS ist ein modulares Content-Management-System (CMS) auf Basis von PHP 8.4+, MySQL und Vanilla JavaScript. Es bietet eine flexible Architektur mit einem Hook- und Event-System für Plugins, einer klaren Trennung von MVC und Templates sowie einer integrierten Marktplatz-Funktionalität.

Das System ist speziell für Enterprise-Umgebungen optimiert und bietet erweiterte Sicherheitsfeatures, Performance-Optimierungen und eine intuitive Benutzeroberfläche.

## Systemvoraussetzungen

| Komponente | Anforderungen |
|------------|----------------|
| PHP | 8.4.0 oder höher |
| MySQL | 8.0 oder höher |
| Webserver | Apache (mit mod_rewrite) oder Nginx |
| Speicherplatz | Mindestens 100 MB |
| Datenbank | UTF-8 Unterstützung |

### PHP-Erweiterungen

- `pdo_mysql`
- `gd` oder `imagick` (für Bildverarbeitung)
- `json`
- `mbstring`
- `fileinfo`

## Installation

Die Installation erfolgt über den integrierten Installer. Folgen Sie diesen Schritten:

1. **Dateien hochladen:** Laden Sie die 365CMS-Dateien in das gewünschte Verzeichnis auf Ihrem Server hoch.
2. **Installer starten:** Rufen Sie die Datei `install.php` im Browser auf (z. B. `https://ihre-domain.de/install.php`).
3. **Konfiguration:** Folgen Sie den Anweisungen des Installers, um die Datenbankverbindung und Admin-Zugangsdaten zu konfigurieren.
4. **Abschluss:** Nach erfolgreicher Installation können Sie den Installer löschen und das CMS unter der Hauptdomain nutzen.

### Automatisierte Installation (CLI)

Falls Sie Zugriff auf die Command Line haben, können Sie die Installation auch über die Konsole durchführen:

```bash
php install.php --db-host=localhost --db-name=cms_db --db-user=cms_user --db-pass=password --admin-email=admin@example.com --admin-password=secure_password
```

## Verzeichnisstruktur

```
CMS/
├── admin/                  # Backend-Interface und Admin-Funktionen
├── assets/                 # Statische Dateien (CSS, JS, Bilder)
├── config/                 # Konfigurationsdateien
│   ├── app.php             # Hauptkonfiguration
│   └── .htaccess           # Schutz für Konfigurationsdateien
├── core/                   # Kernkomponenten des CMS
│   ├── Bootstrap.php       # Bootstrapping und Initialisierung
│   ├── Database.php        # Datenbankabstraktion
│   ├── Hooks.php           # Hook- und Event-System
│   ├── PluginManager.php   # Plugin-Verwaltung
│   ├── Router.php          # Routing-Engine
│   ├── Security.php        # Sicherheitsfunktionen
│   └── ThemeManager.php    # Theme-Verwaltung
├── DOC/                    # Dokumentation (dieser Ordner)
├── includes/               # Globale Funktionen und Helfer
├── install/                # Installationsskripte
├── marketplace/            # Marktplatz für Plugins und Themes
├── member/                 # Mitgliedschafts- und Nutzerfunktionen
├── plugins/                # Plugin-Verzeichnis
├── themes/                 # Theme-Verzeichnis
├── uploads/                # Hochgeladene Dateien
├── vendor/                 # Composer-Abhängigkeiten
├── views/                  # MVC-Templates
├── index.php               # Haupt-Einstiegspunkt
├── config.php              # Konfigurationsstub
└── install.php             # Installer
```

## Schnellstart

### Erste Schritte nach der Installation

1. **Login:** Melden Sie sich im Admin-Bereich an (`/admin`).
2. **Theme auswählen:** Wählen Sie ein Theme in den Einstellungen.
3. **Plugins installieren:** Gehen Sie zum Marktplatz und installieren Sie benötigte Plugins.
4. **Inhalte erstellen:** Erstellen Sie Seiten, Beiträge oder andere Inhalte.

### Beispiel: Erste Seite erstellen

```php
// Beispiel für die Erstellung einer Seite über die API
$pageManager = CMS\PageManager::instance();
$result = $pageManager->createPage([
    'title' => 'Willkommen',
    'slug' => 'willkommen',
    'content' => '<h1>Herzlich willkommen!</h1><p>Dies ist der Inhalt Ihrer ersten Seite.</p>',
    'status' => 'publish'
]);

if ($result) {
    echo "Seite erfolgreich erstellt!";
} else {
    echo "Fehler beim Erstellen der Seite.";
}
```

## Siehe auch

- [ARCHITECTURE.md](ARCHITECTURE.md) – Architektur und technische Details
- [SECURITY.md](SECURITY.md) – Sicherheitsfeatures und Best Practices
- [INSTALLATION.md](INSTALLATION.md) – Detaillierte Installationsanleitung