<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
  <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js'); ?>"></script>
</head>

<body id="user-forgot" class="pre-account forgot">
  <?php UserForm::js_validation(); ?>
  <?php osc_current_web_theme_path('header.php'); ?>

  <section class="container">
    <div class="box">
      <h1><?php _e('Change password', 'epsilon'); ?></h1>

      <a class="alt-action" href="<?php echo osc_user_login_url(); ?>"><?php _e('You already know password? Login to your account', 'epsilon'); ?> &#8594;</a>

      <form action="<?php echo osc_base_url(true); ?>" method="post">
        <input type="hidden" name="page" value="login" />
        <input type="hidden" name="action" value="forgot_post" />
        <input type="hidden" name="userId" value="<?php echo osc_esc_html(Params::getParam('userId')); ?>" />
        <input type="hidden" name="code" value="<?php echo osc_esc_html(Params::getParam('code')); ?>" />
        
        <div class="row">
          <label for="new_email"><?php _e('New password', 'epsilon') ; ?></label>
          <span class="input-box"><input type="password" name="new_password" value="" />
        </div>
        
        <div class="row">
          <label for="new_email"><?php _e('Repeat password', 'epsilon') ; ?></label>
          <span class="input-box"><input type="password" name="new_password2" value="" />
        </div>
        
        <?php osc_run_hook('user_forgot_password_form'); ?>

        <button type="submit" class="btn"><?php _e('Submit', 'epsilon'); ?></button>
        
        <a class="alt-action2" href="<?php echo osc_register_account_url(); ?>"><?php _e('Create a new account', 'epsilon'); ?></a>
      </form>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>