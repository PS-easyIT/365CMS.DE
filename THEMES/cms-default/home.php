<?php
/**
 * Meridian CMS Default – Homepage Template
 *
 * Hinweis: Der Router übergibt bei der Home-Route KEINE $data,
 * daher werden alle Beiträge hier direkt via Helpers abgefragt.
 *
 * @package CMSv2\Themes\CmsDefault
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// ── Daten laden ──────────────────────────────────────────────────────────────
$db   = \CMS\Database::instance();
$pdo  = $db->getConnection();

// Hero-Post (neuester veröffentlichter Beitrag mit Bild, falls vorhanden)
$heroPost = null;
try {
    $stmt = $pdo->prepare(
        "SELECT p.*, u.username AS author_name, c.name AS category_name, c.slug AS category_slug
         FROM posts p
         LEFT JOIN users u ON p.author_id = u.id
         LEFT JOIN post_categories c ON p.category_id = c.id
         WHERE p.status = 'published'
         ORDER BY p.published_at DESC
         LIMIT 1"
    );
    $stmt->execute();
    $heroPost = $stmt->fetch(\PDO::FETCH_OBJ);
} catch (\Exception $e) {
    // Tabelle existiert noch nicht oder leer → kein Hero-Post
}

// Artikel-Liste (nächste 5 Beiträge)
$articleList = [];
try {
    $excludeId = $heroPost ? (int)$heroPost->id : 0;
    $stmt = $pdo->prepare(
        "SELECT p.*, u.username AS author_name, c.name AS category_name, c.slug AS category_slug
         FROM posts p
         LEFT JOIN users u ON p.author_id = u.id
         LEFT JOIN post_categories c ON p.category_id = c.id
         WHERE p.status = 'published' AND p.id != :exclude
         ORDER BY p.published_at DESC
         LIMIT 5"
    );
    $stmt->execute([':exclude' => $excludeId]);
    $articleList = $stmt->fetchAll(\PDO::FETCH_OBJ);
} catch (\Exception $e) {
    $articleList = [];
}

// Card-Grid (weitere 3 Beiträge)
$cardPosts = [];
try {
    $excludeIds = array_merge(
        $heroPost ? [(int)$heroPost->id] : [],
        array_map(fn($p) => (int)$p->id, $articleList)
    );
    $placeholders = $excludeIds ? implode(',', array_fill(0, count($excludeIds), '?')) : '0';
    $stmt = $pdo->prepare(
        "SELECT p.*, u.username AS author_name, c.name AS category_name, c.slug AS category_slug
         FROM posts p
         LEFT JOIN users u ON p.author_id = u.id
         LEFT JOIN post_categories c ON p.category_id = c.id
         WHERE p.status = 'published' AND p.id NOT IN ($placeholders)
         ORDER BY p.views DESC
         LIMIT 3"
    );
    $stmt->execute($excludeIds ?: [0]);
    $cardPosts = $stmt->fetchAll(\PDO::FETCH_OBJ);
} catch (\Exception $e) {
    $cardPosts = [];
}

// Feature-Row (2 weitere Beiträge)
$featurePosts = [];
try {
    $allExclude = array_merge(
        $heroPost ? [(int)$heroPost->id] : [],
        array_map(fn($p) => (int)$p->id, $articleList),
        array_map(fn($p) => (int)$p->id, $cardPosts)
    );
    $placeholders = implode(',', array_fill(0, count($allExclude), '?'));
    $stmt = $pdo->prepare(
        "SELECT p.*, u.username AS author_name, c.name AS category_name, c.slug AS category_slug
         FROM posts p
         LEFT JOIN users u ON p.author_id = u.id
         LEFT JOIN post_categories c ON p.category_id = c.id
         WHERE p.status = 'published' AND p.id NOT IN ($placeholders)
         ORDER BY p.published_at DESC
         LIMIT 2"
    );
    $stmt->execute($allExclude ?: [0]);
    $featurePosts = $stmt->fetchAll(\PDO::FETCH_OBJ);
} catch (\Exception $e) {
    $featurePosts = [];
}

// Sidebar: aktuelle Beiträge
$recentSidebar = meridian_get_recent_posts(5, $heroPost ? (int)$heroPost->id : 0);

// Sidebar: Kategorien
$sidebarCats = meridian_get_categories(8);

// Sidebar: Tags sammeln
$tagCloud = [];
try {
    $stmt = $pdo->query("SELECT tags FROM posts WHERE status = 'published' AND tags IS NOT NULL AND tags != ''");
    $tagRows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    $tagCounts = [];
    foreach ($tagRows as $row) {
        foreach (array_map('trim', explode(',', $row)) as $tag) {
            if ($tag) {
                $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
            }
        }
    }
    arsort($tagCounts);
    $tagCloud = array_keys(array_slice($tagCounts, 0, 20));
} catch (\Exception $e) {
    $tagCloud = [];
}

$showSidebar      = (bool) meridian_setting('layout',   'show_sidebar',        true);
$showHero         = (bool) meridian_setting('blog',     'show_hero_post',      true)
                  && (bool) meridian_setting('homepage', 'homepage_show_hero',  true);
$homepageMode     = (string) meridian_setting('homepage', 'homepage_mode',        'posts');
$heroTitleOverride = (string) meridian_setting('homepage', 'homepage_hero_title',  '');
$ctaText          = (string) meridian_setting('homepage', 'homepage_cta_text',    '');
$ctaUrl           = (string) meridian_setting('homepage', 'homepage_cta_url',     '');
$numRecent        = count($recentSidebar);
?>
<?php if ($homepageMode === 'landing'): ?>
<!-- ══════════════════════════════════════════════════════════
     LANDING PAGE MODUS  –  Inhalte via LandingPageService
══════════════════════════════════════════════════════════ -->
<?php
// ── Landing Page Daten laden ──────────────────────────────
$lpHeader      = [];
$lpFeatures    = [];
$lpColors      = [];
$lpContentSets = ['content_type' => 'features', 'posts_count' => 6];

try {
    $landingSvc    = \CMS\Services\LandingPageService::getInstance();
    $lpHeader      = $landingSvc->getHeader();
    $lpFeatures    = $landingSvc->getFeatures();
    $lpColors      = $lpHeader['colors'] ?? [];
    $lpContentSets = $landingSvc->getContentSettings();
} catch (\Throwable $lpEx) {
    error_log('cms-default home.php LandingPageService: ' . $lpEx->getMessage());
}

// ── Titel: Customizer-Override → LP-Titel → SITE_NAME ────
$lpTitle    = $heroTitleOverride
            ?: ($lpHeader['title'] ?? (defined('SITE_NAME') ? SITE_NAME : ''));
$lpSubtitle = $lpHeader['subtitle']    ?? '';
$lpDesc     = $lpHeader['description'] ?? '';
$lpLogo     = $lpHeader['logo']        ?? '';

// ── CTA: Customizer-Override → erster LP-Button ───────────
$lpCtaLabel = $ctaText;
$lpCtaHref  = $ctaUrl;
if (empty($lpCtaLabel)) {
    $lpBtns = $lpHeader['header_buttons'] ?? [];
    if (is_string($lpBtns)) {
        $lpBtns = @json_decode($lpBtns, true) ?? [];
    }
    if (!empty($lpBtns[0])) {
        $lpCtaLabel = $lpBtns[0]['label'] ?? '';
        $lpCtaHref  = $lpBtns[0]['url']   ?? '';
    }
}

// ── Farben mit Defaults ───────────────────────────────────
$safe = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

$heroGradStart = $safe($lpColors['hero_gradient_start'] ?? '#1a2744');
$heroGradEnd   = $safe($lpColors['hero_gradient_end']   ?? '#0c1526');
$heroBorderCol = $safe($lpColors['hero_border']          ?? '#3b82f6');
$heroTextColor = $safe($lpColors['hero_text']            ?? '#ffffff');
$featuresBgCol = $safe($lpColors['features_bg']          ?? '#f8fafc');
$btnPrimary    = $safe($lpColors['primary_button']       ?? '#3b82f6');
?>
<style>
.lp-hero     { --lp-grad-start:<?php echo $heroGradStart;?>; --lp-grad-end:<?php echo $heroGradEnd;?>; --lp-border:<?php echo $heroBorderCol;?>; --lp-text:<?php echo $heroTextColor;?>; --lp-btn:<?php echo $btnPrimary;?>; }
.lp-features { --lp-features-bg:<?php echo $featuresBgCol;?>; }
</style>

<div class="landing-page">
<main id="main-content">

    <!-- ── Hero ──────────────────────────────────────────── -->
    <section class="lp-hero" style="
        background:linear-gradient(135deg,var(--lp-grad-start) 0%,var(--lp-grad-end) 100%);
        border-bottom:3px solid var(--lp-border);">
        <div class="lp-hero__inner">

            <?php if (!empty($lpLogo)): ?>
            <img src="<?php echo $safe($lpLogo); ?>"
                 alt="<?php echo $safe($lpTitle); ?>"
                 style="max-height:80px;margin:0 auto 1.5rem;display:block;">
            <?php endif; ?>

            <h1 class="lp-hero__title" style="color:var(--lp-text);">
                <?php echo $safe($lpTitle); ?>
            </h1>

            <?php if ($lpSubtitle): ?>
            <p style="color:var(--lp-text);opacity:.85;font-size:1.25rem;max-width:600px;margin:0 auto 1rem;">
                <?php echo $safe($lpSubtitle); ?>
            </p>
            <?php endif; ?>

            <?php if ($lpDesc && $lpDesc !== $lpTitle): ?>
            <p style="color:var(--lp-text);opacity:.75;max-width:680px;margin:0 auto 1.5rem;font-size:1rem;">
                <?php echo $safe($lpDesc); ?>
            </p>
            <?php endif; ?>

            <?php if ($lpCtaLabel && $lpCtaHref): ?>
            <div class="lp-hero__actions">
                <a href="<?php echo $safe($lpCtaHref); ?>"
                   class="btn-hero"
                   style="background:var(--lp-btn);">
                    <?php echo $safe($lpCtaLabel); ?>
                </a>
                <?php if (!theme_is_logged_in()): ?>
                <a href="<?php echo $safe(SITE_URL . '/register'); ?>"
                   class="btn-hero btn-hero--outline">
                    Registrieren →
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>
    </section>

    <!-- ── Hauptinhalt (Features / Artikel / Text) ────────── -->
    <?php
    $lpContentType = $lpContentSets['content_type'] ?? 'features';
    $lpPostsCount  = max(1, (int)($lpContentSets['posts_count'] ?? 6));
    ?>

    <?php if ($lpContentType === 'features' && !empty($lpFeatures)): ?>
    <!-- Feature-Karten aus dem Landing-Page-Admin -->
    <section class="lp-features" style="padding:3rem 0;background:var(--lp-features-bg);">
        <div style="max-width:1140px;margin:0 auto;padding:0 1.5rem;">
            <?php
            $featSectionTitle = $lpHeader['features_title'] ?? '';
            if ($featSectionTitle): ?>
            <div class="section-label" style="text-align:center;margin-bottom:2rem;">
                <h2><?php echo $safe($featSectionTitle); ?></h2>
            </div>
            <?php endif; ?>
            <div class="card-grid">
                <?php foreach ($lpFeatures as $feat):
                    $fTitle = $feat['title'] ?? '';
                    $fText  = $feat['text']  ?? '';
                    $fIcon  = $feat['icon']  ?? '';
                    if (empty($fTitle) && empty($fText)) continue;
                ?>
                <div class="card">
                    <div class="card-body">
                        <?php if ($fIcon): ?>
                        <div style="font-size:2rem;margin-bottom:.5rem;"><?php echo $safe($fIcon); ?></div>
                        <?php endif; ?>
                        <?php if ($fTitle): ?><h4><?php echo $safe($fTitle); ?></h4><?php endif; ?>
                        <?php if ($fText):  ?><p><?php echo $safe($fText); ?></p><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php elseif ($lpContentType === 'posts'): ?>
    <!-- Neueste Artikel (Anzahl aus Landing-Page-Admin) -->
    <?php
    $lpPosts = [];
    try {
        $stmtLp = $pdo->prepare(
            "SELECT p.*, u.username AS author_name, c.name AS category_name, c.slug AS category_slug
             FROM posts p
             LEFT JOIN users u ON p.author_id = u.id
             LEFT JOIN post_categories c ON p.category_id = c.id
             WHERE p.status = 'published'
             ORDER BY p.published_at DESC
             LIMIT " . $lpPostsCount
        );
        $stmtLp->execute();
        $lpPosts = $stmtLp->fetchAll(\PDO::FETCH_OBJ);
    } catch (\Exception $e) { $lpPosts = []; }
    ?>
    <?php if (!empty($lpPosts)): ?>
    <div class="lp-posts-section">
        <div class="section-label"><h3>Aktuelle Beiträge</h3></div>
        <div class="card-grid">
            <?php foreach ($lpPosts as $post): ?>
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
                    <h4><a href="<?php echo SITE_URL; ?>/blog/<?php echo htmlspecialchars($post->slug); ?>"><?php echo htmlspecialchars($post->title); ?></a></h4>
                    <p><?php echo htmlspecialchars(meridian_excerpt($post->excerpt ?: $post->content, 100)); ?></p>
                    <div class="card-footer">
                        <time><?php echo meridian_format_date($post->published_at ?? $post->created_at, true); ?></time>
                        <a href="<?php echo SITE_URL; ?>/blog/<?php echo htmlspecialchars($post->slug); ?>" class="read-link">Lesen →</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="empty-state" style="padding:3rem 0;">
        <p style="font-size:3rem;margin:0">📰</p>
        <p><strong>Noch keine Artikel veröffentlicht</strong></p>
    </div>
    <?php endif; ?>

    <?php elseif ($lpContentType === 'text'): ?>
    <!-- Freitext aus der Landing Page -->
    <?php if ($lpDesc): ?>
    <section style="max-width:860px;margin:3rem auto;padding:0 1.5rem;">
        <div style="font-size:1.1rem;line-height:1.7;color:#1e293b;">
            <?php echo nl2br(htmlspecialchars($lpDesc)); ?>
        </div>
    </section>
    <?php endif; ?>

    <?php elseif (empty($lpFeatures)): ?>
    <!-- Fallback: Keine LP-Inhalte konfiguriert -->
    <div class="empty-state" style="padding:3rem 0;">
        <p style="font-size:3rem;margin:0">🏗️</p>
        <p><strong>Startseite noch nicht konfiguriert</strong></p>
        <p class="text-muted">Richte die Landing Page unter <a href="<?php echo SITE_URL; ?>/admin/landing-page.php">Admin → Landing Page</a> ein.</p>
    </div>
    <?php endif; ?>

</main>
</div><!-- /.landing-page -->

<?php else: /* ── BLOG MODUS (Standard) ── */ ?>
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
        <p class="excerpt"><?php echo htmlspecialchars(meridian_excerpt($heroPost->excerpt, 200)); ?></p>
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
            <?php $artExcerpt = $post->excerpt ?: meridian_excerpt($post->content, 120); ?>
            <?php if ($artExcerpt): ?>
            <div class="art-excerpt"><?php echo htmlspecialchars($artExcerpt); ?></div>
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
            <p><?php echo htmlspecialchars(meridian_excerpt($post->excerpt ?: $post->content, 100)); ?></p>
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
        <p><?php echo htmlspecialchars(meridian_excerpt($post->excerpt ?: $post->content, 120)); ?></p>
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
        <form action="<?php echo SITE_URL; ?>/register" method="GET">
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
<?php endif; /* Ende: homepage mode */ ?>
