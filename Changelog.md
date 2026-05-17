**Version:** 3.0.11

# 365CMS Changelog

## 📋 Legende

| Symbol | Typ | Bedeutung |
|--------|-----|-----------|
| 🟢 | `feat` | Neues Feature |
| 🔴 | `fix` | Bugfix |
| 🟡 | `refactor` | Code-Umbau ohne Funktionsänderung |
| 🟠 | `perf` | Performance-Verbesserung |
| 🔵 | `docs` | Dokumentation |
| ⬜ | `chore` | Wartungsarbeit / Release |
| 🛡️ | `security` | Sicherheits- und Audit-Härtung |

---

## 📜 Aktuelle Versionshistorie ab 3.0.0

> Die vollständige historische 2.x-Historie wurde in [`Changelog_old.md`](Changelog_old.md) archiviert.

### v3.0.11 — 16. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.11** | 🔴 fix | Admin/Performance & Page-Schema | **`CMS/admin/views/performance/settings.php`, `CMS/admin/views/performance/media.php`, `CMS/core/PageManager.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` stabilisieren Performance-Adminseiten und Schema-Kompatibilitätsprüfungen.** Medien-Cache-TTL-Optionen werden vor `htmlspecialchars()` und beim Selected-Vergleich explizit als String behandelt, damit numerische PHP-Array-Keys keinen TypeError auslösen. Page-Schema-Prüfungen schließen `SHOW COLUMNS`-Cursor nun vor anschließenden `ALTER TABLE`-Queries und vermeiden dadurch unbuffered-query-Konflikte. |
| **3.0.11** | 🔴 fix | Admin/Editor-Layout (Seiten & Beiträge) | **`CMS/assets/css/admin.css`, `README.md` und `Changelog.md` korrigieren den Desktop-Layoutflow im Seiten-/Beitragseditor.** Der bisherige Grid-Flow koppelte die Sidebar-Zeilenhöhe an den Editor-Track, wodurch der Editor sichtbar zu weit unten startete und zwischen Sidebar-Panels große Leerzonen entstanden. Das Desktop-Layout nutzt jetzt einen stabilen Zwei-Spalten-Flow mit oben bündigem Editorstart und kompakter, konsistenter Sidebar-Stapelung; Mobile-Breakpoints bleiben unverändert. |

### v3.0.10 — 16. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.10** | 🟢 feat | Admin/Medien Uploadpfad | **`CMS/core/Services/MediaService.php`, `CMS/core/Services/Media/UploadHandler.php`, `CMS/admin/modules/media/MediaModule.php`, `CMS/admin/views/media/settings.php`, `CMS/DOC/admin/media/MEDIA.md`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` machen den Medien-Upload-Zielmodus explizit steuerbar.** Standardmäßig werden Uploads jetzt in den aktuell geöffneten Medienordner geschrieben. Wird die Option „Datumsordner Jahr/Monat/Tag anlegen“ aktiviert, erzeugt der verwaltete Uploadpfad darunter automatisch `YYYY/MM/DD` und verhindert eine doppelte Datumsverschachtelung, wenn man bereits in einem Datumsordner steht. |

### v3.0.9 — 16. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.9** | 🔴 fix | Medien / Cache & WebP | **`CMS/core/Services/MediaService.php`, `CMS/admin/modules/seo/PerformanceModule.php`, `CMS/admin/views/performance/settings.php`, `CMS/admin/views/performance/media.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` stellen WebP-Begleitdateien und sichtbare Medien-Cache-TTL wieder her.** Originalerhaltende Uploads verändern die hochgeladene Datei weiterhin nicht, erzeugen bei aktivierter WebP-Option aber wieder kleinere `.webp`-Begleitdateien. Die Medien-Optimierung zeigt die Browser-Cache-TTL jetzt direkt an und synchronisiert die Performance-TTL zusätzlich in die Upload-`.htaccess`, damit PHINITs direkte `/uploads`-Bilder dieselbe Cache-Policy wie `/media-file` erhalten. |

### v3.0.8 — 16. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.8** | 🔴 fix | Medien / Originaldateien | **`CMS/core/Services/MediaService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` bewahren Medienbibliothek-, Editor- und Beitragsbild-Uploads als echte Originaldateien.** Originalerhaltende Bild-Uploads und Replace-in-place überspringen jetzt das verlustbehaftete Re-Encoding, Maximalmaß-Resize sowie automatische WebP-/Thumbnail-Erzeugung, damit die gespeicherte Upload-Datei nicht größer oder anders codiert wird als die hochgeladene Datei und keine zusätzlichen Derivate entstehen. |

