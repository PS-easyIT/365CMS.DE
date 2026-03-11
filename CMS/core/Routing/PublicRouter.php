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

        $this->router->addRoute('GET', '/login', [$this, 'renderLogin']);
        $this->router->addRoute('POST', '/login', [$this, 'handleLogin']);
        $this->router->addRoute('GET', '/register', [$this, 'renderRegister']);
        $this->router->addRoute('POST', '/register', [$this, 'handleRegister']);
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
        if (Auth::instance()->isLoggedIn()) {
            $this->router->redirect($this->getSafePostLoginRedirect($_GET['redirect'] ?? null));
            return;
        }

        $redirectTarget = $this->getSafePostLoginRedirect($_GET['redirect'] ?? null);
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
                    'options_json' => json_encode($options['options'] ?? new \stdClass(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
            }
        } catch (\Throwable $e) {
            error_log('PublicRouter::renderLogin() Passkey-Setup fehlgeschlagen: ' . $e->getMessage());
        }

        ThemeManager::instance()->render('login', [
            'login_redirect' => $redirectTarget,
            'passkey_payload' => $passkeyPayload,
        ]);
    }

    public function handleLogin(): void
    {
        $security = Security::instance();
        $redirectTarget = $this->getSafePostLoginRedirect($_POST['redirect'] ?? $_GET['redirect'] ?? null);

        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'login')) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->router->redirect('/login');
            return;
        }

        $action = (string)($_POST['action'] ?? 'password_login');

        if ($action === 'passkey_login') {
            $challenge = (string)($_SESSION['login_passkey_challenge'] ?? '');
            unset($_SESSION['login_passkey_challenge']);

            if ($challenge === '') {
                $_SESSION['error'] = 'Die Passkey-Challenge ist abgelaufen. Bitte erneut versuchen.';
                $this->router->redirect('/login');
                return;
            }

            $clientDataJson = $this->base64UrlDecode((string)($_POST['client_data_json'] ?? ''));
            $authenticatorData = $this->base64UrlDecode((string)($_POST['authenticator_data'] ?? ''));
            $signature = $this->base64UrlDecode((string)($_POST['signature'] ?? ''));
            $credentialId = trim((string)($_POST['credential_id'] ?? ''));

            if ($clientDataJson === '' || $authenticatorData === '' || $signature === '' || $credentialId === '') {
                $_SESSION['error'] = 'Die Passkey-Antwort war unvollständig. Bitte erneut versuchen.';
                $this->router->redirect('/login');
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
                $this->router->redirect($redirectTarget);
                return;
            }

            $_SESSION['error'] = (string)$result;
            $this->router->redirect('/login');
            return;
        }

        $loginInput = $_POST['username'] ?? $_POST['email'] ?? '';
        $result = Auth::instance()->login($loginInput, $_POST['password'] ?? '');

        if ($result === true) {
            unset($_SESSION['auth_redirect_after_login']);
            $this->router->redirect($redirectTarget);
        } elseif ($result === 'MFA_REQUIRED') {
            $_SESSION['auth_redirect_after_login'] = $redirectTarget;
            $this->router->redirect('/mfa-challenge');
        } else {
            $_SESSION['error'] = $result;
            $this->router->redirect('/login');
        }
    }

    public function renderMfaChallenge(): void
    {
        if (empty($_SESSION['mfa_pending_user_id'])) {
            $this->router->redirect('/login');
            return;
        }

        $security = Security::instance();
        $csrfToken = $security->generateToken('mfa_challenge');
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);

        echo '<!DOCTYPE html><html lang="de"><head>'
            . '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>Zwei-Faktor-Authentifizierung – ' . htmlspecialchars(SITE_NAME) . '</title>'
            . '<link rel="stylesheet" href="' . SITE_URL . '/assets/css/main.css">'
            . '</head><body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f1f5f9;">'
            . '<div style="background:#fff;padding:2.5rem;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.1);width:100%;max-width:400px;">'
            . '<h2 style="margin:0 0 0.5rem;font-size:1.375rem;color:#1e293b;">🔐 Zwei-Faktor-Authentifizierung</h2>'
            . '<p style="color:#64748b;margin:0 0 1.5rem;">Gib den 6-stelligen Code aus deiner Authenticator-App ein.</p>';

        if ($error) {
            echo '<div style="background:#fee2e2;color:#991b1b;padding:.875rem 1rem;border-radius:8px;margin-bottom:1rem;border-left:4px solid #ef4444;">❌ '
                . htmlspecialchars((string)$error) . '</div>';
        }

        echo '<form method="POST" action="/mfa-challenge" autocomplete="off">'
            . '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">'
            . '<div style="margin-bottom:1.25rem;">'
            . '<label style="display:block;font-weight:600;margin-bottom:.5rem;color:#1e293b;">Authenticator-Code</label>'
            . '<input type="text" name="totp_code" inputmode="numeric" pattern="\d{6}" maxlength="6" required autofocus '
            . 'style="width:100%;padding:.75rem 1rem;border:2px solid #e2e8f0;border-radius:8px;font-size:1.5rem;letter-spacing:.3em;text-align:center;box-sizing:border-box;" '
            . 'placeholder="000000">'
            . '</div>'
            . '<button type="submit" style="width:100%;padding:.875rem;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;">✅ Bestätigen</button>'
            . '</form>'
            . '<p style="text-align:center;margin-top:1.25rem;"><a href="/login" style="color:#64748b;font-size:.875rem;">← Zurück zum Login</a></p>'
            . '</div></body></html>';
    }

    public function handleMfaChallenge(): void
    {
        $security = Security::instance();

        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'mfa_challenge')) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->router->redirect('/mfa-challenge');
            return;
        }

        $pendingUserId = (int)($_SESSION['mfa_pending_user_id'] ?? 0);
        if ($pendingUserId === 0) {
            $this->router->redirect('/login');
            return;
        }

        $code = trim((string)($_POST['totp_code'] ?? ''));
        $mfaResult = \CMS\Auth\AuthManager::instance()->verifyMfa($code);
        if ($mfaResult !== true) {
            $_SESSION['error'] = is_string($mfaResult) && $mfaResult !== ''
                ? $mfaResult
                : 'Ungültiger oder abgelaufener Code. Bitte erneut versuchen.';
            $this->router->redirect('/mfa-challenge');
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

        $this->router->redirect($this->consumePostLoginRedirect());
    }

    public function renderMfaSetup(): void
    {
        if (!Auth::instance()->isLoggedIn()) {
            $this->router->redirect('/login');
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
        $error = $_SESSION['error'] ?? null;
        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['error'], $_SESSION['success']);

        echo '<!DOCTYPE html><html lang="de"><head>'
            . '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>2FA einrichten – ' . htmlspecialchars(SITE_NAME) . '</title>'
            . '<link rel="stylesheet" href="' . SITE_URL . '/assets/css/main.css">'
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
            . '<form method="POST" action="/mfa-setup">'
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
            $this->router->redirect('/login');
            return;
        }

        $security = Security::instance();
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'mfa_setup')) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->router->redirect('/mfa-setup');
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $code = trim((string)($_POST['totp_code'] ?? ''));

        if (!Auth::instance()->confirmMfaSetup($userId, $code)) {
            $_SESSION['error'] = 'Ungültiger Code. Bitte erneut scannen und Code eingeben.';
            $this->router->redirect('/mfa-setup');
            return;
        }

        $_SESSION['success'] = '2FA wurde erfolgreich aktiviert.';
        $this->router->redirect('/member/security');
    }

    public function handleMfaDisable(): void
    {
        if (!Auth::instance()->isLoggedIn()) {
            $this->router->redirect('/login');
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

        ThemeManager::instance()->render('register');
    }

    public function handleRegister(): void
    {
        $security = Security::instance();

        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'register')) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->router->redirect('/register');
            return;
        }

        $result = Auth::instance()->register($_POST);

        if ($result === true) {
            $_SESSION['success'] = 'Registrierung erfolgreich! Sie können sich nun anmelden.';
            $this->router->redirect('/login');
        } else {
            $_SESSION['error'] = $result;
            $this->router->redirect('/register');
        }
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

        if ($postId <= 0 || !Security::instance()->verifyToken((string)($_POST['csrf_token'] ?? ''), 'comment_' . $postId)) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->router->redirect($redirectTarget);
            return;
        }

        $currentUser = Auth::getCurrentUser();
        $result = Services\CommentService::getInstance()->createPendingComment(
            $postId,
            (string)($_POST['author'] ?? ''),
            (string)($_POST['email'] ?? ''),
            (string)($_POST['comment'] ?? ''),
            (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            isset($currentUser->id) ? (int)$currentUser->id : null
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

    private function getSafePostLoginRedirect(mixed $candidate): string
    {
        $candidate = is_string($candidate) ? trim($candidate) : '';
        if ($candidate === '') {
            return '/member';
        }

        $parts = parse_url($candidate);
        if ($parts === false) {
            return '/member';
        }

        $siteHost = (string)(parse_url(SITE_URL, PHP_URL_HOST) ?? '');
        $targetHost = (string)($parts['host'] ?? '');
        if ($targetHost !== '' && $siteHost !== '' && strcasecmp($targetHost, $siteHost) !== 0) {
            return '/member';
        }

        $path = (string)($parts['path'] ?? '/member');
        if ($path === '' || !str_starts_with($path, '/')) {
            $path = '/' . ltrim($path, '/');
        }

        if (in_array($path, ['/login', '/logout', '/mfa-challenge'], true)) {
            return '/member';
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
        return $path . $query;
    }

    private function consumePostLoginRedirect(): string
    {
        $redirect = $this->getSafePostLoginRedirect($_SESSION['auth_redirect_after_login'] ?? null);
        unset($_SESSION['auth_redirect_after_login']);
        return $redirect;
    }

    private function resolveCommentRedirect(int $postId): string
    {
        $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer !== '') {
            $parts = parse_url($referer);
            $siteHost = (string)(parse_url(SITE_URL, PHP_URL_HOST) ?? '');
            $refererHost = (string)($parts['host'] ?? '');
            if ($refererHost === '' || $siteHost === '' || strcasecmp($refererHost, $siteHost) === 0) {
                $path = (string)($parts['path'] ?? '/blog');
                $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
                return $path . $query . '#comments';
            }
        }

        if ($postId > 0) {
            $db = Database::instance();
            $post = $db->get_row("SELECT slug FROM {$db->getPrefix()}posts WHERE id = ? LIMIT 1", [$postId]);
            $slug = trim((string)($post->slug ?? ''));
            if ($slug !== '') {
                return '/blog/' . rawurlencode($slug) . '#comments';
            }
        }

        return '/blog';
    }
}
