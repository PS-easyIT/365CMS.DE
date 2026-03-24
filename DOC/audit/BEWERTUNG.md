# BEWERTUNG

## Inkrementelle Nachpflege — 24. März 2026

Diese Sektion dokumentiert bereits umgesetzte Teilfortschritte aus `DOC/audit/PRÜFUNG.MD`,
ohne die große Bewertungsmatrix bei jedem einzelnen Batch vollständig neu auszurechnen.

### Delta Batch 001

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/analytics.php` | geprüft | Entry-Point-Standardisierung verifiziert: die Datei delegiert bereits schlank an `seo-page.php`; der operative Audit-Hotspot liegt aktuell eher in `SeoSuiteModule` als im Entry selbst. | Kein Sofortfix am Entry nötig; Bewertungsfokus verschiebt sich auf Modul-/Service-Ebene. |
| `CMS/admin/antispam.php` | umgesetzt | POST-Actions nur noch über Allowlist, konsistenter Redirect-/Flash-Flow für CSRF- und Aktionsfehler. | Security- und PHP/BP-Risiko im Entry-Flow gesenkt. |
| `CMS/admin/modules/security/AntispamModule.php` | umgesetzt | Eingaben bereinigt, Secrets werden bei leerem Feld erhalten, rohe Exception-Texte nicht mehr an die UI geleakt, fehlgeschlagene Mutationen auditierbar behandelt. | Security und Fehlerhärte verbessert; Secret-Handling und Mutationspfade robuster. |
| `CMS/admin/views/security/antispam.php` | umgesetzt | Gemeinsame Flash-Alerts eingebunden, reCAPTCHA-Secret nicht mehr vorbelegt, Inline-`confirm()` entfernt. | UI folgt dem gemeinsamen Admin-Muster; XSS-/Secret-/Inline-JS-Risiko reduziert. |
| `CMS/admin/backups.php` | verifiziert | Vorheriger Entry-Fix bestätigt: `CMS_ADMIN_SYSTEM_VIEW`-Guard und gemeinsamer Flash-Partial sind aktiv. | Leere Backup-Seite bleibt behoben; nächster Fokus liegt auf Modul/Service statt Entry-Page. |

### Delta Batch 002

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/modules/system/BackupsModule.php` | umgesetzt | UI-Fehlerpfade leaken keine rohen Exception-Texte mehr. | Admin-Feedback bleibt verständlich, ohne interne Details preiszugeben. |
| `CMS/core/Services/BackupService.php` | umgesetzt | Backup-Namen und Zielpfade werden validiert, Backup-Root wird systematisch abgesichert, Manifest-/Lösch-/Mail-/ZIP-Pfade defensiver behandelt. | Security-, I/O- und Fehlertoleranz im eigentlichen Backup-Hotpath spürbar verbessert. |

### Delta Batch 003

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/theme-editor.php` | geprüft | Aktuelle Rolle verifiziert: Der Entry bettet primär den Theme-Customizer ein; der operative Dateieditor liegt heute im separaten `theme-explorer`-Pfad. | Risikofokus fachlich präziser eingeordnet; kritischer Dateieditor nicht mehr am falschen Entry gesucht. |
| `CMS/admin/theme-explorer.php` | umgesetzt | POST-Aktionsfläche reduziert, Redirect-/Flash-Flow vereinheitlicht. | Security- und PHP/BP-Risiko im Entry-Flow gesenkt. |
| `CMS/admin/modules/themes/ThemeEditorModule.php` | umgesetzt | Pfadnormalisierung, Pattern-Gates, Root-Enforcement, Größenlimit, Symlink-Skip und `LOCK_EX` beim Schreiben. | Traversal-, Oversize- und unsichere Schreibpfade deutlich reduziert. |
| `CMS/admin/views/themes/editor.php` | umgesetzt | Gemeinsame Flash-Alerts und kontrollierte Dateiwarnungen statt losem Sonder-Alert-Markup. | View folgt stärker dem Admin-Standard und bleibt im Fehlerfall sauberer. |

### Delta Batch 004

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/plugin-marketplace.php` | umgesetzt | Entry-Point auf konsistenten `install`-Only-Flow mit Redirect-/Flash-Behandlung gebracht. | Wrapper folgt jetzt derselben Sicherheits- und UX-Linie wie andere Admin-Mutationen. |
| `CMS/admin/theme-marketplace.php` | umgesetzt | Entry-Point auf konsistenten `install`-Only-Flow mit Redirect-/Flash-Behandlung gebracht. | CSRF-/Aktionsfehler werden sauber und einheitlich behandelt. |
| `CMS/admin/modules/plugins/PluginMarketplaceModule.php` | verifiziert | HTTPS-/Host-Allowlist, SHA-256-Prüfung und ZIP-Validierung bereits aktiv. | Audit-Restdruck verschiebt sich vom groben Supply-Chain-Fund auf Detail- und Wrapper-Ebene. |
| `CMS/admin/modules/themes/ThemeMarketplaceModule.php` | verifiziert | HTTPS-/Host-Allowlist, SHA-256-Prüfung, ZIP-Validierung und Manual-only-Handling bereits aktiv. | Der ursprüngliche Kritikalitätsstand ist im Modul selbst fachlich teilweise überholt. |

### Delta Batch 005

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/modules/seo/SeoSuiteModule.php` | umgesetzt | Indexing-URLs werden hostgebunden validiert, dedupliziert und begrenzt; Sitemap-Werte werden normalisiert; Broken-Link-Checks orientieren sich an der konfigurierbaren Permalink-Struktur statt an harten `/blog/`-Pfaden; Fehlerdetails bleiben intern. | Kritische SEO-Trust-Boundaries sind deutlich entschärft; gleichzeitig sinkt das Risiko von Fehlalarmen in SEO-Audits bei abweichender Permalink-Struktur. |
| `CMS/admin/modules/seo/PerformanceModule.php` | umgesetzt | Dateisystemläufe überspringen Symlinks, Settings-Werte werden sauber begrenzt und Save-Fehler generisch behandelt. | Das Risiko unbeabsichtigter Linkpfade und unnötiger Detail-Leaks sinkt; die Modulhärte steigt ohne die Oberfläche aufzublähen. |
| `CMS/admin/modules/settings/SettingsModule.php` | umgesetzt | Config-Schreibpfade für `config/app.php` und `.htaccess` robuster gemacht, Asset-/URL-Werte strenger normalisiert, erfolgreiche Änderungen auditgeloggt und Exception-Leaks entfernt. | Das Modul wirkt weniger „kritisch aus Prinzip“ und mehr wie ein kontrollierter Admin-Kernpfad; Restaufwand liegt eher in weiterer Zerlegung als in Sofortlücken. |
| `CMS/admin/settings.php` | umgesetzt | POST-Aktionen per Allowlist begrenzt; Redirect-/Flash-Flow vereinheitlicht. | Entry folgt konsequenter dem inzwischen etablierten Admin-Sicherheitsmuster. |

### Delta Batch 006

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/modules/member/MemberDashboardModule.php` | umgesetzt | Speicherroutinen nutzen jetzt einen zentralen, auditierbaren Fehlerpfad; Asset-Referenzen und Onboarding-CTA-URLs werden defensiver normalisiert. | Admin-UI leakt keine rohen Exception-Details mehr; gleichzeitig sinkt das Risiko unsauberer Asset-/Link-Konfigurationen im Member-Bereich. |
| `CMS/admin/modules/legal/CookieManagerModule.php` | umgesetzt | Policy-URL, Slugs, Matomo-Site-ID und Bannertexte werden strenger normalisiert; der Scanner überspringt Symlinks; Save-Fehler bleiben generisch. | Cookie-Consent- und Tracking-Konfigurationen werden kontrollierter gespeichert, während Dateisystem-Scans weniger anfällig für Ausreißer und Detail-Leaks sind. |
| `CMS/admin/modules/legal/LegalSitesModule.php` | umgesetzt | Save-, Profil- und Seitengenerierungsfehler laufen über einen zentralen, auditierbaren Fehlerpfad statt über rohe Exception-Ausgaben. | Rechtstext-Management bleibt für Admins bedienbar, ohne interne Fehlerdetails oder Stack-Hinweise direkt offenzulegen. |

### Delta Batch 007

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/documentation.php` | umgesetzt | POST-Aktionen sind auf `sync_docs` begrenzt. | Der Entry folgt dem etablierten Admin-Muster und verarbeitet keine unerwarteten Mutationsaktionen mehr. |
| `CMS/admin/modules/system/DocumentationGitSync.php` | umgesetzt | Repo-/DOC-Ziel, Remote/Branch und Fehlerpfade werden vor/nach Git-Kommandos defensiver behandelt; Rohoutputs bleiben intern. | Git-basierte Doku-Syncs geben keine internen Shell-Details mehr an die Oberfläche weiter und arbeiten nur noch mit erwarteten Zielpfaden. |
| `CMS/admin/modules/system/DocumentationGithubZipSync.php` | umgesetzt | Arbeitsverzeichnisse werden auf Temp-/Repo-Roots begrenzt, symbolische Links ausgeschlossen und Fehler generisch behandelt. | ZIP-basierte Doku-Syncs sind robuster gegen Pfad-Ausreißer, Link-Fallen und UI-Detail-Leaks. |
| `CMS/admin/modules/system/DocumentationSyncDownloader.php` | umgesetzt | ZIP-Ziele werden auf neue Temp-Dateien begrenzt; leere Bodies werden verworfen. | Das Risiko unbeabsichtigter Überschreibungen oder leerer/unsauberer Download-Artefakte sinkt. |
| `CMS/admin/modules/system/DocumentationSyncFilesystem.php` | umgesetzt | Integritäts-, Finder-, Copy-, Count- und Delete-Pfade behandeln symbolische Links defensiv und propagieren rekursive Fehler sauberer. | Der eigentliche Dateisystem-Hotpath des Doku-Syncs wird deutlich robuster gegenüber Traversal-/Link-Effekten und halb stillen Cleanup-Fehlern. |

### Delta Batch 008

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/media.php` | umgesetzt | POST-Aktionen per Allowlist begrenzt; Redirect-Parameter werden vor Wiederverwendung normalisiert. | Der Media-Entry verarbeitet keine unerwarteten Mutationsaktionen mehr und spiegelt weniger rohe Query-Daten in Redirects zurück. |
| `CMS/admin/modules/media/MediaModule.php` | umgesetzt | Pfade, Tabs, Views, Suchbegriffe, Kategorie-Slugs sowie Upload-/Rename-/Category-Eingaben werden zentral bereinigt; System-Kategorien sind serverseitig geschützt; Service-Fehler werden intern geloggt und nur generisch im UI angezeigt. | Der Admin-Media-Hotspot verliert mehrere Trust-Boundary-Schwächen gleichzeitig: weniger unnormalisierter Input, weniger Detail-Leaks und klarere Schutzgeländer um Kategorien, Uploads und Dateipfade. |

### Delta Batch 009

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/updates.php` | umgesetzt | POST-Aktionen per Allowlist begrenzt; Plugin-Slugs vor Modulaufruf normalisiert; Redirect-/Flash-Flow für Fehlfälle vereinheitlicht. | Der Update-Entry verarbeitet keine unerwarteten Mutationen mehr und leitet Update-Aktionen kontrollierter in den Modulpfad weiter. |
| `CMS/admin/modules/system/UpdatesModule.php` | umgesetzt | Fehlerpfade für Prüf- und Installationsläufe liefern nur noch generische UI-Meldungen; technische Details werden intern geloggt und auditierbar protokolliert; manuelle Plugin-Fälle werden vor Auto-Install getrennt. | Der Modul-Hotspot verliert rohe Exception-Leaks und zieht beim Update-Handling auf das inzwischen etablierte Admin-Sicherheitsmuster nach. |
| `CMS/core/Services/UpdateService.php` | umgesetzt | Download-Quellen müssen zusätzlich den SSRF-/DNS-Sicherheitscheck bestehen; Installationsziele werden auf erlaubte Roots begrenzt; leere Bodies werden verworfen; Installationsfehler bleiben generisch. | Supply-Chain- und Dateisystem-Risiken im eigentlichen Update-Installationspfad sinken spürbar, ohne den Rollout-Mechanismus fachlich umzubauen. |

### Delta Batch 010

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/posts.php` | umgesetzt | POST-Aktionen und View-Routing per Allowlist begrenzt; Bulk-/Kategorie-Parameter vor Modulaufruf typisiert; Redirect-/Flash-Flow für Fehlfälle vereinheitlicht. | Der Posts-Entry verarbeitet keine unerwarteten Mutationen oder View-Sprünge mehr und spiegelt weniger rohe Request-Werte in den Admin-Flow zurück. |
| `CMS/admin/modules/posts/PostsModule.php` | umgesetzt | Listenfilter, Bulk-Aktionen und mehrere Text-/Meta-/Medienfelder werden zentral normalisiert; Kategorie-/Tag-Löschpfade sind von verdeckten `$_POST`-Reads entkoppelt; Fehlerpfade liefern nur noch generische UI-Meldungen mit internem Logging/Audit. | Der Posts-Hotspot verliert mehrere klassische Trust-Boundary-Schwächen zugleich: weniger unnormalisierte Eingaben, weniger Detail-Leaks und klarere Modulgrenzen zwischen Entry und Geschäftslogik. |

