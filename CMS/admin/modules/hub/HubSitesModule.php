<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/HubTemplateProfileManager.php';

use CMS\Database;
use CMS\Hooks;

class HubSitesModule
{
    private Database $db;
    private string $prefix;
    private HubTemplateProfileManager $templateProfileManager;
    private ?bool $hasTableSlugColumn = null;

    private const DEFAULT_SETTINGS = [
        'content_mode' => 'hub',
        'hub_slug' => '',
        'hub_domains' => [],
        'hub_template' => 'general-it',
        'hub_feature_card_interval' => 0,
        'hub_feature_cards_json' => '[]',
        'hub_badge' => '',
        'hub_badge_en' => '',
        'hub_hero_title' => '',
        'hub_hero_title_en' => '',
        'hub_hero_text' => '',
        'hub_hero_text_en' => '',
        'hub_cta_label' => '',
        'hub_cta_label_en' => '',
        'hub_cta_url' => '',
        'hub_meta_audience' => '',
        'hub_meta_audience_en' => '',
        'hub_meta_owner' => '',
        'hub_meta_owner_en' => '',
        'hub_meta_update_cycle' => '',
        'hub_meta_update_cycle_en' => '',
        'hub_meta_focus' => '',
        'hub_meta_focus_en' => '',
        'hub_meta_kpi' => '',
        'hub_meta_kpi_en' => '',
        'hub_links_json' => '[]',
        'hub_sections_json' => '[]',
        'hub_card_layout' => 'standard',
        'hub_card_image_position' => 'top',
        'hub_card_image_fit' => 'cover',
        'hub_card_image_ratio' => 'wide',
        'hub_card_meta_layout' => 'split',
    ];

    public function __construct()
    {
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->templateProfileManager = new HubTemplateProfileManager($this->db, $this->prefix);
    }

    public function getListData(): array
    {
        $search = trim((string)($_GET['q'] ?? ''));
        $where = '';
        $params = [];
        $selectSlug = $this->hasTableSlugColumn() ? 'table_slug,' : "'' AS table_slug,";

        if ($search !== '') {
            $where = ' AND (table_name LIKE ? OR description LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $rows = $this->db->get_results(
            "SELECT id, table_name, description, {$selectSlug} rows_json, settings_json,
                    JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.hub_slug')) AS hub_slug,
                    created_at, updated_at
             FROM {$this->prefix}site_tables
             WHERE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.content_mode')), 'table') = 'hub'{$where}
             ORDER BY updated_at DESC
             LIMIT 200",
            $params
        ) ?: [];

        $sites = array_map(function ($row): array {
            $item = (array)$row;
            $settings = \CMS\Json::decodeArray($item['settings_json'] ?? null, []);
            return [
                'id' => (int)($item['id'] ?? 0),
                'table_name' => (string)($item['table_name'] ?? ''),
                'description' => trim(strip_tags((string)($item['description'] ?? ''))),
                'hub_slug' => (string)(($item['hub_slug'] ?? '') !== '' ? $item['hub_slug'] : ($item['table_slug'] ?? '')),
                'template' => (string)($settings['hub_template'] ?? 'general-it'),
                'card_count' => count(\CMS\Json::decodeArray($item['rows_json'] ?? null, [])),
                'updated_at' => (string)($item['updated_at'] ?? $item['created_at'] ?? ''),
            ];
        }, $rows);

        return [
            'sites' => $sites,
            'total' => count($sites),
            'search' => $search,
            'templateOptions' => $this->templateProfileManager->getChoices(),
        ];
    }

