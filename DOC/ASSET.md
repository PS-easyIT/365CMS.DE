# ASSET-Übersicht 365CMS
> **Stand:** 2026-03-28 | **Version:** 2.8.0 RC | **Status:** Aktuell

## Inhaltsverzeichnis
- <a>Übersicht aller Libraries</a>
- <a>CSS-Architektur</a>
- <a>JavaScript-Architektur</a>
- <a>Versionierung & Cache-Busting</a>
- <a>Build-Workflow</a>
- <a>Synchronisation</a>

---

## Übersicht aller Libraries <!-- UPDATED: 2026-03-28 -->
Gesamtliste der relevanten Runtime- und Legacy-Bundles. Quelle: `CMS/assets/` (Produktivpfad, Autoload) – **nicht** `ASSETS/` (Staging).

| Library | Version | Zweck | Pfad (CMS/assets) | CDN/Lokal | Eingebunden in |
|---|---|---|---|---|---|
| tabler | 1.4.0 | Admin-/Member-UI | `tabler/` | lokal | Admin, Member |
| editorjs | 2.x | Block-Editor | `editorjs/` | lokal | Admin/Frontend |
| suneditor | 2.x | Legacy WYSIWYG | `suneditor/` | lokal | Admin |
| elfinder | 2.1.x | ehem. Dateimanager | `elfinder/` | lokal | Legacy-Bestand |
| filepond | 4.x | ehem. Upload-Komponente | `filepond/` | lokal | Legacy-Bestand |
| gridjs | 6.x | Tabellen | `gridjs/` | lokal | Admin |
| photoswipe | 5.x | Lightbox | `photoswipe/` | lokal | Frontend |
| php-jwt | 6.x | JWT Tokens | `php-jwt/` | lokal | API/Auth |
| ldaprecord | 4.x | LDAP/AD | `ldaprecord/` | lokal | Auth |
| symfony-mailer | 6.x | Mail-Versand | `mailer/` | lokal | System |
| mailer (legacy) | 1.x | Alt-Mailer | `mailer/legacy` | lokal | Legacy |
| tntsearch | 5.x | Volltextsuche | `tntsearchsrc/`, `tntsearchhelper/` | lokal | Suche |
| translation | 6.x | i18n | `translation/` | lokal | System |
| twofactorauth | 1.x | TOTP | `twofactorauth/` | lokal | Auth |
| webauthn | 3.x | Passkeys | `webauthn/` | lokal | Auth |
| htmlpurifier | 4.x | XSS-Schutz | `htmlpurifier/` | lokal | System |
| cookieconsent | 3.x | ehem. DSGVO-Banner-Runtime | `cookieconsent/` | lokal | Legacy-Bestand, aktuell nicht aktiv |
| melbahja-seo | 1.x | SEO-Helfer | `melbahja-seo/` | lokal | SEO |
| simplepie | 1.9.x | ehem. RSS/Atom-Parser | `simplepiesrc/`, `simplepielibrary/` | lokal | Legacy-Bestand, aktuell nicht aktiv |
| carbon | 2.x | Datum/Zeit | `carbon/` | lokal | System |
| mime | 6.x | MIME-Erkennung | `mime/` | lokal | Mail/Upload |
| psr | 3.x | PSR-Interfaces | `psr/` | lokal | Transitiv |
| translation deps |  | PSR/EventDispatcher | `psr/` | lokal | Transitiv |
| misc intern | – | css/js/images | `css/`, `js/`, `images/` | lokal | Intern |
| referenz: msgraph | – | SDK-Notizen | `msgraph/` | lokal | Referenz, nicht aktiv |

---

## CSS-Architektur <!-- UPDATED: 2026-03-28 -->
- **Basis:** Tabler CSS (Admin) + eigene Overrides in `CMS/assets/css/`.
- **Variablen:** Farbe, Spacing, Typography zentral in `CMS/assets/css/variables.css` (falls vorhanden); Admin-spezifische Variablen in `tabler/`.
- **Theming:** Admin lädt Tabler + Custom CSS via `CMS/admin/partials/header.php`. Frontend-Themes liefern eigenes CSS im Theme-Ordner; globale Assets nur für gemeinsam genutzte Komponenten.
- **Legacy:** SunEditor bringt eigenes CSS; sollte nur auf Seiten geladen werden, die ihn nutzen.

