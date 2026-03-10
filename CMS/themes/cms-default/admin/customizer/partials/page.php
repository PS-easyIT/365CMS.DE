<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Themes &amp; Design</div>
                <h2 class="page-title">🎨 Theme Customizer</h2>
                <div class="text-secondary mt-1">Passe das Aussehen deines Themes an.</div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="<?php echo htmlspecialchars(SITE_URL . '/', ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary">🌐 Seite ansehen</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if ($embedInAdminLayout): ?>
            <?php require __DIR__ . '/styles.php'; ?>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success customizer-alert"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error customizer-alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="?tab=<?php echo htmlspecialchars($activeTab, ENT_QUOTES); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_theme_options">
            <input type="hidden" name="active_section" value="<?php echo htmlspecialchars($activeTab, ENT_QUOTES); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($customizerCsrfToken, ENT_QUOTES); ?>">

            <div class="customizer-layout">
                <nav class="customizer-nav">
                    <?php foreach ($config as $key => $tab): ?>
                        <a href="?tab=<?php echo htmlspecialchars((string) $key, ENT_QUOTES); ?>" class="<?php echo $activeTab === $key ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars((string) $tab['title']); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="customizer-content">
                    <?php if (isset($config[$activeTab])): ?>
                        <?php $currentSection = $config[$activeTab]; ?>
                        <div class="admin-card">
                            <h3><?php echo htmlspecialchars((string) $currentSection['title']); ?></h3>

                            <?php foreach ($currentSection['sections'] as $fieldKey => $field): ?>
                                <?php
                                $val = $customizer->get($activeTab, $fieldKey, $field['default']);
                                $inputId = 'field_' . $activeTab . '_' . $fieldKey;
                                $inputName = $activeTab . '_' . $fieldKey;
                                require __DIR__ . '/field.php';
                                ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="admin-card form-actions-card">
                            <div class="form-actions customizer-form-actions">
                                <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
                                <button type="button" class="btn btn-secondary" onclick="showResetConfirm()" title="Alle Einstellungen dieses Tabs auf die Standard-Designwerte zurücksetzen">
                                    ↺ Auf Standardwerte zurücksetzen
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <?php if (isset($config[$activeTab])): ?>
            <form id="reset-form" method="POST" action="?tab=<?php echo htmlspecialchars($activeTab, ENT_QUOTES); ?>" style="display:none;">
                <input type="hidden" name="action" value="reset_theme_tab">
                <input type="hidden" name="active_section" value="<?php echo htmlspecialchars($activeTab, ENT_QUOTES); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($customizerCsrfToken, ENT_QUOTES); ?>">
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/modal.php'; ?>
<?php require __DIR__ . '/scripts.php'; ?>
