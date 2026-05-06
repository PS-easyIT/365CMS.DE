# 365CMS – Beiträge & Blog

Kurzbeschreibung: Verwaltung chronologischer Inhalte wie News und Blog-Beiträge im Admin-Bereich.

Letzte Aktualisierung: 2026-05-03 · Version 2.9.506

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

Die obere Editor-Zone besteht aus drei primären Bereichen:

| Bereich | Inhalt |
|---|---|
| Card 1 | Titel, Slug, Primärkategorie, zusätzliche Kategorien, Tags |
| Card 2 | Beitragsbild |
| Card 2b | Hauptaktion `Erstellen/Aktualisieren`, öffentliche DE-/EN-Vorschau und dezenter Delete-Button |
| Card 3 | Status, Veröffentlichungsdatum/-zeit und Autoren-Anzeigename |

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

- Einzel-Löschen ist im Editor direkt in der Aktionskarte unter den Vorschau-Buttons sichtbar und mit Bestätigungsdialog abgesichert.
- Einzel- und Bulk-Löschen feuern `post_deleted` für Folgeprozesse.
- Wenn `perf_auto_clear_content_cache` aktiv ist, leeren Speichern, Löschen, relevante Bulk-Mutationen sowie Kategorie-/Tag-Änderungen den Inhaltscache automatisch.
- Veröffentlichte Beiträge mit zukünftigem Datum erscheinen im Admin als `Geplant` und werden erst zum vorgesehenen Zeitpunkt öffentlich sichtbar.

Das folgt den Heuristiken **Visibility of System Status**, **Error Prevention** und **User Control and Freedom**: Status ist sichtbar, riskante Aktionen werden bestätigt und destruktive Schritte sind klar erkennbar statt versteckt.

---

## Kategorien- und Tag-Vertrag

Die Taxonomie-Verwaltung gehört funktional zum Beiträge-Bereich und folgt jetzt einem konsistenteren Admin-Vertrag:

- **Kategorien** unterstützen Haupt-/Unterkategorien, optionale Fremddomains sowie eine hinterlegte Ersatzkategorie für spätere Löschvorgänge.
- **Tags** bleiben flach, erlauben aber beim Löschen eine bewusste Umstellung betroffener Beiträge auf einen Ersatztag.
- Validierungsfehler in Kategorie-/Tag-Formularen verwerfen die Eingaben nicht mehr sofort: Name, Slug, Eltern-/Ersatzauswahl und Zusatzdomains bleiben nach dem Redirect erhalten und werden direkt am Formular erneut eingeblendet.
- Einzel-Löschdialoge sind bewusst spezifisch formuliert: sie nennen die betroffene Taxonomie und erläutern, ob Beiträge umgehängt oder Beziehungen nur entfernt werden.
- Auch der Fallback ohne Bootstrap-Modal blockiert Kategorie-Löschungen nicht mehr unnötig, wenn bereits eine gültige Ersatzkategorie hinterlegt ist.

Das passt zu den NN/g-Empfehlungen für **Confirmation Dialogs** und **Error Messages**: riskante Aktionen werden konkret beschrieben, Modale bleiben auf destruktive Schritte beschränkt, und Korrekturen können mit erhaltenem Formzustand direkt am Entstehungsort erfolgen.

Zusätzlich ist der Public-Vertrag des Default-Themes für Taxonomie-Navigation jetzt wieder konsistent: Blog-Links mit `?category=` bzw. `?tag=` lösen in dieselben Kategorie-/Tag-Archive auf wie die dedizierten Archivrouten, und Sidebar-/Header-Helfer zählen veröffentlichte Beiträge nicht mehr nur über die Primärkategorie oder Legacy-`posts.tags`, sondern berücksichtigen Relationstabellen sowie die aktuelle DE/EN-Content-Verfügbarkeit.

---

## Kommentare- und TOC-Vertrag

Der Unterbereich **Kommentare** ist nun auch im Public-Frontend wieder vollständig angeschlossen:

- Einzelbeiträge rendern freigegebene Kommentare wieder sichtbar oberhalb des Formulars.
- Der Redirect nach `POST /comments/post` landet bevorzugt wieder auf demselben sicheren Public-Pfad des absendenden Beitrags statt pauschal auf einer generischen Blog-URL; dadurch bleiben locale-aware Pfade und `#comments` stabil.
- Das Default-Theme respektiert im Formular jetzt auch `allow_comments` des Beitrags. Ist Kommentieren deaktiviert, erscheint kein irreführend funktionierendes Formular mehr.
- Fehler- und Erfolgsrückmeldungen der Kommentarabgabe werden inline im Kommentarbereich gezeigt; Name, E-Mail, Kommentartext und der anonyme Status bleiben bei Fehlern erhalten.
- Eingeloggte Nutzer verwenden konsistent ihre Profilidentität und können optional anonym veröffentlichen; öffentliche Kommentare bleiben weiterhin moderationspflichtig.
- Die Admin-Moderationsliste verwendet nur noch den tatsächlich produktiven Formular-/Dropdown-Vertrag; veraltete JS-Aktionspfade ohne DOM-Gegenstück wurden entfernt, und `Alle auswählen` arbeitet jetzt sauber mit indeterminiertem Zwischenzustand und Bulk-Zähler zusammen.

Für das **Inhaltsverzeichnis (TOC)** gilt jetzt ein präziserer Runtime-Vertrag:

- `exclude_headings` akzeptiert Pipe- **und** Komma-getrennte Ausschlusslisten.
- Die Optionen `lowercase` und `hyphenate` beeinflussen die Ankererzeugung jetzt tatsächlich statt nur gespeichert zu werden.
- `homepage_toc` unterdrückt TOCs auf Home-/Locale-Root-Pfaden, wenn die Option deaktiviert ist.
- `exclude_css` rendert eine ungestylte TOC-Ausgabe ohne die internen TOC-Klassen, sodass die Core-CSS wirklich wegfällt.
- Die Admin-Auswahl `light`/`dark` mappt wieder auf funktionierende Theme-Varianten, und die Positionsbeschreibung benennt den realen Insertionsvertrag korrekt als **vor/nach der ersten Überschrift**.
- Die Admin-Seite `/admin/table-of-contents` nutzt denselben Section-Shell-Standard wie andere modernisierte Bereiche, inklusive konsistenter CSRF-/Flash-/Redirect-Behandlung.

Diese Nachschärfung folgt zwei UX-/A11y-Grundsätzen: Kommentare müssen sichtbar, lokalisierbar und rückmeldungsstark sein, und TOCs müssen sich an echter Überschriftenstruktur orientieren statt an bloß dekorativen Schaltern.

---

## Besondere Bezüge

| Bereich | Nutzen |
|---|---|
| Kategorien und Tags | Taxonomie, Archive, Filterung und Routing |
| SEO-Center | Meta-Daten, Vorschauen, strukturierte Daten und Analysen |
| Redirect-Manager | URL-Stabilität bei Slug-Änderungen |
| Sitemap / SEO-Services | Veröffentlichte Beiträge fließen in Sichtbarkeits- und Indexierungsprozesse ein |
| Medienverwaltung | Featured Image und Inhaltsmedien; globale Ersetzung verwendeter Beitragsbilder unter `/admin/media?tab=featured` |

---

## Verwandte Dokumente

- [PAGES.md](PAGES.md)
- [../seo/SEO.md](../seo/SEO.md)
- [../media/MEDIA.md](../media/MEDIA.md)
