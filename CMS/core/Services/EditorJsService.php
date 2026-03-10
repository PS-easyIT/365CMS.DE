<?php
/**
 * Editor.js Integration Service
 *
 * Verwaltet Editor.js Asset-Loading, stellt render()-API bereit
 * und bietet Upload-/Fetch-Endpoints für Bilder, Dateien und Link-Metadaten.
 *
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Services\EditorJs\EditorJsAssetService;
use CMS\Services\EditorJs\EditorJsMediaService;
use CMS\Services\EditorJs\EditorJsSanitizer;

if (!defined('ABSPATH')) {
    exit;
}

final class EditorJsService
{
    private static ?self $instance = null;

    private readonly EditorJsAssetService $assetService;
    private readonly EditorJsMediaService $mediaService;
    private readonly EditorJsSanitizer $sanitizer;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->assetService = new EditorJsAssetService();
        $this->mediaService = new EditorJsMediaService();
        $this->sanitizer = new EditorJsSanitizer();

        \CMS\Hooks::addAction('admin_head', [$this->assetService, 'enqueueEditorAssets']);
    }

    /**
     * Editor.js Block-Editor rendern.
     *
     * @param string $name      Feld-Name (hidden input)
     * @param string $content   Gespeicherter JSON-String (oder leerer String)
     * @param array  $settings  Optionale Einstellungen (height, placeholder, etc.)
     */
    public function render(string $name, string $content = '', array $settings = []): string
    {
        return $this->assetService->render($name, $content, $settings);
    }

    /**
     * Liefert die Admin-Assets für Editor.js zurück.
     *
     * @return array{css: string[], js: string[]}
     */
    public function getPageAssets(): array
    {
        return $this->assetService->getPageAssets();
    }

    /**
     * Editor.js Assets laden (CSS + JS + Plugins).
     */
    public function enqueueEditorAssets(): void
    {
        $this->assetService->enqueueEditorAssets();
    }

    /**
     * Zentrale API für Editor.js Uploads und Link-Metadaten.
     */
    public function handleMediaApiRequest(): void
    {
        $this->mediaService->handleMediaApiRequest();
    }

    /**
     * Editor.js JSON-Daten sanitieren (Block-Typen + Inline-HTML prüfen).
     */
    public function sanitize(string $json): string
    {
        return $this->sanitizer->sanitize($json);
    }
}
