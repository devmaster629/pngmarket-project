<?php

// INCLUDE MAILER SCRIPT
function bpr_include_mailer() {
  if(file_exists(osc_lib_path() . 'phpmailer/class.phpmailer.php')) {
    require_once osc_lib_path() . 'phpmailer/class.phpmailer.php';
  } else if(file_exists(osc_lib_path() . 'vendor/phpmailer/phpmailer/class.phpmailer.php')) {
    require_once osc_lib_path() . 'vendor/phpmailer/phpmailer/class.phpmailer.php';
  }
}



// SHOW BANNER
function bpr_banner($location) {
  $html = '';
  $is_demo = bpr_is_demo(true);
  $demo_locations = array('companies_bottom','company_top','company_bottom');
 
  if(bpr_param('enable_banners') == 1) {
    if($is_demo) {
      if(!in_array($location, $demo_locations)) {
        return false;
      }
    }
    
    if($is_demo) {
      $class = ' is-demo';
    } else {
      $class = '';
    }
    
    if(bpr_param('banner_optimize_adsense') == 1) {
      $class .= ' opt-adsense';
    }

    if(bpr_param('banner_' . $location) == '') {
      $blank = ' blank';
    } else {
      $blank = '';
    }

    if($is_demo && bpr_param('banner_' . $location) == '') {
      $title = ' title="' . __('You can define your own banner code from theme settings', 'business_profile') . '"';
    } else {
      $title = '';
    }

    $html .= '<div id="bpr-banner" class="bpr-banner banner-' . $location . ' not767' . $class . $blank . '"' . $title . '><div class="myad"><div class="text">';


    // BANNER ADS PLUGIN SUPPORT
    if (function_exists('ba_show_banner') && strpos(strtoupper(bpr_param('banner_' . $location)), 'BANNER-ADS-PLUGIN') !== false) {
      $xdata = strtoupper(trim(bpr_param('banner_' . $location)));

      if(strpos(bpr_param('banner_' . $location), 'BANNER-ADS-PLUGIN-HOOK')) {
        $hook = trim(str_replace(array(' ', '  ', '{', '{{', '{{{', '}', '}}', '}}}', 'BANNER-ADS-PLUGIN-HOOK', ':'), '', $xdata));

        if(trim($hook) <> '') {
          $html .= ba_hook($hook, false);
        }
      } else if(strpos(bpr_param('banner_' . $location), 'BANNER-ADS-PLUGIN-BANNER')) {
        $banner_id = trim(str_replace(array(' ', '  ', '{', '{{', '{{{', '}', '}}', '}}}', 'BANNER-ADS-PLUGIN-BANNER', ':'), '', $xdata));

        if(is_numeric($banner_id) && $banner_id > 0) {
          $html .= ba_show_banner($banner_id, false);
        }
      } else if(strpos(bpr_param('banner_' . $location), 'BANNER-ADS-PLUGIN-ADVERT')) {
        $advert_id = trim(str_replace(array(' ', '  ', '{', '{{', '{{{', '}', '}}', '}}}', 'BANNER-ADS-PLUGIN-ADVERT', ':'), '', $xdata));

        if(is_numeric($advert_id) && $advert_id > 0) {
          $html .= ba_show_advert($advert_id);
        }
      }
    } else {
      $html .= bpr_param('banner_' . $location);
    }
    
    
    if($is_demo && bpr_param('banner_' . $location) == '') {
      $html .= '<div class="demo-text"><span>' . __('Banner space', 'business_profile') . '</span><strong>[' .  str_replace('_', ' ', $location) . ']</strong></div>';
    }

    $html .= '</div></div></div>';

    if(!$is_demo && trim(bpr_param('banner_' . $location)) == '') {
      return '';
    } else {
      return $html;
    }
  } else {
    return false;
  }
}


// LIST OF BANNERS
function bpr_banner_list() {
  $list = array(
    array('id' => 'banner_companies_top', 'name' => __('Companies Top', 'business_profile'), 'position' => __('List of companies top of page', 'business_profile')),
    array('id' => 'banner_companies_search_bottom', 'name' => __('Companies Search Bottom', 'business_profile'), 'position' => __('List of companies below search', 'business_profile')),
    array('id' => 'banner_companies_bottom', 'name' => __('Companies Bottom', 'business_profile'), 'position' => __('List of companies bottom of page', 'business_profile')),
    array('id' => 'banner_company_top', 'name' => __('Company Top', 'business_profile'), 'position' => __('Top of company (profile) page', 'business_profile')),
    array('id' => 'banner_company_desc_top', 'name' => __('Company Description Top', 'business_profile'), 'position' => __('Company (profile) page above description', 'business_profile')),
    array('id' => 'banner_company_desc_bottom', 'name' => __('Company Description Bottom', 'business_profile'), 'position' => __('Company (profile) page below description', 'business_profile')),
    array('id' => 'banner_company_sidebar_top', 'name' => __('Company Sidebar Top', 'business_profile'), 'position' => __('Company (profile) page above sidebar', 'business_profile')),
    array('id' => 'banner_company_sidebar_bottom', 'name' => __('Company Sidebar Bottom', 'business_profile'), 'position' => __('Company (profile) page below sidebar', 'business_profile')),
    array('id' => 'banner_company_items_top', 'name' => __('Company Above Listings', 'business_profile'), 'position' => __('Company (profile) page above user listings', 'business_profile')),
    array('id' => 'banner_company_bottom', 'name' => __('Company Bottom', 'business_profile'), 'position' => __('Bottom of company (profile) page', 'business_profile'))
  );

  return $list;
}

// ADD LINK TO HEADER
function bpr_header_link_hook() {
  if(bpr_param('hook_header_links') == 1) {
    echo '<a href="' . bpr_companies_url() . '">' . __('Companies', 'business_profile') . '</a>';    
  }
}

osc_add_hook('header_links', 'bpr_header_link_hook');


