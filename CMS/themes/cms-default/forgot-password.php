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
    header('Location: ' . SITE_URL . '/');
    exit;
}

$step         = ($_GET['step'] ?? 'request') === 'reset' ? 'reset' : 'request';
$resetToken   = htmlspecialchars($_GET['token'] ?? '', ENT_QUOTES, 'UTF-8');
$fpError      = '';
$fpSuccess    = '';
$fpEmail      = '';

// CSRF-Token
$csrfToken = '';
if (class_exists('\CMS\Security')) {
    $csrfToken = \CMS\Security::instance()->generateToken('forgot_password');
}

// ── Schritt 1: Anfrage verarbeiten ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fp_submit'])) {
    $csrfOk = true;
    if (class_exists('\CMS\Security')) {
        $csrfOk = \CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'forgot_password');
    }
    if (!$csrfOk) {
        $fpError = 'Sicherheitscheck fehlgeschlagen. Bitte Seite neu laden.';
    } else {
        $fpEmail = filter_var(trim($_POST['fp_email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '';
        if (empty($fpEmail)) {
            $fpError = 'Bitte eine gültige E-Mail-Adresse eingeben.';
        } else {
            // Token erzeugen und in DB speichern
            try {
                $db        = \CMS\Database::instance();
                $prefix    = $db->getPrefix();
                $userRow   = $db->execute("SELECT id FROM {$prefix}users WHERE email = ? LIMIT 1", [$fpEmail])->fetch();
                if ($userRow) {
                    $token    = bin2hex(random_bytes(32));
                    $expires  = date('Y-m-d H:i:s', time() + 3600); // 1 Stunde
                    // Altes Token löschen, neues einfügen
                    $db->execute("DELETE FROM {$prefix}password_resets WHERE email = ?", [$fpEmail]);
                    $db->execute(
                        "INSERT INTO {$prefix}password_resets (email, token, expires_at, created_at) VALUES (?, ?, ?, NOW())",
                        [$fpEmail, hash('sha256', $token), $expires]
                    );
                    // Reset-Mail senden
                    $resetUrl = SITE_URL . '/forgot-password?step=reset&token=' . $token;
                    $siteName = defined('SITE_NAME') ? SITE_NAME : '365CMS';
                    $subject  = "[$siteName] Passwort zurücksetzen";
                    $body     = "Hallo,\n\ndu hast eine Anfrage zum Zurücksetzen deines Passworts gestellt.\n\n"
                              . "Klicke auf den folgenden Link (gültig für 1 Stunde):\n"
                              . $resetUrl . "\n\n"
                              . "Falls du diese Anfrage nicht gestellt hast, ignoriere diese E-Mail.\n\n"
                              . "Viele Grüße,\n" . $siteName;
                    $headers  = "Content-Type: text/plain; charset=UTF-8\r\nFrom: no-reply@" . (defined('SITE_DOMAIN') ? SITE_DOMAIN : 'localhost');
                    @mail($fpEmail, $subject, $body, $headers);
                }
                // Aus Sicherheitsgründen immer Erfolgsmeldung zeigen
                $fpSuccess = 'Falls ein Konto mit dieser E-Mail-Adresse existiert, haben wir dir einen Reset-Link gesendet.';
            } catch (\Throwable $e) {
                $fpError = 'Fehler beim Verarbeiten der Anfrage. Bitte versuche es erneut.';
            }
        }
    }
    if (class_exists('\CMS\Security')) {
        $csrfToken = \CMS\Security::instance()->generateToken('forgot_password');
    }
}

// ── Schritt 2: Neues Passwort setzen ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_submit'])) {
    $csrfOk = true;
    if (class_exists('\CMS\Security')) {
        $csrfOk = \CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'forgot_password');
    }
    if (!$csrfOk) {
        $fpError = 'Sicherheitscheck fehlgeschlagen.';
    } else {
        $token    = $_POST['reset_token'] ?? '';
        $pass1    = $_POST['new_password']  ?? '';
        $pass2    = $_POST['new_password2'] ?? '';

        if (strlen($pass1) < 8) {
            $fpError = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
        } elseif ($pass1 !== $pass2) {
            $fpError = 'Die Passwörter stimmen nicht überein.';
        } elseif (empty($token)) {
            $fpError = 'Ungültiger Reset-Token.';
        } else {
            try {
                $db     = \CMS\Database::instance();
                $prefix = $db->getPrefix();
                $hToken = hash('sha256', $token);
                $row    = $db->execute(
                    "SELECT email, expires_at FROM {$prefix}password_resets WHERE token = ? LIMIT 1",
                    [$hToken]
                )->fetch();

                if (!$row) {
                    $fpError = 'Ungültiger oder bereits verwendeter Reset-Link.';
                } elseif (strtotime($row->expires_at) < time()) {
                    $fpError = 'Dieser Reset-Link ist abgelaufen. Bitte starte den Vorgang erneut.';
                } else {
                    $hash = password_hash($pass1, PASSWORD_BCRYPT);
                    $db->execute("UPDATE {$prefix}users SET password = ? WHERE email = ?", [$hash, $row->email]);
                    $db->execute("DELETE FROM {$prefix}password_resets WHERE email = ?", [$row->email]);
                    $fpSuccess = 'Dein Passwort wurde erfolgreich geändert. Du kannst dich jetzt anmelden.';
                    $step      = 'done';
                }
            } catch (\Throwable $e) {
                $fpError = 'Fehler beim Zurücksetzen des Passworts. Bitte versuche es erneut.';
            }
        }
    }
}
?>

