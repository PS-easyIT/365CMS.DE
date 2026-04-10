<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SYSTEM_VIEW')) {
    exit;
}

$providersData = is_array($data['providers'] ?? null) ? $data['providers'] : [];
$providers = is_array($providersData['providers'] ?? null) ? $providersData['providers'] : [];
$features = is_array($data['features'] ?? null) ? $data['features'] : [];
$translation = is_array($data['translation'] ?? null) ? $data['translation'] : [];
$logging = is_array($data['logging'] ?? null) ? $data['logging'] : [];
$quotas = is_array($data['quotas'] ?? null) ? $data['quotas'] : [];
$summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
$currentTab = $currentTab ?? 'overview';
$aiBaseUrl = '/admin/ai-services';
$tabs = [
    'overview' => 'Übersicht',
    'providers' => 'Provider',
    'features' => 'Feature-Gates',
    'translation' => 'Translation',
    'logging' => 'Logging',
    'quotas' => 'Quotas',
];
$providerProfiles = [
    'disabled' => 'Disabled',
    'beta' => 'Beta',
    'editor-translation' => 'Editor Translation',
    'content-assist' => 'Content Assist',
    'seo-assist' => 'SEO Assist',
];
$loggingModes = [
    'minimal' => 'Minimal',
    'technical' => 'Technical',
    'debug-no-content' => 'Debug ohne Rohinhalt',
];
$resultModes = [
    'preview' => 'Nur Preview / keine Persistenz',
    'localized-field' => 'In separates Sprachfeld zurückführen',
    'overwrite-current-draft' => 'Aktuellen Draft überschreiben',
];
$statusBadge = static fn (bool $condition): string => $condition ? 'success' : 'secondary';
$isCurrentTab = static fn (string $tab): bool => $currentTab === $tab;
$isSelected = static fn (string $value, string $expected): string => $value === $expected ? 'selected' : '';
$isChecked = static fn (bool $condition): string => $condition ? 'checked' : '';
$renderFormContext = static function (string $tab, string $action) use ($csrfToken): void {
    ?>
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken); ?>">
    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab, ENT_QUOTES); ?>">
    <input type="hidden" name="action" value="<?php echo htmlspecialchars($action, ENT_QUOTES); ?>">
    <?php
};
$renderBadge = static function (string $class, string $label): void {
    ?>
    <span class="badge bg-<?php echo htmlspecialchars($class, ENT_QUOTES); ?>-lt"><?php echo htmlspecialchars($label); ?></span>
    <?php
};
$renderMetricCard = static function (string $label, string $value, string $sub = ''): void {
    ?>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="subheader"><?php echo htmlspecialchars($label); ?></div>
                <div class="h1 mb-1"><?php echo htmlspecialchars($value); ?></div>
                <?php if ($sub !== ''): ?>
                    <div class="text-secondary small"><?php echo htmlspecialchars($sub); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
};
$renderSwitch = static function (string $name, string $label, bool $checked, string $hint = ''): void {
    ?>
    <label class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" name="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>" value="1" <?php echo $checked ? 'checked' : ''; ?>>
        <span class="form-check-label fw-medium"><?php echo htmlspecialchars($label); ?></span>
        <?php if ($hint !== ''): ?>
            <span class="form-hint d-block ms-0"><?php echo htmlspecialchars($hint); ?></span>
        <?php endif; ?>
    </label>
    <?php
};
$providerSecretFields = [
    'openai' => ['key' => 'openai_api_key', 'label' => 'API-Key'],
    'azure_openai' => ['key' => 'azure_openai_api_key', 'label' => 'API-Key / Deployment-Secret'],
    'openrouter' => ['key' => 'openrouter_api_key', 'label' => 'API-Key'],
];
?>

