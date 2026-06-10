# Cookie Consent

> **Stand:** 2026-06-10 | **Version:** 3.3.47 | **Status:** Aktuell

## Kurzbeschreibung

`cookieconsent` ist ein dokumentierter Legacy-Bestand der früheren Vendor-Runtime; aktive Consent-Ausgabe erfolgt inzwischen nativ über `CookieConsentService` und 365CMS-eigene CSS-/JS-Dateien.

## Quellordner

- `CMS/assets/cookieconsent/`

## Verwendung in 365CMS

- keine aktive Einbindung der Vendor-Runtime mehr seit Folge-Batch 454
- verbleibende Dateien dienen aktuell nur als Altbestand im Repository

## Besondere Hinweise

- produktive Consent-Initialisierung hängt an `CMS/core/Services/CookieConsentService.php` und `CMS/assets/js/cookieconsent-init.js`
- vor einem physischen Entfernen sollten Frontend-Templates, Alt-Dokumentation und eventuelle Fremdreferenzen geprüft werden

## Website / GitHub

- Website: https://cookieconsent.orestbida.com/
- GitHub: https://github.com/orestbida/cookieconsent