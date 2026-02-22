<?php
/**
 * Member Controller Base Class
 * 
 * Basis-Controller für alle Member-Seiten
 * 
 * @package CMSv2\Member
 */

declare(strict_types=1);

namespace CMS\Member;

use CMS\Auth;
use CMS\Security;
use CMS\Services\MemberService;
use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base Controller für Member-Bereich
 */
class MemberController
{
    protected Auth $auth;
    protected Security $security;
    protected MemberService $memberService;
    protected Database $db;
    protected object $user;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // Security check - muss eingeloggt sein
        if (!Auth::instance()->isLoggedIn()) {
            $this->redirect('/login');
        }
        // Admins dürfen Member-Bereich besuchen (sehen dort zusätzlich den Admin-Link)

        $this->auth = Auth::instance();
        $this->security = Security::instance();
        $this->memberService = MemberService::getInstance();
        $this->db = Database::instance();
        $this->user = $this->auth->getCurrentUser();

        // Plugin-Dashboard-Registry initialisieren (feuert member_dashboard_init)
        PluginDashboardRegistry::instance()->init();
    }
    
    /**
     * Redirect helper
     * 
     * @param string $url URL to redirect to
     * @return void
     */
    public function redirect(string $url): void
    {
        header('Location: ' . SITE_URL . $url);
        exit;
    }
    
    /**
     * Generate CSRF token
     * 
     * @param string $action Action name
     * @return string Token
     */
    public function generateToken(string $action): string
    {
        return $this->security->generateToken($action);
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @param string $action Action name
     * @return bool
     */
    public function verifyToken(string $token, string $action): bool
    {
        return $this->security->verifyToken($token, $action);
    }
    
    /**
     * Set success message
     * 
     * @param string $message Success message
     * @return void
     */
    public function setSuccess(string $message): void
    {
        $_SESSION['success'] = $message;
    }
    
    /**
     * Set error message
     * 
     * @param string $message Error message
     * @return void
     */
    public function setError(string $message): void
    {
        $_SESSION['error'] = $message;
    }
    
    /**
     * Get POST data with sanitization
     * 
     * @param string $key Post key
     * @param string $type Sanitization type (text|email|url|textarea|int|bool)
     * @param mixed $default Default value
     * @return mixed
     */
    public function getPost(string $key, string $type = 'text', $default = '')
    {
        if (!isset($_POST[$key])) {
            return $default;
        }
        
        $value = $_POST[$key];
        
        switch ($type) {
            case 'email':
                return sanitize_email($value);
            case 'url':
                return esc_url_raw($value);
            case 'textarea':
                return sanitize_textarea_field($value);
            case 'int':
                return (int) $value;
            case 'bool':
                return (bool) $value;
            case 'text':
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Check if checkbox is checked
     * 
     * @param string $key Post key
     * @return bool
     */
    public function isChecked(string $key): bool
    {
        return isset($_POST[$key]);
    }
    
    /**
     * Render view
     * 
     * @param string $view View filename (without .php)
     * @param array $data Data to pass to view
     * @return void
     */
    public function render(string $view, array $data = []): void
    {
        // Extract data to variables
        extract($data);
        
        // Always pass user to view
        $user = $this->user;
        
        // Load menu
        require_once __DIR__ . '/../partials/member-menu.php';
        
        // Load view
        $viewFile = __DIR__ . '/../partials/' . $view . '.php';
        
        if (!file_exists($viewFile)) {
            http_response_code(404);
            die('Seite nicht gefunden.');
        }
        
        require $viewFile;
    }
    
    /**
     * Get user object
     * 
     * @return object
     */
    public function getUser(): object
    {
        return $this->user;
    }

    /**
     * Handle Security Actions
     */
    public function handleSecurityActions(): void
    {
        if (!$this->verifyToken($this->getPost('csrf_token'), 'change_password') && 
            !$this->verifyToken($this->getPost('csrf_token'), 'toggle_2fa')) {
            $this->setError('Sicherheitsüberprüfung fehlgeschlagen.');
            return;
        }

        if ($this->getPost('action') === 'change_password') {
            $current = $this->getPost('current_password');
            $new = $this->getPost('new_password');
            $confirm = $this->getPost('confirm_password');
            
            $result = $this->memberService->changePassword($this->user->id, $current, $new, $confirm);
            
            if ($result === true) {
                $this->setSuccess('Passwort erfolgreich geändert!');
            } else {
                $this->setError(is_string($result) ? $result : 'Fehler beim Ändern des Passworts.');
            }
        } elseif ($this->getPost('action') === 'toggle_2fa') {
            $enable = $this->getPost('enable_2fa') === '1';
            $result = $this->memberService->toggle2FA($this->user->id, $enable);
            
            if ($result === true) {
                $this->setSuccess($enable ? '2FA aktiviert!' : '2FA deaktiviert!');
            } else {
                $this->setError('Fehler bei der 2FA-Änderung.');
            }
        }
        
        $this->redirect('/member/security');
    }

    /**
     * Handle Notification Actions
     */
    public function handleNotificationActions(): void
    {
        if (!$this->verifyToken($this->getPost('csrf_token'), 'member_notifications')) {
            $this->setError('Sicherheitsüberprüfung fehlgeschlagen.');
            return;
        }

        $preferences = [
            'email_notifications'     => $this->isChecked('email_notifications'),
            'email_marketing'         => $this->isChecked('email_marketing'),
            'email_updates'           => $this->isChecked('email_updates'),
            'email_security'          => $this->isChecked('email_security'),
            'browser_notifications'   => $this->isChecked('browser_notifications'),
            'desktop_notifications'   => $this->isChecked('desktop_notifications'),
            'mobile_notifications'    => $this->isChecked('mobile_notifications'),
            'notify_new_features'     => $this->isChecked('notify_new_features'),
            'notify_promotions'       => $this->isChecked('notify_promotions'),
            'notification_frequency'  => $this->getPost('notification_frequency', 'text', 'immediate'),
        ];

        // Safe hook handling
        if (class_exists('\CMS\Hooks')) {
            $preferences = \CMS\Hooks::applyFilters('member_notification_preferences', $preferences, $this->user->id);
        }

        if ($this->memberService->updateNotificationPreferences($this->user->id, $preferences)) {
            $this->setSuccess('Einstellungen gespeichert.');
        } else {
            $this->setError('Fehler beim Speichern.');
        }

        $this->redirect('/member/notifications');
    }

    /**
     * Handle Privacy Actions
     */
    public function handlePrivacyActions(): void
    {
        if (!$this->verifyToken($this->getPost('csrf_token'), 'privacy_settings') && 
            !$this->verifyToken($this->getPost('csrf_token'), 'data_export') &&
            !$this->verifyToken($this->getPost('csrf_token'), 'account_delete')) {
            $this->setError('Sicherheitsüberprüfung fehlgeschlagen.');
            return;
        }

        $action = $this->getPost('action');

        if ($action === 'update_privacy') {
            $settings = [
                'profile_visibility' => $this->getPost('profile_visibility'),
                'show_email' => $this->isChecked('show_email'),
                'show_activity' => $this->isChecked('show_activity'),
            ];
            
            if ($this->memberService->updatePrivacySettings($this->user->id, $settings)) {
                $this->setSuccess('Privatsphäre-Einstellungen aktualisiert.');
            } else {
                $this->setError('Fehler beim Aktualisieren.');
            }
        } elseif ($action === 'export_data') {
            $data = $this->memberService->exportUserData($this->user->id);
            if ($data) {
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="export-'.date('Y-m-d').'.json"');
                echo json_encode($data);
                exit;
            }
            $this->setError('Export fehlgeschlagen.');
        } elseif ($action === 'delete_account') {
            // Additional password check usually here
            if ($this->memberService->requestAccountDeletion($this->user->id)) {
                $this->setSuccess('Account zur Löschung markiert (30 Tage Frist).');
                // Force logout or similar
            } else {
                $this->setError('Löschung konnte nicht beantragt werden.');
            }
        }

        $this->redirect('/member/privacy');
    }
}