    public function getEditData(?int $id): array
    {
        $site = null;

        if ($id !== null) {
            $row = $this->db->get_row(
                "SELECT * FROM {$this->prefix}site_tables WHERE id = ? LIMIT 1",
                [$id]
            );

            if ($row) {
                $site = (array)$row;
                $site['cards'] = \CMS\Json::decodeArray($site['rows_json'] ?? null, []);
                $site['settings'] = array_merge(
                    self::DEFAULT_SETTINGS,
                    \CMS\Json::decodeArray($site['settings_json'] ?? null, [])
                );
                if (($site['settings']['hub_slug'] ?? '') === '' && !empty($site['table_slug'])) {
                    $site['settings']['hub_slug'] = (string)$site['table_slug'];
                }
            }
        }

        return [
            'site' => $site,
            'isNew' => $site === null,
            'defaults' => self::DEFAULT_SETTINGS,
            'templateOptions' => $this->templateProfileManager->getChoices(),
            'templateProfiles' => $this->templateProfileManager->getProfiles(),
        ];
    }

    public function getTemplateListData(): array
    {
        return $this->templateProfileManager->getTemplateListData();
    }

    public function getTemplateEditData(?string $key): array
    {
        return $this->templateProfileManager->getTemplateEditData($key);
    }

    public function save(array $post): array
    {
        $id = (int)($post['id'] ?? 0);
        $name = trim(strip_tags((string)($post['site_name'] ?? '')));
        $description = $this->sanitizeRichText((string)($post['description'] ?? ''), 4000);
        $cards = \CMS\Json::decodeArray($post['cards_json'] ?? null, []);
        $normalizedDomains = $this->normalizeHubDomains((string)($post['hub_domains'] ?? ''));

        if ($name === '') {
            return ['success' => false, 'error' => 'Name darf nicht leer sein.'];
        }

        if (!empty($normalizedDomains['errors'])) {
            return ['success' => false, 'error' => (string)$normalizedDomains['errors'][0]];
        }

        if (!is_array($cards)) {
            $cards = [];
        }

        $hubDomains = $normalizedDomains['domains'] ?? [];
        foreach ($hubDomains as $domain) {
            if ($this->hubDomainExists((string)$domain, $id > 0 ? $id : null)) {
                return ['success' => false, 'error' => 'Die Zusatzdomain „' . $domain . '“ ist bereits einer anderen Hub-Site zugeordnet.'];
            }
        }

        $slug = $this->buildUniqueHubSlug($name, $id > 0 ? $id : null);

        $templateChoices = $this->templateProfileManager->getChoices();

        $settings = [
            'content_mode' => 'hub',
            'hub_slug' => $slug,
            'hub_domains' => $hubDomains,
            'hub_template' => array_key_exists((string)($post['hub_template'] ?? ''), $templateChoices) ? (string)$post['hub_template'] : 'general-it',
            'hub_feature_card_interval' => array_key_exists('hub_feature_card_interval', $post)
                ? $this->normalizeNumber((int)($post['hub_feature_card_interval'] ?? 0), 0, 12, 0)
                : 0,
            'hub_feature_cards_json' => array_key_exists('hub_feature_cards_json', $post)
                ? $this->normalizeFeatureCardsJson((string)$post['hub_feature_cards_json'])
                : '[]',
            'hub_badge' => mb_substr(trim(strip_tags((string)($post['hub_badge'] ?? ''))), 0, 80),
            'hub_badge_en' => mb_substr(trim(strip_tags((string)($post['hub_badge_en'] ?? ''))), 0, 80),
            'hub_hero_title' => mb_substr(trim(strip_tags((string)($post['hub_hero_title'] ?? ''))), 0, 160),
            'hub_hero_title_en' => mb_substr(trim(strip_tags((string)($post['hub_hero_title_en'] ?? ''))), 0, 160),
            'hub_hero_text' => $this->sanitizeRichText((string)($post['hub_hero_text'] ?? ''), 4000),
            'hub_hero_text_en' => $this->sanitizeRichText((string)($post['hub_hero_text_en'] ?? ''), 4000),
            'hub_cta_label' => mb_substr(trim(strip_tags((string)($post['hub_cta_label'] ?? ''))), 0, 60),
            'hub_cta_label_en' => mb_substr(trim(strip_tags((string)($post['hub_cta_label_en'] ?? ''))), 0, 60),
            'hub_cta_url' => filter_var((string)($post['hub_cta_url'] ?? ''), FILTER_SANITIZE_URL),
            'hub_meta_audience' => mb_substr(trim(strip_tags((string)($post['hub_meta_audience'] ?? ''))), 0, 120),
            'hub_meta_audience_en' => mb_substr(trim(strip_tags((string)($post['hub_meta_audience_en'] ?? ''))), 0, 120),
            'hub_meta_owner' => mb_substr(trim(strip_tags((string)($post['hub_meta_owner'] ?? ''))), 0, 120),
            'hub_meta_owner_en' => mb_substr(trim(strip_tags((string)($post['hub_meta_owner_en'] ?? ''))), 0, 120),
            'hub_meta_update_cycle' => mb_substr(trim(strip_tags((string)($post['hub_meta_update_cycle'] ?? ''))), 0, 120),
            'hub_meta_update_cycle_en' => mb_substr(trim(strip_tags((string)($post['hub_meta_update_cycle_en'] ?? ''))), 0, 120),
            'hub_meta_focus' => mb_substr(trim(strip_tags((string)($post['hub_meta_focus'] ?? ''))), 0, 160),
            'hub_meta_focus_en' => mb_substr(trim(strip_tags((string)($post['hub_meta_focus_en'] ?? ''))), 0, 160),
            'hub_meta_kpi' => mb_substr(trim(strip_tags((string)($post['hub_meta_kpi'] ?? ''))), 0, 120),
            'hub_meta_kpi_en' => mb_substr(trim(strip_tags((string)($post['hub_meta_kpi_en'] ?? ''))), 0, 120),
            'hub_links_json' => array_key_exists('hub_links_json', $post)
                ? $this->normalizeJsonArray((string)$post['hub_links_json'], 'link')
                : '[]',
            'hub_sections_json' => array_key_exists('hub_sections_json', $post)
                ? $this->normalizeJsonArray((string)$post['hub_sections_json'], 'section')
                : '[]',
            'hub_card_layout' => array_key_exists('hub_card_layout', $post)
                ? $this->normalizeSetting((string)$post['hub_card_layout'], ['standard', 'feature', 'compact'], 'standard')
                : '',
            'hub_card_image_position' => array_key_exists('hub_card_image_position', $post)
                ? $this->normalizeSetting((string)$post['hub_card_image_position'], ['top', 'left', 'right'], 'top')
                : '',
            'hub_card_image_fit' => array_key_exists('hub_card_image_fit', $post)
                ? $this->normalizeSetting((string)$post['hub_card_image_fit'], ['cover', 'contain'], 'cover')
                : '',
            'hub_card_image_ratio' => array_key_exists('hub_card_image_ratio', $post)
                ? $this->normalizeSetting((string)$post['hub_card_image_ratio'], ['wide', 'square', 'portrait'], 'wide')
                : '',
            'hub_card_meta_layout' => array_key_exists('hub_card_meta_layout', $post)
                ? $this->normalizeSetting((string)$post['hub_card_meta_layout'], ['split', 'stacked'], 'split')
                : '',
        ];

        $filteredSettings = Hooks::applyFilters('cms_prepare_hub_settings_payload', $settings, $post, $id);
        if (is_array($filteredSettings)) {
            $settings = array_merge($settings, $filteredSettings);
        }

        $normalizedCards = [];
        foreach ($cards as $card) {
            if (!is_array($card)) {
                continue;
            }

            $title = mb_substr(trim(strip_tags((string)($card['title'] ?? ''))), 0, 160);
            $url = filter_var((string)($card['url'] ?? ''), FILTER_SANITIZE_URL);
            if ($title === '') {
                continue;
            }

            $normalizedCards[] = [
                'is_feature' => $this->normalizeBoolean($card['is_feature'] ?? false),
                'feature_spacing_top' => $this->normalizeNumber((int)($card['feature_spacing_top'] ?? 0), 0, 240, 0),
                'title' => $title,
                'title_en' => mb_substr(trim(strip_tags((string)($card['title_en'] ?? ''))), 0, 160),
                'url' => $url !== '' ? $url : '#',
                'summary' => mb_substr(trim((string)($card['summary'] ?? '')), 0, 4000),
                'summary_en' => mb_substr(trim((string)($card['summary_en'] ?? '')), 0, 4000),
                'badge' => mb_substr(trim(strip_tags((string)($card['badge'] ?? ''))), 0, 80),
                'badge_en' => mb_substr(trim(strip_tags((string)($card['badge_en'] ?? ''))), 0, 80),
                'meta' => mb_substr(trim(strip_tags((string)($card['meta'] ?? ''))), 0, 120),
                'meta_en' => mb_substr(trim(strip_tags((string)($card['meta_en'] ?? ''))), 0, 120),
                'meta_left' => mb_substr(trim(strip_tags((string)($card['meta_left'] ?? ''))), 0, 120),
                'meta_left_en' => mb_substr(trim(strip_tags((string)($card['meta_left_en'] ?? ''))), 0, 120),
                'meta_right' => mb_substr(trim(strip_tags((string)($card['meta_right'] ?? ''))), 0, 120),
                'meta_right_en' => mb_substr(trim(strip_tags((string)($card['meta_right_en'] ?? ''))), 0, 120),
                'image_url' => mb_substr(trim((string)($card['image_url'] ?? '')), 0, 500),
                'image_alt' => mb_substr(trim(strip_tags((string)($card['image_alt'] ?? ''))), 0, 160),
                'image_alt_en' => mb_substr(trim(strip_tags((string)($card['image_alt_en'] ?? ''))), 0, 160),
                'button_text' => mb_substr(trim(strip_tags((string)($card['button_text'] ?? ''))), 0, 80),
                'button_text_en' => mb_substr(trim(strip_tags((string)($card['button_text_en'] ?? ''))), 0, 80),
                'button_link' => mb_substr(trim((string)($card['button_link'] ?? '')), 0, 500),
            ];
        }

        $filteredCards = Hooks::applyFilters('cms_prepare_hub_cards_payload', $normalizedCards, $post, $id);
        if (is_array($filteredCards)) {
            $normalizedCards = $filteredCards;
        }

        try {
            if ($id > 0) {
                $params = [
                    $name,
                    $description,
                    json_encode($normalizedCards, JSON_UNESCAPED_UNICODE),
                    json_encode($settings, JSON_UNESCAPED_UNICODE),
                ];

                $sql = "UPDATE {$this->prefix}site_tables
                        SET table_name = ?, description = ?, columns_json = '[]', rows_json = ?, settings_json = ?";

                if ($this->hasTableSlugColumn()) {
                    $sql .= ', table_slug = ?';
                    $params[] = $slug;
                }

                $sql .= ', updated_at = NOW() WHERE id = ?';
                $params[] = $id;

                $this->db->execute($sql, $params);

                Hooks::doAction('cms_after_hub_save', $id, $settings, $normalizedCards, $post);

                return ['success' => true, 'id' => $id, 'slug' => $slug, 'message' => 'Routing / Hub Site aktualisiert.'];
            }

            $columns = ['table_name', 'description', 'columns_json', 'rows_json', 'settings_json'];
            $placeholders = ['?', '?', "'[]'", '?', '?'];
            $params = [
                $name,
                $description,
                json_encode($normalizedCards, JSON_UNESCAPED_UNICODE),
                json_encode($settings, JSON_UNESCAPED_UNICODE),
            ];

            if ($this->hasTableSlugColumn()) {
                $columns[] = 'table_slug';
                $placeholders[] = '?';
                $params[] = $slug;
            }

            $columns[] = 'created_at';
            $columns[] = 'updated_at';
            $placeholders[] = 'NOW()';
            $placeholders[] = 'NOW()';

            $this->db->execute(
                "INSERT INTO {$this->prefix}site_tables (" . implode(', ', $columns) . ")
                 VALUES (" . implode(', ', $placeholders) . ")",
                $params
            );

            Hooks::doAction('cms_after_hub_save', (int)$this->db->insert_id(), $settings, $normalizedCards, $post);

            return ['success' => true, 'id' => (int)$this->db->insert_id(), 'slug' => $slug, 'message' => 'Routing / Hub Site erstellt.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Speichern: ' . $e->getMessage()];
        }
    }

