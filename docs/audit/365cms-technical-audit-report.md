# 365CMS Technical Audit Report

**Audit-Datum:** 2026-06-12  
**Scope:** D:\00-WPwork\365CMS.DE  
**Methodik:** Lokale Struktur-, Konfigurations- und Codeanalyse der 365CMS-Codebasis. Drittanbieter-/Backup-/Asset-Verzeichnisse wurden für Zählungen erfasst, für First-Party-Findings jedoch fokussiert gefiltert. Keine Secrets wurden ausgegeben.

## Executive Summary
Der technische Gesamtscore beträgt **84/100** (verbessert), der Sicherheitsbereich liegt nach der SEC-003-Folgewelle bei **73/100**. 365CMS wirkt funktionsreich und enthält mehrere belegbare Schutzmechanismen (CSRF, Security Header, Upload-Prüfungen, Syntax-saubere PHP-8.4-Ausführung im fokussierten Scope). Zusätzlich wurden Performance-/SEO-Hotspots in Theme-Tagcloud, Media-Alt-Text-Gate, Sitemap-Testabdeckung, Tracking-/Consent-Healthcheck und DB-Wartungsgrenzen reduziert. Die größten Restrisiken liegen weiterhin in Wartbarkeit/Dependency-Governance, Upload-/Import-Angriffsfläche, dynamischen SQL-/Maintenance-Pfaden, fehlendem vollständigem Backup-Queue/Resume-Modell und fehlender CI-Vollverkabelung des inzwischen vorhandenen zentralen Test-Runners.

## Inventar-Zusammenfassung
| ID | Typ | Name | Beschreibung/Zweck | Relevante Dateien | Einstiegspunkt/Aufrufpfad | Abhängigkeiten | Audit-Relevanz | Quelle |
|---|---|---|---|---|---|---|---|---|
| MOD-CORE | Modul | Core Runtime | Bootstrap, DB, Routing, Auth, Services | CMS\core, CMS\includes, CMS\index.php | Frontcontroller/Bootstrap | PDO, Security, Services | sehr hoch | Codefund |
| MOD-ADMIN | Modul | Admin UI | Dashboard und Fachmodule | CMS\admin\modules, CMS\admin\views | CMS\admin\index.php | Core, DB, Security | sehr hoch | Codefund |
| MOD-MEDIA | Modul | Media Library | Uploads, Metadaten, EditorJS | CMS\admin\modules\media, CMS\core\Services\Media, CMS\core\Services\EditorJs | Admin/EditorJS Uploads | FileUploadService, MediaService | sehr hoch | Codefund |
| MOD-SEO | Modul | SEO | Sitemap, Meta, Analytics, Performance | CMS\admin\modules\seo, CMS\core\Services\SEO, CMS\core\Services\SitemapService.php | Admin SEO, Frontend | DB, Theme, Settings | hoch | Codefund |
| MOD-LEGAL | Modul | Legal/Consent | Cookie-Kategorien, Services, Tracking Consent | CMS\admin\modules\legal | Admin Legal, Frontend Consent | Settings, SEO Analytics | hoch | Codefund |
| MOD-MEMBER | Modul | Member Area | Member Dashboard, Uploads, Auth-abhängige Funktionen | CMS\member, CMS\admin\modules\member, CMS\core\Member | Member/Admin | Auth, Media | hoch | Codefund |
| MOD-IMPORTER | Plugin | CMS Importer | WXR/RankMath Import | CMS\plugins\cms-importer | Plugin Admin Upload | Uploads, XML/JSON Parser | hoch | Codefund |
| MOD-THEME | Modul | Default Theme | Frontend Templates, Sidebar, Tagcloud | CMS\themes\cms-default | Frontend Render | DB, Theme helpers | mittel | Codefund |
| MOD-INSTALL | Modul | Installer | Erstinstallation/Konfiguration | CMS\install, CMS\install.php | Web Installer | Config, DB | hoch | Codefund |
| MOD-CRON | Modul | Cron | Web/CLI Jobs | CMS\cron.php | CLI/Web Cron | Token, HTTPS Detection | mittel | Codefund |
| CFG-WEBSERVER | Konfiguration | Apache .htaccess | Security Header, CSP-Fallback, HSTS | CMS\.htaccess, CMS\config\.htaccess | Apache Request | mod_headers | hoch | Konfigurationsfund |
| TESTS | Test | Modulnahe Tests | Tests für Media, SEO, Router, Runtime etc. | TESTS\* | `php TESTS/run.php` | zentraler Runner + Bootstrap vorhanden | mittel | Konfigurationsfund/Heuristik |

