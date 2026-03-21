<?php
/**
 * CMS WordPress Importer – Admin-Klasse
 *
 * Registriert die Admin-Seite und verarbeitet Upload + AJAX-Import.
 * Unterstützt:
 * - Direkten Datei-Upload (AJAX + Sync-Fallback)
 * - Auswahl vorhandener Dateien aus uploads/import/, wp_import_files/ und wp_import/
 * - Bilddownload per Inhalts-Slug mit URL-Umschreibung
 *
 * @package CMS_Importer
 * @since   1.2.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (defined('CMS_IMPORTER_ADMIN_CLASS_LOADED') || class_exists('CMS_Importer_Admin', false)) {
    return;
}

define('CMS_IMPORTER_ADMIN_CLASS_LOADED', true);

class CMS_Importer_Admin
{
    private static ?self $instance = null;

    /** Maximale Upload-Größe (50 MB) */
    private const MAX_UPLOAD_MB = 50;

    /** Erlaubte MIME-Typen für XML-/JSON-Uploads */
    private const ALLOWED_MIMES = ['text/xml', 'application/xml', 'application/rss+xml', 'application/json', 'text/json', 'text/plain'];

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    // ── Hook-Registrierung ────────────────────────────────────────────────────

    public function register_pages(): void
    {
        if (!class_exists('CMS\Hooks')) {
            return;
        }

        // Hauptmenü registrieren
        add_menu_page(
            'WordPress Importer',
            'WP Importer',
            'admin',
            'cms-importer',
            [$this, 'render_import_page'],
            '📥',
            80
        );

        // Untermenü: Import
        add_submenu_page(
            'cms-importer',
            'WordPress Importer',
            'Import',
            'admin',
            'cms-importer',
            [$this, 'render_import_page']
        );

        // Untermenü: Protokoll
        add_submenu_page(
            'cms-importer',
            'Import-Protokoll',
            'Protokoll',
            'admin',
            'cms-importer-log',
            [$this, 'render_log_page']
        );

        // AJAX-Handler
        CMS\Hooks::addAction('admin_ajax_cms_importer_upload',        [$this, 'handle_ajax_upload']);
        CMS\Hooks::addAction('admin_ajax_cms_importer_preview',       [$this, 'handle_ajax_preview']);
        CMS\Hooks::addAction('admin_ajax_cms_importer_folder_import', [$this, 'handle_ajax_folder_import']);
        CMS\Hooks::addAction('admin_ajax_cms_importer_scan_folder',   [$this, 'handle_ajax_scan_folder']);
        CMS\Hooks::addAction('admin_ajax_cms_importer_download_report', [$this, 'handle_download_report']);

        // Import-Ordner sicherstellen
        $this->ensure_import_dir();
    }

    // ── Seiten-Renderer ───────────────────────────────────────────────────────

    public function render_import_page(): void
    {
        // ── AJAX-Dispatch: vor jedem HTML-Output abhandeln ────────────────────
        // Der CMS-Router gibt renderAdminLayoutStart() aus, bevor dieser Callback
        // aufgerufen wird. Deshalb müssen wir den Output-Buffer leeren und dann
        // direkt JSON senden.
        $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_ajax) {
            // Bereits gepufferten Admin-Layout-HTML verwerfen
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $action = $_POST['cms_action'] ?? '';
            switch ($action) {
                case 'cms_importer_upload':
                    $this->handle_ajax_upload();
                    exit;
                case 'cms_importer_upload_only':
                    $this->handle_ajax_upload_only();
                    exit;
                case 'cms_importer_preview':
                    $this->handle_ajax_preview();
                    exit;
                case 'cms_importer_folder_import':
                    $this->handle_ajax_folder_import();
                    exit;
                case 'cms_importer_scan_folder':
                    $this->handle_ajax_scan_folder();
                    exit;
                default:
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Unbekannte Aktion: ' . htmlspecialchars($action)]);
                    exit;
            }
        }

        // ── Synchrones POST (normales Formular ohne AJAX) ─────────────────────
        $message  = null;
        $msg_type = 'success';
        $result   = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminAction = (string) ($_POST['cms_admin_action'] ?? '');
            if ($adminAction !== '') {
                [$message, $msg_type, $result] = $this->process_admin_request($adminAction);
            } else {
                [$message, $msg_type, $result] = $this->process_upload_request();
            }
        }

        // WICHTIG: createNonce() ist die korrekte Security-Methode (nicht generateNonce!)
        $security      = class_exists('CMS\Security') ? CMS\Security::instance() : null;
        $nonce         = $security ? $security->createNonce('cms-importer-upload') : '';
        $nonce_download = $security ? $security->createNonce('cms-importer-download') : '';
        $nonce_cleanup = $security ? $security->createNonce('cms-importer-cleanup') : '';

        $log_entries     = $this->get_recent_logs(5);
        $import_files    = $this->scan_import_folder();
        $import_dir_url  = defined('UPLOAD_URL') ? rtrim(UPLOAD_URL, '/') . '/import/' : '';
        $cleanup_stats   = $this->get_cleanup_stats();
        $available_authors = $this->get_available_authors();
        $selected_author_id = max(0, (int) ($_POST['assigned_author_id'] ?? 0));
        $selected_author_display_name = trim((string) ($_POST['author_display_name'] ?? ''));

        include CMS_IMPORTER_PLUGIN_DIR . 'admin/page.php';
    }

    public function render_log_page(): void
    {
        $message  = null;
        $msg_type = 'success';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminAction = (string) ($_POST['cms_admin_action'] ?? '');
            if ($adminAction !== '') {
                [$message, $msg_type] = $this->process_admin_request($adminAction);
            }
        }

        $log_entries = $this->get_recent_logs(50);
        $security    = class_exists('CMS\Security') ? CMS\Security::instance() : null;
        $nonce_download = $security ? $security->createNonce('cms-importer-download') : '';
        $nonce_cleanup = $security ? $security->createNonce('cms-importer-cleanup') : '';
        $cleanup_stats = $this->get_cleanup_stats();
        include CMS_IMPORTER_PLUGIN_DIR . 'admin/log.php';
    }

    // ── AJAX: Datei-Upload ────────────────────────────────────────────────────

    public function handle_ajax_upload(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $nonce = (string) ($_POST['_nonce'] ?? '');

        if (!$this->is_valid_request_token($nonce, 'cms-importer-upload')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        [$message, $type, $result] = $this->process_upload_request();

        echo json_encode($type === 'error'
            ? ['success' => false, 'error' => $message]
            : ['success' => true, 'message' => $message, 'result' => $result]);
        exit;
    }

    // ── AJAX: Import aus Ordner ────────────────────────────────────────────────

    public function handle_ajax_folder_import(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $nonce = (string) ($_POST['_nonce'] ?? '');

        if (!$this->is_valid_request_token($nonce, 'cms-importer-upload')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        [$file_path, $filename, $error] = $this->resolve_requested_import_file($_POST);
        if ($error !== null) {
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }

        [$message, $type, $result] = $this->process_xml_file($file_path, $filename, 'import');

        echo json_encode($type === 'error'
            ? ['success' => false, 'error' => $message]
            : ['success' => true, 'message' => $message, 'result' => $result]);
        exit;
    }

    public function handle_ajax_preview(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $nonce = (string) ($_POST['_nonce'] ?? '');

        if (!$this->is_valid_request_token($nonce, 'cms-importer-upload')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        [$file_path, $filename, $error] = $this->resolve_requested_import_file($_POST);
        if ($error !== null) {
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }

        [$message, $type, $result] = $this->process_xml_file($file_path, $filename, 'preview');

        echo json_encode($type === 'error'
            ? ['success' => false, 'error' => $message]
            : ['success' => true, 'message' => $message, 'result' => $result]);
        exit;
    }

    // ── AJAX: Nur Upload (ohne Import) ────────────────────────────────────────

    public function handle_ajax_upload_only(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $nonce = (string) ($_POST['_nonce'] ?? '');

        if (!$this->is_valid_request_token($nonce, 'cms-importer-upload')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        if (empty($_FILES['wxr_file'])) {
            echo json_encode(['success' => false, 'error' => 'Keine Datei hochgeladen.']);
            exit;
        }

        $file = $_FILES['wxr_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => $this->upload_error_message($file['error'])]);
            exit;
        }

        $max_bytes = self::MAX_UPLOAD_MB * 1048576;
        if ($file['size'] > $max_bytes) {
            echo json_encode(['success' => false, 'error' => 'Datei zu groß. Maximum: ' . self::MAX_UPLOAD_MB . ' MB']);
            exit;
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $detected = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xml', 'json'], true) && !in_array($detected, self::ALLOWED_MIMES, true)) {
            echo json_encode(['success' => false, 'error' => 'Ungültiger Dateityp. Nur WordPress-WXR (.xml) oder Rank-Math-JSON (.json) sind erlaubt.']);
            exit;
        }

        $import_dir = $this->get_import_dir();
        if ($import_dir === '') {
            echo json_encode(['success' => false, 'error' => 'Import-Verzeichnis nicht konfiguriert (UPLOAD_PATH fehlt).']);
            exit;
        }

        $this->ensure_import_dir();

        $filename   = basename($file['name']);
        $saved_path = $import_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $saved_path)) {
            echo json_encode(['success' => false, 'error' => 'Datei konnte nicht in den Import-Ordner gespeichert werden.']);
            exit;
        }

        echo json_encode([
            'success'  => true,
            'filename' => $filename,
            'size'     => $this->format_bytes($file['size']),
        ]);
        exit;
    }

    // ── AJAX: Ordner-Scan ──────────────────────────────────────────────────────

    public function handle_ajax_scan_folder(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $nonce = (string) ($_GET['_nonce'] ?? $_POST['_nonce'] ?? '');

        if (!$this->is_valid_request_token($nonce, 'cms-importer-upload')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        echo json_encode(['success' => true, 'files' => $this->scan_import_folder()]);
        exit;
    }

    // ── AJAX: Berichts-Download ────────────────────────────────────────────────

    public function handle_download_report(): void
    {
        $nonce = (string) ($_GET['_nonce'] ?? '');

        if (!$this->is_valid_request_token($nonce, 'cms-importer-download')) {
            http_response_code(403);
            echo 'Sicherheitscheck fehlgeschlagen.';
            exit;
        }

        $log_id = (int) ($_GET['log_id'] ?? 0);
        if ($log_id <= 0) {
            http_response_code(400);
            exit;
        }

        $format = strtolower((string) ($_GET['format'] ?? 'html'));
        $path = $this->get_report_path($log_id, $format);
        if (!$path || !file_exists($path)) {
            http_response_code(404);
            echo 'Bericht nicht gefunden.';
            exit;
        }

        $filename = basename($path);
        if ($format === 'md') {
            header('Content-Type: text/markdown; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        } else {
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: inline; filename="' . $filename . '"');
        }
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    // ── Upload-Verarbeitung ────────────────────────────────────────────────────

    /**
     * Verarbeitet einen $_FILES-Upload und startet den Import.
     *
     * @return array{0: string, 1: string, 2: array|null}  [message, type, result]
     */
    private function process_upload_request(): array
    {
        if (empty($_FILES['wxr_file'])) {
            return ['Keine Datei hochgeladen.', 'error', null];
        }

        $file = $_FILES['wxr_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [$this->upload_error_message($file['error']), 'error', null];
        }

        $max_bytes = self::MAX_UPLOAD_MB * 1048576;
        if ($file['size'] > $max_bytes) {
            return ['Datei zu groß. Maximum: ' . self::MAX_UPLOAD_MB . ' MB', 'error', null];
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $detected = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xml', 'json'], true) && !in_array($detected, self::ALLOWED_MIMES, true)) {
            return ['Ungültiger Dateityp. Nur WordPress-WXR (.xml) oder Rank-Math-JSON (.json) sind erlaubt. (Erkannt: ' . $detected . ')', 'error', null];
        }

        // Optional: Datei in uploads/import/ speichern
        $save_to_folder = (bool) ($_POST['save_to_folder'] ?? false);
        $import_path    = $file['tmp_name'];

        if ($save_to_folder) {
            $import_dir   = $this->get_import_dir();
            $saved_path   = $import_dir . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $saved_path)) {
                $import_path = $saved_path;
            }
        }

        return $this->process_xml_file($import_path, $file['name']);
    }

    /**
     * @return array{0:string,1:string,2:array|null}
     */
    private function process_admin_request(string $action): array
    {
        $nonce = (string) ($_POST['_cleanup_nonce'] ?? '');
        if (!$this->is_valid_request_token($nonce, 'cms-importer-cleanup')) {
            return ['Sicherheitscheck fehlgeschlagen.', 'error', null];
        }

        $resetSequences = !empty($_POST['reset_cleanup_sequences']);

        return match ($action) {
            'cleanup_posts' => $this->cleanup_posts_entries($resetSequences),
            'cleanup_pages' => $this->cleanup_pages_entries($resetSequences),
            'cleanup_seo' => $this->cleanup_seo_entries($resetSequences),
            'cleanup_tables' => $this->cleanup_tables_entries($resetSequences),
            'cleanup_content' => $this->cleanup_content_entries($resetSequences),
            'cleanup_history' => $this->cleanup_import_history($resetSequences),
            default => ['Unbekannte Admin-Aktion.', 'error', null],
        };
    }

    /**
     * Gemeinsame Import-Logik für Upload und Ordner-Import.
     *
     * @return array{0: string, 1: string, 2: array|null}  [message, type, result]
     */
    private function process_xml_file(string $file_path, string $display_name, string $mode = 'import'): array
    {
        if (class_exists('CMS_Importer_DB')) {
            CMS_Importer_DB::create_tables();
        }

        $parser = new CMS_Importer_XML_Parser();
        $parsed = $parser->parse($file_path);

        if (!empty($parsed['errors'])) {
            return ['Import-Dateifehler: ' . implode('; ', $parsed['errors']), 'error', null];
        }

        if (!$this->looks_like_supported_import($file_path, $parsed)) {
            return ['Keine unterstützte Importdatei erkannt. Erlaubt sind WordPress-WXR (.xml) oder Rank-Math-Settings (.json).', 'error', null];
        }

        $settings_items = !empty($parsed['seo_settings']['settings']) && is_array($parsed['seo_settings']['settings']) ? 1 : 0;
        $total_items = $settings_items + count($parsed['posts']) + count($parsed['pages']) + count($parsed['tables']) + count($parsed['redirects'] ?? []) + count($parsed['others']);
        if ($total_items === 0) {
            return ['Keine importierbaren Inhalte (SEO-Settings, Beiträge, Seiten, Tabellen, Weiterleitungen oder weitere Post-Types) gefunden.', 'warning', null];
        }

        // Import-Optionen aus POST lesen
        $options = [
            'skip_duplicates'     => isset($_POST['skip_duplicates']),
            'import_drafts'       => isset($_POST['import_drafts']),
            'import_trashed'      => isset($_POST['import_trashed']),
            'import_custom_types' => isset($_POST['import_custom_types']),
            'import_only_en'      => isset($_POST['import_only_en']),
            'generate_report'     => isset($_POST['generate_report']),
            'download_images'     => isset($_POST['download_images']),
            'convert_table_shortcodes' => isset($_POST['convert_table_shortcodes']),
            'assigned_author_id'  => max(0, (int) ($_POST['assigned_author_id'] ?? 0)),
            'author_display_name' => trim((string) ($_POST['author_display_name'] ?? '')),
        ];

        // WICHTIG: CMS\Auth::getCurrentUser() ist eine statische Methode
        $user    = class_exists('CMS\Auth') ? CMS\Auth::getCurrentUser() : null;
        $user_id = $user ? (int) ($user->id ?? 0) : 0;

        $service = new CMS_Importer_Service();
        $result  = $mode === 'preview'
            ? $service->preview($parsed, $display_name, $options)
            : $service->import($parsed, $display_name, $user_id, $options);

        if (!empty($result['error'])) {
            return [(string) $result['error'], 'error', $result];
        }

        if ($mode === 'preview') {
            $msg = sprintf(
                'Vorschau erstellt: %d von %d Elementen würden importiert, %d würden übersprungen.',
                (int) ($result['would_import'] ?? 0),
                (int) ($result['total'] ?? 0),
                (int) ($result['would_skip'] ?? 0)
            );

            $details = [];
            if (($result['preview_counts']['posts'] ?? 0) > 0) {
                $details[] = (int) $result['preview_counts']['posts'] . ' Beiträge';
            }
            if (($result['preview_counts']['settings'] ?? 0) > 0) {
                $details[] = (int) $result['preview_counts']['settings'] . ' SEO-Settings-Bundle';
            }
            if (($result['preview_counts']['pages'] ?? 0) > 0) {
                $details[] = (int) $result['preview_counts']['pages'] . ' Seiten';
            }
            if (($result['preview_counts']['tables'] ?? 0) > 0) {
                $details[] = (int) $result['preview_counts']['tables'] . ' Tabellen';
            }
            if (($result['preview_counts']['redirects'] ?? 0) > 0) {
                $details[] = (int) ($result['preview_counts']['redirects'] ?? 0) . ' Weiterleitungen';
            }
            if (($result['preview_counts']['others'] ?? 0) > 0) {
                $details[] = (int) $result['preview_counts']['others'] . ' weitere Typen';
            }
            if ($details !== []) {
                $msg .= ' | ' . implode(', ', $details) . '.';
            }

            if (($result['comments_detected'] ?? 0) > 0) {
                $msg .= sprintf(
                    ' | Kommentare: %d erkannt, %d würden importiert, %d würden übersprungen.',
                    (int) ($result['comments_detected'] ?? 0),
                    (int) ($result['comments_would_import'] ?? 0),
                    (int) ($result['comments_would_skip'] ?? 0)
                );
            }

            if (($result['table_shortcodes_found'] ?? 0) > 0) {
                $msg .= sprintf(
                    ' | %d Tabellen-Shortcodes gefunden, %d davon auflösbar.',
                    (int) ($result['table_shortcodes_found'] ?? 0),
                    (int) ($result['table_shortcodes_resolved'] ?? 0)
                );
            }

            if (($result['meta_keys'] ?? 0) > 0) {
                $msg .= sprintf(' | %d unbekannte Meta-Keys würden protokolliert.', (int) $result['meta_keys']);
            }

            if (($result['source_counts']['settings'] ?? 0) > 0) {
                $msg .= ' | Rank-Math-SEO-Defaults würden in die globalen 365CMS-SEO-Einstellungen übernommen.';
            }

            return [$msg, 'success', $result];
        }

        $security = class_exists('CMS\Security') ? CMS\Security::instance() : null;
        $downloadNonce = $security ? $security->createNonce('cms-importer-download') : '';
        if (!empty($result['meta_report'])) {
            $baseUrl = '/admin/plugins/cms-importer/cms-importer?action=download_report&log_id=' . (int) ($result['log_id'] ?? 0) . '&_nonce=' . rawurlencode($downloadNonce);
            $result['meta_report_download_url'] = $baseUrl . '&format=html';
            $result['meta_report_markdown_url'] = $baseUrl . '&format=md';
        }

        $msg = sprintf(
            'Import abgeschlossen: %d importiert, %d übersprungen, %d Fehler%s.',
            $result['imported'],
            $result['skipped'],
            $result['errors'],
            $result['images_downloaded'] > 0
                ? ', ' . $result['images_downloaded'] . ' Bilder heruntergeladen'
                : ''
        );

        $details = [];
        if (($result['settings_imported'] ?? 0) > 0) {
            $settingsLabel = (int) ($result['settings_keys_imported'] ?? 0) > 0
                ? (int) ($result['settings_keys_imported'] ?? 0) . ' SEO-Settings'
                : '1 SEO-Settings-Bundle';
            $details[] = $settingsLabel;
        }
        if (($result['posts_imported'] ?? 0) > 0) {
            $details[] = (int) $result['posts_imported'] . ' Beiträge';
        }
        if (($result['pages_imported'] ?? 0) > 0) {
            $details[] = (int) $result['pages_imported'] . ' Seiten';
        }
        if (($result['tables_imported'] ?? 0) > 0) {
            $details[] = (int) $result['tables_imported'] . ' Tabellen';
        }
        if (($result['redirects_imported'] ?? 0) > 0) {
            $details[] = (int) $result['redirects_imported'] . ' Weiterleitungen';
        }
        if (($result['others_imported'] ?? 0) > 0) {
            $details[] = (int) $result['others_imported'] . ' weitere Typen';
        }
        if ($details !== []) {
            $msg .= ' | ' . implode(', ', $details) . '.';
        }

        if (($result['comments_total'] ?? 0) > 0) {
            $msg .= sprintf(
                ' | Kommentare: %d importiert, %d übersprungen.',
                (int) ($result['comments_imported'] ?? 0),
                (int) ($result['comments_skipped'] ?? 0)
            );
        }

        if (!empty($result['skip_reasons']) && is_array($result['skip_reasons'])) {
            $normalizedSkipReasons = [];
            foreach ($result['skip_reasons'] as $reason => $count) {
                $normalizedReason = trim((string) $reason);
                if ($normalizedReason === '') {
                    $normalizedReason = 'Unbekannter Überspring-Grund';
                }
                $normalizedSkipReasons[$normalizedReason] = (int) ($normalizedSkipReasons[$normalizedReason] ?? 0) + (int) $count;
            }

            $result['skip_reasons'] = $normalizedSkipReasons;
            arsort($result['skip_reasons']);
            $skipSummary = [];
            foreach (array_slice($result['skip_reasons'], 0, 3, true) as $reason => $count) {
                $skipSummary[] = (int) $count . '× ' . (string) $reason;
            }
            if ($skipSummary !== []) {
                $msg .= ' | Übersprungen wegen: ' . implode(', ', $skipSummary) . '.';
            }
        }

        if ($result['meta_keys'] > 0) {
            $msg .= sprintf(' | %d unbekannte Meta-Keys → Bericht gespeichert.', $result['meta_keys']);
        }

        return [$msg, $result['errors'] > 0 ? 'warning' : 'success', $result];
    }

    /**
     * @param array<string, mixed> $request
     * @return array{0:string,1:string,2:?string}
     */
    private function resolve_requested_import_file(array $request): array
    {
        $raw_name = (string) ($request['import_file'] ?? '');
        if ($raw_name === '') {
            return ['', '', 'Kein Dateiname angegeben.'];
        }

        $sourceKey = (string) ($request['import_source'] ?? 'uploads');
        $source = $this->get_import_source($sourceKey);
        if ($source === null) {
            return ['', '', 'Ungültige Import-Quelle.'];
        }

        $filename = basename($raw_name);
        $filePath = rtrim((string) $source['path'], DIRECTORY_SEPARATOR . '/') . '/' . $filename;
        if (!file_exists($filePath)) {
            return ['', '', 'Datei nicht gefunden: ' . htmlspecialchars($filename)];
        }

        return [$filePath, $filename, null];
    }

    // ── Import-Ordner ─────────────────────────────────────────────────────────

    /**
     * Gibt den absoluten Pfad zum Import-Ordner zurück.
     */
    private function get_import_dir(): string
    {
        if (!defined('UPLOAD_PATH')) {
            return '';
        }
        return rtrim(UPLOAD_PATH, '/') . '/import/';
    }

    /**
     * Stellt sicher dass der Import-Ordner existiert.
     */
    private function ensure_import_dir(): void
    {
        $dir = $this->get_import_dir();
        if ($dir !== '' && !is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Listet alle XML-/JSON-Dateien im Import-Ordner auf.
     *
     * @return array  Array von ['name' => string, 'size' => int, 'date' => string, 'size_human' => string]
     */
    public function scan_import_folder(): array
    {
        $files = [];

        foreach ($this->get_import_sources() as $sourceKey => $source) {
            $dir = (string) ($source['path'] ?? '');
            if ($dir === '' || !is_dir($dir)) {
                continue;
            }

            foreach (['*.xml', '*.json'] as $pattern) {
                foreach (glob(rtrim($dir, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . $pattern) ?: [] as $path) {
                    $size = (int) filesize($path);
                    $mtime = (int) filemtime($path);
                    $files[] = [
                        'name'         => basename($path),
                        'size'         => $size,
                        'size_human'   => $this->format_bytes($size),
                        'date'         => date('d.m.Y H:i', $mtime),
                        'timestamp'    => $mtime,
                        'source_key'   => $sourceKey,
                        'source_label' => (string) ($source['label'] ?? $sourceKey),
                        'source_hint'  => (string) ($source['hint'] ?? ''),
                    ];
                }
            }
        }

        // Neueste zuerst
        usort($files, static fn ($a, $b) => ((int) ($b['timestamp'] ?? 0)) <=> ((int) ($a['timestamp'] ?? 0)));

        return $files;
    }

    /**
     * @return array<string, array{path:string,label:string,hint:string}>
     */
    private function get_import_sources(): array
    {
        $sources = [];

        $uploadDir = $this->get_import_dir();
        if ($uploadDir !== '') {
            $sources['uploads'] = [
                'path' => $uploadDir,
                'label' => 'Uploads / import',
                'hint' => 'Standard-Importordner im Upload-Verzeichnis',
            ];
        }

        $bundledDir = CMS_IMPORTER_PLUGIN_DIR . 'wp_import_files/';
        if (is_dir($bundledDir)) {
            $sources['bundled'] = [
                'path' => $bundledDir,
                'label' => 'Plugin / wp_import_files',
                'hint' => 'Mitgelieferter XML-/JSON-Ordner im Plugin',
            ];
        }

        $legacyBundledDir = CMS_IMPORTER_PLUGIN_DIR . 'wp_import/';
        if (is_dir($legacyBundledDir)) {
            $sources['wp_import'] = [
                'path' => $legacyBundledDir,
                'label' => 'Plugin / wp_import',
                'hint' => 'Bestehender Import-Ordner für XML-/JSON-Dateien im Plugin',
            ];
        }

        return $sources;
    }

    private function get_import_source(string $sourceKey): ?array
    {
        $sources = $this->get_import_sources();
        return $sources[$sourceKey] ?? null;
    }

    // ── DB-Hilfsmethoden ──────────────────────────────────────────────────────

    private function get_report_path(int $log_id, string $format = 'html'): ?string
    {
        if (!class_exists('CMS\Database')) {
            return null;
        }
        $db = CMS\Database::instance();
        $p  = $db->getPrefix();
        $path = $db->get_var(
            "SELECT meta_report_path FROM {$p}import_log WHERE id = ?",
            [$log_id]
        ) ?: null;

        if (!$path) {
            return null;
        }

        if ($format === 'md') {
            return $path;
        }

        $htmlPath = preg_replace('/\.md$/i', '.html', $path) ?? $path;
        return file_exists($htmlPath) ? $htmlPath : $path;
    }

    private function get_recent_logs(int $limit = 10): array
    {
        if (!class_exists('CMS\Database')) {
            return [];
        }
        $db = CMS\Database::instance();
        $p  = $db->getPrefix();
        try {
            return $db->get_results(
                "SELECT * FROM {$p}import_log ORDER BY started_at DESC LIMIT ?",
                [$limit]
            ) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @return array{posts:int,pages:int,tables:int,seo_total:int,seo_settings:int,seo_meta:int,logs:int,mappings:int,meta:int,reports:int}
     */
    private function get_cleanup_stats(): array
    {
        if (!class_exists('CMS\Database')) {
            return [
                'posts' => 0,
                'pages' => 0,
                'tables' => 0,
                'seo_total' => 0,
                'seo_settings' => 0,
                'seo_meta' => 0,
                'logs' => 0,
                'mappings' => 0,
                'meta' => 0,
                'reports' => $this->count_report_files(),
            ];
        }

        $db = CMS\Database::instance();
        $p = $db->getPrefix();

        $seoSettings = $this->count_setting_rows_like($db, $p . 'settings', 'seo\\_%');
        $seoMeta = $this->count_table_rows($db, $p . 'seo_meta');

        return [
            'posts' => $this->count_table_rows($db, $p . 'posts'),
            'pages' => $this->count_table_rows($db, $p . 'pages'),
            'tables' => $this->count_table_rows($db, $p . 'site_tables'),
            'seo_total' => $seoSettings + $seoMeta,
            'seo_settings' => $seoSettings,
            'seo_meta' => $seoMeta,
            'logs' => $this->count_table_rows($db, $p . 'import_log'),
            'mappings' => $this->count_table_rows($db, $p . 'import_items'),
            'meta' => $this->count_table_rows($db, $p . 'import_meta'),
            'reports' => $this->count_report_files(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function get_available_authors(): array
    {
        if (!class_exists('CMS\Database')) {
            return [];
        }

        $db = CMS\Database::instance();
        $p = $db->getPrefix();

        try {
            $rows = $db->get_results(
                "SELECT id, username, display_name, email, role, status
                 FROM {$p}users
                 WHERE status = 'active'
                 ORDER BY display_name ASC, username ASC"
            ) ?: [];

            return array_map(static function (object $row): array {
                return [
                    'id' => (int) ($row->id ?? 0),
                    'username' => (string) ($row->username ?? ''),
                    'display_name' => (string) ($row->display_name ?? ''),
                    'email' => (string) ($row->email ?? ''),
                    'role' => (string) ($row->role ?? ''),
                ];
            }, $rows);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array{0:string,1:string,2:array<string,int>|null}
     */
    private function cleanup_content_entries(bool $resetSequences = false): array
    {
        if (!class_exists('CMS\Database')) {
            return ['CMS\\Database nicht verfügbar.', 'error', null];
        }

        $db = CMS\Database::instance();
        $p = $db->getPrefix();
        $plannedPosts = $this->count_table_rows($db, $p . 'posts');
        $plannedPages = $this->count_table_rows($db, $p . 'pages');
        $removedMappings = 0;
        $removedSeo = 0;
        $removedTagRelations = 0;

        if ($plannedPosts === 0 && $plannedPages === 0) {
            return ['Es sind aktuell keine Beiträge oder Seiten vorhanden. Es wurden keine Inhalte gelöscht.', 'warning', [
                'planned_posts' => 0,
                'planned_pages' => 0,
                'deleted_posts' => 0,
                'deleted_pages' => 0,
                'mappings' => 0,
                'seo_meta' => 0,
                'tag_relations' => 0,
                'remaining_posts' => 0,
                'remaining_pages' => 0,
            ]];
        }

        try {
            if ($this->has_table($db, $p . 'post_tag_rel')) {
                $removedTagRelations = (int) ($db->execute("DELETE FROM {$p}post_tag_rel")?->rowCount() ?? 0);
            }

            if ($this->has_table($db, $p . 'posts')) {
                $db->execute("DELETE FROM {$p}posts");
            }

            if ($this->has_table($db, $p . 'pages')) {
                $db->execute("DELETE FROM {$p}pages");
            }

            if ($this->has_table($db, $p . 'post_tags')) {
                $db->execute("UPDATE {$p}post_tags SET post_count = 0");
            }

            if ($this->has_table($db, $p . 'seo_meta')) {
                $removedSeo += (int) ($db->execute("DELETE FROM {$p}seo_meta WHERE content_type IN ('post', 'page')")?->rowCount() ?? 0);
            }

            if ($this->has_table($db, $p . 'import_items')) {
                $removedMappings = (int) ($db->execute("DELETE FROM {$p}import_items WHERE target_type IN ('post', 'page')")?->rowCount() ?? 0);
            }
        } catch (\Throwable $e) {
            return ['Bereinigung von Beiträgen/Seiten fehlgeschlagen: ' . $e->getMessage(), 'error', null];
        }

        $remainingPosts = $this->count_table_rows($db, $p . 'posts');
        $remainingPages = $this->count_table_rows($db, $p . 'pages');
        $deletedPosts = max(0, $plannedPosts - $remainingPosts);
        $deletedPages = max(0, $plannedPages - $remainingPages);

        $statusType = ($remainingPosts === 0 && $remainingPages === 0) ? 'success' : 'warning';

        $message = sprintf(
            'Globale Bereinigung abgeschlossen: %d/%d Beiträge und %d/%d Seiten entfernt.',
            $deletedPosts,
            $plannedPosts,
            $deletedPages,
            $plannedPages
        );

        $extras = [];
        if ($removedMappings > 0) {
            $extras[] = $removedMappings . ' Import-Mappings';
        }
        if ($removedSeo > 0) {
            $extras[] = $removedSeo . ' SEO-Metadaten';
        }
        if ($removedTagRelations > 0) {
            $extras[] = $removedTagRelations . ' Tag-Zuordnungen';
        }
        if ($extras !== []) {
            $message .= ' Zusätzlich bereinigt: ' . implode(', ', $extras) . '.';
        }

        if ($remainingPosts > 0 || $remainingPages > 0) {
            $message .= sprintf(
                ' Achtung: %d Beiträge und %d Seiten sind weiterhin vorhanden.',
                $remainingPosts,
                $remainingPages
            );
        }

        if ($resetSequences) {
            $sequenceNotice = $this->buildSequenceResetNotice($db, [
                $p . 'import_items' => 'Import-Mappings',
            ]);
            if ($sequenceNotice !== '') {
                $message .= ' ' . $sequenceNotice;
            }
        }

        return [$message, $statusType, [
            'planned_posts' => $plannedPosts,
            'planned_pages' => $plannedPages,
            'deleted_posts' => $deletedPosts,
            'deleted_pages' => $deletedPages,
            'mappings' => $removedMappings,
            'seo_meta' => $removedSeo,
            'tag_relations' => $removedTagRelations,
            'remaining_posts' => $remainingPosts,
            'remaining_pages' => $remainingPages,
        ]];
    }

    /**
     * @return array{0:string,1:string,2:array<string,int>|null}
     */
    private function cleanup_posts_entries(bool $resetSequences = false): array
    {
        if (!class_exists('CMS\Database')) {
            return ['CMS\\Database nicht verfügbar.', 'error', null];
        }

        $db = CMS\Database::instance();
        $p = $db->getPrefix();

        $plannedPosts = $this->count_table_rows($db, $p . 'posts');
        if ($plannedPosts === 0) {
            return ['Es sind aktuell keine Beiträge vorhanden. Es wurden keine Beiträge bereinigt.', 'warning', [
                'planned_posts' => 0,
                'deleted_posts' => 0,
                'comments' => 0,
                'tag_relations' => 0,
                'seo_meta' => 0,
                'mappings' => 0,
                'remaining_posts' => 0,
            ]];
        }

        $deletedComments = 0;
        $deletedTagRelations = 0;
        $deletedSeoMeta = 0;
        $deletedMappings = 0;

        try {
            if ($this->has_table($db, $p . 'comments') && $this->has_table($db, $p . 'posts')) {
                $deletedComments = (int) ($db->execute(
                    "DELETE c FROM {$p}comments c INNER JOIN {$p}posts p ON c.post_id = p.id"
                )?->rowCount() ?? 0);
            }

            if ($this->has_table($db, $p . 'post_tag_rel')) {
                $deletedTagRelations = (int) ($db->execute("DELETE FROM {$p}post_tag_rel")?->rowCount() ?? 0);
            }

            if ($this->has_table($db, $p . 'posts')) {
                $db->execute("DELETE FROM {$p}posts");
            }

            if ($this->has_table($db, $p . 'post_tags')) {
                $db->execute("UPDATE {$p}post_tags SET post_count = 0");
            }

            if ($this->has_table($db, $p . 'seo_meta')) {
                $deletedSeoMeta = (int) ($db->execute("DELETE FROM {$p}seo_meta WHERE content_type = ?", ['post'])?->rowCount() ?? 0);
            }

            if ($this->has_table($db, $p . 'import_items')) {
                $deletedMappings += (int) ($db->execute("DELETE FROM {$p}import_items WHERE target_type = ?", ['post'])?->rowCount() ?? 0);
                $deletedMappings += (int) ($db->execute("DELETE FROM {$p}import_items WHERE target_type = ?", ['comment'])?->rowCount() ?? 0);
            }
        } catch (\Throwable $e) {
            return ['Bereinigung der Beiträge fehlgeschlagen: ' . $e->getMessage(), 'error', null];
        }

        $remainingPosts = $this->count_table_rows($db, $p . 'posts');
        $deletedPosts = max(0, $plannedPosts - $remainingPosts);
        $message = sprintf('Beitrags-Bereinigung abgeschlossen: %d/%d Beiträge entfernt.', $deletedPosts, $plannedPosts);

        $extras = [];
        if ($deletedComments > 0) {
            $extras[] = $deletedComments . ' Kommentare';
        }
        if ($deletedTagRelations > 0) {
            $extras[] = $deletedTagRelations . ' Tag-Zuordnungen';
        }
        if ($deletedSeoMeta > 0) {
            $extras[] = $deletedSeoMeta . ' SEO-Metadaten';
        }
        if ($deletedMappings > 0) {
            $extras[] = $deletedMappings . ' Import-Mappings';
        }
        if ($extras !== []) {
            $message .= ' Zusätzlich bereinigt: ' . implode(', ', $extras) . '.';
        }

        if ($remainingPosts > 0) {
            $message .= ' Achtung: Es sind weiterhin ' . $remainingPosts . ' Beiträge vorhanden.';
        }

        if ($resetSequences) {
            $sequenceNotice = $this->buildSequenceResetNotice($db, [
                $p . 'import_items' => 'Import-Mappings',
            ]);
            if ($sequenceNotice !== '') {
                $message .= ' ' . $sequenceNotice;
            }
        }

        return [$message, $remainingPosts === 0 ? 'success' : 'warning', [
            'planned_posts' => $plannedPosts,
            'deleted_posts' => $deletedPosts,
            'comments' => $deletedComments,
            'tag_relations' => $deletedTagRelations,
            'seo_meta' => $deletedSeoMeta,
            'mappings' => $deletedMappings,
            'remaining_posts' => $remainingPosts,
        ]];
    }

    /**
     * @return array{0:string,1:string,2:array<string,int>|null}
     */
    private function cleanup_pages_entries(bool $resetSequences = false): array
    {
        if (!class_exists('CMS\Database')) {
            return ['CMS\\Database nicht verfügbar.', 'error', null];
        }

        $db = CMS\Database::instance();
        $p = $db->getPrefix();

        $plannedPages = $this->count_table_rows($db, $p . 'pages');
        if ($plannedPages === 0) {
            return ['Es sind aktuell keine Seiten vorhanden. Es wurden keine Seiten bereinigt.', 'warning', [
                'planned_pages' => 0,
                'deleted_pages' => 0,
                'seo_meta' => 0,
                'mappings' => 0,
                'remaining_pages' => 0,
            ]];
        }

        $deletedSeoMeta = 0;
        $deletedMappings = 0;

        try {
            if ($this->has_table($db, $p . 'pages')) {
                $db->execute("DELETE FROM {$p}pages");
            }

            if ($this->has_table($db, $p . 'seo_meta')) {
                $deletedSeoMeta = (int) ($db->execute("DELETE FROM {$p}seo_meta WHERE content_type = ?", ['page'])?->rowCount() ?? 0);
            }

            if ($this->has_table($db, $p . 'import_items')) {
                $deletedMappings = (int) ($db->execute("DELETE FROM {$p}import_items WHERE target_type = ?", ['page'])?->rowCount() ?? 0);
            }
        } catch (\Throwable $e) {
            return ['Bereinigung der Seiten fehlgeschlagen: ' . $e->getMessage(), 'error', null];
        }

        $remainingPages = $this->count_table_rows($db, $p . 'pages');
        $deletedPages = max(0, $plannedPages - $remainingPages);
        $message = sprintf('Seiten-Bereinigung abgeschlossen: %d/%d Seiten entfernt.', $deletedPages, $plannedPages);

        $extras = [];
        if ($deletedSeoMeta > 0) {
            $extras[] = $deletedSeoMeta . ' SEO-Metadaten';
        }
        if ($deletedMappings > 0) {
            $extras[] = $deletedMappings . ' Import-Mappings';
        }
        if ($extras !== []) {
            $message .= ' Zusätzlich bereinigt: ' . implode(', ', $extras) . '.';
        }

        if ($remainingPages > 0) {
            $message .= ' Achtung: Es sind weiterhin ' . $remainingPages . ' Seiten vorhanden.';
        }

        if ($resetSequences) {
            $sequenceNotice = $this->buildSequenceResetNotice($db, [
                $p . 'import_items' => 'Import-Mappings',
            ]);
            if ($sequenceNotice !== '') {
                $message .= ' ' . $sequenceNotice;
            }
        }

        return [$message, $remainingPages === 0 ? 'success' : 'warning', [
            'planned_pages' => $plannedPages,
            'deleted_pages' => $deletedPages,
            'seo_meta' => $deletedSeoMeta,
            'mappings' => $deletedMappings,
            'remaining_pages' => $remainingPages,
        ]];
    }

    /**
     * @return array{0:string,1:string,2:array<string,int>|null}
     */
    private function cleanup_tables_entries(bool $resetSequences = false): array
    {
        if (!class_exists('CMS\Database')) {
            return ['CMS\\Database nicht verfügbar.', 'error', null];
        }

        $db = CMS\Database::instance();
        $p = $db->getPrefix();

        $plannedTables = $this->count_table_rows($db, $p . 'site_tables');
        if ($plannedTables === 0) {
            return ['Es sind aktuell keine Tabellen vorhanden. Es wurden keine Tabellen bereinigt.', 'warning', [
                'planned_tables' => 0,
                'deleted_tables' => 0,
                'mappings' => 0,
                'remaining_tables' => 0,
            ]];
        }

        $deletedMappings = 0;

        try {
            if ($this->has_table($db, $p . 'site_tables')) {
                $db->execute("DELETE FROM {$p}site_tables");
                $this->reset_auto_increment($db, $p . 'site_tables');
            }

            if ($this->has_table($db, $p . 'import_items')) {
                $deletedMappings = (int) ($db->execute("DELETE FROM {$p}import_items WHERE target_type = ?", ['site_table'])?->rowCount() ?? 0);
            }
        } catch (\Throwable $e) {
            return ['Bereinigung der Tabellen fehlgeschlagen: ' . $e->getMessage(), 'error', null];
        }

        $remainingTables = $this->count_table_rows($db, $p . 'site_tables');
        $deletedTables = max(0, $plannedTables - $remainingTables);
        $message = sprintf('Tabellen-Bereinigung abgeschlossen: %d/%d Tabellen entfernt.', $deletedTables, $plannedTables);
        if ($deletedMappings > 0) {
            $message .= ' Zusätzlich bereinigt: ' . $deletedMappings . ' Import-Mappings.';
        }
        if ($remainingTables > 0) {
            $message .= ' Achtung: Es sind weiterhin ' . $remainingTables . ' Tabellen vorhanden.';
        }

        if ($resetSequences) {
            $sequenceNotice = $this->buildSequenceResetNotice($db, [
                $p . 'import_items' => 'Import-Mappings',
            ]);
            if ($sequenceNotice !== '') {
                $message .= ' ' . $sequenceNotice;
            }
        }

        return [$message, $remainingTables === 0 ? 'success' : 'warning', [
            'planned_tables' => $plannedTables,
            'deleted_tables' => $deletedTables,
            'mappings' => $deletedMappings,
            'remaining_tables' => $remainingTables,
        ]];
    }

    /**
     * @return array{0:string,1:string,2:array<string,int>|null}
     */
    private function cleanup_seo_entries(bool $resetSequences = false): array
    {
        if (!class_exists('CMS\Database')) {
            return ['CMS\\Database nicht verfügbar.', 'error', null];
        }

        $db = CMS\Database::instance();
        $p = $db->getPrefix();

        $plannedSettings = $this->count_setting_rows_like($db, $p . 'settings', 'seo\\_%');
        $plannedSeoMeta = $this->count_table_rows($db, $p . 'seo_meta');
        $plannedMappings = $this->count_target_type_rows($db, $p . 'import_items', 'setting_bundle');

        if ($plannedSettings === 0 && $plannedSeoMeta === 0 && $plannedMappings === 0) {
            return ['Es sind aktuell keine SEO-Datensätze vorhanden. Es wurden keine SEO-Daten bereinigt.', 'warning', [
                'planned_settings' => 0,
                'planned_seo_meta' => 0,
                'planned_mappings' => 0,
                'deleted_settings' => 0,
                'deleted_seo_meta' => 0,
                'deleted_mappings' => 0,
            ]];
        }

        $deletedSettings = 0;
        $deletedSeoMeta = 0;
        $deletedMappings = 0;

        try {
            if ($this->has_table($db, $p . 'settings')) {
                $deletedSettings = (int) ($db->execute("DELETE FROM {$p}settings WHERE option_name LIKE ? ESCAPE '\\\\'", ['seo\\_%'])?->rowCount() ?? 0);
            }

            if ($this->has_table($db, $p . 'seo_meta')) {
                $deletedSeoMeta = (int) ($db->execute("DELETE FROM {$p}seo_meta")?->rowCount() ?? 0);
            }

            if ($this->has_table($db, $p . 'import_items')) {
                $deletedMappings = (int) ($db->execute("DELETE FROM {$p}import_items WHERE target_type = ?", ['setting_bundle'])?->rowCount() ?? 0);
            }
        } catch (\Throwable $e) {
            return ['Bereinigung der SEO-Daten fehlgeschlagen: ' . $e->getMessage(), 'error', null];
        }

        $message = 'SEO-Bereinigung abgeschlossen.';
        $details = [];
        if ($deletedSettings > 0) {
            $details[] = $deletedSettings . ' globale SEO-Settings';
        }
        if ($deletedSeoMeta > 0) {
            $details[] = $deletedSeoMeta . ' SEO-Metadaten';
        }
        if ($deletedMappings > 0) {
            $details[] = $deletedMappings . ' Import-Mappings';
        }
        if ($details !== []) {
            $message .= ' Entfernt: ' . implode(', ', $details) . '.';
        }
        $message .= ' Redirect-Regeln bleiben dabei unberührt.';

        if ($resetSequences) {
            $sequenceNotice = $this->buildSequenceResetNotice($db, [
                $p . 'import_items' => 'Import-Mappings',
            ]);
            if ($sequenceNotice !== '') {
                $message .= ' ' . $sequenceNotice;
            }
        }

        return [$message, 'success', [
            'planned_settings' => $plannedSettings,
            'planned_seo_meta' => $plannedSeoMeta,
            'planned_mappings' => $plannedMappings,
            'deleted_settings' => $deletedSettings,
            'deleted_seo_meta' => $deletedSeoMeta,
            'deleted_mappings' => $deletedMappings,
        ]];
    }

    /**
     * @return array{0:string,1:string,2:array<string,int>|null}
     */
    private function cleanup_import_history(bool $resetSequences = false): array
    {
        if (!class_exists('CMS\Database')) {
            return ['CMS\\Database nicht verfügbar.', 'error', null];
        }

        $db = CMS\Database::instance();
        $p = $db->getPrefix();

        $removedLogs = $this->count_table_rows($db, $p . 'import_log');
        $removedMappings = $this->count_table_rows($db, $p . 'import_items');
        $removedMeta = $this->count_table_rows($db, $p . 'import_meta');
        $removedReports = 0;

        try {
            $removedReports = $this->cleanup_report_files($db, $p);

            if ($this->has_table($db, $p . 'import_meta')) {
                $db->execute("DELETE FROM {$p}import_meta");
            }

            if ($this->has_table($db, $p . 'import_items')) {
                $db->execute("DELETE FROM {$p}import_items");
            }

            if ($this->has_table($db, $p . 'import_log')) {
                $db->execute("DELETE FROM {$p}import_log");
            }
        } catch (\Throwable $e) {
            return ['Bereinigung des Import-Verlaufs fehlgeschlagen: ' . $e->getMessage(), 'error', null];
        }

        $message = sprintf(
            'Importer-Verlauf gelöscht: %d Protokolle, %d Mappings und %d Meta-Einträge entfernt.',
            $removedLogs,
            $removedMappings,
            $removedMeta
        );
        if ($removedReports > 0) {
            $message .= ' Zusätzlich wurden ' . $removedReports . ' Bericht-Dateien entfernt.';
        }

        if ($resetSequences) {
            $sequenceNotice = $this->buildSequenceResetNotice($db, [
                $p . 'import_log' => 'Import-Logs',
                $p . 'import_items' => 'Import-Mappings',
                $p . 'import_meta' => 'Import-Meta',
            ]);
            if ($sequenceNotice !== '') {
                $message .= ' ' . $sequenceNotice;
            }
        }

        return [$message, 'success', [
            'logs' => $removedLogs,
            'mappings' => $removedMappings,
            'meta' => $removedMeta,
            'reports' => $removedReports,
        ]];
    }

    private function has_table(\CMS\Database $db, string $tableName): bool
    {
        try {
            return (int) ($db->get_var(
                'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                [defined('DB_NAME') ? DB_NAME : '', $tableName]
            ) ?? 0) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function count_table_rows(\CMS\Database $db, string $tableName): int
    {
        if (!$this->has_table($db, $tableName)) {
            return 0;
        }

        try {
            return (int) ($db->get_var("SELECT COUNT(*) FROM {$tableName}") ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function count_setting_rows_like(\CMS\Database $db, string $tableName, string $pattern): int
    {
        if (!$this->has_table($db, $tableName)) {
            return 0;
        }

        try {
            return (int) ($db->get_var("SELECT COUNT(*) FROM {$tableName} WHERE option_name LIKE ? ESCAPE '\\\\'", [$pattern]) ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function count_target_type_rows(\CMS\Database $db, string $tableName, string $targetType): int
    {
        if (!$this->has_table($db, $tableName)) {
            return 0;
        }

        try {
            return (int) ($db->get_var("SELECT COUNT(*) FROM {$tableName} WHERE target_type = ?", [$targetType]) ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function reset_auto_increment(\CMS\Database $db, string $tableName, int $nextValue = 1): bool
    {
        if (!$this->has_table($db, $tableName)) {
            return false;
        }

        try {
            $db->execute("ALTER TABLE {$tableName} AUTO_INCREMENT = " . max(1, $nextValue));
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param array<string,string> $tableLabels
     */
    private function buildSequenceResetNotice(\CMS\Database $db, array $tableLabels): string
    {
        $reset = [];
        $skipped = [];

        foreach ($tableLabels as $tableName => $label) {
            if (!$this->has_table($db, $tableName)) {
                continue;
            }

            if ($this->count_table_rows($db, $tableName) === 0) {
                if ($this->reset_auto_increment($db, $tableName)) {
                    $reset[] = $label;
                }
            } else {
                $skipped[] = $label;
            }
        }

        $parts = [];
        if ($reset !== []) {
            $parts[] = 'ID-Zähler zurückgesetzt für: ' . implode(', ', $reset) . '.';
        }
        if ($skipped !== []) {
            $parts[] = 'Nicht zurückgesetzt (noch Einträge vorhanden): ' . implode(', ', $skipped) . '.';
        }

        return implode(' ', $parts);
    }

    /**
     * @return array{post_ids:array<int,int>,page_ids:array<int,int>,stale_mappings:int}
     */
    private function get_imported_content_targets(\CMS\Database $db, string $p): array
    {
        $postIds = [];
        $pageIds = [];
        $staleMappings = 0;

        if (!$this->has_table($db, $p . 'import_items')) {
            return [
                'post_ids' => [],
                'page_ids' => [],
                'stale_mappings' => 0,
            ];
        }

        $hasTargetCreatedColumn = $this->has_column($db, $p . 'import_items', 'target_created');
        $targetCreatedFilter = $hasTargetCreatedColumn ? ' AND target_created = 1' : '';

        $rows = $db->get_results(
            "SELECT target_type, target_id FROM {$p}import_items WHERE target_type IN ('post', 'page') AND target_id IS NOT NULL{$targetCreatedFilter}"
        ) ?? [];

        if ($rows === [] && $hasTargetCreatedColumn) {
            $legacyRows = $db->get_results(
                "SELECT target_type, target_id FROM {$p}import_items WHERE target_type IN ('post', 'page') AND target_id IS NOT NULL"
            ) ?? [];

            if ($legacyRows !== []) {
                $rows = $legacyRows;
                $targetCreatedFilter = '';
            }
        }

        if ($rows === []) {
            $this->backfill_import_mappings_from_sources($db, $p);

            $rows = $db->get_results(
                "SELECT target_type, target_id FROM {$p}import_items WHERE target_type IN ('post', 'page') AND target_id IS NOT NULL"
            ) ?? [];

            if ($rows !== []) {
                $targetCreatedFilter = '';
            }
        }

        foreach ($rows as $row) {
            $targetType = (string) ($row->target_type ?? '');
            $targetId = (int) ($row->target_id ?? 0);
            if ($targetId <= 0) {
                continue;
            }

            if ($targetType === 'post') {
                $postIds[$targetId] = $targetId;
            } elseif ($targetType === 'page') {
                $pageIds[$targetId] = $targetId;
            }
        }

        $postIds = $this->filter_existing_ids($db, $p . 'posts', array_values($postIds));
        $pageIds = $this->filter_existing_ids($db, $p . 'pages', array_values($pageIds));

        $postMappingCount = (int) ($db->get_var("SELECT COUNT(*) FROM {$p}import_items WHERE target_type = 'post' AND target_id IS NOT NULL{$targetCreatedFilter}") ?? 0);
        $pageMappingCount = (int) ($db->get_var("SELECT COUNT(*) FROM {$p}import_items WHERE target_type = 'page' AND target_id IS NOT NULL{$targetCreatedFilter}") ?? 0);

        $existingPostMappingCount = $postIds === [] ? 0 : (int) ($db->get_var(
            'SELECT COUNT(*) FROM ' . $p . 'import_items WHERE target_type = ? AND target_id IN (' . implode(',', array_fill(0, count($postIds), '?')) . ')',
            array_merge(['post'], $postIds)
        ) ?? 0);

        $existingPageMappingCount = $pageIds === [] ? 0 : (int) ($db->get_var(
            'SELECT COUNT(*) FROM ' . $p . 'import_items WHERE target_type = ? AND target_id IN (' . implode(',', array_fill(0, count($pageIds), '?')) . ')',
            array_merge(['page'], $pageIds)
        ) ?? 0);

        $staleMappings = max(0, ($postMappingCount - $existingPostMappingCount) + ($pageMappingCount - $existingPageMappingCount));

        return [
            'post_ids' => $postIds,
            'page_ids' => $pageIds,
            'stale_mappings' => $staleMappings,
        ];
    }

    private function backfill_import_mappings_from_sources(\CMS\Database $db, string $p): void
    {
        if (!$this->has_table($db, $p . 'import_log') || !$this->has_table($db, $p . 'import_items')) {
            return;
        }

        $parser = $this->resolve_importer_xml_parser();
        if ($parser === null || !method_exists($parser, 'parse')) {
            return;
        }

        $logs = $db->get_results("SELECT id, filename FROM {$p}import_log ORDER BY id DESC") ?? [];
        if ($logs === []) {
            return;
        }

        $sourceFiles = $this->get_importer_source_files();
        if ($sourceFiles === []) {
            return;
        }

        $processed = [];
        foreach ($logs as $log) {
            $filename = trim((string) ($log->filename ?? ''));
            if ($filename === '' || isset($processed[$filename]) || !isset($sourceFiles[$filename])) {
                continue;
            }

            $processed[$filename] = true;
            $parsed = $parser->parse($sourceFiles[$filename]);
            if (!is_array($parsed) || !empty($parsed['errors'])) {
                continue;
            }

            foreach (($parsed['posts'] ?? []) as $item) {
                if (is_array($item)) {
                    $this->backfill_imported_content_mapping($db, $p, $item, 'post', 'post', (int) ($log->id ?? 0));
                }
            }

            foreach (($parsed['pages'] ?? []) as $item) {
                if (is_array($item)) {
                    $this->backfill_imported_content_mapping($db, $p, $item, 'page', 'page', (int) ($log->id ?? 0));
                }
            }
        }
    }

    private function resolve_importer_xml_parser(): ?object
    {
        if (class_exists('CMS_Importer_XML_Parser')) {
            return new \CMS_Importer_XML_Parser();
        }

        $pluginDir = $this->resolve_importer_plugin_dir();
        if ($pluginDir === '' || !is_file($pluginDir . 'includes/class-xml-parser.php')) {
            return null;
        }

        require_once $pluginDir . 'includes/class-xml-parser.php';
        return class_exists('CMS_Importer_XML_Parser') ? new \CMS_Importer_XML_Parser() : null;
    }

    private function resolve_importer_plugin_dir(): string
    {
        $candidates = [];

        if (defined('CMS_IMPORTER_PLUGIN_DIR')) {
            $candidates[] = (string) CMS_IMPORTER_PLUGIN_DIR;
        }

        if (defined('PLUGIN_PATH')) {
            $candidates[] = rtrim((string) PLUGIN_PATH, '/\\') . '/cms-importer/';
        }

        $candidates[] = dirname(__DIR__) . '/cms-importer/';

        foreach ($candidates as $candidate) {
            $normalized = rtrim(str_replace('\\', '/', $candidate), '/') . '/';
            if (is_dir($normalized)) {
                return $normalized;
            }
        }

        return '';
    }

    /**
     * @return array<string,string>
     */
    private function get_importer_source_files(): array
    {
        $files = [];

        $pluginDir = $this->resolve_importer_plugin_dir();
        if ($pluginDir !== '' && is_dir($pluginDir . 'wp_import_files/')) {
            foreach (['*.xml', '*.json'] as $pattern) {
                foreach (glob($pluginDir . 'wp_import_files/' . $pattern) ?: [] as $path) {
                    $files[basename($path)] = str_replace('\\', '/', $path);
                }
            }
        }

        if ($pluginDir !== '' && is_dir($pluginDir . 'wp_import/')) {
            foreach (['*.xml', '*.json'] as $pattern) {
                foreach (glob($pluginDir . 'wp_import/' . $pattern) ?: [] as $path) {
                    $files[basename($path)] = str_replace('\\', '/', $path);
                }
            }
        }

        $importDir = $this->get_import_dir();
        if ($importDir !== '' && is_dir($importDir)) {
            foreach (['*.xml', '*.json'] as $pattern) {
                foreach (glob($importDir . $pattern) ?: [] as $path) {
                    $files[basename($path)] = str_replace('\\', '/', $path);
                }
            }
        }

        return $files;
    }

    private function backfill_imported_content_mapping(\CMS\Database $db, string $p, array $item, string $sourceType, string $targetType, int $logId): bool
    {
        $sourceWpId = (int) ($item['wp_id'] ?? 0);
        if ($sourceWpId <= 0) {
            return false;
        }

        $existing = (int) ($db->get_var(
            "SELECT id FROM {$p}import_items WHERE source_type = ? AND source_wp_id = ? AND target_type = ? LIMIT 1",
            [$sourceType, $sourceWpId, $targetType]
        ) ?? 0);
        if ($existing > 0) {
            return true;
        }

        $sourceReference = $this->resolve_cleanup_source_reference($item);
        $desiredSlug = $this->resolve_cleanup_desired_slug($item, $sourceReference);

        $match = $this->find_existing_content_match($db, $p, $targetType, $desiredSlug, (string) ($item['title'] ?? ''), (string) ($item['date'] ?? ''));
        if ($match === null) {
            return false;
        }

        $targetUrl = $targetType === 'post'
            ? (class_exists('\\CMS\\Services\\PermalinkService')
                ? \CMS\Services\PermalinkService::getInstance()->buildPostUrlFromValues((string) ($match['slug'] ?? ''), (string) ($match['published_at'] ?? ''), (string) ($match['created_at'] ?? ''))
                : (defined('SITE_URL') ? rtrim((string) SITE_URL, '/') . '/blog/' . ltrim((string) ($match['slug'] ?? ''), '/') : null))
            : (defined('SITE_URL') ? rtrim((string) SITE_URL, '/') . '/' . ltrim((string) ($match['slug'] ?? ''), '/') : null);

        $inserted = $db->insert('import_items', [
            'log_id' => $logId > 0 ? $logId : null,
            'source_type' => $sourceType,
            'source_wp_id' => $sourceWpId,
            'source_reference' => $sourceReference !== '' ? $sourceReference : null,
            'source_slug' => (string) ($item['slug'] ?? ''),
            'source_url' => (string) ($item['link'] ?? ''),
            'target_type' => $targetType,
            'target_id' => (int) ($match['id'] ?? 0),
            'target_created' => 1,
            'target_slug' => (string) ($match['slug'] ?? ''),
            'target_url' => $targetUrl,
        ]);

        return $inserted !== false;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function find_existing_content_match(\CMS\Database $db, string $p, string $targetType, string $desiredSlug, string $title, string $sourceDate): ?array
    {
        $table = $targetType === 'post' ? 'posts' : 'pages';
        $hasTitleEn = $this->has_column($db, $p . $table, 'title_en');

        if ($desiredSlug !== '' && $this->has_table($db, $p . $table)) {
            $slugCondition = 'slug = ?';
            $params = [$desiredSlug];

            if ($this->has_column($db, $p . $table, 'slug_en')) {
                $slugCondition .= ' OR slug_en = ?';
                $params[] = $desiredSlug;
            }

            $row = $db->get_row(
                "SELECT id, slug, created_at, published_at FROM {$p}{$table} WHERE {$slugCondition} LIMIT 1",
                $params
            );
            if ($row !== null) {
                return (array) $row;
            }
        }

        $title = trim($title);
        if ($title === '' || !$this->has_table($db, $p . $table)) {
            return null;
        }

        if ($hasTitleEn) {
            $rows = $db->get_results(
                "SELECT id, slug, created_at, published_at FROM {$p}{$table} WHERE title = ? OR title_en = ? ORDER BY id ASC LIMIT 10",
                [$title, $title]
            ) ?: [];
        } else {
            $rows = $db->get_results(
                "SELECT id, slug, created_at, published_at FROM {$p}{$table} WHERE title = ? ORDER BY id ASC LIMIT 10",
                [$title]
            ) ?: [];
        }

        if (count($rows) === 1) {
            return (array) $rows[0];
        }

        if ($sourceDate !== '') {
            $sourceDay = substr($sourceDate, 0, 10);
            $sameDay = [];
            foreach ($rows as $row) {
                $candidateDate = $targetType === 'post'
                    ? (string) ($row->published_at ?? $row->created_at ?? '')
                    : (string) ($row->created_at ?? '');
                if ($candidateDate !== '' && substr($candidateDate, 0, 10) === $sourceDay) {
                    $sameDay[] = (array) $row;
                }
            }

            if (count($sameDay) === 1) {
                return $sameDay[0];
            }
        }

        return null;
    }

    private function resolve_cleanup_desired_slug(array $item, string $sourceReference): string
    {
        $sourceReferenceSlug = $this->extract_cleanup_reference_slug($sourceReference);
        if ($sourceReferenceSlug !== '') {
            return $sourceReferenceSlug;
        }

        if (class_exists('\\CMS\\Services\\PermalinkService')) {
            return \CMS\Services\PermalinkService::resolveImportedSourceSlug(
                (string) ($item['slug'] ?? ''),
                (string) ($item['link'] ?? '')
            );
        }

        return trim((string) ($item['slug'] ?? ''));
    }

    private function resolve_cleanup_source_reference(array $item): string
    {
        foreach ([(string) ($item['guid'] ?? ''), (string) ($item['link'] ?? '')] as $candidate) {
            $reference = $this->normalize_cleanup_reference_from_url($candidate);
            if ($reference !== '') {
                return $reference;
            }
        }

        return $this->normalize_cleanup_reference_path((string) ($item['slug'] ?? ''));
    }

    private function normalize_cleanup_reference_from_url(string $url): string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($url === '') {
            return '';
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return $this->normalize_cleanup_reference_path($url);
        }

        return $this->normalize_cleanup_reference_path((string) parse_url($url, PHP_URL_PATH));
    }

    private function normalize_cleanup_reference_path(string $path): string
    {
        $path = trim(html_entity_decode($path, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $path = trim($path, "/ \t\n\r\0\x0B");
        if ($path === '') {
            return '';
        }

        $segments = array_values(array_filter(
            explode('/', $path),
            static fn(string $segment): bool => trim($segment) !== ''
        ));

        while ($segments !== [] && strtolower((string) ($segments[0] ?? '')) === 'en') {
            array_shift($segments);
        }

        while ($segments !== [] && strtolower((string) ($segments[count($segments) - 1] ?? '')) === 'en') {
            array_pop($segments);
        }

        $normalized = [];
        foreach ($segments as $segment) {
            $segment = trim(rawurldecode((string) $segment));
            if ($segment !== '') {
                $normalized[] = $segment;
            }
        }

        return implode('/', $normalized);
    }

    private function extract_cleanup_reference_slug(string $reference): string
    {
        $reference = trim($reference, '/');
        if ($reference === '') {
            return '';
        }

        $segments = array_values(array_filter(explode('/', $reference), static fn(string $segment): bool => trim($segment) !== ''));
        if ($segments === []) {
            return '';
        }

        return (string) end($segments);
    }

    /**
     * @param array<int,int> $ids
     * @return array<int,int>
     */
    private function filter_existing_ids(\CMS\Database $db, string $tableName, array $ids): array
    {
        if ($ids === [] || !$this->has_table($db, $tableName)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $results = $db->get_col("SELECT id FROM {$tableName} WHERE id IN ({$placeholders})", $ids);
        return array_map('intval', $results);
    }

    private function has_column(\CMS\Database $db, string $tableName, string $columnName): bool
    {
        if (!$this->has_table($db, $tableName)) {
            return false;
        }

        try {
            $stmt = $db->query("SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'");
            return $stmt instanceof \PDOStatement && (bool) $stmt->fetch();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param array<int,int> $ids
     */
    private function delete_rows_by_ids(\CMS\Database $db, string $tableName, string $column, array $ids): int
    {
        if ($ids === [] || !$this->has_table($db, $tableName)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM {$tableName} WHERE {$column} IN ({$placeholders})";
        $stmt = $db->execute($sql, $ids);
        return $stmt->rowCount();
    }

    /**
     * @param array<int,int> $ids
     */
    private function delete_rows_by_content_ids(\CMS\Database $db, string $tableName, string $contentType, array $ids): int
    {
        if ($ids === [] || !$this->has_table($db, $tableName)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$contentType], $ids);
        $stmt = $db->execute(
            "DELETE FROM {$tableName} WHERE content_type = ? AND content_id IN ({$placeholders})",
            $params
        );
        return $stmt->rowCount();
    }

    /**
     * @param array<int,int> $ids
     */
    private function delete_rows_by_target_ids(\CMS\Database $db, string $tableName, string $targetType, array $ids): int
    {
        if ($ids === [] || !$this->has_table($db, $tableName)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$targetType], $ids);
        $stmt = $db->execute(
            "DELETE FROM {$tableName} WHERE target_type = ? AND target_id IN ({$placeholders})",
            $params
        );
        return $stmt->rowCount();
    }

    private function delete_stale_import_mappings(\CMS\Database $db, string $p): int
    {
        if (!$this->has_table($db, $p . 'import_items')) {
            return 0;
        }

        $stmt = $db->execute(
            "DELETE ii FROM {$p}import_items ii
             LEFT JOIN {$p}posts p ON ii.target_type = 'post' AND ii.target_id = p.id
             LEFT JOIN {$p}pages pg ON ii.target_type = 'page' AND ii.target_id = pg.id
             WHERE ii.target_type IN ('post', 'page')
               AND ((ii.target_type = 'post' AND p.id IS NULL) OR (ii.target_type = 'page' AND pg.id IS NULL))"
        );

        return $stmt->rowCount();
    }

    /**
     * @param array<int,int> $ids
     */
    private function count_existing_ids(\CMS\Database $db, string $tableName, array $ids): int
    {
        if ($ids === [] || !$this->has_table($db, $tableName)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return (int) ($db->get_var("SELECT COUNT(*) FROM {$tableName} WHERE id IN ({$placeholders})", $ids) ?? 0);
    }

    private function count_report_files(): int
    {
        $reportDir = CMS_IMPORTER_PLUGIN_DIR . 'reports/';
        if (!is_dir($reportDir)) {
            return 0;
        }

        $files = glob($reportDir . '*.{md,html}', GLOB_BRACE);
        return is_array($files) ? count($files) : 0;
    }

    private function cleanup_report_files(\CMS\Database $db, string $p): int
    {
        $deleted = 0;
        $paths = [];

        if ($this->has_table($db, $p . 'import_log')) {
            $rows = $db->get_results("SELECT meta_report_path FROM {$p}import_log WHERE meta_report_path IS NOT NULL AND meta_report_path != ''") ?? [];
            foreach ($rows as $row) {
                $path = trim((string) ($row->meta_report_path ?? ''));
                if ($path === '') {
                    continue;
                }
                $paths[$path] = $path;
                $htmlPath = preg_replace('/\.md$/i', '.html', $path) ?? '';
                if ($htmlPath !== '') {
                    $paths[$htmlPath] = $htmlPath;
                }
            }
        }

        $reportDir = CMS_IMPORTER_PLUGIN_DIR . 'reports/';
        if (is_dir($reportDir)) {
            $globbed = glob($reportDir . '*.{md,html}', GLOB_BRACE);
            if (is_array($globbed)) {
                foreach ($globbed as $file) {
                    $paths[$file] = $file;
                }
            }
        }

        foreach ($paths as $path) {
            if (is_file($path) && @unlink($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    // ── Hilfsmethoden ──────────────────────────────────────────────────────────

    private function upload_error_message(int $code): string
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'Datei überschreitet php.ini upload_max_filesize.',
            UPLOAD_ERR_FORM_SIZE  => 'Datei überschreitet MAX_FILE_SIZE des Formulars.',
            UPLOAD_ERR_PARTIAL    => 'Datei wurde nur teilweise hochgeladen.',
            UPLOAD_ERR_NO_FILE    => 'Keine Datei wurde hochgeladen.',
            UPLOAD_ERR_NO_TMP_DIR => 'Temporäres Verzeichnis fehlt.',
            UPLOAD_ERR_CANT_WRITE => 'Datei konnte nicht auf die Festplatte geschrieben werden.',
            UPLOAD_ERR_EXTENSION  => 'Upload durch PHP-Extension abgebrochen.',
        ];
        return $messages[$code] ?? 'Unbekannter Upload-Fehler (Code: ' . $code . ')';
    }

    private function format_bytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 1) . ' MB';
    }

    private function is_valid_request_token(string $token, string $action): bool
    {
        if (!class_exists('CMS\Security')) {
            return true;
        }

        $security = CMS\Security::instance();
        if ($token === '') {
            return false;
        }

        if (method_exists($security, 'verifyPersistentToken')) {
            return $security->verifyPersistentToken($token, $action);
        }

        return $security->verifyNonce($token, $action);
    }

    private function looks_like_supported_import(string $file_path, array $parsed): bool
    {
        if (($parsed['source_format'] ?? '') === 'rank_math_json') {
            return (isset($parsed['redirects']) && is_array($parsed['redirects']))
                || (!empty($parsed['seo_settings']['settings']) && is_array($parsed['seo_settings']['settings']));
        }

        $wxrVersion = trim((string) ($parsed['site']['wxr_version'] ?? ''));
        if ($wxrVersion !== '') {
            return true;
        }

        $structuredCounts = [
            count($parsed['authors'] ?? []),
            count($parsed['attachments'] ?? []),
            count($parsed['posts'] ?? []),
            count($parsed['pages'] ?? []),
            count($parsed['tables'] ?? []),
            count($parsed['redirects'] ?? []),
            count($parsed['others'] ?? []),
        ];

        foreach ($structuredCounts as $count) {
            if ($count > 0) {
                return true;
            }
        }

        $contents = @file_get_contents($file_path);
        if ($contents === false) {
            return false;
        }

        $contents = ltrim($contents, "\xEF\xBB\xBF\x00\x09\x0A\x0D ");
        if ($contents === '') {
            return false;
        }

        if (str_contains($contents, '"redirections"')) {
            return true;
        }
        return str_contains($contents, 'wordpress.org/export/')
            || str_contains($contents, '<wp:wxr_version>')
            || str_contains($contents, 'xmlns:wp="http://wordpress.org/export/')
            || str_contains($contents, "xmlns:wp='http://wordpress.org/export/");
    }
}
