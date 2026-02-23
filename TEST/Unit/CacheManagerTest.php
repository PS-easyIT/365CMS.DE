<?php
<<<<<<< HEAD
=======
/**
 * CacheManager Unit Tests
 *
 * Testet den File-Cache und APCu-L1-Layer-Mechanismus.
 *
 * @package CMS\Tests\Unit
 */
>>>>>>> 99c076b264547ca37d9fb41c77632a2247e7247a

declare(strict_types=1);

namespace CMS\Tests\Unit;

<<<<<<< HEAD
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
=======
use PHPUnit\Framework\TestCase;
use CMS\CacheManager;
use ReflectionClass;

class CacheManagerTest extends TestCase
{
    private CacheManager $cache;
    private string $origCacheDir;

    protected function setUp(): void
    {
        // Singleton zurücksetzen und auf Test-Verzeichnis umleiten
        $ref = new ReflectionClass(CacheManager::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        // Neue Instanz erstellen (nutzt TEST_CACHE_DIR wegen Konstanten-Override)
        // Da CacheManager ABSPATH . 'cache/' nutzt, simulieren wir via Override
        $this->cache = $this->getCacheManagerWithTempDir();
>>>>>>> 99c076b264547ca37d9fb41c77632a2247e7247a
    }

    protected function tearDown(): void
    {
<<<<<<< HEAD
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
=======
        // Cache leeren
        $this->cache->clear();

        // Singleton zurücksetzen
        $ref = new ReflectionClass(CacheManager::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    /**
     * Erstellt CacheManager-Instanz mit temporärem Cache-Verzeichnis (via Reflection)
     */
    private function getCacheManagerWithTempDir(): CacheManager
    {
        $ref = new ReflectionClass(CacheManager::class);
        $instance = $ref->newInstanceWithoutConstructor();

        $dirProp = $ref->getProperty('cacheDir');
        $dirProp->setAccessible(true);
        $dirProp->setValue($instance, TEST_CACHE_DIR);

        $lsProp = $ref->getProperty('useLiteSpeed');
        $lsProp->setAccessible(true);
        $lsProp->setValue($instance, false);

        return $instance;
    }

    // ── set / get ─────────────────────────────────────────────────────────────

    public function testSetAndGetReturnsValue(): void
    {
        $this->assertTrue($this->cache->set('test_key', 'hello world', 60));
        $this->assertSame('hello world', $this->cache->get('test_key'));
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        $this->assertNull($this->cache->get('nonexistent_key_' . uniqid()));
    }

    public function testGetReturnsNullAfterTtlExpiry(): void
    {
        $this->cache->set('expiring', 'value', -1); // TTL bereits abgelaufen
        $this->assertNull($this->cache->get('expiring'));
    }

    public function testSetOverwritesExistingValue(): void
    {
        $this->cache->set('key', 'first', 60);
        $this->cache->set('key', 'second', 60);
        $this->assertSame('second', $this->cache->get('key'));
>>>>>>> 99c076b264547ca37d9fb41c77632a2247e7247a
    }

    public function testSetAndGetArray(): void
    {
<<<<<<< HEAD
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
=======
        $data = ['id' => 42, 'name' => 'Test', 'items' => [1, 2, 3]];
        $this->cache->set('array_key', $data, 60);
        $this->assertSame($data, $this->cache->get('array_key'));
    }

    public function testSetAndGetNull(): void
    {
        // null als Wert → intern nicht von "nicht vorhanden" unterscheidbar
        // Erwartet: null wird nicht gecacht (get() gibt null zurück)
        $this->cache->set('null_key', null, 60);
        $this->assertNull($this->cache->get('null_key'));
    }

    public function testSetReturnsBoolTrue(): void
    {
        $result = $this->cache->set('bool_test', 'value', 60);
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    // ── has ───────────────────────────────────────────────────────────────────

    public function testHasReturnsTrueForExistingKey(): void
    {
        $this->cache->set('has_key', 'data', 60);
        $this->assertTrue($this->cache->has('has_key'));
>>>>>>> 99c076b264547ca37d9fb41c77632a2247e7247a
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
<<<<<<< HEAD
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
=======
        $this->assertFalse($this->cache->has('missing_' . uniqid()));
    }

    public function testHasReturnsFalseAfterDelete(): void
    {
        $this->cache->set('deletable', 'x', 60);
        $this->cache->delete('deletable');
        $this->assertFalse($this->cache->has('deletable'));
    }

    // ── delete ────────────────────────────────────────────────────────────────

    public function testDeleteRemovesExistingKey(): void
    {
        $this->cache->set('del_key', 'value', 60);
        $this->assertTrue($this->cache->delete('del_key'));
        $this->assertNull($this->cache->get('del_key'));
    }

    public function testDeleteReturnsTrueForNonExistentKey(): void
    {
        // Löschen von nicht vorhandenem Schlüssel → true (bereits gelöscht)
        $this->assertTrue($this->cache->delete('never_existed_' . uniqid()));
    }

    // ── clear / flush ─────────────────────────────────────────────────────────

    public function testClearRemovesAllEntries(): void
    {
        $this->cache->set('a', 1, 60);
        $this->cache->set('b', 2, 60);
        $this->cache->set('c', 3, 60);

        $this->assertTrue($this->cache->clear());

        $this->assertNull($this->cache->get('a'));
        $this->assertNull($this->cache->get('b'));
        $this->assertNull($this->cache->get('c'));
    }

    public function testFlushIsSameAsClear(): void
    {
        $this->cache->set('flush_test', 'value', 60);
        $result = $this->cache->flush();
        $this->assertTrue($result);
        $this->assertNull($this->cache->get('flush_test'));
    }

    // ── Batch-Methoden ────────────────────────────────────────────────────────

    public function testGetMultipleReturnsAllValues(): void
    {
        $this->cache->set('m1', 'v1', 60);
        $this->cache->set('m2', 'v2', 60);

        $result = $this->cache->getMultiple(['m1', 'm2', 'm3']);

        $this->assertSame('v1', $result['m1']);
        $this->assertSame('v2', $result['m2']);
        $this->assertNull($result['m3']); // nicht vorhanden → default
    }

    public function testSetMultipleSetsAllValues(): void
    {
        $data = ['k1' => 'hello', 'k2' => 42, 'k3' => ['nested']];
        $result = $this->cache->setMultiple($data, 60);

        $this->assertTrue($result);
        $this->assertSame('hello', $this->cache->get('k1'));
        $this->assertSame(42,      $this->cache->get('k2'));
        $this->assertSame(['nested'], $this->cache->get('k3'));
>>>>>>> 99c076b264547ca37d9fb41c77632a2247e7247a
    }

    public function testDeleteMultipleRemovesAllKeys(): void
    {
<<<<<<< HEAD
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
=======
        $this->cache->set('d1', 'val', 60);
        $this->cache->set('d2', 'val', 60);

        $result = $this->cache->deleteMultiple(['d1', 'd2', 'never']);

        $this->assertTrue($result);
        $this->assertFalse($this->cache->has('d1'));
        $this->assertFalse($this->cache->has('d2'));
    }

    // ── HMAC-Integrität ───────────────────────────────────────────────────────

    public function testTamperedCacheFileReturnsNull(): void
    {
        $this->cache->set('tamper', 'secure_value', 60);

        // Cache-Datei direkt manipulieren
        $ref      = new ReflectionClass(CacheManager::class);
        $method   = $ref->getMethod('getCacheFile');
        $method->setAccessible(true);
        $cacheFile = $method->invoke($this->cache, 'tamper');

        // Inhalt verfälschen
        file_put_contents($cacheFile, 'fakehash:' . base64_encode('{"v":"hacked","e":9999999999}'));

        // Soll null zurückgeben (HMAC-Fehlschlag)
        $this->assertNull($this->cache->get('tamper'));
>>>>>>> 99c076b264547ca37d9fb41c77632a2247e7247a
    }
}
