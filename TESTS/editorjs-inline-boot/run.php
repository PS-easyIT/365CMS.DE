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
$adminEditorSourcePath = dirname(__DIR__, 2)
    . DIRECTORY_SEPARATOR . 'CMS'
    . DIRECTORY_SEPARATOR . 'assets'
    . DIRECTORY_SEPARATOR . 'js'
    . DIRECTORY_SEPARATOR . 'admin-content-editor.js';
$adminEditorSource = file_get_contents($adminEditorSourcePath);

editorInlineAssert(is_string($source) && $source !== '', 'EditorJS-Inline-Boot ist nicht lesbar.');
editorInlineAssert(is_string($adminEditorSource) && $adminEditorSource !== '', 'Admin-Content-Editor ist nicht lesbar.');

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
    'Inline-Boot bewahrt Plaintext-Änderungen während der Initialisierung' => static function () use ($source): void {
        editorInlineAssert(
            str_contains($source, 'plainDirty: !!(textarea && textarea.value !== textarea.defaultValue)')
                && str_contains($source, 'entry.plainDirty = true;'),
            'Änderungen im sichtbaren Plaintextfeld werden während der Initialisierung nicht verfolgt.'
        );
        editorInlineAssert(
            str_contains($source, 'if (entry && entry.plainDirty && textarea) {')
                && str_contains($source, "mark(definition, 'fallback', 'inline-plain-edited-during-init');"),
            'Der Inline-Boot kann ein geändertes Plaintextfeld beim Speichern oder Ready-Handoff überschreiben.'
        );
    },
    'Externer Editor bewahrt Plaintext-Änderungen während der Initialisierung' => static function () use ($adminEditorSource): void {
        $syncAt = strpos($adminEditorSource, 'if (plainState && plainState.dirty) {');
        $createAt = strpos($adminEditorSource, "logEditor('info', '[EJS-CHAIN-BIND-CREATE]");
        editorInlineAssert(
            $syncAt !== false && $createAt !== false && $syncAt < $createAt,
            'Vor EditorJS-Erzeugung geänderter Plaintext wird nicht in den Start-Payload übernommen.'
        );
        editorInlineAssert(
            str_contains($adminEditorSource, 'function preserveDirtyPlainEditor(definition)')
                && str_contains($adminEditorSource, "setEditorStateMarker(definition, 'fallback', 'plain-edited-during-init');"),
            'Nach EditorJS-Erzeugung geänderter Plaintext wird beim Ready-Handoff nicht geschützt.'
        );
        editorInlineAssert(
            str_contains($adminEditorSource, 'if (plainState && plainState.dirty && !currentEntry.ready) {'),
            'EditorJS-onChange kann geänderten Plaintext vor dem Ready-Handoff überschreiben.'
        );
    },
    'Externer Fallback übergibt den Submit-Namen an das JSON-Feld' => static function () use ($adminEditorSource): void {
        editorInlineAssert(
            str_contains($adminEditorSource, "submitName = input.dataset ? String(input.dataset.editorSubmitName || '') : '';")
                && str_contains($adminEditorSource, "input.setAttribute('name', submitName);"),
            'Das serialisierte JSON-Feld erhält im externen Fallback keinen Submit-Namen.'
        );
        editorInlineAssert(
            str_contains($adminEditorSource, 'inputName: inputName')
                && str_contains($adminEditorSource, "entry.input.removeAttribute('name');"),
            'Der temporäre Name des JSON-Felds wird nach Browservalidierung nicht wiederhergestellt.'
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
