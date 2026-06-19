<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require_once ABSPATH . 'admin/modules/pages/PagesModule.php';
require_once ABSPATH . 'admin/modules/posts/PostsModule.php';

/**
 * @throws RuntimeException
 */
function editorFallbackAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function invokeEditorFallbackNormalizer(string $className, string $submitted, string $original): string
{
    $reflection = new ReflectionClass($className);
    $module = $reflection->newInstanceWithoutConstructor();
    $method = $reflection->getMethod('preserveOriginalEditorContentIfUnchanged');
    $method->setAccessible(true);
    $result = $method->invoke($module, $submitted, $original);

    editorFallbackAssert(is_string($result), $className . ' returned a non-string content value.');

    return $result;
}

function assertFallbackPayload(string $payload, string $expectedText, string $label): void
{
    $decoded = json_decode($payload, true);
    editorFallbackAssert(is_array($decoded), $label . ' did not return JSON.');
    editorFallbackAssert(($decoded['version'] ?? '') === 'cms-editor-fallback', $label . ' did not mark fallback content.');
    editorFallbackAssert(isset($decoded['blocks']) && is_array($decoded['blocks']), $label . ' has no EditorJS blocks.');
    editorFallbackAssert(($decoded['blocks'][0]['type'] ?? '') === 'paragraph', $label . ' did not create a paragraph block.');
    editorFallbackAssert(($decoded['blocks'][0]['data']['text'] ?? '') === $expectedText, $label . ' stored unexpected fallback text.');
}

$originalEditorJson = json_encode([
    'time' => 1,
    'version' => '2.31.0',
    'blocks' => [
        [
            'type' => 'paragraph',
            'data' => ['text' => 'Original'],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$newEditorJson = json_encode([
    'time' => 2,
    'version' => '2.31.0',
    'blocks' => [
        [
            'type' => 'paragraph',
            'data' => ['text' => 'Structured'],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

editorFallbackAssert(is_string($originalEditorJson) && is_string($newEditorJson), 'Test fixtures could not be encoded.');

$tests = [
    'Pages fallback edit remains EditorJS JSON' => static function () use ($originalEditorJson): void {
        $result = invokeEditorFallbackNormalizer(PagesModule::class, "Changed\nLine", $originalEditorJson);
        assertFallbackPayload($result, 'Changed<br>Line', 'Pages fallback edit');
    },
    'Posts fallback edit remains EditorJS JSON' => static function () use ($originalEditorJson): void {
        $result = invokeEditorFallbackNormalizer(PostsModule::class, "Changed\nLine", $originalEditorJson);
        assertFallbackPayload($result, 'Changed<br>Line', 'Posts fallback edit');
    },
    'Unchanged plaintext fallback preserves original blocks' => static function () use ($originalEditorJson): void {
        editorFallbackAssert(
            invokeEditorFallbackNormalizer(PagesModule::class, 'Original', $originalEditorJson) === $originalEditorJson,
            'Pages unchanged fallback text did not preserve the original JSON.'
        );
        editorFallbackAssert(
            invokeEditorFallbackNormalizer(PostsModule::class, 'Original', $originalEditorJson) === $originalEditorJson,
            'Posts unchanged fallback text did not preserve the original JSON.'
        );
    },
    'New structured EditorJS submissions are not re-encoded as plaintext' => static function () use ($newEditorJson): void {
        editorFallbackAssert(
            invokeEditorFallbackNormalizer(PagesModule::class, $newEditorJson, '') === $newEditorJson,
            'Pages new structured payload was changed.'
        );
        editorFallbackAssert(
            invokeEditorFallbackNormalizer(PostsModule::class, $newEditorJson, '') === $newEditorJson,
            'Posts new structured payload was changed.'
        );
    },
    'Inline boot serializes visible fallback textarea into hidden JSON input' => static function (): void {
        $inlineBootPath = dirname(__DIR__, 2) . '/CMS/admin/partials/editorjs-inline-boot.php';
        $inlineBoot = file_get_contents($inlineBootPath);
        editorFallbackAssert(is_string($inlineBoot) && $inlineBoot !== '', 'Inline boot file is not readable.');
        editorFallbackAssert(str_contains($inlineBoot, 'function createPlaintextFallbackData'), 'Inline boot has no plaintext fallback serializer.');
        editorFallbackAssert(str_contains($inlineBoot, "input.value = stringifyData(createPlaintextFallbackData(textarea.value || ''));"), 'Inline boot does not write fallback JSON to the hidden input.');
        editorFallbackAssert(str_contains($inlineBoot, "input.setAttribute('name', submitName);"), 'Inline boot does not submit the hidden JSON input.');
        editorFallbackAssert(str_contains($inlineBoot, 'config.uploadContext'), 'Inline boot does not read nested upload context.');
        editorFallbackAssert(str_contains($inlineBoot, 'uploadContext.draftKey'), 'Inline boot does not forward draft upload keys.');
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

echo 'EditorJS-Fallback-Smoke-Checks erfolgreich.' . PHP_EOL;
