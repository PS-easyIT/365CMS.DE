# Wartbarkeit

**Bereichsscore:** 68/100

## Kurzfazit
Die Codebasis ist funktionsreich, aber sehr groß und historisch gewachsen.

## Score-Begründung
- Startwert 100
- MAINT-002: -8 (hoch)
- MAINT-003: -7 (mittel)
- MAINT-004: -7 (mittel)

## Findings-Tabelle
| ID | Modul | Feature | Funktion | Schweregrad | Auswirkung | Fundstelle | Quelle |
|---|---|---|---|---|---:|---|---|
| MAINT-002 | Request Layer | Input Handling | Superglobals | hoch | -8 | Fokus-Scan: 676 direkte Superglobal-Fundstellen | Heuristik/Codefund |
| MAINT-003 | Data Layer | SQL APIs | query/get_var/get_results | mittel | -7 | CMS\core\Database.php:164-178 und zahlreiche Aufrufer | Codefund |
| MAINT-004 | Tests/CI | Qualitätssicherung | Runner/Manifest | mittel | -7 | TESTS vorhanden, aber kein Root-/CMS-Testmanifest gefunden | Heuristik |

## Umsetzungsschritte

### step-002
- **Ziel:** Einheitlichen Request-Layer etablieren.
- **Befund:** Viele direkte Zugriffe auf `$_GET`, `$_POST`, `$_FILES`, `$_SERVER`.
- **Risiko:** Validierungslogik dupliziert und schwer testbar.
- **Technische Ursache:** Module lesen globale Eingaben selbst.
- **Lösungsweg:** Request-DTOs/Validatoren, Controller-Konventionen, statische Regel gegen direkte Superglobal-Nutzung.
- **Betroffene Dateien:** CMS\admin\modules, CMS\core\Services, CMS\plugins.
- **Priorität:** P1
- **Aufwand:** L
- **Abhängigkeiten:** Regressionstests.

### step-003
- **Ziel:** SQL-Zugriff vereinheitlichen.
- **Befund:** Neben Prepared Statements existiert eine breite generische Query-API.
- **Risiko:** Unterschiedliche Sicherheits- und Fehlerbehandlungsqualität.
- **Technische Ursache:** Datenzugriff ist nicht strikt repository-/servicegebunden.
- **Lösungsweg:** Repositories pro Domäne, Identifier-SQL separat, Deprecation von generischer Query in Fachmodulen.
- **Betroffene Dateien:** CMS\core\Database.php, CMS\admin\modules\*, CMS\core\Services\*.
- **Priorität:** P2
- **Aufwand:** L
- **Abhängigkeiten:** Datenzugriffstests.

### step-004
- **Ziel:** CI-/Teststruktur nachvollziehbar machen.
- **Befund:** Viele Testverzeichnisse existieren, aber kein zentrales Runner-Manifest im Audit-Scope.
- **Risiko:** Neue Entwickler können Qualitätssicherung nicht reproduzierbar ausführen.
- **Technische Ursache:** Tests sind thematisch abgelegt, nicht über einheitliches Tooling orchestriert.
- **Lösungsweg:** Dokumentierten Test-Entry-Point und CI-Konfiguration ergänzen.
- **Betroffene Dateien:** TESTS\*, README.md, .github\*.
- **Priorität:** P2
- **Aufwand:** M
- **Abhängigkeiten:** Testumgebung.
