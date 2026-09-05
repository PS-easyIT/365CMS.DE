**Version:** 3.4.00

# 365CMS Changelog

## 📋 Legende

| Symbol | Typ | Bedeutung |
|--------|-----|-----------|
| 🟢 | `feat` | Neues Feature |
| 🔴 | `fix` | Bugfix |
| 🟡 | `refactor` | Code-Umbau ohne Funktionsänderung |
| 🟠 | `perf` | Performance-Verbesserung |
| 🔵 | `docs` | Dokumentation |
| ⬜ | `chore` | Wartungsarbeit / Release |
| 🛡️ | `security` | Sicherheits- und Audit-Härtung |

---

### v3.4.00 — 05.09.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.4.00** | 🛡️ security | AI Services / Admin-only | Vollständige zentrale Härtung und Fertigstellung der AI-Workflows: Runtime-Policy erzwingt Feature-/Provider-/Editor.js-Scopes, Beta- und External-Egress-Freigaben; Cloud-Provider benötigen HTTPS, Ollama nutzt eine exakte interne Host-Allowlist. Atomare UTC-Tages-/Monatsquoten, maximal zwei Retries, policy-/quota-geprüfter Fallback, inhaltsfreier Healthcheck, Secret-bereinigte Provider-Löschung, serverseitige Re-Sanitierung von Übersetzungen und unveränderliche Prompt-Verträge sind umgesetzt. AI-Admin-JavaScript ist CSP-konform ausgelagert; PublicRouter, Themes und Member-Bereich erhalten keine AI-Route, kein Asset und keine automatische Persistenz. |
| **3.4.00** | 🟢 feat | Installation / Updates | Schema-Version `v22` registriert die atomare Tabelle `ai_quota_usage` zentral für Neuinstallationen und bestehende Systeme. Installer und Admin-Updater zeigen installierte/angestrebte Core- und Schema-Versionen an; das Schema kann idempotent und ohne Inhaltsänderung aktualisiert werden. Core-Update-Swaps bewahren `config/`, Uploads, Cache, Logs und Backups. Die Pakete `365CMS-3.4.0-update.zip` und `365CMS-3.4.0-full.zip` enthalten ausschließlich auslieferbaren Code ohne installationsspezifische Daten; Download-Manifest und SHA-256 liegen bei. |

---

### v3.3.81 — 05.09.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.81** | 🟢 feat | AI / Content Creator | Der bisher vorbereitete Bereich ist jetzt produktiv als geschützter Admin-Workflow umgesetzt: Redaktionelle Briefings und optionale Kontexte liefern Kurzfassungen, Gliederungen oder CTA-Varianten als reine Review-Entwürfe. Feature-/Provider-Gates, CSRF, Prompt-Injection-Leitplanken, Eingabegrenzen und datensparsame Audit-Metadaten greifen zentral über `AiService`; kein Ergebnis wird automatisch gespeichert oder veröffentlicht. |

---

### v3.3.80 — 05.09.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.80** | 🟢 feat | AI / SEO Creator | Der Page-/Post-Editor enthält jetzt „SEO mit AI füllen“. Aus ausschließlich freigegebenen EditorJS-Haupttextsegmenten entstehen ein noch ungespeicherter Entwurf für Kurzfassung, Fokus-Keyphrase, Keywords, Meta-/Open-Graph-/X-Texte, Twitter Card, Schema-Typ, Sitemap und Robots. CSRF, Berechtigungen, Feature-/Provider-Gates, Größenlimits, Prompt-Injection-Leitplanken, Audit-Metadaten und Feld-Whitelist sind aktiv; Dokumenttitel, Slug, Canonical-, Bild- und hreflang-Felder können technisch nicht übernommen werden. |

---

### v3.3.79 — 05.09.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.79** | 🟠 perf | AI / EditorJS-Übersetzung | Große Artikel werden für den AI-Provider nun in geordnete, auf 3.500 Zeichen und 20 Segmente begrenzte Batches geteilt. Dadurch bleiben Anfrage und JSON-Antwort unter Modellgrenzen; die Zusammenführung erhält Blockreihenfolge und Vorschau unverändert. Die geschützte Route und der Browser erlauben für die Verarbeitung jeweils bis zu fünf Minuten statt pauschal 45 Sekunden im Browser. |

---

### v3.3.78 — 23.08.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.78** | 🔴 fix | EditorJS / Mehrfach-Editoren | Mehrere über `EditorJsAssetService` gerenderte EditorJS-Instanzen innerhalb desselben Formulars koordinieren beim Absenden ihre jeweiligen `save()`-Vorgänge. Der Save-Status wird pro Hidden-Editor-ID geführt und das Formular erst übermittelt, wenn alle registrierten Editoren erfolgreich gespeichert wurden, sodass kein zweiter Beschreibungseditor mit einem veralteten Hidden-JSON-Wert übergangen wird. |

---

### v3.3.77 — 28.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.77** | 🛡️ security | PHINIT / Content-Fallback | Der PHINIT-Notfall-Sanitizer ist jetzt DOM-basiert und allowlistgesteuert. Sichere EditorJS-Bilder, Bildunterschriften und Quellenlinks bleiben sichtbar; unquotierte `javascript:`-URLs sowie Event-Attribute werden entfernt. Der Core-Purifier-Cache wird nach der Erweiterung erlaubter Linkattribute über eine neue Definition-Revision zuverlässig neu aufgebaut. |
| **3.3.77** | 🔴 fix | EditorJS / Einzelbilder | Bildquellen werden client- und serverseitig nur als absolute HTTP(S)-URL akzeptiert und Public als sichere **„QUELLE ↗“**-Zeile nach der optionalen Bildunterschrift ausgegeben. Eine Regression, bei der Einzelbilder im PHINIT-Frontend durch einen HTML-escapenden Fallback verschwanden, ist behoben. |
| **3.3.77** | 🔴 fix | EditorJS / Clipboard & Presets | Clipboard übernimmt Cropper-Tunes, verwendet keinen veralteten In-Memory-Paste-Fallback und meldet vollständige bzw. partielle Insert-Erfolge korrekt. Nicht unterstützte Delimiter-Breiten und Bild-Maximalhöhen werden konsistent auf die nächstgelegenen vorhandenen CSS-Presets gerundet. |
| **3.3.77** | 🔵 docs | Audit | Renderer-, Sanitizer-, Purifier-, Clipboard- und PHINIT-Pfade wurden durch Runtime-/Purifier-/Regressionstests geprüft; JavaScript/PHP/JSON-Diagnosen sind fehlerfrei. |

---

### v3.3.76 — 28.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.76** | 🟢 feat | EditorJS / Zitat-Block | **Der Zitat-Block bietet jetzt 4 auswählbare Designs: Balken (Standard), Karte, Minimal und Anführungszeichen**, umsetzbar direkt über die Block-Einstellungen im Editor (`CmsQuoteTool` in `editor-init.js`, erweitert den Standard-Quote-Tool um ein `design`-Feld). `EditorJsRenderer::renderQuote()`, `EditorJsSanitizer` und `EditorJsContentNormalizer` validieren/rendern das neue Feld serverseitig (`editorjs-quote--design-*`-Klasse). |
| **3.3.76** | 🎨 style | EditorJS / Zitat-Block | Der Standard-Balken ("Balken"-Design) ist jetzt dünner und abgeschwächt (Farb- und Hintergrundmischung über `color-mix()` statt vollgesättigter Akzentfarbe) und nutzt Theme-Variablen, damit er auch im Dark Mode stimmig bleibt, statt hart auf Weiß gemischt zu werden (`CMS/assets/css/editorjs-content.css`). |
| **3.3.76** | 🔴 fix | EditorJS / Trennstrich | **Der Trennstrich blieb unabhängig von der gewählten Einstellung als mittiger 25%-Strich sichtbar.** Der minifizierte Drittanbieter-Delimiter wurde für den aktiven Editorpfad durch ein eigenständiges `CmsDelimiterTool` ersetzt. Direkt sichtbare Selects für Stil, Breite (8/15/25/35/50/60/100 %) und Dicke (1–6 px) steuern dieselben Werte, die `save()` deterministisch über `normalizeDelimiterData()` serialisiert; jede Änderung löst EditorJS-/CMS-Change-Events aus. Der Renderer ergänzt sanitizer-feste Klassen wie `editorjs-delimiter--width-60` und `editorjs-delimiter--thickness-5`, Core- und PHINIT-CSS rekonstruieren daraus die Public-Darstellung auch ohne Inline-Styles. Ein isolierter Runtime-Test bestätigt Auswahl, Preview und Speicherung mit 60 %/5 px. |
| **3.3.76** | 🔴 fix | EditorJS / Bilder | **Breite, Ausrichtung und Skalierung des Bilder-Blocks hatten im Public-Frontend keine bzw. kaum sichtbare Wirkung.** Das Admin-Tool markiert Optionsänderungen jetzt zusätzlich über `block.dispatchChange()` als gespeichert. `EditorJsRenderer` gibt sanitizer-feste Klassen für Ausrichtung, Breitenpreset, Fit-Modus und Maximalhöhe aus (`editorjs-image--align-*`, `--normal|wide|full`, `--fit-*`, `--max-height-*`). Normal entspricht wieder der natürlichen Bildbreite, Breit bis 1000 px und Strecken 100 %; die Inline-Fallbacks wurden daran angeglichen. `editorjs-content.css` setzt alle Optionen klassenbasiert um. |
| **3.3.76** | 🟢 feat | EditorJS / Einzelbild-Quelle | Der Einzelbild-Block hat nun das Feld **„Quellwebsite (URL)“**. Ausschließlich absolute HTTP(S)-URLs werden in Client-Normalizer, Content-Normalizer, Sanitizer und Renderer akzeptiert. Public erscheint nach einer optionalen Bildunterschrift die sichere externe Linkzeile **„QUELLE ↗“** (`target="_blank"`, `rel="noopener noreferrer external nofollow"`); Core- und PHINIT-CSS gestalten sie dezent. |
| **3.3.76** | 🔴 fix | PHINIT / EditorJS-Bilder | Eine fehlgeschlagene Purifier-/KSES-Initialisierung führte im letzten Theme-Fallback dazu, dass gerendertes Bild-HTML vollständig escaped wurde und Einzelbilder nicht mehr sichtbar waren. `phinit_sanitize_renderable_content_fallback()` verwendet jetzt einen DOM-basierten Allowlist-Sanitizer: sichere Figure-/Image-/Caption-/Link-Markups bleiben erhalten, unquotierte `javascript:`-URLs und Event-Attribute werden entfernt. |
| **3.3.76** | 🔴 fix | EditorJS / Clipboard & Presets | Block-Clipboard verwirft Cropper-/CropperTune-Daten nicht mehr; Insert-Erfolge werden gezählt und Teilerfolge korrekt gemeldet, statt pauschal Erfolg zu melden. Ein veralteter In-Memory-Clipboard-Fallback kann keinen alten Block mehr über spätere Bild-Pastes legen. Delimiter-Breiten und Bild-Maximalhöhen werden in JS, Normalizer, Sanitizer und Renderer konsequent auf ihre unterstützten CSS-Presets gerundet. |
| **3.3.76** | 🟢 feat | EditorJS / Block-Clipboard | **Jeder EditorJS-Block kann jetzt als vollständiger Block kopiert und in einem anderen EditorJS-Bereich wieder eingefügt werden.** Der universelle `CmsBlockClipboardTune` ergänzt bei allen Blocktypen den Menüpunkt „Block kopieren“. Markierte `.ce-block--selected`-Blöcke lassen sich zusätzlich gemeinsam per `Strg/Cmd+C` kopieren. Ein versioniertes Clipboard-Format erhält Typ, Daten und Tunes; beim Einfügen werden die Blöcke normalisiert, über `blocks.insert()` angelegt und Anker/Einzug/Ausrichtung/Textvariante über `blocks.update(..., tunes)` wiederhergestellt. Das funktioniert editorübergreifend und mit sicherem Clipboard-Fallback. |
| **3.3.76** | 🔴 fix | EditorJS / Zitat-Block | **Nach dem Speichern gingen Textformatierungen (Fett/Kursiv/Links) im Zitat-Text verloren – im Editor wie im Live-/Public-Rendering.** Die bestätigte Ursache lag in der blockeigenen EditorJS-Sanitize-Konfiguration: Das Stock-`Quote`-Tool erlaubt für `text`/`caption` nur `<br>`. Weil die globale `inlineToolbar` deaktiviert ist, ergänzt EditorJS dort keine Allowlist der Inline-Tools und entfernt beim finalen `editor.save()` alle übrigen Tags. `CmsQuoteTool.sanitize` verwendet jetzt für Text und Quelle explizit `getInlineSanitizeConfig()` – dieselbe vollständige Allowlist wie der funktionierende `ParagraphTool` – und erhält dadurch Fett, Kursiv, Links, Unterstreichung, Inline-Code, Markierungen und weitere erlaubte Inline-Formate. `CmsQuoteTool.save()` normalisiert zusätzlich wie der Paragraph-Block das eingefügte HTML vor der Serialisierung. |
| **3.3.76** | 🔴 fix | EditorJS / Zitat-Block | **Eigentliche Ursache gefunden: Formatierten Text (Fett/Links) aus der Zwischenablage in einen Zitat-Text/-Quelle einzufügen, tat schlicht nichts** – das vendorte Stock-`Quote`-Tool implementiert kein `onPaste`/`pasteConfig`, wodurch EditorJS' generisches Paste-Routing den Inhalt nicht einfügen konnte (nur reiner Text kam durch, da dafür ein anderer Fallback-Pfad greift). `CmsQuoteTool` bindet jetzt an Zitat-Text und -Quelle die bereits im CMS bewährte Paste-Behandlung (`bindEditablePasteBehavior`, dieselbe Funktion, die u. a. Absatz-/Überschrift-Felder nutzen), wodurch eingefügter formatierter Inhalt zuverlässig übernommen wird. |

---

### v3.3.75 — 27.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.75** | 🟢 feat | Admin / Kommentare – Papierkorb | **Der Papierkorb der Kommentarverwaltung hat jetzt einen eigenen „Papierkorb leeren“-Button, der alle Kommentare mit Status „trash“ in einem Klick endgültig löscht.** Neue `CommentService::deleteAllTrashed()`- und `CommentsModule::emptyTrash()`-Methoden sowie eine neue `empty_trash`-Aktion in `comments.php` ergänzen die bestehende Einzel-/Sammellöschung; der Button erscheint nur im Papierkorb-Tab (nur mit Löschrecht, nur wenn Einträge vorhanden sind) und nutzt denselben generischen `data-confirm-*`-Bestätigungsmechanismus wie die übrigen Aktionen. |

---

### v3.3.74 — 27.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.74** | 🟢 feat | Admin / Legal Sites – Impressum-Vorlage | **Die generierte Impressum-Vorlage enthält den seit 20.07.2025 abgeschalteten EU-Streitschlichtungs-/OS-Plattform-Hinweis (samt Verbraucherschlichtungs-Passus) nicht mehr und benennt für Magazin-, Blog- oder News-Angebote zusätzlich einen Verantwortlichen nach § 18 Abs. 2 Medienstaatsvertrag (MStV).** `LegalSitesModule::buildImprintTemplate()` entfernt den kompletten „EU-Streitschlichtung“-Block; das zugehörige `legal_profile_dispute_participation`-Feld wurde vollständig entfernt (Formular, Sanitizer, Defaults, POST-Allowlist). Neu: Toggle „Magazin / Blog / News“ (`legal_profile_has_editorial_content`) in den Website-Funktionen sowie die Felder `legal_profile_mstv_responsible_name`/`legal_profile_mstv_responsible_address`, die bei Aktivierung automatisch einen zusätzlichen, korrekt zitierten MStV-Abschnitt (Name + Anschrift, Fallback auf Firmenanschrift) ins Impressum einfügen. Vorlagenversion auf 2026.07.27 angehoben. |

---

### v3.3.73 — 27.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.73** | 🔴 fix | Admin / Kommentare – Sammellöschung | **Die Sammelaktion „Endgültig löschen“ in der Kommentarverwaltung blieb ohne jede Reaktion (kein Bestätigungsdialog, kein Neuladen), wenn der zentrale `cmsConfirm()`-Bestätigungsdialog beim Aufruf eine Ausnahme auslöste.** `admin-comments.js` umschließt den Aufruf jetzt mit try/catch (analog zum bereits gehärteten `initConfirmForms()` in `admin.js`) und protokolliert einen Fehler per `console.warn`, statt die Sammellöschung stillschweigend abzubrechen; danach greift zuverlässig der native `window.confirm()`-Fallback. |

---

### v3.3.72 — 27.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.72** | 🟢 feat | Admin / Tabellen-Editor & Hub-Sites-Editor | **Spalten und Zeilen im Tabellen-Editor sowie die Kacheln im Hub-Site-Editor lassen sich im Adminbereich jetzt per Drag & Drop neu anordnen.** `admin-site-tables.js` ergänzt Ziehgriffe für `columnsBody`/`rowsBody` (nativer HTML5-Dragdrop, Reihenfolge landet direkt in `columns_json`/`rows_json`); `admin-hub-site-edit.js` erhält je Kachel eine Kopfzeile mit Ziehgriff sowie Auf/Ab-Buttons für tastaturgesteuertes Verschieben. Neues Stylesheet `assets/css/admin-site-tables.css` sowie Ergänzungen in `assets/css/admin-hub-site-edit.css` liefern die Drag-/Drop-Zielhervorhebung. |

---

### v3.3.71 — 18.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.71** | 🎨 style | Theme cms-phinit / Startseite – Listcard-Kategorie | **Das Kategorie-Badge im Vorschaubild sichtbarer Startseiten-Listcards sitzt jetzt bündig und kantig oben links.** `assets/css/homepage-blog.css` und das dazugehörige `homepage-blog-critical.css` entfernen den bisherigen 6px-Abstand, die Rundung, den weißen Rand und Schatten von `.thumb-badge`; dadurch schließt die türkise Kategorie-Kachel direkt am Bildrand an und bleibt beim Critical-Rendern identisch. |

---

### v3.3.70 — 18.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.70** | 🎨 style | Theme cms-phinit / Beitragsdetail – Update-Badge | **Der Text des `[UPDATE | dd.MM.YY]`-Badges ist jetzt dunkler goldfarben.** `assets/css/post-detail.css` verwendet dafür `#8a5a05` statt des helleren Theme-Akzentgolds. Im Dark Mode wechselt die Badge-Fläche zu einem warmen hellen Ton, damit der dunkle Goldtext weiterhin kontrastreich und gut lesbar bleibt. |

---

### v3.3.69 — 18.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.69** | 🎨 style | Theme cms-phinit / Beitragsdetail – Header-Badges | **Lesezeit- und `[UPDATE | dd.MM.YY]`-Badge sind jetzt kantig und schließen ohne Abstand direkt mit den Bildrändern ab.** `assets/css/post-detail.css` entfernt abgerundete Ecken, Schatten und Außenabstände: Die Lesezeit sitzt an `top: 0; left: 0`, das goldfarbene Update-Badge an `right: 0; bottom: 0`. Die Regeln gelten gleichermaßen in der Desktop-, Tablet- und Mobilansicht sowie im Dark Mode. |

---

### v3.3.68 — 18.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.68** | 🔴 fix | Theme cms-phinit / Beitragsdetail – Header-Badges | **Die Lesezeit bleibt wieder oben links im Content-Headerbild; das optionale `[UPDATE]`-Badge steht davon getrennt oben rechts.** `assets/css/post-detail.css` nutzt für die gemeinsame Overlay-Leiste nun die volle Bildbreite mit `justify-content: space-between`; ohne Lesezeit richtet sich das Update-Badge per `margin-left:auto` weiterhin rechts aus. Der Badge-Text verwendet im hellen und dunklen Design den Gold-Accent `--accent-color`. |

---

