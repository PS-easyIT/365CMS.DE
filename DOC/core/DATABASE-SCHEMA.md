# 365CMS – Datenbank-Schema

Kurzbeschreibung: Überblick über das aktuell verifizierte Core-Schema und die zusätzlich von Modulen erzeugten Tabellen.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

---

## Überblick

Das Datenbankschema besteht in 365CMS aus zwei Ebenen:

1. **Core-Schema** über `CMS\SchemaManager`
2. **Feature- und Modul-Tabellen** über spezialisierte Services oder Admin-Module

Das aktuell verifizierte Core-Schema erstellt **30 Basistabellen**. Darüber hinaus kommen je nach aktivem Modul weitere Tabellen hinzu.

Das Tabellenpräfix wird über die Konfiguration in `CMS/config/app.php` gesteuert.

---

## Basistabellen des `SchemaManager`

| Tabelle | Zweck |
|---|---|
| `users` | Benutzerkonten |
| `user_meta` | zusätzliche Benutzerdaten |
| `roles` | Rollen und Capabilities |
| `settings` | zentrale Einstellungen |
| `sessions` | Session-Daten |
| `pages` | Seiten |
| `page_revisions` | Seitenrevisionen |
| `landing_sections` | Landing-Page-Bausteine |
| `activity_log` | Aktivitätsprotokoll |
| `cache` | Cache-Einträge |
| `login_attempts` | Rate-Limiting / Login-Muster |
| `plugins` | Plugin-Registry |
| `plugin_meta` | Plugin-Metadaten |
| `theme_customizations` | Theme-/Customizer-Werte |
| `subscription_plans` | Paket- und Planverwaltung |
| `user_subscriptions` | Benutzerabos |
| `user_groups` | Gruppen |
| `user_group_members` | Gruppenzuordnungen |
| `subscription_usage` | Nutzungszähler |
| `post_categories` | Kategorien |
| `posts` | Beiträge |
| `orders` | Bestellungen |
| `blocked_ips` | blockierte IPs |
| `failed_logins` | Fehlanmeldungen |
| `media` | Medienbibliothek |
| `page_views` | Analytics / Aufrufe |
| `comments` | Kommentare |
| `messages` | Member-Nachrichten |
| `audit_log` | sicherheits- und adminrelevante Audits |
| `custom_fonts` | lokal verwaltete Webfonts |

---

## Zusätzliche Modultabellen

| Tabelle | Erzeuger |
|---|---|
| `seo_meta` | `SEOService` |
| `redirect_rules` | `RedirectService` |
| `not_found_logs` | `RedirectService` |
| `cookie_categories` | Cookie-Manager |
| `cookie_services` | Cookie-Manager |
| `privacy_requests` | Privacy-/Deletion-Module |
| `firewall_rules` | Firewall-Modul |
| `spam_blacklist` | AntiSpam-Modul |
| `role_permissions` | Rollen-/Rechte-Modul |
| `menus` | Menu-Editor |
| `menu_items` | Menu-Editor |

---

## Wichtige Modellhinweise

### Bestellungen

Im aktuellen Schema ist für Bestellungen `plan_id` maßgeblich. Ältere Bezeichnungen wie `package_id` gelten nur als Legacy-Kontext.

### Theme-Anpassungen

Die Tabelle `theme_customizations` nutzt heute präzisere Feldnamen wie `theme_slug`, `setting_category`, `setting_key` und `setting_value`.

### Auditierung

`audit_log` ergänzt das generischere `activity_log` um Kategorien, Schweregrad und sicherheitsrelevante Metadaten.

---

## Zugriffsregeln für Entwicklerdokumentation

Für Datenbankbeispiele gilt in diesem Projekt:

- `Database::query()` ist für rohe SQL-Ausführung gedacht
- parametrisierte Statements laufen über `prepare()`/`execute()` oder passende Helper

Beispiel zum Lesen eines Settings:

```php
$value = $db->get_var(
    "SELECT option_value FROM {$p}settings WHERE option_name = ?",
    ['active_theme']
);
```

Beispiel für parametrisierte Ausführung:

```php
$db->execute(
    "INSERT INTO {$p}settings (option_name, option_value) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)",
    ['active_theme', 'cms-default']
);
```
