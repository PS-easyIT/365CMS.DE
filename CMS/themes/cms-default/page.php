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

$pageTitle     = $page['title']      ?? '';
$pageContent   = $page['content']    ?? '';
$pageId        = (int)($page['id']   ?? 0);
$pageHideTitle = !empty($page['hide_title']);
?>

<div class="view-post" style="display:block;">
  <div class="post-column">

    <?php if (!$pageHideTitle && $pageTitle !== ''): ?>
    <div class="post-header">
      <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>
    <?php endif; ?>

    <div class="post-body page-content">
      <?php echo $pageContent; // Kommt aus DB, bereits sanitiert ?>
    </div>

  </div><!-- /.post-column -->
</div><!-- /.view-post -->
