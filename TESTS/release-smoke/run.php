<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$projectRoot = dirname(__DIR__, 2);
$manifestPath = __DIR__ . '/manifest.php';

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

/**
 * @return array<string, mixed>
 */
function loadManifest(string $manifestPath): array
{
    $manifest = require $manifestPath;
    if (!is_array($manifest)) {
        throw new RuntimeException('Release-Smoke-Manifest muss ein Array zurückgeben.');
    }

    return $manifest;
}

/**
 * @param array<int, string> $paths
 * @return array<int, string>
 */
function normalizePaths(array $paths): array
{
    return array_values(array_unique(array_map(static fn (string $path): string => trim($path), $paths)));
}

/**
 * @param array<int, string> $candidates
 */
function resolveExistingPath(array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if ($candidate !== '' && is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
}

$tests = [
    'Release-Smoke-Manifest deckt Public-, Auth-, Member-, Admin- und Fehlpfade ab' => static function () use ($manifestPath): void {
        $manifest = loadManifest($manifestPath);
        $groups = $manifest['route_groups'] ?? null;

        assertTrue(is_array($groups), 'route_groups fehlen im Manifest.');

        $requiredGroups = ['public', 'auth', 'member', 'admin', 'error'];
        $violations = [];

        foreach ($requiredGroups as $group) {
            $paths = $groups[$group] ?? null;
            if (!is_array($paths) || $paths === []) {
                $violations[] = $group . ': Gruppe fehlt oder ist leer';
                continue;
            }

            foreach ($paths as $path) {
                if (!is_string($path) || $path === '' || $path[0] !== '/') {
                    $violations[] = $group . ': ungültiger Pfad ' . var_export($path, true);
                }
            }
        }

        assertTrue($violations === [], implode(' | ', $violations));
    },
    'Release-Smoke-Manifest hält kritische Pfade und historische Retests doppelfrei vor' => static function () use ($manifestPath): void {
        $manifest = loadManifest($manifestPath);
        $groups = $manifest['route_groups'] ?? [];
        $historicalRetests = $manifest['historical_retests'] ?? null;

        assertTrue(is_array($historicalRetests), 'historical_retests fehlen im Manifest.');

        $allPaths = [];
        foreach ($groups as $paths) {
            if (is_array($paths)) {
                $allPaths = array_merge($allPaths, $paths);
            }
        }

        $allPaths = normalizePaths($allPaths);
        $historicalRetests = normalizePaths(array_filter($historicalRetests, 'is_string'));

        $requiredHistoricalPaths = [
            '/member/dashboard',
            '/member/privacy',
            '/member/media',
            '/login',
            '/admin/comments',
            '/admin/toc',
            '/admin/hub-sites',
            '/admin/site-tables',
            '/admin/users/new',
            '/admin/groups',
        ];

        $violations = [];

        foreach ($requiredHistoricalPaths as $path) {
            if (!in_array($path, $historicalRetests, true)) {
                $violations[] = 'historischer Retest fehlt: ' . $path;
            }
        }

        if (count($allPaths) !== count(array_unique($allPaths))) {
            $violations[] = 'route_groups enthalten doppelte Pfade';
        }

        if (count($historicalRetests) !== count(array_unique($historicalRetests))) {
            $violations[] = 'historical_retests enthalten doppelte Pfade';
        }

        assertTrue($violations === [], implode(' | ', $violations));
    },
    'Deployment-Workflow dokumentiert die Beta-Smoke-Phase inklusive Suite und Pflichtpfaden' => static function () use ($manifestPath, $projectRoot): void {
        $manifest = loadManifest($manifestPath);
        $markers = $manifest['required_workflow_markers'] ?? null;
        assertTrue(is_array($markers) && $markers !== [], 'required_workflow_markers fehlen im Manifest.');

        $workflowDoc = resolveExistingPath([
            $projectRoot . '/DOC/workflow/UPDATE-DEPLOYMENT-WORKFLOW.md',
            $projectRoot . '/docs/workflow/UPDATE-DEPLOYMENT-WORKFLOW.md',
        ]);

        if ($workflowDoc === null) {
            throw new SkippedTestException('Deployment-Workflow-Dokumentation nicht gefunden (DOC/docs workflow-Pfad).');
        }

        $content = file_get_contents($workflowDoc);
        assertTrue($content !== false, 'Deployment-Workflow konnte nicht gelesen werden.');

        $violations = [];
        foreach ($markers as $marker) {
            if (!is_string($marker) || $marker === '') {
                $violations[] = 'ungültiger Workflow-Marker im Manifest';
                continue;
            }

            if (!str_contains($content, $marker)) {
                $violations[] = 'Workflow-Doku fehlt Marker: ' . $marker;
            }
        }

        assertTrue($violations === [], implode(' | ', $violations));
    },
    'CI-Workflow führt die Release-Smoke-Suite aus' => static function () use ($projectRoot): void {
        $workflowPath = resolveExistingPath([
            $projectRoot . '/.github/workflows/security-regression.yml',
        ]);

        if ($workflowPath === null) {
            throw new SkippedTestException('CI-Workflow für Release-Smoke nicht gefunden (.github/workflows/security-regression.yml).');
        }

        $content = file_get_contents($workflowPath);
        assertTrue($content !== false, 'CI-Workflow konnte nicht gelesen werden.');
        assertTrue(str_contains($content, 'php tests/release-smoke/run.php'), 'Release-Smoke-Suite fehlt im CI-Workflow.');
    },
    'EditorJS Inline-Boot schützt serialisierte Submit-Namen und löst Submit-Lock' => static function () use ($projectRoot): void {
        $inlineBoot = resolveExistingPath([
            $projectRoot . '/CMS/admin/partials/editorjs-inline-boot.php',
        ]);
        assertTrue($inlineBoot !== null, 'EditorJS Inline-Boot Partial fehlt.');

        $content = file_get_contents($inlineBoot);
        assertTrue($content !== false, 'EditorJS Inline-Boot konnte nicht gelesen werden.');
        assertTrue(str_contains($content, 'function suppressPlainSubmitNames'), 'Inline-Boot unterdrückt Plain-Textarea-Submit-Namen nicht temporär.');
        assertTrue(str_contains($content, 'submitNative(form, submitter, suppressPlainSubmitNames(definitions))'), 'Inline-Boot reicht die Submit-Name-Sicherung nicht an den nativen Submit weiter.');
        assertTrue(str_contains($content, 'state.submitting = false;'), 'Inline-Boot löst den Submit-Lock nach dem nativen Submit nicht.');
    },
];

$output = [];
$failures = [];

foreach ($tests as $label => $test) {
    try {
        $test();
        $output[] = '[PASS] ' . $label;
	} catch (SkippedTestException $e) {
		$output[] = '[SKIP] ' . $label . ': ' . $e->getMessage();
    } catch (Throwable $e) {
        $message = '[FAIL] ' . $label . ': ' . $e->getMessage();
        $failures[] = $message;
        $output[] = $message;
    }
}

foreach ($output as $line) {
    echo $line . PHP_EOL;
}

if ($failures !== []) {
    exit(1);
}

echo 'Alle Release-Smoke-Checks erfolgreich.' . PHP_EOL;
