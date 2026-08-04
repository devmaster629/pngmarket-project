<?php
define('EPSILON_THEME_VERSION', '1.7.5');
define('USER_MENU_ICONS', 1);
define('PRELOAD_CATEGORIES', true);
define('THEME_COMPATIBLE_WITH_OSCLASS_HOOKS', 830);       // Compatibility with new hooks up to version

if(!defined('OC_ADMIN')) { 
  define('THEME_ITEM_TABLE', 't_item_epsilon');
  define('THEME_CATEGORY_TABLE', 't_category_epsilon'); 
}


require_once osc_base_path() . 'oc-content/themes/epsilon/model/ModelEPS.php';


// GET THEME INFO FROM INDEX.PHP
function eps_theme_info() {
  return WebThemes::newInstance()->loadThemeInfo('epsilon');
}


// GET FOOTER SOCIAL LINK
function eps_get_social_link($type) {
  $url = '';
  
  if(eps_param('footer_social_define') == 1) {
    switch($type) {
      case 'whatsapp': $url = eps_param('footer_social_whatsapp'); break;
      case 'facebook': $url = eps_param('footer_social_facebook'); break;
      case 'pinterest': $url = eps_param('footer_social_pinterest'); break;
      case 'instagram': $url = eps_param('footer_social_instagram'); break;
      case 'x': $url = eps_param('footer_social_x'); break;
      case 'linkedin': $url = eps_param('footer_social_linkedin'); break;
    }
    
  } else {
    $share_url = urlencode(osc_is_ad_page() ? osc_item_url() : osc_base_url());

    switch($type) {
      case 'whatsapp': $url = 'whatsapp://send?text=' . $share_url; break;
      case 'facebook': $url = 'https://www.facebook.com/sharer/sharer.php?u=' . $share_url; break;
      case 'pinterest': $url = 'https://pinterest.com/pin/create/button/?url=' . $share_url . '&media=' . eps_logo(true) . '&description='; break;
      case 'instagram': $url = eps_param('footer_social_instagram'); break;
      case 'x': $url = 'https://twitter.com/home?status=' . $share_url . '%20-%20' . urlencode(eps_param('site_name')); break;
      case 'linkedin': $url = 'https://www.linkedin.com/shareArticle?mini=true&url=' . $share_url . '&title=' . urlencode(eps_param('site_name')) . '&summary=&source='; break;
    }
  }

  if(trim($url) != '') {
    return $url;
  }
  
  return false;  
}



// ADD ANY LOCATION INTO COOKIES
function eps_add_location_to_recent($form = array()) {
  if(!is_array($form) || $form == '') {
    $form = array(); 
  }
  
  $city_id = @array_values(array_filter(array(@$form['fk_i_city_id'],Params::getParam('fk_i_city_id'),Params::getParam('cityId'),Params::getParam('sCity'),Params::getParam('city'))))[0];
  $region_id = @array_values(array_filter(array(@$form['fk_i_region_id'],Params::getParam('fk_i_region_id'),Params::getParam('regionId'),Params::getParam('sRegion'),Params::getParam('region'))))[0];
  $country_code = @array_values(array_filter(array(@$form['fk_c_country_code'],Params::getParam('fk_c_country_code'),Params::getParam('countryId'),Params::getParam('sCountry'),Params::getParam('country'))))[0];
  
  $city = array();
  if($city_id != '') {
    if($city_id > 0) {
      $city = City::newInstance()->findByPrimaryKey($city_id);
    } else {
      $city = City::newInstance()->findByName($city_id);
    }
  }
  
  $region = array();
  if(!isset($city['pk_i_id']) && $region_id != '') {
    if($region_id > 0) {
      $region = Region::newInstance()->findByPrimaryKey($region_id);
    } else {
      $region = Region::newInstance()->findByName($region_id);
    }
  }

  $country = array();
  if(!isset($city['pk_i_id']) && !isset($region['pk_i_id']) && $country_code != '') {
    if(strlen($country_code) == 2) {
      $country = Country::newInstance()->findByCode($country_code);
    } else {
      $country = Country::newInstance()->findByName($country_code);
    }      
  }
  
  $location = array();
  
  if(isset($city['pk_i_id'])) {
    $location['s_name'] = $city['s_name'];
    $location['s_name_native'] = @$city['s_name_native'];
    $location['s_country'] = '';
    $location['s_region'] = '';
    $location['s_city'] = $city['s_name'];
    $location['fk_i_city_id'] = $city['pk_i_id'];
    $location['fk_i_region_id'] = $city['fk_i_region_id'];
    $location['fk_c_country_code'] = $city['fk_c_country_code'];
    $location['s_slug'] = $city['s_slug'];
    $location['d_coord_lat'] = $city['d_coord_lat'];
    $location['d_coord_long'] = $city['d_coord_long'];
    
  } else if (isset($region['pk_i_id'])) {
    $location['s_name'] = $region['s_name'];
    $location['s_name_native'] = @$region['s_name_native'];
    $location['s_country'] = '';
    $location['s_region'] = $region['s_name'];
    $location['s_city'] = '';
    $location['fk_i_city_id'] = NULL;
    $location['fk_i_region_id'] = $region['pk_i_id'];
    $location['s_slug'] = $region['s_slug'];
    $location['fk_c_country_code'] = $region['fk_c_country_code'];
    $location['d_coord_lat'] = NULL;
    $location['d_coord_long'] = NULL;
    
  } else if (isset($country['pk_c_code'])) {
    $location['s_name'] = $country['s_name'];
    $location['s_name_native'] = @$country['s_name_native'];
    $location['s_country'] = $country['s_name'];
    $location['s_region'] = '';
    $location['s_city'] = '';
    $location['fk_i_city_id'] = NULL;
    $location['fk_i_region_id'] = NULL;
    $location['fk_c_country_code'] = $country['pk_c_code'];
    $location['s_slug'] = $country['s_slug'];
    $location['d_coord_lat'] = NULL;
    $location['d_coord_long'] = NULL;
    
  } else {
    return false; 
  }
  
  $location['success']= true;
  $location['message'] = 'APPLICATION';
  $location['s_location'] = $location['s_name'];
  $location['dt_date'] = date('Y-m-d H:i:s');

  eps_location_to_recent($location);
}

osc_add_hook('header', function() { 
  if(osc_is_search_page()) {
    eps_add_location_to_recent();
  }
});

osc_add_hook('posted_item', 'eps_add_location_to_recent');
osc_add_hook('edited_item', 'eps_add_location_to_recent');
osc_add_hook('user_edit_completed', 'eps_add_location_to_recent');


// SET CORRECT DOMAIN FOR COOKIES
function eps_cookie_domain() {
  $domain = '';
  
  if(defined('COOKIE_DOMAIN') && trim(COOKIE_DOMAIN, './ ') != '') {
    $domain = trim(COOKIE_DOMAIN, './ ');   // in config, define domain without leading dot, ie website.com

  } else if(function_exists('osc_get_parent_domain') && osc_subdomain_enabled()) {
    $domain = trim(osc_get_parent_domain(), './ ');
  }

  if($domain != '' && strpos($domain, 'http') !== false) {
    $domain = preg_replace('#^https?://#', '', $domain);
  }
  
  return $domain;
}


// CHECK IF DARK MODE SHOULD BE ENABLED
function eps_is_dark_mode() {
  if(eps_param('enable_dark_mode') == 1) {
    $default = (eps_param('default_mode') == '' ? 'DARK' : eps_param('default_mode'));
    
    if($default == 'DARK' && @$_COOKIE['epsDarkMode'] != 'disable') {
      return true;
    } else if($default == 'LIGHT' && @$_COOKIE['epsDarkMode'] == 'enable') {
      return true;
    }
  }
  
  return false;  
}


// GET USERS LIST
function eps_get_users($type = '', $limit = 12) {
  if($type == 'by_items') {
    $order_col = 'i_items';
    $order_type = 'DESC';
  } else if ($type == 'by_reg_date') {
    $order_col = 'dt_reg_date';
    $order_type = 'DESC';
  }
  
  $limit = ($limit > 0 ? $limit : 12);
  
  $users = ModelEPS::newInstance()->getUsers($limit, $order_col, $order_type, true);
  
  if(is_array($users) && count($users) > 0) {
    foreach($users as $user) {
      if(!View::newInstance()->_exists('eps_user_' . $user['pk_i_id'])) {
        View::newInstance()->_exportVariableToView('eps_user_' . $user['pk_i_id'], $user);
      }
    }
  }
  
  return $users;
}


// PREPARE LATEST SEARCHES WITH LENGTH CRITERIA
function eps_get_latest_searches($limit = 20) {
  if(View::newInstance()->_exists('latest_searches')) {
    return View::newInstance()->_get('latest_searches');
  }
  
  $data = osc_get_latest_searches($limit*2);
  $output = array();
  $stop_words = strtolower(@osc_get_current_user_locale()['s_stop_words']);
  $stop_words = array_filter(array_map('trim', explode(',', $stop_words)));
  
  if(is_array($data) && count($data) > 0) {
    foreach($data as $d) {
      if(mb_strlen($d['s_search']) >= 4 && mb_strlen($d['s_search']) <= 20 && !in_array(strtolower($d['s_search']), $stop_words)) {
        $output[] = $d;
      }
    }
  }
  
  if(count($output) > $limit) {
    $output = array_slice($output, 0, $limit, false);
  }

  View::newInstance()->_exportVariableToView('latest_searches', $output);

  return $output;
}


// ITEM TO RECENTLY VIEWED
function eps_item_to_recent() {
  $item_id = Params::getParam('id');
  
  if($item_id > 0) {
    $recent = array();
    
    if(isset($_COOKIE['epsItemRecent']) && $_COOKIE['epsItemRecent'] != '') {
      $recent = json_decode(rawurldecode($_COOKIE['epsItemRecent']), true);
    }
    
    if(($key = array_search($item_id, $recent)) !== false) {
      unset($recent[$key]);
    }

    array_unshift($recent, $item_id);

    if(count($recent) > 48) {
      $recent = array_slice($recent, 0, 48, false);
    }
    
    $recent = array_values(array_filter(array_unique($recent)));
    eps_set_cookie('epsItemRecent', rawurlencode(json_encode($recent, JSON_NUMERIC_CHECK)));
  }
}

osc_add_hook('init', function() {
  if(osc_is_ad_page()) {
    eps_item_to_recent();
  }
});


// GET THEME COLOR
function eps_get_theme_color() {
  if(eps_is_demo() && isset($_COOKIE['epsCustomColor']) && $_COOKIE['epsCustomColor'] != '') {
    return osc_esc_html(rawurldecode($_COOKIE['epsCustomColor']));
  } else {
    return eps_param('color');
  }
}


// SET LOCATION TO COOKIES
function eps_set_theme_color() {
  if(eps_is_demo() && Params::getParam('setCustomColor') == 1 && Params::getParam('customColor') != '') {
    $color = osc_esc_html(Params::getParam('customColor'));

    if($color != '' && strlen($color) == 6) {
      $color = '#' . $color;
      eps_set_cookie('epsCustomColor', rawurlencode($color));
    } else {
      eps_drop_cookie('epsCustomColor');
    }
    
    if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != '') {
      header('Location:' . $_SERVER['HTTP_REFERER']);
      exit;
    } else {
      header('Location:' . osc_base_url());
      exit;
    }    
  }
}

osc_add_hook('init', 'eps_set_theme_color');


// PUBLIC PROFILE ITEMS PER PAGE
function eps_public_profile_items_per_page() {
  $section = osc_get_osclass_section();  
  if(eps_param('public_items') > 0 && osc_get_osclass_location() == 'user' && ($section == 'items' || $section == 'pub_profile')) {
    Params::setParam('itemsPerPage', eps_param('public_items'));
  }
}

osc_add_hook('init', 'eps_public_profile_items_per_page', 10);


// Osclass 8.3 filter
osc_add_filter('user_public_profile_items_per_page', function($per_page) {
  return eps_param('public_items') > 0 ? eps_param('public_items') : $per_page;
});


// ALERT ITEMS PER PAGE
function eps_public_items_per_page($per_page) {
  return (eps_param('public_items') > 0 ? eps_param('public_items') : $per_page);
}

osc_add_filter('public_items_per_page', 'eps_public_items_per_page');



// ALERT ITEMS LIMIT
function eps_alert_items_limit($per_page) {
  return (eps_param('alert_items') > 0 ? eps_param('alert_items') : $per_page);
}

osc_add_filter('limit_alert_items', 'eps_alert_items_limit');


// AJAX REQUESTS MANAGEMENT
function eps_ajax_manage() {
  if(Params::getParam('ajaxRequest') == 1) {
    error_reporting(0);
    ob_clean();
    osc_current_web_theme_path('ajax.php');
    exit;
  }
}

osc_add_hook('init', 'eps_ajax_manage');


// FIND USER PUBLIC PROFILE URL
function eps_user_public_profile_url($id) {
  if ($id > 0) {
    if (osc_rewrite_enabled()) {
      $user = eps_get_user($id);
      
      if($user !== false && isset($user['s_username'])) {
        return osc_base_url() . osc_get_preference('rewrite_user_profile') . '/' . $user['s_username'];
      }
    } else {
      return sprintf(osc_base_url(true) . '?page=user&action=pub_profile&id=%d', $id);
    }
  }
  
  return osc_base_url();
}


function eps_user_is_online($id) {
  $limit_seconds = 600; // 10 minutes

  if($id > 0) {
    $user = eps_get_user($id);

    if(isset($user['pk_i_id']) && $user['pk_i_id'] > 0) {
      $date = $user['dt_access_date'];
      
      if($date != '') {
        $last_access_date = date('Y-m-d H:i:s', strtotime($date));
        $threshold = date('Y-m-d H:i:s', strtotime(' -' . $limit_seconds . ' seconds', time()));

        if(isset($last_access_date) && $last_access_date <> '' && $last_access_date <> null && $last_access_date >= $threshold) {
          return true;
        }
      }
    }
  }

  return false;
}


// CHECK IF USER IS COMPANY
function eps_user_is_company($id) {
  if($id > 0) {
    $user = eps_get_user($id);
    if(isset($user['b_company']) && $user['b_company'] == 1) {
      return true;
    }
  }
  
  return false;
}

// GET USER DATA AND STORE INTO SESSION
function eps_get_user($id) {
  if($id > 0) {
    if(!View::newInstance()->_exists('eps_user_' . $id)) {
      View::newInstance()->_exportVariableToView('eps_user_' . $id, User::newInstance()->findByPrimaryKey($id));
    }
    
    return View::newInstance()->_get('eps_user_' . $id);
  }
  
  return false;
}


// GET ITEM MOBILE PHONE
function eps_get_item_phone() {
  $found = true;
  $code = 'OK';
  $title = __('Click to see phone number', 'epsilon');
  
  $mobile = osc_item_field('s_contact_phone');
  if($mobile == '') { $mobile = eps_item_extra(osc_item_id())['s_phone']; }      
  if($mobile == '' && function_exists('bo_mgr_show_mobile')) { $mobile = bo_mgr_show_mobile(); }
  // if($mobile == '' && osc_item_user_id() <> 0) { $mobile = $item_user['s_phone_mobile']; }      
  // if($mobile == '' && osc_item_user_id() <> 0) { $mobile = $item_user['s_phone_land']; } 
  if($mobile == null) { $mobile = ''; }

  $login_required = false;

  if(osc_item_show_phone() == 0) {
    $mobile = '';
    $title = __('No phone number', 'epsilon');
    $found = false;
    $code = 'NOT_ALLOWED';
    
  } else if(osc_get_preference('reg_user_can_see_phone', 'osclass') == 1 && !osc_is_web_user_logged_in() && strlen(trim($mobile)) >= 4) {
    //$mobile = '';
    $title = __('Login to see phone number. Click to login.', 'epsilon');
    $found = true;
    $login_required = true;
    $code = 'LOGIN_REQUIRED';
    
  } else if(trim($mobile) == '' || strlen(trim($mobile)) < 4) { 
    $mobile = '';
    $title = __('No phone number', 'epsilon');
    $found = false;
    $code = 'EMPTY';
  }
  
  return array(
    'found' => $found,
    'code' => $code,
    'login_required' => $login_required,
    'title' => $title,
    'phone' => $mobile,
    'masked' => $found ? substr($mobile, 0, strlen($mobile) - 4) . 'xxxx' : '',
    'part1' => ($found && !$login_required) ? substr($mobile, 0, strlen($mobile) - 4) : '',
    'part2' => ($found && !$login_required) ? substr($mobile, strlen($mobile) - 4) : '',
    'class' => ($found && !$login_required) ? 'masked' : '',
    'url' => ($found && $login_required) ? osc_user_login_url() : '#'
  );
}


// GET ITEM EMAIL
function eps_get_item_email() {
  $visible = true;
  $title = __('Click to see email', 'epsilon');
  $email = osc_item_contact_email();

  if(osc_item_show_email() == 0) {
    $email = '';
    $visible = false;
  }

  $data = eps_mask_email($email);
  
  return array(
    'visible' => $visible,
    'title' => $title,
    'email' => $email,
    'masked' => $visible ? @$data['masked'] : '',
    'part1' => $visible ? @$data['part1'] : '',
    'part2' => $visible ? '@' . @$data['part2'] : '',
    'class' => $visible ? 'masked' : ''
  );
}


// GET PHONE
function eps_get_phone($mobile = '') {
  $found = true;
  $code = 'OK';
  $title = __('Click to see phone number', 'epsilon');
  
  $login_required = false;
  if($mobile == null) { $mobile = ''; }

  if(osc_get_preference('reg_user_can_see_phone', 'osclass') == 1 && !osc_is_web_user_logged_in() && strlen(trim($mobile)) >= 4) {
    //$mobile = '';
    $title = __('Login to see phone number. Click to login.', 'epsilon');
    $found = true;
    $login_required = true;
    $code = 'LOGIN_REQUIRED';
  } else if(trim($mobile) == '' || strlen(trim($mobile)) < 4) { 
    $mobile = '';
    $title = __('No phone number', 'epsilon');
    $found = false;
    $code = 'EMPTY';
  }
  
  return array(
    'found' => $found,
    'code' => $code,
    'login_required' => $login_required,
    'title' => $title,
    'phone' => $mobile,
    'masked' => $found ? substr($mobile, 0, strlen($mobile) - 4) . 'xxxx' : '',
    'part1' => ($found && !$login_required) ? substr($mobile, 0, strlen($mobile) - 4) : '',
    'part2' => ($found && !$login_required) ? substr($mobile, strlen($mobile) - 4) : '',
    'class' => ($found && !$login_required) ? 'masked' : '',
    'url' => ($found && $login_required) ? osc_user_login_url() : '#'
  );
}


// EXPORT ALL CATEGORIES
// May not work in case of huge categories list, disable via PRELOAD_CATEGORIES constant
function eps_get_categories() {
  if(defined('PRELOAD_CATEGORIES') && PRELOAD_CATEGORIES) {
    if(!View::newInstance()->_exists('eps_categories')) {
      $categories = array();
      $categories_raw = Category::newInstance()->listEnabled();
      
      if(is_array($categories_raw) && count($categories_raw) > 0) {
        foreach($categories_raw as $c) {
          $categories[$c['pk_i_id']] = $c;
        }
      }
      
      View::newInstance()->_exportVariableToView('eps_categories', $categories);
    }
    
    return View::newInstance()->_get('eps_categories');
  }
  
  return array();
}


// GET SINGLE CATEGORY
function eps_get_category($id) {
  $categories = eps_get_categories();
  
  if(isset($categories[$id])) {
    return $categories[$id];
  }
  
  return Category::newInstance()->findByPrimaryKey($id);
}


