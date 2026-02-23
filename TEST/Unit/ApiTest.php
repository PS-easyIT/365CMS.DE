<?php

declare(strict_types=1);

namespace CMS\Tests\Unit;

use CMS\Api;
use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für die Api-Klasse
 *
 * handleRequest() erfordert eine Datenbank-Verbindung; hier werden daher
 * nur framework-nahe Aspekte (Singleton, Instanz-Typ) geprüft.
 * End-to-End-API-Tests sind in Integration/ApiFlowTest.php.
 */
class ApiTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────
    // Singleton
    // ──────────────────────────────────────────────────────────────────────

    public function testInstanceReturnsSameObject(): void
    {
        $a = Api::instance();
        $b = Api::instance();
        $this->assertSame($a, $b, 'Api::instance() muss denselben Singleton zurückgeben');
    }

    public function testInstanceIsOfTypeApi(): void
    {
        $this->assertInstanceOf(Api::class, Api::instance());
    }

    // ──────────────────────────────────────────────────────────────────────
    // handleRequest – Absicherung gegen ungültige Endpunkte
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Ein unbekannter Endpunkt darf keinen Fatal-Error/-Exception verursachen.
     * handleRequest() soll intern JSON-Error (404/400) ausgeben und beenden.
     * Im Test-Kontext fangen wir die ausgegebene JSON-Antwort per Output-Buffer auf.
     */
    public function testHandleRequestUnknownEndpointOutputsJson(): void
    {
        $api = Api::instance();

        ob_start();
        try {
            $api->handleRequest('unknown_nonexistent_endpoint_xyz');
        } catch (\Throwable $e) {
            ob_end_clean();
            // Eine Exception hier ist ein Fehler: handleRequest() sollte nie werfen
            $this->fail('handleRequest() darf keine Exception werfen: ' . $e->getMessage());
        }
        $output = ob_get_clean();

        if (!empty($output)) {
            $decoded = json_decode($output, true);
            $this->assertIsArray($decoded, 'Antwort muss gültiges JSON sein');
            $this->assertArrayHasKey('success', $decoded);
            $this->assertFalse($decoded['success'], 'Unbekannter Endpunkt muss success:false zurückgeben');
        } else {
            // Kein Output – handleRequest hat exit() aufgerufen oder Output über andere Methode
            $this->markTestIncomplete('handleRequest() produzierte keinen Output (verwendet exit())');
        }
    }

    public function testHandleRequestWithNullIdDoesNotThrow(): void
    {
        $api = Api::instance();

        ob_start();
        try {
            $api->handleRequest('status', null);
            ob_end_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            $this->fail('handleRequest() mit null-ID darf keine Exception werfen: ' . $e->getMessage());
        }

        $this->addToAssertionCount(1); // Test bestanden wenn kein Exception
    }

    // ──────────────────────────────────────────────────────────────────────
    // Endpunkt-Namen – Sonderzeichen dürfen keinen Error erzeugen
    // ──────────────────────────────────────────────────────────────────────

    /**
     * @dataProvider invalidEndpointProvider
     */
    public function testHandleRequestWithSpecialCharsDoesNotThrow(string $endpoint): void
    {
        $api = Api::instance();
        ob_start();
        try {
            $api->handleRequest($endpoint);
        } catch (\Throwable $e) {
            ob_end_clean();
            $this->fail("handleRequest('{$endpoint}') warf Exception: " . $e->getMessage());
        }
        ob_end_clean();
        $this->addToAssertionCount(1);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidEndpointProvider(): array
    {
        return [
            'leer'              => [''],
            'Sonderzeichen'     => ['../../etc/passwd'],
            'SQL-Injection'     => ["'; DROP TABLE users; --"],
            'XSS'               => ['<script>alert(1)</script>'],
            'sehr lang'         => [str_repeat('a', 1000)],
        ];
    }
}
