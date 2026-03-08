<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$template = $data['template'] ?? [];
$isNew = (bool)($data['isNew'] ?? true);
$baseTemplateOptions = $data['baseTemplateOptions'] ?? [];
$templateLinks = $template['links'] ?? [];
$templateSections = $template['sections'] ?? [];
$templateMeta = $template['meta'] ?? [];
$templateMetaLabels = $template['meta_labels'] ?? [];
$templateColors = $template['colors'] ?? [];
$cardSchema = $template['card_schema'] ?? [];
$cardDesign = $template['card_design'] ?? [];
$starterCards = $template['starter_cards'] ?? [];
$baseTemplateDefaults = $data['baseTemplateDefaults'] ?? [];
?>

<style>
.hub-template-preview {
    --hub-preview-hero-start: <?php echo htmlspecialchars((string)($templateColors['hero_start'] ?? '#1f2937')); ?>;
    --hub-preview-hero-end: <?php echo htmlspecialchars((string)($templateColors['hero_end'] ?? '#0f172a')); ?>;
    --hub-preview-accent: <?php echo htmlspecialchars((string)($templateColors['accent'] ?? '#2563eb')); ?>;
    --hub-preview-surface: <?php echo htmlspecialchars((string)($templateColors['surface'] ?? '#ffffff')); ?>;
    --hub-preview-section: <?php echo htmlspecialchars((string)($templateColors['section_background'] ?? '#ffffff')); ?>;
    --hub-preview-card-bg: <?php echo htmlspecialchars((string)($templateColors['card_background'] ?? '#ffffff')); ?>;
    --hub-preview-card-text: <?php echo htmlspecialchars((string)($templateColors['card_text'] ?? '#0f172a')); ?>;
    border-radius: 1rem;
    overflow: hidden;
    background: #f6f8fb;
    border: 1px solid rgba(15, 23, 42, .08);
}

.hub-template-preview--microsoft-365 {
    background:
        radial-gradient(circle at top right, rgba(37, 99, 235, .18), transparent 34%),
        linear-gradient(180deg, #f8fbff, #eef5ff);
}

.hub-template-preview--datenschutz {
    background:
        radial-gradient(circle at top left, rgba(15, 118, 110, .12), transparent 36%),
        linear-gradient(180deg, #f7fdfc, #f0fdfa);
}

.hub-template-preview--linux {
    background:
        radial-gradient(circle at top right, rgba(180, 83, 9, .18), transparent 28%),
        linear-gradient(180deg, #111827, #0b1220);
    border-color: rgba(180, 83, 9, .24);
}

.hub-template-switcher {
    display: flex;
    flex-wrap: wrap;
    gap: .45rem;
}

.hub-template-switcher__btn {
    appearance: none;
    border: 1px solid rgba(15, 23, 42, .12);
    background: #fff;
    color: #334155;
    border-radius: .85rem;
    padding: .6rem .75rem;
    min-width: 82px;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .2rem;
    font-size: .68rem;
    font-weight: 700;
    line-height: 1.2;
    box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
    cursor: pointer;
    transition: all .16s ease;
}

.hub-template-switcher__btn:hover {
    border-color: var(--hub-preview-accent, #2563eb);
    transform: translateY(-1px);
}

.hub-template-switcher__btn.is-active {
    border-color: var(--hub-preview-accent, #2563eb);
    background: color-mix(in srgb, var(--hub-preview-accent, #2563eb) 12%, white);
    color: var(--hub-preview-accent, #2563eb);
    box-shadow: 0 10px 24px rgba(37, 99, 235, .12);
}

.hub-template-switcher__icon {
    display: grid;
    gap: 3px;
}

.hub-template-switcher__icon--columns-1 { grid-template-columns: 1fr; width: 30px; }
.hub-template-switcher__icon--columns-2 { grid-template-columns: repeat(2, 1fr); width: 30px; }
.hub-template-switcher__icon--columns-3 { grid-template-columns: repeat(3, 1fr); width: 36px; }

.hub-template-switcher__icon-cell {
    height: 10px;
    border-radius: 4px;
    background: currentColor;
    opacity: .25;
}

.hub-template-switcher__icon--layout {
    width: 34px;
    height: 22px;
    position: relative;
}

.hub-template-switcher__icon--layout::before,
.hub-template-switcher__icon--layout::after,
.hub-template-switcher__icon--layout span {
    content: "";
    position: absolute;
    background: currentColor;
    border-radius: 5px;
    opacity: .28;
}

.hub-template-switcher__icon--standard::before { inset: 0; }
.hub-template-switcher__icon--feature::before { left: 0; top: 0; width: 21px; height: 22px; }
.hub-template-switcher__icon--feature::after { right: 0; top: 0; width: 10px; height: 10px; }
.hub-template-switcher__icon--feature span { right: 0; bottom: 0; width: 10px; height: 10px; }
.hub-template-switcher__icon--compact::before { left: 0; top: 0; width: 34px; height: 6px; }
.hub-template-switcher__icon--compact::after { left: 0; top: 8px; width: 34px; height: 6px; }
.hub-template-switcher__icon--compact span { left: 0; top: 16px; width: 34px; height: 6px; }

.hub-template-switcher__icon--media {
    width: 36px;
    height: 24px;
    display: grid;
    gap: 3px;
}

.hub-template-switcher__icon--media-top {
    grid-template-columns: 1fr;
    grid-template-rows: 10px 1fr;
}

.hub-template-switcher__icon--media-left,
.hub-template-switcher__icon--media-right {
    grid-template-columns: 12px 1fr;
}

.hub-template-switcher__icon--media-right {
    grid-template-columns: 1fr 12px;
}

.hub-template-switcher__icon-media,
.hub-template-switcher__icon-copy {
    border-radius: 4px;
    background: currentColor;
    opacity: .28;
}

.hub-template-switcher__label {
    font-size: .67rem;
}

.hub-template-preview__hero {
    background: linear-gradient(135deg, var(--hub-preview-hero-start), var(--hub-preview-hero-end));
    color: #fff;
    padding: 1rem;
}

.hub-template-preview--linux .hub-template-preview__hero {
    position: relative;
}

.hub-template-preview--linux .hub-template-preview__hero::before {
    content: "$ ssh hub-preview";
    display: inline-flex;
    margin-bottom: .65rem;
    padding: .18rem .45rem;
    border-radius: 999px;
    background: rgba(0, 0, 0, .26);
    color: rgba(245, 158, 11, .95);
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: .63rem;
    font-weight: 700;
}

.hub-template-preview__badge {
    display: inline-flex;
    padding: .18rem .55rem;
    border-radius: 999px;
    background: rgba(255,255,255,.18);
    font-size: .65rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    margin-bottom: .65rem;
}

.hub-template-preview--microsoft-365 .hub-template-preview__badge {
    background: rgba(255,255,255,.22);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.14);
}

.hub-template-preview--datenschutz .hub-template-preview__badge {
    background: rgba(255,255,255,.16);
    border: 1px solid rgba(255,255,255,.18);
}

.hub-template-preview--linux .hub-template-preview__badge {
    background: rgba(245, 158, 11, .14);
    color: #f59e0b;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.hub-template-preview__title {
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.2;
    margin: 0 0 .4rem;
}

.hub-template-preview__summary {
    margin: 0;
    font-size: .74rem;
    line-height: 1.5;
    color: rgba(255,255,255,.88);
}

.hub-template-preview__meta {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    margin-top: .75rem;
}

.hub-template-preview__meta-chip {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    padding: .2rem .5rem;
    border-radius: 999px;
    background: rgba(255,255,255,.14);
    font-size: .64rem;
}

.hub-template-preview__meta-chip-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.1rem;
    height: 1.1rem;
    border-radius: 999px;
    background: rgba(255,255,255,.16);
    font-size: .68rem;
    font-weight: 700;
}

.hub-template-preview__meta-chip-label {
    font-weight: 700;
}

.hub-template-preview--microsoft-365 .hub-template-preview__meta-chip-icon {
    background: rgba(255,255,255,.2);
}

.hub-template-preview--datenschutz .hub-template-preview__meta-chip {
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.12);
}

.hub-template-preview--datenschutz .hub-template-preview__meta-chip-icon {
    background: rgba(255,255,255,.22);
}

.hub-template-preview--linux .hub-template-preview__meta-chip {
    background: rgba(0,0,0,.22);
    color: rgba(241, 245, 249, .88);
}

.hub-template-preview--linux .hub-template-preview__meta-chip-icon {
    background: rgba(245, 158, 11, .14);
    color: #f59e0b;
}

.hub-template-preview__body {
    padding: .9rem;
    background: var(--hub-preview-surface);
}

.hub-template-preview--linux .hub-template-preview__body {
    background: rgba(15, 23, 42, .52);
}

.hub-template-preview__toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    margin-bottom: .85rem;
}

.hub-template-preview__toolbar-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    justify-content: flex-end;
}

.hub-template-preview__quicklinks {
    display: flex;
    flex-wrap: wrap;
    gap: .45rem;
    margin-bottom: .85rem;
}

.hub-template-preview__quicklink {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .28rem .55rem;
    border-radius: 999px;
    border: 1px solid rgba(15, 23, 42, .08);
    background: rgba(255,255,255,.86);
    color: #334155;
    font-size: .64rem;
    font-weight: 700;
}

.hub-template-preview__quicklink-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1rem;
    height: 1rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--hub-preview-accent) 14%, white);
    color: var(--hub-preview-accent);
    font-size: .62rem;
}

