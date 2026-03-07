# URL-Weiterleitungen (Redirect Manager)

Kurzbeschreibung: Verwaltung von 301/302-Weiterleitungen, Protokollierung und Aufräumfunktionen.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

---

## Route und Technik

| Eigenschaft | Wert |
|---|---|
| Route | `/admin/seo-redirects` |
| Modul | `CMS/admin/modules/seo/RedirectManagerModule.php` |
| View | `CMS/admin/views/seo/redirects.php` |
| CSRF-Kontext | `admin_seo` |

---

## Funktionsumfang

### Redirect-Liste

Zeigt alle konfigurierten Weiterleitungen mit Quell-URL, Ziel-URL, Typ (301/302), Status und Trefferanzahl.

### Aktionen

| Aktion | Methode |
|---|---|
| Erstellen/Bearbeiten | `saveRedirect(array $post)` |
| Löschen | `deleteRedirect(int $id)` |
| Aktivieren/Deaktivieren | `toggleRedirect(int $id)` |
| Logs leeren | `clearLogs()` |

### Redirect-Typen

| Typ | HTTP-Status | Zweck |
|---|---|---|
| Permanent | `301` | SEO-wirksame dauerhafte Weiterleitung |
| Temporär | `302` | Vorübergehende Umleitung |

---

## Sicherheit

- Admin-Zugriffsschutz
- CSRF-Prüfung über gemeinsamen SEO-Kontext
- Serverseitige Validierung von Quell- und Ziel-URLs

---

## Verwandte Seiten

- [SEO-Übersicht](SEO.md)
- [Technisches SEO](SEO.md#technisches-seo)
