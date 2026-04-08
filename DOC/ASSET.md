# ASSET-Übersicht 365CMS
> **Stand:** 2026-04-08 | **Version:** 2.9.1 | **Status:** Aktuell

## Inhaltsverzeichnis
- <a>Aktive Runtime-Bundles</a>
- <a>Synchronisationsregeln</a>
- <a>CSS- und JavaScript-Architektur</a>
- <a>Build- und Update-Workflow</a>
- <a>Neue Kandidaten außerhalb der Runtime</a>

---

## Aktive Runtime-Bundles <!-- UPDATED: 2026-04-08 -->
Führende Quelle für **aktive Laufzeitpfade** ist `CMS/assets/` sowie der dokumentierte Sonderfall `CMS/vendor/dompdf/`. Das Root-Verzeichnis `ASSETS/` ist **Staging-/Quellmaterial**, nicht die produktive Wahrheit.

| Library | Runtime-Stand | Zweck | Produktiver Pfad | Quelle in `/ASSETS` | Hinweis |
|---|---|---|---|---|---|
| `tabler` | `1.4.0` | Admin-/Member-UI | `CMS/assets/tabler/` | `ASSETS/tabler-core-1.4.0/core/dist/` | nur gebaute `css/`, `js/`, `img/` übernehmen |
| `editorjs` | `2.31.6` | Block-Editor | `CMS/assets/editorjs/` | `ASSETS/editor.js-2.31.6/` | kuratierter Runtime-Satz aus Core- und Plugin-Builds |
| `suneditor` | `3.0.5` | Legacy-WYSIWYG | `CMS/assets/suneditor/` | `ASSETS/suneditor-3.0.5/` | Runtime entsteht aus lokal gebautem `dist/` + `src/langs/de.js` |
| `gridjs` | gebündelter Snapshot | Tabellen / Grids | `CMS/assets/gridjs/` | `ASSETS/gridjs/` | nur auslieferbare Build-Dateien übernehmen |
| `photoswipe` | `5.x`-Build | Lightbox | `CMS/assets/photoswipe/` | `ASSETS/PhotoSwipe/` | nur produktive Frontend-Dateien übernehmen |
| `Carbon` | `3.11.4` | Datum / Zeit | `CMS/assets/Carbon/` | `ASSETS/Carbon-3.11.4/src/Carbon/` | PSR-4-Verzeichnis direkt gespiegelt |
| `ldaprecord` | `4.0.3` | LDAP / Active Directory | `CMS/assets/ldaprecord/` | `ASSETS/LdapRecord-4.0.3/src/` | kompletter Source-Ordner für PSR-4 |
| `mailer` | `8.0.8` | Mailversand | `CMS/assets/mailer/` | `ASSETS/mailer-8.0.8/` | Symfony-Komponente |
| `mime` | `8.0.8` | MIME / Anhänge | `CMS/assets/mime/` | `ASSETS/mime-8.0.8/` | Symfony-Komponente |
| `translation` | `8.0.8` | i18n | `CMS/assets/translation/` | `ASSETS/translation-8.0.8/` | Symfony-Komponente |
| `tntsearch` | `5.0.3` | Volltextsuche | `CMS/assets/tntsearchsrc/`, `CMS/assets/tntsearchhelper/` | `ASSETS/tntsearch-5.0.3/` | `src/` und `helper/helpers.php` getrennt gespiegelt |
| `php-jwt` | gebündelter Snapshot | JWT | `CMS/assets/php-jwt/` | `ASSETS/php-jwt/` | produktiv lokal gebündelt |
| `twofactorauth` | gebündelter Snapshot | TOTP / 2FA | `CMS/assets/twofactorauth/` | `ASSETS/twofactorauth/` | sicherheitskritisch, nur kapseln |
| `webauthn` | gebündelter Snapshot | Passkeys / WebAuthn | `CMS/assets/webauthn/` | `ASSETS/webauthn/` | sicherheitskritisch, nur kapseln |
| `htmlpurifier` | gebündelter Snapshot | XSS-Schutz | `CMS/assets/htmlpurifier/` | `ASSETS/htmlpurifier/` | produktive Sanitizer-Basis |
| `melbahja-seo` | gebündelter Snapshot | SEO-Helfer | `CMS/assets/melbahja-seo/` | `ASSETS/melbahja-seo/` | mittelfristig in Core-Services zerlegbar |
| `psr` | gebündelter Snapshot | PSR-Interfaces | `CMS/assets/psr/` | `ASSETS/psr/` | transitive Basisschnittstellen |
| `dompdf` | `3.1.5` | PDF-Erzeugung | `CMS/vendor/dompdf/` | `ASSETS/dompdf-3.1.5/dompdf/vendor/` | **kein** `CMS/assets`-Bundle, sondern Vendor-Sonderfall |
| `css/js/images` | intern | 365CMS-eigene Runtime-Assets | `CMS/assets/css/`, `CMS/assets/js/`, `CMS/assets/images/` | `ASSETS/css/`, `ASSETS/js/`, `ASSETS/images/` | kein Third-Party-Bundle, aber Teil der Asset-Synchronisation |
| `msgraph` | Referenzbestand | SDK-Ablage | `CMS/assets/msgraph/` | `ASSETS/msgraph-sdk-php-2.56.0/` | aktuell nicht als aktive Runtime-Integration dokumentiert |

