# 365CMS – Manueller Test: Authentifizierung & Benutzerverwaltung

> **Scope:** Login-Flow, MFA, Session-Management, Passwort-Policy, Registrierung  
> **Umgebung:** Staging / lokale Test-Instanz  
> **Schweregrade:** 🔴 Kritisch | 🟠 Hoch | 🟡 Mittel | 🟢 Niedrig  
> **Stand:** 2026

---

## 1. Vorbereitung

| Aufgabe | Erledigt |
|---|---|
| Test-Umgebung gestartet (Webserver + MySQL) | ☐ |
| `CMS_DEBUG=true` in `config.php` gesetzt | ☐ |
| Mindestens 1 Admin-Account und 1 normaler User-Account angelegt | ☐ |
| Browser-DevTools geöffnet (Network + Application > Storage) | ☐ |
| Testprotokoll-Datei angelegt | ☐ |

---

## 2. Login-Flow

### TC-AUTH-01 · Normaler Login (gültige Zugangsdaten)
**Vorgehen:**
1. `SITE_URL/` aufrufen → Weiterleitung auf Login-Seite prüfen
2. Korrekte E-Mail + Passwort eingeben
3. „Anmelden" klicken

**Erwartetes Ergebnis:**
- Weiterleitung auf `/member/dashboard`
- Session-Cookie (`PHPSESSID`) ist gesetzt, `HttpOnly` und `SameSite=Strict`
- Kein Passwort in URL oder Response-Header sichtbar

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Weiterleitung korrekt | ☐ | ☐ |
| Session-Cookie flags korrekt | ☐ | ☐ |

---

### TC-AUTH-02 · Login mit falschen Zugangsdaten
**Vorgehen:**
1. Falsche E-Mail oder falsches Passwort eingeben
2. „Anmelden" klicken

