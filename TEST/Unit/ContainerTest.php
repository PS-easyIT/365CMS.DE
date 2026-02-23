<?php
<<<<<<< HEAD
=======
/**
 * Container Unit Tests
 *
 * Testet den DI-Container (bind, singleton, bindInstance, make, has, forget).
 *
 * @package CMS\Tests\Unit
 */
>>>>>>> 99c076b264547ca37d9fb41c77632a2247e7247a

declare(strict_types=1);

namespace CMS\Tests\Unit;

<<<<<<< HEAD
use CMS\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit-Tests für den Dependency-Injection-Container
 *
 * Testet bind/singleton/bindInstance/make/has/forget/flush.
 */
=======
use PHPUnit\Framework\TestCase;
use CMS\Container;
use ReflectionClass;

>>>>>>> 99c076b264547ca37d9fb41c77632a2247e7247a
class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
<<<<<<< HEAD
        // Frische Container-Instanz via flush() für isolierte Tests
        $this->container = Container::instance();
        $this->container->flush();
=======
        // Frische Container-Instanz für jeden Test
        $ref  = new ReflectionClass(Container::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $this->container = Container::instance();
>>>>>>> 99c076b264547ca37d9fb41c77632a2247e7247a
    }

    protected function tearDown(): void
    {
        $this->container->flush();
<<<<<<< HEAD
    }

    // ──────────────────────────────────────────────────────────────────────
    // Singleton
    // ──────────────────────────────────────────────────────────────────────

    public function testInstanceReturnsSameObject(): void
    {
        $a = Container::instance();
        $b = Container::instance();
        $this->assertSame($a, $b);
    }

    // ──────────────────────────────────────────────────────────────────────
    // bind
    // ──────────────────────────────────────────────────────────────────────

    public function testBindAndMakeCreatesNewInstanceEachTime(): void
    {
        $this->container->bind('counter', fn() => new \stdClass());

        $a = $this->container->make('counter');
        $b = $this->container->make('counter');

        $this->assertInstanceOf(\stdClass::class, $a);
        $this->assertNotSame($a, $b, 'bind() muss bei jedem make() eine neue Instanz erzeugen');
    }

    public function testBindOverwritesPreviousBinding(): void
    {
        $this->container->bind('service', fn() => 'first');
        $this->container->bind('service', fn() => 'second');
        $this->assertEquals('second', $this->container->make('service'));
    }

    // ──────────────────────────────────────────────────────────────────────
    // singleton
    // ──────────────────────────────────────────────────────────────────────

    public function testSingletonReturnsSameInstanceEachTime(): void
    {
        $this->container->singleton('myService', function () {
            $obj = new \stdClass();
            $obj->id = uniqid('', true);
            return $obj;
        });

        $a = $this->container->make('myService');
        $b = $this->container->make('myService');

        $this->assertSame($a, $b, 'singleton() muss bei jedem make() dieselbe Instanz zurückgeben');
    }

    // ──────────────────────────────────────────────────────────────────────
    // bindInstance
    // ──────────────────────────────────────────────────────────────────────

    public function testBindInstanceReturnsExactObject(): void
    {
        $obj = new \stdClass();
        $obj->value = 42;
        $this->container->bindInstance('myObj', $obj);

        $resolved = $this->container->make('myObj');
        $this->assertSame($obj, $resolved);
        $this->assertEquals(42, $resolved->value);
    }

    // ──────────────────────────────────────────────────────────────────────
    // has
    // ──────────────────────────────────────────────────────────────────────

    public function testHasReturnsTrueAfterBind(): void
    {
        $this->container->bind('exists', fn() => true);
        $this->assertTrue($this->container->has('exists'));
    }

    public function testHasReturnsFalseForUnknownAbstract(): void
    {
        $this->assertFalse($this->container->has('unknown_service_xyz'));
    }

    // ──────────────────────────────────────────────────────────────────────
    // forget
    // ──────────────────────────────────────────────────────────────────────

    public function testForgetRemovesBinding(): void
    {
        $this->container->bind('tmpService', fn() => 'value');
        $this->assertTrue($this->container->has('tmpService'));

        $this->container->forget('tmpService');
        $this->assertFalse($this->container->has('tmpService'));
    }

    // ──────────────────────────────────────────────────────────────────────
    // flush
    // ──────────────────────────────────────────────────────────────────────

    public function testFlushClearsAllBindings(): void
    {
        $this->container->bind('a', fn() => 'a');
        $this->container->bind('b', fn() => 'b');
        $this->container->singleton('c', fn() => 'c');
=======

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
>>>>>>> 99c076b264547ca37d9fb41c77632a2247e7247a

        $this->container->flush();

        $this->assertFalse($this->container->has('a'));
        $this->assertFalse($this->container->has('b'));
<<<<<<< HEAD
        $this->assertFalse($this->container->has('c'));
    }

    // ──────────────────────────────────────────────────────────────────────
    // make – Fehlerfälle
    // ──────────────────────────────────────────────────────────────────────

    public function testMakeThrowsForUnknownAbstract(): void
    {
        $this->expectException(RuntimeException::class);
        $this->container->make('does_not_exist');
    }

    // ──────────────────────────────────────────────────────────────────────
    // registered
    // ──────────────────────────────────────────────────────────────────────

    public function testRegisteredListsAllBoundKeys(): void
    {
        $this->container->bind('alpha', fn() => 1);
        $this->container->singleton('beta', fn() => 2);

        $registered = $this->container->registered();
        $this->assertContains('alpha', $registered);
        $this->assertContains('beta', $registered);
    }

    // ──────────────────────────────────────────────────────────────────────
    // get (PSR-11-Alias für make)
    // ──────────────────────────────────────────────────────────────────────

    public function testGetBehavesLikeMake(): void
    {
        $this->container->bind('greet', fn() => 'hello');
        $this->assertEquals('hello', $this->container->get('greet'));
=======
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
>>>>>>> 99c076b264547ca37d9fb41c77632a2247e7247a
    }
}
