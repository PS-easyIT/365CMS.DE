<?php
if (!defined('ABSPATH')) exit;
try { $c = \CMS\Services\ThemeCustomizer::instance(); } catch (\Throwable $e) { $c = null; }

$heroBadge    = $c?->get('medical_hero', 'hero_badge',      '✚ Gesundheitsplattform')                          ?? '✚ Gesundheitsplattform';
$heroHeadline = $c?->get('medical_hero', 'hero_headline',   'Ihren Arzt einfach online finden')                ?? 'Ihren Arzt einfach online finden';
$heroSubline  = $c?->get('medical_hero', 'hero_subline',    'Ärzte, Kliniken und Fachspezialisten in Ihrer Region – schnell und einfach.')  ?? '';
$heroCta      = $c?->get('medical_hero', 'hero_cta_label',  'Arzt suchen')             ?? 'Arzt suchen';
$heroCtaUrl   = $c?->get('medical_hero', 'hero_cta_url',   '/aerzte')                  ?? '/aerzte';
$heroSecCta   = $c?->get('medical_hero', 'hero_secondary_cta_label', 'Termin buchen')  ?? 'Termin buchen';
$heroSecUrl   = $c?->get('medical_hero', 'hero_secondary_cta_url',   '/termin')         ?? '/termin';
$showStats    = $c?->get('medical_hero', 'show_stats_bar',  true)                        ?? true;

$doctorsTitle = $c?->get('medical_content', 'doctor_profiles_title',  'Unsere Fachärzte')     ?? 'Unsere Fachärzte';
$bookingTitle = $c?->get('medical_content', 'booking_section_title',  'Termin vereinbaren')   ?? 'Termin vereinbaren';
$bookingText  = $c?->get('medical_content', 'booking_section_text',   'Online-Terminbuchung rund um die Uhr – ohne Warteschleife.') ?? '';
$insLabel     = $c?->get('medical_content', 'insurance_label',        'Versicherung')         ?? 'Versicherung';
$gkvLabel     = $c?->get('medical_content', 'insurance_gkv_label',    'GKV')                  ?? 'GKV';
$pkvLabel     = $c?->get('medical_content', 'insurance_pkv_label',    'PKV')                  ?? 'PKV';
$emergInfo    = $c?->get('medical_content', 'emergency_info',          '')                     ?? '';
$ctaRegLabel  = $c?->get('medical_content', 'register_cta_label',     'Als Arzt registrieren') ?? '';

$dsgvoMode    = $c?->get('dsgvo_medical', 'enable_medical_dsgvo_mode', false) ?? false;
$cookieText   = $c?->get('dsgvo_medical', 'cookie_banner_text', '')           ?? '';

$siteUrl    = SITE_URL;
$isLoggedIn = theme_is_logged_in();
$safe       = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
<main id="main" class="mc-main" role="main">

    <!-- Notfall-Info -->
    <?php if (!empty($emergInfo)) : ?>
    <div style="background:var(--warning-color);color:#fff;text-align:center;padding:.6rem 1rem;font-size:var(--font-sm);font-weight:600;">
        <?php echo $safe($emergInfo); ?>
    </div>
    <?php endif; ?>

    <!-- Hero -->
    <section class="mc-hero" aria-label="Hero">
        <div class="mc-container">
            <?php if (!empty($heroBadge)) : ?>
                <div class="mc-hero-badge"><?php echo $safe($heroBadge); ?></div>
            <?php endif; ?>
            <h1><?php echo $safe($heroHeadline); ?></h1>
            <?php if (!empty($heroSubline)) : ?>
                <p class="mc-hero-sub"><?php echo $safe($heroSubline); ?></p>
            <?php endif; ?>
            <div class="mc-cta-group">
                <a href="<?php echo $safe($siteUrl . $heroCtaUrl); ?>" class="mc-btn mc-btn-primary"><?php echo $safe($heroCta); ?></a>
                <a href="<?php echo $safe($siteUrl . $heroSecUrl);  ?>" class="mc-btn mc-btn-secondary"><?php echo $safe($heroSecCta); ?></a>
            </div>
            <?php if ($showStats) : ?>
                <div class="mc-stats-row">
                    <div class="mc-stat"><span class="mc-stat-number">1.500+</span><span class="mc-stat-label">Ärzte & Kliniken</span></div>
                    <div class="mc-stat"><span class="mc-stat-number">40+</span><span class="mc-stat-label">Fachgebiete</span></div>
                    <div class="mc-stat"><span class="mc-stat-number">24/7</span><span class="mc-stat-label">Online-Termine</span></div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Ärzte -->
    <section class="mc-section" aria-label="<?php echo $safe($doctorsTitle); ?>">
        <div class="mc-container">
            <div class="mc-section-header"><h2><?php echo $safe($doctorsTitle); ?></h2></div>
            <div class="mc-grid">
                <div class="mc-card" style="text-align:center;grid-column:1/-1;padding:3rem;">
                    <a href="<?php echo $safe($siteUrl); ?>/aerzte" class="mc-btn mc-btn-primary">Alle Ärzte anzeigen →</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Buchungs-CTA -->
    <?php if (!$isLoggedIn) : ?>
        <section class="mc-section mc-section--alt" aria-label="Termin buchen">
            <div class="mc-container" style="text-align:center;">
                <h2><?php echo $safe($bookingTitle); ?></h2>
                <?php if (!empty($bookingText)) : ?>
                    <p style="color:var(--text-secondary);max-width:560px;margin:.75rem auto 1.5rem;"><?php echo $safe($bookingText); ?></p>
                <?php endif; ?>
                <a href="<?php echo $safe($siteUrl); ?>/register" class="mc-btn mc-btn-primary">
                    <?php echo $safe($ctaRegLabel ?: 'Als Arzt registrieren'); ?>
                </a>
            </div>
        </section>
    <?php endif; ?>

</main>
