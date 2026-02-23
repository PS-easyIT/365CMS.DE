<?php
/**
 * Page Template - Einzelne Seite aus DB
 *
 * Erhält $page als Array mit den Feldern:
 * id, title, slug, content, meta_description, status, created_at, updated_at
 *
 * @package IT_Expert_Network_Theme
 * @var array $page
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($page) || !is_array($page)) {
    http_response_code(404);
    \CMS\ThemeManager::instance()->render('404');
    return;
}

$pageTitle   = $page['title'] ?? '';
$pageContent = $page['content'] ?? '';
$pageSlug    = $page['slug'] ?? '';
$updatedAt   = $page['updated_at'] ?? '';

// SEO: Seitentitel im <head> aktualisieren (über Output-Buffer nicht möglich nach Header-Output)
// → Seitentitel wird über ThemeManager::getSiteTitle() in header.php ausgegeben.
// Hier setzen wir ihn für künftige Nutzung über eine Hook-Variable.
?>

<main id="main" class="site-main" role="main">
    <div class="container">
        <article class="content-area" style="padding:var(--spacing-lg) 0;">

            <?php if ($pageTitle && trim($pageTitle) !== '') : ?>
                <header class="entry-header">
                    <h1 class="page-title"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
                </header>
            <?php endif; ?>

            <?php if ($pageContent && trim($pageContent) !== '') : ?>
                <div class="page-content entry-content">
                    <?php
                    // Inhalt mit erlaubten Tags ausgeben (kB safe HTML)
                    $allowedTagsStr = '<p><br><strong><b><em><i><u><s>'
                        . '<h1><h2><h3><h4><h5><h6>'
                        . '<ul><ol><li>'
                        . '<a><img>'
                        . '<blockquote><pre><code>'
                        . '<table><thead><tbody><tr><th><td>'
                        . '<div><span><section><article><aside>'
                        . '<hr><figure><figcaption>';
                    echo strip_tags($pageContent, $allowedTagsStr);
                    ?>
                </div>
            <?php else : ?>
                <div class="page-content" style="color:#666;font-style:italic;">
                    <p>Diese Seite enthält noch keinen Inhalt.</p>
                </div>
            <?php endif; ?>

            <?php if ($updatedAt && trim($updatedAt) !== '') : ?>
                <footer class="entry-footer" style="margin-top:var(--spacing-lg);padding-top:var(--spacing-md);border-top:1px solid var(--border-color);">
                    <span class="entry-meta" style="font-size:0.8rem;color:#888;">
                        Zuletzt aktualisiert: <?php echo htmlspecialchars(date('d.m.Y', strtotime($updatedAt)), ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </footer>
            <?php endif; ?>

        </article>
    </div>
</main>