<div class="container-xl">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center g-3">
            <div class="col">
                <div class="page-pretitle">System</div>
                <h2 class="page-title">AI Services</h2>
                <div class="text-secondary mt-1">Zentrale Steuerung für Provider-Scope, Feature-Gates, Translation-Regeln, Logging-Modus und Quotas. Noch kein Chat-Zirkus – erstmal saubere Leitplanken.</div>
            </div>
            <div class="col-auto d-flex gap-2 flex-wrap">
                <?php $renderBadge($statusBadge(!empty($summary['translation_ready'])), !empty($summary['translation_ready']) ? 'Translation-Ready' : 'Noch nicht einsatzbereit'); ?>
                <?php $renderBadge(!empty($features['ai_services_enabled']) ? 'success' : 'secondary', !empty($features['ai_services_enabled']) ? 'AI Services aktiv' : 'AI Services aus'); ?>
            </div>
        </div>
    </div>

    <?php $alertData = is_array($alert ?? null) ? $alert : []; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?>

    <?php if (!empty($data['error'])): ?>
        <div class="alert alert-warning mb-4"><?php echo htmlspecialchars((string) $data['error']); ?></div>
    <?php endif; ?>

    <div class="mb-4">
        <ul class="nav nav-tabs">
            <?php foreach ($tabs as $tab => $label): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $isCurrentTab($tab) ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($aiBaseUrl . '?tab=' . rawurlencode($tab)); ?>"><?php echo htmlspecialchars($label); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if ($isCurrentTab('overview')): ?>
        <div class="row row-cards mb-4">
            <?php $renderMetricCard('Aktive Provider', (string) ((int) ($summary['provider_enabled'] ?? 0)) . ' / ' . (string) ((int) ($summary['provider_total'] ?? count($providers))), 'konfigurierter Provider-Pool'); ?>
            <?php $renderMetricCard('Aktive Gates', (string) (int) ($summary['feature_enabled'] ?? 0), 'globale Feature-Freigaben'); ?>
            <?php $renderMetricCard('Target-Locale', (string) (int) ($summary['target_locale_count'] ?? 0), 'erlaubte Zielsprachen'); ?>
            <?php $renderMetricCard('Request-Limit', (string) (int) ($summary['quota_chars'] ?? 0), 'max. Zeichen pro Lauf'); ?>
        </div>

        <div class="row row-cards">
            <div class="col-12 col-xl-7">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Aktueller Umsetzungsstand</h3></div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small text-secondary">
                            <li class="mb-2">✅ Admin-Seite für AI Services ist unter <code>/admin/ai-services</code> eingehängt.</li>
                            <li class="mb-2">✅ Persistenz für <code>ai.providers</code>, <code>ai.features</code>, <code>ai.translation</code>, <code>ai.logging</code> und <code>ai.quotas</code> ist vorhanden.</li>
                            <li class="mb-2">✅ Provider-Gateway, integrierter <code>mock</code>-Provider und geschützter Endpoint <code>/admin/ai-translate-editorjs</code> sind aktiv.</li>
                            <li class="mb-2">✅ Post- und Page-Editoren können Editor.js-Inhalte jetzt testweise über eine Mock-Pipeline von DE nach EN zurück in das lokalisierte EN-Feld führen.</li>
                            <li class="mb-2">✅ Vor der EN-Übernahme gibt es jetzt einen echten Preview-/Diff-Schritt direkt im Editor statt nur eines simplen Confirm-Dialogs.</li>
                            <li class="mb-2">✅ Provider-Secrets werden verschlüsselt in der vorhandenen Settings-Tabelle gehalten.</li>
                            <li class="mb-2">⚠️ Echte externe Live-Provider-Adapter und Requests sind weiterhin noch nicht implementiert.</li>
                            <li>⚠️ Feingranulare Capabilities, Teilfehler-/Retry-UX und produktive Daily-/Monthly-Quota-Erzwingung sind noch nicht vollständig durchgezogen.</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Readiness-Check</h3></div>
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-7">Master-Schalter</dt>
                            <dd class="col-5"><?php $renderBadge(!empty($features['ai_services_enabled']) ? 'success' : 'secondary', !empty($features['ai_services_enabled']) ? 'an' : 'aus'); ?></dd>
                            <dt class="col-7">Translation-Gate</dt>
                            <dd class="col-5"><?php $renderBadge(!empty($features['ai_translation_enabled']) ? 'success' : 'secondary', !empty($features['ai_translation_enabled']) ? 'an' : 'aus'); ?></dd>
                            <dt class="col-7">Editor.js-Gate</dt>
                            <dd class="col-5"><?php $renderBadge(!empty($features['ai_editorjs_enabled']) ? 'success' : 'secondary', !empty($features['ai_editorjs_enabled']) ? 'an' : 'aus'); ?></dd>
                            <dt class="col-7">Translation-fähige Provider</dt>
                            <dd class="col-5"><?php echo (int) ($summary['translation_ready_provider_count'] ?? 0); ?></dd>
                            <dt class="col-7">Logging-Modus</dt>
                            <dd class="col-5"><?php echo htmlspecialchars((string) ($summary['logging_mode'] ?? 'technical')); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Kanonische Doku</h3></div>
                    <div class="card-body text-secondary small">
                        Die fachliche Hauptdoku liegt in <code>DOC/ai/AI-SERVICES.md</code>. Diese Admin-Seite bildet den aktuellen Settings- und Persistenzrahmen im Core ab – nicht die spätere Provider-Ausführung.
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($isCurrentTab('providers')): ?>
        <form method="post">
            <?php $renderFormContext('providers', 'save_providers'); ?>
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                            <h3 class="card-title mb-0">Provider-Steuerung</h3>
                            <button type="submit" class="btn btn-primary">Provider speichern</button>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Aktiver Standard-Provider</label>
                                    <select class="form-select" name="active_provider">
                                        <?php foreach ($providers as $providerSlug => $provider): ?>
                                            <option value="<?php echo htmlspecialchars((string) $providerSlug, ENT_QUOTES); ?>" <?php echo $isSelected((string) ($providersData['active_provider'] ?? 'openai'), (string) $providerSlug); ?>><?php echo htmlspecialchars((string) ($provider['label'] ?? $providerSlug)); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Fallback-Provider</label>
                                    <select class="form-select" name="fallback_provider">
                                        <?php foreach ($providers as $providerSlug => $provider): ?>
                                            <option value="<?php echo htmlspecialchars((string) $providerSlug, ENT_QUOTES); ?>" <?php echo $isSelected((string) ($providersData['fallback_provider'] ?? 'azure_openai'), (string) $providerSlug); ?>><?php echo htmlspecialchars((string) ($provider['label'] ?? $providerSlug)); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php foreach ($providers as $providerSlug => $provider): ?>
                    <?php $providerLabel = (string) ($provider['label'] ?? $providerSlug); ?>
                    <div class="col-12 col-xl-6">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center gap-3">
                                <h3 class="card-title mb-0"><?php echo htmlspecialchars($providerLabel); ?></h3>
                                <?php $renderBadge(!empty($provider['enabled']) ? 'success' : 'secondary', !empty($provider['enabled']) ? 'aktiv' : 'aus'); ?>
                            </div>
                            <div class="card-body">
                                <?php $renderSwitch($providerSlug . '_enabled', 'Provider aktivieren', !empty($provider['enabled']), 'Steuert, ob der Provider grundsätzlich für AI Services berücksichtigt wird.'); ?>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Betriebsprofil</label>
                                        <select class="form-select" name="<?php echo htmlspecialchars($providerSlug . '_profile', ENT_QUOTES); ?>">
                                            <?php foreach ($providerProfiles as $profileValue => $profileLabel): ?>
                                                <option value="<?php echo htmlspecialchars($profileValue, ENT_QUOTES); ?>" <?php echo $isSelected((string) ($provider['profile'] ?? 'disabled'), $profileValue); ?>><?php echo htmlspecialchars($profileLabel); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Default-Modell</label>
                                        <input type="text" class="form-control" name="<?php echo htmlspecialchars($providerSlug . '_default_model', ENT_QUOTES); ?>" value="<?php echo htmlspecialchars((string) ($provider['default_model'] ?? '')); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Endpoint</label>
                                        <input type="url" class="form-control" name="<?php echo htmlspecialchars($providerSlug . '_endpoint', ENT_QUOTES); ?>" value="<?php echo htmlspecialchars((string) ($provider['endpoint'] ?? '')); ?>" placeholder="https://...">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Allowed Locales</label>
                                        <input type="text" class="form-control" name="<?php echo htmlspecialchars($providerSlug . '_allowed_locales', ENT_QUOTES); ?>" value="<?php echo htmlspecialchars(implode(',', (array) ($provider['allowed_locales'] ?? ['en']))); ?>" placeholder="en,de">
                                        <div class="form-hint">Kommagetrennte Liste, z. B. <code>en,de</code>.</div>
                                    </div>
                                </div>

                                <hr>
                                <div class="row g-2">
                                    <div class="col-md-6"><?php $renderSwitch($providerSlug . '_translation_enabled', 'Translation', !empty($provider['translation_enabled'])); ?></div>
                                    <div class="col-md-6"><?php $renderSwitch($providerSlug . '_rewrite_enabled', 'Rewrite', !empty($provider['rewrite_enabled'])); ?></div>
                                    <div class="col-md-6"><?php $renderSwitch($providerSlug . '_summary_enabled', 'Summaries', !empty($provider['summary_enabled'])); ?></div>
                                    <div class="col-md-6"><?php $renderSwitch($providerSlug . '_seo_meta_enabled', 'SEO / Meta', !empty($provider['seo_meta_enabled'])); ?></div>
                                    <div class="col-md-6"><?php $renderSwitch($providerSlug . '_editorjs_enabled', 'Editor.js', !empty($provider['editorjs_enabled'])); ?></div>
                                    <div class="col-md-6"><?php $renderSwitch($providerSlug . '_beta_only', 'Nur Beta', !empty($provider['beta_only'])); ?></div>
                                </div>

                                <?php if (isset($providerSecretFields[$providerSlug])): ?>
                                    <?php $secretField = $providerSecretFields[$providerSlug]; ?>
                                    <hr>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label"><?php echo htmlspecialchars((string) $secretField['label']); ?></label>
                                            <input type="password" class="form-control" name="<?php echo htmlspecialchars((string) $secretField['key'], ENT_QUOTES); ?>" value="" placeholder="Leer lassen = gespeichertes Secret behalten">
                                            <div class="form-hint">Aktuell gespeichert: <?php echo !empty($provider['secret_configured']) ? 'Ja' : 'Nein'; ?></div>
                                            <label class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="<?php echo htmlspecialchars('clear_' . (string) $secretField['key'], ENT_QUOTES); ?>" value="1">
                                                <span class="form-check-label">Gespeichertes Secret löschen</span>
                                            </label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Provider speichern</button>
                </div>
            </div>
        </form>
    <?php elseif ($isCurrentTab('features')): ?>
        <form method="post" class="card">
            <?php $renderFormContext('features', 'save_features'); ?>
            <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <h3 class="card-title mb-0">Globale Feature-Gates</h3>
                <button type="submit" class="btn btn-primary">Feature-Gates speichern</button>
            </div>
            <div class="card-body">
                <div class="row row-cards">
                    <div class="col-md-6">
                        <?php $renderSwitch('ai_services_enabled', 'AI Services global aktivieren', !empty($features['ai_services_enabled']), 'Master-Schalter für alle AI-bezogenen Workflows.'); ?>
                        <?php $renderSwitch('ai_translation_enabled', 'Übersetzung erlauben', !empty($features['ai_translation_enabled'])); ?>
                        <?php $renderSwitch('ai_editorjs_enabled', 'Editor.js-Integration erlauben', !empty($features['ai_editorjs_enabled'])); ?>
                    </div>
                    <div class="col-md-6">
                        <?php $renderSwitch('ai_rewrite_enabled', 'Rewrite erlauben', !empty($features['ai_rewrite_enabled'])); ?>
                        <?php $renderSwitch('ai_summary_enabled', 'Zusammenfassungen erlauben', !empty($features['ai_summary_enabled'])); ?>
                        <?php $renderSwitch('ai_seo_meta_enabled', 'SEO-/Meta-Helfer erlauben', !empty($features['ai_seo_meta_enabled'])); ?>
                    </div>
                </div>
            </div>
        </form>
    <?php elseif ($isCurrentTab('translation')): ?>
        <form method="post" class="card">
            <?php $renderFormContext('translation', 'save_translation'); ?>
            <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <h3 class="card-title mb-0">Translation-Profil</h3>
                <button type="submit" class="btn btn-primary">Translation-Einstellungen speichern</button>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Standard-Quellsprache</label>
                        <input type="text" class="form-control" name="default_source_locale" value="<?php echo htmlspecialchars((string) ($translation['default_source_locale'] ?? 'de')); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Standard-Zielsprache</label>
                        <input type="text" class="form-control" name="default_target_locale" value="<?php echo htmlspecialchars((string) ($translation['default_target_locale'] ?? 'en')); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Erlaubte Zielsprachen</label>
                        <input type="text" class="form-control" name="allowed_target_locales" value="<?php echo htmlspecialchars(implode(',', (array) ($translation['allowed_target_locales'] ?? ['en']))); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Unterstützte Blocktypen</label>
                        <input type="text" class="form-control" name="supported_block_types" value="<?php echo htmlspecialchars(implode(',', (array) ($translation['supported_block_types'] ?? []))); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Ergebnis-Modus</label>
                        <select class="form-select" name="result_mode">
                            <?php foreach ($resultModes as $modeValue => $modeLabel): ?>
                                <option value="<?php echo htmlspecialchars($modeValue, ENT_QUOTES); ?>" <?php echo $isSelected((string) ($translation['result_mode'] ?? 'localized-field'), $modeValue); ?>><?php echo htmlspecialchars($modeLabel); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4"><?php $renderSwitch('preview_required', 'Preview vor Übernahme', !empty($translation['preview_required'])); ?></div>
                    <div class="col-md-4"><?php $renderSwitch('preserve_unsupported_blocks', 'Nicht unterstützte Blöcke bewahren', !empty($translation['preserve_unsupported_blocks'])); ?></div>
                    <div class="col-md-4"><?php $renderSwitch('skip_html_blocks', 'HTML-/Raw-Blöcke überspringen', !empty($translation['skip_html_blocks'])); ?></div>
                </div>
            </div>
        </form>
    <?php elseif ($isCurrentTab('logging')): ?>
        <form method="post" class="card">
            <?php $renderFormContext('logging', 'save_logging'); ?>
            <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <h3 class="card-title mb-0">Logging & Audit</h3>
                <button type="submit" class="btn btn-primary">Logging speichern</button>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Logging-Modus</label>
                        <select class="form-select" name="logging_mode">
                            <?php foreach ($loggingModes as $modeValue => $modeLabel): ?>
                                <option value="<?php echo htmlspecialchars($modeValue, ENT_QUOTES); ?>" <?php echo $isSelected((string) ($logging['logging_mode'] ?? 'technical'), $modeValue); ?>><?php echo htmlspecialchars($modeLabel); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Aufbewahrung (Tage)</label>
                        <input type="number" class="form-control" name="retention_days" min="1" max="3650" value="<?php echo (int) ($logging['retention_days'] ?? 30); ?>">
                    </div>
                    <div class="col-md-6"><?php $renderSwitch('store_content_hashes', 'Content-Hashes speichern', !empty($logging['store_content_hashes'])); ?></div>
                    <div class="col-md-6"><?php $renderSwitch('store_request_metrics', 'Request-Metriken speichern', !empty($logging['store_request_metrics'])); ?></div>
                    <div class="col-md-6"><?php $renderSwitch('store_error_context', 'Fehlerkontext speichern', !empty($logging['store_error_context'])); ?></div>
                    <div class="col-md-6"><?php $renderSwitch('store_prompt_preview', 'Prompt-Preview speichern', !empty($logging['store_prompt_preview']), 'Nur mit Vorsicht – weiterhin ohne Rohinhalt empfohlen.'); ?></div>
                </div>
            </div>
        </form>
    <?php else: ?>
        <form method="post" class="card">
            <?php $renderFormContext('quotas', 'save_quotas'); ?>
            <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <h3 class="card-title mb-0">Quotas & technische Limits</h3>
                <button type="submit" class="btn btn-primary">Quotas speichern</button>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Max. Zeichen pro Request</label>
                        <input type="number" class="form-control" name="max_chars_per_request" min="250" max="250000" value="<?php echo (int) ($quotas['max_chars_per_request'] ?? 12000); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Max. Blöcke pro Request</label>
                        <input type="number" class="form-control" name="max_blocks_per_request" min="1" max="500" value="<?php echo (int) ($quotas['max_blocks_per_request'] ?? 40); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Timeout (Sekunden)</label>
                        <input type="number" class="form-control" name="timeout_seconds" min="5" max="300" value="<?php echo (int) ($quotas['timeout_seconds'] ?? 25); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Retry Count</label>
                        <input type="number" class="form-control" name="retry_count" min="0" max="10" value="<?php echo (int) ($quotas['retry_count'] ?? 1); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Requests pro Nutzer / Tag</label>
                        <input type="number" class="form-control" name="daily_requests_per_user" min="1" max="5000" value="<?php echo (int) ($quotas['daily_requests_per_user'] ?? 40); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Zeichen pro Nutzer / Tag</label>
                        <input type="number" class="form-control" name="daily_chars_per_user" min="500" max="2000000" value="<?php echo (int) ($quotas['daily_chars_per_user'] ?? 120000); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Requests pro Provider / Monat</label>
                        <input type="number" class="form-control" name="monthly_requests_per_provider" min="10" max="1000000" value="<?php echo (int) ($quotas['monthly_requests_per_provider'] ?? 5000); ?>">
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>