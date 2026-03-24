# 365CMS.DE  [![Generic badge](https://img.shields.io/badge/VERSION-2.6.18-blue.svg)](https://shields.io/)

# 365CMS Changelog

## 📋 Legende

| Symbol | Typ | Bedeutung |
|--------|-----|-----------|
| 🟢 | `feat` | Neues Feature |
| 🔴 | `fix` | Bugfix |
| 🟡 | `refactor` | Code-Umbau ohne Funktionsänderung |
| 🟠 | `perf` | Performance-Verbesserung |
| 🔵 | `docs` | Dokumentation |
| ⬜ | `chore` | Wartungsarbeit / CI/CD |
| 🎨 | `style` | Design- / UI-Änderungen |

---

## [2.6.18] – 2026-03-24

### Changed

- Die versionierte Beispielgrafik `SidebarRahmenThumnail_V5_CopilotLizenzen.png` liegt nicht mehr im Runtime-Pfad `CMS/uploads/`, sondern unter `DOC/assets/examples/`; damit bleibt der Upload-Baum wieder sauber für echte Laufzeitdaten reserviert.
- `DOC/assets/examples/README.md` dokumentiert die Trennlinie explizit: versionierte Demo-/Referenzdateien gehören in den Doku-/Beispielpfad und nicht in produktive Upload-Verzeichnisse. Kleine Repo-Hygiene, großer Frieden im Kopf. 😉

## [2.6.17] – 2026-03-24

### Changed

- `CMS/admin/views/posts/edit.php` und `CMS/admin/views/pages/edit.php` delegieren die wiederkehrenden Lesbarkeits-, Vorschau-, SEO-Score- und erweiterten SEO-Blöcke jetzt an gemeinsame Partials unter `CMS/admin/views/partials/`, wodurch zwei der größten Editor-Views deutlich diff-ärmer und wartbarer werden.
- Die neuen Partials `content-readability-card.php`, `content-preview-card.php`, `content-seo-score-panel.php` und `content-advanced-seo-panel.php` kapseln die gemeinsamen Admin-Bausteine konfigurierbar, ohne die bestehenden IDs, Form-Felder oder Frontend-Hooks der Editor-JS-/SEO-Logik zu verbiegen.

## [2.6.16] – 2026-03-24

### Changed

- `CMS/core/Services/MediaDeliveryService.php` verarbeitet jetzt Byte-Range-Requests sauber mit `206 Partial Content`, `416 Range Not Satisfiable`, `Accept-Ranges` und passendem `Content-Range`, damit größere Medien und Resume-/Preview-Clients nicht mehr auf einen Alles-oder-nichts-Download festgenagelt sind.
- Die Auslieferung streamt Mediendateien nun chunkweise über einen kontrollierten File-Handle statt sie vollständig per `readfile()` in einem Rutsch auszugeben; `HEAD`-Requests liefern die Header dazu ohne Response-Body.

## [2.6.15] – 2026-03-24

### Changed

- `CMS/core/Routing/ThemeRouter.php` delegiert Kategorie-/Tag-Archivdaten, Legacy-Tag-Normalisierung und veröffentlichte Archiv-Overviews jetzt an das neue `ThemeArchiveRepository`, wodurch der große Routing-Pfad deutlich schmaler und gezielter testbar bleibt.
- `CMS/admin/modules/posts/PostsModule.php` nutzt für Kategorienäume, Optionslabels und Admin-Row-Metadaten jetzt den neuen `PostsCategoryViewModelBuilder`, statt diese ViewModel-Logik weiter direkt im Modul zu halten.
- `CMS/admin/modules/hub/HubTemplateProfileManager.php` bezieht Template-Optionen, Presets und Default-Profile jetzt aus `HubTemplateProfileCatalog`; die umfangreichen Inline-Kataloge und Default-Helfer liegen damit separat und verkleinern den Hub-Admin-Hotspot spürbar.

## [2.6.14] – 2026-03-23

### Changed

- Der WordPress-Importer wurde entlang der Audit-Aufgabe A-14 weiter zerlegt: `CMS/plugins/cms-importer/includes/class-importer.php` delegiert Preview-/Planungslogik und Meta-/Reporting jetzt an die neuen Traits `trait-importer-preview.php` und `trait-importer-reporting.php`, statt diese Blöcke weiter im Service-Monolithen zu halten.
- `CMS/plugins/cms-importer/includes/class-admin.php` nutzt Cleanup-/Backfill-/Reporting-Helfer nun über `trait-admin-cleanup.php`; damit bleiben Admin-Entry-Point und bestehende UI-Flows stabil, während die bislang sehr großen Bereinigungs- und Verlaufsroutinen separat wart- und testbarer werden.

## [2.6.13] – 2026-03-23

### Changed

- `CMS/includes/functions.php` ist jetzt nur noch der kanonische Bootstrap für globale Helfer und lädt die bisherige Sammellogik thematisch getrennt aus `CMS/includes/functions/*.php` nach, statt Escaping, Runtime, Redirects, Rollen, Admin-Menüs, Übersetzungen und WP-Kompatibilität weiter in einer Monolith-Datei zu bündeln.
- Die ausgelagerten Helper-Gruppen halten die bestehende globale API bewusst stabil, verkleinern aber den Wartungshotspot deutlich: Escaping/String-Helfer, Optionen/Archiv-/Runtime-Helfer, Redirect/Auth, Rollen, Admin-Menüs, Übersetzungen, WP-Kompatibilität und Mail sind jetzt getrennt wart- und prüfbar.


## [2.6.12] – 2026-03-23

### Changed

- `CMS/install.php` ist jetzt nur noch ein schlanker Bootstrap und delegiert den mehrstufigen Installer-Ablauf an einen dedizierten `InstallerController` statt UI, Datenbank, Konfigurationsschreibzugriffe und Success-Flow weiter in einer Datei zu mischen.
- `InstallerService` kapselt die Setup-, Lock-, Config-, Schema- und Datenbanklogik des Installers zentral, während die HTML-Schritte unter `CMS/install/views/` als getrennte Views gerendert werden und damit gezielter wart- und testbar bleiben.


## [2.6.11] – 2026-03-23


### Changed

- Die HTTPS-Strategie ist jetzt verbindlich auf Redirects durch Reverse-Proxy/Webserver und nicht mehr auf einen halb-kommentierten `.htaccess`-Sonderpfad ausgerichtet; der ausgelieferte Apache-Fallback normalisiert nur noch Proxy-HTTPS für dieselbe Sicherheitslinie.
- `Security` und die Systemdiagnose weisen die aktive Redirect-Verantwortung jetzt explizit aus und erzeugen HSTS nur noch über eine zentrale HTTPS-/HSTS-Konfiguration mit demselben HTTPS-Erkennungsmodell wie der Apache-Fallback.

### Docs

- Audit und ToDo führen zusätzlich einen neuen offenen Security-Backlogpunkt für ein signiertes, kurzlebiges Login-/Device-Cookie mit, damit Browser-/Gerätebindung nicht als lose Notiz im Doku-Rand hängen bleibt.

## [2.6.10] – 2026-03-23

### Fixed

- Core-Updates werden nicht mehr direkt in das Live-Ziel entpackt, sondern zuerst in ein benachbartes Staging-Verzeichnis extrahiert und erst danach per atomarem Verzeichnis-Swap oder rollback-fähigem Inhalts-Swap übernommen.
- Abgebrochene oder fehlschlagende Installationen hinterlassen damit keine halbfertigen Update-Zustände mehr im Zielverzeichnis; bestehende Inhalte werden vor dem Umschalten in ein temporäres Backup verschoben und bei Fehlern wiederhergestellt.

## [2.6.9] – 2026-03-23

### Fixed

- `Security::startSession()`, `index.php` und `cron.php` setzen `session.cookie_secure` jetzt nur noch bei tatsächlich erkanntem HTTPS bzw. Proxy-HTTPS statt pauschal immer auf `1`.
- HTTP-Staging-Setups und CLI-nahe Cron-Läufe verlieren damit nicht mehr unnötig ihre Session-Cookies durch eine erzwungene Secure-Flag auf Nicht-HTTPS-Anfragen.

## [2.6.8] – 2026-03-23

### Fixed

- `CMS\Http\Client` blockiert ungelöste Remote-Hosts im SSRF-Schutz jetzt standardmäßig, versucht vorab eine echte IPv4/IPv6-Auflösung und lässt ungelöste Hosts nur noch per explizitem `allowUnresolvedHosts`-Opt-in zu.
- `UpdateService` nutzt dieselbe härtere DNS/IP-Auflösung und erlaubt bei fehlender Host-Auflösung keine sensiblen Remote-Ziele mehr stillschweigend durch.

## [2.6.6] – 2026-03-23

### Fixed

- Der GitHub-Doku-Sync validiert ZIP-Einträge jetzt vor `extractTo()` auf Traversals, absolute Pfade, NUL-/Steuerzeichen sowie leere oder punktbasierte Segmente.

## [2.6.5] – 2026-03-23

### Fixed

- Debug-Logs landen standardmäßig nicht mehr im `CMS/logs/`-Release-Baum, sondern über `LOG_PATH`/`CMS_ERROR_LOG` in einem externen Logverzeichnis; Konfig-Writer und `SystemService` nutzen denselben aktiven Pfad.

## [2.6.4] – 2026-03-23

### Docs

- Die Audit-Dokumentation nutzt `FILEINVENTAR.md` jetzt konsequent als kanonische Scope-Quelle; konkurrierende eingebettete Inventarstände und alte 444-Dateien-Referenzen wurden aus Audit und ToDo entfernt.

## [2.6.3] – 2026-03-23

### Fixed

- Der WordPress-Importer lädt Remote-Bilder jetzt über den zentralen Core-HTTP-Client mit aktivierter TLS-Prüfung, SSRF-Schutz sowie Größen- und Image-Content-Type-Limits statt über einen ungehärteten Direkt-Fetch.

## [2.6.2] – 2026-03-23

### Added

- Die SEO-Linie erweitert die IndexNow-Integration um eine dynamische Keydatei-Auslieferung und zeigt im SEO-Admin zusätzlich Status- und URL-Informationen zur aktiven Keydatei an.
- Kategorien und Tags unterstützen jetzt mehrsprachige Archivbasen sowie Ersatzkategorien/-tags beim Löschen, sodass Content-Umbauten ohne harte Abrisskante in Archiven und Listenpfaden möglich werden.
- Der Core bringt einen deutlich ausgebauten Importer unter `CMS/plugins/cms-importer` mit Meta-Report, Admin-Oberfläche, Styles, JavaScript und Importlogik für größere Importpfade mit.

### Changed

- `CMS\Version` ist jetzt wieder die zentrale Release-Quelle für Runtime und Installer; `config/app.php`, Installer-Konfigwriter, Update-Metadaten und sichtbare Versions-Badges wurden auf den konsistenten Stand `2.6.2` nachgezogen.
- Routing, Archive, Slug-Generierung und Inhaltslokalisierung wurden in mehreren Wellen weiter konsolidiert; insbesondere Kategorie-/Tag-Archive, Slug-Validierung in Seiten/Beiträgen sowie die allgemeine Inhaltsauflösung im Frontend wurden nachgeschärft.
- Marketplace- und Update-Pfade wurden erweitert: Theme-/Plugin-Verwaltung, Update-Ansichten und die zugrunde liegende `UpdateService`-Logik unterstützen den jüngsten Ausbauzustand deutlich umfangreicher als im Stand `2.6.1`.
- Der Mitgliederbereich blendet im Header jetzt gezielt einen Admin-Einstieg ein, wenn der aktuelle Nutzer entsprechende Rechte besitzt.

### Fixed

- `install.php` sperrt bestehende Installationen jetzt per Install-Lock und Admin-Guard für öffentliche Zugriffe; zusätzlich wird das Datenbank-Passwort im Reinstall-Pfad nicht mehr aus der vorhandenen Konfiguration vorbefüllt.
- Das Löschverhalten für Kategorien und Tags bricht bei inhaltlich verknüpften Beiträgen nicht mehr stumpf weg, sondern kann Ziele auf Ersatzkategorien/-tags umlenken.
- Archiv- und Routingpfade für Kategorien/Tags verhalten sich in der mehrsprachigen CMS-Linie robuster und besser abgestimmt auf lokalisierte Inhaltsstrukturen.

### Docs

- Der neue Audit `DOC/audit/AUDIT_23032026_CMS_PHINIT-LIVE.md` dokumentiert den CMS- und Live-Site-Prüfstand vom 23.03.2026 inklusive öffentlicher PhinIT-Stichprobe.
- `DOC/audit/NACHARBEIT_AUDIT_ToDo.md` führt jetzt zusätzlich den offenen Release-/Versionsabgleich sowie die reale Proxy-/CDN-/Tracking-Verifikation als aktive Nacharbeiten.
- `DOC/audit/ToDo_Audit_23032026.md` wurde auf eine vollständige First-Party-Dateiabdeckung ohne die Root-Bundle-Ordner `CMS/assets/` und `CMS/vendor/` nachgezogen und nennt die aktuell wichtigsten Punkte zu Installer, Versionsdrift, Importer-Fetch und Log-Hygiene jetzt explizit.
- `README.md` beschreibt den Auditstatus vom 23.03.2026 jetzt direkt im Betriebsabschnitt, damit offene Betriebs- und Sicherheitsbaustellen nicht nur im Audit-Ordner versteckt bleiben.