### Delta Batch 011

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/pages.php` | umgesetzt | POST-Aktionen und View-Routing per Allowlist begrenzt; CSRF-/Aktionsfehler per Redirect + Flash vereinheitlicht; Bulk-Parameter vor Modulaufruf defensiver typisiert. | Der Pages-Entry verarbeitet keine unerwarteten Mutationen oder View-Sprünge mehr und folgt jetzt enger dem etablierten Admin-Entry-Muster. |
| `CMS/admin/modules/pages/PagesModule.php` | umgesetzt | Listenfilter, Bulk-Aktionen sowie mehrere Titel-/Meta-/Medienfelder werden zentral normalisiert; Save-/Delete-/Bulk-Fehler liefern nur noch generische UI-Meldungen mit internem Logging/Audit. | Der Seiten-Hotspot verliert rohe Detail-Leaks und mehrere unnormalisierte Eingabepfade, ohne den fachlichen CRUD-Flow umzubauen. |

### Delta Batch 012

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/theme-explorer.php` | umgesetzt | POST-Aktionsfläche über explizite Allowlist konsolidiert. | Der Entry bleibt beim Save-Flow enger an bekannten Mutationen und reduziert Restspielraum im Wrapper. |
| `CMS/admin/modules/themes/ThemeEditorModule.php` | umgesetzt | Unsichere Dateianfragen liefern kontrollierte Warnungen; Binärdaten/zu große Inhalte werden vor dem Schreiben abgefangen; Syntax- und Schreibfehler bleiben im UI generisch und werden intern geloggt/auditierbar protokolliert. | Der Theme-Editor verliert weitere Detail-Leaks und gewinnt zusätzliche Schutzgeländer um die browserseitige Dateibearbeitung. |
| `CMS/admin/views/themes/editor.php` | umgesetzt | Tree-Renderer defensiver gegen Redeclare-/Datentyp-Randfälle gemacht. | Die View bleibt robuster und folgt enger dem defensiven Admin-Template-Stil. |

### Delta Batch 013

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/hub-sites.php` | umgesetzt | POST-Aktionen und View-Routing per Allowlist begrenzt; Fehlfälle liefern konsistente Fallback-Meldungen statt losem Sonderverhalten. | Der Hub-Sites-Entry verarbeitet keine unerwarteten Mutationen oder View-Sprünge mehr und bleibt beim Fehler-Flow enger am gemeinsamen Admin-Muster. |
| `CMS/admin/modules/hub/HubSitesModule.php` | umgesetzt | Suche, Plaintext-Felder und mehrere CTA-/Card-/Link-/Bild-URLs werden zentral normalisiert; Save-/Delete-/Duplicate-Fehler liefern nur noch generische UI-Meldungen mit internem Logging/Audit. | Der Hub-Hotspot verliert weitere Trust-Boundary-Schwächen: weniger unnormalisierte Eingaben, weniger Detail-Leaks und klarere Schutzgeländer um Hub-Konfiguration, Karten und Linkziele. |

### Delta Batch 014

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/core/Services/FileUploadService.php` | umgesetzt | Nur noch echte POST-Uploads, segmentweise Pfadvalidierung, Einzeldatei-Prüfung und generische Fehlerantworten mit internem Logging/Audit statt roher `WP_Error`-Details. | Der zentrale Upload-Endpunkt verliert Traversal-/Payload-Schwächen und reduziert Detail-Leaks an API-/Public-Clients, ohne den Media-/EditorJS-Flow fachlich umzubauen. |

### Delta Batch 015

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/comments.php` | umgesetzt | Kommentar-Entry nutzt jetzt die vorhandene View-Capability, akzeptiert nur noch bekannte POST-Aktionen und hält Redirects enger am validierten Statusfilter. | Der Entry trennt View- von Mutationsrechten sauberer und spiegelt keine losen Kommentar-Aktionen mehr in den Moderationsflow. |
| `CMS/admin/modules/comments/CommentsModule.php` | umgesetzt | Moderations-, Delete- und Bulk-Pfade validieren IDs, Zielstatus und Vorhandensein der Kommentare serverseitig und protokollieren Fehl- bzw. Teilzustände intern über Logging/Audit. | Der Kommentar-Hotspot verliert mehrere Trust-Boundary-Schwächen: weniger unvalidierte Mutationen, klarere Rechtekanten und weniger stille Bulk-Fehlpfade. |
| `CMS/admin/views/comments/list.php` | umgesetzt | Bulk-Bar, Checkboxen und Row-Actions richten sich an den tatsächlichen Rechten aus; Post-Ziele kommen vorbereitet aus dem Modul. | Die View koppelt Mutations-UI enger an den serverseitigen Rechtezustand und reduziert rohe Verkettungslogik im Template. |

### Delta Batch 016

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/modules/system/DocumentationSyncService.php` | umgesetzt | Konfiggrenzen, Capability-Normalisierung und zentrale Fehler-/Erfolgspfade für den Doku-Sync-Orchestrator nachgezogen. | Der Orchestrator blockt inkonsistente Repo-/DOC-/ZIP-Konfiguration früher, protokolliert Sync-Erfolg und Abbruch zentral und verlässt sich weniger auf lose Roh-Rückgaben aus Unterservices. |

### Delta Batch 017

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/firewall.php` | umgesetzt | Firewall-Entry verarbeitet nur noch bekannte Aktionen und behandelt CSRF-/Aktionsfehler konsistent über Redirect + Flash. | Der Entry spiegelt keine losen Security-Mutationen mehr direkt in den Request-Flow und bleibt beim Fehlerverhalten enger am gemeinsamen Admin-Muster. |
| `CMS/admin/modules/security/FirewallModule.php` | umgesetzt | Regel- und Settings-Mutationen validieren Eingaben strenger, blocken Dubletten, leaken keine rohen Exceptions mehr und auditieren Fehlschläge kontrollierter. | Der Firewall-Hotspot verliert mehrere Trust-Boundary-Schwächen: weniger unvalidierte Regeln, weniger Detail-Leaks und klarere Pfade für Delete-/Toggle-/Save-Fehler. |
| `CMS/admin/views/security/firewall.php` | umgesetzt | Flash-Alerts, kontrollierter Ablaufdatum-Output und bestätigtes Löschen via `cmsConfirm(...)` nachgezogen. | Die UI bleibt näher am restlichen Security-Backend, reduziert unescaped Inline-Ausgabe und entkoppelt sich vom rohen Browser-Confirm. |

### Delta Batch 018

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/modules/hub/HubTemplateProfileManager.php` | umgesetzt | Template-Persistenz härter validiert, URL-/JSON-Payloads begrenzt und Fehler-/Sync-Pfade sauber geloggt; Listen-Nutzung wird ohne N+1-Queries ermittelt. | Der Hub-Template-Hotspot verarbeitet weniger unbereinigte Profil-Daten, läuft robuster bei DB-/Sync-Fehlern und entlastet das Listing spürbar durch aggregierte Nutzungszähler. |

### Delta Batch 019

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/core/Services/GraphApiService.php` | umgesetzt | Graph-Konfiguration, Token-/API-Requests und Response-Handling restriktiver validiert; Testfehler bleiben nach außen generisch. | Der Service reduziert Konfigurationsdrift, unsaubere Endpoint-Pfade und Detail-Leaks im Graph-Testpfad, während Token-Abrufe jetzt sauber als Formular-Request und mit engeren Response-Grenzen laufen. |

### Delta Batch 020

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/core/Services/CommentService.php` | umgesetzt | Öffentliche Kommentar-Payloads, Post-Freigabe, Flood-Limits und Logging-/Audit-Pfade nachgezogen; öffentliche Ausgabe gibt keine Autor-Mail mehr mit aus. | Der Kommentar-Service blockt Missbrauch an der öffentlichen Eingabekante früher, reduziert unnötige personenbezogene Daten im Frontend und hält Persistenz-/Abbruchpfade nachvollziehbarer. |

### Delta Batch 021

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/modules/system/DocumentationRenderer.php` | umgesetzt | Doku-Rendering gegen übergroße Markdown-/CSV-Payloads, ausufernde Tabellen und unsaubere Linkziele begrenzt. | Der Renderer erzeugt HTML aus Repository-Dokumenten jetzt kontrollierter, validiert `href`-Ziele enger und hält große oder auffällige Dokumente durch serverseitige Limits und Logging besser im Zaum. |

### Delta Batch 022

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/modules/security/SecurityAuditModule.php` | umgesetzt | Modulzugriff, Audit-Log-Ausgabe, Check-Texte und `.htaccess`-Inspektion defensiver gemacht; Fehlerpfade bleiben generisch. | Das Sicherheits-Audit verarbeitet weniger rohe Audit-Daten, begrenzt große/ungewöhnliche Prüfausgaben serverseitig und vermeidet Detail-Leaks bei Log-Bereinigung oder Teilfehlern im Audit-Lauf. |

### Delta Batch 023

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/modules/legal/CookieManagerModule.php` | umgesetzt | Modulzugriff, Mutationsvalidierung, Settings-Persistenz und Scanner-Grenzen nachgezogen. | Der Cookie-Manager akzeptiert weniger unsaubere Konfigurationspayloads, auditierbare Mutationen laufen kontrollierter und der Scanner bleibt durch Größen-/Mengenlimits deutlich berechenbarer. |

### Delta Batch 024

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/core/Services/AzureMailTokenProvider.php` | umgesetzt | Konfiguration, Token-Endpoint, Cache-Validierung und Response-Handling restriktiver gemacht. | Der Azure-Mail-Tokenpfad akzeptiert weniger unsaubere Tenant-/Scope-/Endpoint-Werte, verwirft abgelaufene oder kaputte Cache-Einträge früher und reagiert kontrollierter auf Remote-Fehler und ungewöhnliche Antworten. |

### Delta Batch 025

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/core/Services/FeedService.php` | umgesetzt | Feed-URL-Validierung, Ausgabesanitisierung und Cache-Cleanup restriktiver gemacht. | Der Feed-Service blockt private/reservierte Remote-Ziele, begrenzt Batch-/Item-Mengen, sanitisiert Feed-HTML serverseitig und leakt Parser- bzw. Remote-Fehler nicht mehr roh an Aufrufer. |

### Delta Batch 026

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/modules/system/DocumentationModule.php` | umgesetzt | Modulzugriff, Repo-/DOC-Layout-Checks und Fehlerpfade restriktiver gemacht. | Der Doku-Orchestrator validiert Dokumentauswahl und lokale Pfade früher, kapselt Render-/Sync-Fehler generisch und verlässt sich weniger auf lose Annahmen aus Entry oder Unterservices. |

### Delta Batch 027

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/modules/legal/LegalSitesModule.php` | umgesetzt | Modulzugriff, Seitenzuordnung, Settings-Reads und Eingaben restriktiver gemacht. | Das Legal-Sites-Modul validiert zugeordnete Seiten serverseitig, bündelt Settings-Zugriffe, begrenzt Profil-/HTML-Payloads und hält Fehlerpfade für UI und Audit-Log deutlich kontrollierter. |

### Delta Batch 028

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/modules/landing/LandingPageModule.php` | umgesetzt | Interne Guards, Whitelisting und generische Fehlerpfade für Admin-Mutationen nachgezogen. | Das Landing-Page-Modul normalisiert Tabs und Payloads serverseitig, lässt nur erlaubte Felder zu und gibt bei Fehlern keine rohen Exceptions mehr in die Admin-Oberfläche weiter. |

### Delta Batch 029

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/core/Services/MailService.php` | umgesetzt | Header-Injection-Schutz, TLS-Härtung und UI-taugliche Fehlerpfade nachgezogen. | Der zentrale Mail-Service validiert Header, Adressen und Betreff restriktiver, erzwingt TLS für nicht-lokale SMTP-/OAuth2-Transporte und leakt keine rohen Provider-Fehler mehr an Admin-Oberflächen oder API-Aufrufer. |

### Delta Batch 030

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/core/Services/EditorJs/EditorJsRemoteMediaService.php` | umgesetzt | HTTPS-/URL-Validierung, Remote-Fehlerpfade und Metadaten-Sanitizing deutlich verschärft. | Der Remote-Media-Service für Editor.js akzeptiert nur noch normalisierte HTTPS-URLs ohne eingebettete Credentials, kapselt Remote-Fehler generisch und begrenzt fremde Metadaten sowie Preview-Bilder restriktiver. |

### Delta Batch 031

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/modules/system/DocumentationSyncFilesystem.php` | umgesetzt | Repo-/DOC-/Temp-Roots jetzt in allen Copy-/Rename-/Delete-Pfaden strikt erzwungen. | Der Doku-Sync-Dateisystem-Layer operiert nur noch innerhalb explizit verwalteter Arbeitsbereiche und lehnt ausreißende Staging-, Backup-, Extract- oder Cleanup-Pfade konsistent vor der Operation ab. |

### Delta Batch 032

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/modules/member/MemberDashboardModule.php` | umgesetzt | Interne RBAC-/CSRF-Gates, auditierte Save-Pfade und weniger N+1-Settings-Zugriffe nachgezogen. | Das Member-Dashboard-Modul prüft Rechte und Tokens jetzt auch intern pro Bereich, lädt Member-Settings und Plugin-Meta gebündelt und protokolliert erfolgreiche sicherheitsrelevante Konfigurationsänderungen explizit. |

### Delta Batch 033

| Datei/Bereich | Status | Nachgezogener Punkt aus `PRÜFUNG.MD` | Wirkung |
|---|---|---|---|
| `CMS/admin/modules/system/BackupsModule.php` | umgesetzt | Interne RBAC-/CSRF-Gates, Backup-Namens-/Typvalidierung und auditierte Mutationen nachgezogen. | Das Backup-Modul prüft Lese- und Schreibzugriffe jetzt auch intern, akzeptiert für UI und Löschpfade nur noch whitelisted Backup-Namen/-Typen und protokolliert erfolgreiche Create-/Delete-Aktionen sowie Fehlerpfade kontrolliert statt lose Metadaten oder rohe Fehlertexte durchzureichen. |

## Grundlage

Diese Datei bewertet den aktuellen CMS-Codebestand **dateiweise** nach:

- **Security** ($1$ bis $100$)
- **Speed** ($1$ bis $100$)
- **PHP/BP** ($1$ bis $100$) – bei Nicht-PHP-Dateien als Stack-/Best-Practice-Konformität des jeweiligen Dateityps
- **Gesamt** ($1$ bis $100$)

Geprüfter Scope:

- `CMS/**`
- **ohne** `CMS/vendor/**`
- **ohne** `CMS/themes/**`
- unter `CMS/assets/**` **nur** `assets/css/**` und `assets/js/**`

Verwendete Referenzbasis für die Einordnung:

- OWASP Top 10 (2025)
- OWASP Cheat Sheets: PHP Configuration, XSS Prevention, SQL Injection Prevention, Session Management, Input Validation, Error Handling, File Upload
- PHP Manual – Security
- PSR-12
- PHP The Right Way

## Bewertungslogik

