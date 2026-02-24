<?php
/**
 * Meridian CMS Default – Login Template
 *
 * @package CMSv2\Themes\CmsDefault
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Bereits eingeloggt → weiterleiten
if (function_exists('theme_is_logged_in') && theme_is_logged_in()) {
    header('Location: ' . SITE_URL . '/');
    exit;
}

$redirect   = htmlspecialchars($_GET['redirect'] ?? '/');
$loginError = '';
$loginEmail = '';

// Login-Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $csrfOk = true;
    if (class_exists('\CMS\Security')) {
        $csrfOk = \CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'login');
    }
    if (!$csrfOk) {
        $loginError = 'Sicherheitscheck fehlgeschlagen. Bitte Seite neu laden.';
    } else {
        // Erlaube Login via E-Mail ODER Benutzername
        // Filterung entfernen, da Usernames keine E-Mail sein müssen
        $loginInput = trim($_POST['email'] ?? '');
        $loginPass  = $_POST['password'] ?? '';
        
        if (empty($loginInput) || empty($loginPass)) {
            $loginError = 'Bitte Benutzername/E-Mail und Passwort eingeben.';
        } elseif (function_exists('theme_login_user')) {
            $result = theme_login_user($loginInput, $loginPass);
            if ($result === true) {
                header('Location: ' . SITE_URL . $redirect);
                exit;
            } else {
                $loginError = is_string($result) ? $result : 'Ungültige Zugangsdaten.';
            }
        }
    }
}

// CSRF-Token
$csrfToken = '';
if (class_exists('\CMS\Security')) {
    $csrfToken = \CMS\Security::instance()->generateToken('login');
}
?>

<div class="auth-wrap">
    <div class="auth-card">

        <!-- Logo -->
        <a class="auth-logo" href="<?php echo SITE_URL; ?>/">
            <span class="logo-word"><?php echo htmlspecialchars(defined('SITE_NAME') ? SITE_NAME : '365CMS'); ?></span>
        </a>

        <h1 class="auth-title">Willkommen zurück</h1>
        <p class="auth-subtitle">Melde dich mit deinen Zugangsdaten an.</p>

        <?php if ($loginError): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($loginError); ?></div>
        <?php endif; ?>

        <form class="auth-form" method="POST" novalidate>
            <input type="hidden" name="login_submit" value="1">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="redirect" value="<?php echo $redirect; ?>">

            <div class="form-group">
                <label class="form-label" for="loginEmail">E-Mail-Adresse</label>
                <input type="email" id="loginEmail" name="email" class="form-control"
                       value="<?php echo htmlspecialchars($loginEmail); ?>"
                       autocomplete="email" required autofocus
                       placeholder="deine@email.de">
            </div>

            <div class="form-group">
                <label class="form-label" for="loginPassword">
                    Passwort
                    <a href="<?php echo SITE_URL; ?>/forgot-password" class="form-label-link">Vergessen?</a>
                </label>
                <div class="form-control-wrap form-control-wrap--password">
                    <input type="password" id="loginPassword" name="password" class="form-control"
                           autocomplete="current-password" required
                           placeholder="Dein Passwort">
                    <button type="button" class="btn-icon form-password-toggle" aria-label="Passwort anzeigen">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon-eye">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="form-group form-group--checkbox">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" value="1">
                    Angemeldet bleiben
                </label>
            </div>

            <button type="submit" class="btn-solid btn-solid--full auth-submit">Anmelden</button>
        </form>

        <p class="auth-switch">
            Noch kein Konto?
            <a href="<?php echo SITE_URL; ?>/register">Jetzt registrieren →</a>
        </p>

    </div><!-- /.auth-card -->
</div><!-- /.auth-wrap -->
