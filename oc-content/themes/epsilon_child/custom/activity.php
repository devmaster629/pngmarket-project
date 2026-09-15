<?php
/**
 * Activity — in-app notification feed (rendered inside user-custom.php shell).
 */

if (!osc_is_web_user_logged_in()) {
    header('Location: ' . osc_user_login_url());
    exit;
}

if (!function_exists('pngm_activity_get')) {
    require_once dirname(__FILE__) . '/../includes/activity.php';
}

$user_id = (int) osc_logged_user_id();
$activity_url = pngm_activity_url();
$action = Params::getParam('pngm_activity_action');

if (strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST' && $action !== '') {
    if (function_exists('osc_csrf_check')) {
        osc_csrf_check();
    }

    if ($action === 'delete') {
        pngm_activity_delete($user_id, Params::getParam('id'));
    } elseif ($action === 'clear') {
        pngm_activity_clear_all($user_id);
    }

    header('Location: ' . $activity_url);
    exit;
}

pngm_activity_mark_all_read($user_id);
$items = pngm_activity_get($user_id);
?>

<div class="pngm-activity">
  <div class="pngm-activity-head">
    <h1><?php _e('Activity', 'epsilon'); ?></h1>
    <?php if (!empty($items)) { ?>
      <form method="post" action="<?php echo osc_esc_html($activity_url); ?>" onsubmit="return confirm('<?php echo osc_esc_js(__('Clear all activity?', 'epsilon')); ?>');">
        <input type="hidden" name="pngm_activity_action" value="clear" />
        <?php if (function_exists('osc_csrf_token_form')) { osc_csrf_token_form(); } ?>
        <button type="submit" class="pngm-activity-clear"><?php _e('Clear all', 'epsilon'); ?></button>
      </form>
    <?php } ?>
  </div>

  <button type="button" class="pngm-sec-account-menu" data-pngm-ua-menu="1">
    <span><i class="fas fa-list-ul" aria-hidden="true"></i> <?php _e('Account menu', 'epsilon'); ?></span>
    <i class="fas fa-chevron-right" aria-hidden="true"></i>
  </button>

  <?php if (empty($items)) { ?>
    <div class="pngm-activity-empty">
      <i class="fas fa-bell-slash" aria-hidden="true"></i>
      <p><?php _e('No activity yet. Listing and account updates will show up here.', 'epsilon'); ?></p>
    </div>
  <?php } else { ?>
    <ul class="pngm-activity-list">
      <?php foreach ($items as $row) {
          $id = isset($row['id']) ? (string) $row['id'] : '';
          $title = isset($row['title']) ? (string) $row['title'] : '';
          $body = isset($row['body']) ? (string) $row['body'] : '';
          $url = isset($row['url']) ? (string) $row['url'] : '';
          $type = isset($row['type']) ? (string) $row['type'] : '';
          $ts = isset($row['ts']) ? (int) $row['ts'] : 0;
          $icon = pngm_activity_icon($type);
          ?>
        <li class="pngm-activity-item">
          <span class="pngm-activity-ico" aria-hidden="true"><i class="<?php echo osc_esc_html($icon); ?>"></i></span>
          <div class="pngm-activity-copy">
            <?php if ($url !== '') { ?>
              <a class="pngm-activity-title" href="<?php echo osc_esc_html($url); ?>"><?php echo osc_esc_html($title); ?></a>
            <?php } else { ?>
              <strong class="pngm-activity-title"><?php echo osc_esc_html($title); ?></strong>
            <?php } ?>
            <?php if ($body !== '') { ?>
              <p class="pngm-activity-body"><?php echo osc_esc_html($body); ?></p>
            <?php } ?>
            <time class="pngm-activity-time" datetime="<?php echo $ts > 0 ? date('c', $ts) : ''; ?>">
              <?php echo osc_esc_html(pngm_activity_time_label($ts)); ?>
            </time>
          </div>
          <form method="post" action="<?php echo osc_esc_html($activity_url); ?>" class="pngm-activity-del-form">
            <input type="hidden" name="pngm_activity_action" value="delete" />
            <input type="hidden" name="id" value="<?php echo osc_esc_html($id); ?>" />
            <?php if (function_exists('osc_csrf_token_form')) { osc_csrf_token_form(); } ?>
            <button type="submit" class="pngm-activity-del" title="<?php echo osc_esc_html(__('Remove', 'epsilon')); ?>" aria-label="<?php echo osc_esc_html(__('Remove', 'epsilon')); ?>">
              <i class="fas fa-times" aria-hidden="true"></i>
            </button>
          </form>
        </li>
      <?php } ?>
    </ul>
  <?php } ?>
</div>
