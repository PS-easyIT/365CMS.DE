# 365CMS – Inhalte: Seiten, Beiträge, Hub-Sites & Landing Pages

Kurzbeschreibung: Überblick über die Content-Module im Admin-Bereich und ihre aktuelle Aufgabenverteilung.

Letzte Aktualisierung: 2026-05-12 · Version 2.9.779

Der Content-Bereich ist auf mehrere spezialisierte Bereiche verteilt:

| Route | Zweck |
|---|---|
| `/admin/pages` | statische Seiten verwalten |
| `/admin/posts` | Blog- und News-Beiträge verwalten |
| `/admin/comments` | Kommentare moderieren und verwalten |
| `/admin/hub-sites` | Hub-Sites und thematische Sammelseiten verwalten |
| `/admin/landing-page` | Landing-Page-Bausteine und Homepage-Sektionen |
| `/admin/table-of-contents` | Inhaltsverzeichnis-Logik und TOC-Einstellungen |
| `/admin/site-tables` | benutzerdefinierte Datentabellen |
| `/admin/settings?tab=content` | Inhalts-Einstellungen (Editor-Optionen, Defaults, Permalinks, Archiv-Basen) |

---

## Editor-Stack

Im aktuellen Stand nutzt 365CMS im Content-Bereich mehrere Editorkomponenten:

- **SunEditor** für klassische Rich-Text-Bearbeitung
- **Editor.js** für blockbasierte Inhalte
- **SEO-/Readability-/Preview-Karten** unter Seiten und Beiträgen für Meta-Daten, Inhaltsqualität und Vorschau
- **Lazy Language Switching** im Beitrags-Editor mit einmaliger DE→EN-Initialkopie bei leerer englischer Fassung
- **Read-only Revisionsvergleich** in Seiten- und Beitragseditor für die letzten gespeicherten Snapshots des aktuellen Inhalts

Die SEO-spezifischen Global-Einstellungen liegen nicht mehr in einem alten Monolithen `seo.php`, sondern im [SEO-Center](../seo/SEO.md).

---

## Wichtige Fachdokumente

| Dokument | Schwerpunkt |
|---|---|
| [PAGES.md](PAGES.md) | statische Seiten |
| [POSTS.md](POSTS.md) | Beiträge und Blog-Workflow |
| [COMMENTS.md](COMMENTS.md) | Kommentar-Moderation mit Status-Tabs, Schnellfiltern und Massenaktionen |
| [HUBSITES.md](HUBSITES.md) | Hub-Sites, Slugs, Zusatzdomains und Public-Routing |
| [SETTINGS.md](SETTINGS.md) | Inhalts-Einstellungen für Editor, Defaults, Permalinks und Archive |
| [TABLES.md](TABLES.md) | Wiederverwendbare Site-Tabellen |
| [TOC.md](TOC.md) | Inhaltsverzeichnis-Einstellungen |
| [../landing-page/LANDING-PAGE.md](../landing-page/LANDING-PAGE.md) | Landing-Page-Builder |
| [../media/MEDIA.md](../media/MEDIA.md) | Medienbibliothek und Dateinutzung |

---

## Aktuelle Hinweise

