<?php
declare(strict_types=1);

namespace CMS\Services\Landing;

use CMS\Json;

if (!defined('ABSPATH')) {
    exit;
}

final class LandingSectionProfileService
{
    private const ALLOWED_CONTENT_TYPES = ['features', 'text', 'posts'];
    private const ALLOWED_ICON_LAYOUTS = ['top', 'left'];
    private const ALLOWED_SHADOWS = ['none', 'sm', 'md', 'lg'];
    private const ALLOWED_COLUMNS = ['auto', '2', '3', '4'];
    private const ALLOWED_PADDINGS = ['sm', 'md', 'lg', 'xl'];
    private const ALLOWED_BORDER_WIDTHS = ['0', '1px', '2px', '3px'];

    public function __construct(
        private readonly LandingRepository $repository,
        private readonly LandingSanitizer $sanitizer,
        private readonly LandingDefaultsProvider $defaults,
    ) {
    }

    public function ensureDefaultSections(): void
    {
        $this->repository->ensureSingleSectionRecord('content', $this->defaults->getDefaultContentSettings(), 50);
        $this->repository->ensureSingleSectionRecord('footer', $this->defaults->getDefaultFooter(), 99);
        $this->repository->ensureSingleSectionRecord('design', $this->defaults->getDefaultDesign(), 90);
        $this->repository->ensureSingleSectionRecord('settings', $this->defaults->getDefaultSettings(), 100);
        $this->upgradeLegacyFooterDefaults();
    }

    /**
     * @return array<string, mixed>
     */
    public function getFooter(): array
    {
        try {
            $result = $this->repository->getSection('footer');
            if ($result === null) {
                return $this->defaults->getDefaultFooter();
            }

            $data = Json::decodeArray($result['data'] ?? null, []);
            $data = is_array($data) ? $data : [];

            return [
                'id' => (int)($result['id'] ?? 0),
                'content' => $data['content'] ?? '',
                'button_text' => $data['button_text'] ?? '',
                'button_url' => $data['button_url'] ?? '',
                'copyright' => $data['copyright'] ?? '&copy; ' . date('Y') . ' IT Expert Network',
                'show_footer' => $data['show_footer'] ?? true,
            ];
        } catch (\Throwable $e) {
            error_log('LandingSectionProfileService::getFooter() Error: ' . $e->getMessage());
            return $this->defaults->getDefaultFooter();
        }
    }

