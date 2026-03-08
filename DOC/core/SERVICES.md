# 365CMS – Service-Klassen

Kurzbeschreibung: Übersicht über den Service-Layer des 365CMS mit Aufgabenfeldern und typischen Einsatzbeispielen der zentralen Services.

Letzte Aktualisierung: 2026-03-08 · Version 2.3.1

Der Service-Layer enthält die **Geschäftslogik** des CMS. Die zentralen Service-Klassen liegen im Namespace `CMS\Services` und befinden sich in `core/Services/`.

---

## Überblick

| Klasse | Datei | Aufgabe |
|--------|-------|---------|
| `AnalyticsService` | `AnalyticsService.php` | Besucher-Statistiken & Auswertungen |
| `BackupService` | `BackupService.php` | Datenbank- und Datei-Backups |
| `CommentService` | `CommentService.php` | Kommentar-Verwaltung (CRUD, Moderation) |
| `CookieConsentService` | `CookieConsentService.php` | Cookie-Consent-Banner & Einwilligungen |
| `DashboardService` | `DashboardService.php` | Dashboard-Widget-Daten |
| `EditorJsRenderer` | `EditorJsRenderer.php` | Editor.js Block-Rendering zu HTML |
| `EditorJsService` | `EditorJsService.php` | Editor.js Integration & Block-Management |
| `EditorService` | `EditorService.php` | Inhalts-Editor (SunEditor) |
| `FeedService` | `FeedService.php` | RSS-/Atom-Feed-Generierung |
| `FileUploadService` | `FileUploadService.php` | Datei-Upload-Verarbeitung & Validierung |
| `GraphApiService` | `GraphApiService.php` | Microsoft-Graph-Zugriff via Client-Credentials |
| `ImageService` | `ImageService.php` | Bildverarbeitung (Resize, WebP, Thumbnails) |
| `LandingPageService` | `LandingPageService.php` | Landing-Page-Builder |
| `MailLogService` | `MailLogService.php` | Persistente Versandprotokolle für Admin & Diagnose |
| `MailQueueService` | `MailQueueService.php` | Mail-Queue, Cron-Worker, Retries und Backoff |
| `MailService` | `MailService.php` | E-Mail-Versand (SMTP/Symfony Mailer) |
| `MediaService` | `MediaService.php` | Datei-Upload & Medienverwaltung |
| `MemberService` | `MemberService.php` | Member-Dashboard-Logik |
| `MessageService` | `MessageService.php` | Internes Nachrichten-System (Threads, Soft-Delete) |
| `PdfService` | `PdfService.php` | PDF-Generierung via DomPDF |
| `PurifierService` | `PurifierService.php` | HTML-Bereinigung via HTMLPurifier |
| `RedirectService` | `RedirectService.php` | URL-Weiterleitungen |
| `SearchService` | `SearchService.php` | Volltextsuche (TNTSearch) |
| `SeoAnalysisService` | `SeoAnalysisService.php` | SEO-Analyse & Scoring pro Seite |
| `SEOService` | `SEOService.php` | Meta-Tags, Sitemap, Robots.txt |
| `SettingsService` | `SettingsService.php` | Gruppierte und optional verschlüsselte Laufzeit-Einstellungen |
| `SiteTableService` | `SiteTableService.php` | Tabellen-Verwaltung |
| `StatusService` | `StatusService.php` | System-Status-Checks |
| `SystemService` | `SystemService.php` | System-Infos, DB-Status |
| `ThemeCustomizer` | `ThemeCustomizer.php` | Theme-Einstellungen (Farben, Fonts) |
| `TrackingService` | `TrackingService.php` | Page-View-Tracking (DSGVO-konform) |
| `TranslationService` | `TranslationService.php` | Übersetzungssystem (i18n) |
| `UpdateService` | `UpdateService.php` | CMS-Update-Prüfung (GitHub Release API) |
| `UserService` | `UserService.php` | Benutzer-Verwaltung (CRUD) |
| `AzureMailTokenProvider` | `AzureMailTokenProvider.php` | XOAUTH2-Tokenbeschaffung und -Caching für Microsoft 365 SMTP |

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

## MessageService

**Datei:** `core/Services/MessageService.php`  
**Neu seit:** v2.0.0

Vollständiges internes Nachrichten-System für Member-to-Member-Kommunikation.
Unterstützt Threads (Antworten), Soft-Delete und Empfänger-Suche.

