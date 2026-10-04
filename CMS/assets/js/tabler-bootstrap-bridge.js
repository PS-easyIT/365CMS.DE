/*!
 * 365CMS Tabler-Bridge
 *
 * Tabler (ab 1.4, aktuell 1.6) stellt die Bootstrap-Komponenten nur noch unter
 * `window.tabler` bzw. `window.tabler.bootstrap` bereit. Admin-/Member-Skripte
 * nutzen weiterhin `window.bootstrap.*` (Modal, Tooltip, Dropdown …).
 * Muss direkt nach tabler.min.js geladen werden.
 */
(function () {
    'use strict';

    if (window.bootstrap || !window.tabler) {
        return;
    }

    window.bootstrap = window.tabler.bootstrap || window.tabler;
})();
