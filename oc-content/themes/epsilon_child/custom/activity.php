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

if (!function_exists('pngm_ua_render_page_header')) {
    require_once dirname(__FILE__) . '/../includes/account_ua.php';
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
$item_count = is_array($items) ? count($items) : 0;
$listings_url = function_exists('pngm_ua_items_url') ? pngm_ua_items_url('all') : osc_user_list_items_url();
?>

<div class="pngm-activity">
  <div class="pngm-activity-head">
    <div class="pngm-activity-head-text">
      <h1><?php _e('Activity', 'epsilon'); ?></h1>
      <p class="pngm-activity-sub">
        <?php
          if ($item_count > 0) {
              echo osc_esc_html(sprintf(_n('%d update', '%d updates', $item_count, 'epsilon'), $item_count));
          } else {
              _e('Listing and account updates show up here.', 'epsilon');
          }
        ?>
      </p>
    </div>
    <div class="pngm-activity-head-actions">
      <?php if ($item_count > 0) { ?>
        <form method="post" action="<?php echo osc_esc_html($activity_url); ?>" class="pngm-activity-clear-form" onsubmit="return confirm('<?php echo osc_esc_js(__('Clear all activity?', 'epsilon')); ?>');">
          <input type="hidden" name="pngm_activity_action" value="clear" />
          <?php if (function_exists('osc_csrf_token_form')) { osc_csrf_token_form(); } ?>
          <button type="submit" class="pngm-activity-clear"><?php _e('Clear all', 'epsilon'); ?></button>
        </form>
      <?php } ?>
      <div class="pngm-ua-userchip pngm-activity-chip">
        <a href="<?php echo osc_esc_html(osc_user_profile_url()); ?>" class="pngm-ua-userchip-link">
          <img src="<?php echo osc_esc_html(function_exists('eps_profile_picture') ? eps_profile_picture($user_id, 'medium') : osc_user_profile_img_url($user_id)); ?>" alt="" width="48" height="48" />
          <span>
            <strong><?php echo sprintf(__('Hi, %s', 'epsilon'), osc_esc_html(osc_logged_user_name())); ?></strong>
            <small><?php _e('My Account', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></small>
          </span>
        </a>
      </div>
    </div>
  </div>

  <button type="button" class="pngm-sec-account-menu" data-pngm-ua-menu="1">
    <span><i class="fas fa-list-ul" aria-hidden="true"></i> <?php _e('Account menu', 'epsilon'); ?></span>
    <i class="fas fa-chevron-right" aria-hidden="true"></i>
  </button>

  <?php if ($item_count < 1) { ?>
    <div class="pngm-activity-empty">
      <span class="pngm-activity-empty-ico" aria-hidden="true"><i class="fas fa-bell"></i></span>
      <h2><?php _e('No activity yet', 'epsilon'); ?></h2>
      <p><?php _e('Listing approvals, expirations, and account updates will appear here.', 'epsilon'); ?></p>
      <a class="pngm-ua-btn" href="<?php echo osc_esc_html($listings_url); ?>"><?php _e('View my listings', 'epsilon'); ?></a>
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
          $label = pngm_activity_type_label($type);
          $type_mod = preg_replace('/[^a-z0-9_-]+/i', '-', $type);
          if ($type_mod === '') {
              $type_mod = 'update';
          }
          ?>
        <li class="pngm-activity-card is-<?php echo osc_esc_html($type_mod); ?>">
          <span class="pngm-activity-ico" aria-hidden="true"><i class="<?php echo osc_esc_html($icon); ?>"></i></span>
          <div class="pngm-activity-copy">
            <div class="pngm-activity-meta">
              <span class="pngm-activity-badge"><?php echo osc_esc_html($label); ?></span>
              <time class="pngm-activity-time" datetime="<?php echo $ts > 0 ? date('c', $ts) : ''; ?>">
                <?php echo osc_esc_html(pngm_activity_time_label($ts)); ?>
              </time>
            </div>
            <?php if ($url !== '') { ?>
              <a class="pngm-activity-title" href="<?php echo osc_esc_html($url); ?>"><?php echo osc_esc_html($title); ?></a>
            <?php } else { ?>
              <strong class="pngm-activity-title"><?php echo osc_esc_html($title); ?></strong>
            <?php } ?>
            <?php if ($body !== '') { ?>
              <p class="pngm-activity-body"><?php echo osc_esc_html($body); ?></p>
            <?php } ?>
            <?php if ($url !== '') { ?>
              <a class="pngm-activity-link" href="<?php echo osc_esc_html($url); ?>">
                <?php _e('View details', 'epsilon'); ?>
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
              </a>
            <?php } ?>
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
