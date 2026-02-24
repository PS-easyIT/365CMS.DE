<?php
/**
 * Einzelner Blog-Beitrag – MedCare Pro Theme
 *
 * Erwartet: $post (object|array)
 *
 * @package MedCarePro
 */
if (!defined('ABSPATH')) exit;
get_header();
$safe    = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$siteUrl = SITE_URL;

/** @var object|null $post */
if (empty($post)) {
    try {
        $post = \CMS\Services\PostService::getCurrent();
    } catch (\Throwable $e) {
        $post = null;
    }
}
?>
<main id="main" class="mc-main" role="main" style="padding:var(--spacing-2xl) 0;">
    <div class="mc-container" style="max-width:860px;">

        <?php if (!empty($post)) :
            $title    = $safe($post->title ?? '');
            $content  = $post->content ?? '';
            $date     = isset($post->created_at) ? date('d. F Y', strtotime($post->created_at)) : '';
            $author   = $safe($post->author_name ?? '');
            $category = $safe($post->category_name ?? '');
            $imgUrl   = $safe($post->thumbnail_url ?? '');
            $dsgvoNote = mc_get_setting('dsgvo_medical', 'show_privacy_on_forms', true)
                         ? mc_get_setting('dsgvo_medical', 'privacy_form_text', '')
                         : '';
        ?>

        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" style="font-size:var(--font-sm);color:var(--muted-color);margin-bottom:1.5rem;">
            <a href="<?php echo $safe($siteUrl); ?>">Startseite</a> ›
            <a href="<?php echo $safe($siteUrl); ?>/blog">Gesundheitsratgeber</a> ›
            <span aria-current="page"><?php echo $title; ?></span>
        </nav>

        <article aria-labelledby="post-title">
            <!-- Header -->
            <header style="margin-bottom:2rem;">
                <?php if (!empty($category)) : ?>
                <span class="mc-specialty-badge" style="margin-bottom:.75rem;display:inline-block;">
                    <?php echo $category; ?>
                </span>
                <?php endif; ?>

                <h1 id="post-title" style="font-family:var(--font-heading);font-size:clamp(1.5rem,4vw,var(--font-4xl));color:var(--secondary-color);line-height:1.25;margin-bottom:.75rem;">
                    <?php echo $title; ?>
                </h1>

                <div style="display:flex;flex-wrap:wrap;gap:1rem;font-size:var(--font-sm);color:var(--muted-color);padding-bottom:1.25rem;border-bottom:1px solid var(--border-color);">
                    <?php if (!empty($author)) : ?>
                    <span>👤 <?php echo $author; ?></span>
                    <?php endif; ?>
                    <?php if (!empty($date)) : ?>
                    <time>📅 <?php echo $date; ?></time>
                    <?php endif; ?>
                    <span style="margin-left:auto;">
                        <a href="<?php echo $safe($siteUrl); ?>/blog" style="color:var(--primary-color);">← Zurück zum Ratgeber</a>
                    </span>
                </div>
            </header>

            <?php if (!empty($imgUrl)) : ?>
            <img src="<?php echo $imgUrl; ?>" alt="<?php echo $title; ?>"
                 style="width:100%;max-height:420px;object-fit:cover;border-radius:var(--radius-lg);margin-bottom:2rem;">
            <?php endif; ?>

            <!-- Inhalt -->
            <?php if (!empty($content)) : ?>
            <div class="mc-card prose" style="padding:2rem;">
                <?php echo \CMS\Helpers\ContentHelper::processContent($content); ?>
            </div>
            <?php endif; ?>

            <!-- Medizinischer Hinweis -->
            <div class="mc-emergency-info" style="margin-top:2rem;" role="note">
                <strong>⚕️ Medizinischer Hinweis:</strong>
                Dieser Artikel dient ausschließlich der allgemeinen Information. Er ersetzt keine professionelle ärztliche Beratung.
                Bei gesundheitlichen Beschwerden wenden Sie sich bitte an einen Arzt.
            </div>

            <?php if (!empty($dsgvoNote)) : ?>
            <p class="mc-dsgvo-note" style="margin-top:1rem;"><?php echo $safe($dsgvoNote); ?></p>
            <?php endif; ?>
        </article>

        <!-- Navigation: zurück / vorwärts -->
        <nav style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-top:2.5rem;" aria-label="Beitragsnavigation">
            <a href="<?php echo $safe($siteUrl); ?>/blog" class="mc-btn mc-btn-outline">← Alle Beiträge</a>
            <a href="<?php echo $safe($siteUrl); ?>/aerzte" class="mc-btn mc-btn-primary">Passenden Arzt finden</a>
        </nav>

        <?php else : ?>
        <div class="mc-card" style="text-align:center;padding:3rem 2rem;">
            <div style="font-size:3rem;margin-bottom:.75rem;" aria-hidden="true">🔍</div>
            <h1 style="font-family:var(--font-heading);color:var(--secondary-color);margin-bottom:.5rem;">Beitrag nicht gefunden</h1>
            <p style="color:var(--muted-color);">Dieser Beitrag existiert leider nicht mehr.</p>
            <a href="<?php echo $safe($siteUrl); ?>/blog" class="mc-btn mc-btn-primary" style="margin-top:1.25rem;">
                Zurück zum Ratgeber
            </a>
        </div>
        <?php endif; ?>

    </div>
</main>
<?php get_footer(); ?>
