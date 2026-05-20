# Editor.js

## Kurzbeschreibung

`Editor.js` ist der blockbasierte Editor für moderne Inhaltsbearbeitung in 365CMS.

## Quellordner

- Originalquelle: `ASSETS/editor.js-2.31.6/editorjs.umd.js`
- Runtime-Ziel: `CMS/assets/editorjs/editorjs.umd.js`

## Verwendung in 365CMS

- Asset-Management in `CMS/core/Services/EditorJsService.php`
- Rendering in `CMS/core/Services/EditorJsRenderer.php`
- Nutzung über Theme-/Frontend-Integration

## Verfügbare Tools (Stand 20.05.2026)

- Aktive Page/Post-Basis-Tools: `paragraph`, `header`, `list` (inkl. `checklist`-Style), `image`, `quote`, `code`, `table`, `delimiter`.
- Zusätzlich aktivierte lokale Erweiterungen: `embed`, `linkTool`, `attaches`, `warning`, `raw`, `accordion`, `imageGallery` sowie Inline-Tools `inlineCode`, `underline`, `spoiler`.
- Produktive Editor-Plugins: `editorjs-undo` für Undo/Redo inkl. Toolbar-Buttons und `editorjs-drag-drop` für Block-Reordering per Drag&Drop. Beide werden lokal als UMD-Dateien geladen und defensiv initialisiert.
- Der Admin-Editor bietet eine WordPress/Gutenberg-ähnlichere Oberfläche: Commandbar mit Block-Inserter, Undo/Redo, Breitenmodus und gruppierte Blockkarten für Text, Medien sowie Layout/Spezialblöcke.
- Nachtrag 19.05.2026: Die lokale Tool-Schicht unterstützt Read-only-Kontexte defensiver, sodass Vorschau- und geschützte Ansichten nicht mehr von editierbaren UI-Annahmen abhängen.
- Der Core wird bytegleich aus `ASSETS/editor.js-2.31.6/editorjs.umd.js` in `CMS/assets/editorjs/editorjs.umd.js` bereitgestellt.
- Die Page/Post-Tools werden als lokale UMD-Dateien aus `CMS/assets/editorjs/` geladen: Core, Basis-Tools und stabile Erweiterungen werden deterministisch vor `CMS/assets/js/editor-init.js` eingebunden.
- `CMS/assets/js/editor-init.js` ist nur noch die 365CMS-Factory/Normalizer-Schicht: Sie verdrahtet die UMD-Globals (`Paragraph`, `Header`, `EditorjsList`, `ImageTool`, `Quote`, `CodeTool`, `Table`, `Delimiter`, `Embed`, `LinkTool`, `AttachesTool`, `Warning`, `RawTool`, `Accordion`, `ImageGallery`, `InlineCode`, `Underline`, `TgSpoilerEditorJS`) sowie die Plugin-Globals (`Undo`, `DragDrop`) mit Upload-, Save-, History- und Legacy-Datenkompatibilität.
- Plugin-Registrierung ist defensiv: optionale Tools werden nur aktiviert, wenn ihr lokales UMD-Global tatsächlich vorhanden ist. Dadurch gibt es keine toten Toolbar-Buttons und keine parallelen Modul-/Eval-Loader.

## WordPress-like Block-/Blockly-Verhalten

Der Admin-Editor ist als redaktioneller Block-Canvas konzipiert, nicht als technischer JSON-Editor. Das Verhalten orientiert sich an Gutenberg-/WordPress-Mustern, bleibt aber vollständig EditorJS-basiert:

