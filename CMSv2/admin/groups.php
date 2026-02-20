<?php
/**
 * Groups Management Admin Page
 * 
 * @package CMSv2\Admin
 */

declare(strict_types=1);

// Load configuration first
require_once dirname(__DIR__) . '/config.php';

// Load autoloader
require_once CORE_PATH . 'autoload.php';

// Load helper functions
require_once ABSPATH . 'includes/functions.php';

use CMS\Auth;
use CMS\Security;
use CMS\Database;
use CMS\SubscriptionManager;

if (!defined('ABSPATH')) {
    exit;
}

// Security check
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$auth = Auth::instance();
$db = Database::instance();
$subscriptionManager = SubscriptionManager::instance();

// Handle Actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    
    if (!Security::instance()->verifyToken($csrf, 'group_management')) {
        $error = 'Sicherheitsüberprüfung fehlgeschlagen.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create_group') {
            $groupData = [
                'name' => sanitize_text($_POST['name'] ?? ''),
                'slug' => sanitize_text($_POST['slug'] ?? ''),
                'description' => strip_tags(trim($_POST['description'] ?? '')),
                'plan_id' => (int)($_POST['plan_id'] ?? 0) ?: null,
                'is_active' => 1
            ];
            
            if ($db->insert('user_groups', $groupData)) {
                $message = 'Gruppe erfolgreich erstellt.';
            } else {
                $error = 'Fehler beim Erstellen der Gruppe.';
            }
        } elseif ($action === 'add_member') {
            $groupId = (int)($_POST['group_id'] ?? 0);
            $userId = (int)($_POST['user_id'] ?? 0);
            
            if ($groupId && $userId) {
                try {
                    $db->insert('user_group_members', [
                        'user_id' => $userId,
                        'group_id' => $groupId
                    ]);
                    $message = 'Benutzer zur Gruppe hinzugefügt.';
                } catch (\Exception $e) {
                    $error = 'Benutzer ist bereits in dieser Gruppe.';
                }
            }
        } elseif ($action === 'remove_member') {
            $memberId = (int)($_POST['member_id'] ?? 0);
            
            if ($memberId > 0) {
                // Prepared statement – schützt vor SQL-Injection
                $db->execute(
                    "DELETE FROM {$db->getPrefix()}user_group_members WHERE id = ?",
                    [$memberId]
                );
                $message = 'Benutzer aus Gruppe entfernt.';
            }
        }
    }
}

// Get Data
$groups = $db->query("
    SELECT g.*, sp.name as plan_name, COUNT(DISTINCT ugm.user_id) as member_count
    FROM {$db->getPrefix()}user_groups g
    LEFT JOIN {$db->getPrefix()}subscription_plans sp ON g.plan_id = sp.id
    LEFT JOIN {$db->getPrefix()}user_group_members ugm ON g.id = ugm.group_id
    GROUP BY g.id
    ORDER BY g.created_at DESC
")->fetchAll();

$plans = $subscriptionManager->getAllPlans();
$users = $db->query("SELECT id, username, email FROM {$db->getPrefix()}users ORDER BY username")->fetchAll();

// Zentralen CSRF-Token generieren
$csrfToken = Security::instance()->generateToken('group_management');

// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gruppen-Verwaltung - CMS Admin</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        .groups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .group-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s;
        }
        
        .group-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }
        
        .group-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .group-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        
        .member-count {
            background: #3b82f6;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .group-info {
            margin-bottom: 1rem;
        }
        
        .group-info p {
            color: #64748b;
            font-size: 0.9rem;
            margin: 0.5rem 0;
        }
        
        .group-plan {
            background: #dcfce7;
            color: #166534;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
            margin: 0.5rem 0;
        }
        
        .group-actions {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
            display: flex;
            gap: 0.5rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #10b981;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
</head>
<body class="admin-body">
    <?php renderAdminSidebar('groups'); ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <h1>👨‍👩‍👧‍👦 Gruppen-Verwaltung</h1>
            <button onclick="document.getElementById('create-group-modal').classList.add('active')" class="btn btn-primary">
                Neue Gruppe erstellen
            </button>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <p style="color: #64748b; margin-bottom: 2rem;">
            Organisieren Sie Benutzer in Gruppen und weisen Sie Gruppen Abo-Pakete zu. 
            Alle Mitglieder einer Gruppe erhalten automatisch Zugriff auf das zugewiesene Abo.
        </p>
        
        <?php if (empty($groups)): ?>
            <div class="empty-state">
                <p>Noch keine Gruppen vorhanden.</p>
                <button onclick="document.getElementById('create-group-modal').classList.add('active')" class="btn btn-primary">
                    Erste Gruppe erstellen
                </button>
            </div>
        <?php else: ?>
            <div class="groups-grid">
                <?php foreach ($groups as $group): ?>
                    <div class="group-card">
                        <div class="group-header">
                            <h3 class="group-name"><?php echo htmlspecialchars($group->name); ?></h3>
                            <span class="member-count">
                                <?php echo $group->member_count; ?> Mitglieder
                            </span>
                        </div>
                        
                        <div class="group-info">
                            <?php if ($group->description): ?>
                                <p><?php echo htmlspecialchars($group->description); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($group->plan_name): ?>
                                <span class="group-plan">
                                    📦 <?php echo htmlspecialchars($group->plan_name); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #64748b; font-size: 0.9rem;">
                                    ⚠️ Kein Abo-Paket zugewiesen
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="group-actions">
                            <button onclick="showMembers(<?php echo $group->id; ?>)" class="btn btn-sm">
                                Mitglieder verwalten
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Create Group Modal -->
    <div id="create-group-modal" class="modal">
        <div class="modal-content">
            <h2>Neue Gruppe erstellen</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create_group">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div class="form-group">
                    <label>Gruppen-Name *</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Slug *</label>
                    <input type="text" name="slug" required>
                    <small>Eindeutiger Bezeichner (z.B. premium-members)</small>
                </div>
                
                <div class="form-group">
                    <label>Beschreibung</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Abo-Paket</label>
                    <select name="plan_id">
                        <option value="">-- Kein Paket --</option>
                        <?php foreach ($plans as $plan): ?>
                            <option value="<?php echo $plan->id; ?>">
                                <?php echo htmlspecialchars($plan->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Gruppe erstellen</button>
                    <button type="button" class="btn" onclick="document.getElementById('create-group-modal').classList.remove('active')">
                        Abbrechen
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Members Modal -->
    <div id="members-modal" class="modal">
        <div class="modal-content">
            <h2>Mitglieder verwalten</h2>
            <div id="members-content">
                <!-- Loaded via JS/AJAX -->
            </div>
            <button type="button" class="btn" onclick="document.getElementById('members-modal').classList.remove('active')">
                Schließen
            </button>
        </div>
    </div>
    
    <script>
        // Modal handling
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
        
        function showMembers(groupId) {
            // Simplified - would load via AJAX in production
            document.getElementById('members-modal').classList.add('active');
        }
    </script>
</body>
</html>
