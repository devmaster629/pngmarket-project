<?php
$result = im_resolve_create_target();

if($result['error'] != '') {
  osc_add_flash_error_message($result['error']);
  header('Location: ' . $result['redirect']);
  exit;
}

$from_user_id = (osc_is_web_user_logged_in() ? osc_logged_user_id() : null);
$from_user_name = osc_esc_html(Params::getParam('im-from-user-name') <> '' ? Params::getParam('im-from-user-name') : osc_logged_user_name());
$from_user_email = osc_esc_html(Params::getParam('im-from-user-email') ? Params::getParam('im-from-user-email') : osc_logged_user_email());

if(im_param('one_thread_per_user') == 1) {
  $check_item_id = (in_array($result['mode'], array('item', 'user_redirect')) ? $result['item_id'] : 0);
  $existing = im_find_existing_thread($from_user_id, $from_user_email, $result['to_user_id'], $result['to_user_email'], $check_item_id);
  if($existing !== false && isset($existing['i_thread_id'])) {
    im_redirect_to_thread($existing, (osc_is_web_user_logged_in() ? 'n' : $existing['s_from_secret']));
  }
}

$store_item_id = null;
$form_url = '';
$hook_item_id = 0;

if($result['mode'] == 'item' || $result['mode'] == 'user_redirect') {
  if($result['mode'] == 'user_redirect' && (int)$result['user_id'] > 0) {
    $form_url = im_create_thread_url(array('user_id' => $result['user_id']));
  } else {
    $form_url = im_create_thread_url(array('item_id' => $result['item_id']));
    $store_item_id = $result['item_id'];
    $hook_item_id = $result['item_id'];
  }
} else {
  $form_url = im_create_thread_url(array('user_id' => $result['user_id']));
  $hook_item_id = 0;
}

if(im_param('one_thread_per_user') == 1 && (int)$result['to_user_id'] > 0) {
  $store_item_id = null;
}

$target_details = im_get_user_details($result['to_user_id'], $result['to_user_name'], $result['to_user_email']);

if(Params::getParam('im-action') == 'create_thread') {
  $limit_check_result = im_check_user_limits($from_user_id, $from_user_email);
  if($limit_check_result !== true) {
    osc_add_flash_error_message($limit_check_result);
    header('Location: ' . osc_route_url('im-threads'));
    exit;
  }

  if(im_check_block($result['to_user_id'], $from_user_email) == 0) {
    header('Location: ' . osc_route_url('im-threads'));
    exit;
  }

  $title = (im_param('autogenerate_title') == 1 ? '' : trim(osc_esc_html(Params::getParam('im-title'))));
  if($title == '') {
    if($store_item_id > 0 && isset($result['item']['s_title'])) {
      $title = sprintf(__('Inquiry: %s', 'instant_messenger'), osc_highlight($result['item']['s_title'], 64));
    } else {
      $title = sprintf(__('Message to %s', 'instant_messenger'), osc_highlight($result['to_user_name'], 64));
    }
  }

  $thread_id = ModelIM::newInstance()->createThread($store_item_id, $from_user_id, $from_user_name, $from_user_email, $result['to_user_id'], $result['to_user_name'], $result['to_user_email'], $title, 0);
  $thread = ModelIM::newInstance()->getThreadById($thread_id);

  im_insert_message($thread['i_thread_id'], nl2br(htmlspecialchars(im_str(Params::getParam('im-message', false, false)), ENT_QUOTES, 'UTF-8')), 0, Params::getFiles('im-file'));
}
?>

