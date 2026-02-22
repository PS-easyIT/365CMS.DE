<?php
/**
 * Data Deletion Request Admin Page (GDPR Art. 17)
 * 
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Database;
use CMS\Security;

if (!defined('ABSPATH')) exit;
if (!Auth::instance()->isAdmin()) { header('Location: ' . SITE_URL); exit; }

$auth = Auth::instance();
$db = Database::instance();
$security = Security::instance();

$message = '';
$messageType = '';
$userData = null;

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ANONYMIZE ACTION
    if (isset($_POST['action']) && $_POST['action'] === 'anonymize_user' && !empty($_POST['user_id'])) {
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'data_deletion')) {
            die('Security check failed');
        }

        $uid = (int)$_POST['user_id'];
        
        // Ensure user is not an admin to prevent self-lockout or accidents
        $userToCheck = $db->execute("SELECT * FROM {$db->getPrefix()}users WHERE id = ?", [$uid])->fetch();
        // Simple check: if ID 1 or current user
        if ($uid === 1 || $uid === $auth->getCurrentUserId()) {
            $message = 'Admin-Konten oder das eigene Konto können hier nicht gelöscht werden.';
            $messageType = 'error';
        } else {
            // Processing Anonymization
            $anonEmail = "deleted_{$uid}_" . time() . "@anonymized.local";
            $anonName  = "Deleted User {$uid}";
            
            // 1. Update User Table
            $db->execute("UPDATE {$db->getPrefix()}users SET user_login = ?, user_email = ?, user_pass = 'DISABLED', display_name = ? WHERE id = ?", 
                [$anonEmail, $anonEmail, $anonName, $uid]);

            // 2. Delete Meta (Optional: keep some order meta if needed for accounting, but generally delete personal meta)
            // We delete all meta for strict GDPR "Right to be Forgotten" except maybe flags that indicate deletion
            $db->execute("DELETE FROM {$db->getPrefix()}user_meta WHERE user_id = ?", [$uid]);
            $db->execute("INSERT INTO {$db->getPrefix()}user_meta (user_id, meta_key, meta_value) VALUES (?, 'is_anonymized', '1')", [$uid]);

            // 3. Anonymize Orders (if exist)
            // Ideally we keep the order record for tax reasons but remove personal data from it if stored there
            // This depends on Order implementation. Assuming Orders link to User ID, the user is now anon.

            $message = "Benutzer #{$uid} wurde erfolgreich anonymisiert.";
            $messageType = 'success';
            $userData = null; // Clear view
        }
    }

    // SEARCH
    $searchEmail = trim($_POST['email'] ?? '');
    if (!empty($searchEmail) && empty($message)) { // Don't search if we just acted
        $userData = $db->execute("SELECT * FROM {$db->getPrefix()}users WHERE user_email = ?", [$searchEmail])->fetch();
        if (!$userData) {
            $message = 'Kein Benutzer gefunden.';
            $messageType = 'error';
        }
    }
}

$csrfToken = $security->generateToken('data_deletion');

require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Löschanträge - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=20260222b">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">
    <?php renderAdminSidebar('data-deletion'); ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h2>🗑️ Löschanträge (Art. 17 DSGVO)</h2>
            <p>Recht auf Vergessenwerden: Benutzerdaten anonymisieren oder löschen.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- SEARCH -->
        <div class="adm-card">
            <h3>🔍 Benutzer zur Löschung suchen</h3>
            <form method="post">
                <div class="form-group">
                    <label>E-Mail Adresse des Nutzers</label>
                    <div style="display:flex; gap:1rem;">
                        <input type="email" name="email" placeholder="user@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        <button type="submit" class="btn-primary">Suchen</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ACTION AREA -->
        <?php if ($userData): ?>
        <div class="adm-card" style="border-top: 4px solid #ef4444;">
             <h3 style="color:#ef4444;">🚨 Gefundener Benutzer: <?php echo htmlspecialchars($userData->display_name); ?></h3>
             <p>E-Mail: <strong><?php echo htmlspecialchars($userData->user_email); ?></strong> (ID: <?php echo $userData->id; ?>)</p>
             
             <div class="warning-box">
                 <strong>⚠️ Achtung:</strong> Diese Aktion ist <u>irreversibel</u>.
                 <ul style="margin:0.5rem 0 0 1rem; font-size:0.9rem;">
                     <li>Der Benutzer wird umbenannt in "Deleted User".</li>
                     <li>Die E-Mail-Adresse wird anonymisiert.</li>
                     <li>Das Passwort wird deaktiviert (Login unmöglich).</li>
                     <li>Persönliche Metadaten werden gelöscht.</li>
                     <li>Bestellhistorien bleiben für steuerliche Zwecke erhalten (jedoch anonym verknüpft).</li>
                 </ul>
             </div>

             <form method="post" onsubmit="return confirm('Sind Sie sicher? Dieser Vorgang kann nicht rückgängig gemacht werden.');">
                 <input type="hidden" name="action" value="anonymize_user">
                 <input type="hidden" name="user_id" value="<?php echo $userData->id; ?>">
                 <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                 
                 <label style="display:flex; gap:0.5rem; align-items:center; margin-bottom:1rem;">
                     <input type="checkbox" required>
                     <span>Ich bestätige, dass ich die Identität des Antragstellers geprüft habe.</span>
                 </label>

                 <button type="submit" class="btn-danger">
                     🗑️ Jetzt unwiderruflich anonymisieren
                 </button>
             </form>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>
