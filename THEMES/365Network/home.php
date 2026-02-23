<?php
/**
 * Home Template - Startseite
 *
 * Zeigt die über den Admin verwaltete Landing Page an.
 * Inhalte (Titel, Beschreibung, Feature-Grid, Farben) werden über den
 * LandingPageService aus der Datenbank geladen und sind im Admin unter
 * Seiten → „Landing Page" und „Farben" editierbar.
 *
 * @package IT_Expert_Network_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

// ===== Landing Page Daten über LandingPageService laden =====
$landingHeader   = [];
$landingFeatures = [];
$landingColors   = [];

try {
    $landingService  = \CMS\Services\LandingPageService::getInstance();
    $landingHeader   = $landingService->getHeader();
    $landingFeatures = $landingService->getFeatures();
    $landingFooter   = $landingService->getFooter();
    $landingColors   = $landingHeader['colors'] ?? [];
} catch (\Throwable $e) {
    error_log('Home Template LandingPageService error: ' . $e->getMessage());
}

// Farb-Defaults (identisch mit Admin-Defaults in pages.php)
$heroGradStart   = htmlspecialchars($landingColors['hero_gradient_start'] ?? '#1e3a5f', ENT_QUOTES, 'UTF-8');
$heroGradEnd     = htmlspecialchars($landingColors['hero_gradient_end']   ?? '#0c1f35', ENT_QUOTES, 'UTF-8');
$heroBorder      = htmlspecialchars($landingColors['hero_border']          ?? '#b45309', ENT_QUOTES, 'UTF-8');
$heroText        = htmlspecialchars($landingColors['hero_text']            ?? '#ffffff', ENT_QUOTES, 'UTF-8');
$featuresBg      = htmlspecialchars($landingColors['features_bg']          ?? '#f2f0ec', ENT_QUOTES, 'UTF-8');
$featureCardBg   = htmlspecialchars($landingColors['feature_card_bg']      ?? '#faf9f7', ENT_QUOTES, 'UTF-8');
$featureCardHov  = htmlspecialchars($landingColors['feature_card_hover']   ?? '#b45309', ENT_QUOTES, 'UTF-8');
$primaryButton   = htmlspecialchars($landingColors['primary_button']       ?? '#b45309', ENT_QUOTES, 'UTF-8');

$siteUrl    = SITE_URL;
$isLoggedIn = theme_is_logged_in();

?>
<!-- Landing Page Farbvariablen (aus Admin-Einstellungen) -->
<style>
.home-hero {
    --lp-grad-start:   <?php echo $heroGradStart; ?>;
    --lp-grad-end:     <?php echo $heroGradEnd; ?>;
    --lp-border:       <?php echo $heroBorder; ?>;
    --lp-text:         <?php echo $heroText; ?>;
    --lp-btn-primary:  <?php echo $primaryButton; ?>;
}
.home-features {
    --lp-features-bg:       <?php echo $featuresBg; ?>;
    --lp-card-bg:           <?php echo $featureCardBg; ?>;
    --lp-card-hover:        <?php echo $featureCardHov; ?>;
}
</style>

<main id="main" class="site-main" role="main">

    <!-- ===== Hero Section ===== -->
    <?php
    $logoPos = $landingHeader['logo_position'] ?? 'top';
    $headerLayout = $landingHeader['header_layout'] ?? 'standard';
    $hasLogo = !empty($landingHeader['logo']);
    
    // Config: Compact vs Standard
    if ($headerLayout === 'compact') {
        // Compact Configuration
        // Max half height - drastic reduction in padding and margins
        $heroPadding    = $hasLogo ? '2rem 1rem' : '2rem 1rem'; 
        $titleSize      = '1.75rem';
        $subtitleSize   = '1.1rem';
        $descSize       = '0.95rem';
        $logoHeight     = '40px';
        $logoMargin     = '0 auto 0.5rem';
        $titleMargin    = '0.5rem';
        $descMargin     = ($logoPos === 'left' && $hasLogo) ? '0 0 0.5rem' : '0 auto 1rem';
        $btnMarginTop   = '1rem';
        $containerWidth = '900px';
        $minHeight      = 'auto'; // Let content dictate height
    } else {
        // Standard Configuration (Generous)
        $heroPadding    = $hasLogo ? '6rem 2rem' : '4rem 2rem';
        $titleSize      = '3rem';
        $subtitleSize   = '1.5rem';
        $descSize       = '1.2rem';
        $logoHeight     = '90px';
        $logoMargin     = '0 auto 2rem';
        $titleMargin    = '1.5rem';
        $descMargin     = ($logoPos === 'left' && $hasLogo) ? '0 0 2rem' : '0 auto 3rem';
        $btnMarginTop   = '2.5rem';
        $containerWidth = ($logoPos === 'left') ? '1000px' : '800px';
        $minHeight      = '70vh'; // Ensure "Hero" feel
    }
    
    ?>
    <section class="home-hero" style="
        background: linear-gradient(135deg, var(--lp-grad-start) 0%, var(--lp-grad-end) 100%);
        color: var(--lp-text);
        padding: <?php echo $heroPadding; ?>;
        text-align: <?php echo ($logoPos === 'left' && $hasLogo) ? 'left' : 'center'; ?>;
        position: relative;
        overflow: hidden;
        border-bottom: 3px solid var(--lp-border);
        margin-top: 0;
        min-height: <?php echo $minHeight; ?>;
        display: flex;
        flex-direction: column;
        justify-content: center;
    ">
        <div class="container" style="position:relative;z-index:1;max-width:<?php echo $containerWidth; ?>;margin:0 auto;">

            <?php if ($logoPos === 'left' && $hasLogo) : ?>
                <div style="display:flex;gap:2rem;align-items:center;justify-content:center;flex-wrap:wrap;text-align:left;">
                    <div style="flex:0 0 auto;">
                        <img src="<?php echo htmlspecialchars($landingHeader['logo'], ENT_QUOTES, 'UTF-8'); ?>"
                             alt="<?php echo htmlspecialchars($landingHeader['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                             style="max-height:<?php echo $headerLayout === 'compact' ? '80px' : '120px'; ?>;display:block;">
                    </div>
                    <div style="flex:1;min-width:300px;">
            <?php else : ?>
                <?php if ($hasLogo) : ?>
                    <img src="<?php echo htmlspecialchars($landingHeader['logo'], ENT_QUOTES, 'UTF-8'); ?>"
                         alt="<?php echo htmlspecialchars($landingHeader['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                         style="max-height:<?php echo $logoHeight; ?>;margin:<?php echo $logoMargin; ?>;display:block;">
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($landingHeader['title'])) : ?>
                <div style="position:relative;display:inline-block;max-width:100%;">
                    <h1 style="font-size:<?php echo $titleSize; ?>;font-weight:900;margin-bottom:<?php echo $titleMargin; ?>;color:var(--lp-text);line-height:1.2;">
                        <?php echo htmlspecialchars($landingHeader['title'], ENT_QUOTES, 'UTF-8'); ?>
                    </h1>
                    
                    <?php if (!empty($landingHeader['version']) && $headerLayout === 'compact') : ?>
                        <span style="
                            position: absolute;
                            top: -0.25rem;
                            right: -3.5rem;
                            background: rgba(255,255,255,0.15);
                            border: 1px solid rgba(255,255,255,0.3);
                            border-radius: 4px;
                            padding: 0.1rem 0.4rem;
                            font-size: 0.7rem;
                            font-weight: 500;
                            line-height: 1;
                            white-space: nowrap;
                        ">
                            v<?php echo htmlspecialchars($landingHeader['version'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($landingHeader['subtitle'])) : ?>
                <p style="font-size:<?php echo $subtitleSize; ?>;opacity:0.85;margin-bottom:<?php echo $titleMargin; ?>;font-weight:500;">
                    <?php echo htmlspecialchars($landingHeader['subtitle'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($landingHeader['description'])) : ?>
                <div style="font-size:<?php echo $descSize; ?>;opacity:0.75;max-width:640px;margin:<?php echo $descMargin; ?>;line-height:1.6;">
                    <?php echo $landingHeader['description']; // HTML allowed from Editor ?>
                </div>
            <?php endif; ?>

            <?php if ($logoPos === 'left' && $hasLogo) : ?>
                    </div> <!-- End Text Column -->
                </div> <!-- End Flex Container -->
            <?php endif; ?>

            <?php if (!empty($landingHeader['version']) && $headerLayout !== 'compact') : ?>
                <div style="text-align:center;">
                    <span style="display:inline-block;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.25);
                                 border-radius:999px;padding:0.2rem 0.85rem;font-size:0.8rem;margin-bottom:2rem;opacity:0.9;">
                        v<?php echo htmlspecialchars($landingHeader['version'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
            <?php endif; ?>

            <!-- CTA-Buttons -->
            <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;margin-bottom:<?php echo ($headerLayout === 'compact' ? '0.5rem' : '1.5rem'); ?>;margin-top:<?php echo $btnMarginTop; ?>;">
                <?php 
                // Custom Buttons Loop
                $customButtons = $landingHeader['header_buttons'] ?? [];
                
                // Fallback for migration if empty but old fields exist
                 if (empty($customButtons) && !empty($landingHeader['github_url'])) {
                    $customButtons[] = [
                        'text' => $landingHeader['github_text'] ?? 'GitHub',
                        'url' => $landingHeader['github_url'],
                        'icon' => '💻',
                        'target' => '_blank',
                        'outline' => true
                    ];
                }
                if (empty($customButtons) && !empty($landingHeader['gitlab_url']) && count($customButtons) < 4) {
                    $customButtons[] = [
                        'text' => $landingHeader['gitlab_text'] ?? 'GitLab',
                        'url' => $landingHeader['gitlab_url'],
                        'icon' => '🦊',
                        'target' => '_blank',
                        'outline' => true
                    ];
                }

                foreach ($customButtons as $btn) :
                    if (empty($btn['url'])) continue;
                    $bText   = htmlspecialchars($btn['text'] ?? 'Link', ENT_QUOTES, 'UTF-8');
                    $bUrl    = htmlspecialchars($btn['url'], ENT_QUOTES, 'UTF-8');
                    $bIcon   = htmlspecialchars($btn['icon'] ?? '', ENT_QUOTES, 'UTF-8'); // Emojis are safe
                    $bTarget = htmlspecialchars($btn['target'] ?? '_self', ENT_QUOTES, 'UTF-8');
                    $isOutline = !empty($btn['outline']);
                    
                    // Style logic
                    $borderStyle = $isOutline ? 'border-color:rgba(255,255,255,0.5);color:var(--lp-text);' : 'background:rgba(255,255,255,0.2);color:var(--lp-text);border:1px solid rgba(255,255,255,0.3);';
                ?>
                    <a href="<?php echo $bUrl; ?>"
                       class="btn <?php echo ($headerLayout === 'compact' ? 'btn-normal' : 'btn-lg'); ?> <?php echo $isOutline ? 'btn-outline' : ''; ?>"
                       style="<?php echo $borderStyle; ?>"
                       target="<?php echo $bTarget; ?>" 
                       rel="<?php echo ($bTarget === '_blank' ? 'noopener noreferrer' : ''); ?>">
                        <?php echo $bIcon ? $bIcon . ' ' : ''; ?><?php echo $bText; ?>
                    </a>
                <?php endforeach; ?>
            </div>

        </div>
    </section>

    <!-- ===== Feature Grid ===== -->
    <?php if (!empty($landingFeatures)) : ?>
    <section class="home-features" style="
        padding: 4rem 2rem;
        background: var(--lp-features-bg);
    ">
        <div class="container">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem;">
                <?php foreach ($landingFeatures as $feature) :
                    $fIcon  = htmlspecialchars($feature['icon']        ?? '🔧', ENT_QUOTES, 'UTF-8');
                    $fTitle = htmlspecialchars($feature['title']       ?? '',   ENT_QUOTES, 'UTF-8');
                    $fDesc  = htmlspecialchars($feature['description'] ?? '',   ENT_QUOTES, 'UTF-8');
                    if (trim($fTitle) === '') continue;
                ?>
                    <div class="home-feature-card" style="
                        background: var(--lp-card-bg);
                        border-radius: var(--border-radius, 12px);
                        padding: 2rem 1.5rem;
                        text-align: center;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
                        transition: transform 0.2s, box-shadow 0.2s;
                        border-top: 3px solid transparent;
                    "
                    onmouseenter="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.12)';this.style.borderTopColor='<?php echo $featureCardHov; ?>'"
                    onmouseleave="this.style.transform='';this.style.boxShadow='0 2px 10px rgba(0,0,0,0.06)';this.style.borderTopColor='transparent'">
                        <div style="font-size:2.5rem;margin-bottom:1rem;"><?php echo $fIcon; ?></div>
                        <?php if ($fTitle !== '') : ?>
                            <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:0.6rem;"><?php echo $fTitle; ?></h3>
                        <?php endif; ?>
                        <?php if ($fDesc !== '') : ?>
                            <p style="font-size:0.95rem;line-height:1.6;color:var(--muted-color,#666);"><?php echo $fDesc; ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== Footer / CTA Area (Admin Managed) ===== -->
    <?php if (!empty($landingFooter['show_footer']) && $landingFooter['show_footer']) : ?>
    <section style="
        padding: 4rem 2rem;
        text-align: center;
        background: var(--body-bg, #fff);
        border-top: 1px solid var(--border-color, #e2e8f0);
    ">
        <div class="container" style="max-width:800px;margin:0 auto;">
            <?php if (!empty($landingFooter['content'])) : ?>
                <div class="footer-content" style="font-size:1.1rem;line-height:1.6;color:var(--text-color, #333);margin-bottom:2rem;">
                    <?php echo $landingFooter['content']; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($landingFooter['button_text']) && !empty($landingFooter['button_url'])) : ?>
                <div style="margin-bottom:3rem;">
                    <a href="<?php echo htmlspecialchars($landingFooter['button_url'], ENT_QUOTES, 'UTF-8'); ?>" 
                       class="btn btn-primary btn-lg"
                       style="display:inline-block;background:var(--lp-btn-primary, #3b82f6);color:#fff;text-decoration:none;padding:0.75rem 2rem;border-radius:99px;font-weight:600;transition:opacity 0.2s;">
                        <?php echo htmlspecialchars($landingFooter['button_text'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($landingFooter['copyright'])) : ?>
                <div style="margin-top:2rem;padding-top:2rem;border-top:1px solid #eee;font-size:0.9rem;color:#888;">
                    <?php echo htmlspecialchars($landingFooter['copyright']); ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

</main>

