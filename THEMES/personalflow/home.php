<?php
/**
 * PersonalFlow Theme – Home / Startseite
 *
 * @package PersonalFlow_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

try {
    $c = \CMS\Services\ThemeCustomizer::instance();
} catch (\Throwable $e) {
    $c = null;
}

$heroBadge     = $c?->get('hr_hero', 'hero_badge',                '🌟 HR & Recruiting Plattform')       ?? '🌟 HR & Recruiting Plattform';
$heroHeadline  = $c?->get('hr_hero', 'hero_headline',             'Die smarte Plattform für Talente & Teams') ?? '';
$heroSubline   = $c?->get('hr_hero', 'hero_subline',              'Verbindet qualifizierte Fachkräfte mit zukunftsorientierten Unternehmen.') ?? '';
$ctaJobseeker  = $c?->get('hr_hero', 'cta_jobseeker_label',       'Als Kandidat bewerben')              ?? 'Als Kandidat bewerben';
$ctaJobUrl     = $c?->get('hr_hero', 'cta_jobseeker_url',         '/candidates/register')               ?? '/candidates/register';
$ctaEmployer   = $c?->get('hr_hero', 'cta_employer_label',        'Talente finden')                     ?? 'Talente finden';
$ctaEmpUrl     = $c?->get('hr_hero', 'cta_employer_url',         '/employers/register')                 ?? '/employers/register';
$showStats     = $c?->get('hr_hero', 'show_stats_bar',            true)                                  ?? true;

$candidatesTitle   = $c?->get('hr_content', 'candidates_section_title',  'Top-Kandidaten')          ?? 'Top-Kandidaten';
$jobsTitle         = $c?->get('hr_content', 'jobs_section_title',         'Offene Stellen')          ?? 'Offene Stellen';
$remoteLabel       = $c?->get('hr_content', 'remote_label',               'Remote möglich')          ?? 'Remote möglich';
$salaryLabel       = $c?->get('hr_content', 'salary_label',               'Gehalt')                  ?? 'Gehalt';
$matchScoreLabel   = $c?->get('hr_content', 'match_score_label',          'Match-Score')             ?? 'Match-Score';
$ctaTitle          = $c?->get('hr_content', 'cta_section_title',          'Jetzt durchstarten')      ?? 'Jetzt durchstarten';
$ctaText           = $c?->get('hr_content', 'cta_section_text',           'Finde deinen Traumjob oder das perfekte Talent.')   ?? '';
$ctaRegLabel       = $c?->get('hr_content', 'register_cta_label',         'Kostenlos registrieren')  ?? 'Kostenlos registrieren';

$siteUrl    = SITE_URL;
$isLoggedIn = theme_is_logged_in();
$safe       = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>

<main id="main" class="pf-main" role="main">

    <!-- ===== Hero ===== -->
    <section class="pf-hero" aria-label="Hero">
        <div class="pf-container">
            <?php if (!empty($heroBadge)) : ?>
                <div class="pf-hero-badge"><?php echo $safe($heroBadge); ?></div>
            <?php endif; ?>
            <h1><?php echo $safe($heroHeadline); ?></h1>
            <?php if (!empty($heroSubline)) : ?>
                <p class="pf-hero-sub"><?php echo $safe($heroSubline); ?></p>
            <?php endif; ?>
            <div class="pf-cta-group">
                <a href="<?php echo $safe($siteUrl . $ctaJobUrl); ?>" class="pf-btn pf-btn-primary">
                    <?php echo $safe($ctaJobseeker); ?>
                </a>
                <a href="<?php echo $safe($siteUrl . $ctaEmpUrl); ?>" class="pf-btn pf-btn-secondary">
                    <?php echo $safe($ctaEmployer); ?>
                </a>
            </div>

            <?php if ($showStats) : ?>
                <div class="pf-stats-row">
                    <div class="pf-stat">
                        <span class="pf-stat-number">1.200+</span>
                        <span class="pf-stat-label">Kandidaten</span>
                    </div>
                    <div class="pf-stat">
                        <span class="pf-stat-number">350+</span>
                        <span class="pf-stat-label">Unternehmen</span>
                    </div>
                    <div class="pf-stat">
                        <span class="pf-stat-number">85%</span>
                        <span class="pf-stat-label">Erfolgreiche Matches</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===== Kandidaten Preview ===== -->
    <section class="pf-section" style="padding:var(--pf-spacing-2xl,3rem) 0;">
        <div class="pf-container">
            <div class="pf-section-header">
                <h2><?php echo $safe($candidatesTitle); ?></h2>
            </div>
            <div class="pf-grid" id="pf-candidates-grid">
                <div class="pf-card" style="text-align:center;padding:3rem;grid-column:1/-1;">
                    <p style="color:var(--muted-color);">
                        <a href="<?php echo $safe($siteUrl); ?>/candidates" class="pf-btn pf-btn-primary">
                            Alle Kandidaten anzeigen →
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CTA ===== -->
    <?php if (!$isLoggedIn) : ?>
        <section class="pf-cta-section" aria-label="Call to Action">
            <div class="pf-container">
                <h2><?php echo $safe($ctaTitle); ?></h2>
                <?php if (!empty($ctaText)) : ?>
                    <p><?php echo $safe($ctaText); ?></p>
                <?php endif; ?>
                <a href="<?php echo $safe($siteUrl); ?>/register" class="pf-btn pf-btn-outline">
                    <?php echo $safe($ctaRegLabel); ?>
                </a>
            </div>
        </section>
    <?php endif; ?>

</main>
