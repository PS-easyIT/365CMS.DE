<?php
/**
 * Admin: Theme-Verwaltung
 *
 * Verwaltet die installierten Themes:
 * - Anzeigen aller installierten Themes
 * - Aktivieren eines Themes
 * - Löschen inaktiver Themes
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\ThemeManager;

if (!defined('ABSPATH')) {
    exit;
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$security     = Security::instance();
$themeManager = ThemeManager::instance();

$message     = '';
$messageType = '';

// ─── POST Handler ────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {

    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'admin_themes')) {
        $message     = 'Sicherheitsüberprüfung fehlgeschlagen.';
        $messageType = 'error';
    } else {

        $action = $_POST['action'];

        // ── Theme aktivieren ────────────────────────────────────────────────
        if ($action === 'activate_theme') {
            $folder = basename(preg_replace('/[^a-z0-9_-]/i', '', $_POST['theme_folder'] ?? ''));
            $result = $themeManager->switchTheme($folder);
            if ($result === true) {
                $message     = 'Theme "' . htmlspecialchars($folder) . '" wurde aktiviert.';
                $messageType = 'success';
            } else {
                $message     = is_string($result) ? $result : 'Fehler beim Aktivieren.';
                $messageType = 'error';
            }

        // ── Theme löschen ───────────────────────────────────────────────────
        } elseif ($action === 'delete_theme') {
            $folder = basename(preg_replace('/[^a-z0-9_-]/i', '', $_POST['theme_folder'] ?? ''));
            $result = $themeManager->deleteTheme($folder);
            if ($result === true) {
                $message     = 'Theme wurde gelöscht.';
                $messageType = 'success';
            } else {
                $message     = is_string($result) ? $result : 'Fehler beim Löschen.';
                $messageType = 'error';
            }
        }

        // Redirect – verhindert Formular-Resubmission
        $redirect = SITE_URL . '/admin/themes';
        if ($messageType) {
            $redirect .= '?message=' . urlencode($message) . '&type=' . $messageType;
        }
        header('Location: ' . $redirect);
        exit;
    }
}

// ─── GET-Nachrichten aus Redirect ────────────────────────────────────────────

if (isset($_GET['message'])) {
    $message     = htmlspecialchars(urldecode($_GET['message']), ENT_QUOTES, 'UTF-8');
    $messageType = ($_GET['type'] ?? '') === 'success' ? 'success' : 'error';
}

// ─── Daten laden ─────────────────────────────────────────────────────────────

$csrfToken  = $security->generateToken('admin_themes');
$allThemes  = $themeManager->getAvailableThemes();

// Admin-Menü partial laden
require_once __DIR__ . '/partials/admin-menu.php';

// ─── HTML ─────────────────────────────────────────────────────────────────────

?>
<?php renderAdminLayoutStart('Theme-Verwaltung', 'themes'); ?>

        <!-- Page Header -->
                <div class="page-header d-print-none mb-3">
            <div class="row align-items-center">
                <div class="col-auto">
                    <h2 class="page-title">🎨 Theme-Verwaltung</h2>
                </div>
            </div>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- ── Templates-Verwaltung ─────────────────────────────────────── -->
        <div class="info-box">
            <strong>Theme-Verwaltung</strong>
            Hier siehst du alle installierten Themes. Aktiviere ein Theme oder lösche nicht mehr benötigte.
            Das aktuell aktive Theme sowie das letzte verbliebene Theme können nicht gelöscht werden.
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem;">
            <?php foreach ($allThemes as $theme):
                $isActive   = $theme['active'] ?? false;
                $canDelete  = !$isActive && count($allThemes) > 1;
                $folder     = htmlspecialchars($theme['folder'] ?? '', ENT_QUOTES);
                $themeName  = htmlspecialchars($theme['name']   ?? $theme['folder'], ENT_QUOTES);
                $themeDesc  = htmlspecialchars($theme['description'] ?? '', ENT_QUOTES);
                $themeVer   = htmlspecialchars($theme['version'] ?? '', ENT_QUOTES);
                $themeAuth  = htmlspecialchars($theme['author']  ?? '', ENT_QUOTES);
            ?>
            <div style="background:#fff;border:2px solid <?php echo $isActive ? '#3b82f6' : '#e2e8f0'; ?>;border-radius:10px;padding:1.25rem;position:relative;">
                <?php if ($isActive): ?>
                    <span style="position:absolute;top:0.75rem;right:0.75rem;background:#3b82f6;color:#fff;
                                 font-size:0.7rem;font-weight:700;padding:0.2rem 0.6rem;border-radius:20px;
                                 text-transform:uppercase;letter-spacing:0.05em;">Aktiv</span>
                <?php endif; ?>

                <h3 style="margin:0 0 0.25rem;font-size:1rem;font-weight:700;">
                    🎨 <?php echo $themeName; ?>
                </h3>
                <?php if ($themeVer): ?>
                    <p style="margin:0 0 0.5rem;font-size:0.75rem;color:#9ca3af;">v<?php echo $themeVer; ?> · <?php echo $themeAuth ?: 'unbekannt'; ?></p>
                <?php endif; ?>
                <?php if ($themeDesc): ?>
                    <p style="margin:0 0 1rem;font-size:0.8125rem;color:#6b7280;line-height:1.4;"><?php echo $themeDesc; ?></p>
                <?php endif; ?>
                <p style="margin:0 0 1rem;font-size:0.75rem;color:#9ca3af;font-family:monospace;">
                    📁 <?php echo $folder; ?>
                </p>

                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                    <?php if (!$isActive): ?>
                        <form method="POST" action="<?php echo SITE_URL; ?>/admin/themes">
                            <input type="hidden" name="csrf_token"   value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action"       value="activate_theme">
                            <input type="hidden" name="theme_folder" value="<?php echo $folder; ?>">
                            <button type="submit" class="btn btn-primary btn-sm">✅ Aktivieren</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($canDelete): ?>
                        <form method="POST" action="<?php echo SITE_URL; ?>/admin/themes"
                              onsubmit="return confirm('Theme \"<?php echo $themeName; ?>\" wirklich löschen? Das Verzeichnis wird dauerhaft entfernt.');">
                            <input type="hidden" name="csrf_token"   value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action"       value="delete_theme">
                            <input type="hidden" name="theme_folder" value="<?php echo $folder; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">🗑 Löschen</button>
                        </form>
                    <?php else: ?>
                        <button class="btn btn-danger btn-sm" disabled
                                title="<?php echo $isActive ? 'Aktives Theme kann nicht gelöscht werden' : 'Letztes Theme – nicht löschbar'; ?>"
                                style="opacity:0.4;cursor:not-allowed;">🗑 Löschen</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($allThemes)): ?>
            <div class="alert alert-danger">Keine Themes gefunden. Bitte mindestens einen Theme-Ordner mit style.css im themes/-Verzeichnis anlegen.</div>
        <?php endif; ?>

<?php renderAdminLayoutEnd(); ?>
