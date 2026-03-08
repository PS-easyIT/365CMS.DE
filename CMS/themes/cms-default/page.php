<?php
/**
 * Meridian CMS Default – Statische Seite Template
 *
 * Vom Router bereitgestellte Variable:
 *   $page – array: id, title, slug, content, meta_description, updated_at
 *
 * @package CMSv2\Themes\CmsDefault
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$pageTitle   = $page['title']       ?? '';
$pageContent = $page['content']     ?? '';
$pageUpdated = $page['updated_at']  ?? '';
$pageId      = (int)($page['id']    ?? 0);
$isHubSitePage = (($page['content_type'] ?? '') === 'hub') || str_contains((string)$pageContent, 'cms-hub-site');
?>

<div class="container">
    <div class="page-content-wrap<?php echo $isHubSitePage ? ' page-content-wrap--hub' : ''; ?>">
        <article class="page-article<?php echo $isHubSitePage ? ' page-article--hub' : ''; ?>">

            <?php if (!$isHubSitePage): ?>
                <!-- Breadcrumb -->
                <nav class="breadcrumb" aria-label="Breadcrumb" style="margin-bottom:1.5rem;">
                    <a href="<?php echo SITE_URL; ?>/">Startseite</a>
                    <span class="breadcrumb-sep" aria-hidden="true">›</span>
                    <span class="breadcrumb-current" aria-current="page"><?php echo htmlspecialchars($pageTitle); ?></span>
                </nav>

                <header class="post-header">
                    <h1 class="post-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
                    <?php if ($pageUpdated): ?>
                    <p class="post-meta-bar">
                        <time class="post-meta-date" datetime="<?php echo htmlspecialchars(substr($pageUpdated, 0, 10)); ?>">
                            Aktualisiert: <?php echo meridian_format_date($pageUpdated); ?>
                        </time>
                    </p>
                    <?php endif; ?>
                </header>
            <?php endif; ?>

            <div class="post-body page-content<?php echo $isHubSitePage ? ' post-body--hub' : ''; ?>">
                <?php echo $pageContent; // Kommt aus DB, bereits sanitiert ?>
            </div>

        </article>
    </div><!-- /.page-content-wrap -->
</div><!-- /.container -->
