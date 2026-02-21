<?php
/**
 * Plugin Marketplace – Coming Soon
 *
 * Vorschau-Seite für den zukünftigen Plugin-Marketplace.
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
    <title>Plugin Marketplace – <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        /* ── Coming Soon Banner ── */
        .cs-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);
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
            background: rgba(99,102,241,0.15);
            border-radius: 50%;
        }
        .cs-hero::after {
            content: '';
            position: absolute;
            bottom: -40px; left: -40px;
            width: 160px; height: 160px;
            background: rgba(16,185,129,0.1);
            border-radius: 50%;
        }
        .cs-badge {
            display: inline-block;
            background: rgba(99,102,241,0.25);
            border: 1px solid rgba(99,102,241,0.5);
            color: #a5b4fc;
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
        .info-box-coming a {
            color: #b45309;
            font-weight: 600;
        }

        /* ── Feature Cards ── */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        .feature-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.5rem;
            position: relative;
        }
        .feature-card .fc-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            display: block;
        }
        .feature-card h3 {
            font-size: 0.9375rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 0.5rem;
        }
        .feature-card p {
            font-size: 0.8125rem;
            color: #64748b;
            margin: 0;
            line-height: 1.5;
        }
        .feature-card .fc-tag {
            position: absolute;
            top: 0.75rem; right: 0.75rem;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
        }
        .fc-tag.free    { background: #dcfce7; color: #166534; }
        .fc-tag.premium { background: #fef3c7; color: #92400e; }

        /* ── Pricing Preview ── */
        .pricing-note {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .pricing-note h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 1rem;
        }
        .pricing-tiers {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
        }
        .pricing-tier {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
        .pricing-tier .pt-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        .pricing-tier .pt-price {
            font-size: 1.25rem;
            font-weight: 800;
            color: #1e293b;
        }
        .pricing-tier.free-tier  .pt-price { color: #16a34a; }
        .pricing-tier.prem-tier  .pt-price { color: #b45309; }
    </style>
</head>
<body class="admin-body">

    <?php renderAdminSidebar('plugin-marketplace'); ?>

    <div class="admin-content">

        <!-- Page Header -->
        <div class="admin-page-header">
            <h2>🏪 Plugin Marketplace</h2>
            <p style="color:#64748b;font-size:0.875rem;margin:.25rem 0 0;">Entdecke und installiere Erweiterungen für dein CMS</p>
        </div>

        <!-- Coming Soon Info Box -->
        <div class="info-box-coming">
            <strong>⏳ Coming Soon – Plugin Marketplace</strong>
            Der offizielle Plugin Marketplace befindet sich derzeit in Entwicklung und wird in Kürze verfügbar sein.
            Bis dahin kannst du Plugins manuell unter <a href="<?php echo SITE_URL; ?>/admin/plugins">Plugins → Verwalten</a> hochladen
            und unter <a href="<?php echo SITE_URL; ?>/admin/updates">Plugins → Installieren &amp; Updates</a> aktualisieren.
            Schau dir das <a href="<?php echo SITE_URL; ?>/admin/docs/marketplace-konzept" onclick="return false;">Marketplace-Konzept (Doku)</a> an,
            um zu sehen, wie der Marketplace realisiert wird.
        </div>

        <!-- Hero Banner -->
        <div class="cs-hero">
            <span class="cs-badge">🚀 Coming Soon</span>
            <h1>Plugin Marketplace</h1>
            <p>
                Ein kuratierter Marktplatz für 365CMS-Plugins – von kostenlosen Community-Plugins
                bis hin zu leistungsstarken Premium-Erweiterungen. Alles auf Knopfdruck installierbar.
            </p>
        </div>

        <!-- Geplante Features -->
        <h3 style="font-size:1rem;color:#1e293b;margin:0 0 1rem;">🗺️ Geplante Features</h3>
        <div class="features-grid">
            <div class="feature-card">
                <span class="fc-tag free">Free</span>
                <span class="fc-icon">🔍</span>
                <h3>Suche & Filter</h3>
                <p>Plugins nach Kategorie, Bewertung und Kompatibilität filtern.</p>
            </div>
            <div class="feature-card">
                <span class="fc-tag free">Free</span>
                <span class="fc-icon">⚡</span>
                <h3>1-Klick-Installation</h3>
                <p>Direkte Installation aus dem Marketplace ohne manuellen Upload.</p>
            </div>
            <div class="feature-card">
                <span class="fc-tag premium">Premium</span>
                <span class="fc-icon">🔑</span>
                <h3>Lizenz-Management</h3>
                <p>Automatische Aktivierung und Verwaltung von Premium-Lizenzen.</p>
            </div>
            <div class="feature-card">
                <span class="fc-tag free">Free</span>
                <span class="fc-icon">🔄</span>
                <h3>Auto-Updates</h3>
                <p>Updates für Marketplace-Plugins direkt aus dem Admin-Bereich.</p>
            </div>
            <div class="feature-card">
                <span class="fc-tag premium">Premium</span>
                <span class="fc-icon">⭐</span>
                <h3>Reviews & Ratings</h3>
                <p>Community-Bewertungen und verifizierte Nutzerbewertungen.</p>
            </div>
            <div class="feature-card">
                <span class="fc-tag free">Free</span>
                <span class="fc-icon">📦</span>
                <h3>Versionsverwaltung</h3>
                <p>Rollback auf frühere Plugin-Versionen jederzeit möglich.</p>
            </div>
        </div>

        <!-- Preismodell -->
        <div class="pricing-note">
            <h3>💶 Preis-Modell (Vorschau)</h3>
            <div class="pricing-tiers">
                <div class="pricing-tier free-tier">
                    <div class="pt-label">Free</div>
                    <div class="pt-price">0,00 €</div>
                    <div style="font-size:0.75rem;color:#64748b;margin-top:.5rem;">Open Source / Community</div>
                </div>
                <div class="pricing-tier prem-tier">
                    <div class="pt-label">Starter</div>
                    <div class="pt-price">49,95 €</div>
                    <div style="font-size:0.75rem;color:#64748b;margin-top:.5rem;">Einmalige Lizenz</div>
                </div>
                <div class="pricing-tier prem-tier">
                    <div class="pt-label">Professional</div>
                    <div class="pt-price">149–499 €</div>
                    <div style="font-size:0.75rem;color:#64748b;margin-top:.5rem;">Erweiterte Funktionen</div>
                </div>
                <div class="pricing-tier prem-tier">
                    <div class="pt-label">Enterprise</div>
                    <div class="pt-price">499–1499 €</div>
                    <div style="font-size:0.75rem;color:#64748b;margin-top:.5rem;">Vollumfängliche Suites</div>
                </div>
            </div>
            <p style="font-size:0.75rem;color:#94a3b8;margin:1rem 0 0;">
                Alle Premium-Plugins: einmalige Lizenz, lebenslange Updates für 1 Jahr, danach optionaler Update-Schutz.
            </p>
        </div>

        <!-- Schnellzugriff -->
        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
            <a href="<?php echo SITE_URL; ?>/admin/plugins" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;font-size:.875rem;font-weight:600;">
                🔌 Plugins verwalten
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/updates" style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:8px;text-decoration:none;font-size:.875rem;font-weight:600;">
                🔄 Installieren & Updates
            </a>
        </div>

    </div><!-- /.admin-content -->

</body>
</html>
