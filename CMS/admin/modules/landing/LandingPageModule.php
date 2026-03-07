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

use CMS\Services\LandingPageService;

class LandingPageModule
{
    private LandingPageService $service;

    public function __construct()
    {
        $this->service = LandingPageService::getInstance();
    }

    /**
     * Daten für den aktuellen Tab laden
     */
    public function getData(string $tab): array
    {
        return match ($tab) {
            'header'  => ['header'   => $this->service->getHeader()],
            'content' => [
                'content'  => $this->service->getContentSettings(),
                'features' => $this->service->getFeatures(),
            ],
            'footer'  => ['footer'   => $this->service->getFooter()],
            'design'  => [
                'design'   => $this->service->getDesign(),
                'colors'   => $this->service->getColors(),
            ],
            'plugins' => [
                'plugins'   => $this->service->getRegisteredPlugins(),
                'overrides' => $this->service->getPluginOverrides(),
            ],
            default   => [],
        };
    }

    public function saveHeader(array $post): array
    {
        try {
            $this->service->updateHeader($post);
            return ['success' => true, 'message' => 'Header gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function saveContent(array $post): array
    {
        try {
            $this->service->updateContentSettings($post);
            return ['success' => true, 'message' => 'Content-Einstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function saveFooter(array $post): array
    {
        try {
            $this->service->updateFooter($post);
            return ['success' => true, 'message' => 'Footer gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function saveDesign(array $post): array
    {
        try {
            $this->service->updateDesign($post);
            return ['success' => true, 'message' => 'Design gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function saveFeature(array $post): array
    {
        try {
            $id = !empty($post['feature_id']) ? (int)$post['feature_id'] : null;
            $this->service->saveFeature($id, $post);
            return ['success' => true, 'message' => 'Feature gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function deleteFeature(int $id): array
    {
        try {
            $this->service->deleteFeature($id);
            return ['success' => true, 'message' => 'Feature gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function savePlugin(array $post): array
    {
        try {
            $pluginId = preg_replace('/[^a-zA-Z0-9_-]/', '', $post['plugin_id'] ?? '');
            if (empty($pluginId)) {
                return ['success' => false, 'error' => 'Plugin-ID fehlt.'];
            }
            $this->service->savePluginSettings($pluginId, $post);
            return ['success' => true, 'message' => 'Plugin-Einstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }
}
