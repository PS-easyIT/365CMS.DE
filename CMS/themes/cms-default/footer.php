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
$aboutLinks     = function_exists('meridian_footer_about_links') ? meridian_footer_about_links() : [];

$socialLinks = [
    'twitter'   => meridian_setting('footer', 'social_twitter', ''),
    'instagram' => meridian_setting('footer', 'social_instagram', ''),
    'linkedin'  => meridian_setting('footer', 'social_linkedin', ''),
    'rss'       => SITE_URL . '/feed',
];
?>

</main><!-- Main Content Wrapper endet hier -->

<?php
if (class_exists('\\CMS\\Hooks')) {
  \CMS\Hooks::doAction('before_footer');
}
?>

<footer class="site-footer">
  <div class="footer-top-rule"></div>
  <div class="footer-main">

    <!-- Brand Column -->
    <div class="ft-brand">
      <a href="<?php echo SITE_URL; ?>/" class="site-logo-ft" aria-label="<?php echo htmlspecialchars($logoText); ?> – Startseite">
        <span class="lw"><?php echo htmlspecialchars($logoText); ?></span>
        <span class="ld"></span>
      </a>
      <?php if ($footerDesc): ?>
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

    <!-- Themes Column -->
    <div class="ft-col">
      <h4><?php echo htmlspecialchars($col1Title); ?></h4>
      <?php
        $cats = function_exists('meridian_get_categories') ? meridian_get_categories(6) : [];
        if (!empty($cats)) {
            foreach ($cats as $cat) {
                 echo '<a href="'. SITE_URL .'/blog?category='. urlencode($cat['slug'] ?? '') .'">'. htmlspecialchars($cat['name'] ?? '') .'</a>';
            }
        } else {
            echo '<a href="'. SITE_URL .'/blog">Alle Artikel</a>';
        }
      ?>
    </div>

    <!-- Resources Column -->
    <div class="ft-col">
      <h4><?php echo htmlspecialchars($col2Title); ?></h4>
      <a href="<?php echo SITE_URL; ?>/blog">Script-Bibliothek</a>
      <a href="<?php echo SITE_URL; ?>/blog">Tutorials</a>
      <a href="<?php echo SITE_URL; ?>/search">Suche</a>
      <a href="<?php echo SITE_URL; ?>/register">Newsletter</a>
    </div>

    <!-- About Column -->
    <div class="ft-col">
      <h4><?php echo htmlspecialchars($col3Title); ?></h4>
      <?php foreach ($aboutLinks as $link): ?>
      <a href="<?php echo htmlspecialchars(rtrim((string)SITE_URL, '/') . '/' . ltrim((string)($link['url'] ?? ''), '/'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)($link['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a>
      <?php endforeach; ?>
    </div>

  </div>

  <div class="footer-bottom">
    <span class="copy"><?php echo $copyrightText; ?></span>
    <div class="footer-legal">
      <a href="<?php echo SITE_URL; ?>/impressum">Impressum</a>
      <a href="<?php echo SITE_URL; ?>/datenschutz">Datenschutz</a>
    </div>
  </div>
</footer>

<?php
if (class_exists('\\CMS\\Hooks')) {
  \CMS\Hooks::doAction('footer');
  \CMS\Hooks::doAction('body_end');
}
?>

</body>
</html>
