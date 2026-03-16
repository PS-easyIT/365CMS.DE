<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = (string)($section['label'] ?? 'Plugin-Bereich');
$pageKey = 'plugin_' . (string)($section['slug'] ?? 'section');
$pageAssets = [];

include __DIR__ . '/partials/header.php';
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><?= htmlspecialchars((string)($section['label'] ?? 'Plugin-Bereich')) ?></h3>
        <span class="badge bg-primary-lt"><?= htmlspecialchars((string)($section['plugin'] ?? 'plugin')) ?></span>
    </div>
    <div class="card-body">
        <?php \CMS\Hooks::doAction('member_plugin_section_head', $section, $user, $params ?? []); ?>
        <?php
        $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $pluginCallback = $requestMethod === 'POST' && is_callable($section['post_callback'] ?? null)
            ? $section['post_callback']
            : ($section['render_callback'] ?? null);

        if (is_callable($pluginCallback)) {
            call_user_func($pluginCallback, $user, $params ?? []);
        }
        ?>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php';
