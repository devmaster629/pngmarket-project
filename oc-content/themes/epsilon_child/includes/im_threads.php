<?php
/**
 * Messages tab entry — conversation list.
 * Desktop: list + empty board pane (pick a thread; AJAX loads the chat).
 * Mobile: list only; tap opens the full thread page.
 */

if (!defined('ABS_PATH')) {
    exit;
}

require_once dirname(__FILE__) . '/im_ui.php';

$user_id = (int) osc_logged_user_id();
$rows = pngm_im_prepare_conversations($user_id, 50, 0);
$is_mobile = pngm_im_is_mobile_request();
?>
<link href="<?php echo osc_base_url(); ?>oc-content/plugins/instant_messenger/css/tipped.css" rel="stylesheet" type="text/css" />
<script src="<?php echo osc_base_url(); ?>oc-content/plugins/instant_messenger/js/tipped.js"></script>
<script src="<?php echo osc_base_url(); ?>oc-content/plugins/instant_messenger/js/user.js?v=<?php echo date('Ymdhis'); ?>"></script>

<div class="im-html im-file-threads im-theme-<?php echo osc_esc_html(osc_current_web_theme()); ?> pngm-im pngm-im-inbox">
  <?php if (im_param('limit_enabled') == 1) {
      $limit_check = im_check_user_limits($user_id, osc_logged_user_email(), true);
      if ($limit_check !== true) { ?>
        <div class="im-limits-info"><?php echo $limit_check; ?></div>
      <?php }
  } ?>

  <?php if (!$is_mobile) { ?>
  <div class="pngm-im-split">
  <?php } ?>

    <?php pngm_im_render_conversation_list($rows, 0); ?>

  <?php if (!$is_mobile) { ?>
    <div class="pngm-im-board-pane pngm-im-board-pane--idle" aria-label="<?php echo osc_esc_html(__('Conversation', 'epsilon')); ?>">
      <div class="pngm-im-board-placeholder">
        <i class="fas fa-comments" aria-hidden="true"></i>
        <p><?php _e('Select a conversation to read and reply', 'epsilon'); ?></p>
      </div>
    </div>
  </div>
  <?php } ?>
</div>
<?php pngm_im_ui_script(); ?>