// GENERATE PAGINATION - ITEMS 
function bpr_paginate_items($data, $page_id, $per_page, $count_all, $class = '') {
  $html = '';
  $page_id = (int)$page_id;
  $page_id = ($page_id <= 0 ? 1 : $page_id);

  if($per_page < $count_all) {
    $html .= '<div id="bpr-pagination" class="bpr-pagination ' . $class . '">';

    $pages = ceil($count_all/$per_page); 
    $page_actual = ($page_id == '' ? 1 : $page_id);

    if($pages > 6) {

      // Too many pages to list them all
      if($page_id == 1) { 
        $ids = array(1,2,3, $pages);

      } else if ($page_id > 1 && $page_id < $pages) {
        $ids = array(1,$page_id-1, $page_id, $page_id+1, $pages);

      } else {
        $ids = array(1, $page_id-2, $page_id-1, $page_id);
      }

      $old = -1;
      $ids = array_unique(array_filter($ids));

      foreach($ids as $i) {
        // $url = osc_route_url('bpr-seller-filter', array('pageId' => $i));
        $url = osc_route_url('bpr-seller-filter', array('identifier' => $data['identifier'], 'params' => 'city,' . $data['city'] . ';category,' . $data['category'] . ';page,' . $i . ';'));

        if($old <> -1 && $old <> $i - 1) {
          $html .= '<span>&middot;&middot;&middot;</span>';
        }

        $html .= '<a href="' . $url . '" ' . ($page_actual == $i ? 'class="bpr-active"' : '') . '>' . $i . '</a>';
        $old = $i;
      }

    } else {

      // List all pages
      for ($i = 1; $i <= $pages; $i++) {
        // $url = osc_route_url('bpr-seller-filter', array('pageId' => $i));
        $url = osc_route_url('bpr-seller-filter', array('identifier' => $data['identifier'], 'params' => 'city,' . $data['city'] . ';category,' . $data['category'] . ';page,' . $i . ';'));

        $html .= '<a href="' . $url . '" ' . ($page_actual == $i ? 'class="bpr-active"' : '') . '>' . $i . '</a>';
      }
    }

    $html .= '</div>';
  }

  return $html;
}


// GENERATE PAGINATION - ITEMS 
function bpr_paginate_sellers($suffix, $page_id, $per_page, $count_all, $class = '') {
  $html = '';
  $page_id = (int)$page_id;
  $page_id = ($page_id <= 0 ? 1 : $page_id);

  if($per_page < $count_all) {
    $html .= '<div id="bpr-pagination" class="bpr-pagination ' . $class . '">';

    $pages = ceil($count_all/$per_page); 
    $page_actual = ($page_id == '' ? 1 : $page_id);

    if($pages > 6) {

      // Too many pages to list them all
      if($page_id == 1) { 
        $ids = array(1,2,3, $pages);

      } else if ($page_id > 1 && $page_id < $pages) {
        $ids = array(1,$page_id-1, $page_id, $page_id+1, $pages);

      } else {
        $ids = array(1, $page_id-2, $page_id-1, $page_id);
      }

      $old = -1;
      $ids = array_unique(array_filter($ids));

      foreach($ids as $i) {
        // $url = osc_route_url('bpr-seller-filter', array('pageId' => $i));
        $url = osc_route_url('bpr-list-filter', array('iPage' => $i)) . $suffix;

        if($old <> -1 && $old <> $i - 1) {
          $html .= '<span>&middot;&middot;&middot;</span>';
        }

        $html .= '<a href="' . $url . '" ' . ($page_actual == $i ? 'class="bpr-active"' : '') . '>' . $i . '</a>';
        $old = $i;
      }

    } else {

      // List all pages
      for ($i = 1; $i <= $pages; $i++) {
        // $url = osc_route_url('bpr-seller-filter', array('pageId' => $i));
        $url = osc_route_url('bpr-list-filter', array('iPage' => $i)) . $suffix;

        $html .= '<a href="' . $url . '" ' . ($page_actual == $i ? 'class="bpr-active"' : '') . '>' . $i . '</a>';
      }
    }

    $html .= '</div>';
  }

  return $html;
}


// PRINT LEGAL NOTIC ON EACH LISTING
function bpr_print_item_detail_legal_notice($item) {
  $text = bpr_get_item_legal_notice($item);
  
  if($text !== false) {
  ?>
  <div id="bpr-notice" class="bpr-body">
    <div class="bpr-notice-head">
      <a href="#" class="bpr-open-notice"><?php _e('Legal notice', 'business_profile'); ?></a>
    </div>
    
    <div class="bpr-notice-text"><?php echo nl2br($text); ?></div>
  </div>
  <?php  
  }
}

osc_add_hook('item_detail', 'bpr_print_item_detail_legal_notice', 9);
  
  
function bpr_get_item_legal_notice($item) {
  if(bpr_param('legal_notice') == 1) {
    if($item['fk_i_user_id'] > 0) {
      $seller = ModelBPR::newInstance()->getSellerByUserId($item['fk_i_user_id']);

      if(isset($seller['pk_i_id']) && $seller['pk_i_id'] > 0) {
        if($seller['b_enabled'] == 1) {
          if(trim((string)$seller['s_legal_notice']) <> '') {
            return strip_tags($seller['s_legal_notice']);
          }
        }
      }
    }
  }
  
  return false;
}


// GET VIDEO URL
function bpr_video_base_url() {
  if(bpr_param('video_url') != '' && filter_var(bpr_param('video_url'), FILTER_VALIDATE_URL)) {
    $url = bpr_param('video_url');
  } else {
    $url = 'https://www.youtube.com/';
  }
  
  if(substr($url, -1) != '/') {
    $url .= '/';
  }
  
  return $url;
}


// MIGRATE OLD IMAGES STRUCTURE TO NOW
function bpr_migrate_images() {
  $icon_path_old = osc_content_path() . 'plugins/business_profile/img/icon/';
  $cover_path_old = osc_content_path() . 'plugins/business_profile/img/cover/';
  
  if(file_exists($icon_path_old) || file_exists($cover_path_old)) {
    $sellers = ModelBPR::newInstance()->getAllSeller();
    
    if(count($sellers) > 0) {
      foreach($sellers as $seller) {
        $user_id = $seller['fk_i_user_id'];
        bpr_check_upload_dirs($user_id);
        
        $icon_path = UPLOADS_PATH . 'business_profile/' . $user_id . '/icon/';
        $cover_path = UPLOADS_PATH . 'business_profile/' . $user_id . '/cover/';
    
        $icon = $seller['s_icon'];
        $cover = $seller['s_cover'];
        
        $icon = explode('?', $icon)[0];
        $cover = explode('?', $cover)[0];


        if($icon != '') {
          @rename($icon_path_old . $icon, $icon_path . $icon);
        }

        if($cover != '') {
          @rename($cover_path_old . $cover, $cover_path . $cover);
        }
      }
    }
    
    if(file_exists($icon_path_old)) {
      $files = glob($icon_path_old . '*');
      foreach($files as $file){
        if(is_file($file)) {
          @unlink($file); 
        }
      }
      
      rmdir($icon_path_old);
    }
    
    if(file_exists($cover_path_old)) {
      $files = glob($cover_path_old . '*');
      foreach($files as $file){
        if(is_file($file)) {
          @unlink($file); 
        }
      }
      
      rmdir($cover_path_old);
    }
  }
}

