<?php

// require_once () . osc_plugin_folder(__FILE__) . 'email.php';


// CORE ROW HELPERS (POLYFILL FOR OLDER OSCLASS)
if(!function_exists('osc_get_item_row')) {
  function osc_get_item_row($id, $cache = true) {
    if($id <= 0) {
      return false;
    }

    if($cache === true && View::newInstance()->_exists('item_' . $id)) {
      return View::newInstance()->_get('item_' . $id);
    }

    $item = Item::newInstance()->findByPrimaryKey((int)$id);
    View::newInstance()->_exportVariableToView('item_' . $id, $item);

    return $item;
  }
}

if(!function_exists('osc_get_user_row')) {
  function osc_get_user_row($id, $cache = true) {
    if($id <= 0) {
      return false;
    }

    if($cache === true && View::newInstance()->_exists('user_' . $id)) {
      return View::newInstance()->_get('user_' . $id);
    }

    $user = User::newInstance()->findByPrimaryKey((int)$id);
    View::newInstance()->_exportVariableToView('user_' . $id, $user);

    return $user;
  }
}


// HOOK CONTACT BUTTONS
osc_add_hook('init', function() {
  $button_hooks = array_filter(array_unique(array_map('trim', explode(',', trim((string)im_param('button_hooks'))))));

  if(!empty($button_hooks)) {
    foreach($button_hooks as $button_hook) {
      osc_add_hook($button_hook, function() {
        im_contact_button(NULL, false, array('echo' => true, 'is_hooked' => true));
      });
    }
  }

  $button_hooks_user_raw = trim((string)im_param('button_hooks_user'));
  if($button_hooks_user_raw == '') {
    $button_hooks_user_raw = 'user_public_profile_sidebar_bottom';
  }

  $button_hooks_user = array_filter(array_unique(array_map('trim', explode(',', $button_hooks_user_raw))));

  if(!empty($button_hooks_user)) {
    foreach($button_hooks_user as $button_hook) {
      osc_add_hook($button_hook, function() {
        $user_id = im_resolve_user_id();
        if($user_id > 0) {
          im_contact_user_button($user_id, false, array('echo' => true, 'is_hooked' => true));
        }
      });
    }
  }
});


// SAFE STRING CAST (PHP 8.1+ NULL PASSED TO STRING FUNCTIONS)
function im_str($value) {
  if($value === null || $value === false) {
    return '';
  }
  return (string)$value;
}


// SAFE ARRAY VALUE READ
function im_arr($array, $key, $default = '') {
  if(is_array($array) && array_key_exists($key, $array)) {
    return $array[$key];
  }
  return $default;
}


// CHECK VALID THREAD ROW
function im_is_valid_thread($thread) {
  return (is_array($thread) && isset($thread['i_thread_id']) && (int)$thread['i_thread_id'] > 0);
}


// CHECK IF PLUGIN PREFERENCE IS EMPTY
function im_param_is_empty($name) {
  $value = im_param($name);
  return ($value === '' || $value === null || $value === false);
}


// BUILD CREATE THREAD URL
function im_create_thread_url($options = array()) {
  if(isset($options['user_id']) && (int)$options['user_id'] > 0) {
    return osc_route_url('im-create-thread-user', array('user-id' => (int)$options['user_id']));
  }

  if(isset($options['item_id']) && (int)$options['item_id'] > 0) {
    return osc_route_url('im-create-thread', array('item-id' => (int)$options['item_id']));
  }

  return '';
}


// CHECK REGISTERED USER EXISTS
function im_user_exists($user_id) {
  $user_id = (int)$user_id;
  if($user_id <= 0) {
    return false;
  }

  $user = osc_get_user_row($user_id);
  return ($user !== false && isset($user['pk_i_id']) && (int)$user['pk_i_id'] > 0);
}


// CHECK IF CURRENT PAGE IS PUBLIC USER PROFILE
function im_is_public_profile_page() {
  if(function_exists('osc_get_osclass_location') && function_exists('osc_get_osclass_section')) {
    if(osc_get_osclass_location() == 'user' && osc_get_osclass_section() == 'pub_profile') {
      return true;
    }
  }

  if(class_exists('Rewrite')) {
    $rewrite = Rewrite::newInstance();
    if($rewrite->get_location() == 'user' && $rewrite->get_section() == 'pub_profile') {
      return true;
    }
  }

  return false;
}


// RESOLVE TARGET USER ID FOR USER CONTACT BUTTON (NOT LOGGED USER)
function im_resolve_user_id($user = null) {
  if(is_array($user) && isset($user['pk_i_id'])) {
    return (int)$user['pk_i_id'];
  }

  if(is_numeric($user) && (int)$user > 0) {
    return (int)$user;
  }

  if(im_is_public_profile_page()) {
    $profile_id = (int)Params::getParam('id');
    if($profile_id > 0) {
      return $profile_id;
    }

    if(Params::getParam('username') != '') {
      $profile_user = User::newInstance()->findByUsername(Params::getParam('username'));
      if(is_array($profile_user) && isset($profile_user['pk_i_id']) && (int)$profile_user['pk_i_id'] > 0) {
        return (int)$profile_user['pk_i_id'];
      }
    }
  }

  if(function_exists('osc_is_ad_page') && osc_is_ad_page() && function_exists('osc_item_user_id')) {
    $item_user_id = (int)osc_item_user_id();
    if($item_user_id > 0) {
      return $item_user_id;
    }
  }

  if(function_exists('osc_item_user_id')) {
    $item_user_id = (int)osc_item_user_id();
    if($item_user_id > 0) {
      return $item_user_id;
    }
  }

  if(function_exists('osc_user_id')) {
    $view_user_id = (int)osc_user_id();
    if($view_user_id > 0) {
      return $view_user_id;
    }
  }

  return 0;
}


// NORMALIZE USER PARTICIPANT ARRAY
function im_user_pack($pk_i_id, $s_name, $s_email) {
  return array(
    'pk_i_id' => (int)$pk_i_id,
    's_name' => im_str($s_name),
    's_email' => im_str($s_email)
  );
}


// GET USER DETAILS FOR DISPLAY
function im_get_user_details($user_id, $name = '', $email = '') {
  $user_id = (int)$user_id;
  $details = array(
    'pk_i_id' => $user_id,
    's_name' => im_str($name),
    's_email' => im_str($email),
    's_phone' => '',
    's_location' => '',
    's_website' => '',
    'profile_url' => '',
    'image' => im_profile_img_url($user_id, $name)
  );

  if($user_id > 0) {
    $user = osc_get_user_row($user_id);
    if($user !== false && isset($user['pk_i_id'])) {
      $details['s_name'] = $user['s_name'];
      $details['s_email'] = $user['s_email'];
      $details['s_phone'] = trim(im_str(im_arr($user, 's_phone_mobile')));
      if($details['s_phone'] == '' && isset($user['s_phone_land'])) {
        $details['s_phone'] = trim(im_str($user['s_phone_land']));
      }
      $loc = array_filter(array(im_str(im_arr($user, 's_country')), im_str(im_arr($user, 's_region')), im_str(im_arr($user, 's_city'))));
      $details['s_location'] = implode(', ', $loc);
      $details['s_website'] = trim(im_str(im_arr($user, 's_website')));
      $details['profile_url'] = osc_user_public_profile_url($user_id);
      $details['image'] = im_profile_img_url($user_id, $details['s_name'], $user);
    }
  }

  return $details;
}


