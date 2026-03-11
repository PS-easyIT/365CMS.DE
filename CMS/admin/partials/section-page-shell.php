<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

$sectionPageConfig = is_array($sectionPageConfig ?? null) ? $sectionPageConfig : [];
$routePath = (string)($sectionPageConfig['route_path'] ?? SITE_URL);
$viewFile = (string)($sectionPageConfig['view_file'] ?? '');
$pageTitle = (string)($sectionPageConfig['page_title'] ?? 'Admin');
$activePage = (string)($sectionPageConfig['active_page'] ?? 'dashboard');
$pageAssets = is_array($sectionPageConfig['page_assets'] ?? null) ? $sectionPageConfig['page_assets'] : [];
$section = (string)($sectionPageConfig['section'] ?? 'overview');
$csrfAction = (string)($sectionPageConfig['csrf_action'] ?? 'admin_section');
$guardConstant = (string)($sectionPageConfig['guard_constant'] ?? '');
$moduleFile = (string)($sectionPageConfig['module_file'] ?? '');
$moduleFactory = $sectionPageConfig['module_factory'] ?? null;
$postHandler = $sectionPageConfig['post_handler'] ?? null;
$dataLoader = $sectionPageConfig['data_loader'] ?? null;
$alertSessionKey = (string)($sectionPageConfig['alert_session_key'] ?? 'admin_alert');
$invalidTokenMessage = (string)($sectionPageConfig['invalid_token_message'] ?? 'Sicherheitstoken ungültig.');
$unknownActionMessage = (string)($sectionPageConfig['unknown_action_message'] ?? 'Unbekannte Antwort.');

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

if ($moduleFile !== '') {
    require_once $moduleFile;
}

if (!is_callable($moduleFactory)) {
    throw new RuntimeException('Admin-Section-Shell erwartet eine callable module_factory-Konfiguration.');
}

$module = $moduleFactory();
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, $csrfAction)) {
        $_SESSION[$alertSessionKey] = ['type' => 'danger', 'message' => $invalidTokenMessage];
        header('Location: ' . SITE_URL . $routePath);
        exit;
    }

    $result = is_callable($postHandler)
        ? $postHandler($module, $section, $_POST)
        : ['success' => false, 'error' => $unknownActionMessage];

    $_SESSION[$alertSessionKey] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => $result['message'] ?? $result['error'] ?? $unknownActionMessage,
    ];

    header('Location: ' . SITE_URL . $routePath);
    exit;
}

if (!empty($_SESSION[$alertSessionKey])) {
    $alert = $_SESSION[$alertSessionKey];
    unset($_SESSION[$alertSessionKey]);
}

$csrfToken = Security::instance()->generateToken($csrfAction);
$data = is_callable($dataLoader)
    ? $dataLoader($module)
    : (method_exists($module, 'getData') ? $module->getData() : []);

if ($guardConstant !== '' && !defined($guardConstant)) {
    define($guardConstant, true);
}

require __DIR__ . '/header.php';
require __DIR__ . '/sidebar.php';
require $viewFile;
require __DIR__ . '/footer.php';
