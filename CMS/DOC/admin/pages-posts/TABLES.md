# Site-Tabellen

Kurzbeschreibung: Verwaltung wiederverwendbarer Datentabellen für Seiten und Beiträge über `/admin/site-tables`.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

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

Im aktuellen Stand umfasst die Bearbeitungsoberfläche u. a.:

- Tabellenname und Beschreibung
- dynamische Spalten- und Datenzeilenbearbeitung
- Stilvorgaben und Anzeigeoptionen
- Such-, Sortier-, Paginierungs- und Responsive-Schalter
- Exportoptionen wie CSV/JSON/Excel

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
- serverseitige Begrenzung von Spalten-, Zeilen- und Feldgrößen

---

## Verwandte Seiten

- [Seiten](PAGES.md)
- [Inhalte – Übersicht](README.md)
