<?php
/**
 * Public Order Process Controller
 * 
 * Handles subscription checkout for logged-in users.
 * 
 * @package CMSv2\Member
 */

declare(strict_types=1);

use CMS\Auth;
use CMS\Database;
use CMS\SubscriptionManager;

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(__DIR__)) . '/');
}

// Ensure core is loaded if accessed directly (though typically included via index.php route)
if (!class_exists('CMS\Database')) {
    require_once dirname(__DIR__) . '/core/autoload.php';
    require_once dirname(__DIR__) . '/config.php';
}

$db = Database::instance();
$prefix = $db->prefix; // Ensure prefix is available
$auth = Auth::instance();
$subMgr = SubscriptionManager::instance();

// 1. Get Plan
$planId = filter_input(INPUT_GET, 'plan_id', FILTER_VALIDATE_INT);
$plan = $planId ? $subMgr->getPlan($planId) : null;

if (!$plan) {
    // Falls kein Plan ausgewählt, zurück zur Übersicht
    header('Location: ' . SITE_URL . '/member/subscription');
    exit;
}

// 2. Auth Check - User MUST be logged in for now (simplifies logic)
if (!$auth->isLoggedIn()) {
    // Store return URL
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . SITE_URL . '/login.php?message=order_login');
    exit;
}

$currentUser = $auth->currentUser();
$errors = [];
$successOrder = null;

// 3. Pre-fill Data
$formData = [
    'forename' => '',
    'lastname' => '',
    'company' => '',
    'email' => $currentUser->email ?? '',
    'phone' => '',
    'street' => '',
    'zip' => '',
    'city' => '',
    'country' => 'Deutschland',
    'billing_cycle' => 'monthly'
];

// Try to get existing address from user meta
$meta = $db->get_results("SELECT meta_key, meta_value FROM {$prefix}user_meta WHERE user_id = " . (int)$currentUser->id);
$userMeta = [];
if ($meta) {
    foreach ($meta as $row) {
        $userMeta[$row->meta_key] = $row->meta_value;
    }
}

if (!empty($userMeta['first_name'])) $formData['forename'] = $userMeta['first_name'];
if (!empty($userMeta['last_name'])) $formData['lastname'] = $userMeta['last_name'];
if (!empty($userMeta['billing_company'])) $formData['company'] = $userMeta['billing_company'];
if (!empty($userMeta['billing_address_1'])) $formData['street'] = $userMeta['billing_address_1'];
if (!empty($userMeta['billing_postcode'])) $formData['zip'] = $userMeta['billing_postcode'];
if (!empty($userMeta['billing_city'])) $formData['city'] = $userMeta['billing_city'];
if (!empty($userMeta['billing_country'])) $formData['country'] = $userMeta['billing_country'];
if (!empty($userMeta['billing_phone'])) $formData['phone'] = $userMeta['billing_phone'];

// Ensure all are strings for htmlspecialchars
array_walk($formData, function(&$value) {
    if ($value === null) $value = '';
});