function eps_base64url_encode($data) {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function eps_base64url_decode($data) {
  return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}


// GENERATE URL BASED ON PARAMETERS
function eps_create_url($params) {
  $params = array_filter($params);
  $url = osc_base_url(true) . '?' . http_build_query($params);
  return $url;
}


// CHANGE LOCATION IN SEARCH ROW
function eps_location_default_row() {
  $location_cookie = eps_location_from_cookies();

  if($location_cookie['success'] == true) { 
    ?>
    <div class="row defloc">
      <a href="#" class="change-search-location">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20"><path d="M256 168c-48.6 0-88 39.4-88 88s39.4 88 88 88 88-39.4 88-88-39.4-88-88-88zm0 128c-22.06 0-40-17.94-40-40s17.94-40 40-40 40 17.94 40 40-17.94 40-40 40zm240-64h-49.66C435.49 145.19 366.81 76.51 280 65.66V16c0-8.84-7.16-16-16-16h-16c-8.84 0-16 7.16-16 16v49.66C145.19 76.51 76.51 145.19 65.66 232H16c-8.84 0-16 7.16-16 16v16c0 8.84 7.16 16 16 16h49.66C76.51 366.81 145.19 435.49 232 446.34V496c0 8.84 7.16 16 16 16h16c8.84 0 16-7.16 16-16v-49.66C366.81 435.49 435.49 366.8 446.34 280H496c8.84 0 16-7.16 16-16v-16c0-8.84-7.16-16-16-16zM256 400c-79.4 0-144-64.6-144-144s64.6-144 144-144 144 64.6 144 144-64.6 144-144 144z"></path></svg>
        <?php echo sprintf(__('Searching only in %s (your default location)', 'epsilon'), osc_location_native_name_selector($location_cookie, 's_name')); ?>
        <i class="fas fa-times-circle input-clean"></i>
      </a>
    </div>
    <?php 
  }  
}

// DEFAULT CONTENT FOR PATTERN BOX
function eps_default_pattern_content() {
  echo '<div class="row minlength"><em>' . sprintf(__('Enter %s more character(s) to search...', 'epsilon'), '<span class="min">2</span>') . '</em></div>';

  echo eps_location_default_row();


  // SAVED RECENT SEARCHES
  $patterns = array_reverse(eps_get_recent_patterns());

  if(is_array($patterns) && count($patterns) > 0) {
    echo '<div class="row patterns">';
    echo '<div class="lead">' . __('Your recent search', 'epsilon') . '</div>';
    
    foreach($patterns as $p) {
      echo '<a class="option direct" href="' . osc_search_url(array('page' => 'search', 'sPattern' => $p)) . '" data-pattern="' . osc_esc_html($p) . '">' . $p . '</a>';
    }
    
    echo '</div>';
  }

  // RECENT LOCATIONS
  $locations = array_reverse(eps_get_recent_locations());
  
  if(is_array($locations) && count($locations) > 0) {
    echo '<div class="row recent locations">';
    echo '<div class="lead">' . __('Recent locations', 'epsilon') . '</div>';
    
    foreach($locations as $loc) {
      if(@$loc['fk_i_city_id'] > 0) {
        echo '<a class="option direct" href="' . osc_search_url(array('page' => 'search', 'sCity' => $loc['fk_i_city_id'])) . '" data-city="' . osc_esc_html($loc['fk_i_city_id']) . '">' . osc_location_native_name_selector($loc, 's_name') . '</a>';
      } else if (@$loc['fk_i_region_id'] > 0) {
        echo '<a class="option direct" href="' . osc_search_url(array('page' => 'search', 'sRegion' => $loc['fk_i_region_id'])) . '" data-region="' . osc_esc_html($loc['fk_i_region_id']) . '">' . osc_location_native_name_selector($loc, 's_name') . '</a>';
      } else if (@$loc['fk_c_country_code'] <> '') {
        echo '<a class="option direct" href="' . osc_search_url(array('page' => 'search', 'sCountry' => $loc['fk_c_country_code'])) . '" data-country="' . osc_esc_html($loc['fk_c_country_code']) . '">' . osc_location_native_name_selector($loc, 's_name') . '</a>';
      }
    }
    
    echo '</div>';
  }
  
  // CITIES
  $cities = ModelEPS::newInstance()->getPopularCities(6);
  
  if(is_array($cities) && count($cities) > 0) {
    echo '<div class="row cities locations">';
    echo '<div class="lead">' . __('Popular cities', 'epsilon') . '</div>';
    
    foreach($cities as $c) {
      echo '<a class="option direct" href="' . osc_search_url(array('page' => 'search', 'sCity' => $c['fk_i_city_id'])) . '" data-city="' . osc_esc_html($c['fk_i_city_id']) . '">' . osc_location_native_name_selector($c, 's_name') . ', ' . osc_location_native_name_selector($c, 's_name_top') . ($c['i_num_items'] > 0 ? ' <em>' . $c['i_num_items'] . ' ' . ($c['i_num_items'] == 1 ? __('item', 'epsilon') : __('items', 'epsilon')) . '</em>' : '') . '</a>';
    }
    
    echo '</div>';
  }
}


// CLEAN COOKIE LOCATION
function eps_clean_cookies_locations() {
  if(Params::getParam('cleanCookieLocation') == 1) {
    eps_location_to_cookies('');
    osc_add_flash_ok_message(__('Default location has been cleaned', 'epsilon'));
    header('Location:' . osc_base_url());
    exit;
  }
}

osc_add_hook('init', 'eps_clean_cookies_locations');


// SET MANUAL LOCATION
function eps_set_cookies_location_manual() {
  if(Params::getParam('manualCookieLocation') == 1) {
    $data = json_decode(base64_decode(rawurldecode(Params::getParam('hash'))), true);
    $loc = implode(', ', array_filter(array(osc_location_native_name_selector($data, 's_name'), osc_location_native_name_selector($data, 's_name_top'))));

    $location = json_encode(array(
      'success' => true,
      'message' => 'MANUAL',
      's_location' => sprintf(__('Located in %s', 'epsilon'), $loc),
      's_name' => $data['s_name'] . ($data['s_name_top'] != '' ? ', ' . $data['s_name_top'] : ''),
      's_name_native' => $data['s_name_native'] . ($data['s_name_top_native'] != '' ? ', ' . $data['s_name_top_native'] : ''),
      's_region' => @$data['s_region'],
      's_city' => @$data['s_city'],
      'fk_i_city_id' => $data['fk_i_city_id'],
      'fk_i_region_id' => $data['fk_i_region_id'],
      'fk_c_country_code' => $data['fk_c_country_code'],
      's_slug' => @$data['s_slug'],
      'd_coord_lat' => @$data['d_coord_lat'],
      'd_coord_long' => @$data['d_coord_long'],
      'dt_date' => date('Y-m-d H:i:s')
    ));

    eps_location_to_cookies($location);
    osc_add_flash_ok_message(__('Default location has been saved', 'epsilon'));
    header('Location:' . osc_base_url());
    exit;
  }
}

osc_add_hook('init', 'eps_set_cookies_location_manual');


// SET LOCATION TO COOKIES
function eps_location_to_cookies($location) {
  if($location != '') {
    eps_set_cookie('epsLocation', rawurlencode($location));
    eps_location_to_recent(json_decode($location, true));
  } else {
    eps_drop_cookie('epsLocation'); 
  }
}

// SET LOCATION AS RECENT
function eps_location_to_recent($location) {
  $recent = array();
  
  if(isset($_COOKIE['epsLocationRecent']) && $_COOKIE['epsLocationRecent'] != '') {
    $recent = eps_decode_array(rawurldecode($_COOKIE['epsLocationRecent']));
  }

  $found = false;
  if(is_array($recent) && count($recent) > 0) {
    foreach($recent as $p) {
      if(is_array($location) && is_array($p)) {
        if(@$p['fk_i_city_id'] . @$p['fk_i_region_id'] . @$p['fk_c_country_code'] == @$location['fk_i_city_id'] . @$location['fk_i_region_id'] . @$location['fk_c_country_code']) {
          $found = true;
        }
      }
    }
  }

  if(!$found) {
    $recent[] = $location;
  }

  if(count($recent) > 10) {
    $recent = array_slice($recent, -10, 10, false);
  }
  
  $recent = array_filter($recent);
 
  eps_set_cookie('epsLocationRecent', rawurlencode(eps_encode_array($recent)));
}


// GET LOCATION COOKIES
function eps_location_from_cookies() {
  if(eps_param('default_location') == 1 && isset($_COOKIE['epsLocation']) && $_COOKIE['epsLocation'] != '') {
    $data = array();
    
    if(isset($_COOKIE['epsLocation']) && $_COOKIE['epsLocation'] != '') {
      $data = json_decode(rawurldecode($_COOKIE['epsLocation']), true);
    }
    
    $data['success'] == isset($data['success']) ? $data['success'] : false;
    return $data;
  }
  
  return array('success' => false);
}

// GET RECENT LOCATIONS FROM COOKIES
function eps_get_recent_locations($limit = 6) {
  if(isset($_COOKIE['epsLocationRecent']) && $_COOKIE['epsLocationRecent'] != '') {
    $data = eps_decode_array(rawurldecode($_COOKIE['epsLocationRecent']));

    if(is_array($data) && count($data) > $limit) {
      $data = array_slice($data, -$limit, $limit, false);
    }

    return (is_array($data) ? $data : array());
  }
  
  return array();
}

// SAVE PATTERN TO COOKIES
function eps_pattern_to_cookies($pattern) {
  $patterns = array();
  
  if(isset($_COOKIE['epsPatternRecent']) && $_COOKIE['epsPatternRecent'] != '') {
    $patterns = eps_decode_array(rawurldecode($_COOKIE['epsPatternRecent']));
  }
  
  $patterns[] = $pattern;
  $patterns = array_unique(array_filter($patterns));
  
  if(count($patterns) > 30) {
    $patterns = array_slice($patterns, -30, 30, false);
  }
  
  eps_set_cookie('epsPatternRecent', rawurlencode(eps_encode_array($patterns)));
}


// GET RECENT PATTERNS FROM COOKIES
function eps_get_recent_patterns($term = '', $limit = 6) {
  if(isset($_COOKIE['epsPatternRecent']) && $_COOKIE['epsPatternRecent'] != '') {
    $data = eps_decode_array(rawurldecode($_COOKIE['epsPatternRecent']));

    if($term != '') {
      //$term = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $term);

      $data = array_filter($data, function($element) use($term){
        return stripos($element, $term) !== false;
      });
    }

    if(is_array($data) && count($data) > $limit) {
      $data = array_slice($data, -$limit, $limit, false);
    }
    
    return (is_array($data) ? $data : array());
  }
  
  return array();
}


// ENCODE ARRAY TO REDUCE SIZE
function eps_encode_array($array) {
  $array = (is_array($array) ? $array : array());
  return gzcompress(json_encode($array), 7);
}


// DECODE ARRAY
function eps_decode_array($json) {
  return @json_decode(gzuncompress((string)$json), true);
}


// FAVORITE ITEMS SUPPORT
function eps_make_favorite($item_id = NULL) {
  $item_id = ($item_id === NULL ? osc_item_id() : $item_id);

  // SAVED ITEMS PLUGIN
  if(function_exists('svi_save_btn')) {
    $options = array();     // Let's keep options to be defined in plugin settings

    echo svi_save_btn($item_id, $options);  
  
  // FAVORITE ITEMS PLUGIN
  } else if(function_exists('fi_save_favorite')) {
    $options = array(
      'icon_on' => 'fas fa-star',
      'icon_off' => 'far fa-star',
      'title_on' => __('Remove from favorites', 'epsilon'),
      'title_off' => __('Save to favorite', 'epsilon')
    );
    
    echo '<div class="favorite">' . fi_save_favorite($item_id, $options) . '</div>';
  }
}


// GET FAVORITED ITEMS
function eps_favorited_items($limit = 4) {
  //$limit = (eps_param('favorite_count') > 0 ? eps_param('favorite_count') : 16);

  // SEARCH ITEMS IN LIST AND CREATE ITEM ARRAY
  $aSearch = new Search();
  $aSearch->addField(sprintf('count(%st_item.pk_i_id) as count_id', DB_TABLE_PREFIX) );
  $aSearch->addConditions(sprintf("%st_favorite_list.list_id = %st_favorite_items.list_id", DB_TABLE_PREFIX, DB_TABLE_PREFIX));
  $aSearch->addConditions(sprintf("%st_favorite_items.item_id = %st_item.pk_i_id", DB_TABLE_PREFIX, DB_TABLE_PREFIX));
  $aSearch->addConditions(sprintf("%st_favorite_list.user_id <> coalesce(%st_item.fk_i_user_id, 0)", DB_TABLE_PREFIX, DB_TABLE_PREFIX));
  $aSearch->addConditions(sprintf("%st_item.b_enabled = 1", DB_TABLE_PREFIX));
  $aSearch->addConditions(sprintf("%st_item.b_spam = 0", DB_TABLE_PREFIX));
  $aSearch->addConditions(sprintf("%st_item.b_active = 1", DB_TABLE_PREFIX));
  $aSearch->addTable(sprintf("%st_favorite_items", DB_TABLE_PREFIX));
  $aSearch->addTable(sprintf("%st_favorite_list", DB_TABLE_PREFIX));
  $aSearch->addGroupBy(DB_TABLE_PREFIX.'t_item.pk_i_id');
  $aSearch->order('count(*)', 'DESC');
  $aSearch->limit(0, $limit);
  $list_items = $aSearch->doSearch();

  return $list_items;
}


// GET ITEMS BASED ON USER LOCATION
function eps_location_items($location) {
  $limit = 16;
  $radius = 100; // km
  
  $mSearch = new Search();

  if(@$location['fk_c_country_code'] > 0) {
    $mSearch->addCountry($location['fk_c_country_code']);
  }
  
  if(@$location['fk_i_region_id'] > 0) {
    $mSearch->addRegion($location['fk_i_region_id']);
  }
  
  if(@$location['fk_i_city_id'] > 0) {
    $mSearch->addCity($location['fk_i_city_id']);
  }
  
  $mSearch->limit(0, $limit);
  $mSearch->addGroupBy(DB_TABLE_PREFIX.'t_item.pk_i_id');
  
  $aItems = $mSearch->doSearch(); 
  
  
  // TRY RADIUS SEARCH IN AREA
  if(count($aItems) < $limit && @$location['d_coord_lat'] <> '' && @$location['d_coord_long'] <> '') {
    $not_ids = array();
    
    if(count($aItems) > 0) {
      foreach($aItems as $ai) {
        $not_ids[] = $ai['pk_i_id'];
      }
    }
    
    $not_ids = implode(',', $not_ids);
    
    $lat = $location['d_coord_lat'];
    $lon = $location['d_coord_long'];
    $measurement = 6371;  // 3959 for miles 
    $distance_condition = sprintf('(%d * acos(cos(radians(%f)) * cos(radians(d_coord_lat)) * cos(radians(d_coord_long) - radians(%f)) + sin(radians(%f)) * sin(radians(d_coord_lat)))) <= %f', (int)$measurement, (float)$lat, (float)$lon, (float)$lat, (float)$radius);
    
    $mSearch2 = new Search();
    $mSearch2->addJoinTable( DB_TABLE_PREFIX.'t_item_location.fk_i_item_id', DB_TABLE_PREFIX.'t_item_location', DB_TABLE_PREFIX.'t_item.pk_i_id = '.DB_TABLE_PREFIX.'t_item_location.fk_i_item_id', 'INNER');

    //$mSearch2->addConditions(sprintf('(POWER(d_coord_lat - %f, 2) + POWER(d_coord_long - %f, 2) <= POWER(%f, 2))', (float)$location['d_coord_lat'], (float)$location['d_coord_long'], (float)$radius));
    $mSearch2->addConditions($distance_condition);
    
    if($not_ids <> '') {
      $mSearch2->addConditions(DB_TABLE_PREFIX.'t_item.pk_i_id NOT IN (' . $not_ids . ')');
    }

    $mSearch2->limit(0, $limit - count($aItems));
    $aItems2 = $mSearch2->doSearch(); 
    
    if(count($aItems2) > 0) {
      $aItems = (count($aItems) > 0 ? array_merge($aItems, $aItems2) : $aItems2);
    }
  }
  
  return $aItems;  
}


// HIGHLIGHT SEARCHED TERM
function eps_highlight_term($text, $substring) {
  return preg_replace('/(' . $substring . ')/i', '<u>$1</u>', $text);
}


// VERSION SCRIPTS (DEVELOPMENT)
function eps_asset_version() {
  $set = (eps_param('asset_versioning') == 1 ? true : false);
  return ($set ? '?v=' . date('YmdHis') : '');
}


// REMOVE OLD FONT AWESOME (V4)
function eps_clean_old_fonts() {
  osc_remove_style('font-open-sans');
  osc_remove_style('open-sans');
  osc_remove_style('fi_font-awesome');
  osc_remove_style('font-awesome44');
  osc_remove_style('font-awesome45');
  osc_remove_style('font-awesome47');
  osc_remove_style('cookiecuttr-style');
  osc_remove_style('responsiveslides');
  osc_remove_style('font-awesome');
}

osc_add_hook('init', 'eps_clean_old_fonts');
osc_add_hook('header', 'eps_clean_old_fonts');



// SAVE SEARCH SECTION
function eps_save_search_section($position) {
?>
<div id="search-alert" class="pos-<?php echo $position; ?>">
  <?php osc_alert_form(); ?>
</div>
<?php
}  

// ONLINE CHAT
function eps_chat_button($user_id = '') {
  if(function_exists('oc_chat_button')) {
    $html = '';
    $user_name = '';
    $text = '';
    $title = '';

    if((osc_is_ad_page() || osc_is_search_page()) && $user_id == '') {
      $user_id = osc_item_user_id();
      $user_name = osc_item_contact_name();
    }

    if($user_id <> '' && $user_id > 0) {
      $registered = 1;
      $last_active = ModelOC::newInstance()->getUserLastActive($user_id);
      $user = User::newInstance()->findByPrimaryKey($user_id);
      $user_name = @$user['s_name'];

      $active_limit = osc_get_preference('refresh_user', 'plugin-online_chat');
      $active_limit = ($active_limit > 0 ? $active_limit : 120);
      $active_limit = $active_limit + 10;

      $limit_datetime = date('Y-m-d H:i:s', strtotime(' -' . $active_limit . ' seconds', time()));
    } else {
      $registered = 0;
    }

    if($registered == 1 && $user_id <> osc_logged_user_id() && !oc_check_bans($user_id)) {
      $class = ' oc-active';
    } else {
      $class = ' oc-disabled';
    }

    if(isset($limit_datetime) && $limit_datetime <> '' && $last_active >= $limit_datetime) {
      $class .= ' oc-online';
      $title .= __('User is online', 'epsilon');
    } else {
      $class .= ' oc-offline';
      $title .= __('User is offline', 'epsilon');
    }


    //$html .= '<div class="row mob oc-chat-box' . $class . '" data-user-id="' . $user_id . '">';
    //$html .= '<i class="fa fa-comment"></i>';



    if($registered == 0) {
      $text .=  __('Chat unavailable', 'epsilon');
      $title .= ', ' . __('User is not registered', 'epsilon');
    } else {
      if($user_id == osc_logged_user_id()) {
        $text .= __('Chat unavailable', 'epsilon');
        $title .= ', ' . __('It\'s your ad', 'epsilon');
      } else if(oc_check_bans($user_id)) {
        $text .= __('Chat unavailable', 'epsilon');
        $title .= ', ' . __('User has blocked you', 'epsilon');
      } else {
        //$text .= '<span class="oc-user-top oc-status-offline">' . __('Chat unavailable', 'epsilon') . '</span>';
        $text .= '<span class="oc-user-top oc-status-online">' . __('Start chat', 'epsilon') . '</span>';
      }
    }


    $html .= '<a href="#" class="btn oc-start-chat' . $class . '" data-to-user-id="' . $user_id . '" data-to-user-name="' . osc_esc_html($user_name) . '" data-to-user-image="' . oc_get_picture($user_id) . '" title="' . osc_esc_html($title) . '">';
    //$html .= '<svg height="24" viewBox="0 0 512 512" width="24" xmlns="http://www.w3.org/2000/svg"><path d="m512 346.5c0-74.628906-50.285156-139.832031-121.195312-159.480469-4.457032-103.878906-90.347657-187.019531-195.304688-187.019531-107.800781 0-195.5 87.699219-195.5 195.5 0 35.132812 9.351562 69.339844 27.109375 99.371094l-26.390625 95.40625 95.410156-26.386719c27.605469 16.324219 58.746094 25.519531 90.886719 26.90625 19.644531 70.914063 84.851563 121.203125 159.484375 121.203125 29.789062 0 58.757812-7.933594 84.210938-23.007812l80.566406 22.285156-22.285156-80.566406c15.074218-25.453126 23.007812-54.421876 23.007812-84.210938zm-411.136719-15.046875-57.117187 15.800781 15.800781-57.117187-3.601563-5.632813c-16.972656-26.554687-25.945312-57.332031-25.945312-89.003906 0-91.257812 74.242188-165.5 165.5-165.5s165.5 74.242188 165.5 165.5-74.242188 165.5-165.5 165.5c-31.671875 0-62.445312-8.972656-89.003906-25.945312zm367.390625 136.800781-42.382812-11.726562-5.660156 3.683594c-21.941407 14.253906-47.433594 21.789062-73.710938 21.789062-58.65625 0-110.199219-37.925781-128.460938-92.308594 89.820313-10.355468 161.296876-81.832031 171.65625-171.65625 54.378907 18.265625 92.304688 69.808594 92.304688 128.464844 0 26.277344-7.535156 51.769531-21.789062 73.710938l-3.683594 5.660156zm0 0"/><path d="m180.5 271h30v30h-30zm0 0"/><path d="m225.5 150c0 8.519531-3.46875 16.382812-9.765625 22.144531l-35.234375 32.25v36.605469h30v-23.394531l25.488281-23.328125c12.398438-11.347656 19.511719-27.484375 19.511719-44.277344 0-33.085938-26.914062-60-60-60s-60 26.914062-60 60h30c0-16.542969 13.457031-30 30-30s30 13.457031 30 30zm0 0"/></svg>';
    $html .= '<i class="fas fa-comment-alt"></i>';
    $html .= '<span>' . $text . '</span>';
    $html .= '<em class="' . $class . '"></em>';
    $html .= '</a>';

    //$html .= '</div>';

    return $html;
  } else {
    return false;
  }
}


// ON POST/EDIT PAGE TO GET SESSION
function eps_post_item_title() {
  $title = osc_item_title();
  foreach(osc_get_locales() as $locale) {
    if(Session::newInstance()->_getForm('title') != "") {
      $title_ = Session::newInstance()->_getForm('title');
      if(@$title_[$locale['pk_c_code']] != ""){
        $title = $title_[$locale['pk_c_code']];
      }
    }
  }
  return $title;
}


// ON POST/EDIT PAGE TO GET SESSION
function eps_post_item_description() {
  $description = osc_item_description();
  foreach(osc_get_locales() as $locale) {
    if(Session::newInstance()->_getForm('description') != "") {
      $description_ = Session::newInstance()->_getForm('description');
      if(@$description_[$locale['pk_c_code']] != ""){
        $description = $description_[$locale['pk_c_code']];
      }
    }
  }
  return $description;
}


// IDENTIFY DEVICE TYPE
function eps_device() {
  $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
  $iPod    = stripos($agent, "iPod");
  $iPhone  = stripos($agent, "iPhone");
  $iPad    = stripos($agent, "iPad");
  $Android = stripos($agent, "Android");
  $webOS   = stripos($agent, "webOS");

  //do something with this information
  if($iPod || $iPhone || $iPad) {
    return 'ios';
  } else if($Android) {
    return 'android';
  } else if($webOS) {
    return 'webos';
  }
}


// MASK EMAIL
function eps_mask_email($email) {
  if($email == '') { return false; }
  
  $em = explode('@',$email);
  $name = implode('@', array_slice($em, 0, count($em)-1));
  $domain = end($em);

  $len_name = strlen($name)-2;
  $mask_name = substr($name,0, strlen($name) - $len_name) . str_repeat('*', $len_name);
 
  $len_domain = strlen($domain) - 4;
  $mask_domain = str_repeat('*', $len_domain) . substr($domain, $len_domain, strlen($domain));

  return array(
    'email' => $email,
    'masked' => $mask_name . '@' . $mask_domain,
    'part1' => $name,
    'part2' => $domain
  );
}


// PUBLIC PROFILE ITEMS
function eps_public_profile_per_page() {
  $section = osc_get_osclass_section();  
  if(osc_get_osclass_location() == 'user' && $section == 'pub_profile') {
    Params::setParam('itemsPerPage', (eps_param('public_profile_per_page') > 0 ? eps_param('public_profile_per_page') : 24));
  }
}

osc_add_hook('init', 'eps_public_profile_per_page');


// USER ITEMS
function eps_user_items_per_page() {
  $section = osc_get_osclass_section();  
  if(osc_get_osclass_location() == 'user' && $section == 'items') {
    Params::setParam('itemsPerPage', (eps_param('user_items_per_page') > 0 ? eps_param('user_items_per_page') : 12));
  }
}

osc_add_hook('init', 'eps_user_items_per_page');


// CHECK IF LAZY LOAD ENABLED
function eps_is_lazy($disabled = false) {
  if($disabled === true) {
    return false; 
  } else if(eps_param('lazy_load') == 1) {
    return true;
  }

  return false;
}


// CHECK IF BUILT-IN BROWSER BASED LAZY LOAD ENABLED
function eps_is_lazy_browser($disabled = false) {
  if($disabled === true) {
    return false; 
  } else if(eps_param('lazy_load') == 2) {
    return true;
  }

  return false;
}

function eps_get_load_image($type = '') {
  if($type == 'transparent') {
    return osc_current_web_theme_url('images/load-image-transparent.png');
  }
  
  return osc_current_web_theme_url('images/load-image.png');
}


// GET NO IMAGE LINK
function eps_get_noimage($type = 'thumb') {
  if(file_exists(WebThemes::newInstance()->getCurrentThemePath() . 'images/no-image.svg')) {
    return osc_current_web_theme_url('images/no-image.svg');
  }
}


// HEX TO RGBA COLOR
function eps_hex_to_rgb($colour, $opacity = 1) {
  if(strlen($colour) != 7) {
    return $colour;
  }
  
  if($colour[0] == '#') {
    $colour = substr($colour, 1);
  }
  
  if(strlen($colour) == 6) {
    list($r, $g, $b) = array($colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5]);
  } elseif(strlen($colour) == 3) {
    list($r, $g, $b) = array($colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2]);
  } else {
    return false;
  }
  
  $r = hexdec($r);
  $g = hexdec($g);
  $b = hexdec($b);
  
  return 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $opacity . ')'; 
}


function eps_hex_brightness($hex, $steps = 20) {
  if(strlen($hex) != 7) {
    return $hex;
  }

  // Steps should be between -255 and 255. Negative = darker, positive = lighter
  $steps = max(-255, min(255, $steps));

  // Normalize into a six character long hex string
  $hex = str_replace('#', '', $hex);
  if (strlen($hex) == 3) {
    $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
  }

  // Split into three parts: R, G and B
  $color_parts = str_split($hex, 2);
  $return = '#';

  foreach ($color_parts as $color) {
    $color   = hexdec($color); // Convert to decimal
    $color   = max(0,min(255,$color + $steps)); // Adjust color
    $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
  }

  return $return;
}


