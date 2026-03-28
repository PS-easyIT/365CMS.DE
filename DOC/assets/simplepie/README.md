# SimplePie

> **Stand:** 2026-03-28 | **Version:** 2.8.0 RC | **Status:** Legacy-Bestand

## Kurzbeschreibung

`SimplePie` ist ein dokumentierter Legacy-Bestand für frühere RSS-/Atom-Feeds; aktive Feed-Verarbeitung läuft seit Folge-Batch 454 nativ über `FeedService` per DOM/XML.

## Quellordner

- `CMS/assets/simplepielibrary/`
- `CMS/assets/simplepiesrc/`

## Verwendung in 365CMS

- keine aktive Laufzeitverdrahtung mehr in `FeedService`
- keine aktive Klassenbereitstellung mehr über `CMS/assets/autoload.php`

## Besondere Hinweise

- die Dateien bleiben als Altbestand und werden hinsichtlich Vendor-Netzwerkpfaden separat beobachtet
- produktive Feed-Logik hängt an `CMS/core/Services/FeedService.php`

## Website / GitHub

- Website: https://simplepie.org/
- GitHub: https://github.com/simplepie/simplepie