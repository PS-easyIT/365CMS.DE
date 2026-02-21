# 365CMS – Sicherheits-Roadmap

**Bereich:** Security, Compliance, Datenschutz, Betrug-Prävention  
**Stand:** 19. Februar 2026  
**Prioritäten:** 🔴 Kritisch · 🟠 High · 🟡 Mittel · 🟢 Low

---

## Grundsatz: Security by Default

Alle neuen Features werden nach dem Prinzip **"Secure by Default"** entwickelt:
- Gefährliche Operationen erfordern explizite Freischaltung
- Minimal-Permissions (Principle of Least Privilege)
- Alle Eingaben werden als feindlich betrachtet (Zero Trust Input)
- Alle Ausgaben werden kontextabhängig escaped

---

## 1. Authentifizierung & Autorisierung

### 🔴 S-01 · Multi-Factor-Authentication (MFA)
| Stufe | Feature |
|---|---|
| Stufe 1 | TOTP (Time-based One-Time Password, Google Authenticator) |
| Stufe 2 | Backup-Codes (10 Einmalcodes bei MFA-Verlust) |
| Stufe 3 | SMS-OTP via Twilio/Vonage (als zweite Option) |
| Stufe 4 | E-Mail-OTP (Fallback ohne App) |
| Stufe 5 | Gerätevertrauen (bekannte Geräte merken für 30 Tage) |
| Stufe 6 | MFA-Pflicht pro Rolle (Admin MUSS MFA aktivieren) |
| Stufe 7 | WebAuthn/FIDO2 Hardware-Keys (YubiKey etc.) |
| Stufe 8 | Recovery-Flow für verlorenes MFA-Gerät |

---

### 🔴 S-02 · Brute-Force-Schutz & Login-Härtung
| Stufe | Feature |
|---|---|
| Stufe 1 | Konten-Sperrung nach X Fehlversuchen (konfigurierbar: 5) |
| Stufe 2 | Exponentielles Backoff (jede weitere Fehlerversuch → längere Wartezeit) |
| Stufe 3 | IP-Blacklisting (automatisch + manuell) |
| Stufe 4 | Login-Protokoll (Wann, Wo, Welches Gerät) |
| Stufe 5 | Verdächtiges-Login-Alarm (neue Geo-Location → E-Mail-Bestätigung) |
| Stufe 6 | Login-Restriktionen nach IP-Bereich (Whitelist für Admin) |
| Stufe 7 | CAPTCHA-Integration (hCaptcha/Cloudflare Turnstile – datenschutzfreundlich) |
| Stufe 8 | Credential-Stuffing-Schutz (Have-I-Been-Pwned-API-Check) |

---

### 🔴 S-03 · RBAC – Rollenbasierte Zugriffskontrolle
**Aktuell:** Admin, Editor, Member (3 Rollen, fest)  
**Ziel:** Granulares Capabilities-System

| Stufe | Feature |
|---|---|
| Stufe 1 | Capability-System (atomare Rechte statt grober Rollen) |
| Stufe 2 | Benutzerdefinierte Rollen im Admin erstellen |
| Stufe 3 | Kontext-Berechtigungen (Eigene Posts vs. Alle Posts) |
| Stufe 4 | Row-Level-Security (Zugriff auf spezifische Datensätze) |
| Stufe 5 | Zeitbegrenzte Rechte (Gastauthor-Zugang für 30 Tage) |
| Stufe 6 | Rollenhierarchie (Senior Editor erbt Editor-Rechte) |
| Stufe 7 | Audit-Trail für Rechteänderungen |
| Stufe 8 | ABAC-Erweiterung (Attribute-Based Access Control) |

---

## 2. Eingabe-Validierung & Ausgabe-Escaping

