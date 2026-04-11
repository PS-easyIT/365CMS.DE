<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SYSTEM_VIEW')) {
    exit;
}

/**
 * @var array $data System-Daten
 * @var string $csrfToken CSRF-Token
 */

$system      = $data['system'] ?? [];
$database    = $data['database'] ?? [];
$permissions = $data['permissions'] ?? [];
$directories = $data['directories'] ?? [];
$statistics  = $data['statistics'] ?? [];
$security    = $data['security'] ?? [];

$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
    }
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2, ',', '.') . ' KB';
    }
    return $bytes . ' B';
};

$databaseSize = (int)($database['database_size'] ?? 0);
$activeUsers = (int)($statistics['active_users'] ?? 0);
$totalUsers = (int)($statistics['total_users'] ?? 0);
$activeSessions = (int)($statistics['total_sessions'] ?? 0);
$failedLoginsToday = (int)($statistics['failed_logins_today'] ?? 0);
$httpsEnabled = !empty($security['https_enabled']) && stripos((string)$security['https_enabled'], 'ja') !== false;
$debugEnabled = !empty($security['debug_mode']) && stripos((string)$security['debug_mode'], 'aktiv') !== false && stripos((string)$security['debug_mode'], '⚠️') !== false;

$summaryCards = [
    ['label' => 'CMS-Version', 'value' => (string)(defined('CMS_VERSION') ? CMS_VERSION : '-'), 'hint' => 'Aktueller Versionsstand', 'tone' => 'text-primary'],
    ['label' => 'PHP', 'value' => (string)($system['php_version'] ?? PHP_VERSION), 'hint' => (string)($system['server_software'] ?? ($_SERVER['SERVER_SOFTWARE'] ?? 'Server unbekannt')), 'tone' => 'text-azure'],
    ['label' => 'MySQL', 'value' => (string)($system['mysql_version'] ?? 'Unbekannt'), 'hint' => 'Datenbank-Engine', 'tone' => 'text-cyan'],
    ['label' => 'Datenbank', 'value' => !empty($database['connected']) ? 'Online' : 'Offline', 'hint' => $formatBytes($databaseSize), 'tone' => !empty($database['connected']) ? 'text-success' : 'text-danger'],
    ['label' => 'Benutzer', 'value' => (string)$totalUsers, 'hint' => $activeUsers . ' aktiv', 'tone' => 'text-blue'],
    ['label' => 'Aktive Sessions', 'value' => (string)$activeSessions, 'hint' => 'Derzeit eingeloggte Sitzungen', 'tone' => 'text-indigo'],
    ['label' => 'CMS-Tabellen', 'value' => (string)($database['cms_tables'] ?? '-'), 'hint' => (string)($database['total_tables'] ?? '-') . ' gesamt', 'tone' => 'text-purple'],
    ['label' => 'HTTPS', 'value' => $httpsEnabled ? 'Aktiv' : 'Prüfen', 'hint' => (string)($security['https_enabled'] ?? 'Unbekannt'), 'tone' => $httpsEnabled ? 'text-success' : 'text-warning'],
    ['label' => 'Fehlversuche heute', 'value' => (string)$failedLoginsToday, 'hint' => 'Login-Fehlversuche', 'tone' => $failedLoginsToday > 0 ? 'text-warning' : 'text-success'],
    ['label' => 'Debug-Modus', 'value' => $debugEnabled ? 'Aktiv' : 'Sauber', 'hint' => (string)($security['debug_mode'] ?? 'Unbekannt'), 'tone' => $debugEnabled ? 'text-warning' : 'text-success'],
    ['label' => 'Memory Limit', 'value' => (string)($system['memory_limit'] ?? ini_get('memory_limit')), 'hint' => 'PHP-Runtime', 'tone' => 'text-orange'],
    ['label' => 'Zeitzone', 'value' => (string)($system['timezone'] ?? date_default_timezone_get()), 'hint' => (string)($system['hostname'] ?? gethostname()), 'tone' => 'text-teal'],
];

