<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <link rel="stylesheet" media="print" href="<?php echo osc_current_web_theme_url('css/print.css?v=' . date('YmdHis')); ?>">

  <?php
    $itemviewer = (Params::getParam('itemviewer') == 1 ? 1 : 0);
    $item_extra = eps_item_extra(osc_item_id());

    $location_array = array_filter(array(osc_item_city(), osc_item_region()));
    $location = implode(', ', $location_array);

    $location_full_array = array_filter(array(osc_item_city(), osc_item_region()));
    $location_full = implode(', ', $location_full_array);

    $is_company = false;
    
    if(osc_item_user_id() <> 0) {
      $item_user = eps_get_user(osc_item_user_id());
      View::newInstance()->_exportVariableToView('user', $item_user);
      $user_item_count = $item_user['i_items'];
      
      if($item_user['b_company'] == 1) {
        $is_company = true;
      }
    } else {
      $item_user = false;
      $user_item_count = Item::newInstance()->countItemTypesByEmail(osc_item_contact_email(), 'active');
    }
    
    $contact_name = (osc_item_contact_name() <> '' ? osc_item_contact_name() : __('Anonymous', 'epsilon'));

    $item_user_location_array = array_filter(array(osc_user_address(), osc_user_zip(), osc_user_city_area(), osc_user_city(), osc_user_region(), osc_user_country()));
    $item_user_location = implode(', ', $item_user_location_array);


    $reg_type = '';
    $last_online = '';

    if($item_user && $item_user['dt_reg_date'] <> '') { 
      $reg_type = sprintf(__('Registered for %s', 'epsilon'), eps_smart_date2($item_user['dt_reg_date']));
    } else if ($item_user) { 
      $reg_type = __('Registered user', 'epsilon');
    } else {
      $reg_type = __('Unregistered user', 'epsilon');
    }

    if($item_user && @$item_user['dt_access_date'] != '') {
      $last_online = sprintf(__('Last online %s', 'epsilon'), eps_smart_date($item_user['dt_access_date']));
    }
    
    //$user_about = nl2br(strip_tags(osc_user_info()));

    $phone_data = eps_get_item_phone();
    $email_data = eps_get_item_email();
    $user_phone_mobile_data = eps_get_phone(isset($item_user['s_phone_mobile']) ? $item_user['s_phone_mobile'] : '');
    $user_phone_land_data = eps_get_phone(isset($item_user['s_phone_land']) ? $item_user['s_phone_land'] : '');

    $has_cf = false;
    while(osc_has_item_meta()) {
      if(osc_item_meta_value() != '') {
        $has_cf = true;
        break;
      }
    }

    View::newInstance()->_reset('metafields');
    
    $make_offer_enabled = false;

    if(function_exists('mo_call_after_install')) {
      $history = osc_get_preference('history', 'plugin-make_offer');
      $category = osc_get_preference('category', 'plugin-make_offer');
      $category_array = explode(',', $category);

      $root = Category::newInstance()->findRootCategory(osc_item_category_id());
      $root_id = $root['pk_i_id'];

      if((in_array($root_id, $category_array) || trim($category) == '') && (osc_item_price() > 0 || osc_item_price() !== 0)) {
        $setting = ModelMO::newInstance()->getOfferSettingByItemId(osc_item_id());

        if((isset($setting['i_enabled']) && $setting['i_enabled'] == 1) || ((!isset($setting['i_enabled']) || $setting['i_enabled'] == '') && $history == 1)) {
          $make_offer_enabled = true;
        }
      }
    }
    
    $item_search_url = osc_search_url(array('page' => 'search', 'sCategory' => osc_item_category_id(), 'sCountry' => osc_item_country_code(), 'sRegion' => osc_item_region_id(), 'sCity' => osc_item_city_id()));

    if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != '' && strpos($_SERVER['HTTP_REFERER'], osc_base_url()) !== false) {
      $item_search_url = false;
    }
    
    $dimNormal = explode('x', osc_get_preference('dimNormal', 'osclass')); 
    $aspect_ratio = round($dimNormal[0]/$dimNormal[1], 3);
    $gallery_padding_top = round(1/$aspect_ratio*100, 2);
    
    osc_reset_resources();
    osc_get_item_resources();
    $resource_url = osc_resource_url(); 
  ?>

  <!-- FACEBOOK OPEN GRAPH TAGS -->
  <meta property="og:title" content="<?php echo osc_esc_html(osc_item_title()); ?>" />
  <?php if(osc_count_item_resources() > 0) { ?><meta property="og:image" content="<?php echo $resource_url; ?>" /><?php } ?>
  <meta property="og:site_name" content="<?php echo osc_esc_html(osc_page_title()); ?>"/>
  <meta property="og:url" content="<?php echo osc_item_url(); ?>" />
  <meta property="og:description" content="<?php echo osc_esc_html(osc_highlight(osc_item_description(), 500)); ?>" />
  <meta property="og:type" content="article" />
  <meta property="og:locale" content="<?php echo osc_current_user_locale(); ?>" />
  <meta property="product:retailer_item_id" content="<?php echo osc_item_id(); ?>" />
  <?php if(eps_check_category_price(osc_item_category_id())) { ?>
  <meta property="product:price:amount" content="<?php echo osc_esc_html(strip_tags(osc_item_price()/1000000)); ?>" />
  <?php if(osc_item_price() <> '' and osc_item_price() <> 0) { ?><meta property="product:price:currency" content="<?php echo osc_item_currency(); ?>" /><?php } ?>
  <?php } ?>
  
  <?php if(!function_exists('osc_structured_data_enabled')) { ?>
  <!-- GOOGLE RICH SNIPPETS -->
  <span itemscope itemtype="http://schema.org/Product">
    <meta itemprop="name" content="<?php echo osc_esc_html(osc_item_title()); ?>" />
    <meta itemprop="description" content="<?php echo osc_esc_html(osc_highlight(osc_item_description(), 500)); ?>" />
    <?php if(osc_count_item_resources() > 0) { ?><meta itemprop="image" content="<?php echo $resource_url; ?>" /><?php } ?>
  </span>
  <?php } ?>
