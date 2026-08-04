<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="index, follow" />
  <meta name="googlebot" content="index, follow" />
</head>

<?php
  $path = __get('file'); 
  $path = explode('/', $path);

  $plugin = @$path[0];
  $file = str_replace('.php', '', end($path));
?>

<body id="custom" class="plugin-<?php echo $plugin; ?> file-<?php echo $file; ?>">
  <?php osc_current_web_theme_path('header.php'); ?>
  
  <div class="container primary"><?php osc_render_file(); ?></div>
  
  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>