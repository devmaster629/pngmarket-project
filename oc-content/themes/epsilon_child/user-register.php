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

            <div class="pngm-auth-push" data-pngm-reg-push
              data-sw-url="<?php echo osc_esc_html(function_exists('pngm_webpush_sw_url') ? pngm_webpush_sw_url() : (osc_base_url() . 'sw.js')); ?>"
              data-vapid="<?php echo osc_esc_html(function_exists('pngm_webpush_public_key') ? pngm_webpush_public_key() : ''); ?>">
              <button type="button" class="pngm-auth-push-btn" data-pngm-reg-push-btn>
                <i class="fas fa-bell" aria-hidden="true"></i>
                <span data-pngm-reg-push-label><?php _e('Enable push notifications', 'epsilon'); ?></span>
              </button>
              <p class="pngm-auth-push-hint" data-pngm-reg-push-hint><?php _e('Get alerts for messages and listing updates. You can change this later.', 'epsilon'); ?></p>
            </div>

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
      var minLen = <?php echo (int) pngm_password_min_length(); ?>;
      var $form = $('form#register');
      var $pass = $('input[name="s_password"]');
      var $pass2 = $('input[name="s_password2"]');

      $('input[name="s_name"]').attr('placeholder', '<?php echo osc_esc_js(__('First name, Last name', 'epsilon')); ?>').attr('required', true);
      $('input[name="s_email"]').attr('placeholder', '<?php echo osc_esc_js(__('your.email@dot.com', 'epsilon')); ?>').attr('required', true).prop('type', 'email');
      $pass.removeAttr('placeholder').attr('required', true).attr('minlength', minLen);
      $pass2.removeAttr('placeholder').attr('required', true).attr('minlength', minLen);
      // See user-login.php: autocomplete="off" from the core form makes iOS
      // treat these as AutoFill fields and the keyboard can come up blank.
      $('input[name="s_email"]').attr('autocomplete', 'email');
      $pass.attr('autocomplete', 'new-password');
      $pass2.attr('autocomplete', 'new-password');

      if ($form.length && $.fn.validate && $form.data('validator')) {
        $form.validate().settings.rules.s_password = $.extend({}, $form.validate().settings.rules.s_password, {
          required: true,
          minlength: minLen
        });
        $form.validate().settings.rules.s_password2 = $.extend({}, $form.validate().settings.rules.s_password2, {
          required: true,
          minlength: minLen,
          equalTo: '#s_password'
        });
        $form.validate().settings.messages.s_password = $.extend({}, $form.validate().settings.messages.s_password, {
          minlength: '<?php echo osc_esc_js(sprintf(__('Password: enter at least %d characters.', 'epsilon'), pngm_password_min_length())); ?>'
        });
        $form.validate().settings.messages.s_password2 = $.extend({}, $form.validate().settings.messages.s_password2, {
          minlength: '<?php echo osc_esc_js(sprintf(__('Password: enter at least %d characters.', 'epsilon'), pngm_password_min_length())); ?>'
        });
      }

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
      $pass.on('blur input', function () {
        mark($(this), $(this).val().length >= minLen);
        if ($pass2.val()) mark($pass2, $pass2.val() === $(this).val());
      });
      $pass2.on('blur input', function () {
        mark($(this), $(this).val() !== '' && $(this).val() === $pass.val());
      });

      var wrap = document.querySelector('[data-pngm-reg-push]');
      var btn = wrap ? wrap.querySelector('[data-pngm-reg-push-btn]') : null;
      var label = wrap ? wrap.querySelector('[data-pngm-reg-push-label]') : null;
      var hint = wrap ? wrap.querySelector('[data-pngm-reg-push-hint]') : null;
      if (btn && wrap) {
        var swUrl = wrap.getAttribute('data-sw-url') || '/sw.js';
        if (typeof Notification === 'undefined' || !('serviceWorker' in navigator) || !('PushManager' in window)) {
          wrap.hidden = true;
        } else if (Notification.permission === 'granted') {
          btn.disabled = true;
          if (label) label.textContent = <?php echo json_encode(__('Notifications enabled', 'epsilon')); ?>;
          if (hint) hint.textContent = <?php echo json_encode(__('This browser will receive alerts after you create your account.', 'epsilon')); ?>;
        } else if (Notification.permission === 'denied') {
          btn.disabled = true;
          if (hint) hint.textContent = <?php echo json_encode(__('Notifications are blocked in your browser settings.', 'epsilon')); ?>;
        } else {
          btn.addEventListener('click', function () {
            var pending = Notification.requestPermission();
            btn.disabled = true;
            if (label) label.textContent = <?php echo json_encode(__('Enabling…', 'epsilon')); ?>;
            Promise.resolve(pending).then(function (perm) {
              if (perm !== 'granted') {
                btn.disabled = false;
                if (label) label.textContent = <?php echo json_encode(__('Enable push notifications', 'epsilon')); ?>;
                if (hint) hint.textContent = <?php echo json_encode(__('Allow notifications in the browser prompt to continue.', 'epsilon')); ?>;
                return;
              }
              if (label) label.textContent = <?php echo json_encode(__('Notifications enabled', 'epsilon')); ?>;
              if (hint) hint.textContent = <?php echo json_encode(__('This browser will receive alerts after you create your account.', 'epsilon')); ?>;
              try { window.localStorage.setItem('pngm_push_after_login', '1'); } catch (e) {}
              if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register(swUrl, { scope: '/' }).catch(function () {});
              }
            }).catch(function () {
              btn.disabled = false;
              if (label) label.textContent = <?php echo json_encode(__('Enable push notifications', 'epsilon')); ?>;
            });
          });
        }
      }
    });
  })(jQuery);
  </script>
</body>
</html>
