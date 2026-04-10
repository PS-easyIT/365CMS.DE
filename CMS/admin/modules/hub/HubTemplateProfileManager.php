<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/HubTemplateProfileCatalog.php';

use CMS\AuditLogger;
use CMS\Database;
use CMS\Logger;

final class HubTemplateProfileManager
{
    private const TEMPLATE_SETTING_KEY = 'hub_site_templates';
    private const MAX_TEMPLATE_PROFILES = 100;
    private const MAX_LINKS = 12;
    private const MAX_SECTIONS = 24;
    private const MAX_STARTER_CARDS = 3;
    private const MAX_SETTINGS_BYTES = 262144;

    private Database $db;
    private string $prefix;
    private ?array $templateProfilesCache = null;

    public function __construct(Database $db, string $prefix)
    {
        $this->db = $db;
        $this->prefix = $prefix;
    }

    public function getChoices(): array
    {
        $choices = [];
        foreach ($this->getTemplateProfiles() as $key => $profile) {
            $choices[$key] = (string)($profile['label'] ?? $key);
        }

        return $choices;
    }

    public function getProfiles(): array
    {
        return $this->getTemplateProfiles();
    }

    public function getTemplateListData(): array
    {
        $profiles = $this->getTemplateProfiles();
        $usageCounts = $this->getTemplateUsageCounts(array_keys($profiles));
        $items = [];

        foreach ($profiles as $key => $profile) {
            $items[] = [
                'key' => $key,
                'label' => (string)($profile['label'] ?? $key),
                'base_template' => (string)($profile['base_template'] ?? 'general-it'),
                'summary' => (string)($profile['summary'] ?? ''),
                'usage_count' => (int)($usageCounts[$key] ?? 0),
            ];
        }

        usort($items, static fn(array $a, array $b): int => strcasecmp($a['label'], $b['label']));

        return [
            'templates' => $items,
            'baseTemplateOptions' => HubTemplateProfileCatalog::getTemplateOptions(),
        ];
    }

    public function getTemplateEditData(?string $key): array
    {
        $profiles = $this->getTemplateProfiles();
        $normalizedKey = $key !== null ? $this->sanitizeTemplateKey($key) : '';
        $template = null;

        if ($normalizedKey !== '' && isset($profiles[$normalizedKey])) {
            $template = $profiles[$normalizedKey];
            $template['key'] = $normalizedKey;
        }

        if ($template === null) {
            $template = [
                'key' => '',
                'label' => '',
                'base_template' => 'general-it',
                'summary' => '',
                'meta' => [
                    'audience' => '',
                    'owner' => '',
                    'update_cycle' => '',
                    'focus' => '',
                    'kpi' => '',
                ],
                'meta_labels' => [
                    'audience' => 'Zielgruppe',
                    'owner' => 'Verantwortlich',
                    'update_cycle' => 'Update-Zyklus',
                    'focus' => 'Fokus',
                    'kpi' => 'KPI',
                ],
                'navigation' => [
                    'toc_enabled' => false,
                ],
                'links' => [],
                'sections' => [],
                'colors' => [
                    'hero_start' => '#1e3a5f',
                    'hero_end' => '#0f2240',
                    'accent' => '#0d9488',
                    'surface' => '#ffffff',
                    'card_background' => '#ffffff',
                    'card_text' => '#1e293b',
                    'section_background' => '#f1f5f9',
                    'table_header_start' => '#1e3a5f',
                    'table_header_end' => '#0f2240',
                ],
                'card_schema' => [
                    'columns' => 2,
                    'min_cards' => 1,
                    'max_cards' => 3,
                    'title_label' => 'Titel',
                    'summary_label' => 'Kurzbeschreibung',
                    'badge_label' => 'Badge',
                    'meta_left_label' => 'Meta links',
                    'meta_right_label' => 'Meta rechts',
                    'image_label' => 'Bild-URL',
                    'image_alt_label' => 'Bild-Alt',
                    'button_text_label' => 'Button-Text',
                    'button_link_label' => 'Button-Link',
                ],
                'card_design' => [
                    'layout' => 'standard',
                    'image_position' => 'top',
                    'image_fit' => 'cover',
                    'image_ratio' => 'wide',
                    'meta_layout' => 'split',
                    'card_radius' => 20,
                ],
                'starter_cards' => [],
            ];
        }

        return [
            'template' => $template,
            'isNew' => $normalizedKey === '' || !isset($profiles[$normalizedKey]),
            'baseTemplateOptions' => HubTemplateProfileCatalog::getTemplateOptions(),
            'baseTemplateDefaults' => $this->buildDefaultTemplateProfiles(),
        ];
    }

