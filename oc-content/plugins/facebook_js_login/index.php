<?php
/*
  Plugin Name: Facebook Instant Login Plugin
  Plugin URI: https://osclasspoint.com/osclass-plugins/social-and-authentication/facebook-js-login-plugin-i201
  Description: Enable Facebook Instant login feature to your users to quickly login or register with their Facebook Account
  Version: 1.0.6
  Author: MB Themes
  Author URI: http://osclasspoint.com
  Author Email: info@osclasspoint.com
  Short Name: facebook_js_login
  Plugin update URI: facebook_js_login
  Support URI: https://forums.osclasspoint.com/general-plugins-discussion/
  Product Key: csFubIxcfbZTXbuw18p9
*/


require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'model/ModelFJL.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'functions.php';

define('FB_JS_SDK_VERSION', 'v16.0');
define('FJL_DEBUG', false);

// https://developers.facebook.com/docs/facebook-login/web/
// https://developers.facebook.com/docs/reference/javascript/FB.login/v16.0
// https://developers.facebook.com/docs/facebook-login/web/login-button
// https://developers.facebook.com/docs/javascript/quickstart

// SHOW LOGIN BUTTON
function fjl_init_login_button() {
  if(!osc_is_web_user_logged_in() && fjl_param('enabled') == 1 && fjl_param('app_id') != '' && fjl_check_page()) { 
  ?>
  <script type="text/javascript" async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>

  <script type="text/javascript">
    function fjlCheckLoginState(noPopup = false) { 
      FB.getLoginStatus(function(response) {   // See the onlogin handler
        <?php if(FJL_DEBUG) { ?>console.log('fjlCheckLoginState: ', response);<?php } ?>

        if (response.status === 'connected') {
          // The user is logged in and has authenticated your app, and response.authResponse supplies
          // the user's ID, a valid access token, a signed request, and the time the access token and signed request each expire.
          // var uid = response.authResponse.userID;
          // var accessToken = response.authResponse.accessToken;
          fjlGetFbUserDataAndLogin(response);  
          
        } else {      // response.status === 'not_authorized'
          // The user hasn't authorized your application. They must click the Login button, or you must call FB.login
          // in response to a user gesture, to launch a login dialog.
          if(noPopup !== true) {
            FB.login(function (response) {
              if(response.authResponse) {
                // Get and display the user profile data
                fjlGetFbUserDataAndLogin(response);  
              }
            }, {scope: 'public_profile,email'});
          }
        }
      });
    }
 
    // Get user information from facebook
    function fjlGetFbUserDataAndLogin(inputResponse) { 
      console.log('Welcome! Fetching your information.... ');
      
      FB.api('/me', {fields: 'name,email,picture'}, function(response) {
        <?php if(FJL_DEBUG) { ?>console.log('fjlGetFbUserDataAndLogin: ', response);<?php } ?>
        console.log('Successful login for: ' + response.name);
        
        fjlLoginCallback(inputResponse, response);
      });
    }

    // Call after login data are ready, login user
    function fjlLoginCallback(xRes, fbUserData) {
      var ajax = new XMLHttpRequest();
      ajax.open("POST", "<?php echo osc_esc_html(osc_base_url(true) . '?fjlRedirect=1'); ?>", true);

      // Set timeout 30 seconds
      ajax.timeout = 30000;

      ajax.ontimeout = function () {
        console.error('Error: Request timed out! ', ajax);
    
        // setTimeout(function() {
          // location.reload();
        // }, 3000);
      };

      // Callback when the status of AJAX is changed
      ajax.onreadystatechange = function() {
        <?php if(FJL_DEBUG) { ?>console.log('ajax.onreadystatechange: ', this);<?php } ?>

        if(this.readyState == 4) {       // request is completed
          if(this.status != 200) {
            console.error('Error: Invalid http status (' + this.status + '), expecting 200!');
            console.log(this.responseText);
            console.log(this);
          }

          // window.location.reload();
          var url = window.location.href;    

          if(url.indexOf('fjlReloaded') > -1){
            return false;  // already reloaded

          } else {
            if(url.indexOf('?') > -1){
              url += '&fjlReloaded=1'
            } else {
              url += '?fjlReloaded=1'
            }
          }
          
          // Reload page for login
          window.location.href = url;
          
        } else {
          console.log('Waiting for ready state 4, now is ' + this.readyState);
          // console.log(this);

        }
      }

      // Send facebook credentials in the AJAX request
      var formData = new FormData();
      
      formData.append("xRes", JSON.stringify(xRes));
      formData.append("fbUserData", JSON.stringify(fbUserData));
      
      <?php if(FJL_DEBUG) { ?>console.log('formData.xRes: ', JSON.stringify(xRes));<?php } ?>
      <?php if(FJL_DEBUG) { ?>console.log('formData.fbUserData: ', JSON.stringify(fbUserData));<?php } ?>

      ajax.send(formData);
    }
    </script>

    <script> 
    // Initialize and check
    window.fbAsyncInit = function() {
      FB.init({
        appId: '<?php echo osc_esc_js(fjl_param('app_id')); ?>',
        cookie: true,                     // Enable cookies to allow the server to access the session.
        autoLogAppEvents : true,
        xfbml: true,
        version: '<?php echo FB_JS_SDK_VERSION; ?>'
      });
      
      // Check current user status - and if we should login user
      if(1 == <?php echo (int)fjl_param('enable_autologin'); ?> && 1 != <?php echo (int)Cookie::newInstance()->get_value('fjl_user_logged_out'); ?>) {
        fjlCheckLoginState(true);
      }
      
      // Logout person from facebook
      if(1 == <?php echo (int)Cookie::newInstance()->get_value('fjl_user_logged_out'); ?>) {
        FB.getLoginStatus(function(response) {
          if(response.authResponse) {
            FB.logout(function(response) { });
          }
        });
      }
    };
    
    // Retro compatibility to old buttons
    $(document).ready(function() {
      $('body').on('click', '.fl-button:not(.fjl-button), .fl-link, .social a.facebook<?php echo osc_esc_js(trim((string)fjl_param('custom_selector')) <> '' ? ',' . fjl_param('custom_selector') : ''); ?>', function(e) {
        e.preventDefault();
        fjlCheckLoginState();
        return false;
      });
    });
  </script>

  <style>
    a.fjl-button {display:inline-block;clear:both;width:auto;margin:15px 0;height:36px;width:auto;line-height:36px;padding:0 12px 0 48px;font-size:13px;font-weight:600;color:#fff;text-decoration:none;border-radius:4px;position:relative;background:#4267b2;}
    a.fjl-button img {position:absolute;width:36px;height:36px;top:0px;left:0px;background:rgba(0,0,0,0.15);border-radius:4px 0 0 4px;border-right:1px solid rgba(0,0,0,0.02);}
    a.fjl-button:hover {background:#42609e;color:#fff;text-decoration:none;}
    a.fjl-button.fjl-hooked {width:100%;margin:6px 0 24px 0;}
    .control-group.remember + a.fjl-button.fjl-hooked {margin:-2px 0 32px 0;}
    input[type="hidden"] + a.fjl-button.fjl-hooked, a.fjl-button.fjl-hooked:first-of-type {margin:0 0 26px 0;}
    html[dir="rtl"] a.fjl-button {direction:rtl;padding:0 48px 0 12px;}
    html[dir="rtl"] a.fjl-button img {right:0;left:auto;border-radius:0 4px 4px 0;border-left:1px solid rgba(0,0,0,0.02);border-right:none;}
  </style>
  <?php
  }
}

osc_add_hook('footer', 'fjl_init_login_button', 10);



// CALLBACK AFTER LOGIN BUTTON IS HIT
// After successfull ajax call, user browser is refreshed
function fjl_login_callback() {
  if(Params::getParam('fjlRedirect') == 1) {
    $user_id = '';
    
    if(fjl_param('enabled') == 1 && fjl_param('app_secret') != '' && Params::getParam('xRes') != '' && Params::getParam('fbUserData') != '') {
      $xres = @json_decode(Params::getParam('xRes'), true);
      $fb_data = @json_decode(Params::getParam('fbUserData'), true);

      // Log data
      if(FJL_DEBUG) {
        error_log(__('Facebook JS Login Data - after redirect', 'facebook_js_login'));
        error_log(Params::getParam('xRes'));
        error_log(Params::getParam('fbUserData'));
      }

      if(!isset($xres['authResponse']) || !isset($xres['authResponse']['signedRequest']) || !isset($xres['authResponse']['userID']) || !isset($fb_data['email']) || !isset($fb_data['name'])) {
        header('HTTP/1.1 400 Invalid information in payload');
        
        $message = '';
        if(!isset($xres['authResponse'])) {
          $message = __('Missing key "authResponse" in payload.', 'facebook_js_login');
        } else if(!isset($xres['authResponse']['signedRequest'])) {
          $message = __('Missing key "signedRequest" in authResponse of payload.', 'facebook_js_login');
        } else if(!isset($xres['authResponse']['userID'])) {
          $message = __('Missing "userID" in authResponse of payload. Review scope of your FB application, it must be approved and scope for at least: name,email!', 'facebook_js_login');
        } else if(!isset($fb_data['email'])) {
          $message = __('Missing "email" in authResponse of payload. Review scope of your FB application, it must be approved and scope for at least: name,email!', 'facebook_js_login');
        } else if(!isset($fb_data['name'])) {
          $message = __('Missing "name" in authResponse of payload. Review scope of your FB application, it must be approved and scope for at least: name,email!', 'facebook_js_login');
        }         
        
        osc_add_flash_error_message(__('Invalid information in payload.', 'facebook_js_login') . ' ' . $message);
        
        $xres['plugin_message'] = $message;
        
        error_log(__('Facebook JS Login failed', 'facebook_js_login'));
        error_log(sprintf(__('Message: %s', 'facebook_js_login'), $message));
        error_log(sprintf(__('Response (xRes): %s', 'facebook_js_login'), json_encode($xres, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)));
        error_log(sprintf(__('FB User Data: %s', 'facebook_js_login'), json_encode($fb_data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)));
        
        echo json_encode($xres, JSON_PRETTY_PRINT);
        exit;
      }
    
      $auth_id = $xres['authResponse']['userID'];
      $email = $fb_data['email'];
      $name = $fb_data['name'];
      $picture = '';
      
      if(isset($fb_data['picture']) && isset($fb_data['picture']['data']) && isset($fb_data['picture']['data']['url'])) {
        $picture = urldecode($fb_data['picture']['data']['url']);
      }
      
      //$access_token = $xres['authResponse']['accessToken'];
      $signed_request = $xres['authResponse']['signedRequest'];
      
      $signed_request_arr = explode('.', $signed_request);
      $encodedSig = $signed_request_arr[0];
      $encodedPayload = $signed_request_arr[1];

      $hashedSig = hash_hmac('sha256', $encodedPayload, fjl_param('app_secret'), $raw_output = true);

      // Verified valid response
      if(base64_decode(strtr($encodedSig, '-_', '+/')) !== $hashedSig) {
        osc_add_flash_error_message(__('Payload signature does not match.', 'facebook_js_login'));
      }
        
      $fbUserData = array();
      $fbUserData['s_oauth_provider'] = 'facebook';
      $fbUserData['s_oauth_uid'] = $auth_id;
      $fbUserData['s_name'] = $name;
      $fbUserData['s_email'] = $email;
      $fbUserData['s_picture'] = htmlspecialchars_decode($picture);


      $generate_username = '';
      
      // Generate nice username
      if(function_exists('osc_username_generator') && osc_username_generator() == 'SLUG') {
        $generate_username = osc_sanitize_username($fbUserData['s_name'] != '' ? $fbUserData['s_name'] : $fbUserData['s_email']);
        $username_taken = User::newInstance()->findByUsername($generate_username);
        
        if($username_taken != false) {
          $generate_username .= random_int(1000, 9999);
          
          $username_taken = User::newInstance()->findByUsername($generate_username);
          
          if($username_taken != false) {
            $generate_username = '';
          }
        }
        
        if(osc_is_username_blacklisted($generate_username)) {
          $generate_username = '';
        }
      }
      
      $fbUserData['s_username'] = $generate_username;
      
      $user_id = ModelFJL::newInstance()->updateUserFBData($fbUserData);


      // LOGIN NOW!
      if($user_id > 0) {
        $code = fjl_login_user($user_id);

        if($code == 3) {
          require_once osc_lib_path() . 'osclass/helpers/hSecurity.php';
          $secret = osc_genRandomPassword();

          ModelFJL::newInstance()->updateUserSecret($user_id, $secret);

          // mb_set_cookie('oc_userId', $user_id);
          // mb_set_cookie('oc_userSecret', $secret);
          Cookie::newInstance()->set_expires(osc_time_cookie());
          Cookie::newInstance()->push('oc_userId', $user_id);
          Cookie::newInstance()->push('oc_userSecret', $secret);
          Cookie::newInstance()->set();
        }

        // Flash messages
        if($code == 0) {
          osc_add_flash_error_message(__('This account does not exist.', 'facebook_js_login'));
        } else if($code == 1) {
          osc_add_flash_error_message(__('This account has not been activated.', 'facebook_js_login'));
        } else if($code == 2) {
          osc_add_flash_error_message(__('This account has been blocked.', 'facebook_js_login'));
        } else if($code == 3) {
          osc_add_flash_ok_message(__('You have been successfully logged in.', 'facebook_js_login'));
        } else if($code == 11) {
          osc_add_flash_error_message(__('Your current email is not allowed.', 'facebook_js_login'));
        } else if($code == 12) {
          osc_add_flash_error_message(__('Your current IP is not allowed.', 'facebook_js_login'));
        } else if($code == 13) {
          osc_add_flash_error_message(__('Your current email & IP is not allowed.', 'facebook_js_login'));
        }  

        $user = User::newInstance()->findByPrimaryKey($user_id);
        $url_redirect = osc_user_dashboard_url();
        osc_run_hook('after_login', $user, $url_redirect);
        exit;
      } else {
        osc_add_flash_error_message(__('User not found or could not be created.', 'facebook_js_login'));
        exit;
      }
      
    } else {
      // token is not verified or expired
      osc_add_flash_error_message(__('Login has failed.', 'facebook_js_login'));
      exit;
    }
  }
}

osc_add_hook('init', 'fjl_login_callback', 2);


// INSTALL FUNCTION - DEFINE VARIABLES
function fjl_call_after_install() {
  osc_set_preference('enabled', 0, 'plugin-facebook_js_login', 'INTEGER');
  osc_set_preference('enable_autologin', 1, 'plugin-facebook_js_login', 'INTEGER');
  osc_set_preference('hook_button_top', 0, 'plugin-facebook_js_login', 'INTEGER');
  osc_set_preference('hook_button_bot', 0, 'plugin-facebook_js_login', 'INTEGER');
  osc_set_preference('app_id', '', 'plugin-facebook_js_login', 'STRING');
  osc_set_preference('app_secret', '', 'plugin-facebook_js_login', 'STRING');
  osc_set_preference('custom_selector', '', 'plugin-facebook_js_login', 'STRING');
  osc_set_preference('exclude_pages', 'CONTACT,PAGE,ITEM-ITEM_EDIT', 'plugin-facebook_js_login', 'STRING');

  ModelFJL::newInstance()->install();
}


function fjl_call_after_uninstall() {
  ModelFJL::newInstance()->uninstall();
}



// ADMIN MENU
function fjl_menu($title = NULL) {
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/facebook_js_login/css/admin.css?v=' . date('YmdHis') . '" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/facebook_js_login/css/bootstrap-switch.css" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/facebook_js_login/css/tipped.css" rel="stylesheet" type="text/css" />';
  echo '<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/facebook_js_login/js/admin.js"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/facebook_js_login/js/tipped.js"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/facebook_js_login/js/bootstrap-switch.js"></script>';

  if($title == '') { $title = __('Configure', 'facebook_js_login'); }

  $text  = '<div class="mb-head">';
  $text .= '<div class="mb-head-left">';
  $text .= '<h1>' . $title . '</h1>';
  $text .= '<h2>Facebook Instant Login Plugin</h2>';
  $text .= '</div>';
  $text .= '<div class="mb-head-right">';
  $text .= '<ul class="mb-menu">';
  $text .= '<li><a href="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=facebook_js_login/admin/configure.php"><i class="fa fa-wrench"></i><span>' . __('Configure', 'facebook_js_login') . '</span></a></li>';
  $text .= '</ul>';
  $text .= '</div>';
  $text .= '</div>';

  echo $text;
}



// ADMIN FOOTER
function fjl_footer() {
  $pluginInfo = osc_plugin_get_info('facebook_js_login/index.php');
  $text  = '<div class="mb-footer">';
  $text .= '<a target="_blank" class="mb-developer" href="https://osclasspoint.com"><img src="https://osclasspoint.com/favicon.ico" alt="MB Themes" /> osclasspoint.com</a>';
  $text .= '<a target="_blank" href="' . $pluginInfo['support_uri'] . '"><i class="fa fa-bug"></i> ' . __('Report Bug', 'facebook_js_login') . '</a>';
  $text .= '<a target="_blank" href="https://forums.osclasspoint.com/"><i class="fa fa-comments"></i> ' . __('Support Forums', 'facebook_js_login') . '</a>';
  $text .= '<a target="_blank" class="mb-last" href="mailto:info@osclasspoint.com"><i class="fa fa-envelope"></i> ' . __('Contact Us', 'facebook_js_login') . '</a>';
  $text .= '<span class="mb-version">v' . $pluginInfo['version'] . '</span>';
  $text .= '</div>';

  return $text;
}



// ADD MENU LINK TO PLUGIN LIST
function fjl_admin_menu() {
echo '<h3><a href="#">Facebook Instant Login Plugin</a></h3>
<ul> 
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/configure.php') . '">&raquo; ' . __('Configure', 'facebook_js_login') . '</a></li>
</ul>';
}


// ADD MENU TO PLUGINS MENU LIST
osc_add_hook('admin_menu','fjl_admin_menu', 1);



// DISPLAY CONFIGURE LINK IN LIST OF PLUGINS
function fjl_conf() {
  osc_admin_render_plugin(osc_plugin_path(dirname(__FILE__)) . '/admin/configure.php');
}

osc_add_hook(osc_plugin_path(__FILE__) . '_configure', 'fjl_conf');	


// CALL WHEN PLUGIN IS ACTIVATED - INSTALLED
osc_register_plugin(osc_plugin_path(__FILE__), 'fjl_call_after_install');

// SHOW UNINSTALL LINK
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'fjl_call_after_uninstall');

?>