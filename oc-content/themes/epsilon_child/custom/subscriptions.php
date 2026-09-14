<?php
/**
 * Subscriptions — current plan, choose plan, billing history.
 */

if (!osc_is_web_user_logged_in()) {
    header('Location: ' . osc_user_login_url());
    exit;
}

if (!function_exists('pngm_sub_url')) {
    require_once dirname(__FILE__) . '/../includes/subscriptions.php';
}

$user_id = (int) osc_logged_user_id();
$sub_url = pngm_sub_url();
$action = Params::getParam('pngm_sub_action');

if (strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST' && $action === 'set_plan') {
    if (function_exists('osc_csrf_check')) {
        osc_csrf_check();
    }
    if (function_exists('eps_is_demo') && eps_is_demo()) {
        osc_add_flash_error_message(__('You cannot do this on demo site', 'epsilon'));
        header('Location: ' . $sub_url);
        exit;
    }

    $plan_id = Params::getParam('plan');
    $plans = pngm_sub_plans();
    if (!isset($plans[$plan_id])) {
        osc_add_flash_error_message(__('That plan is not available.', 'epsilon'));
        header('Location: ' . $sub_url);
        exit;
    }

    $current = pngm_sub_current_plan($user_id);
    if ($current['id'] === $plan_id) {
        header('Location: ' . $sub_url);
        exit;
    }

    pngm_sub_set_plan($user_id, $plan_id);
    $next = $plans[$plan_id];
    if ((int) $next['price'] <= 0) {
        osc_add_flash_ok_message(__('Your plan was updated to Free.', 'epsilon'));
    } else {
        osc_add_flash_ok_message(sprintf(__('You are now on the %s.', 'epsilon'), $next['label']));
    }
    header('Location: ' . $sub_url);
    exit;
}

$plans = pngm_sub_plans();
$current = pngm_sub_current_plan($user_id);
$billing = pngm_sub_billing($user_id);
$active_count = pngm_sub_active_listings_count($user_id);
$listings_limit = (int) $current['listings'];
$listings_stat = $listings_limit > 0
    ? sprintf('%d of %d', $active_count, $listings_limit)
    : sprintf('%d · %s', $active_count, __('Unlimited', 'epsilon'));
$photos_stat = sprintf(__('%d per listing', 'epsilon'), (int) $current['photos']);
?>

