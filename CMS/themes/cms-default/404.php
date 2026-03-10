<?php
/**
 * Meridian CMS Default – 404 Not Found Template
 *
 * @package CMSv2\Themes\CmsDefault
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!headers_sent()) {
    http_response_code(404);
}

$recentPosts = meridian_get_recent_posts(3);
?>

<div class="container">
    <div class="error-page">
        <div class="error-page-inner">
            <div class="error-code">404</div>
            <h1 class="error-title">Seite nicht gefunden</h1>
            <p class="error-desc">
                Die gesuchte Seite existiert nicht oder wurde verschoben.
                Überprüfe die Adresse oder kehre zur Startseite zurück.
            </p>
            <div class="error-actions">
                <a href="<?php echo SITE_URL; ?>/" class="btn-solid">Zur Startseite</a>
                <a href="<?php echo SITE_URL; ?>/blog" class="btn-ghost">Blog durchsuchen</a>
            </div>
        </div>
    </div>

    <?php if (!empty($recentPosts)): ?>
    <section class="error-suggestions">
        <h2 class="section-label" style="text-align:center;margin-bottom:1.5rem;">Aktuelle Artikel</h2>
        <div class="card-grid">
            <?php foreach ($recentPosts as $post): ?>
            <article class="card">
                <?php if (!empty($post['featured_image'])): ?>
                <a href="<?php echo SITE_URL; ?>/blog/<?php echo htmlspecialchars((string) ($post['slug'] ?? '')); ?>" class="card-img">
                    <img src="<?php echo htmlspecialchars((string) ($post['featured_image'] ?? '')); ?>"
                         alt="<?php echo htmlspecialchars((string) ($post['title'] ?? '')); ?>"
                         loading="lazy">
                </a>
                <?php endif; ?>
                <div class="card-body">
                    <h3 class="card-title">
                        <a href="<?php echo SITE_URL; ?>/blog/<?php echo htmlspecialchars((string) ($post['slug'] ?? '')); ?>">
                            <?php echo htmlspecialchars((string) ($post['title'] ?? '')); ?>
                        </a>
                    </h3>
                    <div class="art-meta">
                        <span class="art-date"><?php echo meridian_format_date($post['published_at'] ?? $post['created_at'] ?? '', true); ?></span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</div><!-- /.container -->
