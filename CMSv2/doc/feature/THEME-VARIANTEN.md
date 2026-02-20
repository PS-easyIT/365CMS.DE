# 365CMS – Theme-Varianten & Design-Richtlinien

**Bereich:** Design-System, Branchen-Themes, Theme-Entwicklung  
**Stand:** 19. Februar 2026  
**Prioritäten:** 🔴 Kritisch · 🟠 High · 🟡 Mittel · 🟢 Low

---

## Theme-System-Architektur

### Technische Basis (gilt für alle Themes)
- **CSS Custom Properties** als Design-Token-System
- **Tailwind CSS** als Utility-Framework (optionale Integration)
- **Mobile-First** Breakpoints (320px, 768px, 1024px, 1280px, 1536px)
- **Dark Mode** via `prefers-color-scheme` + manuellem Toggle
- **Container Queries** für komponentenbasiertes Responsive
- **CSS Grid** und **Flexbox** als primäre Layout-Systeme
- **WCAG 2.2 AA** als Mindeststandard für Kontraste
- **CSS Layers** für saubere Spezifitäts-Kontrolle

### Theme-Struktur
```
themes/
└── theme-name/
    ├── theme.json          # Theme-Metadaten, Design-Tokens
    ├── style.css           # Root CSS, Custom Properties
    ├── functions.php       # Theme-Funktionen (minimal!)
    ├── templates/          # Seiten-Templates
    ├── partials/           # Template-Parts (Header, Footer, Cards)
    ├── assets/
    │   ├── css/
    │   │   ├── base.css
    │   │   ├── components.css
    │   │   ├── utilities.css
    │   │   └── dark-mode.css
    │   ├── js/
    │   └── fonts/
    └── screenshots/
        ├── preview-light.png
        ├── preview-dark.png
        └── mobile-preview.png
```

### Design-Token-Hierarchie
```css
:root {
  /* ─── Farb-Palette (Brand) ─── */
  --color-primary-50: #f0f9ff;
  --color-primary-500: #0ea5e9;
  --color-primary-900: #0c4a6e;

  /* ─── Semantische Tokens ─── */
  --color-background: #ffffff;
  --color-text: #0f172a;
  --color-border: #e2e8f0;
  --color-accent: var(--color-primary-500);

  /* ─── Typografie ─── */
  --font-sans: 'Inter', system-ui, sans-serif;
  --font-display: 'Plus Jakarta Sans', sans-serif;
  --text-base: 1rem;
  --text-scale: 1.25;

  /* ─── Abstände ─── */
  --space-unit: 0.25rem;
  --radius-sm: 0.25rem;
  --radius-md: 0.5rem;
  --radius-lg: 1rem;
  --radius-full: 9999px;

  /* ─── Schatten ─── */
  --shadow-sm: 0 1px 2px rgba(0,0,0,.05);
  --shadow-card: 0 4px 6px rgba(0,0,0,.07);
  --shadow-modal: 0 20px 60px rgba(0,0,0,.15);
}
```

---

## Theme 1: **TechNexus** (IT & Technologie)

### Branchen-Fokus: IT-Dienstleister, Softwarehäuser, Tech-Hubs
**Plugin-Empfehlung:** cms-experts, cms-companies, cms-events, cms-jobs, cms-projects

### Design-Philosophie
> Präzise, technisch, vertrauenswürdig. Das Design kommuniziert Kompetenz und Innovation durch saubere Typografie, strukturierte Grids und dezente Tech-Ästhetik.

### Farb-System
```css
/* TechNexus Design-Tokens */
:root {
  --color-primary: #2563eb;          /* Electric Blue */
  --color-primary-dark: #1d4ed8;
  --color-secondary: #10b981;        /* Emerald (Erfolg/Aktivität) */
  --color-accent: #8b5cf6;           /* Violet (Premium-Features) */
  --color-neutral: #1e293b;          /* Slate Dark */
  --color-surface: #f8fafc;          /* Off-White */
  --color-code-bg: #0f172a;          /* Dark für Code-Snippets */
  
  /* Dark Mode */
  --dm-background: #0f172a;
  --dm-surface: #1e293b;
  --dm-text: #e2e8f0;
  --dm-border: #334155;
}
```

