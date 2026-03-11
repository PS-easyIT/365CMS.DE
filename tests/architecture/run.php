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
    'CMS/core/Services/MailService.php',
    'CMS/core/ThemeManager.php',
    'CMS/core/SchemaManager.php',
    'CMS/core/Services/SystemService.php',
    'CMS/core/Services/ImageService.php',
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
    'Architekturregel: Legacy-Entry-Points senden private Cache-Header über den CacheManager' => static function () use ($projectRoot, $cmsRoot): void {
        $targets = [
            'CMS/config.php',
            'CMS/install.php',
            'CMS/cron.php',
            'CMS/orders.php',
            'CMS/index.php',
        ];

        $violations = [];

        foreach ($targets as $relative) {
            $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $content = file_get_contents($fullPath);

            if ($content === false) {
                $violations[] = $relative . ': Datei konnte nicht gelesen werden';
                continue;
            }

            if (!str_contains($content, "sendResponseHeaders('private')")) {
                $violations[] = $relative . ': private Cache-Header via CacheManager fehlen';
            }
        }

        assertTrue($violations === [], implode(' | ', $violations));
    },
    'Architekturregel: Admin-Content- und Hub-Views laden zentrale Assets statt großer Inline-Skripte' => static function () use ($projectRoot): void {
        $inlineScriptFreeViews = [
            'CMS/admin/views/pages/edit.php',
            'CMS/admin/views/posts/edit.php',
            'CMS/admin/views/hub/edit.php',
        ];

        $entryPointAssets = [
            'CMS/admin/pages.php' => ['admin-seo-editor.js', 'admin-content-editor.js'],
            'CMS/admin/posts.php' => ['admin-seo-editor.js', 'admin-content-editor.js'],
            'CMS/admin/hub-sites.php' => ['admin-hub-site-edit.js'],
        ];

        $violations = [];

        foreach ($inlineScriptFreeViews as $relative) {
            $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $content = file_get_contents($fullPath);

            if ($content === false) {
                $violations[] = $relative . ': Datei konnte nicht gelesen werden';
                continue;
            }

            if (stripos($content, '<script') !== false) {
                $violations[] = $relative . ': enthält weiterhin Inline-Script-Markup';
            }
        }

        foreach ($entryPointAssets as $relative => $assets) {
            $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $content = file_get_contents($fullPath);

            if ($content === false) {
                $violations[] = $relative . ': Datei konnte nicht gelesen werden';
                continue;
            }

            foreach ($assets as $asset) {
                if (!str_contains($content, $asset)) {
                    $violations[] = $relative . ': bindet ' . $asset . ' nicht zentral ein';
                }
            }
        }

        assertTrue($violations === [], implode(' | ', $violations));
    },
    'Architekturregel: MFA-Public-Routen umgehen die generische form_guard-CSRF-Schranke' => static function () use ($projectRoot): void {
        $routerPath = $projectRoot . DIRECTORY_SEPARATOR . 'CMS/core/Router.php';
        $publicRouterPath = $projectRoot . DIRECTORY_SEPARATOR . 'CMS/core/Routing/PublicRouter.php';

        $routerContent = file_get_contents($routerPath);
        $publicRouterContent = file_get_contents($publicRouterPath);
        $violations = [];

        if ($routerContent === false) {
            $violations[] = 'CMS/core/Router.php: Datei konnte nicht gelesen werden';
        }

        if ($publicRouterContent === false) {
            $violations[] = 'CMS/core/Routing/PublicRouter.php: Datei konnte nicht gelesen werden';
        }

        if ($routerContent !== false) {
            foreach (['/mfa-challenge', '/mfa-setup', '/mfa-disable'] as $route) {
                if (!str_contains($routerContent, "'{$route}'")) {
                    $violations[] = 'CMS/core/Router.php: MFA-Bypass für ' . $route . ' fehlt';
                }
            }
        }

        if ($publicRouterContent !== false) {
            $expectedRoutes = [
                "addRoute('POST', '/mfa-challenge'",
                "addRoute('POST', '/mfa-setup'",
                "addRoute('POST', '/mfa-disable'",
                'AuthManager::instance()->verifyMfa($code)',
            ];

            foreach ($expectedRoutes as $needle) {
                if (!str_contains($publicRouterContent, $needle)) {
                    $violations[] = 'CMS/core/Routing/PublicRouter.php: erwartet ' . $needle;
                }
            }

            if (str_contains($publicRouterContent, '$_SESSION[\'user_id\'] = $pendingUserId')) {
                $violations[] = 'CMS/core/Routing/PublicRouter.php: setzt nach MFA weiterhin user_id manuell statt den zentralen AuthManager-Pfad zu nutzen';
            }
        }

        assertTrue($violations === [], implode(' | ', $violations));
    },
    'Architekturregel: Admin-Section-Shells und Subnavs verwenden gemeinsame Layout-Bausteine' => static function () use ($projectRoot): void {
        $sectionPages = [
            'CMS/admin/performance-page.php',
            'CMS/admin/member-dashboard-page.php',
            'CMS/admin/system-monitor-page.php',
        ];

        $sectionSubnavs = [
            'CMS/admin/views/performance/subnav.php',
            'CMS/admin/views/member/subnav.php',
            'CMS/admin/views/system/subnav.php',
        ];

        $violations = [];

        foreach ($sectionPages as $relative) {
            $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $content = file_get_contents($fullPath);

            if ($content === false) {
                $violations[] = $relative . ': Datei konnte nicht gelesen werden';
                continue;
            }

            if (!str_contains($content, "partials/section-page-shell.php")) {
                $violations[] = $relative . ': nutzt die gemeinsame Section-Shell nicht';
            }
        }

        foreach ($sectionSubnavs as $relative) {
            $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $content = file_get_contents($fullPath);

            if ($content === false) {
                $violations[] = $relative . ': Datei konnte nicht gelesen werden';
                continue;
            }

            if (!str_contains($content, 'partials/section-subnav.php')) {
                $violations[] = $relative . ': nutzt die gemeinsame Section-Subnav nicht';
            }
        }

        assertTrue($violations === [], implode(' | ', $violations));
    },
    'Architekturregel: Diagnose bindet Vendor-/Asset-Registry sichtbar an' => static function () use ($projectRoot): void {
        $targets = [
            'CMS/core/VendorRegistry.php' => ['getDiagnostics(', 'getBundledLibraryDiagnostics(', 'getBundledPlatformDiagnostics('],
            'CMS/admin/modules/system/SystemInfoModule.php' => ['vendor_registry', 'getVendorRegistryDiagnosticsSafe('],
            'CMS/admin/views/system/diagnose.php' => ['Vendor- &amp; Asset-Registry', 'Bundle-Plattformprüfung', 'Registrierte Produktivpakete'],
        ];

        $violations = [];

        foreach ($targets as $relative => $needles) {
            $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $content = file_get_contents($fullPath);

            if ($content === false) {
                $violations[] = $relative . ': Datei konnte nicht gelesen werden';
                continue;
            }

            foreach ($needles as $needle) {
                if (!str_contains($content, $needle)) {
                    $violations[] = $relative . ': erwartet ' . $needle;
                }
            }
        }

        assertTrue($violations === [], implode(' | ', $violations));
    },
    'Architekturregel: Bootstrap-Profile werden früh gemessen und im Admin sichtbar ausgewertet' => static function () use ($projectRoot): void {
        $targets = [
            'CMS/core/Bootstrap.php' => ["require_once CORE_PATH . 'Debug.php';", "Debug::checkpoint('bootstrap.start'", "Debug::resetRuntimeProfile(["],
            'CMS/core/Debug.php' => ['getBootstrapTelemetry()', "'bootstrap' => self::getBootstrapTelemetry()", 'SLOW_PHASE_THRESHOLD_MS'],
            'CMS/admin/views/system/diagnose.php' => ['Bootstrap-Profil', 'Kaltstart bis Ready', 'Cold-Path-Anteil'],
            'tests/runtime-telemetry/run.php' => ['Bootstrap-Profil bleibt auch ohne Debug-Modus', 'bootstrap.start'],
        ];

        $violations = [];

        foreach ($targets as $relative => $needles) {
            $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            $content = file_get_contents($fullPath);

            if ($content === false) {
                $violations[] = $relative . ': Datei konnte nicht gelesen werden';
                continue;
            }

            foreach ($needles as $needle) {
                if (!str_contains($content, $needle)) {
                    $violations[] = $relative . ': erwartet ' . $needle;
                }
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
