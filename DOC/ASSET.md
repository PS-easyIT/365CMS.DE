# Asset-Übersicht 365CMS

> Stand: 2026-03-08 | Version 2.5.0 | generiert nach Asset-Cleanup

## Aktiv verwendete Bundles

| Beschreibung | Name | Pfad | Website | GitHub | Status |
|---|---|---|---|---|---|
| Datums- und Zeitbibliothek | Carbon | `Carbon/` | https://carbon.nesbot.com/ | https://github.com/briannesbitt/Carbon | aktiv |
| Consent-Banner für Cookies | Cookie Consent | `cookieconsent/` | https://cookieconsent.orestbida.com/ | https://github.com/orestbida/cookieconsent | aktiv |
| Interne System-Styles | 365CMS CSS | `css/` | – | – | aktiv, intern |
| Block-Editor | Editor.js | `editorjs/` | https://editorjs.io/ | https://github.com/codex-team/editor.js | aktiv |
| PHP-Dateimanager | elFinder | `elfinder/` | https://studio-42.github.io/elFinder/ | https://github.com/Studio-42/elFinder | aktiv |
| Upload-Frontend | FilePond | `filepond/` | https://pqina.nl/filepond/ | https://github.com/pqina/filepond | aktiv |
| Tabellen-UI | Grid.js | `gridjs/` | https://gridjs.io/ | https://github.com/grid-js/gridjs | aktiv |
| HTML-Sanitizer | HTMLPurifier | `htmlpurifier/` | https://htmlpurifier.org/ | https://github.com/ezyang/htmlpurifier | aktiv |
| Interne Bild-Assets | 365CMS Images | `images/` | – | – | aktiv, intern |
| Interne JavaScripts | 365CMS JS | `js/` | – | – | aktiv, intern |
| LDAP-Integration | LdapRecord | `ldaprecord/` | https://ldaprecord.com/ | https://github.com/DirectoryTree/LdapRecord | aktiv |
| Mailer-Komponente | Symfony Mailer | `mailer/` | https://symfony.com/components/Mailer | https://github.com/symfony/mailer | aktiv |
| MIME-Komponente | Symfony Mime | `mime/` | https://symfony.com/components/Mime | https://github.com/symfony/mime | aktiv |
| Lightbox/Galerie | PhotoSwipe | `photoswipe/` | https://photoswipe.com/ | https://github.com/dimsemenov/PhotoSwipe | aktiv |
| JWT-Implementierung | firebase/php-jwt | `php-jwt/` | https://github.com/firebase/php-jwt | https://github.com/firebase/php-jwt | aktiv |
| PSR-Kompatibilität | PSR Log / EventDispatcher | `psr/` | https://www.php-fig.org/psr/ | – | aktiv (transitiv) |
| Feed-Parser (Legacy-Bootstrap) | SimplePie Library | `simplepielibrary/` | https://simplepie.org/ | https://github.com/simplepie/simplepie | aktiv |
| Feed-Parser (Namespaced Klassen) | SimplePie Src | `simplepiesrc/` | https://simplepie.org/ | https://github.com/simplepie/simplepie | aktiv |
| WYSIWYG-Editor | SunEditor | `suneditor/` | https://suneditor.com/ | https://github.com/JiHong88/SunEditor | aktiv |
| Admin-UI-Framework | Tabler Core | `tabler/` | https://tabler.io/ | https://github.com/tabler/tabler | aktiv |
| Suchbibliothek (Helper) | TNTSearch Helper | `tntsearchhelper/` | https://github.com/teamtnt/tntsearch | https://github.com/teamtnt/tntsearch | aktiv |
| Suchbibliothek (Core) | TNTSearch Src | `tntsearchsrc/` | https://github.com/teamtnt/tntsearch | https://github.com/teamtnt/tntsearch | aktiv |
| Übersetzungs-Komponente | Symfony Translation | `translation/` | https://symfony.com/components/Translation | https://github.com/symfony/translation | aktiv |
| TOTP / MFA | RobThree TwoFactorAuth | `twofactorauth/` | https://github.com/RobThree/TwoFactorAuth | https://github.com/RobThree/TwoFactorAuth | aktiv |
| Passkeys / WebAuthn | lbuchs/WebAuthn | `webauthn/` | https://github.com/lbuchs/WebAuthn | https://github.com/lbuchs/WebAuthn | aktiv |

## Mitgeliefert, aber derzeit nicht aktiv verdrahtet

| Beschreibung | Name | Pfad | Website | GitHub | Status |
|---|---|---|---|---|---|
| Lokale Referenznotizen für Graph-SDK | Microsoft Graph Notes | `msgraph/` | https://learn.microsoft.com/graph/ | https://github.com/microsoftgraph/msgraph-sdk-php | ungenutzt in Runtime, nur Referenzablage |

## Bootstrap-/Hilfsdatei

| Beschreibung | Name | Pfad | Website | GitHub | Status |
|---|---|---|---|---|---|
| Zentraler Asset-Autoloader | Asset Autoload | `autoload.php` | – | – | aktiv, intern |

## Wichtige Nachweise

