<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="page-wrap<?php echo !$showSidebar ? ' page-wrap--full' : ''; ?>">

<main id="main-content">

<!-- ── Hero Post ──────────────────────────────────────────────────────────── -->
<?php if ($showHero && $heroPost): ?>
<div class="hero-post">
    <div class="hero-image">
        <?php if ($heroPost->featured_image): ?>
        <img src="<?php echo htmlspecialchars($heroPost->featured_image); ?>"
             alt="<?php echo htmlspecialchars($heroPost->title); ?>"
             loading="eager">
        <?php endif; ?>
        <?php if ($heroPost->category_slug): ?>
        <div class="hero-cat-badge"><?php echo htmlspecialchars(strtoupper($heroPost->category_slug ?? '')); ?></div>
        <?php endif; ?>
    </div>
    <div class="hero-body">
        <?php if ($heroPost->category_name): ?>
        <div class="post-cat"><?php echo htmlspecialchars($heroPost->category_name); ?></div>
        <?php endif; ?>
        <h2>
            <a href="<?php echo SITE_URL; ?>/blog/<?php echo htmlspecialchars($heroPost->slug); ?>">
                <?php echo htmlspecialchars($heroTitleOverride ?: $heroPost->title); ?>
            </a>
        </h2>
        <?php if ($heroPost->excerpt): ?>
        <p class="excerpt"><?php echo meridian_excerpt((string)$heroPost->excerpt, 200); ?></p>
        <?php endif; ?>
        <div class="post-meta">
            <?php if ($heroPost->author_name): ?>
            <div class="meta-author">
                <div class="avatar-xs"><?php echo htmlspecialchars(meridian_author_initials($heroPost->author_name)); ?></div>
                <?php echo htmlspecialchars($heroPost->author_name); ?>
            </div>
            <span class="meta-sep">·</span>
            <?php endif; ?>
            <time class="meta-date"><?php echo meridian_format_date($heroPost->published_at ?? $heroPost->created_at); ?></time>
            <?php if (meridian_setting('blog', 'show_reading_time', true)): ?>
            <span class="meta-sep">·</span>
            <span class="meta-read"><?php echo meridian_reading_time($heroPost->content); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($ctaText && $ctaUrl): ?>
        <div class="hero-cta">
            <a href="<?php echo htmlspecialchars($ctaUrl); ?>" class="btn-hero"><?php echo htmlspecialchars($ctaText); ?></a>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── Artikel-Liste ──────────────────────────────────────────────────────── -->
<?php if (!empty($articleList)): ?>
<div class="section-label"><h3>Aktuelle Artikel</h3></div>
<div class="article-list">
    <?php foreach ($articleList as $post): ?>
    <div class="article-row">
        <div class="art-thumb">
            <?php if ($post->featured_image): ?>
            <img src="<?php echo htmlspecialchars($post->featured_image); ?>"
                 alt="<?php echo htmlspecialchars($post->title); ?>"
                 loading="lazy">
            <?php endif; ?>
        </div>
        <div class="art-body">
            <?php if ($post->category_name): ?>
            <div class="art-cat"><?php echo htmlspecialchars($post->category_name); ?></div>
            <?php endif; ?>
            <div class="art-title">
                <a href="<?php echo SITE_URL; ?>/blog/<?php echo htmlspecialchars($post->slug); ?>">
                    <?php echo htmlspecialchars($post->title); ?>
                </a>
            </div>
            <?php $artExcerpt = meridian_excerpt((string)($post->excerpt ?: $post->content), 120); ?>
            <?php if ($artExcerpt): ?>
            <div class="art-excerpt"><?php echo $artExcerpt; ?></div>
            <?php endif; ?>
            <div class="art-meta">
                <time><?php echo meridian_format_date($post->published_at ?? $post->created_at); ?></time>
                <?php if (meridian_setting('blog', 'show_reading_time', true)): ?>
                <span class="dot"></span>
                <span class="read-t"><?php echo meridian_reading_time($post->content); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Card-Grid ────────────────────────────────────────────────────────── -->
