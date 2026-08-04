<?php
/*
  Plugin Name: Instant Messenger Plugin
  Plugin URI: https://osclasspoint.com/osclass-plugins/messaging-and-communication/instant-messenger-plugin_i50
  Description: Add option to sellers and buyers communicate using instant messages instead of email.
  Version: 2.7.2
  Author: MB Themes
  Author URI: https://osclasspoint.com
  Author Email: info@osclasspoint.com
  Short Name: instant_messenger
  Plugin update URI: instant-messenger-plugin
  Support URI: https://forums.osclasspoint.com/instant-messenger-plugin/
  Product Key: CNMxiwkWshE8H3F1JyMo
*/

define('IM_VERSION_ID', 103);

define('IM_CHAT_ICON', '<svg xmlns="http://www.w3.org/2000/svg" class="im-icon im-icon-chat" fill="currentColor" width="20" height="20" viewBox="0 0 512 512"><path d="M448 0H64C28.7 0 0 28.7 0 64v288c0 35.3 28.7 64 64 64h96v84c0 7.1 5.8 12 12 12 2.4 0 4.9-.7 7.1-2.4L304 416h144c35.3 0 64-28.7 64-64V64c0-35.3-28.7-64-64-64zm16 352c0 8.8-7.2 16-16 16H288l-12.8 9.6L208 428v-60H64c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16h384c8.8 0 16 7.2 16 16v288zm-96-216H144c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h224c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16zm-96 96H144c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h128c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16z"/></svg>');

require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'model/ModelIM.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'email.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'functions.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'admin/pagination.php';


