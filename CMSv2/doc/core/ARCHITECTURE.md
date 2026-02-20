# CMSv2 - System-Architektur

**Version:** 2.0.0  
**Datum:** 17. Februar 2026

## 📐 Architektur-Übersicht

CMSv2 ist ein modulares Content Management System basierend auf modernen Software-Design-Prinzipien und bewährten Architektur-Patterns.

## 🏗️ Architektur-Prinzipien

### 1. Modularität
- **Singleton-Pattern** für Core-Klassen
- **Plugin-basierte Erweiterungen** 
- **Theme-System** für Templates
- **Klare Trennung** von Verantwortlichkeiten

### 2. Sicherheit
- **Security-First Design** - Sicherheit in allen Schichten
- **Defense in Depth** - Mehrschichtige Absicherung
- **Least Privilege** - Minimale Berechtigungen

### 3. Performance
- **Lazy Loading** - Komponenten nur bei Bedarf laden
- **Optimierte Queries** - Prepared Statements mit Caching
- **Asset-Optimierung** - Komprimierung und Browser-Caching

### 4. Erweiterbarkeit
- **Hook-System** - Actions & Filters wie WordPress
- **Plugin-API** - Einfache Plugin-Entwicklung
- **Theme-API** - Flexible Template-Gestaltung

## 📊 System-Schichten

```
┌─────────────────────────────────────────────────┐
│           Presentation Layer                     │
│  (Themes, Templates, Frontend/Admin UI)         │
├─────────────────────────────────────────────────┤
│           Application Layer                      │
│  (Router, Hooks, PluginManager, ThemeManager)   │
├─────────────────────────────────────────────────┤
│           Business Logic Layer                   │
│  (Auth, Security, PageManager, API)             │
├─────────────────────────────────────────────────┤
│           Data Access Layer                      │
│  (Database, CacheManager)                       │
├─────────────────────────────────────────────────┤
│           Infrastructure Layer                   │
│  (Bootstrap, Configuration, Session)            │
└─────────────────────────────────────────────────┘
```

## 🔧 Core-Komponenten

### Bootstrap System
**Datei:** `core/Bootstrap.php`  
**Verantwortung:** System-Initialisierung und Orchestrierung

```php
CMS\Bootstrap
├── loadDependencies()    // Lädt alle Core-Klassen
├── initializeCore()      // Initialisiert Subsysteme
├── loadPlugins()         // Aktiviert Plugins
├── loadTheme()           // Aktiviert Theme
└── route()               // Dispatched Request
```

**Lifecycle:**
1. Autoloading konfigurieren
2. Konfiguration laden
3. Datenbank verbinden
4. Security initialisieren
5. Auth-System starten
6. Plugins laden
7. Theme aktivieren
8. Router ausführen

### Database Layer
**Datei:** `core/Database.php`  
**Verantwortung:** Datenbank-Abstraktion und -Sicherheit

**Features:**
- PDO-basierte Abstraktion
- Prepared Statements (100%)
- Auto-Installation von Tabellen
- Query-Logging (Debug-Mode)
- Transaktions-Support

**Schema:**
```
cms_users          → Benutzerverwaltung
cms_user_meta      → Flexible User-Daten
cms_settings       → System-Optionen
cms_sessions       → Session-Tracking
cms_login_attempts → Security-Logging
cms_pages          → Content-Management
cms_landing_sections → Landing-Page-Builder
cms_cache          → File-Cache Metadaten
```

### Security Layer
**Datei:** `core/Security.php`  
**Verantwortung:** Umfassender Schutz gegen Angriffe

**Implementierte Schutzmechanismen:**

1. **CSRF Protection**
   - Token-basierte Validierung
   - Session-gebundene Tokens
   - Action-spezifische Tokens

2. **XSS Prevention**
   - Input-Sanitization
   - Output-Escaping
   - Content Security Policy Headers

3. **SQL Injection Protection**
   - 100% Prepared Statements
   - Type-hinting
   - Parameter-Binding

4. **Rate Limiting**
   - Login-Versuche limitiert (5/15min)
   - Session-basiertes Tracking
   - Temporäres Account-Lock

