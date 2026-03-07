# 365CMS – Admin-Bereich

Kurzbeschreibung: Überblick über die aktuelle Admin-Navigation, die wichtigsten Modulgruppen und die zugehörigen Fachdokumente.

Letzte Aktualisierung: 2026-03-07

Die hier dokumentierte Struktur folgt der aktuellen Sidebar-Konfiguration aus `CMS/admin/partials/sidebar.php`. Öffentliche Admin-Routen werden in der Dokumentation bewusst ohne `.php` beschrieben.

---

## Überblick

Der Admin-Bereich ist seit 2.3.x deutlich stärker modularisiert. Früher monolithische Seiten wie „SEO“, „System“ oder „Backup“ wurden in mehrere spezialisierte Einstiege aufgeteilt.

Wichtige Grundsätze:

- Routing orientiert sich an sprechenden Slugs wie `/admin/seo-dashboard`
- Menüpunkte werden gruppiert in der Sidebar definiert
- Fachlogik liegt überwiegend in `admin/modules/`
- Views liegen unter `admin/views/`
- Legacy-Einstiege werden, wenn vorhanden, auf neue Seiten umgeleitet

---

## Aktuelle Menügruppen

| Menügruppe | Wichtige Routen | Zweck |
|---|---|---|
| Dashboard | `/admin` | Gesamtüberblick, KPIs, Schnellzugriffe |
| Seiten & Beiträge | `/admin/pages`, `/admin/posts`, `/admin/comments`, `/admin/table-of-contents`, `/admin/site-tables` | Content-Management |
| Medienverwaltung | `/admin/media` | Dateien, Kategorien, Medieneinstellungen |
| Benutzer & Gruppen | `/admin/users`, `/admin/groups`, `/admin/roles` | Benutzer, Teams, Rechte |
| Member Dashboard | `/admin/member-dashboard` und Folgeseiten | Konfiguration des Mitgliederbereichs |
| Aboverwaltung | `/admin/packages`, `/admin/orders`, `/admin/subscription-settings` | Pakete, Bestellungen, Zuweisungen |
| Themes & Design | `/admin/themes`, `/admin/theme-editor`, `/admin/theme-explorer`, `/admin/menu-editor`, `/admin/landing-page`, `/admin/font-manager` | Design, Navigation, Fonts |
| SEO | `/admin/seo-dashboard`, `/admin/analytics`, `/admin/seo-audit`, `/admin/seo-meta`, `/admin/seo-social`, `/admin/seo-schema`, `/admin/seo-sitemap`, `/admin/seo-technical`, `/admin/redirect-manager` | Suchmaschinenoptimierung |
| Performance | `/admin/performance`, `/admin/performance-cache`, `/admin/performance-media`, `/admin/performance-database`, `/admin/performance-settings`, `/admin/performance-sessions` | Laufzeit- und Ressourcenoptimierung |
| Recht | `/admin/legal-sites`, `/admin/cookie-manager`, `/admin/data-requests` | Legal Sites, Cookie-Management, DSGVO-Anfragen |
| Sicherheit | `/admin/antispam`, `/admin/firewall`, `/admin/security-audit` | Schutzmaßnahmen und Auditing |
| Plugins | `/admin/plugins`, `/admin/plugin-marketplace` sowie Plugin-Unterseiten | Plugin-Lifecycle |
| System | `/admin/settings`, `/admin/backups`, `/admin/updates` | Konfiguration, Backups, Updates |
| Info | `/admin/info`, `/admin/documentation` | Systeminfo und lokale Dokuansicht |
| Diagnose | `/admin/diagnose` sowie Monitoring-Seiten | technische Prüfungen und Monitoring |

---

## Relevante Fachdokumente

| Bereich | Dokument |
|---|---|
| Admin-Struktur | [FILESTRUCTURE.md](FILESTRUCTURE.md) |
| Legal & Security | [legal-security/README.md](legal-security/README.md) |
| SEO | [seo-performance/SEO.md](seo-performance/SEO.md) |
| Themes & Design | [themes-design/CUSTOMIZER.md](themes-design/CUSTOMIZER.md) |
| Fonts | [themes-design/FONTS.md](themes-design/FONTS.md) |
| System & Betrieb | [system-settings/README.md](system-settings/README.md) |

---

## Hinweise zu Legacy-Routen

Einige ältere Einstiege tauchen noch in Alt-Dokumentation oder Redirects auf. Der aktuelle Zielzustand ist:

- nicht mehr `theme-customizer.php`, sondern `/admin/theme-editor`
- nicht mehr `fonts-local.php`, sondern `/admin/font-manager`
- nicht mehr ein einzelnes `/admin/seo.php`, sondern mehrere SEO-Unterseiten
- nicht mehr `backup.php`, sondern `/admin/backups`
- nicht mehr `cookies.php`, sondern `/admin/cookie-manager`
- nicht mehr `system-info.php` als Zielseite; die Legacy-Route leitet auf `/admin/info` um

---

## Sicherheitsmodell im Admin

Alle Einstiege folgen demselben Grundmuster:

1. `ABSPATH`-Schutz
2. Admin-Authentifizierung via `CMS\Auth`
3. CSRF-Prüfung via `CMS\Security`
4. Verarbeitung der Aktion im Modul
5. Redirect mit Session-Alert statt direkter POST-Antwort

Das ist wichtig für konsistente Fehlerbehandlung, PRG-Flow und nachvollziehbare Audit-Einträge.

