<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$fieldType = (string) ($field['type'] ?? 'text');
$fieldValue = (string) $val;
$previewUrl = $fieldValue !== '' ? htmlspecialchars($fieldValue) : '';
?>
<div class="form-group">
    <label for="<?php echo htmlspecialchars($inputId, ENT_QUOTES); ?>" class="form-label">
        <?php echo htmlspecialchars((string) ($field['label'] ?? '')); ?>
    </label>

    <?php if ($fieldType === 'textarea'): ?>
        <textarea id="<?php echo htmlspecialchars($inputId, ENT_QUOTES); ?>" name="<?php echo htmlspecialchars($inputName, ENT_QUOTES); ?>" class="form-control" rows="4"><?php echo htmlspecialchars($fieldValue); ?></textarea>

    <?php elseif ($fieldType === 'checkbox'): ?>
        <div class="customizer-checkbox-row">
            <input type="checkbox" id="<?php echo htmlspecialchars($inputId, ENT_QUOTES); ?>" name="<?php echo htmlspecialchars($inputName, ENT_QUOTES); ?>" value="1" <?php echo $fieldValue !== '0' && $fieldValue !== '' ? 'checked' : ''; ?>>
            <label for="<?php echo htmlspecialchars($inputId, ENT_QUOTES); ?>" class="customizer-checkbox-label">Aktivieren</label>
        </div>

    <?php elseif ($fieldType === 'select'): ?>
        <select id="<?php echo htmlspecialchars($inputId, ENT_QUOTES); ?>" name="<?php echo htmlspecialchars($inputName, ENT_QUOTES); ?>" class="form-control">
            <?php foreach (($field['options'] ?? []) as $optVal => $optLabel): ?>
                <option value="<?php echo htmlspecialchars((string) $optVal, ENT_QUOTES); ?>" <?php echo $fieldValue === (string) $optVal ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $optLabel); ?>
                </option>
            <?php endforeach; ?>
        </select>

    <?php elseif ($fieldType === 'image_upload'): ?>
        <div class="customizer-logo-upload">
            <div id="logo-preview-wrap" class="customizer-logo-preview-wrap">
                <?php if ($previewUrl !== ''): ?>
                    <img id="logo-preview-img" src="<?php echo $previewUrl; ?>" alt="Logo" class="customizer-logo-preview-img">
                <?php else: ?>
                    <span id="logo-preview-img" class="customizer-logo-preview-empty">🖼️ Noch kein Logo ausgewählt</span>
                <?php endif; ?>
            </div>
            <div class="customizer-logo-upload-row">
                <label class="customizer-upload-button">
                    📁 Bild hochladen
                    <input type="file" name="logo_upload_file" accept="image/*" class="customizer-upload-input" onchange="previewLogoUpload(this)">
                </label>
                <span class="customizer-upload-hint">oder URL eingeben:</span>
            </div>
            <input type="text" id="<?php echo htmlspecialchars($inputId, ENT_QUOTES); ?>" name="<?php echo htmlspecialchars($inputName, ENT_QUOTES); ?>" value="<?php echo $previewUrl; ?>" class="form-control" placeholder="https://..." oninput="syncLogoUrlPreview(this.value)">
        </div>

    <?php elseif ($fieldType === 'color'): ?>
        <div class="customizer-color-row">
            <input type="color" id="<?php echo htmlspecialchars($inputId, ENT_QUOTES); ?>" name="<?php echo htmlspecialchars($inputName, ENT_QUOTES); ?>" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES); ?>" class="customizer-color-input">
            <input type="text" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES); ?>" class="form-control customizer-color-text" onchange="document.getElementById('<?php echo htmlspecialchars($inputId, ENT_QUOTES); ?>').value = this.value;">
        </div>

    <?php else: ?>
        <input type="<?php echo htmlspecialchars($fieldType, ENT_QUOTES); ?>" id="<?php echo htmlspecialchars($inputId, ENT_QUOTES); ?>" name="<?php echo htmlspecialchars($inputName, ENT_QUOTES); ?>" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES); ?>" class="form-control">
    <?php endif; ?>

    <?php if (!empty($field['description'])): ?>
        <small class="form-text"><?php echo htmlspecialchars((string) $field['description']); ?></small>
    <?php endif; ?>
</div>