    public function delete(int $id): array
    {
        try {
            $this->db->execute("DELETE FROM {$this->prefix}site_tables WHERE id = ?", [$id]);
            return ['success' => true, 'message' => 'Routing / Hub Site gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Löschen.'];
        }
    }

    public function duplicate(int $id): array
    {
        $source = $this->db->get_row(
            "SELECT * FROM {$this->prefix}site_tables WHERE id = ? LIMIT 1",
            [$id]
        );

        if (!$source) {
            return ['success' => false, 'error' => 'Routing / Hub Site nicht gefunden.'];
        }

        $data = (array)$source;

        try {
            $copyName = ((string)($data['table_name'] ?? 'Routing / Hub Site')) . ' (Kopie)';
            $settings = \CMS\Json::decodeArray($data['settings_json'] ?? null, []);
            $settings = array_merge(self::DEFAULT_SETTINGS, $settings);
            $settings['hub_slug'] = $this->buildUniqueHubSlug($copyName, null);
            $settings['hub_domains'] = [];

            $columns = ['table_name', 'description', 'columns_json', 'rows_json', 'settings_json', 'created_at', 'updated_at'];
            $placeholders = ['?', '?', '?', '?', '?', 'NOW()', 'NOW()'];
            $params = [
                $copyName,
                (string)($data['description'] ?? ''),
                (string)($data['columns_json'] ?? '[]'),
                (string)($data['rows_json'] ?? '[]'),
                json_encode($settings, JSON_UNESCAPED_UNICODE),
            ];

            if ($this->hasTableSlugColumn()) {
                $columns[] = 'table_slug';
                $placeholders[] = '?';
                $params[] = (string)$settings['hub_slug'];
            }

            $this->db->execute(
                "INSERT INTO {$this->prefix}site_tables (" . implode(', ', $columns) . ")
                 VALUES (" . implode(', ', $placeholders) . ")",
                $params
            );

            return ['success' => true, 'id' => (int)$this->db->insert_id(), 'slug' => (string)$settings['hub_slug'], 'message' => 'Routing / Hub Site dupliziert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Duplizieren.'];
        }
    }

