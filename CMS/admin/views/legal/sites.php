<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/** @var array $data */
$d         = $data ?? [];
$pages     = $d['pages'] ?? [];
$assigned  = $d['assigned_pages'] ?? [];
$allPages  = $d['all_pages'] ?? [];
$tabKeys   = ['legal_imprint', 'legal_privacy', 'legal_terms', 'legal_revocation'];
$tabIcons  = [
    'legal_imprint'    => '<svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M9 9l1 0"/><path d="M9 13l6 0"/><path d="M9 17l6 0"/></svg>',
    'legal_privacy'    => '<svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"/></svg>',
    'legal_terms'      => '<svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M5 8v-3a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2h-5"/><path d="M6 14m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M4.5 17l-1.5 5l3 -1.5l3 1.5l-1.5 -5"/></svg>',
    'legal_revocation' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l-4 -4l4 -4"/><path d="M5 10h11a4 4 0 1 1 0 8h-1"/></svg>',
];
$pageIdKeys = ['legal_imprint' => 'imprint_page_id', 'legal_privacy' => 'privacy_page_id', 'legal_terms' => 'terms_page_id', 'legal_revocation' => 'revocation_page_id'];
$templateTypes = ['legal_imprint' => 'imprint', 'legal_privacy' => 'privacy', 'legal_terms' => 'terms', 'legal_revocation' => 'revocation'];
?>

<ul class="nav nav-tabs mb-3" role="tablist">
    <?php foreach ($tabKeys as $i => $key): $p = $pages[$key] ?? []; ?>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?php echo $i === 0 ? 'active' : ''; ?>" data-bs-toggle="tab" href="#tab-<?php echo htmlspecialchars($key); ?>" role="tab">
            <?php echo $tabIcons[$key] ?? ''; ?>
            <?php echo htmlspecialchars($p['label'] ?? $key); ?>
            <?php if (!empty($p['content'])): ?>
                <span class="badge bg-success ms-1">✓</span>
            <?php else: ?>
                <span class="badge bg-secondary ms-1">–</span>
            <?php endif; ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
    <input type="hidden" name="action" value="save">

    <div class="tab-content">
        <?php foreach ($tabKeys as $i => $key): $p = $pages[$key] ?? []; ?>
        <div class="tab-pane fade <?php echo $i === 0 ? 'show active' : ''; ?>" id="tab-<?php echo htmlspecialchars($key); ?>" role="tabpanel">
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title"><?php echo htmlspecialchars($p['label'] ?? ''); ?></h3>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                        <input type="hidden" name="action" value="generate">
                        <input type="hidden" name="template_type" value="<?php echo htmlspecialchars($templateTypes[$key] ?? ''); ?>">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Vorlage generieren</button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Inhalt (HTML)</label>
                        <textarea name="<?php echo htmlspecialchars($key); ?>" class="form-control" rows="12"><?php echo htmlspecialchars($p['content'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Zugewiesene Seite</label>
                        <select name="<?php echo htmlspecialchars($pageIdKeys[$key] ?? ''); ?>" class="form-select">
                            <option value="0">– Keine Seite –</option>
                            <?php foreach ($allPages as $pg): ?>
                                <option value="<?php echo (int)$pg['id']; ?>" <?php echo ($assigned[$pageIdKeys[$key] ?? ''] ?? '') == $pg['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pg['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint">Ordne eine bestehende Seite zu, die diesen Rechtstext anzeigt.</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-primary">Alle Änderungen speichern</button>
    </div>
</form>
