<link href="<?php echo osc_base_url(); ?>oc-content/plugins/instant_messenger/css/tipped.css" rel="stylesheet" type="text/css" />
<script src="<?php echo osc_base_url(); ?>oc-content/plugins/instant_messenger/js/tipped.js"></script>
<script src="<?php echo osc_base_url(); ?>oc-content/plugins/instant_messenger/js/user.js?v=<?php echo date('Ymdhis'); ?>"></script>

<?php 
$secret = Params::getParam('secret');
$att_enable = im_param('att_enable');
$message_delete = im_param('message_delete');

$ajax = (im_param('ajax') <> '' ? im_param('ajax') : 1);
$interval = (im_param('interval') <> '' ? im_param('interval') : 3000);
$is_chat_refresh = (Params::getParam('imaction') == 'refresh' ? true : false); 

$thread_id = (int)Params::getParam('thread-id');
$thread = ModelIM::newInstance()->getThreadById($thread_id);
if(!im_is_valid_thread($thread)) {
  osc_add_flash_error_message(__('This thread is not available to you. You are not eligible to view or access its content.', 'instant_messenger'));
  header('Location: ' . (osc_is_web_user_logged_in() ? osc_route_url('im-threads') : osc_base_url()));
  exit;
}

$result = im_thread_context($thread, $secret);
$type = $result['send_type'];
$target_is_null = $result['target_removed'];
$secret = $result['secret'];

$thread_target_id = $result['target']['pk_i_id'];
$thread_target_name = $result['target']['s_name'];
$thread_target_email = $result['target']['s_email'];

$logged_user_id = $result['viewer']['pk_i_id'];
$logged_user_name = $result['viewer']['s_name'];
$logged_user_email = $result['viewer']['s_email'];
$logged_user_img = im_profile_img_url($logged_user_id, $logged_user_name);

$target_details = im_get_user_details($thread_target_id, $thread_target_name, $thread_target_email);

$item = array();
$item_details = false;
if(isset($thread['fk_i_item_id']) && (int)$thread['fk_i_item_id'] > 0) {
  $item = osc_get_item_row($thread['fk_i_item_id']);
  if($item === false || !isset($item['pk_i_id'])) {
    $item = array();
  } else {
    $item_details = im_get_item_details($thread['fk_i_item_id'], $item);
  }
}

$target_user = osc_get_user_row($thread_target_id);
$last_seen = '';
if(is_array($target_user) && @$target_user['dt_access_date'] <> '') {
  $last_seen = im_get_time_diff($target_user['dt_access_date']);
}

$blocked_you_msg = $blocked_by_you_msg = '';
$blocked_you = im_check_block($thread_target_id, $logged_user_email, true, $blocked_you_msg);
$blocked_by_you = im_check_block_reversed($logged_user_id, $thread_target_email, true, $blocked_by_you_msg);


// MARK AS VIEWED FOR THIS USER
$is_read = ModelIM::newInstance()->getThreadIsRead($thread['i_thread_id'], osc_logged_user_id(), $secret);

if(is_array($is_read) && isset($is_read['pk_i_id']) && $is_read['pk_i_id'] <> '' && $is_read['pk_i_id'] > 0 && isset($is_read['i_read']) && $is_read['i_read'] == 0) {
  ModelIM::newInstance()->updateMessagesRead($thread['i_thread_id'], ($type*(-1) + 1));
}


$offer = im_get_offer($thread['i_offer_id']);

if($offer) {
  if($offer['fk_i_item_id'] == $thread['fk_i_item_id']) {
    $offer_item = $item;
  } else {
    $offer_item = osc_get_item_row($offer['fk_i_item_id']);
    if($offer_item === false || !is_array($offer_item)) {
      $offer_item = array();
    }
  }

  $currency_code = (is_array($offer_item) && isset($offer_item['fk_c_currency_code']) ? $offer_item['fk_c_currency_code'] : '');
  $currency = Currency::newInstance()->findByPrimaryKey($currency_code);
  $currency_desc = (is_array($currency) && isset($currency['s_description']) ? $currency['s_description'] : '');
  $offer_item_title = (isset($offer_item['s_title']) ? $offer_item['s_title'] : '');

  $t_title = sprintf(__('New offer on %s - %s', 'instant_messenger'), osc_highlight($offer_item_title, 50), $offer['i_price']/1000000 . $currency_desc);
} else if($thread['s_title'] <> '') {
  $t_title = osc_highlight($thread['s_title'], 60);
} else {
  $t_title = __('No subject', 'instant_messenger');
}