## [2.6.1] – 2026-03-17

### Changed

- Der SEO-Admin trennt Weiterleitungen und erkannte `404` jetzt in zwei eigenständige Bereiche; neue Redirects lassen sich wieder direkt anlegen und Übernahmen aus dem `404`-Monitor können die passende Site-/Host-Zuordnung mitspeichern.
- `RedirectService` bewertet Redirect-Regeln jetzt host- bzw. pfadbezogen über `site_scope`, protokolliert den anfragenden Host in `404`-Logs mit und verhindert Dubletten nur noch innerhalb desselben Site-Scope statt global über alle Sites hinweg.
- Beiträge und Seiten teilen sich jetzt dieselbe Kategorienbasis im Redaktionsbereich; zusätzlich werden Microsoft-365-Standardkategorien wie Copilot, Teams, SharePoint Online, Exchange Online, Intune, Defender oder Power Platform automatisch zur Auswahl vorgehalten.
- Das Theme `cms-phinit` modernisiert den Header mit dezenter Netzwerk-Animation, verfeinerten Hauptmenübuttons und ausgeblendeter Header-Logo-Fläche auf Mobile; zusätzlich wurde der Dark Mode für Core-/Hub-/Rich-Content-Tabellen sichtbar nachgezogen.
- `cms-phinit` startet den frühen Dark-Mode-Init im Head jetzt über ein eigenes cachebares Theme-Asset statt über ein Inline-Skript direkt im `header.php`, wodurch der Header weiter flickerarm bleibt und zugleich template-seitig sauberer wird.
- `cms-phinit` verlagert jetzt auch die umfangreiche Customizer-Logik für Unsaved-Warnung, Shortcut-Speichern, Preview-Drawer, Farb-Presets und Font-Previews in ein eigenes Admin-JS-Asset; im PHP-Fragment verbleibt nur noch passive JSON-Konfiguration.
- `cms-phinit` lädt Google Analytics jetzt über ein eigenes consent-fähiges Theme-Asset statt über einen Inline-Bootstrap im Footer; zusätzlich feuert das Theme-Banner bei Änderungen ein zentrales `cms-cookie-consent-change`-Event für abhängige Frontend-Module.
- `cms-phinit` liefert den Theme-Customizer jetzt auch ohne Inline-CSS-Block und ohne Inline-Event-Handler für Farb-Sync/Reset-Confirm aus; Styles und Bindings liegen zentral in `customizer-admin.css` und `customizer-admin.js`.
- Das Theme `365Network` liefert seinen Admin-Customizer jetzt ebenfalls über ausgelagerte Assets statt über eingebettete CSS-/JS-Blöcke; Farb-Sync, Logo-Vorschau und Reset-Modal hängen an zentralen Klassen/Data-Attributen, und der Einstieg erzwingt nun zusätzlich einen expliziten Admin-Guard.
- `365Network` zieht auch die Directory-Templates weiter glatt: Filter-Selects submitten zentral über `js/theme.js`, der 404-Zurück-Button nutzt keinen Inline-Handler mehr und wiederkehrende Reset-/Listen-Stile liegen jetzt in gemeinsamen CSS-Klassen statt direkt im Markup.

### Fixed

- Public-Routes mit `GET`-Handler reagieren jetzt auch auf `HEAD`-Requests korrekt, sodass Monitoring-, Header-Checks und SEO-Tools für Routen wie `/feed`, `/forgot-password` oder `/.well-known/security.txt` nicht mehr fälschlich in `404` laufen.
- Sensible Recovery-Seiten wie `/forgot-password` verwenden jetzt dieselbe private/no-store-Cache-Strategie wie Login- und Registrierungsseiten.
- RSS-Descriptions extrahieren für Editor.js-Inhalte jetzt robusten Plaintext statt rohe oder abgeschnittene JSON-Blockpayloads in Feed-Reader weiterzureichen; zusätzlich wurde der Regex-Fallback für abgeschnittene Editor.js-Payloads korrigiert, damit auch unvollständige JSON-Fragmente wieder lesbaren Text liefern.
- `CMS/cron.php` stößt den bislang nur registrierten, aber nie ausgelösten Hook `cms_cron_hourly` jetzt kompatibel mit bestehenden Mail-Queue-Cron-Aufrufen an und drosselt ihn intern auf höchstens einen echten Lauf pro Stunde, sodass `cms-feed`-Fetch-Queue und Feed-Digests wieder automatisch nachziehen.
- `cms-contact` nutzt in allen verbleibenden Admin-Views nun die zentralen Admin-Assets statt zusätzlicher Inline-Styles/-Scripts; Filter, Template-Auswahl, Modale, Statuswechsel und Sammelaktionen bleiben dabei funktional, aber die Views sind deutlich sauberer und wartbarer.
- `cms-feed` lädt sein Public-JavaScript jetzt auf allen echten Feed-Routen inklusive Consent-Sperrseite, reagiert damit konsistent auf Cookie-Freigaben/-Entzüge und kommt in den öffentlichen Templates ohne die verbliebenen Inline-Styles/-Scripts für Reset-Links und Consent-Reload aus.
- `cms-feed` liefert jetzt auch seinen großen Admin-View `page-admin.php` ohne direkte `onclick`-/`confirm`-Handler oder `javascript:void(0)`-Links aus; Bulk-Aktionen, Katalog-Importe, Tabs und Modal-Steuerung hängen stattdessen zentral an `assets/js/admin.js`.
- `cms-events` liefert jetzt Admin-, Meta-Box-, Member- und Kalenderpfade ohne ausführbare Inline-Skripte, `onclick`-/`onchange`-Handler oder native Confirm-Dialoge aus; Bestätigungen, Modalsteuerung, Preview-Syncs, Formular-Toggles und die Monatsnavigation hängen stattdessen an zentralen Assets bzw. echten Navigationslinks.
- `cms-phinit` bindet den Theme-Customizer jetzt ohne ausführbaren Inline-Skriptblock an und ersetzt die bisher per JavaScript injizierten Font-Preview-Styles durch eine zentrale CSS-Klasse.
- Der frühere GA-Inline-Loader in `cms-phinit` wurde entfernt; Tracking wird nur noch über `assets/js/analytics-loader.js` und bei akzeptiertem Consent initialisiert.
- Der verbleibende Customizer-Styleblock sowie `onclick`-/`oninput`-Handler im `cms-phinit`-Customizer wurden in zentrale Admin-Assets überführt.
- Die Bulk-Bearbeitung von Beiträgen und Seiten kann jetzt Kategorien setzen oder entfernen; Seiten unterstützen außerdem erstmals eine eigene Einzelbearbeitung per Kategorieauswahl und Listenfilter.

### Added

- `ThemeRouter` liefert jetzt `security.txt` sowohl unter `/security.txt` als auch unter `/.well-known/security.txt` mit Kontakt, Canonical, Sprachenhinweis und Ablaufdatum aus.

### Docs

- Audit-, Sicherheits- und Theme-Dokumentation spiegeln jetzt die PhinIT-Live-/Testsite-Nacharbeit vom 17.03.2026 inklusive `security.txt`, Forgot-Password-Recovery und Feed-Härtung wider.
- Audit-/Release-Notizen dokumentieren jetzt zusätzlich den getrennten Redirect-/404-Admin, site-spezifische Redirect-Scopes, den nachgezogenen Tabellen-Darkmode in `cms-phinit` sowie die bereinigten `cms-contact`-Admin-Views.

## [2.6.0] – 2026-03-16

### Added

- `PermalinkService` zentralisiert Beitrags-URL-Strukturen, Slug-Extraktion und Migrationspfade für beitragsbezogene Router- und Theme-Pfade.
- `ErrorReportService` und `/admin/error-report` führen persistente Admin-Fehlerreports mit Audit-Log, Kontextdaten und CSRF-geschütztem Redirect-Flow ein.
- Neue Admin-Einstiege für Beitrags-Kategorien, Beitrags-Tags und Tabellen-Display-Defaults erweitern den Redaktionsbereich um eigenständige CRUD-Ansichten und Preset-Verwaltung.

### Changed

- Theme-Dateien werden jetzt in isoliertem Scope gerendert, damit Werte aus einem Render-Kontext nicht mehr unbeabsichtigt in andere Templates durchsickern.
- Routing-, Redirect- und Hub-/Schema-Pfade wurden für Archiv- und Sitemap-Routen, URL-Nachmigrationen und robustere Flag-Verwaltung in `SchemaManager` und `MigrationManager` erweitert.

### Fixed

- Kommentar- und Admin-JSON-Pfade verhalten sich konsistenter: eingeloggte Nutzer füllen Kommentarformulare zuverlässiger vor, Moderation meldet Erfolg verlässlich zurück und Admin-/AJAX-Endpunkte für Posts, Seiten, Nutzer und Medien reagieren stabiler.

### v2.5.30 — 11. März 2026 · Standard-Theme-Home-Split, Partials & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.30** | 🟡 refactor | Theme/Standard-Theme | **Startseiten-Orchestrator drastisch verkleinert**: `CMS/themes/cms-default/home.php` lädt nur noch Daten und delegiert anschließend an die spezialisierten Partials `partials/home-landing.php` und `partials/home-blog.php`. |
| **2.5.30** | 🟡 refactor | Theme/Frontend | **Landing- und Blog-Markup sauber getrennt**: Die frühere Mischdatei wurde in `partials/home-landing.php` (Landing-Logik, CTA, Footer-Callout) und `partials/home-blog.php` (Hero, Listen, Sidebar) aufgeteilt, wodurch Theme-Anpassungen deutlich lokalere Änderungen erlauben. |
| **2.5.30** | 🟢 feat | Core/Quality Gates | **Architektur-Suite bestätigt den Theme-Split**: `php tests/architecture/run.php` läuft nach dem Split erfolgreich durch; `home.php` liegt jetzt bei 131 LOC statt als weiterer großer Theme-Monolith im Laufzeitpfad zu bleiben. |
| **2.5.30** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die Standard-Homepage gilt nicht mehr als dominanter Restblock; `AUDIT_FACHBEREICHE.md`, `AUDIT_BEWERTUNG.md` und `AUDIT_09032026.md` verschieben den Restdruck nun stärker auf große CSS-/Admin-Dateien und Proxy-/CDN-Realvalidierung. |

---


---

## 📜 Vollständige Versionshistorie

---

### v2.5.29 — 11. März 2026 · Release-Smoke-Disziplin, Beta-Pflichtpfade & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.29** | 🟢 feat | Core/Quality Gates | **Verbindliche Release-Smoke-Suite ergänzt**: `tests/release-smoke/manifest.php` und `tests/release-smoke/run.php` halten jetzt Public-, Auth-, Member-, Admin- und Fehlpfade inklusive historischer Retests als reproduzierbaren Repo-Standard fest. |
| **2.5.29** | ⬜ chore | CI/Release | **CI prüft die Release-Disziplin automatisch mit**: `.github/workflows/security-regression.yml` führt die neue Release-Smoke-Suite jetzt zusammen mit Security-, Architektur-, Vendor- und Doku-Sync-Checks aus. |
| **2.5.29** | 🔵 docs | Workflow/Release | **Deployment-Leitfaden enthält jetzt feste Beta-Stichprobe**: `DOC/workflow/UPDATE-DEPLOYMENT-WORKFLOW.md` definiert eine verbindliche Phase „Beta-Smoke nach Deployment“ mit Pflichtbefehl `php tests/release-smoke/run.php`, Pflichtpfaden und zusätzlichen Browser-/Log-Prüfungen. |
| **2.5.29** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die Beta-Smoke-Welle gilt als erledigt, `AUDIT_BEWERTUNG.md` hebt die Betriebs-/Release-Reife an und der nächste offene Schwerpunkt verschiebt sich auf große Theme-/Admin-Dateien sowie Proxy-/CDN-Realvalidierung. |

---

### v2.5.28 — 11. März 2026 · Marketplace-Integrität, SHA-256-Gates & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.28** | 🔴 fix | Marketplace/Supply Chain | **Auto-Installationen erzwingen jetzt Integritätsmetadaten**: `PluginMarketplaceModule` und `ThemeMarketplaceModule` erlauben automatische Installationen nur noch bei vorhandener Paket-URL, erlaubtem Zielhost und gültiger SHA-256-Prüfsumme statt allein aufgrund eines Download-Links. |
| **2.5.28** | 🔴 fix | Marketplace/Updates | **ZIP-Downloads werden vor dem Entpacken aktiv verifiziert**: Marketplace-Pakete werden über `UpdateService::verifyDownloadIntegrity()` gegen ihre erwartete Prüfsumme geprüft; bei fehlender oder falscher SHA-256 wird die Installation sauber abgebrochen. |
| **2.5.28** | 🎨 style | Admin/Marketplace | **Marketplace-UI trennt verifizierte von manuellen Paketen sichtbar**: Plugin- und Theme-Ansichten zeigen jetzt explizite Prüfsummen-/Warnhinweise und markieren Einträge ohne Integritätsmetadaten als „Nur manuell“, statt ihnen still denselben Installationspfad zu geben. |
| **2.5.28** | 🟢 feat | Core/Quality Gates | **Security-Suite sichert SHA-256-Gates regressionsseitig ab**: `tests/security/run.php` prüft jetzt zusätzlich, dass Plugin- und Theme-Marketplace Auto-Installationen ohne gültige 64-stellige SHA-256 nicht freigeben. |
| **2.5.28** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die Marketplace-/Supply-Chain-Welle gilt als erledigt, `AUDIT_BEWERTUNG.md` hält die Integritätsprüfung mit **99/100 Punkten** fest und der nächste offene Schwerpunkt rückt auf feste Beta-Smoke-Disziplin sowie verbleibende Proxy-/CDN-Realtests. |

