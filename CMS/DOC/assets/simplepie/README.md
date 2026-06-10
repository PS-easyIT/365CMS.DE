# SimplePie

> **Stand:** 2026-06-10 | **Version:** 3.3.47 | **Status:** Aktuell

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