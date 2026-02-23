# 365CMS – Manueller Test: Sicherheits-Headers & Schutzmaßnahmen

> **Scope:** HTTP-Security-Headers, CSRF, CSP, HSTS, Rate-Limiting, Input-Sanitierung  
> **Werkzeuge:** Browser-DevTools, curl, OWASP ZAP (optional)  
> **Schweregrade:** 🔴 Kritisch | 🟠 Hoch | 🟡 Mittel | 🟢 Niedrig  
> **Stand:** 2026

---

## 1. HTTP-Security-Headers

### TC-SEC-01 🔴 · Content-Security-Policy (CSP)

**Prüfung via curl:**
```bash
curl -I https://your-test-instance/
```

**Erwartete Header:**
```
Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; ...
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
```

| Header | Vorhanden | Wert korrekt |
|---|---|---|
| `Content-Security-Policy` | ☐ | ☐ |
| `X-Frame-Options: DENY` | ☐ | ☐ |
| `X-Content-Type-Options: nosniff` | ☐ | ☐ |
| `Referrer-Policy` | ☐ | ☐ |

---

### TC-SEC-02 🟠 · HSTS (HTTP Strict Transport Security)

**Prüfung:**
```bash
curl -I https://your-test-instance/
```

**Erwartet:**
```
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| HSTS-Header vorhanden | ☐ | ☐ |
| `max-age` ≥ 31536000 | ☐ | ☐ |

---

### TC-SEC-03 🟠 · Server-Fingerprinting unterdrückt

**Prüfung:**
```bash
curl -I https://your-test-instance/
```

**Erwartet:**
- `X-Powered-By` Header **nicht** vorhanden
- `Server` Header enthält keine PHP-Version

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Kein `X-Powered-By` Header | ☐ | ☐ |
| Keine PHP-Version in `Server` | ☐ | ☐ |

---

## 2. CSRF-Schutz

### TC-SEC-04 🔴 · CSRF-Token in Formularen

**Vorgehen:**
1. Jedes HTML-Formular im Browser-Quelltext prüfen
2. Nach `csrf_token` (hidden input) suchen

**Erwartet:**
- Jedes POST-Formular enthält `<input type="hidden" name="csrf_token" value="...">`
- Token-Wert ist nicht leer und mindestens 64 Zeichen lang

| Formular | Token vorhanden | Token ≥ 64 Zeichen |
|---|---|---|
| Login | ☐ | ☐ |
| Registrierung | ☐ | ☐ |
| Admin-Settings | ☐ | ☐ |
| Profil-Update | ☐ | ☐ |

---

### TC-SEC-05 🔴 · CSRF-Token-Verifikation serverseitig

**Vorgehen (mit curl oder Burp Suite):**
1. Gültiges Formular absenden, Token notieren
2. POST-Request mit manipuliertem Token (`csrf_token=invalid123`) wiederholen

**Erwartet:**
- HTTP 403 oder Fehlermeldung „Sicherheitscheck fehlgeschlagen"
- Aktion wird **nicht** ausgeführt

```bash
curl -X POST https://your-test-instance/admin/settings.php \
  -d "action_name=save_settings&csrf_token=MANIPULATED" \
  -b "PHPSESSID=valid_session_id"
```

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Manipulierter Token abgelehnt | ☐ | ☐ |
| Aktion nicht ausgeführt | ☐ | ☐ |

---

### TC-SEC-06 🟠 · Cross-Site Request Forgery Simulation

**Vorgehen:**
1. Eigene Test-HTML-Seite erstellen mit Auto-Submit-Formular:
```html
<form action="https://your-test-instance/admin/settings.php" method="POST">
  <input name="action_name" value="delete_user">
  <input name="user_id" value="1">
