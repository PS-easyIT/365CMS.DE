<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$site = $data['site'] ?? null;
$isNew = (bool)($data['isNew'] ?? true);
$defaults = $data['defaults'] ?? [];
$templateOptions = $data['templateOptions'] ?? [];
$templateProfiles = $data['templateProfiles'] ?? [];
$settings = $site['settings'] ?? $defaults;
$cards = $site['cards'] ?? [];
$hubDomains = is_array($settings['hub_domains'] ?? null) ? $settings['hub_domains'] : [];
$mainDomainHost = trim((string)(parse_url((string) SITE_URL, PHP_URL_HOST) ?? ''));
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
                <div class="page-pretitle">Hub-Sites</div>
                <h2 class="page-title"><?php echo $isNew ? 'Neue Hub-Site' : 'Hub-Site bearbeiten'; ?></h2>
            </div>
            <?php if (!$isNew): ?>
                <div class="col-auto">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-azure-lt">Public URL: /<?php echo htmlspecialchars((string)($settings['hub_slug'] ?? '')); ?></span>
                        <span class="badge bg-indigo-lt">EN: /<?php echo htmlspecialchars((string)($settings['hub_slug'] ?? '')); ?>/en</span>
                        <?php if (!empty($settings['hub_slug'])): ?>
                            <button type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    data-copy-hub-url="<?php echo htmlspecialchars(SITE_URL . '/' . ltrim((string)$settings['hub_slug'], '/'), ENT_QUOTES); ?>">
                                Slug kopieren
                            </button>
                            <a href="<?php echo htmlspecialchars(SITE_URL . '/' . ltrim((string)$settings['hub_slug'], '/')); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
                                DE öffnen
                            </a>
                            <a href="<?php echo htmlspecialchars(rtrim(SITE_URL . '/' . ltrim((string)$settings['hub_slug'], '/'), '/') . '/en'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
                                EN öffnen
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><a class="nav-link active" href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites">Content</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites?action=templates">Templates</a></li>
        </ul>

        <?php if (!empty($alert)): ?>
            <?php $alertData = $alert; $alertMarginClass = 'mb-3'; require __DIR__ . '/../partials/flash-alert.php'; ?>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites" id="hubSiteForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save">
            <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?php echo (int)($site['id'] ?? 0); ?>">
            <?php endif; ?>
            <input type="hidden" name="open_public_after_save" id="openPublicAfterSaveInput" value="0">
            <input type="hidden" name="cards_json" id="cardsJsonInput" value="<?php echo htmlspecialchars(json_encode($cards, JSON_UNESCAPED_UNICODE)); ?>">
            <input type="hidden" name="hub_feature_cards_json" id="featureCardsJsonInput" value="<?php echo htmlspecialchars((string)($settings['hub_feature_cards_json'] ?? '[]')); ?>">
            <input type="hidden" name="hub_feature_card_interval" id="hubFeatureCardIntervalInput" value="0">
            <input type="hidden" id="hubTemplateProfilesInput" value="<?php echo htmlspecialchars((string) json_encode($templateProfiles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES); ?>">
            <input type="hidden" id="hubSiteConfigInput" value="<?php echo htmlspecialchars((string) json_encode([
                'isNew' => $isNew,
                'siteUrl' => rtrim((string) SITE_URL, '/'),
                'storedSlug' => (string) ($settings['hub_slug'] ?? ''),
                'legacyFeatureCardInterval' => (int) ($settings['hub_feature_card_interval'] ?? 0),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES); ?>">

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <strong>Inhaltssprache</strong>
                                    <div class="text-secondary small">Deutsch bleibt Standard, English wird unter <code>/slug/en</code> ausgespielt.</div>
                                </div>
                                <div class="btn-group" role="tablist" aria-label="Hub-Sprachansicht">
                                    <button type="button" class="btn btn-primary" data-hub-lang-toggle="de" aria-pressed="true">Deutsch</button>
                                    <button type="button" class="btn btn-outline-primary" data-hub-lang-toggle="en" aria-pressed="false">English</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Basisdaten</h3></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label required">Name</label>
                                <input type="text" class="form-control" name="site_name" value="<?php echo htmlspecialchars((string)($site['table_name'] ?? '')); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Öffentlicher Slug</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="hubSlugPreviewInput" value="/<?php echo htmlspecialchars((string)($settings['hub_slug'] ?? '')); ?>" readonly>
                                    <button type="button" class="btn btn-outline-secondary" id="copySlugPreviewButton">Slug kopieren</button>
                                </div>
                                <div class="form-hint">Der Slug wird beim Speichern automatisch aus dem Titel erzeugt und als öffentliche Route im `cms-default` Theme bereitgestellt. Schon vor dem ersten Speichern wird hier eine Live-Vorschau angezeigt.</div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Beschreibung</label>
                                <textarea class="form-control" id="hubSiteDescriptionEditor" name="description" rows="4" data-editor="hub-richtext" data-source="form"><?php echo htmlspecialchars((string)($site['description'] ?? '')); ?></textarea>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Zusatzdomains</label>
                                <textarea class="form-control" name="hub_domains" rows="4" placeholder="hub.example.de&#10;thema.example.org"><?php echo htmlspecialchars(implode("\n", array_map('strval', $hubDomains))); ?></textarea>
                                <div class="form-hint">Eine Domain pro Zeile. Nur zusätzliche Domains/Subdomains, die auf die Startseite zeigen. Die Hauptdomain <code><?php echo htmlspecialchars($mainDomainHost); ?></code> ist hier ausdrücklich tabu.</div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Hero / Einstieg</h3></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Template-Profil</label>
                                    <select class="form-select" name="hub_template" id="hubTemplateSelect">
                                        <?php foreach ($templateOptions as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars((string)$value); ?>" <?php echo (($settings['hub_template'] ?? '') === $value) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)$label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-hint">Layouts, Header-Links und Designvorgaben bearbeitest du zentral im Tab <strong>Templates</strong>. Beim Neuanlegen werden die Starter-Kacheln des gewählten Templates automatisch übernommen.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">CTA URL</label>
                                    <input type="text" class="form-control" name="hub_cta_url" value="<?php echo htmlspecialchars((string)($settings['hub_cta_url'] ?? '')); ?>" placeholder="/themen">
                                </div>
                                <div class="col-12" data-lang-pane="de">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Themen-Badge</label>
                                            <input type="text" class="form-control" name="hub_badge" value="<?php echo htmlspecialchars((string)($settings['hub_badge'] ?? '')); ?>" placeholder="z. B. Microsoft 365">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Hero-Titel</label>
                                            <input type="text" class="form-control" name="hub_hero_title" value="<?php echo htmlspecialchars((string)($settings['hub_hero_title'] ?? '')); ?>" placeholder="Wenn leer, wird der Name der Hub Site verwendet">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Hero-Text</label>
                                            <textarea class="form-control" id="hubHeroTextEditorDe" name="hub_hero_text" rows="5" placeholder="Ein kurzer Einleitungstext für diese Hub-Site." data-editor="hub-richtext" data-source="form"><?php echo htmlspecialchars((string)($settings['hub_hero_text'] ?? '')); ?></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">CTA Text</label>
                                            <input type="text" class="form-control" name="hub_cta_label" value="<?php echo htmlspecialchars((string)($settings['hub_cta_label'] ?? '')); ?>" placeholder="z. B. Alle Themen ansehen">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: Zielgruppe</label>
                                            <input type="text" class="form-control" name="hub_meta_audience" value="<?php echo htmlspecialchars((string)($settings['hub_meta_audience'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: Verantwortlich</label>
                                            <input type="text" class="form-control" name="hub_meta_owner" value="<?php echo htmlspecialchars((string)($settings['hub_meta_owner'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: Update-Zyklus</label>
                                            <input type="text" class="form-control" name="hub_meta_update_cycle" value="<?php echo htmlspecialchars((string)($settings['hub_meta_update_cycle'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: Fokus</label>
                                            <input type="text" class="form-control" name="hub_meta_focus" value="<?php echo htmlspecialchars((string)($settings['hub_meta_focus'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: KPI</label>
                                            <input type="text" class="form-control" name="hub_meta_kpi" value="<?php echo htmlspecialchars((string)($settings['hub_meta_kpi'] ?? '')); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 d-none" data-lang-pane="en">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="alert alert-info mb-0">Die englische Hub-Site wird unter <code>/<?php echo htmlspecialchars((string)($settings['hub_slug'] ?? 'hub-site')); ?>/en</code> ausgeliefert. URL und Kartenstruktur bleiben identisch.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Topic badge</label>
                                            <input type="text" class="form-control" name="hub_badge_en" value="<?php echo htmlspecialchars((string)($settings['hub_badge_en'] ?? '')); ?>" placeholder="e.g. Microsoft 365">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Hero title (EN)</label>
                                            <input type="text" class="form-control" name="hub_hero_title_en" value="<?php echo htmlspecialchars((string)($settings['hub_hero_title_en'] ?? '')); ?>" placeholder="Optional English headline">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Hero text (EN)</label>
                                            <textarea class="form-control" id="hubHeroTextEditorEn" name="hub_hero_text_en" rows="5" placeholder="Short English intro for this hub." data-editor="hub-richtext" data-source="form"><?php echo htmlspecialchars((string)($settings['hub_hero_text_en'] ?? '')); ?></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">CTA label (EN)</label>
                                            <input type="text" class="form-control" name="hub_cta_label_en" value="<?php echo htmlspecialchars((string)($settings['hub_cta_label_en'] ?? '')); ?>" placeholder="e.g. Explore all topics">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: Audience</label>
                                            <input type="text" class="form-control" name="hub_meta_audience_en" value="<?php echo htmlspecialchars((string)($settings['hub_meta_audience_en'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: Owner</label>
                                            <input type="text" class="form-control" name="hub_meta_owner_en" value="<?php echo htmlspecialchars((string)($settings['hub_meta_owner_en'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: Update cycle</label>
                                            <input type="text" class="form-control" name="hub_meta_update_cycle_en" value="<?php echo htmlspecialchars((string)($settings['hub_meta_update_cycle_en'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: Focus</label>
                                            <input type="text" class="form-control" name="hub_meta_focus_en" value="<?php echo htmlspecialchars((string)($settings['hub_meta_focus_en'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta: KPI</label>
                                            <input type="text" class="form-control" name="hub_meta_kpi_en" value="<?php echo htmlspecialchars((string)($settings['hub_meta_kpi_en'] ?? '')); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Hub-Kacheln</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addCard">Kachel hinzufügen</button>
                        </div>
                        <div class="card-body border-bottom bg-body-secondary">
                            <div class="small text-secondary" id="cardSchemaHint">Die Felder orientieren sich am gewählten Template-Profil. Jede normale Kachel kann direkt als Feature-Kachel in Vollbreite markiert werden.</div>
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
                            <?php if (!$isNew && !empty($settings['hub_slug'])): ?>
                                <a href="<?php echo htmlspecialchars(SITE_URL . '/' . ltrim((string)$settings['hub_slug'], '/')); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary w-100 mb-2">
                                    DE im neuen Tab öffnen
                                </a>
                                <a href="<?php echo htmlspecialchars(rtrim(SITE_URL . '/' . ltrim((string)$settings['hub_slug'], '/'), '/') . '/en'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary w-100 mb-2">
                                    EN im neuen Tab öffnen
                                </a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary w-100 mb-2"><?php echo $isNew ? 'Hub Site erstellen' : 'Hub Site aktualisieren'; ?></button>
                            <button type="button" class="btn btn-outline-primary w-100" id="saveAndOpenPublicButton"><?php echo $isNew ? 'Erstellen & Public Site öffnen' : 'Speichern & Public Site öffnen'; ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