// MESSAGE SENT TO USER
if(Params::getParam('im-action') == 'send_message') {
  im_insert_message($thread['i_thread_id'], nl2br(htmlspecialchars(im_str(Params::getParam('im-message', false, false)), ENT_QUOTES, 'UTF-8')), $type, Params::getFiles('im-file'));
}


// DELETE MESSAGE
$del_message_id = (int)Params::getParam('del-message-id');
if($del_message_id > 0 && $message_delete == 1) {
  $del_message = ModelIM::newInstance()->getMessageById($del_message_id);

  if($del_message['fk_i_thread_id'] == $thread['i_thread_id'] && $del_message['i_type'] == $type) {
    ModelIM::newInstance()->deleteMessageById($del_message_id);
    osc_add_flash_ok_message(__('Message removed', 'instant_messenger'));

    header('Location: ' . osc_route_url('im-messages', array('thread-id' => $del_message['fk_i_thread_id'], 'secret' => $secret)));
    exit;
    
  } else {
    osc_add_flash_error_message(__('This is not your message, you cannot remove it!', 'instant_messenger'));
  }
}


// DELETE ATTACHMENT
$del_att_message_id = (int)Params::getParam('del-att-message-id');
if($del_att_message_id > 0 && Params::getParam('del-file-name') <> '') {
  $del_message = ModelIM::newInstance()->getMessageById($del_att_message_id);

  if($del_message['fk_i_thread_id'] == $thread['i_thread_id'] && $del_message['i_type'] == $type) {
    im_delete_attachment_file($thread_id, Params::getParam('del-file-name'));
    ModelIM::newInstance()->deleteMessageAttachment($del_att_message_id);
    osc_add_flash_ok_message(__('Attachment removed', 'instant_messenger'));

    header('Location: ' . osc_route_url('im-messages', array('thread-id' => $del_message['fk_i_thread_id'], 'secret' => $secret)));
    exit;
    
  } else {
    osc_add_flash_error_message(__('This is not your message, you cannot remove attachment on it!', 'instant_messenger'));
  }
}


$from_public_url = '';
if($thread['i_from_user_id'] > 0) {
  $from_public_url = osc_user_public_profile_url($thread['i_from_user_id']);
}

$to_public_url = '';
if($thread['i_to_user_id'] > 0) {
  $to_public_url = osc_user_public_profile_url($thread['i_to_user_id']);
}

if(!$result['can_view']) {
  osc_add_flash_error_message(__('This thread is not available to you. You are not eligible to view or access its content.', 'instant_messenger'));
  header('Location: ' . (osc_is_web_user_logged_in() ? osc_route_url('im-threads') : osc_base_url()));
  exit;
}

$messages = ModelIM::newInstance()->getMessagesByThreadId($thread['i_thread_id']); 
?>

