<?php
declare(strict_types=1);

<<<<<<< HEAD
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
=======
/**
 * Seitenvorlage und Zusatzfelder im Seiten-Editor.
 *
 * Erwartet:
 *   $pageTemplates          – Definitionen aus PageTemplateService::getDefinitions()
 *   $pageTemplateValue      – aktuell gewählte Vorlagen-ID
 *   $pageTemplateMetaValues – gespeicherte bzw. zuletzt eingegebene Zusatzfeldwerte
 *
 * Inaktive Vorlagen-Panels werden vom Editor-Skript ausgeblendet und deaktiviert, damit
 * gleichnamige Felder nicht die Werte der gewählten Vorlage überschreiben.
 */

if (!defined('ABSPATH')) {
    exit;
}

$pageTemplates = is_array($pageTemplates ?? null) ? $pageTemplates : [];
if ($pageTemplates === []) {
    $pageTemplates = [['id' => 'default', 'label' => 'Standard', 'description' => '', 'meta_fields' => []]];
}
$pageTemplateValue = trim((string) ($pageTemplateValue ?? 'default'));
$pageTemplateMetaValues = is_array($pageTemplateMetaValues ?? null) ? $pageTemplateMetaValues : [];
$pageTemplateIds = array_values(array_filter(array_map(static fn(array $template): string => trim((string) ($template['id'] ?? '')), $pageTemplates)));
if (!in_array($pageTemplateValue, $pageTemplateIds, true)) {
    $pageTemplateValue = in_array('default', $pageTemplateIds, true) ? 'default' : (string) ($pageTemplateIds[0] ?? 'default');
}
$escapeTemplateField = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$selectedTemplateHasFields = (bool) array_filter(
    $pageTemplates,
    static fn(array $template): bool => (string) ($template['id'] ?? '') === $pageTemplateValue && !empty($template['meta_fields'])
);
?>
<div class="mb-3">
    <label class="form-label" for="pageTemplateSelect">Seitenvorlage</label>
    <select class="form-select" id="pageTemplateSelect" name="page_template" aria-describedby="pageTemplateHint">
        <?php foreach ($pageTemplates as $template): ?>
            <?php $templateId = trim((string) ($template['id'] ?? '')); ?>
            <?php if ($templateId === '') { continue; } ?>
            <option value="<?= $escapeTemplateField($templateId) ?>"<?= $templateId === $pageTemplateValue ? ' selected' : '' ?>>
                <?= $escapeTemplateField((string) ($template['label'] ?? $templateId)) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <div class="form-hint" id="pageTemplateHint">Das aktive Theme legt fest, welche Vorlagen und Zusatzfelder verfügbar sind.</div>
</div>

<?php foreach ($pageTemplates as $template): ?>
    <?php
    $templateId = trim((string) ($template['id'] ?? ''));
    $metaFields = is_array($template['meta_fields'] ?? null) ? $template['meta_fields'] : [];
    if ($templateId === '' || $metaFields === []) {
        continue;
    }
    $isActiveTemplate = $templateId === $pageTemplateValue;
    $templateDescription = trim((string) ($template['description'] ?? ''));
    ?>
    <fieldset class="border rounded-3 p-3 mb-3" data-page-template-meta-panel="<?= $escapeTemplateField($templateId) ?>"<?= $isActiveTemplate ? '' : ' hidden disabled' ?>>
        <legend class="fw-semibold fs-5 mb-1 float-none w-auto px-1"><?= $escapeTemplateField((string) ($template['label'] ?? $templateId)) ?> – Zusatzfelder</legend>
        <?php if ($templateDescription !== ''): ?>
            <div class="text-secondary small mb-3"><?= $escapeTemplateField($templateDescription) ?></div>
        <?php endif; ?>
        <?php foreach ($metaFields as $fieldKey => $field): ?>
            <?php
            $fieldKey = preg_replace('/[^a-z0-9_-]/i', '', (string) $fieldKey) ?? '';
            if ($fieldKey === '' || !is_array($field)) {
                continue;
            }
            $fieldId = 'pageMeta_' . $templateId . '_' . $fieldKey;
            $fieldType = strtolower(trim((string) ($field['type'] ?? 'text')));
            $fieldLabel = trim((string) ($field['label'] ?? $fieldKey));
            $fieldValue = $isActiveTemplate ? ($pageTemplateMetaValues[$fieldKey] ?? '') : '';
            if (is_array($fieldValue)) {
                $fieldValue = json_encode($fieldValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '';
            }
            $fieldValue = (string) $fieldValue;
            ?>
            <div class="mb-3">
                <label class="form-label" for="<?= $escapeTemplateField($fieldId) ?>"><?= $escapeTemplateField($fieldLabel) ?></label>
                <?php if ($fieldType === 'array-of-objects'): ?>
                    <?php
                    $hintId = $fieldId . '_hint';
                    $objectKeys = array_values(array_filter(array_map('strval', (array) ($field['fields'] ?? []))));
                    $example = json_encode([array_fill_keys($objectKeys, '…')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
                    ?>
                    <textarea class="form-control font-monospace" id="<?= $escapeTemplateField($fieldId) ?>" name="page_meta[<?= $escapeTemplateField($fieldKey) ?>]" rows="8" spellcheck="false" aria-describedby="<?= $escapeTemplateField($hintId) ?>"><?= $escapeTemplateField($fieldValue) ?></textarea>
                    <div class="form-hint" id="<?= $escapeTemplateField($hintId) ?>">
                        JSON-Liste mit höchstens <?= (int) \CMS\Services\PageTemplateService::MAX_OBJECT_ITEMS ?> Einträgen. Erlaubte Felder: <?= $escapeTemplateField(implode(', ', $objectKeys)) ?>.
                        Beispiel: <code><?= $escapeTemplateField($example) ?></code>. URLs beginnen mit <code>/</code> oder <code>https://</code>.
                    </div>
                <?php elseif ($fieldType === 'textarea'): ?>
                    <textarea class="form-control" id="<?= $escapeTemplateField($fieldId) ?>" name="page_meta[<?= $escapeTemplateField($fieldKey) ?>]" rows="3"><?= $escapeTemplateField($fieldValue) ?></textarea>
                <?php else: ?>
                    <input type="text" class="form-control" id="<?= $escapeTemplateField($fieldId) ?>" name="page_meta[<?= $escapeTemplateField($fieldKey) ?>]" value="<?= $escapeTemplateField($fieldValue) ?>"<?= $fieldType === 'url' ? ' inputmode="url" placeholder="/pfad oder https://…"' : '' ?>>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </fieldset>
<?php endforeach; ?>
<div class="text-secondary small mb-3" data-page-template-meta-empty<?= $selectedTemplateHasFields ? ' hidden' : '' ?>>Für diese Vorlage sind keine Zusatzfelder definiert.</div>
>>>>>>> a21cdf1cbe7760f7d7466627ac44af28c45a1ba4
