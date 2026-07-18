<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/**
 * @throws RuntimeException
 */
function editorInlineAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$sourcePath = dirname(__DIR__, 2)
    . DIRECTORY_SEPARATOR . 'CMS'
    . DIRECTORY_SEPARATOR . 'admin'
    . DIRECTORY_SEPARATOR . 'partials'
    . DIRECTORY_SEPARATOR . 'editorjs-inline-boot.php';
$source = file_get_contents($sourcePath);

editorInlineAssert(is_string($source) && $source !== '', 'EditorJS-Inline-Boot ist nicht lesbar.');

$tests = [
    'Plain-Fallback wird als EditorJS-JSON serialisiert' => static function () use ($source): void {
        editorInlineAssert(
            str_contains($source, "version: 'cms-editor-fallback'"),
            'Fallback-Payload enthält keine EditorJS-Fallback-Kennung.'
        );
        editorInlineAssert(
            str_contains($source, 'input.value = stringifyData(createPlaintextFallbackData(textarea.value));'),
            'Plain-Textarea wird beim Submit nicht als EditorJS-JSON serialisiert.'
        );
    },
    'Serialisiertes Hidden-Feld übernimmt den Submit-Namen' => static function () use ($source): void {
        editorInlineAssert(
            str_contains($source, 'function prepareSerializedSubmitFields(definitions)'),
            'Submit-Feldvorbereitung fehlt.'
        );
        editorInlineAssert(
            str_contains($source, "input.setAttribute('name', submitName);"),
            'Hidden-Editorfeld übernimmt seinen Submit-Namen nicht.'
        );
        editorInlineAssert(
            str_contains($source, 'textarea.disabled = true;')
                && str_contains($source, "textarea.removeAttribute('name');"),
            'Stale Plain-Textarea wird beim nativen Submit nicht unterdrückt.'
        );
    },
    'Submit-Zustand wird nach Browservalidierung wieder freigegeben' => static function () use ($source): void {
        $unlockAt = strpos($source, "state.submitting = false;\n                submitNative(form, submitter, definitions);");
        editorInlineAssert(
            $unlockAt !== false,
            'Submit-Lock wird vor requestSubmit nicht freigegeben.'
        );
        editorInlineAssert(
            str_contains($source, 'restoreSerializedSubmitFields();'),
            'Temporäre Submit-Feldzustände werden nicht wiederhergestellt.'
        );
    },
];

$failures = [];
foreach ($tests as $label => $test) {
    try {
        $test();
        echo "[PASS] {$label}" . PHP_EOL;
    } catch (Throwable $error) {
        $message = "[FAIL] {$label}: {$error->getMessage()}";
        $failures[] = $message;
        echo $message . PHP_EOL;
    }
}

if ($failures !== []) {
    exit(1);
}