### v3.3.67 — 18.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.67** | 🟢 feat | Theme cms-phinit / Beitragsdetail – Update-Badge | **Das manuelle `[UPDATE]`-Badge erscheint jetzt zusätzlich im Content-Headerbild von Beitragsdetails direkt rechts neben der Lesezeit.** `cms-phinit/partials/post-header.php` prüft ausschließlich das optionale Feld `content_updated_at`, gruppiert Lesezeit und Update-Badge im gemeinsamen Headerbild-Overlay und wird dadurch automatisch in Standard-, Wide- und Tech-Beitragsdetailtemplates angewendet. `assets/css/post-detail.css` positioniert die Leiste oben rechts im Bild, gestaltet das Update-Badge bewusst dezent und stellt Dark-Mode-Kontraste sicher. Ohne gefülltes Aktualisierungsfeld wird kein Badge ausgegeben; ohne Lesezeit erscheint das Update-Badge allein an derselben Headerposition. |

---

### v3.3.66 — 18.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.66** | 🟢 feat | Installer & Datenbank / versionsgeführtes Update | **Zentraler, versionsgeführter Datenbank-Updater für bestehende Installationen.** Neue Klasse `CMS\DatabaseUpdateRunner` liest den installierten Stand aus `cms_settings` (`installed_cms_version`, `db_schema_version`), blockiert automatische Downgrades und führt ausschließlich `Database::repairTables()` aus – damit werden die bereits zentral gepflegten, idempotenten `SchemaManager`-/`MigrationManager`-Migrationen ohne Datenlöschung ausgeführt. Erst nach Erfolg werden Core-Version, Zeitstempel und Schema-Version aktualisiert; ein Eintrag in der bestehenden Update-Historie wird nach Möglichkeit protokolliert. Die neue `CMS/update.php` ist im Web ausschließlich für eingeloggte Administratoren mit `manage_settings`-Berechtigung erreichbar, nutzt CSRF-Schutz und kann im CLI-Modus mit `php update.php --status`/`--dry-run` oder ohne Parameter zur Ausführung verwendet werden. `CMS/install.php` markiert nun explizit den Installer-Kontext; der bestehende Installer-Update-Schritt verwendet ebenfalls den zentralen Runner statt eines separaten Tabellenpfads. Neuinstallationen speichern ihren installierten Core-/Schema-Stand sofort. `SchemaManager` und `MigrationManager` sind auf gemeinsame Schema-Version `v21` vereinheitlicht, damit bestehende Instanzen die neuen zentralen Kompatibilitätsmigrationen zuverlässig ausführen. |

---

### v3.3.65 — 18.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.65** | 🔴 fix | Theme cms-phinit / Startseite – Beitragsabfragen | **Die Startseite zeigt Beiträge auch dann weiter an, wenn die Datenbank die neue Spalte `content_updated_at` noch nicht enthält.** `includes/theme-home-helpers.php::phinit_get_homepage_posts_payload()` prüft vor den beiden Startseiten-Queries (Artikel-Liste und Kachel-Grid), ob die Spalte bereits existiert. Bei einer noch ausstehenden Migration wird `NULL AS content_updated_at` selektiert statt `p.content_updated_at`; damit bleibt das optionale Update-Badge einfach leer, während die Beitragslisten vollständig laden. Der Check ist zusätzlich gegen einen Fehler beim Schema-Check abgesichert. Das Badge verwendet jetzt im Deutschen exakt die gewünschte Beschriftung `[UPDATE]`. |

---

### v3.3.64 — 18.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.64** | 🟢 feat | Admin & Theme / Seiten + Beiträge – Aktualisierungsdatum | **Neues optionales „Aktualisierungsdatum“-Feld für Seiten und Beiträge, das unabhängig vom automatischen `updated_at` manuell gepflegt wird.** Neue Spalte `content_updated_at` (`CMS/core/SchemaManager.php`, Migrationen in `PostsModule`/`PageManager`) ist nur gesetzt, wenn die Redaktion sie im Editor ausfüllt; ist sie leer, erscheint auf keiner Detailseite ein Hinweis. `CMS/admin/modules/posts/PostsModule.php` und `CMS/admin/modules/pages/PagesModule.php` validieren Datum+Uhrzeit (`normalizeContentUpdatedAtInput()`) und speichern das Feld. Im Beitrags-Editor (`CMS/admin/views/posts/edit.php`) gibt es zusätzlich die nicht persistierte Checkbox „Als neuen Beitrag behandeln“: aktiv setzt sie das Veröffentlichungsdatum auf das Aktualisierungsdatum, wodurch der Beitrag in allen bestehenden Sortierungen (Startseite, Archiv, Sitemap, RSS) wieder oben erscheint; ohne hinterlegtes Aktualisierungsdatum wird die Aktion mit einer verständlichen Validierungsmeldung abgewiesen. Die Detailseiten `post.php`, `post-wide.php`, `post-tech.php`, `page.php`, `page-wide.php` und `page-landing.php` zeigen den Hinweis ausschließlich aus dem manuellen Feld. Auf der Startseite (`partials/post-card.php`, `partials/home-post-grid.php`, einschließlich der Homepage-Queries) erscheint bei gesetztem Datum ein sehr dezentes „Update“-Badge; es bleibt auch sichtbar, wenn gewöhnliche Karten-Metadaten im Customizer abgeschaltet sind. Das Feld wird vollständig in die Revisions-Snapshots und -Vergleiche von Beiträgen sowie Seiten aufgenommen. Automatische SEO-Weiterleitungen bei Slug-Änderungen bestehen für Seiten und Beiträge; bei datumsbasierten Beitrags-URLs verwenden sie bei „als neu behandeln“ jetzt korrekt das neue Veröffentlichungsdatum als Zielpfad. |

---

### v3.3.63 — 18.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.63** | 🔴 fix | Theme cms-phinit / Startseite – alternativer Autor | **Der alternative Autoren-Website-Link (`author_display_url`) wird jetzt auch auf der Startseite korrekt aufgelöst statt immer auf die interne Autoren-Info-Seite zu verlinken.** `includes/theme-home-helpers.php::phinit_get_homepage_posts_payload()` selektierte in den Queries für die „Aktuelle Artikel-Liste“ und das „Kachel-Grid“ zwar `p.author_id` und den per `author_display_name` überschriebenen `author_name`, aber nicht `p.author_display_url` – dadurch erhielt der bereits in v3.3.62 eingeführte Helper `phinit_resolve_post_author_link()` in `partials/post-card.php` und `partials/home-post-grid.php` auf der Startseite immer einen leeren Wert und fiel auf die interne `/author/user-{id}`-Seite zurück, obwohl eine externe Website hinterlegt war. Beide SQL-Abfragen ergänzen jetzt `p.author_display_url`. Die Beitrags-Detailseite (`post.php`/`post-wide.php`/`post-tech.php`, Autorbox + Byline) war bereits zuvor korrekt, da sie `SELECT p.*` nutzt. Das Featured-Banner und das Sidebar-„Featured Posts“-Widget zeigen ohnehin keine Autoreninfo an und benötigten keine Änderung. |

---

### v3.3.62 — 13.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.62** | 🟢 feat | Admin & Theme / Beiträge – alternativer Autor | **Optionale Website für den alternativen Autoren-Anzeigenamen von Beiträgen – der Autorenname verlinkt dann im neuen Tab auf diese Website statt auf die interne Autoren-Info-Seite.** Neue Spalte `author_display_url` (`CMS/core/SchemaManager.php`, Migration in `PostsModule::ensureColumns()`/`ensurePostRevisionTable()`) ergänzt das bestehende Feld `author_display_name`. `CMS/admin/modules/posts/PostsModule.php` validiert die URL beim Speichern (`sanitizeAuthorDisplayUrl()`, nur `http(s)://`), persistiert sie in Insert/Update sowie in der Beitrags-Revisionshistorie (Vergleich, Snapshot, Diff-Anzeige „Website des alternativen Autors“). `CMS/admin/posts.php` und `CMS/admin/views/posts/edit.php` ergänzen ein neues Formularfeld „Website des alternativen Autors“ direkt unter dem Anzeigenamen-Feld. Im `cms-phinit`-Theme löst die neue Helper-Funktion `phinit_resolve_post_author_link()` (`includes/theme-template-helpers.php`) zentral auf, ob der Autorenname auf die hinterlegte externe Website (neuer Tab, `target="_blank" rel="noopener noreferrer"`) oder weiterhin auf die interne `/author/user-{id}`-Seite verlinkt – eingebunden in die Autorbox (`partials/post-author-box.php`, `phinit_build_author_box_context()`), den Beitrags-Header (`partials/post-header.php`), Artikelkarten (`partials/post-card.php`) und das Home-Grid (`partials/home-post-grid.php`) sowie alle drei Beitragsvorlagen (`post.php`, `post-wide.php`, `post-tech.php`). |

---

### v3.3.61 — 06.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.61** | 🟢 feat | Member / Profil & öffentliches Autorenprofil | **Das Profilfeld „Über mich“ nutzt jetzt den EditorJS-Block-Editor statt eines einfachen Textfelds, inklusive korrekter Formatierungs-/Zeilenumbruch-Übernahme auf die öffentliche Autorenseite.** `CMS/admin/modules/member/MemberDashboardModule.php` und die Fallback-Definitionen in `CMS/member/includes/class-member-controller.php` ändern den Feldtyp von `bio` auf `wysiwyg`; `CMS/member/profile.php` rendert für diesen Typ den Block-Editor über `EditorService::render()` statt eines `<textarea>`. Beim Speichern (`handleProfileRequest()`) sanitiert eine neue Methode `sanitizeWysiwygProfileFieldValue()` den Editor-Inhalt über `EditorService::sanitize()` und speichert bei leerem Editor-Inhalt bewusst einen leeren String, damit Profilvollständigkeits-Anzeigen weiterhin korrekt funktionieren. Auf der öffentlichen Autorenseite (`CMS/themes/cms-default/author.php`) wird die Biografie nun über `EditorService::renderContent()` gerendert statt nur `htmlspecialchars()`-escaped auszugeben – dadurch werden sowohl neue Block-Inhalte als auch bestehende Freitext-Biografien (inkl. Zeilenumbrüchen) korrekt als HTML dargestellt. |

---

### v3.3.60 — 05.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.60** | 🟢 feat | Admin / SEO Sitemap & Indexing | **Google als Ziel für die IndexNow-Massen-Meldung kürzlich veröffentlichter Inhalte, plus dauerhaft gespeichertes Google-Access-Token.** `CMS/core/Services/IndexingService.php` ergänzt `saveGoogleAccessToken()`/`clearGoogleAccessToken()`/`hasGoogleAccessToken()`, die das Token verschlüsselt (`SettingsService`, AES-256-CBC) in der Datenbank ablegen; `submitGoogle()`/`deleteGoogle()` nutzen dieses Token automatisch als Fallback, wenn kein Token manuell übergeben wird. `submitRecentContent()` akzeptiert jetzt eine Ziel-Liste (`indexnow`, `google`) statt nur IndexNow zu bedienen. `CMS/admin/modules/seo/SeoSuiteModule.php` ergänzt die Aktionen `save_google_access_token`, `clear_google_access_token` und erweitert `submitRecentContentIndexNow()` um Multi-Target-Unterstützung; `CMS/admin/seo-page.php` erlaubt die neuen Aktionen in der Sitemap-Section. `CMS/admin/views/seo/sitemap.php` zeigt einen Google-Token-Status-Badge, ein Formular zum dauerhaften Speichern/Entfernen des Tokens sowie Ziel-Checkboxen (IndexNow/Google) bei „Kürzlich veröffentlichte Inhalte melden“ — die manuelle Token-Eingabe in den bestehenden Google-Formularen ist jetzt optional. |

---

### v3.3.59 — 05.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.59** | 🟢 feat | Admin / SEO Sitemap & Indexing | **Neue IndexNow-Massen-Meldung für kürzlich veröffentlichte Inhalte.** `CMS/core/Services/IndexingService.php` ergänzt `getRecentContentUrls()`/`submitRecentContent()`, die alle veröffentlichten Seiten und Beiträge eines Zeitfensters (24h, 48h, 1 Woche, 1/3/6 Monate) ermitteln und in einem Schritt an IndexNow melden – vorausgesetzt, es ist bereits ein IndexNow-API-Key/eine Keydatei aktiv. `CMS/admin/modules/seo/SeoSuiteModule.php` stellt die neue Aktion `submit_recent_content_indexnow` bereit, `CMS/admin/seo-page.php` erlaubt sie in der Sitemap-Section, und `CMS/admin/views/seo/sitemap.php` zeigt dafür eine eigene Karte mit Zeitraum-Auswahl im Bereich „IndexNow & Google Submission“ – der Button ist deaktiviert, solange kein IndexNow-Key hinterlegt ist. Je Zeitfenster werden maximal 500 Seiten- bzw. Beitrags-URLs je Typ ermittelt, um Massen-Anfragen bei sehr großen Archiven abzufedern. |

---

### v3.3.58 — 05.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.58** | 🔴 fix | Core / EditorJS Renderer | **`CMS/core/Services/EditorJsRenderer.php`: Generierte Dateiname-Captions (z.B. `grafik.png`, `grafik-1.png` aus Word-/Outlook-Paste) landen nicht mehr im `alt`-Attribut von Bild- und Galerie-Blöcken.** Bisher wurde die dateinamenartige Caption bereits als sichtbare `<figcaption>` ausgeblendet, aber unverändert in `alt="..."` übernommen – dadurch enthielten Screenreader-Text und Bild-SEO weiterhin bedeutungslose Dateinamen. `renderImage()` und `renderImageGallery()` nutzen jetzt dieselbe `isGeneratedFilenameCaption()`-Prüfung auch für den Alt-Text und setzen in diesem Fall `alt=""` (dekoratives Bild), statt den Dateinamen auszugeben. Die Sitemap-Bild-Titel (`title`) nutzten bereits zuvor korrekt den Artikel-/Seitennamen und blieben unverändert. |

---

### v3.3.57 — 05.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.57** | 🔴 fix | Core / SEO Sitemap | **`CMS/core/Services/SEO/SeoSitemapService.php` sortiert Sitemap-XML-Dateien jetzt korrekt.** `pages.xml` listet veröffentlichte Seiten alphabetisch (A-Z) statt nach Änderungsdatum. `posts.xml`, `images.xml` und `news.xml` sortieren Beiträge/Bilder jetzt nach Veröffentlichungsdatum absteigend (`COALESCE(published_at, created_at) DESC`), sodass der neueste Inhalt immer oben steht. Der eigene RSS-Feed (`ThemeRouter::serveRssFeed()`) war bereits korrekt nach Veröffentlichungsdatum absteigend sortiert. |

---

### v3.3.51 — 03.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.51** | 🔴 fix | Admin / Kategorien- & Tag-Panel | **`CMS/assets/css/admin.css`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` geben dem Taxonomie-Slideover wieder einen eigenen Hintergrund.** `.taxonomy-slide-panel` referenzierte `var(--color-background-primary)` und `var(--color-border-secondary)` – diese Variablen sind im Admin nur lokal innerhalb von `.roles-permissions-matrix-wrap` definiert, wodurch das Panel transparent über dem Seiteninhalt lag und die Formularfelder unlesbar waren. Panel, Header und Footer nutzen jetzt Tabler-Variablen mit festen Fallbacks (`--tblr-bg-surface`, `--tblr-border-color`) und einen seitlichen Schatten, sodass der Anlege-/Bearbeiten-Bereich deckend und klar abgegrenzt dargestellt wird – auch im Dark Mode. |

---

### v3.3.50 — 03.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.50** | 🔴 fix | Admin / Kategorien- & Tag-Panel | **`CMS/assets/js/admin.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` reparieren das Öffnen des Taxonomie-Slideovers.** Die Buttons „Neue Kategorie anlegen“ und „Neues Tag anlegen“ liegen im Seitenkopf (`.page-header`), während `initTaxonomySlideovers()` den Öffnen-Button nur innerhalb des Panel-Roots (`.page-body[data-taxonomy-panel-root]`) suchte. Dadurch brach die komplette Slideover-Verdrahtung ab und der Klick blieb wirkungslos. Die Initialisierung fällt jetzt auf eine dokumentweite Suche nach `[data-taxonomy-open]` zurück, sodass Panel-Öffnen, Formular-Submit und Erfolgsmeldung wieder funktionieren. |

---

### v3.3.49 — 03.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.49** | 🟢 feat | Kategorien & Tags / Sprach-Slugs | **`CMS/admin/modules/posts/PostsModule.php`, `CMS/admin/post-categories.php`, `CMS/admin/post-tags.php`, `CMS/admin/views/posts/categories.php`, `CMS/admin/views/posts/tags.php`, `CMS/core/Routing/ThemeRouter.php`, `CMS/core/Routing/ThemeArchiveRepository.php`, `CMS/core/SchemaManager.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/core/DATABASE-SCHEMA.md` und `Changelog.md` ergänzen sprachabhängige Slugs für Beitrags-Kategorien und -Tags.** `post_categories` und `post_tags` erhalten eine neue Spalte `slug_en` (Migration läuft automatisch über SchemaManager-Runtime und PostsModule). Im Admin-Slideover ist je Kategorie/Tag neben dem Haupt-Slug ein optionales Feld „Slug (EN)“ pflegbar; die Eindeutigkeitsprüfung greift über beide Slug-Spalten und die Listen zeigen den EN-Slug an. Die Kategorie-/Tag-Archive reagieren je Sprachbereich auf den passenden Slug (`/kategorie/…` bzw. `/category/…`, `/tag/…`): Der Router matcht Haupt- und EN-Slug, liefert Themes den lokalisierten Slug (`slug`, `slug_de`, `slug_en`), Beitragslisten geben `category_slug` in EN-Locale via `COALESCE(slug_en, slug)` aus und die Archiv-Übersichten verlinken lokalisiert. Slug-Änderungen erzeugen sprachspezifische Archiv-Weiterleitungen: DE-Änderungen auf der DE-Basis, Änderungen des effektiven EN-Slugs auf der EN-Basis. |

---

### v3.3.48 — 03.07.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.48** | 🔴 fix | Admin / Beitrags-Kategorien & -Tags | **`CMS/assets/js/admin.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` reparieren das Anlegen neuer Beitrags-Kategorien und -Tags im Adminbereich.** Der Taxonomie-Slideover unter „Seiten & Beiträge“ wertete nach dem Speichern die zurückgelieferte Seite aus und traf dabei immer das dauerhaft vorhandene, per `d-none` versteckte Fehler-Element `taxonomy-form-error` im Panel-Markup. Dadurch wurde auch bei erfolgreichem Speichern fälschlich „Speichern fehlgeschlagen.“ angezeigt, das Panel blieb offen und die Liste wurde nie aktualisiert. Die Fallback-Fehlererkennung berücksichtigt jetzt nur noch sichtbare Danger-Alerts außerhalb des Panel-Fehlercontainers (`.alert-danger:not(.d-none):not([data-taxonomy-form-error])`), sodass Kategorien und Tags wieder mit Erfolgsmeldung angelegt, das Panel geschlossen und die Liste neu geladen werden. |

---

### v3.3.47 — 05.06.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.47** | 🔴 fix | EditorJS / Bild+Text-Ausrichtung | **`CMS/assets/js/editor-init.js`, `CMS/assets/css/admin.css`, `CMS/assets/css/editorjs-content.css`, `CMS/core/Bootstrap.php`, `CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Services/EditorJs/EditorJsContentNormalizer.php`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` synchronisieren die neue Vertikal-Ausrichtung für Text+Bild-Blöcke.** `mediaText` speichert jetzt `verticalAlignment` mit `top`, `center` oder `bottom`; Admin-Vorschau, Sanitizer, Normalizer, Public-Renderer, Critical-CSS und `editorjs-content.css` richten den Text entsprechend oben, mittig oder unten bündig zum Bild aus. WordPress-/Gutenberg-Klassen wie `is-vertically-aligned-center` bleiben beim Import erhalten. |

---

