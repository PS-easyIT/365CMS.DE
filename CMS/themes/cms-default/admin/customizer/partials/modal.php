<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="confirm-reset-modal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3>⚠️ Einstellungen zurücksetzen?</h3>
            <button class="modal-close" onclick="closeResetModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Alle Einstellungen dieses Tabs werden auf die <strong>Standard-Designwerte</strong> des Themes zurückgesetzt.</p>
            <p class="customizer-reset-note">Bereits gespeicherte Anpassungen gehen für diesen Bereich verloren.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeResetModal()">Abbrechen</button>
            <button type="button" class="btn btn-danger" onclick="confirmReset()">↺ Zurücksetzen</button>
        </div>
    </div>
</div>
