# 365CMS – Service-Klassen


Der Service-Layer enthält die **Geschäftslogik** des CMS. Alle 11 Service-Klassen sind im Namespace `CMS\Services` und befinden sich in `core/Services/`.

---

## Überblick

| Klasse | Datei | Aufgabe |
|--------|-------|---------|
| `AnalyticsService` | `AnalyticsService.php` | Besucher-Statistiken & Auswertungen |
| `BackupService` | `BackupService.php` | Datenbank- und Datei-Backups |
| `DashboardService` | `DashboardService.php` | Dashboard-Widget-Daten |
| `EditorService` | `EditorService.php` | Inhalts-Editor (SunEditor) |
| `LandingPageService` | `LandingPageService.php` | Landing-Page-Builder |
| `MediaService` | `MediaService.php` | Datei-Upload & Medienverwaltung |
| `MemberService` | `MemberService.php` | Member-Dashboard-Logik |
| `SEOService` | `SEOService.php` | Meta-Tags, Sitemap |
| `StatusService` | `StatusService.php` | System-Status-Checks |
| `ThemeCustomizer` | `ThemeCustomizer.php` | Theme-Einstellungen |
| `UserService` | `UserService.php` | Benutzer-Verwaltung (CRUD) |

---

## AnalyticsService

**Datei:** `core/Services/AnalyticsService.php`

Verwaltet Besucher-Statistiken und Auswertungen.

```php
$analytics = new CMS\Services\AnalyticsService();

// Besucher der letzten 30 Tage
$stats = $analytics->getVisitorStats(30);

// Top 10 Seiten
$topPages = $analytics->getTopPages(10);

// Heutiger Besuchercount
$today = $analytics->getDailyCount();
```

---

## BackupService

**Datei:** `core/Services/BackupService.php`

Erstellt und verwaltet Backups.

```php
$backup = new CMS\Services\BackupService();

// Datenbank-Backup erstellen
$result = $backup->createDatabaseBackup();
// → Gibt Pfad zur .sql.gz-Datei zurück

// Datei-Backup
$result = $backup->createFileBackup(['uploads/', 'themes/']);

// Verfügbare Backups listen
$backups = $backup->listBackups();

// Backup wiederherstellen
$backup->restore($backupFile);
```

---

## MediaService

**Datei:** `core/Services/MediaService.php`

Verwaltet Datei-Uploads und die Medienbibliothek.

```php
$media = new CMS\Services\MediaService();

// Datei hochladen
$result = $media->upload($_FILES['file'], [
    'allowed_types' => ['image/jpeg', 'image/png'],
    'max_size'      => 5 * 1024 * 1024, // 5 MB
]);

// Medien-Bibliothek
$files = $media->getMediaLibrary([
    'type'  => 'image',
    'page'  => 1,
    'limit' => 20,
]);

// Datei löschen
$media->delete($mediaId);

// Thumbnail generieren
$thumb = $media->generateThumbnail($filePath, 300, 200);
```

---

## UserService

**Datei:** `core/Services/UserService.php`

Vollständige Benutzerverwaltung. Hauptschnittstelle für alle User-Operationen.

```php
$userService = CMS\Services\UserService::getInstance();

// User erstellen
$userId = $userService->createUser([
    'username'   => 'max_mustermann',
    'email'      => 'max@beispiel.de',
    'password'   => 'SicheresPasswort123!',
    'role'       => 'member',
    'first_name' => 'Max',
    'last_name'  => 'Mustermann',
]);

// User laden
$user = $userService->getUserById($userId);

// Alle User mit Filter
$users = $userService->getAllUsers([
    'role'   => 'member',
    'status' => 'active',
    'search' => 'max',
    'page'   => 1,
    'limit'  => 20,
]);

// User aktualisieren
$userService->updateUser($userId, [
    'display_name' => 'Max M.',
    'status'       => 'active',
]);

// User löschen
$userService->deleteUser($userId);

// Meta-Daten
$userService->updateUserMeta($userId, 'phone', '0123456789');
$phone = $userService->getUserMeta($userId, 'phone');
```

---

## MemberService

**Datei:** `core/Services/MemberService.php`

Liefert Daten für das Member-Dashboard.