Zusätzlich produktiv relevant:

- `CMS/assets/autoload.php` als zentraler PHP-Asset-Autoloader
- `CMS/core/VendorRegistry.php` als Diagnose-/Ladevertrag für gebündelte Libraries
- `CMS/core/Services/PdfService.php` als dokumentierter Dompdf-Einstieg

---

## Synchronisationsregeln <!-- UPDATED: 2026-04-08 -->

Die Synchronisation von `/ASSETS` nach `/CMS/assets` ist **selektiv**, nicht spiegelnd. Ein vollständiges Rekursiv-Kopieren ganzer Upstream-Repositories würde Tests, Build-Tooling, `node_modules`, Dokumentation oder falsche Laufzeitpfade mit in die Runtime tragen.

Wichtige Regeln im aktuellen Stand:

1. **`ASSETS/` ist Staging, `CMS/assets/` ist Runtime.**
2. **Frontend-Bundles nur als Build-Artefakte übernehmen.** Das gilt insbesondere für `tabler`, `gridjs`, `PhotoSwipe`, `editor.js` und `suneditor`.
3. **`editorjs` bleibt kuratiert.** Die Runtime orientiert sich an `CMS/core/Services/EditorJs/EditorJsAssetService.php`, nicht am gesamten Plugin-Baum.
4. **`suneditor` ist ein Sonderfall.** Das aktuelle Source-Paket unter `ASSETS/suneditor-3.0.5/` enthält keinen fertigen `dist/`-Stand im Snapshot. Für die Runtime müssen daher `suneditor.min.js` und `suneditor.min.css` zuerst lokal gebaut und anschließend nach `CMS/assets/suneditor/` kopiert werden; die Sprachdatei `de.js` kommt direkt aus `src/langs/`.
5. **`dompdf` bleibt außerhalb von `CMS/assets/`.** Der produktive Pfad ist `CMS/vendor/dompdf/`, geladen über `CMS/vendor/dompdf/autoload.php`.
6. **Sicherheits- und Standard-Bibliotheken nicht umstrukturieren, solange der Autoload-Vertrag stabil bleiben muss.** Das betrifft u. a. `htmlpurifier`, `php-jwt`, `webauthn`, `twofactorauth`, `ldaprecord`, `mailer`, `mime` und `translation`.

---

## CSS- und JavaScript-Architektur <!-- UPDATED: 2026-04-08 -->

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

## Build- und Update-Workflow <!-- UPDATED: 2026-04-08 -->

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

## Neue Kandidaten außerhalb der Runtime <!-- UPDATED: 2026-04-08 -->

Folgende Pakete liegen unter `/ASSETS`, sind aber **nicht** produktiv in `CMS/assets/` bzw. `CMS/vendor/` integriert:

- `symfony/ai-platform` (`ASSETS/ai-platform-0.6.0/`)
- `stichoza/google-translate-php` (`ASSETS/google-translate-php-5.3.0/`)
- weitere Beobachtungskandidaten wie `cache-8.0.8`, `monolog-bundle-4.0.2`, `msgraph-sdk-php-2.56.0`

Für diese Kandidaten gilt:

- **nicht blind in die Runtime kopieren**
- zunächst Service-/Adapter-Schnittstellen im Core definieren
- transitive Abhängigkeiten vollständig bewerten
- Betriebsrisiken, Provider-Abhängigkeiten und Secrets-/Rate-Limits vor Integration dokumentieren

Die ausführliche Bewertungs- und Integrationsdoku dazu liegt in [ASSETS_NEW.md](ASSETS_NEW.md).

---

## Audit-Notiz zur Runtime-Integration <!-- UPDATED: 2026-04-08 -->

- Die produktive PHP-Dependency-Ladung erfolgt überwiegend über `CMS/assets/autoload.php`.
- Die dokumentierte Ausnahme bleibt `CMS/vendor/dompdf/autoload.php`.
- Die produktiv eingebundenen Symfony-Bundles `mailer`, `mime` und `translation` deklarieren `PHP >= 8.4`; diese Mindestplattform ist deshalb Teil des offiziellen Runtime-Vertrags.
- Besonders update-sensibel bleiben Editor- und UI-Bundles mit Build-Artefakten (`editorjs`, `suneditor`, `tabler`, `photoswipe`, `gridjs`).
- Für künftige Pflege wäre eine kleine zentrale Asset-/Versionierungs-Registry sinnvoll, damit Pfadlogik, Existenzprüfung und Cache-Busting nicht über viele Dateien verstreut bleiben.
