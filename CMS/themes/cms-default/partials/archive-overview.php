<?php
/**
 * Meridian CMS Default – Archiv-Übersicht (Tags / Kategorien)
 *
 * Erwartete Variablen:
 *   $overviewItems – array, Einträge mit title, slug, description, count, url
 *   $overviewType  – string, 'tag' oder 'category'
 *   $query         – string, optionaler Filter (?q=)
 *
 * @package CMSv2\Themes\CmsDefault\Partials
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$ovItems = is_array($overviewItems ?? null) ? $overviewItems : [];
$ovIsTag = ($overviewType ?? 'tag') === 'tag';
$ovParam = $ovIsTag ? 'tag' : 'category';
$ovNoun  = $ovIsTag ? 'Tags' : 'Kategorien';
$ovQuery = is_string($query ?? null) ? trim($query) : '';
?>
<?php if (!empty($ovItems)): ?>
<div class="section-label"><h2>Alle <?php echo $ovNoun; ?></h2></div>

<div class="card-grid">
    <?php foreach ($ovItems as $ovItem):
        $ovItem  = (array) $ovItem;
        $ovSlug  = trim((string) ($ovItem['slug'] ?? ''));
        $ovTitle = trim((string) ($ovItem['title'] ?? ''));
        $ovTitle = $ovTitle !== '' ? $ovTitle : $ovSlug;
        if ($ovTitle === '') {
            continue;
        }
        $ovUrl   = trim((string) ($ovItem['url'] ?? ''));
        $ovUrl   = $ovUrl !== '' ? $ovUrl : SITE_URL . '/blog?' . $ovParam . '=' . urlencode($ovSlug !== '' ? $ovSlug : $ovTitle);
        $ovHref  = htmlspecialchars($ovUrl, ENT_QUOTES, 'UTF-8');
        $ovDesc  = trim((string) ($ovItem['description'] ?? ''));
        $ovCount = (int) ($ovItem['count'] ?? 0);
    ?>
    <div class="card">
        <div class="card-body">
            <h3><a href="<?php echo $ovHref; ?>"><?php echo htmlspecialchars($ovTitle, ENT_QUOTES, 'UTF-8'); ?></a></h3>
            <?php if ($ovDesc !== ''): ?>
            <p><?php echo htmlspecialchars($ovDesc, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <div class="card-footer" style="margin-top:auto;">
                <span class="cat-count"><?php echo number_format($ovCount); ?> Artikel</span>
                <a href="<?php echo $ovHref; ?>" class="read-link">Anzeigen →</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php else: ?>
<div class="empty-state" style="text-align:center;padding:4rem 2rem;">
    <p style="font-size:3rem;margin:0;"><?php echo $ovIsTag ? '🏷️' : '📭'; ?></p>
    <h2 style="margin:.75rem 0 .5rem;">Keine <?php echo $ovNoun; ?> gefunden</h2>
    <p style="color:var(--ink-muted);">
        <?php if ($ovQuery !== ''): ?>
        Für „<?php echo htmlspecialchars($ovQuery, ENT_QUOTES, 'UTF-8'); ?>“ wurden keine passenden <?php echo $ovNoun; ?> gefunden.
        <?php else: ?>
        Es wurden noch keine <?php echo $ovNoun; ?> mit veröffentlichten Artikeln angelegt.
        <?php endif; ?>
    </p>
    <a href="<?php echo SITE_URL; ?>/blog" class="btn-solid" style="display:inline-block;margin-top:1rem;">Alle Artikel anzeigen</a>
</div>
<?php endif; ?>
