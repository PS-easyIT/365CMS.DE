# AntiSpam-Einstellungen

**Datei:** `admin/antispam.php`

---

## Übersicht

Das AntiSpam-System schützt Formulare, Kommentare und Registrierungen vor automatisierten Bot-Einreichungen und Spam-Inhalten.

---

## Schutzmaßnahmen

### Honeypot-Felder
Unsichtbare Formularfelder die von Menschen leer gelassen, von Bots jedoch ausgefüllt werden. Bei Ausfüllung wird die Einreichung als Spam markiert.

Konfiguration:
- **Feldname** – zufällig generierter Name (Standard: automatisch)
- **CSS-Klasse** – `cms-hp-field` (im Frontend unsichtbar)

### Time-Based Validation
Formulare die zu schnell ausgefüllt werden, stammen wahrscheinlich von Bots:
- **Mindest-Ausfüllzeit:** Standard 3 Sekunden
- **Maximum:** Optional (verhindert sehr alte Submissions)

### CAPTCHA-Integration

| Typ | Beschreibung |
|-----|--------------|
| **Mathematisches CAPTCHA** | Einfache Rechenaufgabe (kein externer Dienst) |
| **hCaptcha** | Datenschutzfreundlich, API-Key erforderlich |
| **Cloudflare Turnstile** | Unsichtbares CAPTCHA, API-Key erforderlich |

### Keyword-Filter
Definierbare Liste verbotener Wörter in:
- Formular-Einreichungen
- Nachrichten (Member-Bereich)
- Kommentaren

Optionen:
- **Blockieren** – Einreichung ablehnen
- **Moderieren** – Einreichung zur Prüfung einreihen
- **Markieren** – Einreichung als verdächtig kennzeichnen

---

## AntiSpam-Einstellungen

| Einstellung | Standard | Beschreibung |
|-------------|---------|--------------|
| AntiSpam aktiv | ✅ Ein | Globaler An/Aus-Schalter |
| Honeypot | ✅ Ein | Honeypot-Felder in Formulare einfügen |
| Zeitvalidierung | ✅ Ein | Zeitbasierte Spam-Erkennung |
| Min. Ausfüllzeit | 3s | Minimum-Sekunden für valide Einreichung |
| Keyword-Filter | ✅ Ein | Verbotene-Wörter-Filter aktiv |
| IP-Reputation | ⚙️ Optional | Aufrufen externer IP-Reputation-Dienste |
| Protokollierung | ✅ Ein | Spam-Einreichungen loggen |

---

## Geschützte Bereiche

- **Registrierungs-Formular** – Neue Accounts
- **Login-Formular** – Kombiniert mit Rate Limiting
- **Kontaktformular** – Plugin-abhängig
- **Member-Nachrichten** – Private Messaging
- **Kommentare** – Falls aktiviert
- **Alle Plugins-Formulare** – Via Hook-System

---

## Spam-Log

Das Spam-Log zeigt abgelehnte Einreichungen mit:
- Zeitstempel
- IP-Adresse
- Formular-Typ (Register, Contact, etc.)
- Auslöse-Regel (Honeypot, Keyword, etc.)
- Eingabe-Inhalt (anonymisiert nach 7 Tagen)

---

## Verwandte Seiten

- [Firewall & IP-Blocking](FIREWALL.md)
- [Security-Audit](SECURITY-AUDIT.md)
