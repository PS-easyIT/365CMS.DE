# Funktionsvollständigkeit

**Bereichsscore:** 95/100

## Kurzfazit
Die Funktionsabdeckung bleibt breit und wurde im aktuellen Schritt gezielt stabilisiert: Analytics/Tracking besitzt jetzt ein sichtbares Vollständigkeitsmodell (`configured/partial/missing`) mit Placeholder-Blockierung, und die Media-Einstellungen bieten eine explizite Member-Upload-Readiness-Checkliste.

Offen bleibt vor allem die CI-Verkabelung der jetzt zentral verfügbaren Testsuite; die PDF-/Dependency-Story ist durch vorhandenen `PdfService` + VendorRegistry-Einbindung und Smoke-Test deutlich konsolidiert.

## Score-Begründung
- Startwert 100
- FUNC-001: 0 (abgeschlossen)
- FUNC-002: -2 (teilweise reduziert)
- FUNC-003: 0 (abgeschlossen)
- FUNC-004: -3 (teilweise reduziert)

## Findings-Tabelle
| ID | Modul | Feature | Funktion | Schweregrad | Auswirkung | Status | Fundstelle | Quelle |
|---|---|---|---|---|---:|---|---|---|
| FUNC-001 | Analytics/Consent | Tracking | Placeholder IDs | niedrig | 0 | ✅ abgeschlossen | CMS\admin\modules\seo\SeoSuiteModule.php; CMS\admin\views\seo\analytics.php | Codefund |
| FUNC-002 | Member/Media | Uploads | Member Uploads | niedrig | -2 | 🟨 teilweise reduziert | CMS\admin\views\media\settings.php (Readiness-Checkliste), CMS\core\Services\FileUploadService.php | Codefund |
| FUNC-003 | Dependencies | PDF/Reports | dompdf vendored | niedrig | 0 | ✅ abgeschlossen | `CMS\core\Services\PdfService.php`, `CMS\core\VendorRegistry.php`, `TESTS\pdf-service\run.php` | Codefund |
| FUNC-004 | Tests | Modultests | TESTS-Verzeichnis | niedrig | -3 | 🟨 teilweise reduziert | `TESTS\run.php` + `TESTS\bootstrap.php` vorhanden; CI-Verkabelung noch offen | Heuristik |

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