- `CMS/includes/functions.php` nutzt `Carbon\Carbon` in `time_ago()` per `class_exists`-Guard.
- `CMS/core/Services/CookieConsentService.php` und `CMS/assets/js/cookieconsent-init.js` verdrahten `cookieconsent/` im Frontend.
- `CMS/core/Services/EditorService.php` bindet `suneditor/` direkt ein.
- `CMS/core/Services/EditorJsService.php` und Admin-Editoren nutzen `editorjs/`.
- `CMS/core/Services/PurifierService.php` lädt `HTMLPurifier`.
- `CMS/core/Services/FeedService.php` verwendet `\SimplePie\SimplePie`, `\SimplePie\Item` und den Legacy-Bootstrap der SimplePie-Library.
- `CMS/core/Services/SearchService.php` verwendet `TeamTNT\TNTSearch\TNTSearch` plus Helper aus `tntsearchhelper/`.
- `CMS/core/Services/TranslationService.php` nutzt `Symfony\Component\Translation\Translator`; `translation/` greift transitiv auf `psr/` zu.
- `CMS/core/Services/MailService.php` erzeugt `Symfony\Component\Mime\Email` und sendet über `Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport`.
- `CMS/core/Services/MailQueueService.php` und `CMS/cron.php` verarbeiten asynchronen Versand über dieselbe lokale `mailer/`-/`mime/`-Kette.
- `CMS/assets/mailer/` und `CMS/assets/translation/` referenzieren `Psr\Log` bzw. `Psr\EventDispatcher`; deshalb ist `psr/` aktiv (transitiv).
- `CMS/core/Auth/LDAP/LdapAuthProvider.php` sowie `CMS/admin/modules/users/UserSettingsModule.php` nutzen `ldaprecord/` für LDAP-Login und Admin-Erstsync.
- `CMS/core/Auth/Passkey/WebAuthnAdapter.php`, `CMS/core/Auth/MFA/TotpAdapter.php` und `CMS/core/Services/JwtService.php` verwenden `webauthn/`, `twofactorauth/` und `php-jwt/`.
- `CMS/admin/media.php`, `CMS/admin/views/media/library.php` und `CMS/assets/js/admin-media-integrations.js` binden `elFinder` und `FilePond` produktiv ein.
- `CMS/admin/partials/header.php` und `CMS/admin/partials/footer.php` binden `tabler/` direkt ein; zuvor ungenutzte `tabler/libs/*`-Sub-Libs wurden am 2026-03-08 aus dem Runtime-Baum entfernt.
- `CMS/core/Bootstrap.php` und `CMS/assets/js/photoswipe-init.js` aktivieren `photoswipe/` für Frontend-Lightboxen.
- `CMS/core/Services/SEOService.php` erzeugt JSON-LD aktuell manuell; die frühere Reserve-Library `schema-org/` wurde am 2026-03-08 aus `CMS/assets/` entfernt.
- `CMS/assets/filepond/locale/de-de.js` und `CMS/assets/filepond/locale/en-en.js` bleiben trotz fehlender direkter Treffer bewusst erhalten und wurden **nicht** bereinigt.
- `CMS/assets/msgraph/README.md` dokumentiert ausdrücklich, dass `GraphApiService` aktuell cURL-basiert arbeitet und **kein** Runtime-SDK aus `CMS/assets/msgraph/` lädt.

## Detaildokumentation in `DOC/assets/`

- `DOC/assets/carbon/README.md` – Datums-/Zeitlogik mit Carbon
- `DOC/assets/cookieconsent/README.md` – Consent-Banner und Frontend-Hydration
- `DOC/assets/css/README.md` – interne CSS-Assets
- `DOC/assets/editorjs/README.md` – Block-Editor-Integration
- `DOC/assets/elfinder/README.md` – Admin-Dateimanager und lokaler Connector
- `DOC/assets/filepond/README.md` – Upload-Widget und Upload-Endpoint
- `DOC/assets/gridjs/README.md` – serverseitige Admin-Tabellen
- `DOC/assets/htmlpurifier/README.md` – HTML-Sanitizing
- `DOC/assets/images/README.md` – interne Bild-Assets
- `DOC/assets/js/README.md` – interne JavaScript-Assets
- `DOC/assets/ldaprecord/README.md` – LDAP-Authentifizierung und Admin-Erstsynchronisierung
- `DOC/assets/mailer/README.md` – lokaler Symfony-Mailer inkl. Queue-/XOAUTH2-Kontext
- `DOC/assets/melbahja-seo/README.md` – geplanter/fehlender Schema-/Sitemap-Ersatz, derzeit nicht im Runtime-Baum vorhanden
- `DOC/assets/mime/README.md` – MIME-Erzeugung für Mail-Nachrichten
- `DOC/assets/php-jwt/README.md` – JWT-Erzeugung und -Validierung
- `DOC/assets/photoswipe/README.md` – Frontend-Lightbox
- `DOC/assets/psr/README.md` – lokale Minimal-Kompatibilität für `Psr\\Log` und `Psr\\EventDispatcher`
- `DOC/assets/simplepie/README.md` – gemeinsame Doku für `simplepielibrary/` und `simplepiesrc/`
- `DOC/assets/tabler/README.md` – Admin-UI-Framework
- `DOC/assets/tntsearch/README.md` – gemeinsame Doku für `tntsearchhelper/` und `tntsearchsrc/`
- `DOC/assets/translation/README.md` – Übersetzung / I18n
- `DOC/assets/twofactorauth/README.md` – TOTP / MFA
- `DOC/assets/webauthn/README.md` – Passkeys / WebAuthn