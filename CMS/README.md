# 365CMS – Projektdokumentation | Abschnitt: Runtime-Einstieg
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

`CMS/` is the deployable 365CMS runtime. It contains the bootstrap, configuration loader, core services, administration area, member area, runtime assets, plugins, themes, views and the installer.

### Runtime layout

```text
CMS/
├── index.php       # Web bootstrap
├── install.php     # Installer and schema update entry point
├── config/         # Local application configuration
├── core/           # Bootstrap, routing, database and services
├── admin/          # Administration area
├── member/         # Member area
├── plugins/        # Installed runtime plugins
├── themes/         # Installed runtime themes
├── includes/       # Shared helpers
├── assets/         # Runtime CSS, JavaScript and libraries
├── views/          # Public and authentication views
├── logs/           # Runtime logs
├── cache/          # Runtime cache
├── uploads/        # Uploaded files
├── backups/        # Backup files
└── vendor/         # Bundled third-party libraries
```

The active runtime theme and plugin are discovered from the application configuration. The repository currently contains `cms-default` and `cms-importer`.

### Configuration and deployment

[`config.php`](config.php) is the guarded configuration entry point. The application configuration is maintained in [`config/app.php`](config/app.php). Keep installation-specific secrets, logs, uploads, cache and backups outside public release archives.

Use [`../DOC/INSTALLATION.md`](../DOC/INSTALLATION.md) for the supported installation and update workflow. Use [`../DOC/CMSFILESTRUCTUR.md`](../DOC/CMSFILESTRUCTUR.md) for the detailed runtime inventory.

## Deutsch

`CMS/` ist die auslieferbare 365CMS-Runtime. Das Verzeichnis enthält Bootstrap, Konfigurationsloader, Core-Services, Administration, Mitgliederbereich, Runtime-Assets, Plugins, Themes, Views und Installer.

### Deployment-Hinweise

Der Webserver muss entweder direkt auf `CMS/` zeigen oder die Anwendung so weiterleiten, dass [`index.php`](index.php) und [`install.php`](install.php) erreichbar sind. Lokale Konfiguration, Secrets, Logs, Uploads, Cache und Backups gehören nicht in öffentliche Release-Pakete.

Die vollständige Installationsanleitung steht unter [`../DOC/INSTALLATION.md`](../DOC/INSTALLATION.md). Das ausführliche Runtime-Inventar steht unter [`../DOC/CMSFILESTRUCTUR.md`](../DOC/CMSFILESTRUCTUR.md).
