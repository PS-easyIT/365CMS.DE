<?php
declare(strict_types=1);

namespace CMS\Services\Landing;

use CMS\Json;
use CMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class LandingFeatureService
{
    public function __construct(
        private readonly LandingRepository $repository,
        private readonly LandingSanitizer $sanitizer,
        private readonly LandingDefaultsProvider $defaults,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFeatures(): array
    {
        try {
            $results = $this->repository->getSectionsByType('feature');
            if ($results === []) {
                return $this->defaults->getDefaultFeatures();
            }

            $features = [];
            foreach ($results as $row) {
                $data = Json::decodeArray($row['data'] ?? null, []);
                $data = is_array($data) ? $data : [];
                $features[] = [
                    'id' => (int)($row['id'] ?? 0),
                    'icon' => $data['icon'] ?? '🎯',
                    'title' => $data['title'] ?? '',
                    'description' => $data['description'] ?? '',
                    'sort_order' => (int)($row['sort_order'] ?? 0),
                ];
            }

            return $this->mergeWithDefaultFeatures($features);
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('landing')->warning('Landing feature list could not be loaded.', [
                'exception' => $e,
            ]);
            return $this->defaults->getDefaultFeatures();
        }
    }

    public function saveFeature(?int $id, array $data): int
    {
        $payload = [
            'icon' => $this->sanitizer->sanitizePlainText((string)($data['icon'] ?? '🎯'), 16),
            'title' => $this->sanitizer->sanitizePlainText((string)($data['title'] ?? ''), 80),
            'description' => $this->sanitizer->normalizeHtml($data['description'] ?? ''),
        ];

        $sortOrder = max(1, min(999, (int)($data['sort_order'] ?? 999)));

        try {
            return $this->repository->saveFeature($id, $payload, $sortOrder);
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('landing')->warning('Landing feature could not be saved.', [
                'feature_id' => $id,
                'sort_order' => $sortOrder,
                'exception' => $e,
            ]);
            return 0;
        }
    }

    public function deleteFeature(int $id): bool
    {
        return $this->repository->deleteFeature($id);
    }

    public function upgradeLegacyFeatureDefaults(): void
    {
        $features = $this->getFeatures();
        $legacyTitles = [
            'Blitzschnell',
            'Sicher',
            'Responsive',
            'Anpassbar',
            'Erweiterbar',
            'Analytics',
            'Multi-User',
            'SEO-Ready',
            'REST API',
            'Backups',
            'Updates',
            'Editor',
        ];

        $currentTitles = array_values(array_map(
            static fn(array $feature): string => trim((string)($feature['title'] ?? '')),
            $features
        ));

        if ($currentTitles !== $legacyTitles) {
            return;
        }

        $this->repository->deleteAllFeatures();
        foreach ($this->defaults->getDefaultFeatures() as $feature) {
            $this->saveFeature(null, $feature);
        }
    }

    public function backfillMissingDefaultFeatures(): void
    {
        $existingFeatures = $this->getFeatures();
        $defaultFeatures = $this->defaults->getDefaultFeatures();

        if (count($existingFeatures) >= count($defaultFeatures)) {
            return;
        }

        $existingTitles = [];
        $existingSortOrders = [];

        foreach ($existingFeatures as $feature) {
            $title = trim((string)($feature['title'] ?? ''));
            if ($title !== '') {
                $existingTitles[] = mb_strtolower($title);
            }

            $sortOrder = (int)($feature['sort_order'] ?? 0);
            if ($sortOrder > 0) {
                $existingSortOrders[] = $sortOrder;
            }
        }

        foreach ($defaultFeatures as $feature) {
            $defaultTitle = mb_strtolower(trim((string)($feature['title'] ?? '')));
            $defaultSortOrder = (int)($feature['sort_order'] ?? 0);

            if (
                ($defaultTitle !== '' && in_array($defaultTitle, $existingTitles, true))
                || ($defaultSortOrder > 0 && in_array($defaultSortOrder, $existingSortOrders, true))
            ) {
                continue;
            }

            $this->saveFeature(null, $feature);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $features
     * @return array<int, array<string, mixed>>
     */
    private function mergeWithDefaultFeatures(array $features): array
    {
        $defaultFeatures = $this->defaults->getDefaultFeatures();

        if (count($features) >= count($defaultFeatures)) {
            usort($features, static fn(array $a, array $b): int => ((int)($a['sort_order'] ?? 0)) <=> ((int)($b['sort_order'] ?? 0)));
            return $features;
        }

        $merged = $features;
        $existingTitles = [];
        $existingSortOrders = [];

        foreach ($features as $feature) {
            $title = trim((string)($feature['title'] ?? ''));
            if ($title !== '') {
                $existingTitles[] = mb_strtolower($title);
            }

            $sortOrder = (int)($feature['sort_order'] ?? 0);
            if ($sortOrder > 0) {
                $existingSortOrders[] = $sortOrder;
            }
        }

        foreach ($defaultFeatures as $feature) {
            $defaultTitle = mb_strtolower(trim((string)($feature['title'] ?? '')));
            $defaultSortOrder = (int)($feature['sort_order'] ?? 0);

            if (
                ($defaultTitle !== '' && in_array($defaultTitle, $existingTitles, true))
                || ($defaultSortOrder > 0 && in_array($defaultSortOrder, $existingSortOrders, true))
            ) {
                continue;
            }

            $merged[] = $feature;
        }

        usort($merged, static fn(array $a, array $b): int => ((int)($a['sort_order'] ?? 0)) <=> ((int)($b['sort_order'] ?? 0)));

        return $merged;
    }
}