5. **Security Headers**
   ```
   X-Frame-Options: SAMEORIGIN
   X-Content-Type-Options: nosniff
   X-XSS-Protection: 1; mode=block
   Referrer-Policy: strict-origin-when-cross-origin
   Content-Security-Policy: default-src 'self'
   Permissions-Policy: geolocation=(), microphone=(), camera=()
   ```

6. **Password Security**
   - BCrypt Hashing (Cost: 12)
   - Passwort-Komplexität (8+ Zeichen)
   - Sichere Random-Token-Generierung

7. **Session Security**
   - HTTP-Only Cookies
   - Session-Regeneration bei Login
   - Sichere Session-IDs

### Authentication System
**Datei:** `core/Auth.php`  
**Verantwortung:** Benutzer-Authentifizierung und -Autorisierung

**Komponenten:**
- Login-Validierung
- Registrierungs-Flow
- Session-Management
- Rollen-basierte Zugriffskontrolle (RBAC)

**Rollen-System:**
```php
Administrator → Voller Zugriff auf System
    ├── Plugin-Verwaltung
    ├── Theme-Verwaltung
    ├── Benutzer-Verwaltung
    └── System-Einstellungen

Member → Beschränkter Zugriff
    ├── Member-Dashboard
    ├── Eigenes Profil
    └── Plugin-spezifische Features
```

### Routing System
**Datei:** `core/Router.php`  
**Verantwortung:** URL-Handling und Request-Dispatching

**URL-Struktur:**
```
/                  → Homepage
/login             → Login-Seite
/register          → Registrierung
/member            → Member-Dashboard
/admin             → Admin-Dashboard
/admin/{page}      → Admin-Subseiten
/page/{slug}       → Dynamische Seiten
```

**Pattern-Matching:**
- Statische Routes (exakte Übereinstimmung)
- Dynamische Routes (Parameter-Extraktion)
- Fallback auf Theme-Templates
- 404-Handling

### Plugin System
**Datei:** `core/PluginManager.php`  
**Verantwortung:** Plugin-Lifecycle und Hook-Integration

**Plugin-Lifecycle:**
```
Scan → Detect → Parse Metadata → Activate → Load → Execute
```

**Plugin-Struktur:**
```
plugins/
└── example-plugin/
    ├── example-plugin.php     # Main Plugin File
    ├── includes/              # Classes
    ├── assets/               # CSS/JS
    └── templates/            # View Templates
```

**Plugin-Metadata:**
```php
/**
 * Plugin Name: Example Plugin
 * Description: Plugin description
 * Version: 1.0.0
 * Author: Author Name
 * Requires: 2.0.0
 */
```

### Hook System
**Datei:** `core/Hooks.php`  
**Verantwortung:** Event-driven Architektur

**Hook-Typen:**

1. **Actions** (void return)
   - Ausführung von Code an bestimmten Punkten
   - Mehrere Callbacks möglich
   - Prioritäts-basierte Reihenfolge

2. **Filters** (modifizierte return value)
   - Modifikation von Daten
   - Chainable callbacks
   - Prioritäts-basierte Reihenfolge

**Verfügbare Hooks:**
```php
// System Lifecycle
cms_init               → Nach System-Init
cms_before_route       → Vor Routing
cms_after_route        → Nach Routing

// Template Hooks
before_header          → Vor Header-Ausgabe
after_header           → Nach Header-Ausgabe
before_footer          → Vor Footer-Ausgabe
after_footer           → Nach Footer-Ausgabe
head                   → In <head>-Tag
body_start             → Nach <body>
body_end               → Vor </body>

// Content Hooks
home_content           → Homepage-Content
admin_dashboard_content → Admin-Dashboard
member_dashboard_content → Member-Dashboard

// Admin Hooks
admin_menu             → Admin-Menü-Erweiterung

// Filter Hooks
template_name          → Template-Name ändern
theme_color_*          → Theme-Farben anpassen
```

### Theme System
**Datei:** `core/ThemeManager.php`  
**Verantwortung:** Theme-Verwaltung und Template-Rendering

**Template-Hierarchie:**
```
Request → Router → ThemeManager → Template Selection → Render

Template-Suche:
1. Spezifisches Template (z.B. page-{slug}.php)
2. Generisches Template (z.B. page.php)
3. Fallback (home.php oder 404.php)
```

