<?php
/**
 * Rechtstexte Generator
 * 
 * Automatische Erstellung von Impressum und Datenschutz
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
if (!Auth::instance()->isAdmin()) { header('Location: ' . SITE_URL); exit; }

// Load Admin Menu & Layout Helpers
require_once __DIR__ . '/partials/admin-menu.php';

$db = Database::instance();
$message = '';

// Handle Generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_legal'])) {
    if (!wp_verify_nonce($_POST['_csrf_token'], 'generate_legal')) {
        $message = '<div class="alert alert-error">Sicherheitsprüfung fehlgeschlagen.</div>';
    } else {
        // Collect Data
        $company = sanitize_text_field($_POST['company']);
        $address = sanitize_text_field($_POST['address']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $owner = sanitize_text_field($_POST['owner']);
        $registry = sanitize_text_field($_POST['registry']);
        $vat_id = sanitize_text_field($_POST['vat_id']);

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
            
            $cookieContent .= "<h3>Ihre Cookie-Einstellungen</h3>";
            $cookieContent .= "<p>Sie können Ihre Zustimmung zu Cookies jederzeit ändern oder widerrufen:</p>";
            // JS Call to open banner
            $cookieContent .= '<p><a href="#" class="btn btn-secondary" onclick="if(window.CMS && window.CMS.Cookie) { window.CMS.Cookie.openSettings(); return false; } else { alert(\'Cookie-Banner nicht geladen.\'); return false; }">🍪 Cookie-Einstellungen bearbeiten</a></p>';
            
            $cookieContent .= "<h3>Liste der verwendeten Cookies</h3>";
            if (!empty($allCookies)) {
                $cookieContent .= '<table class="wp-block-table"><thead><tr><th>Name</th><th>Anbieter</th><th>Zweck / Kategorie</th><th>Laufzeit</th></tr></thead><tbody>';
                foreach ($allCookies as $c) {
                    $name = htmlspecialchars($c['name'] ?? 'Unbekannt');
                    $provider = htmlspecialchars($c['provider'] ?? '-');
                    $category = ucfirst(htmlspecialchars($c['category'] ?? 'Sonstiges'));
                    $duration = htmlspecialchars($c['duration'] ?? '-');
                    $cookieContent .= "<tr><td>{$name}</td><td>{$provider}</td><td>{$category}</td><td>{$duration}</td></tr>";
                }
                $cookieContent .= '</tbody></table>';
            } else {
                $cookieContent .= "<p>Aktuell sind keine Cookies erfasst.</p>";
            }

            $existingCookie = $db->fetchOne("SELECT id FROM {$db->getPrefix()}posts WHERE slug = 'cookie-richtlinie'");
            if ($existingCookie) {
                $db->update('posts', ['content' => $cookieContent, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $existingCookie['id']]);
            } else {
                $db->insert('posts', [
                    'title' => 'Cookie-Richtlinie',
                    'slug' => 'cookie-richtlinie',
                    'content' => $cookieContent,
                    'status' => 'published',
                    'author_id' => Auth::instance()->getCurrentUser()->id,
                    'published_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        $message = '<div class="alert alert-success">Impressum, Datenschutz und Cookie-Richtlinie wurden erfolgreich aktualisiert!</div>';
    }
}

// Load existing settings if available (from settings table mostly)
$settings = $db->fetchAll("SELECT option_name, option_value FROM {$db->getPrefix()}settings");
$opts = [];
foreach($settings as $s) $opts[$s['option_name']] = $s['option_value'];


renderAdminLayoutStart('Rechtstexte Generator', 'legal-sites');
?>

<div class="admin-header">
    <h1>§ Rechtstexte Generator</h1>
    <p>Erstellen Sie automatisiert Ihr Impressum und Ihre Datenschutzerklärung.</p>
</div>

<?php echo $message; ?>

<div class="admin-card">
    <form method="post" class="admin-form">
        <input type="hidden" name="_csrf_token" value="<?php echo wp_create_nonce('generate_legal'); ?>">
        <input type="hidden" name="generate_legal" value="1">

        <h3>🏢 Firmendaten</h3>
        
        <div class="form-group">
            <label>Firmenname / Inhaber</label>
            <input type="text" name="company" value="<?php echo htmlspecialchars($opts['site_title'] ?? ''); ?>" required placeholder="Musterfirma GmbH">
        </div>

        <div class="form-group">
            <label>Anschrift (Straße, PLZ, Ort)</label>
            <input type="text" name="address" required placeholder="Musterstraße 1, 12345 Musterstadt">
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label>E-Mail Adresse</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($opts['admin_email'] ?? ''); ?>" required>
            </div>
             <div class="form-group">
                <label>Telefonnummer</label>
                <input type="text" name="phone" placeholder="+49 123 456789">
            </div>
        </div>

        <div class="form-group">
            <label>Geschäftsführer / Verantwortlicher</label>
            <input type="text" name="owner" placeholder="Max Mustermann">
        </div>

        <div class="form-grid">
             <div class="form-group">
                <label>Handelsregister (Optional)</label>
                <input type="text" name="registry" placeholder="HRB 12345">
            </div>
             <div class="form-group">
                <label>USt-IdNr. (Optional)</label>
                <input type="text" name="vat_id" placeholder="DE123456789">
            </div>
        </div>

        <div class="form-group">
             <label style="display:flex; align-items:center; gap:0.5rem; font-weight:bold;">
                <input type="checkbox" name="generate_cookie_policy" value="1" checked>
                Cookie-Richtlinie (cookie-richtlinie) mitgenerieren
            </label>
            <p style="font-size:0.85rem; color:#64748b; margin-top:0.25rem;">
                Erstellt eine Seite mit einer Tabelle aller erkannten Cookies und einem Button zum Ändern der Zustimmung.
            </p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                🔨 Rechtstexte generieren & speichern
            </button>
        </div>
        
        <p style="margin-top: 1rem; color: #64748b; font-size: 0.85rem;">
            ℹ️ Hinweis: Durch Klick auf den Button werden die Seiten "Impressum" und "Datenschutz" erstellt oder überschrieben. 
            Bitte prüfen Sie die Texte anschließend auf Vollständigkeit.
        </p>
    </form>
</div>

<?php renderAdminLayoutEnd(); ?>
