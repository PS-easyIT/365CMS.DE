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
        $message = '<div class="alert alert-danger">Sicherheitsprüfung fehlgeschlagen.</div>';
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
             $message = '<div class="alert alert-danger">Fehler: ' . $e->getMessage() . '</div>';
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
?>
<?php renderAdminLayoutStart('AntiSpam Schutz', 'antispam'); ?>

                <div class="page-header d-print-none mb-3">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="page-pretitle">Einstellungen und Statistiken zum Schutz vor Kommentar-Spam</div>
                    <h2 class="page-title">🛡️ AntiSpam Schutz</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <!-- Optional: Reset Button or Help -->
                </div>
            </div>
        </div>

        <?php echo $message; ?>

        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Blockiert (Gesamt)</h3>
                <div class="stat-number"><?php echo number_format((float)$spamCountTotal); ?></div>
                <div class="stat-label">🚫 Spam-Kommentare</div>
            </div>
            
            <div class="stat-card">
                <h3>Heute blockiert</h3>
                <div class="stat-number"><?php echo number_format($blockedToday); ?></div>
                <div class="stat-label">📅 Heute</div>
            </div>
            
            <div class="stat-card">
                <h3>Spam-Quote</h3>
                <div class="stat-number"><?php echo number_format($accuracy, 1); ?>%</div>
                <div class="stat-label">🎯 Spam/Gesamt Verhältnis</div>
            </div>
        </div>

        <form method="post" class="admin-form">
            <input type="hidden" name="save_antispam" value="1">
            <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div class="card">
                <h3>Antispam-Filter</h3>
                <p style="color:#64748b; margin-bottom:1.5rem;">Regeln zur Erkennung von Spam-Kommentaren.</p>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="trust_approved" <?php echo ($current['antispam_trust_approved'] == '1') ? 'checked' : ''; ?>>
                        <strong>Genehmigten Kommentatoren vertrauen</strong>
                    </label>
                    <small class="form-hint" style="margin-left:1.7rem;">Benutzer nicht prüfen, die bereits erfolgreich kommentiert haben.</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="check_time" <?php echo ($current['antispam_check_time'] == '1') ? 'checked' : ''; ?>>
                        <strong>Kommentarzeit berücksichtigen</strong>
                    </label>
                    <small class="form-hint" style="margin-left:1.7rem;">Erkennt Bots, die Formulare zu schnell ausfüllen.</small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="bbcode_check" <?php echo ($current['antispam_bbcode_check'] == '1') ? 'checked' : ''; ?>>
                        <strong>BBCode-Links sind Spam</strong>
                    </label>
                    <small class="form-hint" style="margin-left:1.7rem;">Kommentare mit vielen [url] Tags als Spam markieren.</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="regex_check" <?php echo ($current['antispam_regex_check'] == '1') ? 'checked' : ''; ?>>
                        <strong>Regex-Filter aktiv</strong>
                    </label>
                    <small class="form-hint" style="margin-left:1.7rem;">Scannt Kommentarinhalte auf benutzerdefinierte Muster.</small>
                </div>
                
                <hr style="border:0; border-top:1px solid #e2e8f0; margin:1.5rem 0;">
                
                <div class="form-group">
                    <label class="checkbox-label" style="margin-bottom:0.5rem;">
                        <input type="checkbox" name="block_country_check" 
                            <?php echo !empty($current['antispam_block_country']) ? 'checked' : ''; ?>
                            onclick="document.getElementById('country_select').disabled = !this.checked"> 
                        <strong>Kommentare aus bestimmten Ländern blockieren</strong>
                    </label>
                    <div style="margin-left:1.7rem;">
                        <select name="block_country_code" id="country_select" class="form-select" style="max-width:300px;" <?php echo empty($current['antispam_block_country']) ? 'disabled' : ''; ?>>
                            <option value="">Auswahl...</option>
                            <option value="RU" <?php echo ($current['antispam_block_country'] === 'RU') ? 'selected' : ''; ?>>Russland (RU)</option>
                            <option value="CN" <?php echo ($current['antispam_block_country'] === 'CN') ? 'selected' : ''; ?>>China (CN)</option>
                            <option value="BR" <?php echo ($current['antispam_block_country'] === 'BR') ? 'selected' : ''; ?>>Brasilien (BR)</option>
                        </select>
                        <small class="form-hint">Beachten Sie den Datenschutzhinweis für Geo-Blocking.</small>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3>Erweitert & Datenbank</h3>
                <p style="color:#64748b; margin-bottom:1.5rem;">Verhalten bei erkanntem Spam.</p>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="mark_spam" <?php echo ($current['antispam_mark_spam'] == '1') ? 'checked' : ''; ?>>
                        <strong>Als Spam markieren, nicht löschen</strong>
                    </label>
                    <small class="form-hint" style="margin-left:1.7rem;">Bewahrt Spam zur manuellen Prüfung auf.</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="notify_admin" <?php echo ($current['antispam_notify_admin'] == '1') ? 'checked' : ''; ?>>
                        <strong>Admin per E-Mail benachrichtigen</strong>
                    </label>
                    <small class="form-hint" style="margin-left:1.7rem;">Bei neuem Spam eine Info-Mail senden.</small>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label class="form-label">Alten Spam automatisch löschen nach (Tagen)</label>
                    <input type="number" name="delete_days" class="form-control" style="max-width:150px;" value="<?php echo htmlspecialchars($current['antispam_delete_days']); ?>" min="0">
                    <small class="form-hint">0 = Nie automatisch löschen</small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="spam_cleanup_uninstall" value="1">
                        <strong>AntiSpam-Daten bei Deinstallation löschen</strong>
                    </label>
                </div>
            </div>

            <div class="card">
                <h3>Sonstiges & Dashboard</h3>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="dashboard_widget" <?php echo ($current['antispam_dashboard_widget'] == '1') ? 'checked' : ''; ?>>
                        <strong>Statistik-Widget im Dashboard</strong>
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="spam_counter" <?php echo ($current['antispam_spam_counter'] == '1') ? 'checked' : ''; ?>>
                        <strong>Spam-Zähler anzeigen</strong>
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="ignore_trackbacks" <?php echo ($current['antispam_ignore_trackbacks'] == '1') ? 'checked' : ''; ?>>
                        <strong>Trackbacks & Pingbacks ignorieren</strong>
                    </label>
                    <small class="form-hint" style="margin-left:1.7rem;">Trackbacks werden nicht als Spam-Kandidaten geprüft.</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="check_markup" <?php echo ($current['antispam_check_markup'] == '1') ? 'checked' : ''; ?>>
                        <strong>Gesamtes Markup prüfen</strong>
                    </label>
                    <small class="form-hint" style="margin-left:1.7rem;">Analysiert die gesamte Seite auf versteckte Spam-Felder.</small>
                </div>
            </div>

            <div class="card form-actions-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
                    <span class="form-actions__hint">Änderungen werden sofort übernommen</span>
                </div>
            </div>

        </form>

<?php renderAdminLayoutEnd(); ?>