---

### v2.5.27 — 11. März 2026 · Bootstrap-Profil-Messung, Cold-Path-Transparenz & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.27** | 🟠 perf | Core/Bootstrap | **Cold-Path-Profil startet jetzt vor dem Dependency-Load**: `Debug::resetRuntimeProfile()` läuft bereits vor `loadDependencies()`, sodass Dependency-Load, Plattformprüfung und Migrationslauf erstmals sauber in der Bootstrap-Zeit landen statt unsichtbar vor dem Messstart zu verschwinden. |
| **2.5.27** | 🟢 feat | Admin/System | **Diagnose zeigt aktives Bootstrap-Profil pro Modus**: `/admin/diagnose` wertet das neue Profil jetzt als eigene Ansicht mit Modus, Kaltstart bis `bootstrap.ready`, Post-Bootstrap-Zeit, Cold-Path-Anteil und teuersten Bootstrap-Phasen für CLI/API/Admin/Web aus – auch ohne aktiviertes `CMS_DEBUG`. |
| **2.5.27** | 🟢 feat | Core/Quality Gates | **Runtime- und Architektur-Suiten sichern Profilierung ab**: `tests/runtime-telemetry/run.php` prüft jetzt das leichtgewichtige Bootstrap-Profil explizit auch ohne Debug-Modus, und `tests/architecture/run.php` hält frühe Messung sowie Diagnose-Sichtbarkeit regressionsseitig fest. |
| **2.5.27** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die offene Messwelle für Bootstrap-Profile gilt als erledigt, `AUDIT_BEWERTUNG.md` steigt auf **99/100 Punkte** und der nächste offene Schwerpunkt rückt auf Marketplace-/Supply-Chain-Härtung sowie Proxy-/CDN-Realtests. |

---

### v2.5.26 — 11. März 2026 · Registry-Diagnose, Bundle-Transparenz & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.26** | 🟢 feat | Core/Diagnose | **`VendorRegistry` als Diagnosequelle erweitert**: `getDiagnostics()` exportiert jetzt Assets-Autoloader-Kandidaten, registrierte Produktivpakete, gebündelte Runtime-Libraries und die Symfony-Manifest-/PHP-Plattformprüfung zentral statt diese Informationen nur implizit in Bootstrap- und Servicepfaden zu verstecken. |
| **2.5.26** | 🟢 feat | Admin/System | **Diagnose macht Registry-/Bundle-Status sichtbar**: `SystemInfoModule` speist die Registry-Daten in `/admin/diagnose` ein; `admin/views/system/diagnose.php` zeigt Autoloader, Produktivpakete, Asset-Bundles und Plattformwarnungen direkt im Admin an. |
| **2.5.26** | 🟢 feat | Core/Quality Gates | **Architekturregel für Diagnose-Sichtbarkeit ergänzt**: `tests/architecture/run.php` prüft jetzt regressionsseitig, dass `VendorRegistry`, `SystemInfoModule` und die Diagnose-View die Vendor-/Asset-Registry weiterhin sichtbar anbinden. |
| **2.5.26** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die Registry-/Dependency-/Asset-Diagnosewelle gilt als erledigt, `AUDIT_BEWERTUNG.md` steigt auf **98/100 Punkte** und der nächste offene Schwerpunkt verschiebt sich auf Marketplace-/Supply-Chain-Härtung sowie Proxy-/CDN-Realtests. |

---

### v2.5.25 — 11. März 2026 · Layout-Shell-Reuse, Subnav-Zentralisierung & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.25** | 🟡 refactor | Admin/Layout | **Gemeinsame Admin-Section-Shell eingeführt**: `admin/partials/section-page-shell.php` bündelt den gemeinsamen Auth-/CSRF-/Alert-/Render-Ablauf für `performance-page.php`, `member-dashboard-page.php` und `system-monitor-page.php`, statt diese Wrapper separat mit nahezu identischem Seiten-Skelett zu pflegen. |
| **2.5.25** | 🟡 refactor | Admin/Views | **Button-Subnav zentralisiert**: `admin/views/partials/section-subnav.php` rendert jetzt die wiederkehrende Navigation für Performance-, Member- und System-Unterseiten; die drei bisherigen Subnav-Partials liefern nur noch ihre Konfiguration statt eigenes Markup. |
| **2.5.25** | 🟢 feat | Core/Quality Gates | **Architekturregel für Layout-Reuse ergänzt**: `tests/architecture/run.php` prüft jetzt regressionsseitig, dass die zentralen Section-Seiten und Subnavs die gemeinsamen Layout-Bausteine weiterverwenden. |
| **2.5.25** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die Layout-/Shell-Wiederverwendungswelle gilt als erledigt, `AUDIT_BEWERTUNG.md` steigt auf **97/100 Punkte** und der nächste offene Schwerpunkt verschiebt sich auf Registry-/Diagnose-Ausbau, Marketplace-Härtung und echte Proxy-/CDN-Realtests. |

---

### v2.5.24 — 10. März 2026 · Content-/Hub-View-Glättung, Asset-Split & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.24** | 🟡 refactor | Admin/Views | **Seiten-, Beitrags- und Hub-Editoren entlastet**: `admin/views/pages/edit.php`, `admin/views/posts/edit.php` und `admin/views/hub/edit.php` liefern ihre Bedienlogik nicht mehr als große Inline-Skriptblöcke, sondern nur noch als Konfiguration + Markup für zentrale Admin-Assets. |
| **2.5.24** | 🟢 feat | Core/Quality Gates | **Zentrale Admin-Assets + Architekturregel ergänzt**: `admin-content-editor.js` und `admin-hub-site-edit.js` bündeln die Editor-/Hub-Interaktionen, während `tests/architecture/run.php` regressionsseitig erzwingt, dass diese Views inline-scriptfrei bleiben und die Entry-Points die Assets weiter anbinden. |
| **2.5.24** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die Hub-/Content-View-Welle gilt als erledigt, `AUDIT_BEWERTUNG.md` steigt auf **96/100 Punkte** und der nächste offene Schwerpunkt verschiebt sich auf Layout-/Shell-Wiederverwendung, Registry-/Diagnose-Ausbau und Proxy-/CDN-Realtests. |

---

### v2.5.23 — 10. März 2026 · Legacy-Cache-Pfade, Header-Härtung & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.23** | 🔴 fix | Performance/Headers | **Standalone-Entry-Points an zentrale Cache-Policy angeglichen**: `config.php`, `install.php`, `cron.php` und der Installer-Redirect in `orders.php` senden jetzt ebenfalls private/no-store-Header über `CacheManager::sendResponseHeaders('private')`. |
| **2.5.23** | 🟢 feat | Core/Quality Gates | **Architektur-Suite überwacht Legacy-Header mit**: `tests/architecture/run.php` prüft jetzt zusätzlich, dass die verbliebenen Standalone-Entry-Points ihre privaten Cache-Header nicht wieder verlieren. |
| **2.5.23** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die Cache-/Legacy-Randpfad-Welle gilt als erledigt, `AUDIT_BEWERTUNG.md` steigt auf **95/100 Punkte** und der nächste offene Schwerpunkt verschiebt sich auf Hub-/Content-Views, Registry-/Diagnose-Ausbau und Proxy-/CDN-Realtests. |

---

### v2.5.22 — 10. März 2026 · Host-Allowlisten, Remote-Härtung & Audit-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.22** | 🔴 fix | Security/Remote | **Sensible Remote-Ziele enger begrenzt**: `UpdateService`, `PluginMarketplaceModule`, `ThemeMarketplaceModule` und `DocumentationSyncDownloader` akzeptieren für Update-, Marketplace- und Doku-Downloads jetzt nur noch explizite Zielhosts wie `365network.de`, GitHub-, GitHubusercontent- und Codeload-Ziele. |
| **2.5.22** | 🟢 feat | Core/Quality Gates | **Security-Suite um Host-Allowlists erweitert**: `tests/security/run.php` prüft jetzt zusätzlich, dass fremde Update-, Marketplace- und Doku-Hosts blockiert werden, während legitime Zielräume funktionsfähig bleiben. |
| **2.5.22** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Die Host-Allowlist-Welle gilt als erledigt, `AUDIT_BEWERTUNG.md` steigt auf **94/100 Punkte** und der nächste offene Schwerpunkt verschiebt sich auf restliche Cache-/Legacy-Randpfade sowie Supply-Chain-Feinschliff. |

---

### v2.5.21 — 10. März 2026 · Vendor-Netzwerkmonitoring, Audit-Sync & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.21** | 🟢 feat | Core/Quality Gates | **Vendor-/Drittpfad-Netzwerkmonitor ergänzt**: `tests/vendor-network-monitoring/run.php` prüft bekannte Remote-Primitiven in `CMS/assets/` und `CMS/vendor/` jetzt gegen eine explizite Allowlist und macht neue Drittpfade sofort sichtbar. |
| **2.5.21** | ⬜ chore | CI/Monitoring | **Security-Workflow erweitert**: `.github/workflows/security-regression.yml` führt den Vendor-Monitor jetzt automatisch mit aus; `DOC/assets/VENDOR-NETWORK-PATHS.md` dokumentiert die beobachteten Drittpfade getrennt vom Eigencode. |
| **2.5.21** | 🔵 docs | Audit/Release | **Audit- und Bewertungsstand nachgezogen**: Vendor-/Drittpfad-Monitoring gilt als erledigter Restblock, `AUDIT_BEWERTUNG.md` steigt auf **93/100 Punkte** und der nächste offene Schwerpunkt liegt klar auf engen Host-Allowlisten für sensible Remote-Ziele. |

---

### v2.5.20 — 10. März 2026 · Security-Regressionssuite, ZIP-Pakethärtung & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.20** | 🔴 fix | Marketplace/Updates | **ZIP-Pakete gegen Pfad-Traversal gehärtet**: `PluginMarketplaceModule`, `ThemeMarketplaceModule` und `UpdateService` validieren Archiv-Einträge jetzt vor dem Entpacken und blockieren unsichere `../`- bzw. absolute Pfade, bevor ein Paket ins Dateisystem greifen darf. |
| **2.5.20** | 🟢 feat | Core/Quality Gates | **Security-Suite deutlich verbreitert**: `tests/security/run.php` prüft jetzt zusätzlich GitHub-API-Host-Disziplin, localhost-Blockaden für Remote-Ziele sowie ZIP-Traversal in Marketplace-/Update-Paketen. |
| **2.5.20** | 🔵 docs | Audit/Release | **Audit-Bewertung und Nacharbeitsstand nachgezogen**: Die Security-Regressionssuite gilt als abgearbeiteter Restblock, `AUDIT_BEWERTUNG.md` steigt auf **92/100 Punkte** und die verbleibenden offenen Themen fokussieren sich jetzt stärker auf Vendor-/Allowlist-/Legacy-Ränder. |

---

### v2.5.19 — 10. März 2026 · Sonderpfad-Härtung, Audit-Konsolidierung & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.19** | 🔴 fix | Core/Admin I/O | **Verbleibende Sonderpfade explizit gehärtet**: `FeedService`, `ElfinderService`, `PurifierService`, `ImageProcessor`, `SeoSitemapService`, `FontManagerModule` und `PerformanceModule` behandeln Datei-, Temp-, GD- und Verzeichnisfehler jetzt explizit statt sie still per `@...` wegzudrücken. |
| **2.5.19** | 🟢 feat | Core/Quality Gates | **Security-Regression für ungültige Bildpfade ergänzt**: `tests/security/run.php` prüft zusätzlich, dass `ImageProcessor` kaputte Bilddateien sauber als `WP_Error` zurückweist. |
| **2.5.19** | 🔵 docs | Audit/Release | **Audit-Berichte auf sechs Kern-Dateien konsolidiert**: Die früheren Einzelberichte für Core, Feature, Performance und Security wurden in `DOC/audit/AUDIT_FACHBEREICHE.md` zusammengeführt; historische Testblocker sind jetzt in `AUDIT_TESTS_ToDo.md` konsolidiert und die Release-/Bewertungsdoku spiegelt den Stand mit **91/100 Punkten** wider. |

---

