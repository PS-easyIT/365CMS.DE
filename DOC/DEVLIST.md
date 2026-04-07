# 365CMS – DEVLIST

> Stand: 2026-04-07  
> Zielgruppe: Entwickler, Integratoren, Auditoren, Betreiber  
> Version: passend zum aktuellen 365CMS-Stand 2.9.0

Diese Datei ist die zentrale technische Entwicklerreferenz für das laufende 365CMS. Sie soll möglichst viel Wissen an einer Stelle bündeln. Als begleitende Einstiegspunkte sind nur die Doku-Übersicht unter [`DOC/INDEX.md`](INDEX.md) und die Strukturübersicht unter [`DOC/FILELIST.md`](FILELIST.md) vorgesehen.

---

## 1. Systembild in einem Satz

365CMS ist ein modulbasiertes PHP-CMS mit klarer Trennung zwischen Core, Admin, Member-Bereich, Theme-Laufzeit, Plugin-Laufzeit, Service-Layer, Routing, Audit/Security, SEO sowie Performance-/Monitoring-Funktionen; die produktive Laufzeit wird aus dem Verzeichnis `CMS/` gebootet.

---

## 2. Repository- und Laufzeitmodell

### 2.1 Maßgebliche Laufzeit

Die relevante Runtime liegt im Repository `365CMS.DE` unter `CMS/`.

Wichtig:

- `365CMS.DE-THEME/` ist ein Quell-/Pflege-Repository für Themes, aber **nicht automatisch** die aktive Laufzeitquelle.
- `365CMS.DE-PLUGINS/` ist ein Quell-/Pflege-Repository für Plugins, aber **nicht automatisch** die aktive Laufzeitquelle.
- Die produktive Theme-Laufzeit nutzt `CMS/themes/<slug>/`.
- Die produktive Plugin-Laufzeit nutzt `CMS/plugins/<slug>/`.
- Der Bootstrap lädt Theme und Plugins nur aus diesen Runtime-Pfaden.

### 2.2 Relevante Top-Level-Bereiche

- `CMS/` – produktive Anwendung
- `DOC/` – Projektdokumentation
- `ASSETS/` – Entwicklungs-/Vendor-Kontext außerhalb der produktiven `CMS/assets/`-Laufzeit
- `tests/` – Testkontext
- `var/` – Laufzeitnahe Artefakte und Logs außerhalb des Webroots
- `STAGING/`, `BACKUP/`, `IMAGES/` – Betriebs-/Migrations-/Ablagekontext

### 2.3 Architektur-Schichten

Die Systemlogik ist grob in folgende Schichten gegliedert:

1. **Konfiguration** – Konstanten, Pfade, Betriebsparameter
2. **Bootstrap/Core** – Startlogik, Container, DB, Auth, Security, Hooks, Router
3. **Services** – SEO, Suche, Mail, Upload, Medien, Analytics, Tracking, PDF usw.
4. **Module** – Admin-Funktionen nach Domänen organisiert
5. **Themes** – Template-Rendering, Menüs, Customizer-nahe Ausgabe
6. **Plugins** – zusätzliche, aktivierbare Erweiterungen
7. **Member & Public UI** – Frontend- und Benutzerfunktionen

---

## 3. Bootstrap, Betriebsmodi und Startpfad

### 3.1 Zentrale Bootstrap-Klasse

Der Kernstart erfolgt über `CMS/core/Bootstrap.php`.

Die Bootstrap-Klasse übernimmt unter anderem:

- Erkennung des Betriebsmodus
- Laden der Core-Abhängigkeiten
- Prüfung auf Plattform-/PHP-Kompatibilität gebündelter Bibliotheken
- Aufbau des DI-Containers
- Start von Datenbank, Security, Auth, Cache, Loggern
- Ausführung der Migrationen
- Laden der aktiven Plugins
- Laden des aktiven Themes im Web-Modus
- Initialisierung der Hooks und Routing-Phase

### 3.2 Betriebsmodi

`Bootstrap::detectMode()` unterscheidet aktuell folgende Modi:

- `cli` – wenn `PHP_SAPI === 'cli'`
- `api` – wenn der Request mit `/api/` beginnt
- `admin` – wenn der Request mit `/admin/` beginnt oder exakt `/admin` ist
- `web` – Standard für normale Frontend-Anfragen

### 3.3 Modusabhängiges Laden

Nicht alles wird in jedem Modus geladen:

- Router nur außerhalb von `cli`
- ThemeManager nur in `web` und `admin`
- tatsächliches `loadTheme()` nur im `web`-Modus
- einige Frontend-spezifische Services nur im `web`-Modus
- Analytics nur gezielt im `admin`-Modus

### 3.4 Plattformprüfung

Beim Booten wird geprüft, ob gebündelte Libraries eine höhere PHP-Version verlangen als die deklarierte Zielplattform. Bei Inkonsistenzen bricht 365CMS fail-closed mit `503` oder CLI-Fehler ab.

