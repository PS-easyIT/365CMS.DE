<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use CMS\CacheManager;

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
 * @param array<string, string|array<int, string>|null> $headers
 * @throws RuntimeException
 */
function assertHeaderContains(array $headers, string $name, string $needle, string $message): void
{
    $value = $headers[$name] ?? null;
    if (!is_string($value) || !str_contains($value, $needle)) {
        throw new RuntimeException($message . ' (Header ' . $name . ' enthielt nicht: ' . $needle . ')');
    }
}

/**
 * @param array<string, string|array<int, string>|null> $headers
 * @throws RuntimeException
 */
function assertVaryContains(array $headers, string $needle, string $message): void
{
    $value = $headers['Vary'] ?? null;
    if (!is_array($value) || !in_array($needle, $value, true)) {
        throw new RuntimeException($message . ' (Vary enthielt nicht: ' . $needle . ')');
    }
}

function invokePrivate(object $object, string $methodName, mixed ...$args): mixed
{
    $method = new ReflectionMethod($object, $methodName);
    $method->setAccessible(true);

    return $method->invoke($object, ...$args);
}

$tests = [
    'Öffentliches Cache-Profil enthält Proxy-freundliche Direktiven' => static function (): void {
        $manager = CacheManager::instance();
        /** @var array<string, string|array<int, string>|null> $headers */
        $headers = invokePrivate($manager, 'buildResponseHeaders', 'public', 300);

        assertHeaderContains($headers, 'Cache-Control', 'public', 'Öffentliches Profil ist nicht public.');
        assertHeaderContains($headers, 'Cache-Control', 's-maxage=300', 'Öffentliches Profil setzt kein s-maxage.');
        assertHeaderContains($headers, 'Cache-Control', 'stale-while-revalidate=60', 'Öffentliches Profil setzt kein stale-while-revalidate.');
        assertHeaderContains($headers, 'Cache-Control', 'stale-if-error=300', 'Öffentliches Profil setzt kein stale-if-error.');
        assertHeaderContains($headers, 'Surrogate-Control', 'max-age=300', 'Surrogate-Control für öffentliches Profil fehlt.');
        assertVaryContains($headers, 'Accept-Encoding', 'Vary für Accept-Encoding fehlt im öffentlichen Profil.');
        assertVaryContains($headers, 'Cookie', 'Vary für Cookie fehlt im öffentlichen Profil.');
    },
    'Privates Cache-Profil blockiert Proxy-Caching sauber' => static function (): void {
        $manager = CacheManager::instance();
        /** @var array<string, string|array<int, string>|null> $headers */
        $headers = invokePrivate($manager, 'buildResponseHeaders', 'private', 300);

        assertHeaderContains($headers, 'Cache-Control', 'private', 'Privates Profil ist nicht private.');
        assertHeaderContains($headers, 'Cache-Control', 'no-store', 'Privates Profil setzt kein no-store.');
        assertHeaderContains($headers, 'Cache-Control', 'must-revalidate', 'Privates Profil setzt kein must-revalidate.');
        assertHeaderContains($headers, 'Surrogate-Control', 'no-store', 'Surrogate-Control für privates Profil fehlt.');
        assertVaryContains($headers, 'Accept-Encoding', 'Vary für Accept-Encoding fehlt im privaten Profil.');
        assertVaryContains($headers, 'Cookie', 'Vary für Cookie fehlt im privaten Profil.');
    },
    'Vary-Merge behandelt Header-Tokens case-insensitiv und ohne Duplikate' => static function (): void {
        $manager = CacheManager::instance();
        /** @var array<int, string> $merged */
        $merged = invokePrivate($manager, 'mergeHeaderTokenList', ['User-Agent', 'Cookie'], ['accept-encoding', 'cookie', 'USER-AGENT']);

        assertTrue(count($merged) === 3, 'Vary-Merge hat Duplikate nicht korrekt entfernt.');
        assertTrue(in_array('User-Agent', $merged, true), 'User-Agent ging beim Header-Merge verloren.');
        assertTrue(in_array('Cookie', $merged, true), 'Cookie ging beim Header-Merge verloren.');
        assertTrue(in_array('accept-encoding', $merged, true), 'Accept-Encoding ging beim Header-Merge verloren.');
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

echo 'Alle HTTP-Cache-/Proxy-Regressionschecks erfolgreich.' . PHP_EOL;
