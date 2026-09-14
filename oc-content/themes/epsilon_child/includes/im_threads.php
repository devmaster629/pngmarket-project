<?php
/**
 * Messages tab entry: open the latest conversation (no standalone list page).
 * Included from instant_messenger/user/threads.php when present.
 */

if (!defined('ABS_PATH')) {
    exit;
}

require_once dirname(__FILE__) . '/im_ui.php';

$user_id = (int) osc_logged_user_id();
$rows = pngm_im_prepare_conversations($user_id, 1, 0);

// Prefer opening the most recent thread instead of a list-only page
if (is_array($rows) && !empty($rows[0]['url'])) {
    $go = $rows[0]['url'];
    if (!headers_sent()) {
        header('Location: ' . $go);
        exit;
    }
    echo '<script>window.location.replace(' . json_encode($go) . ');</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . osc_esc_html($go) . '"></noscript>';
    exit;
}

// No conversations yet — lightweight empty state (+ blocked users)
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

  <div class="pngm-im-list-pane">
    <div class="pngm-im-list-head">
      <h1><?php _e('Messages', 'epsilon'); ?></h1>
    </div>
    <div class="pngm-im-empty" id="pngm-messages-empty">
      <i class="fas fa-comments" aria-hidden="true"></i>
      <p><?php _e('No conversations found', 'epsilon'); ?></p>
      <a class="pngm-ua-btn" href="<?php echo osc_search_url(array()); ?>"><?php _e('Browse listings', 'epsilon'); ?></a>
    </div>
  </div>

  <div class="pngm-im-blocked">
    <?php
      $im_block = osc_plugins_path() . 'instant_messenger/user/block.php';
      if (file_exists($im_block)) {
          require $im_block;
      }
    ?>
  </div>
</div>
