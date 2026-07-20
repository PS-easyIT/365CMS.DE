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

$root = dirname(__DIR__, 2);
$sourcePath = $root
    . DIRECTORY_SEPARATOR . 'CMS'
    . DIRECTORY_SEPARATOR . 'admin'
    . DIRECTORY_SEPARATOR . 'partials'
    . DIRECTORY_SEPARATOR . 'editorjs-inline-boot.php';
$adminEditorSourcePath = $root
    . DIRECTORY_SEPARATOR . 'CMS'
    . DIRECTORY_SEPARATOR . 'assets'
    . DIRECTORY_SEPARATOR . 'js'
    . DIRECTORY_SEPARATOR . 'admin-content-editor.js';
$source = file_get_contents($sourcePath);
$adminEditorSource = file_get_contents($adminEditorSourcePath);

editorInlineAssert(is_string($source) && $source !== '', 'EditorJS-Inline-Boot ist nicht lesbar.');
editorInlineAssert(is_string($adminEditorSource) && $adminEditorSource !== '', 'Admin-Content-Editor ist nicht lesbar.');

require_once $root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'PagesModule.php';
require_once $root . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'posts' . DIRECTORY_SEPARATOR . 'PostsModule.php';

function editorInlinePreserveContent(string $className, string $submitted, string $original): string
{
    $reflection = new ReflectionClass($className);
    $instance = $reflection->newInstanceWithoutConstructor();
    $method = $reflection->getMethod('preserveOriginalEditorContentIfUnchanged');
    $method->setAccessible(true);

    return (string) $method->invoke($instance, $submitted, $original);
}

$tests = [
    'Externer Editor-Boot verhindert doppelte Editor- und Submit-Bindings' => static function () use ($source, $adminEditorSource): void {
        $bootStart = strpos($adminEditorSource, 'function bootContentEditor()');
        $claimAt = strpos($adminEditorSource, "editorJsOwnerForm.dataset.cmsEditorJsPrimaryBootBound = '1';", $bootStart === false ? 0 : $bootStart);
        $waitAt = strpos($adminEditorSource, 'waitForEditorJsCore(editorJsConfig).then', $bootStart === false ? 0 : $bootStart);
        $guardAt = strpos($source, "form.dataset.cmsEditorJsPrimaryBootBound === '1'");
        $bindAt = strpos($source, 'bindSubmit(form, definitions);');

        editorInlineAssert(
            $bootStart !== false && $claimAt !== false && $waitAt !== false && $claimAt < $waitAt,
            'Admin-Content-Editor beansprucht den Editor-Boot nicht vor der asynchronen Runtime-Wartephase.'
        );
        editorInlineAssert(
            $guardAt !== false && $bindAt !== false && $guardAt < $bindAt,
            'Inline-Boot bindet trotz bereits aktivem Admin-Content-Editor einen zweiten Submit-Handler.'
        );
    },
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
    'Fehlgeschlagene Editor-Instanz kann den Plain-Fallback nicht überschreiben' => static function () use ($source): void {
        editorInlineAssert(
            str_contains($source, 'entry.failed = true;')
                && str_contains($source, 'entry.editor = null;'),
            'Fehlgeschlagene Editor-Instanz bleibt für spätere Saves aktiv.'
        );
        editorInlineAssert(
            substr_count($source, 'if (!entry || entry.failed)') >= 2,
            'Verspätete Ready-/Change-Callbacks können den aktivierten Plain-Fallback überschreiben.'
        );
    },
    'Unverändertes Legacy-HTML bleibt bei Plain-Fallback-Saves vollständig erhalten' => static function (): void {
        $original = '<h2>Einleitung</h2><p>Text mit <a href="/ziel">Link</a>.</p><img src="/bild.jpg" alt="Bild">';
        $fallback = json_encode([
            'time' => 1,
            'version' => 'cms-editor-fallback',
            'blocks' => [[
                'type' => 'paragraph',
                'data' => ['text' => 'EinleitungText mit Link.'],
            ]],
        ], JSON_UNESCAPED_SLASHES);
        $changedFallback = json_encode([
            'time' => 1,
            'version' => 'cms-editor-fallback',
            'blocks' => [[
                'type' => 'paragraph',
                'data' => ['text' => 'Bewusst geänderter Inhalt'],
            ]],
        ], JSON_UNESCAPED_SLASHES);

        editorInlineAssert(is_string($fallback) && is_string($changedFallback), 'Fallback-Testdaten konnten nicht erzeugt werden.');

        foreach ([PagesModule::class, PostsModule::class] as $className) {
            editorInlineAssert(
                editorInlinePreserveContent($className, $fallback, $original) === $original,
                $className . ' überschreibt unverändertes Legacy-HTML mit dem Plain-Fallback.'
            );
            editorInlineAssert(
                editorInlinePreserveContent($className, $changedFallback, $original) === $changedFallback,
                $className . ' verwirft eine bewusste Änderung im Plain-Fallback.'
            );
        }
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