<div class="im-html im-file-messages im-theme-<?php echo osc_current_web_theme(); ?>">
  <h2 class="im-head"><?php echo $t_title; ?></h2>

  <div class="im-alt-head" style="display:none;">
    <div class="im-head2">
      <span><?php echo $thread_target_name; ?> - <?php echo $t_title; ?></span>
      
      <?php if($target_is_null) { ?>
        <em><?php echo sprintf(__('%s has removed this thread, you cannot reply back.', 'instant_messenger'), $thread_target_name); ?></em>
      <?php } ?>
        
      <?php if(im_param('remove_thread') == 1) { ?>
        <a href="<?php echo osc_route_url('im-thread-remove', array('thread-remove-id' => $thread_id, 'secret' => $secret)); ?>" class="im-remove-thread" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to remove this thread? Action cannot be undone!', 'instant_messenger')); ?>');" title="<?php echo osc_esc_html(__('Remove this thread, related messages and attachments from your account', 'instant_messenger')); ?>"><i class="fa fa-trash"></i><span><?php _e('Remove', 'instant_messenger'); ?></span></a>
      <?php } ?>
    </div>
    
    <?php if($last_seen <> '') { ?>
      <div class="im-subhead2"><?php echo sprintf(__('Last online %s', 'instant_messenger'), $last_seen); ?></div>
    <?php } ?>
  </div>

  <?php if($offer) { ?>
    <a href="<?php echo osc_route_url('mo-show-offers', array('offerId' => $thread['i_offer_id'])); ?>" class="im-row im-body im-offer">
      <div class="im-line"><?php echo sprintf(__('<b>Related offer:</b> %sx %s for %s%s', 'instant_messenger'), $offer['i_quantity'], $offer_item['s_title'], $offer['i_price']/1000000, $currency['s_description']); ?></div>
    </a>
  <?php } ?>
  
  <?php if($item_details !== false && isset($item['pk_i_id'])) { echo im_render_item_context($thread['fk_i_item_id'], $item, $item_details); } ?>
  <div class="im-row im-item-related im-body" style="display:none !important;">
    <?php if(false) { ?>
      <div class="im-col-3 im-item-resource"><a target="_blank" href="<?php echo osc_item_url_ns($item['pk_i_id']); ?>"><img src="<?php echo $item_details['resource']; ?>" /></a></div>
      <div class="im-col-21">
        <div class="im-line im-item-title"><a target="_blank" href="<?php echo osc_item_url_ns($item['pk_i_id']); ?>"><?php echo osc_highlight($item['s_title'], 50); ?></a></div>
        <div class="im-line im-item-price"><?php echo $item_details['price']; ?></div>
        <div class="im-line im-item-location"><?php echo $item_details['location']; ?></div>
      </div>
    <?php } ?>
  </div>


  <ul id="im-error-list" class="error-list im-error-list im-body"></ul>


  <?php if(is_array($messages) && count($messages) > 0) { ?>
    <div class="im-table im-messages im-body">
      <div class="im-vertical">
        <span class="top"></span>
        <span class="bot"></span>
      </div>

      <?php $i = 1; ?>
      <?php $show_last = 10; ?>

      <?php if(count($messages) > $show_last) { ?>
        <div class="im-show-older"><span><?php _e('Show older messages', 'instant_messenger'); ?></span></div>
      <?php } ?>

      <?php foreach($messages as $m) { ?>
        <?php 
          // CHECK IF LOGGED USER IS OWNER OF THIS MESSAGE
          if((osc_is_web_user_logged_in() && (osc_logged_user_id() == $thread['i_from_user_id'] && $m['i_type'] == 0 || osc_logged_user_id() == $thread['i_to_user_id'] && $m['i_type'] == 1)) || ($secret == $thread['s_from_secret'] && $m['i_type'] == 0 || $secret == $thread['s_to_secret'] && $m['i_type'] == 1)) {
            $logged_is_owner = true;
          } else {
            $logged_is_owner = false;
            
            if($m['i_type'] == 0) {
              $identify_name = __('customer', 'instant_messenger');
            } else {
              $identify_name = __('seller', 'instant_messenger');
            }
          } 

          if($thread['i_from_user_id'] == osc_logged_user_id()) {
            $u_id = $thread['i_to_user_id'];
          } else {
            $u_id = $thread['i_from_user_id'];
          }

          if($logged_is_owner) {
            $u_name = osc_logged_user_name();
            $u_id = osc_logged_user_id();
            
          } else {
            $u_name = ($thread['i_from_user_id'] == osc_logged_user_id() ? $thread['s_to_user_name'] : $thread['s_from_user_name']);
            $u_id = ($thread['i_from_user_id'] == osc_logged_user_id() ? $thread['i_to_user_id'] : $thread['i_from_user_id']);
          }

          $u_img = im_profile_img_url($u_id, $u_name);
          $avatar_user_name = ($m['i_type'] == 0 ? $thread['s_from_user_name'] : $thread['s_to_user_name']);
          $avatar_profile_url = '';
          if($m['i_type'] == 0 && $from_public_url <> '') {
            $avatar_profile_url = $from_public_url;
          } else if($m['i_type'] == 1 && $to_public_url <> '') {
            $avatar_profile_url = $to_public_url;
          }
          $is_safe = im_text_contains_contact_info($m['s_message']);
        ?>

        <div class="im-table-row<?php if($logged_is_owner) { ?> im-from<?php } else { ?> im-to<?php } ?><?php if(count($messages) - $i >= $show_last) { ?> hidden<?php } ?>" data-message-id="<?php echo $m['pk_i_id']; ?>">
          <div class="im-horizontal">
            <span class="left"></span>
            <span class="right" data-user-id="<?php echo osc_esc_html($m['i_type'] == 0 ? $thread['i_from_user_id'] : $thread['i_to_user_id']); ?>" data-user-name="<?php echo osc_esc_html($avatar_user_name); ?>">
              <?php if($avatar_profile_url <> '') { ?>
                <a href="<?php echo $avatar_profile_url; ?>" class="im-tooltip" title="<?php echo osc_esc_html($avatar_user_name); ?>">
              <?php } ?>
              <img src="<?php echo $u_img; ?>" alt="<?php echo osc_esc_html($avatar_user_name); ?>"<?php if($avatar_profile_url == '') { ?> class="im-tooltip" title="<?php echo osc_esc_html($avatar_user_name); ?>"<?php } ?> />
              <?php if($avatar_profile_url <> '') { ?></a><?php } ?>
            </span>
          </div>

          <div class="im-line im-name-top">
            <div class="im-col-12 im-name im-align-left">
              <strong>
                <?php 
                  if($m['i_type'] == 0) { 
                    echo $thread['s_from_user_name']; 
                  } else { 
                    echo $thread['s_to_user_name']; 
                  } 
                ?>
              </strong> 
              <span class="im-identifier"><?php if($logged_is_owner) { ?><?php _e('you', 'instant_messenger'); ?><?php } else { ?><?php echo $identify_name; ?><?php } ?></span>
            </div>
            <div class="im-col-12 im-date im-align-right im-i im-gray im-has-tooltip" title="<?php echo osc_esc_html(sprintf(__('Message posted on %s', 'instant_messenger'), date('d/m/Y H:i:s', strtotime($m['d_datetime'])))); ?>">
              <span><?php echo im_get_time_diff($m['d_datetime']); ?></span>

              <?php if($m['i_read'] == 1) { ?>
                <i class="fa fa-check im-has-tooltip" title="<?php echo osc_esc_html(sprintf(__('%s has already read this message', 'instant_messenger'), ($m['i_type'] == 1 ? $thread['s_from_user_name'] : $thread['s_to_user_name']))); ?>"></i> 
              <?php } ?>
            </div>
          </div>

          <div class="im-line im-message-content">
            <?php if($is_safe != 0 && !$logged_is_owner) { ?>
              <div class="im-unsafe-info"><?php _e('This message may contain contact info. Be careful!', 'instant_messenger'); ?></div>
            <?php } ?>
            
            <div class="im-col-24 im-align-left"><?php echo $m['s_message']; ?></div>
          </div>

          <div class="im-line im-message-extra <?php if($m['s_file'] <> '' && $att_enable == 1) { ?>im-box-gray<?php } else { ?>im-box-empty<?php } ?>">
            <div class="im-col-10" class="im-align-left">
              <?php if($m['s_file'] <> '' && $att_enable == 1) { ?>
                <a class="im-download" href="<?php echo im_attachment_url($thread['i_thread_id'], $m['s_file']); ?>" target="_blank">
                  <?php echo im_get_extension_icon($m['s_file']); ?>
                  <i class="fa fa-download" style="display:none;"></i>
                  <?php _e('Attachment', 'instant_messenger'); ?>
                </a>
              <?php } ?>
            </div>

            <?php if($logged_is_owner) {?>
              <div class="im-col-14 im-align-right">
                <?php if($m['s_file'] <> '' && $att_enable == 1) { ?>
                  <a class="im-hide" href="<?php echo osc_route_url('im-delete-attachment', array('thread-id' => $thread['i_thread_id'], 'del-att-message-id' => $m['pk_i_id'], 'del-file-name' => $m['s_file'], 'secret' => $secret)); ?>" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to delete attachment', 'instant_messenger')); ?>?')"><span><?php _e('Remove file', 'instant_messenger'); ?></span><i class="fa fa-trash" style="display:none;"></i></a>
                <?php } ?>
              </div>
            <?php } ?>
          </div>


          <?php if($logged_is_owner && $message_delete == 1) {?>
            <div class="im-del-mes-box">
              <a href="<?php echo osc_route_url('im-delete-message', array('thread-id' => $thread['i_thread_id'], 'del-message-id' => $m['pk_i_id'], 'secret' => $secret)); ?>" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to delete this message', 'instant_messenger')); ?>?')"><i class="fa fa-trash"></i></a>
            </div>
          <?php } ?>
        </div>

        <?php $i++; ?>
      <?php } ?>
    </div>
    
    <?php if($blocked_by_you == 0) { ?>
      <div class="im-table im-messages im-errors"><div class="im-err im-err-blocked-by-you"><?php echo $blocked_by_you_msg; ?></div></div>

    <?php } else if($blocked_you == 0) { ?>
      <div class="im-table im-messages im-errors"><div class="im-err im-err-blocked-you"><?php echo $blocked_you_msg; ?></div></div>
    <?php } ?>

  <?php } else { ?>
    <div class="im-empty flashmessage flashmessage-warning"><?php _e('You do not have any messages', 'instant_messenger'); ?></div>
  <?php } ?>


  <?php if(im_param('only_logged') == 1 && !osc_is_web_user_logged_in()) { ?>
    <div class="im-empty flashmessage flashmessage-warning"><?php _e('Please login to send messages', 'instant_messenger'); ?></div>

  <?php } else if($target_is_null === false && $blocked_by_you != 0 && $blocked_you != 0) { ?>
    <form id="im-message-form" class="im-row im-body im-form-validate" action="<?php echo osc_route_url('im-messages', array('thread-id' => $thread['i_thread_id'], 'secret' => $secret)); ?>" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="im-action" id="im-action" value="send_message" />

      <img class="im-logged-user-img im-tooltip" src="<?php echo $logged_user_img; ?>" title="<?php echo osc_esc_html(sprintf(__('You are logged in as %s', 'instant_messenger'), $logged_user_name)); ?>" alt="<?php echo osc_esc_html($logged_user_name); ?>"/>
      <textarea name="im-message" id="im-message" class="im-textarea" placeholder="<?php echo osc_esc_js(__('Type your message...', 'instant_messenger')); ?>" required></textarea>

      <button type="submit" class="im-button-green"><?php _e('Send message', 'instant_messenger'); ?></button>
      <button type="submit" class="im-button-green im-button-alt" style="display:none;"><i class="fa fa-paper-plane"></i></button>

      <?php if($att_enable == 1) { ?>
        <div class="im-attachment">
          <div class="im-att-box">
            <label class="im-status">
              <span class="im-wrap"><i class="fa fa-paperclip"></i> <span><span class="im-def-text"><?php _e('Upload file', 'instant_messenger'); ?></span></span></span>
              <input type="file" name="im-file" id="im-file" class="im-file" />
            </label>
          </div>
        </div>
      <?php } ?>
    </form>
  <?php } ?>
