# Sicherheit

**Bereichsscore:** 73/100

## Kurzfazit
Die Codebasis enthält belegbare Sicherheitsmechanismen (CSRF-Token, CSP/HSTS/COOP/CORP/Permissions-Policy, Upload-MIME-Prüfung, Pfadnormalisierung). Der Score wird vor allem durch die große Angriffsfläche aus Datei-Uploads/Importen, dynamischen SQL-Wartungsoperationen und breiter direkter Superglobal-Nutzung reduziert.

## Score-Begründung
- Startwert 100
- SEC-001: -10 (hoch)
- SEC-002: -8 (hoch)
- SEC-003: -5 (mittel)
- SEC-004: -4 (niedrig)

## Findings-Tabelle
| ID | Modul | Feature | Funktion | Schweregrad | Auswirkung | Fundstelle | Quelle |
|---|---|---|---|---|---:|---|---|
| SEC-001 | Media/Importer | Datei-Upload | Upload/Import | hoch | -10 | CMS\core\Services\FileUploadService.php:59-81, CMS\plugins\cms-importer\includes\class-admin.php:280-333 | Codefund |
| SEC-002 | System | Tabellenwartung | REPAIR/OPTIMIZE | hoch | -8 | CMS\core\Services\SystemService.php:611-617 | Codefund |
| SEC-003 | Admin/Runtime | Request-Verarbeitung | GET/POST/FILES/SERVER | mittel | -6 | Fokus-Scan: 676 direkte Superglobal-Fundstellen in 512 First-Party-Dateien | Heuristik/Codefund |
| SEC-004 | Cron | Web-Cron Token | Token in Query/Header | niedrig | -4 | CMS\cron.php:46-58 | Codefund |

## Umsetzungsschritte

## Umsetzungsstand (2026-06-13)
- ✅ **SEC-001 (Upload/Import-Härtung) umgesetzt**
	- Zentrale Upload-Scan-Erweiterung über Hook `cms_media_upload_scan` (pre-upload) und `cms_media_post_upload_scan` (post-upload).
	- Heuristische Blockaden für riskante Polyglot-/Script-Muster sowie XML-DOCTYPE (`<!DOCTYPE`) in Upload- und Import-Pfaden.
	- Import-Verzeichnis-Härtung (`index.html`, `.htaccess`) plus restriktive Dateirechte (`0640`) für gespeicherte Importdateien.
- ✅ **SEC-002 (dynamische SQL-Wartung) umgesetzt**
	- Strikte Identifier-Validierung (`/^[A-Za-z0-9_]+$/`) + Prefix-Validierung vor Ausführung.
	- Normalisierte, deterministische Ergebnisstruktur inkl. Details und Audit/Logger-Eintrag.
- 🟨 **SEC-003 (Request-Zentralisierung) teilweise umgesetzt**
	- Neuer zentraler Wrapper `CMS\Http\Request` eingeführt.
	- Kritische Endpunkte (`cron.php`, `FileUploadService`) sowie zentrale Routing-Pfade (`Router`, `ApiRouter`) migriert.
	- Folgeetappe Admin-Module (höchste Trefferdichte) umgesetzt: `MediaModule`, `UsersModule`, `PagesModule`, `PostsModule`.
	- Nächste SEC-003-Welle für Restmodule umgesetzt: `DashboardModule`, `SystemInfoModule`, `HubSitesModule`, `AiServicesModule`, `BackupsModule`.
	- Zusätzlicher Wrapper-Zugriff für Session (`Request::session`) ergänzt.
	- Messbarer Delta im Scope `CMS/admin/modules/**`: direkte Superglobal-Treffer von **31** auf **0** reduziert.
	- Breite Restmigration der 676 Fundstellen bleibt als Folgeetappe bestehen.
- ✅ **SEC-004 (Web-Cron-Token in URL) umgesetzt**
	- Header-first/standardmäßig Header-only erzwungen (`X-CMS-Cron-Token`).
	- Query-Token nur noch optional über expliziten Übergangsschalter `CMS_CRON_ALLOW_QUERY_TOKEN`.
	- Deprecation-Warnlogging für Query-Token-Aufrufe ergänzt.