function eps_hex_hue($hex, $steps = 20) {
  if(strlen($hex) != 7) {
    return $hex;
  }

  // Steps should be between -255 and 255. Negative = darker, positive = lighter
  $steps = max(-255, min(255, $steps));

  // Normalize into a six character long hex string
  $hex = str_replace('#', '', $hex);
  if (strlen($hex) == 3) {
    $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
  }

  // Split into three parts: R, G and B
  $color_parts = str_split($hex, 2);
  $return = '#';

  $i = 1;
  foreach ($color_parts as $color) {
    $add = ($i == 3 ? $steps : 0);
    
    $color   = hexdec($color); // Convert to decimal
    $color   = max(0,min(255,$color + $add)); // Adjust color
    $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
    $i++;
  }

  return $return;
}



// NEXT - PREV ITEM LINKS
function eps_next_prev_item($type, $category_id, $item_id) {
  $mSearch = new Search();
  $mSearch->addCategory($category_id);
  $mSearch->limit(0, 1);
  

  if($type == 'next') {
    $mSearch->addItemConditions(sprintf("%st_item.pk_i_id > %d", DB_TABLE_PREFIX, $item_id));
    $mSearch->order(sprintf("%st_item.pk_i_id", DB_TABLE_PREFIX), 'ASC');
  } else {
    $mSearch->addItemConditions(sprintf("%st_item.pk_i_id < %d", DB_TABLE_PREFIX, $item_id));
    $mSearch->order(sprintf("%st_item.pk_i_id", DB_TABLE_PREFIX), 'DESC');
  }
  
  $aItems = $mSearch->doSearch();

  if(isset($aItems[0])) {
    $item = $aItems[0];
    
    if(isset($item['pk_i_id']) && $item['pk_i_id'] > 0) {
      return osc_item_url_from_item($item);
    }
  }
  
  return false;
}


function eps_next_prev_user($type, $user_id) {
  $db_prefix = DB_TABLE_PREFIX;
  
  if($type == 'prev') {
    $cond = '<';
    $order = 'pk_i_id DESC';
  } else if($type == 'next') {
    $cond = '>';
    $order = 'pk_i_id ASC';
  }
  
  $query = "SELECT * FROM {$db_prefix}t_user WHERE pk_i_id {$cond} {$user_id} AND b_enabled = 1 AND b_active = 1 ORDER BY {$order} LIMIT 0,1;";
  $result = User::newInstance()->dao->query($query);
  
  if($result) { 
    $user = $result->row();
    
    if(isset($user['pk_i_id']) && $user['pk_i_id'] > 0) {
      return osc_user_public_profile_url($user['pk_i_id']);
    }
  }
    
  return false;
}


// RELATED ADS
function eps_related_ads($by = 'category', $card_type = 'normal', $limit = 0, $class = '') {
  if($limit <= 0) {
    $limit = (eps_param('related_count') > 0 ? eps_param('related_count') : 12);
  }
  
  if($limit <= 0) {
    $limit = 12; 
  }
  
  $mSearch = new Search();
  
  $id = 'rel-block';
  
  if($by == 'category') {
    $title = __('Related items', 'epsilon');
    $mSearch->addCategory(osc_item_category_id());
  } else if($by == 'user') {
    $title = __('Other items from this seller', 'epsilon');
    
    if(osc_item_user_id() > 0) {
      $mSearch->fromUser(osc_item_user_id());
    } else {
      $mSearch->addContactEmail(osc_item_contact_email());
    }
    
    $id = 'rel-user-block';
  } else if($by == 'user-pb-premium') {
    $title = __('Premium', 'epsilon');
    $mSearch->fromUser(osc_user_id());
    $mSearch->onlyPremium(true);
    $id = 'rel-user-pb-block';
  }  
  
  //$mSearch->withPicture(true); 
  $mSearch->limit(0, $limit);
  
  if($by != 'user-pb-premium') {
    $mSearch->addItemConditions(sprintf("%st_item.pk_i_id <> %d", DB_TABLE_PREFIX, osc_item_id()));
  }
  
  $aItems = $mSearch->doSearch(); 

  $default_items = View::newInstance()->_get('items');
  View::newInstance()->_exportVariableToView('items', $aItems); 

  if(osc_count_items() > 0) {
  ?>
    <div id="<?php echo $id; ?>" class="related type-<?php echo $by; ?> <?php echo $class; ?>">
      <h2><?php echo $title; ?></h2>
      <div class="nice-scroll-wrap nice-scroll-have-overflow">
        <div class="nice-scroll-prev"><span class="mover"><i class="fas fa-caret-left"></i></span></div>

        <div class="products grid nice-scroll no-visible-scroll">
          <?php
            $c = 1;
            while(osc_has_items()) {
              eps_draw_item($c, false, $card_type);
              $c++;
            }
          ?>
        </div>
        
        <div class="nice-scroll-next"><span class="mover"><i class="fas fa-caret-right"></i></span></div>
      </div>
    </div>
  <?php
  }

  View::newInstance()->_exportVariableToView('items', $default_items);
}


// RELATED ADS
function eps_recent_ads($card_type = 'normal', $limit = 24, $class = '', $only_items = false, $clear_btn = FALSE) {
  if($limit <= 0) {
    $limit = (eps_param('recent_count') > 0 ? eps_param('recent_count') : 12);
  }
  
  $recent = array();
  
  if(isset($_COOKIE['epsItemRecent']) && $_COOKIE['epsItemRecent'] != '') {
    $recent = json_decode(rawurldecode($_COOKIE['epsItemRecent']), true);
  }
    
  if(osc_item_id() > 0) {
    if(($key = array_search(osc_item_id(), $recent)) !== false) {
      unset($recent[$key]);
    }
  }

  if(!is_array($recent) || count($recent) <= 0) {
    return array();
  }

  $mSearch = new Search();
  $mSearch->addItemConditions(sprintf("%st_item.pk_i_id in (%s)", DB_TABLE_PREFIX, implode(',', $recent)));
  $mSearch->order(sprintf("FIELD (%st_item.pk_i_id, %s)", DB_TABLE_PREFIX, implode(',', $recent)), 'ASC');
  $mSearch->limit(0, $limit);
  $aItems = $mSearch->doSearch(); 

  if($only_items) {
    return $aItems;
  }
  
  $default_items = View::newInstance()->_get('items');
  View::newInstance()->_exportVariableToView('items', $aItems); 

  if($class == null) { $class = ''; }

  if(osc_count_items() > 0) {
  ?>
    <div id="recent-ads" class="recent-ads<?php echo (trim($class) <> '' ? ' ' . $class : ''); ?>">
      <h2>
        <span><?php _e('Recently viewed', 'epsilon'); ?></span>
        
        <?php if($clear_btn) { ?>
          <a href="#" class="btn btn-secondary mini clear-recently-viewed"><?php _e('Clear all', 'epsilon'); ?></a>
        <?php } ?>
      </h2>
      
      <div class="nice-scroll-wrap nice-scroll-have-overflow">
        <div class="nice-scroll-prev"><span class="mover"><i class="fas fa-caret-left"></i></span></div>

        <div class="products grid nice-scroll no-visible-scroll">
          <?php
            $c = 1;
            while(osc_has_items()) {
              eps_draw_item($c, false, $card_type);
              $c++;
            }
          ?>
        </div>
        
        <div class="nice-scroll-next"><span class="mover"><i class="fas fa-caret-right"></i></span></div>
      </div>
    </div>
  <?php
  }

  View::newInstance()->_exportVariableToView('items', $default_items);
}


// GET LOCALE SELECT FOR PUBLISH PAGE
function eps_locale_post_links() {
  $c = osc_current_user_locale();

  $html = '';
  $locales = osc_get_locales();

  if(count($locales) > 0) {
    $html .= '<div class="locale-links">';

    foreach($locales as $l) {
      $html .= '<a href="#" data-locale="' . $l['pk_c_code'] . '" data-name="' . $l['s_name'] . '" class="mbBg3Active' . ($c == $l['pk_c_code'] ? ' active' : '') . '">' . $l['s_short_name'] . '</a>';
    }

    $html .= '</div>';
  }

  return $html;
}


// GET PROPER PROFILE IMAGE
function eps_profile_picture($user_id = NULL, $size = 'small') {
  $user_id = ($user_id > 0 ? $user_id : 0);
  
  if(View::newInstance()->_exists('eps_profile_picture_url_' . $user_id)) {
    return View::newInstance()->_get('eps_profile_picture_url_' . $user_id);
  }
 
  // if($user_id === NULL) {
    // $user_id = osc_item_user_id();
    // $user_id = ($user_id > 0 ? $user_id : osc_premium_user_id());
  // }

  if($size == 'small') {
    $dimension = 36;
  } else if($size == 'medium') {
    $dimension = 128;
  } else {
    $dimension = 256;
  }

  $img = '';


  // GET IMAGE FROM PROFILE PICTURE FIRST
  if($user_id > 0) {
    if(function_exists('profile_picture_show')) {
      $conn = getConnection();
      $result = $conn->osc_dbFetchResult("SELECT user_id, pic_ext FROM %st_profile_picture WHERE user_id = '%d' ", DB_TABLE_PREFIX, $user_id);

      if($result > 0) { 
        $path = osc_plugins_path().'profile_picture/images/';

        if(file_exists($path . 'profile' . $user_id . $result['pic_ext'])) { 
          $img = osc_base_url() . 'oc-content/plugins/profile_picture/images/' . 'profile' . $user_id . $result['pic_ext'];
        }
      }
    } else if(osc_profile_img_users_enabled()) {
      $img = eps_user_profile_img_url($user_id);
    }
  }

  if($img == '') {
    $img = osc_current_web_theme_url('images/default-user-image.png');
  }

  View::newInstance()->_exportVariableToView('eps_profile_picture_url_' . $user_id, $img);
  
  return $img;
}


// CUSTOMIZED USER PROFILE IMG URL FUNCTION
function eps_user_profile_img_url($id = null) {
  return (string) osc_apply_filter('user_profile_img_url', osc_base_url(). 'oc-content/uploads/user-images/' . eps_user_profile_img($id));
}


// CUSTOMIZED USER PROFILE IMG FUNCTION
function eps_user_profile_img($id = null) {
  if($id === 0) {
    $img = 'default-user-image.png';
  } else if($id !== null) {
    $user = eps_get_user($id);
    $img = isset($user['s_profile_img']) ? $user['s_profile_img'] : '';
  } else {
    $img = osc_user_field("s_profile_img");
  }

  if($img === NULL || trim($img) == '') {
    $img = 'default-user-image.png';
  }

  return (string) $img;
}


// CHECK IF USER HAS PROFILE PICTURE
function eps_has_profile_picture($user_id) {
  $img = eps_profile_picture($user_id);
  
  if(strpos($img, 'no-user') !== false || strpos($img, 'default-user-image') !== false || strpos($img, 'no-image') !== false) {
    return false;
  }
  
  return true;  
}


// GET SEARCH PARAMS FOR REMOVE
function eps_search_param_remove() {
  $params = Params::getParamsAsArray();
  $output = array();

  foreach($params as $n => $v) {
    if(!in_array($n, array('page','sLocation')) && ((int)$v > 0 || $v <> '')) {
      // Skip if value is set to default
      if(in_array($n, array('bPic','bPremium','bPhone','sCondition','sTransaction','iRadius','sPeriod')) && (int)$v == 0) {
        continue;
      }
      
      if($n == 'meta') {
        $meta_values = $v;
        
        if(is_array($meta_values) && count($meta_values) > 0) {
          foreach($meta_values as $field_id => $meta_val) {
            $fields = eps_get_custom_fields();
            
            if(isset($fields[$field_id])) {
              $field = $fields[$field_id];
              
              if(isset($field['b_searchable']) && $field['b_searchable'] == 1) {
                $meta_val_name = $meta_val;
                
                if($field['e_type'] == 'CHECKBOX') {
                  $meta_val_name = ($meta_val == 1 ? __('Yes', 'epsilon') : __('No', 'epsilon'));
                }
                
                $output['meta' . $field_id] = array(
                  'value' => $meta_val, 
                  'param' => 'meta' . $field_id,
                  'title' => $field['s_name'],
                  'type' => $field['e_type'],
                  'is_meta' => true,
                  'field_id' => $field_id,
                  'name' => $meta_val_name,
                  'to_remove' => true
                );
              }
            }
          }
        }
        
      } else {
        $output[$n] = array(
          'value' => $v, 
          'param' => $n,
          'title' => eps_param_name($n),
          'type' => 'STANDARD',
          'is_meta' => false,
          'field_id' => null,
          'name' => eps_remove_value_name($v, $n, 'long'),
          'to_remove' => (in_array($n, array('sCompany')) ? false : true)
        );
      }
    }
  }

  return $output;
}


// GET NAME FOR REMOVE PARAMETER
function eps_remove_value_name($value, $type) {
  $def_cur = (eps_param('def_cur') <> '' ? eps_param('def_cur') : '$');

  if($type == 'sPeriod') {  
    return eps_get_simple_name($value, 'period');

  } else if($type == 'sTransaction') {  
    return eps_get_simple_name($value, 'transaction');

  } else if($type == 'sCondition') {  
    return eps_get_simple_name($value, 'condition');
    
  } else if($type == 'sCompany') { 
    $value_mod = $value;
    
    if(Params::existParam('sCompany')) {
      $value_mod++;
    }
    
    return eps_get_simple_name($value_mod, 'seller_type');

  } else if($type == 'sCategory' || $type == 'category') {
    if(@osc_search_category_id()[0] > 0) {
      $category = eps_get_category(osc_search_category_id()[0]);
      return $category['s_name'];
    }

  } else if($type == 'sCountry' || $type == 'country') {
    return osc_search_country();

  } else if($type == 'sRegion' || $type == 'region') {
    return osc_search_region();

  } else if($type == 'sCity' || $type == 'city') {
    return osc_search_city();
  
  } else if($type == 'sPriceMin' || $type == 'sPriceMax') {
    return $value . ' ' . $def_cur;

  } else if($type == 'sPattern') {
    return $value;

  } else if($type == 'user' || $type == 'sUser' || $type == 'userId') {
    if(is_numeric($value)) {
      $usr = User::newInstance()->findByPrimaryKey($value);
      return (@$usr['s_name'] <> '' ? @$usr['s_name'] : $value);
    } else {
      return $value;
    }
    
  } else if($type == 'notFromUserId') {
    if(is_numeric($value)) {
      $usr = User::newInstance()->findByPrimaryKey($value);
      return (@$usr['s_name'] <> '' ? @$usr['s_name'] : $value);
    } else {
      return $value;
    }

  }  else if($type == 'bPic') {
    return ($value == 1 ? __('Yes', 'epsilon') : __('No', 'epsilon'));

  }  else if($type == 'bPremium') {
    return ($value == 1 ? __('Yes', 'epsilon') : __('No', 'epsilon'));

  }  else if($type == 'bPhone') {
    return ($value == 1 ? __('Yes', 'epsilon') : __('No', 'epsilon'));

  }
}


// GET PARAMETER NICE NAME
function eps_param_name($param) {
  if($param == 'sTransaction') {
    return __('Transaction', 'epsilon');

  } else if($param == 'sCondition') {
    return __('Condition', 'epsilon');
    
  } else if($param == 'sCompany') {
    return __('Seller type', 'epsilon');

  } else if($param == 'user' || $param == 'sUser' || $param == 'userId') {
    return __('From user', 'epsilon');

  } else if($param == 'notFromUserId') {
    return __('Not from user', 'epsilon');
    
  } else if($param == 'sCategory' || $param == 'category') {
    return __('Category', 'epsilon');

  } else if($param == 'sPeriod') {
    return __('Item age', 'epsilon');

  } else if($param == 'sCountry' || $param == 'country') {
    return __('Country', 'epsilon');

  } else if($param == 'sRegion' || $param == 'region') {
    return __('Region', 'epsilon');

  } else if($param == 'sCity' || $param == 'city') {
    return __('City', 'epsilon');

  } else if($param == 'bPic') {
    return __('With picture', 'epsilon');

  } else if($param == 'bPremium') {
    return __('Only premium', 'epsilon');

  } else if($param == 'bPhone') {
    return __('With phone', 'epsilon');

  } else if($param == 'sPriceMin') {
    return __('Price min', 'epsilon');

  } else if($param == 'sPriceMax') {
    return __('Price max', 'epsilon');

  } else if($param == 'sPattern') {
    return __('Keyword', 'epsilon');

  } 

  return '';
}


// LIST AVAILABLE OPTIONS
function eps_list_options($type, $is_publish = false) {
  $opt = array();

  if($type == 'condition') {
    $opt[0] = !$is_publish ? __('All', 'epsilon') : __('Select a condition...', 'epsilon');
    $opt[1] = __('New', 'epsilon');
    $opt[2] = __('Used', 'epsilon');

  } else if($type == 'transaction') {
    $opt[0] = !$is_publish ? __('All', 'epsilon') : __('Select a transaction...', 'epsilon');
    $opt[1] = __('Sell', 'epsilon');
    $opt[2] = __('Buy', 'epsilon');
    $opt[3] = __('Rent', 'epsilon');
    $opt[4] = __('Exchange', 'epsilon');

  } else if($type == 'period') {
    $opt[0] = __('All', 'epsilon');
    $opt[1] = __('Yesterday', 'epsilon');
    $opt[7] = __('Last week', 'epsilon');
    $opt[14] = __('Last 2 weeks', 'epsilon');
    $opt[31] = __('Last month', 'epsilon');
    $opt[365] = __('Last year', 'epsilon');

  } else if($type == 'seller_type') {
    $opt[0] = __('All', 'epsilon');
    $opt[1] = __('Personal', 'epsilon');
    $opt[2] = __('Company', 'epsilon');
  }

  return $opt;
}


// GET SIMPLE OPTION NAME
function eps_get_simple_name($id, $type, $include_null = true) {
  if($include_null === false && ($id == '' || $id == 0)) {
    return '';
  }
  
  $options = eps_list_options($type);
  return @$options[$id];
}


// GET COUNTRY FLAG, IF EXISTS
function eps_country_flag_image($code) {
  if($code != '' && file_exists(osc_base_path() . 'oc-content/themes/epsilon/images/country_flags/large/' . strtolower($code) . '.png')) {
    return osc_current_web_theme_url() . 'images/country_flags/large/' . strtolower($code) . '.png';
  } 
  
  return osc_current_web_theme_url() . 'images/country_flags/large/default.png';
}


// COUNT COUNTRIES
function eps_count_countries() {
  if(!View::newInstance()->_exists('eps_contries')) {
    View::newInstance()->_exportVariableToView('eps_contries', Country::newInstance()->listAll());
  }
  
  return View::newInstance()->_count('eps_contries');
}


// GET DEFAULT CARD VIEW
function eps_get_search_view() {
  $def_view = 'grid';
  
  if(eps_param('def_view') == 1) {
    $def_view = 'list';
  } else if(eps_param('def_view') == 2) {
    $def_view = 'detail';
  }
  
  if(Params::getParam('sShowAs') == '') {
    $view = $def_view;
  } else {
    $view = (Params::getParam('sShowAs') == 'gallery' ? 'grid' : Params::getParam('sShowAs'));  
  }
  
  if(!in_array($view, array('grid', 'list', 'detail'))) {
    $view = 'grid';  
  }
  
  return $view;  
}


// GET ALL CUSTOM FIELDS
function eps_get_custom_fields() {
  if(View::newInstance()->_exists('eps_custom_fields')) {
    return View::newInstance()->_get('eps_custom_fields');
  }
  
  $output = array();
  $fields = Field::newInstance()->listAll();
  
  if(is_array($fields) && count($fields) > 0) {
    foreach($fields as $field) {
      $output[$field['pk_i_id']] = $field;
    }
  }
  
  View::newInstance()->_exportVariableToView('eps_custom_fields', $output);

  return $output;
}


// GET CORRECT FANCYBOX URL
function eps_item_fancy_url($type, $params = array()) {
  $url = eps_item_form_ajax_url($type, osc_item_id());
  
  if(osc_rewrite_enabled()) {
    // $url = '?type=' . $type;
    $login_url = osc_user_login_url() . '?loginRequired=1&type='. $type;
  } else {
    // $url = '&type=' . $type;
    $login_url = osc_user_login_url() . '&loginRequired=1&type=' . $type;
  }

  $extra = '';
  
  if($type == 'contact_public') {
    if(osc_get_preference('web_contact_form_disabled', 'osclass') == 1) {
      return '#';
    }
    
  } else if($type == 'contact') {
    if(osc_get_preference('item_contact_form_disabled', 'osclass') == 1) {
      return '#';
      
    } else if(osc_reg_user_can_contact() && !osc_is_web_user_logged_in()) {
      return $login_url;
    }
    
  } else if($type == 'comment') {
    if(osc_get_preference('enabled_comments', 'osclass') == 0) {
      return '#';
      
    } else if(osc_reg_user_post_comments() && !osc_is_web_user_logged_in()) {
      return $login_url;
    }

  } else if($type == 'friend') {
    if(osc_get_preference('item_send_friend_form_disabled', 'osclass') == 1) {
      return '#';
    }
  }

  if(!empty($params) && is_array($params)) {
    foreach($params as $n => $v) {
      $extra .= '&' . $n . '=' . $v;
    }
  }

  // return eps_item_send_friend_url() . $url . $extra;
  return $url . $extra;
}


// RECOGNIZE LOGIN URL PARAMS
function eps_login_redirect_required() {
  if(Params::getParam('loginRequired') == 1) {
    if(Params::getParam('type') == 'contact' || Params::getParam('type') == 'contact_public') {
      osc_add_flash_info_message(__('You must be logged in to contact publisher', 'epsilon'));
    } else if (Params::getParam('type') == 'comment') {
      osc_add_flash_info_message(__('You must be logged in to add a comment', 'epsilon'));
    }
  }
}

osc_add_hook('init', 'eps_login_redirect_required');


// CUSTOM SEND FRIEND URL
function eps_item_send_friend_url($item_id = '') {
  if($item_id <= 0) {
    $item_id = (osc_item_id() > 0 ? osc_item_id() : osc_premium_id());
  }

  if(osc_rewrite_enabled()) {
    return osc_base_url() . osc_get_preference('rewrite_item_send_friend') . '/' . $item_id;
  } else {
    return osc_base_url(true)."?page=item&action=send_friend&id=" . $item_id;
  }
}


// GET CORRECT BLOCK ON REGISTER PAGE
function eps_reg_url($type) {
  if(osc_rewrite_enabled()) {
    $reg_url = '?move=' . $type;
  } else {
    $reg_url = '&move=' . $type;
  }

 return osc_register_account_url() . $reg_url;
}


// UPDATE PAGINATION ARROWS
function eps_fix_arrow($data) {
  $data = str_replace('&laquo;', '<i class="fas fa-step-backward"></i>', $data);
  $data = str_replace('&raquo;', '<i class="fas fa-step-forward"></i>', $data);
  $data = str_replace('&lt;', '<i class="fas fa-angle-left"></i>', $data);
  $data = str_replace('&gt;', '<i class="fas fa-angle-right"></i>', $data);
  
  return $data;
}


// GET THEME PARAM
function eps_param($name) {
  return osc_get_preference($name, 'theme-epsilon');
}


