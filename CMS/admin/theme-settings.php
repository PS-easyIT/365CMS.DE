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
            'theme_editor_role'         => sanitize_input($_POST['theme_editor_role'] ?? 'admin', 'slug'),
            'marketplace_source'        => sanitize_input($_POST['marketplace_source'] ?? 'official', 'text'),
            'marketplace_api_url'       => filter_var($_POST['marketplace_api_url'] ?? '', FILTER_SANITIZE_URL),
            'marketplace_license_key'   => sanitize_input($_POST['marketplace_license_key'] ?? '', 'text'),
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
    'theme_editor_role'         => getThemeSetting($db, 'theme_editor_role', 'admin'),
    'marketplace_source'        => getThemeSetting($db, 'marketplace_source', 'official'),
    'marketplace_api_url'       => getThemeSetting($db, 'marketplace_api_url', ''),
    'marketplace_license_key'   => getThemeSetting($db, 'marketplace_license_key', ''),
];

$csrfToken = $security->generateToken('theme_settings');

require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme-Einstellungen – <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=20260222b">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">

    <?php renderAdminSidebar('theme-settings'); ?>

    <div class="admin-content">

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
                            <div class="form-hint">Minimale Rolle für den Design Editor</div>
                        </div>
                        <select name="theme_editor_role" class="form-select" style="max-width:200px;">
                            <?php foreach (['admin' => 'Administrator', 'editor' => 'Redakteur'] as $v => $l): ?>
                                <option value="<?php echo $v; ?>" <?php echo $current['theme_editor_role'] === $v ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($l); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>
            </div>

            <!-- ── Marketplace-Einstellungen ─────────────────────────────── -->
            <div class="settings-card">
                <div class="sc-header">
                    <span style="font-size:1.25rem;">🛍️</span>
                    <div>
                        <h3>Marketplace-Verbindung</h3>
                        <p>API-Verbindung zum Theme &amp; Plugin Marketplace</p>
                    </div>
                </div>
                <div class="sc-body">

                    <div class="info-box-cs">
                        <strong>⏳ Coming Soon</strong>
                        Der Marketplace befindet sich in Entwicklung. Du kannst hier schon die API-Zugangsdaten
                        vorbereiten. Mehr Infos im
                        <a href="<?php echo SITE_URL; ?>/admin/docs/marketplace-konzept" style="color:#b45309;font-weight:600;" onclick="return false;">
                            Marketplace-Konzept (Doku)
                        </a>.
                    </div>

                    <div class="form-row">
                        <div>
                            <div class="form-label">Marketplace aktiv</div>
                            <div class="form-hint">Marketplace-Features im Admin einblenden</div>
                        </div>
                        <div class="toggle-row">
                            <label class="toggle-label">
                                <input type="checkbox" name="theme_marketplace_enabled" value="1"
                                    <?php echo $current['theme_marketplace_enabled'] === '1' ? 'checked' : ''; ?>>
                                Marketplace-Bereich aktivieren
                            </label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <div class="form-label">Marketplace-Quelle</div>
                            <div class="form-hint">Offiziell oder eigener Server</div>
                        </div>
                        <select name="marketplace_source" class="form-select" style="max-width:240px;">
                            <option value="official" <?php echo $current['marketplace_source'] === 'official' ? 'selected' : ''; ?>>
                                Offizieller 365CMS Marketplace
                            </option>
                            <option value="github" <?php echo $current['marketplace_source'] === 'github' ? 'selected' : ''; ?>>
                                GitHub-basierter Marketplace
                            </option>
                            <option value="custom" <?php echo $current['marketplace_source'] === 'custom' ? 'selected' : ''; ?>>
                                Eigener Webspace / Server
                            </option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div>
                            <div class="form-label">Marketplace API-URL</div>
                            <div class="form-hint">Nur für "GitHub" oder "Eigener Server"</div>
                        </div>
                        <input type="url" name="marketplace_api_url" class="form-input"
                               placeholder="https://api.dein-marketplace.de/v1"
                               value="<?php echo htmlspecialchars($current['marketplace_api_url'], ENT_QUOTES); ?>">
                    </div>

                    <div class="form-row">
                        <div>
                            <div class="form-label">Lizenzschlüssel</div>
                            <div class="form-hint">Für Premium-Lizenzen &amp; Auto-Updates</div>
                        </div>
                        <input type="text" name="marketplace_license_key" class="form-input"
                               placeholder="XXXX-XXXX-XXXX-XXXX"
                               autocomplete="off"
                               value="<?php echo htmlspecialchars($current['marketplace_license_key'], ENT_QUOTES); ?>">
                    </div>

                </div>
            </div>

            <!-- ── Speichern ─────────────────────────────────────────────── -->
            <div style="display:flex;gap:1rem;align-items:center;">
                <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
                <a href="<?php echo SITE_URL; ?>/admin/themes" style="font-size:.875rem;color:#64748b;text-decoration:none;">
                    ← Zur Themeverwaltung
                </a>
            </div>

        </form>

    </div><!-- /.admin-content -->

</body>
</html>
