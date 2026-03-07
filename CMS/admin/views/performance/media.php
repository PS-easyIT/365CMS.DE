<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$media = $data['media'] ?? [];
$library = $media['library'] ?? [];
$largestImages = $media['largest_images'] ?? [];
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
    <?php if (!empty($alert)): ?><div class="alert alert-<?php echo htmlspecialchars($alert['type'] ?? 'info'); ?> mb-4"><?php echo htmlspecialchars($alert['message'] ?? ''); ?></div><?php endif; ?>
    <?php require __DIR__ . '/subnav.php'; ?>

    <div class="row row-deck row-cards mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">Bibliothek</div><div class="h1 mb-0"><?php echo (int)($library['total_files'] ?? 0); ?></div><div class="text-secondary">Dateien</div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">Speicherbedarf</div><div class="h1 mb-0"><?php echo htmlspecialchars($formatBytes((int)($media['upload_size'] ?? 0))); ?></div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">Fehlende Alt-Texte</div><div class="h1 mb-0 text-warning"><?php echo (int)($library['missing_alt'] ?? 0); ?></div></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="subheader">WebP-Dateien</div><div class="h1 mb-0"><?php echo (int)($library['webp_files'] ?? 0); ?></div></div></div></div>
    </div>

    <div class="card mb-4"><div class="card-header"><h3 class="card-title">Medien-Strategie</h3></div><div class="card-body"><form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="action" value="save_media_settings"><div class="row"><div class="col-md-4"><label class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="perf_lazy_loading" value="1" <?php echo ($settings['perf_lazy_loading'] ?? '0') === '1' ? 'checked' : ''; ?>><span class="form-check-label">Lazy Loading aktivieren</span></label></div><div class="col-md-4"><label class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="perf_webp_uploads" value="1" <?php echo ($settings['perf_webp_uploads'] ?? '0') === '1' ? 'checked' : ''; ?>><span class="form-check-label">WebP-Konvertierung bei Upload vorbereiten</span></label></div><div class="col-md-4"><label class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="perf_strip_exif" value="1" <?php echo ($settings['perf_strip_exif'] ?? '0') === '1' ? 'checked' : ''; ?>><span class="form-check-label">EXIF-Metadaten künftig entfernen</span></label></div></div><button type="submit" class="btn btn-primary">Medien-Einstellungen speichern</button></form></div></div>

    <div class="card"><div class="card-header"><h3 class="card-title">Größte Bilder</h3></div><div class="table-responsive"><table class="table table-vcenter card-table table-striped"><thead><tr><th>Datei</th><th>Größe</th><th>Dimensionen</th><th>Format</th></tr></thead><tbody><?php if (empty($largestImages)): ?><tr><td colspan="4" class="text-center text-secondary py-4">Keine Bilddaten gefunden.</td></tr><?php else: ?><?php foreach ($largestImages as $image): ?><tr><td class="text-break"><?php echo htmlspecialchars((string)$image['path']); ?></td><td><?php echo htmlspecialchars($formatBytes((int)$image['size'])); ?></td><td><?php echo (int)$image['width']; ?> × <?php echo (int)$image['height']; ?></td><td><span class="badge bg-<?php echo !empty($image['is_webp']) ? 'success' : 'secondary'; ?>-lt"><?php echo !empty($image['is_webp']) ? 'WebP' : 'Klassisch'; ?></span></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div></div>
</div></div>