osc_enqueue_style('font-awesome47', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');
osc_enqueue_style('im-user-style', osc_base_url() . 'oc-content/plugins/instant_messenger/css/user.css?v=' . date('Ymdhis'));
osc_enqueue_style('tipped', osc_base_url() . 'oc-content/plugins/instant_messenger/css/tipped.css');

osc_register_script('im-user', osc_base_url() . 'oc-content/plugins/instant_messenger/js/user.js?v=' . date('YmdHis'), 'jquery');
osc_register_script('tipped', osc_base_url() . 'oc-content/plugins/instant_messenger/js/tipped.js', 'jquery');

osc_enqueue_script('jquery-validate');
osc_enqueue_script('tipped');
osc_enqueue_script('im-user');


// INSTALL FUNCTION - DEFINE VARIABLES
function im_call_after_install() {
  ModelIM::newInstance()->install();

  // General settings
  osc_set_preference('limit_enabled', 1, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('limit_max_messages', 12, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('limit_max_users', 4, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('limit_period_hours', 12, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('limit_disable_after_messages', 100, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('limit_disable_after_users', 20, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('limit_disable_after_days_from_reg', 60, 'plugin-instant_messenger', 'INTEGER');

  osc_set_preference('autogenerate_title', 0, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('hook_header_links', 0, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('contact_seller', 1, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('notify_once', 1, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('email_deferred', 0, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('email_deferred_minutes', 5, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('att_enable', 1, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('att_max_size', 512, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('att_extension', 'jpg, jpeg, png, gif, doc, docx, pdf, txt', 'plugin-instant_messenger', 'STRING');
  osc_set_preference('threads_per_page', 20, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('thread_days', 360, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('att_days', 360, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('link_reg_only', 0, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('only_logged', 0, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('ajax', 1, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('interval', 3000, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('remove_thread', 0, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('generate_avatars', 1, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('button_hooks', 'item_contact', 'plugin-instant_messenger', 'STRING');
  osc_set_preference('button_hooks_user', 'user_public_profile_sidebar_bottom', 'plugin-instant_messenger', 'STRING');
  osc_set_preference('one_thread_per_user', 0, 'plugin-instant_messenger', 'INTEGER');
  osc_set_preference('version', IM_VERSION_ID, 'plugin-instant_messenger', 'INTEGER');


  // UPLOAD EMAIL TEMPLATES
  $data = array();
  $locales = OSCLocale::newInstance()->listAllEnabled();
  foreach($locales as $l) {
    $email_text  = '<p>Hi {TO_NAME},</p>';
    $email_text .= '<p>{FROM_NAME} has sent you new message on your product {ITEM_LINK} and is listed below:<hr></p>';
    $email_text .= '<p><strong>{THREAD_TITLE}</strong></p>';
    $email_text .= '<p>{MESSAGE}</p>';
    $email_text .= '<p><hr></p>';
    $email_text .= '<p><br/></p>';
    $email_text .= '<p>You can directly answer <a target="_blank" href="{THREAD_LINK}">clicking here</a>.';
    $email_text .= '<p>Remember, older conversations can always be viewed under "Messages" in your user account.</p>';
    $email_text .= '<p></p>';
    $email_text .= '<p>Thank you, <br />{WEB_TITLE}</p>';

    $data[$l['pk_c_code']] = array();
    $data[$l['pk_c_code']]['s_title'] = '[{WEB_TITLE}] New message: {ITEM_TITLE}';
    $data[$l['pk_c_code']]['s_text'] = $email_text;
  }

  Page::newInstance()->insert(array('s_internal_name' => 'im_email_message_notify', 'b_indelible' => '1'), $data);

}


// AUTOMATIC PLUGIN UPDATE
function im_install_plugin_update() {
  if(!in_array(Params::getParam('action'), array('widget','add_post','add','enable','disable','install','uninstall')) && !in_array(Params::getParam('page'), array('ajax','login','market','upgrade','appearance'))) {
    $installed_version = (int)im_param('version');
    $current_version = (int)IM_VERSION_ID;

    if($current_version > $installed_version) {
      $ignore_error = (Params::getParam('forceupdateplugin') == 'instant_messenger' ? true : false);
      im_update_version($ignore_error);
    }
  }
}

osc_add_hook('init_admin', 'im_install_plugin_update', 10);

osc_add_hook('cron_minutely', 'im_send_deferred_notifications');


// PLUGIN UPDATE
function im_update_version($ignore_error = false) {
  $result = ModelIM::newInstance()->versionUpdate($ignore_error);

  if($result !== false || $ignore_error !== false) {
    osc_set_preference('version', IM_VERSION_ID, 'plugin-instant_messenger', 'INTEGER');
    osc_reset_preferences();
  }

  if($ignore_error === true) {
    osc_add_flash_ok_message(sprintf(__('Force update of "%s" completed.', 'instant_messenger'), __('Instant Messenger Plugin', 'instant_messenger')), 'admin');
    header('Location:' . osc_admin_base_url(true) . '?page=plugins');
    exit;
  }
}

osc_add_hook(osc_plugin_path(__FILE__) . '_enable', 'im_update_version');



function im_call_after_uninstall() {
  ModelIM::newInstance()->uninstall();


  // get list of primary keys of static pages (emails) that should be deleted on uninstall
  $pages = ModelIM::newInstance()->getPages();  
  foreach($pages as $page) {
    Page::newInstance()->deleteByPrimaryKey($page['pk_i_id']);
  }
}


// ADD JS STRINGS TO HEAD
function im_js() {
  $html = '<script type="text/javascript">';
  $html .= 'var imRqName="' . osc_esc_js(__('Your Name: This field is required, enter your name please.', 'instant_messenger')) . '";';
  $html .= 'var imDsName="' . osc_esc_js(__('Your Name: Name is too short, enter at least 3 characters.', 'instant_messenger')) . '";';
  $html .= 'var imRqEmail="' . osc_esc_js(__('Your Email: This field is required, enter your email please.', 'instant_messenger')) . '";';
  $html .= 'var imDsEmail="' . osc_esc_js(__('Your Email: Email your have entered is not in valid format.', 'instant_messenger')) . '";';
  // $html .= 'var imRqTitle="' . osc_esc_js(__('Title: Please enter title of this converstation.', 'instant_messenger')) . '";';
  // $html .= 'var imDsTitle="' . osc_esc_js(__('Title: Title is too short, enter at least 2 characters.', 'instant_messenger')) . '";';
  $html .= 'var imRqMessage="' . osc_esc_js(__('Message: This field is required, please enter your message.', 'instant_messenger')) . '";';
  $html .= 'var imDsMessage="' . osc_esc_js(__('Message: Enter at least 2 characters.', 'instant_messenger')) . '";';
  $html .= '</script>';
  echo $html;
}

osc_add_hook('header', 'im_js', 2);


// CREATE LINK WITH MESSAGES
function im_messages() {
  $html = '';

  $user_id = osc_logged_user_id();

  if(!osc_is_web_user_logged_in()) {
    $user_id = rand(1000000,9999999);
  }

  if(osc_is_web_user_logged_in() || im_param('link_reg_only') <> 1) {
    $threads = ModelIM::newInstance()->getThreadsByUserId($user_id, 6);
    $count = ModelIM::newInstance()->countMessagesByUserId($user_id);
    $count = $count['i_count'];

    $html .= '<div id="im-link" class="right">';
    $html .= '<a href="' . osc_route_url('im-threads') . '">' . __('Messages', 'instant_messenger') . '<span class="im-t-unread">' . $count . ' ' . __('unread', 'instant_messenger') . '</span></a>';

    $html .= '<div id="im-thread-wrap">';
    $html .= '<div class="im-thread-list im-body">';

    foreach($threads as $t) {
      $message = ModelIM::newInstance()->getLastMessageByThreadId($t['i_thread_id']);

      $m_unread = false;
      if(osc_logged_user_id() == $t['i_from_user_id'] && $message['i_type'] == 0 || osc_logged_user_id() == $t['i_to_user_id'] && $message['i_type'] == 1) {
        $logged_is_owner = true;
      } else {
        $logged_is_owner = false;

        if($message['i_read'] == 0) {
          $m_unread = true;
        }
      }

      $item_details = ((int)$t['fk_i_item_id'] > 0 ? im_get_item_details($t['fk_i_item_id']) : false);
      if($item_details === false) {
        $item_details = array('resource' => osc_base_url() . 'oc-content/plugins/instant_messenger/img/no-image.png');
      }


      $html .= '<a class="im-entry im-row ' . ($m_unread ? 'im-unread' : '') . '" href="' . osc_route_url('im-messages', array('thread-id' => $t['i_thread_id'], 'secret' => 'n')) . '" title="' . __('Click to open conversation', 'instant_messenger') . '">';
        $html .= '<div class="im-col-6 im-img"><img src="' . $item_details['resource'] . '" /></div>';
        $html .= '<div class="im-col-18">';
          $html .= '<div class="im-row im-name">' . ($t['i_from_user_id'] == osc_logged_user_id() ? __('to', 'instant_messenger') . ' ' . $t['s_to_user_name'] : __('from', 'instant_messenger') . ' ' . $t['s_from_user_name']) . '</div>';
          $html .= '<div class="im-row im-text">' . (!$logged_is_owner ? '<i class="fa fa-mail-reply"></i>' : '') . ' ' . osc_highlight($message['s_message'], 36) . '</div>';
          $html .= '<div class="im-row im-time">' . im_get_time_diff($message['d_datetime']) . '</div>';
        $html .= '</div>';
      $html .= '</a>';
    }

    if(count($threads) > 0) {
      $html .= '<div class="im-row im-show-all"><a href="' . osc_route_url('im-threads') . '">' . __('Show all messages', 'instant_messenger') . '</a></div>';
    } else {
      if(osc_is_web_user_logged_in()) {
        $html .= '<div class="im-row im-show-all"><a href="' . osc_route_url('im-threads') . '">' . __('You have no messages yet', 'instant_messenger') . '</a></div>';
      } else {
        $html .= '<div class="im-row im-show-all"><a href="' . osc_route_url('im-threads') . '">' . __('Login to show messages', 'instant_messenger') . '</a></div>';
      }
    }

    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

  } else {
    return false;
  }

  return $html;

}



// COUNT UNREAD MESSAGES
function im_count_unread($user_id) {
  $count = ModelIM::newInstance()->countMessagesByUserId($user_id);
  $count = $count['i_count'];
  return $count;
}


// MANAGE REPLACE CONTACT SELLER BUTTON TO SEND MESSAGE INSTEAD OF MAIL
function im_manage_contact_seller($aItem) {
  if(im_param('contact_seller') == 1) {
    $item_id = $aItem['id'];
    $item = osc_get_item_row($item_id);
    if($item === false || !isset($item['pk_i_id'])) {
      osc_add_flash_error_message(__('Invalid listing ID, thread could not be created.', 'instant_messenger'));
      header('Location: ' . osc_base_url());
      exit;
    }


    // $title = __('Listing', 'instant_messenger') . ' #' . $item_id . ' ' . __('inquiry', 'instant_messenger');
    $title = sprintf(__('Question on: %s', 'instant_messenger'), osc_highlight($item['s_title'], 120)); 

    if((int)osc_logged_user_id() > 0) {
      $from_user_id = osc_logged_user_id();
    } else {
      $from_user_id = '';
    }

    $from_user_name = $aItem['yourName'];
    $from_user_email = $aItem['yourEmail'];

    $to_user_id = $item['fk_i_user_id'];
    $to_user_name = $item['s_contact_name'];
    $to_user_email = $item['s_contact_email'];


    // CHECK LIMITS
    $limit_check_result = im_check_user_limits($from_user_id, $from_user_email);
    if($limit_check_result !== true) {
      osc_add_flash_error_message($limit_check_result);
      header('Location: ' . osc_item_url_from_item($item));
      exit;
    }

    // CHECK FOR BLOCK
    if(im_check_block($to_user_id, $from_user_email) == 0) {
      header('Location: ' . osc_item_url_from_item($item));
      exit;
    }

    $existing = im_find_existing_thread($from_user_id, $from_user_email, $to_user_id, $to_user_email, $item_id);
    if($existing !== false && isset($existing['i_thread_id'])) {
      im_redirect_to_thread($existing, $existing['s_from_secret']);
    }

    $store_item_id = (im_param('one_thread_per_user') == 1 && (int)$to_user_id > 0 ? null : $item_id);
    $thread_id = ModelIM::newInstance()->createThread($store_item_id, $from_user_id, $from_user_name, $from_user_email, $to_user_id, $to_user_name, $to_user_email, $title, 0);
    $thread = ModelIM::newInstance()->getThreadById($thread_id); 

    $message = nl2br(htmlspecialchars(im_str(im_arr($aItem, 'message')), ENT_QUOTES, 'UTF-8'));

    if($aItem['phoneNumber'] <> '') {
      $message .= '<br/><br/>' . __('Contact phone', 'instant_messenger') . ': ' . $aItem['phoneNumber'];
    }

    $attachment = Params::getFiles('attachment');

    im_insert_message($thread['i_thread_id'], $message, 0, $attachment);

    header('Location: ' . osc_route_url('im-messages', array('thread-id' => $thread['i_thread_id'], 'secret' => $thread['s_from_secret'])));
    exit;
  }
}

osc_add_hook('hook_email_item_inquiry', 'im_manage_contact_seller');

//if(osc_logged_user_id() > 0 && im_param('contact_seller') == 1) {
if(im_param('contact_seller') == 1) {
  osc_remove_hook('hook_email_item_inquiry', 'fn_email_item_inquiry');
}

// Osclass 8.3 stop mails filter
function im_stop_item_inquiry_emails($params, $type = '') {
  if(im_param('contact_seller') == 1) {
    if($type == 'item_inquiry') {
      return array('stop' => true);
    }
  }
  
  return false;
}

osc_add_filter('pre_send_mail_filter', 'im_stop_item_inquiry_emails');



// DELETE THREADS RELATED TO ITEM, MESSAGES AND ATTACHMENTS
function im_delete_threads_item($item_id) {
  $threads = ModelIM::newInstance()->getThreadsByItemIdOnly($item_id);

  foreach($threads as $t) {
    im_delete_thread_attachments($t['i_thread_id']);
    ModelIM::newInstance()->removeMessagesByThreadId($t['i_thread_id']);
  }

  ModelIM::newInstance()->removeThreadsByItemId($item_id);
}

osc_add_hook('delete_item', 'im_delete_threads_item');



// DELETE USER THREADS, MESSAGES RELATED TO IT AND ATTACHMENTS
function im_delete_threads_user($user_id) {
  $threads = ModelIM::newInstance()->getThreadsByUserIdOnly($user_id);

  foreach($threads as $t) {
    im_delete_thread_attachments($t['i_thread_id']);
    ModelIM::newInstance()->removeMessagesByThreadId($t['i_thread_id']);
  }

  ModelIM::newInstance()->removeThreadsByUserId($user_id);
}

osc_add_hook('delete_user', 'im_delete_threads_user');



// UPDATE THREADS WHEN EMAIL OF USER IS CHANGED
function im_update_email($new_email, $valid_url) {
  $parts = parse_url(im_str($valid_url));
  $query = array();
  if(is_array($parts) && isset($parts['query'])) {
    parse_str(im_str($parts['query']), $query);
  }
  $user_id = (int)(isset($query['userId']) ? $query['userId'] : 0);

  ModelIM::newInstance()->updateThreadEmail($user_id, $new_email);
}

osc_add_hook('hook_email_new_email', 'im_update_email');



// UPDATE THREADS WHEN USER IS REGISTERED
function im_user_register($user_id) {
  $user = osc_get_user_row($user_id);
  
  if($user !== false && isset($user['pk_i_id'])) {
    $user_email = $user['s_email'];
    $user_name = $user['s_name'];

    ModelIM::newInstance()->updateThreadUserId($user_id, $user_email, $user_name);
  }
}

osc_add_hook('user_register_completed', 'im_user_register');


// Add expired listings into user menu
function im_user_menu_link(){
  if(osc_current_web_theme() == 'veronika' || osc_current_web_theme() == 'stela' || osc_current_web_theme() == 'starter' || (defined('USER_MENU_ICONS') && USER_MENU_ICONS == 1)) {
    echo '<li class="opt_instant_messenger"><a href="' . osc_route_url('im-threads') .'" ><i class="fa fa-envelope-o"></i> '.__('Messages', 'instant_messenger').'<span class="im-user-account-count im-count-' . im_count_unread(osc_logged_user_id()) . '">' . im_count_unread(osc_logged_user_id()) . '</a></li>';
  } else {
    echo '<li class="opt_instant_messenger"><a href="' . osc_route_url('im-threads') .'" >'.__('Messages', 'instant_messenger').'<span class="im-user-account-count im-count-' . im_count_unread(osc_logged_user_id()) . '">' . im_count_unread(osc_logged_user_id()) . '</span></a></li>';
  }
}

osc_add_hook('user_menu', 'im_user_menu_link');


$menu = osc_is_web_user_logged_in();

osc_add_route('im-threads', 'im-threads', 'im-threads', osc_plugin_folder(__FILE__).'user/threads.php', $menu, 'im', 'thread', __('Threads', 'instant_messenger'));
osc_add_route('im-thread-page', 'im-thread-page/([0-9]+)', 'im-thread-page/{page-id}', osc_plugin_folder(__FILE__).'user/threads.php', $menu, 'im', 'thread', __('Threads', 'instant_messenger'));
osc_add_route('im-thread-flag', 'im-thread-flag/([0-9]+)', 'im-thread-flag/{thread-flag-id}', osc_plugin_folder(__FILE__).'user/threads.php', $menu, 'im', 'thread');
osc_add_route('im-thread-notify', 'im-thread-notify/([0-9]+)', 'im-thread-notify/{thread-notify-id}', osc_plugin_folder(__FILE__).'user/threads.php', $menu, 'im', 'thread');
osc_add_route('im-thread-remove', 'im-thread-remove/([0-9]+)/(.+)', 'im-thread-remove/{thread-remove-id}/{secret}', osc_plugin_folder(__FILE__).'user/threads.php', $menu, 'im', 'thread');

osc_add_route('im-messages', 'im-messages/([0-9]+)/(.+)', 'im-messages/{thread-id}/{secret}', osc_plugin_folder(__FILE__).'user/messages.php', $menu, 'im', 'message', __('Messages', 'instant_messenger'));
osc_add_route('im-delete-message', 'im-delete-message/([0-9]+)/([0-9]+)/(.+)', 'im-delete-message/{thread-id}/{del-message-id}/{secret}', osc_plugin_folder(__FILE__).'user/messages.php', $menu, 'im', 'message');
osc_add_route('im-delete-attachment', 'im-delete-attachment/([0-9]+)/([0-9]+)/(.+)/(.+)', 'im-delete-attachment/{thread-id}/{del-att-message-id}/{del-file-name}/{secret}', osc_plugin_folder(__FILE__).'user/messages.php', $menu, 'im', 'message');
osc_add_route('im-refresh-messages', 'im-refresh-messages/([0-9]+)/(.+)/(.+)', 'im-refresh-messages/{thread-id}/{secret}/{imaction}', osc_plugin_folder(__FILE__).'user/messages.php', $menu, 'im', 'refresh-message', __('Messages', 'instant_messenger'));
osc_add_route('im-create-thread', 'im-create-thread/([0-9]+)', 'im-create-thread/{item-id}', osc_plugin_folder(__FILE__).'user/create_thread.php', $menu, 'im', 'create-thread', __('Create thread', 'instant_messenger'));
osc_add_route('im-create-thread-user', 'im-create-thread-user/([0-9]+)', 'im-create-thread-user/{user-id}', osc_plugin_folder(__FILE__).'user/create_thread.php', $menu, 'im', 'create-thread', __('Create thread', 'instant_messenger'));
osc_add_route('im-ban', 'im-ban/(.+)/(.+)', 'im-ban/{action}/{block-email}', osc_plugin_folder(__FILE__).'user/threads.php', $menu, 'im', 'thread');
osc_add_route('im-remove-ban', 'im-remove-ban/(.+)', 'im-remove-ban/{remove-id}', osc_plugin_folder(__FILE__).'user/threads.php', $menu, 'im', 'thread');





// ADMIN MENU
function im_menu($title = NULL) {
  im_migrate_attachments();

  echo '<link href="' . osc_base_url() . 'oc-content/plugins/instant_messenger/css/admin.css" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/instant_messenger/css/bootstrap-switch.css" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/instant_messenger/css/tipped.css" rel="stylesheet" type="text/css" />';
  echo '<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/instant_messenger/js/admin.js"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/instant_messenger/js/tipped.js"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/instant_messenger/js/bootstrap-switch.js"></script>';



  if($title == '') { $title = __('Configure', 'instant_messenger'); }

  $text  = '<div class="mb-head">';
  $text .= '<div class="mb-head-left">';
  $text .= '<h1>' . $title . '</h1>';
  $text .= '<h2>Instant Messenger Plugin</h2>';
  $text .= '</div>';
  $text .= '<div class="mb-head-right">';
  $text .= '<ul class="mb-menu">';
  $text .= '<li><a href="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=instant_messenger/admin/configure.php"><i class="fa fa-wrench"></i><span>' . __('Configure', 'instant_messenger') . '</span></a></li>';
  $text .= '<li><a href="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=instant_messenger/admin/threads.php"><i class="fa fa-comments"></i><span>' . __('Converstations', 'instant_messenger') . '</span></a></li>';
  $text .= '<li><a href="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=instant_messenger/admin/maintenance.php"><i class="fa fa-gears"></i><span>' . __('Maintenance', 'instant_messenger') . '</span></a></li>';
  $text .= '<li><a href="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=instant_messenger/admin/manager.php"><i class="fa fa-file-text-o"></i><span>' . __('Manager', 'instant_messenger') . '</span></a></li>';
  $text .= '</ul>';
  $text .= '</div>';
  $text .= '</div>';

  echo $text;
}



// ADMIN FOOTER
function im_footer() {
  $pluginInfo = osc_plugin_get_info('instant_messenger/index.php');
  $text  = '<div class="mb-footer">';
  $text .= '<a target="_blank" class="mb-developer" href="https://osclasspoint.com"><img src="https://osclasspoint.com/favicon.ico" alt="MB Themes" /> osclasspoint.com</a>';
  $text .= '<a target="_blank" href="' . $pluginInfo['support_uri'] . '"><i class="fa fa-bug"></i> ' . __('Report Bug', 'instant_messenger') . '</a>';
  $text .= '<a target="_blank" href="https://forums.osclasspoint.com/"><i class="fa fa-comments"></i> ' . __('Support Forums', 'instant_messenger') . '</a>';
  $text .= '<a target="_blank" class="mb-last" href="mailto:info@osclasspoint.com"><i class="fa fa-envelope"></i> ' . __('Contact Us', 'instant_messenger') . '</a>';
  $text .= '<span class="mb-version">v' . $pluginInfo['version'] . '</span>';
  $text .= '</div>';

  return $text;
}



// ADD MENU LINK TO PLUGIN LIST
function im_admin_menu() {
echo '<h3><a href="#">Instant Messenger Plugin</a></h3>
<ul> 
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/configure.php') . '">&raquo; ' . __('Configure', 'instant_messenger') . '</a></li>
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/threads.php') . '">&raquo; ' . __('Conversations', 'instant_messenger') . '</a></li>
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/maintenance.php') . '">&raquo; ' . __('Maintenance', 'instant_messenger') . '</a></li>
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/manager.php') . '">&raquo; ' . __('Manager', 'instant_messenger') . '</a></li>
</ul>';
}


// ADD MENU TO PLUGINS MENU LIST
osc_add_hook('admin_menu','im_admin_menu', 1);



// DISPLAY CONFIGURE LINK IN LIST OF PLUGINS
function im_conf() {
  osc_admin_render_plugin(osc_plugin_path(dirname(__FILE__)) . '/admin/configure.php');
}

osc_add_hook(osc_plugin_path(__FILE__) . '_configure', 'im_conf');	


// CALL WHEN PLUGIN IS ACTIVATED - INSTALLED
osc_register_plugin(osc_plugin_path(__FILE__), 'im_call_after_install');

// SHOW UNINSTALL LINK
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'im_call_after_uninstall');
