<?php
declare(strict_types=1);
/**
 * Helper Functions
 * 
 * Global utility functions
 * 
 * @package CMSv2\Includes
 */


if (!defined('ABSPATH')) {
    exit;
}

if (defined('CMS_GLOBAL_FUNCTIONS_LOADED')) {
    return;
}

define('CMS_GLOBAL_FUNCTIONS_LOADED', true);

/**
 * Escape HTML output
 */
function esc_html(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape URL – sanitiert und blockiert unsichere Schemes (javascript:, data:, vbscript:)
 */
function esc_url(string $url): string {
    $url = trim($url);
    // Blockiere unsichere Schemes
    $stripped = strtolower(preg_replace('/[\x00-\x1f\s]/', '', $url));
    if (preg_match('/^(javascript|data|vbscript|file):/i', $stripped)) {
        return '';
    }
    return filter_var($url, FILTER_SANITIZE_URL);
}

/**
 * Escape attribute
 */
function esc_attr(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Echo escaped HTML
 */
function esc_html_e(string $text, string $domain = 'default'): void {
    echo htmlspecialchars(__($text, $domain), ENT_QUOTES, 'UTF-8');
}

/**
 * Echo escaped attribute
 */
function esc_attr_e(string $text, string $domain = 'default'): void {
    echo htmlspecialchars(__($text, $domain), ENT_QUOTES, 'UTF-8');
}

/**
 * Escape for textarea content
 */
function esc_textarea(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape for inline JavaScript
 */
function esc_js(string $text): string {
    return addslashes(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
}

/**
 * WordPress-style date formatting with timezone support
 */
function wp_date(string $format, ?int $timestamp = null, mixed $timezone = null): string|false {
    $timestamp = $timestamp ?? time();
    return date($format, $timestamp);
}

/**
 * Format a number with locale-aware thousands separator
 */
function number_format_i18n(float $number, int $decimals = 0): string {
    return number_format($number, $decimals, ',', '.');
}

/**
 * Generate pagination links (minimal WP-compatible implementation)
 */
function paginate_links(array $args = []): string
{
    $defaults = [
        'base'      => '%_%',
        'format'    => '?paged=%#%',
        'current'   => 1,
        'total'     => 1,
        'prev_text' => '&laquo;',
        'next_text' => '&raquo;',
        'type'      => 'plain',
    ];
    $args = array_merge($defaults, $args);

    $current = (int) $args['current'];
    $total   = (int) $args['total'];

    if ($total <= 1) {
        return '';
    }

    $output = [];

    // Build URL for a given page number
    $page_url = function (int $page) use ($args): string {
        return str_replace(
            ['%_%', '%#%'],
            [str_replace('%#%', (string) $page, $args['format']), (string) $page],
            $args['base']
        );
    };

    if ($current > 1) {
        $output[] = '<a class="prev page-numbers" href="' . esc_url($page_url($current - 1)) . '">' . $args['prev_text'] . '</a>';
    }

    for ($i = 1; $i <= $total; $i++) {
        if ($i === $current) {
            $output[] = '<span aria-current="page" class="page-numbers current">' . $i . '</span>';
        } else {
            $output[] = '<a class="page-numbers" href="' . esc_url($page_url($i)) . '">' . $i . '</a>';
        }
    }

    if ($current < $total) {
        $output[] = '<a class="next page-numbers" href="' . esc_url($page_url($current + 1)) . '">' . $args['next_text'] . '</a>';
    }

    return implode("\n", $output);
}

/**
 * Sanitize text field
 */
function sanitize_text(string $text): string {
    return strip_tags(trim($text));
}

/**
 * Sanitize text field (WP compatible)
 */
function sanitize_text_field($str): string {
    if (is_array($str)) {
        return '';
    }
    return strip_tags(trim((string)$str));
}

/**
 * Sanitize email
 */
function sanitize_email(string $email): string {
    return filter_var($email, FILTER_SANITIZE_EMAIL);
}

/**
 * Get site option
 */
function get_option(string $key, $default = null) {
    static $options = null;
    
    if ($options === null) {
        $db = CMS\Database::instance();
        $stmt = $db->query("SELECT option_name, option_value FROM {$db->prefix()}settings WHERE autoload = 1");
        $results = $stmt->fetchAll();
        
        $options = [];
        foreach ($results as $row) {
            $options[$row->option_name] = $row->option_value;
        }
    }
    
    return $options[$key] ?? $default;
}

/**
 * Liefert den aktuell konfigurierten Website-Namen aus den Settings.
 */
function cms_get_site_name(): string {
    static $siteName = null;

    if ($siteName !== null) {
        return $siteName;
    }

    $fallback = defined('SITE_NAME') ? SITE_NAME : '365CMS';

    try {
        $db = CMS\Database::instance();
        $value = $db->get_var(
            "SELECT option_value FROM {$db->prefix()}settings WHERE option_name IN ('site_name', 'site_title') ORDER BY FIELD(option_name, 'site_name', 'site_title') LIMIT 1"
        );

        $siteName = is_string($value) && trim($value) !== '' ? trim($value) : $fallback;
    } catch (\Throwable) {
        $siteName = $fallback;
    }

    return $siteName;
}

/**
 * Liefert den aktuellen Website-Titel für Browsertabs/Meta.
 */
function cms_get_site_title(): string {
    return cms_get_site_name();
}

/**
 * Liefert die Basis-URL des Asset-Verzeichnisses oder einen Asset-Unterpfad.
 */
function cms_assets_url(string $path = ''): string {
    $baseUrl = defined('ASSETS_URL')
        ? (string) ASSETS_URL
        : rtrim((defined('SITE_URL') ? (string) SITE_URL : ''), '/') . '/assets';

    $baseUrl = rtrim(str_replace('\\', '/', $baseUrl), '/');

    if ($path === '') {
        return $baseUrl;
    }

    return $baseUrl . '/' . ltrim(str_replace('\\', '/', $path), '/');
}

/**
 * Liefert den Dateisystempfad des Asset-Verzeichnisses oder eines Asset-Unterpfads.
 */
function cms_assets_path(string $path = ''): string {
    $basePath = defined('ASSETS_PATH')
        ? (string) ASSETS_PATH
        : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;

    $basePath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $basePath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if ($path === '') {
        return $basePath;
    }

    return $basePath . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
}

/**
 * Liefert die Asset-Version auf Basis des Dateistands oder einen definierten Fallback.
 */
function cms_asset_version(string $relativePath, string|int|null $fallback = ''): string {
    $assetPath = cms_assets_path($relativePath);

    if (is_file($assetPath)) {
        $modified = filemtime($assetPath);
        if ($modified !== false) {
            return (string) $modified;
        }
    }

    return $fallback === null ? '' : (string) $fallback;
}

/**
 * Liefert eine Asset-URL optional mit zentraler Versionierung.
 */
function cms_asset_url(string $relativePath, bool $withVersion = true, string|int|null $fallbackVersion = ''): string {
    $url = cms_assets_url($relativePath);

    if (!$withVersion) {
        return $url;
    }

    $version = cms_asset_version($relativePath, $fallbackVersion);

    return $version !== '' ? $url . '?v=' . rawurlencode($version) : $url;
}

/**
 * Update site option
 */
function update_option(string $key, $value): bool {
    $db = CMS\Database::instance();
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM {$db->prefix()}settings WHERE option_name = ?");
    $stmt->execute([$key]);
    
    $jsonValue = is_array($value) ? json_encode($value) : (string) $value;
    
    if ($stmt->fetch()->count > 0) {
        return $db->update('settings', ['option_value' => $jsonValue], ['option_name' => $key]);
    } else {
        return $db->insert('settings', [
            'option_name' => $key,
            'option_value' => $jsonValue,
            'autoload' => 1
        ]) > 0;
    }
}

/**
 * Redirect helper
 */
function redirect(string $url): void {
    if (strpos($url, 'http') !== 0) {
        $url = SITE_URL . $url;
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Check if user is logged in
 */
function is_logged_in(): bool {
    return CMS\Auth::instance()->isLoggedIn();
}

/**
 * Output checkbox attribute
 */
function checked($checked, $current = true, bool $echo = true): string {
    return __checked_selected_helper($checked, $current, $echo, 'checked');
}

/**
 * Output selected attribute
 */
function selected($selected, $current = true, bool $echo = true): string {
    return __checked_selected_helper($selected, $current, $echo, 'selected');
}

/**
 * Helper for checked/selected
 */
function __checked_selected_helper($helper, $current, bool $echo, string $type): string {
    if ((string) $helper === (string) $current) {
        $result = " $type='$type'";
        if ($echo) {
            echo $result;
        }
        return $result;
    }
    return '';
}

/**
 * Output disabled attribute
 */
function disabled($disabled, $current = true, bool $echo = true): string {
    return __checked_selected_helper($disabled, $current, $echo, 'disabled');
}

/**
 * Output a hidden nonce input field (WP-compatible)
 */
function wp_nonce_field(string|int $action = -1, string $name = '_wpnonce', bool $referer = true, bool $echo = true): string
{
    $nonce  = wp_create_nonce($action);
    $field  = '<input type="hidden" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr($nonce) . '" />';
    if ($referer) {
        $field .= '<input type="hidden" name="_wp_http_referer" value="' . esc_attr($_SERVER['REQUEST_URI'] ?? '') . '" />';
    }
    if ($echo) {
        echo $field;
    }
    return $field;
}

/**
 * Check if current user is admin
 */
function is_admin(): bool {
    return CMS\Auth::instance()->isAdmin();
}

/**
 * Get current user
 */
function current_user(): ?object {
    return CMS\Auth::instance()->currentUser();
}

/**
 * Format date
 */
function format_date(string $date, string $format = 'd.m.Y'): string {
    return date($format, strtotime($date));
}

/**
 * Time ago helper — human-readable relative timestamps
 *
 * Uses Carbon when available (CMS/assets/Carbon), falls back to native PHP.
 */
function time_ago(string|int $datetime): string {
    if (is_string($datetime)) {
        $timestamp = strtotime($datetime);
    } else {
        $timestamp = $datetime;
    }

    if ($timestamp === false || $timestamp <= 0) {
        return '-';
    }

    // Try Carbon if loaded via CMS/assets/autoload.php
    if (class_exists('Carbon\\Carbon')) {
        try {
            return \Carbon\Carbon::createFromTimestamp($timestamp)->locale('de')->diffForHumans();
        } catch (\Throwable $e) {
            // Fall through to native implementation
        }
    }

    $diff = time() - $timestamp;

    if ($diff < 0) {
        return 'In der Zukunft';
    } elseif ($diff < 60) {
        return 'Gerade eben';
    } elseif ($diff < 3600) {
        $mins = (int)floor($diff / 60);
        return 'vor ' . $mins . ' Minute' . ($mins > 1 ? 'n' : '');
    } elseif ($diff < 86400) {
        $hours = (int)floor($diff / 3600);
        return 'vor ' . $hours . ' Stunde' . ($hours > 1 ? 'n' : '');
    } elseif ($diff < 604800) {
        $days = (int)floor($diff / 86400);
        if ($days === 1) return 'Gestern';
        return 'vor ' . $days . ' Tagen';
    } elseif ($diff < 2592000) {
        $weeks = (int)floor($diff / 604800);
        return 'vor ' . $weeks . ' Woche' . ($weeks > 1 ? 'n' : '');
    } elseif ($diff < 31536000) {
        $months = (int)floor($diff / 2592000);
        return 'vor ' . $months . ' Monat' . ($months > 1 ? 'en' : '');
    } else {
        $years = (int)floor($diff / 31536000);
        return 'vor ' . $years . ' Jahr' . ($years > 1 ? 'en' : '');
    }
}

/**
 * Debug helper
 */
function dd(...$vars): void {
    if (!CMS_DEBUG) {
        return;
    }
    
    echo '<pre style="background: #1e293b; color: #e2e8f0; padding: 1rem; border-radius: 8px; margin: 1rem;">';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    exit;
}

/**
 * Global logging helper — delegates to CMS\Logger
 *
 * @param string $level   PSR-3 level (debug|info|notice|warning|error|critical|alert|emergency)
 * @param string $message Log message ({key} placeholders replaced from $context)
 * @param array  $context Context data
 */
function cms_log(string $level, string $message, array $context = []): void
{
    \CMS\Logger::instance()->log($level, $message, $context);
}

/**
 * Generate random string
 */
function generate_random_string(int $length = 32): string {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Check if current user has capability
 */
function current_user_can(string $capability): bool {
    // Check if Auth class is available
    if (!class_exists('CMS\Auth')) {
        return false;
    }

    // Check if user is logged in
    if (!CMS\Auth::isLoggedIn()) {
        return false;
    }

    // Check Admin via static method
    if (CMS\Auth::isAdmin()) {
        return true;
    }
    
    // Check Capability via instance method
    if (method_exists(CMS\Auth::instance(), 'hasCapability')) {
        return CMS\Auth::instance()->hasCapability($capability);
    }
    
    return false;
}

if (!class_exists('CMS_Role_Stub')) {
    class CMS_Role_Stub {
        public string $name;
        public array $capabilities = [];
        public string $display_name;

        public function __construct(string $name, array $caps = [], ?string $displayName = null) {
            $this->name = $name;
            $this->capabilities = $caps;
            $this->display_name = $displayName ?? cms_humanize_role_slug($name);
        }

        public function add_cap($cap, $grant = true): void {
            $capability = cms_normalize_role_capability((string) $cap);
            if ($capability === '') {
                return;
            }

            $this->capabilities[$capability] = (bool) $grant;
            cms_store_role_capability($this->name, $capability, (bool) $grant);
        }

        public function remove_cap($cap): void {
            $capability = cms_normalize_role_capability((string) $cap);
            if ($capability === '') {
                return;
            }

            $this->capabilities[$capability] = false;
            cms_store_role_capability($this->name, $capability, false);
        }

        public function has_cap($cap): bool {
            $capability = cms_normalize_role_capability((string) $cap);
            return $capability !== '' && !empty($this->capabilities[$capability]);
        }
    }
}

/**
 * WordPress-Kompatibilitäts-Registry für Rollen-Metadaten.
 */
function cms_get_wp_role_registry(): array {
    $raw = get_option('wp_compat_role_registry', '');
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = \CMS\Json::decodeArray($raw, []);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Persistiert die WP-Kompatibilitäts-Registry.
 */
function cms_save_wp_role_registry(array $registry): bool {
    return update_option('wp_compat_role_registry', $registry);
}

/**
 * Alias-/Slug-Normalisierung für Rollen.
 */
function cms_normalize_role_slug(string $role): string {
    $role = strtolower(trim($role));
    if ($role === 'administrator') {
        $role = 'admin';
    }

    $role = preg_replace('/[^a-z0-9_-]+/', '-', $role) ?? '';
    return trim($role, '-_');
}

/**
 * Normalisiert Capability-Namen für die Kompatibilitätsschicht.
 */
function cms_normalize_role_capability(string $capability): string {
    $capability = strtolower(trim($capability));
    $capability = str_replace(['\\', '/', ':'], '.', $capability);
    $capability = preg_replace('/\s+/', '.', $capability) ?? '';
    $capability = preg_replace('/[^a-z0-9._-]+/', '-', $capability) ?? '';
    $capability = preg_replace('/\.{2,}/', '.', $capability) ?? '';
    return trim($capability, '.-_');
}

/**
 * Menschlich lesbares Rollenlabel.
 */
function cms_humanize_role_slug(string $role): string {
    $role = str_replace(['_', '-', '.'], ' ', cms_normalize_role_slug($role));
    return ucwords(trim($role));
}

/**
 * Default-Rollen der CMS-/WP-Kompatibilität.
 */
function cms_get_default_role_definitions(): array {
    return [
        'admin' => [
            'display_name' => 'Administrator',
            'capabilities' => [
                'read' => true,
                'edit_profile' => true,
                'view_content' => true,
                'pages.view' => true,
                'pages.create' => true,
                'pages.edit' => true,
                'pages.delete' => true,
                'pages.publish' => true,
                'posts.view' => true,
                'posts.create' => true,
                'posts.edit' => true,
                'posts.delete' => true,
                'posts.publish' => true,
                'media.view' => true,
                'media.upload' => true,
                'media.delete' => true,
                'media.settings' => true,
                'users.view' => true,
                'users.create' => true,
                'users.edit' => true,
                'users.delete' => true,
                'users.roles' => true,
                'themes.view' => true,
                'themes.activate' => true,
                'themes.customize' => true,
                'themes.install' => true,
                'plugins.view' => true,
                'plugins.activate' => true,
                'plugins.install' => true,
                'plugins.settings' => true,
                'settings.view' => true,
                'settings.edit' => true,
                'settings.system' => true,
                'comments.view' => true,
                'comments.moderate' => true,
                'comments.delete' => true,
            ],
        ],
        'editor' => [
            'display_name' => 'Editor',
            'capabilities' => [
                'read' => true,
                'edit_profile' => true,
                'view_content' => true,
                'pages.view' => true,
                'pages.create' => true,
                'pages.edit' => true,
                'pages.delete' => true,
                'pages.publish' => true,
                'posts.view' => true,
                'posts.create' => true,
                'posts.edit' => true,
                'posts.delete' => true,
                'posts.publish' => true,
                'media.view' => true,
                'media.upload' => true,
                'media.delete' => true,
                'media.settings' => true,
                'comments.view' => true,
                'comments.moderate' => true,
                'comments.delete' => true,
            ],
        ],
        'author' => [
            'display_name' => 'Autor',
            'capabilities' => [
                'read' => true,
                'edit_profile' => true,
                'view_content' => true,
                'pages.view' => true,
                'pages.create' => true,
                'pages.edit' => true,
                'posts.view' => true,
                'posts.create' => true,
                'posts.edit' => true,
                'media.view' => true,
                'media.upload' => true,
                'comments.view' => true,
            ],
        ],
        'member' => [
            'display_name' => 'Mitglied',
            'capabilities' => [
                'read' => true,
                'edit_profile' => true,
                'view_content' => true,
                'pages.view' => true,
                'posts.view' => true,
                'media.view' => true,
                'comments.view' => true,
            ],
        ],
        'subscriber' => [
            'display_name' => 'Subscriber',
            'capabilities' => [
                'read' => true,
            ],
        ],
        'contributor' => [
            'display_name' => 'Contributor',
            'capabilities' => [
                'read' => true,
                'posts.view' => true,
                'posts.create' => true,
                'posts.edit' => true,
            ],
        ],
    ];
}

/**
 * Stellt sicher, dass die Rollen-Tabelle für persistente Caps existiert.
 */
function cms_ensure_role_permissions_table(): void {
    $db = \CMS\Database::instance();
    $db->getPdo()->exec("CREATE TABLE IF NOT EXISTS {$db->prefix()}role_permissions (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        role       VARCHAR(50) NOT NULL,
        capability VARCHAR(100) NOT NULL,
        granted    TINYINT(1) NOT NULL DEFAULT 0,
        UNIQUE KEY uk_role_cap (role, capability)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Lädt die Capability-Matrix einer Rolle aus DB + Default-Fallbacks.
 */
function cms_load_role_capabilities(string $role): array {
    $role = cms_normalize_role_slug($role);
    if ($role === '') {
        return [];
    }

    $definitions = cms_get_default_role_definitions();
    $capabilities = $definitions[$role]['capabilities'] ?? [];

    try {
        cms_ensure_role_permissions_table();
        $db = \CMS\Database::instance();
        $rows = $db->get_results(
            "SELECT capability, granted FROM {$db->prefix()}role_permissions WHERE role = ?",
            [$role]
        ) ?: [];

        foreach ($rows as $row) {
            $capability = cms_normalize_role_capability((string) ($row->capability ?? ''));
            if ($capability === '') {
                continue;
            }

            $capabilities[$capability] = !empty($row->granted);
        }
    } catch (\Throwable) {
    }

    return $capabilities;
}

/**
 * Prüft, ob die Rolle im CMS bekannt ist.
 */
function cms_role_exists(string $role): bool {
    $role = cms_normalize_role_slug($role);
    if ($role === '') {
        return false;
    }

    $definitions = cms_get_default_role_definitions();
    if (isset($definitions[$role])) {
        return true;
    }

    $registry = cms_get_wp_role_registry();
    if (isset($registry[$role])) {
        return true;
    }

    try {
        $db = \CMS\Database::instance();

        $dbRole = $db->get_var(
            "SELECT role FROM {$db->prefix()}role_permissions WHERE role = ? LIMIT 1",
            [$role]
        );
        if (is_string($dbRole) && $dbRole !== '') {
            return true;
        }

        $userRole = $db->get_var(
            "SELECT role FROM {$db->prefix()}users WHERE role = ? LIMIT 1",
            [$role]
        );
        return is_string($userRole) && $userRole !== '';
    } catch (\Throwable) {
        return false;
    }
}

/**
 * Persistiert eine einzelne Capability.
 */
function cms_store_role_capability(string $role, string $capability, bool $granted): void {
    $role = cms_normalize_role_slug($role);
    $capability = cms_normalize_role_capability($capability);

    if ($role === '' || $capability === '') {
        return;
    }

    try {
        cms_ensure_role_permissions_table();
        $db = \CMS\Database::instance();
        $db->execute(
            "INSERT INTO {$db->prefix()}role_permissions (role, capability, granted)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE granted = VALUES(granted)",
            [$role, $capability, $granted ? 1 : 0]
        );
    } catch (\Throwable) {
    }
}

/**
 * Überschreibt die Capability-Matrix einer Rolle.
 */
function cms_replace_role_capabilities(string $role, array $capabilities): void {
    $role = cms_normalize_role_slug($role);
    if ($role === '') {
        return;
    }

    try {
        cms_ensure_role_permissions_table();
        $db = \CMS\Database::instance();
        $db->execute(
            "DELETE FROM {$db->prefix()}role_permissions WHERE role = ?",
            [$role]
        );

        foreach ($capabilities as $capability => $granted) {
            cms_store_role_capability($role, (string) $capability, (bool) $granted);
        }
    } catch (\Throwable) {
    }
}

/**
 * Get role object (persistente WP-Kompatibilitätsschicht).
 */
function get_role(string $role): ?object {
    $role = cms_normalize_role_slug($role);
    if ($role === '' || !cms_role_exists($role)) {
        return null;
    }

    $definitions = cms_get_default_role_definitions();
    $registry = cms_get_wp_role_registry();
    $displayName = (string) ($registry[$role]['display_name'] ?? $definitions[$role]['display_name'] ?? cms_humanize_role_slug($role));

    return new CMS_Role_Stub($role, cms_load_role_capabilities($role), $displayName);
}

/**
 * Add role (persistiert Metadaten + Capabilities)
 */
function add_role(string $role, string $display_name, array $capabilities = []): ?object {
    $role = cms_normalize_role_slug($role);
    if ($role === '') {
        return null;
    }

    $normalizedCaps = [];
    foreach ($capabilities as $capability => $granted) {
        $capability = cms_normalize_role_capability((string) $capability);
        if ($capability === '') {
            continue;
        }

        $normalizedCaps[$capability] = (bool) $granted;
    }

    $registry = cms_get_wp_role_registry();
    $registry[$role] = [
        'display_name' => trim($display_name) !== '' ? trim($display_name) : cms_humanize_role_slug($role),
        'managed_by' => 'wp_compat',
    ];
    cms_save_wp_role_registry($registry);
    cms_replace_role_capabilities($role, $normalizedCaps);

    return new CMS_Role_Stub($role, cms_load_role_capabilities($role), $registry[$role]['display_name']);
}

/**
 * Remove role (persistiert Löschung in Registry + Role-Matrix)
 */
function remove_role(string $role): void {
    $role = cms_normalize_role_slug($role);
    if ($role === '' || in_array($role, ['admin', 'editor', 'author', 'member'], true)) {
        return;
    }

    $registry = cms_get_wp_role_registry();
    if (isset($registry[$role])) {
        unset($registry[$role]);
        cms_save_wp_role_registry($registry);
    }

    try {
        cms_ensure_role_permissions_table();
        $db = \CMS\Database::instance();
        $db->execute(
            "DELETE FROM {$db->prefix()}role_permissions WHERE role = ?",
            [$role]
        );
    } catch (\Throwable) {
    }
}

/**
 * Admin Menu Wrappers
 */

global $cms_admin_menu;
if (!isset($cms_admin_menu)) {
    $cms_admin_menu = [];
}

/**
 * Add a top-level menu page.
 * 
 * @param string $page_title The text to be displayed in the title tags of the page when the menu is selected.
 * @param string $menu_title The text to be used for the menu.
 * @param string $capability The capability required for this menu to be displayed to the user.
 * @param string $menu_slug  The slug name to refer to this menu by.
 * @param callable $function The function to be called to output the content for this page.
 * @param string $icon_url   The URL to the icon to be used for this menu.
 * @param int|null $position The position in the menu order this one should appear.
 */
function add_menu_page(string $page_title, string $menu_title, string $capability, string $menu_slug, $function = '', string $icon_url = '', ?int $position = null, bool $hidden = false): void {
    global $cms_admin_menu;
    
    // Check capability
    if (function_exists('current_user_can') && !current_user_can($capability)) {
        return;
    }

    $menu_item = [
        'type'       => 'plugin_page',
        'page_title' => $page_title,
        'menu_title' => $menu_title,
        'capability' => $capability,
        'menu_slug'  => $menu_slug,
        'callable'   => $function,
        'icon_url'   => $icon_url,
        'position'   => $position,
        'hidden'     => $hidden,   // true = nur Routing, kein Sidebar-Eintrag
        'children'   => []
    ];

    if ($position !== null) {
        $cms_admin_menu[$position] = $menu_item;
    } else {
        $cms_admin_menu[] = $menu_item;
    }
}

/**
 * Add a submenu page.
 * 
 * @param string $parent_slug The slug name for the parent menu.
 * @param string $page_title  The text to be displayed in the title tags of the page when the menu is selected.
 * @param string $menu_title  The text to be used for the menu.
 * @param string $capability  The capability required for this menu to be displayed to the user.
 * @param string $menu_slug   The slug name to refer to this menu by.
 * @param callable $function  The function to be called to output the content for this page.
 */
function add_submenu_page(string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, $function = ''): void {
    global $cms_admin_menu;

    // Check capability
    if (function_exists('current_user_can') && !current_user_can($capability)) {
        return;
    }

    $submenu_item = [
        'type'       => 'submenu_page',
        'page_title' => $page_title,
        'menu_title' => $menu_title,
        'capability' => $capability,
        'menu_slug'  => $menu_slug, // This is the actual slug for the page
        'callable'   => $function,
        'url'        => '/admin/plugins/' . $parent_slug . '/' . $menu_slug // Construct URL for router
    ];

    // Find parent and append
    // Note: We need to search effectively since keys can be anything
    $found = false;
    foreach ($cms_admin_menu as $key => &$item) { // Note reference
        if (isset($item['menu_slug']) && $item['menu_slug'] === $parent_slug) {
            $item['children'][] = $submenu_item;
            $found = true;
            break;
        }
    }
}

/**
 * Get all registered admin menus
 */
function get_registered_admin_menus(): array {
    global $cms_admin_menu;
    
    // Sort by position key if set, ensuring numerical order
    // But $cms_admin_menu keys are mixed? 
    // They are populated via $cms_admin_menu[$position] = ... or [] = ...
    // ksort handles this reasonably well.
    ksort($cms_admin_menu);
    return $cms_admin_menu;
}


/**
 * Translation Helper: __()
 * 
 * Retrieves the translation of .
 * This is a minimal polyfill for the CMS environment.
 */
function __(string $text, string $domain = 'default'): string {
    return \CMS\Services\TranslationService::getInstance()->translate($text, $domain);
}

/**
 * Translation Helper: _e()
 * 
 * Displays the translation of .
 */
function _e(string $text, string $domain = 'default'): void {
    echo __($text, $domain);
}

/**
 * Translation Helper: _x()
 * 
 * Retrieves the translation of  with context.
 */
function _x(string $text, string $context, string $domain = 'default'): string {
    // Kontext wird aktuell nicht getrennt gespeichert; Fallback auf normale Übersetzung.
    return __($text, $domain);
}

/**
 * Translation Helper: _n()
 * 
 * Retrieves the plural or single form based on the amount.
 */
function _n(string $single, string $plural, int $number, string $domain = 'default'): string {
    return \CMS\Services\TranslationService::getInstance()->translatePlural($single, $plural, $number, $domain);
}


/**
 * Translation Helper: _ex()
 * 
 * Displays the translation of  with context.
 */
function _ex(string $text, string $context, string $domain = 'default'): void {
    echo _x($text, $context, $domain); 
}

/**
 * Sanitize textarea field
 */
function sanitize_textarea_field(string $text): string {
    return strip_tags(trim($text));
}

/**
 * Remove slashes from a string or array of strings.
 */
function wp_unslash($value) {
    return stripslashes_deep($value);
}

/**
 * Navigates through an array, object, or scalar, and removes slashes from the values.
 */
function stripslashes_deep($value) {
    return map_deep($value, 'stripslashes');
}

/**
 * Maps a function to all non-iterable elements of an array or an object.
 */
function map_deep($value, $callback) {
    if (is_array($value)) {
        foreach ($value as $index => $item) {
            $value[$index] = map_deep($item, $callback);
        }
    } elseif (is_object($value)) {
        $object_vars = get_object_vars($value);
        foreach ($object_vars as $property_name => $property_value) {
            $value->$property_name = map_deep($property_value, $callback);
        }
    } else {
        $value = call_user_func($callback, $value);
    }
    return $value;
}

/**
 * Verify nonce – delegiert an CMS\Security::verifyToken()
 */
function wp_verify_nonce(string $nonce, string|int $action = -1): bool {
    return \CMS\Security::instance()->verifyToken($nonce, (string) $action);
}

/**
 * Die function
 */
function wp_die(string $message = '', int $code = 500): void {
    http_response_code($code);
    die($message);
}

/**
 * Redirect
 */
function wp_redirect(string $location, int $status = 302): void {
    redirect($location);
}

/**
 * Add query arg
 */
function add_query_arg($key, $value = false, $url = false): string {
    // Support WP signature: add_query_arg( $key, $value, $url ) or add_query_arg( $args, $url )
    if (is_array($key)) {
        $args = $key;
        $url = $value;
    } else {
        $args = [$key => $value];
    }

    if (empty($url) || !is_string($url)) {
        $url = $_SERVER['REQUEST_URI'] ?? '/';
    }
    
    $parts = parse_url($url);
    $query = [];
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    
    $query = array_merge($query, $args);
    
    $result = ($parts['path'] ?? '') . '?' . http_build_query($query);
    return $result;
}

/**
 * Admin URL
 */
function admin_url(string $path = ''): string {
    return SITE_URL . '/admin/' . ltrim($path, '/');
}


/**
 * Get current user ID
 */
function get_current_user_id(): int {
    $user = \CMS\Auth::instance()->currentUser();
    return $user ? (int) $user->id : 0;
}

/**
 * Sanitize title (Mock)
 */
function sanitize_title(string $title): string {
    return strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', $title));
}

/**
 * WP Kses Post – filtert HTML auf eine sichere Whitelist.
 * Delegiert an PurifierService (HTMLPurifier) wenn verfügbar.
 */
function wp_kses_post(string $content): string {
    return \CMS\Services\PurifierService::getInstance()->purify($content, 'default');
}

/**
 * HTML sanitieren – zentraler Einstiegspunkt.
 *
 * @param string $html    Unsicherer HTML-String
 * @param string $profile 'default' | 'strict' | 'minimal'
 * @return string          Sauberer HTML-String
 */
function sanitize_html(string $html, string $profile = 'default'): string {
    return \CMS\Services\PurifierService::getInstance()->purify($html, $profile);
}

/**
 * Create Nonce
 */
function wp_create_nonce(string|int $action = -1): string {
    // In a real WP environment, $action is used to salt the nonce
    // We cast to string because our Security::createToken expects a string
    return \CMS\Security::instance()->createNonce((string)$action);
}

/**
 * Check Admin Referer
 */
function check_admin_referer(string|int $action = -1, string $query_arg = '_wpnonce'): int {
    // Check if nonce exists in request
    $nonce = $_REQUEST[$query_arg] ?? '';
    // $action in wp_verify_nonce expects string|int
    if (wp_verify_nonce($nonce, $action)) {
        return 1;
    }
    wp_die('Expired security token.');
    return 0; 
}

/**
 * Send JSON Success
 */
function wp_send_json_success($data = null, ?int $status_code = null): void {
    $response = ['success' => true];
    if ( isset($data) ) {
        $response['data'] = $data;
    }
    header('Content-Type: application/json');
    if ($status_code) {
        http_response_code($status_code);
    }
    echo json_encode($response);
    die;
}

/**
 * Send JSON Error
 */
function wp_send_json_error($data = null, ?int $status_code = null): void {
    $response = ['success' => false];
    if ( isset($data) ) {
        $response['data'] = $data;
    }
    header('Content-Type: application/json');
    if ($status_code) {
        http_response_code($status_code);
    }
    echo json_encode($response);
    die;
}

/**
 * WordPress $wpdb compatibility shim
 *
 * Wraps CMS\Database so plugins using $wpdb-style code work without modification.
 * The CMS never depends on plugins — this shim lives in the CMS includes layer.
 *
 * Lazy initialization: \CMS\Database is not accessed until the first DB call,
 * so this class can be safely instantiated before the autoloader fires.
 */
if ( ! class_exists('CMS_WPDB_Compat') ) {
    class CMS_WPDB_Compat
    {
        /** Resolved lazily on first access via __get() */
        private ?string $_prefix = null;
        public int $insert_id = 0;

        public function __construct() {}

        /**
         * Lazy accessor for \CMS\Database singleton.
         */
        private function db(): \CMS\Database
        {
            return \CMS\Database::instance();
        }

        /**
         * Magic getter — resolves $prefix lazily so the constructor never
         * touches \CMS\Database.
         */
        public function __get(string $name): mixed
        {
            if ($name === 'prefix') {
                if ($this->_prefix === null) {
                    $this->_prefix = $this->db()->prefix;
                }
                return $this->_prefix;
            }
            return null;
        }

        public function __set(string $name, mixed $value): void
        {
            if ($name === 'prefix') {
                $this->_prefix = $value;
            }
        }

        public function __isset(string $name): bool
        {
            return $name === 'prefix';
        }

        /**
         * WP-style prepare(): replaces %d, %s, %f with PDO-quoted values.
         */
        public function prepare(string $query, mixed ...$args): string
        {
            $pdo = $this->db()->getPdo();
            $i   = 0;
            return (string) preg_replace_callback(
                '/%([dsfF])/',
                function (array $m) use ($pdo, $args, &$i): string {
                    $val = $args[$i++] ?? null;
                    if ($val === null) {
                        return 'NULL';
                    }
                    return match ($m[1]) {
                        'd'     => (string) (int) $val,
                        'f','F' => (string) (float) $val,
                        default => $pdo->quote((string) $val),
                    };
                },
                $query
            );
        }

        /**
         * Insert a row. $formats is ignored — PDO handles binding.
         */
        public function insert(string $table, array $data, array $formats = []): bool
        {
            $db              = $this->db();
            $result          = $db->insert($table, $data);
            $this->insert_id = $db->insert_id();
            return $result !== false;
        }

        public function update(string $table, array $data, array $where, array $formats = [], array $where_formats = []): bool
        {
            return $this->db()->update($table, $data, $where);
        }

        public function delete(string $table, array $where, array $where_formats = []): bool
        {
            return $this->db()->delete($table, $where);
        }

        public function get_row(string $sql, string $output = 'OBJECT', int $y = 0): ?object
        {
            return $this->db()->get_row($sql, []) ?: null;
        }

        public function get_results(string $sql, string $output = 'OBJECT'): array
        {
            return $this->db()->get_results($sql, []);
        }

        public function get_var(string $sql): mixed
        {
            return $this->db()->get_var($sql, []);
        }

        public function query(string $sql): bool
        {
            try {
                $this->db()->query($sql);
                return true;
            } catch (\Exception) {
                return false;
            }
        }

        public function esc_like(string $text): string
        {
            return addcslashes($text, '\\%_');
        }
    }
}

// ── WordPress Hook Aliases ────────────────────────────────────────────────────

if ( ! function_exists('do_action') ) {
    /**
     * Execute action hooks (delegates to CMS\Hooks::doAction).
     */
    function do_action( string $tag, ...$args ): void {
        if ( class_exists('CMS\Hooks') ) {
            CMS\Hooks::doAction( $tag, ...$args );
        }
    }
}

if ( ! function_exists('add_action') ) {
    /**
     * Register an action hook (delegates to CMS\Hooks::addAction).
     */
    function add_action( string $tag, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
        if ( class_exists('CMS\Hooks') ) {
            CMS\Hooks::addAction( $tag, $callback, $priority );
        }
        return true;
    }
}

if ( ! function_exists('add_filter') ) {
    /**
     * Register a filter hook (delegates to CMS\Hooks::addFilter).
     */
    function add_filter( string $tag, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
        if ( class_exists('CMS\Hooks') ) {
            CMS\Hooks::addFilter( $tag, $callback, $priority );
        }
        return true;
    }
}

if ( ! function_exists('apply_filters') ) {
    /**
     * Apply filters (delegates to CMS\Hooks::applyFilters).
     */
    function apply_filters( string $tag, $value, ...$args ) {
        if ( class_exists('CMS\Hooks') ) {
            return CMS\Hooks::applyFilters( $tag, $value, ...$args );
        }
        return $value;
    }
}

if ( ! function_exists('remove_action') ) {
    function remove_action( string $tag, callable $callback, int $priority = 10 ): bool {
        if ( class_exists('CMS\Hooks') ) {
            CMS\Hooks::removeAction( $tag, $callback, $priority );
        }
        return true;
    }
}

if ( ! function_exists('remove_filter') ) {
    function remove_filter( string $tag, callable $callback, int $priority = 10 ): bool {
        if ( class_exists('CMS\Hooks') ) {
            CMS\Hooks::removeFilter( $tag, $callback, $priority );
        }
        return true;
    }
}

if ( ! function_exists('has_action') ) {
    function has_action( string $tag, $callback = false ): bool {
        return false; // simplified stub
    }
}

if ( ! function_exists('has_filter') ) {
    function has_filter( string $tag, $callback = false ): bool {
        return false; // simplified stub
    }
}

// ── WordPress Number / String Helpers ─────────────────────────────────────────

if ( ! function_exists('absint') ) {
    /**
     * Convert value to non-negative integer.
     */
    function absint( $maybeint ): int {
        return abs( (int) $maybeint );
    }
}

if ( ! function_exists('sanitize_key') ) {
    /**
     * Sanitize a string key: lowercase letters, numbers, underscores, hyphens.
     */
    function sanitize_key( string $key ): string {
        $key = strtolower( $key );
        $key = preg_replace( '/[^a-z0-9_\-]/', '', $key );
        return $key ?? '';
    }
}

if ( ! function_exists('esc_url_raw') ) {
    /**
     * Sanitize a URL for database storage (no HTML encoding).
     */
    function esc_url_raw( string $url ): string {
        $url = trim( $url );
        if ( '' === $url ) {
            return '';
        }
        // Allow only http/https/ftp schemes
        if ( ! preg_match( '#^https?://#i', $url ) && ! preg_match( '#^ftp://#i', $url ) ) {
            return '';
        }
        return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
    }
}

if ( ! function_exists('wp_strip_all_tags') ) {
    /**
     * Strip HTML and PHP tags and optionally remove line breaks.
     */
    function wp_strip_all_tags( string $text, bool $remove_breaks = false ): string {
        $text = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $text );
        $text = strip_tags( $text );
        if ( $remove_breaks ) {
            $text = preg_replace( '/[\r\n\t ]+/', ' ', $text );
        }
        return trim( $text );
    }
}

if ( ! function_exists('trailingslashit') ) {
    /**
     * Append a trailing slash.
     */
    function trailingslashit( string $string ): string {
        return rtrim( $string, '/\\' ) . '/';
    }
}

if ( ! function_exists('untrailingslashit') ) {
    /**
     * Remove trailing slash.
     */
    function untrailingslashit( string $string ): string {
        return rtrim( $string, '/\\' );
    }
}

// ── WordPress Asset Enqueueing (CMS-Stub — assets are loaded via head hook) ───

if ( ! function_exists('wp_enqueue_style') ) {
    /**
     * Stub: In CMSv2 assets are enqueued via the 'head' hook directly.
     */
    function wp_enqueue_style( string $handle, string $src = '', array $deps = [], $ver = false, string $media = 'all' ): void {
        // no-op — plugin loads CSS via CMS\Hooks::addAction('head', ...)
    }
}

if ( ! function_exists('wp_enqueue_script') ) {
    /**
     * Stub: In CMSv2 scripts are enqueued via the 'body_end' hook directly.
     */
    function wp_enqueue_script( string $handle, string $src = '', array $deps = [], $ver = false, bool $in_footer = false ): void {
        // no-op — plugin loads JS via CMS\Hooks::addAction('body_end', ...)
    }
}

if ( ! function_exists('wp_register_style') ) {
    function wp_register_style( string $handle, string $src, array $deps = [], $ver = false, string $media = 'all' ): bool {
        return true; // no-op
    }
}

if ( ! function_exists('wp_register_script') ) {
    function wp_register_script( string $handle, string $src, array $deps = [], $ver = false, bool $in_footer = false ): bool {
        return true; // no-op
    }
}

if ( ! function_exists('wp_localize_script') ) {
    function wp_localize_script( string $handle, string $object_name, array $l10n ): bool {
        return true; // no-op in CMSv2
    }
}

if ( ! function_exists('wp_dequeue_style') ) {
    function wp_dequeue_style( string $handle ): void {}
}

if ( ! function_exists('wp_dequeue_script') ) {
    function wp_dequeue_script( string $handle ): void {}
}

// ── WordPress URL Helpers ─────────────────────────────────────────────────────

if ( ! function_exists('get_site_url') ) {
    function get_site_url( ?int $blog_id = null, string $path = '', string $scheme = '' ): string {
        $url = defined('SITE_URL') ? SITE_URL : '';
        return $path ? rtrim( $url, '/' ) . '/' . ltrim( $path, '/' ) : $url;
    }
}

if ( ! function_exists('home_url') ) {
    function home_url( string $path = '', string $scheme = '' ): string {
        $url = defined('SITE_URL') ? SITE_URL : '';
        return $path ? rtrim( $url, '/' ) . '/' . ltrim( $path, '/' ) : $url;
    }
}

if ( ! function_exists('site_url') ) {
    function site_url( string $path = '', string $scheme = '' ): string {
        return home_url( $path, $scheme );
    }
}

if ( ! function_exists('plugins_url') ) {
    function plugins_url( string $path = '', string $plugin = '' ): string {
        $base = defined('SITE_URL') ? SITE_URL . '/plugins' : '/plugins';
        return $path ? rtrim( $base, '/' ) . '/' . ltrim( $path, '/' ) : $base;
    }
}

// ── WordPress Misc ─────────────────────────────────────────────────────────────

if ( ! function_exists('wp_parse_args') ) {
    /**
     * Merge user-defined arguments into defaults array.
     */
    function wp_parse_args( $args, array $defaults = [] ): array {
        if ( is_array( $args ) ) {
            return array_merge( $defaults, $args );
        }
        if ( is_object( $args ) ) {
            return array_merge( $defaults, (array) $args );
        }
        // Handle query string format
        parse_str( (string) $args, $parsed );
        return array_merge( $defaults, $parsed );
    }
}

if ( ! function_exists('wp_parse_url') ) {
    function wp_parse_url( string $url, int $component = -1 ) {
        return parse_url( $url, $component );
    }
}

if ( ! function_exists('wp_json_encode') ) {
    function wp_json_encode( $data, int $options = 0, int $depth = 512 ): string|false {
        return json_encode( $data, $options, $depth );
    }
}

if ( ! function_exists('wp_uniqid') ) {
    function wp_uniqid( string $prefix = '', bool $more_entropy = false ): string {
        return uniqid( $prefix, $more_entropy );
    }
}

if ( ! function_exists('submit_button') ) {
    /**
     * Render a submit button (WP admin style).
     */
    function submit_button(
        string $text = '',
        string $type = 'primary',
        string $name = 'submit',
        bool   $wrap = true,
        array  $other_attributes = []
    ): void {
        if ( '' === $text ) {
            $text = __( 'Save Changes' );
        }
        $attrs = '';
        foreach ( $other_attributes as $k => $v ) {
            $attrs .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
        }
        if ( $wrap ) {
            echo '<p class="submit">';
        }
        echo '<button type="submit" name="' . esc_attr( $name ) . '" class="button button-' . esc_attr( $type ) . '"' . $attrs . '>'
            . esc_html( $text )
            . '</button>';
        if ( $wrap ) {
            echo '</p>';
        }
    }
}

if ( ! function_exists('wp_sprintf') ) {
    function wp_sprintf( string $pattern, ...$args ): string {
        return vsprintf( $pattern, $args );
    }
}

if ( ! function_exists('esc_sql') ) {
    function esc_sql( $data ) {
        global $wpdb;
        if ( isset( $wpdb ) && is_object( $wpdb ) ) {
            return $wpdb->_real_escape( $data );
        }
        if ( is_array( $data ) ) {
            return array_map( 'addslashes', $data );
        }
        return addslashes( (string) $data );
    }
}

if ( ! function_exists('maybe_serialize') ) {
    function maybe_serialize( $data ): string {
        if ( is_array( $data ) || is_object( $data ) ) {
            return serialize( $data );
        }
        return (string) $data;
    }
}

if ( ! function_exists('maybe_unserialize') ) {
    function maybe_unserialize( string $original ) {
        if ( is_serialized( $original ) ) {
            return @unserialize( $original, ['allowed_classes' => false] );
        }
        return $original;
    }
}

if ( ! function_exists('is_serialized') ) {
    function is_serialized( $data, bool $strict = true ): bool {
        if ( ! is_string( $data ) ) {
            return false;
        }
        $data = trim( $data );
        if ( 'N;' === $data ) {
            return true;
        }
        if ( strlen( $data ) < 4 ) {
            return false;
        }
        if ( ':' !== $data[1] ) {
            return false;
        }
        return (bool) preg_match( '/^[aOCbids]:/', $data );
    }
}

// Provide the global $wpdb instance for WordPress-style plugin compatibility
global $wpdb;
if ( ! isset($wpdb) || ! ($wpdb instanceof CMS_WPDB_Compat) ) {
    $wpdb = new CMS_WPDB_Compat();
}

// ─── E-Mail Helper ─────────────────────────────────────────────────────────

/**
 * E-Mail senden über den zentralen MailService (SMTP oder mail()-Fallback).
 *
 * @param string $to      Empfänger
 * @param string $subject Betreff
 * @param string $body    HTML- oder Plain-Text-Inhalt
 * @param array  $headers Zusätzliche Header
 * @return bool
 */
function cms_mail(string $to, string $subject, string $body, array $headers = []): bool {
    $queue = \CMS\Services\MailQueueService::getInstance();
    if ($queue->shouldQueue($headers)) {
        $result = $queue->enqueue($to, $subject, $body, $headers, null, 'cms_mail_helper');
        return !empty($result['success']);
    }

    return \CMS\Services\MailService::getInstance()->send($to, $subject, $body, $headers);
}
