<?php
  $identifier = Params::getParam('identifier');
  $params = Params::getParam('params');

  $seller = ModelBPR::newInstance()->getSellerByIdentifier($identifier);
  
  if(!isset($seller['fk_i_user_id']) || $seller['fk_i_user_id'] <= 0) {
    osc_add_flash_info_message(__('This profile does not exists', 'business_profile'));
    header('Location:' . osc_base_url());
    exit;
  }

  $user_id = $seller['fk_i_user_id'];
  $user = User::newInstance()->findByPrimaryKey($user_id);
  View::newInstance()->_exportVariableToView('user', $user); 


  // CHECK IF PROFILE BASED ON PREMIUM GROUPS
  $control = bpr_control_premium($seller);
  if($control) {
    $seller = ModelBPR::newInstance()->getSellerByIdentifier($identifier);    // seller's data has been updated, we must refresh it
  }


  // CHECK IF EXISTS AND CAN BE SEEN
  if(bpr_param('require_validation') == 1 && @$seller['b_enabled'] == 0 && (!osc_is_admin_user_logged_in() && $user['pk_i_id'] <> osc_logged_user_id())) {
    osc_add_flash_info_message(__('This profile has not been validated yet', 'business_profile'));
    header('Location:' . osc_base_url());
    exit;
  }


  $current_cat = $current_city = $current_page = '';

  $cities = ModelBPR::newInstance()->getCities($user['pk_i_id']);
  $categories = ModelBPR::newInstance()->getCategories($user['pk_i_id']);

  $param_array = array_filter(array_map('trim', explode(';', $params)));

  foreach($param_array as $p) {
    $single = array_filter(array_map('trim', explode(',', $p)));

    if(@$single[0] == 'city') {
      $current_city = @$single[1];
    } else if (@$single[0] == 'category') {
      $current_cat = @$single[1];
    } else if (@$single[0] == 'page') {
      $current_page = @$single[1];
    }
  }

  $count_items = ModelBPR::newInstance()->countItems($user['pk_i_id'], $current_cat, $current_city);

  // CONTACT COMPANY
  if(Params::getParam('identifier') <> '' && Params::getParam('what') == 'contact' && Params::getParam('email') <> '') {
    osc_add_flash_ok_message(sprintf(__('Message has been successfully sent to %s', 'business_profile'), $user['s_name']));

    bpr_mail_seller($user['pk_i_id'], Params::getParam('id'), Params::getParam('email'), Params::getParam('message'), Params::getParam('phone'));
    header('Location:' . osc_route_url('bpr-seller', array('identifier' => $identifier)));
    exit;
  }

?>

<script>
  var bprSearch = '<?php echo osc_route_url('bpr-seller-filter', array('identifier' => $identifier, 'params' => 'city,{city};category,{category};')); ?>';
  var bprCity = '<?php echo $current_city; ?>';
  var bprCategory = '<?php echo $current_cat; ?>';
  var bprPage = '<?php echo $current_page; ?>';
</script>