---

## JavaScript-Architektur <!-- UPDATED: 2026-03-28 -->
- **Module:** Lokale JS-Utilities unter `CMS/assets/js/` (u. a. `admin.js`, `admin-media-integrations.js`, `member-dashboard.js`, `cookieconsent-init.js`, `photoswipe-init.js`).
- **Global Objects:** `window.cms` (Admin/Frontend-Hilfen), `window.EditorJS`, `window.CMSCookieConsent`.
- **Event-Bus:** Hooks laufen serverseitig; JS-seitig werden Events über DOM/Custom Events und modulare Initialisierer gekapselt.
- **Admin-Assets:** Werden in `CMS/admin/partials/header.php/footer.php` basierend auf `$pageAssets` injiziert.

---

## Versionierung & Cache-Busting <!-- UPDATED: 2026-03-28 -->
- **Strategie:** Query-Parameter mit Versionsstring aus `CMS_VERSION` oder Asset-Build-Timestamp (manuell gepflegt in Templates).
- **Empfehlung:** Bei Updates von Bibliotheken und nativen Assets `filemtime()` oder konsistente Versionsparameter nutzen; statische Dateien mit Hash im Dateinamen bevorzugen, wenn neu gebaut.

---

## Build-Workflow <!-- UPDATED: 2026-03-28 -->
- Kein Vite/Webpack im Repo. Assets werden manuell verwaltet.
- Minimale Schritte beim Aktualisieren:
  1) Neue Version nach `CMS/assets/<lib>/` kopieren.
  2) `CMS/assets/autoload.php` prüfen, ob Pfade gleich bleiben.
  3) In Admin-/Frontend-Templates Versionsparameter anpassen.
  4) Falls JS/CSS gebündelt werden muss: extern bauen und minifizierte Artefakte ablegen (Hash im Namen bevorzugt).

---

## Synchronisation <!-- UPDATED: 2026-03-28 -->
- Quelle der Wahrheiten: `CMS/assets/` (Runtime) + `DOC/assets/README.md`.
- **Excel-Abgleich:** `DOC/assets/365CMS_Asset_Uebersicht.xlsx` regelmäßig mit obiger Tabelle synchron halten.
- **Staging vs. Prod:** `ASSETS/` nur als Ablage; produktiver Autoload ausschließlich `CMS/assets/autoload.php`.
- führende Detaildoku: `DOC/assets/README.md` plus die jeweiligen Einzel-READMEs

---

## Audit-Notiz zur Runtime-Integration <!-- UPDATED: 2026-03-28 -->
- Die produktive PHP-Dependency-Ladung erfolgt überwiegend über `CMS/assets/autoload.php`.
- Aktuelle Ausnahme: PDF-Erzeugung lädt Dompdf separat aus `CMS/vendor/dompdf/autoload.php`.
- Admin-, Member- und Frontend-nahe Komponenten verwenden aktuell mehrere Pfadmuster parallel: `ASSETS_URL`, `SITE_URL . '/assets'`, feste Versionsstrings und `filemtime()`.
- Besonders update-sensibel sind verbliebene Altbestände und ihre Doku-/Monitoring-Verweise, insbesondere bei `elfinder/`, `simplepie/` und `cookieconsent/`.
- Für künftige Pflege ist eine zentrale Asset-/Versionierungs-Registry empfehlenswert, damit Pfadlogik, Existenzprüfung und Cache-Busting nicht über viele Dateien verstreut bleiben.
- Wichtig für die aktuelle Runtime: Die gebündelten Symfony-Komponenten unter `CMS/assets/mailer`, `CMS/assets/mime` und `CMS/assets/translation` deklarieren in ihren Composer-Metadaten `PHP >= 8.4`; 365CMS hat seine offizielle Mindestplattform deshalb auf PHP 8.4 angehoben und validiert diese Manifeste nun zentral im Bootstrap vor dem regulären Service-Start.
