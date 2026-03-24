# ToDo Audit 23.03.2026 – 365CMS Vollprüfung

> **Scope:** `365CMS.DE/CMS`
> **Datum:** `23.03.2026`
> **Basis:** Code-Inventar, Live-Site-Stichprobe `https://phinit.de/`, Audit `AUDIT_23032026_CMS_PHINIT-LIVE.md`
> **Wichtige Scope-Regel:** `CMS/vendor/` und `CMS/themes/` wurden für diese Vollprüfung **bewusst ausgeschlossen**; aus `CMS/assets/` wurden nur `assets/css/` und `assets/js/` berücksichtigt.

## Prüfabdeckung

Die Vollprüfung wurde auf den kompletten First-Party-Dateibaum von `CMS/` erweitert, **ohne** `CMS/vendor/` und `CMS/themes/`; aus `CMS/assets/` wurden nur `assets/css/` und `assets/js/` einbezogen.

- **Geprüftes Gesamtinventar:** `419` Dateien
- **Geprüfte Bereiche:** Root-Entrypoints, `assets/css/`, `assets/js/`, `admin/`, `config/`, `core/`, `includes/`, `lang/`, `logs/`, `member/`, `plugins/cms-importer/`, `uploads/`
- **Nicht Teil dieses Audits:** Drittanbieterpakete unter `CMS/vendor/`, Theme-Dateien unter `CMS/themes/`, sonstige Asset-Bundles außerhalb von `assets/css/` und `assets/js/`

### Abgehakte Prüfliste

- [x] Root-Entrypoints geprüft (`.htaccess`, `config.php`, `index.php`, `install.php`, `cron.php`, `orders.php`, `update.json`)
- [x] `assets/css/` vollständig geprüft
- [x] `assets/js/` vollständig geprüft
- [x] `config/` vollständig geprüft
- [x] `includes/` vollständig geprüft
- [x] `lang/` vollständig geprüft
- [x] `admin/` vollständig geprüft
- [x] `core/` vollständig geprüft
- [x] `member/` vollständig geprüft
- [x] `plugins/cms-importer/` vollständig geprüft
- [x] `logs/` und `uploads/` als Betriebsartefakte geprüft
- [x] Live-Site-Stichprobe gegen `https://phinit.de/` erneut verifiziert (`/`, `/en`, `/login`, `/register`, `/forgot-password`, `/feed`, `/impressum`, `/datenschutz`, `/contact/kontakt`, `/blog?page=1`)
- [x] `FILEINVENTAR.md` gegen den realen Scope abgeglichen und auf Scope-Drift geprüft

## AUDIT-ToDo Abarbeitung

