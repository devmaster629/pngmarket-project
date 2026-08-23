<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
  <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js'); ?>"></script>
</head>

<body id="user-login" class="pre-account login pngm-auth">
  <?php UserForm::js_validation(); ?>
  <?php osc_current_web_theme_path('header.php'); ?>

  <section class="container">
    <div class="box">
      <h1><?php _e('Sign-in to your account', 'epsilon'); ?></h1>

      <?php if (function_exists('pngm_render_social_login')) { pngm_render_social_login('login'); } ?>

      <a class="alt-action" href="<?php echo osc_register_account_url(); ?>"><?php _e('No account yet? Register a new account', 'epsilon'); ?> &#8594;</a>

      <form action="<?php echo osc_base_url(true); ?>" method="post" >
        <input type="hidden" name="page" value="login" />
        <input type="hidden" name="action" value="login_post" />

        <?php osc_run_hook('user_pre_login_form'); ?>

        <div class="row">
          <label for="email"><?php _e('E-mail', 'epsilon'); ?></label>
          <span class="input-box pngm-field-ico pngm-ico-mail"><?php UserForm::email_login_text(); ?></span>
        </div>

        <div class="row">
          <label for="password"><?php _e('Password', 'epsilon'); ?></label>
          <span class="input-box">
            <?php UserForm::password_login_text(); ?>
            <a href="#" class="toggle-pass" title="<?php echo osc_esc_html(__('Show/hide password', 'epsilon')); ?>"><i class="fa fa-eye-slash"></i></a>
          </span>
        </div>

        <div class="input-box-check">
          <?php UserForm::rememberme_login_checkbox();?>
          <label for="remember"><?php _e('Remember me', 'epsilon'); ?></label>
        </div>

        <div class="user-reg-hook"><?php osc_run_hook('user_login_form'); ?></div>

        <div class="row fr">
        </div>

        <?php eps_show_recaptcha('login'); ?>

        <button type="submit" class="btn"><?php _e('Log in', 'epsilon');?></button>

        <a class="alt-action2" href="<?php echo osc_recover_user_password_url(); ?>"><?php _e('I forgot my password', 'epsilon'); ?></a>
      </form>
    </div>
  </section>

  <?php osc_current_web_theme_path('footer.php'); ?>

  <script type="text/javascript">
    $(document).ready(function(){
      $('input[name="email"]').attr('placeholder', '<?php echo osc_esc_js(__('your.email@dot.com', 'epsilon')); ?>').attr('required', true);
      $('input[name="password"]').attr('placeholder', '<?php echo osc_esc_js(__('YourPass123!', 'epsilon')); ?>').attr('required', true);
    });
  </script>
</body>
</html>
