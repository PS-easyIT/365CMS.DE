# 365CMS – Services-Referenz
> **Stand:** 2026-03-16 | **Version:** 2.6.0 | **Status:** Aktuell

Vollständige Dokumentation des Service-Layers. Alle Service-Klassen liegen im Namespace `CMS\Services` unter `core/Services/`. Der `CacheManager` befindet sich im Root-Namespace `CMS` unter `core/CacheManager.php`.

---

<!-- UPDATED: 2026-03-16 -->
## 1 · Übersicht

| Klasse | Datei | Aufgabe |
|--------|-------|---------|
| `MailService` | `MailService.php` | E-Mail-Versand (Symfony Mailer, SMTP, Azure OAuth2) |
| `MailQueueService` | `MailQueueService.php` | Asynchrone Mail-Queue, Cron-Worker, Retry-Backoff |
| `MailLogService` | `MailLogService.php` | Persistente Versandprotokolle für Admin & Diagnose |
| `AzureMailTokenProvider` | `AzureMailTokenProvider.php` | XOAUTH2-Tokenbeschaffung für Microsoft 365 SMTP |
| `SearchService` | `SearchService.php` | Volltextsuche (TNTSearch, GermanStemmer) |
| `TranslationService` | `TranslationService.php` | Übersetzungssystem (i18n, Symfony Translation) |
| `MediaService` | `MediaService.php` | Datei-Upload & Medienverwaltung |
| `ImageService` | `ImageService.php` | Bildverarbeitung (Resize, WebP, Thumbnails) |
| `FileUploadService` | `FileUploadService.php` | Upload-Validierung & sichere Dateinamen |
| `SEOService` | `SEOService.php` | Schema.org, Meta-Tags, Robots.txt (melbahja/seo) |
| `SitemapService` | `SitemapService.php` | Sitemap-Generierung (melbahja/seo) |
| `IndexingService` | `IndexingService.php` | IndexNow & Google URL Notifications |
| `SeoAnalysisService` | `SeoAnalysisService.php` | SEO-Analyse & Scoring pro Seite |
| `CacheManager` | `CacheManager.php` | File-Cache, APCu L1, LiteSpeed-Integration |
| `BackupService` | `BackupService.php` | Datenbank- und Datei-Backups |
| `AnalyticsService` | `AnalyticsService.php` | Besucher-Statistiken & Auswertungen |
| `CommentService` | `CommentService.php` | Kommentar-CRUD & Moderation |
| `ContentLocalizationService` | `ContentLocalizationService.php` | Lokalisierte Basis-URIs und Pfadauflösung |
| `CoreWebVitalsService` | `CoreWebVitalsService.php` | Feldmessung für Web Vitals |
| `CookieConsentService` | `CookieConsentService.php` | Cookie-Consent-Banner (DSGVO) |
| `DashboardService` | `DashboardService.php` | Admin-Dashboard-Widget-Daten |
| `ErrorReportService` | `ErrorReportService.php` | Persistente Fehlerreports mit Audit-Logging |
| `EditorJsService` | `EditorJsService.php` | Editor.js Block-Management |
| `EditorJsRenderer` | `EditorJsRenderer.php` | Editor.js Block→HTML-Rendering |
| `EditorService` | `EditorService.php` | SunEditor-Integration |
| `FeatureUsageService` | `FeatureUsageService.php` | Datensparsame Nutzungsmetriken für Admin/Member |
| `FeedService` | `FeedService.php` | RSS-/Atom-Feeds nativ laden, validieren und cachen |
| `GraphApiService` | `GraphApiService.php` | Microsoft Graph via Client-Credentials |
| `JwtService` | `JwtService.php` | JWT-Token (firebase/php-jwt, HS256) |
| `LandingPageService` | `LandingPageService.php` | Landing-Page-Builder |
| `MediaDeliveryService` | `MediaDeliveryService.php` | Kontrollierte Auslieferung privater Uploads |
| `MemberService` | `MemberService.php` | Member-Dashboard-Logik |
| `MessageService` | `MessageService.php` | Internes Nachrichten-System (Threads, Soft-Delete) |
| `OpcacheWarmupService` | `OpcacheWarmupService.php` | Signaturgesteuerter Warmup der größten PHP-Dateien |
| `PdfService` | `PdfService.php` | PDF-Generierung via DomPDF |
| `PermalinkService` | `PermalinkService.php` | Beitrags-URL-Strukturen, Slug-Extraktion und Migrationspfade |
| `PurifierService` | `PurifierService.php` | HTML-Bereinigung via HTMLPurifier |
| `RedirectService` | `RedirectService.php` | 301/302-Weiterleitungen |
| `SettingsService` | `SettingsService.php` | Gruppierte, optional verschlüsselte Einstellungen |
| `SiteTableService` | `SiteTableService.php` | Tabellen-Verwaltung |
| `SiteTableDisplaySettings` | `SiteTable/SiteTableDisplaySettings.php` | Kanonische Tabellen-Display-Presets und Defaults |
| `StatusService` | `StatusService.php` | System-Status-Checks |
| `SystemService` | `SystemService.php` | Server-/DB-Infos fürs Admin-Panel |
| `ThemeCustomizer` | `ThemeCustomizer.php` | Theme-Einstellungen (Farben, Fonts) |
| `TrackingService` | `TrackingService.php` | Page-View-Tracking (DSGVO-konform) |
| `UpdateService` | `UpdateService.php` | CMS-Update-Prüfung (GitHub Release API) |
| `UserService` | `UserService.php` | Benutzer-Verwaltung (CRUD) |

