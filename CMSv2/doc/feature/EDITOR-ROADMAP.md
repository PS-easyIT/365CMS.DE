# 365CMS – Editor & Content-Creation Roadmap

**Bereich:** Page-Builder, Text-Editor, Block-System, Landing-Pages  
**Stand:** 19. Februar 2026  
**Prioritäten:** 🔴 Kritisch · 🟠 High · 🟡 Mittel · 🟢 Low

---

## 1. Block-Editor (Basis)

### 🔴 E-01 · Block-System-Fundament
**Aktuell:** SunEditor (Rich-Text)  
**Ziel:** Block-basierter Editor für strukturierten Content

| Stufe | Feature |
|---|---|
| Stufe 1 | Block-Datenmodell (JSON-basierte Inhaltsstruktur) |
| Stufe 2 | Basis-Blöcke: Paragraph, Heading (H1-H6), Image, List, Quote, Divider |
| Stufe 3 | Block-Toolbar (Format, Ausrichtung, Link, Löschen) |
| Stufe 4 | Block-Reorder via Drag & Drop |
| Stufe 5 | Slash-Commands (`/heading`, `/image` – wie Notion) |
| Stufe 6 | Undo/Redo (History-Stack) |
| Stufe 7 | Autosave (alle 60 Sekunden, lokal im Browser) |
| Stufe 8 | Revisions-System (letzte 20 Versionen abrufbar) |

---

### 🟠 E-02 · Erweiterte Blöcke
| Block | Beschreibung | Priorität |
|---|---|---|
| Columns | 2-6 spaltige Layouts | 🟠 High |
| Button | CTA-Buttons mit Stil-Optionen | 🟠 High |
| Video | YouTube/Vimeo/lokal einbetten | 🟠 High |
| Code | Syntax-Highlight, Sprach-Auswahl | 🟠 High |
| Table | Editierbare Tabellen, Merge-Cells | 🟡 Mittel |
| Accordion | Aufklappbare FAQ-Sektionen | 🟡 Mittel |
| Tabs | Tab-Panels für strukturierten Content | 🟡 Mittel |
| Alert/Notice | Info/Warning/Error-Blöcke | 🟠 High |
| Icon Grid | Icon + Titel + Text (Feature-Listen) | 🟡 Mittel |
| Timeline | Chronologische Darstellungen | 🟡 Mittel |
| Pricing Table | Preistabellen mit Highlights | 🟡 Mittel |
| Testimonial | Einzelne Kundenbewertung | 🟡 Mittel |
| Map | Karten-Einbettung (OpenStreetMap) | 🟡 Mittel |
| Form | Formular (via cms-forms) einbetten | 🟠 High |
| HTML | Raw-HTML-Block für Experten | 🟠 High |
| Shortcode | CMS-Shortcodes einbetten | 🟠 High |
| Gallery | Bild-Galerie (Grid/Masonry/Slider) | 🟡 Mittel |
| File Download | Datei-Download-Button | 🟡 Mittel |

---

### 🟡 E-03 · Plugin-Blöcke
Jedes Plugin kann eigene Blöcke registrieren:

| Plugin | Block | Beschreibung |
|---|---|---|
| cms-experts | Expert-Card-Block | Einen Experten einbetten |
| cms-companies | Company-Card-Block | Eine Firma einbetten |
| cms-events | Events-List-Block | Kommende Events anzeigen |
| cms-jobs | Job-Listings-Block | Offene Stellen anzeigen |
| cms-shop | Product-Block | Produkt einbetten |
| cms-testimonials | Testimonials-Block | Bewertungen einbetten |

---

## 2. Page-Builder (Visual)

### 🟠 E-04 · Drag & Drop Page-Builder
| Stufe | Feature |
|---|---|
| Stufe 1 | Canvas-Ansicht (Frontend-Preview beim Bearbeiten) |
| Stufe 2 | Drag & Drop von Blöcken in Canvas |
| Stufe 3 | Inline-Editing (Text direkt im Canvas bearbeiten) |
| Stufe 4 | Section/Column-System (Zeilen und Spalten als Struktur) |
| Stufe 5 | Responsive-Controls (Mobile/Tablet/Desktop pro Block) |
| Stufe 6 | Block-Styling (Hintergrundfarbe, Padding, Margin, Border-Radius) |
| Stufe 7 | Block-Animations (Einblend-Effekte, Scroll-Trigger) |
| Stufe 8 | Copy & Paste von Blöcken innerhalb einer Seite |
| Stufe 9 | Template-Library (vorgefertigte Sektions-Vorlagen auswählen) |
| Stufe 10 | Global-Blöcke (einmal erstellt, überall eingefügt, zentral bearbeitet) |

