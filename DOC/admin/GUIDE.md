# 365CMS – Admin-Handbuch

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026

Dieses Handbuch erklärt die häufigsten Admin-Aufgaben Schritt für Schritt.

---

## Erster Login

1. Browser öffnen: `https://eure-domain.de/admin`
2. Benutzername und Passwort eingeben
3. Auf "Anmelden" klicken
4. Ihr seht das **Dashboard** mit der Systemübersicht

---

## Neuen Benutzer anlegen

1. Admin → **Benutzer** (`/admin/users.php`)
2. Klick auf **"Neuer Benutzer"**
3. Pflichtfelder ausfüllen:
   - **Benutzername** (nur Buchstaben, Zahlen, Unterstriche)
   - **E-Mail** (muss eindeutig sein)
   - **Passwort** (min. 8 Zeichen, besser 12+)
   - **Rolle** wählen: `member` für normale Nutzer, `admin` nur für Admins
4. Auf **"Speichern"** klicken

---

## Passwort zurücksetzen

1. Admin → Benutzer → Benutzer suchen
2. Benutzer anklicken → **"Bearbeiten"**
3. Neues Passwort eingeben
4. **"Speichern"**

---

## Neue Seite erstellen

1. Admin → **Seiten** (`/admin/pages.php`)
2. **"Neue Seite"** klicken
3. **Titel** eingeben (z.B. "Über uns")
4. **Slug** wird automatisch generiert (z.B. `ueber-uns`) → URL wird `/ueber-uns`
5. **Inhalt** mit SunEditor bearbeiten
6. **Status:** `published` um die Seite zu veröffentlichen
7. **"Speichern"**

**Tipp:** Mit `hide_title` kann der Seitentitel auf der Website ausgeblendet werden (nützlich für die Startseite).

---

## Theme wechseln

1. Admin → **Themes** (`/admin/themes.php`)
2. Gewünschtes Theme finden
3. Auf **"Aktivieren"** klicken
4. Website im Browser neu laden – Theme ist sofort aktiv!

---

## Farben anpassen (ohne Code)

1. Admin → **Theme-Customizer** (`/admin/theme-customizer.php`)
2. Kategorie **"Farben"** öffnen
3. Primärfarbe (z.B. `#ff6600`) eintragen
4. **"Speichern"** – Änderung sofort sichtbar

---

## Plugin aktivieren

1. Admin → **Plugins** (`/admin/plugins.php`)
2. Gewünschtes Plugin finden
3. **"Aktivieren"** klicken
4. Plugin steht sofort zur Verfügung

---

## Backup erstellen

1. Admin → **Backup** (`/admin/backup.php`)
2. **"Datenbank-Backup erstellen"** klicken
3. Download startet automatisch (`.sql.gz`-Datei)
4. Datei sicher aufbewahren!

**Empfehlung:** Täglich automatisch Backup erstellen – Einstellung unter "Automatisches Backup".

---

## Performance-Cache leeren

Wenn nach Änderungen die Website nicht aktualisiert erscheint:

1. Admin → **Performance** (`/admin/performance.php`)
2. **"Cache leeren"** klicken
3. Seite neu laden

---

## Abo-Plan anlegen

1. Admin → **Abos** (`/admin/subscriptions.php`)
2. **"Neuer Plan"** klicken
3. Konfigurieren:
   - **Name** (z.B. "Pro")
   - **Preis** monatlich/jährlich
   - **Limits** (Anzahl Einträge pro Plugin)
   - **Features** freischalten (Analytics, API, etc.)
4. **"Speichern"**

---

## System-Status prüfen

Admin → **System** (`/admin/system.php`)

Hier seht ihr:
- 🟢 = Alles in Ordnung
- 🟡 = Warnung (nicht kritisch)
- 🔴 = Problem (sollte behoben werden)

**Häufige Probleme:**
| Problem | Lösung |
|---------|--------|
| PHP-Extension fehlt | Hosting-Provider kontaktieren |
| `cache/` nicht schreibbar | `chmod 777 cache/` (oder Webserver-Besitzer setzen) |
| Datenbank-Verbindung langsam | Zu viele Abfragen? Performance-Seite prüfen |

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
