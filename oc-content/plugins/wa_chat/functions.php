<?php

// ADD CHECKBOX TO ITEM FORM
function wac_item_form($catId) {
  if(wac_param('ask_seller') == 1 && wac_param('enable_items') == 1 && wac_check_category($catId) !== false) {
    include 'form/item_form.php';
  }
}

osc_add_hook('item_form', 'wac_item_form');
osc_add_hook('item_edit', 'wac_item_form');


// MANAGE CHECKBOX VALUE
function wac_item_edit_update($item) {
  $data = ModelWAC::newInstance()->getData(@$item['pk_i_id']); 
  $enabled = (Params::getParam('wac_enable') <> '' ? 1 : 0);
  
  if(isset($data['fk_i_item_id'])) {
    ModelWAC::newInstance()->updateData($item['pk_i_id'], array('b_enable' => $enabled));
  } else {
    ModelWAC::newInstance()->insertData(array('fk_i_item_id' => $item['pk_i_id'], 'b_enable' => $enabled));
  }
}


osc_add_hook('posted_item', 'wac_item_edit_update');
osc_add_hook('edited_item', 'wac_item_edit_update');


// GET VIEWS
function wac_get_views($item_id) {
  $stats = wac_get_stats($item_id);
  
  if($stats !== false) {
    return @$stats['i_views'] > 0 ? $stats['i_views'] : 0; 
  }
}


// GET CLICKS
function wac_get_clicks($item_id) {
  $stats = wac_get_stats($item_id);
  
  if($stats !== false) {
    return @$stats['i_clicks'] > 0 ? $stats['i_clicks'] : 0; 
  }
}


// GET STATS
function wac_get_stats($item_id, $data = array()) {
  if(wac_param('enable_stats') == 1 && $item_id > 0) {
    if(empty($data)) {
      $data = ModelWAC::newInstance()->getData($item_id);
    }
    
    return $data;
  }
  
  return false;
}


// UPDATE CLICKS ON BUTTON
function wac_collect_clicks() {
  $item_id = Params::getParam('itemId');
  
  if(wac_param('only_logged') == 1 && !osc_is_web_user_logged_in()) {
    return false;
  }
  
  if(wac_param('enable_stats') == 1 && $item_id > 0) {
    ModelWAC::newInstance()->updateClicks($item_id);
  }
  
  exit;
}

osc_add_hook('ajax_wac_collect_clicks', 'wac_collect_clicks');


