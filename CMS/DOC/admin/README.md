# 365CMS – Admin-Bereich
> **Stand:** 2026-05-10 | **Version:** 2.9.724 | **Status:** Aktuell

## Inhaltsverzeichnis
- [Überblick](#überblick)
- [Aktuelle Menügruppen](#aktuelle-menügruppen)
- [Relevante Fachdokumente](#relevante-fachdokumente)
- [Hinweise zu Legacy-Routen](#hinweise-zu-legacy-routen)
- [Sicherheitsmodell im Admin](#sicherheitsmodell-im-admin)

Die Struktur folgt der aktuellen Sidebar-Konfiguration aus `CMS/admin/partials/sidebar.php`. Öffentliche Admin-Routen werden in der Dokumentation bewusst ohne `.php` beschrieben.

Für Detailfragen gilt die reale Sidebar- und Routing-Laufzeit als führend; diese Datei ist die verdichtete 2.9.712-Übersicht, nicht der einzige Wahrheitsspeicher.

---
<!-- UPDATED: 2026-05-09 -->

## Überblick

Der Admin-Bereich ist modular aufgebaut. Entry-Dateien unter `CMS/admin/` sind schlanke Routen, während Fachlogik überwiegend in `CMS/admin/modules/` und Views in `CMS/admin/views/` liegen.

Wichtige Grundsätze:

- Routing orientiert sich an sprechenden Slugs wie `/admin/seo-dashboard`
- Menüpunkte werden gruppiert in der Sidebar definiert
- Fachlogik liegt überwiegend in `admin/modules/`
- Views liegen unter `admin/views/`
- Views und Actions hängen an kleinen Request-/CSRF-Verträgen; Admin-Formulare tolerieren mehrere parallel geöffnete Tokens pro Action innerhalb des TTL-Fensters, invalidieren den verwendeten Token aber weiterhin nach erfolgreichem POST
- Redirects folgen möglichst dem PRG-Muster (Post/Redirect/Get)
- Legacy-Einstiege werden nur noch dokumentiert, wenn sie aktiv umleiten oder Rücksicht auf Altbestände erfordern

---

## Aktuelle Menügruppen

| Menügruppe | Wichtige Routen | Zweck |
|---|---|---|
| Dashboard | `/admin` | Gesamtüberblick, KPIs, Schnellzugriffe, fail-softe Statusblöcke und benutzerbezogene Sichtbarkeitsprofile mit mehrtab-tolerantem CSRF-Speichern, defensiv internen Zielpfaden für Quicklinks, bereinigter browserlokaler Recent-Liste, rollenbasierten Standardvorlagen mit Reset-Pfad sowie persistenter Widget-/Favoriten-Sortierung per Drag-&-Drop oder Pfeil-Fallback |
| AI Services | `/admin/ai-services`, `/admin/ai-translation`, `/admin/ai-content-creator`, `/admin/ai-seo-creator`, `/admin/ai-settings` | Provider, Translation-Regeln, Prompt-Vorlagen, Logging, Quotas und request-/historiennahe AI-Beobachtung mit fail-softem Initialisierungspfad und konsistenter aktiver Provider-Auswahl |
| Seiten & Beiträge | `/admin/pages`, `/admin/posts`, `/admin/comments`, `/admin/table-of-contents`, `/admin/site-tables` | Content-Management mit stabilem Slug-/Taxonomie-Vertrag, Bulk-fähiger Kategorien-/Tag-Verwaltung, commit-schonenderem Cache-Clear bei Sammellöschungen, direkt im Editor sichtbaren SEO-/Readability-Prüfungen und read-only Revisionsvergleichen in Seiten- **und** Beitragseditor ohne zusätzliches Snapshot-Debug-Logging im Save-Flow |
| Medienverwaltung | `/admin/media`, `/admin/media?tab=featured`, `/admin/media?tab=categories`, `/admin/media?tab=settings` | Bibliothek, Beitrags-/Site-Medien, Kategorien, Medieneinstellungen mit festem Bildvertrag im Replace-in-place-Flow und read-only Duplikat-Erkennung per Inhalts-Hash |
| Benutzer & Gruppen | `/admin/users`, `/admin/groups`, `/admin/roles`, `/admin/user-settings` | Benutzer, Teams, Rechte und Auth-Einstellungen mit gemeinsamer Rollenmatrix, einheitlicher 12-Zeichen-Passwort-Policy, lokalem Policy-Tester und allowlist-basierten Gruppen-Sammelaktionen für Aktivstatus, Paketzuweisung und Löschung |
| Member Dashboard | `/admin/member-dashboard` und Folgeseiten | Konfiguration des Mitgliederbereichs mit getrenntem Runtime-Settings-Pfad für das öffentliche `/member`-Frontend |
| Aboverwaltung | `/admin/packages`, `/admin/orders`, `/admin/subscription-settings` | Pakete, Bestellungen, Zuweisungen und automatische Standardpaket-Zuweisung für neue Mitglieder |
| Themes & Design | `/admin/themes`, `/admin/theme-editor`, `/admin/theme-explorer`, `/admin/menu-editor`, `/admin/landing-page`, `/admin/font-manager` | Design, Navigation, Fonts und Landing-Page-Plugin-Overrides mit echten Header-/Content-/Footer-Zuweisungen |
| SEO | `/admin/seo-dashboard`, `/admin/analytics`, `/admin/seo-audit`, `/admin/seo-meta`, `/admin/seo-social`, `/admin/seo-schema`, `/admin/seo-sitemap`, `/admin/seo-technical`, `/admin/redirect-manager` | Suchmaschinenoptimierung mit echten globalen Social-Fallbacks für Frontend-Head-Tags |
| Performance | `/admin/performance`, `/admin/performance-cache`, `/admin/performance-media`, `/admin/performance-database`, `/admin/performance-settings`, `/admin/performance-sessions` | Laufzeit- und Ressourcenoptimierung mit ehrlichem Server-Kompressionsstatus statt dekorativem CMS-Schalter |
| Recht | `/admin/legal-sites`, `/admin/cookie-manager`, `/admin/data-requests` | Legal Sites, Cookie-Management mit atomar gespeicherten Consent-/Matomo-Self-Hosted-Settings und auditierbare DSGVO-Anfragen mit Begründungspflicht bei Ablehnungen |
| Sicherheit | `/admin/antispam`, `/admin/firewall`, `/admin/security-audit` | Schutzmaßnahmen und Auditing mit zentralem AntiSpam-Vertrag für Kommentare und aktive Kontaktformulare |
| Plugins | `/admin/plugins`, `/admin/plugin-marketplace` sowie Plugin-Unterseiten | Plugin-Lifecycle mit stabiler, request-idempotenter Menü-Registry für dynamische Sidebar- und Submenü-Einträge |
| System | `/admin/settings`, `/admin/backups`, `/admin/updates`, `/admin/cms-logs` | Konfiguration, Backups mit Download/Restore, Updates inklusive zentralem Theme-Installpfad, zentrale CMS-Logs mit Betriebs-Audit und Update-Historie |
| Info | `/admin/info`, `/admin/documentation` | Systeminfo und lokale Dokuansicht |
| Diagnose | `/admin/diagnose` sowie Monitoring-Seiten | technische Prüfungen und Monitoring mit realem lokalem Health-Endpunkt-Check statt dekorativer Markierung sowie nachvollziehbaren Betriebs- und Update-Spuren in `/admin/cms-logs` |

---

## Relevante Fachdokumente

| Bereich | Dokument |
|---|---|
| Admin-Struktur | [FILESTRUCTURE.md](FILESTRUCTURE.md) |
| Admin-Prüfplanung | [PRUEF-CHECKLISTE.md](PRUEF-CHECKLISTE.md) |
| Dashboard | [dashboard/DASHBOARD.md](dashboard/DASHBOARD.md) |
| Seiten & Beiträge | [pages-posts/README.md](pages-posts/README.md) |
| Kommentare | [pages-posts/COMMENTS.md](pages-posts/COMMENTS.md) |
| Landing Page | [landing-page/LANDING-PAGE.md](landing-page/LANDING-PAGE.md) |
| Medien | [media/README.md](media/README.md) |
| Benutzer & Gruppen | [users-groups/README.md](users-groups/README.md) |
| Member Dashboard | [member/README.md](member/README.md) |
| Aboverwaltung | [subscription/SUBSCRIPTION-SYSTEM.md](subscription/SUBSCRIPTION-SYSTEM.md) |
| Pakete | [subscription/PACKAGES.md](subscription/PACKAGES.md) |
| Recht | [legal/README.md](legal/README.md) |
| DSGVO-Löschanträge | [legal/DELETION-REQUESTS.md](legal/DELETION-REQUESTS.md) |
| Sicherheit | [security/README.md](security/README.md) |
| SEO | [seo/SEO.md](seo/SEO.md) |
| URL-Weiterleitungen | [seo/REDIRECTS.md](seo/REDIRECTS.md) |
| Performance | [performance/PERFORMANCE.md](performance/PERFORMANCE.md) |
| Themes & Design | [themes-design/README.md](themes-design/README.md) |
| Design-Einstellungen | [themes-design/DESIGN-SETTINGS.md](themes-design/DESIGN-SETTINGS.md) |
| Fonts | [themes-design/FONTS.md](themes-design/FONTS.md) |
| Plugins | [plugins/PLUGINS.md](plugins/PLUGINS.md) |
| System & Betrieb | [system-settings/README.md](system-settings/README.md) |
| AI Services (Admin-Kontext) | [system-settings/AI-SERVICES.md](system-settings/AI-SERVICES.md) |
| AI Services (kanonische Konzeptdoku) | [../ai/AI-SERVICES.md](../ai/AI-SERVICES.md) |
| Info | [info/INFO.md](info/INFO.md) |
| Diagnose & Monitoring | [diagnose/DIAGNOSE.md](diagnose/DIAGNOSE.md) |

---

## Hinweise zu Legacy-Routen

Einige ältere Einstiege tauchen noch in Alt-Dokumentation oder Redirects auf. Der aktuelle Zielzustand ist:

- nicht mehr `theme-customizer.php`, sondern `/admin/theme-editor`
- nicht mehr `fonts-local.php`, sondern `/admin/font-manager`
- nicht mehr ein einzelnes `/admin/seo.php`, sondern mehrere SEO-Unterseiten
- nicht mehr `backup.php`, sondern `/admin/backups`
- nicht mehr `cookies.php`, sondern `/admin/cookie-manager`
- nicht mehr `system-info.php` als Zielseite; die Legacy-Route leitet auf `/admin/info` um
- nicht mehr eigene Medien-Unterseiten wie `media-categories.php` oder `media-settings.php`, sondern Query-Tabs unter `/admin/media?tab=...` inklusive `/admin/media?tab=featured` für Beitrags-/Site-Medien

---

## Sicherheitsmodell im Admin

Alle Einstiege folgen demselben Grundmuster:

1. `ABSPATH`-Schutz
2. Admin-Authentifizierung via `CMS\Auth`
3. Capability- und/oder RBAC-Prüfung pro Bereich
4. CSRF-Prüfung via `CMS\Security`
5. Verarbeitung der Aktion im Modul oder Service
6. Redirect mit Session-Alert statt direkter POST-Antwort

Für das Dashboard gilt seit `2.9.615` zusätzlich: einzelne Statistikquellen müssen fail-soft isoliert werden, damit ein ausgefallener Teilblock nicht die komplette Startseite bricht. Seit `2.9.718` bleibt die Personalisierung außerdem nicht mehr auf Sichtbarkeit beschränkt; die Reihenfolge von Arbeits-Widgets und Favoriten wird ebenfalls pro Admin-Benutzer persistiert und serverseitig allowlist-basiert normalisiert. Seit `2.9.719` werden die browserlokalen „Zuletzt genutzt“-Einträge beim Lesen und Schreiben zusätzlich bereinigt, dedupliziert und größenbegrenzt, während das Dashboard-CSS als seitenbezogenes Asset statt inline geladen wird. Seit `2.9.720` ergänzt das Dashboard darauf aufbauend rollenbasierte Standardvorlagen als Default- und Reset-Basis für Bereiche, Arbeits-Widgets, Favoriten und deren Reihenfolge, ohne persönliche Anpassungen global zurück in die Vorlage zu schreiben.

Das ist wichtig für konsistente Fehlerbehandlung, PRG-Flow und nachvollziehbare Audit-Einträge.

Seit `2.9.724` sind außerdem zwei produktive Redeclare-Fatal-Pfade im Admin-/Theme-Bootstrap gehärtet: `CMS\SchemaManager` wird nur noch als konditionale Klasse deklariert, und die Default-Theme-Hilfsfunktion `meridian_nav_menu()` ist in beiden möglichen Helferdateien per `function_exists()` geschützt.

