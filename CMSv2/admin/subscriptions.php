<?php
/**
 * Subscription Management Admin Page
 * 
 * @package CMSv2\Admin
 */

declare(strict_types=1);

// Load configuration first
require_once dirname(__DIR__) . '/config.php';

// Load autoloader
require_once CORE_PATH . 'autoload.php';

// Load helper functions
require_once ABSPATH . 'includes/functions.php';

use CMS\Auth;
use CMS\Security;
use CMS\Database;
use CMS\SubscriptionManager;

if (!defined('ABSPATH')) {
    exit;
}

// Security check
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$auth = Auth::instance();
$subscriptionManager = SubscriptionManager::instance();

// Handle Actions
$action = $_GET['action'] ?? 'list';
$activeTab = $_GET['tab'] ?? 'plans';
$activeSub = $_GET['sub'] ?? 'users';
$message = '';
$error = '';

/** 
 * Handle Plan Update / Create
 * We need extended logic for updating plans if we want to make them editable
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    
    if (!Security::instance()->verifyToken($csrf, 'subscription_management')) {
        $error = 'Sicherheitsüberprüfung fehlgeschlagen.';
    } else {
        $postAction = $_POST['action'] ?? '';

        if ($postAction === 'assign_group_plan') {
            $groupId     = (int)($_POST['group_id']      ?? 0);
            $groupPlanId = (int)($_POST['group_plan_id'] ?? 0);
            if ($groupId > 0) {
                $db2 = CMS\Database::instance();
                $db2->query(
                    "UPDATE {$db2->getPrefix()}user_groups SET plan_id = ? WHERE id = ?",
                    [$groupPlanId > 0 ? $groupPlanId : null, $groupId]
                );
                $message = 'Gruppenpaket erfolgreich aktualisiert.';
            }
        } elseif ($postAction === 'assign_subscription') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $planId = (int)($_POST['plan_id'] ?? 0);
            $billing = $_POST['billing_cycle'] ?? 'monthly';
            
            if ($subscriptionManager->assignSubscription($userId, $planId, $billing)) {
                $message = 'Abo erfolgreich zugewiesen.';
            } else {
                $error = 'Fehler beim Zuweisen des Abos.';
            }
        } elseif ($postAction === 'create_plan' || $postAction === 'update_plan') {
            
            // Common data
            $planData = [
                'name' => sanitize_text($_POST['name'] ?? ''),
                'slug' => sanitize_text($_POST['slug'] ?? ''),
                'description' => $_POST['description'] ?? '',
                'price_monthly' => (float)($_POST['price_monthly'] ?? 0),
                'price_yearly' => (float)($_POST['price_yearly'] ?? 0),
                'limit_experts' => (int)($_POST['limit_experts'] ?? -1),
                'limit_companies' => (int)($_POST['limit_companies'] ?? -1),
                'limit_events' => (int)($_POST['limit_events'] ?? -1),
                'limit_speakers' => (int)($_POST['limit_speakers'] ?? -1),
                'limit_storage_mb' => (int)($_POST['limit_storage_mb'] ?? 1000),
                'plugin_experts' => isset($_POST['plugin_experts']) ? 1 : 0,
                'plugin_companies' => isset($_POST['plugin_companies']) ? 1 : 0,
                'plugin_events' => isset($_POST['plugin_events']) ? 1 : 0,
                'plugin_speakers' => isset($_POST['plugin_speakers']) ? 1 : 0,
                'feature_analytics' => isset($_POST['feature_analytics']) ? 1 : 0,
                'feature_advanced_search' => isset($_POST['feature_advanced_search']) ? 1 : 0,
                'feature_api_access' => isset($_POST['feature_api_access']) ? 1 : 0,
                'feature_custom_branding' => isset($_POST['feature_custom_branding']) ? 1 : 0,
                'feature_priority_support' => isset($_POST['feature_priority_support']) ? 1 : 0,
                'feature_export_data' => isset($_POST['feature_export_data']) ? 1 : 0,
                'feature_integrations' => isset($_POST['feature_integrations']) ? 1 : 0,
                'feature_custom_domains' => isset($_POST['feature_custom_domains']) ? 1 : 0,
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
            ];
            
            $db = CMS\Database::instance();
            
            if ($postAction === 'update_plan') {
                $planId = (int)$_POST['plan_id'];
                if ($db->update('subscription_plans', $planData, ['id' => $planId])) {
                    $message = 'Abo-Paket erfolgreich aktualisiert.';
                } else {
                    $error = 'Fehler beim Aktualisieren des Pakets.';
                }
            } else {
                if ($db->insert('subscription_plans', $planData)) {
                    $message = 'Abo-Paket erfolgreich erstellt.';
                } else {
                    $error = 'Fehler beim Erstellen des Pakets.';
                }
            }
            
        } elseif ($postAction === 'delete_plan') {
            $planId = (int)$_POST['plan_id'];
            $db = CMS\Database::instance();
            // Check usage before delete? For now simple delete.
            if ($db->query("DELETE FROM {$db->getPrefix()}subscription_plans WHERE id = ?", [$planId])) {
                $message = 'Paket gelöscht.';
            } else {
                $error = 'Fehler beim Löschen.';
            }

        } elseif ($postAction === 'update_settings') {
            $db2 = CMS\Database::instance();
            $settingsToSave = [
                'subscription_enabled'    => isset($_POST['subscription_enabled']) ? '1' : '0',
                'subscription_currency'   => in_array($_POST['subscription_currency'] ?? '', ['EUR','USD','CHF']) ? $_POST['subscription_currency'] : 'EUR',
                'payment_info_bank'       => $_POST['payment_info_bank'] ?? '',
                'payment_info_paypal'     => $_POST['payment_info_paypal'] ?? '',
                'payment_info_note'       => $_POST['payment_info_note'] ?? '',
                'order_number_format'     => sanitize_text($_POST['order_number_format'] ?? 'BST{Y}{M}-{ID}'),
                'agb_url'                 => filter_var($_POST['agb_url'] ?? '', FILTER_SANITIZE_URL),
                'impressum_url'           => filter_var($_POST['impressum_url'] ?? '', FILTER_SANITIZE_URL),
                'widerruf_url'            => filter_var($_POST['widerruf_url'] ?? '', FILTER_SANITIZE_URL),
                'company_invoice_name'    => sanitize_text($_POST['company_invoice_name'] ?? ''),
                'company_invoice_address' => $_POST['company_invoice_address'] ?? '',
            ];
            foreach ($settingsToSave as $optName => $optVal) {
                $exists = $db2->get_var("SELECT id FROM {$db2->getPrefix()}settings WHERE option_name = ?", [$optName]);
                if ($exists) {
                    $db2->query("UPDATE {$db2->getPrefix()}settings SET option_value = ? WHERE option_name = ?", [$optVal, $optName]);
                } else {
                    $db2->query("INSERT INTO {$db2->getPrefix()}settings (option_name, option_value) VALUES (?, ?)", [$optName, $optVal]);
                }
            }
            $message = 'Einstellungen gespeichert.';

        } elseif ($postAction === 'seed_defaults') {
            $subscriptionManager->seedDefaultPlans();
            $message = '6 Standard-Pakete erfolgreich erstellt!';
        }
    }
}

// Get Data
$plans = $subscriptionManager->getAllPlans();
$db = Database::instance();
$users = $db->query("SELECT id, username, email FROM {$db->getPrefix()}users ORDER BY username")->fetchAll();
$groups = $db->query(
    "SELECT g.*, sp.name AS plan_name
     FROM {$db->getPrefix()}user_groups g
     LEFT JOIN {$db->getPrefix()}subscription_plans sp ON sp.id = g.plan_id
     ORDER BY g.name"
)->fetchAll();

// Get specific plan for editing if requested
$editPlan = null;
if ($activeTab === 'plans' && isset($_GET['edit_id'])) {
    foreach ($plans as $p) {
        if ($p->id == $_GET['edit_id']) {
            $editPlan = $p;
            break;
        }
    }
}

// Zentralen CSRF-Token generieren
$csrfToken = Security::instance()->generateToken('subscription_management');

// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abo-Verwaltung - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        .subscription-plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .plan-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s;
            position: relative;
        }
        
        .plan-header {
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        
        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 0.5rem;
        }
        
        .plan-price {
            font-size: 1.25rem;
            color: #3b82f6;
            font-weight: 600;
        }
        
        .plan-price small {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: normal;
        }
        
        .plan-features {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .plan-features li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .plan-features li:last-child {
            border-bottom: none;
        }
        
        .feature-label {
            color: #475569;
            font-size: 0.9rem;
        }
        
        .feature-value {
            font-weight: 600;
            color: #1e293b;
        }
        
        .feature-value.unlimited {
            color: #10b981;
        }
        
        .feature-value.disabled {
            color: #ef4444;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #dcfce7;
            color: #166534;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .form-grid-full {
            grid-column: span 2;
        }
        
        /* User Assignment Styles (matching users.php) */
        .usr-adm-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .usr-adm-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
            transition: all 0.2s;
        }
        .usr-adm-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        .usr-adm-top { display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem; }
        .usr-adm-avatar {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; color: white; font-size: 1.2rem;
        }
        .usr-adm-ident { overflow: hidden; }
        .usr-adm-name { margin: 0; font-weight: 600; color: #1e293b; font-size: 1.05rem; }
        .usr-adm-email { margin: 0; color: #64748b; font-size: 0.85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .usr-adm-badges { display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .usr-adm-badge { padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.02em; }
        
        /* Editor Styles */
        .settings-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .settings-table th, .settings-table td {
            text-align: left;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .settings-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
        }
        
        .admin-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 1rem;
        }
    </style>
</head>
<body class="admin-body">
    <?php renderAdminSidebar('subscriptions'); ?>
    
    <div class="admin-content">
        
        <!-- Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1.5rem;">
            <h2 style="margin:0;">💳 Abo-Verwaltung</h2>

            <?php if (empty($plans) && $activeTab === 'plans'): ?>
             <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="seed_defaults">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('6 Standard-Pakete erstellen?')">
                        Standard-Pakete erstellen
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- CONTENT: PAKETÜBERSICHT -->
        <?php if ($activeTab === 'plans'): ?>
            <div class="admin-section-header">
                <h3>Verfügbare Abo-Pakete</h3>
                <a href="#" onclick="document.getElementById('create-plan-modal').style.display='flex'; return false;" class="btn btn-primary btn-sm">+ Neues Paket</a>
            </div>
            
            <?php if (empty($plans)): ?>
                <div class="empty-state">
                    <p>Noch keine Abo-Pakete vorhanden.</p>
                </div>
            <?php else: ?>
                <div class="subscription-plans-grid">
                    <?php foreach ($plans as $plan): ?>
                        <div class="plan-card">
                            <div class="plan-header">
                                <h3 class="plan-name"><?php echo htmlspecialchars($plan->name); ?></h3>
                                <div class="plan-price">
                                    €<?php echo number_format((float)($plan->price_monthly ?? 0), 2); ?> <small>/Monat</small>
                                </div>
                                <div class="plan-price">
                                    €<?php echo number_format((float)($plan->price_yearly ?? 0), 2); ?> <small>/Jahr</small>
                                </div>
                            </div>
                            
                            <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1rem;">
                                <?php echo htmlspecialchars($plan->description ?? ''); ?>
                            </p>
                            
                            <ul class="plan-features">
                                <li>
                                    <span class="feature-label">Experten:</span>
                                    <span class="feature-value <?php echo $plan->limit_experts === -1 ? 'unlimited' : ($plan->limit_experts === 0 ? 'disabled' : ''); ?>">
                                        <?php echo $plan->limit_experts === -1 ? '∞ Unbegrenzt' : ($plan->limit_experts === 0 ? '✗ Deaktiviert' : $plan->limit_experts); ?>
                                    </span>
                                </li>
                                <li>
                                    <span class="feature-label">Unternehmen:</span>
                                    <span class="feature-value <?php echo $plan->limit_companies === -1 ? 'unlimited' : ($plan->limit_companies === 0 ? 'disabled' : ''); ?>">
                                        <?php echo $plan->limit_companies === -1 ? '∞ Unbegrenzt' : ($plan->limit_companies === 0 ? '✗ Deaktiviert' : $plan->limit_companies); ?>
                                    </span>
                                </li>
                                <li>
                                    <span class="feature-label">Speicher:</span>
                                    <span class="feature-value"><?php echo $plan->limit_storage_mb; ?> MB</span>
                                </li>
                            </ul>
                            
                            <h4 style="margin-top: 1rem; margin-bottom: 0.5rem; font-size: 0.9rem; color: #64748b;">Features:</h4>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                <?php if ($plan->feature_analytics): ?><span class="badge badge-success">Analytics</span><?php endif; ?>
                                <?php if ($plan->feature_api_access): ?><span class="badge badge-success">API</span><?php endif; ?>
                                <?php if ($plan->feature_priority_support): ?><span class="badge badge-success">Support</span><?php endif; ?>
                            </div>
                            <div style="margin-top:1rem;display:flex;gap:.5rem;border-top:1px solid #f1f5f9;padding-top:.75rem;">
                                <a href="?tab=plans&edit_id=<?php echo $plan->id; ?>" class="btn btn-sm btn-secondary" style="flex:1;text-align:center;">✏️ Bearbeiten</a>
                                <form method="POST" style="flex:1;" onsubmit="return confirm('Paket wirklich löschen?');">
                                    <input type="hidden" name="action" value="delete_plan">
                                    <input type="hidden" name="plan_id" value="<?php echo $plan->id; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <button class="btn btn-sm btn-danger" style="width:100%;background:#fee2e2;color:#991b1b;">🗑 Löschen</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
        <!-- CONTENT: EINSTELLUNGEN -->
        <?php elseif ($activeTab === 'settings'): ?>
            <?php
            $sysSet = [
                'subscription_enabled'    => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'subscription_enabled'") ?? '1',
                'subscription_currency'   => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'subscription_currency'") ?? 'EUR',
                'payment_info_bank'       => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'payment_info_bank'") ?? '',
                'payment_info_paypal'     => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'payment_info_paypal'") ?? '',
                'payment_info_note'       => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'payment_info_note'") ?? '',
                'order_number_format'     => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'order_number_format'") ?? 'BST{Y}{M}-{ID}',
                'agb_url'                 => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'agb_url'") ?? '',
                'impressum_url'           => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'impressum_url'") ?? '',
                'widerruf_url'            => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'widerruf_url'") ?? '',
                'company_invoice_name'    => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'company_invoice_name'") ?? '',
                'company_invoice_address' => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'company_invoice_address'") ?? '',
            ];
            ?>
            <form method="POST">
                <input type="hidden" name="action" value="update_settings">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <!-- Section: Abo-System Toggle -->
                <div style="background:white;border:1px solid #e2e8f0;border-radius:10px;padding:1.5rem;margin-bottom:1.5rem;">
                    <h3 style="margin-top:0;font-size:1.1rem;color:#1e293b;">🔧 Abo-System</h3>
                    <label style="display:flex;align-items:center;gap:.75rem;font-size:.95rem;cursor:pointer;">
                        <input type="checkbox" name="subscription_enabled" value="1" <?php echo ($sysSet['subscription_enabled'] == '1') ? 'checked' : ''; ?> style="width:18px;height:18px;">
                        <span><strong>Abo-System aktiv</strong></span>
                    </label>
                    <p style="margin:.5rem 0 0 1.75rem;color:#64748b;font-size:.85rem;">Wenn deaktiviert haben alle Benutzer unbegrenzten Zugriff (Unlimited). Pakete werden nicht geprüft.</p>
                    <div style="margin-top:1.25rem;">
                        <label style="display:block;font-weight:600;margin-bottom:.4rem;font-size:.9rem;">Währung</label>
                        <select name="subscription_currency" style="padding:.4rem .75rem;border:1px solid #cbd5e1;border-radius:6px;font-size:.9rem;">
                            <option value="EUR" <?php echo $sysSet['subscription_currency'] === 'EUR' ? 'selected' : ''; ?>>EUR (&euro;)</option>
                            <option value="USD" <?php echo $sysSet['subscription_currency'] === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                            <option value="CHF" <?php echo $sysSet['subscription_currency'] === 'CHF' ? 'selected' : ''; ?>>CHF (Fr.)</option>
                        </select>
                    </div>
                </div>

                <!-- Section: Zahlungsmethoden -->
                <div style="background:white;border:1px solid #e2e8f0;border-radius:10px;padding:1.5rem;margin-bottom:1.5rem;">
                    <h3 style="margin-top:0;font-size:1.1rem;color:#1e293b;">💳 Zahlungsmethoden</h3>
                    <p style="color:#64748b;font-size:.85rem;margin-top:0;">Diese Informationen werden Mitgliedern beim Abschluss eines Abos angezeigt.</p>
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label style="display:block;font-weight:600;margin-bottom:.4rem;font-size:.9rem;">Bankverbindung (Überweisung)</label>
                        <textarea name="payment_info_bank" rows="3" style="width:100%;padding:.5rem;border:1px solid #cbd5e1;border-radius:6px;font-size:.875rem;resize:vertical;" placeholder="Kontoinhaber, IBAN, BIC, Bankname..."><?php echo htmlspecialchars($sysSet['payment_info_bank']); ?></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label style="display:block;font-weight:600;margin-bottom:.4rem;font-size:.9rem;">PayPal</label>
                        <input type="text" name="payment_info_paypal" value="<?php echo htmlspecialchars($sysSet['payment_info_paypal']); ?>" style="width:100%;padding:.5rem;border:1px solid #cbd5e1;border-radius:6px;font-size:.875rem;" placeholder="PayPal.Me Link oder E-Mail...">
                    </div>
                    <div class="form-group">
                        <label style="display:block;font-weight:600;margin-bottom:.4rem;font-size:.9rem;">Allgemeine Zahlungshinweise</label>
                        <textarea name="payment_info_note" rows="2" style="width:100%;padding:.5rem;border:1px solid #cbd5e1;border-radius:6px;font-size:.875rem;resize:vertical;" placeholder="z.B. Rechnung nach Zahlungseingang..."><?php echo htmlspecialchars($sysSet['payment_info_note']); ?></textarea>
                    </div>
                </div>

                <!-- Section: Rechtliche Seiten -->
                <div style="background:white;border:1px solid #e2e8f0;border-radius:10px;padding:1.5rem;margin-bottom:1.5rem;">
                    <h3 style="margin-top:0;font-size:1.1rem;color:#1e293b;">📄 Rechtliche Seiten</h3>
                    <p style="color:#64748b;font-size:.85rem;margin-top:0;">URLs zu Pflichtseiten – werden im Checkout verlinkt.</p>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;">
                        <div class="form-group">
                            <label style="display:block;font-weight:600;margin-bottom:.4rem;font-size:.9rem;">AGB URL</label>
                            <input type="url" name="agb_url" value="<?php echo htmlspecialchars($sysSet['agb_url']); ?>" style="width:100%;padding:.5rem;border:1px solid #cbd5e1;border-radius:6px;font-size:.875rem;" placeholder="https://...">
                        </div>
                        <div class="form-group">
                            <label style="display:block;font-weight:600;margin-bottom:.4rem;font-size:.9rem;">Impressum URL</label>
                            <input type="url" name="impressum_url" value="<?php echo htmlspecialchars($sysSet['impressum_url']); ?>" style="width:100%;padding:.5rem;border:1px solid #cbd5e1;border-radius:6px;font-size:.875rem;" placeholder="https://...">
                        </div>
                        <div class="form-group">
                            <label style="display:block;font-weight:600;margin-bottom:.4rem;font-size:.9rem;">Widerruf URL</label>
                            <input type="url" name="widerruf_url" value="<?php echo htmlspecialchars($sysSet['widerruf_url']); ?>" style="width:100%;padding:.5rem;border:1px solid #cbd5e1;border-radius:6px;font-size:.875rem;" placeholder="https://...">
                        </div>
                    </div>
                </div>

                <!-- Section: Rechnungsabsender -->
                <div style="background:white;border:1px solid #e2e8f0;border-radius:10px;padding:1.5rem;margin-bottom:1.5rem;">
                    <h3 style="margin-top:0;font-size:1.1rem;color:#1e293b;">🏢 Rechnungsabsender</h3>
                    <p style="color:#64748b;font-size:.85rem;margin-top:0;">Werden auf Rechnungen als Absender gedruckt.</p>
                    <div class="form-group" style="margin-bottom:1rem;">
                        <label style="display:block;font-weight:600;margin-bottom:.4rem;font-size:.9rem;">Unternehmensname</label>
                        <input type="text" name="company_invoice_name" value="<?php echo htmlspecialchars($sysSet['company_invoice_name']); ?>" style="width:100%;padding:.5rem;border:1px solid #cbd5e1;border-radius:6px;font-size:.875rem;" placeholder="Ihr Unternehmen GmbH">
                    </div>
                    <div class="form-group">
                        <label style="display:block;font-weight:600;margin-bottom:.4rem;font-size:.9rem;">Adresse</label>
                        <textarea name="company_invoice_address" rows="3" style="width:100%;padding:.5rem;border:1px solid #cbd5e1;border-radius:6px;font-size:.875rem;resize:vertical;" placeholder="Straße, PLZ Stadt, Land"><?php echo htmlspecialchars($sysSet['company_invoice_address']); ?></textarea>
                    </div>
                </div>

                <!-- Section: Bestellnummern -->
                <div style="background:white;border:1px solid #e2e8f0;border-radius:10px;padding:1.5rem;margin-bottom:1.5rem;">
                    <h3 style="margin-top:0;font-size:1.1rem;color:#1e293b;">📋 Bestellnummern</h3>
                    <div class="form-group">
                        <label style="display:block;font-weight:600;margin-bottom:.4rem;font-size:.9rem;">Format</label>
                        <input type="text" name="order_number_format" value="<?php echo htmlspecialchars($sysSet['order_number_format']); ?>" style="width:100%;max-width:320px;padding:.5rem;border:1px solid #cbd5e1;border-radius:6px;font-size:.875rem;">
                        <p style="margin:.4rem 0 0;color:#64748b;font-size:.8rem;">Platzhalter: <code>{Y}</code> Jahr &middot; <code>{M}</code> Monat &middot; <code>{D}</code> Tag &middot; <code>{ID}</code> Bestell-ID &middot; <code>{R}</code> Zufallscode</p>
                    </div>
                </div>

                <div class="form-actions" style="margin-top:.5rem;">
                    <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
                </div>
            </form>

        <!-- CONTENT: ZUWEISUNGEN -->
        <?php elseif ($activeTab === 'assignments'): ?>
            <div class="admin-section-header">
                <h3>👤 Benutzer-Zuweisungen</h3>
            </div>
            
            <!-- Assign Form -->
            <div style="background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; border:1px solid #e2e8f0;">
                <h4 style="margin-top:0;">Abo einem Benutzer zuweisen</h4>
                <form method="POST" class="admin-form">
                    <input type="hidden" name="action" value="assign_subscription">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Benutzer *</label>
                            <select name="user_id" required>
                                <option value="">-- Benutzer wählen --</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user->id; ?>">
                                        <?php echo htmlspecialchars($user->username); ?> (<?php echo htmlspecialchars($user->email); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Abo-Paket *</label>
                            <select name="plan_id" required>
                                <option value="">-- Paket wählen --</option>
                                <?php foreach ($plans as $plan): ?>
                                    <option value="<?php echo $plan->id; ?>">
                                        <?php echo htmlspecialchars($plan->name); ?> (€<?php echo number_format((float)($plan->price_monthly ?? 0), 2); ?>/Monat)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Abrechnungszyklus *</label>
                            <select name="billing_cycle" required>
                                <option value="monthly">Monatlich</option>
                                <option value="yearly">Jährlich</option>
                                <option value="lifetime">Lebenslang</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Abo zuweisen</button>
                    </div>
                </form>
            </div>
            
            <h4 style="margin-bottom:1rem;">Aktive Benutzer-Abos</h4>
            <?php
            $activeSubscriptions = $db->query("
                SELECT us.*, sp.name as plan_name, u.username, u.email
                FROM {$db->getPrefix()}user_subscriptions us
                JOIN {$db->getPrefix()}subscription_plans sp ON us.plan_id = sp.id
                JOIN {$db->getPrefix()}users u ON us.user_id = u.id
                WHERE us.status = 'active'
                ORDER BY us.created_at DESC
            ")->fetchAll();
            ?>
            
            <div class="usr-adm-grid">
                <?php foreach ($activeSubscriptions as $sub): 
                     $nameParts = preg_split('/\s+/', trim($sub->username));
                     $initials  = mb_strtoupper(mb_substr($nameParts[0], 0, 1));
                ?>
                    <div class="usr-adm-card">
                        <div class="usr-adm-top">
                            <div class="usr-adm-avatar" style="background: linear-gradient(135deg,#3b82f6,#2563eb);">
                                <?php echo $initials; ?>
                            </div>
                            <div class="usr-adm-ident">
                                <p class="usr-adm-name"><?php echo htmlspecialchars($sub->username); ?></p>
                                <p class="usr-adm-email"><?php echo htmlspecialchars($sub->email); ?></p>
                            </div>
                        </div>
                        <div class="usr-adm-badges">
                            <span class="usr-adm-badge" style="background:#dcfce7; color:#166534;">
                                <?php echo htmlspecialchars($sub->plan_name); ?>
                            </span>
                            <span class="usr-adm-badge" style="background:#f1f5f9; color:#475569;">
                                <?php echo ucfirst($sub->billing_cycle); ?>
                            </span>
                        </div>
                        <div style="font-size:0.85rem; color:#64748b;">
                            Seit: <?php echo date('d.m.Y', strtotime($sub->created_at)); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($activeSubscriptions)): ?>
                    <div style="grid-column: 1/-1; padding: 2rem; text-align: center; background: white; border-radius: 8px;">
                        <p style="color: #64748b;">Noch keine aktiven Abos.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div style="height:1.5rem;"></div>

            <div class="admin-section-header">
                <h3>🫂 Gruppen-Zuweisungen</h3>
                <p style="color:#64748b;font-size:.9rem;">Weise jeder Gruppe ein Abo-Paket zu. Mitglieder erhalten damit die Rechte des Paketes.</p>
            </div>

            <?php if (empty($groups)): ?>
                <div style="padding:2rem;text-align:center;background:#fff;border-radius:8px;border:1px solid #e2e8f0;">
                    <p style="color:#64748b;">Noch keine Gruppen vorhanden. <a href="<?php echo SITE_URL; ?>/admin/groups">Gruppen erstellen →</a></p>
                </div>
            <?php else: ?>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:auto;margin-bottom:1.5rem;">
                <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                    <thead><tr style="background:#f8fafc;">
                        <th style="padding:.55rem .9rem;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e2e8f0;">Gruppe</th>
                        <th style="padding:.55rem .9rem;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e2e8f0;width:100px;">Status</th>
                        <th style="padding:.55rem .9rem;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e2e8f0;">Aktuelles Paket</th>
                        <th style="padding:.55rem .9rem;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e2e8f0;width:320px;">Paket zuweisen</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($groups as $grp): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:.65rem .9rem;">
                            <span style="font-weight:600;color:#1e293b;"><?php echo htmlspecialchars($grp->name, ENT_QUOTES); ?></span>
                            <?php if (!empty($grp->description)): ?>
                            <div style="font-size:.74rem;color:#94a3b8;"><?php echo htmlspecialchars(substr($grp->description, 0, 55), ENT_QUOTES) . (strlen($grp->description ?? '') > 55 ? '…' : ''); ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:.65rem .9rem;">
                            <span style="display:inline-flex;align-items:center;padding:.2rem .5rem;border-radius:99px;font-size:.74rem;font-weight:600;background:<?php echo $grp->is_active ? '#dcfce7' : '#fee2e2'; ?>;color:<?php echo $grp->is_active ? '#15803d' : '#b91c1c'; ?>;">
                                <?php echo $grp->is_active ? 'Aktiv' : 'Inaktiv'; ?>
                            </span>
                        </td>
                        <td style="padding:.65rem .9rem;">
                            <?php if ($grp->plan_name): ?>
                            <span style="display:inline-flex;align-items:center;padding:.2rem .5rem;border-radius:99px;font-size:.74rem;font-weight:600;background:#dbeafe;color:#1e40af;">
                                📦 <?php echo htmlspecialchars($grp->plan_name, ENT_QUOTES); ?>
                            </span>
                            <?php else: ?>
                            <span style="color:#94a3b8;font-size:.8rem;">Kein Paket</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:.55rem .9rem;">
                            <form method="POST" action="?tab=assignments&sub=groups" style="display:flex;gap:.5rem;align-items:center;">
                                <input type="hidden" name="action" value="assign_group_plan">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="group_id" value="<?php echo (int)$grp->id; ?>">
                                <select name="group_plan_id" style="flex:1;padding:.3rem .5rem;border:1px solid #cbd5e1;border-radius:6px;font-size:.8rem;">
                                    <option value="0">— Kein Paket —</option>
                                    <?php foreach ($plans as $plan): ?>
                                    <option value="<?php echo (int)$plan->id; ?>" <?php echo (int)($grp->plan_id ?? 0) === (int)$plan->id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($plan->name, ENT_QUOTES); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary" style="white-space:nowrap;">💾 Speichern</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
    
    <!-- Create/Edit Plan Modal -->
    <?php 
    $showModal = (isset($editPlan) || ($activeTab === 'plans' && isset($_GET['new'])));
    // If editPlan is set, populate fields. If not, empty.
    $p = $editPlan;
    ?>
    <div id="create-plan-modal" style="display: <?php echo $showModal ? 'flex' : 'none'; ?>; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <h2><?php echo $p ? 'Paket bearbeiten' : 'Neues Abo-Paket erstellen'; ?></h2>
            <form method="POST" action="?tab=plans">
                <input type="hidden" name="action" value="<?php echo $p ? 'update_plan' : 'create_plan'; ?>">
                <?php if ($p): ?><input type="hidden" name="plan_id" value="<?php echo $p->id; ?>"><?php endif; ?>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" name="name" value="<?php echo $p ? htmlspecialchars($p->name) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Slug *</label>
                        <input type="text" name="slug" value="<?php echo $p ? htmlspecialchars($p->slug) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group form-grid-full">
                        <label>Beschreibung</label>
                        <textarea name="description" rows="3"><?php echo $p ? htmlspecialchars($p->description ?? '') : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Preis/Monat (€)</label>
                        <input type="number" name="price_monthly" step="0.01" value="<?php echo $p ? $p->price_monthly : '0.00'; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Preis/Jahr (€)</label>
                        <input type="number" name="price_yearly" step="0.01" value="<?php echo $p ? $p->price_yearly : '0.00'; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Limit Experten (-1 = ∞)</label>
                        <input type="number" name="limit_experts" value="<?php echo $p ? $p->limit_experts : '-1'; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Limit Unternehmen (-1 = ∞)</label>
                        <input type="number" name="limit_companies" value="<?php echo $p ? $p->limit_companies : '-1'; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Limit Events (-1 = ∞)</label>
                        <input type="number" name="limit_events" value="<?php echo $p ? $p->limit_events : '-1'; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Limit Speakers (-1 = ∞)</label>
                        <input type="number" name="limit_speakers" value="<?php echo $p ? $p->limit_speakers : '-1'; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Speicher (MB)</label>
                        <input type="number" name="limit_storage_mb" value="<?php echo $p ? $p->limit_storage_mb : '1000'; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Sortierung</label>
                        <input type="number" name="sort_order" value="<?php echo $p ? $p->sort_order : '0'; ?>">
                    </div>
                </div>
                
                <h3 style="margin-top: 1.5rem;">Plugin-Zugriff</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <label><input type="checkbox" name="plugin_experts" <?php echo (!$p || $p->plugin_experts) ? 'checked' : ''; ?>> Experten</label>
                    <label><input type="checkbox" name="plugin_companies" <?php echo (!$p || $p->plugin_companies) ? 'checked' : ''; ?>> Unternehmen</label>
                    <label><input type="checkbox" name="plugin_events" <?php echo (!$p || $p->plugin_events) ? 'checked' : ''; ?>> Events</label>
                    <label><input type="checkbox" name="plugin_speakers" <?php echo (!$p || $p->plugin_speakers) ? 'checked' : ''; ?>> Speakers</label>
                </div>
                
                <h3 style="margin-top: 1.5rem;">Premium Features</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <label><input type="checkbox" name="feature_analytics" <?php echo ($p && $p->feature_analytics) ? 'checked' : ''; ?>> Analytics</label>
                    <label><input type="checkbox" name="feature_advanced_search" <?php echo ($p && $p->feature_advanced_search) ? 'checked' : ''; ?>> Erweiterte Suche</label>
                    <label><input type="checkbox" name="feature_api_access" <?php echo ($p && $p->feature_api_access) ? 'checked' : ''; ?>> API-Zugang</label>
                    <label><input type="checkbox" name="feature_custom_branding" <?php echo ($p && $p->feature_custom_branding) ? 'checked' : ''; ?>> Custom Branding</label>
                    <label><input type="checkbox" name="feature_priority_support" <?php echo ($p && $p->feature_priority_support) ? 'checked' : ''; ?>> Priority Support</label>
                    <label><input type="checkbox" name="feature_export_data" <?php echo ($p && $p->feature_export_data) ? 'checked' : ''; ?>> Daten-Export</label>
                    <label><input type="checkbox" name="feature_integrations" <?php echo ($p && $p->feature_integrations) ? 'checked' : ''; ?>> Integrationen</label>
                    <label><input type="checkbox" name="feature_custom_domains" <?php echo ($p && $p->feature_custom_domains) ? 'checked' : ''; ?>> Custom Domains</label>
                </div>
                
                <div class="form-actions" style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary"><?php echo $p ? 'Speichern' : 'Paket erstellen'; ?></button>
                    <a href="?tab=plans" class="btn btn-secondary" onclick="document.getElementById('create-plan-modal').style.display='none'">
                        Abbrechen
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Modal checking logic via PHP above handles opening
    </script>
</body>
</html>