---

### 🟡 E-05 · Landing-Page-Templates
Pro Theme mindestens 5 Landing-Page-Vorlagen:

| Template | Zielgruppe |
|---|---|
| IT-Beratung Hero | TechNexus-Theme |
| Jobmesse-Anmeldung | PersonalFlow-Theme |
| Bauprojekt-Showcase | BuildBase-Theme |
| Logistik-Partner-Gesucht | LogiLink-Theme |
| Arztpraxis-Termin | MedCare-Theme |
| Kursanmeldung | Academy365-Theme |
| SaaS-Produkt-Launch | Generisch |
| Event-Registrierung | Generisch |
| Webinar-Anmeldung | Generisch |

---

## 3. Text-Editor-Optimierungen

### 🟠 E-06 · Rich-Text-Editor Verbesserungen
| Stufe | Feature |
|---|---|
| Stufe 1 | Markdown-Shortcuts (** → Fett, # → Überschrift) |
| Stufe 2 | Paste-as-Plain-Text mit optionalem HTML-Behalten |
| Stufe 3 | Floating-Toolbar bei Textauswahl |
| Stufe 4 | Inline-Link-Preview (hover → zeigt Ziel-URL) |
| Stufe 5 | Interner Link-Picker (CMS-Seiten/Posts suchen) |
| Stufe 6 | Tipp-Fehler-Erkennung (Browser-Spellcheck-Integration) |
| Stufe 7 | Fokus-Modus (ablenkungsfreies Schreiben) |
| Stufe 8 | Wörterzählung, Lesedauer-Schätzung |
| Stufe 9 | SEO-Analyse-Overlay (Keyword-Dichte, Headings-Struktur) |
| Stufe 10 | KI-Assistent (Text verbessern, zusammenfassen, übersetzen) |

---

### 🟡 E-07 · Code-Editor
| Stufe | Feature |
|---|---|
| Stufe 1 | Monaco-Editor für HTML/CSS/JS-Bearbeitung im Admin |
| Stufe 2 | PHP-Syntax-Highlighting für Template-Bearbeitung |
| Stufe 3 | Auto-Completion (HTML-Tags, CSS-Properties) |
| Stufe 4 | Code-Formatierung (Prettier-Integration) |
| Stufe 5 | Diff-Ansicht beim Bearbeiten von Template-Overrides |

---

## 4. Content-Workflow

### 🟠 E-08 · Editorial-Workflow
| Stufe | Feature |
|---|---|
| Stufe 1 | Status: Entwurf, Prüfung, Genehmigt, Veröffentlicht, Archiviert |
| Stufe 2 | Redaktionsrollen: Autor, Redakteur, Chef-Redakteur |
| Stufe 3 | Zuweisungs-System (Autor weist Artikel Redakteur zu) |
| Stufe 4 | Kommentare/Notizen pro Artikel (intern, nicht öffentlich) |
| Stufe 5 | E-Mail-Benachrichtigungen bei Status-Änderungen |
| Stufe 6 | Redaktionsplan (Kalender-Ansicht mit geplanten Veröffentlichungen) |
| Stufe 7 | Geplante Veröffentlichung (Datum/Zeit in der Zukunft) |
| Stufe 8 | Automatische Inhalts-Ablaufzeit (Content wird zu Datum archiviert) |

---

### 🟡 E-09 · Content-Vorlagen (Templates)
| Stufe | Feature |
|---|---|
| Stufe 1 | Artikel-Vorlagen (vordefinierte Struktur für wiederkehrende Formate) |
| Stufe 2 | Custom-Fields-Sets pro Inhaltstyp |
| Stufe 3 | Conditional-Fields (Feld erscheint basierend auf anderen Werten) |
| Stufe 4 | Pflichtfelder mit Validierung vor Veröffentlichung |
| Stufe 5 | Content-Modell-Designer (Felder per Drag & Drop konfigurieren) |

---

## 5. Medien im Editor

### 🟡 E-10 · Medien-Integration
| Stufe | Feature |
|---|---|
| Stufe 1 | Media-Picker direkt im Editor (kein Seitenwechsel) |
| Stufe 2 | Drag & Drop von Desktop-Dateien in Editor |
| Stufe 3 | Inline-Bildbearbeitung (Crop, Rotate direkt im Editor) |
| Stufe 4 | Bild-Beschriftung und Alt-Text-Editor pro Bild |
| Stufe 5 | Stock-Photo-Suche (Unsplash, Pixabay – direkt im Editor) |

---

*Letzte Aktualisierung: 19. Februar 2026*