<div id="bpr-seller" class="bpr-body">
  <?php osc_run_hook('bpr_seller_top', $user); ?>

  <?php if(bpr_param('require_validation') == 1) { ?>
    <?php if(isset($seller['b_enabled']) && $seller['b_enabled'] == 0) { ?>
      <div class="bpr-msg-wrap"><div class="bpr-msg"><?php _e('Your business profile is pending validation by admin. Only owner and admin can see this profile now.', 'business_profile'); ?></div></div>
    <?php } ?>
  <?php } ?>

  <?php if(bpr_check_premium() && !bpr_control_user($seller) && $seller['b_enabled'] == 0 && (osc_is_admin_user_logged_in() || $user['pk_i_id'] == osc_logged_user_id()) && bpr_param('require_validation') == 1) { ?>
    <div class="bpr-is-premium">
      <div class="bpr-p-title"><?php _e('In order to enable your profile, you must purchase membership in one of the following groups:', 'business_profile'); ?></div>

      <div class="bpr-grp-wrap">
        <?php foreach(bpr_premium_groups() as $g) { ?>
          <a class="bpr-grp bpr-has-tooltip" href="<?php echo osp_cart_add(OSP_TYPE_MEMBERSHIP, 1, $g['pk_i_id'], $g['i_days']); ?>" title="<?php echo osc_esc_html(__('Click to add membership to cart', 'business_profile')); ?>">
            <div class="bpr-grp-i" style="background:<?php echo $g['s_color']; ?>"></div>
            <i class="bpr-grp-fa fa fa-shopping-basket"></i>
            <div class="bpr-grp-t"><?php echo $g['s_name']; ?></div>
            <div class="bpr-grp-d"><?php echo sprintf(__('%s for %s days', 'business_profile'), osp_format_price($g['f_price']), $g['i_days']); ?></div>
          </a>
        <?php } ?>
      </div>

      <a class="bpr-prem-more" href="<?php echo osc_route_url('osp-membership'); ?>"><?php _e('More options', 'business_profile'); ?> <i class="fa fa-angle-double-right"></i></a>

      <?php if(bpr_param('auto_validation') == 0) { ?>
        <div class="bpr-p-note">* <?php _e('Profile must be validated by admin before it is public', 'business_profile'); ?></div>
      <?php } ?>
    </div>
  <?php } ?>


  <div class="bpr-inside">
    <?php if(!isset($seller['pk_i_id'])) { ?>
      <div class="bpr-empty"><?php _e('This company does not exists', 'business_profile'); ?></div>
    <?php } else { ?>
      <?php
        //$location = implode(', ', array_filter(array($user['s_country'], $user['s_region'], $user['s_city'], $user['s_zip'], $user['s_address'])));
        $location = bpr_user_location($user, 1);

        $img_url = osc_base_url() . 'oc-content/plugins/business_profile/img/';
      ?>

      <?php osc_run_hook('bpr_seller_box_top', $user); ?>
      <?php echo bpr_banner('company_top'); ?>
      
      <div class="bpr-cover">
        <div class="bpr-wrap">
          <div class="bpr-wrap-img" style="background-image:url('<?php echo bpr_get_img($user_id, $seller['s_cover'], 'cover'); ?>');"></div>

          <?php if(osc_is_web_user_logged_in()) { ?>
            <form class="nocsrf" method="POST" name="bpr_contact" id="sellerContact" action="<?php echo osc_route_url('bpr-seller', array('identifier' => $identifier)); ?>">
              <input type="hidden" name="identifier" value="<?php echo Params::getParam('identifier'); ?>" />
              <input type="hidden" name="what" value="contact" />
              <input type="hidden" name="id" value="<?php echo osc_logged_user_id(); ?>" />
              <input type="hidden" name="email" value="<?php echo osc_logged_user_email(); ?>" />

              <fieldset>
                <a class="bpr-close bpr-has-tooltip" title="<?php echo osc_esc_html(__('Close box', 'business_profile')); ?>"><img src="<?php echo $img_url; ?>close.png"/></a>
                <div class="bpr-title"><?php _e('Contact seller', 'business_profile'); ?></div>
                <input type="text" name="name" placeholder="<?php echo osc_esc_html(__('Name', 'business_profile')); ?>" required value="<?php echo osc_logged_user_name(); ?>" />
                <input type="phone" name="phone" placeholder="<?php echo osc_esc_html(__('Phone', 'business_profile')); ?>" value="<?php echo osc_logged_user_phone(); ?>" />
                <textarea name="message" required placeholder="<?php echo osc_esc_html(__('Put your question there...', 'business_profile')); ?>"></textarea>

                <?php osc_run_hook('bpr_seller_contact_form', $user); ?>
                
                <button type="submit" class="bpr-btn"><?php _e('Send', 'business_profile'); ?></button>
             </fieldset>
          <?php } ?>
        </div>

        <?php if(bpr_check_img($user_id, $seller['s_logo'], 'logo')) { ?>
          <div class="bpr-logo-wrap">
            <img src="<?php echo bpr_get_img($user_id, $seller['s_logo'], 'logo'); ?>" alt="<?php echo osc_esc_html($user['s_name']); ?>" />
          </div>
        <?php } ?>
        
        <?php if($seller['s_socials'] <> '') { ?>
          <div class="bpr-socials">
            <div class="bpr-right-box">
              <?php
                // [x] delimit value of parameter, [y] delimit rows
                $soc = array_filter(explode('[y]', $seller['s_socials']));

                if(count($soc) > 0) {
                  foreach($soc as $s) {
                    $data = array_filter(explode('[x]', $s));

                    if(isset($data[0]) && isset($data[1])) {
                      $icon = bpr_soc_icon($data[0]);

                      echo '<a target="_blank" href="' . $data[1] . '"><i class="fa ' . $icon . '"></i></a>';
                    }
                  }
                }
              ?>
            </div>
          </div>
        <?php } ?>

        <div class="bpr-contact-us">
          <?php if(osc_is_web_user_logged_in()) { ?>
            <a href="#" class="bpr-btn bpr-go"><?php echo __('Contact seller', 'business_profile'); ?></a>

          <?php } else { ?>
            <a href="#" class="bpr-btn bpr-disabled bpr-has-tooltip" disabled title="<?php echo osc_esc_html(__('Please login first', 'business_profile')); ?>"><?php echo __('Contact seller', 'business_profile'); ?></a>
          <?php } ?>
        </div>
      </div>


      <div class="bpr-profile">
        <div class="bpr-left">
          <?php echo bpr_banner('company_desc_top'); ?>
          <?php osc_run_hook('bpr_seller_profile_top', $user); ?>
          
          <div class="bpr-icon">
            <img src="<?php echo bpr_get_img($user_id, $seller['s_icon'], 'icon'); ?>" alt="<?php echo osc_esc_html($user['s_name']); ?>" />

            <div class="bpr-type-wrap">
              <?php echo bpr_user_label($seller); ?>
            </div>
          </div>

          <div class="bpr-box">
            <?php osc_run_hook('bpr_seller_profile_box_top', $user); ?>

            <?php if($seller['b_verified'] == 1) { ?>
              <div class="bpr-verified"><img src="<?php echo $img_url; ?>verified.png" alt="<?php echo osc_esc_html(__('Verified seller', 'business_profile')); ?>"/></div>
            <?php } ?>

            <h1><?php echo $user['s_name']; ?></h1>

            <?php osc_run_hook('bpr_seller_profile_name', $user); ?>

            <?php if($seller['s_category_ids'] <> '') { ?>
              <?php $ids = array_filter(array_map('trim', explode(';', $seller['s_category_ids']))); ?>
               
              <?php if(count($ids) > 0) { ?>
                <div class="bpr-category">
                  <?php $counter = 0; ?>
                  <?php foreach($ids as $id) { ?>
                    <?php $category = Category::newInstance()->findByPrimaryKey($id); ?>

                    <?php if(isset($category['s_name'])) { ?>
                      <a href="<?php echo osc_search_url(array('sUser' => $user['pk_i_id'], 'sCategory' => ($category['s_slug'] <> '' ? $category['s_slug'] : $id))); ?>"><?php echo $category['s_name']; ?></a>
                    <?php } ?>
                    
                    <?php $counter++; ?>

                    <?php if($counter < count($ids)) { ?><span>, </span><?php } ?>
                  <?php } ?>
                </div>
              <?php } ?>
            <?php } ?>

            <?php if(date('Y', strtotime($user['dt_reg_date'])) > 2000 && date('Y', strtotime($user['dt_reg_date'])) < 3000) { ?>
              <div class="bpr-reg"><?php echo sprintf(__('Seller since %s', 'business_profile'), date('Y', strtotime($user['dt_reg_date']))); ?></div>
            <?php } ?>
            
            <div class="bpr-type-wrap2">
              <?php echo bpr_user_label($seller); ?>
            </div>
            
            <?php if(osc_user_info() <> '') { ?>
              <div class="bpr-about"><?php echo osc_user_info(); ?></div>
            <?php } ?>
            
            <?php osc_run_hook('bpr_seller_profile_desc', $user); ?>

            <?php if($seller['s_features'] <> '') { ?>
              <div class="bpr-features">
                <?php $features = array_filter(array_map('trim', explode(',', $seller['s_features']))); ?>

                <?php if(count($features) > 0) { ?>
                  <?php foreach($features as $f) { ?>
                    <?php $feat = ModelBPR::newInstance()->getValueLocale($f); ?>

                    <?php if(isset($feat['locales']) && trim(urldecode(bpr_field($feat['locales']))) != '') { ?>
                      <div class="bpr-feat">
                        <span class="bpr-ic"><img src="<?php echo $img_url; ?>check.png" alt="<?php echo osc_esc_html(bpr_field($feat['locales'])); ?>"/></span>
                        <span class="bpr-feat-name"><?php echo urldecode(bpr_field($feat['locales'])); ?></span>
                      </div>
                    <?php } ?>
                  <?php } ?>
                <?php } ?>
              </div>
            <?php } ?>


            <div id="bpr-seller-map"><?php osc_run_hook('bpr_seller_profile_map', $user); ?></div>


            <?php if(bpr_param('gallery') == 1) { ?>
              <?php $gallery = bpr_prepare_user_gallery($seller); ?>
              <?php if(count($gallery) > 0) { ?>
                <div id="bpr-gallery">
                  <strong class="bpr-head"><?php echo sprintf(__('%s\'s gallery', 'business_profile'), $user['s_name']); ?></strong>
                  <div class="bpr-gallery-images">
                    <?php foreach($gallery as $img) { ?>
                      <a class="limg" href="<?php echo $img['url']; ?>" ><img src="<?php echo $img['url']; ?>" alt="<?php echo osc_esc_html($img['title']); ?>"/></a>
                    <?php } ?>
                  </div>
                </div>
              <?php } ?>
            <?php } ?>

            <?php if(bpr_param('video') == 1 && bpr_param('video_limit') > 0) { ?>
              <?php 
                $videos = array_filter(array_unique(explode(',', @$seller['s_videos']))); 
                $video_class = bpr_param('video_layout');
                $video_class = ($video_class == '' ? 'LIST' : $video_class);
                
                if($video_class == 'MIXED') {
                  if(count($videos) <= 2) {
                    $video_class = 'LIST';
                  } else {
                    $video_class = 'GRID';
                  }
                }

                $video_class = strtolower($video_class);
              ?>
              
              <?php if(count($videos) > 0) { ?>
                <div id="bpr-video" class="<?php echo $video_class; ?>">
                  <strong class="bpr-head"><?php _e('Youtube videos', 'business_profile'); ?></strong>
                  <div class="bpr-video-cards">
                    <?php foreach($videos as $video_id) { ?>
                      <div class="bpr-video-card">
                        <iframe width="420" height="240" loading="lazy" src="<?php echo bpr_video_base_url(); ?>embed/<?php echo $video_id; ?>" title="<?php echo osc_esc_html(__('YouTube video player', 'business_profile')); ?>" frameborder="0" allowfullscreen></iframe>
                      </div>
                    <?php } ?>
                  </div>
                </div>
              <?php } ?>
            <?php } ?>
          </div>

          <?php osc_run_hook('bpr_seller_profile_bottom', $user); ?>
          <?php echo bpr_banner('company_desc_bottom'); ?>
        </div>


        <div class="bpr-right">
          <?php osc_run_hook('bpr_seller_profile_sidebar_top', $user); ?>
          <?php echo bpr_banner('company_sidebar_top'); ?>
          
          <?php if($user['s_phone_land'] <> '' || $user['s_phone_mobile'] <> '') { ?>
            <div class="bpr-phone">
              <i class="fa fa-phone"></i>
              <div class="bpr-right-box">
                <?php if($user['s_phone_land'] <> '') { ?>
                  <?php if(bpr_param('phone_only_logged') == 1 && !osc_is_web_user_logged_in()) { ?>
                    <a class="bpr-has-tooltip" href="<?php echo osc_user_login_url(); ?>" title="<?php echo osc_esc_html(__('Login to see phone number', 'business_profile')); ?>"><?php echo bpr_phone_mask($user['s_phone_land'], 'MASKED'); ?></a>
                  <?php } else if(bpr_param('mask_phone') == 1) { ?>
                    <a class="bpr-phone-mask bpr-has-tooltip-phone" href="#" data-part1="<?php echo bpr_phone_mask($user['s_phone_land'], 'PART1'); ?>" data-part2="<?php echo bpr_phone_mask($user['s_phone_land'], 'PART2'); ?>" data-tip="<?php echo osc_esc_html(__('Click to call', 'business_profile')); ?>" title="<?php echo osc_esc_html(__('Click to show phone number', 'business_profile')); ?>"><?php echo bpr_phone_mask($user['s_phone_land'], 'MASKED'); ?></a>
                  <?php } else { ?>
                    <a href="tel:<?php echo $user['s_phone_land']; ?>"><?php echo $user['s_phone_land']; ?></a>
                  <?php } ?>
                <?php } ?>
                
                <?php if($user['s_phone_mobile'] <> '') { ?>
                  <?php if(bpr_param('phone_only_logged') == 1 && !osc_is_web_user_logged_in()) { ?>
                    <a class="bpr-has-tooltip" href="<?php echo osc_user_login_url(); ?>" title="<?php echo osc_esc_html(__('Login to see phone number', 'business_profile')); ?>"><?php echo bpr_phone_mask($user['s_phone_mobile'], 'MASKED'); ?></a>
                  <?php } else if(bpr_param('mask_phone') == 1) { ?>
                    <a class="bpr-phone-mask bpr-has-tooltip-phone" href="#" data-part1="<?php echo bpr_phone_mask($user['s_phone_mobile'], 'PART1'); ?>" data-part2="<?php echo bpr_phone_mask($user['s_phone_mobile'], 'PART2'); ?>" data-tip="<?php echo osc_esc_html(__('Click to call', 'business_profile')); ?>" title="<?php echo osc_esc_html(__('Click to show phone number', 'business_profile')); ?>"><?php echo bpr_phone_mask($user['s_phone_mobile'], 'MASKED'); ?></a>
                  <?php } else { ?>
                    <a href="tel:<?php echo $user['s_phone_mobile']; ?>"><?php echo $user['s_phone_mobile']; ?></a>
                  <?php } ?>
                <?php } ?>
              </div>
            </div>
          <?php } ?>

          <?php if($location <> '') { ?>
            <div class="bpr-location">
              <i class="fa fa-map-marker-alt fa-map-marker"></i>
              <div class="bpr-right-box">
                <span><?php echo $location; ?></span>
              </div>
            </div>
          <?php } ?>


          <?php if($seller['s_payments'] <> '') { ?>
            <?php $payments = array_filter(array_map('trim', explode(',', $seller['s_payments']))); ?>
               
            <?php if(is_array($payments) && count($payments) > 0) { ?>
              <div class="bpr-payments">
                <i class="fa fa-dollar"></i>

                <div class="bpr-right-box">
                  <?php 
                    $store_p = array();

                    foreach($payments as $p) {
                      $pay = ModelBPR::newInstance()->getValueLocale($p);

                      $store_p[] = bpr_field($pay['locales']);
                    }
                  ?>
 
                  <?php echo urldecode(implode(', ', array_filter($store_p))); ?>
                </div>
              </div>
            <?php } ?>
          <?php } ?>


          <?php if($seller['s_hours'] <> '') { ?>
            <div class="bpr-hours">
              <i class="fa fa-clock fa-calendar"></i>

              <div class="bpr-right-box">
                <?php
                  // [x] delimit value of parameter, [y] delimit rows
                  $hrs = array_filter(explode('[y]', $seller['s_hours']));

                  if(count($hrs) > 0) {
                    foreach($hrs as $h) {
                      $data = array_filter(explode('[x]', $h));

                      if(isset($data[0]) && isset($data[1])) {
                        echo '<div><span>' . bpr_day($data[0]) . ':</span> <strong>' . $data[1] . '</strong></div>';
                      }
                    }
                  }
                ?>
              </div>
            </div>
          <?php } ?>

          <div class="bpr-links">
            <i class="fa fa-link"></i>

            <div class="bpr-right-box">
              <?php if($user['s_website'] <> '' && filter_var($user['s_website'], FILTER_VALIDATE_URL)) { ?>
                <a href="<?php echo $user['s_website']; ?>" class="bpr-link" rel="nofollow" target="_blank"><?php echo sprintf(__('%s\'s website', 'business_profile'), $user['s_name']); ?> <i class="fa fa-angle-double-right"></i></a>
              <?php } ?>

              <a href="<?php echo bpr_companies_url(); ?>" class="bpr-comp-all"><?php _e('Show all companies', 'business_profile'); ?> <i class="fa fa-angle-double-right"></i></a>

              <?php if($user['pk_i_id'] == osc_logged_user_id() && osc_logged_user_id() > 0) { ?>
                <a href="<?php echo osc_route_url('bpr-profile'); ?>" class="bpr-comp-all"><?php _e('Edit profile', 'business_profile'); ?> <i class="fa fa-angle-double-right"></i></a>
              <?php } ?>
            </div>
          </div>
          
          <?php if(bpr_param('legal_notice') == 1 && trim((string)$seller['s_legal_notice']) <> '') { ?>
            <div class="bpr-legal-notice-sidebar">
              <strong class="bpr-legal-notice-head"><?php _e('Legal notice', 'business_profile'); ?> <i class="fa fa-caret-down"></i></strong>
              <div class="bpr-legal-notice-text"><?php echo strip_tags($seller['s_legal_notice']); ?></div>
            </div>
          <?php } ?>

          <?php osc_run_hook('bpr_seller_profile_sidebar_bottom', $user); ?>
          <?php echo bpr_banner('company_sidebar_bottom'); ?>
        </div>
      </div>

      <?php if(bpr_draw_check()) { ?>
        <div class="bpr-filters">
          <strong><?php echo sprintf(__('%s\'s listings', 'business_profile'), $user['s_name']); ?></strong>

          <div class="bpr-select bpr-city">
            <select name="city" id="bpr-city">
              <option value=""><?php _e('Select city', 'business_profile'); ?></option>

              <?php if(count($cities) > 0) { ?>
                <?php foreach($cities as $c) { ?>
                  <option value="<?php echo $c['city_id']; ?>" <?php if($current_city == $c['city_id']) { ?>selected="selected"<?php } ?>><?php echo osc_location_native_name_selector($c, 'city_name'); ?></option>
                <?php } ?>
              <?php } ?>
            </select>
          </div>

          <div class="bpr-select bpr-category">
            <select name="category" id="bpr-category">
              <option value=""><?php _e('Select category', 'business_profile'); ?></option>

              <?php if(count($categories) > 0) { ?>
                <?php foreach($categories as $c) { ?>
                  <option value="<?php echo $c['category_id']; ?>" <?php if($current_cat == $c['category_id']) { ?>selected="selected"<?php } ?>><?php echo $c['category_name']; ?></option>
                <?php } ?>
              <?php } ?>
            </select>
          </div>

          <?php osc_run_hook('bpr_seller_filters', $user); ?>

          <a class="bpr-show-all" href="<?php echo osc_search_url(array('sUser' => $user['pk_i_id'])); ?>"><?php _e('Show all', 'business_profile'); ?></a>
        </div>
      <?php } ?>
    <?php } ?>
  </div>

  <?php if(bpr_draw_check()) { ?>
    <?php 
      $param = array('author' => $user['pk_i_id']);
      $per_page = (bpr_param('per_page') <= 0 ? 18 : bpr_param('per_page'));

      if($current_cat > 0) {
        $param['category'] = $current_cat;
      }

      if($current_city > 0) {
        $param['city'] = $current_city;
      }

      if($current_page > 1) {
        $param['offset'] = $per_page * ($current_page-1);
      }

      $param['results_per_page'] = $per_page;


      osc_query_item($param); 
    ?>

    <div class="bpr-inside-trans">
      <?php echo bpr_banner('company_items_top'); ?>
      
      <?php if(osc_count_custom_items() > 0) { ?>
        <div class="bpr-items">
          <?php osc_run_hook('bpr_seller_items_top', $user); ?>
          
          <div id="gallery-view" class="white search-items-wrap">
            <div id="listing-card-list" class="block listing-card-list listing-grid">
              <div class="wrap products grid">

                <?php $c = 1; ?>
                <?php while( osc_has_custom_items() ) { ?>
                  <?php bpr_draw_item($c, 'gallery'); ?>
                  <?php $c++; ?>
                <?php } ?>
              </div>
            </div>
          </div>
          
          <?php osc_run_hook('bpr_seller_items_bottom', $user); ?>
        </div>

        <?php if($count_items > $per_page) { ?>
          <div id="bpr-pagination">
            <?php 
              $page_id = ($current_page == '' ? 1 : $current_page);
              
              $data = array(
                'identifier' => $identifier, 
                'city' => $current_city,
                'category' => $current_cat
              );
              
              echo bpr_paginate_items($data, $page_id, $per_page, $count_items);
            ?>
          </div>
        <?php } ?>

      <?php } else { ?>
        <div class="bpr-empty"><?php _e('No listing has been found', 'business_profile'); ?></div>
      <?php } ?>
    </div>
  <?php } ?>
  
  <?php osc_run_hook('bpr_seller_bottom', $user); ?>
  <?php echo bpr_banner('company_bottom'); ?>
</div>