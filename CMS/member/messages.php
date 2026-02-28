<?php
/**
 * Member Messages Controller
 *
 * Nachrichten-System mit Posteingang, Senden- und Thread-Ansicht.
 * Nutzt MessageService + cms_messages-Tabelle.
 *
 * @package CMSv2\Member
 */

declare(strict_types=1);

// Load configuration and autoloader
require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Member\MemberController;
use CMS\Services\MessageService;
use CMS\Security;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-member-controller.php';

$controller = new MemberController();
$user       = $controller->getUser();
$msgService = MessageService::getInstance();

$success = '';
$error   = '';

// ── AJAX: Empfänger-Suche ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_search_recipients'])) {
    header('Content-Type: application/json');
    $q = sanitize_text_field($_GET['q'] ?? '');
    if (strlen($q) < 2) {
        echo json_encode([]);
        exit;
    }
    $results = $msgService->searchRecipients($q, (int) $user->id, 10);
    echo json_encode(array_map(static fn($u) => [
        'id'           => (int) $u->id,
        'username'     => $u->username,
        'display_name' => $u->display_name,
    ], $results));
    exit;
}

// ── POST-Handler ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['msg_action'] ?? '';

    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'member_messages')) {
        $error = 'Sicherheitscheck fehlgeschlagen.';
    } else {
        switch ($action) {
            case 'send':
                $recipientId = (int) ($_POST['recipient_id'] ?? 0);
                $subject     = sanitize_text_field($_POST['subject'] ?? '');
                $body        = strip_tags($_POST['body'] ?? '', '<p><a><strong><em><br><ul><ol><li>');
                $parentId    = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;

                if ($recipientId < 1 || empty(trim($body))) {
                    $error = 'Empfänger und Nachricht sind erforderlich.';
                } else {
                    $newId = $msgService->send((int) $user->id, $recipientId, $subject, $body, $parentId);
                    if ($newId) {
                        $success = 'Nachricht wurde gesendet.';
                    } else {
                        $error = 'Nachricht konnte nicht gesendet werden.';
                    }
                }
                break;

            case 'delete':
                $msgId = (int) ($_POST['message_id'] ?? 0);
                if ($msgId > 0 && $msgService->delete($msgId, (int) $user->id)) {
                    $success = 'Nachricht gelöscht.';
                } else {
                    $error = 'Löschen fehlgeschlagen.';
                }
                break;
        }
    }
}

// ── View-Modus bestimmen ──────────────────────────────────────────────────
$view     = $_GET['view'] ?? 'inbox';   // inbox | sent | thread | compose
$msgId    = (int) ($_GET['id'] ?? 0);
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 20;
$offset   = ($page - 1) * $perPage;

$conversations  = [];
$activeMessage  = null;
$threadMessages = [];

switch ($view) {
    case 'sent':
        $conversations = $msgService->getSent((int) $user->id, $perPage, $offset);
        break;

    case 'thread':
        if ($msgId > 0) {
            $activeMessage  = $msgService->getMessage($msgId, (int) $user->id);
            $rootId         = $activeMessage ? (int) ($activeMessage->parent_id ?: $activeMessage->id) : 0;
            $threadMessages = $rootId ? $msgService->getThread($rootId, (int) $user->id) : [];
        }
        break;

    case 'compose':
        // Leeres Formular
        break;

    case 'inbox':
    default:
        $view = 'inbox';
        $conversations = $msgService->getInbox((int) $user->id, $perPage, $offset);
        break;
}

$unreadCount = $msgService->getUnreadCount((int) $user->id);
$csrfToken   = Security::instance()->generateToken('member_messages');

// ── Daten an View übergeben ───────────────────────────────────────────────
$data = [
    'user'            => $user,
    'conversations'   => $conversations,
    'activeMessage'   => $activeMessage,
    'threadMessages'  => $threadMessages,
    'unreadCount'     => $unreadCount,
    'view'            => $view,
    'csrfToken'       => $csrfToken,
    'success'         => $success,
    'error'           => $error,
    'currentPage'     => 'messages',
    'page'            => $page,
    'perPage'         => $perPage,
];

$controller->render('messages-view', $data);
