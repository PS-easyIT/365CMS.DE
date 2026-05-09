# 365CMS – Sicherheit

Kurzbeschreibung: Überblick über die aktuellen Sicherheitswerkzeuge im Admin-Bereich mit AntiSpam, Firewall und Security-Audit.

Letzte Aktualisierung: 2026-05-09 · Version 2.9.626

Der Bereich bündelt die anwendungsnahen Schutzmechanismen des aktuellen 365CMS-Kerns. Maßgeblich ist die Sidebar-Gruppe **Sicherheit** aus `CMS/admin/partials/sidebar.php`.

Diese Übersicht beschreibt die 2.9.626-Adminoberfläche; für tiefe Verträge rund um CSRF, Session-Härtung, Auth und Sanitizing sind zusätzlich die Core-Dokumente unter `DOC/core/` führend.

## Aktuelle Routen

| Route | Zweck |
|---|---|
| `/admin/antispam` | Spam-Schutz für Formulare, Kommentare und Eingaben |
| `/admin/firewall` | IP-Sperren, Regeln und anwendungsnahe Blockmechanismen |
| `/admin/security-audit` | Sicherheitsprüfungen, Score-Karten und Audit-Log |

## Zusammenspiel der Module

- **AntiSpam** schützt Kommentare und aktive Kontaktformulare über denselben zentralen Runtime-Service.
- **Firewall** reagiert auf missbräuchliche Muster, Sperren und Regelwerke.
- **Security-Audit** bewertet den Betriebszustand mit technischen Prüfungen und zeigt sicherheitsrelevante Log-Ereignisse an – inklusive zentraler AntiSpam-Verdrahtung für aktive Kontaktformulare.

Mehrere Admin-Aktionen werden zusätzlich über das zentrale `audit_log` nachvollziehbar gemacht.

## Verwandte Dokumente

- [ANTISPAM.md](ANTISPAM.md)
- [FIREWALL.md](FIREWALL.md)
- [../../audit/AUDIT_FACHBEREICHE.md](../../audit/AUDIT_FACHBEREICHE.md)
- [../legal/README.md](../legal/README.md)