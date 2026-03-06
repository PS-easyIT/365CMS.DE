<?php
/**
 * Admin Kommentare (modular)
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\Services\CommentService;

if (!defined('ABSPATH')) {
    exit;
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$commentService = CommentService::getInstance();
$security = Security::instance();

$status = in_array($_GET['status'] ?? 'all', ['all', 'pending', 'approved', 'spam', 'trash'], true)
    ? (string)($_GET['status'] ?? 'all')
    : 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['comment_id'])) {
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'admin_comments_action')) {
        $_SESSION['error'] = 'Sicherheitscheck fehlgeschlagen.';
    } else {
        $action = (string)$_POST['action'];
        $commentId = (int)$_POST['comment_id'];

        $ok = false;
        switch ($action) {
            case 'approve':
                $ok = $commentService->updateStatus($commentId, 'approved');
                break;
            case 'pending':
                $ok = $commentService->updateStatus($commentId, 'pending');
                break;
            case 'spam':
                $ok = $commentService->updateStatus($commentId, 'spam');
                break;
            case 'trash':
                $ok = $commentService->updateStatus($commentId, 'trash');
                break;
            case 'delete':
                $ok = $commentService->delete($commentId);
                break;
        }

        if ($ok) {
            $_SESSION['success'] = 'Kommentar erfolgreich aktualisiert.';
        } else {
            $_SESSION['error'] = 'Aktion konnte nicht ausgeführt werden.';
        }
    }

    header('Location: ' . SITE_URL . '/admin/comments?status=' . urlencode($status));
    exit;
}

$counts = $commentService->getCounts();
$comments = $commentService->getComments($status, 100, 0);
$csrfToken = $security->generateToken('admin_comments_action');
$success = isset($_SESSION['success']) ? (string) $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? (string) $_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']);

require_once dirname(__DIR__, 2) . '/partials/admin-menu.php';
?>
<?php renderAdminLayoutStart('Kommentar-Moderation', 'comments'); ?>

<div class="page-header d-print-none mb-3">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Moderation</div>
            <h2 class="page-title">💬 Kommentare</h2>
        </div>
    </div>
</div>

<?php renderAdminAlerts(); ?>

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-2">
        <?php
        $tabs = [
            'all' => 'Alle',
            'pending' => 'Wartend',
            'approved' => 'Freigegeben',
            'spam' => 'Spam',
            'trash' => 'Papierkorb',
        ];
        foreach ($tabs as $key => $label):
            $isActive = $status === $key;
            $count = (int)($counts[$key] ?? 0);
        ?>
            <a href="<?php echo SITE_URL; ?>/admin/comments?status=<?php echo urlencode($key); ?>"
               class="btn <?php echo $isActive ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <?php echo htmlspecialchars($label); ?> <span class="badge bg-white text-dark ms-1"><?php echo $count; ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Kommentarliste</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
            <tr>
                <th>Autor</th>
                <th>Beitrag</th>
                <th>Status</th>
                <th>Kommentar</th>
                <th>Datum</th>
                <th class="text-end">Aktionen</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($comments)): ?>
                <tr>
                    <td colspan="6" class="text-secondary">Keine Kommentare in diesem Filter gefunden.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <?php
                    $item = (array)$comment;
                    $statusValue = (string)($item['status'] ?? 'pending');
                    $statusBadge = match ($statusValue) {
                        'approved' => 'bg-green-lt text-green',
                        'spam' => 'bg-red-lt text-red',
                        'trash' => 'bg-yellow-lt text-yellow',
                        default => 'bg-blue-lt text-blue',
                    };
                    $commentId = (int)($item['id'] ?? 0);
                    $authorName = (string)($item['author'] ?? '');
                    $actionMap = [
                        'approve' => ['Freigeben', 'btn-outline-success'],
                        'pending' => ['Wartend', 'btn-outline-primary'],
                        'spam' => ['Spam', 'btn-outline-warning'],
                        'trash' => ['Papierkorb', 'btn-outline-secondary'],
                        'delete' => ['Endgültig löschen', 'btn-danger'],
                    ];
                    ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($authorName, ENT_QUOTES); ?></div>
                            <div class="text-secondary"><?php echo htmlspecialchars((string)($item['author_email'] ?? ''), ENT_QUOTES); ?></div>
                        </td>
                        <td>
                            <?php if (!empty($item['post_slug'])): ?>
                                <a href="<?php echo SITE_URL; ?>/blog/<?php echo urlencode((string)$item['post_slug']); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo htmlspecialchars((string)($item['post_title'] ?? ('Post #' . (int)($item['post_id'] ?? 0))), ENT_QUOTES); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-secondary">Post #<?php echo (int)($item['post_id'] ?? 0); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?php echo $statusBadge; ?>"><?php echo htmlspecialchars($statusValue, ENT_QUOTES); ?></span></td>
                        <td>
                            <?php
                            $excerpt = trim(strip_tags((string)($item['content'] ?? '')));
                            if (mb_strlen($excerpt) > 140) {
                                $excerpt = mb_substr($excerpt, 0, 140) . '…';
                            }
                            ?>
                            <?php echo htmlspecialchars($excerpt, ENT_QUOTES); ?>
                        </td>
                        <td title="<?php echo date('d.m.Y H:i', strtotime((string)($item['post_date'] ?? 'now'))); ?>"><?php echo time_ago((string)($item['post_date'] ?? 'now')); ?></td>
                        <td class="text-end">
                            <div class="btn-list justify-content-end">
                                <?php foreach ($actionMap as $actionKey => [$actionLabel, $buttonClass]): ?>
                                    <?php if ($actionKey === $statusValue): ?>
                                        <?php continue; ?>
                                    <?php endif; ?>
                                    <?php $formId = 'comment-action-' . $commentId . '-' . $actionKey; ?>
                                    <form method="post" class="d-inline" id="<?php echo htmlspecialchars($formId, ENT_QUOTES); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                                        <input type="hidden" name="comment_id" value="<?php echo $commentId; ?>">
                                        <input type="hidden" name="action" value="<?php echo htmlspecialchars($actionKey, ENT_QUOTES); ?>">
                                        <?php if ($actionKey === 'delete'): ?>
                                            <button type="button"
                                                    class="btn btn-sm <?php echo $buttonClass; ?>"
                                                    onclick="confirmDeleteComment(<?php echo htmlspecialchars((string) json_encode($formId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES); ?>, <?php echo htmlspecialchars((string) json_encode($authorName, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES); ?>)">
                                                <?php echo htmlspecialchars($actionLabel, ENT_QUOTES); ?>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-sm <?php echo $buttonClass; ?>">
                                                <?php echo htmlspecialchars($actionLabel, ENT_QUOTES); ?>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmDeleteComment(formId, authorName) {
    var form = document.getElementById(formId);
    if (!form) {
        return;
    }

    var label = authorName && authorName.trim() !== '' ? ' von „' + authorName + '“' : '';
    cmsConfirm('Soll der Kommentar' + label + ' wirklich endgültig gelöscht werden?', function () {
        form.submit();
    }, 'Kommentar löschen');
}
</script>

<?php renderAdminLayoutEnd(); ?>