osc_add_hook('init_admin', 'bpr_migrate_images');


// CHECK UPLOAD DIRS
function bpr_check_upload_dirs($user_id = NULL) {
  $dirs = array();
  $dirs[] = UPLOADS_PATH . 'business_profile/';
  
  if($user_id > 0) {
    $dirs[] = UPLOADS_PATH . 'business_profile/' . $user_id . '/';
    $dirs[] = UPLOADS_PATH . 'business_profile/' . $user_id . '/icon/';
    $dirs[] = UPLOADS_PATH . 'business_profile/' . $user_id . '/logo/';
    $dirs[] = UPLOADS_PATH . 'business_profile/' . $user_id . '/cover/';
    $dirs[] = UPLOADS_PATH . 'business_profile/' . $user_id . '/gallery/';
  }
  
  foreach($dirs as $dir) {
    if (!file_exists($dir)) {
      mkdir($dir, 0755, true);
    }
  }  
}



// PREPARE USER GALLERY
function bpr_prepare_user_gallery($seller) {
  $output = array();
  $found = array();
  
  if(isset($seller['s_gallery']) && $seller['s_gallery'] != '') {
    $list = array_filter(array_map('trim', explode(',', $seller['s_gallery'])));

    if(count($list) > 0) {
      foreach($list as $img) {
        if($img != '' && file_exists(UPLOADS_PATH . 'business_profile/' . $seller['fk_i_user_id'] . '/gallery/' . $img)) {
          $found[] = $img;
          
          $output[] = array(
            'name' => $img,
            'ext' => pathinfo($img, PATHINFO_EXTENSION),
            'title' => @explode('.', $img)[0],
            'path' => UPLOADS_PATH . 'business_profile/' . $seller['fk_i_user_id'] . '/gallery/' . $img,
            'url' => UPLOADS_WEB_PATH . 'business_profile/' . $seller['fk_i_user_id'] . '/gallery/' . $img
          );
        }
      }
    }


    // Remove unpaired images
    $dir = UPLOADS_PATH . 'business_profile/' . $seller['fk_i_user_id'] . '/gallery/';

    if(file_exists($dir)) {
      $files = glob($dir . '*');
      foreach($files as $file){
        if(is_file($file) && !in_array(basename($file), $found)) {
          @unlink($file); 
        }
      }
    }
  
    // Update user profile if there is diff
    $found_string = implode(',', $found);
    if(@$seller['pk_i_id'] > 0 && @$seller['s_gallery'] != $found_string) {
      ModelBPR::newInstance()->updateSellerField($seller['pk_i_id'], array('s_gallery' => $found_string));
    }  
  }

  return $output;
}

// GET PHONE NUMBER PARTS
function bpr_phone_mask($phone, $type = '') {
  $phone = trim($phone);
  $mask = (bpr_param('mask_phone') == 1 ? 1 : 0);  
  $only_logged = (bpr_param('phone_only_logged') == 1 ? 1 : 0);
  
  if($type == 'MASKED') {
    if(strlen($phone) <= 4) {
      return 'xxxx';
    } else {
      return substr($phone, 0, strlen($phone) - 4) . 'xxxx';
    }
  } else if ($type == 'PART1') {
    if(strlen($phone) <= 4) {
      return $phone;
    } else {
      return substr($phone, 0, strlen($phone) - 4);
    }
  } else if ($type == 'PART2') {
    if(strlen($phone) <= 4) {
      return '';
    } else {
      return substr($phone, strlen($phone) - 4);
    }
  }
    
  return $phone;
}


// GET CITY NAME
function bpr_city_name($city) {
  if(function_exists('osc_location_native_name_selector')) {
    return osc_location_native_name_selector($city, 's_name');
  }
  
  return isset($city['s_name']) ? $city['s_name'] : '';
  
}

// GET NATIVE LOCATION NAME IF APPLICABLE
function bpr_user_location($user, $type = 0, $delimiter = ', ') {
  if(function_exists('osc_location_native_name_selector')) {
    if($type == 2) {
      $location = array(osc_location_native_name_selector($user, 's_country'), osc_location_native_name_selector($user, 's_region'), osc_location_native_name_selector($user, 's_city'), $user['s_address']);
    } else if($type == 1) {
      $location = array(osc_location_native_name_selector($user, 's_country'), osc_location_native_name_selector($user, 's_region'), osc_location_native_name_selector($user, 's_city'), $user['s_zip'], $user['s_address']);
    } else {
      $location = array(osc_location_native_name_selector($user, 's_city'), osc_location_native_name_selector($user, 's_region'), $user['fk_c_country_code']);
    }
  } else {
    if($type == 2) {
      $location = array($user['s_country'], $user['s_region'], $user['s_city'], $user['s_address']);
    } else if($type == 1) {
      $location = array($user['s_country'], $user['s_region'], $user['s_city'], $user['s_zip'], $user['s_address']);
    } else {
      $location = array($user['s_city'], $user['s_region'], $user['fk_c_country_code']);
    }
  }
  
  return implode($delimiter, array_filter($location));
}


// GET COMPANIES BLOCK
function bpr_companies_block($limit = 5, $order = 'RANDOM') {
  require osc_content_path() . 'plugins/business_profile/form/block.php';
}


// UPDATE PAGINATION URL ON SEARCH
function bpr_search_url_extra() {
  $word = Params::getParam('bpWord');
  $category = Params::getParam('bpCategory');
  // $country = Params::getParam('bpCountry');
  // $region = Params::getParam('bpRegion');
  $city = Params::getParam('bpCity');

  $params = array(
    'bpWord' => $word,
    'bpCategory' => $category,
    // 'bpCountry' => $country,
    // 'bpRegion' => $region,
    'bpCity' => $city
  );


  $string = '';

  if(osc_rewrite_enabled()) {
    $string = '?';
  } else {
    $string = '&';
  }

  $c = 0;
  if($word <> '' || $category <> '' || $city <> '') {
    foreach($params as $n => $v) {
      if($v <> '') {
        if($c > 0) {
          $string .= '&';
        }

        $string .= $n . '=' . $v;
    
        $c++;
      }
    }

    return $string;
  } 
}


// GET CORRECT LOCALE VALUE
function bpr_field($data, $locale = '') {
  if ($locale == '') {
    $locale = osc_current_user_locale();
  }
 
  $value = @$data[$locale];

  if($value == '') {
    $value = @$data[osc_language()];

    if($value == '') {
      $aLocales = osc_get_locales();
      foreach($aLocales as $locale) {
        $value = @$data[@$locale['pk_c_code']];
        if($value != '') {
          break;
        }
      }
    }
  }

  return (string) $value;
}


