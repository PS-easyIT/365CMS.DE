<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Media – Einstellungen View
 *
 * Erwartet: $data (aus MediaModule::getSettingsData())
 *           $alert, $csrfToken
 */

$settings  = $data['settings'] ?? [];
$diskUsage = $data['diskUsage'] ?? [];

$allTypes = ['jpg','jpeg','png','gif','webp','bmp','ico','pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv','zip','rar','7z','tar','gz','mp4','webm','ogg','mov','mp3','wav','aac','flac'];

// Defaults
$s = array_merge([
    'max_upload_size'         => '64M',
    'allowed_types'           => $allTypes,
    'organize_month_year'     => true,
    'sanitize_filename'       => true,
    'unique_filename'         => true,
    'lowercase_filename'      => false,
    'auto_webp'               => true,
    'strip_exif'              => true,
    'jpeg_quality'            => 85,
    'max_width'               => 2560,
    'max_height'              => 2560,
    'generate_thumbnails'     => false,
    'thumbnail_small_w'       => 150,
    'thumbnail_small_h'       => 150,
    'thumbnail_medium_w'      => 300,
    'thumbnail_medium_h'      => 300,
    'thumbnail_large_w'       => 1024,
    'thumbnail_large_h'       => 1024,
    'thumbnail_banner_w'      => 1200,
    'thumbnail_banner_h'      => 400,
    'block_dangerous_types'   => true,
    'validate_image_content'  => true,
    'require_login_for_upload'=> true,
    'protect_uploads_dir'     => true,
    'member_uploads_enabled'  => false,
    'member_max_upload_size'  => '5M',
    'member_allowed_types'    => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'csv'],
    'member_delete_own'       => false,
], $settings);
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Medienverwaltung</div>
                <h2 class="page-title">Einstellungen</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($alert)): ?>
            <?php $alertData = $alert; $alertMarginClass = 'mb-3'; require __DIR__ . '/../partials/flash-alert.php'; ?>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save_settings">

            <div class="row">
                <!-- Hauptbereich -->
                <div class="col-lg-8">

                    <!-- Upload-Einstellungen -->
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Upload-Einstellungen</h3></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="maxUploadSize">Maximale Upload-Größe (MB)</label>
                                <input type="number" class="form-control" id="maxUploadSize" name="max_upload_size" value="<?php echo (int)$s['max_upload_size']; ?>" min="1" max="256" step="1">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Erlaubte Dateitypen</label>
                                <div class="form-hint mb-2">SVG-Uploads sind gemäß Audit aus Sicherheitsgründen vollständig deaktiviert.</div>
                                <div class="row">
                                    <?php
                                    $active = (array)$s['allowed_types'];
                                    foreach ($allTypes as $type): ?>
                                        <div class="col-4 col-sm-3 col-md-2 mb-1">
                                            <label class="form-check">
                                                <input type="checkbox" class="form-check-input" name="allowed_types[]" value="<?php echo $type; ?>" <?php echo in_array($type, $active) ? 'checked' : ''; ?>>
                                                <span class="form-check-label">.<?php echo $type; ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <label class="form-check form-switch mb-2">
                                        <input type="checkbox" class="form-check-input" name="organize_month_year" value="1" <?php echo !empty($s['organize_month_year']) ? 'checked' : ''; ?>>
                                        <span class="form-check-label">Nach Monat/Jahr organisieren</span>
                                    </label>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-check form-switch mb-2">
                                        <input type="checkbox" class="form-check-input" name="sanitize_filename" value="1" <?php echo !empty($s['sanitize_filename']) ? 'checked' : ''; ?>>
                                        <span class="form-check-label">Dateinamen bereinigen</span>
                                    </label>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-check form-switch mb-2">
                                        <input type="checkbox" class="form-check-input" name="unique_filename" value="1" <?php echo !empty($s['unique_filename']) ? 'checked' : ''; ?>>
                                        <span class="form-check-label">Eindeutige Dateinamen</span>
                                    </label>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-check form-switch mb-2">
                                        <input type="checkbox" class="form-check-input" name="lowercase_filename" value="1" <?php echo !empty($s['lowercase_filename']) ? 'checked' : ''; ?>>
                                        <span class="form-check-label">Dateinamen in Kleinbuchstaben</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bild-Verarbeitung -->
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Bild-Verarbeitung</h3></div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <label class="form-check form-switch mb-2">
                                        <input type="checkbox" class="form-check-input" name="auto_webp" value="1" <?php echo !empty($s['auto_webp']) ? 'checked' : ''; ?>>
                                        <span class="form-check-label">Automatisch zu WebP konvertieren</span>
                                    </label>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-check form-switch mb-2">
                                        <input type="checkbox" class="form-check-input" name="strip_exif" value="1" <?php echo !empty($s['strip_exif']) ? 'checked' : ''; ?>>
                                        <span class="form-check-label">EXIF-Daten entfernen</span>
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4 mb-3">
                                    <label class="form-label" for="jpegQuality">JPEG-Qualität (%)</label>
                                    <input type="number" class="form-control" id="jpegQuality" name="jpeg_quality" value="<?php echo (int)$s['jpeg_quality']; ?>" min="30" max="100">
                                </div>
                                <div class="col-sm-4 mb-3">
                                    <label class="form-label" for="maxWidth">Max. Breite (px)</label>
                                    <input type="number" class="form-control" id="maxWidth" name="max_width" value="<?php echo (int)$s['max_width']; ?>" min="0">
                                    <small class="form-hint">0 = kein Limit</small>
                                </div>
                                <div class="col-sm-4 mb-3">
                                    <label class="form-label" for="maxHeight">Max. Höhe (px)</label>
                                    <input type="number" class="form-control" id="maxHeight" name="max_height" value="<?php echo (int)$s['max_height']; ?>" min="0">
                                    <small class="form-hint">0 = kein Limit</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Thumbnails -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Thumbnails</h3>
                            <div class="card-actions">
                                <label class="form-check form-switch m-0">
                                    <input type="checkbox" class="form-check-input" name="generate_thumbnails" value="1" <?php echo !empty($s['generate_thumbnails']) ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Aktiviert</span>
                                </label>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php
                                $sizes = [
                                    ['Small',  'thumbnail_small_w',  'thumbnail_small_h'],
                                    ['Medium', 'thumbnail_medium_w', 'thumbnail_medium_h'],
                                    ['Large',  'thumbnail_large_w',  'thumbnail_large_h'],
                                    ['Banner', 'thumbnail_banner_w', 'thumbnail_banner_h'],
                                ];
                                foreach ($sizes as [$label, $wKey, $hKey]):
                                ?>
                                    <div class="col-sm-6 col-md-3 mb-3">
                                        <label class="form-label fw-bold"><?php echo $label; ?></label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" name="<?php echo $wKey; ?>" value="<?php echo (int)$s[$wKey]; ?>" min="0" placeholder="B">
                                            <span class="input-group-text">×</span>
                                            <input type="number" class="form-control" name="<?php echo $hKey; ?>" value="<?php echo (int)$s[$hKey]; ?>" min="0" placeholder="H">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Member-Uploads -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Member-Uploads</h3>
                            <div class="card-actions">
                                <label class="form-check form-switch m-0">
                                    <input type="checkbox" class="form-check-input" name="member_uploads_enabled" value="1" <?php echo !empty($s['member_uploads_enabled']) ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Aktiviert</span>
                                </label>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label" for="memberMaxUpload">Max. Upload-Größe (MB)</label>
                                    <input type="number" class="form-control" id="memberMaxUpload" name="member_max_upload_size" value="<?php echo (int)$s['member_max_upload_size']; ?>" min="1" max="256">
                                </div>
                                <div class="col-sm-6 d-flex align-items-end">
                                    <label class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" name="member_delete_own" value="1" <?php echo !empty($s['member_delete_own']) ? 'checked' : ''; ?>>
                                        <span class="form-check-label">Eigene Dateien löschen erlauben</span>
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Erlaubte Dateitypen (Member)</label>
                                <div class="row">
                                    <?php
                                    $mActive = (array)$s['member_allowed_types'];
                                    $mTypes = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','zip'];
                                    foreach ($mTypes as $type): ?>
                                        <div class="col-4 col-sm-3 col-md-2 mb-1">
                                            <label class="form-check">
                                                <input type="checkbox" class="form-check-input" name="member_allowed_types[]" value="<?php echo $type; ?>" <?php echo in_array($type, $mActive) ? 'checked' : ''; ?>>
                                                <span class="form-check-label">.<?php echo $type; ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Sicherheit -->
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Sicherheit</h3></div>
                        <div class="card-body">
                            <label class="form-check form-switch mb-2">
                                <input type="checkbox" class="form-check-input" name="block_dangerous_types" value="1" <?php echo !empty($s['block_dangerous_types']) ? 'checked' : ''; ?>>
                                <span class="form-check-label">Gefährliche Dateitypen blockieren</span>
                            </label>
                            <label class="form-check form-switch mb-2">
                                <input type="checkbox" class="form-check-input" name="validate_image_content" value="1" <?php echo !empty($s['validate_image_content']) ? 'checked' : ''; ?>>
                                <span class="form-check-label">Bild-Inhalte validieren</span>
                            </label>
                            <label class="form-check form-switch mb-2">
                                <input type="checkbox" class="form-check-input" name="require_login_for_upload" value="1" <?php echo !empty($s['require_login_for_upload']) ? 'checked' : ''; ?>>
                                <span class="form-check-label">Login für Upload erforderlich</span>
                            </label>
                            <label class="form-check form-switch mb-0">
                                <input type="checkbox" class="form-check-input" name="protect_uploads_dir" value="1" <?php echo !empty($s['protect_uploads_dir']) ? 'checked' : ''; ?>>
                                <span class="form-check-label">Uploads-Verzeichnis schützen</span>
                            </label>
                        </div>
                    </div>

                    <!-- Speicher-Info -->
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Speicherplatz</h3></div>
                        <div class="card-body">
                            <div class="mb-2 d-flex justify-content-between">
                                <span>Dateien</span>
                                <strong><?php echo (int)($diskUsage['count'] ?? 0); ?></strong>
                            </div>
                            <div class="mb-2 d-flex justify-content-between">
                                <span>Speicher</span>
                                <strong><?php echo htmlspecialchars($diskUsage['formatted'] ?? '0 B'); ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Speichern -->
                    <div class="card">
                        <div class="card-body d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2"/><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M14 4l0 4l-6 0l0 -4"/></svg>
                                Einstellungen speichern
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>
