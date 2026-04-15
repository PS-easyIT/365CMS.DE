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
$pTitleRaw = trim((string) ($post->title ?? ''));
$pTitle = htmlspecialchars($pTitleRaw, ENT_QUOTES, 'UTF-8');
$pLink = rtrim((string) SITE_URL, '/') . '/blog/' . ltrim((string) ($post->slug ?? ''), '/');
$pContent = (string) ($post->content ?? '');
$pExcerptRaw = trim((string) ($post->excerpt ?? ''));
$pExcerpt = $pExcerptRaw !== '' ? htmlspecialchars($pExcerptRaw, ENT_QUOTES, 'UTF-8') : '';
$pPublishedAt = (string) ($post->published_at ?? $post->created_at ?? '');
$pDate = $pPublishedAt !== '' ? meridian_format_date($pPublishedAt, false) : '';
$pDateAgo = $pPublishedAt !== '' ? time_ago($pPublishedAt) : '';
$pAuthor = trim((string) ($post->author_name ?? 'Redaktion'));
$pAuthIni = meridian_author_initials($pAuthor);
$pCat = trim((string) ($post->category_name ?? 'Blog'));
$pCatSlug = trim((string) ($post->category_slug ?? 'blog'));
$pReadMinutes = !empty($post->read_time) ? max(1, (int) $post->read_time) : meridian_reading_time($pContent);
$pRead = $pReadMinutes . ' Min. Lesezeit';
$pTags = $post->tags ?? [];
$pFeaturedImageReference = (string) ($post->featured_image ?? '');
$pFeaturedImage = function_exists('meridian_get_picture_sources')
  ? meridian_get_picture_sources($pFeaturedImageReference, null, 1600, 900)
  : ['url' => '', 'webp_url' => '', 'width' => 1600, 'height' => 900];
$pFeaturedImageDimensions = function_exists('meridian_image_dimension_attributes')
  ? meridian_image_dimension_attributes($pFeaturedImageReference, 1600, 900)
  : 'width="1600" height="900"';
$pFeaturedLoading = function_exists('meridian_image_loading_attributes')
  ? meridian_image_loading_attributes(true)
  : 'loading="eager" decoding="async"';
$pViews = max(0, (int) ($post->views ?? 0));

if (is_string($pTags)) {
  $pTags = meridian_post_tags($pTags);
} elseif (is_array($pTags)) {
  $pTags = array_values(array_filter(array_map(static fn(mixed $tag): string => trim((string) $tag), $pTags)));
} else {
  $pTags = [];
}

$showRelated  = (bool) meridian_setting('blog', 'show_related_posts', true);
$showComments = (bool) meridian_setting('blog', 'show_comments', true);
$showViews    = (bool) meridian_setting('blog', 'show_views', false);

// Verwandte Posts: korrekte Signatur (categoryId, excludeId, limit)
$relatedPosts = ($showRelated && function_exists('meridian_get_related_posts'))
    ? meridian_get_related_posts((int)($post->category_id ?? 0), (int)($post->id ?? 0), 3)
    : [];
?>

