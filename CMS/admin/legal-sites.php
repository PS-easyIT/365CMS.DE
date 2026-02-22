<?php
/**
 * Rechtstexte Generator
 * 
 * Automatische Erstellung von Impressum, Datenschutz und Cookie-Richtlinie
 * 
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';
require_once dirname(__DIR__) . '/includes/functions.php';

use CMS\Auth;
use CMS\Database;
use CMS\Security;

if (!defined('ABSPATH')) { exit; }
// Nur Admins dürfen diese Seite sehen
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

// Load Admin Menu & Layout Helpers
require_once __DIR__ . '/partials/admin-menu.php';

$db = Database::instance();
$message = '';
$messageType = '';

// Handle Generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_legal'])) {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'generate_legal')) {
        $message = 'Sicherheitsprüfung fehlgeschlagen.';
        $messageType = 'error';
    } else {
        // Collect Data
        $company  = sanitize_text_field($_POST['company']);
        $address  = sanitize_text_field($_POST['address']);
        $email    = sanitize_email($_POST['email']);
        $phone    = sanitize_text_field($_POST['phone']);
        $owner    = sanitize_text_field($_POST['owner']);
        $registry = sanitize_text_field($_POST['registry']);
        $vat_id   = sanitize_text_field($_POST['vat_id']);

        // 1. Generate Impressum
        $impressumContent = "<h2>Angaben gemäß § 5 TMG</h2>";
        $impressumContent .= "<p>{$company}<br>{$address}</p>";
        $impressumContent .= "<h2>Kontakt</h2>";
        $impressumContent .= "<p>Telefon: {$phone}<br>E-Mail: {$email}</p>";
        if($registry) {
            $impressumContent .= "<h2>Registereintrag</h2>";
            $impressumContent .= "<p>Eintragung im Handelsregister.<br>Registergericht: Amtsgericht<br>Registernummer: {$registry}</p>";
        }
        if($vat_id) {
            $impressumContent .= "<h2>Umsatzsteuer-ID</h2>";
            $impressumContent .= "<p>Umsatzsteuer-Identifikationsnummer gemäß § 27 a Umsatzsteuergesetz:<br>{$vat_id}</p>";
        }
        if($owner) {
             $impressumContent .= "<h2>Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV</h2>";
             $impressumContent .= "<p>{$owner}<br>{$address}</p>";
        }

        // Save Impressum
        $existingImp = $db->fetchOne("SELECT id FROM {$db->getPrefix()}posts WHERE slug = 'impressum'");
        if ($existingImp) {
            $db->update('posts', ['content' => $impressumContent, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $existingImp['id']]);
        } else {
            $db->insert('posts', [
                'title' => 'Impressum',
                'slug' => 'impressum',
                'content' => $impressumContent,
                'status' => 'published',
                'author_id' => Auth::instance()->getCurrentUser()->id,
                'published_at' => date('Y-m-d H:i:s')
            ]);
        }

        // 2. Generate Privacy (Basic Template)
        $privacyContent = "<h2>Datenschutzerklärung</h2>";
        $privacyContent .= "<h3>1. Datenschutz auf einen Blick</h3>";
        $privacyContent .= "<p>Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten passiert, wenn Sie diese Website besuchen.</p>";
        $privacyContent .= "<h3>Verantwortliche Stelle</h3>";
        $privacyContent .= "<p><strong>{$company}</strong><br>{$address}<br>E-Mail: {$email}</p>";
        // ... (This would be a much longer template in production)

         $existingPriv = $db->fetchOne("SELECT id FROM {$db->getPrefix()}posts WHERE slug = 'datenschutz'");
        if ($existingPriv) {
            $db->update('posts', ['content' => $privacyContent, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $existingPriv['id']]);
        } else {
            $db->insert('posts', [
                'title' => 'Datenschutzerklärung',
                'slug' => 'datenschutz',
                'content' => $privacyContent,
                'status' => 'published',
                'author_id' => Auth::instance()->getCurrentUser()->id,
                'published_at' => date('Y-m-d H:i:s')
            ]);
        }

        // 3. Generate Cookie Policy
        if (isset($_POST['generate_cookie_policy'])) {
            // Fetch Cookies
            $manualListJson = $db->fetchOne("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'cookie_manual_list'");
            $manualCookies = $manualListJson ? json_decode($manualListJson['option_value'] ?? '[]', true) : [];
            
            $scanResultJson = $db->fetchOne("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'cookie_scan_result'");
            $scannedCookies = $scanResultJson ? json_decode($scanResultJson['option_value'] ?? '[]', true) : [];
            
            $allCookies = array_merge($manualCookies, $scannedCookies);

            $cookieContent = "<h2>Cookie-Richtlinie</h2>";
            $cookieContent .= "<p>Diese Website verwendet Cookies, um die Benutzererfahrung zu verbessern. Hier finden Sie eine Übersicht über alle verwendeten Cookies.</p>";
            
            // Dynamic Consensus Status
            $cookieContent .= '<div style="background:#f8fafc; padding:1.5rem; border:1px solid #e2e8f0; border-radius:8px; margin:2rem 0;">';
            $cookieContent .= '<h3>Ihr aktueller Consent-Status</h3>';
            $cookieContent .= '<p id="cms-cookie-status">Wird geladen...</p>';
            $cookieContent .= '<p><button class="btn btn-primary" onclick="window.CMS.Cookie.openSettings();">Einstellungen ändern</button></p>';
            $cookieContent .= '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    const checkCMS = setInterval(function() {
                        if(window.CMS && window.CMS.Cookie) {
                            clearInterval(checkCMS);
                            const statusEl = document.getElementById("cms-cookie-status");
                            const consents = window.CMS.Cookie.getConsents();
                            if(Object.keys(consents).length === 0) {
                                statusEl.innerHTML = "Sie haben noch keine Auswahl getroffen.";
                            } else {
                                let text = "Sie haben folgenden Kategorien zugestimmt: ";
                                text += Object.keys(consents).filter(k => consents[k]).map(k => "<strong>" + k.charAt(0).toUpperCase() + k.slice(1) + "</strong>").join(", ");
                                statusEl.innerHTML = text || "Sie haben alle optionalen Cookies abgelehnt.";
                            }
                        }
                    }, 500);
                });
            </script>';
            $cookieContent .= '</div>';
            
            $cookieContent .= "<h3>Liste der verwendeten Cookies</h3>";
            if (!empty($allCookies)) {
                $cookieContent .= '<table class="wp-block-table" style="width:100%; text-align:left; border-collapse:collapse;">
                <thead><tr style="border-bottom:2px solid #e2e8f0;"><th style="padding:10px;">Name</th><th style="padding:10px;">Anbieter</th><th style="padding:10px;">Kategorie</th><th style="padding:10px;">Laufzeit</th></tr></thead>
                <tbody>';
                foreach ($allCookies as $c) {
                    $name = htmlspecialchars($c['name'] ?? 'Unbekannt');
                    $provider = htmlspecialchars($c['provider'] ?? '-'); 
                    $category = ucfirst(htmlspecialchars($c['category'] ?? 'Sonstiges'));
                    $duration = htmlspecialchars($c['duration'] ?? '-');
                    $cookieContent .= "<tr style='border-bottom:1px solid #f1f5f9;'><td style='padding:10px;'><strong>{$name}</strong></td><td style='padding:10px;'>{$provider}</td><td style='padding:10px;'>{$category}</td><td style='padding:10px;'>{$duration}</td></tr>";
                }
                $cookieContent .= '</tbody></table>';
            } else {
                $cookieContent .= "<p>Aktuell sind keine Cookies erfasst.</p>";
            }

            // Check if page exists by slug
            $existingPage = $db->fetchOne("SELECT id FROM {$db->getPrefix()}posts WHERE slug = 'cookie-richtlinie'");
            $saveData = [
                'title' => 'Cookie-Richtlinie',
                'slug' => 'cookie-richtlinie',
                'content' => $cookieContent,
                'status' => 'published',
                'type' => 'page',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($existingPage) {
                // Update
                $db->update('posts', $saveData, ['id' => $existingPage['id']]);
            } else {
                // Insert
                $saveData['author_id'] = Auth::instance()->getCurrentUser()->id;
                $saveData['published_at'] = date('Y-m-d H:i:s');
                $db->insert('posts', $saveData);
            }
        } // End of generate_cookie_policy block

        $message = 'Rechtstexte wurden erfolgreich aktualisiert!';
        $messageType = 'success';
    } // End of nonce check
} // End of POST request

// Load existing settings if available (from settings table mostly)
$settings = $db->fetchAll("SELECT option_name, option_value FROM {$db->getPrefix()}settings");
$opts = [];
foreach($settings as $s) $opts[$s['option_name']] = $s['option_value'];

$csrfToken = Security::instance()->generateToken('generate_legal');

renderAdminLayoutStart('Rechtstexte Generator', 'legal-sites');
?>

<div class="admin-page-header">
    <div>
        <h2>§ Rechtstexte Generator</h2>
        <p>Erstellen Sie automatisiert Ihr Impressum, Datenschutzerklärung und Cookie-Richtlinie.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="admin-card">
    <form method="post" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        <input type="hidden" name="generate_legal" value="1">

        <h3>🏢 Firmendaten</h3>
        
        <div class="form-group">
            <label class="form-label">Firmenname / Inhaber <span style="color:#ef4444;">*</span></label>
            <input type="text" name="company" class="form-control"
                   value="<?php echo htmlspecialchars($opts['site_title'] ?? ''); ?>" required placeholder="Musterfirma GmbH">
        </div>

        <div class="form-group">
            <label class="form-label">Anschrift (Straße, PLZ, Ort) <span style="color:#ef4444;">*</span></label>
            <input type="text" name="address" class="form-control"
                   required placeholder="Musterstraße 1, 12345 Musterstadt">
        </div>

        <div class="form-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem;">
            <div class="form-group">
                <label class="form-label">E-Mail Adresse <span style="color:#ef4444;">*</span></label>
                <input type="email" name="email" class="form-control"
                       value="<?php echo htmlspecialchars($opts['admin_email'] ?? ''); ?>" required>
            </div>
             <div class="form-group">
                <label class="form-label">Telefonnummer</label>
                <input type="text" name="phone" class="form-control" placeholder="+49 123 456789">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Geschäftsführer / Verantwortlicher</label>
            <input type="text" name="owner" class="form-control" placeholder="Max Mustermann">
        </div>

        <div class="form-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem;">
             <div class="form-group">
                <label class="form-label">Handelsregister (Optional)</label>
                <input type="text" name="registry" class="form-control" placeholder="HRB 12345">
            </div>
             <div class="form-group">
                <label class="form-label">USt-IdNr. (Optional)</label>
                <input type="text" name="vat_id" class="form-control" placeholder="DE123456789">
            </div>
        </div>

        <div class="form-group">
             <label class="checkbox-label" style="display:flex; align-items:center; gap:0.5rem; font-weight:bold;">
                <input type="checkbox" name="generate_cookie_policy" value="1" checked>
                Cookie-Richtlinie (cookie-richtlinie) mitgenerieren
            </label>
            <small class="form-text">
                Erstellt eine Seite mit einer Tabelle aller erkannten Cookies und einem Button zum Ändern der Zustimmung.
            </small>
        </div>

        <!-- Sticky Save Bar -->
        <div class="admin-card form-actions-card">
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">🔨 Rechtstexte generieren & speichern</button>
            </div>
            <p style="margin-top: 1rem; color: #64748b; font-size: 0.85rem;">
                ℹ️ Hinweis: Durch Klick auf den Button werden die Seiten "Impressum", "Datenschutz" und ggf. "Cookie-Richtlinie" erstellt oder überschrieben. 
                Bitte prüfen Sie die Texte anschließend auf Vollständigkeit.
            </p>
        </div>
    </form>
</div>

<?php renderAdminLayoutEnd(); ?>