// Override with POST data if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['forename'] = trim($_POST['forename'] ?? '');
    $formData['lastname'] = trim($_POST['lastname'] ?? '');
    $formData['company'] = trim($_POST['company'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $formData['street'] = trim($_POST['street'] ?? '');
    $formData['zip'] = trim($_POST['zip'] ?? '');
    $formData['city'] = trim($_POST['city'] ?? '');
    $formData['country'] = trim($_POST['country'] ?? '');
    $formData['billing_cycle'] = $_POST['billing_cycle'] ?? 'monthly';
    
    // Validation
    if (empty($formData['forename'])) $errors[] = 'Vorname ist Pflicht.';
    if (empty($formData['lastname'])) $errors[] = 'Nachname ist Pflicht.';
    if (empty($formData['street'])) $errors[] = 'Straße ist Pflicht.';
    if (empty($formData['zip'])) $errors[] = 'PLZ ist Pflicht.';
    if (empty($formData['city'])) $errors[] = 'Stadt ist Pflicht.';
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Gültige E-Mail ist Pflicht.';

    if (empty($errors)) {
        // Prepare Order Data
        $price = ($formData['billing_cycle'] === 'yearly') ? $plan->price_yearly : $plan->price_monthly;
        
        $insertData = [
            'order_number' => 'TEMP-' . uniqid(), // Temp, update later
            'user_id' => $currentUser->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'total_amount' => $price,
            'currency' => 'EUR',
            'payment_method' => 'bank_transfer', // Default for now
            'billing_cycle' => $formData['billing_cycle'],
            'forename' => $formData['forename'],
            'lastname' => $formData['lastname'],
            'company' => $formData['company'],
            'email' => $formData['email'],
            'phone' => $formData['phone'],
            'street' => $formData['street'],
            'zip' => $formData['zip'],
            'city' => $formData['city'],
            'country' => $formData['country'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Execute Insert
        // Note: Using simpler query building since $db->insert might not handle all fields automatically if strict
        $keys = array_keys($insertData);
        $fields = implode(', ', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        $values = array_values($insertData);
        
        try {
            $stmt = $db->prepare("INSERT INTO {$prefix}orders ($fields) VALUES ($placeholders)");
            if ($stmt->execute($values)) {
                $orderId = $db->pdo->lastInsertId();
                
                // Generate Real Order Number
                // Get format from settings or default: BST365CMS{ID}
                $settingsFormat = $db->get_var("SELECT option_value FROM {$prefix}settings WHERE option_name = 'order_number_format'");
                $orderFormat = $settingsFormat ? $settingsFormat : 'BST365CMS{ID}';
                
                $orderNumber = str_replace('{ID}', str_pad((string)$orderId, 5, '0', STR_PAD_LEFT), $orderFormat);
                
                // Update Order Number
                $updStmt = $db->prepare("UPDATE {$prefix}orders SET order_number = ? WHERE id = ?");
                $updStmt->execute([$orderNumber, $orderId]);
                
                // Save address to user meta for next time (Simple update)
                $metaUpdates = [
                    'billing_company' => $formData['company'],
                    'billing_address_1' => $formData['street'],
                    'billing_postcode' => $formData['zip'],
                    'billing_city' => $formData['city'],
                    'billing_country' => $formData['country'],
                    'billing_phone' => $formData['phone'],
                    'first_name' => $formData['forename'],
                    'last_name' => $formData['lastname']
                ];
                
                foreach ($metaUpdates as $key => $val) {
                    // Check if exists
                    $exists = $db->get_var("SELECT id FROM {$prefix}user_meta WHERE user_id = ? AND meta_key = ?", [$currentUser->id, $key]);
                    if ($exists) {
                        $st = $db->prepare("UPDATE {$prefix}user_meta SET meta_value = ? WHERE user_id = ? AND meta_key = ?");
                        $st->execute([$val, $currentUser->id, $key]);
                    } else {
                        $st = $db->prepare("INSERT INTO {$prefix}user_meta (user_id, meta_key, meta_value) VALUES (?, ?, ?)");
                        $st->execute([$currentUser->id, $key, $val]);
                    }
                }
                
                $successOrder = (object)array_merge($insertData, ['id' => $orderId, 'order_number' => $orderNumber]);
            } else {
                $errors[] = 'Datenbankfehler beim Erstellen der Bestellung.';
            }
        } catch (Exception $e) {
            $errors[] = 'Fehler: ' . $e->getMessage();
        }
    }
}

// 4. View Rendering
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestellung abschließen - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/member.css">
    <style>
        .order-container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .order-step { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 30px; margin-bottom: 20px; }
        .plan-summary { background: #f8fafc; padding: 20px; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #475569; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 4px; }
        .btn-primary { background: #2563eb; color: white; padding: 12px 24px; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; width: 100%; }
        .btn-primary:hover { background: #1d4ed8; }
        .error-box { background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        
        /* Success View */
        .success-icon { font-size: 48px; color: #22c55e; text-align: center; margin-bottom: 20px; }
        .order-actions { display: flex; gap: 10px; justify-content: center; margin-top: 30px; }
        .btn-outline { background: white; border: 1px solid #cbd5e1; color: #475569; padding: 8px 16px; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn-outline:hover { background: #f1f5f9; }
        
        .bank-details { background: #eff6ff; padding: 20px; border-radius: 6px; border: 1px solid #bfdbfe; margin-top: 20px; }
        .bank-row { display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #dbeafe; padding-bottom: 5px; }
        .bank-row:last-child { border-bottom: none; }
    </style>
</head>
<body class="bg-light">

<header class="site-header">
    <div class="container">
        <a href="<?php echo SITE_URL; ?>" class="logo"><?php echo htmlspecialchars(SITE_NAME); ?></a>
    </div>
</header>

<main class="order-container">
    
    <?php if ($successOrder): ?>
        <!-- SUCCESS VIEW -->
        <div class="order-step text-center">
            <div class="success-icon">🎉</div>
            <h1 class="h2 mb-4">Vielen Dank für Ihre Bestellung!</h1>
            <p class="text-muted mb-4">Ihre Bestellung wurde erfolgreich entgegengenommen. Bitte überweisen Sie den Betrag auf unser Bankkonto.</p>
            
            <div class="plan-summary" style="text-align: left;">
                <h3 class="h5 mb-3">Bestellübersicht</h3>
                <div class="bank-row"><span>Bestellnummer:</span> <strong><?php echo $successOrder->order_number; ?></strong></div>
                <div class="bank-row"><span>Paket:</span> <span><?php echo htmlspecialchars($plan->name); ?></span></div>
                <div class="bank-row"><span>Zyklus:</span> <span><?php echo $successOrder->billing_cycle === 'yearly' ? 'Jährlich' : 'Monatlich'; ?></span></div>
                <div class="bank-row"><span>Betrag:</span> <strong><?php echo number_format((float)$successOrder->total_amount, 2, ',', '.'); ?> EUR</strong></div>
            </div>
            
            <div class="bank-details" style="text-align: left;">
                <h3 class="h5 mb-3">Bankverbindung für Überweisung</h3>
                <div class="bank-row"><span>Empfänger:</span> <span>365CMS GmbH</span></div>
                <div class="bank-row"><span>IBAN:</span> <span>DE12 3456 7890 0000 00</span></div>
                <div class="bank-row"><span>BIC:</span> <span>ABCDEF12</span></div>
                <div class="bank-row"><span>Verwendungszweck:</span> <strong><?php echo $successOrder->order_number; ?></strong></div>
                <p class="text-sm mt-3 text-muted">Die Freischaltung erfolgt automatisch nach Zahlungseingang.</p>
            </div>
            
            <div class="order-actions">
                <button onclick="window.print()" class="btn-outline">🖨️ Drucken</button>
                <a href="#" class="btn-outline" onclick="alert('PDF-Generierung wird implementiert'); return false;">📄 Als PDF speichern</a>
                <a href="mailto:?subject=Rechnung%20<?php echo $successOrder->order_number; ?>&body=Hallo,%0A%0Ahier%20ist%20die%20Rechnung%20für%20Bestellung%20<?php echo $successOrder->order_number; ?>." class="btn-outline">📧 Per E-Mail senden</a>
            </div>
            
            <div class="mt-4">
                <a href="<?php echo SITE_URL; ?>/member/orders" class="text-primary">Zu meinen Bestellungen</a>
            </div>
        </div>
        
    <?php else: ?>
        <!-- ORDER FORM VIEW -->
        <h1 class="h2 mb-4">Bestellung abschließen</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <strong class="d-block mb-1">Bitte korrigieren:</strong>
                <ul class="m-0 pl-4">
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="order-step">
                <h2 class="h4 mb-4">1. Gewähltes Paket</h2>
                <div class="plan-summary d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="h5 m-0 d-block"><?php echo htmlspecialchars($plan->name); ?></strong>
                        <p class="text-sm text-muted m-0 mt-1"><?php echo htmlspecialchars($plan->description); ?></p>
                    </div>
                    <div class="text-right">
                        <select name="billing_cycle" class="form-control" style="width:auto;" onchange="updatePrice(this.value)">
                            <option value="monthly" <?php echo $formData['billing_cycle'] === 'monthly' ? 'selected' : ''; ?>>
                                <?php echo number_format((float)$plan->price_monthly, 2, ',', '.'); ?> € / Monat
                            </option>
                            <option value="yearly" <?php echo $formData['billing_cycle'] === 'yearly' ? 'selected' : ''; ?>>
                                <?php echo number_format((float)$plan->price_yearly, 2, ',', '.'); ?> € / Jahr
                            </option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="order-step">
                <h2 class="h4 mb-4">2. Rechnungsdaten</h2>
                <p class="text-muted text-sm mb-4">Bitte überprüfen Sie Ihre Daten. Diese werden für die Rechnung verwendet.</p>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Vorname *</label>
                        <input type="text" name="forename" class="form-control" value="<?php echo htmlspecialchars($formData['forename']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nachname *</label>
                        <input type="text" name="lastname" class="form-control" value="<?php echo htmlspecialchars($formData['lastname']); ?>" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Firma (Optional)</label>
                        <input type="text" name="company" class="form-control" value="<?php echo htmlspecialchars($formData['company']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Straße / Nr. *</label>
                        <input type="text" name="street" class="form-control" value="<?php echo htmlspecialchars($formData['street']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>PLZ / Ort *</label>
                        <div style="display:flex; gap:10px;">
                            <input type="text" name="zip" class="form-control" style="width:80px;" value="<?php echo htmlspecialchars($formData['zip']); ?>" required>
                            <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($formData['city']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Land *</label>
                        <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($formData['country']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Telefon</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($formData['phone']); ?>">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>E-Mail Adresse *</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                        <small class="text-muted">Rechnung und Bestellbestätigung werden an diese Adresse gesendet.</small>
                    </div>
                </div>
            </div>
            
            <div class="order-step">
                <button type="submit" class="btn-primary">Kostenpflichtig bestellen</button>
                <p class="text-center text-sm text-muted mt-3">Mit Ihrer Bestellung akzeptieren Sie unsere AGB und Datenschutzbestimmungen.</p>
            </div>
        </form>
    <?php endif; ?>

</main>

<script>
function updatePrice(cycle) {
    // Optional: Update displayed summary if needed
}
</script>

</body>
</html>
