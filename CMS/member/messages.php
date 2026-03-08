<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/bootstrap.php';

$controller->handleMessagesRequest();

$pageTitle = 'Nachrichten';
$pageKey = 'messages';
$pageAssets = [];
$messageService = \CMS\Services\MessageService::getInstance();
$conversations = $messageService->getConversations($controller->getUserId(), 20, 0);
$inboxCount = $messageService->getInboxCount($controller->getUserId());
$selectedThreadId = (int)($_GET['thread'] ?? 0);
$thread = $selectedThreadId > 0 ? $controller->getThread($selectedThreadId) : [];

include __DIR__ . '/partials/header.php';
?>
<div class="row g-4">
    <div class="col-xl-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Konversationen</h3>
                <span class="badge bg-primary-lt"><?= (int)$inboxCount ?></span>
            </div>
            <div class="list-group list-group-flush list-group-hoverable member-conversation-list">
                <?php if ($conversations === []): ?>
                    <div class="card-body text-secondary">Noch keine Nachrichten vorhanden.</div>
                <?php else: ?>
                    <?php foreach ($conversations as $conversation): ?>
                        <?php
                        $threadId = (int)($conversation->thread_id ?? $conversation->id ?? 0);
                        $partner = (int)($conversation->sender_id ?? 0) === $controller->getUserId()
                            ? ((string)($conversation->recipient_display_name ?? $conversation->recipient_name ?? 'Empfänger'))
                            : ((string)($conversation->sender_display_name ?? $conversation->sender_name ?? 'Absender'));
                        ?>
                        <a class="list-group-item list-group-item-action <?= $selectedThreadId === $threadId ? 'active' : '' ?>" href="<?= htmlspecialchars(SITE_URL) ?>/member/messages?thread=<?= $threadId ?>">
                            <div class="d-flex justify-content-between">
                                <div class="fw-medium"><?= htmlspecialchars($partner) ?></div>
                                <div class="small text-secondary"><?= htmlspecialchars((string)($conversation->created_at ?? '')) ?></div>
                            </div>
                            <div class="text-secondary small"><?= htmlspecialchars((string)($conversation->subject ?? 'Ohne Betreff')) ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <form class="card" method="post" action="">
            <input type="hidden" name="action" value="message_send">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($controller->csrfToken('messages_action'), ENT_QUOTES) ?>">
            <div class="card-header"><h3 class="card-title">Neue Nachricht</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="recipient">Empfänger</label>
                    <input class="form-control" id="recipient" name="recipient" type="text" placeholder="Username, Anzeigename oder ID" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="subject">Betreff</label>
                    <input class="form-control" id="subject" name="subject" type="text">
                </div>
                <div>
                    <label class="form-label" for="body">Nachricht</label>
                    <textarea class="form-control" id="body" name="body" rows="6" required></textarea>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">Senden</button>
            </div>
        </form>
    </div>

    <div class="col-xl-8">
        <div class="card member-thread-card">
            <div class="card-header"><h3 class="card-title">Thread</h3></div>
            <div class="card-body">
                <?php if ($thread === []): ?>
                    <div class="empty">
                        <div class="empty-icon">✉️</div>
                        <p class="empty-title">Konversation auswählen</p>
                        <p class="empty-subtitle text-secondary">Wähle links einen Thread oder sende eine neue Nachricht.</p>
                    </div>
                <?php else: ?>
                    <div class="member-thread-list mb-4">
                        <?php foreach ($thread as $message): ?>
                            <?php $isOwn = (int)($message->sender_id ?? 0) === $controller->getUserId(); ?>
                            <div class="member-thread-bubble <?= $isOwn ? 'is-own' : '' ?>">
                                <div class="small text-secondary mb-1">
                                    <?= htmlspecialchars((string)($message->sender_display_name ?? $message->sender_name ?? 'Benutzer')) ?> · <?= htmlspecialchars((string)($message->created_at ?? '')) ?>
                                </div>
                                <?php if (!empty($message->subject)): ?>
                                    <div class="fw-semibold mb-1"><?= htmlspecialchars((string)$message->subject) ?></div>
                                <?php endif; ?>
                                <div><?= nl2br(htmlspecialchars((string)($message->body ?? ''))) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form method="post" action="">
                        <input type="hidden" name="action" value="message_send">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($controller->csrfToken('messages_action'), ENT_QUOTES) ?>">
                        <input type="hidden" name="parent_id" value="<?= $selectedThreadId ?>">
                        <input type="hidden" name="recipient" value="<?= (int)($thread[count($thread) - 1]->sender_id ?? 0) === $controller->getUserId() ? (int)($thread[count($thread) - 1]->recipient_id ?? 0) : (int)($thread[count($thread) - 1]->sender_id ?? 0) ?>">
                        <div class="mb-3">
                            <label class="form-label" for="reply_body">Antwort</label>
                            <textarea class="form-control" id="reply_body" name="body" rows="5" required></textarea>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <input type="text" class="form-control me-3" name="subject" value="<?= htmlspecialchars((string)($thread[0]->subject ?? 'Re: Nachricht')) ?>" aria-label="Betreff">
                            <button type="submit" class="btn btn-primary">Antwort senden</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php';