- Lösch-Workflows für Seiten und Beiträge folgen im aktuellen Stand dem konsolidierten Admin-Flow mit Redirects, Alerts und serverseitiger Validierung.
- Featured Images, Slugs und Redaktionshilfen sind stärker mit SEO und Medienverwaltung verzahnt; verwendete Beitrags- und Seitenbilder können unter `/admin/media?tab=featured` gezielt gefunden und global am bestehenden Medienpfad ersetzt werden.
- Neue Beitrags- und Seitenbilder werden zuerst temporär hochgeladen und beim Speichern in den Slug-Ordner verschoben; der Save-Flow ist dabei fail-soft gegen Metadaten-/Dateisystemfehler und verhindert leere 500er nach erfolgreicher Bildübernahme.
- Der Beitrags-Editor nutzt im Top-Bereich jetzt eine kompaktere Kartenverteilung: Kategorie und Tags sitzen direkt beim Slug, während Speichern und öffentliche DE-/EN-Vorschau in einer eigenen Aktions-Card unter dem Beitragsbild liegen.
- Beiträge arbeiten im Editor aktuell mit **einer primären Kategorie**; die frühere UI für zusätzliche Kategorien wird nicht mehr angeboten.
- Slug-Änderungen an Kategorien und Tags halten den öffentlichen Taxonomie-Vertrag jetzt ebenfalls stabil: dedizierte Archivpfade und alte `?category=`/`?tag=`-Filterwerte werden weiter auf den aktuellen Slug aufgelöst.
- Die Kategorie- und Tag-Editoren machen diesen Vertrag inzwischen sichtbar: aktuelle Archivpfade, Redirect-Hinweis direkt am Slug-Feld und Erfolgsdetails zu automatisch gepflegten Archiv-Weiterleitungen sorgen dafür, dass die Cross-Verkabelung im Admin nachvollziehbar bleibt.
- Kategorien und Tags unterstützen seit `2.9.706` Bulk-Löschaktionen in den jeweiligen Listen. Bei Beitragsbezug erzwingt der Server gültige Ersatzkategorien bzw. Ersatztags, verhindert Ersatzwerte aus der Lösch-Auswahl und protokolliert erfolgreiche Sammelaktionen im Audit-Trail.
- Seit `2.9.707` leeren erfolgreiche Bulk-Löschungen für Kategorien und Tags den Content-Cache erst nach erfolgreichem Commit einmalig statt pro Einzellöschung innerhalb der offenen Transaktion. Das reduziert unnötige Cache-Invalidierungen und hält den Löschpfad bei Rollbacks konsistenter.
- Hub-Sites reservieren nun auch statische Public-Routen und Archivbasen als Slugs, damit neu angelegte Hubs nicht an `/contact`, `/authors`, `/feed`, `/category`, `/tag` oder ähnlichen Frontend-Routen unsichtbar vorbeiplanen.
- Site-Tabellen schließen ihren Public-Vertrag jetzt sichtbar an den Editor an: Suche, Sortierung, Paginierung und Zeilenhervorhebung wirken im Frontend tatsächlich; die nicht implementierte Excel-Option wird im Admin nicht länger als scheinbar produktiver Export angeboten.
- Der Unterbereich „Einstellungen“ lebt technisch unter `/admin/settings?tab=content`, bündelt jetzt auch Permalink-/Archiv-Basen direkt im Inhalts-Tab und behält Formulareingaben bei Validierungsfehlern über den Redirect hinweg.
- Listenansichten mit Mehrfachauswahl machen die beabsichtigte Bulk-Aktion jetzt direkt am Button sichtbar; destruktive Aktionen werden so nicht mehr hinter einem generischen „Anwenden“ versteckt. Gleichzeitig öffnen Aktions-Dropdowns in scrollbaren Tabellen sichtbar, statt in der horizontalen Overflow-Zone abgeschnitten zu werden.
- Die Kommentar-Moderation kombiniert Status-Tabs jetzt mit serverseitiger Schnellsuche, Autorentyp- und Beitragsbezug-Filtern; aktive Filter bleiben über Moderationsaktionen hinweg erhalten, und der sichtbare Batch-Modus deaktiviert parallele Zeilenaktionen bewusst.
- Seiten- und Beitragseditor zeigen die letzten gespeicherten Revisionen jetzt direkt im Admin und vergleichen Titel, Slugs, Status sowie DE/EN-Inhalts-Snapshots mit dem aktuellen Stand, ohne alte Fassungen versehentlich sofort zurückzuschreiben.
- Historische Verweise auf `/admin/seo.php` oder alte Monolith-Seiten sind in diesem Bereich nicht mehr korrekt.
