<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * View: Theme Editor Fallback
 *
 * @var array<string, mixed> $data
 */

$state = is_array($data ?? null) ? $data : [];
$activeThemeSlug = (string) ($state['activeThemeSlug'] ?? '');
$reason = (string) ($state['reason'] ?? 'Das aktive Theme stellt keinen eigenen Customizer bereit.');
$reasonCode = (string) ($state['reasonCode'] ?? 'customizer_missing');
$reasonHint = (string) ($state['reasonHint'] ?? '');
$links = is_array($state['links'] ?? null) ? $state['links'] : [];
$constraints = is_array($state['constraints'] ?? null) ? $state['constraints'] : [];
$expectedCustomizerPath = (string) ($state['expectedCustomizerPath'] ?? ($constraints['expected_relative_path'] ?? 'admin/customizer.php'));
$themesLink = (string) ($links['themes'] ?? (SITE_URL . '/admin/themes'));
$explorerLink = (string) ($links['explorer'] ?? (SITE_URL . '/admin/theme-explorer'));
?>
<div class="container-xl">
    <div class="page-header d-flex align-items-center mb-4">
        <div>
            <h2 class="page-title">Theme Editor</h2>
            <div class="text-muted mt-1">Das aktive Theme stellt keinen direkt ladbaren Customizer bereit.</div>
        </div>
    </div>

    <?php
    $alertData = [
        'type' => 'warning',
        'message' => $reason,
        'details' => array_values(array_filter([
            $activeThemeSlug !== '' ? 'Aktives Theme: ' . $activeThemeSlug : '',
            'Erwarteter Pfad: ' . $expectedCustomizerPath,
            $reasonHint !== '' ? $reasonHint : '',
        ])),
    ];
    $alertDismissible = false;
    $alertMarginClass = 'mb-3';
    require __DIR__ . '/../partials/flash-alert.php';
    ?>

    <?php
    $alertData = [
        'type' => 'secondary',
        'message' => 'Der Theme Editor nutzt hier bewusst den sicheren Fallback statt eine unsichere Customizer-Datei direkt zu laden.',
        'details' => array_values(array_filter([
            !empty($constraints['fallback_view']) ? 'Fallback-View: ' . (string) $constraints['fallback_view'] : '',
            'Nächster sicherer Pfad: ' . $expectedCustomizerPath,
        ])),
    ];
    $alertDismissible = false;
    $alertMarginClass = 'mb-3';
    require __DIR__ . '/../partials/flash-alert.php';
    ?>

    <div class="card">
        <div class="card-body">
            <p class="mb-3">Falls das Theme einen eigenen Customizer erhalten soll, erwartet der Admin-Pfad eine sichere Datei <code><?php echo htmlspecialchars($expectedCustomizerPath, ENT_QUOTES); ?></code> innerhalb des aktiven Theme-Verzeichnisses.</p>
            <div class="small text-muted mb-3">
                Grundcode: <code><?php echo htmlspecialchars($reasonCode, ENT_QUOTES); ?></code>
                <?php if ($activeThemeSlug !== ''): ?> · Theme: <code><?php echo htmlspecialchars($activeThemeSlug, ENT_QUOTES); ?></code><?php endif; ?>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo htmlspecialchars($themesLink, ENT_QUOTES); ?>" class="btn btn-primary">Zur Theme-Verwaltung</a>
                <a href="<?php echo htmlspecialchars($explorerLink, ENT_QUOTES); ?>" class="btn btn-outline-secondary">Theme Explorer öffnen</a>
            </div>
        </div>
    </div>
</div>