    public function saveTemplate(array $post): array
    {
        return $this->templateProfileManager->saveTemplate($post);
    }

    public function duplicateTemplate(string $key): array
    {
        return $this->templateProfileManager->duplicateTemplate($key);
    }

    public function deleteTemplate(string $key): array
    {
        return $this->templateProfileManager->deleteTemplate($key);
    }

    private function buildUniqueHubSlug(string $title, ?int $excludeId = null): string
    {
        $baseSlug = $this->sanitizeSlug($title);
        if ($baseSlug === '' || $this->isReservedSlug($baseSlug)) {
            $baseSlug = 'hub-site';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($this->hubSlugExists($slug, $excludeId) || $this->pageSlugExists($slug)) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function hubSlugExists(string $slug, ?int $excludeId = null): bool
    {
                $slugSql = $this->hasTableSlugColumn()
                        ? "(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.hub_slug')) = ? OR table_slug = ?)"
                        : "JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.hub_slug')) = ?";

                $sql = "SELECT id
                                FROM {$this->prefix}site_tables
                                WHERE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.content_mode')), 'table') = 'hub'
                                    AND {$slugSql}";
                $params = $this->hasTableSlugColumn() ? [$slug, $slug] : [$slug];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        return $this->db->get_var($sql . ' LIMIT 1', $params) !== null;
    }

    private function pageSlugExists(string $slug): bool
    {
        return $this->db->get_var(
            "SELECT id FROM {$this->prefix}pages WHERE slug = ? LIMIT 1",
            [$slug]
        ) !== null || $this->isReservedSlug($slug);
    }

    private function isReservedSlug(string $slug): bool
    {
        static $reserved = [
            'admin', 'api', 'login', 'logout', 'register', 'member', 'dashboard', 'order',
            'search', 'blog', 'sitemap.xml', 'robots.txt', 'cookie-einstellungen', 'site-table',
        ];

        return in_array($slug, $reserved, true);
    }

    private function sanitizeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = (string)preg_replace('/[^a-z0-9]+/i', '-', $value);
        return trim($value, '-');
    }

    private function normalizeJsonArray(string $json, string $mode): string
    {
        $items = \CMS\Json::decodeArray($json, []);
        if (!is_array($items)) {
            return '[]';
        }

        $normalized = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            if ($mode === 'link') {
                $label = mb_substr(trim(strip_tags((string)($item['label'] ?? ''))), 0, 80);
                $url = mb_substr(trim((string)($item['url'] ?? '')), 0, 240);
                if ($label === '') {
                    continue;
                }
                $normalized[] = [
                    'label' => $label,
                    'url' => $url !== '' ? $url : '#',
                ];
                continue;
            }

            $title = mb_substr(trim(strip_tags((string)($item['title'] ?? ''))), 0, 120);
            $text = mb_substr(trim((string)($item['text'] ?? '')), 0, 600);
            $actionLabel = mb_substr(trim(strip_tags((string)($item['actionLabel'] ?? ''))), 0, 80);
            $actionUrl = mb_substr(trim((string)($item['actionUrl'] ?? '')), 0, 240);

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

    private function normalizeNumber(int $value, int $min, int $max, int $fallback): int
    {
        if ($value < $min || $value > $max) {
            return $fallback;
        }

        return $value;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    private function normalizeFeatureCardsJson(string $json): string
    {
        $items = \CMS\Json::decodeArray($json, []);
        if (!is_array($items)) {
            return '[]';
        }

        $normalized = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = mb_substr(trim(strip_tags((string)($item['title'] ?? ''))), 0, 160);
            $titleEn = mb_substr(trim(strip_tags((string)($item['title_en'] ?? ''))), 0, 160);
            $text = mb_substr(trim((string)($item['text'] ?? '')), 0, 4000);
            $textEn = mb_substr(trim((string)($item['text_en'] ?? '')), 0, 4000);
            $imageUrl = mb_substr(trim((string)($item['image_url'] ?? '')), 0, 500);
            $imageAlt = mb_substr(trim(strip_tags((string)($item['image_alt'] ?? ''))), 0, 160);
            $imageAltEn = mb_substr(trim(strip_tags((string)($item['image_alt_en'] ?? ''))), 0, 160);
            $insertAfter = $this->normalizeNumber((int)($item['insert_after'] ?? 0), 0, 999, 0);
            $featureSpacingTop = $this->normalizeNumber((int)($item['feature_spacing_top'] ?? 0), 0, 240, 0);

            if ($title === '' && $titleEn === '' && $text === '' && $textEn === '' && $imageUrl === '') {
                continue;
            }

            $normalized[] = [
                'insert_after' => $insertAfter,
                'feature_spacing_top' => $featureSpacingTop,
                'title' => $title,
                'title_en' => $titleEn,
                'text' => $text,
                'text_en' => $textEn,
                'image_url' => $imageUrl,
                'image_alt' => $imageAlt,
                'image_alt_en' => $imageAltEn,
            ];
        }

        return json_encode($normalized, JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    private function sanitizeRichText(string $value, int $maxLength): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $sanitized = \CMS\Services\PurifierService::getInstance()->purify($value, 'table');

        return mb_substr(trim($sanitized), 0, $maxLength);
    }

    private function getExistingHubSettings(int $id): array
    {
        $row = $this->db->get_row("SELECT settings_json FROM {$this->prefix}site_tables WHERE id = ? LIMIT 1", [$id]);
        if (!$row || empty($row->settings_json)) {
            return self::DEFAULT_SETTINGS;
        }

        $decoded = \CMS\Json::decodeArray($row->settings_json ?? null, []);
        return array_merge(self::DEFAULT_SETTINGS, is_array($decoded) ? $decoded : []);
    }

    /**
     * @return array{domains: array<int, string>, errors: array<int, string>}
     */
    private function normalizeHubDomains(string $rawDomains): array
    {
        $entries = preg_split('/[\r\n,;]+/', $rawDomains) ?: [];
        $domains = [];
        $errors = [];

        foreach ($entries as $entry) {
            $normalizedHost = $this->normalizeDomainHost($entry);
            if ($normalizedHost === '') {
                if (trim($entry) !== '') {
                    $errors[] = 'Die Zusatzdomain „' . trim($entry) . '“ ist ungültig.';
                }
                continue;
            }

            if ($this->isMainDomainHost($normalizedHost)) {
                $errors[] = 'Die Hauptdomain darf nicht als Hub-Zusatzdomain verwendet werden.';
                continue;
            }

            $domains[] = $normalizedHost;
        }

        return [
            'domains' => array_values(array_unique($domains)),
            'errors' => array_values(array_unique($errors)),
        ];
    }

    private function hubDomainExists(string $domain, ?int $excludeId = null): bool
    {
        $rows = $this->db->get_results(
            "SELECT id, settings_json
             FROM {$this->prefix}site_tables
             WHERE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.content_mode')), 'table') = 'hub'",
            []
        ) ?: [];

        foreach ($rows as $row) {
            $rowId = (int)($row->id ?? 0);
            if ($excludeId !== null && $rowId === $excludeId) {
                continue;
            }

            $settings = \CMS\Json::decodeArray($row->settings_json ?? null, []);
            $domains = is_array($settings['hub_domains'] ?? null) ? $settings['hub_domains'] : [];
            foreach ($domains as $candidate) {
                if ($this->normalizeDomainHost((string)$candidate) === $domain) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isMainDomainHost(string $host): bool
    {
        $siteHost = $this->normalizeDomainHost((string)(parse_url((string)SITE_URL, PHP_URL_HOST) ?? ''));
        return $siteHost !== '' && $siteHost === $host;
    }

    private function normalizeDomainHost(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $candidate = preg_match('#^https?://#i', $value) === 1 ? $value : 'https://' . ltrim($value, '/');
        $parts = parse_url($candidate);
        if ($parts === false) {
            return '';
        }

        $host = strtolower(trim((string)($parts['host'] ?? ''), '.'));
        if ($host === '') {
            return '';
        }

        if (isset($parts['path']) && $parts['path'] !== '' && $parts['path'] !== '/') {
            return '';
        }

        if (isset($parts['query']) || isset($parts['fragment'])) {
            return '';
        }

        if (!preg_match('/^(?=.{1,253}$)(?:xn--)?[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.(?:xn--)?[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/i', $host)) {
            return '';
        }

        return $host;
    }

    private function hasTableSlugColumn(): bool
    {
        if ($this->hasTableSlugColumn !== null) {
            return $this->hasTableSlugColumn;
        }

        try {
            $column = $this->db->get_var("SHOW COLUMNS FROM {$this->prefix}site_tables LIKE 'table_slug'");
            $this->hasTableSlugColumn = $column !== null;
        } catch (\Throwable) {
            $this->hasTableSlugColumn = false;
        }

        return $this->hasTableSlugColumn;
    }
}
