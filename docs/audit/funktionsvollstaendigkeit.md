# Funktionsvollständigkeit

**Bereichsscore:** 98/100

**Audit-Scope:** Für 365CMS-Core-Bewertungen zählt ausschließlich `365CMS.DE/CMS/**`; `TESTS/**` dient als Validierungsnachweis.

## Kurzfazit
Die Funktionsabdeckung bleibt breit und wurde im aktuellen Schritt gezielt stabilisiert: Analytics/Tracking besitzt ein sichtbares Vollständigkeitsmodell (`configured/partial/missing`) mit Placeholder-Blockierung, die Media-Einstellungen bieten eine explizite Member-Upload-Readiness-Checkliste, und der AI/KI-Bereich ist nun logisch aufgebaut sowie als Single-Provider-Architektur für Azure AI, Mistral AI, OpenAI, OpenRouter, Ollama und Mock umgesetzt. Inhalts- und SEO-Assistent erzeugen echte serverseitige Preview-Ausgaben statt nur Prompt-Settings zu verwalten. Der DE→EN-Copy-Flow für Seiten und Beiträge wurde erneut end-to-end geprüft und um zusätzliche Root-Cause-Fixes für Slug-Normalisierung, Inline-SEO-Drafts und EditorJS-Revisionsvergleich gehärtet.

Offen bleibt vor allem die CI-Verkabelung der jetzt zentral verfügbaren Testsuite; die PDF-/Dependency-Story ist durch vorhandenen `PdfService` + VendorRegistry-Einbindung und Smoke-Test deutlich konsolidiert.

## Score-Begründung
- Startwert 100
- FUNC-001: 0 (abgeschlossen)
- FUNC-002: -2 (teilweise reduziert)
- FUNC-003: 0 (abgeschlossen)
- FUNC-004: -3 (teilweise reduziert)
- FUNC-005: 0 (abgeschlossen, erweitert)

## Findings-Tabelle
| ID | Modul | Feature | Funktion | Schweregrad | Auswirkung | Status | Fundstelle | Quelle |
|---|---|---|---|---|---:|---|---|---|
| FUNC-001 | Analytics/Consent | Tracking | Placeholder IDs | niedrig | 0 | ✅ abgeschlossen | CMS\admin\modules\seo\SeoSuiteModule.php; CMS\admin\views\seo\analytics.php | Codefund |
| FUNC-002 | Member/Media | Uploads | Member Uploads | niedrig | -2 | 🟨 teilweise reduziert | CMS\admin\views\media\settings.php (Readiness-Checkliste), CMS\core\Services\FileUploadService.php | Codefund |
| FUNC-003 | Dependencies | PDF/Reports | dompdf vendored | niedrig | 0 | ✅ abgeschlossen | `CMS\core\Services\PdfService.php`, `CMS\core\VendorRegistry.php`, `TESTS\pdf-service\run.php` | Codefund |
| FUNC-004 | Tests | Modultests | TESTS-Verzeichnis | niedrig | -3 | 🟨 teilweise reduziert | `TESTS\run.php` + `TESTS\bootstrap.php` vorhanden; CI-Verkabelung noch offen | Heuristik |
| FUNC-005 | AI/KI | Provider, Admin & Generatoren | Single Provider, Azure/Mistral/OpenAI-kompatible APIs, Content-/SEO-Previews | niedrig | 0 | ✅ abgeschlossen | `CMS\core\Services\AI\*`, `CMS\admin\views\system\ai-services.php`, `TESTS\ai-services\run.php` | Codefund/Test |
| FUNC-006 | Content | DE→EN Copy | Seiten/Beiträge inkl. EditorJS, SEO, Metadaten, Medienreferenzen und Sprachrelation | niedrig | 0 | ✅ abgeschlossen | `CMS\core\Services\ContentLanguageCopyService.php`, `CMS\admin\pages.php`, `CMS\admin\posts.php`, `TESTS\content-language-copy\run.php` | Codefund/Test |

## Umsetzungsschritte

