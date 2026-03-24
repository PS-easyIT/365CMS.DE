<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
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
    safe_redirect($location, $status);
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect(string $location, int $status = 302): void {
        safe_redirect($location, $status);
    }
}

/**
 * Add query arg
 */
function add_query_arg($key, $value = false, $url = false): string {
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

    return ($parts['path'] ?? '') . '?' . http_build_query($query);
}

/**
 * Admin URL
 */
function admin_url(string $path = ''): string {
    return cms_runtime_base_url('admin/' . ltrim($path, '/'));
}

/**
 * WP Kses Post – filtert HTML auf eine sichere Whitelist.
 */
function wp_kses_post(string $content): string {
    return \CMS\Services\PurifierService::getInstance()->purify($content, 'default');
}

/**
 * HTML sanitieren – zentraler Einstiegspunkt.
 */
function sanitize_html(string $html, string $profile = 'default'): string {
    return \CMS\Services\PurifierService::getInstance()->purify($html, $profile);
}

/**
 * Create Nonce
 */
function wp_create_nonce(string|int $action = -1): string {
    return \CMS\Security::instance()->createNonce((string) $action);
}

/**
 * Check Admin Referer
 */
function check_admin_referer(string|int $action = -1, string $query_arg = '_wpnonce'): int {
    $nonce = $_REQUEST[$query_arg] ?? '';
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
    if (isset($data)) {
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
    if (isset($data)) {
        $response['data'] = $data;
    }
    header('Content-Type: application/json');
    if ($status_code) {
        http_response_code($status_code);
    }
    echo json_encode($response);
    die;
}

if (!class_exists('CMS_WPDB_Compat')) {
    class CMS_WPDB_Compat
    {
        private ?string $_prefix = null;
        public int $insert_id = 0;

        public function __construct() {}

        private function db(): \CMS\Database
        {
            return \CMS\Database::instance();
        }

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

        public function prepare(string $query, mixed ...$args): string
        {
            $pdo = $this->db()->getPdo();
            $i = 0;
            return (string) preg_replace_callback(
                '/%([dsfF])/',
                function (array $m) use ($pdo, $args, &$i): string {
                    $val = $args[$i++] ?? null;
                    if ($val === null) {
                        return 'NULL';
                    }
                    return match ($m[1]) {
                        'd' => (string) (int) $val,
                        'f', 'F' => (string) (float) $val,
                        default => $pdo->quote((string) $val),
                    };
                },
                $query
            );
        }

        public function insert(string $table, array $data, array $formats = []): bool
        {
            $db = $this->db();
            $result = $db->insert($table, $data);
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

global $wpdb;
if (!isset($wpdb) || !($wpdb instanceof CMS_WPDB_Compat)) {
    $wpdb = new CMS_WPDB_Compat();
}

if (!function_exists('do_action')) {
    function do_action(string $tag, ...$args): void {
        if (class_exists('CMS\\Hooks')) {
            CMS\Hooks::doAction($tag, ...$args);
        }
    }
}

if (!function_exists('add_action')) {
    function add_action(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): bool {
        if (class_exists('CMS\\Hooks')) {
            CMS\Hooks::addAction($tag, $callback, $priority);
        }
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): bool {
        if (class_exists('CMS\\Hooks')) {
            CMS\Hooks::addFilter($tag, $callback, $priority);
        }
        return true;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $tag, $value, ...$args) {
        if (class_exists('CMS\\Hooks')) {
            return CMS\Hooks::applyFilters($tag, $value, ...$args);
        }
        return $value;
    }
}

if (!function_exists('remove_action')) {
    function remove_action(string $tag, callable $callback, int $priority = 10): bool {
        if (class_exists('CMS\\Hooks')) {
            CMS\Hooks::removeAction($tag, $callback, $priority);
        }
        return true;
    }
}

if (!function_exists('remove_filter')) {
    function remove_filter(string $tag, callable $callback, int $priority = 10): bool {
        if (class_exists('CMS\\Hooks')) {
            CMS\Hooks::removeFilter($tag, $callback, $priority);
        }
        return true;
    }
}

if (!function_exists('has_action')) {
    function has_action(string $tag, $callback = false): bool {
        return false;
    }
}

if (!function_exists('has_filter')) {
    function has_filter(string $tag, $callback = false): bool {
        return false;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style(string $handle, string $src = '', array $deps = [], $ver = false, string $media = 'all'): void {}
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(string $handle, string $src = '', array $deps = [], $ver = false, bool $in_footer = false): void {}
}

if (!function_exists('wp_register_style')) {
    function wp_register_style(string $handle, string $src, array $deps = [], $ver = false, string $media = 'all'): bool {
        return true;
    }
}

if (!function_exists('wp_register_script')) {
    function wp_register_script(string $handle, string $src, array $deps = [], $ver = false, bool $in_footer = false): bool {
        return true;
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script(string $handle, string $object_name, array $l10n): bool {
        return true;
    }
}

if (!function_exists('wp_dequeue_style')) {
    function wp_dequeue_style(string $handle): void {}
}

if (!function_exists('wp_dequeue_script')) {
    function wp_dequeue_script(string $handle): void {}
}

if (!function_exists('get_site_url')) {
    function get_site_url(?int $blog_id = null, string $path = '', string $scheme = ''): string {
        $url = cms_runtime_base_url();
        return $path ? rtrim($url, '/') . '/' . ltrim($path, '/') : $url;
    }
}

if (!function_exists('home_url')) {
    function home_url(string $path = '', string $scheme = ''): string {
        $url = cms_runtime_base_url();
        return $path ? rtrim($url, '/') . '/' . ltrim($path, '/') : $url;
    }
}

if (!function_exists('site_url')) {
    function site_url(string $path = '', string $scheme = ''): string {
        return home_url($path, $scheme);
    }
}

if (!function_exists('plugins_url')) {
    function plugins_url(string $path = '', string $plugin = ''): string {
        $base = cms_runtime_base_url('plugins');
        return $path ? rtrim($base, '/') . '/' . ltrim($path, '/') : $base;
    }
}

if (!function_exists('submit_button')) {
    function submit_button(string $text = '', string $type = 'primary', string $name = 'submit', bool $wrap = true, array $other_attributes = []): void {
        if ($text === '') {
            $text = __('Save Changes');
        }
        $attrs = '';
        foreach ($other_attributes as $k => $v) {
            $attrs .= ' ' . esc_attr($k) . '="' . esc_attr($v) . '"';
        }
        if ($wrap) {
            echo '<p class="submit">';
        }
        echo '<button type="submit" name="' . esc_attr($name) . '" class="button button-' . esc_attr($type) . '"' . $attrs . '>' . esc_html($text) . '</button>';
        if ($wrap) {
            echo '</p>';
        }
    }
}
