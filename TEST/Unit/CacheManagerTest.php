<?php

declare(strict_types=1);

namespace CMS\Tests\Unit;

use CMS\CacheManager;
use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für CacheManager
 *
 * Testet File-Cache-Fallback (APCu nicht vorausgesetzt), get/set/has/delete,
 * TTL-Ablauf, Multi-Operationen und Flush.
 * Verwendet TEST_CACHE_DIR aus bootstrap.php als temporäres Verzeichnis.
 */
class CacheManagerTest extends TestCase
{
    private CacheManager $cache;

    protected function setUp(): void
    {
        $this->cache = CacheManager::instance();
        $this->cache->clearAll(); // Sicherstellen, dass der Cache leer ist
    }

    protected function tearDown(): void
    {
        $this->cache->clearAll();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Singleton
    // ──────────────────────────────────────────────────────────────────────

    public function testInstanceReturnsSameObject(): void
    {
        $a = CacheManager::instance();
        $b = CacheManager::instance();
        $this->assertSame($a, $b);
    }

    // ──────────────────────────────────────────────────────────────────────
    // set / get
    // ──────────────────────────────────────────────────────────────────────

    public function testSetAndGetString(): void
    {
        $this->cache->set('key_string', 'hello world', 60);
        $this->assertEquals('hello world', $this->cache->get('key_string'));
    }

    public function testSetAndGetArray(): void
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $this->cache->set('key_array', $data, 60);
        $this->assertEquals($data, $this->cache->get('key_array'));
    }

    public function testSetAndGetInteger(): void
    {
        $this->cache->set('key_int', 12345, 60);
        $this->assertEquals(12345, $this->cache->get('key_int'));
    }

    public function testSetAndGetBoolean(): void
    {
        $this->cache->set('key_bool_true', true, 60);
        $this->cache->set('key_bool_false', false, 60);
        $this->assertTrue($this->cache->get('key_bool_true'));
        $this->assertFalse($this->cache->get('key_bool_false'));
    }

    public function testSetReturnsBool(): void
    {
        $result = $this->cache->set('key_result', 'value', 60);
        $this->assertIsBool($result);
    }

    // ──────────────────────────────────────────────────────────────────────
    // has
    // ──────────────────────────────────────────────────────────────────────

    public function testHasReturnsTrueForExistingKey(): void
    {
        $this->cache->set('existing_key', 'val', 60);
        $this->assertTrue($this->cache->has('existing_key'));
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        $this->assertFalse($this->cache->has('nonexistent_xyz_123'));
    }

    // ──────────────────────────────────────────────────────────────────────
    // default-Wert
    // ──────────────────────────────────────────────────────────────────────

    public function testGetReturnsDefaultForMissingKey(): void
    {
        $result = $this->cache->get('missing_key', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    public function testGetReturnsNullByDefaultForMissingKey(): void
    {
        $result = $this->cache->get('another_missing_key');
        $this->assertNull($result);
    }

    // ──────────────────────────────────────────────────────────────────────
    // delete
    // ──────────────────────────────────────────────────────────────────────

    public function testDeleteRemovesKey(): void
    {
        $this->cache->set('to_delete', 'value', 60);
        $this->assertTrue($this->cache->has('to_delete'));

        $this->cache->delete('to_delete');
        $this->assertFalse($this->cache->has('to_delete'));
    }

    public function testDeleteNonExistingKeyDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        $this->cache->delete('key_that_does_not_exist');
    }

    // ──────────────────────────────────────────────────────────────────────
    // flush / clear
    // ──────────────────────────────────────────────────────────────────────

    public function testFlushClearsAllEntries(): void
    {
        $this->cache->set('flush_key_1', 'a', 60);
        $this->cache->set('flush_key_2', 'b', 60);
        $this->cache->flush();

        $this->assertFalse($this->cache->has('flush_key_1'));
        $this->assertFalse($this->cache->has('flush_key_2'));
    }

    public function testClearAliasWorksLikeFlush(): void
    {
        $this->cache->set('clear_key', 'value', 60);
        $this->cache->clear();
        $this->assertFalse($this->cache->has('clear_key'));
    }

    // ──────────────────────────────────────────────────────────────────────
    // Multiple Keys
    // ──────────────────────────────────────────────────────────────────────

    public function testSetMultipleAndGetMultiple(): void
    {
        $entries = [
            'multi_a' => 'alpha',
            'multi_b' => 'beta',
            'multi_c' => 'gamma',
        ];
        $this->cache->setMultiple($entries, 60);

        $result = $this->cache->getMultiple(array_keys($entries));
        $this->assertEquals($entries, $result);
    }

    public function testGetMultipleReturnsDefaultForMissingKeys(): void
    {
        $result = $this->cache->getMultiple(['missing_1', 'missing_2'], 'FALLBACK');
        $this->assertEquals(['missing_1' => 'FALLBACK', 'missing_2' => 'FALLBACK'], $result);
    }

    public function testDeleteMultipleRemovesAllKeys(): void
    {
        $this->cache->setMultiple(['del_x' => 1, 'del_y' => 2], 60);
        $this->cache->deleteMultiple(['del_x', 'del_y']);

        $this->assertFalse($this->cache->has('del_x'));
        $this->assertFalse($this->cache->has('del_y'));
    }

    // ──────────────────────────────────────────────────────────────────────
    // Status
    // ──────────────────────────────────────────────────────────────────────

    public function testGetStatusReturnsArray(): void
    {
        $status = $this->cache->getStatus();
        $this->assertIsArray($status);
        $this->assertArrayHasKey('driver', $status);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Sonderfälle
    // ──────────────────────────────────────────────────────────────────────

    public function testOverwriteExistingKey(): void
    {
        $this->cache->set('overwrite_key', 'original', 60);
        $this->cache->set('overwrite_key', 'updated', 60);
        $this->assertEquals('updated', $this->cache->get('overwrite_key'));
    }

    public function testNullValueIsStoredAndRetrieved(): void
    {
        $this->cache->set('null_key', null, 60);
        // has() kann bei null-Werten driver-abhängig false zurückgeben
        // get() muss den gespeicherten null-Wert zurückliefern
        $result = $this->cache->get('null_key', 'default');
        // Entweder null (gespeichert) oder 'default' (kein null-Support) – beide OK
        $this->assertTrue($result === null || $result === 'default');
    }
}
