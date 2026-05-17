<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * View: Themes – Liste aller installierten Themes
 *
 * @var array  $data
 * @var string $csrfToken
 * @var array|null $alert
 */

$themes      = $data['themes'] ?? [];
$activeSlug  = $data['activeSlug'] ?? '';
$totalThemes = $data['totalThemes'] ?? 0;
$activeCount = 0;
foreach ($themes as $themeItem) {
    if (!empty($themeItem['isActive'])) {
        $activeCount++;
    }
}
$inactiveCount = max(0, (int)$totalThemes - $activeCount);
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="content-listing-header">
            <div>
                <div class="page-pretitle">Themes</div>
                <h2 class="page-title mb-1">Theme-Verwaltung</h2>
                <div class="content-listing-header__meta">
                    <span><?php echo (int) $totalThemes; ?> installiert</span>
                    <span>Aktiv: <?php echo $escape($activeSlug !== '' ? $activeSlug : 'unbekannt'); ?></span>
                </div>
            </div>
            <div class="admin-section-toolbar__actions">
                <a href="/admin/theme-marketplace" class="btn btn-outline-primary btn-sm">Marketplace</a>
                <a href="/admin/theme-settings" class="btn btn-outline-secondary btn-sm">Design-Einstellungen</a>
            </div>
        </div>
    </div>
</div>

<div class="page-body themes-list-page">
<div class="container-xl">
    <?php if (!empty($alert)): ?>
        <?php
        $alertData = is_array($alert ?? null) ? $alert : [];
        require dirname(__DIR__) . '/partials/flash-alert.php';
        ?>
    <?php endif; ?>

    <div class="cms-admin-info-box mb-3" role="note">
        <div class="cms-admin-info-box__head">
            <h3 class="cms-admin-info-box__title">Theme-Runtime und Verwaltung</h3>
            <div class="cms-admin-info-box__actions">
                <a href="/admin/theme-editor" class="btn btn-sm btn-outline-secondary">Theme-Editor</a>
                <a href="/admin/theme-explorer" class="btn btn-sm btn-outline-secondary">Theme-Explorer</a>
            </div>
        </div>
        <p class="cms-admin-info-box__text">
            Aktiviere verfügbare Themes oder entferne nicht mehr benötigte Themes direkt aus der Übersicht.
        </p>
    </div>

    <div class="card content-listing-card mb-4">
        <div class="card-header content-listing-toolbar">
            <div class="content-listing-toolbar__label">Installierte Themes</div>
            <div class="content-listing-filters">
                <div class="content-listing-filters__actions">
                    <span class="text-secondary small">Aktiv: <strong><?php echo $activeCount; ?></strong></span>
                    <span class="text-secondary small">Inaktiv: <strong><?php echo $inactiveCount; ?></strong></span>
                    <span class="text-secondary small">Alle installierten Themes mit Aktivierungs- und Verwaltungsaktionen.</span>
                </div>
            </div>
        </div>
        <div class="card-body">
    <div class="row row-cards admin-theme-list-grid">
        <?php foreach ($themes as $slug => $theme): ?>
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 admin-theme-list-card<?php echo !empty($theme['isActive']) ? ' border-primary' : ''; ?>">
                    <!-- Screenshot -->
                    <div class="card-img-top admin-theme-preview">
                        <?php if (!empty($theme['screenshot'])): ?>
                            <img src="<?php echo htmlspecialchars($theme['screenshot']); ?>" alt="<?php echo htmlspecialchars($theme['name'] ?? $slug); ?>" class="admin-theme-preview__image">
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-palette" width="48" height="48" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.3;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 21a9 9 0 0 1 0 -18c4.97 0 9 3.582 9 8c0 1.06 -.474 2.078 -1.318 2.828c-.844 .75 -1.989 1.172 -3.182 1.172h-2.5a2 2 0 0 0 -1 3.75a1.3 1.3 0 0 1 -1 2.25"/><path d="M8.5 10.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12.5 7.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M16.5 10.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <h3 class="card-title mb-0"><?php echo $escape($theme['name'] ?? $slug); ?></h3>
                            <?php if (!empty($theme['isActive'])): ?>
                                <span class="badge bg-primary ms-2">Aktiv</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($theme['description'])): ?>
                            <p class="text-muted mb-2"><?php echo $escape($theme['description']); ?></p>
                        <?php endif; ?>
                        <div class="text-muted small">
                            <?php if (!empty($theme['version'])): ?>
                                <span class="me-3">v<?php echo $escape($theme['version']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($theme['author'])): ?>
                                <span><?php echo $escape($theme['author']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer d-flex gap-2">
                        <?php if (empty($theme['isActive'])): ?>
                            <form method="post" class="d-inline" data-theme-submit-lock="1">
                                <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                                <input type="hidden" name="action" value="activate">
                                <input type="hidden" name="theme" value="<?php echo $escape($slug); ?>">
                                <button type="submit" class="btn btn-primary btn-sm" data-submitting-text="Aktiviere…">Aktivieren</button>
                            </form>
                            <form method="post"
                                  class="d-inline"
                                  data-theme-submit-lock="1"
                                  data-confirm-message="Soll das Theme &quot;<?php echo $escape($theme['name'] ?? $slug); ?>&quot; wirklich gelöscht werden? Diese Aktion kann nicht rückgängig gemacht werden."
                                  data-confirm-title="Theme löschen"
                                  data-confirm-text="Löschen"
                                  data-confirm-class="btn-danger"
                                  data-confirm-status-class="bg-danger">
                                <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="theme" value="<?php echo $escape($slug); ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm" data-submitting-text="Lösche…">
                                    Löschen
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="/admin/theme-editor" class="btn btn-outline-primary btn-sm">Editor</a>
                            <a href="/admin/theme-explorer" class="btn btn-outline-secondary btn-sm">Explorer</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
        </div>
    </div>
</div>
</div>
