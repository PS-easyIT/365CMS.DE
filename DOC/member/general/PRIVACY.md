# Datenschutz & Privatsphäre


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

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