Das schützt vor halb startenden Installationen mit „läuft irgendwie, bis es knallt“-Charakter.

---

## 4. Konfiguration, Konstanten und feste Werte

Die zentrale Konfiguration liegt in `CMS/config/app.php`.

### 4.1 Wichtige Konstanten

| Konstante | Wert / Bedeutung |
|---|---|
| `ABSPATH` | Basis von `CMS/` |
| `CORE_PATH` | `ABSPATH . 'core/'` |
| `THEME_PATH` | `ABSPATH . 'themes/'` |
| `PLUGIN_PATH` | `ABSPATH . 'plugins/'` |
| `UPLOAD_PATH` | `ABSPATH . 'uploads/'` |
| `ASSETS_PATH` | `ABSPATH . 'assets/'` |
| `LOG_PATH` | bevorzugt `var/logs/`, sonst Fallback auf Temp-Pfad |
| `CMS_ERROR_LOG` | `${LOG_PATH}/error.log` |
| `DEFAULT_THEME` | `cms-default` |
| `DB_PREFIX` | Standard `cms_` |
| `SESSIONS_LIFETIME` | `7200` Sekunden = 2 Stunden |
| `MAX_LOGIN_ATTEMPTS` | `5` |
| `LOGIN_TIMEOUT` | `300` Sekunden = 5 Minuten |
| `CMS_HTTPS_REDIRECT_STRATEGY` | Standard `upstream` |
| `CMS_HSTS_MODE` | Standard `https-only` |
| `CMS_HSTS_MAX_AGE` | `31536000` Sekunden |
| `JWT_TTL` | `3600` Sekunden |
| `LDAP_DEFAULT_ROLE` | `member` |

### 4.2 Konfigurationsprinzipien

- `config/app.php` ist als Template vorbereitet und wird durch den Installer vervollständigt.
- Security-Keys sollen per `random_bytes()` generiert werden.
- Keine echten Zugangsdaten oder Secrets gehören in die Versionsverwaltung.
- Logging wird, wenn möglich, außerhalb des Webroots in `var/logs/` abgelegt.
- `SITE_URL` darf nicht mit einem Unterverzeichnis definiert werden.

### 4.3 Debug-Verhalten

- `CMS_DEBUG = false` ist der sichere Produktivzustand.
- Im Debug-Modus werden Error Reporting und sichtbare Fehler aktiviert.
- CSP läuft im Debug-Modus in `Report-Only` statt enforced.
- HSTS wird im Debug-Modus nicht aggressiv erzwungen.

---

## 5. Dependency Injection und zentrale Core-Komponenten

### 5.1 Container

`CMS/core/Container.php` bildet den zentralen Service-Container.

Typische Muster:

- `bindInstance()` für konkrete Instanzen
- `singleton()` für lazy Services
- zentrale Alias-Namen wie `db`, `logger`, `cache`, `mail`, `search`, `seo`

### 5.2 Kernkomponenten mit hoher Relevanz

- `Database`
- `Security`
- `Auth`
- `Logger`
- `AuditLogger`
- `Hooks`
- `Router`
- `PluginManager`
- `ThemeManager`
- `CacheManager`
- `MigrationManager`
- `SchemaManager`

### 5.3 Lazy-Load-Strategie

Viele Services werden erst bei erstem Zugriff instanziiert. Das reduziert den Initialisierungs-Overhead und verhindert unnötige Arbeit pro Request.

Beispiele:

- `PurifierService`
- `MailService`
- `MailQueueService`
- `SearchService`
- `ImageService`
- `SEOService`
- `ThemeCustomizer`
- `PdfService`

---

## 6. Sicherheitsarchitektur

365CMS arbeitet nach einem klaren Defense-in-Depth-Ansatz. Es gibt nicht „den einen Schutz“, sondern mehrere Schutzschichten.

### 6.1 Sicherheitsziele

- Schutz vor CSRF
- Schutz vor XSS
- Schutz vor SQL Injection
- Schutz vor Session Fixation und Session Hijacking
- Schutz vor unsicheren Theme-/Plugin-Bootstraps
- Schutz vor Rate-Limit-basierten Login-Angriffen
- Schutz durch Security Headers und Transporthärtung
- Nachvollziehbarkeit durch Audit Logging

### 6.2 CSRF

Die CSRF-Logik sitzt in `CMS/core/Security.php`.

Eigenschaften:

- Token-Generierung pro Aktion
- Standardgültigkeit: 1 Stunde
- One-shot-Invaliderung nach erfolgreicher Prüfung
- zusätzliche persistente Prüfvariante für Spezialfälle mit vielen Folge-Requests

Wichtig für Entwickler:

- Erfolgreich verifizierte Standard-CSRF-Tokens werden gelöscht.
- Wenn eingebettete Admin-Shells bereits verifiziert haben, darf die Unterkomponente nicht blind denselben Token erneut prüfen.
- Formulare und AJAX-Endpunkte brauchen immer einen sauberen CSRF-Vertrag.

