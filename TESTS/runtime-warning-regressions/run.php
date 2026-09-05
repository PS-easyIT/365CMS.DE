<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;
$workspace = dirname(rtrim($root, DIRECTORY_SEPARATOR)) . DIRECTORY_SEPARATOR;
$cms = $root . 'CMS' . DIRECTORY_SEPARATOR;

define('ABSPATH', $cms);
define('CMS_PHINIT_THEME_DIR', $workspace . '365CMS.DE-THEME' . DIRECTORY_SEPARATOR . 'cms-phinit' . DIRECTORY_SEPARATOR);

require $cms . 'core/Json.php';
require $cms . 'core/Services/EditorJs/EditorJsHtmlSanitizer.php';
require $cms . 'core/Services/EditorJs/EditorJsContentNormalizer.php';
require CMS_PHINIT_THEME_DIR . 'includes/theme-template-helpers.php';
require $workspace . '365CMS.DE-PLUGINS' . DIRECTORY_SEPARATOR . 'cms-m365landing' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class-repository.php';

use CMS\Services\EditorJs\EditorJsContentNormalizer;

/** @throws RuntimeException */
function runtimeWarningAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    runtimeWarningAssert(
        phinit_extract_upload_relative_path('C:\\uploads\\example.jpg') === '',
        'Windows-Pfade müssen ohne Regex-Warnung abgewiesen werden.'
    );
    runtimeWarningAssert(
        CMS_M365Landing_Repository::normalize_host('https://Example.test/path?value=1#fragment') === 'example.test',
        'Landing-Hostnormalisierung muss Pfad, Query und Fragment ohne Regex-Warnung entfernen.'
    );

    $quote = EditorJsContentNormalizer::normalize([
        'blocks' => [[
            'type' => 'quote',
            'data' => [],
        ]],
    ]);
    $quoteData = (array) ($quote['blocks'][0]['data'] ?? []);
    runtimeWarningAssert(($quoteData['alignment'] ?? '') === 'left', 'Quote-Ausrichtung ohne Eingabewert muss auf left zurückfallen.');
    runtimeWarningAssert(($quoteData['design'] ?? '') === 'bar', 'Quote-Design ohne Eingabewert muss auf bar zurückfallen.');
} finally {
    restore_error_handler();
}

$landingRepository = (string) file_get_contents($workspace . '365CMS.DE-PLUGINS' . DIRECTORY_SEPARATOR . 'cms-m365landing' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class-repository.php');
$eventsDatabase = (string) file_get_contents($workspace . '365CMS.DE-PLUGINS' . DIRECTORY_SEPARATOR . 'cms-365NETeventsandspeaker' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class-database.php');
$expertsDatabase = (string) file_get_contents($workspace . '365CMS.DE-PLUGINS' . DIRECTORY_SEPARATOR . 'cms-365NETexpertsandcompanie' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class-database.php');
$postsModule = (string) file_get_contents($cms . 'admin/modules/posts/PostsModule.php');
$pagesModule = (string) file_get_contents($cms . 'admin/modules/pages/PagesModule.php');
$postsEntry = (string) file_get_contents($cms . 'admin/posts.php');
$pagesEntry = (string) file_get_contents($cms . 'admin/pages.php');
$sectionShell = (string) file_get_contents($cms . 'admin/partials/section-page-shell.php');
$analyticsModule = (string) file_get_contents($cms . 'admin/modules/seo/AnalyticsModule.php');
$seoSuiteModule = (string) file_get_contents($cms . 'admin/modules/seo/SeoSuiteModule.php');
$ordersModule = (string) file_get_contents($cms . 'admin/modules/subscriptions/OrdersModule.php');

runtimeWarningAssert(str_contains($landingRepository, "preg_split('~[/?#]~'"), 'Landing-Hostnormalisierung verwendet keinen sicheren Regex-Delimiter.');
runtimeWarningAssert(!str_contains($eventsDatabase, 'SHOW TABLES LIKE ?'), 'Events-Plugin verwendet weiterhin ein nicht vorbereitbares SHOW TABLES LIKE.');
runtimeWarningAssert(!str_contains($expertsDatabase, 'SHOW TABLES LIKE ?'), 'Experten-/Firmen-Plugin verwendet weiterhin ein nicht vorbereitbares SHOW TABLES LIKE.');
runtimeWarningAssert(str_contains($eventsDatabase, '$db->tableExists('), 'Events-Plugin nutzt den Core-Tabellenhelper nicht.');
runtimeWarningAssert(str_contains($expertsDatabase, '$db->tableExists('), 'Experten-/Firmen-Plugin nutzt den Core-Tabellenhelper nicht.');
runtimeWarningAssert(str_contains($postsModule, 'findUnexpectedNonScalarInput'), 'Beiträge validieren nicht skalare POST-Werte nicht.');
runtimeWarningAssert(str_contains($pagesModule, 'findUnexpectedNonScalarInput'), 'Seiten validieren nicht skalare POST-Werte nicht.');
runtimeWarningAssert(str_contains($postsModule, 'foreach ($value as $entry)'), 'Post-Mehrfachfelder prüfen verschachtelte Werte nicht.');
runtimeWarningAssert(str_contains($postsModule, 'if (!is_scalar($categoryId)'), 'Zusätzliche Post-Kategorien prüfen nicht skalare Werte nicht.');
runtimeWarningAssert(str_contains($postsEntry, 'cms_admin_posts_sanitize_inline_post'), 'Beitrags-Re-Render schützt nicht skalare POST-Werte nicht.');
runtimeWarningAssert(str_contains($pagesEntry, 'cms_admin_pages_sanitize_inline_post'), 'Seiten-Re-Render schützt nicht skalare POST-Werte nicht.');
runtimeWarningAssert(str_contains($sectionShell, "is_string(\$token) ? \$token : ''"), 'Array-CSRF-Tokens werden nicht fail-closed abgewiesen.');
runtimeWarningAssert(str_contains($analyticsModule, 'tableExists($this->prefix . \'page_views\')'), 'Analytics verwendet keinen MariaDB-kompatiblen Tabellencheck.');
runtimeWarningAssert(str_contains($seoSuiteModule, 'tableExists($this->prefix . \'page_views\')'), 'SEO-Suite verwendet keinen MariaDB-kompatiblen Tabellencheck.');
runtimeWarningAssert(str_contains($ordersModule, 'tableExists($table)'), 'Orders verwendet keinen MariaDB-kompatiblen Tabellencheck.');

echo "Runtime warning regression tests passed.\n";