</div>


<?php
  $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>

<script>
//var imMessageUrl = "<?php echo $actual_link; ?>";
var imMessageUrl = "<?php echo osc_route_url('im-refresh-messages', array('thread-id' => $thread_id, 'secret' => (Params::getParam('secret') <> '' ? Params::getParam('secret') : 'n'), 'imaction' => 'refresh')); ?>";


var imShowOlder = 0;
var imAjax = <?php echo $ajax; ?>;

$(document).ready(function() {
  $('body').click();

  // SHOW HIDDEN
  $('body').on('click', '.im-show-older', function(e){
    e.preventDefault();
    imShowOlderMessages();
  });

  // SUBMIT MESSAGE
  $('body').on('click', '#im-message-form button', function(e){
    var button = $(this);
    var form = $(this).closest('form');
    var inputs = form.find('input, select, textarea');

    // Validate form first
    inputs.each(function(){
      form.validate().element($(this));
    });


    if((form.find('input[name="im-file"]').val() == '' || !form.find('input[name="im-file"]').length) && imAjax == 1) {
      if(form.valid()) {
        e.preventDefault();
        button.addClass('im-btn-loading').attr('disabled', true);
        imSubmitButtonLoading(button, true);

        $.ajax({
          url: form.attr('action'),
          type: "POST",
          data: form.find(':input[value!=""]').serialize(),
          success: function(response){
            //console.log('Message sent!');

            imRefreshMessages();
            imClearForm();

            imSubmitButtonLoading(button, false);
            button.removeClass('im-btn-loading').attr('disabled', false);

          },
          error: function() {
            imSubmitButtonLoading(button, false);
            button.removeClass('im-btn-loading').attr('disabled', false);
          }
        });
      }
    } else {
      // submit form with file
    }

  });


  // REFRESH MESSAGES
  if(imAjax == 1) {
    setInterval(function(){
      imRefreshMessages();
    }, <?php echo $interval; ?>);
  }


  // TURN OFF NOTIFICATION
  $(window).on('blur focus click', function() {
    PageTitleNotification.Off();
  });

  // UNLOCK AUDIO PLAYBACK AFTER FIRST USER GESTURE (AUToplay POLICY)
  $(document).one('click keydown touchstart', function() {
    imInitBeepAudio();
    if(imBeepAudio) {
      var unlock = imBeepAudio.play();
      if(unlock && typeof unlock.then === 'function') {
        unlock.then(function() {
          imBeepAudio.pause();
          imBeepAudio.currentTime = 0;
        }).catch(function() {});
      }
    }
  });
});


