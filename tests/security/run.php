<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

require_once dirname(__DIR__, 1) . '/../CMS/admin/modules/plugins/PluginMarketplaceModule.php';
require_once dirname(__DIR__, 1) . '/../CMS/admin/modules/themes/ThemeMarketplaceModule.php';
require_once dirname(__DIR__, 1) . '/../CMS/admin/modules/system/DocumentationSyncFilesystem.php';
require_once dirname(__DIR__, 1) . '/../CMS/admin/modules/system/DocumentationSyncDownloader.php';

use CMS\Security;
use CMS\Services\UpdateService;
use CMS\Services\Media\ImageProcessor;
use CMS\Services\MediaService;
use CMS\WP_Error;

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
function assertNotSame(string $expected, string $actual, string $message): void
{
    if ($expected === $actual) {
        throw new RuntimeException($message . ' (erwartet ungleich, bekam ' . $actual . ')');
    }
}

/**
 * @param mixed $value
 * @throws RuntimeException
 */
function assertWpErrorCode(mixed $value, string $expectedCode, string $message): void
{
    if (!$value instanceof WP_Error) {
        throw new RuntimeException($message . ' (kein WP_Error erhalten)');
    }

    if ($value->get_error_code() !== $expectedCode) {
        throw new RuntimeException($message . ' (erwartet ' . $expectedCode . ', bekam ' . $value->get_error_code() . ')');
    }
}

function resetSingleton(string $className): void
{
    $reflection = new ReflectionClass($className);
    if (!$reflection->hasProperty('instance')) {
        return;
    }

    $property = $reflection->getProperty('instance');
    $property->setAccessible(true);
    $property->setValue(null, null);
}

function resetSessionState(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        session_unset();
        session_destroy();
    }

    $_SESSION = [];
}

function invokePrivate(object $object, string $methodName, mixed ...$args): mixed
{
    $method = new ReflectionMethod($object, $methodName);
    $method->setAccessible(true);

    return $method->invoke($object, ...$args);
}

function createTempFile(string $name, string $content): array
{
    $path = tempnam(sys_get_temp_dir(), '365cms-sec-');
    if ($path === false) {
        throw new RuntimeException('Temporäre Testdatei konnte nicht erstellt werden.');
    }

    file_put_contents($path, $content);

    return [
        'name' => $name,
        'tmp_name' => $path,
        'size' => filesize($path),
        'error' => UPLOAD_ERR_OK,
    ];
}

function createTempZip(array $entries): string
{
    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException('ZipArchive ist für diesen Security-Test nicht verfügbar.');
    }

    $path = tempnam(sys_get_temp_dir(), '365cms-sec-zip-');
    if ($path === false) {
        throw new RuntimeException('Temporäre ZIP-Datei konnte nicht erstellt werden.');
    }

    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Temporäre ZIP-Datei konnte nicht geöffnet werden.');
    }

    foreach ($entries as $entryName => $entryContent) {
        if (!$zip->addFromString((string) $entryName, (string) $entryContent)) {
            $zip->close();
            throw new RuntimeException('ZIP-Eintrag konnte nicht geschrieben werden: ' . $entryName);
        }
    }

    $zip->close();

    return $path;
}

function deleteTempPath(string $path): void
{
    if (is_file($path)) {
        unlink($path);
    }
}

function instantiateWithoutConstructor(string $className): object
{
    return (new ReflectionClass($className))->newInstanceWithoutConstructor();
}

function getUploadValidationSettings(): array
{
    return [
        'max_upload_size' => '64M',
        'allowed_types' => ['image', 'document'],
        'block_dangerous_types' => true,
        'validate_image_content' => true,
    ];
}

