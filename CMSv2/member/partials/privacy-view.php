<?php
/**
 * Member Privacy View
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
    <title>Datenschutz - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/member.css">
    <?php renderMemberSidebarStyles(); ?>
</head>
<body class="member-body">
    
    <?php renderMemberSidebar('privacy'); ?>
    
    <!-- Main Content -->
    <div class="member-content">
        
        <!-- Page Header -->
        <div class="member-page-header">
            <div>
                <h1>Datenschutz & Privatsphäre</h1>
                <p class="member-page-subtitle">Kontrolliere deine Daten und Privatsphäre-Einstellungen</p>
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
        
        <!-- Privacy Settings -->
        <div class="member-card">
            <div class="member-card-header">
                <h3>Privatsphäre-Einstellungen</h3>
                <span class="member-card-icon">🛡️</span>
            </div>
            
            <div class="member-card-body">
                <form method="POST" class="member-form">
                    <input type="hidden" name="action" value="update_privacy">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfPrivacy; ?>">
                    
                    <!-- Profile Visibility -->
                    <div class="member-form-group">
                        <label for="profile_visibility" class="member-form-label">
                            Profil-Sichtbarkeit
                        </label>
                        <select 
                            id="profile_visibility" 
                            name="profile_visibility" 
                            class="member-form-select"
                        >
                            <option value="public" <?php echo ($privacySettings['profile_visibility'] ?? 'private') === 'public' ? 'selected' : ''; ?>>
                                Öffentlich - Für alle sichtbar
                            </option>
                            <option value="members" <?php echo ($privacySettings['profile_visibility'] ?? 'private') === 'members' ? 'selected' : ''; ?>>
                                Mitglieder - Nur für eingeloggte Benutzer
                            </option>
                            <option value="private" <?php echo ($privacySettings['profile_visibility'] ?? 'private') === 'private' ? 'selected' : ''; ?>>
                                Privat - Nur für mich sichtbar
                            </option>
                        </select>
                        <span class="member-form-help">Wer kann dein Profil sehen?</span>
                    </div>
                    
                    <!-- Toggle Options -->
                    <div class="member-toggle-list">
                        <div class="member-toggle-item">
                            <div class="toggle-info">
                                <strong>E-Mail-Adresse anzeigen</strong>
                                <p>Deine E-Mail auf deinem Profil anzeigen</p>
                            </div>
                            <label class="member-toggle">
                                <input 
                                    type="checkbox" 
                                    name="show_email"
                                    <?php echo ($privacySettings['show_email'] ?? false) ? 'checked' : ''; ?>
                                >
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="member-toggle-item">
                            <div class="toggle-info">
                                <strong>Aktivitäten anzeigen</strong>
                                <p>Deine letzten Aktivitäten öffentlich machen</p>
                            </div>
                            <label class="member-toggle">
                                <input 
                                    type="checkbox" 
                                    name="show_activity"
                                    <?php echo ($privacySettings['show_activity'] ?? false) ? 'checked' : ''; ?>
                                >
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="member-toggle-item">
                            <div class="toggle-info">
                                <strong>Kontaktanfragen erlauben</strong>
                                <p>Andere Mitglieder können dich kontaktieren</p>
                            </div>
                            <label class="member-toggle">
                                <input 
                                    type="checkbox" 
                                    name="allow_contact"
                                    <?php echo ($privacySettings['allow_contact'] ?? true) ? 'checked' : ''; ?>
                                >
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Data Sharing -->
                    <div class="member-settings-section">
                        <h4 class="settings-section-title">
                            <span class="section-icon">📊</span>
                            Datenfreigabe & Analyse
                        </h4>
                        
                        <div class="member-toggle-list">
                            <div class="member-toggle-item">
                                <div class="toggle-info">
                                    <strong>Anonyme Datenfreigabe für Verbesserungen</strong>
                                    <p>Hilf uns, die Plattform zu verbessern (anonymisiert)</p>
                                </div>
                                <label class="member-toggle">
                                    <input 
                                        type="checkbox" 
                                        name="data_sharing"
                                        <?php echo ($privacySettings['data_sharing'] ?? false) ? 'checked' : ''; ?>
                                    >
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="member-toggle-item">
                                <div class="toggle-info">
                                    <strong>Analytics & Tracking</strong>
                                    <p>Nutzungsanalyse zur Verbesserung der Plattform</p>
                                </div>
                                <label class="member-toggle">
                                    <input 
                                        type="checkbox" 
                                        name="analytics_tracking"
                                        <?php echo ($privacySettings['analytics_tracking'] ?? true) ? 'checked' : ''; ?>
                                    >
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="member-toggle-item">
                                <div class="toggle-info">
                                    <strong>Drittanbieter-Cookies</strong>
                                    <p>Cookies von externen Diensten (z.B. Social Media)</p>
                                </div>
                                <label class="member-toggle">
                                    <input 
                                        type="checkbox" 
                                        name="third_party_cookies"
                                        <?php echo ($privacySettings['third_party_cookies'] ?? false) ? 'checked' : ''; ?>
                                    >
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit -->
                    <div class="member-form-actions">
                        <button type="submit" class="member-btn member-btn-primary">
                            💾 Einstellungen speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Data Overview -->
        <div class="member-card">
            <div class="member-card-header">
                <h3>Deine Daten - Übersicht</h3>
                <span class="member-card-icon">📋</span>
            </div>
            
            <div class="member-card-body">
                <p class="member-card-description">
                    Diese Daten haben wir von dir gespeichert (DSGVO Artikel 15):
                </p>
                
                <div class="data-overview-grid">
                    <div class="data-item">
                        <span class="data-label">Profilinformationen:</span>
                        <span class="data-value"><?php echo htmlspecialchars((string)($dataOverview['profile_records'] ?? 0)); ?> Datensätze</span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Aktivitäten:</span>
                        <span class="data-value"><?php echo htmlspecialchars((string)($dataOverview['activities'] ?? 0)); ?> Einträge</span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Login-Verlauf:</span>
                        <span class="data-value"><?php echo htmlspecialchars((string)($dataOverview['logins'] ?? 0)); ?> Einträge</span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Einstellungen:</span>
                        <span class="data-value"><?php echo htmlspecialchars((string)($dataOverview['settings'] ?? 0)); ?> Datensätze</span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Gespeicherte Dateien:</span>
                        <span class="data-value"><?php echo htmlspecialchars((string)($dataOverview['files'] ?? 0)); ?> Dateien (<?php echo htmlspecialchars((string)($dataOverview['total_size'] ?? '0 B')); ?>)</span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Sessions:</span>
                        <span class="data-value"><?php echo htmlspecialchars((string)($dataOverview['sessions'] ?? 0)); ?> aktive Sessions</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Data Export (DSGVO) -->
        <div class="member-card">
            <div class="member-card-header">
                <h3>Daten exportieren</h3>
                <span class="member-card-icon">📦</span>
            </div>
            
            <div class="member-card-body">
                <p class="member-card-description">
                    Du hast das Recht, eine Kopie aller deiner Daten zu erhalten (DSGVO Artikel 20 - Datenportabilität).
                </p>
                
                <form method="POST" class="member-form">
                    <input type="hidden" name="action" value="export_data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfExport; ?>">
                    
                    <button type="submit" class="member-btn member-btn-primary">
                        📥 Alle Daten exportieren (JSON)
                    </button>
                </form>
                
                <div class="member-info-box" style="margin-top: 1rem;">
                    <span class="info-icon">ℹ️</span>
                    <span>Der Export enthält alle deine Profildaten, Aktivitäten und Einstellungen im JSON-Format.</span>
                </div>
            </div>
        </div>
        
        <!-- Account Deletion (DSGVO) -->
        <div class="member-card member-danger-card">
            <div class="member-card-header">
                <h3>Account löschen</h3>
                <span class="member-card-icon">⚠️</span>
            </div>
            
            <div class="member-card-body">
                <p class="member-card-description">
                    Du hast das Recht auf Löschung deiner Daten (DSGVO Artikel 17 - "Recht auf Vergessenwerden").
                </p>
                
                <div class="member-warning-box">
                    <span class="warning-icon">⚠️</span>
                    <div>
                        <strong>Achtung: Diese Aktion ist unwiderruflich!</strong>
                        <p>Wenn du deinen Account löschst, werden alle deine Daten nach einer Karenzzeit von 30 Tagen unwiderruflich gelöscht.</p>
                        <p><strong>Was wird gelöscht:</strong></p>
                        <ul>
                            <li>Alle Profildaten und Einstellungen</li>
                            <li>Deine gesamte Aktivitätshistorie</li>
                            <li>Alle hochgeladenen Dateien</li>
                            <li>Deine Login- und Session-Daten</li>
                        </ul>
                    </div>
                </div>
                
                <form method="POST" class="member-form" id="delete-account-form">
                    <input type="hidden" name="action" value="delete_account">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfDelete; ?>">
                    
                    <div class="member-form-group">
                        <label for="confirm_password" class="member-form-label">
                            Bestätige dein Passwort zum Löschen <span class="required">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="member-form-input"
                            required
                        >
                    </div>
                    
                    <div class="member-form-group">
                        <label class="member-checkbox">
                            <input type="checkbox" id="confirm-deletion" required>
                            <span>Ich verstehe, dass diese Aktion nicht rückgängig gemacht werden kann.</span>
                        </label>
                    </div>
                    
                    <button 
                        type="button" 
                        class="member-btn member-btn-danger"
                        onclick="confirmAccountDeletion()"
                    >
                        🗑️ Account unwiderruflich löschen
                    </button>
                </form>
            </div>
        </div>
        
    </div>
    
    <script>
        function confirmAccountDeletion() {
            const confirmCheckbox = document.getElementById('confirm-deletion');
            const passwordInput = document.getElementById('confirm_password');
            
            if (!confirmCheckbox.checked) {
                alert('Bitte bestätige, dass du die Konsequenzen verstanden hast.');
                return;
            }
            
            if (!passwordInput.value) {
                alert('Bitte gib dein Passwort ein.');
                passwordInput.focus();
                return;
            }
            
            const confirmed = confirm(
                'LETZTE WARNUNG!\n\n' +
                'Bist du dir absolut sicher, dass du deinen Account löschen möchtest?\n\n' +
                'Diese Aktion kann NICHT rückgängig gemacht werden!\n\n' +
                'Alle deine Daten werden in 30 Tagen unwiderruflich gelöscht.'
            );
            
            if (confirmed) {
                const doubleConfirm = confirm(
                    'Bitte bestätige ein letztes Mal:\n\n' +
                    'Ja, ich möchte meinen Account und alle meine Daten löschen.'
                );
                
                if (doubleConfirm) {
                    document.getElementById('delete-account-form').submit();
                }
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