### Typografie
| Rolle | Font | Größe | Gewicht |
|---|---|---|---|
| Display | JetBrains Mono / Plus Jakarta Sans | 3rem–5rem | 700 |
| Headings | Plus Jakarta Sans | 1.5rem–2.5rem | 600 |
| Body | Inter | 1rem | 400 |
| Code | JetBrains Mono | 0.875rem | 400 |
| Label | Inter | 0.75rem | 500 |

### Komponenten-Highlights
- **Expert-Card:** Tech-Stack-Badges, GitHub-Aktivitäts-Graph, Skill-Level-Bars
- **Firmen-Card:** Tech-Logos-Grid, Mitarbeiterzahl-Tag, Branchen-Icons
- **Hero:** Animated Particle-Background (diskrete Netzwerk-Punkte), Split-Layout
- **Navigation:** Horizontal mit Mega-Menu für Kategorien, Sticky mit Blur-Effekt
- **Code-Blöcke:** Syntax-Highlighting, Copy-Button, Sprach-Label
- **Status-Badges:** Online/Verfügbar/Beschäftigt (real-time)
- **Dark-Mode:** Vollständig optimiert mit eigenem Farbset

### Feature-Seiten
| Seite | Besonderheit |
|---|---|
| Experten-Archiv | Masonry-Grid, Skill-Filter als Chip-Cloud, Live-Suche |
| Firmen-Archiv | Branchen-Kacheln, Größen-Filter, Map-Integration |
| Event-Kalender | Interaktiver Monatskalender, Farbcodierung nach Event-Typ |
| Job-Board | Kanban-artige Listenansicht, Quick-Apply-Overlay |

### Ausbaustufen

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | Basis-Template (Header, Footer, Homepage, Archiv, Detail) | 🔴 Kritisch |
| Stufe 2 | Dark-Mode vollständig implementiert | 🔴 Kritisch |
| Stufe 3 | Expert-Card v2 (Tech-Stack-Anzeige, Skill-Meter) | 🟠 High |
| Stufe 4 | Animierter Hero mit Particle.js oder Canvas-Animation | 🟡 Mittel |
| Stufe 5 | GitHub-Profil-Widget-Integration auf Expert-Profil | 🟡 Mittel |
| Stufe 6 | Three.js-Netzwerk-Visualisierung auf Homepage | 🟢 Low |
| Stufe 7 | Terminal-Style-Easter-Egg (Konami-Code Aktivierung) | 🟢 Low |

---

## Theme 2: **PersonalFlow** (Personalvermittlung & HR)

### Branchen-Fokus: Personalagenturen, HR-Tech, Karriereportale, Headhunter
**Plugin-Empfehlung:** cms-jobs, cms-experts, cms-directory, cms-messaging, cms-subscriptions

### Design-Philosophie
> Menschlich, warm, professionell. Gesichter und Personen stehen im Mittelpunkt. Das Design erzeugt Vertrauen und vermittelt Karriere-Chancen durch warme Farben und organische Formen.

### Farb-System
```css
:root {
  --color-primary: #f59e0b;          /* Amber (Energie, Optimismus) */
  --color-primary-dark: #d97706;
  --color-secondary: #0284c7;        /* Sky Blue (Vertrauen) */
  --color-accent: #ec4899;           /* Pink (Auffälligkeit) */
  --color-neutral: #292524;          /* Warm Dark */
  --color-surface: #fffbeb;          /* Warm White */
  --color-success: #16a34a;
  --color-badge-new: #ef4444;        /* "NEU"-Badge */

  --dm-background: #1c1917;
  --dm-surface: #292524;
  --dm-text: #fafaf9;
}
```

### Typografie
| Rolle | Font | Besonderheit |
|---|---|---|
| Display | Playfair Display | Elegante Serifen für Professionalität |
| Headings | Nunito | Freundlich, rund |
| Body | Nunito Sans | Gut lesbar bei längeren Profil-Texten |
| Zahlen/Stats | Oswald | Prägnant für KPIs wie Jahresgehalt |

### Komponenten-Highlights
- **Kandidaten-Card:** Portrait-Foto prominent, Top-Skills als Chips, Gehaltswunsch
- **Job-Card:** Firmen-Logo, Standort-Pin, Gehalts-Range, "Sofort"-Badge
- **Kandidaten-Match-Score:** Kreisdiagramm (% Passung)
- **Karriere-Tracking-Board:** Kanban für Bewerbungsstatus
- **Firmen-Kultur-Badges:** Remote-First, Flexzeit, Kinderfreundlich, etc.

