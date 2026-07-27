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

### Documentation
- Neues zweisprachiges Root-`README.md` (DE/EN) als Projekt-Aushängeschild.
- Diese `CHANGELOG.md` neu angelegt (Keep-a-Changelog / SemVer).
- Doku-Hub `DOC/README.md` und Index `DOC/INDEX.md` auf den aktuellen Stand gebracht.
- Interne Code-Audits dokumentiert unter `DOC/AUDIT_core_2026-06-10.md`, `DOC/AUDIT_admin_2026-06-10.md`, `DOC/AUDIT_member-includes-views_2026-06-10.md` (Ergebnis: 0 kritische Funde, alle Defense-in-Depth-Warnungen behoben).

---

## [3.3.56] – 2026-07-05

### Fixed
- **Suche (`/search`)**: `ThemeRouter::renderSearch()` normalisiert `?type=` jetzt über eine Alias-Tabelle (`post→posts`, `page→pages`, `category→categories`, `tag→tags`, `company→companies`, `event→events`, `speaker→speakers`, `expert→experts`). Dadurch scopen auch ältere/zwischengespeicherte Suchformulare (z. B. hinter einem Reverse-Proxy/CDN-Cache) korrekt, ohne auf ein Neu-Deployment des HTML-Formulars angewiesen zu sein.
- **Suche / Relevanz**: `mb_strtolower()` in der Relevanz-Bewertung nutzt jetzt explizit `UTF-8` statt der PHP-internen Kodierung – verhindert falsch negative Exakt-Treffer bei Titeln mit Umlauten (ä/ö/ü/ß).

---

## [3.3.55] – 2026-07-05

### Fixed
- **EditorJS Bild+Text-Block**: Vertikal-Ausrichtung (oben/mittig/unten) blieb weiterhin wirkungslos – Text stand immer oben, Bild immer unten, unabhängig von der gewählten Einstellung. `align-items` auf dem Grid-/Flex-Container allein reichte nicht aus; jetzt wird `align-self` zusätzlich direkt auf `.editorjs-media-text__media` und `.editorjs-media-text__content` gesetzt (Core `editorjs-content.css`, PHINIT `rich-content.css` – beide Vorkommen – sowie die Admin-Live-Vorschau in `admin.css`).

---

## [3.3.54] – 2026-07-05

### Fixed
- **Suche (`/search`)**: Der Scope-Filter (Dropdown „Typ filtern“) sendete Singular-Werte (`post`, `page`, `company`, `event`), während `ThemeRouter::renderSearch()` ausschließlich Plural-Werte (`posts`, `pages`, `companies`, `events`) auswertete — eine gezielte Suche in nur einem Scope lieferte dadurch immer 0 Treffer, während „Alle Typen“ (leerer Filter) weiterhin funktionierte.
- **Suche / Relevanz**: Ergebnisse aus Seiten, Beiträgen, Kategorien und Tags wurden bisher nur pro Typ aneinandergehängt (keine typübergreifende Sortierung); exakte Titeltreffer landeten dadurch oft nicht oben. Neue `sortSearchResultsByRelevance()`-Nachsortierung reiht exakte/beginnende Titeltreffer immer vor reine Volltextfunde ein.

### Changed
- **Suchscope**: Der Standard-Scope („Alle Typen“) umfasst jetzt nur noch Beiträge, Seiten, Kategorien und Tags. Firmen-/Event-/Speaker-Suche (aus den Marketplace-Plugins) läuft nur noch bei explizit gesetztem `type`-Parameter, nicht mehr automatisch im Alle-Scope mit — das reduziert die Trefferflut auf themenfremde Inhalte.

### Added
- **Kategorien-/Tag-Suche**: `/search?type=categories` bzw. `?type=tags` durchsucht jetzt `post_categories`/`post_tags` (Name + Beschreibung) und verlinkt auf die jeweilige Archivseite.

---

## [3.3.53] – 2026-07-05

### Fixed
- **EditorJS Code-Block**: XML-/Markup-lastiger Code wurde nach dem Speichern verstümmelt (z. B. zu „true rue rue 3“). Ursache: `CmsCodeTool` setzte `static get sanitize() { return { code: false } }` — im Editor.js-Kern bedeutet `false` jedoch „mit leerer Tag-Allow-List sanitieren“ (alle `<...>`-Muster werden entfernt), nicht „nicht sanitieren“. Auf `code: true` korrigiert, wodurch der Rohtext beim Speichern unangetastet bleibt. `CmsButtonTool` (neu in 3.3.52) war vom selben Missverständnis betroffen und wurde ebenfalls korrigiert.

---

## [3.3.52] – 2026-07-05

### Added
- **EditorJS Button-Block**: Neuer Block für Link + Button-Text mit einstellbarer Ausrichtung (links/mittig/rechtsbündig), Farbe (primary/secondary/info/success/warning/danger/light/dark) und Größe (klein/mittel/groß). Vollständig über CSS-Klassen gerendert (kein Inline-`style`), damit die Darstellung auch nach der finalen Purifier-Stufe auf Page-/Post-Seiten erhalten bleibt.

### Fixed
- **EditorJS Bild+Text-Block**: Die Vertikal-Ausrichtung (oben/mittig/unten) wird jetzt mit `!important` gegen konkurrierendes CSS abgesichert (Core-CSS, Critical-CSS und PHINIT-Theme-CSS betroffen), damit die im Editor gewählte Ausrichtung öffentlich zuverlässig übernommen wird.

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
