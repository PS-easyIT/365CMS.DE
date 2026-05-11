# 365CMS – Firewall

Kurzbeschreibung: Schutz vor missbräuchlichen Anfragen, IP-Sperren, Blockregeln, Simulationsläufen und sicherheitsrelevanten Zugriffsmustern.

Letzte Aktualisierung: 2026-05-11 · Version 2.9.761

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
- neue Blockregeln zunächst nur simulieren
- Treffer read-only über ein konfigurierbares Vorschaufenster auswerten
- Regeln anschließend explizit scharfschalten
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
| `security_log` (`action = simulated`) | read-only Treffervorschau für Simulationsregeln |
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

## Simulationsmodus seit 2.9.761

Blockregeln können jetzt in zwei Modi laufen:

| Modus | Wirkung |
|---|---|
| `Simulation` | Treffer werden als `simulated` im `security_log` protokolliert, Requests laufen aber weiter |
| `Scharf` | Treffer werden aktiv blockiert |

Wichtige Details:

- Neue Blockregeln können direkt im Simulationsmodus angelegt werden.
- `allow_ip`-Regeln bleiben immer sofort wirksam und unterstützen bewusst keinen Simulationsmodus.
- Die Admin-Oberfläche zeigt eine read-only Treffervorschau für die letzten konfigurierten Stunden.
- Das Scharfschalten erfolgt ausschließlich per CSRF-geschütztem POST im Admin – nicht per GET und nicht über Token in URLs.
- Simulierte Treffer werden unabhängig vom allgemeinen Zugriffs-Logging geschrieben, damit die Vorschau belastbar bleibt.
- Das bestehende Rate-Limit bleibt auch bei simulierten Regeln aktiv und unverändert wirksam.

---

## Typische Admin-Aktionen

- Firewall-Einstellungen speichern
- Regel anlegen
- Regel löschen
- Regel aktivieren oder deaktivieren
- Regel zwischen `Simulation` und `Scharf` umstellen
- gesperrte IPs prüfen

Diese Aktionen werden im aktuellen Arbeitsstand zusätzlich über den `AuditLogger` protokolliert.

---

## Sicherheit

- Zugriff nur für Administratoren
- CSRF-Prüfung für alle POST-Aktionen
- serverseitige Validierung von IPs und Regelparametern
- Runtime-Enforcement über `SecurityRuntimeService`
- Rate-Limit-Ereignisse und Blockierungen in `security_log`
- simulierte Treffer als eigener, read-only Logpfad in `security_log`
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
