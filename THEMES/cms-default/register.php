<?php
/**
 * Meridian CMS Default – Registrierung Template
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

$regError    = '';
$regSuccess  = '';
$regEmail    = '';
$regUsername = '';

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    $csrfOk = true;
    if (class_exists('\CMS\Security')) {
        $csrfOk = \CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'register');
    }
    if (!$csrfOk) {
        $regError = 'Sicherheitscheck fehlgeschlagen. Bitte Seite neu laden.';
    } else {
        $regEmail    = filter_var($_POST['email']    ?? '', FILTER_VALIDATE_EMAIL) ?: '';
        $regUsername = preg_replace('/[^a-zA-Z0-9_\-.]/', '', $_POST['username'] ?? '');
        $regPass     = $_POST['password']  ?? '';
        $regPass2    = $_POST['password2'] ?? '';

        if (empty($regEmail)) {
            $regError = 'Bitte eine gültige E-Mail-Adresse eingeben.';
        } elseif (strlen($regUsername) < 3) {
            $regError = 'Benutzername muss mindestens 3 Zeichen lang sein (a–z, 0–9, _, -, .).';
        } elseif (strlen($regPass) < 8) {
            $regError = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
        } elseif ($regPass !== $regPass2) {
            $regError = 'Die Passwörter stimmen nicht überein.';
        } elseif (empty($_POST['terms'])) {
            $regError = 'Bitte akzeptiere die Nutzungsbedingungen.';
        } elseif (function_exists('theme_register_user')) {
            $result = theme_register_user($regEmail, $regUsername, $regPass);
            if ($result === true) {
                $regSuccess = 'Konto erfolgreich erstellt! Du kannst dich jetzt anmelden.';
                $regEmail = $regUsername = '';
            } else {
                $regError = is_string($result) ? $result : 'Registrierung fehlgeschlagen.';
            }
        }
    }
}

// CSRF-Token
$csrfToken = '';
if (class_exists('\CMS\Security')) {
    $csrfToken = \CMS\Security::instance()->generateToken('register');
}
?>

<div class="auth-wrap">
    <div class="auth-card">

        <!-- Logo -->
        <a class="auth-logo" href="<?php echo SITE_URL; ?>/">
            <span class="logo-word"><?php echo htmlspecialchars(defined('SITE_NAME') ? SITE_NAME : '365CMS'); ?></span>
        </a>

        <h1 class="auth-title">Konto erstellen</h1>
        <p class="auth-subtitle">Kostenlos registrieren und alle Inhalte lesen.</p>

        <?php if ($regError): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($regError); ?></div>
        <?php endif; ?>

        <?php if ($regSuccess): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($regSuccess); ?></div>
        <p style="text-align:center;margin-top:1rem;">
            <a href="<?php echo SITE_URL; ?>/login" class="btn-solid">Jetzt anmelden</a>
        </p>
        <?php else: ?>

        <form class="auth-form" method="POST" novalidate>
            <input type="hidden" name="register_submit" value="1">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <div class="form-group">
                <label class="form-label" for="regEmail">E-Mail-Adresse</label>
                <input type="email" id="regEmail" name="email" class="form-control"
                       value="<?php echo htmlspecialchars($regEmail); ?>"
                       autocomplete="email" required autofocus
                       placeholder="deine@email.de">
            </div>

            <div class="form-group">
                <label class="form-label" for="regUsername">Benutzername</label>
                <input type="text" id="regUsername" name="username" class="form-control"
                       value="<?php echo htmlspecialchars($regUsername); ?>"
                       autocomplete="username" required minlength="3" maxlength="40"
                       pattern="[a-zA-Z0-9_\-.]+"
                       placeholder="mein_name">
                <small class="form-text">Nur Buchstaben, Ziffern, Bindestrich, Punkt und Unterstrich</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="regPassword">Passwort</label>
                <div class="form-control-wrap form-control-wrap--password">
                    <input type="password" id="regPassword" name="password" class="form-control"
                           autocomplete="new-password" required minlength="8"
                           placeholder="Mindestens 8 Zeichen">
                    <button type="button" class="btn-icon form-password-toggle" aria-label="Passwort anzeigen">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="regPassword2">Passwort bestätigen</label>
                <input type="password" id="regPassword2" name="password2" class="form-control"
                       autocomplete="new-password" required minlength="8"
                       placeholder="Passwort wiederholen">
            </div>

            <div class="form-group form-group--checkbox">
                <label class="checkbox-label">
                    <input type="checkbox" name="terms" value="1" required>
                    Ich akzeptiere die
                    <a href="<?php echo SITE_URL; ?>/agb" target="_blank">Nutzungsbedingungen</a>
                    und die
                    <a href="<?php echo SITE_URL; ?>/datenschutz" target="_blank">Datenschutzerklärung</a>.
                    <span style="color:#ef4444;">*</span>
                </label>
            </div>

            <button type="submit" class="btn-solid btn-solid--full auth-submit">Kostenlos registrieren</button>
        </form>

        <?php endif; ?>

        <p class="auth-switch">
            Bereits registriert?
            <a href="<?php echo SITE_URL; ?>/login">Jetzt anmelden →</a>
        </p>

    </div><!-- /.auth-card -->
</div><!-- /.auth-wrap -->