### v3.3.46 — 05.06.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.46** | 🔴 fix | HubSites / TOC Dark Mode | **`CMS/assets/css/hub-sites.css`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` synchronisieren den Dark-Mode-Fix für HubSite-Inhaltsverzeichnisse.** Das HubSite-TOC reagiert jetzt sowohl auf `body.dark-mode` als auch auf `html.dark-mode`, nutzt dunkle Container-/Item-Flächen, helle Titel und Labels sowie kontrastreiche Hover- und Leerzustände. |

---

### v3.3.45 — 05.06.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.45** | 🔴 fix | EditorJS / Public-Spacer | **`CMS/assets/css/editorjs-content.css`, `CMS/core/Bootstrap.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` synchronisieren die Public-Fallbacks für EditorJS-Abstandsblöcke.** `data-height="10"`, `data-height="100"` und `data-height="150"` werden jetzt sowohl im kleinen Critical-CSS als auch im nachgeladenen `editorjs-content.css` explizit auf `10px`, `100px` und `150px` gemappt. Dadurch bleiben die im Editor gewählten Spacer auch dann korrekt sichtbar, wenn Inline-Styles durch nachgelagerte Sanitizer-/Theme-Pfade reduziert wurden. |

### v3.3.44 — 31.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.44** | 🔴 fix | Admin-Sidebar / Plugin-Menüs | **`CMS/includes/functions/admin-menu.php`, `CMS/admin/partials/sidebar.php`, `CMS/assets/css/admin.css`, `CMS/assets/css/admin-tabler.css`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/admin/README.md`, `CMS/DOC/admin/PANEL-INTEGRATION.md` und `Changelog.md` dokumentieren und stabilisieren den Plugin-Menüfluss der letzten drei Tage.** Top-Level-Menüpositionen überschreiben sich bei gleicher Position nicht mehr, sondern werden auf die nächste freie Position verschoben. Die Sidebar stapelt auch lange aktive Plugin-Menülisten sauber vertikal, hält Plugin-Dropdowns im normalen Dokumentfluss, sortiert Plugin-Gruppen natürlich nach Label und bleibt über den mittleren Menübereich scrollbar, ohne Logo oder Footer zu überdecken. |
| **3.3.44** | 🔴 fix | Admin-Routing / Plugin-Layout | **`CMS/core/Routing/AdminRouter.php`, `CMS/admin/partials/header.php`, `CMS/includes/functions/admin-menu.php`, `CMS/assets/css/admin-tabler.css`, `CMS/DOC/admin/PANEL-INTEGRATION.md` und `Changelog.md` ziehen die Admin-Plugin-Routing-Fixes nach.** Plugin-Adminseiten erhalten automatisch den gemeinsamen `page-body`-/`container-xl`-Wrapper, wenn ihr Callback nur Seiteninhalt liefert; vollständige Layouts werden weiterhin erkannt und nicht doppelt eingebettet. Zusätzlich sind Knowledgebase- und ausgewählte M365-Adminseiten mit robusten Callback-Fallbacks abgesichert, damit verschachtelte `/admin/plugins/...`-Routen zuverlässig im Core-Layout öffnen. |
| **3.3.44** | 🟢 feat | Übersetzungen / Netzwerk-Detailseiten | **`CMS/core/Services/TranslationService.php`, `CMS/lang/de.yaml`, `CMS/lang/en.yaml`, `CMS/DOC/core/SERVICES.md` und `Changelog.md` dokumentieren die i18n-Nacharbeit.** Der TranslationService lädt den einfachen Fallback-Katalog jetzt unabhängig vom Symfony-Translator vorab und nutzt ihn auch dann, wenn Symfony einen unbekannten Key nur unverändert zurückgibt. Die Sprachressourcen enthalten neue DE-/EN-Keys für Speaker-, Experten-, Unternehmens- und Event-Detailseiten inklusive Breadcrumbs, ARIA-Labels, CTAs, Statuswerten, Verfügbarkeiten und Abschnittsüberschriften. |

### v3.3.43 — 31.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.43** | 🟡 refactor | Kategorien / Domain-Aliase | **`CMS/core/Router.php`, `CMS/admin/post-categories.php`, `CMS/admin/modules/posts/PostsModule.php`, `CMS/admin/modules/posts/PostsCategoryViewModelBuilder.php`, `CMS/admin/views/posts/categories.php`, `CMS/admin/modules/pages/PagesModule.php`, `CMS/core/SchemaManager.php`, `CMS/DOC/admin/pages-posts/README.md`, `CMS/DOC/admin/pages-posts/POSTS.md`, `CMS/DOC/core/DATABASE-SCHEMA.md`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` entfernen Kategorie-Zusatzdomains vollständig aus der Beitragskategorien-Verwaltung.** Kategorien können keine Fremd-Domains mehr hinterlegen, die Admin-Spalte und das Formular-Payload-Feld sind entfernt, neue Installationen legen keine Kategorie-Domain-Spalte mehr an, und der Router führt keine Root-Domain-Weiterleitung auf Kategoriearchive mehr aus. HubSite-Domains bleiben davon unberührt. |

### v3.3.42 — 31.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.42** | 🔴 fix | Assets / Tabler Icons | **`CMS/admin/partials/header.php`, `CMS/assets/tabler-icons/`, `CMS/DOC/assets/tabler/README.md`, `CMS/DOC/assets/README.md`, `CMS/DOC/ASSET.md`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` entfernen den Tabler-Icons-CDN-Request aus dem Admin-Header.** Die echten Icon-Webfont-Dateien werden jetzt ausschließlich lokal über `/assets/tabler-icons/tabler-icons.min.css` geladen, damit zur Laufzeit keine externen jsDelivr-/Tabler-Assets angefragt werden. |

### v3.3.41 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.41** | 🟠 perf/fix | HubSites / Bilder & CLS | **`CMS/core/Services/SiteTable/SiteTableHubRenderer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` ergänzen stabile Bilddimensionen für HubSite-Karten.** Normale Card-Bilder erhalten `width`-/`height`-Attribute passend zu `wide`, `square` oder `portrait`; Featured-Bilder erhalten Attribute aus der konfigurierten Featured-Bildbreite und `hub_feature_image_height`. Dadurch kann der Browser vor dem Laden der Bilddatei Platz reservieren, was CLS/PageSpeed und Bild-SEO verbessert, ohne bestehende HubSite-Inhalte oder URLs zu verändern. |

### v3.3.40 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.40** | ✨ feature | HubSites / Featured-Bildhöhe | **`CMS/admin/modules/hub/HubSitesModule.php`, `CMS/admin/modules/hub/HubTemplateProfileManager.php`, `CMS/admin/views/hub/edit.php`, `CMS/admin/views/hub/template-edit/main-column.php`, `CMS/core/Services/SiteTable/SiteTableTemplateRegistry.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` ergänzen eine anpassbare Featured-Bildhöhe.** Das neue Setting `hub_feature_image_height` ist im HubSite-Editor und Template-Customizer pflegbar, wird zwischen 160 px und 520 px normalisiert und als CSS-Variable `--hubsite-feature-image-height` an Themes ausgegeben. Zusammen mit `hub_feature_image_width` lassen sich Featured-Bildbereiche jetzt in Höhe und Breite steuern. |

### v3.3.39 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.39** | 🔴 fix | HubSites / Kachel-Schema & Card-Layout | **`CMS/core/Services/SiteTable/SiteTableHubRenderer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` sorgen dafür, dass Card-Layout-Werte aus dem Template-Customizer im Public Markup sichtbar werden.** HubSite-Karten erhalten nun zusätzlich `hubsite-card--layout-{standard|feature|compact}` und `cms-hub-site__card--layout-{standard|feature|compact}`, sodass Themes die im Template gewählte Layoutvariante zuverlässig stylen können. |

### v3.3.38 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.38** | ✨ feature/fix | HubSites / Template-Vererbung & Featured-Kacheln | **`CMS/admin/modules/hub/HubSitesModule.php`, `CMS/admin/modules/hub/HubTemplateProfileManager.php`, `CMS/admin/views/hub/edit.php`, `CMS/admin/views/hub/template-edit/main-column.php`, `CMS/core/Services/SiteTable/SiteTableTemplateRegistry.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` ergänzen die anpassbare Featured-Bildbreite und reparieren die Template-Vererbung.** Das neue Setting `hub_feature_image_width` ist als Prozentwert im HubSite-Editor sowie im Template-Customizer pflegbar, wird zwischen 20 % und 60 % normalisiert und als CSS-Variable `--hubsite-feature-image-width` an Themes ausgegeben. Geerbte Card-Designwerte bleiben auf Einzel-HubSites leer und bestehende, automatisch gespeicherte Template-Werte werden beim Template-Speichern bereinigt, damit Änderungen im Template-Customizer auf neuen und bestehenden Public HubSites sichtbar werden. |

### v3.3.37 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.37** | ✨ feature | HubSites / Card-Abstände | **`CMS/admin/modules/hub/HubSitesModule.php`, `CMS/admin/modules/hub/HubTemplateProfileManager.php`, `CMS/admin/views/hub/edit.php`, `CMS/admin/views/hub/template-edit/main-column.php`, `CMS/core/Services/SiteTable/SiteTableTemplateRegistry.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` ergänzen den anpassbaren HubSite-Card-Reihenabstand.** Das neue Setting `hub_card_row_gap` hat einen Mindestwert von 30 px, ist sowohl im einzelnen HubSite-Editor als auch im Template-Customizer pflegbar und wird als CSS-Variable `--hubsite-card-row-gap` an Themes ausgegeben. |

### v3.3.36 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.36** | ✨ feature | HubSites / Theme-Optionen | **`CMS/admin/modules/hub/HubSitesModule.php`, `CMS/admin/views/hub/edit.php`, `CMS/core/Services/SiteTable/SiteTableHubRenderer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` ergänzen eine optionale Autorenbox pro HubSite.** Die neue Einstellung `hub_show_author_box` wird in `settings_json` gespeichert, im HubSite-Admin als Checkbox angezeigt und über `hub_settings` an das Theme übergeben. Themes können damit unter einzelnen HubSites eine passende Autoren-/Dienstleistungsbox ausgeben, ohne globale HubSite-Templates zu verändern. |

### v3.3.35 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.35** | 🔴 fix | HubSites / Hero-Beschreibung | **`CMS/core/Services/SiteTable/SiteTableHubRenderer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` sichern die HubSite-Hero-Beschreibung ab.** Wenn weder ein expliziter Hero-Text noch eine Tabellenbeschreibung gesetzt ist, nutzt der Renderer jetzt zusätzlich die Template-Profil-Beschreibung. Dadurch erscheint etwa bei Datenschutz-HubSites die in den Einstellungen hinterlegte Beschreibung zuverlässig unter dem HubSite-Haupttitel. |

### v3.3.34 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.34** | 🟡 refactor | HubSites / Renderer-BEM | **`CMS/core/Services/SiteTable/SiteTableHubRenderer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` ergänzen eine stabile BEM-Styling-API für HubSites.** Der Renderer gibt zusätzlich zu den bestehenden `cms-hub-site*` Klassen neue Klassen wie `hubsite`, `hubsite-grid`, `hubsite-card`, `hubsite-card__header`, `hubsite-card__cta`, `hubsite-card--featured` und `hubsite-hero` aus. Dadurch können Themes alle HubSite-Varianten über eine gemeinsame, modifierfähige Struktur gestalten, ohne bestehende Integrationen zu brechen. |

### v3.3.33 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.33** | 🟢 feat | HubSites / Medienauswahl | **`CMS/admin/hub-sites.php`, `CMS/admin/views/hub/edit.php`, `CMS/assets/js/admin-hub-site-edit.js`, `CMS/assets/css/admin-hub-site-edit.css`, `CMS/core/Services/EditorJs/EditorJsUploadService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/admin/pages-posts/HUBSITES.md` und `Changelog.md` erweitern den HubSite-Karteneditor um direkte Medienarbeit.** Neben der manuellen Bild-URL besitzen HubSite-Kacheln jetzt Upload-, Mediathek- und Leeren-Aktionen mit sofortiger Vorschau. Uploads nutzen denselben abgesicherten `/api/media`-Flow wie EditorJS-Bilder, übergeben einen HubSite-Kontext und landen dadurch in `uploads/hub-sites/<slug>`, während Mediathek-Auswahlen vorhandene Bild-URLs direkt in das Karten-JSON übernehmen. |

### v3.3.32 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.32** | 🔴 fix | EditorJS / TOC-Semantik | **`CMS/core/Services/EditorJsRenderer.php`, `CMS/assets/js/editor-init.js`, `CMS/assets/css/editorjs-content.css`, `CMS/assets/css/admin.css`, `CMS/core/TableOfContents.php`, `CMS/admin/modules/toc/TocModule.php`, `CMS/assets/css/main.css`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` schärfen Text+Bild-Titel und Inhaltsverzeichnisse nach.** EditorJS-Text+Bild-Blöcke rendern optionale Titel jetzt semantisch als `h4` und nutzen im Admin sowie Public-Frontend den H4-Maßstab. Das globale TOC ist standardmäßig nicht mehr nummeriert; Header-TOCs und unnummerierte Core-TOCs nutzen ungeordnete Listen mit passenden Punkten, Pfeilen und dezenteren Markern je Einrückungsebene. |

### v3.3.31 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.31** | 🔴 fix | EditorJS / Audit LinkTool & Checklisten | **`CMS/assets/js/editor-init.js`, `CMS/core/Services/EditorJs/EditorJsMediaService.php`, `CMS/core/Services/EditorJs/EditorJsAssetService.php`, `CMS/core/Services/EditorJs/EditorJsContentNormalizer.php`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php`, `CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` schließen den aktuellen umfassenden EditorJS-Audit.** `fetch_link` übernimmt jetzt auch GET-Parameter des LinkTool-Plugins, Checklisten bleiben im Admin nach Reload und Save als `list` mit `style: checklist` inklusive Checked-State erhalten, Legacy-`checklist`-Payloads rendern auch mit moderner `content`/`meta.checked`-Form korrekt, und der generische EditorJS-Service gibt Holder-/Upload-/Token-Werte im Inline-Script als sichere JSON-Literale statt als manuell gequotete JavaScript-Strings aus. |

### v3.3.30 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.30** | 🟢 feat | Seiten / SEO-Tags | **`CMS/core/Services/SEO/SeoMetaRepository.php`, `CMS/core/Services/SEO/SeoHeadRenderer.php`, `CMS/core/Services/SEO/SeoAuditService.php`, `CMS/admin/pages.php`, `CMS/admin/views/pages/edit.php`, `CMS/admin/modules/seo/SeoSuiteModule.php`, `CMS/admin/modules/seo/SeoDashboardModule.php`, `CMS/admin/views/seo/audit.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/admin/pages-posts/PAGES.md`, `CMS/DOC/core/DATABASE-SCHEMA.md` und `Changelog.md` ergänzen unsichtbare SEO-Tags für normale Seiten.** Seiten besitzen in der SEO-Card jetzt ein Feld „SEO-Tags / Keywords“; die Werte werden kommagetrennt normalisiert in `seo_meta.keywords` gespeichert, migrationssicher nachgerüstet, im SEO-Audit ausgelesen und als `<meta name="keywords">` im Head ausgegeben, ohne im sichtbaren Seitencontent als Tagliste zu erscheinen. |

### v3.3.29 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.29** | 🔴 fix | EditorJS / Text+Bild-H3 | **`CMS/core/Services/EditorJsRenderer.php`, `CMS/assets/css/editorjs-content.css`, `CMS/assets/css/admin.css`, `CMS/assets/js/editor-init.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` synchronisieren optionale Titel von EditorJS-Text+Bild-Blöcken mit dem H3-Vertrag.** Der Public-Renderer gibt die Überschrift weiterhin semantisch als `<h3 class="editorjs-media-text__heading">` aus, nutzt jetzt aber H3-Typografie statt eines kleinen Sondertitels. Die Admin-Vorschau verwendet ebenfalls ein `h3`-Element, und die Core-CSS-Regeln überschreiben alte Inline-Reste robust auf den H3-Maßstab. |

### v3.3.28 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.28** | 🟢 feat | Seiten / Header-TOC | **`CMS/core/PageManager.php`, `CMS/core/SchemaManager.php`, `CMS/admin/pages.php`, `CMS/admin/modules/pages/PagesModule.php`, `CMS/admin/views/pages/edit.php`, `CMS/core/Router.php`, `CMS/core/TableOfContents.php`, `CMS/assets/css/main.css`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/admin/pages-posts/PAGES.md`, `CMS/DOC/admin/pages-posts/TOC.md`, `CMS/DOC/core/DATABASE-SCHEMA.md` und `Changelog.md` ergänzen ein seitenspezifisches Header-Inhaltsverzeichnis.** Seiten besitzen jetzt die Option „Eingeklapptes Inhaltsverzeichnis unter dem Titel anzeigen“. Der Core speichert sie in `show_title_toc`, rendert bei mindestens zwei Überschriften ein `<details>`-Widget direkt am Content-Anfang und überspringt dafür bewusst die globalen TOC-Auto-Insert-Einstellungen. Der sichtbare Summary-Titel bleibt schlank bei „Inhaltsverzeichnis“. Dadurch bleibt die Funktion themeübergreifend nutzbar und unabhängig von der normalen TOC-Konfiguration. |

### v3.3.27 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.27** | 🔴 fix | EditorJS / Audit Change-Sync & Cleanup | **`CMS/assets/js/editor-init.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` schließen den aktuellen EditorJS-Auditpunkt zu Runtime-Saves und Cleanup ab.** `createCmsEditor()` führt debounced `onChange`-Serialisierung jetzt in einer Queue aus, sodass keine überlappenden `editor.save()`-Promises ältere Hidden-JSON-Stände nach neueren Änderungen zurückschreiben können. Während eines laufenden Saves eingehende Änderungen werden als Pending-Run nachgezogen. Außerdem fängt die Factory asynchrone `destroy()`-Rejections defensiv ab, damit Pagehide-/Unmount-Cleanup keine unbehandelten Promise-Rejections erzeugt. Die interne Runtime-/Debug-Version ist auf `3.3.27` aktualisiert. |

### v3.3.26 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.26** | 🔴 fix | EditorJS / Text+Bild Abstände | **`CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Services/EditorJs/EditorJsContentNormalizer.php`, `CMS/assets/css/editorjs-content.css`, `CMS/core/Bootstrap.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` stabilisieren die Public-Abstände von `mediaText`-/Text+Bild-Blöcken.** Der Renderer ergänzt sichere Klassen wie `editorjs-media-text--spacing-top-30` und `editorjs-media-text--spacing-bottom-30`; Core-Critical-CSS und das nachgeladene EditorJS-CSS setzen MediaText-Blöcke nicht mehr pauschal über `.editorjs-block + .editorjs-block` oder `:first-child` auf `0px`; der Normalizer übernimmt Abstandswerte zusätzlich aus diesen Klassen, falls `style` oder `data-spacing-*` in Theme-/Sanitizer-Pfaden reduziert wurden. |

### v3.3.25 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.25** | 🛡️ security | EditorJS / Audit LinkTool & Normalizer | **`CMS/assets/js/editor-init.js`, `CMS/core/Services/EditorJs/EditorJsContentNormalizer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` schließen den aktuellen EditorJS-Audit-Fund.** `linkTool` übergibt den `X-CSRF-Token` jetzt auch an das lokale Metadaten-GET für `fetch_link`, wodurch Linkkarten nicht mehr am Media-Guard scheitern. Der Content-Normalizer erkennt zusätzlich bereits gerendertes `.editorjs-media-text`- und `.editorjs-gallery`-HTML, übernimmt Position, Breite, Bildskalierung, Überschrift, Rahmen und Abstände strukturerhaltend und verhindert so Double-Render-Zerlegung in Einzelblöcke. Die interne Runtime-/Debug-Version ist auf `3.3.25` aktualisiert. |

### v3.3.24 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.24** | 🔴 fix | EditorJS / Public Captions | **`CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` korrigieren den Public-Vertrag für EditorJS-Medien.** Bereits gespeicherte dateinamenartige Bild-, Galerie- und Carousel-Captions werden im Renderer unterdrückt, damit Grafik-/Dateinamen nicht mehr als sichtbare Bildunterschrift im Live/Public-Bereich erscheinen. |

