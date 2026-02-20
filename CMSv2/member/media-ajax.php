<?php
/**
 * Member Media Controller - AJAX Handler
 * 
 * @package CMSv2\Member
 */

declare(strict_types=1);

// Disable error display to prevent JSON corruption
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Handle direct access vs included access
if (!defined('ABSPATH')) {
    // Standalone mode: Start buffer and load config
    if (ob_get_level() == 0) ob_start();
    
    // Load config
    $configFile = dirname(dirname(__DIR__)) . '/config.php';
    if (file_exists($configFile)) {
        require_once $configFile;
    }
} else {
    // Included mode: Clean any previous output (e.g. from config include in parent)
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    ob_start();
}

// If config fails to define CORE_PATH, try manual fallback
if (!defined('CORE_PATH')) {
    define('CORE_PATH', dirname(__DIR__) . '/core/');
}

if (file_exists(CORE_PATH . 'autoload.php')) {
    require_once CORE_PATH . 'autoload.php';
}

use CMS\Auth;
use CMS\Services\MediaService;

// Prevent if ABSPATH still not defined
if (!defined('ABSPATH')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Config Load Error']);
    exit;
}

// Basic Authentication for Member Area
$auth = Auth::instance();
if (!$auth || !$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht eingeloggt']);
    exit;
}

$currentUser = $auth->getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nutzer nicht gefunden']);
    exit;
}

// Check Settings
// MediaService without args loads global settings
$globalMedia = new MediaService(); 
$settings = $globalMedia->getSettings(); 

// Check if member uploads are enabled
if (empty($settings['member_uploads_enabled']) && !$auth->isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Uploads deaktiviert']);
    exit;
}

// Setup Member Sandbox
// Path: uploads/member/{username}/
$cleanUsername = preg_replace('/[^a-zA-Z0-9_-]/', '', $currentUser->username);
if (empty($cleanUsername)) $cleanUsername = 'user_' . $currentUser->id;

$memberRoot = UPLOAD_PATH . 'member' . DIRECTORY_SEPARATOR . $cleanUsername;

if (!is_dir($memberRoot)) {
    if (!mkdir($memberRoot, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Mitglieder-Ordner konnte nicht erstellt werden.']);
        exit;
    }
}

// Instantiate Service with Member Root
$memberMedia = new MediaService($memberRoot);

// Handle Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Clear any previous output
    ob_end_clean();
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $currentPath = isset($_POST['path']) ? trim($_POST['path'], '/\\') : '';

    try {
        switch ($action) {
            case 'list_files':
                $result = $memberMedia->getItems($currentPath);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message()]);
                } else {
                    echo json_encode(['success' => true, 'data' => $result]);
                }
                break;

            case 'create_folder':
                $name = $_POST['name'] ?? '';
                if (empty($name)) {
                    echo json_encode(['success' => false, 'error' => 'Name fehlt']);
                    break;
                }
                // Prevent path traversal in name
                $name = basename($name);
                
                $result = $memberMedia->createFolder($name, $currentPath);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message()]);
                } else {
                    echo json_encode(['success' => true]);
                }
                break;

            case 'upload_file':
                if (!isset($_FILES['file'])) {
                    echo json_encode(['success' => false, 'error' => 'Keine Datei']);
                    break;
                }
                
                // Enforce Date Structure (YYYY/MM) if enforced by system, 
                // OR allow user to upload to current folder they navigated to.
                // Standard WordPress behavior is date-based.
                // Requirement: "into the subfolder Member > ProfileName > DATE"
                
                // If the user provided a path (they are inside a folder), respect it.
                // Otherwise use date-based structure.
                
                if (!empty($currentPath)) {
                    $targetPath = $currentPath;
                } else {
                    $year = date('Y');
                    $month = date('m');
                    $targetPath = $year . '/' . $month;
                }
                
                $result = $memberMedia->uploadFile($_FILES['file'], $targetPath);
                
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message()]);
                } else {
                    echo json_encode(['success' => true, 'filename' => $result]);
                }
                break;

            case 'delete_item':
                $itemPath = $_POST['item_path'] ?? '';
                if (empty($itemPath)) {
                    echo json_encode(['success' => false, 'error' => 'Pfad fehlt']);
                    break;
                }
                $result = $memberMedia->deleteItem($itemPath);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message()]);
                } else {
                    echo json_encode(['success' => true]);
                }
                break;
            
            case 'rename_item':
                $oldPath = $_POST['old_path'] ?? '';
                $newName = $_POST['new_name'] ?? '';
                if (empty($oldPath) || empty($newName)) {
                     echo json_encode(['success' => false, 'error' => 'Parameter fehlen']);
                     break;
                }
                $result = $memberMedia->renameItem($oldPath, $newName);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message()]);
                } else {
                    echo json_encode(['success' => true]);
                }
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Unbekannte Aktion']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Serverfehler: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Ungültige Anfrage']);
}
exit;

