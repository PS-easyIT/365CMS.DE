<?php
if (!defined('ABSPATH')) exit;
try { $c = \CMS\Services\ThemeCustomizer::instance(); } catch (\Throwable $e) { $c = null; }

// Hero
$heroBadge    = $c?->get('medical_hero', 'hero_badge',               '✚ Gesundheitsplattform')      ?? '✚ Gesundheitsplattform';
$heroHeadline = $c?->get('medical_hero', 'hero_headline',            'Ihren Arzt einfach online finden') ?? 'Ihren Arzt einfach online finden';
$heroSubline  = $c?->get('medical_hero', 'hero_subline',             'Ärzte, Kliniken und Fachspezialisten in Ihrer Region – schnell, sicher und kostenlos.') ?? '';
$heroCta      = $c?->get('medical_hero', 'hero_cta_label',           'Arzt suchen')        ?? 'Arzt suchen';
$heroCtaUrl   = $c?->get('medical_hero', 'hero_cta_url',             '/aerzte')            ?? '/aerzte';
$heroSecCta   = $c?->get('medical_hero', 'hero_secondary_cta_label', 'Termin buchen')      ?? 'Termin buchen';
$heroSecUrl   = $c?->get('medical_hero', 'hero_secondary_cta_url',   '/termin')            ?? '/termin';
$showStats    = $c?->get('medical_hero', 'show_stats_bar',           true)                 ?? true;
$statNum1     = $c?->get('medical_hero', 'stat_doctors_count',       '1.500+')             ?? '1.500+';
$statLbl1     = $c?->get('medical_hero', 'stat_doctors_label',       'Ärzte & Kliniken')   ?? 'Ärzte & Kliniken';
$statNum2     = $c?->get('medical_hero', 'stat_specialties_count',   '40+')                ?? '40+';
$statLbl2     = $c?->get('medical_hero', 'stat_specialties_label',   'Fachgebiete')        ?? 'Fachgebiete';
$statLbl3     = $c?->get('medical_hero', 'stat_booking_label',       'Online-Termine')     ?? 'Online-Termine';

// Content
$doctorsTitle = $c?->get('medical_content', 'doctor_section_title',      'Unsere Fachärzte')           ?? 'Unsere Fachärzte';
$specTitle    = $c?->get('medical_content', 'specialties_section_title', 'Medizinische Fachbereiche')  ?? 'Medizinische Fachbereiche';
$bookingTitle = $c?->get('medical_content', 'booking_section_title',     'Termin vereinbaren')         ?? 'Termin vereinbaren';
$bookingText  = $c?->get('medical_content', 'booking_intro_text',        'Online-Terminbuchung rund um die Uhr – ohne Warteschleife.') ?? '';
$gkvLabel     = $c?->get('medical_content', 'insurance_label_public',    'Kassenpatient (GKV)')        ?? 'Kassenpatient (GKV)';
$pkvLabel     = $c?->get('medical_content', 'insurance_label_private',   'Privatpatient (PKV)')        ?? 'Privatpatient (PKV)';
$emergInfo    = $c?->get('medical_content', 'emergency_info_text',       '')                           ?? '';
$ctaTitle     = $c?->get('medical_content', 'cta_section_title',         'Ihr Online-Patientenportal') ?? 'Ihr Online-Patientenportal';
$ctaText      = $c?->get('medical_content', 'cta_section_text',          '')                           ?? '';

