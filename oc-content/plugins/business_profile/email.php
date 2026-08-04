<?php

// CONTACT COMPANY MAIL
function bpr_mail_seller($user_id, $from_user_id, $email, $message, $phone) {
  bpr_include_mailer();

  $mPages = new Page() ;
  $aPage = $mPages->findByInternalName('bpr_mail_seller') ;
  $locale = osc_current_user_locale();
  $content = array();


  if(isset($aPage['locale'][$locale]['s_title'])) {
    $content = $aPage['locale'][$locale];
  } else {
    $content = current($aPage['locale'] <> '' ? $aPage['locale'] : array());
  }

  $user = User::newInstance()->findByPrimaryKey($user_id);
  $from = User::newInstance()->findByPrimaryKey($from_user_id);


  $words = array();
  $words[] = array('{USER_NAME}', '{FROM_NAME}', '{MESSAGE}', '{PHONE}', '{EMAIL}', '{WEB_URL}', '{WEB_TITLE}');
  $words[] = array($user['s_name'], @$from['s_name'], $message, $phone, $email, osc_base_url(), osc_page_title());


  $title = osc_mailBeauty($content['s_title'], $words) ;
  $body  = osc_mailBeauty($content['s_text'], $words) ;

  $emailParams = array(
    'subject' => $title,
    'to' => $user['s_email'],
    'to_name' => $user['s_name'],
    'reply_to' => $email,
    'body' => $body,
    'alt_body' => $body
  );

  osc_sendMail($emailParams);
}

?>