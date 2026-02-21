# Admin-Bereich – Vollständige Dateistruktur

**Version:** 2.0.2  
**Letztes Update:** 18. Februar 2026  
**Autor:** Automatisch generiert nach Code-Audit

---

## 📁 Verzeichnisstruktur

```
admin/
├── index.php              # Dashboard
├── pages.php              # Seiten & Landing Page Editor
├── users.php              # Benutzerverwaltung
├── settings.php           # Systemeinstellungen
├── plugins.php            # Plugin-Verwaltung
├── theme-editor.php       # Theme-Editor & Customizer
├── seo.php                # SEO-Einstellungen
├── performance.php        # Performance-Einstellungen
├── analytics.php          # Analytics & Traffic
├── backup.php             # Backup & Wiederherstellung
├── subscriptions.php      # Abo-Pakete & Zuweisungen
├── groups.php             # Benutzergruppen-Verwaltung
├── updates.php            # System-/Plugin-/Theme-Updates
├── system.php             # System & Diagnose
├── README.md              # Kurzübersicht (für Entwickler)
├── includes/
│   └── sidebar.php        # Legacy-Sidebar (deprecated!)
└── partials/
    └── admin-menu.php     # Aktive Menü-Funktionen
```

---

## 🗂️ Datei-Dokumentation

### `index.php` – Dashboard
| Eigenschaft | Wert |
|-------------|------|
| Route | `/admin` |
| Klassen | `CMS\Auth`, `CMS\Security`, `CMS\Services\DashboardService`, `CMS\Hooks` |
| CSRF-Action | `admin_dashboard` |
| Features | Statistik-Karten (Users, Pages, Media, Sessions), System-Info, Security-Status, Schnellzugriff, Plugin-Widgets |
| Hooks | `admin_dashboard_widgets` (Action) |

---

### `pages.php` – Seiten & Landing Page Editor
| Eigenschaft | Wert |
|-------------|------|
| Route | `/admin/pages` |
| Klassen | `CMS\Auth`, `CMS\PageManager`, `CMS\Services\LandingPageService`, `CMS\Hooks`, `CMS\Security` |
| CSRF-Action | `landing_page` |
| Tabs | `pages` (alle Seiten), `landing` (Header/Features), `colors` (Farben) |
| Features | Seiten-Tabelle, Landing-Page-Editor mit Logo-Upload, Feature-Karten, Farbpalette |
| File Upload | Erlaubte Typen: `jpg`, `jpeg`, `png`, `svg`, `gif` |

---

### `users.php` – Benutzerverwaltung
| Eigenschaft | Wert |
|-------------|------|
| Route | `/admin/users` |
| Klassen | `CMS\Auth`, `CMS\Services\UserService`, `CMS\Security`, `CMS\Hooks` |
| CSRF-Action | `user_management` |
| Tabs | `users` (Benutzerliste), `roles` (Rollen & Berechtigungen) |
| Aktionen | `create_user`, `update_user`, `delete_user` |
| Features | Statistik-Karten (gesamt/aktiv/inaktiv/gesperrt), Benutzer-Tabelle, Modal-Formulare, Rollen-Badges |

---

### `settings.php` – Systemeinstellungen
| Eigenschaft | Wert |
|-------------|------|
| Route | `/admin/settings` |
| Klassen | `CMS\Auth`, `CMS\Security`, `CMS\Hooks`, `CMS\Database`, `CMS\Services\EditorService` |
| CSRF-Action | `admin_settings` |
| Aktionen | `save_settings` |
| DB-Schlüssel | `setting_site_name`, `setting_site_description`, `setting_admin_email`, `setting_maintenance_mode`, `setting_allow_registration`, `setting_posts_per_page`, `setting_timezone`, `setting_date_format`, `setting_time_format` |

---

### `plugins.php` – Plugin-Verwaltung
| Eigenschaft | Wert |
|-------------|------|
| Route | `/admin/plugins` |
| Klassen | `CMS\Auth`, `CMS\Security`, `CMS\PluginManager` |
| CSRF-Action | `plugin_management` |
| Aktionen | `activate`, `deactivate`, `delete` (mit `confirm_delete=DELETE`), `upload` |
| Features | Plugin-Liste mit Status-Badges, Aktivieren/Deaktivieren, Löschen (mit Bestätigung), ZIP-Upload |

---

### `theme-editor.php` – Theme-Editor
| Eigenschaft | Wert |
|-------------|------|
| Route | `/admin/theme-editor` |
| Klassen | `CMS\Auth`, `CMS\Security`, `CMS\Services\ThemeCustomizer` |
| CSRF-Action | `theme_editor` |
| Aktionen | `save_customization`, `reset_category`, `reset_all`, `export_settings`, `import_settings`, `generate_css` |
| Tabs | `colors`, weitere via ThemeCustomizer |
| Features | CSS-Customizer (50+ Optionen), Import/Export (JSON), CSS-Generator, Reset-Funktionen, Live-Vorschau |
| **Hinweis** | Redirect-Messages aus GET-Parametern werden HTML-escaped (XSS-Schutz) |

