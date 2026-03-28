# Site-Tabellen

Kurzbeschreibung: Verwaltung wiederverwendbarer Datentabellen für Seiten und Beiträge über `/admin/site-tables`.

Letzte Aktualisierung: 2026-03-28 · Version 2.8.0 RC

---

## Route und Technik

| Eigenschaft | Wert |
|---|---|
| Route | `/admin/site-tables` |
| Entry Point | `CMS/admin/site-tables.php` |
| Modul | `CMS/admin/modules/tables/TablesModule.php` |
| Datenbank | `cms_site_tables` |
| CSRF-Kontext | `admin_tables` |

---

## Funktionsumfang

### Tabellenliste

Zeigt alle angelegten Site-Tabellen mit Name, Status und Aktionsmenü.

### Erstellen / Bearbeiten

Über `getEditData(?int $id)` wird eine Tabelle zum Bearbeiten geladen oder ein leeres Formular vorbereitet.

`save(array $post)` speichert neue oder bestehende Tabellen.

### Weitere Aktionen

| Aktion | Methode |
|---|---|
| Löschen | `delete(int $id)` |
| Duplizieren | `duplicate(int $id)` |

---

## Sicherheit

- Admin-Zugriffsschutz
- CSRF-Prüfung
- Redirect nach Schreibvorgängen

---

## Verwandte Seiten

- [Seiten](PAGES.md)
- [Inhalte – Übersicht](README.md)
