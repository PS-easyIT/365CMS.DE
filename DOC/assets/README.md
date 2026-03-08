# 365CMS Asset-Dokumentation

Dieses Verzeichnis enthält die Detaildokumentation der **aktuell nachweisbar verwendeten** Asset-Bundles aus `CMS/assets/`.

## Enthaltene Dokus

- `carbon/` – Datums- und Zeitverarbeitung
- `cookieconsent/` – Cookie-Consent-Banner
- `css/` – interne 365CMS-Stylesheets
- `editorjs/` – Block-Editor
- `elfinder/` – Admin-Dateimanager für die Medienverwaltung
- `filepond/` – Upload-Widget für Medien-Uploads
- `gridjs/` – Admin-Tabellen mit Server-Side-Loading
- `htmlpurifier/` – HTML-Sanitizing
- `images/` – interne Bild-Assets
- `js/` – interne 365CMS-JavaScripts
- `ldaprecord/` – LDAP-Anbindung
- `mime/` – MIME-Erzeugung für E-Mails
- `php-jwt/` – JWT-Erzeugung und -Validierung
- `photoswipe/` – Lightbox/Galerie
- `psr/` – lokale Minimal-Kompatibilität für PSR-Interfaces
- `simplepie/` – RSS/Atom-Parsing
- `suneditor/` – WYSIWYG-Editor
- `symfony-mailer/` – SMTP-Versand mit Symfony Mailer
- `tabler/` – Admin-UI-Framework
- `tntsearch/` – Volltextsuche
- `translation/` – Übersetzungen / I18n
- `twofactorauth/` – TOTP / MFA
- `webauthn/` – Passkeys / WebAuthn

Die Gesamtübersicht aller Top-Level-Einträge liegt in `DOC/ASSET.md`.
Die derzeit unnötigen oder nicht verdrahteten Bundles sind in `ASSET_OUTDATET.md` dokumentiert.

## Neu ergänzt

- `elfinder/` dokumentiert den produktiven Admin-Dateimanager inklusive lokal gehosteter `jQuery`-/`jQuery UI`-Abhängigkeiten.
- `filepond/` dokumentiert das aktive Upload-Frontend in der Medienverwaltung inklusive `POST /api/upload`.
- `gridjs/` dokumentiert die produktive Nutzung in den Admin-Listen für Benutzer, Seiten und Beiträge.
- `ldaprecord/` dokumentiert nun zusätzlich den Admin-Flow für den LDAP-Erstsync unter `/admin/user-settings`.
- `symfony-mailer/`, `mime/` und `psr/` dokumentieren die lokale Mail-Transportkette in `CMS/assets/` ohne Composer-Installation.
- Die Mail-Dokumentation berücksichtigt jetzt auch den asynchronen Versand über `MailQueueService` und `CMS/cron.php`, der weiterhin auf den lokalen Symfony-Mail-Assets aufsetzt.