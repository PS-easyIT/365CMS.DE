# 365CMS – Projektdokumentation | Abschnitt: Admin – Hub Sites
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

Hub-site administration is handled by the hub module and its list, edit, template, and template-edit views. The current implementation is under `CMS/admin/modules/hub/` and `CMS/admin/views/hub/`.

Use the module-provided forms for hub records and templates. Values are normalized before persistence, and template definitions are rendered by the corresponding view rather than by ad-hoc output in the documentation or entry script.

## Deutsch

Die Hub-Site-Verwaltung wird durch das Hub-Modul sowie die Listen-, Bearbeitungs-, Template- und Template-Bearbeitungsansichten umgesetzt. Die aktuelle Implementierung liegt unter `CMS/admin/modules/hub/` und `CMS/admin/views/hub/`.

Verwenden Sie die Formulare des Moduls für Hub-Datensätze und Templates. Werte werden vor der Speicherung normalisiert; Template-Definitionen werden durch die zugehörigen Views und nicht durch spontane Ausgaben im Einstieg verarbeitet.
