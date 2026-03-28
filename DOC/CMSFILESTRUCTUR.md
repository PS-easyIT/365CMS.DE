# 365CMS – Fileinventar

## Überblick

Dieses Dokument ist die **kanonische Scope-Liste** für die Vollprüfung des CMS-Stands vom **28.03.2026**.

- **Scope:** `365CMS.DE/CMS`
- **Ausgeschlossen:** `CMS/vendor/`, `CMS/themes/`
- **Aus `CMS/assets/` berücksichtigt:** nur `assets/css/` und `assets/js/`
- **Verifizierter Gesamtbestand:** `467` Dateien
- **Verifizierte Quelle:** `DOC/_cms_inventory_current.txt`

## Verifizierte Bestandszahlen

| Bereich | Dateien |
|---|---:|
| Root-Entrypoints | 7 |
| `assets/css/` | 9 |
| `assets/js/` | 30 |
| `admin/` | 243 |
| `config/` | 4 |
| `core/` | 118 |
| `includes/` | 10 |
| `lang/` | 2 |
| `logs/` | 2 |
| `member/` | 17 |
| `plugins/` | 14 |
| `uploads/` | 2 |

## Kurzbeschreibung der Bereiche

| Bereich | Zweck |
|---|---|
| `CMS/` | Root-Entrypoints, Bootstrap, Installer, Laufzeit-Metadaten |
| `CMS/assets/css/` | Stylesheets für Admin, Hub, Frontend, Consent und Member-Bereich |
| `CMS/assets/js/` | JavaScript für Admin-Workflows, Editoren, Consent, Marketplace, Hub, SEO, Users und Member-Bereich |
| `CMS/admin/` | Backend-Einstiegsseiten, Module, Partials und Views |
| `CMS/config/` | Laufzeit- und Medienkonfiguration |
| `CMS/core/` | Kernklassen, Routing, Services, Auth, SEO, Media und Infrastruktur |
| `CMS/includes/` | Globale Hilfsfunktionen und Runtime-Helper |
| `CMS/lang/` | Sprachdateien |
| `CMS/logs/` | Schutz-/Platzhalterdateien für Logging |
| `CMS/member/` | Member-Dashboard, Profile, Medien, Nachrichten und Privatsphäre |
| `CMS/plugins/` | Eingebettete CMS-Plugins, aktuell insbesondere `cms-importer` |
| `CMS/uploads/` | Schutz-/Platzhalterdateien im Upload-Baum |

## Vollständige Dateiliste im Prüfscope

### Root-Dateien

- `.htaccess`
- `config.php`
- `cron.php`
- `index.php`
- `install.php`
- `orders.php`
- `update.json`

### Assets – CSS

- `assets/css/admin-hub-site-edit.css`
- `assets/css/admin-hub-template-edit.css`
- `assets/css/admin-hub-template-editor.css`
- `assets/css/admin-tabler.css`
- `assets/css/admin.css`
- `assets/css/cms-cookie-consent.css`
- `assets/css/hub-sites.css`
- `assets/css/main.css`
- `assets/css/member-dashboard.css`

### Assets – JavaScript

- `assets/js/admin-comments.js`
- `assets/js/admin-content-editor.js`
- `assets/js/admin-cookie-manager.js`
- `assets/js/admin-data-requests.js`
- `assets/js/admin-font-manager.js`
- `assets/js/admin-grid.js`
- `assets/js/admin-hub-site-edit.js`
- `assets/js/admin-hub-sites.js`
- `assets/js/admin-hub-template-edit.js`
- `assets/js/admin-hub-template-editor.js`
- `assets/js/admin-legal-sites.js`
- `assets/js/admin-media-integrations.js`
- `assets/js/admin-menu-editor.js`
- `assets/js/admin-pages.js`
- `assets/js/admin-plugin-marketplace.js`
- `assets/js/admin-plugins.js`
- `assets/js/admin-seo-editor.js`
- `assets/js/admin-seo-redirects.js`
- `assets/js/admin-site-tables.js`
- `assets/js/admin-theme-explorer.js`
- `assets/js/admin-theme-marketplace.js`
- `assets/js/admin-user-groups.js`
- `assets/js/admin-users.js`
- `assets/js/admin.js`
- `assets/js/cookieconsent-init.js`
- `assets/js/editor-init.js`
- `assets/js/gridjs-init.js`
- `assets/js/member-dashboard.js`
- `assets/js/photoswipe-init.js`
- `assets/js/web-vitals.js`

### Admin

