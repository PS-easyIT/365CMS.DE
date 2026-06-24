<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/**
 * @throws RuntimeException
 */
function contentEditorAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function contentEditorReadFile(string $path): string
{
    contentEditorAssert(is_file($path), 'Datei fehlt: ' . $path);
    $content = file_get_contents($path);
    contentEditorAssert(is_string($content) && $content !== '', 'Datei ist leer oder nicht lesbar: ' . $path);

    return $content;
}

$root = dirname(__DIR__, 2);

$tests = [
    'Admin-Editor beansprucht EditorJS vor dem Runtime-Wait' => static function () use ($root): void {
        $script = contentEditorReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'admin-content-editor.js');
        $ownershipPosition = strpos($script, 'window.cmsAdminContentEditorOwnsEditorJs = true;');
        $runtimeWaitPosition = strpos($script, 'waitForEditorJsCore(editorJsConfig)');

        contentEditorAssert($ownershipPosition !== false, 'admin-content-editor.js setzt keinen EditorJS-Ownership-Marker.');
        contentEditorAssert($runtimeWaitPosition !== false, 'admin-content-editor.js wartet nicht mehr sichtbar auf EditorJS.');
        contentEditorAssert($ownershipPosition < $runtimeWaitPosition, 'Der Ownership-Marker muss vor dem Runtime-Wait gesetzt werden.');
    },
    'Inline-EditorJS-Boot überspringt bei Admin-Ownership vor Submit-Binding' => static function () use ($root): void {
        $inlineBoot = contentEditorReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'editorjs-inline-boot.php');
        $guardPosition = strpos($inlineBoot, 'window.cmsAdminContentEditorOwnsEditorJs === true');
        $bindPosition = strpos($inlineBoot, 'bindSubmit(form, definitions)');
        $editorInitPosition = strpos($inlineBoot, 'Promise.all(definitions.map(initDefinition))');

        contentEditorAssert($guardPosition !== false, 'Inline-Boot prüft den Admin-Ownership-Marker nicht.');
        contentEditorAssert($bindPosition !== false, 'Inline-Boot enthält kein Submit-Binding mehr.');
        contentEditorAssert($editorInitPosition !== false, 'Inline-Boot enthält keine Editor-Initialisierung mehr.');
        contentEditorAssert($guardPosition < $bindPosition, 'Inline-Boot muss vor dem Submit-Binding aussteigen.');
        contentEditorAssert($guardPosition < $editorInitPosition, 'Inline-Boot muss vor Editor-Initialisierung und Submit-Name-Mutationen aussteigen.');
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

echo 'Alle Content-Editor-Smoke-Checks erfolgreich.' . PHP_EOL;
