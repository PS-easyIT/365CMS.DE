<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * View: Theme Marketplace
 *
 * @var array  $data
 * @var string $csrfToken
 * @var array|null $alert
 */

$catalog = $data['catalog'] ?? [];
$total   = $data['total'] ?? 0;
?>

<div class="container-xl">
    <!-- Header -->
    <div class="page-header d-flex align-items-center mb-4">
        <div>
            <h2 class="page-title">Theme Marketplace</h2>
            <div class="text-muted mt-1"><?php echo $total; ?> Theme<?php echo $total !== 1 ? 's' : ''; ?> verfügbar</div>
        </div>
    </div>

    <?php if ($alert): ?>
        <div class="alert alert-<?php echo htmlspecialchars($alert['type']); ?> alert-dismissible" role="alert">
            <?php echo htmlspecialchars($alert['message']); ?>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
        </div>
    <?php endif; ?>

    <?php if (empty($catalog)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-packages mb-3" width="48" height="48" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.3;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 16.5l-5 -3l5 -3l5 3v5.5l-5 3z"/><path d="M2 13.5v5.5l5 3"/><path d="M7 16.5l5 -3"/><path d="M12 19v5.5"/><path d="M17 16.5l-5 -3l5 -3l5 3v5.5l-5 3z"/><path d="M12 13.5v5.5l5 3"/><path d="M17 16.5l5 -3"/><path d="M12 8l-5 -3l5 -3l5 3v5.5l-5 3z"/><path d="M7 5v5.5l5 3"/><path d="M12 8l5 -3"/></svg>
                <h3>Kein Theme-Katalog verfügbar</h3>
                <p class="text-muted">Die index.json wurde nicht gefunden oder enthält keine Themes.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row row-deck row-cards">
            <?php foreach ($catalog as $theme):
                $slug = $theme['slug'] ?? '';
                $name = $theme['name'] ?? $slug;
            ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="card">
                        <div class="card-img-top" style="height: 180px; background: var(--tblr-bg-surface-secondary); display: flex; align-items: center; justify-content: center;">
                            <?php if (!empty($theme['screenshot'])): ?>
                                <img src="<?php echo htmlspecialchars($theme['screenshot']); ?>" alt="<?php echo htmlspecialchars($name); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-palette" width="48" height="48" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.3;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 21a9 9 0 0 1 0 -18c4.97 0 9 3.582 9 8c0 1.06 -.474 2.078 -1.318 2.828c-.844 .75 -1.989 1.172 -3.182 1.172h-2.5a2 2 0 0 0 -1 3.75a1.3 1.3 0 0 1 -1 2.25"/><path d="M8.5 10.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12.5 7.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M16.5 10.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <h3 class="card-title mb-0"><?php echo htmlspecialchars($name); ?></h3>
                                <?php if (!empty($theme['active'])): ?>
                                    <span class="badge bg-primary ms-2">Aktiv</span>
                                <?php elseif (!empty($theme['installed'])): ?>
                                    <span class="badge bg-green ms-2">Installiert</span>
                                <?php endif; ?>
                                <?php if (!empty($theme['updateAvailable'])): ?>
                                    <span class="badge bg-warning ms-2">Update</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($theme['description'])): ?>
                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($theme['description']); ?></p>
                            <?php endif; ?>
                            <div class="text-muted small">
                                <?php if (!empty($theme['version'])): ?>
                                    <span class="me-2">v<?php echo htmlspecialchars($theme['version']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($theme['author'])): ?>
                                    <span><?php echo htmlspecialchars($theme['author']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer">
                            <?php if (empty($theme['installed'])): ?>
                                <?php if (!empty($theme['install_supported'])): ?>
                                    <div class="d-flex flex-column gap-2">
                                        <span class="badge bg-green-lt text-success">SHA-256 verifiziert</span>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="action" value="install">
                                            <input type="hidden" name="theme" value="<?php echo htmlspecialchars($slug); ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">Installieren</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-2">
                                        <span class="badge bg-secondary-lt text-secondary">Nur manuell</span>
                                        <span class="text-muted small"><?php echo htmlspecialchars((string)($theme['install_reason'] ?? 'Für dieses Theme ist aktuell kein Installationspaket im Marketplace hinterlegt.')); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php elseif (!empty($theme['active'])): ?>
                                <span class="text-muted small">Aktives Theme</span>
                            <?php else: ?>
                                <span class="text-muted small">Bereits installiert</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
