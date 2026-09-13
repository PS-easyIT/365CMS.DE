# 365CMS – Projektdokumentation | Abschnitt: Admin – Seiten
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

Page administration is available at `/admin/pages`. The request entry is `CMS/admin/pages.php`; page behavior is implemented by `CMS/admin/modules/pages/PagesModule.php`. The module supports normalized page data and allowlisted bulk actions, including transactional processing where required.

Use the editor and bulk controls in the admin UI. The module validates identifiers, action names, page fields, and content before persistence. Invalid or unsupported actions return an explicit failure result instead of being silently ignored.

## Deutsch

Die Seitenverwaltung ist unter `/admin/pages` erreichbar. Der Einstieg liegt in `CMS/admin/pages.php`; die Fachlogik befindet sich in `CMS/admin/modules/pages/PagesModule.php`. Das Modul verarbeitet normalisierte Seitendaten und erlaubte Sammelaktionen bei Bedarf transaktional.

Verwenden Sie Editor und Sammelaktionen der Admin-Oberfläche. IDs, Aktionsnamen, Seitenfelder und Inhalte werden vor der Speicherung geprüft. Ungültige oder nicht unterstützte Aktionen führen zu einem expliziten Fehler und werden nicht stillschweigend ignoriert.