    public function saveTemplate(array $post): array
    {
        try {
            $profiles = $this->getTemplateProfiles();
            $existingKey = $this->sanitizeTemplateKey((string)($post['template_key'] ?? ''));
            $label = $this->sanitizePlainText((string)($post['template_label'] ?? ''), 120);
            $templateOptions = HubTemplateProfileCatalog::getTemplateOptions();
            $baseTemplate = $this->normalizeSetting((string)($post['base_template'] ?? 'general-it'), array_keys($templateOptions), 'general-it');

            if ($label === '') {
                return ['success' => false, 'error' => 'Template-Name darf nicht leer sein.'];
            }

            $isExistingTemplate = $existingKey !== '' && isset($profiles[$existingKey]);
            if (!$isExistingTemplate && count($profiles) >= self::MAX_TEMPLATE_PROFILES) {
                return ['success' => false, 'error' => 'Es können maximal ' . self::MAX_TEMPLATE_PROFILES . ' Hub-Templates gespeichert werden.'];
            }

            $key = $isExistingTemplate
                ? $existingKey
                : $this->buildUniqueTemplateKey($label, $profiles);

            $previousProfile = isset($profiles[$key]) && is_array($profiles[$key]) ? $profiles[$key] : null;

            $profiles[$key] = [
                'label' => $label,
                'base_template' => $baseTemplate,
                'summary' => $this->sanitizePlainText((string)($post['template_summary'] ?? ''), 600),
                'meta' => [
                    'audience' => $this->sanitizePlainText((string)($post['template_meta_audience'] ?? ''), 120),
                    'owner' => $this->sanitizePlainText((string)($post['template_meta_owner'] ?? ''), 120),
                    'update_cycle' => $this->sanitizePlainText((string)($post['template_meta_update_cycle'] ?? ''), 120),
                    'focus' => $this->sanitizePlainText((string)($post['template_meta_focus'] ?? ''), 160),
                    'kpi' => $this->sanitizePlainText((string)($post['template_meta_kpi'] ?? ''), 120),
                ],
                'meta_labels' => [
                    'audience' => $this->sanitizePlainText((string)($post['template_label_audience'] ?? 'Zielgruppe'), 80),
                    'owner' => $this->sanitizePlainText((string)($post['template_label_owner'] ?? 'Verantwortlich'), 80),
                    'update_cycle' => $this->sanitizePlainText((string)($post['template_label_update_cycle'] ?? 'Update-Zyklus'), 80),
                    'focus' => $this->sanitizePlainText((string)($post['template_label_focus'] ?? 'Fokus'), 80),
                    'kpi' => $this->sanitizePlainText((string)($post['template_label_kpi'] ?? 'KPI'), 80),
                ],
                'navigation' => [
                    'toc_enabled' => !empty($post['template_toc_enabled']),
                ],
                'links' => \CMS\Json::decodeArray($this->normalizeJsonArray((string)($post['template_links_json'] ?? '[]'), 'link'), []),
                'sections' => \CMS\Json::decodeArray($this->normalizeJsonArray((string)($post['template_sections_json'] ?? '[]'), 'section'), []),
                'colors' => [
                    'hero_start' => $this->normalizeColor((string)($post['template_color_hero_start'] ?? '#1f2937'), '#1f2937'),
                    'hero_end' => $this->normalizeColor((string)($post['template_color_hero_end'] ?? '#0f172a'), '#0f172a'),
                    'accent' => $this->normalizeColor((string)($post['template_color_accent'] ?? '#2563eb'), '#2563eb'),
                    'surface' => $this->normalizeColor((string)($post['template_color_surface'] ?? '#ffffff'), '#ffffff'),
                    'card_background' => $this->normalizeColor((string)($post['template_color_card_background'] ?? '#ffffff'), '#ffffff'),
                    'card_text' => $this->normalizeColor((string)($post['template_color_card_text'] ?? '#0f172a'), '#0f172a'),
                    'section_background' => $this->normalizeColor((string)($post['template_color_section_background'] ?? '#ffffff'), '#ffffff'),
                    'table_header_start' => $this->normalizeColor((string)($post['template_color_table_header_start'] ?? $post['template_color_hero_start'] ?? '#1f2937'), '#1f2937'),
                    'table_header_end' => $this->normalizeColor((string)($post['template_color_table_header_end'] ?? $post['template_color_hero_end'] ?? '#0f172a'), '#0f172a'),
                ],
                'card_schema' => [
                    'columns' => $this->normalizeNumber((int)($post['template_card_columns'] ?? 2), 1, 3, 2),
                    'min_cards' => 1,
                    'max_cards' => 3,
                    'title_label' => $this->sanitizePlainText((string)($post['template_card_title_label'] ?? 'Titel'), 80),
                    'summary_label' => $this->sanitizePlainText((string)($post['template_card_summary_label'] ?? 'Kurzbeschreibung'), 80),
                    'badge_label' => $this->sanitizePlainText((string)($post['template_card_badge_label'] ?? 'Badge'), 80),
                    'meta_left_label' => $this->sanitizePlainText((string)($post['template_card_meta_left_label'] ?? 'Meta links'), 80),
                    'meta_right_label' => $this->sanitizePlainText((string)($post['template_card_meta_right_label'] ?? 'Meta rechts'), 80),
                    'image_label' => $this->sanitizePlainText((string)($post['template_card_image_label'] ?? 'Bild-URL'), 80),
                    'image_alt_label' => $this->sanitizePlainText((string)($post['template_card_image_alt_label'] ?? 'Bild-Alt'), 80),
                    'button_text_label' => $this->sanitizePlainText((string)($post['template_card_button_text_label'] ?? 'Button-Text'), 80),
                    'button_link_label' => $this->sanitizePlainText((string)($post['template_card_button_link_label'] ?? 'Button-Link'), 80),
                ],
                'card_design' => [
                    'layout' => $this->normalizeSetting((string)($post['hub_card_layout'] ?? 'standard'), ['standard', 'feature', 'compact'], 'standard'),
                    'image_position' => $this->normalizeSetting((string)($post['hub_card_image_position'] ?? 'top'), ['top', 'left', 'right'], 'top'),
                    'image_fit' => $this->normalizeSetting((string)($post['hub_card_image_fit'] ?? 'cover'), ['cover', 'contain'], 'cover'),
                    'image_ratio' => $this->normalizeSetting((string)($post['hub_card_image_ratio'] ?? 'wide'), ['wide', 'square', 'portrait'], 'wide'),
                    'meta_layout' => $this->normalizeSetting((string)($post['hub_card_meta_layout'] ?? 'split'), ['split', 'stacked'], 'split'),
                    'card_radius' => $this->normalizeNumber((int)($post['template_card_radius'] ?? 20), 0, 48, 20),
                ],
                'starter_cards' => $this->normalizeStarterCards((string)($post['template_starter_cards_json'] ?? '[]')),
            ];

            $profiles[$key] = $this->normalizeTemplateProfile($key, $profiles[$key], $previousProfile);

            $this->saveTemplateProfiles($profiles);
            $this->syncInheritedHubSitesWithTemplate($key, $previousProfile, $profiles[$key]);
            $this->logSuccess(
                'hub.template.saved',
                'Hub-Template gespeichert.',
                [
                    'template_key' => $key,
                    'base_template' => $baseTemplate,
                    'is_new' => !$isExistingTemplate,
                ]
            );

            return ['success' => true, 'key' => $key, 'message' => 'Template gespeichert.'];
        } catch (\Throwable $e) {
            return $this->failResult(
                'hub.template.save.failed',
                'Hub-Template konnte nicht gespeichert werden.',
                $e,
                ['template_key' => $this->sanitizeTemplateKey((string)($post['template_key'] ?? ''))]
            );
        }
    }