.hub-template-preview--linux .hub-template-preview__quicklink {
    background: rgba(17, 24, 39, .82);
    border-color: rgba(245, 158, 11, .16);
    color: #e5e7eb;
}

.hub-template-preview--linux .hub-template-preview__quicklink-icon {
    background: rgba(245, 158, 11, .12);
    color: #f59e0b;
}

.hub-template-preview__pill {
    display: inline-flex;
    align-items: center;
    padding: .18rem .5rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--hub-preview-accent) 14%, white);
    color: var(--hub-preview-accent);
    font-size: .65rem;
    font-weight: 700;
}

.hub-template-preview--linux .hub-template-preview__pill {
    background: rgba(245, 158, 11, .12);
    color: #f59e0b;
}

.hub-template-preview__grid {
    display: grid;
    gap: .75rem;
}

.hub-template-preview__grid--1 {
    grid-template-columns: 1fr;
}

.hub-template-preview__grid--2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.hub-template-preview__grid--3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.hub-template-preview__card {
    background: var(--hub-preview-card-bg);
    color: var(--hub-preview-card-text);
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: .9rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 100%;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
}

.hub-template-preview--microsoft-365 .hub-template-preview__card {
    border-color: rgba(37, 99, 235, .12);
    box-shadow: 0 10px 26px rgba(37, 99, 235, .08);
}

.hub-template-preview--datenschutz .hub-template-preview__card {
    border-left: 4px solid color-mix(in srgb, var(--hub-preview-accent) 75%, white);
}

.hub-template-preview--linux .hub-template-preview__card {
    border-color: rgba(245, 158, 11, .18);
    box-shadow: 0 12px 30px rgba(0, 0, 0, .28);
}

.hub-template-preview__card--feature:first-child {
    box-shadow: 0 14px 32px rgba(15, 23, 42, .12);
    border-color: color-mix(in srgb, var(--hub-preview-accent) 28%, white);
}

.hub-template-preview__grid--2 .hub-template-preview__card--feature:first-child,
.hub-template-preview__grid--3 .hub-template-preview__card--feature:first-child {
    grid-column: span 2;
}

.hub-template-preview__grid--1 .hub-template-preview__card--feature:first-child {
    grid-column: span 1;
}

.hub-template-preview__card--compact .hub-template-preview__card-body {
    padding: .65rem;
    gap: .4rem;
}

.hub-template-preview__card--compact .hub-template-preview__card-title {
    font-size: .74rem;
}

.hub-template-preview__card--compact .hub-template-preview__card-text {
    font-size: .64rem;
}

.hub-template-preview__card--image-left,
.hub-template-preview__card--image-right {
    display: grid;
    grid-template-columns: 96px 1fr;
}

.hub-template-preview__card--image-right {
    grid-template-columns: 1fr 96px;
}

.hub-template-preview__card--image-left .hub-template-preview__media,
.hub-template-preview__card--image-right .hub-template-preview__media {
    height: 100%;
    min-height: 100%;
    aspect-ratio: auto;
}

.hub-template-preview__card--image-right .hub-template-preview__media {
    order: 2;
}

.hub-template-preview__card--image-left.hub-template-preview__card--feature:first-child,
.hub-template-preview__card--image-right.hub-template-preview__card--feature:first-child {
    grid-template-columns: 132px 1fr;
}

.hub-template-preview__media {
    aspect-ratio: 16 / 9;
    background: linear-gradient(135deg, color-mix(in srgb, var(--hub-preview-accent) 28%, white), color-mix(in srgb, var(--hub-preview-hero-end) 18%, white));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .68rem;
    font-weight: 700;
    color: rgba(15, 23, 42, .55);
}

.hub-template-preview--microsoft-365 .hub-template-preview__media {
    background:
        linear-gradient(135deg, rgba(96, 165, 250, .42), rgba(59, 130, 246, .18)),
        repeating-linear-gradient(90deg, rgba(255,255,255,.16) 0 1px, transparent 1px 10px);
}

.hub-template-preview--datenschutz .hub-template-preview__media {
    background:
        linear-gradient(135deg, rgba(45, 212, 191, .18), rgba(15, 118, 110, .12)),
        radial-gradient(circle at center, rgba(255,255,255,.28), transparent 52%);
}

.hub-template-preview--linux .hub-template-preview__media {
    background:
        linear-gradient(135deg, rgba(245, 158, 11, .16), rgba(17, 24, 39, .9)),
        repeating-linear-gradient(0deg, rgba(255,255,255,.03) 0 1px, transparent 1px 8px);
    color: rgba(245, 158, 11, .82);
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.hub-template-preview__card-body {
    padding: .8rem;
    display: flex;
    flex-direction: column;
    gap: .55rem;
    flex: 1;
}

.hub-template-preview__card-badge {
    display: inline-flex;
    align-self: flex-start;
    padding: .15rem .45rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--hub-preview-accent) 14%, white);
    color: var(--hub-preview-accent);
    font-size: .6rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.hub-template-preview__card-title {
    font-size: .8rem;
    font-weight: 700;
    line-height: 1.3;
    margin: 0;
}

.hub-template-preview__card-text {
    font-size: .68rem;
    line-height: 1.45;
    color: color-mix(in srgb, var(--hub-preview-card-text) 72%, white);
    margin: 0;
    flex: 1;
}

.hub-template-preview__card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    font-size: .62rem;
}

.hub-template-preview__meta-token {
    padding: .15rem .4rem;
    border-radius: 999px;
    background: rgba(15, 23, 42, .06);
}

.hub-template-preview--linux .hub-template-preview__meta-token {
    background: rgba(245, 158, 11, .08);
    color: #e5e7eb;
}

.hub-template-preview__button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    align-self: flex-start;
    padding: .35rem .6rem;
    border-radius: 999px;
    background: var(--hub-preview-accent);
    color: #fff;
    font-size: .65rem;
    font-weight: 700;
}

.hub-template-preview--datenschutz .hub-template-preview__button {
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.16);
}

.hub-template-preview--linux .hub-template-preview__button {
    color: #111827;
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
}

.hub-template-preview__section {
    margin-top: .85rem;
    background: var(--hub-preview-section);
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: .9rem;
    padding: .75rem;
}

.hub-template-preview__sections {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .75rem;
    margin-top: .85rem;
}

.hub-template-preview__section-card {
    background: var(--hub-preview-section);
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: .9rem;
    padding: .78rem;
    display: flex;
    flex-direction: column;
    gap: .55rem;
}

.hub-template-preview__section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
}

.hub-template-preview__section-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    font-size: .58rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--hub-preview-accent);
}

.hub-template-preview__section-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.2rem;
    height: 1.2rem;
    border-radius: .4rem;
    background: color-mix(in srgb, var(--hub-preview-accent) 12%, white);
    color: var(--hub-preview-accent);
    font-size: .72rem;
}