### v2.5.18 — 10. März 2026 · Media-Delivery-Härtung, Audit-Sync & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.18** | 🟡 refactor | Core/Media | **Private Medienauslieferung zentralisiert**: `MediaDeliveryService` bündelt jetzt die kontrollierte Auslieferung privater Member-Dateien über `GET /media-file`, normalisiert lokale Upload-URLs delivery-aware und versorgt Preview-/Download-Pfade mit passender Cache-Policy, `Last-Modified` und Rollen-/Owner-Prüfung. |
| **2.5.18** | 🔴 fix | Uploads/UX+Security | **Upload-Auslieferung sauber balanciert**: `MediaService::syncUploadsProtection()` hält Attachment + `nosniff` weiter als Standard, erlaubt sichere Bildtypen aber gezielt wieder inline; Media-Library, EditorJS sowie Featured-Image-Previews nutzen dafür jetzt delivery-aware Preview-/Access-URLs statt roher Upload-Links. |
| **2.5.18** | 🟢 feat | Core/Quality Gates | **Neue Media-Delivery-Regression ergänzt**: `tests/media-delivery/run.php` prüft Route, URL-Normalisierung, Member-Schutz und `.htaccess`-Header-Strategie; der Security-Workflow führt die Suite jetzt automatisch mit aus. |
| **2.5.18** | 🔵 docs | Audit/Release | **Audit- und Release-Spiegel nachgezogen**: Nacharbeitsliste, Fach-Audits, Bewertungsmatrix, Test-Checkliste, Changelog und Versions-Fallbacks spiegeln die abgeschlossene Medien-/Bild-/Proxy-Härtung jetzt konsistent wider. |

---

### v2.5.17 — 10. März 2026 · DocumentationSyncService-Split, Audit-Sync & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.17** | 🟡 refactor | Admin/System | **`DocumentationSyncService` weiter entschärft**: Der frühere 545-LOC-Doku-Sync-Block ist jetzt ein schlanker Orchestrator (`71 LOC`) über `DocumentationSyncEnvironment`, `DocumentationSyncFilesystem`, `DocumentationSyncDownloader`, `DocumentationGitSync` und `DocumentationGithubZipSync`; Environment-Probing, Git-Sync, GitHub-ZIP-Download und Dateisystem-Swap sind sauber getrennt. |
| **2.5.17** | 🟢 feat | Core/Quality Gates | **Neue Architekturabsicherung für den Doku-Sync**: `tests/documentation-sync-service/run.php` prüft den Split regressionsseitig, der Workflow führt den Check jetzt automatisch mit aus und der frühere Sync-Monolith bleibt damit unter Dauerbeobachtung. |
| **2.5.17** | ⬜ chore | Versionierung | **Release-Synchronisierung nachgezogen**: Badge, Installer, API-/Dashboard-Fallbacks, Landing-Defaults, Update-Metadaten, Audit-Spiegel und Core-Konstanten wurden auf `2.5.17` angehoben. |

---

### v2.5.16 — 10. März 2026 · media-proxy-Abbau, Audit-Sync & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.16** | 🟡 refactor | Core/Routing | **`media-proxy.php` vollständig abgebaut**: Die physische Legacy-Datei wurde entfernt; `GET /media-proxy.php` leitet jetzt zentral über `PublicRouter` auf `/member/media`, `POST /media-proxy.php` delegiert an `FileUploadService::handleUploadRequest()` und die Apache-Sonderbehandlung in `.htaccess` ist verschwunden. |
| **2.5.16** | 🟢 feat | Core/Quality Gates | **Neue Regression für den Legacy-Abbau**: `tests/media-proxy/run.php` prüft das Entfernen der Datei, die zentrale Router-Übernahme und den fehlenden Apache-Bypass; der Workflow führt den Check automatisch mit aus. |
| **2.5.16** | ⬜ chore | Versionierung | **Release-Synchronisierung nachgezogen**: Badge, Installer, API-/Dashboard-Fallbacks, Landing-Defaults, Update-Metadaten und Core-Konstanten wurden auf `2.5.16` angehoben. |

---

### v2.5.15 — 10. März 2026 · EditorJsMedia-Split, Audit-Sync & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.15** | 🟡 refactor | Core/EditorJs | **`EditorJsMediaService` weiter entschärft**: Der frühere 545-LOC-Medienkern ist jetzt ein schlanker Orchestrator (`87 LOC`) über `EditorJsRequestGuard`, `EditorJsUploadService`, `EditorJsRemoteMediaService` und `EditorJsImageLibraryService`; Guard-, Upload-, Remote-Fetch- und Bibliothekslogik sind sauber getrennt. |
| **2.5.15** | 🟢 feat | Core/Quality Gates | **Neue Architekturabsicherung für EditorJs-Media**: `tests/editorjs-media-service/run.php` prüft den Split regressionsseitig, der Workflow führt den Check jetzt automatisch mit aus und der EditorJs-Medienpfad bleibt damit dauerhaft unter Monolithenaufsicht. |
| **2.5.15** | ⬜ chore | Versionierung | **Release-Synchronisierung nachgezogen**: Badge, Installer, API-/Dashboard-Fallbacks, Landing-Defaults, Update-Metadaten und Core-Konstanten wurden auf `2.5.15` angehoben. |

---

### v2.5.14 — 10. März 2026 · LandingSection-Split, Audit-Sync & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.14** | 🟡 refactor | Core/Landing | **`LandingSectionService` weiter entschärft**: Der frühere 674-LOC-Landing-Kern ist jetzt ein schlanker Orchestrator (`129 LOC`) über `LandingDefaultsProvider`, `LandingHeaderService`, `LandingFeatureService` und `LandingSectionProfileService`; Defaults, Header/Farben, Feature-Migrationen sowie Footer-/Content-/Settings-/Design-Logik sind sauber getrennt. |
| **2.5.14** | 🟢 feat | Core/Quality Gates | **Neue Architekturabsicherung für Landing-Sections**: `tests/landing-section-service/run.php` prüft den Split regressionsseitig, der Workflow führt den Check jetzt automatisch mit aus und der Landing-Kern bleibt damit dauerhaft unter Monolith-Verdacht statt wieder darunter begraben zu werden. |
| **2.5.14** | ⬜ chore | Versionierung | **Release-Synchronisierung nachgezogen**: Badge, Installer, API-/Dashboard-Fallbacks, Landing-Defaults, Update-Metadaten und Core-Konstanten wurden auf `2.5.14` angehoben. |

---

### v2.5.13 — 10. März 2026 · SeoMeta-Split, Audit-Sync & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.13** | 🟡 refactor | Core/SEO | **`SeoMetaService` weiter entschärft**: Der frühere 678-LOC-Meta-Kern ist jetzt ein schlanker Orchestrator (`89 LOC`) über `SeoSettingsStore`, `SeoMetaRepository`, `SeoSchemaRenderer`, `SeoAnalyticsRenderer` und `SeoHeadRenderer`; Settings, Persistenz, Schema, Analytics und Head-Rendering sind sauber getrennt. |
| **2.5.13** | 🟢 feat | Core/Quality Gates | **Neue Architekturabsicherung für SEO-Meta**: `tests/seo-meta-service/run.php` prüft den Split regressionsseitig, der Workflow führt den Check jetzt automatisch mit aus und die generische Architektur-Suite grandfathert `SeoMetaService.php` nicht länger als Ausnahme. |
| **2.5.13** | ⬜ chore | Versionierung | **Release-Synchronisierung nachgezogen**: Badge, Installer, API-/Dashboard-Fallbacks, Landing-Defaults, Update-Metadaten und Core-Konstanten wurden auf `2.5.13` angehoben. |

---

### v2.5.12 — 10. März 2026 · SiteTable-Split, Audit-Sync & Release-Nachzug

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.12** | 🟡 refactor | Core/SiteTable | **`SiteTableService` konsequent entschärft**: Der frühere 1065-LOC-Großservice ist jetzt ein schlanker Orchestrator (`128 LOC`) über `SiteTableRepository`, `SiteTableTemplateRegistry`, `SiteTableHubRenderer` und `SiteTableTableRenderer`; Rendering-, Persistenz-, Hub- und Exportlogik sind sauberer voneinander getrennt. |
| **2.5.12** | 🟢 feat | Core/Quality Gates | **Neue Architekturabsicherung für SiteTable**: `tests/site-table-service/run.php` prüft den Split regressionsseitig, der Workflow führt den Check jetzt automatisch mit aus und die generische Architektur-Suite grandfathert `SiteTableService.php` nicht länger als Ausnahme. |
| **2.5.12** | ⬜ chore | Versionierung | **Release-Synchronisierung nachgezogen**: Badge, Installer, API-/Dashboard-Fallbacks, Landing-Defaults, Update-Metadaten und Core-Konstanten wurden auf `2.5.12` angehoben; nebenbei wurde auch ein veralteter `2.5.4`-Fallback im API-Router beseitigt. |

---

### v2.5.11 — 10. März 2026 · Audit-Härtung, Runtime-Fixes & Release-Stabilisierung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.11** | 🔴 fix | Core/Auth+Security | **Mehrfachformular-CSRF und Login-Fehlerpfade stabilisiert**: wiederverwendbare CSRF-Tokens für identische Actions verhindern falsche Ablehnungen auf Multi-Form-Seiten; Login-/Passkey-Logging ruft `cms_log()` nun mit korrekter Signatur auf. |
| **2.5.11** | 🔴 fix | Member/Media | **Member-Medienpfade und Seitenbild-Uploads gehärtet**: persönliche Upload-Basispfade werden bei Bedarf automatisch erzeugt, und Upload-Metadaten greifen korrekt auf `Auth::getCurrentUser()` statt auf eine nicht existierende Methode zu. |
| **2.5.11** | 🔴 fix | Frontend/Kommentare | **Kommentarfluss Ende-zu-Ende repariert**: `POST /comments/post` ist sauber im Public-Router registriert, Frontend-Kommentare landen wieder in der Moderation, die Admin-Einzelfreigabe sendet nun den korrekten Status `approved`, und das Aktionsmenü scrollt nicht mehr im Tabellen-Overflow fest. |
| **2.5.11** | 🔴 fix | Admin/System+Users | **Mehrere produktive Testblocker beseitigt**: TOC-Speichern (`HY093`), Gruppenverwaltung (`execute()` statt falschem `query()`), Benutzeranlage ohne `CMS\Services\is_wp_error()`-Fatal sowie fehlendes `site_tables`-Schema inklusive Runtime-Nachzug sind bereinigt. |
| **2.5.11** | 🔴 fix | Admin/Runtime | **Leere und fatale Admin-/Theme-Pfade bereinigt**: Sidebar-/Dashboard-Nullwerte, 404-Headerwarnungen sowie früher leere Admin-Views wie `redirect-manager`, `mail-settings`, `updates` und `documentation` rendern wieder robust und kontextsicher. |
| **2.5.11** | 🟡 refactor | Core/Architektur | **Audit-Refactor-Welle umgesetzt**: Router-, Media-, SEO-, Landing-, EditorJs-, Hub-, Theme-Customizer-, Theme-Functions- und zuletzt Documentation-Module wurden weiter in kleinere Verantwortungsbereiche zerlegt; `DocumentationModule` delegiert nun an Katalog-, Render- und Sync-Services statt selbst alles zu schleppen. |
| **2.5.11** | 🟠 perf | Core/Performance | **Bootstrap-, Cache- und Diagnosepfade ausgebaut**: proxy-freundliche Cache-Header (`s-maxage`, `stale-if-error`, `Surrogate-Control`), robustes `Vary`-Merging, OPcache-Warmup der 30 größten PHP-Dateien, echte Core-Web-Vitals-Felddaten sowie Debug-Runtime-/Query-Telemetrie verbessern Messbarkeit und Kaltstartverhalten. |
| **2.5.11** | 🟢 feat | Core/Observability | **Nutzungs- und Runtime-Metriken erweitert**: Admin-/Member-Funktionsnutzung wird datensparsam erfasst, SEO-Analytics zeigt echte Feature-Nutzung, und die Diagnoseschiene liefert Query-Zähler, langsame SQLs und Runtime-Checkpoints. |
| **2.5.11** | 🟢 feat | Core/Quality Gates | **Regressions- und Architekturtests deutlich verbreitert**: neue Checks für Architekturregeln, Contract-Grenzen, HTTP-Cache-Profile, Runtime-Telemetrie, Rollen/Capabilities, Router-Fallbacks, Admin-View-Guards, Medien-Defaults und Kommentarstatus laufen jetzt reproduzierbar im Workflow mit. |
| **2.5.11** | 🎨 style | Admin/UX | **Wiederkehrende Admin-Muster vereinheitlicht**: Flash-Alerts, leere Tabellenzustände und mehrere Liste-/Moderationsansichten verhalten sich konsistenter, robuster und weniger „Überraschungsparty im Backoffice“. |
| **2.5.11** | 🔵 docs | Audit/Release | **Audit-Stand konsolidiert**: Audit-Berichte, Nacharbeitslisten und Release-Doku spiegeln jetzt den gehärteten Stand mit **88/100 Punkten** Gesamtbewertung, deutlich robusterer Release-Basis und klarerem Rest-Backlog. |
| **2.5.11** | ⬜ chore | Versionierung | **CMS-Versionlinie angehoben**: Core-Konstanten, Installer, Update-Metadaten, API-/Dashboard-Fallbacks, Landing-Defaults und Changelog wurden auf `2.5.11` synchronisiert. |

---