    public function duplicateTemplate(string $key): array
    {
        try {
            $profiles = $this->getTemplateProfiles();
            $key = $this->sanitizeTemplateKey($key);

            if ($key === '' || !isset($profiles[$key])) {
                return ['success' => false, 'error' => 'Template nicht gefunden.'];
            }

            if (count($profiles) >= self::MAX_TEMPLATE_PROFILES) {
                return ['success' => false, 'error' => 'Es können maximal ' . self::MAX_TEMPLATE_PROFILES . ' Hub-Templates gespeichert werden.'];
            }

            $copy = $profiles[$key];
            $copy['label'] = ((string)($copy['label'] ?? 'Template')) . ' (Kopie)';
            $newKey = $this->buildUniqueTemplateKey((string)$copy['label'], $profiles);
            $profiles[$newKey] = $this->normalizeTemplateProfile($newKey, $copy, null);
            $this->saveTemplateProfiles($profiles);
            $this->logSuccess('hub.template.duplicated', 'Hub-Template kopiert.', ['template_key' => $key, 'new_template_key' => $newKey]);

            return ['success' => true, 'key' => $newKey, 'message' => 'Template kopiert.'];
        } catch (\Throwable $e) {
            return $this->failResult('hub.template.duplicate.failed', 'Hub-Template konnte nicht dupliziert werden.', $e, ['template_key' => $key]);
        }
    }

    public function deleteTemplate(string $key): array
    {
        try {
            $profiles = $this->getTemplateProfiles();
            $key = $this->sanitizeTemplateKey($key);

            if ($key === '' || !isset($profiles[$key])) {
                return ['success' => false, 'error' => 'Template nicht gefunden.'];
            }

            if (isset(HubTemplateProfileCatalog::getTemplatePresets()[$key])) {
                return ['success' => false, 'error' => 'Standard-Templates können nicht gelöscht werden. Du kannst sie aber bearbeiten, kopieren und umbenennen.'];
            }

            if ($this->countTemplateUsage($key) > 0) {
                return ['success' => false, 'error' => 'Template wird noch von Hub-Sites verwendet und kann nicht gelöscht werden.'];
            }

            unset($profiles[$key]);
            $this->saveTemplateProfiles($profiles);
            $this->logSuccess('hub.template.deleted', 'Hub-Template gelöscht.', ['template_key' => $key]);

            return ['success' => true, 'message' => 'Template gelöscht.'];
        } catch (\Throwable $e) {
            return $this->failResult('hub.template.delete.failed', 'Hub-Template konnte nicht gelöscht werden.', $e, ['template_key' => $key]);
        }
    }