</form>
<script>document.forms[0].submit();</script>
```
2. Im Browser mit aktiver Admin-Session zur Test-Seite navigieren

**Erwartet:**
- Request schlägt fehl (kein CSRF-Token → 403)
- Admin-Account bleibt unverändert

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| CSRF-Angriff blockiert | ☐ | ☐ |

---

## 3. XSS-Schutz

### TC-SEC-07 🔴 · Reflected XSS in URL-Parametern

**Vorgehen:**
```
https://your-test-instance/?q=<script>alert('XSS')</script>
https://your-test-instance/?page=<img src=x onerror=alert(1)>
```

**Erwartet:**
- Kein Alert-Dialog erscheint
- Eingabe im HTML escaped ausgegeben: `&lt;script&gt;` etc.

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Kein Script ausgeführt | ☐ | ☐ |
| Korrekt escaped | ☐ | ☐ |

---

### TC-SEC-08 🔴 · Stored XSS in Profil-Feldern

**Vorgehen:**
1. Im Profil-Bearbeitungsfeld `<script>alert('stored')</script>` eingeben und speichern
2. Profil aufrufen

**Erwartet:**
- Script wird nicht ausgeführt
- Wert ist escaped dargestellt

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Kein Script ausgeführt | ☐ | ☐ |

---

## 4. SQL-Injection

### TC-SEC-09 🔴 · SQL-Injection in Login-Formular

**Vorgehen:**  
Folgende Werte als Username eingeben (Passwort: beliebig):

| Payload | Erwartetes Ergebnis |
|---|---|
| `' OR 1=1 --` | Login schlägt fehl |
| `admin'--` | Login schlägt fehl |
| `'; DROP TABLE users; --` | Fehlermeldung, keine DB-Änderung |

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Alle Payloads abgewiesen | ☐ | ☐ |
| Kein unbefugter Zugriff | ☐ | ☐ |
| Kein DB-Error in Response | ☐ | ☐ |

---

### TC-SEC-10 🟠 · SQL-Injection in Suchfeldern

**Vorgehen:**
```
?search=' UNION SELECT table_name,2,3 FROM information_schema.tables--
```

**Erwartet:**
- Kein DB-Inhalt in Response sichtbar
- Keine DB-Fehlermeldung

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Kein Daten-Leak | ☐ | ☐ |

---

## 5. Rate-Limiting

### TC-SEC-11 🟠 · API-Rate-Limit

**Vorgehen:**
```bash
# 35 Requests innerhalb von 60 Sekunden
for i in $(seq 1 35); do
  curl -s -o /dev/null -w "%{http_code}\n" https://your-test-instance/api/...
done
```

**Erwartet:**
- Ab dem 31. Request HTTP 429 oder Fehlermeldung
- Response: `{"success": false, "error": "Rate limit exceeded"}`

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| HTTP 429 bei Überschreitung | ☐ | ☐ |

---

## 6. Datei-Upload-Sicherheit

### TC-SEC-12 🔴 · PHP-Datei als Upload-Bypass

**Vorgehen:**
1. Datei `shell.php` mit Inhalt `<?php system($_GET['cmd']); ?>` hochladen
2. Verschiedene MIME-Type-Spoofing-Versuche:
   - `shell.php.jpg`
   - `shell.phtml`
   - `shell.php%00.jpg`

**Erwartet:**
- Upload abgelehnt mit Fehlermeldung
- Kein PHP-Code im Upload-Verzeichnis ausführbar

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| PHP-Dateien abgelehnt | ☐ | ☐ |
| Double-Extension abgelehnt | ☐ | ☐ |

---

### TC-SEC-13 🟠 · Path-Traversal im Media-Proxy

**Vorgehen:**
```
GET /media-proxy.php?file=../../config.php
GET /media-proxy.php?file=../../../etc/passwd
```

**Erwartet:**
- HTTP 403 oder 404
- Kein Dateiinhalt in Response

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Path-Traversal blockiert | ☐ | ☐ |

---

## 7. Admin-Bereich

### TC-SEC-14 🔴 · Admin-Zugang ohne Session

**Vorgehen:**
```bash
curl -L https://your-test-instance/admin/dashboard.php
```

**Erwartet:**
- HTTP 302 Redirect auf Login-Seite
- Kein Admin-HTML-Inhalt in Response

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Redirect auf Login | ☐ | ☐ |

---

### TC-SEC-15 🟠 · Member-Account kann nicht auf Admin-Seiten zugreifen

**Vorgehen:**
1. Als normaler Member-User einloggen
2. `/admin/dashboard.php` direkt aufrufen

**Erwartet:**
- HTTP 302 oder 403
- Kein Admin-Inhalt sichtbar

| Ergebnis | ☐ Bestanden | ☐ Fehlgeschlagen |
|---|---|---|
| Zugriff verweigert | ☐ | ☐ |

---

## 8. Testprotokoll

| Datum | Tester | Umgebung | Tool | Ergebnis |
|---|---|---|---|---|
| | | | | |

**Offene Punkte / Befunde:**

<!-- Hier gefundene Sicherheitsprobleme dokumentieren -->
