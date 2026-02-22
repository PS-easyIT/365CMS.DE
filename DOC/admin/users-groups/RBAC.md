# RBAC – Rollen & Berechtigungen

**Datei:** `admin/rbac.php`

---

## Übersicht

Das Role-Based Access Control (RBAC) System ermöglicht granulare Berechtigungsverwaltung auf Gruppen-Ebene. Statt einzelne Benutzer mit Rechten zu versehen, werden Benutzergruppen Capabilities zugewiesen.

---

## Konzept

```
Benutzer → Gruppe → Capabilities
```

Ein Benutzer gehört einer Gruppe an. Die Gruppe hat eine Menge von 8 Capabilities, die bestimmen, was der Benutzer im System tun darf.

---

## Vordefinierte Rollen

| Rolle | Beschreibung | Standard-Capabilities |
|-------|--------------|----------------------|
| `admin` | Vollzugriff | Alle Capabilities |
| `editor` | Inhalte verwalten | `edit_posts`, `publish_posts`, `upload_media`, `manage_pages` |
| `moderator` | Moderation | `edit_posts`, `moderate_comments`, `view_analytics` |
| `member` | Basis-Mitglied | `view_content`, `edit_own_profile` |

---

## 8 Granulare Capabilities

| Capability | Beschreibung |
|-----------|--------------|
| `manage_users` | Benutzer erstellen, bearbeiten, löschen |
| `manage_content` | Seiten und Beiträge verwalten |
| `publish_posts` | Beiträge und Seiten veröffentlichen |
| `upload_media` | Medien hochladen und verwalten |
| `manage_plugins` | Plugins aktivieren/deaktivieren |
| `manage_themes` | Themes aktivieren und anpassen |
| `view_analytics` | Statistiken und Reports einsehen |
| `manage_settings` | Systemeinstellungen ändern |

---

## Gruppen-Verwaltung

### Gruppe erstellen
1. **Name** – Eindeutiger Gruppenname (z.B. "Redaktion", "Partner")
2. **Beschreibung** – Optionale Erklärung des Zwecks
3. **Capabilities** – Checkboxen für jede der 8 Capabilities
4. **Mitglieder** – Bestehende Benutzer zuweisen

### Gruppe bearbeiten
- Capabilities können jederzeit angepasst werden
- Änderungen gelten sofort für alle Mitglieder der Gruppe
- Benutzer werden in der Mitgliederliste angezeigt

### Gruppe löschen
- Alle Mitglieder werden automatisch in die Standard-Gruppe verschoben
- Nicht möglich bei "admin"-Gruppe

---

## Capability-Prüfung im Code

```php
// In PHP-Dateien
$auth = \CMS\Auth::instance();

if ($auth->hasCapability('manage_users')) {
    // Benutzer-Management anzeigen
}

if ($auth->hasCapability('publish_posts')) {
    // Veröffentlichungs-Button anzeigen
}
```

---

## RBAC-Seite im Admin

Die `rbac.php` zeigt:
- Alle Gruppen mit ihren Capabilities in einer Matrix-Ansicht
- Farbkodierung: ✅ Berechtigt / ❌ Nicht berechtigt
- Schnell-Edit: Einzelne Capabilities per Klick umschalten
- Bulk-Edit: Gesamte Gruppe neu konfigurieren

---

## Verwandte Seiten

- [Benutzerverwaltung](USERS.md)
- [Gruppen](GROUPS.md)