### Besondere Seitentypen
| Seite | Konzept |
|---|---|
| Job-B |  Dreispaltig: Branchen-Filter, Job-Liste, Quick-Preview-Panel |
| Talentpool | Kandidaten-Grid mit KI-Empfehlung für Recruiter |
| Karriere-Ratgeber | Blog-Layout mit Podcast-Player |
| Gehaltsrechner | Interaktives Tool als Seiten-Element |

### Ausbaustufen

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | Basis-Layout mit Job-Board und Kandidaten-Profilen | 🔴 Kritisch |
| Stufe 2 | Match-Score-Visualisierung (CSS-Gauge) | 🟠 High |
| Stufe 3 | Bewerbungs-Status-Kanban im Dashboard | 🟠 High |
| Stufe 4 | Firmen-Kultur-Profil-Seite mit interaktiven Elementen | 🟡 Mittel |
| Stufe 5 | Gehaltsband-Visualisierung (horizontaler Range-Slider-Style) | 🟡 Mittel |
| Stufe 6 | KI-Job-Match mit erklärten Übereinstimmungen | 🟢 Low |

---

## Theme 3: **BuildBase** (Bau & Handwerk)

### Branchen-Fokus: Bauunternehmen, Architekten, Handwerksbetriebe, Baustoffhandel
**Plugin-Empfehlung:** cms-directory, cms-projects, cms-bookings, cms-portfolio, cms-reviews

### Design-Philosophie
> Robust, solide, handwerklich. Das Design kommuniziert Verlässlichkeit, Qualität und physische Präsenz durch erdige Farben, strukturierte Texturen und klare Hierarchien.

### Farb-System
```css
:root {
  --color-primary: #b45309;          /* Amber Brown (Erde, Material) */
  --color-primary-dark: #92400e;
  --color-secondary: #f97316;        /* Orange (Sicherheit, Energie) */
  --color-accent: #fcd34d;           /* Bau-Gelb (Warnfarbe) */
  --color-concrete: #78716c;         /* Beton-Grau */
  --color-surface: #fafaf9;
  --color-dark: #1c1917;
  
  /* Texturen via CSS */
  --texture-concrete: url('/assets/textures/concrete-subtle.png');
  --texture-wood: url('/assets/textures/wood-subtle.png');
}
```

### Typografie
| Rolle | Font | Charakter |
|---|---|---|
| Display | Bebas Neue | Kräftig, industriell |
| Headings | Roboto Condensed | Kompakt, klar |
| Body | Roboto | Neutral, gut lesbar |
| Handwerk-Badges | Oswald | Prägnant |

### Spezielle Komponenten
- **Projekt-Referenz-Card:** Vorher/Nachher-Slider, Bauvolumen, Fertigstellungsdatum
- **Handwerker-Profil:** Gewerk-Badges, Zertifikate, Einzugsgebiet-Karte
- **Anfrage-Formular:** Einfach, direkt, mobile-optimiert (Baustelle = Handy)
- **Portfolio-Grid:** Baufotos in Industrial-Stil, Filter nach Gewerk/Region
- **Bewertungs-Modul:** Sternebewertung mit Fotos (Abnahme-Beweis)
- **Notfall-Kontakt-Button:** Prominent für Havariedienste (Klempner etc.)

### Ausbaustufen

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | Basis-Template: Dienstleister-Verzeichnis, Portfolio | 🔴 Kritisch |
| Stufe 2 | Vorher/Nachher-Bild-Slider (CSS-Clip-Path) | 🟠 High |
| Stufe 3 | Einzugsgebiet-Karte pro Betrieb (OpenStreetMap) | 🟠 High |
| Stufe 4 | Kostenvoranschlag-Anfrage-Formular (mehrstufig) | 🟠 High |
| Stufe 5 | Zertifikats-/Führerschein-Scanner (Foto-Upload) | 🟡 Mittel |
| Stufe 6 | Notfall-Service-Modul (24/7-Anfragen separat) | 🟡 Mittel |

---

