<?php
/**
 * Registrierung – MedCare Pro Theme
 *
 * Unterstützt Patienten- und Arzt-Registrierung via GET-Parameter ?type=doctor
 *
 * @package MedCarePro
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

if (theme_is_logged_in()) {
    header('Location: ' . SITE_URL . '/member');
    exit;
}

$isDoctor = ($_GET['type'] ?? '') === 'doctor';
$error    = null;
$success  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mc_register'])) {
    try {
        if (empty($_POST['honeypot_field'])) {  // Honeypot anti-spam
            if (!\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'mc_register')) {
                $error = 'Sicherheitscheck fehlgeschlagen. Bitte laden Sie die Seite neu.';
            } else {
                $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
                $username = htmlspecialchars(trim($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
                $password = $_POST['password'] ?? '';
                $passConf = $_POST['password_confirm'] ?? '';
                $privacyAgreed = !empty($_POST['privacy']);
                $regType  = ($_POST['register_type'] ?? 'patient') === 'doctor' ? 'doctor' : 'patient';

                if (!$email) {
                    $error = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
                } elseif (strlen($username) < 3) {
                    $error = 'Der Benutzername muss mindestens 3 Zeichen lang sein.';
                } elseif (strlen($password) < 8) {
                    $error = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
                } elseif ($password !== $passConf) {
                    $error = 'Die Passwörter stimmen nicht überein.';
                } elseif (!$privacyAgreed) {
                    $error = 'Bitte akzeptieren Sie die Datenschutzerklärung.';
                } else {
                    $result = \CMS\Auth::instance()->register($email, $username, $password, ['role' => $regType]);
                    if ($result === true) {
                        $success = 'Registrierung erfolgreich! Sie können sich jetzt anmelden.';
                    } else {
                        $error = is_string($result) ? $result : 'Registrierung fehlgeschlagen. Bitte versuchen Sie es erneut.';
                    }
                }
            }
        }
    } catch (\Throwable $e) {
        $error = 'Registrierung fehlgeschlagen. Bitte versuchen Sie es erneut.';
    }
}

try {
    $csrfToken = \CMS\Security::instance()->generateToken('mc_register');
} catch (\Throwable $e) {
    $csrfToken = '';
}

$siteUrl  = SITE_URL;
$safe     = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$privText = mc_get_setting('dsgvo_medical', 'privacy_form_text', 'Ihre Daten werden gemäß DSGVO und § 203 StGB vertraulich behandelt.');

get_header();
?>
<main id="main" class="mc-main" role="main"
      style="padding:var(--spacing-2xl) 0;display:flex;align-items:center;justify-content:center;min-height:70vh;">
    <div class="mc-container">
        <div class="mc-form-card" style="max-width:520px;">

            <div style="text-align:center;margin-bottom:1.75rem;">
                <span style="font-size:2.5rem;" aria-hidden="true"><?php echo $isDoctor ? '👨‍⚕️' : '🏥'; ?></span>
                <h1 style="font-size:var(--font-2xl);color:var(--secondary-color);margin:.5rem 0 .25rem;">
                    <?php echo $isDoctor ? 'Als Arzt registrieren' : 'Konto erstellen'; ?>
                </h1>
                <p style="color:var(--muted-color);font-size:var(--font-sm);">
                    <?php echo $isDoctor
                        ? 'Erstellen Sie Ihr Arztprofil und erreichen Sie neue Patienten.'
                        : 'Kostenlos anmelden und Ärzte finden.'; ?>
                </p>
            </div>

            <!-- Typ-Umschalter -->
            <div style="display:flex;gap:.5rem;margin-bottom:1.5rem;background:var(--bg-secondary);padding:.4rem;border-radius:var(--radius-pill);">
                <a href="?type=patient" class="mc-btn<?php echo !$isDoctor ? ' mc-btn-primary' : ' mc-btn-ghost'; ?>"
                   style="flex:1;justify-content:center;<?php echo !$isDoctor ? '' : 'color:var(--muted-color);'; ?>"
                   aria-pressed="<?php echo !$isDoctor ? 'true' : 'false'; ?>">
                    Patient
                </a>
                <a href="?type=doctor" class="mc-btn<?php echo $isDoctor ? ' mc-btn-primary' : ' mc-btn-ghost'; ?>"
                   style="flex:1;justify-content:center;<?php echo $isDoctor ? '' : 'color:var(--muted-color);'; ?>"
                   aria-pressed="<?php echo $isDoctor ? 'true' : 'false'; ?>">
                    Arzt / Therapeut
                </a>
            </div>

            <?php if (!empty($error)) : ?>
            <div class="mc-alert mc-alert-error" role="alert"><?php echo $safe($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($success)) : ?>
            <div class="mc-alert mc-alert-success" role="status">
                <?php echo $safe($success); ?><br>
                <a href="<?php echo $safe($siteUrl); ?>/login" style="font-weight:700;">Jetzt anmelden →</a>
            </div>
            <?php endif; ?>

            <?php if (empty($success)) : ?>
            <form method="POST" novalidate>
                <input type="hidden" name="mc_register" value="1">
                <input type="hidden" name="csrf_token" value="<?php echo $safe($csrfToken); ?>">
                <input type="hidden" name="register_type" value="<?php echo $isDoctor ? 'doctor' : 'patient'; ?>">
                <!-- Honeypot (Spam-Schutz – darf nicht ausgefüllt werden) -->
                <div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">
                    <label for="honeypot_field">Dieses Feld leer lassen</label>
                    <input id="honeypot_field" type="text" name="honeypot_field" tabindex="-1" autocomplete="off">
                </div>

                <div class="mc-form-group">
                    <label for="reg-email" class="mc-label">
                        E-Mail-Adresse <span aria-hidden="true" style="color:#ef4444;">*</span>
                    </label>
                    <input id="reg-email" type="email" name="email" class="mc-input"
                           value="<?php echo $safe($_POST['email'] ?? ''); ?>"
                           autocomplete="email" required aria-required="true"
                           placeholder="ihre@email.de">
                </div>

                <div class="mc-form-group">
                    <label for="reg-username" class="mc-label">
                        <?php echo $isDoctor ? 'Name / Praxisname' : 'Benutzername'; ?>
                        <span aria-hidden="true" style="color:#ef4444;">*</span>
                    </label>
                    <input id="reg-username" type="text" name="username" class="mc-input"
                           value="<?php echo $safe($_POST['username'] ?? ''); ?>"
                           autocomplete="name" required aria-required="true"
                           placeholder="<?php echo $isDoctor ? 'Dr. med. Mustermann' : 'max_mustermann'; ?>"
                           minlength="3">
                </div>

                <div class="mc-form-group">
                    <label for="reg-password" class="mc-label">
                        Passwort <span aria-hidden="true" style="color:#ef4444;">*</span>
                    </label>
                    <input id="reg-password" type="password" name="password" class="mc-input"
                           autocomplete="new-password" required aria-required="true"
                           placeholder="Mindestens 8 Zeichen" minlength="8">
                    <p class="mc-form-hint">Min. 8 Zeichen – nutzen Sie Groß-/Kleinbuchstaben und Zahlen.</p>
                </div>

                <div class="mc-form-group">
                    <label for="reg-password-confirm" class="mc-label">
                        Passwort bestätigen <span aria-hidden="true" style="color:#ef4444;">*</span>
                    </label>
                    <input id="reg-password-confirm" type="password" name="password_confirm" class="mc-input"
                           autocomplete="new-password" required aria-required="true"
                           placeholder="Passwort wiederholen">
                </div>

                <div class="mc-form-group" style="display:flex;align-items:flex-start;gap:.6rem;">
                    <input id="reg-privacy" type="checkbox" name="privacy" value="1" required aria-required="true"
                           style="width:18px;height:18px;margin-top:.2rem;flex-shrink:0;accent-color:var(--primary-color);">
                    <label for="reg-privacy" style="font-size:var(--font-sm);color:var(--text-secondary);cursor:pointer;">
                        Ich habe die
                        <a href="<?php echo $safe($siteUrl); ?>/datenschutz" target="_blank" rel="noopener">Datenschutzerklärung</a>
                        gelesen und stimme der Verarbeitung meiner Daten zu.
                        <span aria-hidden="true" style="color:#ef4444;">*</span>
                    </label>
                </div>

                <button type="submit" class="mc-btn mc-btn-primary"
                        style="width:100%;justify-content:center;margin-top:.25rem;">
                    ✅ <?php echo $isDoctor ? 'Arztprofil erstellen' : 'Konto erstellen'; ?>
                </button>
            </form>
            <?php endif; ?>

            <hr class="mc-form-divider">
            <div class="mc-form-links">
                <a href="<?php echo $safe($siteUrl); ?>/login">Bereits registriert? Anmelden</a>
            </div>
            <p class="mc-dsgvo-note">🔒 <?php echo $safe($privText); ?></p>
        </div>
    </div>
</main>
<?php get_footer(); ?>
