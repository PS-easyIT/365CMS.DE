<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$pageAssets = $pageAssets ?? [];
$inlineJs = $inlineJs ?? '';
$siteUrl = defined('SITE_URL') ? SITE_URL : '';
$pageKey = $pageKey ?? 'dashboard';
?>
            </div>
        </div>
        <footer class="footer footer-transparent mt-auto py-3">
            <div class="container-xl">
                <div class="row align-items-center text-center text-md-start">
                    <div class="col-12 col-md-auto mb-2 mb-md-0 text-secondary">
                        <?= htmlspecialchars((string)(date('Y'))) ?> © <?= htmlspecialchars((string)(defined('SITE_NAME') ? SITE_NAME : '365CMS')) ?>
                    </div>
                    <div class="col text-secondary">
                        Moderne Mitgliedsverwaltung mit Tabler, MFA, Passkeys und Plugin-Erweiterungen.
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>
<script src="<?= htmlspecialchars(cms_asset_url('tabler/js/tabler.min.js'), ENT_QUOTES) ?>" defer></script>
<script src="<?= htmlspecialchars(cms_asset_url('filepond/filepond.min.js'), ENT_QUOTES) ?>" defer></script>
<script src="<?= htmlspecialchars(cms_asset_url('js/member-dashboard.js'), ENT_QUOTES) ?>" defer></script>
<?php if (!empty($pageAssets['js'])): ?>
    <?php foreach ((array)$pageAssets['js'] as $js): ?>
        <script src="<?= htmlspecialchars((string)$js) ?>" defer></script>
    <?php endforeach; ?>
<?php endif; ?>
<?php if ($inlineJs !== ''): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
<?= $inlineJs ?>
});
</script>
<?php endif; ?>
<?php \CMS\Hooks::doAction('body_end'); ?>
<?php \CMS\Hooks::doAction('member_body_end', $pageKey); ?>
</body>
</html>
