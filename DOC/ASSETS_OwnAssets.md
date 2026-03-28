# ASSETS OwnAssets – Roadmap für Eigenersatz
> **Stand:** 2026-03-28 | **Version:** 2.8.0 RC | **Status:** Arbeitsliste / Strategiepapier

## Zielbild

Diese Liste beschreibt, **welche Fremd-Assets 365CMS schrittweise durch eigene Assets oder eigene Wrapper ersetzen kann**, ohne unnötig Sicherheit, Standards oder Wartbarkeit zu opfern.

Wichtig: Nicht jedes Dritt-Asset sollte ersetzt werden. Bei sicherheitskritischen, standardnahen oder sehr komplexen Bibliotheken ist **Kapselung statt Eigenbau** die bessere Strategie.

Alt Legacy Assets sind im ordner assets bereits entfernt.

---

## Prioritätenlogik

| Priorität | Bedeutung |
|---|---|
| P1 | Schnell ersetzbar, geringe Folgeschäden, hoher Vereinheitlichungsgewinn |
| P2 | Mittelfristig ersetzbar, braucht Wrapper/Migrationspfad |
| P3 | Nur langfristig sinnvoll, hoher Aufwand |
| P4 | Nicht ersetzen, sondern kapseln und sauber dokumentieren |

---

## Konkrete Ersatzliste

| Asset | Status in 2.8.0 RC | Empfehlung | Priorität | Ziel für Eigenlösung |
|---|---|---|---|---|
| `photoswipe` | aktiv | mittelfristig durch eigene Lightbox ersetzbar | P2 | `cms-lightbox.js` + schlanke CSS-Komponente |
| `melbahja-seo` | aktiv | einzelne Funktionen nativ übernehmen | P2 | eigener Sitemap-/Schema-/IndexNow-Service |

---

## Empfohlene Reihenfolge

### Phase 1 – UI-Schicht entkoppeln

Diese Assets sind aktiv, aber funktional gut durch eigene kleinere UI-Bausteine ersetzbar:

1. `photoswipe`
2. `gridjs`

**Empfohlene Eigenmodule:**

- `CMS/assets/js/cms-lightbox.js`
- `CMS/assets/js/cms-grid.js`

### Phase 3 – SEO u strategisch reduzieren

- `melbahja-seo`: einzelne Features nativ in `Seo*Service` verschieben

---

### 5. `photoswipe`

- **Heute:** Frontend-Lightbox aktiv
- **Nächster Schritt:** eigenes Lightbox-Modul mit Zoom, Keyboard, Touch, Fokusfalle und Galerie-Navigation planen
- **Eigenlösung:** machbar
- **Risiko:** mittel

### 8. `melbahja-seo`

- **Heute:** SEO-Helfer aktiv
- **Nächster Schritt:** Sitemap-, Schema- und IndexNow-Features getrennt bewerten
- **Eigenlösung:** teils sinnvoll
- **Risiko:** mittel

---

## Was ausdrücklich nicht Eigenbau werden sollte

Diese Bibliotheken lieber **hinter Services kapseln**, aber nicht selbst implementieren:

- `htmlpurifier`
- `php-jwt`
- `webauthn`
- `ldaprecord`
- `mailer`
- `mime`
- `twofactorauth`

Begründung:

- hohe Sicherheits- und Standardisierungsanforderungen
- schwer korrekt nachzubauen
- externer Pflege- und Testaufwand lohnt sich hier eher als Eigenentwicklung


## Kurzfazit

Der schnellste Gewinn liegt **nicht** im Ersetzen aller Drittbibliotheken, sondern im konsequenten Abschluss der bereits begonnenen Ablösungen (`cookieconsent`, `simplepie`, `filepond`, `elfinder`) und in einer sauberen Kapselung der großen verbleibenden Vendoren.

Die beste Reihenfolge lautet deshalb:

1. Legacy-Bundles endgültig auslaufen lassen
2. kleine UI-Bibliotheken durch 365CMS-eigene Module ersetzen
3. komplexe Sicherheits-/Standard-Bibliotheken bewusst behalten und nur abstrahieren