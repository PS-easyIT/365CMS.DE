<?php
declare(strict_types=1);

/**
 * Zentraler Runner für modulnahe Suites im TESTS-Verzeichnis.
 *
 * Nutzung:
 *   php TESTS/run.php
 *   php TESTS/run.php --suite=release-smoke
 *   php TESTS/run.php --list
 */

$testsRoot = __DIR__;

$args = $argv ?? [];
array_shift($args);

$requestedSuite = null;
$listOnly = false;

foreach ($args as $arg) {
    if ($arg === '--list') {
        $listOnly = true;
        continue;
    }

    if (str_starts_with($arg, '--suite=')) {
        $requestedSuite = trim((string) substr($arg, strlen('--suite=')));
    }
}

$suiteMap = [];
$suiteDescriptions = [];
$manifestPath = $testsRoot . DIRECTORY_SEPARATOR . 'manifest.php';

if (is_file($manifestPath)) {
    $manifest = require $manifestPath;
    if (!is_array($manifest)) {
        fwrite(STDERR, "[ERROR] TESTS-Runner: manifest.php muss ein Array zurückgeben." . PHP_EOL);
        exit(1);
    }

    foreach ($manifest as $suite => $definition) {
        if (!is_string($suite) || !is_array($definition)) {
            continue;
        }

        $script = (string)($definition['script'] ?? '');
        if ($script === '' || !is_file($script)) {
            if (!empty($definition['required'])) {
                fwrite(STDERR, '[ERROR] Manifest-Suite fehlt: ' . $suite . ' (' . $script . ')' . PHP_EOL);
                exit(1);
            }
            continue;
        }

        $suiteMap[$suite] = $script;
        $suiteDescriptions[$suite] = (string)($definition['description'] ?? '');
    }
} else {
    $scripts = glob($testsRoot . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'run.php');
    if ($scripts === false) {
        fwrite(STDERR, "[ERROR] TESTS-Runner: Suites konnten nicht ermittelt werden." . PHP_EOL);
        exit(1);
    }

    sort($scripts);

    foreach ($scripts as $script) {
        $suite = basename((string) dirname($script));
        $suiteMap[$suite] = $script;
    }
}

if ($requestedSuite !== null) {
    if (!isset($suiteMap[$requestedSuite])) {
        fwrite(STDERR, '[ERROR] Unbekannte Suite: ' . $requestedSuite . PHP_EOL);
        fwrite(STDERR, '[INFO] Verfügbare Suites: ' . implode(', ', array_keys($suiteMap)) . PHP_EOL);
        exit(1);
    }

    $suiteMap = [$requestedSuite => $suiteMap[$requestedSuite]];
}

if ($listOnly) {
    foreach ($suiteMap as $suite => $_script) {
        $description = trim((string)($suiteDescriptions[$suite] ?? ''));
        echo $description !== '' ? ($suite . ' - ' . $description) : $suite;
        echo PHP_EOL;
    }
    exit(0);
}

if ($suiteMap === []) {
    fwrite(STDERR, "[ERROR] Keine Test-Suiten unter TESTS/*/run.php gefunden." . PHP_EOL);
    exit(1);
}

$phpBinary = PHP_BINARY !== '' ? PHP_BINARY : 'php';
$failures = [];
$passes = 0;

foreach ($suiteMap as $suite => $script) {
    echo PHP_EOL . '=== Suite: ' . $suite . ' ===' . PHP_EOL;

    $command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($script);
    $output = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);

    foreach ($output as $line) {
        echo $line . PHP_EOL;
    }

    if ($exitCode === 0) {
        $passes++;
        echo '[SUITE PASS] ' . $suite . PHP_EOL;
    } else {
        $failures[] = $suite;
        echo '[SUITE FAIL] ' . $suite . ' (Exit ' . $exitCode . ')' . PHP_EOL;
    }
}

echo PHP_EOL;
echo '=== TESTS Gesamt ===' . PHP_EOL;
echo 'Suites gesamt: ' . count($suiteMap) . PHP_EOL;
echo 'Bestanden: ' . $passes . PHP_EOL;
echo 'Fehlgeschlagen: ' . count($failures) . PHP_EOL;

if ($failures !== []) {
    echo 'Fehlende Suites: ' . implode(', ', $failures) . PHP_EOL;
    exit(1);
}

echo 'Alle ausgewählten Suites erfolgreich.' . PHP_EOL;