// RESOLVE THREAD ROLES FOR LOGGED OR SECRET VIEWER
function im_thread_context($thread, $secret = '') {
  $secret = (string)$secret;
  if($secret == 'n') {
    $secret = '';
  }

  $result = array(
    'viewer' => im_user_pack(0, '', ''),
    'target' => im_user_pack(0, '', ''),
    'send_type' => 1,
    'viewer_is_from' => false,
    'can_view' => false,
    'target_removed' => false,
    'secret' => $secret,
    'notify' => 1
  );

  if(!is_array($thread) || !isset($thread['i_thread_id'])) {
    return $result;
  }

  $logged_id = (osc_is_web_user_logged_in() ? (int)osc_logged_user_id() : 0);
  $is_from = ($logged_id > 0 && (int)$thread['i_from_user_id'] === $logged_id);
  $is_to = ($logged_id > 0 && (int)$thread['i_to_user_id'] === $logged_id);
  $secret_from = ($secret !== '' && $secret === (string)$thread['s_from_secret']);
  $secret_to = ($secret !== '' && $secret === (string)$thread['s_to_secret']);

  // Logged-in users: membership only (copied secret URLs must not open someone else's chat).
  // Guests: valid thread secret only.
  if($logged_id > 0) {
    $result['can_view'] = ($is_from || $is_to);
    $viewer_is_from = $is_from;
  } else {
    $result['can_view'] = ($secret_from || $secret_to);
    $viewer_is_from = $secret_from;
  }

  if(!$result['can_view']) {
    return $result;
  }

  if($viewer_is_from) {
    $result['viewer_is_from'] = true;
    $result['send_type'] = 0;
    $result['viewer'] = im_user_pack(im_arr($thread, 'i_from_user_id', 0), im_arr($thread, 's_from_user_name'), im_arr($thread, 's_from_user_email'));
    $result['target'] = im_user_pack(im_arr($thread, 'i_to_user_id', 0), im_arr($thread, 's_to_user_name'), im_arr($thread, 's_to_user_email'));
    $result['secret'] = im_str(im_arr($thread, 's_from_secret'));
    $result['notify'] = (int)$thread['i_from_user_notify'];
    $result['target_removed'] = ((int)$thread['i_to_user_id'] <= 0 && trim((string)$thread['s_to_user_email']) == '');
  } else {
    $result['viewer_is_from'] = false;
    $result['send_type'] = 1;
    $result['viewer'] = im_user_pack(im_arr($thread, 'i_to_user_id', 0), im_arr($thread, 's_to_user_name'), im_arr($thread, 's_to_user_email'));
    $result['target'] = im_user_pack(im_arr($thread, 'i_from_user_id', 0), im_arr($thread, 's_from_user_name'), im_arr($thread, 's_from_user_email'));
    $result['secret'] = im_str(im_arr($thread, 's_to_secret'));
    $result['notify'] = (int)$thread['i_to_user_notify'];
    $result['target_removed'] = ((int)$thread['i_from_user_id'] <= 0 && trim((string)$thread['s_from_user_email']) == '');
  }

  return $result;
}


// FIND EXISTING THREAD BEFORE CREATE
function im_find_existing_thread($from_user_id, $from_user_email, $to_user_id, $to_user_email, $item_id = null) {
  $from_user_id = (int)$from_user_id;
  $to_user_id = (int)$to_user_id;
  $item_id = (int)$item_id;
  $one_thread = (im_param('one_thread_per_user') == 1);

  if($one_thread && $from_user_id > 0 && $to_user_id > 0) {
    return ModelIM::newInstance()->getThreadBetweenUsers($from_user_id, $to_user_id);
  }

  if($item_id > 0) {
    return ModelIM::newInstance()->getThreadByFromUserAndItem($from_user_id, $from_user_email, $item_id);
  }

  if($one_thread && $from_user_id > 0 && $to_user_id > 0) {
    return ModelIM::newInstance()->getThreadBetweenUsers($from_user_id, $to_user_id);
  }

  return false;
}


// REDIRECT TO EXISTING THREAD MESSAGES
function im_redirect_to_thread($thread, $prefer_secret = '') {
  if(!is_array($thread) || !isset($thread['i_thread_id'])) {
    return false;
  }

  $secret = ($prefer_secret != '' ? $prefer_secret : 'n');
  header('Location: ' . osc_route_url('im-messages', array('thread-id' => $thread['i_thread_id'], 'secret' => $secret)));
  exit;
}


// RESOLVE CREATE THREAD TARGET (item or registered user only)
function im_resolve_create_target() {
  $result = array(
    'mode' => '',
    'item_id' => 0,
    'user_id' => 0,
    'item' => array(),
    'item_details' => false,
    'to_user_id' => 0,
    'to_user_name' => '',
    'to_user_email' => '',
    'error' => '',
    'redirect' => osc_base_url()
  );

  $route_user_id = (int)Params::getParam('user-id');
  $route_item_id = (int)Params::getParam('item-id');

  if($route_user_id > 0) {
    $result['mode'] = 'user';
    $result['user_id'] = $route_user_id;
    $result['redirect'] = (im_user_exists($route_user_id) ? osc_user_public_profile_url($route_user_id) : osc_base_url());
  } else if($route_item_id > 0) {
    $result['mode'] = 'item';
    $result['item_id'] = $route_item_id;
    $item = osc_get_item_row($route_item_id);
    $result['item'] = ($item !== false ? $item : array());
    $result['redirect'] = (isset($result['item']['pk_i_id']) ? osc_item_url_from_item($result['item']) : osc_base_url());
  } else {
    $result['error'] = __('Invalid contact target. Thread could not be created.', 'instant_messenger');
    return $result;
  }

  if(im_param('only_logged') == 1 && !osc_is_web_user_logged_in()) {
    $result['error'] = __('Please login, only authenticated users can send instant messages.', 'instant_messenger');
    return $result;
  }

  $from_user_id = (osc_is_web_user_logged_in() ? osc_logged_user_id() : null);

  if($result['mode'] == 'user') {
    if(!im_user_exists($result['user_id'])) {
      $result['error'] = __('User not found. You can only message registered users directly.', 'instant_messenger');
      return $result;
    }

    $user = osc_get_user_row($result['user_id']);
    $result['to_user_id'] = (int)$user['pk_i_id'];
    $result['to_user_name'] = $user['s_name'];
    $result['to_user_email'] = $user['s_email'];

    if($from_user_id > 0 && $result['to_user_id'] == $from_user_id) {
      $result['error'] = __('You cannot contact yourself.', 'instant_messenger');
      return $result;
    }

    return $result;
  }

  $item = $result['item'];
  if(!isset($item['pk_i_id'])) {
    $result['error'] = __('Invalid listing ID, thread could not be created.', 'instant_messenger');
    return $result;
  }

  $result['item_details'] = im_get_item_details($result['item_id'], $item);
  if($result['item_details'] === false) {
    $result['error'] = __('Invalid listing ID, thread could not be created.', 'instant_messenger');
    return $result;
  }

  $result['to_user_id'] = (int)im_arr($item, 'fk_i_user_id', 0);
  $result['to_user_name'] = im_str(im_arr($item, 's_contact_name'));
  $result['to_user_email'] = im_str(im_arr($item, 's_contact_email'));

  if($from_user_id > 0 && $result['to_user_id'] > 0 && $result['to_user_id'] == $from_user_id) {
    $result['error'] = __('You cannot contact yourself.', 'instant_messenger');
    return $result;
  }

  if(im_param('one_thread_per_user') == 1 && $result['to_user_id'] > 0) {
    $result['mode'] = 'user_redirect';
    $result['user_id'] = $result['to_user_id'];
  }

  return $result;
}


