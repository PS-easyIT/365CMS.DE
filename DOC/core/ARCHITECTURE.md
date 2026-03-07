# 365CMS – Systemarchitektur

Kurzbeschreibung: Architekturüberblick über Bootstrap, Routing, Admin-Module, Services und Datenzugriff in 365CMS 2.3.1.

Letzte Aktualisierung: 2026-03-07

---

## Überblick

365CMS folgt einer modularen, serviceorientierten PHP-Architektur mit klarer Aufteilung zwischen Einstiegspunkten, Core-Klassen, Services, Admin-Modulen, Themes und Plugins.

```text
Presentation      → Themes, Frontend-Templates, Admin-Views
Application       → Router, Hooks, PluginManager, ThemeManager
Business Logic    → Auth, Security, PageManager, Module-Logik
Services          → UpdateService, BackupService, ThemeCustomizer, u. a.
Persistence       → Database, SchemaManager, MigrationManager
Configuration     → config.php (Stub) + config/app.php
```

---

## Konfiguration

Wichtig für aktuelle Installationen:

- `CMS/config.php` ist ein Stub für Abwärtskompatibilität
- die echte Konfiguration liegt in `CMS/config/app.php`

---

## Bootstrap und Laufzeit

Der typische Startpfad lautet:

1. Request trifft auf `index.php` oder einen Admin-Entry-Point
2. `config.php` lädt `config/app.php`
3. der Bootstrap initialisiert Container und Kernservices
4. `Database` verbindet sich
5. `SchemaManager` prüft und erstellt Basistabellen idempotent
6. `Router` löst die Route auf
7. Theme, Controller oder Admin-View erzeugen die Ausgabe

---

## Wichtige Kernkomponenten

| Komponente | Verantwortung |
|---|---|
| `Bootstrap` | Initialisierung und Service-Container |
| `Database` | Datenbankzugriff |
| `SchemaManager` | Basisschema anlegen und pflegen |
| `MigrationManager` | ergänzende Schemaanpassungen |
| `Router` | Frontend- und Systemrouten |
| `Auth` | Login, Rollen, Zugriffskontrolle |
| `Security` | CSRF, Header, Schutzlogik |
| `Hooks` | erweiterbare Actions und Filter |
| `PluginManager` | aktive Plugins und deren Einstiegspunkte |
| `ThemeManager` | Theme-Lade- und Renderlogik |

---

## Admin-Architektur

Der Admin-Bereich ist in 2.3.x stärker entkoppelt als ältere Stände:

- Entry-Points unter `CMS/admin/`
- Fachlogik unter `CMS/admin/modules/`
- Ausgabe unter `CMS/admin/views/`
- gemeinsame Navigation in `CMS/admin/partials/sidebar.php`

SEO, Performance, Recht, Diagnose und System bestehen heute aus mehreren spezialisierten Unterseiten statt je einer Sammelseite.

---

## Datenhaltung

Die Basistabellen werden durch `SchemaManager` erstellt. Zusätzlich erzeugen spezialisierte Module und Services weitere Tabellen bei Bedarf, zum Beispiel für Redirects, SEO-Metadaten, Cookie-Services, Privacy-Requests oder Firewall-Regeln.

---

## Was nicht mehr als Architektur-Referenz gelten sollte

Folgende Aussagen aus älteren Dokumenten sind überholt:

- „die komplette Konfiguration liegt in `config.php`“
- „SEO ist eine einzelne Seite `seo.php`“
- „System und Diagnose liegen vollständig in `system.php`“
- „Backups werden über `backup.php` verwaltet“