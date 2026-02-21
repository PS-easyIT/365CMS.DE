# Sicherheits-Center

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026 | **Datei:** `member/security.php`

Das Sicherheits-Center gibt Mitgliedern die Kontrolle über den Schutz ihres Kontos – von aktiven Sessions bis zur Zwei-Faktor-Authentifizierung.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Login-Verlauf](#2-login-verlauf)
3. [Aktive Sessions](#3-aktive-sessions)
4. [Zwei-Faktor-Authentifizierung (2FA)](#4-zwei-faktor-authentifizierung-2fa)
5. [Passwort-Sicherheit](#5-passwort-sicherheit)
6. [Sicherheits-Warnungen](#6-sicherheits-warnungen)
7. [Technische Details](#7-technische-details)

---

## 1. Überblick

URL: `/member/security`

Das Sicherheits-Center zeigt aktuelle Risiken und gibt Empfehlungen für bessere Kontosicherheit.

**Sicherheits-Score** (Fortschrittsbalken):
- 0–40: Gering 🔴 – 2FA deaktiviert, schwaches Passwort
- 41–70: Mittel 🟡 – 2FA deaktiviert aber bekannt
- 71–100: Hoch 🟢 – 2FA aktiv, starkes Passwort, keine unbekannten Sessions

---

## 2. Login-Verlauf

Anzeige der letzten 20 Anmeldungen:

| Spalte | Beschreibung |
|---|---|
| **Datum/Uhrzeit** | Zeitstempel des Logins |
| **IP-Adresse** | IPv4/IPv6 (letzte Stellen maskiert: `192.168.xxx.xxx`) |
| **Browser** | User-Agent vereinfacht (z.B. „Chrome 120 / Windows") |
| **Standort** | Land/Stadt via GeoIP (wenn aktiviert) |
| **Status** | ✅ Erfolgreich / ❌ Fehlgeschlagen |
| **Aktuell** | 📍 Badge für die aktuelle Session |

**Aufbewahrung:** Login-Log 90 Tage, danach automatische Bereinigung.

Ein 🚨-Symbol markiert Logins von bisher unbekanntem Gerät/Browser-Fingerprint.

---

## 3. Aktive Sessions

Alle derzeit angemeldeten Instanzen des Kontos:

- **Anzeige:** Gerät/Browser, letzter Zugriff, IP
- **Eigene Session:** Hervorgehoben, kann nicht beendet werden
- **Session beenden:** Einzeln oder alle anderen mit einem Klick
- Sofortige Invalidierung → Benutzer wird auf `/login` umgeleitet

---

## 4. Zwei-Faktor-Authentifizierung (2FA)

### Einrichtung
1. **„2FA aktivieren"** klicken
2. QR-Code mit Authenticator-App scannen (Google Authenticator, Authy, etc.)
3. 6-stelligen Code aus der App eingeben (Bestätigung)
4. **Backup-Codes** herunterladen und sicher aufbewahren (10 Codes à 8 Stellen)

### Technische Spezifikation
- **Methode:** TOTP (Time-based One-Time Password, RFC 6238)
- **Algorithmus:** SHA-1, 30-Sekunden-Fenster, 6 Stellen
- **Speicherung:** Secrets AES-256-verschlüsselt in `cms_user_meta`

### Backup-Codes
- 10 Einmal-Codes für Notfälle (z.B. Handy verloren)
- Jeder Code nur einmal verwendbar
- Neue Codes generieren invalidiert alle alten sofort

### 2FA deaktivieren
- Passwort + aktuellen 2FA-Code eingeben
- Sicherheitsbenachrichtigung per E-Mail nach Deaktivierung

---

## 5. Passwort-Sicherheit

### Stärke-Indikator

| Stärke | Kriterien |
|---|---|
| ❌ Zu schwach | < 8 Zeichen ODER nur Kleinbuchstaben |
| ⚠️ Schwach | 8–11 Zeichen, 2 Zeichenklassen |
| ✅ Mittel | 12+ Zeichen, 3 Zeichenklassen |
| 💪 Stark | 16+ Zeichen, alle 4 Zeichenklassen |

### Optionaler Passwort-Ablauf (Admin-konfigurierbar)
- Maximale Passwort-Gültigkeit (z.B. 180 Tage)
- Erinnerung 14 Tage vor Ablauf

---

## 6. Sicherheits-Warnungen

Automatische E-Mail-Benachrichtigungen bei:
- Login von **neuem Gerät/Browser**
- **Passwort-Änderung**
- **2FA-Änderung** (Aktivierung/Deaktivierung)
- **Account-Löschungsanfrage**
- Mehr als **5 fehlgeschlagene Loginversuche** (Rate Limiting aktiv)

---

## 7. Technische Details

**Services:** `CMS\Security`, `CMS\Services\MemberService`

```php
// 2FA Secret generieren
$secret = $security->generate2FASecret();

// 2FA-Code verifizieren
$valid = $security->verify2FAToken($userSecret, $userCode);

// Session beenden
$security->invalidateSession($sessionToken);

// Login loggen
$security->logLogin($userId, $ip, $userAgent, $success);
```

**Datenbank:**
- `cms_login_log` – Login-History
- `cms_sessions` – Aktive Sessions (Token, User-Agent, IP, ts_last_activity)
- `cms_user_meta` Key `two_factor_secret` (AES-256 verschlüsselt)
- `cms_user_meta` Key `two_factor_backup_codes` (bcrypt-gehasht)

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