- [x] **Step #1 / A-01 – Installer härten** → `install.php` blockiert bestehende Installationen jetzt per `config/install.lock` und Admin-Guard; der Reinstall-Pfad füllt das DB-Passwort nicht mehr vor.
- [x] **Step #2 / A-02 – Versionsstand vereinheitlichen** → `CMS\Version` ist wieder die zentrale Quelle; Runtime, Installer-Konfigwriter, `CMS/update.json` sowie README-/Changelog-Badges laufen jetzt konsistent auf Release `2.6.2`.
- [x] **Step #3 / A-03 – Importer-Remote-Fetch auf Core-HTTP-Härtung umstellen** → Der Importer lädt Remote-Bilder jetzt über `CMS\Http\Client` mit TLS-Prüfung, SSRF-Schutz sowie Größen- und Image-Content-Type-Limits.
- [x] **Step #4 / A-04 – Inventar- und Scope-Dokumentation dauerhaft vereinheitlichen** → `FILEINVENTAR.md` bleibt die kanonische Quelle; Audit und ToDo referenzieren jetzt nur noch den verifizierten 419-Dateien-Scope statt konkurrierender eingebetteter Inventarstände.
- [x] **Step #5 / A-05 – Debug-Logziel aus dem Release-Baum ziehen** → `LOG_PATH`/`CMS_ERROR_LOG` zeigen jetzt standardmäßig auf ein externes Logverzeichnis (`../var/logs` bzw. Fallback `sys_get_temp_dir()`); `SystemService` liest und leert denselben aktiven Pfad.
- [x] **Step #6 / A-06 – ZIP-Entry-Validierung vor `extractTo()` ergänzen** → Der GitHub-Doku-Sync validiert ZIP-Einträge jetzt vor `extractTo()` auf Traversals, absolute Pfade, NUL-/Steuerzeichen sowie leere oder punktbasierte Segmente.
- [x] **Step #7 / A-07 – Integritätsprüfung für Dokumentationsdownloads ergänzen und /DOC liegt immer dort wo CMS direkt liegt** → Der Doku-Sync akzeptiert jetzt nur noch ein freigegebenes `/DOC`-Bundle per Tree-SHA-256 + Dateianzahl und erzwingt den Zielpfad strikt auf `../DOC` direkt neben `/CMS`.
- [x] **Step #8 / A-08 – DNS-Fallback im SSRF-Schutz härten** → HTTP-Client und UpdateService blockieren ungelöste Remote-Hosts jetzt standardmäßig, versuchen vorab eine echte IP-Auflösung per DNS/GetHostByName und erlauben ungelöste Hosts nur noch per explizitem Opt-in im HTTP-Client.
- [x] **Step #9 / A-09 – `session.cookie_secure` an echtes HTTPS koppeln** → `Security`, `index.php` und `cron.php` setzen `session.cookie_secure` jetzt nur noch bei tatsächlich erkanntem HTTPS bzw. Proxy-HTTPS; CLI/HTTP-Staging bleiben funktionsfähig.
- [x] **Step #10 / A-10 – Update-Installationen atomar machen** → `UpdateService` entpackt Update-ZIPs jetzt erst in ein benachbartes Staging-Verzeichnis und übernimmt sie anschließend per atomarem Directory-Swap bzw. rollback-fähigem Inhalts-Swap ins Live-Ziel.
- [x] **Step #11 / A-11 – HTTPS-/HSTS-Linie vereinheitlichen** → Die ausgelieferte HTTPS-Strategie ist jetzt klar auf Redirects am Reverse-Proxy/Webserver festgelegt; `.htaccess`, `Security` und Diagnose folgen derselben HTTPS-/HSTS-Linie ohne halb-aktive Kommentar-Redirects.
- [x] **Step #12 / A-12 – Installer-Monolith aufspalten** → `install.php` bootstrapped den Ablauf jetzt nur noch; Flow-/Update-Logik liegt in `install/InstallerController.php`, Setup-/DB-/Config-Logik in `install/InstallerService.php` und die HTML-Schritte in `install/views/*.php`.
- [x] **Step #13 / A-13 – `includes/functions.php` thematisch splitten** → `includes/functions.php` lädt die globalen Helfer jetzt nur noch als Bootstrap; Escaping, Optionen/Runtime, Redirect/Auth, Rollen, Admin-Menüs, Übersetzungen, WP-Kompatibilität und Mail liegen getrennt unter `includes/functions/*.php`.
- [ ] **Step #14 / A-14 – Importer-Monolith weiter zerlegen**
- [ ] **Step #15 / A-15 – große Routing-/Admin-Hotspots verkleinern**
- [ ] **Step #16 / A-16 – Media-Delivery um Range-/Streaming ergänzen**
- [ ] **Step #17 / A-17 – große Admin-Views modularisieren**
- [ ] **Step #18 / A-18 – Upload-Beispiel-/Betriebsdaten sauber trennen**
- [ ] **Step #19 / A-19 – Login zusätzlich an ein kurzlebiges Device-Cookie binden**

## Funde und Verbesserungen

## ZUSÄTZLICH

Die folgenden Punkte sind aus den alten Audit-Berichten, Restthemen und Betriebsanforderungen abgeleitet worden, aber **noch nicht produktiv eingebaut**:

