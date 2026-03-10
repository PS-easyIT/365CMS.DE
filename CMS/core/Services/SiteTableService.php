<?php
/**
 * SiteTableService – Frontend-Rendering und Export für Seitentabellen.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;
use CMS\Services\SiteTable\SiteTableHubRenderer;
use CMS\Services\SiteTable\SiteTableRepository;
use CMS\Services\SiteTable\SiteTableTableRenderer;
use CMS\Services\SiteTable\SiteTableTemplateRegistry;

if (!defined('ABSPATH')) {
    exit;
}

final class SiteTableService
{
    private static ?self $instance = null;

    private SiteTableRepository $repository;

    private SiteTableTemplateRegistry $templateRegistry;

    private SiteTableHubRenderer $hubRenderer;

    private SiteTableTableRenderer $tableRenderer;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $db = Database::instance();
        $this->repository = new SiteTableRepository($db, $db->getPrefix());
        $this->templateRegistry = new SiteTableTemplateRegistry($this->repository);
        $this->hubRenderer = new SiteTableHubRenderer($this->templateRegistry);
        $this->tableRenderer = new SiteTableTableRenderer($this->templateRegistry);
    }

    public function replaceShortcodes(string $content): string
    {
        if (!str_contains($content, '[site-table') && !str_contains($content, '[hub-site')) {
            return $content;
        }

        $content = (string) preg_replace_callback(
            '/\[site-table\s+id\s*=\s*["\']?(\d+)["\']?\s*\]/i',
            fn(array $matches): string => $this->renderTableById((int) ($matches[1] ?? 0)),
            $content
        );

        return (string) preg_replace_callback(
            '/\[hub-site\s+id\s*=\s*["\']?(\d+)["\']?\s*\]/i',
            fn(array $matches): string => $this->renderHubById((int) ($matches[1] ?? 0)),
            $content
        );
    }

    public function renderTableById(int $tableId): string
    {
        $table = $this->repository->getTableById($tableId);
        if ($table === null) {
            return '';
        }

        if ($this->isHubTable($table)) {
            return $this->hubRenderer->renderHubMarkup($table);
        }

        return $this->tableRenderer->renderTable($tableId, $table);
    }

    public function renderHubById(int $tableId): string
    {
        $table = $this->repository->getTableById($tableId);
        if ($table === null || !$this->isHubTable($table)) {
            return '';
        }

        return $this->hubRenderer->renderHubMarkup($table);
    }

    public function getHubPageBySlug(string $slug, string $locale = 'de'): ?array
    {
        $slug = $this->sanitizeSlug($slug);
        if ($slug === '') {
            return null;
        }

        $table = $this->repository->getHubTableBySlug($slug);
        if ($table === null) {
            return null;
        }

        return $this->hubRenderer->buildHubPage($table, $slug, $locale);
    }

    public function streamExportById(int $tableId, string $format, bool $respectFrontendPermissions = true): bool
    {
        $table = $this->repository->getTableById($tableId);
        if ($table === null) {
            return false;
        }

        return $this->tableRenderer->streamExport($table, $format, $respectFrontendPermissions, [$this, 'sanitizeSlug']);
    }

    private function isHubTable(array $table): bool
    {
        return (($table['settings']['content_mode'] ?? 'table') === 'hub');
    }

    private function sanitizeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = (string) preg_replace('/[^a-z0-9]+/i', '-', $value);

        return trim($value, '-');
    }
}