// RENDER TARGET USER BAR HTML
function im_render_target_bar($details) {
  if(!is_array($details) || trim((string)$details['s_name']) == '') {
    return '';
  }

  $html  = '<div class="im-row im-target-bar im-body">';
  $html .= '<div class="im-col-3 im-target-img"><img src="' . osc_esc_html($details['image']) . '" alt="" /></div>';
  $html .= '<div class="im-col-21 im-target-info">';

  if((int)$details['pk_i_id'] > 0 && $details['profile_url'] != '') {
    $html .= '<div class="im-line im-target-name"><a target="_blank" href="' . osc_esc_html($details['profile_url']) . '">' . osc_esc_html($details['s_name']) . '</a></div>';
  } else {
    $html .= '<div class="im-line im-target-name"><strong>' . osc_esc_html($details['s_name']) . '</strong></div>';
  }

  if(trim((string)$details['s_phone']) != '') {
    $html .= '<div class="im-line im-target-phone">' . osc_esc_html($details['s_phone']) . '</div>';
  }
  if(trim((string)$details['s_location']) != '') {
    $html .= '<div class="im-line im-target-location">' . osc_esc_html($details['s_location']) . '</div>';
  }
  if(trim((string)$details['s_website']) != '') {
    $url = $details['s_website'];
    if(strpos($url, 'http') !== 0) {
      $url = 'http://' . $url;
    }
    $html .= '<div class="im-line im-target-website"><a target="_blank" rel="nofollow" href="' . osc_esc_html($url) . '">' . osc_esc_html($details['s_website']) . '</a></div>';
  }

  $html .= '</div></div>';
  return $html;
}


// RENDER ITEM CONTEXT LINE HTML
function im_render_item_context($item_id, $item = array(), $item_details = false) {
  $item_id = (int)$item_id;
  if($item_id <= 0) {
    return '';
  }

  if($item_details === false) {
    $item_details = im_get_item_details($item_id, $item);
  }

  if($item_details === false || !is_array($item) || !isset($item['pk_i_id'])) {
    return '';
  }

  $html  = '<div class="im-row im-item-context im-body">';
  $html .= '<div class="im-col-3 im-item-resource"><a target="_blank" href="' . osc_item_url_ns($item['pk_i_id']) . '"><img src="' . $item_details['resource'] . '" /></a></div>';
  $html .= '<div class="im-col-21">';
  $html .= '<div class="im-line im-item-label">' . __('Related listing', 'instant_messenger') . '</div>';
  $html .= '<div class="im-line im-item-title"><a target="_blank" href="' . osc_item_url_ns($item['pk_i_id']) . '">' . osc_highlight($item['s_title'], 50) . '</a></div>';
  $html .= '<div class="im-line im-item-price">' . $item_details['price'] . '</div>';
  $html .= '</div></div>';

  return $html;
}


// MERGE CONTACT BUTTON OPTIONS
function im_contact_options($options = array()) {
  $defaults = array(
    'echo' => true,
    'is_hooked' => false,
    'custom_class' => ''
  );

  if(!is_array($options)) {
    $options = array();
  }

  return array_merge($defaults, $options);
}


// OUTPUT CONTACT BUTTON OR LINK
function im_contact_output($link, $class, $text, $title, $meta, $link_only, $options = array()) {
  $options = im_contact_options($options);
  $class .= ($options['is_hooked'] ? ' im-hooked' : '');
  $class .= (trim($title) != '' ? ' im-has-tooltip' : '');
  $class .= (trim((string)$options['custom_class']) != '' ? ' ' . $options['custom_class'] : '');

  if($link == '') {
    return false;
  }

  if($link_only) {
    return $link;
  }

  $btn  = '<a href="' . $link . '" class="' . $class . '" title="' . ($title != '' ? osc_esc_html($title) : '') . '"' . ($meta != '' ? ' ' . $meta : '') . '>';
  $btn .= '<span>' . IM_CHAT_ICON . $text . '</span>';
  $btn .= '</a>';

  if($options['echo']) {
    echo $btn;
    return true;
  }

  return $btn;
}


// CREATE BUTTON TO SEND PM (ITEM)
function im_contact_button($item = NULL, $link_only = false, $options = array()) {
  $item = ($item === NULL ? osc_item() : $item);
  $item_id = (int)(is_array($item) && isset($item['pk_i_id']) ? $item['pk_i_id'] : osc_item_id());

  $link = $title = $meta = '';
  $class = 'im-contact2';
  $text = __('Chat with seller', 'instant_messenger');

  if(im_param('only_logged') == 1 && !osc_is_web_user_logged_in()) {
    $link = osc_user_login_url();
    $class .= ' im-disabled';
    $title = __('You must be logged in to chat with sellers.', 'instant_messenger');

  } else if($item_id > 0) {
    if(osc_is_web_user_logged_in() && isset($item['fk_i_user_id']) && $item['fk_i_user_id'] == osc_logged_user_id()) {
      $class .= ' im-disabled';
      $title = __('This is your listing. You cannot message yourself.', 'instant_messenger');
      $meta = 'onclick="return false;"';
      $link = '#';

    } else if(im_param('one_thread_per_user') == 1 && isset($item['fk_i_user_id']) && (int)$item['fk_i_user_id'] > 0) {
      $link = im_create_thread_url(array('user_id' => (int)$item['fk_i_user_id']));

    } else {
      $link = im_create_thread_url(array('item_id' => $item_id));
    }
  }

  return im_contact_output($link, $class, $text, $title, $meta, $link_only, $options);
}


// CREATE BUTTON TO SEND PM (REGISTERED USER)
function im_contact_user_button($user = NULL, $link_only = false, $options = array()) {
  $user_id = im_resolve_user_id($user);

  if($user_id <= 0 || !im_user_exists($user_id)) {
    return false;
  }

  $link = $title = $meta = '';
  $class = 'im-contact2 im-contact-user';
  $text = __('Send message', 'instant_messenger');

  if(im_param('only_logged') == 1 && !osc_is_web_user_logged_in()) {
    $link = osc_user_login_url();
    $class .= ' im-disabled';
    $title = __('You must be logged in to send messages.', 'instant_messenger');
  } else if(osc_is_web_user_logged_in() && $user_id == osc_logged_user_id()) {
    $class .= ' im-disabled';
    $title = __('You cannot message yourself.', 'instant_messenger');
    $meta = 'onclick="return false;"';
    $link = '#';
  } else {
    $link = im_create_thread_url(array('user_id' => $user_id));
  }

  return im_contact_output($link, $class, $text, $title, $meta, $link_only, $options);
}


