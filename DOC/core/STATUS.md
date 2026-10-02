# 365CMS – Projektdokumentation | Abschnitt: Core – Implementierungsstatus

> **Stand:** 2026-10-02 | **Version:** 3.4.00 (Changelog bis 3.4.08) | **Schema:** v22 | **Status:** Stable

## English (summary)

Snapshot of the runtime state verified against the source code on 2026-10-02: version and schema constants, feature areas and their maturity, the changes recorded since the 3.4.00 release (3.4.01–3.4.08, which do not bump `Version::CURRENT`), and a list of known gaps and inconsistencies found while updating the documentation.

## Deutsch

### Versionsstand

| Merkmal | Wert | Quelle |
|---|---|---|
| Core-Version | `3.4.00`, Release `2026-09-05`, Status `stable` | `CMS/core/Version.php`, `CMS/update.json` |
| Schema | `v22` | `SchemaManager::SCHEMA_VERSION`, `MigrationManager::SCHEMA_VERSION` |
| PHP | ≥ 8.4.0 | `CMS_MIN_PHP_VERSION` in `CMS/config.php`, `min_php` in `update.json` |
| Datenbank | MySQL ≥ 5.7 / MariaDB | Update-Preflight |
| Standard-Theme | `cms-default` 1.0.9 („Meridian CMS Default“) | `CMS/themes/cms-default/theme.json` |
| Mitgeliefertes Plugin | `cms-importer` 3.0.3 | `CMS/plugins/cms-importer/cms-importer.php` |
| Changelog | Einträge bis `3.4.08` (30.09.2026) | `Changelog.md` – historische Einträge ohne Änderung von `Version::CURRENT` |

### Änderungen seit 3.4.00 (laut Changelog)

| Version | Schwerpunkt |
|---|---|
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

### Bekannte Lücken und Inkonsistenzen (Stand 2026-10-02)

| # | Befund | Auswirkung | Fundstelle |
|---|---|---|---|
| 1 | Speichern unter `/admin/settings` erzeugt `config/app.php` aus einer Vorlage neu | manuell gesetzte `LDAP_*`, `JWT_*`, `SMTP_*`, HTTPS/HSTS-Konstanten und Zeitzone werden zurückgesetzt | `SettingsModule::saveSettings()` |
| 2 | Option `maintenance_mode` wird gespeichert, aber zur Laufzeit nicht ausgewertet | Wartungsmodus ohne Wirkung | `SettingsModule`, Router |
| 3 | Route `/order` erwartet `CMS/member/order_public.php`, Datei fehlt | `/order` liefert 404; Checkout nur über `/orders.php` | `PublicRouter::renderOrder()` |
| 4 | Sicherheits-Audit bewertet PHP ≥ 8.2 als „ok“ | weicht von der Mindestanforderung 8.4 ab | `SecurityAuditModule` |
| 5 | `SubscriptionManager::checkLimit('storage')` sucht `limit_storage`, Spalte heißt `limit_storage_mb`; Free-Fallback nimmt den ersten Paket-Datensatz ohne Sortierung | Speicherlimit nur mit Ressourcenname `storage_mb` prüfbar; Fallback abhängig von der Anlage-Reihenfolge | `SubscriptionManager` |
| 6 | `DesignSettingsModule` und `views/themes/settings.php` werden nicht mehr eingebunden | toter Code | `CMS/admin/modules/themes/` |
| 7 | Dokumentations-Sync-Klassen sind nicht an `/admin/documentation` angebunden | kein Abgleich aus GitHub per Oberfläche | `DocumentationSync*` |
| 8 | Benutzer-Sammelaktion `delete` löscht wie `hard_delete` endgültig | kein „weiches“ Löschen per Sammelaktion | `UserService::bulkAction()` |
| 9 | Health-Endpunkt `/health` ist konfigurierbar, der Core registriert aber keine Route | Prüfung schlägt ohne externen Endpunkt fehl | `SystemInfoModule` |
| 10 | `JwtService` ist nicht in API-Routen verdrahtet | API nur mit Session nutzbar | `ApiRouter`, `Api` |
| 11 | Beispiel-Manifeste unter `CMS/marketplace/` nennen veraltete Versionen (`cms-importer` 1.6.0, `cms-default` 1.0.3) | Anzeige im lokalen Marketplace-Spiegel veraltet | `CMS/marketplace/*/index.json` |
| 12 | Member-Upload unter `/member/media` sendet ein Token der Aktion `member_media_action`, `/api/upload` prüft `media_action` | erster Upload je Seitenaufruf scheitert (403), Wiederholung gelingt mit dem zurückgegebenen Token | `CMS/member/media.php`, `FileUploadService` |

### Grundsatz dieser Dokumentation

Dokumentiert wird nur, was im ausgelieferten Code nachvollziehbar ist. Pfade, Klassen und Routen in `DOC/` wurden am 2026-10-02 gegen `CMS/` abgeglichen.

### Verwandte Dokumente

[README.md](README.md) · [../README.md](../README.md) · [../../Changelog.md](../../Changelog.md)
