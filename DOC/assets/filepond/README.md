# FilePond

## Kurzbeschreibung

`FilePond` ist das aktive Upload-Frontend der Admin-Medienverwaltung und spricht in 365CMS einen kompatiblen JSON-Upload-Endpunkt an.

## Quellordner

- `CMS/assets/filepond/`

## Verwendung in 365CMS

- CSS-/JS-Einbindung in `CMS/admin/media.php`
- Upload-Feld im Medien-View `CMS/admin/views/media/library.php`
- Browser-Initialisierung in `CMS/assets/js/admin-media-integrations.js`
- Upload-Endpunkt-Service in `CMS/core/Services/FileUploadService.php`
- API-Route `POST /api/upload` in `CMS/core/Router.php`

## Besondere Hinweise

- Der Upload-Endpunkt erwartet standardmäßig das Feld `filepond` und akzeptiert alternativ `file`.
- CSRF-Schutz erfolgt über den Scope `media_action`.
- Die eigentliche Dateiverarbeitung delegiert an `CMS\Services\MediaService`.
- Im Asset-Bundle liegen zusätzliche Locale-Dateien unter `CMS/assets/filepond/locale/`, die aktuell nicht produktiv eingebunden werden.

## Website / GitHub

- Website: https://pqina.nl/filepond/
- GitHub: https://github.com/pqina/filepond