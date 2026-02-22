# Member-Bereich – Hooks & Filter

**Datum:** 18. Februar 2026

---

## Überblick

Der Member-Bereich stellt Plugins **4 Hooks** zur Verfügung, um Menü, Widgets und Benachrichtigungseinstellungen zu erweitern, ohne Core-Dateien zu modifizieren.

---

## Filter

### `member_menu_items`

Ermöglicht das Hinzufügen oder Entfernen von Menüpunkten in der Member-Sidebar.

**Registrierung in:** `member/partials/member-menu.php` – Funktion `getMemberMenuItems()`

```php
\CMS\Hooks::addFilter('member_menu_items', function(array $items): array {
    $items[] = [
        'slug'     => 'my_plugin_page',
        'label'    => 'Mein Plugin',
        'icon'     => '🔌',
        'url'      => '/member/my-plugin',
        'active'   => false,
        'category' => 'plugins',  // Erscheint unter "Erweiterungen"
    ];
    return $items;
});
```

**Parameter:**
| # | Typ | Beschreibung |
|---|-----|-------------|
| 1 | `array` | Aktuelles Menü-Array |

**Rückgabe:** `array` – Modifiziertes Menü-Array

---

### `member_notification_preferences`

Ermöglicht das Hinzufügen zusätzlicher Präferenzen beim Speichern.

**Registrierung in:** `includes/class-member-controller.php` – `handleNotificationActions()`

```php
\CMS\Hooks::addFilter('member_notification_preferences', 
    function(array $preferences, int $userId): array {
        // Eigene Plugin-Präferenzen hinzufügen
        $preferences['my_plugin_notify'] = isset($_POST['my_plugin_notify']);
        return $preferences;
    }, 
    10, 
    2  // Anzahl der Parameter
);
```

**Parameter:**
| # | Typ | Beschreibung |
|---|-----|-------------|
| 1 | `array` | Präferenzen-Array |
| 2 | `int` | User-ID |

**Rückgabe:** `array` – Erweitertes Präferenzen-Array

---

### `member_notification_settings_sections`

Fügt eigene Abschnitte in das Benachrichtigungs-Einstellungsformular ein.

**Registrierung in:** `partials/notifications-view.php`

```php
\CMS\Hooks::addFilter('member_notification_settings_sections', 
    function(array $sections): array {
        $sections[] = [
            'title'    => 'Mein Plugin',
            'icon'     => '🔌',
            'callback' => function(array $preferences, object $user): void {
                ?>
                <div class="member-toggle-item">
                    <div class="toggle-info">
                        <strong>Plugin-Benachrichtigungen</strong>
                        <p>Benachrichtigungen von Mein Plugin</p>
                    </div>
                    <label class="member-toggle">
                        <input type="checkbox" name="my_plugin_notify"
                               <?php echo ($preferences['my_plugin_notify'] ?? false) ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <?php
            },
        ];
        return $sections;
    }
);
```

**Parameter:**
| # | Typ | Beschreibung |
|---|-----|-------------|
| 1 | `array` | Bestehende Sektionen |

**Rückgabe:** `array` – Erweitertes Sektionen-Array

---

## Actions

### `member_dashboard_widgets`

Fügt eigene Widgets in den Dashboard-Plugin-Bereich ein.

**Registrierung in:** `partials/dashboard-view.php`

```php
\CMS\Hooks::addFilter('member_dashboard_widgets', function(array $widgets): array {
    $widgets[] = [
        'title'    => 'Mein Widget',
        'callback' => function(object $user): void {
            echo '<div class="member-card">';
            echo '<div class="member-card-header"><h3>Mein Plugin Widget</h3></div>';
            echo '<div class="member-card-body"><p>Hallo, ' . htmlspecialchars($user->username) . '!</p></div>';
            echo '</div>';
        },
    ];
    return $widgets;
});
```

**Parameter:**
| # | Typ | Beschreibung |
|---|-----|-------------|
| 1 | `array` | Bestehende Widgets |

**Rückgabe:** `array` – Erweitertes Widgets-Array

---

## Prioritäten & Ausführungsreihenfolge

```
Plugin-Registrierung (bootstrap)
    → Hooks werden gesammelt

HTTP Request → member/partials/member-menu.php geladen
    → member_menu_items (Filter, collected)

HTTP Request → Dashboard rendering
    → member_dashboard_widgets (Filter, bei Render-Zeit)

HTTP Request → Notifications POST
    → member_notification_preferences (Filter, bei POST-Verarbeitung)

HTTP Request → Notifications rendering
    → member_notification_settings_sections (Filter, bei Render-Zeit)
```

---

## Sicherheitshinweise

- Alle Hook-Callbacks, die Benutzereingaben verarbeiten, **müssen** selbst sanitizen
- Widget-Callbacks erhalten `$user` als Objekt – Ausgaben mit `htmlspecialchars()` escapen
- Neue Menü-URLs müssen eigene Berechtigungsprüfungen implementieren
- Im Präferenzen-Filter: nur Boolean/String-Werte hinzufügen, keine DB-Queries direkt
