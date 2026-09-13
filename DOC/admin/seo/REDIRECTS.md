# 365CMS – Projektdokumentation | Abschnitt: Admin – Weiterleitungen
> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

Redirect management is implemented by `CMS/admin/modules/seo/RedirectManagerModule.php`. It provides data for redirect management and not-found monitoring and exposes validated operations to save, delete, toggle, and clear redirect records or logs.

Use the module form for every change. Source paths, destinations, identifiers, and action names must be normalized by the server before persistence. Review the result after each change because redirect errors can affect public navigation.

## Deutsch

Die Weiterleitungsverwaltung wird durch `CMS/admin/modules/seo/RedirectManagerModule.php` implementiert. Sie liefert Daten für Redirect-Verwaltung und Not-Found-Monitoring und stellt geprüfte Aktionen zum Speichern, Löschen, Aktivieren/Deaktivieren sowie Leeren von Redirect-Datensätzen oder Logs bereit.

Für jede Änderung das Modulformular verwenden. Quellpfade, Ziele, IDs und Aktionsnamen müssen serverseitig normalisiert werden. Nach jeder Änderung das Ergebnis prüfen, da Redirect-Fehler die öffentliche Navigation beeinflussen können.
