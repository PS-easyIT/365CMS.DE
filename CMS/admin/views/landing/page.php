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

    <?php if ($alert): ?>
        <div class="alert alert-<?php echo htmlspecialchars($alert['type']); ?> alert-dismissible" role="alert">
            <?php echo htmlspecialchars($alert['message']); ?>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
        </div>
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
                        <label class="form-label">Content-Bereich Überschrift</label>
                        <input type="text" name="section_title" class="form-control" value="<?php echo htmlspecialchars($content['section_title'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content-Bereich Beschreibung</label>
                        <textarea name="section_description" class="form-control" rows="2"><?php echo htmlspecialchars($content['section_description'] ?? ''); ?></textarea>
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
                                        <h4><?php echo htmlspecialchars($feature->title ?? ''); ?></h4>
                                        <p class="text-muted small"><?php echo htmlspecialchars($feature->description ?? ''); ?></p>
                                    </div>
                                    <div class="card-footer d-flex gap-2">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="action" value="delete_feature">
                                            <input type="hidden" name="feature_id" value="<?php echo (int)($feature->id ?? 0); ?>">
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
                        <label class="form-label">Copyright-Text</label>
                        <input type="text" name="copyright" class="form-control" value="<?php echo htmlspecialchars($footer['copyright'] ?? ''); ?>">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Impressum URL</label>
                            <input type="text" name="imprint_url" class="form-control" value="<?php echo htmlspecialchars($footer['imprint_url'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Datenschutz URL</label>
                            <input type="text" name="privacy_url" class="form-control" value="<?php echo htmlspecialchars($footer['privacy_url'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Zusätzlicher Footer-Text</label>
                        <textarea name="extra_text" class="form-control" rows="3"><?php echo htmlspecialchars($footer['extra_text'] ?? ''); ?></textarea>
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

                    <h4 class="mb-3">Farben</h4>
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Primärfarbe</label>
                            <input type="color" name="primary_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($colors['primary'] ?? '#2563eb'); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sekundärfarbe</label>
                            <input type="color" name="secondary_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($colors['secondary'] ?? '#64748b'); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Akzentfarbe</label>
                            <input type="color" name="accent_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($colors['accent'] ?? '#e8a838'); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Hintergrund</label>
                            <input type="color" name="bg_color" class="form-control form-control-color" value="<?php echo htmlspecialchars($colors['background'] ?? '#ffffff'); ?>">
                        </div>
                    </div>

                    <h4 class="mb-3">Layout</h4>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Header-Stil</label>
                            <select name="header_style" class="form-select">
                                <option value="centered" <?php echo ($design['header_style'] ?? '') === 'centered' ? 'selected' : ''; ?>>Zentriert</option>
                                <option value="left" <?php echo ($design['header_style'] ?? '') === 'left' ? 'selected' : ''; ?>>Linksbündig</option>
                                <option value="split" <?php echo ($design['header_style'] ?? '') === 'split' ? 'selected' : ''; ?>>Geteilt</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Feature-Layout</label>
                            <select name="feature_layout" class="form-select">
                                <option value="grid" <?php echo ($design['feature_layout'] ?? '') === 'grid' ? 'selected' : ''; ?>>Grid (3 Spalten)</option>
                                <option value="list" <?php echo ($design['feature_layout'] ?? '') === 'list' ? 'selected' : ''; ?>>Liste</option>
                                <option value="cards" <?php echo ($design['feature_layout'] ?? '') === 'cards' ? 'selected' : ''; ?>>Cards</option>
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
