# Landing Page Editor

Der Landing-Page-Editor steuert die öffentliche Startseite des CMS über den Admin-Bereich `admin/landing-page.php`.
Die aktuelle Implementierung arbeitet **sektionenbasiert über die Tabelle `cms_landing_sections`** und bietet zusätzlich ein **Plugin-Override-System** für Header, Content und Footer.

---

## Überblick

**URL:** `/admin/landing-page.php`

Verfügbare Admin-Sektionen:

| Sektion | Route | Zweck |
|---|---|---|
| Header | `/admin/landing-page?section=header` | Hero-Bereich, Logo, Titel, Buttons |
| Content | `/admin/landing-page?section=content` | Feature-Grid, Freitext oder letzte Beiträge |
| Footer | `/admin/landing-page?section=footer` | Footer-Text, CTA, Copyright |
| Design | `/admin/landing-page?section=design` | Farben und Design-Tokens |
| Plugins | `/admin/landing-page?section=plugins` | Plugin-Overrides pro Bereich |
| Einstellungen | `/admin/landing-page?section=settings` | Sichtbarkeit, Slug, Wartungsmodus |

Die Seite nutzt das zentrale Admin-Layout über `renderAdminLayoutStart()` / `renderAdminLayoutEnd()`.
Statusmeldungen und Bestätigungen laufen über die gemeinsamen Admin-Komponenten (`renderAdminAlerts()`, `cmsConfirm()`).

---

## Datenmodell

Die Landing Page speichert ihre Daten in `cms_landing_sections`.

Typische `type`-Werte:

- `header`
- `feature`
- `footer`
- `content`
- `settings`
- `design`
- `plugin_overrides`

Zentrale Service-Klasse:

- `CMS/core/Services/LandingPageService.php`

Admin-Datei:

- `CMS/admin/landing-page.php`

---

## Header

Der Header-Bereich enthält:

- Logo-Upload / Logo-Entfernung
- Logo-Position (`top`, `left`)
- Header-Layout (`standard`, `compact`)
- Titel
- Untertitel
- Beschreibung (Editor)
- Versions-/Badge-Text
- bis zu 4 Header-Buttons

### Header-Buttons

Pro Button werden gespeichert:

- `text`
- `url`
- `icon`
- `target` (`_self`, `_blank`)
- `outline`

Serverseitig werden diese Werte im `LandingPageService` validiert und bereinigt.

---

## Content

Die Content-Sektion unterstützt aktuell drei Modi:

| Modus | Beschreibung |
|---|---|
| `features` | Feature-/Widget-Grid |
| `text` | Freier HTML-/Editor-Inhalt |
| `posts` | Neueste Beiträge |

### Feature-Grid

Features werden einzeln in `cms_landing_sections` mit `type = 'feature'` gespeichert.

Pro Feature:

- `icon`
- `title`
- `description`
- `sort_order`

Das Löschen erfolgt im Admin über das zentrale Bestätigungsmodal, nicht über `window.confirm()`.

---

## Footer

Der Footer-Bereich bietet:

- Sichtbarkeit ein/aus
- Freitext-Inhalt
- CTA-Button (`button_text`, `button_url`)
- Copyright-Zeile

Hinweis:

- `{year}` kann im Copyright-Text als Platzhalter verwendet werden.

---

## Design

Die Design-Sektion ist in zwei Bereiche aufgeteilt:

### Farben

Gespeicherte Farbwerte:

- `hero_gradient_start`
- `hero_gradient_end`
- `hero_border`
- `hero_text`
- `features_bg`
- `feature_card_bg`
- `feature_card_hover`
- `primary_button`

### Design-Tokens

Gespeicherte Token u. a.:

- `card_border_radius`
- `button_border_radius`
- `card_icon_layout`
- `card_border_color`
- `card_border_width`
- `card_shadow`
- `feature_columns`
- `hero_padding`
- `feature_padding`
- `footer_bg`
- `footer_text_color`
- `content_section_bg`

Die Werte werden serverseitig auf erlaubte Bereiche, Farben und Enumerationen validiert.

---

## Plugins / Overrides

Die Plugins-Sektion erlaubt, die CMS-Standardbereiche durch Plugin-Ausgaben zu ersetzen.

Unterstützte Zielbereiche:

- `header`
- `content`
- `footer`

### Plugin-Registrierung

Plugins registrieren sich über:

```php
CMS\Hooks::addFilter('landing_page_plugins', function (array $plugins): array {
    $plugins['my-plugin'] = [
        'id' => 'my-plugin',
        'name' => 'Mein Plugin',
        'description' => 'Ersetzt den Content-Bereich der Landing Page.',
        'version' => '1.0.0',
        'author' => '365 Network',
        'targets' => ['content'],
        'settings_callback' => [$this, 'render_settings'],
    ];

    return $plugins;
});
```

### Gespeicherte Override-Daten

`type = 'plugin_overrides'` enthält:

- aktives Plugin je Bereich (`header`, `content`, `footer`)
- plugin-spezifische Einstellungen in `plugin_settings`

Der Service prüft serverseitig:

- ob das Plugin tatsächlich registriert ist
- ob der gewünschte Zielbereich unterstützt wird
- ob eine `settings_callback` wirklich aufrufbar ist

---

## Einstellungen

Die Einstellungs-Sektion verwaltet:

- Sichtbarkeit von Header, Content und Footer-Sektion
- `landing_slug`
- `maintenance_mode`

### Slug-Regeln

Der Slug wird serverseitig normalisiert:

- leer oder `/` = Root-Startseite
- sonst normalisierte Pfadangabe wie z. B. `/start`

---

## Sicherheit und Validierung

Die Landing-Page-Verwaltung nutzt:

- Admin-Check via `Auth::instance()->isAdmin()`
- CSRF-Schutz via `Security::instance()->verifyToken()`
- serverseitige Sanitierung im `LandingPageService`
- zentrale Admin-Alerts und Admin-Confirm-Modal

Serverseitig bereinigt werden unter anderem:

- Texte
- URLs
- Farbcodes
- Slugs
- Button-Konfigurationen
- Plugin-IDs und Plugin-Settings

---

## Wichtige Dateien

| Datei | Zweck |
|---|---|
| `CMS/admin/landing-page.php` | Admin-Oberfläche |
| `CMS/core/Services/LandingPageService.php` | Laden/Speichern/Validieren |
| `CMS/admin/partials/admin-menu.php` | Layout, Sidebar, Alerts, Confirm-Modal |

---

## Aktueller Stand

Die aktuelle Implementierung deckt die real vorhandenen Bereiche im Admin ab und ist nicht mehr identisch mit älteren Konzepten wie Testimonials-, CTA- oder Statistik-Sektionen aus früheren Planungsständen.
Für neue Landing-Page-Module sollte zuerst geprüft werden, ob sie als echte `landing_sections`-Typen oder als Plugin-Overrides umgesetzt werden sollen.
