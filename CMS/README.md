﻿# 365CMS.DE  [![Generic badge](https://img.shields.io/badge/VERSION-1.6.9-blue.svg)](https://shields.io/)
 ---
## Sicheres, modulares und erweiterbares Content Management System => [WWW.365CMS.DE](HTTPS://365CMS.DE)
![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)![MySQL](https://img.shields.io/badge/mysql-4479A1.svg?style=for-the-badge&logo=mysql&logoColor=white)![MariaDB](https://img.shields.io/badge/MariaDB-003545?style=for-the-badge&logo=mariadb&logoColor=white)![HTML5](https://img.shields.io/badge/html5-%23E34F26.svg?style=for-the-badge&logo=html5&logoColor=white)![CSS3](https://img.shields.io/badge/css3-%231572B6.svg?style=for-the-badge&logo=css3&logoColor=white)
## 

365CMS ist eine moderne, PHP-basierte Plattform, entwickelt mit Fokus auf Sicherheit, Performance und einfache Erweiterbarkeit. Es bietet eine robuste Architektur für Entwickler und eine intuitive Oberfläche für Benutzer.

---

## 🚀 Key Features

### 🏗️ Core-System
- **Modulare Architektur:** OOP-Struktur mit Singleton-Pattern, Namespaces und Autoloading (PSR-4).
- **Plugin-System:** Leistungsstarkes Hook-System (Actions & Filters) für flexible Erweiterungen.
- **Service-Layer:** Klare Trennung von Geschäftslogik und Präsentation.
- **REST-API Vorbereitung:** Strukturierte Endpoints für zukünftige Headless-Anwendungen.

### 🎨 Theme & Design
- **Live Theme Customizer:** Visueller Editor mit über 50 Optionen (Farben, Typografie, Layout).
- **CSS-Generator:** Automatische Generierung von optimiertem CSS basierend auf Einstellungen.
- **Responsive Design:** Mobile-First Ansatz mit integriertem Dark Mode.
- **Theme-Verwaltung:** Einfacher Wechsel und Installation von Themes.
- **Google Fonts (DSGVO-konform):** Lokaler Import von Schriftarten.

### 🛠️ Admin-Backend
- **Dashboard:** Widgets für Schnellzugriff und Systemstatus.
- **Content Management:** Editor für Seiten, Beiträge und Landing Pages.
- **Medienverwaltung:** Upload, Bearbeitung und Organisation von Dateien.
- **Analytics:** Eingebautes Besucher-Tracking und System-Monitoring.
- **Updates:** One-Click Updates für Core, Plugins und Themes via GitHub-Integration.

### 👥 Benutzer & Mitglieder
- **Rollen-System:** Granulare Rechtevergabe (Admin, Editor, Mitglied).
- **Member-Dashboard:** Dedizierter Bereich für registrierte Nutzer.
- **Subscription-System:** Verwaltung von Mitgliedschaftsplänen und Zahlungen.
- **Gruppen:** Organisieren von Nutzern in Teams oder Zugriffsgruppen.

### 🔒 Sicherheit (Security First)
- **Schutzmechanismen:** CSRF-Schutz, XSS-Prevention, Input Sanitization & Output Escaping.
- **Authentifizierung:** Sicheres Login mit Brute-Force-Schutz und Rate Limiting.
- **Datenbank:** 100% Prepared Statements (PDO) gegen SQL-Injections.
- **Audit-Log:** Nachverfolgung aller sicherheitsrelevanten Aktionen.
- **Compliance:** DSGVO-Tools für Cookie-Consent und Datenauskunft.

### ⚡ Performance
- **Caching:** Mehrstufiges System (Page Cache, Object Cache, Browser Cache).
- **Optimierung:** Minifizierung von Assets und Lazy Loading für Bilder.
- **Diagnose:** Echtzeit-Monitoring von PHP- und Datenbank-Metriken.

---

## 📋 Systemanforderungen

Um 365CMS betreiben zu können, muss Ihr Server folgende Voraussetzungen erfüllen:

- **PHP:** 8.3+ (Strict Types, Return Types)
- **Datenbank:** MySQL 5.7+ oder MariaDB 10.3+
- **Webserver:** Apache 2.4+ (mit mod_rewrite) oder Nginx
- **PHP Extensions:** `pdo`, `pdo_mysql`, `mbstring`, `json`, `gd` (für Bilder), `zip` (für Updates)
- **Speicher:** Min. 256MB RAM (empfohlen 512MB)
- **Festplatte:** Min. 100MB für Core + Speicherplatz für Uploads

---

## 🔧 Installation

### 1. Dateien hochladen
Laden Sie alle Dateien (ausgenommen `.git` Verzeichnisse) in das Web-Verzeichnis Ihres Servers (z.B. `/var/www/html/` oder `htdocs/`).

### 2. Datenbank vorbereiten
Erstellen Sie eine neue MySQL/MariaDB Datenbank und einen Benutzer:
```sql
CREATE DATABASE cms_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cms_user'@'localhost' IDENTIFIED BY 'sicheres_passwort';
GRANT ALL PRIVILEGES ON cms_v2.* TO 'cms_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Konfiguration
Kopieren Sie die `config.sample.php` zu `config.php` und passen Sie die Werte an:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cms_v2');
define('DB_USER', 'cms_user');
define('DB_PASS', 'sicheres_passwort');
define('SITE_URL', 'https://ihre-domain.de'); // Ohne Trailing Slash
```
**Wichtig:** Generieren Sie neue Security Keys für `AUTH_KEY`, `SECURE_AUTH_KEY` und `NONCE_KEY`.

### 4. Berechtigungen setzen
Stellen Sie sicher, dass der Webserver Schreibrechte auf folgende Verzeichnisse hat:
- `/uploads/`
- `/cache/`
- `/logs/`
- `config.php` (nur während der Installation, danach 644)

### 5. Installer ausführen
Rufen Sie `https://ihre-domain.de/install.php` im Browser auf. Der Installer führt Sie durch:
- Erstellung der Datenbank-Tabellen.
- Einrichtung des Admin-Accounts (`admin` / `admin123`).
- Grundeinstellungen.

### 6. Abschluss
Löschen Sie die Datei `install.php` vom Server und ändern Sie sofort das Admin-Passwort!

---

## 🚀 Erste Schritte

Nach der Installation empfehlen wir folgenden Workflow:

1.  **Login:** Melden Sie sich unter `/login` oder `/admin` an.
2.  **Sicherheit:** Ändern Sie das Passwort und prüfen Sie den Status unter **Admin > System > Security**.
3.  **Einstellungen:** Konfigurieren Sie Titel, Zeitzone und E-Mail unter **Admin > Einstellungen**.
4.  **Design:** Passen Sie das Aussehen Ihrer Website im **Theme Editor** an.
5.  **Inhalte:** Erstellen Sie erste Seiten und Menüs.

---

## 📚 Dokumentation

Die vollständige Dokumentation finden Sie im Ordner `/doc`.

- **[INDEX.md](doc/INDEX.md):** Übersicht aller Dokumentationen.
- **Admin-Handbuch:** [docs/admin/README.md](doc/admin/README.md)
  - [Analytics](doc/admin/analytics/README.md)
  - [Content Management](doc/admin/content/README.md)
  - [Benutzerverwaltung](doc/admin/users/README.md)
  - [System & Tools](doc/admin/system/README.md)
- **Entwickler-Doku:**
  - [Architektur](doc/core/ARCHITECTURE.md)
  - [Datenbank-Schema](doc/core/DATABASE-SCHEMA.md)
  - [Plugin-Entwicklung](doc/plugins/PLUGIN-DEVELOPMENT.md)
  - [Theme-Entwicklung](doc/theme/DEVELOPMENT.md)
- **Mitgliederbereich:** [docs/member/README.md](doc/member/README.md)

---

## 📁 Verzeichnisstruktur

```
365CMS/
├── admin/                  # Backend-Controller & Views
├── assets/                 # Öffentliche Ressourcen (CSS, JS, Bilder)
├── cache/                  # Temporäre Dateien (System-generiert)
├── config/                 # Hilfs-Konfigurationen
├── core/                   # System-Kern (Klassen, Services)
├── doc/                    # Projektdokumentation
├── includes/               # Globale Funktionen & Helper
├── logs/                   # System-Logs und Fehlerprotokolle
├── member/                 # Frontend-Dashboard für Mitglieder
├── plugins/                # Erweiterungen (Modular)
├── themes/                 # Frontend-Templates
├── uploads/                # Benutzer-Dateien
├── config.php              # Hauptkonfiguration
├── index.php               # Frontend Bootstrapper
└── install.php             # Installer (nach Setup löschen!)
```

---

## 🛠️ Tech Stack

- **Backend:** PHP 8.3 (Strict Typing)
- **Datenbank:** MySQL / MariaDB (PDO)
- **Frontend:** HTML5, CSS3 (Custom Properties), Vanilla JS (ES6+)
- **Architektur:** MVC-ähnlich, Event-Driven (Hooks)
- **Abhängigkeiten:** Keine externen PHP-Bibliotheken (Zero-Dependency Core)
