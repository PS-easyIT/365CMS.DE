<?php
/**
 * Member Messages View
 *
 * @package CMSv2\Member\Views
 */

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
        /* Main Layout Wrapper to fill height */
        .messages-wrapper {
            height: calc(100vh - 4rem); /* viewport - padding */
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .messages-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 1.5rem;
            flex: 1; /* Take remaining height */
            min-height: 0; /* Important for scroll */
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        /* Sidebar List */
        .msg-list {
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            background: #f8fafc;
        }
        .msg-list-header {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
        }
        .msg-list-scroll {
            overflow-y: auto;
            flex: 1;
        }
        .msg-item {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: background 0.15s;
            display: flex;
            gap: 0.75rem;
        }
        .msg-item:hover {
            background: #f1f5f9;
        }
        .msg-item.active {
            background: #fff;
            border-left: 3px solid #6366f1;
        }
        .msg-item.unread {
            background: #eff6ff;
        }
        .msg-avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex; align-items: center; justify-content: center;
            color: #64748b; font-weight: 600;
            flex-shrink: 0;
        }
        .msg-content {
            flex: 1;
            min-width: 0;
        }
        .msg-top {
            display: flex; justify-content: space-between;
            margin-bottom: 0.25rem;
        }
        .msg-name { font-weight: 600; font-size: 0.9rem; color: #1e293b; }
        .msg-time { font-size: 0.75rem; color: #94a3b8; }
        .msg-subject { font-size: 0.85rem; color: #334155; margin-bottom: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .msg-preview { font-size: 0.8rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* Detail View */
        .msg-detail {
            display: flex;
            flex-direction: column;
            background: #fff;
        }
        .msg-detail-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .msg-detail-body {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            color: #334155;
            line-height: 1.6;
        }
        .msg-detail-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .reply-box {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 0.75rem;
            resize: vertical;
            min-height: 80px;
            margin-bottom: 0.5rem;
            font-family: inherit;
        }

        .btn-primary {
            background-color: #6366f1;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .messages-container {
                grid-template-columns: 1fr;
            }
            .msg-detail { display: none; } /* Hide detail on mobile for fake view */
            .msg-item.active .msg-detail { display: block; position: fixed; inset: 0; z-index: 50; }
        }
    </style>
</head>
<body class="member-body">
    
    <?php renderMemberSidebar('messages'); ?>
    
    <div class="member-content">
        <div class="messages-wrapper">
            <!-- Header -->
            <div class="member-page-header" style="flex-shrink: 0; margin-bottom: 1.5rem;">
                <div>
                    <h1>Nachrichten</h1>
                    <p class="member-page-subtitle">Deine Kommunikation im Netzwerk</p>
                </div>
                <button class="member-btn member-btn-secondary">
                    ✏️ Neue Nachricht
                </button>
            </div>

            <div class="messages-container">
            <!-- Left: List -->
            <div class="msg-list">
                <div class="msg-list-header">
                    <span style="font-weight:600; color:#475569;">Posteingang</span>
                    <span style="font-size:0.8rem; color:#94a3b8;">Filter ▼</span>
                </div>
                <div class="msg-list-scroll">
                    <?php if (empty($conversations)): ?>
                        <div style="padding:2rem; text-align:center; color:#94a3b8;">Keine Nachrichten</div>
                    <?php else: ?>
                        <?php foreach($conversations as $conv): ?>
                        <div class="msg-item <?php echo $conv['unread'] ? 'unread' : ''; ?>">
                            <div class="msg-avatar"><?php echo $conv['avatar']; ?></div>
                            <div class="msg-content">
                                <div class="msg-top">
                                    <span class="msg-name"><?php echo htmlspecialchars($conv['user']); ?></span>
                                    <span class="msg-time"><?php echo htmlspecialchars($conv['date']); ?></span>
                                </div>
                                <div class="msg-subject"><?php echo htmlspecialchars($conv['subject']); ?></div>
                                <div class="msg-preview"><?php echo htmlspecialchars($conv['preview']); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Detail (Placeholder for first item) -->
            <div class="msg-detail">
                <?php if (!empty($conversations)): $active = $conversations[0]; ?>
                <div class="msg-detail-header">
                    <div>
                        <h3 style="margin:0; font-size:1.1rem;"><?php echo htmlspecialchars($active['subject']); ?></h3>
                        <span style="font-size:0.85rem; color:#64748b;">
                            Von <strong><?php echo htmlspecialchars($active['user']); ?></strong> an <strong>Mir</strong>
                        </span>
                    </div>
                    <div style="display:flex; gap:0.5rem;">
                        <button style="border:none; background:none; cursor:pointer;" title="Archivieren">📦</button>
                        <button style="border:none; background:none; cursor:pointer;" title="Löschen">🗑️</button>
                    </div>
                </div>
                <div class="msg-detail-body">
                    <p>Hallo <?php echo htmlspecialchars($user->username); ?>,</p>
                    <p>Vielen Dank für deine Registrierung im neuen IT Expert Hub. Wir freuen uns, dich an Bord zu haben.</p>
                    <p>Hier sind einige erste Schritte, die du unternehmen kannst:</p>
                    <ul>
                        <li>Vervollständige dein Profil</li>
                        <li>Lade ein Profilbild hoch</li>
                        <li>Verbinde dich mit anderen Mitgliedern</li>
                    </ul>
                    <p>Viele Grüße,<br>Das Support Team</p>
                </div>
                <div class="msg-detail-footer">
                    <textarea class="reply-box" placeholder="Antwort schreiben..."></textarea>
                    <div style="display:flex; justify-content:flex-end;">
                        <button class="btn-primary">Senden ➤</button>
                    </div>
                </div>
                <?php else: ?>
                    <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#94a3b8;">
                        Wähle eine Nachricht aus
                    </div>
                <?php endif; ?>
            </div> <!-- Close Container -->
        </div> <!-- Close Wrapper -->
    </div> <!-- Close Content -->

</body>
</html>