**Theme-Struktur:**
```
themes/default/
├── style.css          # Theme-Header & Styles
├── functions.php      # Theme-Funktionen
├── header.php         # Header-Template
├── footer.php         # Footer-Template
├── home.php           # Homepage
├── page.php           # Einzelseite
├── login.php          # Login-Seite
├── register.php       # Registrierung
├── 404.php            # Error-Seite
└── error.php          # Generic Error
```

### Cache System
**Datei:** `core/CacheManager.php`  
**Verantwortung:** Performance-Optimierung durch Caching

**Cache-Layer:**
1. **File-Cache** - Persistenter Datei-basierter Cache
2. **LiteSpeed Cache** - Integration mit LiteSpeed-Server
3. **Memory Cache** - Runtime-Cache für Requests

**Cache-Strategien:**
- Fragment-Caching (Teilbereiche)
- Object-Caching (Datensätze)
- Page-Caching (Komplette Seiten)
- Auto-Invalidation bei Updates

### API System
**Datei:** `core/Api.php`  
**Verantwortung:** RESTful API für externe Integrationen

**API-Struktur:**
```
/api/v1/users          → Benutzer-Verwaltung
/api/v1/pages          → Seiten-Verwaltung
/api/v1/settings       → System-Einstellungen
/api/v1/plugins        → Plugin-Status
```

**Authentication:**
- Bearer Token-basiert
- API-Key-Validierung
- Rate-Limiting pro Endpoint

**Response-Format:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Optional message",
  "meta": {
    "timestamp": "2026-02-17T19:00:00Z",
    "version": "2.0.0"
  }
}
```

### Page Management
**Datei:** `core/PageManager.php`  
**Verantwortung:** Content-Management und Landing-Page-Builder

**Features:**
- WYSIWYG-Integration
- Landing-Page-Sections
- Content-Revisions (vorbereitet)
- SEO-Metadaten
- Publish-Workflow

## 🔄 Request-Lifecycle

```
1. index.php Bootstrap
   ↓
2. Autoloader registrieren
   ↓
3. Konfiguration laden (config.php)
   ↓
4. Bootstrap::instance() initialisieren
   ↓
5. Database-Verbindung herstellen
   ↓
6. Security-Headers setzen
   ↓
7. Session starten
   ↓
8. Auth-System laden
   ↓
9. CMS-Init Hook feuern
   ↓
10. Plugins laden & initialisieren
    ↓
11. Theme aktivieren
    ↓
12. Before-Route Hook feuern
    ↓
13. Router::match() - URL parsen
    ↓
14. Route-Handler ausführen
    ↓
15. Template rendern
    ↓
16. After-Route Hook feuern
    ↓
