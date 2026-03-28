# 365CMS – Projektdokumentation
> **Stand:** 2026-03-28 | **Version:** 2.8.0 RC | **Status:** Aktuell

## Inhaltsverzeichnis
- [Womit ihr anfangen solltet](#womit-ihr-anfangen-solltet)
- [Release-Fokus 2.8.0 RC](#release-fokus-280-rc)
- [Dokumentationsbereiche](#dokumentationsbereiche)
- [Wichtige Hinweise](#wichtige-hinweise)
- [Verwandte Einstiege](#verwandte-einstiege)

---
<!-- UPDATED: 2026-03-28 -->

## Womit ihr anfangen solltet

| Wenn ihr ... | dann startet hier |
|---|---|
| das System neu aufsetzt | [INSTALLATION.md](INSTALLATION.md) |
| die Projektstruktur verstehen wollt | [core/ARCHITECTURE.md](core/ARCHITECTURE.md) |
| einen Release-Snapshot des Core wollt | [core/STATUS.md](core/STATUS.md) |
| das Admin-Panel nutzt | [admin/README.md](admin/README.md) |
| den Member-Bereich betreut | [member/README.md](member/README.md) |
| den Medienbereich nachvollziehen wollt | [admin/media/README.md](admin/media/README.md) |
| Asset-/Vendor-Stände prüfen wollt | [assets/README.md](assets/README.md) |
| Plugins entwickelt | [plugins/PLUGIN-DEVELOPMENT.md](plugins/PLUGIN-DEVELOPMENT.md) |
| Themes entwickelt | [theme/THEME-DEVELOPMENT.md](theme/THEME-DEVELOPMENT.md) |

---

## Release-Fokus 2.8.0 RC

Die Dokumentation ist jetzt auf den Release-Candidate-Stand `2.8.0` nachgezogen. Der Schwerpunkt liegt auf den Änderungen der jüngsten Folge-Batches:

- native Consent-, Upload-, Picker- und Feed-Laufzeitpfade statt aktiver FilePond-/elFinder-/CookieConsent-/SimplePie-Runtime
- kompaktere Medienverwaltung im Admin mit Dropdowns, zentralen Modalen und Bulk-Aktionen
- robusterer Member-Medienbereich mit Breadcrumbs, Root-Scoping, Rename/Move und optionalem Delete
- korrigierte Schutzlogik für Systempfade: `member` und direkte User-Roots bleiben geschützt, Member-Unterordner sind wieder regulär verwaltbar

---

## Dokumentationsbereiche

### Core

Die Kernsystem-Dokumente unter [`core/`](core/) beschreiben Bootstrap, Routing, Datenmodell, Services, Hooks und Sicherheit.

### Admin

Die Admin-Dokumente unter [`admin/`](admin/) orientieren sich an der aktuellen Sidebar- und Modulstruktur aus `CMS/admin/`.

### Member

Die Dokumente unter [`member/`](member/) beschreiben den persönlichen Mitgliederbereich unter `/member`, einschließlich Nachrichten, Profil, Datenschutz und Plugin-Integration.

### Theme und Plugins

Die Bereiche [`theme/`](theme/) und [`plugins/`](plugins/) enthalten Entwicklungsleitfäden für Erweiterungen des Systems.

### Workflows und Audits

Die Ordner [`workflow/`](workflow/) und [`audit/`](audit/) dokumentieren operative Abläufe, Live-Audits und technische Bewertungen.

---

## Wichtige Hinweise

- Für **Installations- und Konfigurationsfragen** gelten immer `CMS/config.php` als Stub und `CMS/config/app.php` als eigentliche Konfigurationsdatei.
- Für **aktuelle Admin-Routen** gilt die Sidebar-Konfiguration aus `CMS/admin/partials/sidebar.php` als Referenz.
- Für **Datenbankaussagen** ist [core/DATABASE-SCHEMA.md](core/DATABASE-SCHEMA.md) maßgeblich.
- Für **Release-Änderungen** ist [../Changelog.md](../Changelog.md) die führende Datei.
- Für **Medien- und Upload-Aussagen** gelten [admin/media/README.md](admin/media/README.md), [admin/media/MEDIA.md](admin/media/MEDIA.md) und [workflow/MEDIA-UPLOAD-WORKFLOW.md](workflow/MEDIA-UPLOAD-WORKFLOW.md).
- Für **laufende Qualitätsstände** ist der Bereich [`audit/`](audit/) die erste Anlaufstelle.

---

## Verwandte Einstiege

- [Dokumentationsindex](INDEX.md)
- [Root-README](../README.md)
- [Projekt-Changelog](../Changelog.md)
- [Audit-Bewertung](audit/BEWERTUNG.md)


