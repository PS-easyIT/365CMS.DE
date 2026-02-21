# CMSv2 - Member-Bereich Dokumentation

**Version:** 2.0.2  
**Datum:** 18. Februar 2026  
**Status:** ✅ Produktionsreif  
**Pfad:** `/CMS365/CMSv2/member/`

---

## 📋 Übersicht

Der Member-Bereich ist das **persönliche Dashboard** für eingeloggte Mitglieder. Er bietet Verwaltung von Profil, Sicherheitseinstellungen, Benachrichtigungen, Datenschutz und optionalen Abonnements.

### Architektur

Der Member-Bereich folgt einem **MVC-ähnlichen Muster** (Model-View-Controller):

```
member/
├── index.php                           # Dashboard (Controller)
├── profile.php                         # Profil (Controller)
├── security.php                        # Sicherheit (Controller)
├── notifications.php                   # Benachrichtigungen (Controller)
├── privacy.php                         # Datenschutz (Controller)
├── subscription.php                    # Abonnement (Controller)
│
├── includes/
│   └── class-member-controller.php     # Basis-Controller (MemberController)
│
└── partials/
    ├── member-menu.php                 # Sidebar-Navigation + Styles
    ├── dashboard-view.php              # Dashboard-View
    ├── profile-view.php                # Profil-View
    ├── security-view.php               # Sicherheits-View
    ├── notifications-view.php          # Benachrichtigungs-View
    ├── privacy-view.php                # Datenschutz-View
    └── subscription-view.php          # Abonnement-View
```

---

## 🗺️ Seiten & URLs

| URL                    | Datei                  | Beschreibung                         |
|------------------------|------------------------|--------------------------------------|
| `/member`             | `index.php`            | Dashboard – Übersicht & Schnellzugriff |
| `/member/profile`     | `profile.php`          | Profil bearbeiten                    |
| `/member/security`    | `security.php`         | Passwort, 2FA, Sessions              |
| `/member/notifications` | `notifications.php`  | E-Mail- & Browser-Präferenzen        |
| `/member/privacy`     | `privacy.php`          | DSGVO-Einstellungen, Datenexport     |
| `/member/subscription` | `subscription.php`    | Abo-Übersicht (optional sichtbar)    |

---

## 🔐 Zugriffsschutz

Alle URLs werden durch den `MemberController`-Konstruktor gesichert:

```php
// Nicht eingeloggt → /login
if (!Auth::instance()->isLoggedIn()) {
    $this->redirect('/login');
}

// Admin → Admin-Center (nicht Member-Bereich)
if (Auth::instance()->isAdmin()) {
    $this->redirect('/admin');
}
```

**Ergebnis:** Nur reguläre Mitglieder haben Zugang. Admins werden in ihr Panel geleitet.

---

## 🔄 Request-Lifecycle

```
HTTP Request (/member/security)
    │
    ├─ 1. config.php + autoload.php laden
    ├─ 2. MemberController instanziieren
    │      └─ Auth-Check (isLoggedIn + nicht Admin)
    │      └─ Services initialisieren
    ├─ 3. POST prüfen → handleSecurityActions()
    │      └─ CSRF-Token verifizieren
    │      └─ Aktion ausführen
    │      └─ PRG-Redirect (setSuccess/setError + redirect)
    ├─ 4. Seitendaten aufbereiten
    └─ 5. render('security-view', $data)
           └─ member-menu.php includen (Funktionsdefinitionen)
           └─ partials/security-view.php includen (mit extract($data))
```

---

## 📦 Datenübergabe an Views

Der `MemberController::render()` übergibt Daten via PHP `extract()`:

```php
$controller->render('security-view', [
    'securityData'   => $memberService->getSecurityData($user->id),
    'activeSessions' => $memberService->getActiveSessions($user->id),
    'csrfPassword'   => Security::instance()->generateToken('change_password'),
    'csrf2FA'        => Security::instance()->generateToken('toggle_2fa'),
]);
```

Zusätzlich wird `$user` immer automatisch aus `$this->user` injiziert.

---

## 🔌 Plugin-Integration

Der Member-Bereich ist vollständig erweiterbar via Hooks. Plugins können:

- Menüpunkte hinzufügen (`member_menu_items`)
- Dashboard-Widgets einfügen (`member_dashboard_widgets`)
- Benachrichtigungsfelder ergänzen (`member_notification_settings_sections`)
- Benachrichtigungs-Präferenzen filtern (`member_notification_preferences`)

→ Vollständige Hook-Referenz: [HOOKS.md](HOOKS.md)

---

## 📚 Weitere Dokumentation

| Dokument | Beschreibung |
|----------|-------------|
| [CONTROLLERS.md](CONTROLLERS.md) | Alle Controller im Detail |
| [VIEWS.md](VIEWS.md) | Alle Views mit Variablen-Referenz |
| [HOOKS.md](HOOKS.md) | Verfügbare Hooks & Filter |
| [SECURITY.md](SECURITY.md) | Sicherheitsmodell des Member-Bereichs |
