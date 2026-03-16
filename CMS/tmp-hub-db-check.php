<?php
require __DIR__ . '/config/app.php';
require_once CORE_PATH . 'Database.php';
echo class_exists('CMS\\Database') ? "DB_CLASS_OK\n" : "DB_CLASS_MISSING\n";
$db = \CMS\Database::instance();
echo "DB_INSTANCE_OK\n";
