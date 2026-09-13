<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>
<body id="user-dashboard" class="body-ua pngm-ua pngm-ua-dashboard">
  <?php osc_current_web_theme_path('header.php') ; ?>

  <?php
    if (!function_exists('pngm_ua_render_sidebar')) {
      require_once dirname(__FILE__) . '/includes/account_ua.php';
    }

    $user_id = osc_logged_user_id();
    $user = User::newInstance()->findByPrimaryKey($user_id);
    $name = osc_logged_user_name();

    $count_active = eps_count_user_items($user_id, 'active');
    $count_messages = function_exists('eps_count_messages') ? eps_count_messages($user_id) : 0;
    $count_favorite = function_exists('eps_count_favorite') ? eps_count_favorite($user_id) : 0;
    $count_views = pngm_ua_profile_views($user_id);
    $completion = pngm_ua_profile_completion($user);
    $recent_messages = pngm_ua_recent_messages($user_id, 4);
    $recent_activity = pngm_ua_recent_activity($user_id, 4);

    $pct = (int) $completion['percent'];
    $ring = 2 * M_PI * 42;
    $dash = $ring * ($pct / 100);
  ?>

  <div class="container primary pngm-ua-shell">
    <?php pngm_ua_render_sidebar('dashboard'); ?>

    <div id="user-main" class="pngm-ua-main">
      <div class="pngm-ua-top">
        <div class="pngm-ua-welcome">
          <h1><?php echo sprintf(__('Welcome back, %s', 'epsilon'), osc_esc_html($name)); ?></h1>
          <p><?php _e('Here\'s what\'s happening with your account today.', 'epsilon'); ?></p>
        </div>
        <div class="pngm-ua-userchip">
          <a href="<?php echo osc_user_profile_url(); ?>" class="pngm-ua-userchip-link">
            <img src="<?php echo eps_profile_picture($user_id, 'medium'); ?>" alt="" width="48" height="48" />
            <span>
              <strong><?php echo sprintf(__('Hi, %s', 'epsilon'), osc_esc_html($name)); ?></strong>
              <small><?php _e('My Account', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></small>
            </span>
          </a>
        </div>
      </div>

      <?php osc_run_hook('user_dashboard_top'); ?>

      <div class="pngm-ua-stats">
        <article class="pngm-ua-stat">
          <div class="pngm-ua-stat-ico is-green"><i class="fas fa-box" aria-hidden="true"></i></div>
          <div class="pngm-ua-stat-body">
            <span class="pngm-ua-stat-label"><?php _e('Active Listings', 'epsilon'); ?></span>
            <strong class="pngm-ua-stat-num"><?php echo (int) $count_active; ?></strong>
            <a href="<?php echo eps_user_items_url('active'); ?>"><?php _e('View all listings', 'epsilon'); ?></a>
          </div>
        </article>

        <article class="pngm-ua-stat">
          <div class="pngm-ua-stat-ico is-blue"><i class="fas fa-comment-dots" aria-hidden="true"></i></div>
          <div class="pngm-ua-stat-body">
            <span class="pngm-ua-stat-label"><?php _e('Messages', 'epsilon'); ?></span>
            <strong class="pngm-ua-stat-num"><?php echo (int) $count_messages; ?></strong>
            <?php if (function_exists('im_messages')) { ?>
              <a href="<?php echo osc_route_url('im-threads'); ?>"><?php _e('View messages', 'epsilon'); ?></a>
            <?php } else { ?>
              <span class="pngm-ua-stat-muted"><?php _e('Inbox', 'epsilon'); ?></span>
            <?php } ?>
          </div>
        </article>

        <article class="pngm-ua-stat">
          <div class="pngm-ua-stat-ico is-yellow"><i class="fas fa-heart" aria-hidden="true"></i></div>
          <div class="pngm-ua-stat-body">
            <span class="pngm-ua-stat-label"><?php _e('Favorites', 'epsilon'); ?></span>
            <strong class="pngm-ua-stat-num"><?php echo (int) $count_favorite; ?></strong>
            <?php if (function_exists('fi_make_favorite')) { ?>
              <a href="<?php echo osc_route_url('favorite-lists'); ?>"><?php _e('View favorites', 'epsilon'); ?></a>
            <?php } else { ?>
              <span class="pngm-ua-stat-muted"><?php _e('Saved items', 'epsilon'); ?></span>
            <?php } ?>
          </div>
        </article>

        <article class="pngm-ua-stat">
          <div class="pngm-ua-stat-ico is-purple"><i class="fas fa-eye" aria-hidden="true"></i></div>
          <div class="pngm-ua-stat-body">
            <span class="pngm-ua-stat-label"><?php _e('Profile Views', 'epsilon'); ?></span>
            <strong class="pngm-ua-stat-num"><?php echo (int) $count_views; ?></strong>
            <span class="pngm-ua-stat-muted"><?php _e('Last 30 days', 'epsilon'); ?></span>
          </div>
        </article>
      </div>

      <div class="pngm-ua-mid">
        <section class="pngm-ua-card pngm-ua-complete">
          <div class="pngm-ua-complete-ring" aria-hidden="true">
            <svg viewBox="0 0 100 100" width="110" height="110">
              <circle cx="50" cy="50" r="42" class="pngm-ua-ring-bg"></circle>
              <circle cx="50" cy="50" r="42" class="pngm-ua-ring-fg" style="stroke-dasharray: <?php echo $dash . ' ' . $ring; ?>;"></circle>
            </svg>
            <span><?php echo $pct; ?>%</span>
          </div>
          <div class="pngm-ua-complete-copy">
            <h2><?php _e('Profile completion', 'epsilon'); ?></h2>
            <?php if ($pct >= 100) { ?>
              <p><?php _e('Your profile looks complete — great job building buyer trust.', 'epsilon'); ?></p>
            <?php } else { ?>
              <p><?php _e('You\'re doing great! Complete your profile to build trust with buyers.', 'epsilon'); ?></p>
            <?php } ?>
            <a class="pngm-ua-btn" href="<?php echo osc_user_profile_url(); ?>"><?php _e('Complete Profile', 'epsilon'); ?></a>
          </div>
        </section>

        <section class="pngm-ua-card pngm-ua-messages">
          <div class="pngm-ua-card-head">
            <h2><?php _e('Recent messages', 'epsilon'); ?></h2>
            <?php if (function_exists('im_messages')) { ?>
              <a href="<?php echo osc_route_url('im-threads'); ?>"><?php _e('View all', 'epsilon'); ?></a>
            <?php } ?>
          </div>
          <?php if (empty($recent_messages)) { ?>
            <p class="pngm-ua-empty"><?php _e('No messages yet. When buyers contact you, they will show up here.', 'epsilon'); ?></p>
          <?php } else { ?>
            <ul class="pngm-ua-msg-list">
              <?php foreach ($recent_messages as $m) { ?>
                <li>
                  <a href="<?php echo osc_esc_html($m['url']); ?>" class="pngm-ua-msg-row">
                    <span class="pngm-ua-msg-av"><?php echo osc_esc_html($m['initials']); ?></span>
                    <span class="pngm-ua-msg-meta">
                      <strong><?php echo osc_esc_html($m['name']); ?></strong>
                      <em><?php echo osc_esc_html($m['snippet']); ?></em>
                    </span>
                    <span class="pngm-ua-msg-aside">
                      <time><?php echo osc_esc_html($m['time']); ?></time>
                      <?php if (!empty($m['unread'])) { ?><i class="pngm-ua-badge"><?php echo (int) $m['unread']; ?></i><?php } ?>
                    </span>
                  </a>
                </li>
              <?php } ?>
            </ul>
          <?php } ?>
        </section>
      </div>

      <div class="pngm-ua-bottom">
        <section class="pngm-ua-card pngm-ua-activity">
          <div class="pngm-ua-card-head">
            <h2><?php _e('Recent activity', 'epsilon'); ?></h2>
            <a href="<?php echo eps_user_items_url('all'); ?>"><?php _e('View all', 'epsilon'); ?></a>
          </div>
          <?php if (empty($recent_activity)) { ?>
            <p class="pngm-ua-empty"><?php _e('No recent activity yet. Post a listing to get started.', 'epsilon'); ?></p>
          <?php } else { ?>
            <ul class="pngm-ua-act-list">
              <?php foreach ($recent_activity as $a) { ?>
                <li>
                  <a href="<?php echo osc_esc_html($a['url']); ?>" class="pngm-ua-act-row">
                    <span class="pngm-ua-act-ico"><i class="fas fa-<?php echo osc_esc_html($a['icon']); ?>" aria-hidden="true"></i></span>
                    <?php if ($a['thumb'] !== '') { ?>
                      <img class="pngm-ua-act-thumb" src="<?php echo osc_esc_html($a['thumb']); ?>" alt="" width="44" height="44" />
                    <?php } else { ?>
                      <span class="pngm-ua-act-thumb is-empty" aria-hidden="true"></span>
                    <?php } ?>
                    <span class="pngm-ua-act-meta">
                      <strong><?php echo osc_esc_html($a['label']); ?></strong>
                      <em><?php echo osc_esc_html($a['title']); ?></em>
                    </span>
                    <time><?php echo osc_esc_html($a['time']); ?></time>
                  </a>
                </li>
              <?php } ?>
            </ul>
          <?php } ?>
        </section>

        <section class="pngm-ua-actions">
          <div class="pngm-ua-action-card">
            <div class="pngm-ua-action-ico"><i class="fas fa-plus" aria-hidden="true"></i></div>
            <h3><?php _e('Post a Listing', 'epsilon'); ?></h3>
            <p><?php _e('Reach more buyers across Papua New Guinea with a clear, photo-rich ad.', 'epsilon'); ?></p>
            <a class="pngm-ua-btn" href="<?php echo osc_item_post_url(); ?>"><?php _e('Post a Listing', 'epsilon'); ?></a>
          </div>
          <div class="pngm-ua-action-card">
            <div class="pngm-ua-action-ico is-outline"><i class="fas fa-list-ul" aria-hidden="true"></i></div>
            <h3><?php _e('Manage Listings', 'epsilon'); ?></h3>
            <p><?php _e('Edit, renew, or promote your active and expired listings in one place.', 'epsilon'); ?></p>
            <a class="pngm-ua-btn is-ghost" href="<?php echo eps_user_items_url('all'); ?>"><?php _e('Manage Listings', 'epsilon'); ?></a>
          </div>
        </section>
      </div>

      <?php osc_run_hook('user_dashboard_bottom'); ?>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>
