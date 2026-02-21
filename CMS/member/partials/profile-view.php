<?php
/**
 * Member Profile View
 * 
 * Variables provided by profile.php controller:
 * - $csrfToken : string - CSRF token for the profile form
 * - $userMeta  : array  - Additional user meta (first_name, last_name, bio, phone, website)
 * - $user      : object - Current user (injected by MemberController::render)
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
    <title>Mein Profil - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/member.css">
    <?php renderMemberSidebarStyles(); ?>
</head>
<body class="member-body">
    
    <?php renderMemberSidebar('profile'); ?>
    
    <!-- Main Content -->
    <div class="member-content">
        
        <!-- Page Header -->
        <div class="member-page-header">
            <div>
                <h1>Mein Profil</h1>
                <p class="member-page-subtitle">Verwalte deine persönlichen Informationen</p>
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
        
        <!-- Profile Form -->
        <div class="member-card">
            <div class="member-card-header">
                <h3>Persönliche Informationen</h3>
                <span class="member-card-icon">👤</span>
            </div>
            
            <div class="member-card-body">
                <form method="POST" class="member-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <!-- Profile Avatar Preview -->
                    <div class="member-form-group member-avatar-section">
                        <div class="member-avatar-large">
                            <?php echo strtoupper(substr($user->username, 0, 2)); ?>
                        </div>
                        <div class="member-avatar-info">
                            <h4><?php echo htmlspecialchars($user->username); ?></h4>
                            <p>Mitglied seit <?php echo date('d.m.Y', strtotime($user->created_at)); ?></p>
                        </div>
                    </div>
                    
                    <!-- Username -->
                    <div class="member-form-group">
                        <label for="username" class="member-form-label">
                            Benutzername <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="member-form-input"
                            value="<?php echo htmlspecialchars($user->username); ?>"
                            required
                        >
                        <span class="member-form-help">Dein öffentlicher Benutzername</span>
                    </div>
                    
                    <!-- Email -->
                    <div class="member-form-group">
                        <label for="email" class="member-form-label">
                            E-Mail-Adresse <span class="required">*</span>
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="member-form-input"
                            value="<?php echo htmlspecialchars($user->email); ?>"
                            required
                        >
                        <span class="member-form-help">Deine E-Mail für Benachrichtigungen</span>
                    </div>
                    
                    <!-- First Name & Last Name (2 columns) -->
                    <div class="member-form-row">
                        <div class="member-form-group">
                            <label for="first_name" class="member-form-label">Vorname</label>
                            <input 
                                type="text" 
                                id="first_name" 
                                name="first_name" 
                                class="member-form-input"
                                value="<?php echo htmlspecialchars($userMeta['first_name'] ?? ''); ?>"
                            >
                        </div>
                        
                        <div class="member-form-group">
                            <label for="last_name" class="member-form-label">Nachname</label>
                            <input 
                                type="text" 
                                id="last_name" 
                                name="last_name" 
                                class="member-form-input"
                                value="<?php echo htmlspecialchars($userMeta['last_name'] ?? ''); ?>"
                            >
                        </div>
                    </div>
                    
                    <!-- Bio -->
                    <div class="member-form-group">
                        <label class="member-form-label">Biografie</label>
                        <?php
                        if (class_exists('\CMS\Services\EditorService')) {
                            echo \CMS\Services\EditorService::getInstance()->render(
                                'bio',
                                $userMeta['bio'] ?? '',
                                [
                                    'height'     => 150,
                                    'buttonList' => [
                                        ['bold', 'italic', 'underline', 'removeFormat'],
                                        ['link'],
                                    ],
                                ]
                            );
                        } else {
                            ?>
                            <textarea id="bio" name="bio" class="member-form-textarea" rows="4"
                                placeholder="Erzähle etwas über dich..."><?php echo htmlspecialchars($userMeta['bio'] ?? ''); ?></textarea>
                            <?php
                        }
                        ?>
                        <span class="member-form-help">Kurze Beschreibung über dich</span>
                    </div>
                    
                    <!-- Phone -->
                    <div class="member-form-group">
                        <label for="phone" class="member-form-label">Telefon</label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            class="member-form-input"
                            value="<?php echo htmlspecialchars($userMeta['phone'] ?? ''); ?>"
                            placeholder="+49 123 456789"
                        >
                    </div>
                    
                    <!-- Website -->
                    <div class="member-form-group">
                        <label for="website" class="member-form-label">Webseite</label>
                        <input 
                            type="url" 
                            id="website" 
                            name="website" 
                            class="member-form-input"
                            value="<?php echo htmlspecialchars($userMeta['website'] ?? ''); ?>"
                            placeholder="https://example.com"
                        >
                    </div>
                    
                    <!-- Submit -->
                    <div class="member-form-actions">
                        <button type="submit" class="member-btn member-btn-primary">
                            💾 Änderungen speichern
                        </button>
                        <a href="/member" class="member-btn member-btn-secondary">
                            Abbrechen
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Account Info -->
        <div class="member-card">
            <div class="member-card-header">
                <h3>Account-Informationen</h3>
                <span class="member-card-icon">ℹ️</span>
            </div>
            <div class="member-card-body">
                <div class="member-info-grid">
                    <div class="member-info-item">
                        <span class="member-info-label">Benutzer-ID:</span>
                        <span class="member-info-value">#<?php echo $user->id; ?></span>
                    </div>
                    <div class="member-info-item">
                        <span class="member-info-label">Rolle:</span>
                        <span class="member-info-value member-badge member-badge-<?php echo $user->role; ?>">
                            <?php echo ucfirst($user->role); ?>
                        </span>
                    </div>
                    <div class="member-info-item">
                        <span class="member-info-label">Status:</span>
                        <span class="member-info-value member-badge member-badge-<?php echo $user->status; ?>">
                            <?php echo ucfirst($user->status); ?>
                        </span>
                    </div>
                    <div class="member-info-item">
                        <span class="member-info-label">Registriert am:</span>
                        <span class="member-info-value"><?php echo date('d.m.Y H:i', strtotime($user->created_at)); ?></span>
                    </div>
                    <div class="member-info-item">
                        <span class="member-info-label">Letzter Login:</span>
                        <span class="member-info-value"><?php echo $user->last_login ? date('d.m.Y H:i', strtotime($user->last_login)) : 'Nie'; ?></span>
                    </div>
                    <div class="member-info-item">
                        <span class="member-info-label">Letzte Aktualisierung:</span>
                        <span class="member-info-value"><?php echo $user->updated_at ? date('d.m.Y H:i', strtotime($user->updated_at)) : '-'; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    
    <script>
        // Character counter for bio
        const bioTextarea = document.getElementById('bio');
        if (bioTextarea) {
            bioTextarea.addEventListener('input', function() {
                const maxLength = 500;
                const currentLength = this.value.length;
                const helpText = this.nextElementSibling;
                
                if (currentLength > maxLength) {
                    this.value = this.value.substring(0, maxLength);
                }
                
                helpText.textContent = `${this.value.length}/${maxLength} Zeichen`;
            });
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
