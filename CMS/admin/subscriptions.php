<?php
declare(strict_types=1);

/**
 * Subscription Management Admin Page
 * 
 * @package CMSv2\Admin
 */

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
renderAdminLayoutStart('Abo-Verwaltung', 'subscriptions');
?>

<div class="posts-header">
    <h2>💳 Abo-Verwaltung</h2>

            <?php if (empty($plans) && $activeTab === 'plans'): ?>
             <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="seed_defaults">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('6 Standard-Pakete erstellen?')">
                        Standard-Pakete erstellen
                    </button>
                </form>
        <?php endif; ?>
</div><!-- /.posts-header -->

<?php if ($message): ?>
    <div class="notice notice-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
        
        <!-- CONTENT: PAKETÜBERSICHT -->
        <?php if ($activeTab === 'plans'): ?>
            <?php
            // Tier-Farben nach sort_order / Index
            $tierColors = [
                ['--plan-accent:#94a3b8;--plan-accent-bg:#f8fafc;--plan-accent-text:#475569;'],  // Grau – Free
                ['--plan-accent:#22c55e;--plan-accent-bg:#f0fdf4;--plan-accent-text:#15803d;'],  // Grün – Starter
                ['--plan-accent:#3b82f6;--plan-accent-bg:#eff6ff;--plan-accent-text:#1d4ed8;'],  // Blau – Basic
                ['--plan-accent:#a855f7;--plan-accent-bg:#faf5ff;--plan-accent-text:#7e22ce;'],  // Lila – Pro
                ['--plan-accent:#f59e0b;--plan-accent-bg:#fffbeb;--plan-accent-text:#b45309;'],  // Gold – Business
                ['--plan-accent:#ef4444;--plan-accent-bg:#fef2f2;--plan-accent-text:#b91c1c;'],  // Rot – Enterprise
            ];
            $featMap = [
                'feature_analytics'       => ['📊', 'Analytics'],
                'feature_advanced_search' => ['🔍', 'Adv. Search'],
                'feature_api_access'      => ['⚡', 'API'],
                'feature_custom_branding' => ['🎨', 'Branding'],
                'feature_priority_support'=> ['🎯', 'Support'],
                'feature_export_data'     => ['📤', 'Export'],
                'feature_integrations'    => ['🔗', 'Integrationen'],
                'feature_custom_domains'  => ['🌐', 'Domains'],
            ];
            $pluginMap = [
                'plugin_experts'   => ['👤', 'Experten'],
                'plugin_companies' => ['🏢', 'Firmen'],
                'plugin_events'    => ['📅', 'Events'],
                'plugin_speakers'  => ['🎤', 'Speakers'],
            ];
            $limitLabel = fn(int $v, string $unit) => $v === -1
                ? '<span class="plc-limit plc-limit--inf">∞</span>'
                : ($v === 0
                    ? '<span class="plc-limit plc-limit--off">—</span>'
                    : '<span class="plc-limit">' . $v . '</span>');
            ?>

            <div class="admin-section-header">
                <h3 style="margin:0;font-size:1rem;">Verfügbare Abo-Pakete <span class="plc-count-badge"><?php echo count($plans); ?></span></h3>
                <a href="#" onclick="document.getElementById('create-plan-modal').style.display='flex'; return false;" class="btn-sm btn-primary">+ Neues Paket</a>
            </div>

            <?php if (empty($plans)): ?>
                <div class="post-card" style="text-align:center;color:#64748b;padding:2rem;">Noch keine Abo-Pakete vorhanden.</div>
            <?php else: ?>
            <div class="plan-list">
                <?php foreach ($plans as $i => $plan): 
                    $colors = $tierColors[$i % count($tierColors)][0];
                    $lim = fn(mixed $v) => (int)$v === -1 ? '<span class="plc-limit plc-limit--inf">∞</span>' : ((int)$v === 0 ? '<span class="plc-limit plc-limit--off">—</span>' : '<span class="plc-limit">' . (int)$v . '</span>');
                ?>
                <div class="plan-list-card" style="<?php echo $colors; ?>">

                    <!-- Accent bar -->
                    <div class="plc-accent"></div>

                    <!-- Col 1: Identity -->
                    <div class="plc-identity">
                        <div class="plc-name"><?php echo htmlspecialchars($plan->name); ?></div>
                        <code class="plc-slug"><?php echo htmlspecialchars($plan->slug); ?></code>
                        <?php if (!empty($plan->description)): ?>
                            <p class="plc-desc"><?php echo htmlspecialchars($plan->description); ?></p>
                        <?php endif; ?>
                        <div class="plc-order">Sortierung: <?php echo (int)$plan->sort_order; ?></div>
                    </div>

                    <!-- Col 2: Limits -->
                    <div class="plc-limits">
                        <div class="plc-limits-title">Limits</div>
                        <div class="plc-limits-grid">
                            <div class="plc-lrow"><span class="plc-licon">👤</span><span class="plc-lname">Experten</span><?php echo $lim($plan->limit_experts); ?></div>
                            <div class="plc-lrow"><span class="plc-licon">🏢</span><span class="plc-lname">Firmen</span><?php echo $lim($plan->limit_companies); ?></div>
                            <div class="plc-lrow"><span class="plc-licon">📅</span><span class="plc-lname">Events</span><?php echo $lim($plan->limit_events); ?></div>
                            <div class="plc-lrow"><span class="plc-licon">🎤</span><span class="plc-lname">Speakers</span><?php echo $lim($plan->limit_speakers); ?></div>
                            <div class="plc-lrow"><span class="plc-licon">💾</span><span class="plc-lname">Speicher</span><span class="plc-limit"><?php echo (int)$plan->limit_storage_mb; ?>&thinsp;MB</span></div>
                        </div>
                    </div>

                    <!-- Col 3: Plugins & Features -->
                    <div class="plc-features">
                        <div class="plc-limits-title">Plugins &amp; Features</div>
                        <div class="plc-feat-rows">
                            <?php foreach ($pluginMap as $key => [$icon, $label]): ?>
                            <div class="plc-feat-row <?php echo $plan->$key ? 'plc-feat-row--on' : 'plc-feat-row--off'; ?>">
                                <span><?php echo $icon; ?></span> <?php echo $label; ?>
                            </div>
                            <?php endforeach; ?>
                            <?php foreach ($featMap as $key => [$icon, $label]): ?>
                            <div class="plc-feat-row <?php echo $plan->$key ? 'plc-feat-row--on' : 'plc-feat-row--off'; ?>">
                                <span><?php echo $icon; ?></span> <?php echo $label; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Col 4: Preise & Aktionen -->
                    <div class="plc-right">
                        <div class="plc-prices">
                            <div class="plc-price-row">
                                <span class="plc-price-amount">€<?php echo number_format((float)($plan->price_monthly ?? 0), 2); ?></span>
                                <span class="plc-price-cycle">/Monat</span>
                            </div>
                            <div class="plc-price-row">
                                <span class="plc-price-amount plc-price-amount--year">€<?php echo number_format((float)($plan->price_yearly ?? 0), 2); ?></span>
                                <span class="plc-price-cycle">/Jahr</span>
                            </div>
                        </div>
                        <div class="plc-actions">
                            <a href="?tab=plans&edit_id=<?php echo $plan->id; ?>" class="btn-sm btn-secondary">✏️ Bearbeiten</a>
                            <form method="POST" onsubmit="return confirm('Paket &quot;<?php echo htmlspecialchars($plan->name, ENT_QUOTES); ?>&quot; wirklich löschen?');">
                                <input type="hidden" name="action" value="delete_plan">
                                <input type="hidden" name="plan_id" value="<?php echo $plan->id; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <button class="btn-sm btn-danger">🗑 Löschen</button>
                            </form>
                        </div>
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

                <div class="settings-grid-2col">

                <!-- Section: Abo-System Toggle -->
                <div class="post-card">
                    <h3>🔧 Abo-System</h3>
                    <div class="field-group">
                        <label style="display:flex;align-items:center;gap:.6rem;font-size:.875rem;cursor:pointer;font-weight:600;">
                            <input type="checkbox" name="subscription_enabled" value="1" <?php echo ($sysSet['subscription_enabled'] == '1') ? 'checked' : ''; ?> style="width:16px;height:16px;">
                            Abo-System aktiv
                        </label>
                        <p style="margin:.4rem 0 0;color:#64748b;font-size:.8rem;">Wenn deaktiviert haben alle Benutzer unbegrenzten Zugriff (Unlimited). Pakete werden nicht geprüft.</p>
                    </div>
                    <div class="field-group" style="margin-bottom:0;">
                        <label>Währung</label>
                        <select name="subscription_currency">
                            <option value="EUR" <?php echo $sysSet['subscription_currency'] === 'EUR' ? 'selected' : ''; ?>>EUR (&euro;)</option>
                            <option value="USD" <?php echo $sysSet['subscription_currency'] === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                            <option value="CHF" <?php echo $sysSet['subscription_currency'] === 'CHF' ? 'selected' : ''; ?>>CHF (Fr.)</option>
                        </select>
                    </div>
                </div>

                <!-- Section: Zahlungsmethoden -->
                <div class="post-card">
                    <h3>💳 Zahlungsmethoden</h3>
                    <p style="color:#64748b;font-size:.8rem;margin-top:-.25rem;">Werden Mitgliedern beim Abo-Abschluss angezeigt.</p>
                    <div class="field-group">
                        <label>Bankverbindung (Überweisung)</label>
                        <textarea name="payment_info_bank" rows="3" placeholder="Kontoinhaber, IBAN, BIC, Bankname..."><?php echo htmlspecialchars($sysSet['payment_info_bank']); ?></textarea>
                    </div>
                    <div class="field-group">
                        <label>PayPal</label>
                        <input type="text" name="payment_info_paypal" value="<?php echo htmlspecialchars($sysSet['payment_info_paypal']); ?>" placeholder="PayPal.Me Link oder E-Mail...">
                    </div>
                    <div class="field-group" style="margin-bottom:0;">
                        <label>Allgemeine Zahlungshinweise</label>
                        <textarea name="payment_info_note" rows="2" placeholder="z.B. Rechnung nach Zahlungseingang..."><?php echo htmlspecialchars($sysSet['payment_info_note']); ?></textarea>
                    </div>
                </div>

                <!-- Section: Rechtliche Seiten -->
                <div class="post-card">
                    <h3>📄 Rechtliche Seiten</h3>
                    <p style="color:#64748b;font-size:.8rem;margin-top:-.25rem;">URLs zu Pflichtseiten – werden im Checkout verlinkt.</p>
                    <div class="field-group">
                        <label>AGB URL</label>
                        <input type="url" name="agb_url" value="<?php echo htmlspecialchars($sysSet['agb_url']); ?>" placeholder="https://...">
                    </div>
                    <div class="field-group">
                        <label>Impressum URL</label>
                        <input type="url" name="impressum_url" value="<?php echo htmlspecialchars($sysSet['impressum_url']); ?>" placeholder="https://...">
                    </div>
                    <div class="field-group" style="margin-bottom:0;">
                        <label>Widerruf URL</label>
                        <input type="url" name="widerruf_url" value="<?php echo htmlspecialchars($sysSet['widerruf_url']); ?>" placeholder="https://...">
                    </div>
                </div>

                <!-- Section: Rechnungsabsender -->
                <div class="post-card">
                    <h3>🏢 Rechnungsabsender</h3>
                    <p style="color:#64748b;font-size:.8rem;margin-top:-.25rem;">Werden auf Rechnungen als Absender gedruckt.</p>
                    <div class="field-group">
                        <label>Unternehmensname</label>
                        <input type="text" name="company_invoice_name" value="<?php echo htmlspecialchars($sysSet['company_invoice_name']); ?>" placeholder="Ihr Unternehmen GmbH">
                    </div>
                    <div class="field-group" style="margin-bottom:0;">
                        <label>Adresse</label>
                        <textarea name="company_invoice_address" rows="3" placeholder="Straße, PLZ Stadt, Land"><?php echo htmlspecialchars($sysSet['company_invoice_address']); ?></textarea>
                    </div>
                </div>

                <!-- Section: Bestellnummern -->
                <div class="post-card">
                    <h3>📋 Bestellnummern</h3>
                    <div class="field-group" style="margin-bottom:0;">
                        <label>Format</label>
                        <input type="text" name="order_number_format" value="<?php echo htmlspecialchars($sysSet['order_number_format']); ?>">
                        <p style="margin:.4rem 0 0;color:#64748b;font-size:.78rem;">Platzhalter: <code>{Y}</code> Jahr &middot; <code>{M}</code> Monat &middot; <code>{D}</code> Tag &middot; <code>{ID}</code> ID &middot; <code>{R}</code> Zufallscode</p>
                    </div>
                </div>

                </div><!-- /.settings-grid-2col -->

                <div style="margin-top:.25rem;">
                    <button type="submit" class="btn-sm btn-primary" style="padding:.5rem 1.25rem;font-size:.9rem;">💾 Einstellungen speichern</button>
                </div>
            </form>

        <!-- CONTENT: ZUWEISUNGEN -->
        <?php elseif ($activeTab === 'assignments'): ?>

            <!-- Assign form -->
            <div class="post-card" style="margin-bottom:1.5rem;">
                <h3>👤 Benutzer-Abo zuweisen</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="assign_subscription">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Benutzer *</label>
                            <select name="user_id" required>
                                <option value="">-- Benutzer wählen --</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user->id; ?>"><?php echo htmlspecialchars($user->username); ?> (<?php echo htmlspecialchars($user->email); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Abo-Paket *</label>
                            <select name="plan_id" required>
                                <option value="">-- Paket wählen --</option>
                                <?php foreach ($plans as $plan): ?>
                                    <option value="<?php echo $plan->id; ?>"><?php echo htmlspecialchars($plan->name); ?> (€<?php echo number_format((float)($plan->price_monthly ?? 0), 2); ?>/Monat)</option>
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
                        <div class="form-group" style="display:flex;align-items:flex-end;">
                            <button type="submit" class="btn-sm btn-primary" style="padding:.45rem 1rem;">Abo zuweisen</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="admin-section-header" style="margin-top:1.5rem;">
                <h3 style="margin:0;font-size:1rem;">Aktive Benutzer-Abos</h3>
            </div>
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
                            <div class="usr-adm-avatar" style="background:linear-gradient(135deg,#3b82f6,#2563eb);"><?php echo $initials; ?></div>
                            <div class="usr-adm-ident">
                                <p class="usr-adm-name"><?php echo htmlspecialchars($sub->username); ?></p>
                                <p class="usr-adm-email"><?php echo htmlspecialchars($sub->email); ?></p>
                            </div>
                        </div>
                        <div class="usr-adm-badges">
                            <span class="usr-adm-badge" style="background:#dcfce7;color:#166534;"><?php echo htmlspecialchars($sub->plan_name); ?></span>
                            <span class="usr-adm-badge" style="background:#f1f5f9;color:#475569;"><?php echo ucfirst($sub->billing_cycle); ?></span>
                        </div>
                        <div style="font-size:.82rem;color:#64748b;">Seit: <?php echo date('d.m.Y', strtotime($sub->created_at)); ?></div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($activeSubscriptions)): ?>
                    <div class="post-card" style="text-align:center;color:#64748b;">Noch keine aktiven Abos.</div>
                <?php endif; ?>
            </div>

            <div style="height:1.5rem;"></div>

            <div class="admin-section-header" style="margin-top:2rem;">
                <div>
                    <h3 style="margin:0;font-size:1rem;">🫂 Gruppen-Zuweisungen</h3>
                    <p style="margin:.2rem 0 0;color:#64748b;font-size:.82rem;">Weise jeder Gruppe ein Abo-Paket zu. Mitglieder erhalten damit die Rechte des Paketes.</p>
                </div>
            </div>

            <?php if (empty($groups)): ?>
                <div style="padding:2rem;text-align:center;background:#fff;border-radius:8px;border:1px solid #e2e8f0;">
                    <p style="color:#64748b;">Noch keine Gruppen vorhanden. <a href="<?php echo SITE_URL; ?>/admin/groups">Gruppen erstellen →</a></p>
                </div>
            <?php else: ?>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:auto;margin-bottom:1.5rem;">
                <table class="posts-table">
                    <thead><tr>
                        <th>Gruppe</th>
                        <th style="width:100px;">Status</th>
                        <th>Aktuelles Paket</th>
                        <th style="width:300px;">Paket zuweisen</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($groups as $grp): ?>
                    <tr>
                        <td>
                            <span style="font-weight:600;color:#1e293b;"><?php echo htmlspecialchars($grp->name, ENT_QUOTES); ?></span>
                            <?php if (!empty($grp->description)): ?>
                            <div style="font-size:.74rem;color:#94a3b8;"><?php echo htmlspecialchars(substr($grp->description, 0, 55), ENT_QUOTES) . (strlen($grp->description ?? '') > 55 ? '…' : ''); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge" style="background:<?php echo $grp->is_active ? '#dcfce7' : '#fee2e2'; ?>;color:<?php echo $grp->is_active ? '#15803d' : '#b91c1c'; ?>;">
                                <?php echo $grp->is_active ? 'Aktiv' : 'Inaktiv'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($grp->plan_name): ?>
                            <span class="sub-feat-badge" style="background:#dbeafe;color:#1e40af;">📦 <?php echo htmlspecialchars($grp->plan_name, ENT_QUOTES); ?></span>
                            <?php else: ?>
                            <span style="color:#94a3b8;font-size:.8rem;">Kein Paket</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="?tab=assignments&sub=groups" style="display:flex;gap:.4rem;align-items:center;">
                                <input type="hidden" name="action" value="assign_group_plan">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="group_id" value="<?php echo (int)$grp->id; ?>">
                                <select name="group_plan_id" style="flex:1;">
                                    <option value="0">— Kein Paket —</option>
                                    <?php foreach ($plans as $plan): ?>
                                    <option value="<?php echo (int)$plan->id; ?>" <?php echo (int)($grp->plan_id ?? 0) === (int)$plan->id ? 'selected' : ''; ?>><?php echo htmlspecialchars($plan->name, ENT_QUOTES); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-sm btn-primary">💾</button>
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
    <div id="create-plan-modal" style="display:<?php echo $showModal ? 'flex' : 'none'; ?>;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
        <div style="background:#fff;padding:2rem;border-radius:12px;max-width:780px;width:90%;max-height:90vh;overflow-y:auto;">
            <h2 style="margin:0 0 1.25rem;font-size:1.2rem;color:#1e293b;padding-bottom:.75rem;border-bottom:2px solid #f1f5f9;"><?php echo $p ? 'Paket bearbeiten' : 'Neues Abo-Paket erstellen'; ?></h2>
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
        // Modal: clicking outside closes it
        document.getElementById('create-plan-modal').addEventListener('click', function(e){
            if (e.target === this) this.style.display='none';
        });
    </script>
<?php renderAdminLayoutEnd(); ?>