### v2.5.4 — 08. März 2026 · Sitemap-Live-Fixes, SEO-Admin-Härtung & Doku-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.4** | 🔴 fix | Core/Sitemap | **Live-Sitemap-Generierung auf Webservern gehärtet**: Die eingebundene `melbahja/seo`-Sitemap-Engine wurde für den Web-Kontext abgesichert, sodass kein undefiniertes `STDOUT` mehr die Generierung von `sitemap.xml`, `pages.xml`, `posts.xml`, `images.xml` oder `news.xml` blockiert. |
| **2.5.4** | 🔴 fix | Admin/SEO | **CSRF-Token-Flow im SEO-Subnav stabilisiert**: Globale Aktionen wie „Sitemaps generieren“ und „robots.txt schreiben“ verwenden jetzt denselben gültigen `admin_seo_suite`-Token wie die Zielseite und erzeugen keine versehentlichen „Sicherheitstoken ungültig.“-Fehler mehr. |
| **2.5.4** | 🔴 fix | Auth/Passkeys | **Passkey-Schema dauerhaft integriert**: `passkey_credentials` ist jetzt offizieller Bestandteil von `SchemaManager` und `MigrationManager`; neue Installationen und bestehende Deployments erhalten die WebAuthn-Tabelle regulär, und fehlende Passkey-Migrationen reißen die Member-Sicherheitsseite nicht mehr in einen Fatal Error. |
| **2.5.4** | 🔴 fix | SchemaManager | **fehlende Tabellen ergänzt**: `cms_favorites` Tabelle (v15→v16) und `cms_security_log` Tabelle in SchemaManager ergänzt (v16→v17) |

---

### v2.5.3 — 08. März 2026 · melbahja/seo integriert, Sitemaps modularisiert, Admin ausgebaut

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.3** | 🟢 feat | Core/SEO | **`melbahja/seo` produktiv integriert**: Das lokale Asset-Bundle unter `CMS/assets/melbahja-seo/` ist jetzt per Autoloader eingebunden; `SEOService` rendert Schema.org über `Melbahja\Seo\Schema` und `Thing` statt über manuell gebaute JSON-LD-Strings. |
| **2.5.3** | 🟢 feat | Core/Sitemap | **Sitemap-Architektur modularisiert**: Neuer `SitemapService` erzeugt `pages.xml`, `posts.xml`, `images.xml`, `news.xml` und den Index `sitemap.xml` im sicheren TEMP-Modus; ergänzend steuert `IndexingService` IndexNow- und Google-Submissions. |
| **2.5.3** | 🎨 style | Admin/SEO | **SEO-Adminbereich erweitert**: Die Sitemap-/Schema-Ansichten zeigen jetzt den neuen Bundle-Status, die modulare Dateistruktur, News-Defaults sowie Formulare für manuelle URL-Submissions an IndexNow und Google. |
| **2.5.3** | 🔵 docs | Release | **Versionierung nachgezogen**: Changelog, `CMS/update.json` und die Core-Versionskonstante wurden auf den neuen Stand der SEO-Migration synchronisiert. |

---

### v2.5.2 — 08. März 2026 · Asset-Cleanup finalisiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.2** | ⬜ chore | Assets/Cleanup | **Runtime-Bereinigung**: Die ungenutzte Reserve-Library `schema-org/` sowie sämtliche ungenutzten Sub-Libs unter `CMS/assets/tabler/libs/` wurden endgültig aus dem Runtime-Baum entfernt. |
| **2.5.2** | 🔵 docs | Docs/Assets | **Asset-Dokumentation auf Löschstand synchronisiert**: `DOC/ASSET.md`, `DOC/ASSET_OUTDATET.md` und die Bundle-Referenzen dokumentieren jetzt den bereinigten Ist-Zustand ohne `schema-org/` und ohne `tabler/libs/`. |
| **2.5.2** | 🔴 fix | Assets/Autoload | **Autoloader nach Bereinigung konsistent gehalten**: Verweise auf entfernte Bundles wurden aus `CMS/assets/autoload.php` entfernt, während die FilePond-Locales bewusst unangetastet blieben. |

---

### v2.5.1 — 08. März 2026 · Asset-Inventar & Bundle-Doku konsolidiert

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.1** | 🔵 docs | Docs/Assets | **Asset-Inventar vollständig neu abgeglichen**: Die Runtime-Nutzung von `CMS/assets/` wurde systematisch geprüft und in `DOC/ASSET.md` mit aktiven, transitiven und reservierten Bundles sauber nachgezogen. |
| **2.5.1** | 🔵 docs | Docs/Bundles | **Bundle-Dokumentation vereinheitlicht**: Neue bzw. überarbeitete Detaildokus für `mailer/`, `mime/`, `psr/` und offene Migrationshinweise wie `melbahja-seo/` wurden im Doku-Baum verankert. |
| **2.5.1** | ⬜ chore | Assets/Autoload | **Stale Loader vorab bereinigt**: Nicht mehr vorhandene Pfade wie `image/` und `rate-limiter/` wurden aus dem Asset-Autoloader entfernt und die Mailer-/Mime-/PSR-Reihenfolge konsistent gezogen. |

---

### v2.5.0 — 08. März 2026 · Full Sync, Mail-Infrastruktur & Doku-Konsolidierung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.5.0** | 🟢 feat | Core/Mail | **Mail-Infrastruktur produktiv ausgebaut**: `MailQueueService` verarbeitet E-Mails asynchron über `CMS/cron.php`, inklusive Retry-Strategie, Backoff, Queue-Management und Diagnose-Hooks. |
| **2.5.0** | 🟢 feat | Core/Integrations | **Microsoft-365-/Transport-Bausteine ergänzt**: `AzureMailTokenProvider`, `GraphApiService`, `MailLogService` und `SettingsService` erweitern den Transport-Stack um Token-Caching, Graph-Zugriff, Laufzeit-Settings und nachvollziehbare Mail-Logs. |
| **2.5.0** | 🟢 feat | Auth/LDAP | **Authentifizierung erweitert**: LDAP-Provider, Admin-Statusansichten und ein initialer LDAP-Sync für lokale CMS-Konten wurden in die Benutzer-/Authentifizierungsverwaltung integriert. |
| **2.5.0** | 🟢 feat | Admin/API | **Admin und API robuster gemacht**: Neue API-Routen für Seiten und Medien, härtere CSRF-Verifizierung sowie eine Grid-basierte Benutzerlistenansicht modernisieren zentrale Verwaltungsabläufe. |
| **2.5.0** | 🔵 docs | Docs/Assets | **`/CMS/assets` und `/DOC` vollständig synchronisiert**: Asset-Mapping, Workflow-Dokumente, Service-Referenzen und lokale Bundle-Dokus wurden zusammengeführt und auf den aktuellen Runtime-Stand gehoben. |
| **2.5.0** | ⬜ chore | Repo/Cleanup | **Repository bereinigt**: Veraltete Admincenter-Bilder, alte To-do-Dokumente und überholte Asset-Aufräumhinweise wurden entfernt; zusätzliche Parser-Klassen wurden mit dem Asset-Sync übernommen. |

---

### v2.4.1 — 08. März 2026 · Workflows, SEO-Medienlogik & Betriebsdoku

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.4.1** | 🟢 feat | SEO/Media | **Medienverarbeitung im SEO-Kontext verfeinert**: Bestimmte Pfade werden gezielt aus Berechnungen ausgeschlossen, Verzeichnisgrößen robuster ermittelt und Medien-Scans betriebssicherer ausgewertet. |
| **2.4.1** | 🔵 docs | Workflow | **Operative Workflow-Doku deutlich ausgebaut**: Neue Leitfäden für Marketplace, Media-Upload, Update/Deployment sowie Forum- und Newsletter-Plugin dokumentieren reale Betriebs- und Entwicklungsabläufe. |
| **2.4.1** | 🔵 docs | Assets | **Asset-Nutzung präziser dokumentiert**: Empfehlungen zur aktiven Nutzung lokaler Bundles, Mail-/LDAP-Verdrahtung und Runtime-Pfade wurden zentral in der Asset-Dokumentation ergänzt. |
| **2.4.1** | 🟡 refactor | Admin/UI | **Benutzer- und Verwaltungsoberflächen modernisiert**: Listen, Medien- und API-nahe Admin-Flows wurden strukturell aufgeräumt und besser auf die aktuelle Admin-Architektur abgestimmt. |
| **2.4.1** | ⬜ chore | Repo | **Nicht mehr benötigte Assets und Alt-Dokumente bereinigt**: Unbenutzte Artefakte und veraltete Hinweise wurden entfernt, um Doku- und Repository-Struktur klarer zu halten. |

---

### v2.4.0 — 08. März 2026 · Mailer, Auth-Settings & Integrationsbasis

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.4.0** | 🟢 feat | Core/Mail | **MailService auf lokale Symfony-Komponenten umgestellt**: Versand nutzt nun klar dokumentiert `Symfony Mailer` und `Symfony Mime`, inklusive verbesserter Fehlerbehandlung und konsistenter Transportbasis. |
| **2.4.0** | 🟢 feat | Admin/Auth | **Benutzer- und Authentifizierungs-Einstellungen erweitert**: Neue Verwaltungsflächen bündeln Status und Konfiguration für Login-, Provider- und LDAP-bezogene Einstellungen. |
| **2.4.0** | 🟢 feat | Auth/LDAP | **LDAP-Authentifizierung implementiert**: Externe Verzeichnisdienste lassen sich anbinden; ergänzend wurde der Admin-Erstsync für Benutzerkonten vorbereitet. |
| **2.4.0** | 🟢 feat | Core/Services | **Neue Integrations-Services ergänzt**: `SettingsService`, `GraphApiService`, `AzureMailTokenProvider` und `MailLogService` schaffen die Basis für moderne Mail- und Provider-Anbindungen. |
| **2.4.0** | 🔵 docs | Docs/Release | **Release-Dokumentation nachgezogen**: Mail-, Auth-, Asset- und Service-Dokumente wurden auf die neue Integrationslinie ausgerichtet und im Doku-Baum verankert. |

---

### v2.3.1 — 07. März 2026 · WebP-Automation, Font-Self-Hosting & Audit-Härtung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.3.1** | 🟢 feat | Admin/Performance | **WebP-Massenkonvertierung produktiv ergänzt**: Geeignete Bilder in `uploads/` können gesammelt in WebP umgewandelt werden; bekannte Referenzen in Medien-, Seiten-, Beitrags- und SEO-Daten werden automatisch auf die neue Datei aktualisiert. |
| **2.3.1** | 🔴 fix | Admin/Fonts | **Font-Manager robuster gemacht**: Download externer Google-Fonts nutzt zusätzliche Fallbacks für `css`/`css2`, toleriert typische SSL-/CA-Probleme auf Shared-Hosting/Windows-Setups besser und speichert erfolgreiche Self-Hosting-Aktionen nachvollziehbar. |
| **2.3.1** | 🔴 fix | Admin/SEO | **SEO-Audit defensiv stabilisiert**: Audit-Ansicht und Modul normalisieren fehlende Score-/Issue-Daten jetzt zuverlässig und vermeiden Notice-/Warning-Folgen bei unvollständigen Datensätzen. |
| **2.3.1** | 🟡 refactor | Audit/Logging | **Admin-Aktionen stärker protokolliert**: Firewall-, AntiSpam-, Plugin-, Font-, Performance- und Sicherheits-Audit-Aktionen schreiben strukturierte Einträge ins zentrale `audit_log`. |
| **2.3.1** | 🔵 docs | Docs/Release | **README, Changelog und Doku-Indizes auf 2.3.1 ausgerichtet**: Release-Linie, Monitoring-/SEO-Ausbau sowie WebP-/Font-Funktionen wurden zentral dokumentiert. |

---

### v2.3.0 — 07. März 2026 · SEO Suite & Editor-Optimierung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.3.0** | 🟢 feat | Admin/SEO | **SEO-Suite deutlich erweitert**: Neue Bereiche für Audit, Meta-Daten, Social Media, Schema, Sitemap und technisches SEO wurden als eigene Admin-Unterseiten ergänzt. |
| **2.3.0** | 🟢 feat | Editor/SEO | **Seiten- und Beitragseditoren ausgebaut**: Drei SEO-/Readability-/Preview-Karten unter dem Editor, Live-Scoring, Social-Preview und erweiterte SEO-Felder verbessern den Redaktions-Workflow. |
| **2.3.0** | 🟢 feat | Core/SEO | **Sitemap-Bundle erweitert**: XML-Sitemaps für Standard-Inhalte, Bilder und News können zentral regeneriert und überwacht werden. |

---

### v2.2.0 — 07. März 2026 · Performance Center & System-Monitoring

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.2.0** | 🟢 feat | Admin/Performance | **Performance als eigener Hauptbereich**: Cache, Medien, Datenbank, Einstellungen und Sessions wurden in eigenständige, datengetriebene Admin-Unterseiten überführt. |
| **2.2.0** | 🟢 feat | Admin/System | **Info, Diagnose und Monitoring ausgebaut**: Response-Time, Cron-Status, Disk-Usage, Scheduled Tasks, Health-Check und E-Mail-Alerts ergänzen die Systemwerkzeuge. |
| **2.2.0** | 🟡 refactor | Admin/Navigation | **System- und SEO-Navigation neu strukturiert**: Hauptmenüs für SEO, Performance, System, Info und Diagnose wurden klarer aufgeteilt und konsistent sortiert. |

---

