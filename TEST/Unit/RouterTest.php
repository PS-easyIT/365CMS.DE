<?php

declare(strict_types=1);

namespace CMS\Tests\Unit;

use CMS\Router;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit-Tests für den Router
 *
 * Da dispatch() auf $_SERVER und Sessions aufbaut, werden nur
 * unit-testbare Teile geprüft: addRoute(), Route-Registrierung
 * und das Singleton-Verhalten.
 * End-to-End-Routing-Tests sind unter Integration/ abgedeckt.
 */
class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = Router::instance();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Singleton
    // ──────────────────────────────────────────────────────────────────────

    public function testInstanceReturnsSameObject(): void
    {
        $a = Router::instance();
        $b = Router::instance();
        $this->assertSame($a, $b);
    }

    // ──────────────────────────────────────────────────────────────────────
    // addRoute – kein Exception bei gültigen Parametern
    // ──────────────────────────────────────────────────────────────────────

    public function testAddRouteDoesNotThrowForGetMethod(): void
    {
        $this->expectNotToPerformAssertions();
        $this->router->addRoute('GET', '/test-path', function () {
            return 'ok';
        });
    }

    public function testAddRouteDoesNotThrowForPostMethod(): void
    {
        $this->expectNotToPerformAssertions();
        $this->router->addRoute('POST', '/test-submit', function () {
            return 'submitted';
        });
    }

    public function testAddRouteAcceptsCallableArray(): void
    {
        $this->expectNotToPerformAssertions();
        $this->router->addRoute('GET', '/callable-test', [RouterDummyController::class, 'handle']);
    }

    public function testAddMultipleRoutesDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        for ($i = 0; $i < 10; $i++) {
            $this->router->addRoute('GET', "/route-{$i}", fn() => $i);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Hilfsmethoden – können ohne HTTP-Context aufgerufen werden
    // ──────────────────────────────────────────────────────────────────────

    public function testRouterIsInstanceOfRouter(): void
    {
        $this->assertInstanceOf(Router::class, $this->router);
    }
}

/**
 * Dummy-Controller für callable-Array-Tests
 *
 * @internal
 */
class RouterDummyController
{
    public static function handle(): string
    {
        return 'handled';
    }
}
