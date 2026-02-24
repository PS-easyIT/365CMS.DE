<?php
/**
 * Suchergebnisse – MedCare Pro Theme
 *
 * Erwartet: $results (array), $query (string), $total (int), $currentPage (int), $totalPages (int)
 *
 * @package MedCarePro
 */
if (!defined('ABSPATH')) exit;
get_header();
$safe    = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$siteUrl = SITE_URL;
$query   = $safe(trim($_GET['q'] ?? $query ?? ''));
$page    = max(1, (int)($_GET['page'] ?? $currentPage ?? 1));

if (empty($results)) {
    try {
        $results    = [];
        $total      = 0;
        $totalPages = 1;
        if (!empty($query)) {
            $results    = \CMS\Services\PostService::search($query, ['per_page' => 12, 'page' => $page]);
            $total      = \CMS\Services\PostService::searchCount($query);
            $totalPages = (int)ceil($total / 12);
        }
    } catch (\Throwable $e) {
        $results = []; $total = 0; $totalPages = 1;
    }
}
?>
<main id="main" class="mc-main" role="main" style="padding:var(--spacing-2xl) 0;">
    <div class="mc-container">

        <!-- Suchformular -->
        <div class="mc-card" style="padding:2rem;margin-bottom:2rem;">
            <form role="search" method="get" action="<?php echo $safe($siteUrl); ?>/search"
                  style="display:flex;gap:.75rem;flex-wrap:wrap;" aria-label="Suche">
                <label for="search-input" class="mc-visually-hidden">Suchbegriff eingeben</label>
                <input id="search-input" type="search" name="q"
                       value="<?php echo $query; ?>"
                       placeholder="Arzt, Fachgebiet, Diagnose …"
                       class="mc-input" style="flex:1;min-width:200px;"
                       autofocus>
                <button type="submit" class="mc-btn mc-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    Suchen
                </button>
            </form>
        </div>

        <?php if (!empty($query)) : ?>
        <h1 id="search-results-heading"
            style="font-family:var(--font-heading);font-size:var(--font-2xl);color:var(--secondary-color);margin-bottom:1.5rem;">
            Suchergebnisse für
            <em style="font-style:normal;color:var(--primary-color);">&ldquo;<?php echo $query; ?>&rdquo;</em>
            <?php if ($total > 0) : ?>
                <span style="font-size:var(--font-sm);font-weight:400;color:var(--muted-color);margin-left:.5rem;">
                    (<?php echo $total; ?> Treffer)
                </span>
            <?php endif; ?>
        </h1>

        <?php if (!empty($results)) : ?>
        <div style="display:flex;flex-direction:column;gap:1rem;" aria-live="polite">
            <?php foreach ($results as $r) :
                $url     = $safe($r->url ?? $siteUrl . '/' . ($r->slug ?? $r->id ?? ''));
                $title   = $safe($r->title ?? '');
                $excerpt = $safe($r->excerpt ?? '');
                $type    = $safe($r->type ?? '');
                $date    = isset($r->created_at) ? date('d.m.Y', strtotime($r->created_at)) : '';
                $imgUrl  = $safe($r->thumbnail_url ?? '');
            ?>
            <div class="mc-search-result">
                <?php if (!empty($imgUrl)) : ?>
                <img src="<?php echo $imgUrl; ?>" alt="" aria-hidden="true" class="mc-search-result__avatar">
                <?php else : ?>
                <div class="mc-search-result__avatar" style="background:var(--bg-secondary);display:flex;align-items:center;justify-content:center;font-size:1.5rem;" aria-hidden="true">
                    <?php echo ($type === 'doctor') ? '👨‍⚕️' : '📄'; ?>
                </div>
                <?php endif; ?>
                <div style="flex:1;min-width:0;">
                    <h2 class="mc-search-result__name">
                        <a href="<?php echo $url; ?>" style="color:inherit;text-decoration:none;">
                            <?php echo $title; ?>
                        </a>
                    </h2>
                    <?php if (!empty($excerpt)) : ?>
                    <p class="mc-search-result__meta" style="margin-top:.35rem;line-height:1.55;">
                        <?php echo $excerpt; ?>
                    </p>
                    <?php endif; ?>
                    <div style="display:flex;align-items:center;gap:1rem;margin-top:.5rem;flex-wrap:wrap;">
                        <?php if (!empty($type)) : ?>
                        <span class="mc-specialty-badge"><?php echo $type === 'doctor' ? '👨‍⚕️ Arzt' : '📰 Artikel'; ?></span>
                        <?php endif; ?>
                        <?php if (!empty($date)) : ?>
                        <span style="font-size:var(--font-xs);color:var(--muted-color);">📅 <?php echo $date; ?></span>
                        <?php endif; ?>
                        <a href="<?php echo $url; ?>" class="mc-btn mc-btn-outline" style="padding:.3rem .75rem;font-size:var(--font-xs);margin-left:auto;">
                            Anzeigen →
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div><!-- /results -->

        <?php else : ?>
        <div class="mc-card" style="text-align:center;padding:3rem 2rem;">
            <div style="font-size:3rem;margin-bottom:.75rem;" aria-hidden="true">🔍</div>
            <h2 style="font-family:var(--font-heading);color:var(--secondary-color);margin-bottom:.5rem;">Kein Ergebnis gefunden</h2>
            <p style="color:var(--muted-color);max-width:480px;margin:0 auto 1.25rem;">
                Zu <strong><?php echo $query; ?></strong> wurde nichts gefunden. Bitte versuchen Sie andere Suchbegriffe oder durchsuchen Sie direkt unsere Ärzteliste.
            </p>
            <a href="<?php echo $safe($siteUrl); ?>/aerzte" class="mc-btn mc-btn-primary">Arzt suchen</a>
        </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1) : ?>
        <nav aria-label="Seitennavigation" style="display:flex;gap:.5rem;justify-content:center;margin-top:2.5rem;flex-wrap:wrap;">
            <?php if ($page > 1) : ?>
            <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page - 1; ?>" class="mc-btn mc-btn-outline" rel="prev">← Zurück</a>
            <?php endif; ?>
            <span style="padding:.5rem 1rem;font-size:var(--font-sm);color:var(--muted-color);align-self:center;">
                Seite <?php echo $page; ?> von <?php echo $totalPages; ?>
            </span>
            <?php if ($page < $totalPages) : ?>
            <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page + 1; ?>" class="mc-btn mc-btn-outline" rel="next">Weiter →</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

        <?php else : ?>
        <!-- Keine Suchanfrage -->
        <div class="mc-section-header">
            <h1 id="search-results-heading" style="font-size:var(--font-3xl);">🔍 Arzt &amp; Ratgeber-Suche</h1>
            <p>Geben Sie einen Suchbegriff ein, um Ärzte, Fachgebiete oder Gesundheitsartikel zu finden.</p>
        </div>
        <?php endif; ?>

    </div>
</main>
<?php get_footer(); ?>
