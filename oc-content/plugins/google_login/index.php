<?php
/*
  Plugin Name: Google Login Plugin
  Plugin URI: https://osclasspoint.com/osclass-plugins/social-and-authentication/google-login-osclass-plugin_i100
  Description: Allow visitors to login to your classifieds with google account
  Version: 1.3.7
  Author: MB Themes
  Author URI: https://osclasspoint.com
  Author Email: info@osclasspoint.com
  Short Name: google_login
  Plugin update URI: google_login
  Support URI: https://forums.osclasspoint.com/google-login-osclass-plugin/
  Product Key: oxs1OgtBR7lqBtNk9x0j
*/

require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'model/ModelGGL.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'functions.php';


osc_add_route('ggl-redirect', 'ggl/(.+)', 'ggl/{gglLogin}', osc_plugin_folder(__FILE__) . 'form/redirect.php');


function ggl_initialize() {
  require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'src/Google_Client.php';
  require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'src/contrib/Google_Oauth2Service.php';

  // Call Google API
  $gClient = new Google_Client();
  $gClient->setClientId(ggl_param('client_id'));
  $gClient->setClientSecret(ggl_param('client_secret'));
  $gClient->setScopes(
    array(
      'https://www.googleapis.com/auth/userinfo.email',
      'https://www.googleapis.com/auth/userinfo.profile'
    )
  );

  $gClient->setRedirectUri(osc_route_url('ggl-redirect', array('gglLogin' => 1)));

  return $gClient;
}