### v3.0.7 — 16. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.7** | 🔴 fix | Editor/Medien | **`CMS/core/Services/MediaService.php`, `CMS/core/Services/EditorJs/EditorJsUploadService.php`, `CMS/admin/views/partials/featured-image-picker.php`, `CMS/core/Services/EditorJs/EditorJsImageLibraryService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` bewahren Editor-Bilduploads als echte Originaldateien und erweitern den Beitragsbild-Picker.** Bild-Uploads aus Beiträgen und Seiten überspringen jetzt das verlustbehaftete Re-Encoding des gespeicherten Originals; optionale WebP-Dateien bleiben nur Derivate. Der Beitragsbild-Picker nutzt `ArtikelRahmen` als breiteren Standardpräfix, akzeptiert `ArtikelRahmen*` als Prefix-Syntax und kappt explizit gefilterte Treffer nicht mehr bei 250 Einträgen. |

### v3.0.6 — 16. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.6** | 🟢 feat | Admin/Medien | **`CMS/admin/media.php`, `CMS/admin/modules/media/MediaModule.php`, `CMS/admin/views/media/featured.php`, `CMS/assets/js/admin-media-integrations.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `CMS/DOC/admin/media/README.md`, `CMS/DOC/admin/media/MEDIA.md`, `README.md` und `Changelog.md` erweitern den Beitrags-&-Site-Medien-Replace-Flow um Mehrfach-Ersetzung.** Admins können in mehreren Zeilen per „Durchsuchen“ Ersatzbilder vorbereiten; sobald bei einer Zeile „Bild ersetzen“ geklickt wird, sammelt die Oberfläche alle vorbereiteten Dateien in einen gemeinsamen CSRF-geschützten Multipart-POST. Serverseitig verarbeitet die neue `replace_items`-Aktion die Paare aus Zielpfad und Datei weiter über denselben validierten Replace-in-place-Vertrag wie die Einzel-Ersetzung. |

### v3.0.5 — 16. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.5** | 🟠 perf | Core/Performance Mediencache | **`CMS/admin/modules/seo/PerformanceModule.php`, `CMS/admin/views/performance/settings.php`, `CMS/core/Services/MediaDeliveryService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `CMS/DOC/admin/performance/PERFORMANCE.md`, `README.md` und `Changelog.md` ergänzen die Bildauslieferungs-Cache-TTL als feste Performance-Auswahl.** Admins können für öffentlich ausgelieferte Medien jetzt 3, 7 oder 31 Tage wählen; 7 Tage ist Standard und Fallback für fehlende oder alte Werte. Die `/media-file`-Delivery nutzt diese TTL für öffentliche Bilder und liefert dadurch Lighthouse-freundliche `Cache-Control`-/`Expires`-Header, während deaktiviertes Browser-Caching weiterhin im Revalidierungsmodus bleibt. |

### v3.0.4 — 16. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.4** | 🔴 fix | Admin/Medien | **`CMS/core/Services/MediaService.php`, `CMS/core/Services/EditorJs/EditorJsUploadService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `CMS/DOC/admin/media/README.md`, `CMS/DOC/admin/media/MEDIA.md`, `README.md` und `Changelog.md` verhindern doppelte physische Ablagen identischer Beitrags- und Seitenbilder.** Der Featured-Image-Upload prüft vorhandene permanente `articles/`- und `pages/`-Titelbilder jetzt größen- und SHA-256-basiert, überspringt temporäre Draft-Pfade und gibt bei identischem Inhalt direkt die bestehende Medienreferenz zurück. Dadurch können mehrere Beiträge oder Seiten dasselbe ausgewählte Titelbild teilen, ohne neue Kopien wie `ArtikelRahmen_slug-1.jpg` zu erzeugen. |

### v3.0.3 — 15. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.3** | 🟢 feat | Admin/Medien | **`CMS/admin/media.php`, `CMS/admin/modules/media/MediaModule.php`, `CMS/admin/views/media/featured.php`, `CMS/admin/views/media/check.php`, `CMS/admin/partials/sidebar.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `CMS/DOC/admin/media/README.md`, `CMS/DOC/admin/media/MEDIA.md`, `README.md` und `Changelog.md` verschieben den Featured-Image-Konsistenz-Check in den neuen Unterpunkt `Medien Check`.** Dadurch bleibt `Beitrags & Site Medien` auf die tatsächlich verwendeten Featured Images und den Replace-in-place-Flow fokussiert, während die read-only Prüfliste für fehlende oder defekte Zuordnungen separat gefiltert und direkt aus dem Medienmenü erreichbar ist. |

