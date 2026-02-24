<?php
/**
 * Meridian CMS Default – Footer Template
 *
 * @package CMSv2\Themes\CmsDefault
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$logoText       = meridian_setting('header', 'logo_text', defined('SITE_NAME') ? SITE_NAME : '365CMS');
$footerDesc     = meridian_setting('footer', 'footer_description', 'Aktuelle Themen, fundierte Analysen und persönliche Geschichten – täglich neu.');
$showSocial     = (bool) meridian_setting('footer', 'show_social_icons', true);
$col1Title      = meridian_setting('footer', 'col1_title', 'Rubriken');
$col2Title      = meridian_setting('footer', 'col2_title', 'Ressourcen');
$col3Title      = meridian_setting('footer', 'col3_title', 'Über');
$copyrightText  = meridian_copyright(meridian_setting('footer', 'copyright_text', ''));

// Brand-Spalte Beschreibung
$brandTitleEnabled = (bool) meridian_setting('footer', 'footer_brand_title_enabled', true);
$brandDescEnabled  = (bool) meridian_setting('footer', 'footer_brand_desc_enabled', true);
$brandCompact      = !$brandTitleEnabled || !$brandDescEnabled;
$brandEmpty        = !$brandTitleEnabled && !$brandDescEnabled;

// Footer-Karten Sichtbarkeit
$col1Enabled    = (bool) meridian_setting('footer', 'footer_col1_enabled', true);
$col2Enabled    = (bool) meridian_setting('footer', 'footer_col2_enabled', true);
$col3Enabled    = (bool) meridian_setting('footer', 'footer_col3_enabled', true);

// Footer-Menüs aus Menü-Verwaltung laden
$footerCol1Menu = $footerCol2Menu = $footerCol3Menu = $footerLegalMenu = [];
if (class_exists('\\CMS\\ThemeManager')) {
    $tm = \CMS\ThemeManager::instance();
    $footerCol1Menu  = $tm->getMenu('footer_col1') ?: [];
    $footerCol2Menu  = $tm->getMenu('footer_col2') ?: [];
    $footerCol3Menu  = $tm->getMenu('footer_col3') ?: [];
    $footerLegalMenu = $tm->getMenu('footer')      ?: [];
}

$socialLinks = [
    'twitter'   => meridian_setting('footer', 'social_twitter', ''),
    'instagram' => meridian_setting('footer', 'social_instagram', ''),
    'linkedin'  => meridian_setting('footer', 'social_linkedin', ''),
    'rss'       => SITE_URL . '/feed',
];
?>

</main><!-- Main Content Wrapper endet hier -->

<footer>
  <div class="footer-top-rule"></div>
  <div class="footer-main<?php
    $cls = '';
    if ($brandCompact) $cls .= ' footer-main--compact';
    if ($brandEmpty)   $cls .= ' footer-main--slim';
    echo $cls;
  ?>">

    <!-- Brand Column -->
    <div class="ft-brand<?php echo $brandCompact ? ' ft-brand--compact' : ''; ?>">
      <?php if ($brandTitleEnabled): ?>
      <a href="<?php echo SITE_URL; ?>/" class="site-logo-ft" aria-label="<?php echo htmlspecialchars($logoText); ?> – Startseite">
        <span class="lw"><?php echo htmlspecialchars($logoText); ?></span>
        <span class="ld"></span>
      </a>
      <?php endif; ?>
      <?php if ($brandDescEnabled && $footerDesc): ?>
      <p><?php echo htmlspecialchars($footerDesc); ?></p>
      <?php endif; ?>

      <?php if ($showSocial): ?>
      <div class="ft-socials">
        <?php if ($socialLinks['twitter']): ?>
        <a href="<?php echo htmlspecialchars($socialLinks['twitter']); ?>" target="_blank" rel="noopener" aria-label="Twitter">
             <svg viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
        </a>
        <?php endif; ?>
        <?php if ($socialLinks['instagram']): ?>
        <a href="<?php echo htmlspecialchars($socialLinks['instagram']); ?>" target="_blank" rel="noopener" aria-label="Instagram">
             <svg viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
        </a>
        <?php endif; ?>
        <?php if ($socialLinks['linkedin']): ?>
        <a href="<?php echo htmlspecialchars($socialLinks['linkedin']); ?>" target="_blank" rel="noopener" aria-label="LinkedIn">
             <svg viewBox="0 0 24 24"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
        </a>
        <?php endif; // linkedin
        $youtubeUrl = meridian_setting('footer', 'social_youtube', '');
        if ($youtubeUrl): ?>
        <a href="<?php echo htmlspecialchars($youtubeUrl); ?>" target="_blank" rel="noopener" aria-label="YouTube">
             <svg viewBox="0 0 24 24"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.54C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/></svg>
        </a>
        <?php endif; // youtube ?>
        <a href="<?php echo htmlspecialchars($socialLinks['rss']); ?>" target="_blank" rel="noopener" aria-label="RSS">
             <svg viewBox="0 0 24 24"><path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/></svg>
        </a>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($col1Enabled): ?>
    <!-- Footer Menü Karte 1 -->
    <div class="ft-col">
      <h4><?php echo htmlspecialchars($col1Title); ?></h4>
      <?php if (!empty($footerCol1Menu)): ?>
        <?php foreach ($footerCol1Menu as $item): ?>
          <?php
            $mUrl    = htmlspecialchars($item['url'] ?? $item['href'] ?? '#');
            $mLabel  = htmlspecialchars($item['label'] ?? $item['title'] ?? '');
            $mTarget = htmlspecialchars($item['target'] ?? '_self');
          ?>
          <a href="<?php echo $mUrl; ?>" target="<?php echo $mTarget; ?>"><?php echo $mLabel; ?></a>
        <?php endforeach; ?>
      <?php else: ?>
        <?php
          $cats = function_exists('meridian_get_categories') ? meridian_get_categories(6) : [];
          if (!empty($cats)) {
              foreach ($cats as $cat) {
                   echo '<a href="'. SITE_URL .'/blog?category='. urlencode($cat['slug'] ?? '') .'">' . htmlspecialchars($cat['name'] ?? '') . '</a>';
              }
          } else {
              echo '<a href="'. SITE_URL .'/blog">Alle Artikel</a>';
          }
        ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($col2Enabled): ?>
    <!-- Footer Menü Karte 2 -->
    <div class="ft-col">
      <h4><?php echo htmlspecialchars($col2Title); ?></h4>
      <?php if (!empty($footerCol2Menu)): ?>
        <?php foreach ($footerCol2Menu as $item): ?>
          <?php
            $mUrl    = htmlspecialchars($item['url'] ?? $item['href'] ?? '#');
            $mLabel  = htmlspecialchars($item['label'] ?? $item['title'] ?? '');
            $mTarget = htmlspecialchars($item['target'] ?? '_self');
          ?>
          <a href="<?php echo $mUrl; ?>" target="<?php echo $mTarget; ?>"><?php echo $mLabel; ?></a>
        <?php endforeach; ?>
      <?php else: ?>
        <a href="<?php echo SITE_URL; ?>/blog">Script-Bibliothek</a>
        <a href="<?php echo SITE_URL; ?>/blog">Tutorials</a>
        <a href="<?php echo SITE_URL; ?>/search">Suche</a>
        <a href="<?php echo SITE_URL; ?>/register">Newsletter</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($col3Enabled): ?>
    <!-- Footer Menü Karte 3 -->
    <div class="ft-col">
      <h4><?php echo htmlspecialchars($col3Title); ?></h4>
      <?php if (!empty($footerCol3Menu)): ?>
        <?php foreach ($footerCol3Menu as $item): ?>
          <?php
            $mUrl    = htmlspecialchars($item['url'] ?? $item['href'] ?? '#');
            $mLabel  = htmlspecialchars($item['label'] ?? $item['title'] ?? '');
            $mTarget = htmlspecialchars($item['target'] ?? '_self');
          ?>
          <a href="<?php echo $mUrl; ?>" target="<?php echo $mTarget; ?>"><?php echo $mLabel; ?></a>
        <?php endforeach; ?>
      <?php else: ?>
        <a href="<?php echo SITE_URL; ?>/about">Über uns</a>
        <a href="<?php echo SITE_URL; ?>/contact">Kontakt</a>
        <a href="<?php echo SITE_URL; ?>/impressum">Impressum</a>
        <a href="<?php echo SITE_URL; ?>/datenschutz">Datenschutz</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div>

  <div class="footer-bottom">
    <span class="copy"><?php echo $copyrightText; ?></span>
    <div class="footer-legal">
      <?php if (!empty($footerLegalMenu)): ?>
        <?php foreach ($footerLegalMenu as $item): ?>
          <?php
            $mUrl    = htmlspecialchars($item['url'] ?? $item['href'] ?? '#');
            $mLabel  = htmlspecialchars($item['label'] ?? $item['title'] ?? '');
            $mTarget = htmlspecialchars($item['target'] ?? '_self');
          ?>
          <a href="<?php echo $mUrl; ?>" target="<?php echo $mTarget; ?>"><?php echo $mLabel; ?></a>
        <?php endforeach; ?>
      <?php else: ?>
        <a href="<?php echo SITE_URL; ?>/impressum">Impressum</a>
        <a href="<?php echo SITE_URL; ?>/datenschutz">Datenschutz</a>
      <?php endif; ?>
    </div>
  </div>
</footer>

<script src="<?php echo SITE_URL; ?>/themes/cms-default/js/theme.js?v=<?php echo defined('MERIDIAN_THEME_VERSION') ? MERIDIAN_THEME_VERSION : '1.0.0'; ?>" defer></script>
<?php
// Scripts, Cookie-Banner und Custom Footer Code via Hook ausgeben
if (class_exists('\\CMS\\Hooks')) {
    \CMS\Hooks::doAction('before_footer');
}
?>

</body>
</html>
