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
        Debug::resetRuntimeProfile(['mode' => 'test']);
        Debug::checkpoint('bootstrap.start', ['mode' => 'test']);
        Debug::checkpoint('bootstrap.ready', ['mode' => 'test']);
        Debug::query('SELECT 1', [1], 0.015);
        Debug::checkpoint('router.dispatch.start', ['uri' => '/test']);

        $telemetry = Debug::getRuntimeTelemetry();

        assertTrue(($telemetry['enabled'] ?? false) === true, 'Debug-Telemetrie wurde nicht aktiviert.');
        assertSame(1, (int)($telemetry['query']['count'] ?? 0), 'Query-Zähler stimmt nicht.');
        assertSame(3, count($telemetry['checkpoints'] ?? []), 'Checkpoint-Anzahl stimmt nicht.');
        assertTrue((float)($telemetry['query']['total_time_ms'] ?? 0) >= 15.0, 'Query-Gesamtzeit wurde nicht in Millisekunden erfasst.');
        assertSame('test', (string)($telemetry['bootstrap']['mode'] ?? ''), 'Bootstrap-Modus wurde nicht aus dem Profil übernommen.');
        assertTrue((float)($telemetry['bootstrap']['bootstrap_ready_ms'] ?? 0.0) >= 0.0, 'Bootstrap-Ready-Zeit fehlt.');
        assertTrue(count($telemetry['bootstrap']['phases'] ?? []) >= 1, 'Bootstrap-Phasen wurden nicht verdichtet.');
    },
    'Bootstrap-Profil bleibt auch ohne Debug-Modus als leichtgewichtige Messung aktiv' => static function (): void {
        Debug::enable(false);
        Debug::resetRuntimeProfile(['mode' => 'cli', 'request_method' => 'CLI', 'request_uri' => 'cli:test']);
        Debug::checkpoint('bootstrap.start', ['mode' => 'cli']);
        Debug::checkpoint('bootstrap.dependencies_loaded', ['mode' => 'cli']);
        Debug::checkpoint('bootstrap.ready', ['mode' => 'cli']);

        $telemetry = Debug::getRuntimeTelemetry();

        assertTrue(($telemetry['enabled'] ?? true) === false, 'Debug sollte in diesem Test deaktiviert sein.');
        assertTrue(($telemetry['bootstrap']['active'] ?? false) === true, 'Leichtgewichtiges Bootstrap-Profil ist nicht aktiv.');
        assertSame('cli', (string)($telemetry['bootstrap']['mode'] ?? ''), 'Bootstrap-Modus cli wurde nicht übernommen.');
        assertTrue(count($telemetry['bootstrap']['timeline'] ?? []) === 3, 'Bootstrap-Timeline enthält nicht alle Checkpoints.');
    },
    'SystemService liefert Debug-Telemetrie als Snapshot zurück' => static function (): void {
        $reflection = new ReflectionClass(SystemService::class);
        $service = $reflection->newInstanceWithoutConstructor();

        $telemetry = $service->getRuntimeTelemetry();

        assertTrue(array_key_exists('enabled', $telemetry), 'SystemService-Telemetrie enthält keinen Enabled-Status.');
        assertTrue(array_key_exists('query', $telemetry), 'SystemService-Telemetrie enthält keine Query-Daten.');
        assertTrue(array_key_exists('bootstrap', $telemetry), 'SystemService-Telemetrie enthält kein Bootstrap-Profil.');
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