### v3.0.2 — 15. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.2** | 🔴 fix | Admin-Audit / Auth, Media & Security | **`CMS/core/SchemaManager.php`, `CMS/core/Services/MediaDeliveryService.php`, `CMS/core/Services/Media/MediaRepository.php`, `CMS/admin/modules/media/MediaModule.php`, `CMS/admin/modules/security/SecurityAuditModule.php`, `CMS/views/auth/cms-auth.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` beheben die im Live-Admin-Audit gefundenen Core-Fehler.** Das Runtime-Schema erstellt die für Passwort-Resets benötigte Tabelle `password_resets` auch auf bestehenden Installationen, Admin-Medienlinks zeigen Originaldateien über den kontrollierten `/media-file`-Endpunkt statt potenziell blockierter Direkt-Upload-URLs, versteckte Punkt-Dateien wie `.htaccess` erscheinen nicht mehr als normale Medien, das HSTS-Audit bewertet vorhandene Apache-/Proxy-Fallback-Header korrekt und die CMS-Loginpage trennt Passwort-Label und Passwort-vergessen-Link für Screenreader sauberer. |

### v3.0.1 — 15. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.1** | 🔴 fix | Public HTML Cache / Auth-Header | **`CMS/core/Router.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `CMS/DOC/admin/performance/PERFORMANCE.md` und `Changelog.md` verhindern das Ausliefern gecachter Member-Header an anonyme Besucher.** Öffentliche GET-/HEAD-Responses werden jetzt auf echte Auth-, MFA- oder Device-Session-Signale geprüft. Sobald personalisierte Auth-State-Daten vorhanden sind, sendet der Router private `no-store`-Header und überspringt öffentliche 304-Validatoren. Dadurch können öffentliche Seiten weiterhin gecacht werden, aber angemeldete Varianten mit Member-Bar, Dashboard-Link oder Benachrichtigungsbadge landen nicht mehr in Public-/LiteSpeed-/Proxy-Caches. |

