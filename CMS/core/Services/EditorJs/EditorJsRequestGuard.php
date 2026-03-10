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
    public function ensureAdminAccess(): void
    {
        if (!class_exists(\CMS\Auth::class) || !\CMS\Auth::instance()->isAdmin()) {
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