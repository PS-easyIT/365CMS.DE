# Admin Dashboard-Widgets

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026 | **Datei:** `admin/design-dashboard-widgets.php`

Konfiguration und Anpassung der Widgets auf der Admin-Startseite (`admin/index.php`).

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Standard-Widgets](#2-standard-widgets)
3. [Widget-Sichtbarkeit](#3-widget-sichtbarkeit)
4. [Reihenfolge & Layout](#4-reihenfolge--layout)
5. [Custom Widgets](#5-custom-widgets)
6. [Technische Details](#6-technische-details)

---

## 1. Überblick

URL: `/admin/design-dashboard-widgets.php`

Das Dashboard-Widget-System ermöglicht die individuelle Gestaltung der Admin-Startseite pro Benutzerrolle. Admins sehen andere Widgets als Editoren.

---

## 2. Standard-Widgets

| Widget | Slug | Sichtbar für | Beschreibung |
|---|---|---|---|
| **Auf einen Blick** | `at_a_glance` | Admin, Editor | Inhalts-Statistiken (Seiten, Beiträge, Medien) |
| **Aktivitätslog** | `activity` | Admin | Letzte Admin-Aktionen |
| **Schnellentwurf** | `quick_draft` | Editor, Admin | Schnell neuen Beitragsentwurf erstellen |
| **System-Status** | `system_status` | Admin | PHP-Version, DB-Status, CMS-Version |
| **Benutzerstatistiken** | `user_stats` | Admin | Registrierungen, aktive Benutzer |
| **Support-Tickets** | `support_tickets` | Admin | Offene Support-Anfragen (Support-Plugin) |
| **Einnahmen-Übersicht** | `revenue_overview` | Admin | MRR, neue Abos (Subscription-Plugin) |

---

## 3. Widget-Sichtbarkeit

### Pro Benutzerrolle konfigurieren

```
Admin-Rolle:   Alle Widgets sichtbar
Editor-Rolle:  Auf einen Blick, Schnellentwurf, Eigene Beiträge
Member-Rolle:  Kein Admin-Dashboard Zugriff
```

**Einstellungen:**
- Checkbox per Widget per Rolle
- Änderungen sofort wirksam, kein Neustart nötig

---

## 4. Reihenfolge & Layout

### Drag & Drop Sortierung
- Widgets können per Drag & Drop umsortiert werden
- Reihenfolge wird pro Benutzer gespeichert (in `cms_user_meta`)
- „Standard wiederherstellen" setzt alle Reihenfolgen zurück

### Spalten-Layout

| Einstellung | Auswirkung |
|---|---|
| 1 Spalte | Alle Widgets nebeneinander gestapelt (Mobile-ähnlich) |
| 2 Spalten | Zwei-Spalten-Grid (Standard) |
| 3 Spalten | Kompakteres Layout für große Bildschirme |
| 4 Spalten | Maximale Dichte (nur sinnvoll für Widescreen-Monitore) |

---

## 5. Custom Widgets

Admins können eigene HTML-Widgets hinzufügen:

### Über die UI
1. „+ Widget hinzufügen" klicken
2. Titel eingeben
3. HTML-Inhalt einfügen (wird durch `wp_kses_post` gesäubert)
4. Sichtbarkeit nach Rolle konfigurieren
5. Speichern

**Anwendungsbeispiele:**
- Interne Notizen für das Admin-Team
- Links zu häufig genutzten externen Tools
- Aktuelle Informationen über laufende Projekte

### Über Code (Plugin-Integration)

```php
// In eurem Plugin: Widget registrieren
add_action('admin_dashboard_widgets', function ($registry) {
    $registry->register('my-custom-widget', [
        'title'      => 'Mein Plugin Status',
        'callback'   => 'MyPlugin::renderAdminWidget',
        'priority'   => 60,
        'roles'      => ['admin'],      // Welche Rollen sehen es?
        'columns'    => 1,             // Wie viele Spaltenbreiten?
        'collapsible'=> true,
    ]);
});
```

---

## 6. Technische Details

**Speicherung:**
- Widget-Konfiguration global: `cms_settings` Key `dashboard_widgets_config` (JSON)
- Benutzer-spezifische Reihenfolge: `cms_user_meta` Key `dashboard_widget_order`

```php
// Widget-Config lesen
$config = json_decode(
    get_cms_option('dashboard_widgets_config', '{}'),
    true
);

// Widget sichtbar für Benutzer?
$visible = in_array($currentUserRole, $widget['roles'] ?? ['admin', 'editor']);
```

**Hooks:**
```php
add_action('admin_dashboard_widgets', 'my_widget_registrieren');
add_filter('admin_dashboard_widget_order', 'my_order_filter', 10, 2);
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