- **CrUX-/PageSpeed-Vergleichsdaten im Performance-Center** – externe Felddaten zusätzlich zu den internen CWV-Messwerten anzeigen
- **Diagnose 2.0 mit Bundle-/Registry-Historie** – sichtbare Bundle-/Registry-Statusdaten um Trends, Änderungsverlauf und aktive Warnhistorie ergänzen
- **Weitere Service-Splits für Rest-Hotspots** – insbesondere verbleibende Theme-/Media-Restblöcke
- **Proxy-/CDN-Realfall-Prüfung im Betrieb** – Header, Vary-Verhalten und Cache-Reaktionen auf echter Infrastruktur gezielt gegenmessen

- **Login Prüfung über Cookie** – Der Login soll zusätzlich an ein signiertes Device-Cookie mit maximal 2 Stunden Laufzeit gebunden werden; Inkognito-/Fremdsystem-Sessions ohne dieses Cookie dürfen keinen bestehenden Login übernehmen und Logout muss das Cookie löschen.


### Kritisch / hoch

| ID | Priorität | Bereich / Dateien | Befund | Verbesserung |
|----|-----------|-------------------|--------|--------------|
| A-01 | hoch | `CMS/install.php`, `CMS/config/app.php`, `CMS/core/Version.php` | **Installer bleibt hochsensibel öffentlich nutzbar.** Bei vorhandener Installation bietet `install.php` weiterhin Update-/Neuinstallationspfade ohne vorgeschaltete Admin-Authentifizierung. In Schritt 2 wird das Datenbank-Passwort sogar wieder als Formularwert vorbelegt. Wenn die Datei produktiv liegen bleibt, ist das ein massiver Angriffsvektor. | Installer nach erfolgreicher Installation hart deaktivieren (Install-Lock + Admin-Guard), Passwort nie vorbefüllen, öffentliche Ausführung außerhalb des Initial-Setups blockieren. |
| A-02 | hoch | `CMS/config/app.php`, `CMS/core/Version.php`, `CMS/install.php`, `README.md`, `Changelog.md` | **Versionsdrift bleibt bestehen.** Runtime und Installer laufen weiter auf `2.6.0`, während Release-Doku und Git-Historie bereits darüber hinausgehen. | Eine zentrale Versionsquelle definieren und daraus Installer, Runtime, README-Badge, Update-Metadaten und Changelog ableiten. |
| A-03 | hoch | `CMS/plugins/cms-importer/includes/class-importer.php` | **Remote-Dateidownload im Importer umgeht zentrale Härtung.** `fetch_remote_file()` deaktiviert TLS-Prüfung (`CURLOPT_SSL_VERIFYPEER => false`) und nutzt keine Host-Allowlist / keinen SSRF-Guard. | Importer auf `CMS\Http\Client` umstellen, TLS-Verifikation erzwingen, Host-Disziplin und Größen-/Content-Type-Limits zentral wiederverwenden. |

### Mittel

