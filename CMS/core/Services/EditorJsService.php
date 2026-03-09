<?php
/**
 * Editor.js Integration Service
 *
 * Verwaltet Editor.js Asset-Loading, stellt render()-API bereit
 * und bietet Upload-/Fetch-Endpoints für Bilder, Dateien und Link-Metadaten.
 *
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services;

if (!defined('ABSPATH')) {
    exit;
}

final class EditorJsService
{
    private static ?self $instance = null;
    private static bool $assetsEnqueued = false;
    private static int $editorCount = 0;

    /** @var string[] */
    private const EDITOR_JS_FILES = [
        'editorjs.umd.js',
        'header.umd.js',
        'paragraph.umd.js',
        'editorjs-list.umd.js',
        'code.umd.js',
        'delimiter.umd.js',
        'embed.umd.js',
        'image.umd.js',
        'inline-code.umd.js',
        'link.umd.js',
        'quote.umd.js',
        'raw.umd.js',
        'table.umd.js',
        'underline.umd.js',
        'warning.umd.js',
        'attaches.umd.js',
        'accordion.umd.js',
        'carousel.umd.js',
        'columns.umd.js',
        'cropper-tune.umd.js',
        'drag-drop.umd.js',
        'drawing-tool.umd.js',
        'image-gallery.umd.js',
        'spoiler.umd.js',
        'undo.umd.js',
    ];

    /** @var string[] */
    private const EDITOR_CSS_FILES = [
        'cropper-tune.css',
    ];

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        \CMS\Hooks::addAction('admin_head', [$this, 'enqueueEditorAssets']);
    }

    /**
     * Editor.js Block-Editor rendern.
     *
     * @param string $name      Feld-Name (hidden input)
     * @param string $content   Gespeicherter JSON-String (oder leerer String)
     * @param array  $settings  Optionale Einstellungen (height, placeholder, etc.)
     */
    public function render(string $name, string $content = '', array $settings = []): string
    {
        self::$editorCount++;
        $editorNum = self::$editorCount;
        $holderId  = $name . '_editorjs_' . $editorNum;
        $hiddenId  = $name . '_hidden_' . $editorNum;

        if (!self::$assetsEnqueued) {
            $this->enqueueEditorAssets();
        }

        $minHeight   = (int)($settings['height'] ?? 400);
        $escapedData = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        $siteUrl     = defined('SITE_URL') ? SITE_URL : '';
        $contentWidth = max(320, (int)($settings['content_width'] ?? 1100));
        $expandedContentWidth = max($contentWidth, (int)($settings['content_width_expanded'] ?? $contentWidth));
        $contentPaddingX = max(0, (int)($settings['content_padding_x'] ?? 50));
        $contextClass = preg_replace('/[^a-z0-9_-]/i', '', (string)($settings['context'] ?? 'default')) ?: 'default';
        $csrfToken   = class_exists(\CMS\Security::class)
            ? \CMS\Security::instance()->generateToken('editorjs_media')
            : '';

        ob_start();
        ?>
        <input type="hidden"
               id="<?php echo htmlspecialchars($hiddenId, ENT_QUOTES); ?>"
               name="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>"
               value="<?php echo $escapedData; ?>">

           <div class="editorjs-wrap editorjs-wrap--<?php echo htmlspecialchars($contextClass, ENT_QUOTES); ?>"
               id="<?php echo htmlspecialchars($holderId, ENT_QUOTES); ?>_wrap"
               style="--editorjs-content-width:<?php echo $contentWidth; ?>px; --editorjs-content-width-expanded:<?php echo $expandedContentWidth; ?>px; --editorjs-content-padding-x:<?php echo $contentPaddingX; ?>px;">
            <div class="editorjs-toolbar" id="<?php echo htmlspecialchars($holderId, ENT_QUOTES); ?>_toolbar">
                <button type="button" data-block="header" data-level="2" title="Überschrift H2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 4v16"/><path d="M7 12h10"/><path d="M17 4v16"/></svg>
                    <span>H2</span>
                </button>
                <button type="button" data-block="paragraph" title="Textabsatz">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h12"/></svg>
                    <span>Text</span>
                </button>
                <button type="button" data-block="list" title="Liste">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6h11"/><path d="M9 12h11"/><path d="M9 18h11"/><circle cx="5" cy="6" r="1" fill="currentColor"/><circle cx="5" cy="12" r="1" fill="currentColor"/><circle cx="5" cy="18" r="1" fill="currentColor"/></svg>
                    <span>Liste</span>
                </button>
                <button type="button" data-block="image" title="Bild">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="9" cy="9" r="1.5"/><path d="M3 16l5-5c1-.9 2.1-.9 3 0l5 5"/><path d="M14 14l1-1c1-.9 2.1-.9 3 0l3 3"/></svg>
                    <span>Bild</span>
                </button>
                <button type="button" data-block="embed" title="Embed">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 9l-3 3l3 3"/><path d="M16 9l3 3l-3 3"/><path d="M14 5l-4 14"/></svg>
                    <span>Embed</span>
                </button>
                <button type="button" data-block="columns" title="Spalten">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="7" height="14" rx="1"/><rect x="14" y="5" width="7" height="14" rx="1"/></svg>
                    <span>Spalten</span>
                </button>
                <button type="button" data-block="accordion" title="Accordion">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 7h14"/><path d="M5 12h14"/><path d="M9 17h6"/></svg>
                    <span>Akk.</span>
                </button>
                <button type="button" data-block="table" title="Tabelle (3×3)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M3 15h18"/><path d="M9 3v18"/></svg>
                    <span>Tabelle</span>
                </button>
                <button type="button" data-block="quote" title="Zitat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 11h-4a1 1 0 01-1-1v-3a1 1 0 011-1h3a1 1 0 011 1v6c0 2.667-1.333 4.333-4 5"/><path d="M19 11h-4a1 1 0 01-1-1v-3a1 1 0 011-1h3a1 1 0 011 1v6c0 2.667-1.333 4.333-4 5"/></svg>
                    <span>Zitat</span>
                </button>
                <button type="button" data-block="delimiter" title="Trennlinie">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/></svg>
                    <span>Trenner</span>
                </button>
                <button type="button" data-block="spacer" data-height="15" title="Leerraum / Abstand">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16"/><path d="M8 8l4-4l4 4"/><path d="M8 16l4 4l4-4"/></svg>
                    <span>Abstand</span>
                </button>
            </div>

            <div id="<?php echo htmlspecialchars($holderId, ENT_QUOTES); ?>"
                 class="editorjs-holder"
                 style="min-height:<?php echo $minHeight; ?>px;"></div>

            <div class="editorjs-statusbar">
                <span class="editorjs-statusbar__hint">Tippe <kbd>/</kbd> oder nutze das <strong>+</strong>-Menü für alle Plugins</span>
                <span class="editorjs-statusbar__count" id="<?php echo htmlspecialchars($holderId, ENT_QUOTES); ?>_count"></span>
            </div>
        </div>

        <script>
        (function() {
            function initEditorJs<?php echo $editorNum; ?>() {
                var holderEl = document.getElementById('<?php echo $holderId; ?>');
                var hiddenEl = document.getElementById('<?php echo $hiddenId; ?>');
                if (!holderEl || !hiddenEl) {
                    return;
                }

                var raw = hiddenEl.value || '';
                if (typeof window.createCmsEditor !== 'function') {
                    console.error('editor-init.js not loaded');
                    return;
                }

                var editor = window.createCmsEditor(
                    '<?php echo $holderId; ?>',
                    raw,
                    '<?php echo htmlspecialchars($siteUrl, ENT_QUOTES); ?>/api/media',
                    '<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>'
                );

                var toolbar = document.getElementById('<?php echo $holderId; ?>_toolbar');
                if (toolbar) {
                    toolbar.addEventListener('click', function(event) {
                        var btn = event.target.closest('button[data-block]');
                        if (!btn || !editor || !editor.blocks) {
                            return;
                        }

                        var blockType = btn.getAttribute('data-block');
                        var blockData = {};
                        var level = btn.getAttribute('data-level');
                        var height = btn.getAttribute('data-height');
                        if (level) {
                            blockData.level = parseInt(level, 10);
                        }
                        if (height) {
                            blockData.height = parseInt(height, 10);
                            blockData.preset = height + 'px';
                        }

                        editor.blocks.insert(blockType, blockData);

                        var lastIndex = editor.blocks.getBlocksCount() - 1;
                        if (editor.caret && typeof editor.caret.setToBlock === 'function') {
                            editor.caret.setToBlock(lastIndex, 'start');
                        }
                    });
                }

                var countEl = document.getElementById('<?php echo $holderId; ?>_count');
                var updateBlockCount = function() {
                    if (!countEl || !editor || !editor.blocks) {
                        return;
                    }
                    var count = editor.blocks.getBlocksCount();
                    countEl.textContent = count + (count === 1 ? ' Block' : ' Blöcke');
                };

                editor.isReady.then(function() {
                    updateBlockCount();
                });

                var intervalId = window.setInterval(updateBlockCount, 2000);
                window.setTimeout(function() {
                    window.clearInterval(intervalId);
                }, 300000);

                var form = holderEl.closest('form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        if (form.dataset.editorjsSaving === 'true') {
                            return;
                        }

                        e.preventDefault();
                        form.dataset.editorjsSaving = 'true';

                        editor.save().then(function(outputData) {
                            hiddenEl.value = JSON.stringify(outputData);
                            form.submit();
                        }).catch(function(err) {
                            console.error('Editor.js save error:', err);
                            form.dataset.editorjsSaving = '';
                            form.submit();
                        });
                    });
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initEditorJs<?php echo $editorNum; ?>);
            } else {
                initEditorJs<?php echo $editorNum; ?>();
            }
        })();
        </script>
        <?php

        return (string)ob_get_clean();
    }

    /**
     * Liefert die Admin-Assets für Editor.js zurück.
     *
     * @return array{css: string[], js: string[]}
     */
    public function getPageAssets(): array
    {
        return [
            'css' => $this->getEditorCssUrls(),
            'js'  => $this->getEditorJsUrls(),
        ];
    }

    /**
     * Editor.js Assets laden (CSS + JS + Plugins).
     */
    public function enqueueEditorAssets(): void
    {
        if (self::$assetsEnqueued) {
            return;
        }

        echo "\n<!-- Editor.js Assets -->\n";

        foreach ($this->getEditorCssUrls() as $cssUrl) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }

        foreach ($this->getEditorJsUrls() as $jsUrl) {
            echo '<script src="' . htmlspecialchars($jsUrl, ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
        }

        echo "<!-- /Editor.js Assets -->\n\n";
        self::$assetsEnqueued = true;
    }

    /**
     * Zentrale API für Editor.js Uploads und Link-Metadaten.
     */
    public function handleMediaApiRequest(): void
    {
        $this->ensureAdminAccess();
        $this->verifyMediaToken();

        $action = (string)($_REQUEST['action'] ?? '');

        try {
            switch ($action) {
                case 'list_images':
                    $this->json($this->handleImageLibraryList());
                    break;

                case 'upload_image':
                    $this->json($this->handleFileUpload('image', true));
                    break;

                case 'upload_featured':
                    $this->json($this->handleFeaturedImageUpload());
                    break;

                case 'upload_file':
                    $this->json($this->handleFileUpload('file', false));
                    break;

                case 'fetch_image':
                    $this->json($this->handleImageFetchByUrl());
                    break;

                case 'fetch_link':
                    $this->json($this->handleLinkMetadataFetch());
                    break;

                default:
                    $this->json([
                        'success' => 0,
                        'message' => 'Unbekannte Editor.js-Media-Aktion.',
                    ], 400);
            }
        } catch (\Throwable $e) {
            $this->json([
                'success' => 0,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Editor.js JSON-Daten sanitieren (Block-Typen + Inline-HTML prüfen).
     */
    public function sanitize(string $json): string
    {
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['blocks']) || !is_array($data['blocks'])) {
            return '{"blocks":[]}';
        }

        $cleaned = $this->sanitizePayload($data);
        return (string)json_encode($cleaned, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return string[]
     */
    private function getEditorJsUrls(): array
    {
        $urls = [];

        foreach (self::EDITOR_JS_FILES as $file) {
            $urls[] = $this->buildAssetUrl('editorjs/' . $file);
        }

        $urls[] = $this->buildAssetUrl('js/editor-init.js');

        return $urls;
    }

    /**
     * @return string[]
     */
    private function getEditorCssUrls(): array
    {
        $urls = [];

        foreach (self::EDITOR_CSS_FILES as $file) {
            $assetPath = defined('ASSETS_PATH') ? ASSETS_PATH . 'editorjs/' . $file : null;
            if ($assetPath !== null && file_exists($assetPath)) {
                $urls[] = $this->buildAssetUrl('editorjs/' . $file);
            }
        }

        return $urls;
    }

    private function buildAssetUrl(string $relativePath): string
    {
        $siteUrl = defined('ASSETS_URL')
            ? rtrim((string)ASSETS_URL, '/')
            : rtrim((defined('SITE_URL') ? (string)SITE_URL : ''), '/') . '/assets';

        $absolutePath = defined('ASSETS_PATH') ? ASSETS_PATH . str_replace('/', DIRECTORY_SEPARATOR, $relativePath) : null;
        $version = ($absolutePath !== null && file_exists($absolutePath)) ? (string)filemtime($absolutePath) : '';

        return $siteUrl . '/' . ltrim(str_replace('\\', '/', $relativePath), '/') . ($version !== '' ? '?v=' . $version : '');
    }

    private function ensureAdminAccess(): void
    {
        if (!class_exists(\CMS\Auth::class) || !\CMS\Auth::instance()->isAdmin()) {
            $this->json([
                'success' => 0,
                'message' => 'Nicht autorisiert.',
            ], 403);
        }
    }

    private function verifyMediaToken(): void
    {
        $token = '';

        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
        } elseif (isset($_POST['csrf_token'])) {
            $token = (string)$_POST['csrf_token'];
        }

        if ($token === '' || !class_exists(\CMS\Security::class) || !\CMS\Security::instance()->verifyPersistentToken($token, 'editorjs_media')) {
            $this->json([
                'success' => 0,
                'message' => 'Ungültiges Sicherheitstoken für Editor.js-Uploads.',
            ], 403);
        }
    }

    /**
     * @return array{success:int,file?:array<string,mixed>,message?:string}
     */
    private function handleFileUpload(string $fieldName, bool $imagesOnly, string $targetPath = 'editorjs'): array
    {
        if (empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
            return [
                'success' => 0,
                'message' => 'Keine Datei empfangen.',
            ];
        }

        $file = $_FILES[$fieldName];
        $extension = strtolower((string)pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));

        if ($imagesOnly && !in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp'], true)) {
            return [
                'success' => 0,
                'message' => 'Nur Bilddateien sind erlaubt.',
            ];
        }

        $mediaService = MediaService::getInstance();
        $storedFile = $mediaService->uploadFile($file, trim($targetPath, '/'));

        if ($storedFile instanceof \CMS\WP_Error) {
            return [
                'success' => 0,
                'message' => $storedFile->get_error_message(),
            ];
        }

        $normalizedTargetPath = trim($targetPath, '/');
        $relativePath = ($normalizedTargetPath !== '' ? $normalizedTargetPath . '/' : '') . ltrim((string)$storedFile, '/');
        $fullPath = rtrim((string)UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $publicUrl = rtrim((string)UPLOAD_URL, '/') . '/' . $relativePath;

        return [
            'success' => 1,
            'file' => [
                'url' => $publicUrl,
                'name' => basename((string)$storedFile),
                'size' => file_exists($fullPath) ? filesize($fullPath) : 0,
                'extension' => strtolower((string)pathinfo((string)$storedFile, PATHINFO_EXTENSION)),
            ],
        ];
    }

    /**
     * @return array{success:int,file?:array<string,mixed>,message?:string}
     */
    private function handleImageFetchByUrl(): array
    {
        $payload = $this->getJsonInput();
        $url = trim((string)($payload['url'] ?? $_POST['url'] ?? ''));

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => 0,
                'message' => 'Ungültige Bild-URL.',
            ];
        }

        $download = $this->downloadRemoteFile($url, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/bmp']);
        if ($download['success'] !== true) {
            return [
                'success' => 0,
                'message' => (string)($download['message'] ?? 'Bild konnte nicht geladen werden.'),
            ];
        }

        $tmpFile = (string)$download['tmpFile'];
        $tmpName = basename((string)parse_url($url, PHP_URL_PATH)) ?: ('remote-image.' . ($download['extension'] ?? 'jpg'));

        $uploadPayload = [
            'name' => $tmpName,
            'type' => (string)($download['contentType'] ?? 'application/octet-stream'),
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpFile) ?: 0,
        ];

        $result = MediaService::getInstance()->uploadFile($uploadPayload, 'editorjs');

        if (file_exists($tmpFile)) {
            @unlink($tmpFile);
        }

        if ($result instanceof \CMS\WP_Error) {
            return [
                'success' => 0,
                'message' => $result->get_error_message(),
            ];
        }

        $relativePath = 'editorjs/' . ltrim((string)$result, '/');
        $fullPath = rtrim((string)UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        return [
            'success' => 1,
            'file' => [
                'url' => rtrim((string)UPLOAD_URL, '/') . '/' . $relativePath,
                'name' => basename((string)$result),
                'size' => file_exists($fullPath) ? filesize($fullPath) : 0,
                'extension' => strtolower((string)pathinfo((string)$result, PATHINFO_EXTENSION)),
            ],
        ];
    }

    /**
     * @return array{success:int,items?:array<int,array<string,mixed>>,message?:string}
     */
    private function handleImageLibraryList(): array
    {
        $items = [];
        $rootPath = rtrim((string)UPLOAD_PATH, '/\\');
        $rootUrl = rtrim((string)UPLOAD_URL, '/');

        if (!is_dir($rootPath)) {
            return [
                'success' => 1,
                'items' => [],
            ];
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'svg'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            if (!in_array($extension, $allowedExtensions, true)) {
                continue;
            }

            $absolutePath = $file->getPathname();
            $relativePath = ltrim(str_replace('\\', '/', substr($absolutePath, strlen($rootPath))), '/');

            if ($relativePath === 'member' || str_starts_with($relativePath, 'member/')) {
                continue;
            }

            $items[] = [
                'name' => $file->getFilename(),
                'path' => $relativePath,
                'url' => $rootUrl . '/' . $relativePath,
                'size' => $file->getSize(),
                'modified' => $file->getMTime(),
            ];
        }

        usort($items, static function (array $left, array $right): int {
            return (int)($right['modified'] ?? 0) <=> (int)($left['modified'] ?? 0);
        });

        return [
            'success' => 1,
            'items' => array_slice($items, 0, 250),
        ];
    }

    /**
     * @return array{success:int,file?:array<string,mixed>,message?:string}
     */
    private function handleFeaturedImageUpload(): array
    {
        if (empty($_FILES['image']) || !is_array($_FILES['image'])) {
            return [
                'success' => 0,
                'message' => 'Keine Bilddatei empfangen.',
            ];
        }

        // --- Slug / folder name -------------------------------------------------
        $slug = trim((string)($_POST['slug'] ?? ''));
        $slug = strtolower((string)preg_replace('/[^a-z0-9]+/i', '_', $slug));
        $slug = trim($slug, '_');
        if ($slug === '') {
            $slug = 'artikelbild';
        }

        // Content type: 'post' or 'page' (default: post)
        $contentType = in_array($_POST['content_type'] ?? '', ['post', 'page'], true)
            ? $_POST['content_type']
            : 'post';

        $baseFolder = $contentType === 'page' ? 'pages' : 'articles';

        // If new (not yet saved), upload to temp; otherwise directly into slug folder
        $isNew = !empty($_POST['is_new']);
        $subFolder = $isNew ? 'temp' : $slug;
        $targetPath = $baseFolder . '/' . $subFolder;

        // --- Filename = slug.ext -----------------------------------------------
        $file = $_FILES['image'];
        $extension = strtolower((string)pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        $file['name'] = $slug . ($extension !== '' ? '.' . $extension : '');
        $_FILES['image'] = $file;

        $result = $this->handleFileUpload('image', true, $targetPath);

        // --- Assign 'phinit-cover' category ------------------------------------
        if ($result['success'] === 1 && isset($result['file']['url'])) {
            $mediaService = MediaService::getInstance();
            $mediaService->ensureCategory('PhinIT-Cover', 'phinit-cover');
            // Derive relative path from the returned URL
            $uploadUrl  = rtrim((string)(defined('UPLOAD_URL') ? UPLOAD_URL : ''), '/');
            $fileUrl    = rtrim((string)$result['file']['url'], '/');
            $relativePath = $uploadUrl !== '' && str_starts_with($fileUrl, $uploadUrl)
                ? ltrim(substr($fileUrl, strlen($uploadUrl)), '/')
                : '';
            if ($relativePath !== '') {
                $mediaService->assignCategory($relativePath, 'phinit-cover');
            }

            // Tell the client the temp path so it can be moved on save
            $result['temp_path'] = $relativePath;
            $result['target_folder'] = $targetPath;
            $result['is_temp'] = $isNew;
            $result['expected_folder'] = $baseFolder . '/' . $slug;
        }

        return $result;
    }

    /**
     * @return array{success:int,link?:string,meta?:array<string,mixed>,message?:string}
     */
    private function handleLinkMetadataFetch(): array
    {
        $payload = $this->getJsonInput();
        $url = trim((string)($payload['url'] ?? $payload['link'] ?? $_POST['url'] ?? ''));

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => 0,
                'message' => 'Ungültige Link-URL.',
            ];
        }

        $htmlResult = $this->downloadRemoteHtml($url);
        if ($htmlResult['success'] !== true) {
            return [
                'success' => 0,
                'message' => (string)($htmlResult['message'] ?? 'Metadaten konnten nicht geladen werden.'),
            ];
        }

        $html = (string)$htmlResult['html'];
        $meta = $this->extractLinkMetadata($url, $html);

        return [
            'success' => 1,
            'link' => $url,
            'meta' => $meta,
        ];
    }

    private function sanitizePayload(array $payload): array
    {
        $blocks = [];

        foreach (($payload['blocks'] ?? []) as $block) {
            if (!is_array($block)) {
                continue;
            }

            $cleanBlock = $this->sanitizeBlock($block);
            if ($cleanBlock !== null) {
                $blocks[] = $cleanBlock;
            }
        }

        return ['blocks' => $blocks];
    }

    private function sanitizeBlock(array $block): ?array
    {
        $allowedTypes = [
            'paragraph', 'header', 'list', 'checklist', 'quote', 'warning',
            'code', 'raw', 'table', 'image', 'attaches', 'linkTool', 'delimiter',
            'embed', 'imageGallery', 'carousel', 'columns', 'accordion', 'drawingTool', 'spacer',
        ];

        $type = (string)($block['type'] ?? '');
        if (!in_array($type, $allowedTypes, true)) {
            return null;
        }

        $data = $this->sanitizeBlockData($type, is_array($block['data'] ?? null) ? $block['data'] : []);
        $cleanBlock = [
            'type' => $type,
            'data' => $data,
        ];

        $tunes = $this->sanitizeTunes($type, is_array($block['tunes'] ?? null) ? $block['tunes'] : []);
        if ($tunes !== []) {
            $cleanBlock['tunes'] = $tunes;
        }

        return $cleanBlock;
    }

    private function sanitizeBlockData(string $type, array $data): array
    {
        $inlineAllowed = '<b><i><u><a><code><mark><sub><sup><br><strong><em><span>';

        switch ($type) {
            case 'paragraph':
                $data['text'] = strip_tags((string)($data['text'] ?? ''), $inlineAllowed);
                break;

            case 'header':
                $data['text'] = strip_tags((string)($data['text'] ?? ''), $inlineAllowed);
                $data['level'] = max(1, min(6, (int)($data['level'] ?? 2)));
                break;

            case 'list':
                $style = (string)($data['style'] ?? 'unordered');
                $data['style'] = in_array($style, ['ordered', 'unordered', 'checklist'], true) ? $style : 'unordered';
                $data['meta'] = $this->sanitizeListMeta($data['style'], is_array($data['meta'] ?? null) ? $data['meta'] : []);
                $data['items'] = $this->sanitizeListItems(is_array($data['items'] ?? null) ? $data['items'] : [], $data['style']);
                break;

            case 'checklist':
                $data['items'] = array_values(array_filter(array_map(static function ($item) use ($inlineAllowed) {
                    if (!is_array($item)) {
                        return null;
                    }

                    return [
                        'text' => strip_tags((string)($item['text'] ?? ''), $inlineAllowed),
                        'checked' => !empty($item['checked']),
                    ];
                }, is_array($data['items'] ?? null) ? $data['items'] : [])));
                break;

            case 'quote':
                $data['text'] = strip_tags((string)($data['text'] ?? ''), $inlineAllowed);
                $data['caption'] = strip_tags((string)($data['caption'] ?? ''), $inlineAllowed);
                $data['alignment'] = in_array(($data['alignment'] ?? 'left'), ['left', 'center'], true) ? (string)$data['alignment'] : 'left';
                break;

            case 'warning':
                $data['title'] = strip_tags((string)($data['title'] ?? ''), $inlineAllowed);
                $data['message'] = strip_tags((string)($data['message'] ?? ''), $inlineAllowed);
                break;

            case 'code':
                $data['code'] = (string)($data['code'] ?? '');
                if (isset($data['language'])) {
                    $data['language'] = preg_replace('/[^a-z0-9_\-+#]/i', '', (string)$data['language']);
                }
                break;

            case 'raw':
                $data['html'] = strip_tags((string)($data['html'] ?? ''), '<p><a><strong><em><ul><ol><li><br><h1><h2><h3><h4><h5><h6><div><span><img><table><tr><td><th><thead><tbody><blockquote><pre><code><hr><figure><figcaption><iframe>');
                break;

            case 'table':
                $data['withHeadings'] = !empty($data['withHeadings']);
                $data['content'] = array_values(array_map(function ($row) use ($inlineAllowed) {
                    if (!is_array($row)) {
                        return [];
                    }

                    return array_values(array_map(static fn($cell) => strip_tags((string)$cell, $inlineAllowed), $row));
                }, is_array($data['content'] ?? null) ? $data['content'] : []));
                break;

            case 'image':
                $data['file'] = $this->sanitizeFileInfo(is_array($data['file'] ?? null) ? $data['file'] : []);
                $data['caption'] = strip_tags((string)($data['caption'] ?? ''), $inlineAllowed);
                $data['withBorder'] = !empty($data['withBorder']);
                $data['withBackground'] = !empty($data['withBackground']);
                $data['stretched'] = !empty($data['stretched']);
                break;

            case 'attaches':
                $data['file'] = $this->sanitizeFileInfo(is_array($data['file'] ?? null) ? $data['file'] : []);
                $data['title'] = strip_tags((string)($data['title'] ?? ''), $inlineAllowed);
                break;

            case 'linkTool':
                $data['link'] = filter_var((string)($data['link'] ?? ''), FILTER_VALIDATE_URL) ?: '';
                $data['meta'] = $this->sanitizeLinkMeta(is_array($data['meta'] ?? null) ? $data['meta'] : []);
                break;

            case 'embed':
                $data['service'] = preg_replace('/[^a-z0-9\-]/i', '', (string)($data['service'] ?? 'embed'));
                $data['source'] = filter_var((string)($data['source'] ?? ''), FILTER_VALIDATE_URL) ?: '';
                $data['embed'] = filter_var((string)($data['embed'] ?? ''), FILTER_VALIDATE_URL) ?: '';
                $data['width'] = max(0, (int)($data['width'] ?? 0));
                $data['height'] = max(0, (int)($data['height'] ?? 0));
                $data['caption'] = strip_tags((string)($data['caption'] ?? ''), $inlineAllowed);
                break;

            case 'imageGallery':
                $data['urls'] = $this->sanitizeUrlList(is_array($data['urls'] ?? null) ? $data['urls'] : []);
                $data['editImages'] = !empty($data['editImages']);
                $data['bkgMode'] = !empty($data['bkgMode']);
                $data['layoutDefault'] = !empty($data['layoutDefault']);
                $data['layoutHorizontal'] = !empty($data['layoutHorizontal']);
                $data['layoutSquare'] = !empty($data['layoutSquare']);
                $data['layoutWithGap'] = !empty($data['layoutWithGap']);
                $data['layoutWithFixedSize'] = !empty($data['layoutWithFixedSize']);
                break;

            case 'carousel':
                $data = array_values(array_filter(array_map(function ($item) use ($inlineAllowed) {
                    if (!is_array($item)) {
                        return null;
                    }

                    $url = filter_var((string)($item['url'] ?? ''), FILTER_VALIDATE_URL);
                    if ($url === false) {
                        return null;
                    }

                    return [
                        'url' => $url,
                        'caption' => strip_tags((string)($item['caption'] ?? ''), $inlineAllowed),
                    ];
                }, $data)));
                break;

            case 'columns':
                $cleanCols = [];
                foreach ((is_array($data['cols'] ?? null) ? $data['cols'] : []) as $column) {
                    if (!is_array($column)) {
                        continue;
                    }
                    $cleanCols[] = $this->sanitizePayload($column);
                }
                $data['cols'] = $cleanCols;
                break;

            case 'accordion':
                $settings = is_array($data['settings'] ?? null) ? $data['settings'] : [];
                $data['settings'] = [
                    'blockCount' => max(1, min(10, (int)($settings['blockCount'] ?? 3))),
                    'defaultExpanded' => !empty($settings['defaultExpanded']),
                ];
                $data['title'] = strip_tags((string)($data['title'] ?? ''), $inlineAllowed);
                break;

            case 'drawingTool':
                $data['canvasJson'] = is_string($data['canvasJson'] ?? null) ? $data['canvasJson'] : null;
                $data['canvasHeight'] = max(150, min(3000, (int)($data['canvasHeight'] ?? 700)));
                $data['canvasImages'] = array_values(array_filter(array_map(function ($item) {
                    if (!is_array($item)) {
                        return null;
                    }

                    $src = (string)($item['src'] ?? '');
                    if (!$this->isValidAssetUrl($src)) {
                        return null;
                    }

                    return [
                        'id' => preg_replace('/[^a-z0-9_\-]/i', '', (string)($item['id'] ?? 'img')),
                        'src' => $src,
                        'attrs' => is_array($item['attrs'] ?? null) ? $item['attrs'] : [],
                    ];
                }, is_array($data['canvasImages'] ?? null) ? $data['canvasImages'] : [])));
                break;

            case 'spacer':
                $allowedHeights = [15, 25, 40, 60, 75, 100];
                $height = (int)($data['height'] ?? 15);
                if (!in_array($height, $allowedHeights, true)) {
                    $height = 15;
                }

                $data = [
                    'height' => $height,
                    'preset' => $height . 'px',
                ];
                break;
        }

        return $data;
    }

    private function sanitizeTunes(string $type, array $tunes): array
    {
        if ($type !== 'image') {
            return [];
        }

        $cleanTunes = [];
        foreach (['Cropper', 'CropperTune'] as $key) {
            if (!isset($tunes[$key]) || !is_array($tunes[$key])) {
                continue;
            }

            $croppedImage = (string)($tunes[$key]['croppedImage'] ?? '');
            if ($croppedImage !== '' && $this->isValidAssetUrl($croppedImage)) {
                $cleanTunes[$key] = ['croppedImage' => $croppedImage];
            }
        }

        return $cleanTunes;
    }

    private function sanitizeListItems(array $items, string $style): array
    {
        $inlineAllowed = '<b><i><u><a><code><mark><sub><sup><br><strong><em><span>';
        $cleanItems = [];

        foreach ($items as $item) {
            if (is_string($item)) {
                $cleanItems[] = [
                    'content' => strip_tags($item, $inlineAllowed),
                    'meta' => $style === 'checklist' ? ['checked' => false] : [],
                    'items' => [],
                ];
                continue;
            }

            if (!is_array($item)) {
                continue;
            }

            $cleanItems[] = [
                'content' => strip_tags((string)($item['content'] ?? $item['text'] ?? ''), $inlineAllowed),
                'meta' => $this->sanitizeListMeta($style, is_array($item['meta'] ?? null) ? $item['meta'] : []),
                'items' => $this->sanitizeListItems(is_array($item['items'] ?? null) ? $item['items'] : [], $style),
            ];
        }

        return $cleanItems;
    }

    private function sanitizeListMeta(string $style, array $meta): array
    {
        return match ($style) {
            'ordered' => [
                'start' => max(1, (int)($meta['start'] ?? 1)),
                'counterType' => in_array(($meta['counterType'] ?? 'numeric'), ['numeric', 'lower-roman', 'upper-roman', 'lower-alpha', 'upper-alpha'], true)
                    ? (string)$meta['counterType']
                    : 'numeric',
            ],
            'checklist' => [
                'checked' => !empty($meta['checked']),
            ],
            default => [],
        };
    }

    private function sanitizeFileInfo(array $file): array
    {
        $url = filter_var((string)($file['url'] ?? ''), FILTER_VALIDATE_URL) ?: '';

        return [
            'url' => $url,
            'name' => strip_tags((string)($file['name'] ?? ''), ''),
            'size' => max(0, (int)($file['size'] ?? 0)),
            'extension' => preg_replace('/[^a-z0-9]/i', '', (string)($file['extension'] ?? '')),
        ];
    }

    private function sanitizeLinkMeta(array $meta): array
    {
        $inlineAllowed = '<b><i><u><a><code><mark><sub><sup><br><strong><em><span>';

        return [
            'title' => strip_tags((string)($meta['title'] ?? ''), $inlineAllowed),
            'description' => strip_tags((string)($meta['description'] ?? ''), $inlineAllowed),
            'site_name' => strip_tags((string)($meta['site_name'] ?? ''), $inlineAllowed),
            'image' => [
                'url' => filter_var((string)($meta['image']['url'] ?? ''), FILTER_VALIDATE_URL) ?: '',
            ],
        ];
    }

    /** @return string[] */
    private function sanitizeUrlList(array $urls): array
    {
        $cleanUrls = [];
        foreach ($urls as $url) {
            $sanitized = filter_var((string)$url, FILTER_VALIDATE_URL);
            if ($sanitized !== false) {
                $cleanUrls[] = $sanitized;
            }
        }

        return array_values(array_unique($cleanUrls));
    }

    private function isValidAssetUrl(string $url): bool
    {
        if (str_starts_with($url, 'data:image/')) {
            return true;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param string[] $allowedMimePrefixes
     * @return array{success:bool,tmpFile?:string,contentType?:string,extension?:string,message?:string}
     */
    private function downloadRemoteFile(string $url, array $allowedMimePrefixes): array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 12,
                'follow_location' => 1,
                'user_agent' => '365CMS EditorJS/2.1',
            ],
            'https' => [
                'timeout' => 12,
                'follow_location' => 1,
                'user_agent' => '365CMS EditorJS/2.1',
            ],
        ]);

        $content = @file_get_contents($url, false, $context, 0, 10 * 1024 * 1024);
        if ($content === false || $content === '') {
            return [
                'success' => false,
                'message' => 'Remote-Datei konnte nicht geladen werden.',
            ];
        }

        $headers = $http_response_header ?? [];
        $contentType = '';
        foreach ($headers as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                $contentType = trim((string)substr($header, strlen('Content-Type:')));
                $contentType = explode(';', $contentType)[0] ?? $contentType;
                break;
            }
        }

        if ($contentType !== '') {
            $isAllowed = false;
            foreach ($allowedMimePrefixes as $allowedPrefix) {
                if (stripos($contentType, $allowedPrefix) === 0) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                return [
                    'success' => false,
                    'message' => 'Remote-Datei hat keinen erlaubten MIME-Typ.',
                ];
            }
        }

        $extension = strtolower((string)pathinfo((string)parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = match ($contentType) {
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'image/avif' => 'avif',
                'image/bmp' => 'bmp',
                default => 'jpg',
            };
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'cms_ejs_');
        if ($tmpFile === false || file_put_contents($tmpFile, $content) === false) {
            return [
                'success' => false,
                'message' => 'Temporäre Datei konnte nicht geschrieben werden.',
            ];
        }

        return [
            'success' => true,
            'tmpFile' => $tmpFile,
            'contentType' => $contentType,
            'extension' => $extension,
        ];
    }

    /**
     * @return array{success:bool,html?:string,message?:string}
     */
    private function downloadRemoteHtml(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'follow_location' => 1,
                'user_agent' => '365CMS LinkTool/2.1',
            ],
            'https' => [
                'timeout' => 10,
                'follow_location' => 1,
                'user_agent' => '365CMS LinkTool/2.1',
            ],
        ]);

        $html = @file_get_contents($url, false, $context, 0, 1024 * 1024);
        if ($html === false || trim($html) === '') {
            return [
                'success' => false,
                'message' => 'Remote-HTML konnte nicht geladen werden.',
            ];
        }

        return [
            'success' => true,
            'html' => $html,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function extractLinkMetadata(string $sourceUrl, string $html): array
    {
        $title = '';
        $description = '';
        $image = '';
        $siteName = parse_url($sourceUrl, PHP_URL_HOST) ?: '';

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        @$doc->loadHTML($html);
        libxml_clear_errors();

        $titleNodes = $doc->getElementsByTagName('title');
        if ($titleNodes->length > 0) {
            $title = trim((string)$titleNodes->item(0)?->textContent);
        }

        foreach ($doc->getElementsByTagName('meta') as $metaTag) {
            $property = strtolower((string)$metaTag->getAttribute('property'));
            $name = strtolower((string)$metaTag->getAttribute('name'));
            $content = trim((string)$metaTag->getAttribute('content'));

            if ($content === '') {
                continue;
            }

            if ($title === '' && in_array($property, ['og:title', 'twitter:title'], true)) {
                $title = $content;
            }

            if ($description === '' && in_array($name, ['description', 'twitter:description'], true)) {
                $description = $content;
            }

            if ($description === '' && $property === 'og:description') {
                $description = $content;
            }

            if ($image === '' && in_array($property, ['og:image', 'twitter:image'], true)) {
                $image = $this->absolutizeUrl($content, $sourceUrl);
            }

            if ($siteName === '' && $property === 'og:site_name') {
                $siteName = $content;
            }
        }

        if ($title === '') {
            $title = $sourceUrl;
        }

        return [
            'title' => strip_tags($title),
            'description' => strip_tags($description),
            'site_name' => strip_tags($siteName),
            'image' => [
                'url' => filter_var($image, FILTER_VALIDATE_URL) ?: '',
            ],
        ];
    }

    private function absolutizeUrl(string $url, string $baseUrl): string
    {
        if ($url === '') {
            return '';
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $baseParts = parse_url($baseUrl);
        if (!is_array($baseParts) || empty($baseParts['scheme']) || empty($baseParts['host'])) {
            return $url;
        }

        $scheme = $baseParts['scheme'];
        $host = $baseParts['host'];
        $port = isset($baseParts['port']) ? ':' . $baseParts['port'] : '';

        if (str_starts_with($url, '//')) {
            return $scheme . ':' . $url;
        }

        if (str_starts_with($url, '/')) {
            return $scheme . '://' . $host . $port . $url;
        }

        $path = (string)($baseParts['path'] ?? '/');
        $dir = rtrim(str_replace('\\', '/', dirname($path)), '/');
        return $scheme . '://' . $host . $port . ($dir !== '' ? $dir : '') . '/' . ltrim($url, '/');
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
