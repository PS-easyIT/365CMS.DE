# Benutzerverwaltung

**Datei:** `admin/users.php`

---

## Übersicht

Die Benutzerverwaltung ist der zentrale Bereich zur Verwaltung aller registrierten Accounts. Sie bietet vollständige CRUD-Operationen, Rollen-Zuweisung, Gruppen-Management und Bulk-Aktionen.

---

## Statistiken-Übersicht (Stat-Cards)

Oben auf der Seite werden vier KPI-Kacheln angezeigt:

| Kachel | Inhalt |
|--------|--------|
| **Gesamt** | Alle registrierten Benutzer |
| **Aktiv** | Benutzer mit Status `active` |
| **Admins** | Benutzer mit Rolle `admin` |
| **Mitglieder** | Benutzer mit Rolle `member` |

---

## Benutzer-Tabelle

### Filter & Suche
- **Volltextsuche** über Name, E-Mail, Benutzername
- **Rollen-Filter:** Alle, Admin, Editor, Member, Moderator
- **Status-Filter:** Aktiv / Inaktiv

### Tabellenspalten

| Spalte | Beschreibung |
|--------|--------------|
| **#** | Benutzer-ID |
| **Name** | Vor- und Nachname mit Avatar-Initials |
| **Benutzername** | Login-Name (`@username`) |
| **E-Mail** | E-Mail-Adresse |
| **Rolle** | Farbiges Rollen-Badge |
| **Gruppe** | Zugewiesene Benutzergruppe |
| **Status** | Aktiv / Inaktiv Badge |
| **Erstellt** | Registrierungsdatum |
| **Aktionen** | Bearbeiten · Passwort · Löschen |

### Aktionen pro Benutzer
- ✏️ **Bearbeiten** – Öffnet Bearbeitungs-Modal mit allen Feldern
- 🔑 **Passwort zurücksetzen** – Generiert temporäres Passwort
- 🗑️ **Löschen** – Permanente Löschung mit Bestätigungs-Modal

---

## Neuen Benutzer erstellen

**Felder:**
- Benutzername (alphanumerisch, eindeutig)
- E-Mail (valide E-Mail, eindeutig)
- Vorname / Nachname
- Passwort (min. 8 Zeichen)
- Rolle (Admin, Editor, Moderator, Member)
- Gruppe (optional)
- Status (Aktiv/Inaktiv)

---

## Benutzer bearbeiten

Alle Felder des Erstellungs-Formulars sind bearbeitbar. Passwort-Feld leer lassen = Passwort nicht ändern.

**Zusätzlich sichtbar:**
- Letzter Login-Zeitstempel
- Anzahl aktiver Sessions
- Zugewiesenes Abo-Paket

---

## Bulk-Aktionen

Über Checkboxen mehrere Benutzer auswählen und dann:

| Aktion | Beschreibung |
|--------|--------------|
| **Aktivieren** | Status auf `active` setzen |
| **Deaktivieren** | Status auf `inactive` setzen |
| **Löschen** | Permanente Massenl­öschung (mit Bestätigung) |
| **Rolle zuweisen** | Alle markierten Benutzer erhalten dieselbe Rolle |
| **Gruppe zuweisen** | Alle markierten Benutzer einer Gruppe zuweisen |

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
- Gelöschte Benutzer werden aus allen Sessions entfernt
- CSRF-Token für alle Formulare und AJAX-Anfragen verpflichtend

---

## Verwandte Seiten

- [Gruppen & Berechtigungen](GROUPS.md)
- [RBAC-Verwaltung](RBAC.md)
- [Member-Dashboard Admin](../member/README.md)
