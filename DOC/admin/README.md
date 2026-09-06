# 365CMS – Admin-Bereich

> **Stand:** 2026-09-06 | **Dokumentationsversion:** 3.4.00 | **Status:** Aktuelle Gesamtübersicht

Diese Datei ist der Einstieg in die allgemeine Admin-Dokumentation von 365CMS. Die reale Sidebar, die produktiven Routes und die Laufzeitprüfungen im Core sind maßgeblich; diese Übersicht bündelt sie für Betrieb, Redaktion und Entwicklung.

## Inhaltsverzeichnis

- [Überblick](#überblick)
- [Anmeldung und Navigation](#anmeldung-und-navigation)
- [Aktuelle Menügruppen](#aktuelle-menügruppen)
- [AI Services](#ai-services)
- [Sicherheits- und Request-Grundsätze](#sicherheits--und-request-grundsätze)
- [Legacy- und Spezialrouten](#legacy--und-spezialrouten)
- [Relevante Fachdokumente](#relevante-fachdokumente)
- [Betriebscheck vor Änderungen](#betriebscheck-vor-änderungen)

## Überblick

Der Adminbereich ist modular aufgebaut:

- schlanke Entry-Points unter `CMS/admin/`;
- Fachlogik unter `CMS/admin/modules/`;
- rendernde Views unter `CMS/admin/views/`;
- gemeinsame Layout- und Navigationsbausteine unter `CMS/admin/partials/`;
- zentrale Auth-, Security-, Datenbank-, Hook-, Cache- und Audit-Services im Core.

Die bevorzugten URLs sind sprechende Pfade wie `/admin/pages` oder `/admin/seo-dashboard`. Dateinamen mit `.php` sind keine öffentliche URL-Dokumentation.

Grundsätze:

- Menügruppen werden zentral in `CMS/admin/partials/sidebar.php` aufgebaut.
- Plugin-Menüs werden über `cms_admin_menu` registriert und aus der Registry gelesen.
- Fachlogik gehört nicht in Views.
- Schreibende Requests verwenden CSRF-Schutz und das Post/Redirect/Get-Muster.
- Sub-Views sind wrappergebunden und nicht direkt aufrufbar.
- Admin-Callbacks erhalten bei Bedarf automatisch den gemeinsamen `page-body`-/`container-xl`-Wrapper.
- sensible Inhalte, Secrets und Rohprompts werden nicht in Betriebsmonitoring angezeigt.

## Anmeldung und Navigation

1. `/admin` im Browser öffnen.
2. Anmelden.
3. Dashboard und Sidebar öffnen.
4. Nur die für Rolle, Capability und aktiviertes Core-Modul verfügbaren Bereiche verwenden.

Die Sidebar führt Core-Bereiche, AI Services, Plugin-Gruppen und Diagnosepfade zusammen. Viele Gruppen sind scrollbar; die Anzeige hängt zusätzlich von aktivierten Modulen und Marketplace-Einstellungen ab.

## Aktuelle Menügruppen

| Menügruppe | Zentrale Routen | Zweck |
|---|---|---|
| Dashboard | `/admin` | KPIs, Arbeits-Widgets, Warnungen, Favoriten und persönliche Dashboard-Anordnung |
| AI Services | `/admin/ai-services`, `/admin/ai-translation`, `/admin/ai-content-creator`, `/admin/ai-seo-creator`, `/admin/ai-settings` | Provider, Übersetzung, Content-/SEO-Entwürfe, Prompt-Vorlagen, Quotas, Healthchecks und AI-Monitoring |
| Seiten & Beiträge | `/admin/pages`, `/admin/posts`, `/admin/comments`, `/admin/table-of-contents`, `/admin/site-tables`, `/admin/post-categories`, `/admin/post-tags`, `/admin/hub-sites` | Inhalte, Taxonomien, Kommentare, Inhaltsverzeichnisse, Site-Tabellen und Revisions-/SEO-Prüfungen |
| Medien | `/admin/media` und `?tab=featured|categories|settings` | Bibliothek, Kategorien, Alt-Texte, Bulk-Aktionen, Nutzung, Orphans, Duplikate und Medienoptimierung |
| Benutzer & Gruppen | `/admin/users`, `/admin/groups`, `/admin/roles`, `/admin/user-settings`, `/admin/rbac` | Benutzer, Rollen, Capabilities, Gruppen und Authentifizierung |
| Member Dashboard | `/admin/member-dashboard` und Folgeseiten | Member-Runtime, Widgets, Module, Design, Onboarding, Profilfelder und Preview |
| Abos | `/admin/packages`, `/admin/orders`, `/admin/subscription-settings` | Pakete, Bestellungen, Zuweisungen, Renewal- und Ablaufhinweise |
| Themes & Design | `/admin/themes`, `/admin/theme-editor`, `/admin/theme-explorer`, `/admin/theme-marketplace`, `/admin/menu-editor`, `/admin/landing-page`, `/admin/font-manager`, `/admin/design-settings` | Themes, Menüs, Fonts, Landing Pages und Designwerte |
| SEO | `/admin/seo-dashboard`, `/admin/analytics`, `/admin/seo-audit`, `/admin/seo-meta`, `/admin/seo-social`, `/admin/seo-schema`, `/admin/seo-sitemap`, `/admin/seo-technical`, `/admin/redirect-manager` | SEO-Health, Metadaten, Social, Schema, Sitemap, Technik, Analytics und Redirects |
| Performance | `/admin/performance`, `/admin/performance-cache`, `/admin/performance-media`, `/admin/performance-database`, `/admin/performance-settings`, `/admin/performance-sessions` | Cache, Medien, Datenbank, Sessions, Settings und Laufzeitstatus |
| Recht | `/admin/legal-sites`, `/admin/cookie-manager`, `/admin/data-requests` | Legal Sites, Consent und gebündelte Datenschutzanfragen |
| Sicherheit | `/admin/antispam`, `/admin/firewall`, `/admin/security-audit` | AntiSpam, Firewall und Sicherheits-Audit |
| Plugins | `/admin/plugins`, `/admin/plugin-marketplace` und registrierte Plugin-Unterseiten | Lifecycle, Marketplace und dynamische Plugin-Menüs |
| System | `/admin/settings`, `/admin/backups`, `/admin/updates`, `/admin/cms-logs`, `/admin/mail-settings` | Konfiguration, Backup/Restore, Updates, Betriebslogs und Mail/OAuth2 |
| Info & Diagnose | `/admin/info`, `/admin/documentation`, `/admin/diagnose`, `/admin/monitor-*` | Systeminfo, lokale Doku, Healthchecks, Cron, Disk, Response Time und Warnungen |

## AI Services

AI Services ist ein eigener geschützter Admin-Hauptbereich. Der aktuelle Workflow ist revieworientiert:

- Editor.js-Übersetzungen werden in konservativen Batches verarbeitet und vor Übernahme geprüft.
- Content Creator erzeugt nur Summary-, Outline- oder CTA-Entwürfe.
- SEO Creator liefert eine serverseitig begrenzte Metadatenvorschau.
- Providerzugriffe laufen über Policy, Quota, Retry/Fallback und Readiness.
- externe Cloud-Datenweitergabe muss ausdrücklich erlaubt sein.
- Rohprompts, Secrets und Volltexte bleiben aus dem Monitoring heraus.

Die vollständige Dokumentation steht in [../ai/AI-SERVICES.md](../ai/AI-SERVICES.md).

## Sicherheits- und Request-Grundsätze

- Adminzugriff über `CMS\Auth` und Capability-Prüfungen.
- CSRF-Tokens für jede Zustandsänderung.
- Eingaben serverseitig normalisieren, begrenzen und sanitizen.
- Ausgaben kontextbezogen escapen.
- POST nach erfolgreicher Verarbeitung auf GET umleiten.
- keine schreibenden GET-Requests.
- Sub-Views nur über autorisierte Wrapper laden.
- technische Fehler verständlich anzeigen und Details in sicheren Logs halten.
- keine API-Keys, Sessions oder personenbezogenen Volltexte in Debug-Ausgaben.

## Legacy- und Spezialrouten

Die folgenden Dateien existieren teilweise aus Kompatibilitätsgründen und sind nicht automatisch die führende Oberfläche:

- `backup.php` → `/admin/backups`
- `theme-customizer.php` → `/admin/theme-editor`
- `cookies.php` → `/admin/cookie-manager`
- `data-access.php` und `data-deletion.php` → `/admin/data-requests`
- `fonts-local.php` → `/admin/font-manager`
- `subscriptions.php` → Paket-, Order- und Subscription-Settings
- `system.php` und `system-info.php` → `/admin/settings`, `/admin/info`, `/admin/diagnose`

Bei neuen Links immer die dokumentierte Route und nicht den alten Dateinamen verwenden.

## Relevante Fachdokumente

| Bereich | Dokument |
|---|---|
| Admin-Struktur | [FILESTRUCTURE.md](FILESTRUCTURE.md) |
| Bedienung | [GUIDE.md](GUIDE.md) |
| Plugin-Integration | [PANEL-INTEGRATION.md](PANEL-INTEGRATION.md) |
| Prüfanker | [PRUEF-CHECKLISTE.md](PRUEF-CHECKLISTE.md) |
| AI Services | [../ai/AI-SERVICES.md](../ai/AI-SERVICES.md) |
| Dashboard | [dashboard/DASHBOARD.md](dashboard/DASHBOARD.md) |
| Seiten & Beiträge | [pages-posts/README.md](pages-posts/README.md) |
| Medien | [media/README.md](media/README.md) |
| Benutzer & Gruppen | [users-groups/README.md](users-groups/README.md) |
| Member Dashboard | [member/README.md](member/README.md) |
| Abos | [subscription/SUBSCRIPTION-SYSTEM.md](subscription/SUBSCRIPTION-SYSTEM.md) |
| SEO | [seo/SEO.md](seo/SEO.md) |
| Performance | [performance/PERFORMANCE.md](performance/PERFORMANCE.md) |
| Sicherheit | [security/README.md](security/README.md) |
| System & Betrieb | [system-settings/README.md](system-settings/README.md) |

## Betriebscheck vor Änderungen

Vor einer Admin-Änderung prüfen:

1. Ist die betreffende Route aktuell und nicht nur Legacy?
2. Ist das zuständige Core-Modul aktiviert?
3. Besitzt der Benutzer die richtige Capability?
4. Gibt es einen CSRF-geschützten POST-Ablauf?
5. Wird nach POST auf eine sichere interne Route umgeleitet?
6. Werden leere, Warnungs- und Fehlerzustände korrekt behandelt?
7. Werden sensible Daten aus UI, Logs und Monitoring ferngehalten?
8. Ist die Fach-Dokumentation aktualisiert?
