# AntiSpam-Einstellungen

Kurzbeschreibung: Beschreibt die aktuelle Anti-Spam-Verwaltung im Admin, die konfigurierbaren Schutzmechanismen und die serverseitige Speicherung der Blacklist- und CAPTCHA-Einstellungen.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

## Überblick

Die Anti-Spam-Verwaltung ist über `/admin/antispam` erreichbar und wird serverseitig durch `CMS/admin/modules/security/AntispamModule.php` gesteuert. Die Seite bündelt Basisschutz, Formularhärtung, Blacklist-Verwaltung und optionale CAPTCHA-Anbindung.

## Konfigurierbare Schutzmechanismen

Die aktuelle Implementierung arbeitet mit einer Kombination aus einfachen, performanten Prüfungen und optionalen externen Diensten.

### Basisschutz

- globaler Schalter `antispam_enabled`
- Honeypot-Feld über `antispam_honeypot`
- minimale Formularzeit über `antispam_min_time`
- maximale Linkanzahl über `antispam_max_links`
- Blockade leerer User-Agents über `antispam_block_empty_ua`

Diese Prüfungen sind besonders relevant für öffentliche Formulare, Registrierungen und kontaktnahe Eingaben. Welche Frontend-Formulare die Werte konkret auswerten, hängt vom jeweiligen Handler oder Plugin ab.

### CAPTCHA-Unterstützung

Die Admin-Seite verwaltet optionale Zugangsdaten für zusätzliche Prüfmechanismen. Im Modul sichtbar sind insbesondere Einstellungen für reCAPTCHA-Schlüssel. Frühere Dokumentationsstände mit hCaptcha- oder Turnstile-Fokus beschreiben nicht den aktuellen Kernstand des Moduls.

## Blacklist-Verwaltung

Spam-Indikatoren werden in der Tabelle `spam_blacklist` verwaltet. Unterstützte Typen sind aktuell:

- `word`
- `email`
- `ip`
- `domain`

Damit lassen sich sowohl Inhalte als auch Herkunft oder bekannte Absender gezielt blockieren. Das Modul stellt Funktionen zum Hinzufügen und Löschen einzelner Blacklist-Einträge bereit.

## Typische Admin-Aktionen

Die Oberfläche unterstützt im Kern drei Aufgabenbereiche:

- Speichern der Anti-Spam-Grundeinstellungen
- Hinzufügen neuer Blacklist-Einträge
- Löschen vorhandener Blacklist-Einträge

Alle Änderungen werden über das Modul verarbeitet und mit Audit-Log-Einträgen versehen.

## Audit-Logging

Die aktuelle Implementierung protokolliert Anti-Spam-relevante Änderungen über den `AuditLogger`. Dazu gehören insbesondere:

- Änderungen an den Grundeinstellungen
- neue Blacklist-Einträge
- gelöschte Blacklist-Einträge

Dadurch sind Konfigurationsänderungen nachvollziehbar, ohne dass ein separates „Spam-Log“-Subsystem auf Admin-Ebene dokumentiert werden muss.

## Sicherheit

Die Seite folgt dem üblichen Admin-Muster:

- Zugriff nur für Administratoren
- CSRF-Schutz für POST-Aktionen
- serverseitige Validierung numerischer und boolescher Einstellungen
- Sanitierung von Blacklist-Werten und Typangaben

## Relevante Einstellungen

| Key | Zweck |
|---|---|
| `antispam_enabled` | globaler Ein-/Aus-Schalter |
| `antispam_honeypot` | aktiviert Honeypot-Prüfung |
| `antispam_min_time` | Mindestdauer bis zur erlaubten Formularabgabe |
| `antispam_max_links` | maximale Anzahl erlaubter Links |
| `antispam_block_empty_ua` | blockiert Requests ohne User-Agent |
| `recaptcha_site_key` | öffentlicher CAPTCHA-Key |
| `recaptcha_secret_key` | geheimer CAPTCHA-Key |

## Relevante Dateien

| Datei | Zweck |
|---|---|
| `CMS/admin/antispam.php` | Admin-Entry-Point |
| `CMS/admin/modules/security/AntispamModule.php` | Speichern, Laden und Blacklist-Handling |
| `CMS/admin/views/security/antispam.php` | Ausgabe der Verwaltungsoberfläche |

## Verwandte Dokumente

- [FIREWALL.md](FIREWALL.md)
- [DSGVO.md](../legal/DSGVO.md)
- [Member-Sicherheit](../../member/SECURITY.md)
