<?php
/**
 * Support & Dokumentation
 *
 * Lädt Dokumentations-Dateien direkt aus dem GitHub-Repository
 * PS-easyIT/365CMS.DE, Ordner /DOC, via GitHub Contents API.
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

// ─── Konstanten ──────────────────────────────────────────────────────────────

const GITHUB_OWNER    = 'PS-easyIT';
const GITHUB_REPO     = '365CMS.DE';
const GITHUB_DOC_PATH = 'DOC';
const GITHUB_BRANCH   = 'main';
const GITHUB_API_BASE = 'https://api.github.com/repos/' . GITHUB_OWNER . '/' . GITHUB_REPO . '/contents/';
const GITHUB_RAW_BASE = 'https://raw.githubusercontent.com/' . GITHUB_OWNER . '/' . GITHUB_REPO . '/' . GITHUB_BRANCH . '/';

// Optionaler GitHub-Token für höhere Rate-Limits (aus config.php: GITHUB_TOKEN)
$githubToken = defined('GITHUB_TOKEN') ? GITHUB_TOKEN : '';

// ─── GitHub API Helper ───────────────────────────────────────────────────────

/**
 * HTTP GET gegen GitHub API oder Raw-Content.
 * Bei Fehler wird null zurückgegeben (kein Exception-Wurf).
 */
function githubGet(string $url, string $token = ''): ?string
{
    $headers = [
        'User-Agent: 365CMS-Admin/2.0',
        'Accept: application/vnd.github.v3+json',
    ];
    if ($token !== '') {
        $headers[] = 'Authorization: token ' . $token;
    }

    $ctx = stream_context_create([
        'http' => [
            'header'  => implode("\r\n", $headers),
            'timeout' => 8,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer'      => true,
            'verify_peer_name' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $ctx);
    return $response === false ? null : $response;
}

/**
 * Rekursiv alle .md-Dateien in einem GitHub-Verzeichnispfad laden.
 * Wird intern von fetchDocList() aufgerufen – kein eigenes Caching.
 *
 * @param string $apiPath  Pfad relativ zum Repo-Root (z. B. "DOC" oder "DOC/admin")
 * @param string $token    Optionaler GitHub-Token
 * @param string $relDir   Aktueller Unterordner relativ zu DOC ('' = Root)
 * @return array<array{name:string, path:string, dir:string, sha:string, download_url:string}>
 */
function fetchDocTree(string $apiPath, string $token = '', string $relDir = ''): array
{
    $body = githubGet(GITHUB_API_BASE . $apiPath . '?ref=' . GITHUB_BRANCH, $token);
    if ($body === null) return [];

    $items = json_decode($body, true);
    if (!is_array($items)) return [];

    $files = [];
    $dirs  = [];

    foreach ($items as $item) {
        $type = $item['type'] ?? '';
        $name = $item['name'] ?? '';

        if ($type === 'file' && str_ends_with(strtolower($name), '.md')) {
            $files[] = [
                'name'         => $name,
                'path'         => $item['path'],
                'dir'          => $relDir,
                'sha'          => $item['sha'] ?? '',
                'download_url' => $item['download_url'] ?? '',
            ];
        } elseif ($type === 'dir') {
            $dirs[] = $item;
        }
    }

    // Dateien im aktuellen Verzeichnis alphabetisch sortieren
    usort($files, fn($a, $b) => strcmp($a['name'], $b['name']));
    // Unterordner alphabetisch sortieren
    usort($dirs, fn($a, $b) => strcmp($a['name'], $b['name']));

    // Rekursiv in Unterordner abtauchen
    foreach ($dirs as $dir) {
        $subRelDir = $relDir === '' ? $dir['name'] : $relDir . '/' . $dir['name'];
        $subFiles  = fetchDocTree($dir['path'], $token, $subRelDir);
        $files     = array_merge($files, $subFiles);
    }

    return $files;
}

/**
 * Listet alle .md-Dateien im /DOC-Verzeichnis des Repos (inkl. Unterordner).
 * Ergebnis wird 10 Minuten gecacht (file-based).
 *
 * @return array<array{name:string, path:string, dir:string, sha:string, download_url:string}>
 */
function fetchDocList(string $token = ''): array
{
    $cacheFile = sys_get_temp_dir() . '/365cms_docs_list.json';
    $cacheTTL  = 600; // 10 Minuten

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached) && count($cached) > 0 && isset($cached[0]['dir'])) {
            return $cached;
        }
    }

    $docs = fetchDocTree(GITHUB_DOC_PATH, $token);

    file_put_contents($cacheFile, json_encode($docs));
    return $docs;
}