// READ INFORMATION - CALLBACK FROM GOOGLE
function ggl_callback($is_direct = false) {
  if(Params::getParam('gglLogin') == 1 || Params::getParam('route') == 'ggl-redirect' || $is_direct === true) {
    $gClient = ggl_initialize();
    $google_oauthV2 = new Google_Oauth2Service($gClient);


    if(isset($_GET['code'])){
      $gClient->authenticate($_GET['code']);
      $_SESSION['token'] = $gClient->getAccessToken();
      
      //header('Location: ' . filter_var(osc_route_url('ggl-redirect', array('gglLogin' => 1)), FILTER_SANITIZE_URL));
      //exit;
    }

    if(isset($_SESSION['token'])){
      $gClient->setAccessToken($_SESSION['token']);
    }


    try{
      $gClient->getAccessToken();
      $gpUserProfile = $google_oauthV2->userinfo->get();
      
      $gpUserData = array();
      $gpUserData['s_oauth_provider'] = 'google';
      $gpUserData['s_oauth_uid'] = !empty($gpUserProfile['id']) ? $gpUserProfile['id'] : '';
      $gpUserData['s_first_name'] = !empty($gpUserProfile['given_name']) ? $gpUserProfile['given_name'] : '';
      $gpUserData['s_last_name'] = !empty($gpUserProfile['family_name']) ? $gpUserProfile['family_name'] : '';
      $gpUserData['s_email'] = !empty($gpUserProfile['email']) ? $gpUserProfile['email'] : '';
      $gpUserData['s_gender'] = !empty($gpUserProfile['gender']) ? $gpUserProfile['gender'] : '';
      $gpUserData['s_locale'] = !empty($gpUserProfile['locale']) ? $gpUserProfile['locale'] : '';
      $gpUserData['s_picture'] = !empty($gpUserProfile['picture']) ? $gpUserProfile['picture'] : '';
      $gpUserData['s_link'] = !empty($gpUserProfile['link']) ? $gpUserProfile['link'] : '';


      $generate_username = '';
      
      // Generate nice username
      if(function_exists('osc_username_generator') && osc_username_generator() == 'SLUG') {
        $generate_username = osc_sanitize_username($gpUserData['s_first_name'] . $gpUserData['s_last_name']);
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
      
      $gpUserData['s_username'] = $generate_username;
      
      $user_id = ModelGGL::newInstance()->updateUser($gpUserData);
      
    } catch (\Exception $e) {
      $errors = $e->getErrors();  // all errors, contains 'message', 'domain', 'reason'

      $messages = array();
      foreach($errors as $er) {
        $messages[] = $er['message'];
      }
      
      $message_text = implode('<br/>- ', $messages);

      osc_add_flash_error_message(sprintf(__('There was problem getting access token from google, login has failed. Following errors were returned: %s', 'google_login'), $message_text));
      header('Location: ' . osc_base_url());
      exit;
      
    }
    
    // LOGIN NOW!
    $code = ggl_user_login($user_id);

    if($code == 3) {
      require_once osc_lib_path() . 'osclass/helpers/hSecurity.php';
      $secret = osc_genRandomPassword();

      ModelGGL::newInstance()->updateUserSecret($user_id, $secret);

      mb_set_cookie('oc_userId', $user_id);
      mb_set_cookie('oc_userSecret', $secret);
    }

    // Flash messages
    if($code == 0) {
      osc_add_flash_error_message(__('This account does not exist.', 'google_login'));
    } else if ($code == 1) {
      osc_add_flash_error_message(__('This account has not been activated.', 'google_login'));
    } else if ($code == 2) {
      osc_add_flash_error_message(__('This account has been blocked.', 'google_login'));
    } else if ($code == 3) {
      osc_add_flash_ok_message(__('You have been successfully logged in.', 'google_login'));
    } else if ($code == 11) {
      osc_add_flash_error_message(__('Your current email is not allowed.', 'google_login'));
    } else if ($code == 12) {
      osc_add_flash_error_message(__('Your current IP is not allowed.', 'google_login'));
    } else if ($code == 13) {
      osc_add_flash_error_message(__('Your current email & IP is not allowed.', 'google_login'));
    }  

    $url_redirect = osc_user_dashboard_url();
    
    if($user_id > 0) {
      $user = User::newInstance()->findByPrimaryKey($user_id);
      osc_run_hook('after_login', $user, $url_redirect);
    }
    
    header('Location: ' . osc_apply_filter('correct_login_url_redirect', $url_redirect));
    exit;
  }
}

osc_add_hook('init', 'ggl_callback', 1);



// INSTALL FUNCTION - DEFINE VARIABLES
function ggl_call_after_install() {
  osc_set_preference('client_id', '', 'plugin-google_login', 'STRING');
  osc_set_preference('client_secret', 1, 'plugin-google_login', 'INTEGER');

  ModelGGL::newInstance()->install();
}


function ggl_call_after_uninstall() {
  ModelGGL::newInstance()->uninstall();
}




// ADMIN MENU
function ggl_menu($title = NULL) {
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/google_login/css/admin.css?v=' . date('YmdHis') . '" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/google_login/css/bootstrap-switch.css" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/google_login/css/tipped.css" rel="stylesheet" type="text/css" />';
  echo '<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/google_login/js/admin.js?v=' . date('YmdHis') . '"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/google_login/js/tipped.js"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/google_login/js/bootstrap-switch.js"></script>';



  if( $title == '') { $title = __('Configure', 'google_login'); }

  $text  = '<div class="mb-head">';
  $text .= '<div class="mb-head-left">';
  $text .= '<h1>' . $title . '</h1>';
  $text .= '<h2>Google Login Plugin</h2>';
  $text .= '</div>';
  $text .= '<div class="mb-head-right">';
  $text .= '<ul class="mb-menu">';
  $text .= '<li><a href="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=google_login/admin/configure.php"><i class="fa fa-wrench"></i><span>' . __('Configure', 'google_login') . '</span></a></li>';
  $text .= '</ul>';
  $text .= '</div>';
  $text .= '</div>';

  echo $text;
}



// ADMIN FOOTER
function ggl_footer() {
  $pluginInfo = osc_plugin_get_info('google_login/index.php');
  $text  = '<div class="mb-footer">';
  $text .= '<a target="_blank" class="mb-developer" href="https://osclasspoint.com"><img src="https://osclasspoint.com/favicon.ico" alt="OsclassPoint Market" /> OsclassPoint Market</a>';
  $text .= '<a target="_blank" href="' . $pluginInfo['support_uri'] . '"><i class="fa fa-bug"></i> ' . __('Report Bug', 'google_login') . '</a>';
  $text .= '<a target="_blank" href="https://forums.osclasspoint.com/"><i class="fa fa-handshake-o"></i> ' . __('Support Forums', 'google_login') . '</a>';
  $text .= '<a target="_blank" class="mb-last" href="mailto:info@osclasspoint.com"><i class="fa fa-envelope"></i> ' . __('Contact Us', 'google_login') . '</a>';
  $text .= '<span class="mb-version">v' . $pluginInfo['version'] . '</span>';
  $text .= '</div>';

  return $text;
}



// ADD MENU LINK TO PLUGIN LIST
function ggl_admin_menu() {
echo '<h3><a href="#">Google Login Plugin</a></h3>
<ul> 
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/configure.php') . '">&raquo; ' . __('Configure', 'google_login') . '</a></li>
</ul>';
}


// ADD MENU TO PLUGINS MENU LIST
osc_add_hook('admin_menu','ggl_admin_menu', 1);



// DISPLAY CONFIGURE LINK IN LIST OF PLUGINS
function ggl_conf() {
  osc_admin_render_plugin( osc_plugin_path( dirname(__FILE__) ) . '/admin/configure.php' );
}

osc_add_hook( osc_plugin_path( __FILE__ ) . '_configure', 'ggl_conf' );	


// CALL WHEN PLUGIN IS ACTIVATED - INSTALLED
osc_register_plugin(osc_plugin_path(__FILE__), 'ggl_call_after_install');

// SHOW UNINSTALL LINK
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'ggl_call_after_uninstall');

?>