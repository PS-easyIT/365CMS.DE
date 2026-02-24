<?php
/**
 * Login-Seite – MedCare Pro Theme
 *
 * @package MedCarePro
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

// Bereits eingeloggt → Weiterleitung
if (theme_is_logged_in()) {
    header('Location: ' . SITE_URL . '/member');
    exit;
}

$error   = null;
$success = null;

// POST-Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mc_login'])) {
    try {
        if (!\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'mc_login')) {
            $error = 'Sicherheitscheck fehlgeschlagen. Bitte laden Sie die Seite neu.';
        } else {
            $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
            $password = $_POST['password'] ?? '';

            if (!$email || empty($password)) {
                $error = 'Bitte E-Mail und Passwort eingeben.';
            } else {
                $result = \CMS\Auth::instance()->login($email, $password);
                if ($result === true) {
                    $redirect = filter_var($_POST['redirect'] ?? '', FILTER_VALIDATE_URL) ?: SITE_URL . '/member';
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    $error = is_string($result) ? $result : 'Ungültige E-Mail-Adresse oder falsches Passwort.';
                }
            }
        }
    } catch (\Throwable $e) {
        $error = 'Anmeldung fehlgeschlagen. Bitte versuchen Sie es erneut.';
    }
}

try {
    $csrfToken = \CMS\Security::instance()->generateToken('mc_login');
} catch (\Throwable $e) {
    $csrfToken = '';
}

$siteUrl = SITE_URL;
$safe    = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$redirect = $safe(filter_var($_GET['redirect'] ?? '', FILTER_SANITIZE_URL));

get_header();
?>
<main id="main" class="mc-main" role="main"
      style="min-height:70vh;display:flex;align-items:center;justify-content:center;padding:var(--spacing-2xl) 0;">
    <div class="mc-container">
        <div class="mc-form-card">
            <!-- Logo / Branding -->
            <div style="text-align:center;margin-bottom:1.75rem;">
                <span style="font-size:2.5rem;" aria-hidden="true">🔒</span>
                <h1 style="font-size:var(--font-2xl);color:var(--secondary-color);margin:.5rem 0 .25rem;">Anmelden</h1>
                <p style="color:var(--muted-color);font-size:var(--font-sm);">Willkommen zurück bei MedCare Pro</p>
            </div>

            <?php if (!empty($error)) : ?>
            <div class="mc-alert mc-alert-error" role="alert"><?php echo $safe($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($success)) : ?>
            <div class="mc-alert mc-alert-success" role="status"><?php echo $safe($success); ?></div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="mc_login" value="1">
                <input type="hidden" name="csrf_token" value="<?php echo $safe($csrfToken); ?>">
                <?php if (!empty($redirect)) : ?>
                <input type="hidden" name="redirect" value="<?php echo $redirect; ?>">
                <?php endif; ?>

                <div class="mc-form-group">
                    <label for="login-email" class="mc-label">
                        E-Mail-Adresse <span aria-hidden="true" style="color:#ef4444;">*</span>
                    </label>
                    <input id="login-email" type="email" name="email" class="mc-input"
                           value="<?php echo $safe($_POST['email'] ?? ''); ?>"
                           autocomplete="email" required
                           aria-required="true"
                           placeholder="ihre@email.de">
                </div>

                <div class="mc-form-group">
                    <label for="login-password" class="mc-label">
                        Passwort <span aria-hidden="true" style="color:#ef4444;">*</span>
                    </label>
                    <input id="login-password" type="password" name="password" class="mc-input"
                           autocomplete="current-password" required
                           aria-required="true"
                           placeholder="Ihr Passwort">
                </div>

                <button type="submit" class="mc-btn mc-btn-primary" style="width:100%;justify-content:center;margin-top:.5rem;">
                    🔑 Anmelden
                </button>
            </form>

            <hr class="mc-form-divider">

            <div class="mc-form-links">
                <a href="<?php echo $safe($siteUrl); ?>/forgot-password">Passwort vergessen?</a>
                <a href="<?php echo $safe($siteUrl); ?>/register">Jetzt registrieren</a>
            </div>

            <p class="mc-dsgvo-note">
                🔒 Ihre Anmeldedaten werden verschlüsselt übertragen und gemäß DSGVO &amp; § 203 StGB geschützt.
            </p>
        </div>
    </div>
</main>
<?php get_footer(); ?>
