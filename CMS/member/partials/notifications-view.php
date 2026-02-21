<?php
/**
 * Member Notifications View
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
    <title>Benachrichtigungen - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/member.css">
    <?php renderMemberSidebarStyles(); ?>
</head>
<body class="member-body">
    
    <?php renderMemberSidebar('notifications'); ?>
    
    <!-- Main Content -->
    <div class="member-content">
        
        <!-- Page Header -->
        <div class="member-page-header">
            <div>
                <h1>Benachrichtigungen</h1>
                <p class="member-page-subtitle">Verwalte, wie du informiert werden möchtest</p>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="member-alert member-alert-success">
                <span class="alert-icon">✓</span>
                <span><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="member-alert member-alert-error">
                <span class="alert-icon">✕</span>
                <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Recent Notifications -->
        <?php if (!empty($recentNotifications)): ?>
            <div class="member-card">
                <div class="member-card-header">
                    <h3>Letzte Benachrichtigungen</h3>
                    <span class="member-badge member-badge-primary">
                        <?php echo count($recentNotifications); ?> neue
                    </span>
                </div>
                
                <div class="member-card-body">
                    <div class="notifications-list">
                        <?php foreach ($recentNotifications as $notification): ?>
                            <div class="notification-item <?php echo $notification['read'] ? 'read' : 'unread'; ?>">
                                <div class="notification-icon" style="background: <?php echo htmlspecialchars($notification['color'] ?? '#667eea'); ?>">
                                    <?php echo $notification['icon']; ?>
                                </div>
                                <div class="notification-content">
                                    <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                    <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <span class="notification-time"><?php echo htmlspecialchars($notification['time_ago'] ?? ''); ?></span>
                                </div>
                                <?php if (!$notification['read']): ?>
                                    <span class="notification-unread-badge"></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="member-card-footer">
                        <button class="member-btn member-btn-secondary" onclick="markAllAsRead()">
                            Alle als gelesen markieren
                        </button>
                        <a href="/member/notifications/all" class="member-btn member-btn-outline">
                            Alle anzeigen
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Notification Settings -->
        <div class="member-card">
            <div class="member-card-header">
                <h3>Benachrichtigungseinstellungen</h3>
                <span class="member-card-icon">⚙️</span>
            </div>
            
            <div class="member-card-body">
                <form method="POST" class="member-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <!-- Email Notifications -->
                    <div class="member-settings-section">
                        <h4 class="settings-section-title">
                            <span class="section-icon">📧</span>
                            E-Mail Benachrichtigungen
                        </h4>
                        
                        <div class="member-toggle-list">
                            <div class="member-toggle-item">
                                <div class="toggle-info">
                                    <strong>E-Mail Benachrichtigungen allgemein</strong>
                                    <p>Erhalte wichtige Updates per E-Mail</p>
                                </div>
                                <label class="member-toggle">
                                    <input 
                                        type="checkbox" 
                                        name="email_notifications"
                                        <?php echo ($preferences['email_notifications'] ?? true) ? 'checked' : ''; ?>
                                    >
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="member-toggle-item">
                                <div class="toggle-info">
                                    <strong>Marketing & Newsletter</strong>
                                    <p>Produktneuheiten und Sonderangebote</p>
                                </div>
                                <label class="member-toggle">
                                    <input 
                                        type="checkbox" 
                                        name="email_marketing"
                                        <?php echo ($preferences['email_marketing'] ?? false) ? 'checked' : ''; ?>
                                    >
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="member-toggle-item">
                                <div class="toggle-info">
                                    <strong>System-Updates</strong>
                                    <p>Informationen zu Wartungen und Updates</p>
                                </div>
                                <label class="member-toggle">
                                    <input 
                                        type="checkbox" 
                                        name="email_updates"
                                        <?php echo ($preferences['email_updates'] ?? true) ? 'checked' : ''; ?>
                                    >
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="member-toggle-item">
                                <div class="toggle-info">
                                    <strong>Sicherheitswarnungen</strong>
                                    <p>Wichtige Sicherheitshinweise (empfohlen)</p>
                                </div>
                                <label class="member-toggle">
                                    <input 
                                        type="checkbox" 
                                        name="email_security"
                                        <?php echo ($preferences['email_security'] ?? true) ? 'checked' : ''; ?>
                                    >
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Browser & App Notifications -->
                    <div class="member-settings-section">
                        <h4 class="settings-section-title">
                            <span class="section-icon">💻</span>
                            Browser & App Benachrichtigungen
                        </h4>
                        
                        <div class="member-toggle-list">
                            <div class="member-toggle-item">
                                <div class="toggle-info">
                                    <strong>Browser Push-Benachrichtigungen</strong>
                                    <p>Echtzeitbenachrichtigungen im Browser</p>
                                </div>
                                <label class="member-toggle">
                                    <input 
                                        type="checkbox" 
                                        name="browser_notifications"
                                        <?php echo ($preferences['browser_notifications'] ?? false) ? 'checked' : ''; ?>
                                    >
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="member-toggle-item">
                                <div class="toggle-info">
                                    <strong>Desktop-Benachrichtigungen</strong>
                                    <p>Systembenachrichtigungen auf deinem Desktop</p>
                                </div>
                                <label class="member-toggle">
                                    <input 
                                        type="checkbox" 
                                        name="desktop_notifications"
                                        <?php echo ($preferences['desktop_notifications'] ?? false) ? 'checked' : ''; ?>
                                    >
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="member-toggle-item">
                                <div class="toggle-info">
                                    <strong>Mobile Push-Benachrichtigungen</strong>
                                    <p>Benachrichtigungen auf mobilen Geräten</p>
                                </div>
                                <label class="member-toggle">
                                    <input 
                                        type="checkbox" 
                                        name="mobile_notifications"
                                        <?php echo ($preferences['mobile_notifications'] ?? false) ? 'checked' : ''; ?>
                                    >
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content Preferences -->
                    <div class="member-settings-section">
                        <h4 class="settings-section-title">
                            <span class="section-icon">🎯</span>
                            Inhaltspräferenzen
                        </h4>
                        
                        <div class="member-toggle-list">
                            <div class="member-toggle-item">
                                <div class="toggle-info">
                                    <strong>Neue Features & Funktionen</strong>
                                    <p>Informationen über neue Plattform-Features</p>
                                </div>
                                <label class="member-toggle">
                                    <input 
                                        type="checkbox" 
                                        name="notify_new_features"
                                        <?php echo ($preferences['notify_new_features'] ?? true) ? 'checked' : ''; ?>
                                    >
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="member-toggle-item">
                                <div class="toggle-info">
                                    <strong>Aktionen & Promotionen</strong>
                                    <p>Exklusive Angebote und Rabatte</p>
                                </div>
                                <label class="member-toggle">
                                    <input 
                                        type="checkbox" 
                                        name="notify_promotions"
                                        <?php echo ($preferences['notify_promotions'] ?? false) ? 'checked' : ''; ?>
                                    >
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notification Frequency -->
                    <div class="member-settings-section">
                        <h4 class="settings-section-title">
                            <span class="section-icon">⏱️</span>
                            Benachrichtigungshäufigkeit
                        </h4>
                        
                        <div class="member-form-group">
                            <label for="notification_frequency" class="member-form-label">
                                Wie oft möchtest du Benachrichtigungen erhalten?
                            </label>
                            <select 
                                id="notification_frequency" 
                                name="notification_frequency" 
                                class="member-form-select"
                            >
                                <option value="immediate" <?php echo ($preferences['notification_frequency'] ?? 'immediate') === 'immediate' ? 'selected' : ''; ?>>
                                    Sofort
                                </option>
                                <option value="hourly" <?php echo ($preferences['notification_frequency'] ?? '') === 'hourly' ? 'selected' : ''; ?>>
                                    Stündlich zusammengefasst
                                </option>
                                <option value="daily" <?php echo ($preferences['notification_frequency'] ?? '') === 'daily' ? 'selected' : ''; ?>>
                                    Tägliche Zusammenfassung
                                </option>
                                <option value="weekly" <?php echo ($preferences['notification_frequency'] ?? '') === 'weekly' ? 'selected' : ''; ?>>
                                    Wöchentliche Zusammenfassung
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Plugin Notification Settings (Hook) -->
                    <?php 
                    $pluginSettings = \CMS\Hooks::applyFilters('member_notification_settings_sections', []);
                    foreach ($pluginSettings as $setting): 
                    ?>
                        <div class="member-settings-section">
                            <h4 class="settings-section-title">
                                <span class="section-icon"><?php echo $setting['icon'] ?? '🔌'; ?></span>
                                <?php echo htmlspecialchars($setting['title']); ?>
                            </h4>
                            <?php
                            if (isset($setting['callback']) && is_callable($setting['callback'])) {
                                call_user_func($setting['callback'], $preferences, $user);
                            }
                            ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Submit -->
                    <div class="member-form-actions">
                        <button type="submit" class="member-btn member-btn-primary">
                            💾 Einstellungen speichern
                        </button>
                        <button type="button" class="member-btn member-btn-outline" onclick="testNotification()">
                            🔔 Test-Benachrichtigung senden
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
    
    <script>
        function markAllAsRead() {
            // AJAX call to mark all as read
            console.log('Marking all notifications as read');
        }
        
        function testNotification() {
            // Request notification permission if needed
            if ('Notification' in window) {
                if (Notification.permission === 'granted') {
                    new Notification('Test-Benachrichtigung', {
                        body: 'Dies ist eine Test-Benachrichtigung von ' + <?php echo json_encode(SITE_NAME); ?>,
                        icon: '/assets/images/logo.png'
                    });
                } else if (Notification.permission !== 'denied') {
                    Notification.requestPermission().then(function(permission) {
                        if (permission === 'granted') {
                            new Notification('Test-Benachrichtigung', {
                                body: 'Dies ist eine Test-Benachrichtigung von ' + <?php echo json_encode(SITE_NAME); ?>,
                                icon: '/assets/images/logo.png'
                            });
                        }
                    });
                } else {
                    alert('Browser-Benachrichtigungen sind blockiert. Bitte erlaube Benachrichtigungen in deinen Browser-Einstellungen.');
                }
            } else {
                alert('Dein Browser unterstützt keine Push-Benachrichtigungen.');
            }
        }
        
        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.member-alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
    
</body>
</html>