- `admin/analytics.php`
- `admin/antispam.php`
- `admin/backups.php`
- `admin/comments.php`
- `admin/cookie-manager.php`
- `admin/data-requests.php`
- `admin/deletion-requests.php`
- `admin/design-settings.php`
- `admin/diagnose.php`
- `admin/documentation.php`
- `admin/error-report.php`
- `admin/firewall.php`
- `admin/font-manager.php`
- `admin/groups.php`
- `admin/hub-sites.php`
- `admin/index.php`
- `admin/info.php`
- `admin/landing-page.php`
- `admin/legal-sites.php`
- `admin/mail-settings.php`
- `admin/media.php`
- `admin/member-dashboard-design.php`
- `admin/member-dashboard-frontend-modules.php`
- `admin/member-dashboard-general.php`
- `admin/member-dashboard-notifications.php`
- `admin/member-dashboard-onboarding.php`
- `admin/member-dashboard-page.php`
- `admin/member-dashboard-plugin-widgets.php`
- `admin/member-dashboard-profile-fields.php`
- `admin/member-dashboard-widgets.php`
- `admin/member-dashboard.php`
- `admin/menu-editor.php`
- `admin/modules.php`
- `admin/modules/comments/CommentsModule.php`
- `admin/modules/dashboard/DashboardModule.php`
- `admin/modules/hub/HubSitesModule.php`
- `admin/modules/hub/HubTemplateProfileCatalog.php`
- `admin/modules/hub/HubTemplateProfileManager.php`
- `admin/modules/landing/LandingPageModule.php`
- `admin/modules/legal/CookieManagerModule.php`
- `admin/modules/legal/DeletionRequestsModule.php`
- `admin/modules/legal/LegalSitesModule.php`
- `admin/modules/legal/PrivacyRequestsModule.php`
- `admin/modules/media/MediaModule.php`
- `admin/modules/member/MemberDashboardModule.php`
- `admin/modules/menus/MenuEditorModule.php`
- `admin/modules/pages/PagesModule.php`
- `admin/modules/plugins/PluginMarketplaceModule.php`
- `admin/modules/plugins/PluginsModule.php`
- `admin/modules/posts/PostsCategoryViewModelBuilder.php`
- `admin/modules/posts/PostsModule.php`
- `admin/modules/security/AntispamModule.php`
- `admin/modules/security/FirewallModule.php`
- `admin/modules/security/SecurityAuditModule.php`
- `admin/modules/seo/AnalyticsModule.php`
- `admin/modules/seo/PerformanceModule.php`
- `admin/modules/seo/RedirectManagerModule.php`
- `admin/modules/seo/SeoDashboardModule.php`
- `admin/modules/seo/SeoSuiteModule.php`
- `admin/modules/settings/SettingsModule.php`
- `admin/modules/subscriptions/OrdersModule.php`
- `admin/modules/subscriptions/PackagesModule.php`
- `admin/modules/subscriptions/SubscriptionSettingsModule.php`
- `admin/modules/system/BackupsModule.php`
- `admin/modules/system/DocumentationCatalog.php`
- `admin/modules/system/DocumentationGithubZipSync.php`
- `admin/modules/system/DocumentationGitSync.php`
- `admin/modules/system/DocumentationModule.php`
- `admin/modules/system/DocumentationRenderer.php`
- `admin/modules/system/DocumentationSyncDownloader.php`
- `admin/modules/system/DocumentationSyncEnvironment.php`
- `admin/modules/system/DocumentationSyncFilesystem.php`
- `admin/modules/system/DocumentationSyncService.php`
- `admin/modules/system/MailSettingsModule.php`
- `admin/modules/system/ModulesModule.php`
- `admin/modules/system/SupportModule.php`
- `admin/modules/system/SystemInfoModule.php`
- `admin/modules/system/UpdatesModule.php`
- `admin/modules/tables/TablesModule.php`
- `admin/modules/themes/DesignSettingsModule.php`
- `admin/modules/themes/FontManagerModule.php`
- `admin/modules/themes/ThemeEditorModule.php`
- `admin/modules/themes/ThemeMarketplaceModule.php`
- `admin/modules/themes/ThemesModule.php`
- `admin/modules/toc/TocModule.php`
- `admin/modules/users/GroupsModule.php`
- `admin/modules/users/RolesModule.php`
- `admin/modules/users/UserSettingsModule.php`
- `admin/modules/users/UsersModule.php`
- `admin/monitor-cron-status.php`
- `admin/monitor-disk-usage.php`
- `admin/monitor-email-alerts.php`
- `admin/monitor-health-check.php`
- `admin/monitor-response-time.php`
- `admin/monitor-scheduled-tasks.php`
- `admin/not-found-monitor.php`
- `admin/orders.php`
- `admin/packages.php`
- `admin/pages.php`
- `admin/partials/footer.php`
- `admin/partials/header.php`
- `admin/partials/post-action-shell.php`
- `admin/partials/redirect-alias-shell.php`
- `admin/partials/section-page-shell.php`
- `admin/partials/sidebar.php`
- `admin/performance-cache.php`
- `admin/performance-database.php`
- `admin/performance-media.php`
- `admin/performance-page.php`
- `admin/performance-sessions.php`
- `admin/performance-settings.php`
- `admin/performance.php`
- `admin/plugin-marketplace.php`
- `admin/plugins.php`
- `admin/post-categories.php`
- `admin/post-tags.php`
- `admin/posts.php`
- `admin/privacy-requests.php`
- `admin/redirect-manager.php`
- `admin/roles.php`
- `admin/security-audit.php`
- `admin/seo-audit.php`
- `admin/seo-dashboard.php`
- `admin/seo-meta.php`
- `admin/seo-page.php`
- `admin/seo-schema.php`
- `admin/seo-sitemap.php`
- `admin/seo-social.php`
- `admin/seo-technical.php`
- `admin/settings.php`
- `admin/site-tables.php`
- `admin/subscription-settings.php`
- `admin/support.php`
- `admin/system-info.php`
- `admin/system-monitor-page.php`
- `admin/table-of-contents.php`
- `admin/theme-editor.php`
- `admin/theme-explorer.php`
- `admin/theme-marketplace.php`
- `admin/theme-settings.php`
- `admin/themes.php`
- `admin/updates.php`
- `admin/user-settings.php`
- `admin/users.php`
- `admin/views/comments/list.php`
- `admin/views/dashboard/index.php`
- `admin/views/hub/edit.php`
- `admin/views/hub/list.php`
- `admin/views/hub/template-edit.php`
- `admin/views/hub/template-edit/main-column.php`
- `admin/views/hub/template-edit/sidebar-column.php`
- `admin/views/hub/templates.php`
- `admin/views/landing/page.php`
- `admin/views/legal/cookies.php`
- `admin/views/legal/data-requests.php`
- `admin/views/legal/deletion-requests.php`
- `admin/views/legal/privacy-requests.php`
- `admin/views/legal/sites.php`
- `admin/views/media/categories.php`
- `admin/views/media/library.php`
- `admin/views/media/settings.php`
- `admin/views/member/dashboard.php`
- `admin/views/member/design.php`
- `admin/views/member/frontend-modules.php`
- `admin/views/member/general.php`
- `admin/views/member/notifications.php`
- `admin/views/member/onboarding.php`
- `admin/views/member/plugin-widgets.php`
- `admin/views/member/profile-fields.php`
- `admin/views/member/subnav.php`
- `admin/views/member/widgets.php`
- `admin/views/menus/editor.php`
- `admin/views/pages/edit.php`
- `admin/views/pages/list.php`
- `admin/views/partials/content-advanced-seo-panel.php`
- `admin/views/partials/content-preview-card.php`
- `admin/views/partials/content-readability-card.php`
- `admin/views/partials/content-seo-score-panel.php`
- `admin/views/partials/empty-table-row.php`
- `admin/views/partials/featured-image-picker.php`
- `admin/views/partials/flash-alert.php`
- `admin/views/partials/section-subnav.php`
- `admin/views/performance/cache.php`
- `admin/views/performance/database.php`
- `admin/views/performance/media.php`
- `admin/views/performance/sessions.php`
- `admin/views/performance/settings.php`
- `admin/views/performance/subnav.php`
- `admin/views/plugins/list.php`
- `admin/views/plugins/marketplace.php`
- `admin/views/posts/categories.php`
- `admin/views/posts/edit.php`
- `admin/views/posts/list.php`
- `admin/views/posts/tags.php`
- `admin/views/security/antispam.php`
- `admin/views/security/audit.php`
- `admin/views/security/firewall.php`
- `admin/views/seo/analytics.php`
- `admin/views/seo/audit.php`
- `admin/views/seo/dashboard.php`
- `admin/views/seo/meta.php`
- `admin/views/seo/not-found.php`
- `admin/views/seo/performance.php`
- `admin/views/seo/redirects.php`
- `admin/views/seo/schema.php`
- `admin/views/seo/sitemap.php`
- `admin/views/seo/social.php`
- `admin/views/seo/subnav.php`
- `admin/views/seo/technical.php`
- `admin/views/settings/general.php`
- `admin/views/subscriptions/orders.php`
- `admin/views/subscriptions/packages.php`
- `admin/views/subscriptions/settings.php`
- `admin/views/system/backups.php`
- `admin/views/system/cron-status.php`
- `admin/views/system/diagnose.php`
- `admin/views/system/disk-usage.php`
- `admin/views/system/documentation.php`
- `admin/views/system/email-alerts.php`
- `admin/views/system/health-check.php`
- `admin/views/system/info.php`
- `admin/views/system/mail-settings.php`
- `admin/views/system/modules.php`
- `admin/views/system/response-time.php`
- `admin/views/system/scheduled-tasks.php`
- `admin/views/system/subnav.php`
- `admin/views/system/support.php`
- `admin/views/system/updates.php`
- `admin/views/tables/edit.php`
- `admin/views/tables/list.php`
- `admin/views/tables/settings.php`
- `admin/views/themes/customizer-missing.php`
- `admin/views/themes/editor.php`
- `admin/views/themes/fonts.php`
- `admin/views/themes/list.php`
- `admin/views/themes/marketplace.php`
- `admin/views/themes/settings.php`
- `admin/views/toc/settings.php`
- `admin/views/users/edit.php`
- `admin/views/users/groups.php`
- `admin/views/users/list.php`
- `admin/views/users/roles.php`
- `admin/views/users/settings.php`

