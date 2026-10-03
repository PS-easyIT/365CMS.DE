# 365CMS – Projektdokumentation | Abschnitt: Core – Implementierungsstatus

> **Stand:** 2026-10-03 | **Version:** 3.4.00 (Changelog bis 3.4.12) | **Schema:** v22 | **Status:** Stable

## English (summary)

Snapshot of the runtime state verified against the source code on 2026-10-02: version and schema constants, feature areas and their maturity, the changes recorded since the 3.4.00 release (3.4.01–3.4.12, which do not bump `Version::CURRENT`), and a list of known gaps and inconsistencies found while updating the documentation.

## Deutsch

### Versionsstand

| Merkmal | Wert | Quelle |
|---|---|---|
| Core-Version | `3.4.00`, Release `2026-09-05`, Status `stable` | `CMS/core/Version.php`, `CMS/update.json` |
| Schema | `v22` | `SchemaManager::SCHEMA_VERSION`, `MigrationManager::SCHEMA_VERSION` |
| PHP | ≥ 8.4.0 | `CMS_MIN_PHP_VERSION` in `CMS/config.php`, `min_php` in `update.json` |
| Datenbank | MySQL ≥ 5.7 / MariaDB (empfohlen MySQL 8.0+ / MariaDB 10.6+) | Update-Preflight |
| Standard-Theme | `cms-default` 1.0.9 („Meridian CMS Default“) | `CMS/themes/cms-default/theme.json` |
| Mitgeliefertes Plugin | `cms-importer` 3.0.3 | `CMS/plugins/cms-importer/cms-importer.php` |
| Changelog | Einträge bis `3.4.12` (03.10.2026) | `Changelog.md` – historische Einträge ohne Änderung von `Version::CURRENT` |

### Änderungen seit 3.4.00 (laut Changelog)

| Version | Schwerpunkt |
|---|---|
| 3.4.12 | Audit 2026-10-03: Wartungsmodus, `/order`, `/health`, Config-Speichern, Upload-/Proxy-/Registrierungs-Härtung, Theme-Abfragen, Doku-Links – siehe [../audit/README.md](../audit/README.md) |
| 3.4.11 | PageSpeed: DOMPurify per `defer`, OPcache-Deploy-Prüfung gedrosselt |
| 3.4.10 | Editor.js: Zitate/Media-Text im Purifier, klassenbasiertes CSS |
| 3.4.09 | Dokumentation vollständig gegen 3.4-Code abgeglichen |
| 3.4.08 | SEO: Template-Variablen in gespeicherten Meta-Titeln/-Beschreibungen werden aufgelöst |
| 3.4.07 | Admin-Sidebar: Plugin-Menüs nach Schema „Familie \| Name“, umbrechende Titel, Tooltip |
| 3.4.06 | Suche: nur Treffer mit allen Begriffen, sichtbarer Text statt Editor.js-JSON, strikte Typ-Filter, Sortierung nach Relevanz/Datum, Plugin-Treffer mit `date` |
| 3.4.05 | Theme: Logout per POST mit CSRF-Token, mobiles Menü bis 720 px |
| 3.4.04 | SEO: externe Links nicht mehr pauschal `nofollow`, Schema-Typen auf ausgegebene Typen begrenzt, Plugin-Seiten in der Sitemap (`cms_sitemap_entries`), Plugin-Treffer in `/search`, TNTSearch-Verbindungsfix |
| 3.4.03 | Theme: Tag-/Kategorie-Archive ohne HTTP 500, OPcache-Warmup bricht Admin-Seiten nicht mehr ab, Plugin-Unterseiten erhalten `$_GET['page']` |
| 3.4.02 | OPcache-Warmup nach Deploy ohne „Cannot redeclare class“ |
| 3.4.01 | Rollen & Rechte: Rechte-Matrix ohne doppelte Einträge |
| ältere | siehe [../../Changelog.md](../../Changelog.md) |

### Funktionsbereiche

| Bereich | Status | Dokumentation |
|---|---|---|
| Seiten/Beiträge (DE/EN, Revisionen, Planung) | produktiv | [../admin/pages-posts/](../admin/pages-posts/README.md) |
| Editor.js / SunEditor | produktiv | [../assets/editorjs/README.md](../assets/editorjs/README.md) |
| Medien (Bibliothek, WebP, Thumbnails) | produktiv | [../admin/media/MEDIA.md](../admin/media/MEDIA.md) |
| Benutzer, Rollen, Gruppen | produktiv | [../admin/users-groups/](../admin/users-groups/README.md) |
| Anmeldung (Passwort, TOTP, Passkeys, LDAP) | produktiv | [SECURITY.md](SECURITY.md) |
| Mitgliederbereich | produktiv | [../member/README.md](../member/README.md) |
| Aboverwaltung | produktiv, ohne Zahlungs-Gateway | [../admin/subscription/](../admin/subscription/README.md) |
| SEO-Suite, Sitemaps, Weiterleitungen | produktiv | [../admin/seo/](../admin/seo/README.md) |
| Rechtstexte, Cookie-Consent, DSGVO-Anfragen | produktiv | [../admin/legal/](../admin/legal/README.md) |
| Firewall, Anti-Spam, Audit | produktiv | [../admin/security/](../admin/security/README.md) |
| KI-Dienste | produktiv (nur Admin, keine Auto-Veröffentlichung) | [../ai/AI-SERVICES.md](../ai/AI-SERVICES.md) |
| Backups, Updates, Monitoring, Cron | produktiv | [../admin/system-settings/](../admin/system-settings/README.md) |
| JSON-API | lesend + Admin-Listen, Session-basiert | [API-REFERENCE.md](API-REFERENCE.md) |

