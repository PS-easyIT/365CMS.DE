# 365CMS – Member Dashboard

Kurzbeschreibung: Detaildokumentation der persönlichen Startseite unter `/member` mit Willkommensbereich, Statuskarten, Info-Widgets und Plugin-Kacheln.

Letzte Aktualisierung: 2026-03-07

**Route:** `/member`

---

## Überblick

Das Dashboard rendert `member/partials/dashboard-view.php` und kombiniert mehrere Datenquellen:

- `dashboardData` aus dem Controller bzw. `MemberService`
- Design- und Modul-Settings aus der Datenbank
- Plugin-Widgets aus Hooks und `PluginDashboardRegistry`

---

## Sichtbare Hauptbereiche

| Bereich | Beschreibung |
|---|---|
| Header | Begrüßung, optionales Logo, letzter Login |
| Onboarding-Banner | administrativ konfigurierbare Einstiegs-Checkliste |
| Notifications-Banner | kompakte Meldungs- und Digest-Zusammenfassung |
| Schnellstart-Leiste | Direktlinks zu Profil, Sicherheit, Nachrichten usw. |
| Statuskarten | Konto, Abo, Aktivität und Sicherheit |
| Informations-Widgets | administrativ gepflegte Custom Widgets |
| Plugin-Kacheln | registrierte Plugin-Bereiche plus Schnelllinks |

---

## Wichtige Settings

Die View liest unter anderem folgende Schalter aus `settings`:

- `member_dashboard_show_welcome`
- `member_dashboard_show_quickstart`
- `member_dashboard_show_stats`
- `member_dashboard_show_custom_widgets`
- `member_dashboard_show_plugin_widgets`
- `member_dashboard_show_notifications_panel`
- `member_dashboard_show_onboarding_panel`
- `member_dashboard_plugin_order`

---

## Plugin-Integration

Aktuell greifen zwei Mechanismen ineinander:

1. Filter `member_dashboard_widgets`
2. `PluginDashboardRegistry`

Registry-Widgets werden priorisiert sortiert; statische Feature-Kacheln werden nur ergänzend angezeigt, wenn kein Registry-Widget denselben Plugin-Slug bereits belegt.

---

## Besondere Hinweise

- Administratoren sehen im Dashboard zusätzlich einen Link zurück in den Admin-Bereich.
- Die konkrete Reihenfolge der Sektionen wird über `member_dashboard_section_order` gesteuert.
- Die Kachelspalten orientieren sich an `member_dashboard_columns`.

---

## Verwandte Dokumente

- [../README.md](../README.md)
- [../HOOKS.md](../HOOKS.md)
- [SUBSCRIPTION.md](SUBSCRIPTION.md)