17. Response ausgeben
```

## 🗂️ Verzeichnis-Struktur (Detailliert)

```
CMSv2/
├── core/                    # Kern-System (11 Klassen)
│   ├── Bootstrap.php        # System-Initialisierung
│   ├── Database.php         # DB-Abstraktion
│   ├── Security.php         # Sicherheits-Layer
│   ├── Auth.php            # Authentifizierung
│   ├── Router.php          # URL-Routing
│   ├── Hooks.php           # Event-System
│   ├── PluginManager.php   # Plugin-Verwaltung
│   ├── ThemeManager.php    # Theme-Verwaltung
│   ├── CacheManager.php    # Cache-System
│   ├── PageManager.php     # Content-Management
│   └── Api.php             # REST API
│
├── admin/                   # Admin-Backend
│   ├── layout/             # Admin-Templates
│   │   ├── header.php
│   │   └── footer.php
│   ├── index.php           # Dashboard
│   ├── plugins.php         # Plugin-Verwaltung
│   ├── themes.php          # Theme-Verwaltung
│   ├── users.php           # Benutzer-Verwaltung
│   ├── settings.php        # System-Einstellungen
│   ├── pages.php           # Seiten-Verwaltung
│   ├── landing.php         # Landing-Page-Builder
│   ├── media.php           # Medien-Verwaltung
│   └── update.php          # System-Updates
│
├── member/                  # Mitglieder-Bereich
│   └── index.php           # Member-Dashboard
│
├── themes/                  # Themes
│   └── default/            # Standard-Theme
│       ├── style.css       # Styles + Theme-Header
│       ├── functions.php   # Theme-Funktionen
│       ├── header.php      # Header-Template
│       ├── footer.php      # Footer-Template
│       ├── home.php        # Homepage
│       ├── page.php        # Einzelseite
│       ├── login.php       # Login
│       ├── register.php    # Registrierung
│       ├── 404.php         # 404-Seite
│       └── error.php       # Error-Seite
│
├── plugins/                 # Plugins
│   └── example-plugin/     # Beispiel-Plugin
│       └── example-plugin.php
│
├── assets/                  # Frontend-Assets
│   ├── css/
│   │   ├── main.css        # Main-Styles
│   │   └── admin.css       # Admin-Styles
│   └── js/
│       └── admin.js        # Admin-JavaScript
│
├── includes/                # Helper-Funktionen
│   └── functions.php       # Global Helpers
│
├── uploads/                 # User-Uploads
│   └── .htaccess          # PHP-Execution disabled
│
├── doc/                     # Dokumentation
│   ├── README.md           # Übersicht
│   ├── INSTALLATION.md     # Installation
│   ├── STATUS.md           # Projekt-Status
│   ├── PLUGIN-DEVELOPMENT.md
│   ├── THEME-DEVELOPMENT.md
│   ├── API-REFERENCE.md
│   ├── SECURITY.md
│   ├── CHANGELOG.md
│   ├── ARCHITECTURE.md     # Diese Datei
│   ├── DATABASE-SCHEMA.md
│   └── HOOKS-REFERENCE.md
│
├── index.php               # Bootstrap-Datei (Entry Point)
├── config.php              # Konfiguration
├── install.php             # Installer
├── .htaccess              # Apache-Konfiguration
└── README.md              # Quick-Start-Guide
```

## 🔐 Sicherheits-Architektur

### Schichten-Modell

```
┌─────────────────────────────────────┐
│  1. Input Validation Layer          │
│     - Sanitization                  │
│     - Type checking                 │
│     - Format validation             │
├─────────────────────────────────────┤
│  2. Authentication Layer            │
│     - Login verification            │
│     - Session management            │
│     - Role-based access control     │
├─────────────────────────────────────┤
│  3. Authorization Layer             │
│     - Permission checks             │
│     - Capability system             │
│     - Resource ownership            │
├─────────────────────────────────────┤
│  4. Data Access Layer               │
│     - Prepared statements           │
│     - Query sanitization            │
│     - SQL injection prevention      │
├─────────────────────────────────────┤
│  5. Output Layer                    │
│     - HTML escaping                 │
│     - URL sanitization              │
│     - Attribute escaping            │
└─────────────────────────────────────┘
```

### Security-Checkliste (Implementiert)

- ✅ SQL Injection Prevention (Prepared Statements)
- ✅ XSS Protection (Input/Output Escaping)
- ✅ CSRF Protection (Token-basiert)
- ✅ Session Security (HTTP-Only, Regeneration)
- ✅ Password Security (BCrypt, Cost 12)
- ✅ Rate Limiting (Login-Attempts)
- ✅ Security Headers (OWASP-konform)
- ✅ File Upload Protection (Type-Check, Execution-Block)
- ✅ Directory Traversal Prevention
- ✅ Sensitive Data Protection

## 🚀 Performance-Architektur

### Optimierungs-Strategien

1. **Datenbank-Optimierung**
   - Indexierung wichtiger Felder
   - Query-Optimierung
   - Connection-Pooling
   - Lazy Loading von Relations

2. **Caching-Strategie**
   - Multi-Layer-Cache (File + Memory)
   - Fragment-Caching
   - LiteSpeed-Integration
   - Auto-Invalidation

3. **Asset-Optimierung**
   - CSS/JS Minifizierung (Production)
   - Browser-Caching (1 Jahr für Assets)
   - GZIP-Kompression
   - Lazy-Loading von Bildern

4. **Code-Optimierung**
   - Singleton-Pattern (Single Instance)
   - Lazy-Initialization
   - Minimaler Bootstrap
   - Optimierte Autoloader

## 🧩 Erweiterbarkeit

### Plugin-Integration

```
Plugin Development Flow:
1. Create plugin directory
2. Add plugin header metadata
3. Implement Singleton pattern
4. Register hooks (Actions/Filters)
5. Add admin pages (optional)
6. Define activation/deactivation hooks
```

**Plugin-Capabilities:**
- Eigene Datenbank-Tabellen
- Eigene Admin-Pages
- Eigene Frontend-Templates
- API-Endpoints registrieren
- Custom Post Types
- Custom Taxonomies

### Theme-Integration

```
Theme Development Flow:
1. Create theme directory
2. Add style.css with header
3. Implement required templates
4. Add functions.php for hooks
5. Register assets
6. Test responsive design
```

**Theme-Capabilities:**
- Vollständige Template-Kontrolle
- Custom CSS/JavaScript
- Hook-basierte Content-Injection
- Widget-Areas (prepared)
- Customizer-Integration (prepared)

## 📐 Design-Patterns

### 1. Singleton Pattern
**Verwendung:** Alle Core-Klassen  
**Grund:** Garantiert nur eine Instanz pro Request

```php
private static ?self $instance = null;

