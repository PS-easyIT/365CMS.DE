# 365CMS – Member-Hooks & Filter

Kurzbeschreibung: Erweiterungspunkte des Mitgliederbereichs für Navigation, Widgets und Benachrichtigungseinstellungen.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

---

## Überblick

Der Member-Bereich ist bewusst pluginfähig aufgebaut. Die wichtigsten Erweiterungspunkte betreffen Menü, Dashboard und Notification-Einstellungen.

---

## Bestätigte Filter im aktuellen Stand

| Hook | Ort | Zweck |
|---|---|---|
| `member_menu_items` | `member/partials/member-menu.php` | Sidebar-Einträge erweitern |
| `member_dashboard_widgets` | `member/partials/dashboard-view.php` | Dashboard-Widgets hinzufügen |
| `member_notification_preferences` | `member/includes/class-member-controller.php` | zusätzliche Präferenzen beim Speichern ergänzen |
| `member_notification_settings_sections` | `member/partials/notifications-view.php` | zusätzliche UI-Sektionen in Notifications einfügen |

---

## Weitere dokumentierte Erweiterungspunkte

Zusätzlich wird in der bestehenden Member-Implementierung und angrenzenden Dokumentation mit weiteren Hooks gearbeitet, etwa für Dashboard-Statistiken oder Profil-/Datenschutzfolgen. Diese sollten bei Änderungen jeweils gegen die konkrete Core-Implementierung geprüft werden.

---

## Beispiel: Sidebar erweitern

```php
\CMS\Hooks::addFilter('member_menu_items', function(array $items): array {
    $items[] = [
        'slug' => 'my-plugin',
        'label' => 'Mein Plugin',
        'icon' => '🔌',
        'url' => '/member/my-plugin',
        'category' => 'plugins',
    ];

    return $items;
});
```

---

## Beispiel: Dashboard-Widget ergänzen

```php
\CMS\Hooks::addFilter('member_dashboard_widgets', function(array $widgets): array {
    $widgets[] = [
        'title' => 'Mein Widget',
        'callback' => function(object $user): void {
            echo '<div class="member-card"><p>Hallo ' . htmlspecialchars($user->username) . '</p></div>';
        },
    ];

    return $widgets;
});
```

---

## Beispiel: Notification-Formular erweitern

```php
\CMS\Hooks::addFilter('member_notification_settings_sections', function(array $sections): array {
    $sections[] = [
        'title' => 'Mein Plugin',
        'icon' => '🔔',
        'callback' => function(array $preferences, object $user): void {
            // eigene Formularausgabe
        },
    ];

    return $sections;
});
```

Beim Speichern können korrespondierende Werte über `member_notification_preferences` ergänzt werden.

---

## Hinweise für Plugin-Entwicklung

- Ausgaben immer escapen
- Eingaben selbst sanitizen
- eigene Member-Routen zusätzlich absichern
- Hook-Ergebnisse möglichst klein und deterministisch halten

---

## Verwandte Dokumente

- [README.md](README.md)
- [CONTROLLERS.md](CONTROLLERS.md)
- [VIEWS.md](VIEWS.md)
- [../plugins/PLUGIN-DEVELOPMENT.md](../plugins/PLUGIN-DEVELOPMENT.md)

