# Inhaltsverzeichnis (Table of Contents)

Kurzbeschreibung: Einstellungen für die automatische Inhaltsverzeichnis-Generierung in Seiten und Beiträgen.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

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

### Speichern

`saveSettings(array $input)` validiert und speichert die TOC-Einstellungen.

---

## Verwandte Seiten

- [Seiten](PAGES.md)
- [Beiträge](POSTS.md)
- [Content-Einstellungen](README.md)