### step-001
- **Ziel:** Upload- und Importpfade weiter härten.
- **Befund:** Uploads prüfen CSRF, Auth, Dateigröße und MIME, bleiben aber eine zentrale Angriffsfläche; Importer speichert XML/JSON in Upload-Strukturen.
- **Risiko:** Missbrauch durch Parser-Bugs, Polyglot-Dateien, DoS über große Importinhalte oder später fehlerhafte Auslieferung.
- **Technische Ursache:** Breite Upload-Funktionalität in Media, EditorJS, Importer und Theme-Customizer.
- **Lösungsweg:** Einheitliche Upload-Policy mit Allowlist je Kontext, Quarantäne-Verzeichnis außerhalb Webroot, Viren-/Content-Scan-Hook, serverseitige Revalidierung vor Auslieferung.
- **Betroffene Dateien:** CMS\core\Services\FileUploadService.php, CMS\core\Services\Media\UploadHandler.php, CMS\plugins\cms-importer\includes\class-admin.php.
- **Priorität:** P1
- **Aufwand:** M
- **Abhängigkeiten:** Upload-Konfiguration, Webserver-Auslieferung.
- **Status:** ✅ umgesetzt (2026-06-13)

### step-002
- **Ziel:** Dynamische SQL-Wartungsbefehle absichern.
- **Befund:** Tabellenwartung baut SQL aus Operation und Tabellenname zusammen.
- **Risiko:** Bei unvollständiger Identifier-Validierung können administrative Wartungsfunktionen zu SQL-Manipulationen führen.
- **Technische Ursache:** SQL-Identifier sind nicht parametrisierbar; die Sicherheit hängt vollständig an `quoteIdentifier` und vorgelagerten Quellen.
- **Lösungsweg:** Tabellen ausschließlich aus `information_schema`/Prefix-Allowlist auswählen, Identifier vor `quoteIdentifier` streng gegen `/^[A-Za-z0-9_]+$/` validieren und Audit-Log erzwingen.
- **Betroffene Dateien:** CMS\core\Services\SystemService.php:611-617.
- **Priorität:** P1
- **Aufwand:** S
- **Abhängigkeiten:** Admin-Systemmodul.
- **Status:** ✅ umgesetzt (2026-06-13)

### step-003
- **Ziel:** Request-Zugriffe zentralisieren.
- **Befund:** Der Fokus-Scan fand 676 direkte Superglobal-Zugriffe in First-Party-PHP-Dateien.
- **Risiko:** Inkonsistente Validierung/Normalisierung, XSS-/Path-Traversal-/Business-Logic-Lücken.
- **Technische Ursache:** Kein strikt durchgesetzter Request-DTO-/Validator-Layer.
- **Lösungsweg:** Request-Wrapper für GET/POST/FILES/SERVER, typed Validatoren pro Formular/Endpoint, schrittweise Migration der Admin-Module.
- **Betroffene Dateien:** CMS\admin\modules\*, CMS\core\Services\*, CMS\plugins\cms-importer\*.
- **Priorität:** P2
- **Aufwand:** L
- **Abhängigkeiten:** Regressionstests pro Modul.
- **Status:** 🟨 teilweise umgesetzt (Wrapper + Migration kritischer Endpunkte + Admin-Modul-Scope vollständig migriert)

### step-004
- **Ziel:** Web-Cron-Token nicht über URLs transportieren.
- **Befund:** Cron akzeptiert Token aus Header oder Query-Parameter.
- **Risiko:** Query-Token können in Logs, Browser-History oder Referern landen.
- **Technische Ursache:** Fallback auf `$_GET['token']`.
- **Lösungsweg:** Query-Token als deprecated markieren, Header-only erzwingen, Übergangszeit mit Warn-Logging.
- **Betroffene Dateien:** CMS\cron.php:46-58.
- **Priorität:** P3
- **Aufwand:** S
- **Abhängigkeiten:** Cron-Aufrufer.
- **Status:** ✅ umgesetzt (2026-06-13)
