<?php
/**
 * Data Access Request Admin Page
 * 
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;

if (!defined('ABSPATH')) exit;
if (!Auth::instance()->isAdmin()) { header('Location: ' . SITE_URL); exit; }

require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Recht auf Auskunft - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">
    <?php renderAdminSidebar('data-access'); ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h2>👤 Recht auf Auskunft</h2>
            <p>Erstellen Sie Datenauszüge für Nutzer gemäß Art. 15 DSGVO.</p>
        </div>
        <div class="alert alert-info">Diese Funktion befindet sich noch in der Entwicklung.</div>
    </div>
</body>
</html>
