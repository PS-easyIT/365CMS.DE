<?php
declare(strict_types=1);

/**
 * Member Dashboard Module – Konfiguration des Member-Bereichs
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;

class MemberDashboardModule
{
    private Database $db;
    private string $prefix;

    private const SETTINGS_KEYS = [
        'member_dashboard_enabled',
        'member_registration_enabled',
        'member_email_verification',
        'member_welcome_message',
        'member_dashboard_widgets',
        'member_default_role',
        'member_profile_fields',
        'member_dashboard_columns',
        'member_dashboard_section_order',
        'member_dashboard_logo',
        'member_dashboard_greeting',
        'member_dashboard_welcome_text',
        'member_dashboard_show_welcome',
        'member_subscription_visible',
        'member_widget_1_title',
        'member_widget_1_content',
        'member_widget_1_icon',
        'member_widget_2_title',
        'member_widget_2_content',
        'member_widget_2_icon',
        'member_widget_3_title',
        'member_widget_3_content',
        'member_widget_3_icon',
        'member_widget_4_title',
        'member_widget_4_content',
        'member_widget_4_icon',
        'member_dashboard_color_primary',
        'member_dashboard_color_accent',
        'member_dashboard_color_bg',
        'member_dashboard_color_card_bg',
        'member_dashboard_color_text',
        'member_dashboard_color_border',
        'member_dashboard_show_quickstart',
        'member_dashboard_show_stats',
        'member_dashboard_show_custom_widgets',
        'member_dashboard_show_plugin_widgets',
        'member_dashboard_show_notifications_panel',
        'member_dashboard_show_onboarding_panel',
        'member_dashboard_notification_center_enabled',
        'member_dashboard_notification_email_enabled',
        'member_dashboard_notification_digest_frequency',
        'member_dashboard_notification_sender_name',
        'member_dashboard_notification_empty_text',
        'member_dashboard_notification_types',
        'member_dashboard_onboarding_enabled',
        'member_dashboard_onboarding_title',
        'member_dashboard_onboarding_intro',
        'member_dashboard_onboarding_steps',
        'member_dashboard_onboarding_cta_label',
        'member_dashboard_onboarding_cta_url',
        'member_dashboard_onboarding_require_profile_completion',
        'member_dashboard_plugin_order',
    ];

    private const SECTION_ORDER_OPTIONS = [
        'stats,widgets,plugins' => 'Statistiken → Widgets → Plugins',
        'stats,plugins,widgets' => 'Statistiken → Plugins → Widgets',
        'widgets,stats,plugins' => 'Widgets → Statistiken → Plugins',
        'plugins,stats,widgets' => 'Plugins → Statistiken → Widgets',
        'quick_start,stats,widgets,plugins' => 'Schnellstart → Statistiken → Widgets → Plugins',
        'quick_start,stats,plugins,widgets' => 'Schnellstart → Statistiken → Plugins → Widgets',
    ];

    private const NOTIFICATION_TYPES = [
        'system' => 'Systemmeldungen',
        'messages' => 'Direktnachrichten',
        'billing' => 'Abo & Rechnungen',
        'security' => 'Sicherheitswarnungen',
        'community' => 'Community & Aktivitäten',
    ];

    private const DIGEST_FREQUENCIES = [
        'instant' => 'Sofort',
        'daily' => 'Täglich',
        'weekly' => 'Wöchentlich',
    ];

    private const PROFILE_FIELDS = [
        'first_name' => [
            'label'       => 'Vorname',
            'description' => 'Pflichtnahes Basisfeld für persönliche Ansprache.',
            'recommended' => true,
        ],
        'last_name' => [
            'label'       => 'Nachname',
            'description' => 'Für vollständige Profile und Verzeichnisse sinnvoll.',
            'recommended' => true,
        ],
        'bio' => [
            'label'       => 'Biografie',
            'description' => 'Kurzbeschreibung für Mitgliederprofil oder Netzwerkseiten.',
            'recommended' => true,
        ],
        'website' => [
            'label'       => 'Website',
            'description' => 'Externe Website oder Portfolio verlinken.',
            'recommended' => false,
        ],
        'phone' => [
            'label'       => 'Telefon',
            'description' => 'Nur aktivieren, wenn Kontaktdaten im Portal sichtbar sein sollen.',
            'recommended' => false,
        ],
        'company' => [
            'label'       => 'Firma',
            'description' => 'Hilfreich für Branchen-, Speaker- oder Expertenprofile.',
            'recommended' => false,
        ],
        'position' => [
            'label'       => 'Position',
            'description' => 'Berufsbezeichnung oder Rolle im Unternehmen.',
            'recommended' => false,
        ],
        'location' => [
            'label'       => 'Standort',
            'description' => 'Ort oder Region für Community- und Netzwerkfunktionen.',
            'recommended' => false,
        ],
        'social' => [
            'label'       => 'Social-Media-Links',
            'description' => 'Zeigt zusätzliche Social-Profile im Member-Bereich.',
            'recommended' => false,
        ],
        'avatar' => [
            'label'       => 'Profilbild',
            'description' => 'Wichtig für persönliche Darstellung und Wiedererkennung.',
            'recommended' => true,
        ],
    ];

    public function __construct()
    {
        $this->db     = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * Einstellungen laden
     */
    public function getData(): array
    {
        $settings = $this->getSettings();
        $stats    = $this->getMemberStats();
        $widgets  = $this->getAvailableWidgets();
        $profileFields = $this->getProfileFieldDefinitions();

        return [
            'settings'  => $settings,
            'stats'     => $stats,
            'widgets'   => $widgets,
            'profileFields' => $profileFields,
            'roles'     => $this->getAvailableRoles(),
            'sectionOrderOptions' => self::SECTION_ORDER_OPTIONS,
            'notificationTypes' => self::NOTIFICATION_TYPES,
            'digestFrequencies' => self::DIGEST_FREQUENCIES,
            'pluginWidgets' => $this->getPluginWidgets(),
            'overview'  => [
                'enabledWidgets'      => count($settings['widgets'] ?? []),
                'enabledProfileFields'=> count($settings['profile_fields'] ?? []),
                'customWidgetCount'   => count(array_filter($settings['custom_widgets'] ?? [], static function (array $widget): bool {
                    return trim((string)($widget['title'] ?? '')) !== '' || trim((string)($widget['content'] ?? '')) !== '';
                })),
                'registrationEnabled' => !empty($settings['registration_enabled']),
                'verificationEnabled' => !empty($settings['email_verification']),
                'subscriptionVisible' => !empty($settings['subscription_visible']),
                'pluginWidgetCount'   => count($this->getPluginWidgets()),
            ],
        ];
    }

    /**
     * Einstellungen speichern
     */
    public function saveSettings(array $post): array
    {
        $section = (string)($post['settings_section'] ?? 'general');

        return $this->saveSection($section, $post);
    }

    public function saveSection(string $section, array $post): array
    {
        return match ($section) {
            'general'        => $this->saveGeneralSettings($post),
            'widgets'        => $this->saveWidgetSettings($post),
            'profile-fields' => $this->saveProfileSettings($post),
            'design'         => $this->saveDesignSettings($post),
            'frontend-modules' => $this->saveFrontendModules($post),
            'notifications'  => $this->saveNotificationSettings($post),
            'onboarding'     => $this->saveOnboardingSettings($post),
            'plugin-widgets' => $this->savePluginWidgetSettings($post),
            default          => ['success' => false, 'error' => 'Unbekannter Einstellungsbereich.'],
        };
    }

    private function saveGeneralSettings(array $post): array
    {
        try {
            $values = [
                'member_dashboard_enabled'    => !empty($post['dashboard_enabled']) ? '1' : '0',
                'member_welcome_message'      => strip_tags($post['welcome_message'] ?? '', '<p><a><strong><em><br>'),
                'member_dashboard_greeting'   => $this->sanitizeTextSetting((string)($post['dashboard_greeting'] ?? 'Guten Tag, {name}!'), 120),
                'member_dashboard_welcome_text' => strip_tags((string)($post['dashboard_welcome_text'] ?? ''), '<p><a><strong><em><br><ul><ol><li>'),
                'member_dashboard_show_welcome' => !empty($post['show_welcome']) ? '1' : '0',
                'member_dashboard_logo'       => trim((string)($post['dashboard_logo'] ?? '')),
            ];

            $this->persistSettings($values);

            return ['success' => true, 'message' => 'Allgemeine Member-Dashboard-Einstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    private function saveWidgetSettings(array $post): array
    {
        try {
            $selectedWidgets = array_values(array_intersect(
                array_keys($this->getAvailableWidgets()),
                array_keys(array_filter($post['widgets'] ?? []))
            ));

            $columns = (int)($post['dashboard_columns'] ?? 3);
            if ($columns < 1 || $columns > 4) {
                $columns = 3;
            }

            $sectionOrder = (string)($post['section_order'] ?? 'stats,widgets,plugins');
            if (!isset(self::SECTION_ORDER_OPTIONS[$sectionOrder])) {
                $sectionOrder = 'stats,widgets,plugins';
            }

            $values = [
                'member_dashboard_widgets'      => json_encode($selectedWidgets, JSON_UNESCAPED_UNICODE),
                'member_dashboard_columns'      => (string)$columns,
                'member_dashboard_section_order'=> $sectionOrder,
            ];

            for ($i = 1; $i <= 4; $i++) {
                $values["member_widget_{$i}_title"] = $this->sanitizeTextSetting((string)($post['custom_widgets'][$i]['title'] ?? ''), 80);
                $values["member_widget_{$i}_icon"] = $this->sanitizeTextSetting((string)($post['custom_widgets'][$i]['icon'] ?? ''), 16);
                $values["member_widget_{$i}_content"] = strip_tags((string)($post['custom_widgets'][$i]['content'] ?? ''), '<p><a><strong><em><br><ul><ol><li>');
            }

            $this->persistSettings($values);

            return ['success' => true, 'message' => 'Widget- und Layout-Einstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    private function saveProfileSettings(array $post): array
    {
        try {
            $selectedFields = array_values(array_intersect(
                array_keys(self::PROFILE_FIELDS),
                array_keys(array_filter($post['profile_fields'] ?? []))
            ));

            $values = [
                'member_profile_fields'       => json_encode($selectedFields, JSON_UNESCAPED_UNICODE),
                'member_subscription_visible' => !empty($post['subscription_visible']) ? '1' : '0',
            ];

            $this->persistSettings($values);

            return ['success' => true, 'message' => 'Profil-Felder und Navigationssichtbarkeit gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    private function saveDesignSettings(array $post): array
    {
        try {
            $values = [
                'member_dashboard_color_primary' => $this->sanitizeColor((string)($post['color_primary'] ?? '#6366f1'), '#6366f1'),
                'member_dashboard_color_accent' => $this->sanitizeColor((string)($post['color_accent'] ?? '#8b5cf6'), '#8b5cf6'),
                'member_dashboard_color_bg' => $this->sanitizeColor((string)($post['color_bg'] ?? '#f1f5f9'), '#f1f5f9'),
                'member_dashboard_color_card_bg' => $this->sanitizeColor((string)($post['color_card_bg'] ?? '#ffffff'), '#ffffff'),
                'member_dashboard_color_text' => $this->sanitizeColor((string)($post['color_text'] ?? '#1e293b'), '#1e293b'),
                'member_dashboard_color_border' => $this->sanitizeColor((string)($post['color_border'] ?? '#e2e8f0'), '#e2e8f0'),
            ];

            $this->persistSettings($values);

            return ['success' => true, 'message' => 'Design- und Farbvorgaben gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    private function saveFrontendModules(array $post): array
    {
        try {
            $values = [
                'member_dashboard_show_quickstart' => !empty($post['show_quickstart']) ? '1' : '0',
                'member_dashboard_show_stats' => !empty($post['show_stats']) ? '1' : '0',
                'member_dashboard_show_custom_widgets' => !empty($post['show_custom_widgets']) ? '1' : '0',
                'member_dashboard_show_plugin_widgets' => !empty($post['show_plugin_widgets']) ? '1' : '0',
                'member_dashboard_show_notifications_panel' => !empty($post['show_notifications_panel']) ? '1' : '0',
                'member_dashboard_show_onboarding_panel' => !empty($post['show_onboarding_panel']) ? '1' : '0',
            ];

            $this->persistSettings($values);

            return ['success' => true, 'message' => 'Frontend-Module gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    private function saveNotificationSettings(array $post): array
    {
        try {
            $selectedTypes = array_values(array_intersect(
                array_keys(self::NOTIFICATION_TYPES),
                array_keys(array_filter($post['notification_types'] ?? []))
            ));

            $frequency = (string)($post['notification_digest_frequency'] ?? 'daily');
            if (!isset(self::DIGEST_FREQUENCIES[$frequency])) {
                $frequency = 'daily';
            }

            $values = [
                'member_dashboard_notification_center_enabled' => !empty($post['notification_center_enabled']) ? '1' : '0',
                'member_dashboard_notification_email_enabled' => !empty($post['notification_email_enabled']) ? '1' : '0',
                'member_dashboard_notification_digest_frequency' => $frequency,
                'member_dashboard_notification_sender_name' => $this->sanitizeTextSetting((string)($post['notification_sender_name'] ?? '365CMS Member Hub'), 120),
                'member_dashboard_notification_empty_text' => $this->sanitizeTextSetting((string)($post['notification_empty_text'] ?? 'Aktuell gibt es keine neuen Meldungen.'), 255),
                'member_dashboard_notification_types' => json_encode($selectedTypes, JSON_UNESCAPED_UNICODE),
            ];

            $this->persistSettings($values);

            return ['success' => true, 'message' => 'Benachrichtigungseinstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    private function saveOnboardingSettings(array $post): array
    {
        try {
            $steps = preg_split('/\r\n|\r|\n/', (string)($post['onboarding_steps'] ?? '')) ?: [];
            $steps = array_values(array_filter(array_map(fn(string $step): string => $this->sanitizeTextSetting($step, 160), $steps)));

            $values = [
                'member_dashboard_onboarding_enabled' => !empty($post['onboarding_enabled']) ? '1' : '0',
                'member_dashboard_onboarding_title' => $this->sanitizeTextSetting((string)($post['onboarding_title'] ?? 'Dein nächster Schritt'), 120),
                'member_dashboard_onboarding_intro' => trim(strip_tags((string)($post['onboarding_intro'] ?? ''))),
                'member_dashboard_onboarding_steps' => json_encode($steps, JSON_UNESCAPED_UNICODE),
                'member_dashboard_onboarding_cta_label' => $this->sanitizeTextSetting((string)($post['onboarding_cta_label'] ?? 'Profil vervollständigen'), 80),
                'member_dashboard_onboarding_cta_url' => trim((string)($post['onboarding_cta_url'] ?? '/member/profile')),
                'member_dashboard_onboarding_require_profile_completion' => !empty($post['onboarding_require_profile_completion']) ? '1' : '0',
            ];

            $this->persistSettings($values);

            return ['success' => true, 'message' => 'Onboarding-Einstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    private function savePluginWidgetSettings(array $post): array
    {
        try {
            $pluginWidgets = $this->getPluginWidgets();
            $allowedPlugins = array_map(static fn(array $widget): string => (string)$widget['plugin'], $pluginWidgets);

            $order = array_values(array_filter(array_map(
                static fn(string $value): string => trim($value),
                explode(',', (string)($post['plugin_widget_order'] ?? ''))
            ), static fn(string $value): bool => in_array($value, $allowedPlugins, true)));

            $values = [
                'member_dashboard_plugin_order' => json_encode($order, JSON_UNESCAPED_UNICODE),
            ];

            foreach ($allowedPlugins as $pluginSlug) {
                $values['member_dashboard_plugin_' . $pluginSlug] = !empty($post['plugin_visible'][$pluginSlug]) ? '1' : '0';
            }

            $this->persistSettings($values);

            return ['success' => true, 'message' => 'Plugin-Widgets gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Einstellungen laden
     */
    private function getSettings(): array
    {
        $settings = [];
        try {
            $rows = $this->db->get_results(
                "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name LIKE 'member_%'"
            ) ?: [];
            foreach ($rows as $row) {
                $settings[$row->option_name] = $row->option_value;
            }
        } catch (\Throwable $e) {
            // Defaults
        }

        return [
            'dashboard_enabled'    => ($settings['member_dashboard_enabled'] ?? '1') === '1',
            'registration_enabled' => ($settings['member_registration_enabled'] ?? '1') === '1',
            'email_verification'   => ($settings['member_email_verification'] ?? '0') === '1',
            'welcome_message'      => $settings['member_welcome_message'] ?? '',
            'default_role'         => $settings['member_default_role'] ?? 'member',
            'widgets'              => \CMS\Json::decodeArray($settings['member_dashboard_widgets'] ?? null, []),
            'profile_fields'       => \CMS\Json::decodeArray($settings['member_profile_fields'] ?? null, []),
            'dashboard_columns'    => (int)($settings['member_dashboard_columns'] ?? 3),
            'section_order'        => $settings['member_dashboard_section_order'] ?? 'stats,widgets,plugins',
            'dashboard_logo'       => $settings['member_dashboard_logo'] ?? '',
            'dashboard_greeting'   => $settings['member_dashboard_greeting'] ?? 'Guten Tag, {name}!',
            'dashboard_welcome_text' => $settings['member_dashboard_welcome_text'] ?? '',
            'show_welcome'         => ($settings['member_dashboard_show_welcome'] ?? '1') === '1',
            'subscription_visible' => ($settings['member_subscription_visible'] ?? '0') === '1',
            'custom_widgets'       => $this->mapCustomWidgets($settings),
            'design'               => [
                'primary' => $settings['member_dashboard_color_primary'] ?? '#6366f1',
                'accent' => $settings['member_dashboard_color_accent'] ?? '#8b5cf6',
                'bg' => $settings['member_dashboard_color_bg'] ?? '#f1f5f9',
                'card_bg' => $settings['member_dashboard_color_card_bg'] ?? '#ffffff',
                'text' => $settings['member_dashboard_color_text'] ?? '#1e293b',
                'border' => $settings['member_dashboard_color_border'] ?? '#e2e8f0',
            ],
            'frontend_modules'     => [
                'show_quickstart' => ($settings['member_dashboard_show_quickstart'] ?? '1') === '1',
                'show_stats' => ($settings['member_dashboard_show_stats'] ?? '1') === '1',
                'show_custom_widgets' => ($settings['member_dashboard_show_custom_widgets'] ?? '1') === '1',
                'show_plugin_widgets' => ($settings['member_dashboard_show_plugin_widgets'] ?? '1') === '1',
                'show_notifications_panel' => ($settings['member_dashboard_show_notifications_panel'] ?? '1') === '1',
                'show_onboarding_panel' => ($settings['member_dashboard_show_onboarding_panel'] ?? '1') === '1',
            ],
            'notifications'        => [
                'center_enabled' => ($settings['member_dashboard_notification_center_enabled'] ?? '1') === '1',
                'email_enabled' => ($settings['member_dashboard_notification_email_enabled'] ?? '0') === '1',
                'digest_frequency' => $settings['member_dashboard_notification_digest_frequency'] ?? 'daily',
                'sender_name' => $settings['member_dashboard_notification_sender_name'] ?? '365CMS Member Hub',
                'empty_text' => $settings['member_dashboard_notification_empty_text'] ?? 'Aktuell gibt es keine neuen Meldungen.',
                'types' => \CMS\Json::decodeArray($settings['member_dashboard_notification_types'] ?? null, ['system', 'messages']),
            ],
            'onboarding'           => [
                'enabled' => ($settings['member_dashboard_onboarding_enabled'] ?? '1') === '1',
                'title' => $settings['member_dashboard_onboarding_title'] ?? 'So startest du optimal',
                'intro' => $settings['member_dashboard_onboarding_intro'] ?? 'Begleite neue Mitglieder mit einer klaren Checkliste und gezielten nächsten Schritten.',
                'steps' => \CMS\Json::decodeArray($settings['member_dashboard_onboarding_steps'] ?? null, [
                    'Profil vervollständigen',
                    'Profilbild hochladen',
                    'Passwort & Sicherheit prüfen',
                    'Erste Bereiche im Member-Dashboard entdecken',
                ]),
                'cta_label' => $settings['member_dashboard_onboarding_cta_label'] ?? 'Jetzt starten',
                'cta_url' => $settings['member_dashboard_onboarding_cta_url'] ?? '/member/profile',
                'require_profile_completion' => ($settings['member_dashboard_onboarding_require_profile_completion'] ?? '0') === '1',
            ],
            'plugin_widget_order'  => \CMS\Json::decodeArray($settings['member_dashboard_plugin_order'] ?? null, []),
        ];
    }

    /**
     * Member-Statistiken
     */
    private function getMemberStats(): array
    {
        try {
            $total    = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users");
            $active   = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users WHERE status = 'active'");
            $thisWeek = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

            return [
                'total'    => $total,
                'active'   => $active,
                'thisWeek' => $thisWeek,
            ];
        } catch (\Throwable $e) {
            return ['total' => 0, 'active' => 0, 'thisWeek' => 0];
        }
    }

    /**
     * Verfügbare Dashboard-Widgets
     */
    private function getAvailableWidgets(): array
    {
        return [
            'profile' => [
                'label'       => 'Profil-Übersicht',
                'description' => 'Zeigt Basisinformationen, Avatar und Kurzstatus des Mitglieds.',
                'recommended' => true,
            ],
            'activity' => [
                'label'       => 'Letzte Aktivitäten',
                'description' => 'Bündelt jüngste Aktionen, Änderungen oder Interaktionen.',
                'recommended' => true,
            ],
            'messages' => [
                'label'       => 'Nachrichten',
                'description' => 'Reserviert Platz für direkte Kommunikation oder Systemnachrichten.',
                'recommended' => false,
            ],
            'bookmarks' => [
                'label'       => 'Lesezeichen',
                'description' => 'Speichert relevante Inhalte oder interne Schnellmerker.',
                'recommended' => false,
            ],
            'notifications' => [
                'label'       => 'Benachrichtigungen',
                'description' => 'Zeigt Statusmeldungen, Erinnerungen oder Workflow-Hinweise.',
                'recommended' => true,
            ],
            'quick_links' => [
                'label'       => 'Schnellzugriffe',
                'description' => 'Nützlich für direkte Aktionen in häufig verwendete Member-Bereiche.',
                'recommended' => true,
            ],
            'statistics' => [
                'label'       => 'Statistiken',
                'description' => 'Kennzahlen, Zähler oder Leistungsübersichten im Dashboard.',
                'recommended' => false,
            ],
        ];
    }

    private function getProfileFieldDefinitions(): array
    {
        return self::PROFILE_FIELDS;
    }

    private function getAvailableRoles(): array
    {
        $roles = ['member', 'author'];

        try {
            $dbRoles = $this->db->get_results("SELECT DISTINCT role FROM {$this->prefix}users ORDER BY role ASC") ?: [];
            foreach ($dbRoles as $row) {
                $role = $this->sanitizeRole((string)($row->role ?? ''));
                if ($role !== '' && !in_array($role, $roles, true)) {
                    $roles[] = $role;
                }
            }
        } catch (\Throwable $e) {
        }

        return $roles;
    }

    private function getPluginWidgets(): array
    {
        if (!class_exists('\\CMS\\Member\\PluginDashboardRegistry')) {
            return [];
        }

        try {
            $registry = \CMS\Member\PluginDashboardRegistry::instance();
            $registry->init();

            $widgets = [];
            foreach ($registry->getAll() as $section) {
                if (!empty($section['parent_slug'])) {
                    continue;
                }

                $config = is_array($section['dashboard_widget'] ?? null) ? $section['dashboard_widget'] : [];
                $plugin = (string)($section['plugin'] ?? $section['slug'] ?? '');
                if ($plugin === '') {
                    continue;
                }

                $widgets[] = [
                    'plugin' => $plugin,
                    'slug' => (string)($section['slug'] ?? $plugin),
                    'label' => (string)($config['title'] ?? $section['label'] ?? $plugin),
                    'description' => (string)($config['description'] ?? ''),
                    'icon' => (string)($config['icon'] ?? $section['icon'] ?? '🔌'),
                    'color' => (string)($config['color'] ?? '#4f46e5'),
                    'priority' => (int)($section['priority'] ?? 50),
                ];
            }

            return $widgets;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function persistSettings(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->upsertSetting((string)$key, (string)$value);
        }
    }

    private function upsertSetting(string $key, string $value): void
    {
        $existing = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?",
            [$key]
        );

        if ((int)$existing > 0) {
            $this->db->execute(
                "UPDATE {$this->prefix}settings SET option_value = ? WHERE option_name = ?",
                [$value, $key]
            );
            return;
        }

        $this->db->execute(
            "INSERT INTO {$this->prefix}settings (option_name, option_value) VALUES (?, ?)",
            [$key, $value]
        );
    }

    private function mapCustomWidgets(array $settings): array
    {
        $widgets = [];

        for ($i = 1; $i <= 4; $i++) {
            $widgets[$i] = [
                'title'   => (string)($settings["member_widget_{$i}_title"] ?? ''),
                'content' => (string)($settings["member_widget_{$i}_content"] ?? ''),
                'icon'    => (string)($settings["member_widget_{$i}_icon"] ?? ''),
            ];
        }

        return $widgets;
    }

    private function sanitizeRole(string $role): string
    {
        $role = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($role)) ?? '';

        return $role !== '' ? strtolower($role) : 'member';
    }

    private function sanitizeTextSetting(string $value, int $maxLength): string
    {
        $value = trim(strip_tags($value));

        if ($value === '') {
            return '';
        }

        return function_exists('mb_substr')
            ? mb_substr($value, 0, $maxLength)
            : substr($value, 0, $maxLength);
    }

    private function sanitizeColor(string $value, string $fallback): string
    {
        $value = trim($value);

        if (preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1) {
            return strtolower($value);
        }

        return $fallback;
    }
}
