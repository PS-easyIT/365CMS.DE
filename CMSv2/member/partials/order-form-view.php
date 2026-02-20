<?php
/**
 * Public Order Form View
 */
if (!defined('ABSPATH')) {
    exit;
}

$isLoggedIn = Auth::instance()->isLoggedIn();
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
        .order-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .order-summary {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 6px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body style="background-color: #f1f5f9;">

<div class="order-container">
    <h1 style="margin-bottom: 1.5rem;">Bestellung abschließen</h1>

    <?php if (isset($error) && $error): ?>
        <div style="background:#fee2e2; color:#b91c1c; padding:1rem; border-radius:4px; margin-bottom:1.5rem;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="order-summary">
        <h3>Zusammenfassung</h3>
        <p><strong>Paket:</strong> <?php echo htmlspecialchars($plan->name); ?></p>
        <p><strong>Preis:</strong> <?php echo number_format((float)$plan->price_monthly, 2); ?> € / Monat</p>
        <p><strong>Laufzeit:</strong> <?php echo $plan->billing_cycle === 'yearly' ? 'Jährlich' : 'Monatlich'; ?></p>
    </div>

    <?php if (!$isLoggedIn): ?>
        <div style="background:#fff7ed; color:#9a3412; padding:1rem; border-radius:4px; margin-bottom:1.5rem;">
            <strong>Hinweis:</strong> Sie sind nicht eingeloggt. 
            <a href="<?php echo SITE_URL; ?>/login?redirect_to=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" style="text-decoration:underline;">Bitte loggen Sie sich ein</a>, um die Bestellung Ihrem Konto zuzuordnen.
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Vorname</label>
                <input type="text" name="first_name" class="form-input" value="<?php echo htmlspecialchars($formData['first_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nachname</label>
                <input type="text" name="last_name" class="form-input" value="<?php echo htmlspecialchars($formData['last_name'] ?? ''); ?>" required>
            </div>
        </div>

        <?php if (!$isLoggedIn): ?>
        <div class="form-group">
            <label class="form-label">E-Mail-Adresse</label>
            <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
            <p style="font-size:0.875rem; color:#64748b; margin-top:0.25rem;">Wir erstellen automatisch ein Konto für Sie.</p>
        </div>
        <?php else: ?>
            <div class="form-group">
                <label class="form-label">E-Mail-Adresse</label>
                <input type="email" value="<?php echo htmlspecialchars($auth->currentUser()->user_email); ?>" class="form-input" disabled style="background:#f1f5f9;">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($auth->currentUser()->user_email); ?>">
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label">Firma (Optional)</label>
            <input type="text" name="company" class="form-input" value="<?php echo htmlspecialchars($formData['company'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Adresse</label>
            <input type="text" name="address" class="form-input" value="<?php echo htmlspecialchars($formData['address'] ?? ''); ?>" required>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">PLZ</label>
                <input type="text" name="zip" class="form-input" value="<?php echo htmlspecialchars($formData['zip'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Stadt</label>
                <input type="text" name="city" class="form-input" value="<?php echo htmlspecialchars($formData['city'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Land</label>
            <select name="country" class="form-input">
                <option value="Deutschland" selected>Deutschland</option>
                <option value="Österreich">Österreich</option>
                <option value="Schweiz">Schweiz</option>
            </select>
        </div>

        <div class="form-group" style="margin-top:2rem;">
            <label class="form-label" style="font-size:1.1rem; margin-bottom:1rem;">Zahlungsmethode</label>
            
            <?php if (!empty($paymentInfo['bank'])): ?>
            <label style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem; cursor:pointer;">
                <input type="radio" name="payment_method" value="bank_transfer" checked>
                <span>Überweisung (Rechnung)</span>
            </label>
            <?php endif; ?>
            
            <?php if (!empty($paymentInfo['paypal'])): ?>
            <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                <input type="radio" name="payment_method" value="paypal">
                <span>PayPal</span>
            </label>
            <?php endif; ?>
        </div>

        <div style="margin-top:2rem;">
            <button type="submit" class="member-btn member-btn-primary" style="width:100%; padding:1rem; font-size:1.1rem;">
                Kostenpflichtig bestellen
            </button>
        </div>
    </form>
</div>

</body>
</html>
