<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>
<body id="user-dashboard" class="body-ua pngm-ua">
  <?php osc_current_web_theme_path('header.php') ; ?>

  <div class="container primary">
    <div id="user-menu" class="pngm-user-menu"><?php eps_user_menu(); ?></div>

    <?php
      $user_id = osc_logged_user_id();
      $user = User::newInstance()->findByPrimaryKey($user_id);

      $count_active = eps_count_user_items($user_id, 'active');
      $count_pending = eps_count_user_items($user_id, 'pending_validate');
      $count_expired = eps_count_user_items($user_id, 'expired');
      $count_alerts = count(Alerts::newInstance()->findByUser($user_id));
      $count_messages = function_exists('eps_count_messages') ? eps_count_messages($user_id) : 0;
      $count_favorite = function_exists('eps_count_favorite') ? eps_count_favorite($user_id) : 0;

      $profile_gaps = 0;
      if ($user['s_phone_land'] == '' && $user['s_phone_mobile'] == '') { $profile_gaps++; }
      if ($user['s_website'] == '') { $profile_gaps++; }
      if ($user['s_country'] == '' && $user['s_region'] == '' && $user['s_city'] == '') { $profile_gaps++; }
      if ($user['s_address'] == '' && $user['s_zip'] == '') { $profile_gaps++; }
    ?>

    <div id="user-main">
      <div class="headers pngm-ua-header">
        <a href="<?php echo osc_user_profile_url(); ?>" class="img-container" title="<?php echo osc_esc_html(__('Upload profile picture', 'epsilon')); ?>">
          <img src="<?php echo eps_profile_picture($user_id, 'medium'); ?>" alt="<?php echo osc_esc_html(osc_logged_user_name()); ?>" width="36" height="36"/>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="28" height="28"><path d="M256 408c-66.2 0-120-53.8-120-120s53.8-120 120-120 120 53.8 120 120-53.8 120-120 120zm0-192c-39.7 0-72 32.3-72 72s32.3 72 72 72 72-32.3 72-72-32.3-72-72-72zm-24 72c0-13.2 10.8-24 24-24 8.8 0 16-7.2 16-16s-7.2-16-16-16c-30.9 0-56 25.1-56 56 0 8.8 7.2 16 16 16s16-7.2 16-16zm110.7-145H464v288H48V143h121.3l24-64h125.5l23.9 64zM324.3 31h-131c-20 0-37.9 12.4-44.9 31.1L136 95H48c-26.5 0-48 21.5-48 48v288c0 26.5 21.5 48 48 48h416c26.5 0 48-21.5 48-48V143c0-26.5-21.5-48-48-48h-88l-14.3-38c-5.8-15.7-20.7-26-37.4-26z"/></svg>
        </a>

        <h1>
          <?php echo sprintf(__('Hi %s', 'epsilon'), osc_logged_user_name()); ?>
        </h1>
        <h2><?php _e('Manage your listings, messages, favourites and profile', 'epsilon'); ?></h2>
      </div>

      <?php osc_run_hook('user_dashboard_top'); ?>

      <div class="pngm-ua-quick">
        <a class="pngm-ua-chip" href="<?php echo eps_user_items_url('active'); ?>">
          <strong><?php echo (int) $count_active; ?></strong>
          <span><?php _e('Active listings', 'epsilon'); ?></span>
        </a>
        <?php if (function_exists('im_messages')) { ?>
          <a class="pngm-ua-chip messages" href="<?php echo osc_route_url('im-threads'); ?>">
            <strong><?php echo (int) $count_messages; ?></strong>
            <span><?php _e('Messages', 'epsilon'); ?></span>
          </a>
        <?php } ?>
        <?php if (function_exists('fi_make_favorite')) { ?>
          <a class="pngm-ua-chip favorite" href="<?php echo osc_route_url('favorite-lists'); ?>">
            <strong><?php echo (int) $count_favorite; ?></strong>
            <span><?php _e('Favourites', 'epsilon'); ?></span>
          </a>
        <?php } ?>
        <a class="pngm-ua-chip place" href="<?php echo osc_item_post_url(); ?>">
          <strong><i class="fas fa-plus"></i></strong>
          <span><?php _e('Place an ad', 'epsilon'); ?></span>
        </a>
      </div>

      <div class="pngm-ua-section">
        <h3 class="pngm-ua-section-title"><?php _e('Your listings', 'epsilon'); ?></h3>
        <div class="card-box">
          <a class="card active" href="<?php echo eps_user_items_url('active'); ?>">
            <div class="icon">
              <i class="fas fa-check-double"></i>
              <span class="count"><?php echo (int) $count_active; ?></span>
            </div>
            <div class="header"><?php _e('Active listings', 'epsilon'); ?></div>
            <div class="description"><?php _e('Visible listings customers can view and contact you about.', 'epsilon'); ?></div>
          </a>

          <a class="card not-validated" href="<?php echo eps_user_items_url('pending_validate'); ?>">
            <div class="icon">
              <i class="fas fa-history"></i>
              <span class="count"><?php echo (int) $count_pending; ?></span>
            </div>
            <div class="header"><?php _e('Pending validation', 'epsilon'); ?></div>
            <div class="description"><?php _e('Hidden until you or an admin validate them.', 'epsilon'); ?></div>
          </a>

          <a class="card expired" href="<?php echo eps_user_items_url('expired'); ?>">
            <div class="icon">
              <i class="fas fa-hourglass-end"></i>
              <span class="count"><?php echo (int) $count_expired; ?></span>
            </div>
            <div class="header"><?php _e('Expired listings', 'epsilon'); ?></div>
            <div class="description"><?php _e('No longer visible. Renew or recreate them.', 'epsilon'); ?></div>
          </a>
        </div>
      </div>

      <div class="pngm-ua-section">
        <h3 class="pngm-ua-section-title"><?php _e('Messages & saved', 'epsilon'); ?></h3>
        <div class="card-box">
          <?php if (function_exists('im_messages')) { ?>
            <a class="card messages" href="<?php echo osc_route_url('im-threads'); ?>">
              <div class="icon">
                <i class="fas fa-comments"></i>
                <span class="count"><?php echo (int) $count_messages; ?></span>
              </div>
              <div class="header"><?php _e('Messages', 'epsilon'); ?></div>
              <div class="description"><?php _e('PNGMarket messages with buyers and sellers.', 'epsilon'); ?></div>
            </a>
          <?php } ?>

          <?php if (function_exists('fi_make_favorite')) { ?>
            <a class="card favorite" href="<?php echo osc_route_url('favorite-lists'); ?>">
              <div class="icon">
                <i class="fas fa-star"></i>
                <span class="count"><?php echo (int) $count_favorite; ?></span>
              </div>
              <div class="header"><?php _e('Favourite listings', 'epsilon'); ?></div>
              <div class="description"><?php _e('Listings you saved to review later.', 'epsilon'); ?></div>
            </a>
          <?php } ?>

          <a class="card alerts" href="<?php echo osc_user_alerts_url(); ?>">
            <div class="icon">
              <i class="fas fa-bell"></i>
              <span class="count"><?php echo (int) $count_alerts; ?></span>
            </div>
            <div class="header"><?php _e('Subscriptions', 'epsilon'); ?></div>
            <div class="description"><?php _e('Search alerts you subscribe to for new matches.', 'epsilon'); ?></div>
          </a>
        </div>
      </div>

      <div class="pngm-ua-section">
        <h3 class="pngm-ua-section-title"><?php _e('Account', 'epsilon'); ?></h3>
        <div class="card-box">
          <?php if (function_exists('bpr_call_after_install') && (bpr_param('only_company_users') == 0 || (bpr_param('only_company_users') == 1 && $user['b_company'] == 1)) && bpr_company_url($user_id) !== false) { ?>
            <a class="card public" href="<?php echo bpr_company_url($user_id); ?>">
              <div class="icon"><i class="fas fa-briefcase"></i></div>
              <div class="header"><?php _e('Business profile', 'epsilon'); ?></div>
              <div class="description"><?php _e('Public company page with your info and listings.', 'epsilon'); ?></div>
            </a>
          <?php } else { ?>
            <a class="card public" href="<?php echo osc_user_public_profile_url($user_id); ?>">
              <div class="icon"><i class="far fa-address-card"></i></div>
              <div class="header"><?php _e('Public profile', 'epsilon'); ?></div>
              <div class="description"><?php _e('How buyers see your name, location and listings.', 'epsilon'); ?></div>
            </a>
          <?php } ?>

          <a class="card profile" href="<?php echo osc_user_profile_url(); ?>">
            <div class="icon">
              <i class="fas fa-user-edit"></i>
              <span class="count">
                <?php if ($profile_gaps == 0) { ?><i class="fas fa-check"></i><?php } else { ?><i class="fas fa-exclamation"></i><?php } ?>
              </span>
            </div>
            <div class="header"><?php _e('My profile', 'epsilon'); ?></div>
            <div class="description">
              <?php if ($profile_gaps == 0) { ?>
                <?php _e('Contact details, photo, location and account settings.', 'epsilon'); ?>
              <?php } else { ?>
                <?php echo sprintf(__('Profile incomplete — %d important field(s) still empty.', 'epsilon'), $profile_gaps); ?>
              <?php } ?>
            </div>
          </a>

          <?php if (function_exists('bpr_call_after_install') && (bpr_param('only_company_users') == 0 || (bpr_param('only_company_users') == 1 && $user['b_company'] == 1))) { ?>
            <a class="card business-profile" href="<?php echo osc_route_url('bpr-profile'); ?>">
              <div class="icon"><i class="fas fa-store"></i></div>
              <div class="header"><?php _e('My business profile', 'epsilon'); ?></div>
              <div class="description"><?php _e('Hours, payments, gallery and company details.', 'epsilon'); ?></div>
            </a>
          <?php } ?>

          <?php if (function_exists('osp_param')) { ?>
            <a class="card promote" href="<?php echo osc_route_url('osp-item'); ?>">
              <div class="icon"><i class="fas fa-bullhorn"></i></div>
              <div class="header"><?php _e('Promotions', 'epsilon'); ?></div>
              <div class="description"><?php _e('Boost listings, credits or memberships.', 'epsilon'); ?></div>
            </a>
          <?php } ?>

          <a class="card contact" href="<?php echo osc_contact_url(); ?>">
            <div class="icon"><i class="fas fa-headset"></i></div>
            <div class="header"><?php _e('Contact us', 'epsilon'); ?></div>
            <div class="description"><?php _e('Questions or help with your account.', 'epsilon'); ?></div>
          </a>

          <?php osc_run_hook('user_dashboard_links'); ?>
        </div>
      </div>

      <?php osc_run_hook('user_dashboard_bottom'); ?>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>