### v3.3.23 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.23** | 🔴 fix | EditorJS / Lazy Submit-Save | **`CMS/assets/js/admin-content-editor.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` schließen den aktuellen EditorJS-Audit-Fund im Submit-Save-Pfad.** `saveEditorContent()` wartet jetzt zuerst auf ausstehende Lazy-Bindings, bevor es entscheidet, ob eine EditorJS-Instanz existiert oder der Hidden-JSON-Fallback verwendet wird. Dadurch werden schnell aktivierte, kopierte oder per AI-Übernahme vorbereitete DE/EN-Inhalte nicht mehr als veralteter Hidden-Input-Stand gespeichert. |

### v3.3.22 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.22** | 🔴 fix | EditorJS / Text+Bild Paste | **`CMS/assets/js/editor-init.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` korrigieren das Paste-Verhalten im `mediaText`-/Text+Bild-Block.** Formatierter HTML-Inhalt ohne Bild, zum Beispiel Listen, Absätze und erlaubte Inline-Formatierungen, wird jetzt direkt im bestehenden Textbereich eingefügt und löst nicht mehr den generischen EditorJS-Pfad aus, der neue Text-/Listenblöcke erzeugt. Kombinierte Bild+Text-Zwischenablagen nutzen weiterhin den bestehenden Upload-/URL-Pfad und behalten den Text im selben Block. |

### v3.3.21 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.21** | 🛡️ security | EditorJS / Audit-Callbacks | **`CMS/core/Services/EditorJs/EditorJsAssetService.php`, `CMS/assets/js/admin-content-editor.js`, `CMS/assets/js/editor-init.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` schließen den aktuellen EditorJS-Auditpunkt zur Core-Initialisierung ab.** Der generische EditorJS-Service und das Page/Post-Binding übergeben jetzt explizite `onReady`-Callbacks an `createCmsEditor()`, sodass `holder`/`data`/`tools` zusammen mit `onReady`, `onChange` und `onError` in allen Einstiegspunkten konsistent verdrahtet sind. Tool-/Upload-/Persistenz-/Cleanup- und Public-Renderer-Prüfung ergab keine weiteren Code-Fixes. |

### v3.3.20 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.20** | 🟢 feat | EditorJS / Bildskalierung | **`CMS/assets/js/editor-init.js`, `CMS/assets/css/admin.css`, `CMS/assets/css/editorjs-content.css`, `CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php`, `CMS/core/Services/EditorJs/EditorJsContentNormalizer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` ergänzen kontrollierte Bildskalierungsoptionen.** Normale Bildblöcke bieten nun „Skalierung“ und „Max. Höhe“ mit sicheren Presets; Text+Bild-Blöcke können die Bildskalierung zwischen `Ausschnitt`, `Anpassen`, `Strecken`, `Verkleinern` und `Original` wählen. Client-Normalizer, Server-Sanitizer, Content-Normalizer, Public-Renderer und Admin-/Public-CSS speichern und rendern die neuen Werte konsistent. |

### v3.3.19 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.19** | 🔴 fix | EditorJS / Text+Bild Clipboard-Audit | **`CMS/assets/js/editor-init.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` schließen den erneuten EditorJS-Audit nach Core-Setup, Tool-Vollständigkeit, Uploads, Persistenz, Cleanup, Public-Rendering und UX ab.** Als realer Restfund wurde der `mediaText`-Clipboard-Pfad für HTML-Zwischenablagen mit eingebetteten `data:image/...;base64,...`-Quellen gehärtet: solche Bilder werden jetzt in sichere `File`-Objekte konvertiert und über den bestehenden Upload-Vertrag `{ success: 1, file: { url } }` verarbeitet, statt als nicht zulässige Base64-URL verloren zu gehen. |

### v3.3.18 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.18** | 🟢 feat | EditorJS / Text+Bild Clipboard | **`CMS/assets/js/editor-init.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` ergänzen schlankes Clipboard-Handling für Text+Bild-Blöcke.** Fügt man im Textbereich eines `mediaText`-Blocks kombinierten Inhalt aus Bild und formatiertem Text ein, wird das Bild im selben Block gesetzt bzw. hochgeladen und der Text ohne Bild in den Contentbereich übernommen. Erlaubte Inline-/Blockformatierungen wie Links, Fett/Kursiv, Listen und Zitate bleiben durch die bestehende Sanitizer-Schicht erhalten. |

### v3.3.17 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.17** | 🛡️ security | EditorJS / Audit-Abschluss | **`CMS/assets/js/editor-init.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` schließen den umfassenden EditorJS-Audit ab.** Core-Setup, Tool-/Asset-Registry, Upload-Response-Verträge, Hidden-JSON-Persistenz, Destroy-/Cleanup-Pfade, Readonly-/Public-Rendering und Admin-A11y wurden erneut abgeglichen; als realer Restfund meldete die interne Runtime-/Debug-Version noch den alten Audit-Stand `3.3.15`. Die Factory-Version ist nun auf den aktuellen Core-Stand gehoben, damit Browserdiagnosen und Cache-/Support-Ausgaben konsistent bleiben. |

### v3.3.16 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.16** | 🔴 fix | EditorJS / Text+Bild Public-Abstände | **`CMS/core/Services/EditorJsRenderer.php`, `CMS/assets/css/editorjs-content.css`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` sorgen dafür, dass Text+Bild-Abstände oben und unten auf Publicseiten live greifen.** Ursache war, dass der Renderer zwar `margin` schrieb, die globale Public-Regel für `.editorjs-block` aber `margin-block-start/end` per `!important` aus `--cms-editorjs-space-before/after` verwendet. `mediaText` setzt diese Core-Abstandsvariablen jetzt direkt auf `spacingTop` und `spacingBottom`, sodass Theme-/Public-Margin-Regeln die eingestellten Werte übernehmen. |

### v3.3.15 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.15** | 🔴 fix | EditorJS / Audit-Fixes | **`CMS/assets/js/editor-init.js`, `CMS/core/Services/EditorJs/EditorJsAssetService.php`, `CMS/assets/editorjs/image-gallery.umd.js`, `CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php`, `CMS/core/Services/EditorJs/EditorJsContentNormalizer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` schließen den erneuten EditorJS-Audit.** Das alte externe `ImageGallery`-UMD-Bundle wurde entfernt, weil registriert und genutzt ausschließlich das lokale `CmsImageGalleryTool` ist. Der Datei-/Attaches-Uploader besitzt jetzt zusätzlich `uploadByUrl()` mit korrektem `{ success: 1, file: { url } }`-Shape. MediaText-Überschriften werden in Sanitizer, Normalizer und Renderer über einen robusten `mb_substr`-Fallback gekürzt, damit Installationen ohne `mbstring` nicht fatal abbrechen. |

### v3.3.14 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.14** | 🟢 add | EditorJS / Text+Bild | **`CMS/assets/js/editor-init.js`, `CMS/assets/css/admin.css`, `CMS/assets/css/editorjs-content.css`, `CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php`, `CMS/core/Services/EditorJs/EditorJsContentNormalizer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` erweitern Text+Bild-Blöcke um optionale Überschriften und getrennte Blockabstände.** Die neue Überschrift wird als `heading` gespeichert und bei aktivem dezenten Rahmen als Band am oberen Rahmen angedockt gerendert. `spacingTop` und `spacingBottom` erlauben getrennte Abstände zum Block darüber und darunter; Standard bleibt jeweils 10px. |

### v3.3.13 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.13** | 🟢 add | EditorJS / Text+Bild | **`CMS/assets/js/editor-init.js`, `CMS/assets/css/admin.css`, `CMS/assets/css/editorjs-content.css`, `CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php`, `CMS/core/Services/EditorJs/EditorJsContentNormalizer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` ergänzen für Text+Bild-Blöcke eine optionale dezente Rahmenanzeige.** Die neue Option wird als `showBorder` gespeichert, im Admin über die Eigenschaften-Leiste bedient und im Public-Renderer mit 1px Kontur, maximal 2px Rundung sowie standardmäßig 10px Abstand nach oben und unten ausgegeben. |

### v3.3.12 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.12** | 🛡️ security | EditorJS / Runtime-Audit | **`CMS/assets/js/editor-init.js`, `CMS/core/Services/EditorJs/EditorJsAssetService.php`, `CMS/core/Services/AI/AiSettingsService.php`, `CMS/admin/modules/system/AiServicesModule.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` schließen den umfassenden EditorJS-Audit nach Core-Setup, Tool-Registry, Uploads, Persistenz, Cleanup, Public-Rendering und UX.** `createCmsEditor()` reicht `onReady` jetzt ebenso konsistent durch wie `onChange` und `onError`; der generische Standalone-Editor synchronisiert sein Hidden-JSON bereits bei Editor-Änderungen und zerstört die Instanz defensiv bei `pagehide`. AI-/Admin-Blocktyp-Fallbacks liefern keine Alias-Duplikate mehr. Der bestehende Upload-Vertrag bleibt bei `{ success: 1, file: { url } }`, nutzt Same-Origin-Requests mit CSRF und klaren Fehlerpfaden; Public-Renderer und Sanitizer decken alle aktiven Blocktools ab. |

### v3.3.11 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.11** | 🟢 feat | EditorJS / Tool-Audit & Spacer | **`CMS/assets/js/editor-init.js`, `CMS/assets/js/admin-content-editor.js`, `CMS/assets/css/admin.css`, `CMS/core/Services/EditorJs/EditorJsAssetService.php`, `CMS/core/Services/EditorJs/EditorJsContentNormalizer.php`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php`, `CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Services/AI/AiSettingsService.php`, `CMS/core/Services/AI/EditorJsTranslationPipeline.php`, `CMS/admin/modules/system/AiServicesModule.php`, `CMS/admin/views/system/ai-services.php`, `CMS/assets/editorjs/`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md`, `CMS/DOC/FILELIST.md`, `CMS/DOC/core/SECURITY.md` und `Changelog.md` schließen den EditorJS-Tool-Abgleich.** Spacer-Blöcke bieten im Admin nun direkt `10px`, `150px` und `200px` und werden clientseitig, im CMS-PHP-Normalizer, serverseitig und im Public-Renderer als kontrollierte Höhen erhalten. Aktive Blocktools sind in der Page/Post-GUI, im generischen EditorJS-Service und in den AI-/Übersetzungs-Blocklisten sichtbar; `alert` ist als eigener Block verfügbar, Textfarbe und Spoiler sind über die Formatierungsbubble erreichbar. Blockrahmen im Admin erscheinen nur noch beim Hover und bleiben sonst public-nah dezent. Nicht registrierte EditorJS-Asset-Bundles (`checklist`, `carousel`, `columns`, alter ESM-Core sowie Cropper-/Drawing-Dateien) wurden aus `CMS/assets/editorjs/` bereinigt, während Legacy-Inhalte im Public-Renderer kompatibel bleiben. |

### v3.3.10 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.10** | 🟠 perf | EditorJS / große Inhalte & Overlays | **`CMS/assets/js/editor-init.js`, `CMS/assets/js/admin-content-editor.js`, `CMS/assets/css/admin.css`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` stabilisieren große EditorJS-Seiten und -Beiträge im Admin.** Bildvorschauen, Galerietumbnails und Mediathek-Kacheln werden lazy/async geladen und nicht unnötig neu mit derselben URL bestückt; Offscreen-Blöcke nutzen bei großen Dokumenten browserseitige Render-Drosselung. Bei sehr vielen Blöcken erzeugt die Admin-UI keine massenhaften Inline-Einfügebuttons und alten Bild-Hover-Overlays mehr, wodurch Öffnen und Re-Rendern großer Inhalte deutlich ruhiger bleiben. Native EditorJS-Zahnrad-/Popover-Menüs erhalten eine eigene hohe Stacking-Ebene und werden nicht mehr hinter nachfolgenden Blöcken versteckt. |

### v3.3.9 — 25.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.9** | 🔴 fix | EditorJS / Medienblöcke & Galerie | **`CMS/assets/js/editor-init.js`, `CMS/assets/css/admin.css`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` stabilisieren die EditorJS-Medienblöcke.** Galerie-Daten werden beim Admin-Reload nach URL dedupliziert, sodass parallel gespeicherte `images`-/`urls`-Legacy-Felder nicht mehr bei jedem Speichern mehrfach im Editor auftauchen. Bild-, Galerie- und Bild+Text-Eigenschaften erscheinen jetzt als dezente linke Properties-Leiste statt als störendes Overlay über Vorschau oder Texteingabe. Normale Bildblöcke unterstützen zusätzlich zum Upload nun auch die vorhandene Mediathek-Auswahl. |

### v3.3.8 — 24.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.8** | 🛡️ security | EditorJS / Audit & UX-Härtung | **`CMS/assets/js/editor-init.js`, `CMS/assets/js/admin-content-editor.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` schließen die neue EditorJS-Audit-Runde.** Upload-, Mediathek- und Attachment-Requests prüfen jetzt HTTP-Status und Nicht-JSON-Antworten, bevor Payloads normalisiert werden. Das Admin-Mutation-Tracking registriert entfernbare Listener und räumt sie beim Editor-Recreate auf. Die eigene Textformat-Bubble erzeugt sichere `http(s)`-Links mit `target="_blank" rel="noopener noreferrer"`, lehnt gefährliche Linkwerte ab und bietet Strikethrough passend zum registrierten Inline-Tool direkt an. |

### v3.3.7 — 24.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.7** | 🟢 feat | EditorJS / Inline-Tools & Delimiter | **`ASSETS/package.json`, `CMS/assets/editorjs/delimiter.umd.js`, `CMS/assets/editorjs/strikethrough.umd.js`, `CMS/assets/editorjs/hyperlink.umd.js`, `CMS/assets/js/editor-init.js`, `CMS/core/Services/EditorJs/EditorJsAssetService.php`, `CMS/core/Services/EditorJs/EditorJsContentNormalizer.php`, `CMS/core/Services/EditorJs/EditorJsHtmlSanitizer.php`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php`, `CMS/core/Services/EditorJsRenderer.php`, `CMS/assets/css/editorjs-content.css`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` ergänzen die kleinen EditorJS-Upgrades.** Der Delimiter nutzt jetzt die CoolBytes-Variante mit `line`, `dash` und `star`, Strikethrough speichert sichere `<s class="cdx-strikethrough">`-Inline-Markups, Hyperlinks können `target`/`rel` kontrolliert setzen, und Client-/Server-Sanitizer normalisieren Links weiterhin gegen `javascript:`- und unsichere `rel`-/`target`-Werte. |

### v3.3.6 — 24.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.6** | 🛡️ security | EditorJS / Audit & Stabilität | **`CMS/assets/js/editor-init.js`, `CMS/assets/js/admin-content-editor.js`, `CMS/core/Services/EditorJs/EditorJsAssetService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` schließen die EditorJS-Audit-Funde.** Der Editor registriert ein lokales Marker-Inline-Tool, reicht ReadOnly- und Fehler-Callbacks bis zur Factory durch, versieht Holder und Toolbars mit ARIA-Status, bricht Standalone-Formular-Submits bei `editor.save()`-Fehlern sicher ab, normalisiert Save-Ausgaben vor der Persistenz und räumt eigene Admin-UI-Listener, Selection-Bubbles sowie MutationObserver beim Recreate sauber auf. |

### v3.3.5 — 24.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.5** | 🔴 fix | EditorJS / Public-Bildlayout | **`CMS/core/Services/EditorJsRenderer.php`, `CMS/assets/css/editorjs-content.css`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` gleichen EditorJS-Bilder und Bild-Text-Blöcke im Public-Frontend an die Admin-Vorschau an.** Normale und breite Bildblöcke werden nicht mehr auf große Public-Container aufgeblasen, sondern nutzen dieselbe 760px-Deckelung wie der Editor; `mediaText` rendert mit stabilen Flex-Spalten, `min-width: 0` und klaren Breitenvariablen, sodass Theme-Regeln für globale `figure`-/`img`-Elemente Bild und Text nicht mehr auseinanderziehen. |

### v3.3.4 — 24.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.4** | 🔴 fix | EditorJS / Galerie-Runtime | **`CMS/assets/js/editor-init.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` beheben den verbliebenen Admin-Stub bei `imageGallery`-Blöcken.** Das lokale `CmsImageGalleryTool` besitzt nun die fehlende DOM-Cleanup-Hilfsfunktion, akzeptiert auch stringbasierte Legacy-URL-Listen und nutzt eine neue interne EditorJS-Datenversion, damit Browser nicht weiter den alten fehlerhaften Initialisierer aus dem Cache verwenden. |

### v3.3.3 — 24.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.3** | 🔴 fix | EditorJS / WordPress-Galerien | **`CMS/core/Services/EditorJs/EditorJsContentNormalizer.php`, `CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php`, `CMS/assets/js/editor-init.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` stabilisieren alte WordPress-Seiten mit Galerien.** `wp:gallery` und `.wp-block-gallery` werden als nativer `imageGallery`-Block mit allen Bildern und Captions normalisiert, Legacy-Datenformen mit `urls`, `images` als Strings oder `src`-Objekten bleiben im Public-Renderer und Sanitizer erhalten, und der Admin-Editor nutzt konsequent das lokale robuste Gallery-Tool statt auf den inkompatiblen externen `ImageGallery`-Datenvertrag zurückzufallen. Dadurch erscheinen alte WP-Seiten nicht mehr nur mit Content-Header und der Editor zeigt Galerien wieder statt des Stub-Fehlers „The block can not be displayed correctly“. |

### v3.3.2 — 24.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.2** | 🔴 fix | EditorJS / Audit-Härtung | **`CMS/core/Services/EditorJs/EditorJsContentNormalizer.php`, `CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php`, `CMS/core/Services/EditorService.php`, `CMS/assets/js/editor-init.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` schließen die Audit-Lücken aus dem EditorJS-/Gutenberg-Follow-up.** Gültige EditorJS-Listen-, Bild-, Text- und Zitat-Metadaten bleiben beim Normalisieren erhalten, selbstschließende Gutenberg-Blöcke wie `wp:image` werden in der richtigen Reihenfolge konvertiert, Media-Text-Blöcke behalten sichere Absätze und Listen, der Admin-Importer erkennt `.wp-block-media-text`, und der letzte Legacy-Fallback gibt keine unsanitisierten Fehler-/Import-Fragmente mehr aus. |

### v3.3.1 — 24.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.1** | 🔴 fix | EditorJS / WordPress-Blöcke | **`CMS/core/Services/EditorJs/EditorJsContentNormalizer.php`, `CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php`, `CMS/core/Services/EditorService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` machen Public-Rendering und Speichern von EditorJS-Inhalten fehlertoleranter.** Einzelne defekte Blöcke brechen nicht mehr den kompletten Artikel ab, importiertes WordPress-/Gutenberg-HTML wird in 365CMS-EditorJS-Blöcke normalisiert, und `wp:media-text` beziehungsweise `.wp-block-media-text` wird als nativer Bild-Text-Block mit Bild links/rechts und Breitenmapping gerendert. |

### v3.3.0 — 24.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.3.0** | 🟢 feat | EditorJS / Medienblöcke | **`CMS/assets/js/editor-init.js`, `CMS/assets/js/admin-content-editor.js`, `CMS/assets/css/admin.css`, `CMS/assets/css/editorjs-content.css`, `CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php`, `CMS/core/Services/EditorJs/EditorJsAssetService.php`, `CMS/core/Bootstrap.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` ergänzen einen nativen EditorJS-Block „Bild + Text“.** Redakteure können ein Bild links oder rechts neben formatiertem Text platzieren, die Bildbreite wählen, Alt-Text pflegen und den Block responsive oberhalb/innerhalb normaler Inhalte rendern lassen. |

