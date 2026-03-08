# elFinder

## Kurzbeschreibung

`elFinder` ist der browserbasierte Dateimanager der Admin-Medienverwaltung in 365CMS.

## Quellordner

- `CMS/assets/elfinder/`
- `CMS/assets/elfinder/vendor/jquery/`
- `CMS/assets/elfinder/vendor/jquery-ui/`

## Verwendung in 365CMS

- abgesicherter Connector in `CMS/core/Services/ElfinderService.php`
- Admin-Routen `GET` und `POST /api/v1/admin/media/elfinder` in `CMS/core/Router.php`
- CSS-/JS-Einbindung in `CMS/admin/media.php`
- Initialisierung im Medien-View `CMS/admin/views/media/library.php`
- Browser-Bootstrap in `CMS/assets/js/admin-media-integrations.js`
- Klassen-Autoload für `elFinder*` über `CMS/assets/autoload.php`

## Besondere Hinweise

- Der Betrieb ist **CDN-frei**; die benötigten Frontend-Abhängigkeiten `jQuery` und `jQuery UI` werden lokal aus `CMS/assets/elfinder/vendor/` geladen.
- Der Connector arbeitet ausschließlich im Admin-Kontext und prüft `media_action`-CSRF-Tokens.
- Laufzeitordner für Thumbnails und temporäre Dateien werden unter `UPLOAD_PATH/.elfinder/` erzeugt.

## Website / GitHub

- Website: https://studio-42.github.io/elFinder/
- GitHub: https://github.com/Studio-42/elFinder