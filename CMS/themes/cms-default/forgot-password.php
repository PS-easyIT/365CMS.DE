<?php
/**
 * Meridian CMS Default – Passwort vergessen Template
 *
 * Unterstützt zwei Modi (via GET-Parameter `step`):
 *   1. step=request  – E-Mail-Adresse eingeben (Standard)
 *   2. step=reset    – Neues Passwort setzen (via Token aus E-Mail)
 *
 * @package CMSv2\Themes\CmsDefault
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Bereits eingeloggt → weiterleiten
if (function_exists('theme_is_logged_in') && theme_is_logged_in()) {
    $loggedInRedirect = function_exists('theme_logged_in_redirect_path')
        ? theme_logged_in_redirect_path()
        : '/member';
    header('Location: ' . $loggedInRedirect);
    exit;
}

$requestedStep = is_string($_GET['step'] ?? null) ? $_GET['step'] : 'request';
$step         = in_array($requestedStep, ['reset', 'done'], true) ? $requestedStep : 'request';
$resetToken   = is_string($_GET['token'] ?? null) && preg_match('/^[a-f0-9]{16,128}$/i', $_GET['token']) === 1 ? $_GET['token'] : '';
$themeFlashAvailable = function_exists('meridian_get_flash');
$themeFlash = $themeFlashAvailable ? (meridian_get_flash() ?? []) : [];
$fpError      = $themeFlashAvailable
    ? (trim((string) ($themeFlash['type'] ?? '')) === 'error' ? trim((string) ($themeFlash['message'] ?? '')) : '')
    : trim((string) ($_SESSION['error'] ?? ''));
$fpSuccess    = $themeFlashAvailable
    ? (trim((string) ($themeFlash['type'] ?? '')) === 'success' ? trim((string) ($themeFlash['message'] ?? '')) : '')
    : trim((string) ($_SESSION['success'] ?? ''));
$forgotPasswordUrl = function_exists('meridian_auth_url') ? meridian_auth_url('forgot-password') : rtrim((string) SITE_URL, '/') . '/forgot-password';
$loginUrl = function_exists('meridian_auth_url') ? meridian_auth_url('login') : rtrim((string) SITE_URL, '/') . '/login';
$homeUrl = rtrim((string) SITE_URL, '/') . '/';
$passwordPolicyHint = 'Mindestens 12 Zeichen sowie Groß-/Kleinbuchstabe, Zahl und Sonderzeichen.';

if (!$themeFlashAvailable) {
    unset($_SESSION['error'], $_SESSION['success']);
}

// CSRF-Token
$csrfToken = '';
if (class_exists('\CMS\Security')) {
    $csrfToken = \CMS\Security::instance()->generateToken('forgot_password');
}

// Die Formulare senden an den Core-Handler (PublicRouter::handleForgotPassword()): Er prüft
// CSRF, Rate-Limits je IP/Konto/Token, versendet über den konfigurierten Mail-Dienst und
// beendet nach dem Reset alle anderen Sitzungen. Eine eigene Theme-Logik mit mail() und ohne
// Rate-Limit entfällt; POST-Anfragen erreichen dieses Template ohnehin nicht.
$oldValues = is_array($_SESSION['auth_form_old']['forgot-password'] ?? null) ? $_SESSION['auth_form_old']['forgot-password'] : [];
$fpEmail = trim((string) ($oldValues['email'] ?? ''));
unset($_SESSION['auth_form_old']['forgot-password']);
?>

<div class="auth-main" style="background:linear-gradient(135deg,#e3f2fd 0%,#f5f9fc 100%);min-height:calc(100vh - 200px);display:flex;align-items:center;padding:2rem 1.5rem;">
    <div style="width:100%;max-width:440px;margin:0 auto;">

        <div class="auth-card">

            <!-- Logo -->
            <div class="auth-logo">
                <svg class="network-icon" style="width:56px;height:56px;color:var(--accent);margin:0 auto 0.75rem;display:block;"
                     viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="30" cy="30" r="6" fill="currentColor"/>
                    <circle cx="15" cy="15" r="5" fill="currentColor"/>
                    <circle cx="45" cy="15" r="5" fill="currentColor"/>
                    <circle cx="15" cy="45" r="5" fill="currentColor"/>
                    <circle cx="45" cy="45" r="5" fill="currentColor"/>
                    <line x1="30" y1="30" x2="15" y2="15" stroke="currentColor" stroke-width="2"/>
                    <line x1="30" y1="30" x2="45" y2="15" stroke="currentColor" stroke-width="2"/>
                    <line x1="30" y1="30" x2="15" y2="45" stroke="currentColor" stroke-width="2"/>
                    <line x1="30" y1="30" x2="45" y2="45" stroke="currentColor" stroke-width="2"/>
                </svg>
                <h1><?php echo htmlspecialchars(defined('SITE_NAME') ? SITE_NAME : '365CMS', ENT_QUOTES, 'UTF-8'); ?></h1>
            </div>

        <?php if ($step === 'done'): ?>
            <!-- Erfolg: Weiterleitung zum Login -->
            <h1 class="auth-title">Passwort geändert</h1>
            <p class="auth-subtitle">Du kannst dich jetzt mit deinem neuen Passwort anmelden.</p>
            <?php if ($fpSuccess): ?>
            <div class="alert alert-success" role="status"><?php echo htmlspecialchars($fpSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <a href="<?php echo htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn-solid btn-solid--full">Jetzt anmelden</a>

        <?php elseif ($step === 'reset' && !empty($resetToken)): ?>
            <!-- Schritt 2: Neues Passwort festlegen -->
            <h1 class="auth-title">Neues Passwort</h1>
            <p class="auth-subtitle">Lege ein neues sicheres Passwort fest.</p>

            <?php if ($fpError): ?>
            <div class="alert alert-error" role="alert"><?php echo htmlspecialchars($fpError, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="<?php echo htmlspecialchars($forgotPasswordUrl, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="forgot_password_action" value="reset_password">
                <input type="hidden" name="csrf_token"  value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="reset_token" value="<?php echo htmlspecialchars($resetToken, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label class="form-label" for="newPassword">Neues Passwort</label>
                    <div class="form-control-wrap form-control-wrap--password">
                        <input type="password" id="newPassword" name="new_password" class="form-control"
                               autocomplete="new-password" required minlength="12"
                               placeholder="Mindestens 12 Zeichen" autofocus>
                        <button type="button" class="btn-icon form-password-toggle" aria-label="Passwort anzeigen">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <small class="form-text"><?php echo htmlspecialchars($passwordPolicyHint, ENT_QUOTES, 'UTF-8'); ?></small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="newPassword2">Passwort wiederholen</label>
                    <input type="password" id="newPassword2" name="new_password2" class="form-control"
                           autocomplete="new-password" required minlength="12"
                           placeholder="Passwort wiederholen">
                </div>

                <button type="submit" class="btn-solid btn-solid--full auth-submit">Passwort ändern</button>
            </form>

        <?php else: ?>
            <!-- Schritt 1: E-Mail-Adresse eingeben -->
            <h1 class="auth-title">Passwort zurücksetzen</h1>
            <p class="auth-subtitle">Gib deine E-Mail-Adresse ein – wir senden dir einen Reset-Link.</p>

            <?php if ($fpError): ?>
            <div class="alert alert-error" role="alert"><?php echo htmlspecialchars($fpError, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($fpSuccess): ?>
            <div class="alert alert-success" role="status"><?php echo htmlspecialchars($fpSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php else: ?>
            <form class="auth-form" method="POST" action="<?php echo htmlspecialchars($forgotPasswordUrl, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="forgot_password_action" value="request_reset">
                <input type="hidden" name="csrf_token"  value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label class="form-label" for="fpEmail">E-Mail-Adresse</label>
                    <input type="email" id="fpEmail" name="email" class="form-control"
                           value="<?php echo htmlspecialchars($fpEmail, ENT_QUOTES, 'UTF-8'); ?>"
                           autocomplete="email" required autofocus
                           placeholder="deine@email.de">
                </div>

                <button type="submit" class="btn-solid btn-solid--full auth-submit">Reset-Link senden</button>
            </form>
            <?php endif; ?>

        <?php endif; ?>

            <!-- Footer Links -->
            <div class="auth-footer">
                <p><a href="<?php echo htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8'); ?>">← Zurück zur Anmeldung</a></p>
                <p style="margin-top:0.5rem;"><a href="<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>">← Zurück zur Startseite</a></p>
            </div>

        </div><!-- /.auth-card -->
    </div>
</div>
