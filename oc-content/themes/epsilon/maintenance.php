<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
  <head>
    <?php osc_current_web_theme_path('head.php') ; ?>
    <meta name="robots" content="noindex, nofollow" />
    <meta name="googlebot" content="noindex, nofollow" />
  </head>

  <body id="maintenanc">
    <?php osc_goto_first_locale(); ?>

    <section class="content loc-error sec-default">
      <div class="inside">
        <div class="maintenance">
          <div class="logo">
            <a href="<?php echo osc_base_url(); ?>"><?php echo eps_logo(); ?></a>
          </div>
          
          <h1><?php _e('Maintenance', 'epsilon'); ?></h1>
          <h2><?php _e('OOOPS! We are sorry, page is undergoing maintenance.', 'epsilon'); ?></h2>
          <h3><?php _e('Please come back later. Thank you!', 'epsilon'); ?></h3>
        </div>
      </div>
    </section>
  </body>
</html>