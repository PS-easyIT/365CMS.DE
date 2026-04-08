# Member-Dashboard-Widgets

Kurzbeschreibung: Dokumentiert die Konfiguration der Dashboard-Widgets im **Member Dashboard** aus Admin-Sicht.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

---

## Überblick

Die frühere Route `/admin/design-dashboard-widgets.php` ist veraltet. Die Widget-Konfiguration wird heute im Bereich **Member Dashboard** gepflegt:

- Übersicht: `/admin/member-dashboard`
- Widget-Konfiguration: `/admin/member-dashboard-widgets`
- Plugin-Widgets: `/admin/member-dashboard-plugin-widgets`

Technische Basis:

- Entry Points: `CMS/admin/member-dashboard*.php`
- Logik: `CMS/admin/modules/member/MemberDashboardModule.php`
- Layout/View: `CMS/admin/views/member/*.php`

---

## Verfügbare Kern-Widgets

Die Kern-Widgets werden in `MemberDashboardModule::getAvailableWidgets()` definiert.

| Widget-Key | Bezeichnung | Zweck |
|---|---|---|
| `profile` | Profil-Übersicht | Basisdaten, Avatar, Kurzstatus |
| `activity` | Letzte Aktivitäten | Eigene jüngste Aktionen |
| `messages` | Nachrichten | Platzhalter für Kommunikationsmodule |
| `bookmarks` | Lesezeichen | Gespeicherte Inhalte oder Merker |
| `notifications` | Benachrichtigungen | Statusmeldungen und Hinweise |
| `quick_links` | Schnellzugriffe | Direkte Links in Member-Bereiche |
| `statistics` | Statistiken | Kompakte Kennzahlen im Dashboard |

Nicht jedes Widget muss im Frontend sichtbar sein. Sichtbarkeit und Reihenfolge werden über Admin-Einstellungen gesteuert.

---

## Konfigurierbare Bereiche

Auf `/admin/member-dashboard-widgets` werden drei Ebenen konfiguriert:

1. **Aktive Kern-Widgets**
2. **Spaltenlayout** (`1` bis `4` Spalten)
3. **Reihenfolge der Bereichsblöcke**

Unterstützte Abschnittsreihenfolgen:

- `stats,widgets,plugins`
- `stats,plugins,widgets`
- `widgets,stats,plugins`
- `plugins,stats,widgets`
- `quick_start,stats,widgets,plugins`
- `quick_start,stats,plugins,widgets`

Damit wird nicht nur die Reihenfolge einzelner Kacheln, sondern die Anordnung ganzer Dashboard-Sektionen gesteuert.

---

## Eigene Info-Widgets

Zusätzlich zu den Kern-Widgets unterstützt das System bis zu **vier frei pflegbare Info-Widgets**.

Gespeicherte Felder:

- `member_widget_1_title` bis `member_widget_4_title`
- `member_widget_1_content` bis `member_widget_4_content`
- `member_widget_1_icon` bis `member_widget_4_icon`

Diese Widgets eignen sich für:

- interne Hinweise
- Einstiegs-Links
- Onboarding-Tipps
- Support- oder Community-Verweise

Die Inhalte werden serverseitig bereinigt; erlaubt ist nur eingeschränktes HTML.

---

## Plugin-Widgets

Plugin-Widgets werden **nicht** im Kern-Widget-Set hinterlegt, sondern über die Registry des Member-Bereichs gesammelt:

- Klasse: `CMS\Member\PluginDashboardRegistry`
- Auswertung: `MemberDashboardModule::getPluginWidgets()`

Konfigurierbar sind:

- Sichtbarkeit pro Plugin-Widget
- Reihenfolge über `member_dashboard_plugin_order`

Die zugehörige Admin-Seite ist:

- `/admin/member-dashboard-plugin-widgets`

---

## Gespeicherte Einstellungen

Die Konfiguration landet in der Tabelle `settings` mit `member_*`-Schlüsseln, insbesondere:

- `member_dashboard_widgets`
- `member_dashboard_columns`
- `member_dashboard_section_order`
- `member_dashboard_plugin_order`
- `member_dashboard_show_custom_widgets`
- `member_dashboard_show_plugin_widgets`
- `member_dashboard_show_stats`
- `member_dashboard_show_quickstart`

Die Werte werden überwiegend als Strings oder JSON gespeichert.

---

## Wichtige Hinweise

- Diese Seite dokumentiert **Member-Dashboard-Widgets**, nicht das klassische Admin-Startseiten-Dashboard.
- Das Admin-Dashboard selbst wird in [`../dashboard/DASHBOARD.md`](../dashboard/DASHBOARD.md) beschrieben.
- Ältere Dokumentation mit der Bezeichnung „Admin Dashboard Widgets“ ist historisch und nicht mehr maßgeblich.

---

## Verwandte Seiten

- [Member Dashboard – Überblick](../member/README.md)
- [Admin-Dashboard](../dashboard/DASHBOARD.md)
- [Themes & Design – Überblick](README.md)
