<?php
declare(strict_types=1);

namespace CMS\Services\Landing;

use CMS\Json;

if (!defined('ABSPATH')) {
    exit;
}

final class LandingPluginService
{
    private const ALLOWED_PLUGIN_AREAS = ['header', 'content', 'footer'];

    public function __construct(
        private readonly LandingRepository $repository,
        private readonly LandingSanitizer $sanitizer,
    ) {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getRegisteredPlugins(): array
    {
        if (!class_exists('\CMS\Hooks')) {
            return [];
        }

        $plugins = \CMS\Hooks::applyFilters('landing_page_plugins', []);
        if (!is_array($plugins)) {
            return [];
        }

        $normalized = [];
        foreach ($plugins as $pluginId => $plugin) {
            if (!is_array($plugin)) {
                continue;
            }

            $id = is_string($pluginId) && $pluginId !== ''
                ? $pluginId
                : $this->sanitizer->sanitizePluginId((string)($plugin['id'] ?? ''));

            if ($id === '') {
                continue;
            }

            $targets = array_values(array_intersect(
                self::ALLOWED_PLUGIN_AREAS,
                array_map('strval', (array)($plugin['targets'] ?? []))
            ));

            $normalized[$id] = [
                'id' => $id,
                'name' => $this->sanitizer->sanitizePlainText((string)($plugin['name'] ?? $id), 120),
                'description' => $this->sanitizer->sanitizePlainText((string)($plugin['description'] ?? ''), 280),
                'version' => $this->sanitizer->sanitizePlainText((string)($plugin['version'] ?? ''), 40),
                'author' => $this->sanitizer->sanitizePlainText((string)($plugin['author'] ?? ''), 80),
                'targets' => $targets,
                'settings_callback' => is_callable($plugin['settings_callback'] ?? null) ? $plugin['settings_callback'] : null,
            ];
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPluginOverrides(): array
    {
        try {
            $result = $this->repository->getSection('plugin_overrides');
            if ($result === null) {
                return $this->getDefaultPluginOverrides();
            }

            $data = Json::decodeArray($result['data'] ?? null, []);
            $defaults = $this->getDefaultPluginOverrides();
            $merged = array_merge($defaults, is_array($data) ? $data : []);
            $merged['id'] = (int)($result['id'] ?? 0);
            $merged['plugin_settings'] = is_array($merged['plugin_settings'] ?? null) ? $merged['plugin_settings'] : [];

            return $merged;
        } catch (\Throwable $e) {
            error_log('LandingPluginService::getPluginOverrides() Error: ' . $e->getMessage());
            return $this->getDefaultPluginOverrides();
        }
    }

    public function updatePluginOverride(array $data): bool
    {
        $area = (string)($data['area'] ?? '');
        $pluginId = $this->sanitizer->sanitizePluginId((string)($data['plugin_id'] ?? ''));

        if (!in_array($area, self::ALLOWED_PLUGIN_AREAS, true)) {
            return false;
        }

        if ($pluginId !== '') {
            $plugins = $this->getRegisteredPlugins();
            if (!isset($plugins[$pluginId]) || !in_array($area, $plugins[$pluginId]['targets'], true)) {
                return false;
            }
        }

        $overrides = $this->getPluginOverrides();
        $overrides[$area] = $pluginId === '' ? null : $pluginId;
        unset($overrides['id']);

        return $this->savePluginOverridesRecord($overrides);
    }

    public function savePluginSettings(string $pluginId, array $data): bool
    {
        $pluginId = $this->sanitizer->sanitizePluginId($pluginId);
        if ($pluginId === '') {
            return false;
        }

        $plugins = $this->getRegisteredPlugins();
        if (!isset($plugins[$pluginId])) {
            return false;
        }

        $overrides = $this->getPluginOverrides();
        $overrides['plugin_settings'] = is_array($overrides['plugin_settings'] ?? null) ? $overrides['plugin_settings'] : [];

        $cleanSettings = [];
        foreach ($data as $key => $value) {
            $cleanKey = $this->sanitizer->sanitizePluginId((string)$key);
            if ($cleanKey === '') {
                continue;
            }

            $cleanSettings[$cleanKey] = is_array($value)
                ? $this->sanitizer->sanitizePluginSettingsArray($value)
                : $this->sanitizer->sanitizePlainText((string)$value, 5000);
        }

        $overrides['plugin_settings'][$pluginId] = $cleanSettings;
        unset($overrides['id']);

        return $this->savePluginOverridesRecord($overrides);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPluginSettings(string $pluginId): array
    {
        $overrides = $this->getPluginOverrides();
        return (array)($overrides['plugin_settings'][$pluginId] ?? []);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function savePluginOverridesRecord(array $overrides): bool
    {
        try {
            return $this->repository->upsertSection('plugin_overrides', $overrides, 200);
        } catch (\Throwable $e) {
            error_log('LandingPluginService::savePluginOverridesRecord() Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultPluginOverrides(): array
    {
        return [
            'id' => null,
            'header' => null,
            'content' => null,
            'footer' => null,
            'plugin_settings' => [],
        ];
    }
}
