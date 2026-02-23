<?php

declare(strict_types=1);

namespace CMS\Tests\Unit;

use CMS\PluginManager;
use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für PluginManager
 *
 * Testet Singleton, getAvailablePlugins(), getActivePlugins() und
 * activatePlugin()/deactivatePlugin() mit ungültigen Slugs (ohne echte Dateien).
 * Vollständige Lifecycle-Tests (Dateisystem) sind in Integration/.
 */
class PluginManagerTest extends TestCase
{
    private PluginManager $pm;

    protected function setUp(): void
    {
        $this->pm = PluginManager::instance();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Singleton
    // ──────────────────────────────────────────────────────────────────────

    public function testInstanceReturnsSameObject(): void
    {
        $a = PluginManager::instance();
        $b = PluginManager::instance();
        $this->assertSame($a, $b);
    }

    // ──────────────────────────────────────────────────────────────────────
    // getAvailablePlugins
    // ──────────────────────────────────────────────────────────────────────

    public function testGetAvailablePluginsReturnsArray(): void
    {
        $plugins = $this->pm->getAvailablePlugins();
        $this->assertIsArray($plugins);
    }

    public function testAvailablePluginsHaveRequiredKeys(): void
    {
        $plugins = $this->pm->getAvailablePlugins();
        foreach ($plugins as $slug => $plugin) {
            $this->assertIsString($slug, 'Plugin-Keys müssen Strings (Slug) sein');
            $this->assertIsArray($plugin, "Plugin-Daten für '{$slug}' müssen ein Array sein");
            // Mindest-Required-Keys aus index.json / Plugin-Header
            $this->assertArrayHasKey('name', $plugin, "Plugin '{$slug}' muss 'name' enthalten");
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // getActivePlugins
    // ──────────────────────────────────────────────────────────────────────

    public function testGetActivePluginsReturnsArray(): void
    {
        $active = $this->pm->getActivePlugins();
        $this->assertIsArray($active);
    }

    public function testGetActivePluginsIsSubsetOfAvailable(): void
    {
        $available = array_keys($this->pm->getAvailablePlugins());
        $active    = $this->pm->getActivePlugins();

        foreach ($active as $slug) {
            $this->assertContains(
                $slug,
                $available,
                "Aktives Plugin '{$slug}' muss auch in getAvailablePlugins() vorkommen"
            );
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // activatePlugin – Fehlerfälle ohne Dateisystem
    // ──────────────────────────────────────────────────────────────────────

    public function testActivateNonExistingPluginReturnsErrorString(): void
    {
        $result = $this->pm->activatePlugin('nonexistent_plugin_xyz_12345');
        $this->assertIsString($result, 'Bei nicht vorhandenem Plugin muss eine Fehlermeldung zurückgegeben werden');
        $this->assertNotTrue($result);
    }

    public function testActivateEmptySlugReturnsError(): void
    {
        $result = $this->pm->activatePlugin('');
        // Leerer Slug: Fehler-String oder false
        $this->assertNotTrue($result, 'Leerer Slug darf nicht als aktiviert gelten');
    }

    public function testActivateSlugWithPathTraversalReturnsError(): void
    {
        $result = $this->pm->activatePlugin('../../../etc/passwd');
        $this->assertNotTrue($result, 'Path-Traversal-Slug muss abgelehnt werden');
    }

    // ──────────────────────────────────────────────────────────────────────
    // deactivatePlugin – Fehlerfälle
    // ──────────────────────────────────────────────────────────────────────

    public function testDeactivateNonActivePluginReturnsError(): void
    {
        $result = $this->pm->deactivatePlugin('nonexistent_plugin_xyz_12345');
        $this->assertNotTrue($result, 'Deaktivierung nicht-existenter Plugins muss Fehler zurückgeben');
    }

    // ──────────────────────────────────────────────────────────────────────
    // loadPlugins – darf keine Exception werfen
    // ──────────────────────────────────────────────────────────────────────

    public function testLoadPluginsDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        try {
            $this->pm->loadPlugins();
        } catch (\Throwable $e) {
            $this->fail('loadPlugins() darf keine Exception werfen: ' . $e->getMessage());
        }
    }
}
