<?php
/**
 * Admin Landing Page Management
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

$auth = Auth::instance();
$landingService = LandingPageService::getInstance();

// Get current tab
$activeTab = $_GET['tab'] ?? 'landing';
if (!in_array($activeTab, ['landing', 'colors'])) {
    $activeTab = 'landing';
}

// Handle Landing Page form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['landing_action'])) {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'landing_page')) {
        $error = 'Sicherheitscheck fehlgeschlagen';
    } else {
        switch ($_POST['landing_action']) {
            case 'update_header':
                // Handle logo upload
                $logoPath = null;
                
                // Check removal first
                if (isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
                    $_POST['logo'] = '';
                }
                // Handle upload
                elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = dirname(__DIR__) . '/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileExt = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                    $allowedExts = ['jpg', 'jpeg', 'png', 'svg', 'gif'];
                    
                    if (in_array($fileExt, $allowedExts)) {
                        $fileName = 'logo-' . time() . '.' . $fileExt;
                        $targetPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                            $logoPath = 'uploads/' . $fileName;
                            $_POST['logo'] = $logoPath;
                        }
                    } else {
                        $error = 'Nur Bild-Dateien (JPG, PNG, SVG, GIF) sind erlaubt';
                    }
                } elseif (isset($_POST['existing_logo']) && !empty($_POST['existing_logo'])) {
                    // Keep existing logo if no new one uploaded
                    $_POST['logo'] = $_POST['existing_logo'];
                }
                
                if (!isset($error)) {
                    // Ensure logo_position is passed
                    if (!isset($_POST['logo_position'])) {
                        $_POST['logo_position'] = 'top';
                    }
                    
                    $landingService->updateHeader($_POST);
                    $success = 'Header und Logo Optionen aktualisiert';
                }
                break;

            case 'update_footer':
                if ($landingService->updateFooter($_POST)) {
                    $success = 'Footer Bereich erfolgreich aktualisiert';
                } else {
                    $error = 'Fehler beim Speichern des Footer Bereichs';
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
        }
    }
}

// Get Landing Page data
$landingHeader = $landingService->getHeader();
$landingFeatures = $landingService->getFeatures();
$landingFooter = $landingService->getFooter();
$landingColors = $landingService->getColors();

$csrfToken = Security::instance()->generateToken('landing_page');

// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">
    
    <?php renderAdminSidebar('landing-page'); ?>
    
    <!-- Main Content -->
    <div class="admin-content">
        
        <!-- Page Header -->
        <div class="admin-page-header">
            <h2>Landing Page verwalten</h2>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <a href="?tab=landing" class="tab-link <?php echo $activeTab === 'landing' ? 'active' : ''; ?>">
                🏠 Übersicht / Landing Page
            </a>
            <a href="?tab=colors" class="tab-link <?php echo $activeTab === 'colors' ? 'active' : ''; ?>">
                🎨 Farben
            </a>
        </div>
        
        <!-- Tab Content: Landing Page -->
        <?php if ($activeTab === 'landing'): ?>
            <div class="tab-content">
                
                <!-- Landing Page Header Section -->
                <div class="admin-section">
                    <h3>Header Bereich</h3>
                    <form method="POST" enctype="multipart/form-data" class="admin-form">
                        <input type="hidden" name="landing_action" value="update_header">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <!-- Keep existing colors when updating header -->
                        <input type="hidden" name="hero_gradient_start" value="<?php echo htmlspecialchars($landingColors['hero_gradient_start']); ?>">
                        <input type="hidden" name="hero_gradient_end" value="<?php echo htmlspecialchars($landingColors['hero_gradient_end']); ?>">
                        <input type="hidden" name="hero_border" value="<?php echo htmlspecialchars($landingColors['hero_border']); ?>">
                        <input type="hidden" name="hero_text" value="<?php echo htmlspecialchars($landingColors['hero_text']); ?>">
                        <input type="hidden" name="features_bg" value="<?php echo htmlspecialchars($landingColors['features_bg']); ?>">
                        <input type="hidden" name="feature_card_bg" value="<?php echo htmlspecialchars($landingColors['feature_card_bg']); ?>">
                        <input type="hidden" name="feature_card_hover" value="<?php echo htmlspecialchars($landingColors['feature_card_hover']); ?>">
                        <input type="hidden" name="primary_button" value="<?php echo htmlspecialchars($landingColors['primary_button']); ?>">
                        
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="logo">🖼️ Projekt Logo & Positionierung</label>
                                <div style="display: flex; gap: 2rem; align-items: flex-start; background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    
                                    <!-- Upload Section -->
                                    <div style="flex: 1;">
                                        <?php if (!empty($landingHeader['logo'])): ?>
                                            <div style="margin-bottom: 0.5rem; display: flex; flex-direction: column; gap: 8px;">
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <img src="<?php echo SITE_URL . '/' . htmlspecialchars($landingHeader['logo']); ?>" 
                                                         alt="Current Logo" 
                                                         style="max-height: 60px; max-width: 100%; border: 1px solid #e2e8f0; border-radius: 4px; padding: 4px; background: white;">
                                                    
                                                    <span class="text-muted" style="font-size: 0.8em;">Aktuelles Logo</span>
                                                </div>
                                                
                                                <input type="hidden" name="existing_logo" value="<?php echo htmlspecialchars($landingHeader['logo']); ?>">
                                                
                                                <label class="checkbox-label" style="display: inline-flex; align-items: center; gap: 6px; color: #dc2626; font-size: 0.85em; cursor: pointer;">
                                                    <input type="checkbox" name="remove_logo" value="1">
                                                    <span>🗑️ Logo entfernen</span>
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <label for="logo" style="font-size: 0.9em; margin-bottom: 4px; display: block; margin-top: 10px;">Neues Logo hochladen:</label>
                                        <input type="file" 
                                               id="logo" 
                                               name="logo" 
                                               class="form-control" 
                                               accept="image/*"
                                               style="font-size: 0.9em;">
                                        <small class="text-muted" style="display: block; margin-top: 4px;">JPG, PNG, SVG oder GIF.</small>
                                    </div>

                                    <!-- Position Section -->
                                    <div style="flex: 1; border-left: 1px solid #e2e8f0; padding-left: 2rem;">
                                        <div style="margin-bottom: 20px;">
                                            <label style="margin-bottom: 10px; display: block; font-weight: 600;">Logo Position:</label>
                                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                                <label class="radio-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                                    <input type="radio" name="logo_position" value="top" <?php echo ($landingHeader['logo_position'] ?? 'top') === 'top' ? 'checked' : ''; ?>>
                                                    <span>⬆️ Oberhalb des Titels</span>
                                                </label>
                                                <label class="radio-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                                    <input type="radio" name="logo_position" value="left" <?php echo ($landingHeader['logo_position'] ?? 'top') === 'left' ? 'checked' : ''; ?>>
                                                    <span>⬅️ Links neben dem Titel</span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label style="margin-bottom: 10px; display: block; font-weight: 600;">Header Layout:</label>
                                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                                <label class="radio-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                                    <input type="radio" name="header_layout" value="standard" <?php echo ($landingHeader['header_layout'] ?? 'standard') === 'standard' ? 'checked' : ''; ?>>
                                                    <span>Standard (Großzügig)</span>
                                                </label>
                                                <label class="radio-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                                    <input type="radio" name="header_layout" value="compact" <?php echo ($landingHeader['header_layout'] ?? 'standard') === 'compact' ? 'checked' : ''; ?>>
                                                    <span>Kompakt (Reduzierte Höhe)</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="title">Titel</label>
                                <input type="text" 
                                       id="title" 
                                       name="title" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($landingHeader['title']); ?>" 
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="subtitle">Untertitel</label>
                                <input type="text" 
                                       id="subtitle" 
                                       name="subtitle" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($landingHeader['subtitle']); ?>">
                            </div>
                            
                            <div class="form-group full-width">
                                <label>Beschreibung</label>
                                <?php echo EditorService::getInstance()->render('description', $landingHeader['description'], ['height' => 220]); ?>
                            </div>
                            
                            <div class="admin-section" style="margin-top: 2rem; background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <h4 style="margin-top: 0; margin-bottom: 1rem;">Action Buttons (Max. 4)</h4>
                                <p class="text-muted" style="font-size: 0.9em; margin-bottom: 1.5rem;">Definieren Sie hier bis zu 4 Buttons, die unter dem Text angezeigt werden.</p>
                                
                                <div class="buttons-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                    <?php 
                                    $buttons = $landingHeader['header_buttons'] ?? [];
                                    // Migration fallback: If buttons empty but legacy fields exist, create them
                                    if (empty($buttons) && !empty($landingHeader['github_url'])) {
                                        $buttons[] = [
                                            'text' => $landingHeader['github_text'] ?? 'GitHub',
                                            'url' => $landingHeader['github_url'],
                                            'icon' => '💻',
                                            'target' => '_blank',
                                            'outline' => true
                                        ];
                                    }
                                    if (empty($buttons) && !empty($landingHeader['gitlab_url']) && count($buttons) < 4) {
                                        $buttons[] = [
                                            'text' => $landingHeader['gitlab_text'] ?? 'GitLab',
                                            'url' => $landingHeader['gitlab_url'],
                                            'icon' => '🦊',
                                            'target' => '_blank',
                                            'outline' => true
                                        ];
                                    }
                                    
                                    for ($i = 0; $i < 4; $i++): 
                                        $btn = $buttons[$i] ?? [];
                                    ?>
                                        <div class="button-config" style="background: white; padding: 1rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                                            <div style="font-weight: 600; margin-bottom: 0.5rem;">Button <?php echo $i + 1; ?></div>
                                            
                                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                                <label style="font-size: 0.85em;">Text</label>
                                                <input type="text" name="header_buttons[<?php echo $i; ?>][text]" class="form-control" value="<?php echo htmlspecialchars($btn['text'] ?? ''); ?>" placeholder="z.B. Jetzt starten">
                                            </div>
                                            
                                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                                <label style="font-size: 0.85em;">URL</label>
                                                <input type="url" name="header_buttons[<?php echo $i; ?>][url]" class="form-control" value="<?php echo htmlspecialchars($btn['url'] ?? ''); ?>" placeholder="https://...">
                                            </div>
                                            
                                            <div style="display: flex; gap: 0.5rem;">
                                                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                                    <label style="font-size: 0.85em;">Icon (Emoji/HTML)</label>
                                                    <input type="text" name="header_buttons[<?php echo $i; ?>][icon]" class="form-control" value="<?php echo htmlspecialchars($btn['icon'] ?? ''); ?>" placeholder="🚀">
                                                </div>
                                                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                                    <label style="font-size: 0.85em;">Ziel</label>
                                                    <select name="header_buttons[<?php echo $i; ?>][target]" class="form-control">
                                                        <option value="_self" <?php echo ($btn['target'] ?? '') === '_self' ? 'selected' : ''; ?>>Gleicher Tab</option>
                                                        <option value="_blank" <?php echo ($btn['target'] ?? '') === '_blank' ? 'selected' : ''; ?>>Neuer Tab</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div style="margin-top: 0.5rem;">
                                                <label class="checkbox-label" style="font-size: 0.85em;">
                                                    <input type="checkbox" name="header_buttons[<?php echo $i; ?>][outline]" value="1" <?php echo !empty($btn['outline']) ? 'checked' : ''; ?>>
                                                    Als Outline-Button (Transparent)
                                                </label>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="version">Version</label>
                                <input type="text" 
                                       id="version" 
                                       name="version" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($landingHeader['version']); ?>"
                                       placeholder="2.0.0">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">💾 Header speichern</button>
                    </form>
                </div>
                
                <!-- Feature Grid Sektion -->
                <div class="admin-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="margin: 0;">Feature Grid (4x3)</h3>
                        <button onclick="showFeatureModal()" class="btn btn-primary">➕ Feature hinzufügen</button>
                    </div>
                    
                    <div class="landing-feature-grid">
                        <?php foreach ($landingFeatures as $feature): ?>
                            <div class="feature-card" data-id="<?php echo $feature['id']; ?>">
                                <div class="feature-icon"><?php echo $feature['icon']; ?></div>
                                <h4><?php echo htmlspecialchars($feature['title']); ?></h4>
                                <p><?php echo htmlspecialchars($feature['description']); ?></p>
                                <div class="feature-actions">
                                    <button onclick='editFeature(<?php echo json_encode($feature); ?>)' class="btn btn-secondary btn-sm">
                                        ✏️ Bearbeiten
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Wirklich löschen?');">
                                        <input type="hidden" name="landing_action" value="delete_feature">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="feature_id" value="<?php echo $feature['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">🗑️ Löschen</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Footer / Unterer Bereich -->
                <div class="admin-section">
                    <div class="section-header">
                        <h3>Bereich unterhalb Widgets (Footer-Info & CTA)</h3>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="landing_action" value="update_footer">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="form-group">
                            <label class="checkbox-label" style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px; cursor: pointer;">
                                <input type="checkbox" name="show_footer" value="1" <?php echo ($landingFooter['show_footer'] ?? true) ? 'checked' : ''; ?>>
                                <strong>Diesen Bereich anzeigen</strong>
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="footer_content">Inhalt (HTML erlaubt):</label>
                            <textarea name="footer_content" id="footer_content" class="form-control" rows="5"><?php echo htmlspecialchars($landingFooter['content'] ?? ''); ?></textarea>
                            <p class="form-help">Dieser Text erscheint unterhalb der Feature Grid.</p>
                        </div>

                        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group">
                                <label for="footer_button_text">Button Text (Optional):</label>
                                <input type="text" name="footer_button_text" id="footer_button_text" class="form-control" value="<?php echo htmlspecialchars($landingFooter['button_text'] ?? ''); ?>" placeholder="z.B. Jetzt starten">
                                <small class="text-muted">Wenn leer, wird kein Button angezeigt.</small>
                            </div>
                            <div class="form-group">
                                <label for="footer_button_url">Button Link (Optional):</label>
                                <input type="text" name="footer_button_url" id="footer_button_url" class="form-control" value="<?php echo htmlspecialchars($landingFooter['button_url'] ?? ''); ?>" placeholder="z.B. /register oder https://...">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="footer_copyright">Copyright Zeile (ganz unten):</label>
                            <input type="text" name="footer_copyright" id="footer_copyright" class="form-control" value="<?php echo htmlspecialchars($landingFooter['copyright'] ?? ''); ?>">
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">💾 Footer Speichern</button>
                        </div>
                    </form>
                </div>
                
            </div>
        <?php endif; ?>
        
        <!-- Tab Content: Colors -->
        <?php if ($activeTab === 'colors'): ?>
            <div class="tab-content">
                
                <div class="admin-section">
                    <h3>🎨 Farbschema der Landing Page</h3>
                    <p class="text-muted">Passen Sie die Farben Ihrer Landing Page an Ihr Corporate Design an.</p>
                    
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="landing_action" value="update_header">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <!-- Keep existing header data -->
                        <input type="hidden" name="title" value="<?php echo htmlspecialchars($landingHeader['title']); ?>">
                        <input type="hidden" name="subtitle" value="<?php echo htmlspecialchars($landingHeader['subtitle']); ?>">
                        <input type="hidden" name="description" value="<?php echo htmlspecialchars($landingHeader['description']); ?>">
                        <input type="hidden" name="github_url" value="<?php echo htmlspecialchars($landingHeader['github_url']); ?>">
                        <input type="hidden" name="github_text" value="<?php echo htmlspecialchars($landingHeader['github_text'] ?? '💻 GitHub Projekt'); ?>">
                        <input type="hidden" name="gitlab_url" value="<?php echo htmlspecialchars($landingHeader['gitlab_url']); ?>">
                        <input type="hidden" name="gitlab_text" value="<?php echo htmlspecialchars($landingHeader['gitlab_text'] ?? '🦊 GitLab Projekt'); ?>">
                        <input type="hidden" name="version" value="<?php echo htmlspecialchars($landingHeader['version']); ?>">
                        <input type="hidden" name="logo" value="<?php echo htmlspecialchars($landingHeader['logo'] ?? ''); ?>">  
                        
                        <div class="color-grid">
                            <!-- Hero Section Colors -->
                            <div class="color-section">
                                <h4>🌟 Hero Section</h4>
                                
                                <div class="color-input-group">
                                    <label for="hero_gradient_start">Gradient Start</label>
                                    <div class="color-input-wrapper">
                                        <input type="color" 
                                               id="hero_gradient_start" 
                                               name="hero_gradient_start" 
                                               value="<?php echo htmlspecialchars($landingColors['hero_gradient_start']); ?>"
                                               class="color-picker">
                                        <input type="text" 
                                               value="<?php echo htmlspecialchars($landingColors['hero_gradient_start']); ?>"
                                               class="color-text"
                                               readonly>
                                    </div>
                                </div>
                                
                                <div class="color-input-group">
                                    <label for="hero_gradient_end">Gradient Ende</label>
                                    <div class="color-input-wrapper">
                                        <input type="color" 
                                               id="hero_gradient_end" 
                                               name="hero_gradient_end" 
                                               value="<?php echo htmlspecialchars($landingColors['hero_gradient_end']); ?>"
                                               class="color-picker">
                                        <input type="text" 
                                               value="<?php echo htmlspecialchars($landingColors['hero_gradient_end']); ?>"
                                               class="color-text"
                                               readonly>
                                    </div>
                                </div>
                                
                                <div class="color-input-group">
                                    <label for="hero_border">Border Color</label>
                                    <div class="color-input-wrapper">
                                        <input type="color" 
                                               id="hero_border" 
                                               name="hero_border" 
                                               value="<?php echo htmlspecialchars($landingColors['hero_border']); ?>"
                                               class="color-picker">
                                        <input type="text" 
                                               value="<?php echo htmlspecialchars($landingColors['hero_border']); ?>"
                                               class="color-text"
                                               readonly>
                                    </div>
                                </div>
                                
                                <div class="color-input-group">
                                    <label for="hero_text">Text Color</label>
                                    <div class="color-input-wrapper">
                                        <input type="color" 
                                               id="hero_text" 
                                               name="hero_text" 
                                               value="<?php echo htmlspecialchars($landingColors['hero_text']); ?>"
                                               class="color-picker">
                                        <input type="text" 
                                               value="<?php echo htmlspecialchars($landingColors['hero_text']); ?>"
                                               class="color-text"
                                               readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Features Section Colors -->
                            <div class="color-section">
                                <h4>✨ Features Section</h4>
                                
                                <div class="color-input-group">
                                    <label for="features_bg">Background</label>
                                    <div class="color-input-wrapper">
                                        <input type="color" 
                                               id="features_bg" 
                                               name="features_bg" 
                                               value="<?php echo htmlspecialchars($landingColors['features_bg']); ?>"
                                               class="color-picker">
                                        <input type="text" 
                                               value="<?php echo htmlspecialchars($landingColors['features_bg']); ?>"
                                               class="color-text"
                                               readonly>
                                    </div>
                                </div>
                                
                                <div class="color-input-group">
                                    <label for="feature_card_bg">Card Background</label>
                                    <div class="color-input-wrapper">
                                        <input type="color" 
                                               id="feature_card_bg" 
                                               name="feature_card_bg" 
                                               value="<?php echo htmlspecialchars($landingColors['feature_card_bg']); ?>"
                                               class="color-picker">
                                        <input type="text" 
                                               value="<?php echo htmlspecialchars($landingColors['feature_card_bg']); ?>"
                                               class="color-text"
                                               readonly>
                                    </div>
                                </div>
                                
                                <div class="color-input-group">
                                    <label for="feature_card_hover">Card Hover Border</label>
                                    <div class="color-input-wrapper">
                                        <input type="color" 
                                               id="feature_card_hover" 
                                               name="feature_card_hover" 
                                               value="<?php echo htmlspecialchars($landingColors['feature_card_hover']); ?>"
                                               class="color-picker">
                                        <input type="text" 
                                               value="<?php echo htmlspecialchars($landingColors['feature_card_hover']); ?>"
                                               class="color-text"
                                               readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Button Colors -->
                            <div class="color-section">
                                <h4>🔘 Buttons</h4>
                                
                                <div class="color-input-group">
                                    <label for="primary_button">Primary Button</label>
                                    <div class="color-input-wrapper">
                                        <input type="color" 
                                               id="primary_button" 
                                               name="primary_button" 
                                               value="<?php echo htmlspecialchars($landingColors['primary_button']); ?>"
                                               class="color-picker">
                                        <input type="text" 
                                               value="<?php echo htmlspecialchars($landingColors['primary_button']); ?>"
                                               class="color-text"
                                               readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary">💾 Farben speichern</button>
                            <button type="button" onclick="resetColors()" class="btn btn-secondary">🔄 Zurücksetzen</button>
                        </div>
                    </form>
                </div>
                
                <!-- Live Preview -->
                <div class="admin-section">
                    <h3>👁️ Vorschau</h3>
                    <div id="colorPreview" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                        <div class="preview-hero" style="
                            background: linear-gradient(135deg, <?php echo $landingColors['hero_gradient_start']; ?> 0%, <?php echo $landingColors['hero_gradient_end']; ?> 100%);
                            border-bottom: 4px solid <?php echo $landingColors['hero_border']; ?>;
                            color: <?php echo $landingColors['hero_text']; ?>;
                            padding: 3rem;
                            text-align: center;
                        ">
                            <h2 style="margin: 0 0 1rem 0;">Hero Section</h2>
                            <p style="margin: 0 0 1.5rem 0; opacity: 0.9;">Das ist eine Vorschau Ihrer Farben</p>
                            <div style="display: inline-block; padding: 0.75rem 1.5rem; background: <?php echo $landingColors['primary_button']; ?>; border-radius: 0.5rem; color: white; font-weight: 600;">
                                Primary Button
                            </div>
                        </div>
                        <div class="preview-features" style="
                            background: <?php echo $landingColors['features_bg']; ?>;
                            padding: 2rem;
                        ">
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                                <div style="
                                    background: <?php echo $landingColors['feature_card_bg']; ?>;
                                    padding: 1.5rem;
                                    border-radius: 0.5rem;
                                    border: 2px solid <?php echo $landingColors['feature_card_hover']; ?>;
                                ">
                                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🚀</div>
                                    <strong>Feature Card</strong>
                                    <p style="font-size: 0.875rem; color: #64748b; margin: 0.5rem 0 0 0;">Hover Border Farbe</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        <?php endif; ?>
        
    </div>
    
    <!-- Feature Modal -->
    <div id="featureModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Feature bearbeiten</h3>
                <button onclick="closeFeatureModal()" class="modal-close">×</button>
            </div>
            <form method="POST" id="featureForm">
                <input type="hidden" name="landing_action" value="save_feature">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="feature_id" id="feature_id">
                
                <div class="form-group">
                    <label for="feature_icon">Icon (Emoji)</label>
                    <input type="text" 
                           id="feature_icon" 
                           name="icon" 
                           class="form-control" 
                           placeholder="🎯"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="feature_title">Titel</label>
                    <input type="text" 
                           id="feature_title" 
                           name="title" 
                           class="form-control" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="feature_description">Beschreibung</label>
                    <textarea id="feature_description"
                              name="description"
                              class="form-control"
                              rows="3"
                              required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="feature_sort">Sortierung</label>
                    <input type="number" 
                           id="feature_sort" 
                           name="sort_order" 
                           class="form-control" 
                           min="1" 
                           max="99">
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
        let _footerEditor = null;

        function _initFeatureEditor() {
            if (typeof SUNEDITOR === 'undefined') return;
            
            if (!_featureEditor) {
                _featureEditor = SUNEDITOR.create('feature_description', {
                    height: 100,
                    width: '100%',
                    buttonList: [['bold', 'italic', 'link', 'removeFormat']],
                    defaultStyle: 'font-family: inherit; font-size: 14px;'
                });
            }
        }
        
        function _initFooterEditor() {
            if (typeof SUNEDITOR === 'undefined') return;
            
            if (!_footerEditor && document.getElementById('footer_content')) {
                _footerEditor = SUNEDITOR.create('footer_content', {
                    height: 200,
                    width: '100%',
                    buttonList: [
                        ['bold', 'underline', 'italic', 'strike', 'subscript', 'superscript'],
                        ['fontColor', 'hiliteColor'],
                        ['outdent', 'indent', 'align', 'list', 'horizontalRule'],
                        ['link', 'image'],
                        ['fullScreen', 'showBlocks', 'codeView']
                    ],
                    defaultStyle: 'font-family: inherit; font-size: 14px;'
                });
            }
        }

        // Init Footer Editor on load if tab is landing
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($activeTab === 'landing'): ?>
            setTimeout(_initFooterEditor, 100);
            <?php endif; ?>
        });

        function showFeatureModal() {
            document.getElementById('modalTitle').textContent = 'Neues Feature';
            document.getElementById('featureForm').reset();
            document.getElementById('feature_id').value = '';
            document.getElementById('featureModal').style.display = 'flex';
            setTimeout(() => {
                _initFeatureEditor();
                if (_featureEditor) _featureEditor.setContents('');
            }, 50);
        }
        
        function editFeature(feature) {
            document.getElementById('modalTitle').textContent = 'Feature bearbeiten';
            document.getElementById('feature_id').value = feature.id;
            document.getElementById('feature_icon').value = feature.icon;
            document.getElementById('feature_title').value = feature.title;
            document.getElementById('feature_sort').value = feature.sort_order;
            document.getElementById('featureModal').style.display = 'flex';
            setTimeout(() => {
                _initFeatureEditor();
                if (_featureEditor) {
                    _featureEditor.setContents(feature.description);
                } else {
                    document.getElementById('feature_description').value = feature.description;
                }
            }, 50);
        }
        
        function closeFeatureModal() {
            document.getElementById('featureModal').style.display = 'none';
        }
        
        // Close modal on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('featureModal');
            if (event.target === modal) {
                closeFeatureModal();
            }
        }
        
        // Color Picker Synchronization
        document.querySelectorAll('.color-picker').forEach(picker => {
            picker.addEventListener('input', function() {
                const textInput = this.parentElement.querySelector('.color-text');
                if (textInput) {
                    textInput.value = this.value;
                }
                updateColorPreview();
            });
        });
        
        function updateColorPreview() {
            const hero = document.querySelector('.preview-hero');
            const features = document.querySelector('.preview-features');
            const featureCard = document.querySelector('.preview-features > div > div');
            const button = document.querySelector('.preview-hero > div');
            
            if (hero) {
                const gradStart = document.getElementById('hero_gradient_start')?.value || '#1e293b';
                const gradEnd = document.getElementById('hero_gradient_end')?.value || '#0f172a';
                const border = document.getElementById('hero_border')?.value || '#3b82f6';
                const text = document.getElementById('hero_text')?.value || '#ffffff';
                const primaryBtn = document.getElementById('primary_button')?.value || '#3b82f6';
                
                hero.style.background = `linear-gradient(135deg, ${gradStart} 0%, ${gradEnd} 100%)`;
                hero.style.borderBottom = `4px solid ${border}`;
                hero.style.color = text;
                
                if (button) {
                    button.style.background = primaryBtn;
                }
            }
            
            if (features) {
                const featBg = document.getElementById('features_bg')?.value || '#f8fafc';
                features.style.background = featBg;
            }
            
            if (featureCard) {
                const cardBg = document.getElementById('feature_card_bg')?.value || '#ffffff';
                const hoverBorder = document.getElementById('feature_card_hover')?.value || '#3b82f6';
                featureCard.style.background = cardBg;
                featureCard.style.borderColor = hoverBorder;
            }
        }
        
        function resetColors() {
            if (confirm('Möchten Sie wirklich alle Farben auf die Standardwerte zurücksetzen?')) {
                const defaults = {
                    'hero_gradient_start': '#1e293b',
                    'hero_gradient_end': '#0f172a',
                    'hero_border': '#3b82f6',
                    'hero_text': '#ffffff',
                    'features_bg': '#f8fafc',
                    'feature_card_bg': '#ffffff',
                    'feature_card_hover': '#3b82f6',
                    'primary_button': '#3b82f6'
                };
                
                Object.keys(defaults).forEach(key => {
                    const picker = document.getElementById(key);
                    const text = picker?.parentElement.querySelector('.color-text');
                    if (picker) {
                        picker.value = defaults[key];
                        if (text) text.value = defaults[key];
                    }
                });
                
                updateColorPreview();
            }
        }
    </script>
</body>
</html>