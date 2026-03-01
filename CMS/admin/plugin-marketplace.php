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
renderAdminLayoutStart('Plugin Marketplace', 'plugin-marketplace');
?>

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

<?php renderAdminLayoutEnd(); ?>
