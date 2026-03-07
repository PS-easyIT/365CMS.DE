# 365CMS – Cookie-Manager

Kurzbeschreibung: Verwaltung von Cookie-Kategorien, Diensten, Banner-Texten und der öffentlichen Einwilligungsseite.

Letzte Aktualisierung: 2026-03-07

**Admin-Route:** `/admin/cookie-manager`

---

## Überblick

Der Cookie-Manager bündelt die Pflege der Consent-Konfiguration im Admin und die Auslieferung der öffentlichen Präferenzseite im Frontend.

Wichtige Aufgaben:

- Cookie-Kategorien verwalten
- Dienste und zugehörige Cookies pflegen
- Banner- und Textbausteine definieren
- kuratierte Dienste importieren
- Website-Scan auslösen

---

## Öffentliche Einwilligungsseite

Die öffentliche Seite für Besucher lautet `/cookie-einstellungen`.

Sie wird nicht als normale CMS-Seite gepflegt, sondern vom Cookie-Consent-Service bereitgestellt und clientseitig hydratisiert. Dadurch bleiben Consent-Status, Kategorien und UI konsistent zwischen Banner, Einstellungen und Protokollierung.

---

## Verwaltungsbereiche im Admin

### Kategorien

Typische Kategorien sind:

- notwendig
- funktional
- statistik
- marketing

### Dienste

Pro Dienst werden üblicherweise gepflegt:

- Dienstname
- Anbieter
- Kategorie
- Cookie-Namen
- Zweckbeschreibung
- Laufzeit
- Datenschutz-URL

### Einstellungen

Konfigurierbar sind unter anderem Banner-Texte, Button-Beschriftungen, Standardzustände, Scanner-Einstellungen und ergänzende Hinweise für öffentlich dargestellte Datenschutzhinweise.

---

## Technische Grundlage

Der Admin-Einstieg lädt `CMS/admin/modules/legal/CookieManagerModule.php`.

Im Datenmodell spielen insbesondere diese Tabellen eine Rolle:

- `cookie_categories`
- `cookie_services`

---

## Typische Aktionen

| Aktion | Bedeutung |
|---|---|
| `save_settings` | globale Cookie-Manager-Einstellungen speichern |
| `save_category` | Kategorie anlegen oder aktualisieren |
| `delete_category` | Kategorie löschen |
| `save_service` | Dienst anlegen oder aktualisieren |
| `delete_service` | Dienst löschen |
| `import_curated_service` | vordefinierten Dienst importieren |
| `run_scan` | Cookie-Scanner starten |
