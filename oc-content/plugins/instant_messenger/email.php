<?php
EmailVariables::newInstance()->add('{TO_NAME}', __('Name of user that will receive email notification', 'instant_messenger'));
EmailVariables::newInstance()->add('{FROM_NAME}', __('Name of user that has send message', 'instant_messenger'));
EmailVariables::newInstance()->add('{ITEM_LINK}', __('Link to listing', 'instant_messenger'));
EmailVariables::newInstance()->add('{THREAD_TITLE}', __('Title of conversation', 'instant_messenger'));
// EmailVariables::newInstance()->add('{THREAD_URL}', __('URL to conversation', 'instant_messenger'));
EmailVariables::newInstance()->add('{THREAD_LINK}', __('Link to conversation', 'instant_messenger'));
EmailVariables::newInstance()->add('{MESSAGE}', __('Message in conversation', 'instant_messenger'));



// SEND DEFERRED NOTIFICATION EMAILS (CRON)
function im_send_deferred_notifications() {
  if(im_param('email_deferred') != 1) {
    return false;
  }

  $delay = (int)im_param('email_deferred_minutes');
  $delay = ($delay > 0 ? $delay : 5);
  $date = date('Y-m-d H:i:s', strtotime('-' . $delay . ' minutes'));
  $list = ModelIM::newInstance()->getPendingEmailNotifications($date);

  if(!is_array($list) || empty($list)) {
    return true;
  }

  foreach($list as $l) {
    $thread_id = (int)im_arr($l, 'fk_i_thread_id', 0);
    $type = (int)im_arr($l, 'i_type', 0);
    $dt_datetime = im_str(im_arr($l, 'dt_datetime'));

    if($thread_id <= 0 || $dt_datetime == '') {
      continue;
    }

    $thread = ModelIM::newInstance()->getThreadById($thread_id);
    if(!im_is_valid_thread($thread)) {
      continue;
    }

    if($type == 0 && (int)$thread['i_to_user_notify'] != 1) {
      continue;
    }

    if($type == 1 && (int)$thread['i_from_user_notify'] != 1) {
      continue;
    }

    if(im_param('notify_once') == 1 && ModelIM::newInstance()->hasUnreadNotifiedEmail($thread_id, $type)) {
      continue;
    }

    $message = ModelIM::newInstance()->getNotificationMessage($thread_id, $type, $dt_datetime);
    if(!is_array($message) || !isset($message['pk_i_id'])) {
      continue;
    }

    if(im_email_message_notify_from_row($thread, $message, $type)) {
      ModelIM::newInstance()->updateEmailSent($thread_id, $type, $dt_datetime);
    }
  }

  return true;
}


// BUILD RECIPIENT DATA FROM THREAD ROW
function im_email_notify_users($thread, $type) {
  $type = (int)$type;

  if($type == 0) {
    return array(
      'to_name' => im_str(im_arr($thread, 's_to_user_name')),
      'to_email' => im_str(im_arr($thread, 's_to_user_email')),
      'from_name' => im_str(im_arr($thread, 's_from_user_name')),
      'secret' => im_str(im_arr($thread, 's_to_secret'))
    );
  }

  return array(
    'to_name' => im_str(im_arr($thread, 's_from_user_name')),
    'to_email' => im_str(im_arr($thread, 's_from_user_email')),
    'from_name' => im_str(im_arr($thread, 's_to_user_name')),
    'secret' => im_str(im_arr($thread, 's_from_secret'))
  );
}


// SEND NOTIFICATION FROM MESSAGE ROW
function im_email_message_notify_from_row($thread, $message, $type) {
  if(!is_array($thread) || !is_array($message)) {
    return false;
  }

  if(trim(im_str(im_arr($message, 's_message'))) == '' && trim(im_str(im_arr($message, 's_file'))) == '') {
    return false;
  }

  $users = im_email_notify_users($thread, $type);
  if(trim($users['to_email']) == '') {
    return false;
  }

  return im_email_message_notify(
    $users['to_name'],
    $users['to_email'],
    $users['from_name'],
    (int)im_arr($thread, 'fk_i_item_id', 0),
    (int)$thread['i_thread_id'],
    im_str(im_arr($thread, 's_title')),
    im_str(im_arr($message, 's_message')),
    im_str(im_arr($message, 's_file')),
    $users['secret']
  );
}


// Create email when message is sent (notify user)
function im_email_message_notify($send_to_user_name, $send_to_user_email, $send_from_user_name, $item_id, $thread_id, $thread_title, $message, $file, $secret) {
  im_include_mailer();

  $page = new Page();
  $page = $page->findByInternalName('im_email_message_notify');
  if(empty($page)) { return false; }

  $locale = osc_current_user_locale();
  $content = array();
  if(isset($page['locale'][$locale]['s_title'])) {
    $content = $page['locale'][$locale];
  } else {
    $content = current($page['locale']);
  }

  $thread_id = (int)$thread_id;
  $file = trim(im_str($file));
  $item_id = (int)$item_id;

  $attachment_path = '';
  if($file != '' && $thread_id > 0) {
    $attachment_path = im_attachment_path($thread_id, $file);
    if((!$attachment_path || !file_exists($attachment_path)) && file_exists(im_old_download_path() . $file)) {
      $attachment_path = im_old_download_path() . $file;
    }
    if(!$attachment_path || !file_exists($attachment_path)) {
      $attachment_path = '';
    }
    $attachment_path = osc_apply_filter('im_email_attachment_path', $attachment_path, $thread_id, $file);
  }

  $item = ($item_id > 0 ? osc_get_item_row($item_id) : false);
  $item_title = '';
  $item_url = '';

  if($item !== false && is_array($item) && isset($item['s_title']) && trim((string)$item['s_title']) != '') {
    $item_title = stripslashes(strip_tags(osc_highlight($item['s_title'], 35)));
    $item_url = '<a href="' . osc_item_url_from_item($item) . '">' . $item_title . '</a>';
  } else if($item_id > 0) {
    $item_title = __('Listing removed', 'instant_messenger');
    $item_url = $item_title;
  } else {
    $item_title = __('Direct message', 'instant_messenger');
    $item_url = $item_title;
  }

  $thread_url = osc_route_url('im-messages', array('thread-id' => $thread_id, 'secret' => $secret));

  $words = array();
  $words[] = array('{TO_NAME}', '{FROM_NAME}', '{ITEM_TITLE}', '{ITEM_LINK}', '{THREAD_TITLE}', '{THREAD_LINK}', '{MESSAGE}', '{WEB_TITLE}');
  $words[] = array($send_to_user_name, $send_from_user_name, $item_title, $item_url, stripslashes(strip_tags($thread_title)), $thread_url, $message, stripslashes(strip_tags(osc_page_title())));

  $title = osc_mailBeauty($content['s_title'], $words);
  $body = osc_mailBeauty($content['s_text'], $words);

  $email_build = array(
    'subject' => $title,
    'to' => $send_to_user_email,
    'to_name' => $send_to_user_name,
    'body' => $body,
    'alt_body' => $body
  );

  if($attachment_path <> '') {
    $email_build['attachment'] = $attachment_path;
  }

  osc_sendMail($email_build);
  return true;
}
