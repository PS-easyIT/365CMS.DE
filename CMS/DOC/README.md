# 365CMS – Projektdokumentation
> **Stand:** 2026-05-02 | **Version:** 2.9.248 | **Status:** Aktuell

## Inhaltsverzeichnis
- [Womit ihr anfangen solltet](#womit-ihr-anfangen-solltet)
- [Release-Fokus 2.9.248](#release-fokus-29248)
- [Dokumentationsbereiche](#dokumentationsbereiche)
- [Wichtige Hinweise](#wichtige-hinweise)
- [Verwandte Einstiege](#verwandte-einstiege)

---
<!-- UPDATED: 2026-05-02 -->

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

## Release-Fokus 2.9.248

Der aktuelle Release-Fokus `2.9.248` schließt die nächsten Lücken im getrennten Sprach- und Medienvertrag für redaktionelle Inhalte:

- Beitrags- und Seiteneditoren speichern getrennte DE-/EN-Ansichten jetzt sprachisoliert: Die jeweils inaktive Sprachfassung wird bei Save und Inline-Fehlerrendering aus dem bestehenden Datensatz erhalten
- Public-Seiten folgen analog zu Beiträgen strikt dem Prefix-Vertrag: `/en/...` liefert nur echte EN-Seitenvarianten, deutsche Pfade bleiben ohne `/en`, alte Suffix-Erkennungen werden nicht mehr als lokalisierte Route behandelt
- Die Coverbild-Auswahl für Seiten und Beiträge zeigt nur noch Dateien mit `ArtikelRahmen_`-Prefix; normale Editor-Medienlisten bleiben unverändert, neue Cover-Uploads bekommen den Prefix automatisch
- `README.md`, `Changelog.md`, `CMS/core/Version.php`, `CMS/update.json`, Marketplace-Metadaten und die zentralen CMS-Dokumente bleiben dabei auf demselben Release-Stand `2.9.248`

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


