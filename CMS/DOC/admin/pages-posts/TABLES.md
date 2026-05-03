# Site-Tabellen

Kurzbeschreibung: Verwaltung wiederverwendbarer Datentabellen und dynamischer CMS-Quellen für Seiten und Beiträge über `/admin/site-tables`.

Letzte Aktualisierung: 2026-05-03 · Version 2.9.507

---

## Route und Technik

| Eigenschaft | Wert |
|---|---|
| Route | `/admin/site-tables` |
| Entry Point | `CMS/admin/site-tables.php` |
| Modul | `CMS/admin/modules/tables/TablesModule.php` |
| Public Runtime | `CMS/core/Services/SiteTable/SiteTableTableRenderer.php` |
| Datenbank | `cms_site_tables` |
| CSRF-Kontext | `admin_tables` |

---

## Aktueller Vertragsstand

### Tabellenliste

Die Übersicht zeigt ausschließlich echte Tabellen (`content_mode = table`) und blendet Hub-Sites aus, obwohl beide denselben Datenspeicher `cms_site_tables` nutzen.

Pro Zeile sind aktuell vorgesehen:

- Name
- Kurzbeschreibung
- Spaltenanzahl
- Zeilenanzahl
- letztes Update
- Bearbeiten / Duplizieren / Löschen

### Editor und Datenquellen

Der Editor unterstützt zwei Modi:

1. **Manuelle Tabelle**
	- freie Spaltenlabels
	- freie Zellenwerte
	- serverseitige Begrenzung auf maximal 25 Spalten, 250 Zeilen und 5000 Zeichen pro Zelle

2. **Dynamische Site Table aus CMS-Inhalten**
	- entweder explizit ausgewählte veröffentlichte Seiten/Beiträge
	- oder ein Kategorie-Filter
	- freie Spalten-/Zellbearbeitung ist in diesem Modus bewusst deaktiviert
	- die Public-Ausgabe bleibt auf feste Spalten beschränkt: **Typ, Titel, Public-Link, Kategorie**

Die Auswahlwerte werden serverseitig nochmals gegen die tatsächlich verfügbaren Admin-Optionen validiert. Clientseitig deaktivierte Controls sind damit nur UX, nicht die Sicherheitsgrenze.

### Public-Runtime

Die Tabellen-Runtime schließt die im Editor sichtbaren Schalter jetzt tatsächlich an die Ausgabe an:

- **Suche** filtert Zeilen clientseitig im Frontend
- **Sortierung** arbeitet direkt über die Tabellenköpfe und pflegt `aria-sort`
- **Paginierung** nutzt die konfigurierte Seitengröße statt die Option nur zu speichern
- **Zeilen hervorheben** hat nun eine sichtbare Runtime-Wirkung
- **Responsive** Tabellen bleiben horizontal scrollbar statt abgeschnitten zu werden

Das folgt den W3C-Empfehlungen für Datentabellen: semantische `<table>`-/`<th>`-Struktur, echte Kopfzellen-Beziehungen, Caption-Unterstützung und keine irreführende `role="grid"`-Umdeutung für statische Datentabellen.

### Export-Vertrag

Produktiv vorgesehen sind aktuell nur:

- **CSV**
- **JSON**

Die vorher im Editor sichtbare Excel-Option war nicht an eine Runtime gebunden und wird daher nicht länger als scheinbar verfügbarer Export angeboten.

---

## Accessibility- und Content-Hinweise

- `caption` dient als echte Tabellenüberschrift, wenn die globale Anzeige aktiviert ist.
- Beschreibungen können zusätzlich über die Meta-Zone mit der Tabelle verknüpft werden.
- Sortierbare Spaltenköpfe werden als echte Buttons innerhalb der Header-Zellen gerendert.
- Die Public-Suche arbeitet ohne Änderung der semantischen Tabellenstruktur.
- Tabellenzellen erlauben weiterhin nur eine enge Inline-HTML-Whitelist (`a`, `strong`, `b`, `em`, `i`, `u`).

---

## Sicherheit

- Admin-Zugriffsschutz
- CSRF-Prüfung
- Redirect nach Schreibvorgängen
- serverseitige Begrenzung von Spalten-, Zeilen- und Feldgrößen
- fail-closed-Validierung dynamischer Inhaltsquellen
- Sanitizing von Zellinhalten über das `table_cell`-Profil

---

## Verwandte Seiten

- [HUBSITES.md](HUBSITES.md)
- [PAGES.md](PAGES.md)
- [POSTS.md](POSTS.md)
- [Inhalte – Übersicht](README.md)