### step-001
- **Ziel:** Placeholder-Konfigurationen als unvollständig kennzeichnen.
- **Befund:** ✅ umgesetzt.
- **Risiko:** deutlich reduziert.
- **Technische Ursache:** adressiert durch Save-Validierung + Statusmodell.
- **Lösungsweg:** Feature-Statusmodell mit `configured/partial/missing`, sichtbare Integrationsmatrix im Analytics-UI, Blockierung ungültiger IDs beim Speichern sowie Legacy-/manuell gesetzte ungültige Kennungen als `partial` statt `configured`.
- **Betroffene Dateien:** CMS\admin\modules\seo\SeoSuiteModule.php, CMS\admin\views\seo\analytics.php.
- **Priorität:** P2
- **Aufwand:** S
- **Abhängigkeiten:** Settings-Modul.

### step-002
- **Ziel:** Member-Uploads vollständig produktionsreif absichern.
- **Befund:** 🟨 teilweise umgesetzt.
- **Risiko:** reduziert, aber nicht vollständig eliminiert.
- **Technische Ursache:** UI-Transparenz jetzt vorhanden, weiterführende Quota-/Moderationsnachweise noch nicht zentralisiert.
- **Lösungsweg:** Readiness-Checkliste in den Media-Settings ergänzt (Enable-Status, Limits, Typen, Sicherheitskontrollen); Quota-/Moderationsabdeckung als nächste Etappe offen.
- **Betroffene Dateien:** CMS\core\Services\FileUploadService.php, CMS\admin\modules\media.
- **Priorität:** P2
- **Aufwand:** M
- **Abhängigkeiten:** Media Settings.

### step-003
- **Ziel:** PDF-/Dokumentfunktionen konsolidieren.
- **Befund:** ✅ umgesetzt.
- **Risiko:** deutlich reduziert.
- **Technische Ursache:** durch zentrale Service-/Registry-Pfade entschärft.
- **Lösungsweg:** `PdfService` nutzt `VendorRegistry::loadPackage('dompdf')`; ergänzender Smoke-Test in `TESTS\pdf-service\run.php` dokumentiert Runtime-Fähigkeit.
- **Betroffene Dateien:** CMS\core\Services\PdfService.php, CMS\core\VendorRegistry.php, TESTS\pdf-service\run.php.
- **Priorität:** P2
- **Aufwand:** M
- **Abhängigkeiten:** Dependency-Management.

### step-004
- **Ziel:** Vollständigkeit über Tests nachweisen.
- **Befund:** 🟨 teilweise umgesetzt.
- **Risiko:** Neue oder optionale Features bleiben ohne einheitliche Abnahmekriterien.
- **Technische Ursache:** Tests sind verzeichnisbasiert, nicht als zentrale Pipeline erkennbar.
- **Lösungsweg:** Zentralen Runner (`TESTS/run.php`) und gemeinsames Bootstrap (`TESTS/bootstrap.php`) etabliert; nächste Etappe: CI-Job + feste Gates.
- **Betroffene Dateien:** TESTS\*, CMS\*.
- **Priorität:** P2
- **Aufwand:** M
- **Abhängigkeiten:** CI.

### step-005
- **Ziel:** AI/KI-Module auf logischen Adminaufbau und Provider-Vollständigkeit prüfen.
- **Befund:** ✅ umgesetzt.
- **Risiko:** deutlich reduziert; Azure AI, Mistral AI, OpenAI, OpenRouter, Ollama und Mock sind sichtbar, aber gemäß Zielarchitektur ist immer genau ein Provider aktiv. Content-/SEO-Ausgaben laufen als Preview mit redaktioneller Freigabe.
- **Technische Ursache:** zuvor waren OpenAI/OpenRouter nur vorbereitet, Mistral fehlte im Provider-Katalog und die UI ließ mehrere Provider parallel/fallbackartig denken.
- **Lösungsweg:** `OpenAiCompatibleProvider` ergänzt, Provider-Katalog erweitert, Gateway-Factory und Readiness-Prüfung angepasst, generische `generateText()`-Fähigkeit plus `generateContentDraft()`/`generateSeoDraft()` ergänzt, Admin-Hinweise/Formulare auf Single Provider aktualisiert und `TESTS\ai-services\run.php` erweitert.
- **Betroffene Dateien:** CMS\core\Services\AI\*, CMS\admin\views\system\ai-services.php, TESTS\ai-services\run.php.
- **Priorität:** P1
- **Aufwand:** M
- **Abhängigkeiten:** API-Key/Endpoint-Konfiguration pro Provider.

