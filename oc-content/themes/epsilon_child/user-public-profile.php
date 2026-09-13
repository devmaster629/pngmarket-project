<?php
  if (!function_exists('pngm_ua_render_sidebar')) {
    require_once dirname(__FILE__) . '/includes/account_ua.php';
  }

  $user = osc_user();
  $user_id = (int) osc_user_id();
  $is_own = osc_is_web_user_logged_in() && ((int) osc_logged_user_id() === $user_id);
  $show_sidebar = osc_is_web_user_logged_in();

  $city = trim((string) osc_user_city());
  $region = trim((string) osc_user_region());
  $user_location = implode(', ', array_filter(array($city, $region)));

  $contact_name = (osc_user_name() !== '' ? osc_user_name() : __('Anonymous', 'epsilon'));
  $initials = function_exists('pngm_ua_user_initials') ? pngm_ua_user_initials($contact_name) : strtoupper(substr($contact_name, 0, 2));
  $has_photo = function_exists('eps_has_profile_picture') && eps_has_profile_picture($user_id);

  $email_verified = is_array($user) && !empty($user['b_active']);
  $phone_verified = is_array($user) && (trim((string) @$user['s_phone_mobile']) !== '' || trim((string) @$user['s_phone_land']) !== '');
  $id_verified = is_array($user) && !empty($user['b_company']); // Pro/company treated as stronger identity signal
  $is_verified_seller = $email_verified && ($phone_verified || $id_verified);

  $member_since = '';
  if (is_array($user) && !empty($user['dt_reg_date']) && $user['dt_reg_date'] !== '0000-00-00 00:00:00') {
    $ts = strtotime($user['dt_reg_date']);
    if ($ts) {
      $member_since = date('M Y', $ts);
    }
  }

  $about_raw = trim(strip_tags((string) osc_user_info()));
  $about_len = function_exists('mb_strlen') ? mb_strlen($about_raw) : strlen($about_raw);
  if ($about_len > 250) {
    $about_display = function_exists('mb_substr') ? mb_substr($about_raw, 0, 250) : substr($about_raw, 0, 250);
    $about_len = 250;
  } else {
    $about_display = $about_raw;
  }

  $msg_url = '';
  $msg_title = '';
  $show_message = !$is_own;
  if ($show_message) {
    if (function_exists('im_create_thread_url')) {
      if (!osc_is_web_user_logged_in()) {
        $msg_url = osc_user_login_url();
        $msg_title = __('Sign in to message this seller', 'epsilon');
      } else {
        $msg_url = im_create_thread_url(array('user_id' => $user_id));
      }
    } elseif (function_exists('eps_item_fancy_url') && getBoolPreference('item_contact_form_disabled') != 1) {
      $msg_url = eps_item_fancy_url('contact_public', array('userId' => $user_id));
    } else {
      $show_message = false;
    }
  }

  $total_items = function_exists('osc_search_total_items') ? (int) osc_search_total_items() : (int) osc_count_items();
  $pattern = Params::getParam('sPattern');
  $order = Params::getParam('sOrder');
  $order_type = Params::getParam('sOrderType');
  if ($order === '') {
    $order = 'dt_pub_date';
  }
  if ($order_type === '') {
    $order_type = 'DESC';
  }
  $sort_key = $order . '|' . $order_type;
  $sort_options = array(
    'dt_pub_date|DESC' => __('Sort: Newest First', 'epsilon'),
    'dt_pub_date|ASC' => __('Sort: Oldest First', 'epsilon'),
    'i_price|DESC' => __('Sort: Price high–low', 'epsilon'),
    'i_price|ASC' => __('Sort: Price low–high', 'epsilon'),
  );

  $msg_classes = 'pngm-seller-msg';
  if (strpos((string) $msg_url, 'contact_public') !== false || strpos((string) $msg_url, 'fancy') !== false) {
    $msg_classes .= ' open-form public-contact';
  }
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="index, follow" />
  <meta name="googlebot" content="index, follow" />
</head>