// SHOW CHAT BUTTON FOR ITEM
function wac_item_chat_button($item_id = NULL) {
  $item = array();
  
  if($item_id <= 0 || $item_id == osc_item_id()) {
    $item_id = osc_item_id();
    $item = osc_item();
  }
  
  if($item_id > 0 && (empty($item) || !isset($item['pk_i_id']))) {
    $item = Item::newInstance()->findByPrimaryKey($item_id);
  }
  
  if($item_id <= 0) {
    return false;
  }

  $data = ModelWAC::newInstance()->getData($item_id);
  $enabled = isset($data['b_enable']) ? $data['b_enable'] : 0;

  // User not enabled whatsapp button
  if(wac_param('ask_seller') == 1 && $enabled != 1) {
    return false;
  }
  
  $phone = wac_get_phone($item_id, $item);
  $phone_raw = wac_get_phone($item_id, $item, false);
  
  if($phone === false || wac_param('enable_items') != 1 || wac_check_category($item['fk_i_category_id']) === false) {  // add category restriction here
    if(wac_is_demo(true)) {
      $phone = $phone_raw = '+12345678';
    } else {
      return false;
    }
  }
  
  $title = (@$item['s_title'] <> '' ? $item['s_title'] : '');
  $url = (osc_item_url() != '' ? osc_item_url() : osc_item_url_from_item($item));
  $button_text = trim(wac_param('button_text'));
  $logged_ok = true;
  
  if(wac_param('only_logged') == 1 && !osc_is_web_user_logged_in()) {
    $logged_ok = false;
  }

  if(wac_param('enable_stats') == 1 && $item_id > 0) {
    if(isset($data['fk_i_item_id'])) {
      ModelWAC::newInstance()->updateViews($item_id);
    } else {
      ModelWAC::newInstance()->insertData(array(
        'fk_i_item_id' => $item_id,
        'b_enable' => 1,
        'i_clicks' => 0,
        'i_views' => 1
      ));
    }
  }
  
  $stats = wac_get_stats($item_id, $data);
  
  if($item['fk_i_user_id'] == osc_logged_user_id() && $item['fk_i_user_id'] > 0 || osc_is_admin_user_logged_in()) {
    $tooltip = osc_esc_html(sprintf(__('Views: %dx', 'wa_chat'), @$stats['i_views'] > 0 ? $stats['i_views'] : 0)) . '&#013;';
    $tooltip .= osc_esc_html(sprintf(__('Clicks: %dx', 'wa_chat'), @$stats['i_clicks'] > 0 ? $stats['i_clicks'] : 0));
  } else {
    $tooltip = '';
  }
  ?>
    <style>
      <?php
        echo (wac_param('button_padding') >= 0 ? 'a.wac-btn.wac-item {padding:' . wac_param('button_padding') . 'px;}' : '');
        echo (wac_param('button_bg_color') != '' ? 'a.wac-btn.wac-item {background:' . wac_param('button_bg_color') . ';}' : '');
        echo (wac_param('button_font_color') != '' ? 'a.wac-btn.wac-item, a.wac-btn.wac-item:hover {color:' . wac_param('button_font_color') . ';}' : '');
        echo (wac_param('icon_bg_color') != '' ? 'a.wac-btn.wac-item .wac-icon {background:' . wac_param('icon_bg_color') . ';}' : '');
        echo (wac_param('icon_padding') >= 0 ? 'a.wac-btn.wac-item .wac-icon img {padding:' . wac_param('icon_padding') . 'px;}' : '');
        echo (trim(wac_param('custom_css')) != '' ? wac_param('custom_css') : '');
      ?>
    </style>
    
    <div class="wac-box">
      <a target="_blank" class="wac-btn wac-item wac-<?php echo strtolower(wac_param('size')); ?> wac-<?php echo strtolower(str_replace('_', '-', wac_param('style'))); ?> wac-phone-<?php echo strtolower(str_replace('_', '-', (wac_param('phone_in_button') <> '' ? wac_param('phone_in_button') : 'NONE'))); ?> <?php if(!$logged_ok) { ?>wac-disabled<?php } ?>" href="<?php echo ($logged_ok ? wac_get_link($phone, $title, $url) : '#'); ?>" title="<?php echo osc_esc_html(!$logged_ok ? __('You must login to use contact button', 'wa_chat') : __('Click to initiate WhatsApp chat', 'wa_chat')); ?>&#013;<?php echo $tooltip; ?>" data-stats="<?php echo wac_param('enable_stats'); ?>" data-url="<?php echo wac_param('enable_stats') == 1 ? osc_base_url(true) . '?page=ajax&action=runhook&hook=wac_collect_clicks' : ''; ?>" data-item-id="<?php echo $item_id; ?>">
        <div class="wac-icon">
          <img src="<?php echo wac_chat_icon_url(); ?>" alt="<?php echo osc_esc_html(__('Whatsapp chat', 'wa_chat')); ?>" width=32 height=32/>
        </div>
        
        <?php if(in_array(wac_param('style'), array('BUTTON_ROUNDED','BUTTON_SQUARE','BUTTON_BUBBLE'))) { ?>
          <div class="wac-text">
            <span>
              <?php 
                echo ($button_text != '' ? $button_text : __('Whatsapp chat', 'wa_chat')); 
                echo (wac_param('phone_in_button') == 'NEW_LINE' ? '<br/>' : ' ');
                echo (wac_param('phone_in_button') != '' ? $phone_raw : '');
              ?>
            </span>
          </div>
        <?php } ?>
      </a>
    </div>
  <?php
}

osc_add_hook('init', function() {
  $hooks = array_filter(array_unique(array_map('trim', explode(',', wac_param('hooks')))));
  
  if(!empty($hooks)) {
    foreach($hooks as $hook) {
      osc_add_hook($hook, 'wac_item_chat_button');
    }
  }
});


