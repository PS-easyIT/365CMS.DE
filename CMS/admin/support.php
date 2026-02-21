<?php
/**
 * Support & Dokumentation
 *
 * Lädt .md-Dateien ausschließlich aus dem öffentlichen GitHub-Repository.
 * Dateiliste: GitHub API  → api.github.com  (kein Token nötig, public Repo)
 * Inhalt:     GitHub Raw  → raw.githubusercontent.com
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

// ─── GitHub-Konstanten ────────────────────────────────────────────────────────

const GITHUB_OWNER    = 'PS-easyIT';
const GITHUB_REPO     = '365CMS.DE';
const GITHUB_BRANCH   = 'main';
const GITHUB_DOC_PATH = 'DOC';
const GITHUB_RAW_BASE      = 'https://raw.githubusercontent.com/' . GITHUB_OWNER . '/' . GITHUB_REPO . '/' . GITHUB_BRANCH . '/';
const GITHUB_API_TREE      = 'https://api.github.com/repos/' . GITHUB_OWNER . '/' . GITHUB_REPO . '/git/trees/' . GITHUB_BRANCH . '?recursive=1';
const GITHUB_API_CONTENTS  = 'https://api.github.com/repos/' . GITHUB_OWNER . '/' . GITHUB_REPO . '/contents/';

// ─── HTTP-Hilfsfunktion ───────────────────────────────────────────────────────

/**
 * Lädt eine URL via cURL (Primär) oder file_get_contents (Fallback).
 */
function supportHttpGet(string $url, array $headers = []): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => '365CMS-Support/2.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body !== false && $code === 200) {
            return (string) $body;
        }
        $GLOBALS['_support_last_error'] = "HTTP {$code}" . ($err ? " – {$err}" : '') . " · {$url}";
        return null;
    }

    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => [
                'header'     => implode("\r\n", array_merge(['User-Agent: 365CMS-Support/2.0'], $headers)),
                'timeout'    => 15,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        if ($body !== false) {
            return $body;
        }
        $GLOBALS['_support_last_error'] = "file_get_contents fehlgeschlagen · {$url}";
        return null;
    }

    $GLOBALS['_support_last_error'] = 'Weder cURL noch allow_url_fopen verfügbar';
    return null;
}

// ─── Dateiliste aus GitHub API ────────────────────────────────────────────────

/**
 * Holt alle .md-Dateien unterhalb von DOC/ aus dem GitHub-Repository.
 *
 * @return array<array{name:string, path:string, dir:string}>
 */
function fetchDocList(): array
{
    $body = supportHttpGet(GITHUB_API_TREE, ['Accept: application/vnd.github+json']);
    if ($body === null) {
        return [];
    }

    $data = json_decode($body, true);
    if (!is_array($data) || !isset($data['tree'])) {
        $GLOBALS['_support_last_error'] = 'Ungültige API-Antwort (kein "tree"-Schlüssel)';
        return [];
    }

    $docs   = [];
    $prefix = GITHUB_DOC_PATH . '/';

    foreach ($data['tree'] as $node) {
        if (($node['type'] ?? '') !== 'blob') continue;
        $path = $node['path'] ?? '';
        if (!str_starts_with($path, $prefix)) continue;
        if (!str_ends_with(strtolower($path), '.md')) continue;

        $relPath = substr($path, strlen($prefix)); // z. B. "INDEX.md" | "admin/README.md"
        $dir     = str_contains($relPath, '/') ? dirname($relPath) : '';
        $name    = basename($relPath);

        $docs[] = [
            'name' => $name,
            'path' => $path,
            'dir'  => $dir,
        ];
    }

    // INDEX.md im Root immer an erster Stelle
    usort($docs, static function (array $a, array $b): int {
        $aIsIndex = $a['dir'] === '' && strtolower($a['name']) === 'index.md';
        $bIsIndex = $b['dir'] === '' && strtolower($b['name']) === 'index.md';
        if ($aIsIndex) return -1;
        if ($bIsIndex) return  1;
        $dirCmp = strcmp($a['dir'], $b['dir']);
        return $dirCmp !== 0 ? $dirCmp : strcmp($a['name'], $b['name']);
    });

    return $docs;
}