</head>

<body id="item" class="<?php if(eps_device() <> '') { echo ' dvc-' . eps_device(); } ?><?php if(osc_item_is_expired()) { ?> expired<?php } ?>">
  <?php osc_current_web_theme_path('header.php') ; ?>

  <div class="container primary">
    <?php osc_run_hook('item_top'); ?>
    
    <?php echo eps_banner('item_top'); ?>

    <div class="data-box<?php echo osc_esc_html(@$item_extra['i_sold'] == 1 ?  ' sold' : ''); ?>" title="<?php echo osc_esc_html(@$item_extra['i_sold'] == 1 ? __('Sold', 'epsilon') : ''); ?>">
      <div id="item-main">
        <?php if(osc_images_enabled_at_items()) { ?> 
          <div id="item-image" class="<?php if(osc_count_item_resources() <= 0 ) { ?>noimg<?php } ?>">
            <?php if($item_search_url !== false) { ?>
              <a href="<?php echo $item_search_url; ?>" class="mlink back isMobile"><i class="fas fa-arrow-left"></i></a>
            <?php } else { ?>
              <a href="#" onclick="history.back();return false;" class="mlink back isMobile"><i class="fas fa-arrow-left"></i></a>
            <?php } ?>

            <?php if(eps_param('messenger_replace_button') == 1 && function_exists('im_contact_button') && im_contact_button(osc_item(), true) !== false) { ?>
              <a href="<?php echo im_contact_button(osc_item(), true); ?>" class="mlink contact isMobile"><i class="fas fa-envelope-open"></i></a>
            <?php } else if(getBoolPreference('item_contact_form_disabled') != 1) { ?>
              <a href="<?php echo eps_item_fancy_url('contact'); ?>" data-type="contact" class="mlink contact isMobile open-form"><i class="fas fa-envelope-open"></i></a>
            <?php } ?>
            
            <a href="#" class="mlink share isMobile"><i class="fas fa-share-alt"></i></a>
            
            <?php osc_get_item_resources(); ?>
            <?php osc_reset_resources(); ?>

            <?php if(osc_count_item_resources() > 0 ) { ?>  
              <div class="swiper-container pngm-item-gallery<?php if(osc_count_item_resources() <= 1) { ?> hide-buttons<?php } ?>">
                <div class="swiper-wrapper">
                  <?php for($i = 0;osc_has_item_resources(); $i++) { ?>
                    <li class="swiper-slide ratio<?php echo str_replace(':', 'to', eps_param('gallery_ratio')); ?>" <?php if(eps_param('gallery_ratio') == '') { ?>style="padding-top:<?php echo $gallery_padding_top; ?>%;"<?php } ?>>
                      <a href="<?php echo osc_resource_url(); ?>" data-sub-html="<?php echo osc_esc_html(osc_item_title()); ?> — <?php echo ($i+1); ?>/<?php echo osc_count_item_resources(); ?>">
                        <div class="swiper-zoom-container">
                          <img class="<?php echo (eps_is_lazy() ? 'lazy' : ''); ?>" <?php echo (eps_is_lazy_browser() ? 'loading="lazy"' : ''); ?> src="<?php echo (eps_is_lazy() ? eps_get_load_image() : osc_resource_url()); ?>" data-src="<?php echo osc_resource_url(); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?> - <?php echo $i+1;?>/<?php echo osc_count_item_resources(); ?>"/>
                        </div>
                      </a>
                    </li>
                  <?php } ?>
                </div>
                
                <div class="swiper-pg"></div>

                <div class="swiper-button swiper-next" aria-label="<?php echo osc_esc_html(__('Next photo', 'epsilon')); ?>"><i class="fas fa-caret-right"></i></div>
                <div class="swiper-button swiper-prev" aria-label="<?php echo osc_esc_html(__('Previous photo', 'epsilon')); ?>"><i class="fas fa-caret-left"></i></div>
                <button type="button" class="pngm-gallery-fullscreen" title="<?php echo osc_esc_html(__('Full screen', 'epsilon')); ?>" aria-label="<?php echo osc_esc_html(__('Full screen', 'epsilon')); ?>"><i class="fas fa-expand"></i></button>
              </div>
            <?php } ?>
            
            <?php osc_get_item_resources(); ?>
            <?php osc_reset_resources(); ?>

            <?php if(osc_count_item_resources() > 0 ) { ?>
              <div class="swiper-thumbs">
                <ul>
                  <?php for($i = 0;osc_has_item_resources(); $i++) { ?>
                    <li class="<?php if($i == 0) { ?>active<?php } ?>" data-id="<?php echo $i; ?>">
                      <img class="<?php echo (eps_is_lazy() ? 'lazy' : ''); ?>" <?php echo (eps_is_lazy_browser() ? 'loading="lazy"' : ''); ?> src="<?php echo (eps_is_lazy() ? eps_get_load_image() : osc_resource_thumbnail_url()); ?>" data-src="<?php echo osc_resource_thumbnail_url(); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?> - <?php echo $i+1;?>"/>
                    </li>
                  <?php } ?>
                </ul>
              </div>
            <?php } ?>
          </div>
        <?php } ?>
        
        <?php osc_run_hook('item_images'); ?>
        
        
        <div class="basic">
          <h1 class="row"><?php echo osc_item_title(); ?></h1>
          
          <?php osc_run_hook('item_title'); ?>
          
          <div class="labels">
            <?php if(osc_item_is_premium()) { ?><span class="premium"><?php _e('Premium', 'epsilon'); ?></span><?php } ?>
            <?php if(osc_item_is_expired()) { ?><span class="expired"><?php _e('Expired', 'epsilon'); ?></span><?php } ?>
            <?php if($item_extra['i_sold'] == 1) { ?><span class="sold"><?php _e('Sold', 'epsilon'); ?></span><?php } ?>
            <?php if($item_extra['i_sold'] == 2) { ?><span class="reserved"><?php _e('Reserved', 'epsilon'); ?></span><?php } ?>
          </div>
            
          <?php if(eps_check_category_price(osc_item_category_id())) { ?>
            <div class="row price under-header p-<?php echo osc_esc_html(osc_item_price()); ?>x<?php if(osc_item_price() <= 0) { ?> isstring<?php } ?>"><?php echo function_exists('pngm_format_price') ? pngm_format_price() : osc_item_formated_price(); ?></div>
          <?php } ?>
          
          <?php if(function_exists('mo_show_offer_link_raw') && mo_show_offer_link_raw() !== false) { ?>
            <a href="<?php echo mo_show_offer_link_raw(); ?>" class="mo-open-offer mo-button-create mo-make-offer-price"><?php _e('Make price offer', 'epsilon'); ?></a>
          <?php } ?>
          
          
          <?php if($make_offer_enabled && 1==2) { ?>
            <a href="#" id="mk-offer" class="make-offer-link" data-item-id="<?php echo osc_item_id(); ?>" data-item-currency="<?php echo osc_item_currency(); ?>" data-ajax-url="<?php echo mo_ajax_url(); ?>&moAjaxOffer=1&itemId=<?php echo osc_item_id(); ?>"><?php _e('Submit your offer', 'epsilon'); ?></a>
          <?php } ?>

          <div class="row date pngm-item-date">
            <p><?php echo sprintf(__('Posted %s', 'epsilon'), function_exists('eps_smart_date') ? eps_smart_date(osc_item_pub_date()) : osc_format_date(osc_item_pub_date())); ?></p>
          </div>
          
           <?php eps_make_favorite(); ?>
        </div>
        

        <div class="props pngm-item-compact pngm-item-details">
          <div id="item-hook"><?php osc_run_hook('item_detail', osc_item()); ?></div>
        </div>
        
        <?php osc_run_hook('item_meta'); ?>
        
        <?php echo eps_banner('item_description'); ?>
        
        <!-- DESCRIPTION → LOCATION → CONTACT (mockup order) -->
        <div class="row description pngm-item-detail-block">
          <h2><i class="fas fa-align-left" aria-hidden="true"></i> <?php _e('Description', 'epsilon'); ?></h2>

          <div class="desc-parts">
            <div class="desc-text">
              <?php if(eps_param('shorten_description') == 1) { ?>
                <div class="text visible">
                  <?php if(function_exists('show_qrcode')) { ?>
                    <div class="qr-code">
                      <strong><?php _e('Scan QR', 'epsilon'); ?></strong>
                      <?php show_qrcode(); ?>
                    </div>
                  <?php } ?>

                  <div class="desc-text-raw"><?php echo substr(strip_tags(osc_item_description()), 0, 720) . (strlen(strip_tags(osc_item_description())) > 720 ? '...' : ''); ?></div>
                </div>

                <?php if(strlen(osc_item_description()) > 720) { ?>
                  <div class="text hidden">
                    <?php if(function_exists('show_qrcode')) { ?>
                      <div class="qr-code">
                        <strong><?php _e('Scan QR', 'epsilon'); ?></strong>
                        <?php show_qrcode(); ?>
                      </div>
                    <?php } ?>
                  
                    <div class="desc-text-raw"><?php echo osc_item_description(); ?></div>
                  </div>

                  <div class="links">
                    <a href="#" class="read-more-desc"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-angle-down"></i></a>
                  </div>
                <?php } ?>
              <?php } else { ?>
                <div class="text visible">
                  <?php if(function_exists('show_qrcode')) { ?>
                    <div class="qr-code">
                      <strong><?php _e('Scan QR', 'epsilon'); ?></strong>
                      <?php show_qrcode(); ?>
                    </div>
                  <?php } ?>
                  
                  <?php echo osc_item_description(); ?>
                </div>
              <?php } ?>
            </div>
            
            <?php osc_run_hook('item_description'); ?>
          </div>
        </div>

        <div class="location pngm-item-detail-block">
          <h2><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php _e('Location', 'epsilon'); ?></h2>

          <?php if($location <> '') { ?>
            <div class="row address"><?php echo osc_esc_html($location_full); ?></div>
            
            <?php
              $pngm_map_q = (osc_item_latitude() <> 0 && osc_item_longitude() <> 0)
                ? osc_item_latitude() . ',' . osc_item_longitude()
                : $location;
            ?>
            <a target="_blank" rel="noopener noreferrer" class="directions" href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($pngm_map_q); ?>">
              <?php _e('View on map', 'epsilon'); ?> &#8594;
            </a>
          <?php } else { ?>
            <?php _e('Unknown location', 'epsilon'); ?>
          <?php } ?>

          <div id="location-hook"><?php osc_run_hook('location'); ?></div>
        </div>

        <?php
          if (function_exists('pngm_render_seller_contact_buttons')) {
            pngm_render_seller_contact_buttons();
          }
        ?>


        <!-- COMMENTS BLOCK -->
        <?php if(osc_comments_enabled()) { ?>
          <div class="box" id="comments">
            <h2><?php _e('Comments', 'epsilon'); ?></h2>

            <div class="wrap">
              <?php if(osc_item_total_comments() > 0) { ?>
                <?php while(osc_has_item_comments()) { ?>
                  <?php
                    $comment_author = (osc_comment_author_name() == '' ? __('Anonymous', 'epsilon') : osc_comment_author_name());
                  ?>
                  
                  <div class="comment">
                    <a class="author" href="<?php echo (osc_comment_user_id() ? eps_user_public_profile_url(osc_comment_user_id()) : '#'); ?>" <?php echo (osc_comment_user_id() > 0 ? '' : 'onclick="return false;"'); ?>>
                      <img class="img <?php echo (eps_is_lazy() ? 'lazy' : ''); ?>" <?php echo (eps_is_lazy_browser() ? 'loading="lazy"' : ''); ?> src="<?php echo (eps_is_lazy() ? eps_get_load_image() : eps_profile_picture(osc_comment_user_id(), 'medium')); ?>" data-src="<?php echo eps_profile_picture(osc_comment_user_id(), 'medium'); ?>" alt="<?php echo osc_esc_html(osc_comment_author_name()); ?>"/>
                      <strong class="name"><?php echo $comment_author; ?></strong>
                    </a>
                    
                    <div class="data">
                      <?php if(osc_comment_title() != '') { ?>
                        <h3><?php echo osc_comment_title(); ?></h3>
                      <?php } ?>
                      
                      <div class="date"><?php echo eps_smart_date(osc_comment_pub_date()); ?></div>
                      
                      <?php if(function_exists('osc_enable_comment_rating') && osc_enable_comment_rating()) { ?>
                        <div class="rating">
                          <?php for($i = 1; $i <= 5; $i++) { ?>
                            <?php
                              $class = '';
                              if(osc_comment_rating() >= $i) {
                                $class = ' fill';
                              }
                            ?>
                            <i class="fa fa-star<?php echo $class; ?>"></i>
                          <?php } ?>

                          <span>(<?php echo sprintf(__('%d of 5', 'epsilon'), osc_comment_rating()); ?>)</span>
                        </div>
                      <?php } ?>

                      <div class="body"><?php echo nl2br(osc_comment_body()); ?></div>
   
                      <?php if(osc_comment_user_id() && (osc_comment_user_id() == osc_logged_user_id())) { ?>
                        <a rel="nofollow" class="remove" href="<?php echo osc_delete_comment_url(); ?>" title="<?php echo osc_esc_html(__('Delete your comment', 'epsilon')); ?>">
                          <i class="fas fa-trash-alt"></i> <span><?php _e('Delete', 'epsilon'); ?></span>
                        </a>
                      <?php } ?>
                    </div>
                  </div>

                  <?php if(function_exists('osc_enable_comment_reply') && osc_enable_comment_reply()) { ?>
                    <?php osc_get_comment_replies(); ?>
                    <?php if(osc_count_comment_replies() > 0) { ?>
                      <div id="comment-replies">
                        <?php while (osc_has_comment_replies()) { ?>
                          <?php
                            $comment_reply_author = (osc_comment_reply_author_name() == '' ? __('Anonymous', 'epsilon') : osc_comment_reply_author_name());
                          ?>
                          
                          <div class="comment">
                            <a class="author" href="<?php echo (osc_comment_reply_user_id() ? eps_user_public_profile_url(osc_comment_reply_user_id()) : '#'); ?>" <?php echo (osc_comment_reply_user_id() > 0 ? '' : 'onclick="return false;"'); ?>>
                              <img class="img <?php echo (eps_is_lazy() ? 'lazy' : ''); ?>" <?php echo (eps_is_lazy_browser() ? 'loading="lazy"' : ''); ?> src="<?php echo (eps_is_lazy() ? eps_get_load_image() : eps_profile_picture(osc_comment_reply_user_id(), 'medium')); ?>" data-src="<?php echo eps_profile_picture(osc_comment_reply_user_id(), 'medium'); ?>" alt="<?php echo osc_esc_html(osc_comment_reply_author_name()); ?>"/>
                              <strong class="name"><?php echo $comment_reply_author; ?></strong>
                            </a>
                            
                            <div class="data">
                              <?php if(osc_comment_reply_title() != '') { ?>
                                <h3><?php echo osc_comment_reply_title(); ?></h3>
                              <?php } ?>
                              
                              <div class="date"><?php echo eps_smart_date(osc_comment_reply_pub_date()); ?></div>
                              
                              <?php if(function_exists('osc_enable_comment_rating') && osc_enable_comment_rating()) { ?>
                                <div class="rating">
                                  <?php for($i = 1; $i <= 5; $i++) { ?>
                                    <?php
                                      $class = '';
                                      if(osc_comment_reply_rating() >= $i) {
                                        $class = ' fill';
                                      }
                                    ?>
                                    <i class="fa fa-star<?php echo $class; ?>"></i>
                                  <?php } ?>

                                  <span>(<?php echo sprintf(__('%d of 5', 'epsilon'), osc_comment_reply_rating()); ?>)</span>
                                </div>
                              <?php } ?>

                              <div class="body"><?php echo nl2br(osc_comment_reply_body()); ?></div>
           
                              <?php if(osc_comment_reply_user_id() && (osc_comment_reply_user_id() == osc_logged_user_id())) { ?>
                                <a rel="nofollow" class="remove" href="<?php echo osc_delete_comment_reply_url(); ?>" title="<?php echo osc_esc_html(__('Delete your comment', 'epsilon')); ?>">
                                  <i class="fas fa-trash-alt"></i> <span><?php _e('Delete', 'epsilon'); ?></span>
                                </a>
                              <?php } ?>
                            </div>
                          </div>
                        <?php } ?>
                      </div>
                    <?php } ?>
                  <?php } ?>
                  
                  <?php if(
                    function_exists('osc_enable_comment_reply')
                    && osc_enable_comment_reply() 
                    && (
                      osc_comment_reply_user_type() == ''
                      || osc_comment_reply_user_type() == 'LOGGED' && osc_is_web_user_logged_in()
                      || osc_comment_reply_user_type() == 'OWNER' && (osc_logged_user_id() == osc_item_user_id() && osc_item_user_id() > 0 || osc_logged_user_email() == osc_item_contact_email())
                      || osc_comment_reply_user_type() == 'ADMIN' && osc_is_admin_user_logged_in()
                    )
                  ) { ?>
                    <p class="comment-reply-row">
                      <?php $reply_params = array('replyToCommentId' => osc_comment_id()); ?>
                      <a class="btn btn-secondary comment-reply open-form" href="<?php echo eps_item_fancy_url('comment', $reply_params); ?>"><?php _e('Reply', 'epsilon'); ?></a>
                    </p>
                  <?php } ?>
                <?php } ?>

                <div class="paginate"><?php echo osc_comments_pagination(); ?></div>

              <?php } else { ?>
                <div class="empty-comments"><?php _e('No comments has been added yet', 'epsilon'); ?></div>
              <?php } ?>
              
              <?php if(osc_reg_user_post_comments() && osc_is_web_user_logged_in() || !osc_reg_user_post_comments()) { ?>
                <a class="open-form add btn<?php echo (osc_enable_comment_rating() ? ' has-rating' : ''); ?>" href="<?php echo eps_item_fancy_url('comment'); ?>" data-type="comment">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16"><path d="M448 0H64C28.7 0 0 28.7 0 64v288c0 35.3 28.7 64 64 64h96v84c0 9.8 11.2 15.5 19.1 9.7L304 416h144c35.3 0 64-28.7 64-64V64c0-35.3-28.7-64-64-64zm-80 216c0 8.8-7.2 16-16 16h-72v72c0 8.8-7.2 16-16 16h-16c-8.8 0-16-7.2-16-16v-72h-72c-8.8 0-16-7.2-16-16v-16c0-8.8 7.2-16 16-16h72v-72c0-8.8 7.2-16 16-16h16c8.8 0 16 7.2 16 16v72h72c8.8 0 16 7.2 16 16v16z"/></svg>
                  <?php _e('Add comment', 'epsilon'); ?>
                </a>
              <?php } ?>
            </div>
          </div>
        <?php } ?>
        
        <?php osc_run_hook('item_comment'); ?>

        <div id="shortcuts">
          <a href="#" class="print isDesktop"><i class="fas fa-print"></i> <?php _e('Print', 'epsilon'); ?></a>
          
          <?php if(!osc_item_send_friend_form_disabled()) { ?>
            <a class="friend open-form" href="<?php echo eps_item_fancy_url('friend'); ?>" data-type="friend"><i class="fas fa-share-square"></i> <?php _e('Send to friend', 'epsilon'); ?></a>
          <?php } ?>
          
          <div class="item-share">
            <a class="whatsapp" href="whatsapp://send?text=<?php echo urlencode(osc_item_url()); ?>" data-action="share/whatsapp/share"><i class="fab fa-whatsapp"></i></a></span>
            <a class="facebook" title="<?php echo osc_esc_html(__('Share on Facebook', 'epsilon')); ?>" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(osc_item_url()); ?>"><i class="fab fa-facebook"></i></a> 
            <a class="twitter" title="<?php echo osc_esc_html(__('Share on Twitter', 'epsilon')); ?>" target="_blank" href="https://twitter.com/intent/tweet?text=<?php echo urlencode(osc_item_title()); ?>&url=<?php echo urlencode(osc_item_url()); ?>"><i class="fab fa-twitter"></i></a> 
            <a class="pinterest" title="<?php echo osc_esc_html(__('Share on Pinterest', 'epsilon')); ?>" target="_blank" href="https://pinterest.com/pin/create/button/?url=<?php echo urlencode(osc_item_url()); ?>&media=<?php echo urlencode($resource_url); ?>&description=<?php echo htmlspecialchars(osc_item_title()); ?>"><i class="fab fa-pinterest"></i></a> 
          </div>
        </div>
      </div>


      <!-- SIDEBAR - RIGHT -->
      <div id="item-side">
        <?php osc_run_hook('item_sidebar_top'); ?>

        <?php if($email_data['visible']) { ?>
          <a class="master-button email pngm-item-email <?php echo $email_data['class']; ?>" title="<?php echo osc_esc_html($email_data['title']); ?>" href="#" data-prefix="mailto" data-part1="<?php echo osc_esc_html($email_data['part1']); ?>" data-part2="<?php echo osc_esc_html($email_data['part2']); ?>">
            <i class="fas fa-at"></i>
            <span><?php echo $email_data['masked']; ?></span>
          </a>
        <?php } ?>

        <?php /* Contact row already includes IM Chat — skip duplicate item_contact hook output. */ ?>

        <div class="box" id="seller">
          <div class="line1">
            <div class="img">
              <img src="<?php echo eps_profile_picture(osc_item_user_id(), 'small'); ?>" alt="<?php echo osc_esc_html($contact_name); ?>" />

              <?php if(osc_item_user_id() > 0) { ?>
                <?php if(eps_user_is_online(osc_item_user_id())) { ?>
                  <div class="online" title="<?php echo osc_esc_html(__('User is online', 'epsilon')); ?>"></div>
                <?php } else { ?>
                  <div class="online off" title="<?php echo osc_esc_html(__('User is offline', 'epsilon')); ?>"></div>
                <?php } ?>
              <?php } ?>
            </div>

            <div class="data">
              <?php if(osc_item_user_id() > 0) { ?>
                <a class="name" href="<?php echo eps_user_public_profile_url(osc_item_user_id()); ?>"><?php echo $contact_name; ?></a>
              <?php } else { ?>
                <strong class="name"><?php echo $contact_name; ?></strong>
              <?php } ?>

              <div class="items"><?php echo sprintf(__('%d active listings', 'epsilon'), $user_item_count); ?></div>
              
              <?php if($is_company) { ?>
                <div class="pro"><?php _e('Pro', 'epsilon'); ?></div>
              <?php } ?>
            </div>
          </div>
          
          <?php if(osc_item_user_id() > 0 && function_exists('ur_show_rating_link')) { ?>
            <div class="line-rating">
              <span class="ur-fdb">
                <span class="strs"><?php echo ur_show_rating_stars(osc_item_user_id(), osc_contact_email(), osc_item_id()); ?></span>
                <span class="lnk"><?php echo ur_add_rating_link(osc_item_user_id(), osc_item_id()); ?></span>
              </span>
            </div>
          <?php } ?>
          
          <div class="line2">
            <div class="date"><?php echo $last_online; ?></div>
            <div class="reg"><?php echo $reg_type; ?></div>
          </div>

          <?php /* Chat is in the Call / WhatsApp / Chat row above — no duplicate here. */ ?>

          <?php if(osc_item_user_id() > 0) { ?>
            <div class="line3">
              <?php if($item_user_location != '') { ?>
                <div class="address"><i class="fas fa-map-marked-alt"></i> <?php echo $item_user_location; ?></div>
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
          <?php } ?>
        </div>


        <?php if(osc_item_user_id() > 0) { ?>
          <a href="<?php echo eps_user_public_profile_url(osc_item_user_id()); ?>" class="seller-button seller-profile"><?php echo __('Seller\'s profile', 'epsilon'); ?></a>
          <a href="<?php echo osc_search_url(array('page' => 'search', 'userId' => osc_item_user_id())); ?>" class="seller-button seller-items"><?php echo __('All seller items', 'epsilon') . ' (' . $user_item_count . ')'; ?></a>

          <?php if(trim(osc_user_website()) <> '') { ?>
            <a href="<?php echo osc_user_website(); ?>" target="_blank" rel="nofollow noreferrer" class="seller-button seller-url">
              <i class="fas fa-external-link-alt"></i>
              <span><?php echo rtrim(str_replace(array('https://', 'http://'), '', osc_user_website()), '/'); ?></span>
            </a>
          <?php } ?>
        <?php } ?>

        <?php if(function_exists('sp_buttons')) { ?>
          <div class="sms-payments">
            <?php echo sp_buttons(osc_item_id());?>
          </div>
        <?php } ?>

        <?php if(osc_is_web_user_logged_in() && osc_item_user_id() == osc_logged_user_id()) { ?>
          <div class="manage-delimit"></div>
          
          <?php if(osc_item_is_inactive()) { ?>
            <?php if((function_exists('iv_add_item') && osc_get_preference('enable','plugin-item_validation') <> 1) || !function_exists('iv_add_item')) { ?>
              <a class="manage-button activate" target="_blank" href="<?php echo osc_item_activate_url(); ?>"><?php _e('Validate', 'epsilon'); ?></a>
            <?php } ?>
          <?php } ?>
          
          <a class="manage-button edit" href="<?php echo osc_item_edit_url(); ?>"><i class="fas fa-edit"></i> <span><?php _e('Edit', 'epsilon'); ?></span></a>
          <a class="manage-button delete" href="<?php echo osc_item_delete_url(); ?>" onclick="return confirm('<?php _e('Are you sure you want to delete this listing? This action cannot be undone.', 'epsilon'); ?>?')"><i class="fas fa-trash-alt"></i> <span><?php _e('Remove', 'epsilon'); ?></span></a>
        <?php } ?>
        
        
        <?php echo eps_banner('item_sidebar'); ?>

        
        <div class="box" id="protection">
          <h2><?php _e('Be careful!', 'epsilon'); ?></h2>
          
          <div class="point">
            <div class="icon i1"><i class="far fa-credit-card"></i></div>
            <span><?php _e('Never pay down a deposit in a bank account until you have met the seller, seen signed a purchase agreement.', 'epsilon'); ?></span>
          </div>
          
          <div class="point">
            <div class="icon i2"><i class="fas fa-cash-register"></i></div>
            <span><?php _e('No serious private advertisers ask for a down payment before you meet. ', 'epsilon'); ?></span>
          </div>
          
          <div class="point">
            <div class="icon i3"><i class="fas fa-user-secret"></i></div>
            <span><?php _e('Receiving an email with an in-scanned ID does not mean that you have identified the sender. You do this on the spot, when you sign a purchase agreement.', 'epsilon'); ?></span>
          </div>
        </div>

        <a href="#" class="report-button">
          <i class="fas fa-flag"></i>
          <span><?php _e('Report listing', 'epsilon'); ?></span>
        </a>

        <div class="report-wrap" style="display:none;">
          <div id="report">
            <img src="<?php echo osc_current_web_theme_url('images/report.png'); ?>" alt="<?php echo osc_esc_html(__('Report', 'epsilon')); ?>" />
            <div class="header"><?php _e('Report listing', 'epsilon'); ?></div>
            <div class="subheader"><?php _e('If you find this listing as inappropriate, offensive or spammy, please let us know about it. Select one of following reasons:', 'epsilon'); ?></div>
            
            <div class="text">
              <a href="<?php echo osc_item_link_spam() ; ?>" rel="nofollow"><?php _e('Spam', 'epsilon') ; ?></a>
              <a href="<?php echo osc_item_link_bad_category() ; ?>" rel="nofollow"><?php _e('Misclassified', 'epsilon') ; ?></a>
              <a href="<?php echo osc_item_link_repeated() ; ?>" rel="nofollow"><?php _e('Duplicated', 'epsilon') ; ?></a>
              <a href="<?php echo osc_item_link_expired() ; ?>" rel="nofollow"><?php _e('Expired', 'epsilon') ; ?></a>
              <a href="<?php echo osc_item_link_offensive() ; ?>" rel="nofollow"><?php _e('Offensive', 'epsilon') ; ?></a>
            </div>
          </div>
        </div>
        
        <?php echo eps_banner('item_sidebar_bottom'); ?>
        
        <?php osc_run_hook('item_sidebar_bottom'); ?>
      </div>
    </div>
  
    <?php 
      if (function_exists('pngm_seller_other_ads') && osc_item_user_id() > 0) {
        pngm_seller_other_ads(eps_param('related_design'), 8);
      }

      echo eps_banner('item_bottom');
      
      if(eps_param('recent_item') == 1) {
        eps_recent_ads('pngm-card', eps_param('recent_count'), 'onitem');
      }

      if (function_exists('pngm_similar_ads')) {
        pngm_similar_ads(eps_param('related_design'), eps_param('related_count'));
      } elseif (eps_param('related') == 1) {
        eps_related_ads('category', eps_param('related_design'), eps_param('related_count'));
      }
    ?>
  </div>

  <?php /* Floating phone / message buttons removed — contact stays in the seller block. */ ?>
  
  <div class="share-item-data" style="display:none">
    <a class="whatsapp" href="whatsapp://send?text=<?php echo urlencode(osc_item_url()); ?>" data-action="share/whatsapp/share"><i class="fab fa-whatsapp"></i> <?php _e('Share on Whatsapp', 'epsilon'); ?></a></span>
    <a class="facebook" title="<?php echo osc_esc_html(__('Share on Facebook', 'epsilon')); ?>" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(osc_item_url()); ?>"><i class="fab fa-facebook"></i> <?php _e('Share on Facebook', 'epsilon'); ?></a> 
    <a class="twitter" title="<?php echo osc_esc_html(__('Share on Twitter', 'epsilon')); ?>" target="_blank" href="https://twitter.com/intent/tweet?text=<?php echo urlencode(osc_item_title()); ?>&url=<?php echo urlencode(osc_item_url()); ?>"><i class="fab fa-twitter"></i> <?php _e('Share on Twitter', 'epsilon'); ?></a> 
    <a class="pinterest" title="<?php echo osc_esc_html(__('Share on Pinterest', 'epsilon')); ?>" target="_blank" href="https://pinterest.com/pin/create/button/?url=<?php echo urlencode(osc_item_url()); ?>&media=<?php echo urlencode($resource_url); ?>&description=<?php echo htmlspecialchars(osc_item_title()); ?>"><i class="fab fa-pinterest"></i> <?php _e('Share on Pinterest', 'epsilon'); ?></a> 

    <?php if(!osc_item_send_friend_form_disabled()) { ?>
      <a class="friend open-form" href="<?php echo eps_item_fancy_url('friend'); ?>" data-type="friend"><i class="fas fa-user-friends"></i> <?php _e('Send to friend', 'epsilon'); ?></a>
    <?php } ?>
  </div>

  <?php osc_run_hook('item_bottom'); ?>

  <script type="text/javascript">
    $(document).ready(function(){

      // SHARE BUTTON
      $('.mlink.share').on('click', () => {
        if (navigator.share) {
          navigator.share({
              title: '<?php echo osc_esc_js(osc_highlight(osc_item_title(), 40) . ' - ' . osc_item_formated_price()); ?>',
              text: '<?php echo osc_esc_js(osc_highlight(osc_item_title(), 40) . ' - ' . osc_item_formated_price()); ?>',
              url: '<?php echo osc_esc_js(osc_item_url()); ?>',
          }).catch((error) => console.log('ERROR: ', error));
        }
        
        return false;
      });
      
      
      $('.main-data > .img .mlink.share').on('click', () => {
        if (navigator.share) {
          navigator.share({
              title: '<?php echo osc_esc_js(osc_highlight(osc_item_title(), 30) . ' - ' . osc_item_formated_price()); ?>',
              text: '<?php echo osc_esc_js(osc_highlight(osc_item_title(), 30) . ' - ' . osc_item_formated_price()); ?>',
              url: '<?php echo osc_esc_js(osc_item_url()); ?>',
            })
            .catch((error) => console.log('ER', error));
        } else {
          if(($('#item-summary').is(':hidden') || $('.share-item-data').is(':hidden')) && $('.main-data > .img .mlink.share').hasClass('shown')) {
            $('.main-data > .img .mlink.share').removeClass('shown');
          }
          
          if(!$('.main-data > .img .mlink.share').hasClass('shown')) {
            $('.share-item-data').fadeIn(200);
            
            if(!$('#item-summary').hasClass('shown')) {
              $('#item-summary').addClass('shown').show(0).css('overflow', 'visible').css('bottom', '-100px').css('opacity', '0').stop(false, false).animate( {bottom:'8px', opacity:1}, 250);
            }
          } else {
            $('.share-item-data').fadeOut(200);

            if($('#listing .item .data').offset().top - 50 > $(window).scrollTop()) {
              $('#item-summary').removeClass('shown').stop(false, false).animate( {bottom:'-100px', opacity:0}, 250, function() {$('#item-summary').hide(0);});
            }
          }
            
          $('.main-data > .img .mlink.share').toggleClass('shown');
        }
        
        return false;
      });
      

    });
  </script>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>				