<body id="public" class="<?php echo $show_sidebar ? 'body-ua pngm-ua pngm-ua-seller' : 'pngm-ua-seller-guest'; ?>">
  <?php
    View::newInstance()->_exportVariableToView('user', $user);
    osc_current_web_theme_path('header.php');
    View::newInstance()->_exportVariableToView('user', $user);
  ?>

  <div class="container primary<?php echo $show_sidebar ? ' pngm-ua-shell' : ' pngm-seller-shell'; ?>">
    <?php if ($show_sidebar) { pngm_ua_render_sidebar($is_own ? 'public' : ''); } ?>

    <div id="user-main" class="pngm-ua-main pngm-seller">
      <?php osc_run_hook('user_public_profile_sidebar_top'); ?>

      <div class="pngm-seller-panel">
        <section class="pngm-seller-hero">
          <div class="pngm-seller-identity">
            <div class="pngm-seller-avatar" aria-hidden="true">
              <?php if ($has_photo) { ?>
                <img src="<?php echo eps_profile_picture($user_id, 'medium'); ?>" alt="" width="88" height="88" />
              <?php } else { ?>
                <span><?php echo osc_esc_html($initials); ?></span>
              <?php } ?>
            </div>
            <div class="pngm-seller-id-copy">
              <h1 class="pngm-seller-name"><?php echo osc_esc_html($contact_name); ?></h1>
              <?php if ($is_verified_seller) { ?>
                <div class="pngm-seller-verified">
                  <i class="fas fa-check" aria-hidden="true"></i>
                  <span><?php _e('Verified Seller', 'epsilon'); ?></span>
                </div>
              <?php } ?>
              <?php if ($member_since !== '') { ?>
                <div class="pngm-seller-since"><?php echo sprintf(__('Member since %s', 'epsilon'), osc_esc_html($member_since)); ?></div>
              <?php } ?>
            </div>
          </div>

          <ul class="pngm-seller-badges">
            <?php if ($email_verified) { ?>
              <li><i class="fas fa-check-circle" aria-hidden="true"></i><span><?php _e('Email verified', 'epsilon'); ?></span></li>
            <?php } ?>
            <?php if ($phone_verified) { ?>
              <li><i class="fas fa-check-circle" aria-hidden="true"></i><span><?php _e('Phone verified', 'epsilon'); ?></span></li>
            <?php } ?>
            <?php if ($id_verified) { ?>
              <li><i class="fas fa-check-circle" aria-hidden="true"></i><span><?php _e('ID verified', 'epsilon'); ?></span></li>
            <?php } ?>
          </ul>

          <?php if ($user_location !== '') { ?>
            <p class="pngm-seller-loc"><i class="fas fa-map-marker-alt" aria-hidden="true"></i><span><?php echo osc_esc_html($user_location); ?></span></p>
          <?php } ?>

          <hr class="pngm-seller-rule" />

          <?php if ($about_display !== '') { ?>
            <div class="pngm-seller-about">
              <h2><?php _e('About Me', 'epsilon'); ?></h2>
              <div class="pngm-seller-about-box">
                <p><?php echo nl2br(osc_esc_html($about_display)); ?></p>
                <span class="pngm-seller-about-count"><?php echo (int) $about_len; ?>/250</span>
              </div>
            </div>
          <?php } ?>

          <?php if ($show_message && $msg_url !== '') { ?>
            <a class="<?php echo osc_esc_html($msg_classes); ?>" href="<?php echo osc_esc_html($msg_url); ?>"<?php echo $msg_title !== '' ? ' title="' . osc_esc_html($msg_title) . '"' : ''; ?><?php echo (strpos($msg_classes, 'open-form') !== false) ? ' data-type="contact_public"' : ''; ?>>
              <i class="fas fa-comment" aria-hidden="true"></i>
              <span><?php _e('Message Seller', 'epsilon'); ?></span>
            </a>
          <?php } ?>
        </section>

        <hr class="pngm-seller-rule" />

        <section class="pngm-seller-listings">
          <?php osc_run_hook('user_public_profile_items_top'); ?>
          <?php echo eps_banner('public_profile_top'); ?>

          <h2 class="pngm-seller-listings-title"><?php _e('Seller Listings', 'epsilon'); ?></h2>

          <?php if (osc_version() >= 830) { ?>
            <form name="user-public-profile-search" action="<?php echo osc_base_url(true); ?>" method="get" class="pngm-seller-toolbar nocsrf">
              <input type="hidden" name="page" value="user" />
              <input type="hidden" name="action" value="pub_profile" />
              <input type="hidden" name="id" value="<?php echo osc_esc_html($user['pk_i_id']); ?>" />

              <?php osc_run_hook('user_public_profile_search_form_top'); ?>

              <div class="pngm-seller-search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="text" name="sPattern" value="<?php echo osc_esc_html($pattern); ?>" placeholder="<?php echo osc_esc_html(__('Search listings...', 'epsilon')); ?>" />
              </div>

              <div class="pngm-seller-filters">
                <div class="pngm-seller-select">
                  <?php UserForm::search_category_select($user_id); ?>
                </div>
                <div class="pngm-seller-select">
                  <select name="pngm_sort" id="pngm_seller_sort" aria-label="<?php echo osc_esc_html(__('Sort listings', 'epsilon')); ?>">
                    <?php foreach ($sort_options as $key => $label) { ?>
                      <option value="<?php echo osc_esc_html($key); ?>"<?php echo ($sort_key === $key) ? ' selected' : ''; ?>><?php echo osc_esc_html($label); ?></option>
                    <?php } ?>
                  </select>
                  <input type="hidden" name="sOrder" id="pngm_seller_order" value="<?php echo osc_esc_html($order); ?>" />
                  <input type="hidden" name="sOrderType" id="pngm_seller_order_type" value="<?php echo osc_esc_html($order_type); ?>" />
                </div>
              </div>

              <?php osc_run_hook('user_public_profile_search_form_bottom'); ?>
              <button type="submit" class="is-sr-only"><?php _e('Apply', 'epsilon'); ?></button>
            </form>
          <?php } ?>

          <p class="pngm-seller-count">
            <?php echo sprintf(_n('%d listing found', '%d listings found', $total_items, 'epsilon'), $total_items); ?>
          </p>

          <?php if (osc_count_items() > 0) { ?>
            <div class="pngm-seller-grid">
              <?php
                $c = 0;
                while (osc_has_items()) {
                  $c++;
                  $thumb = '';
                  if (osc_count_item_resources() > 0) {
                    $thumb = osc_resource_thumbnail_url();
                  }
                  $price = function_exists('pngm_format_price') ? pngm_format_price() : osc_item_formated_price();
                  $item_city = function_exists('pngm_city_only') ? pngm_city_only() : osc_item_city();
                  if ($item_city === '') {
                    $item_city = osc_item_region();
                  }
              ?>
                <a class="pngm-seller-item" href="<?php echo osc_item_url(); ?>">
                  <span class="pngm-seller-item-media">
                    <?php if ($thumb !== '') { ?>
                      <img src="<?php echo osc_esc_html($thumb); ?>" alt="" loading="lazy" />
                    <?php } else { ?>
                      <span class="pngm-seller-item-empty" aria-hidden="true"></span>
                    <?php } ?>
                  </span>
                  <span class="pngm-seller-item-body">
                    <strong class="pngm-seller-item-title"><?php echo osc_esc_html(osc_item_title()); ?></strong>
                    <?php if (eps_check_category_price(osc_item_category_id()) && $price !== '') { ?>
                      <em class="pngm-seller-item-price"><?php echo osc_esc_html($price); ?></em>
                    <?php } ?>
                    <?php if ($item_city !== '') { ?>
                      <span class="pngm-seller-item-city"><?php echo osc_esc_html($item_city); ?></span>
                    <?php } ?>
                    <span class="pngm-seller-item-time"><?php echo osc_esc_html(eps_smart_date(osc_item_pub_date())); ?></span>
                  </span>
                </a>
              <?php
                  if ($c === 3 && osc_count_items() > 3) {
                    echo eps_banner('public_profile_middle');
                  }
                }
              ?>
            </div>

            <div class="pngm-seller-paginate paginate"><?php echo eps_fix_arrow(osc_pagination_items()); ?></div>
          <?php } else { ?>
            <div class="pngm-seller-empty"><?php _e('No listings found', 'epsilon'); ?></div>
          <?php } ?>

          <?php echo eps_banner('public_profile_bottom'); ?>
        </section>
      </div>

      <?php osc_run_hook('user_public_profile_sidebar_bottom'); ?>
    </div>
  </div>

  <script>
  (function ($) {
    $(function () {
      var $sort = $('#pngm_seller_sort');
      if ($sort.length) {
        $sort.on('change', function () {
          var parts = String($sort.val() || 'dt_pub_date|DESC').split('|');
          $('#pngm_seller_order').val(parts[0] || 'dt_pub_date');
          $('#pngm_seller_order_type').val(parts[1] || 'DESC');
          $sort.closest('form').trigger('submit');
        });
      }
      $('select[name="sCategory"]').on('change', function () {
        $(this).closest('form').trigger('submit');
      });
    });
  })(jQuery);
  </script>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
