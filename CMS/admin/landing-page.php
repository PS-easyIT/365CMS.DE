<?php
/**
 * Admin Landing Page Management
 *
 * Sections: header | content | footer | design | settings
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

// Load configuration first
require_once dirname(__DIR__) . '/config.php';

// Load autoloader
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Services\LandingPageService;
use CMS\Services\EditorService;
use CMS\Security;

if (!defined('ABSPATH')) {
    exit;
}

// Security check
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$auth           = Auth::instance();
$landingService = LandingPageService::getInstance();

// Active section (default: header)
$validSections = ['header', 'content', 'footer', 'design', 'settings', 'plugins'];
$activeSection = $_GET['section'] ?? 'header';
if (!in_array($activeSection, $validSections, true)) {
    $activeSection = 'header';
}

// Handle Landing Page form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['landing_action'])) {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'landing_page')) {
        $error = 'Sicherheitscheck fehlgeschlagen';
    } else {
        switch ($_POST['landing_action']) {
            case 'update_header':
                // Remove logo
                if (isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
                    $_POST['logo'] = '';
                } elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = dirname(__DIR__) . '/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $fileExt     = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                    $allowedExts = ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp'];
                    if (in_array($fileExt, $allowedExts, true)) {
                        $fileName   = 'logo-' . time() . '.' . $fileExt;
                        $targetPath = $uploadDir . $fileName;
                        if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                            $_POST['logo'] = 'uploads/' . $fileName;
                        }
                    } else {
                        $error = 'Nur Bild-Dateien (JPG, PNG, WebP, SVG, GIF) sind erlaubt';
                    }
                } elseif (isset($_POST['existing_logo']) && !empty($_POST['existing_logo'])) {
                    $_POST['logo'] = $_POST['existing_logo'];
                }

                if (!isset($error)) {
                    if (!isset($_POST['logo_position'])) {
                        $_POST['logo_position'] = 'top';
                    }
                    $landingService->updateHeader($_POST);
                    $success = 'Header erfolgreich aktualisiert';
                }
                break;

            case 'update_footer':
                if ($landingService->updateFooter($_POST)) {
                    $success = 'Footer erfolgreich aktualisiert';
                } else {
                    $error = 'Fehler beim Speichern des Footers';
                }
                break;

            case 'save_feature':
                $featureId = !empty($_POST['feature_id']) ? (int)$_POST['feature_id'] : null;
                $landingService->saveFeature($featureId, $_POST);
                $success = 'Feature erfolgreich gespeichert';
                break;

            case 'delete_feature':
                $landingService->deleteFeature((int)$_POST['feature_id']);
                $success = 'Feature erfolgreich gelöscht';
                break;

            case 'update_content_mode':
            case 'update_content_text':
                $landingService->updateContentSettings($_POST);
                $success = 'Content-Einstellung gespeichert';
                break;

            case 'update_colors':
                $landingService->updateColors($_POST);
                $success = 'Farbschema gespeichert';
                break;

            case 'update_design':
                $landingService->updateDesign($_POST);
                $success = 'Design-Einstellungen gespeichert';
                break;

            case 'update_settings':
                $landingService->updateSettings($_POST);
                $success = 'Einstellungen gespeichert';
                break;

            case 'update_plugin_override':
                $area     = $_POST['area']      ?? '';
                $pluginId = $_POST['plugin_id'] ?? '';
                if ($landingService->updatePluginOverride(['area' => $area, 'plugin_id' => $pluginId])) {
                    $success = $pluginId !== ''
                        ? 'Plugin-Override für ' . htmlspecialchars($area) . ' aktiviert'
                        : 'CMS-Standard für ' . htmlspecialchars($area) . ' wiederhergestellt';
                } else {
                    $error = 'Fehler beim Speichern des Plugin-Overrides';
                }
                break;

            case 'save_plugin_settings':
                $pluginId = $_POST['plugin_id'] ?? '';
                unset($_POST['landing_action'], $_POST['csrf_token'], $_POST['plugin_id']);
                if ($pluginId !== '' && $landingService->savePluginSettings($pluginId, $_POST)) {
                    $success = 'Plugin-Einstellungen gespeichert';
                } else {
                    $error = 'Fehler beim Speichern der Plugin-Einstellungen';
                }
                break;
        }
    }
}

// ── Load Data ────────────────────────────────────────────────────────────────
$landingHeader   = $landingService->getHeader();
$landingFeatures = $landingService->getFeatures();
$landingFooter   = $landingService->getFooter();
$landingColors   = $landingService->getColors();
$landingSettings    = $landingService->getSettings();
$landingContent     = $landingService->getContentSettings();
$landingDesign      = $landingService->getDesign();
$contentMode        = $landingContent['content_type'] ?? 'features';
$contentText        = $landingContent['content_text'] ?? '';
$postsCount         = (int)($landingContent['posts_count'] ?? 5);
$registeredPlugins  = $landingService->getRegisteredPlugins();
$pluginOverrides    = $landingService->getPluginOverrides();

$csrfToken = Security::instance()->generateToken('landing_page');


// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page – <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=20260222">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">

    <?php renderAdminSidebar('landing-page'); ?>

    <div class="admin-content">

        <!-- Page Header -->
        <div class="admin-page-header">
            <h2>🖥️ Landing Page</h2>
            <p>Gestalte die &ouml;ffentliche Startseite deines CMS</p>
        </div>

        <!-- Section Navigation -->
        <nav class="lp-section-nav">
            <a href="?section=header" class="lp-section-nav__item <?php echo $activeSection === 'header'   ? 'active' : ''; ?>">
                <span class="lp-section-nav__icon">🔝</span> Header
            </a>
            <a href="?section=content" class="lp-section-nav__item <?php echo $activeSection === 'content'  ? 'active' : ''; ?>">
                <span class="lp-section-nav__icon">📋</span> Content
            </a>
            <a href="?section=footer" class="lp-section-nav__item <?php echo $activeSection === 'footer'   ? 'active' : ''; ?>">
                <span class="lp-section-nav__icon">🔚</span> Footer
            </a>
            <a href="?section=design" class="lp-section-nav__item <?php echo $activeSection === 'design'   ? 'active' : ''; ?>">
                <span class="lp-section-nav__icon">🎨</span> Design
            </a>
            <a href="?section=plugins" class="lp-section-nav__item <?php echo $activeSection === 'plugins'  ? 'active' : ''; ?>">
                <span class="lp-section-nav__icon">🔌</span> Plugins
            </a>
            <a href="?section=settings" class="lp-section-nav__item <?php echo $activeSection === 'settings' ? 'active' : ''; ?>">
                <span class="lp-section-nav__icon">⚙️</span> Einstellungen
            </a>
        </nav>

        <!-- Messages -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php /* ═══════════ SECTION: HEADER ══════════════════════════════ */ ?>
        <?php if ($activeSection === 'header'): ?>

            <form method="POST" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="landing_action" value="update_header">
                <input type="hidden" name="csrf_token"     value="<?php echo $csrfToken; ?>">
                <?php foreach (['hero_gradient_start','hero_gradient_end','hero_border','hero_text','features_bg','feature_card_bg','feature_card_hover','primary_button'] as $_ck): ?>
                <input type="hidden" name="<?php echo $_ck; ?>" value="<?php echo htmlspecialchars($landingColors[$_ck] ?? ''); ?>">
                <?php endforeach; ?>

                <div class="lp-card">
                    <h4>🖼️ Logo &amp; Layout</h4>
                    <div class="lp-logo-box">
                        <div class="lp-logo-box__upload">
                            <?php if (!empty($landingHeader['logo'])): ?>
                                <div style="margin-bottom:.75rem;">
                                    <img src="<?php echo SITE_URL . '/' . htmlspecialchars($landingHeader['logo']); ?>"
                                         alt="Aktuelles Logo"
                                         style="max-height:60px;max-width:100%;border:1px solid #e2e8f0;border-radius:4px;padding:4px;background:#fff;">
                                </div>
                                <input type="hidden" name="existing_logo" value="<?php echo htmlspecialchars($landingHeader['logo']); ?>">
                                <label style="display:inline-flex;align-items:center;gap:6px;color:#dc2626;font-size:.85em;cursor:pointer;margin-bottom:.75rem;">
                                    <input type="checkbox" name="remove_logo" value="1"> 🗑️ Logo entfernen
                                </label>
                            <?php endif; ?>
                            <label for="logo" class="form-label" style="font-size:.9em;display:block;margin-bottom:.25rem;">Neues Logo hochladen</label>
                            <input type="file" id="logo" name="logo" class="form-control" accept="image/*">
                            <small class="text-muted">JPG, PNG, WebP, SVG oder GIF</small>
                        </div>
                        <div class="lp-logo-box__options">
                            <div style="margin-bottom:1.25rem;">
                                <label class="form-label" style="font-weight:600;">Logo Position</label>
                                <div style="display:flex;flex-direction:column;gap:.5rem;margin-top:.4rem;">
                                    <label class="radio-label">
                                        <input type="radio" name="logo_position" value="top"
                                               <?php echo ($landingHeader['logo_position'] ?? 'top') === 'top' ? 'checked' : ''; ?>>
                                        ⬆️ Oberhalb des Titels
                                    </label>
                                    <label class="radio-label">
                                        <input type="radio" name="logo_position" value="left"
                                               <?php echo ($landingHeader['logo_position'] ?? 'top') === 'left' ? 'checked' : ''; ?>>
                                        ⬅️ Links neben dem Titel
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="form-label" style="font-weight:600;">Header Layout</label>
                                <div style="display:flex;flex-direction:column;gap:.5rem;margin-top:.4rem;">
                                    <label class="radio-label">
                                        <input type="radio" name="header_layout" value="standard"
                                               <?php echo ($landingHeader['header_layout'] ?? 'standard') === 'standard' ? 'checked' : ''; ?>>
                                        Standard (Großzügig)
                                    </label>
                                    <label class="radio-label">
                                        <input type="radio" name="header_layout" value="compact"
                                               <?php echo ($landingHeader['header_layout'] ?? 'standard') === 'compact' ? 'checked' : ''; ?>>
                                        Kompakt (Reduzierte Höhe)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lp-card">
                    <h4>✍️ Headline &amp; Text</h4>
                    <div class="lp-form-row">
                        <div class="form-group">
                            <label for="title" class="form-label">Titel <span style="color:#ef4444;">*</span></label>
                            <input type="text" id="title" name="title" class="form-control"
                                   value="<?php echo htmlspecialchars($landingHeader['title']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="subtitle" class="form-label">Untertitel</label>
                            <input type="text" id="subtitle" name="subtitle" class="form-control"
                                   value="<?php echo htmlspecialchars($landingHeader['subtitle']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Beschreibung</label>
                        <?php echo EditorService::getInstance()->render('description', $landingHeader['description'], ['height' => 200]); ?>
                    </div>
                    <div style="display:grid;grid-template-columns:220px 1fr;gap:1.25rem;margin-top:.5rem;">
                        <div class="form-group">
                            <label for="version" class="form-label">Version / Badge</label>
                            <input type="text" id="version" name="version" class="form-control"
                                   value="<?php echo htmlspecialchars($landingHeader['version']); ?>"
                                   placeholder="2.0.0">
                        </div>
                    </div>
                </div>

                <div class="lp-card">
                    <h4>🔘 Action Buttons (max. 4)</h4>
                    <p class="text-muted" style="font-size:.875rem;margin-bottom:1.25rem;">
                        Felder leer lassen = Button ausblenden.
                    </p>
                    <?php
                    $buttons = $landingHeader['header_buttons'] ?? [];
                    if (empty($buttons) && !empty($landingHeader['github_url'])) {
                        $buttons[] = ['text' => $landingHeader['github_text'] ?? 'GitHub', 'url' => $landingHeader['github_url'], 'icon' => '💻', 'target' => '_blank', 'outline' => true];
                    }
                    if (count($buttons) < 4 && !empty($landingHeader['gitlab_url'])) {
                        $buttons[] = ['text' => $landingHeader['gitlab_text'] ?? 'GitLab', 'url' => $landingHeader['gitlab_url'], 'icon' => '🦊', 'target' => '_blank', 'outline' => true];
                    }
                    ?>
                    <div class="lp-buttons-grid">
                        <?php for ($i = 0; $i < 4; $i++): $btn = $buttons[$i] ?? []; ?>
                        <div class="lp-btn-card">
                            <div class="lp-btn-card__num">Button <?php echo $i + 1; ?></div>
                            <div class="form-group" style="margin-bottom:.5rem;">
                                <label style="font-size:.82em;">Text</label>
                                <input type="text" name="header_buttons[<?php echo $i; ?>][text]" class="form-control"
                                       value="<?php echo htmlspecialchars($btn['text'] ?? ''); ?>" placeholder="z.B. Jetzt starten">
                            </div>
                            <div class="form-group" style="margin-bottom:.5rem;">
                                <label style="font-size:.82em;">URL</label>
                                <input type="url" name="header_buttons[<?php echo $i; ?>][url]" class="form-control"
                                       value="<?php echo htmlspecialchars($btn['url'] ?? ''); ?>" placeholder="https://...">
                            </div>
                            <div style="display:flex;gap:.5rem;margin-bottom:.5rem;">
                                <div class="form-group" style="flex:1;margin:0;">
                                    <label style="font-size:.82em;">Icon (Emoji)</label>
                                    <input type="text" name="header_buttons[<?php echo $i; ?>][icon]" class="form-control"
                                           value="<?php echo htmlspecialchars($btn['icon'] ?? ''); ?>" placeholder="🚀">
                                </div>
                                <div class="form-group" style="flex:1;margin:0;">
                                    <label style="font-size:.82em;">Ziel</label>
                                    <select name="header_buttons[<?php echo $i; ?>][target]" class="form-control">
                                        <option value="_self"  <?php echo ($btn['target'] ?? '') === '_self'  ? 'selected' : ''; ?>>Gleicher Tab</option>
                                        <option value="_blank" <?php echo ($btn['target'] ?? '') === '_blank' ? 'selected' : ''; ?>>Neuer Tab</option>
                                    </select>
                                </div>
                            </div>
                            <label class="checkbox-label" style="font-size:.82em;">
                                <input type="checkbox" name="header_buttons[<?php echo $i; ?>][outline]" value="1"
                                       <?php echo !empty($btn['outline']) ? 'checked' : ''; ?>>
                                Als Outline-Button (transparent)
                            </label>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="lp-card form-actions-card">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Header speichern</button>
                        <span class="form-actions__hint">Alle Abschnitte werden gemeinsam gespeichert</span>
                    </div>
                </div>
            </form>

        <?php endif; ?>

        <?php /* ═══════════ SECTION: CONTENT ═════════════════════════════ */ ?>
        <?php if ($activeSection === 'content'): ?>

            <div class="lp-card" style="margin-bottom:1.25rem;">
                <h4>📐 Darstellungsart</h4>
                <p class="text-muted" style="font-size:.875rem;margin-bottom:1rem;">
                    Widget-/Feature-Grid oder freier Text.
                </p>
                <form method="POST" id="contentTypeForm">
                    <input type="hidden" name="landing_action" value="update_content_mode">
                    <input type="hidden" name="csrf_token"     value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="content_text"  value="<?php echo htmlspecialchars($contentText); ?>">

                    <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.25rem;">
                        <label class="lp-mode-btn <?php echo $contentMode === 'features' ? 'lp-mode-btn--active' : ''; ?>">
                            <input type="radio" name="content_type" value="features"
                                   <?php echo $contentMode === 'features' ? 'checked' : ''; ?>
                                   onchange="lpModeChanged(this)">
                            <span>&#128230; Widget / Feature Grid</span>
                        </label>
                        <label class="lp-mode-btn <?php echo $contentMode === 'text' ? 'lp-mode-btn--active' : ''; ?>">
                            <input type="radio" name="content_type" value="text"
                                   <?php echo $contentMode === 'text' ? 'checked' : ''; ?>
                                   onchange="lpModeChanged(this)">
                            <span>&#128221; Freier Text / HTML</span>
                        </label>
                        <label class="lp-mode-btn <?php echo $contentMode === 'posts' ? 'lp-mode-btn--active' : ''; ?>">
                            <input type="radio" name="content_type" value="posts"
                                   <?php echo $contentMode === 'posts' ? 'checked' : ''; ?>
                                   onchange="lpModeChanged(this)">
                            <span>&#128240; Letzte Beitr&auml;ge</span>
                        </label>
                    </div>

                    <div id="postsCountBlock" style="display:<?php echo $contentMode === 'posts' ? 'flex' : 'none'; ?>;align-items:center;gap:1rem;margin-bottom:1rem;">
                        <label class="form-label" style="margin:0;white-space:nowrap;">Anzahl Beitr&auml;ge:</label>
                        <input type="number" name="posts_count" id="postsCountInput"
                               class="form-control" min="1" max="50" style="width:90px;"
                               value="<?php echo $postsCount; ?>">
                        <small class="text-muted">Zeigt die neuesten Beitr&auml;ge aus dem CMS (1&ndash;50).</small>
                    </div>

                    <button type="submit" class="btn btn-primary">&#128190; Darstellungsart speichern</button>
                </form>
            </div>

            <?php if ($contentMode === 'features'): ?>

                <div class="lp-card">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
                        <h4 style="margin:0;border:none;padding:0;">📦 Feature / Widget Grid</h4>
                        <button onclick="showFeatureModal()" class="btn btn-primary">➕ Feature hinzufügen</button>
                    </div>
                    <?php if (empty($landingFeatures)): ?>
                        <div style="text-align:center;padding:2rem;color:#94a3b8;">
                            <div style="font-size:2.5rem;margin-bottom:.5rem;">📭</div>
                            <p>Noch keine Features vorhanden.</p>
                        </div>
                    <?php else: ?>
                        <div class="lp-feature-grid">
                            <?php foreach ($landingFeatures as $feature): ?>
                                <div class="lp-feature-card">
                                    <div class="lp-feature-card__icon"><?php echo $feature['icon']; ?></div>
                                    <p class="lp-feature-card__title"><?php echo htmlspecialchars($feature['title']); ?></p>
                                    <p class="lp-feature-card__desc"><?php echo htmlspecialchars(strip_tags($feature['description'])); ?></p>
                                    <div class="lp-feature-card__actions">
                                        <button onclick='editFeature(<?php echo json_encode($feature); ?>)' class="btn btn-secondary btn-sm">✏️ Bearbeiten</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Feature wirklich löschen?');">
                                            <input type="hidden" name="landing_action" value="delete_feature">
                                            <input type="hidden" name="csrf_token"     value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="feature_id"     value="<?php echo $feature['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($contentMode === 'text'): ?>

                <div class="lp-card">
                    <h4>&#128221; Content-Text</h4>
                    <p class="text-muted" style="font-size:.875rem;margin-bottom:1.25rem;">
                        Wird anstelle des Feature-Grids angezeigt. HTML ist erlaubt.
                    </p>
                    <form method="POST">
                        <input type="hidden" name="landing_action" value="update_content_text">
                        <input type="hidden" name="csrf_token"     value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="content_type"   value="text">
                        <div class="form-group">
                            <?php echo EditorService::getInstance()->render('content_text', $contentText, ['height' => 350]); ?>
                        </div>
                        <div class="form-actions" style="margin-top:1.25rem;">
                            <button type="submit" class="btn btn-primary">&#128190; Text speichern</button>
                        </div>
                    </form>
                </div>

            <?php elseif ($contentMode === 'posts'): ?>

                <div class="lp-card">
                    <h4>&#128240; Letzte Beitr&auml;ge</h4>
                    <p class="text-muted" style="font-size:.875rem;margin-bottom:.75rem;">
                        Es werden die neuesten <strong><?php echo $postsCount; ?> Beitr&auml;ge</strong> angezeigt.
                        Anzahl oben in der Darstellungsart anpassen.
                    </p>
                    <div style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;padding:1.25rem;color:#64748b;font-size:.875rem;">
                        <strong>&#8505;&#65039;</strong> Die visuelle Darstellung (Karten, Liste, Grid) wird &uuml;ber das Theme oder ein Plugin-Override gesteuert.
                        Layout-Einstellungen findest du im Bereich <a href="/admin/landing-page?section=design" style="color:#3b82f6;">&#127912; Design</a>.
                    </div>
                </div>

            <?php endif; ?>

        <?php endif; ?>

        <?php /* ═══════════ SECTION: FOOTER ════════════════════════════ */ ?>
        <?php if ($activeSection === 'footer'): ?>

            <form method="POST">
                <input type="hidden" name="landing_action" value="update_footer">
                <input type="hidden" name="csrf_token"     value="<?php echo $csrfToken; ?>">

                <!-- Row 1: Visibility + Copyright nebeneinander -->
                <div class="lp-card-row">
                    <div class="lp-card">
                        <h4>👁️ Sichtbarkeit</h4>
                        <div style="padding:1.25rem 1.5rem;">
                            <label class="checkbox-label">
                                <input type="checkbox" name="show_footer" value="1"
                                       <?php echo ($landingFooter['show_footer'] ?? true) ? 'checked' : ''; ?>>
                                <strong>Footer-Bereich anzeigen</strong>
                            </label>
                            <p class="text-muted" style="font-size:.8rem;margin:.6rem 0 0 1.5rem;">Wenn deaktiviert, wird der gesamte Footer-Bereich der Landing Page ausgeblendet.</p>
                        </div>
                    </div>

                    <div class="lp-card">
                        <h4>©️ Copyright</h4>
                        <div style="padding:1.25rem 1.5rem;">
                            <div class="form-group" style="margin:0;">
                                <label class="form-label" for="footer_copyright">Copyright-Zeile (ganz unten)</label>
                                <input type="text" name="footer_copyright" id="footer_copyright" class="form-control"
                                       value="<?php echo htmlspecialchars($landingFooter['copyright'] ?? ''); ?>"
                                       placeholder="© 2026 Mein Projekt. Alle Rechte vorbehalten.">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Content + CTA-Button nebeneinander -->
                <div class="lp-card-row">
                    <div class="lp-card">
                        <h4>📄 Inhalt</h4>
                        <div style="padding:1.25rem 1.5rem;">
                            <div class="form-group" style="margin:0;">
                                <label class="form-label">Text unterhalb der Content-Sektion</label>
                                <textarea name="footer_content" id="footer_content" class="form-control" rows="5"><?php echo htmlspecialchars($landingFooter['content'] ?? ''); ?></textarea>
                                <small class="text-muted">HTML ist erlaubt.</small>
                            </div>
                        </div>
                    </div>

                    <div class="lp-card">
                        <h4>🔘 Call-to-Action Button</h4>
                        <div style="padding:1.25rem 1.5rem;">
                            <p class="text-muted" style="font-size:.82rem;margin:0 0 1rem;">Felder leer lassen = Button ausblenden.</p>
                            <div class="form-group">
                                <label class="form-label" for="footer_button_text">Button Text</label>
                                <input type="text" name="footer_button_text" id="footer_button_text" class="form-control"
                                       value="<?php echo htmlspecialchars($landingFooter['button_text'] ?? ''); ?>"
                                       placeholder="z.B. Jetzt registrieren">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" for="footer_button_url">Button URL</label>
                                <input type="text" name="footer_button_url" id="footer_button_url" class="form-control"
                                       value="<?php echo htmlspecialchars($landingFooter['button_url'] ?? ''); ?>"
                                       placeholder="/register oder https://...">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lp-card form-actions-card">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Footer speichern</button>
                    </div>
                </div>
            </form>

        <?php endif; ?>

        <?php /* ═══════════ SECTION: DESIGN ════════════════════════════ */ ?>
        <?php if ($activeSection === 'design'): ?>

            <form method="POST" class="admin-form">
                <input type="hidden" name="landing_action" value="update_colors">
                <input type="hidden" name="csrf_token"     value="<?php echo $csrfToken; ?>">

                <div class="lp-card">
                    <h4>🎨 Farbschema</h4>
                    <p class="text-muted" style="font-size:.875rem;margin-bottom:1.5rem;">Farben an Ihr Corporate Design anpassen.</p>

                    <div class="lp-color-grid">
                        <div class="lp-color-section">
                            <h4>🌟 Hero Section</h4>
                            <?php foreach (['hero_gradient_start' => 'Gradient Start', 'hero_gradient_end' => 'Gradient Ende', 'hero_border' => 'Border-Farbe', 'hero_text' => 'Text-Farbe'] as $_n => $_l): ?>
                            <div class="lp-color-row">
                                <label><?php echo $_l; ?></label>
                                <input type="color" id="<?php echo $_n; ?>" name="<?php echo $_n; ?>"
                                       value="<?php echo htmlspecialchars($landingColors[$_n]); ?>" class="color-picker">
                                <input type="text" value="<?php echo htmlspecialchars($landingColors[$_n]); ?>"
                                       class="form-control color-text" style="width:90px;font-family:monospace;font-size:.8rem;" readonly>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="lp-color-section">
                            <h4>✨ Feature Section</h4>
                            <?php foreach (['features_bg' => 'Hintergrund', 'feature_card_bg' => 'Card Hintergrund', 'feature_card_hover' => 'Card Hover Border'] as $_n => $_l): ?>
                            <div class="lp-color-row">
                                <label><?php echo $_l; ?></label>
                                <input type="color" id="<?php echo $_n; ?>" name="<?php echo $_n; ?>"
                                       value="<?php echo htmlspecialchars($landingColors[$_n]); ?>" class="color-picker">
                                <input type="text" value="<?php echo htmlspecialchars($landingColors[$_n]); ?>"
                                       class="form-control color-text" style="width:90px;font-family:monospace;font-size:.8rem;" readonly>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="lp-color-section">
                            <h4>🔘 Buttons</h4>
                            <div class="lp-color-row">
                                <label>Primary Button</label>
                                <input type="color" id="primary_button" name="primary_button"
                                       value="<?php echo htmlspecialchars($landingColors['primary_button']); ?>" class="color-picker">
                                <input type="text" value="<?php echo htmlspecialchars($landingColors['primary_button']); ?>"
                                       class="form-control color-text" style="width:90px;font-family:monospace;font-size:.8rem;" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions" style="padding:1rem 1.5rem;border-top:1px solid #f1f5f9;margin-top:1rem;border-radius:0 0 9px 9px;background:#f8fafc;">
                        <button type="submit" class="btn btn-primary">💾 Farben speichern</button>
                        <button type="button" onclick="resetColors()" class="btn btn-secondary">🔄 Zurücksetzen</button>
                    </div>
                </div>
            </form>

            <!-- ──────── Form 2: Design-Tokens ──────── -->
            <form method="POST" class="admin-form">
                <input type="hidden" name="landing_action" value="update_design">
                <input type="hidden" name="csrf_token"     value="<?php echo $csrfToken; ?>">
                <?php
                $d_card_br    = (int)($landingDesign['card_border_radius']    ?? 12);
                $d_btn_br     = (int)($landingDesign['button_border_radius']  ?? 8);
                $d_icon       = $landingDesign['card_icon_layout']  ?? 'top';
                $d_border_c   = $landingDesign['card_border_color'] ?? '#e2e8f0';
                $d_border_w   = $landingDesign['card_border_width'] ?? '1px';
                $d_shadow     = $landingDesign['card_shadow']       ?? 'sm';
                $d_columns    = $landingDesign['feature_columns']   ?? 'auto';
                $d_hero_pad   = $landingDesign['hero_padding']      ?? 'md';
                $d_feat_pad   = $landingDesign['feature_padding']   ?? 'md';
                $d_footer_bg  = $landingDesign['footer_bg']         ?? '#1e293b';
                $d_footer_tc  = $landingDesign['footer_text_color'] ?? '#94a3b8';
                $d_content_bg = $landingDesign['content_section_bg']?? '#ffffff';
                ?>

                <!-- Feature Cards -->
                <div class="lp-card">
                    <h4>&#128230; Feature Cards</h4>
                    <div class="lp-design-grid">
                        <!-- Icon-Position -->
                        <div class="form-group">
                            <label class="form-label" style="font-weight:600;">Icon-Position in der Card</label>
                            <div class="lp-icon-layout-grid" style="margin-top:.4rem;">
                                <label class="lp-icon-layout-btn <?php echo $d_icon === 'top' ? 'lp-icon-layout-btn--active' : ''; ?>">
                                    <input type="radio" name="card_icon_layout" value="top" <?php echo $d_icon === 'top' ? 'checked' : ''; ?> onchange="this.closest('form').querySelectorAll('.lp-icon-layout-btn').forEach(el=>el.classList.remove('lp-icon-layout-btn--active'));this.closest('.lp-icon-layout-btn').classList.add('lp-icon-layout-btn--active')">
                                    <div class="preview-top">
                                        <span class="lp-icon-preview-icon">&#128640;</span>
                                        <div class="lp-icon-preview-lines"><span style="width:52px;"></span><span style="width:38px;"></span></div>
                                    </div>
                                    <small>Icon oben</small>
                                </label>
                                <label class="lp-icon-layout-btn <?php echo $d_icon === 'left' ? 'lp-icon-layout-btn--active' : ''; ?>">
                                    <input type="radio" name="card_icon_layout" value="left" <?php echo $d_icon === 'left' ? 'checked' : ''; ?> onchange="this.closest('form').querySelectorAll('.lp-icon-layout-btn').forEach(el=>el.classList.remove('lp-icon-layout-btn--active'));this.closest('.lp-icon-layout-btn').classList.add('lp-icon-layout-btn--active')">
                                    <div class="preview-left">
                                        <span class="lp-icon-preview-icon">&#128640;</span>
                                        <div class="lp-icon-preview-lines"><span style="width:52px;"></span><span style="width:38px;"></span></div>
                                    </div>
                                    <small>Icon links</small>
                                </label>
                            </div>
                        </div>

                        <!-- Spalten -->
                        <div class="form-group">
                            <label class="form-label" style="font-weight:600;">Spaltenanzahl</label>
                            <select name="feature_columns" class="form-control">
                                <?php foreach (['auto' => 'Automatisch (responsive)', '2' => '2 Spalten', '3' => '3 Spalten', '4' => '4 Spalten'] as $_cv => $_cl): ?>
                                <option value="<?php echo $_cv; ?>" <?php echo $d_columns === $_cv ? 'selected' : ''; ?>><?php echo $_cl; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Eckenradius -->
                        <div class="form-group">
                            <label class="form-label" style="font-weight:600;">Eckenradius</label>
                            <div class="lp-range-row">
                                <input type="range" name="card_border_radius" min="0" max="32" step="1"
                                       value="<?php echo $d_card_br; ?>"
                                       oninput="document.getElementById('cardBrVal').textContent=this.value+'px';_updateDesignPreview()">
                                <span class="lp-range-val" id="cardBrVal"><?php echo $d_card_br; ?>px</span>
                            </div>
                        </div>

                        <!-- Schatten -->
                        <div class="form-group">
                            <label class="form-label" style="font-weight:600;">Schatten</label>
                            <select name="card_shadow" class="form-control">
                                <?php foreach (['none' => 'Kein Schatten', 'sm' => 'Leicht', 'md' => 'Mittel', 'lg' => 'Stark'] as $_sv => $_sl): ?>
                                <option value="<?php echo $_sv; ?>" <?php echo $d_shadow === $_sv ? 'selected' : ''; ?>><?php echo $_sl; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Rahmenbreite -->
                        <div class="form-group">
                            <label class="form-label" style="font-weight:600;">Rahmenbreite</label>
                            <select name="card_border_width" class="form-control" onchange="_updateDesignPreview()">
                                <?php foreach (['0' => 'Kein Rahmen', '1px' => '1px', '2px' => '2px', '3px' => '3px'] as $_bv => $_bl): ?>
                                <option value="<?php echo $_bv; ?>" <?php echo $d_border_w === $_bv ? 'selected' : ''; ?>><?php echo $_bl; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Standard-Rahmenfarbe -->
                        <div class="form-group">
                            <label class="form-label" style="font-weight:600;">Standard-Rahmenfarbe</label>
                            <div class="lp-color-row">
                                <input type="color" name="card_border_color" id="cardBorderColor"
                                       value="<?php echo htmlspecialchars($d_border_c); ?>"
                                       class="color-picker" oninput="this.nextElementSibling.value=this.value;_updateDesignPreview()">
                                <input type="text" value="<?php echo htmlspecialchars($d_border_c); ?>"
                                       class="form-control color-text" style="width:90px;font-family:monospace;font-size:.8rem;" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="lp-card">
                    <h4>&#128280; Buttons</h4>
                    <div class="lp-design-grid">
                        <div class="form-group">
                            <label class="form-label" style="font-weight:600;">Eckenradius</label>
                            <div class="lp-range-row">
                                <input type="range" name="button_border_radius" min="0" max="50" step="1"
                                       value="<?php echo $d_btn_br; ?>"
                                       oninput="document.getElementById('btnBrVal').textContent=this.value+'px';_updateDesignPreview()">
                                <span class="lp-range-val" id="btnBrVal"><?php echo $d_btn_br; ?>px</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Abstaende & Hintergrundfarben -->
                <div class="lp-card">
                    <h4>&#128208; Abst&auml;nde &amp; Hintergrundfarben</h4>
                    <div class="lp-design-grid">
                        <div class="form-group">
                            <label class="form-label" style="font-weight:600;">Hero-Bereich Innenabstand</label>
                            <select name="hero_padding" class="form-control">
                                <?php foreach (['sm' => 'Klein (2rem)', 'md' => 'Mittel (4rem)', 'lg' => 'Gro&szlig; (6rem)', 'xl' => 'Extra Gro&szlig; (8rem)'] as $_pv => $_pl): ?>
                                <option value="<?php echo $_pv; ?>" <?php echo $d_hero_pad === $_pv ? 'selected' : ''; ?>><?php echo $_pl; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-weight:600;">Feature-Bereich Innenabstand</label>
                            <select name="feature_padding" class="form-control">
                                <?php foreach (['sm' => 'Klein (2rem)', 'md' => 'Mittel (4rem)', 'lg' => 'Gro&szlig; (6rem)', 'xl' => 'Extra Gro&szlig; (8rem)'] as $_pv => $_pl): ?>
                                <option value="<?php echo $_pv; ?>" <?php echo $d_feat_pad === $_pv ? 'selected' : ''; ?>><?php echo $_pl; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-weight:600;">Content-Bereich Hintergrund</label>
                            <div class="lp-color-row">
                                <input type="color" name="content_section_bg" value="<?php echo htmlspecialchars($d_content_bg); ?>"
                                       class="color-picker" oninput="this.nextElementSibling.value=this.value">
                                <input type="text" value="<?php echo htmlspecialchars($d_content_bg); ?>"
                                       class="form-control color-text" style="width:90px;font-family:monospace;font-size:.8rem;" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-weight:600;">Footer Hintergrundfarbe</label>
                            <div class="lp-color-row">
                                <input type="color" name="footer_bg" value="<?php echo htmlspecialchars($d_footer_bg); ?>"
                                       class="color-picker" oninput="this.nextElementSibling.value=this.value">
                                <input type="text" value="<?php echo htmlspecialchars($d_footer_bg); ?>"
                                       class="form-control color-text" style="width:90px;font-family:monospace;font-size:.8rem;" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-weight:600;">Footer Textfarbe</label>
                            <div class="lp-color-row">
                                <input type="color" name="footer_text_color" value="<?php echo htmlspecialchars($d_footer_tc); ?>"
                                       class="color-picker" oninput="this.nextElementSibling.value=this.value">
                                <input type="text" value="<?php echo htmlspecialchars($d_footer_tc); ?>"
                                       class="form-control color-text" style="width:90px;font-family:monospace;font-size:.8rem;" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lp-card form-actions-card">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">&#128190; Design speichern</button>
                    </div>
                </div>
            </form>

            <!-- Live Preview (updated) -->
            <div class="lp-card">
                <h4>&#128065;&#65039; Live-Vorschau</h4>
                <div class="lp-preview">
                    <div class="lp-preview__hero" id="previewHero" style="background:linear-gradient(135deg,<?php echo $landingColors['hero_gradient_start']; ?> 0%,<?php echo $landingColors['hero_gradient_end']; ?> 100%);border-bottom:4px solid <?php echo $landingColors['hero_border']; ?>;color:<?php echo $landingColors['hero_text']; ?>;">
                        <h2 style="margin:0 0 .5rem 0;">Hero Section</h2>
                        <p style="margin:0 0 1.25rem 0;opacity:.9;">Vorschau der Farbeinstellungen</p>
                        <div id="previewBtn" style="display:inline-block;padding:.6rem 1.4rem;background:<?php echo $landingColors['primary_button']; ?>;border-radius:<?php echo $d_btn_br; ?>px;color:#fff;font-weight:600;">
                            Primary Button
                        </div>
                    </div>
                    <div class="lp-preview__features" id="previewFeatures" style="background:<?php echo $landingColors['features_bg']; ?>;">
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;">
                            <div id="previewCard" style="background:<?php echo $landingColors['feature_card_bg']; ?>;padding:1.25rem;border-radius:<?php echo $d_card_br; ?>px;border:<?php echo $d_border_w; ?> solid <?php echo $d_border_c; ?>;">
                                <div style="font-size:1.75rem;margin-bottom:.4rem;">&#128640;</div>
                                <strong>Feature Card</strong>
                                <p style="font-size:.8rem;color:#64748b;margin:.25rem 0 0 0;">Radius &amp; Rahmen</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>

        <?php /* ═══════════ SECTION: SETTINGS ═══════════════════════════ */ ?>
        <?php if ($activeSection === 'settings'): ?>

            <form method="POST">
                <input type="hidden" name="landing_action" value="update_settings">
                <input type="hidden" name="csrf_token"     value="<?php echo $csrfToken; ?>">

                <div class="lp-card-row">
                    <div class="lp-card">
                        <h4>🔦 Sichtbarkeit der Bereiche</h4>
                        <div style="padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:.5rem;">
                            <p class="text-muted" style="font-size:.82rem;margin:0 0 .5rem;">Welche Bereiche sollen auf der Landing Page angezeigt werden?</p>
                            <label class="checkbox-label">
                                <input type="checkbox" name="show_header" value="1"
                                       <?php echo ($landingSettings['show_header'] ?? true) ? 'checked' : ''; ?>>
                                <span><strong>Header</strong> – Hero-Bereich mit Titel und Buttons</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="show_content" value="1"
                                       <?php echo ($landingSettings['show_content'] ?? true) ? 'checked' : ''; ?>>
                                <span><strong>Content</strong> – Feature Grid, freier Text oder letzte Beitr&auml;ge</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="show_footer_section" value="1"
                                       <?php echo ($landingSettings['show_footer_section'] ?? true) ? 'checked' : ''; ?>>
                                <span><strong>Footer</strong> – CTA-Bereich und Copyright</span>
                            </label>
                        </div>
                    </div>

                    <div class="lp-card">
                        <h4>&#128279; URL &amp; Erreichbarkeit</h4>
                        <div style="padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:1rem;">
                            <div class="form-group" style="margin:0;">
                                <label class="form-label" for="landing_slug">URL-Slug der Landing Page</label>
                                <input type="text" id="landing_slug" name="landing_slug" class="form-control"
                                       value="<?php echo htmlspecialchars($landingSettings['landing_slug'] ?? ''); ?>"
                                       placeholder="/ (Root) oder /start">
                                <small class="text-muted">Leer oder / = Startseite des CMS.</small>
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label class="form-label">Wartungsmodus</label>
                                <label class="checkbox-label" style="margin-top:.3rem;">
                                    <input type="checkbox" name="maintenance_mode" value="1"
                                           <?php echo !empty($landingSettings['maintenance_mode']) ? 'checked' : ''; ?>>
                                    Landing Page in Wartungsmodus setzen
                                </label>
                                <small class="text-muted">Nicht-eingeloggte Besucher sehen eine Wartungsseite.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lp-card form-actions-card">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
                    </div>
                </div>
            </form>

        <?php endif; ?>

        <?php /* ═══════════ SECTION: PLUGINS ═══════════════════════════════ */ ?>
        <?php if ($activeSection === 'plugins'): ?>

            <div class="lp-card">
                <h4>🔌 Plugin-Overrides</h4>
                <p style="color:#64748b;font-size:.9rem;margin:0 0 .4rem 0;">
                    Plugins können die Standard-Bereiche <strong>Header</strong>, <strong>Content</strong> und <strong>Footer</strong>
                    der Landing Page ersetzen. Sobald ein Plugin-Override für einen Bereich aktiviert ist,
                    wird das Standard-CMS-Element deaktiviert und die Ausgabe vollständig durch das Plugin übernommen.
                </p>
                <p style="color:#94a3b8;font-size:.82rem;margin:0 0 1.75rem 0;">
                    Plugins registrieren sich über den Filter-Hook
                    <code style="background:#f1f5f9;padding:1px 6px;border-radius:3px;">\CMS\Hooks::addFilter('landing_page_plugins', ...)</code>.
                    Weitere Informationen in der
                    <a href="/admin/docs?doc=admin/plugins/LANDING-PAGE-PLUGINS" style="color:#3b82f6;">📖 Plugin-Dokumentation</a>.
                </p>

                <?php foreach (['header' => '🔝 Header', 'content' => '📋 Content', 'footer' => '🔚 Footer'] as $area => $areaLabel): ?>
                    <?php
                    $areaPlugins    = array_filter($registeredPlugins, fn($p) => in_array($area, (array)($p['targets'] ?? []), true));
                    $activePluginId = $pluginOverrides[$area] ?? null;
                    $activePlugin   = ($activePluginId && isset($registeredPlugins[$activePluginId]))
                                     ? $registeredPlugins[$activePluginId] : null;
                    ?>
                    <div class="lp-plugin-area">
                        <div class="lp-plugin-area__head">
                            <span class="lp-plugin-area__title"><?php echo $areaLabel; ?> Bereich</span>
                            <?php if ($activePlugin): ?>
                                <span class="lp-plugin-badge lp-plugin-badge--active">✅ Override aktiv: <?php echo htmlspecialchars($activePlugin['name'] ?? $activePluginId); ?></span>
                            <?php else: ?>
                                <span class="lp-plugin-badge lp-plugin-badge--default">CMS-Standard aktiv</span>
                            <?php endif; ?>
                        </div>

                        <?php if (empty($areaPlugins)): ?>
                            <div class="lp-plugin-empty">
                                <span>📫</span>
                                <p>Kein Plugin für diesen Bereich registriert.</p>
                                <small>Plugins registrieren sich per <code>landing_page_plugins</code> Filter-Hook
                                    mit <code>'targets' =&gt; ['<?php echo $area; ?>']</code>.</small>
                            </div>

                        <?php else: ?>
                            <div class="lp-plugin-list">

                                <?php if ($activePlugin): ?>
                                <form method="POST" style="margin-bottom:.75rem;">
                                    <input type="hidden" name="landing_action" value="update_plugin_override">
                                    <input type="hidden" name="csrf_token"     value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="area"           value="<?php echo htmlspecialchars($area); ?>">
                                    <input type="hidden" name="plugin_id"      value="">
                                    <button type="submit" class="btn btn-secondary" style="font-size:.85rem;">
                                        ↩️ Auf CMS-Standard zurücksetzen
                                    </button>
                                </form>
                                <?php endif; ?>

                                <?php foreach ($areaPlugins as $pluginId => $plugin): ?>
                                    <?php $isActive = ($activePluginId === $pluginId); ?>
                                    <div class="lp-plugin-card<?php echo $isActive ? ' lp-plugin-card--active' : ''; ?>">
                                        <div class="lp-plugin-card__header">
                                            <div>
                                                <strong><?php echo htmlspecialchars($plugin['name'] ?? $pluginId); ?></strong>
                                                <?php if (!empty($plugin['version'])): ?>
                                                    <span style="font-size:.75rem;color:#94a3b8;margin-left:.4rem;">v<?php echo htmlspecialchars($plugin['version']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($plugin['author'])): ?>
                                                    <span style="font-size:.75rem;color:#94a3b8;margin-left:.5rem;">– <?php echo htmlspecialchars($plugin['author']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!$isActive): ?>
                                            <form method="POST">
                                                <input type="hidden" name="landing_action" value="update_plugin_override">
                                                <input type="hidden" name="csrf_token"     value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="area"           value="<?php echo htmlspecialchars($area); ?>">
                                                <input type="hidden" name="plugin_id"      value="<?php echo htmlspecialchars($pluginId); ?>">
                                                <button type="submit" class="btn btn-primary" style="font-size:.83rem;padding:.35rem .8rem;">
                                                    ▶️ Aktivieren
                                                </button>
                                            </form>
                                            <?php else: ?>
                                                <span style="font-size:.82rem;color:#16a34a;font-weight:600;">✅ Aktiv</span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!empty($plugin['description'])): ?>
                                            <p class="lp-plugin-card__desc"><?php echo htmlspecialchars($plugin['description']); ?></p>
                                        <?php endif; ?>

                                        <?php if ($isActive && !empty($plugin['settings_callback']) && is_callable($plugin['settings_callback'])): ?>
                                        <div class="lp-plugin-card__settings">
                                            <h6 style="margin:0 0 .75rem 0;font-size:.875rem;color:#475569;border-bottom:1px solid #e2e8f0;padding-bottom:.4rem;">
                                                ⚙️ Plugin-Einstellungen
                                            </h6>
                                            <form method="POST">
                                                <input type="hidden" name="landing_action" value="save_plugin_settings">
                                                <input type="hidden" name="csrf_token"     value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="plugin_id"      value="<?php echo htmlspecialchars($pluginId); ?>">
                                                <?php call_user_func($plugin['settings_callback'], $landingService->getPluginSettings($pluginId)); ?>
                                                <div class="form-actions" style="margin-top:1rem;">
                                                    <button type="submit" class="btn btn-primary" style="font-size:.85rem;">&#128190; Plugin-Einstellungen speichern</button>
                                                </div>
                                            </form>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                            </div><!-- /.lp-plugin-list -->
                        <?php endif; ?>
                    </div><!-- /.lp-plugin-area -->

                <?php endforeach; ?>
            </div><!-- /.lp-card -->

        <?php endif; ?>

    </div><!-- /.admin-content -->

    <!-- Feature Modal -->
    <div id="featureModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Feature bearbeiten</h3>
                <button onclick="closeFeatureModal()" class="modal-close">×</button>
            </div>
            <form method="POST" id="featureForm">
                <input type="hidden" name="landing_action" value="save_feature">
                <input type="hidden" name="csrf_token"     value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="feature_id"     id="feature_id">
                <div class="form-group">
                    <label for="feature_icon" class="form-label">Icon (Emoji)</label>
                    <input type="text" id="feature_icon" name="icon" class="form-control" placeholder="🎯" required>
                </div>
                <div class="form-group">
                    <label for="feature_title" class="form-label">Titel</label>
                    <input type="text" id="feature_title" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="feature_description" class="form-label">Beschreibung</label>
                    <textarea id="feature_description" name="description" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="feature_sort" class="form-label">Sortierung</label>
                    <input type="number" id="feature_sort" name="sort_order" class="form-control" min="1" max="99">
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeFeatureModal()" class="btn btn-secondary">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">💾 Speichern</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
    <script>
    let _featureEditor = null;

    function _initFeatureEditor() {
        if (typeof SUNEDITOR === 'undefined') return;
        if (!_featureEditor) {
            _featureEditor = SUNEDITOR.create('feature_description', {
                height: 100, width: '100%',
                buttonList: [['bold', 'italic', 'link', 'removeFormat']],
                defaultStyle: 'font-family: inherit; font-size: 14px;'
            });
        }
    }

    function showFeatureModal() {
        document.getElementById('modalTitle').textContent = 'Neues Feature';
        document.getElementById('featureForm').reset();
        document.getElementById('feature_id').value = '';
        document.getElementById('featureModal').style.display = 'flex';
        setTimeout(() => { _initFeatureEditor(); if (_featureEditor) _featureEditor.setContents(''); }, 50);
    }

    function editFeature(feature) {
        document.getElementById('modalTitle').textContent    = 'Feature bearbeiten';
        document.getElementById('feature_id').value          = feature.id;
        document.getElementById('feature_icon').value        = feature.icon;
        document.getElementById('feature_title').value       = feature.title;
        document.getElementById('feature_sort').value        = feature.sort_order;
        document.getElementById('featureModal').style.display = 'flex';
        setTimeout(() => {
            _initFeatureEditor();
            if (_featureEditor) { _featureEditor.setContents(feature.description); }
            else { document.getElementById('feature_description').value = feature.description; }
        }, 50);
    }

    function closeFeatureModal() {
        document.getElementById('featureModal').style.display = 'none';
    }

    window.addEventListener('click', function(e) {
        if (e.target === document.getElementById('featureModal')) closeFeatureModal();
    });

    document.querySelectorAll('.color-picker').forEach(function(picker) {
        picker.addEventListener('input', function() {
            const text = this.parentElement.querySelector('.color-text');
            if (text) text.value = this.value;
            _updateColorPreview();
        });
    });

    function _updateColorPreview() {
        const g = id => document.getElementById(id)?.value || '';
        const hero = document.getElementById('previewHero');
        if (hero) {
            hero.style.background   = 'linear-gradient(135deg,' + g('hero_gradient_start') + ' 0%,' + g('hero_gradient_end') + ' 100%)';
            hero.style.borderBottom = '4px solid ' + g('hero_border');
            hero.style.color        = g('hero_text');
        }
        const btn = document.getElementById('previewBtn');
        if (btn) btn.style.background = g('primary_button');
        const features = document.getElementById('previewFeatures');
        if (features) features.style.background = g('features_bg');
        const card = document.getElementById('previewCard');
        if (card) { card.style.background = g('feature_card_bg'); card.style.borderColor = g('feature_card_hover'); }
    }

    function resetColors() {
        if (!confirm('Alle Farben auf Standardwerte zuruecksetzen?')) return;
        const defaults = {
            hero_gradient_start: '#1e293b', hero_gradient_end: '#0f172a',
            hero_border: '#3b82f6',         hero_text: '#ffffff',
            features_bg: '#f8fafc',         feature_card_bg: '#ffffff',
            feature_card_hover: '#3b82f6',  primary_button: '#3b82f6'
        };
        Object.entries(defaults).forEach(([k, v]) => {
            const p = document.getElementById(k);
            const t = p?.parentElement?.querySelector('.color-text');
            if (p) p.value = v;
            if (t) t.value = v;
        });
        _updateColorPreview();
    }

    // ── Content Mode Toggle ─────────────────────────────────────────────────
    function lpModeChanged(radioEl) {
        document.querySelectorAll('.lp-mode-btn').forEach(function(lbl) {
            const inp = lbl.querySelector('input[type="radio"]');
            lbl.classList.toggle('lp-mode-btn--active', inp && inp.checked);
        });
        var postsBlock = document.getElementById('postsCountBlock');
        if (postsBlock) {
            postsBlock.style.display = radioEl.value === 'posts' ? 'flex' : 'none';
        }
    }

    // ── Design Preview Update ───────────────────────────────────────────────
    function _updateDesignPreview() {
        var cardBrRange = document.querySelector('input[name="card_border_radius"]');
        var btnBrRange  = document.querySelector('input[name="button_border_radius"]');
        var borderWSelect = document.querySelector('select[name="card_border_width"]');
        var borderColorEl = document.getElementById('cardBorderColor');

        var card = document.getElementById('previewCard');
        var btn  = document.getElementById('previewBtn');

        if (card && cardBrRange) {
            card.style.borderRadius = cardBrRange.value + 'px';
        }
        if (card && borderWSelect && borderColorEl) {
            var bw = borderWSelect.value === '0' ? '0' : borderWSelect.value;
            card.style.border = bw + ' solid ' + borderColorEl.value;
        }
        if (btn && btnBrRange) {
            btn.style.borderRadius = btnBrRange.value + 'px';
        }
    }
    </script>
</body>
</html>
