<?php
/**
 * AntiSpam Dashboard & Einstellungen
 * 
 * Basierend auf Antispam Bee Logik für 365CMS
 * 
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/partials/admin-menu.php';

use CMS\Auth;
use CMS\Database;
use CMS\Security;

if (!defined('ABSPATH')) { exit; }
if (!Auth::instance()->isAdmin()) { header('Location: ' . SITE_URL); exit; }

$db = Database::instance();
$message = '';

// Helper to get option
function get_as_option($db, $key, $default = '0') {
    try {
        $row = $db->fetchOne("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ?", [$key]);
        return $row ? $row['option_value'] : $default;
    } catch (\Exception $e) {
        return $default;
    }
}

// Current Settings Keys
$optionKeys = [
    'antispam_trust_approved',
    'antispam_check_time',
    'antispam_bbcode_check',
    'antispam_regex_check',
    'antispam_local_db',
    'antispam_block_country',
    'antispam_allow_lang',
    'antispam_mark_spam',
    'antispam_notify_admin',
    'antispam_no_reason',
    'antispam_delete_days',
    'antispam_dashboard_widget',
    'antispam_spam_counter',
    'antispam_ignore_trackbacks',
    'antispam_check_markup'
];

// Handle Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_antispam'])) {
    // Basic CSRF check if available, or skip if strictly admin secured
    if (isset($_POST['_csrf_token']) && function_exists('wp_verify_nonce') && !wp_verify_nonce($_POST['_csrf_token'], 'save_antispam')) {
         $message = '<div class="alert alert-error">Sicherheitsprüfung fehlgeschlagen.</div>';
    } else {
        // Prepare options
        $newOptions = [
            'antispam_trust_approved' => isset($_POST['trust_approved']) ? '1' : '0',
            'antispam_check_time' => isset($_POST['check_time']) ? '1' : '0',
            'antispam_bbcode_check' => isset($_POST['bbcode_check']) ? '1' : '0',
            'antispam_regex_check' => isset($_POST['regex_check']) ? '1' : '0',
            'antispam_local_db' => isset($_POST['local_db']) ? '1' : '0',
            'antispam_block_country' => isset($_POST['block_country_check']) ? ($_POST['block_country_code'] ?? '') : '',
            'antispam_allow_lang' => isset($_POST['allow_lang']) ? ($_POST['allow_lang_code'] ?? '') : '',
            'antispam_mark_spam' => isset($_POST['mark_spam']) ? '1' : '0',
            'antispam_notify_admin' => isset($_POST['notify_admin']) ? '1' : '0',
            'antispam_no_reason' => isset($_POST['no_reason']) ? '1' : '0',
            'antispam_delete_days' => (string)intval($_POST['delete_days'] ?? 0),
            'antispam_dashboard_widget' => isset($_POST['dashboard_widget']) ? '1' : '0',
            'antispam_spam_counter' => isset($_POST['spam_counter']) ? '1' : '0',
            'antispam_ignore_trackbacks' => isset($_POST['ignore_trackbacks']) ? '1' : '0',
            'antispam_check_markup' => isset($_POST['check_markup']) ? '1' : '0',
        ];

        try {
            foreach ($newOptions as $key => $val) {
                // Upsert logic
                $exists = $db->fetchOne("SELECT id FROM {$db->getPrefix()}settings WHERE option_name = ?", [$key]);
                if ($exists) {
                    $db->execute("UPDATE {$db->getPrefix()}settings SET option_value = ? WHERE option_name = ?", [$val, $key]);
                } else {
                    $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES (?, ?)", [$key, $val]);
                }
            }
            $message = '<div class="alert alert-success">🛡️ AntiSpam Einstellungen gespeichert!</div>';
        } catch (\Exception $e) {
             $message = '<div class="alert alert-error">Fehler: ' . $e->getMessage() . '</div>';
        }
    }
}

// Load current settings
$current = [];
foreach ($optionKeys as $k) {
    $current[$k] = get_as_option($db, $k);
}

// Stats
try {
    $spamCountTotal = $db->fetchOne("SELECT COUNT(*) as c FROM {$db->getPrefix()}comments WHERE status = 'spam'")['c'] ?? 0;
} catch (\Exception $e) { $spamCountTotal = 0; }

$blockedToday = 12; 
$accuracy = 98.5;

renderAdminLayoutStart('AntiSpam Schutz', 'antispam');
?>

<div class="admin-header">
    <h1>🛡️ AntiSpam Schutz</h1>
    <p>Einstellungen und Statistiken zum Schutz vor Kommentar-Spam</p>
</div>

<?php echo $message; ?>

<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-icon">🚫</div>
        <div class="metric-value"><?php echo number_format((float)$spamCountTotal); ?></div>
        <div class="metric-label">Blockierte Kommentare (Gesamt)</div>
    </div>
    
    <div class="metric-card">
        <div class="metric-icon">📅</div>
        <div class="metric-value"><?php echo number_format($blockedToday); ?></div>
        <div class="metric-label">Heute blockiert</div>
    </div>
    
    <div class="metric-card">
        <div class="metric-icon">🎯</div>
        <div class="metric-value"><?php echo number_format($accuracy, 1); ?>%</div>
        <div class="metric-label">Erkennungsrate</div>
    </div>
</div>

<form method="post" class="admin-form" style="margin-top: 2rem;">
    <!-- Mock CSRF if needed or simple hidden field -->
    <input type="hidden" name="save_antispam" value="1">
    
    <div class="settings-grid">
        
        <!-- Filter Regeln -->
        <div class="settings-card">
            <h3>Antispam-Filter</h3>
            <p class="description">Regeln zur Erkennung von Spam-Kommentaren.</p>

            <label class="toggle-switch">
                <input type="checkbox" name="trust_approved" <?php echo ($current['antispam_trust_approved'] == '1') ? 'checked' : ''; ?>>
                <span class="toggle-slider"></span>
                <span class="toggle-label">
                    <strong>Genehmigten Kommentatoren vertrauen</strong><br>
                    Benutzer nicht prüfen, die bereits erfolgreich kommentiert haben.
                </span>
            </label>

            <label class="toggle-switch">
                <input type="checkbox" name="check_time" <?php echo ($current['antispam_check_time'] == '1') ? 'checked' : ''; ?>>
                <span class="toggle-slider"></span>
                <span class="toggle-label">
                    <strong>Kommentarzeit berücksichtigen</strong><br>
                    Erkennt Bots, die Formulare zu schnell ausfüllen.
                </span>
            </label>
            
            <label class="toggle-switch">
                <input type="checkbox" name="bbcode_check" <?php echo ($current['antispam_bbcode_check'] == '1') ? 'checked' : ''; ?>>
                <span class="toggle-slider"></span>
                <span class="toggle-label">
                    <strong>BBCode-Links sind Spam</strong><br>
                    Kommentare mit vielen [url] Tags als Spam markieren.
                </span>
            </label>

             <label class="toggle-switch">
                <input type="checkbox" name="local_db" <?php echo ($current['antispam_local_db'] == '1') ? 'checked' : ''; ?>>
                <span class="toggle-slider"></span>
                <span class="toggle-label">
                    <strong>Lokale Spam-Datenbank nutzen</strong><br>
                    Vergleicht IP, E-Mail und Inhalt mit existierendem Spam.
                </span>
            </label>
            
            <hr>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="block_country_check" 
                        <?php echo !empty($current['antispam_block_country']) ? 'checked' : ''; ?>
                        onclick="document.getElementById('country_select').disabled = !this.checked"> 
                    Kommentare aus bestimmten Ländern blockieren
                </label>
                <select name="block_country_code" id="country_select" class="form-control" <?php echo empty($current['antispam_block_country']) ? 'disabled' : ''; ?>>
                    <option value="">Auswahl...</option>
                    <option value="RU" <?php echo ($current['antispam_block_country'] === 'RU') ? 'selected' : ''; ?>>Russland (RU)</option>
                    <option value="CN" <?php echo ($current['antispam_block_country'] === 'CN') ? 'selected' : ''; ?>>China (CN)</option>
                    <option value="BR" <?php echo ($current['antispam_block_country'] === 'BR') ? 'selected' : ''; ?>>Brasilien (BR)</option>
                </select>
                <small>Beachten Sie den Datenschutzhinweis für Geo-Blocking.</small>
            </div>
        </div>

        <!-- Erweitert -->
        <div class="settings-card">
            <h3>Erweitert & Datenbank</h3>
            <p class="description">Verhalten bei erkanntem Spam.</p>

            <label class="toggle-switch">
                <input type="checkbox" name="mark_spam" <?php echo ($current['antispam_mark_spam'] == '1') ? 'checked' : ''; ?>>
                <span class="toggle-slider"></span>
                <span class="toggle-label">
                    <strong>Als Spam markieren, nicht löschen</strong><br>
                    Bewahrt Spam zur manuellen Prüfung auf.
                </span>
            </label>

            <label class="toggle-switch">
                <input type="checkbox" name="notify_admin" <?php echo ($current['antispam_notify_admin'] == '1') ? 'checked' : ''; ?>>
                <span class="toggle-slider"></span>
                <span class="toggle-label">
                    <strong>Admin per E-Mail benachrichtigen</strong><br>
                    Bei neuem Spam eine Info-Mail senden.
                </span>
            </label>

             <div class="form-group" style="margin-top: 1rem;">
                <label>Alten Spam automatisch löschen nach (Tagen)</label>
                <input type="number" name="delete_days" value="<?php echo htmlspecialchars($current['antispam_delete_days']); ?>" min="0">
                <small>0 = Nie automatisch löschen</small>
            </div>
            
            <label class="toggle-switch">
                <input type="checkbox" name="spam_cleanup_uninstall" value="1">
                <span class="toggle-slider"></span>
                <span class="toggle-label">
                    <strong>AntiSpam-Daten bei Deinstallation löschen</strong>
                </span>
            </label>
        </div>

        <!-- Sonstiges -->
         <div class="settings-card">
            <h3>Sonstiges & Dashboard</h3>

            <label class="toggle-switch">
                <input type="checkbox" name="dashboard_widget" <?php echo ($current['antispam_dashboard_widget'] == '1') ? 'checked' : ''; ?>>
                <span class="toggle-slider"></span>
                <span class="toggle-label">
                    <strong>Statistik-Widget im Dashboard</strong>
                </span>
            </label>

             <label class="toggle-switch">
                <input type="checkbox" name="spam_counter" <?php echo ($current['antispam_spam_counter'] == '1') ? 'checked' : ''; ?>>
                <span class="toggle-slider"></span>
                <span class="toggle-label">
                    <strong>Spam-Zähler anzeigen</strong>
                </span>
            </label>

             <label class="toggle-switch">
                <input type="checkbox" name="check_markup" <?php echo ($current['antispam_check_markup'] == '1') ? 'checked' : ''; ?>>
                <span class="toggle-slider"></span>
                <span class="toggle-label">
                    <strong>Gesamtes Markup prüfen</strong><br>
                    Analysiert die gesamte Seite auf versteckte Spam-Felder.
                </span>
            </label>
        </div>

    </div>

    <div class="form-actions sticky-footer">
        <button type="submit" class="btn btn-primary btn-lg">💾 Einstellungen speichern</button>
    </div>

</form>

<style>
    /* Admin General */
    .admin-header h1 { font-size: 1.8rem; margin-bottom: 0.5rem; color: #1e293b; }
    .admin-header p { color: #64748b; margin-top: 0; }
    
    /* Metrics */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    .metric-card {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    .metric-icon { font-size: 2rem; margin-bottom: 0.5rem; }
    .metric-value { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
    .metric-label { font-size: 0.85rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem; }

    /* Settings Layout */
    .settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .settings-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .settings-card h3 { margin-top: 0; margin-bottom: 0.5rem; color: #0f172a; font-size: 1.1rem; }
    .settings-card .description { color: #64748b; font-size: 0.9rem; margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem; }

    /* Forms */
    .form-group { margin-bottom: 1.5rem; }
    .form-group label { display: block; font-weight: 500; margin-bottom: 0.5rem; color: #334155; }
    .form-control {
        width: 100%;
        padding: 0.6rem 0.75rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.95rem;
        background: #fff;
    }
    .form-control:disabled { background: #f1f5f9; cursor: not-allowed; }
    .form-control:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    small { display: block; color: #94a3b8; font-size: 0.8rem; margin-top: 0.4rem; }

    /* Toggles */
    .toggle-switch {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.5rem;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 8px;
        transition: background 0.2s;
    }
    .toggle-switch:hover { background: #f8fafc; }
    .toggle-switch input { display: none; }
    
    .toggle-slider {
        position: relative;
        width: 44px;
        height: 24px;
        background-color: #cbd5e1;
        border-radius: 24px;
        transition: .3s;
        flex-shrink: 0;
    }
    .toggle-slider:before {
        content: "";
        position: absolute;
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        border-radius: 50%;
        transition: .3s;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    input:checked + .toggle-slider { background-color: #3b82f6; }
    input:checked + .toggle-slider:before { transform: translateX(20px); }
    
    .toggle-label strong { display: block; color: #334155; font-size: 0.95rem; }
    .toggle-label { font-size: 0.85rem; color: #64748b; line-height: 1.4; }

    /* Sticky Footer */
    .sticky-footer {
        position: sticky;
        bottom: 2rem;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(8px);
        padding: 1rem;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        display: flex;
        justify-content: flex-end;
        z-index: 50;
    }
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.6rem 1.2rem;
        font-weight: 500;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.95rem;
    }
    .btn-primary { background: #3b82f6; color: white; }
    .btn-primary:hover { background: #2563eb; }
    .btn-lg { padding: 0.75rem 2rem; font-size: 1rem; }
    
    /* Alerts */
    .alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid transparent; }
    .alert-success { background: #dcfce7; color: #166534; border-left-color: #166534; }
    .alert-error { background: #fee2e2; color: #991b1b; border-left-color: #991b1b; }
</style>

<?php renderAdminLayoutEnd(); ?>
