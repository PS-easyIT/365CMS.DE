# 365CMS – Landing Page Builder
> **Stand:** 2026-03-08 | **Version:** 2.5.4 | **Status:** Aktuell

<!-- ADDED: 2026-03-08 -->

## Überblick

Der Landing Page Builder ermöglicht die Gestaltung der öffentlichen Startseite über eine
sektionenbasierte Admin-Oberfläche unter `/admin/landing-page`. Die Speicher- und
Validierungslogik liegt im `LandingPageService`, Inhalte werden in `cms_landing_sections` persistiert.

Plugin-Overrides können einzelne Bereiche der Startseite ersetzen oder erweitern.

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