// CHECK IF PRICE ENABLED ON CATEGORY
function eps_check_category_price($id) {
  if(!osc_price_enabled_at_items()) {
    return false;
  } else if(!isset($id) || $id == '' || $id <= 0) {
    return true;
  } else {
    $category = eps_get_category($id);
    if(isset($category['b_price_enabled'])) {
      return ($category['b_price_enabled'] == 0 ? false : true);
    }
    
    return true;
  }
}


// RTL LANGUAGE SUPPORT
function eps_is_rtl() {
  $current_lang = strtolower(osc_current_user_locale());
  $locale = osc_get_current_user_locale();
  
  if(isset($locale['b_rtl']) && $locale['b_rtl'] == 1) {
    return true;
  } else if(in_array(osc_current_user_locale(), eps_rtl_languages())) {
    return true;
  } else {
    return false;
  }
}

// GET DIRECTION STRING
function eps_language_dir() {
  return eps_is_rtl() ? 'rtl' : 'ltr';
}

// LIST ALL RTL LANGUAGES/LOCALES FOR OLDER OSCLASS VERSIONS
function eps_rtl_languages() {
  $langs = array('ar_LB','ar_DZ','ar_BH','ar_EG','ar_IQ','ar_JO','ar_KW','ar_LY','ar_MA','ar_OM','ar_SA','ar_SY','fa_IR','ar_TN','ar_AE','ar_YE','ar_TD','ar_CO','ar_DJ','ar_ER','ar_MR','ar_SD');
  return $langs;
}


// FLAT CATEGORIES CONTENT (Publish)
function eps_flat_categories() {
  return '<div id="flat-cat-fancy" style="display:none;overflow:hidden;">' . eps_category_loop() . '</div>';
}


// SMART DATE
function eps_smart_date($time) {
  $time_diff = round(abs(time() - strtotime($time)) / 60);
  $time_diff_h = floor($time_diff/60);
  $time_diff_d = floor($time_diff/1440);
  $time_diff_w = floor($time_diff/10080);
  $time_diff_m = floor($time_diff/43200);
  $time_diff_y = floor($time_diff/518400);


  if($time_diff < 2) {
    $time_diff_name = __('minute ago', 'epsilon');
  } else if($time_diff < 60) {
    $time_diff_name = sprintf(__('%d minutes ago', 'epsilon'), $time_diff);
  } else if($time_diff < 120) {
    $time_diff_name = sprintf(__('%d hour ago', 'epsilon'), $time_diff_h);
  } else if($time_diff < 1440) {
    $time_diff_name = sprintf(__('%d hours ago', 'epsilon'), $time_diff_h);
  } else if($time_diff < 2880) {
    $time_diff_name = sprintf(__('%d day ago', 'epsilon'), $time_diff_d);
  } else if($time_diff < 10080) {
    $time_diff_name = sprintf(__('%d days ago', 'epsilon'), $time_diff_d);
  } else if($time_diff < 20160) {
    $time_diff_name = sprintf(__('%d week ago', 'epsilon'), $time_diff_w);
  } else if($time_diff < 43200) {
    $time_diff_name = sprintf(__('%d weeks ago', 'epsilon'), $time_diff_w);
  } else if($time_diff < 86400) {
    $time_diff_name = sprintf(__('%d month ago', 'epsilon'), $time_diff_m);
  } else if($time_diff < 518400) {
    $time_diff_name = sprintf(__('%d months ago', 'epsilon'), $time_diff_m);
  } else if($time_diff < 1036800) {
    $time_diff_name = sprintf(__('%d year ago', 'epsilon'), $time_diff_y);
  } else {
    $time_diff_name = sprintf(__('%d years ago', 'epsilon'), $time_diff_y);
  }

  return $time_diff_name;
}


// SMART DATE2
function eps_smart_date2($time) {
  $time_diff = round(abs(time() - strtotime($time)) / 60);
  $time_diff_h = floor($time_diff/60);
  $time_diff_d = floor($time_diff/1440);
  $time_diff_w = floor($time_diff/10080);
  $time_diff_m = floor($time_diff/43200);
  $time_diff_y = floor($time_diff/518400);


  if($time_diff < 10080) {
    $time_diff_name = sprintf(__('%d+ days', 'epsilon'), $time_diff_d);
  } else if($time_diff < 20160) {
    $time_diff_name = sprintf(__('%d+ week', 'epsilon'), $time_diff_w);
  } else if($time_diff < 43200) {
    $time_diff_name = sprintf(__('%d+ weeks', 'epsilon'), $time_diff_w);
  } else if($time_diff < 86400) {
    $time_diff_name = sprintf(__('%d+ month', 'epsilon'), $time_diff_m);
  } else if($time_diff < 518400) {
    $time_diff_name = sprintf(__('%d+ months', 'epsilon'), $time_diff_m);
  } else if($time_diff < 1036800) {
    $time_diff_name = sprintf(__('%d+ year', 'epsilon'), $time_diff_y);
  } else {
    $time_diff_name = sprintf(__('%d+ years', 'epsilon'), $time_diff_y);
  }

  return $time_diff_name;
}


// CHECK IF ITEM MARKED AS SOLD-UNSOLD
function eps_check_sold(){
  $status = Params::getParam('markSold');
  $item_id = Params::getParam('itemId');
  $secret = Params::getParam('secret');
  $item_type = Params::getParam('itemType');

  if($status <> '' && $item_id <> '' && $item_id > 0) {
    $item = Item::newInstance()->findByPrimaryKey($item_id);

    if($secret == $item['s_secret']) {
      $item_extra = eps_item_extra($item_id);
      
      if(isset($item_extra['fk_i_item_id']) && $item_extra['fk_i_item_id'] > 0 && @$item_extra['found'] != 'NOTFOUND') {
        ModelEPS::newInstance()->updateItemExtra($item_id, array('i_sold' => $status));
      } else {
        ModelEPS::newInstance()->insertItemExtra(array('fk_i_item_id' => $item_id, 'i_sold' => $status));
      }
 
      if(osc_rewrite_enabled()) {
        $item_type_url = '?itemType=' . $item_type;
      } else {
        $item_type_url = '&itemType=' . $item_type;
      }

      header('Location: ' . osc_user_list_items_url() . $item_type_url);
      exit;
    }
  }
}

osc_add_hook('header', 'eps_check_sold');



// HELP FUNCTION TO GET CATEGORIES
function eps_category_loop($parent_id = NULL, $parent_color = NULL) {
  $parent_color = isset($parent_color) ? $parent_color : NULL;

  if(Params::getParam('sCategory') <> '') {
    $id = Params::getParam('sCategory');
  } else if(eps_get_session('sCategory') <> '' && (osc_is_publish_page() || osc_is_edit_page())) {
    $id = eps_get_session('sCategory');
  } else if(osc_item_category_id() <> '') {
    $id = osc_item_category_id();
  } else {
    $id = '';
  }


  if($parent_id <> '' && $parent_id > 0) {
    $categories = Category::newInstance()->findSubcategoriesEnabled($parent_id);
  } else {
    $parent_id = 0;
    $categories = Category::newInstance()->findRootCategoriesEnabled();
  }

  $html = '<div class="flat-wrap' . ($parent_id == 0 ? ' root' : '') . '" data-parent-id="' . $parent_id . '">';
  $html .= '<div class="single info">' . __('Select category', 'epsilon') . ' ' . ($parent_id <> 0 ? '<span class="back tr1 round2"><i class="fa fa-angle-left"></i> ' . __('Back', 'epsilon') . '</span>' : '') . '</div>';

  foreach($categories as $c) {
    if($parent_id == 0) {
      $parent_color = eps_get_cat_color($c['pk_i_id'], $c);
      $icon = '<div class="parent-icon" style="background:' . eps_get_cat_color($c['pk_i_id'], $c) . ';">' . eps_get_cat_icon($c['pk_i_id'], $c) . '</div>';
    } else {
      $icon = '<div class="parent-icon children" style="background: ' . $parent_color . '">' . eps_get_cat_icon($c['pk_i_id'], $c) . '</div>';
    }
    
    $html .= '<div class="single tr1' . ($c['pk_i_id'] == $id ? ' selected' : '') . '" data-id="' . $c['pk_i_id'] . '"><span>' . $icon . $c['s_name'] . '</span></div>';

    $subcategories = Category::newInstance()->findSubcategoriesEnabled($c['pk_i_id']);
    if(isset($subcategories[0])) {
      $html .= eps_category_loop($c['pk_i_id'], $parent_color);
    }
  }
  
  $html .= '</div>';
  return $html;
}



// FLAT CATEGORIES SELECT (Publish)
function eps_flat_category_select(){  
  $root = Category::newInstance()->findRootCategoriesEnabled();

  $html = '<div class="category-box tr1">';
  foreach($root as $c) {
    $html .= '<div class="option tr1" style="background:' . eps_get_cat_color($c['pk_i_id'], $c) . ';">' . eps_get_cat_icon($c['pk_i_id'], $c) . '</div>';
  }
 
  $html .= '</div>';
  return $html;
}



// GET CITY, REGION, COUNTRY FOR AJAX LOADER
function eps_ajax_city() {
  $user = osc_user();
  $item = osc_item();

  if(Params::getParam('sCity') <> '') {
    return Params::getParam('sCity');
  } else if(isset($item['fk_i_city_id']) && $item['fk_i_city_id'] <> '') {
    return $item['fk_i_city_id'];
  } else if(isset($user['fk_i_city_id']) && $user['fk_i_city_id'] <> '') {
    return $user['fk_i_city_id'];
  }
}


function eps_ajax_region() {
  $user = osc_user();
  $item = osc_item();

  if(Params::getParam('sRegion') <> '') {
    return Params::getParam('sRegion');
  } else if(isset($item['fk_i_region_id']) && $item['fk_i_region_id'] <> '') {
    return $item['fk_i_region_id'];
  } else if(isset($user['fk_i_region_id']) && $user['fk_i_region_id'] <> '') {
    return $user['fk_i_region_id'];
  }
}


function eps_ajax_country() {
  $user = osc_user();
  $item = osc_item();

  if(Params::getParam('sCountry') <> '') {
    return Params::getParam('sCountry');
  } else if(isset($item['fk_c_country_code']) && $item['fk_c_country_code'] <> '') {
    return $item['fk_c_country_code'];
  } else if(isset($user['fk_c_country_code']) && $user['fk_c_country_code'] <> '') {
    return $user['fk_c_country_code'];
  }
}


// CARD DESIGN OPTIONS
function eps_card_designs() {
  return array(
    'verywide' => __('Very wide', 'epsilon'), 
    'wide' => __('Wide', 'epsilon'), 
    'normal' => __('Normal', 'epsilon'), 
    'square' => __('Square', 'epsilon'), 
    'tall' => __('Tall', 'epsilon'), 
    'verytall' => __('Very tall', 'epsilon'), 
    'extratall' => __('Extra tall', 'epsilon'),
    'megatall' => __('Mega tall', 'epsilon')
  );
}


// GET CURRENT URL
function eps_current_url() {
  $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  return $actual_link;
}


// USER MENU - SIDE MENU - ONLY HOOKS
function eps_user_menu_side() {
  osc_run_hook('user_menu_items');
  osc_run_hook('user_menu');
}


// CREATE URL FOR USER ITEMS
function eps_user_items_url($type = '') {
  $type = ($type == '' ? 'all' : $type);
  $param = (osc_version() >= 830 ? 'sItemType' : 'itemType');
  $current_type = (Params::getParam($param) <> '' ? Params::getParam($param) : 'all');
  $separator = osc_rewrite_enabled() ? '?' : '&';
  
  $url = osc_user_list_items_url();
  $url .= ($type != 'all' ? $separator . $param . '=' . $type : '');
  
  return $url;  
}


// COUNT USER ITEMS
function eps_count_user_items($user_id, $type = '') {
  $type = (($type == '' || $type == 'all') ? 'enabled' : $type);

  if(in_array($type, array('active','pending_validate','expired','enabled'))) {
    return Item::newInstance()->countItemTypesByUserID($user_id, $type);
  }
  
  return 0;
}


function eps_item_sold_reserved_url($type = '', $item_extra = array()) {
  $url = osc_user_list_items_url();
  $flag = (@$item_extra['i_sold'] > 0 ? $item_extra['i_sold'] : 0);
  
  if($type != '') {
    if(strpos($url, '?') !== false) {
      $url .= '&';
    } else {
      $url .= '?';
    }
    
    if($type == 'sold') {
      $url .= 'itemId=' . osc_item_id() . '&markSold=' . ($flag == 1 ? 0 : 1) . '&secret=' . osc_item_field('s_secret');
    } else if ($type == 'reserved') { 
      $url .= 'itemId=' . osc_item_id() . '&markSold=' . ($flag == 2 ? 0 : 2) . '&secret=' . osc_item_field('s_secret');
    }
  }
  
  return $url;  
}


// ALERT FREQUENCY MAPPING
function eps_alert_frequency($type) {
  if($type == 'DAILY') {
    return __('daily', 'epsilon'); 
  } else if($type == 'WEEKLY') {
    return __('weekly', 'epsilon'); 
  } else if($type == 'MONTHLY') {
    return __('monthly', 'epsilon'); 
  } else if($type == 'INSTANT') {
    return __('instant', 'epsilon'); 
  }
}


// USER ACCOUNT - MENU ELEMENTS
function eps_user_menu() {
  $loc = osc_get_osclass_location();
  $sec = osc_get_osclass_section();

  $alerts = Alerts::newInstance()->findByUser(osc_logged_user_id());
  $c_alerts = (is_array($alerts) ? count($alerts) : 0);
  
  $param = (osc_version() >= 830 ? 'sItemType' : 'itemType');
  $current_type = (Params::getParam($param) <> '' ? Params::getParam($param) : 'all');
  
  // Define item types
  $types = array(
    'all' => __('My items', 'epsilon'),
    'active' => __('Active items', 'epsilon'),
    'pending_validate' => __('Pending items', 'epsilon'),
    'expired' => __('Expired items', 'epsilon')
  );

  // Count items for each type
  $counts = array(
    'active' => eps_count_user_items(osc_logged_user_id(), 'active'),
    'pending_validate' => eps_count_user_items(osc_logged_user_id(), 'pending_validate'),
    'expired' => eps_count_user_items(osc_logged_user_id(), 'expired')
  );

  $counts['all'] = $counts['active'] + $counts['pending_validate'] + $counts['expired'];
  ?>
  <a href="<?php echo osc_user_public_profile_url(osc_logged_user_id()); ?>" class="public"><?php echo __('Public profile', 'epsilon'); ?> <i class="ext fas fa-external-link-alt"></i></a>

  <a href="<?php echo osc_user_dashboard_url(); ?>" class="<?php if($loc == 'user' && $sec == 'dashboard') { ?>active<?php } ?>"><?php echo __('Dashboard', 'epsilon'); ?></a>

  <?php foreach($types as $type => $label) { ?>
    <?php if($type == 'all' && $counts['all'] == 0 || $type != 'all' && $counts[$type] > 0) { ?>
      <a href="<?php echo eps_user_items_url($type); ?>" class="<?php if($loc == 'user' && $sec == 'items' && $current_type == $type) { ?>active<?php } ?>">
        <?php echo $label; ?>
        <span class="counter"><?php echo ($counts[$type] ?? 0); ?></span>
      </a>
    <?php } ?>
  <?php } ?>

  <a href="<?php echo osc_user_alerts_url(); ?>" class="<?php if($loc == 'user' && $sec == 'alerts') { ?>active<?php } ?>">
    <?php echo __('Subscriptions', 'epsilon'); ?>
    <span class="counter"><?php echo $c_alerts; ?></span>
  </a>
  
  <a href="<?php echo osc_user_profile_url(); ?>" class="<?php if($loc == 'user' && $sec == 'profile') { ?>active<?php } ?>"><?php echo __('My Profile', 'epsilon'); ?></a>
  
  <div class="menu-hooks">
    <?php
      osc_run_hook('user_menu_items');
      osc_run_hook('user_menu');
    ?>
  </div>
  
  <a href="<?php echo osc_user_logout_url(); ?>" class="logout"><i class="fas fa-sign-out-alt"></i> <?php echo __('Logout', 'epsilon'); ?></a>
  <?php
}


// GET TERM NAME BASED ON COUNTRY, REGION & CITY
// function eps_get_term($term = '', $country = '', $region = '', $city = ''){
  // if($term == '') {
    // if($city <> '' && is_numeric($city)) {
      // $city_info = City::newInstance()->findByPrimaryKey($city);
      // return (osc_location_native_name_selector($city_info, 's_name') <> '' ? osc_location_native_name_selector($city_info, 's_name') : $city);
    // }
 
    // if($region <> '' && is_numeric($region)) {
      // $region_info = Region::newInstance()->findByPrimaryKey($region);
      // return (osc_location_native_name_selector($region_info, 's_name') <> '' ? osc_location_native_name_selector($region_info, 's_name') : $region);
    // }

    // if($country <> '' && strlen($country) == 2) {
      // $country_info = Country::newInstance()->findByCode($country);
      // return (osc_location_native_name_selector($country_info, 's_name') <> '' ? osc_location_native_name_selector($country_info, 's_name') : $country);
    // }

    // $array = array_filter(array($city, $region, $country));
    // return @$array[0]; // if all fail, return first non-empty

  // } else {
    // return $term;
  // }
// }


// GET LOCATION FULL NAME BASED ON COUNTRY, REGION & CITY
// function eps_get_full_loc($country = '', $region = '', $city = ''){
  // if($city <> '' && is_numeric($city)) {
    // $city_info = City::newInstance()->findByPrimaryKey($city);
    // $region_info = Region::newInstance()->findByPrimaryKey($city_info['fk_i_region_id']);
    // $country_info = Country::newInstance()->findByCode($city_info['fk_c_country_code']);
    // return osc_location_native_name_selector($city_info, 's_name') . ', ' . osc_location_native_name_selector($region_info, 's_name') . ', ' . osc_location_native_name_selector($country_info, 's_name');
  // }

  // if($region <> '' && is_numeric($region)) {
    // $region_info = Region::newInstance()->findByPrimaryKey($region);
    // $country_info = Country::newInstance()->findByCode($region_info['fk_c_country_code']);

    // return osc_location_native_name_selector($region_info, 's_name') . ', ' . osc_location_native_name_selector($country_info, 's_name');
  // }

  // if($country <> '' && strlen($country) == 2) {
    // $country_info = Country::newInstance()->findByCode($country);
    // return osc_location_native_name_selector($country_info, 's_name');
  // }

  // return '';
// }



// ADD TRANSACTION AND CONDITION TO OC-ADMIN EDIT ITEM
function eps_extra_add_admin($catId = null, $item_id = null){
  if(defined('OC_ADMIN') && OC_ADMIN === true) {
    // if($item_id > 0) {
      $item = Item::newInstance()->findByPrimaryKey($item_id);
      $item_extra = eps_item_extra($item_id);
      ?>
      
      <div class="control-group">
        <label class="control-label" for="sTransaction"><?php _e('Transaction', 'epsilon'); ?></label>
        <div class="controls"><?php echo eps_simple_transaction(true, $item_id <> '' ? $item_id : false); ?></div>
      </div>

      <div class="control-group">
        <label class="control-label" for="sCondition"><?php _e('Condition', 'epsilon'); ?></label>
        <div class="controls"><?php echo eps_simple_condition(true, $item_id <> '' ? $item_id : false); ?></div>
      </div>

      <?php if(!method_exists('ItemForm', 'contact_phone_text')) { ?>
        <div class="control-group">
        <label class="control-label" for="sPhone"><?php _e('Phone', 'epsilon'); ?></label>
        <div class="controls"><input type="text" name="sPhone" id="sPhone" value="<?php echo osc_esc_html(@$item_extra['s_phone']); ?>" /></div>
        </div>
      <?php } ?>
      
      <div class="control-group">
        <label class="control-label" for="sSold"><?php _e('Status', 'epsilon'); ?></label>
        <div class="controls">
          <select name="sSold">
            <option value="" <?php if(@$item_extra['i_sold'] == '') { ?>selected="selected"<?php } ?>><?php _e('Select a status...', 'epsilon'); ?></option>
            <option value="2" <?php if(@$item_extra['i_sold'] == 2) { ?>selected="selected"<?php } ?>><?php _e('Reserved', 'epsilon'); ?></option>
            <option value="1" <?php if(@$item_extra['i_sold'] == 1) { ?>selected="selected"<?php } ?>><?php _e('Sold', 'epsilon'); ?></option>
          </select>
        </div>
      </div>
      
      <?php
    // }
  }
}

osc_add_hook('item_form', 'eps_extra_add_admin');
osc_add_hook('item_edit', 'eps_extra_add_admin');



function eps_extra_edit($item) {
  $item['pk_i_id'] = isset($item['pk_i_id']) ? $item['pk_i_id'] : 0;
  $detail = ModelAisItem::newInstance()->findByItemId($item['pk_i_id']);

  if(isset($detail['fk_i_item_id'])) {
    ModelAisItem::newInstance()->updateItemMeta($item['pk_i_id'], Params::getParam('ais_meta_title'), Params::getParam('ais_meta_description'));
  } else {
    ModelAisItem::newInstance()->insertItemMeta($item['pk_i_id'], Params::getParam('ais_meta_title'), Params::getParam('ais_meta_description'));
  } 
}


// SIMPLE SEARCH SORT
function eps_simple_sort() {
  $type = osc_search_order();            // dt_pub_date, i_price
  $order = osc_search_order_type();      // asc, desc
  $order = (($order == '0' || $order == 'asc') ? 'asc' : 'desc');

  $orders = osc_list_orders();
  $params = eps_search_params_all();


  //$html  = '<input type="hidden" name="sOrder" id="sOrder" val="' . $type . '"/>';
  //$html  = '<input type="hidden" name="iOrderType" id="iOrderType" val="' . $order . '"/>';

  $html  = '<select class="orderSelect" id="orderSelect">';
  
  foreach($orders as $label => $spec) {
    $selected = '';
    if($spec['sOrder'] == $type && $spec['iOrderType'] == $order) {
      $selected = ' selected="selected"';
    }
 
    $params['sOrder'] = $spec['sOrder'];
    $params['iOrderType'] = $spec['iOrderType'];
    
    $html .= '<option' . $selected . ' data-type="' . $spec['sOrder'] . '" data-order="' . $spec['iOrderType'] . '" data-link="' . osc_search_url($params) . '" value="' . $spec['sOrder'] . '-' . $spec['iOrderType'] . '">' . $label . '</option>';
  }

  $html .= '</select>';

  return $html;
}