### 🔴 S-04 · Zentrales Sanitization-Framework
| Stufe | Feature |
|---|---|
| Stufe 1 | Typsichere Sanitizer-Klasse (Text, Email, URL, HTML, Integer etc.) |
| Stufe 2 | Kontextsensitives Escaping (HTML, Attribut, URL, JS, CSS, SQL) |
| Stufe 3 | Content-Security-Policy (CSP) Header automatisch generiert |
| Stufe 4 | HTML-Purifier-Integration für Nutzereingaben mit Rich-Text |
| Stufe 5 | Strikte JSON-Schema-Validierung für API-Payloads |
| Stufe 6 | SQL-Injection-Scanner (Dev-Mode: warnt bei unsicheren Queries) |
| Stufe 7 | XSS-Audit-Tool (automatisches Scannen aller Ausgabe-Punkte) |

---

### 🟠 S-05 · CSRF-Schutz
| Stufe | Feature |
|---|---|
| Stufe 1 | CSRF-Tokens für alle State-ändernden Formulare |
| Stufe 2 | Double-Submit-Cookie-Pattern für AJAX-Requests |
| Stufe 3 | SameSite-Cookie-Attribute (Strict/Lax) |
| Stufe 4 | Origin-Header-Validierung für API-Requests |
| Stufe 5 | CSRF-Token-Rotation nach Verbrauch |

---

## 3. Datenverschlüsselung & Datenschutz

### 🔴 S-06 · Datenverschlüsselung
| Stufe | Feature |
|---|---|
| Stufe 1 | Passwörter: Argon2id (PHP 8.3 Standard) |
| Stufe 2 | Sensible Daten at rest: AES-256-GCM für Datenbank-Felder |
| Stufe 3 | Transport: HSTS-Header, TLS-Mindestversion 1.2 erzwingen |
| Stufe 4 | Key-Management (Verschlüsselungsschlüssel rotieren ohne Datenverlust) |
| Stufe 5 | Daten-Pseudonymisierung für Analytics |
| Stufe 6 | Zero-Knowledge-Architektur für besonders sensible Felder |

---

### 🔴 S-07 · DSGVO & Privacy-Technik
| Stufe | Feature |
|---|---|
| Stufe 1 | Datenschutz-Einwilligungs-Management (Granular, auditierbar) |
| Stufe 2 | Automatisches Daten-Löschfrist-System (TTL pro Datenkategorie) |
| Stufe 3 | Pseudo-Anonymisierung von Analytics-Daten (IP truncation) |
| Stufe 4 | Datenschutz-Auskunfts-Export (Art. 15 DSGVO) – maschinenlesbar |
| Stufe 5 | Verarbeitungsverzeichnis (Art. 30 DSGVO) – auto-generiert |
| Stufe 6 | PII-Detektor (findet personenbezogene Daten in Freitext-Feldern) |
| Stufe 7 | Privacy-Impact-Assessment-Assistent |

---

## 4. Netzwerk & Infrastruktur

### 🟠 S-08 · Security-Headers
| Header | Ziel-Konfiguration | Priorität |
|---|---|---|
| `Content-Security-Policy` | Strikt, nonce-basiert | 🔴 Kritisch |
| `X-Frame-Options` | DENY (Clickjacking) | 🔴 Kritisch |
| `X-XSS-Protection` | 1; mode=block | 🔴 Kritisch |
| `X-Content-Type-Options` | nosniff | 🔴 Kritisch |
| `Referrer-Policy` | strict-origin-when-cross-origin | 🟠 High |
| `Permissions-Policy` | camera=(), microphone=() | 🟠 High |
| `Strict-Transport-Security` | max-age=31536000; includeSubDomains | 🔴 Kritisch |
| `Cross-Origin-Opener-Policy` | same-origin | 🟡 Mittel |

---

