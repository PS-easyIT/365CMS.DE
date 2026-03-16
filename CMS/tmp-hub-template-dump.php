<?php
require __DIR__ . '/config/app.php';
require_once CORE_PATH . 'Contracts/DatabaseInterface.php';
require_once CORE_PATH . 'Debug.php';
require_once CORE_PATH . 'MigrationManager.php';
require_once CORE_PATH . 'SchemaManager.php';
require_once CORE_PATH . 'Database.php';

echo "PRE_DB\n";
try {
    $db = \CMS\Database::instance();
    echo "DB_OK\n";
    $prefix = $db->getPrefix();
    $row = $db->get_row("SELECT option_value FROM {$prefix}settings WHERE option_name = ? LIMIT 1", ['hub_site_templates']);
    var_dump($row);
} catch (Throwable $e) {
    echo 'ERR: ' . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