// SHOW WEB CONTACT BUTTON
function wac_web_contact_button() {
  $phone_raw = trim(wac_param('web_phone'));
  $phone = wac_sanitize_number($phone_raw);

  if($phone == '' || wac_param('web_enable') != 1) { 
    return false;
  }
  
  $button_text = trim(wac_param('web_button_text'));
  $logged_ok = true;
  
  if(wac_param('only_logged') == 1 && !osc_is_web_user_logged_in()) {
    $logged_ok = false;
  }

  $url = osc_base_url();
  $title = meta_title();
  ?>
    <style>
      <?php
        echo (wac_param('web_button_padding') >= 0 ? 'a.wac-btn.wac-web {padding:' . wac_param('web_button_padding') . 'px;}' : '');
        echo (wac_param('web_margin') >= 0 ? 'a.wac-btn.wac-web {margin:' . wac_param('web_margin') . 'px;}' : '');
        echo (wac_param('web_button_bg_color') != '' ? 'a.wac-btn.wac-web {background:' . wac_param('web_button_bg_color') . ';}' : '');
        echo (wac_param('web_button_font_color') != '' ? 'a.wac-btn.wac-web, a.wac-btn:not(.wac-disabled).wac-web:hover {color:' . wac_param('web_button_font_color') . ';}' : '');
        echo (wac_param('web_icon_bg_color') != '' ? 'a.wac-btn.wac-web .wac-icon {background:' . wac_param('web_icon_bg_color') . ';}' : '');
        echo (wac_param('web_icon_padding') >= 0 ? 'a.wac-btn.wac-web .wac-icon img {padding:' . wac_param('web_icon_padding') . 'px;}' : '');
        echo (trim(wac_param('web_custom_css')) != '' ? wac_param('web_custom_css') : '');
      ?>
    </style>
    
    <div class="wac-box">
      <a target="_blank" class="wac-btn wac-web wac-<?php echo strtolower(wac_param('web_size')); ?> wac-position-<?php echo strtolower(str_replace('_', '-', (wac_param('web_position') <> '' ? wac_param('web_position') : 'BOTTOM-LEFT'))); ?> wac-<?php echo strtolower(str_replace('_', '-', wac_param('web_style'))); ?> wac-phone-<?php echo strtolower(str_replace('_', '-', (wac_param('web_phone_in_button') <> '' ? wac_param('web_phone_in_button') : 'NONE'))); ?> <?php if(!$logged_ok) { ?>wac-disabled<?php } ?>" href="<?php echo ($logged_ok ? wac_get_link($phone, $title, $url, 'web') : '#'); ?>" title="<?php echo osc_esc_html(!$logged_ok ? __('You must login to use contact button', 'wa_chat') : __('Click to initiate WhatsApp chat', 'wa_chat')); ?>">
        <div class="wac-icon">
          <img src="<?php echo wac_chat_icon_url('web'); ?>" alt="<?php echo osc_esc_html(__('Whatsapp chat', 'wa_chat')); ?>" width=32 height=32/>
        </div>
        
        <?php if(in_array(wac_param('style'), array('BUTTON_ROUNDED','BUTTON_SQUARE','BUTTON_BUBBLE'))) { ?>
          <div class="wac-text">
            <span>
              <?php 
                echo ($button_text != '' ? $button_text : __('Whatsapp chat', 'wa_chat')); 
                echo (wac_param('phone_in_button') == 'NEW_LINE' ? '<br/>' : ' ');
                echo (wac_param('phone_in_button') != '' ? $phone_raw : '');
              ?>
            </span>
          </div>
        <?php } ?>
      </a>
    </div>
  <?php
}

osc_add_hook('footer', function() {
  if(wac_param('web_hook') == 1) {
    wac_web_contact_button();
  }
});


// GENERATE LINK
function wac_get_link($phone, $text, $url, $type = 'item') {
  if($type == 'item') {
    $text = urlencode(sprintf(__('Hello! I have question on your item "%s" (%s)', 'wa_chat'), osc_highlight($text, 60), $url));
  } else {
    $text = urlencode(sprintf(__('Hello! I have question about your website "%s" (%s)', 'wa_chat'), osc_highlight($text, 60), $url));
  }
  
  $link = 'https://wa.me/' . $phone . '/?text=' . $text;
  
  return $link;
}