### v3.0.0 — 14. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.0** | 🛡️ security | Core/Final Audit – Logging, Diagnose & Schema-Härtung | **`CMS/core/Database.php`, `CMS/core/AuditLogger.php`, `CMS/admin/views/partials/flash-alert.php`, `CMS/core/Services/RedirectService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `Changelog.md` und `Changelog_old.md` schließen den finalen Core-only Auditlauf für den 365CMS-Hauptcore ab.** Der Audit fokussierte ausschließlich `365CMS.DE` bzw. `CMS/` und ignoriert externe Theme- und Plugin-Repositories. Als konkrete Nachhärtung redigieren Low-Level-Datenbank- und Audit-Logger nun Inline-Secrets, Kontrollzeichen und überlange Diagnosewerte, geben keine DB-Benutzernamen mehr in Fehlerlogs aus und melden DB-Verbindungsfehler nach außen generischer, während technische Details intern begrenzt bleiben. Das gemeinsame Admin-Flash-Partial redigiert sensible Fehlerreport-Kontexte vor Anzeige und Report-Weitergabe und entfernt den früheren `print_r()`-Fallback aus der Diagnoseausgabe. Der Redirect-Schema-Upgrade-Helfer akzeptiert nur noch die erwarteten internen Tabellen-/Spalten-/Definition-Kombinationen, bevor dynamische DDL ausgeführt wird. |
| **3.0.0** | 🛡️ security | Admin-Shell / Theme-Editor | **`CMS/admin/partials/section-page-shell.php`, `CMS/index.php` und `CMS/themes/cms-default/error.php` verhindern Header-Warnings nach bereits gestarteter Admin-Ausgabe.** Eingebettete Admin-Views wie der Theme-Editor werden jetzt inline abgefangen, sicher protokolliert und mit redigierter Fehlerdetailzeile angezeigt; globale Fehler-Templates setzen Status- und Content-Type-Header nur noch, wenn noch keine Ausgabe begonnen hat. |
| **3.0.0** | 🛡️ security | Zweiter Auditlauf – Fatal-, Installer- und Schema-Logs | **Der erneute Durchlauf hat weitere Low-Level-Logpfade gehärtet.** `CMS/index.php`, `CMS/core/Debug.php`, `CMS/core/Security.php`, `CMS/core/CacheManager.php`, `CMS/install/InstallerService.php` und `CMS/core/SchemaManager.php` redigieren Diagnosemeldungen nun ebenfalls vor dem Schreiben in Error-Logs, Debug-Dateien, Debug-Panel und Fehlerreport-Payloads. Der Bootstrap kürzt und maskiert Fatal-Error- und Stacktrace-Logs, Rate-Limit-/Installer-/Cache-Fehler vermeiden rohe Exception-Texte, und der SchemaManager schreibt das automatisch generierte Erst-Admin-Passwort nicht mehr ins globale Error-Log, sondern verweist nur noch auf die bestehende einmalige Credential-Datei. Zusätzlich validiert `SchemaManager::ensureColumnExists()` Tabellen-, Spalten- und ALTER-Präfixe, bevor interne Runtime-Migrationen ausgeführt werden. |
| **3.0.0** | 🛡️ security | Dritter Auditlauf – Remote-/Archiv- und DOM-Härtung | **Der dritte Durchlauf hat Remote-/Archiv- und Web-Best-Practice-Funde geschlossen.** `CMS/core/PluginManager.php` entpackt hochgeladene Plugin-ZIPs nicht mehr direkt in den Plugin-Root, sondern validiert Pfade, Top-Level-Slug, Hauptdatei, Symlink-Freiheit, Eintragszahl und entpackte Größe vor einem Staging-Extract mit anschließendem Security-Scan und atomarem Move; fehlgeschlagene Extracts räumen ihr temporäres Staging-Verzeichnis wieder auf. `CMS/core/Http/Client.php` blockiert URLs mit eingebetteten Zugangsdaten, validiert Ports, begrenzt Response-Größen während des Downloads, setzt HTTP/HTTPS-Protokollgrenzen und prüft nach dem Request die tatsächlich verbundene IP erneut gegen private/reservierte Netze. `CMS/assets/js/admin-dashboard.js` rendert die zuletzt genutzten Admin-Ziele nun DOM-basiert statt per `innerHTML`-Stringaufbau. |
| **3.0.0** | 🛡️ security | Folgeaudit – Update-/Restore-Archivpfade | **Die priorisierten Remote-/Archiv-Hotspots wurden weiter gekapselt.** `CMS/core/Services/UpdateService.php` akzeptiert Plugin-/Theme-Installationen nun nur noch als direkte Child-Ziele unter den verwalteten Plugin-/Theme-Roots, blockiert Root-Overwrite-Szenarien, prüft Update-ZIPs zusätzlich auf Eintragszahl, Einzel-/Gesamtgröße, Kontrollzeichen, Punktsegmente und Unix-Symlinks und validiert nach dem Extract, dass der komplette Staging-Baum linkfrei innerhalb des Staging-Roots bleibt. Installationsfehler-Kontexte werden vor Logger- und Audit-Ausgabe maskiert, insbesondere bei URL-Query-Secrets. `CMS/core/Services/BackupService.php` nutzt dieselben Archivgrenzen für Restore-ZIPs und validiert entpackte Restore-Staging-Bäume vor dem Move gegen Symlinks und Root-Ausbruch. |
| **3.0.0** | 🛡️ security | Folgeaudit – Shared Editor & AI-Translation | **Der kritische Shared-Editor-Pfad wurde gegen Client- und Server-Randfälle nachgezogen.** `CMS/assets/js/admin-content-editor.js` erzwingt für AI-Translation-Requests nun Same-Origin-Endpunkte, setzt ein clientseitiges Zeitlimit, prüft deklarierte und tatsächliche JSON-Antwortgrößen und verwirft übergroße Antworten ohne sie dauerhaft im UI-State zu halten. `CMS/admin/modules/system/AiEditorJsTranslationModule.php` validiert Editor.js-Payloads vor der AI-Pipeline zusätzlich auf gültiges JSON, maximale Blockanzahl, erlaubte Blocktyp-Metadaten und array-basierte Blockdaten. `CMS/assets/js/admin-seo-editor.js` begrenzt die Liveanalyse von Editor.js-JSON, Blockanzahl und HTML-Fragmenten defensiv, damit große oder manipulierte Inhalte die SEO-Vorschau nicht unnötig blockieren. Damit folgt der Übersetzungspfad enger dem OWASP-ASVS-Fail-Closed-Prinzip und reduziert unnötige Heap-Last bei fehlerhaften oder manipulierten Editor-Daten. |
| **3.0.0** | ⬜ chore | Release-Schnitt & Dokumentation | **Die 2.x-Historie wurde von `Changelog.md` nach `Changelog_old.md` verschoben und eine neue, schlanke `Changelog.md` für Version `3.0.0` angelegt.** Version, Update-Metadaten und README verweisen auf den neuen Major-Release-Stand; die historische Detailspur bleibt weiterhin vollständig über `Changelog_old.md` nachvollziehbar. |