    private function normalizeJsonArray(string $json, string $mode): string
    {
        $items = \CMS\Json::decodeArray($json, []);
        if (!is_array($items)) {
            return '[]';
        }

        $normalized = [];
        $limit = $mode === 'link' ? self::MAX_LINKS : self::MAX_SECTIONS;
        foreach (array_slice($items, 0, $limit) as $item) {
            if (!is_array($item)) {
                continue;
            }

            if ($mode === 'link') {
                $label = $this->sanitizePlainText((string)($item['label'] ?? ''), 80);
                $url = $this->normalizeUrlValue((string)($item['url'] ?? ''), 240);
                if ($label === '') {
                    continue;
                }
                $normalized[] = [
                    'label' => $label,
                    'url' => $url !== '' ? $url : '#',
                ];
                continue;
            }

            $title = $this->sanitizePlainText((string)($item['title'] ?? ''), 120);
            $text = $this->sanitizePlainText((string)($item['text'] ?? ''), 600);
            $actionLabel = $this->sanitizePlainText((string)($item['actionLabel'] ?? ''), 80);
            $actionUrl = $this->normalizeUrlValue((string)($item['actionUrl'] ?? ''), 240);

            if ($title === '' && $text === '') {
                continue;
            }

            $normalized[] = [
                'title' => $title,
                'text' => $text,
                'actionLabel' => $actionLabel,
                'actionUrl' => $actionUrl,
            ];
        }

        return json_encode($normalized, JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    private function normalizeSetting(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function getTemplateProfiles(): array
    {
        if ($this->templateProfilesCache !== null) {
            return $this->templateProfilesCache;
        }

        $defaultProfiles = $this->buildDefaultTemplateProfiles();
        $row = $this->db->get_row(
            "SELECT option_value FROM {$this->prefix}settings WHERE option_name = ? LIMIT 1",
            [self::TEMPLATE_SETTING_KEY]
        );

        $stored = [];
        if ($row && !empty($row->option_value)) {
            $decoded = \CMS\Json::decodeArray($row->option_value ?? null, []);
            if (is_array($decoded)) {
                foreach ($decoded as $key => $profile) {
                    if (!is_array($profile)) {
                        continue;
                    }
                    $normalizedKey = $this->sanitizeTemplateKey((string)$key);
                    if ($normalizedKey === '') {
                        continue;
                    }
                    $stored[$normalizedKey] = $this->normalizeTemplateProfile($normalizedKey, $profile, $defaultProfiles[$normalizedKey] ?? null);
                }
            }
        }

        foreach ($defaultProfiles as $key => $profile) {
            if (!isset($stored[$key])) {
                $stored[$key] = $profile;
            }
        }

        if (count($stored) > self::MAX_TEMPLATE_PROFILES) {
            $stored = array_slice($stored, 0, self::MAX_TEMPLATE_PROFILES, true);
        }

        $this->templateProfilesCache = $stored;
        return $this->templateProfilesCache;
    }

    private function saveTemplateProfiles(array $profiles): void
    {
        $payload = [];
        foreach ($profiles as $key => $profile) {
            if (!is_array($profile)) {
                continue;
            }
            $normalizedKey = $this->sanitizeTemplateKey((string)$key);
            if ($normalizedKey === '') {
                continue;
            }
            $payload[$normalizedKey] = $this->normalizeTemplateProfile($normalizedKey, $profile, null);
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '{}';
        if (strlen($json) > self::MAX_SETTINGS_BYTES) {
            throw new \RuntimeException('Hub-Template-Payload überschreitet das erlaubte Größenlimit.');
        }

        $exists = (int)($this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?", [self::TEMPLATE_SETTING_KEY]) ?? 0);
        if ($exists > 0) {
            $updated = $this->db->update('settings', ['option_value' => $json], ['option_name' => self::TEMPLATE_SETTING_KEY]);
            if ($updated !== true) {
                throw new \RuntimeException('Hub-Template-Settings konnten nicht aktualisiert werden.');
            }
        } else {
            $insertId = $this->db->insert('settings', ['option_name' => self::TEMPLATE_SETTING_KEY, 'option_value' => $json]);
            if ($insertId === false) {
                throw new \RuntimeException('Hub-Template-Settings konnten nicht gespeichert werden.');
            }
        }

        $this->templateProfilesCache = $payload;
    }

    private function buildDefaultTemplateProfiles(): array
    {
        $profiles = [];
        foreach (HubTemplateProfileCatalog::buildRawDefaultProfiles() as $key => $profile) {
            $profiles[$key] = $this->normalizeTemplateProfile($key, $profile, null);
        }

        return $profiles;
    }

    private function normalizeTemplateProfile(string $key, array $profile, ?array $fallback): array
    {
        $fallback = $fallback ?? [
            'label' => $key,
            'base_template' => 'general-it',
            'summary' => '',
            'meta' => ['audience' => '', 'owner' => '', 'update_cycle' => '', 'focus' => '', 'kpi' => ''],
            'meta_labels' => ['audience' => 'Zielgruppe', 'owner' => 'Verantwortlich', 'update_cycle' => 'Update-Zyklus', 'focus' => 'Fokus', 'kpi' => 'KPI'],
            'navigation' => ['toc_enabled' => false],
            'links' => [],
            'sections' => [],
            'colors' => HubTemplateProfileCatalog::defaultTemplateColors('general-it'),
            'card_schema' => HubTemplateProfileCatalog::defaultCardSchema(),
            'card_design' => ['layout' => 'standard', 'image_position' => 'top', 'image_fit' => 'cover', 'image_ratio' => 'wide', 'meta_layout' => 'split', 'card_radius' => 20],
            'starter_cards' => [],
        ];

        $templateOptions = HubTemplateProfileCatalog::getTemplateOptions();

        return [
            'label' => $this->sanitizePlainText((string)($profile['label'] ?? $fallback['label']), 120),
            'base_template' => $this->normalizeSetting((string)($profile['base_template'] ?? $fallback['base_template']), array_keys($templateOptions), 'general-it'),
            'summary' => $this->truncateText((string)($profile['summary'] ?? $fallback['summary']), 600),
            'meta' => [
                'audience' => $this->sanitizePlainText((string)($profile['meta']['audience'] ?? $fallback['meta']['audience'] ?? ''), 120),
                'owner' => $this->sanitizePlainText((string)($profile['meta']['owner'] ?? $fallback['meta']['owner'] ?? ''), 120),
                'update_cycle' => $this->sanitizePlainText((string)($profile['meta']['update_cycle'] ?? $fallback['meta']['update_cycle'] ?? ''), 120),
                'focus' => $this->sanitizePlainText((string)($profile['meta']['focus'] ?? $fallback['meta']['focus'] ?? ''), 160),
                'kpi' => $this->sanitizePlainText((string)($profile['meta']['kpi'] ?? $fallback['meta']['kpi'] ?? ''), 120),
            ],
            'meta_labels' => [
                'audience' => $this->sanitizePlainText((string)($profile['meta_labels']['audience'] ?? $fallback['meta_labels']['audience'] ?? 'Zielgruppe'), 80),
                'owner' => $this->sanitizePlainText((string)($profile['meta_labels']['owner'] ?? $fallback['meta_labels']['owner'] ?? 'Verantwortlich'), 80),
                'update_cycle' => $this->sanitizePlainText((string)($profile['meta_labels']['update_cycle'] ?? $fallback['meta_labels']['update_cycle'] ?? 'Update-Zyklus'), 80),
                'focus' => $this->sanitizePlainText((string)($profile['meta_labels']['focus'] ?? $fallback['meta_labels']['focus'] ?? 'Fokus'), 80),
                'kpi' => $this->sanitizePlainText((string)($profile['meta_labels']['kpi'] ?? $fallback['meta_labels']['kpi'] ?? 'KPI'), 80),
            ],
            'navigation' => [
                'toc_enabled' => !empty($profile['navigation']['toc_enabled'] ?? $fallback['navigation']['toc_enabled'] ?? false),
            ],
            'links' => \CMS\Json::decodeArray($this->normalizeJsonArray(json_encode($profile['links'] ?? $fallback['links'] ?? [], JSON_UNESCAPED_UNICODE) ?: '[]', 'link'), []),
            'sections' => \CMS\Json::decodeArray($this->normalizeJsonArray(json_encode($profile['sections'] ?? $fallback['sections'] ?? [], JSON_UNESCAPED_UNICODE) ?: '[]', 'section'), []),
            'colors' => [
                'hero_start' => $this->normalizeColor((string)($profile['colors']['hero_start'] ?? $fallback['colors']['hero_start'] ?? '#1f2937'), '#1f2937'),
                'hero_end' => $this->normalizeColor((string)($profile['colors']['hero_end'] ?? $fallback['colors']['hero_end'] ?? '#0f172a'), '#0f172a'),
                'accent' => $this->normalizeColor((string)($profile['colors']['accent'] ?? $fallback['colors']['accent'] ?? '#2563eb'), '#2563eb'),
                'surface' => $this->normalizeColor((string)($profile['colors']['surface'] ?? $fallback['colors']['surface'] ?? '#ffffff'), '#ffffff'),
                'card_background' => $this->normalizeColor((string)($profile['colors']['card_background'] ?? $fallback['colors']['card_background'] ?? '#ffffff'), '#ffffff'),
                'card_text' => $this->normalizeColor((string)($profile['colors']['card_text'] ?? $fallback['colors']['card_text'] ?? '#0f172a'), '#0f172a'),
                'section_background' => $this->normalizeColor((string)($profile['colors']['section_background'] ?? $fallback['colors']['section_background'] ?? '#ffffff'), '#ffffff'),
                'table_header_start' => $this->normalizeColor((string)($profile['colors']['table_header_start'] ?? $fallback['colors']['table_header_start'] ?? $fallback['colors']['hero_start'] ?? '#1f2937'), '#1f2937'),
                'table_header_end' => $this->normalizeColor((string)($profile['colors']['table_header_end'] ?? $fallback['colors']['table_header_end'] ?? $fallback['colors']['hero_end'] ?? '#0f172a'), '#0f172a'),
            ],
            'card_schema' => [
                'columns' => $this->normalizeNumber((int)($profile['card_schema']['columns'] ?? $fallback['card_schema']['columns'] ?? 2), 1, 3, 2),
                'min_cards' => 1,
                'max_cards' => 3,
                'title_label' => $this->sanitizePlainText((string)($profile['card_schema']['title_label'] ?? $fallback['card_schema']['title_label'] ?? 'Titel'), 80),
                'summary_label' => $this->sanitizePlainText((string)($profile['card_schema']['summary_label'] ?? $fallback['card_schema']['summary_label'] ?? 'Kurzbeschreibung'), 80),
                'badge_label' => $this->sanitizePlainText((string)($profile['card_schema']['badge_label'] ?? $fallback['card_schema']['badge_label'] ?? 'Badge'), 80),
                'meta_left_label' => $this->sanitizePlainText((string)($profile['card_schema']['meta_left_label'] ?? $fallback['card_schema']['meta_left_label'] ?? 'Meta links'), 80),
                'meta_right_label' => $this->sanitizePlainText((string)($profile['card_schema']['meta_right_label'] ?? $fallback['card_schema']['meta_right_label'] ?? 'Meta rechts'), 80),
                'image_label' => $this->sanitizePlainText((string)($profile['card_schema']['image_label'] ?? $fallback['card_schema']['image_label'] ?? 'Bild-URL'), 80),
                'image_alt_label' => $this->sanitizePlainText((string)($profile['card_schema']['image_alt_label'] ?? $fallback['card_schema']['image_alt_label'] ?? 'Bild-Alt'), 80),
                'button_text_label' => $this->sanitizePlainText((string)($profile['card_schema']['button_text_label'] ?? $fallback['card_schema']['button_text_label'] ?? 'Button-Text'), 80),
                'button_link_label' => $this->sanitizePlainText((string)($profile['card_schema']['button_link_label'] ?? $fallback['card_schema']['button_link_label'] ?? 'Button-Link'), 80),
            ],
            'card_design' => [
                'layout' => $this->normalizeSetting((string)($profile['card_design']['layout'] ?? $fallback['card_design']['layout'] ?? 'standard'), ['standard', 'feature', 'compact'], 'standard'),
                'image_position' => $this->normalizeSetting((string)($profile['card_design']['image_position'] ?? $fallback['card_design']['image_position'] ?? 'top'), ['top', 'left', 'right'], 'top'),
                'image_fit' => $this->normalizeSetting((string)($profile['card_design']['image_fit'] ?? $fallback['card_design']['image_fit'] ?? 'cover'), ['cover', 'contain'], 'cover'),
                'image_ratio' => $this->normalizeSetting((string)($profile['card_design']['image_ratio'] ?? $fallback['card_design']['image_ratio'] ?? 'wide'), ['wide', 'square', 'portrait'], 'wide'),
                'meta_layout' => $this->normalizeSetting((string)($profile['card_design']['meta_layout'] ?? $fallback['card_design']['meta_layout'] ?? 'split'), ['split', 'stacked'], 'split'),
                'card_radius' => $this->normalizeNumber((int)($profile['card_design']['card_radius'] ?? $fallback['card_design']['card_radius'] ?? 20), 0, 48, 20),
            ],
            'starter_cards' => $this->normalizeStarterCards(json_encode($profile['starter_cards'] ?? $fallback['starter_cards'] ?? [], JSON_UNESCAPED_UNICODE) ?: '[]'),
        ];
    }

    private function countTemplateUsage(string $key): int
    {
        return (int)($this->getTemplateUsageCounts([$key])[$key] ?? 0);
    }

    /**
     * @param array<int, string> $templateKeys
     * @return array<string, int>
     */
    private function getTemplateUsageCounts(array $templateKeys): array
    {
        $normalizedKeys = [];
        foreach ($templateKeys as $templateKey) {
            $normalizedKey = $this->sanitizeTemplateKey((string)$templateKey);
            if ($normalizedKey === '') {
                continue;
            }

            $normalizedKeys[$normalizedKey] = 0;
        }

        if ($normalizedKeys === []) {
            return [];
        }

        $rows = $this->db->get_results(
            "SELECT JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.hub_template')) AS template_key,
                    COUNT(*) AS usage_count
             FROM {$this->prefix}site_tables
             WHERE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.content_mode')), 'table') = 'hub'
             GROUP BY JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.hub_template'))",
            []
        ) ?: [];

        foreach ($rows as $row) {
            $templateKey = $this->sanitizeTemplateKey((string)($row->template_key ?? ''));
            if ($templateKey === '' || !array_key_exists($templateKey, $normalizedKeys)) {
                continue;
            }

            $normalizedKeys[$templateKey] = max(0, (int)($row->usage_count ?? 0));
        }

        return $normalizedKeys;
    }

    private function buildUniqueTemplateKey(string $label, array $profiles): string
    {
        $base = $this->sanitizeTemplateKey($label);
        if ($base === '') {
            $base = 'hub-template';
        }

        $key = $base;
        $suffix = 2;
        while (isset($profiles[$key])) {
            $key = $base . '-' . $suffix;
            $suffix++;
        }

        return $key;
    }

    private function sanitizeTemplateKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = (string)preg_replace('/[^a-z0-9]+/i', '-', $value);
        return trim($value, '-');
    }

    private function normalizeColor(string $value, string $fallback): string
    {
        $value = trim($value);
        if ((bool)preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return strtolower($value);
        }

        return strtolower($fallback);
    }

    private function normalizeNumber(int $value, int $min, int $max, int $fallback): int
    {
        if ($value < $min || $value > $max) {
            return $fallback;
        }

        return $value;
    }

    private function normalizeStarterCards(string $json): array
    {
        $cards = \CMS\Json::decodeArray($json, []);
        if (!is_array($cards)) {
            return [];
        }

        $normalized = [];
        foreach (array_slice($cards, 0, self::MAX_STARTER_CARDS) as $card) {
            if (!is_array($card)) {
                continue;
            }

            $normalized[] = [
                'title' => $this->sanitizePlainText((string)($card['title'] ?? ''), 160),
                'summary' => $this->sanitizePlainText((string)($card['summary'] ?? ''), 600),
                'badge' => $this->sanitizePlainText((string)($card['badge'] ?? ''), 80),
                'meta_left' => $this->sanitizePlainText((string)($card['meta_left'] ?? ''), 120),
                'meta_right' => $this->sanitizePlainText((string)($card['meta_right'] ?? ''), 120),
                'image_url' => $this->normalizeUrlValue((string)($card['image_url'] ?? ''), 500),
                'image_alt' => $this->sanitizePlainText((string)($card['image_alt'] ?? ''), 160),
                'button_text' => $this->sanitizePlainText((string)($card['button_text'] ?? ''), 80),
                'button_link' => $this->normalizeUrlValue((string)($card['button_link'] ?? ''), 500),
                'url' => $this->normalizeUrlValue((string)($card['url'] ?? '#'), 500),
            ];
        }

        return $normalized;
    }

    private function syncInheritedHubSitesWithTemplate(string $templateKey, ?array $previousProfile, array $currentProfile): void
    {
        if ($previousProfile === null) {
            return;
        }

        $previousLinks = $this->normalizeComparableLinks($previousProfile['links'] ?? []);
        $currentLinks = $this->normalizeComparableLinks($currentProfile['links'] ?? []);
        $previousStarterCards = $this->normalizeComparableStarterCards($previousProfile['starter_cards'] ?? []);
        $currentStarterCardsComparable = $this->normalizeComparableStarterCards($currentProfile['starter_cards'] ?? []);

        if ($previousLinks === $currentLinks && $previousStarterCards === $currentStarterCardsComparable) {
            return;
        }

        $sites = $this->db->get_results(
            "SELECT id, rows_json, settings_json
             FROM {$this->prefix}site_tables
             WHERE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.content_mode')), 'table') = 'hub'
               AND JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.hub_template')) = ?",
            [$templateKey]
        ) ?: [];

        if ($sites === []) {
            return;
        }

        $currentStarterCards = $this->buildSiteRowsFromStarterCards($currentProfile['starter_cards'] ?? []);

        foreach ($sites as $site) {
            $siteId = (int) ($site->id ?? 0);
            if ($siteId <= 0) {
                continue;
            }

            $settings = \CMS\Json::decodeArray($site->settings_json ?? null, []);
            $rows = \CMS\Json::decodeArray($site->rows_json ?? null, []);

            if (!is_array($settings)) {
                $settings = [];
            }
            if (!is_array($rows)) {
                $rows = [];
            }

            $settingsChanged = false;
            $rowsChanged = false;

            $storedLinks = $this->normalizeComparableLinks(\CMS\Json::decodeArray((string) ($settings['hub_links_json'] ?? '[]'), []));
            if ($storedLinks !== [] && $storedLinks === $previousLinks) {
                $settings['hub_links_json'] = '[]';
                $settingsChanged = true;
            }

            $storedRowsComparable = $this->normalizeComparableSiteRows($rows);
            if ($storedRowsComparable !== [] && $storedRowsComparable === $previousStarterCards) {
                $rows = $currentStarterCards;
                $rowsChanged = true;
            }

            if (!$settingsChanged && !$rowsChanged) {
                continue;
            }

            $updatePayload = ['updated_at' => date('Y-m-d H:i:s')];
            if ($settingsChanged) {
                $updatePayload['settings_json'] = json_encode($settings, JSON_UNESCAPED_UNICODE);
            }
            if ($rowsChanged) {
                $updatePayload['rows_json'] = json_encode($rows, JSON_UNESCAPED_UNICODE);
            }

            $updated = $this->db->update('site_tables', $updatePayload, [
                'id' => $siteId,
            ]);

            if ($updated !== true) {
                $this->logFailure(
                    'hub.template.sync_site.failed',
                    'Vererbte Hub-Site konnte nach Template-Update nicht synchronisiert werden.',
                    null,
                    ['template_key' => $templateKey, 'site_id' => $siteId]
                );
            }
        }
    }

    private function normalizeUrlValue(string $value, int $maxLength): string
    {
        $value = trim($value);
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', '', $value) ?? '';
        if ($value === '') {
            return '';
        }

        $value = $this->truncateText($value, $maxLength);

        if ($value === '#') {
            return '#';
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        if ($scheme === '') {
            return '#';
        }

        if (!in_array($scheme, ['http', 'https'], true)) {
            return '#';
        }

        return filter_var($value, FILTER_SANITIZE_URL) ?: '#';
    }

    private function truncateText(string $value, int $maxLength): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return cms_truncate_text($value, $maxLength, '');
    }

    private function sanitizePlainText(string $value, int $maxLength): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $value) ?? '';

        return $this->truncateText($value, $maxLength);
    }

    private function failResult(string $action, string $message, ?\Throwable $exception = null, array $context = []): array
    {
        $this->logFailure($action, $message, $exception, $context);

        return ['success' => false, 'error' => $message . ' Bitte Logs prüfen.'];
    }

    private function logFailure(string $action, string $message, ?\Throwable $exception = null, array $context = []): void
    {
        if ($exception !== null) {
            $context['exception'] = $exception->getMessage();
        }

        Logger::instance()->withChannel('admin.hub-template-profiles')->error($message, $context);
        AuditLogger::instance()->log(
            AuditLogger::CAT_CONTENT,
            $action,
            $message,
            'hub-template-profiles',
            null,
            $context,
            'error'
        );
    }

    private function logSuccess(string $action, string $message, array $context = []): void
    {
        Logger::instance()->withChannel('admin.hub-template-profiles')->info($message, $context);
        AuditLogger::instance()->log(
            AuditLogger::CAT_CONTENT,
            $action,
            $message,
            'hub-template-profiles',
            null,
            $context,
            'info'
        );
    }

    private function normalizeComparableLinks(mixed $links): array
    {
        if (!is_array($links)) {
            return [];
        }

        $normalized = [];
        foreach ($links as $link) {
            if (!is_array($link)) {
                continue;
            }

            $label = $this->sanitizePlainText((string) ($link['label'] ?? ''), 80);
            $url = $this->truncateText((string) ($link['url'] ?? '#'), 240);
            if ($label === '') {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'url' => $url !== '' ? $url : '#',
            ];
        }

        return array_slice($normalized, 0, 6);
    }

    private function normalizeComparableStarterCards(mixed $cards): array
    {
        if (!is_array($cards)) {
            return [];
        }

        $normalized = [];
        foreach (array_slice($cards, 0, 3) as $card) {
            if (!is_array($card)) {
                continue;
            }

            $normalized[] = [
                'title' => $this->sanitizePlainText((string) ($card['title'] ?? ''), 160),
                'summary' => $this->truncateText((string) ($card['summary'] ?? ''), 600),
                'badge' => $this->sanitizePlainText((string) ($card['badge'] ?? ''), 80),
                'meta_left' => $this->sanitizePlainText((string) ($card['meta_left'] ?? ''), 120),
                'meta_right' => $this->sanitizePlainText((string) ($card['meta_right'] ?? ''), 120),
                'image_url' => $this->truncateText((string) ($card['image_url'] ?? ''), 500),
                'image_alt' => $this->sanitizePlainText((string) ($card['image_alt'] ?? ''), 160),
                'button_text' => $this->sanitizePlainText((string) ($card['button_text'] ?? ''), 80),
                'button_link' => $this->truncateText((string) ($card['button_link'] ?? ''), 500),
                'url' => $this->truncateText((string) ($card['url'] ?? '#'), 500),
            ];
        }

        return $normalized;
    }

    private function normalizeComparableSiteRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $normalized = [];
        foreach (array_slice($rows, 0, 3) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized[] = [
                'title' => $this->sanitizePlainText((string) ($row['title'] ?? ''), 160),
                'summary' => $this->truncateText((string) ($row['summary'] ?? ''), 600),
                'badge' => $this->sanitizePlainText((string) ($row['badge'] ?? ''), 80),
                'meta_left' => $this->sanitizePlainText((string) ($row['meta_left'] ?? ''), 120),
                'meta_right' => $this->sanitizePlainText((string) ($row['meta_right'] ?? ''), 120),
                'image_url' => $this->truncateText((string) ($row['image_url'] ?? ''), 500),
                'image_alt' => $this->sanitizePlainText((string) ($row['image_alt'] ?? ''), 160),
                'button_text' => $this->sanitizePlainText((string) ($row['button_text'] ?? ''), 80),
                'button_link' => $this->truncateText((string) ($row['button_link'] ?? ''), 500),
                'url' => $this->truncateText((string) ($row['url'] ?? '#'), 500),
            ];
        }

        return $normalized;
    }

    private function buildSiteRowsFromStarterCards(mixed $cards): array
    {
        $starterCards = $this->normalizeComparableStarterCards($cards);
        $rows = [];

        foreach ($starterCards as $card) {
            $rows[] = [
                'title' => (string) ($card['title'] ?? ''),
                'title_en' => '',
                'url' => (string) ($card['url'] ?? '#'),
                'summary' => (string) ($card['summary'] ?? ''),
                'summary_en' => '',
                'badge' => (string) ($card['badge'] ?? ''),
                'badge_en' => '',
                'meta' => '',
                'meta_en' => '',
                'meta_left' => (string) ($card['meta_left'] ?? ''),
                'meta_left_en' => '',
                'meta_right' => (string) ($card['meta_right'] ?? ''),
                'meta_right_en' => '',
                'image_url' => (string) ($card['image_url'] ?? ''),
                'image_alt' => (string) ($card['image_alt'] ?? ''),
                'image_alt_en' => '',
                'button_text' => (string) ($card['button_text'] ?? ''),
                'button_text_en' => '',
                'button_link' => (string) ($card['button_link'] ?? ''),
            ];
        }

        return $rows;
    }

}
