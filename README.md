# 365CMS – Projektdokumentation | Abschnitt: Projektübersicht
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

### What is 365CMS?

365CMS is a self-hosted PHP CMS and portal platform for content, members, themes, plugins, SEO, privacy and day-to-day operations. The runtime is located in [`CMS/`](CMS/); the public documentation is maintained in [`DOC/`](DOC/).

The current product version is defined by [`CMS/core/Version.php`](CMS/core/Version.php). Release `3.4.00` requires PHP `8.4+` and uses schema version `v22`.

### Core capabilities

| Area | Current runtime capability |
|---|---|
| Content | Pages, posts, EditorJS blocks, revisions, categories and tags |
| Administration | Dashboard, users, roles, settings, media, SEO, updates and diagnostics |
| Members | Member area, profiles, notifications and protected features |
| Security | CSRF protection, capability checks, secure sessions, audit logging and hardened uploads |
| Extensions | Runtime plugins in `CMS/plugins/` and themes in `CMS/themes/` |
| Operations | Cache, logs, backups, cron, schema updates and update packages |

### Requirements

- PHP `8.4+`
- PDO with `pdo_mysql`
- A MySQL- or MariaDB-compatible database
- A web server configured to serve the `CMS/` directory
- Writable runtime directories required by the installer, including configuration, logs, cache, uploads and backups

The installer performs the authoritative environment checks. See [`DOC/INSTALLATION.md`](DOC/INSTALLATION.md) for the complete procedure.

### Quick start

```text
1. Deploy the contents of CMS/ to the web root or configure the web root to CMS/.
2. Open install.php in the browser.
3. Enter database, site and administrator values.
4. Complete the schema installation or update.
5. Remove or protect the installer after completion.
```

The runtime entry points are [`CMS/index.php`](CMS/index.php) and [`CMS/install.php`](CMS/install.php). Do not copy installation-specific configuration, logs, uploads or backups into a release archive.

### Documentation

| Topic | Link |
|---|---|
| Documentation hub | [`DOC/README.md`](DOC/README.md) |
| Documentation index | [`DOC/INDEX.md`](DOC/INDEX.md) |
| Installation | [`DOC/INSTALLATION.md`](DOC/INSTALLATION.md) |
| Runtime structure | [`DOC/FILESTRUCTUR.md`](DOC/FILESTRUCTUR.md) |
| Core developer reference | [`DOC/DEVLIST.md`](DOC/DEVLIST.md) |
| Security and audits | [`AUDIT/audit/`](AUDIT/audit/) |
| Release history | [`Changelog.md`](Changelog.md) |
| Community rules | [`CODE_OF_CONDUCT.md`](CODE_OF_CONDUCT.md) |

### Contributing and security

Please keep changes focused, document behavior changes and validate PHP syntax before opening a pull request. Follow the project [`CODE_OF_CONDUCT.md`](CODE_OF_CONDUCT.md) and report security issues privately to the maintainers rather than publishing exploit details in a public issue.

## Deutsch

### Was ist 365CMS?

365CMS ist ein selbst gehostetes PHP-CMS und Portal-System für Inhalte, Mitglieder, Themes, Plugins, SEO, Datenschutz und den laufenden Betrieb. Die Runtime liegt unter [`CMS/`](CMS/); die öffentliche Projektdokumentation liegt unter [`DOC/`](DOC/).

Die aktuelle Produktversion wird in [`CMS/core/Version.php`](CMS/core/Version.php) definiert. Release `3.4.00` benötigt PHP `8.4+` und verwendet die Schema-Version `v22`.

### Zentrale Funktionen

| Bereich | Aktuelle Runtime-Funktion |
|---|---|
| Inhalte | Seiten, Beiträge, EditorJS-Blöcke, Revisionen, Kategorien und Tags |
| Administration | Dashboard, Benutzer, Rollen, Einstellungen, Medien, SEO, Updates und Diagnose |
| Mitglieder | Mitgliederbereich, Profile, Benachrichtigungen und geschützte Funktionen |
| Sicherheit | CSRF-Schutz, Capability-Prüfungen, sichere Sessions, Audit-Logging und gehärtete Uploads |
| Erweiterungen | Runtime-Plugins unter `CMS/plugins/` und Themes unter `CMS/themes/` |
| Betrieb | Cache, Logs, Backups, Cron, Schema-Updates und Update-Pakete |

### Voraussetzungen und Schnellstart

Benötigt werden PHP `8.4+`, PDO mit `pdo_mysql`, eine MySQL- oder MariaDB-kompatible Datenbank sowie ein Webserver, der das Verzeichnis `CMS/` ausliefert. Der Installer prüft die verbindlichen Voraussetzungen und benötigt Schreibrechte für die vorgesehenen Runtime-Verzeichnisse.

Die vollständige Installationsanleitung steht unter [`DOC/INSTALLATION.md`](DOC/INSTALLATION.md). Nach der Installation muss [`CMS/install.php`](CMS/install.php) entfernt oder geschützt werden.

### Mitwirken

Bitte Änderungen fokussiert halten, Verhaltensänderungen dokumentieren und die PHP-Syntax vor einem Pull Request prüfen. Für das Projekt gelten [`CODE_OF_CONDUCT.md`](CODE_OF_CONDUCT.md) und die privaten Meldewege für Sicherheitsprobleme.
