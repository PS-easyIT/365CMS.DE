<?php
/**
 * Public Router Module
 *
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS\Routing;

use CMS\AuditLogger;
use CMS\Auth;
use CMS\Database;
use CMS\Logger;
use CMS\Router;
use CMS\Security;
use CMS\Services;
use CMS\ThemeManager;

if (!defined('ABSPATH')) {
    exit;
}

final class PublicRouter
{
    public function __construct(private readonly Router $router)
    {
    }

    public function registerRoutes(): void
    {
        $this->router->addRoute('GET', '/media-proxy.php', [$this, 'redirectLegacyMediaProxy']);
        $this->router->addRoute('POST', '/media-proxy.php', [$this, 'handleLegacyMediaProxyUpload']);
        $this->router->addRoute('GET', '/media-file', [$this, 'handleMediaDelivery']);

        $this->router->addRoute('GET', '/cms-login', [$this, 'renderLogin']);
        $this->router->addRoute('POST', '/cms-login', [$this, 'handleLogin']);
        $this->router->addRoute('GET', '/cms-register', [$this, 'renderRegister']);
        $this->router->addRoute('POST', '/cms-register', [$this, 'handleRegister']);
        $this->router->addRoute('GET', '/cms-password-forgot', [$this, 'renderForgotPassword']);
        $this->router->addRoute('POST', '/cms-password-forgot', [$this, 'handleForgotPassword']);

        $this->router->addRoute('GET', '/login', [$this, 'redirectLegacyLogin']);
        $this->router->addRoute('POST', '/login', [$this, 'handleLogin']);
        $this->router->addRoute('GET', '/register', [$this, 'redirectLegacyRegister']);
        $this->router->addRoute('POST', '/register', [$this, 'handleRegister']);
        $this->router->addRoute('GET', '/forgot-password', [$this, 'redirectLegacyForgotPassword']);
        $this->router->addRoute('POST', '/forgot-password', [$this, 'handleForgotPassword']);
        $this->router->addRoute('GET', '/logout', [$this, 'handleLogout']);

        $this->router->addRoute('GET', '/mfa-challenge', [$this, 'renderMfaChallenge']);
        $this->router->addRoute('POST', '/mfa-challenge', [$this, 'handleMfaChallenge']);
        $this->router->addRoute('GET', '/mfa-setup', [$this, 'renderMfaSetup']);
        $this->router->addRoute('POST', '/mfa-setup', [$this, 'handleMfaSetup']);
        $this->router->addRoute('POST', '/mfa-disable', [$this, 'handleMfaDisable']);

        $this->router->addRoute('GET', '/order', [$this, 'renderOrder']);
        $this->router->addRoute('POST', '/order', [$this, 'handleOrder']);
        $this->router->addRoute('POST', '/comments/post', [$this, 'handleCommentPost']);
        $this->router->addRoute('GET', '/cookie-einstellungen', [$this, 'renderCookiePreferencesPage']);
    }

    public function redirectLegacyMediaProxy(): void
    {
        Logger::instance()->warning('Legacy media-proxy route used; redirecting to member media.', [
            'method' => 'GET',
            'path' => '/media-proxy.php',
            'remote_addr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);

        $this->router->redirect('/member/media', 302);
    }

    public function handleLegacyMediaProxyUpload(): void
    {
        Logger::instance()->warning('Legacy media-proxy upload route used; delegating to FileUploadService.', [
            'method' => 'POST',
            'path' => '/media-proxy.php',
            'remote_addr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);

        $result = Services\FileUploadService::getInstance()->handleUploadRequest();
        http_response_code((int)($result['status'] ?? 200));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result['data'] ?? ['error' => 'Unbekannter Fehler'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function handleMediaDelivery(): void
    {
        Services\MediaDeliveryService::getInstance()->handleRequest();
    }

    public function renderLogin(): void
    {
        $safeRedirectParts = $this->resolveAllowedRedirectParts($_GET['redirect'] ?? null);
        $safeRedirectTarget = $this->buildAllowedRedirectTarget($safeRedirectParts);

        if (Auth::instance()->isLoggedIn()) {
            $this->redirectToSafeTarget($safeRedirectParts);
            return;
        }

        $passkeyPayload = [
            'available' => false,
            'options_json' => '{}',
        ];

        try {
            $authManager = \CMS\Auth\AuthManager::instance();
            if ($authManager->isPasskeyAvailable()) {
                $options = $authManager->getPasskeyLoginOptions();
                $_SESSION['login_passkey_challenge'] = (string)($options['challenge'] ?? '');
                $passkeyPayload = [
                    'available' => true,
                    'options_json' => json_encode(
                        $options['options'] ?? new \stdClass(),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                    ) ?: '{}',
                ];
            }
        } catch (\Throwable $e) {
            error_log('PublicRouter::renderLogin() Passkey-Setup fehlgeschlagen: ' . $e->getMessage());
        }

        Services\CmsAuthPageService::getInstance()->render('login', [
            'login_redirect' => $safeRedirectTarget,
            'passkey_payload' => $passkeyPayload,
        ]);
    }

    public function handleLogin(): void
    {
        $security = Security::instance();
        $redirectTarget = $this->normalizeAllowedRedirectTarget($_POST['redirect'] ?? $_GET['redirect'] ?? null);
        $defaultPendingRedirect = $this->resolvePendingLoginRedirect($redirectTarget, (string)($_POST['username'] ?? $_POST['email'] ?? ''));
        $loginPagePath = $this->buildLoginPagePath($redirectTarget);

        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'login')) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->router->redirect($loginPagePath);
            return;
        }

        $action = (string)($_POST['action'] ?? 'password_login');

        if ($action === 'passkey_login') {
            $challenge = (string)($_SESSION['login_passkey_challenge'] ?? '');
            unset($_SESSION['login_passkey_challenge']);

            if ($challenge === '') {
                $_SESSION['error'] = 'Die Passkey-Challenge ist abgelaufen. Bitte erneut versuchen.';
                $this->router->redirect($loginPagePath);
                return;
            }

            $clientDataJson = $this->base64UrlDecode((string)($_POST['client_data_json'] ?? ''));
            $authenticatorData = $this->base64UrlDecode((string)($_POST['authenticator_data'] ?? ''));
            $signature = $this->base64UrlDecode((string)($_POST['signature'] ?? ''));
            $credentialId = trim((string)($_POST['credential_id'] ?? ''));

            if ($clientDataJson === '' || $authenticatorData === '' || $signature === '' || $credentialId === '') {
                $_SESSION['error'] = 'Die Passkey-Antwort war unvollständig. Bitte erneut versuchen.';
                $this->router->redirect($loginPagePath);
                return;
            }

            $result = \CMS\Auth\AuthManager::instance()->authenticateViaPasskey(
                $clientDataJson,
                $authenticatorData,
                $signature,
                $credentialId,
                $challenge
            );

            if ($result === true) {
                unset($_SESSION['auth_redirect_after_login']);
                $this->redirectToSafeTarget($this->resolveAllowedRedirectParts($this->resolveSuccessfulLoginRedirect($redirectTarget)));
                return;
            }

            $_SESSION['error'] = (string)$result;
            $this->setAuthFormOldValues('login', [
                'username' => (string)($_POST['username'] ?? ''),
                'email' => (string)($_POST['email'] ?? ''),
            ]);
            $this->router->redirect($loginPagePath);
            return;
        }

        $loginInput = $_POST['username'] ?? $_POST['email'] ?? '';
        $result = Auth::instance()->login($loginInput, $_POST['password'] ?? '', !empty($_POST['remember']));

        if ($result === true) {
            unset($_SESSION['auth_redirect_after_login']);
            $this->redirectToSafeTarget($this->resolveAllowedRedirectParts($this->resolveSuccessfulLoginRedirect($redirectTarget)));
        } elseif ($result === 'MFA_REQUIRED') {
            $_SESSION['auth_redirect_after_login'] = $defaultPendingRedirect;
            $this->router->redirect($this->getLocalizedPublicPath('/mfa-challenge'));
        } else {
            $_SESSION['error'] = $result;
            $this->setAuthFormOldValues('login', [
                'username' => (string)$loginInput,
                'remember' => !empty($_POST['remember']) ? '1' : '0',
            ]);
            $this->router->redirect($loginPagePath);
        }
    }

    public function renderMfaChallenge(): void
    {
        if (empty($_SESSION['mfa_pending_user_id'])) {
            $this->router->redirect($this->getCmsAuthPath('login'));
            return;
        }

        $security = Security::instance();
        $csrfToken = $security->generateToken('mfa_challenge');
        $mfaChallengePath = $this->getLocalizedPublicPath('/mfa-challenge');
        $loginPath = $this->getPublicAuthPath('login');
        $documentLang = htmlspecialchars($this->getCurrentDocumentLang(), ENT_QUOTES, 'UTF-8');
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);

        echo '<!DOCTYPE html><html lang="' . $documentLang . '"><head>'
            . '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>Zwei-Faktor-Authentifizierung – ' . htmlspecialchars(SITE_NAME) . '</title>'
            . '<link rel="stylesheet" href="/assets/css/main.css">'
            . '</head><body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f1f5f9;">'
            . '<div style="background:#fff;padding:2.5rem;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.1);width:100%;max-width:400px;">'
            . '<h2 style="margin:0 0 0.5rem;font-size:1.375rem;color:#1e293b;">🔐 Zwei-Faktor-Authentifizierung</h2>'
            . '<p style="color:#64748b;margin:0 0 1.5rem;">Gib den 6-stelligen Code aus deiner Authenticator-App ein.</p>';

        if ($error) {
            echo '<div style="background:#fee2e2;color:#991b1b;padding:.875rem 1rem;border-radius:8px;margin-bottom:1rem;border-left:4px solid #ef4444;">❌ '
                . htmlspecialchars((string)$error) . '</div>';
        }

        echo '<form method="POST" action="' . htmlspecialchars($mfaChallengePath, ENT_QUOTES, 'UTF-8') . '" autocomplete="off">'
            . '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">'
            . '<div style="margin-bottom:1.25rem;">'
            . '<label style="display:block;font-weight:600;margin-bottom:.5rem;color:#1e293b;">Authenticator-Code</label>'
            . '<input type="text" name="totp_code" inputmode="numeric" pattern="\d{6}" maxlength="6" required autofocus '
            . 'style="width:100%;padding:.75rem 1rem;border:2px solid #e2e8f0;border-radius:8px;font-size:1.5rem;letter-spacing:.3em;text-align:center;box-sizing:border-box;" '
            . 'placeholder="000000">'
            . '</div>'
            . '<button type="submit" style="width:100%;padding:.875rem;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;">✅ Bestätigen</button>'
            . '</form>'
                . '<p style="text-align:center;margin-top:1.25rem;"><a href="' . htmlspecialchars($loginPath, ENT_QUOTES, 'UTF-8') . '" style="color:#64748b;font-size:.875rem;">← Zurück zum Login</a></p>'
            . '</div></body></html>';
    }

    public function handleMfaChallenge(): void
    {
        $security = Security::instance();

        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'mfa_challenge')) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->router->redirect($this->getLocalizedPublicPath('/mfa-challenge'));
            return;
        }

        $pendingUserId = (int)($_SESSION['mfa_pending_user_id'] ?? 0);
        if ($pendingUserId === 0) {
            $this->router->redirect($this->getCmsAuthPath('login'));
            return;
        }

        $code = trim((string)($_POST['totp_code'] ?? ''));
        $mfaResult = \CMS\Auth\AuthManager::instance()->verifyMfa($code);
        if ($mfaResult !== true) {
            $_SESSION['error'] = is_string($mfaResult) && $mfaResult !== ''
                ? $mfaResult
                : 'Ungültiger oder abgelaufener Code. Bitte erneut versuchen.';
            $this->router->redirect($this->getLocalizedPublicPath('/mfa-challenge'));
            return;
        }

        AuditLogger::instance()->log(
            AuditLogger::CAT_SECURITY,
            'login_mfa_success',
            'Login mit MFA erfolgreich.',
            'user',
            $pendingUserId,
            ['ip' => $security->getClientIp()],
            'info'
        );

        $this->redirectToSafeTarget($this->resolveAllowedRedirectParts($this->consumePostLoginRedirect()));
    }

    public function renderMfaSetup(): void
    {
        if (!Auth::instance()->isLoggedIn()) {
            $this->router->redirect($this->getCmsAuthPath('login'));
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        if (Auth::instance()->isMfaEnabled($userId)) {
            $_SESSION['success'] = '2FA ist bereits aktiv.';
            $this->router->redirect('/member/security');
            return;
        }

        $auth = Auth::instance();
        $security = Security::instance();
        $setup = $auth->setupMfaSecret($userId);
        $csrfToken = $security->generateToken('mfa_setup');
        $mfaSetupPath = $this->getLocalizedPublicPath('/mfa-setup');
        $documentLang = htmlspecialchars($this->getCurrentDocumentLang(), ENT_QUOTES, 'UTF-8');
        $error = $_SESSION['error'] ?? null;
        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['error'], $_SESSION['success']);

        echo '<!DOCTYPE html><html lang="' . $documentLang . '"><head>'
            . '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>2FA einrichten – ' . htmlspecialchars(SITE_NAME) . '</title>'
            . '<link rel="stylesheet" href="/assets/css/main.css">'
            . '</head><body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f1f5f9;">'
            . '<div style="background:#fff;padding:2.5rem;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.1);width:100%;max-width:480px;">'
            . '<h2 style="margin:0 0 0.5rem;font-size:1.375rem;color:#1e293b;">🔐 Zwei-Faktor-Authentifizierung einrichten</h2>'
            . '<p style="color:#64748b;margin:0 0 1.5rem;">Scanne den QR-Code mit deiner Authenticator-App (z. B. Google Authenticator, Authy) und gib anschließend den 6-stelligen Code ein.</p>';

        if ($error) {
            echo '<div style="background:#fee2e2;color:#991b1b;padding:.875rem;border-radius:8px;margin-bottom:1rem;border-left:4px solid #ef4444;">❌ '
                . htmlspecialchars((string)$error) . '</div>';
        }

        if ($success) {
            echo '<div style="background:#dcfce7;color:#166534;padding:.875rem;border-radius:8px;margin-bottom:1rem;border-left:4px solid #22c55e;">✅ '
                . htmlspecialchars((string)$success) . '</div>';
        }

        echo '<div style="text-align:center;margin-bottom:1.5rem;">'
            . '<img src="' . htmlspecialchars((string)$setup['qr_url']) . '" alt="QR-Code für Authenticator" width="200" height="200" style="border:4px solid #e2e8f0;border-radius:8px;">'
            . '</div>'
            . '<div style="background:#f8fafc;padding:1rem;border-radius:8px;margin-bottom:1.5rem;font-family:monospace;text-align:center;font-size:1.1rem;letter-spacing:.1em;color:#1e293b;border:1px solid #e2e8f0;">'
            . htmlspecialchars((string)$setup['secret'])
            . '</div>'
            . '<p style="color:#64748b;font-size:.875rem;margin:0 0 1.25rem;">Falls du den QR-Code nicht scannen kannst, gib den obigen Schlüssel manuell in deiner App ein.</p>'
            . '<form method="POST" action="' . htmlspecialchars($mfaSetupPath, ENT_QUOTES, 'UTF-8') . '">'
            . '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">'
            . '<div style="margin-bottom:1.25rem;">'
            . '<label style="display:block;font-weight:600;margin-bottom:.5rem;color:#1e293b;">Bestätigungscode aus der App</label>'
            . '<input type="text" name="totp_code" inputmode="numeric" pattern="\d{6}" maxlength="6" required autofocus '
            . 'style="width:100%;padding:.75rem 1rem;border:2px solid #e2e8f0;border-radius:8px;font-size:1.5rem;letter-spacing:.3em;text-align:center;box-sizing:border-box;" '
            . 'placeholder="000000">'
            . '</div>'
            . '<button type="submit" style="width:100%;padding:.875rem;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;">✅ 2FA aktivieren</button>'
            . '</form>'
            . '<p style="text-align:center;margin-top:1.25rem;"><a href="/member/security" style="color:#64748b;font-size:.875rem;">← Zurück zur Sicherheitsseite</a></p>'
            . '</div></body></html>';
    }

    public function handleMfaSetup(): void
    {
        if (!Auth::instance()->isLoggedIn()) {
            $this->router->redirect($this->getCmsAuthPath('login'));
            return;
        }

        $security = Security::instance();
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'mfa_setup')) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->router->redirect($this->getLocalizedPublicPath('/mfa-setup'));
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $code = trim((string)($_POST['totp_code'] ?? ''));

        if (!Auth::instance()->confirmMfaSetup($userId, $code)) {
            $_SESSION['error'] = 'Ungültiger Code. Bitte erneut scannen und Code eingeben.';
            $this->router->redirect($this->getLocalizedPublicPath('/mfa-setup'));
            return;
        }

        $_SESSION['success'] = '2FA wurde erfolgreich aktiviert.';
        $this->router->redirect('/member/security');
    }

    public function handleMfaDisable(): void
    {
        if (!Auth::instance()->isLoggedIn()) {
            $this->router->redirect($this->getCmsAuthPath('login'));
            return;
        }

        $security = Security::instance();
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'mfa_disable')) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->router->redirect('/member/security');
            return;
        }

        Auth::instance()->disableMfa((int)$_SESSION['user_id']);
        $_SESSION['success'] = '2FA wurde deaktiviert.';
        $this->router->redirect('/member/security');
    }

    public function renderRegister(): void
    {
        if (Auth::instance()->isLoggedIn()) {
            $this->router->redirect('/member');
            return;
        }

        Services\CmsAuthPageService::getInstance()->render('register');
    }

    public function renderForgotPassword(): void
    {
        if (Auth::instance()->isLoggedIn()) {
            $this->router->redirect('/member');
            return;
        }

        Services\CmsAuthPageService::getInstance()->render('forgot-password');
    }

    public function handleForgotPassword(): void
    {
        if (Auth::instance()->isLoggedIn()) {
            $this->router->redirect('/member');
            return;
        }

        $security = Security::instance();
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'forgot_password')) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->router->redirect($this->getPublicAuthPath('forgot-password'));
            return;
        }

        $action = trim((string)($_POST['forgot_password_action'] ?? 'request_reset'));
        $authPageService = Services\CmsAuthPageService::getInstance();

        if ($action === 'reset_password') {
            $token = (string)($_POST['reset_token'] ?? '');
            $result = $authPageService->resetPassword(
                $token,
                (string)($_POST['new_password'] ?? ''),
                (string)($_POST['new_password2'] ?? '')
            );

            if ($result['success'] ?? false) {
                $_SESSION['success'] = (string)($result['message'] ?? 'Dein Passwort wurde erfolgreich geändert.');
                $this->router->redirect($this->getPublicAuthPath('forgot-password') . '?step=done');
                return;
            }

            $_SESSION['error'] = (string)($result['message'] ?? 'Das Passwort konnte nicht geändert werden.');
            $this->router->redirect($this->getPublicAuthPath('forgot-password') . '?step=reset&token=' . urlencode($token));
            return;
        }

        $email = trim((string)($_POST['email'] ?? ''));
        $result = $authPageService->requestPasswordReset($email, $this->router->getRequestLocale());
        if ($result['success'] ?? false) {
            $_SESSION['success'] = (string)($result['message'] ?? 'Falls das Konto existiert, wurde ein Reset-Link versendet.');
        } else {
            $_SESSION['error'] = (string)($result['message'] ?? 'Die Anfrage konnte nicht verarbeitet werden.');
            $this->setAuthFormOldValues('forgot-password', ['email' => $email]);
        }

        $this->router->redirect($this->getPublicAuthPath('forgot-password'));
    }

    public function handleRegister(): void
    {
        $security = Security::instance();
        $authPageService = Services\CmsAuthPageService::getInstance();

        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'register')) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->router->redirect($this->getPublicAuthPath('register'));
            return;
        }

        if (!$authPageService->isRegistrationEnabled()) {
            $_SESSION['error'] = (string) ($authPageService->getSettings()['register_disabled_message'] ?? 'Die Registrierung ist aktuell deaktiviert.');
            $this->router->redirect($this->getPublicAuthPath('register'));
            return;
        }

        $settings = $authPageService->getSettings();

        if (array_key_exists('password2', $_POST) && (string)($_POST['password'] ?? '') !== (string)($_POST['password2'] ?? '')) {
            $_SESSION['error'] = 'Die Passwörter stimmen nicht überein.';
            $this->setAuthFormOldValues('register', [
                'email' => (string)($_POST['email'] ?? ''),
                'username' => (string)($_POST['username'] ?? ''),
                'terms' => !empty($_POST['terms']) ? '1' : '0',
            ]);
            $this->router->redirect($this->getPublicAuthPath('register'));
            return;
        }

        if (($settings['register_require_terms'] ?? '1') === '1' && empty($_POST['terms'])) {
            $_SESSION['error'] = 'Bitte akzeptiere die Nutzungsbedingungen und die Datenschutzerklärung.';
            $this->setAuthFormOldValues('register', [
                'email' => (string)($_POST['email'] ?? ''),
                'username' => (string)($_POST['username'] ?? ''),
                'terms' => !empty($_POST['terms']) ? '1' : '0',
            ]);
            $this->router->redirect($this->getPublicAuthPath('register'));
            return;
        }

        $result = Auth::instance()->register($_POST);

        if ($result === true) {
            $_SESSION['success'] = 'Registrierung erfolgreich! Sie können sich nun anmelden.';
            $this->router->redirect($this->getPublicAuthPath('login'));
        } else {
            $_SESSION['error'] = $result;
            $this->setAuthFormOldValues('register', [
                'email' => (string)($_POST['email'] ?? ''),
                'username' => (string)($_POST['username'] ?? ''),
                'terms' => !empty($_POST['terms']) ? '1' : '0',
            ]);
            $this->router->redirect($this->getPublicAuthPath('register'));
        }
    }

    public function redirectLegacyLogin(): void
    {
        if ($this->shouldRenderLegacyThemeAuthPage()) {
            $safeRedirectParts = $this->resolveAllowedRedirectParts($_GET['redirect'] ?? null);
            $safeRedirectTarget = $this->buildAllowedRedirectTarget($safeRedirectParts);

            ThemeManager::instance()->render('login', [
                'login_redirect' => $safeRedirectTarget,
            ]);
            return;
        }

        $this->redirectLegacyAuthPath('login');
    }

    public function redirectLegacyRegister(): void
    {
        if ($this->shouldRenderLegacyThemeAuthPage()) {
            ThemeManager::instance()->render('register');
            return;
        }

        $this->redirectLegacyAuthPath('register');
    }

    public function redirectLegacyForgotPassword(): void
    {
        if ($this->shouldRenderLegacyThemeAuthPage()) {
            ThemeManager::instance()->render('forgot-password');
            return;
        }

        $this->redirectLegacyAuthPath('forgot-password');
    }

    public function handleLogout(): void
    {
        Auth::instance()->logout();
        $this->router->redirect('/');
    }

    public function renderOrder(): void
    {
        $GLOBALS['cms_form_guard_csrf'] = Security::instance()->generateToken('form_guard');

        $file = ABSPATH . 'member/order_public.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }

        $this->router->render404();
    }

    public function handleOrder(): void
    {
        $file = ABSPATH . 'member/order_public.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }

        $this->router->render404();
    }

    public function handleCommentPost(): void
    {
        $postId = (int)($_POST['post_id'] ?? 0);
        $redirectTarget = $this->resolveCommentRedirect($postId);

        if (trim((string)($_POST['comment_hp'] ?? '')) !== '') {
            $_SESSION['success'] = 'Kommentar gespeichert und zur Moderation eingereicht.';
            $this->router->redirect($redirectTarget);
            return;
        }

        if ($postId <= 0 || !Security::instance()->verifyToken((string)($_POST['csrf_token'] ?? ''), 'comment_' . $postId)) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->router->redirect($redirectTarget);
            return;
        }

        $currentUser = Auth::getCurrentUser();
        $isAnonymousComment = isset($currentUser->id) && !empty($_POST['comment_anonymous']);
        $result = Services\CommentService::getInstance()->createPendingComment(
            $postId,
            (string)($_POST['author'] ?? ''),
            (string)($_POST['email'] ?? ''),
            (string)($_POST['comment'] ?? ''),
            (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            isset($currentUser->id) ? (int)$currentUser->id : null,
            $isAnonymousComment
        );

        if ($result === false) {
            $_SESSION['error'] = 'Kommentar konnte nicht gespeichert werden. Bitte prüfe deine Eingaben.';
        } else {
            $_SESSION['success'] = 'Kommentar gespeichert und zur Moderation eingereicht.';
        }

        $this->router->redirect($redirectTarget);
    }

    public function renderCookiePreferencesPage(): void
    {
        if (!class_exists(Services\CookieConsentService::class)) {
            $this->router->render404();
            return;
        }

        ThemeManager::instance()->render('page', [
            'page' => Services\CookieConsentService::getInstance()->getPublicConsentPage(),
        ]);
    }

    private function base64UrlDecode(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return (string)(base64_decode($value, true) ?: '');
    }

    private function getCmsAuthPath(string $page): string
    {
        return Services\CmsAuthPageService::getInstance()->getPath($page);
    }

    private function getLocalizedPublicPath(string $path): string
    {
        return Services\ContentLocalizationService::getInstance()->buildLocalizedPath(
            $path,
            $this->router->getRequestLocale()
        );
    }

    private function getCurrentDocumentLang(): string
    {
        $locale = strtolower(str_replace('_', '-', trim($this->router->getRequestLocale())));
        if ($locale === '') {
            return 'de';
        }

        return preg_match('/^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/i', $locale) === 1
            ? $locale
            : 'de';
    }

    private function buildLoginPagePath(?string $redirectTarget = null): string
    {
        $path = $this->getPublicAuthPath('login');
        $safeRedirect = $this->normalizeAllowedRedirectTarget($redirectTarget);

        if ($safeRedirect === '/member') {
            return $path;
        }

        return $path . '?redirect=' . urlencode($safeRedirect);
    }

    private function getPublicAuthPath(string $page): string
    {
        return Services\CmsAuthPageService::getInstance()->getPublicPath($page, $this->router->getRequestLocale());
    }

    private function redirectLegacyAuthPath(string $page): void
    {
        $target = $this->getCmsAuthPath($page);
        $queryString = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY) ?? '');
        if ($queryString !== '') {
            parse_str($queryString, $queryParams);
            if (is_array($queryParams) && $queryParams !== []) {
                $target .= '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
            }
        }

        $this->router->redirect($target, 302);
    }

    private function shouldRenderLegacyThemeAuthPage(): bool
    {
        $settings = Services\CmsAuthPageService::getInstance()->getSettings();

        return (($settings['auth_slug_mode'] ?? 'cms') === 'legacy');
    }

    private function setAuthFormOldValues(string $page, array $values): void
    {
        if (!isset($_SESSION['auth_form_old']) || !is_array($_SESSION['auth_form_old'])) {
            $_SESSION['auth_form_old'] = [];
        }

        $_SESSION['auth_form_old'][$page] = $values;
    }

    private function getSafePostLoginRedirect(mixed $candidate): string
    {
        return $this->normalizeAllowedRedirectTarget($candidate);
    }

    private function resolveSuccessfulLoginRedirect(string $redirectTarget): string
    {
        if ($redirectTarget !== '/member') {
            return $this->normalizeAllowedRedirectTarget($redirectTarget);
        }

        return Auth::instance()->isAdmin() ? '/admin' : '/member';
    }

    private function resolvePendingLoginRedirect(string $redirectTarget, string $loginInput): string
    {
        if ($redirectTarget !== '/member') {
            return $this->normalizeAllowedRedirectTarget($redirectTarget);
        }

        $user = $this->findUserForLoginInput($loginInput);
        if ($user !== null && (string)($user->role ?? '') === 'admin') {
            return '/admin';
        }

        return '/member';
    }

    private function findUserForLoginInput(string $loginInput): ?object
    {
        $loginInput = trim($loginInput);
        if ($loginInput === '') {
            return null;
        }

        try {
            $db = Database::instance();
            $stmt = $db->prepare("SELECT id, role FROM {$db->getPrefix()}users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$loginInput, $loginInput]);
            $user = $stmt->fetch();

            return is_object($user) ? $user : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function consumePostLoginRedirect(): string
    {
        $redirect = $this->normalizeAllowedRedirectTarget($_SESSION['auth_redirect_after_login'] ?? null);
        unset($_SESSION['auth_redirect_after_login']);
        return $redirect;
    }

    private function resolveCommentRedirect(int $postId): string
    {
        if ($postId > 0) {
            $db = Database::instance();
            $post = $db->get_row("SELECT slug, published_at, created_at FROM {$db->getPrefix()}posts WHERE id = ? LIMIT 1", [$postId]);
            $slug = trim((string)($post->slug ?? ''));
            if ($slug !== '') {
                $path = class_exists('CMS\\Services\\PermalinkService')
                    ? \CMS\Services\PermalinkService::getInstance()->buildPostPathFromValues(
                        $slug,
                        (string)($post->published_at ?? ''),
                        (string)($post->created_at ?? '')
                    )
                    : '/blog/' . rawurlencode($slug);
                return $path . '#comments';
            }
        }

        return '/blog';
    }

    private function redirectToSafeTarget(array $targetParts): void
    {
        $route = (string) ($targetParts['route'] ?? 'member');
        $suffix = (string) ($targetParts['suffix'] ?? '');
        $query = (string) ($targetParts['query'] ?? '');

        $basePath = match ($route) {
            'admin' => '/admin',
            'dashboard' => '/dashboard',
            default => '/member',
        };

        $safeSuffix = $this->sanitizeAllowedPathSuffix($suffix);
        if ($safeSuffix === null) {
            $safeSuffix = '';
        }

        $target = $basePath . $safeSuffix;
        $safeQuery = $this->sanitizeAllowedQueryString($query);
        $this->router->redirect($safeQuery !== '' ? $target . '?' . $safeQuery : $target);
    }

    private function resolveAllowedRedirectParts(mixed $candidate): array
    {
        $rawValue = trim((string) $candidate);
        if ($rawValue === '') {
            return ['route' => 'member', 'suffix' => '', 'query' => ''];
        }

        $relativeValue = $this->toSameOriginRelativePath($rawValue);
        if ($relativeValue === '') {
            return ['route' => 'member', 'suffix' => '', 'query' => ''];
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $relativeValue) === 1 || str_starts_with($relativeValue, '//')) {
            return ['route' => 'member', 'suffix' => '', 'query' => ''];
        }

        $path = (string) parse_url($relativeValue, PHP_URL_PATH);
        $query = (string) parse_url($relativeValue, PHP_URL_QUERY);
        if ($path === '') {
            return ['route' => 'member', 'suffix' => '', 'query' => ''];
        }

        foreach (['admin' => '/admin', 'member' => '/member', 'dashboard' => '/dashboard'] as $route => $allowedPrefix) {
            if ($path === $allowedPrefix) {
                return [
                    'route' => $route,
                    'suffix' => '',
                    'query' => $this->sanitizeAllowedQueryString($query),
                ];
            }

            if (!str_starts_with($path, $allowedPrefix . '/')) {
                continue;
            }

            $suffix = substr($path, strlen($allowedPrefix));
            $safeSuffix = $this->sanitizeAllowedPathSuffix($suffix);

            return [
                'route' => $route,
                'suffix' => $safeSuffix ?? '',
                'query' => $this->sanitizeAllowedQueryString($query),
            ];
        }

        return ['route' => 'member', 'suffix' => '', 'query' => ''];
    }

    private function buildAllowedRedirectTarget(array $targetParts): string
    {
        $route = (string) ($targetParts['route'] ?? 'member');
        $suffix = (string) ($targetParts['suffix'] ?? '');
        $query = (string) ($targetParts['query'] ?? '');

        $basePath = match ($route) {
            'admin' => '/admin',
            'dashboard' => '/dashboard',
            default => '/member',
        };

        $safeSuffix = $this->sanitizeAllowedPathSuffix($suffix);
        if ($safeSuffix === null) {
            $safeSuffix = '';
        }

        $target = $basePath . $safeSuffix;
        $safeQuery = $this->sanitizeAllowedQueryString($query);

        return $safeQuery !== '' ? $target . '?' . $safeQuery : $target;
    }

    private function normalizeAllowedRedirectTarget(mixed $candidate): string
    {
        return $this->buildAllowedRedirectTarget($this->resolveAllowedRedirectParts($candidate));
    }

    private function toSameOriginRelativePath(string $candidate): string
    {
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $candidate) !== 1) {
            return str_starts_with($candidate, '/') ? $candidate : '/' . ltrim($candidate, '/');
        }

        $siteParts = parse_url((string) SITE_URL);
        $targetParts = parse_url($candidate);
        if (!is_array($siteParts) || !is_array($targetParts)) {
            return '';
        }

        $siteScheme = strtolower((string) ($siteParts['scheme'] ?? ''));
        $siteHost = strtolower((string) ($siteParts['host'] ?? ''));
        $targetScheme = strtolower((string) ($targetParts['scheme'] ?? ''));
        $targetHost = strtolower((string) ($targetParts['host'] ?? ''));

        if ($siteScheme !== $targetScheme || $siteHost !== $targetHost) {
            return '';
        }

        $path = (string) ($targetParts['path'] ?? '/');
        $query = isset($targetParts['query']) && $targetParts['query'] !== '' ? '?' . (string) $targetParts['query'] : '';

        return $path . $query;
    }

    private function sanitizeAllowedPathSuffix(string $suffix): ?string
    {
        if ($suffix === '') {
            return '';
        }

        if (preg_match('#^/(?:[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)*)$#', $suffix) !== 1) {
            return null;
        }

        return $suffix;
    }

    private function sanitizeAllowedQueryString(string $query): string
    {
        if ($query === '') {
            return '';
        }

        parse_str($query, $params);
        if (!is_array($params) || $params === []) {
            return '';
        }

        $allowed = [];
        foreach ($params as $key => $value) {
            $key = (string) $key;
            if (preg_match('/^[a-zA-Z0-9_-]{1,40}$/', $key) !== 1) {
                continue;
            }

            if (is_array($value)) {
                continue;
            }

            $stringValue = trim((string) $value);
            if ($stringValue === '' || preg_match('/^[a-zA-Z0-9_\-.,:@]{1,120}$/', $stringValue) !== 1) {
                continue;
            }

            $allowed[$key] = $stringValue;
        }

        return $allowed === [] ? '' : http_build_query($allowed, '', '&', PHP_QUERY_RFC3986);
    }
}