// LINK ONLY FOR ITEM CONTACT
function im_contact_link($item = NULL, $options = array()) {
  return im_contact_button($item, true, $options);
}


// LINK ONLY FOR USER CONTACT
function im_contact_user_link($user = NULL, $options = array()) {
  return im_contact_user_button($user, true, $options);
}



// CHECK USER LIMITS
function im_check_user_limits($user_id, $email = '', $get_limits = false) {
  if(im_param('limit_enabled') == 0) {
    return true;
  }
  
  $user_id = (int)$user_id;
  $email = trim((string)$email);
  $email = ($email == '' ? 'dummy@mail.com' : $email);

  $count_messages = ModelIM::newInstance()->countAllMessagesFromUser($user_id, $email);
  $count_users = ModelIM::newInstance()->countAllUsersContactedByUser($user_id, $email);
  $count_reg_days = NULL;
  
  
  // Get days from registration if user exists
  if($user_id > 0) {
    $user = osc_get_user_row($user_id);
    
    if(isset($user['dt_reg_date'])) {
      $count_reg_days = floor((time() - strtotime($user['dt_reg_date']))/86400);  // (60 * 60 * 24)
    }
  }
  
  // Get days based on first user thread
  if($count_reg_days === NULL) {
    $first_thread = ModelIM::newInstance()->getFirstThreadByUser($user_id, $email);

    if(isset($first_thread['d_datetime'])) {
      $count_reg_days = floor((time() - strtotime($first_thread['d_datetime']))/86400);  // (60 * 60 * 24)
    }
  }


  // Check if user is eligible to ignore limits
  if($count_messages >= im_param('limit_disable_after_messages') && $count_users >= im_param('limit_disable_after_users') && (int)$count_reg_days >= im_param('limit_disable_after_days_from_reg')) {
    return true;
  }


  // User is not eligible to disable limits, count messages & users contacted in defined period
  $count_messages = ModelIM::newInstance()->countAllMessagesFromUser($user_id, $email, im_param('limit_period_hours'));
  $count_users = ModelIM::newInstance()->countAllUsersContactedByUser($user_id, $email, im_param('limit_period_hours'));
  $count_reg_days = NULL;
  
  
  // Only show limit
  if($get_limits === true) {
    return 
      sprintf(__('Your current usage is %d messages sent and %d users contacted in last %d hours.', 'instant_messenger'), $count_messages, $count_users, im_param('limit_period_hours'))
      . ' ' . sprintf(__('Limit is %d messages sent or %d users contacted within period of %d hours. After reaching this limit, new messages cannot be sent.', 'instant_messenger'), im_param('limit_max_messages'), im_param('limit_max_users'), im_param('limit_period_hours')); 
  }


  // Check if user reached limits
  if($count_messages > im_param('limit_max_messages') || $count_users > im_param('limit_max_users')) {
    return sprintf(__('You\'ve reached limit of %d messages sent or %d users contacted within period of %d hours. New messages will not be sent. You must wait until you can sent another messages.', 'instant_messenger'), im_param('limit_max_messages'), im_param('limit_max_users'), im_param('limit_period_hours')); 
  }

  return true;
}


// ADD LINK TO HEADER
function im_header_link_hook() {
  if(im_param('hook_header_links') == 1) {
    echo '<a href="' . osc_route_url('im-threads') . '">' . __('Messenger', 'instant_messenger') . '</a>';    
  }
}

osc_add_hook('header_links', 'im_header_link_hook');