// ─── Dateiinhalt via GitHub Contents-API ────────────────────────────────────────

/**
 * Lädt den Inhalt einer .md-Datei über die GitHub Contents-API (api.github.com).
 * Nutzt bewusst dieselbe Domain wie fetchDocList(), da raw.githubusercontent.com
 * auf manchen Servern geblockt sein kann.
 *
 * @param string $repoPath  Vollständiger Repo-Pfad, z. B. "DOC/admin/README.md"
 */
function fetchDocContent(string $repoPath): ?string
{
    $encoded = implode('/', array_map('rawurlencode', explode('/', $repoPath)));
    $url     = GITHUB_API_CONTENTS . $encoded . '?ref=' . GITHUB_BRANCH;

    $body = supportHttpGet($url, ['Accept: application/vnd.github+json']);
    if ($body === null) {
        return null;
    }

    $data = json_decode($body, true);
    if (!is_array($data) || !isset($data['content'])) {
        $GLOBALS['_support_last_error'] = 'Contents-API: kein "content"-Feld in Antwort';
        return null;
    }
    if (($data['encoding'] ?? '') !== 'base64') {
        $GLOBALS['_support_last_error'] = 'Contents-API: unbekanntes Encoding "' . ($data['encoding'] ?? '?') . '"';
        return null;
    }

    $decoded = base64_decode(str_replace(["\n", "\r"], '', $data['content']));
    return $decoded !== false ? $decoded : null;
}

// ─── PHP-Markdown-Renderer (kein CDN, kein JS) ───────────────────────────────

function inlineMarkdown(string $text): string
{
    $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1" style="max-width:100%;">', $text);
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $text);
    $text = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $text);
    $text = preg_replace('/\*\*(.+?)\*\*/',     '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/',          '<em>$1</em>', $text);
    $text = preg_replace('/~~(.+?)~~/',           '<del>$1</del>', $text);
    return $text;
}

