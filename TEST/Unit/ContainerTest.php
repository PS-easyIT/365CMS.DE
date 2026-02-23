<?php
/**
 * Container Unit Tests
 *
 * Testet den DI-Container (bind, singleton, bindInstance, make, has, forget).
 *
 * @package CMS\Tests\Unit
 */

declare(strict_types=1);

namespace CMS\Tests\Unit;

use PHPUnit\Framework\TestCase;
use CMS\Container;
use ReflectionClass;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        // Frische Container-Instanz für jeden Test
        $ref  = new ReflectionClass(Container::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $this->container = Container::instance();
    }

    protected function tearDown(): void
    {
        $this->container->flush();

        $ref  = new ReflectionClass(Container::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    // ── bindInstance / get ────────────────────────────────────────────────────

    public function testBindInstanceAndGet(): void
    {
        $obj = new \stdClass();
        $obj->value = 'test';

        $this->container->bindInstance('my_service', $obj);

        $resolved = $this->container->get('my_service');
        $this->assertSame($obj, $resolved);
    }

    public function testBindInstanceReturnsSameObjectEachTime(): void
    {
        $obj = new \stdClass();
        $this->container->bindInstance('singleton_test', $obj);

        $a = $this->container->get('singleton_test');
        $b = $this->container->get('singleton_test');

        $this->assertSame($a, $b);
        $this->assertSame($obj, $a);
    }

    // ── has ───────────────────────────────────────────────────────────────────

    public function testHasReturnsTrueAfterBind(): void
    {
        $this->container->bindInstance('existing', new \stdClass());
        $this->assertTrue($this->container->has('existing'));
    }

    public function testHasReturnsFalseForUnknown(): void
    {
        $this->assertFalse($this->container->has('unknown_' . uniqid()));
    }

    // ── bind + make ───────────────────────────────────────────────────────────

    public function testBindAndMakeCallsFactory(): void
    {
        $callCount = 0;
        $this->container->bind('counter', static function () use (&$callCount): \stdClass {
            $callCount++;
            $obj = new \stdClass();
            $obj->id = $callCount;
            return $obj;
        });

        $first  = $this->container->make('counter');
        $second = $this->container->make('counter');

        // bind (nicht singleton) → neue Instanz pro make()
        $this->assertSame(2, $callCount);
        $this->assertNotSame($first, $second);
        $this->assertSame(1, $first->id);
        $this->assertSame(2, $second->id);
    }

    // ── singleton ─────────────────────────────────────────────────────────────

    public function testSingletonReturnsSameInstance(): void
    {
        $callCount = 0;
        $this->container->singleton('logger', static function () use (&$callCount): \stdClass {
            $callCount++;
            return new \stdClass();
        });

        $a = $this->container->make('logger');
        $b = $this->container->make('logger');

        $this->assertSame(1, $callCount, 'Factory darf für Singleton nur 1× aufgerufen werden');
        $this->assertSame($a, $b);
    }

    // ── forget ────────────────────────────────────────────────────────────────

    public function testForgetRemovesBinding(): void
    {
        $this->container->bindInstance('temp', new \stdClass());
        $this->assertTrue($this->container->has('temp'));

        $this->container->forget('temp');
        $this->assertFalse($this->container->has('temp'));
    }

    // ── flush ─────────────────────────────────────────────────────────────────

    public function testFlushClearsAllBindings(): void
    {
        $this->container->bindInstance('a', new \stdClass());
        $this->container->bindInstance('b', new \stdClass());

        $this->container->flush();

        $this->assertFalse($this->container->has('a'));
        $this->assertFalse($this->container->has('b'));
    }

    // ── registered ────────────────────────────────────────────────────────────

    public function testRegisteredReturnsAllKeys(): void
    {
        $this->container->bindInstance('svc1', new \stdClass());
        $this->container->bindInstance('svc2', new \stdClass());

        $keys = $this->container->registered();

        $this->assertContains('svc1', $keys);
        $this->assertContains('svc2', $keys);
    }

    // ── Fehlerfall: unbekanntes Binding ────────────────────────────────────────

    public function testMakeUnknownAbstractReturnsNull(): void
    {
        $result = $this->container->make('not_registered_' . uniqid());
        $this->assertNull($result);
    }

    public function testGetUnknownAbstractReturnsNull(): void
    {
        $result = $this->container->get('also_not_registered_' . uniqid());
        $this->assertNull($result);
    }
}
