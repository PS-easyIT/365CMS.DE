# 365CMS Asset-Dokumentation
> **Stand:** 2026-03-08 | **Version:** 2.5.4 | **Status:** Aktuell

## Inhaltsverzeichnis
- <a>Tabellarische Übersicht</a>
- <a>Detail-Einträge</a>
- <a>Pfad & Autoload</a>
- <a>Hinweise & Besonderheiten</a>

---

## Tabellarische Übersicht <!-- UPDATED: 2026-03-08 -->
| Kategorie | Library | Version | Zweck | Eingebunden in |
|---|---|---|---|---|
| UI | tabler | 1.0.x | Admin UI Framework | Admin |
| UI | gridjs | 6.x | Tabellen | Admin |
| UI | photoswipe | 5.x | Lightbox | Frontend |
| Editor | editorjs | 2.x | Block-Editor | Admin/Frontend |
| Editor | suneditor | 2.x | Legacy WYSIWYG | Admin |
| Media | elfinder | 2.1.x | Dateimanager | Admin |
| Media | filepond | 4.x | Upload | Admin/Frontend |
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
| Consent | cookieconsent | 3.x | DSGVO-Banner | Frontend/Admin |
| Feed | simplepie | 1.9.x | RSS/Atom | Feeds |
| Intern | css/js/images | – | interne Assets | Admin/Frontend |
| Referenz | msgraph | – | SDK-Notizen | Referenz, nicht aktiv |

---

## Detail-Einträge <!-- UPDATED: 2026-03-08 -->
Jede Unterdatei `DOC/assets/<lib>/README.md` beschreibt:
- **Version** (lokaler Stand), **Zweck**, **Eingebunden in** (Admin/Frontend/Beide), **Konfiguration** (Pfad zur Config/Service), **Verwendung** (Code-Beispiel), **Besonderheiten**, **Offizielle Doku**.

Aktuelle Unterordner:
- carbon/, cookieconsent/, css/, editorjs/, elfinder/, filepond/, gridjs/, htmlpurifier/, images/, js/, ldaprecord/, mailer/, melbahja-seo/, mime/, photoswipe/, php-jwt/, psr/, simplepie/, suneditor/, tabler/, tntsearch/, translation/, twofactorauth/, webauthn/

---

## Pfad & Autoload <!-- UPDATED: 2026-03-08 -->
- Runtime-Pfad: `CMS/assets/` (wird deployt).
- Autoloader: `CMS/assets/autoload.php` (primär) – lädt PHP-Libs (z. B. HTMLPurifier, SimplePie, TNTSearch, Carbon, Translation, JWT, WebAuthn).
- Staging: `ASSETS/` nur als Quelle/Entwicklung, nicht produktiv.
- JS/CSS: Werden manuell über Admin-Partials oder Theme-Templates referenziert; keine zentrale Bundling-Pipeline.

---

## Hinweise & Besonderheiten <!-- UPDATED: 2026-03-08 -->
- CookieConsent rendert per `CMS/core/Services/CookieConsentService.php` + `CMS/assets/js/cookieconsent-init.js`, nutzt Hooks `head`/`body_end` (auch im Admin-Layout verfügbar).
- PhotoSwipe v5 wird über `CMS/assets/js/photoswipe-init.js` initialisiert; CSS/JS lokal.
- Mail-Stack: `mailer/` + `mime/` + `psr/`; Queue/Log über `MailQueueService`/`MailLogService`.
- Search: TNTSearch nutzt lokale PHP-Libs; Indizes liegen außerhalb der DB (Filesystem).
- Legacy/Reserve: `msgraph/` nur Doku/Notizen, kein Autoload; `mailer (legacy)` nur Fallback.

---

## Audit-Notiz zur Integration <!-- UPDATED: 2026-03-09 -->
- Runtime-Assets werden im Hauptsystem nicht komplett einheitlich referenziert: neben `ASSETS_URL` existieren direkte `SITE_URL . '/assets'`-Verkettungen und einzelne feste Versionsstrings.
- `CMS/core/Services/EditorJsService.php` ist aktuell das sauberste Beispiel für Datei-Existenzprüfung + `filemtime()`-basiertes Cache-Busting.
- `CMS/core/Services/CookieConsentService.php` nutzt dagegen feste Query-Versionen (`?v=20260307a`), was bei späteren Asset-Änderungen dokumentiert nachgezogen werden muss.
- Der Medienbereich greift tief auf interne elFinder-Unterpfade wie `elfinder/vendor/jquery-ui/*` und `elfinder/vendor/jquery/*` zu; diese Dateien existieren aktuell, sind aber bei Bibliotheksupdates besonders bruchanfällig.
- Dompdf ist kein Teil des `CMS/assets`-Autoloads, sondern ein dokumentierter Sonderfall unter `CMS/vendor/dompdf/`.
- Die produktiv eingebundenen Symfony-Bundles `mailer`, `mime` und `translation` deklarieren aktuell `PHP >= 8.4` in ihren Composer-Metadaten; diese Information muss mit der offiziellen 365CMS-Zielplattform und den lokalen Versionsangaben in der Dokumentation abgeglichen werden.
- Auch externe Workspace-Repos koppeln teils direkt an Core-Assets: Theme-Customizer laden `assets/css/admin.css` / `assets/js/admin.js` direkt, `cms-experts` nutzt `assets/suneditor/css/suneditor.min.css`, und `cms-jobprofile-generator` bindet Core-CSS in eigenen Member-Layouts fest ein.