<main id="main" role="main" style="background:linear-gradient(135deg,#e3f2fd 0%,#f5f9fc 100%);min-height:calc(100vh - 200px);display:flex;align-items:center;padding:2rem 1.5rem;">
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
            <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($fpSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <a href="<?php echo SITE_URL; ?>/login" class="btn-solid btn-solid--full">Jetzt anmelden</a>

        <?php elseif ($step === 'reset' && !empty($resetToken)): ?>
            <!-- Schritt 2: Neues Passwort festlegen -->
            <h1 class="auth-title">Neues Passwort</h1>
            <p class="auth-subtitle">Lege ein neues sicheres Passwort fest.</p>

            <?php if ($fpError): ?>
            <div class="alert alert-error" role="alert"><?php echo htmlspecialchars($fpError, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form class="auth-form" method="POST" novalidate>
                <input type="hidden" name="reset_submit" value="1">
                <input type="hidden" name="csrf_token"  value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="reset_token" value="<?php echo htmlspecialchars($resetToken, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label class="form-label" for="newPassword">Neues Passwort</label>
                    <div class="form-control-wrap form-control-wrap--password">
                        <input type="password" id="newPassword" name="new_password" class="form-control"
                               autocomplete="new-password" required minlength="8"
                               placeholder="Mindestens 8 Zeichen" autofocus>
                        <button type="button" class="btn-icon form-password-toggle" aria-label="Passwort anzeigen">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="newPassword2">Passwort wiederholen</label>
                    <input type="password" id="newPassword2" name="new_password2" class="form-control"
                           autocomplete="new-password" required minlength="8"
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
            <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($fpSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php else: ?>
            <form class="auth-form" method="POST" novalidate>
                <input type="hidden" name="fp_submit"   value="1">
                <input type="hidden" name="csrf_token"  value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label class="form-label" for="fpEmail">E-Mail-Adresse</label>
                    <input type="email" id="fpEmail" name="fp_email" class="form-control"
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
                <p><a href="<?php echo SITE_URL; ?>/login">← Zurück zur Anmeldung</a></p>
                <p style="margin-top:0.5rem;"><a href="<?php echo SITE_URL; ?>/">← Zurück zur Startseite</a></p>
            </div>

        </div><!-- /.auth-card -->
    </div>
</main>