$siteUrl    = SITE_URL;
$isLoggedIn = theme_is_logged_in();
$safe       = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
<main id="main" class="mc-main" role="main">

    <!-- Notfall-Info Banner -->
    <?php if (!empty($emergInfo)) : ?>
    <div class="mc-emergency-notice" role="alert" aria-live="polite">
        <div class="mc-container" style="display:flex;align-items:center;justify-content:center;gap:.75rem;flex-wrap:wrap;">
            <strong>⚕️ Wichtiger Hinweis:</strong>
            <span><?php echo $safe($emergInfo); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══ Hero ═══════════════════════════════════════════════════════════ -->
    <section class="mc-hero" aria-labelledby="hero-heading">
        <div class="mc-container">
            <?php if (!empty($heroBadge)) : ?>
                <div class="mc-hero-badge"><?php echo $safe($heroBadge); ?></div>
            <?php endif; ?>
            <h1 id="hero-heading"><?php echo $safe($heroHeadline); ?></h1>
            <?php if (!empty($heroSubline)) : ?>
                <p class="mc-hero-sub"><?php echo $safe($heroSubline); ?></p>
            <?php endif; ?>

            <!-- Hero Sucheingabe -->
            <form class="mc-hero-search" role="search" method="get"
                  action="<?php echo $safe($siteUrl); ?>/aerzte" aria-label="Arzt suchen">
                <label for="hero-search-input" class="mc-visually-hidden">Arzt, Fachgebiet oder PLZ eingeben</label>
                <input id="hero-search-input" type="search" name="q"
                       placeholder="Arzt, Fachgebiet oder PLZ …"
                       autocomplete="off" class="mc-hero-search-input">
                <button type="submit" class="mc-btn mc-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    <?php echo $safe($heroCta); ?>
                </button>
            </form>

            <div class="mc-cta-group">
                <a href="<?php echo $safe($siteUrl . $heroSecUrl); ?>" class="mc-btn mc-btn-secondary">
                    🗓️ <?php echo $safe($heroSecCta); ?>
                </a>
                <a href="<?php echo $safe($siteUrl . $heroCtaUrl); ?>" class="mc-btn mc-btn-white">
                    Alle Ärzte anzeigen
                </a>
            </div>

            <?php if ($showStats) : ?>
            <div class="mc-stats-row" aria-label="Plattform-Statistiken">
                <div class="mc-stat">
                    <span class="mc-stat-number"><?php echo $safe($statNum1); ?></span>
                    <span class="mc-stat-label"><?php echo $safe($statLbl1); ?></span>
                </div>
                <div class="mc-stat">
                    <span class="mc-stat-number"><?php echo $safe($statNum2); ?></span>
                    <span class="mc-stat-label"><?php echo $safe($statLbl2); ?></span>
                </div>
                <div class="mc-stat">
                    <span class="mc-stat-number">24/7</span>
                    <span class="mc-stat-label"><?php echo $safe($statLbl3); ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section><!-- /.mc-hero -->

    <!-- ═══ Fachgebiete ════════════════════════════════════════════════════ -->
    <section class="mc-section" aria-labelledby="specialties-heading">
        <div class="mc-container">
            <div class="mc-section-header">
                <h2 id="specialties-heading"><?php echo $safe($specTitle); ?></h2>
                <p>Kompetente Versorgung in allen medizinischen Fachbereichen</p>
            </div>
            <?php
            $specialties = [
                ['slug' => 'allgemein',    'label' => 'Allgemeinmedizin', 'icon' => '🩺', 'url' => '/fachgebiet/allgemeinmedizin'],
                ['slug' => 'kardiologie',  'label' => 'Kardiologie',      'icon' => '❤️', 'url' => '/fachgebiet/kardiologie'],
                ['slug' => 'neurologie',   'label' => 'Neurologie',       'icon' => '🧠', 'url' => '/fachgebiet/neurologie'],
                ['slug' => 'orthopaedie',  'label' => 'Orthopädie',       'icon' => '🦴', 'url' => '/fachgebiet/orthopaedie'],
                ['slug' => 'dermatologie', 'label' => 'Dermatologie',     'icon' => '🔬', 'url' => '/fachgebiet/dermatologie'],
                ['slug' => 'zahn',         'label' => 'Zahnmedizin',      'icon' => '🦷', 'url' => '/fachgebiet/zahnmedizin'],
                ['slug' => 'psychologie',  'label' => 'Psychologie',      'icon' => '🧘', 'url' => '/fachgebiet/psychologie'],
            ];
            ?>
            <div class="mc-specialties-grid">
                <?php foreach ($specialties as $sp) : ?>
                <a href="<?php echo $safe($siteUrl . $sp['url']); ?>"
                   class="mc-specialty-card"
                   aria-label="Fachgebiet <?php echo $safe($sp['label']); ?> anzeigen">
                    <span class="mc-specialty-icon" aria-hidden="true"><?php echo $sp['icon']; ?></span>
                    <span class="mc-specialty-name mc-specialty--<?php echo $sp['slug']; ?>">
                        <?php echo $safe($sp['label']); ?>
                    </span>
                </a>
                <?php endforeach; ?>
                <a href="<?php echo $safe($siteUrl); ?>/fachgebiete"
                   class="mc-specialty-card mc-specialty-card--more"
                   aria-label="Alle Fachgebiete anzeigen">
                    <span class="mc-specialty-icon" aria-hidden="true">→</span>
                    <span>Alle Fachgebiete</span>
                </a>
            </div>
        </div>
    </section><!-- /.mc-specialties -->

    <!-- ═══ Ärzte ══════════════════════════════════════════════════════════ -->
    <section class="mc-section mc-section--alt" aria-labelledby="doctors-heading">
        <div class="mc-container">
            <div class="mc-section-header">
                <h2 id="doctors-heading"><?php echo $safe($doctorsTitle); ?></h2>
                <p>Qualifizierte Experten für Ihre Gesundheit – geprüft und zertifiziert</p>
            </div>
            <!-- Versicherungsfilter -->
            <div class="mc-insurance-filter" role="group" aria-label="Nach Versicherungstyp filtern">
                <button class="mc-insurance-btn active" data-insurance="all" aria-pressed="true">Alle</button>
                <button class="mc-insurance-btn" data-insurance="gkv" aria-pressed="false">
                    <span class="mc-insurance-badge mc-insurance--gkv"><?php echo $safe($gkvLabel); ?></span>
                </button>
                <button class="mc-insurance-btn" data-insurance="pkv" aria-pressed="false">
                    <span class="mc-insurance-badge mc-insurance--pkv"><?php echo $safe($pkvLabel); ?></span>
                </button>
            </div>
            <div class="mc-grid" style="margin-top:1.5rem;">
                <div class="mc-card" style="text-align:center;grid-column:1/-1;padding:2.5rem 2rem;">
                    <div style="font-size:3rem;margin-bottom:.75rem;" aria-hidden="true">👨‍⚕️</div>
                    <h3 style="font-family:var(--font-heading);color:var(--secondary-color);margin-bottom:.5rem;">
                        Alle Ärzte &amp; Therapeuten entdecken
                    </h3>
                    <p style="color:var(--muted-color);max-width:500px;margin:0 auto 1.25rem;">
                        Finden Sie den passenden Spezialisten in Ihrer Nähe – mit Bewertungen, Öffnungszeiten und Online-Terminbuchung.
                    </p>
                    <a href="<?php echo $safe($siteUrl); ?>/aerzte" class="mc-btn mc-btn-primary">
                        Alle Ärzte anzeigen →
                    </a>
                </div>
            </div>
        </div>
    </section><!-- /.mc-doctors -->

    <!-- ═══ Termin-CTA ═════════════════════════════════════════════════════ -->
    <?php if (!$isLoggedIn) : ?>
    <section class="mc-section" aria-labelledby="booking-heading">
        <div class="mc-container">
            <div class="mc-booking-cta">
                <div class="mc-booking-cta__text">
                    <h2 id="booking-heading"><?php echo $safe($bookingTitle); ?></h2>
                    <?php if (!empty($bookingText)) : ?>
                        <p><?php echo $safe($bookingText); ?></p>
                    <?php endif; ?>
                    <ul class="mc-booking-features">
                        <li>✓ Sofortige Online-Buchung – 24 Stunden, 7 Tage die Woche</li>
                        <li>✓ Automatische Erinnerungen per E-Mail</li>
                        <li>✓ DSGVO-konforme Datenhaltung nach § 203 StGB</li>
                        <li>✓ Für GKV- und PKV-Patienten verfügbar</li>
                    </ul>
                </div>
                <div class="mc-booking-cta__actions">
                    <a href="<?php echo $safe($siteUrl); ?>/termin" class="mc-btn mc-btn-primary mc-btn-lg">
                        🗓️ Termin buchen
                    </a>
                    <a href="<?php echo $safe($siteUrl); ?>/register" class="mc-btn mc-btn-outline"
                       style="margin-top:.75rem;">
                        Als Arzt registrieren
                    </a>
                    <p style="font-size:var(--font-xs);color:var(--muted-color);margin-top:.75rem;text-align:center;">
                        Ihre Daten werden gemäß DSGVO &amp; § 203 StGB geschützt.
                    </p>
                </div>
            </div>
        </div>
    </section><!-- /.mc-booking-cta -->
    <?php endif; ?>

    <!-- ═══ Trust / Vertrauenssignale ═════════════════════════════════════ -->
    <section class="mc-section mc-section--alt" aria-labelledby="trust-heading">
        <div class="mc-container">
            <div class="mc-section-header">
                <h2 id="trust-heading">Warum MedCare Pro?</h2>
                <p>Qualität, Datenschutz und Verlässlichkeit – für Patienten und Ärzte</p>
            </div>
            <div class="mc-trust-grid">
                <div class="mc-trust-item">
                    <div class="mc-trust-icon" aria-hidden="true">🔒</div>
                    <h3>DSGVO-konform</h3>
                    <p>Alle Patientendaten werden nach höchsten Datenschutzstandards verarbeitet. Keine Datenweitergabe an Dritte.</p>
                </div>
                <div class="mc-trust-item">
                    <div class="mc-trust-icon" aria-hidden="true">✅</div>
                    <h3>Geprüfte Ärzte</h3>
                    <p>Jedes Arztprofil wird vor der Freischaltung auf Approbation und Qualifikation verifiziert.</p>
                </div>
                <div class="mc-trust-item">
                    <div class="mc-trust-icon" aria-hidden="true">📱</div>
                    <h3>Online-Buchung 24/7</h3>
                    <p>Termin online vereinbaren – ohne Warteschleife, rund um die Uhr verfügbar.</p>
                </div>
                <div class="mc-trust-item">
                    <div class="mc-trust-icon" aria-hidden="true">⭐</div>
                    <h3>Echte Bewertungen</h3>
                    <p>Verifizierte Patientenbewertungen helfen, den richtigen Arzt für Ihre Bedürfnisse zu finden.</p>
                </div>
            </div>
        </div>
    </section><!-- /.mc-trust -->

    <!-- ═══ Registrierungs-CTA ═════════════════════════════════════════════ -->
    <?php if (!$isLoggedIn && !empty(trim($ctaTitle))) : ?>
    <section class="mc-section" aria-labelledby="cta-heading">
        <div class="mc-container" style="text-align:center;">
            <h2 id="cta-heading"><?php echo $safe($ctaTitle); ?></h2>
            <?php if (!empty(trim($ctaText))) : ?>
                <p style="color:var(--text-secondary);max-width:600px;margin:.75rem auto 1.75rem;">
                    <?php echo $safe($ctaText); ?>
                </p>
            <?php else : ?>
                <p style="color:var(--text-secondary);max-width:600px;margin:.75rem auto 1.75rem;">
                    Registrieren Sie sich und verwalten Sie Termine, Befunde und Nachrichten sicher und papierlos.
                </p>
            <?php endif; ?>
            <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                <a href="<?php echo $safe($siteUrl); ?>/register" class="mc-btn mc-btn-primary mc-btn-lg">
                    Jetzt kostenlos registrieren
                </a>
                <a href="<?php echo $safe($siteUrl); ?>/aerzte" class="mc-btn mc-btn-outline">
                    Arzt suchen
                </a>
            </div>
        </div>
    </section><!-- /.mc-cta-section -->
    <?php endif; ?>

</main><!-- #main -->
