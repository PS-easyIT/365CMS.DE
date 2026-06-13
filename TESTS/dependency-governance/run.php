<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/**
 * @throws RuntimeException
 */
function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function readJsonFileAsArray(string $path): array
{
    assertTrue(is_file($path), 'JSON-Datei fehlt: ' . $path);
    $content = file_get_contents($path);
    assertTrue(is_string($content) && $content !== '', 'JSON-Datei ist leer oder nicht lesbar: ' . $path);

    $decoded = json_decode($content, true);
    assertTrue(is_array($decoded), 'JSON-Datei ist ungültig: ' . $path . ' (' . json_last_error_msg() . ')');

    return $decoded;
}

$tests = [
    'Dependency-Governance: Inventar ist valide und vollständig strukturiert' => static function (): void {
        $inventoryPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'audit' . DIRECTORY_SEPARATOR . 'dependency-governance.json';
        $inventory = readJsonFileAsArray($inventoryPath);

        assertTrue((string)($inventory['schema_version'] ?? '') !== '', 'schema_version fehlt.');
        assertTrue(is_array($inventory['manifests'] ?? null) && $inventory['manifests'] !== [], 'manifests-Liste fehlt oder ist leer.');
        assertTrue(is_array($inventory['vendor_roots'] ?? null) && $inventory['vendor_roots'] !== [], 'vendor_roots-Liste fehlt oder ist leer.');
        assertTrue(is_array($inventory['known_gaps'] ?? null), 'known_gaps muss als Array dokumentiert sein.');
    },
    'Dependency-Governance: referenzierte Manifest-Dateien existieren und sind JSON-valid' => static function (): void {
        $root = dirname(__DIR__, 2);
        $inventory = readJsonFileAsArray($root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'audit' . DIRECTORY_SEPARATOR . 'dependency-governance.json');

        foreach ((array)($inventory['manifests'] ?? []) as $manifest) {
            assertTrue(is_array($manifest), 'Manifest-Eintrag muss ein Objekt sein.');
            $relativePath = trim((string)($manifest['path'] ?? ''));
            assertTrue($relativePath !== '', 'Manifest-Eintrag ohne path gefunden.');
            $fullPath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $decoded = readJsonFileAsArray($fullPath);
            assertTrue($decoded !== [], 'Manifest ist leer: ' . $relativePath);
        }
    },
    'Dependency-Governance: dokumentierte Vendor-Roots existieren' => static function (): void {
        $root = dirname(__DIR__, 2);
        $inventory = readJsonFileAsArray($root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'audit' . DIRECTORY_SEPARATOR . 'dependency-governance.json');

        foreach ((array)($inventory['vendor_roots'] ?? []) as $vendorRoot) {
            assertTrue(is_array($vendorRoot), 'Vendor-Root-Eintrag muss ein Objekt sein.');
            $relativePath = trim((string)($vendorRoot['path'] ?? ''));
            assertTrue($relativePath !== '', 'Vendor-Root ohne path gefunden.');
            assertTrue(is_dir($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath)), 'Vendor-Root fehlt: ' . $relativePath);
            assertTrue(trim((string)($vendorRoot['release_note'] ?? '')) !== '', 'Vendor-Root ohne release_note: ' . $relativePath);
        }
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

echo 'Alle Dependency-Governance-Smoke-Checks erfolgreich.' . PHP_EOL;