// GET COMPANIES LIST
function bpr_show_companies() {
  require osc_content_path() . 'plugins/business_profile/form/home.php';
}


// GET USER BUSINESS TYPE
function bpr_get_type($user_id = -1) {
  if($user_id < 0) {
    $user_id = osc_logged_user_id();
  }

  if($user_id > 0) {
    $seller = ModelBPR::newInstance()->getSellerByUserId($user_id);

    if(isset($seller['pk_i_id']) && $seller['pk_i_id'] > 0) {
      if($seller['b_enabled'] == 1) {
        return $seller['i_type'];
      }
    }
  }

  return -1;
}


// CHECK IF USER IS BUSINESS ONE - Has business profile (valid and enabled)
function bpr_is_business($user_id = -1) {
  if($user_id < 0) {
    $user_id = osc_logged_user_id();
  }

  if($user_id > 0) {
    $seller = ModelBPR::newInstance()->getSellerByUserId($user_id);

    if(isset($seller['pk_i_id']) && $seller['pk_i_id'] > 0) {
      if($seller['b_enabled'] == 1) {
        return true;
      }
    }
  }

  return false;
}


// CHECK IF USER IS VERIFIED SELLER
function bpr_is_user_verified($user_id) {
  if($user_id > 0) {
    $seller = ModelBPR::newInstance()->getSellerByUserId($user_id);

    if(isset($seller['pk_i_id']) && $seller['pk_i_id'] > 0) {
      if($seller['b_verified'] == 1) {
        return true;
      }
    }
  }

  return false;
}


// GET VERIFIED SELLER BADGE HTML OR IMAGE URL
function bpr_is_user_verified_badge($user_id, $img_url_only = false) {
  if(!bpr_is_user_verified($user_id)) {
    return false;
  }

  $img_url = osc_base_url() . 'oc-content/plugins/business_profile/img/verified.png';

  if($img_url_only) {
    return $img_url;
  }

  $html = '<div class="bpr-verified"><img src="' . $img_url . '" alt="' . osc_esc_html(__('Verified seller', 'business_profile')) . '"/></div>';

  return $html;
}


// REDIRECT PUBLIC PROFILE TO BUSINESS PROFILE IF EXISTS
function bpr_public_to_business() {
  $location = Rewrite::newInstance()->get_location();
  $section  = Rewrite::newInstance()->get_section();

  if($location == 'user' && $section == 'pub_profile' && Params::getParam('action') <> 'delete') {
    if(Params::getParam('username') <> '' || Params::getParam('id') > 0) {
      $user = User::newInstance()->findByPrimaryKey(Params::getParam('id'));
      
      if(!isset($user['pk_i_id'])) {
        $user = User::newInstance()->findByUsername(Params::getParam('username'));
      }
      
      if(isset($user['pk_i_id']) && $user['pk_i_id'] > 0) {
        $seller = ModelBPR::newInstance()->getSellerByUserId($user['pk_i_id']);

        if(isset($seller['pk_i_id']) && $seller['b_enabled'] == 1) {
          header('Location:' . osc_route_url('bpr-seller', array('identifier' => $seller['s_identifier'])));
          exit;
        }
      }
    }
  }
}

osc_add_hook('init', 'bpr_public_to_business');


// CHECK USER IF PREMIUM
function bpr_control_user($seller) {
  if(isset($seller['pk_i_id'])) {
    if(bpr_check_premium()) {
      $groups = explode(',', bpr_check_premium());

      $user_group = osp_get_user_group($seller['fk_i_user_id']);
 
      if(in_array($user_group, $groups)) {
        return true;
      }
    }
  }

  return false;
}


// CHECK IF PREMIUM GROUPS ARE SET
function bpr_check_premium() {
  if(function_exists('osp_param')) {
    if(osp_param('groups_enabled') == 1) {
      if(bpr_param('premium_groups') <> '') {
        return bpr_param('premium_groups');
      }
    }
  }

  return false;
}


// GET PREMIUM GROUPS
function bpr_premium_groups() {
  if(bpr_check_premium()) {
    $return = array();
    $groups = explode(',', bpr_check_premium());

    if(count($groups) > 0) {
      foreach($groups as $id) {
        $group_rec = ModelOSP::newInstance()->getGroup($id);
        
        if($group_rec !== false && isset($group_rec['pk_i_id'])) {
          $return[] = ModelOSP::newInstance()->getGroup($id);
        }
      }

      return (empty($return) ? false : $return);
    }
  }

  return false;
}


// DISABLE PROFILE WHEN USER HAS BEEN BLOCKED
function bpr_disable_blocked_user($user_id) {
  if(is_array($user_id) && isset($user_id['pk_i_id'])) {
    $user_id = $user_id['pk_i_id'];
  }
  
  $profile = ModelBPR::newInstance()->getSellerByUserId($user_id);

  if(isset($profile['pk_i_id']) && $profile['b_enabled'] == 1) {
    osc_add_flash_warning_message(sprintf(__('Business profile of this user (%s) has been deactivated.', 'business_profile'), $profile['s_identifier']), 'admin');
    ModelBPR::newInstance()->deactivateProfile($profile['pk_i_id']);
  }
}

osc_add_hook('disable_user', 'bpr_disable_blocked_user');


// REMOVE PROFILE WHEN USER HAS BEEN REMOVED
function bpr_removed_user($user_id, $message = true) {
  if(is_array($user_id) && isset($user_id['pk_i_id'])) {
    $user_id = $user_id['pk_i_id'];
  }
  
  $profile = ModelBPR::newInstance()->getSellerByUserId($user_id);
  
  if(isset($profile['pk_i_id'])) {
    bpr_remove_user_image($profile);
    bpr_remove_all_user_images($user_id);
    ModelBPR::newInstance()->removeProfileByUserId($user_id);
    
    if($message) {
      osc_add_flash_warning_message(sprintf(__('Business profile of this user (%s) has been removed.', 'business_profile'), $profile['s_identifier']), 'admin');
    }
  }
}

osc_add_hook('delete_user', 'bpr_removed_user');



