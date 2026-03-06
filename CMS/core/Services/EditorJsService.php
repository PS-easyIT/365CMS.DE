<?php
/**
 * Editor.js Integration Service
 *
 * Verwaltet Editor.js Asset-Loading, stellt render()-API bereit
 * und bietet Upload-Endpoints für Bilder/Dateien.
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
     * @param string $name    Feld-Name (hidden input)
     * @param string $content Gespeicherter JSON-String (oder leerer String)
     * @param array  $settings  Optionale Einstellungen (height, placeholder, etc.)
     * @return string         HTML + JS für den Editor
     */
    public function render(string $name, string $content = '', array $settings = []): string
    {
        self::$editorCount++;
        $editorNum  = self::$editorCount;
        $holderId   = $name . '_editorjs_' . $editorNum;
        $hiddenId   = $name . '_hidden_' . $editorNum;

        if (!self::$assetsEnqueued) {
            $this->enqueueEditorAssets();
        }

        $placeholder = htmlspecialchars($settings['placeholder'] ?? 'Beginne hier zu schreiben…', ENT_QUOTES, 'UTF-8');
        $minHeight   = (int)($settings['height'] ?? 400);

        // JSON-Daten für JS-Übergabe vorbereiten
        $escapedData = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        $siteUrl  = defined('SITE_URL') ? SITE_URL : '';
        $csrfToken = '';
        if (class_exists(\CMS\Security::class)) {
            $csrfToken = \CMS\Security::instance()->generateToken('editorjs_upload');
        }

        ob_start();
        ?>
        <input type="hidden"
               id="<?php echo htmlspecialchars($hiddenId, ENT_QUOTES); ?>"
               name="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>"
               value="<?php echo $escapedData; ?>">

        <div class="editorjs-wrap" id="<?php echo htmlspecialchars($holderId, ENT_QUOTES); ?>_wrap">
            <div class="editorjs-toolbar" id="<?php echo htmlspecialchars($holderId, ENT_QUOTES); ?>_toolbar">
                <button type="button" data-block="header" data-level="2" title="Überschrift H2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 4v16"/><path d="M7 12h10"/><path d="M17 4v16"/></svg>
                    <span>H2</span>
                </button>
                <button type="button" data-block="header" data-level="3" title="Überschrift H3">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 4v16"/><path d="M7 12h10"/><path d="M17 4v16"/></svg>
                    <span>H3</span>
                </button>
                <button type="button" data-block="header" data-level="4" title="Überschrift H4">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 4v16"/><path d="M7 12h10"/><path d="M17 4v16"/></svg>
                    <span>H4</span>
                </button>
                <button type="button" data-block="header" data-level="5" title="Überschrift H5">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 4v16"/><path d="M7 12h10"/><path d="M17 4v16"/></svg>
                    <span>H5</span>
                </button>
                <button type="button" data-block="header" data-level="6" title="Überschrift H6">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 4v16"/><path d="M7 12h10"/><path d="M17 4v16"/></svg>
                    <span>H6</span>
                </button>
                <span class="editorjs-toolbar__sep"></span>
                <button type="button" data-block="paragraph" title="Textabsatz">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h12"/></svg>
                    <span>Text</span>
                </button>
                <button type="button" data-block="list" title="Aufzählung">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6h11"/><path d="M9 12h11"/><path d="M9 18h11"/><circle cx="5" cy="6" r="1" fill="currentColor"/><circle cx="5" cy="12" r="1" fill="currentColor"/><circle cx="5" cy="18" r="1" fill="currentColor"/></svg>
                    <span>Liste</span>
                </button>
                <button type="button" data-block="checklist" title="Checkliste">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3.5 5.5l1.5 1.5l2.5-2.5"/><path d="M3.5 11.5l1.5 1.5l2.5-2.5"/><path d="M3.5 17.5l1.5 1.5l2.5-2.5"/><path d="M11 6h9"/><path d="M11 12h9"/><path d="M11 18h9"/></svg>
                    <span>Checkliste</span>
                </button>
                <span class="editorjs-toolbar__sep"></span>
                <button type="button" data-block="image" title="Bild einfügen">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="9" cy="9" r="1.5"/><path d="M3 16l5-5c1-.9 2.1-.9 3 0l5 5"/><path d="M14 14l1-1c1-.9 2.1-.9 3 0l3 3"/></svg>
                    <span>Bild</span>
                </button>
                <button type="button" data-block="table" title="Tabelle (3×3)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M3 15h18"/><path d="M9 3v18"/></svg>
                    <span>Tabelle</span>
                </button>
                <button type="button" data-block="quote" title="Zitat-Block">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 11h-4a1 1 0 01-1-1v-3a1 1 0 011-1h3a1 1 0 011 1v6c0 2.667-1.333 4.333-4 5"/><path d="M19 11h-4a1 1 0 01-1-1v-3a1 1 0 011-1h3a1 1 0 011 1v6c0 2.667-1.333 4.333-4 5"/></svg>
                    <span>Zitat</span>
                </button>
                <button type="button" data-block="warning" title="Hinweis / Warnung">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><circle cx="12" cy="16" r="0.5" fill="currentColor"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 001.636 2.875h16.214a1.914 1.914 0 001.636-2.875L13.637 3.591a1.914 1.914 0 00-3.274 0z"/></svg>
                    <span>Hinweis</span>
                </button>
                <button type="button" data-block="code" title="Code-Block">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    <span>Code</span>
                </button>
                <button type="button" data-block="raw" title="Raw HTML">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7l4.5-3L13 7"/><path d="M4 17l4.5 3L13 17"/><path d="M17 4l2 16"/></svg>
                    <span>HTML</span>
                </button>
                <button type="button" data-block="delimiter" title="Trennlinie">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/></svg>
                    <span>Trenner</span>
                </button>
            </div>

            <div id="<?php echo htmlspecialchars($holderId, ENT_QUOTES); ?>"
                 class="editorjs-holder"
                 style="min-height:<?php echo $minHeight; ?>px;"></div>

            <div class="editorjs-statusbar">
                <span class="editorjs-statusbar__hint">Tippe <kbd>/</kbd> oder nutze die Toolbar oben für Blöcke</span>
                <span class="editorjs-statusbar__count" id="<?php echo htmlspecialchars($holderId, ENT_QUOTES); ?>_count"></span>
            </div>
        </div>

        <script>
        (function() {
            function initEditorJs<?php echo $editorNum; ?>() {
                var holderEl = document.getElementById('<?php echo $holderId; ?>');
                var hiddenEl = document.getElementById('<?php echo $hiddenId; ?>');
                if (!holderEl || !hiddenEl) return;

                var initialData = null;
                try {
                    var raw = hiddenEl.value;
                    if (raw && raw !== '') {
                        initialData = JSON.parse(raw);
                    }
                } catch(e) {
                    console.warn('Editor.js: Could not parse initial data', e);
                }

                if (typeof window.createCmsEditor !== 'function') {
                    console.error('editor-init.js not loaded');
                    return;
                }

                var editor = window.createCmsEditor(
                    '<?php echo $holderId; ?>',
                    initialData,
                    '<?php echo htmlspecialchars($siteUrl, ENT_QUOTES); ?>/admin/api/editorjs-upload.php',
                    '<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>'
                );

                // Schnellfunktionen-Toolbar anbinden
                var toolbar = document.getElementById('<?php echo $holderId; ?>_toolbar');
                if (toolbar) {
                    toolbar.addEventListener('click', function(e) {
                        var btn = e.target.closest('button[data-block]');
                        if (!btn) return;
                        var blockType = btn.getAttribute('data-block');
                        var blockData = {};
                        // Header mit Level-Angabe
                        var level = btn.getAttribute('data-level');
                        if (level) {
                            blockData.level = parseInt(level, 10);
                        }
                        editor.blocks.insert(blockType, blockData);
                        // Fokus auf den neuen Block
                        var lastIdx = editor.blocks.getBlocksCount() - 1;
                        editor.caret.setToBlock(lastIdx, 'start');
                    });
                }

                // Blockzähler aktualisieren
                var countEl = document.getElementById('<?php echo $holderId; ?>_count');
                function updateBlockCount() {
                    if (countEl && editor.blocks) {
                        var n = editor.blocks.getBlocksCount();
                        countEl.textContent = n + (n === 1 ? ' Block' : ' Blöcke');
                    }
                }
                // Editor.js onChange Event
                editor.isReady.then(function() {
                    updateBlockCount();
                });
                var origConfig = editor.configuration || {};
                if (typeof origConfig === 'object') {
                    var _checkInterval = setInterval(function() {
                        if (editor.blocks) {
                            updateBlockCount();
                        }
                    }, 2000);
                    // cleanup nach 5min
                    setTimeout(function() { clearInterval(_checkInterval); }, 300000);
                }

                // Bei Form-Submit JSON in hidden Input schreiben
                var form = holderEl.closest('form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        // Verhindere doppeltes Submit
                        if (form.dataset.editorjsSaving === 'true') return;
                        e.preventDefault();
                        form.dataset.editorjsSaving = 'true';

                        editor.save().then(function(outputData) {
                            hiddenEl.value = JSON.stringify(outputData);
                            form.submit();
                        }).catch(function(err) {
                            console.error('Editor.js save error:', err);
                            form.dataset.editorjsSaving = '';
                            form.submit(); // Trotzdem absenden
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
        return ob_get_clean();
    }

    /**
     * Editor.js Assets laden (CSS + JS + Plugins).
     */
    public function enqueueEditorAssets(): void
    {
        if (self::$assetsEnqueued) {
            return;
        }

        $siteUrl = defined('SITE_URL') ? SITE_URL : '';
        $base    = $siteUrl . '/assets/editorjs';
        $jsBase  = $siteUrl . '/assets/js';

        echo "\n<!-- Editor.js Assets -->\n";
        // Core
        echo '<script src="' . htmlspecialchars($base) . '/editorjs.umd.js"></script>' . "\n";
        // Plugins
        $plugins = [
            'header', 'paragraph', 'checklist', 'code', 'inline-code',
            'quote', 'raw', 'underline', 'warning', 'delimiter',
            'editorjs-list', 'table', 'link', 'attaches', 'image',
        ];
        foreach ($plugins as $p) {
            echo '<script src="' . htmlspecialchars($base) . '/' . $p . '.umd.js"></script>' . "\n";
        }
        // Init helper
        echo '<script src="' . htmlspecialchars($jsBase) . '/editor-init.js"></script>' . "\n";
        echo "<!-- /Editor.js Assets -->\n\n";

        self::$assetsEnqueued = true;
    }

    /**
     * Editor.js JSON-Daten sanitieren (Block-Typen + Inline-HTML prüfen).
     *
     * @param string $json  Roher JSON-String vom POST
     * @return string       Sanitierter JSON-String
     */
    public function sanitize(string $json): string
    {
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['blocks'])) {
            return '{"blocks":[]}';
        }

        $allowedTypes = [
            'paragraph', 'header', 'list', 'checklist', 'quote',
            'warning', 'code', 'raw', 'table', 'image',
            'attaches', 'linkTool', 'delimiter',
        ];

        $cleaned = [];
        foreach ($data['blocks'] as $block) {
            $type = $block['type'] ?? '';
            if (!in_array($type, $allowedTypes, true)) {
                continue;
            }
            $blockData = $block['data'] ?? [];
            // Inline-HTML in Textfeldern sanitieren
            $blockData = $this->sanitizeBlockData($type, $blockData);
            $cleaned[] = ['type' => $type, 'data' => $blockData];
        }

        return json_encode(['blocks' => $cleaned], JSON_UNESCAPED_UNICODE);
    }

    private function sanitizeBlockData(string $type, array $d): array
    {
        $inlineAllowed = '<b><i><u><a><code><mark><sub><sup><br><strong><em>';

        switch ($type) {
            case 'paragraph':
                $d['text'] = strip_tags($d['text'] ?? '', $inlineAllowed);
                break;
            case 'header':
                $d['text']  = strip_tags($d['text'] ?? '', $inlineAllowed);
                $d['level'] = max(1, min(6, (int)($d['level'] ?? 2)));
                break;
            case 'quote':
                $d['text']    = strip_tags($d['text'] ?? '', $inlineAllowed);
                $d['caption'] = strip_tags($d['caption'] ?? '', $inlineAllowed);
                break;
            case 'warning':
                $d['title']   = strip_tags($d['title'] ?? '', $inlineAllowed);
                $d['message'] = strip_tags($d['message'] ?? '', $inlineAllowed);
                break;
            case 'code':
                $d['code'] = htmlspecialchars_decode(htmlspecialchars($d['code'] ?? '', ENT_QUOTES, 'UTF-8'));
                break;
            case 'raw':
                $d['html'] = strip_tags($d['html'] ?? '', '<p><a><strong><em><ul><ol><li><br><h2><h3><h4><h5><div><span><img><table><tr><td><th><thead><tbody><blockquote><pre><code><hr>');
                break;
            case 'table':
                if (isset($d['content']) && is_array($d['content'])) {
                    foreach ($d['content'] as &$row) {
                        if (is_array($row)) {
                            foreach ($row as &$cell) {
                                $cell = strip_tags((string)$cell, $inlineAllowed);
                            }
                        }
                    }
                }
                break;
            case 'image':
                if (isset($d['file']['url'])) {
                    $d['file']['url'] = filter_var($d['file']['url'], FILTER_VALIDATE_URL) ?: '';
                }
                $d['caption'] = strip_tags($d['caption'] ?? '', $inlineAllowed);
                break;
            case 'attaches':
                if (isset($d['file']['url'])) {
                    $d['file']['url'] = filter_var($d['file']['url'], FILTER_VALIDATE_URL) ?: '';
                }
                $d['title'] = strip_tags($d['title'] ?? '', $inlineAllowed);
                break;
            case 'linkTool':
                $d['link'] = filter_var($d['link'] ?? '', FILTER_VALIDATE_URL) ?: '';
                break;
        }

        return $d;
    }
}
