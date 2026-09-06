> **Website:** [365CMS.DE](https://365cms.de/) | **Version:** 3.4.00
> **Datum:** 2026-09-06 | **Status:** Abgeschlossen – **Zuletzt aktualisiert am:** 2026-09-06
> **Kurzbeschreibung:** Complete development reference for 365CMS plugins, covering lifecycle, hooks, admin and member integration, persistence, security, assets, routing, testing, and release quality.

# 365CMS Plugin Development

## English

### User-friendly guide

365CMS plugins are self-contained feature packages. They run from `CMS/plugins/<slug>/`, register behavior through the CMS hook system, expose only the pages and data they need, and must keep their own presentation assets namespaced.

#### Recommended plugin structure

```text
CMS/plugins/my-plugin/
├── my-plugin.php
├── includes/
│   ├── class-plugin.php
│   ├── class-admin.php
│   └── class-member.php
├── admin/
│   └── page.php
├── templates/
│   ├── frontend.php
│   └── member-widget.php
└── assets/
    ├── css/my-plugin.css
    └── js/my-plugin.js
```

Keep the bootstrap small. Load classes from the plugin directory, initialize one clear runtime entry point, and register hooks from that entry point. Do not copy core classes or depend on another plugin without checking that it is active.

#### User-visible integration

Use `/admin/plugins` to activate or deactivate the plugin. A plugin admin page should be reachable below `/admin/plugins/<plugin>/<page>` after registering a top-level menu and optional submenus. Member features should be exposed through a capability-aware menu item or dashboard widget and should require an authenticated member.

Use templates for HTML, the central admin shell for admin pages, and plugin-prefixed CSS such as `.my-plugin-card`. Do not use generic classes such as `.card` for plugin-specific styling, inline styles, inline scripts, or unescaped request values.

#### Safe form workflow

1. Render a persistent CSRF token with the action-specific token name.
2. Accept state changes only through POST.
3. Verify authentication, capability, token, and normalized input on the server.
4. Persist through a service or repository and return an explicit success or error.
5. Redirect after a successful admin POST and show a flash message.

#### Database and migrations

Create plugin tables idempotently with `InnoDB`, `utf8mb4`, and the runtime database prefix. Add indexes for frequent filters. Store structured values as JSON in `TEXT` or `LONGTEXT` and handle encoding errors explicitly. Migrations must be repeatable and must not assume a hard-coded `cms_` prefix.

### Technical reference

#### Bootstrap and lifecycle

Every PHP file starts with `declare(strict_types=1)` and an `ABSPATH` guard. A plugin bootstrap normally:

1. declares the CMS plugin header;
2. defines version, path, and optional URL constants;
3. requires the plugin classes;
4. creates the singleton or equivalent bootstrap object.

The runtime emits `cms_init`, plugin-specific hooks, `cms_admin_menu`, `plugin_loaded`, and `plugins_loaded` through `CMS\Hooks`. A plugin should register its own hooks from `cms_init` or its constructor and avoid work at file load time except safe definitions and requires.

#### Core hooks used by plugins

| Hook | Type | Use |
|---|---|---|
| `cms_init` | action | initialize runtime services and idempotent migrations |
| `cms_admin_menu` | action | register admin menus and submenus |
| `head` | action | add approved head-level output or assets |
| `after_header` | action | add early frontend content |
| `before_footer` | action | add footer-area output |
| `body_end` | action | add scripts or modals through the asset policy |
| `plugin_loaded` | action | react to a plugin that loaded successfully |
| `plugins_loaded` | action | run work that requires all active plugins |
| `member_menu_items` | filter | add a member navigation item |
| `member_dashboard_widgets` | filter | register a member dashboard widget |

The canonical hook names and signatures are maintained in the Core hook documentation. Do not invent a second hook bus.

#### Admin menu and routing contract

Use `add_menu_page()` for a top-level menu and `add_submenu_page()` for child pages. Both helpers store capability, title, slug, callback, and menu relationships in the central registry. The admin router resolves:

- `/admin/plugins/:plugin/:page` for registered plugin pages;
- the plugin callback or a compatibility fallback;
- normal and AJAX output paths;
- the central shell when the callback does not provide a complete layout.

Admin callbacks should render content only. They should use `renderAdminLayoutStart()` and `renderAdminLayoutEnd()` only when the compatibility path requires it, because the router already supplies the current shell for ordinary plugin callbacks.

#### Example: protected settings page

```php
public function renderSettingsPage(): void
{
    $security = \CMS\Security::instance();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!$security->verifyPersistentToken(
            (string) ($_POST['csrf_token'] ?? ''),
            'my_plugin_settings'
        )) {
            $this->addError('Security validation failed.');
        } elseif (!\CMS\Auth::instance()->hasCapability('manage_settings')) {
            $this->addError('Permission denied.');
        } else {
            $value = sanitize_text_field((string) ($_POST['my_setting'] ?? ''));
            $this->settings->save('my_plugin.setting', $value);
            $this->redirectWithNotice('Saved.');
        }
    }

    $csrfToken = $security->generatePersistentToken('my_plugin_settings');
    include MY_PLUGIN_PATH . 'admin/page.php';
}
```

The exact helper used by an existing module must match the current `CMS\Security` contract. Never rely on hidden fields alone; authorization and CSRF are both server-side checks.

#### Member integration

Register member navigation through `member_menu_items` and widgets through `member_dashboard_widgets`. Member routes are resolved by `MemberController`, which enforces authentication, private cache headers, plugin slug normalization, and template overrides. A plugin must not expose a member page as a public route accidentally.

Use the plugin dashboard registry when the plugin needs structured member tabs or settings. Restrict user data by the current member ID, escape output, and use a plugin-specific capability for privileged operations.

#### Persistence and SQL

Use `\CMS\Database::instance()` and its prepared statement API:

```php
$db = \CMS\Database::instance();
$prefix = $db->getPrefix();
$statement = $db->prepare(
    "SELECT id, status FROM {$prefix}my_plugin_items WHERE user_id = ?"
);
$statement->execute([$userId]);
$rows = $statement->fetchAll() ?: [];
```

Only trusted, internally generated identifiers may appear in interpolated SQL. Values always use bindings. Use `getPrefix()` rather than hard-coded table names, add indexes for `user_id` and status fields, and use an explicit schema version for migrations.

#### REST, AJAX, and routes

Expose an endpoint only when the feature requires it. Use the core router or the established CMS endpoint registration, restrict methods, require authentication for member data, require a capability for admin data, and validate CSRF for browser state changes. Return explicit JSON errors with appropriate status codes. Never use an unrestricted public permission callback for private plugin data.

#### Assets and templates

Use plugin-specific CSS and JavaScript files. Register or enqueue them through the current CMS asset loader so cache versions and CSP rules remain consistent. Keep JavaScript external and use `data-*` configuration rather than inline scripts. Resolve template paths from the plugin directory and provide theme overrides only through the documented template-loader contract.

#### Security and error handling

- guard every direct PHP entry point;
- sanitize text, email, URL, and HTML according to the input type;
- escape text, attributes, URLs, and allowed HTML at output;
- verify capability and CSRF before every write;
- use rate limiting for public or expensive endpoints;
- do not log secrets, tokens, raw credentials, or unnecessary personal data;
- return explicit errors rather than silent success fallbacks;
- keep optional integrations fail-closed when their dependency is disabled.

#### Test and release gate

Before release, run syntax checks and the existing project test or quality commands, test activation/deactivation and missing-file behavior, exercise admin and member authorization, test invalid input and CSRF rejection, inspect database migrations on an empty and an existing installation, verify asset loading and CSP compatibility, and update the plugin's documentation, changelog, and version metadata.

## Deutsch

### Anwenderleitfaden

365CMS-Plugins sind eigenständige Funktionspakete. Sie laufen unter `CMS/plugins/<slug>/`, registrieren Verhalten über das CMS-Hook-System, stellen nur die benötigten Seiten und Daten bereit und kapseln ihre Darstellungs-Assets mit eigenen Präfixen.

#### Empfohlene Plugin-Struktur

```text
CMS/plugins/my-plugin/
├── my-plugin.php
├── includes/
│   ├── class-plugin.php
│   ├── class-admin.php
│   └── class-member.php
├── admin/
│   └── page.php
├── templates/
│   ├── frontend.php
│   └── member-widget.php
└── assets/
    ├── css/my-plugin.css
    └── js/my-plugin.js
```

Halten Sie die Bootstrap-Datei klein. Laden Sie Klassen aus dem Plugin-Ordner, verwenden Sie einen klaren Runtime-Einstieg und registrieren Sie Hooks dort. Kopieren Sie keine Core-Klassen und prüfen Sie vor einer Abhängigkeit, ob das andere Plugin aktiv ist.

#### Sichtbare Integration

Über `/admin/plugins` wird das Plugin aktiviert oder deaktiviert. Eine Plugin-Adminseite liegt nach der Registrierung eines Hauptmenüs und optionaler Untermenüs unter `/admin/plugins/<plugin>/<page>`. Member-Funktionen werden über einen berechtigungsgeprüften Menüeintrag oder ein Dashboard-Widget angeboten und verlangen einen angemeldeten Member.

Verwenden Sie Templates für HTML, die zentrale Admin-Shell für Adminseiten und Plugin-Präfixe wie `.my-plugin-card` für CSS. Generische Klassen wie `.card` für Plugin-Styles, Inline-Styles, Inline-Scripts und ungeescapte Request-Werte sind unzulässig.

#### Sicherer Formularablauf

1. Ein persistentes, aktionsbezogenes CSRF-Token ausgeben.
2. Zustandsänderungen ausschließlich per POST akzeptieren.
3. Serverseitig Anmeldung, Capability, Token und normalisierte Eingaben prüfen.
4. Über Service oder Repository speichern und expliziten Erfolg oder Fehler liefern.
5. Nach erfolgreichem Admin-POST weiterleiten und eine Flash-Meldung ausgeben.

#### Datenbank und Migrationen

Plugin-Tabellen idempotent mit `InnoDB`, `utf8mb4` und dem Runtime-Datenbankpräfix anlegen. Für häufige Filter werden Indizes angelegt. Strukturwerte liegen als JSON in `TEXT` oder `LONGTEXT`; Kodierungsfehler werden explizit behandelt. Migrationen sind wiederholbar und verwenden niemals ein fest codiertes `cms_`-Präfix.

### Technische Referenz

#### Bootstrap und Lebenszyklus

Jede PHP-Datei beginnt mit `declare(strict_types=1)` und einem `ABSPATH`-Guard. Ein Plugin-Bootstrap:

1. definiert den CMS-Plugin-Header;
2. definiert Versions-, Pfad- und optionale URL-Konstanten;
3. lädt die Plugin-Klassen;
4. erzeugt Singleton oder gleichwertigen Bootstrap.

Die Runtime löst `cms_init`, Plugin-Hooks, `cms_admin_menu`, `plugin_loaded` und `plugins_loaded` über `CMS\Hooks` aus. Plugins registrieren eigene Hooks aus `cms_init` oder dem Konstruktor und vermeiden Logik beim Dateiladen, außer sicheren Definitionen und `require`s.

#### Core-Hooks für Plugins

| Hook | Typ | Verwendung |
|---|---|---|
| `cms_init` | Action | Runtime-Services und idempotente Migrationen initialisieren |
| `cms_admin_menu` | Action | Admin-Menüs und Untermenüs registrieren |
| `head` | Action | freigegebene Head-Ausgaben oder Assets ergänzen |
| `after_header` | Action | frühen Frontend-Inhalt ergänzen |
| `before_footer` | Action | Ausgaben im Footer-Bereich ergänzen |
| `body_end` | Action | Scripts oder Modals über die Asset-Regeln ergänzen |
| `plugin_loaded` | Action | auf erfolgreich geladenes Plugin reagieren |
| `plugins_loaded` | Action | von allen aktiven Plugins abhängige Logik ausführen |
| `member_menu_items` | Filter | Member-Navigation erweitern |
| `member_dashboard_widgets` | Filter | Member-Dashboard-Widget registrieren |

Die kanonischen Hook-Namen und Signaturen stehen in der Core-Hook-Dokumentation. Es wird kein zweiter Hook-Bus eingeführt.

#### Admin-Menü und Routing

Für ein Hauptmenü wird `add_menu_page()`, für Unterseiten `add_submenu_page()` verwendet. Beide Helfer speichern Capability, Titel, Slug, Callback und Menübeziehungen in der zentralen Registry. Der AdminRouter löst auf:

- `/admin/plugins/:plugin/:page` für registrierte Pluginseiten;
- Plugin-Callback oder Kompatibilitätsfallback;
- normale und AJAX-Ausgabewege;
- zentrale Shell, wenn der Callback kein vollständiges Layout liefert.

Admin-Callbacks rendern ausschließlich Inhalt. `renderAdminLayoutStart()` und `renderAdminLayoutEnd()` werden nur im Kompatibilitätspfad verwendet, weil der Router bei normalen Plugin-Callbacks bereits die aktuelle Shell bereitstellt.

#### Beispiel: geschützte Settings-Seite

```php
public function renderSettingsPage(): void
{
    $security = \CMS\Security::instance();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!$security->verifyPersistentToken(
            (string) ($_POST['csrf_token'] ?? ''),
            'my_plugin_settings'
        )) {
            $this->addError('Sicherheitsprüfung fehlgeschlagen.');
        } elseif (!\CMS\Auth::instance()->hasCapability('manage_settings')) {
            $this->addError('Keine Berechtigung.');
        } else {
            $value = sanitize_text_field((string) ($_POST['my_setting'] ?? ''));
            $this->settings->save('my_plugin.setting', $value);
            $this->redirectWithNotice('Gespeichert.');
        }
    }

    $csrfToken = $security->generatePersistentToken('my_plugin_settings');
    include MY_PLUGIN_PATH . 'admin/page.php';
}
```

Die verwendete Hilfsmethode muss dem aktuellen `CMS\Security`-Vertrag entsprechen. Versteckte Felder allein schützen nicht; Autorisierung und CSRF werden beide serverseitig geprüft.

#### Member-Integration

Member-Navigation wird über `member_menu_items`, Widgets über `member_dashboard_widgets` registriert. `MemberController` erzwingt Authentifizierung, private Cache-Header, Plugin-Slug-Normalisierung und Template-Overrides. Eine Memberseite darf nicht unbeabsichtigt öffentlich erreichbar sein.

Für strukturierte Member-Tabs oder Einstellungen wird die Plugin-Dashboard-Registry verwendet. Benutzerdaten werden auf die aktuelle Member-ID begrenzt, Ausgaben escaped und privilegierte Aktionen mit einer pluginbezogenen Capability geschützt.

#### Persistenz und SQL

Verwenden Sie `\CMS\Database::instance()` und die Prepared-Statement-API:

```php
$db = \CMS\Database::instance();
$prefix = $db->getPrefix();
$statement = $db->prepare(
    "SELECT id, status FROM {$prefix}my_plugin_items WHERE user_id = ?"
);
$statement->execute([$userId]);
$rows = $statement->fetchAll() ?: [];
```

Nur vertrauenswürdige, intern erzeugte Bezeichner dürfen in interpoliertem SQL erscheinen. Werte werden immer gebunden. Verwenden Sie `getPrefix()` statt harter Tabellennamen, indizieren Sie `user_id` und Statusfelder und führen Sie Migrationen mit einer expliziten Schema-Version.

#### REST, AJAX und Routen

Ein Endpoint wird nur bei fachlichem Bedarf bereitgestellt. Verwenden Sie den Core-Router oder die etablierte CMS-Registrierung, beschränken Sie Methoden, verlangen Sie Authentifizierung für Memberdaten, Capabilities für Admindaten und CSRF für browserbasierte Zustandsänderungen. JSON-Fehler enthalten klare Statuscodes. Für private Plugin-Daten wird niemals ein uneingeschränkter öffentlicher Permission-Callback verwendet.

#### Assets und Templates

Verwenden Sie pluginbezogene CSS- und JavaScript-Dateien. Binden Sie sie über den aktuellen CMS-Asset-Loader ein, damit Cache-Versionen und CSP-Regeln erhalten bleiben. JavaScript bleibt extern und erhält Konfiguration über `data-*` statt Inline-Scripts. Templatepfade werden aus dem Plugin-Ordner aufgelöst; Theme-Overrides verwenden ausschließlich den dokumentierten Template-Loader-Vertrag.

#### Sicherheit und Fehlerbehandlung

- jede direkte PHP-Datei absichern;
- Text, E-Mail, URL und HTML passend zum Eingabetyp sanitizen;
- Text, Attribute, URLs und erlaubtes HTML bei der Ausgabe escapen;
- vor jedem Schreiben Capability und CSRF prüfen;
- öffentliche oder teure Endpoints rate-limiten;
- Secrets, Tokens, Zugangsdaten und unnötige personenbezogene Daten nicht loggen;
- explizite Fehler statt stiller Erfolgs-Fallbacks zurückgeben;
- optionale Integrationen bei deaktivierter Abhängigkeit geschlossen halten.

#### Prüf- und Release-Gate

Vor dem Release Syntaxprüfungen und vorhandene Projekt-Tests beziehungsweise Quality-Gates ausführen, Aktivierung/Deaktivierung und fehlende Dateien testen, Admin- und Member-Autorisierung prüfen, ungültige Eingaben und CSRF-Ablehnung testen, Migrationen auf leerer und bestehender Installation prüfen, Asset-Laden und CSP-Kompatibilität kontrollieren und Dokumentation, Changelog und Versionsmetadaten aktualisieren.
