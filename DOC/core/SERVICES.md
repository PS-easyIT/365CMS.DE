# Services Referenz

**Namespace:** `CMS\Services\` | **Stand:** 2026-02-18

Alle Services liegen in `core/Services/`. Sie folgen dem Singleton-Pattern via `getInstance()`.

---

## UserService

**Datei:** `core/Services/UserService.php` · **654 Zeilen**

Business-Logik für Benutzerverwaltung. Wird von AJAX-Endpoints und Admin-Views genutzt.

### Methoden

| Methode | Rückgabe | Beschreibung |
|---------|----------|-------------|
| `createUser(array $data)` | `int\|WP_Error` | Neuen Benutzer anlegen (Dupe-Check, Password-Hash) |
| `updateUser(int $id, array $data)` | `bool\|WP_Error` | Felder aktualisieren |
| `deleteUser(int $id)` | `bool\|WP_Error` | User löschen oder auf inactive setzen |
| `getUserById(int $id)` | `?object` | User by ID |
| `listUsers(array $filters, string $orderby, string $order, int $page, int $per_page)` | `array` | Paginierte User-Liste |
| `bulkAction(string $action, array $userIds, array $data)` | `array` | activate/deactivate/change_role für mehrere User |
| `getUserStats()` | `array` | Statistiken (total, active, roles, new_last_30_days) |
| `getUserMeta(int $id, string $key)` | `mixed` | Meta-Wert lesen |
| `updateUserMeta(int $id, string $key, mixed $value)` | `void` | Meta-Wert schreiben |
| `validateUserData(array $data, string $mode)` | `bool\|WP_Error` | Validierung (mode: create/update) |

### `$data`-Felder für createUser

| Feld | Pflicht | Validierung |
|------|---------|-------------|
| `username` | Ja | alphanumeric + underscore |
| `email` | Ja | filter_var VALIDATE_EMAIL |
| `password` | Ja | bcrypt (cost 12) |
| `role` | Nein | admin/editor/author/member |
| `status` | Nein | active/inactive/banned |
| `first_name`, `last_name` | Nein | Gespeichert als user_meta |

---

## DashboardService

**Datei:** `core/Services/DashboardService.php` · **465 Zeilen**

Dashboard-Statistiken für das Admin-Center.

### Methoden

| Methode | Beschreibung |
|---------|-------------|
| `getAllStats()` | Alle Stats: users, pages, media, sessions, security, performance, system |
| `getUserStats()` | Benutzer-Zahlen, Rollen, Wachstumsrate |
| `getPageStats()` | Seiten total/published/draft/private/scheduled |
| `getMediaStats()` | Upload-Verzeichnis-Größe, Dateianzahl |
| `getSessionStats()` | Aktive Sessions, Session-Ablauf |
| `getSecurityStats()` | Login-Versuche, fehlgeschlagene Logins |
| `getPerformanceStats()` | PHP-Memory, OPcache-Status, Cache-Infos |
| `getSystemInfo()` | PHP-Version, MySQL-Version, Zeitzone etc. |
| `getActivityFeed(int $limit = 50)` | Aktivitäts-Log mit User-Joins |

---

## StatusService

**Datei:** `core/Services/StatusService.php` · **580 Zeilen**

System-Health-Checks und Reparatur-Werkzeuge.

### Methoden

| Methode | Beschreibung |
|---------|-------------|
| `getFullStatus()` | Alle Checks: database, filesystem, php, security, performance |
| `checkDatabase()` | Verbindung, Tabellen, Charset, Overhead, Größe |
| `checkFilesystem()` | Verzeichnisse prüfen (uploads, cache, logs, themes, plugins) |
| `checkPHP()` | PHP-Version, Erweiterungen, Einstellungen |
| `checkSecurity()` | Admin-User, Sessions, verwaiste Datensätze |
| `checkPerformance()` | OPcache, APCu, Speicher |
| `runRepair(string $action)` | Repairs: create_missing_tables, cleanup_sessions, cleanup_logs |

### Required-Tables-Prüfung

Prüft dynamisch via `$this->prefix . 'tablename'`:
`users`, `user_meta`, `sessions`, `login_attempts`, `settings`, `pages`, `activity_log`

---

## SystemService

**Datei:** `core/Services/SystemService.php` · **656 Zeilen**

System-Informationen und Diagnose-Tools.

### Methoden

| Methode | Beschreibung |
|---------|-------------|
| `getSystemInfo()` | PHP, MySQL, Server, Memory, Timezone, OS |
| `getDatabaseStatus()` | DB-Verbindung, -Größe, Tabellen-Anzahl |

---

## SEOService

**Datei:** `core/Services/SEOService.php`

SEO-Optimierung und Crawler-Unterstützung.

| Methode | Beschreibung |
|---------|-------------|
| `generateSitemap()` | XML-Sitemap aller veröffentlichten Seiten |
| `generateRobotsTxt()` | robots.txt generieren |
| `getMetaTags(array $page)` | Meta-Tags Array (title, description, og:, twitter:) |

---

## TrackingService

**Datei:** `core/Services/TrackingService.php`

Seitenaufruf-Tracking ohne externe Dienste.

| Methode | Beschreibung |
|---------|-------------|
| `trackPageView(int\|null $pageId, string $slug, string $title, int\|null $userId)` | Aufruf protokollieren |

> Wird automatisch in `ThemeManager::render()` aufgerufen (silent fail).

---

## AnalyticsService

**Datei:** `core/Services/AnalyticsService.php`

Besucherstatistiken aus den Tracking-Daten.

---

## BackupService

**Datei:** `core/Services/BackupService.php`

Datenbank- und Datei-Backups.

---

## EditorService

**Datei:** `core/Services/EditorService.php`

Seiten-Editor Logik (Revision-Handling, Auto-Save).

---

## LandingPageService

**Datei:** `core/Services/LandingPageService.php`

Landing-Page-Sections (Drag & Drop Builder Logik).

---

## ThemeCustomizer

**Datei:** `core/Services/ThemeCustomizer.php`

Verwaltung von Theme-Einstellungen (Farben, Fonts, Logo) in `{prefix}settings` und `{prefix}theme_customizations`.

---

## UpdateService

**Datei:** `core/Services/UpdateService.php`

Prüft auf CMS-Updates und koordiniert den Update-Prozess.

---

## Gemeinsame Muster

### Prefix-Nutzung in Services

Alle Services mit DB-Zugriff definieren:

```php
class XxxService {
    private Database $db;
    private string $prefix;
    
    private function __construct() {
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }
}
```

### Raw SQL vs. insert/update/delete

```php
// Raw SQL → Prefix manuell:
$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users");

// CRUD-Methoden → Prefix AUTOMATISCH hinzugefügt:
$this->db->insert('users', $data);       // korrekt
$this->db->delete('users', ['id' => 5]); // korrekt
// FALSCH: $this->db->insert('cms_users', ...) → doppelter Prefix!
```

### WP_Error-Muster

```php
public function doSomething(int $id): int|WP_Error {
    if ($id <= 0) {
        return new WP_Error('invalid_id', 'Ungültige ID', ['status' => 400]);
    }
    $result = $this->db->insert('table', [...]);
    if (!$result) {
        return new WP_Error('db_error', 'Datenbankfehler', ['status' => 500]);
    }
    return $this->db->insert_id();
}

// Aufruf
$result = $service->doSomething($id);
if (is_wp_error($result)) {
    wp_send_json_error(['message' => $result->get_error_message()]);
}
```
