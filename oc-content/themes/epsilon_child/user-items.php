<?php Params::setParam('itemsPerPage', 12); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>
<body id="user-items" class="body-ua pngm-ua pngm-ua-listings">
  <?php osc_current_web_theme_path('header.php'); ?>

  <?php
    if (!function_exists('pngm_ua_render_sidebar')) {
      require_once dirname(__FILE__) . '/includes/account_ua.php';
    }

    $param = (osc_version() >= 830 ? 'sItemType' : 'itemType');
    $current_type = Params::getParam($param);
    if ($current_type === '') {
      $current_type = 'all';
    }

    $user_id = osc_logged_user_id();
    $counts = pngm_ua_listing_counts($user_id);
    $pattern = Params::getParam('sPattern');
    $category = Params::getParam('sCategory');
    $order = Params::getParam('sOrder');
    $order_type = Params::getParam('sOrderType');
    if ($order === '') {
      $order = 'dt_pub_date';
    }
    if ($order_type === '') {
      $order_type = 'DESC';
    }

    $tabs = array(
      'all' => array('label' => __('All listings', 'epsilon'), 'count' => $counts['all']),
      'active' => array('label' => __('Active', 'epsilon'), 'count' => $counts['active']),
      'pending_validate' => array('label' => __('Pending validation', 'epsilon'), 'count' => $counts['pending_validate']),
      'blocked' => array('label' => __('Inactive', 'epsilon'), 'count' => $counts['blocked']),
      'expired' => array('label' => __('Expired', 'epsilon'), 'count' => $counts['expired']),
    );

    $sort_options = array(
      'dt_pub_date|DESC' => __('Sort: Newest', 'epsilon'),
      'dt_pub_date|ASC' => __('Sort: Oldest', 'epsilon'),
      'i_price|DESC' => __('Sort: Price high–low', 'epsilon'),
      'i_price|ASC' => __('Sort: Price low–high', 'epsilon'),
      'i_num_views|DESC' => __('Sort: Most viewed', 'epsilon'),
    );
    $sort_key = $order . '|' . $order_type;
  ?>

  <div class="container primary pngm-ua-shell">
    <?php pngm_ua_render_sidebar(); ?>

    <div id="user-main" class="pngm-ua-main pngm-listings">
      <?php osc_run_hook('user_items_top'); ?>

      <div class="pngm-listings-head">
        <div class="pngm-listings-head-text">
          <h1><?php _e('My Listings — Complete Management', 'epsilon'); ?></h1>
        </div>
        <div class="pngm-ua-userchip pngm-listings-chip">
          <a href="<?php echo osc_user_profile_url(); ?>" class="pngm-ua-userchip-link">
            <img src="<?php echo eps_profile_picture($user_id, 'medium'); ?>" alt="" width="48" height="48" />
            <span>
              <strong><?php echo sprintf(__('Hi, %s', 'epsilon'), osc_esc_html(osc_logged_user_name())); ?></strong>
              <small><?php _e('My Account', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></small>
            </span>
          </a>
        </div>
      </div>

      <nav class="pngm-listings-tabs" aria-label="<?php echo osc_esc_html(__('Listing filters', 'epsilon')); ?>">
        <?php foreach ($tabs as $key => $tab) {
          $is_active = ($current_type === $key);
        ?>
          <a class="pngm-listings-tab<?php echo $is_active ? ' is-active' : ''; ?>" href="<?php echo osc_esc_html(pngm_ua_items_url($key)); ?>">
            <span class="pngm-listings-tab-label"><?php echo osc_esc_html($tab['label']); ?></span>
            <em class="pngm-listings-tab-count"><?php echo (int) $tab['count']; ?></em>
          </a>
        <?php } ?>
      </nav>

      <form name="user-items-search" action="<?php echo osc_base_url(true); ?>" method="get" class="pngm-listings-toolbar nocsrf">
        <input type="hidden" name="page" value="user" />
        <input type="hidden" name="action" value="items" />
        <?php if ($current_type !== 'all') { ?>
          <input type="hidden" name="<?php echo osc_esc_html($param); ?>" value="<?php echo osc_esc_html($current_type); ?>" />
        <?php } ?>

        <div class="pngm-listings-search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input type="search" name="sPattern" id="sPattern" value="<?php echo osc_esc_html($pattern); ?>" placeholder="<?php echo osc_esc_html(__('Search my listings…', 'epsilon')); ?>" />
        </div>

        <div class="pngm-listings-filters">
          <label class="pngm-listings-select">
            <span class="is-sr-only"><?php _e('Category', 'epsilon'); ?></span>
            <?php UserForm::search_category_select(); ?>
          </label>

          <label class="pngm-listings-select">
            <span class="is-sr-only"><?php _e('Sort', 'epsilon'); ?></span>
            <select name="pngm_sort" id="pngm_sort" data-sort-select="1">
              <?php foreach ($sort_options as $val => $label) { ?>
                <option value="<?php echo osc_esc_html($val); ?>"<?php echo ($sort_key === $val) ? ' selected' : ''; ?>><?php echo osc_esc_html($label); ?></option>
              <?php } ?>
            </select>
            <input type="hidden" name="sOrder" id="sOrder" value="<?php echo osc_esc_html($order); ?>" />
            <input type="hidden" name="sOrderType" id="sOrderType" value="<?php echo osc_esc_html($order_type); ?>" />
          </label>
        </div>
      </form>

      <?php if ($current_type === 'expired') { ?>
        <div class="pngm-listings-policy" role="note">
          <i class="fas fa-info-circle" aria-hidden="true"></i>
          <p><?php _e('Expired listings are inactive and hidden from search — they are not deleted. Use Renew on any listing below to reactivate it for another 30 days with a new expiry date.', 'epsilon'); ?></p>
        </div>
      <?php } ?>

      <div class="pngm-listings-list items-box <?php echo osc_esc_html($current_type); ?>">
        <?php if (osc_count_items() > 0) { ?>
          <?php while (osc_has_items()) {
            $status = pngm_ua_item_status();
            View::newInstance()->_erase('resources');
            $photo_count = osc_images_enabled_at_items() ? (int) osc_count_item_resources() : 0;
            $thumb = eps_get_noimage();
            if ($photo_count > 0) {
              osc_reset_resources();
              if (osc_has_item_resources()) {
                $thumb = osc_resource_thumbnail_url();
              }
            }
            $loc = pngm_ua_item_location_short();
            $secret = osc_item_field('s_secret');
            $item_extra = function_exists('eps_item_extra') ? eps_item_extra(osc_item_id()) : array();
          ?>
            <article class="pngm-listing-card status-<?php echo osc_esc_html($status['key']); ?>">
              <a class="pngm-listing-thumb" href="<?php echo osc_item_url(); ?>">
                <img src="<?php echo osc_esc_html($thumb); ?>" alt="" width="120" height="90" loading="lazy" />
                <?php if ($photo_count > 0) { ?>
                  <span class="pngm-listing-photo-count"><i class="fas fa-camera" aria-hidden="true"></i> <?php echo $photo_count; ?></span>
                <?php } ?>
              </a>

              <div class="pngm-listing-body">
                <div class="pngm-listing-main">
                  <h2 class="pngm-listing-title"><a href="<?php echo osc_item_url(); ?>"><?php echo osc_esc_html(osc_item_title()); ?></a></h2>
                  <p class="pngm-listing-cat"><?php echo osc_esc_html(pngm_ua_item_category_path()); ?></p>
                  <?php if ($loc !== '') { ?>
                    <p class="pngm-listing-loc"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php echo osc_esc_html($loc); ?></p>
                  <?php } ?>
                  <?php if (eps_check_category_price(osc_item_category_id())) { ?>
                    <p class="pngm-listing-price"><?php echo osc_item_formated_price(); ?></p>
                  <?php } ?>
                </div>

                <div class="pngm-listing-meta">
                  <span class="pngm-listing-badge is-<?php echo osc_esc_html($status['key']); ?>"><?php echo osc_esc_html($status['label']); ?></span>
                  <?php
                    $expiry_label = function_exists('pngm_listing_expiry_label') ? pngm_listing_expiry_label() : '';
                    $days_left = function_exists('pngm_listing_expiry_days_left') ? pngm_listing_expiry_days_left() : null;
                    $in_warn = function_exists('pngm_listing_expiry_in_warn_window') && pngm_listing_expiry_in_warn_window();
                    if ($expiry_label !== '' && ($status['key'] === 'active' || $status['key'] === 'expired')) {
                      if ($status['key'] === 'expired') {
                        echo '<span class="pngm-listing-expiry is-expired">' . osc_esc_html(sprintf(__('Expired %s', 'epsilon'), $expiry_label)) . '</span>';
                      } elseif ($in_warn && $days_left !== null) {
                        echo '<span class="pngm-listing-expiry is-expiring">' . osc_esc_html(sprintf(
                          _n('Expires in %d day', 'Expires in %d days', (int) $days_left, 'epsilon'),
                          (int) $days_left
                        )) . '</span>';
                      } else {
                        echo '<span class="pngm-listing-expiry">' . osc_esc_html(sprintf(__('Expires %s', 'epsilon'), $expiry_label)) . '</span>';
                      }
                    }
                  ?>
                  <time class="pngm-listing-date" datetime="<?php echo osc_esc_html(osc_item_pub_date()); ?>"><?php echo osc_format_date(osc_item_pub_date()); ?></time>
                </div>

                <div class="pngm-listing-actions">
                  <?php if ($status['key'] === 'expired' && osc_item_can_renew()) { ?>
                    <a class="pngm-listing-btn is-renew" href="<?php echo osc_esc_html(function_exists('pngm_item_renew_url') ? pngm_item_renew_url() : osc_item_renew_url($secret, osc_item_id())); ?>"><i class="fas fa-sync-alt" aria-hidden="true"></i> <?php _e('Renew', 'epsilon'); ?></a>
                  <?php } ?>

                  <a class="pngm-listing-btn" href="<?php echo osc_item_edit_url(); ?>" rel="nofollow"><i class="fas fa-pen" aria-hidden="true"></i> <?php _e('Edit', 'epsilon'); ?></a>

                  <?php if ($status['key'] === 'active' && osc_can_deactivate_items()) { ?>
                    <a class="pngm-listing-btn" href="<?php echo osc_item_deactivate_url($secret, osc_item_id()); ?>"><i class="fas fa-pause" aria-hidden="true"></i> <?php _e('Deactivate', 'epsilon'); ?></a>
                  <?php } ?>

                  <?php if ($status['key'] === 'inactive') { ?>
                    <?php if ((function_exists('iv_add_item') && osc_get_preference('enable', 'plugin-item_validation') <> 1) || !function_exists('iv_add_item')) { ?>
                      <a class="pngm-listing-btn is-activate" href="<?php echo osc_item_activate_url($secret, osc_item_id()); ?>"><i class="fas fa-play" aria-hidden="true"></i> <?php _e('Activate', 'epsilon'); ?></a>
                    <?php } ?>
                  <?php } ?>

                  <a class="pngm-listing-btn is-preview" href="<?php echo osc_item_url(); ?>" target="_blank" rel="noopener"><i class="far fa-eye" aria-hidden="true"></i> <?php _e('Preview', 'epsilon'); ?></a>

                  <?php if ($status['key'] === 'pending' || $status['key'] === 'expired') { ?>
                    <a class="pngm-listing-btn is-danger" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to delete this listing? This action cannot be undone.', 'epsilon')); ?>')" href="<?php echo osc_item_delete_url(); ?>"><?php _e('Delete', 'epsilon'); ?></a>
                  <?php } ?>

                  <div class="pngm-listing-more">
                    <button type="button" class="pngm-listing-more-btn" aria-expanded="false" aria-haspopup="true" title="<?php echo osc_esc_html(__('More actions', 'epsilon')); ?>">
                      <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                    </button>
                    <div class="pngm-listing-more-menu" hidden>
                      <?php if ($status['key'] === 'pending') { ?>
                        <?php if ((function_exists('iv_add_item') && osc_get_preference('enable', 'plugin-item_validation') <> 1) || !function_exists('iv_add_item')) { ?>
                          <a href="<?php echo osc_item_activate_url($secret, osc_item_id()); ?>"><?php _e('Validate', 'epsilon'); ?></a>
                        <?php } ?>
                      <?php } ?>
                      <?php if ($status['key'] === 'active' && !in_array(osc_item_category_id(), eps_extra_fields_hide())) { ?>
                        <a href="<?php echo eps_item_sold_reserved_url('sold', $item_extra); ?>"><?php echo (@$item_extra['i_sold'] == 1 ? __('Unmark sold', 'epsilon') : __('Mark sold', 'epsilon')); ?></a>
                        <a href="<?php echo eps_item_sold_reserved_url('reserved', $item_extra); ?>"><?php echo (@$item_extra['i_sold'] == 2 ? __('Unmark reserved', 'epsilon') : __('Mark reserved', 'epsilon')); ?></a>
                      <?php } ?>
                      <?php if (function_exists('republish_link_raw') && republish_link_raw(osc_item_id())) { ?>
                        <a href="<?php echo republish_link_raw(osc_item_id()); ?>"><?php _e('Republish', 'epsilon'); ?></a>
                      <?php } ?>
                      <?php if ($status['key'] === 'active' || $status['key'] === 'inactive') { ?>
                        <a class="is-danger" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to delete this listing? This action cannot be undone.', 'epsilon')); ?>')" href="<?php echo osc_item_delete_url(); ?>"><?php _e('Delete', 'epsilon'); ?></a>
                      <?php } ?>
                      <?php osc_run_hook('user_items_action', osc_item_id()); ?>
                    </div>
                  </div>
                </div>
              </div>
            </article>
          <?php } ?>

          <div class="paginate pngm-listings-paginate">
            <?php echo eps_fix_arrow(osc_pagination_items()); ?>
          </div>
        <?php } else { ?>
          <div class="pngm-listings-empty">
            <?php if ($current_type === 'expired') { ?>
              <p><strong><?php _e('No expired listings', 'epsilon'); ?></strong></p>
              <p><?php _e('When a listing reaches 30 days, it becomes inactive here (not deleted) so you can renew it.', 'epsilon'); ?></p>
              <a class="pngm-ua-btn" href="<?php echo osc_esc_html(pngm_ua_items_url('active')); ?>"><?php _e('View active listings', 'epsilon'); ?></a>
            <?php } else { ?>
              <p><strong><?php _e('No listings found', 'epsilon'); ?></strong></p>
              <p><?php _e('You don’t have any listings in this category or filter.', 'epsilon'); ?></p>
              <a class="pngm-ua-btn" href="<?php echo osc_item_post_url(); ?>"><i class="fas fa-plus" aria-hidden="true"></i> <?php _e('Place an ad', 'epsilon'); ?></a>
            <?php } ?>
          </div>
        <?php } ?>
      </div>

      <?php osc_run_hook('user_items_bottom'); ?>
    </div>
  </div>

  <script>
  (function () {
    var sort = document.getElementById('pngm_sort');
    var order = document.getElementById('sOrder');
    var orderType = document.getElementById('sOrderType');
    if (sort && order && orderType) {
      sort.addEventListener('change', function () {
        var parts = String(sort.value || '').split('|');
        order.value = parts[0] || 'dt_pub_date';
        orderType.value = parts[1] || 'DESC';
        var form = sort.closest('form');
        if (form) form.submit();
      });
    }

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.pngm-listing-more-btn');
      var menus = document.querySelectorAll('.pngm-listing-more-menu');
      if (btn) {
        e.preventDefault();
        var wrap = btn.closest('.pngm-listing-more');
        var menu = wrap ? wrap.querySelector('.pngm-listing-more-menu') : null;
        var open = menu && !menu.hasAttribute('hidden');
        menus.forEach(function (m) { m.setAttribute('hidden', 'hidden'); });
        document.querySelectorAll('.pngm-listing-more-btn').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
        if (menu && !open) {
          menu.removeAttribute('hidden');
          btn.setAttribute('aria-expanded', 'true');
        }
        return;
      }
      if (!e.target.closest('.pngm-listing-more')) {
        menus.forEach(function (m) { m.setAttribute('hidden', 'hidden'); });
        document.querySelectorAll('.pngm-listing-more-btn').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
      }
    });
  })();
  </script>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