// REFRESH MESSAGES
function imRefreshMessages() {
  $.ajax({
    url: imMessageUrl,
    type: "GET",
    success: function(response){
      //console.log('Messages loaded');

      if(response.length) {
        var content = $(response).contents().find('.im-table.im-messages').html();
        var messagesCount = $(response).contents().find('.im-table.im-messages .im-table-row').length;
        var lastMessageId = $('.im-table.im-messages .im-table-row:last-child').attr('data-message-id');

        if(
          messagesCount != $('.im-table.im-messages .im-table-row').length
          || (!$('.im-table.im-messages .im-table-row:last-child .im-date .fa-check').length && $(response).contents().find('.im-table.im-messages .im-table-row:last-child .im-date .fa-check').length)
        ) {
          $('.im-table.im-messages').html(content).animate({ scrollTop: $('.im-table.im-messages').prop("scrollHeight")}, 200);


          // IF USER SEEING OLDER MESSAGES, DO NOT COLLAPSE THEM
          if(imShowOlder == 1) {
            imShowOlderMessages();
          }

          // IF THERE IS NEW MESSAGE AND IT'S NOT FROM SENDER
          if(!$('.im-table.im-messages .im-table-row:last-child').hasClass('im-from') && $('.im-table.im-messages .im-table-row:last-child').attr('data-message-id') != lastMessageId) {
            imPlayBeep();

            PageTitleNotification.On('<?php echo osc_esc_js(__('You have new message!', 'instant_messenger')); ?>');

            setTimeout(function(){
              //PageTitleNotification.Off();
            }, 3000);
          }
        }
      }

    }, error: function(response) {
      console.log('Error: Messages not loaded');
    }
  });
}