### v3.2.0 — 24.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.2.0** | 🟢 feat | Beiträge / Templates | **`CMS/admin/modules/posts/PostsModule.php`, `CMS/admin/views/posts/edit.php`, `CMS/assets/js/admin-content-editor.js`, `CMS/core/SchemaManager.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` ergänzen Theme-gesteuerte Beitrags-Templates im Editor.** Beiträge speichern `post_template` und `post_meta_json`; der Editor liest `post_templates` aus dem aktiven Theme, zeigt templateabhängige Zusatzfelder an und persistiert nur befüllte Felder typgerecht. |

### v3.1.2 — 24.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.1.2** | 🟢 feat | Hub-Sites / Template-Design | **`CMS/admin/views/hub/template-edit/main-column.php`, `CMS/assets/js/admin-hub-template-editor.js`, `CMS/assets/css/admin-hub-template-editor.css`, `CMS/admin/views/hub/edit.php`, `CMS/admin/modules/hub/HubSitesModule.php`, `CMS/core/Services/SiteTable/SiteTableTemplateRegistry.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `Changelog.md` und `CMS/DOC/admin/pages-posts/HUBSITES.md` machen die Card-Rundung für HubSite-Templates vollständig steuerbar.** Der Template-Editor erhält eine deutlich sichtbare Zahl-/Slider-Steuerung mit Live-Preview, Basis-Template-Wechsel übernehmen den jeweiligen Radius-Default korrekt, und einzelne HubSites können den Template-Radius optional mit `hub_card_radius` überschreiben. |

### v3.1.1 — 24.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.1.1** | 🔴 fix | Hub-Sites / Dienstleistungen | **`CMS/admin/views/hub/edit.php`, `CMS/assets/js/admin-hub-site-edit.js`, `CMS/assets/js/admin-hub-template-editor.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `Changelog.md` und `CMS/DOC/admin/pages-posts/HUBSITES.md` machen das Dienstleistungs-Profil im HubSite-Admin vollständig nutzbar.** `services` erscheint sichtbar in den Template-Varianten, services-basierte Templates unterstützen Feature-Kacheln in Vollbreite, und die Template-Vorschau nutzt passende Service-/Delivery-Texte statt auf General-IT zurückzufallen. |

### v3.1.0 — 23.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.1.0** | 🟢 feat | Hub-Sites / Services | **`CMS/admin/modules/hub/HubTemplateProfileCatalog.php`, `CMS/core/Services/SiteTable/SiteTableTemplateRegistry.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `Changelog.md` und `CMS/DOC/admin/pages-posts/HUBSITES.md` ergänzen ein neues HubSite-Template-Profil `services`.** Das Profil ist für Dienstleistungs-/Landing-Hubs ausgelegt und bringt passende Meta-Labels, Quicklinks, drei Einstiegsektionen, PHINIT-nahe Farbwerte sowie Starter-Kacheln für Beratung, Microsoft 365 und 365CMS/Web-Plattformen mit. |

### v3.0.28 — 23.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.28** | 🔴 fix | Site Tables / Pagination | **`CMS/core/Services/SiteTable/SiteTableTableRenderer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` korrigieren die Frontend-Pagination auf echte 20-Zeilen-Seiten.** Der Renderer hebt konfigurierte kleinere Seitengrößen wie `15` auf mindestens `20` an und aktiviert Pagination erst bei mehr als 20 Zeilen; exakt 20 Zeilen bleiben damit ohne Pagination. |

### v3.0.27 — 23.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.27** | 🔴 fix | Site Tables / Toolbar | **`CMS/core/Services/SiteTable/SiteTableTableRenderer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` entfernen die reine Pagination-Metazeile oberhalb der Tabelle.** Der Toolbar-Block wird nur noch gerendert, wenn Suche aktiv ist; bei reiner Pagination erscheint keine Zeilen-/Seitenstatusinfo mehr unterhalb des Tabellentitels, während die eigentliche Pagination weiterhin unter der Tabelle gerendert wird. |

### v3.0.26 — 23.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.26** | 🔴 fix | Site Tables / Interaktion | **`CMS/core/Services/SiteTable/SiteTableTableRenderer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` begrenzen die Frontend-Pagination auf wirklich lange Tabellen.** Der Renderer aktiviert Pagination nur noch, wenn die Tabelle mindestens 20 Zeilen besitzt und mehr Zeilen als die konfigurierte Seitengröße enthält; zusätzlich steht mit `site_table_interactive_config` ein Filter bereit, damit Themes die Such-/Sortier-/Pagination-Konfiguration ohne Markup-Hacks anpassen können. |

### v3.0.25 — 23.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.25** | 🔴 fix | Site Tables / Public-Sanitizer | **`CMS/core/Services/PurifierService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` erhalten zentral aktivierte Tabellen-Captions im Public-Renderpfad.** Die Default- und Hub-Profile erlauben nun das sichere `<caption>`-Element samt `class`-Attribut und nutzen eine neue HTML-Definition-Revision, damit Site-Table-Überschriften oberhalb der Tabelle nicht mehr durch nachgelagerte Theme-Sanitizer-Läufe entfernt werden. |

### v3.0.24 — 22.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.24** | 🔴 fix | Medien / Featured Images | **`CMS/core/Services/EditorJs/EditorJsImageLibraryService.php`, `CMS/core/Services/EditorJs/EditorJsUploadService.php`, `CMS/core/Services/Media/UploadHandler.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `CMS/DOC/admin/media/MEDIA.md` und `Changelog.md` schließen den Editor-Picker-Pfad für 403-Fehler nach Beitrags-/Seitenbildwechseln.** Die Featured-Image-Bibliothek und neue EditorJS-Uploads geben öffentliche Medien jetzt als hostneutrale direkte `/uploads/...`-URLs zurück, wodurch gespeicherte Formularwerte keine unnötigen `/media-file?...`-Delivery-Queries mehr durch den Update-POST tragen. Zusätzlich setzt der Medien-Move beim Verschieben temporärer Uploads in den finalen Slug-Ordner die Ziel-Datei explizit auf den passenden öffentlichen bzw. privaten Dateimodus. |

### v3.0.23 — 22.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.23** | 🔴 fix | Medien / Featured Images | **`CMS/core/Services/MediaService.php`, `CMS/core/Services/Media/UploadHandler.php`, `CMS/core/Services/MediaDeliveryService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json` und `Changelog.md` beheben browserlokale 403-Fehler direkt nach dem Aktualisieren von Beitrags- oder Seitenbildern.** Öffentliche Uploads unter `articles/`, `pages/`, `editorjs/`, Theme-/Medienordnern und deren WebP-/Thumbnail-Derivate werden nun nach Upload, Replace-in-place und Derivat-Jobs explizit mit webserverlesbaren Rechten gespeichert; bestehende öffentliche Direktdateien werden beim Erzeugen der `/uploads/...`-URL defensiv nachgezogen. Private Member-Uploads und versteckte Runtime-Pfade behalten die restriktiven Rechte und laufen weiterhin über den geschützten Delivery-Pfad. |

### v3.0.22 — 22.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.22** | 🛡️ security | Sessions / Uploads / Pfadauflösung | **`CMS/core/Security.php`, `CMS/core/Auth.php`, `CMS/install.php`, `CMS/install/InstallerController.php`, `CMS/core/Services/FileUploadService.php`, `CMS/core/Services/MediaService.php`, `CMS/core/Services/Media/UploadHandler.php` und `CMS/.htaccess` härten Session- und Upload-Grenzen nach.** Session-Cookies verwenden nun Strict-SameSite-Fallbacks, Geräte-/Session-Cookies werden konsistent mit Strict erneuert, Web-Uploads akzeptieren nur echte `is_uploaded_file()`-Quellen, Upload-Moves erlauben `rename`/`copy` nur noch in CLI-Kontexten, Pfade werden kanonisch gegen das Upload-Basisverzeichnis geprüft und ausführbare Upload-Endungen wie `phar`, `shtml`, Shell-/CGI-/ASP-/JSP-/War-Dateien werden zusätzlich blockiert. |
| **3.0.22** | 🟠 perf | DB / Assets | **`CMS/core/Auth.php`, `CMS/core/Services/UserService.php`, `CMS/core/Services/MemberService.php`, `CMS/core/SchemaManager.php`, `CMS/core/MigrationManager.php`, `CMS/core/Services/EditorJs/EditorJsAssetService.php`, `CMS/core/Services/SiteTable/SiteTableTableRenderer.php` und `CMS/core/Services/EditorService.php` reduzieren unnötige Last im Request-Pfad.** Auth-, User- und Notification-Hotpaths laden keine kompletten Datensätze mehr per `SELECT *`, Passwort-Hashes werden nach erfolgreicher Prüfung nicht im aktuellen User-Objekt gehalten, das Notification-Center erhält den Composite-Index `idx_user_created (user_id, created_at)`, und EditorJS-/SiteTable-/SunEditor-Skripte werden mit `defer` ausgegeben. |
| **3.0.22** | 🟡 refactor | Code-Qualität / Accessibility | **`CMS/includes/functions/redirects-auth.php`, `CMS/assets/js/member-dashboard.js`, `CMS/core/Services/SEO/SeoAnalyticsRenderer.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` schließen Best-Practice- und A11y-Funde.** Der Debug-Helfer nutzt kein `var_dump()` mehr, kleine First-Party-JS-Pfade verwenden blockscoped `const`, Meta-Pixel-Noscript-Bilder erhalten ein dekoratives `alt=""`, das versteckte GTM-Iframe bekommt einen Titel, und der Audit-Batch ist als Release `3.0.22` dokumentiert. |

### v3.0.21 — 22.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.21** | 🟠 perf | EditorJS / Frontend-CSS | **`CMS/core/Bootstrap.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `CMS/DOC/assets/editorjs/README.md`, `README.md` und `Changelog.md` entfernen das globale `editorjs-content.css` aus dem render-blockierenden Head-Pfad.** Der Frontend-Bootstrap gibt nun nur noch einen kleinen Inline-Basisstil für EditorJS-Blöcke, Bilder, Tabellen und Spacer aus und lädt die vollständige Public-CSS anschließend per `preload`/`onload` mit `noscript`-Fallback nach. Dadurch bleibt das sichtbare Content-Rendering stabil, während Lighthouse die bisherige Render-Blocking-Anfrage auf `assets/css/editorjs-content.css` nicht mehr als LCP-/FCP-Bremse im kritischen Pfad bewertet. |

### v3.0.20 — 21.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.20** | 🔴 fix | Theme-Assets / Apache Rewrite | **`CMS/.htaccess`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `CMS/DOC/INSTALLATION.md`, `README.md` und `Changelog.md` beheben die Asset-Regression nach der .htaccess-Härtung.** Die private Cache-Sperre bleibt bestehen, aber die vom `AssetOptimizerService` erzeugten öffentlichen Dateien `cache/optimized-assets/<hash>.min.css` und `cache/optimized-assets/<hash>.min.js` werden wieder eng begrenzt ausgeliefert. Zusätzlich bleiben öffentliche Upload-Direkt-URLs erreichbar: ausführbare und versteckte Uploads werden weiterhin blockiert, während normale Medien/Downloads und die vom FontManager erzeugten `uploads/fonts/*.css`, `*.woff`, `*.woff2`, `*.ttf` und `*.otf` über korrekte MIME-Typen sowie Font-spezifische CORS/CORP-Header laden. Dadurch funktionieren Theme-Styles, Theme-Skripte und lokale Schriften bei aktivierter CSS-/JS-Minifizierung wieder korrekt, ohne private Cache- oder Runtime-Bereiche zu öffnen. |

### v3.0.19 — 21.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.19** | 🛡️ security | Cron/MailQueue/Plugin-Trigger | **`CMS/cron.php`, `CMS/config.php`, `CMS/install/InstallerService.php`, `CMS/core/Services/CronRunnerService.php`, `CMS/core/Services/MailQueueService.php`, `CMS/admin/views/system/mail-settings.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `CMS/DOC/admin/diagnose/DIAGNOSE.md`, `CMS/DOC/core/SERVICES.md`, `CMS/DOC/assets/cron/README.md`, `README.md` und `Changelog.md` härten den Cron- und Mail-Worker-Vertrag nach.** Der Web-Cron sendet nun No-Store-/No-Referrer-/Noindex-Header, loggt Entry-Point-Ausnahmen intern, nutzt den vorhandenen Proxy-aware HTTPS-Check für Query-Token-Warnungen und dokumentiert den Header-Token `X-CMS-Cron-Token` als bevorzugten Weg. Deaktivierte Mail-Queues erzeugen keinen Cron-Fehler mehr, sondern einen sauberen Skip, Cron-Aufrufe mit fehlender/platzhalterhafter Konfiguration liefern keinen Installer-HTML-Body mehr, Feed-Recovery-Fehler werden öffentlich generisch zurückgegeben, und die Mailpfade für Kommentare, `cms_mail()` und `cms-contact` bleiben über `cms_cron_mail_queue` zuverlässig verarbeitbar. |

### v3.0.18 — 21.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.18** | 🛡️ security | Apache/Installer/Bestellrouting | **`CMS/.htaccess`, `CMS/install.php`, `CMS/install/InstallerController.php`, `CMS/install/InstallerService.php`, `CMS/core/Routing/PublicRouter.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `CMS/DOC/INSTALLATION.md`, `README.md` und `Changelog.md` ziehen den Webserver- und Installationsvertrag nach.** Die `.htaccess` verwendet keine in `.htaccess` unzulässigen `<Directory>`-Blöcke mehr, schützt private Runtime-Verzeichnisse und ausführbare Uploads per Rewrite-Regeln, kapselt `php_value`-Limits auf `mod_php` und vermeidet dadurch PHP-FPM/CGI-500er. Der Installer erkennt HTTPS hinter Reverse Proxies konsistent, schreibt beim Installieren/Updaten wieder den aktuellen gehärteten `config.php`-Stub und die öffentliche `/order`-Route respektiert jetzt die Core-Modulschalter für Abos, öffentliche Paketkommunikation und Bestellprozesse. |

### v3.0.17 — 20.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.17** | 🟢 feat | Member-Profile – Pflicht-/Optionalfelder & Custom-Felder | **`CMS/admin/modules/member/MemberDashboardModule.php`, `CMS/admin/views/member/profile-fields.php`, `CMS/member/includes/class-member-controller.php`, `CMS/member/profile.php`, `CMS/core/Services/MemberService.php`, `CMS/admin/partials/topbar.php`, `CMS/DOC/admin/member/README.md`, `README.md`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json` und `Changelog.md` reparieren den Admin-Profilpfad und erweitern die Profilfeldverwaltung.** Der Avatar-Dropdown-Link „Mein Profil“ führt wieder zur echten `/member/profile`-Seite; die Admin-Konfiguration unterscheidet sichtbare und verpflichtende Profilfelder, setzt Benutzername und Mailadresse als feste Pflichtfelder, ergänzt die gewünschten Standardfelder Vorname, Nachname, Benutzername, Geburtsdatum, Mailadresse, Website und SocialMedia und erlaubt zusätzliche projektbezogene Custom-Felder mit Typ, Hinweis, optionalem Pflichtstatus und sicherer User-Meta-Speicherung. |

### v3.0.16 — 20.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.16** | 🔴 fix | Admin-Layout – Sidebar & Editor-Leerraum | **`CMS/admin/partials/sidebar.php`, `CMS/assets/css/admin.css`, `CMS/core/Version.php`, `README.md`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json` und `Changelog.md` straffen die Admin-Sidebar und den Page-/Post-Editor.** Der Site-Name/Domain-Text unter dem Sidebar-Logo wird nicht mehr ausgegeben, das Logo sitzt mittig mit geringem Abstand zum Menü, und der Editor-Grid-Span wurde von `999` auf einen begrenzten Wert reduziert, damit unterhalb des Inhaltsbereichs keine mehrere tausend Pixel hohe Leerfläche entsteht. |

### v3.0.15 — 20.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.15** | 🟢 feat | EditorJS – Rich-Text-Hinweisboxen | **`CMS/assets/js/editor-init.js`, `CMS/assets/css/admin.css`, `CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php`, `CMS/assets/css/editorjs-content.css`, `README.md`, `CMS/DOC/assets/editorjs/README.md`, `CMS/update.json` und `CMS/marketplace/core/365cms/update.json` machen Info-/Warn-/Erfolg-/Kritisch-Boxen rich-editierbar.** Das lokale `CmsWarningTool` ersetzt die bisherigen nativen Eingabefelder durch contenteditable Titel und Inhalte, sodass fett, kursiv, unterstrichen, Inline-Code, Links und Spoiler im Admin auswählbar sind und nach Save/Sanitizer/Public-Rendering erhalten bleiben. |

### v3.0.14 — 20.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.14** | 🔴 fix | Admin-Routing – Protokolle & Audit | **`CMS/core/Routing/AdminRouter.php`, `CMS/admin/logs/*.php`, `README.md`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json` und `Changelog.md` stellen die verschachtelten Log-Unterseiten wieder her.** Neben der Übersicht `/admin/logs` werden jetzt auch `/admin/logs/operational`, `/admin/logs/security-audit`, `/admin/logs/php-errors` und `/admin/logs/channels` direkt vom Admin-Router aufgelöst, sodass die Sidebar-Unterpunkte nicht mehr in der 404-Seite landen. |

### v3.0.13 — 20.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.13** | 🟢 feat | EditorJS – WordPress-like Block-/Blockly-Verhalten | **`CMS/assets/js/admin-content-editor.js`, `CMS/assets/js/editor-init.js`, `CMS/assets/css/admin.css`, `README.md`, `CMS/DOC/assets/editorjs/README.md` und `CMS/DOC/admin/pages-posts/README.md` dokumentieren die Bedienlogik des WordPress-ähnlichen Blockeditors nach.** Der Admin-Editor ist nicht nur ein JSON-Editor, sondern ein blockorientierter Redaktions-Canvas mit gruppiertem Inserter, Commandbar, stabiler Blockauswahl, Drag-&-Drop-Reordering, Undo/Redo, Breitenmodus, Blockkarten für Text/Medien/Layout/Spezialblöcke und read-only-fähigen Vorschaukontexten. |
| **3.0.13** | 🟢 feat | EditorJS – Nachtrag 19.05.2026: Spacer, Galerie & Admin-Integration | **`CMS/assets/js/editor-init.js`, `CMS/assets/js/admin-content-editor.js`, `CMS/assets/css/admin.css`, `CMS/assets/css/editorjs-content.css`, `CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php` und `Changelog.md` dokumentieren die gestrige EditorJS-Ausbaustufe vollständig nach.** Der Editor unterstützt nun robuste Spacer-Höhen inklusive Preset-/Pixel-Normalisierung bis `200px`, stabilere Galerie-/Bilddaten, Editor-nahe Admin-Styles und ein Public-CSS, das Spacer, Bilder und Medienblöcke näher am gespeicherten Editorzustand rendert. |
| **3.0.13** | 🟢 feat | EditorJS – Nachtrag 19.05.2026: Read-only & Bildhandling | **`CMS/assets/js/editor-init.js` ergänzt Read-only-Unterstützung und defensiveres Bildhandling für lokale Editor.js-Tools.** Tools werden so initialisiert, dass Vorschau-/Read-only-Kontexte nicht durch editierbare UI-Annahmen brechen; Bilddaten werden kompatibler normalisiert, damit Upload-, URL-, Caption- und Darstellungsoptionen im Save-/Render-Pfad konsistent bleiben. |
| **3.0.13** | 🟡 refactor | EditorJS – Nachtrag 19.05.2026: Renderer-/Sanitizer-Struktur | **`CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php`, `CMS/DOC/assets/editorjs/README.md` und begleitende Strukturdateien ziehen die interne EditorJS-Verarbeitung nach.** Spacer-Normalisierung, Bild-/Medienattribute, Sanitizer-Allowlist und Renderer-Ausgabe sind klarer getrennt, damit Admin-Save, Public-Render und Theme-CSS denselben Datenvertrag verwenden. |
| **3.0.13** | 🔴 fix | EditorJS / Public-Sanitizer | **`CMS/core/Services/PurifierService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` stabilisieren den Public-Vertrag für EditorJS-Abstände und Theme-TOC-Anker.** Der zentrale HTMLPurifier erlaubt jetzt sichere EditorJS-Attribute wie `data-height`, `role`, `aria-hidden` und CMS-Spacing-Datenattribute in den relevanten Profilen und nutzt eine neue HTML-Definition-Revision, damit öffentliche Themes gespeicherte Spacer-Höhen sowie nachgelagerte Überschriften-IDs nicht mehr durch eine finale Sanitizer-Stufe verlieren. |
| **3.0.13** | 🔵 docs | EditorJS & Services | **`CMS/DOC/assets/editorjs/README.md` und `CMS/DOC/core/SERVICES.md` dokumentieren den aktuellen Save-/Render-/Sanitizer-Vertrag.** Die Dokumentation benennt explizit, dass EditorJS-Spacer über sichere `data-height`-Attribute und theme-seitige CSS-Fallbacks gerendert werden und dass Purifier-Profile HTML5-/ARIA-/Datenattribute nur kontrolliert freigeben. |

