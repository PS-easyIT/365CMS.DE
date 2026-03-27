# FilePond

## Kurzbeschreibung

`FilePond` ist ein Legacy-Bestand des früheren Upload-Frontends; aktive Admin- und Member-Uploads laufen inzwischen über native 365CMS-Formulare und interne APIs.

## Quellordner

- `CMS/assets/filepond/`

## Verwendung in 365CMS

- keine aktive Frontend- oder Admin-Verdrahtung mehr seit Folge-Batch 454
- `CMS/core/Services/FileUploadService.php` akzeptiert aus Kompatibilitätsgründen weiterhin `filepond` und `file` als Upload-Feldnamen

## Besondere Hinweise

- Der Upload-Endpunkt akzeptiert weiterhin das Feld `filepond`, bevorzugt im nativen 365CMS-Flow aber `file`.
- CSRF-Schutz erfolgt über den Scope `media_action`.
- Die eigentliche Dateiverarbeitung delegiert an `CMS\Services\MediaService`.
- Im Asset-Bundle liegen zusätzliche Locale-Dateien unter `CMS/assets/filepond/locale/`, die aktuell nicht produktiv eingebunden werden.

## Website / GitHub

- Website: https://pqina.nl/filepond/
- GitHub: https://github.com/pqina/filepond