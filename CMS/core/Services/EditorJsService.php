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

        <div id="<?php echo htmlspecialchars($holderId, ENT_QUOTES); ?>"
             class="editorjs-holder"
             style="min-height:<?php echo $minHeight; ?>px; border:2px solid #e2e8f0; border-radius:var(--card-radius,10px); padding:1rem;"></div>

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
            'paragraph', 'checklist', 'code', 'inline-code',
            'quote', 'raw', 'underline', 'warning',
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