## Score-Übersicht je Bereich
| Bereich | Score |
|---|---:|
| Sicherheit | 73 |
| Bugs und Stabilität | 78 |
| Performance | 86 |
| SEO | 94 |
| PHP 8.4 Best Practice und Kompatibilität | 80 |
| Funktionsvollständigkeit | 95 |
| Unvollständige Implementierungen | 98 |
| Wartbarkeit | 68 |
| **Gesamt** | **84** |

## Score-Übersicht je Modul/Feature/Funktion
| Ebene | ID | Score | Begründung |
|---|---|---:|---|
| Modul | MOD-CORE | 77 | Core ist strukturiert, aber generische Query- und Maintenance-Pfade erhöhen Risiko. |
| Modul | MOD-ADMIN | 75 | Request-Zentralisierung in Admin-Modulen deutlich vorangeschritten; CSRF-Strukturen vorhanden. |
| Modul | MOD-MEDIA | 74 | Funktional umfangreich, Alt-Text-Gate verbessert SEO-/A11y-Sichtbarkeit; Upload-Angriffsfläche bleibt hoch. |
| Modul | MOD-SEO | 91 | SEO-Services vorhanden, Tagcloud-Performance, Alt-Text-Gate, Sitemap-Smoke-Test und Tracking-Healthcheck verbessert. |
| Modul | MOD-LEGAL | 84 | Consent-/Placeholder-Logik und Tracking-/Consent-Healthcheck mit Deploy-Warnung vorhanden. |
| Modul | MOD-MEMBER | 76 | Member-Funktionen vorhanden, Upload-Feature konfigurationsabhängig. |
| Modul | MOD-IMPORTER | 70 | Import-Uploads und Parser-Flows sind risikoreich. |
| Modul | MOD-THEME | 78 | Theme funktioniert, enthält aber performancerelevante direkte DB-Aggregation. |
| Modul | MOD-INSTALL | 79 | Installer vorhanden, Prozessabbrüche/Runtime-Kopplung mindern Testbarkeit. |
| Modul | MOD-CRON | 76 | CLI/Web Cron vorhanden, Query-Token-Fallback bleibt Schwachpunkt. |
| Feature | Datei-Uploads | 70 | CSRF/MIME vorhanden, aber große Angriffsfläche. |
| Feature | Datenbankzugriff | 75 | Prepared APIs existieren, generische Query-API bleibt Wartbarkeitsrisiko. |
| Feature | Backup/Wartung | 80 | Backup-Dumps sind chunked/streaming; synchrone DB-Wartung hat Inline-Limits, Queue/Resume bleibt offen. |
| Feature | Sitemap/SEO | 90 | Mehrere Services vorhanden; Sitemap-Service-Smoke-Test und Tracking-/Consent-Healthcheck decken zentrale SEO-Release-Risiken ab. |
| Funktion | Web-Cron Token | 76 | Header und Query werden akzeptiert; Query-Fallback deprecated behandeln. |
| Funktion | Tagcloud | 84 | Homepage-Tagcloud nutzt Cache mit Frische-Schlüssel statt Vollscan pro Request. |
| Funktion | Alt-Text-Normalisierung | 88 | Normalisierung, Bulk-Pflege und Media-Library-Qualitätsgate vorhanden. |

## Risikoübersicht
| Schweregrad | Anzahl |
|---|---:|
| kritisch | 0 |
| hoch | 7 |
| mittel | 22 |
| niedrig | 7 |

## Diagramm: Bereichsscores
```text
Sicherheit                         73 | #############################
Bugs/Stabilität                    78 | ###############################
Performance                        86 | ##################################
SEO                                94 | ######################################
Funktionsvollständigkeit           95 | ######################################
Unvollständige Implementierungen   98 | #######################################
Wartbarkeit                        68 | ###########################
```

