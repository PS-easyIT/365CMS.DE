<?php
/**
 * Plugin Dashboard Registry
 *
 * Zentrale Registrierung für Plugin-Bereiche im Member-Dashboard.
 * Plugins nutzen `member_dashboard_init`-Action um sich hier einzutragen.
 *
 * Verwendung in einem Plugin:
 * ─────────────────────────────────────────────────────────────────────────────
 * \CMS\Hooks::addAction('member_dashboard_init', function(\CMS\Member\PluginDashboardRegistry $r) {
 *     $r->register([
 *         'plugin'    => 'cms-jobads',
 *         'slug'      => 'jobads',
 *         'label'     => 'Stellenanzeigen',
 *         'icon'      => '💼',
 *         'category'  => 'plugins',
 *         'priority'  => 20,
 *         'capability'=> null,       // null = alle eingeloggten Member
 *         'dashboard_widget' => [
 *             'title'          => 'Meine Stellen',
 *             'description'    => 'Verwalte deine Stellenanzeigen.',
 *             'color'          => '#f59e0b',
 *             'stats_callback' => [MyClass::class, 'getDashboardStats'],
 *             // stats_callback muss array ['count'=>int, 'label'=>string] zurückgeben
 *         ],
 *         'render_callback' => function(object $user, array $params) {
 *             include JOBADS_DIR . 'member/views/my-jobs-view.php';
 *         },
 *         'post_callback'   => function(object $user, array $params): void {
 *             // Optional: verarbeitet POST-Requests für diesen Bereich
 *         },
 *     ]);
 * });
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * @package CMSv2\Core\Member
 */

declare(strict_types=1);

namespace CMS\Member;

use CMS\Auth;
use CMS\Hooks;
use CMS\ThemeManager;

if (!defined('ABSPATH')) {
    exit;
}

class PluginDashboardRegistry
{
    private static ?self $instance = null;

    /** @var array<string, array> Registered plugin sections keyed by slug */
    private array $sections = [];

    /** @var bool Ob init bereits gefeuert wurde */
    private bool $initialized = false;

    /**
     * Cache für Admin-Sichtbarkeits-Einstellungen aus der DB.
     * null = noch nicht geladen; array = geladen (key=plugin-slug, val=bool)
     *
     * @var array<string,bool>|null
     */
    private ?array $adminVisibility = null;

    /** @var array<string,array<string,string>>|null */
    private ?array $widgetMetaOverrides = null;

    // ── Singleton ────────────────────────────────────────────────────────────

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    // ── Init ─────────────────────────────────────────────────────────────────

    /**
     * Feuert die `member_dashboard_init`-Action (einmalig).
     * Wird vom MemberController im Konstruktor aufgerufen.
     */
    public function init(): void
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;

        // Plugins können sich jetzt registrieren
        Hooks::doAction('member_dashboard_init', $this);

