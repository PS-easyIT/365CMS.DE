<?php
/**
 * Register Template
 *
 * @package IT_Expert_Network_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$siteUrl      = SITE_URL;
$themeManager = \CMS\ThemeManager::instance();
$siteTitle    = $themeManager->getSiteTitle();
$error        = theme_get_flash('error');
$success      = theme_get_flash('success');

// Felder aus fehlgeschlagenem Submit wiederherstellen
$savedUsername = htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8');
$savedEmail    = htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<main id="main" role="main" style="background:linear-gradient(135deg,#e3f2fd 0%,#f5f9fc 100%);min-height:calc(100vh - 200px);display:flex;align-items:center;padding:var(--spacing-lg);">
    <div style="width:100%;max-width:480px;margin:0 auto;">

        <div class="auth-card">

            <!-- Logo -->
            <div class="auth-logo">
                <svg class="network-icon" style="width:56px;height:56px;color:var(--primary-color);margin:0 auto 0.75rem;display:block;"
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
                <h1><?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p>Erstelle dein kostenloses Konto</p>
            </div>

            <!-- Flash Messages -->
            <?php if ($error && trim($error) !== '') : ?>
                <div class="alert alert-error" role="alert">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($success && trim($success) !== '') : ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <!-- Register Form -->
            <form method="POST" action="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/register" novalidate>
                <?php theme_csrf_field('register'); ?>

                <div class="form-group">
                    <label class="form-label" for="username">Benutzername</label>
                    <input class="form-control"
                           type="text"
                           id="username"
                           name="username"
                           autocomplete="username"
                           required
                           autofocus
                           minlength="3"
                           maxlength="60"
                           pattern="[a-zA-Z0-9_\-]+"
                           value="<?php echo $savedUsername; ?>">
                    <span class="form-hint">Nur Buchstaben, Zahlen, - und _ erlaubt.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">E-Mail-Adresse</label>
                    <input class="form-control"
                           type="email"
                           id="email"
                           name="email"
                           autocomplete="email"
                           required
                           value="<?php echo $savedEmail; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Passwort</label>
                    <input class="form-control"
                           type="password"
                           id="password"
                           name="password"
                           autocomplete="new-password"
                           required
                           minlength="8">
                    <span class="form-hint">Mindestens 8 Zeichen, empfohlen: Buchstaben, Zahlen und Sonderzeichen.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirm">Passwort bestätigen</label>
                    <input class="form-control"
                           type="password"
                           id="password_confirm"
                           name="password_confirm"
                           autocomplete="new-password"
                           required>
                </div>

                <!-- Datenschutz Checkbox -->
                <div class="form-group" style="display:flex;gap:0.75rem;align-items:flex-start;">
                    <input type="checkbox" id="privacy" name="privacy" required
                           style="margin-top:0.25rem;flex-shrink:0;width:18px;height:18px;">
                    <label for="privacy" style="font-size:0.875rem;line-height:1.5;cursor:pointer;">
                        Ich stimme der
                        <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/datenschutz" target="_blank">Datenschutzerklärung</a>
                        zu und akzeptiere die
                        <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/agb" target="_blank">AGB</a>.
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:var(--spacing-sm);">
                    Konto erstellen
                </button>
            </form>

            <div class="auth-footer">
                <p>Bereits registriert?
                    <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/login">Jetzt anmelden</a>
                </p>
                <p style="margin-top:0.5rem;">
                    <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/">← Zurück zur Startseite</a>
                </p>
            </div>

        </div>
    </div>
</main>