function buildMarkdownTable(array $rows): string
{
    if (count($rows) < 2) return '';
    $header = array_shift($rows);
    array_shift($rows); // Trennzeile entfernen
    $cols = array_map('trim', explode('|', trim($header, '|')));
    $html = '<div class="md-table-wrap"><table class="md-table"><thead><tr>';
    foreach ($cols as $c) {
        $html .= '<th>' . inlineMarkdown(htmlspecialchars($c, ENT_QUOTES)) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($rows as $row) {
        if (trim($row) === '' || preg_match('/^\|?[\s\-:|]+\|/', $row)) continue;
        $cells = array_map('trim', explode('|', trim($row, '|')));
        $html .= '<tr>';
        foreach ($cells as $cell) {
            $html .= '<td>' . inlineMarkdown(htmlspecialchars($cell, ENT_QUOTES)) . '</td>';
        }
        $html .= '</tr>';
    }
    return $html . '</tbody></table></div>';
}

function renderMarkdown(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);

    $codeBlocks = [];
    $text = preg_replace_callback(
        '/^```(\w*)\n(.*?)^```/ms',
        static function (array $m) use (&$codeBlocks): string {
            $lang  = htmlspecialchars($m[1], ENT_QUOTES);
            $code  = htmlspecialchars($m[2], ENT_QUOTES);
            $label = $lang !== '' ? "<span class=\"md-lang\">{$lang}</span>" : '';
            $html  = "<pre class=\"md-pre\">{$label}<code class=\"language-{$lang}\">{$code}</code></pre>";
            $key   = "\x02CODE" . count($codeBlocks) . "\x03";
            $codeBlocks[$key] = $html;
            return $key;
        },
        $text
    );

    $inlineCodes = [];
    $text = preg_replace_callback(
        '/`([^`\n]+)`/',
        static function (array $m) use (&$inlineCodes): string {
            $html = '<code class="md-inline-code">' . htmlspecialchars($m[1], ENT_QUOTES) . '</code>';
            $key  = "\x02IC" . count($inlineCodes) . "\x03";
            $inlineCodes[$key] = $html;
            return $key;
        },
        $text
    );

    $lines  = explode("\n", $text);
    $output = '';
    $i      = 0;
    $total  = count($lines);

    while ($i < $total) {
        $line = $lines[$i];

        if (trim($line) === '') { $output .= "\n"; $i++; continue; }

        if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
            $lvl     = strlen($m[1]);
            $content = inlineMarkdown($m[2]);
            $id      = preg_replace('/[^a-z0-9-]/', '-', strtolower(strip_tags($content)));
            $output .= "<h{$lvl} id=\"{$id}\">{$content}</h{$lvl}>\n";
            $i++; continue;
        }

        if (preg_match('/^[-*_]{3,}\s*$/', trim($line))) {
            $output .= "<hr>\n"; $i++; continue;
        }

        if (str_starts_with($line, '> ')) {
            $bq = [];
            while ($i < $total && str_starts_with($lines[$i], '>')) {
                $bq[] = ltrim($lines[$i], '> '); $i++;
            }
            $output .= '<blockquote class="md-blockquote">' . renderMarkdown(implode("\n", $bq)) . '</blockquote>';
            continue;
        }

        if (str_contains($line, '|') && isset($lines[$i + 1]) && preg_match('/^\|?[\s\-:|]+\|/', $lines[$i + 1])) {
            $rows = [];
            while ($i < $total && str_contains($lines[$i], '|')) {
                $rows[] = $lines[$i++];
            }
            $output .= buildMarkdownTable($rows);
            continue;
        }

        if (preg_match('/^(\s*)([-*+])\s+/', $line)) {
            $output .= "<ul>\n";
            while ($i < $total && preg_match('/^(\s*)([-*+])\s+(.*)/', $lines[$i], $lm)) {
                $output .= '<li>' . inlineMarkdown($lm[3]) . "</li>\n"; $i++;
            }
            $output .= "</ul>\n"; continue;
        }

        if (preg_match('/^\d+\.\s+/', $line)) {
            $output .= "<ol>\n";
            while ($i < $total && preg_match('/^\d+\.\s+(.*)/', $lines[$i], $lm)) {
                $output .= '<li>' . inlineMarkdown($lm[1]) . "</li>\n"; $i++;
            }
            $output .= "</ol>\n"; continue;
        }

        $para = [];
        while ($i < $total && trim($lines[$i]) !== '' &&
               !preg_match('/^#{1,6}\s/', $lines[$i]) &&
               !str_starts_with($lines[$i], '> ') &&
               !preg_match('/^(\s*)([-*+])\s/', $lines[$i]) &&
               !preg_match('/^\d+\.\s/', $lines[$i]) &&
               !preg_match('/^[-*_]{3,}\s*$/', trim($lines[$i])) &&
               !str_contains($lines[$i], "\x02CODE")
        ) {
            $para[] = $lines[$i]; $i++;
        }
        if (!empty($para)) {
            $output .= '<p>' . inlineMarkdown(htmlspecialchars(implode(' ', $para), ENT_QUOTES)) . "</p>\n";
        }
    }

    $output = strtr($output, $codeBlocks);
    $output = strtr($output, $inlineCodes);
    return $output;
}

// ─── Verzeichnis-Labels & Sortierung ─────────────────────────────────────────

