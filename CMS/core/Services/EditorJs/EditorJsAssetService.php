<?php
/**
 * Editor.js Asset- und Render-Service.
 *
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services\EditorJs;

if (!defined('ABSPATH')) {
    exit;
}

final class EditorJsAssetService
{
    private bool $assetsEnqueued = false;
    private int $editorCount = 0;

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

    public function render(string $name, string $content = '', array $settings = []): string
    {
        $this->editorCount++;
        $editorNum = $this->editorCount;
        $holderId  = $name . '_editorjs_' . $editorNum;
        $hiddenId  = $name . '_hidden_' . $editorNum;

        if (!$this->assetsEnqueued) {
            $this->enqueueEditorAssets();
        }

        $minHeight = (int) ($settings['height'] ?? 400);
        $escapedData = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        $siteUrl = defined('SITE_URL') ? SITE_URL : '';
        $contentWidth = max(320, (int) ($settings['content_width'] ?? 1100));
        $expandedContentWidth = max($contentWidth, (int) ($settings['content_width_expanded'] ?? $contentWidth));
        $contentPaddingX = max(0, (int) ($settings['content_padding_x'] ?? 50));
        $contextClass = preg_replace('/[^a-z0-9_-]/i', '', (string) ($settings['context'] ?? 'default')) ?: 'default';
        $csrfToken = class_exists(\CMS\Security::class)
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
                <button type="button" data-block="mediaText" title="Medien + Text">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="6" height="14" rx="1"/><path d="M12 7h8"/><path d="M12 12h8"/><path d="M12 17h6"/></svg>
                    <span>Medien+Text</span>
                </button>
                <button type="button" data-block="imageGallery" data-columns="3" title="Gallery">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 9h.01"/><path d="M21 15l-4.5-4.5a1.5 1.5 0 00-2.12 0L9 15.88"/><path d="M3 17l4.5-4.5a1.5 1.5 0 012.12 0L13 16"/></svg>
                    <span>Gallery</span>
                </button>
                <button type="button" data-block="callout" title="Callout / Hinweisbox">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86l-7.5 13A2 2 0 004.53 20h14.94a2 2 0 001.74-3.14l-7.5-13a2 2 0 00-3.46 0z"/></svg>
                    <span>Callout</span>
                </button>
                <button type="button" data-block="terminal" title="Terminal / Command Block">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 17l6-5-6-5"/><path d="M12 19h8"/></svg>
                    <span>Terminal</span>
                </button>
                <button type="button" data-block="codeTabs" title="Code Tabs">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M8 5v5"/></svg>
                    <span>Code Tabs</span>
                </button>
                <button type="button" data-block="mermaid" title="Mermaid / Diagramm">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h10"/><path d="M10 12h10"/><path d="M4 18h10"/><circle cx="17" cy="6" r="2"/><circle cx="7" cy="12" r="2"/><circle cx="17" cy="18" r="2"/></svg>
                    <span>Mermaid</span>
                </button>
                <button type="button" data-block="apiEndpoint" title="API Endpoint Block">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 12h8"/><path d="M12 8v8"/><rect x="4" y="4" width="16" height="16" rx="3"/></svg>
                    <span>API</span>
                </button>
                <button type="button" data-block="changelog" title="Changelog / Version-Hinweis">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h12"/><path d="M8 12h12"/><path d="M8 18h12"/><path d="M4 6h.01"/><path d="M4 12h.01"/><path d="M4 18h.01"/></svg>
                    <span>Changelog</span>
                </button>
                <button type="button" data-block="prosCons" title="Pros / Cons oder Vergleichsblock">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 7h10"/><path d="M8 12h10"/><path d="M8 17h10"/><path d="M4 7h.01"/><path d="M4 12h.01"/><path d="M4 17h.01"/></svg>
                    <span>Pros/Cons</span>
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
                        var columns = btn.getAttribute('data-columns');
                        if (level) {
                            blockData.level = parseInt(level, 10);
                        }
                        if (height) {
                            blockData.height = parseInt(height, 10);
                            blockData.preset = height + 'px';
                        }
                        if (columns) {
                            blockData.columns = parseInt(columns, 10);
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

        return (string) ob_get_clean();
    }

    /**
     * @return array{css: string[], js: string[]}
     */
    public function getPageAssets(): array
    {
        return [
            'css' => $this->getEditorCssUrls(),
            'js' => $this->getEditorJsUrls(),
        ];
    }

    public function enqueueEditorAssets(): void
    {
        if ($this->assetsEnqueued) {
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
        $this->assetsEnqueued = true;
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
        return \cms_asset_url($relativePath);
    }
}
