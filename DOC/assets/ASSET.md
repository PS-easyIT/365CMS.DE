# ASSET-Übersicht 365CMS
> **Stand:** 2026-10-04 | **Version:** 3.4.00 | **Status:** Aktuell

## Inhaltsverzeichnis
- <a>Aktive Runtime-Bundles</a>
- <a>Synchronisationsregeln</a>
- <a>CSS- und JavaScript-Architektur</a>
- <a>Build- und Update-Workflow</a>
- <a>Interne Bild-Assets</a>
- <a>Neue Kandidaten außerhalb der Runtime</a>

---

## Aktive Runtime-Bundles <!-- UPDATED: 2026-10-04 -->
Führende Quelle für **aktive Laufzeitpfade** ist `CMS/assets/` sowie der dokumentierte Sonderfall `CMS/vendor/dompdf/`. Das Root-Verzeichnis `ASSETS/` ist **Staging-/Quellmaterial**, nicht die produktive Wahrheit.

| Library | Runtime-Stand | Zweck | Produktiver Pfad | Quelle in `/ASSETS` | Hinweis | Website | GitHub |
|---|---|---|---|---|---|---|---|
| `tabler` | `1.4.0` | Admin-/Member-UI | `CMS/assets/tabler/` | `ASSETS/tabler-core-1.4.0/core/dist/` | nur `css/tabler.min.css` und `js/tabler.min.js` übernehmen (Flags/Payments/RTL/Maps/`img/` werden nicht geladen) | [tabler.io](https://tabler.io/) | [tabler/tabler](https://github.com/tabler/tabler) |
| `editorjs` | `2.31.6` | Block-Editor | `CMS/assets/editorjs/` | `ASSETS/editor.js-2.31.6/` | kuratierter Runtime-Satz aus Core-Dateien und gezielt gebauten Plugin-Artefakten wie `delimiter.umd.js` | [editorjs.io](https://editorjs.io/) | [codex-team/editor.js](https://github.com/codex-team/editor.js) |
| `suneditor` | `3.0.5` | Legacy-WYSIWYG | `CMS/assets/suneditor/` | `ASSETS/suneditor-3.0.5/` | Runtime wird aus `dist/` + `src/langs/de.js` übernommen; fehlt `dist/` nach Upstream-Download, muss lokal gebaut werden | [suneditor.com](https://suneditor.com/) | [JiHong88/suneditor](https://github.com/JiHong88/suneditor) |
| `photoswipe` | `5.x`-Build | Lightbox | `CMS/assets/photoswipe/` | `ASSETS/PhotoSwipe/` | nur produktive Frontend-Dateien übernehmen | [photoswipe.com](https://photoswipe.com/) | [dimsemenov/PhotoSwipe](https://github.com/dimsemenov/PhotoSwipe) |
| `Carbon` | `3.11.4` | Datum / Zeit | `CMS/assets/Carbon/src/Carbon/`, `CMS/assets/Carbon/lazy/Carbon/` | `ASSETS/Carbon-3.11.4/src/Carbon/`, `ASSETS/Carbon-3.11.4/lazy/` | Upstream-Layout `src/` + `lazy/` beibehalten (relative `lazy/`-Requires); ohne `Laravel/`, `PHPStan/`, `Cli/` | [carbonphp.github.io/carbon](https://carbonphp.github.io/carbon/) | [CarbonPHP/carbon](https://github.com/CarbonPHP/carbon) |
| `ldaprecord` | `4.0.3` | LDAP / Active Directory | `CMS/assets/ldaprecord/` | `ASSETS/LdapRecord-4.0.3/src/` | kompletter Source-Ordner für PSR-4 | [ldaprecord.com](https://ldaprecord.com/) | [DirectoryTree/LdapRecord](https://github.com/DirectoryTree/LdapRecord) |
| `mailer` | `8.0.8` | Mailversand | `CMS/assets/mailer/` | `ASSETS/mailer-8.0.8/` | Symfony-Komponente | [symfony.com/components/Mailer](https://symfony.com/components/Mailer) | [symfony/mailer](https://github.com/symfony/mailer) |
| `mime` | `8.0.8` | MIME / Anhänge | `CMS/assets/mime/` | `ASSETS/mime-8.0.8/` | Symfony-Komponente | [symfony.com/components/Mime](https://symfony.com/components/Mime) | [symfony/mime](https://github.com/symfony/mime) |
| `translation` | `8.0.8` | i18n | `CMS/assets/translation/` | `ASSETS/translation-8.0.8/` | Symfony-Komponente, ohne Command/DataCollector/DI/Extractor | [symfony.com/components/Translation](https://symfony.com/components/Translation) | [symfony/translation](https://github.com/symfony/translation) |
| `yaml` | `8.0.8` | YAML-Parser (Sprachkataloge) | `CMS/assets/yaml/` | Packagist `symfony/yaml` | ohne `Command/` | [symfony.com/components/Yaml](https://symfony.com/components/Yaml) | [symfony/yaml](https://github.com/symfony/yaml) |
| `clock` | `8.0.8` | Clock für Carbon / AI | `CMS/assets/clock/` | Packagist `symfony/clock` | inkl. `Resources/now.php` (files-Autoload) | [symfony.com/components/Clock](https://symfony.com/components/Clock) | [symfony/clock](https://github.com/symfony/clock) |
| `egulias-email-validator` | `4.0.4` | E-Mail-Validierung | `CMS/assets/egulias-email-validator/` | Packagist `egulias/email-validator` | Pflicht für `Mime\Address`/SMTP; benötigt `doctrine-lexer` 3.0.2 | [packagist.org/packages/egulias/email-validator](https://packagist.org/packages/egulias/email-validator) | [egulias/EmailValidator](https://github.com/egulias/EmailValidator) |
| AI-Platform-Kette | `8.0.8` bzw. aktuelle Stable | Serializer, PropertyInfo/-Access, TypeInfo, Uid, String, EventDispatcher, enum-helper, phpDocumentor, phpdoc-parser, webmozart/assert, doctrine/deprecations | jeweils `CMS/assets/<paket>/` | Packagist | ohne Tests/Command/DataCollector/DI | [ai.symfony.com](https://ai.symfony.com/) | [symfony/ai-platform](https://github.com/symfony/ai-platform) |
| `polyfill-*` | `1.3x`/`1.4x` | mbstring, ctype, intl-idn/-normalizer/-grapheme, uuid | `CMS/assets/polyfill-*/` | Packagist `symfony/polyfill-*` | `bootstrap.php` über `files`-Liste im Autoloader | [symfony.com/components/Polyfill Mbstring](https://symfony.com/components/Polyfill%20Mbstring) | [symfony/polyfill](https://github.com/symfony/polyfill) |
| `symfony-contracts` | `3.6.1` | Service-/Translation-/EventDispatcher-/HttpClient-/Deprecation-Contracts | `CMS/assets/symfony-contracts/` | `ASSETS/contracts-3.6.1/` | ersetzt den früheren Minimal-Shim; `Cache/` nicht übernommen (kein psr/cache) | [symfony.com](https://symfony.com/) | [symfony/contracts](https://github.com/symfony/contracts) |
| `tntsearch` | `5.0.3` | Volltextsuche | `CMS/assets/tntsearchsrc/`, `CMS/assets/tntsearchhelper/` | `ASSETS/tntsearch-5.0.3/` | `src/` und `helper/helpers.php` getrennt gespiegelt | [packagist.org/packages/teamtnt/tntsearch](https://packagist.org/packages/teamtnt/tntsearch) | [teamtnt/tntsearch](https://github.com/teamtnt/tntsearch) |
| `php-jwt` | gebündelter Snapshot | JWT | `CMS/assets/php-jwt/` | `ASSETS/php-jwt/` | produktiv lokal gebündelt | [packagist.org/packages/firebase/php-jwt](https://packagist.org/packages/firebase/php-jwt) | [googleapis/php-jwt](https://github.com/googleapis/php-jwt) |
| `twofactorauth` | gebündelter Snapshot | TOTP / 2FA | `CMS/assets/twofactorauth/` | `ASSETS/twofactorauth/` | sicherheitskritisch, nur kapseln | [robthree.github.io/TwoFactorAuth](https://robthree.github.io/TwoFactorAuth/) | [RobThree/TwoFactorAuth](https://github.com/RobThree/TwoFactorAuth) |
| `webauthn` | gebündelter Snapshot | Passkeys / WebAuthn | `CMS/assets/webauthn/` | `ASSETS/webauthn/` | sicherheitskritisch, nur kapseln | [packagist.org/packages/lbuchs/webauthn](https://packagist.org/packages/lbuchs/webauthn) | [lbuchs/WebAuthn](https://github.com/lbuchs/WebAuthn) |
| `htmlpurifier` | gebündelter Snapshot | XSS-Schutz | `CMS/assets/htmlpurifier/` | `ASSETS/htmlpurifier/` | produktive Sanitizer-Basis | [htmlpurifier.org](https://htmlpurifier.org/) | [ezyang/htmlpurifier](https://github.com/ezyang/htmlpurifier) |
| `melbahja-seo` | gebündelter Snapshot | SEO-Helfer | `CMS/assets/melbahja-seo/` | `ASSETS/melbahja-seo/` | mittelfristig in Core-Services zerlegbar | [packagist.org/packages/melbahja/seo](https://packagist.org/packages/melbahja/seo) | [melbahja/seo](https://github.com/melbahja/seo) |
| `psr` | Log 3.0.2, EventDispatcher 1.0.0, Container 2.0.2, Clock 1.0.0 | PSR-Interfaces | `CMS/assets/psr/` | Packagist `psr/*` | vollständige Originalpakete statt Minimal-Shim | [php-fig.org/psr](https://www.php-fig.org/psr/) | [php-fig](https://github.com/php-fig) |
| `dompdf` | `3.1.5` | PDF-Erzeugung | `CMS/vendor/dompdf/` | `ASSETS/dompdf-3.1.5/dompdf/vendor/` | **kein** `CMS/assets`-Bundle, sondern Vendor-Sonderfall | [dompdf.github.io](https://dompdf.github.io/) | [dompdf/dompdf](https://github.com/dompdf/dompdf) |
| `css/js/images` | intern | 365CMS-eigene Runtime-Assets | `CMS/assets/css/`, `CMS/assets/js/`, `CMS/assets/images/` | `ASSETS/css/`, `ASSETS/js/`, `ASSETS/images/` | kein Third-Party-Bundle; Bildinventar siehe [`DOC/assets/images/README.md`](images/README.md) | – | – |
| `msgraph` | Referenzbestand | SDK-Ablage | `CMS/assets/msgraph/` | `ASSETS/msgraph-sdk-php-2.56.0/` | aktuell nicht als aktive Runtime-Integration dokumentiert | [learn.microsoft.com/graph/sdks/sdks-overview](https://learn.microsoft.com/graph/sdks/sdks-overview) | [microsoftgraph/msgraph-sdk-php](https://github.com/microsoftgraph/msgraph-sdk-php) |

Zusätzlich produktiv relevant:

- `CMS/assets/autoload.php` als zentraler PHP-Asset-Autoloader
- `CMS/core/VendorRegistry.php` als Diagnose-/Ladevertrag für gebündelte Libraries
- `CMS/core/Services/PdfService.php` als dokumentierter Dompdf-Einstieg

Die Website-/GitHub-Spalten nennen das Hauptprojekt. Die vollständige Upstream-Liste je Paket (inklusive aller Editor.js-Plugins, AI-Platform-Abhängigkeiten, PSR-Pakete und Dompdf-Unterpakete, Stand 2026-10-04) steht in [README.md → Upstream-Quellen](README.md#upstream-quellen-website--github).

---

## Synchronisationsregeln <!-- UPDATED: 2026-09-06 -->

Die Synchronisation von `/ASSETS` nach `/CMS/assets` ist **selektiv**, nicht spiegelnd. Ein vollständiges Rekursiv-Kopieren ganzer Upstream-Repositories würde Tests, Build-Tooling, `node_modules`, Dokumentation oder falsche Laufzeitpfade mit in die Runtime tragen.

Wichtige Regeln im aktuellen Stand:

1. **`ASSETS/` ist Staging, `CMS/assets/` ist Runtime.**
2. **Frontend-Bundles nur als Build-Artefakte übernehmen.** Das gilt insbesondere für `tabler`, `gridjs`, `PhotoSwipe`, `editor.js` und `suneditor`.
3. **`editorjs` bleibt kuratiert.** Die Runtime orientiert sich an `CMS/core/Services/EditorJs/EditorJsAssetService.php`, nicht am gesamten Plugin-Baum; Zusatzartefakte wie `delimiter.umd.js` müssen bei neuen Plugin-Ständen gezielt gebaut oder aktualisiert werden.
4. **`suneditor` ist ein Sonderfall.** Für die Runtime werden `suneditor.min.js`, `suneditor.min.css`, `suneditor-contents.min.css` und `src/langs/de.js` aus dem gebauten Paketstand übernommen; fehlt `dist/` nach einem frischen Upstream-Download, muss SunEditor zuerst lokal gebaut werden.
6. **`dompdf` bleibt außerhalb von `CMS/assets/`.** Der produktive Pfad ist `CMS/vendor/dompdf/`, geladen über `CMS/vendor/dompdf/autoload.php`.
7. **Sicherheits- und Standard-Bibliotheken nicht umstrukturieren, solange der Autoload-Vertrag stabil bleiben muss.** Das betrifft u. a. `htmlpurifier`, `php-jwt`, `webauthn`, `twofactorauth`, `ldaprecord`, `mailer`, `mime` und `translation`.

---

## CSS- und JavaScript-Architektur <!-- UPDATED: 2026-09-06 -->

### CSS

- **Basis:** `Tabler` plus 365CMS-spezifische Overrides in `CMS/assets/css/`
- **Produktive Styles:** `admin.css`, `admin-tabler.css`, `admin-hub-*`, `hub-sites.css`, `main.css`, `member-dashboard.css`, `cms-cookie-consent.css`
- **Editor-Sonderfälle:** `SunEditor` bringt eigenes CSS mit; `Editor.js` nutzt eigene Tool-/Block-Assets
- **Theme-Trennung:** Themes liefern zusätzliches Frontend-CSS in `CMS/themes/<theme>/`; globale Runtime-Assets bleiben davon getrennt

### JavaScript

- **Zentrale Runtime-Zone:** `CMS/assets/js/`
- **Wichtige produktive Dateien:** `admin.js`, `admin-content-editor.js`, `admin-media-integrations.js`, `admin-seo-redirects.js`, `admin-system-cron.js`, `member-dashboard.js`, `photoswipe-init.js`, `cookieconsent-init.js`
- **Editor-Zonen:**
  - `Editor.js` = kuratierter Block-Editor mit mehreren Plugin-Builds
  - `SunEditor` = Legacy-WYSIWYG für HTML-Eingabe
- **Ladeverträge:** Admin-/Frontend-Templates referenzieren Assets weiterhin manuell über `ASSETS_URL`, `SITE_URL . '/assets'`, `cms_asset_url()` oder `filemtime()`-basierte Varianten

---

## Build- und Update-Workflow <!-- UPDATED: 2026-09-06 -->

Es gibt **keine zentrale Repo-weite Bundling-Pipeline**. Asset-Updates sind weiterhin ein dokumentierter manueller bzw. teilmanueller Pfad.

Empfohlener Ablauf pro Update:

1. Quellstand in `/ASSETS` prüfen
2. Nur den tatsächlich produktiven Runtime-Scope nach `CMS/assets/` bzw. `CMS/vendor/` übernehmen
3. `CMS/assets/autoload.php` sowie Core-Verträge (`VendorRegistry`, `PdfService`, Editor-Services) gegen Pfadänderungen prüfen
4. Falls ein Upstream nur Source-Dateien enthält, den Build lokal erzeugen und **erst dann** die Artefakte übernehmen
5. Doku synchron halten:
   - `DOC/ASSET.md`
   - `DOC/assets/README.md`
   - `DOC/ASSETS_OwnAssets.md`
   - `DOC/ASSETS_NEW.md` für neue Kandidaten außerhalb der Runtime

---

## Interne Bild-Assets <!-- UPDATED: 2026-09-06 -->

`CMS/assets/images/` ist der produktive interne Bildbestand der Runtime. Der
aktuelle Bestand umfasst 13 PNG-Dateien:

- zwei Dashboard-Icons für Admin und Member
- fünf Logos mit Text
- drei reine Schriftzüge
- drei Markenzeichen ohne Schriftzug

Die verbindliche Einzelauflistung mit Abmessungen und Verwendungszweck steht in
[`DOC/assets/images/README.md`](images/README.md). Neue Bilddateien sind
dort und in [`DOC/FILELIST.md`](../FILELIST.md) gemeinsam zu ergänzen.

---

## Neue Kandidaten außerhalb der Runtime <!-- UPDATED: 2026-09-06 -->

Folgende Pakete liegen unter `/ASSETS`, sind aber **nicht** produktiv in `CMS/assets/` bzw. `CMS/vendor/` integriert:

- `symfony/cache` (`ASSETS/cache-8.0.8/`)
- `guzzlehttp/guzzle` (`ASSETS/guzzle-7.10.0/`)
- `adhocore/jwt` (`ASSETS/php-jwt_yuliyan_1.1.3/`)
- weitere Beobachtungskandidaten wie `monolog-bundle-4.0.2`, `msgraph-sdk-php-2.56.0`

Hinweis seit `3.3.42`: Tabler Icons wurden aus dem Beobachtungskandidaten-Status in die produktive Runtime übernommen. Die lokale Kopie unter `CMS/assets/tabler-icons/` ersetzt externe jsDelivr-/Tabler-Icon-Webfont-Requests im Admin-Header.

Für diese Kandidaten gilt:

- **nicht blind in die Runtime kopieren**
- zunächst Service-/Adapter-Schnittstellen im Core definieren
- transitive Abhängigkeiten vollständig bewerten
- Betriebsrisiken, Provider-Abhängigkeiten und Secrets-/Rate-Limits vor Integration dokumentieren

Ausnahme im aktuellen Stand:

- `symfony/ai-platform` zählt nicht mehr als reiner Außenkandidat, weil die Basis jetzt produktiv unter `CMS/assets/ai-platform/` liegt, vom zentralen Assets-Autoloader aufgelöst wird und in `Diagnose -> Assets` als Produktivpaket erscheint.
- Das ändert **nicht** den Architekturgrundsatz: weitere Provider-Bridges und Zusatzabhängigkeiten dürfen weiterhin nur kontrolliert über Core-Adapter und Feature-Gates in die Runtime wachsen.

Zusätzlich wichtig im aktuellen Stand:

- Das zuvor mitgeführte Paket `stichoza/google-translate-php` wurde **bewusst aus `/ASSETS` entfernt** und wird nicht weiter als aktiver Integrationskandidat geführt.
- Für Übersetzungs- und Rewrite-Funktionen ist stattdessen ein **providerbasierter AI-Services-Ansatz** vorgesehen; Details siehe [ASSETS_NEW.md](ASSETS_NEW.md), [ai/AI-SERVICES.md](../ai/AI-SERVICES.md) und den Admin-Kontext unter [admin/system-settings/AI-SERVICES.md](../admin/system-settings/AI-SERVICES.md).

Die ausführliche Bewertungs- und Integrationsdoku dazu liegt in [ASSETS_NEW.md](ASSETS_NEW.md).

---

## Audit-Notiz zur Runtime-Integration <!-- UPDATED: 2026-09-06 -->

- Die produktive PHP-Dependency-Ladung erfolgt überwiegend über `CMS/assets/autoload.php`.
- Die dokumentierte Ausnahme bleibt `CMS/vendor/dompdf/autoload.php`.
- Die produktiv eingebundenen Symfony-Bundles `mailer`, `mime` und `translation` deklarieren `PHP >= 8.4`; diese Mindestplattform ist deshalb Teil des offiziellen Runtime-Vertrags.
- Besonders update-sensibel bleiben Editor- und UI-Bundles mit Build-Artefakten (`editorjs`, `suneditor`, `tabler`, `photoswipe`, `gridjs`).
- Für künftige Pflege wäre eine kleine zentrale Asset-/Versionierungs-Registry sinnvoll, damit Pfadlogik, Existenzprüfung und Cache-Busting nicht über viele Dateien verstreut bleiben.
