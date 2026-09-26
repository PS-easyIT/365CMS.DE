# 365CMS Asset-Dokumentation
> **Stand:** 2026-09-26 | **Version:** 3.4.02 | **Status:** Aktuell

## Inhaltsverzeichnis
- <a>Tabellarische Übersicht</a>
- <a>Runtime-Details</a>
- <a>Pfad, Autoload und Sonderfälle</a>
- <a>Interne Bild-Assets</a>
- <a>Neue Kandidaten außerhalb der Runtime</a>

---

## Tabellarische Übersicht <!-- UPDATED: 2026-09-26 -->
| Kategorie | Library | Runtime-Stand | Zweck | Eingebunden in |
|---|---|---|---|---|
| UI | `tabler` | `1.4.0` (nur `tabler.min.css` / `tabler.min.js`) | Admin-UI-Framework | Admin / Member |
| UI | `tabler-icons` | `3.41.1` (nur `tabler-icons.min.css` + woff2/woff/ttf) | Icon-Webfont | Admin |
| UI | `photoswipe` | `5.x`-Build | Lightbox | Frontend |
| Editor | `editorjs` | `2.31.6` | Block-Editor | Admin / Frontend |
| Editor | `suneditor` | `3.0.5` | Legacy-WYSIWYG | Admin |
| Auth | `php-jwt` | gebündelter Snapshot | JWT | API / Auth |
| Auth | `ldaprecord` | `4.0.3` | LDAP / AD | Auth |
| Auth | `twofactorauth` | gebündelter Snapshot | TOTP | Auth |
| Auth | `bacon-qr-code` + `dasprid-enum` | `3.1.1` / `1.0.7` | lokale TOTP-QR-Codes als SVG (kein externer QR-Dienst, kein GD) | Auth / Member |
| Auth | `webauthn` | gebündelter Snapshot | Passkeys | Auth |
| Mail | `mailer` | `8.0.8` | Mail-Versand | System |
| Mail | `mime` | `8.0.8` | MIME-Objekte / Anhänge | System |
| Mail | `egulias-email-validator` + `doctrine-lexer` | `4.0.4` / `3.0.2` | RFC-Adressvalidierung (Pflicht für `Mime\Address` / SMTP) | System |
| Search | `tntsearch` | `5.0.3` | Volltextsuche | Suche |
| SEO | `melbahja-seo` | gebündelter Snapshot | SEO-Helfer | SEO |
| Security | `htmlpurifier` | gebündelter Snapshot | XSS-Schutz | System |
| Security | `dompurify` + `js/cms-csp-runtime.js` | `3.4.16` | Trusted-Types-`default`-Policy, Style-Nonce für dynamische `<style>` | Admin / Member / Frontend |
| i18n | `translation` | `8.0.8` | Übersetzungen | System |
| i18n | `yaml` | `8.0.8` | Parser für `CMS/lang/*.yaml` | System |
| AI | `symfony/ai-platform` | `0.6.0` | AI-Plattform-Grundvertrag / Core-Adapter-Basis | System / AI Services |
| AI (transitiv) | `serializer`, `property-info`, `property-access`, `type-info`, `uid`, `string`, `event-dispatcher` | `8.0.8` | Abhängigkeiten der AI Platform | Transitiv |
| AI (transitiv) | `oskarstark-enum-helper`, `phpdocumentor-*`, `phpstan-phpdoc-parser`, `webmozart-assert`, `doctrine-deprecations` | aktuelle Stable | Abhängigkeiten der AI Platform | Transitiv |
| Util | `Carbon` | `3.11.4` (`src/Carbon` + `lazy/Carbon`) | Datum / Zeit | System |
| Util | `clock` + `psr/Clock` | `8.0.8` / `1.0.0` | Clock-Abstraktion für Carbon / AI | Transitiv |
| Util | `psr` | `Log` 3.0.2, `EventDispatcher` 1.0.0, `Container` 2.0.2, `Clock` 1.0.0 | PSR-Interfaces | Transitiv |
| Util | `symfony-contracts` | `3.6.1` | Service-/Translation-/EventDispatcher-/HttpClient-/Deprecation-Contracts | Transitiv |
| Util | `polyfill-*` | `1.3x`/`1.4x` | mbstring, ctype, intl-idn, intl-normalizer, intl-grapheme, uuid (nur ohne Extension aktiv) | Transitiv |
| PDF | `dompdf` | `3.1.5` | PDF-Erzeugung | System |
| Intern | `css/js/images` | intern | 365CMS-eigene Runtime-Dateien | Admin / Frontend / Member |
| Referenz | `msgraph` | Referenzstand | SDK-Ablage | aktuell nicht produktiv verdrahtet |

