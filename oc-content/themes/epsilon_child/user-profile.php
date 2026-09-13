<?php
  $locales = __get('locales');
  $user = osc_user();
  $location_type = eps_param('profile_location');

  if(osc_profile_img_users_enabled()) {
    osc_enqueue_script('cropper');
    osc_enqueue_style('cropper', osc_assets_url('js/cropper/cropper.min.css'));
  }
  
  $location_text = @array_values(array_filter(array(osc_user_city(), osc_user_region(), osc_user_country())))[0];
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>

<body id="user-profile" class="body-ua pngm-ua">
  <?php 
    osc_current_web_theme_path('header.php');

    if (!function_exists('pngm_ua_render_sidebar')) {
      require_once dirname(__FILE__) . '/includes/account_ua.php';
    }

    if($location_type == 0) {
      UserForm::location_javascript(); 
    }
  ?>

  <div class="container primary pngm-ua-shell">
    <?php pngm_ua_render_sidebar('profile'); ?>

    <div id="user-main" class="pngm-ua-main pngm-ua-panel">
      <?php osc_run_hook('user_profile_top'); ?>
      
      <h1><?php _e('My Profile', 'epsilon'); ?></h1>

      <div class="profile-box prim">
        <form action="<?php echo osc_base_url(true); ?>" method="post" class="profile">
          <div class="wrap">
            <div class="left-block">
              <input type="hidden" name="page" value="user" />
              <input type="hidden" name="action" value="profile_post" />

              <?php if(osc_profile_img_users_enabled()) { ?>
                <div class="control-group profile-img">
                  <label class="control-label" for="name"><?php _e('Profile picture (avatar)', 'epsilon'); ?></label>
                  <div class="controls">
                    <div class="user-img">
                      <div class="img-preview">
                        <img src="<?php echo osc_user_profile_img_url(osc_logged_user_id()); ?>" alt="<?php echo osc_esc_html(osc_logged_user_name()); ?>"/>
                      </div> 
                    </div> 

                    <div class="user-img-button">
                      <?php UserForm::upload_profile_img(); ?>
                    </div>
                  </div>
                </div>
              <?php } ?>
      
              <div class="row">
                <label for="name"><?php _e('Your name', 'epsilon'); ?> <span class="req">*</span></label>
                <div class="input-box"><?php UserForm::name_text(osc_user()); ?></div>

                <?php if(function_exists('profile_picture_show') && !osc_profile_img_users_enabled()) { ?>
                  <a href="#" class="update-avatar"><?php _e('Update avatar', 'epsilon'); ?></a>
                  <?php echo profile_picture_show(); ?>
                <?php } ?>
              </div>


              <div class="row">
                <label for="email"><?php _e('E-mail', 'epsilon'); ?> <a href="#" class="change-email"><?php _e('Edit', 'epsilon'); ?></a></label>
                <div class="input-box"><input type="text" disabled value="<?php echo osc_esc_html(osc_user_email()); ?>"/></div>
              </div>

              <div class="row">
                <label for="phoneMobile"><?php _e('Mobile phone', 'epsilon'); ?> <span class="req">*</span></label>
                <div class="input-box"><?php UserForm::mobile_text(osc_user()); ?></div>
              </div>

              <div class="row">
                <label for="phoneLand"><?php _e('Land Phone', 'epsilon'); ?></label>
                <div class="input-box"><?php UserForm::phone_land_text(osc_user()); ?></div>
              </div>                        

              <div class="row">
                <label for="webSite"><?php _e('Website', 'epsilon'); ?></label>
                <div class="input-box"><?php UserForm::website_text(osc_user()); ?></div>
              </div>
              
              <div class="row">
                <label for="user_type"><?php _e('User type', 'epsilon'); ?></label>
                <div class="input-box"><?php UserForm::is_company_select(osc_user(), __('Personal', 'epsilon'), __('Company', 'epsilon')); ?></div>
              </div>

              <div class="hooksrow"><?php osc_run_hook('user_form'); ?></div>

              <div class="row">
                <label for="info"><?php _e('About you', 'epsilon'); ?></label>
                <?php UserForm::multilanguage_info($locales, osc_user()); ?>
              </div>
            </div>
            
            <div id="user-loc" class="right-block navigator-fill-selects">
              <?php osc_run_hook('user_profile_sidebar'); ?>
              
              <h2><?php _e('Location information', 'epsilon'); ?></h2>

              <?php if(eps_param('default_location') == 1) { ?>
                <div class="row navigator">
                  <a href="#" class="locate-me">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 168c-48.6 0-88 39.4-88 88s39.4 88 88 88 88-39.4 88-88-39.4-88-88-88zm0 128c-22.06 0-40-17.94-40-40s17.94-40 40-40 40 17.94 40 40-17.94 40-40 40zm240-64h-49.66C435.49 145.19 366.81 76.51 280 65.66V16c0-8.84-7.16-16-16-16h-16c-8.84 0-16 7.16-16 16v49.66C145.19 76.51 76.51 145.19 65.66 232H16c-8.84 0-16 7.16-16 16v16c0 8.84 7.16 16 16 16h49.66C76.51 366.81 145.19 435.49 232 446.34V496c0 8.84 7.16 16 16 16h16c8.84 0 16-7.16 16-16v-49.66C366.81 435.49 435.49 366.8 446.34 280H496c8.84 0 16-7.16 16-16v-16c0-8.84-7.16-16-16-16zM256 400c-79.4 0-144-64.6-144-144s64.6-144 144-144 144 64.6 144 144-64.6 144-144 144z"/></svg>
                    <strong><?php _e('Use current location', 'epsilon'); ?></strong>
                    <span class="status">
                      <span class="init"><?php _e('Click to find closest city to your location', 'epsilon'); ?></span>
                      <span class="not-supported" style="display:none;"><?php _e('Geolocation is not supported by your browser', 'epsilon'); ?></span>
                      <span class="failed" style="display:none;"><?php _e('Unable to retrieve your location, it may be blocked', 'epsilon'); ?></span>
                      <span class="failed-unfound" style="display:none;"><?php _e('Unable to retrieve your location, no close city found', 'epsilon'); ?></span>
                      <span class="loading" style="display:none;"><?php _e('Locating...', 'epsilon'); ?></span>
                      <span class="success" style="display:none;"></span>
                      <span class="refresh" style="display:none;"><?php _e('Refresh page to take effect', 'epsilon'); ?></span>
                    </span>
                  </a>
                </div>
              <?php } ?>
      
              <?php if($location_type == 0) { ?>
                <div class="row">
                  <label for="country"><?php _e('Country', 'epsilon'); ?></label>
                  <div class="input-box"><?php UserForm::country_select(osc_get_countries(), osc_user()); ?></div>
                </div>
                
                <div class="row">
                  <label for="region"><?php _e('Region', 'epsilon'); ?></label>
                  <div class="input-box"><?php UserForm::region_select(osc_get_regions(), osc_user()); ?></div>
                </div>
                
                <div class="row">
                  <label for="city"><?php _e('City', 'epsilon'); ?></label>
                  <div class="input-box"><?php UserForm::city_select(osc_get_cities(), osc_user()); ?></div>
                </div>

              <?php } else if($location_type == 1) { ?>
                <input type="hidden" name="countryId" id="sCountry" value="<?php echo osc_esc_html($user['fk_c_country_code']); ?>"/>
                <input type="hidden" name="regionId" id="sRegion" value="<?php echo osc_esc_html($user['fk_i_region_id']); ?>"/>
                <input type="hidden" name="cityId" id="sCity" value="<?php echo osc_esc_html($user['fk_i_city_id']); ?>"/>
                
                <div class="row">
                  <label for="sLocation" class="auto-width"><?php _e('Location', 'epsilon'); ?></label>

                  <div class="input-box picker location only-search">
                    <input name="sLocation" type="text" class="location-pick" id="sLocation" placeholder="<?php echo osc_esc_html(__('Start typing region, city...', 'epsilon')); ?>" value="<?php echo osc_esc_html($location_text); ?>" autocomplete="off"/>
                    <i class="clean fas fa-times-circle"></i>
                    <div class="results"></div>
                  </div>
                </div>
              <?php } ?>
              
              <div class="row">
                <label for="cityArea"><?php _e('City Area', 'epsilon'); ?></label>
                <div class="input-box"><?php UserForm::city_area_text(osc_user()); ?></div>
              </div>

              <div class="row">
                <label for="address"><?php _e('Address', 'epsilon'); ?></label>
                <div class="input-box"><?php UserForm::address_text(osc_user()); ?></div>
              </div>

              <div class="row">
                <label for="address"><?php _e('ZIP', 'epsilon'); ?></label>
                <div class="input-box"><?php UserForm::zip_text(osc_user()); ?></div>
              </div>
            </div>
          </div>
          
          <div class="row user-buttons">
            <button type="submit" class="btn btn-primary mbBg"><?php _e('Update', 'epsilon'); ?></button>

            <?php if(!eps_is_demo()) { ?>
              <a class="btn-remove-account btn btn-secondary" href="<?php echo osc_base_url(true).'?page=user&action=delete&id='.osc_user_id().'&secret='.$user['s_secret']; ?>" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to delete your account? This action cannot be undone', 'epsilon')); ?>?')"><span><?php _e('Delete account', 'epsilon'); ?></span></a>
            <?php } ?>
          </div>
        </form>
      </div>

      <div class="profile-box alt change-mail">
        <h2><?php _e('Change your email', 'epsilon'); ?></h2>

        <form action="<?php echo osc_base_url(true); ?>" method="post" id="user_email_change" class="user-change">
          <?php if(!eps_is_demo()) { ?>
          <input type="hidden" name="page" value="user" />
          <input type="hidden" name="action" value="change_email_post" />
          <?php } ?>

          <div class="row">
            <label for="email"><?php _e('Current e-mail', 'epsilon'); ?></label>
            <div class="input-box"><input type="text" disabled value="<?php echo osc_esc_html(osc_logged_user_email()); ?>"/></div>
          </div>

          <div class="row">
            <label for="new_email"><?php _e('New e-mail', 'epsilon'); ?> <span class="req">*</span></label>
            <div class="input-box"><input type="text" name="new_email" id="new_email" value="" /></div>
          </div>

          <div class="row user-buttons">
            <?php if(eps_is_demo()) { ?>
              <a class="btn mbBg disabled" onclick="return false;" title="<?php echo osc_esc_html(__('You cannot do this on demo site', 'epsilon')); ?>"><?php _e('Submit', 'epsilon'); ?></a>
            <?php } else { ?>
              <button type="submit" class="btn mbBg" disabled><?php _e('Submit', 'epsilon'); ?></button>
            <?php } ?>
          </div>
        </form>
      </div>
      
      <div class="profile-box alt change-pass">
        <h2><?php _e('Change your password', 'epsilon'); ?></h2>

        <form action="<?php echo osc_base_url(true); ?>" method="post" id="user_password_change" class="user-change">
          <?php if(!eps_is_demo()) { ?>
          <input type="hidden" name="page" value="user" />
          <input type="hidden" name="action" value="change_password_post" />
          <?php } ?>

          <div class="row">
            <label for="password"><?php _e('Current password', 'epsilon'); ?> <span class="req">*</span></label>
            <div class="input-box"><input type="password" name="password" id="password" value="" /></div>
          </div>

          <div class="row">
            <label for="new_password"><?php _e('New password', 'epsilon'); ?> <span class="req">*</span></label>
            <div class="input-box">
              <input type="password" name="new_password" id="new_password" value="" />
              <a href="#" class="toggle-pass" title="<?php echo osc_esc_html(__('Show/hide password', 'epsilon')); ?>"><i class="fa fa-eye-slash"></i></a>
            </div>
          </div>

          <div class="row">
            <label for="new_password2"><?php _e('Repeat new password', 'epsilon'); ?> <span class="req">*</span></label>
            <div class="input-box">
              <input type="password" name="new_password2" id="new_password2" value="" />
              <a href="#" class="toggle-pass" title="<?php echo osc_esc_html(__('Show/hide password', 'epsilon')); ?>"><i class="fa fa-eye-slash"></i></a>
            </div>
          </div>

          <div class="row user-buttons">
            <?php if(eps_is_demo()) { ?>
              <a class="btn mbBg disabled" onclick="return false;" title="<?php echo osc_esc_html(__('You cannot do this on demo site', 'epsilon')); ?>"><?php _e('Submit', 'epsilon'); ?></a>
            <?php } else { ?>
              <button type="submit" class="btn mbBg" disabled><?php _e('Submit', 'epsilon'); ?></button>
            <?php } ?>
          </div>
        </form>
      </div>

    </div>
  </div>

  <?php 
    $locale = osc_get_current_user_locale();
    $locale_code = $locale['pk_c_code'];
    $locale_name = $locale['s_name'];
  ?>

  <script>
    $(document).ready(function() {
      // Unify selected locale in all tabs
      function delUserLocCheck() {
        if($('.tabbernav li').length) {
          var localeText = "<?php echo trim(osc_esc_html($locale_name)); ?>";
          $('.tabbernav > li > a:contains("' + localeText+ '")').click();
          clearInterval(checkTimer);
          return;
        }
      }

      var checkTimer = setInterval(delUserLocCheck, 150);
      
      <?php if(!eps_is_demo()) { ?>
        // Enable submit buttons
        $('input#new_email').on('keyup', function() {
          if($(this).val() != '') {
            $(this).closest('.profile-box').find('button').attr('disabled', false);
          } else {
            $(this).closest('.profile-box').find('button').attr('disabled', true);
          }
        });
        
        $('input#password, input#new_password, input#new_password2').on('keyup', function() {
          if($(this).val() != '') {
            $(this).closest('.profile-box').find('button').attr('disabled', false);
          } else {
            $(this).closest('.profile-box').find('button').attr('disabled', true);
          }
        });
      <?php } ?>
    });
  </script>


  <?php 
    if(function_exists('profile_picture_upload') && !osc_profile_img_users_enabled()) { 
      profile_picture_upload(); 
    } 
  ?>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>