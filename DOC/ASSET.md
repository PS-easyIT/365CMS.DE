# Asset-Übersicht 365CMS

## Aktiv verwendete Bundles

| Beschreibung | Name | Pfad | Website | GitHub | Status |
|---|---|---|---|---|---|
| Datums- und Zeitbibliothek | Carbon | `Carbon/` | https://carbon.nesbot.com/ | https://github.com/briannesbitt/Carbon | aktiv |
| Consent-Banner für Cookies | Cookie Consent | `cookieconsent/` | https://cookieconsent.orestbida.com/ | https://github.com/orestbida/cookieconsent | aktiv |
| Interne System-Styles | 365CMS CSS | `css/` | – | – | aktiv, intern |
| Block-Editor | Editor.js | `editorjs/` | https://editorjs.io/ | https://github.com/codex-team/editor.js | aktiv |
| HTML-Sanitizer | HTMLPurifier | `htmlpurifier/` | https://htmlpurifier.org/ | https://github.com/ezyang/htmlpurifier | aktiv |
| Interne Bild-Assets | 365CMS Images | `images/` | – | – | aktiv, intern |
| Interne JavaScripts | 365CMS JS | `js/` | – | – | aktiv, intern |
| LDAP-Integration | LdapRecord | `ldaprecord/` | https://ldaprecord.com/ | https://github.com/DirectoryTree/LdapRecord | aktiv |
| Mailer-Komponente | Symfony Mailer | `mailer/` | https://symfony.com/components/Mailer | https://github.com/symfony/mailer | aktiv |
| MIME-Komponente | Symfony Mime | `mime/` | https://symfony.com/components/Mime | https://github.com/symfony/mime | aktiv |
| JWT-Implementierung | firebase/php-jwt | `php-jwt/` | https://github.com/firebase/php-jwt | https://github.com/firebase/php-jwt | aktiv |
| PSR-Kompatibilität | PSR Log / EventDispatcher | `psr/` | https://www.php-fig.org/psr/ | – | aktiv, lokal/minimal |
| PHP-Dateimanager | elFinder | `elfinder/` | https://studio-42.github.io/elFinder/ | https://github.com/Studio-42/elFinder | aktiv |
| Upload-Frontend | FilePond | `filepond/` | https://pqina.nl/filepond/ | https://github.com/pqina/filepond | aktiv |
| Tabellen-UI | Grid.js | `gridjs/` | https://gridjs.io/ | https://github.com/grid-js/gridjs | aktiv |
| Lightbox/Galerie | PhotoSwipe | `photoswipe/` | https://photoswipe.com/ | https://github.com/dimsemenov/PhotoSwipe | aktiv |
| Feed-Parser (Teil 1) | SimplePie Library | `simplepielibrary/` | https://simplepie.org/ | https://github.com/simplepie/simplepie | aktiv |
| Feed-Parser (Teil 2) | SimplePie Src | `simplepiesrc/` | https://simplepie.org/ | https://github.com/simplepie/simplepie | aktiv |
| WYSIWYG-Editor | SunEditor | `suneditor/` | https://suneditor.com/ | https://github.com/JiHong88/suneditor | aktiv |
| Admin-UI-Framework | Tabler Core | `tabler/` | https://tabler.io/ | https://github.com/tabler/tabler | aktiv |
| Suchbibliothek (Helper) | TNTSearch Helper | `tntsearchhelper/` | https://github.com/teamtnt/tntsearch | https://github.com/teamtnt/tntsearch | aktiv |
| Suchbibliothek (Core) | TNTSearch Src | `tntsearchsrc/` | https://github.com/teamtnt/tntsearch | https://github.com/teamtnt/tntsearch | aktiv |
| Übersetzungs-Komponente | Symfony Translation | `translation/` | https://symfony.com/components/Translation | https://github.com/symfony/translation | aktiv |
| TOTP / MFA | RobThree TwoFactorAuth | `twofactorauth/` | https://github.com/RobThree/TwoFactorAuth | https://github.com/RobThree/TwoFactorAuth | aktiv |
| Passkeys / WebAuthn | lbuchs/WebAuthn | `webauthn/` | https://github.com/lbuchs/WebAuthn | https://github.com/lbuchs/WebAuthn | aktiv |

## Mitgeliefert, aber derzeit nicht aktiv verdrahtet

| Beschreibung | Name | Pfad | Website | GitHub | Status |
|---|---|---|---|---|---|
| Schema.org-Builder | Spatie Schema.org | `schema-org/` | https://github.com/spatie/schema-org | https://github.com/spatie/schema-org | Reserve, aktuell manuell ersetzt |

