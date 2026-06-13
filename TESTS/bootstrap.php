<?php
declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$cmsRoot = $projectRoot . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR;

if (!defined('ABSPATH')) {
    define('ABSPATH', $cmsRoot);
}

if (!defined('CMS_TESTS_RUNNING')) {
    define('CMS_TESTS_RUNNING', true);
}

if (!defined('CMS_DEBUG')) {
    define('CMS_DEBUG', false);
}

if (!defined('LOG_LEVEL')) {
    define('LOG_LEVEL', 'warning');
}

if (!defined('LOG_PATH')) {
    define('LOG_PATH', $projectRoot . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'test-logs' . DIRECTORY_SEPARATOR);
}

if (!is_dir(LOG_PATH) && !mkdir(LOG_PATH, 0750, true) && !is_dir(LOG_PATH)) {
    throw new RuntimeException('TESTS/bootstrap: Log-Verzeichnis konnte nicht angelegt werden: ' . LOG_PATH);
}

$testUploadsPath = $projectRoot . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'test-uploads' . DIRECTORY_SEPARATOR;
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', $testUploadsPath);
}

if (!is_dir(UPLOAD_PATH) && !mkdir(UPLOAD_PATH, 0750, true) && !is_dir(UPLOAD_PATH)) {
    throw new RuntimeException('TESTS/bootstrap: Upload-Verzeichnis konnte nicht angelegt werden: ' . UPLOAD_PATH);
}

if (!defined('UPLOAD_URL')) {
    define('UPLOAD_URL', '/cache/test-uploads');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost');
}

require_once ABSPATH . 'core/autoload.php';
