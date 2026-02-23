<?php
/**
 * TechNexus Theme – Home / Startseite
 *
 * @package TechNexus_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

try {
    $c           = \CMS\Services\ThemeCustomizer::instance();
    $landingSvc  = \CMS\Services\LandingPageService::getInstance();
    $lpHeader    = $landingSvc->getHeader();
    $lpFeatures  = $landingSvc->getFeatures();
    $lpFooter    = $landingSvc->getFooter();
} catch (\Throwable $e) {
    $c          = null;
    $lpHeader   = [];
    $lpFeatures = [];
    $lpFooter   = [];
}

// Customizer-Werte
$heroBadge     = $c?->get('tech_hero', 'hero_badge', '🚀 Die IT-Expert Plattform') ?? '🚀 Die IT-Expert Plattform';
$heroHeadline  = $c?->get('tech_hero', 'hero_headline', 'Vernetze IT-Experten. Skaliere dein Team.') ?? 'Vernetze IT-Experten. Skaliere dein Team.';
$heroSubline   = $c?->get('tech_hero', 'hero_subline', 'Finde spezialisierte IT-Fachkräfte, innovative Technologiepartner und spannende Projekte.') ?? '';
$heroCta       = $c?->get('tech_hero', 'hero_cta_label', 'Experten entdecken') ?? 'Experten entdecken';
$heroCtaUrl    = $c?->get('tech_hero', 'hero_cta_url', '/it-experts') ?? '/it-experts';
$heroSecCta    = $c?->get('tech_hero', 'hero_secondary_cta_label', 'Profil anlegen') ?? 'Profil anlegen';
$heroSecCtaUrl = $c?->get('tech_hero', 'hero_secondary_cta_url', '/register') ?? '/register';
$showStats     = $c?->get('tech_hero', 'show_stats_bar', true) ?? true;

$expertTitle   = $c?->get('tech_content', 'experts_section_title', 'Top IT-Experten') ?? 'Top IT-Experten';
$expertSubT    = $c?->get('tech_content', 'experts_section_subtitle', 'Spezialisierte Fachkräfte für jedes Tech-Projekt') ?? '';
$compTitle     = $c?->get('tech_content', 'companies_section_title', 'Führende Tech-Unternehmen') ?? 'Führende Tech-Unternehmen';
$ctaTitle      = $c?->get('tech_content', 'cta_section_title', 'Bereit für das nächste Projekt?') ?? 'Bereit für das nächste Projekt?';
$ctaText       = $c?->get('tech_content', 'cta_section_text', 'Registriere Dich kostenlos und werde Teil der größten IT-Expert-Community.') ?? '';
$ctaRegLabel   = $c?->get('tech_content', 'register_cta_label', 'Jetzt kostenlos registrieren') ?? 'Jetzt kostenlos registrieren';

$siteUrl    = SITE_URL;
$isLoggedIn = theme_is_logged_in();

$safe = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>

<main id="main" class="site-main" role="main">

    <!-- ===== Hero Section ===== -->
    <section class="home-hero" aria-label="Hero">
        <div class="container">
            <?php if (!empty($heroBadge)) : ?>
                <div class="hero-badge" aria-hidden="true"><?php echo $safe($heroBadge); ?></div>
            <?php endif; ?>

            <h1><?php echo $safe($heroHeadline); ?></h1>

            <?php if (!empty($heroSubline)) : ?>
                <p><?php echo $safe($heroSubline); ?></p>
            <?php endif; ?>

            <div class="hero-cta-group">
                <a href="<?php echo $safe($siteUrl . $heroCtaUrl); ?>" class="btn btn-primary">
                    <?php echo $safe($heroCta); ?>
                </a>
                <?php if (!$isLoggedIn) : ?>
                    <a href="<?php echo $safe($siteUrl . $heroSecCtaUrl); ?>" class="btn btn-outline">
                        <?php echo $safe($heroSecCta); ?>
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($showStats) : ?>
                <div class="hero-stats" aria-label="Plattform-Statistiken">
                    <div class="hero-stat">
                        <span class="hero-stat-number" id="stat-experts">500+</span>
                        <span class="hero-stat-label">IT-Experten</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number" id="stat-companies">120+</span>
                        <span class="hero-stat-label">Tech-Firmen</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number" id="stat-projects">300+</span>
                        <span class="hero-stat-label">Projekte</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===== Features from LandingPage Admin ===== -->
    <?php if (!empty($lpFeatures)) : ?>
        <section class="home-features" style="padding:var(--spacing-2xl) 0;background:var(--bg-secondary);">
            <div class="container">
                <div class="section-header">
                    <?php
                    $featTitle = $lpHeader['features_title'] ?? '';
                    $featSub   = $lpHeader['features_subtitle'] ?? '';
                    if (!empty($featTitle)) : ?>
                        <h2><?php echo $safe($featTitle); ?></h2>
                    <?php endif; ?>
                    <?php if (!empty($featSub)) : ?>
                        <p><?php echo $safe($featSub); ?></p>
                    <?php endif; ?>
                </div>
                <div class="tech-grid">
                    <?php foreach ($lpFeatures as $feat) :
                        $fTitle = $feat['title'] ?? '';
                        $fText  = $feat['text']  ?? '';
                        $fIcon  = $feat['icon']  ?? '';
                        if (empty($fTitle) && empty($fText)) continue;
                    ?>
                        <div class="tech-card">
                            <?php if (!empty($fIcon)) : ?>
                                <div class="feature-icon" aria-hidden="true" style="font-size:2rem;margin-bottom:0.75rem;"><?php echo $safe($fIcon); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($fTitle)) : ?>
                                <h3><?php echo $safe($fTitle); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($fText)) : ?>
                                <p style="color:var(--muted-color);margin-top:0.5rem;"><?php echo $safe($fText); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- ===== Experten Sektion ===== -->
    <section class="home-experts" style="padding:var(--spacing-2xl) 0;">
        <div class="container">
            <div class="section-header">
                <h2><?php echo $safe($expertTitle); ?></h2>
                <?php if (!empty($expertSubT)) : ?>
                    <p><?php echo $safe($expertSubT); ?></p>
                <?php endif; ?>
            </div>
            <div class="tech-grid" id="home-experts-grid">
                <!-- Via JS/AJAX oder serverseitig befüllbar -->
                <div class="tech-card" style="text-align:center;padding:3rem;grid-column:1/-1;">
                    <p style="color:var(--muted-color);">
                        <a href="<?php echo $safe($siteUrl); ?>/it-experts" class="btn btn-primary" style="margin-top:1rem;">
                            Alle IT-Experten anzeigen →
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CTA Section ===== -->
    <?php if (!$isLoggedIn) : ?>
        <section class="cta-section" aria-label="Registrierung">
            <div class="container">
                <h2><?php echo $safe($ctaTitle); ?></h2>
                <?php if (!empty($ctaText)) : ?>
                    <p><?php echo $safe($ctaText); ?></p>
                <?php endif; ?>
                <a href="<?php echo $safe($siteUrl); ?>/register" class="btn btn-outline">
                    <?php echo $safe($ctaRegLabel); ?>
                </a>
            </div>
        </section>
    <?php endif; ?>

</main>
