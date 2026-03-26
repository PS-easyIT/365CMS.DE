<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * View: Landing Page – Tab-basierte Verwaltung
 *
 * @var array  $data
 * @var string $csrfToken
 * @var array|null $alert
 */

$tab = preg_replace('/[^a-z]/', '', $_GET['tab'] ?? 'header');
$tabs = [
    'header'  => 'Header',
    'content' => 'Content',
    'footer'  => 'Footer',
    'design'  => 'Design',
    'plugins' => 'Plugins',
];
?>

<div class="container-xl">
    <!-- Header -->
    <div class="page-header d-flex align-items-center mb-4">
        <div>
            <h2 class="page-title">Landing Page</h2>
            <div class="text-muted mt-1">Startseite konfigurieren</div>
        </div>
    </div>

    <?php if (!empty($alert)): ?>
        <?php $alertData = $alert; $alertMarginClass = 'mb-3'; require __DIR__ . '/../partials/flash-alert.php'; ?>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <?php foreach ($tabs as $key => $label): ?>
                    <li class="nav-item">
                        <a class="nav-link<?php echo $tab === $key ? ' active' : ''; ?>"
                           href="<?php echo SITE_URL; ?>/admin/landing-page?tab=<?php echo $key; ?>">
                            <?php echo htmlspecialchars($label); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="card-body">
            <?php if ($tab === 'header'): ?>
                <?php
                $header = $data['header'] ?? [];
                ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="save_header">

                    <div class="mb-3">
                        <label class="form-label">Titel</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($header['title'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Untertitel</label>
                        <input type="text" name="subtitle" class="form-control" value="<?php echo htmlspecialchars($header['subtitle'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beschreibung</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($header['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">CTA Button Text</label>
                            <input type="text" name="cta_text" class="form-control" value="<?php echo htmlspecialchars($header['cta_text'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CTA Button URL</label>
                            <input type="text" name="cta_url" class="form-control" value="<?php echo htmlspecialchars($header['cta_url'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hintergrundbild URL</label>
                        <input type="text" name="bg_image" class="form-control" value="<?php echo htmlspecialchars($header['bg_image'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">Speichern</button>
                </form>

            <?php elseif ($tab === 'content'): ?>
                <?php
                $content  = $data['content'] ?? [];
                $features = $data['features'] ?? [];
                ?>
                <form method="post" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="save_content">

                    <div class="mb-3">
                        <label class="form-label">Inhaltstyp</label>
                        <select name="content_type" class="form-select">
                            <option value="features" <?php echo ($content['content_type'] ?? 'features') === 'features' ? 'selected' : ''; ?>>Feature-Kacheln</option>
                            <option value="text" <?php echo ($content['content_type'] ?? '') === 'text' ? 'selected' : ''; ?>>Freitext-Bereich</option>
                            <option value="posts" <?php echo ($content['content_type'] ?? '') === 'posts' ? 'selected' : ''; ?>>Aktuelle Beiträge</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Freitext-Inhalt</label>
                        <textarea name="content_text" class="form-control" rows="5" placeholder="Wird nur verwendet, wenn ‚Freitext-Bereich‘ ausgewählt ist."><?php echo htmlspecialchars($content['content_text'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Anzahl Beiträge</label>
                        <input type="number" name="posts_count" min="1" max="50" class="form-control" value="<?php echo (int)($content['posts_count'] ?? 5); ?>">
                        <div class="form-hint">Wird nur verwendet, wenn „Aktuelle Beiträge“ aktiv ist.</div>
                    </div>

                    <button type="submit" class="btn btn-primary">Content-Einstellungen speichern</button>
                </form>

                <hr class="my-4">
                <h3 class="mb-3">Features / Kacheln</h3>

                <?php if (!empty($features)): ?>
                    <div class="row row-cards mb-3">
                        <?php foreach ($features as $feature): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="fs-1 lh-1"><?php echo htmlspecialchars($feature['icon'] ?? '🧩'); ?></div>
                                            <div>
                                                <h4 class="mb-1"><?php echo htmlspecialchars($feature['title'] ?? ''); ?></h4>
                                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($feature['description'] ?? ''); ?></p>
                                                <span class="badge bg-azure-lt">Reihenfolge <?php echo (int)($feature['sort_order'] ?? 0); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex gap-2">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="action" value="delete_feature">
                                            <input type="hidden" name="feature_id" value="<?php echo (int)($feature['id'] ?? 0); ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Löschen</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="card bg-light">
                    <div class="card-header"><h4 class="card-title">Neues Feature hinzufügen</h4></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="action" value="save_feature">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Titel</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Icon (CSS-Klasse)</label>
                                    <input type="text" name="icon" class="form-control" placeholder="z.B. icon-star">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Reihenfolge</label>
                                    <input type="number" name="sort_order" class="form-control" value="0">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Beschreibung</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Feature hinzufügen</button>
                        </form>
                    </div>
                </div>

            <?php elseif ($tab === 'footer'): ?>
                <?php
                $footer = $data['footer'] ?? [];
                ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="save_footer">

                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input type="checkbox" name="show_footer" class="form-check-input" value="1" <?php echo !empty($footer['show_footer']) ? 'checked' : ''; ?>>
                            <span class="form-check-label">Landing-Footer anzeigen</span>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Footer-Inhalt</label>
                        <textarea name="footer_content" class="form-control" rows="5"><?php echo htmlspecialchars($footer['content'] ?? ''); ?></textarea>
                        <div class="form-hint">Kurzer CTA oder Zusammenfassung der wichtigsten 365CMS-Features.</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Button-Text</label>
                            <input type="text" name="footer_button_text" class="form-control" value="<?php echo htmlspecialchars($footer['button_text'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Button-URL</label>
                            <input type="text" name="footer_button_url" class="form-control" value="<?php echo htmlspecialchars($footer['button_url'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Copyright-Text</label>
                        <input type="text" name="footer_copyright" class="form-control" value="<?php echo htmlspecialchars($footer['copyright'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">Speichern</button>
                </form>

            <?php elseif ($tab === 'design'): ?>
                <?php
                $design = $data['design'] ?? [];
                $colors = $data['colors'] ?? [];
                ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="save_design">

                    <h4 class="mb-3">Landing-Farben</h4>
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Hero-Verlauf Start</label>
                            <input type="color" name="hero_gradient_start" class="form-control form-control-color" value="<?php echo htmlspecialchars($colors['hero_gradient_start'] ?? '#1e293b'); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Hero-Verlauf Ende</label>
                            <input type="color" name="hero_gradient_end" class="form-control form-control-color" value="<?php echo htmlspecialchars($colors['hero_gradient_end'] ?? '#0f172a'); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Hero-Rand</label>
                            <input type="color" name="hero_border" class="form-control form-control-color" value="<?php echo htmlspecialchars($colors['hero_border'] ?? '#3b82f6'); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Hero-Text</label>
                            <input type="color" name="hero_text" class="form-control form-control-color" value="<?php echo htmlspecialchars($colors['hero_text'] ?? '#ffffff'); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Kachel-Bereich</label>
                            <input type="color" name="features_bg" class="form-control form-control-color" value="<?php echo htmlspecialchars($colors['features_bg'] ?? '#f8fafc'); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Kachel-Hintergrund</label>
                            <input type="color" name="feature_card_bg" class="form-control form-control-color" value="<?php echo htmlspecialchars($colors['feature_card_bg'] ?? '#ffffff'); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Kachel-Hover</label>
                            <input type="color" name="feature_card_hover" class="form-control form-control-color" value="<?php echo htmlspecialchars($colors['feature_card_hover'] ?? '#3b82f6'); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Primärer Button</label>
                            <input type="color" name="primary_button" class="form-control form-control-color" value="<?php echo htmlspecialchars($colors['primary_button'] ?? '#3b82f6'); ?>">
                        </div>
                    </div>

                    <h4 class="mb-3">Layout & Karten</h4>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Icon-Ausrichtung</label>
                            <select name="card_icon_layout" class="form-select">
                                <option value="top" <?php echo ($design['card_icon_layout'] ?? 'top') === 'top' ? 'selected' : ''; ?>>Oben</option>
                                <option value="left" <?php echo ($design['card_icon_layout'] ?? '') === 'left' ? 'selected' : ''; ?>>Links</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Feature-Spalten</label>
                            <select name="feature_columns" class="form-select">
                                <option value="auto" <?php echo ($design['feature_columns'] ?? 'auto') === 'auto' ? 'selected' : ''; ?>>Automatisch responsiv</option>
                                <option value="2" <?php echo ($design['feature_columns'] ?? '') === '2' ? 'selected' : ''; ?>>2 Spalten</option>
                                <option value="3" <?php echo ($design['feature_columns'] ?? '') === '3' ? 'selected' : ''; ?>>3 Spalten</option>
                                <option value="4" <?php echo ($design['feature_columns'] ?? '') === '4' ? 'selected' : ''; ?>>4 Spalten</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Kartenradius</label>
                            <input type="number" name="card_border_radius" min="0" max="48" class="form-control" value="<?php echo (int)($design['card_border_radius'] ?? 18); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Button-Radius</label>
                            <input type="number" name="button_border_radius" min="0" max="50" class="form-control" value="<?php echo (int)($design['button_border_radius'] ?? 12); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Kartenrand</label>
                            <select name="card_border_width" class="form-select">
                                <option value="0" <?php echo ($design['card_border_width'] ?? '1px') === '0' ? 'selected' : ''; ?>>Kein Rand</option>
                                <option value="1px" <?php echo ($design['card_border_width'] ?? '1px') === '1px' ? 'selected' : ''; ?>>1px</option>
                                <option value="2px" <?php echo ($design['card_border_width'] ?? '') === '2px' ? 'selected' : ''; ?>>2px</option>
                                <option value="3px" <?php echo ($design['card_border_width'] ?? '') === '3px' ? 'selected' : ''; ?>>3px</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Karten-Schatten</label>
                            <select name="card_shadow" class="form-select">
                                <option value="none" <?php echo ($design['card_shadow'] ?? 'md') === 'none' ? 'selected' : ''; ?>>Keiner</option>
                                <option value="sm" <?php echo ($design['card_shadow'] ?? '') === 'sm' ? 'selected' : ''; ?>>Klein</option>
                                <option value="md" <?php echo ($design['card_shadow'] ?? 'md') === 'md' ? 'selected' : ''; ?>>Mittel</option>
                                <option value="lg" <?php echo ($design['card_shadow'] ?? '') === 'lg' ? 'selected' : ''; ?>>Groß</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Karten-Randfarbe</label>
                            <input type="color" name="card_border_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($design['card_border_color'] ?? '#e2e8f0'); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Footer-Hintergrund</label>
                            <input type="color" name="footer_bg" class="form-control form-control-color" value="<?php echo htmlspecialchars($design['footer_bg'] ?? '#0f172a'); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Footer-Textfarbe</label>
                            <input type="color" name="footer_text_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($design['footer_text_color'] ?? '#cbd5e1'); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Content-Hintergrund</label>
                            <input type="color" name="content_section_bg" class="form-control form-control-color" value="<?php echo htmlspecialchars($design['content_section_bg'] ?? '#ffffff'); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Hero-Padding</label>
                            <select name="hero_padding" class="form-select">
                                <option value="sm" <?php echo ($design['hero_padding'] ?? 'md') === 'sm' ? 'selected' : ''; ?>>Klein</option>
                                <option value="md" <?php echo ($design['hero_padding'] ?? 'md') === 'md' ? 'selected' : ''; ?>>Mittel</option>
                                <option value="lg" <?php echo ($design['hero_padding'] ?? '') === 'lg' ? 'selected' : ''; ?>>Groß</option>
                                <option value="xl" <?php echo ($design['hero_padding'] ?? '') === 'xl' ? 'selected' : ''; ?>>XL</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Feature-Padding</label>
                            <select name="feature_padding" class="form-select">
                                <option value="sm" <?php echo ($design['feature_padding'] ?? 'md') === 'sm' ? 'selected' : ''; ?>>Klein</option>
                                <option value="md" <?php echo ($design['feature_padding'] ?? 'md') === 'md' ? 'selected' : ''; ?>>Mittel</option>
                                <option value="lg" <?php echo ($design['feature_padding'] ?? '') === 'lg' ? 'selected' : ''; ?>>Groß</option>
                                <option value="xl" <?php echo ($design['feature_padding'] ?? '') === 'xl' ? 'selected' : ''; ?>>XL</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Speichern</button>
                </form>

            <?php elseif ($tab === 'plugins'): ?>
                <?php
                $plugins   = $data['plugins'] ?? [];
                $overrides = $data['overrides'] ?? [];
                ?>

                <?php if (empty($plugins)): ?>
                    <div class="text-center py-5 text-muted">
                        <p>Keine Plugins haben Landing-Page-Bereiche registriert.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($plugins as $plugin): ?>
                        <div class="card mb-3">
                            <div class="card-header">
                                <h4 class="card-title"><?php echo htmlspecialchars($plugin['label'] ?? $plugin['id'] ?? ''); ?></h4>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="action" value="save_plugin">
                                    <input type="hidden" name="plugin_id" value="<?php echo htmlspecialchars($plugin['id'] ?? ''); ?>">

                                    <div class="mb-3">
                                        <label class="form-check form-switch">
                                            <input type="checkbox" name="enabled" class="form-check-input" value="1"
                                                   <?php echo !empty($overrides[$plugin['id'] ?? '']['enabled']) ? 'checked' : ''; ?>>
                                            <span class="form-check-label">Auf Landing Page anzeigen</span>
                                        </label>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Reihenfolge</label>
                                        <input type="number" name="sort_order" class="form-control" style="width: 120px;"
                                               value="<?php echo (int)($overrides[$plugin['id'] ?? '']['sort_order'] ?? 10); ?>">
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-sm">Speichern</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>
