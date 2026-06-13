# PHP 8.4 Best Practice und Kompatibilität

**Bereichsscore:** 80/100

## Kurzfazit
Die Codebasis nutzt `declare(strict_types=1)` in vielen First-Party-Dateien und die lokale PHP-8.4-Syntaxprüfung der fokussierten PHP-Dateien ergab 0 Syntaxfehler. Risiken bestehen durch fehlendes zentrales Composer-Manifest/Lockfile, vendored Drittbibliotheken und potenzielle Deprecations in nicht zentral verwalteten Dependencies.

## Score-Begründung
- Startwert 100
- PHP84-001: -8 (hoch)
- PHP84-002: -5 (mittel)
- PHP84-003: -4 (mittel)
- PHP84-004: -3 (niedrig)

## Findings-Tabelle
| ID | Modul | Feature | Funktion | Schweregrad | Auswirkung | Fundstelle | Quelle |
|---|---|---|---|---|---:|---|---|
| PHP84-001 | Dependencies | Composer/NPM | Dependency Governance | hoch | -8 | Kein Root-/CMS-composer.json; viele composer.json nur in ASSETS/CMS\assets/CMS\vendor | Konfigurationsfund |
| PHP84-002 | Dependencies | Vendored Libraries | Runtime libs | mittel | -5 | ASSETS\dompdf-3.1.5, ASSETS\Carbon-3.11.4, CMS\assets\* | Konfigurationsfund |
| PHP84-003 | Runtime | Low-level SQL/IO | PDO/file APIs | mittel | -4 | CMS\core\Database.php, CMS\core\Services\* | Codefund/Heuristik |
| PHP84-004 | Code style | Type safety | Strict types | niedrig | -3 | Gemischte historische Struktur mit vielen Legacy-/Backup-Bereichen | Heuristik |

## Umsetzungsschritte

### step-001
- **Ziel:** PHP-8.4-Kompatibilität reproduzierbar machen.
- **Befund:** Kein zentrales Composer-Manifest/Lockfile für die Anwendung gefunden.
- **Risiko:** Dependency-Versionen und PHP-Constraints sind nicht eindeutig prüfbar.
- **Technische Ursache:** Drittbibliotheken sind vendored statt zentral gemanagt.
- **Lösungsweg:** Root- oder CMS-Composer-Projekt mit `config.platform.php`, Lockfile, Autoloading und Audit-Pipeline einführen.
- **Betroffene Dateien:** Repository-Root, CMS\assets, CMS\vendor, ASSETS.
- **Priorität:** P1
- **Aufwand:** L
- **Abhängigkeiten:** Release-/Deploymentprozess.

### step-002
- **Ziel:** Drittbibliotheken regelmäßig prüfen.
- **Befund:** Viele Libraries liegen als kopierte Quellbäume vor.
- **Risiko:** Security- und PHP-8.4-Fixes werden nicht systematisch übernommen.
- **Technische Ursache:** Kein konsolidierter Update-/Audit-Mechanismus.
- **Lösungsweg:** Composer/NPM Audit, SBOM, Version-Matrix und Update-Owner definieren.
- **Betroffene Dateien:** ASSETS\*, CMS\assets\*, CMS\vendor\*.
- **Priorität:** P1
- **Aufwand:** M
- **Abhängigkeiten:** Dependency-Migration.

### step-003
- **Ziel:** PHP-8.4-Deprecations dauerhaft erkennen.
- **Befund:** Syntax-Lint ist erfolgreich, ersetzt aber keine Runtime-Deprecation-Tests.
- **Risiko:** Deprecations treten erst im produktiven Codepfad auf.
- **Technische Ursache:** Keine zentrale Test-/CI-Konfiguration im Audit-Scope gefunden.
- **Lösungsweg:** CI mit `php -d error_reporting=E_ALL`, Smoke-/Integrationstests und Deprecation-Fail-Gate einführen.
- **Betroffene Dateien:** TESTS\*, CMS\*.
- **Priorität:** P2
- **Aufwand:** M
- **Abhängigkeiten:** Testumgebung/DB-Fixtures.

### step-004
- **Ziel:** Striktere Typisierung ausbauen.
- **Befund:** Moderne Typisierung ist vorhanden, aber Legacy-Strukturen und globale Helfer bleiben stark vertreten.
- **Risiko:** TypeErrors/ValueErrors in Edge Cases.
- **Technische Ursache:** Historisch gewachsene CMS-Struktur.
- **Lösungsweg:** DTOs, Value Objects und statische Analyse schrittweise für Core/Admin-Module aktivieren.
- **Betroffene Dateien:** CMS\core, CMS\admin\modules, CMS\includes.
- **Priorität:** P3
- **Aufwand:** L
- **Abhängigkeiten:** Coding-Standards.
