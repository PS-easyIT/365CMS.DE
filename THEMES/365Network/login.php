<?php
/**
 * Login Template
 *
 * Kein header/footer-wrap nötig - dieser wird durch ThemeManager::render() automatisch eingebunden.
 *
 * @package IT_Expert_Network_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$siteUrl   = SITE_URL;
$themeManager = \CMS\ThemeManager::instance();
$siteTitle = $themeManager->getSiteTitle();
$error     = theme_get_flash('error');
$success   = theme_get_flash('success');
?>

<main id="main" role="main" style="background:linear-gradient(135deg,#e3f2fd 0%,#f5f9fc 100%);min-height:calc(100vh - 200px);display:flex;align-items:center;padding:var(--spacing-lg);">
    <div style="width:100%;max-width:440px;margin:0 auto;">

        <!-- Auth Card -->
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
                <p>Melde dich mit deinem Konto an</p>
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

            <!-- Login Form -->
            <form method="POST" action="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/login" novalidate>
                <?php theme_csrf_field('login'); ?>

                <div class="form-group">
                    <label class="form-label" for="username">Benutzername oder E-Mail</label>
                    <input class="form-control"
                           type="text"
                           id="username"
                           name="username"
                           autocomplete="username"
                           required
                           autofocus
                           value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Passwort</label>
                    <input class="form-control"
                           type="password"
                           id="password"
                           name="password"
                           autocomplete="current-password"
                           required>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:var(--spacing-md);">
                    Anmelden
                </button>
            </form>

            <!-- Footer Links -->
            <div class="auth-footer">
                <p>Noch kein Konto?
                    <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/register">Jetzt registrieren</a>
                </p>
                <p style="margin-top:0.5rem;">
                    <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/">← Zurück zur Startseite</a>
                </p>
            </div>

        </div>
    </div>
</main>