### v3.0.12 — 18.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.12** | 🔴 fix | EditorJS – Abstände in Live-/Draft-Ausgabe | **`CMS/assets/js/admin-content-editor.js`, `CMS/core/Services/EditorJsRenderer.php`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php`, `CMS/assets/css/editorjs-content.css` und `Changelog.md` gleichen den sichtbaren Editor-Abstand mit der öffentlichen Ausgabe ab.** Die Textformat-Bubble signalisiert Abstands-/Ausrichtungsänderungen jetzt als echte Editor-Änderung, der Renderer gibt sichere CSS-Variablen und `margin-top:0`/Spacing-Werte aus, visuelle Tune-Daten werden allowlisted übernommen, und die Public-CSS neutralisiert generische Theme-Top-Margins für EditorJS-Blöcke, damit `kompakt`, `normal`, `mehr Abstand`, `großer Abstand` und Spacer-Blöcke auf Live- und Entwurfsseiten sichtbar wie im Editor wirken. |
| **3.0.12** | 🔴 fix | EditorJS – Live-/Entwurfs-Rendering | **`CMS/core/Services/EditorJsRenderer.php`, `CMS/assets/css/editorjs-content.css`, `CMS/core/Bootstrap.php` und `Changelog.md` schließen die Lücke zwischen Admin-Editor und Public-Ausgabe.** Der Renderer akzeptiert jetzt zusätzliche reale EditorJS-Datenformen und Aliase, rendert Bild-URLs auch außerhalb von `file.url`, übernimmt ältere Checklist-States, nutzt Attachment-Titel, gibt sichere responsive Embeds aus und verarbeitet Accordion-Inhalte auch ohne nachfolgende Nested-Blöcke. Zusätzlich lädt das Frontend eine kleine, globale EditorJS-Content-CSS, damit Blöcke, Inline-Formatierungen, Tabellen, Bilder, Galerien, Details, Warnungen und Codeblöcke auf Live- und Draft-Seiten sichtbar dem Editor-Ergebnis entsprechen. |
| **3.0.12** | 🟢 feat | Public Draft Preview | **`CMS/core/Routing/ThemeRouter.php`, `CMS/admin/views/posts/edit.php`, `CMS/admin/views/pages/edit.php`, `CMS/assets/js/editor-init.js`, `CMS/assets/css/admin.css` und `Changelog.md` verbessern den redaktionellen Vorschaupfad.** Entwürfe und noch nicht öffentlich sichtbare Inhalte sind im Public-Bereich für angemeldete Autoren, Admins und passende Capabilities sichtbar, werden aber mit `noindex` und privaten No-Store-Headern geschützt; öffentliche Listen/Archive bleiben published-only. Die EditorJS-Admin-Preview liest zusätzlich das aktive Theme-Stylesheet aus und übernimmt relevante Typography- und Heading-Werte scoped in den Editor. |
| **3.0.12** | 🔵 docs | README & Produktdokumentation | **`README.md` und `Changelog.md` trennen Produktübersicht und Versionshistorie wieder sauber.** Die README wurde vollständig neu strukturiert, nimmt den WordPress-ähnlichen EditorJS prominent als Kernfeature auf und bleibt bewusst frei von Changelog-Einträgen; konkrete Versionsdetails stehen ausschließlich im Changelog. |