### step-006
- **Ziel:** DE→EN-Kopierfunktion für Seiten und Beiträge vollständig end-to-end prüfen und Root Causes statt Workarounds beheben.
- **Befund:** ✅ umgesetzt.
- **Risiko:** deutlich reduziert; Copy läuft serverseitig deterministisch über eine zentrale Payload-Builder-Schicht und erhält den ursprünglichen Formular-Submitter auch bei EditorJS-Sync.
- **Technische Ursache:** zusätzlich zum bereits behobenen Client-only-Copy waren drei Restfehler vorhanden: Slug-Normalisierung im Copy-Service war schwächer als die System-Slugger, Inline-Fehler-Renderdaten enthielten EN-SEO-Felder nicht vollständig, und der EditorJS-Revisionsvergleich prüfte einen nicht berechneten `sha1`-Key statt `sha256`.
- **Lösungsweg:** Slug-Transliteration für deutsche Umlaute/ß und häufige Akzente im `ContentLanguageCopyService` ergänzt; `meta_title_en`/`meta_description_en` in Page-/Post-Inline-Draftdaten aufgenommen und sprachabhängig geschützt; EditorJS-Revisionsdiff auf vorhandene `sha256`-Summaries umgestellt; Smoke-Suite um Routing, CSRF-Kontext, Submitter-Erhalt, Hidden-JSON-Sync, Copy-Buttons, Inline-SEO-Drafts, Renderer-/Schema-Pfade und Revisionsvergleich erweitert.
- **Betroffene Dateien:** CMS\core\Services\ContentLanguageCopyService.php, CMS\admin\pages.php, CMS\admin\posts.php, CMS\admin\modules\pages\PagesModule.php, CMS\admin\modules\posts\PostsModule.php, TESTS\content-language-copy\run.php.
- **Priorität:** P1
- **Aufwand:** M
- **Abhängigkeiten:** bestehende Tabellen-Spalten für `title_en`, `slug_en`, `content_en`, `excerpt_en`, `meta_title_en`, `meta_description_en`; keine neue Migration erforderlich.

## Update 2026-06-13 (AI/KI Provider-Vollständigkeit)
- AI/KI-Adminbereich bestätigt: Dashboard, Übersetzung, Inhaltsassistent, SEO-Assistent und Einstellungen bilden einen logischen Aufbau.
- `OpenAiCompatibleProvider` ermöglicht OpenAI-kompatible Chat-Completions für OpenAI, Mistral AI und OpenRouter.
- Azure AI / Azure OpenAI bleibt mit Endpoint, Deployment, API-Version und API-Key als eigener Provider verdrahtet.
- Die Admin-Speicherung erzwingt genau einen aktiven Provider; Fallback-/Parallelprovider-UI wurde entfernt.
- Inhalts- und SEO-Assistenten erzeugen jetzt serverseitige Preview-Ausgaben über Provider-Capability-Gates; automatische Veröffentlichung findet nicht statt.
- Validiert: `php TESTS\run.php --suite=ai-services` → **PASS**.

## Update 2026-06-13 (DE→EN Copy End-to-End)

### Annahmen
- Für den 365CMS-Core zählt ausschließlich `365CMS.DE/CMS/**`; Testdateien unter `TESTS/**` sind Validierungsnachweis.
- Der Copy-Flow ist eine strukturierte DE→EN-Kopie, keine maschinelle Übersetzung: Titel, Slug, EditorJS-Inhalt, SEO, Status, Metadaten, Medienreferenzen und Sprachrelationen werden deterministisch übernommen.
- Medienreferenzen werden erhalten und nicht physisch dupliziert; temporäre Upload-Relocation bleibt Aufgabe des Save-Flows.

### Fehlerursachen
- Die Slug-Normalisierung im zentralen Copy-Service entfernte deutsche Umlaute/ß zu schwach bzw. inkonsistent gegenüber den System-Sluggern.
- Inline-Renderdaten nach Validierungsfehlern oder Sprachwechsel enthielten `meta_title_en` und `meta_description_en` nicht vollständig, wodurch EN-SEO-Drafts verloren oder leer angezeigt werden konnten.
- EditorJS-Revisionsvergleiche nutzten einen veralteten `sha1`-Array-Key, obwohl die Summary-Funktion `sha256` berechnet.
- Der frühere Client-Copy war bereits ersetzt; die erneute Prüfung bestätigte, dass der aktuelle POST-Flow den serverseitigen Submitter erhält und alle EditorJS-Hidden-JSON-Felder vor dem Submit synchronisiert.

