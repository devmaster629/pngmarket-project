<?php
/**
 * Account Messages inbox — matches My Listings management UI.
 * Included from instant_messenger/user/threads.php when present.
 *
 * Expects: $threads, $threads_count, $page_id, $per_page, $page_count
 */

if (!defined('ABS_PATH')) {
    exit;
}

if (!function_exists('pngm_ua_user_initials')) {
    require_once dirname(__FILE__) . '/account_ua.php';
}

$user_id = (int) osc_logged_user_id();
$filter = Params::getParam('pngm_im');
if ($filter === '') {
    $filter = 'all';
}
$allowed_filters = array('all', 'unread', 'flagged', 'offers');
if (!in_array($filter, $allowed_filters, true)) {
    $filter = 'all';
}
$pattern = trim((string) Params::getParam('pngm_im_q'));
if ($pattern === '') {
    // legacy fallback if an old link used sPattern on this page
    $legacy = trim((string) Params::getParam('sPattern'));
    // Only accept legacy when we are clearly on the IM route (avoid global search bleed)
    if ($legacy !== '' && (Params::getParam('route') === 'im-threads' || Params::getParam('route') === 'im-thread-page')) {
        $pattern = $legacy;
    }
}

// When filtering/searching, load a wider thread set so tabs are meaningful
if (($filter !== 'all' || $pattern !== '') && class_exists('ModelIM')) {
    $threads = ModelIM::newInstance()->getThreadsByUserId($user_id, 200, 0);
    if (!is_array($threads)) {
        $threads = array();
    }
}
$counts = array(
    'all' => (int) $threads_count,
    'unread' => 0,
    'flagged' => 0,
    'offers' => 0,
);

