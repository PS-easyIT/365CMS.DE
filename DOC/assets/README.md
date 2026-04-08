# 365CMS Asset-Dokumentation
> **Stand:** 2026-04-08 | **Version:** 2.9.2 | **Status:** Aktuell

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
- `editorjs` benötigt für das produktive `delimiter.umd.js` bei neuen Plugin-Ständen einen gezielten Build-/Refresh-Pfad
- `tabler`, `gridjs` und `PhotoSwipe` werden nur mit ihren auslieferbaren Dateien übernommen
- `tntsearch` liegt produktiv bewusst aufgeteilt in `tntsearchsrc/` und `tntsearchhelper/`
- `images/` enthält produktive Dashboard-, Logo- und Branding-Bestände
- `msgraph/` bleibt Referenzablage, solange kein eigener produktiver Core-Service diese Bibliothek verdrahtet

---

## Pfad, Autoload und Sonderfälle <!-- UPDATED: 2026-04-08 -->

- **Runtime-Pfad:** `CMS/assets/`
- **Zentraler Autoloader:** `CMS/assets/autoload.php`
- **Vendor-Sonderfall:** `CMS/vendor/dompdf/` wird separat über `CMS/vendor/dompdf/autoload.php` geladen
- **SunEditor-Sonderfall:** Die Runtime wird aus `dist/` plus `src/langs/de.js` übernommen; fehlt `dist/` nach einem frischen Upstream-Download, müssen die Build-Artefakte zuerst lokal erzeugt werden
- **Editor.js-Sonderfall:** Der Runtime-Vertrag folgt `CMS/core/Services/EditorJs/EditorJsAssetService.php`, nicht dem kompletten Plugin-Baum; `delimiter.umd.js` wurde für den aktuellen Stand gezielt aus `editorjs-delimiter-version1.0.2` neu gebaut
- **JS/CSS-Ladung:** erfolgt weiterhin manuell über Admin-Partials, Theme-Templates und Service-spezifische Loader; es gibt keine zentrale Bundling-Pipeline

Zusätzliche Hinweise:

- `cookieconsent`, `filepond`, `elfinder` und `simplepie` sind keine aktiven Runtime-Bundles mehr
- die produktiv eingebundenen Symfony-Bundles `mailer`, `mime` und `translation` deklarieren `PHP >= 8.4`
- `DOC/FILELIST.md` bleibt die lesbare Strukturreferenz für die aktuelle Runtime-Oberfläche

---

## Neue Kandidaten außerhalb der Runtime <!-- UPDATED: 2026-04-08 -->

Neu dokumentierte, aber noch nicht produktiv integrierte Pakete:

- `symfony/ai-platform` unter `ASSETS/ai-platform-0.6.0/`
- `symfony/cache` unter `ASSETS/cache-8.0.8/`
- `guzzlehttp/guzzle` unter `ASSETS/guzzle-7.10.0/`
- `adhocore/jwt` unter `ASSETS/php-jwt_yuliyan_1.1.3/`
- `tabler-icons-3.41.1` unter `ASSETS/tabler-core-1.4.0/tabler-icons-3.41.1/`

Diese Kandidaten sind im aktuellen Core **nicht aktiv verdrahtet**. Die Code- und Laufzeitprüfung zeigte hierfür keine produktiven Referenzen in `CMS/**`; deshalb wurden sie beim Refresh nach `2.9.2` bewusst nicht in die aktive Runtime übernommen.

Hinweis zum jüngsten Bereinigungsschritt:

- `stichoza/google-translate-php` wird **nicht mehr** als Staging-Kandidat mitgeführt und wurde aus `/ASSETS` entfernt.
- Für Übersetzungsfunktionen soll 365CMS stattdessen auf einen **AI-Services-Bereich mit Provider-Scope** setzen, nicht auf eine einzelne inoffizielle Crawling-Bibliothek.

Diese Kandidaten benötigen vor einer Aufnahme:

1. vollständige Dependency-Prüfung
2. Service-/Adapter-Schicht im Core
3. Security-/Rate-Limit-/Provider-Konzept
4. dokumentierte Entscheidung, ob lokales Bundling überhaupt sinnvoll ist

Die ausführliche Bewertungsdoku steht in [../ASSETS_NEW.md](../ASSETS_NEW.md). Die AI-Admin-Konzeption liegt zusätzlich in [../admin/system-settings/AI-SERVICES.md](../admin/system-settings/AI-SERVICES.md). Die Roadmap für Eigenersatz und Wrapper-Strategien steht in [../ASSETS_OwnAssets.md](../ASSETS_OwnAssets.md).