---

## Runtime-Details <!-- UPDATED: 2026-09-26 -->

Die produktive Detaildoku richtet sich nach den **Laufzeitpfaden in `CMS/assets/`** und dem dokumentierten Vendor-Sonderfall `CMS/vendor/dompdf/`.

Wichtig im aktuellen Stand:

- `ASSETS/` ist Quell- und Staging-Bereich, **nicht** der direkte Webroot-Assetpfad
- `editorjs` und `suneditor` sind keine simplen Ordnerkopien, sondern kuratierte bzw. gebaute Runtime-Sets
- `editorjs` benötigt für das produktive `delimiter.umd.js` bei neuen Plugin-Ständen einen gezielten Build-/Refresh-Pfad
- `tabler`, `tabler-icons` und `PhotoSwipe` werden nur mit ihren tatsächlich geladenen Dateien übernommen (keine RTL-/Map-/SVG-Font-/Varianten-Dateien)
- `tntsearch` liegt produktiv bewusst aufgeteilt in `tntsearchsrc/` und `tntsearchhelper/`
- `images/` enthält produktive Dashboard-, Logo- und Branding-Bestände
- `ai-platform/` enthält die produktiv registrierte Symfony-AI-Platform-Basis inklusive aller Pflichtabhängigkeiten; Provider-Bridges und `symfony/http-client` bleiben separat zu bewerten
- Bibliotheken werden ohne `Test*/`, `Command/`, `DataCollector/`, `DependencyInjection/`, Extractor- und Framework-Integrationsordner übernommen
- `msgraph/` bleibt Referenzablage, solange kein eigener produktiver Core-Service diese Bibliothek verdrahtet

---

## Interne Bild-Assets <!-- UPDATED: 2026-09-06 -->

Der produktive Bildbestand liegt unter `CMS/assets/images/` und umfasst aktuell
13 PNG-Dateien für Dashboard-Icons, Logos, Schriftzüge und Markenzeichen.

Die vollständige Dateiliste mit Abmessungen und Verwendungszweck steht in
[`assets/images/README.md`](images/README.md). Die übergeordnete Runtime-Struktur
ist zusätzlich in [`DOC/FILELIST.md`](../FILELIST.md) dokumentiert.

Für PHP-Referenzen gilt der zentrale Asset-Helper:

```php
cms_asset_url('images/LOGO_365CMS-75px.png')
```

---

## Pfad, Autoload und Sonderfälle <!-- UPDATED: 2026-09-26 -->

- **Runtime-Pfad:** `CMS/assets/`
- **Zentraler Autoloader:** `CMS/assets/autoload.php` – PSR-4-Map (`cms_vendor_psr4_map()`), Composer-`files`-Bootstraps (Polyfills, `trigger_deprecation()`, `Clock\now()`, `String\u()`) und Classmap für den `Normalizer`-Stub
- **Neue Library aufnehmen:** Paket ohne Tests/Tooling nach `CMS/assets/<name>/` kopieren, Präfix in `cms_vendor_psr4_map()` eintragen, ggf. `files` ergänzen und in `CMS/core/VendorRegistry.php` (Bundle + ggf. Funktions-Probe) registrieren
- **Vendor-Sonderfall:** `CMS/vendor/dompdf/` wird separat über `CMS/vendor/dompdf/autoload.php` geladen
- **SunEditor-Sonderfall:** Die Runtime wird aus `dist/` plus `src/langs/de.js` übernommen; fehlt `dist/` nach einem frischen Upstream-Download, müssen die Build-Artefakte zuerst lokal erzeugt werden
- **Editor.js-Sonderfall:** Der Runtime-Vertrag folgt `CMS/core/Services/EditorJs/EditorJsAssetService.php`, nicht dem kompletten Plugin-Baum; `delimiter.umd.js` wurde für den aktuellen Stand gezielt aus `editorjs-delimiter-version1.0.2` neu gebaut
- **JS/CSS-Ladung:** erfolgt weiterhin manuell über Admin-Partials, Theme-Templates und Service-spezifische Loader; es gibt keine zentrale Bundling-Pipeline

Zusätzliche Hinweise:

