<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="col-12 cms-editor-primary">
    <div class="card cms-edit-card mb-3">
        <div class="card-header"><h3 class="card-title">Seitenvorlage</h3></div>
        <div class="card-body">
            <label class="form-label" for="pageTemplateSelect">Layout</label>
            <select class="form-select mb-3" id="pageTemplateSelect" name="page_template">
                <?php foreach ($pageTemplates as $template): ?>
                <option value="<?= htmlspecialchars($template['id'], ENT_QUOTES) ?>"<?= $template['id'] === $pageTemplateValue ? ' selected' : '' ?>><?= htmlspecialchars($template['label'], ENT_QUOTES) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-hint mb-3">Das aktive Theme legt die verfügbaren Layouts fest. Die Zusatzfelder gelten für beide Inhaltssprachen.</div>
            <?php foreach ($pageTemplates as $template): ?>
                <?php if ($template['meta_fields'] === []) { continue; } ?>
                <?php $activeTemplate = $template['id'] === $pageTemplateValue; ?>
                <fieldset data-page-template-meta-panel="<?= htmlspecialchars($template['id'], ENT_QUOTES) ?>"<?= $activeTemplate ? '' : ' hidden' ?>>
                    <legend class="fs-5"><?= htmlspecialchars($template['label'], ENT_QUOTES) ?> – Zusatzfelder</legend>
                    <?php if ($template['description'] !== ''): ?><p class="text-secondary"><?= htmlspecialchars($template['description'], ENT_QUOTES) ?></p><?php endif; ?>
                    <div class="row g-3">
                    <?php foreach ($template['meta_fields'] as $key => $field): ?>
                        <?php
                        $fieldId = 'pageMeta_' . $template['id'] . '_' . $key;
                        $type = $field['type'] ?? 'text';
                        $value = $activeTemplate ? ($pageTemplateMetaValues[$key] ?? '') : '';
                        $value = is_array($value)
                            ? ($type === 'array-of-objects' ? json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : implode("\n", $value))
                            : (string) $value;
                        $disabled = $activeTemplate ? '' : ' disabled';
                        ?>
                        <div class="<?= $type === 'array-of-objects' ? 'col-12' : 'col-md-6' ?>">
                            <label class="form-label" for="<?= htmlspecialchars($fieldId, ENT_QUOTES) ?>"><?= htmlspecialchars((string) ($field['label'] ?? $key), ENT_QUOTES) ?></label>
                            <?php if (in_array($type, ['textarea', 'array', 'array-of-objects'], true)): ?>
                                <textarea class="form-control" id="<?= htmlspecialchars($fieldId, ENT_QUOTES) ?>" name="page_meta[<?= htmlspecialchars($key, ENT_QUOTES) ?>]" rows="<?= $type === 'array-of-objects' ? 8 : 3 ?>"<?= $disabled ?><?= $type === 'array-of-objects' ? ' aria-describedby="' . htmlspecialchars($fieldId, ENT_QUOTES) . '_hint"' : '' ?>><?= htmlspecialchars($value, ENT_QUOTES) ?></textarea>
                                <?php if ($type === 'array-of-objects'): ?>
                                <div class="form-hint" id="<?= htmlspecialchars($fieldId, ENT_QUOTES) ?>_hint">JSON-Array mit höchstens 20 Objekten. Erlaubte Felder: <?= htmlspecialchars(implode(', ', $field['fields'] ?? []), ENT_QUOTES) ?>. Beispiel: <code>[{"icon":"✓","title":"Beratung","text":"Persönliche Unterstützung","url":"/kontakt"}]</code></div>
                                <?php elseif ($type === 'array'): ?><div class="form-hint">Ein Eintrag pro Zeile oder per Komma getrennt.</div><?php endif; ?>
                            <?php elseif ($type === 'select'): ?>
                                <select class="form-select" id="<?= htmlspecialchars($fieldId, ENT_QUOTES) ?>" name="page_meta[<?= htmlspecialchars($key, ENT_QUOTES) ?>]"<?= $disabled ?>>
                                    <option value="">Nicht anzeigen</option>
                                    <?php foreach ($field['options'] ?? [] as $option): ?>
                                    <option value="<?= htmlspecialchars((string) $option, ENT_QUOTES) ?>"<?= $value === $option ? ' selected' : '' ?>><?= htmlspecialchars((string) $option, ENT_QUOTES) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input class="form-control" type="<?= $type === 'date' ? 'date' : 'text' ?>" id="<?= htmlspecialchars($fieldId, ENT_QUOTES) ?>" name="page_meta[<?= htmlspecialchars($key, ENT_QUOTES) ?>]" value="<?= htmlspecialchars($value, ENT_QUOTES) ?>"<?= $type === 'url' ? ' inputmode="url" placeholder="https://example.org oder /lokaler-pfad"' : '' ?><?= $disabled ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </fieldset>
            <?php endforeach; ?>
        </div>
    </div>
</div>
