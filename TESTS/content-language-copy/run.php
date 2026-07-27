<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use CMS\Services\ContentLanguageCopyService;

/**
 * @throws RuntimeException
 */
function contentLanguageCopyAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$service = ContentLanguageCopyService::getInstance();

$editorParagraph = static function (string $text): string {
    $payload = [
        'time' => 1,
        'blocks' => [
            [
                'type' => 'paragraph',
                'data' => ['text' => $text],
            ],
        ],
        'version' => '2.28.0',
    ];

    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) {
        throw new RuntimeException('EditorJS-Testpayload konnte nicht serialisiert werden.');
    }

    return $encoded;
};

$emptyEditorDocument = json_encode([
    'time' => 1,
    'blocks' => [],
    'version' => '2.28.0',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$emptyParagraphDocument = json_encode([
    'time' => 1,
    'blocks' => [
        [
            'type' => 'paragraph',
            'data' => ['text' => ''],
        ],
    ],
    'version' => '2.28.0',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

contentLanguageCopyAssert(is_string($emptyEditorDocument), 'Leeres EditorJS-Dokument fehlt.');
contentLanguageCopyAssert(is_string($emptyParagraphDocument), 'Leerer Paragraph-Block fehlt.');

$tests = [
    'Seitentitel allein darf content_en nicht leeren' => static function () use ($service, $editorParagraph): void {
        $payload = $service->buildPageGermanToEnglishPayload([
            'title' => 'Nur Titel',
            'slug' => 'nur-titel',
            'content' => '',
            'content_en' => $editorParagraph('Existing English body'),
            'status' => 'published',
        ]);

        contentLanguageCopyAssert($payload === [], 'Titel-only DE→EN-Kopie hätte abgelehnt werden müssen.');
    },
    'Leeres EditorJS-Dokument darf content_en nicht leeren' => static function () use ($service, $emptyEditorDocument, $editorParagraph): void {
        $payload = $service->buildPageGermanToEnglishPayload([
            'title' => 'Titel vorhanden',
            'slug' => 'titel-vorhanden',
            'content' => $emptyEditorDocument,
            'content_en' => $editorParagraph('Keep me'),
            'status' => 'published',
        ]);

        contentLanguageCopyAssert($payload === [], 'Leeres EditorJS-Dokument hätte abgelehnt werden müssen.');
    },
    'Leerer Paragraph-Block darf content_en nicht leeren' => static function () use ($service, $emptyParagraphDocument, $editorParagraph): void {
        $payload = $service->buildPageGermanToEnglishPayload([
            'title' => 'Titel vorhanden',
            'slug' => 'titel-vorhanden',
            'content' => $emptyParagraphDocument,
            'content_en' => $editorParagraph('Keep me'),
            'status' => 'published',
        ]);

        contentLanguageCopyAssert($payload === [], 'Leerer Paragraph-Block hätte abgelehnt werden müssen.');
    },
    'Beitrag mit Titel/Excerpt ohne Body darf content_en nicht leeren' => static function () use ($service, $editorParagraph): void {
        $payload = $service->buildPostGermanToEnglishPayload([
            'title' => 'Nur Titel',
            'slug' => 'nur-titel',
            'content' => '',
            'excerpt' => 'Kurztext ohne Body',
            'content_en' => $editorParagraph('Existing English post body'),
            'status' => 'published',
        ]);

        contentLanguageCopyAssert($payload === [], 'Titel/Excerpt-only DE→EN-Kopie hätte abgelehnt werden müssen.');
    },
    'Seite mit DE-Body erzeugt content_en Payload' => static function () use ($service, $editorParagraph): void {
        $germanBody = $editorParagraph('Deutscher Inhalt');
        $payload = $service->buildPageGermanToEnglishPayload([
            'title' => 'Deutsche Seite',
            'slug' => 'deutsche-seite',
            'content' => $germanBody,
            'status' => 'published',
            'meta_title' => 'Meta DE',
            'meta_description' => 'Beschreibung DE',
        ]);

        contentLanguageCopyAssert($payload !== [], 'DE-Body hätte eine Kopie erzeugen müssen.');
        contentLanguageCopyAssert(($payload['title_en'] ?? null) === 'Deutsche Seite', 'title_en wurde nicht gesetzt.');
        contentLanguageCopyAssert(($payload['slug_en'] ?? null) === 'deutsche-seite', 'slug_en wurde nicht gesetzt.');
        contentLanguageCopyAssert(($payload['content_en'] ?? null) === $germanBody, 'content_en entspricht nicht dem DE-Body.');
    },
    'Beitrag mit DE-Body erzeugt content_en Payload' => static function () use ($service, $editorParagraph): void {
        $germanBody = $editorParagraph('Deutscher Beitrag');
        $payload = $service->buildPostGermanToEnglishPayload([
            'title' => 'Deutscher Beitrag',
            'slug' => 'deutscher-beitrag',
            'content' => $germanBody,
            'excerpt' => 'Auszug',
            'status' => 'draft',
            'tags' => 'news',
        ]);

        contentLanguageCopyAssert($payload !== [], 'DE-Body hätte eine Beitragskopie erzeugen müssen.');
        contentLanguageCopyAssert(($payload['content_en'] ?? null) === $germanBody, 'content_en entspricht nicht dem DE-Body.');
        contentLanguageCopyAssert(($payload['excerpt_en'] ?? null) === 'Auszug', 'excerpt_en wurde nicht gesetzt.');
    },
    'Admin-Module lehnen leere Payloads mit klarer Fehlermeldung ab' => static function (): void {
        $pagesModule = file_get_contents(
            dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR . 'CMS'
            . DIRECTORY_SEPARATOR . 'admin'
            . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . 'pages'
            . DIRECTORY_SEPARATOR . 'PagesModule.php'
        );
        $postsModule = file_get_contents(
            dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR . 'CMS'
            . DIRECTORY_SEPARATOR . 'admin'
            . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . 'posts'
            . DIRECTORY_SEPARATOR . 'PostsModule.php'
        );

        contentLanguageCopyAssert(is_string($pagesModule) && $pagesModule !== '', 'PagesModule ist nicht lesbar.');
        contentLanguageCopyAssert(is_string($postsModule) && $postsModule !== '', 'PostsModule ist nicht lesbar.');
        contentLanguageCopyAssert(
            str_contains($pagesModule, "\$payload === []")
            && str_contains($pagesModule, 'Die deutsche Seitenfassung enthält keinen kopierbaren Inhalt.'),
            'PagesModule behandelt leere Copy-Payloads nicht.'
        );
        contentLanguageCopyAssert(
            str_contains($postsModule, "\$payload === []")
            && str_contains($postsModule, 'Die deutsche Beitragsfassung enthält keinen kopierbaren Inhalt.'),
            'PostsModule behandelt leere Copy-Payloads nicht.'
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

echo 'Alle Content-Language-Copy-Checks erfolgreich.' . PHP_EOL;
