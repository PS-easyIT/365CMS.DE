<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$site = $data['site'] ?? null;
$isNew = (bool)($data['isNew'] ?? true);
$defaults = $data['defaults'] ?? [];
$templateOptions = $data['templateOptions'] ?? [];
$settings = $site['settings'] ?? $defaults;
$cards = $site['cards'] ?? [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites" class="btn btn-ghost-secondary btn-sm me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6"/></svg>
                    Zurück
                </a>
            </div>
            <div class="col">
                <div class="page-pretitle">Routing / Hub Sites</div>
                <h2 class="page-title"><?php echo $isNew ? 'Neue Routing / Hub Site' : 'Routing / Hub Site bearbeiten'; ?></h2>
            </div>
            <?php if (!$isNew): ?>
                <div class="col-auto">
                    <span class="badge bg-azure-lt">Public URL: /<?php echo htmlspecialchars((string)($settings['hub_slug'] ?? '')); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <form method="post" action="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites" id="hubSiteForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save">
            <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?php echo (int)($site['id'] ?? 0); ?>">
            <?php endif; ?>
            <input type="hidden" name="cards_json" id="cardsJsonInput" value="<?php echo htmlspecialchars(json_encode($cards, JSON_UNESCAPED_UNICODE)); ?>">

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Basisdaten</h3></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label required">Name</label>
                                <input type="text" class="form-control" name="site_name" value="<?php echo htmlspecialchars((string)($site['table_name'] ?? '')); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Öffentlicher Slug</label>
                                <input type="text" class="form-control" value="/<?php echo htmlspecialchars((string)($settings['hub_slug'] ?? '')); ?>" readonly>
                                <div class="form-hint">Der Slug wird beim Speichern automatisch aus dem Titel erzeugt und als öffentliche Route im `cms-default` Theme bereitgestellt.</div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Beschreibung</label>
                                <textarea class="form-control" name="description" rows="2"><?php echo htmlspecialchars((string)($site['description'] ?? '')); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Hero / Einstieg</h3></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Themen-Badge</label>
                                    <input type="text" class="form-control" name="hub_badge" value="<?php echo htmlspecialchars((string)($settings['hub_badge'] ?? '')); ?>" placeholder="z. B. Microsoft 365">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Template</label>
                                    <select class="form-select" name="hub_template">
                                        <?php foreach ($templateOptions as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars((string)$value); ?>" <?php echo (($settings['hub_template'] ?? '') === $value) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)$label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Hero-Titel</label>
                                    <input type="text" class="form-control" name="hub_hero_title" value="<?php echo htmlspecialchars((string)($settings['hub_hero_title'] ?? '')); ?>" placeholder="Wenn leer, wird der Name der Hub Site verwendet">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Hero-Text</label>
                                    <textarea class="form-control" name="hub_hero_text" rows="4" placeholder="Ein kurzer Einleitungstext für diese Routing-/Sammelseite."><?php echo htmlspecialchars((string)($settings['hub_hero_text'] ?? '')); ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">CTA Text</label>
                                    <input type="text" class="form-control" name="hub_cta_label" value="<?php echo htmlspecialchars((string)($settings['hub_cta_label'] ?? '')); ?>" placeholder="z. B. Alle Themen ansehen">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">CTA URL</label>
                                    <input type="text" class="form-control" name="hub_cta_url" value="<?php echo htmlspecialchars((string)($settings['hub_cta_url'] ?? '')); ?>" placeholder="/themen">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Routing-Kacheln</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addCard">Kachel hinzufügen</button>
                        </div>
                        <div class="card-body p-0">
                            <div id="cardsContainer"></div>
                            <div class="text-center text-secondary py-4 d-none" id="cardsEmpty">Noch keine Kacheln vorhanden.</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Template-Varianten</h3></div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                                <li><strong>IT Themen Allgemein</strong><br><span class="text-secondary small">Breit, neutral, editorial.</span></li>
                                <li><strong>Microsoft 365</strong><br><span class="text-secondary small">Azure-/M365-Optik, modern.</span></li>
                                <li><strong>Datenschutz</strong><br><span class="text-secondary small">Vertrauen, Schutz, strukturierte Hinweise.</span></li>
                                <li><strong>Compliance</strong><br><span class="text-secondary small">Governance, Policies, Nachvollziehbarkeit.</span></li>
                                <li><strong>Linux</strong><br><span class="text-secondary small">Technischer, dunkler, terminalnaher Charakter.</span></li>
                            </ul>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100"><?php echo $isNew ? 'Hub Site erstellen' : 'Hub Site aktualisieren'; ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var cards = <?php echo json_encode($cards, JSON_UNESCAPED_UNICODE); ?>;
    var container = document.getElementById('cardsContainer');
    var emptyState = document.getElementById('cardsEmpty');
    var input = document.getElementById('cardsJsonInput');

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(value || ''));
        return div.innerHTML;
    }

    function sync() {
        input.value = JSON.stringify(cards);
    }

    function render() {
        container.innerHTML = '';
        emptyState.classList.toggle('d-none', cards.length !== 0);

        cards.forEach(function (card, index) {
            var html = '';
            html += '<div class="border-bottom p-3">';
            html += '  <div class="row g-2">';
            html += '    <div class="col-md-6"><label class="form-label small">Titel</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.title || '') + '" data-index="' + index + '" data-key="title"></div>';
            html += '    <div class="col-md-6"><label class="form-label small">URL</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.url || '') + '" data-index="' + index + '" data-key="url"></div>';
            html += '    <div class="col-md-6"><label class="form-label small">Badge</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.badge || '') + '" data-index="' + index + '" data-key="badge"></div>';
            html += '    <div class="col-md-6"><label class="form-label small">Meta</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.meta || '') + '" data-index="' + index + '" data-key="meta"></div>';
            html += '    <div class="col-12"><label class="form-label small">Kurzbeschreibung</label><textarea class="form-control form-control-sm" rows="3" data-index="' + index + '" data-key="summary">' + escapeHtml(card.summary || '') + '</textarea></div>';
            html += '    <div class="col-12 text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-card" data-index="' + index + '">Entfernen</button></div>';
            html += '  </div>';
            html += '</div>';
            container.insertAdjacentHTML('beforeend', html);
        });

        sync();
    }

    document.getElementById('addCard').addEventListener('click', function () {
        cards.push({ title: '', url: '', badge: '', meta: '', summary: '' });
        render();
    });

    container.addEventListener('input', function (event) {
        var target = event.target;
        var index = parseInt(target.dataset.index || '-1', 10);
        var key = target.dataset.key || '';
        if (index < 0 || !cards[index] || !key) {
            return;
        }
        cards[index][key] = target.value;
        sync();
    });

    container.addEventListener('click', function (event) {
        var button = event.target.closest('.remove-card');
        if (!button) {
            return;
        }
        var index = parseInt(button.dataset.index || '-1', 10);
        if (index < 0) {
            return;
        }
        cards.splice(index, 1);
        render();
    });

    render();
})();
</script>
