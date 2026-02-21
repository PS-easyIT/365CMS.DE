<?php
/**
 * Public Order / Checkout Page
 * 
 * @package 365CMS
 */

declare(strict_types=1);

// Load configuration
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    // If config doesn't exist, redirect to install
    header('Location: install.php');
    exit;
}

// Load autoloader
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Database;
use CMS\Security;
use CMS\SubscriptionManager;
use CMS\ThemeManager;

// Get parameters
$planId = isset($_GET['plan']) ? (int)$_GET['plan'] : 0;
$billing = isset($_GET['billing']) ? $_GET['billing'] : 'monthly';

// Services
$db = Database::instance();
$auth = Auth::instance();
$subManager = SubscriptionManager::instance();

// Fetch Plan
$plan = $db->fetch("SELECT * FROM {$db->prefix()}subscription_plans WHERE id = ?", [$planId]);

if (!$plan) {
    // Redirect to packages if no plan selected
    // Assuming there is a packages page, or back to home
    header('Location: ' . SITE_URL . '/#pricing');
    exit;
}

$user = $auth->isLoggedIn() ? $auth->currentUser() : null;

// Handle Form Submission
$error = '';
$success = false;
$orderId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'checkout_process')) {
        $error = 'Sicherheitsüberprüfung fehlgeschlagen. Bitte versuchen Sie es erneut.';
    } else {
        // Validate inputs
        $contactData = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'company' => trim($_POST['company'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'zip' => trim($_POST['zip'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'country' => trim($_POST['country'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
        ];

        if (empty($contactData['last_name']) || empty($contactData['email']) || empty($contactData['address'])) {
            $error = 'Bitte füllen Sie alle Pflichtfelder aus (*).';
        } else {
            // Generate Order Number
            // Fetch format from settings or default
            $format = $db->fetchColumn("SELECT option_value FROM {$db->prefix()}settings WHERE option_name = 'setting_order_number_format'") ?: 'BST{Y}{M}-{ID}';
            
            // We need a temporary ID or just use a random string and update later if ID is needed for format
            // Simple generation for now:
            $nextId = $db->fetchColumn("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$db->prefix()}orders'");
            if (!$nextId) {
                // Fallback if permission denied
                $maxId = $db->fetchColumn("SELECT MAX(id) FROM {$db->prefix()}orders");
                $nextId = ($maxId ? $maxId : 0) + 1;
            }

            $placeholders = [
                '{Y}' => date('Y'),
                '{M}' => date('m'),
                '{D}' => date('d'),
                '{ID}' => str_pad((string)$nextId, 4, '0', STR_PAD_LEFT),
                '{R}' => strtoupper(substr(uniqid(), -4))
            ];
            
            $orderNumber = str_replace(array_keys($placeholders), array_values($placeholders), $format);

            // Price calculation
            $price = ($billing === 'yearly') ? $plan['price_yearly'] : $plan['price_monthly'];
            
            // Insert Order
            try {
                $db->insert('orders', [
                    'order_number' => $orderNumber,
                    'user_id' => $user ? $user->id : null,
                    'plan_id' => $plan['id'],
                    'billing_cycle' => $billing,
                    'amount' => $price,
                    'currency' => 'EUR',
                    'status' => 'pending',
                    'contact_data' => json_encode($contactData),
                    'payment_method' => 'invoice', // Default for now
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                $orderId = $db->lastInsertId();
                $success = true;
                
                // Here you would send an email
                
            } catch (\Exception $e) {
                $error = 'Fehler bei der Bestellung: ' . $e->getMessage();
            }
        }
    }
}

// Render Page
ob_start();
?>

<div class="checkout-container" style="max-width: 1000px; margin: 40px auto; padding: 20px;">
    
    <?php if ($success): ?>
        <div class="checkout-success" style="background: white; padding: 40px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 60px; color: #10b981; margin-bottom: 20px;">✓</div>
            <h1>Vielen Dank für Ihre Bestellung!</h1>
            <p class="lead">Ihre Bestellnummer lautet: <strong><?php echo htmlspecialchars($orderNumber); ?></strong></p>
            <p>Wir haben Ihnen eine Bestätigung an <strong><?php echo htmlspecialchars($contactData['email']); ?></strong> gesendet.</p>
            
            <div class="order-actions" style="margin-top: 30px; display: flex; gap: 10px; justify-content: center;">
                <button onclick="window.print()" class="btn btn-secondary">🖨️ Drucken / PDF</button>
            </div>
            
            <div style="margin-top: 40px;">
                <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">Zurück zur Startseite</a>
            </div>
        </div>
    <?php else: ?>
    
    <h1 style="margin-bottom: 30px;">Bestellung abschließen</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger" style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="checkout-grid" style="display: grid; grid-template-columns: 1fr 350px; gap: 40px;">
        
        <!-- Left: Form -->
        <div class="checkout-form-section">
            <div class="card" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px;">Rechnungsdaten</h3>
                
                <?php if (!$user): ?>
                    <div class="alert alert-info" style="background: #eff6ff; color: #1e40af; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem;">
                        <a href="<?php echo SITE_URL; ?>/login?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" style="color: inherit; font-weight: bold;">Melden Sie sich an</a>, um Ihre gespeicherten Daten zu verwenden.
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::instance()->generateToken('checkout_process'); ?>">
                    
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Vorname</label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ($user->first_name ?? '')); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Nachname *</label>
                            <input type="text" name="last_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ($user->last_name ?? '')); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Firma</label>
                        <input type="text" name="company" class="form-control" value="<?php echo htmlspecialchars($_POST['company'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Adresse *</label>
                        <input type="text" name="address" class="form-control" required value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">PLZ *</label>
                            <input type="text" name="zip" class="form-control" required value="<?php echo htmlspecialchars($_POST['zip'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Stadt *</label>
                            <input type="text" name="city" class="form-control" required value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Land</label>
                        <select name="country" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            <option value="DE">Deutschland</option>
                            <option value="AT">Österreich</option>
                            <option value="CH">Schweiz</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">E-Mail Adresse *</label>
                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ($user->email ?? '')); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary" style="background: #2563eb; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; width: 100%;">Zahlungspflichtig bestellen</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right: Summary -->
        <div class="checkout-summary-section">
            <div class="card" style="background: #f8fafc; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; position: sticky; top: 20px;">
                <h3 style="margin-top: 0; margin-bottom: 20px;">Bestellübersicht</h3>
                
                <div class="summary-item" style="margin-bottom: 15px;">
                    <strong style="display: block; color: #64748b; font-size: 0.9rem;">Paket</strong>
                    <div style="font-size: 1.1rem; font-weight: 600;"><?php echo htmlspecialchars($plan['name']); ?></div>
                </div>

                <div class="summary-item" style="margin-bottom: 15px;">
                    <strong style="display: block; color: #64748b; font-size: 0.9rem;">Abrechnungszeitraum</strong>
                    <div><?php echo $billing === 'yearly' ? 'Jährlich' : 'Monatlich'; ?></div>
                </div>

                <hr style="border-color: #cbd5e1; margin: 20px 0;">

                <div class="total" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <span style="font-weight: 600;">Gesamtbetrag:</span>
                    <span style="font-size: 1.5rem; font-weight: bold; color: #2563eb;">
                        <?php echo number_format(($billing === 'yearly' ? $plan['price_yearly'] : $plan['price_monthly']), 2, ',', '.'); ?> €
                    </span>
                </div>
                <div style="text-align: right; color: #64748b; font-size: 0.85rem;">
                    inkl. MwSt.
                </div>
            </div>
        </div>

    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();

// Render with Theme
ThemeManager::instance()->render('page', [
    'page' => [
        'title' => 'Kasse',
        'content' => $content
    ]
]);
?>