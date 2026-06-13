<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use CMS\Services\PdfService;
use CMS\VendorRegistry;

final class SkippedTestException extends RuntimeException
{
}

/**
 * @throws RuntimeException
 */
function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$tests = [
    'PDF-Service: Dompdf ist als gemanagtes Package verfügbar' => static function (): void {
        $diagnostics = VendorRegistry::instance()->getDiagnostics();
        $packages = is_array($diagnostics['packages'] ?? null) ? $diagnostics['packages'] : [];

        $dompdfEntry = null;
        foreach ($packages as $package) {
            if (($package['package'] ?? '') === 'dompdf') {
                $dompdfEntry = $package;
                break;
            }
        }

        assertTrue(is_array($dompdfEntry), 'VendorRegistry enthält keinen dompdf-Eintrag.');
        assertTrue(!empty($dompdfEntry['available']), 'Dompdf ist im Runtime-Pfad nicht verfügbar.');
    },
    'PDF-Service: Generierung liefert gültigen PDF-Header' => static function (): void {
        if (!function_exists('mb_internal_encoding')) {
            throw new SkippedTestException('mbstring ist in der aktuellen Runtime nicht verfügbar.');
        }

        $service = PdfService::getInstance();
        assertTrue($service->isAvailable(), 'PdfService meldet Dompdf als nicht verfügbar.');

        $html = $service->wrapTemplate('PDF Smoke Test', '<h1>PDF Smoke Test</h1><p>Runner geprüft.</p>');
        $binary = $service->generateFromHtml($html);

        assertTrue(is_string($binary) && $binary !== '', 'PdfService lieferte keinen PDF-Output.');
        assertTrue(str_starts_with($binary, "%PDF-"), 'PDF-Output beginnt nicht mit dem erwarteten %PDF- Header.');
    },
];

$output = [];
$failures = [];

foreach ($tests as $label => $test) {
    try {
        $test();
        $output[] = "[PASS] {$label}";
	} catch (SkippedTestException $e) {
		$output[] = "[SKIP] {$label}: {$e->getMessage()}";
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

echo 'Alle PDF-Service-Smoke-Checks erfolgreich.' . PHP_EOL;
