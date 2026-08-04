<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="index, follow" />
  <meta name="googlebot" content="index, follow" />
</head>

<body id="subdomain" class="subdomain-navigation sd-<?php echo osc_subdomain_type(); ?> <?php if(in_array(osc_subdomain_type(), array('country', 'language'))) { ?>sd-with-icon<?php } ?> has-footer">
  <?php osc_current_web_theme_path('header.php') ; ?>

  <section class="container">
    <div class="box">
      <div class="wrapper wrapper-flash flash2"><?php osc_show_flash_message(); ?></div>

      <div class="wrapper">
        <div class="m25"><?php _e('Join our community to buy and sell from each other everyday around the world.', 'epsilon'); ?></div>
        <div><strong><?php _e('Please select preferred site:', 'epsilon'); ?></strong></div>
        <?php echo osc_subdomain_links($with_images = true, $with_counts = true, $with_toplink = false, $limit = 1000, $min_item_count = 0); ?>
      </div>
    </div>
  </section>
  
  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>