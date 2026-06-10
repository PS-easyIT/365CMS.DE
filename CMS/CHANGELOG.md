# Changelog

Alle nennenswerten Änderungen an **365CMS** werden in dieser Datei dokumentiert.

Das Format orientiert sich an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/),
die Versionierung folgt [Semantic Versioning](https://semver.org/lang/de/).

> Maschinenlesbarer Release-Stand: [`update.json`](update.json). Kanonische Online-Quelle:
> `https://raw.githubusercontent.com/PS-easyIT/365CMS.DE/main/Changelog.md`.

---

## [Unreleased]

### Security
- **MailService:** `mail()`-Fallback härtet jetzt `$to` und `$subject` gegen Header-Injection (Bereinigung auch im Nicht-SMTP-Pfad); Anhangsnamen werden vor der MIME-Header-Interpolation über `sanitizeAttachmentFilename()` von CRLF/Anführungszeichen/Pfadanteilen befreit.
- **Bootstrap:** Neues `hardenErrorReporting()` erzwingt produktiv `display_errors=0` und registriert einen globalen Exception-Handler — keine Stack-Traces mehr an den Client, volle Protokollierung serverseitig. Im Debug-Modus (`CMS_DEBUG`) bleibt die volle Sichtbarkeit erhalten.
- **Escaping:** `esc_js()` neutralisiert zusätzlich `</script>`-Breakout und JS-Zeilenterminatoren (CR/LF, U+2028/U+2029).
- **Mitgliederbereich:** `MemberController::redirect()` lässt absolute URLs nur noch same-origin zu (Open-Redirect-Schutz über `cms_normalize_redirect_target()`).
- **Admin / Featured-Image-Picker:** JSON-Werte im `<script>`-Block werden über eine zentrale `$jsEnc`-Closure mit `JSON_HEX_TAG|HEX_AMP|HEX_APOS|HEX_QUOT` ausgegeben (kein Script-Breakout).

### Added
- **`LICENSE`** ergänzt: **365CMS License (Source-Available, No-Resale)** — freie Nutzung/Anpassung (auch kommerziell in eigenen Projekten), aber kein Verkauf/Weiterverkauf der Software unter eigenem Namen. README-Lizenzabschnitte (DE/EN) und Badge entsprechend gesetzt.

### Documentation
- Neues zweisprachiges Root-`README.md` (DE/EN) als Projekt-Aushängeschild.
- Diese `CHANGELOG.md` neu angelegt (Keep-a-Changelog / SemVer).
- Doku-Hub `DOC/README.md` und Index `DOC/INDEX.md` auf den aktuellen Stand gebracht.
- Interne Code-Audits dokumentiert unter `DOC/AUDIT_core_2026-06-10.md`, `DOC/AUDIT_admin_2026-06-10.md`, `DOC/AUDIT_member-includes-views_2026-06-10.md` (Ergebnis: 0 kritische Funde, alle Defense-in-Depth-Warnungen behoben).

---

## [3.3.47] – 2026-06-05

### Added / Changed
- **EditorJS Bild+Text-Blöcke** speichern eine **Vertikal-Ausrichtung** für Text und Bild. Admin-Vorschau, Sanitizer, Normalizer, Public-Renderer, Critical-CSS und `editorjs-content.css` richten den Text oben, mittig oder unten bündig zum Bild aus.

---

## [3.3.44] – 2026-05-31

### Changed
- **Plugin-Menüs** werden natürlich nach sichtbarem Label sortiert, überschreiben sich bei gleichen numerischen Positionen nicht mehr gegenseitig und bleiben auch bei vielen aktiven Plugins in der Sidebar scrollbar.
- **Plugin-Admincallbacks**, die nur Inhaltsmarkup ausgeben, werden automatisch in den gemeinsamen `page-body`/`container-xl`-Wrapper eingebettet; vollständige Admin-Layouts werden erkannt und nicht doppelt gerendert.

### Documentation
- `DOC/admin/README.md` als verdichtete 3.3.44-Übersicht der Sidebar-/Menügruppen aktualisiert.

---

## [3.3.x] – 2026-05 (kondensiert)

### Changed
- **Settings** und **Mail & Azure OAuth2** visuell vereinheitlicht (reduzierte Badge-/Kacheloptik, konsistente `Header → Toolbar → Inhalt`-Struktur, persistente Info-Boxen). _(17.05.2026)_
- **Aboverwaltung:** Einstellungsseite auf den klassischen `Header → Toolbar → Inhalt`-Vertrag umgestellt. _(17.05.2026)_
- **Medien:** serverseitig erweiterte GET-Filter, per-Admin speicherbare Filter-Presets, Bulk-Kategorisierung/-Tagging/-Alt-Texte, read-only Orphan- und Duplikat-Erkennung, chunkbasierte WebP-/Thumbnail-Jobs mit validierter Statusdatei.

---

## [3.0.x] – 2026-03/04 (kondensiert)

### Added
- Grundgerüst der aktuellen 3.x-Architektur: modularer Admin-Bereich (`admin/modules`, `admin/views`), Hook-/Event-System, capability-basiertes RBAC, Plugin-/Theme-Marktplatz, KI-Services, SEO-Suite, Mitgliederbereich und Subscriptions.
- Web-Installer (`install.php`) mit Umgebungsprüfung, Schema-Erzeugung über `SchemaManager` und sicheren Default-Settings.

> Hinweis: Detaillierte Einträge vor 3.3.44 sind in dieser Datei bewusst zusammengefasst. Die Fachdokumente unter `DOC/` führen die jeweils gültigen Stände pro Bereich.

---

## Legende

| Typ | Bedeutung |
|---|---|
| `Added` | Neue Funktionen |
| `Changed` | Änderungen an bestehender Funktionalität |
| `Deprecated` | Bald entfallende Funktionen |
| `Removed` | Entfernte Funktionen |
| `Fixed` | Fehlerbehebungen |
| `Security` | Sicherheitsrelevante Änderungen |

[Unreleased]: https://github.com/PS-easyIT/365CMS.DE/compare/v3.3.47...HEAD
[3.3.47]: https://github.com/PS-easyIT/365CMS.DE/releases/tag/v3.3.47
[3.3.44]: https://github.com/PS-easyIT/365CMS.DE/releases/tag/v3.3.44
