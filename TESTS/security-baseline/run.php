<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/**
 * @throws RuntimeException
 */
function securityAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function securityReadFile(string $path): string
{
    securityAssert(is_file($path), 'Datei fehlt: ' . $path);
    $content = file_get_contents($path);
    securityAssert(is_string($content) && $content !== '', 'Datei ist leer oder nicht lesbar: ' . $path);

    return $content;
}

/**
 * @return list<string>
 */
function securityCollectPhpFiles(string $directory): array
{
    securityAssert(is_dir($directory), 'Verzeichnis fehlt: ' . $directory);

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
            continue;
        }

        if (strtolower($fileInfo->getExtension()) !== 'php') {
            continue;
        }

        $files[] = $fileInfo->getPathname();
    }

    sort($files);

    return $files;
}

$root = dirname(__DIR__, 2);

$tests = [
    'SEC-001: Media-Uploads haben Content-Scan-Hook und Blocker' => static function () use ($root): void {
        $mediaService = securityReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'MediaService.php');

        securityAssert(str_contains($mediaService, "Hooks::applyFilters('cms_media_upload_scan'"), 'cms_media_upload_scan Hook fehlt in MediaService.');
        securityAssert(str_contains($mediaService, 'upload_polyglot_detected'), 'Polyglot-/Script-Blocker fehlt in MediaService.');
        securityAssert(str_contains($mediaService, 'upload_xml_doctype_blocked'), 'XML-DOCTYPE-Blocker fehlt in MediaService.');
    },
    'SEC-001: Importer sichert gespeicherte Payloads und blockiert XML-DOCTYPE' => static function () use ($root): void {
        $importer = securityReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'cms-importer' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class-admin.php');

        securityAssert(str_contains($importer, 'secure_saved_import_file('), 'Importer ruft secure_saved_import_file nicht auf.');
        securityAssert(str_contains($importer, 'validate_import_payload_file('), 'Importer ruft validate_import_payload_file nicht auf.');
        securityAssert(str_contains($importer, 'chmod($path, 0640)'), 'Restriktive Importdatei-Rechte 0640 fehlen.');
        securityAssert(str_contains($importer, '<!DOCTYPE'), 'XML-DOCTYPE-Blocker fehlt im Importer.');
        securityAssert(str_contains($importer, '.htaccess'), 'Import-Verzeichnishärtung per .htaccess fehlt.');
    },
    'SEC-002: SQL-Wartung validiert Identifier strikt' => static function () use ($root): void {
        $systemService = securityReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'SystemService.php');

        securityAssert(str_contains($systemService, 'preg_match(\'/^[A-Za-z0-9_]+$/\', $identifier)'), 'Strikte Identifier-Regex fehlt in quoteIdentifier.');
        securityAssert(str_contains($systemService, "sprintf('%s TABLE %s'"), 'Deterministischer TABLE-Wartungsstatement-Bau fehlt.');
        securityAssert(str_contains($systemService, 'Ungültiger SQL-Identifier für Wartungsoperation'), 'Identifier-Fehlerpfad fehlt.');
    },
    'SEC-003: Admin-Module enthalten keine direkten Superglobal-Zugriffe' => static function () use ($root): void {
        $adminModules = $root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'modules';
        $violations = [];

        foreach (securityCollectPhpFiles($adminModules) as $file) {
            $content = securityReadFile($file);
            if (preg_match('/\$_(?:GET|POST|FILES|SERVER|REQUEST|COOKIE|SESSION)\b/', $content) === 1) {
                $violations[] = str_replace($root . DIRECTORY_SEPARATOR, '', $file);
            }
        }

        securityAssert($violations === [], 'Direkte Superglobals im Admin-Modul-Scope gefunden: ' . implode(', ', $violations));
    },
    'SEC-004: Web-Cron erzwingt Header-first und gated Query-Token' => static function () use ($root): void {
        $cron = securityReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'cron.php');

        securityAssert(str_contains($cron, 'HTTP_X_CMS_CRON_TOKEN'), 'X-CMS-Cron-Token Header-Auswertung fehlt.');
        securityAssert(str_contains($cron, 'CMS_CRON_ALLOW_QUERY_TOKEN'), 'Expliziter Query-Token-Übergangsschalter fehlt.');
        securityAssert(str_contains($cron, 'queryTokenSunsetReached'), 'Query-Token-Sunset-Guard fehlt.');
        securityAssert(str_contains($cron, 'Cron-Token via Query-Parameter ist deaktiviert'), 'Query-Token-Blockadefehler fehlt.');
    },
    'SEC-005: Security-Baseline ist im Testmanifest versioniert' => static function () use ($root): void {
        $manifest = securityReadFile($root . DIRECTORY_SEPARATOR . 'TESTS' . DIRECTORY_SEPARATOR . 'manifest.php');
        $gitignore = securityReadFile($root . DIRECTORY_SEPARATOR . '.gitignore');

        securityAssert(str_contains($manifest, "'security-baseline'"), 'security-baseline fehlt in TESTS/manifest.php.');
        securityAssert(str_contains($gitignore, '!/TESTS/security-baseline/'), 'security-baseline-Verzeichnis ist nicht per .gitignore freigegeben.');
        securityAssert(str_contains($gitignore, '!/TESTS/security-baseline/run.php'), 'security-baseline/run.php ist nicht per .gitignore freigegeben.');
        securityAssert(!preg_match('/^TESTS\/run\.php$/m', $gitignore), 'TESTS/run.php wird am Ende erneut ignoriert.');
        securityAssert(!preg_match('/^TESTS\/security-baseline\/run\.php$/m', $gitignore), 'Security-Baseline-Runfile wird erneut ignoriert.');
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

echo 'Alle Security-Baseline-Smoke-Checks erfolgreich.' . PHP_EOL;