/**
 * Lädt den Raw-Inhalt einer Markdown-Datei.
 * Ergebnis wird 10 Minuten gecacht.
 */
function fetchDocContent(string $filePath, string $token = ''): ?string
{
    $cacheKey  = preg_replace('/[^a-z0-9_]/i', '_', $filePath);
    $cacheFile = sys_get_temp_dir() . '/365cms_doc_' . $cacheKey . '.md';
    $cacheTTL  = 600;

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
        return (string) file_get_contents($cacheFile);
    }

    $url     = GITHUB_RAW_BASE . $filePath;
    $content = githubGet($url, $token);

    if ($content !== null) {
        file_put_contents($cacheFile, $content);
    }

    return $content;
}

// ─── Verarbeitung ────────────────────────────────────────────────────────────

// Cache-Invalidierung via GET ?refresh=1 (nur Admin)
if (($_GET['refresh'] ?? '') === '1') {
    $pattern = sys_get_temp_dir() . '/365cms_doc*.json';
    foreach (glob($pattern) ?: [] as $f) {
        @unlink($f);
    }
    $pattern2 = sys_get_temp_dir() . '/365cms_doc_*.md';
    foreach (glob($pattern2) ?: [] as $f) {
        @unlink($f);
    }
    header('Location: ' . SITE_URL . '/admin/support');
    exit;
}

$docList    = fetchDocList($githubToken);

// INDEX.md immer an erster Stelle in $docList (garantierter Fallback)
$indexKey = null;
foreach ($docList as $k => $doc) {
    if (($doc['dir'] ?? '') === '' && strtolower($doc['name']) === 'index.md') {
        $indexKey = $k;
        break;
    }
}
if ($indexKey !== null && $indexKey !== 0) {
    $indexEntry = array_splice($docList, $indexKey, 1);
    array_unshift($docList, $indexEntry[0]);
}

$activeDoc  = $_GET['doc'] ?? '';
$docContent = null;
$docTitle   = '';

// Sicherheitsprüfung: Pfad darf nur im /DOC-Verzeichnis liegen, kein Path-Traversal
if ($activeDoc !== '') {
    // Bereinigen: kein "..", kein Backslash, kein Null-Byte
    $cleanDoc = str_replace(['..', '\\', "\0"], '', $activeDoc);
    $cleanDoc = trim($cleanDoc, '/');
    $safePath = GITHUB_DOC_PATH . '/' . $cleanDoc;

    if (
        str_starts_with($safePath, GITHUB_DOC_PATH . '/') &&
        str_ends_with(strtolower($safePath), '.md') &&
        !str_contains($safePath, '//')
    ) {
        $docContent = fetchDocContent($safePath, $githubToken);
        $docTitle   = pathinfo(basename($safePath), PATHINFO_FILENAME);
        $docTitle   = str_replace(['-', '_'], ' ', $docTitle);
        $activeDoc  = $cleanDoc; // normalisierter Pfad relativ zu DOC/
    } else {
        $activeDoc = '';
    }
}

