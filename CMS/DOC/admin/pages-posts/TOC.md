# Inhaltsverzeichnis (Table of Contents)

Kurzbeschreibung: Einstellungen für die automatische Inhaltsverzeichnis-Generierung in Seiten und Beiträgen.

Letzte Aktualisierung: 2026-05-25 · Version 3.3.28

---

## Route und Technik

| Eigenschaft | Wert |
|---|---|
| Route | `/admin/table-of-contents` |
| Entry Point | `CMS/admin/table-of-contents.php` |
| Modul | `CMS/admin/modules/toc/TocModule.php` |
| CSRF-Kontext | `admin_toc` |

---

## Funktionsumfang

### Einstellungen

`getSettings()` liefert die aktuelle TOC-Konfiguration. `getDefaults()` gibt die Standardwerte zurück.

Typische Optionen:

- TOC aktivieren/deaktivieren
- Mindestanzahl Überschriften
- aktivierte Überschriftenebenen (`h2` bis `h6`)
- Position (vor Inhalt, nach Einleitung, etc.)
- Darstellungsstil, Breite, Theme und Ausrichtung
- Scroll-Verhalten, Offsets und Anker-Präfix
- Ausnahmeslugs und Pfadbegrenzung

### Seitenspezifisches Header-TOC

Seit Release `3.3.28` besitzen Seiten zusätzlich die lokale Option **„Eingeklapptes Inhaltsverzeichnis unter dem Titel anzeigen“**. Diese Option liegt im Seiteneditor selbst und ist bewusst unabhängig von den globalen TOC-Einstellungen unter `/admin/table-of-contents`.

- Persistenz: `cms_pages.show_title_toc`
- Ausgabe: Core-Router injiziert ein `<details class="cms-page-title-toc">` am Anfang des vorbereiteten Seitencontents; der eingeklappte Summary-Text zeigt nur `Inhaltsverzeichnis`.
- Mindestumfang: mindestens zwei Überschriften (`h2` bis `h6`).
- Standardzustand: eingeklappt.
- Ziel: themeübergreifende Schnellnavigation direkt unter dem Seitentitel, ohne globale Auto-Insert-Regeln aktivieren zu müssen.

### Speichern

`saveSettings(array $input)` validiert und speichert die TOC-Einstellungen.

---

## Verwandte Seiten

- [Seiten](PAGES.md)
- [Beiträge](POSTS.md)
- [Content-Einstellungen](README.md)
