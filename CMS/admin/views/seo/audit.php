<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SEO_VIEW')) {
    exit;
}

$overview = $data['overview'] ?? [];
$audit = $data['audit'] ?? [];
$content = $audit['rows'] ?? [];
$scoreColors = ['good' => 'success', 'warning' => 'warning', 'bad' => 'danger'];
$scoreLabels = ['good' => 'Gut', 'warning' => 'Warnung', 'bad' => 'Kritisch'];
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">SEO</div>
                <h2 class="page-title">SEO Audit</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!empty($alert)): ?>
            <?php
            $alertData = is_array($alert ?? null) ? $alert : [];
            require dirname(__DIR__) . '/partials/flash-alert.php';
            ?>
        <?php endif; ?>

        <?php require __DIR__ . '/subnav.php'; ?>

        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Geprüfte Inhalte</div><div class="h1 mb-0"><?= (int)($overview['total'] ?? 0) ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Warnungen</div><div class="h1 mb-0 text-warning"><?= (int)($audit['warning_count'] ?? 0) ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Kritisch</div><div class="h1 mb-0 text-danger"><?= (int)($audit['critical_count'] ?? 0) ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Ø Score</div><div class="h1 mb-0"><?= (int)($overview['average_score'] ?? 0) ?></div></div></div></div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Inhalte & Bulk-Metadaten</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Inhalt</th>
                            <th>Meta</th>
                            <th>Social & Technik</th>
                            <th>Score</th>
                            <th>Probleme</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($content as $item): ?>
                            <?php
                            // FIX: View defensiv gegen unvollständige Audit-Datensätze absichern.
                            $itemScore = (string)($item['seo_score'] ?? 'warning');
                            if (!array_key_exists($itemScore, $scoreColors)) {
                                $itemScore = 'warning';
                            }
                            $itemScoreValue = (int)($item['seo_score_value'] ?? (($item['analysis']['score'] ?? 0)));
                            $itemIssues = array_values((array)($item['seo_issues'] ?? []));
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars((string)($item['title'] ?? '')) ?></div>
                                    <div class="text-secondary small"><?= htmlspecialchars((string)($item['type'] ?? '')) ?> · <?= htmlspecialchars((string)($item['slug'] ?? '')) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold small"><?= htmlspecialchars((string)($item['resolved_meta_title'] ?? '')) ?></div>
                                    <div class="text-secondary small"><?= htmlspecialchars((string)($item['resolved_meta_description'] ?? '')) ?></div>
                                </td>
                                <td>
                                    <div class="small"><strong>Keyphrase:</strong> <?= htmlspecialchars((string)($item['focus_keyphrase'] ?? '—')) ?></div>
                                    <div class="small"><strong>Canonical:</strong> <?= htmlspecialchars((string)($item['canonical_url'] ?? 'automatisch')) ?></div>
                                    <div class="small"><strong>Schema:</strong> <?= htmlspecialchars((string)($item['schema_type'] ?? 'WebPage')) ?></div>
                                </td>
                                <td><span class="badge bg-<?= htmlspecialchars($scoreColors[$itemScore] ?? 'secondary') ?>"><?= $itemScoreValue ?> · <?= htmlspecialchars($scoreLabels[$itemScore] ?? '?') ?></span></td>
                                <td>
                                    <?php foreach (array_slice($itemIssues, 0, 4) as $issue): ?>
                                        <div class="small text-warning"><strong><?= htmlspecialchars((string)($issue['msg'] ?? '')) ?></strong> <span class="text-secondary">· <?= htmlspecialchars((string)($issue['detail'] ?? '')) ?></span></div>
                                    <?php endforeach; ?>
                                    <?php if ($itemIssues === []): ?><span class="text-success small">✓ Keine offenen Punkte</span><?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <details>
                                        <summary class="btn btn-outline-secondary btn-sm">Bearbeiten</summary>
                                        <form method="post" class="mt-3" style="min-width:340px;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrfToken ?? '')) ?>">
                                            <input type="hidden" name="action" value="save_audit_item">
                                            <input type="hidden" name="content_type" value="<?= htmlspecialchars((string)($item['type'] ?? '')) ?>">
                                            <input type="hidden" name="content_id" value="<?= (int)($item['id'] ?? 0) ?>">
                                            <div class="row g-2 text-start">
                                                <div class="col-12"><label class="form-label small">Meta-Titel</label><input class="form-control form-control-sm" type="text" name="meta_title" value="<?= htmlspecialchars((string)($item['meta_title'] ?? '')) ?>"></div>
                                                <div class="col-12"><label class="form-label small">Meta-Beschreibung</label><textarea class="form-control form-control-sm" name="meta_description" rows="3"><?= htmlspecialchars((string)($item['meta_description'] ?? '')) ?></textarea></div>
                                                <div class="col-12"><label class="form-label small">Fokus-Keyphrase</label><input class="form-control form-control-sm" type="text" name="focus_keyphrase" value="<?= htmlspecialchars((string)($item['focus_keyphrase'] ?? '')) ?>"></div>
                                                <div class="col-12"><label class="form-label small">Canonical</label><input class="form-control form-control-sm" type="text" name="canonical_url" value="<?= htmlspecialchars((string)($item['canonical_url'] ?? '')) ?>"></div>
                                                <div class="col-6"><label class="form-label small">Schema</label><input class="form-control form-control-sm" type="text" name="schema_type" value="<?= htmlspecialchars((string)($item['schema_type'] ?? '')) ?>"></div>
                                                <div class="col-6"><label class="form-label small">Twitter Card</label><input class="form-control form-control-sm" type="text" name="twitter_card" value="<?= htmlspecialchars((string)($item['twitter_card'] ?? '')) ?>"></div>
                                                <div class="col-6"><label class="form-check mt-4"><input class="form-check-input" type="checkbox" name="robots_index" value="1" <?= !empty($item['robots_index']) ? 'checked' : '' ?>><span class="form-check-label">index</span></label></div>
                                                <div class="col-6"><label class="form-check mt-4"><input class="form-check-input" type="checkbox" name="robots_follow" value="1" <?= !empty($item['robots_follow']) ? 'checked' : '' ?>><span class="form-check-label">follow</span></label></div>
                                                <div class="col-12"><button type="submit" class="btn btn-primary btn-sm w-100">Speichern</button></div>
                                            </div>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
