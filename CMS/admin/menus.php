<?php
/**
 * Admin: Menü-Verwaltung
 *
 * Verwaltet alle Navigationsmenüs des aktiven Themes.
 * Unterstützt Untermenüs (children) auf einer Ebene.
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\ThemeManager;
use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$security     = Security::instance();
$themeManager = ThemeManager::instance();
$db           = Database::instance();

$message     = '';
$messageType = '';

// ─── Hilfsfunktion: Menü-Items rekursiv sanitieren ───────────────────────────

function sanitizeMenuItems(array $raw): array
{
    $items = [];
    foreach ($raw as $item) {
        if (!is_array($item)) {
            continue;
        }
        $label = trim(Security::sanitize($item['label'] ?? '', 'text'));
        $url   = trim($item['url'] ?? '');
        if ($label === '' || $url === '') {
            continue;
        }
        $entry = [
            'label'    => $label,
            'url'      => $url,
            'target'   => ($item['target'] ?? '') === '_blank' ? '_blank' : '_self',
            'children' => [],
        ];
        if (!empty($item['children']) && is_array($item['children'])) {
            foreach ($item['children'] as $child) {
                if (!is_array($child)) {
                    continue;
                }
                $cLabel = trim(Security::sanitize($child['label'] ?? '', 'text'));
                $cUrl   = trim($child['url'] ?? '');
                if ($cLabel !== '' && $cUrl !== '') {
                    $entry['children'][] = [
                        'label'  => $cLabel,
                        'url'    => $cUrl,
                        'target' => ($child['target'] ?? '') === '_blank' ? '_blank' : '_self',
                    ];
                }
            }
        }
        $items[] = $entry;
    }
    return $items;
}

// ─── POST Handler ────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {

    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'admin_menus')) {
        $message     = 'Sicherheitsüberprüfung fehlgeschlagen.';
        $messageType = 'error';
    } else {

        $action         = $_POST['action'];
        $postedLocation = trim($_POST['location'] ?? 'primary');

        // ── Menü speichern ─────────────────────────────────────────────────
        if ($action === 'save_menu') {
            $rawJson = $_POST['menu_json'] ?? '[]';
            $decoded = json_decode($rawJson, true);

            if (!is_array($decoded)) {
                $message     = 'Ungültige Menüdaten.';
                $messageType = 'error';
            } else {
                $items = sanitizeMenuItems($decoded);
                if ($themeManager->saveMenu($postedLocation, $items)) {
                    $message     = 'Menü wurde gespeichert.';
                    $messageType = 'success';
                } else {
                    $message     = 'Fehler beim Speichern.';
                    $messageType = 'error';
                }
            }

        // ── Neue Menüposition anlegen ───────────────────────────────────────
        } elseif ($action === 'add_location') {
            $newSlug  = preg_replace('/[^a-z0-9_-]/', '-', strtolower(trim($_POST['new_slug']  ?? '')));
            $newLabel = trim(Security::sanitize($_POST['new_label'] ?? '', 'text'));

            if ($newSlug === '' || $newLabel === '') {
                $message     = 'Bitte Slug und Bezeichnung eingeben.';
                $messageType = 'error';
            } else {
                $stmt = $db->prepare("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'menu_custom_locations' LIMIT 1");
                $stmt->execute();
                $res    = $stmt->fetch();
                $custom = $res ? (json_decode($res->option_value, true) ?: []) : [];

                if (in_array($newSlug, array_column($custom, 'slug'), true)) {
                    $message     = 'Slug "' . htmlspecialchars($newSlug) . '" wird bereits verwendet.';
                    $messageType = 'error';
                } else {
                    $custom[] = ['slug' => $newSlug, 'label' => $newLabel];
                    $themeManager->saveCustomMenuLocations($custom);
                    $message     = 'Position "' . htmlspecialchars($newLabel) . '" angelegt.';
                    $messageType = 'success';
                    $postedLocation = $newSlug;
                }
            }

        // ── Menüposition löschen ────────────────────────────────────────────
        } elseif ($action === 'delete_location') {
            $delSlug = trim($_POST['delete_slug'] ?? '');
            if ($delSlug) {
                $stmt = $db->prepare("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'menu_custom_locations' LIMIT 1");
                $stmt->execute();
                $res    = $stmt->fetch();
                $custom = $res ? (json_decode($res->option_value, true) ?: []) : [];
                $custom = array_values(array_filter($custom, static fn($l) => $l['slug'] !== $delSlug));
                $themeManager->saveCustomMenuLocations($custom);
                $message     = 'Menüposition wurde gelöscht.';
                $messageType = 'success';
                $postedLocation = 'primary';
            }
        }

        // Redirect – verhindert Formular-Resubmission
        $redirect = SITE_URL . '/admin/menus?location=' . urlencode($postedLocation);
        if ($messageType) {
            $redirect .= '&msg=' . urlencode($message) . '&type=' . $messageType;
        }
        header('Location: ' . $redirect);
        exit;
    }
}

// ─── GET-Nachrichten aus Redirect ────────────────────────────────────────────

if (isset($_GET['msg'])) {
    $message     = htmlspecialchars(urldecode($_GET['msg']), ENT_QUOTES, 'UTF-8');
    $messageType = ($_GET['type'] ?? '') === 'success' ? 'success' : 'error';
}

// ─── Daten laden ─────────────────────────────────────────────────────────────

$locations = $themeManager->getMenuLocations();
if (empty($locations)) {
    $locations = [['slug' => 'primary', 'label' => 'Hauptmenü']];
}

$currentLocation = trim($_GET['location'] ?? 'primary');
$validSlugs      = array_column($locations, 'slug');
if (!in_array($currentLocation, $validSlugs, true)) {
    $currentLocation = $validSlugs[0] ?? 'primary';
}

$currentItems = $themeManager->getMenu($currentLocation);

// Custom Locations
$stmt = $db->prepare("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'menu_custom_locations' LIMIT 1");
$stmt->execute();
$result      = $stmt->fetch();
$customSlugs = array_column($result ? (json_decode($result->option_value, true) ?: []) : [], 'slug');

$isCurrentCustom = in_array($currentLocation, $customSlugs, true);

$locationLabel = '';
foreach ($locations as $loc) {
    if (!is_array($loc) || !isset($loc['slug'])) {
        continue; // Defensive: ungültige Einträge überspringen
    }
    if ($loc['slug'] === $currentLocation) {
        $locationLabel = $loc['label'];
        break;
    }
}

// PHP → JSON für den JS-Editor (HTML-Sonderzeichen escapen)
$menuJsonForJs = json_encode($currentItems, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

$csrfToken = $security->generateToken('admin_menus');

require_once __DIR__ . '/partials/admin-menu.php';
renderAdminLayoutStart('Menü-Verwaltung', 'menus');
?>

        <!-- Page Header -->
                <div class="page-header d-print-none mb-3">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="page-pretitle">Navigationsmenüs des aktiven Themes bearbeiten – inkl. Untermenüs</div>
                    <h2 class="page-title">🗂️ Menü-Verwaltung</h2>
                </div>
            </div>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Positions-Tabs -->
        <div class="card" style="padding:0;overflow:hidden;">
            <div style="display:flex;align-items:stretch;flex-wrap:wrap;border-bottom:1px solid #e2e8f0;background:#f8fafc;">
                <?php foreach ($locations as $loc):
                    if (!is_array($loc) || !isset($loc['slug'], $loc['label'])) continue;
                    $tabActive = $loc['slug'] === $currentLocation;
                    $tabCustom = in_array($loc['slug'], $customSlugs, true);
                    $tabCnt    = count($themeManager->getMenu($loc['slug']));
                ?>
                <a href="<?php echo SITE_URL; ?>/admin/menus?location=<?php echo urlencode($loc['slug']); ?>"
                   style="display:flex;align-items:center;gap:.45rem;padding:.7rem 1.15rem;font-size:.875rem;font-weight:600;
                          color:<?php echo $tabActive ? 'var(--admin-primary)' : '#475569'; ?>;
                          border-bottom:2px solid <?php echo $tabActive ? 'var(--admin-primary)' : 'transparent'; ?>;
                          text-decoration:none;white-space:nowrap;transition:color .15s,border-color .15s;">
                    <?php echo htmlspecialchars($loc['label']); ?>
                    <span style="font-size:.72rem;font-weight:500;padding:.1rem .45rem;border-radius:999px;
                                 background:<?php echo $tabActive ? '#dbeafe' : '#f1f5f9'; ?>;
                                 color:<?php echo $tabActive ? '#1e40af' : '#64748b'; ?>;"><?php echo $tabCnt; ?></span>
                    <?php if ($tabCustom): ?>
                        <span style="font-size:.65rem;font-weight:700;padding:.1rem .35rem;border-radius:4px;
                                     background:#fef3c7;color:#92400e;letter-spacing:.02em;">EIGENE</span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
                <button type="button" onclick="document.getElementById('addLocPanel').classList.toggle('hidden')"
                        style="margin-left:auto;display:flex;align-items:center;gap:.3rem;padding:.7rem 1rem;
                               font-size:.8rem;font-weight:600;color:var(--admin-primary);background:none;
                               border:none;cursor:pointer;white-space:nowrap;">
                    ➕ Position anlegen
                </button>
            </div>

            <!-- Neue Position Panel -->
            <div id="addLocPanel" class="hidden"
                 style="padding:1.1rem 1.25rem;background:#fffbeb;border-bottom:1px solid #fde68a;">
                <form method="POST" action="<?php echo SITE_URL; ?>/admin/menus"
                      style="display:flex;align-items:flex-end;gap:.65rem;flex-wrap:wrap;">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action"     value="add_location">
                    <div>
                        <label class="form-label" style="font-size:.8rem;">Slug</label>
                        <input type="text" name="new_slug" class="form-control"
                               placeholder="z.B. sidebar" style="width:155px;"
                               pattern="[a-zA-Z0-9_-]+" title="Nur a–z, 0–9, - und _" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem;">Bezeichnung</label>
                        <input type="text" name="new_label" class="form-control"
                               placeholder="z.B. Sidebar-Menü" style="width:200px;" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Anlegen</button>
                    <button type="button" class="btn btn-secondary btn-sm"
                            onclick="document.getElementById('addLocPanel').classList.add('hidden')">Abbrechen</button>
                </form>
                <p style="margin:.4rem 0 0;font-size:.75rem;color:#92400e;">
                    Im Theme via <code>theme_nav_menu('slug')</code> einbinden.
                </p>
            </div>
        </div>

        <!-- Editor -->
        <div class="card">
            <!-- Card-Header -->
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;
                        border-bottom:1px solid #f1f5f9;padding-bottom:1rem;margin-bottom:1.25rem;">
                <div>
                    <h3 style="margin:0;">✏️ <?php echo htmlspecialchars($locationLabel); ?></h3>
                    <span style="font-size:.8rem;color:#64748b;">
                        Position: <code><?php echo htmlspecialchars($currentLocation); ?></code>
                        &nbsp;·&nbsp; Template: <code>theme_nav_menu('<?php echo htmlspecialchars($currentLocation); ?>')</code>
                    </span>
                </div>
                <?php if ($isCurrentCustom): ?>
                <form method="POST" action="<?php echo SITE_URL; ?>/admin/menus"
                      onsubmit="return confirm('Position und alle Menüeinträge wirklich löschen?');">
                    <input type="hidden" name="csrf_token"  value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action"      value="delete_location">
                    <input type="hidden" name="delete_slug" value="<?php echo htmlspecialchars($currentLocation); ?>">
                    <button type="submit" class="btn btn-danger btn-sm">🗑️ Position löschen</button>
                </form>
                <?php endif; ?>
            </div>

            <!-- Spaltenköpfe -->
            <div id="menuColHeads"
                 style="display:grid;grid-template-columns:26px 1fr 1fr 115px 32px 72px 34px;
                        gap:.4rem;padding:.3rem .4rem;font-size:.73rem;font-weight:700;
                        color:#64748b;letter-spacing:.03em;text-transform:uppercase;
                        border-bottom:1px solid #e2e8f0;margin-bottom:.4rem;">
                <span></span><span>Bezeichnung</span><span>URL / Pfad</span>
                <span>Link-Ziel</span><span></span><span style="text-align:center;">Reihenf.</span><span></span>
            </div>

            <!-- Eintragscontainer (JS-rendered) -->
            <div id="menuList"></div>
            <div id="menuEmpty" style="display:none;text-align:center;padding:2.25rem;
                 color:#94a3b8;font-style:italic;border:2px dashed #e2e8f0;border-radius:8px;margin-bottom:1rem;">
                📭 Noch keine Einträge – füge unten einen ersten Punkt hinzu.
            </div>

            <!-- Neuer Hauptpunkt -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;
                        padding:.9rem 1rem;margin-top:.75rem;">
                <div style="font-size:.78rem;font-weight:700;color:#475569;margin-bottom:.6rem;">
                    ➕ Neuen Hauptpunkt hinzufügen
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 115px auto;gap:.45rem;">
                    <input type="text" id="newLabel"  class="form-control" placeholder="Bezeichnung">
                    <input type="text" id="newUrl"    class="form-control" placeholder="/pfad oder https://…">
                    <select           id="newTarget"  class="form-select">
                        <option value="_self">Gleicher Tab</option>
                        <option value="_blank">Neuer Tab</option>
                    </select>
                    <button type="button" class="btn btn-secondary" onclick="addTopItem()">Hinzufügen</button>
                </div>
            </div>

            <!-- Speichern-Leiste -->
            <form id="menuForm" method="POST" action="<?php echo SITE_URL; ?>/admin/menus">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action"     value="save_menu">
                <input type="hidden" name="location"   value="<?php echo htmlspecialchars($currentLocation); ?>">
                <input type="hidden" name="menu_json"  id="menuJsonInput" value="">
                <div style="display:flex;align-items:center;justify-content:flex-end;gap:1rem;
                            margin-top:1.25rem;padding-top:1rem;border-top:1px solid #f1f5f9;">
                    <span id="itemCount" style="font-size:.8rem;color:#64748b;"></span>
                    <button type="submit" class="btn btn-primary" onclick="return prepareSubmit()">
                        💾 Menü speichern
                    </button>
                </div>
            </form>
        </div>

    </div><!-- /.admin-content -->

<style>
/* ═══ Menü-Editor ══════════════════════════════════════════════════ */
.mrow {
    display: grid;
    grid-template-columns: 26px 1fr 1fr 115px 32px 72px 34px;
    gap: .4rem;
    align-items: center;
    padding: .45rem .4rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    margin-bottom: .35rem;
}
.mrow:hover { box-shadow: 0 2px 6px rgba(0,0,0,.06); }
.mrow input, .mrow select, .mcrow input, .mcrow select { width: 100%; min-width: 0; }
.mchild-wrap {
    margin: 0 0 .35rem 2rem;
    padding: .65rem .75rem .5rem;
    background: #f8fafc;
    border: 1px solid #e8edf3;
    border-radius: 7px;
}
.mcrow {
    display: grid;
    grid-template-columns: 26px 1fr 1fr 115px 32px 72px 34px;
    gap: .4rem;
    align-items: center;
    padding: .35rem .4rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
    margin-bottom: .3rem;
}
.dh  { color:#94a3b8;font-size:1rem;text-align:center;cursor:default;user-select:none; }
.sbtn {
    background: none;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    padding: 1px 4px;
    cursor: pointer;
    font-size: .78rem;
    color: #475569;
}
.sbtn:disabled { opacity:.3;cursor:default; }
.sbtn:not(:disabled):hover { background:#f1f5f9; }
.dbtn {
    background: none;
    border: 1px solid #fecaca;
    border-radius: 6px;
    color: #ef4444;
    padding: .2rem .4rem;
    cursor: pointer;
    font-size: .8rem;
    line-height: 1;
}
.dbtn:hover { background:#fee2e2; }
.acbtn {
    font-size: .72rem;
    font-weight: 700;
    color: var(--admin-primary);
    background: none;
    border: 1px solid #bfdbfe;
    border-radius: 5px;
    padding: .15rem .45rem;
    cursor: pointer;
    white-space: nowrap;
}
.acbtn:hover { background:#eff6ff; }
.hidden { display: none !important; }
#menuColHeads, .mrow, .mcrow {
    font-size: .875rem;
}
@media (max-width:760px) {
    .mrow, .mcrow { grid-template-columns: 26px 1fr 1fr 80px 32px 60px 30px; }
    #menuColHeads  { grid-template-columns: 26px 1fr 1fr 80px 32px 60px 30px; }
}
@media (max-width:520px) {
    .mrow, .mcrow  { grid-template-columns: 1fr 1fr; gap:.3rem; }
    #menuColHeads  { display:none; }
    .mrow > :nth-child(1), .mrow > :nth-child(5) { display:none; }
    .mcrow > :nth-child(1), .mcrow > :nth-child(5) { display:none; }
}
</style>

<script>
// ─── State ───────────────────────────────────────────────────────────────────
let menuItems = <?php echo $menuJsonForJs ?: '[]'; ?>;
menuItems = (menuItems || []).map(norm);

function norm(it) {
    return {
        label:    it.label    || '',
        url:      it.url      || '',
        target:   it.target   || '_self',
        children: (it.children || []).map(c => ({
            label:  c.label  || '',
            url:    c.url    || '',
            target: c.target || '_self',
        })),
    };
}

// ─── Render ──────────────────────────────────────────────────────────────────
function render() {
    const list  = document.getElementById('menuList');
    const empty = document.getElementById('menuEmpty');
    const count = document.getElementById('itemCount');
    list.innerHTML = '';

    if (!menuItems.length) {
        empty.style.display = 'block';
        count.textContent   = '';
        return;
    }
    empty.style.display = 'none';

    menuItems.forEach((item, i) => {

        // Haupt-Zeile
        const row = el('div', 'mrow');
        row.innerHTML = `
            <span class="dh">⠿</span>
            <input class="form-control" value="${esc(item.label)}" placeholder="Bezeichnung"
                   oninput="menuItems[${i}].label=this.value">
            <input class="form-control" value="${esc(item.url)}" placeholder="/pfad oder https://…"
                   oninput="menuItems[${i}].url=this.value">
            <select class="form-select" onchange="menuItems[${i}].target=this.value">
                <option value="_self"  ${item.target!=='_blank'?'selected':''}>Gleicher Tab</option>
                <option value="_blank" ${item.target==='_blank'?'selected':''}>Neuer Tab</option>
            </select>
            <button type="button" class="acbtn" title="Untermenü-Punkt hinzufügen"
                    onclick="focusChildInput(${i})">↳+</button>
            <span style="display:flex;gap:.2rem;justify-content:center;">
                <button type="button" class="sbtn" onclick="mvTop(${i},-1)" ${i===0?'disabled':''}>↑</button>
                <button type="button" class="sbtn" onclick="mvTop(${i},1)"  ${i===menuItems.length-1?'disabled':''}>↓</button>
            </span>
            <button type="button" class="dbtn" onclick="rmTop(${i})">✕</button>
        `;
        list.appendChild(row);

        // Kinder-Bereich
        const cWrap = el('div', 'mchild-wrap');

        if (item.children.length) {
            const cHead = el('div');
            cHead.style.cssText = 'font-size:.7rem;font-weight:800;color:#64748b;letter-spacing:.05em;text-transform:uppercase;margin-bottom:.4rem;';
            cHead.textContent = 'Untermenüpunkte';
            cWrap.appendChild(cHead);

            item.children.forEach((child, ci) => {
                const crow = el('div', 'mcrow');
                crow.innerHTML = `
                    <span class="dh" style="color:#cbd5e1;">↳</span>
                    <input class="form-control" value="${esc(child.label)}" placeholder="Bezeichnung"
                           oninput="menuItems[${i}].children[${ci}].label=this.value">
                    <input class="form-control" value="${esc(child.url)}" placeholder="/pfad"
                           oninput="menuItems[${i}].children[${ci}].url=this.value">
                    <select class="form-select" onchange="menuItems[${i}].children[${ci}].target=this.value">
                        <option value="_self"  ${child.target!=='_blank'?'selected':''}>Gleicher Tab</option>
                        <option value="_blank" ${child.target==='_blank'?'selected':''}>Neuer Tab</option>
                    </select>
                    <span></span>
                    <span style="display:flex;gap:.2rem;justify-content:center;">
                        <button type="button" class="sbtn" onclick="mvChild(${i},${ci},-1)" ${ci===0?'disabled':''}>↑</button>
                        <button type="button" class="sbtn" onclick="mvChild(${i},${ci},1)"  ${ci===item.children.length-1?'disabled':''}>↓</button>
                    </span>
                    <button type="button" class="dbtn" onclick="rmChild(${i},${ci})">✕</button>
                `;
                cWrap.appendChild(crow);
            });
        }

        // Eingabe neuer Untermenüpunkt
        const addRow = el('div');
        addRow.style.cssText = 'margin-top:' + (item.children.length ? '.5rem' : '0') + ';';
        addRow.innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 1fr 115px auto;gap:.4rem;">
                <input type="text" id="cl_${i}" class="form-control" placeholder="Untermenü-Bezeichnung" style="font-size:.85rem;">
                <input type="text" id="cu_${i}" class="form-control" placeholder="/pfad" style="font-size:.85rem;">
                <select           id="ct_${i}" class="form-select" style="font-size:.85rem;">
                    <option value="_self">Gleicher Tab</option>
                    <option value="_blank">Neuer Tab</option>
                </select>
                <button type="button" class="btn btn-secondary btn-sm" onclick="addChild(${i})">↳ Hinzufügen</button>
            </div>
        `;
        cWrap.appendChild(addRow);
        list.appendChild(cWrap);

        // Enter-Listener verzögert setzen (nach DOM-Einfügen)
        requestAnimationFrame(() => {
            ['cl_'+i,'cu_'+i].forEach(id => {
                const input = document.getElementById(id);
                if (input) input.addEventListener('keydown', e => {
                    if (e.key === 'Enter') { e.preventDefault(); addChild(i); }
                });
            });
        });
    });

    const total = menuItems.reduce((s, it) => s + 1 + it.children.length, 0);
    count.textContent = total + (total === 1 ? ' Eintrag' : ' Einträge');
}

function el(tag, cls) {
    const d = document.createElement(tag);
    if (cls) d.className = cls;
    return d;
}

// ─── Top-Level ───────────────────────────────────────────────────────────────
function addTopItem() {
    const lbl = document.getElementById('newLabel').value.trim();
    const url = document.getElementById('newUrl').value.trim();
    const tgt = document.getElementById('newTarget').value;
    if (!lbl || !url) { alert('Bitte Bezeichnung und URL eingeben.'); return; }
    menuItems.push({ label: lbl, url, target: tgt, children: [] });
    document.getElementById('newLabel').value = '';
    document.getElementById('newUrl').value   = '';
    document.getElementById('newTarget').value = '_self';
    render();
    document.getElementById('newLabel').focus();
}

function rmTop(i) { menuItems.splice(i, 1); render(); }

function mvTop(i, d) {
    const j = i + d;
    if (j < 0 || j >= menuItems.length) return;
    [menuItems[i], menuItems[j]] = [menuItems[j], menuItems[i]];
    render();
}

// ─── Untermenü ───────────────────────────────────────────────────────────────
function focusChildInput(i) {
    const inp = document.getElementById('cl_' + i);
    if (inp) inp.focus();
}

function addChild(i) {
    const lbl = (document.getElementById('cl_'+i) || {}).value?.trim();
    const url = (document.getElementById('cu_'+i) || {}).value?.trim();
    const tgt = (document.getElementById('ct_'+i) || {}).value || '_self';
    if (!lbl || !url) { alert('Bitte Bezeichnung und URL für den Untermenüpunkt eingeben.'); return; }
    menuItems[i].children.push({ label: lbl, url, target: tgt });
    render();
}

function rmChild(i, ci) { menuItems[i].children.splice(ci, 1); render(); }

function mvChild(i, ci, d) {
    const ch = menuItems[i].children;
    const j  = ci + d;
    if (j < 0 || j >= ch.length) return;
    [ch[ci], ch[j]] = [ch[j], ch[ci]];
    render();
}

// ─── Submit ───────────────────────────────────────────────────────────────────
function prepareSubmit() {
    document.getElementById('menuJsonInput').value = JSON.stringify(menuItems);
    return true;
}

// ─── Escape ──────────────────────────────────────────────────────────────────
function esc(s) {
    return String(s)
        .replace(/&/g,'&amp;').replace(/"/g,'&quot;')
        .replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Enter im Haupt-Add-Formular
['newLabel','newUrl'].forEach(id => {
    document.getElementById(id)?.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); addTopItem(); }
    });
});

render();
</script>

<?php renderAdminLayoutEnd(); ?>
