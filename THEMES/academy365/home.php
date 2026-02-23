<?php get_header(); ?>
<main id="main" class="ac-main-content" role="main">
<?php
$safe = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
try {
    $c = \CMS\Services\ThemeCustomizer::instance();
    // --- learning_hero ---
    $heroBadge       = $c->get('learning_hero', 'hero_badge',             'Die #1 Lernplattform');
    $heroHeadline    = $c->get('learning_hero', 'hero_headline',          'Lerne neue Skills.<br>Starte deine Karriere.');
    $heroSubline     = $c->get('learning_hero', 'hero_subline',           'Tausende Kurse von echten Experten – jederzeit und überall verfügbar.');
    $heroCta         = $c->get('learning_hero', 'hero_cta_label',         'Kurse entdecken');
    $heroCtaUrl      = $c->get('learning_hero', 'hero_cta_url',           SITE_URL . '/courses');
    $heroSecCta      = $c->get('learning_hero', 'hero_secondary_cta_label', 'Als Tutor anmelden');
    $heroSecCtaUrl   = $c->get('learning_hero', 'hero_secondary_cta_url',   SITE_URL . '/tutor-register');
    $coursesLabel    = $c->get('learning_hero', 'courses_label',  '2.400+ Kurse');
    $studentsLabel   = $c->get('learning_hero', 'students_label', '185.000 Lernende');
    $tutorsLabel     = $c->get('learning_hero', 'tutors_label',   '620 Tutoren');
    // --- learning_courses ---
    $coursesSectionTitle = $c->get('learning_courses', 'courses_section_title', 'Beliebte Kurse');
    $labelFree       = $c->get('learning_courses', 'badge_label_free',   'Kostenlos');
    $labelCert       = $c->get('learning_courses', 'badge_label_cert',   'Zertifikat');
    $labelNew        = $c->get('learning_courses', 'badge_label_new',    'Neu');
    $labelBest       = $c->get('learning_courses', 'badge_label_best',   'Bestseller');
    $showRating      = (bool) $c->get('learning_courses', 'show_rating', true);
    $showParticipants= (bool) $c->get('learning_courses', 'show_participants', true);
    $showDuration    = (bool) $c->get('learning_courses', 'show_duration', true);
    $showProgress    = (bool) $c->get('learning_courses', 'show_progress_bar', false);
    $enableGami      = (bool) $c->get('learning_courses', 'enable_gamification', true);
    // --- learning_content ---
    $tutorTitle      = $c->get('learning_content', 'tutor_section_title', 'Lerne von den Besten');
    $tutorSubline    = $c->get('learning_content', 'tutor_section_subline', 'Unsere Tutoren sind erfahrene Praktiker aus der Industrie.');
    $subscriptionCta = $c->get('learning_content', 'subscription_cta_title', 'Unbegrenztes Lernen mit Academy365 Pro');
    $subscriptionSub = $c->get('learning_content', 'subscription_cta_subline', 'Alle Kurse – ein Preis. Starte noch heute.');
    $subscriptionBtn = $c->get('learning_content', 'subscription_cta_button', 'Jetzt Pro werden');
    $subscriptionUrl = $c->get('learning_content', 'subscription_cta_url', SITE_URL . '/pro');
} catch (\Throwable $e) {
    $heroBadge = 'Die #1 Lernplattform'; $heroHeadline = 'Lerne neue Skills.<br>Starte deine Karriere.';
    $heroSubline = 'Tausende Kurse von echten Experten.'; $heroCta = 'Kurse entdecken';
    $heroCtaUrl = SITE_URL . '/courses'; $heroSecCta = 'Als Tutor anmelden'; $heroSecCtaUrl = SITE_URL . '/tutor-register';
    $coursesLabel = '2.400+ Kurse'; $studentsLabel = '185.000 Lernende'; $tutorsLabel = '620 Tutoren';
    $coursesSectionTitle = 'Beliebte Kurse'; $labelFree = 'Kostenlos'; $labelCert = 'Zertifikat';
    $labelNew = 'Neu'; $labelBest = 'Bestseller';
    $showRating = true; $showParticipants = true; $showDuration = true; $showProgress = false; $enableGami = true;
    $tutorTitle = 'Lerne von den Besten'; $tutorSubline = 'Unsere Tutoren sind erfahrene Praktiker.';
    $subscriptionCta = 'Unbegrenztes Lernen mit Academy365 Pro'; $subscriptionSub = 'Alle Kurse – ein Preis.';
    $subscriptionBtn = 'Jetzt Pro werden'; $subscriptionUrl = SITE_URL . '/pro';
}
?>