```php
$memberService = CMS\Services\MemberService::getInstance();

// Dashboard-Daten für User
$data = $memberService->getMemberDashboardData($userId);
// Enthält: notifications, recent_activity, subscription, stats

// Profil aktualisieren
$memberService->updateProfile($userId, $_POST);

// Benachrichtigungen
$notifications = $memberService->getNotifications($userId, unread: true);
$memberService->markNotificationRead($notificationId);
```

---

## SEOService

**Datei:** `core/Services/SEOService.php`

Verwaltet Meta-Tags und generiert Sitemaps.

```php
$seo = new CMS\Services\SEOService();

// Meta-Tags für Seite setzen
$seo->setPageMeta([
    'title'       => 'Über uns',
    'description' => 'Erfahrt mehr über unser Team...',
    'keywords'    => 'team, unternehmen, kontakt',
    'og_image'    => '/uploads/team.jpg',
]);

// Sitemap generieren
$seo->generateSitemap(); // Schreibt /sitemap.xml

// Robots.txt
$seo->generateRobotsTxt();
```

---

## DashboardService

**Datei:** `core/Services/DashboardService.php`

Liefert Daten für das Admin-Dashboard.

```php
$dashboard = new CMS\Services\DashboardService();

// Alle Widget-Daten
$data = $dashboard->getWidgetData();
// Enthält: user_count, post_count, recent_logins, system_status, top_pages

// Einzelne Metriken
$userCount = $dashboard->getUserCount();
$activePlugins = $dashboard->getActivePluginCount();
```

---

## ThemeCustomizer

**Datei:** `core/Services/ThemeCustomizer.php`

Verwaltet Theme-Anpassungen via UI.

```php
$customizer = new CMS\Services\ThemeCustomizer();

// Einstellung lesen
$primaryColor = $customizer->getSetting('cms-default', 'colors', 'primary', '#007bff');

// Einstellung speichern
$customizer->saveSetting('cms-default', 'colors', 'primary', '#ff6600');

// Alle Theme-Einstellungen
$settings = $customizer->getThemeSettings('cms-default');
```

---

## StatusService

**Datei:** `core/Services/StatusService.php`

Prüft den System-Status (für Admin-Dashboard).

```php
$status = new CMS\Services\StatusService();

// Vollständiger Status-Check
$report = $status->getSystemStatus();
// Prüft: DB-Verbindung, PHP-Version, Extensions, Datei-Berechtigungen, Cache

// Einzelne Checks
$dbOk = $status->checkDatabase();
$phpOk = $status->checkPhpVersion();
$writeable = $status->checkWritePermissions(['uploads/', 'cache/', 'logs/']);
```

---

## SystemService

**Datei:** `core/Services/SystemService.php`

Liefert umfassende Server- und System-Informationen für das Admin-Panel.

```php
$system = new CMS\Services\SystemService();

// Server-Informationen
$info = $system->getServerInfo();
// Enthält: PHP-Version, MySQL-Version, Web-Server, OS, Upload-Limits

// Datenbank-Status
$dbStats = $system->getDatabaseStats();
// Enthält: Größe, Tabellenanzahl, Verbindungsstatus

// Verzeichnisgrößen
$sizes = $system->getDirectorySizes();
```

---

## TrackingService

**Datei:** `core/Services/TrackingService.php`

Erfasst Seitenaufrufe DSGVO-konform ohne externe Tools.

```php
$tracker = new CMS\Services\TrackingService();

// Aktuellen Seitenaufruf tracken
$tracker->trackPageView(
    url: '/experten',
    referrer: $_SERVER['HTTP_REFERER'] ?? '',
    userAgent: $_SERVER['HTTP_USER_AGENT'] ?? ''
);
// IP-Adressen werden nur als Hash gespeichert
```

---

## UpdateService

**Datei:** `core/Services/UpdateService.php`

Prüft verfügbare Updates für CMS-Core und Plugins via GitHub Release API.

```php
$updater = new CMS\Services\UpdateService();

// Verfügbare Updates prüfen (gecacht 6h)
$updates = $updater->checkForUpdates();
// Gibt: ['core' => [...], 'plugins' => [...]]

// Core-Update durchführen
$result = $updater->updateCore('1.8.1');

// Plugin-Update
$result = $updater->updatePlugin('cms-experts', '1.3.0');
```

---

*Letzte Aktualisierung: 22. Februar 2026 – Version 1.8.0*