<div class="pngm-sub">
  <button type="button" class="pngm-sub-account-menu" data-pngm-ua-menu="1">
    <span><i class="fas fa-list-ul" aria-hidden="true"></i> <?php _e('Account menu', 'epsilon'); ?></span>
    <i class="fas fa-chevron-down" aria-hidden="true"></i>
  </button>

  <div class="pngm-sub-head">
    <h1><?php _e('Subscriptions', 'epsilon'); ?></h1>
    <p><?php _e('Manage your listing promotion plans and billing.', 'epsilon'); ?></p>
  </div>

  <section class="pngm-sub-block">
    <h2 class="pngm-sub-label"><?php _e('Current plan', 'epsilon'); ?></h2>
    <div class="pngm-sub-current">
      <div class="pngm-sub-current-copy">
        <div class="pngm-sub-current-title">
          <strong><?php echo osc_esc_html($current['label']); ?></strong>
          <span class="pngm-sub-badge is-ok"><?php _e('Active', 'epsilon'); ?></span>
        </div>
        <p><?php echo osc_esc_html($current['blurb']); ?></p>
      </div>
      <div class="pngm-sub-stats" role="list">
        <div class="pngm-sub-stat" role="listitem">
          <i class="fas fa-list-ul" aria-hidden="true"></i>
          <div>
            <span><?php _e('Active listings', 'epsilon'); ?></span>
            <strong><?php echo osc_esc_html($listings_stat); ?></strong>
          </div>
        </div>
        <div class="pngm-sub-stat" role="listitem">
          <i class="far fa-image" aria-hidden="true"></i>
          <div>
            <span><?php _e('Photo limit', 'epsilon'); ?></span>
            <strong><?php echo osc_esc_html($photos_stat); ?></strong>
          </div>
        </div>
        <div class="pngm-sub-stat" role="listitem">
          <i class="far fa-eye" aria-hidden="true"></i>
          <div>
            <span><?php _e('Visibility', 'epsilon'); ?></span>
            <strong><?php echo osc_esc_html($current['visibility']); ?></strong>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="pngm-sub-block">
    <h2 class="pngm-sub-label"><?php _e('Choose your plan', 'epsilon'); ?></h2>
    <div class="pngm-sub-plans">
      <?php foreach ($plans as $plan) {
          $is_current = ($plan['id'] === $current['id']);
          ?>
        <article class="pngm-sub-plan<?php echo $is_current ? ' is-current' : ''; ?>">
          <div class="pngm-sub-plan-head">
            <h3><?php echo osc_esc_html($plan['name']); ?></h3>
            <div class="pngm-sub-price">
              <strong><?php echo osc_esc_html($plan['price_label']); ?></strong>
              <span>/ <?php echo osc_esc_html($plan['period']); ?></span>
            </div>
            <p><?php echo osc_esc_html($plan['tagline']); ?></p>
          </div>
          <ul class="pngm-sub-features">
            <?php foreach ((array) $plan['features'] as $feat) { ?>
              <li><i class="fas fa-check" aria-hidden="true"></i><span><?php echo osc_esc_html($feat); ?></span></li>
            <?php } ?>
          </ul>
          <?php if ($is_current) { ?>
            <button type="button" class="pngm-ua-btn is-ghost is-block" disabled><?php _e('Current Plan', 'epsilon'); ?></button>
          <?php } else { ?>
            <form method="post" action="<?php echo osc_esc_html($sub_url); ?>">
              <input type="hidden" name="pngm_sub_action" value="set_plan" />
              <input type="hidden" name="plan" value="<?php echo osc_esc_html($plan['id']); ?>" />
              <?php if (function_exists('osc_csrf_token_form')) { osc_csrf_token_form(); } ?>
              <button type="submit" class="pngm-ua-btn is-block">
                <?php
                  if ((int) $plan['price'] > (int) $current['price']) {
                      echo osc_esc_html(sprintf(__('Upgrade to %s', 'epsilon'), $plan['name']));
                  } elseif ((int) $plan['price'] < (int) $current['price']) {
                      echo osc_esc_html(sprintf(__('Switch to %s', 'epsilon'), $plan['name']));
                  } else {
                      echo osc_esc_html(sprintf(__('Choose %s', 'epsilon'), $plan['name']));
                  }
                ?>
              </button>
            </form>
          <?php } ?>
        </article>
      <?php } ?>
    </div>
  </section>

  <section class="pngm-sub-block">
    <h2 class="pngm-sub-label"><?php _e('Billing history', 'epsilon'); ?></h2>
    <div class="pngm-sub-billing">
      <div class="pngm-sub-billing-table-wrap">
        <table class="pngm-sub-billing-table">
          <thead>
            <tr>
              <th><?php _e('Date', 'epsilon'); ?></th>
              <th><?php _e('Description', 'epsilon'); ?></th>
              <th><?php _e('Amount', 'epsilon'); ?></th>
              <th><?php _e('Status', 'epsilon'); ?></th>
              <th><?php _e('Receipt', 'epsilon'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($billing)) {
                foreach ($billing as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    ?>
              <tr>
                <td data-label="<?php echo osc_esc_html(__('Date', 'epsilon')); ?>"><?php echo osc_esc_html((string) @$row['date']); ?></td>
                <td data-label="<?php echo osc_esc_html(__('Description', 'epsilon')); ?>"><?php echo osc_esc_html((string) @$row['description']); ?></td>
                <td data-label="<?php echo osc_esc_html(__('Amount', 'epsilon')); ?>"><?php echo osc_esc_html((string) @$row['amount']); ?></td>
                <td data-label="<?php echo osc_esc_html(__('Status', 'epsilon')); ?>"><span class="pngm-sub-badge is-ok"><?php echo osc_esc_html((string) @$row['status']); ?></span></td>
                <td data-label="<?php echo osc_esc_html(__('Receipt', 'epsilon')); ?>">
                  <?php if (!empty($row['receipt'])) { ?>
                    <a href="<?php echo osc_esc_html((string) $row['receipt']); ?>"><?php _e('View', 'epsilon'); ?></a>
                  <?php } else { ?>
                    <span class="pngm-sub-muted">—</span>
                  <?php } ?>
                </td>
              </tr>
                <?php }
            } ?>
          </tbody>
        </table>
      </div>

      <?php if (empty($billing)) { ?>
        <div class="pngm-sub-empty">
          <span class="pngm-sub-empty-ico" aria-hidden="true"><i class="far fa-file-alt"></i></span>
          <strong><?php _e('No billing history', 'epsilon'); ?></strong>
          <p><?php _e("You haven't made any payments yet. Your billing history will appear here when you subscribe.", 'epsilon'); ?></p>
        </div>
      <?php } ?>
    </div>
  </section>
</div>
