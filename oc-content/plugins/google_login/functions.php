<?php

// LOGOUT USER
function ggl_user_logout($user_id) {
  $gClient = ggl_initialize();

  unset($_SESSION['token']);
  unset($_SESSION['userData']);

  $gClient->revokeToken();
}

osc_add_hook('logout_user', 'ggl_user_logout');


// LOGIN USER
function ggl_user_login($user_id) {
  $user = User::newInstance()->findByPrimaryKey($user_id);

  if($user === false) { 
    return 0; 
  } else if($user['b_active'] != 1) { 
    return 1; 
  } else if($user['b_enabled'] != 1) { 
    return 2; 
  }
  
  $banned = osc_is_banned($user['s_email']); // int 0: not banned or unknown, 1: email is banned, 2: IP is banned, 3: both email & IP are banned
  if($banned !== 0) {
    return 10 + $banned;
  }
  
  Session::newInstance()->_set('userId', $user['pk_i_id']);
  Session::newInstance()->_set('userName', $user['s_name']);
  Session::newInstance()->_set('userEmail', $user['s_email']);
  Session::newInstance()->_set('userPhone', ($user['s_phone_mobile'] ? $user['s_phone_mobile'] : $user['s_phone_land']));

  return 3;
}


// LOGIN LINK
function ggl_login_link($only_link = 1) {
  $gClient = ggl_initialize();

  $authUrl = $gClient->createAuthUrl();
  $link = filter_var($authUrl, FILTER_SANITIZE_URL);

  if($only_link == 1) {
    $output = $link;
  } else {
    $output =  '<style>';
    $output .= '#ggl-login-link {display:inline-block;height:40px;width:auto;}';
    $output .= '#ggl-login-link img {display:inline-block;max-width:100%;max-height:46px;width:auto;height:46px;margin:-3px 0;}';
    $output .= '</style>';
    
    $output .= '<a id="ggl-login-link" href="' . $link . '"><img src="' . osc_base_url() . 'oc-content/plugins/google_login/img/btn_google_signin_dark_normal_web@2x.png" alt="' . osc_esc_html(__('Login with Google', 'google_login')) . '"/></a>';
  }

  return $output;
}

function ggl_login_button() {
  return ggl_login_link(0);
}


// RETRO-COMPATIBILITY / REPLACEMENT OF GOOGLE CONNECT PLUGIN
if(!function_exists('gc_login_button')) {
  function gc_login_button($link_only = '') {
    echo ggl_login_link(1);
  }
}


// Add info that user used Google Login to register in oc-admin > Users
function ggl_extend_manage_users($row, $aRow) {
  $user_id = $aRow['pk_i_id'];
  $user_exist = false;
  $manager = User::newInstance();
  $manager->dao->select();
  $manager->dao->from(DB_TABLE_PREFIX . 't_user_google_login');
  $manager->dao->where('fk_i_user_id', $user_id);
  $result = $manager->dao->get();

  if($result != false) {
    if($result->result()!=array()) {
      $row['email'] = $row['email'] . ' - '. __('via Google Login Plugin', 'google_login');
    }
  }

  return $row;
}

osc_add_filter('users_processing_row', 'ggl_extend_manage_users');


// Add info to email title that user used Google Login to register
function ggl_extend_title($title) {
  return $title . ' - '. __('via Google', 'google_login');
}


function ggl_extend_email_manage($user) {
  $manager = User::newInstance();
  $manager->dao->select();
  $manager->dao->from(DB_TABLE_PREFIX . 't_user_google_login');
  $manager->dao->where('fk_i_user_id', $user['pk_i_id'] );
  $result = $manager->dao->get();

  if($result != false) {
    if($result->result()!=array()) {
      osc_add_filter('email_user_registration_title', 'ggl_extend_title');
    }
  }
}

osc_add_hook('hook_email_user_registration', 'ggl_extend_email_manage', 5);
osc_add_hook('hook_email_admin_new_user', 'ggl_extend_email_manage', 5);




// CORE FUNCTIONS
function ggl_param($name) {
  return osc_get_preference($name, 'plugin-google_login');
}


if(!function_exists('mb_param_update')) {
  function mb_param_update( $param_name, $update_param_name, $type = NULL, $plugin_var_name = NULL ) {
  
    $val = '';
    if( $type == 'check') {

      // Checkbox input
      if( Params::getParam( $param_name ) == 'on' ) {
        $val = 1;
      } else {
        if( Params::getParam( $update_param_name ) == 'done' ) {
          $val = 0;
        } else {
          $val = ( osc_get_preference( $param_name, $plugin_var_name ) != '' ) ? osc_get_preference( $param_name, $plugin_var_name ) : '';
        }
      }
    } else {

      // Other inputs (text, password, ...)
      if( Params::getParam( $update_param_name ) == 'done' && Params::existParam($param_name)) {
        $val = Params::getParam( $param_name );
      } else {
        $val = ( osc_get_preference( $param_name, $plugin_var_name) != '' ) ? osc_get_preference( $param_name, $plugin_var_name ) : '';
      }
    }


    // If save button was pressed, update param
    if( Params::getParam( $update_param_name ) == 'done' ) {

      if(osc_get_preference( $param_name, $plugin_var_name ) == '') {
        osc_set_preference( $param_name, $val, $plugin_var_name, 'STRING');  
      } else {
        $dao_preference = new Preference();
        $dao_preference->update( array( "s_value" => $val ), array( "s_section" => $plugin_var_name, "s_name" => $param_name ));
        osc_reset_preferences();
        unset($dao_preference);
      }
    }

    return $val;
  }
}


// CHECK IF RUNNING ON DEMO
function ggl_is_demo() {
  if(osc_logged_admin_username() == 'admin') {
    return false;
  } else if(isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'],'mb-themes') !== false || strpos($_SERVER['HTTP_HOST'],'abprofitrade') !== false)) {
    return true;
  } else {
    return false;
  }
}


if(!function_exists('message_ok')) {
  function message_ok( $text ) {
    $final  = '<div class="flashmessage flashmessage-ok flashmessage-inline">';
    $final .= $text;
    $final .= '</div>';
    echo $final;
  }
}


if(!function_exists('message_error')) {
  function message_error( $text ) {
    $final  = '<div class="flashmessage flashmessage-error flashmessage-inline">';
    $final .= $text;
    $final .= '</div>';
    echo $final;
  }
}



// COOKIES WORK
if(!function_exists('mb_set_cookie')) {
  function mb_set_cookie($name, $val) {
    Cookie::newInstance()->set_expires( 86400 * 30 );
    Cookie::newInstance()->push($name, $val);
    Cookie::newInstance()->set();
  }
}


if(!function_exists('mb_get_cookie')) {
  function mb_get_cookie($name) {
    return Cookie::newInstance()->get_value($name);
  }
}

if(!function_exists('mb_drop_cookie')) {
  function mb_drop_cookie($name) {
    Cookie::newInstance()->pop($name);
  }
}


if(!function_exists('mb_generate_rand_string')) {
  function mb_generate_rand_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
  }
}


?>