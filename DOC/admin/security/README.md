# 365CMS – Sicherheit

Kurzbeschreibung: Überblick über die aktuellen Sicherheitswerkzeuge im Admin-Bereich mit AntiSpam, Firewall und Security-Audit.

Letzte Aktualisierung: 2026-03-07 · Version 2.3.1

Der Bereich bündelt die anwendungsnahen Schutzmechanismen des aktuellen 365CMS-Kerns. Maßgeblich ist die Sidebar-Gruppe **Sicherheit** aus `CMS/admin/partials/sidebar.php`.

## Aktuelle Routen

| Route | Zweck |
|---|---|
| `/admin/antispam` | Spam-Schutz für Formulare, Kommentare und Eingaben |
| `/admin/firewall` | IP-Sperren, Regeln und anwendungsnahe Blockmechanismen |
| `/admin/security-audit` | Sicherheitsprüfungen, Score-Karten und Audit-Log |

## Zusammenspiel der Module

- **AntiSpam** schützt redaktionelle und öffentliche Eingabeflüsse.
- **Firewall** reagiert auf missbräuchliche Muster, Sperren und Regelwerke.
- **Security-Audit** bewertet den Betriebszustand mit technischen Prüfungen und zeigt sicherheitsrelevante Log-Ereignisse an.

Mehrere Admin-Aktionen werden zusätzlich über das zentrale `audit_log` nachvollziehbar gemacht.

## Verwandte Dokumente

- [ANTISPAM.md](ANTISPAM.md)
- [FIREWALL.md](FIREWALL.md)
- [SECURITY-AUDIT.md](SECURITY-AUDIT.md)
- [../legal/README.md](../legal/README.md)