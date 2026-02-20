# CMSv2 Datenbank-Schema

Vollständige Übersicht aller Datenbank-Tabellen des Content Management Systems.

**Präfix:** `cms_` (konfigurierbar in `config.php`)  
**Engine:** InnoDB  
**Charset:** utf8mb4

---

## 📊 Tabellen-Übersicht

| Nr. | Tabelle | Zweck | Primärschlüssel | Fremdschlüssel |
|-----|---------|-------|-----------------|----------------|
| 1 | users | Benutzerverwaltung | id | - |
| 2 | user_meta | Benutzer-Metadaten | id | user_id → users.id |
| 3 | roles | Rollen & Berechtigungen | id | - |
| 4 | settings | System-Einstellungen | id | - |
| 5 | sessions | Session-Management | id (VARCHAR) | user_id → users.id |
| 6 | login_attempts | Login-Tracking | id | - |
| 7 | blocked_ips | IP-Blockliste | id | - |
| 8 | activity_log | Aktivitätsprotokolle | id | user_id → users.id |
| 9 | pages | Seiten-Content | id | author_id → users.id |
| 10 | page_revisions | Seiten-Versionen | id | page_id → pages.id |
| 11 | cache | Cache-Speicher | id | - |
| 12 | failed_logins | Fehlversuche | id | - |
| 13 | landing_sections | Landing-Page Bereiche | id | - |
| 14 | media | Media-Bibliothek | id | uploaded_by → users.id |
| 15 | plugins | Plugin-Management | id | - |
| 16 | plugin_meta | Plugin-Metadaten | id | plugin_id → plugins.id |
| 17 | theme_customizations | Theme-Einstellungen | id | user_id → users.id |
| 18 | page_views | Analytics-Tracking | id | page_id, user_id |

---

## 🔐 Benutzer & Authentifizierung

### cms_users
Zentrale Benutzerverwaltung.

