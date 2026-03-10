<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="col-lg-4">
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0">Live-Vorschau</h3>
            <span class="badge bg-azure-lt" id="templatePreviewColumnsBadge">2 Kacheln</span>
        </div>
        <div class="card-body">
            <div class="hub-template-preview" id="hubTemplatePreview" style="<?php echo htmlspecialchars($templatePreviewStyle, ENT_QUOTES); ?>">
                <div class="hub-template-preview__hero">
                    <span class="hub-template-preview__badge" id="templatePreviewBadge">Template</span>
                    <h4 class="hub-template-preview__title" id="templatePreviewTitle">Template-Vorschau</h4>
                    <p class="hub-template-preview__summary" id="templatePreviewSummary">So wirken Hero, Meta-Felder und 1/2/3 Kachel-Layouts im Admin direkt beim Bearbeiten.</p>
                    <div class="hub-template-preview__meta" id="templatePreviewMeta"></div>
                </div>
                <div class="hub-template-preview__body">
                    <div class="hub-template-preview__toolbar">
                        <span class="hub-template-preview__pill" id="templatePreviewLayoutPill">2 nebeneinander</span>
                        <div class="hub-template-preview__toolbar-meta">
                            <span class="hub-template-preview__pill" id="templatePreviewTypePill">Standard</span>
                            <span class="hub-template-preview__pill" id="templatePreviewImagePill">Bild oben</span>
                            <span class="text-secondary small" id="templatePreviewCardCount">0 Karten</span>
                        </div>
                    </div>
                    <div class="hub-template-preview__quicklinks" id="templatePreviewQuicklinks"></div>
                    <div class="hub-template-preview__grid hub-template-preview__grid--2" id="templatePreviewGrid"></div>
                    <div class="hub-template-preview__sections" id="templatePreviewSections">
                        <div class="hub-template-preview__section-card">
                            <div class="hub-template-preview__section-head">
                                <span class="hub-template-preview__section-eyebrow">Template-Bereich</span>
                                <span class="hub-template-preview__section-icon">◆</span>
                            </div>
                            <h5 class="hub-template-preview__section-title" id="templatePreviewSectionTitle">Template-Bereich</h5>
                            <p class="hub-template-preview__section-text" id="templatePreviewSectionText">Auch die Bereichsfarbe wird hier live aus deinen Template-Farben übernommen.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Hinweis</h3></div>
        <div class="card-body text-secondary small">
            Dieses Profil steuert Aufbau, Farben und Starter-Kacheln aller zugeordneten Hub-Sites. Templates sind hier also wirklich Templates — keine verkleideten Einzelinstanzen mehr.
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Empfehlung</h3></div>
        <div class="card-body text-secondary small">
            Zwei Kacheln nebeneinander funktionieren für viele Layouts am ausgewogensten. Für kompakte oder produktnahe Hubs kannst du hier aber gezielt auf 1 oder 3 Spalten wechseln.
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <button type="submit" class="btn btn-primary w-100"><?php echo $isNew ? 'Template anlegen' : 'Template speichern'; ?></button>
        </div>
    </div>
</div>