---

<!-- UPDATED: 2026-03-08 -->
## 2 · MailService

**Datei:** `core/Services/MailService.php`
**Bibliothek:** Symfony Mime + Symfony Mailer (lokales Asset-Bundle)
**Pattern:** Singleton (`getInstance()`)

Zentraler E-Mail-Service. Unterstützt drei Transportwege:

| Transport | Konfiguration |
|-----------|--------------|
| PHP `mail()` | Fallback – keine Einstellungen nötig |
| SMTP (Benutzername/Passwort) | `use_smtp`, `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass` |
| Microsoft 365 XOAUTH2 | `smtp_auth_mode = xoauth2` + Azure-Credentials via `AzureMailTokenProvider` |

### Konfiguration

Einstellungen werden über `SettingsService` (Gruppe `mail`) geladen, Fallback auf `config/app.php`:

```php
// config/app.php (Fallback-Werte)
return [
    'mail_from'      => 'noreply@example.com',
    'mail_from_name' => '365CMS',
    'use_smtp'       => true,
    'smtp_host'      => 'smtp.example.com',
    'smtp_port'      => 587,
    'smtp_user'      => 'user@example.com',
    'smtp_pass'      => 'geheim',
];
```

### E-Mails senden

```php
$mail = CMS\Services\MailService::getInstance();

// Einfache HTML-Mail
$mail->send('empfaenger@example.com', 'Betreff', '<p>HTML-Inhalt</p>');

// Mit Optionen (CC, BCC, Reply-To, Anhänge)
$mail->send('empfaenger@example.com', 'Betreff', $htmlBody, [
    'cc'         => 'kopie@example.com',
    'bcc'        => 'blind@example.com',
    'replyTo'    => 'antwort@example.com',
    'attachments' => ['/pfad/zur/datei.pdf'],
]);

// Transport-Info abfragen
$info = $mail->getTransportInfo();
// → ['uses_smtp' => true, 'transport' => 'smtp', 'transport_label' => 'SMTP']
```

### Mail-Queue (asynchroner Versand)

`MailQueueService` verarbeitet Mails im Hintergrund via Cron mit Retry-Backoff:

```php
$queue = CMS\Services\MailQueueService::getInstance();

// Mail in Queue einreihen (statt sofort senden)
$queue->enqueue('empfaenger@example.com', 'Betreff', '<p>Inhalt</p>');

// Queue verarbeiten (via Cron-Hook cms_cron_mail_queue)
$queue->processQueue();
```

### Versandprotokoll

```php
$logs = CMS\Services\MailLogService::getInstance();

// Letzte 50 E-Mails anzeigen
$entries = $logs->getEntries(limit: 50);
```

---

<!-- UPDATED: 2026-03-08 -->
## 3 · SearchService

**Datei:** `core/Services/SearchService.php`
**Bibliothek:** TNTSearch (SQLite-Engine, `GermanStemmer`)
**Pattern:** Singleton (`getInstance()`)

Performante Volltextsuche über Pages, Posts und Plugin-Inhalte mit SQLite-basierten Indizes.

### Indexkonfiguration

Indizes werden intern über `$indexDefinitions` registriert. Jeder Index besteht aus einem SQL-Query und den zu indizierenden Feldern. Die SQLite-Indexdateien liegen unter `cache/search/`.