| Bereich | Bedeutung |
|---|---|
| `90–100` | sehr stark / vorbildlich |
| `75–89` | gut / solide |
| `60–74` | brauchbar, aber mit klaren Baustellen |
| `40–59` | deutlich verbesserungsbedürftig |
| `< 40` | kritisch |

## Admin

### Bewertung

| Datei | Kurzbeschreibung | Verknüpfung/Abhängigkeiten | Security | Speed | PHP/BP | Gesamt |
|---|---|---|---:|---:|---:|---:|
| `CMS/admin/analytics.php` | Analytics-Entry | SEO/Analytics-Modul, Layout, SEO-View | 84 | 86 | 85 | 85 |
| `CMS/admin/antispam.php` | Antispam-Entry | `AntispamModule`, Security-View | 84 | 86 | 85 | 85 |
| `CMS/admin/backups.php` | Backup-Entry | `BackupsModule`, System-View, I/O | 76 | 72 | 82 | 77 |
| `CMS/admin/comments.php` | Kommentar-Entry | `CommentsModule`, Comments-View | 89 | 86 | 88 | 88 |
| `CMS/admin/cookie-manager.php` | Cookie-Entry | `CookieManagerModule`, Legal-View | 80 | 78 | 84 | 80 |
| `CMS/admin/data-requests.php` | DSGVO-Export-Entry | Legal-Modul, Legal-View | 84 | 86 | 85 | 85 |
| `CMS/admin/deletion-requests.php` | Löschanfrage-Entry | Legal-Modul, Legal-View | 84 | 86 | 85 | 85 |
| `CMS/admin/design-settings.php` | Design-Entry | Theme-Design-Modul, Theme-View | 84 | 86 | 85 | 85 |
| `CMS/admin/diagnose.php` | Diagnose-Entry | System-Modul, Diagnose-View | 89 | 93 | 86 | 89 |
| `CMS/admin/documentation.php` | Doku-Entry | Dokumentations-Module, Doku-View | 78 | 72 | 82 | 78 |
| `CMS/admin/error-report.php` | Error-Report-Entry | Error-Services, Formular-Flow | 78 | 80 | 83 | 80 |
| `CMS/admin/firewall.php` | Firewall-Entry | `FirewallModule`, Security-View | 84 | 79 | 86 | 83 |
| `CMS/admin/font-manager.php` | Font-Manager-Entry | Font-Modul, Theme-Fonts-View, FS | 78 | 76 | 84 | 79 |
| `CMS/admin/groups.php` | Gruppen-Entry | `GroupsModule`, Users-View | 84 | 86 | 85 | 85 |
| `CMS/admin/hub-sites.php` | Hub-Sites-Entry | Hub-Module, Hub-Views | 85 | 80 | 86 | 84 |
| `CMS/admin/index.php` | Dashboard-Entry | `DashboardModule`, Header/Sidebar, Dashboard-View | 89 | 93 | 86 | 89 |
| `CMS/admin/info.php` | Info-Entry | SystemInfo-Modul, System-View | 89 | 93 | 86 | 89 |
| `CMS/admin/landing-page.php` | Landing-Builder-Entry | `LandingPageModule`, Landing-View | 80 | 78 | 84 | 80 |
| `CMS/admin/legal-sites.php` | Rechtstexte-Entry | `LegalSitesModule`, Legal-Views | 80 | 76 | 84 | 79 |
| `CMS/admin/mail-settings.php` | Mail-Entry | `MailSettingsModule`, Mail-View, API-Test | 80 | 78 | 84 | 80 |
| `CMS/admin/media.php` | Medien-Entry | `MediaModule`, Media-Views, Upload/FS | 76 | 72 | 82 | 77 |
| `CMS/admin/member-dashboard-design.php` | Member-Design-Entry | Member-Modul, Member-View | 84 | 86 | 85 | 85 |
| `CMS/admin/member-dashboard-frontend-modules.php` | Member-Module-Entry | Member-Modul, Member-View | 84 | 86 | 85 | 85 |
| `CMS/admin/member-dashboard-general.php` | Member-General-Entry | Member-Modul, Member-View | 84 | 86 | 85 | 85 |
| `CMS/admin/member-dashboard-notifications.php` | Member-Notify-Entry | Member-Modul, Member-View | 84 | 86 | 85 | 85 |
| `CMS/admin/member-dashboard-onboarding.php` | Member-Onboarding-Entry | Member-Modul, Member-View | 84 | 86 | 85 | 85 |
| `CMS/admin/member-dashboard-page.php` | Member-Page-Entry | Member-Modul, Member-View | 84 | 86 | 85 | 85 |
| `CMS/admin/member-dashboard-plugin-widgets.php` | Member-Pluginwidget-Entry | Member-Modul, Member-View | 84 | 86 | 85 | 85 |
| `CMS/admin/member-dashboard-profile-fields.php` | Member-Profilfeld-Entry | Member-Modul, Member-View | 84 | 86 | 85 | 85 |
| `CMS/admin/member-dashboard-widgets.php` | Member-Widget-Entry | Member-Modul, Member-View | 84 | 86 | 85 | 85 |
| `CMS/admin/member-dashboard.php` | Member-Dashboard-Entry | `MemberDashboardModule`, Member-Views | 84 | 86 | 85 | 85 |
| `CMS/admin/menu-editor.php` | Menü-Editor-Entry | `MenuEditorModule`, Menus-View | 84 | 86 | 85 | 85 |
| `CMS/admin/monitor-cron-status.php` | Cron-Monitor-Entry | Performance/System-View | 89 | 93 | 86 | 89 |
| `CMS/admin/monitor-disk-usage.php` | Disk-Monitor-Entry | Performance/System-View, FS | 89 | 90 | 86 | 88 |
| `CMS/admin/monitor-email-alerts.php` | Alert-Monitor-Entry | System-View | 89 | 93 | 86 | 89 |
| `CMS/admin/monitor-health-check.php` | Health-Monitor-Entry | System-View | 89 | 93 | 86 | 89 |
| `CMS/admin/monitor-response-time.php` | Response-Monitor-Entry | System-View | 89 | 93 | 86 | 89 |
| `CMS/admin/monitor-scheduled-tasks.php` | Task-Monitor-Entry | System-View | 89 | 93 | 86 | 89 |
| `CMS/admin/not-found-monitor.php` | 404-Monitor-Entry | Redirect-Modul, SEO-View | 84 | 86 | 85 | 85 |
| `CMS/admin/orders.php` | Orders-Entry | `OrdersModule`, Subscription-View | 84 | 86 | 85 | 85 |
| `CMS/admin/packages.php` | Pakete-Entry | `PackagesModule`, Subscription-View | 84 | 86 | 85 | 85 |
| `CMS/admin/pages.php` | Pages-Entry | `PagesModule`, Pages-Views, Editor | 87 | 85 | 86 | 86 |
| `CMS/admin/performance-cache.php` | Cache-Entry | `PerformanceModule`, Performance-View | 84 | 86 | 85 | 85 |
| `CMS/admin/performance-database.php` | DB-Perf-Entry | `PerformanceModule`, Performance-View | 84 | 84 | 85 | 84 |
| `CMS/admin/performance-media.php` | Media-Perf-Entry | `PerformanceModule`, Performance-View | 84 | 84 | 85 | 84 |
| `CMS/admin/performance-page.php` | PageSpeed-Entry | `PerformanceModule`, SEO-Perf-View | 84 | 86 | 85 | 85 |
| `CMS/admin/performance-sessions.php` | Sessions-Entry | `PerformanceModule`, Performance-View | 84 | 86 | 85 | 85 |
| `CMS/admin/performance-settings.php` | Perf-Settings-Entry | `PerformanceModule`, Performance-View | 84 | 86 | 85 | 85 |
| `CMS/admin/performance.php` | Performance-Entry | `PerformanceModule`, SEO/Perf-Views | 84 | 86 | 85 | 85 |
| `CMS/admin/plugin-marketplace.php` | Plugin-Marketplace-Entry | Marketplace-Modul, Remote-Registry | 74 | 68 | 82 | 74 |
| `CMS/admin/plugins.php` | Plugins-Entry | `PluginsModule`, Plugins-Views | 80 | 82 | 84 | 81 |
| `CMS/admin/post-categories.php` | Post-Kategorien-Entry | Posts/Page-Modul, Posts-View | 84 | 86 | 85 | 85 |
| `CMS/admin/post-tags.php` | Post-Tags-Entry | Posts-Modul, Posts-View | 84 | 86 | 85 | 85 |
| `CMS/admin/posts.php` | Posts-Entry | `PostsModule`, Posts-Views, EditorJS | 86 | 83 | 86 | 85 |
| `CMS/admin/privacy-requests.php` | Privacy-Requests-Entry | Legal-Modul, Legal-View | 84 | 86 | 85 | 85 |
| `CMS/admin/redirect-manager.php` | Redirect-Entry | `RedirectManagerModule`, SEO-View | 84 | 84 | 85 | 84 |
| `CMS/admin/roles.php` | Rollen-Entry | `RolesModule`, Users-View | 84 | 86 | 85 | 85 |
| `CMS/admin/security-audit.php` | Security-Audit-Entry | `SecurityAuditModule`, Security-View | 80 | 80 | 84 | 81 |
| `CMS/admin/seo-audit.php` | SEO-Audit-Entry | SEO-Suite, SEO-View | 84 | 84 | 85 | 84 |
| `CMS/admin/seo-dashboard.php` | SEO-Dashboard-Entry | SEO-Module, SEO-View | 84 | 86 | 85 | 85 |
| `CMS/admin/seo-meta.php` | SEO-Meta-Entry | SEO-Suite, SEO-View | 84 | 86 | 85 | 85 |
| `CMS/admin/seo-page.php` | SEO-Page-Entry | SEO-Suite, SEO-View | 84 | 84 | 85 | 84 |
| `CMS/admin/seo-schema.php` | SEO-Schema-Entry | SEO-Suite, SEO-View | 84 | 86 | 85 | 85 |
| `CMS/admin/seo-sitemap.php` | SEO-Sitemap-Entry | SEO-Suite, Sitemap/Indexing | 80 | 78 | 84 | 80 |
| `CMS/admin/seo-social.php` | SEO-Social-Entry | SEO-Suite, SEO-View | 84 | 86 | 85 | 85 |
| `CMS/admin/seo-technical.php` | SEO-Technical-Entry | SEO-Suite, Redirect/Link-Checks | 82 | 80 | 84 | 82 |
| `CMS/admin/settings.php` | Settings-Entry | `SettingsModule`, Settings-View | 82 | 80 | 84 | 82 |
| `CMS/admin/site-tables.php` | Site-Tables-Entry | `TablesModule`, Tables-Views | 80 | 78 | 84 | 80 |
| `CMS/admin/subscription-settings.php` | Subscription-Settings-Entry | Billing-Modul, Subscription-View | 80 | 78 | 84 | 80 |
| `CMS/admin/support.php` | Support-Entry | `SupportModule`, Support-View | 89 | 93 | 86 | 89 |
| `CMS/admin/system-info.php` | System-Info-Entry | `SystemInfoModule`, System-View | 89 | 93 | 86 | 89 |
| `CMS/admin/system-monitor-page.php` | System-Monitor-Entry | Performance/System-View | 89 | 93 | 86 | 89 |
| `CMS/admin/table-of-contents.php` | TOC-Entry | `TocModule`, TOC-View | 84 | 86 | 85 | 85 |
| `CMS/admin/theme-editor.php` | Theme-Editor-Entry | Theme-Editor-Modul, FS | 72 | 72 | 82 | 74 |
| `CMS/admin/theme-explorer.php` | Theme-Explorer-Entry | Theme-Modul, FS | 80 | 79 | 85 | 81 |
| `CMS/admin/theme-marketplace.php` | Theme-Marketplace-Entry | Theme-Marketplace, Remote | 74 | 68 | 82 | 74 |
| `CMS/admin/theme-settings.php` | Theme-Settings-Entry | Theme-Modul, Theme-View | 84 | 86 | 85 | 85 |
| `CMS/admin/themes.php` | Themes-Entry | `ThemesModule`, Theme-Views | 80 | 82 | 84 | 81 |
| `CMS/admin/updates.php` | Updates-Entry | `UpdatesModule`, System-View | 78 | 76 | 84 | 79 |
| `CMS/admin/user-settings.php` | User-Settings-Entry | `UserSettingsModule`, Users-View | 84 | 86 | 85 | 85 |
| `CMS/admin/users.php` | Users-Entry | `UsersModule`, Users-Views | 84 | 84 | 85 | 84 |
| `CMS/admin/modules/comments/CommentsModule.php` | Kommentar-Logik | DB, Kommentarstatus, View-Daten | 87 | 77 | 87 | 84 |
| `CMS/admin/modules/dashboard/DashboardModule.php` | Dashboard-Datenlogik | `DashboardService`, DB, KPI-Builder | 80 | 74 | 84 | 79 |
| `CMS/admin/modules/hub/HubSitesModule.php` | Hub-Sites-Logik | DB, Settings, Hub-Views | 83 | 71 | 85 | 80 |
| `CMS/admin/modules/hub/HubTemplateProfileCatalog.php` | Hub-Profilkatalog | Profile, Defaults, Hub-Editor | 84 | 88 | 84 | 85 |
| `CMS/admin/modules/hub/HubTemplateProfileManager.php` | Hub-Profilmanager | DB, JSON, Hub-Templates | 84 | 74 | 87 | 82 |
| `CMS/admin/modules/landing/LandingPageModule.php` | Landing-Builder-Logik | DB/Settings, Landing-View | 88 | 76 | 86 | 83 |
| `CMS/admin/modules/legal/CookieManagerModule.php` | Cookie-Manager-Logik | DB, Settings, Code-Snippets | 84 | 74 | 86 | 82 |
| `CMS/admin/modules/legal/DeletionRequestsModule.php` | Löschanfragen-Logik | DB, DSGVO-Hooks | 80 | 76 | 84 | 80 |
| `CMS/admin/modules/legal/LegalSitesModule.php` | Rechtstexte-Logik | DB, Templates, Escaping | 86 | 74 | 85 | 82 |
| `CMS/admin/modules/legal/PrivacyRequestsModule.php` | Privacy-Request-Logik | DB, DSGVO-Prozess | 80 | 76 | 84 | 80 |
| `CMS/admin/modules/media/MediaModule.php` | Medienlogik | `MediaService`, Upload, FS | 76 | 70 | 84 | 77 |
| `CMS/admin/modules/member/MemberDashboardModule.php` | Member-Dashboard-Logik | DB, Settings, Widgets | 88 | 72 | 86 | 83 |
| `CMS/admin/modules/menus/MenuEditorModule.php` | Menülogik | DB, Menübaum, CRUD | 80 | 70 | 84 | 78 |
| `CMS/admin/modules/pages/PagesModule.php` | Seitenlogik | DB, SEO, Kategorien, Bulk | 83 | 68 | 86 | 80 |
| `CMS/admin/modules/plugins/PluginMarketplaceModule.php` | Plugin-Marketplace-Logik | Registry, Remote-Download | 72 | 66 | 82 | 73 |
| `CMS/admin/modules/plugins/PluginsModule.php` | Plugin-Management-Logik | Settings, Aktivierung, FS | 78 | 80 | 84 | 80 |
| `CMS/admin/modules/posts/PostsCategoryViewModelBuilder.php` | Kategorie-ViewModel-Helfer | Posts-Modul, Kategorien-UI | 88 | 92 | 86 | 89 |
| `CMS/admin/modules/posts/PostsModule.php` | Beitragslogik | DB, SEO, Media, Redirects | 83 | 66 | 86 | 79 |
| `CMS/admin/modules/security/AntispamModule.php` | Antispam-Logik | Settings, Regeln, Security-View | 84 | 86 | 84 | 85 |
| `CMS/admin/modules/security/FirewallModule.php` | Firewall-Logik | DB, Regeln, Security-Logs | 84 | 73 | 87 | 82 |
| `CMS/admin/modules/security/SecurityAuditModule.php` | Security-Audit-Logik | Scanner, Settings, Reports | 86 | 73 | 86 | 82 |
| `CMS/admin/modules/seo/AnalyticsModule.php` | SEO-Analytics-Logik | DB, PageViews, KPIs | 80 | 66 | 84 | 77 |
| `CMS/admin/modules/seo/PerformanceModule.php` | Performance-Logik | DB, FS, Sessions, Cache | 76 | 58 | 84 | 73 |
| `CMS/admin/modules/seo/RedirectManagerModule.php` | Redirect-Logik | Redirect-Service, DB | 82 | 78 | 84 | 81 |
| `CMS/admin/modules/seo/SeoDashboardModule.php` | SEO-Dashboard-Logik | SEO-Services, KPIs | 84 | 82 | 84 | 83 |
| `CMS/admin/modules/seo/SeoSuiteModule.php` | SEO-Suite-Kernlogik | SEO/Analytics/Indexing/Redirect | 76 | 56 | 84 | 72 |
| `CMS/admin/modules/settings/SettingsModule.php` | Settings-Kernlogik | DB, Mail, URL-Migration | 76 | 60 | 84 | 74 |
| `CMS/admin/modules/subscriptions/OrdersModule.php` | Orders-Logik | DB, Abos, Zahlungsdaten | 80 | 76 | 84 | 80 |
| `CMS/admin/modules/subscriptions/PackagesModule.php` | Paket-Logik | DB, Paket-CRUD | 82 | 80 | 84 | 82 |
| `CMS/admin/modules/subscriptions/SubscriptionSettingsModule.php` | Billing-Settings-Logik | Settings, Gateway-Optionen | 78 | 74 | 84 | 78 |
| `CMS/admin/modules/system/BackupsModule.php` | Backup-Logik | FS, Dumps, Restore | 90 | 66 | 87 | 82 |
| `CMS/admin/modules/system/DocumentationCatalog.php` | Doku-Katalog | Doku-Service, Quellen | 84 | 88 | 84 | 85 |
| `CMS/admin/modules/system/DocumentationGitSync.php` | Git-Doku-Sync | Git/Remote, FS | 72 | 62 | 82 | 72 |
| `CMS/admin/modules/system/DocumentationGithubZipSync.php` | GitHub-Zip-Sync | Remote-Zip, FS | 72 | 62 | 82 | 72 |
| `CMS/admin/modules/system/DocumentationModule.php` | Doku-Logik | Renderer, Sync, Catalog | 86 | 76 | 86 | 83 |
| `CMS/admin/modules/system/DocumentationRenderer.php` | Doku-Renderer | Markdown→HTML, Escaping | 87 | 78 | 86 | 84 |
| `CMS/admin/modules/system/DocumentationSyncDownloader.php` | Doku-Downloader | HTTP/Remote, FS | 72 | 62 | 82 | 72 |
| `CMS/admin/modules/system/DocumentationSyncEnvironment.php` | Doku-Env-Check | Runtime/Env-Checks | 84 | 88 | 84 | 85 |
| `CMS/admin/modules/system/DocumentationSyncFilesystem.php` | Doku-FS-Logik | FS, Pfade, Speicherung | 88 | 74 | 86 | 84 |
| `CMS/admin/modules/system/DocumentationSyncService.php` | Doku-Sync-Orchestrator | Downloader, FS, GitSync | 84 | 67 | 87 | 80 |
| `CMS/admin/modules/system/MailSettingsModule.php` | Mail-Settings-Logik | Mailservice, Settings | 78 | 74 | 84 | 78 |
| `CMS/admin/modules/system/SupportModule.php` | Support-Logik | Tickets/Hinweise, leicht | 86 | 88 | 84 | 86 |
| `CMS/admin/modules/system/SystemInfoModule.php` | Systeminfo-Logik | Env/PHP/Serverdaten | 88 | 84 | 84 | 86 |
| `CMS/admin/modules/system/UpdatesModule.php` | Update-Logik | Remote/Versionen, FS | 76 | 70 | 84 | 77 |
| `CMS/admin/modules/tables/TablesModule.php` | Site-Tables-Logik | DB, JSON, Tabellen-CRUD | 76 | 64 | 84 | 75 |
| `CMS/admin/modules/themes/DesignSettingsModule.php` | Theme-Design-Logik | Settings, Theme-Optionen | 84 | 82 | 84 | 83 |
| `CMS/admin/modules/themes/FontManagerModule.php` | Font-Logik | Upload, FS, Theme-Assets | 78 | 70 | 84 | 77 |
| `CMS/admin/modules/themes/ThemeEditorModule.php` | Theme-Editor-Logik | FS, Theme-Dateien | 78 | 75 | 85 | 79 |
| `CMS/admin/modules/themes/ThemeMarketplaceModule.php` | Theme-Marketplace-Logik | Remote-Registry, Download | 72 | 66 | 82 | 73 |
| `CMS/admin/modules/themes/ThemesModule.php` | Theme-Management-Logik | Aktivierung, Löschung, FS | 80 | 78 | 84 | 80 |
| `CMS/admin/modules/toc/TocModule.php` | TOC-Logik | Settings, Parser, UI | 84 | 88 | 84 | 85 |
| `CMS/admin/modules/users/GroupsModule.php` | Gruppen-Logik | DB, User-Gruppen | 82 | 82 | 84 | 82 |
| `CMS/admin/modules/users/RolesModule.php` | Rollen-Logik | DB, RBAC | 82 | 82 | 84 | 82 |
| `CMS/admin/modules/users/UserSettingsModule.php` | User-Settings-Logik | Settings, Registrierung | 84 | 84 | 84 | 84 |
| `CMS/admin/modules/users/UsersModule.php` | User-CRUD-Logik | `UserService`, DB, Filter | 80 | 76 | 84 | 80 |
| `CMS/admin/partials/footer.php` | Admin-Footer | globale JS, Page-Assets | 88 | 92 | 84 | 88 |
| `CMS/admin/partials/header.php` | Admin-Header | Meta, CSS, Cache-Header | 88 | 92 | 84 | 88 |
| `CMS/admin/partials/section-page-shell.php` | Section-Shell | Auth, CSRF, Modul-Factory, View | 90 | 94 | 85 | 90 |
| `CMS/admin/partials/sidebar.php` | Admin-Sidebar | Navigation, DB-Settings, Icons | 84 | 86 | 84 | 84 |
| `CMS/admin/views/comments/list.php` | Kommentar-Liste | Comments-Modul, Flash-Partial | 88 | 88 | 86 | 87 |
| `CMS/admin/views/dashboard/index.php` | Dashboard-UI | `DashboardModule`, Inline-Helfer, KPIs | 82 | 74 | 83 | 80 |
| `CMS/admin/views/hub/edit.php` | Hub-Site-Edit-UI | Hub-Modul | 82 | 80 | 83 | 82 |
| `CMS/admin/views/hub/list.php` | Hub-Site-Liste | Hub-Modul | 86 | 88 | 84 | 86 |
| `CMS/admin/views/hub/template-edit.php` | Hub-Template-Edit-UI | Hub-Modul, Subpartials | 78 | 72 | 82 | 77 |
| `CMS/admin/views/hub/template-edit/main-column.php` | Hub-Edit-Hauptspalte | Parent-View | 86 | 90 | 84 | 87 |
| `CMS/admin/views/hub/template-edit/sidebar-column.php` | Hub-Edit-Seitenspalte | Parent-View | 86 | 90 | 84 | 87 |
| `CMS/admin/views/hub/templates.php` | Hub-Template-Liste | Hub-Module | 86 | 88 | 84 | 86 |
| `CMS/admin/views/landing/page.php` | Landing-Builder-UI | `LandingPageModule`, viele Formblöcke | 80 | 70 | 82 | 78 |
| `CMS/admin/views/legal/cookies.php` | Cookie-UI | `CookieManagerModule` | 82 | 80 | 83 | 82 |
| `CMS/admin/views/legal/data-requests.php` | DSGVO-Export-UI | Legal-Modul | 86 | 88 | 84 | 86 |
| `CMS/admin/views/legal/deletion-requests.php` | Löschanfrage-UI | Legal-Modul | 86 | 88 | 84 | 86 |
| `CMS/admin/views/legal/privacy-requests.php` | Privacy-Request-UI | Legal-Modul | 86 | 88 | 84 | 86 |
| `CMS/admin/views/legal/sites.php` | Rechtstext-UI | `LegalSitesModule` | 82 | 80 | 83 | 82 |
| `CMS/admin/views/media/categories.php` | Medien-Kategorien-UI | `MediaModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/media/library.php` | Medienbibliothek-UI | `MediaModule`, Upload/Picker | 80 | 74 | 82 | 79 |
| `CMS/admin/views/media/settings.php` | Medien-Settings-UI | `MediaModule` | 84 | 84 | 83 | 84 |
| `CMS/admin/views/member/dashboard.php` | Member-Dashboard-UI | `MemberDashboardModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/member/design.php` | Member-Design-UI | Member-Modul | 86 | 88 | 84 | 86 |
| `CMS/admin/views/member/frontend-modules.php` | Member-Frontend-Module-UI | Member-Modul | 86 | 88 | 84 | 86 |
| `CMS/admin/views/member/general.php` | Member-General-UI | Member-Modul | 86 | 88 | 84 | 86 |
| `CMS/admin/views/member/notifications.php` | Member-Notifications-UI | Member-Modul | 86 | 88 | 84 | 86 |
| `CMS/admin/views/member/onboarding.php` | Member-Onboarding-UI | Member-Modul | 86 | 88 | 84 | 86 |
| `CMS/admin/views/member/plugin-widgets.php` | Member-Pluginwidget-UI | Member-Modul | 86 | 88 | 84 | 86 |
| `CMS/admin/views/member/profile-fields.php` | Member-Profilfeld-UI | Member-Modul | 84 | 84 | 83 | 84 |
| `CMS/admin/views/member/subnav.php` | Member-Subnav | Member-Views | 90 | 95 | 85 | 90 |
| `CMS/admin/views/member/widgets.php` | Member-Widgets-UI | Member-Modul | 86 | 88 | 84 | 86 |
| `CMS/admin/views/menus/editor.php` | Menü-Editor-UI | `MenuEditorModule`, viele Formblöcke | 78 | 72 | 82 | 77 |
| `CMS/admin/views/pages/edit.php` | Seiten-Editor-UI | `PagesModule`, SEO-Partials, EditorJS | 78 | 72 | 82 | 77 |
| `CMS/admin/views/pages/list.php` | Seiten-Liste | `PagesModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/partials/content-advanced-seo-panel.php` | SEO-Advanced-Panel | Posts/Pages-Edit | 90 | 95 | 85 | 90 |
| `CMS/admin/views/partials/content-preview-card.php` | SERP/Social-Preview | Posts/Pages-Edit | 90 | 95 | 85 | 90 |
| `CMS/admin/views/partials/content-readability-card.php` | Readability-Card | Posts/Pages-Edit | 90 | 95 | 85 | 90 |
| `CMS/admin/views/partials/content-seo-score-panel.php` | SEO-Score-Panel | Posts/Pages-Edit | 90 | 95 | 85 | 90 |
| `CMS/admin/views/partials/empty-table-row.php` | Empty-State-Zeile | Tabellen-Views | 90 | 95 | 85 | 90 |
| `CMS/admin/views/partials/featured-image-picker.php` | Featured-Image-Picker | Posts/Pages-Edit, Media | 86 | 90 | 84 | 87 |
| `CMS/admin/views/partials/flash-alert.php` | Flash-Alert-Partial | viele Admin-Views | 90 | 95 | 85 | 90 |
| `CMS/admin/views/partials/section-subnav.php` | Section-Subnav | SEO/System/Performance-Views | 90 | 95 | 85 | 90 |
| `CMS/admin/views/performance/cache.php` | Cache-UI | `PerformanceModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/performance/database.php` | DB-Perf-UI | `PerformanceModule` | 84 | 84 | 83 | 84 |
| `CMS/admin/views/performance/media.php` | Media-Perf-UI | `PerformanceModule` | 84 | 84 | 83 | 84 |
| `CMS/admin/views/performance/sessions.php` | Sessions-UI | `PerformanceModule` | 84 | 84 | 83 | 84 |
| `CMS/admin/views/performance/settings.php` | Perf-Settings-UI | `PerformanceModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/performance/subnav.php` | Performance-Subnav | Performance-Views | 90 | 95 | 85 | 90 |
| `CMS/admin/views/plugins/list.php` | Plugin-Liste | `PluginsModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/plugins/marketplace.php` | Plugin-Marketplace-UI | Marketplace-Modul, Remote | 82 | 80 | 83 | 82 |
| `CMS/admin/views/posts/categories.php` | Post-Kategorien-UI | Posts/Page-Module | 86 | 88 | 84 | 86 |
| `CMS/admin/views/posts/edit.php` | Beitrags-Editor-UI | `PostsModule`, SEO-Partials, EditorJS | 76 | 70 | 82 | 76 |
| `CMS/admin/views/posts/list.php` | Beitrags-Liste | `PostsModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/posts/tags.php` | Post-Tags-UI | `PostsModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/security/antispam.php` | Antispam-UI | `AntispamModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/security/audit.php` | Security-Audit-UI | `SecurityAuditModule` | 84 | 84 | 83 | 84 |
| `CMS/admin/views/security/firewall.php` | Firewall-UI | `FirewallModule` | 87 | 84 | 85 | 85 |
| `CMS/admin/views/seo/analytics.php` | SEO-Analytics-UI | `AnalyticsModule` | 84 | 82 | 83 | 83 |
| `CMS/admin/views/seo/audit.php` | SEO-Audit-UI | SEO-Suite | 84 | 82 | 83 | 83 |
| `CMS/admin/views/seo/dashboard.php` | SEO-Dashboard-UI | SEO-Module | 86 | 88 | 84 | 86 |
| `CMS/admin/views/seo/meta.php` | SEO-Meta-UI | SEO-Suite | 86 | 88 | 84 | 86 |
| `CMS/admin/views/seo/not-found.php` | 404/Redirect-UI | Redirect-Modul | 84 | 84 | 83 | 84 |
| `CMS/admin/views/seo/performance.php` | SEO-Perf-UI | Performance-/SEO-Modul | 84 | 82 | 83 | 83 |
| `CMS/admin/views/seo/redirects.php` | Redirect-UI | Redirect-Modul | 84 | 84 | 83 | 84 |
| `CMS/admin/views/seo/schema.php` | Schema-UI | SEO-Suite | 86 | 88 | 84 | 86 |
| `CMS/admin/views/seo/sitemap.php` | Sitemap-UI | SEO-Suite, Indexing | 84 | 84 | 83 | 84 |
| `CMS/admin/views/seo/social.php` | SEO-Social-UI | SEO-Suite | 86 | 88 | 84 | 86 |
| `CMS/admin/views/seo/subnav.php` | SEO-Subnav | SEO-Views | 90 | 95 | 85 | 90 |
| `CMS/admin/views/seo/technical.php` | SEO-Technical-UI | SEO-Suite, Redirects | 84 | 82 | 83 | 83 |
| `CMS/admin/views/settings/general.php` | General-Settings-UI | `SettingsModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/subscriptions/orders.php` | Orders-UI | `OrdersModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/subscriptions/packages.php` | Packages-UI | `PackagesModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/subscriptions/settings.php` | Billing-Settings-UI | Subscription-Settings-Modul | 84 | 84 | 83 | 84 |
| `CMS/admin/views/system/backups.php` | Backup-UI | `BackupsModule` | 84 | 84 | 83 | 84 |
| `CMS/admin/views/system/cron-status.php` | Cron-Status-UI | System-/Performance-Modul | 88 | 92 | 84 | 88 |
| `CMS/admin/views/system/diagnose.php` | Diagnose-UI | System-Modul | 88 | 92 | 84 | 88 |
| `CMS/admin/views/system/disk-usage.php` | Disk-Usage-UI | System-/FS-Daten | 88 | 90 | 84 | 87 |
| `CMS/admin/views/system/documentation.php` | Doku-UI | Doku-Module, Renderer | 84 | 82 | 83 | 83 |
| `CMS/admin/views/system/email-alerts.php` | Email-Alerts-UI | System-/Log-Daten | 88 | 92 | 84 | 88 |
| `CMS/admin/views/system/health-check.php` | Health-Check-UI | System-Modul | 88 | 92 | 84 | 88 |
| `CMS/admin/views/system/info.php` | Systeminfo-UI | SystemInfo-Modul | 88 | 92 | 84 | 88 |
| `CMS/admin/views/system/mail-settings.php` | Mail-Settings-UI | Mail-Modul | 84 | 84 | 83 | 84 |
| `CMS/admin/views/system/response-time.php` | Response-Time-UI | Perf-/System-Modul | 88 | 92 | 84 | 88 |
| `CMS/admin/views/system/scheduled-tasks.php` | Scheduled-Tasks-UI | System-Modul | 88 | 92 | 84 | 88 |
| `CMS/admin/views/system/subnav.php` | System-Subnav | System-Views | 90 | 95 | 85 | 90 |
| `CMS/admin/views/system/support.php` | Support-UI | Support-Modul | 88 | 92 | 84 | 88 |
| `CMS/admin/views/system/updates.php` | Updates-UI | `UpdatesModule` | 84 | 84 | 83 | 84 |
| `CMS/admin/views/tables/edit.php` | Tabellen-Editor-UI | `TablesModule` | 82 | 80 | 83 | 82 |
| `CMS/admin/views/tables/list.php` | Tabellen-Liste | `TablesModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/tables/settings.php` | Tabellen-Settings-UI | `TablesModule` | 84 | 84 | 83 | 84 |
| `CMS/admin/views/themes/editor.php` | Theme-Editor-UI | Theme-Editor-Modul, FS | 80 | 76 | 84 | 80 |
| `CMS/admin/views/themes/fonts.php` | Theme-Fonts-UI | Font-Modul | 84 | 84 | 83 | 84 |
| `CMS/admin/views/themes/list.php` | Theme-Liste | `ThemesModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/themes/marketplace.php` | Theme-Marketplace-UI | Marketplace-Modul, Remote | 82 | 80 | 83 | 82 |
| `CMS/admin/views/themes/settings.php` | Theme-Settings-UI | Theme-Modul | 86 | 88 | 84 | 86 |
| `CMS/admin/views/toc/settings.php` | TOC-Settings-UI | `TocModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/users/edit.php` | User-Edit-UI | `UsersModule` | 84 | 84 | 83 | 84 |
| `CMS/admin/views/users/groups.php` | User-Groups-UI | `GroupsModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/users/list.php` | User-Liste | `UsersModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/users/roles.php` | User-Roles-UI | `RolesModule` | 86 | 88 | 84 | 86 |
| `CMS/admin/views/users/settings.php` | User-Settings-UI | `UserSettingsModule` | 86 | 88 | 84 | 86 |

