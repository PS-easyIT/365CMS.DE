<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$items = is_array($data['items'] ?? null) ? $data['items'] : [];
$search = (string)($data['search'] ?? '');
$stats = is_array($data['stats'] ?? null) ? $data['stats'] : [];
$baseUrl = (string)($data['base_url'] ?? '/admin/media');
$constraints = is_array($data['constraints'] ?? null) ? $data['constraints'] : [];
$usageScope = (string)($data['usage_scope'] ?? 'all');
$usageScopeOptions = is_array($data['usage_scope_options'] ?? null) ? $data['usage_scope_options'] : [];
$emptyState = is_array($data['empty_state'] ?? null) ? $data['empty_state'] : [
    'title' => 'Keine Medien gefunden',
    'subtitle' => 'Sobald Beiträge oder Seiten ein Bild erhalten, erscheinen sie hier.',
];
$helpText = (string)($data['help_text'] ?? '');
$isSuccessAlert = is_array($alert ?? null) && (string)($alert['type'] ?? '') === 'success';

if (!function_exists('cms_admin_media_render_featured_usage_list')) {
    /**
     * @param list<array<string, mixed>> $usageItems
     */
    function cms_admin_media_render_featured_usage_list(array $usageItems): string
    {
        if ($usageItems === []) {
            return '<span class="text-secondary small">Keine aktiven Verwendungen gefunden.</span>';
        }

        $html = '<div class="d-flex flex-column gap-2">';
        $visibleItems = array_slice($usageItems, 0, 6);

        foreach ($visibleItems as $usageItem) {
            $editUrl = (string)($usageItem['edit_url'] ?? '#');
            $title = (string)($usageItem['title'] ?? 'Ohne Titel');
            $contentTypeLabel = (string)($usageItem['content_type_label'] ?? 'Inhalt');
            $fieldLabel = (string)($usageItem['field_label'] ?? 'Verwendung');

            $html .= '<a href="' . htmlspecialchars($editUrl, ENT_QUOTES) . '" class="text-reset text-decoration-none small d-inline-flex flex-wrap align-items-center gap-1">';
            $html .= '<span class="badge bg-blue-lt">' . htmlspecialchars($contentTypeLabel) . '</span>';
            $html .= '<span class="fw-medium">' . htmlspecialchars($title) . '</span>';
            $html .= '<span class="text-secondary">(' . htmlspecialchars($fieldLabel) . ')</span>';
            $html .= '</a>';
        }

        $remaining = count($usageItems) - count($visibleItems);
        if ($remaining > 0) {
            $html .= '<span class="text-secondary small">+ ' . $remaining . ' weitere Referenz' . ($remaining === 1 ? '' : 'en') . '</span>';
        }

        $html .= '</div>';

        return $html;
    }
}
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Medienverwaltung</div>
                <h2 class="page-title">Beitrags- &amp; Site Medien</h2>
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

        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="h2 mb-1"><?php echo (int)($stats['image_count'] ?? 0); ?></div>
                        <div class="text-secondary">Eindeutige Bildreferenzen</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="h2 mb-1"><?php echo (int)($stats['reference_count'] ?? 0); ?></div>
                        <div class="text-secondary">Aktive Verknüpfungen</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="h2 mb-1"><?php echo (int)($stats['post_reference_count'] ?? 0); ?></div>
                        <div class="text-secondary">Beitrags-Verknüpfungen</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="h2 mb-1"><?php echo (int)($stats['page_reference_count'] ?? 0); ?></div>
                        <div class="text-secondary">Seiten-Verknüpfungen</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="h2 mb-1"><?php echo (int)($stats['missing_count'] ?? 0); ?></div>
                        <div class="text-secondary">Fehlende Dateien</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="get" action="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES); ?>" class="row g-2 align-items-end">
                    <input type="hidden" name="tab" value="featured">
                    <div class="col-md-6 col-lg-5">
                        <label for="featuredMediaSearch" class="form-label">Nach Bildname, Pfad, Beitrag oder Seite suchen</label>
                        <input
                            type="search"
                            class="form-control"
                            id="featuredMediaSearch"
                            name="q"
                            value="<?php echo htmlspecialchars($search); ?>"
                            maxlength="<?php echo (int)($constraints['search_max_length'] ?? 120); ?>"
                            placeholder="z. B. hero, teaser oder startseite">
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <label for="featuredMediaScope" class="form-label">Verwendungen anzeigen</label>
                        <select class="form-select" id="featuredMediaScope" name="usage_scope">
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
                            <a href="<?php echo htmlspecialchars($baseUrl . '?tab=featured', ENT_QUOTES); ?>" class="btn btn-outline-secondary">Zurücksetzen</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if ($items === []): ?>
                    <div class="empty">
                        <div class="empty-img">🖼️</div>
                        <p class="empty-title"><?php echo htmlspecialchars((string)($emptyState['title'] ?? 'Keine Medien gefunden')); ?></p>
                        <p class="empty-subtitle text-secondary"><?php echo htmlspecialchars((string)($emptyState['subtitle'] ?? 'Sobald Beiträge oder Seiten ein Bild erhalten, erscheinen sie hier.')); ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th style="width: 96px;">Vorschau</th>
                                    <th>Bild</th>
                                    <th style="min-width: 22rem;">Verwendet in</th>
                                    <th style="min-width: 20rem;">Bild ersetzen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <?php
                                    $path = (string)($item['path'] ?? '');
                                    $usageItems = is_array($item['usage_items'] ?? null) ? $item['usage_items'] : [];
                                    $fileExists = !empty($item['exists']);
                                    $fieldId = 'replacement-file-' . md5($path);
                                    $statusId = 'replacement-status-' . md5($path);
                                    $isHighlighted = !empty($item['is_highlighted']) && $isSuccessAlert;
                                    $defaultSelectedFileMessage = 'Noch keine Datei ausgewählt.';
                                    $defaultStatusMessage = $fileExists
                                        ? 'Die Datei wird an derselben Medien-Referenz ersetzt. Alle verknüpften Beiträge und Seiten übernehmen automatisch das neue Bild.'
                                        : 'Diese Referenz ist aktuell defekt. Laden Sie hier ein neues Bild an derselben Stelle hoch, um alle Verknüpfungen wiederherzustellen.';
                                    ?>
                                    <tr<?php echo !$fileExists ? ' class="table-warning"' : ($isHighlighted ? ' class="table-success"' : ''); ?>>
                                        <td>
                                            <?php if ($fileExists && (string)($item['preview_url'] ?? '') !== ''): ?>
                                                <a href="<?php echo htmlspecialchars((string)($item['access_url'] ?? ''), ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer">
                                                    <img src="<?php echo htmlspecialchars((string)($item['preview_url'] ?? ''), ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars((string)($item['name'] ?? 'Bild')); ?>" class="avatar avatar-xl rounded" loading="lazy">
                                                </a>
                                            <?php else: ?>
                                                <span class="avatar avatar-xl rounded bg-warning-lt text-warning">!</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars((string)($item['name'] ?? 'Bild')); ?></div>
                                            <div class="text-secondary small"><?php echo htmlspecialchars($path); ?></div>
                                            <div class="mt-2 d-flex flex-wrap gap-2">
                                                <span class="badge bg-blue-lt"><?php echo htmlspecialchars((string)($item['usage_count_label'] ?? '0 Verknüpfungen')); ?></span>
                                                <span class="badge bg-azure-lt"><?php echo (int)($item['post_count'] ?? 0); ?> Beiträge</span>
                                                <span class="badge bg-teal-lt"><?php echo (int)($item['page_count'] ?? 0); ?> Seiten</span>
                                                <?php if (!$fileExists): ?>
                                                    <span class="badge bg-warning text-warning-fg">Datei fehlt</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo cms_admin_media_render_featured_usage_list($usageItems); ?></td>
                                        <td>
                                            <form method="post" enctype="multipart/form-data" class="d-flex flex-column gap-2" data-featured-replace-form="1">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                                                <input type="hidden" name="action" value="replace_item">
                                                <input type="hidden" name="item_path" value="<?php echo htmlspecialchars($path, ENT_QUOTES); ?>">

                                                <?php if ($isHighlighted): ?>
                                                    <div class="alert alert-success py-2 px-3 mb-0 small" data-featured-replace-status>
                                                        Dieses Bild wurde gerade ersetzt. Alle verknüpften Beiträge und Seiten zeigen jetzt automatisch die neue Datei.
                                                    </div>
                                                <?php endif; ?>

                                                <label for="<?php echo htmlspecialchars($fieldId, ENT_QUOTES); ?>" class="form-label mb-0 small">Neues Bild auswählen</label>
                                                <div class="card card-sm border-0 bg-body-tertiary" data-featured-dropzone="1" role="button" tabindex="0" aria-controls="<?php echo htmlspecialchars($fieldId, ENT_QUOTES); ?>">
                                                    <div class="card-body py-3">
                                                        <div class="fw-medium">Bild hier ablegen oder per Klick auswählen</div>
                                                        <div class="text-secondary small mt-1">Ziehen Sie genau ein JPG, PNG, GIF, WebP, BMP oder ICO direkt auf diese Karte. Danach können Sie es sofort ersetzen.</div>
                                                        <div class="small text-success mt-2" data-featured-selected-file data-default-message="<?php echo htmlspecialchars($defaultSelectedFileMessage, ENT_QUOTES); ?>" aria-live="polite"><?php echo htmlspecialchars($defaultSelectedFileMessage); ?></div>
                                                        <div class="mt-3" data-featured-local-preview hidden>
                                                            <div class="d-flex align-items-center gap-3">
                                                                <img
                                                                    src=""
                                                                    alt=""
                                                                    width="72"
                                                                    height="72"
                                                                    class="rounded border bg-white"
                                                                    style="object-fit: cover;"
                                                                    data-featured-local-preview-image>
                                                                <div class="small">
                                                                    <div class="fw-medium" data-featured-local-preview-name></div>
                                                                    <div class="text-secondary" data-featured-local-preview-meta></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input
                                                    type="file"
                                                    name="replacement_file"
                                                    id="<?php echo htmlspecialchars($fieldId, ENT_QUOTES); ?>"
                                                    class="form-control form-control-sm"
                                                    accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.ico,image/jpeg,image/png,image/gif,image/webp,image/bmp,image/x-icon,image/vnd.microsoft.icon"
                                                    required>

                                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                                    <button type="submit" class="btn btn-primary btn-sm" data-featured-submit="1">Bild ersetzen</button>
                                                    <?php if ($fileExists && (string)($item['access_url'] ?? '') !== ''): ?>
                                                        <a href="<?php echo htmlspecialchars((string)($item['access_url'] ?? ''), ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm">Original öffnen</a>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="form-hint" id="<?php echo htmlspecialchars($statusId, ENT_QUOTES); ?>" data-featured-replace-status data-default-message="<?php echo htmlspecialchars($defaultStatusMessage, ENT_QUOTES); ?>" aria-live="polite"><?php echo htmlspecialchars($defaultStatusMessage); ?></div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
