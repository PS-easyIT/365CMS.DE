<?php
declare(strict_types=1);

/**
 * AntiSpam Dashboard & Einstellungen
 * 
 * Basierend auf Antispam Bee Logik für 365CMS
 * 
 * @package CMSv2\Admin
 */

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
$security = Security::instance();
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
    if (!$security->verifyToken($_POST['_csrf_token'] ?? '', 'save_antispam')) {
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

try {
    $today = date('Y-m-d');
    $blockedToday = $db->fetchOne(
        "SELECT COUNT(*) as c FROM {$db->getPrefix()}comments WHERE status = 'spam' AND DATE(post_date) = ?",
        [$today]
    )['c'] ?? 0;
} catch (\Exception $e) { $blockedToday = 0; }

// Verhältnis Spam zu Gesamt (nur wenn Gesamt > 0)
try {
    $totalComments = $db->fetchOne("SELECT COUNT(*) as c FROM {$db->getPrefix()}comments")['c'] ?? 0;
    $accuracy = ($totalComments > 0 && $spamCountTotal > 0)
        ? round(($spamCountTotal / $totalComments) * 100, 1)
        : 0.0;
} catch (\Exception $e) { $accuracy = 0.0; }

$csrfToken = $security->generateToken('save_antispam');

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
        <div class="metric-label">Spam-Quote (Spam/Gesamt)</div>
    </div>
</div>

<form method="post" class="admin-form" style="margin-top: 2rem;">
    <input type="hidden" name="save_antispam" value="1">
    <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    
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
                <input type="checkbox" name="regex_check" <?php echo ($current['antispam_regex_check'] == '1') ? 'checked' : ''; ?>>
                <span class="toggle-slider"></span>
                <span class="toggle-label">
                    <strong>Regex-Filter aktiv</strong><br>
                    Scannt Kommentarinhalte auf benutzerdefinierte Muster.
                </span>
            </label>
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
                <input type="checkbox" name="ignore_trackbacks" <?php echo ($current['antispam_ignore_trackbacks'] == '1') ? 'checked' : ''; ?>>
                <span class="toggle-slider"></span>
                <span class="toggle-label">
                    <strong>Trackbacks & Pingbacks ignorieren</strong><br>
                    Trackbacks werden nicht als Spam-Kandidaten geprüft.
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

<?php renderAdminLayoutEnd(); ?>