```php
$search = CMS\Services\SearchService::getInstance();
```

### Suche ausführen

```php
// Standard-Suche (paginiert)
$results = $search->search('suchbegriff', ['limit' => 20]);

// Ergebnis enthält: Treffer mit Highlighting, Gesamtanzahl, Suchzeit
```

### Index neu aufbauen

```php
// Alle Indizes komplett neu erstellen
$search->reindex();

// Einzelnes Dokument im Index aktualisieren (nach Seiten-Update)
$search->updateDocument($pageId);
```

### Hooks

Der SearchService nutzt `CMS\Hooks`, um Plugin-Inhalte in die Suche zu integrieren:

```php
// Plugin registriert eigenen Suchindex
CMS\Hooks::addFilter('search_index_definitions', function (array $defs) {
    $defs['my_plugin'] = [
        'query'  => 'SELECT id, title, content FROM cms_my_table WHERE status = "published"',
        'fields' => ['title', 'content'],
    ];
    return $defs;
});
```

---

<!-- UPDATED: 2026-03-08 -->
## 4 · TranslationService

**Datei:** `core/Services/TranslationService.php`
**Bibliothek:** Symfony Translation (optional, Fallback für Shared-Hosting)
**Pattern:** Singleton (`getInstance()`)

Internationalisierung mit automatischer Locale-Erkennung und Sprachdateien aus `lang/`.

### Sprachkonfiguration

Sprachdateien liegen als PHP-Arrays unter `lang/{locale}/`:

```bash
lang/
├── de/
│   ├── messages.php
│   └── admin.php
├── en/
│   ├── messages.php
│   └── admin.php
└── fr/
    └── messages.php
```

### Verwendung

```php
$i18n = CMS\Services\TranslationService::getInstance();

// Aktuelle Locale
$locale = $i18n->getLocale(); // z. B. 'de'

// Verfügbare Sprachen
$locales = $i18n->getAvailableLocales(); // ['de', 'en', 'fr']

// Übersetzen
$text = $i18n->translate('messages.welcome');
// Oder mit Platzhaltern
$text = $i18n->translate('messages.greeting', ['name' => 'Max']);

// Locale wechseln
$i18n->setLocale('en');
```

### Fallback-Mechanismus

Wenn Symfony Translation nicht verfügbar ist (Shared Hosting), wird automatisch der eingebaute Fallback-Katalog verwendet. Übersetzungsschlüssel, für die kein Eintrag existiert, werden in der Standard-Locale (`de`) gesucht.

---

<!-- UPDATED: 2026-03-08 -->
## 5 · MediaService

**Datei:** `core/Services/MediaService.php`
**Pattern:** Multi-Instance via `getInstance(string $customRoot = '')`

Verwaltet die Medienbibliothek mit Upload, Kategorien und geschützten System-Ordnern.

### System-Ordner (nicht löschbar)

`themes`, `plugins`, `assets`, `fonts`, `dl-manager`, `form-uploads`, `member`

### Datei hochladen

```php
$media = CMS\Services\MediaService::getInstance();

// Upload mit Validierung
$result = $media->upload($_FILES['file'], [
    'allowed_types' => ['image/jpeg', 'image/png', 'image/webp'],
    'max_size'      => 5 * 1024 * 1024, // 5 MB
]);
// $result enthält Dateipfad, URL, Metadaten
```

### Medienbibliothek

```php
// Dateien auflisten (paginiert, filterbar)
$files = $media->getMediaLibrary([
    'type'  => 'image',
    'page'  => 1,
    'limit' => 20,
]);

// Datei löschen
$media->delete($mediaId);
```

### Bildverarbeitung (ImageService)

Für Resize, Crop und WebP-Konvertierung wird der separate `ImageService` genutzt:

```php
$img = CMS\Services\ImageService::getInstance();

// Thumbnail generieren
$thumb = $img->generateThumbnail($filePath, 300, 200);

// WebP-Konvertierung
$webp = $img->convertToWebP($filePath);
```

### Benutzerdefinierter Upload-Root

```php
// Eigener Root-Pfad (z. B. für Plugin-Uploads)
$pluginMedia = CMS\Services\MediaService::getInstance('/var/www/uploads/my-plugin');
```

---

<!-- UPDATED: 2026-03-08 -->
## 6 · SEOService

**Datei:** `core/Services/SEOService.php`
**Bibliothek:** melbahja/seo (Schema.org, Sitemap, Indexing)
**Pattern:** Singleton (`getInstance()`)

