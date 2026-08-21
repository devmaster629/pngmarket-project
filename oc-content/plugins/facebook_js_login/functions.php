<?php

// CREATE LOGIN BUTTON
function fjl_login_button($hooked = false) {
  if(fjl_param('enabled') == 1 && fjl_param('app_id') != '') {
    return '<a target="_top" href="javascript:void(0);" class="fl-button fjl-button' . ($hooked ? ' fjl-hooked' : '') . '" onclick="fjlCheckLoginState();"><img src="' . osc_base_url() . 'oc-content/plugins/facebook_js_login/img/fb_login.png" alt="' . osc_esc_html(__('Facebook login', 'facebook_login')) . '"/>' . __('Log in with Facebook', 'facebook_login') . '</a>';
  }
}

// Auto-hook to registration form
osc_add_hook('user_pre_register_form', function() {
  if(fjl_param('hook_button_top') == 1) {
    echo fjl_login_button(true);
  }
});

osc_add_hook('user_register_form', function() {
  if(fjl_param('hook_button_bot') == 1) {
    echo fjl_login_button(true);
  }
});

  
// Auto-hook to login form
osc_add_hook('user_pre_login_form', function() {
  if(fjl_param('hook_button_top') == 1) {
    echo fjl_login_button(true);
  }
});

osc_add_hook('user_login_form', function() {
  if(fjl_param('hook_button_bot') == 1) {
    echo fjl_login_button(true);
  }
});



// REPLACE OLD PLUGIN IF WAS EXPECTED
if(function_exists('facebook_login')) {
  function facebook_login() {
    return fjl_login_button();
  }
}


if(function_exists('facebook_login_link')) {
  function facebook_login_link() {
    return '#';
  }
}

if(function_exists('fl_call_after_install')) {
  function fl_call_after_install() {
    return false;
  }
}


// AFTER LOGOUT SET COOKIES TO AVOID AUTOLOGIN
function fjl_after_logout() {
  Cookie::newInstance()->set_expires(osc_time_cookie());
  Cookie::newInstance()->push('fjl_user_logged_out', 1);
  Cookie::newInstance()->set();
}

osc_add_hook('logout', 'fjl_after_logout', 1);


// CHECK IF PAGE IS ALLOWED TO SHOW LOGIN BOX
function fjl_check_page() {
  $excluded_pages = explode(',', fjl_param('exclude_pages'));
  
  $location = strtoupper(osc_get_osclass_location() <> '' ? osc_get_osclass_location() : 'home');
  $section = strtoupper(osc_get_osclass_section());
  
  $code = implode('-', array_filter(array($location, $section)));
  
  if(in_array($code, $excluded_pages)) {
    return false;
  }
  
  if(defined('OC_ADMIN') && OC_ADMIN === true) {
    return false;
  }
  
  return true;
}


// DEFINE ALL PAGES
function fjl_pages() {
  return array(
    'HOME' => __('Home', 'facebook_js_login'),
    'CONTACT' => __('Contact', 'facebook_js_login'),
    'PAGE' => __('Static pages', 'facebook_js_login'),
    'SEARCH' => __('Search', 'facebook_js_login'),
    'ITEM' => __('Item', 'facebook_js_login'),
    'ITEM-ITEM_ADD' => __('Add item', 'facebook_js_login'),
    'ITEM-ITEM_EDIT' => __('Edit item', 'facebook_js_login'),
    'CUSTOM' => __('Plugin pages', 'facebook_js_login'),
    'LOGIN-RECOVER' => __('Forgot password', 'facebook_js_login'),
    'LOGIN' => __('Login', 'facebook_js_login'),
    'REGISTER-REGISTER' => __('Register', 'facebook_js_login')
  );  
}