### Config

- `config/.htaccess`
- `config/app.php`
- `config/media-meta.json`
- `config/media-settings.json`

### Core

- `core/Api.php`
- `core/AuditLogger.php`
- `core/Auth.php`
- `core/Auth/AuthManager.php`
- `core/Auth/LDAP/LdapAuthProvider.php`
- `core/Auth/MFA/BackupCodesManager.php`
- `core/Auth/MFA/TotpAdapter.php`
- `core/Auth/Passkey/WebAuthnAdapter.php`
- `core/autoload.php`
- `core/Bootstrap.php`
- `core/CacheManager.php`
- `core/Container.php`
- `core/Contracts/CacheInterface.php`
- `core/Contracts/DatabaseInterface.php`
- `core/Contracts/LoggerInterface.php`
- `core/Database.php`
- `core/Debug.php`
- `core/Hooks.php`
- `core/Http/Client.php`
- `core/Json.php`
- `core/Logger.php`
- `core/Member/PluginDashboardRegistry.php`
- `core/MigrationManager.php`
- `core/PageManager.php`
- `core/PluginManager.php`
- `core/Router.php`
- `core/Routing/AdminRouter.php`
- `core/Routing/ApiRouter.php`
- `core/Routing/MemberRouter.php`
- `core/Routing/PublicRouter.php`
- `core/Routing/ThemeArchiveRepository.php`
- `core/Routing/ThemeRouter.php`
- `core/SchemaManager.php`
- `core/Security.php`
- `core/Services/AnalyticsService.php`
- `core/Services/AzureMailTokenProvider.php`
- `core/Services/BackupService.php`
- `core/Services/CommentService.php`
- `core/Services/ContentLocalizationService.php`
- `core/Services/CookieConsentService.php`
- `core/Services/CoreModuleService.php`
- `core/Services/CoreWebVitalsService.php`
- `core/Services/DashboardService.php`
- `core/Services/EditorJs/EditorJsAssetService.php`
- `core/Services/EditorJs/EditorJsImageLibraryService.php`
- `core/Services/EditorJs/EditorJsMediaService.php`
- `core/Services/EditorJs/EditorJsRemoteMediaService.php`
- `core/Services/EditorJs/EditorJsRequestGuard.php`
- `core/Services/EditorJs/EditorJsSanitizer.php`
- `core/Services/EditorJs/EditorJsUploadService.php`
- `core/Services/EditorJsRenderer.php`
- `core/Services/EditorJsService.php`
- `core/Services/EditorService.php`
- `core/Services/ErrorReportService.php`
- `core/Services/FeatureUsageService.php`
- `core/Services/FeedService.php`
- `core/Services/FileUploadService.php`
- `core/Services/GraphApiService.php`
- `core/Services/ImageService.php`
- `core/Services/IndexingService.php`
- `core/Services/JwtService.php`
- `core/Services/Landing/LandingDefaultsProvider.php`
- `core/Services/Landing/LandingFeatureService.php`
- `core/Services/Landing/LandingHeaderService.php`
- `core/Services/Landing/LandingPluginService.php`
- `core/Services/Landing/LandingRepository.php`
- `core/Services/Landing/LandingSanitizer.php`
- `core/Services/Landing/LandingSectionProfileService.php`
- `core/Services/Landing/LandingSectionService.php`
- `core/Services/LandingPageService.php`
- `core/Services/MailLogService.php`
- `core/Services/MailQueueService.php`
- `core/Services/MailService.php`
- `core/Services/Media/ImageProcessor.php`
- `core/Services/Media/MediaRepository.php`
- `core/Services/Media/UploadHandler.php`
- `core/Services/MediaDeliveryService.php`
- `core/Services/MediaService.php`
- `core/Services/MemberService.php`
- `core/Services/MessageService.php`
- `core/Services/OpcacheWarmupService.php`
- `core/Services/PdfService.php`
- `core/Services/PermalinkService.php`
- `core/Services/PurifierService.php`
- `core/Services/RedirectService.php`
- `core/Services/SearchService.php`
- `core/Services/SEO/SeoAnalyticsRenderer.php`
- `core/Services/SEO/SeoAuditService.php`
- `core/Services/SEO/SeoHeadRenderer.php`
- `core/Services/SEO/SeoMetaRepository.php`
- `core/Services/SEO/SeoMetaService.php`
- `core/Services/SEO/SeoSchemaRenderer.php`
- `core/Services/SEO/SeoSettingsStore.php`
- `core/Services/SEO/SeoSitemapService.php`
- `core/Services/SeoAnalysisService.php`
- `core/Services/SEOService.php`
- `core/Services/SettingsService.php`
- `core/Services/SitemapService.php`
- `core/Services/SiteTable/SiteTableDisplaySettings.php`
- `core/Services/SiteTable/SiteTableHubRenderer.php`
- `core/Services/SiteTable/SiteTableRepository.php`
- `core/Services/SiteTable/SiteTableTableRenderer.php`
- `core/Services/SiteTable/SiteTableTemplateRegistry.php`
- `core/Services/SiteTableService.php`
- `core/Services/StatusService.php`
- `core/Services/SystemService.php`
- `core/Services/ThemeCustomizer.php`
- `core/Services/TrackingService.php`
- `core/Services/TranslationService.php`
- `core/Services/UpdateService.php`
- `core/Services/UserService.php`
- `core/SubscriptionManager.php`
- `core/TableOfContents.php`
- `core/ThemeManager.php`
- `core/Totp.php`
- `core/VendorRegistry.php`
- `core/Version.php`
- `core/WP_Error.php`

