<?php
declare(strict_types=1);

namespace CMS\Services\Landing;

use CMS\Json;
use CMS\Version;

if (!defined('ABSPATH')) {
    exit;
}

final class LandingHeaderService
{
    private const ALLOWED_LOGO_POSITIONS = ['top', 'left'];
    private const ALLOWED_HEADER_LAYOUTS = ['standard', 'compact'];

    public function __construct(
        private readonly LandingRepository $repository,
        private readonly LandingSanitizer $sanitizer,
        private readonly LandingDefaultsProvider $defaults,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getHeader(): array
    {
        try {
            $result = $this->repository->getSection('header');
            if ($result === null) {
                return $this->defaults->getDefaultHeader();
            }

            $data = Json::decodeArray($result['data'] ?? null, []);
            $data = is_array($data) ? $data : [];

            return [
                'id' => (int)($result['id'] ?? 0),
                'title' => $data['title'] ?? 'IT Expert Network CMS',
                'subtitle' => $data['subtitle'] ?? 'Modernes Content Management System',
                'logo_position' => $data['logo_position'] ?? 'top',
                'header_layout' => $data['header_layout'] ?? 'standard',
                'description' => $data['description'] ?? 'Ein leistungsstarkes, sicheres und erweiterbares CMS.',
                'header_buttons' => is_array($data['header_buttons'] ?? null) ? $data['header_buttons'] : [],
                'github_url' => $data['github_url'] ?? '',
                'github_text' => $data['github_text'] ?? '💻 GitHub Projekt',
                'gitlab_url' => $data['gitlab_url'] ?? '',
                'gitlab_text' => $data['gitlab_text'] ?? '🦊 GitLab Projekt',
                'version' => $data['version'] ?? (defined('CMS_VERSION') ? CMS_VERSION : Version::CURRENT),
                'logo' => $data['logo'] ?? '',
                'colors' => is_array($data['colors'] ?? null) ? $data['colors'] : $this->defaults->getDefaultColors(),
            ];
        } catch (\Throwable $e) {
            error_log('LandingHeaderService::getHeader() Error: ' . $e->getMessage());
            return $this->defaults->getDefaultHeader();
        }
    }

    public function updateHeader(array $data): bool
    {
        $existing = $this->getHeader();

        $colors = isset($data['hero_gradient_start'])
            ? [
                'hero_gradient_start' => $data['hero_gradient_start'] ?? '#1e293b',
                'hero_gradient_end' => $data['hero_gradient_end'] ?? '#0f172a',
                'hero_border' => $data['hero_border'] ?? '#3b82f6',
                'hero_text' => $data['hero_text'] ?? '#ffffff',
                'features_bg' => $data['features_bg'] ?? '#f8fafc',
                'feature_card_bg' => $data['feature_card_bg'] ?? '#ffffff',
                'feature_card_hover' => $data['feature_card_hover'] ?? '#3b82f6',
                'primary_button' => $data['primary_button'] ?? '#3b82f6',
            ]
            : ($existing['colors'] ?? $this->defaults->getDefaultColors());

        $payload = [
            'title' => $this->sanitizer->sanitizePlainText((string)($data['title'] ?? $existing['title']), 120),
            'subtitle' => $this->sanitizer->sanitizePlainText((string)($data['subtitle'] ?? $existing['subtitle']), 160),
            'logo_position' => $this->sanitizer->sanitizeEnum((string)($data['logo_position'] ?? $existing['logo_position'] ?? 'top'), self::ALLOWED_LOGO_POSITIONS, 'top'),
            'header_layout' => $this->sanitizer->sanitizeEnum((string)($data['header_layout'] ?? $existing['header_layout'] ?? 'standard'), self::ALLOWED_HEADER_LAYOUTS, 'standard'),
            'description' => $this->sanitizer->normalizeHtml($data['description'] ?? $existing['description']),
            'header_buttons' => $this->sanitizer->sanitizeHeaderButtons($data['header_buttons'] ?? $existing['header_buttons'] ?? []),
            'github_url' => $this->sanitizer->sanitizeUrl((string)($data['github_url'] ?? $existing['github_url'] ?? '')),
            'github_text' => $this->sanitizer->sanitizePlainText((string)($data['github_text'] ?? $existing['github_text'] ?? '💻 GitHub Projekt'), 40),
            'gitlab_url' => $this->sanitizer->sanitizeUrl((string)($data['gitlab_url'] ?? $existing['gitlab_url'] ?? '')),
            'gitlab_text' => $this->sanitizer->sanitizePlainText((string)($data['gitlab_text'] ?? $existing['gitlab_text'] ?? '🦊 GitLab Projekt'), 40),
            'version' => $this->sanitizer->sanitizePlainText((string)($data['version'] ?? $existing['version']), 40),
            'logo' => isset($data['logo'])
                ? $this->sanitizer->sanitizeRelativeAssetPath((string)$data['logo'])
                : ($existing['logo'] ?? ''),
            'colors' => $colors,
        ];

        try {
            return $this->repository->upsertSection('header', $payload, 0);
        } catch (\Throwable $e) {
            error_log('LandingHeaderService::updateHeader() Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array<string, string>
     */
    public function getColors(): array
    {
        $header = $this->getHeader();
        return is_array($header['colors'] ?? null) ? $header['colors'] : $this->defaults->getDefaultColors();
    }

    public function updateColors(array $data): bool
    {
        $existing = $this->getHeader();
        $colors = [
            'hero_gradient_start' => $data['hero_gradient_start'] ?? $existing['colors']['hero_gradient_start'] ?? '#1e293b',
            'hero_gradient_end' => $data['hero_gradient_end'] ?? $existing['colors']['hero_gradient_end'] ?? '#0f172a',
            'hero_border' => $data['hero_border'] ?? $existing['colors']['hero_border'] ?? '#3b82f6',
            'hero_text' => $data['hero_text'] ?? $existing['colors']['hero_text'] ?? '#ffffff',
            'features_bg' => $data['features_bg'] ?? $existing['colors']['features_bg'] ?? '#f8fafc',
            'feature_card_bg' => $data['feature_card_bg'] ?? $existing['colors']['feature_card_bg'] ?? '#ffffff',
            'feature_card_hover' => $data['feature_card_hover'] ?? $existing['colors']['feature_card_hover'] ?? '#3b82f6',
            'primary_button' => $data['primary_button'] ?? $existing['colors']['primary_button'] ?? '#3b82f6',
        ];

        $merged = array_merge($existing, $colors);
        foreach ($colors as $key => $value) {
            $merged[$key] = $value;
        }

        return $this->updateHeader($merged);
    }
}
