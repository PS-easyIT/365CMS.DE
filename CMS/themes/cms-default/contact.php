<?php
/**
 * Meridian CMS Default – Kontakt Template
 *
 * Unterstützt:
 *   - Kontaktformular mit CSRF-Schutz
 *   - Konfigurierbare Empfänger-E-Mail aus CMS-Settings
 *   - Honeypot-Anti-Spam
 *
 * @package CMSv2\Themes\CmsDefault
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$contactSuccess = '';
$contactError   = '';
$formData       = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

// Empfänger-E-Mail aus Settings
$recipientEmail = '';
try {
    $db  = \CMS\Database::instance();
    $row = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'contact_email' LIMIT 1")->fetch();
    if ($row && $row->option_value) {
        $recipientEmail = $row->option_value;
    }
} catch (\Throwable $e) {
    // Fallback: Admin-Mail aus Config
}
if (empty($recipientEmail) && defined('ADMIN_EMAIL')) {
    $recipientEmail = ADMIN_EMAIL;
}

// CSRF-Token
$csrfToken = '';
if (class_exists('\CMS\Security')) {
    $csrfToken = \CMS\Security::instance()->generateToken('contact_form');
}

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {

    // CSRF prüfen
    $csrfOk = true;
    if (class_exists('\CMS\Security')) {
        $csrfOk = \CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'contact_form');
    }

    // Honeypot prüfen (sollte leer sein)
    $honeypot = $_POST['website'] ?? '';

    if (!$csrfOk) {
        $contactError = 'Sicherheitscheck fehlgeschlagen. Bitte Seite neu laden.';
    } elseif (!empty($honeypot)) {
        // Stiller Spam-Abbruch (Honeypot gefüllt → Bot)
        $contactSuccess = 'Deine Nachricht wurde gesendet. Wir melden uns bald!';
    } else {
        $formData['name']    = htmlspecialchars(trim($_POST['contact_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $formData['email']   = filter_var(trim($_POST['contact_email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '';
        $formData['subject'] = htmlspecialchars(trim($_POST['contact_subject'] ?? ''), ENT_QUOTES, 'UTF-8');
        $formData['message'] = htmlspecialchars(trim($_POST['contact_message'] ?? ''), ENT_QUOTES, 'UTF-8');

        if (empty($formData['name'])) {
            $contactError = 'Bitte gib deinen Namen an.';
        } elseif (empty($formData['email'])) {
            $contactError = 'Bitte gib eine gültige E-Mail-Adresse an.';
        } elseif (strlen($formData['message']) < 20) {
            $contactError = 'Bitte gib eine ausführlichere Nachricht ein (min. 20 Zeichen).';
        } elseif (!empty($recipientEmail)) {
            // E-Mail senden
            $subject  = '[Kontakt] ' . ($formData['subject'] ?: $formData['name']);
            $body     = "Name: {$formData['name']}\n"
                      . "E-Mail: {$formData['email']}\n\n"
                      . "Nachricht:\n{$formData['message']}";
            $headers  = "From: {$formData['email']}\r\nReply-To: {$formData['email']}\r\nContent-Type: text/plain; charset=UTF-8";

            $sent = @mail($recipientEmail, $subject, $body, $headers);
            if ($sent) {
                $contactSuccess = 'Deine Nachricht wurde gesendet. Wir melden uns in Kürze!';
                $formData = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
            } else {
                $contactError = 'Beim Senden ist ein Fehler aufgetreten. Bitte versuche es erneut oder kontaktiere uns direkt per E-Mail.';
            }
        } else {
            $contactError = 'Kontaktformular ist momentan nicht konfiguriert. Bitte wende dich direkt an uns.';
        }
    }
    // CSRF-Token nach Verarbeitung neu erzeugen
    if (class_exists('\CMS\Security')) {
        $csrfToken = \CMS\Security::instance()->generateToken('contact_form');
    }
}
?>

<div class="container" style="max-width:var(--max);margin:0 auto;padding:2rem 1.5rem;">

    <!-- Breadcrumb -->
    <nav class="breadcrumb" aria-label="Breadcrumb" style="font-size:.8rem;color:var(--ink-muted);margin-bottom:2rem;">
        <a href="<?php echo SITE_URL; ?>/" style="color:var(--ink-muted);text-decoration:none;">Startseite</a>
        <span style="margin:0 .4rem;">›</span>
        <span aria-current="page" style="color:var(--ink);">Kontakt</span>
    </nav>

    <div style="display:grid;grid-template-columns:1fr min(420px,40%);gap:3rem;align-items:start;" class="contact-grid">

        <!-- Formular -->
        <div>
            <h1 style="font-family:var(--font-serif);font-size:clamp(1.6rem,4vw,2.2rem);margin:0 0 .5rem;">Kontakt aufnehmen</h1>
            <p style="color:var(--ink-muted);margin:0 0 2rem;">Hast du Fragen, Feedback oder einen Kooperationswunsch? Schreib uns gerne!</p>

            <?php if ($contactSuccess): ?>
            <div class="alert alert-success" style="padding:.85rem 1.2rem;background:#d1fae5;color:#065f46;border-radius:var(--r);margin-bottom:1.5rem;font-size:.9rem;">
                ✅ <?php echo htmlspecialchars($contactSuccess, ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php endif; ?>

            <?php if ($contactError): ?>
            <div class="alert alert-error" style="padding:.85rem 1.2rem;background:#fee2e2;color:#991b1b;border-radius:var(--r);margin-bottom:1.5rem;font-size:.9rem;">
                ❌ <?php echo htmlspecialchars($contactError, ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php endif; ?>

            <?php if (!$contactSuccess): ?>
            <form class="auth-form" method="POST" novalidate>
                <input type="hidden" name="contact_submit" value="1">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <!-- Honeypot (verstecktes Anti-Spam-Feld) -->
                <div style="display:none;" aria-hidden="true">
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;" class="form-2col">
                    <div class="form-group">
                        <label class="form-label" for="contactName">
                            Name <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="text" id="contactName" name="contact_name" class="form-control"
                               value="<?php echo $formData['name']; ?>"
                               required maxlength="100" autocomplete="name"
                               placeholder="Dein Name">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contactEmail">
                            E-Mail <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="email" id="contactEmail" name="contact_email" class="form-control"
                               value="<?php echo $formData['email']; ?>"
                               required maxlength="200" autocomplete="email"
                               placeholder="deine@email.de">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="contactSubject">Betreff</label>
                    <input type="text" id="contactSubject" name="contact_subject" class="form-control"
                           value="<?php echo $formData['subject']; ?>"
                           maxlength="200"
                           placeholder="Worum geht es?">
                </div>

                <div class="form-group">
                    <label class="form-label" for="contactMessage">
                        Nachricht <span style="color:#ef4444;">*</span>
                    </label>
                    <textarea id="contactMessage" name="contact_message" class="form-control"
                              required minlength="20" maxlength="5000"
                              rows="6"
                              style="resize:vertical;min-height:140px;"
                              placeholder="Deine Nachricht …"><?php echo $formData['message']; ?></textarea>
                </div>

                <div class="form-group" style="font-size:.78rem;color:var(--ink-muted);margin-top:-.5rem;">
                    <span>Mit dem Absenden stimmst du unserer <a href="<?php echo SITE_URL; ?>/datenschutz" style="color:var(--accent);">Datenschutzerklärung</a> zu.</span>
                </div>

                <button type="submit" class="btn-solid btn-solid--full" style="margin-top:.5rem;">
                    Nachricht senden →
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Kontaktinfos -->
        <aside style="background:var(--surface-tint);border-radius:var(--r);border:1px solid var(--rule);padding:1.75rem;">
            <h3 style="font-family:var(--font-serif);font-size:1.1rem;margin:0 0 1.25rem;color:var(--ink);">So erreichst du uns</h3>

            <?php
            // Kontaktdaten aus Settings laden
            $contactInfo = [];
            $contactKeys = ['contact_address', 'contact_phone', 'contact_email_display', 'contact_hours'];
            try {
                $db = \CMS\Database::instance();
                foreach ($contactKeys as $key) {
                    $row = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ? LIMIT 1", [$key])->fetch();
                    if ($row && $row->option_value) {
                        $contactInfo[$key] = $row->option_value;
                    }
                }
            } catch (\Throwable $e) {}
            ?>

            <dl style="margin:0;font-size:.88rem;line-height:1.7;">
                <?php if (!empty($contactInfo['contact_email_display']) || !empty($recipientEmail)): ?>
                <dt style="font-weight:600;color:var(--ink-soft);margin-top:.75rem;">E-Mail</dt>
                <dd style="margin:0;color:var(--ink-muted);">
                    <a href="mailto:<?php echo htmlspecialchars($contactInfo['contact_email_display'] ?? $recipientEmail, ENT_QUOTES, 'UTF-8'); ?>"
                       style="color:var(--accent);">
                        <?php echo htmlspecialchars($contactInfo['contact_email_display'] ?? $recipientEmail, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </dd>
                <?php endif; ?>

                <?php if (!empty($contactInfo['contact_phone'])): ?>
                <dt style="font-weight:600;color:var(--ink-soft);margin-top:.75rem;">Telefon</dt>
                <dd style="margin:0;color:var(--ink-muted);"><?php echo htmlspecialchars($contactInfo['contact_phone'], ENT_QUOTES, 'UTF-8'); ?></dd>
                <?php endif; ?>

                <?php if (!empty($contactInfo['contact_address'])): ?>
                <dt style="font-weight:600;color:var(--ink-soft);margin-top:.75rem;">Adresse</dt>
                <dd style="margin:0;color:var(--ink-muted);"><?php echo nl2br(htmlspecialchars($contactInfo['contact_address'], ENT_QUOTES, 'UTF-8')); ?></dd>
                <?php endif; ?>

                <?php if (!empty($contactInfo['contact_hours'])): ?>
                <dt style="font-weight:600;color:var(--ink-soft);margin-top:.75rem;">Erreichbarkeit</dt>
                <dd style="margin:0;color:var(--ink-muted);"><?php echo nl2br(htmlspecialchars($contactInfo['contact_hours'], ENT_QUOTES, 'UTF-8')); ?></dd>
                <?php endif; ?>

                <?php if (empty($contactInfo)): ?>
                <dd style="margin:0;color:var(--ink-ghost);font-style:italic;">Kontaktdaten werden in den CMS-Einstellungen hinterlegt.</dd>
                <?php endif; ?>
            </dl>

            <!-- Rechtliche Links -->
            <hr style="border:none;border-top:1px solid var(--rule);margin:1.5rem 0 1rem;">
            <p style="font-size:.78rem;color:var(--ink-ghost);margin:0;">
                <a href="<?php echo SITE_URL; ?>/impressum" style="color:var(--ink-muted);text-decoration:none;">Impressum</a>
                &nbsp;·&nbsp;
                <a href="<?php echo SITE_URL; ?>/datenschutz" style="color:var(--ink-muted);text-decoration:none;">Datenschutz</a>
            </p>
        </aside>

    </div>
</div>
