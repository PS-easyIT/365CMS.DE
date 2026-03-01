<?php
/**
 * Theme Marketplace – Coming Soon
 *
 * Vorschau-Seite für den zukünftigen Theme-Marketplace.
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;

if (!defined('ABSPATH')) {
    exit;
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/partials/admin-menu.php';
renderAdminLayoutStart('Theme Marketplace', 'theme-marketplace');
?>

        <!-- Page Header -->
        <div class="admin-page-header">
            <h2>🛍️ Theme Marketplace</h2>
            <p style="color:#64748b;font-size:.875rem;margin:.25rem 0 0;">Professionelle Themes für dein 365CMS – kostenlos &amp; premium</p>
        </div>

        <!-- Coming Soon Info Box -->
        <div class="info-box-coming">
            <strong>⏳ Coming Soon – Theme Marketplace</strong>
            Der offizielle Theme Marketplace befindet sich derzeit in Entwicklung und wird in Kürze verfügbar sein.
            Bis dahin kannst du Themes manuell unter <a href="<?php echo SITE_URL; ?>/admin/themes">Themes & Design → Themes</a> verwalten
            und im <a href="<?php echo SITE_URL; ?>/admin/theme-customizer">Design Editor</a> anpassen.
        </div>

        <!-- Hero Banner -->
        <div class="cs-hero">
            <span class="cs-badge">🚀 Coming Soon</span>
            <h1>Theme Marketplace</h1>
            <p>
                Ein kuratierter Marktplatz für professionelle 365CMS-Themes – von eleganten Free-Themes
                der Community bis zu maßgeschneiderten Premium-Designs für jede Branche.
            </p>
        </div>

        <!-- Preview-Themes (Platzhalter) -->
        <h3 style="font-size:1rem;color:#1e293b;margin:0 0 1rem;">🎨 Themes-Vorschau (Beispiele)</h3>
        <div class="theme-preview-grid">
            <?php
            $previewThemes = [
                ['icon' => '🏢', 'bg' => '#dbeafe', 'name' => 'TechNexus', 'desc' => 'Modernes Tech-Theme für IT-Unternehmen', 'tag' => 'free'],
                ['icon' => '💼', 'bg' => '#fef3c7', 'name' => 'Business Pro', 'desc' => 'Professionelles Business-Design', 'tag' => 'premium'],
                ['icon' => '🎓', 'bg' => '#d1fae5', 'name' => 'Academy365', 'desc' => 'E-Learning & Kursplattform', 'tag' => 'premium'],
                ['icon' => '🏥', 'bg' => '#fce7f3', 'name' => 'MedCarePro', 'desc' => 'Healthcare & Praxis-Design', 'tag' => 'premium'],
                ['icon' => '🔗', 'bg' => '#ede9fe', 'name' => 'LogiLink', 'desc' => 'Logistik & Netzwerk-Theme', 'tag' => 'free'],
                ['icon' => '👤', 'bg' => '#e0f2fe', 'name' => 'PersonalFlow', 'desc' => 'Portfolio & Personal Brand', 'tag' => 'free'],
            ];
            foreach ($previewThemes as $t): ?>
            <div class="theme-preview-card tpc-coming">
                <div class="tpc-thumbnail" style="background:<?php echo $t['bg']; ?>;"><?php echo $t['icon']; ?></div>
                <span class="tpc-tag <?php echo $t['tag']; ?>"><?php echo $t['tag'] === 'free' ? 'Free' : 'Premium'; ?></span>
                <div class="tpc-body">
                    <h3><?php echo htmlspecialchars($t['name']); ?></h3>
                    <p><?php echo htmlspecialchars($t['desc']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Preismodell -->
        <div class="pricing-note">
            <h3>💶 Preis-Modell (Vorschau)</h3>
            <div class="pricing-tiers">
                <div class="pricing-tier free-tier">
                    <div class="pt-label">Free</div>
                    <div class="pt-price">0,00 €</div>
                    <div style="font-size:.75rem;color:#64748b;margin-top:.5rem;">Open Source / Community</div>
                </div>
                <div class="pricing-tier prem-tier">
                    <div class="pt-label">Starter</div>
                    <div class="pt-price">49,95 €</div>
                    <div style="font-size:.75rem;color:#64748b;margin-top:.5rem;">Einmalige Lizenz</div>
                </div>
                <div class="pricing-tier prem-tier">
                    <div class="pt-label">Business</div>
                    <div class="pt-price">149–499 €</div>
                    <div style="font-size:.75rem;color:#64748b;margin-top:.5rem;">Inkl. Support & Updates</div>
                </div>
                <div class="pricing-tier prem-tier">
                    <div class="pt-label">Enterprise</div>
                    <div class="pt-price">499–1499 €</div>
                    <div style="font-size:.75rem;color:#64748b;margin-top:.5rem;">Maßgeschneidert</div>
                </div>
            </div>
            <p style="font-size:.75rem;color:#94a3b8;margin:1rem 0 0;">
                Alle Premium-Themes: einmalige Lizenz, lebenslange Updates für 1 Jahr, danach optionaler Update-Schutz.
            </p>
        </div>

        <!-- Schnellzugriff -->
        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
            <a href="<?php echo SITE_URL; ?>/admin/themes" style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:#7c3aed;color:#fff;border-radius:8px;text-decoration:none;font-size:.875rem;font-weight:600;">
                🖼️ Themes verwalten
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/theme-customizer" style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:8px;text-decoration:none;font-size:.875rem;font-weight:600;">
                🎨 Design Editor
            </a>
        </div>

    </div><!-- /.admin-content -->

<?php renderAdminLayoutEnd(); ?>