```sql
CREATE TABLE cms_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,          -- bcrypt hash
    display_name VARCHAR(100) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'member',  -- admin, editor, member
    status VARCHAR(20) NOT NULL DEFAULT 'active', -- active, suspended, deleted
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Rollen:**
- `admin` - Vollzugriff auf System
- `editor` - Content-Verwaltung
- `member` - Grundlegende Rechte

### cms_user_meta
Erweiterbare Benutzer-Metadaten (Key-Value Store).

```sql
CREATE TABLE cms_user_meta (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    meta_key VARCHAR(255) NOT NULL,
    meta_value LONGTEXT,
    INDEX idx_user_id (user_id),
    INDEX idx_meta_key (meta_key),
    FOREIGN KEY (user_id) REFERENCES cms_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Beispiel Meta-Keys:**
- `phone_number`
- `company`
- `avatar_url`
- `notification_settings` (JSON)

### cms_roles
Rollen-System mit JSON-basierten Capabilities.

```sql
CREATE TABLE cms_roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    capabilities TEXT COMMENT 'JSON-Array mit Berechtigungen',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Capabilities Beispiel:**
```json
{
    "edit_pages": true,
    "delete_pages": true,
    "manage_users": false,
    "manage_plugins": false
}
```

---

## 🔒 Sicherheit & Sessions

### cms_sessions
Sichere Session-Verwaltung.

```sql
CREATE TABLE cms_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT UNSIGNED,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    payload TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### cms_login_attempts
Bruteforce-Schutz via Rate-Limiting.

```sql
CREATE TABLE cms_login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60),
    ip_address VARCHAR(45),
    success TINYINT(1) DEFAULT 0,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_ip (ip_address),
    INDEX idx_time (attempted_at),
    INDEX idx_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### cms_blocked_ips
IP-Blockliste (temporär oder permanent).

```sql
CREATE TABLE cms_blocked_ips (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    reason VARCHAR(255),
    expires_at DATETIME,
    permanent TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### cms_failed_logins
Detailliertes Failed-Login Tracking.

```sql
CREATE TABLE cms_failed_logins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60),
    ip_address VARCHAR(45),
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_agent VARCHAR(255),
    INDEX idx_username (username),
    INDEX idx_ip (ip_address),
    INDEX idx_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### cms_activity_log
Audit-Log für alle System-Aktivitäten.

```sql
CREATE TABLE cms_activity_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    action VARCHAR(100) NOT NULL,            -- login, logout, create_page, etc.
    entity_type VARCHAR(100),                -- page, user, plugin, etc.
    entity_id BIGINT UNSIGNED,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    metadata LONGTEXT COMMENT 'JSON-Daten',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity_type (entity_type),
    INDEX idx_entity_id (entity_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 📝 Content Management

### cms_pages
Seiten-Content mit Status-Workflow.

```sql
CREATE TABLE cms_pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(200) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT,
    excerpt TEXT,
    status VARCHAR(20) DEFAULT 'draft',      -- draft, published, scheduled
    author_id INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_author (author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### cms_page_revisions
Versionierung für Seiten (Rollback-Funktion).

```sql
CREATE TABLE cms_page_revisions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT,
    excerpt TEXT,
    author_id INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page_id (page_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (page_id) REFERENCES cms_pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### cms_media
Media-Bibliothek (Bilder, PDFs, etc.).

```sql
CREATE TABLE cms_media (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    filetype VARCHAR(50),                    -- image/jpeg, application/pdf, etc.
    filesize INT UNSIGNED,                   -- Bytes
    title VARCHAR(255),
    alt_text VARCHAR(255),
    caption TEXT,
    uploaded_by INT UNSIGNED,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (filetype),
    INDEX idx_uploader (uploaded_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## ⚙️ System & Konfiguration

### cms_settings
Globale System-Einstellungen (Key-Value).

```sql
CREATE TABLE cms_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    option_name VARCHAR(191) NOT NULL UNIQUE,
    option_value LONGTEXT,
    autoload TINYINT(1) DEFAULT 1,           -- 1 = Beim Start laden
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_option_name (option_name),
    INDEX idx_autoload (autoload)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Standard-Settings:**
- `site_title`
- `site_tagline`
- `admin_email`
- `timezone`
- `active_theme`
- `active_plugins` (JSON-Array)

### cms_cache
Datenbank-basierter Cache.

```sql
CREATE TABLE cms_cache (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(191) NOT NULL UNIQUE,
    cache_value LONGTEXT,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_key (cache_key),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🔌 Plugins & Themes

### cms_plugins
Plugin-Management.

```sql
CREATE TABLE cms_plugins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    version VARCHAR(20) NOT NULL,
    author VARCHAR(100),
    description TEXT,
    plugin_path VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 0,
    auto_update TINYINT(1) DEFAULT 0,
    settings LONGTEXT COMMENT 'JSON-Daten für Plugin-Einstellungen',
    installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    activated_at TIMESTAMP NULL,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### cms_plugin_meta
Plugin-Metadaten.

```sql
CREATE TABLE cms_plugin_meta (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plugin_id INT UNSIGNED NOT NULL,
    meta_key VARCHAR(255) NOT NULL,
    meta_value LONGTEXT,
    INDEX idx_plugin_id (plugin_id),
    INDEX idx_meta_key (meta_key),
    FOREIGN KEY (plugin_id) REFERENCES cms_plugins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### cms_theme_customizations
Theme-Anpassungen (Colors, Typography, etc.).

```sql
CREATE TABLE cms_theme_customizations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    theme_slug VARCHAR(100) NOT NULL,
    setting_category VARCHAR(100) NOT NULL,  -- colors, typography, layout
    setting_key VARCHAR(255) NOT NULL,
    setting_value LONGTEXT,
    user_id INT UNSIGNED NULL,               -- Optional: User-spezifisch
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_theme_slug (theme_slug),
    INDEX idx_category (setting_category),
    INDEX idx_key (setting_key),
    INDEX idx_user_id (user_id),
    UNIQUE KEY unique_theme_setting (theme_slug, setting_category, setting_key, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🏠 Landing Page

### cms_landing_sections
Dynamische Landing-Page Sektionen.

```sql
CREATE TABLE cms_landing_sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,               -- header, feature, cta, footer
    data TEXT,                               -- JSON-Daten
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Section Types:**
- `header` - Hero-Bereich
- `feature` - Feature-Grid (12 Features)
- `cta` - Call-to-Action
- `footer` - Footer-Links

---

## 📊 Analytics & Tracking

### cms_page_views
Detailliertes Tracking für Analytics.

```sql
CREATE TABLE cms_page_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id INT UNSIGNED NULL,
    page_slug VARCHAR(200),
    page_title VARCHAR(255),
    user_id INT UNSIGNED NULL,
    session_id VARCHAR(128),
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    referrer VARCHAR(500),
    visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page_id (page_id),
    INDEX idx_page_slug (page_slug),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_visited_at (visited_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Verwendung:**
- Seitenaufrufe tracken
- Unique Visitors berechnen
- Top-Seiten ermitteln
- Bounce-Rate analysieren

---

## 🔄 Wartung & Best Practices

### Auto-Cleanup Empfehlungen

**Täglich:**
- Abgelaufene Cache-Einträge löschen
- Alte Sessions entfernen (> 30 Tage)

**Wöchentlich:**
- Login-Attempts älter als 7 Tage
- Failed-Logins älter als 30 Tage

**Monatlich:**
- Activity-Log älter als 90 Tage archivieren
- Page-Views aggregieren (monatliche Statistiken)

### Index-Optimierung

Alle Tabellen haben relevante Indizes für:
- Primärschlüssel (id)
- Fremdschlüssel (CASCADE bei Löschung)
- Häufig verwendete WHERE-Bedingungen
- Sortierungs-Spalten (created_at, sort_order)

### Backup-Strategie

**BackupService erstellt:**
1. SQL-Dump aller Tabellen (GZIP komprimiert)
2. ZIP-Archiv von `uploads/`, `themes/`, `plugins/`
3. Manifest.json mit Metadaten

**Speicherorte:**
- Webspace: `ABSPATH/backups/`
- E-Mail: Nur Datenbank (komprimiert)
- S3: Vollständiges Backup

---

**Zuletzt aktualisiert:** 18. Februar 2026  
**Version:** CMSv2 2.0.0  
**Gesamt-Tabellen:** 18
