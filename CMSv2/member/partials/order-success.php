<?php
/**
 * Public Order Success View
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Bestellung erfolgreich - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 4rem auto;
            text-align: center;
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .success-icon {
            font-size: 4rem;
            color: #16a34a;
            margin-bottom: 1rem;
        }
        .order-details {
            text-align: left;
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 6px;
        }
        .payment-info {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px dashed #cbd5e1;
            text-align: left;
        }
    </style>
</head>
<body style="background-color: #f1f5f9;">

<div class="success-container">
    <div class="success-icon">✅</div>
    <h1>Vielen Dank für Ihre Bestellung!</h1>
    <p>Wir haben Ihre Bestellung erhalten. Eine Bestätigung wurde an Ihre E-Mail-Adresse gesendet.</p>
    
    <div class="order-details">
        <p><strong>Bestellnummer:</strong> #<?php echo htmlspecialchars($order->order_number); ?></p>
        <p><strong>Betrag:</strong> <?php echo number_format((float)$order->total, 2); ?> €</p>
        <p><strong>Zahlungsmethode:</strong> <?php echo htmlspecialchars($order->payment_method === 'paypal' ? 'PayPal' : 'Überweisung'); ?></p>
    </div>
    
    <div class="payment-info">
        <h3>Zahlungsinformationen</h3>
        <?php if ($order->payment_method === 'bank_transfer' && !empty($paymentInfo['bank'])): ?>
            <p>Bitte überweisen Sie den Betrag auf folgendes Konto:</p>
            <div style="background:#f1f5f9; padding:1rem; border-radius:4px; font-family:monospace;">
                <?php echo nl2br(htmlspecialchars($paymentInfo['bank'])); ?>
            </div>
            <p style="margin-top:0.5rem; font-size:0.9rem;">Verwendungszweck: <strong><?php echo htmlspecialchars($order->order_number); ?></strong></p>
        <?php elseif ($order->payment_method === 'paypal' && !empty($paymentInfo['paypal'])): ?>
            <p>Bitte zahlen Sie via PayPal:</p>
            <a href="<?php echo htmlspecialchars($paymentInfo['paypal']); ?>" class="btn-primary" style="display:inline-block; padding:0.75rem 1.5rem; background:#0070ba; color:white; text-decoration:none; border-radius:4px;">Jetzt mit PayPal bezahlen</a>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 2rem;">
        <a href="<?php echo SITE_URL; ?>/member" style="color:#2563eb; text-decoration:underline;">Zurück zum Mitgliederbereich</a>
    </div>
</div>

</body>
</html>