$prepared = array();
if (is_array($threads)) {
    foreach ($threads as $t) {
        if (!is_array($t) || empty($t['i_thread_id'])) {
            continue;
        }

        $item = osc_get_item_row($t['fk_i_item_id']);
        if ($item === false || !isset($item['pk_i_id'])) {
            $item = array();
        }

        $time_diff = function_exists('im_get_time_diff') ? im_get_time_diff($t['d_datetime']) : '';
        $is_read_row = ModelIM::newInstance()->getThreadIsRead($t['i_thread_id'], $user_id);
        $is_read = (is_array($is_read_row) && isset($is_read_row['i_read'])) ? (int) $is_read_row['i_read'] : 1;
        $unread = ($is_read === 0);

        if ((int) $t['i_from_user_id'] === $user_id) {
            $notify = (int) $t['i_from_user_notify'];
            $u_id = (int) $t['i_to_user_id'];
            $u_name = (string) $t['s_to_user_name'];
            $u_mail = (string) $t['s_to_user_email'];
            $direction = 'to';
        } else {
            $notify = (int) $t['i_to_user_notify'];
            $u_id = (int) $t['i_from_user_id'];
            $u_name = (string) $t['s_from_user_name'];
            $u_mail = (string) $t['s_from_user_email'];
            $direction = 'from';
        }
        if ($u_name === '') {
            $u_name = __('User', 'epsilon');
        }

        $img = function_exists('im_profile_img_url') ? im_profile_img_url($u_id, $u_name) : '';
        $offer = function_exists('im_get_offer') ? im_get_offer($t['i_offer_id']) : false;
        $flagged = ((int) $t['i_flag'] === 1);

        if ($unread) {
            $counts['unread']++;
        }
        if ($flagged) {
            $counts['flagged']++;
        }
        if ($offer) {
            $counts['offers']++;
        }

        if ($offer) {
            $currency = !empty($item['fk_c_currency_code'])
                ? Currency::newInstance()->findByPrimaryKey($item['fk_c_currency_code'])
                : array();
            $t_title = sprintf(
                __('New offer on %s - %s', 'instant_messenger'),
                osc_highlight(@$item['s_title'], 50),
                @$offer['i_price'] / 1000000 . @$currency['s_description']
            );
        } elseif (!empty($t['s_title'])) {
            $t_title = osc_highlight($t['s_title'], 60);
        } else {
            $t_title = __('No subject', 'instant_messenger');
        }

        $listing_title = '';
        $listing_label = '';
        $listing_url = '';
        $listing_cat = '';
        $listing_loc = '';
        $listing_price = '';
        $thumb = '';
        $photo_count = 0;
        $has_listing = !empty($item['pk_i_id']);

        if ($has_listing) {
            View::newInstance()->_erase('resources');
            View::newInstance()->_exportVariableToView('item', $item);

            $listing_title = trim((string) osc_item_title());
            if ($listing_title === '' && !empty($item['s_title'])) {
                $listing_title = (string) $item['s_title'];
            }
            $listing_label = $listing_title;
            $listing_url = osc_item_url();
            $listing_cat = function_exists('pngm_ua_item_category_path') ? pngm_ua_item_category_path() : (string) osc_item_category();
            $listing_loc = function_exists('pngm_ua_item_location_short') ? pngm_ua_item_location_short() : trim(osc_item_city() . (osc_item_region() ? ', ' . osc_item_region() : ''));

            if (function_exists('eps_check_category_price') && eps_check_category_price(osc_item_category_id())) {
                $listing_price = function_exists('pngm_format_price')
                    ? (string) pngm_format_price()
                    : (string) osc_item_formated_price();
            }

            $resources = ItemResource::newInstance()->getAllResourcesFromItem((int) $item['pk_i_id']);
            if (is_array($resources) && !empty($resources)) {
                $photo_count = count($resources);
                View::newInstance()->_exportVariableToView('resources', $resources);
                osc_reset_resources();
                if (osc_has_item_resources()) {
                    $thumb = (string) osc_resource_thumbnail_url();
                    if ($thumb === '') {
                        $thumb = (string) osc_resource_preview_url();
                    }
                    if ($thumb === '') {
                        $thumb = (string) osc_resource_url();
                    }
                }
            }
            if ($thumb === '' && function_exists('eps_get_noimage')) {
                $thumb = eps_get_noimage();
            }

            View::newInstance()->_erase('resources');
            View::newInstance()->_erase('item');
        } elseif ((int) $t['fk_i_item_id'] > 0) {
            $listing_title = __('Listing removed', 'instant_messenger');
            $listing_label = $listing_title;
            if (function_exists('eps_get_noimage')) {
                $thumb = eps_get_noimage();
            }
        } else {
            $listing_title = $t_title !== '' ? $t_title : __('Direct message', 'instant_messenger');
            $listing_label = __('Direct message', 'instant_messenger');
        }

        // Card title prefers listing name (My Listings style), not "Question on" / "Inquiry" prefixes
        $card_title = $listing_title !== '' ? $listing_title : $t_title;
        $last = ModelIM::newInstance()->getLastMessageByThreadId((int) $t['i_thread_id']);
        $snippet = '';
        if (is_array($last) && !empty($last['s_message'])) {
            $snippet = strip_tags((string) $last['s_message']);
            if (function_exists('mb_substr')) {
                $snippet = mb_substr($snippet, 0, 90);
            } else {
                $snippet = substr($snippet, 0, 90);
            }
        }

        $check_block = ModelIM::newInstance()->checkUserBlocks($user_id, $u_mail);
        $is_blocked = (isset($check_block['i_user_id']) && (int) $check_block['i_user_id'] === $user_id);
        $ban_url = osc_route_url('im-ban', array('action' => 'block_email', 'block-email' => base64_encode($u_mail)));
        $thread_url = osc_route_url('im-messages', array('thread-id' => $t['i_thread_id'], 'secret' => 'n'));

        $hay = strtolower($card_title . ' ' . $t_title . ' ' . $u_name . ' ' . $listing_label . ' ' . $listing_cat . ' ' . $listing_loc . ' ' . $snippet);
        $match_pattern = ($pattern === '' || strpos($hay, strtolower($pattern)) !== false);

        $match_filter = true;
        if ($filter === 'unread' && !$unread) {
            $match_filter = false;
        } elseif ($filter === 'flagged' && !$flagged) {
            $match_filter = false;
        } elseif ($filter === 'offers' && !$offer) {
            $match_filter = false;
        }

        if (!$match_filter || !$match_pattern) {
            continue;
        }

        $status_key = $unread ? 'unread' : ($flagged ? 'flagged' : 'read');
        $status_label = $unread
            ? __('Unread', 'epsilon')
            : ($flagged ? __('Flagged', 'epsilon') : __('Read', 'epsilon'));

        $prepared[] = array(
            'thread' => $t,
            'title' => $card_title,
            'thread_subject' => $t_title,
            'u_id' => $u_id,
            'u_name' => $u_name,
            'img' => $img,
            'thumb' => $thumb,
            'photo_count' => $photo_count,
            'has_listing' => $has_listing,
            'offer' => $offer,
            'unread' => $unread,
            'flagged' => $flagged,
            'notify' => $notify,
            'direction' => $direction,
            'listing_label' => $listing_label,
            'listing_url' => $listing_url,
            'listing_cat' => $listing_cat,
            'listing_loc' => $listing_loc,
            'listing_price' => $listing_price,
            'snippet' => $snippet,
            'time_diff' => $time_diff,
            'pm_count' => (int) $t['i_count'],
            'thread_url' => $thread_url,
            'ban_url' => $ban_url,
            'is_blocked' => $is_blocked,
            'status_key' => $status_key,
            'status_label' => $status_label,
            'initials' => pngm_ua_user_initials($u_name),
        );
    }
}