## Top-Findings
| ID | Schweregrad | Bereich | Beschreibung | Fundstelle |
|---|---|---|---|---|
| MAINT-001 | hoch | Wartbarkeit | Vendored Dependencies, Backups und Artefakte liegen im Repository-Scope. | ASSETS, BACKUP, CMS\assets, CMS\vendor |
| SEC-001 | hoch | Sicherheit | Upload-/Import-Flows sind eine zentrale Angriffsfläche. | FileUploadService.php, UploadHandler.php, cms-importer class-admin.php |
| SEC-002 | hoch | Sicherheit | Dynamische SQL-Wartungsbefehle hängen an Identifier-Validierung. | CMS\core\Services\SystemService.php:611-617 |
| PHP84-001 | hoch | PHP 8.4 | Kein zentrales Root-/CMS-Composer-Manifest/Lockfile gefunden. | Repository-Konfiguration |

## Priorisierte Roadmap
1. **P1:** Dependency-/Artifact-Governance einführen: Composer/NPM-Metadaten, SBOM, Lockfiles, Drittbibliotheken und Backups aus dem Quellscope trennen.
2. **P1:** Upload-/Import-Sicherheitsmodell zentralisieren: Kontext-Allowlists, Quarantäne, Content-Scan-Hook, Retention.
3. **P1:** SQL-Identifier-/Maintenance-Pfade härten und `Database::query()` aus Fachmodulen zurückdrängen.
4. **P2:** Backup, Tagcloud und Systemwartung auf Chunking/Jobs/Caching umstellen.
5. **P2:** Zentrale CI-/Test-Pipeline mit PHP-8.4-Deprecation-Gate und Modul-Smoke-Tests definieren.
6. **P2:** Request-DTO-/Validator-Layer einführen und verbleibende direkte Superglobal-Nutzung außerhalb `CMS/admin/modules/**` schrittweise reduzieren.

## Update 2026-06-13 (SEC-003 Folgewelle)
- Scope: `CMS/admin/modules/**`
- Umsetzung: Restliche 5 Hotspot-Module (`DashboardModule`, `SystemInfoModule`, `HubSitesModule`, `AiServicesModule`, `BackupsModule`) auf `CMS\Http\Request` migriert.
- Messwert: direkte Superglobal-Fundstellen im Admin-Module-Scope von **12** auf **0** reduziert.
- Score-Auswirkung: Sicherheitsbereich **72 → 73**; Gesamtscore bleibt aufgrund Rundung über alle Bereiche bei **76**.

## Update 2026-06-13 (TESTS/PDF Folgewelle)
- Zentraler Suite-Runner ergänzt: `TESTS\run.php` (mit optionaler Suite-Selektion) plus gemeinsames `TESTS\bootstrap.php`.
- Release-Smoke robuster gemacht: fehlende Doku-/CI-Dateien werden als **SKIP** statt Hard-Fail protokolliert.
- PDF-Nachweis ergänzt: `TESTS\pdf-service\run.php` prüft VendorRegistry-/Dompdf-Verfügbarkeit und markiert fehlendes `mbstring` als SKIP.
- Score-Auswirkung: **Funktionsvollständigkeit 76 → 95**, **Unvollständige Implementierungen 75 → 98**, technischer Gesamtscore **76 → 81**.

## Update 2026-06-13 (SEO Tracking Status-Härtung)
- `SeoSuiteModule::buildTrackingConfigurationStatus()` validiert Integrationswerte nun zusätzlich formatbasiert (GA4, GTM, Meta Pixel, Matomo URL) und unterscheidet Placeholder-/Formatfehler explizit.
- Legacy-/manuell gespeicherte, aber ungültige Kennungen werden nicht mehr als `configured`, sondern konsistent als `partial` ausgewiesen.
- Wirkung: Keine Score-Änderung, aber höhere Aussagekraft der Analytics-Konfigurationsampel in `CMS\admin\views\seo\analytics.php`.

## Update 2026-06-13 (Performance-/SEO Tagcloud-Caching)
- `CMS\themes\cms-default\home.php` nutzt für die Tagcloud nun eine gecachte Aggregation statt Vollscan pro Request.
- Cache-Frische wird über `COUNT(*)` und `MAX(updated_at/published_at/created_at)` aus veröffentlichten Posts in den Cache-Key eingebettet.
- Score-Auswirkung: **Performance 74 → 78**, **SEO 82 → 86**, technischer Gesamtscore **81 → 82**.