### v2.1.2 — 07. März 2026 · Legal-Sites, Lösch-Workflow & Sicherheits-Layout

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.1.2** | 🔴 fix | Admin/Pages+Posts | **Löschen von Seiten und Beiträgen stabilisiert**: Single-Delete nutzt wieder einen robusten Formular-Submit mit direkter Bestätigung; die Delete-Logik in den Modulen liefert bei Fehlern jetzt saubere Rückmeldungen statt stillem Nichtstun. |
| **2.1.2** | 🟢 feat | Admin/Legal | **Legal Sites um Profiltyp erweitert**: Rechtstexte können jetzt gezielt für `Firma` oder `Privat` gepflegt werden. Die Pflichtfelder passen sich server- und clientseitig an den gewählten Profiltyp an. |
| **2.1.2** | 🟢 feat | Admin/Legal+Cookie | **Legal-Sites synchronisieren Folgeeinstellungen**: Beim Erstellen oder Zuordnen von Datenschutz-, AGB- und Widerrufsseiten werden abhängige Felder in anderen Admin-Bereichen automatisch befüllt, z. B. `cookie_policy_url` im Cookie-Manager sowie rechtliche Seiten-IDs für Abo-/Checkout-Einstellungen. |
| **2.1.2** | 🎨 style | Admin/Security | **Firewall- und AntiSpam-Layouts repariert**: KPI-Cards, Formulare und Listen werden wieder korrekt innerhalb des Admin-Containers gerendert und sauber nebeneinander ausgerichtet. |
| **2.1.2** | 🔵 docs | Docs/Release | **README und Changelog erweitert**: Die neue Legal-Sites-Logik, die Auto-Verknüpfung mit Cookie-/Abo-Einstellungen sowie die stabilisierten Lösch-Workflows wurden in der Projektdokumentation nachgetragen. |

---

### v2.1.1 — 07. März 2026 · Medienverwaltung, Rollenrechte & Release-Dokumentation

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.1.1** | 🔴 fix | Admin/Media | **Medienbibliothek funktional vervollständigt**: Standardmäßig Listenansicht, Suche, Kategorien-Filter, Datei-/Ordner-Löschung, robustere Redirects nach Aktionen und korrekt URL-encodierte Vorschaupfade für Bilder mit Leerzeichen oder Umlauten. |
| **2.1.1** | 🟢 feat | Admin/Media | **Geschützter Member-Medienbereich**: Der Ordner `member` verlangt vor dem Öffnen eine zusätzliche Bestätigung, wird als geschützter Systembereich behandelt und Member-Bilder werden in der Vorschaubild-Auswahl für Seiten/Beiträge ausgeblendet. |
| **2.1.1** | 🟡 refactor | Admin/Navigation | **Medien-Navigation aufgeräumt**: Doppelte Tab-Navigation entfernt, aktive Sidebar-Zustände für Medien-Unterseiten korrigiert und der Medien-Menübereich bleibt bei Unterpunkten zuverlässig geöffnet. |
| **2.1.1** | 🟢 feat | Admin/RBAC | **Rollen & Rechte erweiterbar gemacht**: In `Benutzer & Gruppen -> Rollen & Rechte` können jetzt neue Rollen und neue Rechte direkt angelegt werden; die Matrix verarbeitet dynamische Rollen und Capabilities. |
| **2.1.1** | 🔴 fix | Admin/Users | **Benutzerverwaltung an dynamische Rollen angebunden**: Rollen-Dropdowns und Filter in Listen- und Bearbeitungsansichten nutzen nun dieselbe dynamische Rollenquelle wie die Rechteverwaltung. |
| **2.1.1** | 🔴 fix | Core/Auth | **Capability-Prüfung DB-basiert erweitert**: `Auth::hasCapability()` berücksichtigt gespeicherte Rollenrechte aus `role_permissions`, damit neu angelegte Rollen sofort wirksam sind. |
| **2.1.1** | 🔵 docs | Docs/Release | **README, Changelog und Release-Metadaten synchronisiert**: Versionsstände auf `2.1.1` angehoben, neue Medien- und RBAC-Funktionen dokumentiert und die Patch-Version ohne Versionssprung in die Historie aufgenommen. |

---

### v2.1.0 — 07. März 2026 · Editor.js, Routing, Services & System-Tools

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.1.0** | 🟢 feat | Core/Editor | **Editor.js zusätzlich integriert**: Neben SunEditor steht jetzt auch Editor.js für moderne, blockbasierte Inhalte zur Verfügung. |
| **2.1.0** | 🟢 feat | Core/Services | **Neue Services ergänzt**: Comment-Management, Cookie-Consent, File-Uploads, PDF-Generierung, Site-Tables und Translation-Services wurden ausgebaut bzw. neu integriert. |
| **2.1.0** | 🟢 feat | Core/Router | **Mitglieder- und Dashboard-Routing erweitert**: Eigene Dashboard-Routen, Theme-Overrides, POST-Routen für den Member-Bereich und zusätzliche Seitennamen-Prüfungen ergänzen das Routing-System. |
| **2.1.0** | 🟢 feat | Admin/System | **DB-Tools in System-Info & Diagnose**: Neue Aktionen zum Erstellen fehlender Tabellen und zur Tabellen-Reparatur direkt im Admin. Die Diagnose deckt jetzt das vollständige Core-Schema mit 30 Tabellen inkl. `posts`, `comments`, `messages`, `audit_log` und `custom_fonts` ab. |
| **2.1.0** | 🟢 feat | Admin/RBAC | **Benutzer-, Rollen- und Berechtigungsverwaltung erweitert**: Neue Verwaltungsansichten und überarbeitete Admin-Oberflächen erleichtern Rollen- und Rechtemanagement. |
| **2.1.0** | 🟢 feat | Admin/Theme | **Schriften & Theme-Assets erweitert**: Brand-Schriften wurden in Download- und Ladefunktion integriert; Google Fonts lassen sich DSGVO-konform lokal speichern und im Frontend einbinden. Neue Tabelle `custom_fonts`, Schema-Version `v9`. |
| **2.1.0** | 🔴 fix | Admin/Security | **CSRF-Token-Flows korrigiert**: Die Token-Reihenfolge in Admin-Formularen wurde bereinigt, fehlende Token-Erzeugung auf normalen GET-Loads ergänzt und fehleranfällige Formularabläufe stabilisiert. |
| **2.1.0** | 🔴 fix | Admin/System | **Diagnose-Ansichten stabilisiert**: Berechtigungsanzeige und System-Info verarbeiten Rückgabedaten wieder korrekt und vermeiden TypeErrors in der Ausgabe. |
| **2.1.0** | 🟡 refactor | Admin/UI | **Admin-UI modernisiert**: Theme-Seiten, Dashboard, Posts- und User-Oberflächen wurden aufgeräumt, stärker auf Tabler Icons ausgerichtet und strukturell vereinheitlicht. |
| **2.1.0** | 🔴 fix | Theme/Navigation | **Defensive Verarbeitung für Menüs und Themes**: Ungültige Einträge in Theme- und Menü-Arrays werden robuster abgefangen und übersprungen. |
| **2.1.0** | 🔵 docs | Docs | **README & Changelog komplett aktualisiert**: Release-Version angehoben, System-/Schema-Dokumentation korrigiert und vollständige Übersicht der gebündelten Drittanbieter-Assets mit Autor, Website und GitHub-Links ergänzt. |

---

### v2.0.9 — 07. März 2026 · Rollenverwaltung & Release-Vorbereitung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.9** | 🟢 feat | Admin/RBAC | **Rollen- und Berechtigungsansicht erweitert**: Neue Verwaltungsoberfläche für Benutzerrollen und Berechtigungen vorbereitet bzw. eingebunden. |
| **2.0.9** | ⬜ chore | Assets | **Vendor-/Asset-Bestand bereinigt**: Größere Asset-Bestände wie `remark42` wurden in der Arbeitsbasis überarbeitet bzw. ausgeräumt, um das Repository zu konsolidieren. |
| **2.0.9** | 🔵 docs | Project | **Release-Vorbereitung für 2.1.0**: Versionspflege und Projekt-Metadaten wurden auf den nächsten Major-Patch-Zwischenschritt vorbereitet. |

---

### v2.0.8 — 06. März 2026 · Services-Ausbau

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.8** | 🟢 feat | Core/Services | **Neue Service-Bausteine ergänzt**: Comment-Management, Cookie-Consent, File-Uploads, PDF-Generierung, Site-Tables und Translation wurden als Services ergänzt bzw. deutlich erweitert. |
| **2.0.8** | 🟢 feat | Core/Docs | **Infrastruktur für weitere Core-Integrationen**: Die Service-Schicht wurde als Grundlage für zusätzliche Admin- und Frontend-Funktionen ausgebaut. |

---

### v2.0.7 — 05. März 2026 · Admin-UI, Editor.js & Asset-Sync

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.7** | 🟢 feat | Core/Editor | **Editor.js integriert**: Editor.js wurde zusätzlich zu SunEditor eingebunden, um blockbasierte Inhaltsbearbeitung zu ermöglichen. |
| **2.0.7** | 🟡 refactor | Admin/UI | **Admin-Oberflächen überarbeitet**: Dashboard, Posts, Users und Theme-Seiten wurden strukturell modernisiert, aufgeräumt und stärker auf Tabler abgestimmt. |
| **2.0.7** | 🟢 feat | Admin/Layout | **Layout-Funktionen für Dashboard/Seiten ausgebaut**: HTML-Strukturen wurden in zentrale Layout-Helfer überführt und Script-Verknüpfungen vereinheitlicht. |
| **2.0.7** | ⬜ chore | Assets | **Asset-Bestand aktualisiert**: Zusätzliche Vendor-Assets wie Tabler-Libs wurden in die Arbeitsbasis übernommen; veraltete Test-/Import-Verzeichnisse wurden entfernt. |

---

### v2.0.6 — 04. März 2026 · Fonts & Dashboard-Routing

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.6** | 🟢 feat | Core/Router | **Dashboard-Routen ergänzt**: Themes können jetzt ein eigenes Dashboard ausspielen; andernfalls greift sauber der Fallback auf `/member/dashboard`. |
| **2.0.6** | 🟢 feat | Admin/Theme | **Brand-Schriftarten erweitert**: Brand-Fonts wurden sowohl in die Ladefunktion als auch in die Downloadfunktion für den Font-Workflow aufgenommen. |

---

### v2.0.5 — 03. März 2026 · Member-Routing & defensive Theme-Menüs

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.5** | 🟢 feat | Core/Router | **Memberbereich auf `/member/dashboard` umgestellt**: Routing für den Mitgliederbereich wurde vereinheitlicht und Theme-Overrides für Seitenimplementierungen ergänzt. |
| **2.0.5** | 🔴 fix | Theme/Navigation | **Defensive Menü-/Theme-Verarbeitung**: Ungültige Einträge in Menü- und Theme-Arrays werden jetzt robuster erkannt und übersprungen. |

---

### v2.0.4 — 02. März 2026 · Member-POST-Routen & Kontaktseite

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.4** | 🟢 feat | Core/Router | **POST-Routen für den Mitgliederbereich**: Formulare und Aktionen im Memberbereich erhielten eigene POST-Routen inklusive zusätzlicher Prüfung erlaubter Seitennamen. |
| **2.0.4** | 🟢 feat | Frontend/Kontakt | **Kontaktseite im Routing berücksichtigt**: Kontaktformulare bekamen die nötige Sonderbehandlung im Routing, damit Frontend-POSTs sauber verarbeitet werden. |

---

### v2.0.3 — 01. März 2026 · Legal-Generator & Abo-Zuweisungen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.3** | 🔴 fix | Admin/Abo | **Benutzer-Abos in Zuweisungen abgesichert**: Die Anzeige und Zuordnung aktiver Benutzer-Abos wurde nach dem Split der Abo-Ansichten weiter stabilisiert. |
| **2.0.3** | 🟢 feat | Admin/Legal | **Impressum-Generator nachgeschärft**: Der Generator wurde weiter erweitert und für den produktiven Einsatz in den Rechtstexten verfeinert. |

---