// GENERATE PAGINATION
function im_admin_paginate($file, $page_id, $per_page, $count_all, $class = '', $params = '') {
  $html = '';
  $page_id = (int)$page_id;
  $page_id = ($page_id <= 0 ? 1 : $page_id);
  $base_link = osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=' . $file . $params;

  if($per_page < $count_all) {
    $html .= '<div id="mb-pagination" class="' . $class . '">';
    $html .= '<div class="mb-pagination-wrap">';
    $html .= '<div>' . __('Page:', 'instant_messenger') . '</div>';

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

// BASE UPLOADS PATH FOR ATTACHMENTS
function im_uploads_path() {
  $path = UPLOADS_PATH . 'instant_messenger/';
  return osc_apply_filter('im_uploads_path', $path);
}


// BASE UPLOADS URL FOR ATTACHMENTS
function im_uploads_url() {
  $url = UPLOADS_WEB_PATH . 'instant_messenger/';
  return osc_apply_filter('im_uploads_url', $url);
}


// LEGACY DOWNLOAD FOLDER PATH (pre-3.0 attachments)
function im_old_download_path() {
  return osc_content_path() . 'plugins/instant_messenger/download/';
}


// LEGACY DOWNLOAD FOLDER PATHS TO CHECK (download / downloads)
function im_old_download_paths() {
  $paths = array(im_old_download_path());
  $alt = osc_content_path() . 'plugins/instant_messenger/downloads/';

  if($alt != $paths[0] && !in_array($alt, $paths)) {
    $paths[] = $alt;
  }

  return $paths;
}


// TRUE WHILE LEGACY DOWNLOAD FOLDER STILL EXISTS (MIGRATION NOT DONE)
function im_legacy_download_pending() {
  foreach(im_old_download_paths() as $legacy_path) {
    if(is_dir($legacy_path)) {
      return true;
    }
  }

  return false;
}


// LIST FILES IN DIRECTORY RECURSIVELY
function im_collect_files_recursive($dir) {
  $list = array();
  if(!is_dir($dir)) {
    return $list;
  }

  $dir = rtrim($dir, '/\\') . '/';
  $items = @scandir($dir);
  if(!is_array($items)) {
    return $list;
  }

  foreach($items as $item) {
    if($item === '.' || $item === '..') {
      continue;
    }

    $path = $dir . $item;
    if(is_dir($path)) {
      $list = array_merge($list, im_collect_files_recursive($path));
    } else if(is_file($path)) {
      $list[] = $path;
    }
  }

  return $list;
}


// REMOVE FILE OR DIRECTORY RECURSIVELY
function im_remove_path_recursive($path) {
  if(!file_exists($path)) {
    return true;
  }

  if(is_file($path)) {
    return @unlink($path);
  }

  if(!is_dir($path)) {
    return false;
  }

  $path = rtrim($path, '/\\') . '/';
  $items = @scandir($path);
  if(!is_array($items)) {
    return @rmdir($path);
  }

  foreach($items as $item) {
    if($item === '.' || $item === '..') {
      continue;
    }

    im_remove_path_recursive($path . $item);
  }

  return @rmdir($path);
}


// MOVE ONE LEGACY ATTACHMENT FILE INTO UPLOADS
function im_move_legacy_attachment_file($old_file, $thread_id, $file_name) {
  $thread_id = (int)$thread_id;
  $file_name = trim(im_str($file_name));
  if($thread_id <= 0 || $file_name == '' || !is_file($old_file)) {
    return false;
  }

  im_check_attachment_dir($thread_id);
  $new_file = im_attachment_path($thread_id, $file_name);
  if(!$new_file) {
    return false;
  }

  if(!file_exists($new_file)) {
    return @rename($old_file, $new_file);
  }

  @unlink($old_file);
  return true;
}


// THREAD ATTACHMENT DIRECTORY
function im_attachment_dir($thread_id) {
  $thread_id = (int)$thread_id;
  $dir = im_uploads_path() . $thread_id . '/';
  return osc_apply_filter('im_attachment_dir', $dir, $thread_id);
}


// THREAD ATTACHMENT FILE PATH
function im_attachment_path($thread_id, $file_name) {
  $file_name = trim(im_str($file_name));
  if($file_name == '' || (int)$thread_id <= 0) {
    return false;
  }

  $path = im_attachment_dir($thread_id) . $file_name;
  return osc_apply_filter('im_attachment_path', $path, $thread_id, $file_name);
}


// THREAD ATTACHMENT FILE URL
function im_attachment_url($thread_id, $file_name) {
  $file_name = trim(im_str($file_name));
  if($file_name == '' || (int)$thread_id <= 0) {
    return false;
  }

  $url = im_uploads_url() . (int)$thread_id . '/' . rawurlencode($file_name);
  return osc_apply_filter('im_attachment_url', $url, $thread_id, $file_name);
}


// CREATE UPLOAD DIRECTORIES
function im_check_attachment_dir($thread_id) {
  $thread_id = (int)$thread_id;
  if($thread_id <= 0) {
    return false;
  }

  $base = im_uploads_path();
  if(!file_exists($base)) {
    @mkdir($base, 0755, true);
  }

  $dir = im_attachment_dir($thread_id);
  if(!file_exists($dir)) {
    @mkdir($dir, 0755, true);
  }

  return is_dir($dir);
}


// DELETE SINGLE ATTACHMENT FILE
function im_delete_attachment_file($thread_id, $file_name) {
  $path = im_attachment_path($thread_id, $file_name);
  if($path && file_exists($path)) {
    @unlink($path);
  }
}


// DELETE ALL ATTACHMENTS IN THREAD
function im_delete_thread_attachments($thread_id) {
  $thread_id = (int)$thread_id;
  if($thread_id <= 0) {
    return false;
  }

  $messages = ModelIM::newInstance()->getMessagesByThreadIdWithFile($thread_id);
  if(is_array($messages) && !empty($messages)) {
    foreach($messages as $m) {
      if(trim(im_str(im_arr($m, 's_file'))) != '') {
        im_delete_attachment_file($thread_id, $m['s_file']);
      }
    }
  }

  $dir = im_attachment_dir($thread_id);
  if(is_dir($dir)) {
    $files = glob($dir . '*');
    if(is_array($files) && !empty($files)) {
      foreach($files as $file) {
        if(is_file($file)) {
          @unlink($file);
        }
      }
    }
    @rmdir($dir);
  }

  return true;
}


// MIGRATE ATTACHMENTS FROM PLUGIN DOWNLOAD FOLDER TO UPLOADS (RUN ONLY WHILE LEGACY FOLDER EXISTS)
function im_migrate_attachments() {
  if(!im_legacy_download_pending()) {
    return true;
  }

  if(!is_dir(im_uploads_path())) {
    @mkdir(im_uploads_path(), 0755, true);
  }

  $file_map = array();
  $messages = ModelIM::newInstance()->getAllMessagesWithFile();
  if(is_array($messages) && !empty($messages)) {
    foreach($messages as $m) {
      $thread_id = (int)im_arr($m, 'fk_i_thread_id', 0);
      $file = trim(im_str(im_arr($m, 's_file')));
      if($thread_id <= 0 || $file == '') {
        continue;
      }

      $file_map[$file] = $thread_id;
    }
  }

  foreach(im_old_download_paths() as $old_path) {
    if(!is_dir($old_path)) {
      continue;
    }

    $legacy_files = im_collect_files_recursive($old_path);
    if(is_array($legacy_files) && !empty($legacy_files)) {
      foreach($legacy_files as $old_file) {
        $file_name = basename($old_file);
        if($file_name == '' || $file_name === '.' || $file_name === '..') {
          continue;
        }

        if(isset($file_map[$file_name])) {
          im_move_legacy_attachment_file($old_file, $file_map[$file_name], $file_name);
        } else {
          @unlink($old_file);
        }
      }
    }

    im_remove_path_recursive($old_path);
  }

  return true;
}


// RUN MIGRATION IN BACKOFFICE WHILE LEGACY DOWNLOAD FOLDER EXISTS
function im_migrate_attachments_on_admin() {
  if(defined('OC_ADMIN') && OC_ADMIN) {
    im_migrate_attachments();
  }
}

osc_add_hook('init_admin', 'im_migrate_attachments_on_admin', 5);


// REMOVE THREAD
function im_remove_thread($thread_id, $user_id, $email, $secret) {
  $thread = ModelIM::newInstance()->getThreadById($thread_id);
  
  $remove_type = '';
  if(
    $user_id > 0 && $thread['i_from_user_id'] == $user_id 
    || $secret != '' && $secret == $thread['s_from_secret']
    || $email != '' && $email == $thread['s_from_user_email']
 ) {
    $remove_type = 'FROM';
  } else if (
    $user_id > 0 && $thread['i_to_user_id'] == $user_id 
    || $secret != '' && $secret == $thread['s_to_secret']
    || $email != '' && $email == $thread['s_to_user_email']  
 ) {
    $remove_type = 'TO';
  } else {
    return false; 
  }
  
  if(
    $remove_type == 'FROM' && $thread['i_to_user_id'] == NULL && $thread['s_to_user_email'] == NULL
    || $remove_type == 'TO' && $thread['i_from_user_id'] == NULL && $thread['s_from_user_email'] == NULL
 ) {
    im_delete_thread_attachments($thread_id);
    ModelIM::newInstance()->removeMessagesByThreadId($thread_id);
    ModelIM::newInstance()->removeThreadById($thread_id);
  } else {
    ModelIM::newInstance()->cleanThreadUser($thread_id, $remove_type);
  }
  
  return true;
}


// DELETE SINGLE MESSAGE
function im_remove_message($id) {
  if($id <= 0) { 
    return false; 
  }
  
  $message = ModelIM::newInstance()->getMessageById($id);
  
  if(trim(im_str(im_arr($message, 's_file'))) != '') {
    im_delete_attachment_file((int)im_arr($message, 'fk_i_thread_id', 0), $message['s_file']);
  }
  
  ModelIM::newInstance()->removeMessage($id);
  return true;
}


// GENERATE PAGINATION
function im_paginate($page_id, $per_page, $count_all, $class = '') {
  $html = '';
  $page_id = (int)$page_id;
  $page_id = ($page_id <= 0 ? 1 : $page_id);

  if($per_page < $count_all) {
    $html .= '<div class="im-pagination ' . $class . '">';

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
        $url = osc_route_url('im-thread-page', array('page-id' => $i));

        if($old <> -1 && $old <> $i - 1) {
          $html .= '<span>&middot;&middot;&middot;</span>';
        }

        $html .= '<a href="' . $url . '" ' . ($page_actual == $i ? 'class="im-active"' : '') . '>' . $i . '</a>';
        $old = $i;
      }

    } else {

      // List all pages
      for ($i = 1; $i <= $pages; $i++) {
        $url = osc_route_url('im-thread-page', array('page-id' => $i));

        $html .= '<a href="' . $url . '" ' . ($page_actual == $i ? 'class="im-active"' : '') . '>' . $i . '</a>';
      }
    }

    $html .= '</div>';
  }

  return $html;
}

