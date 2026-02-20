<?php
/**
 * Member Favorites View
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
    <title>Favoriten - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/member.css">
    <?php renderMemberSidebarStyles(); ?>
    <style>
        /* Height adjustment similar to messages */
        .favorites-wrapper {
            height: calc(100vh - 4rem - 40px); /* viewport - header - padding */
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .favorites-scroll {
            flex: 1;
            overflow-y: auto;
            padding-bottom: 2rem;
        }

        .fav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .fav-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            position: relative;
        }
        .fav-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-color: #cbd5e1;
        }

        .fav-icon-box {
            width: 48px; height: 48px;
            background: #f1f5f9;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }
        .fav-type {
            position: absolute;
            top: 1.5rem; right: 1.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            font-weight: 500;
        }

        .fav-content h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            color: #1e293b;
            font-weight: 600;
        }
        .fav-content p {
            margin: 0;
            font-size: 0.9rem;
            color: #64748b;
            line-height: 1.5;
        }
        
        .fav-actions {
            margin-top: auto;
            display: flex;
            gap: 0.5rem;
        }
        .btn-fav-open {
            flex: 1;
            text-align: center;
            padding: 0.5rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            color: #475569;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.15s;
        }
        .btn-fav-open:hover {
            background: #fff;
            border-color: #6366f1;
            color: #6366f1;
        }
        .btn-fav-remove {
            width: 36px;
            display: flex; align-items: center; justify-content: center;
            border: 1px solid #e2e8f0;
            background: #fff;
            border-radius: 6px;
            color: #ef4444;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-fav-remove:hover {
            background: #fef2f2;
            border-color: #fecaca;
        }

        /* Empty state */
        .fav-empty {
            text-align: center;
            padding: 4rem 2rem;
            background: #fff;
            border-radius: 8px;
            border: 1px dashed #cbd5e1;
            color: #64748b;
        }
        .fav-empty-icon { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }
    </style>
</head>
<body class="member-body">
    
    <?php renderMemberSidebar('favorites'); ?>
    
    <div class="member-content">
        <div class="favorites-wrapper">
            <!-- Header (Fixed height part) -->
            <div class="member-page-header" style="flex-shrink: 0;">
                <div>
                    <h1>Meine Favoriten</h1>
                    <p class="member-page-subtitle">Gespeicherte Inhalte und Tools</p>
                </div>
                <div class="member-header-actions">
                    <div class="search-box" style="position:relative;">
                        <input type="text" placeholder="Favoriten suchen..." 
                               style="padding:0.5rem 1rem 0.5rem 2rem; border:1px solid #e2e8f0; border-radius:6px; font-size:0.9rem; width:250px;">
                        <span style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); opacity:0.5;">🔍</span>
                    </div>
                </div>
            </div>

            <!-- Scrollable Content -->
            <div class="favorites-scroll">
                <?php if (empty($favorites)): ?>
                    <div class="fav-empty">
                        <div class="fav-empty-icon">⭐</div>
                        <h3>Noch keine Favoriten</h3>
                        <p>Markiere Inhalte mit dem Stern-Symbol, um sie hier wiederzufinden.</p>
                        <a href="/member" class="member-btn member-btn-primary" style="margin-top:1rem; display:inline-block;">Zum Dashboard</a>
                    </div>
                <?php else: ?>
                    <div class="fav-grid">
                        <?php foreach($favorites as $fav): ?>
                        <div class="fav-card">
                            <span class="fav-type"><?php echo htmlspecialchars($fav['type']); ?></span>
                            <div class="fav-icon-box"><?php echo $fav['icon']; ?></div>
                            <div class="fav-content">
                                <h3><?php echo htmlspecialchars($fav['title']); ?></h3>
                                <p><?php echo htmlspecialchars($fav['desc']); ?></p>
                            </div>
                            <div class="fav-actions">
                                <a href="<?php echo htmlspecialchars($fav['link']); ?>" class="btn-fav-open">Öffnen</a>
                                <button class="btn-fav-remove" title="Aus Favoriten entfernen">✖</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Dummy Items for visual filling if few items -->
                        <?php if (count($favorites) < 8): ?>
                            <!-- Dummy 1 -->
                            <div class="fav-card" style="opacity:0.6; border-style:dashed;">
                                <span class="fav-type">Beispiel</span>
                                <div class="fav-icon-box">💡</div>
                                <div class="fav-content">
                                    <h3>Platzhalter Favorit</h3>
                                    <p>Hier könnten weitere gespeicherte Elemente erscheinen.</p>
                                </div>
                                <div class="fav-actions">
                                    <span class="btn-fav-open" style="cursor:not-allowed;">Vorschau</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>