// SIMPLE CATEGORY SELECT
function eps_simple_category($select = false, $level = 3, $id = 'sCategory') {
  $categories = Category::newInstance()->toTree();
  $current = @osc_search_category_id()[0];
  $allow_parent = ($id == 'catId' ? osc_get_preference('selectable_parent_categories', 'osclass') : 1);

  if($id == 'catId') {   // publish-edit listing page
    $current = osc_item_category_id();
  }

  $c_category = eps_get_category($current);
  $root = Category::newInstance()->toRootTree($current);
  $root = isset($root[0]) ? $root[0] : array('pk_i_id' => $current, 's_name' => (isset($c_category['s_name']) ? $c_category['s_name'] : ''));

  if(!$select) {
    $html  = '<div class="simple-cat simple-select level' . $level . '">';
    $html .= '<input type="hidden" id="' . $id . '" name="' . $id . '" class="input-hidden ' . $id . '" value="' . $current . '"/>';
    $html .= '<span class="text round3 tr1"><span>' . (@$c_category['s_name'] <> '' ? $c_category['s_name'] : __('Category', 'epsilon')) . '</span> <i class="fa fa-angle-down"></i></span>';
    $html .= '<div class="list">';
    $html .= '<div class="option info">' . __('Choose one category', 'epsilon') . '</div>';

    if($id <> 'catId') {
      $html .= '<div class="option bold' . ($root['pk_i_id'] == "" ? ' selected' : '') . '" data-id="">' . __('All', 'epsilon') . '</div>';
    }

    // Root cat
    foreach($categories as $c) {
      $disable = false;
      if($allow_parent == 0 && count(@$c['categories']) > 0) { $disable = true; }

      $html .= '<div class="option ' . ($disable ? 'nonclickable' : '') . ' root' . ($root['pk_i_id'] == $c['pk_i_id'] ? ' selected' : '') . '" data-id="' . $c['pk_i_id'] . '">' . $c['s_name'] . '</span></div>';

      // Sub cat level 1
      if(count(@$c['categories']) > 0 && $level >= 1) { 
        foreach($c['categories'] as $s1) {
          $disable = false;
          if($allow_parent == 0 && count($s1['categories']) > 0) { $disable = true; }

          $html .= '<div class="option ' . ($disable ? 'nonclickable' : '') . ' sub1' . ($current == $s1['pk_i_id'] ? ' selected' : '') . '" data-id="' . $s1['pk_i_id'] . '">' . $s1['s_name'] . '</span></div>';

          // Sub cat level 2
          if(count($s1['categories']) > 0 && $level >= 2) { 
            foreach($s1['categories'] as $s2) {
              $disable = false;
              if($allow_parent == 0 && count($s2['categories']) > 0) { $disable = true; }

              $html .= '<div class="option ' . ($disable ? 'nonclickable' : '') . ' sub2' . ($current == $s2['pk_i_id'] ? ' selected' : '') . '" data-id="' . $s2['pk_i_id'] . '">' . $s2['s_name'] . '</span></div>';

              // Sub cat level 3
              if(count($s2['categories']) > 0 && $level >= 3) { 
                foreach($s2['categories'] as $s3) {
                  $html .= '<div class="option sub3' . ($current == $s3['pk_i_id'] ? ' selected' : '') . '" data-id="' . $s3['pk_i_id'] . '">' . $s3['s_name'] . '</span></div>';
                }
              }

            }
          }
        }
      }
    }

    $html .= '</div>';
    $html .= '</div>';

    return $html;

  } else {
    $html  = '<select class="' . $id . '" id="' . $id . '" name="' . $id . '">';
    $html .= '<option value="" ' . ($root['pk_i_id'] == "" ? ' selected="selected"' : '') . '>' . __('All categories', 'epsilon') . '</option>';

    foreach($categories as $c) {
      $html .= '<option ' . ($root['pk_i_id'] == $c['pk_i_id'] ? ' selected="selected"' : '') . ' value="' . $c['pk_i_id'] . '">' . $c['s_name'] . '</option>';

      // Sub cat level 1
      if(count(@$c['categories']) > 0 && $level >= 1) { 
        foreach($c['categories'] as $s1) {
          $html .= '<option ' . ($current == $s1['pk_i_id'] ? ' selected="selected"' : '') . ' value="' . $s1['pk_i_id'] . '">- ' . $s1['s_name'] . '</option>';

          // Sub cat level 2
          if(count($s1['categories']) > 0 && $level >= 2) { 
            foreach($s1['categories'] as $s2) {
              $html .= '<option ' . ($current == $s2['pk_i_id'] ? ' selected="selected"' : '') . ' value="' . $s2['pk_i_id'] . '">-- ' . $s2['s_name'] . '</option>';

              // Sub cat level 3
              if(count($s2['categories']) > 0 && $level >= 3) { 
                foreach($s2['categories'] as $s3) {
                  $html .= '<option ' . ($current == $s3['pk_i_id'] ? ' selected="selected"' : '') . ' value="' . $s3['pk_i_id'] . '">--- ' . $s3['s_name'] . '</option>';
                }
              }

            }
          }
        }
      }
    }

    $html .= '</select>';

    return $html;

  }
}


// SIMPLE SELLER TYPE SELECT
function eps_simple_seller() {
  $id = Params::getParam('sCompany');

  if($id !== '' && $id !== null) {
    $id_mod = (int)$id + 1;
  } else {
    $id_mod = 0;
  }

  $name = eps_get_simple_name($id_mod, 'seller_type');
  $name = ($name == '' ? __('Seller type', 'epsilon') : $name);

  $html  = '<select class="sCompany" id="sCompany" name="sCompany">';
  $html .= '<option value="" ' . ($id_mod == "0" ? ' selected="selected"' : '') . '>' . __('All sellers', 'epsilon') . '</option>';
  $html .= '<option value="0" ' . ($id_mod == "1" ? ' selected="selected"' : '') . '>' . __('Personal', 'epsilon') . '</option>';
  $html .= '<option value="1" ' . ($id_mod == "2" ? ' selected="selected"' : '') . '>' . __('Company', 'epsilon') . '</option>';
  $html .= '</select>';

  return $html;

}


// SIMPLE TRANSACTION TYPE SELECT
function eps_simple_transaction($is_publish = false, $item_id = false) {
  if((osc_is_publish_page() || osc_is_edit_page()) && eps_get_session('sTransaction') <> '') {
    $id = eps_get_session('sTransaction');
  } else {
    $id = Params::getParam('sTransaction');
  }

  if($item_id == '') {
    $item_id = osc_item_id();
  }

  if($item_id > 0) {
    $id = eps_item_extra($item_id);
    $id = $id['i_transaction'];
  }

  $name = eps_get_simple_name($id, 'transaction');
  $name = ($name == '' ? __('Transaction', 'epsilon') : $name);

  $options =  eps_list_options('transaction', $is_publish);

  $html  = '<select class="sTransaction' . ($is_publish ? ' mini' : '') . '" id="sTransaction" name="sTransaction">';

  foreach($options as $n => $v) {
    $html .= '<option value="' . ($n == 0 ? '' : $n) . '" ' . ($id == $n ? ' selected="selected"' : '') . '>' . $v . '</option>';
  }

  $html .= '</select>';

  return $html;
}


// SIMPLE OFFER TYPE SELECT
function eps_simple_condition($is_publish = false, $item_id = false) {
  if((osc_is_publish_page() || osc_is_edit_page()) && eps_get_session('sCondition') <> '') {
    $id = eps_get_session('sCondition');
  } else {
    $id = Params::getParam('sCondition');
  }

  if($item_id == '') {
    $item_id = osc_item_id();
  }

  if($item_id > 0) {
    $id = eps_item_extra($item_id);
    $id = $id['i_condition'];
  }

  $name = eps_get_simple_name($id, 'condition');
  $name = ($name == '' ? __('Condition', 'epsilon') : $name);

  $options =  eps_list_options('condition', $is_publish);

  $html  = '<select class="sCondition' . ($is_publish ? ' mini' : '') . '" id="sCondition" name="sCondition">';

  foreach($options as $n => $v) {
    $html .= '<option value="' . ($n == 0 ? '' : $n) . '" ' . ($id == $n ? ' selected="selected"' : '') . '>' . $v . '</option>';
  }

  $html .= '</select>';

  return $html;
}



// SIMPLE CURRENCY SELECT (publish)
function eps_simple_currency() {
  $currencies = osc_get_currencies();
  $item = osc_item(); 

  if((osc_is_publish_page() || osc_is_edit_page()) && eps_get_session('currency') <> '') {
    $id = eps_get_session('currency');
  } else {
    $id = Params::getParam('currency');
  }

  $currency = $id <> '' ? $id : osc_get_preference('currency', 'osclass');

  if(isset($item['fk_c_currency_code'])) {
    $default_key = $item['fk_c_currency_code'];
  } elseif(isset($currency) && $currency <> '') {
    $default_key = $currency;
  } else {
    $default_key = $currencies[0]['pk_c_code'];
  }
 
  $html = '<select class="currency" id="currency" name="currency">';
  foreach($currencies as $c) {
    $html .= '<option value="' . $c['pk_c_code'] . '"' . ($c['pk_c_code'] == $default_key ? ' selected="selected"' : '') . '>' . $c['pk_c_code'] . ' (' . $c['s_description'] . ')</option>';
  }

  $html .= '</select>';

  return $html;
}



// SIMPLE PRICE TYPE SELECT (publish)
function eps_simple_price_type() {
  $item = osc_item(); 

  // Item edit
  if(isset($item['i_price'])) {
    if($item['i_price'] > 0) {
      $default_key = 0;
      $default_name = '<i class="fa fa-pencil help"></i> ' . __('Enter price', 'epsilon');
    } else if($item['i_price'] == 0) {
      $default_key = 1;
      $default_name = '<i class="fa fa-cut help"></i> ' . __('Free', 'epsilon');
    } else if($item['i_price'] == '') {
      $default_key = 2;
      $default_name = '<i class="fa fa-phone help"></i> ' . __('Check with seller', 'epsilon');
    } 
  
  // Item publish
  } else {
    $default_key = 0;
    $default_name = '<i class="fa fa-pencil help"></i> ' . __('Enter price', 'epsilon');
  }


  $html  = '<div class="simple-price-type simple-select">';
  $html .= '<span class="text round3 tr1"><span>' . $default_name . '</span> <i class="fa fa-angle-down"></i></span>';
  $html .= '<div class="list">';
  $html .= '<div class="option info">' . __('Choose price type', 'epsilon') . '</div>';

  $html .= '<div class="option' . ($default_key == 0 ? ' selected' : '') . '" data-id="0"><i class="fa fa-pencil help"></i> ' . __('Enter price', 'epsilon') . '</span></div>';
  $html .= '<div class="option' . ($default_key == 1 ? ' selected' : '') . '" data-id="1"><i class="fa fa-cut help"></i> ' . __('Free', 'epsilon') . '</span></div>';
  $html .= '<div class="option' . ($default_key == 2 ? ' selected' : '') . '" data-id="2"><i class="fa fa-phone help"></i> ' . __('Check with seller', 'epsilon') . '</span></div>';

  $html .= '</div>';
  $html .= '</div>';

  return $html;
}


// SIMPLE PERIOD SELECT (search only)
function eps_simple_period() {
  $id = Params::getParam('sPeriod');

  $name = eps_get_simple_name($id, 'period');
  $name = ($name == '' ? __('Age', 'epsilon') : $name);

  $options =  eps_list_options('period');

  $html  = '<select class="sPeriod" id="sPeriod" name="sPeriod">';

  foreach($options as $n => $v) {
    $html .= '<option value="' . ($n == 0 ? '' : $n)   . '" ' . ($id == $n ? ' selected="selected"' : '') . '>' . $v . '</option>';
  }

  $html .= '</select>';

  return $html;
}


// SIMPLE PERIOD LIST
function eps_simple_period_list() {
  $id = Params::getParam('sPeriod');

  $name = eps_get_simple_name($id, 'period');
  $name = ($name == '' ? __('Age', 'epsilon') : $name);

  $options =  eps_list_options('period');
  $params = eps_search_params_all();


  $html  = '<div class="simple-period simple-list">';
  $html .= '<input type="hidden" name="sPeriod" class="input-hidden" value="' . $id . '"/>';

  $html .= '<div class="list link-check-box">';

  foreach($options as $n => $v) {
    $params['sPeriod'] = $n;
    $html .= '<a href="' . osc_search_url($params) . '" ' . ($id == $n ? 'class="active"' : '') . ' data-name="sPeriod" data-val="' . $n . '">' . $v . '</a>';
  }

  $html .= '</div>';
  $html .= '</div>';

  return $html;
}


// SIMPLE TRANSACTION LIST
function eps_simple_transaction_list() {
  $id = Params::getParam('sTransaction');

  $name = eps_get_simple_name($id, 'transaction');
  $name = ($name == '' ? __('Transaction', 'epsilon') : $name);

  $options =  eps_list_options('transaction');
  $params = eps_search_params_all();


  $html  = '<div class="simple-transaction simple-list">';
  $html .= '<input type="hidden" name="sTransaction" class="input-hidden" value="' . $id . '"/>';

  $html .= '<div class="list link-check-box">';

  foreach($options as $n => $v) {
    $params['sTransaction'] = $n;
    $html .= '<a href="' . osc_search_url($params) . '" ' . ($id == $n ? 'class="active"' : '') . ' data-name="sTransaction" data-val="' . $n . '">' . $v . '</a>';
  }

  $html .= '</div>';
  $html .= '</div>';

  return $html;
}


// SIMPLE CONDITION LIST
function eps_simple_condition_list() {
  $id = Params::getParam('sCondition');

  $name = eps_get_simple_name($id, 'condition');
  $name = ($name == '' ? __('Condition', 'epsilon') : $name);

  $options =  eps_list_options('condition');
  $params = eps_search_params_all();


  $html  = '<div class="simple-condition simple-list">';
  $html .= '<input type="hidden" name="sCondition" class="input-hidden" value="' . $id . '"/>';

  $html .= '<div class="list link-check-box">';

  foreach($options as $n => $v) {
    $params['sCondition'] = $n;
    $html .= '<a href="' . osc_search_url($params) . '" ' . ($id == $n ? 'class="active"' : '') . ' data-name="sCondition" data-val="' . $n . '">' . $v . '</a>';
  }

  $html .= '</div>';
  $html .= '</div>';

  return $html;
}




