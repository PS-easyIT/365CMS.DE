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
        $website_name    = sanitize_text_field($_POST['website_name'] ?? '');
        $registry_court  = sanitize_text_field($_POST['registry_court'] ?? '');
        $domains_raw     = sanitize_text_field($_POST['connected_domains'] ?? '');
        $privacy_officer = sanitize_text_field($_POST['privacy_officer'] ?? '');
        $privacy_officer_address = sanitize_text_field($_POST['privacy_officer_address'] ?? '');
        $privacy_officer_email   = sanitize_email($_POST['privacy_officer_email'] ?? '');

        // 1. Generate Impressum (§ 5 DDG / ehem. TMG, § 18 MStV)
        $impressumContent = '';

        // Optionaler Einleitungssatz
        if (!empty($website_name)) {
            $impressumContent .= "<p><strong>{$website_name}</strong></p>\n";
        }

        $impressumContent .= "<h2>Angaben gem&auml;&szlig; &sect; 5 DDG</h2>\n";
        $impressumContent .= "<p><strong>{$company}</strong><br>{$address}</p>\n";

        $impressumContent .= "<h3>Kontakt</h3>\n";
        $contactParts = [];
        if (!empty($phone)) {
            $contactParts[] = "Telefon: {$phone}";
        }
        $contactParts[] = "E-Mail: <a href=\"mailto:{$email}\">{$email}</a>";
        $impressumContent .= '<p>' . implode('<br>', $contactParts) . "</p>\n";

        if (!empty($registry)) {
            $impressumContent .= "<h3>Registereintrag</h3>\n";
            $impressumContent .= '<p>Eintragung im Handelsregister.<br>';
            if (!empty($registry_court)) {
                $impressumContent .= "Registergericht: {$registry_court}<br>";
            }
            $impressumContent .= "Registernummer: {$registry}</p>\n";
        }

        if (!empty($vat_id)) {
            $impressumContent .= "<h3>Umsatzsteuer-ID</h3>\n";
            $impressumContent .= "<p>Umsatzsteuer-Identifikationsnummer gem&auml;&szlig; &sect; 27 a Umsatzsteuergesetz:<br>{$vat_id}</p>\n";
        }

        if (!empty($owner)) {
            $impressumContent .= "<h3>Vertretungsberechtigte Person</h3>\n";
            $impressumContent .= "<p>{$owner}</p>\n";
            $impressumContent .= "<h3>Verantwortlich f&uuml;r den Inhalt nach &sect; 18 Abs. 2 MStV</h3>\n";
            $impressumContent .= "<p>{$owner}<br>{$address}</p>\n";
        }

        // Datenschutzbeauftragter (optional)
        if (!empty($privacy_officer)) {
            $impressumContent .= "<h3>Datenschutzbeauftragter</h3>\n";
            $dpoInfo = "<p>{$privacy_officer}";
            if (!empty($privacy_officer_address)) {
                $dpoInfo .= "<br>{$privacy_officer_address}";
            }
            if (!empty($privacy_officer_email)) {
                $dpoInfo .= "<br>E-Mail: <a href=\"mailto:{$privacy_officer_email}\">{$privacy_officer_email}</a>";
            }
            $dpoInfo .= "</p>\n";
            $impressumContent .= $dpoInfo;
        }

        // Verbundene Domains
        if (!empty($domains_raw)) {
            $domains = array_filter(array_map('trim', preg_split('/[\n,;]+/', $domains_raw)));
            if (!empty($domains)) {
                $impressumContent .= "<h3>Mit diesem Impressum verbundene Domains</h3>\n<ul>\n";
                foreach ($domains as $domain) {
                    $impressumContent .= '<li>' . htmlspecialchars($domain) . "</li>\n";
                }
                $impressumContent .= "</ul>\n";
            }
        }

        $impressumContent .= "<h3>EU-Streitschlichtung</h3>\n";
        $impressumContent .= '<p>Die Europ&auml;ische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit: ';
        $impressumContent .= '<a href="https://ec.europa.eu/consumers/odr/" target="_blank" rel="noopener noreferrer">https://ec.europa.eu/consumers/odr/</a>.<br>';
        $impressumContent .= "Unsere E-Mail-Adresse finden Sie oben im Impressum.</p>\n";
        $impressumContent .= "<h3>Verbraucherstreitbeilegung / Universalschlichtungsstelle</h3>\n";
        $impressumContent .= "<p>Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p>\n";

        // Haftungsausschluss
        $impressumContent .= "<h3>Haftung f&uuml;r Inhalte</h3>\n";
        $impressumContent .= '<p>Wir entwickeln die Inhalte dieser Website st&auml;ndig weiter und bem&uuml;hen uns, korrekte und aktuelle Informationen bereitzustellen. Leider k&ouml;nnen wir keine Haftung f&uuml;r die Korrektheit aller Inhalte auf dieser Website &uuml;bernehmen, speziell f&uuml;r jene, die seitens Dritter bereitgestellt wurden. Als Diensteanbieter sind wir nicht verpflichtet, die von Ihnen &uuml;bermittelten oder gespeicherten Informationen zu &uuml;berwachen oder nach Umst&auml;nden zu forschen, die auf eine rechtswidrige T&auml;tigkeit hinweisen.</p>';
        $impressumContent .= "\n<p>Unsere Verpflichtungen zur Entfernung von Informationen oder zur Sperrung der Nutzung von Informationen nach den allgemeinen Gesetzen aufgrund von gerichtlichen oder beh&ouml;rdlichen Anordnungen bleiben auch im Falle unserer Nichtverantwortlichkeit davon unber&uuml;hrt.</p>\n";
        $impressumContent .= "<p>Sollten Ihnen problematische oder rechtswidrige Inhalte auffallen, bitten wir Sie, uns umgehend zu kontaktieren, damit wir die rechtswidrigen Inhalte entfernen k&ouml;nnen. Sie finden die Kontaktdaten im Impressum.</p>\n";

        $impressumContent .= "<h3>Haftung f&uuml;r Links</h3>\n";
        $impressumContent .= '<p>Unsere Website enth&auml;lt Links zu anderen Websites, f&uuml;r deren Inhalt wir nicht verantwortlich sind. Haftung f&uuml;r verlinkte Websites besteht f&uuml;r uns nicht, da wir keine Kenntnis rechtswidriger T&auml;tigkeiten hatten und haben, uns solche Rechtswidrigkeiten auch bisher nicht aufgefallen sind und wir Links sofort entfernen w&uuml;rden, wenn uns Rechtswidrigkeiten bekannt werden.</p>';
        $impressumContent .= "\n<p>Wenn Ihnen rechtswidrige Links auf unserer Website auffallen, bitten wir Sie, uns zu kontaktieren. Sie finden die Kontaktdaten im Impressum.</p>\n";

        $impressumContent .= "<h3>Urheberrechtshinweis</h3>\n";
        $impressumContent .= '<p>Alle Inhalte dieser Webseite (Bilder, Fotos, Texte, Videos) unterliegen dem Urheberrecht. Bitte fragen Sie uns, bevor Sie die Inhalte dieser Website verbreiten, vervielf&auml;ltigen oder verwerten wie zum Beispiel auf anderen Websites erneut ver&ouml;ffentlichen. Falls notwendig, werden wir die unerlaubte Nutzung von Teilen der Inhalte unserer Seite rechtlich verfolgen.</p>';
        $impressumContent .= "\n<p>Sollten Sie auf dieser Webseite Inhalte finden, die das Urheberrecht verletzen, bitten wir Sie, uns zu kontaktieren.</p>\n";

        // Save Impressum → pages table
        $existingImp = $db->fetchOne("SELECT id FROM {$db->getPrefix()}pages WHERE slug = 'impressum'");
        if ($existingImp) {
            $db->update('pages', ['content' => $impressumContent, 'title' => 'Impressum', 'updated_at' => date('Y-m-d H:i:s')], ['id' => $existingImp['id']]);
        } else {
            $db->insert('pages', [
                'title' => 'Impressum',
                'slug' => 'impressum',
                'content' => $impressumContent,
                'status' => 'published',
                'author_id' => Auth::instance()->getCurrentUser()->id,
                'published_at' => date('Y-m-d H:i:s')
            ]);
        }

        // 2. Generate Datenschutzerklärung (DSGVO-konform, Art. 13/14 DSGVO)
        $privacyContent  = "<h2>Datenschutzerklärung</h2>";
        $privacyContent .= "<h3>1. Datenschutz auf einen Blick</h3>";
        $privacyContent .= "<h4>Allgemeine Hinweise</h4>";
        $privacyContent .= "<p>Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten passiert, wenn Sie diese Website besuchen. Personenbezogene Daten sind alle Daten, mit denen Sie persönlich identifiziert werden können. Ausführliche Informationen zum Thema Datenschutz entnehmen Sie unserer unter diesem Text aufgeführten Datenschutzerklärung.</p>";
        $privacyContent .= "<h4>Datenerfassung auf dieser Website</h4>";
        $privacyContent .= "<p><strong>Wer ist verantwortlich für die Datenerfassung auf dieser Website?</strong><br>";
        $privacyContent .= 'Die Datenverarbeitung auf dieser Website erfolgt durch den Websitebetreiber. Dessen Kontaktdaten können Sie dem Abschnitt &bdquo;Hinweis zur verantwortlichen Stelle&ldquo; in dieser Datenschutzerklärung entnehmen.</p>';
        $privacyContent .= "<p><strong>Wie erfassen wir Ihre Daten?</strong><br>";
        $privacyContent .= "Ihre Daten werden zum einen dadurch erhoben, dass Sie uns diese mitteilen. Hierbei kann es sich z.&thinsp;B. um Daten handeln, die Sie in ein Kontaktformular eingeben. Andere Daten werden automatisch oder nach Ihrer Einwilligung beim Besuch der Website durch unsere IT-Systeme erfasst. Das sind vor allem technische Daten (z.&thinsp;B. Internetbrowser, Betriebssystem oder Uhrzeit des Seitenaufrufs). Die Erfassung dieser Daten erfolgt automatisch, sobald Sie diese Website betreten.</p>";
        $privacyContent .= "<p><strong>Wofür nutzen wir Ihre Daten?</strong><br>";
        $privacyContent .= "Ein Teil der Daten wird erhoben, um eine fehlerfreie Bereitstellung der Website zu gewährleisten. Andere Daten können zur Analyse Ihres Nutzerverhaltens verwendet werden.</p>";
        $privacyContent .= "<p><strong>Welche Rechte haben Sie bezüglich Ihrer Daten?</strong><br>";
        $privacyContent .= "Sie haben jederzeit das Recht, unentgeltlich Auskunft über Herkunft, Empfänger und Zweck Ihrer gespeicherten personenbezogenen Daten zu erhalten. Sie haben außerdem ein Recht, die Berichtigung oder Löschung dieser Daten zu verlangen. Wenn Sie eine Einwilligung zur Datenverarbeitung erteilt haben, können Sie diese Einwilligung jederzeit für die Zukunft widerrufen. Außerdem haben Sie das Recht, unter bestimmten Umständen die Einschränkung der Verarbeitung Ihrer personenbezogenen Daten zu verlangen. Des Weiteren steht Ihnen ein Beschwerderecht bei der zuständigen Aufsichtsbehörde zu.</p>";

        $privacyContent .= "<h3>2. Hosting</h3>";
        $privacyContent .= "<p>Wir hosten die Inhalte unserer Website bei folgendem Anbieter:</p>";
        $privacyContent .= "<p>Die Nutzung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO. Wir haben ein berechtigtes Interesse an einer möglichst zuverlässigen Darstellung unserer Website. Sofern eine entsprechende Einwilligung abgefragt wurde, erfolgt die Verarbeitung ausschließlich auf Grundlage von Art. 6 Abs. 1 lit. a DSGVO.</p>";

        $privacyContent .= "<h3>3. Allgemeine Hinweise und Pflichtinformationen</h3>";
        $privacyContent .= "<h4>Datenschutz</h4>";
        $privacyContent .= "<p>Die Betreiber dieser Seiten nehmen den Schutz Ihrer persönlichen Daten sehr ernst. Wir behandeln Ihre personenbezogenen Daten vertraulich und entsprechend den gesetzlichen Datenschutzvorschriften sowie dieser Datenschutzerklärung.</p>";
        $privacyContent .= "<h4>Hinweis zur verantwortlichen Stelle</h4>";
        $privacyContent .= "<p>Die verantwortliche Stelle für die Datenverarbeitung auf dieser Website ist:</p>";
        $privacyContent .= "<p><strong>{$company}</strong><br>{$address}</p>";
        if ($owner) {
            $privacyContent .= "<p>Vertreten durch: {$owner}</p>";
        }
        $privacyContent .= "<p>Telefon: {$phone}<br>E-Mail: {$email}</p>";
        $privacyContent .= "<p>Verantwortliche Stelle ist die natürliche oder juristische Person, die allein oder gemeinsam mit anderen über die Zwecke und Mittel der Verarbeitung von personenbezogenen Daten (z.&thinsp;B. Namen, E-Mail-Adressen o.&thinsp;Ä.) entscheidet.</p>";

        $privacyContent .= "<h4>Speicherdauer</h4>";
        $privacyContent .= "<p>Soweit innerhalb dieser Datenschutzerklärung keine speziellere Speicherdauer genannt wurde, verbleiben Ihre personenbezogenen Daten bei uns, bis der Zweck für die Datenverarbeitung entfällt. Wenn Sie ein berechtigtes Löschersuchen geltend machen oder eine Einwilligung zur Datenverarbeitung widerrufen, werden Ihre Daten gelöscht, sofern wir keine anderen rechtlich zulässigen Gründe für die Speicherung Ihrer personenbezogenen Daten haben; in einem solchen Fall erfolgt die Löschung nach Fortfall dieser Gründe.</p>";

        $privacyContent .= "<h4>Allgemeine Hinweise zu den Rechtsgrundlagen der Datenverarbeitung auf dieser Website</h4>";
        $privacyContent .= "<p>Sofern Sie in die Datenverarbeitung eingewilligt haben, verarbeiten wir Ihre personenbezogenen Daten auf Grundlage von Art. 6 Abs. 1 lit. a DSGVO bzw. Art. 9 Abs. 2 lit. a DSGVO, sofern besondere Datenkategorien nach Art. 9 Abs. 1 DSGVO verarbeitet werden. Sofern Sie ausdrücklich in die Übertragung personenbezogener Daten in Drittstaaten eingewilligt haben, erfolgt die Datenverarbeitung außerdem auf Grundlage von Art. 49 Abs. 1 lit. a DSGVO. Bei der Verarbeitung von Daten zur Vertragserfüllung oder vorvertraglicher Maßnahmen geschieht dies auf Grundlage von Art. 6 Abs. 1 lit. b DSGVO. Ferner verarbeiten wir Daten auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO, sofern ein berechtigtes Interesse besteht.</p>";

        $privacyContent .= "<h4>Empfänger von personenbezogenen Daten</h4>";
        $privacyContent .= "<p>Im Rahmen unserer Geschäftstätigkeit arbeiten wir mit verschiedenen externen Stellen zusammen. Dabei ist teilweise auch eine Übermittlung von personenbezogenen Daten an diese externen Stellen erforderlich. Wir geben personenbezogene Daten nur dann an externe Stellen weiter, wenn dies im Rahmen einer Vertragserfüllung erforderlich ist, wenn wir gesetzlich hierzu verpflichtet sind, wenn wir ein berechtigtes Interesse gem. Art. 6 Abs. 1 lit. f DSGVO haben oder wenn eine sonstige Rechtsgrundlage die Datenweitergabe erlaubt.</p>";

        $privacyContent .= "<h4>Widerruf Ihrer Einwilligung zur Datenverarbeitung</h4>";
        $privacyContent .= "<p>Viele Datenverarbeitungsvorgänge sind nur mit Ihrer ausdrücklichen Einwilligung möglich. Sie können eine bereits erteilte Einwilligung jederzeit widerrufen. Die Rechtmäßigkeit der bis zum Widerruf erfolgten Datenverarbeitung bleibt vom Widerruf unberührt.</p>";

        $privacyContent .= "<h4>Widerspruchsrecht gegen die Datenerhebung in besonderen Fällen sowie gegen Direktwerbung (Art. 21 DSGVO)</h4>";
        $privacyContent .= "<p><strong>WENN DIE DATENVERARBEITUNG AUF GRUNDLAGE VON ART. 6 ABS. 1 LIT. E ODER F DSGVO ERFOLGT, HABEN SIE JEDERZEIT DAS RECHT, AUS GRÜNDEN, DIE SICH AUS IHRER BESONDEREN SITUATION ERGEBEN, GEGEN DIE VERARBEITUNG IHRER PERSONENBEZOGENEN DATEN WIDERSPRUCH EINZULEGEN. WERDEN IHRE PERSONENBEZOGENEN DATEN VERARBEITET, UM DIREKTWERBUNG ZU BETREIBEN, SO HABEN SIE DAS RECHT, JEDERZEIT WIDERSPRUCH GEGEN DIE VERARBEITUNG SIE BETREFFENDER PERSONENBEZOGENER DATEN ZUM ZWECKE DERARTIGER WERBUNG EINZULEGEN.</strong></p>";

        $privacyContent .= "<h4>Beschwerderecht bei der zuständigen Aufsichtsbehörde</h4>";
        $privacyContent .= "<p>Im Falle von Verstößen gegen die DSGVO steht den Betroffenen ein Beschwerderecht bei einer Aufsichtsbehörde zu, insbesondere in dem Mitgliedstaat ihres gewöhnlichen Aufenthalts, ihres Arbeitsplatzes oder des Orts des mutmaßlichen Verstoßes. Das Beschwerderecht besteht unbeschadet anderweitiger verwaltungsrechtlicher oder gerichtlicher Rechtsbehelfe.</p>";

        $privacyContent .= "<h4>Recht auf Datenübertragbarkeit</h4>";
        $privacyContent .= "<p>Sie haben das Recht, Daten, die wir auf Grundlage Ihrer Einwilligung oder in Erfüllung eines Vertrags automatisiert verarbeiten, an sich oder an einen Dritten in einem gängigen, maschinenlesbaren Format aushändigen zu lassen. Sofern Sie die direkte Übertragung der Daten an einen anderen Verantwortlichen verlangen, erfolgt dies nur, soweit es technisch machbar ist.</p>";

        $privacyContent .= "<h4>Auskunft, Berichtigung und Löschung</h4>";
        $privacyContent .= "<p>Sie haben im Rahmen der geltenden gesetzlichen Bestimmungen jederzeit das Recht auf unentgeltliche Auskunft über Ihre gespeicherten personenbezogenen Daten, deren Herkunft und Empfänger und den Zweck der Datenverarbeitung und ggf. ein Recht auf Berichtigung oder Löschung dieser Daten. Hierzu sowie zu weiteren Fragen zum Thema personenbezogene Daten können Sie sich jederzeit an uns wenden.</p>";

        $privacyContent .= "<h4>Recht auf Einschränkung der Verarbeitung</h4>";
        $privacyContent .= "<p>Sie haben das Recht, die Einschränkung der Verarbeitung Ihrer personenbezogenen Daten zu verlangen. Hierzu können Sie sich jederzeit an uns wenden.</p>";

        $privacyContent .= "<h4>SSL- bzw. TLS-Verschlüsselung</h4>";
        $privacyContent .= '<p>Diese Seite nutzt aus Sicherheitsgründen und zum Schutz der Übertragung vertraulicher Inhalte, wie zum Beispiel Bestellungen oder Anfragen, die Sie an uns als Seitenbetreiber senden, eine SSL- bzw. TLS-Verschlüsselung. Eine verschlüsselte Verbindung erkennen Sie daran, dass die Adresszeile des Browsers von &bdquo;http://&ldquo; auf &bdquo;https://&ldquo; wechselt und an dem Schloss-Symbol in Ihrer Browserzeile.</p>';

        $privacyContent .= "<h3>4. Datenerfassung auf dieser Website</h3>";
        $privacyContent .= "<h4>Cookies</h4>";
        $privacyContent .= '<p>Unsere Internetseiten verwenden so genannte &bdquo;Cookies&ldquo;. Cookies sind kleine Datenpakete und richten auf Ihrem Endger&auml;t keinen Schaden an. Sie werden entweder vor&uuml;bergehend f&uuml;r die Dauer einer Sitzung (Session-Cookies) oder dauerhaft (permanente Cookies) auf Ihrem Endger&auml;t gespeichert. Session-Cookies werden nach Ende Ihres Besuchs automatisch gel&ouml;scht. Permanente Cookies bleiben auf Ihrem Endger&auml;t gespeichert, bis Sie diese selbst l&ouml;schen oder eine automatische L&ouml;schung durch Ihren Webbrowser erfolgt.</p>';
        $privacyContent .= "<p>Die Speicherung von Cookies erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO. Der Websitebetreiber hat ein berechtigtes Interesse an der Speicherung technisch notwendiger Cookies zur technisch fehlerfreien und optimierten Bereitstellung seiner Dienste. Soweit eine Einwilligung zur Speicherung von Cookies abgefragt wurde, erfolgt die Speicherung der betreffenden Cookies ausschließlich auf Grundlage dieser Einwilligung (Art. 6 Abs. 1 lit. a DSGVO); die Einwilligung ist jederzeit widerrufbar.</p>";

        $privacyContent .= "<h4>Server-Log-Dateien</h4>";
        $privacyContent .= "<p>Der Provider der Seiten erhebt und speichert automatisch Informationen in so genannten Server-Log-Dateien, die Ihr Browser automatisch an uns übermittelt. Dies sind:</p>";
        $privacyContent .= "<ul><li>Browsertyp und Browserversion</li><li>verwendetes Betriebssystem</li><li>Referrer URL</li><li>Hostname des zugreifenden Rechners</li><li>Uhrzeit der Serveranfrage</li><li>IP-Adresse</li></ul>";
        $privacyContent .= "<p>Eine Zusammenführung dieser Daten mit anderen Datenquellen wird nicht vorgenommen. Die Erfassung dieser Daten erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO.</p>";

        $privacyContent .= "<h4>Kontaktformular</h4>";
        $privacyContent .= "<p>Wenn Sie uns per Kontaktformular Anfragen zukommen lassen, werden Ihre Angaben aus dem Anfrageformular inklusive der von Ihnen dort angegebenen Kontaktdaten zwecks Bearbeitung der Anfrage und für den Fall von Anschlussfragen bei uns gespeichert. Diese Daten geben wir nicht ohne Ihre Einwilligung weiter. Die Verarbeitung dieser Daten erfolgt auf Grundlage von Art. 6 Abs. 1 lit. b DSGVO.</p>";

        $privacyContent .= "<h4>Registrierung auf dieser Website</h4>";
        $privacyContent .= "<p>Sie können sich auf dieser Website registrieren, um zusätzliche Funktionen auf der Seite zu nutzen. Die dazu eingegebenen Daten verwenden wir nur zum Zwecke der Nutzung des jeweiligen Angebotes oder Dienstes, für den Sie sich registriert haben. Die bei der Registrierung abgefragten Pflichtangaben müssen vollständig angegeben werden. Anderenfalls werden wir die Registrierung ablehnen. Die Verarbeitung der bei der Registrierung eingegebenen Daten erfolgt auf Grundlage Ihrer Einwilligung (Art. 6 Abs. 1 lit. a DSGVO).</p>";

        $privacyContent .= "<p><em>Quelle: Generiert durch 365CMS Rechtstexte-Generator. Bitte prüfen Sie die Angaben sorgfältig und passen Sie den Text ggf. an Ihre konkrete Datenverarbeitung an.</em></p>";

        // Save Datenschutz → pages table
        $existingPriv = $db->fetchOne("SELECT id FROM {$db->getPrefix()}pages WHERE slug = 'datenschutz'");
        if ($existingPriv) {
            $db->update('pages', ['content' => $privacyContent, 'title' => 'Datenschutzerklärung', 'updated_at' => date('Y-m-d H:i:s')], ['id' => $existingPriv['id']]);
        } else {
            $db->insert('pages', [
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

            // Save Cookie-Richtlinie → pages table
            $existingCookie = $db->fetchOne("SELECT id FROM {$db->getPrefix()}pages WHERE slug = 'cookie-richtlinie'");
            if ($existingCookie) {
                $db->update('pages', [
                    'content'    => $cookieContent,
                    'title'      => 'Cookie-Richtlinie',
                    'updated_at' => date('Y-m-d H:i:s'),
                ], ['id' => $existingCookie['id']]);
            } else {
                $db->insert('pages', [
                    'title'        => 'Cookie-Richtlinie',
                    'slug'         => 'cookie-richtlinie',
                    'content'      => $cookieContent,
                    'status'       => 'published',
                    'author_id'    => Auth::instance()->getCurrentUser()->id,
                    'published_at' => date('Y-m-d H:i:s'),
                ]);
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
            <label class="form-label">Website-Name</label>
            <input type="text" name="website_name" class="form-control" placeholder="z. B. MeinBlog.de oder Firmenwebsite">
            <small class="form-text">Wird als Einleitung im Impressum angezeigt (optional).</small>
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
                <label class="form-label">Handelsregister-Nr. (Optional)</label>
                <input type="text" name="registry" class="form-control" placeholder="HRB 12345">
            </div>
             <div class="form-group">
                <label class="form-label">Registergericht (Optional)</label>
                <input type="text" name="registry_court" class="form-control" placeholder="Amtsgericht Musterstadt">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">USt-IdNr. (Optional)</label>
            <input type="text" name="vat_id" class="form-control" placeholder="DE123456789" style="max-width:320px;">
        </div>

        <h3>🌐 Weitere Angaben</h3>

        <div class="form-group">
            <label class="form-label">Verbundene Domains (Optional)</label>
            <textarea name="connected_domains" class="form-control" rows="3" placeholder="z. B. meinshop.de&#10;meineapp.com"
                      style="resize:vertical;min-height:80px;"></textarea>
            <small class="form-text">Eine Domain pro Zeile. Diese Domains teilen sich das gleiche Impressum.</small>
        </div>

        <h3>🛡️ Datenschutzbeauftragter (Optional)</h3>
        <small class="form-text" style="display:block;margin-bottom:1rem;">Nur ausfüllen, wenn ein externer oder interner Datenschutzbeauftragter bestellt wurde.</small>

        <div class="form-group">
            <label class="form-label">Name des Datenschutzbeauftragten</label>
            <input type="text" name="privacy_officer" class="form-control" placeholder="Dr. Erika Muster">
        </div>

        <div class="form-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem;">
            <div class="form-group">
                <label class="form-label">Anschrift (DSB)</label>
                <input type="text" name="privacy_officer_address" class="form-control" placeholder="Datenschutzstraße 1, 12345 Musterstadt">
            </div>
            <div class="form-group">
                <label class="form-label">E-Mail (DSB)</label>
                <input type="email" name="privacy_officer_email" class="form-control" placeholder="dsb@example.de">
            </div>
        </div>

        <h3>⚙️ Optionen</h3>

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