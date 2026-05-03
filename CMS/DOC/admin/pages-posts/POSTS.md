# 365CMS – Beiträge & Blog

Kurzbeschreibung: Verwaltung chronologischer Inhalte wie News und Blog-Beiträge im Admin-Bereich.

Letzte Aktualisierung: 2026-05-03 · Version 2.9.502

---

## Überblick

Beiträge sind chronologische Inhalte für Blog, News, Feeds, Themenarchive und Suche. Der Admin-Bereich kombiniert Listenworkflow, mehrsprachige Bearbeitung, SEO-Hilfen, Veröffentlichungssteuerung und Medien-/Taxonomie-Zuordnung in einem gemeinsamen Redaktionspfad.

---

## Aktueller Listenvertrag

Die Beitragsübersicht bietet aktuell:

- Statusfilter (`Veröffentlicht`, `Geplant`, `Entwurf`, `Privat`)
- Kategoriefilter
- Freitextsuche
- Multi-Select für Bulk-Aktionen
- KPI-Karten für Gesamt, veröffentlicht, geplant, Entwürfe und privat

### Bulk-Aktionen

Folgende Bulk-Aktionen sind produktiv vorgesehen:

- Veröffentlichen
- Als Entwurf setzen
- Kategorie(n) setzen
- Kategorie entfernen
- Autoren-Anzeigenamen setzen
- Autoren-Anzeigenamen zurücksetzen
- Löschen

Der Bulk-Flow validiert Beitrags-IDs fail-closed gegen den aktuellen Datenbestand. Fehlende oder zwischenzeitlich gelöschte Beiträge führen nicht zu stillen Teiloperationen, sondern zu einer klaren Fehlermeldung.

---

## Editor-Aufbau

Die obere Editor-Zone besteht aus drei primären Bereichen plus sichtbarer Delete-Sektion bei bestehenden Beiträgen:

| Bereich | Inhalt |
|---|---|
| Card 1 | Titel, Slug, Primärkategorie, zusätzliche Kategorien, Tags |
| Card 2 | Beitragsbild |
| Card 2b | Hauptaktion `Erstellen/Aktualisieren` sowie öffentliche DE-/EN-Vorschau |
| Card 3 | Status, Veröffentlichungsdatum/-zeit und Autoren-Anzeigename |
| Delete-Card | Sichtbarer Einzel-Löschpfad mit Bestätigung |

Wichtig: Beiträge unterstützen weiterhin **eine Primärkategorie plus optionale zusätzliche Kategorien** über die Relationstabelle `post_category_rel`. Ältere Dokumentationsstände ohne Mehrfachkategorien sind überholt.

---

## Mehrsprachiger Redaktionsfluss

Beiträge werden in getrennten DE-/EN-Ansichten bearbeitet.

- Die deutsche und englische Fassung bleiben beim Speichern voneinander isoliert.
- Die EN-Ansicht bietet einen expliziten Button `DE nach EN kopieren`.
- Optional kann die EN-Fassung per AI-Übersetzung vorbereitet werden.
- Eine automatische Erstkopie beim ersten Sprachwechsel ist für Beiträge aktuell **nicht** konfiguriert.

Das bedeutet: Bestehende EN-Inhalte werden nicht implizit beim Ansichtswechsel überschrieben. Kopie und Übersetzung sind bewusste Redaktionsaktionen.

---

## Redirect- und URL-Vertrag

Bei Slug-Änderungen werden automatische Redirects auf Basis der aktiven Beitrags-Permalinkstruktur erzeugt.

- Standardpfade folgen dem aktuellen Public-Schema, z. B. `/blog/...`
- Lokalisierte Pfade folgen dem Präfix-Schema `/en/blog/...`
- Legacy-Pfade bleiben zusätzlich per Redirect kompatibel
- Änderungen an `slug_en` erzeugen ebenfalls lokalisierte Redirects und fallen bei leerem EN-Slug kontrolliert auf den Standardslug zurück

Damit bleiben sowohl aktuelle als auch ältere öffentliche Beitrags-URLs stabil auflösbar.

---

## Delete-, Cache- und Veröffentlichungslogik

- Einzel-Löschen ist im Editor sichtbar und mit Bestätigungsdialog abgesichert.
- Einzel- und Bulk-Löschen feuern `post_deleted` für Folgeprozesse.
- Wenn `perf_auto_clear_content_cache` aktiv ist, leeren Speichern, Löschen, relevante Bulk-Mutationen sowie Kategorie-/Tag-Änderungen den Inhaltscache automatisch.
- Veröffentlichte Beiträge mit zukünftigem Datum erscheinen im Admin als `Geplant` und werden erst zum vorgesehenen Zeitpunkt öffentlich sichtbar.

Das folgt den Heuristiken **Visibility of System Status**, **Error Prevention** und **User Control and Freedom**: Status ist sichtbar, riskante Aktionen werden bestätigt und destruktive Schritte sind klar erkennbar statt versteckt.

---

## Besondere Bezüge

| Bereich | Nutzen |
|---|---|
| Kategorien und Tags | Taxonomie, Archive, Filterung und Routing |
| SEO-Center | Meta-Daten, Vorschauen, strukturierte Daten und Analysen |
| Redirect-Manager | URL-Stabilität bei Slug-Änderungen |
| Sitemap / SEO-Services | Veröffentlichte Beiträge fließen in Sichtbarkeits- und Indexierungsprozesse ein |
| Medienverwaltung | Featured Image und Inhaltsmedien |

---

## Verwandte Dokumente

- [PAGES.md](PAGES.md)
- [../seo/SEO.md](../seo/SEO.md)
- [../media/MEDIA.md](../media/MEDIA.md)