<?php if (!empty($cardPosts)): ?>
<div class="section-label"><h3>Weitere Artikel</h3></div>
<div class="card-grid">
    <?php foreach ($cardPosts as $post): ?>
    <div class="card">
        <div class="card-thumb" style="background:<?php echo meridian_cat_gradient($post->category_name ?? ''); ?>">
            <?php if ($post->featured_image): ?>
            <img src="<?php echo htmlspecialchars($post->featured_image); ?>"
                 alt="<?php echo htmlspecialchars($post->title); ?>"
                 loading="lazy">
            <?php endif; ?>
            <?php if ($post->category_name): ?>
            <span class="card-cat"><?php echo htmlspecialchars($post->category_name); ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <h4>
                <a href="<?php echo SITE_URL; ?>/blog/<?php echo htmlspecialchars($post->slug); ?>">
                    <?php echo htmlspecialchars($post->title); ?>
                </a>
            </h4>
            <p><?php echo meridian_excerpt((string)($post->excerpt ?: $post->content), 100); ?></p>
            <div class="card-footer">
                <time><?php echo meridian_format_date($post->published_at ?? $post->created_at, true); ?></time>
                <a href="<?php echo SITE_URL; ?>/blog/<?php echo htmlspecialchars($post->slug); ?>" class="read-link">Lesen →</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Feature-Row ───────────────────────────────────────────────────────── -->
<?php if (!empty($featurePosts)): ?>
<div class="section-label"><h3>Schwerpunkte</h3></div>
<div class="feature-row">
    <?php foreach ($featurePosts as $post): ?>
    <div class="feature-box">
        <h3>
            <a href="<?php echo SITE_URL; ?>/blog/<?php echo htmlspecialchars($post->slug); ?>">
                <?php echo htmlspecialchars($post->title); ?>
            </a>
        </h3>
        <p><?php echo meridian_excerpt((string)($post->excerpt ?: $post->content), 120); ?></p>
        <a href="<?php echo SITE_URL; ?>/blog/<?php echo htmlspecialchars($post->slug); ?>" class="feature-link">
            <?php echo htmlspecialchars($post->category_name ?: 'Weiterlesen'); ?> →
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!$heroPost && empty($articleList)): ?>
<div class="empty-state">
    <p style="font-size:3rem;margin:0">📰</p>
    <p><strong>Noch keine Artikel veröffentlicht</strong></p>
    <p>Die ersten Artikel erscheinen hier, sobald sie veröffentlicht werden.</p>
</div>
<?php endif; ?>

</main>

<!-- ── Sidebar ────────────────────────────────────────────────────────────── -->
<?php if ($showSidebar): ?>
<aside class="sidebar" aria-label="Sidebar">

    <!-- Newsletter Widget -->
    <div class="newsletter-widget">
        <div class="widget-title">Newsletter</div>
        <h3>Kein Artikel verpassen</h3>
        <p>Die besten Artikel direkt in dein Postfach – kostenlos.</p>
        <form action="<?php echo htmlspecialchars(meridian_auth_url('register'), ENT_QUOTES, 'UTF-8'); ?>" method="GET">
            <input type="email" name="email" placeholder="deine@email.de" required autocomplete="email">
            <button type="submit">Jetzt abonnieren →</button>
        </form>
    </div>

    <!-- Kategorien -->
    <?php if (!empty($sidebarCats)): ?>
    <div>
        <div class="widget-title">Kategorien</div>
        <?php foreach ($sidebarCats as $cat): ?>
        <div class="cat-row">
            <a href="<?php echo SITE_URL . '/blog?category=' . urlencode($cat['slug'] ?? ''); ?>">
                <?php echo htmlspecialchars($cat['name']); ?>
            </a>
            <?php if (!empty($cat['post_count'])): ?>
            <span class="cat-count"><?php echo (int)$cat['post_count']; ?></span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Zuletzt erschienen -->
    <?php if (!empty($recentSidebar)): ?>
    <div>
        <div class="widget-title">Zuletzt erschienen</div>
        <?php foreach ($recentSidebar as $i => $recent):
            $rArr = is_object($recent) ? (array)$recent : (array)$recent;
        ?>
        <div class="recent-item">
            <div class="recent-num"><?php echo str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT); ?></div>
            <div class="recent-body">
                <?php if (!empty($rArr['category_name'])): ?>
                <div class="rcat"><?php echo htmlspecialchars($rArr['category_name']); ?></div>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>/blog/<?php echo htmlspecialchars($rArr['slug'] ?? ''); ?>">
                    <?php echo htmlspecialchars($rArr['title'] ?? ''); ?>
                </a>
                <time><?php echo meridian_format_date($rArr['published_at'] ?? $rArr['created_at'] ?? '', true); ?></time>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Tag Cloud -->
    <?php if (!empty($tagCloud)): ?>
    <div>
        <div class="widget-title">Tags</div>
        <div class="tag-cloud">
            <?php foreach ($tagCloud as $tag): ?>
            <a href="<?php echo SITE_URL . '/search?q=' . urlencode($tag); ?>">
                <?php echo htmlspecialchars($tag); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</aside>
<?php endif; ?>

</div><!-- /.page-wrap -->
