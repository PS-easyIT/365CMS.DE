# 365CMS – Projektdokumentation
> **Stand:** 2026-04-24 | **Version:** 2.9.244 | **Status:** Aktuell

## Inhaltsverzeichnis
- [Womit ihr anfangen solltet](#womit-ihr-anfangen-solltet)
- [Release-Fokus 2.9.244](#release-fokus-29244)
- [Dokumentationsbereiche](#dokumentationsbereiche)
- [Wichtige Hinweise](#wichtige-hinweise)
- [Verwandte Einstiege](#verwandte-einstiege)

---
<!-- UPDATED: 2026-04-24 -->

## Womit ihr anfangen solltet

| Wenn ihr ... | dann startet hier |
|---|---|
| das System neu aufsetzt | [INSTALLATION.md](INSTALLATION.md) |
| die Runtime-Struktur aktuell verstehen wollt | [FILELIST.md](FILELIST.md) |
| die technische Gesamtsicht braucht | [DEVLIST.md](DEVLIST.md) |
| die Projektstruktur verstehen wollt | [core/ARCHITECTURE.md](core/ARCHITECTURE.md) |
| einen Release-Snapshot des Core wollt | [core/STATUS.md](core/STATUS.md) |
| das Admin-Panel nutzt | [admin/README.md](admin/README.md) |
| die neue CMS-Loginpage steuern wollt | [admin/themes-design/CMS-LOGINPAGE.md](admin/themes-design/CMS-LOGINPAGE.md) |
| den Member-Bereich betreut | [member/README.md](member/README.md) |
| den Medienbereich nachvollziehen wollt | [admin/media/README.md](admin/media/README.md) |
| Asset-/Vendor-Stände prüfen wollt | [assets/README.md](assets/README.md) |
| neue Asset-Kandidaten bewerten wollt | [ASSETS_NEW.md](ASSETS_NEW.md) |
| das geplante AI-/Translate-Zielbild prüfen wollt | [ai/AI-SERVICES.md](ai/AI-SERVICES.md) |
| Fremd-Assets schrittweise ersetzen wollt | [ASSETS_OwnAssets.md](ASSETS_OwnAssets.md) |
| Plugins entwickelt | [plugins/PLUGIN-DEVELOPMENT.md](plugins/PLUGIN-DEVELOPMENT.md) |
| Themes entwickelt | [theme/THEME-DEVELOPMENT.md](theme/THEME-DEVELOPMENT.md) |

---

## Release-Fokus 2.9.244

Die zentralen Einstiegsdokumente sind jetzt auf den aktuellen Release-Stand `2.9.244` nachgezogen. Der Schwerpunkt liegt diesmal nicht auf neuen Kapiteln, sondern auf einem wieder konsistenten, betriebssicheren Dokumentationsvertrag zwischen Runtime, Update-Metadaten und API-Referenz:

- `DOC/INDEX.md` und `DOC/README.md` zeigen wieder denselben aktuellen Release-Stand wie `README.md`, `Changelog.md`, `CMS/core/Version.php` und `CMS/update.json`
- `DOC/core/STATUS.md` referenziert den laufenden Core jetzt wieder mit `2.9.244` und dem tatsächlichen Release-Datum `2026-04-24` statt einem uralten `2.9.0`-Snapshot
- `DOC/core/API-REFERENCE.md` dokumentiert den öffentlichen Status-Endpunkt `/api/v1/status` jetzt passend zur echten Runtime in `CMS/core/Routing/ApiRouter.php`
- der Status-Endpunkt wird dabei explizit als **flat JSON ohne `data`-Wrapper** beschrieben, während controllerbasierte API-Antworten weiter über `CMS\Api::sendResponse()` im klassischen `{"data": ...}`-Format laufen

---

## Dokumentationsbereiche

### Core

Die Kernsystem-Dokumente unter [`core/`](core/) beschreiben Bootstrap, Routing, Datenmodell, Services, Hooks und Sicherheit.

### Admin

Die Admin-Dokumente unter [`admin/`](admin/) orientieren sich an der aktuellen Sidebar- und Modulstruktur aus `CMS/admin/`.
Dazu gehören jetzt auch die **CMS Loginpage** unter `/admin/cms-loginpage` und **CMS Logs** unter `/admin/cms-logs`, die bewusst als eigene Core-Bereiche für Auth-Branding bzw. Laufzeitdiagnose dokumentiert werden.

### Member

Die Dokumente unter [`member/`](member/) beschreiben den persönlichen Mitgliederbereich unter `/member`, einschließlich Nachrichten, Profil, Datenschutz und Plugin-Integration.

### Theme und Plugins

Die Bereiche [`theme/`](theme/) und [`plugins/`](plugins/) enthalten Entwicklungsleitfäden für Erweiterungen des Systems.

### Workflows und Audits

Die Ordner [`workflow/`](workflow/) und [`audit/`](audit/) dokumentieren operative Abläufe, Live-Audits und technische Bewertungen. Der Audit-Bereich ist jetzt bewusst auf **sechs Sammelaudits plus `ToDoPrüfung.md` und `BEWERTUNG.md`** verdichtet, damit die Pflege nicht mehr über dutzende Einzeldateien zerfällt.

---

## Wichtige Hinweise

- Für **Installations- und Konfigurationsfragen** gelten immer `CMS/config.php` als Stub und `CMS/config/app.php` als eigentliche Konfigurationsdatei.
- Für **aktuelle Admin-Routen** gilt die Sidebar-Konfiguration aus `CMS/admin/partials/sidebar.php` als Referenz.
- Für **Datenbankaussagen** ist [core/DATABASE-SCHEMA.md](core/DATABASE-SCHEMA.md) maßgeblich.
- Für **Release-Änderungen** ist [../Changelog.md](../Changelog.md) die führende Datei.
- Für **Medien- und Upload-Aussagen** gelten [admin/media/README.md](admin/media/README.md), [admin/media/MEDIA.md](admin/media/MEDIA.md) und [workflow/MEDIA-UPLOAD-WORKFLOW.md](workflow/MEDIA-UPLOAD-WORKFLOW.md).
- Für **laufende Qualitätsstände** ist der Bereich [`audit/`](audit/) die erste Anlaufstelle.
- Für **aktuelle Strukturfragen** ist [FILELIST.md](FILELIST.md) die führende lesbare Strukturkarte.
- Für **historisch verifizierte Vollprüfscopes** bleibt [CMSFILESTRUCTUR.md](CMSFILESTRUCTUR.md) relevant, auch wenn es bewusst nicht jede aktuelle Runtime-Unterfläche vollständig ausrollt.

---

## Verwandte Einstiege

- [Dokumentationsindex](INDEX.md)
- [Root-README](../README.md)
- [Projekt-Changelog](../Changelog.md)
- [Audit-Bewertung](audit/BEWERTUNG.md)


