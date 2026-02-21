# Member Dashboard

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026 | **Datei:** `member/index.php`

Das Member-Dashboard ist die persönliche Startseite jedes eingeloggten Benutzers und bündelt die wichtigsten Informationen auf einen Blick.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Dashboard-Widgets](#2-dashboard-widgets)
3. [Plugin-Widgets](#3-plugin-widgets)
4. [Zugang & Routing](#4-zugang--routing)
5. [Personalisierung](#5-personalisierung)
6. [Technische Details](#6-technische-details)

---

## 1. Überblick

URL: `/member` oder `/member/index.php`

Das Dashboard kombiniert:
- **System-Widgets** (immer vorhanden, fest)
- **Plan-Widgets** (abhängig vom aktuellen Abo-Paket)
- **Plugin-Widgets** (von aktivierten Plugins injiziert)

---

## 2. Dashboard-Widgets

### Willkommens-Widget
- Begrüßung mit Vorname des Mitglieds
- Anzeige des aktuellen Abo-Plans (z.B. „Free", „Pro", „Business")
- Ablaufdatum des Abos (sofern zeitlich begrenzt)

### Aktivitäts-Feed
- Letzte 10 Aktionen des Benutzers im System
- Zeitstempel, Aktivitätstyp und Link zur betreffenden Seite
- Typen: `login`, `profile_update`, `order`, `message_sent`, `file_uploaded`

### Benachrichtigungs-Widget
- Die 5 neuesten ungelesenen Benachrichtigungen
- Link zu `/member/notifications` für alle Benachrichtigungen
- Ungelesene Anzahl als Badge

### Statistik-Kacheln

| Kachel | Beschreibung |
|---|---|
| Nachrichten | Anzahl ungelesener Nachrichten |
| Favoriten | Anzahl gespeicherter Favoriten |
| Dateien | Genutzter Speicherplatz von max. Limit |
| Aktive Tickets | Offene Support-Anfragen (falls Support-Plugin aktiv) |

---

## 3. Plugin-Widgets

Aktivierte Plugins können eigene Dashboard-Widgets registrieren:

```php
CMS\Hooks::addAction('member_dashboard_widgets', function($registry) {
    $registry->register('my-plugin-widget', [
        'title'    => 'Mein Plugin',
        'callback' => 'MyPlugin::renderDashboardWidget',
        'priority' => 20,
        'plans'    => ['pro', 'business'],
    ]);
});
```

**Aktuelle Plugin-Widgets (wenn installiert):**
- `cms-experts`: Experten-Profilstatus und Anfragen
- `cms-events`: Nächste angemeldete Veranstaltungen
- `cms-jobads`: Aktive Stellenanzeigen

---

## 4. Zugang & Routing

Zugang nur für eingeloggte Benutzer – automatischer Auth-Check im `MemberController`:

```php
if (!$auth->isLoggedIn()) {
    header('Location: /login?redirect=/member');
    exit;
}
```

**Schnellnavigation:**
```
/member                  → Dashboard (diese Seite)
/member/profile          → Profil bearbeiten
/member/notifications    → Alle Benachrichtigungen
/member/subscription     → Abo & Upgrade
/member/media            → Eigene Dateien
/member/messages         → Direktnachrichten
/member/favorites        → Favoritenliste
/member/privacy          → Datenschutz & DSGVO
/member/security         → Sicherheitseinstellungen
```

---

## 5. Personalisierung

- **Widget-Reihenfolge:** Admin-seitig über `design-dashboard-widgets.php` konfigurierbar
- **Geplant (Roadmap):** Drag-and-Drop-Widget-Sortierung durch Mitglieder
- **Responsive:** Grid bricht bei mobilen Geräten auf einspaltigen Layout um

---

## 6. Technische Details

**Controller:** `CMS\Member\MemberController`

```php
$controller->render('dashboard-view', [
    'notifications' => $memberService->getRecentNotifications($user->id, 5),
    'stats'         => $memberService->getDashboardStats($user->id),
    'activities'    => $memberService->getRecentActivities($user->id, 10),
    'subscription'  => $memberService->getUserSubscription($user->id),
    'pluginWidgets' => CMS\Hooks::applyFilters('member_dashboard_widgets', []),
]);
```

**Hooks:**
```php
add_action('member_dashboard_widgets', 'mein_plugin_widget_registrieren');
add_filter('member_dashboard_stats', 'mein_plugin_stats_erweitern', 10, 2);
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
