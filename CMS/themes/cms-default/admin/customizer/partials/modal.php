<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="confirm-reset-modal" class="modal customizer-modal-hidden">
    <div class="modal-content customizer-modal-content">
        <div class="modal-header">
            <h3>⚠️ Einstellungen zurücksetzen?</h3>
            <button type="button" class="modal-close" data-customizer-reset-close aria-label="Dialog schließen">&times;</button>
        </div>
        <div class="modal-body">
            <p>Alle Einstellungen dieses Tabs werden auf die <strong>Standard-Designwerte</strong> des Themes zurückgesetzt.</p>
            <p class="customizer-reset-note">Bereits gespeicherte Anpassungen gehen für diesen Bereich verloren.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-customizer-reset-close>Abbrechen</button>
            <button type="button" class="btn btn-danger" data-customizer-reset-confirm>↺ Zurücksetzen</button>
        </div>
    </div>
</div>
