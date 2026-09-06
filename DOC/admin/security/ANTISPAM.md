# AntiSpam-Einstellungen

Kurzbeschreibung: Beschreibt die aktuelle Anti-Spam-Verwaltung im Admin, die lokalen Schutzmechanismen und die serverseitige Blacklist-Speicherung.

Letzte Aktualisierung: 2026-05-09 · Version 2.9.626

## Überblick

Die Anti-Spam-Verwaltung ist über `/admin/antispam` erreichbar und wird serverseitig durch `CMS/admin/modules/security/AntispamModule.php` gesteuert. Die Seite bündelt Basisschutz, Formularhärtung und Blacklist-Verwaltung. Die Laufzeit-Auswertung der globalen Regeln erfolgt zentral über `CMS/core/Services/AntispamService.php`. Externe CAPTCHA-Dienste werden im Public-Runtime-Vertrag nicht geladen.

## Konfigurierbare Schutzmechanismen

Die aktuelle Implementierung arbeitet bewusst mit lokalen, performanten Prüfungen ohne externe Public-Assets.

### Basisschutz

- globaler Schalter `antispam_enabled`
- Honeypot-Feld über `antispam_honeypot`
- minimale Formularzeit über `antispam_min_time`
- maximale Linkanzahl über `antispam_max_links`
- Blockade leerer User-Agents über `antispam_block_empty_ua`

Diese Prüfungen werden serverseitig zentral über `CMS/core/Services/AntispamService.php` ausgewertet. Aktuell nutzen mindestens der Core-Kommentarpfad (`CMS/core/Services/CommentService.php`) und aktive `cms-contact`-Formulare denselben Runtime-Service. Das Default-Theme liefert dafür Honeypot- und Mindestzeit-Felder mit; die Kontaktformulare senden zusätzlich einen Formular-Timestamp, damit `antispam_min_time` auch dort serverseitig erzwungen wird.

### CAPTCHA-Unterstützung

Externe CAPTCHA-Dienste wie reCAPTCHA, hCaptcha oder Turnstile sind im Core-Runtime-Vertrag deaktiviert, weil sie Fremdskripte bzw. externe Prüf-Endpunkte voraussetzen. Der produktive Schutzumfang besteht aus Honeypot, Mindestzeit, Linklimit, User-Agent-Prüfung und Blacklist. Plugins dürfen zusätzliche lokale Prüfungen wie Mathe-Captchas ergänzen, sollen die globalen AntiSpam-Regeln aber nicht umgehen.

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

Es werden keine CAPTCHA-Secrets mehr über die AntiSpam-Oberfläche gespeichert oder beworben.

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

## Aktueller Runtime-Vertrag

- Öffentliche Kommentare und aktive `cms-contact`-Formulare nutzen dieselbe zentrale AntiSpam-Auswertung.
- Kontaktformulare dürfen optional zusätzlich ein lokales Mathe-Captcha und sessionbasiertes Rate-Limit verwenden, ersetzen damit aber nicht die globalen AntiSpam-Regeln.
- Weitere Public-Plugins mit eigenen Formularen sollten denselben Core-Service verwenden, statt einen parallelen Blacklist-/Mindestzeit-Pfad aufzubauen.

## Relevante Dateien

| Datei | Zweck |
|---|---|
| `CMS/admin/antispam.php` | Admin-Entry-Point |
| `CMS/admin/modules/security/AntispamModule.php` | Speichern, Laden und Blacklist-Handling |
| `CMS/core/Services/AntispamService.php` | zentrale Runtime-Auswertung für globale AntiSpam-Regeln |
| `CMS/core/Services/CommentService.php` | Runtime-Prüfung öffentlicher Kommentare |
| `365CMS.DE-PLUGINS/cms-contact/includes/class-frontend.php` | zentrale AntiSpam-Verdrahtung im Kontaktformular-Plugin |
| `CMS/admin/views/security/antispam.php` | Ausgabe der Verwaltungsoberfläche |

## Verwandte Dokumente

- [FIREWALL.md](FIREWALL.md)
- [DSGVO.md](../legal/DSGVO.md)
- [Member-Sicherheit](../../member/SECURITY.md)