### 6.3 Sessions

Beim Session-Start werden u. a. gesetzt:

- `session.cookie_httponly = 1`
- `session.cookie_secure = 1` bei HTTPS
- `session.use_strict_mode = 1`
- Session-ID-Regeneration beim Initialisieren der Session

Das minimiert Session-Fixation-Risiken.

### 6.4 Password Hashing

Passwörter werden mit `PASSWORD_BCRYPT` und Cost `12` behandelt. Historisch schwache Verfahren wie MD5 oder SHA1/SHA256 als Passwortspeicher sind nicht vorgesehen.

### 6.5 Rate Limiting

Es gibt zwei Ebenen:

1. Session-basiertes Fallback-Rate-Limit
2. Datenbankbasiertes Rate-Limit über `login_attempts`

Das DB-basierte Rate-Limit berücksichtigt:

- IP-Adresse
- Aktion, z. B. `login`
- Zeitfenster
- erlaubte Maximalversuche

Standardwerte:

- `MAX_LOGIN_ATTEMPTS = 5`
- `LOGIN_TIMEOUT = 300`

### 6.6 Security Headers

Ausgegeben werden unter anderem:

- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: geolocation=(), microphone=(), camera=()`
- `Cross-Origin-Opener-Policy: same-origin`
- `Cross-Origin-Resource-Policy: same-site`
- `Strict-Transport-Security` bei aktivem HTTPS und Nicht-Debug
- `Content-Security-Policy` bzw. `Content-Security-Policy-Report-Only`

### 6.7 CSP und Nonce-Modell

Die CSP arbeitet nonce-basiert.

Merkmale:

- pro Request generierte Nonce
- Einbau in Header und Templates
- keine pauschalen `unsafe-inline`-Freifahrtscheine
- Trusted-Types-Regeln sind vorgesehen
- im Debug-Modus Report-Only

### 6.8 XSS-Schutz

Die XSS-Abwehr ist mehrschichtig:

- konsequentes `htmlspecialchars()` in Templates
- `Security::escape()` als Hilfsweg
- Sanitizing von Eingaben je nach Typ
- spezialisierte HTML-Reinigung über `PurifierService`
- CSP als zusätzliche Laufzeitbarriere

### 6.9 SQL-Injection-Schutz

Die DB-Schicht ist PDO-basiert und erwartet Prepared Statements statt String-Bastelei.

Regeln:

- niemals Benutzereingaben direkt in SQL interpolieren
- immer `prepare()` + `execute()`
- Tabellennamen nur aus vertrauenswürdigem, internem Kontext ableiten

### 6.10 Theme- und Plugin-Härtung

**Themes**

- Theme-Pfade werden via `realpath()` gegen Path Traversal validiert.
- `functions.php` eines Themes wird auf gefährliche Funktionen geprüft.
- Bei Ladefehlern kann ein Rollback auf `DEFAULT_THEME` erfolgen.

**Plugins**

- Aktivierung prüft Bootstrap-Datei, Abhängigkeiten und Sicherheitsmuster.
- Sicherheits-Scan blockiert u. a. nackte Aufrufe von `eval`, `exec`, `shell_exec`, `system`, `passthru`, `proc_open`, `popen`, `pcntl_exec`.
- ZIP-Installationen prüfen auf unsichere Archivpfade.

### 6.11 Upload-Sicherheit

Die Upload-Pipeline wird durch Services abgesichert. Typische Sicherungen sind:

- MIME-/Dateitypprüfung
- kontrollierte Zielpfade
- sichere Dateinamen bzw. Umbenennungen
- Trennung in Upload-Verzeichnisse
- keine Vertrauensannahme nur anhand der Dateiendung

### 6.12 Audit Logging

Sicherheitsrelevante Vorgänge werden über `AuditLogger` protokolliert.

Typische Kategorien:

- Security
- Plugin
- Theme
- Login/Auth
- Systemaktionen

Das Audit Log ist essenziell für Fehleranalyse, Compliance und Incident Review.

---

## 7. Authentifizierung, MFA, Passkeys, LDAP, JWT

### 7.1 Auth-Stack

365CMS unterstützt mehrere Authentifizierungsbausteine:

- klassische Session-/Passwort-Anmeldung
- MFA/TOTP
- Backup-Codes
- Passkeys/WebAuthn
- LDAP
- JWT für API-nahe Szenarien

### 7.2 Kanonische Auth-Routen

Die Core-Authentifizierung läuft aktuell über:

- `/cms-login`
- `/cms-register`
- `/cms-password-forgot`

Diese Routen sind core-seitig verankert und nicht an ein einzelnes Frontend-Theme gekoppelt.

### 7.3 Routing der Auth-Seiten

Die Public-Router-Registrierung umfasst GET/POST für Login, Register und Forgot Password. Damit ist die Auth-Strecke nicht bloß ein Template-Trick, sondern fester Teil des Public-Routings.

### 7.4 Passkeys/WebAuthn

Passkeys werden über eigene Auth-Komponenten verwaltet. Das System unterstützt damit moderne phish-resistente Anmeldepfade.

### 7.5 LDAP

LDAP ist optional und konfigurierbar über:

- `LDAP_HOST`
- `LDAP_PORT`
- `LDAP_BASE_DN`
- `LDAP_USERNAME`
- `LDAP_PASSWORD`
- `LDAP_USE_SSL`
- `LDAP_USE_TLS`
- `LDAP_FILTER`
- `LDAP_DEFAULT_ROLE`

### 7.6 JWT

JWT-Konstanten:

- `JWT_SECRET`
- `JWT_TTL`
- `JWT_ISSUER`

Wenn `JWT_SECRET` leer ist, kann ein Fallback auf `AUTH_KEY` erfolgen. Für produktive API-Nutzung ist ein dediziertes Secret sauberer.

---

## 8. Datenbank, Schema und Migrationen

### 8.1 Grundmodell

365CMS verwendet ein relationales Schema mit `utf8mb4` und InnoDB-orientiertem Design.

Wichtige Eigenschaften:

- Key-Value-Konfiguration über `cms_settings`
- Sessions und Login-Attempts in Tabellenform
- Content in separaten Tabellen für Seiten, Beiträge, Revisionen, Taxonomien
- SEO-/Redirect-/404-Daten in eigenen Tabellen
- Audit-/Analyse-/Systemdaten in dedizierten Strukturen

### 8.2 SchemaManager vs. MigrationManager

- `SchemaManager` kümmert sich um idempotente Grundstrukturen
- `MigrationManager` führt inkrementelle Versionsmigrationen aus

Das Ziel ist, Neuinstallation und Bestandsmigration nicht zu vermischen.

### 8.3 Installer-Fallback

`install.php` enthält zusätzlich eine Tabelleninitialisierung als Installationspfad. Diese Logik muss mit dem eigentlichen Schema-/Migrationsmodell synchron bleiben.

### 8.4 Kritische Tabellenklassen

Typische Kernobjekte:

- `cms_settings`
- `cms_sessions`
- `cms_users`
- `cms_user_meta`
- `cms_pages`
- `cms_posts`
- `cms_comments`
- `cms_audit_log`
- `cms_redirect_rules`
- `cms_not_found_logs`
- `cms_cache`
- `login_attempts`

### 8.5 Einstellungen als Persistenzzentrum

`cms_settings` ist eine zentrale Schaltstelle für:

- aktives Theme
- aktive Plugins
- Menükonfigurationen
- Theme-/Site-Optionen
- SEO-Einstellungen
- Systemoptionen

Das ist bequem, aber auch ein Grund, Änderungen an Settings mit Respekt zu behandeln. Ein einzelner falscher Key kann überraschend viel Wirkung entfalten.

### 8.6 Fremdschlüssel und Namenskonventionen

Bei Plugin- oder Modultabellen müssen Foreign-Key-Namen schemaweit eindeutig sein. Generische Constraint-Namen sind fehleranfällig, speziell bei frischen Installationen oder mehrfachen Deployments.

---

## 9. Content-Modell und Mehrsprachigkeit

### 9.1 Seiten und Beiträge

Content wird im Kern vor allem über `cms_pages` und `cms_posts` verwaltet.

### 9.2 Lokalisierung

Das System verwendet für Englisch zusätzliche Felder mit `_en`-Suffix, z. B.:

- `title_en`
- `slug_en`
- `content_en`

Das ist keine voll generische Translation Engine pro Feldfamilie, sondern ein pragmatisches zweisprachiges Schema mit starker DE/EN-Ausrichtung.

### 9.3 Revisionen

Revisionen werden gesondert gespeichert, typischerweise JSON-basiert. Das ermöglicht Wiederherstellung und Nachvollziehbarkeit.

### 9.4 Kategorien, Tags und Beziehungen

Beiträge arbeiten mit Kategorisierung/Tagging über Beziehungstabellen und Admin-Module. Diese Logik ist relevant für Listings, SEO, Suche und Archive.

### 9.5 Editoren

Es gibt mehrere Editor-Kontexte:

- `Editor.js` für Block-Editing
- `SunEditor`/EditorService-Kontext für weitere Content-Pfade

Wichtig:

- sichtbare Block-Buttons im Live-Admin-Editor müssen nicht nur in Asset-Services, sondern auch im Live-Admin-JavaScript gepflegt werden
- Editor-UI und Render-Logik sind nicht automatisch dasselbe

---

## 10. Routing und Request-Fluss

### 10.1 Router-Familien

Das Routing ist aufgeteilt in:

- `AdminRouter`
- `PublicRouter`
- `ThemeRouter`
- `MemberRouter`
- `ApiRouter`

### 10.2 Grober Ablauf

1. Bootstrap erkennt Modus
2. Core und Services werden geladen
3. Plugins registrieren Hooks/Routen
4. Router dispatcht Anfrage
5. Theme- bzw. Public-/Member-/Admin-Logik rendert Ergebnis

### 10.3 ThemeRouter

Der ThemeRouter entscheidet, welche Templates für Seiten, Beiträge, Archive und Sonderfälle gezogen werden.

### 10.4 Admin-Pfade

Admin-Pfade leben unter `/admin/*`. Der Admin-Bereich arbeitet stark mit Modulen, View-Dateien und Shell-/Partial-Strukturen.

### 10.5 Member-Pfade

Member-Funktionalität liegt unter `/member/*`.

### 10.6 API-Pfade

API-Pfade hängen unter `/api/*`.

---

## 11. Service-Layer

Der Service-Layer in `CMS/core/Services/` ist einer der wichtigsten Stabilitäts- und Erweiterungspunkte.

### 11.1 Service-Familien

**Mail & Kommunikation**

- `MailService`
- `MailQueueService`
- `MailLogService`
- `AzureMailTokenProvider`
- `GraphApiService`

**SEO & Indexing**

- `SEOService`
- `SeoAnalysisService`
- `RedirectService`
- `IndexingService`
- SEO-Unterservices

**Suche**

- `SearchService`

**Medien & Upload**

- `MediaService`
- `MediaDeliveryService`
- `ImageService`
- `FileUploadService`
- Media-Unterservices

**Editoren & Content**

- `EditorJsService`
- `EditorJsRenderer`
- `EditorService`
- `CommentService`
- `PdfService`

**Performance & Betrieb**

- `CoreWebVitalsService`
- `OpcacheWarmupService`
- `BackupService`
- `SystemService`
- `StatusService`
- `UpdateService`

**Member / Messaging / Tracking**

- `MemberService`
- `MessageService`
- `TrackingService`
- `FeatureUsageService`
- `AnalyticsService`

### 11.2 Service-Prinzipien

- möglichst kapselnde Fachlogik statt verstreuter Controller-Logik
- lazy instanziert, wenn sinnvoll
- zentrale Verwendung über Container oder `getInstance()`-Muster
- wiederverwendbar zwischen Admin, Public, Member und Plugins

---

## 12. SEO, Redirects, 404 und Sichtbarkeit

### 12.1 SEO-Bereich

365CMS enthält eine ernstzunehmende SEO-Schicht und nicht nur ein Metabox-Alibi.

### 12.2 Kernfunktionen

- Meta-Daten-Verwaltung
- Social/OpenGraph/Twitter-Kontext
- Schema.org-Ausgabe
- Sitemap-Erzeugung
- technische SEO-Analyse
- Redirect-Management
- 404-Erfassung
- IndexNow-/Indexing-Unterstützung

### 12.3 Redirect-Management

Das Redirect-System verwaltet Regeln für z. B. 301/302-Weiterleitungen.

Wichtig für Weiterentwicklungen:

- Redirect-Logik und 404-Monitoring teilen sich Frontend-/Admin-JavaScript
- Bugfixes dürfen nicht nur in einer einzelnen Admin-Seite landen, wenn die tatsächliche Funktionalität im gemeinsamen Script sitzt

### 12.4 404-Monitor

404er werden protokolliert, damit häufige Fehlpfade erkannt und in Redirect-Regeln überführt werden können.

### 12.5 Robots und Sitemap

Die Robots-/Sitemap-Logik ist systemweit relevant. Änderungen an URL-Regeln, Publishing oder Visibility-Einstellungen müssen diese Pfade mitdenken.

### 12.6 Suchindex

Die interne Suche muss bei mehrsprachigem Content sowohl Standard- als auch `_en`-Felder berücksichtigen. Außerdem müssen die korrekten Save-Hooks verwendet werden, damit der Index nach Änderungen aktuell bleibt.

---

## 13. Plugin-System

### 13.1 Grundprinzip

Aktive Plugins werden über den `PluginManager` geladen.

### 13.2 Quelle aktiver Plugins

Die Liste aktiver Plugins kommt aus `cms_settings`, Key `active_plugins`.

### 13.3 Bootstrap-Vertrag

Für ein Plugin wird eine Bootstrap-Datei im Muster erwartet:

- `CMS/plugins/<slug>/<slug>.php`

### 13.4 Protected Plugins

Aktuell ist mindestens `cms-importer` als geschütztes Plugin hinterlegt und darf nicht beliebig gelöscht werden.

### 13.5 Aktivierungslogik

Bei Aktivierung werden geprüft:

- Existenz der Bootstrap-Datei
- CMS-Versionserfordernisse
- Plugin-Abhängigkeiten
- Sicherheitsmuster im Code

### 13.6 Deaktivierung fehlender Plugins

Wenn ein Plugin als aktiv gespeichert ist, aber im Runtime-Pfad fehlt, wird es automatisch aus dem aktiven Satz entfernt und auditiert.

### 13.7 Plugin-Installation per ZIP

Installationen berücksichtigen:

- MIME-/Typprüfung
- Größenlimit
- ZIP-Öffnung
- Prüfung gegen Path Traversal / ZIP Slip

### 13.8 Konsequenz für Entwicklung

Ein Plugin im Quellrepo `365CMS.DE-PLUGINS/` ist **nicht** automatisch live. Es muss in die Runtime `CMS/plugins/` gelangen, bevor der Core es bootet.

---

## 14. Theme-System

### 14.1 Grundprinzip

Der `ThemeManager` verwaltet aktives Theme, Theme-Wechsel, Rendering, Menüs und bestimmte Site-/Customizer-bezogene Ausgaben.

### 14.2 Aktives Theme

Das aktive Theme kommt primär aus `cms_settings`, Key `active_theme`.

### 14.3 Laufzeitpfad

Kanonisch ist:

- `CMS/themes/<slug>/`

Nicht kanonisch für die Live-Laufzeit:

- `365CMS.DE-THEME/<slug>/`

### 14.4 Theme-Sicherheitschecks

Vor Laden/Wechsel werden u. a. berücksichtigt:

- `realpath()`-Prüfung
- Syntaxprüfung von PHP-Dateien
- Scan auf gefährliche Funktionen
- Rollback auf `DEFAULT_THEME` bei Fehlern

### 14.5 Rendering

Der ThemeManager rendert:

- Header
- Haupttemplate
- Footer

zusammen mit Hooks wie:

- `before_header`
- `after_header`
- `before_footer`
- `body_end`
- `after_footer`

### 14.6 Theme-Settings und Menüs

Theme-nahe Einstellungen werden lazy geladen. Menüs kommen aus Settings und teilweise aus `theme.json`-Fallbacks.

### 14.7 Favicon und Custom Styles

Der ThemeManager rendert globale Favicons und Customizer-nahe CSS-Variablen auf Basis gespeicherter Optionen.

---

## 15. Admin-Architektur

### 15.1 Aufbau

Der Admin-Bereich besteht aus:

- Einstiegsdateien unter `CMS/admin/*.php`
- Modulen unter `CMS/admin/modules/*`
- Views unter `CMS/admin/views/*`
- Partials unter `CMS/admin/partials/*`

### 15.2 Große Modulgruppen

- Dashboard
- Kommentare
- Hub-Sites
- Landing-Pages
- Recht / DSGVO / Cookies
- Medien
- Member-Dashboard-Konfiguration
- Menüs
- Seiten
- Beiträge
- Plugins / Marketplace
- SEO / Redirects / Performance / Analytics
- Sicherheit / Audit / Firewall / Antispam
- System / Updates / Diagnose / Doku / Mail / Support
- Themes / Fonts / Design / Editor / Marketplace
- Benutzer / Gruppen / Rollen / Settings
- Subscriptions / Orders / Packages

### 15.3 Shell-Muster

Wichtige Shell-/Routing-Teile sind z. B.:

- `section-page-shell`
- `post-action-shell`
- `redirect-alias-shell`

Diese Shells kapseln wiederkehrende Logik wie Routing, Form-Handling, CSRF-Verifikation, View-Einbettung und Redirects.

### 15.4 Konsequenz für Erweiterungen

Neue Admin-Funktionalität sollte möglichst als Modul plus View sauber integriert werden, nicht als monolithische Einzeldatei mit Inline-Komplettlogik.

---

## 16. Member-Bereich

### 16.1 Grundbereiche

Der Member-Bereich enthält u. a.:

- Dashboard
- Profil
- Sicherheit
- Subscription
- Medien
- Nachrichten
- Benachrichtigungen
- Datenschutz
- Favoriten

### 16.2 Member-Medien

Persönliche Medien leben benutzerbezogen und sind relevant für Datenschutz, Speicherbudget und Zugriffslogik.

### 16.3 Member-Sicherheit

Der Member-Bereich ist nicht nur „Profilseite mit netter Farbe“, sondern enthält sicherheitsrelevante Funktionen wie Passwort, MFA, Sessions und Privatsphäreoptionen.

---

## 17. Hooks- und Event-System

### 17.1 Hook-Kern

`CMS/core/Hooks.php` liefert das Erweiterungsrückgrat.

### 17.2 Wichtige Hook-Klassen

- Lifecycle-Hooks
- Content-Hooks
- User-/Auth-Hooks
- Admin-Hooks
- Theme-/Render-Hooks
- Cron-/Queue-Hooks

### 17.3 Beispiele wichtiger Events

- `cms_init`
- `cms_init_<mode>`
- `plugins_loaded`
- `plugin_loaded`
- `plugin_activated`
- `plugin_deactivated`
- `theme_loaded`
- `cms_before_route`
- `register_routes`
- `cms_after_route`
- `cms_after_page_save`
- `cms_after_post_save`
- `cms_cron_mail_queue`

### 17.4 Entwicklungsregel

Wenn Folgefunktionen an Content- oder Auth-Vorgänge gekoppelt sind, müssen exakt die real ausgelösten Hooks verwendet werden. Falsche Hook-Namen führen gern zu „funktioniert lokal nicht reproduzierbar, aber fühlt sich kaputt an“.

---

## 18. Performance, Cache und Monitoring

### 18.1 Cache-Strategie

`CacheManager` ist die zentrale Cache-Schicht.

Genannt werden:

- L1 APCu, wenn verfügbar
- L2 Dateisystem
- optionale Integrationslogik für performante Cache-Pfade

### 18.2 OPcache-Warmup

`OpcacheWarmupService` kann nach Deployments relevante PHP-Dateien vorwärmen.

### 18.3 Core Web Vitals

`CoreWebVitalsService` unterstützt Performance-/Metrik-Erfassung im Frontend.

### 18.4 Monitoring-Endpunkte und Admin-Monitoring

Im Admin existieren separate Monitoring-/Diagnosepfade, u. a. für:

- Cron-Status
- Disk Usage
- Email Alerts
- Health Checks
- Response Time
- Scheduled Tasks

### 18.5 Mail Queue

Die Mailqueue ist asynchron gedacht und wird über Cron-/Hook-Logik verarbeitet. Das ist wichtig für Stabilität, Nutzererlebnis und Retry-Verhalten.

### 18.6 Performance-Denke für Entwickler

- keine unnötigen DB-Abfragen im Bootstrap
- lazy Loading nutzen
- wiederkehrende Settings bündeln
- große UI- oder Editor-Skripte zentral pflegen
- Caches nach strukturellen Änderungen sauber invalidieren

---

## 19. Cron, Hintergrundjobs und Betriebsautomation

### 19.1 Cron-Grundidee

365CMS besitzt eine Cron-/Scheduled-Task-Schicht für wiederkehrende Arbeiten.

### 19.2 Typische Aufgaben

- Verarbeitung der Mailqueue
- Health-/Monitoring-Prüfungen
- Wartung und Aufräumjobs
- potenzielle Reindex-/Analyse-/Synchronisationsaufgaben

### 19.3 Wichtige Entwicklerregel

Cron-Logik muss idempotent und fehlertolerant sein. Hintergrundjobs dürfen bei Teilfehlern nicht das Gesamtsystem blockieren.

### 19.4 Beobachtbarkeit

Cron-Status und verwandte Systemzustände sollten immer über Audit/Logs/Admin-Diagnose nachvollziehbar bleiben.

---

## 20. Medien, Dateien und Assets

### 20.1 Asset-Struktur

Die produktive Asset-Laufzeit liegt unter `CMS/assets/`.

Dort liegen u. a.:

- CSS-Dateien für Admin, Member, Hub, Consent
- JavaScript für Admin-Workflows, SEO, Menüs, Benutzer, Editoren
- gebündelte Drittbibliotheken

### 20.2 Externe Libraries

Im Projektkontext tauchen u. a. Bibliotheken auf für:

- Editor.js
- SunEditor
- Grid.js
- PhotoSwipe
- Dompdf / TCPDF
- HTMLPurifier
- JWT
- WebAuthn
- LDAP
- Symfony Mailer/Mime/Translation

### 20.3 Wichtige Unterscheidung

`CMS/assets/` ist die Runtime-Basis. Das Top-Level-`ASSETS/` außerhalb von `CMS/` ist Entwicklungs-/Quellkontext und wird im Bootstrap nur als Fallback-Autoloader berücksichtigt, wenn `CMS/assets/autoload.php` fehlt.

---

## 21. Logging, Fehlerbehandlung und Diagnose

### 21.1 Fehlerlog

`CMS_ERROR_LOG` zeigt auf das zentrale Fehlerlog. Bevorzugt wird `var/logs/error.log`.

### 21.2 Logger

Der zentrale Logger ergänzt klassische PHP-Fehlerpfade um strukturiertere Anwendungssicht.

### 21.3 AuditLogger

Der AuditLogger dokumentiert sicherheits- und betriebsrelevante Aktionen unabhängig vom klassischen Fehlerlog.

### 21.4 Diagnose im Admin

Der Admin-Bereich bietet eigene Diagnose-/Support-/Systeminfo-Seiten. Diese sind für Betrieb und Fehlersuche relevant und sollten nicht als optionaler Deko-Bereich betrachtet werden.

---

## 22. Best Practices für Entwicklung im 365CMS

### 22.1 Runtime vor Quellrepo denken

Wenn eine Änderung im Live-System wirken soll, muss sie in der Runtime landen:

- Themes unter `CMS/themes/`
- Plugins unter `CMS/plugins/`

### 22.2 Hooks exakt verwenden

Nicht vermutete Hook-Namen benutzen, sondern die tatsächlich im Code ausgelösten. Das spart Stunden auf der Suche nach „Warum läuft mein Indexer nie?“.

### 22.3 Geteilte Admin-Skripte ernst nehmen

Viele Admin-Funktionen hängen an gemeinsamen JS-Dateien. Ein Fix in einer View reicht oft nicht, wenn die echte Interaktion im gemeinsamen Asset lebt.

### 22.4 One-shot-CSRF respektieren

One-shot-Tokens werden nach erfolgreicher Prüfung invalidiert. Shell- und Embedded-Komponenten dürfen denselben Token nicht doppelt verbrauchen.

### 22.5 Theme-/Plugin-Checks nicht umgehen

Die Security-Scans und Pfadvalidierungen sind keine Schikane, sondern verhindern spätere Katastrophen mit Anlauf.

### 22.6 Mehrsprachigkeit vollständig mitdenken

Bei Suche, SEO, Save-Hooks, Routing und Editorlogik müssen `_en`-Felder berücksichtigt werden, wenn das Feature mehrsprachig sein soll.

### 22.7 Einstellungen zentral behandeln

Direkte Wildwuchs-Settings ohne Namenskonzept erschweren Diagnose, Migration und UI-Integration.

### 22.8 Fehlertoleranz bei Hintergrundjobs

Cron-/Queue-Funktionen müssen robust gegen Einzelprobleme sein und brauchbare Logs hinterlassen.

### 22.9 Admin-Pfade intern konsistent halten

Interne Redirects und Formularziele sollten sauber auf die tatsächliche Admin-Routing-Struktur abgestimmt sein.

### 22.10 Keine Security durch Hoffnung

- niemals rohes HTML aus Nutzereingaben ausgeben
- niemals SQL mit Input zusammensetzen
- niemals Dateipfade ungeprüft übernehmen
- niemals ZIP-Inhalte blind entpacken
- niemals annehmen, dass Theme-/Plugin-Code harmlos ist, nur weil er hübsch kommentiert wurde

---

## 23. Häufige Stolperfallen

### 23.1 Theme-Pfad-Verwechslung

Änderungen im Theme-Quellrepo wirken nicht automatisch auf das laufende CMS. Live zählt der Pfad `CMS/themes/<slug>/`.

### 23.2 Plugin-Pfad-Verwechslung

Ein Plugin im Repo `365CMS.DE-PLUGINS/` ist noch nicht aktiv, solange es nicht im Runtime-Ordner liegt.

### 23.3 Redirect-/404-UI nur halb gefixt

Wenn Redirect- und 404-Dialoge auf gemeinsames JavaScript setzen, muss der Fix dort ansetzen, nicht nur in einer einzelnen View.

### 23.4 Editor.js nur an einer Stelle geändert

Die sichtbare Live-Editor-Konfiguration kann an anderer Stelle sitzen als der Asset-Service. Beides muss bei Bedarf angepasst werden.

### 23.5 Suche hört auf falsche Hooks

Wenn Suchindexierung an falschen Events hängt, bleibt Content unsichtbar oder veraltet, besonders bei mehrsprachigen Feldern.

### 23.6 Menü-Startseite nicht normalisiert

Home-/Startseitenpfade müssen als Root logisch behandelt werden. Sonst erzeugt ein eigentlich korrekter Menüeintrag unnötige Validierungsfehler.

### 23.7 Fremdschlüssel generisch benannt

Constraint-Namen müssen schemaweit eindeutig sein, sonst scheitern Installer/Migrationen auf realen Datenbanken.

---

## 24. Technische Checkliste vor Änderungen

Vor größeren Eingriffen prüfen:

- Arbeite ich in der Runtime oder nur im Quellrepo?
- Greife ich an Hook, Router, Shell oder View wirklich an der richtigen Stelle an?
- Betreffe ich DE/EN-Contentpfade gleichzeitig?
- Wird ein gemeinsam genutztes JavaScript oder Service-Modul beeinflusst?
- Sind CSRF, Escape und Prepared Statements eingehalten?
- Müssen Settings, Cache, Index oder Cron-Verhalten mit aktualisiert werden?
- Sind Theme-/Plugin-Laufzeitverträge weiterhin erfüllt?
- Entsteht zusätzlicher Audit- oder Logging-Bedarf?

---

## 25. Abschlussbild

365CMS ist kein kleines Ein-Datei-CMS mehr, sondern ein verteiltes Anwendungssystem mit Security-, Service-, Routing-, Theme-, Plugin-, SEO-, Monitoring- und Member-Schichten. Wer daran entwickelt, sollte nicht nur „die betroffene Datei“ sehen, sondern immer die Laufzeitkette mitdenken:

**Konfiguration → Bootstrap → Security/Auth → Hooks → Services → Router → Module/Theme/Plugin → Logging/Audit → Betrieb**

Wenn diese Kette sauber bleibt, bleibt das System stabil. Wenn man irgendwo quer schneidet, rächt sich das meist später, nachts oder kurz vor einem Demo-Termin — also zu den traditionellen Öffnungszeiten des Chaos.