## Core & Includes

### Bewertung

| Datei | Kurzbeschreibung | Verknüpfung/Abhängigkeiten | Security | Speed | PHP/BP | Gesamt |
|---|---|---|---:|---:|---:|---:|
| `core/Api.php` | API-Fassade für Kernendpunkte und JSON-Antworten. | Router, Auth, Json, Service-Layer | 72 | 78 | 80 | 77 |
| `core/AuditLogger.php` | Protokolliert sicherheits- und administrationsrelevante Ereignisse. | Logger, Database, Security, Konfiguration | 84 | 80 | 84 | 83 |
| `core/Auth.php` | Kompakter Zugriffspunkt für Authentifizierungslogik. | AuthManager, Security, Session, User-Kontext | 74 | 82 | 80 | 79 |
| `core/Auth/AuthManager.php` | Orchestriert Login, Session und Auth-Provider. | Database, Security, UserService, Session | 72 | 80 | 84 | 79 |
| `core/Auth/LDAP/LdapAuthProvider.php` | Bindet einen LDAP-Provider für externe Anmeldung an. | AuthManager, LDAP-Erweiterung, Konfiguration | 66 | 74 | 80 | 73 |
| `core/Auth/MFA/BackupCodesManager.php` | Verwaltet Fallback-Codes für MFA-Szenarien. | Security, Database, User-Kontext | 70 | 82 | 84 | 79 |
| `core/Auth/MFA/TotpAdapter.php` | Adapter für TOTP-basierte Mehrfaktorprüfung. | Totp, Security, AuthManager | 71 | 83 | 85 | 80 |
| `core/Auth/Passkey/WebAuthnAdapter.php` | Adapter für Passkey/WebAuthn-Authentifizierung. | AuthManager, Security, WebAuthn-Bibliothek | 69 | 76 | 83 | 76 |
| `core/autoload.php` | Lädt Kernklassen automatisch nach Namenskonvention. | Bootstrap, Dateisystem, Klassenpfade | 88 | 90 | 76 | 85 |
| `core/Bootstrap.php` | Startet den Kern, lädt Abhängigkeiten und initialisiert Routing. | autoload, Container, Router, includes/functions.php | 78 | 76 | 84 | 79 |
| `core/CacheManager.php` | Vereinheitlicht Cache-Zugriffe und Cache-Backends. | CacheInterface, Konfiguration, Dateisystem | 86 | 88 | 86 | 87 |
| `core/Container.php` | Einfacher DI-/Service-Container für Kernobjekte. | Bootstrap, Services, Konfiguration | 84 | 87 | 86 | 86 |
| `core/Contracts/CacheInterface.php` | Vertrag für Cache-Implementierungen. | CacheManager, konkrete Cache-Backends | 94 | 95 | 92 | 94 |
| `core/Contracts/DatabaseInterface.php` | Vertrag für Datenbankzugriffe im Kern. | Database, Repositories, Services | 94 | 95 | 92 | 94 |
| `core/Contracts/LoggerInterface.php` | Vertrag für Logging-Implementierungen. | Logger, AuditLogger, ErrorReportService | 94 | 95 | 92 | 94 |
| `core/Database.php` | Zentrale Datenbankabstraktion für Queries und Verbindungen. | PDO, Konfiguration, Repositories, Services | 72 | 74 | 82 | 76 |
| `core/Debug.php` | Hilfslogik für Debug-Ausgaben und Diagnose. | Logger, Konfiguration, Laufzeitstatus | 82 | 84 | 78 | 81 |
| `core/Hooks.php` | Event-/Filter-System für Core, Themes und Plugins. | Bootstrap, PluginManager, ThemeManager | 84 | 88 | 84 | 85 |
| `core/Http/Client.php` | HTTP-Client für externe Requests und APIs. | cURL/Streams, Konfiguration, externe Dienste | 70 | 75 | 82 | 76 |
| `core/Json.php` | Kleine Hilfsschicht für JSON-Encoding und -Decoding. | Api, Services, PHP-JSON | 88 | 90 | 84 | 87 |
| `core/Logger.php` | Basislogger für Fehler, Hinweise und Betriebsdaten. | LoggerInterface, Dateisystem, Konfiguration | 85 | 83 | 83 | 84 |
| `core/Member/PluginDashboardRegistry.php` | Registriert Plugin-Widgets für das Member-Dashboard. | Hooks, PluginManager, MemberRouter | 86 | 88 | 84 | 86 |
| `core/MigrationManager.php` | Führt Schema- und Datenmigrationen zentral aus. | Database, SchemaManager, UpdateService | 74 | 70 | 82 | 75 |
| `core/PageManager.php` | Verwaltert Seitenlogik und Seitentemplates im Kern. | ThemeManager, Router, Database | 80 | 82 | 82 | 81 |
| `core/PluginManager.php` | Lädt, aktiviert und koordiniert Plugins. | Hooks, Bootstrap, Database, Dateisystem | 78 | 80 | 84 | 81 |
| `core/Router.php` | Gemeinsame Routing-Basis für unterschiedliche Kontexte. | PublicRouter, AdminRouter, ApiRouter, ThemeRouter | 78 | 82 | 84 | 81 |
| `core/Routing/AdminRouter.php` | Routet Admin-Anfragen in den Backend-Kontext. | Router, Auth, Security, Admin-Module | 74 | 80 | 82 | 79 |
| `core/Routing/ApiRouter.php` | Routet API-Anfragen und Übergaben an Handler. | Router, Api, Auth, Json | 72 | 79 | 82 | 78 |
| `core/Routing/MemberRouter.php` | Routet Anfragen für den Mitgliederbereich. | Router, Auth, MemberService, Themes/Plugins | 75 | 80 | 82 | 79 |
| `core/Routing/PublicRouter.php` | Öffentliche Frontend-Routen für Seiten und Inhalte. | Router, ThemeRouter, PageManager | 77 | 80 | 82 | 80 |
| `core/Routing/ThemeArchiveRepository.php` | Liefert Archivdaten für Theme-Routen. | ThemeRouter, Database, PermalinkService | 82 | 81 | 84 | 82 |
| `core/Routing/ThemeRouter.php` | Verbindet Frontend-Routing mit Theme-Templates. | PublicRouter, ThemeManager, PermalinkService | 78 | 79 | 84 | 80 |
| `core/SchemaManager.php` | Verwaltet DB-Schemas und strukturierte Tabellenänderungen. | Database, MigrationManager, UpdateService | 76 | 74 | 82 | 77 |
| `core/Security.php` | Stellt Kernfunktionen für Tokens und Schutzmechanismen bereit. | Auth, Session, CSRF/Token-Logik | 76 | 78 | 84 | 79 |
| `core/Services/AnalyticsService.php` | Bündelt Metriken und einfache Analytics-Auswertungen. | Database, TrackingService, Seo/Reporting | 76 | 76 | 82 | 78 |
| `core/Services/AzureMailTokenProvider.php` | Beschafft Zugriffstoken für Azure-/Graph-Mailversand. | GraphApiService, Konfiguration, externe APIs | 82 | 74 | 85 | 80 |
| `core/Services/BackupService.php` | Erstellt Sicherungen von Daten oder Dateien. | Database, Dateisystem, Zip/Archiv | 70 | 66 | 80 | 72 |
| `core/Services/CommentService.php` | Verarbeitet Kommentarlogik und zugehörige Aktionen. | Database, Security, Content/Member-Kontext | 84 | 80 | 85 | 83 |
| `core/Services/ContentLocalizationService.php` | Unterstützt Lokalisierung von Inhalten und Varianten. | TranslationService, Database, Routing | 82 | 80 | 84 | 82 |
| `core/Services/CookieConsentService.php` | Verwaltet Consent-Status und Cookie-Banner-Logik. | Security, SettingsService, Frontend-Hooks | 83 | 82 | 84 | 83 |
| `core/Services/CoreWebVitalsService.php` | Aggregiert oder bewertet Performance-Signale. | AnalyticsService, TrackingService, Frontend-Metriken | 85 | 72 | 84 | 80 |
| `core/Services/DashboardService.php` | Liefert Daten und Bausteine für Dashboards. | Database, Hooks, PluginDashboardRegistry | 80 | 82 | 84 | 82 |
| `core/Services/EditorJs/EditorJsAssetService.php` | Liefert Assets und Konfiguration für Editor.js. | EditorJsService, Theme/Asset-Layer, Dateisystem | 84 | 86 | 84 | 85 |
| `core/Services/EditorJs/EditorJsImageLibraryService.php` | Bindet Bildbibliothek in Editor.js ein. | MediaService, EditorJsMediaService, Uploads | 79 | 78 | 83 | 80 |
| `core/Services/EditorJs/EditorJsMediaService.php` | Vermittelt Mediendaten an Editor.js-Workflows. | MediaService, UploadHandler, RequestGuard | 73 | 76 | 82 | 77 |
| `core/Services/EditorJs/EditorJsRemoteMediaService.php` | Holt oder verarbeitet Remote-Medien für Editor.js. | Http\Client, MediaService, Sanitizer | 86 | 72 | 84 | 80 |
| `core/Services/EditorJs/EditorJsRequestGuard.php` | Prüft Berechtigungen und Request-Schutz für Editor.js. | Auth, Security, MemberService | 86 | 85 | 86 | 86 |
| `core/Services/EditorJs/EditorJsSanitizer.php` | Säubert Editor.js-Blockdaten vor Speicherung/Rendern. | PurifierService, Security, EditorJsService | 90 | 86 | 86 | 87 |
| `core/Services/EditorJs/EditorJsUploadService.php` | Upload-Workflow für Editor.js-Dateien und Bilder. | FileUploadService, MediaService, Security | 69 | 74 | 82 | 75 |
| `core/Services/EditorJsRenderer.php` | Rendert Editor.js-Daten in HTML-Ausgabe. | EditorJsService, Sanitizer, Theme-Templates | 84 | 84 | 84 | 84 |
| `core/Services/EditorJsService.php` | Zentrale Koordination für Editor.js-Inhalte. | Renderer, Sanitizer, AssetService | 78 | 80 | 82 | 80 |
| `core/Services/EditorService.php` | Allgemeiner Editor-Service für Content-Bearbeitung. | EditorJsService, MediaService, SettingsService | 80 | 81 | 82 | 81 |
| `core/Services/ElfinderService.php` | Adapter/Service für elFinder-Dateiverwaltung. | Dateisystem, Auth, externe elFinder-Assets | 68 | 70 | 78 | 72 |
| `core/Services/ErrorReportService.php` | Sammelt und meldet Fehlerberichte strukturiert. | Logger, MailService, AuditLogger | 78 | 76 | 82 | 79 |
| `core/Services/FeatureUsageService.php` | Erfasst oder aggregiert Feature-Nutzungsdaten. | Database, AnalyticsService, PluginManager | 80 | 84 | 84 | 83 |
| `core/Services/FeedService.php` | Verarbeitet externe oder interne Feed-Inhalte. | Http\Client, XML/Feed-Library, Cache | 84 | 76 | 84 | 81 |
| `core/Services/FileUploadService.php` | Kapselt generische Datei-Uploads und Validierung. | Security, Dateisystem, MediaService | 77 | 74 | 84 | 78 |
| `core/Services/GraphApiService.php` | Wrapper für Microsoft Graph-bezogene Aufrufe. | Http\Client, AzureMailTokenProvider, Konfiguration | 80 | 71 | 85 | 79 |
| `core/Services/ImageService.php` | Allzweckservice für Bildverarbeitung und Bildpfade. | ImageProcessor, MediaService, Dateisystem | 76 | 72 | 82 | 77 |
| `core/Services/IndexingService.php` | Reicht URLs/Änderungen an Suchmaschinen-Indizierung weiter. | SitemapService, Http\Client, SEO-Settings | 74 | 77 | 82 | 78 |
| `core/Services/JwtService.php` | Erzeugt und prüft JWTs für API-/Token-Szenarien. | Security, Json, Konfiguration | 68 | 84 | 82 | 78 |
| `core/Services/Landing/LandingDefaultsProvider.php` | Liefert Default-Daten für Landing-Page-Bausteine. | LandingPageService, ThemeManager, Konfiguration | 86 | 88 | 84 | 86 |
| `core/Services/Landing/LandingFeatureService.php` | Baut Feature-Abschnitte für Landing-Pages auf. | LandingRepository, LandingDefaultsProvider, Themes | 82 | 83 | 84 | 83 |
| `core/Services/Landing/LandingHeaderService.php` | Verwaltet Header-/Hero-Daten für Landing-Pages. | LandingRepository, ThemeManager, MediaService | 84 | 85 | 84 | 84 |
| `core/Services/Landing/LandingPluginService.php` | Bindet Plugins in Landing-Page-Module ein. | PluginManager, LandingPageService, Hooks | 76 | 80 | 82 | 79 |
| `core/Services/Landing/LandingRepository.php` | Persistiert Landing-Page-Daten und Konfigurationen. | Database, LandingSectionService, MediaService | 78 | 77 | 84 | 80 |
| `core/Services/Landing/LandingSanitizer.php` | Säubert Landing-Page-Eingaben und Blockdaten. | PurifierService, Security, LandingRepository | 90 | 86 | 86 | 87 |
| `core/Services/Landing/LandingSectionProfileService.php` | Liefert Profil-/Schema-Infos für Landing-Sections. | LandingSectionService, DefaultsProvider, Themes | 81 | 82 | 84 | 82 |
| `core/Services/Landing/LandingSectionService.php` | Verarbeitet einzelne Inhaltssektionen von Landing-Pages. | LandingRepository, Sanitizer, MediaService | 80 | 82 | 84 | 82 |
| `core/Services/LandingPageService.php` | Oberer Service für Landing-Page-Renderlogik und Daten. | Landing-Subservices, ThemeManager, Hooks | 79 | 80 | 84 | 81 |
| `core/Services/MailLogService.php` | Protokolliert Mail-Versand und Zustellstatus. | MailService, Database, Logger | 78 | 80 | 82 | 80 |
| `core/Services/MailQueueService.php` | Verwaltet Warteschlange für asynchronen Mailversand. | MailService, Database, Cron/Queue-Logik | 76 | 78 | 82 | 79 |
| `core/Services/MailService.php` | Zentraler Versandservice für E-Mails. | Konfiguration, Queue/Log, externe Mailer | 88 | 74 | 86 | 83 |
| `core/Services/Media/ImageProcessor.php` | Führt Bildtransformationen und Ableitungen aus. | GD/Imagick, Dateisystem, ImageService | 74 | 70 | 82 | 75 |
| `core/Services/Media/MediaRepository.php` | Persistiert Metadaten von Medienobjekten. | Database, MediaService, Uploads | 78 | 80 | 84 | 81 |
| `core/Services/Media/UploadHandler.php` | Operativer Handler für Datei- und Medienuploads. | FileUploadService, Security, Dateisystem | 69 | 72 | 80 | 74 |
| `core/Services/MediaDeliveryService.php` | Steuert Auslieferung und Pfadauflösung für Medien. | MediaRepository, ImageService, Cache | 74 | 82 | 82 | 79 |
| `core/Services/MediaService.php` | Zentrale Medienverwaltung für Uploads und Abrufe. | MediaRepository, UploadHandler, ImageService | 72 | 76 | 82 | 77 |
| `core/Services/MemberService.php` | Verwaltet Berechtigungen und Daten des Member-Bereichs. | Auth, Database, MemberRouter, Plugins | 74 | 78 | 82 | 78 |
| `core/Services/MessageService.php` | Vereinheitlicht Flash-/Systemmeldungen im Laufzeitkontext. | Session, Router, Admin/Member/UI | 82 | 86 | 84 | 84 |
| `core/Services/OpcacheWarmupService.php` | Wärmt wichtige Skripte/Assets für schnellere Laufzeit auf. | Dateisystem, PHP-OPcache, Bootstrap | 80 | 88 | 82 | 83 |
| `core/Services/PdfService.php` | Erzeugt oder liefert PDF-Dokumente aus. | dompdf/Renderer, Dateisystem, Content-Daten | 70 | 68 | 80 | 73 |
| `core/Services/PermalinkService.php` | Berechnet konsistente URLs und Permalink-Strukturen. | Routing, ThemeRouter, SettingsService | 84 | 84 | 86 | 85 |
| `core/Services/PurifierService.php` | Kapselt HTML-Sanitizing für vertrauensarme Inhalte. | Security, externe Purifier-Library, Editor/Landing | 90 | 80 | 84 | 85 |
| `core/Services/RedirectService.php` | Verwaltet Redirect-Regeln und deren Auflösung. | Database, Router, SettingsService | 74 | 84 | 84 | 81 |
| `core/Services/SearchService.php` | Führt Suchabfragen aus und bereitet Treffer auf. | Database oder TNTSearch, Cache, Routing | 78 | 70 | 82 | 77 |
| `core/Services/SeoAnalysisService.php` | Analysiert Inhalte auf SEO-/Lesbarkeitsaspekte. | SEOService, Content-Daten, Analyzer-Logik | 84 | 76 | 84 | 81 |
| `core/Services/SEO/SeoAnalyticsRenderer.php` | Rendert SEO-bezogene Kennzahlen für UI/Reports. | SeoAnalysisService, DashboardService | 82 | 84 | 84 | 83 |
| `core/Services/SEO/SeoAuditService.php` | Führt SEO-Audits über Inhalte und Metadaten aus. | SeoMetaService, SitemapService, AnalysisService | 82 | 76 | 84 | 81 |
| `core/Services/SEO/SeoHeadRenderer.php` | Rendert Meta-Tags und Head-Ausgaben. | SeoMetaService, Theme-Hooks, SettingsStore | 84 | 86 | 84 | 85 |
| `core/Services/SEO/SeoMetaRepository.php` | Persistiert SEO-Metadaten in der Datenhaltung. | Database, SeoMetaService, Content-Daten | 79 | 80 | 84 | 81 |
| `core/Services/SEO/SeoMetaService.php` | Verarbeitet SEO-Metaobjekte und Standardwerte. | SeoMetaRepository, SettingsStore, HeadRenderer | 82 | 82 | 84 | 83 |
| `core/Services/SEO/SeoSchemaRenderer.php` | Rendert strukturierte Daten für Suchmaschinen. | SeoMetaService, Theme-Hooks, JSON-LD | 84 | 86 | 84 | 85 |
| `core/Services/SEO/SeoSettingsStore.php` | Kapselt Speicherung und Zugriff auf SEO-Settings. | SettingsService, Database, SEOService | 80 | 82 | 84 | 82 |
| `core/Services/SEO/SeoSitemapService.php` | Baut SEO-orientierte Sitemaps und zugehörige Daten. | SitemapService, Routing, SEO-Settings | 84 | 84 | 84 | 84 |
| `core/Services/SEOService.php` | Übergeordneter Service für SEO-Konfiguration und Abläufe. | Seo*-Services, SettingsService, Hooks | 80 | 81 | 84 | 82 |
| `core/Services/SettingsService.php` | Zentraler Zugriff auf Laufzeit- und Systemsettings. | Database, Konfiguration, Cache | 80 | 80 | 84 | 81 |
| `core/Services/SitemapService.php` | Erstellt und verwaltet XML-/Seiten-Sitemaps. | Routing, PermalinkService, SEOService | 84 | 82 | 84 | 83 |
| `core/Services/SiteTable/SiteTableDisplaySettings.php` | Hält Anzeigeeinstellungen für Site-Tabellen zusammen. | SiteTableService, SettingsService, UI-Renderer | 84 | 86 | 84 | 85 |
| `core/Services/SiteTable/SiteTableHubRenderer.php` | Rendert Hub-/Übersichtsansichten für Site-Tabellen. | SiteTableRepository, TemplateRegistry, Theme/UI | 82 | 84 | 84 | 83 |
| `core/Services/SiteTable/SiteTableRepository.php` | Datenzugriffsschicht für Site-Tabellen. | Database, SiteTableService | 79 | 79 | 84 | 81 |
| `core/Services/SiteTable/SiteTableTableRenderer.php` | Rendert tabellarische Ausgaben für Site-Daten. | Repository, DisplaySettings, Templates | 84 | 85 | 84 | 84 |
| `core/Services/SiteTable/SiteTableTemplateRegistry.php` | Registriert verfügbare Templates für Site-Tabellen. | Renderer, ThemeManager, PluginManager | 84 | 87 | 84 | 85 |
| `core/Services/SiteTableService.php` | Oberer Service für Site-Tabellen und Darstellung. | Repository, Renderer, Settings | 80 | 81 | 84 | 82 |
| `core/Services/StatusService.php` | Liefert kompakte Status-/Gesundheitsinformationen des Systems. | SystemService, UpdateService, Cache | 84 | 88 | 84 | 85 |
| `core/Services/SystemService.php` | Sammelt Systeminfos und Kernzustände. | StatusService, Konfiguration, Dateisystem | 82 | 80 | 84 | 82 |
| `core/Services/ThemeCustomizer.php` | Bindet Theme-Einstellungen und CSS-Generierung ein. | ThemeManager, SettingsService, Themes | 80 | 80 | 84 | 81 |
| `core/Services/TrackingService.php` | Erfasst Tracking-Ereignisse und einfache Nutzungsdaten. | AnalyticsService, CookieConsentService, Database | 74 | 78 | 82 | 78 |
| `core/Services/TranslationService.php` | Verwaltet Übersetzungen und sprachabhängige Inhalte. | includes/functions/translation.php, Settings, Content | 84 | 82 | 84 | 83 |
| `core/Services/UpdateService.php` | Steuert Update-Prüfung, Versionierung und Rollout-Schritte. | Version, MigrationManager, Netzwerk/Dateisystem | 72 | 74 | 82 | 76 |
| `core/Services/UserService.php` | Liefert Benutzerlogik jenseits des reinen Auth-Flows. | Database, AuthManager, MemberService | 74 | 79 | 82 | 78 |
| `core/SubscriptionManager.php` | Verwaltet Abos bzw. Subskriptionszustände zentral. | Database, MailService, subscription-helpers | 76 | 80 | 82 | 79 |
| `core/TableOfContents.php` | Erstellt Inhaltsverzeichnisse aus Content-Strukturen. | Editor/Content-Rendering, HTML-Parsing | 86 | 84 | 84 | 85 |
| `core/ThemeManager.php` | Lädt Themes, Assets und Template-Pfade. | Hooks, ThemeRouter, SettingsService, Dateisystem | 82 | 84 | 84 | 83 |
| `core/Totp.php` | Basiskomponente für TOTP-Berechnung und Prüfung. | Security, MFA-Adapter, Zeit-/Hashfunktionen | 74 | 84 | 82 | 80 |
| `core/VendorRegistry.php` | Registriert externe Pakete/Assets des Systems. | autoload, Bootstrap, Dateisystem | 84 | 88 | 84 | 85 |
| `core/Version.php` | Hält Versionsinformationen und Vergleichslogik bereit. | UpdateService, Bootstrap | 90 | 94 | 86 | 90 |
| `core/WP_Error.php` | Kompatibilitätshülle für WordPress-artige Fehlerobjekte. | wordpress-compat, Api, Services | 88 | 92 | 78 | 86 |
| `includes/functions.php` | Kanonischer Sammel-Loader für globale Hilfsfunktionen. | Bootstrap, functions/*.php, Core-Helfer | 80 | 84 | 76 | 80 |
| `includes/functions/admin-menu.php` | Hilfsfunktionen rund um Menüaufbau im Adminbereich. | Hooks, Theme/Plugin-Menüs, Router | 84 | 88 | 78 | 83 |
| `includes/functions/escaping.php` | Sammlung für sicheres Escaping in Ausgabe-Kontexten. | Security, Templates, globale Helfer | 92 | 92 | 84 | 89 |
| `includes/functions/mail.php` | Globale Mail-Helfer als dünne Komfortschicht. | MailService, Konfiguration, Queue/Log | 74 | 80 | 76 | 77 |
| `includes/functions/options-runtime.php` | Runtime-Helfer zum Lesen/Schreiben von Optionen. | SettingsService, Database, Cache | 82 | 86 | 78 | 82 |
| `includes/functions/redirects-auth.php` | Hilfsfunktionen für Auth-Redirects und Zielpfade. | Auth, Router, Security | 74 | 82 | 76 | 77 |
| `includes/functions/roles.php` | Rollen- und Berechtigungshelfer im globalen Kontext. | Auth, MemberService, Security | 82 | 88 | 78 | 83 |
| `includes/functions/translation.php` | Sprach- und Übersetzungshelfer für Templates/Core. | TranslationService, SettingsService | 86 | 88 | 80 | 85 |
| `includes/functions/wordpress-compat.php` | Kompatibilitätsschicht für WP-nahe Helper-APIs. | WP_Error, globale Helper, Legacy-Aufrufer | 84 | 86 | 74 | 81 |
| `includes/subscription-helpers.php` | Ergänzende Hilfsfunktionen für Abo-/Subscription-Flows. | SubscriptionManager, MailService, Database | 80 | 84 | 78 | 81 |

## Restliche Bereiche

### Bewertung

| Datei | Kurzbeschreibung | Verknüpfung/Abhängigkeiten | Security | Speed | PHP/BP | Gesamt |
| --- | --- | --- | ---: | ---: | ---: | ---: |
| `.htaccess` | Apache-Schutz und URL-Rewrite im CMS-Root | Apache `mod_rewrite`; Webserver-Zugriffsschutz | 80 | 82 | 75 | 79 |
| `assets/css/admin-hub-site-edit.css` | Styles für Hub-Site-Editor | Admin-Hub-UI; SunEditor-Markup | 88 | 78 | 72 | 79 |
| `assets/css/admin-hub-template-edit.css` | Styles für Hub-Template-Bearbeitung | Hub-Template-Preview; Admin-Editor | 88 | 78 | 76 | 81 |
| `assets/css/admin-hub-template-editor.css` | Styles für Hub-Template-Preview/Editor | Hub-Template-Renderer; Preview-Tokens | 88 | 78 | 76 | 81 |
| `assets/css/admin-tabler.css` | Tabler-Overrides und Admin-Branding | Tabler-Core; Admin-Layout | 88 | 78 | 78 | 81 |
| `assets/css/admin.css` | Allgemeine Admin-Komponentenstyles | Admin-UI; Formular- und Card-Komponenten | 88 | 72 | 72 | 77 |
| `assets/css/hub-sites.css` | Frontend-Styles für Hub-Sites | Hub-Site-Ausgabe; CSS-Custom-Properties | 88 | 78 | 72 | 79 |
| `assets/css/main.css` | Haupt-Frontend-Styles | Frontend-Templates; globale UI-Bausteine | 88 | 78 | 76 | 81 |
| `assets/css/member-dashboard.css` | Styles für Member-Dashboard | Member-Seiten; Design-Tokens | 88 | 78 | 78 | 81 |
| `assets/js/admin-content-editor.js` | Admin-Content-Editor-Logik | DOM; Editor-Formulare; JSON-Payloads | 62 | 79 | 80 | 74 |
| `assets/js/admin-grid.js` | Grid.js-Helfer für Admin-Tabellen | Grid.js; serverseitige Tabellen | 72 | 79 | 84 | 78 |
| `assets/js/admin-hub-site-edit.js` | Interaktion für Hub-Site-Bearbeitung | DOM; Hub-Site-Editor; JSON-Daten | 62 | 79 | 80 | 74 |
| `assets/js/admin-hub-template-edit.js` | Interaktion für Template-Bearbeitung | DOM; Hub-Template-Formulare | 62 | 79 | 84 | 75 |
| `assets/js/admin-hub-template-editor.js` | Logik für Template-Editor-Ansicht | DOM; Preview-Payload; Editor-Initialisierung | 62 | 75 | 80 | 72 |
| `assets/js/admin-media-integrations.js` | Einbindung externer Medien-Tools | FilePond; elFinder; Admin-Medien | 62 | 79 | 82 | 74 |
| `assets/js/admin-seo-editor.js` | SEO-Editor-Interaktionen | DOM; SEO-Felder; String-Normalisierung | 62 | 76 | 78 | 72 |
| `assets/js/admin-seo-redirects.js` | Redirect-/SEO-UI-Logik | DOM; Redirect-Konfiguration | 72 | 79 | 84 | 78 |
| `assets/js/admin.js` | Allgemeine Admin-JavaScript-Basis | Admin-UI; globale DOM-Interaktion | 72 | 79 | 84 | 78 |
| `assets/js/cookieconsent-init.js` | Initialisierung des Cookie-Consent-Banners | Frontend; CookieConsent; DOM | 72 | 79 | 80 | 77 |
| `assets/js/editor-init.js` | Initialisierung von Editor.js | Editor.js; HTML-zu-Blocks-Konvertierung | 62 | 79 | 86 | 76 |
| `assets/js/gridjs-init.js` | Grid.js-Initialisierungshilfe | Grid.js; serverseitige Data-Endpoints | 62 | 79 | 79 | 73 |
| `assets/js/member-dashboard.js` | Interaktionen im Member-Dashboard | DOM; Member-UI; WebAuthn/Base64-Helper | 72 | 79 | 84 | 78 |
| `assets/js/photoswipe-init.js` | Initialisierung der Lightbox | PhotoSwipe; Frontend-Galerien | 72 | 79 | 84 | 78 |
| `assets/js/web-vitals.js` | Versand/Erfassung von Web-Vitals | Browser-Performance-APIs; Telemetrie-Endpoint | 72 | 76 | 80 | 76 |
| `config.php` | Bootstrap-Stub für Konfiguration | `config/app.php`; `CacheManager`; Installer-Redirect | 81 | 67 | 86 | 78 |
| `config/.htaccess` | Webzugriffsschutz für Config-Verzeichnis | Apache-Zugriffsschutz; sensible Konfigs | 80 | 82 | 75 | 79 |
| `config/app.php` | Anwendungs-Konfigurationsvorlage | Bootstrap; DB-/App-Konstanten; Installer | 82 | 67 | 86 | 78 |
| `config/media-meta.json` | Medienkategorien und Metadaten | Medienverwaltung; Kategorie-Mapping | 82 | 85 | 82 | 83 |
| `config/media-settings.json` | Medien-Upload- und Typ-Einstellungen | Medienservice; Upload-Regeln | 82 | 85 | 82 | 83 |
| `cron.php` | CLI/Web-Cron-Endpunkt | `config.php`; `Bootstrap`; `MailQueueService`; `SettingsService`; `CMS\Hooks` | 59 | 67 | 74 | 67 |
| `index.php` | Haupt-Entry-Point des CMS | `config.php`; Autoloader; `CMS\Bootstrap`; Theme-Error-Fallback | 78 | 67 | 82 | 76 |
| `install.php` | Startpunkt des Installers | Installer-Bootstrap; Install-Controller | 75 | 67 | 82 | 75 |
| `install/InstallerController.php` | Steuerung des Installationsablaufs | `InstallerService`; PDO; Install-Views; Session | 80 | 68 | 94 | 81 |
| `install/InstallerService.php` | Service für Setup, Config und DB-Anlage | PDO; Dateisystem; Config-Erzeugung; Tabellenanlage | 80 | 67 | 90 | 79 |
| `install/views/admin.php` | Installer-View für Admin-Anlage | Installer-Controller; HTML-Formular | 75 | 68 | 82 | 75 |
| `install/views/blocked.php` | Installer-Blockierungsansicht | Installer-Guard; statische View | 75 | 68 | 82 | 75 |
| `install/views/database.php` | Installer-View für Datenbankdaten | Installer-Controller; Formularschritt DB | 75 | 68 | 82 | 75 |
| `install/views/site.php` | Installer-View für Site-Konfiguration | Installer-Controller; Formularschritt Site | 75 | 68 | 82 | 75 |
| `install/views/success.php` | Erfolgsansicht nach Installation | Installer-Session; Abschlussseite | 75 | 68 | 82 | 75 |
| `install/views/update.php` | Update-/Reinstall-Ansicht | Installer-Service; Formularschritt Update | 75 | 68 | 82 | 75 |
| `install/views/welcome.php` | Willkommens- und Vorabcheck-Ansicht | Installer-Controller; Systemchecks | 75 | 68 | 82 | 75 |
| `lang/de.yaml` | Deutsche Übersetzungsstrings | Lokalisierung; UI-Text-Mapping | 84 | 85 | 80 | 83 |
| `lang/en.yaml` | Englische Übersetzungsstrings | Lokalisierung; UI-Text-Mapping | 84 | 85 | 80 | 83 |
| `logs/.gitignore` | Hält Log-Verzeichnis im Repo schlank | Git; Laufzeit-Logs | 80 | 82 | 75 | 79 |
| `logs/.htaccess` | Sperrt Webzugriff auf Logs | Apache-Zugriffsschutz; Laufzeit-Logs | 80 | 82 | 75 | 79 |
| `member/dashboard.php` | Dashboard-Seite im Member-Bereich | `member/includes/bootstrap.php`; Member-Services | 81 | 67 | 86 | 78 |
| `member/favorites.php` | Favoriten-Seite im Member-Bereich | Member-Bootstrap; Favoriten-Daten | 81 | 67 | 86 | 78 |
| `member/includes/bootstrap.php` | Minimaler Loader für Member-Bereich | `class-member-controller.php` | 75 | 67 | 82 | 75 |
| `member/includes/class-member-controller.php` | Zentraler Controller für Member-Funktionen | Auth; Database; Security; Hooks; Member-/Message-/MFA-/Passkey-Services | 68 | 67 | 86 | 74 |
| `member/media.php` | Medienseite für Mitglieder | Member-Bootstrap; MediaService; Form-Handling | 88 | 67 | 90 | 82 |
| `member/messages.php` | Nachrichtenansicht für Mitglieder | Member-Bootstrap; MessageService; Form-Handling | 82 | 67 | 90 | 80 |
| `member/notifications.php` | Benachrichtigungseinstellungen | Member-Bootstrap; MemberService; Form-Handling | 88 | 67 | 90 | 82 |
| `member/partials/alerts.php` | Alert-Partial für Member-Seiten | `MemberController::consumeFlash()` | 81 | 68 | 86 | 78 |
| `member/partials/footer.php` | Footer-Partial für Member-Seiten | `CMS\Hooks`; Seitenassets; Scripts | 81 | 68 | 86 | 78 |
| `member/partials/header.php` | Header-Partial für Member-Seiten | `CMS\Hooks`; Controller; Design-Tokens | 81 | 68 | 86 | 78 |
| `member/partials/plugin-not-found.php` | Fallback-Partial für fehlende Plugins | Member-Plugin-Sektion; statische Ausgabe | 81 | 68 | 86 | 78 |
| `member/partials/sidebar.php` | Sidebar-Partial im Member-Bereich | `MemberController`; Menüdaten | 81 | 68 | 86 | 78 |
| `member/plugin-section.php` | Plugin-Sektion im Member-Bereich | Member-Bootstrap; `CMS\Hooks` | 81 | 67 | 86 | 78 |
| `member/privacy.php` | Datenschutzseite für Mitglieder | Member-Bootstrap; Export-/Delete-Formulare | 88 | 67 | 90 | 82 |
| `member/profile.php` | Profilseite für Mitglieder | Member-Bootstrap; Profilformular | 88 | 67 | 90 | 82 |
| `member/security.php` | Sicherheitsseite für Mitglieder | Member-Bootstrap; Passwort/TOTP/Passkeys | 88 | 67 | 90 | 82 |
| `member/subscription.php` | Abo- und Bestellübersicht | Member-Bootstrap; Bestell-/Plan-Daten | 81 | 67 | 86 | 78 |
| `orders.php` | Öffentliche Checkout-/Bestellseite | Auth; Database; Security; SubscriptionManager; ThemeManager | 74 | 67 | 90 | 77 |
| `plugins/cms-importer/admin/log.php` | Admin-View für Import-Protokolle | Importer-Admin; Formular-/Logausgabe | 88 | 68 | 76 | 77 |
| `plugins/cms-importer/admin/page.php` | Haupt-Admin-View des Importers | Importer-Admin; Upload-/Import-UI | 88 | 68 | 76 | 77 |
| `plugins/cms-importer/assets/css/importer.css` | Styles für Importer-Admin | Importer-Admin-UI; CSS-Tokens | 88 | 78 | 82 | 83 |
| `plugins/cms-importer/assets/js/importer.js` | Admin-JS für Import-Workflow | DOM; Upload; Preview; Import-Requests | 62 | 79 | 80 | 74 |
| `plugins/cms-importer/cms-importer.php` | Plugin-Bootstrap des Importers | `CMS\Hooks`; Admin-Klasse; Importer-/Parser-Klassen | 81 | 67 | 86 | 78 |
| `plugins/cms-importer/includes/class-admin.php` | Admin-Controller des Importers | `CMS\Hooks`; Upload/AJAX; Admin-Views | 74 | 67 | 90 | 77 |
| `plugins/cms-importer/includes/class-importer.php` | Kernlogik und DB-Zugriff des Importers | Import-DB; Datenmapping; Parser; Berichts-Traits | 73 | 63 | 86 | 74 |
| `plugins/cms-importer/includes/class-xml-parser.php` | Parser für WXR- und SEO-JSON-Dateien | XML/JSON-Parsing; Import-Service | 81 | 64 | 86 | 77 |
| `plugins/cms-importer/includes/trait-admin-cleanup.php` | Cleanup-Helfer für Importer-Admin | Importer-Admin; Datenbereinigung | 73 | 67 | 86 | 75 |
| `plugins/cms-importer/includes/trait-importer-preview.php` | Preview- und Planungslogik des Importers | Importer-Service; Vorschau-/Filterlogik | 73 | 68 | 86 | 76 |
| `plugins/cms-importer/includes/trait-importer-reporting.php` | Reporting- und Meta-Helfer | Importer-Service; Markdown-Berichte | 81 | 68 | 86 | 78 |
| `plugins/cms-importer/readme.txt` | Textdoku für das Importer-Plugin | Plugin-Dokumentation; Feature-Überblick | 88 | 86 | 78 | 84 |
| `plugins/cms-importer/reports/EXAMPLE_meta-report.md` | Beispielbericht unbekannter Meta-Felder | Importer-Reporting; Markdown-Demo | 90 | 88 | 82 | 87 |
| `plugins/cms-importer/update.json` | Update-Metadaten des Importer-Plugins | Plugin-Updater; Versionsinfos | 82 | 85 | 82 | 83 |
| `update.json` | Update-Metadaten des CMS-Kerns | Core-Updater; Versionsinfos | 82 | 85 | 82 | 83 |
| `uploads/.gitkeep` | Platzhalter für Upload-Verzeichnis | Laufzeit-Uploads; Repo-Struktur | 80 | 82 | 75 | 79 |
| `uploads/.htaccess` | Schutzregeln für Upload-Dateien | Apache-Headers; Upload-Sicherheit | 80 | 82 | 75 | 79 |

## Gesamtbewertung nach Kategorien

### Aggregierte Matrix

| Kategorie | Dateien | Ø Security | Ø Speed | Ø PHP/BP | Ø Gesamt | Schwächste Dateien | Stärkste Dateien | Audit-Fokus |
|---|---:|---:|---:|---:|---:|---|---|---|
| **Admin – Entry-Points** | 81 | 83,4 | 84,1 | 84,8 | 83,9 | `theme-editor.php`, `theme-marketplace.php` | `diagnose.php`, `index.php`, `info.php`, `support.php`, `system-*.php` | Remote-Zugriffe in Marketplace-/Theme-Entrypoints härten |
| **Admin – Module** | 55 | 81,4 | 74,4 | 84,4 | 80,4 | `DocumentationGitSync.php`, `DocumentationGithubZipSync.php`, `DocumentationSyncDownloader.php` | `PostsCategoryViewModelBuilder.php`, `SystemInfoModule.php` | Performance- und Qualitäts-Gates für große Module priorisieren |
| **Admin – Layout-Partials** | 4 | 87,5 | 91,0 | 84,3 | 87,5 | `sidebar.php` | `section-page-shell.php` | Bereits stark; nur Regressionen verhindern |
| **Admin – Views** | 89 | 84,4 | 83,9 | 83,7 | 83,4 | `posts/edit.php`, `pages/edit.php`, `landing/page.php` | `member/subnav.php`, `performance/subnav.php`, `seo/subnav.php` | Editor-Komplexität und Formularpfade weiter entkoppeln |
| **Admin – View-Partials** | 8 | 89,5 | 94,4 | 84,9 | 89,6 | `featured-image-picker.php` | `content-advanced-seo-panel.php`, `content-preview-card.php`, `content-readability-card.php`, `content-seo-score-panel.php` | Sehr guter Standard – als Referenzmuster konservieren |
| **Core – Kernel & Infrastruktur** | 27 | 80,3 | 81,6 | 82,5 | 81,3 | `Http/Client.php`, `Database.php`, `Api.php` | `Version.php`, `CacheManager.php`, `Json.php` | HTTP-Client, DB-Abstraktion und Bootstrap weiter entlasten |
| **Core – Contracts** | 3 | 94,0 | 95,0 | 92,0 | 94,0 | – | alle Contracts | Referenzniveau halten, keine unnötige Aufblähung |
| **Core – Auth Provider & MFA** | 5 | 69,6 | 79,0 | 83,2 | 77,4 | `LdapAuthProvider.php`, `WebAuthnAdapter.php` | `TotpAdapter.php`, `AuthManager.php` | LDAP-, Passkey- und MFA-Randfälle gezielt testen |
| **Core – Routing** | 6 | 76,3 | 79,8 | 82,7 | 79,7 | `ApiRouter.php` | `ThemeArchiveRepository.php` | API-/Routing-Validierung und Request-Härtung vertiefen |
| **Core – Allgemeine Services** | 42 | 80,7 | 77,8 | 83,1 | 79,4 | `RemoteImageService.php`, `RemoteFileService.php`, `DocumentationSyncFilesystem.php` | `PurifierService.php`, `PermalinkService.php`, `StatusService.php` | Externe APIs, Remote-URLs und Fehlerpfade robuster machen |
| **Core – EditorJs Services** | 9 | 81,0 | 80,1 | 83,6 | 81,6 | `EditorJsUploadService.php`, `EditorJsMediaService.php` | `EditorJsSanitizer.php`, `EditorJsRequestGuard.php` | Remote-Media und Upload-Härtung priorisieren |
| **Core – Landing Services** | 9 | 81,8 | 82,6 | 84,0 | 82,7 | `LandingPluginService.php` | `LandingSanitizer.php` | Sanitizer-Qualität halten, Plugin-Integration schärfer absichern |
| **Core – Media Services** | 3 | 73,7 | 74,0 | 82,0 | 76,7 | `UploadHandler.php` | `MediaRepository.php` | Upload-Pfade, Dateitypen und Filesystem-Grenzen prüfen |
| **Core – SEO Services** | 8 | 82,1 | 82,5 | 84,0 | 83,0 | `SeoAuditService.php`, `SeoMetaRepository.php` | `SeoHeadRenderer.php`, `SeoSchemaRenderer.php` | Head-/Schema-Ausgabe weiter als Best Practice festigen |
| **Core – SiteTable Services** | 5 | 82,6 | 84,2 | 84,0 | 83,6 | `SiteTableRepository.php` | `SiteTableDisplaySettings.php`, `SiteTableTemplateRegistry.php` | Renderer-Escaping und Template-Registrierung stabil halten |
| **Includes – Globale Helper** | 9 | 82,0 | 86,0 | 77,8 | 81,9 | `mail.php`, `redirects-auth.php` | `escaping.php` | `escaping.php` als verbindlichen Standard weiterziehen |
| **Includes – Subscription Helper** | 1 | 80,0 | 84,0 | 78,0 | 81,0 | – | `subscription-helpers.php` | Abo-Helfer stärker gegen Komplexitätswachstum absichern |
| **Rest – Root** | 7 | 75,6 | 71,7 | 81,6 | 76,4 | `cron.php` | `update.json` | Root-Entrypoints, speziell `cron.php`, enger absichern |
| **Rest – Assets CSS** | 8 | 88,0 | 77,3 | 75,0 | 80,0 | `admin.css`, `admin-hub-*.css` | mehrere Dateien bei 81 | CSS-Tokens, Breakpoints und Altlasten konsolidieren |
| **Rest – Assets JS** | 15 | 67,2 | 78,5 | 81,7 | 77,3 | `admin-hub-template-editor.js`, `admin-seo-editor.js` | `admin.js`, `admin-seo-redirects.js` | Höchste Frontend-Sicherheitspriorität: DOM-/XSS-Audit |
| **Rest – Config** | 4 | 81,5 | 79,8 | 81,3 | 80,8 | `app.php` | `media-meta.json`, `media-settings.json` | Config-Defaults, Secret-Handling und Upload-Policies überprüfen |
| **Rest – Installer Core** | 2 | 80,0 | 67,5 | 92,0 | 80,0 | – | `InstallerController.php` | Installer-Härtung und Reinstall-/Update-Schutz prüfen |
| **Rest – Installer Views** | 7 | 75,0 | 68,0 | 82,0 | 75,0 | alle annähernd gleich | alle annähernd gleich | Fehlerführung, CSRF und UX im Setup-Prozess nachschärfen |
| **Rest – Sprache** | 2 | 84,0 | 85,0 | 80,0 | 83,0 | – | beide Sprachdateien | Stabile Zone, nur Konsistenzpflege nötig |
| **Rest – Logs** | 2 | 80,0 | 82,0 | 75,0 | 79,0 | – | – | Infrastruktur-Schutz ausreichend, geringe Priorität |
| **Rest – Member Core** | 2 | 71,5 | 67,0 | 84,0 | 74,5 | `class-member-controller.php` | `bootstrap.php` | Member-Controller weiter zerlegen und request-sicher halten |
| **Rest – Member Pages** | 10 | 84,6 | 67,0 | 88,4 | 80,2 | `dashboard.php`, `favorites.php`, `plugin-section.php` | `media.php`, `notifications.php`, `privacy.php`, `profile.php`, `security.php` | Performance-Bottleneck im Member-Bereich untersuchen |
| **Rest – Member Partials** | 5 | 81,0 | 68,0 | 86,0 | 78,0 | alle annähernd gleich | alle annähernd gleich | Partials stabil, aber Speed hängt am umgebenden Rendering |
| **Rest – Importer Admin** | 2 | 88,0 | 68,0 | 76,0 | 77,0 | – | – | Import-UI, Fehlertexte und Upload-Grenzen verbessern |
| **Rest – Importer Core** | 7 | 76,6 | 66,3 | 86,6 | 76,4 | `class-importer.php`, `trait-admin-cleanup.php` | `trait-importer-reporting.php`, `cms-importer.php` | Memory-/Batch-Verhalten und Cleanup-Sicherheit priorisieren |
| **Rest – Importer JS** | 1 | 62,0 | 79,0 | 80,0 | 74,0 | `importer.js` | `importer.js` | DOM-Sicherheit und Request-/Response-Sanitizing prüfen |
| **Rest – Importer CSS** | 1 | 88,0 | 78,0 | 82,0 | 83,0 | `importer.css` | `importer.css` | Geringe Priorität |
| **Rest – Importer Sonstiges** | 3 | 86,7 | 86,3 | 80,7 | 84,7 | `readme.txt` | `EXAMPLE_meta-report.md` | Dokumentationsqualität hoch, nur Sync sauber halten |
| **Rest – Uploads** | 2 | 80,0 | 82,0 | 75,0 | 79,0 | – | – | Upload-Verzeichnis weiter strikt als Infrastruktur-Schutzzone behandeln |

### Auditfazit

Die Gesamtbewertung zeigt ein **solides bis starkes Architektur-Niveau** mit klarer Reife im Backend-Layout, bei den Core-Contracts sowie in mehreren sicherheitsnahen Service-Schichten. Besonders positiv fallen die wiederverwendbaren Admin-Partials, die Contracts, `escaping.php`, `PurifierService` sowie die SEO-/Head- und Sanitizer-Pfade auf. Hier ist bereits ein belastbarer Qualitätsstandard sichtbar.

Die **riskantesten Zonen** liegen nicht im Layout, sondern in den dynamischeren und I/O-lastigen Bereichen: `assets/js/**`, Auth-/Provider-Integrationen, Upload-/Media-Pfade, Importer-Core sowie einzelne Root-Entrypoints wie `cron.php`. Dort ist die Punktzahl nicht katastrophal, aber klar unter dem Reifegrad der besten Kernbereiche – genau dort lohnt sich die nächste Audit-Runde am meisten.

Für die nächste Iteration sollten vor allem vier Themen priorisiert werden:

- **JavaScript-Sicherheitsaudit** für DOM-Manipulationen, Preview-Rendering und Editor-nahe Admin-Skripte
- **Cron-/Entrypoint-Härtung** inklusive Authentisierung, Rate-Limits und sauberem Fehler-/Logging-Verhalten
- **Upload-/Importer-Härtung** mit Fokus auf Pfade, Dateitypen, Größenlimits, Cleanup und Speicherverbrauch
- **Member-/Controller-Performance** durch Entkopplung, Query-Profiling und gezieltes Lazy-/Cache-Verhalten

Unterm Strich ist die Codebasis **nicht roh oder ungeordnet**, sondern bereits deutlich strukturiert und enterprise-tauglich. Der größte Hebel liegt jetzt nicht mehr im groben Neuaufbau, sondern in der **gezielten Härtung der beweglichen Teile**: Remote-Integrationen, Uploads, JS, Importpfade und stark verdichtete Controller-/Modulebene.
