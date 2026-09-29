# 365CMS – Projektdokumentation | Abschnitt: Admin – SEO

## English

The SEO administration is implemented by `SeoDashboardModule.php` and `SeoSuiteModule.php` in `CMS/admin/modules/seo/`. Supporting entry points include `CMS/admin/seo-dashboard.php`, `CMS/admin/seo-audit.php`, `CMS/admin/seo-meta.php`, `CMS/admin/ai-seo-creator.php`, and `CMS/admin/ai-generate-seo-metadata.php`.

The SEO views cover dashboard, metadata, schema, social defaults, technical settings, sitemap, audit, analytics, redirects, and not-found monitoring. Supported writes include metadata templates/defaults, sitemap and robots settings, analytics settings, audit items, and indexing submissions. AI-generated SEO values are review drafts and must not be assumed to be published automatically.

The sitemap bundle consists of `sitemap.xml` (index), `pages.xml`, `posts.xml`, `plugins.xml`, `images.xml` and `news.xml`. `plugins.xml` contains public plugin pages that active plugins report through the `cms_sitemap_entries` filter; it is only written when at least one entry exists. The schema type selection offers only types the JSON-LD renderer outputs (posts: `Article`, `BlogPosting`, `NewsArticle`; pages: `WebPage`, `Article`, `Organization`); stored legacy values fall back to the content type default.

## Deutsch

Die SEO-Administration wird durch `SeoDashboardModule.php` und `SeoSuiteModule.php` unter `CMS/admin/modules/seo/` implementiert. Zugehörige Einstiege sind `CMS/admin/seo-dashboard.php`, `CMS/admin/seo-audit.php`, `CMS/admin/seo-meta.php`, `CMS/admin/ai-seo-creator.php` und `CMS/admin/ai-generate-seo-metadata.php`.

Die SEO-Views behandeln Dashboard, Metadaten, Schema, Social Defaults, technische Einstellungen, Sitemap, Audit, Analytics, Redirects und Not-Found-Monitoring. Unterstützte Schreibvorgänge umfassen Metadaten-Templates/-Defaults, Sitemap- und Robots-Einstellungen, Analytics-Einstellungen, Audit-Einträge und Indexierungsübermittlungen. AI-generierte SEO-Werte sind Review-Entwürfe und werden nicht automatisch als veröffentlicht vorausgesetzt.

Das Sitemap-Bundle besteht aus `sitemap.xml` (Index), `pages.xml`, `posts.xml`, `plugins.xml`, `images.xml` und `news.xml`. `plugins.xml` enthält öffentliche Plugin-Seiten, die aktive Plugins über den Filter `cms_sitemap_entries` melden, und wird nur geschrieben, wenn mindestens ein Eintrag vorliegt. Die Schema-Auswahl bietet nur Typen an, die der JSON-LD-Renderer ausgibt (Beiträge: `Article`, `BlogPosting`, `NewsArticle`; Seiten: `WebPage`, `Article`, `Organization`); gespeicherte Altwerte fallen auf den Standard des Inhaltstyps zurück.
