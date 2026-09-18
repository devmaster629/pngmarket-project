<?php 
  $item_extra = eps_item_extra(osc_item_id(), osc_item()); 
  $is_day_offer = (osc_item_id() == eps_param('day_offer_id') ? true : false);
  $card_class = trim((isset($c) ? ' o'. $c : '') . (osc_item_is_premium() ? ' is-premium' : '') . ($is_day_offer ? ' day-offer' : '') . (@$class != '' ? ' ' . $class : '') . (@$item_extra['i_sold'] == 1 ? ' st-sold' : '') . (@$item_extra['i_sold'] == 2 ? ' st-reserved' : ''));
  $phone_data = eps_get_item_phone();
  $email_data = eps_get_item_email();
?>

<div class="simple-prod<?php echo $card_class <> '' ? ' ' . $card_class : ''; ?> <?php osc_run_hook('highlight_class'); ?>">
  <div class="simple-wrap" title="<?php echo osc_esc_html(@$item_extra['i_sold'] == 1 ? __('Sold', 'epsilon') : ''); ?>">
    <?php osc_run_hook('item_loop_top'); ?>
    
    <div class="img-wrap<?php if(osc_count_item_resources() <= 0) { ?> no-image<?php } ?>">
      <a class="img" href="<?php echo osc_item_url(); ?>">
        <?php if(osc_count_item_resources() > 0) { ?>
          <img class="<?php echo (eps_is_lazy() ? 'lazy' : ''); ?>" <?php echo (eps_is_lazy_browser() ? 'loading="lazy"' : ''); ?> src="<?php echo (eps_is_lazy() ? eps_get_load_image() : osc_resource_thumbnail_url()); ?>" data-src="<?php echo osc_resource_thumbnail_url(); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" />
        <?php } else { ?>
          <img class="<?php echo (eps_is_lazy() ? 'lazy' : ''); ?>" <?php echo (eps_is_lazy_browser() ? 'loading="lazy"' : ''); ?> src="<?php echo (eps_is_lazy() ? eps_get_load_image() : eps_get_noimage()); ?>" data-src="<?php echo eps_get_noimage(); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" />
        <?php } ?>
      </a>

      <?php if(osc_count_item_resources() > 0) { ?>
        <span class="pngm-photo-count isGrid" aria-hidden="true"><i class="fas fa-camera"></i> <?php echo osc_count_item_resources(); ?></span>
      <?php } ?>

      <a class="bar isDetail" href="<?php echo osc_item_url(); ?>">
        <?php if(eps_check_category_price(osc_item_category_id())) { ?>
          <div class="price isDetail"><span><?php echo function_exists('pngm_format_price') ? pngm_format_price() : osc_item_formated_price(); ?></span></div>
        <?php } ?>
        
        <?php if(osc_count_item_resources() > 0) { ?>
          <div class="image-counter isDetail"><i class="fas fa-camera"></i> <?php echo osc_count_item_resources(); ?></div>
        <?php } ?>
      </a>
      
      <?php if(osc_item_user_id() > 0 && eps_has_profile_picture(osc_item_user_id())) { ?>
        <a href="<?php echo eps_user_public_profile_url(osc_item_user_id()); ?>" class="user-image isDetail">
          <img class="<?php echo (eps_is_lazy() ? 'lazy' : ''); ?>" <?php echo (eps_is_lazy_browser() ? 'loading="lazy"' : ''); ?> src="<?php echo (eps_is_lazy() ? eps_get_load_image() : eps_profile_picture(osc_item_user_id(), 'small')); ?>" data-src="<?php echo eps_profile_picture(osc_item_user_id(), 'small'); ?>" alt="<?php echo osc_esc_html(osc_item_contact_name()); ?>"/>

          <?php if(eps_user_is_company(osc_item_user_id())) { ?>
            <span class="business" title="<?php echo osc_esc_html(__('Professional seller', 'epsilon')); ?>"><?php _e('Pro', 'epsilon'); ?></span>
          <?php } ?>
          
          <?php if(eps_user_is_online(osc_item_user_id())) { ?>
            <div class="online" title="<?php echo osc_esc_html(__('User is online', 'epsilon')); ?>"></div>
          <?php } ?>
        </a>
      <?php } ?>
      
      <?php if(osc_item_is_premium()) { ?>
        <span class="premium-mark isGrid"><?php _e('Premium', 'epsilon'); ?></span>
      <?php } ?>
      
      <?php if($is_day_offer) { ?>
        <span class="day-offer-mark"><?php _e('Offer of the day', 'epsilon'); ?></span>
      <?php } ?>
    </div>

    <div class="data">
      <?php // Card: green price → title → location · date → Category • Condition • Transaction ?>
      <?php if(eps_check_category_price(osc_item_category_id())) { ?>
        <div class="price standalone pngm-card-price"><span><?php echo function_exists('pngm_format_price') ? pngm_format_price() : osc_item_formated_price(); ?></span></div>
      <?php } ?>

      <div class="pngm-card-title-row">
        <a class="title" href="<?php echo osc_item_url(); ?>"><?php echo osc_highlight(osc_item_title(), 100); ?></a>
      </div>

      <div class="info pngm-card-loc">
        <?php if(isset($item_extra['i_sold']) && $item_extra['i_sold'] == 1) { ?>
          <span class="label sold isList"><?php _e('Sold', 'epsilon'); ?></span>
        <?php } else if(isset($item_extra['i_sold']) && $item_extra['i_sold'] == 2) { ?>
          <span class="label reserved isList"><?php _e('Reserved', 'epsilon'); ?></span>
        <?php } ?>
      
        <span class="pngm-card-city"><?php echo function_exists('pngm_city_only') ? osc_esc_html(pngm_city_only()) : eps_item_location(); ?></span>
        <span class="pngm-card-sep" aria-hidden="true">·</span>
        <span class="pngm-card-time"><?php echo eps_smart_date(osc_item_pub_date()); ?></span>
      </div>
      
      <?php osc_run_hook('item_loop_title'); ?>
      
      <div class="description isDetail"><?php echo osc_highlight(strip_tags(osc_item_description()), 360); ?></div>
      
      <?php osc_run_hook('item_loop_description'); ?>

      <?php
        $pngm_meta_bits = array();
        if (osc_item_category() <> '') {
          $pngm_meta_bits[] = osc_item_category();
        }
        if (!in_array(osc_item_category_id(), eps_extra_fields_hide())) {
          $pngm_cond = eps_get_simple_name($item_extra['i_condition'], 'condition', false);
          $pngm_trx = eps_get_simple_name($item_extra['i_transaction'], 'transaction', false);
          if ($pngm_cond !== '') {
            $pngm_meta_bits[] = $pngm_cond;
          }
          if ($pngm_trx !== '') {
            $pngm_meta_bits[] = $pngm_trx;
          }
        }
      ?>
      <?php if (count($pngm_meta_bits) > 0) { ?>
        <div class="pngm-card-tags">
          <?php echo osc_esc_html(implode(' • ', $pngm_meta_bits)); ?>
        </div>
      <?php } ?>

      <div class="action isDetail">
        <?php if(eps_check_category_price(osc_item_category_id())) { ?>
          <div class="price<?php if(osc_item_price() <= 0) { ?> isstring<?php } ?>"><span><?php echo function_exists('pngm_format_price') ? pngm_format_price() : osc_item_formated_price(); ?></span></div>
        <?php } ?>
      </div>
      
      <?php if($item_extra['i_sold'] > 0) { ?>
        <div class="labels isDetail">
          <?php if($item_extra['i_sold'] == 1) { ?>
            <span class="label sold"><?php _e('Sold', 'epsilon'); ?></span>
          <?php } else if($item_extra['i_sold'] == 2) { ?>
            <span class="label reserved"><?php _e('Reserved', 'epsilon'); ?></span>
          <?php } ?>
        </div>
      <?php } ?>
      
      <div class="contact isDetail">
        <?php if(getBoolPreference('item_contact_form_disabled') != 1) { ?>
           <?php if(eps_param('messenger_replace_button') == 1 && function_exists('im_contact_button') && im_contact_button(osc_item(), true) !== false) { ?>
            <a class="contact" href="<?php echo im_contact_button(osc_item(), true); ?>"><i class="fas fa-envelope-open"></i> <span><?php _e('Send message', 'epsilon'); ?></span></a>
           <?php } else if(osc_reg_user_can_contact() && osc_is_web_user_logged_in() || !osc_reg_user_can_contact()) { ?>
            <a class="contact" href="<?php echo osc_item_url(); ?>#contact"><i class="fas fa-envelope-open"></i> <span><?php _e('Send message', 'epsilon'); ?></span></a>
          <?php } else { ?>
            <a class="contact" href="<?php echo osc_user_login_url(); ?>" title="<?php echo osc_esc_html(__('You must login first', 'epsilon')); ?>"><i class="fas fa-envelope-open"></i> <span><?php _e('Send message', 'epsilon'); ?></span></a>
          <?php } ?>
        <?php } ?>
        
        <?php if($phone_data['found']) { ?>
          <a class="phone <?php echo $phone_data['class']; ?>" title="<?php echo osc_esc_html($phone_data['title']); ?>" data-prefix="tel" href="<?php echo $phone_data['url']; ?>" data-part1="<?php echo osc_esc_html($phone_data['part1']); ?>" data-part2="<?php echo osc_esc_html($phone_data['part2']); ?>"><i class="fas fa-phone-alt"></i> <span><?php echo $phone_data['masked']; ?></span></a>
        <?php } ?>

        <?php if($email_data['visible']) { ?>
          <a class="email <?php echo $email_data['class']; ?>" title="<?php echo osc_esc_html($email_data['title']); ?>" href="#" data-prefix="mailto" data-part1="<?php echo osc_esc_html($email_data['part1']); ?>" data-part2="<?php echo osc_esc_html($email_data['part2']); ?>"><i class="fas fa-at"></i> <span><?php echo $email_data['masked']; ?></span></a>
        <?php } ?>
      </div>
    </div>

    <div class="right isList">
      <?php if(eps_check_category_price(osc_item_category_id())) { ?>
        <div class="price"><span><?php echo function_exists('pngm_format_price') ? pngm_format_price() : osc_item_formated_price(); ?></span></div>
      <?php } ?>
      
      <?php eps_make_favorite(); ?>
    </div>
      
      <div class="labels isGrid">
      <?php if(isset($item_extra['i_sold']) && $item_extra['i_sold'] == 1) { ?>
        <span class="label sold"><?php _e('Sold', 'epsilon'); ?></span>
      <?php } else if(isset($item_extra['i_sold']) && $item_extra['i_sold'] == 2) { ?>
        <span class="label reserved"><?php _e('Reserved', 'epsilon'); ?></span>
      <?php } ?>
    </div>

    <div class="pngm-fav isGrid isDetail"><?php eps_make_favorite(); ?></div>
    
    <?php osc_run_hook('item_loop_bottom'); ?>
  </div>
</div>