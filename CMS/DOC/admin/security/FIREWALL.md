# 365CMS – Firewall

Kurzbeschreibung: Schutz vor missbräuchlichen Anfragen, IP-Sperren, Blockregeln und sicherheitsrelevanten Zugriffsmustern.

Letzte Aktualisierung: 2026-05-02 · Version 2.9.248

**Admin-Route:** `/admin/firewall`

---

## Überblick

Die Firewall ist das zentrale Admin-Modul für anwendungsnahe Abwehrmaßnahmen. Sie arbeitet zusammen mit Login-Schutz, AntiSpam und Security-Audit.

| Baustein | Datei |
|---|---|
| Entry Point | `CMS/admin/firewall.php` |
| Modul | `CMS/admin/modules/security/FirewallModule.php` |
| Runtime | `CMS/core/Services/SecurityRuntimeService.php` |
| View | `CMS/admin/views/security/firewall.php` |

Typische Aufgaben:

- IP-Adressen oder Bereiche blockieren
- regelbasierte Filter pflegen
- Sperren aktivieren oder aufheben
- sicherheitsrelevante Änderungen protokollieren

Seit 2.9.248 werden aktive Firewall-Regeln nicht nur verwaltet, sondern im Core-Runtime-Pfad serverseitig ausgewertet. Blockentscheidungen laufen damit unabhängig von der Admin-Oberfläche.

---

## Datenquellen

| Bereich | Zweck |
|---|---|
| `blocked_ips` | persistente oder temporäre Sperren |
| `failed_logins` | Fehlanmeldungen |
| `login_attempts` | Rate-Limiting und Login-Muster |
| `firewall_rules` | benutzerdefinierte Firewall-Regeln |
| `audit_log` | Nachvollziehbarkeit von Admin-Aktionen |

---

## Regelarten

Je nach Konfiguration können Regeln unter anderem auf Folgendes zielen:

- einzelne IP-Adresse
- CIDR-Bereich
- Länderkennung
- User-Agent-Muster
- temporäre Rate-Limit-Sperren

Die aktuelle Modul-Implementierung prüft Eingaben strenger als ältere Stände, insbesondere bei IP-Bereichen, Länderkennungen und benutzerdefinierten Regeln.

---

## Typische Admin-Aktionen

- Firewall-Einstellungen speichern
- Regel anlegen
- Regel löschen
- Regel aktivieren oder deaktivieren
- gesperrte IPs prüfen

Diese Aktionen werden im aktuellen Arbeitsstand zusätzlich über den `AuditLogger` protokolliert.

---

## Sicherheit

- Zugriff nur für Administratoren
- CSRF-Prüfung für alle POST-Aktionen
- serverseitige Validierung von IPs und Regelparametern
- Runtime-Enforcement über `SecurityRuntimeService`
- Rate-Limit-Ereignisse und Blockierungen in `security_log`
- Allow-Regeln überspringen Blockregeln, aber nicht mehr das Rate-Limit
- Redirect nach jeder schreibenden Aktion

## Aktuell noch offen

- `security_log` dient weiterhin als Rate-Limit-Zähler. Für sehr stark frequentierte Installationen wäre eine aggregierte Zählertabelle ressourcenschonender.

---

## Verwandte Dokumente

- [ANTISPAM.md](ANTISPAM.md)
- [../../audit/AUDIT_FACHBEREICHE.md](../../audit/AUDIT_FACHBEREICHE.md)
- [../legal/README.md](../legal/README.md)
- [../../core/SECURITY.md](../../core/SECURITY.md)
