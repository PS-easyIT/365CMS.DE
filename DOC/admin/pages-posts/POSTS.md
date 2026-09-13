# 365CMS – Projektdokumentation | Abschnitt: Admin – Beiträge
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

Post administration is available at `/admin/posts`. The entry point is `CMS/admin/posts.php`; the current implementation is `CMS/admin/modules/posts/PostsModule.php`, supported by `PostsCategoryViewModelBuilder.php`.

The module handles post fields, bilingual values where present, categories, tags, templates, metadata, featured images, publication state, and allowlisted bulk actions. Writes use normalized input and database transactions for multi-record operations. Template metadata is restricted to the configured template definition.

## Deutsch

Die Beitragsverwaltung ist unter `/admin/posts` erreichbar. Der Einstieg liegt in `CMS/admin/posts.php`; die aktuelle Implementierung befindet sich in `CMS/admin/modules/posts/PostsModule.php` und wird durch `PostsCategoryViewModelBuilder.php` unterstützt.

Das Modul verarbeitet Beitragsfelder, vorhandene zweisprachige Werte, Kategorien, Tags, Templates, Metadaten, Beitragsbilder, Veröffentlichungsstatus und erlaubte Sammelaktionen. Eingaben werden normalisiert; Mehrfachänderungen verwenden Datenbanktransaktionen. Template-Metadaten sind auf die konfigurierte Template-Definition begrenzt.
