# FilePond

> **Stand:** 2026-03-28 | **Version:** 2.8.0 RC | **Status:** Legacy-Bestand

## Kurzbeschreibung

`FilePond` ist ein dokumentierter Legacy-Bestand des früheren Upload-Frontends; aktive Admin- und Member-Uploads laufen inzwischen über native 365CMS-Formulare und interne APIs.

## Quellordner

- `CMS/assets/filepond/`

## Verwendung in 365CMS

- keine aktive Frontend- oder Admin-Laufzeitverdrahtung mehr seit Folge-Batch 454
- die Bibliothek verbleibt aktuell nur als Altbestand im Repository

## Besondere Hinweise

- produktive Upload-Flows hängen an `MediaService`, `UploadHandler`, `admin/media.php`, `member/media.php` und nativen JS-/Form-Verträgen
- vor einem physischen Entfernen sollten Alt-Dokumentation, mögliche Fallback-Endpunkte und historische Upload-Integrationen geprüft werden

## Website / GitHub

- Website: https://pqina.nl/filepond/
- GitHub: https://github.com/pqina/filepond