### Betroffene Dateien/Funktionen
- `CMS\core\Services\ContentLanguageCopyService.php`: `buildPageGermanToEnglishPayload()`, `buildPostGermanToEnglishPayload()`, `normalizeEditorJson()`, `normalizeSlug()`, `buildSeoRelationPayload()`.
- `CMS\admin\pages.php`: `cms_admin_pages_build_inline_edit_data()`, `post_handler` für `copy_de_to_en`, `cms_admin_pages_target_url()`.
- `CMS\admin\posts.php`: `cms_admin_posts_build_inline_edit_data()`, `post_handler` für `copy_de_to_en`, `cms_admin_posts_target_url()`.
- `CMS\admin\modules\pages\PagesModule.php`: `copyGermanToEnglish()`, Save-Preservation, EditorJS-Revisionsdiff.
- `CMS\admin\modules\posts\PostsModule.php`: `copyGermanToEnglish()`, Save-Preservation, Tag-/Kategorie-Sync, EditorJS-Revisionsdiff.
- `CMS\assets\js\admin-content-editor.js`: Submitter-Erhalt, `Promise.all(...saveEditorContent...)`, `requestSubmit(resolvedSubmitter)`, `suppressPlainEditorSubmitNames()`.

### Konkrete Codeänderungen
- `normalizeSlug()` im Copy-Service transliteriert jetzt Umlaute/ß und häufige Akzente (`ä→ae`, `ö→oe`, `ü→ue`, `ß→ss` usw.) vor dem ASCII-Slug-Cleanup.
- Inline-Draftdaten für Seiten und Beiträge enthalten und bewahren `meta_title_en` und `meta_description_en` sprachabhängig.
- Beim Bearbeiten der EN-Sicht werden DE-SEO-Felder aus dem Bestand geschützt; beim Bearbeiten der DE-Sicht werden EN-SEO-Felder aus dem Bestand geschützt.
- EditorJS-Revisionsvergleich in Page-/Post-Modulen vergleicht nun die tatsächlich erzeugten `sha256`-Summaries.
- `TESTS\content-language-copy\run.php` prüft zusätzlich Router, CSRF-Shell-Kontext, EN-Redirect, Copy-Buttons, `formnovalidate`, Bestätigung, Hidden-Originalfelder, Submitter-Erhalt, Hidden-JSON-Sync und Inline-SEO-Drafts.

### DB/Migrationen
- Keine neue Migration erforderlich.
- Bereits vorhandene/abgesicherte Spalten werden weiter genutzt: `meta_title_en`, `meta_description_en`, `title_en`, `slug_en`, `content_en`, `excerpt_en`.
- Sprachrelation läuft über vorhandenes SEO-Meta-Feld `hreflang_group` mit deterministischer Gruppe (`page-{id}` / `post-{id}`), wenn leer.

### Tests
- `php -l` auf allen geänderten PHP-Dateien → **PASS**.
- `php TESTS\run.php --suite=content-language-copy` → **PASS**.
- Regression: `php TESTS\run.php --suite=ai-services` → **PASS**.
- VS-Code-Diagnostics für geänderte Copy-/Routing-/Modul-/Testdateien → **keine Fehler**.

### Manuelle Prüfliste
- Seite mit DE-Titel/Inhalt/SEO/Featured Image öffnen, „DE → EN kopieren“ auslösen, Bestätigung akzeptieren, danach EN-Edit-Ansicht prüfen.
- Beitrag mit Teaser, Tags, Kategorie, Template-Metadaten, Veröffentlichungsdatum und Featured Image kopieren und EN-Felder prüfen.
- Slugs mit Umlauten testen, z. B. `Große Übersicht` → `grosse-uebersicht`.
- Nach Copy speichern und Frontend/Preview auf EN-Lokalisierung, SEO-Titel/-Beschreibung und `hreflang_group` prüfen.
- EditorJS-Inhalte mit mehreren Blöcken und Medienblöcken kopieren; Hidden-Felder dürfen weder leer noch durch Plain-Fallback überschrieben werden.
- Validierungsfehler absichtlich auslösen und kontrollieren, dass EN-SEO-Drafts im Inline-Render erhalten bleiben.