## Theme 4: **LogiLink** (Logistik & Transport)

### Branchen-Fokus: Speditionen, Lagerdienstleister, Kurierdienste, Fuhrparkmanagement
**Plugin-Empfehlung:** cms-bookings, cms-tracking, cms-directory, cms-invoicing, cms-map

### Design-Philosophie
> Schnell, präzise, international. Logistik braucht Geschwindigkeit und Übersicht. Das Design priorisiert Statusanzeigen, Tracking und operative Effizienz über Ästhetik.

### Farb-System
```css
:root {
  --color-primary: #0284c7;          /* Sky Blue (Frische, Effizienz) */
  --color-primary-dark: #0369a1;
  --color-secondary: #16a34a;        /* Grün (Lieferung erfolgreich) */
  --color-warning: #f59e0b;          /* Gelb (Verzögerung) */
  --color-danger: #ef4444;           /* Rot (Kritisch) */
  --color-transit: #8b5cf6;          /* Lila (In Transit) */
  --color-surface: #f0f9ff;
  --color-dark: #0c1a2e;             /* Nacht-Blau für Dashboards */
}
```

### Status-Bar-System (Tracking)
```css
/* Lieferstatus-Farben */
.status-warehouse   { --status-color: #94a3b8; } /* Eingelagert */
.status-picked      { --status-color: #f59e0b; } /* Kommissioniert */
.status-transit     { --status-color: #8b5cf6; } /* In Transit */
.status-delivered   { --status-color: #16a34a; } /* Geliefert */
.status-delayed     { --status-color: #ef4444; } /* Verzögert */
.status-returned    { --status-color: #64748b; } /* Retoure */
```

### Spezielle Komponenten
- **Live-Map-Tracker:** Fahrzeug-Position auf OSM-Karte
- **Sendungs-Status-Stepper:** Schritt-für-Schritt Fortschrittsanzeige
- **Füllstand-Monitor:** Kapazitäts-Visualisierung für Lager
- **Tour-Planer:** Route-Planung und Fahrer-Zuweisung
- **KPI-Dashboard:** Pünktlichkeitsrate, Schadensquote, Last-Mile-Effizienz

### Ausbaustufen

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | Basis-Template: Speditions-Profil, Service-Übersicht | 🔴 Kritisch |
| Stufe 2 | Tracking-Status-Stepper (CSS-Step-Indicator) | 🟠 High |
| Stufe 3 | Kapazitäts-Buchungs-Formular (Paletten, m³, Gewicht) | 🟠 High |
| Stufe 4 | Live-Karte mit Fahrzeug-Markern | 🟡 Mittel |
| Stufe 5 | Fahrer-Portal (vereinfachte Mobile-App-artige Ansicht) | 🟡 Mittel |
| Stufe 6 | API-Anbindung an Telematik-Systeme (Daten-Import) | 🟢 Low |

---

## Theme 5: **MedCare Pro** (Gesundheitswesen & Medizin)

### Branchen-Fokus: Arztpraxen, Kliniken, Therapeuten, Pflegedienste, Medizintechnik
**Plugin-Empfehlung:** cms-bookings, cms-experts, cms-directory, cms-helpdesk, cms-forms

### Design-Philosophie
> Vertrauensvoll, beruhigend, klar. Gesundheits-Design muss Sicherheit und Professionalität ausstrahlen. Weiche Farben, viel Weißraum und klare Hierarchien senken Schwellenhemmungen.

### Farb-System
```css
:root {
  --color-primary: #0ea5e9;          /* Helles Blau (Reinheit, Vertrauen) */
  --color-primary-dark: #0284c7;
  --color-secondary: #10b981;        /* Emerald (Gesundheit, Natur) */
  --color-accent: #f0fdf4;           /* Mint-Hauch (Ruhe) */
  --color-warning: #f59e0b;
  --color-danger: #ef4444;
  --color-surface: #f0f9ff;
  --color-text: #1e3a5f;             /* Dunkelblau (Seriös statt Schwarz) */

  /* Branchenspezifische Farben */
  --color-allgemein: #0ea5e9;
  --color-chirurgie: #ef4444;
  --color-psychologie: #8b5cf6;
  --color-zahnmedizin: #f59e0b;
}
```