### Bekannte Lücken und Inkonsistenzen (Stand 2026-10-03)

Nachgeprüft im Audit vom 2026-10-03 ([../audit/README.md](../audit/README.md)). Behobene Punkte bleiben zur Nachvollziehbarkeit stehen.

| # | Befund | Auswirkung | Fundstelle | Status |
|---|---|---|---|---|
| 1 | Speichern unter `/admin/settings` erzeugte `config/app.php` aus einer Vorlage neu | manuell gesetzte Konstanten gingen verloren | `SettingsModule::saveSettings()` | ✅ 3.4.12 – nur verwaltete Konstanten werden ersetzt |
| 2 | Option `maintenance_mode` wurde nicht ausgewertet | Wartungsmodus ohne Wirkung | `SettingsModule`, Router | ✅ 3.4.12 – 503-Seite in `Router::dispatch()` |
| 3 | Route `/order` erwartete fehlendes `CMS/member/order_public.php` | 404 | `PublicRouter::renderOrder()` | ✅ 3.4.12 – Weiterleitung auf `/orders.php` |
| 4 | Sicherheits-Audit bewertete PHP ≥ 8.2 als „ok“ | Abweichung zur Mindestanforderung 8.4 | `SecurityAuditModule` | ✅ 3.4.12 – nutzt `CMS_MIN_PHP_VERSION` |
| 5 | `checkLimit('storage')` suchte `limit_storage`; Free-Fallback unsortiert | Speicherlimit nicht prüfbar | `SubscriptionManager` | ✅ 3.4.12 |
| 6 | `DesignSettingsModule` und `views/themes/settings.php` werden nicht eingebunden | toter Code | `CMS/admin/modules/themes/` | offen |
| 7 | Dokumentations-Sync-Klassen nicht an `/admin/documentation` angebunden | kein Abgleich per Oberfläche | `DocumentationSync*` | offen |
| 8 | Sammelaktion `delete` löschte wie `hard_delete` | kein weiches Löschen | `UserService::bulkAction()` | ✅ 3.4.12 – `delete` = Soft-Delete |
| 9 | Health-Endpunkt ohne Core-Route | Prüfung schlug fehl | `SystemInfoModule` | ✅ 3.4.12 – `GET /health` (Standardpfad) |
| 10 | `JwtService` nicht in API-Routen verdrahtet | API nur mit Session | `ApiRouter`, `Api` | offen |
| 11 | Beispiel-Manifeste unter `CMS/marketplace/` mit veralteten Versionen | Anzeige veraltet | `CMS/marketplace/*/index.json` | ✅ 3.4.12 |
| 12 | Member-Upload sendete Token `member_media_action`, `/api/upload` prüft `media_action` | erster Upload scheiterte (403) | `CMS/member/media.php` | ✅ 3.4.12 |
| 13 | Plugin-Member-Bereiche (`post_callback`) ohne zentrale CSRF-Prüfung | Plugins müssen CSRF selbst prüfen | `CMS/member/plugin-section.php` | offen (Audit SEC-07) |
| 14 | `wp_enqueue_*`/`wp_dequeue_*` sind leere Stubs | WP-nahe Plugins laden keine Assets | `includes/functions/wordpress-compat.php` | offen (Audit FUN-10) |
| 15 | `Version::CURRENT` bleibt `3.4.00` trotz ausgelieferter Fixes | Installationen nicht unterscheidbar | `CMS/core/Version.php` | offen (Audit FUN-12) |

### Grundsatz dieser Dokumentation

Dokumentiert wird nur, was im ausgelieferten Code nachvollziehbar ist. Pfade, Klassen und Routen in `DOC/` wurden am 2026-10-02 gegen `CMS/` abgeglichen und im Audit vom 2026-10-03 erneut geprüft.

### Verwandte Dokumente

[README.md](README.md) · [../README.md](../README.md) · [../audit/README.md](../audit/README.md) · [../../Changelog.md](../../Changelog.md)
