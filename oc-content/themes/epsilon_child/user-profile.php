<?php
  $locales = __get('locales');
  $user = osc_user();
  $location_type = eps_param('profile_location');

  if (osc_profile_img_users_enabled()) {
    osc_enqueue_script('cropper');
    osc_enqueue_style('cropper', osc_assets_url('js/cropper/cropper.min.css'));
  }

  $location_text = '';
  $loc_parts = array_filter(array(osc_user_city(), osc_user_region(), osc_user_country()));
  if (!empty($loc_parts)) {
    $location_text = @array_values($loc_parts)[0];
  }

  $email_verified = is_array($user) && !empty($user['b_active']);
  $phone_val = is_array($user) && !empty($user['s_phone_mobile']) ? (string) $user['s_phone_mobile'] : '';
  $phone_verified = ($phone_val !== '');
  $phone_parts = function_exists('pngm_ua_phone_split') ? pngm_ua_phone_split($phone_val) : array('dial' => '675', 'local' => preg_replace('/\D+/', '', $phone_val), 'iso' => 'PG');
  $phone_codes = function_exists('pngm_ua_phone_dial_codes') ? pngm_ua_phone_dial_codes() : array();

  $about_text = '';
  $locale_code = osc_current_user_locale();
  if (is_array($user) && isset($user['locale'][$locale_code]['s_info'])) {
    $about_text = (string) $user['locale'][$locale_code]['s_info'];
  } elseif (function_exists('osc_user_info')) {
    $about_text = (string) osc_user_info();
  }
  $about_len = function_exists('mb_strlen') ? mb_strlen(strip_tags($about_text)) : strlen(strip_tags($about_text));

  $notif_url = function_exists('pngm_notif_prefs_url') ? pngm_notif_prefs_url() : osc_user_alerts_url();
  $public_url = osc_user_public_profile_url(osc_logged_user_id());
  $cancel_url = osc_user_dashboard_url();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>

