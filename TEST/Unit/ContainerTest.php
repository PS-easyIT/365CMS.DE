<?php

declare(strict_types=1);

namespace CMS\Tests\Unit;

use CMS\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit-Tests für den Dependency-Injection-Container
 *
 * Testet bind/singleton/bindInstance/make/has/forget/flush.
 */
class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        // Frische Container-Instanz via flush() für isolierte Tests
        $this->container = Container::instance();
        $this->container->flush();
    }

    protected function tearDown(): void
    {
        $this->container->flush();
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

        $this->container->flush();

        $this->assertFalse($this->container->has('a'));
        $this->assertFalse($this->container->has('b'));
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
    }
}