Verwaltet Schema.org-Markup, Meta-Tags, Robots.txt und arbeitet mit `SitemapService` und `IndexingService` zusammen.

### Meta-Tags setzen

```php
$seo = CMS\Services\SEOService::getInstance();

$seo->setPageMeta([
    'title'       => 'Über uns – 365CMS',
    'description' => 'Erfahrt mehr über unser Team und unsere Mission.',
    'keywords'    => 'team, unternehmen, kontakt',
    'og_image'    => '/uploads/team.jpg',
]);
```

### Schema.org-Markup

Nutzt `Melbahja\Seo\Schema` und `Melbahja\Seo\Schema\Thing` für strukturierte Daten:

```php
// Organization-Schema generieren
$schema = $seo->getOrganizationSchema();
// → JSON-LD <script>-Block für den <head>
```

### Sitemap generieren

```php
// Sitemap (delegiert an SitemapService, nutzt Melbahja\Seo\Sitemap)
$seo->generateSitemap(); // Schreibt /sitemap.xml

// Robots.txt
$seo->generateRobotsTxt();
```

### URL-Indexierung (IndexingService)

```php
$indexer = CMS\Services\IndexingService::getInstance();

// URL bei IndexNow melden (Bing, Yandex)
$indexer->submitIndexNow('https://example.com/neue-seite');

// URL bei Google Indexing API melden
$indexer->submitGoogle('https://example.com/neue-seite');
```

### SEO-Analyse (SeoAnalysisService)

```php
$analysis = CMS\Services\SeoAnalysisService::getInstance();

// Seite analysieren (Titel, Meta, Headings, Keyword-Dichte)
$score = $analysis->analyze($pageId);
// → ['score' => 85, 'issues' => [...], 'recommendations' => [...]]
```

---

<!-- UPDATED: 2026-03-08 -->
## 7 · CacheManager

**Datei:** `core/CacheManager.php`
**Namespace:** `CMS` (nicht `CMS\Services`)
**Interface:** `CMS\Contracts\CacheInterface`
**Pattern:** Singleton (`instance()`)

Dreistufiges Caching: APCu (L1, In-Memory) → File-Cache (L2) → LiteSpeed-Integration.

### Treiber

| Treiber | Bedingung | Geschwindigkeit |
|---------|-----------|----------------|
| APCu (L1) | `apcu_*`-Funktionen + `apc.enabled`, kein CLI | Sub-Millisekunde |
| File-Cache (L2) | Immer verfügbar | Millisekunden |
| LiteSpeed | `SERVER_SOFTWARE` enthält „LiteSpeed" | Automatisch |

Cache-Verzeichnis: `cache/` (wird automatisch angelegt, `0755`).

### Verwendung

```php
$cache = CMS\CacheManager::instance();

// Wert speichern (TTL in Sekunden)
$cache->set('dashboard_stats', $data, 300); // 5 Minuten

// Wert lesen
$data = $cache->get('dashboard_stats');

// Wert löschen
$cache->delete('dashboard_stats');

// Gesamten Cache leeren
$cache->clear();
```

### Cache-Invalidierung

```php
// Nach Content-Update den betroffenen Cache-Eintrag löschen
$cache->delete('page_' . $pageId);

// Gruppen-Invalidierung via Prefix-Konvention
$cache->deleteByPrefix('seo_');
```

### LiteSpeed-Integration

Wenn LiteSpeed erkannt wird, werden automatisch passende Cache-Header gesetzt. Keine zusätzliche Konfiguration nötig.

---

<!-- UPDATED: 2026-03-08 -->
## 8 · BackupService

**Datei:** `core/Services/BackupService.php`
**Pattern:** Singleton (`getInstance()`)

Automatische Datensicherung mit Ziel-Optionen: Webspace, E-Mail (SQL-only), S3.

### Backup-Verzeichnis

Backups werden unter `backups/` gespeichert. Das Verzeichnis wird automatisch erstellt und mit `.htaccess` (`deny from all`) geschützt.

### Datenbank-Backup

```php
$backup = CMS\Services\BackupService::getInstance();

// Komplettes Datenbank-Backup (SQL, gzip-komprimiert)
$path = $backup->createDatabaseBackup();
// → 'backups/db_2026-03-08_143022.sql.gz'
```

### Datei-Backup

```php
// Bestimmte Verzeichnisse sichern (ZIP-Archiv)
$path = $backup->createFileBackup(['uploads/', 'themes/']);
// → 'backups/files_2026-03-08_143022.zip'
```