// Wenn kein Doc gewählt → INDEX.md bevorzugen, sonst erstes Dokument
if ($docContent === null && count($docList) > 0) {
    // INDEX.md im Root zuerst suchen (case-insensitive)
    $indexDoc = null;
    foreach ($docList as $candidate) {
        if (($candidate['dir'] ?? '') === '' && strtolower($candidate['name']) === 'index.md') {
            $indexDoc = $candidate;
            break;
        }
    }
    $firstDoc   = $indexDoc ?? $docList[0];
    $docContent = fetchDocContent($firstDoc['path'], $githubToken);
    $docTitle   = pathinfo($firstDoc['name'], PATHINFO_FILENAME);
    $docTitle   = str_replace(['-', '_'], ' ', $docTitle);
    // DOC-relativer Pfad (z. B. "INDEX.md" oder "admin/INSTALL.md")
    $activeDoc  = substr($firstDoc['path'], strlen(GITHUB_DOC_PATH) + 1);
}

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
    <!-- Markdown-Renderer (marked.js + highlight.js für Code-Blöcke) -->
    <script src="https://cdn.jsdelivr.net/npm/marked@12.0.0/marked.min.js"></script>
    <link  rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/styles/github.min.css">
    <script src="https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/highlight.min.js"></script>
    <?php renderAdminSidebarStyles(); ?>
    <style>
        /* ── Layout ── */
        .support-layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 1.5rem;
            align-items: start;
        }
        @media (max-width: 860px) {
            .support-layout { grid-template-columns: 1fr; }
            .docs-sidebar    { display: none; }
        }

        /* ── Docs Sidebar ── */
        .docs-sidebar {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            position: sticky;
            top: 1.5rem;
        }
        .docs-sidebar-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: .75rem 1rem;
            font-size: .8125rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .06em;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .docs-sidebar-list {
            list-style: none;
            margin: 0;
            padding: .375rem 0;
        }
        .docs-sidebar-list li a {
            display: block;
            padding: .5rem 1rem;
            font-size: .8125rem;
            color: #475569;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all .12s;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .docs-sidebar-list li a:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        .docs-sidebar-list li a.active {
            background: #eff6ff;
            color: #2563eb;
            border-left-color: #2563eb;
            font-weight: 600;
        }
        .docs-sidebar-empty {
            padding: 1.25rem 1rem;
            font-size: .8125rem;
            color: #94a3b8;
            text-align: center;
        }

        /* ── Sidebar-Gruppen (Unterordner) ── */
        .docs-sidebar-group {
            list-style: none;
        }
        .docs-sidebar-group details > summary {
            display: flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem 1rem;
            font-size: .75rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .06em;
            cursor: pointer;
            user-select: none;
            list-style: none;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
        }
        .docs-sidebar-group details > summary::-webkit-details-marker { display: none; }
        .docs-sidebar-group details > summary::before {
            content: '▶';
            font-size: .6rem;
            transition: transform .15s;
            color: #cbd5e1;
        }
        .docs-sidebar-group details[open] > summary::before {
            transform: rotate(90deg);
        }
        .docs-sidebar-sublist {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .docs-sidebar-sublist li a {
            display: block;
            padding: .5rem 1rem .5rem 1.75rem;
            font-size: .8125rem;
            color: #475569;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all .12s;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .docs-sidebar-sublist li a:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        .docs-sidebar-sublist li a.active {
            background: #eff6ff;
            color: #2563eb;
            border-left-color: #2563eb;
            font-weight: 600;
        }
        .docs-sidebar-root-header {
            display: flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem 1rem;
            font-size: .75rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .06em;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
        }
        .docs-sidebar-root-header:first-child { border-top: none; }
        .dir-icon { font-style: normal; flex-shrink: 0; }

        /* ── Doc Viewer ── */
        .doc-viewer {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 2rem 2.25rem;
            min-height: 400px;
        }
        .doc-viewer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.75rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .doc-viewer-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            text-transform: capitalize;
        }
        .doc-actions {
            display: flex;
            gap: .5rem;
            flex-shrink: 0;
        }
        .btn-doc {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .4rem .875rem;
            border-radius: 6px;
            font-size: .8rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #374151;
            transition: all .12s;
        }
        .btn-doc:hover { background: #f8fafc; }
        .btn-doc.primary { background: #2563eb; color: #fff; border-color: #2563eb; }
        .btn-doc.primary:hover { background: #1d4ed8; }

        /* ── Markdown Styles ── */
        .md-content { font-size: .9rem; line-height: 1.75; color: #334155; }
        .md-content h1 { font-size: 1.6rem; font-weight: 800; color: #0f172a; margin: 0 0 1rem; border-bottom: 2px solid #e2e8f0; padding-bottom: .5rem; }
        .md-content h2 { font-size: 1.2rem; font-weight: 700; color: #1e293b; margin: 1.75rem 0 .75rem; padding-bottom: .375rem; border-bottom: 1px solid #f1f5f9; }
        .md-content h3 { font-size: 1rem; font-weight: 700; color: #334155; margin: 1.25rem 0 .5rem; }
        .md-content h4 { font-size: .9375rem; font-weight: 600; color: #475569; margin: 1rem 0 .4rem; }
        .md-content p  { margin: 0 0 1rem; }
        .md-content ul, .md-content ol { margin: 0 0 1rem 1.5rem; padding: 0; }
        .md-content li { margin-bottom: .375rem; }
        .md-content code {
            background: #f1f5f9;
            color: #e11d48;
            padding: .15em .4em;
            border-radius: 4px;
            font-size: .85em;
            font-family: 'Consolas', 'Monaco', monospace;
        }
        .md-content pre {
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 1.25rem 1.5rem;
            overflow-x: auto;
            margin: 0 0 1.25rem;
            font-size: .825rem;
            line-height: 1.6;
        }
        .md-content pre code {
            background: transparent;
            color: inherit;
            padding: 0;
            border-radius: 0;
            font-size: inherit;
        }
        .md-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 1.25rem;
            font-size: .875rem;
        }
        .md-content th {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: .5rem .875rem;
            text-align: left;
            font-weight: 700;
            color: #1e293b;
        }
        .md-content td {
            border: 1px solid #e2e8f0;
            padding: .5rem .875rem;
            color: #475569;
        }
        .md-content tr:nth-child(even) td { background: #f8fafc; }
        .md-content blockquote {
            border-left: 4px solid #3b82f6;
            background: #eff6ff;
            padding: .75rem 1rem;
            margin: 0 0 1rem;
            border-radius: 0 6px 6px 0;
            color: #1e40af;
        }
        .md-content a { color: #2563eb; }
        .md-content hr { border: none; border-top: 1px solid #e2e8f0; margin: 1.5rem 0; }
        .md-content img { max-width: 100%; border-radius: 6px; }

        /* ── Info / Warn Boxes ── */
        .github-notice {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-left: 4px solid #0ea5e9;
            border-radius: 8px;
            padding: .875rem 1.25rem;
            margin-bottom: 1.5rem;
            font-size: .8125rem;
            color: #0c4a6e;
            display: flex;
            align-items: flex-start;
            gap: .75rem;
        }
        .github-notice a { color: #0369a1; font-weight: 600; }
        .online-badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            font-size: .7rem;
            font-weight: 700;
            padding: .2rem .6rem;
            border-radius: 10px;
            vertical-align: middle;
        }
        .online-badge.ok      { background: #dcfce7; color: #166534; }
        .online-badge.offline { background: #fee2e2; color: #991b1b; }
        .doc-spinner { text-align: center; padding: 3rem; color: #94a3b8; font-size: .9rem; }
    </style>
</head>
<body class="admin-body">

    <?php renderAdminSidebar('support'); ?>

    <div class="admin-content">

        <!-- Page Header -->
        <div class="admin-page-header">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2>📖 Support &amp; Dokumentation</h2>
                    <p style="color:#64748b;font-size:.875rem;margin:.25rem 0 0;">
                        Offizielle Dokumentation aus dem
                        <a href="https://github.com/<?php echo GITHUB_OWNER; ?>/<?php echo GITHUB_REPO; ?>/tree/<?php echo GITHUB_BRANCH; ?>/<?php echo GITHUB_DOC_PATH; ?>"
                           target="_blank" style="color:#2563eb;font-weight:600;">
                            GitHub Repository
                        </a>
                        – immer aktuell direkt aus der Quelle.
                    </p>
                </div>
                <div style="display:flex;gap:.625rem;align-items:center;">
                    <a href="?refresh=1" class="btn-doc" title="Docs-Cache leeren und neu laden">
                        🔄 Neu laden
                    </a>
                    <a href="https://github.com/<?php echo GITHUB_OWNER; ?>/<?php echo GITHUB_REPO; ?>/tree/main/<?php echo GITHUB_DOC_PATH; ?>"
                       target="_blank" class="btn-doc primary">
                        📂 GitHub öffnen
                    </a>
                </div>
            </div>
        </div>

        <!-- GitHub Notice -->
        <div class="github-notice">
            <span style="font-size:1.25rem;flex-shrink:0;">ℹ️</span>
            <div>
                Die Dokumentation wird <strong>live von GitHub geladen</strong> und für 10 Minuten gecacht.
                Quelle: <a href="https://github.com/<?php echo GITHUB_OWNER; ?>/<?php echo GITHUB_REPO; ?>" target="_blank">
                    github.com/<?php echo GITHUB_OWNER; ?>/<?php echo GITHUB_REPO; ?>
                </a> / <code style="background:#e0f2fe;color:#0c4a6e;padding:.1em .3em;border-radius:3px;"><?php echo GITHUB_DOC_PATH; ?>/</code>
                <?php if (count($docList) > 0): ?>
                    &nbsp;<span class="online-badge ok">✓ Verbunden · <?php echo count($docList); ?> Dokumente</span>
                <?php else: ?>
                    &nbsp;<span class="online-badge offline">✗ Nicht erreichbar</span>
                    &mdash; GitHub API nicht verfügbar oder /DOC-Ordner noch nicht hochgeladen.
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Layout -->
        <div class="support-layout">

            <!-- ── Seitenliste ─────────────────────────────────────────── -->
            <aside class="docs-sidebar">
                <div class="docs-sidebar-header">
                    <span>📄 Dokumente</span>
                    <span style="font-weight:400;color:#94a3b8;"><?php echo count($docList); ?></span>
                </div>

                <?php if (count($docList) === 0): ?>
                    <div class="docs-sidebar-empty">
                        Noch keine Dokumente verfügbar.<br>
                        <small>Lade /<?php echo GITHUB_DOC_PATH; ?> ins GitHub-Repo hoch.</small>
                    </div>
                <?php else: ?>
                    <?php
                    // Ordner-Kategorien mit Icons + lesbaren Labels
                    $dirLabels = [
                        ''         => ['icon' => '📄', 'label' => 'Übersicht'],
                        'admin'    => ['icon' => '⚙️',  'label' => 'Admin-Bereich'],
                        'core'     => ['icon' => '🔧', 'label' => 'Core & System'],
                        'feature'  => ['icon' => '✨', 'label' => 'Features'],
                        'member'   => ['icon' => '👤', 'label' => 'Mitglieder'],
                        'plugins'  => ['icon' => '🔌', 'label' => 'Plugins'],
                        'theme'    => ['icon' => '🎨', 'label' => 'Theme & Design'],
                        'workflow' => ['icon' => '🔄', 'label' => 'Workflows'],
                        'audits'   => ['icon' => '🔍', 'label' => 'Audits'],
                    ];

                    // Nach Unterordner gruppieren; Root-Einträge zuerst
                    $grouped = [];
                    foreach ($docList as $doc) {
                        $grouped[$doc['dir'] ?? ''][] = $doc;
                    }

                    // INDEX.md im Root immer ganz oben
                    if (isset($grouped[''])) {
                        usort($grouped[''], function($a, $b) {
                            $aIsIndex = strtolower($a['name']) === 'index.md';
                            $bIsIndex = strtolower($b['name']) === 'index.md';
                            if ($aIsIndex) return -1;
                            if ($bIsIndex) return  1;
                            return strcmp($a['name'], $b['name']);
                        });
                    }

                    // Reihenfolge: Root, dann bekannte Ordner in definieter Reihenfolge, dann Rest
                    $knownOrder = ['', 'core', 'admin', 'member', 'plugins', 'theme', 'feature', 'workflow', 'audits'];
                    $orderedGrouped = [];
                    foreach ($knownOrder as $k) {
                        if (isset($grouped[$k])) {
                            $orderedGrouped[$k] = $grouped[$k];
                            unset($grouped[$k]);
                        }
                    }
                    uksort($grouped, fn($a, $b) => strcmp($a, $b));
                    $grouped = array_merge($orderedGrouped, $grouped);
                    ?>
                    <ul class="docs-sidebar-list">
                        <?php foreach ($grouped as $dir => $dirDocs): ?>
                            <?php if ($dir !== ''): ?>
                                <!-- Unterordner-Gruppe -->
                                <?php
                                // Gruppe aufklappen wenn aktives Dokument darin liegt
                                $groupIsActive = str_starts_with($activeDoc, $dir . '/');
                                $dirInfo = $dirLabels[$dir] ?? ['icon' => '📁', 'label' => ucfirst(str_replace(['-', '_'], ' ', $dir))];
                                ?>
                                <li class="docs-sidebar-group">
                                    <details>
                                        <summary>
                                            <span class="dir-icon"><?php echo $dirInfo['icon']; ?></span>
                                            <?php echo htmlspecialchars($dirInfo['label']); ?>
                                        </summary>
                                        <ul class="docs-sidebar-sublist">
                                            <?php foreach ($dirDocs as $doc):
                                                $docRelPath = substr($doc['path'], strlen(GITHUB_DOC_PATH) + 1);
                                                $docLabel   = str_replace(['-', '_'], ' ', pathinfo($doc['name'], PATHINFO_FILENAME));
                                                $isActive   = ($activeDoc === $docRelPath);
                                            ?>
                                            <li>
                                                <a href="?doc=<?php echo urlencode($docRelPath); ?>"
                                                   class="<?php echo $isActive ? 'active' : ''; ?>"
                                                   title="<?php echo htmlspecialchars($docLabel); ?>">
                                                    <?php echo htmlspecialchars($docLabel); ?>
                                                </a>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </details>
                                </li>
                            <?php else: ?>
                                <!-- Root-Dateien -->
                                <?php $rootInfo = $dirLabels[''] ?? ['icon' => '📄', 'label' => 'Übersicht']; ?>
                                <li class="docs-sidebar-root-header">
                                    <span class="dir-icon"><?php echo $rootInfo['icon']; ?></span>
                                    <?php echo htmlspecialchars($rootInfo['label']); ?>
                                </li>
                                <?php foreach ($dirDocs as $doc):
                                    $docRelPath = substr($doc['path'], strlen(GITHUB_DOC_PATH) + 1);
                                    $docLabel   = str_replace(['-', '_'], ' ', pathinfo($doc['name'], PATHINFO_FILENAME));
                                    $isActive   = ($activeDoc === $docRelPath);
                                ?>
                                <li>
                                    <a href="?doc=<?php echo urlencode($docRelPath); ?>"
                                       class="<?php echo $isActive ? 'active' : ''; ?>"
                                       title="<?php echo htmlspecialchars($docLabel); ?>">
                                        <?php echo htmlspecialchars($docLabel); ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </aside>

            <!-- ── Dokument-Viewer ────────────────────────────────────── -->
            <main class="doc-viewer">

                <?php if ($docContent !== null): ?>
                    <div class="doc-viewer-header">
                        <h1 class="doc-viewer-title">
                            <?php echo htmlspecialchars(ucwords(strtolower($docTitle))); ?>
                        </h1>
                        <div class="doc-actions">
                            <?php
                            // $activeDoc enthält den DOC-relativen Pfad (z. B. "README.md" oder "admin/INSTALL.md")
                            $currentDocFile = count($docList) > 0
                                ? ($activeDoc !== ''
                                    ? GITHUB_DOC_PATH . '/' . $activeDoc
                                    : $docList[0]['path'])
                                : '';
                            $ghUrl = $currentDocFile
                                ? 'https://github.com/' . GITHUB_OWNER . '/' . GITHUB_REPO . '/blob/main/' . $currentDocFile
                                : '#';
                            $rawUrl = $currentDocFile
                                ? GITHUB_RAW_BASE . $currentDocFile
                                : '#';
                            ?>
                            <a href="<?php echo htmlspecialchars($rawUrl); ?>" target="_blank" class="btn-doc" title="Raw-Datei anzeigen">
                                📄 Raw
                            </a>
                            <a href="<?php echo htmlspecialchars($ghUrl); ?>" target="_blank" class="btn-doc primary" title="Auf GitHub öffnen">
                                🔗 GitHub
                            </a>
                        </div>
                    </div>

                    <!-- Markdown-Inhalt wird via JS gerendert -->
                    <div id="md-raw" style="display:none;"><?php echo htmlspecialchars($docContent, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div id="md-output" class="md-content">
                        <div class="doc-spinner">⏳ Dokument wird gerendert...</div>
                    </div>

                <?php else: ?>
                    <!-- Kein Dokument geladen -->
                    <div style="text-align:center;padding:4rem 2rem;color:#64748b;">
                        <div style="font-size:3rem;margin-bottom:1rem;">📂</div>
                        <h3 style="color:#94a3b8;font-weight:600;margin:0 0 .5rem;">Keine Dokumente gefunden</h3>
                        <p style="font-size:.875rem;margin:0 0 1.5rem;">
                            Der <code>/<?php echo GITHUB_DOC_PATH; ?></code>-Ordner wurde noch nicht ins GitHub-Repository hochgeladen
                            oder die GitHub API ist aktuell nicht erreichbar.
                        </p>
                        <a href="https://github.com/<?php echo GITHUB_OWNER; ?>/<?php echo GITHUB_REPO; ?>"
                           target="_blank" class="btn-doc primary">
                            📂 Zum GitHub-Repository
                        </a>
                    </div>
                <?php endif; ?>

            </main>

        </div><!-- /.support-layout -->

    </div><!-- /.admin-content -->

    <script>
    // ── Markdown rendern ─────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        const rawEl = document.getElementById('md-raw');
        const outEl = document.getElementById('md-output');
        if (!rawEl || !outEl) return;

        // marked.js konfigurieren
        if (typeof marked !== 'undefined') {
            marked.setOptions({
                breaks:  true,
                gfm:     true,
                pedantic: false,
                // Code-Highlighting via highlight.js
                highlight: typeof hljs !== 'undefined'
                    ? function(code, lang) {
                        if (lang && hljs.getLanguage(lang)) {
                            return hljs.highlight(code, { language: lang }).value;
                        }
                        return hljs.highlightAuto(code).value;
                    }
                    : null,
            });

            // HTML-Entitäten rückgängig machen (PHP hat escaped)
            const raw = rawEl.textContent
                .replace(/&amp;/g,  '&')
                .replace(/&lt;/g,   '<')
                .replace(/&gt;/g,   '>')
                .replace(/&quot;/g, '"')
                .replace(/&#039;/g, "'");

            outEl.innerHTML = marked.parse(raw);

            // highlight.js auf bereits gerendertem Code anwenden
            if (typeof hljs !== 'undefined') {
                outEl.querySelectorAll('pre code').forEach(function(el) {
                    hljs.highlightElement(el);
                });
            }
        } else {
            // Fallback: Pre-formatted plain text
            outEl.innerHTML = '<pre style="white-space:pre-wrap;font-size:.85rem;">'
                + rawEl.textContent + '</pre>';
        }
    });

    // ── Aktiver Link in Sidebar highlighten ──────────────────────────────────
    document.querySelectorAll('.docs-sidebar-list a').forEach(function(link) {
        link.addEventListener('click', function() {
            document.querySelectorAll('.docs-sidebar-list a').forEach(function(l) {
                l.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
    </script>

</body>
</html>