### v2.0.2 — 01. März 2026 · Admin-Fixes, SEO-Frontend, Abo-Split

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.2** | 🔴 fix | Admin/Legal | **Legal Pages Posts->Pages**: `cms_posts` durch `cms_pages` ersetzt; `type`-Spalte entfernt (existiert nicht). DSGVO-Texte erweitert (Art. 13/14, EU-Streitschlichtung, SSL/TLS). Unicode-Quotes durch HTML-Entities ersetzt. |
| **2.0.2** | 🟢 feat | Admin/Legal | **Impressum Generator erweitert**: Neue Abschnitte: Haftung fuer Inhalte, Haftung fuer Links, Urheberrechtshinweis. Neue Formularfelder: Website-Name, Registergericht, verbundene Domains, Datenschutzbeauftragter. Kontaktzeile zeigt Telefon nur wenn ausgefuellt. HTML-Entities statt Unicode-Sonderzeichen. |
| **2.0.2** | 🔴 fix | Admin/Media | **CSRF Auto-Retry**: `cmsPost()` erkennt CSRF-Fehler und wiederholt den Request automatisch mit neuem Token. Behebt "Sicherheitsueberprüfung fehlgeschlagen" bei Ordner-Navigation. |
| **2.0.2** | 🟢 feat | Core/SEO | **SEO Frontend-Integration**: 5 neue public Getter in `SEOService` (`getHomepageTitle`, `getHomepageDescription`, `getMetaDescription`, `getSiteTitleFormat`, `getTitleSeparator`). Theme-Header nutzt SEO-Titel-Prioritaetskette. |
| **2.0.2** | 🟢 feat | Admin/Theme | **Multi-Rolle Editor-Zugriff**: Einzelauswahl-Dropdown durch Mehrfach-Checkboxen ersetzt (`theme_editor_roles`, kommasepariert). Marketplace-Sektion komplett entfernt. |
| **2.0.2** | 🔴 fix | Admin/Settings | **Aktive Module Mock entfernt**: Hardcodierte "Blog Modul: Aktiv / Shop System: Inaktiv"-Karte entfernt. |
| **2.0.2** | 🔴 fix | Core/UpdateService | **Update-URL konfigurierbar**: GitHub Repo/API-URL per DB-Setting (`update_github_repo`, `update_github_api`) konfigurierbar mit Fallback auf Defaults. Behebt HTTP 404 bei falscher API-URL. |
| **2.0.2** | 🟢 feat | Admin/Abo | **Zuweisungen gesplittet**: Neuer Tab "Uebersicht" (`?tab=overview`) mit Statistiken, Benutzer-Abo-Tabelle und Gruppen-Paketzuordnung (read-only). Tab "Zuweisungen" nur noch fuer Formulare. Neuer Sidebar-Menuepunkt. |
| **2.0.2** | 🔴 fix | Admin/Abo | **Benutzer-Abos in Zuweisungen**: Aktive Benutzer-Abos-Tabelle zurueck in den Tab "Zuweisungen" (war nach Overview-Split verloren gegangen). |
| **2.0.2** | 🔴 fix | Plugins/JPG | **Installer Column-Fix**: `setting_key`/`setting_value` auf `option_name`/`option_value` korrigiert fuer Zugriff auf Core-Tabelle `cms_settings`. |

---

### v2.0.1 — 01. März 2026 · Admin-Panel Audit & Bugfixes

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.1** | 🔴 fix | Admin/Media | **CSRF-Token-Rotation**: `query()` → `execute()` für AJAX-Aufrufe; neuer Token wird nach jeder Verifizierung zurückgegeben und im JS aktualisiert (`const` → `let` für `CMS_MEDIA_NONCE`). |
| **2.0.1** | 🔴 fix | Admin/Users+Groups | **Dynamische Rollen**: Hardcodierte Rollen-Arrays (`['admin','member','editor']`) durch DB-Abfragen aus `cms_roles` ersetzt; Rollendropdowns, Filter-Tabs und Validierung nutzen jetzt alle CMS-Rollen. Auto-Migration für `sort_order`/`member_dashboard_access`-Spalten in `groups.php`. |
| **2.0.1** | 🔴 fix | Admin/Subscriptions | **SQL-Fehler behoben**: `$db->query()` (kein Param-Support) → `$db->execute()` für Prepared Statements in `update_settings`, `assign_group_plan` und `delete_plan`. In `subscription-settings.php`: nicht-existierende `fetch()`/`fetchColumn()` → `get_row()`/`get_var()`; CSRF Action-Slug ergänzt. |
| **2.0.1** | 🔴 fix | Admin/Theme | **Editor-Rolle dynamisch**: Dropdown in `theme-settings.php` zeigt jetzt alle DB-Rollen statt nur `admin`/`editor`. |
| **2.0.1** | 🔴 fix | Admin/Legal | **type=page bei INSERT**: Impressum und Datenschutz-Posts erhalten jetzt `'type' => 'page'` wie Cookie-Richtlinie. |
| **2.0.1** | 🔴 fix | Admin/Settings | **Rollen-Validierung dynamisch**: `$allowedRoles` wird aus DB geladen statt hardcodiert (`['admin','editor','author','member','subscriber']`). |
| **2.0.1** | 🔴 fix | Admin/System | **Tabellenzahl nicht mehr hardcoded**: `/ 22` aus CMS-Tabellen-Anzeige entfernt (tatsächlich 29+ Tabellen). |
| **2.0.1** | 🟡 refactor | Admin/Updates | `window.confirm()` durch eigenes Bestätigungs-Modal ersetzt (Konventions-konform). |
| **2.0.1** | 🟡 refactor | Admin/Layout | `theme-marketplace.php`, `plugin-marketplace.php`, `support.php`, `updates.php`: Manuelles HTML-Boilerplate durch `renderAdminLayoutStart()`/`renderAdminLayoutEnd()` ersetzt. |

---

### v2.0.0 — 28. Februar 2026 · Nachrichten-System & Security-Audit

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **2.0.0** | 🟢 feat | Core/Member | **Nachrichten-System**: Vollständige Member-to-Member-Messaging-Funktion mit Posteingang, Gesendet-Ansicht, Thread-Konversationen, Empfänger-Autocomplete und Soft-Delete. Neue `cms_messages`-Tabelle (SchemaManager v8). Neuer `MessageService` (Singleton, Inbox/Sent/Thread/Send/Delete/UnreadCount). Member-Dashboard mit Two-Panel-Layout (Konversationsliste + Detail/Thread/Compose). **Security-Audit**: 10 CRITICAL- und 9 HIGH-Priority-Fixes implementiert (XSS-Escaping, CSRF-Schutz, Path-Traversal, Admin-Passwort, SQL-Injection-Prävention). **Installer**: CMS_VERSION → 2.0.0, PHP-Mindestversion → 8.2, Messages-Tabelle hinzugefügt. |

---

### v1.9.x — 27.–28. Februar 2026 · Security Hardening & UI-Improvements

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **1.9.5** | 🔴 fix | Admin/Security | **Media**: XSS-Escaping mit `escHtml()`/`escAttr()` für Dateinamen und Alt-Texte; Custom Delete-Modal statt `window.confirm()`. **Pages**: `wp_kses_post` für Seiteninhalt-Sanitierung; Custom Lösch-Modal. **Backup**: Path-Traversal-Schutz mit `basename()`+Regex. **Updates**: Core-Update-Button mit AJAX-Handler. **Security-Audit**: Inline-CSS + admin-page-header Fix. **Plugin-UI**: 10px Menü-Spacing-Fix. |
| **1.9.4** | 🔴 fix | Admin | `ABSPATH`-Guard in `users.php` hinzugefügt; dupliziertes HTML entfernt; `last_login`-Spalte und Delete-Button ergänzt; Site-URL-Definition sichergestellt. |
| **1.9.3** | 🎨 style | Admin | Plugin-Management-UI komplett überarbeitet für besseres Layout und Responsiveness. |
| **1.9.2** | 🟡 refactor | Theme | ThemeCustomizer: `prepare/execute` durch `execute` ersetzt für korrekte NULL-Wert-Behandlung; Error-Logging verbessert. |
| **1.9.1** | 🔴 fix | Admin/Plugin | Unbenutzte Feature-Widgets aus Widget-Dashboard entfernt; Output-Buffering für Plugin-Admin-Bereich korrigiert. |
| **1.9.0** | 🟢 feat | Member | Accordion-Navigation für Member-Sidebar mit ausklappbaren Plugin-Bereichen und verbessertem Styling. |

---

### v1.8.x — 22.–26. Februar 2026 · Security, Themes & Blog

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **1.8.5** | 🟢 feat | Member | Plugin-Navigation im Member-Bereich mit Sub-Items und verbessertem Styling. |
| **1.8.4** | ⬜ chore | Theme | TechNexus-Theme und zugehörige Unit-Tests entfernt. |
| **1.8.3** | 🟢 feat | Theme | 365Network Theme Customizer: Konfigurierbare Einstellungen für Farben, Typografie, Layout, Header, Footer, Buttons und erweiterte Optionen. |
| **1.8.2** | 🔴 fix | Core | `Security::escape()` akzeptiert nun `string|int`. EditorService nutzt `setContents()` statt `set()` um WYSIWYG-Double-Encoding zu verhindern. `fetchGitHubData` von `file_get_contents` auf cURL umgestellt. |
| **1.8.1** | 🔴 fix | Router/Admin | **Öffentliche Seiten 404-Bug**: Neue CMS-Seiten wurden standardmäßig als `draft` angelegt. `admin/pages.php` – Default-Status auf `published` geändert. Router-Debug-Logging verbessert. |
| **1.8.0** | 🟢 feat | Security | CMS-Firewall mit IP-Blocking, Geo-Filtering und Request-Analyse sowie AntiSpam und Security-Audit vollständig überarbeitet. |

---

### v1.7.x — 22. Februar 2026 · Theme & Plugin Marketplace

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.7.9 | 🟢 feat | Admin | RBAC-Verwaltung vollständig neu implementiert mit granularen Capabilities |
| 1.7.8 | 🟢 feat | Admin | Support-Ticket-System mit Prioritäten und Status-Tracking in Admin integriert |
| 1.7.7 | 🟢 feat | Theme | Theme-Marketplace mit 10 fertigen Themes und Vorschau-Funktion |
| 1.7.6 | 🟢 feat | Plugin | Plugin-Marketplace mit Kategorie-Browser und Such-Filter |
| 1.7.5 | 🟢 feat | Theme | Lokaler Fonts Manager mit Upload, Verwaltung und Theme-Integration |
| 1.7.4 | 🟡 refactor | Theme | Theme-Customizer auf 50+ Optionen in 8 Kategorien erweitert |
| 1.7.3 | 🟢 feat | Admin | Update-Manager für Core, Plugins und Themes direkt via GitHub API |
| 1.7.2 | 🟢 feat | Admin | Benutzerdefinierte Site-Tables mit CRUD, Import/Export CSV/JSON erweitert |
| 1.7.1 | 🟢 feat | Member | Member-Dashboard Admin-Verwaltung mit Übersichts- und Statusseite überarbeitet |
| 1.7.0 | 🟢 feat | Admin | README-Dokumentation vollständig mit Screenshots und Feature-Übersicht aktualisiert |

---

### v1.6.x — 21.–22. Februar 2026 · Cookie-Manager & Legal-Suite

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.6.9 | 🟢 feat | Cookie | Cookie-Verwaltung mit Dienstbibliothek und Sicherheitsprüfungen erweitert |
| 1.6.8 | 🔵 docs | Core | Dokumentation und Skripte für 365CMS aktualisiert |
| 1.6.7 | ⬜ chore | Docs | Veraltete Sicherheitsarchitektur-Dokumentation entfernt |
| 1.6.6 | 🔵 docs | README | README-Dateien mit neuen Versionsinformationen und verbesserten Beschreibungen aktualisiert |
| 1.6.5 | 🟢 feat | Admin | Site-Tables-Management mit CRUD-Operationen und Import/Export; neue Menüeinträge |
| 1.6.4 | 🟡 refactor | Legal | Generierung von Rechtstexten bereinigt und optimiert; Menübezeichnung aktualisiert |
| 1.6.3 | 🟢 feat | Cookie | Cookie-Richtlinie mit dynamischem Zustimmungsstatus und optimierter Darstellung |
| 1.6.2 | 🟢 feat | Cookie | Cookie-Richtlinie-Generierung in Rechtstexte-Generator integriert |
| 1.6.1 | 🟢 feat | Legal | AntiSpam-Einstellungsseite und Rechtstexte-Generator implementiert |
| 1.6.0 | 🟢 feat | Cache | Cache-Clearing-Funktionalität und Asset-Regenerierung hinzugefügt |

---

### v1.5.x — 21. Februar 2026 · Support-System & DSGVO

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.5.9 | 🔴 fix | Database | Tabellenbezeichnungen von `usermeta` zu `user_meta` in mehreren Dateien aktualisiert |
| 1.5.8 | 🔴 fix | SEO | Einstellungsname für benutzerdefinierten robots.txt-Inhalt korrigiert |
| 1.5.7 | 🟢 feat | GDPR | DSGVO-konforme Datenlöschung und Security-Audit-Seite hinzugefügt |
| 1.5.6 | 🔵 docs | Docs | INDEX.md in Dokumentationsliste priorisiert; Dokumentationsindex bereinigt |
| 1.5.5 | 🔵 docs | Docs | Dokumentation für Content-Management, SEO, Performance, Backup und User-Management |
| 1.5.4 | 🟡 refactor | Support | Übersichtsseiten je Bereich mit GitHub-Links statt Markdown-Rendering |
| 1.5.3 | 🔴 fix | Support | Timeout auf 4/6s reduziert; 5-min-Datei-Cache für Dok-Liste; Refresh-Link |
| 1.5.2 | 🔴 fix | Support | fetchDocContent auf GitHub Contents-API umgestellt; CDN entfernt, Markdown serverseitig gerendert |
| 1.5.1 | 🔴 fix | Support | cURL-basierter GitHub-API-Client; Debug-Modus; DOC/admin-Ordner umbenannt |
| 1.5.0 | 🟡 refactor | Support | Support.php komplett neu: Docs ausschließlich via GitHub API + raw.githubusercontent.com |

---

