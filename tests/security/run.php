<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use CMS\Security;
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
        $service = (new ReflectionClass(MediaService::class))->newInstanceWithoutConstructor();
        $file = createTempFile('attack.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $result = invokePrivate($service, 'validateFile', $file, getUploadValidationSettings());

        assertWpErrorCode($result, 'type_blocked', 'SVG-Upload wurde nicht blockiert.');
        @unlink($file['tmp_name']);
    },
    'Upload-Fuzzing blockiert PHP-Inhalt in Textdateien' => static function (): void {
        $service = (new ReflectionClass(MediaService::class))->newInstanceWithoutConstructor();
        $file = createTempFile('shell.txt', "<?php echo 'boom';");
        $result = invokePrivate($service, 'validateFile', $file, getUploadValidationSettings());

        assertWpErrorCode($result, 'php_code_detected', 'PHP-Code in Textdatei wurde nicht blockiert.');
        @unlink($file['tmp_name']);
    },
    'Upload-Fuzzing blockiert gefährliche Erweiterungen' => static function (): void {
        $service = (new ReflectionClass(MediaService::class))->newInstanceWithoutConstructor();
        $file = createTempFile('shell.php', '<?php echo 1;');
        $result = invokePrivate($service, 'validateFile', $file, getUploadValidationSettings());

        assertWpErrorCode($result, 'dangerous_type', 'Gefährliche Dateiendung wurde nicht blockiert.');
        @unlink($file['tmp_name']);
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
