# Grid.js

## Kurzbeschreibung

`Grid.js` stellt die modernen, serverseitig geladenen Tabellen in der 365CMS-Administration bereit.

## Quellordner

- `CMS/assets/gridjs/`

## Verwendung in 365CMS

- gemeinsamer Helper `window.cmsGrid()` in `CMS/assets/js/gridjs-init.js`
- Asset-Einbindung in `CMS/admin/users.php`
- Asset-Einbindung in `CMS/admin/pages.php`
- Asset-Einbindung in `CMS/admin/posts.php`
- JSON-Datenquellen über die Admin-API in `CMS/core/Router.php`

## Besondere Hinweise

- Die Tabellen nutzen serverseitige Pagination, Sortierung und Suche über die Admin-API.
- Das Styling wird zusätzlich in `CMS/assets/css/admin-tabler.css` an das Tabler-Backend angepasst.
- Produktiv genutzt wird Grid.js aktuell in den Admin-Listen für Benutzer, Seiten und Beiträge.

## Website / GitHub

- Website: https://gridjs.io/
- GitHub: https://github.com/grid-js/gridjs