### Typografie
| Rolle | Font | Begründung |
|---|---|---|
| Display | Libre Baskerville | Autorität, Seriosität |
| Headings | Source Sans Pro | Klar, gut lesbar |
| Body | Open Sans | Maximale Lesbarkeit |
| Hinweise | Source Serif Pro | Vertrauenswürdig bei med. Texten |

### DSGVO & Medical Compliance Overrides
- Kein Google Analytics ohne explizite Einwilligung
- Keine Social-Sharing-Buttons (Patientendaten-Schutz)
- Alle Formulare: Pflichthinweis auf Datenschutz
- Cookie-Banner: Medizinischer Standard (nur technisch notwendige Cookies default)

### Spezielle Komponenten
- **Arzt-Profil-Card:** Foto, Fachgebiet-Tag, Kassenärztl. Zulassung-Badge
- **Terminbuchungs-Widget:** Kalender-basiert, Fachbereich-Auswahl
- **Praxis-Finder:** Standort + Fachgebiet + Kassenart Filter
- **Notfall-Banner:** Prominente Notfallnummer-Anzeige (konfigurierbar)
- **Leistungs-Übersicht:** Strukturierte Tabellenansicht (wie GOÄ-Ziffern)

### Ausbaustufen

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | Basis-Template: Praxis-Profil, Terminbuchung | 🔴 Kritisch |
| Stufe 2 | Online-Terminbuchung mit Echtzeit-Verfügbarkeit | 🔴 Kritisch |
| Stufe 3 | DSGVO-Medical-Mode (verschärfte Datenschutz-Defaults) | 🔴 Kritisch |
| Stufe 4 | Barrierefreiheits-Modus (Schriftgröße, Kontrast, Vorlesefunktion) | 🟠 High |
| Stufe 5 | Symptom-Checker als interaktives Tool | 🟡 Mittel |
| Stufe 6 | Patienten-Portal (Befunde, Termine, Nachrichten) | 🟢 Low |

---

## Theme 6: **Academy365** (Bildung & Weiterbildung)

### Branchen-Fokus: Weiterbildungsinstitute, Hochschulen, E-Learning, Coaching-Anbieter
**Plugin-Empfehlung:** cms-learning, cms-events, cms-speakers, cms-subscriptions, cms-certificates

### Design-Philosophie
> Inspirierend, strukturiert, zugänglich. Bildungs-Design soll Neugier wecken und Orientierung bieten. Klare Navigation, motivierende Visuals und erreichbare Lernziele stehen im Vordergrund.

### Farb-System
```css
:root {
  --color-primary: #4f46e5;          /* Indigo (Intellekt, Tiefe) */
  --color-primary-dark: #4338ca;
  --color-secondary: #0ea5e9;        /* Sky Blue (Weite, Möglichkeiten) */
  --color-accent: #f59e0b;           /* Amber (Highlights, CTAs) */
  --color-success: #16a34a;          /* Kurs abgeschlossen */
  --color-progress: #8b5cf6;         /* Fortschritts-Farbe */
  --color-surface: #f5f3ff;          /* Leichter Indigo-Hauch */
  --color-text: #1e1b4b;             /* Deep Indigo */

  /* Lernpfad-Farben */
  --lp-beginner: #86efac;
  --lp-intermediate: #93c5fd;
  --lp-advanced: #c4b5fd;
  --lp-expert: #fbbf24;
}
```

### Typografie
| Rolle | Font | Charakter |
|---|---|---|
| Display | Merriweather | Akademisch, substanziell |
| Headings | Poppins | Modern, freundlich |
| Body | Lato | Lesbar bei langen Lektionen |
| Code | Fira Code | Für Coding-Kurse |
| Zitate | Merriweather Italic | Inspirationsquellen |

### Lernfortschritt-Design
```css
/* Fortschritts-Balken für Kurse */
.progress-bar {
  background: linear-gradient(90deg, 
    var(--color-progress) 0%, 
    var(--color-secondary) 100%
  );
  height: 6px;
  border-radius: var(--radius-full);
}

/* Level-Badges */
.level-badge-beginner { background: var(--lp-beginner); }
.level-badge-advanced { background: var(--lp-advanced); }
```