// INCLUDE MAILER SCRIPT
function im_include_mailer() {
  if(file_exists(osc_lib_path() . 'phpmailer/class.phpmailer.php')) {
    require_once osc_lib_path() . 'phpmailer/class.phpmailer.php';
  } else if(file_exists(osc_lib_path() . 'vendor/phpmailer/phpmailer/class.phpmailer.php')) {
    require_once osc_lib_path() . 'vendor/phpmailer/phpmailer/class.phpmailer.php';
  }
}


// MASK EMAIL
function im_mask_email($mail) {
  $mail = explode('@', $mail);
  $a = substr($mail[0], 0, -2) . 'xx';
  $b = explode('.', @$mail[1]);
  $b[0] = 'xxxx';
  $mail_masked = $a . '@' . implode('.', $b);
  
  return $mail_masked;
}


// CHECK IF OSCLASS HAS PROFILE PICTURE
function im_has_profile_img() {
  if(defined('OSCLASS_AUTHOR') && defined('OSCLASS_AUTHOR') == 'OSCLASSPOINT' && osc_version() > 420) {
    return true;
  }
  
  return false;
}


// GET USER PROFILE IMAGE URL
function im_profile_img_url($user_id, $user_name = '', $user = array()) {
  // osc_base_url() . 'oc-content/plugins/instant_messenger/img/default-user-image.png';
  $def_img = im_default_image_url($user_name);
  
  if($user_id <= 0) {;
    return $def_img;
  }
  
  if(im_has_profile_img()) {
    if(!is_array($user) || empty($user) || !isset($user['pk_i_id'])) {
      $user = osc_get_user_row($user_id);
    }
    
    if(isset($user['s_profile_img']) && $user['s_profile_img'] != '') {
      return osc_base_url() . 'oc-content/uploads/user-images/' . $user['s_profile_img'];
    }
    
    return im_default_image_url(im_str(im_arr($user, 's_name', $user_name)));
    
  } else if(function_exists('profile_picture_show')) {
    $picture = ModelUR::newInstance()->getPictureByUserId($user_id);
    $picture['pic_ext'] = isset($picture['pic_ext']) ? $picture['pic_ext'] : '.jpg';  

    if(file_exists(osc_content_path() . 'plugins/profile_picture/images/profile' . $user_id . $picture['pic_ext'])) { 
      return osc_base_url() . 'oc-content/plugins/profile_picture/images/profile' . $user_id . $picture['pic_ext'];
    } 
    
  } else if(function_exists('show_avatar')) {
    $picture = ModelAvatar::newInstance()->getAvatar($user_id); 

    if($picture <> '') {
      if(file_exists(osc_content_path() . 'plugins/avatar_plugin/avatar/' . $picture)) { 
        return osc_base_url() . 'oc-content/plugins/avatar_plugin/avatar/' . $picture;
      } 
    }
  }
  
  return $def_img;
}


// GET USER IMAGE
function im_get_user_image($user_id) {
  return im_profile_img_url($user_id);
}


// GET OFFER
function im_get_offer($offer_id) {
  if(osc_plugin_is_enabled('make_offer/index.php') && $offer_id > 0) {
    $offer = ModelMO::newInstance()->getOfferById($offer_id);
    return $offer;
  }

  return false;
}


// CHECK IF USER IS BLOCKED
function im_check_block($to_user_id, $from_user_email, $silent = false, &$message = '') {
  if($from_user_email == '' || $to_user_id == 0) {
    return 1;
  }
  
  $check_block = ModelIM::newInstance()->checkUserBlocks($to_user_id, $from_user_email);

  if(isset($check_block) && @$check_block['i_user_id'] > 0) {
    $message = __('This user has blocked you and you can no longer send messages.', 'instant_messenger');

    if($silent === false) {
      osc_add_flash_error_message($message);
    }
    
    return 0;
  }

  $banned = osc_is_banned($from_user_email);

  if($banned==1) {
    $message = __('Your email has been banned by admin/system.', 'instant_messenger');

    if($silent === false) {
      osc_add_flash_error_message($message);
    }
    
    return 0;
    
  } else if($banned==2) {
    $message = __('Your current IP is not allowed - banned by admin/system.', 'instant_messenger');

    if($silent === false) {
      osc_add_flash_error_message($message);
    }
    
    return 0;
  }

  return 1;
}


// CHECK IF USER IS BLOCKED - REVERSED
function im_check_block_reversed($from_user_id, $to_user_email, $silent = false, &$message = '') {
  if($to_user_email == '' || $from_user_id == 0) {
    return 1;
  }
  
  $check_block = ModelIM::newInstance()->checkUserBlocks($from_user_id, $to_user_email);

  if(isset($check_block) && @$check_block['i_user_id'] > 0) {
    $message = __('You have blocked this user and can no longer send messages to them.', 'instant_messenger');

    if($silent === false) {
      osc_add_flash_error_message($message);
    }
    
    return 0;
  }

  $banned = osc_is_banned($to_user_email);

  if($banned==1) {
    $message = __('User\'s email has been banned by admin/system.', 'instant_messenger');

    if($silent === false) {
      osc_add_flash_error_message($message);
    }
    
    return 0;
  }

  return 1;
}


// CHECK IF TEXT-MESSAGE CONTAINS SOME KIND OF CONTACT INFO
function im_text_contains_contact_info($text) {
  $text = trim((string)$text);
  
  if(preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $text)) return 1;
  if(preg_match('/\+?\d[\d\s\-\(\)]{5,}\d/', $text)) return 2;
  if(preg_match('/(?:(?:\+?\d[\d\s\-\(\)]{5,}\d)|WhatsApp)/i', $text)) return 3;
  if(preg_match('/@[\w]{5,}/', $text)) return 4;
  if(preg_match('/(live:|skype:)[\w\.\-]+/i', $text)) return 5;
  if(preg_match('/(facebook\.com|fb\.me|instagram\.com|linkedin\.com\/in)\/[A-Za-z0-9._-]+/i', $text)) return 6;
  
  return 0;
}


