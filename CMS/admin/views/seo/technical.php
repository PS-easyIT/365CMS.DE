<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SEO_VIEW')) {
    exit;
}

$technical = $data['technical'] ?? [];
$settings = $technical['settings'] ?? [];
$brokenLinks = $technical['broken_links'] ?? [];
$missingAltRows = $technical['missing_alt_rows'] ?? [];
$noindexCandidates = $technical['noindex_candidates'] ?? [];
$hreflangGroups = $technical['hreflang_groups'] ?? [];
$redirectStats = $technical['redirect_stats'] ?? [];
$indexNow = $technical['indexnow'] ?? [];
$indexNowRootTxtFiles = $indexNow['root_txt_files'] ?? [];
$indexNowValidationErrors = $indexNow['validation_errors'] ?? [];
$indexNowValidationNotes = $indexNow['validation_notes'] ?? [];
$indexNowDebug = $indexNow['debug'] ?? [];
$indexNowDebugCandidates = is_array($indexNowDebug['root_candidates'] ?? null) ? $indexNowDebug['root_candidates'] : [];
$indexNowSelectedFileReason = (string)($indexNowDebug['selected_file_reason'] ?? '');
$indexNowSelectedFileResolvedFrom = (string)($indexNowDebug['selected_file_resolved_from'] ?? '');
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">SEO</div>
                <h2 class="page-title">Technisches SEO</h2>
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
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Broken Links</div>
                    <div class="h1 mb-0 text-danger"><?= count($brokenLinks) ?></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Bilder ohne Alt</div>
                    <div class="h1 mb-0 text-warning"><?= count($missingAltRows) ?></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">noindex-Kandidaten</div>
                    <div class="h1 mb-0"><?= count($noindexCandidates) ?></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">404-Hits</div>
                    <div class="h1 mb-0"><?= (int)($redirectStats['not_found_hits'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Technische Standards &amp; IndexNow</h3>
                </div>
                <div class="card-body">
                    <form method="post" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="save_technical_settings">

                        <div class="col-12">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="auto_redirect_slug" value="1" <?= !empty($settings['seo_technical_auto_redirect_slug']) ? 'checked' : '' ?>>
                                <span class="form-check-label">Weiterleitung bei Slug-Änderung automatisch</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="hreflang_enabled" value="1" <?= !empty($settings['seo_technical_hreflang_enabled']) ? 'checked' : '' ?>>
                                <span class="form-check-label">hreflang-Gruppen pflegen</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="breadcrumbs_enabled" value="1" <?= !empty($settings['seo_technical_breadcrumbs_enabled']) ? 'checked' : '' ?>>
                                <span class="form-check-label">Breadcrumbs aktiv</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="image_alt_required" value="1" <?= !empty($settings['seo_technical_image_alt_required']) ? 'checked' : '' ?>>
                                <span class="form-check-label">Bild-Alt-Texte verpflichtend</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="noindex_archives" value="1" <?= !empty($settings['seo_technical_noindex_archives']) ? 'checked' : '' ?>>
                                <span class="form-check-label">Archive noindex</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="noindex_tags" value="1" <?= !empty($settings['seo_technical_noindex_tags']) ? 'checked' : '' ?>>
                                <span class="form-check-label">Tags noindex</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="pagination_rel" value="1" <?= !empty($settings['seo_technical_pagination_rel']) ? 'checked' : '' ?>>
                                <span class="form-check-label">Pagination-SEO aktiv</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="broken_link_scan" value="1" <?= !empty($settings['seo_technical_broken_link_scan']) ? 'checked' : '' ?>>
                                <span class="form-check-label">Broken-Link-Prüfung aktiv</span>
                            </label>
                        </div>

                        <div class="col-12"><hr class="my-2"></div>

                        <div class="col-12">
                            <label class="form-label" for="indexnow-key">IndexNow API-Key</label>
                            <input
                                class="form-control"
                                id="indexnow-key"
                                type="text"
                                name="indexnow_key"
                                value="<?= htmlspecialchars((string)($settings['seo_indexnow_key'] ?? '')) ?>"
                                placeholder="z. B. 75d0bf8d82694244923c1ecf56fe1e6e"
                                autocomplete="off"
                            >
                            <div class="form-hint">Der Key wird für die dynamische <code>/KEY.txt</code>-Auslieferung und für IndexNow-Submissions verwendet.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="indexnow-key-file">Root-`.txt`-Datei auswählen</label>
                            <select class="form-select" id="indexnow-key-file" name="indexnow_key_file">
                                <option value="">Keine physische Root-TXT-Datei wählen</option>
                                <?php foreach ($indexNowRootTxtFiles as $fileName): ?>
                                    <option value="<?= htmlspecialchars((string) $fileName) ?>" <?= (($settings['seo_indexnow_key_file'] ?? '') === $fileName) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) $fileName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-hint">
                                Root-Verzeichnis: <code><?= htmlspecialchars((string)($indexNow['root_directory'] ?? ABSPATH)) ?></code>
                            </div>
                            <?php if (empty($indexNowRootTxtFiles)): ?>
                                <div class="alert alert-warning mt-3 mb-0" role="alert">
                                    Im aktuellen CMS-Root wurde noch keine <code>.txt</code>-Datei gefunden.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Technische SEO speichern</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">IndexNow-Prüfung</h3>
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <span class="badge <?= !empty($indexNow['key_available']) ? 'bg-success' : 'bg-warning text-dark' ?>">
                            API-Key <?= !empty($indexNow['key_available']) ? 'bereit' : 'fehlt' ?>
                        </span>
                        <span class="badge <?= !empty($indexNow['selected_root_file']) ? 'bg-primary' : 'bg-secondary' ?>">
                            Root-TXT <?= !empty($indexNow['selected_root_file']) ? 'gewählt' : 'nicht gewählt' ?>
                        </span>
                        <span class="badge <?= !empty($indexNow['ready_for_submission']) ? 'bg-success' : 'bg-warning text-dark' ?>">
                            Übermittlung <?= !empty($indexNow['ready_for_submission']) ? 'bereit' : 'prüfen' ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="small text-secondary mb-1">Dynamische Keydatei</div>
                            <?php if (!empty($indexNow['dynamic_key_file_url'])): ?>
                                <a href="<?= htmlspecialchars((string) $indexNow['dynamic_key_file_url']) ?>" target="_blank" rel="noopener noreferrer">
                                    <code><?= htmlspecialchars((string) $indexNow['dynamic_key_file_url']) ?></code>
                                </a>
                            <?php else: ?>
                                <span class="text-secondary">Noch nicht verfügbar</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-secondary mb-1">Ausgewählte Root-TXT-Datei</div>
                            <?php if (!empty($indexNow['selected_root_file'])): ?>
                                <div><code><?= htmlspecialchars((string) $indexNow['selected_root_file']) ?></code></div>
                                <?php if (!empty($indexNow['selected_root_file_url'])): ?>
                                    <a href="<?= htmlspecialchars((string) $indexNow['selected_root_file_url']) ?>" target="_blank" rel="noopener noreferrer" class="small">
                                        <?= htmlspecialchars((string) $indexNow['selected_root_file_url']) ?>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-secondary">Keine Datei ausgewählt</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="small text-secondary mb-1">Datei vorhanden</div>
                            <span class="badge <?= !empty($indexNow['selected_root_file_exists']) ? 'bg-success' : 'bg-secondary' ?>">
                                <?= !empty($indexNow['selected_root_file_exists']) ? 'Ja' : 'Nein' ?>
                            </span>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-secondary mb-1">Dateiname = API-Key</div>
                            <span class="badge <?= !empty($indexNow['selected_root_file_matches_key']) ? 'bg-success' : 'bg-secondary' ?>">
                                <?= !empty($indexNow['selected_root_file_matches_key']) ? 'Ja' : 'Nein' ?>
                            </span>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-secondary mb-1">Dateiinhalt = API-Key</div>
                            <span class="badge <?= !empty($indexNow['selected_root_file_content_matches_key']) ? 'bg-success' : 'bg-secondary' ?>">
                                <?= !empty($indexNow['selected_root_file_content_matches_key']) ? 'Ja' : 'Nein' ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($indexNowValidationErrors)): ?>
                        <div class="alert alert-warning" role="alert">
                            <div class="fw-semibold mb-1">Prüfung mit Hinweisen</div>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($indexNowValidationErrors as $error): ?>
                                    <li><?= htmlspecialchars((string) $error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($indexNowValidationNotes)): ?>
                        <div class="text-secondary small">
                            <?php foreach ($indexNowValidationNotes as $note): ?>
                                <div>• <?= htmlspecialchars((string) $note) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <hr class="my-4">

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div>
                            <div class="fw-semibold">Debug: geprüfte Root-Pfade</div>
                            <div class="text-secondary small">Hier siehst du exakt, welche Kandidaten aktuell geprüft wurden und woran die ausgewählte TXT-Datei scheitert.</div>
                        </div>
                        <span class="badge bg-info-lt text-info"><?= count($indexNowDebugCandidates) ?> Pfad<?= count($indexNowDebugCandidates) === 1 ? '' : 'e' ?></span>
                    </div>

                    <div class="alert alert-info" role="alert">
                        <div class="fw-semibold mb-1">Prüf-Ergebnis für die ausgewählte TXT-Datei</div>
                        <div><?= htmlspecialchars($indexNowSelectedFileReason !== '' ? $indexNowSelectedFileReason : 'Noch keine Detailinformationen vorhanden.') ?></div>
                        <?php if (!empty($indexNow['selected_root_file_path'])): ?>
                            <div class="small mt-2">Aufgelöster Dateipfad: <code><?= htmlspecialchars((string) $indexNow['selected_root_file_path']) ?></code></div>
                        <?php elseif ($indexNowSelectedFileResolvedFrom !== ''): ?>
                            <div class="small mt-2">Gefunden unter: <code><?= htmlspecialchars($indexNowSelectedFileResolvedFrom) ?></code></div>
                        <?php endif; ?>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Quelle</th>
                                    <th>Geprüfter Pfad</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($indexNowDebugCandidates)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-secondary py-3">Noch keine Root-Debugdaten verfügbar.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($indexNowDebugCandidates as $candidate): ?>
                                        <?php
                                        $candidateSource = (string)($candidate['source'] ?? 'unbekannt');
                                        $candidatePath = (string)($candidate['normalized_path'] ?? $candidate['original_path'] ?? '');
                                        $candidateUsable = !empty($candidate['usable']);
                                        $candidateReason = (string)($candidate['reason'] ?? '');
                                        $candidateTxtFiles = is_array($candidate['txt_files'] ?? null) ? $candidate['txt_files'] : [];
                                        $candidateSelectedExists = !empty($candidate['selected_file_exists']);
                                        $candidateSelectedPath = (string)($candidate['selected_file_path'] ?? '');
                                        ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($candidateSource) ?></code></td>
                                            <td>
                                                <code><?= htmlspecialchars($candidatePath !== '' ? $candidatePath : '—') ?></code>
                                                <?php if ($candidateSelectedExists && $candidateSelectedPath !== ''): ?>
                                                    <div class="small text-success mt-1">Ausgewählte Datei gefunden: <code><?= htmlspecialchars($candidateSelectedPath) ?></code></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $candidateUsable ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' ?>">
                                                    <?= $candidateUsable ? 'geprüft' : 'übersprungen' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($candidateReason !== '' ? $candidateReason : 'Keine Details vorhanden.') ?></div>
                                                <?php if (!empty($candidateTxtFiles)): ?>
                                                    <div class="small text-secondary mt-1">
                                                        TXT-Dateien: <?= htmlspecialchars(implode(', ', array_map(static fn($file): string => (string) $file, $candidateTxtFiles))) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Broken Links</h3>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-vcenter">
                        <thead>
                            <tr>
                                <th>Quelle</th>
                                <th>Ziel</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($brokenLinks)): ?>
                                <tr>
                                    <td colspan="2" class="text-center text-secondary py-4">Keine internen Broken Links erkannt.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($brokenLinks as $row): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars((string) $row['source_title']) ?>
                                            <div class="text-secondary small">
                                                <?= htmlspecialchars((string) $row['source_type']) ?> · <?= htmlspecialchars((string) $row['source_slug']) ?>
                                            </div>
                                        </td>
                                        <td><code><?= htmlspecialchars((string) $row['target_path']) ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">hreflang &amp; Bild-SEO</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3"><strong>hreflang-Gruppen:</strong> <?= count($hreflangGroups) ?></div>
                    <div class="mb-3"><strong>404-Weiterleitungen:</strong> <?= (int)($redirectStats['redirects_total'] ?? 0) ?> Regeln, <?= (int)($redirectStats['redirects_active'] ?? 0) ?> aktiv</div>
                    <div class="text-secondary small">Inhalte mit fehlenden Bild-Alt-Texten: <?= count($missingAltRows) ?> · Inhalte mit noindex: <?= count($noindexCandidates) ?></div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
