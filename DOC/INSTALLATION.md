# CMSv2 - Installation & Einrichtung

Vollständige Anleitung zur Installation des CMSv2.

## 📋 System-Anforderungen

### Minimum
- **PHP:** 8.0 oder höher
- **MySQL/MariaDB:** 5.7+ / 10.2+
- **Webserver:** Apache 2.4+ oder Nginx
- **PHP-Extensions:**
  - PDO
  - pdo_mysql
  - mbstring
  - session
- **Apache-Module:**
  - mod_rewrite
  - mod_headers (empfohlen)

### Empfohlen
- **PHP:** 8.3
- **MySQL:** 8.0+
- **RAM:** 512 MB
- **Festplatte:** 100 MB freier Speicher
- **PHP Memory Limit:** 128 MB

## 📦 Download & Upload

### 1. Dateien hochladen

Laden Sie alle Dateien auf Ihren Webserver hoch:

```
/ihr-verzeichnis/
├── core/
├── admin/
├── member/
├── themes/
├── plugins/
├── assets/
├── includes/
├── uploads/
├── index.php
├── config.php
├── .htaccess
└── install.php
```

### 2. Berechtigungen setzen

**Linux/Unix:**
```bash
# Standard-Berechtigungen
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Uploads-Verzeichnis beschreibbar
chmod 775 uploads/
```

**Wichtig:** Das `uploads/` Verzeichnis muss beschreibbar sein!

## 🔧 Vorbereitung

### 1. Datenbank erstellen

Erstellen Sie eine neue MySQL-Datenbank:

```sql
CREATE DATABASE cms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Erstellen Sie einen Datenbank-Benutzer:

```sql
CREATE USER 'cms_user'@'localhost' IDENTIFIED BY 'sicheres_passwort';
GRANT ALL PRIVILEGES ON cms_db.* TO 'cms_user'@'localhost';
FLUSH PRIVILEGES;
```

### 2. .htaccess prüfen

Stellen Sie sicher, dass `.htaccess` hochgeladen wurde und `mod_rewrite` aktiv ist.

**Apache-Konfiguration testen:**
```bash
# mod_rewrite aktiv?
apache2ctl -M | grep rewrite
```

Wenn nicht aktiv:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## 🚀 Installation durchführen

CMSv2 verfügt über einen **intelligenten Installer**, der Sie durch die komplette Einrichtung führt.

### Installation starten

1. **Öffnen Sie im Browser:** `https://ihre-domain.de/install.php`

Der Installer führt Sie durch 4 Schritte:

### Schritt 1: System-Check

Der Installer überprüft automatisch:
- ✅ PHP Version (min. 8.0)
- ✅ MySQL/PDO Extension
- ✅ Schreibrechte im Verzeichnis
- 🌐 **Automatische Domain-Erkennung**

**Wichtig:** Die Domain wird automatisch erkannt. Das System läuft **NIEMALS in einem Unterverzeichnis**!

### Schritt 2: Datenbank-Konfiguration

Geben Sie Ihre Datenbank-Zugangsdaten ein:

| Feld | Beschreibung | Beispiel |
|------|--------------|----------|
| **Datenbank-Host** | Meist "localhost" | `localhost` |
| **Datenbank-Name** | Ihre Datenbank | `cms_db` |
| **Datenbank-Benutzer** | DB-Username | `cms_user` |
| **Datenbank-Passwort** | DB-Passwort | `sicheres_passwort` |

Der Installer testet die Verbindung, bevor Sie fortfahren können.

### Schritt 3: Site-Konfiguration

Konfigurieren Sie Ihre Website:

| Feld | Beschreibung | Beispiel |
|------|--------------|----------|
| **Site-Name** | Name Ihrer Website | `IT Expert Network` |
| **Site-URL** | **Automatisch erkannt!** | `https://ihre-domain.de` |
| **Admin E-Mail** | Ihre E-Mail-Adresse | `admin@ihre-domain.de` |
| **Debug-Modus** | Nur für Entwicklung | ☐ Aktivieren |

**Hinweis:** Die URL wurde automatisch erkannt und sollte korrekt sein. Falls nicht, können Sie sie manuell korrigieren.

### Schritt 4: Administrator-Account

Erstellen Sie Ihren Admin-Account:

