<?php
/**
 * Support & Dokumentation
 *
 * Zeigt eine Übersichtsseite je DOC-Bereich mit direkten GitHub-Links.
 * Kein Laden von Dateiinhalten – kein Hängen.
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;

if (!defined('ABSPATH')) {
    exit;
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

// ─── Konstanten ───────────────────────────────────────────────────────────────

const GITHUB_OWNER    = 'PS-easyIT';
const GITHUB_REPO     = '365CMS.DE';
const GITHUB_BRANCH   = 'main';
const GITHUB_DOC_PATH = 'DOC';
const GITHUB_API_TREE = 'https://api.github.com/repos/' . GITHUB_OWNER . '/' . GITHUB_REPO . '/git/trees/' . GITHUB_BRANCH . '?recursive=1';
const GITHUB_BROWSE   = 'https://github.com/' . GITHUB_OWNER . '/' . GITHUB_REPO . '/blob/' . GITHUB_BRANCH . '/';

// ─── Dateiliste (gecacht, 5 min) ──────────────────────────────────────────────

function fetchDocList(): array
{
    $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . '365cms_doclist_' . md5(GITHUB_REPO) . '.json';
    $cacheTTL  = 300;

    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached) && count($cached) > 0) {
            return $cached;
        }
    }

    // HTTP-Request
    $body = null;
    $url  = GITHUB_API_TREE;
    $hdrs = ['Accept: application/vnd.github+json', 'User-Agent: 365CMS-Support/2.0'];

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_HTTPHEADER     => $hdrs,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp !== false && $code === 200) $body = $resp;
    } elseif (ini_get('allow_url_fopen')) {
        $ctx  = stream_context_create(['http' => ['header' => implode("\r\n", $hdrs), 'timeout' => 6]]);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp !== false) $body = $resp;
    }

    if ($body === null) {
        // Abgelaufener Cache besser als nichts
        if (is_file($cacheFile)) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($cached)) return $cached;
        }
        return [];
    }

    $data   = json_decode($body, true);
    $docs   = [];
    $prefix = GITHUB_DOC_PATH . '/';

    foreach (($data['tree'] ?? []) as $node) {
        if (($node['type'] ?? '') !== 'blob') continue;
        $path = $node['path'] ?? '';
        if (!str_starts_with($path, $prefix)) continue;
        if (!str_ends_with(strtolower($path), '.md')) continue;

        $relPath = substr($path, strlen($prefix));
        $dir     = str_contains($relPath, '/') ? dirname($relPath) : '';
        $docs[]  = ['name' => basename($relPath), 'path' => $path, 'dir' => $dir];
    }

    if (!empty($docs)) {
        @file_put_contents($cacheFile, json_encode($docs, JSON_UNESCAPED_UNICODE));
    }

    return $docs;
}

// ─── Bereichs-Konfiguration ───────────────────────────────────────────────────

$areaConfig = [
    '__root__'                       => ['label' => 'Allgemein',              'icon' => '📄', 'desc' => 'Allgemeine Projektdokumentation'],
    'admin'                          => ['label' => 'Administration',         'icon' => '🔧', 'desc' => 'Dokumentation des Admin-Bereichs'],
    'admin/dashboard'                => ['label' => 'Dashboard',              'icon' => '🏠', 'desc' => 'Dashboard & Widgets'],
    'admin/landing-page'             => ['label' => 'Landing Page',           'icon' => '🎯', 'desc' => 'Landing Page Builder'],
    'admin/pages-posts'              => ['label' => 'Seiten & Beiträge',      'icon' => '📝', 'desc' => 'Seitenmanagement und Beiträge'],
    'admin/media'                    => ['label' => 'Medien',                 'icon' => '🖼️', 'desc' => 'Medienverwaltung'],
    'admin/users-groups'             => ['label' => 'Benutzer & Gruppen',     'icon' => '👥', 'desc' => 'Benutzerverwaltung und Gruppen'],
    'admin/subscription'             => ['label' => 'Subscriptions',          'icon' => '💳', 'desc' => 'Abonnement-Verwaltung'],
    'admin/themes-design'            => ['label' => 'Themes & Design',        'icon' => '🎨', 'desc' => 'Themes, Design und Customizer'],
    'admin/seo-performance'          => ['label' => 'SEO & Performance',      'icon' => '📈', 'desc' => 'Suchmaschinenoptimierung und Performance'],
    'admin/seo-performance/analytics'=> ['label' => 'Analytics',              'icon' => '📊', 'desc' => 'Besucherstatistiken und Analytics'],
    'admin/legal-security'           => ['label' => 'Recht & Sicherheit',     'icon' => '⚖️', 'desc' => 'Rechtliches, Datenschutz und Sicherheit'],
    'admin/plugins'                  => ['label' => 'Plugins',                'icon' => '🔌', 'desc' => 'Plugin-Verwaltung'],
    'admin/system-settings'          => ['label' => 'System & Einstellungen', 'icon' => '⚙️', 'desc' => 'Systemkonfiguration und Einstellungen'],
    'member'                         => ['label' => 'Mitglieder',             'icon' => '👤', 'desc' => 'Mitglieder-Bereich'],
    'member/general'                 => ['label' => 'Mitglieder Allgemein',   'icon' => '👤', 'desc' => 'Allgemeine Mitglieder-Dokumentation'],
    'plugins'                        => ['label' => 'Plugin-Entwicklung',     'icon' => '🔌', 'desc' => 'Plugin-Entwicklung und API'],
    'theme'                          => ['label' => 'Theme-Entwicklung',      'icon' => '🎨', 'desc' => 'Theme-Entwicklung und Templates'],
    'feature'                        => ['label' => 'Feature-Guides',         'icon' => '✨', 'desc' => 'Feature-Dokumentation und Guides'],
    'workflow'                       => ['label' => 'Workflows',              'icon' => '🔄', 'desc' => 'Entwicklungs-Workflows'],
    'audits'                         => ['label' => 'Audits',                 'icon' => '🔍', 'desc' => 'Code- und Sicherheitsaudits'],
    'screenshots'                    => ['label' => 'Screenshots',            'icon' => '📷', 'desc' => 'Screenshots und Medien'],
];

$sidebarOrder = [
    '__root__', 'admin', 'admin/dashboard', 'admin/landing-page', 'admin/pages-posts',
    'admin/media', 'admin/users-groups', 'admin/subscription',
    'admin/themes-design', 'admin/seo-performance', 'admin/seo-performance/analytics',
    'admin/legal-security', 'admin/plugins', 'admin/system-settings',
    'member', 'member/general', 'plugins', 'theme', 'feature', 'workflow', 'audits', 'screenshots',
];

// ─── Verarbeitung ─────────────────────────────────────────────────────────────

// ?refresh=1 → Cache löschen
if (($_GET['refresh'] ?? '') === '1') {
    $cf = sys_get_temp_dir() . DIRECTORY_SEPARATOR . '365cms_doclist_' . md5(GITHUB_REPO) . '.json';
    if (is_file($cf)) @unlink($cf);
    header('Location: ' . SITE_URL . '/admin/support');
    exit;
}

$allDocs   = fetchDocList();
$activeArea = $_GET['area'] ?? '__root__';

// Dokumente nach Verzeichnis gruppieren
$groups = [];
foreach ($allDocs as $doc) {
    $key          = $doc['dir'] === '' ? '__root__' : $doc['dir'];
    $groups[$key][] = $doc;
}

// Alle vorhandenen Bereiche (für Sidebar)
$availableDirs = array_keys($groups);

// Aktiven Bereich bestimmen → erster vorhandener als Fallback
if (!isset($groups[$activeArea])) {
    $activeArea = !empty($availableDirs) ? $availableDirs[0] : '__root__';
}

$activeDocs  = $groups[$activeArea] ?? [];
$activeLabel = $areaConfig[$activeArea]['label']  ?? ucfirst(basename($activeArea));
$activeIcon  = $areaConfig[$activeArea]['icon']   ?? '📁';
$activeDesc  = $areaConfig[$activeArea]['desc']   ?? '';

require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support & Docs – <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        /* ── Layout ───────────────────────────────────────────────────── */
        .support-layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 1.5rem;
            align-items: start;
        }
        @media (max-width: 900px) {
            .support-layout { grid-template-columns: 1fr; }
            .docs-sidebar   { display: none; }
        }

        /* ── Sidebar ──────────────────────────────────────────────────── */
        .docs-sidebar {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            position: sticky;
            top: 1.5rem;
            max-height: calc(100vh - 3rem);
            overflow-y: auto;
        }
        .docs-sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .7rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            font-size: .83rem;
            color: #374151;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .docs-nav-group {
            border-bottom: 1px solid #f1f5f9;
        }
        .docs-nav-group:last-child { border-bottom: none; }
        .docs-nav-group > summary {
            display: flex;
            align-items: center;
            gap: .4rem;
            padding: .5rem 1rem;
            cursor: pointer;
            font-size: .79rem;
            font-weight: 600;
            color: #475569;
            background: #f8fafc;
            list-style: none;
            user-select: none;
        }
        .docs-nav-group > summary::-webkit-details-marker { display: none; }
        .docs-nav-group > summary::before {
            content: '▶';
            font-size: .58rem;
            color: #94a3b8;
            transition: transform .2s;
            flex-shrink: 0;
        }
        .docs-nav-group[open] > summary::before { transform: rotate(90deg); }
        .docs-nav-group > summary:hover { background: #f1f5f9; color: #1e293b; }
        .docs-nav-link {
            display: block;
            padding: .38rem 1rem .38rem 2.1rem;
            font-size: .8rem;
            color: #64748b;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: background .12s, color .12s;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .docs-nav-link:hover { background: #f1f5f9; color: #1e293b; }
        .docs-nav-link.active {
            background: #eff6ff;
            color: #2563eb;
            border-left-color: #2563eb;
            font-weight: 600;
        }
        .docs-nav-root {
            display: block;
            padding: .42rem 1rem;
            font-size: .8rem;
            color: #64748b;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: background .12s, color .12s;
        }
        .docs-nav-root:hover { background: #f1f5f9; color: #1e293b; }
        .docs-nav-root.active {
            background: #eff6ff;
            color: #2563eb;
            border-left-color: #2563eb;
            font-weight: 600;
        }

        /* ── Content ──────────────────────────────────────────────────── */
        .docs-content {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.75rem 2rem;
            min-height: 400px;
        }
        .docs-area-header {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .docs-area-icon {
            font-size: 1.8rem;
            line-height: 1;
        }
        .docs-area-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
        }
        .docs-area-desc {
            margin: .2rem 0 0;
            font-size: .83rem;
            color: #64748b;
        }
        .docs-area-meta {
            margin-left: auto;
            font-size: .72rem;
            color: #94a3b8;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: .2rem .7rem;
            white-space: nowrap;
        }

        /* ── Datei-Kacheln ────────────────────────────────────────────── */
        .docs-file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1rem;
        }
        .docs-file-card {
            display: flex;
            flex-direction: column;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem 1.1rem;
            text-decoration: none;
            color: inherit;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }
        .docs-file-card:hover {
            border-color: #3b82f6;
            background: #eff6ff;
            box-shadow: 0 2px 8px rgba(59,130,246,.1);
        }
        .docs-file-card-icon {
            font-size: 1.6rem;
            margin-bottom: .5rem;
        }
        .docs-file-card-name {
            font-size: .9rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: .25rem;
            word-break: break-word;
        }
        .docs-file-card-path {
            font-size: .72rem;
            color: #94a3b8;
            font-family: monospace;
            word-break: break-all;
        }
        .docs-file-card-badge {
            margin-top: .6rem;
            font-size: .7rem;
            color: #2563eb;
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        /* ── Leer-Zustand ─────────────────────────────────────────────── */
        .docs-empty {
            text-align: center;
            padding: 4rem 2rem;
            color: #94a3b8;
        }
        .docs-empty-icon { font-size: 3rem; margin-bottom: 1rem; }

        /* ── Gesamt-Übersicht (kein Bereich gewählt) ─────────────────── */
        .docs-overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        .docs-overview-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.25rem 1rem;
            text-decoration: none;
            color: inherit;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }
        .docs-overview-card:hover {
            border-color: #3b82f6;
            background: #eff6ff;
            box-shadow: 0 2px 10px rgba(59,130,246,.1);
        }
        .docs-overview-card-icon { font-size: 2rem; margin-bottom: .5rem; }
        .docs-overview-card-label {
            font-size: .88rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: .25rem;
        }
        .docs-overview-card-count {
            font-size: .75rem;
            color: #64748b;
        }
    </style>
</head>
<body class="admin-body">

    <?php renderAdminSidebar('support'); ?>

    <!-- Main Content -->
    <div class="admin-content">

        <!-- Page Header -->
        <div class="admin-page-header">
            <h2>Support & Dokumentation</h2>
        </div>

        <div class="support-layout">

            <!-- ── Sidebar ──────────────────────────────────────────────── -->
            <aside class="docs-sidebar">
                <div class="docs-sidebar-header">
                    <span>📚 Bereiche</span>
                    <a href="?refresh=1" title="Liste aktualisieren"
                       style="color:#94a3b8;font-size:.75rem;text-decoration:none;"
                       onclick="this.textContent='…'">↻</a>
                </div>

                <?php if (empty($availableDirs)): ?>
                    <div style="padding:1.5rem 1rem;color:#94a3b8;font-size:.83rem;text-align:center;">
                        Keine Dokumente gefunden.
                    </div>
                <?php else:
                    // Bereiche strukturiert ausgeben: Root direkt, Sub-Bereiche unter "admin", "member" etc.
                    $rootDirs     = array_filter($availableDirs, fn($d) => $d === '__root__');
                    $topLevelDirs = array_filter($availableDirs, fn($d) => $d !== '__root__' && !str_contains($d, '/'));
                    $subDirs      = array_filter($availableDirs, fn($d) => $d !== '__root__' && str_contains($d, '/'));

                    // Root
                    foreach ($rootDirs as $dir):
                        $lbl = $areaConfig[$dir]['label'] ?? 'Allgemein';
                        $ico = $areaConfig[$dir]['icon']  ?? '📄';
                    ?>
                        <a href="?area=<?php echo rawurlencode($dir); ?>"
                           class="docs-nav-root<?php echo $activeArea === $dir ? ' active' : ''; ?>">
                            <?php echo $ico . ' ' . htmlspecialchars($lbl); ?>
                        </a>
                    <?php endforeach;

                    // Top-Level-Bereiche und ihre Unterordner
                    foreach ($sidebarOrder as $dir):
                        if ($dir === '__root__') continue;
                        if (!in_array($dir, $availableDirs, true)) continue;

                        $lbl      = $areaConfig[$dir]['label'] ?? ucfirst(basename($dir));
                        $ico      = $areaConfig[$dir]['icon']  ?? '📁';
                        $isChild  = str_contains($dir, '/');
                        $isActive = $activeArea === $dir;

                        if (!$isChild):
                    ?>
                        <a href="?area=<?php echo rawurlencode($dir); ?>"
                           class="docs-nav-root<?php echo $isActive ? ' active' : ''; ?>">
                            <?php echo $ico . ' ' . htmlspecialchars($lbl); ?>
                        </a>
                    <?php else: ?>
                        <a href="?area=<?php echo rawurlencode($dir); ?>"
                           class="docs-nav-link<?php echo $isActive ? ' active' : ''; ?>">
                            <?php echo $ico . ' ' . htmlspecialchars($lbl); ?>
                        </a>
                    <?php endif; endforeach;

                    // Beliebige weitere Dirs die nicht im sidebarOrder sind
                    $extraDirs = array_diff($availableDirs, $sidebarOrder, ['__root__']);
                    foreach ($extraDirs as $dir):
                        $lbl      = $areaConfig[$dir]['label'] ?? ucfirst(basename($dir));
                        $ico      = $areaConfig[$dir]['icon']  ?? '📁';
                        $isActive = $activeArea === $dir;
                    ?>
                        <a href="?area=<?php echo rawurlencode($dir); ?>"
                           class="docs-nav-link<?php echo $isActive ? ' active' : ''; ?>">
                            <?php echo $ico . ' ' . htmlspecialchars($lbl); ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </aside>

            <!-- ── Bereichs-Inhalt ───────────────────────────────────────── -->
            <main class="docs-content">

                <?php if (empty($allDocs)): ?>
                    <div class="docs-empty">
                        <div class="docs-empty-icon">📭</div>
                        <p>Keine Dokumente gefunden.</p>
                        <p style="font-size:.83rem;color:#94a3b8;">
                            Repo: <code><?php echo GITHUB_OWNER . '/' . GITHUB_REPO; ?></code>
                            &nbsp;/ Pfad: <code><?php echo GITHUB_DOC_PATH . '/'; ?></code>
                        </p>
                        <a href="?refresh=1" style="font-size:.83rem;color:#2563eb;">↻ Erneut versuchen</a>
                    </div>

                <?php elseif ($activeArea === '' || empty($activeDocs)): ?>
                    <!-- Gesamt-Übersicht aller Bereiche -->
                    <div class="docs-area-header">
                        <span class="docs-area-icon">📚</span>
                        <div>
                            <h1 class="docs-area-title">Dokumentation</h1>
                            <p class="docs-area-desc">Wähle einen Bereich in der Seitenleiste</p>
                        </div>
                        <span class="docs-area-meta"><?php echo count($allDocs); ?> Dateien</span>
                    </div>
                    <div class="docs-overview-grid">
                        <?php foreach ($sidebarOrder as $dir):
                            if (!isset($groups[$dir])) continue;
                            $lbl = $areaConfig[$dir]['label'] ?? ucfirst(basename($dir));
                            $ico = $areaConfig[$dir]['icon']  ?? '📁';
                            $cnt = count($groups[$dir]);
                        ?>
                            <a href="?area=<?php echo rawurlencode($dir); ?>" class="docs-overview-card">
                                <span class="docs-overview-card-icon"><?php echo $ico; ?></span>
                                <div class="docs-overview-card-label"><?php echo htmlspecialchars($lbl); ?></div>
                                <div class="docs-overview-card-count"><?php echo $cnt; ?> Datei<?php echo $cnt !== 1 ? 'en' : ''; ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>

                <?php else: ?>
                    <!-- Bereichs-Übersicht -->
                    <div class="docs-area-header">
                        <span class="docs-area-icon"><?php echo $activeIcon; ?></span>
                        <div>
                            <h1 class="docs-area-title"><?php echo htmlspecialchars($activeLabel); ?></h1>
                            <?php if ($activeDesc): ?>
                                <p class="docs-area-desc"><?php echo htmlspecialchars($activeDesc); ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="docs-area-meta">
                            <?php echo count($activeDocs); ?> Datei<?php echo count($activeDocs) !== 1 ? 'en' : ''; ?>
                        </span>
                    </div>

                    <div class="docs-file-grid">
                        <?php foreach ($activeDocs as $doc):
                            // Schöner Anzeigename (ohne .md, Bindestriche als Leerzeichen)
                            $displayName = str_replace(['-', '_'], ' ', pathinfo($doc['name'], PATHINFO_FILENAME));
                            $displayName = ucwords($displayName);
                            // GitHub-Link zum Anzeigen der Datei
                            $ghLink = GITHUB_BROWSE . implode('/', array_map('rawurlencode', explode('/', $doc['path'])));
                        ?>
                            <a href="<?php echo $ghLink; ?>" target="_blank" rel="noopener" class="docs-file-card">
                                <span class="docs-file-card-icon">📄</span>
                                <div class="docs-file-card-name"><?php echo htmlspecialchars($displayName); ?></div>
                                <div class="docs-file-card-path"><?php echo htmlspecialchars($doc['path']); ?></div>
                                <div class="docs-file-card-badge">
                                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                        <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38
                                                 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13
                                                 -.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66
                                                 .07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15
                                                 -.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27
                                                 .68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12
                                                 .51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48
                                                 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/>
                                    </svg>
                                    Auf GitHub öffnen
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </main>

        </div><!-- /.support-layout -->

    </div><!-- /.admin-content -->

</body>
</html>
