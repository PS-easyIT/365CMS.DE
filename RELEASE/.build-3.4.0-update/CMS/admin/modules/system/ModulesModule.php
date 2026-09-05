<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;
use CMS\Logger;
use CMS\Services\CoreModuleService;

final class ModulesModule
{
    private CoreModuleService $coreModuleService;
    private \CMS\Logger $logger;

    public function __construct()
    {
        $this->coreModuleService = CoreModuleService::getInstance();
        $this->logger = Logger::instance()->withChannel('admin.modules');
    }

    public function getData(): array
    {
        return [
            'groups' => $this->coreModuleService->getGroupedModules(),
        ];
    }

    public function saveModules(array $post): array
    {
        try {
            $submittedModules = is_array($post['modules'] ?? null) ? $post['modules'] : [];
            $requestedStates = [];

            foreach ($this->coreModuleService->getKnownModuleSlugs() as $slug) {
                $requestedStates[$slug] = !empty($submittedModules[$slug]);
            }

            $result = $this->coreModuleService->updateModuleStates($requestedStates);
            $enabledCount = count(array_filter($result['effective'], static fn (bool $enabled): bool => $enabled));

            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'system.modules.save',
                'Core-Module aktualisiert',
                'core_modules',
                null,
                [
                    'enabled_count' => $enabledCount,
                    'module_count' => count($result['effective']),
                ],
                'info'
            );

            return [
                'success' => true,
                'message' => 'Core-Module wurden gespeichert.',
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Core-Module konnten nicht gespeichert werden.', [
                'exception' => $e,
            ]);

            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'system.modules.save_failed',
                'Core-Module konnten nicht gespeichert werden.',
                'core_modules',
                null,
                ['exception' => $e::class],
                'error'
            );

            return [
                'success' => false,
                'error' => 'Core-Module konnten nicht gespeichert werden. Bitte Logs prüfen.',
            ];
        }
    }
}
