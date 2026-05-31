# Tabler Core

> **Stand:** 2026-05-31 | **Version:** 3.3.44

## Kurzbeschreibung

`Tabler` ist das primäre Admin-UI-Framework von 365CMS.

## Quellordner

- `CMS/assets/tabler/`
- `CMS/assets/tabler-icons/` für die lokal eingebundenen Tabler-Icon-Webfonts

## Verwendung in 365CMS

- direkte CSS-Einbindung in `CMS/admin/partials/header.php`
- direkte JS-Einbindung in `CMS/admin/partials/footer.php`
- seit `3.3.42` lädt der Admin-Header die Tabler Icons nicht mehr über jsDelivr/CDN, sondern ausschließlich lokal über `/assets/tabler-icons/tabler-icons.min.css`

## Lokale Icon-Webfonts

Die Webfont-Dateien aus `CMS/assets/tabler-icons/fonts/` sind produktiver Runtime-Bestand. Sie ersetzen den früheren externen Tabler-Icons-CDN-Request im Admin und müssen bei Asset-Refreshes gemeinsam mit `tabler-icons.min.css` erhalten bleiben.

## Website / GitHub

- Website: https://tabler.io/
- GitHub: https://github.com/tabler/tabler