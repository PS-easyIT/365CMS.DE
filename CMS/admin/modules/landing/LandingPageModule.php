<?php
declare(strict_types=1);

/**
 * Landing Page Module – Wrapper um LandingPageService
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Logger;
use CMS\Services\Landing\LandingSanitizer;
use CMS\Services\LandingPageService;

class LandingPageModule
{
    private const VALID_TABS = ['header', 'content', 'footer', 'design', 'plugins'];
    private const MAX_SORT_ORDER = 999;

    private LandingPageService $service;
    private LandingSanitizer $sanitizer;
    private Logger $logger;
    private bool $defaultsEnsured = false;

    public function __construct()
    {
        $this->service = LandingPageService::getInstance();
        $this->sanitizer = new LandingSanitizer();
        $this->logger = Logger::instance()->withChannel('admin.landing-page');
    }

    /**
     * Daten für den aktuellen Tab laden
     */
    public function getData(string $tab): array
    {
        if (!$this->canAccess()) {
            return [];
        }

        $this->ensureDefaultsLoaded();
        $tab = $this->normalizeTab($tab);

        return match ($tab) {
            'header'  => ['header'   => $this->service->getHeader()],
            'content' => [
                'content'  => $this->service->getContentSettings(),
                'contentTypeOptions' => $this->buildContentTypeOptions((string)($this->service->getContentSettings()['content_type'] ?? 'features')),
                'featureCards' => $this->buildFeatureCards($this->service->getFeatures()),
            ],
            'footer'  => ['footer'   => $this->service->getFooter()],
            'design'  => [
                'design'   => $this->service->getDesign(),
                'colors'   => $this->service->getColors(),
            ],
            'plugins' => [
                'pluginCards' => $this->buildPluginCards(
                    $this->service->getRegisteredPlugins(),
                    $this->service->getPluginOverrides()
                ),
            ],
            default   => [],
        };
    }

    public function saveHeader(array $post): array
    {
        if (!$this->canAccess()) {
            return $this->accessDeniedResult();
        }

        try {
            $this->ensureDefaultsLoaded();
            $this->service->updateHeader($this->sanitizeHeaderPayload($post));
            return ['success' => true, 'message' => 'Header gespeichert.'];
        } catch (\Throwable $e) {
            return $this->failResult('Header konnte nicht gespeichert werden.', 'save_header', $e);
        }
    }

    public function saveContent(array $post): array
    {
        if (!$this->canAccess()) {
            return $this->accessDeniedResult();
        }

        try {
            $this->ensureDefaultsLoaded();
            $this->service->updateContentSettings($this->sanitizeContentPayload($post));
            return ['success' => true, 'message' => 'Content-Einstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return $this->failResult('Content-Einstellungen konnten nicht gespeichert werden.', 'save_content', $e);
        }
    }

    public function saveFooter(array $post): array
    {
        if (!$this->canAccess()) {
            return $this->accessDeniedResult();
        }

        try {
            $this->ensureDefaultsLoaded();
            $this->service->updateFooter($this->sanitizeFooterPayload($post));
            return ['success' => true, 'message' => 'Footer gespeichert.'];
        } catch (\Throwable $e) {
            return $this->failResult('Footer konnte nicht gespeichert werden.', 'save_footer', $e);
        }
    }

    public function saveDesign(array $post): array
    {
        if (!$this->canAccess()) {
            return $this->accessDeniedResult();
        }

        try {
            $this->ensureDefaultsLoaded();
            $payload = $this->sanitizeDesignPayload($post);
            $colorsSaved = $this->service->updateColors($payload);
            $designSaved = $this->service->updateDesign($payload);

            if (!$colorsSaved || !$designSaved) {
                return ['success' => false, 'error' => 'Design konnte nicht vollständig gespeichert werden.'];
            }

            return ['success' => true, 'message' => 'Design gespeichert.'];
        } catch (\Throwable $e) {
            return $this->failResult('Design konnte nicht gespeichert werden.', 'save_design', $e);
        }
    }

    public function saveFeature(array $post): array
    {
        if (!$this->canAccess()) {
            return $this->accessDeniedResult();
        }

        try {
            $this->ensureDefaultsLoaded();
            $id = !empty($post['feature_id']) ? (int)$post['feature_id'] : null;
            $payload = $this->sanitizeFeaturePayload($post);
            if ($payload['title'] === '') {
                return ['success' => false, 'error' => 'Feature-Titel fehlt.'];
            }

            $this->service->saveFeature($id, $payload);
            return ['success' => true, 'message' => 'Feature gespeichert.'];
        } catch (\Throwable $e) {
            return $this->failResult('Feature konnte nicht gespeichert werden.', 'save_feature', $e);
        }
    }

    public function deleteFeature(int $id): array
    {
        if (!$this->canAccess()) {
            return $this->accessDeniedResult();
        }

        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige Feature-ID.'];
        }

        try {
            $this->ensureDefaultsLoaded();
            $this->service->deleteFeature($id);
            return ['success' => true, 'message' => 'Feature gelöscht.'];
        } catch (\Throwable $e) {
            return $this->failResult('Feature konnte nicht gelöscht werden.', 'delete_feature', $e);
        }
    }

    public function savePlugin(array $post): array
    {
        if (!$this->canAccess()) {
            return $this->accessDeniedResult();
        }

        try {
            $this->ensureDefaultsLoaded();
            $payload = $this->sanitizePluginPayload($post);
            $pluginId = $payload['plugin_id'];
            if (empty($pluginId)) {
                return ['success' => false, 'error' => 'Plugin-ID fehlt.'];
            }

            $this->service->savePluginSettings($pluginId, $payload);
            return ['success' => true, 'message' => 'Plugin-Einstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return $this->failResult('Plugin-Einstellungen konnten nicht gespeichert werden.', 'save_plugin', $e);
        }
    }

    private function canAccess(): bool
    {
        return Auth::instance()->isAdmin() && Auth::instance()->hasCapability('manage_settings');
    }

    private function ensureDefaultsLoaded(): void
    {
        if ($this->defaultsEnsured) {
            return;
        }

        $this->service->ensureDefaults();
        $this->defaultsEnsured = true;
    }

    private function normalizeTab(string $tab): string
    {
        return in_array($tab, self::VALID_TABS, true) ? $tab : 'header';
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function sanitizeHeaderPayload(array $post): array
    {
        return [
            'title' => $this->sanitizer->sanitizePlainText((string)($post['title'] ?? ''), 150),
            'subtitle' => $this->sanitizer->sanitizePlainText((string)($post['subtitle'] ?? ''), 200),
            'badge_text' => $this->sanitizer->sanitizePlainText((string)($post['badge_text'] ?? ''), 60),
            'description' => $this->sanitizer->normalizeHtml($post['description'] ?? ''),
            'cta_text' => $this->sanitizer->sanitizePlainText((string)($post['cta_text'] ?? ''), 60),
            'cta_url' => $this->sanitizer->sanitizeUrl((string)($post['cta_url'] ?? '')),
            'bg_image' => $this->sanitizeUrlOrAssetPath((string)($post['bg_image'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function sanitizeContentPayload(array $post): array
    {
        return [
            'content_type' => $this->sanitizer->sanitizeEnum((string)($post['content_type'] ?? 'features'), ['features', 'text', 'posts'], 'features'),
            'content_text' => $this->sanitizer->normalizeHtml($post['content_text'] ?? ''),
            'posts_count' => $this->clampInt($post['posts_count'] ?? 5, 1, 50),
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function sanitizeFooterPayload(array $post): array
    {
        return [
            'show_footer' => !empty($post['show_footer']),
            'footer_content' => $this->sanitizer->normalizeHtml($post['footer_content'] ?? ''),
            'footer_button_text' => $this->sanitizer->sanitizePlainText((string)($post['footer_button_text'] ?? ''), 60),
            'footer_button_url' => $this->sanitizer->sanitizeUrl((string)($post['footer_button_url'] ?? '')),
            'footer_copyright' => $this->sanitizer->sanitizeCopyright((string)($post['footer_copyright'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function sanitizeDesignPayload(array $post): array
    {
        return [
            'hero_gradient_start' => $this->sanitizer->sanitizeColor((string)($post['hero_gradient_start'] ?? ''), '#1e293b'),
            'hero_gradient_end' => $this->sanitizer->sanitizeColor((string)($post['hero_gradient_end'] ?? ''), '#0f172a'),
            'hero_border' => $this->sanitizer->sanitizeColor((string)($post['hero_border'] ?? ''), '#3b82f6'),
            'hero_text' => $this->sanitizer->sanitizeColor((string)($post['hero_text'] ?? ''), '#ffffff'),
            'features_bg' => $this->sanitizer->sanitizeColor((string)($post['features_bg'] ?? ''), '#f8fafc'),
            'feature_card_bg' => $this->sanitizer->sanitizeColor((string)($post['feature_card_bg'] ?? ''), '#ffffff'),
            'feature_card_hover' => $this->sanitizer->sanitizeColor((string)($post['feature_card_hover'] ?? ''), '#3b82f6'),
            'primary_button' => $this->sanitizer->sanitizeColor((string)($post['primary_button'] ?? ''), '#3b82f6'),
            'card_icon_layout' => $this->sanitizer->sanitizeEnum((string)($post['card_icon_layout'] ?? 'top'), ['top', 'left'], 'top'),
            'feature_columns' => $this->sanitizer->sanitizeEnum((string)($post['feature_columns'] ?? 'auto'), ['auto', '2', '3', '4'], 'auto'),
            'card_border_radius' => $this->clampInt($post['card_border_radius'] ?? 18, 0, 48),
            'button_border_radius' => $this->clampInt($post['button_border_radius'] ?? 12, 0, 50),
            'card_border_width' => $this->sanitizer->sanitizeEnum((string)($post['card_border_width'] ?? '1px'), ['0', '1px', '2px', '3px'], '1px'),
            'card_shadow' => $this->sanitizer->sanitizeEnum((string)($post['card_shadow'] ?? 'md'), ['none', 'sm', 'md', 'lg'], 'md'),
            'card_border_color' => $this->sanitizer->sanitizeColor((string)($post['card_border_color'] ?? ''), '#e2e8f0'),
            'footer_bg' => $this->sanitizer->sanitizeColor((string)($post['footer_bg'] ?? ''), '#0f172a'),
            'footer_text_color' => $this->sanitizer->sanitizeColor((string)($post['footer_text_color'] ?? ''), '#cbd5e1'),
            'content_section_bg' => $this->sanitizer->sanitizeColor((string)($post['content_section_bg'] ?? ''), '#ffffff'),
            'hero_padding' => $this->sanitizer->sanitizeEnum((string)($post['hero_padding'] ?? 'md'), ['sm', 'md', 'lg', 'xl'], 'md'),
            'feature_padding' => $this->sanitizer->sanitizeEnum((string)($post['feature_padding'] ?? 'md'), ['sm', 'md', 'lg', 'xl'], 'md'),
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function sanitizeFeaturePayload(array $post): array
    {
        return [
            'title' => $this->sanitizer->sanitizePlainText((string)($post['title'] ?? ''), 120),
            'icon' => $this->sanitizeFeatureIcon((string)($post['icon'] ?? '')),
            'description' => $this->sanitizer->sanitizePlainText((string)($post['description'] ?? ''), 500),
            'sort_order' => $this->clampInt($post['sort_order'] ?? 0, 0, self::MAX_SORT_ORDER),
        ];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function sanitizePluginPayload(array $post): array
    {
        return [
            'plugin_id' => $this->sanitizer->sanitizePluginId((string)($post['plugin_id'] ?? '')),
            'enabled' => !empty($post['enabled']),
            'sort_order' => $this->clampInt($post['sort_order'] ?? 10, 0, self::MAX_SORT_ORDER),
        ];
    }

    private function sanitizeUrlOrAssetPath(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $url = $this->sanitizer->sanitizeUrl($value);
        if ($url !== '') {
            return $url;
        }

        return $this->sanitizer->sanitizeRelativeAssetPath($value);
    }

    /**
     * @param array<string, mixed> $content
     * @return array<int, array{value:string,label:string,selected:bool}>
     */
    private function buildContentTypeOptions(string $selectedType): array
    {
        $options = [
            'features' => 'Feature-Kacheln',
            'text' => 'Freitext-Bereich',
            'posts' => 'Aktuelle Beiträge',
        ];

        $normalizedSelectedType = $this->sanitizer->sanitizeEnum($selectedType, array_keys($options), 'features');

        $result = [];
        foreach ($options as $value => $label) {
            $result[] = [
                'value' => $value,
                'label' => $label,
                'selected' => $normalizedSelectedType === $value,
            ];
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $features
     * @return array<int, array<string, mixed>>
     */
    private function buildFeatureCards(array $features): array
    {
        $cards = [];

        foreach ($features as $feature) {
            $cards[] = [
                'id' => (int)($feature['id'] ?? 0),
                'icon' => (string)($feature['icon'] ?? '🧩'),
                'title' => (string)($feature['title'] ?? ''),
                'description' => (string)($feature['description'] ?? ''),
                'sort_order' => (int)($feature['sort_order'] ?? 0),
                'delete_disabled' => (int)($feature['id'] ?? 0) < 1,
            ];
        }

        return $cards;
    }

    /**
     * @param array<string, array<string, mixed>> $plugins
     * @param array<string, mixed> $overrides
     * @return array<int, array<string, mixed>>
     */
    private function buildPluginCards(array $plugins, array $overrides): array
    {
        $pluginSettings = is_array($overrides['plugin_settings'] ?? null) ? $overrides['plugin_settings'] : [];
        $cards = [];

        foreach ($plugins as $pluginId => $plugin) {
            $id = $this->sanitizer->sanitizePluginId((string)($plugin['id'] ?? $pluginId));
            if ($id === '') {
                continue;
            }

            $storedSettings = is_array($pluginSettings[$id] ?? null) ? $pluginSettings[$id] : [];
            $targetLabels = array_map(
                static fn (string $target): string => match ($target) {
                    'header' => 'Header',
                    'content' => 'Content',
                    'footer' => 'Footer',
                    default => ucfirst($target),
                },
                array_values(array_filter(array_map('strval', (array)($plugin['targets'] ?? []))))
            );

            $cards[] = [
                'id' => $id,
                'title' => (string)($plugin['name'] ?? $id),
                'description' => (string)($plugin['description'] ?? ''),
                'version' => (string)($plugin['version'] ?? ''),
                'author' => (string)($plugin['author'] ?? ''),
                'targets' => $targetLabels,
                'enabled' => !empty($storedSettings['enabled']),
                'sort_order' => $this->clampInt($storedSettings['sort_order'] ?? 10, 0, self::MAX_SORT_ORDER),
            ];
        }

        return $cards;
    }

    private function sanitizeFeatureIcon(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return preg_replace('/[^\p{L}\p{N}_\- ]/u', '', $value) ?? '';
    }

    private function clampInt(mixed $value, int $min, int $max): int
    {
        $intValue = (int)$value;

        if ($intValue < $min) {
            return $min;
        }

        if ($intValue > $max) {
            return $max;
        }

        return $intValue;
    }

    /**
     * @return array{success: false, error: string}
     */
    private function accessDeniedResult(): array
    {
        return ['success' => false, 'error' => 'Zugriff verweigert.'];
    }

    /**
     * @return array{success: false, error: string}
     */
    private function failResult(string $message, string $context, ?\Throwable $exception = null): array
    {
        $this->logger->error($message, [
            'context' => $context,
            'exception' => $exception?->getMessage(),
            'type' => $exception !== null ? $exception::class : null,
        ]);

        return ['success' => false, 'error' => $message];
    }
}
