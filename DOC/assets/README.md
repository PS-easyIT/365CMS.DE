# 365CMS Asset-Dokumentation
> **Stand:** 2026-04-08 | **Version:** 2.9.1 | **Status:** Aktuell

## Inhaltsverzeichnis
- <a>Tabellarische Übersicht</a>
- <a>Runtime-Details</a>
- <a>Pfad, Autoload und Sonderfälle</a>
- <a>Neue Kandidaten außerhalb der Runtime</a>

---

## Tabellarische Übersicht <!-- UPDATED: 2026-04-08 -->
| Kategorie | Library | Runtime-Stand | Zweck | Eingebunden in |
|---|---|---|---|---|
| UI | `tabler` | `1.4.0` | Admin-UI-Framework | Admin / Member |
| UI | `gridjs` | gebündelter Snapshot | Tabellen / Grids | Admin |
| UI | `photoswipe` | `5.x`-Build | Lightbox | Frontend |
| Editor | `editorjs` | `2.31.6` | Block-Editor | Admin / Frontend |
| Editor | `suneditor` | `3.0.5` | Legacy-WYSIWYG | Admin |
| Auth | `php-jwt` | gebündelter Snapshot | JWT | API / Auth |
| Auth | `ldaprecord` | `4.0.3` | LDAP / AD | Auth |
| Auth | `twofactorauth` | gebündelter Snapshot | TOTP | Auth |
| Auth | `webauthn` | gebündelter Snapshot | Passkeys | Auth |
| Mail | `mailer` | `8.0.8` | Mail-Versand | System |
| Mail | `mime` | `8.0.8` | MIME-Objekte / Anhänge | System |
| Search | `tntsearch` | `5.0.3` | Volltextsuche | Suche |
| SEO | `melbahja-seo` | gebündelter Snapshot | SEO-Helfer | SEO |
| Security | `htmlpurifier` | gebündelter Snapshot | XSS-Schutz | System |
| i18n | `translation` | `8.0.8` | Übersetzungen | System |
| Util | `Carbon` | `3.11.4` | Datum / Zeit | System |
| Util | `psr` | gebündelter Snapshot | PSR-Interfaces | Transitiv |
| PDF | `dompdf` | `3.1.5` | PDF-Erzeugung | System |
| Intern | `css/js/images` | intern | 365CMS-eigene Runtime-Dateien | Admin / Frontend / Member |
| Referenz | `msgraph` | Referenzstand | SDK-Ablage | aktuell nicht produktiv verdrahtet |

---

## Runtime-Details <!-- UPDATED: 2026-04-08 -->

Die produktive Detaildoku richtet sich nach den **Laufzeitpfaden in `CMS/assets/`** und dem dokumentierten Vendor-Sonderfall `CMS/vendor/dompdf/`.

Wichtig im aktuellen Stand:

- `ASSETS/` ist Quell- und Staging-Bereich, **nicht** der direkte Webroot-Assetpfad
- `editorjs` und `suneditor` sind keine simplen Ordnerkopien, sondern kuratierte bzw. gebaute Runtime-Sets
- `tabler`, `gridjs` und `PhotoSwipe` werden nur mit ihren auslieferbaren Dateien übernommen
- `tntsearch` liegt produktiv bewusst aufgeteilt in `tntsearchsrc/` und `tntsearchhelper/`
- `images/` enthält produktive Dashboard-, Logo- und Branding-Bestände
- `msgraph/` bleibt Referenzablage, solange kein eigener produktiver Core-Service diese Bibliothek verdrahtet

---

## Pfad, Autoload und Sonderfälle <!-- UPDATED: 2026-04-08 -->

- **Runtime-Pfad:** `CMS/assets/`
- **Zentraler Autoloader:** `CMS/assets/autoload.php`
- **Vendor-Sonderfall:** `CMS/vendor/dompdf/` wird separat über `CMS/vendor/dompdf/autoload.php` geladen
- **SunEditor-Sonderfall:** Das Paket unter `ASSETS/suneditor-3.0.5/` enthält im Snapshot keinen fertigen `dist/`-Stand; für die Runtime müssen `suneditor.min.js` und `suneditor.min.css` zuerst lokal gebaut werden
- **Editor.js-Sonderfall:** Der Runtime-Vertrag folgt `CMS/core/Services/EditorJs/EditorJsAssetService.php`, nicht dem kompletten Plugin-Baum
- **JS/CSS-Ladung:** erfolgt weiterhin manuell über Admin-Partials, Theme-Templates und Service-spezifische Loader; es gibt keine zentrale Bundling-Pipeline

Zusätzliche Hinweise:

- `cookieconsent`, `filepond`, `elfinder` und `simplepie` sind keine aktiven Runtime-Bundles mehr
- die produktiv eingebundenen Symfony-Bundles `mailer`, `mime` und `translation` deklarieren `PHP >= 8.4`
- `DOC/FILELIST.md` bleibt die lesbare Strukturreferenz für die aktuelle Runtime-Oberfläche

---

## Neue Kandidaten außerhalb der Runtime <!-- UPDATED: 2026-04-08 -->

Neu dokumentierte, aber noch nicht produktiv integrierte Pakete:

- `symfony/ai-platform` unter `ASSETS/ai-platform-0.6.0/`
- `stichoza/google-translate-php` unter `ASSETS/google-translate-php-5.3.0/`

Diese Kandidaten benötigen vor einer Aufnahme:

1. vollständige Dependency-Prüfung
2. Service-/Adapter-Schicht im Core
3. Security-/Rate-Limit-/Provider-Konzept
4. dokumentierte Entscheidung, ob lokales Bundling überhaupt sinnvoll ist

Die ausführliche Bewertungsdoku steht in [../ASSETS_NEW.md](../ASSETS_NEW.md). Die Roadmap für Eigenersatz und Wrapper-Strategien steht in [../ASSETS_OwnAssets.md](../ASSETS_OwnAssets.md).