### 🔴 S-09 · Firewall & Intrusion Detection
| Stufe | Feature |
|---|---|
| Stufe 1 | Integrations-Hook für WAF (Cloudflare, nginx ModSecurity) |
| Stufe 2 | Integriertes Request-Filtering (bekannte Attack-Patterns) |
| Stufe 3 | Anomalie-Erkennung (ungewöhnliche Request-Muster) |
| Stufe 4 | Geo-IP-Blocking (bestimmte Länder blockieren) |
| Stufe 5 | Bot-Erkennung und -Klassifizierung (gut/schlecht/unbekannt) |
| Stufe 6 | DDoS-Mitigierung (Connection-Limiting, SYN-Flood-Schutz) |

---

## 5. Audit & Monitoring

### 🔴 S-10 · Audit-Log
| Stufe | Feature |
|---|---|
| Stufe 1 | Protokollierung aller Admin-Aktionen (Wer, Was, Wann, IP) |
| Stufe 2 | Protokollierung aller Login-Versuche (Erfolg und Misserfolge) |
| Stufe 3 | Protokollierung von API-Zugriffen |
| Stufe 4 | Protokollierung von Datei-Operationen (Upload, Löschen) |
| Stufe 5 | Audit-Log Suche und Filter im Admin |
| Stufe 6 | Audit-Log-Export (CSV, JSON) |
| Stufe 7 | Manipulation-Schutz (Audit-Log darf nicht bearbeitet werden) |
| Stufe 8 | SIEM-Integration (syslog, Splunk, Graylog) |

---

### 🟠 S-11 · Sicherheits-Monitoring
| Stufe | Feature |
|---|---|
| Stufe 1 | Datei-Integritäts-Monitor (Core-Dateien auf Änderungen prüfen) |
| Stufe 2 | Benachrichtigung bei Sicherheitsereignissen (E-Mail, Slack) |
| Stufe 3 | Wöchentlicher Sicherheitsbericht |
| Stufe 4 | Plugin/Theme-Vulnerability-Scanner (CVE-DB-Abgleich) |
| Stufe 5 | Automatische Deaktivierung kompromittierter Plugins |

---

## 6. Upload & Datei-Sicherheit

### 🔴 S-12 · Sichere Datei-Uploads
| Stufe | Feature |
|---|---|
| Stufe 1 | MIME-Type-Whitelist (kein PHP, JS, HTML Upload) |
| Stufe 2 | Content-basierte Type-Erkennung (fichier-Fingerprinting) |
| Stufe 3 | Maximale Dateigröße pro Typ und Rolle |
| Stufe 4 | Malware-Scan-Integration (ClamAV Hook) |
| Stufe 5 | Private Uploads (Dateien außerhalb Webroot) |
| Stufe 6 | Signed Download-URLs (zeitlich begrenzte Zugangslinks) |
| Stufe 7 | Automatische EXIF-Entfernung für Bilder (Datenschutz) |
| Stufe 8 | SVG-Sanitizer (XSS in SVG-Dateien verhindern) |

---

## 7. Dependency & Supply Chain Security

### 🟠 S-13 · Software-Supply-Chain
| Stufe | Feature |
|---|---|
| Stufe 1 | Composer-Lock-File und Hash-Verifikation |
| Stufe 2 | Automatische Dependency-Vulnerability-Scans (Dependabot) |
| Stufe 3 | SRI (Subresource Integrity) für CDN-Assets |
| Stufe 4 | Plugin-Signatur-Verifikation (kryptografisch signierte Pakete) |
| Stufe 5 | SBOM (Software Bill of Materials) generieren |

---

## 8. Penetrationtest-Checkliste (für jedes Release)

| Kategorie | Tests |
|---|---|
| **OWASP Top 10** | SQL-Injection, XSS, CSRF, IDOR, Path-Traversal, Broken Auth |
| **API-Security** | JWT-Bypass, Mass-Assignment, Rate-Limit-Bypass |
| **Upload** | polyglot files, MIME-Spoofing, LFI |
| **Business Logic** | Preis-Manipulation, Permission-Escalation |
| **Crypto** | Weak-Hash-Erkennung, Key-Leak-Detection |

---

*Letzte Aktualisierung: 19. Februar 2026*