### Backup-Verwaltung

```php
// Alle verfügbaren Backups auflisten
$backups = $backup->listBackups();
// → [['file' => '...', 'type' => 'database', 'size' => 1234567, 'date' => '...'], ...]

// Backup wiederherstellen
$backup->restore($backupFile);

// Alte Backups automatisch bereinigen
$backup->cleanup(keepDays: 30);
```

### Cron-Integration

```bash
# Tägliches Datenbank-Backup via Cron
0 2 * * * php /var/www/html/cron.php --task=backup_database
```

---

<!-- UPDATED: 2026-03-16 -->
## 9 · Weitere Services

### AnalyticsService

**Datei:** `core/Services/AnalyticsService.php`

Besucher-Statistiken und Auswertungen für das Admin-Dashboard.

```php
$analytics = CMS\Services\AnalyticsService::getInstance();

$stats    = $analytics->getVisitorStats(30);  // Letzte 30 Tage
$topPages = $analytics->getTopPages(10);       // Top 10 Seiten
$today    = $analytics->getDailyCount();       // Heutiger Besuchercount
```

### CommentService

**Datei:** `core/Services/CommentService.php`

Kommentar-CRUD und Moderation für Seiten und Beiträge.

```php
$comments = CMS\Services\CommentService::getInstance();
$list = $comments->getComments($pageId, ['status' => 'approved', 'limit' => 20]);
```

### CookieConsentService

**Datei:** `core/Services/CookieConsentService.php`

Cookie-Consent-Banner und Einwilligungsverwaltung (DSGVO-konform).

### DashboardService

**Datei:** `core/Services/DashboardService.php`

Liefert Widget-Daten für das Admin-Dashboard.

```php
$dashboard = CMS\Services\DashboardService::getInstance();
$data = $dashboard->getWidgetData();
// → user_count, post_count, recent_logins, system_status, top_pages
```

### ErrorReportService

**Datei:** `core/Services/ErrorReportService.php`

Persistiert Admin-Fehlerreports in `error_reports`, normalisiert `WP_Error`-Kontext für Alerts/Form-Redirects und schreibt Audit-Einträge für neue Reports.

```php
$payload = CMS\Services\ErrorReportService::buildReportPayloadFromWpError($error, [
    'source' => '/admin/users',
    'title' => 'Benutzerverwaltung',
]);

$result = CMS\Services\ErrorReportService::getInstance()->createReport($payload);
```

### EditorJsService / EditorJsRenderer

**Dateien:** `core/Services/EditorJsService.php`, `core/Services/EditorJsRenderer.php`

Editor.js-Integration. `EditorJsService` verwaltet Block-Daten; `EditorJsRenderer` konvertiert JSON-Blöcke in HTML.

### EditorService

**Datei:** `core/Services/EditorService.php`

SunEditor-Integration für den Inhalts-Editor.

### FeedService

**Datei:** `core/Services/FeedService.php`

RSS- und Atom-Feeds werden nativ per DOM/XML geladen, abgesichert validiert und dateibasiert gecacht.

### PermalinkService

**Datei:** `core/Services/PermalinkService.php`

Kapselt Beitrags-Permalink-Presets, Token-Normalisierung, URL-Beispiele, Slug-Extraktion und Legacy-/Migrationspfade für beitragsbezogene Router- und Theme-Pfade.

```php
$permalinks = CMS\Services\PermalinkService::getInstance();
$path = $permalinks->buildPostPathFromValues('mein-beitrag', '2026-03-16 09:00:00');
$slug = $permalinks->extractPostSlugFromPath('/blog/mein-beitrag');
```

### GraphApiService

**Datei:** `core/Services/GraphApiService.php`

Microsoft-Graph-Zugriff via Client-Credentials-Flow (Azure AD).

### JwtService

**Datei:** `core/Services/JwtService.php`

JWT-Token-Verwaltung (firebase/php-jwt, HS256). Siehe [API-Referenz](API-REFERENCE.md).

```php
$jwt = CMS\Services\JwtService::getInstance();
$token = $jwt->generate(['user_id' => 42]);
$payload = $jwt->validate($token);
```

### LandingPageService

**Datei:** `core/Services/LandingPageService.php`

Landing-Page-Builder mit Block-basiertem Layout.

### MemberService

**Datei:** `core/Services/MemberService.php`

Member-Dashboard-Logik: Profil, Benachrichtigungen, Aktivität.

