<?php
/**
 * Member Subscription View
 * 
 * Variables provided by subscription.php controller:
 * - $subscription        : object|null - Active subscription data
 * - $allPlans            : array       - Purchasable packages (objects)
 * - $paymentInfo         : array       - Admin settings for payments
 * - $permissions         : array       - User permission strings
 * - $statusBadges        : array       - Status => CSS-class map
 * - $user                : object      - Current user (injected by MemberController::render)
 * - $showUpgrade         : bool        - Whether to highlight upgrade options
 * 
 * @package CMSv2\Member\Views
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure payment info is an array to prevent "stdClass as array" errors
$paymentInfo = (array) $paymentInfo;

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mein Abo - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/member.css">
    <?php renderMemberSidebarStyles(); ?>
    <style>
        .payment-info-box {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        .payment-method-block {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .payment-method-block:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .pay-label { font-weight: 600; color: #1e293b; display: block; margin-bottom: 0.5rem; }
        .pay-value { background: #f8fafc; padding: 1rem; border-radius: 6px; font-family: monospace; white-space: pre-wrap; color: #334155; }
        .plan-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            padding: 1.5rem;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
        }
        .plan-card:hover {
            border-color: #6366f1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .plan-features-list {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
            flex-grow: 1;
        }
        .plan-features-list li {
            padding: 0.35rem 0;
            font-size: 0.9rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .check-icon { color: #10b981; }
        .cross-icon { color: #ef4444; }
    </style>
</head>
<body class="member-body">
    
    <?php renderMemberSidebar('subscription'); ?>
    
    <!-- Main Content -->
    <div class="member-content">
        
        <!-- Page Header -->
        <div class="member-page-header">
            <div>
                <h1>Meine Mitgliedschaft</h1>
                <p class="member-page-subtitle">Übersicht über dein Abo und verfügbare Pläne</p>
            </div>
        </div>
        
        <!-- Current Subscription -->
        <?php if ($subscription): 
            $planName = $subscription->package_name ?? $subscription->name ?? 'Unbekanntes Paket';
            $price = $subscription->billing_cycle === 'yearly' ? ($subscription->price_yearly ?? 0) : ($subscription->price_monthly ?? 0);
            $cycle = $subscription->billing_cycle === 'yearly' ? 'Jahr' : 'Monat';
            $statusClass = $statusBadges[$subscription->status] ?? 'info';
        ?>
            <div class="member-card member-subscription-card">
                <div class="member-card-header">
                    <h3>Aktuelles Abonnement</h3>
                    <span class="member-badge member-badge-<?php echo $statusClass; ?>">
                        <?php echo ucfirst($subscription->status); ?>
                    </span>
                </div>
                
                <div class="member-card-body">
                    <div class="member-subscription-details">
                        <div class="subscription-main">
                            <div class="subscription-icon">💎</div>
                            <div class="subscription-info">
                                <h2><?php echo htmlspecialchars($planName); ?></h2>
                                <p class="subscription-description">
                                    <?php echo htmlspecialchars($subscription->description ?? ''); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="subscription-meta-grid">
                            <div class="subscription-meta-item">
                                <span class="meta-label">Preis:</span>
                                <span class="meta-value">
                                    <?php echo number_format((float)$price, 2); ?> € / <?php echo $cycle; ?>
                                </span>
                            </div>
                            
                            <div class="subscription-meta-item">
                                <span class="meta-label">Start:</span>
                                <span class="meta-value">
                                    <?php echo date('d.m.Y', strtotime($subscription->created_at)); ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($subscription->end_date)): ?>
                                <div class="subscription-meta-item">
                                    <span class="meta-label">Läuft aus:</span>
                                    <span class="meta-value">
                                        <?php echo date('d.m.Y', strtotime($subscription->end_date)); ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <div class="subscription-meta-item">
                                    <span class="meta-label">Laufzeit:</span>
                                    <span class="meta-value">Unbegrenzt</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Limit Info -->
                        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed #e2e8f0;">
                            <h4 style="font-size:0.9rem; margin-bottom:0.75rem;">Deine Limits:</h4>
                            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                                <div><strong>Experten:</strong> <?php echo $subscription->limit_experts == -1 ? '∞' : $subscription->limit_experts; ?></div>
                                <div><strong>Firmen:</strong> <?php echo $subscription->limit_companies == -1 ? '∞' : $subscription->limit_companies; ?></div>
                                <div><strong>Speicher:</strong> <?php echo $subscription->limit_storage_mb ?? 0; ?> MB</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- No active subscription -->
            <div class="member-card">
                <div class="member-card-body">
                    <div class="member-empty-state">
                        <div class="empty-icon">📭</div>
                        <h3>Kein aktives Abonnement</h3>
                        <p>Du hast derzeit kein aktives Abo. Wähle unten ein Paket aus!</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Payment Info Section (Always visible if data exists) -->
        <?php if (!empty($paymentInfo['bank']) || !empty($paymentInfo['paypal']) || !empty($paymentInfo['note'])): ?>
            <div class="member-section" style="margin-top: 2rem;">
                <h2 class="member-section-title">💳 Zahlungsinformationen</h2>
                <div class="payment-info-box">
                    <?php if (!empty($paymentInfo['note'])): ?>
                        <div class="payment-method-block">
                            <div style="background:#fff7ed; color:#9a3412; padding:1rem; border-radius:6px; border:1px solid #ffedd5;">
                                <strong>Hinweis:</strong> <?php echo nl2br(htmlspecialchars($paymentInfo['note'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                        <?php if (!empty($paymentInfo['bank'])): ?>
                            <div class="payment-method-block">
                                <span class="pay-label">🏛️ Bankverbindung (Überweisung)</span>
                                <div class="pay-value"><?php echo htmlspecialchars($paymentInfo['bank']); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($paymentInfo['paypal'])): ?>
                            <div class="payment-method-block">
                                <span class="pay-label">🅿️ PayPal</span>
                                <div class="pay-value"><?php echo htmlspecialchars($paymentInfo['paypal']); ?></div>
                                <?php if (filter_var($paymentInfo['paypal'], FILTER_VALIDATE_URL)): ?>
                                    <a href="<?php echo htmlspecialchars($paymentInfo['paypal']); ?>" target="_blank" class="btn-sm btn-primary-ghost" style="margin-top:0.5rem; display:inline-block;">
                                        Jetzt bezahlen &rarr;
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Available Plans -->
        <div class="member-section" style="margin-top: 3rem;">
            <h2 class="member-section-title">Verfügbare Pakete</h2>
            
            <div class="member-packages-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                <?php foreach ($allPlans as $plan): 
                    $isCurrent = ($subscription && $subscription->plan_id == $plan->id);
                ?>
                    <div class="plan-card <?php echo $isCurrent ? 'current-plan' : ''; ?>" style="<?php echo $isCurrent ? 'border-color:var(--member-primary); background:#fcfdff;' : ''; ?>">
                        <?php if ($isCurrent): ?>
                            <div style="background:var(--member-primary); color:white; font-size:0.75rem; padding:0.25rem 0.5rem; border-radius:4px; align-self:flex-start; margin-bottom:0.5rem;">
                                Aktuelles Paket
                            </div>
                        <?php endif; ?>
                        
                        <h3 style="margin:0; font-size:1.25rem;"><?php echo htmlspecialchars($plan->name); ?></h3>
                        <div style="font-size:1.5rem; font-weight:700; color:var(--member-text); margin:0.5rem 0;">
                            €<?php echo number_format((float)$plan->price_monthly, 2); ?> <span style="font-size:0.875rem; color:#64748b; font-weight:400;">/Monat</span>
                        </div>
                        <p style="font-size:0.9rem; color:#64748b; margin-bottom:1rem;">
                            <?php echo htmlspecialchars($plan->description ?? ''); ?>
                        </p>
                        
                        <div style="border-top:1px solid #f1f5f9; margin-top:0.5rem; padding-top:0.5rem;">
                            <ul class="plan-features-list">
                                <li><span class="check-icon">✓</span> <?php echo $plan->limit_experts == -1 ? 'Unbegrenzt Experten' : $plan->limit_experts . ' Experten-Profile'; ?></li>
                                <li><span class="check-icon">✓</span> <?php echo $plan->limit_companies == -1 ? 'Unbegrenzt Firmen' : $plan->limit_companies . ' Firmen-Profile'; ?></li>
                                <li><span class="check-icon">✓</span> <?php echo $plan->limit_storage_mb; ?> MB Speicher</li>
                                <?php if ($plan->feature_analytics): ?><li><span class="check-icon">✓</span> Analytics</li><?php endif; ?>
                                <?php if ($plan->feature_api_access): ?><li><span class="check-icon">✓</span> API Zugang</li><?php endif; ?>
                            </ul>
                        </div>
                        
                        <div style="margin-top:auto; padding-top:1rem;">
                            <?php if ($isCurrent): ?>
                                <button disabled class="member-btn" style="width:100%; opacity:0.6; cursor:default;">Aktiv</button>
                            <?php else: ?>
                                <a href="<?php echo SITE_URL; ?>/order?plan_id=<?php echo $plan->id; ?>" class="member-btn member-btn-secondary" style="width:100%; text-align:center; display:block;">
                                    Paket wählen
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
    </div>
    
</body>
</html>
