# 365CMS – Admin-Dateistruktur

> **Stand:** 2026-09-06 | **Version:** 3.4.00 | **Status:** Aktuelle technische Referenz

## Inhaltsverzeichnis

- [Architekturprinzip](#architekturprinzip)
- [Verzeichnisstruktur](#verzeichnisstruktur)
- [Entry-Points, Module und Views](#entry-points-module-und-views)
- [Routing und aktive Slugs](#routing-und-aktive-slugs)
- [Admin-Shell und Sidebar](#admin-shell-und-sidebar)
- [Standardaufbau eines Entry-Points](#standardaufbau-eines-entry-points)
- [Wrappergebundene Sub-Views](#wrappergebundene-sub-views)
- [Sicherheitsmuster](#sicherheitsmuster)
- [AI-Services-Struktur](#ai-services-struktur)
- [Legacy-Dateien](#legacy-dateien)

## Architekturprinzip

Der Admin ist eine Schichtenstruktur, kein flaches Sammelbecken:

```text
Route / Entry-Point
        |
        v
Auth + Request + CSRF + Action
        |
        v
Admin-Modul / Core-Service
        |
        v
vorbereitete Daten
        |
        v
View / Partial / Redirect
```

Businesslogik gehört in Module und Services. Views rendern vorbereitete Daten und führen keine eigenständigen Zustandsänderungen aus.

## Verzeichnisstruktur

```text
CMS/admin/
├── index.php
├── pages.php / posts.php / comments.php
├── media.php
├── users.php / groups.php / roles.php / rbac.php
├── member-dashboard*.php
├── ai-*.php
├── packages.php / orders.php / subscription-settings.php
├── themes.php / theme-editor.php / theme-explorer.php
├── menu-editor.php / landing-page.php / font-manager.php
├── seo*.php / analytics.php / redirect-manager.php
├── performance*.php
├── legal-sites.php / cookie-manager.php / data-requests.php
├── antispam.php / firewall.php / security-audit.php
├── plugins.php / plugin-marketplace.php
├── settings.php / backups.php / updates.php / cms-logs.php
├── info.php / documentation.php / diagnose.php
├── monitor-*.php
├── modules/
├── views/
└── partials/
```

Die Liste ist bewusst nach Funktionsfamilien gekürzt; die tatsächliche Dateiliste kann zusätzliche Spezial- und Kompatibilitäts-Entry-Points enthalten.

## Entry-Points, Module und Views

### `CMS/admin/*.php`

Entry-Points repräsentieren Routes. Sie laden Konfiguration und Autoloader, prüfen Zugriff, verarbeiten Request-Aktionen, laden Module und übergeben Daten an Shells oder Views. Neue Entry-Points sollen klein bleiben.

### `CMS/admin/modules/`

Fachlogik für System, AI, SEO, Performance, Recht, Sicherheit, Medien, Member und weitere Bereiche. Module validieren und speichern Daten über Core-Services.

### `CMS/admin/views/`

Rendernde Templates, gruppiert nach Fachbereich, zum Beispiel `views/system/`, `views/seo/`, `views/performance/`, `views/member/` und `views/ai/`. Views sind keine frei erreichbaren URLs.

### `CMS/admin/partials/`

Gemeinsame Layout- und Navigationsteile:

- `header.php`;
- `sidebar.php`;
- `footer.php`;
- Section-Shells und wiederverwendbare UI-Teile.

Die Sidebar in `partials/sidebar.php` ist die führende Quelle für die sichtbare Core-Navigation.

## Routing und aktive Slugs

| Fachbereich | Aktive Beispiele |
|---|---|
| Dashboard | `/admin` |
| AI | `/admin/ai-services`, `/admin/ai-translation`, `/admin/ai-content-creator`, `/admin/ai-seo-creator`, `/admin/ai-settings` |
| Inhalt | `/admin/pages`, `/admin/posts`, `/admin/comments`, `/admin/post-categories`, `/admin/post-tags` |
| Benutzer | `/admin/users`, `/admin/groups`, `/admin/roles`, `/admin/rbac`, `/admin/user-settings` |
| Themes | `/admin/themes`, `/admin/theme-editor`, `/admin/theme-explorer`, `/admin/theme-marketplace` |
| SEO | `/admin/seo-dashboard`, `/admin/seo-meta`, `/admin/seo-sitemap`, `/admin/redirect-manager` |
| System | `/admin/settings`, `/admin/backups`, `/admin/updates`, `/admin/cms-logs` |
| Diagnose | `/admin/info`, `/admin/diagnose`, `/admin/monitor-health-check` |

Query-Tabs wie `/admin/media?tab=settings` sind fachliche Unterzustände derselben Route und keine separaten Entry-Points.

## Admin-Shell und Sidebar

Die Sidebar:

1. normalisiert den aktiven Seitenslug;
2. lädt Core-Menüs;
3. löst `cms_admin_menu` aus;
4. liest `get_registered_admin_menus()`;
5. sortiert Plugin-Gruppen labelbasiert;
6. löst gleiche numerische Positionen kollisionsfrei;
7. rendert Gruppen, Unterpunkte und Plugin-Einträge;
8. hält lange Menüs scrollbar.

Plugin-Callbacks, die nur Inhaltsmarkup ausgeben, werden vom Core in den gemeinsamen `page-body`-/`container-xl cms-plugin-admin-content`-Wrapper eingebettet. Vollständige Layouts und bereits vorhandene `page-body`-Strukturen werden nicht doppelt gewrappt.

## Standardaufbau eines Entry-Points

```php
<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$csrfToken = Security::instance()->generateToken('my_action');

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
    if (!Security::instance()->verifyToken(
        (string) ($_POST['csrf_token'] ?? ''),
        'my_action'
    )) {
        // Fehlerzustand melden; keine Mutation ausführen.
    }
}
```

Der konkrete Bootstrap kann je nach Core-Wrapper variieren. Entscheidend sind Guard, Auth, Capability, CSRF, Validierung, sichere Mutation und Redirect.

## Wrappergebundene Sub-Views

Sub-Views prüfen zusätzlich zu `ABSPATH` eine Kontextkonstante ihres Wrappers, zum Beispiel:

- `CMS_ADMIN_AI_VIEW`;
- `CMS_ADMIN_SEO_VIEW`;
- `CMS_ADMIN_PERFORMANCE_VIEW`;
- `CMS_ADMIN_MEMBER_VIEW`;
- `CMS_ADMIN_SYSTEM_VIEW`.

Bei fehlendem Kontext sofort `exit;`. Businesslogik, Auth und CSRF bleiben im Entry-Point oder Wrapper.

## Sicherheitsmuster

- `declare(strict_types=1);`
- `ABSPATH`-Guard
- `CMS\Auth` für Login/Adminstatus
- Capabilities für fachliche Berechtigungen
- `CMS\Security` für Tokens
- keine Zustandsänderung über GET
- Eingaben sanitizen und typisieren
- SQL nur parametrisiert
- Ausgaben escapen
- sichere interne Redirects
- keine Secrets oder Volltexte in Logs
- Fehleroberflächen ohne interne Exceptiondetails

Für AI kommen Provider-Policy, Egress- und Locale-Gates, Quotas, begrenzte Retries, Fallback-Prüfungen, strukturierte JSON-Verträge und Reviewpflicht hinzu.

## AI-Services-Struktur

```text
CMS/admin/
├── ai-page.php
├── ai-services.php
├── ai-translation.php
├── ai-content-creator.php
├── ai-seo-creator.php
├── ai-settings.php
├── ai-translate-editorjs.php
├── ai-generate-seo-metadata.php
├── modules/system/
│   ├── AiServicesModule.php
│   ├── AiEditorJsTranslationModule.php
│   └── AiEditorJsSeoMetadataModule.php
└── views/system/ai-services.php

CMS/core/Services/AI/
├── AiSettingsService.php
├── AiProviderFactory.php
├── AiProviderGateway.php
├── AiProviderPolicyService.php
├── AiExecutionService.php
├── AiQuotaService.php
├── QuotaAwareAiProvider.php
├── EditorJsTranslationPipeline.php
├── ContentDraftGenerationPipeline.php
├── SeoMetadataGenerationPipeline.php
└── Providers/
    ├── MockAiProvider.php
    ├── OllamaAiProvider.php
    └── AzureOpenAiProvider.php
```

Die AI-Dokumentation liegt unter [../ai/AI-SERVICES.md](../ai/AI-SERVICES.md).

## Legacy-Dateien

Nicht jede PHP-Datei unter `CMS/admin/` ist eine führende Route. Beispiele:

- `backup.php`, `theme-customizer.php`, `cookies.php`;
- `data-access.php`, `data-deletion.php`, `privacy-requests.php`;
- `fonts-local.php`, `subscriptions.php`;
- `system.php`, `system-info.php`.

Vor Änderungen prüfen, ob eine Datei Redirect-, Kompatibilitäts- oder aktive Fachlogik enthält. Neue Dokumentation verweist auf die sprechende aktuelle Route.
