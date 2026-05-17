# Editor.js

## Kurzbeschreibung

`Editor.js` ist der blockbasierte Editor für moderne Inhaltsbearbeitung in 365CMS.

## Quellordner

- `CMS/assets/editorjs/`

## Verwendung in 365CMS

- Asset-Management in `CMS/core/Services/EditorJsService.php`
- Rendering in `CMS/core/Services/EditorJsRenderer.php`
- Nutzung über Theme-/Frontend-Integration

## Verfügbare Tools (Stand 17.05.2026)

- **Inhaltsstruktur**: `header` (H2-H5), `paragraph`, `list`, `checklist`, `quote`, `delimiter`, `spacer`, `details` (Toggle/Disclosure), `callout`.
- **Tech-Content**: `code` (mit Sprachfeld), `table` (Header-Zeile umschaltbar), `embed`, `linkTool` (Link-Karte), `raw`.
- **Medien & Dateien**: `image` (Mediathek + Upload + URL), `imageGallery` (Mehrfach-Upload + URL-Import), `attaches` (Datei-Upload), `carousel`.
- **Inline-Formatting**: `bold`, `italic`, `underline`, `inlineCode`, `spoiler`, `marker/highlight`, `link`.

## Save-/Render-/Sanitizer-Vertrag

- Neue und bestehende Blöcke werden serverseitig über `EditorJsSanitizer` validiert/sanitized; unbekannte oder ungültige Typen werden verworfen.
- Das Frontend rendert über `EditorJsRenderer` typ-spezifisch und sanitizt Inline-/Raw-Inhalte erneut.
- Legacy-Inhalte (JSON-String, HTML-Fallback, Plaintext) werden clientseitig in `editor-init.js` rückwärtskompatibel in Blockdaten normalisiert.
- Upload- und Link-Requests laufen weiterhin über den bestehenden `/api/media`-Flow inkl. CSRF-Header.

## Bekannte Grenzen

- ToC wird aktuell über `header`-Blöcke/Anker im Frontend-Kontext aufgebaut; ein separater ToC-Editorblock ist noch nicht vorhanden.
- Externe Embed-Provider werden aus Sicherheitsgründen als sichere Link-Embeds (statt unsandboxed iFrame-HTML) ausgegeben.
- Einige UMD-Plugins wurden durch stabile interne Fallback-Tools ersetzt, damit Save/Render ohne defekte Asset-404s funktioniert.

## Sicherheits- und Betriebsvertrag

- Gebündelte Core-Version: `Editor.js 2.31.6` (Upstream-Stand im Audit: `v2.31.6`).
- Gespeicherte Editor.js-JSON-Payloads werden serverseitig über `CMS\Services\EditorJs\EditorJsSanitizer` bereinigt; Client-Sanitizer der Tools sind nur Ergänzung.
- Inline-HTML und Raw-Blöcke laufen über `CMS\Services\EditorJs\EditorJsHtmlSanitizer`: keine Event-Attribute, keine `javascript:`-Links, kontrollierte Link-/Asset-Schemata und strikt erlaubte Tags.
- Der Frontend-Renderer sanitizt Raw-Blöcke erneut vor der Ausgabe, damit ältere oder importierte Inhalte nicht ungefiltert gerendert werden.
- Editor.js-Media-Requests bleiben login-/capability- und CSRF-geschützt; technische Fehlerdetails werden serverseitig geloggt und nicht als JSON-Fehlermeldung ausgegeben.
- Bildpicker und Remote-Bildimport erlauben nur Formate, die die zentrale Medienvalidierung als sichere Bild-Uploads unterstützt (`jpg`, `jpeg`, `png`, `gif`, `webp`, `bmp`, `ico`). SVG und AVIF bleiben im Editor.js-Picker/Remote-Import deaktiviert, solange die zentrale Upload-/Derivative-Pipeline sie nicht vollständig validiert.
- Legacy-/Fallback-Submits nach `editor.save()` nutzen native Submitter bzw. `requestSubmit()` statt direkter `form.submit()`-Bypässe.

## Website / GitHub

- Website: https://editorjs.io/
- GitHub: https://github.com/codex-team/editor.js