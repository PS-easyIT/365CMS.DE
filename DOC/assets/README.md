# 365CMS Asset-Dokumentation
> **Stand:** 2026-04-07 | **Version:** 2.9.0 | **Status:** Aktuell

## Inhaltsverzeichnis
- <a>Tabellarische Übersicht</a>
- <a>Detail-Einträge</a>
- <a>Pfad & Autoload</a>
- <a>Hinweise & Besonderheiten</a>

---

## Tabellarische Übersicht <!-- UPDATED: 2026-04-07 -->
| Kategorie | Library | Version | Zweck | Eingebunden in |
|---|---|---|---|---|
| UI | tabler | 1.4.0 | Admin-UI-Framework | Admin / Member |
| UI | gridjs | 6.x | Tabellen | Admin |
| UI | photoswipe | 5.x | Lightbox | Frontend |
| Editor | editorjs | 2.x | Block-Editor | Admin/Frontend |
| Editor | suneditor | 2.x | Legacy WYSIWYG | Admin |
| Auth | php-jwt | 6.x | JWT | API/Auth |
| Auth | ldaprecord | 4.x | LDAP/AD | Auth |
| Auth | twofactorauth | 1.x | TOTP | Auth |
| Auth | webauthn | 3.x | Passkeys | Auth |
| Mail | mailer | Symfony Mailer | Mail-Versand | System |
| Mail | mime | Symfony Mime | MIME-Objekte & Anhänge | System |
| Search | tntsearch | 5.x | Volltextsuche | Suche |
| SEO | melbahja-seo | 1.x | SEO-Helfer | SEO |
| SEO/Security | htmlpurifier | 4.x | XSS-Schutz | System |
| i18n | translation | 6.x | Übersetzungen | System |
| Util | carbon | 2.x | Datum/Zeit | System |
| Util | psr | 3.x | PSR-Interfaces | Transitiv |
| Intern | css/js/images | – | interne Assets und Branding-/Runtime-Bestand | Admin/Frontend/Member |
| Referenz | msgraph | – | SDK-Ablage in `CMS/assets`, aktuell nicht produktiv verdrahtet | Referenz |

---

## Detail-Einträge <!-- UPDATED: 2026-04-07 -->
Die führende Detaildoku richtet sich nach den **produktiven Laufzeitpfaden in `CMS/assets/`**. Sammel-Readmes wie `simplepie/` und `tntsearch/` dokumentieren dabei bewusst mehrere Runtime-Unterordner (`simplepielibrary/` + `simplepiesrc` bzw. `tntsearchhelper/` + `tntsearchsrc`).

Wichtig im Stand 2.9.0:

- `cookieconsent/`, `filepond/`, `elfinder/` und `simplepie/` bleiben im Repository dokumentiert, sind aber keine aktiven Laufzeitpfade mehr
- Medien-, Picker-, Consent- und Feed-Logik hängt an nativen 365CMS-Services und JS-Dateien
- aktive JS-Hotspots sind insbesondere `admin.js`, `admin-media-integrations.js`, `member-dashboard.js`, `cookieconsent-init.js`, `photoswipe-init.js`, `admin-content-editor.js`, `admin-seo-redirects.js` und `admin-system-cron.js`
- `examples/` ist absichtlich kein Runtime-Bundle, sondern ein versionierter Doku-/Fixture-Pfad
- `msgraph/` bleibt reine Referenzablage, solange keine produktive Graph-Integration über einen eigenen Service verdrahtet ist

Zusätzlich zum Überblick wichtig:

- `images/` enthält produktive Dashboard- und Logo-Bestände
- `editorjs/`, `suneditor/`, `tabler/`, `htmlpurifier/` und `photoswipe/` besitzen eigene tiefere Unterstrukturen, nicht nur Einzelfiles

---