<!-- HERO -->
<section class="ac-hero" aria-label="Startseiten-Hero">
    <div class="ac-hero-content">
        <?php if ($heroBadge && trim($heroBadge) !== '') : ?>
            <span class="ac-hero-badge"><?php echo $safe($heroBadge); ?></span>
        <?php endif; ?>
        <h1 class="ac-hero-headline"><?php echo $heroHeadline; ?></h1>
        <?php if ($heroSubline && trim($heroSubline) !== '') : ?>
            <p class="ac-hero-subline"><?php echo $safe($heroSubline); ?></p>
        <?php endif; ?>
        <div class="ac-hero-ctas">
            <a href="<?php echo $safe($heroCtaUrl); ?>" class="ac-btn ac-btn-primary ac-btn-lg"><?php echo $safe($heroCta); ?></a>
            <?php if ($heroSecCta && trim($heroSecCta) !== '') : ?>
                <a href="<?php echo $safe($heroSecCtaUrl); ?>" class="ac-btn ac-btn-outline-light"><?php echo $safe($heroSecCta); ?></a>
            <?php endif; ?>
        </div>
    </div>
    <div class="ac-hero-stats" aria-label="Plattform-Statistiken">
        <?php foreach ([$coursesLabel, $studentsLabel, $tutorsLabel] as $stat) : ?>
            <?php if ($stat && trim($stat) !== '') : ?>
                <div class="ac-stat-item"><?php echo $safe($stat); ?></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>

