<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
  <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js'); ?>"></script>
</head>

<body id="body-user-register" class="pre-account register pngm-auth pngm-auth-v2">
  <?php UserForm::js_validation(); ?>
  <?php osc_current_web_theme_path('header.php'); ?>

  <?php
    $terms_url = '#';
    $privacy_url = '#';
    if (class_exists('Page')) {
        $terms_page = Page::newInstance()->findByInternalName('terms');
        $privacy_page = Page::newInstance()->findByInternalName('privacy');
        if (is_array($terms_page) && function_exists('osc_static_page_url_from_page')) {
            $terms_url = osc_static_page_url_from_page($terms_page);
        }
        if (is_array($privacy_page) && function_exists('osc_static_page_url_from_page')) {
            $privacy_url = osc_static_page_url_from_page($privacy_page);
        }
    }
  ?>

  <section class="pngm-auth-wrap">
    <div class="pngm-auth-card">
      <aside class="pngm-auth-visual" aria-hidden="true">
        <img src="<?php echo osc_esc_html(osc_current_web_theme_url('images/auth-hero.png')); ?>?v=<?php echo rawurlencode(PNGM_CHILD_VERSION); ?>" alt="" width="720" height="960" decoding="async" />
      </aside>

      <div class="pngm-auth-panel">
        <div class="pngm-auth-box">
          <h1 class="pngm-auth-lead"><?php _e('Create your account', 'epsilon'); ?></h1>

          <?php if (function_exists('pngm_render_social_login')) { pngm_render_social_login('register', 'row'); } ?>

          <form name="register" id="register" action="<?php echo osc_base_url(true); ?>" method="post" class="pngm-auth-form">
            <input type="hidden" name="page" value="register" />
            <input type="hidden" name="action" value="register_post" />

            <?php osc_run_hook('user_pre_register_form'); ?>

            <ul id="error_list" class="pngm-auth-errors"></ul>

            <div class="pngm-auth-field row nm">
              <label for="s_name"><?php _e('Full name', 'epsilon'); ?></label>
              <div class="pngm-auth-control">
                <?php UserForm::name_text(); ?>
                <span class="pngm-auth-status" aria-hidden="true"></span>
              </div>
            </div>

            <div class="pngm-auth-field row em">
              <label for="s_email"><?php _e('Email', 'epsilon'); ?></label>
              <div class="pngm-auth-control">
                <?php UserForm::email_text(); ?>
                <span class="pngm-auth-status" aria-hidden="true"></span>
              </div>
            </div>

            <div class="pngm-auth-field row p1">
              <label for="s_password"><?php _e('Password', 'epsilon'); ?></label>
              <div class="pngm-auth-control has-toggle">
                <?php UserForm::password_text(); ?>
                <a href="#" class="toggle-pass" title="<?php echo osc_esc_html(__('Show/hide password', 'epsilon')); ?>"><i class="fa fa-eye-slash"></i></a>
                <span class="pngm-auth-status" aria-hidden="true"></span>
              </div>
            </div>

            <div class="pngm-auth-field row p2">
              <label for="s_password2"><?php _e('Confirm password', 'epsilon'); ?></label>
              <div class="pngm-auth-control has-toggle">
                <?php UserForm::check_password_text(); ?>
                <a href="#" class="toggle-pass" title="<?php echo osc_esc_html(__('Show/hide password', 'epsilon')); ?>"><i class="fa fa-eye-slash"></i></a>
                <span class="pngm-auth-status" aria-hidden="true"></span>
              </div>
            </div>

            <div class="user-reg-hook"><?php osc_run_hook('user_register_form'); ?></div>

            <div class="pngm-auth-captcha">
              <?php pngm_auth_show_recaptcha('register'); ?>
            </div>

            <?php if (function_exists('pngm_antispam_public_status')) {
                $pngm_limits = pngm_antispam_public_status();
                if (!empty($pngm_limits['registration']['label'])) {
                ?>
              <div class="pngm-auth-limits" id="pngm-auth-limits">
                <strong><?php _e('Abuse protection active', 'epsilon'); ?></strong>
                <ul>
                  <li><?php echo osc_esc_html($pngm_limits['registration']['label']); ?></li>
                </ul>
              </div>
            <?php }
            } ?>

            <label class="pngm-auth-check pngm-auth-terms">
              <input type="checkbox" name="pngm_terms" id="pngm_terms" value="1" required />
              <span>
                <?php
                  echo sprintf(
                      __('I agree to the %s and %s', 'epsilon'),
                      '<a href="' . osc_esc_html($terms_url) . '" target="_blank" rel="noopener">' . osc_esc_html(__('Terms of Service', 'epsilon')) . '</a>',
                      '<a href="' . osc_esc_html($privacy_url) . '" target="_blank" rel="noopener">' . osc_esc_html(__('Privacy Policy', 'epsilon')) . '</a>'
                  );
                ?>
              </span>
            </label>

            <button type="submit" class="btn pngm-auth-submit"><?php _e('Create account', 'epsilon'); ?></button>
          </form>

          <p class="pngm-auth-switch">
            <a href="<?php echo osc_user_login_url(); ?>"><?php _e('Already have an account? Log in', 'epsilon'); ?></a>
          </p>
        </div>
      </div>
    </div>
  </section>

  <?php osc_current_web_theme_path('footer.php'); ?>

  <script type="text/javascript">
  (function ($) {
    $(function () {
      $('input[name="s_name"]').attr('placeholder', '<?php echo osc_esc_js(__('First name, Last name', 'epsilon')); ?>').attr('required', true);
      $('input[name="s_email"]').attr('placeholder', '<?php echo osc_esc_js(__('your.email@dot.com', 'epsilon')); ?>').attr('required', true).prop('type', 'email');
      $('input[name="s_password"]').removeAttr('placeholder').attr('required', true).attr('minlength', 8);
      $('input[name="s_password2"]').removeAttr('placeholder').attr('required', true);

      function mark($input, ok) {
        var $field = $input.closest('.pngm-auth-field');
        if (!$field.length) return;
        $field.removeClass('is-ok is-error');
        if (!$input.val()) return;
        $field.addClass(ok ? 'is-ok' : 'is-error');
      }

      $('input[name="s_name"]').on('blur input', function () {
        mark($(this), $(this).val().trim().length >= 2);
      });
      $('input[name="s_email"]').on('blur input', function () {
        var el = this;
        mark($(this), el.checkValidity ? el.checkValidity() : true);
      });
      $('input[name="s_password"]').on('blur input', function () {
        mark($(this), $(this).val().length >= 8);
        var $p2 = $('input[name="s_password2"]');
        if ($p2.val()) mark($p2, $p2.val() === $(this).val());
      });
      $('input[name="s_password2"]').on('blur input', function () {
        mark($(this), $(this).val() !== '' && $(this).val() === $('input[name="s_password"]').val());
      });
    });
  })(jQuery);
  </script>
</body>
</html>