// GET PROPER ICON
function wac_chat_icon_url($type = 'item') {
  if($type == 'item') {
    $id = (wac_param('icon') > 0 ? wac_param('icon') : 1);
  } else {
    $id = (wac_param('web_icon') > 0 ? wac_param('web_icon') : 1);
  }
  
  return osc_base_url() . 'oc-content/plugins/wa_chat/img/' . $id . '.svg';
}


// CHECK IF CATEGORY ENABLED
function wac_check_category($id) {
  $cats = trim(wac_param('category'));
  $cats = array_filter(explode(',', $cats));
  
  if(empty($cats) || in_array($id, $cats)) {
    return true;
  }
  
  return false;
}


// GET PHONE NUMBER FROM ITEM
function wac_get_phone($item_id = NULL, $item = array(), $sanitize = true) {
  if($item_id <= 0 || $item_id == osc_item_id()) {
    $item_id = osc_item_id();
    $item = osc_item();
  }
  
  if($item_id > 0 && (empty($item) || !isset($item['pk_i_id']))) {
    $item = Item::newInstance()->findByPrimaryKey($item_id);
  }
  
  $data = ModelWAC::newInstance()->getData($item_id);
  
  if(wac_param('ask_seller') == 1 && isset($data['b_enable']) && $data['b_enable'] == 0) {
    return false; 
  } else if (wac_param('enable_existing') == 0 && !isset($data['b_enable'])) {
    return false;
  }
  
  $phone = wac_phone_by_type($item_id, $item);
  
  if($phone == '' && wac_param('user_profile_phone') == 1 && @$item['fk_i_user_id'] > 0) {
    $user = User::newInstance()->findByPrimaryKey($item['fk_i_user_id']);
    
    if(isset($user['pk_i_id'])) {
      $phone = trim($user['s_phone_mobile'] <> '' ? $user['s_phone_mobile'] : $user['s_phone_land']);
    }
  }
  
  if(trim($phone) == '') {
    return false;
  }
  
  return $sanitize ? wac_sanitize_number($phone) : $phone;
}



// GET PHONE NUMBER BY TYPE
function wac_phone_by_type($item_id = NULL, $item = array()) {
  if($item_id <= 0 || $item_id == osc_item_id()) {
    $item_id = osc_item_id();
    $item = osc_item();
  }
  
  if($item_id > 0 && (empty($item) || !isset($item['pk_i_id']))) {
    $item = Item::newInstance()->findByPrimaryKey($item_id);
  }
  
  if(wac_param('use_phone') == 'OSCLASS_PHONE') {
    if(isset($item['s_contact_phone']) && trim($item['s_contact_phone']) <> '' && $item['b_show_phone'] == 1) {
      return trim($item['s_contact_phone']);
    }
    
  } else if(wac_param('use_phone') == 'OSCLASS_OTHER') {
    if(isset($item['s_contact_other']) && trim($item['s_contact_other']) <> '') {
      return trim($item['s_contact_other']);
    }
    
  } else if(wac_param('use_phone') == 'CUSTOM_FIELD' && wac_param('custom_field_slug') != '') {
    $data = Item::newInstance()->metaFields($item_id);

    if(count($data) > 0) {
      foreach($data as $d) {
        if($d['s_slug'] == wac_param('custom_field_slug')) {
          if(trim($d['s_value']) <> '') {
            return trim($d['s_value']);
          }

          break;
        }
      }
    }

  } else if(wac_param('use_phone') == 'CITY_AREA') {
    if(isset($item['s_city_area']) && trim($item['s_city_area']) <> '') {
      return trim($item['s_city_area']);
    }
    
  } else if(wac_param('use_phone') == 'THEME_FIELD') {
    return ModelWAC::newInstance()->getItemThemeNumber($item_id, osc_current_web_theme());

  } else if(wac_param('use_phone') == 'TELEPHONE_PLUGIN' && function_exists('osc_telephone_number') && class_exists('Modelphone')){ 
    $data = Modelphone::newInstance()->t_check_value($item_id);

    if(trim(@$data['s_telephone']) <> '') {
      return trim($data['s_telephone']);
    }
  }
  
  return false;
}


