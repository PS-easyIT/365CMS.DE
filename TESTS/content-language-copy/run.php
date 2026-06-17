<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/**
 * @throws RuntimeException
 */
function contentCopyAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function contentCopyReadFile(string $path): string
{
    contentCopyAssert(is_file($path), 'Datei fehlt: ' . $path);
    $content = file_get_contents($path);
    contentCopyAssert(is_string($content) && $content !== '', 'Datei ist leer oder nicht lesbar: ' . $path);

    return $content;
}

$root = dirname(__DIR__, 2);

$tests = [
    'EditorJS Submit sperrt Hintergrund-Sync waehrend der finalen Serialisierung' => static function () use ($root): void {
        $editorInit = contentCopyReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'editor-init.js');
        $inlineBoot = contentCopyReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'editorjs-inline-boot.php');

        contentCopyAssert(str_contains($editorInit, 'changeSyncSuspendCount'), 'EditorJS Factory kennt keinen Submit-Sync-Suspend-Zaehler.');
        contentCopyAssert(str_contains($editorInit, 'editor.cmsSuspendChangeSync = function ()'), 'EditorJS Instanzen stellen keine Submit-Sync-Sperre bereit.');
        contentCopyAssert(str_contains($editorInit, 'window.clearTimeout(toolChangeSyncTimer)'), 'Geplante Change-Syncs werden beim Submit nicht abgebrochen.');
        contentCopyAssert(str_contains($editorInit, '!changeSyncDestroyed && changeSyncSuspendCount === 0'), 'In-flight Change-Sync darf waehrend Submit weiter Hidden-Inputs ueberschreiben.');
        contentCopyAssert(str_contains($inlineBoot, 'acquireSubmitLocks(definitions)'), 'Inline-Boot aktiviert die EditorJS Submit-Sync-Sperre nicht.');
        contentCopyAssert(str_contains($inlineBoot, 'resetSubmitState(releaseSubmitLocks)'), 'Inline-Boot gibt Submit-Sync-Sperren bei Fehlern nicht frei.');
    },
    'EditorJS Submit setzt Lock nach nativer Validierungsablehnung zurueck' => static function () use ($root): void {
        $inlineBoot = contentCopyReadFile($root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'editorjs-inline-boot.php');

        contentCopyAssert(str_contains($inlineBoot, 'function formCanSubmit(form, submitter)'), 'Inline-Boot prueft native Formularvalidierung nicht explizit.');
        contentCopyAssert(str_contains($inlineBoot, 'form.reportValidity()'), 'HTML5-Validierungsfehler werden vor dem nativen Submit nicht erkannt.');
        contentCopyAssert(str_contains($inlineBoot, 'submitter.formNoValidate'), 'formnovalidate-Submitter werden bei manueller Validierung nicht respektiert.');
        contentCopyAssert(str_contains($inlineBoot, 'if (!formCanSubmit(form, submitter))'), 'Submit-Pfad bricht bei nativer Validierungsablehnung nicht kontrolliert ab.');
        contentCopyAssert(str_contains($inlineBoot, 'state.submitting = false;'), 'Submit-Lock wird nach Validierungsablehnung oder Fehlern nicht zurueckgesetzt.');
    },
    'Content-Language-Copy Suite ist im zentralen Manifest lauffaehig registriert' => static function () use ($root): void {
        $manifest = contentCopyReadFile($root . DIRECTORY_SEPARATOR . 'TESTS' . DIRECTORY_SEPARATOR . 'manifest.php');
        $gitignore = contentCopyReadFile($root . DIRECTORY_SEPARATOR . '.gitignore');

        contentCopyAssert(str_contains($manifest, "'content-language-copy'"), 'content-language-copy fehlt im TESTS-Manifest.');
        contentCopyAssert(str_contains($gitignore, '!/TESTS/content-language-copy/'), 'content-language-copy-Verzeichnis ist nicht per .gitignore freigegeben.');
        contentCopyAssert(str_contains($gitignore, '!/TESTS/content-language-copy/run.php'), 'content-language-copy/run.php ist nicht per .gitignore freigegeben.');
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

echo 'Alle Content-Language-Copy-/EditorJS-Smoke-Checks erfolgreich.' . PHP_EOL;