// Cookies work
if(!function_exists('mb_set_cookie')) {
  function mb_set_cookie($name, $val) {
    Cookie::newInstance()->set_expires(86400 * 30);
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


// FIND ROOT CATEGORY OF SELECTED
function eps_category_root($category_id) {
  $category = Category::newInstance()->findRootCategory($category_id);
  return $category;
}


// CHECK IF THEME IS DEMO
function eps_is_demo() {
  if(isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'],'mb-themes') !== false || strpos($_SERVER['HTTP_HOST'],'abprofitrade') !== false)) {
    return true;
  } else {
    return false;
  }
}


// MANAGE DAILY OFFER
function eps_manage_day_offer() {
  osc_reset_preferences();
  
  if(eps_param('enable_day_offer') != 1) {
    osc_set_preference('day_offer_id', '', 'theme-epsilon');
    osc_reset_preferences();
    return;
  } else {
    if(eps_param('day_offer_admin_id') > 0 || Params::getParam('day_offer_admin_id') > 0) {
      $id = (Params::getParam('day_offer_admin_id') > 0 ? Params::getParam('day_offer_admin_id') : eps_param('day_offer_admin_id'));
      $item = Item::newInstance()->findByPrimaryKey($id);
      
      if($item !== false && isset($item['pk_i_id'])) {
        osc_set_preference('day_offer_id', $id, 'theme-epsilon');
        osc_reset_preferences();
        return;
      }
    }

    $mSearch = new Search();
    $mSearch->onlyPremium(true); 
    $mSearch->order('RAND()', '');
    $mSearch->limit(0, 1);
    $aItems = $mSearch->doSearch(); 

    if(isset($aItems[0]) && isset($aItems[0]['pk_i_id'])) {
      osc_set_preference('day_offer_id', $aItems[0]['pk_i_id'], 'theme-epsilon');
      osc_reset_preferences();
      return;
    }
  }
}

osc_add_hook('cron_daily', 'eps_manage_day_offer');


// CREATE ITEM (in loop)
function eps_draw_item($c = NULL, $premium = false, $class = false) {
  if($premium){
    $filename = 'loop-single-premium.php';
  } else {
    $filename = 'loop-single.php';
  }

  if(function_exists('osc_current_web_theme_path_value')) {
    include osc_current_web_theme_path_value($filename);
  } else {
    include $filename;
  }
}


function eps_draw_placeholder_item($id = '', $class = '') {
  ?>
  <a href="<?php echo osc_esc_html(eps_param('search_premium_promote_url')); ?>" class="simple-prod placeholder o<?php echo $id; ?> <?php echo $class; ?>">
    <div class="simple-wrap">
      <div class="dx">
        <svg width="48" height="48" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M497.9 150.5c9 9 14.1 21.2 14.1 33.9v143.1c0 12.7-5.1 24.9-14.1 33.9L361.5 497.9c-9 9-21.2 14.1-33.9 14.1H184.5c-12.7 0-24.9-5.1-33.9-14.1L14.1 361.5c-9-9-14.1-21.2-14.1-33.9V184.5c0-12.7 5.1-24.9 14.1-33.9L150.5 14.1c9-9 21.2-14.1 33.9-14.1h143.1c12.7 0 24.9 5.1 33.9 14.1l136.5 136.4zM400 228c0-6.6-5.4-12-12-12h-92v-92c0-6.6-5.4-12-12-12h-56c-6.6 0-12 5.4-12 12v92h-92c-6.6 0-12 5.4-12 12v56c0 6.6 5.4 12 12 12h92v92c0 6.6 5.4 12 12 12h56c6.6 0 12-5.4 12-12v-92h92c6.6 0 12-5.4 12-12v-56z"/></svg>
        <span><?php _e('Your listing here', 'epsilon'); ?></span>
      </div>
    </div>
  </a>
  <?php
}


// RANDOM LATEST ITEMS ON HOME PAGE
function eps_random_items($numItems = 10, $category = array(), $withPicture = false) {
  $max_items = osc_get_preference('maxLatestItems@home', 'osclass');

  if($max_items == '' or $max_items == 0) {
    $max_items = 24;
  }

  $numItems = $max_items;

  $withPicture = eps_param('latest_picture');
  $randomOrder = eps_param('latest_random');
  $premiums = eps_param('latest_premium');
  $category_ids = eps_param('latest_category');

  $randSearch = Search::newInstance();
  $randSearch->dao->select(DB_TABLE_PREFIX.'t_item.* ');
  $randSearch->dao->from(DB_TABLE_PREFIX.'t_item use index (PRIMARY)');

  // where
  $whe  = DB_TABLE_PREFIX.'t_item.b_active = 1 AND ';
  $whe .= DB_TABLE_PREFIX.'t_item.b_enabled = 1 AND ';
  $whe .= DB_TABLE_PREFIX.'t_item.b_spam = 0 AND ';

  if($premiums == 1) {
    $whe .= DB_TABLE_PREFIX.'t_item.b_premium = 1 AND ';
  }

  $whe .= '('.DB_TABLE_PREFIX.'t_item.b_premium = 1 OR '.DB_TABLE_PREFIX.'t_item.dt_expiration >= \''. date('Y-m-d H:i:s').'\') ';

  // if($category <> '' and $category > 0) {
    // $subcat_list = Category::newInstance()->findSubcategories($category);
    // $subcat_id = array();
    // $subcat_id[] = $category;

    // foreach($subcat_list as $s) {
      // $subcat_id[] = $s['pk_i_id'];
    // }

    // $listCategories = implode(', ', $subcat_id);
    // $whe .= ' AND '.DB_TABLE_PREFIX.'t_item.fk_i_category_id IN ('.$listCategories.') ';
  // }

  $category_ids = array_filter(array_unique(explode(',', $category_ids)));
  if(is_array($category_ids) && count($category_ids) > 0) {
    $category_ids = implode(',', $category_ids);
    $whe .= ' AND '.DB_TABLE_PREFIX.'t_item.fk_i_category_id IN ('.$category_ids.') ';
  }

  // Subdomain (multisite)
  $options = osc_get_subdomain_params();
  $tables = array();
  
  if(isset($options['sCategory']) && $options['sCategory'] != '') {
    $whe .= ' AND ' . DB_TABLE_PREFIX.'t_item.fk_i_category_id in (SELECT fk_i_category_id FROM '.DB_TABLE_PREFIX.'t_category_description WHERE s_slug = "' . osc_esc_html($options['sCategory']) . '")';
    
  } else if(isset($options['sCountry']) && $options['sCountry'] != '') {
    $tables[] = DB_TABLE_PREFIX.'t_item_location';
    $whe .= ' AND ' . DB_TABLE_PREFIX.'t_item_location.fk_i_item_id = '.DB_TABLE_PREFIX.'t_item.pk_i_id';
    $whe .= ' AND ' . DB_TABLE_PREFIX.'t_item_location.fk_c_country_code in (SELECT pk_c_code FROM '.DB_TABLE_PREFIX.'t_country WHERE s_slug = "' . osc_esc_html($options['sCountry']) . '")';
    
  } else if(isset($options['sRegion']) && $options['sRegion'] != '') {
    $tables[] = DB_TABLE_PREFIX.'t_item_location';
    $whe .= ' AND ' . DB_TABLE_PREFIX.'t_item_location.fk_i_item_id = '.DB_TABLE_PREFIX.'t_item.pk_i_id';
    $whe .= ' AND ' . DB_TABLE_PREFIX.'t_item_location.fk_i_region_id in (SELECT pk_i_id FROM '.DB_TABLE_PREFIX.'t_region WHERE s_slug = "' . osc_esc_html($options['sRegion']) . '")';
    
  } else if(isset($options['sCity']) && $options['sCity'] != '') {
    $tables[] = DB_TABLE_PREFIX.'t_item_location';
    $whe .= ' AND ' . DB_TABLE_PREFIX.'t_item_location.fk_i_item_id = '.DB_TABLE_PREFIX.'t_item.pk_i_id';
    $whe .= ' AND ' . DB_TABLE_PREFIX.'t_item_location.fk_i_city_id in (SELECT pk_i_id FROM '.DB_TABLE_PREFIX.'t_city WHERE s_slug = "' . osc_esc_html($options['sCity']) . '")';
    
  } else if(isset($options['sUser']) && $options['sUser'] != '') {
    $tables[] = DB_TABLE_PREFIX.'t_user';
    $whe .= ' AND ' . DB_TABLE_PREFIX.'t_item.fk_i_user_id = '.DB_TABLE_PREFIX.'t_user.pk_i_id';
    $whe .= ' AND ' . DB_TABLE_PREFIX.'t_user.s_username = "' . osc_esc_html($options['sUser']) . '"';

  }

  $tables = implode(', ', $tables);
  $tables = ($tables <> '' ? ', ' : '') . $tables;

  $whe = osc_apply_filter('custom_item_search_conditions', $whe);

  if($withPicture) {
    $prem_where = ' AND ' . $whe;

    $randSearch->dao->from('(' . sprintf("select %st_item.pk_i_id FROM %st_item, %st_item_resource %s WHERE %st_item_resource.s_content_type LIKE '%%image%%' AND %st_item.pk_i_id = %st_item_resource.fk_i_item_id %s GROUP BY %st_item.pk_i_id ORDER BY %st_item.dt_pub_date DESC LIMIT %s", DB_TABLE_PREFIX, DB_TABLE_PREFIX, DB_TABLE_PREFIX, $tables, DB_TABLE_PREFIX, DB_TABLE_PREFIX, DB_TABLE_PREFIX, $prem_where, DB_TABLE_PREFIX, DB_TABLE_PREFIX, $numItems) . ') AS LIM');
  } else {
    $prem_where = ' WHERE ' . $whe;

    $randSearch->dao->from('(' . sprintf("select %st_item.pk_i_id FROM %st_item %s %s ORDER BY %st_item.dt_pub_date DESC LIMIT %s", DB_TABLE_PREFIX, DB_TABLE_PREFIX, $tables, $prem_where, DB_TABLE_PREFIX, $numItems) . ') AS LIM');
  }

  $randSearch->dao->where(DB_TABLE_PREFIX.'t_item.pk_i_id = LIM.pk_i_id');
  

  // group by & order & limit
  $randSearch->dao->groupBy(DB_TABLE_PREFIX.'t_item.pk_i_id');

  if(!$randomOrder) {
    $randSearch->dao->orderBy(DB_TABLE_PREFIX.'t_item.dt_pub_date DESC');
  } else {
    $randSearch->dao->orderBy('RAND()');
  }
  

  $randSearch->dao->limit($numItems);
  
  $rs = $randSearch->dao->get();

  if($rs === false){
    return array();
  }
  if($rs->numRows() == 0) {
    return array();
  }

  $items = $rs->result();
  return Item::newInstance()->extendData($items);
}


// RANDOM LATEST ITEMS ON HOME PAGE
function eps_premium_items($limit = 10, $exclude_ids = array(), $category_id = NULL, $with_picture = true) {
  if(View::newInstance()->_exists('eps_home_premium_items')) {
    return View::newInstance()->_count('eps_home_premium_items');
  }
  
  $limit = ($limit > 0 ? $limit : 4);

  $search = Search::newInstance();
  $search->dao->select('i.*');
  $search->dao->from(DB_TABLE_PREFIX.'t_item as i use index (PRIMARY)');

  // where
  $search->dao->where('i.b_active = 1');
  $search->dao->where('i.b_enabled = 1');
  $search->dao->where('i.b_spam = 0');
  $search->dao->where('i.b_premium = 1');

  if(is_array($exclude_ids) && count($exclude_ids) > 0) {
    $exclude_ids = implode(',', $exclude_ids);
    $search->dao->where('i.pk_i_id NOT IN (' . $exclude_ids . ')');
  } else if (!is_array($exclude_ids) && $exclude_ids > 0) { 
    $search->dao->where('i.pk_i_id <> ' . $exclude_ids);
  }
  
  if($category_id > 0) {
    $subcat_list = Category::newInstance()->findSubcategories($category_id);
    $subcat_id = array();
    $subcat_id[] = $category_id;

    foreach($subcat_list as $s) {
      $subcat_id[] = $s['pk_i_id'];
    }

    $listCategories = implode(',', $subcat_id);

    if($listCategories != '') {
      $search->dao->where('i.fk_i_category_id IN ('.$listCategories.')');
    }
  }
  
  // Category filter in theme settings
  $restrict_category_ids = array_filter(array_unique(array_map('trim', explode(',', eps_param('premium_home_category')))));

  if(is_array($restrict_category_ids) && count($restrict_category_ids) > 0) {
    $search->dao->where('i.fk_i_category_id IN (' . implode(',', $restrict_category_ids) . ')');
  }


  if($with_picture == true) {
    $search->dao->where('i.pk_i_id in (SELECT fk_i_item_id FROM '.DB_TABLE_PREFIX.'t_item_resource WHERE s_content_type LIKE "%image%")'); 
  }
 
  // group by & order & limit
  $search->dao->groupBy('i.pk_i_id');
  //$search->dao->orderBy('i.dt_pub_date DESC');
  
  
  // CHECK FOR LOCATION AND IF EXISTS, TRY TO SHOW CLOSEST LISTINGS ONLY (BY ORDERING)
  $lat = $lon = '';
  if(Params::getParam('sCity') != '' && (int)Params::getParam('sCity') > 0) {
    $city = City::newInstance()->findByPrimaryKey((int)Params::getParam('sCity'));
    
    if(isset($city['pk_i_id'])) {
      $lat = $city['d_coord_lat'];
      $lon = $city['d_coord_long'];
    }
  }
  
  if($lat == '' && $lon == '') {
    $location_cookie = eps_location_from_cookies();

    if($location_cookie['success'] == true) { 
      $lat = (@$location_cookie['d_device_coord_lat'] <> '' ? $location_cookie['d_device_coord_lat'] : @$location_cookie['d_coord_lat']);
      $lon = (@$location_cookie['d_device_coord_long'] <> '' ? $location_cookie['d_device_coord_long'] : @$location_cookie['d_coord_long']);
    }
  }
  
  if($lat <> '' && $lon <> '') {
    $measurement = 6371;  // 3959 for miles 
    
    $search->dao->select(sprintf('(%d * acos(cos(radians(%f)) * cos(radians(l.d_coord_lat)) * cos(radians(l.d_coord_long) - radians(%f)) + sin(radians(%f)) * sin(radians(l.d_coord_lat)))) as d_distance', (int)$measurement, (float)$lat, (float)$lon, (float)$lat));
    $search->dao->from(DB_TABLE_PREFIX.'t_item_location as l');
    $search->dao->where('i.pk_i_id = l.fk_i_item_id');
    $search->dao->orderby('d_distance', 'ASC');
  }
  
  $sortby = explode('/', eps_param('premium_home_sort'));
  $sort_col = strtolower((string)$sortby[0]);
  $sort_ord = strtoupper(isset($sortby[1]) ? (string)$sortby[1] : 'DESC');
  
  if($sort_col != '') {
    $search->dao->orderby($sort_col, $sort_ord);
  }
 
  $search->dao->limit($limit);

  $rs = $search->dao->get();

  if($rs === false){
    return array();
  }
  
  if($rs->numRows() == 0) {
    return array();
  }

  $items = $rs->result();
  $items = Item::newInstance()->extendData($items);
  
  View::newInstance()->_exportVariableToView('eps_home_premium_items', $items);

  return $items;
}


// ITEM LOOP FORMAT LOCATION
function eps_item_location($premium = false) {
  if(!$premium) {
    $loc = array_filter(array(osc_item_city(), (osc_item_city() != '' ? '' : osc_item_region()), (eps_count_countries() > 1 ? osc_item_country() : '')));
  } else {
    $loc = array_filter(array(osc_premium_city(), (osc_premium_city() != '' ? '' : osc_premium_region()), (eps_count_countries() > 1 ? osc_premium_country() : '')));
  }

  return implode(', ', $loc);
}


// USER ITEMS LOOP FORMAT LOCATION
function eps_user_item_location() {
  $loc = array_filter(array(osc_item_city(), osc_item_region(), (eps_count_countries() > 1 ? osc_item_country() : ''), osc_item_city_area(), osc_item_address(), osc_item_zip()));
  return implode(', ', $loc);
}


function eps_item_location_url($premium = false) {
  $array = array('page' => 'search');
  
  if(!$premium) {
    if(osc_item_city_id() > 0) {
      $array['sCity'] = osc_item_city_id();
    } else {
      $array['sCity'] = osc_item_city();
      
      if(osc_item_region_id() > 0) {
        $array['sRegion'] = osc_item_region_id();
      } else {
        $array['sRegion'] = osc_item_region();
        
        if(osc_item_country_code() > 0) {
          $array['sCountry'] = osc_item_country_code();
        } else {
          $array['sCountry'] = osc_item_country();
        }
      }
    }
  } else {
    if(osc_premium_city_id() > 0) {
      $array['sCity'] = osc_premium_city_id();
    } else {
      $array['sCity'] = osc_premium_city();
      
      if(osc_premium_region_id() > 0) {
        $array['sRegion'] = osc_premium_region_id();
      } else {
        $array['sRegion'] = osc_premium_region();
        
        if(osc_premium_country_code() > 0) {
          $array['sCountry'] = osc_premium_country_code();
        } else {
          $array['sCountry'] = osc_premium_country();
        }
      }
    }
  }

  return osc_search_url($array);
}




// LOCATION FORMATER - USED ON SEARCH LIST
function eps_location_format($country = null, $region = null, $city = null) { 
  if($country <> '') {
    if(strlen($country) == 2) {
      $country_full = Country::newInstance()->findByCode($country);
    } else {
      $country_full = Country::newInstance()->findByName($country);
    }

    if($region <> '') {
      if($city <> '') {
        return $city . ' ' . __('in', 'epsilon') . ' ' . $region . (osc_location_native_name_selector($country_full, 's_name') <> '' ? ' (' . osc_location_native_name_selector($country_full, 's_name') . ')' : '');
      } else {
        return $region . ' (' . osc_location_native_name_selector($country_full, 's_name') . ')';
      }
    } else { 
      if($city <> '') {
        return $city . ' ' . __('in', 'epsilon') . ' ' . osc_location_native_name_selector($country_full, 's_name');
      } else {
        return osc_location_native_name_selector($country_full, 's_name');
      }
    }
  } else {
    if($region <> '') {
      if($city <> '') {
        return $city . ' ' . __('in', 'epsilon') . ' ' . $region;
      } else {
        return $region;
      }
    } else { 
      if($city <> '') {
        return $city;
      } else {
        return __('Location not entered', 'epsilon');
      }
    }
  }
}


// SEARCH CONDITIONS EXTEND SEARCH
function eps_filter_extend() {
  // SEARCH - ALL - INDIVIDUAL - COMPANY TYPE
  Search::newInstance()->addJoinTable(DB_TABLE_PREFIX.'t_item_epsilon.fk_i_item_id', DB_TABLE_PREFIX.'t_item_epsilon', DB_TABLE_PREFIX.'t_item.pk_i_id = '.DB_TABLE_PREFIX.'t_item_epsilon.fk_i_item_id', 'LEFT OUTER'); // Mod


  // SEARCH - TRANSACTION
  if(Params::getParam('sTransaction') > 0) {
    Search::newInstance()->addConditions(sprintf("%st_item_epsilon.i_transaction = %d", DB_TABLE_PREFIX, Params::getParam('sTransaction')));
  }


  // SEARCH - CONDITION
  if(Params::getParam('sCondition') > 0) {
    Search::newInstance()->addConditions(sprintf("%st_item_epsilon.i_condition = %d", DB_TABLE_PREFIX, Params::getParam('sCondition')));
  }

  // SEARCH - PRICE FREE
  if(Params::getParam('bPriceFree') > 0) {
    Search::newInstance()->addConditions(sprintf("%st_item.i_price = 0", DB_TABLE_PREFIX));
  }
  
  // SEARCH - PRICE CHECK WITH SELLER
  if(Params::getParam('bPriceCheckWithSeller') > 0) {
    Search::newInstance()->addConditions(sprintf("%st_item.i_price is NULL", DB_TABLE_PREFIX));
  }

  // SEARCH - PERIOD
  if(Params::getParam('sPeriod') > 0) {
    $date_from = date('Y-m-d', strtotime(' -' . Params::getParam('sPeriod') . ' day', time()));
    Search::newInstance()->addConditions(sprintf('cast(%st_item.dt_pub_date as date) > "%s"', DB_TABLE_PREFIX, $date_from));
  }

  // SEARCH - USER ID
  if(Params::getParam('userId') > 0) {
    Search::newInstance()->addConditions(sprintf("%st_item.fk_i_user_id = %d", DB_TABLE_PREFIX, Params::getParam('userId')));
  }

  // ONLY WITH PHONE
  if(Params::getParam('bPhone') == 1) {
    if(function_exists('osc_item_show_phone') && method_exists('ItemForm', 'contact_phone_text')) {
      Search::newInstance()->addConditions(sprintf('(%st_item.b_show_phone = 1 AND (length(coalesce(%st_item_epsilon.s_phone, "")) > 4 OR length(coalesce(%st_item.s_contact_phone, "")) > 4))', DB_TABLE_PREFIX, DB_TABLE_PREFIX, DB_TABLE_PREFIX));
    } else {
      Search::newInstance()->addConditions(sprintf('length(coalesce(%st_item_epsilon.s_phone, "")) > 4', DB_TABLE_PREFIX));
    }
  }
  
  if(Params::getParam('notFromUserId') > 0) {
    //Search::newInstance()->addJoinTable(DB_TABLE_PREFIX.'t_user.pk_i_id', DB_TABLE_PREFIX.'t_user', DB_TABLE_PREFIX.'t_item.fk_i_user_id = '.DB_TABLE_PREFIX.'t_user.pk_i_id', 'LEFT OUTER'); // Mod
    //Search::newInstance()->addConditions(sprintf( '((%st_user.pk_i_id = %st_item.fk_i_user_id AND %st_item.fk_i_user_id != %d) || %st_item.fk_i_user_id IS NULL) ', DB_TABLE_PREFIX, DB_TABLE_PREFIX, DB_TABLE_PREFIX, Params::getParam('notFromUserId'), DB_TABLE_PREFIX));
    Search::newInstance()->addConditions(sprintf('(%st_item.fk_i_user_id != %d OR %st_item.fk_i_user_id IS NULL)', DB_TABLE_PREFIX, Params::getParam('notFromUserId'), DB_TABLE_PREFIX));
  }

  // SEARCH - COMPANY
  if(Params::getParam('sCompany') <> '' and Params::getParam('sCompany') <> null) {
    if(Params::getParam('notFromUserId') <= 0) {
      Search::newInstance()->addJoinTable(DB_TABLE_PREFIX.'t_user.pk_i_id', DB_TABLE_PREFIX.'t_user', DB_TABLE_PREFIX.'t_item.fk_i_user_id = '.DB_TABLE_PREFIX.'t_user.pk_i_id', 'LEFT OUTER'); // Mod
    }
    
    if(Params::getParam('sCompany') == 1) {
      Search::newInstance()->addConditions(sprintf("%st_user.b_company = 1", DB_TABLE_PREFIX));
    } else {
      Search::newInstance()->addConditions(sprintf("coalesce(%st_user.b_company, 0) <> 1", DB_TABLE_PREFIX));
    }
  }
  
  // SUPPORT ALERTS URLS
  $extra_tables = Params::getParam('extraTables');
  if($extra_tables != '') {
    $tables = @json_decode(eps_base64url_decode($extra_tables), true);
    
    if(is_array($tables) && count($tables) > 0) {
      Search::newInstance()->addTable($tables);
    }
  }

  $extra_conditions = Params::getParam('extraConditions');
  if($extra_conditions != '') {
    $conditions = @json_decode(eps_base64url_decode($extra_conditions), true);
    
    if(is_array($conditions) && count($conditions) > 0) {
      Search::newInstance()->addConditions($conditions);
    }
  }
}

osc_add_hook('search_conditions', 'eps_filter_extend');



// GET SELECTED SEARCH PARAMETERS
function eps_search_params() {
 return array(
   'sCategory' => Params::getParam('sCategory'),
   'sCountry' => Params::getParam('sCountry'),
   'sRegion' => Params::getParam('sRegion'),
   'sCity' => Params::getParam('sCity'),
   //'sPriceMin' => Params::getParam('sPriceMin'),
   //'sPriceMin' => Params::getParam('sPriceMax'),
   'sCompany' => Params::getParam('sCompany'),
   'sShowAs' => Params::getParam('sShowAs'),
   'sOrder' => Params::getParam('sOrder'),
   'iOrderType' => Params::getParam('iOrderType')
 );
}


// GET ALL PARAMS
function eps_search_params_all() {
  $params = Params::getParamsAsArray();
  unset($params['iPage']);
  return $params;
}


// FIND MAXIMUM PRICE
function eps_max_price($cat_id = null, $country_code = null, $region_id = null, $city_id = null) {
  // Search by all parameters
  $allSearch = new Search();
  $allSearch->addCategory($cat_id);
  $allSearch->addCountry($country_code);
  $allSearch->addRegion($region_id);
  $allSearch->addCity($city_id);
  $allSearch->order('i_price', 'DESC');
  $allSearch->limit(0, 1);

  $result = $allSearch->doSearch();
  $result = $result[0];

  $max_price = isset($result['i_price']) ? $result['i_price'] : 0;


  // FOLLOWING BLOCK LOOKS FOR MAX-PRICE IF IT IS 0
  // City is set, find max price by Region
  if($max_price <= 0 && isset($city_id) && $city_id <> '') {
    $regSearch = new Search();
    $regSearch->addCategory($cat_id);
    $regSearch->addCountry($country_code);
    $regSearch->addRegion($region_id);
    $regSearch->order('i_price', 'DESC');
    $regSearch->limit(0, 1);

    $result = $regSearch->doSearch();
    $result = $result[0];

    $max_price = isset($result['i_price']) ? $result['i_price'] : 0;
  }


  // Region is set, find max price by Country
  if($max_price <= 0 && isset($region_id) && $region_id <> '') {
    $regSearch = new Search();
    $regSearch->addCategory($cat_id);
    $regSearch->addCountry($country_code);
    $regSearch->order('i_price', 'DESC');
    $regSearch->limit(0, 1);

    $result = $regSearch->doSearch();
    $result = $result[0];

    $max_price = isset($result['i_price']) ? $result['i_price'] : 0;
  }


  // Country is set, find max price WorldWide
  if($max_price <= 0 && isset($country_code) && $country_code <> '') {
    $regSearch = new Search();
    $regSearch->addCategory($cat_id);
    $regSearch->order('i_price', 'DESC');
    $regSearch->limit(0, 1);

    $result = $regSearch->doSearch();
    $result = $result[0];

    $max_price = isset($result['i_price']) ? $result['i_price'] : 0;
  }


  // Category is set, find max price in all Categories
  if($max_price <= 0 && isset($region_id) && $region_id <> '') {
    $regSearch = new Search();
    $regSearch->addCategory($cat_id);
    $regSearch->order('i_price', 'DESC');
    $regSearch->limit(0, 1);

    $result = $regSearch->doSearch();
    $result = $result[0];

    $max_price = isset($result['i_price']) ? $result['i_price'] : 0;
  }


  // If max_price is still 0, set it to 1 to avoid slider defect
  if($max_price <= 0) {
    $max_price = 1000000;
  }


  return array(
    'max_price' => $max_price/1000000,
    'max_currency' => eps_param('def_cur')
 );
}


// CHECK IF AJAX IMAGE UPLOAD ON PUBLISH-EDIT PAGE CAN BE USED (from osclass 3.3)
function eps_ajax_image_upload() {
  if(class_exists('Scripts')) {
    return Scripts::newInstance()->registered['jquery-fineuploader'] && method_exists('ItemForm', 'ajax_photos');
  }
}


// CLOSE BUTTON RETRO-COMPATIBILITY
if(!OC_ADMIN) {
  if(!function_exists('add_close_button_action')) {
    function add_close_button_action(){
      echo '<script type="text/javascript">';
      echo '$(".flashmessage .ico-close").click(function(){';
      echo '$(this).parent().hide();';
      echo '});';
      echo '</script>';
    }
    osc_add_hook('footer', 'add_close_button_action');
  }
}


if(!function_exists('message_ok')) {
  function message_ok($text) {
    $final  = '<div style="padding: 1%;width: 98%;margin-bottom: 15px;" class="flashmessage flashmessage-ok flashmessage-inline">';
    $final .= $text;
    $final .= '</div>';
    echo $final;
  }
}


if(!function_exists('message_error')) {
  function message_error($text) {
    $final  = '<div style="padding: 1%;width: 98%;margin-bottom: 15px;" class="flashmessage flashmessage-error flashmessage-inline">';
    $final .= $text;
    $final .= '</div>';
    echo $final;
  }
}





// FIX PRICE FORMAT OF PREMIUM ITEMS
function eps_premium_formated_price($price = null) {
  if($price == '') {
    $price = osc_premium_price();
  }

  return (string) eps_premium_format_price($price);
}


function eps_premium_format_price($price, $symbol = null) {
  if($price === null) return osc_apply_filter ('item_price_null', __('Check with seller', 'epsilon'));
  if($price == 0) return osc_apply_filter ('item_price_zero', __('Free', 'epsilon'));

  if($symbol==null) { $symbol = osc_premium_currency_symbol(); }

  $price = $price/1000000;

  $currencyFormat = osc_locale_currency_format();
  $currencyFormat = str_replace('{NUMBER}', number_format($price, osc_locale_num_dec(), osc_locale_dec_point(), osc_locale_thousands_sep()), $currencyFormat);
  $currencyFormat = str_replace('{CURRENCY}', $symbol, $currencyFormat);
  return osc_apply_filter('premium_price', $currencyFormat);
}


function eps_ajax_item_format_price($price, $symbol_code) {
  if($price === null) return __('Check with seller', 'epsilon');
  if($price == 0) return __('Free', 'epsilon');
  return round($price/1000000, 2) . ' ' . $symbol_code;
}


AdminMenu::newInstance()->add_menu(__('Theme Setting', 'epsilon'), osc_admin_render_theme_url('oc-content/themes/epsilon/admin/configure.php'), 'epsilon_menu');
AdminMenu::newInstance()->add_submenu('epsilon_menu', __('Configure', 'epsilon'), osc_admin_render_theme_url('oc-content/themes/epsilon/admin/configure.php'), 'settings_epsilon1');
AdminMenu::newInstance()->add_submenu('epsilon_menu', __('Advertisement', 'epsilon'), osc_admin_render_theme_url('oc-content/themes/epsilon/admin/banner.php'), 'settings_epsilon2');
AdminMenu::newInstance()->add_submenu('epsilon_menu', __('Category Icons', 'epsilon'), osc_admin_render_theme_url('oc-content/themes/epsilon/admin/category.php'), 'settings_epsilon3');
AdminMenu::newInstance()->add_submenu('epsilon_menu', __('Logo', 'epsilon'), osc_admin_render_theme_url('oc-content/themes/epsilon/admin/logo.php'), 'settings_epsilon4');
AdminMenu::newInstance()->add_submenu('epsilon_menu', __('Plugins', 'epsilon'), osc_admin_render_theme_url('oc-content/themes/epsilon/admin/plugins.php'), 'settings_epsilon5');


function eps_admin_toolbar() {
  AdminMenu::newInstance()->add_submenu_divider('appearance', __('Epsilon Theme Settings', 'epsilon'), 'epsilon_submenu');

  AdminMenu::newInstance()->add_submenu('appearance', __('Configure', 'epsilon'), osc_admin_render_theme_url('oc-content/themes/epsilon/admin/configure.php'), 'settings_epsilon1');
  AdminMenu::newInstance()->add_submenu('appearance', __('Advertisement', 'epsilon'), osc_admin_render_theme_url('oc-content/themes/epsilon/admin/banner.php'), 'settings_epsilon2');
  AdminMenu::newInstance()->add_submenu('appearance', __('Category Icons', 'epsilon'), osc_admin_render_theme_url('oc-content/themes/epsilon/admin/category.php'), 'settings_epsilon3');
  AdminMenu::newInstance()->add_submenu('appearance', __('Logo', 'epsilon'), osc_admin_render_theme_url('oc-content/themes/epsilon/admin/logo.php'), 'settings_epsilon4');
  AdminMenu::newInstance()->add_submenu('appearance', __('Plugins', 'epsilon'), osc_admin_render_theme_url('oc-content/themes/epsilon/admin/plugins.php'), 'settings_epsilon5');
}

osc_add_hook('add_admin_toolbar_menus', 'eps_admin_toolbar');


// GET SITE LOGO
function eps_logo($image_only = false) {
  $src = '';
  $name = 'logo';
  $url = osc_apply_filter('logo_url', osc_current_web_theme_url('images/'));
  $path = osc_apply_filter('logo_path', WebThemes::newInstance()->getCurrentThemePath() . 'images/');

  if(eps_param('default_logo') == 1 && file_exists($path . $name . '-default.png')) {
    $src = $url . $name . '-default.png';
  }

  // Check in theme folder
  if($src == '') {
    foreach(eps_logo_extensions() as $ext) {
      if(file_exists($path . $name . '.' . $ext)) {
        $src = $url . $name . '.' . $ext;
        break;
      }
    }
  }
  
  // If it's child theme, check in parent theme folder
  if($src == '' && osc_current_web_theme_is_child() != '') {
    $url = str_replace('_child', '', $url);
    
    foreach(eps_logo_extensions() as $ext) {
      if(file_exists($path . $name . '.' . $ext)) {
        $src = $url . $name . '.' . $ext;
        break;
      }
    }
  }
  
  // No logo found, use default anyway - never from child theme
  if($src == '') {
    $url = (osc_current_web_theme_is_child() != '' ? str_replace('_child', '', $url) : $url);
    $src = $url . $name . '-default.png';
  }

  // Need just pure image link
  if($image_only === true) {
    return $src;
  }

  //return '<img src="' . (eps_is_lazy() ? eps_get_load_image('transparent') : $src) . '" data-src="' . $src . '" alt="' . osc_esc_html(osc_page_title()) . '" class="' . (eps_is_lazy() ? 'lazy' : '') . '"/>';
  return '<img src="' . $src . '" alt="' . osc_esc_html(osc_page_title()) . '"/>';
}


// CHECK IF SITE LOGO IS UPLOADED
function eps_logo_is_uploaded($default = true, $name = 'logo') {
  $url = osc_apply_filter('logo_url', osc_current_web_theme_url('images/'));
  $path = osc_apply_filter('logo_path', WebThemes::newInstance()->getCurrentThemePath() . 'images/');

  if(eps_param('default_logo') == 1 && file_exists($path . $name . '-default.png')) {
    if($default) { 
      return false;
    } else {
      return '' . $name . '-default.png'; 
    }
  }
  
  // Check in theme folder
  foreach(eps_logo_extensions() as $ext) {
    if(file_exists($path . $name . '.' . $ext)) {
      return $url . $name . '.' . $ext;
    }
  }
  
  // If it's child theme, check in parent theme folder
  if(osc_current_web_theme_is_child() != '') {
    $url = str_replace('_child', '', $url);
    $path = str_replace('_child', '', $url);
    
    foreach(eps_logo_extensions() as $ext) {
      if(file_exists($path . $name . '.' . $ext)) {
        return $url . $name . '.' . $ext;
      }
    }
  }

  return false;
}


// LIST ALL POSSIBLE LOGO EXTENSIONS
function eps_logo_extensions() {
  return array('jpg','jpeg','png','webp','gif'); 
}


// INSTALL & UPDATE OPTIONS
function eps_theme_install() {
  osc_set_preference('footer_social_define', 0, 'theme-epsilon');
  osc_set_preference('sample_images', 1, 'theme-epsilon');
  osc_set_preference('default_location', 1, 'theme-epsilon');
  osc_set_preference('profile_location', 0, 'theme-epsilon');
  osc_set_preference('publish_location', 0, 'theme-epsilon');
  osc_set_preference('messenger_replace_button', 1, 'theme-epsilon');
  osc_set_preference('enable_custom_font', 0, 'theme-epsilon');
  osc_set_preference('font_name', '', 'theme-epsilon');
  osc_set_preference('font_url', '', 'theme-epsilon');
  osc_set_preference('version', EPSILON_THEME_VERSION, 'theme-epsilon');
  osc_set_preference('enable_custom_color', 0, 'theme-epsilon');
  osc_set_preference('enable_dark_mode', 1, 'theme-epsilon');
  osc_set_preference('default_mode', 'DARK', 'theme-epsilon');
  osc_set_preference('color', '#3b49df', 'theme-epsilon');
  osc_set_preference('color_dark', '#6200ee', 'theme-epsilon');
  osc_set_preference('footer_link', '1', 'theme-epsilon');
  osc_set_preference('default_logo', '1', 'theme-epsilon');
  osc_set_preference('def_cur', '$', 'theme-epsilon');
  osc_set_preference('def_view', '1', 'theme-epsilon');
  osc_set_preference('latest_picture', '0', 'theme-epsilon');
  osc_set_preference('latest_random', '1', 'theme-epsilon');
  osc_set_preference('latest_premium', '0', 'theme-epsilon');
  osc_set_preference('latest_category', '', 'theme-epsilon');
  osc_set_preference('latest_design', 'compact', 'theme-epsilon');
  osc_set_preference('publish_category', '2', 'theme-epsilon');
  osc_set_preference('premium_home', '1', 'theme-epsilon');
  osc_set_preference('location_home', '1', 'theme-epsilon');
  osc_set_preference('premium_search', '1', 'theme-epsilon');
  osc_set_preference('premium_home_count', '4', 'theme-epsilon');
  osc_set_preference('premium_search_count', '24', 'theme-epsilon');
  osc_set_preference('premium_home_design', '', 'theme-epsilon');
  osc_set_preference('premium_search_design', 'compact', 'theme-epsilon');
  osc_set_preference('search_ajax', '1', 'theme-epsilon');
  osc_set_preference('post_required', '', 'theme-epsilon');
  osc_set_preference('post_extra_exclude', '', 'theme-epsilon');
  osc_set_preference('favorite_home', '1', 'theme-epsilon');
  osc_set_preference('favorite_count', '20', 'theme-epsilon');
  osc_set_preference('favorite_design', 'wide', 'theme-epsilon');
  osc_set_preference('blog_home', '1', 'theme-epsilon');
  osc_set_preference('blog_home_count', '4', 'theme-epsilon');
  osc_set_preference('company_home', '1', 'theme-epsilon');
  osc_set_preference('company_home_count', '5', 'theme-epsilon');
  osc_set_preference('banners', '0', 'theme-epsilon');
  osc_set_preference('banner_optimize_adsense', '0', 'theme-epsilon');
  osc_set_preference('lazy_load', '1', 'theme-epsilon');
  osc_set_preference('public_items', '12', 'theme-epsilon');
  osc_set_preference('alert_items', '12', 'theme-epsilon');
  osc_set_preference('related', '1', 'theme-epsilon');
  osc_set_preference('related_count', '24', 'theme-epsilon');
  osc_set_preference('related_design', 'tall', 'theme-epsilon');
  osc_set_preference('recent_home', '1', 'theme-epsilon');
  osc_set_preference('recent_item', '1', 'theme-epsilon');
  osc_set_preference('recent_search', '1', 'theme-epsilon');
  osc_set_preference('recent_count', '24', 'theme-epsilon');
  osc_set_preference('recent_design', 'tall', 'theme-epsilon');
  osc_set_preference('generate_favicons', '1', 'theme-epsilon');
  osc_set_preference('sample_favicons', '1', 'theme-epsilon');
  osc_set_preference('shorten_description', '1', 'theme-epsilon');
  osc_set_preference('interactive_title', '1', 'theme-epsilon');
  osc_set_preference('gallery_ratio', '', 'theme-epsilon');
  osc_set_preference('users_home', '1', 'theme-epsilon');
  osc_set_preference('users_home_count', '20', 'theme-epsilon');


  // BANNERS
  if(function_exists('epsilon_banner_list')) {
    foreach(eps_banner_list() as $b) {
      osc_set_preference($b['id'], '', 'theme-epsilon');
    }
  }

  osc_reset_preferences();

  // Add extra item fields into database
  ModelEPS::newInstance()->install();
}

// THEME UPDATE - version with dots, ie: 1.9.2
function eps_theme_update($version) {
  if(version_compare2('1.1.0', $version) == 1) {  // A > B
    // Do something if current version was undre 1.1.0  
  }
  
  osc_reset_preferences();
}


function eps_install_update_theme() {
  $installed_version = eps_param('version');
  $current_version = EPSILON_THEME_VERSION;
  
  $compare = version_compare2($current_version, $installed_version);

  if(!$installed_version || $installed_version == '') {
    eps_theme_install();
    osc_set_preference('version', EPSILON_THEME_VERSION, 'theme-epsilon');
  } else if($compare == 1) {    // A > B
    eps_theme_update($current_version);
    osc_set_preference('version', EPSILON_THEME_VERSION, 'theme-epsilon');
  }
}

osc_add_hook('init', 'eps_install_update_theme');


// WHEN NEW LISTING IS CREATED, ADD IT TO EPSILON EXTRA TABLE
function eps_new_item_extra($item) {
  ModelEPS::newInstance()->insertItemExtra(array('fk_i_item_id' => $item['pk_i_id']));
}

osc_add_hook('posted_item', 'eps_new_item_extra', 1);



// USER MENU FIX
function eps_export_variables() {
  $user = User::newInstance()->findByPrimaryKey(osc_logged_user_id());
  View::newInstance()->_exportVariableToView('user', $user);
}

osc_add_hook('init', 'eps_export_variables', 1);


// UPDATE THEME COLS ON ITEM POST-EDIT
function eps_update_fields($item) {
  if(!isset($item['pk_i_id']) || $item['pk_i_id'] <= 0) {
    return false;
  }
  
  $fields = array(
    's_phone' => (Params::getParam('contactPhone') <> '' ? Params::getParam('contactPhone') : Params::getParam('sPhone')),
    'i_condition' => Params::getParam('sCondition'),
    'i_transaction' => Params::getParam('sTransaction'),
    'i_sold' => Params::getParam('sSold')
  );

  Item::newInstance()->dao->update(DB_TABLE_PREFIX.'t_item_epsilon', $fields, array('fk_i_item_id' => $item['pk_i_id']));
}

osc_add_hook('posted_item', 'eps_update_fields', 1);
osc_add_hook('edited_item', 'eps_update_fields', 1);


// GET EPSILON ITEM EXTRA VALUES
function eps_item_extra($item_id, $item = array()) {
  if(empty($item)) {
    $item = osc_item();
  }
  
  // Theme item tables support in osclass from 8.0.2
  if(isset($item['theme_item_table_loaded']) && $item['theme_item_table_loaded'] == 1) {
    return array(
      'fk_i_item_id' => $item_id,
      's_phone' => $item['s_phone'],
      'i_condition' => $item['i_condition'],
      'i_transaction' => $item['i_transaction'],
      'i_sold' => $item['i_sold']
    );
    
  } else {
    $data = ModelEPS::newInstance()->getItemExtra($item_id);
    
    if(isset($data['fk_i_item_id']) && $data['fk_i_item_id'] > 0) {
      return $data;
    }
    
    return array(
      'fk_i_item_id' => $item_id,
      's_phone' => '',
      'i_condition' => null,
      'i_transaction' => null,
      'i_sold' => null,
      'found' => 'NOTFOUND'
    );
  }
}


// COUNT INSTANT MESSENGER MESSAGES
function eps_count_messages($user_id) {
  if($user_id > 0 && class_exists('ModelIM')) {
    $mes_counter = ModelIM::newInstance()->countMessagesByUserId($user_id); 
    $mes_counter = (isset($mes_counter['i_count']) ? $mes_counter['i_count'] : 0);
    return $mes_counter;
  } else {
    return 0;   
  }
}
  
  
// COUNT FAVORITE ITEMS
function eps_count_favorite($user_id = NULL) {
  if($user_id !== NULL) { 
    // nothing
  } else if(osc_is_web_user_logged_in()) {
    $user_id = osc_logged_user_id();
    
  } else if(function_exists('fi_save_favorite')) {
    $user_id = mb_get_cookie('fi_user_id');
  }
  

  if($user_id > 0) {
    if(function_exists('svi_save_btn')) {
      return svi_count_user_items($user_id);
      
    } else if(class_exists('ModelFI')) {
      $db_prefix = DB_TABLE_PREFIX;

      $query = "SELECT count(*) as count FROM {$db_prefix}t_favorite_items i, {$db_prefix}t_favorite_list l WHERE l.list_id = i.list_id AND l.user_id = " . $user_id . ";";
      $result = Item::newInstance()->dao->query($query);
      if(!$result) { 
        $prepare = array();
        return 0;
      } else {
        $prepare = @$result->row()['count'];
        return $prepare;
      }
    }
  }

  return 0;
}


// GET EPSILON CATEGORY EXTRA VALUES
function eps_category_extra($id, $category = array()) {
  if(isset($category['theme_category_table_loaded']) && $category['theme_category_table_loaded'] == 1) {
    return array(
      'fk_i_category_id' => $id,
      's_color' => $category['s_color'],
      's_icon' => $category['s_icon']
    );
    
  } else {
    $data = ModelEPS::newInstance()->getCategoryExtra($id);
    
    if(isset($data['fk_i_category_id']) && $data['fk_i_category_id'] > 0) {
      return $data;
    }

    return array(
      'fk_i_category_id' => $id,
      's_color' => '',
      's_icon' => '',
      'found' => false
    );
  }
}


// KEEP VALUES OF INPUTS ON RELOAD
function eps_post_preserve() {
  Session::newInstance()->_setForm('sPhone', Params::getParam('sPhone'));
  Session::newInstance()->_setForm('contactPhone', Params::getParam('contactPhone'));
  Session::newInstance()->_setForm('term', Params::getParam('term'));
  Session::newInstance()->_setForm('zip', Params::getParam('zip'));
  Session::newInstance()->_setForm('sCondition', Params::getParam('sCondition'));
  Session::newInstance()->_setForm('sTransaction', Params::getParam('sTransaction'));

  Session::newInstance()->_keepForm('sPhone');
  Session::newInstance()->_keepForm('contactPhone');
  Session::newInstance()->_keepForm('term');
  Session::newInstance()->_keepForm('zip');
  Session::newInstance()->_keepForm('sCondition');
  Session::newInstance()->_keepForm('sTransaction');
}

osc_add_hook('pre_item_post', 'eps_post_preserve');


// DROP VALUES OF INPUTS ON SUCCESSFUL PUBLISH
function eps_post_drop() {
  Session::newInstance()->_dropKeepForm('sPhone');
  Session::newInstance()->_dropKeepForm('contactPhone');
  Session::newInstance()->_dropKeepForm('term');
  Session::newInstance()->_dropKeepForm('zip');
  Session::newInstance()->_dropKeepForm('sCondition');
  Session::newInstance()->_dropKeepForm('sTransaction');

  Session::newInstance()->_clearVariables();
}

osc_add_hook('posted_item', 'eps_post_drop');



// GET VALUES FROM SESSION ON PUBLISH PAGE
function eps_get_session($param) {
  return Session::newInstance()->_getForm($param);
}


// DEFAULT ICONS ARRAY
function eps_default_icons() {
  $icons = array(
    1 => 'fa-newspaper', 2 => 'fa-motorcycle', 3 => 'fa-graduation-cap', 4 => 'fa-home', 5 => 'fa-wrench', 6 => 'fa-users', 7 => 'fa-venus-mars', 8 => 'fa-briefcase', 9 => 'fa-paw', 
    10 => 'fa-paint-brush', 11 => 'fa-exchange', 12 => 'fa-newspaper', 13 => 'fa-camera', 14 => 'fa-tablet', 15 => 'fa-mobile', 16 => 'fa-shopping-bag', 
    17 => 'fa-laptop', 18 => 'fa-mobile', 19 => 'fa-lightbulb-o', 20 => 'fa-soccer-ball-o', 21 => 'fa-s15', 22 => 'fa-medkit', 23 => 'fa-home', 24 => 'fa-clock-o', 
    25 => 'fa-microphone', 26 => 'fa-bicycle', 27 => 'fa-ticket', 28 => 'fa-plane', 29 => 'fa-television', 30 => 'fa-ellipsis-h', 31 => 'fa-car', 32 => 'fa-gears', 
    33 => 'fa-motorcycle', 34 => 'fa-ship', 35 => 'fa-bus', 36 => 'fa-truck', 37 => 'fa-ellipsis-h', 38 => 'fa-laptop', 39 => 'fa-language', 40 => 'fa-microphone', 
    41 => 'fa-graduation-cap', 42 => 'fa-ellipsis-h', 43 => 'fa-building-o', 44 => 'fa-building', 45 => 'fa-refresh', 46 => 'fa-exchange', 47 => 'fa-plane', 48 => 'fa-car', 
    49 => 'fa-window-minimize', 50 => 'fa-suitcase', 51 => 'fa-shopping-basket', 52 => 'fa-child', 53 => 'fa-microphone', 54 => 'fa-laptop', 55 => 'fa-music', 
    56 => 'fa-stethoscope', 57 => 'fa-star', 58 => 'fa-home', 59 => 'fa-truck', 60 => 'fa-wrench', 61 => 'fa-pencil', 62 => 'fa-ellipsis-h', 63 => 'fa-refresh', 
    64 => 'fa-sun-o', 65 => 'fa-star', 66 => 'fa-music', 67 => 'fa-wheelchair', 68 => 'fa-key', 69 => 'fa-venus', 70 => 'fa-mars', 71 => 'fa-mars-double', 
    72 => 'fa-venus-double', 73 => 'fa-genderless', 74 => 'fa-phone', 75 => 'fa-money', 76 => 'fa-television', 77 => 'fa-paint-brush', 78 => 'fa-book', 79 => 'fa-headphones', 
    80 => 'fa-graduation-cap', 81 => 'fa-paper-plane-o', 82 => 'fa-medkit', 83 => 'fa-users', 84 => 'fa-internet-explorer', 85 => 'fa-gavel', 86 => 'fa-wrench', 
    87 => 'fa-industry', 88 => 'fa-newspaper', 89 => 'fa-wheelchair', 90 => 'fa-home', 91 => 'fa-spoon', 92 => 'fa-exchange', 93 => 'fa-gavel', 94 => 'fa-microchip', 
    95 => 'fa-ellipsis-h', 999 => 'fa-newspaper'
 );

  return $icons;
}


function eps_default_colors() {
  $colors = array(1 => '#F44336', 2 => '#00BCD4', 3 => '#009688', 4 => '#FDE74C', 5 => '#8BC34A', 6 => '#D32F2F', 7 => '#2196F3', 8 => '#777', 999 => '#F44336');
  return $colors;
}


function eps_get_cat_icon($id, $category = array(), $string = false) {
  $category_extra = eps_category_extra($id, $category);
  $default_icons = eps_default_icons();

  if(eps_param('cat_icons') == 1) { 
    if($category_extra !== false && $category_extra['s_icon'] <> '') {
      $icon_code = $category_extra['s_icon'];
    } else {
      if(isset($default_icons[$id]) && $default_icons[$id] <> '') {
        $icon_code = $default_icons[$id];
      } else {
        $icon_code = $default_icons[999];
      }
    }

    if($string) {
      return $icon_code;
    } else {
      return '<i class="fa ' . $icon_code . '"></i>';
    }
    
  } else {
    if($string) {
      if(file_exists($path . 'small_cat/' . $id . '.png')) {
        return $url . 'small_cat/' . $id . '.png';
        
      } else if($path_parent != '' && file_exists($path_parent . 'small_cat/' . $id . '.png')) {
        return $url_parent . 'small_cat/' . $id . '.png';

      } else if(eps_param('sample_images') == 1 && file_exists($path_parent . 'small_cat/sample/' . $id . '.png')) {
        return $url_parent . 'small_cat/sample/' . $id . '.png';

      } else if(file_exists($path . 'small_cat/default.png')) {
        return $url . 'small_cat/default.png';
        
      } else {
        return $url_parent . 'small_cat/default.png';
      }
      
    } else {
      if(file_exists($path . 'small_cat/' . $id . '.png')) {
        return '<img src="' . $url . 'small_cat/' . $id . '.png" alt="' . osc_esc_html($category['s_name']) . '" />';
        
      } else if($path_parent != '' && file_exists($path_parent . 'small_cat/' . $id . '.png')) {
        return '<img src="' . $url_parent . 'small_cat/' . $id . '.png" alt="' . osc_esc_html($category['s_name']) . '" />';

      } else if(eps_param('sample_images') == 1 && file_exists($path_parent . 'small_cat/sample/' . $id . '.png')) {
        return '<img src="' . $url_parent . 'small_cat/sample/' . $id . '.png" alt="' . osc_esc_html($category['s_name']) . '" />';

      } else if(file_exists($path . 'small_cat/default.png')) {
        return '<img src="' . $url . 'small_cat/default.png" alt="' . osc_esc_html($category['s_name']) . '" />';

      } else {
        return '<img src="' . $url_parent . 'small_cat/default.png" alt="' . osc_esc_html($category['s_name']) . '" />';
      }
    }
  }

  if($string) {
    // do nothing
  } else {
    return $icon;
  }
}


function eps_get_cat_color($id, $category = array()) {
  $category_extra = eps_category_extra($id, $category);
  $default_colors = eps_default_colors();

  if($category_extra !== false && $category_extra['s_color'] <> '') {
    $color_code = $category_extra['s_color'];                        
  } else {
    if(isset($default_colors[$id]) && $default_colors[$id] <> '') {
      $color_code = $default_colors[$id];
    } else {
      $color_code = $default_colors[999];
    }
  }

  return $color_code;
}


// GET PROPER CATEGORY IMAGE (ICON)
function eps_get_cat_image($category_id) {
  if(file_exists(WebThemes::newInstance()->getCurrentThemePath() . 'images/small_cat/' . $category_id . '.png')) {
    return osc_current_web_theme_url() . 'images/small_cat/' . $category_id . '.png';
    
  } else if(eps_param('sample_images') == 1 && file_exists(WebThemes::newInstance()->getCurrentThemePath() . 'images/small_cat/sample/' . $category_id . '.png')) {
    return osc_current_web_theme_url() . 'images/small_cat/sample/' . $category_id . '.png';
    
  } else {
    return osc_current_web_theme_url() . 'images/small_cat/default.png';
  }
}


// INCREASE PHONE CLICK VIEWS
function eps_increase_clicks($itemId, $itemUserId = NULL) {
  if($itemId > 0) {
    if($itemUserId == '' || $itemUserId == 0 || ($itemUserId <> '' && $itemUserId > 0 && $itemUserId <> osc_logged_user_id())) {
      $db_prefix = DB_TABLE_PREFIX;
      $query = 'INSERT INTO ' . $db_prefix . 't_item_stats_epsilon (fk_i_item_id, dt_date, i_num_phone_clicks) VALUES (' . $itemId . ', "' . date('Y-m-d') . '", 1) ON DUPLICATE KEY UPDATE  i_num_phone_clicks = i_num_phone_clicks + 1';
      return ItemStats::newInstance()->dao->query($query);
    }
  }
}


// FIX ADMIN MENU LIST WITH THEME OPTIONS
function eps_admin_menu_fix(){
  echo '<style>' . PHP_EOL;
  echo 'body.compact #epsilon_menu .ico-epsilon_menu {bottom:-6px!important;width:50px!important;height:50px!important;margin:0!important;background:#fff url(' . osc_base_url() . 'oc-content/themes/epsilon/images/favicons/favicon-32x32.png) no-repeat center center !important;}' . PHP_EOL;
  echo 'body.compact #epsilon_menu .ico-epsilon_menu:hover {background-color:rgba(255,255,255,0.95)!important;}' . PHP_EOL;
  echo 'body.compact #menu_epsilon_menu > h3 {bottom:0!important;}' . PHP_EOL;
  echo 'body.compact #menu_epsilon_menu > ul {border-top-left-radius:0px!important;margin-top:1px!important;}' . PHP_EOL;
  echo 'body.compact #menu_epsilon_menu.current:after {content:"";display:block;width:6px;height:6px;border-radius:10px;box-shadow:1px 1px 3px rgba(0,0,0,0.1);position:absolute;left:3px;bottom:3px;background:#03a9f4}' . PHP_EOL;
  echo 'body:not(.compact) #epsilon_menu .ico-epsilon_menu {background:transparent url(' . osc_base_url() . 'oc-content/themes/epsilon/images/favicons/favicon-32x32.png) no-repeat center center !important;}' . PHP_EOL;
  echo '</style>' . PHP_EOL;
}

osc_add_hook('admin_header', 'eps_admin_menu_fix');




// CREATE URL FOR THEME AJAX REQUESTS
function eps_ajax_url() {
  return osc_base_url(true) . '?ajaxRequest=1';
}


// GET AJAX FORM URL (FOR ITEMS)
function eps_item_form_ajax_url($type, $item_id) {
  return eps_ajax_url() . '&ajaxItemForm=1&type=' . osc_esc_html($type) . '&itemId=' . osc_esc_html($item_id);
}


// COUNT PHONE CLICKS ON ITEM
function eps_phone_clicks($item_id) {
  if($item_id <> '') {
    $db_prefix = DB_TABLE_PREFIX;

    $query = "SELECT sum(coalesce(i_num_phone_clicks, 0)) as phone_clicks FROM {$db_prefix}t_item_stats_epsilon s WHERE fk_i_item_id = " . $item_id . ";";
    $result = ItemStats::newInstance()->dao->query($query);
    if(!$result) { 
      $prepare = array();
      return '0';
    } else {
      $prepare = $result->row();

      if($prepare['phone_clicks'] <> '') {
        return $prepare['phone_clicks'];
      } else {
        return '0';
      }
    }
  }
}


// NO CAPTCHA RECAPTCHA CHECK
function eps_show_recaptcha($section = ''){
  if(function_exists('anr_get_option')) {
    if(anr_get_option('site_key') <> '') {
      if($section == 'contact_listing') {
        if(anr_get_option('contact_listing') == '1') {
          osc_run_hook("anr_captcha_form_field");
        }
      } else if($section == 'login') {
        if(anr_get_option('login') == '1') {
          osc_run_hook("anr_captcha_form_field");
        }
      } else {
        // plugin sections are: login, registration, new, contact, contact_listing, send_friend, comment
        osc_run_hook("anr_captcha_form_field");
      }
    }
  } else {
    if(osc_recaptcha_public_key() <> '') {
      if(((osc_is_publish_page() || osc_is_edit_page()) && osc_recaptcha_items_enabled()) || (!osc_is_publish_page() && !osc_is_edit_page())) {
        osc_show_recaptcha($section);
      }
    }
  }
}


// SHOW BANNER
function eps_banner($location) {
  $html = '';
  
  if(eps_param('banners') == 1) {
    if(eps_is_demo()) {
      $allowed = array_filter(array_map('trim', explode(',', eps_param('banners_demo_ids'))));
      
      if(count($allowed) > 0 && !in_array('banner_' . $location, $allowed) && !in_array($location, $allowed)) {
        return false;
      }
    }
    
    if(eps_is_demo()) {
      $class = ' is-demo';
    } else {
      $class = '';
    }
    
    if(eps_param('banner_optimize_adsense') == 1) {
      $class .= ' opt-adsense';
    }

    if(eps_param('banner_' . $location) == '') {
      $blank = ' blank';
    } else {
      $blank = '';
    }

    if(eps_is_demo() && eps_param('banner_' . $location) == '') {
      $title = ' title="' . __('You can define your own banner code from theme settings', 'epsilon') . '"';
    } else {
      $title = '';
    }

    $html .= '<div id="banner-theme" class="banner-theme banner-' . $location . ' not767' . $class . $blank . '"' . $title . '><div class="myad"><div class="text">';


    // BANNER ADS PLUGIN SUPPORT
    if (function_exists('ba_show_banner') && strpos(strtoupper(eps_param('banner_' . $location)), 'BANNER-ADS-PLUGIN') !== false) {
      $xdata = strtoupper(trim(eps_param('banner_' . $location)));

      if(strpos(eps_param('banner_' . $location), 'BANNER-ADS-PLUGIN-HOOK')) {
        $hook = trim(str_replace(array(' ', '  ', '{', '{{', '{{{', '}', '}}', '}}}', 'BANNER-ADS-PLUGIN-HOOK', ':'), '', $xdata));

        if(trim($hook) <> '') {
          $html .= ba_hook($hook, false);
        }
      } else if(strpos(eps_param('banner_' . $location), 'BANNER-ADS-PLUGIN-BANNER')) {
        $banner_id = trim(str_replace(array(' ', '  ', '{', '{{', '{{{', '}', '}}', '}}}', 'BANNER-ADS-PLUGIN-BANNER', ':'), '', $xdata));

        if(is_numeric($banner_id) && $banner_id > 0) {
          $html .= ba_show_banner($banner_id, false);
        }
      } else if(strpos(eps_param('banner_' . $location), 'BANNER-ADS-PLUGIN-ADVERT')) {
        $advert_id = trim(str_replace(array(' ', '  ', '{', '{{', '{{{', '}', '}}', '}}}', 'BANNER-ADS-PLUGIN-ADVERT', ':'), '', $xdata));

        if(is_numeric($advert_id) && $advert_id > 0) {
          $html .= ba_show_advert($advert_id);
        }
      }
    } else {
      $html .= eps_param('banner_' . $location);
    }
    
    
    if(eps_is_demo() && eps_param('banner_' . $location) == '') {
      $html .= '<div class="demo-text"><span>' . __('Banner space', 'epsilon') . '</span><strong>[' .  str_replace('_', ' ', $location) . ']</strong></div>';
    }

    $html .= '</div></div></div>';

    if(!eps_is_demo() && trim(eps_param('banner_' . $location)) == '') {
      return '';
    } else {
      return $html;
    }
  } else {
    return false;
  }
}


function eps_banner_list() {
  $list = array(
    array('id' => 'banner_home_top', 'position' => __('Top of home page', 'epsilon')),
    array('id' => 'banner_home_middle', 'position' => __('Middle of home page', 'epsilon')),
    array('id' => 'banner_home_bottom', 'position' => __('Bottom of home page', 'epsilon')),
    array('id' => 'banner_search_sidebar', 'position' => __('Bottom of search sidebar', 'epsilon')),
    array('id' => 'banner_search_top', 'position' => __('Top of search page', 'epsilon')),
    array('id' => 'banner_search_bottom', 'position' => __('Bottom of search page', 'epsilon')),
    array('id' => 'banner_search_middle', 'position' => __('Between listings', 'epsilon')),
    array('id' => 'banner_item_top', 'position' => __('Top of item page', 'epsilon')),
    array('id' => 'banner_item_bottom', 'position' => __('Bottom of item page', 'epsilon')),
    array('id' => 'banner_item_sidebar', 'position' => __('Middle of item sidebar', 'epsilon')),
    array('id' => 'banner_item_sidebar_bottom', 'position' => __('Bottom of item sidebar', 'epsilon')),
    array('id' => 'banner_item_description', 'position' => __('Under item description', 'epsilon')),
    array('id' => 'banner_public_profile_sidebar', 'position' => __('Public profile sidebar', 'epsilon')),
    array('id' => 'banner_public_profile_top', 'position' => __('Public profile above header', 'epsilon')),
    array('id' => 'banner_public_profile_middle', 'position' => __('Public profile between items', 'epsilon')),
    array('id' => 'banner_public_profile_bottom', 'position' => __('Public profile under items', 'epsilon')),
    array('id' => 'banner_static_page_top', 'position' => __('Top of static page', 'epsilon')),
    array('id' => 'banner_static_page_bottom', 'position' => __('Bottom of static page', 'epsilon')),
    array('id' => 'banner_body_left', 'position' => __('All pages on left from body', 'epsilon')),
    array('id' => 'banner_body_right', 'position' => __('All pages on right from body', 'epsilon'))
 );

  return $list;
}


function eps_extra_fields_hide() {
  $list = trim(eps_param('post_extra_exclude'));
  $array = explode(',', $list);
  $array = array_map('trim', $array);
  $array = array_filter($array);

  if(!empty($array) && count($array) > 0) {
    return $array;
  } else {
    return array();
  }
}


// DISABLE ERROR404 ON SEARCH PAGE WHEN NO ITEMS FOUND
function eps_disable_404() {
  if(osc_is_search_page() && osc_count_items() <= 0) {
    if(!headers_sent()) {
      $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
      header($protocol . ' 200 OK', true, 200);
      header('Status: 200 OK', true, 200); // fallback for some SAPIs/proxies
    }
  }
}

osc_add_hook('before_html', 'eps_disable_404', 7);


// THEME PARAMS UPDATE
if(!function_exists('eps_param_update')) {
  function eps_param_update($param_name, $update_param_name, $type = NULL, $plugin_var_name = null) {
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

    } else if ($type == 'code') {

      if(Params::getParam($update_param_name) == 'done' && Params::existParam($param_name)) {
        $val = stripslashes(Params::getParam($param_name, false, false));
      } else {
        $val = (osc_get_preference($param_name, $plugin_var_name) != '') ? stripslashes(osc_get_preference($param_name, $plugin_var_name)) : '';
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
        if ($type == 'code') {
          osc_set_preference($param_name, stripslashes($val), $plugin_var_name, 'STRING');
        } else {
          osc_set_preference($param_name, $val, $plugin_var_name, 'STRING');  
        }
      } else {
        $dao_preference = new Preference();

        if ($type == 'code') {
          $dao_preference->update(array("s_value" => stripslashes($val)), array("s_section" => $plugin_var_name, "s_name" => $param_name));
        } else {
          $dao_preference->update(array("s_value" => $val), array("s_section" => $plugin_var_name, "s_name" => $param_name));
        }

        osc_reset_preferences();
        unset($dao_preference);
      }
    }

    return $val;
  }
}


// MULTI-LEVEL CATEGORIES SELECT
function eps_cat_tree() {
  $array = array();
  $root = Category::newInstance()->findRootCategoriesEnabled();

  $i = 0;
  foreach($root as $c) {
    $array[$i] = array('pk_i_id' => $c['pk_i_id'], 's_name' => $c['s_name']);
    $array[$i]['sub'] = eps_cat_sub($c['pk_i_id']);
    $i++;
  }

  return $array;
}


function eps_cat_sub($id) {
  $array = array();
  $cats = Category::newInstance()->findSubcategories($id);

  if($cats && count($cats) > 0) {
    $i = 0;
    foreach($cats as $c) {
      $array[$i] = array('pk_i_id' => $c['pk_i_id'], 's_name' => $c['s_name']);
      $array[$i]['sub'] = eps_cat_sub($c['pk_i_id']);
      $i++;
    }
  }
      
  return $array;
}


function eps_cat_list($selected = array(), $categories = '', $level = 0) {
  if($categories == '') {
    $categories = eps_cat_tree();
  }

  foreach($categories as $c) {
    echo '<option value="' . $c['pk_i_id'] . '" ' . (in_array($c['pk_i_id'], $selected) ? 'selected="selected"' : '') . '>' . str_repeat('-', $level) . ($level > 0 ? ' ' : '') . $c['s_name'] . '</option>';

    if(isset($c['sub']) && count($c['sub']) > 0) {
      eps_cat_list($selected, $c['sub'], $level + 1);
    }
  }
}

function eps_location_javascript($path = 'front') {
  ?>
  <script type="text/javascript">
  $(document).ready(function(){
    $("#countryId").on("change",function(){
      var pk_c_code = $(this).val();
      <?php if($path === 'admin') { ?>
        var url = '<?php echo osc_admin_base_url(true) . '?page=ajax&action=regions&countryId='; ?>' + pk_c_code;
      <?php } else { ?>
        var url = '<?php echo osc_base_url(true) . '?page=ajax&action=regions&countryId='; ?>' + pk_c_code;
      <?php } ?>
      var result = '';

      if(pk_c_code != '') {

        $("#regionId").attr('disabled',false);
        $("#cityId").attr('disabled',true);

        $.ajax({
          type: "POST",
          url: url,
          dataType: 'json',
          success: function(data){
            var length = data.length;
            var locationsNative = "<?php echo osc_get_current_user_locations_native(); ?>";

            if(length > 0) {
              result += '<option selected value=""><?php echo osc_esc_js(__( 'Select a region...' )); ?></option>';
              for(key in data) {
                var vname = data[key].s_name;
                if(data[key].hasOwnProperty('s_name_native')) { 
                  if(data[key].s_name_native != '' && data[key].s_name_native != 'null' && data[key].s_name_native != null && locationsNative == "1") {
                    vname = data[key].s_name_native;
                  }
                }
                result += '<option value="' + data[key].pk_i_id + '">' + vname + '</option>';
              }

              $("#region").before('<select name="regionId" id="regionId" ></select>');
              $("#region").remove();
              $("#city").before('<select name="cityId" id="cityId" ></select>');
              $("#city").remove();
              $("#regionId").val("");
            } else {
              $("#regionId").before('<input type="text" name="region" id="region" />');
              $("#regionId").remove();
              $("#cityId").before('<input type="text" name="city" id="city" />');
              $("#cityId").remove();
            }

            $("#regionId").html(result);
            $("#cityId").html('<option selected value=""><?php echo osc_esc_js(__( 'Select a city...' )); ?></option>');
            $("#regionId").trigger('change');
            $("#cityId").trigger('change');
          }
         });

       } else {

         // add empty select
         $("#region").before('<select name="regionId" id="regionId" ><option value=""><?php echo osc_esc_js(__( 'Select a region...' )); ?></option></select>');
         $("#region").remove();

         $("#city").before('<select name="cityId" id="cityId" ><option value=""><?php echo osc_esc_js(__( 'Select a city...' )); ?></option></select>');
         $("#city").remove();

         if( $("#regionId").length > 0 ){
           $("#regionId").html('<option value=""><?php echo osc_esc_js(__( 'Select a region...' )); ?></option>');
         } else {
           $("#region").before('<select name="regionId" id="regionId" ><option value=""><?php echo osc_esc_js(__( 'Select a region...' )); ?></option></select>');
           $("#region").remove();
         }
         if( $("#cityId").length > 0 ){
           $("#cityId").html('<option value=""><?php echo osc_esc_js(__( 'Select a city...' )); ?></option>');
         } else {
           $("#city").before('<select name="cityId" id="cityId" ><option value=""><?php echo osc_esc_js(__( 'Select a city...' )); ?></option></select>');
           $("#city").remove();
         }
         $("#regionId").attr('disabled',true);
         $("#cityId").attr('disabled',true);
       }
    });

    $("#regionId").on("change",function(){
      var pk_c_code = $(this).val();
      <?php if($path === 'admin') { ?>
        var url = '<?php echo osc_admin_base_url(true) . '?page=ajax&action=cities&regionId='; ?>' + pk_c_code;
      <?php } else { ?>
        var url = '<?php echo osc_base_url(true) . '?page=ajax&action=cities&regionId='; ?>' + pk_c_code;
      <?php } ?>

      var result = '';
      if(pk_c_code != '') {
        $("#cityId").attr('disabled',false);
        $.ajax({
          type: "POST",
          url: url,
          dataType: 'json',
          success: function(data){
            var length = data.length;
            var locationsNative = "<?php echo osc_get_current_user_locations_native(); ?>";

            if(length > 0) {
              result += '<option selected value=""><?php echo osc_esc_js(__( 'Select a city...' )); ?></option>';
              for(key in data) {
                var vname = data[key].s_name;
                if(data[key].hasOwnProperty('s_name_native')) {
                  if(data[key].s_name_native != '' && data[key].s_name_native != 'null' && data[key].s_name_native != null && locationsNative == "1") {
                    vname = data[key].s_name_native;
                  }
                }

                result += '<option value="' + data[key].pk_i_id + '">' + vname + '</option>';
              }

              $("#city").before('<select name="cityId" id="cityId" ></select>');
              $("#city").remove();
            } else {
              result += '<option value=""><?php echo osc_esc_js(__('No results')); ?></option>';
              $("#cityId").before('<input type="text" name="city" id="city" />');
              $("#cityId").remove();
            }
            $("#cityId").html(result);
            $("#cityId").trigger('change');
          }
         });
       } else {
        $("#cityId").attr('disabled',true);
       }
    });

    if( $("#regionId").attr('value') == "") {
      $("#cityId").attr('disabled',true);
    }

    if($("#countryId").length != 0) {
      if( $("#countryId").prop('type').match(/select-one/) ) {
        if( $("#countryId").attr('value') == "") {
          $("#regionId").attr('disabled',true);
        }
      }
    }
  });
  </script>
  <?php
}





// COMPATIBILITY FUNCTIONS
if(!function_exists('osc_can_deactivate_items')) {
  function osc_can_deactivate_items() {
    return false;
  }
}

if(!function_exists('osc_item_can_renew')) {
  function osc_item_can_renew() {
    return false;
  }
}

if(!function_exists('osc_profile_img_users_enabled')) {
  function osc_profile_img_users_enabled() {
    return false;
  }
}

if(!function_exists('osc_item_show_phone')) {
  function osc_item_show_phone() {
    return true;
  }
}

if(!function_exists('osc_get_current_user_locations_native')) {
  function osc_get_current_user_locations_native() {
    return false;
  }
}

if(!function_exists('osc_location_native_name_selector')) {
  function osc_location_native_name_selector($array, $column = 's_name') {
    return @$array[$column];
  }
}

if(!function_exists('osc_count_cities')) {
  function osc_count_cities($region = '%%%%') {
    if(!View::newInstance()->_exists('cities')) {
      View::newInstance()->_exportVariableToView('cities', Search::newInstance()->listCities($region, ">=", "city_name ASC"));
    }

    return View::newInstance()->_count('cities');
  }
}

if(!function_exists('array_column')) {
  function array_column(array $input, $columnKey, $indexKey = null) {
    $array = array();
    foreach ($input as $value) {
      if(!array_key_exists($columnKey, $value)) {
        trigger_error("Key \"$columnKey\" does not exist in array");
        return false;
      }
      if(is_null($indexKey)) {
        $array[] = $value[$columnKey];
      }
      else {
        if(!array_key_exists($indexKey, $value)) {
          trigger_error("Key \"$indexKey\" does not exist in array");
          return false;
        }
        if(! is_scalar($value[$indexKey])) {
          trigger_error("Key \"$indexKey\" does not contain scalar value");
          return false;
        }
        $array[$value[$indexKey]] = $value[$columnKey];
      }
    }
    return $array;
  }
}

if(!function_exists('osc_is_current_page')){
  function osc_is_current_page($location, $section) {
    if(osc_get_osclass_location() === $location && osc_get_osclass_section() === $section) {
      return true;
    }

    return false;
  }
}

if(!function_exists('osc_is_register_page')) {
  function osc_is_register_page() {
    return osc_is_current_page("register", "register");
  }
}

if(!function_exists('osc_is_edit_page')) {
  function osc_is_edit_page() {
    return osc_is_current_page('item', 'item_edit');
  }
}

if(!function_exists('osc_count_countries')) {
  function osc_count_countries() {
    if(!View::newInstance()->_exists('contries')) {
      View::newInstance()->_exportVariableToView('countries', Search::newInstance()->listCountries(">=", "country_name ASC"));
    }
    
    return View::newInstance()->_count('countries');
  }
}



// GET USER FROM SESSION
if(!function_exists('osc_get_user_row')) {
  function osc_get_user_row($id) {
    if($id <= 0) {
      return false;
    }

    if(View::newInstance()->_exists('user_' . $id)) {
      return View::newInstance()->_get('user_' . $id);
    }
    
    $user = User::newInstance()->findByPrimaryKey($id);
    View::newInstance()->_exportVariableToView('user_' . $id, $user);
    
    return $user;
  }
}

// GET ITEM FROM SESSION
if(!function_exists('osc_get_item_row')) {
  function osc_get_item_row($id) {
    if($id <= 0) {
      return false;
    }

    if(View::newInstance()->_exists('item_' . $id)) {
      return View::newInstance()->_get('item_' . $id);
    }
    
    $item = Item::newInstance()->findByPrimaryKey($id);
    View::newInstance()->_exportVariableToView('item_' . $id, $item);
    
    return $item;
  }
}


// GET CATEGORY FROM SESSION
if(!function_exists('osc_get_category_row')) {
  function osc_get_category_row($id) {
    if($id <= 0) {
      return false;
    }

    if(View::newInstance()->_exists('category_' . $id)) {
      return View::newInstance()->_get('category_' . $id);
    }
    
    $category = Category::newInstance()->findByPrimaryKey($id);
    View::newInstance()->_exportVariableToView('category_' . $id, $category);
    
    return $category;
  }
}


// GET COUNTRY FROM SESSION
if(!function_exists('osc_get_country_row')) {
  function osc_get_country_row($code) {
    if($code == '') {
      return false;
    }

    if(View::newInstance()->_exists('country_' . $code)) {
      return View::newInstance()->_get('country_' . $code);
    }
    
    $country = Country::newInstance()->findByCode($code);
    View::newInstance()->_exportVariableToView('country_' . $code, $country);
    
    return $country;
  }
}


// GET REGION FROM SESSION
if(!function_exists('osc_get_region_row')) {
  function osc_get_region_row($id) {
    if($id <= 0) {
      return false;
    }

    if(View::newInstance()->_exists('region_' . $id)) {
      return View::newInstance()->_get('region_' . $id);
    }
    
    $region = Region::newInstance()->findByPrimaryKey($id);
    View::newInstance()->_exportVariableToView('region_' . $id, $region);
    
    return $region;
  }
}


// GET CITY FROM SESSION
if(!function_exists('osc_get_city_row')) {
  function osc_get_city_row($id) {
    if($id <= 0) {
      return false;
    }

    if(View::newInstance()->_exists('city_' . $id)) {
      return View::newInstance()->_get('city_' . $id);
    }
    
    $city = City::newInstance()->findByPrimaryKey($id);
    View::newInstance()->_exportVariableToView('city_' . $id, $city);
    
    return $city;
  }
}


// GET LOGGED USER ROW FROM SESSION
if(!function_exists('osc_logged_user')) {
  function osc_logged_user() {
    if(osc_is_web_user_logged_in()) {
      if(View::newInstance()->_exists('_loggedUser')) {
        return View::newInstance()->_get('_loggedUser');
      }
    }
    
    return false;
  }
}


// RETURN DISTANCE BETWEEN 2 COORDINATES IN KM
function epsCalcCordDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371){
  // convert from degrees to radians
  $latFrom = deg2rad($latitudeFrom);
  $lonFrom = deg2rad($longitudeFrom);
  $latTo = deg2rad($latitudeTo);
  $lonTo = deg2rad($longitudeTo);

  $latDelta = $latTo - $latFrom;
  $lonDelta = $lonTo - $lonFrom;

  $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
  $dist = (float)($angle * $earthRadius);
  
  return number_format($dist, 2);
}