$tabs = array(
    'all' => array('label' => __('All messages', 'epsilon'), 'count' => $counts['all']),
    'unread' => array('label' => __('Unread', 'epsilon'), 'count' => $counts['unread']),
    'flagged' => array('label' => __('Flagged', 'epsilon'), 'count' => $counts['flagged']),
    'offers' => array('label' => __('Offers', 'epsilon'), 'count' => $counts['offers']),
);

if (!function_exists('pngm_im_tab_url')) {
    function pngm_im_tab_url($key, $query = '')
    {
        $parts = array(
            'page' => 'custom',
            'route' => 'im-threads',
        );
        if ($key !== '' && $key !== 'all') {
            $parts['pngm_im'] = $key;
        }
        $query = trim((string) $query);
        if ($query !== '') {
            $parts['pngm_im_q'] = $query;
        }
        return osc_base_url(true) . '?' . http_build_query($parts);
    }
}
?>

<link href="<?php echo osc_base_url(); ?>oc-content/plugins/instant_messenger/css/tipped.css" rel="stylesheet" type="text/css" />
<script src="<?php echo osc_base_url(); ?>oc-content/plugins/instant_messenger/js/tipped.js"></script>
<script src="<?php echo osc_base_url(); ?>oc-content/plugins/instant_messenger/js/user.js?v=<?php echo date('Ymdhis'); ?>"></script>