### v3.0.11 — 17.05.2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.11** | 🟢 feat | EditorJS – WordPress-ähnliche Admin-UI und produktive Plugins | **`CMS/assets/js/admin-content-editor.js`, `CMS/assets/js/editor-init.js`, `CMS/core/Services/EditorJs/EditorJsAssetService.php`, `CMS/assets/css/admin.css`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` bauen den funktionierenden EditorJS sinnvoll aus.** Auf Basis der Web-/Upstream-Recherche bleiben instabile Layout-Plugins wie Columns zunächst deaktiviert, während lokal vorhandene und praxistaugliche Erweiterungen produktiv eingebunden werden: `editorjs-undo` liefert Undo/Redo inklusive Commandbar-Buttons, `editorjs-drag-drop` aktiviert Block-Reordering, und der Admin-Editor erhält eine Gutenberg-/WordPress-ähnlichere Oberfläche mit Commandbar, Block-Inserter, gruppierten Blockkarten, Breitenmodus und verbesserter Canvas-Typografie. |
| **3.0.11** | 🔴 fix | EditorJS – Bestandseiten stabilisiert und Plugin-Matrix erweitert | **`CMS/assets/js/admin-content-editor.js`, `CMS/assets/js/editor-init.js`, `CMS/core/Services/EditorJs/EditorJsAssetService.php`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` verhindern Browser-Tab-Abstürze bei bestehenden Seiten/Beiträgen und schalten die lokalen EditorJS-Erweiterungen wieder frei.** Die Admin-Block-UI rendert ihre Insert-/Overlay-Elemente jetzt mutationsfrei, signaturbasiert und per `requestAnimationFrame` gedrosselt, sodass der `MutationObserver` nicht mehr auf seine eigenen DOM-Änderungen in einer Endlosschleife reagiert. Zusätzlich lädt die Asset-Kette stabile lokale UMD-Plugins (`embed`, `linkTool`, `attaches`, `warning`, `raw`, `inlineCode`, `underline`, `spoiler`, `accordion`, `imageGallery`), registriert sie defensiv über echte Globals und erweitert die Schnelltoolbar um Checkliste, Medien, Hinweise und HTML-Blöcke. |
| **3.0.11** | 🔴 fix | EditorJS – Admin-Template-Variablen aktivieren echten Editor | **`CMS/admin/pages.php`, `CMS/admin/posts.php` und `Changelog.md` beheben den eigentlichen Grund, warum in Seiten und Beiträgen trotz geladener Assets nur der `EditorJS Notfall-Fallback (DE)` sichtbar blieb.** Die Edit-Routen verwenden für `template_vars` jetzt `array_replace()` statt PHP-Array-Union (`+`), sodass `useEditorJs` den Basiswert `false` wirklich auf `true` überschreibt; dadurch rendert die View wieder den echten EditorJS-Holder und hält die Fallback-Textarea hidden/disabled bis zu einem realen Init-Fehler. |
| **3.0.11** | 🔴 fix | EditorJS – lokale Original-UMD-Toolkette neu verdrahtet | **`CMS/core/Services/EditorJs/EditorJsAssetService.php`, `CMS/assets/js/editor-init.js`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` bauen den Seiten-/Beitragseditor wieder auf die lokalen EditorJS-Assets aus dem aus `ASSETS/editor.js-2.31.6` gespeisten Runtime-Pfad.** Der Asset-Service lädt jetzt Core plus UMD-Tools (`paragraph`, `header`, `editorjs-list`, `image`, `quote`, `code`, `table`, `delimiter`) deterministisch vor der 365CMS-Factory; `editor-init.js` nutzt die echten UMD-Globals statt eigener Tool-Stubs und normalisiert Legacy-Listendaten nur noch als Kompatibilitätsschicht. |
| **3.0.11** | 🔴 fix | EditorJS – Admin-Initialisierung fuer Seiten/Beitraege | **`CMS/assets/js/admin-content-editor.js` und `Changelog.md` beheben den sichtbaren `EditorJS Notfall-Fallback (DE)` in Seiten- und Beitragseditoren.** Der EditorJS-Wait-Pfad nutzt den Logger jetzt aus dem korrekten Modul-Scope statt vor `initEditorJs()` mit `ReferenceError: logEditor is not defined` abzubrechen; zusätzlich rendert die gruppierte Editor-Toolbar ihren Overflow-Bereich nur, wenn zusätzliche Gruppen vorhanden sind, sodass der echte Editor nach erfolgreicher Runtime-Erkennung stabil aktiv bleibt. |
| **3.0.11** | 🔴 fix | EditorJS – Seiten/Beiträge sauber aus Original-ASSETS neu aufgebaut | **`ASSETS/editor.js-2.31.6/editorjs.umd.js`, `CMS/assets/editorjs/editorjs.umd.js`, `CMS/core/Services/EditorJs/EditorJsAssetService.php`, `CMS/admin/pages.php`, `CMS/admin/posts.php`, `CMS/admin/views/pages/edit.php`, `CMS/admin/views/posts/edit.php`, `CMS/assets/js/editor-init.js`, `CMS/assets/js/admin-content-editor.js`, `CMS/assets/css/admin.css`, `CMS/DOC/assets/editorjs/README.md` und `Changelog.md` ersetzen den defekten EditorJS-Pfad durch eine deterministische lokale UMD-Bootkette.** Der Core wird aus den Original-ASSETS in die CMS-Runtime kopiert, `editorjs-core-loader.js`/`editorjs-core-boot.js` entfallen, `editor-init.js` registriert nur die real vorhandenen stabilen Tools `paragraph`, `header`, `list`, `image`, `quote`, `code`, `table`, `delimiter`, und die Page/Post-Fallback-Textareas bleiben hidden/disabled bis zu einem echten Init-/Readiness-Fehler. |
| **3.0.11** | 🟢 feat | Cron/Scheduler – externe Cron-Expression-Library integriert | **`CMS/assets/cron/CronExpression.php`, `CMS/assets/cron/composer.json`, `CMS/assets/autoload.php`, `CMS/core/Services/CronExpressionAdapter.php`, `CMS/core/Services/CronRunnerService.php`, `CMS/admin/modules/system/SystemInfoModule.php`, `CMS/admin/views/system/cron-status.php`, `CMS/DOC/assets/cron/README.md` und `Changelog.md` integrieren `poliander/cron` (v3.3.1) als produktive Runtime-Library in 365CMS.** Der stündliche Scheduler nutzt jetzt Cron-Expressions robust über einen Adapter mit Fallback auf die bisherige 3600s-Intervalllogik, damit bestehende Jobs ohne Regression weiterlaufen; Cron-Status und Doku zeigen zusätzlich Expression/Scheduler-Engine transparent an. |
| **3.0.11** | 🔴 fix | EditorJS – Core-Ladepfad fuer Seiten/Beitraege hotfixed | **`CMS/core/Services/EditorJs/EditorJsAssetService.php`, `CMS/assets/js/editorjs-core-loader.js` und `Changelog.md` beheben den Init-Abbruch in den Post-/Page-Editoren, wenn `window.EditorJS` im `getPageAssets()`-Pfad fehlte.** Der neue Loader stellt den Core synchron vor `admin-content-editor.js` bereit, sodass die Editor-Initialisierung nicht mehr durch einen fehlenden Core blockiert wird; bestehende JSON-Inhalte und Save-Flows bleiben unveraendert. |
| **3.0.11** | 🔴 fix | EditorJS – Asset-Loading/Tool-Buttons stabilisiert | **`CMS/core/Services/EditorJs/EditorJsAssetService.php`, `CMS/assets/js/editor-init.js`, `CMS/assets/js/admin-content-editor.js` und `Changelog.md` beheben die zentrale EditorJS-Regression mit defekten Script-Referenzen und ungültigen Toolbar-Blöcken.** Das Core-Loading nutzt jetzt das vorhandene `editorjs.mjs` korrekt als Modul, nicht vorhandene UMD-Dateien werden nicht mehr eingebunden, und die gruppierte Admin-Toolbar blendet nur tatsächlich registrierte Blocktypen ein, damit keine kaputten Tool-Buttons oder Runtime-Fehler mehr entstehen. |
| **3.0.11** | 🟡 refactor | EditorJS – Fallback-Tooling für Blogger/Tech-Content | **`CMS/assets/js/editor-init.js`, `CMS/core/Services/EditorJs/EditorJsSanitizer.php`, `CMS/core/Services/EditorJsRenderer.php` und `Changelog.md` ergänzen den Editor um robuste interne Fallback-Tools und konsistente Save/Render-Pfade.** Für fehlende externe Plugins stehen jetzt praxistaugliche Blöcke wie `checklist`, `embed`, `linkTool`, `attaches`, `details`, `callout`, verbesserter `code`-Block sowie Inline-`marker` bereit; Sanitizer und Renderer wurden für den neuen `details`-Typ erweitert, ohne bestehende Blockdatenformate zu brechen. |
| **3.0.11** | ⬜ chore | EditorJS-Doku aktualisiert (17.05.2026) | **`CMS/DOC/assets/editorjs/README.md` und `Changelog.md` dokumentieren die aktuelle EditorJS-Tool-Matrix, den Save-/Render-/Sanitizer-Vertrag sowie bekannte Grenzen.** Die Doku beschreibt explizit, welche Tools produktiv verfügbar sind und welche Sicherheits-/Kompatibilitätsentscheidungen beim Embed- und Plugin-Fallback gelten. |
| **3.0.11** | 🔴 fix | Admin/UI – globale Klick-Interaktion wiederhergestellt | **`CMS/assets/js/admin.js`, `CMS/assets/css/admin.css` und `Changelog.md` beheben eine Regression, bei der verwaiste Fullscreen-Overlay-/Backdrop-Zustände die gesamte Admin-Interaktion blockieren konnten.** Ein defensiver Startup-Cleanup entfernt nur dann stale Backdrops und `modal-open`, wenn kein aktives Modal geöffnet ist; zusätzlich verhindern CSS-Guards, dass unsichtbare Overlay-Layer Pointer-Events abfangen. |
| **3.0.11** | 🟡 refactor | Admin/Dashboard – KPI-Entdopplung, Typografie & Rhythmus | **`CMS/admin/views/dashboard/index.php`, `CMS/assets/css/admin-dashboard.css`, `CMS/assets/js/admin-dashboard.js`, `CMS/DOC/admin/dashboard/DASHBOARD.md`, `CMS/DOC/admin/README.md` und `Changelog.md` setzen das priorisierte UX-Feedback fuer das Dashboard iterativ um.** Die doppelte KPI-Logik wurde aufgeloest (eine dominante Top-KPI-Reihe, darunter kontextbezogene Arbeits-Widgets ohne redundante Zahlenwiederholung), KPI-Werte klar typografisch priorisiert, vertikale Abschnittsabstaende samt subtiler Trenner eingefuehrt, Empty-States mit ruhigem Mikrohinweis nachgeschaerft, der Failed-Login-Indikator als sichtbarer Danger-Badge mit Kartenakzent aufgewertet und das Grid auf einen robusten 5/3/2-Breakpointfluss umgestellt; bestehende Business-Logik und Personalisierungsflows bleiben unveraendert. |
| **3.0.11** | ⬜ chore | Admin/Sidebar – konsistente Child-Icons | **`CMS/admin/partials/sidebar.php`, `CMS/assets/css/admin.css` und `Changelog.md` ergaenzen die Sidebar-Unterpunkte um eine einheitliche Icon-Sprache als Grundlage fuer kuenftige collapsed-Navigation.** Gruppen-Children erhalten jetzt konsistente, slug-basierte Mini-Icons mit neutralem Fallback, ohne Routing-, Berechtigungs- oder Modulstruktur zu aendern. |
| **3.0.11** | 🟡 refactor | Admin/UI Sweep – vollständiger Unterseiten-Nachzug | **`CMS/assets/css/admin.css`, `CMS/admin/views/settings/general.php`, `CMS/admin/views/themes/settings.php`, `CMS/admin/views/system/modules.php`, `CMS/admin/views/seo/technical.php` und `Changelog.md` schließen den restlichen Design-Sweep über alle Admin-Unterseiten ab.** Radius-Grenzen für Buttons/Boxen sind zentral weiter verschärft (max. 2px), Header-/Toolbar-Blöcke folgen konsistent `Header → Toolbar/Filter → Inhalt` und verschachtelte Boxen wurden visuell klarer abgestuft, ohne Änderungen an Business-Logik oder Formularabläufen. |
| **3.0.11** | ⬜ chore | Admin/UI-Texte – technische Metahinweise bereinigt | **`CMS/admin/views/system/modules.php`, `CMS/admin/views/seo/technical.php` und `Changelog.md` ersetzen nicht-nutzwertige Meta-/Debugformulierungen durch präzise Bedientexte.** Die Oberflächen bleiben fachlich vollständig, wirken aber klarer und konsistenter im produktiven Admin-Kontext. |
| **3.0.11** | 🟡 refactor | Admin/UI global – harte Radius-/Box-Hierarchie-Regeln | **`CMS/assets/css/admin-tabler.css`, `CMS/assets/css/admin.css`, `CMS/admin/views/member/plugin-widgets.php`, `CMS/admin/views/member/design.php` und `Changelog.md` erzwingen adminweit den harten UI-Standard vom 17.05.2026.** Buttons sowie Karten-/Boxcontainer sind jetzt durch zentrale Overrides auf maximal 2px Radius begrenzt; zusätzlich erhalten verschachtelte Boxen einen klar abgesetzten Hintergrund/Borderton für sichtbare Hierarchie, ohne Änderungen an bestehender Business-Logik. |
| **3.0.11** | ⬜ chore | Admin-Doku – UI-Hard-Standard dokumentiert (17.05.2026) | **`CMS/DOC/admin/legal/README.md`, `CMS/DOC/admin/performance/README.md`, `CMS/DOC/admin/security/README.md`, `CMS/DOC/admin/seo/README.md` und `Changelog.md` dokumentieren den globalen Admin-Designvertrag.** Die Bereichsdokumente benennen explizit die verbindlichen Radiusgrenzen (max. 2px) und die Kontrastregel für verschachtelte Boxen. |
| **3.0.11** | 🟡 refactor | Admin/UI-Textbereinigung in Recht, Sicherheit & Themes | **`CMS/admin/views/legal/data-requests.php`, `CMS/admin/views/security/antispam.php`, `CMS/admin/views/themes/list.php` und `Changelog.md` entfernen nicht-fachliche Meta-/Designtexte aus den betroffenen Admin-Oberflächen.** Sichtbare Hinweise wie `Designstand 17.05.2026`, layoutbezogene Platzhaltertexte und technische Implementierungsformulierungen wurden durch kurze, fachlich nutzbare UI-Texte ersetzt; Business-Logik, Aktionen und bestehende Flows bleiben unverändert. |
| **3.0.11** | 🟡 refactor | Admin/Themes & Design – CMS-Loginpage Hinweise unterhalb des Hauptinhalts | **`CMS/admin/views/themes/cms-loginpage.php` und `Changelog.md` ordnen die Landingpage-Struktur für `Themes & Design → CMS Loginpage` neu.** Die bisher rechts platzierte Karte `Hinweise` wird als untere Hinweisbox unterhalb der Hauptkarte gerendert, während der Form-/Hauptbereich auf volle Breite erweitert wird; bestehende Formularfelder, Aktionen und Business-Logik bleiben unverändert. |
| **3.0.11** | 🟡 refactor | Admin/Legal Sites, Cookie Manager, Performance, SEO, Plugins – UX-Layout überarbeitet | **`CMS/admin/views/legal/sites.php`, `CMS/admin/views/legal/cookies.php`, `CMS/admin/views/performance/settings.php`, `CMS/admin/views/seo/dashboard.php`, `CMS/admin/views/plugins/list.php` und `CMS/assets/css/admin.css` verbessern die Bedienoberfläche in fünf Kernbereichen sichtbar.** Header-, Toolbar- und Inhaltszonen sind konsistenter gegliedert, geeignete Karten/Formblöcke laufen auf großen Viewports sinnvoll nebeneinander und fallen auf kleineren Breiten sauber vertikal zurück; alle bestehenden Filter-, Save- und Aktionsflüsse bleiben unverändert. |
| **3.0.11** | ⬜ chore | Admin-Dokumentation aktualisiert (17.05.2026) | **`CMS/DOC/admin/legal/README.md`, `CMS/DOC/admin/performance/README.md`, `CMS/DOC/admin/seo/README.md`, `CMS/DOC/admin/plugins/README.md` und `Changelog.md` aktualisieren die Bereichsdokumentation zur aktuellen Seitenstruktur.** Die Texte fokussieren die fachliche Bedienführung ohne technische UI-Metahinweise für Endnutzeroberflächen. |
| **3.0.11** | 🟡 refactor | Admin/Recht + Themes (non-settings) – sichtbarer Redesign-Pass | **`CMS/admin/views/legal/data-requests.php`, `CMS/admin/views/themes/list.php` und `CMS/assets/css/admin.css` ziehen zentrale Non-Settings-Adminansichten sichtbar auf den klassischen Backend-Vertrag nach.** Die Bereiche nutzen jetzt deutliche Headerblöcke mit Meta/Aktionen, klar getrennte Toolbar-/Statuszonen, vereinheitlichte Tabellen-/Listenoptik, ruhigere KPI-/Kachelflächen und persistente Info-Boxen mit Titelzeile; alle bestehenden DSGVO-, Theme- und Aktionsflows bleiben unverändert. |
| **3.0.11** | ⬜ chore | Doku-Update zur Designrunde 17.05.2026 | **`README.md`, `CMS/DOC/admin/legal/README.md`, `CMS/DOC/admin/themes-design/README.md` und `Changelog.md` dokumentieren den sichtbaren Non-Settings-Redesign-Stand für Rechts- und Theme-Verwaltung vom 17.05.2026.** Die Dokumentation beschreibt den nachgezogenen `Header → Toolbar → Inhalt`-Vertrag und den unveränderten Business-Logikpfad explizit. |
| **3.0.11** | 🟡 refactor | Admin/Themes, Recht, SEO, Performance – Layoutvertrag vereinheitlicht | **`CMS/admin/views/themes/list.php`, `CMS/admin/views/themes/marketplace.php`, `CMS/admin/views/themes/editor.php`, `CMS/admin/views/themes/customizer-missing.php`, `CMS/admin/views/themes/cms-loginpage.php`, `CMS/admin/views/legal/sites.php`, `CMS/admin/views/legal/cookies.php`, `CMS/admin/views/legal/data-requests.php`, `CMS/admin/views/seo/dashboard.php`, `CMS/admin/views/seo/analytics.php`, `CMS/admin/views/seo/meta.php`, `CMS/admin/views/seo/social.php`, `CMS/admin/views/seo/redirects.php`, `CMS/admin/views/performance/settings.php`, `CMS/assets/css/admin.css`, `CMS/DOC/admin/themes-design/README.md`, `CMS/DOC/admin/legal/README.md`, `CMS/DOC/admin/seo/README.md`, `CMS/DOC/admin/performance/README.md` und `Changelog.md` harmonisieren die nächste Designrunde auf den nüchternen CMS-Backend-Stil.** Die betroffenen Kernseiten folgen jetzt konsistent `Header → Toolbar/Filter → Inhalt`, KPI-/Info-Bereiche wurden typografisch nachgezogen und Aktionszonen übergreifend overflow-sicher gemacht; Business-Logik, Formularaktionen und bestehende Workflows bleiben unverändert. |
| **3.0.11** | 🟡 refactor | Admin/Medien-Kategorien + Benutzer/Gruppen/Rollen/Einstellungen – klassischer Listen- und Toolbar-Flow | **`CMS/admin/views/media/categories.php`, `CMS/admin/views/users/list.php`, `CMS/admin/views/users/groups.php`, `CMS/admin/views/users/roles.php`, `CMS/admin/views/users/settings.php`, `CMS/admin/views/users/edit.php`, `README.md`, `CMS/DOC/admin/media/README.md`, `CMS/DOC/admin/users-groups/README.md` und `Changelog.md` harmonisieren weitere Kernbereiche auf denselben professionellen CMS-Backend-Vertrag.** In den betroffenen Screens wurden KPI-Kachelblöcke reduziert, Header-Metazeilen ergänzt und Steuerzonen konsistent als `Header → Toolbar/Filter → Inhalt` nachgezogen; Info-Boxen bleiben persistent mit kurzer Titel-/Textstruktur und overflow-sicheren Aktionen, während alle bestehenden Benutzer-, Gruppen-, Rollen-, Kategorie- und Berechtigungsflüsse unverändert bleiben. |
| **3.0.11** | 🟡 refactor | Admin/Medien – Haupttabs auf klassischen Backend-Flow vereinheitlicht | **`CMS/admin/views/media/library.php`, `CMS/admin/views/media/featured.php`, `CMS/admin/views/media/check.php`, `README.md`, `CMS/DOC/admin/media/README.md` und `Changelog.md` ziehen die drei meistgenutzten Medien-Haupttabs visuell auf denselben nüchternen CMS-Layoutvertrag nach.** Die bisherigen KPI-Kachelreihen wurden durch kompakte Header-Metaangaben ersetzt, Toolbar-/Filterbereiche klar als zweite Ebene unterhalb des Headers strukturiert und persistente Info-Boxen mit titelgeführtem Kopf plus overflow-sicheren Aktionen ergänzt; bestehende Such-, Filter-, Replace-, Prüf- und Dateiverwaltungslogik bleibt unverändert. |
| **3.0.11** | 🟡 refactor | Admin/Seiten & Beiträge – SEO-Sidebarcards ans Ende verschoben | **`CMS/admin/views/posts/edit.php`, `CMS/admin/views/pages/edit.php`, `CMS/assets/css/admin.css` und `Changelog.md` setzen die SEO-bezogenen Sidebar-Cards im Editor konsistent hinter die Basis-Cards.** `SEO-Card`, Lesbarkeit, `SERP & Social-Vorschau`, `SEO-Score & Checkliste`, `Revisionen & Vergleich` und `Erweitertes SEO` tragen nun einen dedizierten Sidebar-Slot und werden per zentralem Ordering als letzter Card-Block gerendert; Collapse-Defaults sowie Save-/Form-/Business-Logik bleiben unverändert. |
| **3.0.11** | 🟡 refactor | Admin/System & Aboverwaltung – klassischer Backend-Layoutvertrag für Kernseiten | **`CMS/admin/views/settings/general.php`, `CMS/admin/views/system/mail-settings.php`, `CMS/admin/views/subscriptions/settings.php`, `CMS/assets/css/admin.css`, `README.md`, `CMS/DOC/admin/system-settings/README.md`, `CMS/DOC/admin/subscription/README.md`, `CMS/DOC/admin/README.md` und `Changelog.md` ziehen drei stark genutzte Admin-Bereiche auf einen einheitlichen, nüchternen CMS-Stil nach.** In `Allgemeine Einstellungen`, `Mail & Azure OAuth2` und `Abo-Einstellungen` wurde die Struktur klar auf `Header → Toolbar/Tabs → Inhalt` ausgerichtet, KPI-/Badge-Anmutungen visuell reduziert und statische Hinweise auf persistente Info-Boxen mit sauberem Titel-/Textaufbau sowie overflow-sicheren Aktionen umgestellt; alle bestehenden Settings-, Mail-, Queue- und Abo-Flows bleiben unverändert. |
| **3.0.11** | 🟡 refactor | Admin/Beiträge – Kategorien & Tags Hinweisbox `Archiv- & Redirect-Vertrag` | **`CMS/admin/views/posts/categories.php`, `CMS/admin/views/posts/tags.php`, `CMS/assets/css/admin.css` und `Changelog.md` vereinheitlichen Aufbau und Responsive-Verhalten der Hinweisbox `Archiv- & Redirect-Vertrag` in Kategorien/Tags.** Die Box nutzt jetzt einen konsistenten vertikalen Aufbau mit Titel, Kurzbeschreibung und Aktionszeile; Buttontexte umbrechen kontrolliert mit stabiler Höhe/Abständen, sodass kein Horizontal-Overflow mehr entsteht und bestehende Actions/Logik unverändert bleiben. |
| **3.0.11** | 🟡 refactor | Admin/Medienverwaltung – Bibliothek ohne Preset-Panel | **`CMS/admin/views/media/library.php` und `Changelog.md` entfernen im Bereich `Medienverwaltung > Medien` den UI-Abschnitt für speicherbare Filter vollständig.** Der Preset-/Permalink-Block (gespeicherte Filter, Preset speichern/löschen, Link kopieren) entfällt, während bestehende Such-, Filter-, Listen-, Bulk- und Medienaktionen unverändert weiterlaufen. |
| **3.0.11** | 🟡 refactor | Admin/Beiträge – Kategorien & Tags Toolbar-Struktur | **`CMS/admin/views/posts/categories.php`, `CMS/admin/views/posts/tags.php`, `CMS/assets/css/admin.css` und `Changelog.md` ordnen die Steuerzone in den Bereichen Kategorien/Tags konsistent neu.** Filter-/Aktions-Controls stehen nun als klarer oberer Toolbar-Block über der jeweiligen Inhaltskarte (Form/List/Tabelle), mit einheitlicher Labeling-, Abstands- und Ausrichtungslogik im nüchternen CMS-Stil; bestehende CRUD-, Bulk- und Löschabläufe bleiben unverändert. |
| **3.0.11** | 🟡 refactor | Admin/Medien + Benutzer & Gruppen – UI-Hierarchie und Info-Box-Standard | **`CMS/admin/views/media/library.php`, `CMS/admin/views/media/featured.php`, `CMS/admin/views/media/check.php`, `CMS/admin/views/media/categories.php`, `CMS/admin/views/media/settings.php`, `CMS/admin/views/users/list.php`, `CMS/admin/views/users/groups.php`, `CMS/admin/views/users/roles.php`, `CMS/admin/views/users/edit.php`, `CMS/admin/views/users/settings.php`, `CMS/admin/views/partials/flash-alert.php`, `CMS/assets/css/admin.css`, `CMS/assets/js/admin.js`, `README.md`, `CMS/DOC/admin/media/README.md`, `CMS/DOC/admin/users-groups/README.md` und `Changelog.md` vereinheitlichen die Admin-Teilbereiche Medien sowie Benutzer/Gruppen auf denselben professionellen CMS-Layoutvertrag.** Statische Hinweisbereiche verwenden nun eine gemeinsame Info-Box-Komponente mit Titelzeile oberhalb optionaler Aktionen, kurzen Texten und robustem Wrapping ohne äußeren Overflow; außerdem wurde das automatische Ausblenden von Alerts im Admin entfernt, damit Hinweise sichtbar bleiben, bis sie bewusst geschlossen werden. |
| **3.0.11** | 🟡 refactor | Admin/Content-Bereiche – Kategorien, Tags, Kommentare, TOC, Hub-Sites, Tabellen | **`CMS/admin/views/posts/categories.php`, `CMS/admin/views/posts/tags.php`, `CMS/admin/views/comments/list.php`, `CMS/admin/views/toc/settings.php`, `CMS/admin/views/hub/list.php`, `CMS/admin/views/tables/list.php`, `CMS/admin/views/tables/settings.php`, `CMS/admin/views/tables/edit.php`, `CMS/assets/css/admin.css`, `README.md`, `CMS/DOC/admin/pages-posts/README.md` und `Changelog.md` vereinheitlichen sechs weitere Content-Admin-Bereiche auf den nüchternen CMS-Backend-Stil.** Dekorative KPI-Kachelblöcke wurden in funktionale Listen-Header mit klarer Meta-Hierarchie überführt, Toolbars/Filter/Tabelle/Aktionen strukturell angeglichen und Zeilenaktionen zurückhaltend im bestehenden Designsystem nachgeschärft; alle bestehenden Filter-, Such-, Bulk-, Sortier-, Pagination- und CRUD-Flows bleiben unverändert. |
| **3.0.11** | 🟡 refactor | Admin/Dashboard – visuelle Hierarchie & Kartenstruktur | **`CMS/admin/views/dashboard/index.php`, `CMS/assets/css/admin-dashboard.css`, `README.md` und `Changelog.md` überarbeiten die Admin-Übersichtsseite im nüchternen CMS-Backend-Stil.** Das Dashboard zeigt primäre Kernkennzahlen jetzt in einer eigenen, klaren Top-Reihe; die zentrale Arbeitsübersicht wurde visuell entschlackt (neutralere Boxen/Borders statt „Kachel-Look“) und die sekundären Widgets sind für bessere Scanbarkeit logisch geordnet (Aufmerksamkeit/Bestellungen vor System- und Sicherheitsblöcken). Bestehende Datenquellen, Links, Favoriten-, Verlauf- und Personalisierungsfunktionen bleiben unverändert. |
| **3.0.11** | 🟡 refactor | Admin/Seiten & Beiträge – Listenfinish + Sidebar-Reihenfolge ohne View-Override | **`CMS/admin/views/posts/list.php`, `CMS/admin/views/pages/list.php`, `CMS/assets/css/admin.css`, `README.md` und `Changelog.md` finalisieren das klassische Listenlayout für Beiträge/Seiten und korrigieren die Editor-Sidebar-Reihenfolge ohne Änderungen an den Edit-View-Dateien.** Die Toolbar erhält eine klarere `Filter & Suche`-Beschriftung, Tabellenlinks/-Zeilenaktionen werden im nüchternen CMS-Stil nachgeschärft und im Editor wird per zentralem CSS die Reihenfolge der rechten Panels so gesetzt, dass `Aktionen` über der `Titel/Slug`-Card steht. Filter-, Such-, Sortier-, Pagination- und Aktionslogik bleiben unverändert. |
| **3.0.11** | 🟡 refactor | Admin/Seiten & Beiträge – Listenlayout & Bedienführung | **`CMS/admin/views/posts/list.php`, `CMS/admin/views/pages/list.php`, `CMS/assets/css/admin.css`, `README.md` und `Changelog.md` vereinheitlichen die Übersichtsseiten für Beiträge und Seiten im klassischen CMS-Backend-Stil.** KPI-Karten entfallen zugunsten eines nüchternen Listen-Headers mit klarer Metazeile; Filter, Suche, Bulk-Aktionen und Tabellenstruktur folgen jetzt einer konsistenten Informationshierarchie mit professionellen Abständen, Header-/Zeilenstates und responsiven Breakpoints. Bestehende Filter-, Such-, Bulk- und Zeilenaktionen bleiben vollständig erhalten. |
| **3.0.11** | 🟡 refactor | Admin/Editor-Sidebar – Reihenfolge Aktionen/Bild | **`CMS/admin/views/posts/edit.php`, `CMS/admin/views/pages/edit.php` und `Changelog.md` ordnen die obere Sidebar-Reihenfolge in Beitrags- und Seiteneditor konsistent neu.** Die Card `Aktionen` steht jetzt als erstes Panel im rechten Sidebar-Stack, direkt gefolgt von `Beitragsbild` bzw. `Contentheader Bild`; alle weiteren Cards bleiben unverändert in der bestehenden Reihenfolge. Collapse-Defaults, Styling sowie Save-/Form-Logik bleiben unverändert. |
| **3.0.11** | 🟡 refactor | Admin/Editor – SEO-KPI-Grid & Default-Collapse | **`CMS/admin/views/pages/edit.php`, `CMS/admin/views/posts/edit.php`, `CMS/admin/views/partials/content-seo-score-panel.php`, `CMS/assets/css/admin.css`, `README.md` und `Changelog.md` stellen den SEO-Bereich im Seiten-/Beitragseditor auf den gewünschten Standardzustand um.** In `SEO-Score & Checkliste` werden die ersten vier KPI-Kacheln oberhalb von `Live-Hinweise` jetzt als festes 2x2-Grid mit zwei Kacheln pro Zeile dargestellt (Desktop), mit kontrolliertem Fallback auf 1 Spalte für kleinere Viewports. Gleichzeitig starten nach der Karte `Veröffentlichung` alle folgenden Sidebar-/SEO-Panels standardmäßig eingeklappt; Save-/Form-Logik und der nüchtern-klassische Stil bleiben unverändert. |
| **3.0.11** | 🟡 refactor | Admin/SEO-Default-Hinweis – Button-Wrap & Titelposition | **`CMS/assets/css/admin.css`, `README.md` und `Changelog.md` korrigieren das finale Layout im Bereich `SEO-Default-Hinweis` gezielt nach.** Die Notice läuft jetzt strikt vertikal (Titelblock klar oberhalb der Aktionen), während die drei Reset-Buttons auf Desktop stabil nebeneinander im 3-Spalten-Grid stehen. Gleichzeitig erlauben die Buttons sauberes Multi-Line-Wrapping mit belastbarer Mindesthöhe/Zeilenhöhe, sodass lange Beschriftungen keinen Horizontal-Overflow mehr auslösen; bei kleineren Breakpoints fällt das Layout kontrolliert auf 2 bzw. 1 Spalte zurück. |
| **3.0.11** | 🟡 refactor | Admin/Beiträge – New-Post Sidebar-Layout | **`CMS/admin/posts.php`, `CMS/admin/views/posts/edit.php`, `CMS/admin/views/pages/edit.php`, `CMS/assets/css/admin.css`, `README.md` und `Changelog.md` trennen den Desktop-Layoutfluss für `Neuer Beitrag`, `Bestehender Beitrag` und Seiteneditor explizit und beheben die verbleibende Sidebar-Lücke im New-Post-Screen.** Root Cause war der Grid-Row-Vertrag im gemeinsamen Editor-Layout: Der linke Editor lag in `grid-row: 1`, wodurch Zeile 1 auf Editorhöhe anwuchs und die zweite rechte Sidebar-Card nach unten gedrückt wurde. Der Editor spannt jetzt den linken Track über mehrere Grid-Zeilen (`grid-row: 1 / span 999`), während die Sidebar-Slots oben ausgerichtet bleiben; dadurch starten Editor und Sidebar bündig und rechte Cards laufen ohne editorbedingte Zwischenräume direkt untereinander. |
| **3.0.11** | 🔴 fix | Admin/Performance & Page-Schema | **`CMS/admin/views/performance/settings.php`, `CMS/admin/views/performance/media.php`, `CMS/core/PageManager.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` stabilisieren Performance-Adminseiten und Schema-Kompatibilitätsprüfungen.** Medien-Cache-TTL-Optionen werden vor `htmlspecialchars()` und beim Selected-Vergleich explizit als String behandelt, damit numerische PHP-Array-Keys keinen TypeError auslösen. Page-Schema-Prüfungen schließen `SHOW COLUMNS`-Cursor nun vor anschließenden `ALTER TABLE`-Queries und vermeiden dadurch unbuffered-query-Konflikte. |
| **3.0.11** | 🟡 refactor | Admin/Editor-Layout & Hinweisboxen (Seiten & Beiträge) | **`CMS/assets/css/admin.css`, `CMS/admin/views/partials/content-preview-card.php`, `CMS/admin/views/partials/content-seo-score-panel.php`, `CMS/admin/views/pages/edit.php`, `CMS/admin/views/posts/edit.php`, `README.md` und `Changelog.md` stabilisieren den Seiten-/Beitragseditor für den produktiven Desktop-Flow.** Der Desktop-Editor bleibt in Seiten und Beiträgen oben bündig, die Sidebar startet auf derselben Höhe und ihre Cards bleiben kompakt ohne rechte Überläufe. Für `SEO-Default-Hinweis`, Live-Hinweis-Badges, SERP-/Social-Preview-Texte sowie URL-/Code-Inhalte greifen jetzt robuste Wrap-Regeln (`overflow-wrap`, `word-break`, `white-space`) und responsive Action-Buttons, damit in der Sidebar nichts mehr aus dem Fenster ragt. |
| **3.0.11** | 🟡 refactor | Admin/Seiteneditor & SEO-Default-Hinweis (Sidebar) | **`CMS/assets/css/admin.css`, `CMS/assets/js/admin-seo-editor.js`, `CMS/admin/views/pages/edit.php`, `README.md` und `Changelog.md` härten den Sidebar-Flow im Seiten-/Beitragseditor gegen horizontales Ausbrechen und gleichen den Seiteneditor visuell an den stabilen Beitragseditor an.** Der SEO-Default-Hinweis konnte durch lange, nicht umbrechbare Tokens (z. B. Slugs/URL-nahe Defaulttexte) in Alert-Listen und Button-Reihen die Sidebar nach rechts aufdrücken; Sidebar-Grid-Items und Card-Inhalte erzwingen jetzt `min-width: 0` plus robustes `overflow-wrap`, und die Hinweisbuttons umbrechen sauber statt aus dem Viewport zu laufen. Zusätzlich nutzt der Seiteneditor im oberen Bereich jetzt denselben kompakten Card-Flow wie Beiträge (ohne Spacer-Label-Zeile und mit konsistentem Bild-/Action-Stack), wodurch Top-Alignment und vertikaler Rhythmus im Sidebar-Startbereich stabil bleiben. |
| **3.0.11** | 🟡 refactor | Admin/SEO-Sidebar – aufklappbare Panels & Default-Hinweis | **`CMS/admin/views/pages/edit.php`, `CMS/admin/views/posts/edit.php`, `CMS/admin/views/partials/content-readability-card.php`, `CMS/admin/views/partials/content-preview-card.php`, `CMS/admin/views/partials/content-seo-score-panel.php`, `CMS/admin/views/partials/content-advanced-seo-panel.php`, `CMS/assets/css/admin.css`, `CMS/assets/js/admin-seo-editor.js`, `README.md` und `Changelog.md` führen einen klaren Collapse-Flow ab der `SEO-Card` ein und straffen den SEO-Default-Hinweis visuell.** `SEO-Card`, Lesbarkeits-Card, SERP-/Social-Vorschau, SEO-Score/Checkliste und Erweitertes SEO nutzen jetzt konsistente Toggle-Header mit Chevron im nüchternen Admin-Stil (wichtige Bereiche initial offen, ergänzende Panels initial eingeklappt). Der Bereich `SEO-Default-Hinweis` ist als dezente Box mit Titel-Unterstrich aufgebaut, ohne separaten Beschreibungstext; die Reset-Buttons stehen nebeneinander und bleiben auf kleineren Breiten responsiv umbruchfähig. |
| **3.0.11** | 🟡 refactor | Admin/SEO-Default-Hinweis – finale Reduktion | **`CMS/admin/views/pages/edit.php`, `CMS/admin/views/posts/edit.php`, `CMS/assets/css/admin.css`, `CMS/assets/js/admin-seo-editor.js`, `README.md` und `Changelog.md` reduzieren den Bereich `SEO-Default-Hinweis` final auf Titelzeile plus Reset-Buttons.** Erklärende Texte und Hinweislisten werden im sichtbaren Markup vollständig entfernt; die Alert-Box zeigt nur noch eine klare Kopfzeile und darunter eine horizontal ausgerichtete Button-Reihe mit sauberen Breiten/Abständen für Desktop. Für kleinere Breiten bleibt ein kontrollierter responsiver Fallback erhalten, ohne Save-/Form-Verhalten zu verändern. |
| **3.0.11** | 🟡 refactor | Admin/Theme Font Manager – Vorschau-Card entfernt | **`CMS/admin/views/themes/fonts.php`, `CMS/assets/js/admin-font-manager.js` und `Changelog.md` entfernen im Font Manager die komplette rechte `Vorschau`-Card samt Demo-Inhalten und nutzen den frei gewordenen Platz für den Hauptinhalt über die volle Breite.** Die bisherige Zweispaltenstruktur (`col-lg-8` + Vorschau-Spalte) wird auf eine volle Inhaltsspalte umgestellt, und nicht mehr benötigte Preview-Bindings im zugehörigen JS entfallen; Scan-, Download-, Zuordnungs-, Delete- und Save-Workflows bleiben unverändert. |

