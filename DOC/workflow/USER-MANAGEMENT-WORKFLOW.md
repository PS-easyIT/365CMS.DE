# User-Management Workflow – 365CMS

> **Bereich:** Benutzerverwaltung · **Version:** 1.6.14  
> **Services:** `Auth.php`, `core/Services/UserService.php`  
> **Admin-Seiten:** `admin/users.php`, `admin/rbac.php`

---

## Übersicht: User-Rollen

| Rolle | Berechtigungen | Zugang |
|---|---|---|
| `admin` | Vollzugriff | Admin-Backend + Member-Bereich |
| `member` | Eigener Bereich | Member-Dashboard |
| `editor` | Inhalte verwalten | Admin (eingeschränkt) |
| `subscriber` | Nur lesen | Frontend |
| *Plugin-Rollen* | Plugin-spezifisch | Per Plugin definiert |

---

## Workflow 1: Neuen Benutzer anlegen

### Via Admin-UI
1. Admin → `admin/users.php` → "Neuen Benutzer erstellen"
2. Pflichtfelder: `username`, `email`, `password`, `role`
3. Optionale Felder: `first_name`, `last_name`, `bio`, `avatar`
4. CSRF-Token automatisch im Formular (Security-Prüfung)
5. Bestätigung: Status-Meldung + Weiterleitungs-URL

### Via UserService (programmatisch)
```php
$userService = \CMS\Services\UserService::instance();

$userId = $userService->createUser([
    'username'   => 'max.mustermann',
    'email'      => 'max@example.com',
    'password'   => 'sicheres-passwort-123!',  // Wird mit BCrypt-12 gehashed
    'role'       => 'member',
    'first_name' => 'Max',
    'last_name'  => 'Mustermann',
    'status'     => 'active', // active | pending | suspended
]);

if (is_wp_error($userId)) {
    // Fehler behandeln
} else {
    // Aktivierungs-E-Mail senden (wenn E-Mail-Plugin aktiv)
    \CMS\Hooks::doAction('user_registered', $userId);
}
```

---

## Workflow 2: Benutzer-Rollen verwalten (RBAC)

### Admin → RBAC-System (`admin/rbac.php`)

**Vorhandene Capabilities:**
```
manage_options    → Systemeinstellungen ändern
manage_users      → Benutzer verwalten
manage_plugins    → Plugins aktivieren/deaktivieren
manage_themes     → Themes aktivieren/Customizer
edit_posts        → Eigene Inhalte bearbeiten
edit_others_posts → Fremde Inhalte bearbeiten
publish_posts     → Inhalte veröffentlichen
manage_media      → Medien hochladen/löschen
view_analytics    → Statistiken einsehen
manage_backups    → Backups erstellen/wiederherstellen
```

### Capability-Check in Code
```php
use CMS\Auth;

// Einfacher Rollen-Check:
if (!Auth::instance()->isAdmin()) {
    wp_redirect('/member/');
    exit;
}

// Capability-Check (feiner):
if (!Auth::instance()->hasCapability('manage_plugins')) {
    http_response_code(403);
    exit('Keine Berechtigung');
}

// Eigene Capability für Plugin definieren:
\CMS\Hooks::addAction('plugins_loaded', function() {
    \CMS\Auth::instance()->addCapabilityToRole('member', 'manage_own_bookings');
});
```

---

## Workflow 3: Benutzer sperren / Account-Status

```php
$userService = \CMS\Services\UserService::instance();

// Benutzer suspendieren:
$userService->updateStatus($userId, 'suspended');
// → Login wird blockiert, Session invalidiert

// Benutzer reaktivieren:
$userService->updateStatus($userId, 'active');

// Benutzer löschen (DSGVO Art. 17):
// ACHTUNG: Erst alle personenbezogenen Daten löschen!
\CMS\Hooks::doAction('user_deletion_requested', $userId, $reason);
$userService->deleteUser($userId, anonymize: true);
// anonymize: Kommentare etc. werden anonymisiert statt gelöscht
```

---

## Workflow 4: Passwort-Reset

### User-seitig (Self-Service)
1. Login-Seite → "Passwort vergessen"
2. E-Mail mit Reset-Token (gültig: 1 Stunde)
3. Token-Link → Neues Passwort setzen
4. Alle aktiven Sessions des Nutzers invalidieren

### Admin-seitig (Passwort zurücksetzen)
```php
// Admin kann Passwort direkt setzen:
$userService->setPassword($userId, $newPassword);
// → password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12])
// → Session-Invalidierung des betroffenen Users
```

---

## Workflow 5: Massen-Import von Benutzern

**Via cms-importer Plugin** (CSV-Format):

```csv
username,email,role,first_name,last_name,status
max.mustermann,max@example.com,member,Max,Mustermann,active
erika.muster,erika@example.com,editor,Erika,Muster,active
```

```php
// In cms-importer:
$importer = new CmsImporterUsers();
$result = $importer->importFromCSV('/pfad/zur/users.csv', [
    'duplicate_action'  => 'skip',   // skip | update | error
    'send_welcome_mail' => false,     // Keine Massenmail
    'default_password'  => null,      // null = Zufallspasswort generieren
]);
```

---

## Workflow 6: DSGVO-Prozesse

### Auskunft (Art. 15)
```
Admin → admin/data-access.php → User-ID/E-Mail eingeben
→ Exportiert: Profildaten, Aktivitätslog, Kommentare, Käufe
→ Download als JSON oder PDF
```

### Löschung (Art. 17)
```
Admin → admin/data-deletion.php → User-ID eingeben
→ Bestätigung via Modal + CSRF-Token
→ Löscht: Profil, Metadaten, Sessions
→ Anonymisiert: Kommentare, Käufe (für Buchführung nötig)
→ Protokolliert: "Löschung auf Antrag" im Audit-Log
```

---

## Sicherheits-Checkliste User-Verwaltung

```
TÄGLICH:
[ ] Fehlgeschlagene Login-Versuche prüfen (Brute-Force?)
[ ] Neue Admin-Accounts überprüfen (wer hat Zugriff?)

WÖCHENTLICH:
[ ] Inaktive Accounts prüfen (> 90 Tage kein Login)
[ ] Bekannte Admin-E-Mail-Adressen nicht veröffentlicht?

SICHERHEITS-REGELN:
[ ] Keine generischen Usernames: admin, root, administrator
[ ] Admin-Passwort: > 16 Zeichen, Zeichenklassen-Mix
[ ] MFA nach Implementierung: Pflicht für Admin
[ ] Session-Timeout: Admin 8h, Member 30 Tage
[ ] Login-Logs aktiv: login_attempts-Tabelle befüllt?
```

---

## Referenzen

- [core/Auth.php](../../CMS/core/Auth.php) – Authentifizierung
- [core/Services/UserService.php](../../CMS/core/Services/UserService.php) – CRUD
- [admin/users.php](../../CMS/admin/users.php) – Admin-UI
- [admin/rbac.php](../../CMS/admin/rbac.php) – Rollen & Berechtigungen
- [member/SECURITY.md](../member/SECURITY.md) – Member-Sicherheit
