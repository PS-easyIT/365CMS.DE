# 365CMS – Sicherheit

Kurzbeschreibung: Überblick über die aktuellen Sicherheitswerkzeuge im Admin-Bereich mit AntiSpam, Firewall und Security-Audit.

Letzte Aktualisierung: 17.05.2026 · Version 3.0.11

Der Bereich bündelt die anwendungsnahen Schutzmechanismen des aktuellen 365CMS-Kerns. Maßgeblich ist die Sidebar-Gruppe **Sicherheit** aus `CMS/admin/partials/sidebar.php`.

Diese Übersicht beschreibt die aktuelle 3.0.11-Adminoberfläche; für tiefe Verträge rund um CSRF, Session-Härtung, Auth und Sanitizing sind zusätzlich die Core-Dokumente unter `DOC/core/` führend.

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
- **Security-Alarmierung** nutzt die bestehende Monitoring-Mail-Pipeline aus `/admin/monitor-email-alerts`, verdichtet Login-Fehlversuche, AntiSpam-Rejections und Firewall-Blocks über ein konfigurierbares Zeitfenster und löst Mails ausschließlich read-only über den stündlichen Core-Cron aus.
- **Firewall-Härtungsprofile** zeigen eine read-only Diff-Ansicht für Entwicklung, Staging und Produktion und erlauben die optionale Anwendung nur per CSRF-geschütztem POST.
- **UI-Vertrag 17.05.2026:** Alle drei Seiten folgen sichtbar `Header → Toolbar → Inhalt` mit klar getrennten Aktionszonen, persistenter fachlicher Hinweisbox und konsistenten Tabellen-/Kartenabständen.
- **Globaler UI-Hard-Standard 17.05.2026:** Buttons sowie Karten-/Boxcontainer sind adminweit auf maximal 2px Radius begrenzt; verschachtelte Panels heben sich über leicht abgesetzte Hintergründe klar von der umgebenden Hauptbox ab.

Mehrere Admin-Aktionen werden zusätzlich über das zentrale `audit_log` nachvollziehbar gemacht.

## Verwandte Dokumente

- [ANTISPAM.md](ANTISPAM.md)
- [FIREWALL.md](FIREWALL.md)
- [../../audit/AUDIT_FACHBEREICHE.md](../../audit/AUDIT_FACHBEREICHE.md)
- [../legal/README.md](../legal/README.md)