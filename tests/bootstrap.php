<?php
declare(strict_types=1);

$cmsRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR;

define('ABSPATH', $cmsRoot);
define('CMS_DEBUG', true);
define('CMS_VERSION', 'test');
define('SITE_URL', 'https://example.test');
define('UPLOAD_PATH', sys_get_temp_dir() . DIRECTORY_SEPARATOR . '365cms-test-uploads');
define('UPLOAD_URL', SITE_URL . '/uploads');
define('LOG_PATH', sys_get_temp_dir() . DIRECTORY_SEPARATOR . '365cms-test-logs' . DIRECTORY_SEPARATOR);
define('LOG_LEVEL', 'debug');

if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}
if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0777, true);
}

require_once ABSPATH . 'core/WP_Error.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'CMS\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = ABSPATH . 'core/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