// CHECK IF PROFILES USER IS IN PREMIUM GROUP
function bpr_control_premium($seller) {
  if(isset($seller['pk_i_id'])) {
    if(bpr_check_premium()) {
      $groups = explode(',', bpr_check_premium());
      $user_group = osp_get_user_group($seller['fk_i_user_id']);

      // Check group attribute -> update profile type if in (1,2,3)
      if(in_array($user_group, $groups)) {
        $u_group = ModelOSP::newInstance()->getGroup($user_group);

        if(($u_group['i_attr'] <> $seller['i_type']) && ($u_group['i_attr'] == 1 || $u_group['i_attr'] == 2 || $u_group['i_attr'] == 3)) {
          ModelBPR::newInstance()->updateProfileType($seller['pk_i_id'], $u_group['i_attr']);
        }

        if(in_array($user_group, $groups) && $seller['b_enabled'] == 1) {
          return true;
        }        
      }

      // Deactivate
      if(!in_array($user_group, $groups) && $seller['b_enabled'] == 1) {
        ModelBPR::newInstance()->deactivateProfile($seller['pk_i_id']);
        return true;
      }

      // Activate
      if(in_array($user_group, $groups) && $seller['b_enabled'] == 0 && bpr_param('auto_validate') == 1) {
        ModelBPR::newInstance()->activateProfile($seller['pk_i_id']);

        // Check group attribute -> update profile type if in (1,2,3)
        if(in_array($user_group, $groups)) {
          $u_group = ModelOSP::newInstance()->getGroup($user_group);

          if(($u_group['i_attr'] <> $seller['i_type']) && ($u_group['i_attr'] == 1 || $u_group['i_attr'] == 2 || $u_group['i_attr'] == 3)) {
            ModelBPR::newInstance()->updateProfileType($seller['pk_i_id'], $u_group['i_attr']);
          }
        }

        return true;
      }
    }
  }

  return false;
}


// WHEN NEW PAYMENT IS MADE, CHECK IF AUTO-VALIDATION OF PROVIDE SHOULD NOT BE PROCESSED
// We do not check specifically for payment type, just run verification for user that paid
function bpr_process_after_payment($payment_id) {
  if(function_exists('osp_install')) {
    $payment = ModelOSP::newInstance()->getPayment($payment_id);
    
    if(isset($payment['fk_i_user_id']) && $payment['fk_i_user_id'] > 0) {
      $user_id = $payment['fk_i_user_id'];
      $seller = ModelBPR::newInstance()->getSellerByUserId($user_id);
      bpr_control_premium($seller);
    }
  }
  
  bpr_check_seller_status();
}

osc_add_hook('osp_log_saved', 'bpr_process_after_payment');


// Check user profile status on load
function bpr_check_seller_status() {
  if(osc_is_web_user_logged_in()) {
    $seller = ModelBPR::newInstance()->getSellerByUserId(osc_logged_user_id());
    bpr_control_premium($seller);
  }
}

osc_add_hook('init', 'bpr_check_seller_status', 3);


// UPDATE COLORS BASED ON COMPANY ONE
function bpr_update_theme_colors() {
  $html = '';
  $location = Rewrite::newInstance()->get_location();
  $section  = Rewrite::newInstance()->get_section();

  $identifier = Params::getParam('identifier');
  $seller = ModelBPR::newInstance()->getSellerByIdentifier($identifier);

  if($location == 'custom' && $section == 'bpr-seller' && isset($seller['s_color']) && bpr_validate_color($seller['s_color']) && bpr_param('selectors') <> '') {
    $html .= '<style>';
    $html .= bpr_param('selectors') . ' {background:' . bpr_validate_color($seller['s_color']) . '!important;}';
    $html .= '</style>';
  }

  echo $html;
}

osc_add_hook('footer', 'bpr_update_theme_colors', 10);


// VALIDATE HEX COLOR
function bpr_validate_color($color) {
  if(preg_match('/^#[a-f0-9]{6}$/i', $color)){
    return $color;

  } else if(preg_match('/^[a-f0-9]{6}$/i', $color)) {
    return '#' . $color;
  }

  return false;
}


// GET COMPANY ITEMS URL
function bpr_company_items_url($user_id = -1) {
  if($user_id < 0) {
    $user_id = osc_item_user_id(); 
  }

  return osc_search_url(array('sUser' => $user_id));
}


// GET COMPANIES URL
function bpr_companies_url() {
  return osc_route_url('bpr-list');
}


// GET COMPANY URL
function bpr_company_url($user_id = NULL) {
  if($user_id === NULL) {
    $user_id = osc_item_user_id(); 
  }

  if($user_id > 0) {
    $seller = ModelBPR::newInstance()->getSellerByUserId($user_id);

    if(isset($seller['s_identifier']) && $seller['s_identifier'] <> '') {
      return osc_route_url('bpr-seller', array('identifier' => $seller['s_identifier']));
    }
  }

  return false;
}


// GET COMPANY COLOR
function bpr_company_color($user_id = -1) {
  if($user_id < 0) {
    $user_id = osc_item_user_id(); 
  }

  if($user_id > 0) {
    $seller = ModelBPR::newInstance()->getSellerByUserId($user_id);

    if(isset($seller['s_color']) && $seller['s_color'] <> '' && bpr_validate_color($seller['s_color'])) {
      return bpr_validate_color($seller['s_color']);
    }
  }

  return false;
}


// GET COVER IMAGE
function bpr_get_img($user_id, $file = '', $type = 'icon') {
  if($file == '' || $user_id == 0) {
    $file = 'none.png';
  }


  //$img_url = osc_base_url() . 'oc-content/plugins/business_profile/img/';
  //$img_path = osc_content_path() . 'plugins/business_profile/img/' . $type . '/';

  $img_url = UPLOADS_WEB_PATH . 'business_profile/' . $user_id . '/';
  $img_path = UPLOADS_PATH . 'business_profile/' . $user_id . '/' . $type . '/';
  
  $split = explode('?v=', $file);
  $img = $split[0];
  $ver = isset($split[1]) ? $split[1] : '';
  $ext = pathinfo($img, PATHINFO_EXTENSION);
  
  // if(strpos($img, '?')) {
    // $img_name = substr($img, 0, strpos($img, '?'));
  // } else {
    // $img_name = $img;
  // }

  if(file_exists($img_path . $img)) {
    return $img_url . $type . '/' . $img;
    
  } else {
    return osc_base_url() . 'oc-content/plugins/business_profile/img/' . $type . '_default.' . (($type == 'icon' || $type == 'logo') ? 'png' : 'jpg');
  }
}


// CHECK IF IMAGE EXISTS FOR PROFILE
function bpr_check_img($user_id, $file = '', $type = 'icon') {
  if($file == '' || $file == 'none.png') {
    return false;
  }

  //$img_path = osc_content_path() . 'plugins/business_profile/img/' . $type . '/';
  $img_path = UPLOADS_PATH . 'business_profile/' . $user_id . '/' . $type . '/';

  $split = explode('?v=', $file);
  $img = $split[0];
  $ver = isset($split[1]) ? $split[1] : '';
  $ext = pathinfo($img, PATHINFO_EXTENSION);

  if(file_exists($img_path . $img)) {
    return true;
  }
  
  return false;
}

