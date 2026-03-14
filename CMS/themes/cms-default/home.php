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
$db  = \CMS\Database::instance();
$pdo = $db->getConnection();

// Hero-Post (neuester veröffentlichter Beitrag mit Bild, falls vorhanden)
$heroPost = null;
try {
    $stmt = $pdo->prepare(
        "SELECT p.*, COALESCE(NULLIF(p.author_display_name, ''), NULLIF(u.display_name, ''), NULLIF(u.username, ''), 'Autor') AS author_name, c.name AS category_name, c.slug AS category_slug
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
    $excludeId = $heroPost ? (int) $heroPost->id : 0;
    $stmt = $pdo->prepare(
        "SELECT p.*, COALESCE(NULLIF(p.author_display_name, ''), NULLIF(u.display_name, ''), NULLIF(u.username, ''), 'Autor') AS author_name, c.name AS category_name, c.slug AS category_slug
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
        $heroPost ? [(int) $heroPost->id] : [],
        array_map(fn($post) => (int) $post->id, $articleList)
    );
    $placeholders = $excludeIds ? implode(',', array_fill(0, count($excludeIds), '?')) : '0';
    $stmt = $pdo->prepare(
        "SELECT p.*, COALESCE(NULLIF(p.author_display_name, ''), NULLIF(u.display_name, ''), NULLIF(u.username, ''), 'Autor') AS author_name, c.name AS category_name, c.slug AS category_slug
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
        $heroPost ? [(int) $heroPost->id] : [],
        array_map(fn($post) => (int) $post->id, $articleList),
        array_map(fn($post) => (int) $post->id, $cardPosts)
    );
    $placeholders = implode(',', array_fill(0, count($allExclude), '?'));
    $stmt = $pdo->prepare(
        "SELECT p.*, COALESCE(NULLIF(p.author_display_name, ''), NULLIF(u.display_name, ''), NULLIF(u.username, ''), 'Autor') AS author_name, c.name AS category_name, c.slug AS category_slug
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
$recentSidebar = meridian_get_recent_posts(5, $heroPost ? (int) $heroPost->id : 0);

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

$showSidebar       = (bool) meridian_setting('layout', 'show_sidebar', true);
$showHero          = (bool) meridian_setting('blog', 'show_hero_post', true)
                  && (bool) meridian_setting('homepage', 'homepage_show_hero', true);
$homepageMode      = (string) meridian_setting('homepage', 'homepage_mode', 'posts');
$heroTitleOverride = (string) meridian_setting('homepage', 'homepage_hero_title', '');
$ctaText           = (string) meridian_setting('homepage', 'homepage_cta_text', '');
$ctaUrl            = (string) meridian_setting('homepage', 'homepage_cta_url', '');
$numRecent         = count($recentSidebar);

if ($homepageMode === 'landing') {
    require __DIR__ . '/partials/home-landing.php';
    return;
}

require __DIR__ . '/partials/home-blog.php';
