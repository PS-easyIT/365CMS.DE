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
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Marketplace – <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        /* ── Coming Soon Hero ── */
        .cs-hero {
            background: linear-gradient(135deg, #1a0533 0%, #3b1f6e 50%, #1a0533 100%);
            border-radius: 16px;
            padding: 3rem 2.5rem;
            text-align: center;
            color: #f1f5f9;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        .cs-hero::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 220px; height: 220px;
            background: rgba(167,139,250,0.15);
            border-radius: 50%;
        }
        .cs-hero::after {
            content: '';
            position: absolute;
            bottom: -40px; left: -40px;
            width: 160px; height: 160px;
            background: rgba(236,72,153,0.1);
            border-radius: 50%;
        }
        .cs-badge {
            display: inline-block;
            background: rgba(167,139,250,0.25);
            border: 1px solid rgba(167,139,250,0.5);
            color: #c4b5fd;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            margin-bottom: 1.25rem;
        }
        .cs-hero h1 {
            font-size: 2rem;
            font-weight: 800;
            margin: 0 0 0.75rem;
            color: #f8fafc;
        }
        .cs-hero p {
            font-size: 1rem;
            color: #94a3b8;
            max-width: 560px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* ── Info Box ── */
        .info-box-coming {
            background: #fefce8;
            border: 1px solid #fde68a;
            border-left: 4px solid #f59e0b;
            border-radius: 8px;
            padding: 1.125rem 1.5rem;
            margin-bottom: 2rem;
            font-size: 0.875rem;
            color: #78350f;
            line-height: 1.6;
        }
        .info-box-coming strong {
            display: block;
            font-size: 0.9375rem;
            margin-bottom: 0.375rem;
            color: #92400e;
        }
        .info-box-coming a { color: #b45309; font-weight: 600; }

        /* ── Preview Cards (Theme Previews) ── */
        .theme-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        .theme-preview-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        .tpc-thumbnail {
            height: 130px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }
        .tpc-body { padding: 1rem; }
        .tpc-body h3 { font-size: 0.9rem; font-weight: 700; color: #1e293b; margin: 0 0 0.3rem; }
        .tpc-body p  { font-size: 0.775rem; color: #64748b; margin: 0; }
        .tpc-tag {
            position: absolute;
            top: .6rem; right: .6rem;
            font-size: 0.65rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .04em;
            padding: .2rem .5rem; border-radius: 10px;
        }
        .tpc-tag.free    { background: #dcfce7; color: #166534; }
        .tpc-tag.premium { background: #fef3c7; color: #92400e; }
        .tpc-coming { opacity: .6; }

        /* ── Pricing Preview ── */
        .pricing-note {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .pricing-note h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 1rem; }
        .pricing-tiers {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1rem;
        }
        .pricing-tier {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;
            padding: 1rem; text-align: center;
        }
        .pt-label { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #64748b; margin-bottom: .5rem; }
        .pt-price  { font-size: 1.25rem; font-weight: 800; color: #1e293b; }
        .free-tier  .pt-price { color: #16a34a; }
        .prem-tier  .pt-price { color: #b45309; }
    </style>
</head>
<body class="admin-body">

    <?php renderAdminSidebar('theme-marketplace'); ?>

    <div class="admin-content">

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

</body>
</html>