public static function instance(): self
{
    if (self::$instance === null) {
        self::$instance = new self();
    }
    return self::$instance;
}
```

### 2. Dependency Injection
**Verwendung:** Bootstrap-Klasse  
**Grund:** Lose Kopplung, bessere Testbarkeit

```php
public function __construct(
    private Database $db,
    private Security $security,
    private Auth $auth
) {}
```

### 3. Factory Pattern
**Verwendung:** PluginManager, ThemeManager  
**Grund:** Dynamisches Laden von Komponenten

### 4. Observer Pattern
**Verwendung:** Hook-System  
**Grund:** Event-driven Architektur, lose Kopplung

### 5. Strategy Pattern
**Verwendung:** Cache-System (File/LiteSpeed)  
**Grund:** Austauschbare Implementierungen

## 🔄 Data Flow

### User Registration Flow
```
User Input → Validation → Sanitization → Check Existing
    ↓
Password Hash → Insert Database → Create Session
    ↓
Send Welcome Email → Redirect to Dashboard
```

### Plugin Activation Flow
```
Admin Request → CSRF Verify → Load Plugin Metadata
    ↓
Check Dependencies → Execute Activation Hook
    ↓
Update Database Status → Cache Clear → Reload Plugins
```

### Page Render Flow
```
URL Request → Route Match → Permission Check
    ↓
Load Page Data → Apply Filters → Cache Check
    ↓
Render Template → Output Escaping → Response
```

## 📊 Skalierbarkeit

### Horizontal Scaling
- Session-less API-Modus
- Database-Replication-Support (vorbereitet)
- CDN-Integration für Assets
- Load-Balancer-kompatibel

### Vertical Scaling
- Optimierte Queries (minimale DB-Calls)
- Memory-effiziente Implementierung
- Lazy-Loading aller Komponenten
- Cache-First-Strategie

## 🎯 Best Practices

### Code-Standards
- **PHP 8.3+** - Moderne Features nutzen
- **Strict Types** - `declare(strict_types=1)`
- **Type Hints** - Alle Parameter und Returns
- **PSR-12** - Code-Style-Standard
- **PHPDoc** - Vollständige Dokumentation

### Security-Standards
- **OWASP Top 10** - Alle Punkte berücksichtigt
- **Defense in Depth** - Mehrschichtige Sicherheit
- **Least Privilege** - Minimale Berechtigungen
- **Secure by Default** - Sichere Default-Konfiguration

### Performance-Standards
- **< 50ms** - Homepage Load-Time (ohne DB)
- **< 100ms** - Admin-Dashboard
- **< 5 Queries** - Pro Standard-Seite
- **100% Prepared** - Alle DB-Queries

## 📚 Weiterführende Dokumentation

- [Installation Guide](INSTALLATION.md)
- [Plugin Development](PLUGIN-DEVELOPMENT.md)
- [Theme Development](THEME-DEVELOPMENT.md)
- [API Reference](API-REFERENCE.md)
- [Security Guide](SECURITY.md)
- [Database Schema](DATABASE-SCHEMA.md)
- [Hooks Reference](HOOKS-REFERENCE.md)

---

**Letzte Aktualisierung:** 17. Februar 2026  
**Version:** 2.0.0  
**Autor:** CMSv2 Development Team