<div class="im-html im-file-create-thread im-theme-<?php echo osc_current_web_theme(); ?>">
  <h2 class="im-head"><?php _e('Start conversation', 'instant_messenger'); ?></h2>

  <?php echo im_render_target_bar($target_details); ?>

  <?php if(($result['mode'] == 'item' || $result['mode'] == 'user_redirect') && $result['item_id'] > 0) { ?>
    <?php echo im_render_item_context($result['item_id'], $result['item'], $result['item_details']); ?>
  <?php } ?>

  <ul id="im-error-list" class="error-list im-error-list im-body"></ul>

  <form id="im-create-thread-form" name="im-create-thread-form" class="im-body im-form-validate" action="<?php echo $form_url; ?>" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="im-action" id="im-action" value="create_thread">

    <div class="im-row">
      <div class="im-col-24">
        <label class="im-label" for="im-from-user-name"><?php _e('Your name', 'instant_messenger'); ?></label>
        <input type="text" class="im-input" name="im-from-user-name" id="im-from-user-name" value="<?php echo osc_esc_html(osc_logged_user_name()); ?>" />
      </div>
    </div>

    <div class="im-row">
      <div class="im-col-24">
        <label class="im-label" for="im-from-user-email"><?php _e('Your email', 'instant_messenger'); ?></label>
        <input type="text" class="im-input" name="im-from-user-email" id="im-from-user-email" value="<?php echo osc_esc_html(osc_logged_user_email()); ?>" />
      </div>
    </div>

    <?php if(im_param('autogenerate_title') != 1) { ?>
      <div class="im-row">
        <div class="im-col-24">
          <label class="im-label" for="im-title"><?php _e('Title', 'instant_messenger'); ?></label>
          <input type="text" class="im-input im-big" name="im-title" id="im-title" placeholder="<?php echo osc_esc_html(__('Message title', 'instant_messenger')); ?>" value="" />
        </div>
      </div>
    <?php } ?>

    <div class="im-row">
      <div class="im-col-24">
        <label class="im-label" for="im-message"><?php _e('Message', 'instant_messenger'); ?></label>
        <textarea name="im-message" id="im-message" class="im-textarea" placeholder="<?php echo osc_esc_html(__('Write all details here', 'instant_messenger')); ?>"></textarea>
      </div>
    </div>
    
    <?php osc_run_hook('im_create_thread_form', $hook_item_id, osc_logged_user_id()); ?>

    <button type="submit" class="im-button-green"><?php _e('Send message', 'instant_messenger'); ?></button>

    <?php if(im_param('att_enable') == 1) { ?>
      <div class="im-attachment">
        <div class="im-att-box">
          <label class="im-status">
            <span class="im-wrap"><i class="fa fa-paperclip"></i> <span><?php _e('Upload file', 'instant_messenger'); ?></span></span>
            <input type="file" name="im-file" id="im-file" class="im-file" />
          </label>
        </div>
      </div>
    <?php } ?>
  </form>

  <?php if($result['mode'] == 'item' && im_param('one_thread_per_user') != 1) { ?>
    <?php $threads = ModelIM::newInstance()->getThreadsByItemId($result['item_id'], osc_logged_user_id()); ?>

    <?php if(is_array($threads) && count($threads) > 0) { ?>
      <div class="im-threads-exist im-body">
        <h3 class="im-head"><?php _e('You have already contacted seller on this listing, you may want to continue in existing conversation', 'instant_messenger'); ?></h3>

        <?php foreach($threads as $t) { ?>
          <?php $time_diff = im_get_time_diff($t['d_datetime']); ?>

          <a class="im-row im-has-tooltip-left" href="<?php echo osc_route_url('im-messages', array('thread-id' => $t['i_thread_id'], 'secret' => 'n')); ?>" title="<?php _e('Open conversation', 'instant_messenger'); ?>">
            <div class="im-col-12 im-b im-title"><?php echo ($t['s_title'] <> '' ? osc_highlight($t['s_title'], 40) : __('No subject', 'instant_messenger')); ?></div>
            <div class="im-col-4 im-from-to"><?php echo ($t['i_from_user_id'] == osc_logged_user_id() ? __('to', 'instant_messenger') : __('from', 'instant_messenger')); ?> <strong><?php echo $t['s_to_user_name']; ?></strong></div>
            <div class="im-col-4 im-pms im-align-center"><?php echo $t['i_count'] . ' ' . ($t['i_count'] == 1 ? __('pm', 'instant_messenger') : __('pms', 'instant_messenger')); ?></div>
            <div class="im-col-4 im-time im-align-right"><?php echo $time_diff; ?></div>
          </a>
        <?php } ?>
      </div>
    <?php } ?>
  <?php } ?>
</div>
