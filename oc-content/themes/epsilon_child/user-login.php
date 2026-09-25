<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
  <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js'); ?>"></script>
</head>

<body id="user-login" class="pre-account login pngm-auth pngm-auth-v2">
  <?php UserForm::js_validation(); ?>
  <?php osc_current_web_theme_path('header.php'); ?>

  <section class="pngm-auth-wrap">
    <div class="pngm-auth-card">
      <aside class="pngm-auth-visual" aria-hidden="true">
        <img src="<?php echo osc_esc_html(osc_current_web_theme_url('images/auth-hero.png')); ?>?v=<?php echo rawurlencode(PNGM_CHILD_VERSION); ?>" alt="" width="720" height="960" decoding="async" />
      </aside>

      <div class="pngm-auth-panel">
        <div class="pngm-auth-box">
          <h1 class="pngm-auth-lead"><?php _e('Log in to your PNGMarket account.', 'epsilon'); ?></h1>

          <?php if (function_exists('pngm_render_social_login')) { pngm_render_social_login('login', 'row'); } ?>

          <form action="<?php echo osc_base_url(true); ?>" method="post" class="pngm-auth-form" id="pngm-login-form">
            <input type="hidden" name="page" value="login" />
            <input type="hidden" name="action" value="login_post" />

            <?php osc_run_hook('user_pre_login_form'); ?>

            <div class="pngm-auth-field row">
              <label for="email"><?php _e('Email', 'epsilon'); ?></label>
              <div class="pngm-auth-control">
                <?php
                  $pngm_login_email = '';
                  if (class_exists('Session')) {
                      $pngm_login_email = trim((string) Session::newInstance()->_get('pngm_login_email'));
                  }
                  UserForm::email_login_text($pngm_login_email !== '' ? array('s_email' => $pngm_login_email) : null);
                ?>
                <span class="pngm-auth-status" aria-hidden="true"></span>
              </div>
            </div>

            <div class="pngm-auth-field row">
              <label for="password"><?php _e('Password', 'epsilon'); ?></label>
              <div class="pngm-auth-control has-toggle">
                <?php UserForm::password_login_text(); ?>
                <a href="#" class="toggle-pass" title="<?php echo osc_esc_html(__('Show/hide password', 'epsilon')); ?>"><i class="fa fa-eye-slash"></i></a>
                <span class="pngm-auth-status" aria-hidden="true"></span>
              </div>
            </div>

            <div class="user-reg-hook"><?php osc_run_hook('user_login_form'); ?></div>

            <div class="pngm-auth-captcha">
              <?php pngm_auth_show_recaptcha('login'); ?>
            </div>

            <div class="pngm-auth-meta">
              <label class="pngm-auth-check">
                <?php UserForm::rememberme_login_checkbox(null, true); ?>
                <span><?php _e('Stay signed in on this device', 'epsilon'); ?></span>
              </label>
              <input type="hidden" name="remember" value="1" />
              <a class="pngm-auth-link" href="<?php echo osc_recover_user_password_url(); ?>"><?php _e('Forgot password?', 'epsilon'); ?></a>
            </div>

            <button type="submit" class="btn pngm-auth-submit"><?php _e('Log in', 'epsilon'); ?></button>
          </form>

          <p class="pngm-auth-switch">
            <a href="<?php echo osc_register_account_url(); ?>"><?php _e("Don't have an account? Register", 'epsilon'); ?></a>
          </p>
        </div>
      </div>
    </div>
  </section>

  <?php osc_current_web_theme_path('footer.php'); ?>

  <script type="text/javascript">
  (function ($) {
    $(function () {
      var $email = $('input[name="email"]');
      var $pass = $('input[name="password"]');
      $email.attr('placeholder', '<?php echo osc_esc_js(__('your.email@dot.com', 'epsilon')); ?>').attr('required', true).attr('type', 'email');
      $pass.removeAttr('placeholder').attr('required', true);

      function mark($input) {
        var $field = $input.closest('.pngm-auth-field');
        var el = $input.get(0);
        if (!$field.length || !el) return;
        $field.removeClass('is-ok is-error');
        if (!$input.val()) return;
        if (el.checkValidity && el.checkValidity()) {
          $field.addClass('is-ok');
        } else {
          $field.addClass('is-error');
        }
      }

      $email.on('blur input', function () { mark($(this)); });
      if ($email.val()) {
        mark($email);
        $pass.trigger('focus');
      }
      $pass.on('blur input', function () {
        var $field = $(this).closest('.pngm-auth-field');
        $field.removeClass('is-ok is-error');
        if ($(this).val().length >= 1) $field.addClass('is-ok');
      });

      var failMsg = '<?php echo osc_esc_js(__('Could not sign in. Please check your email and password.', 'epsilon')); ?>';
      var $form = $('#pngm-login-form');

      function isLoginPage(url, html) {
        try {
          var u = new URL(url, window.location.href);
          var page = u.searchParams.get('page') || '';
          var action = u.searchParams.get('action') || '';
          if (page === 'login' && action !== 'login_post') {
            return true;
          }
          if (/\/login\/?$/.test(u.pathname)) {
            return true;
          }
        } catch (err) {}
        return /id=["']user-login["']/.test(html || '');
      }

      function errorTexts(html) {
        var doc = new DOMParser().parseFromString(html || '', 'text/html');
        var nodes = doc.querySelectorAll('.flashmessage-error');
        var texts = [];
        Array.prototype.forEach.call(nodes, function (el) {
          var clone = el.cloneNode(true);
          var closer = clone.querySelector('.ico-close, .close');
          if (closer && closer.parentNode) {
            closer.parentNode.removeChild(closer);
          }
          var text = (clone.textContent || '').replace(/\s+/g, ' ').trim();
          if (text && texts.indexOf(text) === -1) {
            texts.push(text);
          }
        });
        return texts;
      }

      $form.on('submit', function (e) {
        var formEl = this;
        if (formEl.checkValidity && !formEl.checkValidity()) {
          return;
        }
        e.preventDefault();
        if ($form.data('pngm-busy')) {
          return;
        }
        $form.data('pngm-busy', 1);
        var $btn = $form.find('.pngm-auth-submit');
        $btn.prop('disabled', true);

        fetch(formEl.action, {
          method: 'POST',
          body: new FormData(formEl),
          credentials: 'same-origin',
          redirect: 'follow'
        }).then(function (res) {
          return res.text().then(function (html) {
            return { url: res.url, html: html };
          });
        }).then(function (payload) {
          if (!isLoginPage(payload.url, payload.html)) {
            window.location.href = payload.url || window.location.href;
            return;
          }
          var errors = errorTexts(payload.html);
          if (!errors.length) {
            errors = [failMsg];
          }
          errors.forEach(function (msg) {
            if (typeof window.pngmShowToast === 'function') {
              window.pngmShowToast(msg, true);
            }
          });
          if (window.grecaptcha && typeof window.grecaptcha.reset === 'function') {
            try { window.grecaptcha.reset(); } catch (err) {}
          }
          $pass.trigger('focus');
          $form.data('pngm-busy', 0);
          $btn.prop('disabled', false);
        }).catch(function () {
          $form.data('pngm-busy', 0);
          $btn.prop('disabled', false);
          formEl.submit();
        });
      });
    });
  })(jQuery);
  </script>
</body>
</html>
