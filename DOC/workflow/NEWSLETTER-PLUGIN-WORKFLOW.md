# Newsletter-Plugin Workflow – cms-newsletter

> **Stand:** 2026-03-08 | **Version:** 2.5.4 | **Status:** Aktuell
>
> **Bereich:** Neues Plugin-Konzept · **Status:** Konzept (nicht implementiert)  
> **Referenz:** [NEW-PLUGIN-CONCEPTS.md](../feature/NEW-PLUGIN-CONCEPTS.md)  
> **Entwicklungs-Workflow:** [PLUGIN-DEVELOPMENT-WORKFLOW.md](PLUGIN-DEVELOPMENT-WORKFLOW.md)

---
<!-- UPDATED: 2026-03-08 -->

## Übersicht: Feature-Set

| Phase | Features | Komplexität |
|---|---|---|
| Phase 1 (MVP) | Abonnenten, einfache Kampagnen, DSGVO-Opt-In | 3 Tage |
| Phase 2 | Template-Builder, Statistiken, Segmentierung | 3 Tage |
| Phase 3 | Auto-Responder, A/B-Tests, SMTP-Config | 2 Tage |

---

## Workflow 1: Abonnent eintragen (Double-Opt-In)

```
Frontend Formular (via Shortcode oder API)
    ↓
POST /api/v1/newsletter/subscribe
  - email: max@example.com
  - first_name: Max (optional)
  - list_id: 1 (optional)
    ↓
Validierung:
  - E-Mail gültig? filter_var($email, FILTER_VALIDATE_EMAIL)
  - Bereits eingetragen? → duplicate_action: resend | ignore | error
  - Rate-Limit: max 3 Anmeldungen pro IP / Stunde
    ↓
DB-INSERT: cms_newsletter_subscribers
  - status = 'pending'
  - confirm_token = bin2hex(random_bytes(20))
  - confirm_expires = NOW() + 48 Stunden
    ↓
Bestätigungs-E-Mail senden:
  "Bitte bestätige deine Newsletter-Anmeldung"
  → Link: /newsletter/confirm?token=<confirm_token>
    ↓
User klickt Bestätigungs-Link
    ↓
GET /newsletter/confirm?token=<token>
  - Token in DB suchen
  - Abgelaufen? → Fehler + "Neu anfordern"-Link
  - Gültig? → status = 'active', IP + timestamp speichern
    ↓
Willkommens-E-Mail (Auto-Responder, falls aktiv)
```

### DB-Struktur Abonnenten

```sql
CREATE TABLE cms_newsletter_subscribers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255) NOT NULL UNIQUE,
    first_name      VARCHAR(100),
    last_name       VARCHAR(100),
    status          ENUM('pending','active','unsubscribed','bounced') DEFAULT 'pending',
    list_id         INT UNSIGNED DEFAULT 1,
    confirm_token   VARCHAR(64),
    confirm_expires DATETIME,
    subscribed_ip   VARCHAR(45),
    subscribed_at   DATETIME DEFAULT NOW(),
    unsubscribed_at DATETIME NULL,
    INDEX idx_status (status),
    INDEX idx_list   (list_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Workflow 2: Kampagne erstellen und senden

```
Admin → Plugins → Newsletter → "Neue Kampagne"
    ↓
Schritt 1: Grunddaten
  - Betreff: "Neuigkeiten im März 2026"
  - Absender-Name: "365CMS Team"
  - Antwort-Adresse: newsletter@domain.de
  - Empfänger: [Alle | Segment: member | Segment: aktiv > 30 Tage]
    ↓
Schritt 2: Inhalt
  - WYSIWYG-Editor (SunEditor)
  - Verfügbare Blöcke:
    [Text] [Bild] [Button] [Trennlinie] [Zwei-Spalten]
  - Personalisierung: {{first_name}}, {{unsubscribe_link}}
    ↓
Schritt 3: Vorschau + Test
  - Test-E-Mail an eigene Adresse senden
  - Mobile + Desktop Vorschau
  - Spam-Score anzeigen (falls SpamAssassin API verfügbar)
    ↓
Schritt 4: Senden / Planen
  - Jetzt senden: Bestätigung via Modal
  - Geplant: Datum + Uhrzeit wählen (Cron-basiert)
    ↓
Sende-Prozess (Batch-Versand):
  - Loop: 50 E-Mails / Minute (SMTP-Limits!)
  - Fehler loggen, Bounces markieren
  - Fortschritt in Admin sichtbar
    ↓
