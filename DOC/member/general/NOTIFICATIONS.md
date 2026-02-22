# Benachrichtigungen


Zentrale Inbox für alle System- und Benutzer-Benachrichtigungen im Mitglieder-Bereich.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Benachrichtigungstypen](#2-benachrichtigungstypen)
3. [Lesen & Verwalten](#3-lesen--verwalten)
4. [Filter & Suche](#4-filter--suche)
5. [E-Mail-Einstellungen](#5-e-mail-einstellungen)
6. [Technische Details](#6-technische-details)

---

## 1. Überblick

URL: `/member/notifications`

Benachrichtigungen informieren Mitglieder über relevante Ereignisse. Sie werden in der Tabelle `cms_notifications` gespeichert und als Badge-Zähler im Dashboard angezeigt.

**Lebenszyklus:**
1. Benachrichtigung wird erstellt (via Hook oder direktem API-Aufruf)
2. Anzeige im Dashboard-Widget (Badge) und auf der Notifications-Seite
3. Beim Klick: Status → `read`, Weiterleitung zur verknüpften Seite
4. Automatische Bereinigung nach 90 Tagen

---

## 2. Benachrichtigungstypen

| Typ | Icon | Beschreibung | Beispiel |
|---|---|---|---|
| `system` | ⚙️ | Wichtige System-Meldungen | Update verfügbar |
| `security` | 🔒 | Sicherheitsereignisse | Neuer Login von unbekanntem Gerät |
| `subscription` | 💳 | Abo-Ereignisse | Abo läuft in 7 Tagen ab |
| `message` | ✉️ | Neue Direktnachricht | Neue Nachricht von Max Muster |
| `info` | ℹ️ | Allgemeine Informationen | Neue Funktion verfügbar |
| `warning` | ⚠️ | Warnungen | Speicherplatz fast aufgebraucht |
| `plugin` | 🔌 | Plugin-spezifisch | Neue Buchungsanfrage |

---

## 3. Lesen & Verwalten

### Einzelne Benachrichtigung
- **Klick** → markiert als gelesen und öffnet verknüpften Link
- **„Als gelesen markieren"** – ohne Weiterleitung

### Massenaktionen
- **Alle als gelesen markieren** – setzt alle Notifications auf `read`
- **Alle löschen** – entfernt alle (nach Bestätigung)
- **Auswahl löschen** – Checkboxen + Bulk-Action

### Pagination
- 20 Einträge pro Seite
- Sortierung: Neueste zuerst (Standard) / Älteste zuerst

---

## 4. Filter & Suche

| Filter | Optionen |
|---|---|
| **Status** | Alle, Ungelesen, Gelesen |
| **Typ** | Alle, System, Sicherheit, Abo, Nachricht, Info, Plugin |
| **Zeitraum** | Heute, Letzte 7 Tage, Letzter Monat, Alle |

Filter als kombinierbare GET-Parameter:
```
/member/notifications?type=security&status=unread
```

---

## 5. E-Mail-Einstellungen

| Typ | Standard | Konfigurierbar |
|---|---|---|
| `security` | ✅ immer | ❌ Nein (Sicherheitsschutz) |
| `subscription` | ✅ aktiv | ✅ Ja |
| `message` | ✅ aktiv | ✅ Ja |
| `system` | ❌ deaktiviert | ✅ Ja |
| `info` | ❌ deaktiviert | ✅ Ja |

Einstellungen als `user_meta` mit Key `notification_prefs` (JSON).

---

## 6. Technische Details

**Datenbank-Tabelle:** `cms_notifications`

```sql
CREATE TABLE cms_notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    type        VARCHAR(50) NOT NULL DEFAULT 'info',
    title       VARCHAR(255) NOT NULL,
    message     TEXT,
    link        VARCHAR(500),
    is_read     TINYINT(1) DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_unread (user_id, is_read)
);
```

**API – Benachrichtigung erstellen:**
```php
use CMS\Services\NotificationService;

NotificationService::create([
    'user_id' => $userId,
    'type'    => 'info',
    'title'   => 'Willkommen!',
    'message' => 'Ihr Konto wurde erfolgreich erstellt.',
    'link'    => '/member/profile'
]);
```

**Hooks:**
```php
do_action('cms_notification_created', $notificationId, $userId, $type);
do_action('cms_notification_read', $notificationId, $userId);
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