<!-- COURSES -->
<section class="ac-section ac-courses-section" aria-labelledby="courses-heading">
    <div class="ac-container">
        <div class="ac-section-head">
            <h2 id="courses-heading"><?php echo $safe($coursesSectionTitle); ?></h2>
            <a href="<?php echo $safe(SITE_URL . '/courses'); ?>" class="ac-link-arrow">Alle Kurse →</a>
        </div>
        <?php if ($enableGami) : ?>
            <div class="ac-gami-hint">
                <span class="ac-xp-badge">XP</span>
                Sammle Punkte und schalte Abzeichen frei, je mehr du lernst!
            </div>
        <?php endif; ?>
        <!-- Course cards rendered by CMS course loop -->
        <div class="ac-courses-grid">
            <?php
            // Placeholder course cards — replace with real CMS loop
            $sampleCourses = [
                ['title'=>'PHP für Einsteiger','category'=>'tech','badge'=>'new',   'level'=>'Anfänger', 'duration'=>'8h','rating'=>4.7,'participants'=>1230,'progress'=>40],
                ['title'=>'UX Design Grundlagen','category'=>'design','badge'=>'bestseller','level'=>'Alle', 'duration'=>'12h','rating'=>4.9,'participants'=>3400,'progress'=>0],
                ['title'=>'Agiles Projektmanagement','category'=>'business','badge'=>'cert','level'=>'Fortgeschritten','duration'=>'6h','rating'=>4.6,'participants'=>890,'progress'=>0],
                ['title'=>'Business English','category'=>'language','badge'=>'free','level'=>'Mittelstufe','duration'=>'5h','rating'=>4.5,'participants'=>2100,'progress'=>75],
            ];
            foreach ($sampleCourses as $course) :
                $badgeClass = 'ac-badge--' . $course['badge'];
                $badgeLabel = match($course['badge']) {
                    'free'       => $labelFree,
                    'cert'       => $labelCert,
                    'new'        => $labelNew,
                    'bestseller' => $labelBest,
                    default      => '',
                };
            ?>
            <article class="ac-card ac-cat--<?php echo $safe($course['category']); ?>">
                <div class="ac-card-thumb">
                    <?php if ($badgeLabel !== '') : ?>
                        <span class="ac-badge <?php echo $safe($badgeClass); ?>"><?php echo $safe($badgeLabel); ?></span>
                    <?php endif; ?>
                </div>
                <div class="ac-card-body">
                    <span class="ac-card-level"><?php echo $safe($course['level']); ?></span>
                    <h3 class="ac-card-title"><?php echo $safe($course['title']); ?></h3>
                    <div class="ac-card-meta">
                        <?php if ($showDuration && $course['duration']) : ?>
                            <span>⏱ <?php echo $safe($course['duration']); ?></span>
                        <?php endif; ?>
                        <?php if ($showRating) : ?>
                            <span class="ac-rating" title="<?php echo $safe((string)$course['rating']); ?> / 5">
                                <span class="ac-rating-stars" style="--rating:<?php echo $safe((string)$course['rating']); ?>" aria-hidden="true"></span>
                                <?php echo $safe((string)$course['rating']); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($showParticipants) : ?>
                            <span>👥 <?php echo number_format($course['participants']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($showProgress && $course['progress'] > 0) : ?>
                        <div class="ac-progress" role="progressbar" aria-valuenow="<?php echo $course['progress']; ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="ac-progress-bar" data-progress="<?php echo $course['progress']; ?>" style="width:<?php echo $course['progress']; ?>%"></div>
                        </div>
                        <small><?php echo $course['progress']; ?>% abgeschlossen</small>
                    <?php endif; ?>
                    <a href="<?php echo $safe(SITE_URL . '/courses/' . sanitize_title($course['title'])); ?>" class="ac-btn ac-btn-secondary ac-btn-sm ac-mt-1">Zum Kurs</a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- TUTORS -->
<section class="ac-section ac-tutors-section ac-bg-light" aria-labelledby="tutors-heading">
    <div class="ac-container">
        <div class="ac-section-head">
            <h2 id="tutors-heading"><?php echo $safe($tutorTitle); ?></h2>
            <?php if ($tutorSubline && trim($tutorSubline) !== '') : ?>
                <p class="ac-section-subline"><?php echo $safe($tutorSubline); ?></p>
            <?php endif; ?>
        </div>
        <!-- Tutor cards rendered by CMS loop -->
        <div class="ac-tutors-grid">
            <?php for ($i = 0; $i < 4; $i++) : ?>
            <div class="ac-tutor-card ac-card">
                <div class="ac-tutor-avatar"><span aria-hidden="true">👩‍🏫</span></div>
                <div class="ac-card-body">
                    <h3 class="ac-card-title">Tutor <?php echo $i + 1; ?></h3>
                    <p class="ac-muted">Experte für IT &amp; Entwicklung</p>
                    <?php if ($showRating) : ?>
                        <div class="ac-rating" aria-label="Bewertung">
                            <span class="ac-rating-stars" style="--rating:4.8" aria-hidden="true"></span>
                            4.8
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endfor; ?>
        </div>
        <div style="text-align:center;margin-top:2rem;">
            <a href="<?php echo $safe(SITE_URL . '/tutors'); ?>" class="ac-btn ac-btn-secondary">Alle Tutoren →</a>
        </div>
    </div>
</section>

<!-- SUBSCRIPTION CTA -->
<section class="ac-cta-section" aria-labelledby="cta-heading">
    <div class="ac-container" style="text-align:center;">
        <?php if ($enableGami) : ?>
            <span class="ac-xp-badge ac-mb-1">PRO</span>
        <?php endif; ?>
        <h2 id="cta-heading"><?php echo $safe($subscriptionCta); ?></h2>
        <?php if ($subscriptionSub && trim($subscriptionSub) !== '') : ?>
            <p class="ac-hero-subline" style="color:rgba(255,255,255,.85);"><?php echo $safe($subscriptionSub); ?></p>
        <?php endif; ?>
        <a href="<?php echo $safe($subscriptionUrl); ?>" class="ac-btn ac-btn-accent ac-btn-lg"><?php echo $safe($subscriptionBtn); ?></a>
    </div>
</section>

</main>
<?php get_footer(); ?>
