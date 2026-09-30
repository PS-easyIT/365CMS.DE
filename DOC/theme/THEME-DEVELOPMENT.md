# 365CMS – Projektdokumentation | Abschnitt: Theme-Entwicklung
> **Stand:** 2026-09-26 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-26

## English

A theme change is complete only when the active theme under `CMS/themes/` contains the referenced templates and assets, the admin theme modules can still load it, and public output remains escaped and accessible. Do not use `CMS/themes/` as a place for credentials or deployment data.

### Theme contract (365CMS 3.4)

- **Frame:** `ThemeManager::render()` includes `header.php`, the template and `footer.php`. Templates must not call `get_header()`/`get_footer()` themselves; only `error.php` (included directly by the fatal handler in `index.php`) renders its own frame.
- **Template data:** `page.php` receives `$page` (array, `content` is rendered, sanitised HTML); `blog.php`/`category.php`/`tag.php` (fallback `index.php`) receive `$posts` (objects), `$currentPage`, `$totalPages` (`?p=N`); `blog-single.php` receives `$post` (object); `search.php` receives `$results` (arrays with `_type`, `_type_label`, `slug`, `title`, `meta_description`; plugin hits also `url`), `$query`, `$type` (scope, empty = all), `$sort` (`relevance` or `date`) and `$total`. Results are already filtered (every search word in the visible text) and sorted; an empty query yields no results. Search forms submit `q`, `type` and `sort`. Post links come from `PermalinkService::buildPostUrl()`. There are no `\CMS\CMS`, `PageService`, `PostService` or `ContentHelper` classes, and there is no `ThemeManager::registerMenuLocation()`; menu locations are registered via the `register_menu_locations` filter.
- **CSP:** `header.php` outputs `cms_csp_runtime_tags()` as the first script (the core also adds it as the first `head` action as a safety net). Every inline `<style>`/`<script>` carries `Security::instance()->nonceAttr()`. Inline event handlers, `javascript:` URLs, `eval()` and third-party scripts without `Security::allowCspSources()` are blocked.
- **Hooks:** `before_footer` and `body_end` run once per request. The core fires them around `footer.php`; themes fire `body_end` before `</body>` so that consent, web vitals and PhotoSwipe end up inside the document.
- **Privacy:** Skip Google Fonts when `privacy_use_local_fonts = 1`. Skip theme cookie banners when `CookieConsentService::isManagedExternally()` is true.
- **Manifest:** `theme.json`, `update.json` (`requires_cms`/`min_cms_version` ≥ `3.4.00`), `style.css` and the version constant in `functions.php` carry the same version.
- **Check:** `php TESTS/theme-contract/run.php <theme-root> …` validates the contract statically.

## Deutsch

Eine Theme-Änderung ist erst abgeschlossen, wenn das aktive Theme unter `CMS/themes/` die referenzierten Templates und Assets enthält, die Admin-Theme-Module es weiterhin laden können und öffentliche Ausgaben escaped sowie barrierearm bleiben. Zugangsdaten und Deployment-Daten gehören nicht in `CMS/themes/`.

### Theme-Vertrag (365CMS 3.4)

- **Rahmen:** `ThemeManager::render()` bindet `header.php`, das Template und `footer.php` ein. Templates rufen `get_header()`/`get_footer()` nicht selbst auf; nur `error.php` (vom Fatal-Handler in `index.php` direkt eingebunden) rendert den Rahmen selbst.
- **Template-Daten:** `page.php` erhält `$page` (Array, `content` ist gerendertes, sanitiertes HTML); `blog.php`/`category.php`/`tag.php` (Fallback `index.php`) erhalten `$posts` (Objekte), `$currentPage`, `$totalPages` (`?p=N`); `blog-single.php` erhält `$post` (Objekt); `search.php` erhält `$results` (Arrays mit `_type`, `_type_label`, `slug`, `title`, `meta_description`; Plugin-Treffer zusätzlich `url`), `$query`, `$type` (Scope, leer = alle), `$sort` (`relevance` oder `date`) und `$total`. Die Treffer sind bereits gefiltert (jedes Suchwort im sichtbaren Text) und sortiert; ohne Suchbegriff gibt es keine Treffer. Suchformulare senden `q`, `type` und `sort`. Beitragslinks liefert `PermalinkService::buildPostUrl()`. Klassen `\CMS\CMS`, `PageService`, `PostService` oder `ContentHelper` gibt es nicht, ebenso wenig `ThemeManager::registerMenuLocation()`; Menüpositionen werden über den Filter `register_menu_locations` angemeldet.
- **CSP:** `header.php` gibt `cms_csp_runtime_tags()` als erstes Script aus (der Core ergänzt es zusätzlich als erste `head`-Aktion). Jedes Inline-`<style>`/`<script>` trägt `Security::instance()->nonceAttr()`. Inline-Event-Handler, `javascript:`-URLs, `eval()` und Fremd-Scripts ohne `Security::allowCspSources()` werden blockiert.
- **Hooks:** `before_footer` und `body_end` laufen pro Request nur einmal. Der Core löst sie rund um `footer.php` aus; Themes lösen `body_end` vor `</body>` aus, damit Consent, Web-Vitals und PhotoSwipe im Dokument landen.
- **Datenschutz:** Google Fonts entfallen bei `privacy_use_local_fonts = 1`; Theme-Cookie-Banner entfallen, wenn `CookieConsentService::isManagedExternally()` gilt.
- **Manifest:** `theme.json`, `update.json` (`requires_cms`/`min_cms_version` ≥ `3.4.00`), `style.css` und die Versionskonstante in `functions.php` tragen dieselbe Version.
- **Prüfung:** `php TESTS/theme-contract/run.php <Theme-Wurzel> …` prüft den Vertrag statisch.
