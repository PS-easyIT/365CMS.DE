<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SEO_VIEW')) {
    exit;
}

$meta = $data['meta'] ?? [];
$settings = $meta['settings'] ?? [];
$examples = $meta['examples'] ?? [];
$permalinkService = class_exists('\CMS\Services\PermalinkService') ? \CMS\Services\PermalinkService::getInstance() : null;
$buildRuntimePublicUrl = static function (string $path): string {
    $normalizedPath = '/' . ltrim($path, '/');

    if (function_exists('home_url')) {
        return home_url(ltrim($normalizedPath, '/'));
    }

    return rtrim((string) SITE_URL, '/') . $normalizedPath;
};
?>
<div class="page-header d-print-none"><div class="container-xl"><div class="row g-2 align-items-center"><div class="col"><div class="page-pretitle">SEO</div><h2 class="page-title">Meta-Daten & Variablen</h2></div></div></div></div>
<div class="page-body"><div class="container-xl">
    <?php if (!empty($alert)): ?>
        <?php
        $alertData = is_array($alert ?? null) ? $alert : [];
        require dirname(__DIR__) . '/partials/flash-alert.php';
        ?>
    <?php endif; ?>
    <?php require __DIR__ . '/subnav.php'; ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card h-100"><div class="card-header"><h3 class="card-title">Globale Meta-Defaults</h3></div><div class="card-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="save_meta_defaults">
                    <div class="col-12"><label class="form-label">Homepage-Titel</label><input class="form-control" type="text" name="homepage_title" value="<?= htmlspecialchars((string)($settings['seo_homepage_title'] ?? '')) ?>"></div>
                    <div class="col-12"><label class="form-label">Homepage-Beschreibung</label><textarea class="form-control" name="homepage_description" rows="3"><?= htmlspecialchars((string)($settings['seo_homepage_description'] ?? '')) ?></textarea></div>
                    <div class="col-12"><label class="form-label">Globale Meta-Beschreibung</label><textarea class="form-control" name="meta_description" rows="3"><?= htmlspecialchars((string)($settings['seo_meta_description'] ?? '')) ?></textarea></div>
                    <div class="col-md-6"><label class="form-check"><input class="form-check-input" type="checkbox" name="default_robots_index" value="1" <?= !empty($settings['seo_default_robots_index']) ? 'checked' : '' ?>><span class="form-check-label">Standard: index</span></label></div>
                    <div class="col-md-6"><label class="form-check"><input class="form-check-input" type="checkbox" name="default_robots_follow" value="1" <?= !empty($settings['seo_default_robots_follow']) ? 'checked' : '' ?>><span class="form-check-label">Standard: follow</span></label></div>
                    <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="self_referencing_canonical" value="1" <?= !empty($settings['seo_self_referencing_canonical']) ? 'checked' : '' ?>><span class="form-check-label">Self-referencing Canonical automatisch setzen</span></label></div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Meta-Defaults speichern</button></div>
                </form>
            </div></div>
        </div>
        <div class="col-lg-5">
            <div class="card mb-4"><div class="card-header"><h3 class="card-title">Variablen</h3></div><div class="list-group list-group-flush">
                <?php foreach (($meta['variables'] ?? []) as $variable): ?>
                    <div class="list-group-item"><code><?= htmlspecialchars((string)$variable['token']) ?></code><div class="text-secondary small"><?= htmlspecialchars((string)$variable['description']) ?></div></div>
                <?php endforeach; ?>
            </div></div>
            <div class="card"><div class="card-header"><h3 class="card-title">SERP-Beispiele</h3></div><div class="card-body">
                <?php foreach ($examples as $example): ?>
                    <?php
                    $examplePath = (string)(($example['type'] ?? '') === 'post'
                        ? (($permalinkService !== null && (!empty($example['published_at']) || !empty($example['created_at'])))
                            ? $permalinkService->buildPostPathFromValues((string)($example['slug'] ?? ''), (string)($example['published_at'] ?? ''), (string)($example['created_at'] ?? ''))
                            : '/blog/' . ltrim((string)($example['slug'] ?? ''), '/'))
                        : '/' . ltrim((string)($example['slug'] ?? ''), '/'));
                    $exampleUrl = $buildRuntimePublicUrl($examplePath);
                    ?>
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="text-primary fw-semibold mb-1"><?= htmlspecialchars((string)($example['resolved_meta_title'] ?? '')) ?></div>
                        <div class="small text-success mb-1"><?= htmlspecialchars($exampleUrl) ?></div>
                        <div class="small text-secondary"><?= htmlspecialchars((string)($example['resolved_meta_description'] ?? '')) ?></div>
                    </div>
                <?php endforeach; ?>
            </div></div>
        </div>
        <div class="col-12">
            <div class="card"><div class="card-header"><h3 class="card-title">Titel-Template & Analyse-Regeln</h3></div><div class="card-body">
                <form method="post" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="save_templates">
                    <div class="col-lg-5"><label class="form-label">Titel-Template</label><input class="form-control" type="text" name="site_title_format" value="<?= htmlspecialchars((string)($settings['seo_site_title_format'] ?? '%%title%% %%sep%% %%sitename%%')) ?>"></div>
                    <div class="col-lg-2"><label class="form-label">Separator</label><input class="form-control" type="text" name="title_separator" value="<?= htmlspecialchars((string)($settings['seo_title_separator'] ?? '|')) ?>"></div>
                    <div class="col-lg-2"><label class="form-label">Min. Wörter</label><input class="form-control" type="number" name="analysis_min_words" min="100" value="<?= (int)($settings['seo_analysis_min_words'] ?? 300) ?>"></div>
                    <div class="col-lg-3"><label class="form-label">Max. Wörter pro Satz</label><input class="form-control" type="number" name="analysis_sentence_words" min="12" value="<?= (int)($settings['seo_analysis_sentence_words'] ?? 24) ?>"></div>
                    <div class="col-lg-3"><label class="form-label">Max. Wörter pro Absatz</label><input class="form-control" type="number" name="analysis_paragraph_words" min="40" value="<?= (int)($settings['seo_analysis_paragraph_words'] ?? 120) ?>"></div>
                    <div class="col-12"><button class="btn btn-primary" type="submit">Template speichern</button></div>
                </form>
            </div></div>
        </div>
    </div>
</div></div>
