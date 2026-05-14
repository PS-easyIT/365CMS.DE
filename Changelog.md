﻿**Version:** 3.00.00

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

## 📜 Aktuelle Versionshistorie ab 3.00.00

> Die vollständige historische 2.x-Historie wurde in [`Changelog_old.md`](Changelog_old.md) archiviert.

### v3.00.00 — 14. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.00.00** | 🛡️ security | Core/Final Audit – Logging, Diagnose & Schema-Härtung | **`CMS/core/Database.php`, `CMS/core/AuditLogger.php`, `CMS/admin/views/partials/flash-alert.php`, `CMS/core/Services/RedirectService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `Changelog.md` und `Changelog_old.md` schließen den finalen Core-only Auditlauf für den 365CMS-Hauptcore ab.** Der Audit fokussierte ausschließlich `365CMS.DE` bzw. `CMS/` und ignoriert externe Theme- und Plugin-Repositories. Als konkrete Nachhärtung redigieren Low-Level-Datenbank- und Audit-Logger nun Inline-Secrets, Kontrollzeichen und überlange Diagnosewerte, geben keine DB-Benutzernamen mehr in Fehlerlogs aus und melden DB-Verbindungsfehler nach außen generischer, während technische Details intern begrenzt bleiben. Das gemeinsame Admin-Flash-Partial redigiert sensible Fehlerreport-Kontexte vor Anzeige und Report-Weitergabe und entfernt den früheren `print_r()`-Fallback aus der Diagnoseausgabe. Der Redirect-Schema-Upgrade-Helfer akzeptiert nur noch die erwarteten internen Tabellen-/Spalten-/Definition-Kombinationen, bevor dynamische DDL ausgeführt wird. |
| **3.00.00** | 🛡️ security | Zweiter Auditlauf – Fatal-, Installer- und Schema-Logs | **Der erneute Durchlauf hat weitere Low-Level-Logpfade gehärtet.** `CMS/index.php`, `CMS/core/Security.php`, `CMS/core/CacheManager.php`, `CMS/install/InstallerService.php` und `CMS/core/SchemaManager.php` redigieren Diagnosemeldungen nun ebenfalls vor dem Schreiben in Error-Logs. Der Bootstrap kürzt und maskiert Fatal-Error- und Stacktrace-Logs, Rate-Limit-/Installer-/Cache-Fehler vermeiden rohe Exception-Texte, und der SchemaManager schreibt das automatisch generierte Erst-Admin-Passwort nicht mehr ins globale Error-Log, sondern verweist nur noch auf die bestehende einmalige Credential-Datei. Zusätzlich validiert `SchemaManager::ensureColumnExists()` Tabellen-, Spalten- und ALTER-Präfixe, bevor interne Runtime-Migrationen ausgeführt werden. |
| **3.00.00** | ⬜ chore | Release-Schnitt & Dokumentation | **Die 2.x-Historie wurde von `Changelog.md` nach `Changelog_old.md` verschoben und eine neue, schlanke `Changelog.md` für Version `3.00.00` angelegt.** Version, Update-Metadaten und README verweisen auf den neuen Major-Release-Stand; die historische Detailspur bleibt weiterhin vollständig über `Changelog_old.md` nachvollziehbar. |

---

## ✅ Audit-Nachweis

- Erster Core-Auditlauf: abgeschlossen, Findings in Logging-/Diagnose-/Schema-Härtung umgesetzt.
- Zweiter Core-Auditlauf: abgeschlossen; zusätzliche Log-/Secret-/Schema-Funde wurden umgesetzt.
- Validierung: PHP-Syntax, JSON-Metadaten, Workspace-Diagnostics und Whitespace-Diff werden im Abschlusslauf geprüft.