### Spezielle Komponenten
- **Kurs-Card:** Fortschrittsbalken, Bewertungs-Sterne, Schüler-Anzahl, Niveau-Badge
- **Dozenten-Profil:** Expertise-Bereiche, aktive Kurse, Abschlüsse
- **Lernpfad-Visualisierung:** Interaktive Baumstruktur mit Abhängigkeiten
- **Video-Player:** Angepasster Player mit Geschwindigkeitskontrolle und Lesezeichen
- **Quiz-Widget:** Timer, Feedback nach jeder Antwort, Fortschrittsanzeige
- **Zertifikat-Generator:** PDF mit Logo, QR-Code für Verifikation

### Ausbaustufen

| Stufe | Feature | Priorität |
|---|---|---|
| Stufe 1 | Basis-Template: Kurs-Katalog, Kurs-Detailseite | 🔴 Kritisch |
| Stufe 2 | Lernfortschritt-Tracking (% abgeschlossen) | 🔴 Kritisch |
| Stufe 3 | Quiz-Komponenten (Multiple Choice, Wahr/Falsch) | 🟠 High |
| Stufe 4 | Zertifikats-PDF-Generator | 🟠 High |
| Stufe 5 | Lernpfad-Visualisierung (D3.js Tree) | 🟡 Mittel |
| Stufe 6 | Note-Taking-Widget (persönliche Notizen pro Lektion) | 🟡 Mittel |
| Stufe 7 | Peer-Learning-Forum pro Kurs | 🟡 Mittel |
| Stufe 8 | KI-Tutor (Fragen im Kurskontext beantworten) | 🟢 Low |

---

## 🎨 Theme-Management & Customizer-Roadmap

### TC-01 · Theme-Customizer v2
**Priorität:** 🟠 High

| Stufe | Feature |
|---|---|
| Stufe 1 | Design-Token-Editor im Admin (Farben, Fonts, Abstände) |
| Stufe 2 | Live-Preview (Änderungen sofort sichtbar) |
| Stufe 3 | Design-Presets speichern und laden |
| Stufe 4 | Theme-Varianten (mehrere Farbschemata eines Themes) |
| Stufe 5 | CSS-Export (Download als custom.css-Datei) |
| Stufe 6 | Brand-Kit-Import (Logo + Primärfarbe → automatische Palette) |

---

### TC-02 · Child-Theme-System
**Priorität:** 🟠 High

| Stufe | Feature |
|---|---|
| Stufe 1 | Child-Theme-Ersteller im Admin |
| Stufe 2 | Sichere Überschreibung einzelner Templates |
| Stufe 3 | CSS-Override-Layer (kein Verlust bei Parent-Updates) |
| Stufe 4 | Child-Theme-Export/Import (ZIP) |

---

### TC-03 · Theme-Schaufenster (Galerie)
**Priorität:** 🟡 Mittel

| Stufe | Feature |
|---|---|
| Stufe 1 | Theme-Vorschau im Admin (Live-Preview mit Demo-Content) |
| Stufe 2 | One-Click-Demo-Content-Import pro Theme |
| Stufe 3 | Theme-Marketplace (Community-Themes) |
| Stufe 4 | Theme-Bewertungen und Reviews |

---

## Barrierefreiheit (gilt für alle Themes)

### 🔴 Basis-Standards (Pflicht)
- WCAG 2.2 AA Kontrast-Anforderungen (4.5:1 normal, 3:1 groß)
- Keyboard-Navigation (Tab-Order, Focus-Styles sichtbar)
- ARIA-Labels für alle interaktiven Elemente
- Skip-Navigation-Link (für Screen-Reader)
- Alt-Texte für alle Bilder (erzwungen durch Validierung)

### 🟠 Erweiterte Standards
- WCAG 2.2 AAA für kritische Pfade (Login, Checkout, Formulare)
- Screen-Reader-Optimierung (Announce-Regions, Live-Regions)
- Reduced-Motion-Mode (`prefers-reduced-motion`)
- High-Contrast-Mode (`prefers-contrast: high`)

### 🟡 Optionale Features
- Font-Size-Switcher (Frontend-Widget)
- Dyslexie-freundliche Font-Option (OpenDyslexic)
- Leichte-Sprache-Mode (vereinfachter Content)
- Automatischer Kontrast-Check im Page-Builder

---

*Letzte Aktualisierung: 19. Februar 2026*
