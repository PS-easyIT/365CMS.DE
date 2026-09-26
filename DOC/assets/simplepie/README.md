# SimplePie

> **Stand:** 2026-09-26 | **Version:** 3.4.02 | **Status:** Entfernt

## Kurzbeschreibung

`SimplePie` war ein Legacy-Bestand für RSS-/Atom-Feeds. Die Feed-Verarbeitung läuft seit Folge-Batch 454 nativ über `CMS/core/Services/FeedService.php` per DOM/XML; SimplePie war weder im Autoloader registriert noch referenziert.

## Status

- `CMS/assets/simplepielibrary/` und `CMS/assets/simplepiesrc/` wurden in `3.4.02` entfernt.
- Der Legacy-Eintrag in `CMS/core/VendorRegistry.php` wurde entfernt.

## Website / GitHub

- Website: https://simplepie.org/
- GitHub: https://github.com/simplepie/simplepie