```php
$msg = CMS\Services\MessageService::getInstance();

// Posteingang (paginiert)
$inbox = $msg->getInbox($userId, 20, 0);

// Gesendete Nachrichten
$sent = $msg->getSent($userId, 20, 0);

// Nachricht senden
$id = $msg->send($senderId, $recipientId, 'Betreff', 'Nachrichtentext', $parentId);

// Thread abrufen
$thread = $msg->getThread($messageId, $userId);

// Als gelesen markieren
$msg->markAsRead($messageId, $userId);

// Soft-Delete (physisch gelöscht wenn beide Parteien gelöscht haben)
$msg->delete($messageId, $userId);

// Ungelesene Nachrichten zählen
$count = $msg->getUnreadCount($userId);

// Konversationen gruppiert nach Thread
$convos = $msg->getConversations($userId, 'inbox', 20, 0);

// Empfänger-Autocomplete
$users = $msg->searchRecipients('max', $currentUserId);
```

**Datenbank-Tabelle:** `cms_messages` (Schema v8)

| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| id | BIGINT PK | Auto-Increment |
| sender_id | INT UNSIGNED | FK → users |
| recipient_id | INT UNSIGNED | FK → users |
| subject | VARCHAR(255) | Betreff (optional) |
| body | TEXT | Nachrichtentext |
| is_read | TINYINT(1) | 0/1 |
| read_at | TIMESTAMP | Lesezeitpunkt |
| parent_id | BIGINT | Thread-Root-ID |
| deleted_by_sender | TINYINT(1) | Soft-Delete Absender |
| deleted_by_recipient | TINYINT(1) | Soft-Delete Empfänger |
| created_at | TIMESTAMP | Erstellungsdatum |

---

## CommentService

**Datei:** `core/Services/CommentService.php`

Verwaltet Kommentare für Seiten und Beiträge inkl. Moderation.

```php
$comments = new CMS\Services\CommentService();
$list = $comments->getComments($pageId, ['status' => 'approved', 'limit' => 20]);
```

---

## CookieConsentService

**Datei:** `core/Services/CookieConsentService.php`

Generiert und verwaltet das Cookie-Consent-Banner (DSGVO-konform).

---

## EditorJsService / EditorJsRenderer

**Dateien:** `core/Services/EditorJsService.php`, `core/Services/EditorJsRenderer.php`

Integration des Editor.js Block-Editors. `EditorJsService` verwaltet das Speichern/Laden von Block-Daten; `EditorJsRenderer` konvertiert die JSON-Blöcke in HTML-Ausgabe.

---

## FeedService

**Datei:** `core/Services/FeedService.php`

Generiert RSS- und Atom-Feeds für veröffentlichte Inhalte.

---

## FileUploadService

**Datei:** `core/Services/FileUploadService.php`

Validiert und verarbeitet Datei-Uploads (Typ-Prüfung, Größenlimits, sichere Dateinamen).

---

## ImageService

**Datei:** `core/Services/ImageService.php`

Bildverarbeitung: Resize, Crop, WebP-Konvertierung, Thumbnail-Generierung. Nutzt die Intervention/Image-Bibliothek.

---

## MailService

**Datei:** `core/Services/MailService.php`

E-Mail-Versand über `Symfony Mime` und `Symfony Mailer` im lokalen Asset-Bundle. Unterstützt HTML-/Plain-Text-Nachrichten, Reply-To/CC/BCC-Header und Anhänge.

```php
$mail = new CMS\Services\MailService();
$mail->send('empfaenger@example.com', 'Betreff', '<p>HTML-Inhalt</p>');
```

---

## PdfService

**Datei:** `core/Services/PdfService.php`

PDF-Generierung aus HTML via DomPDF. Wird u. a. für Rechnungen und Zertifikate verwendet.

---

## PurifierService

**Datei:** `core/Services/PurifierService.php`

HTML-Bereinigung via HTMLPurifier. Entfernt potenziell gefährlichen Code und erlaubt nur sichere Tags/Attribute.

---

## RedirectService

**Datei:** `core/Services/RedirectService.php`

Verwaltet 301/302-Weiterleitungen. Admin-UI zum Anlegen und Verwalten von Redirect-Regeln.

---

## SearchService

**Datei:** `core/Services/SearchService.php`

Volltextsuche über TNTSearch. Indiziert Seiten, Beiträge und Landing Pages.

```php
$search = new CMS\Services\SearchService();
$results = $search->search('suchbegriff', ['limit' => 20]);
```

---

## SeoAnalysisService

**Datei:** `core/Services/SeoAnalysisService.php`

Analysiert einzelne Seiten auf SEO-Kriterien (Titel-Länge, Meta-Description, Heading-Struktur, Keyword-Dichte) und liefert einen Score.

---

## SiteTableService

**Datei:** `core/Services/SiteTableService.php`

Verwaltet tabellarische Daten für Site-weite Anzeigen.

---

## TranslationService

**Datei:** `core/Services/TranslationService.php`

Übersetzungssystem (i18n). Lädt Sprachdateien aus `lang/` und stellt `__('key')`-artige Übersetzungsfunktionen bereit.
