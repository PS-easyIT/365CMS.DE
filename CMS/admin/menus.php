<?php
/**
 * Admin: Menü-Verwaltung
 *
 * Verwaltet alle Navigationsmenüs des aktiven Themes:
 * - Header-/Footer-Menü und weitere registrierte Positionen bearbeiten
 * - Unter-Punkte hinzufügen, bearbeiten, löschen, umsortieren
 * - Eigene Menüpositionen anlegen (sofern das Theme eine weitere Position nutzt)
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

// ─── POST Handler ────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {

    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'admin_menus')) {
        $message     = 'Sicherheitsüberprüfung fehlgeschlagen.';
        $messageType = 'error';
    } else {

        $action          = $_POST['action'];
        $postedLocation  = trim($_POST['location'] ?? 'primary');

        // ── Menü speichern ─────────────────────────────────────────────────
        if ($action === 'save_menu') {
            $labels  = $_POST['item_label']  ?? [];
            $urls    = $_POST['item_url']    ?? [];
            $targets = $_POST['item_target'] ?? [];

            $items = [];
            foreach ($labels as $i => $rawLabel) {
                $label = trim(Security::sanitize($rawLabel,          'text'));
                $url   = trim(Security::sanitize($urls[$i] ?? '',    'url'));
                if ($label === '' || $url === '') {
                    continue;
                }
                $items[] = [
                    'label'  => $label,
                    'url'    => $url,
                    'target' => ($targets[$i] ?? '') === '_blank' ? '_blank' : '_self',
                ];
            }

            if ($themeManager->saveMenu($postedLocation, $items)) {
                $message     = 'Menü wurde gespeichert.';
                $messageType = 'success';
            } else {
                $message     = 'Fehler beim Speichern des Menüs.';
                $messageType = 'error';
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

                $existing = array_column($custom, 'slug');
                if (in_array($newSlug, $existing, true)) {
                    $message     = 'Slug "' . htmlspecialchars($newSlug) . '" wird bereits verwendet.';
                    $messageType = 'error';
                } else {
                    $custom[] = ['slug' => $newSlug, 'label' => $newLabel];
                    $themeManager->saveCustomMenuLocations($custom);
                    $message     = 'Menüposition "' . htmlspecialchars($newLabel) . '" wurde angelegt.';
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
            $redirect .= '&message=' . urlencode($message) . '&type=' . $messageType;
        }
        header('Location: ' . $redirect);
        exit;
    }
}

// ─── GET-Nachrichten aus Redirect ────────────────────────────────────────────

if (isset($_GET['message'])) {
    $message     = htmlspecialchars(urldecode($_GET['message']), ENT_QUOTES, 'UTF-8');
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

// Welche Locations sind custom (löschbar)?
$stmt   = $db->prepare("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'menu_custom_locations' LIMIT 1");
$stmt->execute();
$result = $stmt->fetch();
$customSlugs = array_column($result ? (json_decode($result->option_value, true) ?: []) : [], 'slug');

$csrfToken  = $security->generateToken('admin_menus');
$activeTab  = 'menus';
// $allThemes  = $themeManager->getAvailableThemes(); // Nicht mehr benötigt

// Admin-Menü partial laden
require_once __DIR__ . '/partials/admin-menu.php';

// ─── HTML ─────────────────────────────────────────────────────────────────────

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menü-Verwaltung – <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=20260222b">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">

    <?php renderAdminSidebar('menus'); ?>

    <div class="admin-content">

        <!-- Page Header -->
        <div class="admin-page-header">
            <h2>🗂️ Menü-Verwaltung</h2>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <strong>Wie es funktioniert</strong>
            Wähle links eine Menüposition, bearbeite die Einträge und klicke auf „Menü speichern".
            Das Theme bindet die Menüs automatisch an der richtigen Stelle ein.
            Unter jeder Position steht in grün ob sie vom aktiven Theme unterstützt wird.
        </div>

        <!-- Layout -->
        <div class="menu-layout">

            <!-- ── Linke Spalte: Locations ─────────────────────────────── -->
            <div>
                <div class="menu-locations-panel">
                    <div class="menu-locations-header">Menü-Positionen</div>
                    <?php foreach ($locations as $loc): ?>
                        <?php
                        $isActive  = $loc['slug'] === $currentLocation;
                        $isCustom  = in_array($loc['slug'], $customSlugs, true);
                        $badgeText  = $isCustom ? 'Eigene' : 'Theme';
                        $badgeClass = $isCustom ? 'custom-badge' : 'theme-badge';
                        $itemCount  = count($themeManager->getMenu($loc['slug']));
                        ?>
                        <a href="<?php echo SITE_URL; ?>/admin/menus?location=<?php echo urlencode($loc['slug']); ?>"
                           class="menu-location-item <?php echo $isActive ? 'active' : ''; ?>">
                            <span>
                                <?php echo htmlspecialchars($loc['label']); ?>
                                <small style="display:block;font-size:0.75rem;color:#9ca3af;font-weight:400;">
                                    <?php echo $itemCount; ?> Einträge · slug: <?php echo htmlspecialchars($loc['slug']); ?>
                                </small>
                            </span>
                            <span class="location-badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Neue Menüposition anlegen -->
                <div class="add-location-panel">
                    <h4>➕ Neue Position anlegen</h4>
                    <form method="POST" action="<?php echo SITE_URL; ?>/admin/menus">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action"     value="add_location">
                        <div class="add-location-form">
                            <input type="text" name="new_slug"  placeholder="Slug (z.B. sidebar)" required
                                   pattern="[a-zA-Z0-9_-]+" title="Nur Buchstaben, Zahlen, - und _"
                                   style="flex:1;min-width:110px;">
                            <input type="text" name="new_label" placeholder="Bezeichnung" required
                                   style="flex:2;min-width:140px;">
                            <button type="submit" class="btn btn-primary btn-sm">Anlegen</button>
                        </div>
                        <p style="margin:0.5rem 0 0;font-size:0.75rem;color:#9ca3af;">
                            Das Theme muss die Position via <code>register_menu_locations</code>-Hook oder
                            <code>theme_nav_menu('slug')</code> einbinden.
                        </p>
                    </form>
                </div>
            </div>

            <!-- ── Rechte Spalte: Editor ───────────────────────────────── -->
            <div>
                <?php
                $locationLabel = '';
                foreach ($locations as $loc) {
                    if ($loc['slug'] === $currentLocation) {
                        $locationLabel = $loc['label'];
                        break;
                    }
                }
                $isCurrentCustom = in_array($currentLocation, $customSlugs, true);
                ?>
                <div class="menu-editor-panel">
                    <div class="menu-editor-header">
                        <h3>✏️ <?php echo htmlspecialchars($locationLabel); ?></h3>
                        <?php if ($isCurrentCustom): ?>
                            <form method="POST" action="<?php echo SITE_URL; ?>/admin/menus"
                                  onsubmit="return confirm('Position und alle Einträge wirklich löschen?');">
                                <input type="hidden" name="csrf_token"   value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action"       value="delete_location">
                                <input type="hidden" name="delete_slug"  value="<?php echo htmlspecialchars($currentLocation); ?>">
                                <button type="submit" class="btn btn-danger">🗑 Position löschen</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- Bearbeitungs-Formular -->
                    <form method="POST" action="<?php echo SITE_URL; ?>/admin/menus" id="menuForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action"     value="save_menu">
                        <input type="hidden" name="location"   value="<?php echo htmlspecialchars($currentLocation); ?>">

                        <table class="menu-items-table" id="menuItemsTable">
                            <thead>
                                <tr>
                                    <th style="width:32px;"></th><!-- Drag Handle / Reihenfolge -->
                                    <th>Bezeichnung</th>
                                    <th>URL / Pfad</th>
                                    <th style="width:110px;">Ziel</th>
                                    <th style="width:80px;">Reihenfolge</th>
                                    <th style="width:60px;">Löschen</th>
                                </tr>
                            </thead>
                            <tbody id="menuItemsTbody">

                                <?php if (empty($currentItems)): ?>
                                    <tr id="emptyRow">
                                        <td colspan="6" style="text-align:center;padding:2rem;color:#9ca3af;font-style:italic;">
                                            Noch keine Einträge. Füge unten einen neuen Eintrag hinzu.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($currentItems as $idx => $item): ?>
                                        <tr data-idx="<?php echo $idx; ?>">
                                            <td>
                                                <span class="drag-handle" title="Ziehen zum Umsortieren">⠿</span>
                                            </td>
                                            <td>
                                                <input type="text"
                                                       name="item_label[]"
                                                       value="<?php echo htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                       placeholder="Bezeichnung"
                                                       required>
                                            </td>
                                            <td>
                                                <input type="text"
                                                       name="item_url[]"
                                                       value="<?php echo htmlspecialchars($item['url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                       placeholder="/seite oder https://..."
                                                       required>
                                            </td>
                                            <td>
                                                <select name="item_target[]">
                                                    <option value="_self"  <?php echo ($item['target'] ?? '') !== '_blank' ? 'selected' : ''; ?>>Gleicher Tab</option>
                                                    <option value="_blank" <?php echo ($item['target'] ?? '') === '_blank' ? 'selected' : ''; ?>>Neuer Tab</option>
                                                </select>
                                            </td>
                                            <td style="text-align:center;">
                                                <button type="button" class="sort-btn" onclick="moveRow(this, -1)" title="Nach oben"
                                                        <?php echo $idx === 0 ? 'disabled' : ''; ?>>↑</button>
                                                <button type="button" class="sort-btn" onclick="moveRow(this, 1)"  title="Nach unten"
                                                        <?php echo $idx === count($currentItems) - 1 ? 'disabled' : ''; ?>>↓</button>
                                            </td>
                                            <td>
                                                <button type="button" class="btn-delete-item" onclick="deleteRow(this)">✕</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                            </tbody>

                            <!-- Neue Zeile hinzufügen -->
                            <tfoot>
                                <tr class="add-item-row" id="addItemRow">
                                    <td>
                                        <span style="color:#9ca3af;font-size:1.1rem;">＋</span>
                                    </td>
                                    <td>
                                        <input type="text" id="newLabel" placeholder="Bezeichnung (z.B. Startseite)">
                                    </td>
                                    <td>
                                        <input type="text" id="newUrl"   placeholder="/pfad oder https://...">
                                    </td>
                                    <td>
                                        <select id="newTarget">
                                            <option value="_self">Gleicher Tab</option>
                                            <option value="_blank">Neuer Tab</option>
                                        </select>
                                    </td>
                                    <td colspan="2">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="addRow()">
                                            Eintrag hinzufügen
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>

                        <div class="menu-editor-footer">
                            <button type="submit" class="btn btn-primary">
                                💾 Menü speichern
                            </button>
                            <span style="font-size:0.8rem;color:#9ca3af;">
                                Position: <strong><?php echo htmlspecialchars($currentLocation); ?></strong>
                                · Nutzung im Template:
                                <code>theme_nav_menu('<?php echo htmlspecialchars($currentLocation); ?>')</code>
                            </span>
                        </div>

                    </form>
                </div><!-- /.menu-editor-panel -->

                <!-- Hinweis für Theme-Einbindung -->
                <div style="margin-top:1rem;padding:1rem 1.25rem;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;font-size:0.8125rem;color:#92400e;">
                    <strong>💡 Theme-Einbindung</strong> –
                    Um ein weiteres Menü im Theme anzuzeigen, füge in das gewünschte Template ein:<br>
                    <code style="display:block;margin-top:0.5rem;padding:0.5rem;background:rgba(0,0,0,0.05);border-radius:4px;">
                        &lt;?php theme_nav_menu('<?php echo htmlspecialchars($currentLocation); ?>'); ?&gt;
                    </code>
                    Der Hook <code>register_menu_locations</code> meldet die Position dann in dieser Verwaltung als "Theme"-Position.
                </div>

            </div><!-- /.right column -->
        </div><!-- /.menu-layout -->

    </div><!-- /.admin-content -->

    <script>
    // ── Zeile löschen ─────────────────────────────────────────────────────────
    function deleteRow(btn) {
        const row  = btn.closest('tr');
        const tbody = document.getElementById('menuItemsTbody');
        row.remove();
        refreshSortButtons();
        if (tbody.querySelectorAll('tr:not(#emptyRow)').length === 0) {
            tbody.innerHTML = '<tr id="emptyRow"><td colspan="6" style="text-align:center;padding:2rem;color:#9ca3af;font-style:italic;">Noch keine Einträge. Füge unten einen neuen Eintrag hinzu.</td></tr>';
        }
    }

    // ── Zeile verschieben ─────────────────────────────────────────────────────
    function moveRow(btn, direction) {
        const row   = btn.closest('tr');
        const tbody = row.parentNode;
        if (direction === -1) {
            const prev = row.previousElementSibling;
            if (prev && prev.id !== 'emptyRow') tbody.insertBefore(row, prev);
        } else {
            const next = row.nextElementSibling;
            if (next) tbody.insertBefore(next, row);
        }
        refreshSortButtons();
    }

    // ── Sort-Buttons nach jeder Änderung aktualisieren ─────────────────────
    function refreshSortButtons() {
        const tbody = document.getElementById('menuItemsTbody');
        const rows  = Array.from(tbody.querySelectorAll('tr[data-idx], tr:not(#emptyRow)'));
        rows.forEach((row, i) => {
            const btns = row.querySelectorAll('.sort-btn');
            if (btns[0]) btns[0].disabled = (i === 0);
            if (btns[1]) btns[1].disabled = (i === rows.length - 1);
        });
    }

    // ── Neue Zeile hinzufügen ─────────────────────────────────────────────
    function addRow() {
        const label  = document.getElementById('newLabel').value.trim();
        const url    = document.getElementById('newUrl').value.trim();
        const target = document.getElementById('newTarget').value;

        if (!label || !url) {
            alert('Bitte Bezeichnung und URL eingeben.');
            return;
        }

        // Leere-Hinweis-Zeile entfernen
        const emptyRow = document.getElementById('emptyRow');
        if (emptyRow) emptyRow.remove();

        const tbody = document.getElementById('menuItemsTbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><span class="drag-handle" title="Ziehen zum Umsortieren">⠿</span></td>
            <td><input type="text" name="item_label[]" value="${escHtml(label)}" placeholder="Bezeichnung" required></td>
            <td><input type="text" name="item_url[]"   value="${escHtml(url)}"   placeholder="/seite..." required></td>
            <td>
                <select name="item_target[]">
                    <option value="_self"  ${target !== '_blank' ? 'selected' : ''}>Gleicher Tab</option>
                    <option value="_blank" ${target === '_blank' ? 'selected' : ''}>Neuer Tab</option>
                </select>
            </td>
            <td style="text-align:center;">
                <button type="button" class="sort-btn" onclick="moveRow(this,-1)" title="Nach oben">↑</button>
                <button type="button" class="sort-btn" onclick="moveRow(this,1)"  title="Nach unten">↓</button>
            </td>
            <td><button type="button" class="btn-delete-item" onclick="deleteRow(this)">✕</button></td>
        `;
        tbody.appendChild(tr);

        // Felder zurücksetzen
        document.getElementById('newLabel').value  = '';
        document.getElementById('newUrl').value    = '';
        document.getElementById('newTarget').value = '_self';
        document.getElementById('newLabel').focus();

        refreshSortButtons();
    }

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // Enter im Add-Formular → addRow()
    ['newLabel','newUrl'].forEach(id => {
        document.getElementById(id)?.addEventListener('keydown', e => {
            if (e.key === 'Enter') { e.preventDefault(); addRow(); }
        });
    });

    // Initialer Zustand
    refreshSortButtons();
    </script>
</body>
</html>