.hub-template-preview__section-list {
    display: grid;
    gap: .35rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.hub-template-preview__section-list li {
    position: relative;
    padding-left: .9rem;
    font-size: .62rem;
    color: #64748b;
}

.hub-template-preview__section-list li::before {
    content: "•";
    position: absolute;
    left: 0;
    top: 0;
    color: var(--hub-preview-accent);
}

.hub-template-preview__section-note {
    font-size: .6rem;
    font-weight: 700;
    color: color-mix(in srgb, var(--hub-preview-accent) 72%, #0f172a);
}

.hub-template-preview--microsoft-365 .hub-template-preview__section {
    background: linear-gradient(135deg, rgba(59, 130, 246, .05), rgba(255,255,255,.96));
}

.hub-template-preview--microsoft-365 .hub-template-preview__section-card--spotlight {
    background: linear-gradient(135deg, rgba(59, 130, 246, .07), rgba(255,255,255,.98));
}

.hub-template-preview--datenschutz .hub-template-preview__section {
    border-left: 4px solid color-mix(in srgb, var(--hub-preview-accent) 75%, white);
}

.hub-template-preview--datenschutz .hub-template-preview__section-card,
.hub-template-preview--datenschutz .hub-template-preview__section-card--checklist {
    border-left: 4px solid color-mix(in srgb, var(--hub-preview-accent) 75%, white);
}

.hub-template-preview--linux .hub-template-preview__section {
    background: rgba(17, 24, 39, .88);
    border-color: rgba(245, 158, 11, .16);
}

.hub-template-preview--linux .hub-template-preview__section-card,
.hub-template-preview--linux .hub-template-preview__section-card--terminal {
    background: rgba(17, 24, 39, .88);
    border-color: rgba(245, 158, 11, .16);
}

.hub-template-preview__section-title {
    font-size: .72rem;
    font-weight: 700;
    margin: 0 0 .3rem;
}

.hub-template-preview__section-text {
    font-size: .65rem;
    line-height: 1.5;
    color: #64748b;
    margin: 0;
}

.hub-template-preview--linux .hub-template-preview__section-title {
    color: #f3f4f6;
}

.hub-template-preview--linux .hub-template-preview__section-text {
    color: #d1d5db;
}

.hub-template-preview--linux .hub-template-preview__section-list li {
    color: #d1d5db;
}

.hub-template-preview--linux .hub-template-preview__section-note {
    color: #f59e0b;
}

@media (max-width: 991.98px) {
    .hub-template-preview__grid--2,
    .hub-template-preview__grid--3 {
        grid-template-columns: 1fr;
    }

    .hub-template-preview__sections {
        grid-template-columns: 1fr;
    }

    .hub-template-preview__grid--2 .hub-template-preview__card--feature:first-child,
    .hub-template-preview__grid--3 .hub-template-preview__card--feature:first-child {
        grid-column: span 1;
    }
}

@media (max-width: 575.98px) {
    .hub-template-switcher__btn {
        min-width: 72px;
        padding: .55rem .6rem;
    }

    .hub-template-preview__card--image-left,
    .hub-template-preview__card--image-right,
    .hub-template-preview__card--image-left.hub-template-preview__card--feature:first-child,
    .hub-template-preview__card--image-right.hub-template-preview__card--feature:first-child {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites?action=templates" class="btn btn-ghost-secondary btn-sm me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6"/></svg>
                    Zurück zu Templates
                </a>
            </div>
            <div class="col">
                <div class="page-pretitle">Hub-Site Templates</div>
                <h2 class="page-title"><?php echo $isNew ? 'Neues Template' : 'Template bearbeiten'; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites">Content</a></li>
            <li class="nav-item"><a class="nav-link active" href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites?action=templates">Templates</a></li>
        </ul>

        <form method="post" action="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites" id="hubTemplateForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save-template">
            <input type="hidden" name="template_key" value="<?php echo htmlspecialchars((string)($template['key'] ?? '')); ?>">
            <input type="hidden" name="template_links_json" id="templateLinksJsonInput" value="<?php echo htmlspecialchars(json_encode($templateLinks, JSON_UNESCAPED_UNICODE)); ?>">
            <input type="hidden" name="template_sections_json" id="templateSectionsJsonInput" value="<?php echo htmlspecialchars(json_encode($templateSections, JSON_UNESCAPED_UNICODE)); ?>">
            <input type="hidden" name="template_starter_cards_json" id="templateStarterCardsJsonInput" value="<?php echo htmlspecialchars(json_encode($starterCards, JSON_UNESCAPED_UNICODE)); ?>">

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Basisdaten</h3></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label required">Template-Name</label>
                                    <input type="text" class="form-control" name="template_label" value="<?php echo htmlspecialchars((string)($template['label'] ?? '')); ?>" required>
                                    <div class="form-hint">Der sichtbare Name darf geändert werden, der interne Key bleibt stabil — keine unnötigen Hub-Site-Explosionen inklusive.</div>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Basis-Layout</label>
                                    <select class="form-select" name="base_template">
                                        <?php foreach ($baseTemplateOptions as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars((string)$value); ?>" <?php echo (($template['base_template'] ?? 'general-it') === $value) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)$label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Kurzbeschreibung</label>
                                    <textarea class="form-control" name="template_summary" rows="3"><?php echo htmlspecialchars((string)($template['summary'] ?? '')); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Template-Meta & Beschriftungen</h3></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Meta-Label: Zielgruppe</label>
                                    <input type="text" class="form-control" name="template_label_audience" value="<?php echo htmlspecialchars((string)($templateMetaLabels['audience'] ?? 'Zielgruppe')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Meta-Wert</label>
                                    <input type="text" class="form-control" name="template_meta_audience" value="<?php echo htmlspecialchars((string)($templateMeta['audience'] ?? '')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Meta-Label: Verantwortlich</label>
                                    <input type="text" class="form-control" name="template_label_owner" value="<?php echo htmlspecialchars((string)($templateMetaLabels['owner'] ?? 'Verantwortlich')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Meta-Wert</label>
                                    <input type="text" class="form-control" name="template_meta_owner" value="<?php echo htmlspecialchars((string)($templateMeta['owner'] ?? '')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Meta-Label: Update-Zyklus</label>
                                    <input type="text" class="form-control" name="template_label_update_cycle" value="<?php echo htmlspecialchars((string)($templateMetaLabels['update_cycle'] ?? 'Update-Zyklus')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Meta-Label: Fokus</label>
                                    <input type="text" class="form-control" name="template_label_focus" value="<?php echo htmlspecialchars((string)($templateMetaLabels['focus'] ?? 'Fokus')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Meta-Label: KPI</label>
                                    <input type="text" class="form-control" name="template_label_kpi" value="<?php echo htmlspecialchars((string)($templateMetaLabels['kpi'] ?? 'KPI')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Meta-Wert: Update-Zyklus</label>
                                    <input type="text" class="form-control" name="template_meta_update_cycle" value="<?php echo htmlspecialchars((string)($templateMeta['update_cycle'] ?? '')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Meta-Wert: Fokus</label>
                                    <input type="text" class="form-control" name="template_meta_focus" value="<?php echo htmlspecialchars((string)($templateMeta['focus'] ?? '')); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Meta-Wert: KPI</label>
                                    <input type="text" class="form-control" name="template_meta_kpi" value="<?php echo htmlspecialchars((string)($templateMeta['kpi'] ?? '')); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Farben & Flächen</h3></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Hero Verlauf Start</label><input type="color" class="form-control form-control-color" name="template_color_hero_start" value="<?php echo htmlspecialchars((string)($templateColors['hero_start'] ?? '#1f2937')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Hero Verlauf Ende</label><input type="color" class="form-control form-control-color" name="template_color_hero_end" value="<?php echo htmlspecialchars((string)($templateColors['hero_end'] ?? '#0f172a')); ?>"></div>
                                <div class="col-md-4"><label class="form-label">Akzent / Buttons</label><input type="color" class="form-control form-control-color" name="template_color_accent" value="<?php echo htmlspecialchars((string)($templateColors['accent'] ?? '#2563eb')); ?>"></div>
                                <div class="col-md-4"><label class="form-label">Oberfläche</label><input type="color" class="form-control form-control-color" name="template_color_surface" value="<?php echo htmlspecialchars((string)($templateColors['surface'] ?? '#ffffff')); ?>"></div>
                                <div class="col-md-4"><label class="form-label">Bereichs-Hintergrund</label><input type="color" class="form-control form-control-color" name="template_color_section_background" value="<?php echo htmlspecialchars((string)($templateColors['section_background'] ?? '#ffffff')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Card-Hintergrund</label><input type="color" class="form-control form-control-color" name="template_color_card_background" value="<?php echo htmlspecialchars((string)($templateColors['card_background'] ?? '#ffffff')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Card-Text</label><input type="color" class="form-control form-control-color" name="template_color_card_text" value="<?php echo htmlspecialchars((string)($templateColors['card_text'] ?? '#0f172a')); ?>"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Kachel-Schema</h3></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Kacheln pro Reihe</label>
                                    <input type="hidden" name="template_card_columns" value="<?php echo (int)($cardSchema['columns'] ?? 2); ?>">
                                    <div class="hub-template-switcher" data-switcher="template_card_columns">
                                        <?php foreach ([1, 2, 3] as $columnCount): ?>
                                            <button type="button" class="hub-template-switcher__btn <?php echo ((int)($cardSchema['columns'] ?? 2) === $columnCount) ? 'is-active' : ''; ?>" data-value="<?php echo $columnCount; ?>">
                                                <span class="hub-template-switcher__icon hub-template-switcher__icon--columns-<?php echo $columnCount; ?>">
                                                    <?php for ($i = 0; $i < $columnCount; $i++): ?>
                                                        <span class="hub-template-switcher__icon-cell"></span>
                                                    <?php endfor; ?>
                                                </span>
                                                <span class="hub-template-switcher__label"><?php echo $columnCount; ?>er</span>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Card-Layout</label>
                                    <input type="hidden" name="hub_card_layout" value="<?php echo htmlspecialchars((string)($cardDesign['layout'] ?? 'standard')); ?>">
                                    <div class="hub-template-switcher" data-switcher="hub_card_layout">
                                        <button type="button" class="hub-template-switcher__btn <?php echo (($cardDesign['layout'] ?? 'standard') === 'standard') ? 'is-active' : ''; ?>" data-value="standard">
                                            <span class="hub-template-switcher__icon hub-template-switcher__icon--layout hub-template-switcher__icon--standard"></span>
                                            <span class="hub-template-switcher__label">Standard</span>
                                        </button>
                                        <button type="button" class="hub-template-switcher__btn <?php echo (($cardDesign['layout'] ?? '') === 'feature') ? 'is-active' : ''; ?>" data-value="feature">
                                            <span class="hub-template-switcher__icon hub-template-switcher__icon--layout hub-template-switcher__icon--feature"><span></span></span>
                                            <span class="hub-template-switcher__label">Feature</span>
                                        </button>
                                        <button type="button" class="hub-template-switcher__btn <?php echo (($cardDesign['layout'] ?? '') === 'compact') ? 'is-active' : ''; ?>" data-value="compact">
                                            <span class="hub-template-switcher__icon hub-template-switcher__icon--layout hub-template-switcher__icon--compact"><span></span></span>
                                            <span class="hub-template-switcher__label">Compact</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4"><label class="form-label">Meta-Layout</label><select class="form-select" name="hub_card_meta_layout"><option value="split" <?php echo (($cardDesign['meta_layout'] ?? 'split') === 'split') ? 'selected' : ''; ?>>Links / Rechts</option><option value="stacked" <?php echo (($cardDesign['meta_layout'] ?? '') === 'stacked') ? 'selected' : ''; ?>>Gestapelt</option></select></div>
                                <div class="col-md-4">
                                    <label class="form-label">Bildposition</label>
                                    <input type="hidden" name="hub_card_image_position" value="<?php echo htmlspecialchars((string)($cardDesign['image_position'] ?? 'top')); ?>">
                                    <div class="hub-template-switcher" data-switcher="hub_card_image_position">
                                        <button type="button" class="hub-template-switcher__btn <?php echo (($cardDesign['image_position'] ?? 'top') === 'top') ? 'is-active' : ''; ?>" data-value="top">
                                            <span class="hub-template-switcher__icon hub-template-switcher__icon--media hub-template-switcher__icon--media-top"><span class="hub-template-switcher__icon-media"></span><span class="hub-template-switcher__icon-copy"></span></span>
                                            <span class="hub-template-switcher__label">Oben</span>
                                        </button>
                                        <button type="button" class="hub-template-switcher__btn <?php echo (($cardDesign['image_position'] ?? '') === 'left') ? 'is-active' : ''; ?>" data-value="left">
                                            <span class="hub-template-switcher__icon hub-template-switcher__icon--media hub-template-switcher__icon--media-left"><span class="hub-template-switcher__icon-media"></span><span class="hub-template-switcher__icon-copy"></span></span>
                                            <span class="hub-template-switcher__label">Links</span>
                                        </button>
                                        <button type="button" class="hub-template-switcher__btn <?php echo (($cardDesign['image_position'] ?? '') === 'right') ? 'is-active' : ''; ?>" data-value="right">
                                            <span class="hub-template-switcher__icon hub-template-switcher__icon--media hub-template-switcher__icon--media-right"><span class="hub-template-switcher__icon-copy"></span><span class="hub-template-switcher__icon-media"></span></span>
                                            <span class="hub-template-switcher__label">Rechts</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4"><label class="form-label">Bilddarstellung</label><select class="form-select" name="hub_card_image_fit"><option value="cover" <?php echo (($cardDesign['image_fit'] ?? 'cover') === 'cover') ? 'selected' : ''; ?>>Cover</option><option value="contain" <?php echo (($cardDesign['image_fit'] ?? '') === 'contain') ? 'selected' : ''; ?>>Contain</option></select></div>
                                <div class="col-md-4"><label class="form-label">Bildformat</label><select class="form-select" name="hub_card_image_ratio"><option value="wide" <?php echo (($cardDesign['image_ratio'] ?? 'wide') === 'wide') ? 'selected' : ''; ?>>Breit</option><option value="square" <?php echo (($cardDesign['image_ratio'] ?? '') === 'square') ? 'selected' : ''; ?>>Quadratisch</option><option value="portrait" <?php echo (($cardDesign['image_ratio'] ?? '') === 'portrait') ? 'selected' : ''; ?>>Hochformat</option></select></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Titel</label><input type="text" class="form-control" name="template_card_title_label" value="<?php echo htmlspecialchars((string)($cardSchema['title_label'] ?? 'Titel')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Kurzbeschreibung</label><input type="text" class="form-control" name="template_card_summary_label" value="<?php echo htmlspecialchars((string)($cardSchema['summary_label'] ?? 'Kurzbeschreibung')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Badge</label><input type="text" class="form-control" name="template_card_badge_label" value="<?php echo htmlspecialchars((string)($cardSchema['badge_label'] ?? 'Badge')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Meta links</label><input type="text" class="form-control" name="template_card_meta_left_label" value="<?php echo htmlspecialchars((string)($cardSchema['meta_left_label'] ?? 'Meta links')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Meta rechts</label><input type="text" class="form-control" name="template_card_meta_right_label" value="<?php echo htmlspecialchars((string)($cardSchema['meta_right_label'] ?? 'Meta rechts')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Bild-URL</label><input type="text" class="form-control" name="template_card_image_label" value="<?php echo htmlspecialchars((string)($cardSchema['image_label'] ?? 'Bild-URL')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Bild-Alt</label><input type="text" class="form-control" name="template_card_image_alt_label" value="<?php echo htmlspecialchars((string)($cardSchema['image_alt_label'] ?? 'Bild-Alt')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Button-Text</label><input type="text" class="form-control" name="template_card_button_text_label" value="<?php echo htmlspecialchars((string)($cardSchema['button_text_label'] ?? 'Button-Text')); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Feldlabel: Button-Link</label><input type="text" class="form-control" name="template_card_button_link_label" value="<?php echo htmlspecialchars((string)($cardSchema['button_link_label'] ?? 'Button-Link')); ?>"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Starter-Kacheln</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addStarterCard">Starter-Kachel hinzufügen</button>
                        </div>
                        <div class="card-body p-0">
                            <div id="starterCardsContainer"></div>
                            <div class="text-center text-secondary py-4 d-none" id="starterCardsEmpty">Keine Starter-Kacheln definiert. Mindestens eine lohnt sich meistens.</div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Header-Links</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addTemplateLink">Link hinzufügen</button>
                        </div>
                        <div class="card-body p-0">
                            <div id="templateLinksContainer"></div>
                            <div class="text-center text-secondary py-4 d-none" id="templateLinksEmpty">Noch keine Header-Links vorhanden.</div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Designbereiche</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addTemplateSection">Bereich hinzufügen</button>
                        </div>
                        <div class="card-body p-0">
                            <div id="templateSectionsContainer"></div>
                            <div class="text-center text-secondary py-4 d-none" id="templateSectionsEmpty">Noch keine Bereiche vorhanden.</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">Live-Vorschau</h3>
                            <span class="badge bg-azure-lt" id="templatePreviewColumnsBadge">2 Kacheln</span>
                        </div>
                        <div class="card-body">
                            <div class="hub-template-preview" id="hubTemplatePreview">
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
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var isNewTemplate = <?php echo $isNew ? 'true' : 'false'; ?>;
    var baseTemplateDefaults = <?php echo json_encode($baseTemplateDefaults, JSON_UNESCAPED_UNICODE); ?>;
    var links = <?php echo json_encode($templateLinks, JSON_UNESCAPED_UNICODE); ?>;
    var sections = <?php echo json_encode($templateSections, JSON_UNESCAPED_UNICODE); ?>;
    var starterCards = <?php echo json_encode($starterCards, JSON_UNESCAPED_UNICODE); ?>;
    var linksInput = document.getElementById('templateLinksJsonInput');
    var sectionsInput = document.getElementById('templateSectionsJsonInput');
    var starterCardsInput = document.getElementById('templateStarterCardsJsonInput');
    var form = document.getElementById('hubTemplateForm');
    var linksContainer = document.getElementById('templateLinksContainer');
    var sectionsContainer = document.getElementById('templateSectionsContainer');
    var starterCardsContainer = document.getElementById('starterCardsContainer');
    var linksEmpty = document.getElementById('templateLinksEmpty');
    var sectionsEmpty = document.getElementById('templateSectionsEmpty');
    var starterCardsEmpty = document.getElementById('starterCardsEmpty');
    var preview = document.getElementById('hubTemplatePreview');
    var previewBadge = document.getElementById('templatePreviewBadge');
    var previewTitle = document.getElementById('templatePreviewTitle');
    var previewSummary = document.getElementById('templatePreviewSummary');
    var previewMeta = document.getElementById('templatePreviewMeta');
    var previewGrid = document.getElementById('templatePreviewGrid');
    var previewQuicklinks = document.getElementById('templatePreviewQuicklinks');
    var previewSections = document.getElementById('templatePreviewSections');
    var previewLayoutPill = document.getElementById('templatePreviewLayoutPill');
    var previewTypePill = document.getElementById('templatePreviewTypePill');
    var previewImagePill = document.getElementById('templatePreviewImagePill');
    var previewColumnsBadge = document.getElementById('templatePreviewColumnsBadge');
    var previewCardCount = document.getElementById('templatePreviewCardCount');
    var previewSectionTitle = document.getElementById('templatePreviewSectionTitle');
    var previewSectionText = document.getElementById('templatePreviewSectionText');
    var lastAppliedBaseTemplate = null;

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(value || ''));
        return div.innerHTML;
    }

    function sync() {
        linksInput.value = JSON.stringify(links);
        sectionsInput.value = JSON.stringify(sections);
        starterCardsInput.value = JSON.stringify(starterCards);
        renderPreview();
    }

    function getValue(name, fallback) {
        var field = form.querySelector('[name="' + name + '"]');
        return field ? field.value : (fallback || '');
    }

    function bindSwitchers() {
        form.querySelectorAll('[data-switcher]').forEach(function (switcher) {
            switcher.addEventListener('click', function (event) {
                var button = event.target.closest('.hub-template-switcher__btn');
                if (!button) {
                    return;
                }

                var inputName = switcher.getAttribute('data-switcher');
                var input = form.querySelector('[name="' + inputName + '"]');
                if (!input) {
                    return;
                }

                input.value = button.getAttribute('data-value') || '';
                switcher.querySelectorAll('.hub-template-switcher__btn').forEach(function (item) {
                    item.classList.toggle('is-active', item === button);
                });
                renderPreview();
            });
        });
    }

    function getStarterCardsForPreview() {
        var defaults = getBaseTemplateDefaults(getValue('base_template', 'general-it'));
        var cards = starterCards.slice(0, 3).filter(function (card) {
            return card && (card.title || card.summary || card.badge || card.button_text || card.image_url);
        });

        if (cards.length === 0) {
            cards = (defaults.starter_cards || []).slice(0, 3);
        }

        if (cards.length === 0) {
            cards = [
                { title: 'Beispiel-Kachel 1', summary: 'Erste Vorschau-Kachel für das Template-Layout.', badge: 'Primary', meta_left: 'Meta links', meta_right: 'Meta rechts', button_text: 'Mehr', image_url: '' },
                { title: 'Beispiel-Kachel 2', summary: 'Zweite Vorschau-Kachel für die nebeneinander-Darstellung.', badge: 'Secondary', meta_left: 'Owner', meta_right: 'Status', button_text: 'Öffnen', image_url: '' },
                { title: 'Beispiel-Kachel 3', summary: 'Dritte Kachel zeigt die 3-Spalten-Variante.', badge: 'Optional', meta_left: 'Typ', meta_right: 'Live', button_text: 'Details', image_url: '' }
            ];
        }

        return cards.slice(0, 3);
    }

    function cloneValue(value) {
        return JSON.parse(JSON.stringify(value || null));
    }

    function getBaseTemplateDefaults(baseTemplate) {
        return baseTemplateDefaults[baseTemplate] || baseTemplateDefaults['general-it'] || {};
    }

    function syncSwitcherState(inputName, value) {
        form.querySelectorAll('[data-switcher="' + inputName + '"] .hub-template-switcher__btn').forEach(function (button) {
            button.classList.toggle('is-active', (button.getAttribute('data-value') || '') === String(value || ''));
        });
    }

    function setFieldValue(name, value, previousDefault) {
        var field = form.querySelector('[name="' + name + '"]');
        if (!field) {
            return;
        }

        var current = field.value || '';
        var nextValue = value || '';
        if (current === '' || current === previousDefault) {
            field.value = nextValue;
        }
    }

    function applyBaseTemplateDefaults(baseTemplate, forceCollections) {
        if (!isNewTemplate) {
            return;
        }

        var defaults = getBaseTemplateDefaults(baseTemplate);
        var previousDefaults = lastAppliedBaseTemplate ? getBaseTemplateDefaults(lastAppliedBaseTemplate) : {};
        var metaLabels = defaults.meta_labels || {};
        var meta = defaults.meta || {};
        var colors = defaults.colors || {};
        var cardSchemaDefaults = defaults.card_schema || {};
        var cardDesignDefaults = defaults.card_design || {};

        setFieldValue('template_summary', defaults.summary || '', previousDefaults.summary || '');
        setFieldValue('template_label_audience', metaLabels.audience || 'Zielgruppe', (previousDefaults.meta_labels || {}).audience || '');
        setFieldValue('template_label_owner', metaLabels.owner || 'Verantwortlich', (previousDefaults.meta_labels || {}).owner || '');
        setFieldValue('template_label_update_cycle', metaLabels.update_cycle || 'Update-Zyklus', (previousDefaults.meta_labels || {}).update_cycle || '');
        setFieldValue('template_label_focus', metaLabels.focus || 'Fokus', (previousDefaults.meta_labels || {}).focus || '');
        setFieldValue('template_label_kpi', metaLabels.kpi || 'KPI', (previousDefaults.meta_labels || {}).kpi || '');
        setFieldValue('template_meta_audience', meta.audience || '', (previousDefaults.meta || {}).audience || '');
        setFieldValue('template_meta_owner', meta.owner || '', (previousDefaults.meta || {}).owner || '');
        setFieldValue('template_meta_update_cycle', meta.update_cycle || '', (previousDefaults.meta || {}).update_cycle || '');
        setFieldValue('template_meta_focus', meta.focus || '', (previousDefaults.meta || {}).focus || '');
        setFieldValue('template_meta_kpi', meta.kpi || '', (previousDefaults.meta || {}).kpi || '');
        setFieldValue('template_card_title_label', cardSchemaDefaults.title_label || 'Titel', (previousDefaults.card_schema || {}).title_label || '');
        setFieldValue('template_card_summary_label', cardSchemaDefaults.summary_label || 'Kurzbeschreibung', (previousDefaults.card_schema || {}).summary_label || '');
        setFieldValue('template_card_badge_label', cardSchemaDefaults.badge_label || 'Badge', (previousDefaults.card_schema || {}).badge_label || '');
        setFieldValue('template_card_meta_left_label', cardSchemaDefaults.meta_left_label || 'Meta links', (previousDefaults.card_schema || {}).meta_left_label || '');
        setFieldValue('template_card_meta_right_label', cardSchemaDefaults.meta_right_label || 'Meta rechts', (previousDefaults.card_schema || {}).meta_right_label || '');
        setFieldValue('template_card_image_label', cardSchemaDefaults.image_label || 'Bild-URL', (previousDefaults.card_schema || {}).image_label || '');
        setFieldValue('template_card_image_alt_label', cardSchemaDefaults.image_alt_label || 'Bild-Alt', (previousDefaults.card_schema || {}).image_alt_label || '');
        setFieldValue('template_card_button_text_label', cardSchemaDefaults.button_text_label || 'Button-Text', (previousDefaults.card_schema || {}).button_text_label || '');
        setFieldValue('template_card_button_link_label', cardSchemaDefaults.button_link_label || 'Button-Link', (previousDefaults.card_schema || {}).button_link_label || '');

        ['hero_start', 'hero_end', 'accent', 'surface', 'section_background', 'card_background', 'card_text'].forEach(function (key) {
            var fieldName = 'template_color_' + key;
            var field = form.querySelector('[name="' + fieldName + '"]');
            if (!field) {
                return;
            }
            var current = field.value || '';
            var previous = (previousDefaults.colors || {})[key] || '';
            if (current === '' || current === previous) {
                field.value = colors[key] || field.value;
            }
        });

        ['template_card_columns', 'hub_card_layout', 'hub_card_image_position', 'hub_card_image_fit', 'hub_card_image_ratio', 'hub_card_meta_layout'].forEach(function (fieldName) {
            var field = form.querySelector('[name="' + fieldName + '"]');
            if (!field) {
                return;
            }

            var valueMap = {
                template_card_columns: cardSchemaDefaults.columns,
                hub_card_layout: cardDesignDefaults.layout,
                hub_card_image_position: cardDesignDefaults.image_position,
                hub_card_image_fit: cardDesignDefaults.image_fit,
                hub_card_image_ratio: cardDesignDefaults.image_ratio,
                hub_card_meta_layout: cardDesignDefaults.meta_layout
            };

            if (valueMap[fieldName] !== undefined && valueMap[fieldName] !== null && valueMap[fieldName] !== '') {
                field.value = String(valueMap[fieldName]);
                syncSwitcherState(fieldName, field.value);
            }
        });

        if (forceCollections || links.length === 0 || JSON.stringify(links) === JSON.stringify((previousDefaults.links || []))) {
            links = cloneValue(defaults.links || []);
        }
        if (forceCollections || sections.length === 0 || JSON.stringify(sections) === JSON.stringify((previousDefaults.sections || []))) {
            sections = cloneValue(defaults.sections || []);
        }
        if (forceCollections || starterCards.length === 0 || JSON.stringify(starterCards) === JSON.stringify((previousDefaults.starter_cards || []))) {
            starterCards = cloneValue(defaults.starter_cards || []);
        }

        lastAppliedBaseTemplate = baseTemplate;
        renderLinks();
        renderSections();
        renderStarterCards();
    }

    function getTemplatePreviewProfile(baseTemplate) {
        var profiles = {
            'microsoft-365': {
                badge: 'Microsoft 365',
                sectionTitle: 'Workspace & Adoption',
                sectionText: 'Cloud, Collaboration und Governance wirken hier klarer, heller und produktnäher.',
                cardPrefix: 'M365',
                mediaLabel: 'Cloud',
                metaIcons: { audience: '◈', owner: '☁', update_cycle: '↺', focus: '✦', kpi: '↑' },
                linkIcons: ['T', 'S', 'C', 'G'],
                sectionEyebrows: ['Workspace Layer', 'Guardrails'],
                sectionIcons: ['☁', '✓'],
                sectionStyles: ['spotlight', 'stacked'],
                sectionNotes: ['Workloads & Journeys', 'Policies & Rollout']
            },
            'datenschutz': {
                badge: 'Datenschutz',
                sectionTitle: 'Schutz & Nachweise',
                sectionText: 'Ruhige Vertrauensoptik mit klaren Hinweisen, Schutzcharakter und Compliance-Fokus.',
                cardPrefix: 'DSGVO',
                mediaLabel: 'Shield',
                metaIcons: { audience: '§', owner: '⚖', update_cycle: '⏱', focus: '✓', kpi: '▣' },
                linkIcons: ['§', 'V', 'T', 'R'],
                sectionEyebrows: ['Nachweise', 'Pflichten'],
                sectionIcons: ['✓', '⚖'],
                sectionStyles: ['trust', 'checklist'],
                sectionNotes: ['Dokumentation & Belege', 'Fristen & Maßnahmen']
            },
            'linux': {
                badge: 'Linux',
                sectionTitle: 'Platform & Ops',
                sectionText: 'Terminalnah, dunkler und technischer — perfekt für Betrieb, Plattformen und Automatisierung.',
                cardPrefix: 'Ops',
                mediaLabel: 'CLI',
                metaIcons: { audience: '⌘', owner: '#', update_cycle: '↻', focus: '▤', kpi: '●' },
                linkIcons: ['#', '□', '>', '!'],
                sectionEyebrows: ['Runtime', 'Runbooks'],
                sectionIcons: ['⌘', '>'],
                sectionStyles: ['terminal', 'terminal'],
                sectionNotes: ['$ health=ok', '$ status=watch']
            },
            'compliance': {
                badge: 'Compliance',
                sectionTitle: 'Governance & Audit',
                sectionText: 'Kontrollen, Richtlinien und Nachvollziehbarkeit stehen hier visuell stärker im Vordergrund.',
                cardPrefix: 'Audit',
                mediaLabel: 'Policy',
                metaIcons: { audience: '◎', owner: '◆', update_cycle: '↺', focus: '◌', kpi: '▲' },
                linkIcons: ['P', 'A', 'R', 'N'],
                sectionEyebrows: ['Controls', 'Evidence'],
                sectionIcons: ['◆', '▲'],
                sectionStyles: ['spotlight', 'stacked'],
                sectionNotes: ['Kontrollen & Rollen', 'Audit & Evidence']
            },
            'general-it': {
                badge: 'General IT',
                sectionTitle: 'IT-Architektur',
                sectionText: 'Breit einsetzbares, neutrales Basislayout für Technologie-, Team- und Lösungsseiten.',
                cardPrefix: 'IT',
                mediaLabel: 'Preview',
                metaIcons: { audience: '◎', owner: '◆', update_cycle: '↺', focus: '◌', kpi: '▲' },
                linkIcons: ['S', 'P', 'C', 'B'],
                sectionEyebrows: ['Architektur', 'Betrieb'],
                sectionIcons: ['◆', '▲'],
                sectionStyles: ['spotlight', 'stacked'],
                sectionNotes: ['Zielbild & Standards', 'Services & Delivery']
            }
        };

        return profiles[baseTemplate] || profiles['general-it'];
    }

    function getPreviewLinks(defaults) {
        var candidates = links.length ? links : (defaults.links || []);
        return candidates.slice(0, 4).filter(function (item) {
            return item && item.label;
        });
    }

    function getPreviewSections(defaults) {
        var candidates = sections.length ? sections : (defaults.sections || []);
        return candidates.slice(0, 2).filter(function (item) {
            return item && (item.title || item.text);
        });
    }

    function renderPreview() {
        var columns = parseInt(getValue('template_card_columns', '2'), 10);
        var layout = getValue('hub_card_layout', 'standard');
        var imagePosition = getValue('hub_card_image_position', 'top');
        var baseTemplate = getValue('base_template', 'general-it');
        var defaults = getBaseTemplateDefaults(baseTemplate);
        var templateProfile = getTemplatePreviewProfile(baseTemplate);
        if (columns < 1 || columns > 3) {
            columns = 2;
        }

        var title = getValue('template_label', 'Template-Vorschau').trim() || 'Template-Vorschau';
        var summary = getValue('template_summary', '').trim() || defaults.summary || 'So wirken Hero, Meta-Felder und 1/2/3 Kachel-Layouts im Admin direkt beim Bearbeiten.';
        var cards = getStarterCardsForPreview();
        var metaEntries = [
            { key: 'audience', label: getValue('template_label_audience', (defaults.meta_labels || {}).audience || 'Zielgruppe'), value: getValue('template_meta_audience', (defaults.meta || {}).audience || '') || (defaults.meta || {}).audience || '' },
            { key: 'owner', label: getValue('template_label_owner', (defaults.meta_labels || {}).owner || 'Verantwortlich'), value: getValue('template_meta_owner', (defaults.meta || {}).owner || '') || (defaults.meta || {}).owner || '' },
            { key: 'update_cycle', label: getValue('template_label_update_cycle', (defaults.meta_labels || {}).update_cycle || 'Update-Zyklus'), value: getValue('template_meta_update_cycle', (defaults.meta || {}).update_cycle || '') || (defaults.meta || {}).update_cycle || '' },
            { key: 'focus', label: getValue('template_label_focus', (defaults.meta_labels || {}).focus || 'Fokus'), value: getValue('template_meta_focus', (defaults.meta || {}).focus || '') || (defaults.meta || {}).focus || '' },
            { key: 'kpi', label: getValue('template_label_kpi', (defaults.meta_labels || {}).kpi || 'KPI'), value: getValue('template_meta_kpi', (defaults.meta || {}).kpi || '') || (defaults.meta || {}).kpi || '' }
        ];

        preview.style.setProperty('--hub-preview-hero-start', getValue('template_color_hero_start', '#1f2937'));
        preview.style.setProperty('--hub-preview-hero-end', getValue('template_color_hero_end', '#0f172a'));
        preview.style.setProperty('--hub-preview-accent', getValue('template_color_accent', '#2563eb'));
        preview.style.setProperty('--hub-preview-surface', getValue('template_color_surface', '#ffffff'));
        preview.style.setProperty('--hub-preview-section', getValue('template_color_section_background', '#ffffff'));
        preview.style.setProperty('--hub-preview-card-bg', getValue('template_color_card_background', '#ffffff'));
        preview.style.setProperty('--hub-preview-card-text', getValue('template_color_card_text', '#0f172a'));
        preview.className = 'hub-template-preview hub-template-preview--' + baseTemplate;

        previewBadge.textContent = templateProfile.badge;
        previewTitle.textContent = title;
        previewSummary.textContent = summary;
        previewLayoutPill.textContent = columns + ' nebeneinander';
        previewTypePill.textContent = layout.charAt(0).toUpperCase() + layout.slice(1);
        previewImagePill.textContent = 'Bild ' + (imagePosition === 'top' ? 'oben' : imagePosition === 'left' ? 'links' : 'rechts');
        previewColumnsBadge.textContent = columns + ' Kachel' + (columns === 1 ? '' : 'n');
        previewCardCount.textContent = cards.length + ' Karte' + (cards.length === 1 ? '' : 'n');
        previewSectionTitle.textContent = templateProfile.sectionTitle;
        previewSectionText.textContent = templateProfile.sectionText;

        previewMeta.innerHTML = '';
        metaEntries.filter(function (item) { return item.value; }).forEach(function (item) {
            var chip = document.createElement('span');
            chip.className = 'hub-template-preview__meta-chip';
            chip.innerHTML = ''
                + '<span class="hub-template-preview__meta-chip-icon">' + escapeHtml((templateProfile.metaIcons || {})[item.key] || '•') + '</span>'
                + '<span class="hub-template-preview__meta-chip-label">' + escapeHtml(item.label || '') + ':</span>'
                + '<span class="hub-template-preview__meta-chip-value">' + escapeHtml(item.value || '') + '</span>';
            previewMeta.appendChild(chip);
        });

        previewQuicklinks.innerHTML = '';
        getPreviewLinks(defaults).forEach(function (item, index) {
            var link = document.createElement('span');
            link.className = 'hub-template-preview__quicklink';
            link.innerHTML = ''
                + '<span class="hub-template-preview__quicklink-icon">' + escapeHtml((templateProfile.linkIcons || [])[index] || '•') + '</span>'
                + '<span>' + escapeHtml(item.label || '') + '</span>';
            previewQuicklinks.appendChild(link);
        });

        previewGrid.className = 'hub-template-preview__grid hub-template-preview__grid--' + columns;
        previewGrid.innerHTML = '';

        cards.forEach(function (card, index) {
            var article = document.createElement('article');
            article.className = 'hub-template-preview__card hub-template-preview__card--' + layout + ' hub-template-preview__card--image-' + imagePosition;
            article.innerHTML = ''
                + '<div class="hub-template-preview__media">' + escapeHtml(card.image_url ? templateProfile.mediaLabel : templateProfile.cardPrefix + ' ' + (index + 1)) + '</div>'
                + '<div class="hub-template-preview__card-body">'
                + '  <span class="hub-template-preview__card-badge">' + escapeHtml(card.badge || getValue('template_card_badge_label', 'Badge')) + '</span>'
                + '  <h5 class="hub-template-preview__card-title">' + escapeHtml(card.title || ('Beispiel ' + (index + 1))) + '</h5>'
                + '  <p class="hub-template-preview__card-text">' + escapeHtml(card.summary || 'Beispieltext für die Kachel-Vorschau im Template-Editor.') + '</p>'
                + '  <div class="hub-template-preview__card-meta">'
                + '    <span class="hub-template-preview__meta-token">' + escapeHtml(card.meta_left || getValue('template_card_meta_left_label', 'Meta links')) + '</span>'
                + '    <span class="hub-template-preview__meta-token">' + escapeHtml(card.meta_right || getValue('template_card_meta_right_label', 'Meta rechts')) + '</span>'
                + '  </div>'
                + '  <span class="hub-template-preview__button">' + escapeHtml(card.button_text || getValue('template_card_button_text_label', 'Button-Text')) + '</span>'
                + '</div>';
            previewGrid.appendChild(article);
        });

        previewSections.innerHTML = '';
        getPreviewSections(defaults).forEach(function (section, index) {
            var sectionCard = document.createElement('div');
            var modifier = (templateProfile.sectionStyles || [])[index] || 'stacked';
            var detailItems = [];
            if (section.actionLabel) {
                detailItems.push(section.actionLabel);
            }
            if (section.actionUrl) {
                detailItems.push(section.actionUrl.replace('#', 'Anchor: '));
            }
            if (metaEntries[index + 3] && metaEntries[index + 3].value) {
                detailItems.push(metaEntries[index + 3].label + ': ' + metaEntries[index + 3].value);
            }
            sectionCard.className = 'hub-template-preview__section-card hub-template-preview__section-card--' + modifier;
            sectionCard.innerHTML = ''
                + '<div class="hub-template-preview__section-head">'
                + '  <span class="hub-template-preview__section-eyebrow">' + escapeHtml((templateProfile.sectionEyebrows || [])[index] || 'Section') + '</span>'
                + '  <span class="hub-template-preview__section-icon">' + escapeHtml((templateProfile.sectionIcons || [])[index] || '◆') + '</span>'
                + '</div>'
                + '<h5 class="hub-template-preview__section-title">' + escapeHtml(section.title || templateProfile.sectionTitle) + '</h5>'
                + '<p class="hub-template-preview__section-text">' + escapeHtml(section.text || templateProfile.sectionText) + '</p>'
                + '<ul class="hub-template-preview__section-list">' + detailItems.map(function (item) { return '<li>' + escapeHtml(item) + '</li>'; }).join('') + '</ul>'
                + '<div class="hub-template-preview__section-note">' + escapeHtml((templateProfile.sectionNotes || [])[index] || '') + '</div>';
            previewSections.appendChild(sectionCard);
        });
    }

    function renderLinks() {
        linksContainer.innerHTML = '';
        linksEmpty.classList.toggle('d-none', links.length !== 0);
        links.forEach(function (link, index) {
            var html = '';
            html += '<div class="border-bottom p-3"><div class="row g-2">';
            html += '<div class="col-md-5"><label class="form-label small">Label</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(link.label || '') + '" data-link-index="' + index + '" data-link-key="label"></div>';
            html += '<div class="col-md-5"><label class="form-label small">URL / Anchor</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(link.url || '') + '" data-link-index="' + index + '" data-link-key="url"></div>';
            html += '<div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm w-100 remove-template-link" data-link-index="' + index + '">Entfernen</button></div>';
            html += '</div></div>';
            linksContainer.insertAdjacentHTML('beforeend', html);
        });
        sync();
    }

    function renderSections() {
        sectionsContainer.innerHTML = '';
        sectionsEmpty.classList.toggle('d-none', sections.length !== 0);
        sections.forEach(function (section, index) {
            var html = '';
            html += '<div class="border-bottom p-3"><div class="row g-2">';
            html += '<div class="col-md-6"><label class="form-label small">Titel</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(section.title || '') + '" data-section-index="' + index + '" data-section-key="title"></div>';
            html += '<div class="col-md-6"><label class="form-label small">CTA Label</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(section.actionLabel || '') + '" data-section-index="' + index + '" data-section-key="actionLabel"></div>';
            html += '<div class="col-12"><label class="form-label small">Beschreibung</label><textarea class="form-control form-control-sm" rows="3" data-section-index="' + index + '" data-section-key="text">' + escapeHtml(section.text || '') + '</textarea></div>';
            html += '<div class="col-md-8"><label class="form-label small">CTA URL</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(section.actionUrl || '') + '" data-section-index="' + index + '" data-section-key="actionUrl"></div>';
            html += '<div class="col-md-4 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm w-100 remove-template-section" data-section-index="' + index + '">Entfernen</button></div>';
            html += '</div></div>';
            sectionsContainer.insertAdjacentHTML('beforeend', html);
        });
        sync();
    }

    function renderStarterCards() {
        starterCardsContainer.innerHTML = '';
        starterCardsEmpty.classList.toggle('d-none', starterCards.length !== 0);
        starterCards.forEach(function (card, index) {
            var html = '';
            html += '<div class="border-bottom p-3"><div class="row g-2">';
            html += '<div class="col-md-6"><label class="form-label small">Titel</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.title || '') + '" data-card-index="' + index + '" data-card-key="title"></div>';
            html += '<div class="col-md-6"><label class="form-label small">Ziel-URL</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.url || '') + '" data-card-index="' + index + '" data-card-key="url"></div>';
            html += '<div class="col-md-6"><label class="form-label small">Badge</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.badge || '') + '" data-card-index="' + index + '" data-card-key="badge"></div>';
            html += '<div class="col-md-6"><label class="form-label small">Button-Text</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.button_text || '') + '" data-card-index="' + index + '" data-card-key="button_text"></div>';
            html += '<div class="col-md-6"><label class="form-label small">Meta links</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.meta_left || '') + '" data-card-index="' + index + '" data-card-key="meta_left"></div>';
            html += '<div class="col-md-6"><label class="form-label small">Meta rechts</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.meta_right || '') + '" data-card-index="' + index + '" data-card-key="meta_right"></div>';
            html += '<div class="col-md-8"><label class="form-label small">Bild-URL</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.image_url || '') + '" data-card-index="' + index + '" data-card-key="image_url"></div>';
            html += '<div class="col-md-4"><label class="form-label small">Bild-Alt</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.image_alt || '') + '" data-card-index="' + index + '" data-card-key="image_alt"></div>';
            html += '<div class="col-md-12"><label class="form-label small">Button-Link</label><input type="text" class="form-control form-control-sm" value="' + escapeHtml(card.button_link || '') + '" data-card-index="' + index + '" data-card-key="button_link"></div>';
            html += '<div class="col-12"><label class="form-label small">Kurzbeschreibung</label><textarea class="form-control form-control-sm" rows="3" data-card-index="' + index + '" data-card-key="summary">' + escapeHtml(card.summary || '') + '</textarea></div>';
            html += '<div class="col-12 text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-starter-card" data-card-index="' + index + '">Entfernen</button></div>';
            html += '</div></div>';
            starterCardsContainer.insertAdjacentHTML('beforeend', html);
        });
        sync();
    }

    document.getElementById('addTemplateLink').addEventListener('click', function () {
        links.push({ label: '', url: '' });
        renderLinks();
    });

    document.getElementById('addTemplateSection').addEventListener('click', function () {
        sections.push({ title: '', text: '', actionLabel: '', actionUrl: '' });
        renderSections();
    });

    document.getElementById('addStarterCard').addEventListener('click', function () {
        if (starterCards.length >= 3) {
            if (typeof cmsAlert === 'function') {
                cmsAlert('Maximal drei Starter-Kacheln pro Template sind möglich.', 'warning');
            }
            return;
        }

        starterCards.push({ title: '', url: '#', summary: '', badge: '', meta_left: '', meta_right: '', image_url: '', image_alt: '', button_text: '', button_link: '' });
        renderStarterCards();
    });

    linksContainer.addEventListener('input', function (event) {
        var target = event.target;
        var index = parseInt(target.dataset.linkIndex || '-1', 10);
        var key = target.dataset.linkKey || '';
        if (index < 0 || !links[index] || !key) {
            return;
        }
        links[index][key] = target.value;
        sync();
    });

    linksContainer.addEventListener('click', function (event) {
        var button = event.target.closest('.remove-template-link');
        if (!button) {
            return;
        }
        var index = parseInt(button.dataset.linkIndex || '-1', 10);
        if (index < 0) {
            return;
        }
        links.splice(index, 1);
        renderLinks();
    });

    sectionsContainer.addEventListener('input', function (event) {
        var target = event.target;
        var index = parseInt(target.dataset.sectionIndex || '-1', 10);
        var key = target.dataset.sectionKey || '';
        if (index < 0 || !sections[index] || !key) {
            return;
        }
        sections[index][key] = target.value;
        sync();
    });

    sectionsContainer.addEventListener('click', function (event) {
        var button = event.target.closest('.remove-template-section');
        if (!button) {
            return;
        }
        var index = parseInt(button.dataset.sectionIndex || '-1', 10);
        if (index < 0) {
            return;
        }
        sections.splice(index, 1);
        renderSections();
    });

    starterCardsContainer.addEventListener('input', function (event) {
        var target = event.target;
        var index = parseInt(target.dataset.cardIndex || '-1', 10);
        var key = target.dataset.cardKey || '';
        if (index < 0 || !starterCards[index] || !key) {
            return;
        }
        starterCards[index][key] = target.value;
        sync();
    });

    starterCardsContainer.addEventListener('click', function (event) {
        var button = event.target.closest('.remove-starter-card');
        if (!button) {
            return;
        }
        var index = parseInt(button.dataset.cardIndex || '-1', 10);
        if (index < 0) {
            return;
        }
        starterCards.splice(index, 1);
        renderStarterCards();
    });

    form.addEventListener('input', function () {
        renderPreview();
    });

    form.addEventListener('change', function (event) {
        if (isNewTemplate && event && event.target && event.target.name === 'base_template') {
            applyBaseTemplateDefaults(event.target.value || 'general-it', true);
        }
        renderPreview();
    });

    bindSwitchers();
    if (isNewTemplate) {
        applyBaseTemplateDefaults(getValue('base_template', 'general-it'), true);
    }
    renderLinks();
    renderSections();
    renderStarterCards();
    renderPreview();
})();
</script>