// CLEAR FORM WHEN MESSAGE IS SENT
function imClearForm() {
  $('#im-message-form textarea[name="im-message"], #im-message-form input[name="im-file"]').val('');
}


// PLAY BEEP SOUND ON NEW MESSAGE (requires prior user interaction on modern browsers)
var imBeepUrl = "<?php echo osc_esc_js(osc_base_url() . 'oc-content/plugins/instant_messenger/audio/beep.mp3'); ?>";
var imBeepAudio = null;

function imInitBeepAudio() {
  if(imBeepAudio || !imBeepUrl) {
    return;
  }

  try {
    imBeepAudio = new Audio(imBeepUrl);
    imBeepAudio.preload = 'auto';
    imBeepAudio.volume = 0.3;
  } catch(e) {}
}

function imPlayBeep() {
  imInitBeepAudio();
  if(!imBeepAudio) {
    return;
  }

  try {
    imBeepAudio.currentTime = 0;
    var playPromise = imBeepAudio.play();
    if(playPromise && typeof playPromise.catch === 'function') {
      playPromise.catch(function() {});
    }
  } catch(e) {}
}


// BROWSER TAB TITLE NOTIFICATION
var PageTitleNotification = {
  Vars:{
    OriginalTitle: document.title,
    Interval: null
  },
  On: function(notification, intervalSpeed){
    var _this = this;
    if(_this.Vars.Interval) {
      clearInterval(_this.Vars.Interval);
    }
    _this.Vars.Interval = setInterval(function(){
      document.title = (_this.Vars.OriginalTitle == document.title) ? notification : _this.Vars.OriginalTitle;
    }, (intervalSpeed) ? intervalSpeed : 1000);
  },
  Off: function(){
    if(this.Vars.Interval) {
      clearInterval(this.Vars.Interval);
      this.Vars.Interval = null;
    }
    document.title = this.Vars.OriginalTitle;
  }
}


// SHOW OLDER MESSAGES
function imShowOlderMessages() {
  $('.im-show-older').hide(0);
  $('.im-table.im-messages .im-table-row').removeClass('hidden');
  imShowOlder = 1;
}
</script>