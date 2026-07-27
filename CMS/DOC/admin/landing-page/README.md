# 365CMS – Landing Page Builder
> **Stand:** 2026-04-07 | **Version:** 2.9.0 | **Status:** Aktuell

<!-- UPDATED: 2026-04-07 -->

## Überblick

Der Landing Page Builder ermöglicht die Gestaltung der öffentlichen Startseite über eine
sektionenbasierte Admin-Oberfläche unter `/admin/landing-page`. Die Speicher- und
Validierungslogik liegt im `LandingPageService`, Inhalte werden in `cms_landing_sections` persistiert.

Plugin-Overrides können einzelne Bereiche der Startseite ersetzen oder erweitern.

Die Oberfläche ist im aktuellen Stand eng mit Theme-, Header-, Hero- und Plugin-Sections verzahnt und sollte immer zusammen mit `Landing*`-Services und Theme-Ausgabe gedacht werden.

## Verfügbare Funktionen

| Tab / Funktion | Beschreibung |
|---|---|
| Header | Hero-Bereich mit Logo, Titel, Untertitel, Beschreibung und Buttons |
| Content | Inhaltsmodus mit Feature-Liste oder redaktionellem Freitext |
| Sektionen | Wiederverwendbare Inhaltsblöcke für die Startseite |
| Plugin-Overrides | Erweiterungspunkte für Plugin-gesteuerte Sektionen |
| Vorschau | Live-Vorschau der Startseite aus dem Editor |

## Benötigte Rechte

- Rolle **Admin** erforderlich

## Verwandte Dokumente

- [LANDING-PAGE.md](LANDING-PAGE.md)
- [../dashboard/README.md](../dashboard/README.md)
