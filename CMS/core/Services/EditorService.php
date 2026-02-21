<?php
/**
 * WYSIWYG Editor Service - SunEditor Integration
 * 
 * Provides rich text editing capabilities using SunEditor
 * https://github.com/JiHong88/SunEditor
 * 
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Editor Service Class
 */
class EditorService
{
    /**
     * Singleton instance
     */
    private static ?self $instance = null;
    
    /**
     * Track if assets are enqueued
     */
    private static bool $assetsEnqueued = false;
    
    /**
     * Track editor instances for unique IDs
     */
    private static int $editorCount = 0;
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor (Singleton)
     */
    private function __construct()
    {
        // Register admin head action for assets
        \CMS\Hooks::addAction('admin_head', [$this, 'enqueueEditorAssets']);
    }
    
    /**
     * Render WYSIWYG editor
     * 
     * @param string $name Field name attribute
     * @param string $content Initial content
     * @param array $settings Optional editor settings
     * @return string Editor HTML
     */
    public function render(string $name, string $content = '', array $settings = []): string
    {
        self::$editorCount++;
        $editorId = $name . '_editor_' . self::$editorCount;
        
        // Ensure assets are enqueued
        if (!self::$assetsEnqueued) {
            $this->enqueueEditorAssets();
        }
        
        // Default settings
        $defaults = [
            'height' => '400',
            'language' => 'de',
            'buttonList' => [
                ['undo', 'redo'],
                ['bold', 'italic', 'underline', 'strike'],
                ['fontColor', 'hiliteColor'],
                ['removeFormat'],
                ['outdent', 'indent'],
                ['align', 'list', 'lineHeight'],
                ['table', 'link', 'image'],
                ['fullScreen', 'codeView']
            ]
        ];
        
        $settings = array_merge($defaults, $settings);
        
        // Escape content for textarea
        $escapedContent = htmlspecialchars($content ?? '', ENT_QUOTES, 'UTF-8');
        
        ob_start();
        ?>
        <textarea 
            id="<?php echo htmlspecialchars($editorId); ?>" 
            name="<?php echo htmlspecialchars($name); ?>" 
            style="display:none;"><?php echo $escapedContent; ?></textarea>
        <script>
        (function() {
            if (typeof SUNEDITOR === 'undefined') {
                console.error('SunEditor not loaded. Please check script inclusion.');
                // Fallback: show textarea
                const el = document.getElementById('<?php echo $editorId; ?>');
                if (el) el.style.display = 'block';
                return;
            }
            
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initEditor);
            } else {
                initEditor();
            }
            
            function initEditor() {
                const editorElement = document.getElementById('<?php echo $editorId; ?>');
                if (!editorElement) {
                    console.error('Editor element not found: <?php echo $editorId; ?>');
                    return;
                }
                
                try {
                    const editor_<?php echo self::$editorCount; ?> = SUNEDITOR.create(editorElement, {
                        lang: SUNEDITOR_LANG && SUNEDITOR_LANG.de ? SUNEDITOR_LANG.de : 'en',
                        height: '<?php echo htmlspecialchars((string)$settings['height']); ?>',
                        width: '100%',
                        buttonList: <?php echo json_encode($settings['buttonList']); ?>,
                        defaultStyle: 'font-family: "Cascadia Code", "Fira Code", "JetBrains Mono", Consolas, monospace; font-size: 14px;',
                        charCounter: false,
                        maxCharCount: null,
                        resizeEnable: true,
                        resizingBar: true,
                        showPathLabel: false,
                        attributesWhitelist: {
                            all: 'style|class|id|data-*',
                            table: 'cellpadding|cellspacing|border',
                            a: 'href|target|rel|title',
                            img: 'src|alt|title|width|height',
                            iframe: 'src|width|height|frameborder|allowfullscreen'
                        },
                        pasteTagsWhitelist: 'p|h1|h2|h3|h4|h5|h6|blockquote|ul|ol|li|table|thead|tbody|tr|th|td|a|b|strong|i|em|u|s|del|sub|sup|br|img|div|span|hr',
                        videoFileInput: false,
                        audioFileInput: false,
                        tabDisable: false,
                        formats: ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre'],
                        font: ['Arial', 'Comic Sans MS', 'Courier New', 'Georgia', 'Impact', 'Tahoma', 'Times New Roman', 'Verdana'],
                        fontSize: [8, 10, 12, 14, 16, 18, 20, 24, 28, 32, 36, 48, 64],
                        colorList: [
                            ['#ff0000', '#ff5e00', '#ffe400', '#abf200', '#00d8ff', '#0055ff', '#6600ff', '#ff00dd', '#000000'],
                            ['#ffd8d8', '#fae0d4', '#faecc5', '#c5f2e6', '#d4f4fa', '#d9e5ff', '#e8d9ff', '#ffd9fa', '#f1f1f1'],
                            ['#ffa7a7', '#ffc19e', '#faed7d', '#b7f0b1', '#b2ebf4', '#b2ccff', '#d1b2ff', '#ffb2f5', '#bdbdbd'],
                            ['#ff7a7a', '#ff9770', '#f7d730', '#80df90', '#60d2f0', '#8bb8ff', '#bd8fff', '#ff8fe6', '#8c8c8c'],
                            ['#f15f5f', '#ff7a44', '#f9d120', '#5ce07e', '#30cde4', '#6f9eff', '#af75ff', '#ff78d9', '#595959'],
                            ['#c92323', '#df5319', '#e5b700', '#30c757', '#009cb4', '#4072ff', '#8150e6', '#ff40c0', '#3b3b3b'],
                            ['#8c0000', '#a82800', '#ad8a00', '#158f3e', '#005f6d', '#1841bb', '#4e1a95', '#b60084', '#000000']
                        ]
                    });
                    
                    // Sync content to textarea on change
                    editor_<?php echo self::$editorCount; ?>.onChange = function(contents, core) {
                        editorElement.value = contents;
                    };
                } catch (error) {
                    console.error('Failed to initialize SunEditor:', error);
                    // Fallback: show textarea
                    editorElement.style.display = 'block';
                }
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enqueue editor assets (CSS and JS)
     * Called via admin_head hook
     * 
     * @return void
     */
    public function enqueueEditorAssets(): void
    {
        if (self::$assetsEnqueued) {
            return;
        }
        
        $siteUrl = defined('SITE_URL') ? SITE_URL : '';
        
        echo "\n<!-- SunEditor Assets -->\n";
        echo '<link rel="stylesheet" href="' . htmlspecialchars($siteUrl) . '/assets/suneditor/css/suneditor.min.css">' . "\n";
        echo '<script src="' . htmlspecialchars($siteUrl) . '/assets/suneditor/suneditor.min.js"></script>' . "\n";
        // Check if lang file exists before verifying, but for now just include it.
        // If file structure matches the attachment: assets/suneditor/lang/de.js
        echo '<script src="' . htmlspecialchars($siteUrl) . '/assets/suneditor/lang/de.js"></script>' . "\n";
        echo "<!-- /SunEditor Assets -->\n\n";
        
        self::$assetsEnqueued = true;
    }
    
    /**
     * Render simple textarea (fallback without WYSIWYG)
     * 
     * @param string $name Field name
     * @param string $content Content
     * @param array $settings Settings (rows, cols, etc.)
     * @return string Textarea HTML
     */
    public function renderSimple(string $name, string $content = '', array $settings = []): string
    {
        $rows = $settings['rows'] ?? 10;
        $cols = $settings['cols'] ?? 50;
        $classes = $settings['class'] ?? 'form-control';
        
        $escapedContent = htmlspecialchars($content ?? '', ENT_QUOTES, 'UTF-8');
        
        return sprintf(
            '<textarea name="%s" rows="%d" cols="%d" class="%s">%s</textarea>',
            htmlspecialchars($name),
            (int)$rows,
            (int)$cols,
            htmlspecialchars($classes),
            $escapedContent
        );
    }
    
    /**
     * Sanitize editor content
     * 
     * Removes dangerous HTML while preserving safe formatting
     * 
     * @param string $content Raw content from editor
     * @return string Sanitized content
     */
    public function sanitize(string $content): string
    {
        // Allow safe HTML tags
        $allowedTags = [
            'p', 'br', 'strong', 'em', 'u', 's', 'del', 'b', 'i',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'ul', 'ol', 'li',
            'blockquote', 'pre', 'code',
            'a', 'img',
            'table', 'thead', 'tbody', 'tr', 'th', 'td',
            'div', 'span', 'hr',
            'sub', 'sup'
        ];
        
        $allowedAttributes = [
            'a' => ['href', 'title', 'target', 'rel'],
            'img' => ['src', 'alt', 'title', 'width', 'height', 'class', 'style'],
            'table' => ['class', 'border', 'cellpadding', 'cellspacing'],
            'td' => ['colspan', 'rowspan'],
            'th' => ['colspan', 'rowspan'],
            'div' => ['class', 'style'],
            'span' => ['class', 'style'],
            'p' => ['style'],
            'h1' => ['style'], 'h2' => ['style'], 'h3' => ['style'],
            'h4' => ['style'], 'h5' => ['style'], 'h6' => ['style']
        ];
        
        // Build allowed tags string
        $allowedTagsString = '<' . implode('><', $allowedTags) . '>';
        
        // Strip dangerous tags
        $sanitized = strip_tags($content, $allowedTagsString);
        
        // Remove dangerous attributes (event handlers, javascript:)
        $sanitized = preg_replace('/(<[^>]+)\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '$1', $sanitized ?? '');
        $sanitized = preg_replace('/(<[^>]+)\s+href\s*=\s*["\']javascript:[^"\']*["\']/i', '$1', $sanitized ?? '');
        
        return $sanitized ?? '';
    }
}