        // Menü-Items automatisch aus der Registry einspeisen
        Hooks::addFilter('member_menu_items', [$this, 'filterMenuItems'], 20);
    }

    // ── Registration API ─────────────────────────────────────────────────────

    /**
     * Filter-Callback für `member_menu_items`.
     * Fügt Menüpunkte aller registrierten Plugin-Bereiche ein.
     *
     * @param array $menuItems Vorhandene Menüeinträge
     * @return array Ergänzte Menüeinträge
     */
    public function filterMenuItems(array $menuItems): array
    {
        // Aktuellen Slug aus URI ermitteln
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $currentSlug = '';
        if (preg_match('#/member/plugin/([a-zA-Z0-9_-]+)#', $uri, $m)) {
            $currentSlug = $m[1];
        }

        foreach ($this->getMenuItems($currentSlug) as $item) {
            // Doppeleinträge vermeiden
            $exists = false;
            foreach ($menuItems as $existing) {
                if ($existing['slug'] === $item['slug']) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $menuItems[] = $item;
            }
        }

        return $menuItems;
    }

    /**
     * Registriert einen Plugin-Bereich im Member-Dashboard.     *
     * Pflichtfelder: slug, label, render_callback
     *
     * @param array{
     *   plugin:             string,
     *   slug:               string,
     *   label:              string,
     *   icon?:              string,
     *   category?:          string,
     *   priority?:          int,
     *   capability?:        string|null,
    *   dashboard_widget?:  array|bool|null,
     *   render_callback:    callable,
     *   post_callback?:     callable|null,
     * } $config
     */
    public function register(array $config): void
    {
        $slug = trim($config['slug'] ?? '');
        if ($slug === '' || !isset($config['render_callback'])) {
            trigger_error(
                '[PluginDashboardRegistry] register() benötigt mindestens "slug" und "render_callback".',
                E_USER_WARNING
            );
            return;
        }

        $this->sections[$slug] = [
            'plugin'           => $config['plugin'] ?? $slug,
            'slug'             => $slug,
            'label'            => $config['label'] ?? $slug,
            'icon'             => $config['icon'] ?? '🔌',
            'category'         => $config['category'] ?? 'plugins',
            'priority'         => (int) ($config['priority'] ?? 50),
            'capability'       => $config['capability'] ?? null,
            'parent_slug'      => $config['parent_slug'] ?? null,
            'dashboard_widget' => $config['dashboard_widget'] ?? null,
            'render_callback'  => $config['render_callback'],
            'post_callback'    => $config['post_callback'] ?? null,
        ];
    }

    // ── Query API ─────────────────────────────────────────────────────────────

    /**
     * Liefert einen einzelnen registrierten Bereich oder null.
     */
    public function getSection(string $slug): ?array
    {
        return $this->sections[$slug] ?? null;
    }

    /**
     * Liefert alle registrierten Bereiche, sortiert nach priority.
     *
     * Filtert nach Berechtigung des aktuellen Benutzers UND nach Admin-
     * Sichtbarkeits-Einstellungen (member_dashboard_plugin_{slug}).
     *
     * @return array[]
     */
    public function getAll(): array
    {
        $sections = array_filter(
            $this->sections,
            fn(array $s) => $this->checkCapability($s['capability'])
                         && $this->isVisibleByAdmin($s)
        );

        uasort($sections, fn(array $a, array $b) => $a['priority'] <=> $b['priority']);

        return array_values($sections);
    }

    /**
     * Lädt alle member_dashboard_plugin_* Einstellungen einmalig aus der DB.
     */
    private function loadAdminVisibility(): void
    {
        if ($this->adminVisibility !== null) {
            return;
        }
        $this->adminVisibility = [];
        try {
            $db   = \CMS\Database::instance();
            $stmt = $db->prepare(
                "SELECT option_name, option_value FROM {$db->getPrefix()}settings
                  WHERE option_name LIKE 'member_dashboard_plugin_%'"
            );
            if ($stmt) {
                $stmt->execute();
                foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $row) {
                    $pluginSlug = substr($row->option_name, strlen('member_dashboard_plugin_'));
                    $this->adminVisibility[$pluginSlug] = ($row->option_value === '1');
                }
            }
        } catch (\Throwable $e) {
            // Bei DB-Fehler alle Bereiche anzeigen (Fallback = sichtbar)
        }
    }

    private function loadWidgetMetaOverrides(): void
    {
        if ($this->widgetMetaOverrides !== null) {
            return;
        }

        $this->widgetMetaOverrides = [];

        try {
            $db = \CMS\Database::instance();
            $stmt = $db->prepare(
                "SELECT option_name, option_value FROM {$db->getPrefix()}settings
                 WHERE option_name LIKE 'member_dashboard_widget_meta_%'"
            );

            if ($stmt) {
                $stmt->execute();
                foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $row) {
                    $pluginSlug = substr((string) $row->option_name, strlen('member_dashboard_widget_meta_'));
                    $decoded = \CMS\Json::decodeArray((string) ($row->option_value ?? ''), []);
                    $this->widgetMetaOverrides[$pluginSlug] = is_array($decoded) ? $decoded : [];
                }
            }
        } catch (\Throwable $e) {
            $this->widgetMetaOverrides = [];
        }
    }

    /**
     * @return array<string,string>
     */
    private function getWidgetMetaOverride(string $pluginSlug): array
    {
        $this->loadWidgetMetaOverrides();
        return $this->widgetMetaOverrides[$pluginSlug] ?? [];
    }

    /**
     * Prüft ob ein Plugin-Bereich laut Admin-Einstellungen sichtbar ist.
     * Kein Eintrag in der DB = noch nie gesetzt = standardmäßig sichtbar.
     */
    private function isVisibleByAdmin(array $section): bool
    {
        $this->loadAdminVisibility();
        $pluginSlug = $section['plugin'] ?? $section['slug'];
        // null (kein Eintrag) = noch nie deaktiviert → sichtbar
        return $this->adminVisibility[$pluginSlug] ?? true;
    }

    /**
     * Gibt Menüeinträge zurück (bereit für getMemberMenuItems-Filter).
     */
    public function getMenuItems(string $currentSlug = ''): array
    {
        $items = [];
        foreach ($this->getAll() as $section) {
            $items[] = [
                'slug'        => 'plugin_' . $section['slug'],
                'label'       => $section['label'],
                'icon'        => $section['icon'],
                'url'         => '/member/plugin/' . $section['slug'],
                'active'      => $currentSlug === $section['slug'],
                'category'    => $section['category'],
                'parent_slug' => $section['parent_slug'] ?? null,
            ];
        }
        return $items;
    }

    /**
    * Gibt Dashboard-Widgets für alle sichtbaren Plugin-Bereiche zurück.
    * Ist `dashboard_widget` null, wird dennoch ein Standard-Widget erzeugt.
    * Mit `dashboard_widget === false` kann ein Bereich explizit von den
    * Kacheln im generischen 365CMS-Member-Dashboard ausgeschlossen werden.
     */
    public function getDashboardWidgets(object $user): array
    {
        $widgets = [];
        $isAdmin = Auth::instance()->isAdmin();

        foreach ($this->getAll() as $section) {
            // Sub-Items (mit parent_slug) bekommen keine eigene Dashboard-Kachel
            if (!empty($section['parent_slug'])) {
                continue;
            }
            if (($section['dashboard_widget'] ?? null) === false) {
                continue;
            }
            $wConfig = $section['dashboard_widget'] ?? [];
            $metaOverride = $this->getWidgetMetaOverride((string) ($section['plugin'] ?? $section['slug'] ?? ''));

            // Stats via Callback ermitteln
            $stats = null;
            if (!empty($wConfig['stats_callback']) && is_callable($wConfig['stats_callback'])) {
                try {
                    $stats = call_user_func($wConfig['stats_callback'], $user);
                } catch (\Throwable $e) {
                    // Stats-Fehler still ignorieren
                }
            }

            $widgets[] = [
                'plugin'      => $section['plugin'],
                'slug'        => $section['slug'],
                'icon'        => $metaOverride['icon'] ?? $wConfig['icon'] ?? $section['icon'],
                'title'       => $metaOverride['title'] ?? $wConfig['title'] ?? $section['label'],
                'description' => $metaOverride['description'] ?? $wConfig['description'] ?? '',
                'color'       => $metaOverride['color'] ?? $wConfig['color'] ?? '#4f46e5',
                'link'        => '/member/plugin/' . $section['slug'],
                'link_label'  => $wConfig['link_label'] ?? 'Öffnen',
                'admin_link'  => $isAdmin && !empty($wConfig['admin_url']) ? $wConfig['admin_url'] : null,
                'admin_label' => $wConfig['admin_label'] ?? '⚙️ Verwalten',
                'stats'       => $stats,  // null oder ['count'=>int, 'label'=>string]
                'badge'       => $wConfig['badge'] ?? null,   // z.B. 'Neu' oder '3'
            ];
        }

        return $widgets;
    }

    // ── Dispatcher ────────────────────────────────────────────────────────────

    /**
     * Wird vom Router für /member/plugin/:slug (GET + POST) aufgerufen.
     *
     * @param string $slug
     * @param array  $params Zusätzliche Route-Parameter
     */
    public function handleRoute(string $slug, array $params = []): void
    {
        // Auth-Check (Member muss eingeloggt sein)
        if (!Auth::instance()->isLoggedIn()) {
            header('Location: ' . (defined('SITE_URL') ? SITE_URL : '') . '/login');
            exit;
        }

        // Init sicherstellen (falls direkt über Route aufgerufen)
        $this->init();

        $section = $this->getSection($slug);

        if ($section === null) {
            require_once ABSPATH . 'member/partials/plugin-not-found.php';
            return;
        }

        // Berechtigungsprüfung
        if (!$this->checkCapability($section['capability'])) {
            require_once ABSPATH . 'member/partials/plugin-not-found.php';
            return;
        }

        $user = Auth::instance()->getCurrentUser();

        // Theme-Override bevorzugen, damit Plugin-Bereiche im Member-Design
        // des aktiven Themes statt im generischen 365CMS-Wrapper erscheinen.
        $themeFile = ThemeManager::instance()->getThemePath() . 'member/plugin-section.php';
        if (file_exists($themeFile)) {
            require $themeFile;
            return;
        }

        // Fallback auf den generischen Core-Wrapper.
        require_once ABSPATH . 'member/plugin-section.php';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Prüft ob der aktuelle Benutzer die Berechtigung hat.
     */
    private function checkCapability(?string $capability): bool
    {
        if ($capability === null) {
            return true;
        }
        $auth = Auth::instance();
        if ($capability === 'admin') {
            return $auth->isAdmin();
        }
        // Erweiterbar für weitere Rollen
        return true;
    }
}
