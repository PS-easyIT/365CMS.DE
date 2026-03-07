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
$templateDefaults = $d['templates'] ?? [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Recht</div>
                <h2 class="page-title">Legal Sites</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-deck row-cards mb-4">
            <div class="col-md-4">
                <div class="card"><div class="card-body"><div class="subheader">Bereiche</div><div class="h1 mb-0"><?= count($tabKeys) ?></div></div></div>
            </div>
            <div class="col-md-4">
                <div class="card"><div class="card-body"><div class="subheader">Zugewiesene Seiten</div><div class="h1 mb-0"><?= count(array_filter($assigned)) ?></div></div></div>
            </div>
            <div class="col-md-4">
                <div class="card"><div class="card-body"><div class="subheader">Veröffentlicht</div><div class="text-secondary">Impressum, Datenschutz, AGB und Widerruf zentral pflegen und vorhandenen Seiten zuordnen.</div></div></div>
            </div>
        </div>

        <div class="alert alert-primary" role="alert">
            Jeder Bereich enthält Vorlagen, Inhaltsfeld und Seitenzuordnung. Leere Bereiche können direkt mit einer Vorlage befüllt werden – also weniger weiße Wüste, mehr verwertbarer Rechtstext.
        </div>
        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert['type'] ?? 'info'); ?> mb-4" role="alert">
                <?php echo htmlspecialchars($alert['message'] ?? ''); ?>
            </div>
        <?php endif; ?>

        <div class="row row-cards">
        <?php foreach ($tabKeys as $i => $key): $p = $pages[$key] ?? []; ?>
        <div class="col-12">
            <?php $templateType = $templateTypes[$key] ?? ''; ?>
            <?php $defaultTemplate = $templateDefaults[$templateType] ?? ''; ?>
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <?php echo $tabIcons[$key] ?? ''; ?>
                        <h3 class="card-title mb-0"><?php echo htmlspecialchars($p['label'] ?? ''); ?></h3>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-primary btn-sm js-insert-template" data-target="legal-<?php echo htmlspecialchars($key); ?>" data-template="<?php echo htmlspecialchars($defaultTemplate, ENT_QUOTES); ?>">Vorlage einfügen</button>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                            <input type="hidden" name="action" value="generate">
                            <input type="hidden" name="template_type" value="<?php echo htmlspecialchars($templateType); ?>">
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Vorlage speichern</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="subheader">Status</div>
                                <div class="fw-bold mb-2"><?php echo !empty($p['content']) ? 'Inhalt vorhanden' : 'Noch leer'; ?></div>
                                <div class="text-secondary small"><?php echo !empty($p['content']) ? 'Der Text ist gepflegt und kann einer Seite zugeordnet werden.' : 'Mit einem Klick auf „Vorlage generieren“ wird ein DSGVO-/TMG-Grundgerüst eingefügt.'; ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="subheader">Zugeordnete Seite</div>
                                <div class="fw-bold mb-2"><?php echo !empty($assigned[$pageIdKeys[$key] ?? '']) ? 'Verknüpft' : 'Nicht verknüpft'; ?></div>
                                <div class="text-secondary small">Wähle unten eine veröffentlichte Seite, damit der Inhalt öffentlich an der richtigen Stelle ausgespielt wird.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="subheader">Vorlage</div>
                                <div class="fw-bold mb-2"><?php echo $defaultTemplate !== '' ? 'Standardtext verfügbar' : 'Keine Vorlage'; ?></div>
                                <div class="text-secondary small">Die Vorlage enthält Platzhalter wie Firma, Anschrift und Kontakt und kann danach individuell angepasst werden.</div>
                            </div>
                        </div>
                    </div>

                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                        <input type="hidden" name="action" value="save">
                        <div class="mb-3">
                            <label class="form-label">Inhalt (HTML)</label>
                            <textarea name="<?php echo htmlspecialchars($key); ?>" id="legal-<?php echo htmlspecialchars($key); ?>" class="form-control" rows="12"><?php echo htmlspecialchars($p['content'] ?? ''); ?></textarea>
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
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Bereich speichern</button>
                            <?php if (empty($p['content']) && $defaultTemplate !== ''): ?>
                                <span class="text-secondary small align-self-center">Tipp: Erst Vorlage generieren, dann anpassen.</span>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-insert-template').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var template = this.getAttribute('data-template') || '';
            var field = document.getElementById(targetId);

            if (!field) {
                return;
            }

            if (field.value.trim() !== '') {
                cmsConfirm({
                    title: 'Vorlage einfügen?',
                    message: 'Vorhandener Inhalt wird überschrieben.',
                    confirmText: 'Einfügen',
                    onConfirm: function () {
                        field.value = template;
                    }
                });
                return;
            }

            field.value = template;
        });
    });
});
</script>
