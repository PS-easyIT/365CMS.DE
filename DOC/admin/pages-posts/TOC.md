# Inhaltsverzeichnis (Table of Contents)

Kurzbeschreibung: Einstellungen für die automatische Inhaltsverzeichnis-Generierung in Seiten und Beiträgen.

Letzte Aktualisierung: 2026-03-28 · Version 2.8.0 RC

---

## Route und Technik

| Eigenschaft | Wert |
|---|---|
| Route | `/admin/content-settings` (Tab: TOC) |
| Modul | `CMS/admin/modules/toc/TocModule.php` |
| CSRF-Kontext | `admin_toc` |

---

## Funktionsumfang

### Einstellungen

`getSettings()` liefert die aktuelle TOC-Konfiguration. `getDefaults()` gibt die Standardwerte zurück.

Typische Optionen:

- TOC aktivieren/deaktivieren
- Minimale Überschriftenebene
- Maximale Überschriftenebene
- Position (vor Inhalt, nach Einleitung, etc.)
- Darstellungsstil

### Speichern

`saveSettings(array $input)` validiert und speichert die TOC-Einstellungen.

---

## Verwandte Seiten

- [Seiten](PAGES.md)
- [Beiträge](POSTS.md)
- [Content-Einstellungen](README.md)
