#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Documentation writer - member/general & admin stubs
Version 0.26.13 | 365CMS
"""

import os

BASE = r"e:\00-WPwork\365CMS.DE\DOC"
FOOTER = "\n---\n\n*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*\n"
HEADER_SUFFIX = " | **Stand:** 21. Februar 2026"

files = {}

# ──────────────────────────────────────────────────────────────
# member/general/PROFILE.md
# ──────────────────────────────────────────────────────────────
files["member/general/PROFILE.md"] = """# Member Profil

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026 | **Datei:** `member/profile.php`

Der Profilbereich ermöglicht eingeloggten Mitgliedern die vollständige Verwaltung ihrer persönlichen Daten und Kontoeinstellungen.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Stammdaten bearbeiten](#2-stammdaten-bearbeiten)
3. [Avatar & Bild](#3-avatar--bild)
4. [Kontakt & Social](#4-kontakt--social)
5. [Passwort ändern](#5-passwort-ändern)
6. [Sichtbarkeitseinstellungen](#6-sichtbarkeitseinstellungen)
7. [Technische Details](#7-technische-details)

---

## 1. Überblick

URL: `/member/profile`

Das Profil-Formular wird als Standard-POST verarbeitet (PRG-Pattern). Nach erfolgreicher Speicherung erfolgt eine Weiterleitung, damit versehentliches Neu-Laden kein doppeltes Absenden verursacht.

---

## 2. Stammdaten bearbeiten

| Feld | Typ | Pflicht | Beschreibung |
|---|---|---|---|
| `username` | Text | Ja | Eindeutiger Anzeigename (3–30 Zeichen) |
| `first_name` | Text | Nein | Vorname |
| `last_name` | Text | Nein | Nachname |
| `email` | E-Mail | Ja | Login-E-Mail, muss eindeutig sein |
| `bio` | Textarea | Nein | Freitext-Beschreibung (max. 1000 Zeichen) |
| `phone` | Text | Nein | Telefonnummer |
| `website` | URL | Nein | Persönliche Website (`https://` erforderlich) |

**Validierungsregeln:**
- E-Mail: RFC 5322-konform, Eindeutigkeitsprüfung in der Datenbank
- Benutzername: Regex `^[a-zA-Z0-9_.-]{3,30}$`
- Bio: HTML-Tags werden gefiltert (`strip_tags`)

---

## 3. Avatar & Bild

- **Upload-Formate:** JPG, PNG, WebP (GIF nicht erlaubt)
- **Maximale Dateigröße:** 2 MB (konfigurierbar über `max_avatar_size` in Settings)
- **Automatische Verkleinerung:** Bilder über 400×400 px werden automatisch skaliert
- **Speicherort:** `/uploads/avatars/{user_id}/`
- **Entfernen:** Avatar kann auf Standard-Gravatar zurückgesetzt werden

---

## 4. Kontakt & Social

Social-Media-Felder werden als `user_meta` gespeichert:

| Feld | Beispiel |
|---|---|
| `social_twitter` | `https://twitter.com/username` |
| `social_linkedin` | `https://linkedin.com/in/username` |
| `social_github` | `https://github.com/username` |
| `social_xing` | `https://xing.com/profile/username` |

Die Links werden im öffentlichen Profil (Experts-Plugin) angezeigt.

---

## 5. Passwort ändern

Separates Formular innerhalb der Profilseite:

1. **Aktuelles Passwort** eingeben (Schutz vor fremden Zugriffen)
2. **Neues Passwort** (min. 8 Zeichen, 1 Großbuchstabe, 1 Zahl)
3. **Passwort bestätigen** (muss identisch sein)

```php
// Passwort-Update in MemberService
$memberService->updatePassword($userId, $currentPassword, $newPassword);
// Wirft Exception bei falschem aktuellem Passwort
```

Nach Passwort-Änderung: Alle anderen aktiven Sessions werden automatisch beendet.

---

## 6. Sichtbarkeitseinstellungen

Relevant für das **Experts-Plugin** (`cms-experts`):

| Einstellung | Standard | Beschreibung |
|---|---|---|
| `profile_public` | `true` | Profil im öffentlichen Verzeichnis anzeigen |
| `show_email` | `false` | E-Mail-Adresse öffentlich zeigen |
| `show_phone` | `false` | Telefonnummer öffentlich zeigen |
| `show_location` | `true` | Standort/Stadt anzeigen |

---

## 7. Technische Details

**Controller:** `CMS\\Member\\MemberController`  
**Service:** `CMS\\Services\\MemberService::updateProfile(int $userId, array $data): bool`  
**CSRF-Token:** `member_profile` (30 Min. Gültigkeit)

```php
$result = $memberService->updateProfile($controller->getUser()->id, [
    'username'   => $controller->getPost('username'),
    'email'      => $controller->getPost('email', 'email'),
    'first_name' => $controller->getPost('first_name'),
    'last_name'  => $controller->getPost('last_name'),
    'bio'        => $controller->getPost('bio', 'textarea'),
    'phone'      => $controller->getPost('phone'),
    'website'    => $controller->getPost('website', 'url')
]);
```

**Hooks:**
```php
do_action('cms_member_profile_updated', $userId, $updateData);
do_action('cms_member_avatar_changed', $userId, $newAvatarPath);
```
""" + FOOTER

# ──────────────────────────────────────────────────────────────
# member/general/DASHBOARD.md
# ──────────────────────────────────────────────────────────────
files["member/general/DASHBOARD.md"] = """# Member Dashboard

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026 | **Datei:** `member/index.php`

Das Member-Dashboard ist die persönliche Startseite jedes eingeloggten Benutzers und bündelt die wichtigsten Informationen auf einen Blick.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Dashboard-Widgets](#2-dashboard-widgets)
3. [Plugin-Widgets](#3-plugin-widgets)
4. [Zugang & Routing](#4-zugang--routing)
5. [Personalisierung](#5-personalisierung)
6. [Technische Details](#6-technische-details)

---

## 1. Überblick

URL: `/member` oder `/member/index.php`

Das Dashboard kombiniert:
- **System-Widgets** (immer vorhanden, fest)
- **Plan-Widgets** (abhängig vom aktuellen Abo-Paket)
- **Plugin-Widgets** (von aktivierten Plugins injiziert)

---

## 2. Dashboard-Widgets

### Willkommens-Widget
- Begrüßung mit Vorname des Mitglieds
- Anzeige des aktuellen Abo-Plans (z.B. „Free", „Pro", „Business")
- Ablaufdatum des Abos (sofern zeitlich begrenzt)

### Aktivitäts-Feed
- Letzte 10 Aktionen des Benutzers im System
- Zeitstempel, Aktivitätstyp und Link zur betreffenden Seite
- Typen: `login`, `profile_update`, `order`, `message_sent`, `file_uploaded`

### Benachrichtigungs-Widget
- Die 5 neuesten ungelesenen Benachrichtigungen
- Link zu `/member/notifications` für alle Benachrichtigungen
- Ungelesene Anzahl als Badge

### Statistik-Kacheln

| Kachel | Beschreibung |
|---|---|
| Nachrichten | Anzahl ungelesener Nachrichten |
| Favoriten | Anzahl gespeicherter Favoriten |
| Dateien | Genutzter Speicherplatz von max. Limit |
| Aktive Tickets | Offene Support-Anfragen (falls Support-Plugin aktiv) |

---

## 3. Plugin-Widgets

Aktivierte Plugins können eigene Dashboard-Widgets registrieren:

```php
CMS\\Hooks::addAction('member_dashboard_widgets', function($registry) {
    $registry->register('my-plugin-widget', [
        'title'    => 'Mein Plugin',
        'callback' => 'MyPlugin::renderDashboardWidget',
        'priority' => 20,
        'plans'    => ['pro', 'business'],
    ]);
});
```

**Aktuelle Plugin-Widgets (wenn installiert):**
- `cms-experts`: Experten-Profilstatus und Anfragen
- `cms-events`: Nächste angemeldete Veranstaltungen
- `cms-jobads`: Aktive Stellenanzeigen

---

## 4. Zugang & Routing

Zugang nur für eingeloggte Benutzer – automatischer Auth-Check im `MemberController`:

```php
if (!$auth->isLoggedIn()) {
    header('Location: /login?redirect=/member');
    exit;
}
```

**Schnellnavigation:**
```
/member                  → Dashboard (diese Seite)
/member/profile          → Profil bearbeiten
/member/notifications    → Alle Benachrichtigungen
/member/subscription     → Abo & Upgrade
/member/media            → Eigene Dateien
/member/messages         → Direktnachrichten
/member/favorites        → Favoritenliste
/member/privacy          → Datenschutz & DSGVO
/member/security         → Sicherheitseinstellungen
```

---

## 5. Personalisierung

- **Widget-Reihenfolge:** Admin-seitig über `design-dashboard-widgets.php` konfigurierbar
- **Geplant (Roadmap):** Drag-and-Drop-Widget-Sortierung durch Mitglieder
- **Responsive:** Grid bricht bei mobilen Geräten auf einspaltigen Layout um

---

## 6. Technische Details

**Controller:** `CMS\\Member\\MemberController`

```php
$controller->render('dashboard-view', [
    'notifications' => $memberService->getRecentNotifications($user->id, 5),
    'stats'         => $memberService->getDashboardStats($user->id),
    'activities'    => $memberService->getRecentActivities($user->id, 10),
    'subscription'  => $memberService->getUserSubscription($user->id),
    'pluginWidgets' => CMS\\Hooks::applyFilters('member_dashboard_widgets', []),
]);
```

**Hooks:**
```php
add_action('member_dashboard_widgets', 'mein_plugin_widget_registrieren');
add_filter('member_dashboard_stats', 'mein_plugin_stats_erweitern', 10, 2);
```
""" + FOOTER

# ──────────────────────────────────────────────────────────────
# member/general/NOTIFICATIONS.md
# ──────────────────────────────────────────────────────────────
files["member/general/NOTIFICATIONS.md"] = """# Benachrichtigungen

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026 | **Datei:** `member/notifications.php`

Zentrale Inbox für alle System- und Benutzer-Benachrichtigungen im Mitglieder-Bereich.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Benachrichtigungstypen](#2-benachrichtigungstypen)
3. [Lesen & Verwalten](#3-lesen--verwalten)
4. [Filter & Suche](#4-filter--suche)
5. [E-Mail-Einstellungen](#5-e-mail-einstellungen)
6. [Technische Details](#6-technische-details)

---

## 1. Überblick

URL: `/member/notifications`

Benachrichtigungen informieren Mitglieder über relevante Ereignisse. Sie werden in der Tabelle `cms_notifications` gespeichert und als Badge-Zähler im Dashboard angezeigt.

**Lebenszyklus:**
1. Benachrichtigung wird erstellt (via Hook oder direktem API-Aufruf)
2. Anzeige im Dashboard-Widget (Badge) und auf der Notifications-Seite
3. Beim Klick: Status → `read`, Weiterleitung zur verknüpften Seite
4. Automatische Bereinigung nach 90 Tagen

---

## 2. Benachrichtigungstypen

| Typ | Icon | Beschreibung | Beispiel |
|---|---|---|---|
| `system` | ⚙️ | Wichtige System-Meldungen | Update verfügbar |
| `security` | 🔒 | Sicherheitsereignisse | Neuer Login von unbekanntem Gerät |
| `subscription` | 💳 | Abo-Ereignisse | Abo läuft in 7 Tagen ab |
| `message` | ✉️ | Neue Direktnachricht | Neue Nachricht von Max Muster |
| `info` | ℹ️ | Allgemeine Informationen | Neue Funktion verfügbar |
| `warning` | ⚠️ | Warnungen | Speicherplatz fast aufgebraucht |
| `plugin` | 🔌 | Plugin-spezifisch | Neue Buchungsanfrage |

---

## 3. Lesen & Verwalten

### Einzelne Benachrichtigung
- **Klick** → markiert als gelesen und öffnet verknüpften Link
- **„Als gelesen markieren"** – ohne Weiterleitung

### Massenaktionen
- **Alle als gelesen markieren** – setzt alle Notifications auf `read`
- **Alle löschen** – entfernt alle (nach Bestätigung)
- **Auswahl löschen** – Checkboxen + Bulk-Action

### Pagination
- 20 Einträge pro Seite
- Sortierung: Neueste zuerst (Standard) / Älteste zuerst

---

## 4. Filter & Suche

| Filter | Optionen |
|---|---|
| **Status** | Alle, Ungelesen, Gelesen |
| **Typ** | Alle, System, Sicherheit, Abo, Nachricht, Info, Plugin |
| **Zeitraum** | Heute, Letzte 7 Tage, Letzter Monat, Alle |

Filter als kombinierbare GET-Parameter:
```
/member/notifications?type=security&status=unread
```

---

## 5. E-Mail-Einstellungen

| Typ | Standard | Konfigurierbar |
|---|---|---|
| `security` | ✅ immer | ❌ Nein (Sicherheitsschutz) |
| `subscription` | ✅ aktiv | ✅ Ja |
| `message` | ✅ aktiv | ✅ Ja |
| `system` | ❌ deaktiviert | ✅ Ja |
| `info` | ❌ deaktiviert | ✅ Ja |

Einstellungen als `user_meta` mit Key `notification_prefs` (JSON).

---

## 6. Technische Details

**Datenbank-Tabelle:** `cms_notifications`

```sql
CREATE TABLE cms_notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    type        VARCHAR(50) NOT NULL DEFAULT 'info',
    title       VARCHAR(255) NOT NULL,
    message     TEXT,
    link        VARCHAR(500),
    is_read     TINYINT(1) DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_unread (user_id, is_read)
);
```

**API – Benachrichtigung erstellen:**
```php
use CMS\\Services\\NotificationService;

NotificationService::create([
    'user_id' => $userId,
    'type'    => 'info',
    'title'   => 'Willkommen!',
    'message' => 'Ihr Konto wurde erfolgreich erstellt.',
    'link'    => '/member/profile'
]);
```

**Hooks:**
```php
do_action('cms_notification_created', $notificationId, $userId, $type);
do_action('cms_notification_read', $notificationId, $userId);
```
""" + FOOTER

# ──────────────────────────────────────────────────────────────
# member/general/PRIVACY.md
# ──────────────────────────────────────────────────────────────
files["member/general/PRIVACY.md"] = """# Datenschutz & Privatsphäre

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026 | **Datei:** `member/privacy.php`

DSGVO-konformes Datenschutz-Center für Mitglieder. Ermöglicht die Verwaltung aller persönlichen Daten und rechtlicher Einwilligungen.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Datenkategorien](#2-datenkategorien)
3. [Daten-Export (DSGVO Art. 20)](#3-daten-export-dsgvo-art-20)
4. [Daten löschen (DSGVO Art. 17)](#4-daten-löschen-dsgvo-art-17)
5. [Einwilligungen verwalten](#5-einwilligungen-verwalten)
6. [Konto endgültig löschen](#6-konto-endgültig-löschen)
7. [Technische Details](#7-technische-details)

---

## 1. Überblick

URL: `/member/privacy`

Das Datenschutz-Center erfüllt die Anforderungen der **DSGVO (EU 2016/679)**:
- **Recht auf Auskunft** (Art. 15) – Datenkategorien einsehen
- **Recht auf Datenübertragbarkeit** (Art. 20) – Export als JSON/CSV
- **Recht auf Löschung** (Art. 17) – Account-Löschung beantragen
- **Einwilligungsverwaltung** (Art. 7) – Zustimmungen verwalten

Alle Aktionen auf dieser Seite verwenden separate CSRF-Tokens.

---

## 2. Datenkategorien

Übersicht der gespeicherten Daten:

| Kategorie | Dateninhalt | Gespeichert in |
|---|---|---|
| **Profildaten** | Name, E-Mail, Telefon, Bio | `cms_users`, `cms_user_meta` |
| **Login-Daten** | Letzte Anmeldungen, IP-Adressen | `cms_login_log` |
| **Bestellungen** | Rechnungen, Abo-History | `cms_orders`, `cms_subscriptions` |
| **Nachrichten** | Gesendete/empfangene Direktnachrichten | `cms_messages` |
| **Mediendaten** | Hochgeladene Dateien | `/uploads/members/{id}/` |
| **Benachrichtigungen** | Log aller System-Notifications | `cms_notifications` |
| **Cookies** | Session-Cookie, optionale Analytics | Browser |

---

## 3. Daten-Export (DSGVO Art. 20)

1. Klick auf **„Meine Daten exportieren"**
2. CSRF-Token-Validierung
3. System erstellt ZIP-Archiv mit:
   - `profile.json` – Profildaten
   - `orders.json` – Bestellhistorie
   - `messages.json` – Nachrichtenverläufe
   - `activity.json` – Login- und Aktivitätslog
4. Download-Link per E-Mail (gültig 24 Stunden)

**Wartezeit:** Asynchrone Verarbeitung, max. 15 Minuten bei großen Datensätzen.

```php
$memberService->requestDataExport($user->id);
// Feuert: do_action('cms_member_data_export_requested', $userId)
```

---

## 4. Daten löschen (DSGVO Art. 17)

| Kategorie | Einzeln löschbar | Hinweis |
|---|---|---|
| Login-Log | ✅ Ja | Sicherheitslog der letzten 90 Tage |
| Nachrichten | ✅ Ja | Eigene Seite der Konversation |
| Hochgeladene Dateien | ✅ Ja | Nur eigene Dateien |
| Profildaten | ❌ Nein | Nur via Account-Löschung |
| Bestelldaten | ❌ Nein | Aufbewahrungspflicht 10 Jahre (§ 147 AO) |

---

## 5. Einwilligungen verwalten

| Einwilligung | Standard | Widerrufbar |
|---|---|---|
| **Notwendige Cookies** | ✅ Pflicht | ❌ Nein |
| **Newsletter** | ❌ Opt-in | ✅ Ja |
| **Analytics** | ❌ Opt-in | ✅ Ja |
| **Profil-Sichtbarkeit** | ✅ aktiv | ✅ Ja |
| **Mitgliederverzeichnis** | ✅ aktiv | ✅ Ja |

Widerruf wird sofort wirksam. Newsletter-Widerruf sendet Abmelde-Bestätigung per E-Mail.

---

## 6. Konto endgültig löschen

> ⚠️ **Diese Aktion ist nicht umkehrbar!**

**Prozess:**
1. Klick auf „Konto löschen"
2. Passwort zur Bestätigung eingeben
3. Checkbox: „Ich verstehe, dass alle meine Daten gelöscht werden"
4. 30-Tage-Übergangsfrist (Deaktivierung, nicht sofortige Löschung)
5. Reaktivierung innerhalb der 30 Tage möglich
6. Endgültige Löschung per E-Mail bestätigt

**Was wird gelöscht:** Profildaten, Avatar, Benachrichtigungen, Login-Logs  
**Was bleibt:** Bestelldaten (steuerliche Pflicht), publizierte Inhalte (anonymisiert)

---

## 7. Technische Details

**CSRF-Tokens (drei separate):**
```php
$data = [
    'csrfPrivacy' => Security::instance()->generateToken('privacy_settings'),
    'csrfExport'  => Security::instance()->generateToken('data_export'),
    'csrfDelete'  => Security::instance()->generateToken('account_delete'),
];
```

**Hooks:**
```php
do_action('cms_member_data_export_requested', $userId);
do_action('cms_member_consent_updated', $userId, $consentKey, $newValue);
do_action('cms_member_account_deletion_requested', $userId, $scheduledDate);
```
""" + FOOTER

# ──────────────────────────────────────────────────────────────
# member/general/SECURITY.md
# ──────────────────────────────────────────────────────────────
files["member/general/SECURITY.md"] = """# Sicherheits-Center

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

**Services:** `CMS\\Security`, `CMS\\Services\\MemberService`

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
""" + FOOTER

# ──────────────────────────────────────────────────────────────
# member/general/SUBSCRIPTION.md
# ──────────────────────────────────────────────────────────────
files["member/general/SUBSCRIPTION.md"] = """# Abo-Verwaltung

> **Version:** 0.26.13 | **Stand:** 21. Februar 2026 | **Datei:** `member/subscription.php`

Der Abo-Bereich zeigt den aktuellen Mitgliedschaftsplan und ermöglicht Upgrades, Downgrades sowie den Zugriff auf Rechnungen und Zahlungsinformationen.

---

## Inhaltsverzeichnis

1. [Überblick](#1-überblick)
2. [Aktueller Plan](#2-aktueller-plan)
3. [Verfügbare Pakete](#3-verfügbare-pakete)
4. [Upgrade / Downgrade](#4-upgrade--downgrade)
5. [Zahlungsoptionen](#5-zahlungsoptionen)
6. [Rechnungen & History](#6-rechnungen--history)
7. [Technische Details](#7-technische-details)

---

## 1. Überblick

URL: `/member/subscription`

Mitglieder sehen ihren aktuellen Plan und können direkt upgraden. Kein automatischer Zahlungseinzug – 365CMS verwendet manuelles Billing.

---

## 2. Aktueller Plan

| Information | Beispiel |
|---|---|
| **Plan-Name** | „Pro" |
| **Status** | Aktiv / Abgelaufen / Pending / Gekündigt |
| **Laufzeit** | Monatlich / Jährlich |
| **Nächste Abrechnung** | 21. März 2026 |
| **Ablaufdatum** | 21. März 2026 (oder „Unbegrenzt") |
| **Features** | Liste freigeschalteter Funktionen |

**Status-Badges:**
```php
$statusBadges = [
    'active'    => 'success',   // Grün
    'expired'   => 'danger',    // Rot
    'pending'   => 'warning',   // Gelb
    'cancelled' => 'secondary', // Grau
];
```

---

## 3. Verfügbare Pakete

Pakete werden über `SubscriptionManager::getAllPlans()` geladen:

| Paket | Preis | Intervall | Highlights |
|---|---|---|---|
| **Free** | 0 € | – | Basis-Funktionen, 100 MB Speicher |
| **Starter** | 9,90 € | Monatlich | 1 GB Speicher, Nachrichten |
| **Pro** | 24,90 € | Monatlich | 10 GB Speicher, alle Plugins |
| **Business** | 49,90 € | Monatlich | Unbegrenzt, API-Zugang, White-Label |

*Pakete werden im Admin unter `admin/subscription-packages.php` verwaltet.*

---

## 4. Upgrade / Downgrade

### Upgrade-Prozess
1. Gewünschtes Paket auswählen → „Jetzt upgraden"
2. Zahlungsinformationen werden angezeigt (Banküberweisung oder PayPal)
3. Zahlung manuell durchführen
4. Admin bestätigt → Plan wird aktiviert
5. Bestätigungs-E-Mail wird gesendet

### Downgrade
- Zum Ende der aktuellen Laufzeit möglich
- Restwert wird bei Sofort-Downgrade nicht erstattet
- Automatische Benachrichtigung 7 Tage vor Ablauf

---

## 5. Zahlungsoptionen

Zahlungsinformationen aus CMS-Einstellungen:

```php
$paymentInfo = [
    'bank'   => $db->get_var("SELECT option_value FROM cms_settings
                              WHERE option_name = 'payment_info_bank'"),
    'paypal' => $db->get_var("SELECT option_value FROM cms_settings
                              WHERE option_name = 'payment_info_paypal'"),
    'note'   => $db->get_var("SELECT option_value FROM cms_settings
                              WHERE option_name = 'payment_info_note'"),
];
```

**Konfiguration im Admin:** `admin/settings.php` → Bereich „Zahlungsinformationen"

---

## 6. Rechnungen & History

- **Rechnungsliste:** Datum, Paket, Betrag, Status als Tabelle
- **PDF-Download:** Jede Rechnung einzeln als PDF
- **Bestellnummer:** Eindeutige Referenz für Support-Anfragen

| Status | Bedeutung |
|---|---|
| `paid` | Bezahlt und bestätigt |
| `pending` | Zahlung erwartet |
| `refunded` | Erstattet |
| `cancelled` | Storniert |

---

## 7. Technische Details

**Services:** `CMS\\Services\\MemberService`, `CMS\\SubscriptionManager`

```php
$controller->render('subscription-view', [
    'subscription' => $memberService->getUserSubscription($user->id),
    'allPlans'     => $subscriptionManager->getAllPlans(),
    'paymentInfo'  => $paymentInfo,
    'permissions'  => $memberService->getUserPermissions($user->id),
    'statusBadges' => $statusBadges
]);
```

**Hooks:**
```php
do_action('cms_subscription_upgraded', $userId, $oldPlanId, $newPlanId);
do_action('cms_subscription_cancelled', $userId, $planId, $effectiveDate);
do_action('cms_subscription_expired', $userId, $planId);
```
""" + FOOTER

# ──────────────────────────── Write all files ────────────────────────────
written = []
errors = []

for rel_path, content in files.items():
    full_path = os.path.join(BASE, rel_path.replace("/", os.sep))
    os.makedirs(os.path.dirname(full_path), exist_ok=True)
    try:
        with open(full_path, "w", encoding="utf-8") as f:
            f.write(content)
        written.append(rel_path)
        print(f"  ✓  {rel_path}")
    except Exception as e:
        errors.append((rel_path, str(e)))
        print(f"  ✗  {rel_path}: {e}")

print(f"\nErgebnis: {len(written)} Dateien geschrieben, {len(errors)} Fehler.")
