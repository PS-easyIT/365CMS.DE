<?php
/**
 * Admin Subscription Settings
 * 
 * @package CMSv2\Admin
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/partials/admin-menu.php';

$auth = CMS\Auth::instance();
if (!$auth->isAdmin()) {
    header('Location: ' . SITE_URL . '/login');
    exit;
}

$db = CMS\Database::instance();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '')) {
        if ($_POST['action'] === 'save_settings') {
            $format = sanitize_text_field($_POST['order_number_format']);
            
            // Check if setting exists
            $existing = $db->fetch("SELECT id FROM {$db->prefix()}settings WHERE option_name = 'setting_order_number_format'");
            if ($existing) {
                $db->update('settings', ['option_value' => $format], ['option_name' => 'setting_order_number_format']);
            } else {
                $db->insert('settings', ['option_name' => 'setting_order_number_format', 'option_value' => $format]);
            }
            $message = 'Einstellungen gespeichert.';
        }
    }
}

// Get current setting
$currentFormat = $db->fetchColumn("SELECT option_value FROM {$db->prefix()}settings WHERE option_name = 'setting_order_number_format'") ?: 'BST{Y}{M}-{ID}';

$exampleFormats = [
    'BST{Y}{M}-{ID}' => 'BST202602-0001 (Standard)',
    'INV-{Y}-{ID}' => 'INV-2026-0001',
    '{Y}{M}{D}-{R}' => '20260220-X8J2 (Zufallscode)',
    'ABO-{ID}' => 'ABO-0001'
];

renderAdminLayoutStart('Abo-Einstellungen', 'subscription-settings');
?>

        <div class="admin-page-header">
            <div>
                <h2>⚙️ Abo-Einstellungen</h2>
                <p>Bestellnummern-Format und weitere Abo-Konfiguration.</p>
            </div>
        </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="admin-card">
                <h3>🔢 Bestellnummern-Format</h3>
                <p>Definieren Sie hier das Format für neue Bestellnummern. Änderungen wirken sich nur auf zukünftige Bestellungen aus.</p>
                
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo CMS\Security::instance()->generateToken(); ?>">
                    <input type="hidden" name="action" value="save_settings">
                    
                    <div class="form-group">
                        <label>Format-Vorlage wählen:</label>
                        <select onchange="document.getElementById('formatInput').value = this.value" class="form-control">
                            <option value="">-- Bitte wählen --</option>
                            <?php foreach ($exampleFormats as $fmt => $desc): ?>
                                <option value="<?php echo htmlspecialchars($fmt); ?>"><?php echo htmlspecialchars($desc); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Aktuelles Format:</label>
                        <input type="text" name="order_number_format" id="formatInput" value="<?php echo htmlspecialchars($currentFormat); ?>" class="form-control" readonly style="background: #f1f5f9; cursor: not-allowed;">
                        <small class="form-text">Das Format kann nur aus den vordefinierten Optionen gewählt werden.</small>
                    </div>

                    <div class="info-box">
                        <h4>Verfügbare Platzhalter:</h4>
                        <ul>
                            <li><code>{Y}</code> - Jahr (z.B. 2026)</li>
                            <li><code>{M}</code> - Monat (z.B. 02)</li>
                            <li><code>{D}</code> - Tag (z.B. 20)</li>
                            <li><code>{ID}</code> - Fortlaufende Nummer (z.B. 0001)</li>
                            <li><code>{R}</code> - Zufällige 4 Zeichen (z.B. AB12)</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-primary">💾 Speichern</button>
                </form>
            </div>
<?php renderAdminLayoutEnd(); ?>