---

### `seo.php` – SEO-Einstellungen
| Eigenschaft | Wert |
|-------------|------|
| Route | `/admin/seo` |
| Klassen | `CMS\Auth`, `CMS\Security`, `CMS\Database`, `CMS\Services\SEOService` |
| CSRF-Action | `seo_settings` |
| Aktionen | `save_seo` |
| DB-Schlüssel | `seo_meta_description`, `seo_meta_keywords`, `seo_og_title`, `seo_og_description`, `seo_og_image`, `seo_twitter_card`, `seo_twitter_site`, `seo_twitter_creator`, `seo_canonical_url`, `seo_robots_index`, `seo_robots_follow`, `seo_google_analytics`, `seo_google_site_verification`, `seo_bing_site_verification`, `seo_favicon_url`, `seo_apple_touch_icon`, `seo_robots_txt_content` |
| Besonderheiten | Regeneriert automatisch `robots.txt` und `sitemap.xml` nach dem Speichern |

---

### `performance.php` – Performance-Einstellungen
| Eigenschaft | Wert |
|-------------|------|
| Route | `/admin/performance` |
| Klassen | `CMS\Auth`, `CMS\Security`, `CMS\Database` |
| CSRF-Action | `performance_settings` |
| Aktionen | `save_performance` |
| DB-Schlüssel | `perf_enable_lazy_loading`, `perf_minify_css`, `perf_minify_js`, `perf_enable_preload_fonts`, `perf_enable_gzip`, `perf_enable_browser_cache`, `perf_cache_duration`, `perf_defer_js`, `perf_async_css`, `perf_preload_critical_css`, `perf_disable_emojis`, `perf_limit_revisions` |

---

### `analytics.php` – Analytics & Traffic
| Eigenschaft | Wert |
|-------------|------|
| Route | `/admin/analytics` |
| Klassen | `CMS\Auth`, `CMS\Security`, `CMS\Services\AnalyticsService`, `CMS\Services\TrackingService` |
| CSRF-Action | `analytics` |
| Tabs | `overview`, weitere |
| Features | Besucher-Statistiken (30 Tage), Top-Pages, Seitenaufrufe nach Datum, Aktivitäts-Log |
| Fehlerbehandlung | try/catch mit Fallback auf leere Arrays – Seite bleibt ladbar auch wenn Analytics-DB leer ist |

---

### `backup.php` – Backup & Wiederherstellung
| Eigenschaft | Wert |
|-------------|------|
| Route | `/admin/backup` |
| Klassen | `CMS\Auth`, `CMS\Security`, `CMS\Services\BackupService` |
| CSRF-Action | `backup` |
| Aktionen | `create_full_backup` (Timeout 300s), `create_db_backup` (Timeout 120s), `email_backup`, `delete_backup` |
| Features | Backup-Liste mit Größen, Vollbackup, DB-Backup, E-Mail-Versand, Backup-History (20 Einträge) |

---

### `subscriptions.php` – Abo-Verwaltung
| Eigenschaft | Wert |
|-------------|------|
| Route | `/admin/subscriptions` |
| Klassen | `CMS\Auth`, `CMS\Security`, `CMS\Database`, `CMS\SubscriptionManager` |
| CSRF-Action | `subscription_management` |
| Aktionen | `assign_subscription`, `create_plan`, `seed_defaults` |
| Tabs | `plans` (Abo-Pakete), `assignments` (Benutzer-Zuweisungen), `groups` (Gruppen-Tab, aktuell Platzhalter) |
| Features | Abo-Paket-Karten mit Limits/Features, Benutzer-Zuweisungen, Standard-Pakete generieren |
| DB | `{prefix}subscription_plans`, `{prefix}user_subscriptions` |

---

### `groups.php` – Benutzergruppen
| Eigenschaft | Wert |
|-------------|------|
| Route | `/admin/groups` |
| Klassen | `CMS\Auth`, `CMS\Security`, `CMS\Database`, `CMS\SubscriptionManager` |
| CSRF-Action | `group_management` |
| Aktionen | `create_group`, `add_member`, `remove_member` |
| Features | Gruppen-Karten mit Mitgliederzahl, Gruppenplan-Badge, Modal für neue Gruppe und Mitgliederverwaltung |
| DB | `{prefix}user_groups`, `{prefix}user_group_members`, `{prefix}subscription_plans` |

---

### `updates.php` – Updates
| Eigenschaft | Wert |
|-------------|------|
| Route | `/admin/updates` |
| Klassen | `CMS\Auth`, `CMS\Security`, `CMS\Services\UpdateService` |
| CSRF-Action | `updates` |
| Tabs | `core` (CMS-Updates), `plugins`, `themes`, `requirements`, `history` |
| Features | CMS-Core-Update-Status, Plugin-Update-Liste, Theme-Updates, System-Anforderungsprüfung, Update-History |