$securityHighlights = [
    'HTTPS' => (string)($security['https_enabled'] ?? 'Unbekannt'),
    'CSP' => (string)($security['csp_mode'] ?? 'Unbekannt'),
    'CSP Nonce' => (string)($security['csp_nonce'] ?? 'Unbekannt'),
    'Trusted Types' => (string)($security['trusted_types'] ?? 'Unbekannt'),
    'HSTS' => (string)($security['hsts'] ?? 'Unbekannt'),
    'HSTS preload' => (string)($security['hsts_preload'] ?? 'Unbekannt'),
    'HSTS includeSubDomains' => (string)($security['hsts_include_subdomains'] ?? 'Unbekannt'),
    'Secure-Cookie' => (string)($security['session_secure'] ?? 'Unbekannt'),
    'HTTPOnly' => (string)($security['session_httponly'] ?? 'Unbekannt'),
    'SameSite' => (string)($security['session_samesite'] ?? 'Unbekannt'),
    'Display Errors' => (string)($security['display_errors'] ?? 'Unbekannt'),
    'Debug' => (string)($security['debug_mode'] ?? 'Unbekannt'),
];

$normalizeSecurityValue = static function (string $value): string {
    $value = trim($value);

    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value);
    }

    return strtolower($value);
};
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Info &amp; Diagnose</div>
                <h2 class="page-title">Info</h2>
                <div class="text-secondary mt-1">Versionsstand, Infrastruktur, Sicherheitsüberblick und Systemkennzahlen auf einen Blick.</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php $alertData = $alert; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?>

        <?php require __DIR__ . '/subnav.php'; ?>

        <div class="row row-deck row-cards mb-4">
            <?php foreach ($summaryCards as $card): ?>
                <div class="col-sm-6 col-lg-4 col-xxl-3">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="subheader"><?php echo htmlspecialchars($card['label']); ?></div>
                            <div class="h2 mb-2 <?php echo htmlspecialchars($card['tone']); ?>"><?php echo htmlspecialchars($card['value']); ?></div>
                            <div class="text-secondary small mt-auto"><?php echo htmlspecialchars($card['hint']); ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row row-deck row-cards mb-4">
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Laufzeit &amp; Server</h3></div>
                    <div class="card-body">
                        <div class="datagrid">
                            <div class="datagrid-item"><div class="datagrid-title">Webserver</div><div class="datagrid-content"><?php echo htmlspecialchars((string)($system['server_software'] ?? ($_SERVER['SERVER_SOFTWARE'] ?? '-'))); ?></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">Betriebssystem</div><div class="datagrid-content"><?php echo htmlspecialchars((string)($system['os'] ?? PHP_OS)); ?></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">Architektur</div><div class="datagrid-content"><?php echo htmlspecialchars((string)($system['architecture'] ?? '-')); ?></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">Hostname</div><div class="datagrid-content"><?php echo htmlspecialchars((string)($system['hostname'] ?? '-')); ?></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">Zeitzone</div><div class="datagrid-content"><?php echo htmlspecialchars((string)($system['timezone'] ?? date_default_timezone_get())); ?></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">Temp-Verzeichnis</div><div class="datagrid-content text-break"><?php echo htmlspecialchars((string)($system['temp_dir'] ?? sys_get_temp_dir())); ?></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">PHP-Konfiguration</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-sm">
                            <tbody>
                                <tr><td class="text-muted w-50">PHP-Version</td><td><?php echo htmlspecialchars((string)($system['php_version'] ?? PHP_VERSION)); ?></td></tr>
                                <tr><td class="text-muted">Memory Limit</td><td><?php echo htmlspecialchars((string)($system['memory_limit'] ?? ini_get('memory_limit'))); ?></td></tr>
                                <tr><td class="text-muted">Max. Upload</td><td><?php echo htmlspecialchars((string)($system['upload_max_filesize'] ?? ini_get('upload_max_filesize'))); ?></td></tr>
                                <tr><td class="text-muted">Max. POST</td><td><?php echo htmlspecialchars((string)($system['post_max_size'] ?? ini_get('post_max_size'))); ?></td></tr>
                                <tr><td class="text-muted">Max. Laufzeit</td><td><?php echo htmlspecialchars((string)(($system['max_execution_time'] ?? ini_get('max_execution_time')) . 's')); ?></td></tr>
                                <tr><td class="text-muted">Max. Input Vars</td><td><?php echo htmlspecialchars((string)($system['max_input_vars'] ?? '-')); ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Datenbank-Überblick</h3></div>
                    <div class="card-body">
                        <div class="datagrid">
                            <div class="datagrid-item"><div class="datagrid-title">Status</div><div class="datagrid-content"><span class="badge bg-<?php echo !empty($database['connected']) ? 'success' : 'danger'; ?>-lt"><?php echo !empty($database['connected']) ? 'Verbunden' : 'Offline'; ?></span></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">Datenbank</div><div class="datagrid-content"><?php echo htmlspecialchars((string)($database['database_name'] ?? '')); ?></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">MySQL-Version</div><div class="datagrid-content text-break"><?php echo htmlspecialchars((string)($system['mysql_version'] ?? 'Unbekannt')); ?></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">Größe</div><div class="datagrid-content"><?php echo htmlspecialchars($formatBytes($databaseSize)); ?></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">CMS-Tabellen</div><div class="datagrid-content"><?php echo htmlspecialchars((string)($database['cms_tables'] ?? '-')); ?></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">Tabellen gesamt</div><div class="datagrid-content"><?php echo htmlspecialchars((string)($database['total_tables'] ?? '-')); ?></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards">
            <?php if (!empty($statistics)): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-header"><h3 class="card-title">CMS-Statistiken</h3></div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-sm">
                                <tbody>
                                    <?php foreach ($statistics as $key => $value): ?>
                                        <tr>
                                            <td class="text-muted w-50"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?></td>
                                            <td><strong><?php echo htmlspecialchars((string)$value); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($security)): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-header"><h3 class="card-title">Sicherheitsstatus</h3></div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <?php foreach ($securityHighlights as $label => $value): ?>
                                    <?php
                                    $normalizedValue = $normalizeSecurityValue((string) $value);
                                    $badgeClass = str_contains($normalizedValue, '✓') || str_contains($normalizedValue, 'ja') || str_contains($normalizedValue, 'deaktiviert')
                                        ? 'bg-success-lt'
                                        : (str_contains($normalizedValue, '⚠️') || str_contains($normalizedValue, 'nein') || str_contains($normalizedValue, 'nicht gesetzt') ? 'bg-warning-lt' : 'bg-secondary-lt');
                                    ?>
                                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                        <span class="text-muted"><?php echo htmlspecialchars($label); ?></span>
                                        <span class="badge <?php echo $badgeClass; ?> text-wrap text-end"><?php echo htmlspecialchars($value); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($permissions)): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-header"><h3 class="card-title">Dateiberechtigungen</h3></div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-sm">
                                <thead><tr><th>Verzeichnis</th><th>Lesbar</th><th>Schreibbar</th></tr></thead>
                                <tbody>
                                    <?php foreach ($permissions as $perm): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars((string)($perm['path'] ?? $perm['full_path'] ?? '')); ?></td>
                                            <td>
                                                <?php $readable = is_array($perm) ? ($perm['readable'] ?? false) : false; ?>
                                                <span class="badge bg-<?php echo $readable ? 'success' : 'danger'; ?>-lt"><?php echo $readable ? 'Ja' : 'Nein'; ?></span>
                                            </td>
                                            <td>
                                                <?php $writable = is_array($perm) ? ($perm['writable'] ?? false) : false; ?>
                                                <span class="badge bg-<?php echo $writable ? 'success' : 'danger'; ?>-lt"><?php echo $writable ? 'Ja' : 'Nein'; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($directories)): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-header"><h3 class="card-title">Verzeichnisgrößen</h3></div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-sm">
                                <thead><tr><th>Verzeichnis</th><th>Größe</th></tr></thead>
                                <tbody>
                                    <?php foreach ($directories as $dir => $info): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($dir); ?></td>
                                            <td><?php echo htmlspecialchars(is_array($info) ? ($info['formatted'] ?? ($info['size'] ?? '-')) : (string)$info); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