### Includes

- `includes/functions.php`
- `includes/functions/admin-menu.php`
- `includes/functions/escaping.php`
- `includes/functions/mail.php`
- `includes/functions/options-runtime.php`
- `includes/functions/redirects-auth.php`
- `includes/functions/roles.php`
- `includes/functions/translation.php`
- `includes/functions/wordpress-compat.php`
- `includes/subscription-helpers.php`

### Lang

- `lang/de.yaml`
- `lang/en.yaml`

### Logs

- `logs/.gitignore`
- `logs/.htaccess`

### Member

- `member/dashboard.php`
- `member/favorites.php`
- `member/includes/bootstrap.php`
- `member/includes/class-member-controller.php`
- `member/media.php`
- `member/messages.php`
- `member/notifications.php`
- `member/partials/alerts.php`
- `member/partials/footer.php`
- `member/partials/header.php`
- `member/partials/plugin-not-found.php`
- `member/partials/sidebar.php`
- `member/plugin-section.php`
- `member/privacy.php`
- `member/profile.php`
- `member/security.php`
- `member/subscription.php`

### Plugins

- `plugins/cms-importer/admin/log.php`
- `plugins/cms-importer/admin/page.php`
- `plugins/cms-importer/assets/css/importer.css`
- `plugins/cms-importer/assets/js/importer.js`
- `plugins/cms-importer/cms-importer.php`
- `plugins/cms-importer/includes/class-admin.php`
- `plugins/cms-importer/includes/class-importer.php`
- `plugins/cms-importer/includes/class-xml-parser.php`
- `plugins/cms-importer/includes/trait-admin-cleanup.php`
- `plugins/cms-importer/includes/trait-importer-preview.php`
- `plugins/cms-importer/includes/trait-importer-reporting.php`
- `plugins/cms-importer/readme.txt`
- `plugins/cms-importer/reports/EXAMPLE_meta-report.md`
- `plugins/cms-importer/update.json`

### Uploads

- `uploads/.gitkeep`
- `uploads/.htaccess`

## Hinweis zur Pflege

Wenn sich der Prüfscope ändert, müssen **Zählung**, **Bereichstabelle** und **Dateiliste** gemeinsam aktualisiert werden. Audit- und ToDo-Dokumente sollten diese Liste nur referenzieren oder aus derselben verifizierten Quelle ableiten.