// LOGIN USER
function fjl_login_user($user_id) {
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


// Add info that user used Google Login to register in oc-admin > Users
function fjl_extend_manage_users($row, $aRow) {
  $user_id = $aRow['pk_i_id'];
  $user_exist = false;
  $manager = User::newInstance();
  $manager->dao->select();
  $manager->dao->from(DB_TABLE_PREFIX . 't_user_facebook_js_login');
  $manager->dao->where('fk_i_user_id', $user_id);
  $result = $manager->dao->get();

  if($result != false) {
    if($result->result()!=array()) {
      $row['email'] = $row['email'] . ' - '. __('via Facebook JS Login Plugin', 'facebook_js_login');
    }
  }

  return $row;
}

osc_add_filter('users_processing_row', 'fjl_extend_manage_users');


// Add info to email title that user used Google Login to register
function fjl_extend_title($title) {
  return $title . ' - '. __('via Facebook JS Login', 'facebook_js_login');
}


function fjl_extend_email_manage($user) {
  $manager = User::newInstance();
  $manager->dao->select();
  $manager->dao->from(DB_TABLE_PREFIX . 't_user_facebook_js_login');
  $manager->dao->where('fk_i_user_id', $user['pk_i_id'] );
  $result = $manager->dao->get();

  if($result != false) {
    if($result->result()!=array()) {
      osc_add_filter('email_user_registration_title', 'fjl_extend_title');
    }
  }
}

osc_add_hook('hook_email_user_registration', 'fjl_extend_email_manage', 5);
osc_add_hook('hook_email_admin_new_user', 'fjl_extend_email_manage', 5);



// CHECK IF RUNNING ON DEMO
function fjl_is_demo($ignore_admin = false) {
  if(!$ignore_admin && osc_logged_admin_username() == 'admin') {
    return false;
  } else if(isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'mb-themes') !== false || strpos($_SERVER['HTTP_HOST'], 'abprofitrade') !== false)) {
    return true;
  } else {
    return false;
  }
}


// CORE FUNCTIONS
function fjl_param($name) {
  return osc_get_preference($name, 'plugin-facebook_js_login');
}


if(!function_exists('mb_param_update')) {
  function mb_param_update($param_name, $update_param_name, $type = NULL, $plugin_var_name = NULL) {
  
    $val = '';
    if($type == 'check') {

      // Checkbox input
      if(Params::getParam($param_name) == 'on') {
        $val = 1;
      } else {
        if(Params::getParam($update_param_name) == 'done') {
          $val = 0;
        } else {
          $val = (osc_get_preference($param_name, $plugin_var_name) != '') ? osc_get_preference($param_name, $plugin_var_name) : '';
        }
      }
    } else {

      // Other inputs (text, password, ...)
      if(Params::getParam($update_param_name) == 'done' && Params::existParam($param_name)) {
        $val = Params::getParam($param_name);
      } else {
        $val = (osc_get_preference($param_name, $plugin_var_name) != '') ? osc_get_preference($param_name, $plugin_var_name) : '';
      }
    }


    // If save button was pressed, update param
    if(Params::getParam($update_param_name) == 'done') {

      if(osc_get_preference($param_name, $plugin_var_name) == '') {
        osc_set_preference($param_name, $val, $plugin_var_name, 'STRING');  
      } else {
        $dao_preference = new Preference();
        $dao_preference->update(array("s_value" => $val), array("s_section" => $plugin_var_name, "s_name" => $param_name));
        osc_reset_preferences();
        unset($dao_preference);
      }
    }

    return $val;
  }
}

if(!function_exists('mb_generate_rand_int')) {
  function mb_generate_rand_int($length = 18) {
    $characters = '0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
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


if(!function_exists('message_ok')) {
  function message_ok($text) {
    $final  = '<div class="flashmessage flashmessage-ok flashmessage-inline">';
    $final .= $text;
    $final .= '</div>';
    echo $final;
  }
}


if(!function_exists('message_error')) {
  function message_error($text) {
    $final  = '<div class="flashmessage flashmessage-error flashmessage-inline">';
    $final .= $text;
    $final .= '</div>';
    echo $final;
  }
}

if(!function_exists('osc_content_url')) {
  function osc_content_url() {
    if(!defined('CONTENT_WEB_PATH')) {
      $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
      return $protocol . $_SERVER['HTTP_HOST'] . '/oc-content/';
    } else {
      return CONTENT_WEB_PATH;
    }
  }
}

if(!function_exists('osc_content_path')) {
  function osc_content_path() {
    if(!defined('CONTENT_PATH')) {
      return ABS_PATH . 'oc-content/';
    } else {    
      return CONTENT_PATH;
    }
  }
}

?>