// GET PICTURE FOR FILE EXTENSION
function im_get_extension_icon($file) {
  if(!is_array($file) || empty($file)) {
    return '';
  }
  
  $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
  $return = '';

  if($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'gif' || $ext == 'bmp' || $ext == 'tiff') {
    $return = 'img.png';
  } else if ($ext == 'zip' || $ext == 'rar' || $ext == 'tar' || $ext == '7z') {
    $return = 'zip.png';
  } else if ($ext == 'txt') {
    $return = 'txt.png';
  } else if ($ext == 'doc' || $ext == 'docx') {
    $return = 'doc.png';
  } else if ($ext == 'xls' || $ext == 'xlsx') {
    $return = 'xls.png';
  } else if ($ext == 'ppt' || $ext == 'pptx') {
    $return = 'ppt.png';
  } else if ($ext == 'pdf') {
    $return = 'pdf.png';
  } else {
    $return = 'def.png';
  }

  $return = '<img class="im-att-icon" src="' . osc_base_url() . 'oc-content/plugins/instant_messenger/img/icon/' . $return . '" />';
  return $return;
}


// GET ITEM DETAILS
function im_get_item_details($item_id, $item = array()) {
  if(empty($item) || !isset($item['pk_i_id'])) {
    $item = osc_get_item_row($item_id); 
  }
  
  if($item === false || !isset($item['pk_i_id'])) {
    return false;
  }
  
  $resource = ItemResource::newInstance()->getResource($item_id);

  if(isset($item['s_country'])) {
    $location = array($item['s_country'], $item['s_region'], $item['s_city']);
    $location = array_filter($location);
    $location = implode(', ', $location);
  } else {
    $location = '';
  }
  
  if(isset($resource['s_name']) && $resource['s_name'] <> '') {
    $resource_url = osc_apply_filter('resource_thumbnail_url', osc_base_url() . $resource['s_path'] . $resource['pk_i_id'] . '_thumbnail.' . $resource['s_extension']);
  } else {
    $resource_url = osc_base_url() . 'oc-content/plugins/instant_messenger/img/no-image.png';
  }

  $currency_full = Currency::newInstance()->findByPrimaryKey($item['fk_c_currency_code']);
  $currency_symbol = isset($currency_full['s_description']) ? $currency_full['s_description'] : '';


  if(!isset($item['i_price']) || $item['i_price'] == '') {
    $price = __('Check with seller', 'instant_messenger');
  } else if($item['i_price'] == 0) {
    $price = __('Free', 'instant_messenger');
  } else {
    $price = round($item['i_price']/1000000, 2); 
  }

  $price = osc_format_price($item['i_price'], $currency_symbol);


  return array('item_id' => $item_id, 'resource' => $resource_url, 'price' => $price, 'location' => $location);
}



// MANAGE MESSAGE INSERT INTO DATABASE
function im_insert_message($thread_id, $message, $type, $file = array(), $notify = true, $redirect = true) {
  if(im_param('only_logged') == 1 && !osc_is_web_user_logged_in()) {
    return false;
  }
  
  if(!is_array($file)) {
    $file = array();
  }
  
  $thread = ModelIM::newInstance()->getThreadById($thread_id);
  if(!im_is_valid_thread($thread)) {
    return false;
  }

  // MANAGE FILE UPLOAD
  $allowed_extensions = (im_param('att_extension') <> '' ? im_param('att_extension') : 'jpg, jpeg, gif, png');
  $allowed_extensions = array_map('strtolower', array_filter(array_map('trim', explode(',', $allowed_extensions))));

  $upload_name = (isset($file['name']) ? im_str($file['name']) : '');
  $extension = strtolower(pathinfo($upload_name, PATHINFO_EXTENSION));
  $max_file_size = (im_param('att_max_size') <> '' ? im_param('att_max_size') : 512) * 1000;  //(in bytes)
  $file_size = @$file['size'];
  $file_name = $thread_id . '_' . date('Ymd') . '_' . mb_generate_rand_int(6) . '.' . $extension;

  $update_file_name = '';
  if(isset($file['name']) && $file['name'] <> '' && im_param('att_enable') == 1) {
    if($file['error'] == UPLOAD_ERR_OK) {
      if(in_array($extension, $allowed_extensions)) {
        if($file_size < $max_file_size) {
          im_check_attachment_dir($thread['i_thread_id']);
          if(move_uploaded_file(@$file['tmp_name'], im_attachment_path($thread['i_thread_id'], $file_name))) {
            $update_file_name = $file_name;
          } else {
            osc_add_flash_error_message(__('An error with file sending has occurred, please try again', 'instant_messenger'));
          }
        } else {
          osc_add_flash_error_message(__('File is too big and was not sent. Maximum file size is:', 'instant_messenger') . ' ' . round($max_file_size/1000) . 'kb');
        }
      } else {
        osc_add_flash_error_message(__('File extension is not allowed, file was not sent. Only files with following extensions are allowed to send in attachment', 'instant_messenger') . ': ' . implode(', ', $allowed_extensions));
      }
    } else {
      osc_add_flash_error_message(__('An error with file sending has occurred, please try again.', 'instant_messenger'));
    }
  }


  // MANAGE EMAIL SENDING WHEN NEW MESSAGE IS ADDED
  if($type == 0) {

    // Message send by FROM user, send notification to TO user
    $notify_enabled = $thread['i_to_user_notify'];
    
    $send_to_user_id = $thread['i_to_user_id'];
    $send_to_user_name = $thread['s_to_user_name'];
    $send_to_user_email = $thread['s_to_user_email'];
    
    $send_from_user_id = $thread['i_from_user_id'];
    $send_from_user_name = $thread['s_from_user_name'];
    $send_from_user_email = $thread['s_from_user_email'];
    
    $secret_mail = $thread['s_to_secret'];
    $secret = $thread['s_from_secret'];


  } else {

    // Message send by TO user, send notification to FROM user
    $notify_enabled = $thread['i_from_user_notify'];
    
    $send_to_user_id = $thread['i_from_user_id'];
    $send_to_user_name = $thread['s_from_user_name'];
    $send_to_user_email = $thread['s_from_user_email'];
    
    $send_from_user_id = $thread['i_to_user_id'];
    $send_from_user_name = $thread['s_to_user_name'];
    $send_from_user_email = $thread['s_to_user_email'];
    
    $secret_mail = $thread['s_from_secret'];
    $secret = $thread['s_to_secret'];

  }


  // CHECK LIMITS
  $limit_check_result = im_check_user_limits($send_from_user_id, $send_from_user_email);
  if($limit_check_result !== true) {
    osc_add_flash_error_message($limit_check_result);
    header('Location: ' . osc_route_url('im-messages', array('thread-id' => $thread['i_thread_id'], 'secret' => $secret)));
    exit;
  }

  // CHECK FOR BLOCK
  if(im_check_block($send_to_user_id, $send_from_user_email) == 0) {
    //osc_add_flash_error_message(__('You cannot message this user. This user has blocked communication with you.', 'instant_messenger'));
    header('Location: ' . osc_route_url('im-messages', array('thread-id' => $thread['i_thread_id'], 'secret' => $secret)));
    exit;
  }


  $email_sent = 0;
  if($notify_enabled == 1 && $notify === true) {
    $skip_email = false;

    if(im_param('notify_once') == 1) {
      $last_unread = ModelIM::newInstance()->getLastMessage($thread['i_thread_id'], $type, 0, 1);
      if(is_array($last_unread) && (int)im_arr($last_unread, 'pk_i_id', 0) > 0) {
        $skip_email = true;
      }
    }

    if(!$skip_email && im_param('email_deferred') != 1) {
      im_email_message_notify($send_to_user_name, $send_to_user_email, $send_from_user_name, (int)im_arr($thread, 'fk_i_item_id', 0), $thread['i_thread_id'], $thread['s_title'], $message, $update_file_name, $secret_mail);
      $email_sent = 1;
    }
  }



  // INSERT MESSAGE INTO DATABASE
  $id = ModelIM::newInstance()->insertMessage($thread['i_thread_id'], $type, 0, $message, $update_file_name, $email_sent);
  osc_run_hook('im_insert_message', $id);

  if($redirect === false) {
    return $id;
  }

  osc_add_flash_ok_message(__('Message successfully sent to', 'instant_messenger') . ' ' . $send_to_user_name);
  header('Location: ' . osc_route_url('im-messages', array('thread-id' => $thread['i_thread_id'], 'secret' => $secret)));
  exit;
}