## Pfad & Autoload <!-- UPDATED: 2026-04-07 -->
- Runtime-Pfad: `CMS/assets/` (wird deployt).
- Autoloader: `CMS/assets/autoload.php` (primär) – lädt PHP-Libs wie HTMLPurifier, TNTSearch, Carbon, Translation, JWT und WebAuthn
- `SimplePie` und `elFinder` sind dort nicht mehr aktiv eingebunden
- Staging: `ASSETS/` nur als Quelle/Entwicklung, nicht produktiv.
- Im Repo-Root `ASSETS/` liegen zusätzlich Import-/Downloadstände wie `dompdf/`, `editor.js-2.31.4/`, `tabler--tabler-core-1.4.0/`, `msgraph-sdk-php-2.56.0/` und `msgraph-training-php-main/`; diese Ordner sind keine führenden Laufzeitpfade.
- JS/CSS: Werden manuell über Admin-Partials oder Theme-Templates referenziert; keine zentrale Bundling-Pipeline.
- Für die aktuelle lesbare Runtime-Struktur von `CMS/assets/` ist zusätzlich `DOC/FILELIST.md` führend.

---

## Hinweise & Besonderheiten <!-- UPDATED: 2026-04-07 -->
- Cookie-Consent rendert nativ per `CMS/core/Services/CookieConsentService.php` + `CMS/assets/js/cookieconsent-init.js`
- Admin- und Member-Medien setzen auf native Bibliotheks- und Upload-Flows statt auf aktive FilePond-/elFinder-Integration
- Feed-Verarbeitung läuft nativ über `CMS/core/Services/FeedService.php`; die SimplePie-Bundles sind nur noch Altbestand
- Die produktiv eingebundenen Symfony-Bundles `mailer`, `mime` und `translation` deklarieren `PHP >= 8.4`; das Projekt dokumentiert und prüft diese Mindestplattform zentral
- Die Roadmap für Eigenersatz und schrittweise Entkopplung fremder Assets steht in [../ASSETS_OwnAssets.md](../ASSETS_OwnAssets.md)

---

## Audit-Notiz zur Integration <!-- UPDATED: 2026-04-07 -->
- Runtime-Assets werden im Hauptsystem nicht komplett einheitlich referenziert: neben `ASSETS_URL` existieren direkte `SITE_URL . '/assets'`-Verkettungen und einzelne feste Versionsstrings.
- `CMS/core/Services/EditorJsService.php` ist aktuell das sauberste Beispiel für Datei-Existenzprüfung + `filemtime()`-basiertes Cache-Busting.
- Die Altbestände `cookieconsent/`, `filepond/`, `elfinder/` und `simplepie/` liegen noch im Repository, sind aber nach Folge-Batch 454 nicht mehr aktiv an Consent-, Upload-, Picker- oder Feed-Laufzeitpfade verdrahtet.
- Dompdf ist kein Teil des `CMS/assets`-Autoloads, sondern ein dokumentierter Sonderfall unter `CMS/vendor/dompdf/`.
- Die produktiv eingebundenen Symfony-Bundles `mailer`, `mime` und `translation` deklarieren aktuell `PHP >= 8.4` in ihren Composer-Metadaten; 365CMS führt diese Information jetzt offiziell als Mindestplattform und prüft sie zusätzlich zentral im Bootstrap gegen die aktive Runtime.
- Der Asset-Bereich umfasst im aktuellen Runtime-Bild mehr als den früheren Audit-Scope `assets/css` und `assets/js`; insbesondere Bilder und Bibliotheksunterstrukturen sind für die echte Laufzeit ebenfalls relevant.
- Auch externe Workspace-Repos koppeln teils direkt an Core-Assets: Theme-Customizer laden `assets/css/admin.css` / `assets/js/admin.js` direkt, `cms-experts` nutzt `assets/suneditor/css/suneditor.min.css`, und `cms-jobprofile-generator` bindet Core-CSS in eigenen Member-Layouts fest ein.
