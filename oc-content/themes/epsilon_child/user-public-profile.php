<?php
  $user = osc_user();

  $user_location_array = array(osc_user_address(), osc_user_zip(), osc_user_city_area(), osc_user_city(), osc_user_region(), osc_user_country());
  $user_location_array = array_filter($user_location_array);
  $user_location = implode(', ', $user_location_array);

  $is_company = false;
  $user_item_count = $user['i_items'];

  if($user['b_company'] == 1) {
    $is_company = true;
  }

  $reg_type = '';
  $last_online = '';

  if($user && $user['dt_reg_date'] <> '') {
    $reg_type = sprintf(__('Registered for %s', 'epsilon'), eps_smart_date2($user['dt_reg_date']));
  } else if ($user) {
    $reg_type = __('Registered user', 'epsilon');
  } else {
    $reg_type = __('Unregistered user', 'epsilon');
  }

  if($user) {
    $last_online = sprintf(__('Last online %s', 'epsilon'), eps_smart_date($user['dt_access_date']));
  }

  $user_about = nl2br(strip_tags(osc_user_info()));
  $contact_name = (osc_user_name() <> '' ? osc_user_name() : __('Anonymous', 'epsilon'));

  $user_phone_mobile_data = eps_get_phone($user['s_phone_mobile']);
  $user_phone_land_data = eps_get_phone($user['s_phone_land']);
  $im_active = function_exists('im_contact_user_button');
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="index, follow" />
  <meta name="googlebot" content="index, follow" />
</head>