Nach Versand:
  - Statistiken: Gesendet, Zugestellt*, Öffnungsrate*, Klickrate*
  (*) Nur mit Tracking-Pixel, datenschutzkonform optional
```

### DB-Struktur Kampagnen

```sql
CREATE TABLE cms_newsletter_campaigns (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(255) NOT NULL,
    subject      VARCHAR(255) NOT NULL,
    from_name    VARCHAR(100),
    from_email   VARCHAR(255),
    content_html LONGTEXT,
    content_text LONGTEXT,
    status       ENUM('draft','scheduled','sending','sent','paused') DEFAULT 'draft',
    list_id      INT UNSIGNED,
    scheduled_at DATETIME NULL,
    sent_at      DATETIME NULL,
    sent_count   INT DEFAULT 0,
    created_at   DATETIME DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Workflow 3: Abmelden (DSGVO-konform)

```
Jede E-Mail enthält:
  "Abmelden" → Link mit einmaligem Token

GET /newsletter/unsubscribe?token=<unsubscribe_token>
    ↓
Token validieren → E-Mail-Adresse aus Token ermitteln
    ↓
Bestätigungs-Seite:
  "Möchtest du dich vom Newsletter abmelden?"
  [Ja, abmelden] [Nein, abbrechen]
    ↓
POST /newsletter/unsubscribe
  - status = 'unsubscribed'
  - unsubscribed_at = NOW()
  - E-Mail NICHT löschen (Nachweis!)
    ↓
Bestätigungs-Seite:
  "Du wurdest erfolgreich abgemeldet."
```

---

## Workflow 4: DSGVO-Compliance

```
PFLICHT bei Double-Opt-In:
[ ] Bestätigungs-E-Mail vor Aktivierung
[ ] IP + Zeitstempel der Bestätigung speichern
[ ] Unsubscribe-Link in JEDER E-Mail
[ ] Keine E-Mail nach Opt-Out

DATENSCHUTZ-EINSTELLUNGEN (Admin):
[ ] E-Mail auf Anfrage löschen (DSGVO Art. 17)
    → status = 'deleted', email = MD5-Hash (anonymisiert)
[ ] Export aller Daten eines Abonnenten (Art. 15)
[ ] Aufbewahrungsfristen konfigurierbar
[ ] Datenschutzhinweis auf Anmeldeformular verlinken
```

---

## Sicherheits-Anforderungen

```php
// Anti-Spam im Subscribe-Endpoint:
// 1. Honeypot-Feld (unsichtbares Feld, das Bot ausfüllen würden):
if (!empty($_POST['website'])) { /* Bot */ exit; }

// 2. Rate-Limiting:
$key = 'newsletter_subscribe_' . $_SERVER['REMOTE_ADDR'];
$attempts = (int)\CMS\CacheManager::instance()->get($key, 0);
if ($attempts >= 3) {
    ApiResponse::error('Zu viele Anmeldeversuche', 429);
}
\CMS\CacheManager::instance()->set($key, $attempts + 1, 3600);

// 3. Token-Generierung sicher:
$token = bin2hex(random_bytes(20)); // 40-Zeichen
// NIEMALS: md5(email + time()) – vorhersagbar!
```

---

## Plugin-Struktur

```
plugins/cms-newsletter/
├── cms-newsletter.php
├── includes/
│   ├── class-install.php       ← DB-Tabellen
│   ├── class-subscribers.php   ← Abonnenten-CRUD
│   ├── class-campaigns.php     ← Kampagnen-CRUD
│   ├── class-mailer.php        ← E-Mail-Versand (SMTP)
│   ├── class-api.php           ← REST-Endpoints
│   └── class-admin.php         ← Admin-Seiten
├── admin/
│   ├── subscribers.php
│   ├── campaigns.php
│   └── settings.php
├── templates/
│   ├── subscribe-form.php      ← Frontend-Formular
│   ├── confirm.php             ← Bestätigungsseite
│   └── unsubscribe.php         ← Abmelde-Seite
├── assets/
│   ├── css/newsletter.css      ← Prefix: cms-newsletter-
│   └── js/newsletter.js
└── update.json
```

---

## Referenzen

- [NEW-PLUGIN-CONCEPTS.md](../feature/NEW-PLUGIN-CONCEPTS.md) – Konzept-Übersicht
- [PLUGIN-DEVELOPMENT-WORKFLOW.md](PLUGIN-DEVELOPMENT-WORKFLOW.md) – Entwicklungsworkflow
- [SECURITY-HARDENING-WORKFLOW.md](SECURITY-HARDENING-WORKFLOW.md) – Anti-Spam, CSRF
