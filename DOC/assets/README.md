# 365CMS Asset-Dokumentation
> **Stand:** 2026-03-28 | **Version:** 2.8.0 RC | **Status:** Aktuell

## Inhaltsverzeichnis
- <a>Tabellarische Übersicht</a>
- <a>Detail-Einträge</a>
- <a>Pfad & Autoload</a>
- <a>Hinweise & Besonderheiten</a>

---

## Tabellarische Übersicht <!-- UPDATED: 2026-03-28 -->
| Kategorie | Library | Version | Zweck | Eingebunden in |
|---|---|---|---|---|
| UI | tabler | 1.4.0 | Admin-UI-Framework | Admin / Member |
| UI | gridjs | 6.x | Tabellen | Admin |
| UI | photoswipe | 5.x | Lightbox | Frontend |
| Editor | editorjs | 2.x | Block-Editor | Admin/Frontend |
| Editor | suneditor | 2.x | Legacy WYSIWYG | Admin |
| Media (Legacy-Bestand) | elfinder | 2.1.x | ehem. Dateimanager, nicht mehr aktiv verdrahtet | Altbestand |
| Media (Legacy-Bestand) | filepond | 4.x | ehem. Upload-UI, nicht mehr aktiv verdrahtet | Altbestand |
| Auth | php-jwt | 6.x | JWT | API/Auth |
| Auth | ldaprecord | 4.x | LDAP/AD | Auth |
| Auth | twofactorauth | 1.x | TOTP | Auth |
| Auth | webauthn | 3.x | Passkeys | Auth |
| Mail | symfony-mailer | 6.x | Mail-Versand | System |
| Mail | symfony-mime | 6.x | MIME | System |
| Mail (Legacy) | mailer (legacy) | 1.x | Alt-Mailer | Legacy |
| Search | tntsearch | 5.x | Volltextsuche | Suche |
| SEO | melbahja-seo | 1.x | SEO-Helfer | SEO |
| SEO/Security | htmlpurifier | 4.x | XSS-Schutz | System |
| i18n | translation | 6.x | Übersetzungen | System |
| Util | carbon | 2.x | Datum/Zeit | System |
| Util | mime | 6.x | MIME-Typen | Mail/Upload |
| Util | psr | 3.x | PSR-Interfaces | Transitiv |
| Consent (Legacy-Bestand) | cookieconsent | 3.x | ehem. DSGVO-Banner-Runtime, aktuell durch native Assets ersetzt | Altbestand |
| Feed (Legacy-Bestand) | simplepie | 1.9.x | ehem. RSS/Atom-Parser, aktuell nicht aktiv verdrahtet | Altbestand |
| Intern | css/js/images | – | interne Assets | Admin/Frontend |
| Referenz | msgraph | – | SDK-Notizen | Referenz, nicht aktiv |

---

## Detail-Einträge <!-- UPDATED: 2026-03-28 -->
Jede Unterdatei `DOC/assets/<lib>/README.md` beschreibt den aktuellen Dokumentationsstand, aktive Nutzung, Altbestände und besondere Integrationshinweise.

Wichtig im Stand 2.8.0 RC:

- `cookieconsent/`, `filepond/`, `elfinder/` und `simplepie/` bleiben im Repository dokumentiert, sind aber keine aktiven Laufzeitpfade mehr
- Medien-, Picker-, Consent- und Feed-Logik hängt an nativen 365CMS-Services und JS-Dateien
- aktive JS-Hotspots sind insbesondere `admin.js`, `admin-media-integrations.js`, `member-dashboard.js`, `cookieconsent-init.js` und `photoswipe-init.js`

---

## Pfad & Autoload <!-- UPDATED: 2026-03-28 -->
- Runtime-Pfad: `CMS/assets/` (wird deployt).
- Autoloader: `CMS/assets/autoload.php` (primär) – lädt PHP-Libs wie HTMLPurifier, TNTSearch, Carbon, Translation, JWT und WebAuthn
- `SimplePie` und `elFinder` sind dort nicht mehr aktiv eingebunden
- Staging: `ASSETS/` nur als Quelle/Entwicklung, nicht produktiv.
- JS/CSS: Werden manuell über Admin-Partials oder Theme-Templates referenziert; keine zentrale Bundling-Pipeline.

---

## Hinweise & Besonderheiten <!-- UPDATED: 2026-03-28 -->
- Cookie-Consent rendert nativ per `CMS/core/Services/CookieConsentService.php` + `CMS/assets/js/cookieconsent-init.js`
- Admin- und Member-Medien setzen auf native Bibliotheks- und Upload-Flows statt auf aktive FilePond-/elFinder-Integration
- Feed-Verarbeitung läuft nativ über `CMS/core/Services/FeedService.php`; die SimplePie-Bundles sind nur noch Altbestand
- Die produktiv eingebundenen Symfony-Bundles `mailer`, `mime` und `translation` deklarieren `PHP >= 8.4`; das Projekt dokumentiert und prüft diese Mindestplattform zentral

---

## Audit-Notiz zur Integration <!-- UPDATED: 2026-03-09 -->
- Runtime-Assets werden im Hauptsystem nicht komplett einheitlich referenziert: neben `ASSETS_URL` existieren direkte `SITE_URL . '/assets'`-Verkettungen und einzelne feste Versionsstrings.
- `CMS/core/Services/EditorJsService.php` ist aktuell das sauberste Beispiel für Datei-Existenzprüfung + `filemtime()`-basiertes Cache-Busting.
- Die Altbestände `cookieconsent/`, `filepond/`, `elfinder/` und `simplepie/` liegen noch im Repository, sind aber nach Folge-Batch 454 nicht mehr aktiv an Consent-, Upload-, Picker- oder Feed-Laufzeitpfade verdrahtet.
- Dompdf ist kein Teil des `CMS/assets`-Autoloads, sondern ein dokumentierter Sonderfall unter `CMS/vendor/dompdf/`.
- Die produktiv eingebundenen Symfony-Bundles `mailer`, `mime` und `translation` deklarieren aktuell `PHP >= 8.4` in ihren Composer-Metadaten; 365CMS führt diese Information jetzt offiziell als Mindestplattform und prüft sie zusätzlich zentral im Bootstrap gegen die aktive Runtime.
- Auch externe Workspace-Repos koppeln teils direkt an Core-Assets: Theme-Customizer laden `assets/css/admin.css` / `assets/js/admin.js` direkt, `cms-experts` nutzt `assets/suneditor/css/suneditor.min.css`, und `cms-jobprofile-generator` bindet Core-CSS in eigenen Member-Layouts fest ein.
