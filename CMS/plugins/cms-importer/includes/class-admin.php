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

require_once __DIR__ . '/trait-admin-cleanup.php';

class CMS_Importer_Admin
{
    use CMS_Importer_Admin_Cleanup_Trait;

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
