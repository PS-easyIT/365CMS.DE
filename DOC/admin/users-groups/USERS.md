# Benutzerverwaltung

**Datei:** `admin/users.php`

---

## Übersicht

Die Benutzerverwaltung ist der zentrale Bereich zur Verwaltung aller registrierten Accounts. Sie bietet vollständige CRUD-Operationen, Rollen-Zuweisung, Gruppen-Management, Grid.js-Listenansicht und Bulk-Aktionen.

---

## Statistiken-Übersicht (Stat-Cards)

Oben auf der Listenansicht werden vier KPI-Kacheln angezeigt:

| Kachel | Inhalt |
|--------|--------|
| **Gesamt** | Alle registrierten Benutzer |
| **Aktiv** | Benutzer mit Status `active` |
| **Admins** | Benutzer mit Rolle `admin` |
| **Mitglieder** | Benutzer mit Rolle `member` |

---

## Benutzer-Tabelle

Die Hauptliste wird über **Grid.js** geladen und serverseitig aus der Admin-API befüllt.

### Filter & Suche
- **Grid.js-Suche** über die API-Liste
- **Rollen-Tabs:** Alle, vorhandene Rollen, Gesperrt
- **Bulk-Auswahl** per Checkbox direkt in der Liste

### Tabellenspalten

| Spalte | Beschreibung |
|--------|--------------|
| **Benutzer** | Login-Name mit Avatar-Initials und optionalem Anzeigenamen |
| **E-Mail** | E-Mail-Adresse |
| **Rolle** | Farbiges Rollen-Badge |
| **Gruppen** | Anzahl zugewiesener Gruppen |
| **Status** | Aktiv / Inaktiv Badge |
| **Erstellt** | Registrierungsdatum |
| **Aktionen** | Bearbeiten · Löschen (nicht für den eigenen Account oder geschützte Admins) |

### Aktionen pro Benutzer
- ✏️ **Bearbeiten** – Öffnet die Detail-/Bearbeitungsansicht
- 🔑 **Passwort ändern** – Direkt in der Bearbeitungsansicht, optional leer lassen
- 🗑️ **Löschen** – Permanente Löschung mit zentralem Bestätigungs-Modal

---

## Neuen Benutzer erstellen

**Felder:**
- Benutzername (alphanumerisch, eindeutig)
- E-Mail (valide E-Mail, eindeutig)
- Anzeigename
- Passwort (min. 12 Zeichen inkl. Policy)
- Rolle (Admin, Editor, Moderator, Member)
- Standardstatus: `active`

---

## Benutzer bearbeiten

Die Bearbeitungsansicht ist als eigene Admin-Seite aufgebaut. Passwort-Feld leer lassen = Passwort nicht ändern.

**Zusätzlich sichtbar:**
- Letzter Login-Zeitstempel
- Interne Benutzer-ID
- Gruppen-Mitgliedschaften
- Rolle/Status mit Schutz für Self-Edit

---

## Bulk-Aktionen

Über Checkboxen mehrere Benutzer auswählen und dann:

| Aktion | Beschreibung |
|--------|--------------|
| **Aktivieren** | Status auf `active` setzen |
| **Deaktivieren** | Status auf `inactive` setzen |
| **Sperren** | Status auf `banned` setzen |
| **Löschen** | Permanente Massenlöschung (mit Bestätigung) |
| **Rolle → Admin** | Alle markierten Benutzer werden zu Admins |
| **Rolle → Member** | Alle markierten Benutzer werden zu Membern |

Administrator-Accounts bleiben bei Lösch-Bulk-Aktionen geschützt.

---

## Datenbank-Tabellen

| Tabelle | Inhalt |
|---------|--------|
| `cms_users` | Benutzer-Grunddaten |
| `cms_user_meta` | Erweiterte Metadaten (Avatar, Bio, Social Links) |
| `cms_sessions` | Aktive Sessions |
| `cms_login_attempts` | Login-Versuche (für Rate Limiting) |
| `cms_failed_logins` | Fehlgeschlagene Logins |

---

## Sicherheitshinweise

- Alle Passwörter werden BCrypt-gehasht gespeichert
- Admins können andere Admins nicht löschen (Schutz vor Selbst-Aussperrung)
- Der aktuell angemeldete Benutzer kann sich nicht selbst löschen oder seine eigene Rolle/Status versehentlich entwerten
- Gelöschte Benutzer werden aus allen Sessions entfernt
- CSRF-Token für alle Formulare und AJAX-Anfragen verpflichtend

---

## Verwandte Seiten

- [Gruppen & Berechtigungen](GROUPS.md)
- [RBAC-Verwaltung](RBAC.md)
- [Member-Dashboard Admin](../member/README.md)