### v3.0.10 — 16. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.10** | 🟢 feat | Admin/Medien Uploadpfad | **`CMS/core/Services/MediaService.php`, `CMS/core/Services/Media/UploadHandler.php`, `CMS/admin/modules/media/MediaModule.php`, `CMS/admin/views/media/settings.php`, `CMS/DOC/admin/media/MEDIA.md`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` machen den Medien-Upload-Zielmodus explizit steuerbar.** Standardmäßig werden Uploads jetzt in den aktuell geöffneten Medienordner geschrieben. Wird die Option „Datumsordner Jahr/Monat/Tag anlegen“ aktiviert, erzeugt der verwaltete Uploadpfad darunter automatisch `YYYY/MM/DD` und verhindert eine doppelte Datumsverschachtelung, wenn man bereits in einem Datumsordner steht. |

### v3.0.9 — 16. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.9** | 🔴 fix | Medien / Cache & WebP | **`CMS/core/Services/MediaService.php`, `CMS/admin/modules/seo/PerformanceModule.php`, `CMS/admin/views/performance/settings.php`, `CMS/admin/views/performance/media.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` stellen WebP-Begleitdateien und sichtbare Medien-Cache-TTL wieder her.** Originalerhaltende Uploads verändern die hochgeladene Datei weiterhin nicht, erzeugen bei aktivierter WebP-Option aber wieder kleinere `.webp`-Begleitdateien. Die Medien-Optimierung zeigt die Browser-Cache-TTL jetzt direkt an und synchronisiert die Performance-TTL zusätzlich in die Upload-`.htaccess`, damit PHINITs direkte `/uploads`-Bilder dieselbe Cache-Policy wie `/media-file` erhalten. |

### v3.0.8 — 16. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.8** | 🔴 fix | Medien / Originaldateien | **`CMS/core/Services/MediaService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` bewahren Medienbibliothek-, Editor- und Beitragsbild-Uploads als echte Originaldateien.** Originalerhaltende Bild-Uploads und Replace-in-place überspringen jetzt das verlustbehaftete Re-Encoding, Maximalmaß-Resize sowie automatische WebP-/Thumbnail-Erzeugung, damit die gespeicherte Upload-Datei nicht größer oder anders codiert wird als die hochgeladene Datei und keine zusätzlichen Derivate entstehen. |

### v3.0.7 — 16. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.7** | 🔴 fix | Editor/Medien | **`CMS/core/Services/MediaService.php`, `CMS/core/Services/EditorJs/EditorJsUploadService.php`, `CMS/admin/views/partials/featured-image-picker.php`, `CMS/core/Services/EditorJs/EditorJsImageLibraryService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` bewahren Editor-Bilduploads als echte Originaldateien und erweitern den Beitragsbild-Picker.** Bild-Uploads aus Beiträgen und Seiten überspringen jetzt das verlustbehaftete Re-Encoding des gespeicherten Originals; optionale WebP-Dateien bleiben nur Derivate. Der Beitragsbild-Picker nutzt `ArtikelRahmen` als breiteren Standardpräfix, akzeptiert `ArtikelRahmen*` als Prefix-Syntax und kappt explizit gefilterte Treffer nicht mehr bei 250 Einträgen. |

### v3.0.6 — 16. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.6** | 🟢 feat | Admin/Medien | **`CMS/admin/media.php`, `CMS/admin/modules/media/MediaModule.php`, `CMS/admin/views/media/featured.php`, `CMS/assets/js/admin-media-integrations.js`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `CMS/DOC/admin/media/README.md`, `CMS/DOC/admin/media/MEDIA.md`, `README.md` und `Changelog.md` erweitern den Beitrags-&-Site-Medien-Replace-Flow um Mehrfach-Ersetzung.** Admins können in mehreren Zeilen per „Durchsuchen“ Ersatzbilder vorbereiten; sobald bei einer Zeile „Bild ersetzen“ geklickt wird, sammelt die Oberfläche alle vorbereiteten Dateien in einen gemeinsamen CSRF-geschützten Multipart-POST. Serverseitig verarbeitet die neue `replace_items`-Aktion die Paare aus Zielpfad und Datei weiter über denselben validierten Replace-in-place-Vertrag wie die Einzel-Ersetzung. |

### v3.0.5 — 16. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.5** | 🟠 perf | Core/Performance Mediencache | **`CMS/admin/modules/seo/PerformanceModule.php`, `CMS/admin/views/performance/settings.php`, `CMS/core/Services/MediaDeliveryService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `CMS/DOC/admin/performance/PERFORMANCE.md`, `README.md` und `Changelog.md` ergänzen die Bildauslieferungs-Cache-TTL als feste Performance-Auswahl.** Admins können für öffentlich ausgelieferte Medien jetzt 3, 7 oder 31 Tage wählen; 7 Tage ist Standard und Fallback für fehlende oder alte Werte. Die `/media-file`-Delivery nutzt diese TTL für öffentliche Bilder und liefert dadurch Lighthouse-freundliche `Cache-Control`-/`Expires`-Header, während deaktiviertes Browser-Caching weiterhin im Revalidierungsmodus bleibt. |

### v3.0.4 — 16. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.4** | 🔴 fix | Admin/Medien | **`CMS/core/Services/MediaService.php`, `CMS/core/Services/EditorJs/EditorJsUploadService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `CMS/DOC/admin/media/README.md`, `CMS/DOC/admin/media/MEDIA.md`, `README.md` und `Changelog.md` verhindern doppelte physische Ablagen identischer Beitrags- und Seitenbilder.** Der Featured-Image-Upload prüft vorhandene permanente `articles/`- und `pages/`-Titelbilder jetzt größen- und SHA-256-basiert, überspringt temporäre Draft-Pfade und gibt bei identischem Inhalt direkt die bestehende Medienreferenz zurück. Dadurch können mehrere Beiträge oder Seiten dasselbe ausgewählte Titelbild teilen, ohne neue Kopien wie `ArtikelRahmen_slug-1.jpg` zu erzeugen. |

### v3.0.3 — 15. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.3** | 🟢 feat | Admin/Medien | **`CMS/admin/media.php`, `CMS/admin/modules/media/MediaModule.php`, `CMS/admin/views/media/featured.php`, `CMS/admin/views/media/check.php`, `CMS/admin/partials/sidebar.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `CMS/DOC/admin/media/README.md`, `CMS/DOC/admin/media/MEDIA.md`, `README.md` und `Changelog.md` verschieben den Featured-Image-Konsistenz-Check in den neuen Unterpunkt `Medien Check`.** Dadurch bleibt `Beitrags & Site Medien` auf die tatsächlich verwendeten Featured Images und den Replace-in-place-Flow fokussiert, während die read-only Prüfliste für fehlende oder defekte Zuordnungen separat gefiltert und direkt aus dem Medienmenü erreichbar ist. |

### v3.0.2 — 15. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.2** | 🔴 fix | Admin-Audit / Auth, Media & Security | **`CMS/core/SchemaManager.php`, `CMS/core/Services/MediaDeliveryService.php`, `CMS/core/Services/Media/MediaRepository.php`, `CMS/admin/modules/media/MediaModule.php`, `CMS/admin/modules/security/SecurityAuditModule.php`, `CMS/views/auth/cms-auth.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md` und `Changelog.md` beheben die im Live-Admin-Audit gefundenen Core-Fehler.** Das Runtime-Schema erstellt die für Passwort-Resets benötigte Tabelle `password_resets` auch auf bestehenden Installationen, Admin-Medienlinks zeigen Originaldateien über den kontrollierten `/media-file`-Endpunkt statt potenziell blockierter Direkt-Upload-URLs, versteckte Punkt-Dateien wie `.htaccess` erscheinen nicht mehr als normale Medien, das HSTS-Audit bewertet vorhandene Apache-/Proxy-Fallback-Header korrekt und die CMS-Loginpage trennt Passwort-Label und Passwort-vergessen-Link für Screenreader sauberer. |

### v3.0.1 — 15. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.1** | 🔴 fix | Public HTML Cache / Auth-Header | **`CMS/core/Router.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `CMS/DOC/admin/performance/PERFORMANCE.md` und `Changelog.md` verhindern das Ausliefern gecachter Member-Header an anonyme Besucher.** Öffentliche GET-/HEAD-Responses werden jetzt auf echte Auth-, MFA- oder Device-Session-Signale geprüft. Sobald personalisierte Auth-State-Daten vorhanden sind, sendet der Router private `no-store`-Header und überspringt öffentliche 304-Validatoren. Dadurch können öffentliche Seiten weiterhin gecacht werden, aber angemeldete Varianten mit Member-Bar, Dashboard-Link oder Benachrichtigungsbadge landen nicht mehr in Public-/LiteSpeed-/Proxy-Caches. |

### v3.0.0 — 14. Mai 2026

| Version | Typ | Bereich | Beschreibung |
|---------|-----|---------|-------------|
| **3.0.0** | 🛡️ security | Core/Final Audit – Logging, Diagnose & Schema-Härtung | **`CMS/core/Database.php`, `CMS/core/AuditLogger.php`, `CMS/admin/views/partials/flash-alert.php`, `CMS/core/Services/RedirectService.php`, `CMS/core/Version.php`, `CMS/update.json`, `CMS/marketplace/core/365cms/update.json`, `README.md`, `Changelog.md` und `Changelog_old.md` schließen den finalen Core-only Auditlauf für den 365CMS-Hauptcore ab.** Der Audit fokussierte ausschließlich `365CMS.DE` bzw. `CMS/` und ignoriert externe Theme- und Plugin-Repositories. Als konkrete Nachhärtung redigieren Low-Level-Datenbank- und Audit-Logger nun Inline-Secrets, Kontrollzeichen und überlange Diagnosewerte, geben keine DB-Benutzernamen mehr in Fehlerlogs aus und melden DB-Verbindungsfehler nach außen generischer, während technische Details intern begrenzt bleiben. Das gemeinsame Admin-Flash-Partial redigiert sensible Fehlerreport-Kontexte vor Anzeige und Report-Weitergabe und entfernt den früheren `print_r()`-Fallback aus der Diagnoseausgabe. Der Redirect-Schema-Upgrade-Helfer akzeptiert nur noch die erwarteten internen Tabellen-/Spalten-/Definition-Kombinationen, bevor dynamische DDL ausgeführt wird. |
| **3.0.0** | 🛡️ security | Admin-Shell / Theme-Editor | **`CMS/admin/partials/section-page-shell.php`, `CMS/index.php` und `CMS/themes/cms-default/error.php` verhindern Header-Warnings nach bereits gestarteter Admin-Ausgabe.** Eingebettete Admin-Views wie der Theme-Editor werden jetzt inline abgefangen, sicher protokolliert und mit redigierter Fehlerdetailzeile angezeigt; globale Fehler-Templates setzen Status- und Content-Type-Header nur noch, wenn noch keine Ausgabe begonnen hat. |
| **3.0.0** | 🛡️ security | Zweiter Auditlauf – Fatal-, Installer- und Schema-Logs | **Der erneute Durchlauf hat weitere Low-Level-Logpfade gehärtet.** `CMS/index.php`, `CMS/core/Debug.php`, `CMS/core/Security.php`, `CMS/core/CacheManager.php`, `CMS/install/InstallerService.php` und `CMS/core/SchemaManager.php` redigieren Diagnosemeldungen nun ebenfalls vor dem Schreiben in Error-Logs, Debug-Dateien, Debug-Panel und Fehlerreport-Payloads. Der Bootstrap kürzt und maskiert Fatal-Error- und Stacktrace-Logs, Rate-Limit-/Installer-/Cache-Fehler vermeiden rohe Exception-Texte, und der SchemaManager schreibt das automatisch generierte Erst-Admin-Passwort nicht mehr ins globale Error-Log, sondern verweist nur noch auf die bestehende einmalige Credential-Datei. Zusätzlich validiert `SchemaManager::ensureColumnExists()` Tabellen-, Spalten- und ALTER-Präfixe, bevor interne Runtime-Migrationen ausgeführt werden. |
| **3.0.0** | 🛡️ security | Dritter Auditlauf – Remote-/Archiv- und DOM-Härtung | **Der dritte Durchlauf hat Remote-/Archiv- und Web-Best-Practice-Funde geschlossen.** `CMS/core/PluginManager.php` entpackt hochgeladene Plugin-ZIPs nicht mehr direkt in den Plugin-Root, sondern validiert Pfade, Top-Level-Slug, Hauptdatei, Symlink-Freiheit, Eintragszahl und entpackte Größe vor einem Staging-Extract mit anschließendem Security-Scan und atomarem Move; fehlgeschlagene Extracts räumen ihr temporäres Staging-Verzeichnis wieder auf. `CMS/core/Http/Client.php` blockiert URLs mit eingebetteten Zugangsdaten, validiert Ports, begrenzt Response-Größen während des Downloads, setzt HTTP/HTTPS-Protokollgrenzen und prüft nach dem Request die tatsächlich verbundene IP erneut gegen private/reservierte Netze. `CMS/assets/js/admin-dashboard.js` rendert die zuletzt genutzten Admin-Ziele nun DOM-basiert statt per `innerHTML`-Stringaufbau. |
| **3.0.0** | 🛡️ security | Folgeaudit – Update-/Restore-Archivpfade | **Die priorisierten Remote-/Archiv-Hotspots wurden weiter gekapselt.** `CMS/core/Services/UpdateService.php` akzeptiert Plugin-/Theme-Installationen nun nur noch als direkte Child-Ziele unter den verwalteten Plugin-/Theme-Roots, blockiert Root-Overwrite-Szenarien, prüft Update-ZIPs zusätzlich auf Eintragszahl, Einzel-/Gesamtgröße, Kontrollzeichen, Punktsegmente und Unix-Symlinks und validiert nach dem Extract, dass der komplette Staging-Baum linkfrei innerhalb des Staging-Roots bleibt. Installationsfehler-Kontexte werden vor Logger- und Audit-Ausgabe maskiert, insbesondere bei URL-Query-Secrets. `CMS/core/Services/BackupService.php` nutzt dieselben Archivgrenzen für Restore-ZIPs und validiert entpackte Restore-Staging-Bäume vor dem Move gegen Symlinks und Root-Ausbruch. |
| **3.0.0** | 🛡️ security | Folgeaudit – Shared Editor & AI-Translation | **Der kritische Shared-Editor-Pfad wurde gegen Client- und Server-Randfälle nachgezogen.** `CMS/assets/js/admin-content-editor.js` erzwingt für AI-Translation-Requests nun Same-Origin-Endpunkte, setzt ein clientseitiges Zeitlimit, prüft deklarierte und tatsächliche JSON-Antwortgrößen und verwirft übergroße Antworten ohne sie dauerhaft im UI-State zu halten. `CMS/admin/modules/system/AiEditorJsTranslationModule.php` validiert Editor.js-Payloads vor der AI-Pipeline zusätzlich auf gültiges JSON, maximale Blockanzahl, erlaubte Blocktyp-Metadaten und array-basierte Blockdaten. `CMS/assets/js/admin-seo-editor.js` begrenzt die Liveanalyse von Editor.js-JSON, Blockanzahl und HTML-Fragmenten defensiv, damit große oder manipulierte Inhalte die SEO-Vorschau nicht unnötig blockieren. Damit folgt der Übersetzungspfad enger dem OWASP-ASVS-Fail-Closed-Prinzip und reduziert unnötige Heap-Last bei fehlerhaften oder manipulierten Editor-Daten. |
| **3.0.0** | ⬜ chore | Release-Schnitt & Dokumentation | **Die 2.x-Historie wurde von `Changelog.md` nach `Changelog_old.md` verschoben und eine neue, schlanke `Changelog.md` für Version `3.0.0` angelegt.** Version, Update-Metadaten und README verweisen auf den neuen Major-Release-Stand; die historische Detailspur bleibt weiterhin vollständig über `Changelog_old.md` nachvollziehbar. |

> Die vollständige historische 2.x-Historie wurde in [`Changelog_old.md`](Changelog_old.md) archiviert.
