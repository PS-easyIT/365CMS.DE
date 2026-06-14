<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_AI_VIEW')) {
    exit;
}

$providersData = is_array($data['providers'] ?? null) ? $data['providers'] : [];
$providers = array_values(array_filter(
    (array) ($providersData['entries'] ?? []),
    static fn (mixed $entry): bool => is_array($entry)
));
$providerCatalog = is_array($providersData['catalog'] ?? null) ? $providersData['catalog'] : [];
$providerCatalogAddable = array_filter(
    $providerCatalog,
    static fn (array $entry): bool => !empty($entry['addable'])
);
$providerOptions = [];
foreach ($providers as $provider) {
    $providerId = (string) ($provider['id'] ?? '');
    if ($providerId === '') {
        continue;
    }

    $providerOptions[$providerId] = $provider;
}
$activeProviderId = (string) ($providersData['active_provider_id'] ?? '');
$activeProviderLabel = (string) (($providerOptions[$activeProviderId]['label'] ?? '') ?: '—');
$features = is_array($data['features'] ?? null) ? $data['features'] : [];
$translation = is_array($data['translation'] ?? null) ? $data['translation'] : [];
$logging = is_array($data['logging'] ?? null) ? $data['logging'] : [];
$quotas = is_array($data['quotas'] ?? null) ? $data['quotas'] : [];
$prompts = is_array($data['prompts'] ?? null) ? $data['prompts'] : [];
$translationPromptTemplate = is_array($prompts['translation'] ?? null) ? $prompts['translation'] : [];
$contentPromptTemplate = is_array($prompts['content_creator'] ?? null) ? $prompts['content_creator'] : [];
$seoPromptTemplate = is_array($prompts['seo_creator'] ?? null) ? $prompts['seo_creator'] : [];
$summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
$monitoring = is_array($data['monitoring'] ?? null) ? $data['monitoring'] : [];
$currentUserMonitoring = is_array($monitoring['current_user'] ?? null) ? $monitoring['current_user'] : [];
$activeProviderMonitoring = is_array($monitoring['active_provider'] ?? null) ? $monitoring['active_provider'] : [];
$providerBreakdown = array_values(array_filter(
    (array) ($monitoring['provider_breakdown'] ?? []),
    static fn (mixed $entry): bool => is_array($entry)
));
$generationHistory = array_values(array_filter(
    (array) ($data['generation_history'] ?? []),
    static fn (mixed $entry): bool => is_array($entry)
));
$contentGenerationResult = is_array($data['content_result'] ?? null) ? $data['content_result'] : [];
$seoGenerationResult = is_array($data['seo_result'] ?? null) ? $data['seo_result'] : [];
$currentSection = $currentSection ?? 'overview';
$navItems = [
    'overview' => ['label' => 'Dashboard', 'url' => '/admin/ai-services'],
    'translation' => ['label' => 'Übersetzung', 'url' => '/admin/ai-translation'],
    'content_creator' => ['label' => 'Inhaltsassistent', 'url' => '/admin/ai-content-creator'],
    'seo_creator' => ['label' => 'SEO-Assistent', 'url' => '/admin/ai-seo-creator'],
    'settings' => ['label' => 'Einstellungen', 'url' => '/admin/ai-settings'],
];
$providerProfiles = [
    'disabled' => 'Disabled',
    'beta' => 'Beta',
    'editor-translation' => 'Editor Translation',
    'content-assist' => 'Content Assist',
    'seo-assist' => 'SEO Assist',
];
$providerCatalogJson = (string) json_encode($providerCatalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
$isCurrentSection = static fn (string $section): bool => $currentSection === $section;
$isSelected = static fn (string $value, string $expected): string => $value === $expected ? 'selected' : '';
$renderFormContext = static function (string $action) use ($csrfToken): void {
    ?>
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken); ?>">
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
$renderPromptTemplateForm = static function (string $action, array $template, string $title, string $description) use ($renderFormContext, $renderSwitch): void {
    $placeholders = array_values(array_filter(array_map('strval', (array) ($template['placeholders'] ?? []))));
    ?>
    <form method="post" class="card h-100">
        <?php $renderFormContext($action); ?>
        <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <h3 class="card-title mb-1"><?php echo htmlspecialchars($title); ?></h3>
                <div class="text-secondary small"><?php echo htmlspecialchars($description); ?></div>
            </div>
            <button type="submit" class="btn btn-primary">Vorlage speichern</button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Anzeigename</label>
                    <input type="text" class="form-control" name="prompt_label" maxlength="120" value="<?php echo htmlspecialchars((string) ($template['label'] ?? ''), ENT_QUOTES); ?>">
                </div>
                <div class="col-md-5 d-flex align-items-end">
                    <?php $renderSwitch('prompt_enabled', 'Vorlage aktiv verwenden', !empty($template['enabled']), 'Bei Translation wirkt die Vorlage direkt in der Live-Pipeline; Content/SEO sind für kommende Generatoren vorbereitet.'); ?>
                </div>
                <div class="col-12">
                    <label class="form-label">System-Prompt / Leitplanken</label>
                    <textarea class="form-control" name="system_prompt" rows="6" maxlength="4000" spellcheck="false"><?php echo htmlspecialchars((string) ($template['system_prompt'] ?? '')); ?></textarea>
                    <div class="form-hint">Serverseitig werden zusätzliche Pflichtregeln gegen Prompt Injection, Secret-Leaks und System-Prompt-Offenlegung angehängt.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">User-Template / strukturierte Nutzdaten</label>
                    <textarea class="form-control" name="user_template" rows="6" maxlength="4000" spellcheck="false"><?php echo htmlspecialchars((string) ($template['user_template'] ?? '')); ?></textarea>
                    <?php if ($placeholders !== []): ?>
                        <div class="form-hint">Platzhalter: <?php echo htmlspecialchars(implode(', ', $placeholders)); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label">Interne Notiz</label>
                    <textarea class="form-control" name="prompt_notes" rows="3" maxlength="1000"><?php echo htmlspecialchars((string) ($template['notes'] ?? '')); ?></textarea>
                    <div class="form-hint">Notizen werden gespeichert, aber nicht an Provider gesendet.</div>
                </div>
                <div class="col-12">
                    <div class="alert alert-info mb-0 small">
                        Best-Practice: Instruktionen und Nutzdaten getrennt halten. Keine API-Keys, personenbezogenen Details oder Rohprompts in Notizen oder Logs ablegen.
                    </div>
                </div>
            </div>
        </div>
    </form>
    <?php
};
$translationReadyProviders = array_values(array_filter($providers, static fn (array $provider): bool => !empty($provider['enabled']) && !empty($provider['translation_enabled'])));
$contentAssistProviders = array_values(array_filter($providers, static fn (array $provider): bool => !empty($provider['enabled']) && (!empty($provider['rewrite_enabled']) || !empty($provider['summary_enabled']))));
$seoAssistProviders = array_values(array_filter($providers, static fn (array $provider): bool => !empty($provider['enabled']) && !empty($provider['seo_meta_enabled'])));
$showAiSubtitle = $isCurrentSection('overview');
$aiBlockingBadges = [];
if (empty($features['ai_services_enabled'])) {
    $aiBlockingBadges[] = 'KI global deaktiviert';
}
if (empty($summary['translation_ready'])) {
    $aiBlockingBadges[] = 'Übersetzung blockiert';
}
?>

<div class="container-xl">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center g-3">
            <div class="col">
                <?php if ($showAiSubtitle): ?>
                    <div class="page-pretitle">KI-Dienste</div>
                <?php endif; ?>
                <h2 class="page-title"><?php echo htmlspecialchars((string) ($navItems[$currentSection]['label'] ?? 'AI Services')); ?></h2>
                <?php if ($showAiSubtitle): ?>
                    <div class="text-secondary mt-1">Eigener Admin-Bereich für KI-Workflows, Provider-Leitplanken und produktive Übergaben zwischen Übersetzung, Inhalts- und SEO-Assistenten.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php $alertData = is_array($alert ?? null) ? $alert : []; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?>

    <?php if (!empty($data['error'])): ?>
        <div class="alert alert-warning mb-4"><?php echo htmlspecialchars((string) $data['error']); ?></div>
    <?php endif; ?>

    <div class="mb-4 d-flex flex-wrap gap-2">
        <?php foreach ($navItems as $section => $item): ?>
            <a class="btn <?php echo $isCurrentSection($section) ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo htmlspecialchars((string) $item['url'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars((string) $item['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($aiBlockingBadges !== []): ?>
        <div class="alert alert-warning py-2 mb-4">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <strong>Blocker</strong>
                <?php foreach ($aiBlockingBadges as $badgeLabel): ?>
                    <span class="badge bg-warning text-dark"><?php echo htmlspecialchars((string) $badgeLabel); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($isCurrentSection('overview')): ?>
        <div class="row row-cards mb-4">
            <?php $renderMetricCard('Aktiver Provider', $activeProviderLabel, 'Single-Provider-Modus'); ?>
            <?php $renderMetricCard('Aktive Gates', (string) (int) ($summary['feature_enabled'] ?? 0), 'globale Feature-Freigaben'); ?>
            <?php $renderMetricCard('Translation-Provider', (string) count($translationReadyProviders), 'für DE → EN nutzbar'); ?>
            <?php $renderMetricCard('Prompt-Vorlagen', (string) (int) ($summary['prompt_templates_enabled'] ?? 0) . ' / 3', 'aktiv verwaltete Bereiche'); ?>
        </div>

        <?php if ($monitoring !== []): ?>
            <div class="row row-cards mb-4">
                <?php $renderMetricCard('AI-Läufe · 24h', (string) (int) ($monitoring['runs_24h'] ?? 0), ((int) ($monitoring['failures_24h'] ?? 0)) > 0 ? (string) (int) ($monitoring['failures_24h'] ?? 0) . ' fehlgeschlagen' : 'keine Fehler protokolliert'); ?>
                <?php $renderMetricCard('Erfolgsquote · 30 Tage', (string) (int) ($monitoring['success_rate_30d'] ?? 0) . ' %', (string) ((int) ($monitoring['successes_30d'] ?? 0) + (int) ($monitoring['failures_30d'] ?? 0)) . ' dokumentierte Läufe'); ?>
                <?php $renderMetricCard('Dein Tagesbudget', (string) (int) ($currentUserMonitoring['requests_24h'] ?? 0) . ' / ' . (string) max(0, (int) ($currentUserMonitoring['request_limit'] ?? 0)), 'Requests der letzten 24 Stunden'); ?>
                <?php $renderMetricCard('Ø Laufzeit · 30 Tage', (string) (int) ($monitoring['avg_duration_ms_30d'] ?? 0) . ' ms', 'auf Basis erfolgreicher Läufe'); ?>
            </div>

            <?php if (!empty($monitoring['load_error'])): ?>
                <div class="alert alert-warning mb-4"><?php echo htmlspecialchars((string) $monitoring['load_error']); ?></div>
            <?php endif; ?>

            <?php if (empty($monitoring['metrics_logging_enabled'])): ?>
                <div class="alert alert-info mb-4">
                    Request-Metriken sind derzeit nicht vollständig aktiviert. Das Dashboard zeigt deshalb bewusst nur request- und quota-nahe Nutzungsdaten; Rohprompts, Volltexte und exakte Tokenkosten bleiben ausgeblendet.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="row row-cards">
            <div class="col-12 col-xl-7">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">Aktueller Umsetzungsstand</h3>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#aiImplementationStatus" aria-expanded="false" aria-controls="aiImplementationStatus">Details</button>
                    </div>
                    <div class="card-body collapse show" id="aiImplementationStatus">
                        <ul class="list-unstyled mb-0 small text-secondary">
                            <li class="mb-2">✅ AI Services laufen jetzt als eigener Hauptbereich mit separaten Unterseiten.</li>
                            <li class="mb-2">✅ Provider-, Feature-, Translation-, Logging- und Quota-Persistenz ist im Core verdrahtet.</li>
                            <li class="mb-2">✅ Provider erscheinen jetzt nur noch als bewusst angelegte Liste statt als starre Komplettmatrix.</li>
                            <li class="mb-2">✅ Der Editor.js-Übersetzungs-Endpoint bleibt geschützt und an die zentralen Feature-Gates gekoppelt.</li>
                            <li class="mb-2">✅ Ollama, Azure AI, OpenAI, Mistral AI und OpenRouter sind als echte Live-Provider im Gateway verdrahtet.</li>
                            <li class="mb-2">✅ Translation, Content-Assist und SEO-Assist lassen sich auf Provider-Ebene getrennt schalten.</li>
                            <li class="mb-2">✅ Das AI-Dashboard zeigt jetzt request- und quota-nahe Nutzungsdaten sowie letzte Generierungsläufe aus dem Audit-Log, ohne Rohprompts oder Volltexte offenzulegen.</li>
                            <li class="mb-2">✅ Prompt-Vorlagen lassen sich je Bereich verwalten; die Translation-Vorlage wirkt direkt in der Live-Pipeline und bleibt durch serverseitige Pflicht-Leitplanken abgesichert.</li>
                            <li class="mb-2">✅ Content- und SEO-Generatoren liefern serverseitige Preview-Ausgaben über dieselben Provider-Gates wie Translation.</li>
                            <li>⚠️ Feingranulare Daily-/Monthly-Quota-Erzwingung und echte providerübergreifende Tokenkosten bleiben Follow-up-Arbeit, solange Live-Provider ihre Usage-Daten nicht konsistent zurückmelden.</li>
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
                            <dt class="col-7">Content-Assist</dt>
                            <dd class="col-5"><?php echo count($contentAssistProviders); ?> Provider</dd>
                            <dt class="col-7">SEO-Assist</dt>
                            <dd class="col-5"><?php echo count($seoAssistProviders); ?> Provider</dd>
                            <dt class="col-7">Logging-Modus</dt>
                            <dd class="col-5"><?php echo htmlspecialchars((string) ($summary['logging_mode'] ?? 'technical')); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between gap-2">
                        <h3 class="card-title mb-0">Kanonische Doku</h3>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#aiCanonicalDocs" aria-expanded="false" aria-controls="aiCanonicalDocs">Ein-/ausklappen</button>
                    </div>
                    <div class="card-body text-secondary small collapse show" id="aiCanonicalDocs">
                        Die fachliche Hauptdoku liegt in <code>DOC/ai/AI-SERVICES.md</code>. Dieser Bereich bildet den aktuellen Settings-, Routing-, Monitoring- und Readiness-Rahmen im Core ab.
                    </div>
                </div>
            </div>
        </div>

        <?php if ($monitoring !== [] || $generationHistory !== []): ?>
            <div class="row row-cards mt-4">
                <div class="col-12 col-xl-5">
                    <div class="card h-100">
                        <div class="card-header"><h3 class="card-title">Nutzungsmonitoring & Kontingente</h3></div>
                        <div class="card-body">
                            <dl class="row mb-4 small">
                                <dt class="col-7">Dein Tagesbudget</dt>
                                <dd class="col-5"><?php echo (int) ($currentUserMonitoring['requests_24h'] ?? 0); ?> / <?php echo (int) ($currentUserMonitoring['request_limit'] ?? 0); ?> Requests</dd>
                                <dt class="col-7">Dein Zeichenbudget</dt>
                                <dd class="col-5">
                                    <?php if (!empty($currentUserMonitoring['char_metrics_available'])): ?>
                                        <?php echo number_format((int) ($currentUserMonitoring['chars_24h'] ?? 0), 0, ',', '.'); ?> / <?php echo number_format((int) ($currentUserMonitoring['char_limit'] ?? 0), 0, ',', '.'); ?>
                                    <?php else: ?>
                                        <span class="text-secondary">noch keine Zeichenmetriken</span>
                                    <?php endif; ?>
                                </dd>
                                <dt class="col-7">Aktiver Provider</dt>
                                <dd class="col-5"><?php echo htmlspecialchars((string) ($activeProviderMonitoring['provider_label'] ?? '—')); ?></dd>
                                <dt class="col-7">Provider-Budget · 30 Tage</dt>
                                <dd class="col-5"><?php echo (int) ($activeProviderMonitoring['requests_30d'] ?? 0); ?> / <?php echo (int) ($activeProviderMonitoring['request_limit'] ?? 0); ?></dd>
                            </dl>

                            <div class="text-secondary small mb-2">Top-Provider der letzten 30 Tage</div>
                            <div class="table-responsive">
                                <table class="table table-sm table-vcenter mb-0">
                                    <thead>
                                        <tr>
                                            <th>Provider</th>
                                            <th>Requests</th>
                                            <th>Blöcke</th>
                                            <th>Zeichen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($providerBreakdown === []): ?>
                                            <tr><td colspan="4" class="text-center text-secondary py-3">Noch keine protokollierten AI-Läufe im 30-Tage-Fenster.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($providerBreakdown as $providerStats): ?>
                                                <tr>
                                                    <td class="fw-semibold"><?php echo htmlspecialchars((string) ($providerStats['provider_label'] ?? '—')); ?></td>
                                                    <td><?php echo (int) ($providerStats['requests_30d'] ?? 0); ?></td>
                                                    <td><?php echo number_format((int) ($providerStats['blocks_30d'] ?? 0), 0, ',', '.'); ?></td>
                                                    <td>
                                                        <?php if (!empty($providerStats['char_metrics_available'])): ?>
                                                            <?php echo number_format((int) ($providerStats['chars_30d'] ?? 0), 0, ',', '.'); ?>
                                                        <?php else: ?>
                                                            <span class="text-secondary">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-secondary small mt-3 mb-0">Es werden bewusst keine Rohprompts, Inhaltsblöcke oder Secrets angezeigt – nur betriebsnahe Audit-Metadaten.</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-7">
                    <div class="card h-100">
                        <div class="card-header"><h3 class="card-title">Letzte AI-Läufe</h3></div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-striped">
                                <thead>
                                    <tr>
                                        <th>Zeitpunkt</th>
                                        <th>Status</th>
                                        <th>Benutzer</th>
                                        <th>Provider</th>
                                        <th>Ziel</th>
                                        <th>Dauer</th>
                                        <th>Blöcke / Zeichen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($generationHistory === []): ?>
                                        <tr><td colspan="7" class="text-center text-secondary py-4">Noch keine Generierungsläufe protokolliert.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($generationHistory as $historyEntry): ?>
                                            <tr>
                                                <td class="text-secondary small"><?php echo htmlspecialchars((string) ($historyEntry['created_at'] ?? '—')); ?></td>
                                                <td>
                                                    <?php $renderBadge((string) ($historyEntry['status'] ?? 'secondary'), (string) ($historyEntry['status_label'] ?? 'unbekannt')); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars((string) ($historyEntry['user_label'] ?? '—')); ?></td>
                                                <td>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars((string) ($historyEntry['provider_label'] ?? '—')); ?></div>
                                                    <div class="text-secondary small"><?php echo htmlspecialchars((string) ($historyEntry['selection_mode'] ?? 'single-provider')); ?></div>
                                                </td>
                                                <td><?php echo htmlspecialchars((string) ($historyEntry['target_locale'] ?? '—')); ?></td>
                                                <td>
                                                    <?php if (($historyEntry['duration_ms'] ?? null) !== null): ?>
                                                        <?php echo number_format((int) $historyEntry['duration_ms'], 0, ',', '.'); ?> ms
                                                    <?php else: ?>
                                                        <span class="text-secondary">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo number_format((int) ($historyEntry['translated_blocks'] ?? 0), 0, ',', '.'); ?>
                                                    /
                                                    <?php if (($historyEntry['char_count'] ?? null) !== null): ?>
                                                        <?php echo number_format((int) $historyEntry['char_count'], 0, ',', '.'); ?>
                                                    <?php else: ?>
                                                        <span class="text-secondary">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php elseif ($isCurrentSection('translation')): ?>
        <div class="row row-cards mb-4">
            <?php $renderMetricCard('Default-Quelle', (string) ($translation['default_source_locale'] ?? 'de'), 'typisch DE'); ?>
            <?php $renderMetricCard('Default-Ziel', (string) ($translation['default_target_locale'] ?? 'en'), 'typisch EN'); ?>
            <?php $renderMetricCard('Zielsprachen', (string) count((array) ($translation['allowed_target_locales'] ?? ['en'])), 'erlaubte Locales'); ?>
            <?php $renderMetricCard('Translation-Provider', (string) count($translationReadyProviders), 'aktiv & berechtigt'); ?>
        </div>

        <div class="row row-cards">
            <div class="col-12 col-xl-8">
                <form method="post" class="card h-100">
                    <?php $renderFormContext('save_translation'); ?>
                    <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                        <h3 class="card-title mb-0">Übersetzungsprofil</h3>
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
                                <div class="form-text">Aktive EditorJS-Blocktypen sind u. a. paragraph, header, list/checklist, image, table, attaches, linkTool, warning, alert, accordion, imageGallery und mediaText. Strukturblöcke wie spacer, delimiter, embed, code und raw werden erhalten; übersetzt werden nur sichere Textsegmente.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Ergebnis-Modus</label>
                                <select class="form-select" name="result_mode">
                                    <?php foreach ($resultModes as $modeValue => $modeLabel): ?>
                                        <option value="<?php echo htmlspecialchars($modeValue, ENT_QUOTES); ?>" <?php echo $isSelected((string) ($translation['result_mode'] ?? 'localized-field'), $modeValue); ?>><?php echo htmlspecialchars($modeLabel); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <input type="hidden" name="preview_required" value="1">
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    Generierte Übersetzungen werden immer erst als Vorschau/Diff bereitgestellt und nur nach expliziter Bestätigung übernommen.
                                </div>
                            </div>
                            <div class="col-md-6"><?php $renderSwitch('preserve_unsupported_blocks', 'Nicht unterstützte Blöcke bewahren', !empty($translation['preserve_unsupported_blocks'])); ?></div>
                            <div class="col-md-6"><?php $renderSwitch('skip_html_blocks', 'HTML-/Raw-Blöcke überspringen', !empty($translation['skip_html_blocks'])); ?></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Editor-Workflow</h3></div>
                    <div class="card-body text-secondary small">
                        <p>Die bestehende Übersetzungspipeline bedient aktuell Editor.js-Inhalte und führt DE-Inhalte nach EN in lokalisierte Felder zurück.</p>
                        <ul class="mb-0 ps-3">
                            <li>Preview-/Diff-Schritt vor der Übernahme</li>
                            <li>Provider-abhängige Translation-Freigaben</li>
                            <li>Blocktypen können granular eingegrenzt werden</li>
                            <li>Nicht unterstützte Inhalte lassen sich fail-soft bewahren</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <?php $renderPromptTemplateForm('save_translation_prompts', $translationPromptTemplate, 'Prompt-Vorlage · Übersetzung', 'Strukturierte Runtime-Vorlage für Editor.js-Übersetzungen mit klarer Trennung von Instruktion und Segmentdaten.'); ?>
            </div>
        </div>
    <?php elseif ($isCurrentSection('content_creator')): ?>
        <div class="row row-cards mb-4">
            <?php $renderMetricCard('Assist-Provider', (string) count($contentAssistProviders), 'Rewrite oder Summary aktiv'); ?>
            <?php $renderMetricCard('Master-Gate', !empty($features['ai_services_enabled']) ? 'an' : 'aus', 'globale Freigabe'); ?>
            <?php $renderMetricCard('Rewrite-Gate', !empty($features['ai_rewrite_enabled']) ? 'an' : 'aus', 'für Content-Helfer'); ?>
            <?php $renderMetricCard('Summary-Gate', !empty($features['ai_summary_enabled']) ? 'an' : 'aus', 'für Teaser & Zusammenfassungen'); ?>
        </div>

        <div class="row row-cards">
            <div class="col-12">
                <form method="post" class="card">
                    <?php $renderFormContext('generate_content'); ?>
                    <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                        <div>
                            <h3 class="card-title mb-1">Content-Preview generieren</h3>
                            <div class="text-secondary small">Human-in-the-loop: Es wird nur eine Vorschau erzeugt, nichts automatisch veröffentlicht.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Preview generieren</button>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Workflow</label>
                                <select class="form-select" name="content_task">
                                    <option value="summary">Zusammenfassung</option>
                                    <option value="rewrite">Rewrite</option>
                                    <option value="outline">Outline</option>
                                    <option value="cta">CTA-Varianten</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Sprache</label>
                                <input type="text" class="form-control" name="content_locale" maxlength="12" value="de">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tonality</label>
                                <input type="text" class="form-control" name="content_tone" maxlength="120" value="professionell, klar, hilfreich">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Format</label>
                                <input type="text" class="form-control" name="content_format" maxlength="120" value="review-draft">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Briefing</label>
                                <textarea class="form-control" name="content_brief" rows="4" maxlength="6000" required placeholder="Was soll die KI ausarbeiten, zusammenfassen oder umschreiben?"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Kontext / Ausgangstext</label>
                                <textarea class="form-control" name="content_context" rows="6" maxlength="6000" placeholder="Optionaler Seiten-/Beitragstext, Zielgruppe, Constraints, Quellenhinweise..."></textarea>
                                <div class="form-hint">Secrets, API-Keys und personenbezogene Daten gehören nicht in Prompts. Die Ausgabe bleibt Preview.</div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <?php if ($contentGenerationResult !== []): ?>
                <?php $contentResult = is_array($contentGenerationResult['content'] ?? null) ? $contentGenerationResult['content'] : []; ?>
                <div class="col-12">
                    <div class="card border-primary">
                        <div class="card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div>
                                <h3 class="card-title mb-1">Generierte Content-Preview</h3>
                                <div class="text-secondary small">Provider: <?php echo htmlspecialchars((string) ($contentGenerationResult['provider']['label'] ?? '—')); ?> · Modell: <?php echo htmlspecialchars((string) ($contentGenerationResult['provider']['model'] ?? '—')); ?> · <?php echo (int) ($contentGenerationResult['telemetry']['duration_ms'] ?? 0); ?> ms</div>
                            </div>
                            <span class="badge bg-primary-lt">Preview · keine Persistenz</span>
                        </div>
                        <div class="card-body">
                            <h4><?php echo htmlspecialchars((string) ($contentResult['title'] ?? 'Content-Vorschlag')); ?></h4>
                            <?php if (trim((string) ($contentResult['summary'] ?? '')) !== ''): ?>
                                <p class="text-secondary"><?php echo nl2br(htmlspecialchars((string) $contentResult['summary'])); ?></p>
                            <?php endif; ?>
                            <?php if (trim((string) ($contentResult['draft'] ?? '')) !== ''): ?>
                                <div class="border rounded p-3 bg-light-subtle mb-3"><?php echo nl2br(htmlspecialchars((string) $contentResult['draft'])); ?></div>
                            <?php endif; ?>
                            <?php $variants = array_values(array_filter(array_map('strval', (array) ($contentResult['variants'] ?? [])))); ?>
                            <?php if ($variants !== []): ?>
                                <div class="mb-3"><strong>Varianten</strong><ul class="mb-0 mt-2"><?php foreach ($variants as $variant): ?><li><?php echo htmlspecialchars($variant); ?></li><?php endforeach; ?></ul></div>
                            <?php endif; ?>
                            <?php if (trim((string) ($contentResult['rationale'] ?? '')) !== ''): ?>
                                <div class="alert alert-info mb-0 small"><?php echo nl2br(htmlspecialchars((string) $contentResult['rationale'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="col-12 col-xl-7">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Verfügbare Content-Workflows</h3></div>
                    <div class="card-body text-secondary small">
                        <ul class="mb-0 ps-3">
                            <li>Absatz-Rewrite für Seiten- und Beitragsentwürfe</li>
                            <li>Zusammenfassungen, CTA-Textvarianten und Snippet-Ideen</li>
                            <li>Outline-/Briefing-Helfer für neue Inhalte</li>
                            <li>Serverseitige Preview-Generierung mit Provider-Readiness-Prüfung</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Status</h3></div>
                    <div class="card-body text-secondary small">
                        Der Bereich erzeugt jetzt echte serverseitige Vorschauen über Provider mit Rewrite- oder Summary-Fähigkeit. Ausgaben bleiben bewusst Human-in-the-loop und werden nicht automatisch gespeichert.
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Assist-fähige Provider</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped">
                            <thead>
                                <tr>
                                    <th>Provider</th>
                                    <th>Profil</th>
                                    <th>Fähigkeiten</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($contentAssistProviders === []): ?>
                                    <tr><td colspan="4" class="text-center text-secondary py-4">Derzeit kein Provider mit Content-Assist-Fähigkeiten aktiv.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($contentAssistProviders as $provider): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo htmlspecialchars((string) ($provider['label'] ?? 'Provider')); ?></td>
                                            <td><code><?php echo htmlspecialchars((string) ($provider['profile'] ?? 'disabled')); ?></code></td>
                                            <td class="text-secondary small"><?php echo trim((!empty($provider['rewrite_enabled']) ? 'Rewrite ' : '') . (!empty($provider['summary_enabled']) ? 'Summary' : '')); ?></td>
                                            <td><?php $renderBadge(!empty($provider['enabled']) ? 'success' : 'secondary', !empty($provider['enabled']) ? 'aktiv' : 'aus'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <?php $renderPromptTemplateForm('save_content_prompts', $contentPromptTemplate, 'Prompt-Vorlage · Content Creator', 'Vorbereitete Briefing-Vorlage für Rewrite-, Summary-, CTA- und Outline-Flows mit Human-in-the-Loop-Ausgabe.'); ?>
            </div>
        </div>
    <?php elseif ($isCurrentSection('seo_creator')): ?>
        <div class="row row-cards mb-4">
            <?php $renderMetricCard('SEO-Provider', (string) count($seoAssistProviders), 'Meta-/SEO-Unterstützung aktiv'); ?>
            <?php $renderMetricCard('SEO-Gate', !empty($features['ai_seo_meta_enabled']) ? 'an' : 'aus', 'globale Freigabe'); ?>
            <?php $renderMetricCard('SEO-Modul', class_exists('\\CMS\\Services\\CoreModuleService') && \CMS\Services\CoreModuleService::getInstance()->isModuleEnabled('seo') ? 'an' : 'aus', 'Core-Modulstatus'); ?>
            <?php $renderMetricCard('Editor.js', !empty($features['ai_editorjs_enabled']) ? 'an' : 'aus', 'für spätere Inline-Helfer'); ?>
        </div>

        <div class="row row-cards">
            <div class="col-12">
                <form method="post" class="card">
                    <?php $renderFormContext('generate_seo'); ?>
                    <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                        <div>
                            <h3 class="card-title mb-1">SEO-Preview generieren</h3>
                            <div class="text-secondary small">Erzeugt Title-/Description-/Social-/Schema-Vorschläge zur redaktionellen Prüfung.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">SEO-Preview generieren</button>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Primäres Keyword</label>
                                <input type="text" class="form-control" name="seo_keyword" maxlength="160" placeholder="z. B. Microsoft 365 Backup">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sprache</label>
                                <input type="text" class="form-control" name="seo_locale" maxlength="12" value="de">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Inhaltstyp</label>
                                <select class="form-select" name="seo_content_type">
                                    <option value="page">Seite</option>
                                    <option value="post">Beitrag</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Seiten-/Beitragskontext</label>
                                <textarea class="form-control" name="seo_context" rows="7" maxlength="6000" required placeholder="Titel, Kurzbeschreibung, relevante Abschnitte, Zielgruppe, Suchintention..."></textarea>
                                <div class="form-hint">Die KI darf keine Fakten erfinden; bei dünnem Kontext werden Warnungen erwartet.</div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <?php if ($seoGenerationResult !== []): ?>
                <?php $seoResult = is_array($seoGenerationResult['seo'] ?? null) ? $seoGenerationResult['seo'] : []; ?>
                <div class="col-12">
                    <div class="card border-primary">
                        <div class="card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div>
                                <h3 class="card-title mb-1">Generierte SEO-Preview</h3>
                                <div class="text-secondary small">Provider: <?php echo htmlspecialchars((string) ($seoGenerationResult['provider']['label'] ?? '—')); ?> · Modell: <?php echo htmlspecialchars((string) ($seoGenerationResult['provider']['model'] ?? '—')); ?> · <?php echo (int) ($seoGenerationResult['telemetry']['duration_ms'] ?? 0); ?> ms</div>
                            </div>
                            <span class="badge bg-primary-lt">Review erforderlich</span>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-md-3">Meta Title</dt><dd class="col-md-9"><?php echo htmlspecialchars((string) ($seoResult['meta_title'] ?? '')); ?></dd>
                                <dt class="col-md-3">Meta Description</dt><dd class="col-md-9"><?php echo htmlspecialchars((string) ($seoResult['meta_description'] ?? '')); ?></dd>
                                <dt class="col-md-3">Social Title</dt><dd class="col-md-9"><?php echo htmlspecialchars((string) ($seoResult['social_title'] ?? '')); ?></dd>
                                <dt class="col-md-3">Social Description</dt><dd class="col-md-9"><?php echo htmlspecialchars((string) ($seoResult['social_description'] ?? '')); ?></dd>
                                <dt class="col-md-3">Keywords</dt><dd class="col-md-9"><?php echo htmlspecialchars(implode(', ', (array) ($seoResult['keywords'] ?? []))); ?></dd>
                                <dt class="col-md-3">Schema-Hinweise</dt><dd class="col-md-9"><?php echo htmlspecialchars(implode(', ', (array) ($seoResult['schema_hints'] ?? []))); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="col-12 col-xl-7">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Verfügbare SEO-Workflows</h3></div>
                    <div class="card-body text-secondary small">
                        <ul class="mb-0 ps-3">
                            <li>Title-/Meta-Description-Vorschläge pro Entwurf</li>
                            <li>Social Snippets, OpenGraph-Ideen und strukturierte Daten-Hinweise</li>
                            <li>Keyword- und Intent-basierte Outline-Verbesserungen</li>
                            <li>Serverseitige Preview-Generierung mit Review-Pflicht</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Status</h3></div>
                    <div class="card-body text-secondary small">
                        Der SEO Creator ist als eigenständiger Navigationspunkt live nutzbar und erzeugt Preview-Vorschläge über Provider mit SEO-/Meta-Fähigkeit, ohne automatisch zu veröffentlichen.
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">SEO-fähige Provider</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped">
                            <thead>
                                <tr>
                                    <th>Provider</th>
                                    <th>Profil</th>
                                    <th>SEO-Fokus</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($seoAssistProviders === []): ?>
                                    <tr><td colspan="4" class="text-center text-secondary py-4">Derzeit kein Provider mit SEO-Assist aktiv.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($seoAssistProviders as $provider): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo htmlspecialchars((string) ($provider['label'] ?? 'Provider')); ?></td>
                                            <td><code><?php echo htmlspecialchars((string) ($provider['profile'] ?? 'disabled')); ?></code></td>
                                            <td class="text-secondary small">Meta, Snippets, SEO-Hilfen</td>
                                            <td><?php $renderBadge(!empty($provider['enabled']) ? 'success' : 'secondary', !empty($provider['enabled']) ? 'aktiv' : 'aus'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <?php $renderPromptTemplateForm('save_seo_prompts', $seoPromptTemplate, 'Prompt-Vorlage · SEO Creator', 'Vorbereitete Vorlage für Meta-, Social-Snippet- und strukturierte Daten-Hinweise mit redaktioneller Freigabe.'); ?>
            </div>
        </div>
    <?php else: ?>
        <div class="row row-cards">
            <div class="col-12">
                <form method="post" class="card mb-4">
                    <?php $renderFormContext('save_features'); ?>
                    <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                        <h3 class="card-title mb-0">Globale Feature-Gates</h3>
                        <button type="submit" class="btn btn-primary">Feature-Gates speichern</button>
                    </div>
                    <div class="card-body">
                        <div class="row row-cards">
                            <div class="col-md-4">
                                <?php $renderSwitch('ai_services_enabled', 'AI Services global aktivieren', !empty($features['ai_services_enabled']), 'Master-Schalter für alle AI-bezogenen Workflows.'); ?>
                                <?php $renderSwitch('ai_translation_enabled', 'Übersetzung erlauben', !empty($features['ai_translation_enabled'])); ?>
                            </div>
                            <div class="col-md-4">
                                <?php $renderSwitch('ai_rewrite_enabled', 'Rewrite erlauben', !empty($features['ai_rewrite_enabled'])); ?>
                                <?php $renderSwitch('ai_summary_enabled', 'Zusammenfassungen erlauben', !empty($features['ai_summary_enabled'])); ?>
                            </div>
                            <div class="col-md-4">
                                <?php $renderSwitch('ai_seo_meta_enabled', 'SEO-/Meta-Helfer erlauben', !empty($features['ai_seo_meta_enabled'])); ?>
                                <?php $renderSwitch('ai_editorjs_enabled', 'Editor.js-Integration erlauben', !empty($features['ai_editorjs_enabled'])); ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-12">
                <form method="post" class="card mb-4">
                    <?php $renderFormContext('save_providers'); ?>
                    <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                        <div>
                            <h3 class="card-title mb-1">Single AI Provider</h3>
                            <div class="text-secondary small">Es ist exakt ein Provider aktiv. Kein Fallback, keine Parallelprovider, keine direkte Providerliste.</div>
                        </div>
                        <div class="btn-list">
                            <button type="submit" name="action" value="test_provider" class="btn btn-outline-primary">Provider speichern & testen</button>
                            <button type="submit" class="btn btn-primary">Provider speichern</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $provider = $providers[0] ?? ['id' => 'mock', 'type' => 'mock', 'label' => 'Mock Provider'];
                        $providerId = (string) ($provider['id'] ?? 'mock');
                        $providerType = (string) ($provider['type'] ?? 'mock');
                        $providerLabel = (string) ($provider['label'] ?? $providerId);
                        $namePrefix = 'provider_entries[0]';
                        ?>
                        <input type="hidden" id="aiActiveProviderIdInput" name="active_provider_id" value="<?php echo htmlspecialchars($providerId, ENT_QUOTES); ?>">
                        <input type="hidden" id="aiProviderEntryIdInput" name="<?php echo htmlspecialchars($namePrefix . '[id]', ENT_QUOTES); ?>" value="<?php echo htmlspecialchars($providerId, ENT_QUOTES); ?>">
                        <input type="hidden" name="<?php echo htmlspecialchars($namePrefix . '[enabled]', ENT_QUOTES); ?>" value="1">

                        <div class="alert alert-info small">
                            Der AI-Core lädt ausschließlich diesen aktiven Provider. Wenn er falsch konfiguriert ist, schlägt der Workflow sichtbar fehl – es gibt keinen stillen Fallback auf Mock, OpenAI, Mistral, Azure AI oder andere Provider.
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Provider-Typ</label>
                                <select class="form-select" id="aiProviderTypeSelect" name="<?php echo htmlspecialchars($namePrefix . '[type]', ENT_QUOTES); ?>">
                                    <?php foreach ($providerCatalog as $catalogType => $catalogEntry): ?>
                                        <option value="<?php echo htmlspecialchars((string) $catalogType, ENT_QUOTES); ?>" <?php echo $isSelected($providerType, (string) $catalogType); ?>><?php echo htmlspecialchars((string) ($catalogEntry['label'] ?? $catalogType)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Anzeigename</label>
                                <input type="text" class="form-control" name="<?php echo htmlspecialchars($namePrefix . '[label]', ENT_QUOTES); ?>" value="<?php echo htmlspecialchars($providerLabel); ?>">
                            </div>
                            <div class="col-md-4" data-ai-provider-field="model">
                                <label class="form-label">Optionales Modell</label>
                                <select class="form-select" id="aiProviderModelSelect" name="<?php echo htmlspecialchars($namePrefix . '[default_model]', ENT_QUOTES); ?>" data-current-model="<?php echo htmlspecialchars((string) ($provider['default_model'] ?? ''), ENT_QUOTES); ?>">
                                    <?php $currentModelOptions = (array) ($providerCatalog[$providerType]['model_options'] ?? []); ?>
                                    <?php foreach ($currentModelOptions as $modelValue => $modelLabel): ?>
                                        <option value="<?php echo htmlspecialchars((string) $modelValue, ENT_QUOTES); ?>" <?php echo $isSelected((string) ($provider['default_model'] ?? ''), (string) $modelValue); ?>><?php echo htmlspecialchars((string) $modelLabel); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-hint">Modellauswahl ist providerabhängig und serverseitig validiert. Nicht freigegebene Legacy-Modelle sind nicht auswählbar.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Betriebsprofil</label>
                                <select class="form-select" name="<?php echo htmlspecialchars($namePrefix . '[profile]', ENT_QUOTES); ?>">
                                    <?php foreach ($providerProfiles as $profileValue => $profileLabel): ?>
                                        <option value="<?php echo htmlspecialchars($profileValue, ENT_QUOTES); ?>" <?php echo $isSelected((string) ($provider['profile'] ?? 'editor-translation'), $profileValue); ?>><?php echo htmlspecialchars($profileLabel); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Erlaubte Zielsprachen</label>
                                <input type="text" class="form-control" name="<?php echo htmlspecialchars($namePrefix . '[allowed_locales]', ENT_QUOTES); ?>" value="<?php echo htmlspecialchars(implode(',', (array) ($provider['allowed_locales'] ?? ['en']))); ?>" placeholder="en">
                            </div>
                            <div class="col-md-4" data-ai-provider-field="secret">
                                <label class="form-label">API-Key / Secret</label>
                                <input type="password" class="form-control" name="provider_secret_value" value="" placeholder="Leer lassen = gespeichertes Secret behalten" autocomplete="new-password" spellcheck="false" autocapitalize="off" autocorrect="off">
                                <div class="form-hint">Aktuell gespeichert: <?php echo !empty($provider['secret_configured']) ? 'Ja' : 'Nein'; ?> · Mock/Ollama benötigen keinen Key.</div>
                            </div>
                            <div class="col-12" data-ai-provider-field="endpoint">
                                <label class="form-label">Endpoint</label>
                                <input type="url" class="form-control" id="aiProviderEndpointInput" name="<?php echo htmlspecialchars($namePrefix . '[endpoint]', ENT_QUOTES); ?>" value="<?php echo htmlspecialchars((string) ($provider['endpoint'] ?? '')); ?>" placeholder="https://..." data-current-default="<?php echo htmlspecialchars((string) ($providerCatalog[$providerType]['default_endpoint'] ?? ''), ENT_QUOTES); ?>">
                                <div class="form-hint" id="aiProviderEndpointHint">OpenAI/Mistral/OpenRouter verwenden OpenAI-kompatible Chat-Completions; Azure AI benötigt zusätzlich Deployment und API-Version.</div>
                            </div>
                            <div class="col-md-6" data-ai-provider-field="deployment">
                                <label class="form-label">Azure Deployment</label>
                                <input type="text" class="form-control" name="<?php echo htmlspecialchars($namePrefix . '[deployment]', ENT_QUOTES); ?>" value="<?php echo htmlspecialchars((string) ($provider['deployment'] ?? '')); ?>" placeholder="Nur für Azure AI erforderlich">
                            </div>
                            <div class="col-md-6" data-ai-provider-field="api_version">
                                <label class="form-label">Azure API-Version</label>
                                <input type="text" class="form-control" name="<?php echo htmlspecialchars($namePrefix . '[api_version]', ENT_QUOTES); ?>" value="<?php echo htmlspecialchars((string) ($provider['api_version'] ?? '')); ?>" placeholder="2024-10-21">
                            </div>
                        </div>

                        <script type="application/json" id="aiProviderCatalogJson"><?php echo htmlspecialchars($providerCatalogJson, ENT_NOQUOTES, 'UTF-8'); ?></script>
                        <script>
                            (function () {
                                var catalogNode = document.getElementById('aiProviderCatalogJson');
                                var typeSelect = document.getElementById('aiProviderTypeSelect');
                                var modelSelect = document.getElementById('aiProviderModelSelect');
                                var endpointInput = document.getElementById('aiProviderEndpointInput');
                                var endpointHint = document.getElementById('aiProviderEndpointHint');
                                var activeProviderIdInput = document.getElementById('aiActiveProviderIdInput');
                                var providerEntryIdInput = document.getElementById('aiProviderEntryIdInput');
                                var catalog = {};
                                var lastProviderType = typeSelect ? typeSelect.value : '';

                                if (!catalogNode || !typeSelect || !modelSelect) {
                                    return;
                                }

                                try {
                                    catalog = JSON.parse(catalogNode.textContent || '{}');
                                } catch (error) {
                                    catalog = {};
                                }

                                function getProviderEntry(providerType) {
                                    return catalog[providerType] || catalog.mock || {};
                                }

                                function renderModelOptions(providerType, preferredModel) {
                                    var entry = getProviderEntry(providerType);
                                    var options = entry.model_options || {};
                                    var defaultModel = entry.default_model || Object.keys(options)[0] || '';
                                    var selectedModel = Object.prototype.hasOwnProperty.call(options, preferredModel) ? preferredModel : defaultModel;

                                    modelSelect.innerHTML = '';
                                    Object.keys(options).forEach(function (modelValue) {
                                        var option = document.createElement('option');
                                        option.value = modelValue;
                                        option.textContent = options[modelValue] || modelValue;
                                        option.selected = modelValue === selectedModel;
                                        modelSelect.appendChild(option);
                                    });
                                }

                                function updateFieldVisibility(providerType) {
                                    var entry = getProviderEntry(providerType);
                                    var fields = entry.settings_fields || {};

                                    document.querySelectorAll('[data-ai-provider-field]').forEach(function (fieldNode) {
                                        var fieldName = fieldNode.getAttribute('data-ai-provider-field') || '';
                                        var visible = fields[fieldName] !== false;
                                        fieldNode.hidden = !visible;
                                    });

                                    if (endpointHint) {
                                        if (providerType === 'azure_openai') {
                                            endpointHint.textContent = 'Azure AI benötigt Resource-Endpoint, Deployment-Name, API-Version und API-Key.';
                                        } else if (providerType === 'ollama') {
                                            endpointHint.textContent = 'Ollama nutzt den lokalen/interneren Host, z. B. http://127.0.0.1:11434.';
                                        } else if (providerType === 'mock') {
                                            endpointHint.textContent = 'Mock läuft intern und benötigt keinen externen Endpoint.';
                                        } else {
                                            endpointHint.textContent = 'Dieser Provider nutzt einen OpenAI-kompatiblen /chat/completions Endpoint.';
                                        }
                                    }
                                }

                                function applyProviderDefaults(providerType) {
                                    var entry = getProviderEntry(providerType);
                                    var oldEntry = getProviderEntry(lastProviderType);
                                    var oldDefaultEndpoint = oldEntry.default_endpoint || '';
                                    var nextDefaultEndpoint = entry.default_endpoint || '';

                                    if (activeProviderIdInput) {
                                        activeProviderIdInput.value = providerType;
                                    }

                                    if (providerEntryIdInput) {
                                        providerEntryIdInput.value = providerType;
                                    }

                                    renderModelOptions(providerType, providerType === lastProviderType ? (modelSelect.getAttribute('data-current-model') || modelSelect.value || '') : '');
                                    updateFieldVisibility(providerType);

                                    if (endpointInput && (endpointInput.value.trim() === '' || endpointInput.value.trim() === oldDefaultEndpoint)) {
                                        endpointInput.value = nextDefaultEndpoint;
                                    }

                                    lastProviderType = providerType;
                                }

                                typeSelect.addEventListener('change', function () {
                                    applyProviderDefaults(typeSelect.value || 'mock');
                                });

                                applyProviderDefaults(typeSelect.value || 'mock');
                            }());
                        </script>

                        <hr>
                        <div class="row g-2">
                            <div class="col-md-4"><?php $renderSwitch($namePrefix . '[translation_enabled]', 'Translation', !empty($provider['translation_enabled']) || $providerType === 'mock'); ?></div>
                            <div class="col-md-4"><?php $renderSwitch($namePrefix . '[rewrite_enabled]', 'Rewrite', !empty($provider['rewrite_enabled'])); ?></div>
                            <div class="col-md-4"><?php $renderSwitch($namePrefix . '[summary_enabled]', 'Summaries', !empty($provider['summary_enabled'])); ?></div>
                            <div class="col-md-4"><?php $renderSwitch($namePrefix . '[seo_meta_enabled]', 'SEO / Meta', !empty($provider['seo_meta_enabled'])); ?></div>
                            <div class="col-md-4"><?php $renderSwitch($namePrefix . '[editorjs_enabled]', 'Editor.js', !empty($provider['editorjs_enabled']) || $providerType === 'mock'); ?></div>
                            <div class="col-md-4"><?php $renderSwitch($namePrefix . '[beta_only]', 'Nur Beta', !empty($provider['beta_only'])); ?></div>
                        </div>

                        <hr>
                        <label class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="clear_provider_secret_value" value="1">
                            <span class="form-check-label">Gespeichertes Secret für den aktiven Provider löschen</span>
                        </label>
                    </div>
                </form>
            </div>

            <div class="col-12 col-xl-6">
                <form method="post" class="card h-100">
                    <?php $renderFormContext('save_logging'); ?>
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
            </div>

            <div class="col-12 col-xl-6">
                <form method="post" class="card h-100">
                    <?php $renderFormContext('save_quotas'); ?>
                    <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                        <h3 class="card-title mb-0">Quotas & technische Limits</h3>
                        <button type="submit" class="btn btn-primary">Quotas speichern</button>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Max. Zeichen pro Request</label>
                                <input type="number" class="form-control" name="max_chars_per_request" min="250" max="250000" value="<?php echo (int) ($quotas['max_chars_per_request'] ?? 12000); ?>">
                            </div>
                            <div class="col-md-6">
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
                            <div class="col-md-6">
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
            </div>
        </div>
    <?php endif; ?>
</div>
