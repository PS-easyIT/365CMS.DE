<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
if (!defined('CMS_ADMIN_PERFORMANCE_VIEW')) exit;

$media = $data['media'] ?? [];
$library = $media['library'] ?? [];
$largestImages = $media['largest_images'] ?? [];
$conversion = $media['conversion'] ?? [];
$conversionCandidates = $conversion['candidates'] ?? [];
$settings = $data['settings'] ?? [];
$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2, ',', '.') . ' KB';
    return $bytes . ' B';
};
?>
<div class="page-header d-print-none"><div class="container-xl"><div class="row g-2 align-items-center"><div class="col"><div class="page-pretitle">Performance</div><h2 class="page-title">Medien-Optimierung</h2><div class="text-secondary mt-1">Bildbibliothek, Alt-Texte, Dateigrößen und WebP-/EXIF-Strategie im Blick.</div></div></div></div></div>
<div class="page-body"><div class="container-xl">
    <?php $alertData = $alert; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?>
    <?php require __DIR__ . '/subnav.php'; ?>

    <div class="row row-deck row-cards mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">Bibliothek</div><div class="h1 mb-0"><?php echo (int)($library['total_files'] ?? 0); ?></div><div class="text-secondary">Dateien</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">Speicherbedarf</div><div class="h1 mb-0"><?php echo htmlspecialchars($formatBytes((int)($media['upload_size'] ?? 0))); ?></div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">Fehlende Alt-Texte</div><div class="h1 mb-0 text-warning"><?php echo (int)($library['missing_alt'] ?? 0); ?></div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">WebP-Dateien</div><div class="h1 mb-0"><?php echo (int)($library['webp_files'] ?? 0); ?></div></div></div></div>
    </div>

    <div class="card mb-4"><div class="card-header"><h3 class="card-title">Medien-Strategie</h3></div><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="action" value="save_media_settings"><div class="row"><div class="col-md-4"><label class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="perf_lazy_loading" value="1" <?php echo ($settings['perf_lazy_loading'] ?? '0') === '1' ? 'checked' : ''; ?>><span class="form-check-label">Lazy Loading für CMS-Medien aktivieren</span></label></div><div class="col-md-4"><label class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="perf_webp_uploads" value="1" <?php echo ($settings['perf_webp_uploads'] ?? '0') === '1' ? 'checked' : ''; ?>><span class="form-check-label">WebP-Begleitdateien bei Upload erzeugen</span></label></div><div class="col-md-4"><label class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="perf_strip_exif" value="1" <?php echo ($settings['perf_strip_exif'] ?? '0') === '1' ? 'checked' : ''; ?>><span class="form-check-label">EXIF-Metadaten beim Upload entfernen</span></label></div></div><div class="form-text mb-3">Diese Optionen werden mit den Medien-Einstellungen synchronisiert und wirken auf neue Uploads sowie Editor.js-Ausgaben.</div><button type="submit" class="btn btn-primary">Medien-Einstellungen speichern</button></form></div></div>

    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div>
                <h3 class="card-title">WebP-Massenkonvertierung</h3>
                <div class="text-secondary small">Konvertiert geeignete Bilder, zeigt die Ersparnis und ersetzt bekannte Referenzen automatisch. Vor Live-Läufen Backup prüfen.</div>
            </div>
            <form method="post" class="m-0" data-confirm-title="WebP-Massenkonvertierung starten" data-confirm-message="Wirklich alle geeigneten Bilder in WebP konvertieren? Erfolgreich umgewandelte Originaldateien können ersetzt und bekannte Referenzen automatisch angepasst werden. Bitte vorher ein Backup sicherstellen." data-confirm-text="Konvertierung starten" data-confirm-class="btn-success" data-confirm-status-class="bg-success">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                <input type="hidden" name="action" value="convert_media_to_webp">
                <button type="submit" class="btn btn-success" <?php echo empty($conversion['supported']) || empty($conversion['convertible_files']) ? 'disabled' : ''; ?>>Alle Bilder zu WebP konvertieren</button>
            </form>
        </div>
        <div class="card-body">
            <div class="row row-cards mb-3">
                <div class="col-md-4"><div class="card card-sm"><div class="card-body"><div class="subheader">Konvertierbare Bilder</div><div class="h2 mb-0"><?php echo (int)($conversion['convertible_files'] ?? 0); ?></div></div></div></div>
                <div class="col-md-4"><div class="card card-sm"><div class="card-body"><div class="subheader">Aktuelles Dateivolumen</div><div class="h2 mb-0"><?php echo htmlspecialchars($formatBytes((int)($conversion['convertible_bytes'] ?? 0))); ?></div></div></div></div>
                <div class="col-md-4"><div class="card card-sm"><div class="card-body"><div class="subheader">Server-Support</div><div class="h2 mb-0 <?php echo !empty($conversion['supported']) ? 'text-success' : 'text-danger'; ?>"><?php echo !empty($conversion['supported']) ? 'WebP bereit' : 'Nicht verfügbar'; ?></div></div></div></div>
            </div>

            <?php if (empty($conversion['supported'])): ?>
                <div class="alert alert-warning mb-0">Auf diesem Server fehlt GD mit WebP-Support. Ohne das bleibt die WebP-Rakete leider am Boden.</div>
            <?php elseif (empty($conversionCandidates)): ?>
                <div class="alert alert-secondary mb-0">Keine JPG-, PNG- oder GIF-Dateien gefunden, die aktuell als Kandidaten für WebP infrage kommen.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-striped">
                        <thead><tr><th>Datei</th><th>Format</th><th>Größe</th></tr></thead>
                        <tbody>
                        <?php foreach ($conversionCandidates as $candidate): ?>
                            <tr>
                                <td class="text-break"><?php echo htmlspecialchars((string)($candidate['path'] ?? '')); ?></td>
                                <td><span class="badge bg-secondary-lt"><?php echo htmlspecialchars(strtoupper((string)($candidate['extension'] ?? ''))); ?></span></td>
                                <td><?php echo htmlspecialchars($formatBytes((int)($candidate['size'] ?? 0))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card"><div class="card-header"><h3 class="card-title">Größte Bilder</h3></div><div class="table-responsive"><table class="table table-vcenter card-table table-striped"><thead><tr><th>Datei</th><th>Größe</th><th>Dimensionen</th><th>Format</th></tr></thead><tbody><?php if (empty($largestImages)): ?><tr><td colspan="4" class="text-center text-secondary py-4">Keine Bilddaten gefunden.</td></tr><?php else: ?><?php foreach ($largestImages as $image): ?><tr><td class="text-break"><?php echo htmlspecialchars((string)$image['path']); ?></td><td><?php echo htmlspecialchars($formatBytes((int)$image['size'])); ?></td><td><?php echo (int)$image['width']; ?> × <?php echo (int)$image['height']; ?></td><td><span class="badge bg-<?php echo !empty($image['is_webp']) ? 'success' : 'secondary'; ?>-lt"><?php echo !empty($image['is_webp']) ? 'WebP' : 'Klassisch'; ?></span></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div></div>
</div></div>