$tests = [
    'Session-Fixation wird unterbunden' => static function (): void {
        resetSessionState();
        resetSingleton(Security::class);

        session_id('fixedsessionid1234567890');
        $security = Security::instance();
        invokePrivate($security, 'startSession');

        assertTrue(session_status() === PHP_SESSION_ACTIVE, 'Die Security-Session wurde nicht gestartet.');
        assertTrue(!empty($_SESSION['initialized']), 'Die Session wurde nicht initialisiert.');
        assertNotSame('fixedsessionid1234567890', session_id(), 'Session-ID wurde nicht regeneriert.');

        session_write_close();
    },
    'CSRF-Token lässt kein Replay zu' => static function (): void {
        resetSessionState();
        resetSingleton(Security::class);

        session_id('csrfsessionid1234567890');
        $security = Security::instance();
        invokePrivate($security, 'startSession');

        $token = $security->generateToken('security_replay_test');
        assertTrue($security->verifyToken($token, 'security_replay_test') === true, 'Erste CSRF-Prüfung fehlgeschlagen.');
        assertTrue($security->verifyToken($token, 'security_replay_test') === false, 'Replay-CSRF-Token wurde fälschlich erneut akzeptiert.');

        session_write_close();
    },
    'Upload-Fuzzing blockiert SVG' => static function (): void {
        $service = instantiateWithoutConstructor(MediaService::class);
        $file = createTempFile('attack.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $result = invokePrivate($service, 'validateFile', $file, getUploadValidationSettings());

        assertWpErrorCode($result, 'type_blocked', 'SVG-Upload wurde nicht blockiert.');
        deleteTempPath($file['tmp_name']);
    },
    'Upload-Fuzzing blockiert PHP-Inhalt in Textdateien' => static function (): void {
        $service = instantiateWithoutConstructor(MediaService::class);
        $file = createTempFile('shell.txt', "<?php echo 'boom';");
        $result = invokePrivate($service, 'validateFile', $file, getUploadValidationSettings());

        assertWpErrorCode($result, 'php_code_detected', 'PHP-Code in Textdatei wurde nicht blockiert.');
        deleteTempPath($file['tmp_name']);
    },
    'Upload-Fuzzing blockiert gefährliche Erweiterungen' => static function (): void {
        $service = instantiateWithoutConstructor(MediaService::class);
        $file = createTempFile('shell.php', '<?php echo 1;');
        $result = invokePrivate($service, 'validateFile', $file, getUploadValidationSettings());

        assertWpErrorCode($result, 'dangerous_type', 'Gefährliche Dateiendung wurde nicht blockiert.');
        deleteTempPath($file['tmp_name']);
    },
    'ImageProcessor lehnt ungültige Bilddateien sauber ab' => static function (): void {
        $file = createTempFile('broken-image.jpg', 'kein echtes bild');
        $processor = new ImageProcessor();
        $result = $processor->convertToWebP($file['tmp_name']);

        $expectedCode = extension_loaded('gd') ? 'invalid_image' : 'gd_missing';
        assertWpErrorCode($result, $expectedCode, 'Ungültige Bilddatei wurde nicht sauber abgelehnt.');
        deleteTempPath($file['tmp_name']);
    },
    'UpdateService blockiert fremde GitHub-API-Hosts' => static function (): void {
        $service = instantiateWithoutConstructor(UpdateService::class);
        $property = new ReflectionProperty(UpdateService::class, 'githubApi');
        $property->setAccessible(true);
        $property->setValue($service, 'https://api.github.com');

        assertTrue(invokePrivate($service, 'isAllowedGitHubApiUrl', 'https://api.github.com/repos/PS-easyIT/365CMS.DE/releases/latest') === true, 'Legitime GitHub-API-URL wurde fälschlich blockiert.');
        assertTrue(invokePrivate($service, 'isAllowedGitHubApiUrl', 'https://evil.example.test/repos/PS-easyIT/365CMS.DE/releases/latest') === false, 'Fremder GitHub-API-Host wurde nicht blockiert.');
    },
    'UpdateService blockiert localhost in Remote-Pfaden' => static function (): void {
        $service = instantiateWithoutConstructor(UpdateService::class);

        assertTrue(invokePrivate($service, 'isSafeExternalUrl', 'https://localhost/update.json') === false, 'localhost wurde im SSRF-Guard nicht blockiert.');
    },
    'UpdateService blockiert fremde Paket-Hosts' => static function (): void {
        $service = instantiateWithoutConstructor(UpdateService::class);

        assertTrue(invokePrivate($service, 'isAllowedSensitiveRemoteUrl', 'https://365network.de/plugins/cms-contact/cms-contact-1.0.0.zip') === true, 'Legitimer Update-Host wurde fälschlich blockiert.');
        assertTrue(invokePrivate($service, 'isAllowedSensitiveRemoteUrl', 'https://evil.example.test/update.zip') === false, 'Fremder Update-Host wurde nicht blockiert.');
    },
    'Plugin-Marketplace blockiert fremde Registry-Hosts' => static function (): void {
        $module = instantiateWithoutConstructor(PluginMarketplaceModule::class);

        assertTrue(invokePrivate($module, 'isAllowedMarketplaceUrl', 'https://365network.de/plugins/cms-contact/cms-contact-1.0.0.zip') === true, 'Legitimer Plugin-Marketplace-Host wurde fälschlich blockiert.');
        assertTrue(invokePrivate($module, 'isAllowedMarketplaceUrl', 'https://evil.example.test/plugins/index.json') === false, 'Fremder Plugin-Marketplace-Host wurde nicht blockiert.');
    },
    'Plugin-Marketplace erzwingt SHA-256 für Auto-Installationen' => static function (): void {
        $module = instantiateWithoutConstructor(PluginMarketplaceModule::class);

        $withoutHash = [
            'slug' => 'cms-contact',
            'download_url' => 'https://365network.de/plugins/cms-contact/cms-contact-1.0.0.zip',
        ];
        $withHash = [
            'slug' => 'cms-contact',
            'download_url' => 'https://365network.de/plugins/cms-contact/cms-contact-1.0.0.zip',
            'sha256' => str_repeat('a', 64),
        ];

        assertTrue(invokePrivate($module, 'canAutoInstall', $withoutHash) === false, 'Plugin-Auto-Installation ohne SHA-256 wurde nicht blockiert.');
        assertTrue(invokePrivate($module, 'canAutoInstall', $withHash) === true, 'Plugin-Auto-Installation mit SHA-256 wurde fälschlich blockiert.');
    },
    'Theme-Marketplace blockiert fremde Registry-Hosts' => static function (): void {
        $module = instantiateWithoutConstructor(ThemeMarketplaceModule::class);

        assertTrue(invokePrivate($module, 'isAllowedMarketplaceUrl', 'https://raw.githubusercontent.com/PS-easyIT/365CMS.DE-THEME/main/index.json') === true, 'Legitimer Theme-Marketplace-Host wurde fälschlich blockiert.');
        assertTrue(invokePrivate($module, 'isAllowedMarketplaceUrl', 'https://evil.example.test/themes/index.json') === false, 'Fremder Theme-Marketplace-Host wurde nicht blockiert.');
    },
    'Theme-Marketplace erzwingt SHA-256 für Auto-Installationen' => static function (): void {
        $module = instantiateWithoutConstructor(ThemeMarketplaceModule::class);

        $withoutHash = [
            'slug' => 'cms-default',
            'download_url' => 'https://raw.githubusercontent.com/PS-easyIT/365CMS.DE-THEME/main/cms-default.zip',
        ];
        $withHash = [
            'slug' => 'cms-default',
            'download_url' => 'https://raw.githubusercontent.com/PS-easyIT/365CMS.DE-THEME/main/cms-default.zip',
            'sha256' => str_repeat('b', 64),
        ];

        assertTrue(invokePrivate($module, 'canAutoInstall', $withoutHash) === false, 'Theme-Auto-Installation ohne SHA-256 wurde nicht blockiert.');
        assertTrue(invokePrivate($module, 'canAutoInstall', $withHash) === true, 'Theme-Auto-Installation mit SHA-256 wurde fälschlich blockiert.');
    },
    'Dokumentations-Sync blockiert fremde Download-Hosts' => static function (): void {
        $downloader = new DocumentationSyncDownloader(new DocumentationSyncFilesystem());
        $destination = sys_get_temp_dir() . DIRECTORY_SEPARATOR . '365cms-doc-sync-security-test.zip';

        $result = $downloader->downloadFile('https://evil.example.test/archive.zip', $destination);
        assertTrue(($result['success'] ?? false) === false, 'Fremder Doku-Download-Host wurde nicht blockiert.');
        assertTrue(str_contains((string) ($result['error'] ?? ''), 'erlaubten GitHub-Hosts'), 'Fehlermeldung für blockierten Doku-Host fehlt.');
    },
    'Plugin-Marketplace blockiert ZIP-Pfad-Traversal' => static function (): void {
        if (!class_exists(ZipArchive::class)) {
            return;
        }

        $zipPath = createTempZip([
            '../evil.php' => '<?php echo 1;',
            'cms-contact/cms-contact.php' => '<?php echo 2;',
        ]);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            deleteTempPath($zipPath);
            throw new RuntimeException('Test-ZIP für Plugin-Marketplace konnte nicht geöffnet werden.');
        }

        $module = instantiateWithoutConstructor(PluginMarketplaceModule::class);
        assertTrue(invokePrivate($module, 'validateZipEntries', $zip, 'cms-contact') === false, 'ZIP-Pfad-Traversal im Plugin-Marketplace wurde nicht blockiert.');

        $zip->close();
        deleteTempPath($zipPath);
    },
    'Theme-Marketplace blockiert ZIP-Pfad-Traversal' => static function (): void {
        if (!class_exists(ZipArchive::class)) {
            return;
        }

        $zipPath = createTempZip([
            'abcdef123/theme.json' => '{}',
            'abcdef123/style.css' => '/* theme */',
            '../evil.php' => '<?php echo 1;',
        ]);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            deleteTempPath($zipPath);
            throw new RuntimeException('Test-ZIP für Theme-Marketplace konnte nicht geöffnet werden.');
        }

        $module = instantiateWithoutConstructor(ThemeMarketplaceModule::class);
        assertTrue(invokePrivate($module, 'validateZipEntries', $zip, 'cms-default') === false, 'ZIP-Pfad-Traversal im Theme-Marketplace wurde nicht blockiert.');

        $zip->close();
        deleteTempPath($zipPath);
    },
    'Update-Pakete blockieren ZIP-Pfad-Traversal' => static function (): void {
        if (!class_exists(ZipArchive::class)) {
            return;
        }

        $zipPath = createTempZip([
            '../evil.php' => '<?php echo 1;',
            'core/Bootstrap.php' => '<?php',
        ]);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            deleteTempPath($zipPath);
            throw new RuntimeException('Test-ZIP für Update-Paket konnte nicht geöffnet werden.');
        }

        $service = instantiateWithoutConstructor(UpdateService::class);
        assertTrue(invokePrivate($service, 'validateZipEntries', $zip) === false, 'ZIP-Pfad-Traversal im Update-Paket wurde nicht blockiert.');

        $zip->close();
        deleteTempPath($zipPath);
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

echo 'Alle Security-Regressionschecks erfolgreich.' . PHP_EOL;
