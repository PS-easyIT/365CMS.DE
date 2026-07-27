<?php
/**
 * Data Access Request Admin Page (GDPR Art. 15)
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
$relatedData = [];

// Handle Search & Export
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchEmail = trim($_POST['email'] ?? '');
    
    // EXPORT JSON
    if (isset($_POST['action']) && $_POST['action'] === 'export_json' && !empty($_POST['user_id'])) {
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'data_access')) {
            die('Security check failed');
        }
        
        $uid = (int)$_POST['user_id'];
        $user = $db->execute("SELECT * FROM {$db->getPrefix()}users WHERE id = ?", [$uid])->fetch();
        
        if ($user) {
            // Gather all data
            $export = [
                'generated_at' => date('c'),
                'context' => 'GDPR Article 15 Data Access Request',
                'user' => (array)$user,
                'meta' => [],
                'orders' => []
            ];
            
            // Meta
            $meta = $db->execute("SELECT meta_key, meta_value FROM {$db->getPrefix()}user_meta WHERE user_id = ?", [$uid])->fetchAll();
            $export['meta'] = $meta;

            // Orders (if exist)
            // Check if table exists first to avoid error if plugin not active
            try {
                $orders = $db->execute("SELECT * FROM {$db->getPrefix()}orders WHERE user_id = ?", [$uid])->fetchAll();
                $export['orders'] = $orders;
            } catch (Exception $e) { $export['orders'] = 'Table not found'; }

            // User Log
            try {
                 $logs = $db->execute("SELECT * FROM {$db->getPrefix()}user_logs WHERE user_id = ?", [$uid])->fetchAll();
                 $export['logs'] = $logs;
            } catch (Exception $e) {}


            // Clean password hash before partial export
            unset($export['user']['user_pass']);

            // Send Download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="data-export-user-' . $uid . '.json"');
            echo json_encode($export, JSON_PRETTY_PRINT);
            exit;
        }
    }

    // SEARCH
    if (!empty($searchEmail)) {
        $userData = $db->execute("SELECT * FROM {$db->getPrefix()}users WHERE user_email = ?", [$searchEmail])->fetch();
        if ($userData) {
            // Count related items
            $relatedData['meta_count'] = $db->execute("SELECT COUNT(*) as c FROM {$db->getPrefix()}user_meta WHERE user_id = ?", [$userData->id])->fetch()->c;
            try {
                $relatedData['order_count'] = $db->execute("SELECT COUNT(*) as c FROM {$db->getPrefix()}orders WHERE user_id = ?", [$userData->id])->fetch()->c;
            } catch (Exception $e) { $relatedData['order_count'] = 0; }
        } else {
            $message = 'Kein Benutzer mit dieser E-Mail gefunden.';
            $messageType = 'error';
        }
    }
}

$csrfToken = $security->generateToken('data_access');

require_once __DIR__ . '/partials/admin-menu.php';
renderAdminLayoutStart('Recht auf Auskunft', 'data-access');
?>
<div class="admin-page-header">
    <div>
        <h2>👤 Recht auf Auskunft (Art. 15 DSGVO)</h2>
        <p>Erstellen Sie maschinenlesbare Datenauszüge für Nutzeranfragen.</p>
    </div>
</div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- SEARCH FORM -->
        <div class="admin-card">
            <h3>🔍 Benutzer suchen</h3>
            <form method="post">
                <div class="form-group">
                    <label class="form-label">E-Mail Adresse des Nutzers</label>
                    <div style="display:flex; gap:1rem;">
                        <input type="email" name="email" class="form-control" placeholder="user@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        <button type="submit" class="btn btn-primary">Suchen</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- RESULT SECTION -->
        <?php if ($userData): ?>
        <div class="admin-card" style="border-top: 4px solid var(--admin-primary);">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                    <h3 style="margin-top:0;">Gefundener Datensatz</h3>
                    <p style="color:#64748b; margin:0;">Benutzer-ID: #<?php echo $userData->id; ?></p>
                </div>
                <span style="background:#dcfce7; color:#166534; padding:0.25rem 0.75rem; border-radius:99px; font-weight:600; font-size:0.8rem;">Aktiv</span>
            </div>

            <div class="data-grid">
                <div class="data-box">
                    <span class="data-label">Name</span>
                    <span class="data-value"><?php echo htmlspecialchars($userData->display_name); ?></span>
                </div>
                <div class="data-box">
                    <span class="data-label">E-Mail</span>
                    <span class="data-value"><?php echo htmlspecialchars($userData->user_email); ?></span>
                </div>
                <div class="data-box">
                    <span class="data-label">Registriert am</span>
                    <span class="data-value"><?php echo htmlspecialchars($userData->user_registered); ?></span>
                </div>
                <div class="data-box">
                    <span class="data-label">Metadaten Einträge</span>
                    <span class="data-value"><?php echo $relatedData['meta_count']; ?></span>
                </div>
                <div class="data-box">
                    <span class="data-label">Bestellungen</span>
                    <span class="data-value"><?php echo $relatedData['order_count']; ?></span>
                </div>
            </div>

            <div style="margin-top: 2rem; border-top: 1px solid #e2e8f0; padding-top: 1.5rem;">
                <h4>Datenauszug generieren</h4>
                <p style="font-size:0.9rem; color:#64748b; margin-bottom:1rem;">
                    Erstellt eine vollständige JSON-Datei aller gespeicherten Daten zu diesem Nutzer. Diese Datei kann dem Nutzer gemäß DSGVO Art. 15 zur Verfügung gestellt werden.
                </p>
                
                <form method="post">
                    <input type="hidden" name="action" value="export_json">
                    <input type="hidden" name="user_id" value="<?php echo $userData->id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <button type="submit" class="btn btn-primary">
                        📥 JSON-Export herunterladen
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

<?php renderAdminLayoutEnd(); ?>
