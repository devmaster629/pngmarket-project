<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>

<?php
  $path = __get('file');
  $path = explode('/', (string) $path);

  $plugin = @$path[0];
  $file = str_replace('.php', '', end($path));

  if (!function_exists('pngm_ua_render_sidebar')) {
    require_once dirname(__FILE__) . '/includes/account_ua.php';
  }
?>

<body id="user-custom" class="body-ua pngm-ua plugin-<?php echo osc_esc_html($plugin); ?> file-<?php echo osc_esc_html($file); ?>">
  <?php osc_current_web_theme_path('header.php'); ?>

  <div class="container primary pngm-ua-shell">
    <?php pngm_ua_render_sidebar(); ?>

    <div id="user-main" class="pngm-ua-main pngm-ua-panel">
      <div class="user-custom-box">
        <?php osc_render_file(); ?>
      </div>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