- **Block-Inserter:** Blöcke werden über gruppierte Karten eingefügt (`Text`, `Medien`, `Layout`, `Spezial`) statt über eine lange technische Tool-Liste.
- **Commandbar:** Häufige Aktionen wie Einfügen, Undo/Redo und Breitenmodus sind direkt oberhalb des Canvas erreichbar.
- **Drag & Drop:** `editorjs-drag-drop` übernimmt Block-Reordering; der Save-Pfad bleibt bei strukturierter EditorJS-JSON-Ausgabe.
- **Undo/Redo:** `editorjs-undo` ergänzt die gewohnte Redaktionskorrektur ohne zusätzliche Serverzustände.
- **Breitenmodus und Canvas-Typografie:** Der Editor kann kompakter oder breiter wirken und übernimmt relevante Theme-Typografie für eine näher am Frontend liegende Bearbeitungsansicht.
- **Read-only-fähige Tools:** Vorschau- und geschützte Kontexte initialisieren Tools defensiv, damit reine Ansichten nicht durch editierbare UI-Annahmen brechen.

Damit entsteht ein WordPress-ähnliches Blockgefühl, während Sanitizer, Renderer und Theme-CSS weiterhin den sicheren 365CMS-Datenvertrag erzwingen.

## Save-/Render-/Sanitizer-Vertrag

- Neue und bestehende Blöcke werden serverseitig über `EditorJsSanitizer` validiert/sanitized; unbekannte oder ungültige Typen werden verworfen.
- Das Frontend rendert über `EditorJsRenderer` typ-spezifisch und sanitizt Inline-/Raw-Inhalte erneut.
- Nachtrag 19.05.2026: `EditorJsSanitizer` normalisiert Spacer-Höhen aus Presets und Pixelwerten in einen begrenzten Bereich bis `200px`; ungültige Werte fallen auf sichere Defaults zurück.
- Spacer-Blöcke werden im Public-HTML mit kontrolliertem `data-height`, `role="presentation"` und `aria-hidden="true"` ausgegeben; der zentrale Purifier erlaubt diese Attribute, damit Themes die gespeicherte Höhe per CSS-Fallback sichtbar abbilden können.
- Themes, die eigene Rich-Content-Abstände setzen, müssen `.editorjs-spacer[data-height]` aus generischen Absatz-/Block-Margins ausnehmen und die Höhe explizit über `height`/`min-height` oder eine CSS-Variable respektieren.
- Bild- und Galerieblöcke behalten Darstellungsoptionen wie Ausrichtung, Größe, Rahmen, Hintergrund, Rundung und Schatten über normalisierte Datenattribute, damit Themes gezielt stylen können, ohne unsichere HTML-Fragmente zu übernehmen.
- Legacy-Inhalte (JSON-String, HTML-Fallback, Plaintext) werden clientseitig in `editor-init.js` rückwärtskompatibel in Blockdaten normalisiert.
- Bild-Uploads laufen weiterhin über den bestehenden `/api/media?action=upload_image`-Flow inkl. CSRF-Header; alternativ kann das Bild-Tool eine vorhandene URL speichern.
- Page-/Post-Uploads reichen den Editor-Kontext (`content_type`, Slug-/Titel-Fallbacks, `draft_key`) an `/api/media` weiter, damit Bilder direkt in `uploads/articles/...`, `uploads/pages/...` oder temporäre Draft-Ordner einsortiert werden.
- Die lokalen 365CMS-Tools definieren ergänzende Editor.js-Client-Sanitizer, Paste-Substitutionen für Bilder/Bild-URLs sowie Read-only-Support; serverseitige Validierung bleibt verbindlich.
- Die lokale Galerie unterstützt Mehrfachupload, Caption-Pflege, stabile Bilddaten-Normalisierung und Sortierung per `Hoch`/`Runter`, ohne zusätzliche SortableJS-Abhängigkeit.

## Bekannte Grenzen

- ToC wird aktuell über `header`-Blöcke/Anker im Frontend-Kontext aufgebaut; Themes müssen finale Sanitizer-Stufen so verdrahten, dass Heading-IDs erhalten bleiben oder danach stabil neu gesetzt werden. Ein separater ToC-Editorblock ist noch nicht vorhanden.
- Externe Embed-Provider werden aus Sicherheitsgründen als sichere Link-Embeds (statt unsandboxed iFrame-HTML) ausgegeben.
- Fallback-Textareas in Page/Post-Edit-Views sind hidden/disabled und werden nur eingeblendet, wenn die EditorJS-Initialisierung oder Readiness wirklich fehlschlägt.

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