<body id="user-profile" class="body-ua pngm-ua pngm-ua-profile">
  <?php
    osc_current_web_theme_path('header.php');

    if (!function_exists('pngm_ua_render_sidebar')) {
      require_once dirname(__FILE__) . '/includes/account_ua.php';
    }

    if ($location_type == 0) {
      UserForm::location_javascript();
    }
  ?>

  <div class="container primary pngm-ua-shell">
    <?php pngm_ua_render_sidebar('profile'); ?>

    <div id="user-main" class="pngm-ua-main pngm-ua-panel pngm-profile">
      <?php osc_run_hook('user_profile_top'); ?>

      <?php pngm_ua_render_page_header(__('My Profile', 'epsilon')); ?>

      <form action="<?php echo osc_base_url(true); ?>" method="post" class="pngm-profile-form profile" id="pngm-profile-form">
        <input type="hidden" name="page" value="user" />
        <input type="hidden" name="action" value="profile_post" />

        <?php if (osc_profile_img_users_enabled()) { ?>
          <div class="pngm-profile-photo">
            <button type="button" class="pngm-profile-avatar" id="pngm-profile-avatar-btn" aria-label="<?php echo osc_esc_html(__('Change photo', 'epsilon')); ?>">
              <span class="user-img">
                <span class="img-preview">
                  <img src="<?php echo osc_user_profile_img_url(osc_logged_user_id()); ?>" alt="<?php echo osc_esc_html(osc_logged_user_name()); ?>" />
                </span>
              </span>
              <span class="pngm-profile-avatar-cam" aria-hidden="true"><i class="fas fa-camera"></i></span>
            </button>
            <div class="pngm-profile-photo-meta">
              <button type="button" class="pngm-profile-photo-btn" id="pngm-change-photo">
                <?php _e('Change Photo', 'epsilon'); ?>
              </button>
              <span class="pngm-profile-photo-hint"><?php _e('JPG, PNG. Max 2MB.', 'epsilon'); ?></span>
              <div class="pngm-profile-upload-raw">
                <?php UserForm::upload_profile_img(); ?>
              </div>
            </div>
          </div>
        <?php } ?>

        <div class="pngm-profile-fields">
          <div class="pngm-profile-field">
            <label for="s_name"><?php _e('Full Name', 'epsilon'); ?></label>
            <div class="pngm-profile-control"><?php UserForm::name_text(osc_user()); ?></div>
          </div>

          <div class="pngm-profile-field">
            <label for="email"><?php _e('Email', 'epsilon'); ?></label>
            <div class="pngm-profile-control has-badge">
              <input type="text" id="email" disabled value="<?php echo osc_esc_html(osc_user_email()); ?>" />
              <?php if ($email_verified) { ?>
                <span class="pngm-profile-badge"><?php _e('Verified', 'epsilon'); ?></span>
              <?php } ?>
            </div>
          </div>

          <div class="pngm-profile-field">
            <label for="pngm_phone_local"><?php _e('Phone Number', 'epsilon'); ?></label>
            <div class="pngm-profile-control pngm-profile-phone<?php echo $phone_verified ? ' has-badge' : ''; ?>">
              <label class="pngm-profile-phone-cc" for="pngm_phone_dial">
                <select name="pngm_phone_dial" id="pngm_phone_dial" aria-label="<?php echo osc_esc_html(__('Country calling code', 'epsilon')); ?>">
                  <?php foreach ($phone_codes as $cc) {
                    $sel = ((string) $cc['dial'] === (string) $phone_parts['dial']) ? ' selected' : '';
                  ?>
                    <option value="<?php echo osc_esc_html($cc['dial']); ?>" data-iso="<?php echo osc_esc_html($cc['iso']); ?>" data-flag="<?php echo osc_esc_html($cc['flag']); ?>"<?php echo $sel; ?>>
                      <?php echo osc_esc_html($cc['flag'] . ' ' . $cc['iso'] . ' +' . $cc['dial']); ?>
                    </option>
                  <?php } ?>
                </select>
              </label>
              <input type="text" name="pngm_phone_local" id="pngm_phone_local" inputmode="tel" autocomplete="tel-national" value="<?php echo osc_esc_html($phone_parts['local']); ?>" placeholder="<?php echo osc_esc_html(__('Phone number', 'epsilon')); ?>" />
              <input type="hidden" name="s_phone_mobile" id="s_phone_mobile" value="<?php echo osc_esc_html($phone_val); ?>" />
              <?php if ($phone_verified) { ?>
                <span class="pngm-profile-badge"><?php _e('Verified', 'epsilon'); ?></span>
              <?php } ?>
            </div>
          </div>

          <?php if ($location_type == 0) {
            $countries = osc_get_countries();
            $hide_country = !is_array($countries) || count($countries) <= 1;
          ?>
            <div class="pngm-profile-field<?php echo $hide_country ? ' is-country-auto' : ''; ?>">
              <label for="countryId"><?php _e('Country', 'epsilon'); ?></label>
              <div class="pngm-profile-control is-select"><?php UserForm::country_select($countries, osc_user()); ?></div>
            </div>

            <div class="pngm-profile-field">
              <label for="regionId"><?php _e('Province / Region', 'epsilon'); ?></label>
              <div class="pngm-profile-control is-select"><?php UserForm::region_select(osc_get_regions(), osc_user()); ?></div>
            </div>

            <div class="pngm-profile-field">
              <label for="cityId"><?php _e('City / Location', 'epsilon'); ?></label>
              <div class="pngm-profile-control is-select"><?php UserForm::city_select(osc_get_cities(), osc_user()); ?></div>
            </div>
          <?php } else if ($location_type == 1) { ?>
            <input type="hidden" name="countryId" id="sCountry" value="<?php echo osc_esc_html($user['fk_c_country_code']); ?>" />
            <input type="hidden" name="regionId" id="sRegion" value="<?php echo osc_esc_html($user['fk_i_region_id']); ?>" />
            <input type="hidden" name="cityId" id="sCity" value="<?php echo osc_esc_html($user['fk_i_city_id']); ?>" />

            <div class="pngm-profile-field">
              <label for="sLocation"><?php _e('City / Location', 'epsilon'); ?></label>
              <div class="pngm-profile-control picker location only-search">
                <input name="sLocation" type="text" class="location-pick" id="sLocation" placeholder="<?php echo osc_esc_html(__('Start typing region, city...', 'epsilon')); ?>" value="<?php echo osc_esc_html($location_text); ?>" autocomplete="off" />
                <i class="clean fas fa-times-circle"></i>
                <div class="results"></div>
              </div>
            </div>
          <?php } ?>

          <div class="pngm-profile-field">
            <label for="s_address"><?php _e('Street Address', 'epsilon'); ?></label>
            <div class="pngm-profile-control"><?php UserForm::address_text(osc_user()); ?></div>
          </div>

          <div class="pngm-profile-field pngm-profile-field-zip">
            <label for="s_zip"><?php _e('ZIP / Postcode', 'epsilon'); ?></label>
            <div class="pngm-profile-control"><?php UserForm::zip_text(osc_user()); ?></div>
          </div>

          <div class="pngm-profile-field pngm-profile-about" id="pngm-profile-about">
            <label for="pngm-about"><?php _e('About Me', 'epsilon'); ?></label>
            <div class="pngm-profile-control is-textarea">
              <?php UserForm::multilanguage_info($locales, osc_user()); ?>
              <span class="pngm-profile-counter" id="pngm-about-counter"><?php echo (int) $about_len; ?>/250</span>
            </div>
          </div>
        </div>

        <div class="hooksrow pngm-profile-hooks"><?php osc_run_hook('user_form'); ?></div>
        <?php osc_run_hook('user_profile_sidebar'); ?>

        <div class="pngm-profile-accords">
          <details class="pngm-profile-accord">
            <summary>
              <span><?php _e('Privacy & Visibility', 'epsilon'); ?></span>
              <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </summary>
            <div class="pngm-profile-accord-body">
              <div class="pngm-profile-field">
                <label for="b_company"><?php _e('Account type', 'epsilon'); ?></label>
                <div class="pngm-profile-control is-select">
                  <?php UserForm::is_company_select(osc_user(), __('Personal', 'epsilon'), __('Company', 'epsilon')); ?>
                </div>
              </div>
              <div class="pngm-profile-field">
                <label for="s_website"><?php _e('Website', 'epsilon'); ?></label>
                <div class="pngm-profile-control"><?php UserForm::website_text(osc_user()); ?></div>
              </div>
              <div class="pngm-profile-field">
                <label for="s_phone_land"><?php _e('Land phone', 'epsilon'); ?></label>
                <div class="pngm-profile-control"><?php UserForm::phone_land_text(osc_user()); ?></div>
              </div>
              <div class="pngm-profile-field">
                <label for="s_city_area"><?php _e('City area', 'epsilon'); ?></label>
                <div class="pngm-profile-control"><?php UserForm::city_area_text(osc_user()); ?></div>
              </div>
              <p class="pngm-profile-accord-note">
                <a href="<?php echo osc_esc_html($public_url); ?>"><?php _e('View public seller profile', 'epsilon'); ?></a>
              </p>
            </div>
          </details>

          <details class="pngm-profile-accord">
            <summary>
              <span><?php _e('Notification Preferences', 'epsilon'); ?></span>
              <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </summary>
            <div class="pngm-profile-accord-body">
              <p><?php _e('Choose which email and push alerts you receive about messages, listings and saved searches.', 'epsilon'); ?></p>
              <a class="pngm-ua-btn is-ghost" href="<?php echo osc_esc_html($notif_url); ?>"><?php _e('Open notification preferences', 'epsilon'); ?></a>
            </div>
          </details>

          <details class="pngm-profile-accord" id="pngm-profile-security">
            <summary>
              <span><?php _e('Account & Security', 'epsilon'); ?></span>
              <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </summary>
            <div class="pngm-profile-accord-body">
              <div class="pngm-profile-sec-block change-mail profile-box">
                <h3><?php _e('Change your email', 'epsilon'); ?></h3>
                <p class="pngm-profile-accord-note">
                  <a href="#" class="change-email"><?php _e('Update email address', 'epsilon'); ?></a>
                </p>
              </div>
              <div class="pngm-profile-sec-block">
                <h3><?php _e('Password', 'epsilon'); ?></h3>
                <p class="pngm-profile-accord-note"><?php _e('Use the form below to change your password.', 'epsilon'); ?></p>
              </div>
              <?php if (!eps_is_demo()) { ?>
                <p class="pngm-profile-accord-note is-danger">
                  <a class="btn-remove-account" href="<?php echo osc_base_url(true) . '?page=user&action=delete&id=' . osc_user_id() . '&secret=' . $user['s_secret']; ?>" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to delete your account? This action cannot be undone', 'epsilon')); ?>?')">
                    <?php _e('Delete account', 'epsilon'); ?>
                  </a>
                </p>
              <?php } ?>
            </div>
          </details>
        </div>

        <div class="pngm-profile-actions">
          <button type="submit" class="pngm-ua-btn pngm-profile-save"><?php _e('Save Changes', 'epsilon'); ?></button>
          <a class="pngm-ua-btn is-ghost pngm-profile-cancel" href="<?php echo osc_esc_html($cancel_url); ?>"><?php _e('Cancel', 'epsilon'); ?></a>
        </div>
      </form>

      <div class="pngm-profile-alt profile-box alt change-mail" id="pngm-change-mail">
        <h2><?php _e('Change your email', 'epsilon'); ?></h2>
        <form action="<?php echo osc_base_url(true); ?>" method="post" id="user_email_change" class="user-change">
          <?php if (!eps_is_demo()) { ?>
            <input type="hidden" name="page" value="user" />
            <input type="hidden" name="action" value="change_email_post" />
          <?php } ?>
          <div class="pngm-profile-field">
            <label for="current_email"><?php _e('Current e-mail', 'epsilon'); ?></label>
            <div class="pngm-profile-control"><input type="text" id="current_email" disabled value="<?php echo osc_esc_html(osc_logged_user_email()); ?>" /></div>
          </div>
          <div class="pngm-profile-field">
            <label for="new_email"><?php _e('New e-mail', 'epsilon'); ?></label>
            <div class="pngm-profile-control"><input type="text" name="new_email" id="new_email" value="" /></div>
          </div>
          <div class="pngm-profile-actions is-inline">
            <?php if (eps_is_demo()) { ?>
              <a class="pngm-ua-btn disabled" onclick="return false;" title="<?php echo osc_esc_html(__('You cannot do this on demo site', 'epsilon')); ?>"><?php _e('Submit', 'epsilon'); ?></a>
            <?php } else { ?>
              <button type="submit" class="pngm-ua-btn" disabled><?php _e('Submit', 'epsilon'); ?></button>
            <?php } ?>
          </div>
        </form>
      </div>

      <div class="pngm-profile-alt profile-box alt change-pass" id="pngm-change-pass">
        <h2><?php _e('Change your password', 'epsilon'); ?></h2>
        <form action="<?php echo osc_base_url(true); ?>" method="post" id="user_password_change" class="user-change">
          <?php if (!eps_is_demo()) { ?>
            <input type="hidden" name="page" value="user" />
            <input type="hidden" name="action" value="change_password_post" />
          <?php } ?>
          <div class="pngm-profile-field">
            <label for="password"><?php _e('Current password', 'epsilon'); ?></label>
            <div class="pngm-profile-control"><input type="password" name="password" id="password" value="" /></div>
          </div>
          <div class="pngm-profile-field">
            <label for="new_password"><?php _e('New password', 'epsilon'); ?></label>
            <div class="pngm-profile-control has-toggle">
              <input type="password" name="new_password" id="new_password" value="" />
              <a href="#" class="toggle-pass" title="<?php echo osc_esc_html(__('Show/hide password', 'epsilon')); ?>"><i class="fa fa-eye-slash"></i></a>
            </div>
          </div>
          <div class="pngm-profile-field">
            <label for="new_password2"><?php _e('Repeat new password', 'epsilon'); ?></label>
            <div class="pngm-profile-control has-toggle">
              <input type="password" name="new_password2" id="new_password2" value="" />
              <a href="#" class="toggle-pass" title="<?php echo osc_esc_html(__('Show/hide password', 'epsilon')); ?>"><i class="fa fa-eye-slash"></i></a>
            </div>
          </div>
          <div class="pngm-profile-actions is-inline">
            <?php if (eps_is_demo()) { ?>
              <a class="pngm-ua-btn disabled" onclick="return false;" title="<?php echo osc_esc_html(__('You cannot do this on demo site', 'epsilon')); ?>"><?php _e('Submit', 'epsilon'); ?></a>
            <?php } else { ?>
              <button type="submit" class="pngm-ua-btn" disabled><?php _e('Submit', 'epsilon'); ?></button>
            <?php } ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php
    $locale = osc_get_current_user_locale();
    $locale_name = is_array($locale) && isset($locale['s_name']) ? $locale['s_name'] : '';
  ?>

  <script>
  (function ($) {
    $(function () {
      function delUserLocCheck() {
        if ($('.tabbernav li').length) {
          var localeText = "<?php echo trim(osc_esc_html($locale_name)); ?>";
          $('.tabbernav > li > a:contains("' + localeText + '")').click();
          clearInterval(checkTimer);
        }
      }
      var checkTimer = setInterval(delUserLocCheck, 150);

      // Prefer visible Change Photo; keep UserForm trigger hidden but functional
      $('.pngm-profile-upload-raw > a.start-image-upload, .pngm-profile-upload-raw > a.remove-profile-picture').addClass('pngm-profile-upload-hidden');
      function pngmOpenProfilePhoto() {
        var $raw = $('.pngm-profile-upload-raw .start-image-upload').first();
        if ($raw.length) {
          $raw.trigger('click');
          return;
        }
        var input = document.querySelector('.pngm-profile-upload-raw input.upload-image');
        if (input) input.click();
      }
      $('#pngm-change-photo, #pngm-profile-avatar-btn').on('click', function (e) {
        e.preventDefault();
        pngmOpenProfilePhoto();
      });

      // About me counter (first locale textarea)
      var $about = $('.pngm-profile-about textarea').first();
      var $counter = $('#pngm-about-counter');
      function updateAbout() {
        if (!$about.length) return;
        var val = $about.val() || '';
        if (val.length > 250) {
          val = val.substring(0, 250);
          $about.val(val);
        }
        $counter.text(val.length + '/250');
      }
      $about.attr('maxlength', 250).on('input', updateAbout);
      updateAbout();

      function syncProfilePhone() {
        var dial = String($('#pngm_phone_dial').val() || '675').replace(/\D+/g, '');
        var local = String($('#pngm_phone_local').val() || '').replace(/\D+/g, '');
        $('#s_phone_mobile').val(local ? ('+' + dial + local) : '');
      }
      $('#pngm_phone_dial, #pngm_phone_local').on('change input', syncProfilePhone);
      $('#pngm-profile-form').on('submit', syncProfilePhone);

      <?php if (!eps_is_demo()) { ?>
      $('#new_email').on('keyup', function () {
        $(this).closest('form').find('button[type="submit"]').prop('disabled', $(this).val() === '');
      });
      $('#password, #new_password, #new_password2').on('keyup', function () {
        var $form = $(this).closest('form');
        var ready = $('#password').val() !== '' && $('#new_password').val() !== '' && $('#new_password2').val() !== '';
        $form.find('button[type="submit"]').prop('disabled', !ready);
      });
      <?php } ?>

      $('body').on('click', 'a.change-email', function (e) {
        e.preventDefault();
        var $box = $('#pngm-change-mail');
        if ($box.length) {
          $('html, body').animate({ scrollTop: $box.offset().top - 72 }, 300);
          $('#new_email').focus();
        }
      });
    });
  })(jQuery);
  </script>

  <?php
    if (function_exists('profile_picture_upload') && !osc_profile_img_users_enabled()) {
      profile_picture_upload();
    }
  ?>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
