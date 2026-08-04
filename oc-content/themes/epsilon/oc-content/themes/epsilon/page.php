<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
</head>
<body id="page">
  <?php osc_current_web_theme_path('header.php') ; ?>
  <?php osc_reset_static_pages(); ?>

  <?php if(eps_banner('static_page_top') !== false) { ?>
    <div class="container banner-box<?php if(eps_is_demo()) { ?> is-demo<?php } ?>"><div class="inside"><?php echo eps_banner('static_page_top'); ?></div></div>
  <?php } ?>
    
  <div class="page-text container">
    <h1><?php echo osc_static_page_title(); ?></h1>
    <section class="text"><?php echo osc_static_page_text(); ?></section>
    <section class="bottom"><?php _e('Do you have more questions?', 'epsilon'); ?> <a href="<?php echo osc_contact_url(); ?>"><?php _e('Contact us', 'epsilon'); ?></a></div>
  </div>

  <?php if(eps_banner('static_page_bottom') !== false) { ?>
    <div class="container banner-box<?php if(eps_is_demo()) { ?> is-demo<?php } ?>"><div class="inside"><?php echo eps_banner('static_page_bottom'); ?></div></div>
  <?php } ?>
    
  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>