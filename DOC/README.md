# 365CMS – Projektdokumentation
> **Stand:** 2026-04-08 | **Version:** 2.9.2 | **Status:** Aktuell

## Inhaltsverzeichnis
- [Womit ihr anfangen solltet](#womit-ihr-anfangen-solltet)
- [Release-Fokus 2.9.2](#release-fokus-292)
- [Dokumentationsbereiche](#dokumentationsbereiche)
- [Wichtige Hinweise](#wichtige-hinweise)
- [Verwandte Einstiege](#verwandte-einstiege)

---
<!-- UPDATED: 2026-04-08 -->

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

## Release-Fokus 2.9.2

Die Dokumentation ist jetzt auf den dokumentierten Stand `2.9.2` nachgezogen. Der Schwerpunkt liegt auf dem jüngsten Runtime-Asset-Refresh und der sauberen Trennung zwischen aktiv genutzten Bundles und nur beobachteten Kandidaten:

- selektiver Runtime-Refresh für `Carbon`, `LdapRecord`, `mailer`, `mime`, `translation`, `tntsearch`, `suneditor`, `editorjs` und `dompdf`
- gezielter Neu-Build des produktiv erwarteten `Editor.js`-Delimiter-Bundles statt Übernahme einer unklaren Altdatei
- aktualisierte Asset-Doku mit präziserem Sonderfall-Hinweis für `SunEditor` und `Editor.js`
- bewusste Nicht-Integration aktuell unreferenzierter Kandidaten wie `cache`, `guzzle`, `php-jwt_yuliyan` und `tabler-icons`
- Entfernung des inoffiziellen `google-translate-php`-Staging-Assets aus `/ASSETS` zugunsten eines providerbasierten AI-Services-Zielbilds
- neue kanonische Konzeptdoku für **AI Services** unter `DOC/ai/AI-SERVICES.md` mit Provider-Scope, Feature-Gates, Provider-Matrix, Editor.js-Datenfluss und einer ersten Phase für Übersetzung nach Englisch
- synchronisierte Release-Spur über `README.md`, `Changelog.md`, `CMS/core/Version.php`, `CMS/update.json`, `DOC/ASSET.md` und `DOC/assets/README.md`

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

Die Ordner [`workflow/`](workflow/) und [`audit/`](audit/) dokumentieren operative Abläufe, Live-Audits und technische Bewertungen.

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


