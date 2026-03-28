# 365CMS – Cookie-Manager

Kurzbeschreibung: Verwaltung von Cookie-Kategorien, Diensten, Banner-Texten und der öffentlichen Einwilligungsseite.

Letzte Aktualisierung: 2026-03-28 · Version 2.8.0 RC

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

Sie wird nicht als normale CMS-Seite gepflegt, sondern vom `CookieConsentService` bereitgestellt und mit nativen 365CMS-Assets hydratisiert. Eine aktive Vendor-Runtime aus `CMS/assets/cookieconsent/` ist dafür nicht mehr nötig.

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

Wichtige Bausteine im aktuellen Stand:

- Admin-Einstieg: `CMS/admin/cookie-manager.php`
- Modul: `CMS/admin/modules/legal/CookieManagerModule.php`
- Frontend/Runtime: `CMS/core/Services/CookieConsentService.php`
- Initialisierung: `CMS/assets/js/cookieconsent-init.js`

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

---

## Verwandte Dokumente

- [README.md](README.md)
- [../../assets/cookieconsent/README.md](../../assets/cookieconsent/README.md)
- [../../core/SERVICES.md](../../core/SERVICES.md)