| ID | Priorität | Bereich / Dateien | Befund | Verbesserung |
|----|-----------|-------------------|--------|--------------|
| A-04 | mittel | `DOC/audit/FILEINVENTAR.md`, `DOC/audit/AUDIT_23032026_CMS_PHINIT-LIVE.md`, `DOC/audit/ToDo_Audit_23032026.md` | **Inventar- und Scope-Drift zwischen den Audit-Dokumenten.** Frühere Stände arbeiteten parallel mit `~290`, `444` und inzwischen verifiziert `419` Dateien. Außerdem liefen eingebettete Inventar-Kopien und der tatsächliche Prüfscope auseinander. | `FILEINVENTAR.md` als kanonische Scope-Liste führen und Audit/ToDo nur noch daraus bzw. aus derselben verifizierten Zählung ableiten. |
| A-05 | mittel | `CMS/core/Security.php`, `CMS/config/app.php`, `CMS/logs/.gitignore`, `CMS/logs/.htaccess` | **Debug-Logging ist weiterhin auf den Release-Baum ausgerichtet.** Aktuell enthält `CMS/logs/` nur Schutz-/Platzhalterdateien, aber bei aktivem Debug-Modus wird weiterhin `ABSPATH . 'logs/error.log'` als Ziel gesetzt. | Logs dauerhaft außerhalb des Release-Artefakts schreiben oder den Zielpfad strikt nach Umgebung kapseln; `CMS/logs/` nur als Schutz-/Placeholder-Ordner belassen. |
| A-06 | mittel | `CMS/admin/modules/system/DocumentationGithubZipSync.php` | **ZIP-Archive werden ohne sichtbare Entry-Validierung entpackt.** Bei kompromittierter Quelle fehlt vor `extractTo()` eine zusätzliche Pfadprüfung. | Vor dem Entpacken alle ZIP-Einträge auf `../`, absolute Pfade, NUL-Bytes und unerwartete Dateinamen validieren. |
| A-07 | mittel | `CMS/admin/modules/system/DocumentationSyncDownloader.php` | Dokumentationsdownloads sind hostgebunden, aber **nicht integritätsgeprüft**. Vertrauen liegt vollständig auf Transport + GitHub-Quelle. | Optional SHA-256/Signatur für freigegebene Doku-Bundles ergänzen und im UI sichtbar ausweisen. |
| A-08 | mittel | `CMS/core/Http/Client.php` | Der SSRF-Schutz ist gut, aber bei **fehlender DNS-Auflösung wird aktuell „allow“ statt „deny“** verwendet. Das ist betrieblich freundlich, sicherheitlich jedoch weich. | Fallback-Strategie härten: bei sicherheitskritischen Fetches DNS-Fehler blockieren oder per explizitem Policy-Flag erzwingen. |
| A-09 | mittel | `CMS/core/Security.php`, `CMS/index.php`, `CMS/cron.php` | `session.cookie_secure` wird **immer** auf `1` gesetzt, auch außerhalb echter HTTPS-Läufe. Das kann lokale HTTP-/Staging-Setups unnötig brechen. | `cookie_secure` nur setzen, wenn `isHttpsRequest()` tatsächlich `true` liefert, oder Verhalten über eine klare Config kapseln. |
| A-10 | mittel | `CMS/core/Services/UpdateService.php` | Updates werden geprüft und ZIP-Einträge validiert, aber **nicht atomar** installiert. Direkte Extraktion ins Ziel kann bei Abbruch inkonsistente Zustände hinterlassen. | In ein Staging-Verzeichnis entpacken, validieren, dann atomar umschalten bzw. Rollback ermöglichen. |
| A-11 | mittel | `CMS/.htaccess`, `CMS/core/Security.php` | Die HSTS-/HTTPS-Linie ist nicht komplett einheitlich: `.htaccess` enthält die HTTPS-Weiterleitung weiterhin auskommentiert, während Security-Header andernorts streng gesetzt werden. | HTTPS-Strategie zentral dokumentieren und entscheiden, ob Redirect im Webserver, Proxy oder Core verbindlich aktiv sein soll. |
| A-12 | mittel | `CMS/install.php` | Der Installer ist weiterhin ein **großer Monolith** (UI, Config, DB, Schema, Cleanup, Success-Flow in einer Datei). | In Controller, Setup-Service und Template-Views aufteilen, damit Sicherheits- und Updatepfade gezielt testbar werden. |
| A-13 | mittel | `CMS/includes/functions.php` | Die globale Helper-Datei ist ein **Wartungs-Hotspot** und mischt Escaping, Routing, Rollen, WP-Kompatibilität, Assets und Redirects. | In thematische Helper-Dateien / Namespaces splitten und nur noch gezielt bootstrappen. |
| A-14 | mittel | `CMS/plugins/cms-importer/includes/class-importer.php`, `CMS/plugins/cms-importer/includes/class-admin.php`, `CMS/plugins/cms-importer/includes/class-xml-parser.php` | Das Importer-Paket ist funktional stark, aber inzwischen ein **eigener Monolith im Monolithen**. | Parser, Mapping, Media-Download, Preview, Cleanup und Reporting in eigenständige Services zerlegen; Tests ergänzen. |
| A-15 | mittel | `CMS/core/Routing/ThemeRouter.php`, `CMS/admin/modules/posts/PostsModule.php`, `CMS/admin/modules/hub/HubTemplateProfileManager.php` | Mehrere Kernpfade sind inzwischen sehr groß und regressionsanfällig. | Pro Bereich kleinere Action-/Repository-/ViewModel-Schichten einziehen. |

### Niedrig bis mittel