<article class="post-detail">

  <div class="breadcrumb-bar">
    <nav class="breadcrumb-inner" aria-label="Breadcrumb">
      <a href="<?php echo SITE_URL; ?>/">Startseite</a><span class="sep">›</span>
      <a href="<?php echo SITE_URL; ?>/blog">Blog</a><span class="sep">›</span>
      <a href="<?php echo SITE_URL; ?>/blog?category=<?php echo urlencode($pCatSlug); ?>"><?php echo htmlspecialchars($pCat, ENT_QUOTES, 'UTF-8'); ?></a><span class="sep">›</span>
      <span class="cur"><?php echo $pTitle; ?></span>
    </nav>
  </div>

  <div class="post-detail-shell">
    <header class="post-hero">
      <div class="post-hero__intro">
        <div class="post-hero__eyebrow">
          <a href="<?php echo SITE_URL; ?>/blog?category=<?php echo urlencode($pCatSlug); ?>" class="post-hero__category"><?php echo htmlspecialchars($pCat, ENT_QUOTES, 'UTF-8'); ?></a>
          <span class="post-hero__sep">•</span>
          <span class="post-hero__reading"><?php echo htmlspecialchars($pRead, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>

        <h1 class="post-hero__title"><?php echo $pTitle; ?></h1>

        <?php if ($pExcerpt !== ''): ?>
        <p class="post-hero__excerpt"><?php echo $pExcerpt; ?></p>
        <?php endif; ?>

        <div class="post-hero__meta">
          <div class="author-chip">
            <div class="avatar-sm"><?php echo htmlspecialchars($pAuthIni, ENT_QUOTES, 'UTF-8'); ?></div>
            <div>
              <div class="name"><?php echo htmlspecialchars($pAuthor, ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="role">Redaktion &amp; Autorenprofil</div>
            </div>
          </div>

          <div class="post-hero__meta-list">
            <?php if ($pDate !== ''): ?>
            <span><strong>Veröffentlicht</strong> <?php echo htmlspecialchars($pDate, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
            <?php if ($pDateAgo !== ''): ?>
            <span><?php echo htmlspecialchars($pDateAgo, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
            <?php if ($showViews && $pViews > 0): ?>
            <span><?php echo number_format($pViews, 0, ',', '.'); ?> Aufrufe</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <aside class="post-stat-card" aria-label="Artikeldetails">
        <div class="post-stat-card__item">
          <span class="post-stat-card__label">Kategorie</span>
          <span class="post-stat-card__value"><?php echo htmlspecialchars($pCat, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="post-stat-card__item">
          <span class="post-stat-card__label">Lesezeit</span>
          <span class="post-stat-card__value"><?php echo htmlspecialchars($pRead, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <?php if ($pDate !== ''): ?>
        <div class="post-stat-card__item">
          <span class="post-stat-card__label">Stand</span>
          <span class="post-stat-card__value"><?php echo htmlspecialchars($pDate, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <?php endif; ?>
      </aside>
    </header>

    <?php if ($pFeaturedImage['url'] !== ''): ?>
    <figure class="post-featured-figure">
      <picture>
        <?php if ($pFeaturedImage['webp_url'] !== ''): ?>
        <source srcset="<?php echo htmlspecialchars($pFeaturedImage['webp_url'], ENT_QUOTES, 'UTF-8'); ?>" type="image/webp">
        <?php endif; ?>
        <img src="<?php echo htmlspecialchars($pFeaturedImage['url'], ENT_QUOTES, 'UTF-8'); ?>"
             alt="<?php echo $pTitle; ?>"
             <?php echo $pFeaturedLoading; ?>
             <?php echo $pFeaturedImageDimensions; ?>>
      </picture>
    </figure>
    <?php endif; ?>

    <div class="post-body post-body--detail">
      <?php echo $pContent; // Content is trusted ?>
    </div>

    <?php if (!empty($pTags)): ?>
    <div class="post-footer-bar post-footer-bar--detail">
      <span class="pf-label">Schlagwörter</span>
      <div class="pf-tags">
        <?php foreach ($pTags as $tag): ?>
        <a href="<?php echo SITE_URL; ?>/blog?tag=<?php echo urlencode((string) $tag); ?>"><?php echo htmlspecialchars((string) $tag, ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="author-box-wrap">
      <div class="author-box author-box--elevated">
        <div class="author-avatar-lg"><?php echo htmlspecialchars($pAuthIni, ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="author-box-info">
          <div class="label">Über den Autor</div>
          <h4><?php echo htmlspecialchars($pAuthor, ENT_QUOTES, 'UTF-8'); ?></h4>
          <div class="arole">Redaktion · Schwerpunkt <?php echo htmlspecialchars($pCat, ENT_QUOTES, 'UTF-8'); ?></div>
          <p>Konzipiert, verdichtet und veröffentlicht Beiträge mit Fokus auf nachvollziehbare Praxis, klare Struktur und einen brauchbaren Erkenntnisgewinn statt Deko-Textwüste.</p>
        </div>
      </div>
    </div>

    <?php if ($showRelated && !empty($relatedPosts)): ?>
    <section class="related-section" aria-labelledby="related-posts-heading">
      <div class="section-label"><h3 id="related-posts-heading">Verwandte Artikel</h3></div>
      <div class="related-grid">
        <?php foreach ($relatedPosts as $rp): $rp = (array) $rp;
            $rTitleRaw = trim((string) ($rp['title'] ?? ''));
            $rTitle = htmlspecialchars($rTitleRaw, ENT_QUOTES, 'UTF-8');
            $rLink = SITE_URL . '/blog/' . ltrim((string) ($rp['slug'] ?? ''), '/');
            $rCat = trim((string) ($rp['category_name'] ?? 'Tipp'));
            $rDate = !empty($rp['published_at'] ?? $rp['created_at'] ?? '')
                ? meridian_format_date((string) ($rp['published_at'] ?? $rp['created_at']), true)
                : '';
            $rExcerptRaw = trim((string) ($rp['excerpt'] ?? ''));
            $rExcerpt = $rExcerptRaw !== ''
                ? htmlspecialchars($rExcerptRaw, ENT_QUOTES, 'UTF-8')
                : 'Mehr aus diesem Themenfeld lesen';
            $rImageRef = (string) ($rp['featured_image'] ?? '');
            $rPicture = function_exists('meridian_get_picture_sources')
                ? meridian_get_picture_sources($rImageRef, null, 720, 405)
                : ['url' => '', 'webp_url' => '', 'width' => 720, 'height' => 405];
            $rImageDimensions = function_exists('meridian_image_dimension_attributes')
                ? meridian_image_dimension_attributes($rImageRef, 720, 405)
                : 'width="720" height="405"';
        ?>
        <article class="related-card related-card--detailed">
          <?php if ($rPicture['url'] !== ''): ?>
          <a href="<?php echo htmlspecialchars($rLink, ENT_QUOTES, 'UTF-8'); ?>" class="related-card__media">
            <picture>
              <?php if ($rPicture['webp_url'] !== ''): ?>
              <source srcset="<?php echo htmlspecialchars($rPicture['webp_url'], ENT_QUOTES, 'UTF-8'); ?>" type="image/webp">
              <?php endif; ?>
              <img src="<?php echo htmlspecialchars($rPicture['url'], ENT_QUOTES, 'UTF-8'); ?>"
                   alt="<?php echo $rTitle; ?>"
                   <?php echo function_exists('meridian_image_loading_attributes') ? meridian_image_loading_attributes(false) : 'loading="lazy" decoding="async"'; ?>
                   <?php echo $rImageDimensions; ?>>
            </picture>
          </a>
          <?php else: ?>
          <a href="<?php echo htmlspecialchars($rLink, ENT_QUOTES, 'UTF-8'); ?>" class="related-card__media related-card__media--placeholder">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </a>
          <?php endif; ?>
          <div class="rc-body">
            <div class="rc-cat"><?php echo htmlspecialchars($rCat, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="rc-title"><a href="<?php echo htmlspecialchars($rLink, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $rTitle; ?></a></div>
            <p class="rc-excerpt"><?php echo $rExcerpt; ?></p>
            <?php if ($rDate !== ''): ?><div class="rc-meta"><?php echo htmlspecialchars($rDate, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($showComments): ?>
    <section class="comments-section" aria-labelledby="comments-heading">
      <div class="section-label"><h3 id="comments-heading">Kommentare</h3></div>
      <div class="comment-form-wrap">
        <h4>Einen Kommentar hinterlassen</h4>
        <?php
          $commentCsrf = '';
          if (class_exists('\\CMS\\Security')) {
              $commentCsrf = \CMS\Security::instance()->generateToken('comment_' . ($post->id ?? 0));
          }
        ?>
        <form method="post" action="<?php echo SITE_URL; ?>/comments/post">
           <input type="hidden" name="post_id" value="<?php echo (int) ($post->id ?? 0); ?>">
           <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($commentCsrf, ENT_QUOTES, 'UTF-8'); ?>">
           <div class="form-2col">
              <div class="form-field"><label>Name <span class="form-required">*</span></label><input type="text" name="author" placeholder="Dein Name" required maxlength="100"></div>
              <div class="form-field"><label>E-Mail <span class="form-required">*</span></label><input type="email" name="email" placeholder="deine@email.de" required maxlength="200"></div>
              <div class="form-field form-full"><label>Kommentar <span class="form-required">*</span></label><textarea name="comment" placeholder="Dein Kommentar …" required maxlength="2000"></textarea></div>
           </div>
           <button type="submit" class="btn-submit">Kommentar absenden →</button>
        </form>
      </div>
    </section>
    <?php endif; ?>
  </div>

</article>