<body id="public">
  <?php
    View::newInstance()->_exportVariableToView('user', $user);
    osc_current_web_theme_path('header.php');
    View::newInstance()->_exportVariableToView('user', $user);
  ?>

  <div class="container primary">
    <div id="item-side">
      <?php osc_run_hook('user_public_profile_sidebar_top'); ?>

      <div class="box" id="seller">
        <div class="line1">
          <div class="img">
            <img src="<?php echo eps_profile_picture(osc_user_id(), 'small'); ?>" alt="<?php echo osc_esc_html($contact_name); ?>" />

            <?php if(eps_user_is_online(osc_user_id())) { ?>
              <div class="online" title="<?php echo osc_esc_html(__('User is online', 'epsilon')); ?>"></div>
            <?php } else { ?>
              <div class="online off" title="<?php echo osc_esc_html(__('User is offline', 'epsilon')); ?>"></div>
            <?php } ?>
          </div>

          <div class="data">
            <strong class="name"><?php echo $contact_name; ?></strong>

            <div class="items"><?php echo sprintf(__('%d active listings', 'epsilon'), $user_item_count); ?></div>

            <?php if($is_company) { ?>
              <div class="pro"><?php _e('Pro', 'epsilon'); ?></div>
            <?php } ?>
          </div>
        </div>

        <?php if(function_exists('ur_show_rating_link')) { ?>
          <div class="line-rating">
            <span class="ur-fdb">
              <span class="strs"><?php echo ur_show_rating_stars(osc_user_id(), osc_user_email()); ?></span>
              <span class="lnk"><?php echo ur_add_rating_link(osc_user_id()); ?></span>
            </span>
          </div>
        <?php } ?>

        <div class="line2">
          <div class="date"><?php echo $last_online; ?></div>
          <div class="reg"><?php echo $reg_type; ?></div>
        </div>

        <?php if(!$im_active && eps_chat_button(osc_user_id())) { ?>
          <div class="line-chat"><?php echo eps_chat_button(osc_user_id()); ?></div>
        <?php } ?>

        <div class="line3">
          <?php if($user_location != '') { ?>
            <div class="address"><i class="fas fa-map-marked-alt"></i> <?php echo $user_location; ?></div>
          <?php } ?>

          <?php if($user_phone_mobile_data['found']) { ?>
            <a class="phone-mobile phone <?php echo $user_phone_mobile_data['class']; ?>" title="<?php echo osc_esc_html($user_phone_mobile_data['title']); ?>" data-prefix="tel" href="<?php echo $user_phone_mobile_data['url']; ?>" data-part1="<?php echo osc_esc_html($user_phone_mobile_data['part1']); ?>" data-part2="<?php echo osc_esc_html($user_phone_mobile_data['part2']); ?>">
              <i class="fas fa-phone-alt"></i>
              <span><?php echo $user_phone_mobile_data['masked']; ?></span>
            </a>
          <?php } ?>

          <?php if($user_phone_land_data['found']) { ?>
            <a class="phone-land phone <?php echo $user_phone_land_data['class']; ?>" title="<?php echo osc_esc_html($user_phone_land_data['title']); ?>" data-prefix="tel" href="<?php echo $user_phone_land_data['url']; ?>" data-part1="<?php echo osc_esc_html($user_phone_land_data['part1']); ?>" data-part2="<?php echo osc_esc_html($user_phone_land_data['part2']); ?>">
              <i class="fas fa-phone-alt"></i>
              <span><?php echo $user_phone_land_data['masked']; ?></span>
            </a>
          <?php } ?>
        </div>
      </div>

      <?php if(!$im_active && getBoolPreference('item_contact_form_disabled') != 1) { ?>
        <a href="<?php echo eps_item_fancy_url('contact_public', array('userId' => osc_user_id())); ?>" class="open-form public-contact master-button" data-type="contact_public">
          <i class="fas fa-envelope-open"></i>
          <span><?php _e('Send message', 'epsilon'); ?></span>
        </a>
      <?php } ?>

      <a href="<?php echo osc_search_url(array('page' => 'search', 'userId' => osc_user_id())); ?>" class="seller-button seller-items"><?php echo __('All seller items', 'epsilon') . ' (' . $user_item_count . ')'; ?></a>

      <?php if(trim(osc_user_website()) <> '') { ?>
        <a href="<?php echo osc_user_website(); ?>" target="_blank" rel="nofollow noreferrer" class="seller-button seller-url">
          <i class="fas fa-external-link-alt"></i>
          <span><?php echo rtrim(str_replace(array('https://', 'http://'), '', osc_user_website()), '/'); ?></span>
        </a>
      <?php } ?>

      <?php if($user_about <> '') { ?>
        <div class="box" id="about">
          <strong><?php _e('About seller', 'epsilon'); ?></strong>
          <div><?php echo $user_about; ?></div>
        </div>
      <?php } ?>

      <div class="box" id="share">
        <?php osc_reset_resources(); ?>
        <a class="whatsapp isMobile" href="whatsapp://send?text=<?php echo urlencode(osc_user_public_profile_url(osc_user_id())); ?>" data-action="share/whatsapp/share"><i class="fab fa-whatsapp"></i> <?php _e('Whatsapp', 'epsilon'); ?></a></span>
        <a class="facebook" title="<?php echo osc_esc_html(__('Share on Facebook', 'epsilon')); ?>" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(osc_user_public_profile_url(osc_user_id())); ?>"><i class="fab fa-facebook"></i> <?php _e('Facebook', 'epsilon'); ?></a>
        <a class="twitter" title="<?php echo osc_esc_html(__('Share on Twitter', 'epsilon')); ?>" target="_blank" href="https://twitter.com/intent/tweet?text=<?php echo urlencode(meta_title()); ?>&url=<?php echo urlencode(osc_user_public_profile_url(osc_user_id())); ?>"><i class="fab fa-twitter"></i> <?php _e('Twitter', 'epsilon'); ?></a>
        <a class="pinterest" title="<?php echo osc_esc_html(__('Share on Pinterest', 'epsilon')); ?>" target="_blank" href="https://pinterest.com/pin/create/button/?url=<?php echo urlencode(osc_user_public_profile_url(osc_user_id())); ?>&media=<?php echo eps_profile_picture(osc_user_id(), 'large'); ?>&description=<?php echo htmlspecialchars(meta_title()); ?>"><i class="fab fa-pinterest"></i> <?php _e('Pinterest', 'epsilon'); ?></a>
      </div>

      <?php echo eps_banner('public_profile_sidebar'); ?>
      <?php osc_run_hook('user_public_profile_sidebar_bottom'); ?>
    </div>


    <div id="public-main">
      <?php osc_run_hook('user_public_profile_items_top'); ?>

      <?php echo eps_banner('public_profile_top'); ?>

      <h1><?php echo sprintf(__('%s\'s listings', 'epsilon'), $contact_name); ?></h1>

      <?php if(osc_version() >= 830) { ?>
        <form name="user-public-profile-search" action="<?php echo osc_base_url(true); ?>" method="get" class="user-public-profile-search-form nocsrf">
          <input type="hidden" name="page" value="user"/>
          <input type="hidden" name="action" value="pub_profile"/>
          <input type="hidden" name="id" value="<?php echo osc_esc_html($user['pk_i_id']); ?>"/>

          <?php osc_run_hook('user_public_profile_search_form_top'); ?>

          <div class="control-group">
            <label class="control-label" for="sPattern"><?php _e('Keyword', 'epsilon'); ?></label>

            <div class="controls">
              <?php UserForm::search_pattern_text(); ?>
            </div>
          </div>

          <div class="control-group">
            <label class="control-label" for="sCategory"><?php _e('Category', 'epsilon'); ?></label>

            <div class="controls">
              <?php UserForm::search_category_select(); ?>
            </div>
          </div>

          <div class="control-group">
            <label class="control-label" for="sCity"><?php _e('City', 'epsilon'); ?></label>

            <div class="controls">
              <?php UserForm::search_city_select(); ?>
            </div>
          </div>

          <?php osc_run_hook('user_public_profile_search_form_bottom'); ?>

          <div class="actions">
            <button type="submit" class="btn btn-primary"><?php _e('Apply', 'epsilon'); ?></button>
          </div>
        </form>
      <?php } ?>

      <?php if(osc_count_items() > 0) { ?>
        <div class="products list">
          <?php
            $c = 1;
            while(osc_has_items()) {
              eps_draw_item($c);

              if($c == 3 && osc_count_items() > 3) {
                echo eps_banner('public_profile_middle');
              }

              $c++;
            }
          ?>
        </div>

        <div class="paginate"><?php echo eps_fix_arrow(osc_pagination_items()); ?></div>

      <?php } else { ?>
        <div class="empty"><?php _e('User has no active listings', 'epsilon'); ?></div>
      <?php } ?>

      <?php echo eps_banner('public_profile_bottom'); ?>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>
