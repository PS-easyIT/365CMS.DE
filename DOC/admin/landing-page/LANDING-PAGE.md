# Landing Page Editor

Kurzbeschreibung: Dokumentiert den aktuellen Landing-Page-Editor im Admin inklusive Sektionen, Datenmodell, Plugin-Overrides und Sicherheitslogik.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

## Überblick

Die Landing Page wird im aktuellen System über die Route `/admin/landing-page` verwaltet. Die Oberfläche basiert auf `CMS/admin/landing-page.php`, während die komplette Lade-, Speicher- und Validierungslogik im `CMS\core\Services\LandingPageService` gekapselt ist.

Die Implementierung ist sektionenbasiert und speichert Inhalte in `cms_landing_sections`. Zusätzlich unterstützt sie Plugin-Overrides für einzelne Bereiche der Startseite.

## Verfügbare Tabs

Die Admin-Seite verarbeitet aktuell den Parameter `tab` mit folgenden Bereichen:

| Tab | Zweck |
|---|---|
| `header` | Hero-Bereich, Logo, Titel, Untertitel, Beschreibung, Buttons |
| `content` | Inhaltsmodus, Feature-Liste oder redaktioneller Freitext |
| `footer` | Footer-Text, CTA und Copyright |
| `design` | Farben und Design-Tokens für die Landing Page |
| `plugins` | Plugin-Overrides für Header, Content und Footer |

Ältere Doku-Stände mit zusätzlichen Unterpunkten wie Testimonials, Statistiken oder separaten Settings-Tabs entsprechen nicht mehr der aktuellen Produktlogik.

## Datenmodell

Die Service-Schicht arbeitet mit Einträgen in `cms_landing_sections`. Relevante `type`-Werte sind insbesondere:

- `header`
- `content`
- `feature`
- `footer`
- `design`
- `plugin_overrides`

Zusätzliche globale Einstellungen wie `landing_slug` und `maintenance_mode` werden über die normalen Settings gespeichert.

## Header

Der Header-Bereich erlaubt die Pflege der zentralen Hero-Daten der Startseite. Dazu gehören unter anderem:

- Titel und Untertitel
- beschreibender Einleitungstext
- Badge- oder Versionshinweis
- Logo inklusive Position und Layout
- bis zu vier Call-to-Action-Buttons

Jeder Button wird serverseitig normalisiert. Relevante Felder sind `text`, `url`, `icon`, `target` und `outline`. Ungültige Werte werden im Service gefiltert oder auf zulässige Defaults zurückgeführt.

## Content

Die Content-Sektion unterstützt drei Betriebsarten:

| Modus | Beschreibung |
|---|---|
| `features` | Raster aus einzelnen Feature-Karten |
| `text` | Freier HTML-/Editor-Inhalt |
| `posts` | Ausgabe aktueller Beiträge |

Feature-Karten werden einzeln gespeichert und typischerweise mit Icon, Titel, Beschreibung und Sortierung verwaltet. Das Anlegen, Bearbeiten und Löschen läuft über dedizierte POST-Aktionen wie `save_feature` und `delete_feature`.

## Footer

Im Footer-Bereich werden folgende Elemente gepflegt:

- Sichtbarkeit der Sektion
- redaktioneller Text
- CTA-Button mit Text und URL
- Copyright-Zeile

Im Copyright-Text kann `{year}` als Platzhalter verwendet werden.

## Design

Die Design-Sektion verwaltet sowohl Farbwerte als auch UI-Tokens. Typische Schlüssel sind:

- `hero_gradient_start`
- `hero_gradient_end`
- `hero_border`
- `hero_text`
- `features_bg`
- `feature_card_bg`
- `primary_button`
- `card_border_radius`
- `button_border_radius`
- `feature_columns`
- `hero_padding`
- `feature_padding`
- `footer_bg`
- `footer_text_color`

Die Werte werden im Service serverseitig validiert. Dazu gehören Hex-Farben, erlaubte Enumerationen und numerische Bereiche für Abstände oder Darstellungsmodi.

## Plugin-Overrides

Die Landing Page unterstützt alternative Ausgaben durch Plugins. Unterstützte Zielbereiche sind aktuell:

- `header`
- `content`
- `footer`

Plugins registrieren sich über den Filter `landing_page_plugins`. Ein Plugin-Eintrag beschreibt mindestens Kennung, Namen, unterstützte Zielbereiche und optional eine Callback-Funktion für eigene Einstellungen.

Gespeichert wird pro Zielbereich, welches Plugin aktiv ist. Zusätzlich können plugin-spezifische Einstellungen als `plugin_settings` abgelegt werden. Vor dem Speichern prüft der Service, ob:

- das Plugin tatsächlich registriert wurde,
- der Zielbereich unterstützt wird,
- optionale Callbacks aufrufbar sind.

## POST-Aktionen

`CMS/admin/landing-page.php` verarbeitet derzeit insbesondere diese Aktionen:

- `save_header`
- `save_content`
- `save_footer`
- `save_design`
- `save_feature`
- `delete_feature`
- `save_plugin`

Alle Aktionen verwenden denselben CSRF-Kontext `admin_landing_page`.

## Sicherheit

Die Seite folgt dem aktuellen Admin-Standard:

- Zugriff nur für Administratoren über `Auth::instance()->isAdmin()`
- CSRF-Prüfung via `Security::instance()->verifyToken(..., 'admin_landing_page')`
- serverseitige Sanitierung sämtlicher Texte, URLs, Farbcodes, Slugs und Plugin-Konfigurationen
- Redirect nach erfolgreichem POST mit Session-basierten Statusmeldungen

## Relevante Dateien

| Datei | Zweck |
|---|---|
| `CMS/admin/landing-page.php` | Admin-Entry-Point und POST-Dispatch |
| `CMS/core/Services/LandingPageService.php` | Laden, Validieren und Persistieren der Landing-Page-Daten |
| `CMS/admin/views/landing-page/*` | Ausgabe der einzelnen Admin-Abschnitte |

## Verwandte Dokumente

- [Admin-Übersicht](../README.md)
- [Themes & Design](../themes-design/README.md)
- [Plugin-Verwaltung](../plugins/PLUGINS.md)
