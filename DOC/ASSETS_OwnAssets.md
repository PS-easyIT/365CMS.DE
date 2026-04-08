# ASSETS OwnAssets – Roadmap für Eigenersatz
> **Stand:** 2026-04-07 | **Version:** 2.9.0 | **Status:** Arbeitsliste / Strategiepapier

## Zielbild

Diese Liste beschreibt, **welche aktiven Fremd-Assets 365CMS schrittweise durch eigene Assets oder eigene Wrapper ersetzen kann**, ohne Sicherheit, Standards, Wartbarkeit oder Updatefähigkeit unnötig zu opfern.

Wichtig: Nicht jede Drittbibliothek sollte ersetzt werden. Bei sicherheitskritischen, standardnahen oder komplexen Bibliotheken ist **Kapselung statt Eigenbau** die bessere Strategie.

Bereits entfernte oder nicht mehr führende Legacy-Assets aus früheren Laufzeitpfaden sind **nicht mehr Teil dieser Ersatzliste**. Der Fokus liegt auf dem aktuell relevanten Restbestand unter `CMS/assets/`.

---

## Entscheidungslogik

| Priorität | Bedeutung |
|---|---|
| P1 | Schnell ersetzbar, geringer Folgeschaden, hoher Vereinheitlichungsgewinn |
| P2 | Mittelfristig ersetzbar, benötigt Wrapper, Migration oder schrittweisen Parallelbetrieb |
| P3 | Nur langfristig sinnvoll, hoher Aufwand oder größere funktionale Tiefe |
| P4 | Nicht ersetzen, sondern kapseln, härten und sauber dokumentieren |

---

## Aktive Kandidaten für Eigenersatz

| Asset | Status in 2.9.0 | Rolle heute | Empfehlung | Priorität | Ziel für Eigenlösung |
|---|---|---|---|---|---|
| `gridjs` | aktiv | Tabellen-/Grid-Darstellung im Admin | durch eigenes Grid-Wrapper-Modul ersetzen | P1 | `cms-grid.js` + serverseitige Config-Helper |
| `photoswipe` | aktiv | Frontend-Lightbox | durch eigene Lightbox-Komponente ablösen | P2 | `cms-lightbox.js` + schlanke CSS-Komponente |
| `melbahja-seo` | aktiv | Schema, Sitemap, SEO-Helfer | Funktionen schrittweise in Core-Services überführen | P2 | eigene `Seo*Service`-/`Sitemap*Service`-Bausteine |

---

## Empfohlene Reihenfolge

### Phase 1 – Kleine UI-Bibliotheken herauslösen

Diese Bibliotheken sind aktiv, aber funktional gut durch 365CMS-eigene UI-Bausteine ersetzbar:

1. `gridjs`
2. `photoswipe`

**Empfohlene Eigenmodule:**

- `CMS/assets/js/cms-grid.js`
- `CMS/assets/js/cms-lightbox.js`
- optionale Styles unter `CMS/assets/css/cms-grid.css` und `CMS/assets/css/cms-lightbox.css`

### Phase 2 – SEO-Abhängigkeit strategisch verkleinern

`melbahja-seo` sollte nicht in einem Schritt „hart“ entfernt werden. Sinnvoll ist eine **funktionale Zerlegung**:

- Sitemap-Erzeugung in einen eigenen Sitemap-Service verschieben
- Schema-Aufbau in eigene `Seo`-/`Schema`-Bausteine überführen
- IndexNow sauber als isolierten Service oder Adapter pflegen

---

## Detailbewertung der Kandidaten

### `gridjs`

- **Heute:** Admin-Tabellen und Grid-nahe Listen
- **Warum ersetzbar:** UI-nah, überschaubarer Scope, keine sicherheitskritische Kernbibliothek
- **Nächster Schritt:** gemeinsame Tabellenanforderungen inventarisieren (Suche, Sortierung, Pagination, Bulk-Auswahl, Empty-States)
- **Eigenlösung:** gut machbar
- **Risiko:** niedrig bis mittel
- **Zielbild:** ein 365CMS-eigenes Grid-Modul, das auf vorbereitete JSON-Konfigurationen und bestehende Admin-Stile aufsetzt

### `photoswipe`

- **Heute:** Frontend-Lightbox für Galerien
- **Warum ersetzbar:** klar abgegrenzte UI-Komponente mit begrenztem Runtime-Scope
- **Nächster Schritt:** eigenes Lightbox-Modul mit Zoom, Keyboard-Steuerung, Touch, Fokusfalle und Galerie-Navigation planen
- **Eigenlösung:** machbar
- **Risiko:** mittel
- **Zielbild:** barriereärmere 365CMS-Lightbox, die Theme-seitig leichter angepasst werden kann

### `melbahja-seo`

- **Heute:** SEO-Helfer für Schema, Sitemap und Indexing-nahe Features
- **Warum nur schrittweise:** fachlich sinnvoll, aber enger am Core und an Suchmaschinenformaten
- **Nächster Schritt:** Sitemap-, Schema- und IndexNow-Funktionen getrennt bewerten und nicht als monolithische „Alles neu“-Aktion angehen
- **Eigenlösung:** teilweise sinnvoll
- **Risiko:** mittel
- **Zielbild:** kleinere, testbare Core-Services statt generischem SEO-Helferpaket

---

## Was ausdrücklich nicht Eigenbau werden sollte

Diese Bibliotheken lieber **hinter Services kapseln**, aber **nicht selbst neu implementieren**:

- `htmlpurifier`
- `php-jwt`
- `webauthn`
- `ldaprecord`
- `mailer`
- `mime`
- `twofactorauth`

### Warum nicht?

- hohe Sicherheits- und Standardisierungsanforderungen
- schwer korrekt und vollständig nachzubauen
- hoher Pflege-, Test- und Kompatibilitätsaufwand
- Fehler in diesen Bereichen wären teurer als der Nutzen eines Eigenbaus

---

## Empfohlenes Vorgehen pro Ersatz

Jeder künftige Ersatz sollte in derselben Reihenfolge umgesetzt werden:

1. **Wrapper definieren** – bestehenden Einsatz über eine kleine interne Schnittstelle kapseln
2. **Nativen Ersatz parallel einführen** – zunächst nur in klar abgegrenzten Pfaden
3. **Feature-Lücken schließen** – UX, Accessibility und Edge-Cases angleichen
4. **Altpfad entkoppeln** – alte Bibliothek nur noch fallback-artig halten
5. **Dokumentation aktualisieren** – `DOC/ASSET.md`, `DOC/assets/README.md`, Changelog und diese Datei synchron halten

---

## Kurzfazit

Der schnellste Gewinn liegt **nicht** im Ersetzen aller Drittbibliotheken, sondern im gezielten Abbau kleiner UI-Abhängigkeiten und in der sauberen Kapselung komplexer Sicherheits- oder Standardbibliotheken.

Die beste Reihenfolge lautet daher:

1. kleine UI-Bibliotheken wie `gridjs` und `photoswipe` durch 365CMS-eigene Module ersetzen
2. `melbahja-seo` funktional in kleinere Core-Services aufteilen
3. komplexe Sicherheits- und Infrastruktur-Bibliotheken bewusst behalten und nur abstrahieren

Wichtig für den aktuellen Dokumentationsstand: Die führende Runtime- und Strukturreferenz für aktive Asset-Pfade liegt jetzt zusätzlich in `DOC/FILELIST.md` und `DOC/assets/README.md`.