**Erwartetes Ergebnis:**
- Fehlermeldung wird angezeigt (generisch: „Zugangsdaten ungültig")
- **Kein** Hinweis, ob E-Mail oder Passwort falsch ist
- HTTP 200 (kein 500)
- Keine Session wird erstellt

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Generische Fehlermeldung | ☐ | ☐ |
| Keine Session gesetzt | ☐ | ☐ |

---

### TC-AUTH-03 🔴 · Brute-Force-Schutz (Rate-Limiting)
**Vorgehen:**
1. 6× falsche Zugangsdaten innerhalb von 5 Minuten eingeben

**Erwartetes Ergebnis:**
- Nach dem 5. Fehlversuch: Account temporär gesperrt oder Wartezeit erzwungen
- HTTP 429 oder entsprechende Fehlermeldung
- Rate-Limiter-Eintrag in `php://error_log` oder Audit-Log sichtbar

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Sperre nach 5 Versuchen | ☐ | ☐ |
| Audit-Log-Eintrag vorhanden | ☐ | ☐ |

---

### TC-AUTH-04 · Login mit leerem Formular
**Vorgehen:**
1. Leeres Formular absenden (beide Felder leer)

**Erwartetes Ergebnis:**
- HTML5-Validierung verhindert Absenden ODER Server liefert Fehlermeldung
- Kein 500-Fehler

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Kein 500-Fehler | ☐ | ☐ |

---

## 3. Passwort-Policy

### TC-AUTH-05 · Schwaches Passwort bei Registrierung ablehnen
**Vorgehen:**  
Passwörter mit folgenden Defiziten bei der Registrierung eingeben:

| Test | Passwort | Erwartetes Ergebnis |
|---|---|---|
| Zu kurz | `Aa1!Short` | Fehler: min. 12 Zeichen |
| Kein Großbuchstabe | `secure@passw0rd` | Fehler: Großbuchstabe fehlt |
| Keine Ziffer | `Secure@Password` | Fehler: Ziffer fehlt |
| Kein Sonderzeichen | `SecurePassw0rd` | Fehler: Sonderzeichen fehlt |

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Alle 4 Fälle korrekt abgelehnt | ☐ | ☐ |

---

### TC-AUTH-06 · Starkes Passwort akzeptieren
**Vorgehen:**
1. `Secure@Passw0rd2026!` als Passwort bei Registrierung eingeben

**Erwartetes Ergebnis:**
- Registrierung erfolgreich
- Passwort in DB als bcrypt-Hash gespeichert (nicht im Klartext)

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Passwort akzeptiert | ☐ | ☐ |
| DB-Eintrag ist Hash (beginnt mit `$2y$`) | ☐ | ☐ |

---

## 4. MFA-Setup & Verifikation

### TC-AUTH-07 · MFA aktivieren
**Vorgehen:**
1. Als eingeloggter User zu Profil / Sicherheitseinstellungen navigieren
2. „MFA aktivieren" wählen
3. QR-Code mit Authenticator-App (z. B. Google Authenticator) scannen
4. Einmalcode eingeben und bestätigen

**Erwartetes Ergebnis:**
- MFA ist aktiviert (Bestätigung in UI)
- `mfa_secret` ist in DB gespeichert (verschlüsselt oder gehasht)
- Nächster Login verlangt TOTP-Code

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| QR-Code korrekt generiert | ☐ | ☐ |
| Setup mit gültigem Code bestätigt | ☐ | ☐ |
| Nächster Login fordert MFA | ☐ | ☐ |

---

### TC-AUTH-08 · MFA-Login mit gültigem Code
**Vorgehen:**
1. Login mit korrekten Zugangsdaten → MFA-Eingabe erscheint
2. Aktuellen TOTP-Code aus Authenticator-App eingeben

**Erwartetes Ergebnis:**
- Zugang gewährt, Weiterleitung auf Dashboard

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Login erfolgreich | ☐ | ☐ |

---

### TC-AUTH-09 · MFA-Login mit ungültigem Code
**Vorgehen:**
1. MFA-Eingabe: `000000` (oder anderer ungültiger Code)

**Erwartetes Ergebnis:**
- Fehlermeldung „Ungültiger Code"
- Session wird nicht erstellt
- Audit-Log-Eintrag (fehlgeschlagener MFA-Versuch)

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Zugang verweigert | ☐ | ☐ |

---

### TC-AUTH-10 · MFA deaktivieren
**Vorgehen:**
1. Eingeloggt als User mit aktiver MFA
2. „MFA deaktivieren" wählen

**Erwartetes Ergebnis:**
- MFA deaktiviert
- Nächster Login erfordert keinen TOTP-Code mehr

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| MFA deaktiviert | ☐ | ☐ |

---

## 5. Session-Management

### TC-AUTH-11 · Session-Ablauf
**Vorgehen:**
1. Als User einloggen
2. In `config.php` `SESSION_LIFETIME` auf 1 Minute setzen
3. 2 Minuten inaktiv warten
4. Seite aufrufen

**Erwartetes Ergebnis:**
- Weiterleitung auf Login-Seite
- Session-Cookie gelöscht oder ungültig

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Automatischer Logout | ☐ | ☐ |

---

### TC-AUTH-12 · Manueller Logout
**Vorgehen:**
1. Eingeloggten User über „Abmelden" abmelden
2. Browser-Zurück-Button drücken

**Erwartetes Ergebnis:**
- Session-Cookie gelöscht
- Nach Back-Button: Weiterleitung auf Login, kein Zugriff auf gecachten Member-Inhalt
- Cache-Control-Header: `no-store, no-cache`

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Cookie gelöscht | ☐ | ☐ |
| Kein Back-Button-Zugriff | ☐ | ☐ |

---

## 6. Registrierung

### TC-AUTH-13 · Vollständige Registrierung
**Vorgehen:**
1. Registrierungsformular mit gültigen Daten ausfüllen
2. Absenden

**Erwartetes Ergebnis:**
- User-Account in DB angelegt
- Passwort als bcrypt-Hash gespeichert
- User ist noch nicht eingeloggt (keine auto-login ohne E-Mail-Bestätigung, falls aktiv)

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Account angelegt | ☐ | ☐ |
| Kein Klartext-Passwort in DB | ☐ | ☐ |

---

### TC-AUTH-14 · Doppelte Registrierung (gleiche E-Mail)
**Vorgehen:**
1. Registrierung mit einer bereits vorhandenen E-Mail ausführen

**Erwartetes Ergebnis:**
- Fehlermeldung: „E-Mail bereits registriert"
- Kein doppelter Eintrag in DB

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Fehlermeldung korrekt | ☐ | ☐ |

---

## 7. Testprotokoll

| Datum | Tester | Umgebung | Ergebnis |
|---|---|---|---|
| | | | |

**Offene Punkte:**

<!-- Hier gefundene Probleme dokumentieren -->
