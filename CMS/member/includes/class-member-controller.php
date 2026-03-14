<?php
declare(strict_types=1);

namespace CMS\MemberArea;

use CMS\Auth;
use CMS\Database;
use CMS\Hooks;
use CMS\Json;
use CMS\Security;
use CMS\Auth\AuthManager;
use CMS\Auth\MFA\BackupCodesManager;
use CMS\Auth\MFA\TotpAdapter;
use CMS\Auth\Passkey\WebAuthnAdapter;
use CMS\Member\PluginDashboardRegistry;
use CMS\Services\MediaService;
use CMS\Services\MemberService;
use CMS\Services\MessageService;

if (!defined('ABSPATH')) {
    exit;
}

final class MemberController
{
    private static ?self $instance = null;

    private Auth $auth;
    private Database $db;
    private string $prefix;
    private MemberService $memberService;
    private MessageService $messageService;
    private AuthManager $authManager;
    private PluginDashboardRegistry $registry;

    /** @var array<string,mixed>|null */
    private ?array $settings = null;

    /** @var object|null */
    private ?object $currentUser = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->auth = Auth::instance();
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->memberService = MemberService::getInstance();
        $this->messageService = MessageService::getInstance();
        $this->authManager = AuthManager::instance();
        $this->registry = PluginDashboardRegistry::instance();
        $this->registry->init();
    }

    public function requireAuth(): void
    {
        if (!$this->auth->isLoggedIn()) {
            $redirect = urlencode((string)($_SERVER['REQUEST_URI'] ?? '/member/dashboard'));
            header('Location: ' . SITE_URL . '/login?redirect=' . $redirect);
            exit;
        }

        $this->currentUser = $this->auth->getCurrentUser();
    }

    public function getCurrentUser(): object
    {
        $this->requireAuth();
        return $this->currentUser;
    }

    public function getUserId(): int
    {
        return (int)($this->getCurrentUser()->id ?? 0);
    }

    /**
     * @return array<string,mixed>
     */
    public function getSettings(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $moduleFile = ABSPATH . 'admin/modules/member/MemberDashboardModule.php';
        if (!class_exists('MemberDashboardModule') && file_exists($moduleFile)) {
            require_once $moduleFile;
        }

        if (class_exists('MemberDashboardModule')) {
            try {
                $module = new \MemberDashboardModule();
                $data = $module->getData();
                if (is_array($data['settings'] ?? null)) {
                    $this->settings = $data['settings'];
                    return $this->settings;
                }
            } catch (\Throwable) {
            }
        }

        $this->settings = [
            'dashboard_enabled' => true,
            'registration_enabled' => true,
            'email_verification' => false,
            'welcome_message' => '',
            'default_role' => 'member',
            'widgets' => ['profile', 'activity', 'notifications', 'quick_links'],
            'profile_fields' => ['first_name', 'last_name', 'bio', 'website', 'avatar'],
            'dashboard_columns' => 3,
            'section_order' => 'quick_start,stats,widgets,plugins',
            'dashboard_logo' => '',
            'dashboard_greeting' => 'Willkommen zurück, {name}!',
            'dashboard_welcome_text' => '',
            'show_welcome' => true,
            'subscription_visible' => true,
            'custom_widgets' => [1 => ['title' => '', 'content' => '', 'icon' => ''], 2 => ['title' => '', 'content' => '', 'icon' => ''], 3 => ['title' => '', 'content' => '', 'icon' => ''], 4 => ['title' => '', 'content' => '', 'icon' => '']],
            'design' => [
                'primary' => '#6366f1',
                'accent' => '#8b5cf6',
                'bg' => '#f1f5f9',
                'card_bg' => '#ffffff',
                'text' => '#1e293b',
                'border' => '#e2e8f0',
            ],
            'frontend_modules' => [
                'show_quickstart' => true,
                'show_stats' => true,
                'show_custom_widgets' => true,
                'show_plugin_widgets' => true,
                'show_notifications_panel' => true,
                'show_onboarding_panel' => true,
            ],
            'notifications' => [
                'center_enabled' => true,
                'email_enabled' => false,
                'digest_frequency' => 'daily',
                'sender_name' => '365CMS Member Hub',
                'empty_text' => 'Aktuell gibt es keine neuen Meldungen.',
                'types' => ['system', 'messages'],
            ],
            'onboarding' => [
                'enabled' => true,
                'title' => 'Dein Start im Member-Bereich',
                'intro' => 'Konfiguriere dein Profil, sichere deinen Zugang und entdecke die wichtigsten Bereiche.',
                'steps' => ['Profil vervollständigen', 'Sicherheit aktivieren', 'Dateien hochladen'],
                'cta_label' => 'Profil öffnen',
                'cta_url' => '/member/profile',
                'require_profile_completion' => false,
            ],
            'plugin_widget_order' => [],
        ];

        return $this->settings;
    }

    public function csrfToken(string $action): string
    {
        return Security::instance()->generateToken('member_' . $action);
    }

    public function verifyCsrf(string $action, ?string $token = null): bool
    {
        $token ??= (string)($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        return Security::instance()->verifyToken($token, 'member_' . $action);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function flash(string $type, string $message, array $payload = []): void
    {
        $_SESSION['member_alert'] = [
            'type' => $type,
            'message' => $message,
            'payload' => $payload,
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function consumeFlash(): ?array
    {
        if (empty($_SESSION['member_alert']) || !is_array($_SESSION['member_alert'])) {
            return null;
        }

        $flash = $_SESSION['member_alert'];
        unset($_SESSION['member_alert']);
        return $flash;
    }

    public function redirect(string $path): void
    {
        if (!str_starts_with($path, 'http')) {
            $path = SITE_URL . $path;
        }

        header('Location: ' . $path);
        exit;
    }

    public function getDisplayName(): string
    {
        $user = $this->getCurrentUser();
        $meta = $this->memberService->getUserMeta($this->getUserId());
        $firstName = trim((string)($meta['first_name'] ?? ''));
        $lastName = trim((string)($meta['last_name'] ?? ''));
        $fullName = trim($firstName . ' ' . $lastName);

        if ($fullName !== '') {
            return $fullName;
        }

        if (!empty($user->display_name)) {
            return (string)$user->display_name;
        }

        return (string)($user->username ?? $user->email ?? 'Mitglied');
    }

    public function getInitials(): string
    {
        $name = $this->getDisplayName();
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1);
        }

        return strtoupper($initials !== '' ? $initials : 'M');
    }

    public function getAvatarUrl(): string
    {
        $meta = $this->memberService->getUserMeta($this->getUserId());
        $avatar = trim((string)($meta['avatar'] ?? ''));

        if ($avatar !== '') {
            return $avatar;
        }

        return '';
    }

    public function canAccessAdminPortal(): bool
    {
        if ($this->auth->isAdmin()) {
            return true;
        }

        foreach ($this->getAdminPortalCapabilities() as $capability) {
            if ($this->auth->hasCapability($capability)) {
                return true;
            }
        }

        return false;
    }

    public function getAdminPortalUrl(): string
    {
        return '/admin';
    }

    /**
     * @return array<int,string>
     */
    private function getAdminPortalCapabilities(): array
    {
        return ['adminportal', 'admin.portal', 'admin-portal'];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getMenuItems(string $activePage): array
    {
        $settings = $this->getSettings();
        $items = [
            ['slug' => 'dashboard', 'label' => 'Dashboard', 'icon' => '🏠', 'url' => '/member/dashboard', 'category' => 'core'],
            ['slug' => 'profile', 'label' => 'Profil', 'icon' => '👤', 'url' => '/member/profile', 'category' => 'core'],
            ['slug' => 'security', 'label' => 'Sicherheit', 'icon' => '🛡️', 'url' => '/member/security', 'category' => 'core'],
            ['slug' => 'notifications', 'label' => 'Benachrichtigungen', 'icon' => '🔔', 'url' => '/member/notifications', 'category' => 'core'],
            ['slug' => 'messages', 'label' => 'Nachrichten', 'icon' => '✉️', 'url' => '/member/messages', 'category' => 'core'],
            ['slug' => 'media', 'label' => 'Dateien', 'icon' => '🗂️', 'url' => '/member/media', 'category' => 'content'],
            ['slug' => 'favorites', 'label' => 'Favoriten', 'icon' => '⭐', 'url' => '/member/favorites', 'category' => 'content'],
            ['slug' => 'privacy', 'label' => 'Datenschutz', 'icon' => '🔐', 'url' => '/member/privacy', 'category' => 'account'],
        ];

        if (!empty($settings['subscription_visible'])) {
            $items[] = ['slug' => 'subscription', 'label' => 'Abo & Bestellungen', 'icon' => '💳', 'url' => '/member/subscription', 'category' => 'account'];
        }

        if ($this->canAccessAdminPortal()) {
            $items[] = ['slug' => 'admin', 'label' => 'Adminmenü', 'icon' => '⚙️', 'url' => $this->getAdminPortalUrl(), 'category' => 'admin'];
        }

        $items = Hooks::applyFilters('member_menu_items', $items);

        foreach ($items as &$item) {
            $item['active'] = ($item['slug'] ?? '') === $activePage;
        }
        unset($item);

        usort($items, static function (array $a, array $b): int {
            return strcmp((string)($a['label'] ?? ''), (string)($b['label'] ?? ''));
        });

        $coreOrder = ['dashboard', 'profile', 'security', 'notifications', 'messages', 'media', 'favorites', 'privacy', 'subscription', 'admin'];
        usort($items, static function (array $a, array $b) use ($coreOrder): int {
            $aIndex = array_search((string)($a['slug'] ?? ''), $coreOrder, true);
            $bIndex = array_search((string)($b['slug'] ?? ''), $coreOrder, true);
            $aIndex = $aIndex === false ? 999 : $aIndex;
            $bIndex = $bIndex === false ? 999 : $bIndex;
            return $aIndex <=> $bIndex;
        });

        return $items;
    }

    /**
     * @return array<string,mixed>
     */
    public function getDashboardData(): array
    {
        $userId = $this->getUserId();
        $settings = $this->getSettings();
        $data = $this->memberService->getMemberDashboardData($userId);
        $data['profile_completion'] = $this->getProfileCompletion();
        $data['recent_activity'] = $this->getRecentActivity();
        $data['unread_messages'] = $this->messageService->getUnreadCount($userId);
        $data['favorites'] = $this->getFavorites();
        $data['plugin_widgets'] = $this->getPluginWidgets();
        $data['custom_widgets'] = $this->getCustomWidgets();
        $data['settings'] = $settings;
        return $data;
    }

    /**
     * @return array<string,mixed>
     */
    public function getProfileCompletion(): array
    {
        $settings = $this->getSettings();
        $meta = $this->memberService->getUserMeta($this->getUserId());
        $selectedFields = (array)($settings['profile_fields'] ?? []);
        $completed = 0;
        $missing = [];
        $total = max(1, count($selectedFields));

        foreach ($selectedFields as $field) {
            $value = match ($field) {
                'avatar' => $meta['avatar'] ?? '',
                'social' => $meta['social'] ?? '',
                default => $meta[$field] ?? '',
            };

            if (trim((string)$value) !== '') {
                $completed++;
            } else {
                $missing[] = (string)$field;
            }
        }

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => (int)round(($completed / $total) * 100),
            'missing' => $missing,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getPluginWidgets(): array
    {
        $widgets = $this->registry->getDashboardWidgets($this->getCurrentUser());
        $settings = $this->getSettings();
        $order = array_values(array_filter(array_map('strval', (array)($settings['plugin_widget_order'] ?? []))));

        usort($widgets, static function (array $a, array $b) use ($order): int {
            $aPos = array_search((string)($a['plugin'] ?? $a['slug'] ?? ''), $order, true);
            $bPos = array_search((string)($b['plugin'] ?? $b['slug'] ?? ''), $order, true);
            $aPos = $aPos === false ? 999 : $aPos;
            $bPos = $bPos === false ? 999 : $bPos;
            return $aPos <=> $bPos;
        });

        return Hooks::applyFilters('member_dashboard_widgets', $widgets, $this->getCurrentUser(), $settings);
    }

    /**
     * @return array<int,array<string,string>>
     */
    public function getCustomWidgets(): array
    {
        $widgets = [];
        foreach ((array)($this->getSettings()['custom_widgets'] ?? []) as $widget) {
            if (trim((string)($widget['title'] ?? '')) === '' && trim((string)($widget['content'] ?? '')) === '') {
                continue;
            }
            $widgets[] = [
                'title' => trim((string)($widget['title'] ?? '')),
                'content' => trim((string)($widget['content'] ?? '')),
                'icon' => trim((string)($widget['icon'] ?? '✨')),
            ];
        }

        return $widgets;
    }

    /**
     * @return array<int,object>
     */
    public function getRecentActivity(int $limit = 8): array
    {
        try {
            return $this->db->get_results(
                "SELECT action, description, created_at FROM {$this->prefix}activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
                [$this->getUserId(), $limit]
            ) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<int,object>
     */
    public function getOrders(): array
    {
        try {
            return $this->db->get_results(
                "SELECT o.*, p.name AS plan_name
                 FROM {$this->prefix}orders o
                 LEFT JOIN {$this->prefix}subscription_plans p ON p.id = o.plan_id
                 WHERE o.user_id = ? OR (o.user_id IS NULL AND o.email = ?)
                 ORDER BY o.created_at DESC
                 LIMIT 25",
                [$this->getUserId(), (string)($this->getCurrentUser()->email ?? '')]
            ) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getFavorites(): array
    {
        $favorites = [];

        try {
            $rows = $this->db->get_results(
                "SELECT * FROM {$this->prefix}favorites WHERE user_id = ? ORDER BY created_at DESC LIMIT 20",
                [$this->getUserId()]
            ) ?: [];

            foreach ($rows as $row) {
                $favorites[] = [
                    'title' => (string)($row->title ?? $row->name ?? ('Eintrag #' . (int)($row->id ?? 0))),
                    'url' => (string)($row->url ?? '#'),
                    'type' => (string)($row->type ?? 'Favorit'),
                    'created_at' => (string)($row->created_at ?? ''),
                ];
            }
        } catch (\Throwable) {
            $meta = $this->memberService->getUserMeta($this->getUserId());
            $saved = Json::decodeArray($meta['favorites'] ?? null, []);
            if (is_array($saved)) {
                foreach ($saved as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $favorites[] = [
                        'title' => (string)($item['title'] ?? 'Gespeicherter Eintrag'),
                        'url' => (string)($item['url'] ?? '#'),
                        'type' => (string)($item['type'] ?? 'Favorit'),
                        'created_at' => (string)($item['created_at'] ?? ''),
                    ];
                }
            }
        }

        return $favorites;
    }

    public function getMemberMediaPath(?int $userId = null): string
    {
        $userId ??= $this->getUserId();
        return 'member/user-' . $userId;
    }

    private function ensureMemberMediaRootExists(): bool
    {
        $memberRoot = rtrim((string)UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'member';
        $userRoot = $memberRoot . DIRECTORY_SEPARATOR . 'user-' . $this->getUserId();

        if (is_dir($userRoot)) {
            return true;
        }

        if (!mkdir($userRoot, 0755, true) && !is_dir($userRoot)) {
            error_log('MemberController::ensureMemberMediaRootExists() failed for ' . $userRoot);
            return false;
        }

        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function getMediaOverview(): array
    {
        $mediaService = MediaService::getInstance();
        $path = $this->getMemberMediaPath();
        $items = $this->ensureMemberMediaRootExists() ? $mediaService->getItems($path) : ['folders' => [], 'files' => []];
        if (!is_array($items)) {
            $items = ['folders' => [], 'files' => []];
        }

        return [
            'path' => $path,
            'items' => $items,
            'settings' => $mediaService->getSettings(),
        ];
    }

    public function handleProfileRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (string)($_POST['action'] ?? '') !== 'profile_save') {
            return;
        }

        if (!$this->verifyCsrf('profile_save')) {
            $this->flash('danger', 'Sicherheitsüberprüfung fehlgeschlagen.');
            $this->redirect('/member/profile');
        }

        $data = [
            'display_name' => trim((string)($_POST['display_name'] ?? '')),
            'first_name' => trim((string)($_POST['first_name'] ?? '')),
            'last_name' => trim((string)($_POST['last_name'] ?? '')),
            'email' => trim((string)($_POST['email'] ?? '')),
            'bio' => trim((string)($_POST['bio'] ?? '')),
            'website' => trim((string)($_POST['website'] ?? '')),
            'phone' => trim((string)($_POST['phone'] ?? '')),
            'company' => trim((string)($_POST['company'] ?? '')),
            'position' => trim((string)($_POST['position'] ?? '')),
            'birth_date' => trim((string)($_POST['birth_date'] ?? '')),
        ];

        $result = $this->memberService->updateProfile($this->getUserId(), $data);
        $this->upsertUserMeta('location', trim((string)($_POST['location'] ?? '')));
        $this->upsertUserMeta('social', trim((string)($_POST['social'] ?? '')));
        $this->upsertUserMeta('avatar', trim((string)($_POST['avatar'] ?? '')));

        if ($result === true) {
            $this->flash('success', 'Dein Profil wurde aktualisiert.');
        } else {
            $this->flash('danger', (string)$result);
        }

        $this->redirect('/member/profile');
    }

    public function handleNotificationsRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (string)($_POST['action'] ?? '') !== 'notifications_save') {
            return;
        }

        if (!$this->verifyCsrf('notifications_save')) {
            $this->flash('danger', 'Sicherheitsüberprüfung fehlgeschlagen.');
            $this->redirect('/member/notifications');
        }

        $preferences = [
            'email_notifications' => !empty($_POST['email_notifications']),
            'email_marketing' => !empty($_POST['email_marketing']),
            'email_updates' => !empty($_POST['email_updates']),
            'email_security' => !empty($_POST['email_security']),
            'browser_notifications' => !empty($_POST['browser_notifications']),
            'desktop_notifications' => !empty($_POST['desktop_notifications']),
            'mobile_notifications' => !empty($_POST['mobile_notifications']),
            'notify_new_features' => !empty($_POST['notify_new_features']),
            'notify_promotions' => !empty($_POST['notify_promotions']),
            'notification_frequency' => (string)($_POST['notification_frequency'] ?? 'immediate'),
        ];

        $this->memberService->updateNotificationPreferences($this->getUserId(), $preferences);
        $this->flash('success', 'Deine Benachrichtigungseinstellungen wurden gespeichert.');
        $this->redirect('/member/notifications');
    }

    public function handlePrivacyRequest(): void
    {
        $action = (string)($_POST['action'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($action, ['privacy_save', 'privacy_export', 'privacy_delete_request'], true)) {
            return;
        }

        if (!$this->verifyCsrf('privacy_action')) {
            $this->flash('danger', 'Sicherheitsüberprüfung fehlgeschlagen.');
            $this->redirect('/member/privacy');
        }

        if ($action === 'privacy_save') {
            $settings = [
                'profile_visibility' => (string)($_POST['profile_visibility'] ?? 'members'),
                'show_email' => !empty($_POST['show_email']),
                'show_activity' => !empty($_POST['show_activity']),
            ];
            $this->memberService->updatePrivacySettings($this->getUserId(), $settings);
            $this->flash('success', 'Datenschutzeinstellungen gespeichert.');
            $this->redirect('/member/privacy');
        }

        if ($action === 'privacy_delete_request') {
            $this->memberService->requestAccountDeletion($this->getUserId());
            $this->flash('warning', 'Dein Account wurde zur Löschung vorgemerkt. Die Frist beträgt 30 Tage.');
            $this->redirect('/member/privacy');
        }

        $export = $this->memberService->exportUserData($this->getUserId());
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="member-export-' . $this->getUserId() . '.json"');
        echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function handleMessagesRequest(): void
    {
        $action = (string)($_POST['action'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($action, ['message_send', 'message_delete'], true)) {
            return;
        }

        if (!$this->verifyCsrf('messages_action')) {
            $this->flash('danger', 'Sicherheitsüberprüfung fehlgeschlagen.');
            $this->redirect('/member/messages');
        }

        if ($action === 'message_delete') {
            $messageId = (int)($_POST['message_id'] ?? 0);
            $deleted = $this->messageService->delete($messageId, $this->getUserId());
            $this->flash($deleted ? 'success' : 'danger', $deleted ? 'Nachricht gelöscht.' : 'Nachricht konnte nicht gelöscht werden.');
            $this->redirect('/member/messages');
        }

        $recipientInput = trim((string)($_POST['recipient'] ?? ''));
        $recipientId = ctype_digit($recipientInput) ? (int)$recipientInput : $this->resolveRecipientId($recipientInput);
        $subject = trim((string)($_POST['subject'] ?? ''));
        $body = trim((string)($_POST['body'] ?? ''));
        $parentId = (int)($_POST['parent_id'] ?? 0);

        $result = $recipientId > 0
            ? $this->messageService->send($this->getUserId(), $recipientId, $subject, $body, $parentId > 0 ? $parentId : null)
            : false;

        if ($result !== false) {
            $this->flash('success', 'Nachricht wurde gesendet.');
        } else {
            $this->flash('danger', 'Nachricht konnte nicht gesendet werden. Bitte Empfänger und Inhalt prüfen.');
        }

        $this->redirect('/member/messages');
    }

    public function handleMediaRequest(): void
    {
        $action = (string)($_POST['action'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($action, ['media_delete', 'media_folder_create'], true)) {
            return;
        }

        if (!$this->verifyCsrf('media_action')) {
            $this->flash('danger', 'Sicherheitsüberprüfung fehlgeschlagen.');
            $this->redirect('/member/media');
        }

        $mediaService = MediaService::getInstance();
        $memberPath = $this->getMemberMediaPath();
        $settings = $mediaService->getSettings();

        if (!$this->ensureMemberMediaRootExists()) {
            $this->flash('danger', 'Das persönliche Upload-Verzeichnis konnte nicht vorbereitet werden.');
            $this->redirect('/member/media');
        }

        if ($action === 'media_folder_create') {
            $folderName = trim((string)($_POST['folder_name'] ?? ''));
            $result = $mediaService->createFolder($folderName, $memberPath);
            $ok = !($result instanceof \CMS\WP_Error);
            $this->flash($ok ? 'success' : 'danger', $ok ? 'Ordner wurde erstellt.' : $result->get_error_message());
            $this->redirect('/member/media');
        }

        if (empty($settings['member_delete_own'])) {
            $this->flash('warning', 'Das Löschen eigener Dateien ist derzeit deaktiviert.');
            $this->redirect('/member/media');
        }

        $path = trim((string)($_POST['path'] ?? ''));
        if (!str_starts_with($path, $memberPath)) {
            $this->flash('danger', 'Ungültiger Dateipfad.');
            $this->redirect('/member/media');
        }

        $result = $mediaService->deleteItem($path);
        $ok = !($result instanceof \CMS\WP_Error);
        $this->flash($ok ? 'success' : 'danger', $ok ? 'Datei wurde gelöscht.' : $result->get_error_message());
        $this->redirect('/member/media');
    }

    public function handleSecurityRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $action = (string)($_POST['action'] ?? '');
        if (!in_array($action, ['password_change', 'totp_start', 'totp_confirm', 'totp_disable', 'backup_generate', 'passkey_register', 'passkey_delete'], true)) {
            return;
        }

        $csrfAction = match ($action) {
            'password_change' => 'security_password',
            'passkey_register', 'passkey_delete' => 'security_passkey',
            default => 'security_mfa',
        };

        if (!$this->verifyCsrf($csrfAction)) {
            $this->flash('danger', 'Sicherheitsüberprüfung fehlgeschlagen.');
            $this->redirect('/member/security');
        }

        $userId = $this->getUserId();

        if ($action === 'password_change') {
            $result = $this->memberService->changePassword(
                $userId,
                (string)($_POST['current_password'] ?? ''),
                (string)($_POST['new_password'] ?? ''),
                (string)($_POST['confirm_password'] ?? '')
            );

            if ($result === true) {
                $this->upsertUserMeta('password_changed_at', date('Y-m-d H:i:s'));
                $this->flash('success', 'Passwort erfolgreich geändert.');
            } else {
                $this->flash('danger', (string)$result);
            }

            $this->redirect('/member/security');
        }

        if ($action === 'totp_start') {
            $setup = TotpAdapter::instance()->startSetup($userId, $this->getDisplayName());
            $_SESSION['member_totp_setup'] = $setup;
            $this->flash('info', 'Authenticator-Setup wurde vorbereitet. Bestätige jetzt den 6-stelligen Code.', $setup);
            $this->redirect('/member/security');
        }

        if ($action === 'totp_confirm') {
            $code = trim((string)($_POST['totp_code'] ?? ''));
            $confirmed = TotpAdapter::instance()->confirmSetup($userId, $code);
            if ($confirmed) {
                unset($_SESSION['member_totp_setup']);
                $codes = BackupCodesManager::instance()->generate($userId);
                $this->flash('success', 'Zwei-Faktor-Authentifizierung wurde aktiviert.', ['backup_codes' => $codes]);
            } else {
                $this->flash('danger', 'Der eingegebene TOTP-Code war ungültig.');
            }
            $this->redirect('/member/security');
        }

        if ($action === 'totp_disable') {
            TotpAdapter::instance()->disable($userId);
            BackupCodesManager::instance()->deleteAll($userId);
            unset($_SESSION['member_totp_setup']);
            $this->flash('warning', 'Zwei-Faktor-Authentifizierung wurde deaktiviert.');
            $this->redirect('/member/security');
        }

        if ($action === 'backup_generate') {
            $codes = BackupCodesManager::instance()->regenerate($userId);
            $this->flash('success', 'Neue Backup-Codes wurden erzeugt.', ['backup_codes' => $codes]);
            $this->redirect('/member/security');
        }

        if ($action === 'passkey_delete') {
            $credentialId = (int)($_POST['credential_id'] ?? 0);
            $deleted = WebAuthnAdapter::instance()->deleteCredential($credentialId, $userId);
            $this->flash($deleted ? 'success' : 'danger', $deleted ? 'Passkey wurde entfernt.' : 'Passkey konnte nicht entfernt werden.');
            $this->redirect('/member/security');
        }

        $challenge = (string)($_SESSION['member_passkey_challenge'] ?? '');
        if ($challenge === '') {
            $this->flash('danger', 'Die Passkey-Challenge ist abgelaufen. Bitte erneut versuchen.');
            $this->redirect('/member/security');
        }

        $clientData = $this->base64UrlDecode((string)($_POST['client_data_json'] ?? ''));
        $attestation = $this->base64UrlDecode((string)($_POST['attestation_object'] ?? ''));
        $credentialName = trim((string)($_POST['credential_name'] ?? 'Neuer Passkey'));

        $result = WebAuthnAdapter::instance()->processRegistration($userId, $clientData, $attestation, $challenge, $credentialName);
        unset($_SESSION['member_passkey_challenge']);

        if ($result === true) {
            $this->flash('success', 'Passkey erfolgreich registriert.');
        } else {
            $this->flash('danger', (string)$result);
        }

        $this->redirect('/member/security');
    }

    /**
     * @return array<string,mixed>
     */
    public function getSecurityPageData(): array
    {
        $userId = $this->getUserId();
        $setup = $_SESSION['member_totp_setup'] ?? null;
        $credentials = WebAuthnAdapter::instance()->getCredentialsForUser($userId);
        $backupCount = BackupCodesManager::instance()->getRemainingCount($userId);
        $security = $this->memberService->getSecurityData($userId);
        $sessions = $this->memberService->getActiveSessions($userId);
        $passkeyPayload = $this->buildPasskeyRegistrationPayload();

        return [
            'security' => $security,
            'sessions' => $sessions,
            'credentials' => $credentials,
            'backup_count' => $backupCount,
            'totp_enabled' => TotpAdapter::instance()->isEnabled($userId),
            'totp_setup' => is_array($setup) ? $setup : null,
            'passkey_payload' => $passkeyPayload,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function buildPasskeyRegistrationPayload(): array
    {
        if (!$this->authManager->isPasskeyAvailable()) {
            return ['available' => false, 'options_json' => '{}'];
        }

        $options = WebAuthnAdapter::instance()->getRegistrationOptions(
            $this->getUserId(),
            (string)($this->getCurrentUser()->username ?? ('user-' . $this->getUserId())),
            $this->getDisplayName()
        );
        $_SESSION['member_passkey_challenge'] = (string)($options['challenge'] ?? '');

        return [
            'available' => true,
            'options_json' => json_encode($options['options'] ?? new \stdClass(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * @return array<int,object>
     */
    public function getInbox(): array
    {
        return $this->messageService->getInbox($this->getUserId(), 30, 0);
    }

    /**
     * @return array<int,object>
     */
    public function getSent(): array
    {
        return $this->messageService->getSent($this->getUserId(), 30, 0);
    }

    /**
     * @return array<int,object>
     */
    public function getThread(int $rootId): array
    {
        return $rootId > 0 ? $this->messageService->getThread($rootId, $this->getUserId()) : [];
    }

    private function resolveRecipientId(string $input): int
    {
        if ($input === '') {
            return 0;
        }

        $row = $this->db->get_row(
            "SELECT id FROM {$this->prefix}users WHERE status = 'active' AND (username = ? OR display_name = ? OR email = ?) LIMIT 1",
            [$input, $input, $input]
        );

        return (int)($row->id ?? 0);
    }

    private function upsertUserMeta(string $key, string $value): void
    {
        $this->db->execute(
            "INSERT INTO {$this->prefix}user_meta (user_id, meta_key, meta_value)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)",
            [$this->getUserId(), $key, $value]
        );
    }

    private function base64UrlDecode(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return (string)base64_decode($value, true);
    }
}
