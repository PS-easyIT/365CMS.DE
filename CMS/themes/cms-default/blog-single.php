<?php
/**
 * Meridian CMS Default – Single Post Template
 *
 * @package CMSv2\Themes\CmsDefault
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (empty($post)) {
    header('Location: ' . SITE_URL . '/blog');
    exit;
}

// Prepare variables
$pTitle   = htmlspecialchars($post->title ?? '');
$pLink    = SITE_URL . '/blog/' . ($post->slug ?? '');
$pContent = $post->content ?? '';
$pExcerpt = meridian_excerpt((string)($post->excerpt ?? ''), 240);
$pDate    = isset($post->created_at) ? date('d. M Y', strtotime($post->created_at)) : '';
$pAuthor  = $post->author_name ?? 'Redaktion';
$pAuthIni = substr($pAuthor, 0, 2);
$pCat     = $post->category_name ?? 'Blog';
$pCatSlug = $post->category_slug ?? 'blog';
$pRead    = isset($post->read_time) ? $post->read_time . ' Min.' : '5 Min.';
$pTags    = $post->tags ?? []; // Assume array or comma string

if (is_string($pTags)) {
    $pTags = array_filter(array_map('trim', explode(',', $pTags)));
}

$showRelated  = (bool) meridian_setting('blog', 'show_related_posts', true);
$showComments = (bool) meridian_setting('blog', 'show_comments', true);
$showViews    = (bool) meridian_setting('blog', 'show_views', false);

// Verwandte Posts: korrekte Signatur (categoryId, excludeId, limit)
$relatedPosts = ($showRelated && function_exists('meridian_get_related_posts'))
    ? meridian_get_related_posts((int)($post->category_id ?? 0), (int)($post->id ?? 0), 3)
    : [];
?>

<div class="view-post" style="display:block;">

  <!-- Breadcrumb -->
  <div class="breadcrumb-bar">
    <div class="breadcrumb-inner">
      <a href="<?php echo SITE_URL; ?>/">Startseite</a><span class="sep">›</span>
      <a href="<?php echo SITE_URL; ?>/blog">Blog</a><span class="sep">›</span>
      <a href="<?php echo SITE_URL; ?>/blog?category=<?php echo urlencode($pCatSlug); ?>"><?php echo htmlspecialchars($pCat); ?></a><span class="sep">›</span>
      <span class="cur"><?php echo $pTitle; ?></span>
    </div>
  </div>

  <!-- Post Header -->
  <div class="post-header">
    <div class="p-cat"><?php echo htmlspecialchars($pCat); ?></div>
    <h1><?php echo $pTitle; ?></h1>
    <?php if ($pExcerpt): ?>
    <p class="p-intro"><?php echo $pExcerpt; ?></p>
    <?php endif; ?>
    <div class="p-meta">
      <div class="author-chip">
        <div class="avatar-sm"><?php echo $pAuthIni; ?></div>
        <div>
          <div class="name"><?php echo htmlspecialchars($pAuthor); ?></div>
          <div class="role">Autor</div>
        </div>
      </div>
      <span style="color:var(--ink-ghost);font-size:.7rem;">·</span>
      <time style="font-size:.78rem;color:var(--ink-muted);"><?php echo $pDate; ?></time>
      <span style="color:var(--ink-ghost);font-size:.7rem;">·</span>
      <span style="font-size:.78rem;color:var(--ink-ghost);"><?php echo $pRead; ?> Lesezeit</span>
    </div>
    <?php if ($showViews && isset($post->views) && (int)$post->views > 0): ?>
    <div class="p-meta" style="margin-top:.25rem;">
      <span style="font-size:.75rem;color:var(--ink-ghost);">??? <?php echo number_format((int)$post->views); ?> Aufrufe</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Post Body -->
  <div class="post-body">
    <?php echo $pContent; // Content is trusted ?>
  </div>

  <!-- Post Footer: Tags -->
  <?php if (!empty($pTags)): ?>
  <div class="post-footer-bar">
    <span class="pf-label">Tags</span>
    <div class="pf-tags">
      <?php foreach ($pTags as $tag): ?>
      <a href="<?php echo SITE_URL; ?>/blog?tag=<?php echo urlencode($tag); ?>"><?php echo htmlspecialchars($tag); ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Autor-Box -->
  <div class="author-box-wrap">
    <div class="author-box">
      <div class="author-avatar-lg"><?php echo $pAuthIni; ?></div>
      <div class="author-box-info">
        <div class="label">Über den Autor</div>
        <h4><?php echo htmlspecialchars($pAuthor); ?></h4>
        <div class="arole">Redakteur</div>
        <p>Schreibt über <?php echo htmlspecialchars($pCat); ?> und technische Themen.</p>
      </div>
    </div>
  </div>

  <!-- Verwandte Artikel -->
  <?php if ($showRelated && !empty($relatedPosts)): ?>
  <div class="related-section">
    <div class="section-label"><h3>Verwandte Artikel</h3></div>
    <div class="related-grid">
      <?php foreach ($relatedPosts as $rp): $rp = (array)$rp;
          $rTitle = htmlspecialchars($rp['title'] ?? '');
          $rLink  = SITE_URL . '/blog/' . ($rp['slug'] ?? '');
          $rCat   = htmlspecialchars($rp['category_name'] ?? 'Tipp');
      ?>
      <div class="related-card">
        <div class="rc-img" style="background:linear-gradient(135deg,#1e2a3e,#1a1a18);">
          <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <div class="rc-body">
          <div class="rc-cat"><?php echo $rCat; ?></div>
          <div class="rc-title"><a href="<?php echo $rLink; ?>"><?php echo $rTitle; ?></a></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Kommentare -->
  <?php if ($showComments): ?>
  <div class="comments-section">
    <div class="section-label"><h3>Kommentare</h3></div>
    <!-- Comments implementation would go here -->
    <div class="comment-form-wrap">
      <h4>Einen Kommentar hinterlassen</h4>
      <?php
        $commentCsrf = '';
        if (class_exists('\\CMS\\Security')) {
            $commentCsrf = \CMS\Security::instance()->generateToken('comment_' . ($post->id ?? 0));
        }
      ?>
      <form method="post" action="<?php echo SITE_URL; ?>/comments/post">
         <input type="hidden" name="post_id" value="<?php echo (int)($post->id ?? 0); ?>">
         <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($commentCsrf, ENT_QUOTES, 'UTF-8'); ?>">
         <div class="form-2col">
            <div class="form-field"><label>Name <span style="color:#ef4444;">*</span></label><input type="text" name="author" placeholder="Dein Name" required maxlength="100"></div>
            <div class="form-field"><label>E-Mail <span style="color:#ef4444;">*</span></label><input type="email" name="email" placeholder="deine@email.de" required maxlength="200"></div>
            <div class="form-field form-full"><label>Kommentar <span style="color:#ef4444;">*</span></label><textarea name="comment" placeholder="Dein Kommentar …" required maxlength="2000" style="min-height:120px;"></textarea></div>
         </div>
         <button type="submit" class="btn-submit">Kommentar absenden →</button>
      </form>
    </div>
  </div>
  <?php endif; // show_comments ?>

</div>