$dirLabels = [
    ''                               => ['label' => 'Allgemein',              'icon' => '📄'],
    'admin'                          => ['label' => 'Administration',         'icon' => '🔧'],
    'admin/dashboard'                => ['label' => 'Dashboard',              'icon' => '🏠'],
    'admin/landing-page'             => ['label' => 'Landing Page',           'icon' => '🎯'],
    'admin/pages-posts'              => ['label' => 'Seiten & Beiträge',      'icon' => '📝'],
    'admin/media'                    => ['label' => 'Medien',                 'icon' => '🖼️'],
    'admin/users-groups'             => ['label' => 'Benutzer & Gruppen',     'icon' => '👥'],
    'admin/subscription'             => ['label' => 'Subscriptions',          'icon' => '💳'],
    'admin/themes-design'            => ['label' => 'Themes & Design',        'icon' => '🎨'],
    'admin/seo-performance'          => ['label' => 'SEO & Performance',      'icon' => '📈'],
    'admin/seo-performance/analytics'=> ['label' => 'Analytics',              'icon' => '📊'],
    'admin/legal-security'           => ['label' => 'Recht & Sicherheit',     'icon' => '⚖️'],
    'admin/plugins'                  => ['label' => 'Plugins',                'icon' => '🔌'],
    'admin/system-settings'          => ['label' => 'System & Einstellungen', 'icon' => '⚙️'],
    'member'                         => ['label' => 'Mitglieder',             'icon' => '👤'],
    'member/general'                 => ['label' => 'Mitglieder Allgemein',   'icon' => '👤'],
    'plugins'                        => ['label' => 'Plugin-Entwicklung',     'icon' => '🔌'],
    'theme'                          => ['label' => 'Theme-Entwicklung',      'icon' => '🎨'],
    'feature'                        => ['label' => 'Feature-Guides',         'icon' => '✨'],
    'workflow'                       => ['label' => 'Workflows',              'icon' => '🔄'],
    'audits'                         => ['label' => 'Audits',                 'icon' => '🔍'],
    'screenshots'                    => ['label' => 'Screenshots',            'icon' => '📷'],
];

$knownOrder = [
    '', 'admin', 'admin/dashboard', 'admin/landing-page', 'admin/pages-posts',
    'admin/media', 'admin/users-groups', 'admin/subscription',
    'admin/themes-design', 'admin/seo-performance', 'admin/seo-performance/analytics',
    'admin/legal-security', 'admin/plugins', 'admin/system-settings',
    'member', 'member/general', 'plugins', 'theme', 'feature', 'workflow', 'audits', 'screenshots',
];

// ─── Verarbeitung ─────────────────────────────────────────────────────────────

$GLOBALS['_support_last_error'] = '';
$debugMode  = (($_GET['debug'] ?? '') === '1');
$docList    = fetchDocList();
$activeDoc  = $_GET['doc'] ?? '';
$docContent = null;
$docTitle   = '';

// Sicherheitsprüfung: nur DOC/-relative Pfade, kein Path-Traversal
if ($activeDoc !== '') {
    $clean    = str_replace(['..', '\\', "\0"], '', $activeDoc);
    $clean    = trim($clean, '/');
    $safePath = GITHUB_DOC_PATH . '/' . $clean;

    if (
        str_starts_with($safePath, GITHUB_DOC_PATH . '/') &&
        str_ends_with(strtolower($safePath), '.md') &&
        !str_contains($safePath, '//')
    ) {
        $docContent = fetchDocContent($safePath);
        $docTitle   = str_replace(['-', '_'], ' ', pathinfo(basename($safePath), PATHINFO_FILENAME));
        $activeDoc  = $clean;
    } else {
        $activeDoc = '';
    }
}

// Kein Dokument gewählt → INDEX.md bevorzugen (steht nach usort an Position 0)
if ($docContent === null && count($docList) > 0) {
    $firstDoc   = $docList[0];
    $docContent = fetchDocContent($firstDoc['path']);
    $docTitle   = str_replace(['-', '_'], ' ', pathinfo($firstDoc['name'], PATHINFO_FILENAME));
    $activeDoc  = substr($firstDoc['path'], strlen(GITHUB_DOC_PATH) + 1);
}

