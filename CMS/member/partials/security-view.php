<?php
/**
 * Member Security View
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
    <title>Sicherheit - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/member.css">
    <?php renderMemberSidebarStyles(); ?>
</head>
<body class="member-body">
    
    <?php renderMemberSidebar('security'); ?>
    
    <!-- Main Content -->
    <div class="member-content">
        
        <!-- Page Header -->
        <div class="member-page-header">
            <div>
                <h1>Sicherheitseinstellungen</h1>
                <p class="member-page-subtitle">Schütze deinen Account mit starken Sicherheitsmaßnahmen</p>
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
        
        <!-- Security Overview -->
        <div class="member-card">
            <div class="member-card-header">
                <h3>Sicherheits-Score</h3>
                <span class="member-card-icon">🛡️</span>
            </div>
            
            <div class="member-card-body">
                <div class="security-score">
                    <div class="score-circle" data-score="<?php echo $securityData['score']; ?>">
                        <svg viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="45" class="score-bg"></circle>
                            <circle cx="50" cy="50" r="45" class="score-fill"
                                    style="stroke-dashoffset: <?php echo 283 - (283 * $securityData['score'] / 100); ?>"></circle>
                        </svg>
                        <div class="score-text">
                            <span class="score-number"><?php echo $securityData['score']; ?></span>
                            <span class="score-label">/ 100</span>
                        </div>
                    </div>
                    
                    <div class="score-info">
                        <h4>Deine Account-Sicherheit</h4>
                        <p><?php echo htmlspecialchars((string)($securityData['score_message'] ?? '')); ?></p>
                        <ul class="recommendation-list">
                            <?php foreach ((array)($securityData['recommendations'] ?? []) as $rec): ?>
                                <li class="recommendation-item <?php echo !empty($rec['done']) ? 'done' : 'pending'; ?>">
                                    <span class="rec-icon"><?php echo !empty($rec['done']) ? '✓' : '!'; ?></span>
                                    <span class="rec-text"><?php echo htmlspecialchars((string)($rec['text'] ?? '')); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Password Change -->
        <div class="member-card">
            <div class="member-card-header">
                <h3>Passwort ändern</h3>
                <span class="member-card-icon">🔑</span>
            </div>
            
            <div class="member-card-body">
                        <div class="member-info-box">
                    <span class="info-icon">ℹ️</span>
                    <span>Letztes Passwort-Update: <strong><?php echo htmlspecialchars((string)($securityData['password_changed'] ?? 'Unbekannt')); ?></strong></span>
                </div>
                
                <form method="POST" class="member-form">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfPassword; ?>">
                    
                    <div class="member-form-group">
                        <label for="current_password" class="member-form-label">
                            Aktuelles Passwort <span class="required">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="current_password" 
                            name="current_password" 
                            class="member-form-input"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                    
                    <div class="member-form-group">
                        <label for="new_password" class="member-form-label">
                            Neues Passwort <span class="required">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            class="member-form-input"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >
                        <span class="member-form-help">Mindestens 8 Zeichen, mit Groß-, Kleinbuchstaben und Zahl</span>
                        <div class="password-strength" id="password-strength"></div>
                    </div>
                    
                    <div class="member-form-group">
                        <label for="confirm_password" class="member-form-label">
                            Passwort bestätigen <span class="required">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="member-form-input"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >
                    </div>
                    
                    <div class="member-form-actions">
                        <button type="submit" class="member-btn member-btn-primary">
                            🔑 Passwort ändern
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Two-Factor Authentication -->
        <div class="member-card">
            <div class="member-card-header">
                <h3>Zwei-Faktor-Authentifizierung (2FA)</h3>
                <span class="member-badge member-badge-<?php echo $securityData['2fa_enabled'] ? 'success' : 'warning'; ?>">
                    <?php echo $securityData['2fa_enabled'] ? 'Aktiv' : 'Inaktiv'; ?>
                </span>
            </div>
            
            <div class="member-card-body">
                <p class="member-card-description">
                    Schütze deinen Account zusätzlich mit einer zweiten Sicherheitsebene.
                </p>
                
                <?php if ($securityData['2fa_enabled']): ?>
                    <div class="member-success-box">
                        <span class="success-icon">✓</span>
                        <div>
                            <strong>2FA ist aktiviert</strong>
                            <p>Dein Account ist durch Zwei-Faktor-Authentifizierung geschützt.</p>
                        </div>
                    </div>
                    
                    <form method="POST" class="member-form">
                        <input type="hidden" name="action" value="toggle_2fa">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf2FA; ?>">
                        <input type="hidden" name="enable_2fa" value="0">
                        
                        <button type="submit" class="member-btn member-btn-danger">
                            2FA deaktivieren
                        </button>
                    </form>
                <?php else: ?>
                    <div class="member-warning-box">
                        <span class="warning-icon">⚠️</span>
                        <div>
                            <strong>2FA ist nicht aktiviert</strong>
                            <p>Aktiviere 2FA für zusätzlichen Schutz deines Accounts.</p>
                        </div>
                    </div>
                    
                    <form method="POST" class="member-form">
                        <input type="hidden" name="action" value="toggle_2fa">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf2FA; ?>">
                        <input type="hidden" name="enable_2fa" value="1">
                        
                        <button type="submit" class="member-btn member-btn-success">
                            🔐 2FA aktivieren
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Active Sessions -->
        <div class="member-card">
            <div class="member-card-header">
                <h3>Aktive Sitzungen</h3>
                <span class="member-card-icon">📱</span>
            </div>
            
            <div class="member-card-body">
                <?php if (!empty($activeSessions)): ?>
                    <div class="sessions-list">
                        <?php foreach ($activeSessions as $session): ?>
                            <div class="session-item <?php echo $session['is_current'] ? 'current-session' : ''; ?>">
                                <div class="session-icon">
                                    <?php echo $session['device_icon']; ?>
                                </div>
                                <div class="session-info">
                                    <strong><?php echo htmlspecialchars($session['device']); ?></strong>
                                    <?php if ($session['is_current']): ?>
                                        <span class="member-badge member-badge-success">Aktuelle Sitzung</span>
                                    <?php endif; ?>
                                    <p class="session-meta">
                                        <?php echo htmlspecialchars($session['ip']); ?> • 
                                        <?php echo htmlspecialchars($session['location']); ?> • 
                                        <?php echo htmlspecialchars($session['last_activity']); ?>
                                    </p>
                                </div>
                                <?php if (!$session['is_current']): ?>
                                    <button class="member-btn member-btn-sm member-btn-danger" 
                                            onclick="terminateSession('<?php echo $session['id']; ?>')">
                                        Beenden
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="member-card-footer">
                        <button class="member-btn member-btn-danger" onclick="terminateAllSessions()">
                            Alle anderen Sitzungen beenden
                        </button>
                    </div>
                <?php else: ?>
                    <div class="member-empty-state">
                        <div class="empty-icon">📭</div>
                        <p>Keine aktiven Sitzungen gefunden.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Login History -->
        <div class="member-card">
            <div class="member-card-header">
                <h3>Login-Verlauf</h3>
                <span class="member-card-icon">📊</span>
            </div>
            
            <div class="member-card-body">
                <?php if (!empty($securityData['login_history'])): ?>
                    <div class="login-history-list">
                        <?php foreach ($securityData['login_history'] as $login): ?>
                            <div class="login-history-item">
                                <span class="login-status <?php echo $login['success'] ? 'success' : 'failed'; ?>">
                                    <?php echo $login['success'] ? '✓' : '✕'; ?>
                                </span>
                                <div class="login-info">
                                    <strong><?php echo $login['success'] ? 'Erfolgreich' : 'Fehlgeschlagen'; ?></strong>
                                    <p>
                                        <?php echo htmlspecialchars($login['ip']); ?> • 
                                        <?php echo htmlspecialchars($login['time']); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="member-card-description">Kein Login-Verlauf verfügbar.</p>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
    
    <script>
        // Password strength indicator
        const newPasswordInput = document.getElementById('new_password');
        const strengthIndicator = document.getElementById('password-strength');
        
        if (newPasswordInput && strengthIndicator) {
            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 8) strength += 25;
                if (password.length >= 12) strength += 25;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
                if (/\d/.test(password)) strength += 15;
                if (/[^a-zA-Z0-9]/.test(password)) strength += 10;
                
                let strengthClass = 'weak';
                let strengthText = 'Schwach';
                
                if (strength >= 75) {
                    strengthClass = 'strong';
                    strengthText = 'Stark';
                } else if (strength >= 50) {
                    strengthClass = 'medium';
                    strengthText = 'Mittel';
                }
                
                strengthIndicator.className = 'password-strength ' + strengthClass;
                strengthIndicator.innerHTML = '<div class="strength-bar"></div><span>' + strengthText + '</span>';
            });
        }
        
        // Terminate session
        function terminateSession(sessionId) {
            if (confirm('Möchtest du diese Sitzung wirklich beenden?')) {
                // AJAX call to terminate session
                console.log('Terminating session:', sessionId);
            }
        }
        
        // Terminate all sessions
        function terminateAllSessions() {
            if (confirm('Möchtest du wirklich alle anderen Sitzungen beenden?')) {
                // AJAX call to terminate all sessions except current
                console.log('Terminating all other sessions');
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