| ID | Priorität | Bereich / Dateien | Befund | Verbesserung |
|----|-----------|-------------------|--------|--------------|
| A-16 | niedrig-mittel | `CMS/core/Services/MediaDeliveryService.php` | Medienauslieferung ist sicher aufgebaut, aber **ohne Range-/Streaming-Optimierung** für größere Dateien. | Range-Requests / effizientere Binärausgabe für größere Downloads ergänzen. |
| A-17 | niedrig-mittel | `CMS/admin/views/*` | Viele Views/Partials sind groß und HTML-lastig; künftige Änderungen werden dadurch diff-lastiger. | View-Komponenten stärker modularisieren und gemeinsame Teilbausteine extrahieren. |
| A-18 | niedrig-mittel | `CMS/uploads/SidebarRahmenThumnail_V5_CopilotLizenzen.png`, `CMS/uploads/.htaccess` | Im Audit-Scope liegt mindestens eine echte Upload-Datei bereits im Repo-/Laufzeitbaum. Das ist nicht per se falsch, erschwert aber die Trennung von Code und Betriebsdaten. | Deploy-Artefakte und Beispiel-/Betriebsuploads sauber trennen; produktive Uploads nicht versionieren. |
| A-19 | mittel | `CMS/core/Auth.php`, `CMS/core/Routing/PublicRouter.php`, `CMS/core/Security.php` | Logins hängen aktuell im Wesentlichen an Session + IP-/Rate-Limit-Kontext, aber nicht zusätzlich an einem kurzlebigen Device-Cookie. Dadurch kann ein Login in einem fremden Browser-Kontext leichter übernommen werden, solange nur die Sessionlage passt. | Ein signiertes Login-/Device-Cookie mit maximal 2 Stunden TTL ergänzen, bei jedem geschützten Request mitprüfen und beim Logout hart löschen. |

## Positiv bestätigt

- Die zentrale Sicherheitslinie in `CMS/core/Security.php`, `CMS/core/Services/UpdateService.php`, `CMS/core/Services/MediaDeliveryService.php` und `CMS/core/Http/Client.php` ist grundsätzlich tragfähig.
- Die Live-Site `https://phinit.de/` wirkt öffentlich konsistent; der aktuelle Druck liegt eher in Betriebsdisziplin, Release-Sauberkeit und Wartbarkeit als in einem sofort sichtbaren Frontend-Ausfall.
- Die stärksten Risiken sitzen aktuell in **Installer-/Importer-Rändern**, **Release-Metadaten**, **Inventar-/Dokumentationsdrift** und einigen **großen Wartungshotspots**.

## Empfohlene Reihenfolge

1. **Installer härten oder nach Installation vollständig deaktivieren**
2. **Versionsstand vereinheitlichen** (`CMS_VERSION`, `Version::CURRENT`, Installer, README, Release-Flow)
3. **Importer-Remote-Fetch auf den zentralen HTTP-Client umstellen**
4. **Inventar- und Scope-Dokumentation vereinheitlichen**
5. **Log-/Betriebsartefakte aus Deployments entfernen**
6. **ZIP-/Download-Pfade härten** (Docs + Updates)
7. **Monolithen aufspalten** (`install.php`, `includes/functions.php`, Importer, große Admin-/Routing-Module)
---

## Kanonische Inventarquelle

Die vollständige Dateiliste und Bereichszählung wird ausschließlich in `DOC/audit/FILEINVENTAR.md` geführt. Dieses ToDo referenziert nur noch den verifizierten Prüfscope von `419` Dateien.

## Abschluss

Die erweiterte Vollprüfung bestätigt erneut: 365CMS ist aktuell eher ein Fall für **gezielten Struktur- und Betriebs-Feinschliff** als für akute Feuerwehreinsätze. Die drei schärfsten ToDos sind jetzt glasklar:

- `install.php` darf produktiv nicht als frei erreichbarer Setup-Weg stehen bleiben.
- Release-/Versionsmetadaten müssen endlich dieselbe Wahrheit erzählen.
- Laufzeit-Logs und ähnliche Betriebsartefakte gehören aus dem Release-Baum heraus.
