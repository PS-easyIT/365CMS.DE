<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class CoreModuleService
{
    private const string SETTINGS_GROUP = 'core_modules';

    /**
     * @var array<string, array{
     *     label:string,
     *     description:string,
     *     category:string,
     *     category_label:string,
     *     order:int,
     *     default_enabled:bool,
     *     dependencies:array<int,string>,
     *     admin_pages:array<int,string>,
     *     admin_labels:array<int,string>,
     *     sidebar_group:?string,
     *     legacy_setting:?string
     * }>
     */
    private const array MODULES = [
        'subscriptions' => [
            'label' => 'Aboverwaltung Core',
            'description' => 'Schaltet die integrierte Aboverwaltung als Kernmodul ein oder aus. Wenn dieses Modul deaktiviert ist, verschwinden alle zugehörigen Admin-Bereiche und abhängigen Laufzeitfunktionen.',
            'category' => 'subscriptions',
            'category_label' => 'Aboverwaltung',
            'order' => 10,
            'default_enabled' => true,
            'dependencies' => [],
            'admin_pages' => [],
            'admin_labels' => [],
            'sidebar_group' => null,
            'legacy_setting' => 'subscription_enabled',
        ],
        'subscription_admin_packages' => [
            'label' => 'Pakete & Abo-Einstellungen',
            'description' => 'Blendet den Admin-Bereich für Paketdefinitionen, Preise, Trial-Einstellungen, Steuern und Rechnungsoptionen ein oder aus.',
            'category' => 'subscriptions',
            'category_label' => 'Aboverwaltung',
            'order' => 20,
            'default_enabled' => true,
            'dependencies' => ['subscriptions'],
            'admin_pages' => ['packages'],
            'admin_labels' => ['Pakete & Abo-Einstellungen'],
            'sidebar_group' => 'subscriptions',
            'legacy_setting' => null,
        ],
        'subscription_admin_orders' => [
            'label' => 'Bestellungen & Zuweisung',
            'description' => 'Steuert den Admin-Bereich für Bestellungen, Statuspflege und manuelle Paketzuweisung.',
            'category' => 'subscriptions',
            'category_label' => 'Aboverwaltung',
            'order' => 30,
            'default_enabled' => true,
            'dependencies' => ['subscriptions'],
            'admin_pages' => ['orders'],
            'admin_labels' => ['Bestellungen & Zuweisung'],
            'sidebar_group' => 'subscriptions',
            'legacy_setting' => null,
        ],
        'subscription_admin_settings' => [
            'label' => 'Abo-Einstellungen',
            'description' => 'Aktiviert die globale Einstellungsseite der Aboverwaltung im Adminbereich.',
            'category' => 'subscriptions',
            'category_label' => 'Aboverwaltung',
            'order' => 40,
            'default_enabled' => true,
            'dependencies' => ['subscriptions'],
            'admin_pages' => ['subscription-settings'],
            'admin_labels' => ['Einstellungen'],
            'sidebar_group' => 'subscriptions',
            'legacy_setting' => null,
        ],
        'subscription_limits' => [
            'label' => 'Paketlimits & Zugriffsgates',
            'description' => 'Schaltet die Limit- und Zugriffsprüfung auf Basis der zugewiesenen Pakete systemweit an oder aus.',
            'category' => 'subscriptions',
            'category_label' => 'Aboverwaltung',
            'order' => 50,
            'default_enabled' => true,
            'dependencies' => ['subscriptions'],
            'admin_pages' => [],
            'admin_labels' => [],
            'sidebar_group' => null,
            'legacy_setting' => 'subscription_limits_enabled',
        ],
        'subscription_member_area' => [
            'label' => 'Member-Abo-Bereich',
            'description' => 'Blendet den Bereich „Abo & Bestellungen“ im Member-Dashboard samt zugehöriger Seite aus oder ein.',
            'category' => 'subscriptions',
            'category_label' => 'Aboverwaltung',
            'order' => 60,
            'default_enabled' => true,
            'dependencies' => ['subscriptions'],
            'admin_pages' => [],
            'admin_labels' => [],
            'sidebar_group' => null,
            'legacy_setting' => 'subscription_member_area_enabled',
        ],
        'subscription_ordering' => [
            'label' => 'Bestell- & Upgrade-Prozesse',
            'description' => 'Reservierter Kernschalter für Bestell- und Upgrade-Prozesse der Aboverwaltung.',
            'category' => 'subscriptions',
            'category_label' => 'Aboverwaltung',
            'order' => 70,
            'default_enabled' => true,
            'dependencies' => ['subscriptions'],
            'admin_pages' => [],
            'admin_labels' => [],
            'sidebar_group' => null,
            'legacy_setting' => 'subscription_ordering_enabled',
        ],
        'subscription_public_pricing' => [
            'label' => 'Öffentliche Paketkommunikation',
            'description' => 'Reservierter Kernschalter für öffentliche Paket- und Pricing-Darstellung.',
            'category' => 'subscriptions',
            'category_label' => 'Aboverwaltung',
            'order' => 80,
            'default_enabled' => true,
            'dependencies' => ['subscriptions'],
            'admin_pages' => [],
            'admin_labels' => [],
            'sidebar_group' => null,
            'legacy_setting' => 'subscription_public_pricing_enabled',
        ],
    ];

    private static ?self $instance = null;

    private SettingsService $settings;
    private Database $db;
    private string $prefix;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->settings = SettingsService::getInstance();
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    /** @return list<string> */
    public function getKnownModuleSlugs(): array
    {
        return array_keys(self::MODULES);
    }

    /** @return array<string, mixed>|null */
    public function getModuleDefinition(string $slug): ?array
    {
        return self::MODULES[$slug] ?? null;
    }

    public function isModuleEnabled(string $slug, bool $resolveDependencies = true): bool
    {
        $definition = self::MODULES[$slug] ?? null;
        if ($definition === null) {
            return true;
        }

        $storedEnabled = $this->resolveStoredEnabledState($slug, $definition);

        if (!$storedEnabled || !$resolveDependencies) {
            return $storedEnabled;
        }

        foreach ((array) ($definition['dependencies'] ?? []) as $dependencySlug) {
            if (!$this->isModuleEnabled((string) $dependencySlug, true)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<array<string, mixed>> */
    public function getModulesForAdmin(): array
    {
        $modules = [];
        foreach ($this->getSortedDefinitions() as $slug => $definition) {
            $modules[] = $this->buildAdminModulePayload($slug, $definition);
        }

        return $modules;
    }

    /** @return list<array<string, mixed>> */
    public function getGroupedModules(): array
    {
        $groups = [];

        foreach ($this->getModulesForAdmin() as $module) {
            $category = (string) ($module['category'] ?? 'general');
            if (!isset($groups[$category])) {
                $groups[$category] = [
                    'slug' => $category,
                    'label' => (string) ($module['category_label'] ?? ucfirst($category)),
                    'modules' => [],
                    'enabled_count' => 0,
                    'total_count' => 0,
                ];
            }

            $groups[$category]['modules'][] = $module;
            $groups[$category]['total_count']++;
            if (!empty($module['effective_enabled'])) {
                $groups[$category]['enabled_count']++;
            }
        }

        return array_values($groups);
    }

    public function isAdminPageEnabled(string $pageSlug): bool
    {
        $normalizedSlug = trim($pageSlug);
        if ($normalizedSlug === '') {
            return true;
        }

        $matchedModuleSlugs = [];
        foreach (self::MODULES as $slug => $definition) {
            if (in_array($normalizedSlug, (array) ($definition['admin_pages'] ?? []), true)) {
                $matchedModuleSlugs[] = $slug;
            }
        }

        if ($matchedModuleSlugs === []) {
            return true;
        }

        foreach ($matchedModuleSlugs as $moduleSlug) {
            if ($this->isModuleEnabled($moduleSlug, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $children
     * @return array<int, array<string, mixed>>
     */
    public function filterSidebarChildren(string $sidebarGroup, array $children): array
    {
        $filtered = [];

        foreach ($children as $child) {
            $pageSlug = trim((string) ($child['slug'] ?? ''));
            if ($pageSlug === '' || $this->pageBelongsToSidebarGroup($pageSlug, $sidebarGroup) === false) {
                $filtered[] = $child;
                continue;
            }

            if ($this->isAdminPageEnabled($pageSlug)) {
                $filtered[] = $child;
            }
        }

        return array_values($filtered);
    }

    public function isSidebarGroupVisible(string $sidebarGroup, array $children): bool
    {
        return $this->filterSidebarChildren($sidebarGroup, $children) !== [];
    }

    /**
     * @param array<string, bool> $requestedStates
     * @return array{stored:array<string,bool>,effective:array<string,bool>}
     */
    public function updateModuleStates(array $requestedStates): array
    {
        $normalizedStates = [];
        foreach (self::MODULES as $slug => $definition) {
            $normalizedStates[$slug] = !empty($requestedStates[$slug]);
        }

        $this->settings->setMany(self::SETTINGS_GROUP, $normalizedStates);

        $effectiveStates = [];
        foreach (self::MODULES as $slug => $definition) {
            $effectiveStates[$slug] = $this->isModuleEnabled($slug, true);
        }

        foreach (self::MODULES as $slug => $definition) {
            $legacySetting = (string) ($definition['legacy_setting'] ?? '');
            if ($legacySetting === '') {
                continue;
            }

            $this->storeLegacySetting($legacySetting, !empty($effectiveStates[$slug]) ? '1' : '0');
        }

        return [
            'stored' => $normalizedStates,
            'effective' => $effectiveStates,
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function getSortedDefinitions(): array
    {
        $definitions = self::MODULES;
        uasort($definitions, static function (array $left, array $right): int {
            return ((int) ($left['order'] ?? 999)) <=> ((int) ($right['order'] ?? 999));
        });

        return $definitions;
    }

    /** @param array<string, mixed> $definition */
    private function buildAdminModulePayload(string $slug, array $definition): array
    {
        $storedEnabled = $this->resolveStoredEnabledState($slug, $definition);
        $effectiveEnabled = $this->isModuleEnabled($slug, true);

        $dependencyLabels = [];
        foreach ((array) ($definition['dependencies'] ?? []) as $dependencySlug) {
            $dependencyDefinition = self::MODULES[(string) $dependencySlug] ?? null;
            if ($dependencyDefinition === null) {
                continue;
            }

            $dependencyLabels[] = (string) ($dependencyDefinition['label'] ?? $dependencySlug);
        }

        $statusReason = 'Aktiv.';
        if (!$storedEnabled) {
            $statusReason = 'Manuell deaktiviert.';
        } elseif (!$effectiveEnabled && $dependencyLabels !== []) {
            $statusReason = 'Blockiert durch abhängige Kernmodule: ' . implode(', ', $dependencyLabels) . '.';
        }

        return [
            'slug' => $slug,
            'label' => (string) ($definition['label'] ?? $slug),
            'description' => (string) ($definition['description'] ?? ''),
            'category' => (string) ($definition['category'] ?? 'general'),
            'category_label' => (string) ($definition['category_label'] ?? 'Allgemein'),
            'stored_enabled' => $storedEnabled,
            'effective_enabled' => $effectiveEnabled,
            'dependencies' => array_values((array) ($definition['dependencies'] ?? [])),
            'dependency_labels' => $dependencyLabels,
            'admin_pages' => array_values((array) ($definition['admin_pages'] ?? [])),
            'admin_labels' => array_values((array) ($definition['admin_labels'] ?? [])),
            'legacy_setting' => (string) ($definition['legacy_setting'] ?? ''),
            'status_reason' => $statusReason,
        ];
    }

    /** @param array<string, mixed> $definition */
    private function resolveStoredEnabledState(string $slug, array $definition): bool
    {
        $default = (bool) ($definition['default_enabled'] ?? true);
        $storedValue = $this->settings->get(self::SETTINGS_GROUP, $slug, null);
        if ($storedValue !== null) {
            return $this->normalizeStoredEnabledValue($storedValue, $default);
        }

        $legacySetting = trim((string) ($definition['legacy_setting'] ?? ''));
        if ($legacySetting !== '') {
            return $this->readLegacyEnabledSetting($legacySetting, $default);
        }

        return $default;
    }

    private function readLegacyEnabledSetting(string $settingKey, bool $default): bool
    {
        try {
            $value = $this->db->get_var(
                "SELECT option_value FROM {$this->prefix}settings WHERE option_name = ? LIMIT 1",
                [$settingKey]
            );
        } catch (\Throwable) {
            return $default;
        }

        if ($value === null) {
            return $default;
        }

        return $this->normalizeStoredEnabledValue($value, $default);
    }

    private function normalizeStoredEnabledValue(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if ($normalized === '') {
                return $default;
            }

            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        return $default;
    }

    private function pageBelongsToSidebarGroup(string $pageSlug, string $sidebarGroup): bool
    {
        foreach (self::MODULES as $definition) {
            if (($definition['sidebar_group'] ?? null) !== $sidebarGroup) {
                continue;
            }

            if (in_array($pageSlug, (array) ($definition['admin_pages'] ?? []), true)) {
                return true;
            }
        }

        return false;
    }

    private function storeLegacySetting(string $settingKey, string $value): void
    {
        $this->db->execute(
            "INSERT INTO {$this->prefix}settings (option_name, option_value)\n             VALUES (?, ?)\n             ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)",
            [$settingKey, $value]
        );
    }
}