## Bootstrap-/Hilfsdatei

| Beschreibung | Name | Pfad | Website | GitHub | Status |
|---|---|---|---|---|---|
| Zentraler Asset-Autoloader | Asset Autoload | `autoload.php` | – | – | aktiv, intern |

## Wichtige Nachweise

- `CMS/core/Services/CookieConsentService.php` bindet `cookieconsent/` direkt ein.
- `CMS/core/Services/EditorService.php` bindet `suneditor/` direkt ein.
- `CMS/core/Services/EditorJsService.php` verwaltet `editorjs/`.
- `CMS/core/Services/PurifierService.php` nutzt `HTMLPurifier`.
- `CMS/core/Services/FeedService.php` nutzt `SimplePie`.
- `CMS/core/Services/SearchService.php` nutzt `TNTSearch`.
- `CMS/core/Services/TranslationService.php` nutzt `Symfony Translation`.
- `CMS/core/Services/MailService.php` erzeugt E-Mails mit `Symfony Mime` und sendet SMTP-Nachrichten über `Symfony Mailer` (`EsmtpTransport`).
- `CMS/core/Services/MailQueueService.php` verarbeitet asynchronen Mailversand, Retries und Backoff über dieselbe lokale `mailer/`- und `mime/`-Kette.
- `CMS/cron.php` ist der zentrale Cron-Einstieg für den Queue-Versand und nutzt damit ebenfalls die lokal eingebundenen Mail-Assets.
- `CMS/core/Bootstrap.php` bindet `PhotoSwipe` ein und registriert die Kernservices.
- `CMS/admin/partials/header.php` und `CMS/admin/partials/footer.php` binden `tabler/` direkt ein.
- `CMS/admin/media.php`, `CMS/admin/views/media/library.php` und `CMS/assets/js/admin-media-integrations.js` binden `elFinder` und `FilePond` aktiv in die Medienverwaltung ein.
- `CMS/admin/users.php`, `CMS/admin/pages.php`, `CMS/admin/posts.php` sowie `CMS/assets/js/gridjs-init.js` binden `Grid.js` aktiv in Admin-Listen ein.
- `CMS/admin/user-settings.php`, `CMS/admin/modules/users/UserSettingsModule.php` und `CMS/core/Auth/LDAP/LdapAuthProvider.php` nutzen `ldaprecord/` zusätzlich für den LDAP-Erstsync im Admin.
- `CMS/assets/elfinder/vendor/jquery/` und `CMS/assets/elfinder/vendor/jquery-ui/` enthalten die lokal gehosteten Frontend-Abhängigkeiten für den CDN-freien elFinder-Betrieb.
- `CMS/assets/autoload.php` mappt die lokalen Namespaces für `mailer/`, `mime/` und die minimale `psr/`-Kompatibilität.
- `CMS/core/Auth/Passkey/WebAuthnAdapter.php`, `CMS/core/Auth/MFA/TotpAdapter.php`, `CMS/core/Auth/LDAP/LdapAuthProvider.php` und `CMS/core/Services/JwtService.php` verwenden `webauthn/`, `twofactorauth/`, `ldaprecord/` und `php-jwt/`.
- `CMS/assets/autoload.php` kommentiert ausdrücklich: `SEOService baut Schema.org derzeit manuell; Library als Reserve.`

## Detaildokumentation in `DOC/assets/`

- `DOC/assets/elfinder/README.md` – Admin-Dateimanager und lokaler Connector
- `DOC/assets/filepond/README.md` – Upload-Widget und Upload-Endpoint
- `DOC/assets/gridjs/README.md` – Server-seitige Admin-Tabellen
- `DOC/assets/ldaprecord/README.md` – LDAP-Authentifizierung und Admin-Erstsynchronisierung
- `DOC/assets/mime/README.md` – MIME-Erzeugung für Mail-Nachrichten
- `DOC/assets/psr/README.md` – lokale Minimal-Kompatibilität für `Psr\\Log` und `Psr\\EventDispatcher`
- `DOC/assets/simplepie/README.md` – gemeinsame Doku für `simplepielibrary/` und `simplepiesrc/`
- `DOC/assets/symfony-mailer/README.md` – SMTP-Versand über lokale Symfony-Komponenten
- `DOC/assets/tntsearch/README.md` – gemeinsame Doku für `tntsearchhelper/` und `tntsearchsrc/`