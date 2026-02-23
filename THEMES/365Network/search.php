<?php
/**
 * Search Results Template
 *
 * Erhält:
 *   $results - Array von Suchergebnissen (Seiten-Objekte)
 *   $query   - Suchbegriff (string)
 *
 * @package IT_Expert_Network_Theme
 * @var array  $results
 * @var string $query
 */

if (!defined('ABSPATH')) {
    exit;
}

$results = $results ?? [];
$query   = $query ?? '';
$siteUrl = SITE_URL;
$count   = count($results);
?>

<main id="main" class="site-main" role="main">
    <div class="container">
        <div class="content-area" style="padding:var(--spacing-lg) 0;">

            <!-- Suchkopf -->
            <header class="search-results-header">
                <h1>
                    <?php if ($query && trim($query) !== '') : ?>
                        Suchergebnisse für: <em style="color:var(--primary-color);">„<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>"</em>
                    <?php else : ?>
                        Suche
                    <?php endif; ?>
                </h1>
                <?php if ($query && trim($query) !== '') : ?>
                    <p style="color:#666;font-size:0.95rem;margin-top:0.5rem;">
                        <?php echo $count; ?> Ergebnis<?php echo $count !== 1 ? 'se' : ''; ?> gefunden
                    </p>
                <?php endif; ?>
            </header>

            <!-- Suchformular -->
            <form action="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/search" method="GET"
                  style="display:flex;gap:0.75rem;margin-bottom:var(--spacing-lg);">
                <input class="form-control"
                       type="search"
                       name="q"
                       placeholder="Suche nach Seiten, Themen…"
                       value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>"
                       aria-label="Suchbegriff"
                       style="max-width:500px;">
                <button type="submit" class="btn btn-primary">Suchen</button>
            </form>

            <!-- Ergebnisse -->
            <?php if ($count > 0) : ?>
                <div class="search-results-list">
                    <?php foreach ($results as $result) :
                        $resultTitle   = is_array($result) ? ($result['title'] ?? '') : ($result->title ?? '');
                        $resultSlug    = is_array($result) ? ($result['slug'] ?? '') : ($result->slug ?? '');
                        $resultExcerpt = is_array($result) ? ($result['meta_description'] ?? $result['content'] ?? '') : ($result->meta_description ?? $result->content ?? '');
                        $resultUrl     = htmlspecialchars($siteUrl . '/' . $resultSlug, ENT_QUOTES, 'UTF-8');

                        // Excerpt kürzen
                        if (mb_strlen(strip_tags($resultExcerpt)) > 200) {
                            $resultExcerpt = mb_substr(strip_tags($resultExcerpt), 0, 200) . '…';
                        } else {
                            $resultExcerpt = strip_tags($resultExcerpt);
                        }
                    ?>
                        <article class="search-result-item">
                            <h2 class="search-result-title">
                                <a href="<?php echo $resultUrl; ?>">
                                    <?php echo htmlspecialchars($resultTitle, ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </h2>
                            <?php if ($resultExcerpt && trim($resultExcerpt) !== '') : ?>
                                <p class="search-result-excerpt">
                                    <?php echo htmlspecialchars($resultExcerpt, ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            <?php endif; ?>
                            <p class="search-result-meta">
                                <a href="<?php echo $resultUrl; ?>" style="color:var(--primary-color);">
                                    <?php echo $resultUrl; ?>
                                </a>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>

            <?php else : ?>
                <div class="search-no-results">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        <line x1="8" y1="11" x2="14" y2="11"/>
                    </svg>
                    <?php if ($query && trim($query) !== '') : ?>
                        <p>Keine Ergebnisse für „<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>" gefunden.</p>
                        <p style="font-size:0.9rem;">Versuche andere Suchbegriffe oder weniger Wörter.</p>
                    <?php else : ?>
                        <p>Gib einen Suchbegriff ein, um loszulegen.</p>
                    <?php endif; ?>
                    <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/"
                       class="btn btn-outline" style="margin-top:var(--spacing-md);">
                        Zur Startseite
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</main>