| Feld | Anforderung |
|------|-------------|
| **Benutzername** | Min. 4 Zeichen |
| **E-Mail** | Gültige E-Mail-Adresse |
| **Passwort** | Min. 8 Zeichen |
| **Bestätigung** | Muss übereinstimmen |

**WICHTIG:** Notieren Sie sich diese Zugangsdaten!

### Installation abschließen

Nach Klick auf "Installation starten" werden automatisch:

1. ✅ **config.php erstellt** mit allen Einstellungen
2. ✅ **Security Keys generiert** (64 Zeichen, kryptographisch sicher)
3. ✅ **Datenbank-Tabellen erstellt** (5 Core-Tabellen)
4. ✅ **Admin-User angelegt** mit Ihren Zugangsdaten
5. ✅ **Standard-Einstellungen** gesetzt

### Was passiert automatisch?

**config.php wird generiert mit:**
- Datenbank-Zugangsdaten
- Automatisch generierten Security Keys (`bin2hex(random_bytes(32))`)
- Automatisch erkannter Site-URL (KEIN Unterverzeichnis!)
- Debug-Modus-Einstellung
- Timezone (Europe/Berlin)

**5 Datenbank-Tabellen werden erstellt:**
- `cms_users` - Benutzer-Accounts
- `cms_user_meta` - Benutzer-Metadaten
- `cms_settings` - System-Einstellungen
- `cms_sessions` - Session-Management
- `cms_login_attempts` - Brute-Force-Schutz

**Alte config.php wird gesichert:**
Falls eine config.php bereits existiert, wird ein Backup erstellt:
```
config.php.backup.2026-01-15_14-30-45
```

## 👤 Erster Login

Nach erfolgreicher Installation:

1. **Öffnen Sie:** `https://ihre-domain.de/login`
2. **Anmeldedaten:** Die von Ihnen in Schritt 4 festgelegten
3. **Zugriff auf:**
   - Frontend: `https://ihre-domain.de/`
   - Admin: `https://ihre-domain.de/admin`
   - Member: `https://ihre-domain.de/member`

## 🔒 Sicherheits-Checkliste

Nach der Installation **ZWINGEND** durchführen:

### Sofort nach Installation

- [ ] **`install.php` LÖSCHEN!**
  ```bash
  rm install.php
  ```
  **Kritisch:** Diese Datei ermöglicht jedem die Neuinstallation!

- [ ] **Debug-Modus deaktivieren (Production)**
  - In config.php: `define('CMS_DEBUG', false);`
  - Fehlermeldungen werden dann in `logs/error.log` geschrieben

