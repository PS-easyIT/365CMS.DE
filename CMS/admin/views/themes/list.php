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
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>

<div class="container-xl">
    <!-- Header -->
    <div class="page-header d-flex align-items-center mb-4">
        <div>
            <h2 class="page-title">Themes</h2>
            <div class="text-muted mt-1"><?php echo (int) $totalThemes; ?> Theme<?php echo (int) $totalThemes !== 1 ? 's' : ''; ?> installiert</div>
        </div>
    </div>

    <?php if (!empty($alert)): ?>
        <?php
        $alertData = is_array($alert ?? null) ? $alert : [];
        require dirname(__DIR__) . '/partials/flash-alert.php';
        ?>
    <?php endif; ?>

    <!-- Themes Grid -->
    <div class="row row-deck row-cards">
        <?php foreach ($themes as $slug => $theme): ?>
            <div class="col-sm-6 col-lg-4">
                <div class="card<?php echo !empty($theme['isActive']) ? ' border-primary' : ''; ?>">
                    <!-- Screenshot -->
                    <div class="card-img-top" style="height: 200px; background: var(--tblr-bg-surface-secondary); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <?php if (!empty($theme['screenshot'])): ?>
                            <img src="<?php echo htmlspecialchars($theme['screenshot']); ?>" alt="<?php echo htmlspecialchars($theme['name'] ?? $slug); ?>" style="width: 100%; height: 100%; object-fit: cover;">
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
