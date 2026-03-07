<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/** @var array $data */
$d         = $data ?? [];
$pages     = $d['pages'] ?? [];
$assigned  = $d['assigned_pages'] ?? [];
$allPages  = $d['all_pages'] ?? [];
$tabKeys   = ['legal_imprint', 'legal_privacy', 'legal_terms', 'legal_revocation'];
$tabIcons  = [
    'legal_imprint'    => '<svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M9 9l1 0"/><path d="M9 13l6 0"/><path d="M9 17l6 0"/></svg>',
    'legal_privacy'    => '<svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"/></svg>',
    'legal_terms'      => '<svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M5 8v-3a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2h-5"/><path d="M6 14m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M4.5 17l-1.5 5l3 -1.5l3 1.5l-1.5 -5"/></svg>',
    'legal_revocation' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l-4 -4l4 -4"/><path d="M5 10h11a4 4 0 1 1 0 8h-1"/></svg>',
];
$pageIdKeys = ['legal_imprint' => 'imprint_page_id', 'legal_privacy' => 'privacy_page_id', 'legal_terms' => 'terms_page_id', 'legal_revocation' => 'revocation_page_id'];
$templateTypes = ['legal_imprint' => 'imprint', 'legal_privacy' => 'privacy', 'legal_terms' => 'terms', 'legal_revocation' => 'revocation'];
$templateDefaults = $d['templates'] ?? [];
$profile = $d['profile'] ?? [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Recht</div>
                <h2 class="page-title">Legal Sites</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-deck row-cards mb-4">
            <div class="col-md-4">
                <div class="card"><div class="card-body"><div class="subheader">Bereiche</div><div class="h1 mb-0"><?= count($tabKeys) ?></div></div></div>
            </div>
            <div class="col-md-4">
                <div class="card"><div class="card-body"><div class="subheader">Zugewiesene Seiten</div><div class="h1 mb-0"><?= count(array_filter($assigned)) ?></div></div></div>
            </div>
            <div class="col-md-4">
                <div class="card"><div class="card-body"><div class="subheader">Veröffentlicht</div><div class="text-secondary">Impressum, Datenschutz, AGB und Widerruf zentral pflegen und vorhandenen Seiten zuordnen.</div></div></div>
            </div>
        </div>

        <div class="alert alert-primary" role="alert">
            Hinterlege einmal die Standardwerte deines Unternehmens und erzeuge daraus passende Rechtstext-Seiten. Das spart Klicks, vermeidet Platzhalter-Chaos und bringt Impressum & Datenschutz deutlich näher an „fertig statt fragwürdig“.
        </div>
        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert['type'] ?? 'info'); ?> mb-4" role="alert">
                <?php echo htmlspecialchars($alert['message'] ?? ''); ?>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h3 class="card-title mb-1">Standardwerte & Generator</h3>
                    <div class="text-secondary small">Diese Angaben werden für Impressum, Datenschutz, AGB und Widerrufsbelehrung verwendet.</div>
                </div>
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                    <input type="hidden" name="action" value="create_all_pages">
                    <button type="submit" class="btn btn-primary">Alle Rechtstext-Seiten erstellen/aktualisieren</button>
                </form>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                    <input type="hidden" name="action" value="save_profile">

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="border rounded p-3 bg-light-subtle">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label">Profiltyp</label>
                                        <select name="legal_profile_entity_type" id="legalProfileEntityType" class="form-select">
                                            <option value="company" <?php echo ($profile['legal_profile_entity_type'] ?? 'company') === 'company' ? 'selected' : ''; ?>>Firma</option>
                                            <option value="private" <?php echo ($profile['legal_profile_entity_type'] ?? 'company') === 'private' ? 'selected' : ''; ?>>Privat</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="text-secondary small pt-md-4" id="legalProfileEntityHint">Wähle aus, ob die Rechtstexte für eine Firma oder eine Privatperson erstellt werden. Die passenden Pflichtfelder werden automatisch gesetzt.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="subheader mb-2">Unternehmen</div>
                                <div class="row g-3">
                                    <div class="col-md-6" data-required-for="company">
                                        <label class="form-label">Firma / Name <span class="text-danger js-required-indicator">*</span></label>
                                        <input type="text" name="legal_profile_company_name" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_company_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6" data-required-for="company">
                                        <label class="form-label">Rechtsform <span class="text-danger js-required-indicator">*</span></label>
                                        <input type="text" name="legal_profile_legal_form" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_legal_form'] ?? ''); ?>" placeholder="z. B. GmbH, e.K., Einzelunternehmen">
                                    </div>
                                    <div class="col-md-6" data-required-for="private">
                                        <label class="form-label">Vor- und Nachname <span class="text-danger js-required-indicator">*</span></label>
                                        <input type="text" name="legal_profile_owner_name" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_owner_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Geschäftsführung</label>
                                        <input type="text" name="legal_profile_managing_director" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_managing_director'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Inhaltlich verantwortlich</label>
                                        <input type="text" name="legal_profile_content_responsible" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_content_responsible'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="subheader mb-2">Adresse & Kontakt</div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Straße / Hausnummer <span class="text-danger">*</span></label>
                                        <input type="text" name="legal_profile_street" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_street'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">PLZ <span class="text-danger">*</span></label>
                                        <input type="text" name="legal_profile_postal_code" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_postal_code'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Ort <span class="text-danger">*</span></label>
                                        <input type="text" name="legal_profile_city" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_city'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Land <span class="text-danger">*</span></label>
                                        <input type="text" name="legal_profile_country" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_country'] ?? 'Deutschland'); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">E-Mail <span class="text-danger">*</span></label>
                                        <input type="email" name="legal_profile_email" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_email'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Telefon</label>
                                        <input type="text" name="legal_profile_phone" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Website</label>
                                        <input type="url" name="legal_profile_website" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_website'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="subheader mb-2">Register & Pflichtangaben</div>
                                <div class="row g-3">
                                    <div class="col-md-6" data-recommended-for="company">
                                        <label class="form-label">Registergericht</label>
                                        <input type="text" name="legal_profile_register_court" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_register_court'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6" data-recommended-for="company">
                                        <label class="form-label">Registernummer</label>
                                        <input type="text" name="legal_profile_register_number" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_register_number'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6" data-recommended-for="company">
                                        <label class="form-label">USt.-ID</label>
                                        <input type="text" name="legal_profile_vat_id" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_vat_id'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Streitbeilegung</label>
                                        <select name="legal_profile_dispute_participation" class="form-select">
                                            <option value="no" <?php echo ($profile['legal_profile_dispute_participation'] ?? 'no') === 'no' ? 'selected' : ''; ?>>Nicht teilnehmend</option>
                                            <option value="yes" <?php echo ($profile['legal_profile_dispute_participation'] ?? 'no') === 'yes' ? 'selected' : ''; ?>>Teilnahme vorgesehen</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="subheader mb-2">Datenschutz-Setup</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Hosting-Anbieter</label>
                                        <input type="text" name="legal_profile_hosting_provider" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_hosting_provider'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Hosting-Adresse</label>
                                        <input type="text" name="legal_profile_hosting_address" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_hosting_address'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Datenschutz-Kontakt</label>
                                        <input type="text" name="legal_profile_privacy_contact_name" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_privacy_contact_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Datenschutz-E-Mail</label>
                                        <input type="email" name="legal_profile_privacy_contact_email" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_privacy_contact_email'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Analyse-Tool</label>
                                        <input type="text" name="legal_profile_analytics_name" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_analytics_name'] ?? ''); ?>" placeholder="z. B. Matomo, GA4">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Zahlungsanbieter</label>
                                        <input type="text" name="legal_profile_payment_providers" class="form-control" value="<?php echo htmlspecialchars($profile['legal_profile_payment_providers'] ?? ''); ?>" placeholder="z. B. Stripe, PayPal">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="subheader mb-2">AGB & Widerruf</div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Zielgruppe AGB</label>
                                        <select name="legal_profile_terms_scope" class="form-select">
                                            <option value="b2c" <?php echo ($profile['legal_profile_terms_scope'] ?? 'b2c') === 'b2c' ? 'selected' : ''; ?>>B2C</option>
                                            <option value="b2b" <?php echo ($profile['legal_profile_terms_scope'] ?? 'b2c') === 'b2b' ? 'selected' : ''; ?>>B2B</option>
                                            <option value="mixed" <?php echo ($profile['legal_profile_terms_scope'] ?? 'b2c') === 'mixed' ? 'selected' : ''; ?>>B2C + B2B</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Vertragsart</label>
                                        <select name="legal_profile_contract_type" class="form-select">
                                            <option value="services" <?php echo ($profile['legal_profile_contract_type'] ?? 'services') === 'services' ? 'selected' : ''; ?>>Dienstleistung</option>
                                            <option value="goods" <?php echo ($profile['legal_profile_contract_type'] ?? 'services') === 'goods' ? 'selected' : ''; ?>>Warenverkauf</option>
                                            <option value="digital" <?php echo ($profile['legal_profile_contract_type'] ?? 'services') === 'digital' ? 'selected' : ''; ?>>Digitale Inhalte</option>
                                            <option value="mixed" <?php echo ($profile['legal_profile_contract_type'] ?? 'services') === 'mixed' ? 'selected' : ''; ?>>Gemischt</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Rücksendekosten</label>
                                        <select name="legal_profile_return_costs" class="form-select">
                                            <option value="customer" <?php echo ($profile['legal_profile_return_costs'] ?? 'customer') === 'customer' ? 'selected' : ''; ?>>Kunde trägt Kosten</option>
                                            <option value="merchant" <?php echo ($profile['legal_profile_return_costs'] ?? 'customer') === 'merchant' ? 'selected' : ''; ?>>Unternehmen trägt Kosten</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-check">
                                            <input type="checkbox" class="form-check-input" name="legal_profile_service_start_notice" value="1" <?php echo ($profile['legal_profile_service_start_notice'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                            <span class="form-check-label">Hinweis auf vorzeitigen Leistungsbeginn / Erlöschen des Widerrufsrechts einbauen</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="border rounded p-3 bg-light-subtle">
                                <div class="subheader mb-2">Aktive Website-Funktionen</div>
                                <div class="row g-3">
                                    <?php
                                    $toggleFields = [
                                        'legal_profile_has_cookies' => 'Cookie-/Consent-Banner',
                                        'legal_profile_has_contact_form' => 'Kontaktformular',
                                        'legal_profile_has_registration' => 'Registrierung / Benutzerkonten',
                                        'legal_profile_has_comments' => 'Kommentare / Beiträge',
                                        'legal_profile_has_newsletter' => 'Newsletter',
                                        'legal_profile_has_analytics' => 'Analyse / Tracking',
                                        'legal_profile_has_external_media' => 'Externe Medien / Drittinhalte',
                                        'legal_profile_has_webfonts' => 'Externe oder besondere Webfonts',
                                        'legal_profile_has_shop' => 'Shop / Buchung / Zahlungsabwicklung',
                                    ];
                                    foreach ($toggleFields as $fieldName => $label):
                                    ?>
                                        <div class="col-sm-6 col-lg-4">
                                            <label class="form-check">
                                                <input type="checkbox" class="form-check-input" name="<?php echo htmlspecialchars($fieldName); ?>" value="1" <?php echo ($profile[$fieldName] ?? '0') === '1' ? 'checked' : ''; ?>>
                                                <span class="form-check-label"><?php echo htmlspecialchars($label); ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">Standardwerte speichern</button>
                        <span class="text-secondary small align-self-center">Hinweis: Die Texte sind eine technische Vorlage und ersetzen keine rechtliche Prüfung.</span>
                    </div>
                </form>
            </div>
        </div>

        <div class="row row-cards">
        <?php foreach ($tabKeys as $i => $key): $p = $pages[$key] ?? []; ?>
        <div class="col-12">
            <?php $templateType = $templateTypes[$key] ?? ''; ?>
            <?php $defaultTemplate = $templateDefaults[$templateType] ?? ''; ?>
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <?php echo $tabIcons[$key] ?? ''; ?>
                        <h3 class="card-title mb-0"><?php echo htmlspecialchars($p['label'] ?? ''); ?></h3>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-primary btn-sm js-insert-template" data-target="legal-<?php echo htmlspecialchars($key); ?>" data-template="<?php echo htmlspecialchars($defaultTemplate, ENT_QUOTES); ?>">Vorlage einfügen</button>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                            <input type="hidden" name="action" value="generate">
                            <input type="hidden" name="template_type" value="<?php echo htmlspecialchars($templateType); ?>">
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Aus Standardwerten generieren</button>
                        </form>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                            <input type="hidden" name="action" value="create_page">
                            <input type="hidden" name="template_type" value="<?php echo htmlspecialchars($templateType); ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Seite erstellen/aktualisieren</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="subheader">Status</div>
                                <div class="fw-bold mb-2"><?php echo !empty($p['content']) ? 'Inhalt vorhanden' : 'Noch leer'; ?></div>
                                <div class="text-secondary small"><?php echo !empty($p['content']) ? 'Der Text ist gepflegt und kann einer Seite zugeordnet werden.' : 'Mit einem Klick auf „Vorlage generieren“ wird ein DSGVO-/TMG-Grundgerüst eingefügt.'; ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="subheader">Zugeordnete Seite</div>
                                <div class="fw-bold mb-2"><?php echo !empty($assigned[$pageIdKeys[$key] ?? '']) ? 'Verknüpft' : 'Nicht verknüpft'; ?></div>
                                <div class="text-secondary small">Wähle unten eine veröffentlichte Seite, damit der Inhalt öffentlich an der richtigen Stelle ausgespielt wird.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100 bg-light-subtle">
                                <div class="subheader">Vorlage</div>
                                <div class="fw-bold mb-2"><?php echo $defaultTemplate !== '' ? 'Standardtext verfügbar' : 'Keine Vorlage'; ?></div>
                                <div class="text-secondary small">Der Generator nutzt deine Standardwerte für Name, Anschrift, Kontakt, Hosting und aktive Website-Funktionen.</div>
                            </div>
                        </div>
                    </div>

                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                        <input type="hidden" name="action" value="save">
                        <div class="mb-3">
                            <label class="form-label">Inhalt (HTML)</label>
                            <textarea name="<?php echo htmlspecialchars($key); ?>" id="legal-<?php echo htmlspecialchars($key); ?>" class="form-control" rows="12"><?php echo htmlspecialchars($p['content'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Zugewiesene Seite</label>
                            <select name="<?php echo htmlspecialchars($pageIdKeys[$key] ?? ''); ?>" class="form-select">
                                <option value="0">– Keine Seite –</option>
                                <?php foreach ($allPages as $pg): ?>
                                    <option value="<?php echo (int)$pg['id']; ?>" <?php echo ($assigned[$pageIdKeys[$key] ?? ''] ?? '') == $pg['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pg['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-hint">Ordne eine bestehende Seite zu, die diesen Rechtstext anzeigt.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Bereich speichern</button>
                            <?php if (empty($p['content']) && $defaultTemplate !== ''): ?>
                                <span class="text-secondary small align-self-center">Tipp: Erst Vorlage generieren, dann anpassen.</span>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var entityTypeSelect = document.getElementById('legalProfileEntityType');
    var entityHint = document.getElementById('legalProfileEntityHint');

    function updateLegalProfileRequirements() {
        if (!entityTypeSelect) {
            return;
        }

        var entityType = entityTypeSelect.value || 'company';
        var isCompany = entityType === 'company';

        document.querySelectorAll('[data-required-for]').forEach(function (wrapper) {
            var requiredFor = wrapper.getAttribute('data-required-for');
            var input = wrapper.querySelector('input, select, textarea');
            var indicator = wrapper.querySelector('.js-required-indicator');
            var isRequired = requiredFor === entityType;

            wrapper.classList.toggle('opacity-75', !isRequired);

            if (input) {
                input.required = isRequired;
            }

            if (indicator) {
                indicator.classList.toggle('d-none', !isRequired);
            }
        });

        document.querySelectorAll('[data-recommended-for="company"]').forEach(function (wrapper) {
            wrapper.classList.toggle('opacity-75', !isCompany);
        });

        if (entityHint) {
            entityHint.textContent = isCompany
                ? 'Firmenprofil aktiv: Firma und Rechtsform sind Pflichtfelder. Register- und USt.-Angaben sind für das Impressum empfohlen.'
                : 'Privatprofil aktiv: Vor- und Nachname ist Pflicht. Firmenbezogene Felder bleiben optional und können leer bleiben.';
        }
    }

    if (entityTypeSelect) {
        entityTypeSelect.addEventListener('change', updateLegalProfileRequirements);
        updateLegalProfileRequirements();
    }

    document.querySelectorAll('.js-insert-template').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var template = this.getAttribute('data-template') || '';
            var field = document.getElementById(targetId);

            if (!field) {
                return;
            }

            if (field.value.trim() !== '') {
                cmsConfirm({
                    title: 'Vorlage einfügen?',
                    message: 'Vorhandener Inhalt wird überschrieben.',
                    confirmText: 'Einfügen',
                    onConfirm: function () {
                        field.value = template;
                    }
                });
                return;
            }

            field.value = template;
        });
    });
});
</script>
