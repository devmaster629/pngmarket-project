<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
  <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js'); ?>"></script>
</head>

<body id="body-user-register" class="pre-account register">
  <?php UserForm::js_validation(); ?>
  <?php osc_current_web_theme_path('header.php'); ?>

  <section class="container">
    <div class="box">
      <h1><?php _e('Register a new account', 'epsilon'); ?></h1>

      <?php if(function_exists('fl_call_after_install') || function_exists('gc_login_button') || function_exists('fjl_login_button')) { ?>
        <div class="social">
          <?php if(function_exists('fl_call_after_install')) { ?>
            <a class="facebook" href="<?php echo facebook_login_link(); ?>" title="<?php echo osc_esc_html(__('Connect with Facebook', 'epsilon')); ?>">
              <i class="fab fa-facebook-square"></i>
              <span><?php _e('Continue with Facebook', 'epsilon'); ?></span>
            </a>
          <?php } ?>

          <?php if(function_exists('ggl_login_link')) { ?>
            <a class="google" href="<?php echo ggl_login_link(); ?>" title="<?php echo osc_esc_html(__('Connect with Google', 'epsilon')); ?>">
              <i class="fab fa-google"></i>
              <span><?php _e('Continue with Google', 'epsilon'); ?></span>
            </a>
          <?php } ?>
          
          <?php if(function_exists('fjl_login_button')) { ?>
            <a target="_top" href="javascript:void(0);" class="facebook fl-button fjl-button" onclick="fjlCheckLoginState();" title="<?php echo osc_esc_html(__('Connect with Facebook', 'epsilon')); ?>">
              <i class="fab fa-facebook-square"></i>
              <span><?php _e('Continue with Facebook', 'epsilon'); ?></span>
            </a>
          <?php } ?>
        </div>
      <?php } ?>

      <a class="alt-action" href="<?php echo osc_user_login_url(); ?>"><?php _e('Already have account? Login', 'epsilon'); ?> &#8594;</a>

      <form name="register" id="register" action="<?php echo osc_base_url(true); ?>" method="post" >
        <input type="hidden" name="page" value="register" />
        <input type="hidden" name="action" value="register_post" />

        <?php osc_run_hook('user_pre_register_form'); ?>
        
        <ul id="error_list"></ul>

        <div class="row nm">
          <label for="name"><?php _e('Name', 'epsilon'); ?> <span class="req">*</span></label>
          <div class="input-box"><?php UserForm::name_text(); ?></div>
        </div>
        
        <div class="row em">
          <label for="email"><?php _e('E-mail', 'epsilon'); ?> <span class="req">*</span></label>
          <div class="input-box"><?php UserForm::email_text(); ?></div>
        </div>
        
        <div class="row mb">
          <label for="phone"><?php _e('Mobile Phone', 'epsilon'); ?></label>
          <div class="input-box"><?php UserForm::mobile_text(osc_user()); ?></div>
        </div>
        
        <div class="row p1">
          <label for="password"><?php _e('Password', 'epsilon'); ?> <span class="req">*</span></label>
          <div class="input-box">
            <?php UserForm::password_text(); ?>
            <a href="#" class="toggle-pass" title="<?php echo osc_esc_html(__('Show/hide password', 'epsilon')); ?>"><i class="fa fa-eye-slash"></i></a>
          </div>
        </div>
        
        <div class="row p2">
          <label for="password"><?php _e('Re-type password', 'epsilon'); ?> <span class="req">*</span></label>
          <div class="input-box">
            <?php UserForm::check_password_text(); ?>
            <a href="#" class="toggle-pass" title="<?php echo osc_esc_html(__('Show/hide password', 'epsilon')); ?>"><i class="fa fa-eye-slash"></i></a>
          </div>
        </div>

        <div class="user-reg-hook"><?php osc_run_hook('user_register_form'); ?></div>

        <?php eps_show_recaptcha('register'); ?>

        <button type="submit" class="btn"><?php _e('Create account', 'epsilon'); ?></button>
      </form>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php'); ?>
  
  <script type="text/javascript">
    $(document).ready(function(){
      $('input[name="s_name"]').attr('placeholder', '<?php echo osc_esc_js(__('First name, Last name', 'epsilon')); ?>').attr('required', true);
      $('input[name="s_email"]').attr('placeholder', '<?php echo osc_esc_js(__('your.email@dot.com', 'epsilon')); ?>').attr('required', true).prop('type', 'email');
      $('input[name="s_phone_mobile"]').attr('placeholder', '<?php echo osc_esc_js(__('+XXX XXX XXX', 'epsilon')); ?>');
      $('input[name="s_password"]').attr('placeholder', '<?php echo osc_esc_js(__('YourPass123!', 'epsilon')); ?>').attr('required', true);
      $('input[name="s_password2"]').attr('placeholder', '<?php echo osc_esc_js(__('YourPass123!', 'epsilon')); ?>').attr('required', true);
    });
  </script>
</body>
</html>