# 365CMS – Datenbank-Schema


Das 365CMS nutzt **22 Datenbank-Tabellen**, die beim ersten Start automatisch angelegt werden. Alle Tabellen haben das Prefix `cms_` (konfigurierbar in `config.php`).

---

## Inhaltsverzeichnis

1. [Tabellen-Übersicht](#1-tabellen-übersicht)
2. [Benutzer-System](#2-benutzer-system)
3. [Inhalts-System](#3-inhalts-system)
4. [Plugin & Theme System](#4-plugin--theme-system)
5. [Abo-System](#5-abo-system)
6. [System-Tabellen](#6-system-tabellen)
7. [Beziehungen (ER-Diagramm)](#7-beziehungen-er-diagramm)
8. [Wichtige Queries](#8-wichtige-queries)

---

## 1. Tabellen-Übersicht

| Tabelle | Zeilen-Typ | Beschreibung |
|---------|------------|--------------|
| `cms_users` | Benutzer | Alle registrierten Nutzer |
| `cms_user_meta` | Metadaten | Zusätzliche Nutzerdaten (Key-Value) |
| `cms_roles` | Konfiguration | Benutzer-Rollen & Berechtigungen |
| `cms_sessions` | Laufzeit | Aktive Browser-Sessions |
| `cms_login_attempts` | Security | Login-Versuche für Rate-Limiting |
| `cms_pages` | Inhalt | Statische CMS-Seiten |
| `cms_page_revisions` | Archiv | Versionsverlauf von Seiten |
| `cms_posts` | Inhalt | Blog-Beiträge |
| `cms_post_categories` | Taxonomie | Blog-Kategorien |
| `cms_landing_sections` | Inhalt | Landing-Page Abschnitte |
| `cms_settings` | Konfiguration | System-Einstellungen (Options) |
| `cms_plugins` | Konfiguration | Plugin-Registry |
| `cms_plugin_meta` | Metadaten | Plugin-Einstellungen |
| `cms_theme_customizations` | Design | Theme-Anpassungen per DB |
| `cms_subscription_plans` | Abo | Verfügbare Abo-Pläne |
| `cms_user_subscriptions` | Abo | Benutzer-Abonnements |
| `cms_user_groups` | Gruppen | Nutzergruppen |
| `cms_user_group_members` | Relation | Gruppen-Mitgliedschaften |
| `cms_subscription_usage` | Tracking | Ressourcen-Nutzung pro User |
| `cms_orders` | E-Commerce | Bestellungen & Rechnungsdaten |
| `cms_activity_log` | Audit | Alle Aktionen mit IP-Logging |
| `cms_cache` | Performance | Datenbank-Cache-Einträge |

---

## 2. Benutzer-System

### `cms_users` – Haupt-Benutzertabelle

```sql
CREATE TABLE cms_users (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(60)  NOT NULL UNIQUE,
    email        VARCHAR(100) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,          -- bcrypt-Hash
    display_name VARCHAR(100) NOT NULL,
    role         VARCHAR(20)  NOT NULL DEFAULT 'member',  -- admin|editor|author|member
    status       VARCHAR(20)  NOT NULL DEFAULT 'active',  -- active|inactive|banned
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login   TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email    (email),
    INDEX idx_role     (role)
);
```

**Rollen:**
- `admin` – Vollzugriff auf alle Funktionen
- `editor` – Inhalte verwalten, keine System-Einstellungen
- `author` – Eigene Inhalte erstellen und bearbeiten
- `member` – Nur Mitglieder-Bereich

### `cms_user_meta` – Zusätzliche Nutzerdaten

```sql
CREATE TABLE cms_user_meta (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    meta_key   VARCHAR(255) NOT NULL,
    meta_value LONGTEXT,
    FOREIGN KEY (user_id) REFERENCES cms_users(id) ON DELETE CASCADE
);
```

**Häufige Meta-Keys:**
| Key | Beschreibung |
|-----|--------------|
| `first_name` | Vorname |
| `last_name` | Nachname |
| `phone` | Telefonnummer |
| `company` | Firma |
| `avatar` | Profilbild-Pfad |
| `bio` | Kurzbiographie |
| `website` | Webseite |
| `social_*` | Social-Media-Links |

### `cms_roles` – Rollen & Berechtigungen

```sql
CREATE TABLE cms_roles (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(50)  NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description  TEXT,
    capabilities TEXT COMMENT 'JSON-Array mit Berechtigungen',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 3. Inhalts-System

### `cms_pages` – Statische Seiten

```sql
CREATE TABLE cms_pages (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug         VARCHAR(200) NOT NULL UNIQUE,   -- URL: /ueber-uns
    title        VARCHAR(255) NOT NULL,
    content      LONGTEXT,
    excerpt      TEXT,
    status       VARCHAR(20)  DEFAULT 'draft',   -- draft|published|trash
    hide_title   TINYINT(1)   NOT NULL DEFAULT 0,
    author_id    INT UNSIGNED,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL
);
```

### `cms_posts` – Blog-Beiträge

```sql
CREATE TABLE cms_posts (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(255) NOT NULL,
    slug           VARCHAR(255) NOT NULL UNIQUE,
    content        LONGTEXT,
    excerpt        TEXT,
    featured_image VARCHAR(500),
    status         ENUM('draft','published','trash') NOT NULL DEFAULT 'draft',
    author_id      INT UNSIGNED NOT NULL,
    category_id    INT UNSIGNED DEFAULT NULL,
    tags           VARCHAR(500) COMMENT 'Kommagetrennte Tags',
    views          INT UNSIGNED DEFAULT 0,
    allow_comments TINYINT(1)   NOT NULL DEFAULT 1,
    meta_title     VARCHAR(255),
    meta_description TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    published_at   TIMESTAMP NULL
);
```

### `cms_landing_sections` – Landing-Page Abschnitte

```sql
CREATE TABLE cms_landing_sections (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type       VARCHAR(50) NOT NULL,   -- hero|features|cta|testimonials
    data       TEXT,                   -- JSON mit Abschnitt-Daten
    sort_order INT DEFAULT 0
);
```

---

## 4. Plugin & Theme System

### `cms_plugins` – Plugin-Registry

```sql
CREATE TABLE cms_plugins (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    slug        VARCHAR(100) NOT NULL UNIQUE,
    version     VARCHAR(20)  NOT NULL,
    author      VARCHAR(100),
    description TEXT,
    plugin_path VARCHAR(255) NOT NULL,
    is_active   TINYINT(1)   DEFAULT 0,
    auto_update TINYINT(1)   DEFAULT 0,
    settings    LONGTEXT COMMENT 'JSON-Einstellungen',
    installed_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activated_at  TIMESTAMP NULL
);
```

### `cms_theme_customizations` – Theme-Einstellungen

```sql
CREATE TABLE cms_theme_customizations (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    theme_slug       VARCHAR(100) NOT NULL,
    setting_category VARCHAR(100) NOT NULL,   -- colors|typography|layout
    setting_key      VARCHAR(255) NOT NULL,
    setting_value    LONGTEXT,
    user_id          INT UNSIGNED NULL,        -- NULL = global, sonst user-spezifisch
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_theme_setting (theme_slug, setting_category, setting_key, user_id)
);
```

---

## 5. Abo-System

### `cms_subscription_plans` – Abo-Pläne

```sql
CREATE TABLE cms_subscription_plans (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    slug            VARCHAR(100) NOT NULL UNIQUE,   -- free|starter|pro|enterprise
    description     TEXT,
    price_monthly   DECIMAL(10,2) DEFAULT 0.00,
    price_yearly    DECIMAL(10,2) DEFAULT 0.00,
    -- Limits (-1 = unbegrenzt)
    limit_experts   INT DEFAULT -1,
    limit_companies INT DEFAULT -1,
    limit_events    INT DEFAULT -1,
    limit_speakers  INT DEFAULT -1,
    limit_storage_mb INT DEFAULT 1000,
    -- Plugin-Zugriffe (Boolean)
    plugin_experts  BOOLEAN DEFAULT 1,
    plugin_companies BOOLEAN DEFAULT 1,
    plugin_events   BOOLEAN DEFAULT 1,
    plugin_speakers BOOLEAN DEFAULT 1,
    -- Features
    feature_analytics      BOOLEAN DEFAULT 0,
    feature_advanced_search BOOLEAN DEFAULT 0,
    feature_api_access     BOOLEAN DEFAULT 0,
    is_active   BOOLEAN DEFAULT 1,
    sort_order  INT     DEFAULT 0
);
```

### `cms_user_subscriptions` – Benutzer-Abonnements

```sql
CREATE TABLE cms_user_subscriptions (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT UNSIGNED NOT NULL,
    plan_id           INT NOT NULL,
    status            ENUM('active','cancelled','expired','trial','suspended') DEFAULT 'active',
    billing_cycle     ENUM('monthly','yearly','lifetime') DEFAULT 'monthly',
    start_date        DATETIME NOT NULL,
    end_date          DATETIME,
    next_billing_date DATETIME,
    cancelled_at      DATETIME
);
```

### `cms_orders` – Bestellungen

```sql
CREATE TABLE cms_orders (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(64)    NOT NULL UNIQUE,
    user_id      INT UNSIGNED   NULL,
    plan_id      INT            NOT NULL,
    status       ENUM('pending','confirmed','cancelled','refunded') DEFAULT 'pending',
    total_amount DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    currency     VARCHAR(3)     DEFAULT 'EUR',
    billing_cycle ENUM('monthly','yearly','lifetime') DEFAULT 'monthly',
    -- Rechnungsdaten
    forename VARCHAR(100),
    lastname VARCHAR(100),
    company  VARCHAR(100),
    email    VARCHAR(150),
    street   VARCHAR(255),
    zip      VARCHAR(20),
    city     VARCHAR(100),
    country  VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 6. System-Tabellen

### `cms_settings` – System-Einstellungen

```sql
CREATE TABLE cms_settings (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    option_name  VARCHAR(255) NOT NULL UNIQUE,
    option_value LONGTEXT,
    autoload     TINYINT(1) DEFAULT 1   -- 1 = beim Start laden
);
```

**Häufig genutzte Settings:**
| Key | Beschreibung |
|-----|--------------|
| `site_name` | Website-Name |
| `active_theme` | Aktives Theme-Slug |
| `active_plugins` | JSON der aktiven Plugin-Slugs |
| `color_primary` | Primärfarbe |
| `font_heading` | Überschriften-Font |

### `cms_activity_log` – Audit-Log

```sql
CREATE TABLE cms_activity_log (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED,
    action      VARCHAR(100)  NOT NULL,   -- user_login|page_updated|plugin_activated
    entity_type VARCHAR(100),
    entity_id   BIGINT UNSIGNED,
    description TEXT,
    ip_address  VARCHAR(45),
    user_agent  VARCHAR(500),
    metadata    LONGTEXT COMMENT 'JSON',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 7. Beziehungen (ER-Diagramm)

```
cms_users ──────────────── cms_user_meta
    │                            (user_id → users.id)
    │
    ├── cms_user_subscriptions ── cms_subscription_plans
    │        (user_id)                 (plan_id)
    │
    ├── cms_user_group_members ── cms_user_groups
    │        (user_id)                (group_id)
    │
    ├── cms_subscription_usage
    │        (user_id)
    │
    └── cms_orders
             (user_id)

cms_posts ─── cms_post_categories
cms_pages ─── cms_page_revisions
cms_plugins ── cms_plugin_meta
```

---

## 8. Wichtige Queries

### User mit Subscription laden

```php
$db = CMS\Database::instance();
$p = $db->getPrefix();

$user = $db->get_row(
    "SELECT u.*, sp.name as plan_name, us.status as sub_status
     FROM {$p}users u
     LEFT JOIN {$p}user_subscriptions us ON us.user_id = u.id AND us.status = 'active'
     LEFT JOIN {$p}subscription_plans sp ON sp.id = us.plan_id
     WHERE u.id = ?",
    [$userId]
);
```

### Alle aktiven Plugins

```php
$plugins = $db->get_results(
    "SELECT * FROM {$p}plugins WHERE is_active = 1 ORDER BY name",
    []
);
```

### Setting lesen / schreiben

```php
// Lesen
$value = $db->get_var(
    "SELECT option_value FROM {$p}settings WHERE option_name = ?",
    ['active_theme']
);

// Schreiben (INSERT OR UPDATE)
$db->query(
    "INSERT INTO {$p}settings (option_name, option_value) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)",
    ['active_theme', 'cms-dark']
);
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
