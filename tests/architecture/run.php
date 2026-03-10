<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$projectRoot = dirname(dirname(__DIR__));
$cmsRoot = $projectRoot . DIRECTORY_SEPARATOR . 'CMS';

const MAX_LINES_DEFAULT = 700;
const CORE_NAMESPACE_PATTERN = '/^namespace\s+CMS(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*\s*(?:;|\{)/m';
const STRICT_TYPES_PATTERN = '/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/m';
const ABSPATH_GUARD_PATTERN = '/if\s*\(\s*!defined\(\s*[\'\"]ABSPATH[\'\"]\s*\)\s*\)\s*\{\s*exit\s*;\s*\}/s';
const FORBIDDEN_DEPENDENCY_PATTERN = '/(?:require|include)(?:_once)?\s*\(?[^\n;]*(admin\/|member\/|tests\/|DOC\/|BACKUP\/)/i';

$lineLimitAllowList = [
    'CMS/core/Services/SiteTableService.php',
    'CMS/core/Services/MailService.php',
    'CMS/core/ThemeManager.php',
    'CMS/core/SchemaManager.php',
    'CMS/core/Services/SystemService.php',
    'CMS/core/Services/ImageService.php',
    'CMS/core/Services/SEO/SeoMetaService.php',
    'CMS/core/Services/ThemeCustomizer.php',
    'CMS/core/Services/UpdateService.php',
    'CMS/core/Services/MediaService.php',
    'CMS/core/Services/UserService.php',
    'CMS/includes/functions.php',
];

$dependencyAllowList = [
    'CMS/core/Routing/AdminRouter.php',
    'CMS/core/Member/PluginDashboardRegistry.php',
];

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
 * @return array<int, string>
 */
function collectMonitoredFiles(string $cmsRoot): array
{
    $files = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cmsRoot . DIRECTORY_SEPARATOR . 'core', FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
            $files[] = str_replace('\\', '/', $file->getPathname());
        }
    }

    foreach (['includes/functions.php'] as $relative) {
        $fullPath = $cmsRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (is_file($fullPath)) {
            $files[] = str_replace('\\', '/', $fullPath);
        }
    }

    sort($files);

    return $files;
}

function toProjectRelative(string $path, string $projectRoot): string
{
    $normalizedProjectRoot = str_replace('\\', '/', $projectRoot);
    $normalizedPath = str_replace('\\', '/', $path);

    return ltrim(substr($normalizedPath, strlen($normalizedProjectRoot)), '/');
}

$files = collectMonitoredFiles($cmsRoot);

$tests = [
    'Architekturregel: überwachte Runtime-Dateien erzwingen strict_types und ABSPATH-Guard' => static function () use ($files, $projectRoot): void {
        $violations = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                $violations[] = toProjectRelative($file, $projectRoot) . ': Datei konnte nicht gelesen werden';
                continue;
            }

            if (!preg_match(STRICT_TYPES_PATTERN, $content)) {
                $violations[] = toProjectRelative($file, $projectRoot) . ': declare(strict_types=1) fehlt';
            }

            if (!preg_match(ABSPATH_GUARD_PATTERN, $content)) {
                $violations[] = toProjectRelative($file, $projectRoot) . ': ABSPATH-Guard fehlt';
            }
        }

        assertTrue($violations === [], implode(' | ', $violations));
    },
    'Architekturregel: Core-Dateien verwenden den CMS-Namespace' => static function () use ($projectRoot, $cmsRoot): void {
        $violations = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cmsRoot . DIRECTORY_SEPARATOR . 'core', FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $relative = toProjectRelative($file->getPathname(), $projectRoot);
            if (in_array($relative, ['CMS/core/autoload.php'], true)) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                $violations[] = $relative . ': Datei konnte nicht gelesen werden';
                continue;
            }

            if (!preg_match(CORE_NAMESPACE_PATTERN, $content)) {
                $violations[] = $relative . ': CMS-Namespace fehlt';
            }
        }

        assertTrue($violations === [], implode(' | ', $violations));
    },
    'Architekturregel: keine neuen Großdateien über dem Core-Limit ohne explizite Baseline-Freigabe' => static function () use ($projectRoot, $files, $lineLimitAllowList): void {
        $violations = [];

        foreach ($files as $file) {
            $relative = toProjectRelative($file, $projectRoot);
            $lineCount = count(file($file, FILE_IGNORE_NEW_LINES));

            if ($lineCount > MAX_LINES_DEFAULT && !in_array($relative, $lineLimitAllowList, true)) {
                $violations[] = $relative . ': ' . $lineCount . ' Zeilen überschreiten das Limit von ' . MAX_LINES_DEFAULT;
            }
        }

        assertTrue($violations === [], implode(' | ', $violations));
    },
    'Architekturregel: Core-Dateien ziehen keine verbotenen Admin-/Member-/Test-Abhängigkeiten' => static function () use ($projectRoot, $cmsRoot, $dependencyAllowList): void {
        $violations = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cmsRoot . DIRECTORY_SEPARATOR . 'core', FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $relative = toProjectRelative($file->getPathname(), $projectRoot);
            if (in_array($relative, $dependencyAllowList, true)) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                $violations[] = $relative . ': Datei konnte nicht gelesen werden';
                continue;
            }

            if (preg_match(FORBIDDEN_DEPENDENCY_PATTERN, $content) === 1) {
                $violations[] = $relative . ': enthält direkte Abhängigkeit auf Admin-/Member-/Test-/Dokumentationspfade';
            }
        }

        assertTrue($violations === [], implode(' | ', $violations));
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

echo 'Alle Architektur-Fitness-Checks erfolgreich.' . PHP_EOL;
