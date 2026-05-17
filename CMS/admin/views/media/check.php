<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$search = (string)($data['search'] ?? '');
$stats = is_array($data['stats'] ?? null) ? $data['stats'] : [];
$baseUrl = (string)($data['base_url'] ?? '/admin/media');
$featuredUrl = (string)($data['featured_url'] ?? '/admin/media?tab=featured');
$constraints = is_array($data['constraints'] ?? null) ? $data['constraints'] : [];
$usageScope = (string)($data['usage_scope'] ?? 'all');
$usageScopeOptions = is_array($data['usage_scope_options'] ?? null) ? $data['usage_scope_options'] : [];
$helpText = (string)($data['help_text'] ?? '');
$consistency = is_array($data['consistency'] ?? null) ? $data['consistency'] : [];
$consistencyItems = is_array($consistency['items'] ?? null) ? $consistency['items'] : [];
$consistencyStats = is_array($consistency['stats'] ?? null) ? $consistency['stats'] : [];
$consistencyEmptyState = is_array($consistency['empty_state'] ?? null) ? $consistency['empty_state'] : [
    'title' => 'Keine offenen Featured-Image-Probleme gefunden',
    'subtitle' => 'Alle aktuell gefilterten Inhalte besitzen eine funktionierende Referenz.',
];
$consistencyHelpText = (string)($consistency['help_text'] ?? '');
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="content-listing-header">
            <div>
                <div class="page-pretitle">Medienverwaltung</div>
                <nav class="cms-media-tabs" aria-label="Medienverwaltung Navigation">
                    <a href="/admin/media" class="cms-media-tabs__tab">Medien</a>
                    <a href="<?php echo htmlspecialchars($featuredUrl, ENT_QUOTES); ?>" class="cms-media-tabs__tab">Beitrags- &amp; Site Medien</a>
                    <a href="<?php echo htmlspecialchars($baseUrl . '?tab=check', ENT_QUOTES); ?>" class="cms-media-tabs__tab is-active" aria-current="page">Medien Check</a>
                    <a href="<?php echo htmlspecialchars($baseUrl . '?tab=categories', ENT_QUOTES); ?>" class="cms-media-tabs__tab">Kategorien</a>
                    <a href="<?php echo htmlspecialchars($baseUrl . '?tab=settings', ENT_QUOTES); ?>" class="cms-media-tabs__tab">Einstellungen</a>
                </nav>
                <h2 class="page-title mb-1">Medien Check</h2>
                <div class="content-listing-header__meta">
                    <span><?php echo (int)($stats['issue_count'] ?? $consistencyStats['issue_count'] ?? 0); ?> Auffälligkeiten</span>
                    <span><?php echo (int)($stats['missing_assignment_count'] ?? $consistencyStats['missing_assignment_count'] ?? 0); ?> ohne Bild</span>
                    <span><?php echo (int)($stats['broken_reference_count'] ?? $consistencyStats['broken_reference_count'] ?? 0); ?> defekte Referenzen</span>
                </div>
                <?php if ($helpText !== ''): ?>
                    <div class="text-secondary mt-1"><?php echo htmlspecialchars($helpText); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!empty($alert)): ?>
            <?php $alertData = $alert; $alertMarginClass = 'mb-3'; require __DIR__ . '/../partials/flash-alert.php'; ?>
        <?php endif; ?>

        <div class="cms-admin-info-box mb-3" role="note">
            <div class="cms-admin-info-box__head">
                <h3 class="cms-admin-info-box__title">Read-only Konsistenzprüfung</h3>
            </div>
            <p class="cms-admin-info-box__text">
                Diese Ansicht markiert nur Auffälligkeiten. Korrekturen erfolgen bewusst über bestehende Editor- oder Replace-Pfade.
            </p>
        </div>
        <div class="card content-listing-card mb-4">
            <div class="card-header content-listing-toolbar">
                <div class="content-listing-toolbar__label">Filter &amp; Suche</div>
                <form method="get" action="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES); ?>" class="row g-2 align-items-end">
                    <input type="hidden" name="tab" value="check">
                    <div class="col-md-6 col-lg-5">
                        <label for="mediaCheckSearch" class="form-label">Nach Beitrag, Seite, Status oder Referenz suchen</label>
                        <input
                            type="search"
                            class="form-control"
                            id="mediaCheckSearch"
                            name="q"
                            value="<?php echo htmlspecialchars($search); ?>"
                            maxlength="<?php echo (int)($constraints['search_max_length'] ?? 120); ?>"
                            placeholder="z. B. hero, startseite oder defekt">
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <label for="mediaCheckScope" class="form-label">Inhalte anzeigen</label>
                        <select class="form-select" id="mediaCheckScope" name="usage_scope">
                            <?php foreach ($usageScopeOptions as $scopeOption): ?>
                                <?php
                                $scopeValue = (string)($scopeOption['value'] ?? 'all');
                                $scopeLabel = (string)($scopeOption['label'] ?? $scopeValue);
                                ?>
                                <option value="<?php echo htmlspecialchars($scopeValue, ENT_QUOTES); ?>"<?php echo $scopeValue === $usageScope ? ' selected' : ''; ?>><?php echo htmlspecialchars($scopeLabel); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Filtern</button>
                        <?php if ($search !== '' || $usageScope !== 'all'): ?>
                            <a href="<?php echo htmlspecialchars($baseUrl . '?tab=check', ENT_QUOTES); ?>" class="btn btn-outline-secondary">Zurücksetzen</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title mb-1">Konsistenz-Check für Featured Images</h3>
                    <div class="media-check-results-hint">Korrekturen erfolgen idealerweise direkt im jeweiligen Inhaltseintrag, damit Referenz und Kontext konsistent bleiben.</div>
                    <?php if ($consistencyHelpText !== ''): ?>
                        <div class="text-secondary small"><?php echo htmlspecialchars($consistencyHelpText); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-blue-lt"><?php echo (int)($consistencyStats['issue_count'] ?? 0); ?> offene Auffälligkeiten</span>
                    <span class="badge bg-warning-lt text-warning"><?php echo (int)($consistencyStats['missing_assignment_count'] ?? 0); ?> ohne Bild</span>
                    <span class="badge bg-danger-lt text-danger"><?php echo (int)($consistencyStats['broken_reference_count'] ?? 0); ?> defekte Referenzen</span>
                </div>

                <?php if ($consistencyItems === []): ?>
                    <div class="empty py-4">
                        <div class="empty-img">✅</div>
                        <p class="empty-title"><?php echo htmlspecialchars((string)($consistencyEmptyState['title'] ?? 'Keine offenen Featured-Image-Probleme gefunden')); ?></p>
                        <p class="empty-subtitle text-secondary"><?php echo htmlspecialchars((string)($consistencyEmptyState['subtitle'] ?? 'Alle aktuell gefilterten Inhalte besitzen eine funktionierende Referenz.')); ?></p>
                    </div>
                <?php else: ?>
                    <div class="media-check-results-wrap">
                        <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Inhalt</th>
                                    <th>Status</th>
                                    <th>Aktuelle Referenz</th>
                                    <th>Empfehlung</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($consistencyItems as $issue): ?>
                                    <?php
                                    $issueTitle = (string)($issue['title'] ?? 'Ohne Titel');
                                    $issueTypeLabel = (string)($issue['content_type_label'] ?? 'Inhalt');
                                    $issueStatusLabel = (string)($issue['status_label'] ?? 'Auffälligkeit');
                                    $issueStatusClass = (string)($issue['status_class'] ?? 'bg-secondary-lt');
                                    $issueStatusTextClass = (string)($issue['status_text_class'] ?? 'text-secondary');
                                    $issueReference = (string)($issue['reference_display'] ?? '');
                                    $issueEditUrl = (string)($issue['edit_url'] ?? '#');
                                    $issuePrimaryActionLabel = (string)($issue['primary_action_label'] ?? 'Öffnen');
                                    $issueReplaceUrl = (string)($issue['replace_url'] ?? '');
                                    $issueReplaceLabel = (string)($issue['replace_label'] ?? '');
                                    $sharedUsageCount = (int)($issue['shared_usage_count'] ?? 0);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <div class="d-flex flex-wrap align-items-center gap-2">
                                                    <span class="badge bg-blue-lt"><?php echo htmlspecialchars($issueTypeLabel); ?></span>
                                                    <span class="fw-semibold"><?php echo htmlspecialchars($issueTitle); ?></span>
                                                </div>
                                                <?php if ($sharedUsageCount > 1): ?>
                                                    <div class="small text-secondary">Die aktuelle Referenz wird von <?php echo $sharedUsageCount; ?> Inhalten gemeinsam genutzt.</div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo htmlspecialchars($issueStatusClass, ENT_QUOTES); ?> <?php echo htmlspecialchars($issueStatusTextClass, ENT_QUOTES); ?>">
                                                <?php echo htmlspecialchars($issueStatusLabel); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($issueReference !== ''): ?>
                                                <code class="small"><?php echo htmlspecialchars($issueReference); ?></code>
                                            <?php else: ?>
                                                <span class="text-secondary small">Keine Referenz gespeichert</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2 mb-2">
                                                <a href="<?php echo htmlspecialchars($issueEditUrl, ENT_QUOTES); ?>" class="btn btn-outline-primary btn-sm"><?php echo htmlspecialchars($issuePrimaryActionLabel); ?></a>
                                                <?php if ($issueReplaceUrl !== '' && $issueReplaceLabel !== ''): ?>
                                                    <a href="<?php echo htmlspecialchars($issueReplaceUrl, ENT_QUOTES); ?>" class="btn btn-outline-secondary btn-sm"><?php echo htmlspecialchars($issueReplaceLabel); ?></a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
