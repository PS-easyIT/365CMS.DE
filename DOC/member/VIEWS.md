# 365CMS – Member-Views

Kurzbeschreibung: Dokumentation der Partials im Mitgliederbereich und der typischen Daten, die beim Rendern bereitgestellt werden.

Letzte Aktualisierung: 2026-03-07

---

## Renderprinzip

Alle Partials liegen unter `CMS/member/partials/` und werden über `MemberController::render()` geladen. Dabei gilt:

- Daten werden per `extract($data)` in Variablen überführt
- `$user` steht immer zusätzlich bereit
- `member-menu.php` wird vor dem eigentlichen View geladen

---

## Wichtige Partials

| Partial | Aufgabe |
|---|---|
| `dashboard-view.php` | Dashboard-Karten, Schnellzugriffe, Widgets |
| `profile-view.php` | Profilformular |
| `security-view.php` | Passwort, 2FA, Sessions |
| `notifications-view.php` | Präferenzen und Verlauf |
| `privacy-view.php` | Datenschutz- und Exportfunktionen |
| `subscription-view.php` | Pakete, aktuelles Abo, Rechte |
| `member-menu.php` | Sidebar-Funktionen und Menüaufbau |

---

## Dashboard-View

Das Dashboard nutzt neben `$dashboardData` auch mehrere globale Einstellungen aus der Datenbank, z. B.:

- `member_dashboard_show_welcome`
- `member_dashboard_show_quickstart`
- `member_dashboard_show_stats`
- `member_dashboard_show_custom_widgets`
- `member_dashboard_show_plugin_widgets`
- `member_dashboard_show_notifications_panel`
- `member_dashboard_show_onboarding_panel`
- `member_dashboard_plugin_order`

Plugin-Widgets werden über `member_dashboard_widgets` ergänzt.

---

## Notifications-View

Die Notifications-Ansicht arbeitet mit:

- `$preferences`
- `$recentNotifications`
- `$csrfToken`

Die Frequenzauswahl unterstützt aktuell:

- `immediate`
- `hourly`
- `daily`
- `weekly`

Zusätzliche Formularsektionen können über `member_notification_settings_sections` eingebunden werden.

---

## Privacy-View

Die Datenschutzansicht verwendet u. a.:

- `$privacySettings`
- `$dataOverview`
- `$csrfPrivacy`
- `$csrfExport`
- `$csrfDelete`

Die Oberfläche arbeitet mit der Sichtbarkeitslogik:

- `public`
- `members`
- `private`

---

## Security-View

Typische Daten:

- `$securityData`
- `$activeSessions`
- `$csrfPassword`
- `$csrf2FA`

Die View enthält Sicherheits-Score, Passwortformular, 2FA-Umschaltung und Sitzungsdarstellung.

---

## Menü-Partial

`member-menu.php` stellt die Navigationslogik für den gesamten Bereich bereit. Relevante Hilfsfunktionen sind typischerweise:

- Sidebar rendern
- Menüeinträge gruppieren
- Sichtbarkeit des Subscription-Menüpunkts bestimmen

Menüeinträge werden über `member_menu_items` erweitert.

---

## Verwandte Dokumente

- [README.md](README.md)
- [CONTROLLERS.md](CONTROLLERS.md)
- [HOOKS.md](HOOKS.md)
- [SECURITY.md](SECURITY.md)

