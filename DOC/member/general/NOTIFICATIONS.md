# 365CMS – Benachrichtigungen

Kurzbeschreibung: Dokumentation der Notifications-Seite für Präferenzen, aktuelle Meldungen und Erweiterungspunkte.

Letzte Aktualisierung: 2026-03-07

**Route:** `/member/notifications`

---

## Überblick

Die Notifications-Seite kombiniert zwei Dinge:

1. Anzeige der zuletzt geladenen Benachrichtigungen
2. Speicherung persönlicher Notification-Präferenzen

---

## Gespeicherte Präferenzfelder

Der aktuelle Handler `handleNotificationActions()` speichert insbesondere:

- `email_notifications`
- `email_marketing`
- `email_updates`
- `email_security`
- `browser_notifications`
- `desktop_notifications`
- `mobile_notifications`
- `notify_new_features`
- `notify_promotions`
- `notification_frequency`

Die Frequenz unterstützt aktuell:

- `immediate`
- `hourly`
- `daily`
- `weekly`

---

## Anzeige aktueller Meldungen

Wenn `recentNotifications` befüllt ist, zeigt die View:

- Icon und Farbkennung
- Titel und Text
- relative Zeitangabe
- Kennzeichnung gelesen/ungelesen

---

## Wichtige Realitätshinweise

- Der Button **„Alle als gelesen markieren“** ist in der aktuellen View nur als JavaScript-Platzhalter angelegt.
- Auch der Link **„Alle anzeigen“** verweist auf `/member/notifications/all`; diese Dokumentation sollte nur dann als führend betrachtet werden, wenn die zugehörige Route tatsächlich projektweit vorhanden ist.
- Browser-Testbenachrichtigungen laufen clientseitig über die Notification API des Browsers.

---

## Erweiterungspunkte

Die Seite ist pluginfähig aufgebaut:

| Hook | Zweck |
|---|---|
| `member_notification_preferences` | zusätzliche Präferenzwerte beim Speichern ergänzen |
| `member_notification_settings_sections` | zusätzliche Einstellungsabschnitte rendern |

---

## Verwandte Dokumente

- [DASHBOARD.md](DASHBOARD.md)
- [PRIVACY.md](PRIVACY.md)
- [../HOOKS.md](../HOOKS.md)