---

### `system.php` – System & Diagnose
| Eigenschaft | Wert |
|-------------|------|
| Route | `/admin/system` |
| Klassen | `CMS\Auth`, `CMS\Security`, `CMS\Services\SystemService` |
| CSRF-Action | `system_management` |
| Aktionen | `clear_cache`, `clear_sessions`, `clear_failed_logins`, `repair_tables`, `optimize_tables`, `clear_logs`, `create_missing_tables` |
| Tabs | `overview`, `database`, `files`, `security`, `tools`, `logs` |
| Features | PHP/MySQL-Info, Datenbankstatus, Tabellenstatus, Dateirechte, Verzeichnisgrößen, CMS-Statistiken, Security-Status, Fehler-Logs (50 Einträge) |
| POST-Redirect | Nach POST wird PR-Session-Key gesetzt und auf dieselbe URL redirectet (verhindert Form-Resubmission) |

---

## 🔧 Hilfsdateien

### `partials/admin-menu.php` – Aktive Menü-Funktionen (Primär)

Definiert die Funktionen:
- `getAdminMenuItems(string $currentPage): array` – Gibt alle Menüpunkte zurück
- `renderAdminSidebarStyles(): void` – Gibt Inline-CSS für Sidebar aus
- `renderAdminSidebar(string $currentPage): void` – Rendert die komplette Sidebar

**Menüstruktur mit Children (Sub-Menü):**
- Das `children`-Array ermöglicht verschachtelte Menüpunkte
- Aktuell nutzt `settings` → `updates` als Sub-Menüpunkt

**Hooks:**
- `admin_menu_items` (Filter) – Erlaubt Plugins eigene Menüpunkte hinzuzufügen

### `includes/sidebar.php` – Legacy-Sidebar (Deprecated)

> ⚠️ **DEPRECATED** – Diese Datei wird nicht mehr aktiv eingebunden.  
> Verwende `partials/admin-menu.php` mit `renderAdminSidebar()`.

Wurde früher von `groups.php` und `subscriptions.php` direkt includiert und renderte
die Sidebar als Inline-HTML. Seit dem Sicherheits-Audit vom 18.02.2026 sind beide
Dateien auf `admin-menu.php` umgestellt.

---

## 🔒 Sicherheitsmuster

Alle Admin-Dateien folgen diesem Sicherheits-Bootstrap:

```php
<?php
declare(strict_types=1);

// Schritt 1: Konfiguration
require_once dirname(__DIR__) . '/config.php';

// Schritt 2: Autoloader (CMS\* Klassen)
require_once CORE_PATH . 'autoload.php';

// Schritt 3: Helper-Funktionen (sanitize_text, esc_html, …)
require_once ABSPATH . 'includes/functions.php';

use CMS\Auth;
use CMS\Security;

if (!defined('ABSPATH')) {
    exit;
}

// Schritt 4: Admin-Zugriff
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

// Schritt 5: CSRF-Token generieren
$csrfToken = Security::instance()->generateToken('my_action_name');

// Schritt 6: Admin-Menü laden
require_once __DIR__ . '/partials/admin-menu.php';
```

**CSRF-Verifikation beim POST:**
```php
if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'my_action_name')) {
    $error = 'Sicherheitsüberprüfung fehlgeschlagen';
}
```

**Ein Token pro Seite** (nicht mehrere `generateToken()` in Formularen!):
```php
// ✅ Richtig – Token einmalig generieren, in allen Formularen verwenden
$csrfToken = Security::instance()->generateToken('my_action');

// ❌ Falsch – überschreibt den Session-Token bei jedem Aufruf
<input ... value="<?php echo Security::instance()->generateToken(); ?>">
```

---

## 🧭 Navigations-Reihenfolge (Sidebar)

```
📊 Dashboard          /admin
📄 Seiten             /admin/pages
👥 Benutzer           /admin/users
💳 Abos               /admin/subscriptions
🔌 Plugins            /admin/plugins
🎨 Design             /admin/theme-editor
🔍 SEO                /admin/seo
⚡ Performance         /admin/performance
📈 Analytics          /admin/analytics
💾 Backups            /admin/backup
⚙️ Einstellungen      /admin/settings
   └ 🔄 Updates       /admin/updates
🔧 System & Diagnose  /admin/system
── Zur Website        /
🚪 Abmelden           /logout
```

---

## 📋 Bisher nicht als Admin-Seiten vorhandene Bereiche

Folgende Seiten existieren in der Sidebar-Definition, haben aber noch keine vollständige
Implementierung oder sind an andere Seiten angebunden:

| Seite | Status |
|-------|--------|
| Gruppen (`/admin/groups`) | ✅ Vorhanden, Mitglieder-AJAX-Loading ausstehend |
| Updates (`/admin/updates`) | ✅ Vorhanden, tatsächliche Update-Logik abhängig von `UpdateService` |