// REMOVE USER IMAGE
function bpr_remove_user_image($seller, $type = NULL) {
  $icon = @$seller['s_icon'];
  $logo = @$seller['s_logo'];
  $cover = @$seller['s_cover'];
  
  // Remove icon
  if($icon != '' && ($type == 'icon' || $type === NULL)) {
    //$img_path = osc_content_path() . 'plugins/business_profile/img/' . $type . '/';
    $img_path = UPLOADS_PATH . 'business_profile/' . $seller['fk_i_user_id'] . '/' . $type . '/';

    $split = explode('?v=', $icon);
    $img = $split[0];
    $ver = isset($split[1]) ? $split[1] : '';
    $ext = pathinfo($img, PATHINFO_EXTENSION);
  
    if(file_exists($img_path . $img)) {
      @unlink($img_path . $img);
    }
    
    ModelBPR::newInstance()->updateSellerField($seller['pk_i_id'], array('s_icon' => NULL));
  }

  // Remove logo
  if($logo != '' && ($type == 'logo' || $type === NULL)) {
    //$img_path = osc_content_path() . 'plugins/business_profile/img/' . $type . '/';
    $img_path = UPLOADS_PATH . 'business_profile/' . $seller['fk_i_user_id'] . '/' . $type . '/';

    $split = explode('?v=', $logo);
    $img = $split[0];
    $ver = isset($split[1]) ? $split[1] : '';
    $ext = pathinfo($img, PATHINFO_EXTENSION);
  
    if(file_exists($img_path . $img)) {
      @unlink($img_path . $img);
    }
    
    ModelBPR::newInstance()->updateSellerField($seller['pk_i_id'], array('s_logo' => NULL));
  }
  
  // Remove cover
  if($cover != '' && ($type == 'cover' || $type === NULL)) {
    //$img_path = osc_content_path() . 'plugins/business_profile/img/' . $type . '/';
    $img_path = UPLOADS_PATH . 'business_profile/' . $seller['fk_i_user_id'] . '/' . $type . '/';

    $split = explode('?v=', $cover);
    $img = $split[0];
    $ver = isset($split[1]) ? $split[1] : '';
    $ext = pathinfo($img, PATHINFO_EXTENSION);
  
    if(file_exists($img_path . $img)) {
      @unlink($img_path . $img);
    }
    
    ModelBPR::newInstance()->updateSellerField($seller['pk_i_id'], array('s_cover' => NULL));
  }
}


// REMOVE GALLERY IMAGE
function bpr_remove_user_gallery_image($seller, $img) {
  $list  = @$seller['s_gallery'];
  $list = array_filter(array_map('trim', explode(',', $list)));
  $status = false;
  $img = rawurldecode($img);
  
  // Remove images
  if($img != '' && count($list) > 0 && in_array($img, $list) && isset($seller['fk_i_user_id'])) {
    $path = UPLOADS_PATH . 'business_profile/' . $seller['fk_i_user_id'] . '/gallery/';
 
    if(file_exists($path . $img)) {
      @unlink($path . $img);
      $status = true;
    }
    
    if (($key = array_search($img, $list)) !== false) {
      unset($list[$key]);
    }
    
    $list = implode(',', $list);
    
    ModelBPR::newInstance()->updateSellerField($seller['pk_i_id'], array('s_gallery' => $list));
  }
  
  return $status;
}

// REMOVE ALL USER GALLERY IMAGES
function bpr_remove_all_user_images($user_id) {
  $dirs = array();
  
  if($user_id > 0) {
    $dirs[] = UPLOADS_PATH . 'business_profile/' . $user_id . '/icon/';
    $dirs[] = UPLOADS_PATH . 'business_profile/' . $user_id . '/logo/';
    $dirs[] = UPLOADS_PATH . 'business_profile/' . $user_id . '/cover/';
    $dirs[] = UPLOADS_PATH . 'business_profile/' . $user_id . '/gallery/';
    $dirs[] = UPLOADS_PATH . 'business_profile/' . $user_id . '/';

    foreach($dirs as $dir) {
      if(file_exists($dir)) {
        $files = glob($dir . '*');
        foreach($files as $file){
          if(is_file($file)) {
            @unlink($file); 
          }
        }
        
        @rmdir($dir);
      }
    }  
  }
}


// GET USER IMAGE
function bpr_get_user_img($user_id = -1) {
  if($user_id < 0) {
    $user_id = osc_item_user_id(); 
  }

  $seller = ModelBPR::newInstance()->getSellerByUserId($user_id);

  if(isset($seller['s_icon']) && $seller['s_icon'] <> '') {
    return bpr_get_img($seller['fk_i_user_id'], $seller['s_icon'], 'icon');
  } else {
    return bpr_get_img(0, 'none.png', 'icon');
  }
}


// GET USER LOGO
function bpr_get_user_logo($user_id = -1) {
  if($user_id < 0) {
    $user_id = osc_item_user_id(); 
  }

  $seller = ModelBPR::newInstance()->getSellerByUserId($user_id);

  if(isset($seller['s_logo']) && $seller['s_logo'] <> '') {
    return bpr_get_img($seller['fk_i_user_id'], $seller['s_logo'], 'logo');
  } else {
    return bpr_get_img(0, 'none.png', 'logo');
  }
}


// META TITLE
function bpr_meta_title($tag) {
  $location = Rewrite::newInstance()->get_location();
  $section  = Rewrite::newInstance()->get_section();

  if($section == 'bpr-home') {
    $tag = __('Companies', 'business_profile') . ' - ' . osc_page_title();

  } else if ($section == 'bpr-seller') {
    $identifier = Params::getParam('identifier');
    $seller = ModelBPR::newInstance()->getSellerByIdentifier($identifier);
    
    if($identifier != '' && isset($seller['fk_i_user_id']) && $seller['fk_i_user_id'] > 0) {
      $user = User::newInstance()->findByPrimaryKey($seller['fk_i_user_id']);

      if(isset($user['s_name']) && $user['s_name'] <> '') {
        $tag = sprintf(__('%s\'s business profile', 'business_profile'), $user['s_name']) . ' - ' . osc_page_title();
      } else {
        $tag = __('Business profile does not exists', 'business_profile') . ' - ' . osc_page_title();
      }
    } else {
      $tag = __('Business profile does not exists', 'business_profile') . ' - ' . osc_page_title();
    }
  }

  return $tag;
}

osc_add_filter('meta_title_filter', 'bpr_meta_title', 6);
osc_add_filter('structured_data_title_filter', 'bpr_meta_title', 6);