- [ ] **HTTPS aktivieren**
  - SSL-Zertifikat installieren (z.B. Let's Encrypt)
  - HTTP → HTTPS Redirect in `.htaccess` aktivieren

### Innerhalb der ersten Stunde

- [ ] **Backup-Strategie einrichten**
  - Datenbank: tägliche Backups
  - Dateien: wöchentliche Backups
  - `uploads/` Verzeichnis besonders wichtig

- [ ] **Dateiberechtigungen prüfen**
  ```bash
  # Dateien
  find . -type f -exec chmod 644 {} \;
  
  # Verzeichnisse
  find . -type d -exec chmod 755 {} \;
  
  # uploads/ beschreibbar
  chmod 775 uploads/
  ```

- [ ] **Admin-E-Mail bestätigen**
  - Prüfen Sie, ob die E-Mail-Adresse korrekt ist
  - Test-E-Mail senden

### Innerhalb der ersten Woche

- [ ] **Security Headers aktivieren** (siehe Nginx/Apache-Konfiguration unten)
- [ ] **Fehlerlog-Monitoring einrichten**
- [ ] **Regelmäßige Updates planen**
- [ ] **Firewall-Regeln prüfen**

## 📊 Installation überprüfen

### 1. Frontend testen
```
✓ Homepage:      https://ihre-domain.de/
✓ Login:         https://ihre-domain.de/login
✓ Registrierung: https://ihre-domain.de/register
✓ Logout:        https://ihre-domain.de/logout
```

### 2. Admin-Bereich testen
```
✓ Dashboard: https://ihre-domain.de/admin
✓ Plugins:   https://ihre-domain.de/admin/plugins
✓ Themes:    https://ihre-domain.de/admin/themes
✓ Users:     https://ihre-domain.de/admin/users
✓ Settings:  https://ihre-domain.de/admin/settings
```

### 3. Member-Bereich testen
```
✓ Dashboard: https://ihre-domain.de/member
```

### 4. Datenbank-Tabellen prüfen

```sql
SHOW TABLES LIKE 'cms_%';
```

Sollte anzeigen:
- `cms_users`
- `cms_user_meta`
- `cms_settings`
- `cms_sessions`
- `cms_login_attempts`

## 🌐 Webserver-Konfiguration

### Apache

Stellen Sie sicher, dass `.htaccess` funktioniert:

**httpd.conf / apache2.conf:**
```apache
<Directory /var/www/html>
    AllowOverride All
    Require all granted
</Directory>
```

### Nginx

Falls Sie Nginx verwenden, hier die Konfiguration:

```nginx
server {
    listen 80;
    server_name ihre-domain.de;
    root /var/www/html;
    index index.php;

    # Clean URLs
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Sicherheit
    location ~ /\. {
        deny all;
    }

    location ~* ^/uploads/.*\.php$ {
        deny all;
    }
}
```

## 🐛 Troubleshooting

### Problem: Weiße Seite

**Lösung:**
1. PHP Error Logs prüfen
2. `CMS_DEBUG` auf `true` setzen
3. PHP-Version prüfen (min. 8.0)

### Problem: 404 bei allen Seiten

**Lösung:**
1. `.htaccess` vorhanden?
2. `mod_rewrite` aktiviert?
3. `AllowOverride All` in Apache-Config?

### Problem: Datenbank-Verbindung fehlgeschlagen

**Lösung:**
1. Datenbank-Credentials in `config.php` prüfen
2. MySQL-Service läuft?
3. User hat Rechte auf Datenbank?

### Problem: "Headers already sent"

**Lösung:**
1. Keine Ausgabe vor `<?php` Tags
2. UTF-8 ohne BOM speichern
3. Whitespace am Ende von Dateien entfernen

### Problem: Plugin aktiviert sich nicht

**Lösung:**
1. Plugin-Verzeichnis mit Hauptdatei identisch?
2. Plugin-Header vorhanden?
3. PHP-Fehler im Plugin-Code?

### Problem: Uploads funktionieren nicht

**Lösung:**
```bash
# Berechtigungen setzen
chmod 775 uploads/
chown www-data:www-data uploads/

# SELinux (falls aktiv)
chcon -R -t httpd_sys_rw_content_t uploads/
```

## 📊 Performance-Optimierung

### PHP-Konfiguration

**php.ini:**
```ini
memory_limit = 128M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
max_input_time = 300
```

### Caching aktivieren

**OPcache aktivieren:**
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

### Datenbank-Optimierung

```sql
-- Indizes prüfen
SHOW INDEX FROM cms_users;

-- Langsame Queries loggen
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
```

## 🔄 Updates durchführen

### 1. Backup erstellen

```bash
# Dateien
tar -czf cms_backup_$(date +%Y%m%d).tar.gz /pfad/zum/cms/

# Datenbank
mysqldump -u cms_user -p cms_db > cms_backup_$(date +%Y%m%d).sql
```

### 2. Neue Dateien hochladen

- Überschreiben Sie **NICHT** `config.php`
- Überschreiben Sie **NICHT** `uploads/`

### 3. Datenbank-Updates

Falls erforderlich, führen Sie DB-Migrations-Skripte aus.

## 📞 Support

Bei Problemen:

1. Überprüfen Sie die Logs
2. Konsultieren Sie die Dokumentation
3. Prüfen Sie bekannte Issues
4. Erstellen Sie ein Backup vor Änderungen

## ✨ Nächste Schritte

Nach erfolgreicher Installation:

1. **Inhalte anpassen**
   - Site-Namen in Einstellungen ändern
   - Theme anpassen oder neues installieren
   
2. **Plugins installieren**
   - Siehe [PLUGIN-DEVELOPMENT.md](PLUGIN-DEVELOPMENT.md)
   
3. **Theme anpassen**
   - Siehe [THEME-DEVELOPMENT.md](THEME-DEVELOPMENT.md)
   
4. **Backups einrichten**
   - Automatische tägliche Backups
   - Offsite-Speicherung

5. **Monitoring einrichten**
   - Uptime-Monitoring
   - Error-Logging
   - Performance-Tracking