if(!function_exists('osc_item_send_friend_form_disabled')) {
  function osc_item_send_friend_form_disabled() {
    return false;
  }
}

// Set a cookie
function eps_set_cookie($name, $value, $expire = null, $path = '/', $secure = null, $httponly = true, $samesite = 'Lax') {
  if($secure === null) {
    $secure = isset($_SERVER['HTTPS']);
  }

  if($expire === null) {
    $expire = time() + (3 * 365 * 24 * 3600); // 3 years
  }
  
  if (PHP_VERSION_ID < 70300) {
    // Old syntax: name, value, expire, path, domain, secure, httponly
    setcookie($name, $value, $expire, $path, eps_cookie_domain(), $secure, $httponly);
  } else {
    // Modern syntax: associative array
    $options = [
      'expires'  => $expire,
      'path'     => $path,
      'domain'   => eps_cookie_domain(),
      'secure'   => $secure,
      'httponly' => $httponly,
      'samesite' => $samesite
    ];
    setcookie($name, $value, $options);
  }
}

// Get a cookie value safely
function eps_get_cookie($name, $default = null) {
  return $_COOKIE[$name] ?? $default;
}

// Remove (expire) a cookie safely
function eps_drop_cookie($name, $path = '/', $secure = null, $httponly = true, $samesite = 'Lax') {
  eps_set_cookie($name, '', time() - 3600, $path, $secure, $httponly, $samesite);
  unset($_COOKIE[$name]);
}


?>