- `cookieconsent`, `filepond`, `elfinder`, `simplepie` und `gridjs` sind keine Runtime-Bundles mehr und wurden in `3.4.02` aus `CMS/assets/` entfernt
- die produktiv eingebundenen Symfony-8-Bundles deklarieren `PHP >= 8.4`
- `.htaccess` sperrt `vendor/` komplett sowie PHP-/Metadaten-/versteckte Dateien unter `assets/`
- **CSP-Vertrag:** Inline-`<script>`/`<style>` nur mit `Security::instance()->nonceAttr()`; keine Inline-Event-Handler (`onclick` …) – stattdessen `data-cms-*`-Attribute aus `js/cms-inline-actions.js`; `style="`-Attribute bleiben per CSP blockiert (`style-src-attr`); `CMS\Http\InlineStyleRewriter` (`core/Http/InlineStyleRewriter.php`, gestartet in `Bootstrap::run()`) überführt sie in vollständigen HTML-Antworten serverseitig in Klassen plus einen `<style nonce>`-Block vor `</head>`. Unsichere Werte (`{ } < > @ \`, Kommentare) bleiben blockiert; Fragmente, Downloads, Antworten mit `Content-Length`, gestreamte oder > 4 MB große Ausgaben bleiben unverändert; per JavaScript eingefügte Style-Attribute werden nicht umgeschrieben (dort CSSOM `el.style.*` nutzen). Tests: `TESTS/csp-style-rewriter/run.php`
- `js/tabler-bootstrap-bridge.js` stellt `window.bootstrap` für Tabler 1.4 (`window.tabler`) bereit
- seit `3.3.42` sind Tabler Icons produktiv lokal unter `CMS/assets/tabler-icons/` eingebunden; der Admin-Header lädt keine externen jsDelivr-/Tabler-Icon-Webfonts mehr
- `DOC/FILELIST.md` bleibt die lesbare Strukturreferenz für die aktuelle Runtime-Oberfläche

---

## Neue Kandidaten außerhalb der Runtime <!-- UPDATED: 2026-09-06 -->

Neu dokumentierte, aber noch nicht produktiv integrierte Pakete:

- `symfony/cache` unter `ASSETS/cache-8.0.8/`
- `guzzlehttp/guzzle` unter `ASSETS/guzzle-7.10.0/`
- `adhocore/jwt` unter `ASSETS/php-jwt_yuliyan_1.1.3/`

Diese Kandidaten sind im aktuellen Core **nicht aktiv verdrahtet**. Die Code- und Laufzeitprüfung zeigte hierfür keine produktiven Referenzen in `CMS/**`; deshalb wurden sie beim Refresh nach `3.4.00` bewusst nicht in die aktive Runtime übernommen.

Tabler Icons waren früher ebenfalls Beobachtungskandidat, sind seit `3.3.42` aber gezielt als lokales Runtime-Bundle nach `CMS/assets/tabler-icons/` übernommen und ersetzen dort den früheren CDN-Request.

Wichtig dazu: `symfony/ai-platform` gehört **nicht mehr** in diese Liste, weil die Basis jetzt bewusst produktiv unter `CMS/assets/ai-platform/` gespiegelt, über `CMS/assets/autoload.php` auflösbar gemacht und in `Diagnose -> Assets` registriert wurde.

Hinweis zum jüngsten Bereinigungsschritt:

- `stichoza/google-translate-php` wird **nicht mehr** als Staging-Kandidat mitgeführt und wurde aus `/ASSETS` entfernt.
- Für Übersetzungsfunktionen soll 365CMS stattdessen auf einen **AI-Services-Bereich mit Provider-Scope** setzen, nicht auf eine einzelne inoffizielle Crawling-Bibliothek.

Diese Kandidaten benötigen vor einer Aufnahme:

1. vollständige Dependency-Prüfung
2. Service-/Adapter-Schicht im Core
3. Security-/Rate-Limit-/Provider-Konzept
4. dokumentierte Entscheidung, ob lokales Bundling überhaupt sinnvoll ist

Die ausführliche Bewertungsdoku steht in [../ASSETS_NEW.md](../ASSETS_NEW.md). Die kanonische AI-Konzeption liegt zusätzlich in [../ai/AI-SERVICES.md](../ai/AI-SERVICES.md), ergänzt um den Admin-Kontext unter [../admin/system-settings/AI-SERVICES.md](../admin/system-settings/AI-SERVICES.md). Die Roadmap für Eigenersatz und Wrapper-Strategien steht in [../ASSETS_OwnAssets.md](../ASSETS_OwnAssets.md).
