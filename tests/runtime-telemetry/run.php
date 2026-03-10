<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use CMS\Debug;
use CMS\Services\SystemService;

/**
 * @throws RuntimeException
 */
function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/**
 * @throws RuntimeException
 */
function assertSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' (erwartet ' . var_export($expected, true) . ', bekam ' . var_export($actual, true) . ')');
    }
}

$tests = [
    'Runtime-Telemetrie sammelt Messpunkte und Queries im Debug-Modus' => static function (): void {
        Debug::enable(true);
        Debug::resetRuntimeProfile();
        Debug::checkpoint('bootstrap.ready', ['mode' => 'test']);
        Debug::query('SELECT 1', [1], 0.015);
        Debug::checkpoint('router.dispatch.start', ['uri' => '/test']);

        $telemetry = Debug::getRuntimeTelemetry();

        assertTrue(($telemetry['enabled'] ?? false) === true, 'Debug-Telemetrie wurde nicht aktiviert.');
        assertSame(1, (int)($telemetry['query']['count'] ?? 0), 'Query-Zähler stimmt nicht.');
        assertSame(2, count($telemetry['checkpoints'] ?? []), 'Checkpoint-Anzahl stimmt nicht.');
        assertTrue((float)($telemetry['query']['total_time_ms'] ?? 0) >= 15.0, 'Query-Gesamtzeit wurde nicht in Millisekunden erfasst.');
    },
    'SystemService liefert Debug-Telemetrie als Snapshot zurück' => static function (): void {
        $reflection = new ReflectionClass(SystemService::class);
        $service = $reflection->newInstanceWithoutConstructor();

        $telemetry = $service->getRuntimeTelemetry();

        assertTrue(array_key_exists('enabled', $telemetry), 'SystemService-Telemetrie enthält keinen Enabled-Status.');
        assertTrue(array_key_exists('query', $telemetry), 'SystemService-Telemetrie enthält keine Query-Daten.');
    },
];

$output = [];
$failures = [];
foreach ($tests as $label => $test) {
    try {
        $test();
        $output[] = "[PASS] {$label}";
    } catch (Throwable $e) {
        $failures[] = "[FAIL] {$label}: {$e->getMessage()}";
        $output[] = end($failures);
    }
}

foreach ($output as $line) {
    echo $line . PHP_EOL;
}

if ($failures !== []) {
    exit(1);
}

echo 'Alle Runtime-/Query-Telemetrie-Regressionschecks erfolgreich.' . PHP_EOL;