/**
 * Normalize $_FILES payload (single or multiple) into a list of one-file arrays.
 *
 * @param array $files
 * @return array
 */
function im_uploaded_file_list($files)
{
  if(!is_array($files) || !isset($files['name'])) {
    return array();
  }

  if(!is_array($files['name'])) {
    if(trim((string)$files['name']) === '' || (isset($files['error']) && (int)$files['error'] === UPLOAD_ERR_NO_FILE)) {
      return array();
    }

    return array($files);
  }

  $out = array();
  foreach($files['name'] as $i => $name) {
    if(trim((string)$name) === '' || (isset($files['error'][$i]) && (int)$files['error'][$i] === UPLOAD_ERR_NO_FILE)) {
      continue;
    }

    $out[] = array(
      'name' => $name,
      'type' => isset($files['type'][$i]) ? $files['type'][$i] : '',
      'tmp_name' => isset($files['tmp_name'][$i]) ? $files['tmp_name'][$i] : '',
      'error' => isset($files['error'][$i]) ? $files['error'][$i] : UPLOAD_ERR_OK,
      'size' => isset($files['size'][$i]) ? $files['size'][$i] : 0,
    );
  }

  return $out;
}



function im_get_time_diff($time) {
  $time_diff = round(abs(time() - strtotime($time)) / 60);
  $time_diff_h = floor($time_diff/60);
  $time_diff_d = floor($time_diff/1440);
  $time_diff_w = floor($time_diff/10080);
  $time_diff_m = floor($time_diff/43200);
  $time_diff_y = floor($time_diff/518400);


  if($time_diff < 2) {
    $time_diff_name = __('Minute ago', 'instant_messenger');
  } else if ($time_diff < 60) {
    $time_diff_name = sprintf(__('%d minutes ago', 'instant_messenger'), $time_diff);
  } else if ($time_diff < 120) {
    $time_diff_name = sprintf(__('%d hour ago', 'instant_messenger'), $time_diff_h);
  } else if ($time_diff < 1440) {
    $time_diff_name = sprintf(__('%d hours ago', 'instant_messenger'), $time_diff_h);
  } else if ($time_diff < 2880) {
    $time_diff_name = sprintf(__('%d day ago', 'instant_messenger'), $time_diff_d);
  } else if ($time_diff < 10080) {
    $time_diff_name = sprintf(__('%d days ago', 'instant_messenger'), $time_diff_d);
  } else if ($time_diff < 20160) {
    $time_diff_name = sprintf(__('%d week ago', 'instant_messenger'), $time_diff_w);
  } else if ($time_diff < 43200) {
    $time_diff_name = sprintf(__('%d weeks ago', 'instant_messenger'), $time_diff_w);
  } else if ($time_diff < 86400) {
    $time_diff_name = sprintf(__('%d month ago', 'instant_messenger'), $time_diff_m);
  } else {
    $time_diff_name = sprintf(__('%d months ago', 'instant_messenger'), $time_diff_m);
  }

  return $time_diff_name;
}


// GENERATE AVATAR IMAGE BASED ON USER NAME
function im_avatar_letters($name = '', $size = 64) {
  // Clean and sanitize username
  $name = trim((string)$name);
  $name_sanitized = str_replace('-', '', osc_sanitizeString($name));
  $name_ = explode(' ', str_replace('  ', ' ', $name));
  
  if(count($name_) > 1) {
    $letters = substr($name_[0], 0, 1) . substr(end($name_), 0, 1);
  } else {
    $letters = (strlen($name_sanitized) >= 2) ? substr($name_sanitized, 0, 2) : str_pad($name_sanitized, 2, 'X');
  }
  
  $letters = strtoupper($letters);

  // Generate deterministic color from hash
  // $hash = crc32($letters);
  // $color = sprintf("#%06X", $hash & 0xFFFFFF);

  $palette = [
    '#1abc9c', '#2ecc71', '#3498db', '#9b59b6', '#34495e','#16a085', '#27ae60', '#2980b9', '#8e44ad', '#2c3e50',
    '#f1c40f', '#e67e22', '#e74c3c', '#ecf0f1', '#95a5a6','#f39c12', '#d35400', '#c0392b', '#bdc3c7', '#7f8c8d'
  ];

  $color = $palette[abs(crc32($letters)) % count($palette)];

  // Build SVG
  $fontSize = round($size * 0.5);
  $svg = sprintf(
    '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" class="im-letter-avatar">' .
    '<rect width="100%%" height="100%%" fill="%2$s"/>' .
    '<text x="50%%" y="50%%" font-size="%3$d" fill="#FFF" dy=".35em" text-anchor="middle" font-family="Arial, sans-serif">%4$s</text>' .
    '</svg>',
    $size,
    $color,
    $fontSize,
    htmlspecialchars($letters, ENT_QUOTES, 'UTF-8')
  );

  // Encode SVG as Data URI
  $dataUri = "data:image/svg+xml;base64," . base64_encode($svg);

  return array(
    'letters' => $letters,
    'color'   => $color,
    'svg'     => $svg,
    'dataUri' => $dataUri
  );
}


// GET DEFAULT IMAGE
function im_default_image_url($name = '') {
  if($name != '' && im_param('generate_avatars') == 1) { 
    $data = im_avatar_letters($name);
    return (isset($data['dataUri']) ? $data['dataUri'] : '');
  }
  
  // return osc_base_url() . 'oc-content/plugins/instant_messenger/img/new-profile-default.png';
  return osc_base_url() . 'oc-content/plugins/instant_messenger/img/default-user-image.png';
}



// CHECK IF RUNNING ON DEMO
function im_is_demo() {
  if(osc_logged_admin_username() == 'admin') {
    return false;
  } else if(isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'],'mb-themes') !== false || strpos($_SERVER['HTTP_HOST'],'abprofitrade') !== false)) {
    return true;
  } else {
    return false;
  }
}


function im_param($name) {
  return osc_get_preference($name, 'plugin-instant_messenger');
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
?>