### v1.4.x — 21. Februar 2026 · Admin-Erweiterungen & Logging

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.4.9 | 🟢 feat | Docs/Support | Dokumentationsabruf mit rekursivem Directory-Traversal; Sidebar-Gruppierung |
| 1.4.8 | 🟡 refactor | Core | File-Struktur bereinigt; Code-Struktur für bessere Lesbarkeit optimiert |
| 1.4.7 | 🟢 feat | Admin | Plugin- und Theme-Marketplace-Seiten mit Settings-Management hinzugefügt |
| 1.4.6 | 🟢 feat | Landing | Landing-Page-Management erweitert |
| 1.4.5 | 🔴 fix | Logging | Logs werden nur noch bei `CMS_DEBUG=true` in `/logs` geschrieben |
| 1.4.4 | 🎨 style | Orders | Admin-Design für Bestellverwaltung vereinheitlicht (Benutzer & Gruppen) |
| 1.4.3 | 🔵 docs | Changelog | Versionierung auf 0.x umgestellt; Changelog + README aktualisiert |
| 1.4.2 | 🟢 feat | Subscriptions | Admin-Subscriptions-UI mit verbesserter Navigation und Labels |
| 1.4.1 | 🟡 refactor | Subscriptions | Pakete-Editor in Übersicht integriert; neue Einstellungen-Seite; Sub-Tabs entfernt |
| 1.4.0 | 🟢 feat | Dashboard | Version-Badge im Admin Dashboard-Header |

---

### v1.3.x — 20. Februar 2026 · Public Release & Blog/Subscriptions

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.3.6 | ⬜ chore | CI/CD | PHP-Composer-Workflow-Konfiguration hinzugefügt |
| 1.3.5 | 🟢 feat | Subscriptions | Subscription- und Checkout-System implementiert |
| 1.3.4 | 🟢 feat | Pages | Page-Management-UI mit Success/Error-Messages und verbessertem Layout |
| 1.3.3 | 🔴 fix | Security | CSRF-Token-Handling in User- und Post-Management-Formularen verbessert |
| 1.3.2 | 🟢 feat | Blog | Blog-Routen für Post-Listing und Single-Post-Detailansicht hinzugefügt |
| 1.3.1 | 🟢 feat | Database | Datenbankschema auf Version 3 aktualisiert; Blog-Post-Tabellen hinzugefügt |
| **1.3.0** | 🟢 feat | **Release** | **First Public Release – 365CMS.DE veröffentlicht** |

---

### v1.2.x — 18.–20. Februar 2026 · Media & Member-Erweiterungen

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.2.7 | 🔵 docs | Projekt | Initial Commit 365CMS.DE Repository; README mit CMS-Beschreibung und Website-Link |
| 1.2.6 | 🟢 feat | Subscriptions | Zahlungsarten-Update implementiert; Benutzerabonnements-Abfrage verbessert |
| 1.2.5 | 🟢 feat | Member | Member-Menü überarbeitet; Favoriten- und Nachrichten-Funktionalität hinzugefügt |
| 1.2.4 | 🟡 refactor | Error | Fehlerbehandlung überarbeitet; Media-Upload-Struktur für mehr Robustheit verbessert |
| 1.2.3 | 🟡 refactor | Media | Media-View und AJAX-Handling für bessere UX und Fehlerbehandlung überarbeitet |
| 1.2.2 | 🔴 fix | AJAX | AJAX-URL-Handling für mehr Robustheit und Debugging verbessert |
| 1.2.1 | 🟢 feat | Media | Media-Proxy und AJAX-Handling für verbesserte Medienoperationen implementiert |
| 1.2.0 | 🟢 feat | Media | Medien-AJAX-Handling und Authentifizierung verbessert; robustere Fehlerbehandlung |

---

### v1.1.x — 10.–18. Februar 2026 · Member-System & Plugins

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.1.9 | 🟢 feat | Member | Member-Medien-Management implementiert (Upload, Verwaltung) |
| 1.1.8 | 🟢 feat | Admin | Dashboard-Funktionalität um Logo-Upload erweitert; Widget-Anzahl auf 4 erhöht |
| 1.1.7 | 🔵 docs | Themes | Umfassende Dokumentation für Theme-Entwicklung in CMSv2 erstellt |
| 1.1.6 | 🟢 feat | Member | Member-Service hinzugefügt; CMS-Speakers-Plugin refaktoriert |
| 1.1.5 | 🟢 feat | Events | CMS-Experts und Events-Management erweitert |
| 1.1.4 | 🟢 feat | Experts | Expert-Management: Status-Updates, Skill-Presets und Plugin-Einstellungen |
| 1.1.3 | 🟡 refactor | Core | Code-Struktur für bessere Lesbarkeit und Wartbarkeit refaktoriert |
| 1.1.2 | 🟢 feat | Landing | Landing-Page-Service um Footer-Management erweitert |
| 1.1.1 | 🟢 feat | Cookie | Cookie-Scanning-Funktionalität mit serverseitigen und Content-Heuristik-Prüfungen |
| 1.1.0 | 🟢 feat | Admin | Landing-Page und Theme-Management-Funktionalität im Admin hinzugefügt |

---

### v1.0.x — 01.–09. Februar 2026 · Stabilisierung & AJAX-Architektur

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 1.0.9 | 🔴 fix | Dashboard | Escaped Backslash-Dollar in SQL-Prefix-Interpolation entfernt |
| 1.0.8 | 🔴 fix | Subscriptions | Fehlendes PHP-Schlusstag `?>` in create-plan-Modal (Zeile 521) ergänzt |
| 1.0.7 | 🔴 fix | Subscriptions | Price-Felder zu Float gecastet vor `number_format()` |
| 1.0.6 | 🔴 fix | Core | Sicherheits-Fixes in Core-Klassen |
| 1.0.5 | 🔴 fix | Core | Datenbank-Prefix-Methoden und Session-Logout-Handling verbessert |
| 1.0.4 | 🟢 feat | Admin | Vollständiger Admin-Bereich: AJAX-Architektur für 12 Dateien (Services + AJAX + Views-Trennung) |
| 1.0.3 | 🔵 docs | Core | Core-Bereich vollständig dokumentiert |
| 1.0.2 | 🟡 refactor | Services | Prefix-Property + hardkodierte Tabellennamen eliminiert |
| 1.0.1 | 🟠 perf | Core | `createTables()` Performance Guards in Database + SubscriptionManager |
| 1.0.0 | 🔴 fix | Admin | Konsistenz + Performance-Fixes im Admin-Bereich |

---

### v0.9.x — Januar 2026 · Member-Bereich & Admin-Neugestaltung

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 0.9.9 | 🔴 fix | Admin | Kritische Sicherheits-Fixes – groups.php, subscriptions.php, theme-editor.php |
| 0.9.8 | 🔵 docs | Admin | README.md aktualisiert und ADMIN-FILESTRUCTURE.md zur vollständigen Dokumentation erstellt |
| 0.9.7 | 🔴 fix | Subscriptions | Redundante statusBadges in subscription-view entfernt |
| 0.9.6 | 🔴 fix | Member | Critical Bug Fixes: Method-Visibility, Config-Loading, XSS, Escaping |
| 0.9.5 | 🟢 feat | Member | Member-Profil, Security, Subscription und Datenschutz-Views und Controller hinzugefügt |
| 0.9.4 | 🟢 feat | Subscriptions | Subscription-Management Admin-Seite mit Plan-Erstellung und Zuweisung |
| 0.9.3 | 🟢 feat | Admin | Updates-, Backup- und Tracking-Services hinzugefügt |
| 0.9.2 | 🟢 feat | Admin | Backup-Management-Seite mit Backup-Funktionalitäten implementiert |
| 0.9.1 | 🟢 feat | Admin | Komplett neuer Admin-Bereich – Modern & Friendly |
| 0.9.0 | 🔴 fix | Assets | CSS/JS-Pfade auf absolute Server-Root-Pfade geändert + Test-Datei |

---

### v0.8.x — Januar 2026 · Sicherheits-Patches & Dashboard

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 0.8.9 | 🔴 fix | Admin | Admin-CSS- und JS-Pfade korrigiert (global → admin/assets) |
| 0.8.8 | 🟢 feat | Dashboard | Dashboard mit moderner AJAX-Architektur ersetzt; DashboardService-Datenbankfehler behoben |
| 0.8.7 | 🟢 feat | Cache | Umfassende Cache-Clearing-Funktion implementiert |
| 0.8.6 | 🔴 fix | Services | Service-Fehler behoben; fehlende `use CMS\Security` in landing-get.php ergänzt |
| 0.8.5 | 🔴 fix | Settings | Settings-Tabelle Spaltennamen korrigiert (`setting_key/value` → `option_name/value`) |
| 0.8.4 | 🟢 feat | Database | Automatische DB-Bereinigung bei Neuinstallation implementiert |
| 0.8.3 | 🔴 fix | Install | install-schema.php HTTP 500 durch falsche Database-Methoden behoben |
| 0.8.2 | 🔴 fix | Namespaces | Namespace-Regressionen in Services und Datenbank-Schema behoben |
| 0.8.1 | 🔴 fix | Core | Session-Management in autoload.php zentralisiert; `Auth::getCurrentUser()` hinzugefügt |
| **0.8.0** | 🔴 **fix** | **Core** | **KRITISCH: 7 Sicherheitsprobleme behoben** |

---

### v0.7.x — Januar 2026 · Sicherheit, E-Mail & PWA

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 0.7.8 | 🔴 fix | Security | CORS-Konfiguration und SEO-External-Code-Embedding gesichert |
| 0.7.7 | 🔴 fix | Security | 5 kritische Sicherheitsprobleme im Core-System behoben |
| 0.7.6 | 🟢 feat | Admin | Phase 1.1: Admin-Core-Migration – Admin.php mit erweiterten Features erstellt |
| 0.7.5 | 🟢 feat | Core | Phase 1.3: Job-Queue-System mit Scheduling, Worker-Management und Monitoring |
| 0.7.4 | 🟢 feat | Email | Phase 1.2: E-Mail-System mit Templates, Queue und Tracking vollständig implementiert |
| 0.7.3 | 🔴 fix | Security | SQL-Injection- und Credential-Exposure-Schwachstellen behoben |
| 0.7.2 | 🟢 feat | Cache | LiteSpeed-Cache-Integration und Performance-Optimierungen implementiert |
| 0.7.1 | 🟢 feat | PWA | Phase 1.5 PWA-Support implementiert – Phase 1 Implementierung 100 % abgeschlossen |
| **0.7.0** | 🟢 feat | Security | Phase 1.4 Sicherheits-Enhancements: MFA, OAuth, Social Login, Intrusion Detection, GDPR |

---

### v0.6.x — Januar 2026 · Bugfixes, Bookings & Multi-Tenancy

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 0.6.9 | 🟢 feat | Core | Multi-Tenancy-Foundation (Tenant.php) implementiert – Phase 2 Core-Start |
| 0.6.8 | 🟠 perf | Bookings | Datenbankindex-Optimierung für 75 % Abfrage-Performance-Verbesserung |
| 0.6.7 | 🟢 feat | Bookings | Konflikt-Erkennung mit Pufferzeiten, Urlaubssperrung und Concurrency-Limits erweitert |
| 0.6.6 | 🔴 fix | Admin | Admin-Panel Plugin-Management gefixt; Subdirectory-Support hinzugefügt |
| 0.6.5 | 🔴 fix | Database | Merge-Konflikte, Schema-Doppelpräfix und Konfig-Struktur behoben |
| 0.6.4 | 🔴 fix | Core | Fehlende Helper-Funktionen ergänzt: `has_action`, `has_filter`, `trailingslashit` |
| 0.6.3 | 🔴 fix | Database | Schema.sql bereinigt: Plugin-Tabellen entfernt, cms_users-Felder korrigiert |
| 0.6.2 | 🟡 refactor | Core | Modulare Architektur: index.php von 258 auf 72 Zeilen reduziert |
| 0.6.1 | 🔴 fix | Database | Datenbank-Prefix-Doppelpräfix-Bugs im gesamten Codebase behoben |
| 0.6.0 | 🔴 fix | Core | Kritische Routing- und Datenbank-Prefix-Bugs im CMS-Core behoben |

---

### v0.5.x — Januar 2026 · CMSv2 Initial · Interner Release

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| 0.5.9 | 🔵 docs | Docs | ADMIN-GUIDE.md in `doc/admin/`-Unterverzeichnis reorganisiert |
| 0.5.8 | 🔵 docs | Admin | Umfassende ADMIN-GUIDE.md + Security/Performance-Admin-Seiten erstellt |
| 0.5.7 | 🟢 feat | Core | PluginManager: getActivePlugins angepasst; getCurrentTheme; time_ago erweitert; clear-cache.php |
| 0.5.6 | 🟢 feat | Admin | System-Status-Seite hinzugefügt; User-Erstellungsformular verbessert |
| 0.5.5 | 🟢 feat | Admin | User-Management mit CRUD-Operationen, Rollenverwaltung und Bulk-Aktionen |
| 0.5.4 | 🟢 feat | Admin | Vollständiger Admin-Bereich implementiert |
| 0.5.3 | 🔵 docs | Docs | Vollständige Dokumentation für CMS365-Phasen und Security-Audit hinzugefügt |
| 0.5.2 | 🟢 feat | Security | Security-Layer implementiert; 5 kritische Sicherheitsprobleme im Core behoben |
| 0.5.1 | 🟢 feat | Core | Install.php, Updater.php und erweitertes index.php mit Full-Routing hinzugefügt |
| **0.5.0** | 🟢 feat | **Core** | **CMSv2 Initial: Core-System mit Hooks, Datenbank, Auth und Routing implementiert** |

---

> *CMSv1 (0.1.xx – 0.4.99) – Interne Entwicklungsphase 2024-2025, nicht öffentlich verfügbar*