// Dokumente nach Verzeichnis gruppieren
$groups = [];
foreach ($docList as $doc) {
    $groups[$doc['dir']][] = $doc;
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
    <?php renderAdminSidebarStyles(); ?>
    <style>
        /* ── Layout ─────────────────────────────────────────────────────── */
        .support-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 1.5rem;
            align-items: start;
        }
        @media (max-width: 900px) {
            .support-layout { grid-template-columns: 1fr; }
            .docs-sidebar   { display: none; }
        }

        /* ── Sidebar ─────────────────────────────────────────────────────── */
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
            padding: .75rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            font-size: .85rem;
            color: #374151;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .docs-sidebar-empty {
            padding: 1.5rem 1rem;
            color: #94a3b8;
            font-size: .85rem;
            text-align: center;
        }
        .docs-group {
            border-bottom: 1px solid #f1f5f9;
        }
        .docs-group:last-child { border-bottom: none; }
        .docs-group > summary {
            display: flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1rem;
            cursor: pointer;
            font-size: .8rem;
            font-weight: 600;
            color: #475569;
            background: #f8fafc;
            list-style: none;
            user-select: none;
        }
        .docs-group > summary::-webkit-details-marker { display: none; }
        .docs-group > summary::before {
            content: '▶';
            font-size: .6rem;
            color: #94a3b8;
            transition: transform .2s;
            flex-shrink: 0;
        }
        .docs-group[open] > summary::before { transform: rotate(90deg); }
        .docs-group > summary:hover { background: #f1f5f9; color: #1e293b; }
        .docs-nav-item {
            display: block;
            padding: .4rem 1rem .4rem 2.25rem;
            font-size: .82rem;
            color: #64748b;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: background .15s, color .15s;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .docs-nav-item:hover { background: #f1f5f9; color: #1e293b; }
        .docs-nav-item.active {
            background: #eff6ff;
            color: #2563eb;
            border-left-color: #2563eb;
            font-weight: 600;
        }
        .docs-root-item {
            display: block;
            padding: .45rem 1rem;
            font-size: .82rem;
            color: #64748b;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: background .15s, color .15s;
        }
        .docs-root-item:hover { background: #f1f5f9; color: #1e293b; }
        .docs-root-item.active {
            background: #eff6ff;
            color: #2563eb;
            border-left-color: #2563eb;
            font-weight: 600;
        }

        /* ── Content ─────────────────────────────────────────────────────── */
        .docs-content {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 2rem 2.5rem;
            min-height: 400px;
        }
        .docs-content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
            gap: 1rem;
        }
        .docs-content-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e293b;
            text-transform: capitalize;
            margin: 0;
        }
        .docs-source-badge {
            font-size: .72rem;
            color: #64748b;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: .2rem .7rem;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .docs-empty {
            text-align: center;
            padding: 4rem 2rem;
            color: #94a3b8;
        }
        .docs-empty-icon { font-size: 3rem; margin-bottom: 1rem; }

        /* ── Markdown Styles ─────────────────────────────────────────────── */
        .md-body { color: #1e293b; line-height: 1.75; font-size: .93rem; }
        .md-body h1, .md-body h2, .md-body h3,
        .md-body h4, .md-body h5, .md-body h6 {
            margin: 1.5rem 0 .5rem;
            color: #0f172a;
            line-height: 1.3;
        }
        .md-body h1 { font-size: 1.8rem; border-bottom: 2px solid #e2e8f0; padding-bottom: .4rem; }
        .md-body h2 { font-size: 1.4rem; border-bottom: 1px solid #f1f5f9; padding-bottom: .3rem; }
        .md-body h3 { font-size: 1.15rem; }
        .md-body p  { margin: .75rem 0; }
        .md-body ul, .md-body ol { margin: .75rem 0; padding-left: 1.75rem; }
        .md-body li { margin: .25rem 0; }
        .md-body a  { color: #2563eb; text-decoration: none; }
        .md-body a:hover { text-decoration: underline; }
        .md-body hr { border: none; border-top: 1px solid #e2e8f0; margin: 1.5rem 0; }
        .md-body blockquote.md-blockquote {
            margin: 1rem 0;
            padding: .75rem 1rem;
            border-left: 4px solid #3b82f6;
            background: #eff6ff;
            border-radius: 0 6px 6px 0;
            color: #1e40af;
        }
        .md-body pre.md-pre {
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 1.25rem 1.5rem;
            overflow-x: auto;
            margin: 1rem 0;
            font-size: .82rem;
            line-height: 1.6;
            position: relative;
        }
        .md-body .md-lang {
            position: absolute;
            top: .5rem;
            right: .75rem;
            font-size: .65rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .md-body code.md-inline-code {
            background: #f1f5f9;
            color: #e11d48;
            padding: .1em .35em;
            border-radius: 4px;
            font-size: .85em;
        }
        .md-body .md-table-wrap { overflow-x: auto; margin: 1rem 0; }
        .md-body table.md-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .87rem;
        }
        .md-body table.md-table th {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: .5rem .75rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
        }
        .md-body table.md-table td {
            border: 1px solid #e2e8f0;
            padding: .45rem .75rem;
            color: #4b5563;
        }
        .md-body table.md-table tr:nth-child(even) td { background: #f9fafb; }

        /* ── Debug Panel ─────────────────────────────────────────────────── */
        .debug-panel {
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            font-family: monospace;
            font-size: .8rem;
            line-height: 1.8;
        }
        .debug-panel-title {
            color: #f59e0b;
            font-weight: 700;
            margin-bottom: .75rem;
            font-size: .9rem;
        }
        .debug-panel table { width: 100%; border-collapse: collapse; }
        .debug-panel td:first-child {
            color: #94a3b8;
            padding: .2rem .75rem .2rem 0;
            white-space: nowrap;
            width: 220px;
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

        <?php if ($debugMode): ?>
        <!-- ── Debug-Panel (?debug=1) ─────────────────────────────────── -->
        <div class="debug-panel">
            <div class="debug-panel-title">🔧 Server-Diagnose (debug=1)</div>
            <table>
                <tr>
                    <td>PHP Version</td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td>cURL verfügbar</td>
                    <td><?php echo function_exists('curl_init')
                        ? '<span style="color:#4ade80">✓ Ja (' . (curl_version()['version'] ?? '?') . ')</span>'
                        : '<span style="color:#f87171">✗ Nein</span>'; ?>
                    </td>
                </tr>
                <tr>
                    <td>allow_url_fopen</td>
                    <td><?php echo ini_get('allow_url_fopen')
                        ? '<span style="color:#4ade80">✓ On</span>'
                        : '<span style="color:#f87171">✗ Off</span>'; ?>
                    </td>
                </tr>
                <tr>
                    <td>GitHub API Tree</td>
                    <td><span style="color:#94a3b8"><?php echo htmlspecialchars(GITHUB_API_TREE); ?></span></td>
                </tr>
                <tr>
                    <td>GitHub Contents-API</td>
                    <td><span style="color:#94a3b8"><?php echo htmlspecialchars(GITHUB_API_CONTENTS); ?></span></td>
                </tr>
                <tr>
                    <td>Docs geladen</td>
                    <td><?php echo count($docList); ?> Dokumente</td>
                </tr>
                <?php $lastErr = $GLOBALS['_support_last_error'] ?? ''; ?>
                <tr>
                    <td>Letzter Fehler</td>
                    <td><?php echo $lastErr !== ''
                        ? '<span style="color:#fca5a5">' . htmlspecialchars($lastErr) . '</span>'
                        : '<span style="color:#4ade80">–</span>'; ?>
                    </td>
                </tr>
            </table>
        </div>
        <?php endif; ?>

        <!-- Main Layout -->
        <div class="support-layout">

            <!-- ── Sidebar ────────────────────────────────────────────────── -->
            <aside class="docs-sidebar">
                <div class="docs-sidebar-header">
                    <span>📄 Dokumente</span>
                    <span style="font-weight:400;color:#94a3b8;"><?php echo count($docList); ?></span>
                </div>

                <?php if (count($docList) === 0): ?>
                    <div class="docs-sidebar-empty">
                        Keine Dokumente gefunden.<br>
                        <small style="color:#cbd5e1;">
                            <?php echo htmlspecialchars(GITHUB_OWNER . '/' . GITHUB_REPO . '/' . GITHUB_DOC_PATH); ?>
                        </small>
                    </div>
                <?php else: ?>
                    <?php
                    // Gruppen nach $knownOrder sortiert ausgeben; unbekannte ans Ende
                    $allDirs    = array_keys($groups);
                    $sortedDirs = array_merge(
                        array_filter($knownOrder, fn($d) => isset($groups[$d])),
                        array_diff($allDirs, $knownOrder)
                    );

                    foreach ($sortedDirs as $dir):
                        $dirDocs = $groups[$dir] ?? [];
                        if (empty($dirDocs)) continue;
                        $label   = $dirLabels[$dir]['label'] ?? ucfirst(basename((string)$dir));
                        $icon    = $dirLabels[$dir]['icon']  ?? '📁';

                        if ($dir === ''):
                            // Root-Dateien direkt (keine <details>)
                            foreach ($dirDocs as $doc):
                                $relPath  = substr($doc['path'], strlen(GITHUB_DOC_PATH) + 1);
                                $isActive = ($activeDoc === $relPath);
                    ?>
                        <a href="?doc=<?php echo rawurlencode($relPath); ?>"
                           class="docs-root-item<?php echo $isActive ? ' active' : ''; ?>">
                            <?php echo htmlspecialchars($doc['name']); ?>
                        </a>
                    <?php
                            endforeach;
                        else:
                            // Prüfen ob ein Doc der Gruppe aktiv ist
                            $groupIsActive = false;
                            foreach ($dirDocs as $doc) {
                                if ($activeDoc === substr($doc['path'], strlen(GITHUB_DOC_PATH) + 1)) {
                                    $groupIsActive = true;
                                    break;
                                }
                            }
                    ?>
                        <details class="docs-group"<?php echo $groupIsActive ? ' open' : ''; ?>>
                            <summary><?php echo $icon . ' ' . htmlspecialchars($label); ?></summary>
                            <?php foreach ($dirDocs as $doc):
                                $relPath  = substr($doc['path'], strlen(GITHUB_DOC_PATH) + 1);
                                $isActive = ($activeDoc === $relPath);
                            ?>
                                <a href="?doc=<?php echo rawurlencode($relPath); ?>"
                                   class="docs-nav-item<?php echo $isActive ? ' active' : ''; ?>">
                                    <?php echo htmlspecialchars($doc['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </details>
                    <?php endif; endforeach; ?>
                <?php endif; ?>
            </aside>

            <!-- ── Dokument-Inhalt ────────────────────────────────────────── -->
            <main class="docs-content">
                <?php if ($docContent !== null): ?>
                    <div class="docs-content-header">
                        <h1 class="docs-content-title"><?php echo htmlspecialchars($docTitle); ?></h1>
                        <span class="docs-source-badge">
                            🌐 <?php echo htmlspecialchars(GITHUB_OWNER . '/' . GITHUB_REPO); ?>
                        </span>
                    </div>
                    <div class="md-body">
                        <?php echo renderMarkdown($docContent); ?>
                    </div>
                <?php else: ?>
                    <div class="docs-empty">
                        <div class="docs-empty-icon">📭</div>
                        <p>Kein Dokument verfügbar.</p>
                        <p style="font-size:.85rem;color:#cbd5e1;">
                            Repository: <code><?php echo htmlspecialchars(GITHUB_OWNER . '/' . GITHUB_REPO); ?></code>
                            &nbsp;/&nbsp;
                            Pfad: <code><?php echo htmlspecialchars(GITHUB_DOC_PATH . '/'); ?></code>
                        </p>
                        <?php $lastErr = $GLOBALS['_support_last_error'] ?? ''; ?>
                        <?php if ($lastErr !== ''): ?>
                            <p style="font-size:.8rem;color:#f87171;margin-top:1rem;">
                                <?php echo htmlspecialchars($lastErr); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </main>

        </div><!-- /.support-layout -->

    </div><!-- /.admin-content -->

</body>
</html>