// META DESCRIPTION
function bpr_meta_description($tag) {
  $location = Rewrite::newInstance()->get_location();
  $section  = Rewrite::newInstance()->get_section();

  if($section == 'bpr-home') {
    $tag = __('Companies and corporates selling on our classifieds.', 'business_profile');

  } else if ($section == 'bpr-seller') {
    $identifier = Params::getParam('identifier');
    $seller = ModelBPR::newInstance()->getSellerByIdentifier($identifier);
    
    if(isset($seller['fk_i_user_id']) && $seller['fk_i_user_id'] > 0) {
      $user = User::newInstance()->findByPrimaryKey($seller['fk_i_user_id']);
      View::newInstance()->_exportVariableToView('user', $user); 
      
      $tag = osc_user_info();
    }
  }

  return $tag;
}

osc_add_filter('meta_description_filter', 'bpr_meta_description', 6);
osc_add_filter('structured_data_description_filter', 'bpr_meta_description', 6);


// GET LOOP TEMPLATE FILE
function bpr_draw_loop_file($theme_path) {
  if(file_exists($theme_path . 'loop-single.php')) {
    return 'loop-single';
  } else if(file_exists($theme_path . 'loop-gallery.php')) {
    return 'loop-gallery';
  }

  return false;
}


// RESOLVE LOOP TEMPLATE
function bpr_draw_resolve() {
  static $resolved = null;

  if($resolved !== null) {
    return $resolved;
  }

  $resolved = false;
  $parent = '';

  if(method_exists('WebThemes', 'getCurrentThemeIsChild')) {
    $parent = WebThemes::newInstance()->getCurrentThemeIsChild();
  }

  $theme_path = WebThemes::newInstance()->getCurrentThemePath();
  $filename = bpr_draw_loop_file($theme_path);

  if($filename !== false) {
    $resolved = array('location' => 1, 'path' => $theme_path, 'filename' => $filename);
    return $resolved;
  }

  if($parent != '') {
    $theme_path = THEMES_PATH . $parent . '/';
    $filename = bpr_draw_loop_file($theme_path);

    if($filename !== false) {
      $resolved = array('location' => 2, 'path' => $theme_path, 'filename' => $filename);
      return $resolved;
    }
  }

  return $resolved;
}


// DRAW ITEM
function bpr_draw_item($c = NULL, $view = 'gallery', $premium = false, $class = false) {
  $resolve = bpr_draw_resolve();

  if($resolve !== false) {
    require $resolve['path'] . $resolve['filename'] . '.php';
  }
}


// CHECK IF ITEMS CAN BE DRAWN
function bpr_draw_check() {
  $resolve = bpr_draw_resolve();

  if($resolve !== false) {
    return $resolve['location'];
  }

  return false;
}


// GET DAY SHORTCUT
function bpr_day($id, $type = 'short') {
  if($id == 1) { 
    return ($type == 'short' ? __('Mo', 'business_profile') : __('Monday', 'business_profile'));
  } else if($id == 2) { 
    return ($type == 'short' ? __('Tu', 'business_profile') : __('Tuesday', 'business_profile'));
  } else if($id == 3) { 
    return ($type == 'short' ? __('We', 'business_profile') : __('Wednesday', 'business_profile'));
  } else if($id == 4) { 
    return ($type == 'short' ? __('Th', 'business_profile') : __('Thursday', 'business_profile'));
  } else if($id == 5) { 
    return ($type == 'short' ? __('Fr', 'business_profile') : __('Friday', 'business_profile'));
  } else if($id == 6) { 
    return ($type == 'short' ? __('Sa', 'business_profile') : __('Saturday', 'business_profile'));
  } else if($id == 7) { 
    return ($type == 'short' ? __('Su', 'business_profile') : __('Sunday', 'business_profile'));
  } 
}


// GET SOCIAL ICON
function bpr_soc_icon($id, $type = 'icon') {
  if($id == 'fb') { 
    return ($type == 'icon' ? 'fa-facebook' : __('Facebook', 'business_profile'));
  } else if($id == 'tw') { 
    return ($type == 'icon' ? 'fa-twitter' : __('X (Twitter)', 'business_profile'));
  } else if($id == 'vm') { 
    return ($type == 'icon' ? 'fa-vimeo' : __('Vimeo', 'business_profile'));
  } else if($id == 'yt') { 
    return ($type == 'icon' ? 'fa-youtube' : __('Youtube', 'business_profile'));
  } else if($id == 'li') { 
    return ($type == 'icon' ? 'fa-linkedin' : __('LinkedIn', 'business_profile'));
  } else if($id == 'ig') {
    return ($type == 'icon' ? 'fa-instagram' : __('Instagram', 'business_profile'));
   } else if($id == 'pn') { 
    return ($type == 'icon' ? 'fa-pinterest-p' : __('Pinterest', 'business_profile'));
  } else {  // 'ot' option
    return ($type == 'icon' ? 'fa-share' : __('Share', 'business_profile'));
  }
} 


// GET USER TYPE
function bpr_user_type($seller) {
  if(!isset($seller['pk_i_id'])) {
    return false;
  }
  
  $type = $seller['i_type'];
  $label = '';

  if($type == 1) {
    $label = __('Basic', 'business_profile');
    
  } else if ($type == 2) {
    $label = __('Pro', 'business_profile');
    
  } else if ($type == 3) {
    $label = __('VIP', 'business_profile');
  }
  
  $label = osc_apply_filter('bpr_user_type', $label, $seller['fk_i_user_id'], $seller);
  
  return $label;
}


// GENERATE USER LABEL
// Supports rewrite by Osclass Pay
// $user_id - osclass user id
function bpr_user_label($seller) {
  if(!isset($seller['pk_i_id'])) {
    return false;
  }
  
  $html = '<div class="bpr-user-type bpr-type-' . $seller['i_type'] . '">';
  $html .= bpr_user_type($seller);
  $html .= '</div>';
  
  $html = osc_apply_filter('bpr_user_label', $html, $seller['fk_i_user_id'], $seller);
  
  return $html;
}


// CREATE LOCALE SELECT BOX
function bpr_locale_box( $file, $param = '', $id = -1 ) {
  $html = '';
  $locales = OSCLocale::newInstance()->listAllEnabled();
  $current = bpr_get_locale();
  $id_string = '';

  if($id > 0 && $param <> '') {
    $id_string = '&' . $param . '=' . $id;
  }

  $html .= '<select rel="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/' . $file . $id_string . '" class="mb-select mb-select-locale" id="bprLocale" name="bprLocale">';

  foreach( $locales as $l ) {
    $html .= '<option value="' . $l['pk_c_code'] . '" ' . ($current == $l['pk_c_code'] ? 'selected="selected"' : '') . '>' . $l['s_name'] . '</option>';
  }
 
  $html .= '</select>';
  return $html;
}


