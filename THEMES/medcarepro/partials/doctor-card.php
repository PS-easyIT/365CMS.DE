<?php
/**
 * Partial: Arzt-Karte
 *
 * Erwartet $doctor (array|object) mit:
 *   - id, name, title, specialty, specialty_slug, avatar_url,
 *     insurance (array: 'gkv', 'pkv'), rating, review_count,
 *     location, next_appointment, url
 *
 * Verwendung:
 *   <?php $doctor = [...]; include THEME_PATH . 'medcarepro/partials/doctor-card.php'; ?>
 *
 * @package MedCarePro
 */
if (!defined('ABSPATH')) exit;

$safe    = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$siteUrl = SITE_URL;

if (empty($doctor)) return;

$d          = (array)$doctor;
$id         = (int)($d['id']               ?? 0);
$name       = $safe($d['name']             ?? $d['display_name'] ?? '');
$title      = $safe($d['title']            ?? $d['academic_title'] ?? '');
$specialty  = $safe($d['specialty']        ?? $d['specialty_name'] ?? '');
$specSlug   = $safe($d['specialty_slug']   ?? strtolower(str_replace(' ', '', $specialty)));
$avatarUrl  = $safe($d['avatar_url']       ?? '');
$location   = $safe($d['location']         ?? $d['city'] ?? '');
$rating     = (float)($d['rating']         ?? 0);
$reviews    = (int)($d['review_count']     ?? 0);
$nextAppt   = $safe($d['next_appointment'] ?? '');
$profileUrl = $safe($d['url']              ?? ($siteUrl . '/arzt/' . $id));
$hasGkv     = !empty($d['insurance']['gkv']) || !empty($d['gkv']);
$hasPkv     = !empty($d['insurance']['pkv']) || !empty($d['pkv']);
$verified   = !empty($d['verified']);
?>
<article class="mc-card mc-doctor-card" aria-labelledby="doctor-<?php echo $id; ?>">
    <!-- Avatar -->
    <div style="display:flex;align-items:flex-start;gap:1rem;margin-bottom:.875rem;">
        <?php if (!empty($avatarUrl)) : ?>
        <img src="<?php echo $avatarUrl; ?>" alt="Profilbild von <?php echo $name; ?>"
             class="mc-doctor-avatar" width="80" height="80">
        <?php else : ?>
        <div class="mc-doctor-avatar" style="background:var(--bg-secondary);display:flex;align-items:center;justify-content:center;font-size:2rem;" aria-hidden="true">
            👨‍⚕️
        </div>
        <?php endif; ?>
        <div style="flex:1;min-width:0;">
            <h2 class="mc-doctor-name" id="doctor-<?php echo $id; ?>" style="font-size:var(--font-md);">
                <?php echo $title ? $title . ' ' . $name : $name; ?>
                <?php if ($verified) : ?>
                <span title="Verifizierter Arzt" aria-label="Verifizierter Arzt" style="color:var(--accent-color);font-size:var(--font-sm);">✅</span>
                <?php endif; ?>
            </h2>
            <?php if (!empty($specialty)) : ?>
            <p class="mc-doctor-title">
                <span class="mc-specialty-badge mc-specialty--<?php echo $specSlug; ?>">
                    <?php echo $specialty; ?>
                </span>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Versicherung -->
    <?php if ($hasGkv || $hasPkv) : ?>
    <div style="margin-bottom:.6rem;">
        <?php if ($hasGkv) : ?>
        <span class="mc-insurance-badge mc-insurance--gkv">GKV</span>
        <?php endif; ?>
        <?php if ($hasPkv) : ?>
        <span class="mc-insurance-badge mc-insurance--pkv">PKV</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Bewertung -->
    <?php if ($rating > 0) : ?>
    <div style="font-size:var(--font-sm);color:var(--muted-color);margin-bottom:.5rem;">
        <span style="color:#f59e0b;font-size:var(--font-md);">
            <?php echo str_repeat('★', (int)round($rating)) . str_repeat('☆', 5 - (int)round($rating)); ?>
        </span>
        <span><?php echo number_format($rating, 1); ?></span>
        <?php if ($reviews > 0) : ?>
        <span>(<?php echo $reviews; ?> Bewertungen)</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Standort -->
    <?php if (!empty($location)) : ?>
    <p style="font-size:var(--font-sm);color:var(--muted-color);margin-bottom:.5rem;">
        📍 <?php echo $location; ?>
    </p>
    <?php endif; ?>

    <!-- Nächster Termin -->
    <?php if (!empty($nextAppt)) : ?>
    <p style="font-size:var(--font-sm);color:var(--accent-color);font-weight:600;margin-bottom:.75rem;">
        🗓️ Nächster Termin: <?php echo $nextAppt; ?>
    </p>
    <?php endif; ?>

    <!-- Aktionen -->
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:auto;padding-top:.875rem;border-top:1px solid var(--border-color);">
        <a href="<?php echo $profileUrl; ?>" class="mc-btn mc-btn-outline" style="flex:1;min-width:0;justify-content:center;font-size:var(--font-xs);"
           aria-label="Profil von <?php echo $name; ?> anzeigen">
            Profil
        </a>
        <a href="<?php echo $safe($siteUrl . '/termin?doctor=' . $id); ?>" class="mc-btn mc-btn-primary" style="flex:1;min-width:0;justify-content:center;font-size:var(--font-xs);"
           aria-label="Termin bei <?php echo $name; ?> buchen">
            🗓️ Termin
        </a>
    </div>
</article>
