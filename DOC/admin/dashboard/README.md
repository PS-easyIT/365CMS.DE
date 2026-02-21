# Admin Dashboard

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026 | **Datei:** `admin/index.php`

Die Startseite des Admin-Bereichs bietet einen schnellen Überblick über den gesamten Systemstatus und ermöglicht schnellen Zugriff auf die wichtigsten Verwaltungsaufgaben.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Dashboard-Widgets](#2-dashboard-widgets)
3. [System-Status](#3-system-status)
4. [Schnellzugriff](#4-schnellzugriff)
5. [Widget-Anpassung](#5-widget-anpassung)
6. [Technische Details](#6-technische-details)

---

## 1. Überblick

URL: `/admin` oder `/admin/index.php`

Das Admin-Dashboard ist die erste Seite nach dem Login in den Administrationsbereich. Es fasst den aktuellen Status aller Systembereiche zusammen.

**Zugangskontrolle:** Nur Benutzer mit Rolle `admin` oder `editor` können auf den Admin-Bereich zugreifen.

---

## 2. Dashboard-Widgets

### Inhalts-Übersicht
Die erste Widget-Reihe zeigt die aktuellen Inhaltsmengen:

| Metric | Beschreibung |
|---|---|
| Beiträge | Anzahl veröffentlichter Blog-Artikel |
| Seiten | Anzahl veröffentlichter statischer Seiten |
| Kommentare | Anzahl moderierter Kommentare (falls aktiviert) |
| Medien | Anzahl hochgeladener Dateien |

### Benutzer-Statistiken
- Gesamtzahl registrierter Benutzer
- Neue Registrierungen der letzten 7 Tage
- Aktive Benutzer (Login in letzten 30 Tagen)
- Offene Aktivierungen (noch nicht bestätigte Accounts)

### Aktivitätslog
- Chronologische Auflistung der letzten 20 Admin-Aktionen
- Zeigt: Benutzer, Aktion, Datum/Zeit, betroffenes Objekt
- Direkt-Links zum bearbeiteten Element

### Letzte Inhalte
- Die 5 zuletzt bearbeiteten Seiten/Beiträge
- Status-Badge (Veröffentlicht, Entwurf, Privat)
- Schnell-Edit-Links

---

## 3. System-Status

Zusammenfassung der Server- und CMS-Gesundheit:

| Indikator | OK | Warnung | Kritisch |
|---|---|---|---|
| **CMS-Version** | 0.26.13 (aktuell) | Update verfügbar | — |
| **PHP-Version** | 8.2+ | 8.0–8.1 | < 8.0 |
| **Datenbankverbindung** | Verbunden | — | Getrennt |
| **Schreibrechte** | Alle OK | Teilweise eingeschränkt | `/uploads` nicht schreibbar |
| **WP_DEBUG** | `false` | — | `true` (Production!) |
| **Disk-Speicher** | > 20% frei | 5–20% frei | < 5% frei |

---

## 4. Schnellzugriff

Shortcut-Buttons für häufige Aufgaben:

```
[+ Neuer Beitrag]   [+ Neue Seite]   [+ Neuer Benutzer]
[Medien hochladen]  [Cache leeren]   [Backup erstellen]
```

---

## 5. Widget-Anpassung

Dashboard-Widgets können über `admin/design-dashboard-widgets.php` angepasst werden:
- **Ein-/Ausblenden:** Nicht benötigte Widgets deaktivieren
- **Reihenfolge:** Drag & Drop Sortierung
- **Spalten-Layout:** 1–4 Spalten konfigurierbar

→ Siehe [DASHBOARD-WIDGETS.md](../themes-design/DASHBOARD-WIDGETS.md) für Details.

---

## 6. Technische Details

**Controller:** `CMS\Admin\DashboardController`

```php
// Dashboard-Daten laden
$stats = [
    'posts'    => $db->get_var("SELECT COUNT(*) FROM cms_posts WHERE status='published' AND type='post'"),
    'pages'    => $db->get_var("SELECT COUNT(*) FROM cms_posts WHERE status='published' AND type='page'"),
    'users'    => $db->get_var("SELECT COUNT(*) FROM cms_users WHERE status='active'"),
    'media'    => $db->get_var("SELECT COUNT(*) FROM cms_media"),
    'php_ver'  => PHP_VERSION,
    'cms_ver'  => CMS_VERSION,
    'db_ok'    => $db->ping(),
];
```

**Hooks:**
```php
// Eigene Widgets hinzufügen
add_action('admin_dashboard_widgets', function($registry) {
    $registry->register('my-widget', [
        'title'    => 'Mein Widget',
        'callback' => 'my_widget_render',
        'columns'  => 1,
        'priority' => 50,
    ]);
});
```

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
