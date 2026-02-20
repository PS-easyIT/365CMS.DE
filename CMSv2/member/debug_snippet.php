<?php
$log = date('Y-m-d H:i:s') . " - Access: " . $_SERVER['REQUEST_URI'] . "\n";
$log .= "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$log .= "Media Ajax Param: " . (isset($_REQUEST['media_ajax']) ? $_REQUEST['media_ajax'] : 'Not Set') . "\n";
file_put_contents(__DIR__ . '/media_debug.log', $log, FILE_APPEND);