    public function updateFooter(array $data): bool
    {
        $payload = [
            'content' => $this->sanitizer->normalizeHtml($data['footer_content'] ?? ''),
            'button_text' => $this->sanitizer->sanitizePlainText((string)($data['footer_button_text'] ?? ''), 60),
            'button_url' => $this->sanitizer->sanitizeUrl((string)($data['footer_button_url'] ?? '')),
            'copyright' => $this->sanitizer->sanitizeCopyright((string)($data['footer_copyright'] ?? '')),
            'show_footer' => isset($data['show_footer']),
        ];

        try {
            return $this->repository->upsertSection('footer', $payload, 99);
        } catch (\Throwable $e) {
            error_log('LandingSectionProfileService::updateFooter() Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getContentSettings(): array
    {
        try {
            $result = $this->repository->getSection('content');
            if ($result === null) {
                return $this->defaults->getDefaultContentSettings();
            }

            $data = Json::decodeArray($result['data'] ?? null, []);
            $data = is_array($data) ? $data : [];

            return [
                'id' => (int)($result['id'] ?? 0),
                'content_type' => $data['content_type'] ?? 'features',
                'content_text' => $data['content_text'] ?? '',
                'posts_count' => max(1, (int)($data['posts_count'] ?? 5)),
            ];
        } catch (\Throwable $e) {
            error_log('LandingSectionProfileService::getContentSettings() Error: ' . $e->getMessage());
            return $this->defaults->getDefaultContentSettings();
        }
    }

    public function updateContentSettings(array $data): bool
    {
        $payload = [
            'content_type' => $this->sanitizer->sanitizeEnum((string)($data['content_type'] ?? 'features'), self::ALLOWED_CONTENT_TYPES, 'features'),
            'content_text' => $this->sanitizer->normalizeHtml($data['content_text'] ?? ''),
            'posts_count' => max(1, min(50, (int)($data['posts_count'] ?? 5))),
        ];

        try {
            return $this->repository->upsertSection('content', $payload, 50);
        } catch (\Throwable $e) {
            error_log('LandingSectionProfileService::updateContentSettings() Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        try {
            $result = $this->repository->getSection('settings');
            if ($result === null) {
                return $this->defaults->getDefaultSettings();
            }

            $data = Json::decodeArray($result['data'] ?? null, []);
            $data = is_array($data) ? $data : [];

            return array_merge($this->defaults->getDefaultSettings(), $data, ['id' => (int)($result['id'] ?? 0)]);
        } catch (\Throwable $e) {
            error_log('LandingSectionProfileService::getSettings() Error: ' . $e->getMessage());
            return $this->defaults->getDefaultSettings();
        }
    }

    public function updateSettings(array $data): bool
    {
        $payload = [
            'show_header' => isset($data['show_header']),
            'show_content' => isset($data['show_content']),
            'show_footer_section' => isset($data['show_footer_section']),
            'landing_slug' => $this->sanitizer->sanitizeLandingSlug((string)($data['landing_slug'] ?? '')),
            'maintenance_mode' => isset($data['maintenance_mode']),
        ];

        try {
            return $this->repository->upsertSection('settings', $payload, 100);
        } catch (\Throwable $e) {
            error_log('LandingSectionProfileService::updateSettings() Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getDesign(): array
    {
        try {
            $result = $this->repository->getSection('design');
            if ($result === null) {
                return $this->defaults->getDefaultDesign();
            }

            $data = Json::decodeArray($result['data'] ?? null, []);
            $data = is_array($data) ? $data : [];
            $merged = array_merge($this->defaults->getDefaultDesign(), $data);
            $merged['id'] = (int)($result['id'] ?? 0);
            return $merged;
        } catch (\Throwable $e) {
            error_log('LandingSectionProfileService::getDesign() Error: ' . $e->getMessage());
            return $this->defaults->getDefaultDesign();
        }
    }

    public function updateDesign(array $data): bool
    {
        $defaults = $this->defaults->getDefaultDesign();
        $existing = $this->getDesign();
        $allowedKeys = array_keys($defaults);

        $designData = [];
        foreach ($allowedKeys as $key) {
            if ($key === 'id') {
                continue;
            }

            $designData[$key] = array_key_exists($key, $data)
                ? $data[$key]
                : ($existing[$key] ?? $defaults[$key]);
        }

        $designData['card_border_radius'] = max(0, min(48, (int)($designData['card_border_radius'] ?? 12)));
        $designData['button_border_radius'] = max(0, min(50, (int)($designData['button_border_radius'] ?? 8)));
        $designData['card_icon_layout'] = $this->sanitizer->sanitizeEnum((string)($designData['card_icon_layout'] ?? 'top'), self::ALLOWED_ICON_LAYOUTS, 'top');
        $designData['card_shadow'] = $this->sanitizer->sanitizeEnum((string)($designData['card_shadow'] ?? 'sm'), self::ALLOWED_SHADOWS, 'sm');
        $designData['feature_columns'] = $this->sanitizer->sanitizeEnum((string)($designData['feature_columns'] ?? 'auto'), self::ALLOWED_COLUMNS, 'auto');
        $designData['hero_padding'] = $this->sanitizer->sanitizeEnum((string)($designData['hero_padding'] ?? 'md'), self::ALLOWED_PADDINGS, 'md');
        $designData['feature_padding'] = $this->sanitizer->sanitizeEnum((string)($designData['feature_padding'] ?? 'md'), self::ALLOWED_PADDINGS, 'md');
        $designData['card_border_width'] = $this->sanitizer->sanitizeEnum((string)($designData['card_border_width'] ?? '1px'), self::ALLOWED_BORDER_WIDTHS, '1px');
        $designData['card_border_color'] = $this->sanitizer->sanitizeColor((string)($designData['card_border_color'] ?? '#e2e8f0'), '#e2e8f0');
        $designData['footer_bg'] = $this->sanitizer->sanitizeColor((string)($designData['footer_bg'] ?? '#1e293b'), '#1e293b');
        $designData['footer_text_color'] = $this->sanitizer->sanitizeColor((string)($designData['footer_text_color'] ?? '#94a3b8'), '#94a3b8');
        $designData['content_section_bg'] = $this->sanitizer->sanitizeColor((string)($designData['content_section_bg'] ?? '#ffffff'), '#ffffff');

        try {
            return $this->repository->upsertSection('design', $designData, 90);
        } catch (\Throwable $e) {
            error_log('LandingSectionProfileService::updateDesign() Error: ' . $e->getMessage());
            return false;
        }
    }

    public function upgradeLegacyFooterDefaults(): void
    {
        $footer = $this->getFooter();
        $legacyContent = '<p>Kontaktieren Sie uns für weitere Informationen.</p>';
        $currentContent = trim((string)($footer['content'] ?? ''));

        if ($currentContent !== $legacyContent) {
            return;
        }

        $defaultFooter = $this->defaults->getDefaultFooter();
        $this->updateFooter([
            'footer_content' => $defaultFooter['content'],
            'footer_button_text' => $defaultFooter['button_text'],
            'footer_button_url' => $defaultFooter['button_url'],
            'footer_copyright' => $defaultFooter['copyright'],
            'show_footer' => true,
        ]);
    }
}
