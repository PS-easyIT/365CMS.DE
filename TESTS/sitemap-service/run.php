<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use CMS\Services\SitemapService;
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

function createSitemapTempDir(): string
{
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . '365cms-sitemap-test-' . bin2hex(random_bytes(6));
    if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Temporäres Sitemap-Testverzeichnis konnte nicht erstellt werden.');
    }

    return $dir;
}

function deleteSitemapTempDir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_file($path)) {
            unlink($path);
        }
    }

    rmdir($dir);
}

function readGeneratedSitemapFile(string $dir, string $file): string
{
    $path = $dir . DIRECTORY_SEPARATOR . $file;
    assertTrue(is_file($path), 'Erwartete Sitemap-Datei fehlt: ' . $file);

    $content = file_get_contents($path);
    assertTrue(is_string($content) && $content !== '', 'Sitemap-Datei ist leer oder nicht lesbar: ' . $file);

    return $content;
}

$tests = [
    'SitemapService: melbahja-seo Package ist verfügbar' => static function (): void {
        $loaded = VendorRegistry::instance()->loadPackage('melbahja-seo');
        assertTrue($loaded, 'melbahja-seo konnte über die VendorRegistry nicht geladen werden.');
        assertTrue(class_exists(\Melbahja\Seo\Sitemap::class), 'Melbahja\\Seo\\Sitemap-Klasse ist nicht verfügbar.');
    },
    'SitemapService: Index und Teil-Sitemaps enthalten kanonische Felder' => static function (): void {
        $dir = createSitemapTempDir();

        try {
            $service = new SitemapService('https://example.test', $dir);
            $service->generatePages([
                ['url' => '/', 'lastmod' => '2026-06-13T10:00:00+00:00', 'priority' => 1.0, 'changefreq' => 'daily'],
                ['url' => '/about', 'lastmod' => '2026-06-12T09:00:00+00:00', 'priority' => 0.7, 'changefreq' => 'monthly'],
            ]);
            $service->generatePosts([
                ['url' => '/blog/post-a', 'lastmod' => '2026-06-11T08:00:00+00:00', 'priority' => 0.8, 'changefreq' => 'weekly'],
            ]);
            $service->generateImages([
                [
                    'url' => '/blog/post-a',
                    'image' => '/uploads/post-a.jpg',
                    'title' => 'Post A Bild',
                    'caption' => 'Bildbeschreibung',
                    'lastmod' => '2026-06-11T08:00:00+00:00',
                ],
            ]);
            $service->generateNews([
                [
                    'url' => '/news/post-a',
                    'title' => 'Nachricht A',
                    'publication_date' => '2026-06-13T07:00:00+00:00',
                    'lastmod' => '2026-06-13T07:30:00+00:00',
                ],
            ], '365CMS Test', 'de');
            $service->generate();

            $index = readGeneratedSitemapFile($dir, 'sitemap.xml');
            foreach (['pages.xml', 'posts.xml', 'images.xml', 'news.xml'] as $file) {
                assertTrue(str_contains($index, $file), 'Sitemap-Index referenziert ' . $file . ' nicht.');
            }

            $pages = readGeneratedSitemapFile($dir, 'pages.xml');
            assertTrue(str_contains($pages, 'https://example.test/about'), 'pages.xml enthält die normalisierte About-URL nicht.');
            assertTrue(str_contains($pages, '<lastmod>'), 'pages.xml enthält kein lastmod-Feld.');
            assertTrue(str_contains($pages, '<priority>'), 'pages.xml enthält kein priority-Feld.');
            assertTrue(str_contains($pages, '<changefreq>'), 'pages.xml enthält kein changefreq-Feld.');

            $posts = readGeneratedSitemapFile($dir, 'posts.xml');
            assertTrue(str_contains($posts, 'https://example.test/blog/post-a'), 'posts.xml enthält die Post-URL nicht.');

            $images = readGeneratedSitemapFile($dir, 'images.xml');
            assertTrue(str_contains($images, 'https://example.test/uploads/post-a.jpg'), 'images.xml enthält die normalisierte Bild-URL nicht.');
            assertTrue(str_contains($images, 'Post A Bild'), 'images.xml enthält den Bildtitel nicht.');

            $news = readGeneratedSitemapFile($dir, 'news.xml');
            assertTrue(str_contains($news, 'Nachricht A'), 'news.xml enthält den News-Titel nicht.');
            assertTrue(str_contains($news, '365CMS Test'), 'news.xml enthält den Publication-Namen nicht.');
        } finally {
            deleteSitemapTempDir($dir);
        }
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

echo 'Alle Sitemap-Service-Smoke-Checks erfolgreich.' . PHP_EOL;
