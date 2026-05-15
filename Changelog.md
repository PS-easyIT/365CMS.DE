﻿**Version:** 3.0.2

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
