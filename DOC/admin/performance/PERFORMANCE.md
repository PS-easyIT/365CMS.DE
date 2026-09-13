# 365CMS – Projektdokumentation | Abschnitt: Admin – Performance
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

Performance administration is available at `/admin/performance`. The main entry is `CMS/admin/performance.php`; section entry points cover cache, database, media, page, sessions, and settings. The current section module is `CMS/admin/modules/seo/PerformanceModule.php`.

The module exposes read-only diagnostics and explicitly handled maintenance actions. Use the section-specific controls and review warnings before changing cache or runtime settings. A missing metric or unavailable dependency must remain a bounded diagnostic result.

## Deutsch

Die Performance-Verwaltung ist unter `/admin/performance` erreichbar. Der Haupteinstieg liegt in `CMS/admin/performance.php`; Abschnittseinstiege behandeln Cache, Datenbank, Medien, Seiten, Sessions und Einstellungen. Das aktuelle Abschnittsmodul ist `CMS/admin/modules/seo/PerformanceModule.php`.

Das Modul stellt lesende Diagnosen und ausdrücklich behandelte Wartungsaktionen bereit. Verwenden Sie die bereichsspezifischen Steuerungen und prüfen Sie Warnungen vor Änderungen an Cache oder Runtime-Einstellungen. Fehlende Messwerte oder Abhängigkeiten müssen als begrenzte Diagnose erscheinen.
