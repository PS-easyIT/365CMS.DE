# CMSv2 - API-Referenz

Vollständige API-Dokumentation aller Core-Klassen und Methoden.

## Core-Klassen

- [Bootstrap](#bootstrap)
- [Database](#database)
- [Security](#security)
- [Auth](#auth)
- [Router](#router)
- [Hooks](#hooks)
- [PluginManager](#pluginmanager)
- [ThemeManager](#thememanager)

---

## Bootstrap

**Namespace:** `CMS\Bootstrap`  
**Datei:** `core/Bootstrap.php`

### Beschreibung
Zentrale Bootstrap-Klasse, die das gesamte CMS initialisiert.

### Methoden

#### `instance(): self`
Gibt die Singleton-Instanz zurück.

**Return:** `Bootstrap` Instanz

**Beispiel:**
```php
$app = CMS\Bootstrap::instance();
```

#### `run(): void`
Startet das CMS und führt das Routing durch.

**Beispiel:**
```php
$app->run();
```

#### `db(): Database`
Gibt die Database-Instanz zurück.

**Return:** `Database` Instanz

**Beispiel:**
```php
$db = $app->db();
```

#### `auth(): Auth`
Gibt die Auth-Instanz zurück.

**Return:** `Auth` Instanz

#### `security(): Security`
Gibt die Security-Instanz zurück.

**Return:** `Security` Instanz

---

## Database

**Namespace:** `CMS\Database`  
**Datei:** `core/Database.php`

### Beschreibung
Datenbank-Abstraktionsschicht mit PDO.

### Methoden

#### `instance(): self`
Singleton-Instanz.

#### `prepare(string $sql): PDOStatement`
Erstellt ein Prepared Statement.

**Parameter:**
- `$sql` (string) - SQL-Query

**Return:** `PDOStatement`

**Beispiel:**
```php
$db = CMS\Database::instance();
$stmt = $db->prepare("SELECT * FROM {$db->prefix()}users WHERE id = ?");
$stmt->execute([$userId]);
```

#### `query(string $sql): PDOStatement`
Führt eine SQL-Query direkt aus.

**Parameter:**
- `$sql` (string) - SQL-Query

**Return:** `PDOStatement`

**Beispiel:**
```php
$stmt = $db->query("SELECT COUNT(*) FROM {$db->prefix()}users");
$count = $stmt->fetchColumn();
```

#### `insert(string $table, array $data): int`
Fügt Daten in eine Tabelle ein.

**Parameter:**
- `$table` (string) - Tabellenname (ohne Präfix)
- `$data` (array) - Assoziatives Array mit Spaltennamen und Werten

**Return:** `int` - Insert-ID

**Beispiel:**
```php
$userId = $db->insert('users', [
    'username' => 'john',
    'email' => 'john@example.com',
    'password' => password_hash('secret', PASSWORD_BCRYPT)
]);
```

#### `update(string $table, array $data, array $where): bool`
Aktualisiert Daten in einer Tabelle.

**Parameter:**
- `$table` (string) - Tabellenname
- `$data` (array) - Zu aktualisierende Daten
- `$where` (array) - WHERE-Bedingungen

**Return:** `bool` - Erfolg

**Beispiel:**
```php
$success = $db->update('users', 
    ['email' => 'newemail@example.com'],
    ['id' => $userId]
);
```

#### `delete(string $table, array $where): bool`
Löscht Daten aus einer Tabelle.

**Parameter:**
- `$table` (string) - Tabellenname
- `$where` (array) - WHERE-Bedingungen

**Return:** `bool` - Erfolg

**Beispiel:**
```php
$success = $db->delete('users', ['id' => $userId]);
```

#### `prefix(): string`
Gibt den Tabellen-Präfix zurück.

**Return:** `string` - Präfix (Standard: 'cms_')

**Beispiel:**
```php
$tableName = $db->prefix() . 'custom_table';
```

#### `getPdo(): PDO`
Gibt die PDO-Instanz zurück.

**Return:** `PDO`

---

## Security

**Namespace:** `CMS\Security`  
**Datei:** `core/Security.php`

### Beschreibung
Sicherheitsfunktionen: CSRF, XSS, Input-Sanitization.

### Methoden

#### `instance(): self`
Singleton-Instanz.

#### `init(): void`
Initialisiert Sicherheitsmaßnahmen (Headers, Session).

#### `generateToken(string $action = 'default'): string`
Generiert ein CSRF-Token.

**Parameter:**
- `$action` (string) - Eindeutiger Action-Name

**Return:** `string` - Token

**Beispiel:**
```php
$token = $security->generateToken('login_form');
echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
```

#### `verifyToken(string $token, string $action = 'default'): bool`
Verifiziert ein CSRF-Token.

**Parameter:**
- `$token` (string) - Zu überprüfendes Token
- `$action` (string) - Action-Name

**Return:** `bool` - Gültig?

**Beispiel:**
```php
if (!$security->verifyToken($_POST['csrf_token'], 'login_form')) {
    die('CSRF check failed');
}
```

#### `sanitize(string $input, string $type = 'text'): string`
Säubert Input-Daten.

**Parameter:**
- `$input` (string) - Zu säubernder Input
- `$type` (string) - Typ: 'text', 'email', 'url', 'int', 'html'

**Return:** `string` - Gesäuberter Wert

**Beispiel:**
```php
$clean = $security->sanitize($_POST['name'], 'text');
$email = $security->sanitize($_POST['email'], 'email');
$html = $security->sanitize($_POST['content'], 'html');
```

#### `escape(string $output): string`
Escaped Output für HTML.

**Parameter:**
- `$output` (string) - Auszugebender Text

**Return:** `string` - Escaped Text

**Beispiel:**
```php
echo $security->escape($userInput);
```

#### `validateEmail(string $email): bool`
Validiert E-Mail-Adresse.

**Parameter:**
- `$email` (string) - E-Mail-Adresse

**Return:** `bool` - Gültig?

#### `validateUrl(string $url): bool`
Validiert URL.

**Parameter:**
- `$url` (string) - URL

**Return:** `bool` - Gültig?

#### `hashPassword(string $password): string`
Hasht ein Passwort (BCrypt, Cost 12).

**Parameter:**
- `$password` (string) - Klartext-Passwort

**Return:** `string` - Gehashtes Passwort

**Beispiel:**
```php
$hash = $security->hashPassword('userpassword');
```

#### `verifyPassword(string $password, string $hash): bool`
Verifiziert ein Passwort gegen einen Hash.

**Parameter:**
- `$password` (string) - Klartext-Passwort
- `$hash` (string) - Gespeicherter Hash

**Return:** `bool` - Korrekt?

**Beispiel:**
```php
if ($security->verifyPassword($inputPassword, $storedHash)) {
    // Login successful
}
```

#### `checkRateLimit(string $identifier, int $maxAttempts = 5, int $timeWindow = 300): bool`
Prüft Rate-Limiting.

**Parameter:**
- `$identifier` (string) - Eindeutiger Identifier (z.B. IP + Action)
- `$maxAttempts` (int) - Max. Versuche
- `$timeWindow` (int) - Zeitfenster in Sekunden

**Return:** `bool` - Erlaubt?

**Beispiel:**
```php
if (!$security->checkRateLimit('login_' . $ip, 5, 300)) {
    die('Too many attempts');
}
```

#### `getClientIp(): string`
Gibt die Client-IP zurück.

**Return:** `string` - IP-Adresse

---

## Auth

**Namespace:** `CMS\Auth`  
**Datei:** `core/Auth.php`

### Beschreibung
Authentifizierungs- und Benutzerverwaltung.

### Methoden

#### `instance(): self`
Singleton-Instanz.

#### `login(string $username, string $password): bool|string`
Meldet einen Benutzer an.

**Parameter:**
- `$username` (string) - Username oder E-Mail
- `$password` (string) - Passwort

**Return:** `bool|string` - `true` bei Erfolg, Fehlermeldung bei Fehler

**Beispiel:**
```php
$result = $auth->login($_POST['username'], $_POST['password']);
if ($result === true) {
    redirect('/member');
} else {
    echo $result; // Fehlermeldung
}
```

#### `register(array $data): bool|string`
Registriert einen neuen Benutzer.

**Parameter:**
- `$data` (array) - User-Daten (username, email, password, display_name)

**Return:** `bool|string` - `true` bei Erfolg, Fehlermeldung bei Fehler

**Beispiel:**
```php
$result = $auth->register([
    'username' => 'john',
    'email' => 'john@example.com',
    'password' => 'secret123',
    'display_name' => 'John Doe'
]);
```

#### `logout(): void`
Meldet den aktuellen Benutzer ab.

**Beispiel:**
```php
$auth->logout();
```

#### `isLoggedIn(): bool`
Prüft, ob ein User eingeloggt ist.

**Return:** `bool`

**Beispiel:**
```php
if ($auth->isLoggedIn()) {
    // User ist eingeloggt
}
```

#### `currentUser(): ?object`
Gibt den aktuellen User zurück.

**Return:** `object|null` - User-Objekt oder null

**Beispiel:**
```php
$user = $auth->currentUser();
if ($user) {
    echo $user->display_name;
    echo $user->email;
}
```

#### `hasRole(string $role): bool`
Prüft ob User eine bestimmte Rolle hat.

**Parameter:**
- `$role` (string) - Rollenname

**Return:** `bool`

**Beispiel:**
```php
if ($auth->hasRole('admin')) {
    // User ist Admin
}
```

#### `isAdmin(): bool`
Prüft ob User Admin ist.

**Return:** `bool`

**Beispiel:**
```php
if ($auth->isAdmin()) {
    // Admin-Funktionen
}
```

---

## Router

**Namespace:** `CMS\Router`  
**Datei:** `core/Router.php`

### Beschreibung
URL-Routing und Request-Handling.

### Methoden

#### `instance(): self`
Singleton-Instanz.

#### `addRoute(string $method, string $path, callable $callback): void`
Fügt eine neue Route hinzu.

**Parameter:**
- `$method` (string) - HTTP-Methode ('GET', 'POST', etc.)
- `$path` (string) - URL-Pfad (kann :parameter enthalten)
- `$callback` (callable) - Callback-Funktion

**Beispiel:**
```php
$router->addRoute('GET', '/custom-page', function() {
    echo '<h1>Custom Page</h1>';
});

$router->addRoute('GET', '/user/:id', function($id) {
    echo '<h1>User ' . $id . '</h1>';
});
```

#### `dispatch(): void`
Verarbeitet die aktuelle Anfrage.

#### `redirect(string $url): void`
Leitet zu einer URL weiter.

**Parameter:**
- `$url` (string) - Ziel-URL (relativ oder absolut)

**Beispiel:**
```php
$router->redirect('/member');
$router->redirect('https://example.com');
```

---

## Hooks

**Namespace:** `CMS\Hooks`  
**Datei:** `core/Hooks.php`

### Beschreibung
WordPress-ähnliches Hook-System für Actions & Filters.

### Statische Methoden

#### `addAction(string $tag, callable $callback, int $priority = 10): void`
Registriert eine Action.

**Parameter:**
- `$tag` (string) - Hook-Name
- `$callback` (callable) - Callback-Funktion
- `$priority` (int) - Priorität (niedrig = früh)

**Beispiel:**
```php
CMS\Hooks::addAction('cms_init', function() {
    // Code beim System-Start
}, 10);
```

#### `doAction(string $tag, ...$args): void`
Führt alle an einen Hook registrierten Actions aus.

**Parameter:**
- `$tag` (string) - Hook-Name
- `$args` (mixed) - Beliebige Parameter

**Beispiel:**
```php
CMS\Hooks::doAction('user_registered', $userId);
```

#### `addFilter(string $tag, callable $callback, int $priority = 10): void`
Registriert einen Filter.

**Parameter:**
- `$tag` (string) - Filter-Name
- `$callback` (callable) - Callback-Funktion (muss Wert zurückgeben!)
- `$priority` (int) - Priorität

**Beispiel:**
```php
CMS\Hooks::addFilter('template_name', function($template) {
    return $template === 'old' ? 'new' : $template;
}, 10);
```

#### `applyFilters(string $tag, $value, ...$args): mixed`
Wendet alle Filter auf einen Wert an.

**Parameter:**
- `$tag` (string) - Filter-Name
- `$value` (mixed) - Zu filternder Wert
- `$args` (mixed) - Zusätzliche Parameter

**Return:** `mixed` - Gefilterter Wert

**Beispiel:**
```php
$template = CMS\Hooks::applyFilters('template_name', 'home');
```

#### `removeAction(string $tag, callable $callback, int $priority = 10): bool`
Entfernt eine Action.

**Return:** `bool` - Erfolg

#### `removeFilter(string $tag, callable $callback, int $priority = 10): bool`
Entfernt einen Filter.

**Return:** `bool` - Erfolg

---

## PluginManager

**Namespace:** `CMS\PluginManager`  
**Datei:** `core/PluginManager.php`

### Beschreibung
Verwaltung von Plugins.

### Methoden

#### `instance(): self`
Singleton-Instanz.

#### `loadPlugins(): void`
Lädt alle aktiven Plugins.

#### `getAvailablePlugins(): array`
Gibt alle verfügbaren Plugins zurück.

**Return:** `array` - Plugin-Array mit Metadaten

**Beispiel:**
```php
$plugins = $manager->getAvailablePlugins();
foreach ($plugins as $plugin) {
    echo $plugin['name'];
    echo $plugin['version'];
    echo $plugin['active'] ? 'Aktiv' : 'Inaktiv';
}
```

#### `activatePlugin(string $plugin): bool|string`
Aktiviert ein Plugin.

**Parameter:**
- `$plugin` (string) - Plugin-Verzeichnisname

**Return:** `bool|string` - `true` oder Fehlermeldung

**Beispiel:**
```php
$result = $manager->activatePlugin('my-plugin');
```

#### `deactivatePlugin(string $plugin): bool|string`
Deaktiviert ein Plugin.

**Parameter:**
- `$plugin` (string) - Plugin-Verzeichnisname

**Return:** `bool|string` - `true` oder Fehlermeldung

---

## ThemeManager

**Namespace:** `CMS\ThemeManager`  
**Datei:** `core/ThemeManager.php`

### Beschreibung
Verwaltung von Themes.

### Methoden

#### `instance(): self`
Singleton-Instanz.

#### `loadTheme(): void`
Lädt das aktive Theme.

#### `render(string $template, array $data = []): void`
Rendert ein Template.

**Parameter:**
- `$template` (string) - Template-Name (ohne .php)
- `$data` (array) - Daten für Template

**Beispiel:**
```php
$theme->render('home', ['title' => 'Welcome']);
```

#### `getHeader(): void`
Inkludiert header.php.

#### `getFooter(): void`
Inkludiert footer.php.

#### `getAvailableThemes(): array`
Gibt alle verfügbaren Themes zurück.

**Return:** `array` - Theme-Array mit Metadaten

**Beispiel:**
```php
$themes = $manager->getAvailableThemes();
```

#### `switchTheme(string $theme): bool|string`
Wechselt das aktive Theme.

**Parameter:**
- `$theme` (string) - Theme-Verzeichnisname

**Return:** `bool|string` - `true` oder Fehlermeldung

**Beispiel:**
```php
$result = $manager->switchTheme('new-theme');
```

#### `getThemePath(): string`
Gibt den absoluten Theme-Pfad zurück.

**Return:** `string` - Dateisystem-Pfad

#### `getThemeUrl(): string`
Gibt die Theme-URL zurück.

**Return:** `string` - HTTP-URL

**Beispiel:**
```php
$url = $manager->getThemeUrl();
echo '<link rel="stylesheet" href="' . $url . '/style.css">';
```

---

## Helper-Funktionen

**Datei:** `includes/functions.php`

### Escaping

- `esc_html(string $text): string` - HTML-Escape
- `esc_url(string $url): string` - URL-Escape
- `esc_attr(string $text): string` - Attribut-Escape

### Sanitization

- `sanitize_text(string $text): string` - Text säubern
- `sanitize_email(string $email): string` - E-Mail validieren

### Options

- `get_option(string $key, $default = null): mixed` - Option abrufen
- `update_option(string $key, $value): bool` - Option speichern

### Auth-Helpers

- `is_logged_in(): bool` - User eingeloggt?
- `is_admin(): bool` - User ist Admin?
- `current_user(): ?object` - Aktueller User

### Utilities

- `redirect(string $url): void` - Weiterleitung
- `format_date(string $date, string $format = 'd.m.Y'): string` - Datum formatieren
- `time_ago(string $datetime): string` - "vor X Minuten"
- `dd(...$vars): void` - Debug & Die
- `generate_random_string(int $length = 32): string` - Zufallsstring