// CREATE VALID PHONE NUMBER
function wac_sanitize_number($phone_number, $type = 'item') {
  if(wac_param('sanitize_phone') != 1) {
    return $phone_number;
  }
  
  $min_length = (wac_param('phone_length') > 0 ? wac_param('phone_length') : 0);
  $country_code = wac_default_country_code();

  //$phone_number = str_replace('00', '+', $phone_number);

  if(substr($phone_number, 0, 2) == '00') {
    $phone_number = '+' . substr($phone_number, 2);
  }
  
  $phone_number = ltrim($phone_number, '0');
  $phone_number = str_replace('/', '', $phone_number);
  $phone_number = str_replace('(', '', $phone_number);
  $phone_number = str_replace(')', '', $phone_number);
  $phone_number = str_replace('-', '', $phone_number);
  $phone_number = str_replace(' ', '', $phone_number);

  $phone_number = filter_var($phone_number, FILTER_SANITIZE_NUMBER_INT);

  if ($type == 'item' && $min_length > 0 && strlen($phone_number) < $min_length && $country_code != '' && strpos($phone_number, $country_code) !== 0 && substr($phone_number, 0, 1) <> '+') {
    $phone_number = $country_code . $phone_number;
  }

  if(substr($phone_number, 0, 1) <> '+') {
    $phone_number = '+' . $phone_number;
  }
 
  return $phone_number;
}


// PREPARE COUNTRY CODE
function wac_default_country_code() {
  $country_code = trim(wac_param('default_country_code'));
  
  if(strpos($country_code, '00') === 0) {
    $country_code = substr($country_code, 2);
  }
  
  if(strpos($country_code, '+') !== 0) {
    $country_code = '+' . $country_code;
  }
  
  if($country_code == '+') {
    $country_code = '';
  }
  
  return $country_code;  
}


// CORE FUNCTIONS
function wac_param($name) {
  return osc_get_preference($name, 'plugin-wa_chat');
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
function wac_is_demo($ignore_admin = false) {
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



// CATEGORIES WORK
function wac_cat_tree($list = array()) {
  if(!is_array($list) || empty($list)) {
    $list = Category::newInstance()->listAll();
  }

  $array = array();
  //$root = Category::newInstance()->findRootCategoriesEnabled();

  foreach($list as $c) {
    if($c['fk_i_parent_id'] <= 0) {
      $array[$c['pk_i_id']] = array('pk_i_id' => $c['pk_i_id'], 's_name' => $c['s_name']);
      $array[$c['pk_i_id']]['sub'] = wac_cat_sub($list, $c['pk_i_id']);
    }
  }

  return $array;
}

function wac_cat_sub($list, $parent_id) {
  $array = array();
  //$cats = Category::newInstance()->findSubcategories($id);

  if(count($list) > 0) {
    foreach($list as $c) {
      if($c['fk_i_parent_id'] == $parent_id) {  echo $c['s_name'];
        $array[$c['pk_i_id']] = array('pk_i_id' => $c['pk_i_id'], 's_name' => $c['s_name']);
        $array[$c['pk_i_id']]['sub'] = wac_cat_sub($list, $c['pk_i_id']);
      }
    }
  }
      
  return $array;
}

function wac_cat_list($selected = array(), $categories = '', $level = 0) {
  if($categories == '' || $level == 0) {
    $categories = wac_cat_tree($categories);
  }


  foreach($categories as $c) {
    echo '<option value="' . $c['pk_i_id'] . '" ' . (in_array($c['pk_i_id'], $selected) ? 'selected="selected"' : '') . '>' . str_repeat('-', $level) . ($level > 0 ? ' ' : '') . $c['s_name'] . '</option>';

    if(@count($c['sub']) > 0) {
      wac_cat_list($selected, $c['sub'], $level + 1);
    }
  }
}


function wac_list_values_ol($values) {
 if(count($values) > 0 && is_array($values)) {
    foreach($values as $v) {
      ?>

      <li class="mb-val" id="val_<?php echo $v['pk_i_id']; ?>">
        <?php wac_div_value($v); ?>
      
        <ol>
          <?php 
            if(isset($v['values']) && count($v['values']) > 0) { 
              wac_list_values_ol($v['values']); 
            }
          ?>
        </ol>
      </li>
    <?php
    }
  }
}

?>