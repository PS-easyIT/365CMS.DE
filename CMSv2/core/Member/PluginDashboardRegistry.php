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
 * @version 1.0.0
 */

declare(strict_types=1);

namespace CMS\Member;

use CMS\Auth;
use CMS\Hooks;

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
     *   dashboard_widget?:  array|null,
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
     * Filtert nach Berechtigung des aktuellen Benutzers.
     *
     * @return array[]
     */
    public function getAll(): array
    {
        $sections = array_filter(
            $this->sections,
            fn(array $s) => $this->checkCapability($s['capability'])
        );

        uasort($sections, fn(array $a, array $b) => $a['priority'] <=> $b['priority']);

        return array_values($sections);
    }

    /**
     * Gibt Menüeinträge zurück (bereit für getMemberMenuItems-Filter).
     */
    public function getMenuItems(string $currentSlug = ''): array
    {
        $items = [];
        foreach ($this->getAll() as $section) {
            $items[] = [
                'slug'     => 'plugin_' . $section['slug'],
                'label'    => $section['label'],
                'icon'     => $section['icon'],
                'url'      => '/member/plugin/' . $section['slug'],
                'active'   => $currentSlug === $section['slug'],
                'category' => $section['category'],
            ];
        }
        return $items;
    }

    /**
     * Gibt Dashboard-Widgets für alle sichtbaren Plugin-Bereiche zurück.
     * Ist `dashboard_widget` null, wird dennoch ein Standard-Widget erzeugt.
     */
    public function getDashboardWidgets(object $user): array
    {
        $widgets = [];
        $isAdmin = Auth::instance()->isAdmin();

        foreach ($this->getAll() as $section) {
            $wConfig = $section['dashboard_widget'] ?? [];

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
                'icon'        => $wConfig['icon'] ?? $section['icon'],
                'title'       => $wConfig['title'] ?? $section['label'],
                'description' => $wConfig['description'] ?? '',
                'color'       => $wConfig['color'] ?? '#4f46e5',
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

        // POST-Handling zuerst
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_callable($section['post_callback'])) {
            call_user_func($section['post_callback'], $user, $params);
            return;
        }

        // Page rendern
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
