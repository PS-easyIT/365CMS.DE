# Member Profil

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

**Controller:** `CMS\Member\MemberController`  
**Service:** `CMS\Services\MemberService::updateProfile(int $userId, array $data): bool`  
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

---

*Letzte Aktualisierung: 21. Februar 2026 – Version 0.26.13*