// GET CURRENT OR DEFAULT ADMIN LOCALE
function bpr_get_locale() {
  $locales = OSCLocale::newInstance()->listAllEnabled();

  if(Params::getParam('bprLocale') <> '') {
    $current = Params::getParam('bprLocale');
  } else {
    $current = (osc_current_user_locale() <> '' ? osc_current_user_locale() : osc_current_admin_locale());
    $current_exists = false;

    // check if current locale exist in front-office
    foreach( $locales as $l ) {
      if($current == $l['pk_c_code']) {
        $current_exists = true;
      }
    }

    if( !$current_exists ) {
      $i = 0;
      foreach( $locales as $l ) {
        if( $i==0 ) {
          $current = $l['pk_c_code'];
        }

        $i++;
      }
    }
  }

  return $current;
}



// CORE FUNCTIONS
function bpr_param($name) {
  return osc_get_preference($name, 'plugin-business_profile');
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
function bpr_is_demo($ignore_admin = false) {
  if(osc_logged_admin_username() == 'admin' && $ignore_admin === false) {
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


if( !function_exists('osc_is_contact_page') ) {
  function osc_is_contact_page() {
    $location = Rewrite::newInstance()->get_location();
    $section = Rewrite::newInstance()->get_section();
    if( $location == 'contact' ) {
      return true ;
    }

    return false ;
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



// CATEGORIES WORK
function bpr_cat_tree($list = array()) {
  if(!is_array($list) || empty($list)) {
    $list = Category::newInstance()->listAll();
  }

  $array = array();
  //$root = Category::newInstance()->findRootCategoriesEnabled();

  foreach($list as $c) {
    if($c['fk_i_parent_id'] <= 0) {
      $array[$c['pk_i_id']] = array('pk_i_id' => $c['pk_i_id'], 's_name' => $c['s_name']);
      $array[$c['pk_i_id']]['sub'] = bpr_cat_sub($list, $c['pk_i_id']);
    }
  }

  return $array;
}

function bpr_cat_sub($list, $parent_id) {
  $array = array();
  //$cats = Category::newInstance()->findSubcategories($id);

  if(count($list) > 0) {
    foreach($list as $c) {
      if($c['fk_i_parent_id'] == $parent_id) {  echo $c['s_name'];
        $array[$c['pk_i_id']] = array('pk_i_id' => $c['pk_i_id'], 's_name' => $c['s_name']);
        $array[$c['pk_i_id']]['sub'] = bpr_cat_sub($list, $c['pk_i_id']);
      }
    }
  }
      
  return $array;
}

function bpr_cat_list($selected = array(), $categories = '', $level = 0) {
  if($categories == '' || $level == 0) {
    $categories = bpr_cat_tree($categories);
  }


  foreach($categories as $c) {
    echo '<option value="' . $c['pk_i_id'] . '" ' . (in_array($c['pk_i_id'], $selected) ? 'selected="selected"' : '') . '>' . str_repeat('-', $level) . ($level > 0 ? ' ' : '') . $c['s_name'] . '</option>';

    if(@count($c['sub']) > 0) {
      bpr_cat_list($selected, $c['sub'], $level + 1);
    }
  }
}


function bpr_list_values_ol($values) {
 if(count($values) > 0 && is_array($values)) {
    foreach($values as $v) {
      ?>

      <li class="mb-val" id="val_<?php echo $v['pk_i_id']; ?>">
        <?php bpr_div_value($v); ?>
      
        <ol>
          <?php 
            if(isset($v['values']) && count($v['values']) > 0) { 
              bpr_list_values_ol($v['values']); 
            }
          ?>
        </ol>
      </li>
    <?php
    }
  }
}



// GENERATE PAGINATION
function bpr_admin_paginate($file, $page_id, $per_page, $count_all, $class = '', $params = '') {
  $html = '';
  $page_id = (int)$page_id;
  $page_id = ($page_id <= 0 ? 1 : $page_id);
  $base_link = osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=' . $file . $params;

  if($per_page < $count_all) {
    $html .= '<div id="mb-pagination" class="' . $class . '">';
    $html .= '<div class="mb-pagination-wrap">';
    $html .= '<div>' . __('Page:', 'business_profile') . '</div>';

    $pages = ceil($count_all/$per_page); 
    $page_actual = ($page_id == '' ? 1 : $page_id);

    if($pages > 6) {

      // Too many pages to list them all
      if($page_id == 1) { 
        $ids = array(1,2,3, $pages);

      } else if ($page_id > 1 && $page_id < $pages) {
        $ids = array(1,$page_id-1, $page_id, $page_id+1, $pages);

      } else {
        $ids = array(1, $page_id-2, $page_id-1, $page_id);
      }

      $old = -1;
      $ids = array_unique(array_filter($ids));

      foreach($ids as $i) {
        $url = $base_link . '&pageId=' . $i;
        
        if($old <> -1 && $old <> $i - 1) {
          $html .= '<span>&middot;&middot;&middot;</span>';
        }

        $html .= '<a href="' . $url . '" ' . ($page_actual == $i ? 'class="mb-active"' : '') . '>' . $i . '</a>';
        $old = $i;
      }

    } else {

      // List all pages
      for ($i = 1; $i <= $pages; $i++) {
        $url = $base_link . '&pageId=' . $i;
        $html .= '<a href="' . $url . '" ' . ($page_actual == $i ? 'class="mb-active"' : '') . '>' . $i . '</a>';
      }
    }

    $html .= '</div>';
    $html .= '</div>';
  }

  return $html;
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

if(!function_exists('osc_is_backoffice')) {
  function osc_is_backoffice() {
    if(defined('OC_ADMIN')) {
      if(OC_ADMIN === true) {
        return true;
      }
    }
    return false;
  }
}

if(!function_exists('osc_is_frontoffice')) {
  function osc_is_frontoffice() {
    return !osc_is_backoffice();
  }
}

if(!function_exists('osc_subdomain_enabled')) {
  function osc_subdomain_enabled() {
    return false;
  }
}

if(!function_exists('osc_subdomain_param')) {
  function osc_subdomain_param() {
    return false;
  }
}

if(!function_exists('osc_subdomain_id')) {
  function osc_subdomain_id() {
    return false;
  }
}

if(!function_exists('osc_subdomain_type')) {
  function osc_subdomain_type() {
    return false;
  }
}

?>