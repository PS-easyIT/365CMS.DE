<?php
/**
 * Guard-Logik für Editor.js Media-Requests.
 *
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services\EditorJs;

if (!defined('ABSPATH')) {
    exit;
}

final class EditorJsRequestGuard
{
    public function ensureEditorAccess(): void
    {
        if (!class_exists(\CMS\Auth::class) || !\CMS\Auth::instance()->isLoggedIn()) {
            throw new \RuntimeException('Nicht autorisiert.', 403);
        }

        if (\CMS\Auth::instance()->isAdmin()) {
            return;
        }

        $currentUser = \CMS\Auth::instance()->currentUser();
        $userId = (int) ($currentUser->id ?? 0);

        if ($userId <= 0 || !class_exists(\CMS\Services\MemberService::class)) {
            throw new \RuntimeException('Nicht autorisiert.', 403);
        }

        try {
            $permissions = \CMS\Services\MemberService::getInstance()->getUserPermissions($userId);
        } catch (\Throwable) {
            throw new \RuntimeException('Nicht autorisiert.', 403);
        }

        if (empty($permissions['can_post'])) {
            throw new \RuntimeException('Nicht autorisiert.', 403);
        }
    }

    public function verifyMediaToken(): void
    {
        $token = '';

        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
        } elseif (isset($_POST['csrf_token'])) {
            $token = (string) $_POST['csrf_token'];
        }

        if ($token === '' || !class_exists(\CMS\Security::class) || !\CMS\Security::instance()->verifyPersistentToken($token, 'editorjs_media')) {
            throw new \RuntimeException('Ungültiges Sicherheitstoken für Editor.js-Uploads.', 403);
        }
    }
}