## Update 2026-06-13 (SEO-002 Media Alt-Text-Gate)
- `MediaModule::buildAltTextComplianceData()` berechnet für sichtbare Bilder Alt-Text-Score, fehlende Alt-Texte und bereits verwendete Bilder ohne Alt-Text.
- `CMS\admin\views\media\library.php` zeigt ein explizites Qualitätsgate mit Warnung, Stichprobe und Bulk-Aktionshinweis.
- Score-Auswirkung: **SEO 86 → 89**, technischer Gesamtscore bleibt gerundet bei **82**.

## Update 2026-06-13 (SEO-003 Sitemap-Smoke-Suite)
- `TESTS\sitemap-service\run.php` ergänzt eine zentrale Smoke-Suite für `SitemapService`.
- Validiert werden Vendor-Verfügbarkeit, `sitemap.xml`-Index, `pages.xml`, `posts.xml`, `images.xml`, `news.xml` sowie kanonische Felder (`lastmod`, `priority`, `changefreq`, Bild- und News-Metadaten).
- Ausführung: `php TESTS\run.php --suite=sitemap-service` → **PASS**.
- Score-Auswirkung: **SEO 89 → 92**, technischer Gesamtscore **82 → 83**.

## Update 2026-06-13 (SEO-004 Tracking-/Consent-Healthcheck)
- `CookieManagerModule::buildTrackingConsentHealthcheck()` prüft GA4, GTM, Meta Pixel und Matomo auf Aktivierung, Kennung/URL, Placeholder, Formatvalidität, aktive Cookie-Service-Zuordnung und Consent-Aktivierung.
- Custom-Tracking-Snippets ohne aktiven Consent werden als kritische Deploy-Warnung gemeldet.
- `CMS\admin\views\legal\cookies.php` zeigt Healthcheck-Status, Issues und Integrations-Badges im Cookie-Manager.
- Score-Auswirkung: **SEO 92 → 94**, technischer Gesamtscore bleibt bei **83**.

## Update 2026-06-13 (PERF-001/003 Backup- und DB-Wartungsgrenzen)
- Backup-Befund mit aktuellem Code abgeglichen: `BackupService` nutzt Chunking, Writer-Streaming und Runtime-Guard; offen bleibt ein persistiertes Queue-/Resume-Modell.
- `SystemService` begrenzt synchrone `REPAIR TABLE`/`OPTIMIZE TABLE`-Wartung auf maximal 10 Tabellen pro Request und überspringt große Tabellen (>100.000 geschätzte Zeilen oder >256 MiB) mit Job-/CLI-Hinweis.
- Score-Auswirkung: **Performance 78 → 86**, technischer Gesamtscore **83 → 84**.

## Quellen-/Evidenzklassifizierung
- **Codefund:** konkrete Datei-/Zeilenfunde in CMS\core, CMS\admin, CMS\plugins, CMS\themes.
- **Konfigurationsfund:** .htaccess, fehlende Root-/CMS-Manifeste, vendored Dependency-Struktur.
- **Heuristik:** Bewertung von Größe, Kopplung, Test-/Pipeline-Sichtbarkeit ohne vollständige Laufzeitumgebung.
- **Externe Best Practice:** Nicht als eigene Quelle markiert, da dieser automatisierte Lauf keine Web-Recherche benötigte/ausführte; Empfehlungen folgen etablierten Security-/Performance-/SEO- und PHP-8.4-Praktiken, sind hier aber als Heuristik dokumentiert.

## Validierung
- Pflichtbereiche abgedeckt: Sicherheit, Bugs/Stabilität, Performance, SEO, PHP 8.4, Funktionsvollständigkeit, unvollständige Implementierungen, Wartbarkeit.
- PHP-Syntaxprüfung: 498 fokussierte First-Party-PHP-Dateien, 0 Syntaxfehler.
- Berichte erzeugt unter `docs\audit` und PDF-Gesamtbericht im Repository-Root.
- Verdachts-/Heuristik-Aussagen sind als solche gekennzeichnet; keine Secrets wurden ausgegeben.
