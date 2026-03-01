<?php
/**
 * Theme & Design Einstellungen
 *
 * Allgemeine Einstellungen für die Themeverwaltung und den Marketplace.
 * Diese Seite enthält KEINE theme-spezifischen Einstellungen (dafür → Design Editor),
 * sondern übergreifende Konfiguration für das Theme-System.
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$security = Security::instance();
$db       = Database::instance();

$message     = '';
$messageType = '';

// ─── POST Handler ────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {

    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'theme_settings')) {
        $message     = 'Sicherheitsüberprüfung fehlgeschlagen.';
        $messageType = 'error';
    } elseif ($_POST['action'] === 'save_theme_settings') {

        $settingsToSave = [
            'theme_auto_update'         => isset($_POST['theme_auto_update']) ? '1' : '0',
            'theme_marketplace_enabled' => isset($_POST['theme_marketplace_enabled']) ? '1' : '0',
            'theme_preview_mode'        => sanitize_input($_POST['theme_preview_mode'] ?? 'disabled', 'slug'),
            'theme_custom_css_global'   => isset($_POST['theme_custom_css_global']) ? '1' : '0',
            'theme_editor_roles'        => implode(',', array_map(fn($r) => sanitize_input($r, 'slug'), (array)($_POST['theme_editor_roles'] ?? ['admin']))),
        ];

        try {
            foreach ($settingsToSave as $key => $value) {
                $optionName = 'theme_setting_' . $key;
                $existing = $db->execute(
                    "SELECT id FROM {$db->getPrefix()}settings WHERE option_name = ?",
                    [$optionName]
                )->fetch();

                if ($existing) {
                    $db->execute(
                        "UPDATE {$db->getPrefix()}settings SET option_value = ? WHERE option_name = ?",
                        [$value, $optionName]
                    );
                } else {
                    $db->execute(
                        "INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES (?, ?)",
                        [$optionName, $value]
                    );
                }
            }
            $message     = 'Einstellungen wurden gespeichert.';
            $messageType = 'success';
        } catch (\Throwable $e) {
            $message     = 'Fehler beim Speichern: ' . htmlspecialchars($e->getMessage());
            $messageType = 'error';
        }
    }
}

// ─── Helper ──────────────────────────────────────────────────────────────────

function sanitize_input(string $value, string $type): string
{
    return match ($type) {
        'slug' => preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($value))),
        'url'  => filter_var(trim($value), FILTER_SANITIZE_URL),
        default => htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8'),
    };
}

// ─── Settings laden ───────────────────────────────────────────────────────────

function getThemeSetting(Database $db, string $key, string $default = ''): string
{
    $row = $db->execute(
        "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ?",
        ['theme_setting_' . $key]
    )->fetch(\PDO::FETCH_ASSOC);
    return $row ? (string)$row['option_value'] : $default;
}

$current = [
    'theme_auto_update'         => getThemeSetting($db, 'theme_auto_update', '0'),
    'theme_marketplace_enabled' => getThemeSetting($db, 'theme_marketplace_enabled', '0'),
    'theme_preview_mode'        => getThemeSetting($db, 'theme_preview_mode', 'disabled'),
    'theme_custom_css_global'   => getThemeSetting($db, 'theme_custom_css_global', '0'),
    'theme_editor_roles'        => getThemeSetting($db, 'theme_editor_roles', 'admin'),
];

// Rollen als Array für Checkbox-Auswahl
$selectedRoles = array_filter(explode(',', $current['theme_editor_roles']));

// Alle Rollen aus der DB laden für Editor-Zugriff
$allDbRoles = $db->get_results("SELECT name, display_name FROM {$db->getPrefix()}roles ORDER BY sort_order ASC, name ASC");
$editorRoleOptions = [];
foreach ($allDbRoles as $role) {
    $editorRoleOptions[$role->name] = $role->display_name ?: ucfirst($role->name);
}

$csrfToken = $security->generateToken('theme_settings');

require_once __DIR__ . '/partials/admin-menu.php';
renderAdminLayoutStart('Theme-Einstellungen', 'theme-settings');
?>

        <div class="admin-page-header">
            <h2>⚙️ Theme-Einstellungen</h2>
            <p style="color:#64748b;font-size:.875rem;margin:.25rem 0 0;">
                Allgemeine Einstellungen für Themeverwaltung &amp; Marketplace —
                unabhängig vom aktiven Theme.
            </p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo SITE_URL; ?>/admin/theme-settings">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action"     value="save_theme_settings">

            <!-- ── Allgemeine Theme-Verwaltung ───────────────────────────── -->
            <div class="settings-card">
                <div class="sc-header">
                    <span style="font-size:1.25rem;">🎨</span>
                    <div>
                        <h3>Theme-Verwaltung</h3>
                        <p>Standardverhalten beim Theme-Wechsel und Editor-Zugriff</p>
                    </div>
                </div>
                <div class="sc-body">

                    <div class="form-row">
                        <div>
                            <div class="form-label">Auto-Updates</div>
                            <div class="form-hint">Marketplace-Themes automatisch aktualisieren</div>
                        </div>
                        <div class="toggle-row">
                            <label class="toggle-label">
                                <input type="checkbox" name="theme_auto_update" value="1"
                                    <?php echo $current['theme_auto_update'] === '1' ? 'checked' : ''; ?>>
                                Automatische Updates aktivieren
                            </label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <div class="form-label">Desktop-Vorschau</div>
                            <div class="form-hint">Live-Vorschau beim Theme-Wechsel</div>
                        </div>
                        <select name="theme_preview_mode" class="form-select" style="max-width:240px;">
                            <?php foreach (['disabled' => 'Deaktiviert', 'iframe' => 'iFrame-Vorschau', 'tab' => 'Neues Tab'] as $v => $l): ?>
                                <option value="<?php echo $v; ?>" <?php echo $current['theme_preview_mode'] === $v ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($l); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div>
                            <div class="form-label">Globales Custom-CSS</div>
                            <div class="form-hint">CSS-Editor im Design-Bereich für alle Themes zugänglich</div>
                        </div>
                        <div class="toggle-row">
                            <label class="toggle-label">
                                <input type="checkbox" name="theme_custom_css_global" value="1"
                                    <?php echo $current['theme_custom_css_global'] === '1' ? 'checked' : ''; ?>>
                                Theme-übergreifendes CSS erlauben
                            </label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <div class="form-label">Editor-Zugriff</div>
                            <div class="form-hint">Welche Rollen d&uuml;rfen den Design Editor nutzen?</div>
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:.75rem;">
                            <?php foreach ($editorRoleOptions as $v => $l): ?>
                            <label class="toggle-label" style="min-width:140px;">
                                <input type="checkbox" name="theme_editor_roles[]" value="<?php echo htmlspecialchars($v); ?>"
                                    <?php echo in_array($v, $selectedRoles) ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($l); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Marketplace-Einstellungen → Coming Soon, wird in Zukunft hier ergänzt -->

            <!-- ── Speichern ─────────────────────────────────────────────── -->
            <div style="display:flex;gap:1rem;align-items:center;">
                <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
                <a href="<?php echo SITE_URL; ?>/admin/themes" style="font-size:.875rem;color:#64748b;text-decoration:none;">
                    ← Zur Themeverwaltung
                </a>
            </div>

        </form>

    </div><!-- /.admin-content -->

<?php renderAdminLayoutEnd(); ?>
