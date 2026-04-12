<?php
declare(strict_types=1);

namespace CMS\Services\Landing;

use CMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class LandingSectionService
{
    private readonly LandingDefaultsProvider $defaults;
    private readonly LandingHeaderService $headerService;
    private readonly LandingFeatureService $featureService;
    private readonly LandingSectionProfileService $profileService;

    public function __construct(
        private readonly LandingRepository $repository,
        private readonly LandingSanitizer $sanitizer,
    ) {
        $this->defaults = new LandingDefaultsProvider();
        $this->headerService = new LandingHeaderService($repository, $sanitizer, $this->defaults);
        $this->featureService = new LandingFeatureService($repository, $sanitizer, $this->defaults);
        $this->profileService = new LandingSectionProfileService($repository, $sanitizer, $this->defaults);
    }

    public function ensureDefaults(): void
    {
        try {
            if (!$this->repository->hasSectionRecord('header')) {
                $header = $this->defaults->getDefaultHeader();
                unset($header['id']);
                $this->headerService->updateHeader($header);
            }

            $featureCount = $this->repository->countSectionsByType('feature');
            if ($featureCount === 0) {
                foreach ($this->defaults->getDefaultFeatures() as $feature) {
                    $this->featureService->saveFeature(null, $feature);
                }
            } else {
                $this->featureService->backfillMissingDefaultFeatures();
                $this->featureService->upgradeLegacyFeatureDefaults();
            }

            $this->profileService->ensureDefaultSections();
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('landing')->warning('Landing defaults could not be ensured.', [
                'exception' => $e,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getHeader(): array
    {
        return $this->headerService->getHeader();
    }

    public function updateHeader(array $data): bool
    {
        return $this->headerService->updateHeader($data);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFeatures(): array
    {
        return $this->featureService->getFeatures();
    }

    public function saveFeature(?int $id, array $data): int
    {
        return $this->featureService->saveFeature($id, $data);
    }

    public function deleteFeature(int $id): bool
    {
        return $this->featureService->deleteFeature($id);
    }

    public function initializeDefaults(): void
    {
        $this->ensureDefaults();
    }

    /**
     * @return array<string, string>
     */
    public function getColors(): array
    {
        return $this->headerService->getColors();
    }

    /**
     * @return array<string, mixed>
     */
    public function getFooter(): array
    {
        return $this->profileService->getFooter();
    }

    public function updateFooter(array $data): bool
    {
        return $this->profileService->updateFooter($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function getContentSettings(): array
    {
        return $this->profileService->getContentSettings();
    }

    public function updateContentSettings(array $data): bool
    {
        return $this->profileService->updateContentSettings($data);
    }

    public function updateColors(array $data): bool
    {
        return $this->headerService->updateColors($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->profileService->getSettings();
    }

    public function updateSettings(array $data): bool
    {
        return $this->profileService->updateSettings($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDesign(): array
    {
        return $this->profileService->getDesign();
    }

    public function updateDesign(array $data): bool
    {
        return $this->profileService->updateDesign($data);
    }
}
