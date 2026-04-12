# 365CMS – Recht & Sicherheit

Kurzbeschreibung: Überblick über die aktuellen Legal-, Privacy- und Security-Module im Admin-Bereich.

Letzte Aktualisierung: 2026-04-07 · Version 2.9.0

Der Bereich ist in zwei Gruppen gegliedert:

- **Recht**
- **Sicherheit**

Die Menüstruktur wird aus `CMS/admin/partials/sidebar.php` gespeist. Dadurch gelten die folgenden Routen als maßgeblich.

---

## Recht

| Route | Zweck |
|---|---|
| `/admin/legal-sites` | Verwaltung von Impressum, Datenschutz, AGB und weiteren Legal Pages |
| `/admin/cookie-manager` | Cookie-Kategorien, Services, Banner- und Scanner-Konfiguration |
| `/admin/data-requests` | gebündelte Bearbeitung von Auskunfts- und Löschanfragen |

Besonderheit: Frühere Einzelseiten für Privacy- und Deletion-Requests werden heute auf die Sammelroute `/admin/data-requests` zusammengeführt.

---

## Sicherheit

| Route | Zweck |
|---|---|
| `/admin/antispam` | Formular- und Content-Schutz gegen Spam |
| `/admin/firewall` | Blockregeln, IP-Sperren und Anfrageschutz |
| `/admin/security-audit` | Sicherheitsbewertung, Prüfungen und Härtungshinweise |

---

## Zugehörige Fachdokumente

| Dokument | Schwerpunkt |
|---|---|
| [COOKIES.md](COOKIES.md) | Cookie-Manager und öffentliche Einwilligungsseite |
| [DSGVO.md](DSGVO.md) | Auskunfts- und Löschprozesse |
| [LEGAL.md](LEGAL.md) | Rechtstexte und veröffentlichte Pflichtseiten |
| [DELETION-REQUESTS.md](DELETION-REQUESTS.md) | DSGVO Art. 17 – Löschanträge |
| [../security/FIREWALL.md](../security/FIREWALL.md) | Firewall-Regeln und Blocklisten |
| [../security/ANTISPAM.md](../security/ANTISPAM.md) | Anti-Spam-Strategien |
| [../../audit/AUDIT_FACHBEREICHE.md](../../audit/AUDIT_FACHBEREICHE.md) | Konsolidierter Audit-Score und Prüfbereiche |

---

## Audit- und Nachvollziehbarkeit

Mehrere Module in diesem Bereich schreiben sicherheitsrelevante Aktionen inzwischen in das Audit-Log, darunter insbesondere:

- Speichern von Legal-Site-Einstellungen
- Firewall-Regeln anlegen, löschen oder umschalten
- AntiSpam-Blacklist pflegen
- Security-Audits auslösen oder bereinigen