```php
$member = CMS\Services\MemberService::getInstance();
$data = $member->getMemberDashboardData($userId);
$member->updateProfile($userId, $_POST);
```

### MessageService

**Datei:** `core/Services/MessageService.php` (seit v2.0.0)

Internes Nachrichten-System: Threads, Soft-Delete, Empfänger-Autocomplete.

```php
$msg = CMS\Services\MessageService::getInstance();
$inbox = $msg->getInbox($userId, 20, 0);
$id = $msg->send($senderId, $recipientId, 'Betreff', 'Text', $parentId);
$count = $msg->getUnreadCount($userId);
```

### PdfService

**Datei:** `core/Services/PdfService.php`

PDF-Generierung aus HTML via DomPDF (Rechnungen, Zertifikate).

### PurifierService

**Datei:** `core/Services/PurifierService.php`

HTML-Bereinigung via HTMLPurifier. Entfernt unsichere Tags/Attribute.

### RedirectService

**Datei:** `core/Services/RedirectService.php`

301/302-Weiterleitungen. Admin-UI zum Anlegen und Verwalten von Redirect-Regeln.

### SettingsService

**Datei:** `core/Services/SettingsService.php`

Gruppierte, optional verschlüsselte Laufzeit-Einstellungen aus der Datenbank.

```php
$settings = CMS\Services\SettingsService::getInstance();
$value = $settings->get('mail', 'smtp_host', 'localhost');
$settings->set('mail', 'smtp_host', 'smtp.example.com');
```

### SiteTableService

**Datei:** `core/Services/SiteTableService.php`

Tabellarische Daten-Verwaltung für Site-weite Anzeigen.

### SiteTableDisplaySettings

**Datei:** `core/Services/SiteTable/SiteTableDisplaySettings.php`

Definiert die kanonischen Frontend-Display-Defaults für Content-Tabellen, validiert aktivierte Stilvarianten und begrenzt freigeschaltete Presets auf vier auswählbare Optionen.

```php
$settings = CMS\Services\SiteTable\SiteTableDisplaySettings::normalize($_POST);
$styles = CMS\Services\SiteTable\SiteTableDisplaySettings::styleOptions();
```

### StatusService

**Datei:** `core/Services/StatusService.php`

System-Status-Checks: DB-Verbindung, PHP-Version, Extensions, Berechtigungen.

```php
$status = CMS\Services\StatusService::getInstance();
$report = $status->getSystemStatus();
```

### SystemService

**Datei:** `core/Services/SystemService.php`

Server-/System-Informationen: PHP-Version, MySQL, OS, Upload-Limits.

```php
$system = CMS\Services\SystemService::getInstance();
$info = $system->getServerInfo();
$dbStats = $system->getDatabaseStats();
```

### ThemeCustomizer

**Datei:** `core/Services/ThemeCustomizer.php`

Theme-Einstellungen (Farben, Fonts) über die Admin-UI.

```php
$customizer = CMS\Services\ThemeCustomizer::getInstance();
$color = $customizer->getSetting('cms-default', 'colors', 'primary', '#007bff');
$customizer->saveSetting('cms-default', 'colors', 'primary', '#ff6600');
```

### TrackingService

**Datei:** `core/Services/TrackingService.php`

Page-View-Tracking (DSGVO-konform, IP nur als Hash, keine externen Tools).

```php
$tracker = CMS\Services\TrackingService::getInstance();
$tracker->trackPageView(
    url: '/experten',
    referrer: $_SERVER['HTTP_REFERER'] ?? '',
    userAgent: $_SERVER['HTTP_USER_AGENT'] ?? ''
);
```

### UpdateService

**Datei:** `core/Services/UpdateService.php`

CMS-Update-Prüfung via GitHub Release API (6h-Cache).

```php
$updater = CMS\Services\UpdateService::getInstance();
$updates = $updater->checkForUpdates(); // ['core' => [...], 'plugins' => [...]]
$updater->updateCore('2.5.5');
```

### UserService

**Datei:** `core/Services/UserService.php`

Vollständige Benutzerverwaltung (CRUD, Meta-Daten, Filterung).

```php
$users = CMS\Services\UserService::getInstance();
$userId = $users->createUser([
    'username' => 'max', 'email' => 'max@example.com',
    'password' => 'Sicher123!', 'role' => 'member',
]);
$user = $users->getUserById($userId);
$users->updateUser($userId, ['display_name' => 'Max M.']);
$users->deleteUser($userId);
```