<div class="im-html im-file-threads im-theme-<?php echo osc_esc_html(osc_current_web_theme()); ?> pngm-messages">
  <div class="pngm-listings-head pngm-messages-head">
    <div class="pngm-listings-head-text">
      <h1><?php _e('Messages — Conversations', 'epsilon'); ?></h1>
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

  <?php if (im_param('limit_enabled') == 1) {
      $limit_check = im_check_user_limits($user_id, osc_logged_user_email(), true);
      if ($limit_check !== true) { ?>
        <div class="im-limits-info pngm-messages-limits"><?php echo $limit_check; ?></div>
      <?php }
  } ?>

  <nav class="pngm-listings-tabs pngm-messages-tabs" aria-label="<?php echo osc_esc_html(__('Message filters', 'epsilon')); ?>">
    <?php foreach ($tabs as $key => $tab) {
        $is_active = ($filter === $key);
        ?>
      <a class="pngm-listings-tab<?php echo $is_active ? ' is-active' : ''; ?>" href="<?php echo osc_esc_html(pngm_im_tab_url($key, $pattern)); ?>">
        <span class="pngm-listings-tab-label"><?php echo osc_esc_html($tab['label']); ?></span>
        <em class="pngm-listings-tab-count"><?php echo (int) $tab['count']; ?></em>
      </a>
    <?php } ?>
  </nav>

  <form class="pngm-listings-toolbar pngm-messages-toolbar nocsrf" action="<?php echo osc_esc_html(osc_base_url(true)); ?>" method="get" role="search">
    <input type="hidden" name="page" value="custom" />
    <input type="hidden" name="route" value="im-threads" />
    <?php if ($filter !== 'all') { ?>
      <input type="hidden" name="pngm_im" value="<?php echo osc_esc_html($filter); ?>" />
    <?php } ?>
    <div class="pngm-listings-search">
      <i class="fas fa-search" aria-hidden="true"></i>
      <input type="search" name="pngm_im_q" value="<?php echo osc_esc_html($pattern); ?>" placeholder="<?php echo osc_esc_html(__('Search conversations…', 'epsilon')); ?>" autocomplete="off" />
    </div>
    <div class="pngm-listings-filters">
      <button type="submit" class="pngm-listings-filter-btn" title="<?php echo osc_esc_html(__('Search', 'epsilon')); ?>">
        <i class="fas fa-filter" aria-hidden="true"></i>
      </button>
    </div>
  </form>

  <div class="pngm-listings-list pngm-messages-list">
    <?php if (count($prepared) > 0) { ?>
      <?php foreach ($prepared as $row) {
          $t = $row['thread'];
          $thread_id = (int) $t['i_thread_id'];
          ?>
        <article class="pngm-listing-card pngm-message-card status-<?php echo osc_esc_html($row['status_key']); ?><?php echo $row['unread'] ? ' is-unread' : ''; ?><?php echo $row['offer'] ? ' is-offer' : ''; ?>">
          <a class="pngm-listing-thumb" href="<?php echo osc_esc_html($row['thread_url']); ?>">
            <?php if ($row['thumb'] !== '') { ?>
              <img src="<?php echo osc_esc_html($row['thumb']); ?>" alt="" width="120" height="90" loading="lazy" />
            <?php } elseif ($row['offer']) { ?>
              <span class="pngm-message-av-fallback is-offer"><?php _e('Offer', 'epsilon'); ?></span>
            <?php } else { ?>
              <span class="pngm-message-av-fallback"><?php echo osc_esc_html($row['initials']); ?></span>
            <?php } ?>
            <?php if ((int) $row['photo_count'] > 0) { ?>
              <span class="pngm-listing-photo-count"><i class="fas fa-camera" aria-hidden="true"></i> <?php echo (int) $row['photo_count']; ?></span>
            <?php } elseif ((int) $row['pm_count'] > 0) { ?>
              <span class="pngm-listing-photo-count"><i class="far fa-comment" aria-hidden="true"></i> <?php echo (int) $row['pm_count']; ?></span>
            <?php } ?>
          </a>

          <div class="pngm-listing-body">
            <div class="pngm-listing-main">
              <h2 class="pngm-listing-title">
                <a href="<?php echo osc_esc_html($row['thread_url']); ?>"><?php echo osc_esc_html($row['title']); ?></a>
              </h2>
              <?php if ($row['listing_cat'] !== '') { ?>
                <p class="pngm-listing-cat"><?php echo osc_esc_html($row['listing_cat']); ?></p>
              <?php } else { ?>
                <p class="pngm-listing-cat">
                  <?php
                    echo ($row['direction'] === 'to')
                      ? sprintf(__('To %s', 'epsilon'), osc_esc_html($row['u_name']))
                      : sprintf(__('From %s', 'epsilon'), osc_esc_html($row['u_name']));
                  ?>
                </p>
              <?php } ?>
              <?php if ($row['listing_loc'] !== '') { ?>
                <p class="pngm-listing-loc"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php echo osc_esc_html($row['listing_loc']); ?></p>
              <?php } elseif ($row['has_listing']) { ?>
                <p class="pngm-listing-loc">
                  <i class="fas fa-user" aria-hidden="true"></i>
                  <?php
                    echo ($row['direction'] === 'to')
                      ? sprintf(__('To %s', 'epsilon'), osc_esc_html($row['u_name']))
                      : sprintf(__('From %s', 'epsilon'), osc_esc_html($row['u_name']));
                  ?>
                </p>
              <?php } ?>
              <?php if ($row['listing_price'] !== '') { ?>
                <p class="pngm-listing-price"><?php echo osc_esc_html($row['listing_price']); ?></p>
              <?php } elseif ($row['snippet'] !== '') { ?>
                <p class="pngm-listing-price pngm-message-snippet"><?php echo osc_esc_html($row['snippet']); ?></p>
              <?php } ?>
            </div>

            <div class="pngm-listing-meta">
              <span class="pngm-listing-badge is-<?php echo osc_esc_html($row['status_key']); ?>"><?php echo osc_esc_html($row['status_label']); ?></span>
              <span class="pngm-listing-views"><i class="far fa-comment-dots" aria-hidden="true"></i> <?php echo sprintf(__('%d messages', 'epsilon'), (int) $row['pm_count']); ?></span>
              <?php if ($row['time_diff'] !== '') { ?>
                <time class="pngm-listing-date"><?php echo osc_esc_html($row['time_diff']); ?></time>
              <?php } ?>
            </div>

            <div class="pngm-listing-actions">
              <a class="pngm-listing-btn is-preview" href="<?php echo osc_esc_html($row['thread_url']); ?>">
                <i class="far fa-envelope-open" aria-hidden="true"></i> <?php _e('Open', 'epsilon'); ?>
              </a>
              <a class="pngm-listing-btn" href="<?php echo osc_esc_html(osc_route_url('im-thread-flag', array('thread-flag-id' => $thread_id))); ?>">
                <i class="<?php echo $row['flagged'] ? 'fas' : 'far'; ?> fa-flag" aria-hidden="true"></i>
                <?php echo $row['flagged'] ? __('Unflag', 'epsilon') : __('Flag', 'epsilon'); ?>
              </a>
              <a class="pngm-listing-btn" href="<?php echo osc_esc_html(osc_route_url('im-thread-notify', array('thread-notify-id' => $thread_id))); ?>">
                <i class="fas <?php echo $row['notify'] ? 'fa-bell' : 'fa-bell-slash'; ?>" aria-hidden="true"></i>
                <?php echo $row['notify'] ? __('Mute', 'epsilon') : __('Notify', 'epsilon'); ?>
              </a>

              <div class="pngm-listing-more">
                <button type="button" class="pngm-listing-more-btn" aria-expanded="false" aria-haspopup="true" title="<?php echo osc_esc_html(__('More actions', 'epsilon')); ?>">
                  <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                </button>
                <div class="pngm-listing-more-menu" hidden>
                  <?php if ($row['listing_url'] !== '') { ?>
                    <a href="<?php echo osc_esc_html($row['listing_url']); ?>" target="_blank" rel="noopener"><?php _e('View listing', 'epsilon'); ?></a>
                  <?php } ?>
                  <?php if (!$row['is_blocked']) { ?>
                    <a href="<?php echo osc_esc_html($row['ban_url']); ?>"><?php _e('Block user', 'epsilon'); ?></a>
                  <?php } else { ?>
                    <span><?php _e('User blocked', 'epsilon'); ?></span>
                  <?php } ?>
                  <?php if (im_param('remove_thread') == 1) { ?>
                    <a class="is-danger" href="<?php echo osc_esc_html(osc_route_url('im-thread-remove', array('thread-remove-id' => $thread_id, 'secret' => 'n'))); ?>" onclick="return confirm('<?php echo osc_esc_js(__('Remove this conversation?', 'epsilon')); ?>');"><?php _e('Delete', 'epsilon'); ?></a>
                  <?php } ?>
                </div>
              </div>
            </div>
          </div>
        </article>
      <?php } ?>

      <?php if (function_exists('im_paginate')) {
          echo im_paginate($page_id, $per_page, $threads_count);
      } ?>
    <?php } else { ?>
      <div class="pngm-listings-empty pngm-messages-empty">
        <i class="fas fa-comments" aria-hidden="true"></i>
        <p><?php _e('No conversations found', 'epsilon'); ?></p>
        <a class="pngm-ua-btn" href="<?php echo osc_search_url(array()); ?>"><?php _e('Browse listings', 'epsilon'); ?></a>
      </div>
    <?php } ?>
  </div>

  <div class="pngm-messages-blocked">
    <?php
      $im_block = osc_plugins_path() . 'instant_messenger/user/block.php';
      if (file_exists($im_block)) {
          require $im_block;
      }
    ?>
  </div>
</div>

<script>
(function () {
  document.querySelectorAll('.pngm-message-card .pngm-listing-more-btn').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var menu = btn.parentNode.querySelector('.pngm-listing-more-menu');
      var open = menu && !menu.hidden;
      document.querySelectorAll('.pngm-listing-more-menu').forEach(function (m) { m.hidden = true; });
      document.querySelectorAll('.pngm-listing-more-btn').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
      if (menu && !open) {
        menu.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
      }
    });
  });
  document.addEventListener('click', function () {
    document.querySelectorAll('.pngm-listing-more-menu').forEach(function (m) { m.hidden = true; });
  });
})();
</script>
