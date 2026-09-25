<?php
$result = im_resolve_create_target();

if($result['error'] != '') {
  osc_add_flash_error_message($result['error']);
  header('Location: ' . $result['redirect']);
  exit;
}

$from_user_id = (osc_is_web_user_logged_in() ? osc_logged_user_id() : null);
// Logged-in users always use their registered name/email (no form fields).
if (osc_is_web_user_logged_in()) {
  $from_user_name = osc_esc_html(osc_logged_user_name());
  $from_user_email = osc_esc_html(osc_logged_user_email());
} else {
  $from_user_name = osc_esc_html(Params::getParam('im-from-user-name') <> '' ? Params::getParam('im-from-user-name') : '');
  $from_user_email = osc_esc_html(Params::getParam('im-from-user-email') <> '' ? Params::getParam('im-from-user-email') : '');
}

// Always reuse buyer+listing (or one-thread profile) conversation — skip Start page.
$check_item_id = (in_array($result['mode'], array('item', 'user_redirect')) ? (int)$result['item_id'] : 0);
$existing = im_find_existing_thread($from_user_id, $from_user_email, $result['to_user_id'], $result['to_user_email'], $check_item_id);
if($existing !== false && isset($existing['i_thread_id'])) {
  im_redirect_to_thread($existing, (osc_is_web_user_logged_in() ? 'n' : $existing['s_from_secret']));
}

$store_item_id = null;
$form_url = '';
$hook_item_id = 0;

if($result['mode'] == 'item' || $result['mode'] == 'user_redirect') {
  if($result['mode'] == 'user_redirect' && (int)$result['user_id'] > 0 && (int)$result['item_id'] <= 0) {
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

// Keep listing id on create so inbox/chat can show which listing the chat is about.
// (Do not clear fk_i_item_id when one_thread_per_user is enabled.)

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

  // Block duplicate insert on double-submit / race.
  $existing_post = im_find_existing_thread($from_user_id, $from_user_email, $result['to_user_id'], $result['to_user_email'], $check_item_id);
  if($existing_post !== false && isset($existing_post['i_thread_id'])) {
    im_redirect_to_thread($existing_post, (osc_is_web_user_logged_in() ? 'n' : $existing_post['s_from_secret']));
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

  <?php
    $pngm_im_ui = WebThemes::newInstance()->getCurrentThemePath() . 'includes/im_ui.php';
    if (file_exists($pngm_im_ui)) {
      require_once $pngm_im_ui;
    }
  ?>

  <?php if(($result['mode'] == 'item' || $result['mode'] == 'user_redirect') && $result['item_id'] > 0) { ?>
    <?php echo im_render_item_context($result['item_id'], $result['item'], $result['item_details']); ?>
  <?php } elseif (function_exists('pngm_im_render_general_inquiry')) { ?>
    <?php echo pngm_im_render_general_inquiry(); ?>
  <?php } else { ?>
    <div class="im-row im-item-context im-body pngm-im-general-inquiry" role="status">
      <div class="im-col-24">
        <div class="im-line im-item-title"><?php echo osc_esc_html(__('General seller inquiry', 'epsilon')); ?></div>
      </div>
    </div>
  <?php } ?>

  <ul id="im-error-list" class="error-list im-error-list im-body"></ul>

  <form id="im-create-thread-form" name="im-create-thread-form" class="im-body im-form-validate" action="<?php echo $form_url; ?>" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="im-action" id="im-action" value="create_thread">

    <?php if (osc_is_web_user_logged_in()) { ?>
      <input type="hidden" name="im-from-user-name" id="im-from-user-name" value="<?php echo osc_esc_html(osc_logged_user_name()); ?>" />
      <input type="hidden" name="im-from-user-email" id="im-from-user-email" value="<?php echo osc_esc_html(osc_logged_user_email()); ?>" />
    <?php } else { ?>
    <div class="im-row">
      <div class="im-col-24">
        <label class="im-label" for="im-from-user-name"><?php _e('Your name', 'instant_messenger'); ?></label>
        <input type="text" class="im-input" name="im-from-user-name" id="im-from-user-name" value="" />
      </div>
    </div>

    <div class="im-row">
      <div class="im-col-24">
        <label class="im-label" for="im-from-user-email"><?php _e('Your email', 'instant_messenger'); ?></label>
        <input type="text" class="im-input" name="im-from-user-email" id="im-from-user-email" value="" />
      </div>
    </div>
    <?php } ?>

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
</div>
