# 365CMS Asset-Dokumentation

Dieses Verzeichnis enthält die Detaildokumentation der **aktuell verwendeten** sowie einzelner **offener/ersetzender** Asset-Bundles rund um `CMS/assets/`.

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
- `mailer/` – lokaler Symfony-Mailer für SMTP, Queue und XOAUTH2
- `melbahja-seo/` – geplanter/fehlender Ersatz für Schema-/Sitemap-Bausteine
- `mime/` – MIME-Erzeugung für E-Mails
- `php-jwt/` – JWT-Erzeugung und -Validierung
- `photoswipe/` – Lightbox/Galerie
- `psr/` – lokale Minimal-Kompatibilität für PSR-Interfaces
- `simplepie/` – RSS/Atom-Parsing
- `suneditor/` – WYSIWYG-Editor
- `tabler/` – Admin-UI-Framework
- `tntsearch/` – Volltextsuche
- `translation/` – Übersetzungen / I18n
- `twofactorauth/` – TOTP / MFA
- `webauthn/` – Passkeys / WebAuthn

Die Gesamtübersicht aller Top-Level-Einträge liegt in `DOC/ASSET.md`.
Cleanup-Kandidaten und erledigte Bereinigungen sind in `DOC/ASSET_OUTDATET.md` dokumentiert.

## Neu bzw. angepasst

- `mailer/`, `mime/` und `psr/` dokumentieren die produktive Mail-Transportkette in `CMS/assets/` ohne Composer-Installation.
- `melbahja-seo/` dokumentiert den aktuell **nicht vorhandenen** Runtime-Ersatz für `schema-org/`, damit die offene Migration nachvollziehbar bleibt.
- Die Asset-Dokumentation berücksichtigt jetzt den bereinigten Autoloader ohne veraltete `rate-limiter`-/`image`-Einträge.