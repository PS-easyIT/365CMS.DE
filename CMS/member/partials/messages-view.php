<?php
/**
 * Member Messages View
 *
 * Vollständige Nachrichten-UI mit Posteingang, Senden, Thread-Ansicht.
 *
 * @package CMSv2\Member\Views
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nachrichten - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/member.css">
    <?php renderMemberSidebarStyles(); ?>
    <style>
        .messages-wrapper { height:calc(100vh - 4rem); display:flex; flex-direction:column; overflow:hidden; }
        .messages-container { display:grid; grid-template-columns:350px 1fr; gap:0; flex:1; min-height:0;
            background:#fff; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,.05); overflow:hidden; border:1px solid #e2e8f0; }
        .msg-list { border-right:1px solid #e2e8f0; display:flex; flex-direction:column; background:#f8fafc; }
        .msg-list-header { padding:.75rem 1rem; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between;
            align-items:center; background:#fff; gap:.5rem; }
        .msg-tab { padding:.4rem .75rem; border-radius:6px; font-size:.85rem; cursor:pointer;
            border:1px solid #e2e8f0; background:#fff; color:#475569; text-decoration:none; }
        .msg-tab.active { background:#6366f1; color:#fff; border-color:#6366f1; }
        .msg-tab:hover:not(.active) { background:#f1f5f9; }
        .msg-list-scroll { overflow-y:auto; flex:1; }
        .msg-item { padding:.85rem 1rem; border-bottom:1px solid #f1f5f9; cursor:pointer; transition:background .15s;
            display:flex; gap:.75rem; text-decoration:none; color:inherit; }
        .msg-item:hover { background:#f1f5f9; }
        .msg-item.active { background:#fff; border-left:3px solid #6366f1; }
        .msg-item.unread { background:#eff6ff; }
        .msg-avatar { width:38px; height:38px; border-radius:50%; background:#e2e8f0;
            display:flex; align-items:center; justify-content:center; color:#64748b; font-weight:600; font-size:.85rem; flex-shrink:0; }
        .msg-content { flex:1; min-width:0; }
        .msg-top { display:flex; justify-content:space-between; margin-bottom:.2rem; }
        .msg-name { font-weight:600; font-size:.875rem; color:#1e293b; }
        .msg-time { font-size:.75rem; color:#94a3b8; }
        .msg-subject { font-size:.85rem; color:#334155; margin-bottom:.15rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .msg-preview { font-size:.8rem; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .msg-badge { display:inline-block; background:#6366f1; color:#fff; font-size:.7rem; padding:.1rem .4rem; border-radius:10px; margin-left:.35rem; }
        .msg-detail { display:flex; flex-direction:column; background:#fff; }
        .msg-detail-header { padding:.85rem 1.5rem; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; }
        .msg-detail-body { flex:1; padding:1.5rem; overflow-y:auto; color:#334155; line-height:1.65; }
        .msg-detail-footer { padding:.85rem 1.5rem; border-top:1px solid #e2e8f0; background:#f8fafc; }
        .reply-box { width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:.75rem; resize:vertical;
            min-height:80px; margin-bottom:.5rem; font-family:inherit; font-size:.9rem; }
        .reply-box:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
        .compose-form .form-group { margin-bottom:1rem; }
        .compose-form label { display:block; font-weight:600; color:#334155; margin-bottom:.35rem; font-size:.9rem; }
        .compose-form input,.compose-form textarea { width:100%; padding:.65rem .75rem; border:1px solid #cbd5e1;
            border-radius:6px; font-size:.9rem; font-family:inherit; }
        .compose-form input:focus,.compose-form textarea:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
        .thread-msg { margin-bottom:1rem; padding:.85rem 1rem; border-radius:8px; max-width:85%; }
        .thread-msg.sent { background:#eff6ff; margin-left:auto; border-bottom-right-radius:2px; }
        .thread-msg.received { background:#f8fafc; border-bottom-left-radius:2px; }
        .thread-meta { font-size:.75rem; color:#94a3b8; margin-bottom:.25rem; }
        .thread-body { font-size:.9rem; color:#1e293b; line-height:1.5; }
        .btn-msg { padding:.5rem 1rem; border-radius:6px; cursor:pointer; font-size:.875rem; border:none; font-weight:500; }
        .btn-msg-primary { background:#6366f1; color:#fff; }
        .btn-msg-primary:hover { background:#4f46e5; }
        .btn-msg-danger { background:#fee2e2; color:#991b1b; }
        .btn-msg-danger:hover { background:#fecaca; }
        .btn-msg-outline { background:transparent; border:1px solid #cbd5e1; color:#475569; }
        .btn-msg-outline:hover { background:#f1f5f9; }
        .empty-state-msg { padding:3rem 1.5rem; text-align:center; color:#94a3b8; }
        .empty-state-msg .icon { font-size:2.5rem; margin-bottom:.5rem; }
        .empty-state-msg p { margin:.25rem 0; }
        .recipient-dropdown { position:absolute; z-index:100; background:#fff; border:1px solid #e2e8f0;
            border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,.1); max-height:200px; overflow-y:auto; width:100%; display:none; }
        .recipient-dropdown .item { padding:.6rem .75rem; cursor:pointer; font-size:.9rem; }
        .recipient-dropdown .item:hover { background:#f1f5f9; }
        .recipient-field { position:relative; }
        @media(max-width:768px) {
            .messages-container { grid-template-columns:1fr; }
            .msg-detail { display:none; }
            .msg-detail.show-mobile { display:flex; position:fixed; inset:0; z-index:50; }
        }
    </style>
</head>
<body class="member-body">

    <?php renderMemberSidebar('messages'); ?>

    <div class="member-content">
        <div class="messages-wrapper">
            <!-- Header -->
            <div class="member-page-header" style="flex-shrink:0; margin-bottom:1rem;">
                <div>
                    <h1>💬 Nachrichten <?php if ($unreadCount > 0): ?><span class="msg-badge"><?php echo $unreadCount; ?></span><?php endif; ?></h1>
                    <p class="member-page-subtitle">Deine Kommunikation im Netzwerk</p>
                </div>
                <a href="?view=compose" class="btn-msg btn-msg-primary">✏️ Neue Nachricht</a>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="margin-bottom:1rem;"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-error" style="margin-bottom:1rem;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="messages-container">
                <!-- Left: List -->
                <div class="msg-list">
                    <div class="msg-list-header">
                        <a href="?view=inbox" class="msg-tab <?php echo $view === 'inbox' ? 'active' : ''; ?>">
                            📥 Posteingang <?php if ($unreadCount > 0): ?><span class="msg-badge"><?php echo $unreadCount; ?></span><?php endif; ?>
                        </a>
                        <a href="?view=sent" class="msg-tab <?php echo $view === 'sent' ? 'active' : ''; ?>">📤 Gesendet</a>
                    </div>
                    <div class="msg-list-scroll">
                        <?php if (empty($conversations) && $view !== 'compose' && $view !== 'thread'): ?>
                            <div class="empty-state-msg">
                                <div class="icon">📭</div>
                                <p><strong>Keine Nachrichten</strong></p>
                                <p style="font-size:.85rem;">Starte eine Konversation über den Button oben.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($conversations as $conv):
                                $isUnread = isset($conv->is_read) && !$conv->is_read && $view === 'inbox';
                                $name   = htmlspecialchars($view === 'sent'
                                    ? ($conv->recipient_display_name ?? $conv->recipient_name ?? '')
                                    : ($conv->sender_display_name ?? $conv->sender_name ?? ''));
                                $initials = mb_strtoupper(mb_substr($name, 0, 2));
                                $date   = date('d.m. H:i', strtotime($conv->created_at));
                            ?>
                            <a href="?view=thread&id=<?php echo (int) $conv->id; ?>"
                               class="msg-item <?php echo $isUnread ? 'unread' : ''; ?> <?php echo (isset($activeMessage) && $activeMessage && (int) $activeMessage->id === (int) $conv->id) ? 'active' : ''; ?>">
                                <div class="msg-avatar"><?php echo $initials; ?></div>
                                <div class="msg-content">
                                    <div class="msg-top">
                                        <span class="msg-name"><?php echo $name; ?></span>
                                        <span class="msg-time"><?php echo $date; ?></span>
                                    </div>
                                    <div class="msg-subject"><?php echo htmlspecialchars($conv->subject ?: '(Kein Betreff)'); ?></div>
                                    <div class="msg-preview"><?php echo htmlspecialchars(mb_substr(strip_tags($conv->body), 0, 80)); ?></div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: Detail / Compose / Thread -->
                <div class="msg-detail <?php echo ($view === 'thread' || $view === 'compose') ? 'show-mobile' : ''; ?>">
                    <?php if ($view === 'compose'): ?>
                        <div class="msg-detail-header">
                            <h3 style="margin:0; font-size:1rem;">✏️ Neue Nachricht</h3>
                            <a href="?view=inbox" class="btn-msg btn-msg-outline" style="font-size:.8rem;">← Zurück</a>
                        </div>
                        <div class="msg-detail-body">
                            <form method="POST" class="compose-form">
                                <input type="hidden" name="msg_action" value="send">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="recipient_id" id="recipient_id" value="">
                                <div class="form-group recipient-field">
                                    <label for="recipient_search">Empfänger <span style="color:#ef4444;">*</span></label>
                                    <input type="text" id="recipient_search" placeholder="Benutzername oder Name eingeben..." autocomplete="off" required>
                                    <div class="recipient-dropdown" id="recipientDropdown"></div>
                                </div>
                                <div class="form-group">
                                    <label for="subject">Betreff</label>
                                    <input type="text" id="subject" name="subject" placeholder="Betreff eingeben..." maxlength="255">
                                </div>
                                <div class="form-group">
                                    <label for="body">Nachricht <span style="color:#ef4444;">*</span></label>
                                    <textarea name="body" id="body" rows="8" placeholder="Deine Nachricht..." required style="min-height:160px;"></textarea>
                                </div>
                                <div style="display:flex; justify-content:flex-end; gap:.5rem;">
                                    <a href="?view=inbox" class="btn-msg btn-msg-outline">Abbrechen</a>
                                    <button type="submit" class="btn-msg btn-msg-primary">📨 Senden</button>
                                </div>
                            </form>
                        </div>

                    <?php elseif ($view === 'thread' && !empty($threadMessages)): ?>
                        <?php $root = $threadMessages[0]; ?>
                        <div class="msg-detail-header">
                            <div>
                                <h3 style="margin:0; font-size:1rem;"><?php echo htmlspecialchars($root->subject ?: '(Kein Betreff)'); ?></h3>
                                <span style="font-size:.8rem; color:#64748b;"><?php echo count($threadMessages); ?> Nachricht<?php echo count($threadMessages) > 1 ? 'en' : ''; ?></span>
                            </div>
                            <div style="display:flex; gap:.5rem; align-items:center;">
                                <a href="?view=inbox" class="btn-msg btn-msg-outline" style="font-size:.8rem;">← Zurück</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="msg_action" value="delete">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="message_id" value="<?php echo (int) $root->id; ?>">
                                    <button type="submit" class="btn-msg btn-msg-danger" style="font-size:.8rem;">🗑️</button>
                                </form>
                            </div>
                        </div>
                        <div class="msg-detail-body">
                            <?php foreach ($threadMessages as $tmsg):
                                $isSent = (int) $tmsg->sender_id === (int) $user->id;
                            ?>
                            <div class="thread-msg <?php echo $isSent ? 'sent' : 'received'; ?>">
                                <div class="thread-meta">
                                    <strong><?php echo htmlspecialchars($tmsg->sender_display_name ?? $tmsg->sender_name); ?></strong>
                                    · <?php echo date('d.m.Y H:i', strtotime($tmsg->created_at)); ?>
                                </div>
                                <div class="thread-body"><?php echo nl2br(htmlspecialchars($tmsg->body)); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="msg-detail-footer">
                            <form method="POST">
                                <input type="hidden" name="msg_action" value="send">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="parent_id" value="<?php echo (int) ($root->parent_id ?: $root->id); ?>">
                                <input type="hidden" name="subject" value="Re: <?php echo htmlspecialchars($root->subject ?? ''); ?>">
                                <?php $replyTo = (int) $root->sender_id === (int) $user->id ? (int) $root->recipient_id : (int) $root->sender_id; ?>
                                <input type="hidden" name="recipient_id" value="<?php echo $replyTo; ?>">
                                <textarea class="reply-box" name="body" placeholder="Antwort schreiben..." required></textarea>
                                <div style="display:flex; justify-content:flex-end;">
                                    <button type="submit" class="btn-msg btn-msg-primary">Antworten ➤</button>
                                </div>
                            </form>
                        </div>

                    <?php elseif ($view === 'thread' && empty($threadMessages)): ?>
                        <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#94a3b8;">
                            Nachricht nicht gefunden oder kein Zugriff.
                        </div>

                    <?php else: ?>
                        <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#94a3b8; flex-direction:column; gap:.5rem;">
                            <span style="font-size:2rem;">💬</span>
                            <span>Wähle eine Nachricht aus oder erstelle eine neue.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const searchInput = document.getElementById('recipient_search');
        const dropdown    = document.getElementById('recipientDropdown');
        const hiddenInput = document.getElementById('recipient_id');
        if (!searchInput) return;
        let debounce = null;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounce);
            const q = this.value.trim();
            if (q.length < 2) { dropdown.style.display = 'none'; return; }
            debounce = setTimeout(async () => {
                try {
                    const res = await fetch('?ajax_search_recipients=1&q=' + encodeURIComponent(q));
                    const users = await res.json();
                    if (!users.length) { dropdown.style.display = 'none'; return; }
                    dropdown.innerHTML = users.map(u => {
                        const d = document.createElement('div'); d.textContent = u.display_name;
                        const safe = d.innerHTML;
                        const d2 = document.createElement('div'); d2.textContent = u.username;
                        const safeU = d2.innerHTML;
                        return '<div class="item" data-id="' + u.id + '" data-name="' + safe + '">'
                            + safe + ' <span style="color:#94a3b8;">@' + safeU + '</span></div>';
                    }).join('');
                    dropdown.style.display = 'block';
                } catch(e) { dropdown.style.display = 'none'; }
            }, 250);
        });
        dropdown.addEventListener('click', function(e) {
            const item = e.target.closest('.item');
            if (!item) return;
            hiddenInput.value = item.dataset.id;
            searchInput.value = item.dataset.name;
            dropdown.style.display = 'none';
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.recipient-field')) dropdown.style.display = 'none';
        });
    })();
    </script>

</body>
</html>
