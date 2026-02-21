# 365CMS – Installations-Anleitung

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026

Diese Anleitung führt euch Schritt für Schritt durch die Installation des 365CMS – von den Voraussetzungen bis zum ersten Login.

---

## Inhaltsverzeichnis

1. [System-Voraussetzungen](#1-system-voraussetzungen)
2. [Dateien hochladen](#2-dateien-hochladen)
3. [Datenbank anlegen](#3-datenbank-anlegen)
4. [config.php anpassen](#4-configphp-anpassen)
5. [Webserver konfigurieren](#5-webserver-konfigurieren)
6. [Erster Start](#6-erster-start)
7. [Produktions-Checkliste](#7-produktions-checkliste)
8. [Troubleshooting](#8-troubleshooting)

---

## 1. System-Voraussetzungen

| Komponente | Minimum | Empfohlen |
|------------|---------|-----------|
| **PHP** | 8.1 | 8.3+ |
| **MySQL** | 8.0 | 8.0+ |
| **MariaDB** | 10.6 | 10.11+ |
| **Webserver** | Apache 2.4 / Nginx 1.18 | latest |
| **PHP-Extensions** | pdo_mysql, mbstring, json, openssl | + curl, gd |
| **Speicher** | 128 MB RAM | 256 MB+ |
| **Festplatte** | 100 MB | 1 GB+ (inkl. Uploads) |

**PHP-Extensions prüfen:**
```bash
php -m | grep -E "pdo|mbstring|json|openssl|curl|gd"
```

---

## 2. Dateien hochladen

### Option A: Git (empfohlen)
```bash
git clone https://github.com/PS-easyIT/365CMS.DE.git /var/www/html/cms
cd /var/www/html/cms
```

### Option B: FTP
1. Alle Dateien aus dem `CMS/`-Ordner in euer Webroot hochladen
2. Rechte setzen:
   ```bash
   chmod 755 /var/www/html/cms
   chmod 644 /var/www/html/cms/config.php
   chmod 777 /var/www/html/cms/uploads
   chmod 777 /var/www/html/cms/cache
   chmod 777 /var/www/html/cms/logs
   ```

**⚠️ Wichtig:** Die `config.php` enthält Datenbank-Zugangsdaten – **niemals** im Web-Root ohne `.htaccess`-Schutz!

---

## 3. Datenbank anlegen

```sql
CREATE DATABASE cms365 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cms365user'@'localhost' IDENTIFIED BY 'SICHERES_PASSWORT';
GRANT ALL PRIVILEGES ON cms365.* TO 'cms365user'@'localhost';
FLUSH PRIVILEGES;
```

Die Tabellen werden **automatisch** beim ersten Start über `Database::createTables()` angelegt.

---

## 4. config.php anpassen

Öffnet `CMS/config.php` und passt folgende Werte an:

```php
// Datenbank
define('DB_HOST', 'localhost');       // DB-Server
define('DB_NAME', 'cms365');          // Datenbankname
define('DB_USER', 'cms365user');      // DB-Benutzer
define('DB_PASS', 'SICHERES_PASSWORT'); // DB-Passwort

// Website
define('SITE_NAME', 'Meine Website');
define('SITE_URL',  'https://meine-domain.de'); // Kein Trailing Slash!
define('ADMIN_EMAIL', 'admin@meine-domain.de');

// Sicherheitsschlüssel – UNBEDINGT ÄNDERN!
define('AUTH_KEY',         'zufaelliger-string-min-32-zeichen');
define('SECURE_AUTH_KEY',  'zufaelliger-string-min-32-zeichen');
define('NONCE_KEY',        'zufaelliger-string-min-32-zeichen');

// Produktionsmodus
define('CMS_DEBUG', false); // Auf false setzen für Produktion!
```

**Sicherheitsschlüssel generieren:**
```php
echo bin2hex(random_bytes(32));
```

---

## 5. Webserver konfigurieren

### Apache (`.htaccess` bereits enthalten)

Die Datei `CMS/.htaccess` ist bereits konfiguriert. Stellt sicher, dass `mod_rewrite` aktiviert ist:

```bash
a2enmod rewrite
systemctl restart apache2
```

**Wichtige Apache-Direktiven:**
```apache
AllowOverride All
Options -Indexes
```

### Nginx

```nginx
server {
    listen 80;
    server_name meine-domain.de;
    root /var/www/html/cms;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Logs-Ordner schützen
    location ~ ^/(logs|cache|config)/ {
        deny all;
    }
}
```

---

## 6. Erster Start

1. Browser öffnen: `https://meine-domain.de`
2. CMS erkennt die fehlende Installation und leitet zu `/install.php` weiter
3. Installationsassistent ausfüllen:
   - Admin-Benutzername
   - Admin-Passwort (mind. 12 Zeichen)
   - Admin-E-Mail
4. Nach erfolgreicher Installation: `/install.php` löschen!
5. Admin-Login: `https://meine-domain.de/admin`

---

## 7. Produktions-Checkliste

Vor dem Live-Gang folgendes prüfen:

- [ ] `CMS_DEBUG` auf `false` gesetzt
- [ ] Sicherheitsschlüssel in `config.php` geändert
- [ ] `install.php` gelöscht
- [ ] HTTPS aktiviert (SSL-Zertifikat)
- [ ] `logs/`, `cache/`, `uploads/` außerhalb des Web-Roots oder via `.htaccess` gesperrt
- [ ] Regelmäßige Backups eingerichtet (→ Admin > Backup)
- [ ] Starke Passwörter für alle Admin-Konten
- [ ] Dateiberechtigungen korrekt (keine 777 in Produktion außer uploads/cache)
- [ ] `config.php` nicht über Browser aufrufbar

---

## 8. Troubleshooting

### "500 Internal Server Error"
```bash
# PHP-Fehlerlog prüfen
tail -f /var/log/apache2/error.log
# oder
cat /var/www/html/cms/logs/error.log
```

Häufige Ursachen:
- PHP-Extension fehlt (pdo_mysql!)
- Dateiberechtigungen falsch
- `.htaccess` nicht unterstützt (Apache: AllowOverride All)

### "Database connection failed"
- DB-Zugangsdaten in `config.php` prüfen
- MySQL-Dienst läuft? `systemctl status mysql`
- DB-Benutzer hat Rechte? `SHOW GRANTS FOR 'user'@'localhost';`

### "Page not found" für alle Seiten außer Startseite
- `mod_rewrite` aktivieren (Apache)
- Nginx: `try_files`-Direktive prüfen
- `.htaccess`-Datei vorhanden?

### Leere Seiten (White Screen)
- `CMS_DEBUG` temporär auf `true` setzen
- PHP-Fehler-Ausgabe: `ini_set('display_errors', 1)` in `config.php`

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
