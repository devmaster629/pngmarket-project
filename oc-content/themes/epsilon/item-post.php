<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">

<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />

  <?php if(osc_images_enabled_at_items()) { ItemForm::photos_javascript(); } ?>
</head>

<?php
  $action = 'item_add_post';
  $edit = false;

  if(Params::getParam('action') == 'item_edit') {
    $action = 'item_edit_post';
    $edit = true;
  }

  $countries = Country::newInstance()->listAll();
  
  $user = array();
  
  if(osc_is_web_user_logged_in()) {
    $user = osc_user();
  }
  
  if(!$edit) {
    $loc_cook = eps_location_from_cookies();

    $prepare = array();
    $prepare['s_contact_name'] = osc_user_name();
    $prepare['s_contact_email'] = osc_user_email();
    $prepare['s_zip'] = osc_user_zip();
    $prepare['s_city_area'] = osc_user_city_area();
    $prepare['s_address'] = osc_user_address();
    $prepare['fk_c_country_code'] = eps_get_session('countryId') <> '' ? eps_get_session('countryId') : (@$loc_cook['fk_c_country_code'] <> '' ? @$loc_cook['fk_c_country_code'] : @$user['fk_c_country_code']);
    $prepare['fk_i_region_id'] = eps_get_session('regionId') <> '' ? eps_get_session('regionId') : (@$loc_cook['fk_i_region_id'] > 0 ? @$loc_cook['fk_i_region_id'] : @$user['fk_i_region_id']);
    $prepare['fk_i_city_id'] = eps_get_session('cityId') <> '' ? eps_get_session('cityId') : (@$loc_cook['fk_i_city_id'] > 0 ? @$loc_cook['fk_i_city_id'] : @$user['fk_i_city_id']);
    $prepare['s_country'] = eps_get_session('sCountry') <> '' ? eps_get_session('sCountry') : osc_user_field('s_country');
    $prepare['s_region'] = eps_get_session('sRegion') <> '' ? eps_get_session('sRegion') : osc_user_region();
    $prepare['s_city'] = eps_get_session('sCity') <> '' ? eps_get_session('sCity') : osc_user_city();
    $prepare['s_phone'] = eps_get_session('sPhone') <> '' ? eps_get_session('sPhone') : osc_user_phone();
    $prepare['s_contact_phone'] = $prepare['s_phone'];
    $prepare['i_category'] = eps_get_session('catId') <> '' ? eps_get_session('catId') : Params::getParam('catId');
    $location_text = @array_values(array_filter(array($prepare['s_city'], $prepare['s_region'], $prepare['s_country'])))[0];
  } else {

    $item_extra = eps_item_extra(osc_item_id());

    $prepare = osc_item();
    $prepare['fk_c_country_code'] = eps_get_session('countryId') <> '' ? eps_get_session('countryId') : osc_item_country_code();
    $prepare['fk_i_region_id'] = eps_get_session('regionId') <> '' ? eps_get_session('regionId') : osc_item_region_id();
    $prepare['fk_i_city_id'] = eps_get_session('cityId') <> '' ? eps_get_session('cityId') : osc_item_city_id();
    $prepare['s_country'] = eps_get_session('sCountry') <> '' ? eps_get_session('sCountry') : osc_item_country();
    $prepare['s_region'] = eps_get_session('sRegion') <> '' ? eps_get_session('sRegion') : osc_item_region();
    $prepare['s_city'] = eps_get_session('sCity') <> '' ? eps_get_session('sCity') : osc_item_city();
    $prepare['s_phone'] = eps_get_session('sPhone') <> '' ? eps_get_session('sPhone') : @$item_extra['s_phone'];
    $prepare['i_category'] = eps_get_session('catId') <> '' ? eps_get_session('catId') : osc_item_category_id();
    $prepare['s_zip'] = eps_get_session('sZip') <> '' ? eps_get_session('sZip') : osc_item_zip();
    $prepare['s_address'] = eps_get_session('sAddress') <> '' ? eps_get_session('sAddress') : osc_item_address();
    $location_text = @array_values(array_filter(array(osc_item_city(), osc_item_region(), osc_item_country())))[0];
  }

  $prepare['fk_i_category_id'] = $prepare['i_category'];

  if($prepare['i_category'] > 0) {
    $cat = eps_get_category($prepare['i_category']);
  }

  $required_fields = strtolower(eps_param('post_required'));
  $location_type = eps_param('publish_location');
  $price_type = '';

  $category_type = (eps_param('publish_category') == '' ? 2 : eps_param('publish_category'));

  if($edit) {
    if(osc_item_price() === null) {
      $price_type = 'CHECK';
    } else if(osc_item_price() == 0) {
      $price_type = 'FREE';
    } else {
      $price_type = 'PAID';
    }
  }
  
  if($location_type == 0) { 
    //ItemForm::location_javascript();
    eps_location_javascript();
  }
?>

<body id="body-item-post" class="item-publish">
  <?php osc_current_web_theme_path('header.php') ; ?>

  <div class="container">
    <div class="box">
      <h1><?php echo (!$edit ? __('Post your ad', 'epsilon') : __('Edit your ad', 'epsilon')); ?></h1>

      <ul id="error_list" class="new-item"></ul>

      <form name="item" action="<?php echo osc_base_url(true);?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?php echo $action; ?>" />
        <input type="hidden" name="page" value="item" />
        <?php if($edit) { ?><input type="hidden" name="id" value="<?php echo osc_item_id(); ?>" /><?php } ?>
        <?php if($edit) { ?><input type="hidden" name="secret" value="<?php echo osc_item_secret(); ?>" /><?php } ?>
        <?php if($category_type == 1) { ?><input type="hidden" name="catId" id="catId" value="<?php echo $prepare['i_category']; ?>"/><?php } ?>

        <?php osc_run_hook('item_publish_top'); ?>
        
        <section class="s1">
          <h2><?php _e('Category', 'epsilon'); ?> <i class="show-tip fas fa-question-circle"></i></h2>
   
          <div class="in">
            <!-- CATEGORY -->

            <?php if($category_type == 1) { ?>
              <div class="row">
              
                <label for="sCategoryTerm" class="auto-width"><?php _e('Category', 'epsilon'); ?></label>

                <div class="input-box picker category only-search">
                  <input name="sCategoryTerm" type="text" class="category-pick" id="sCategoryTerm" placeholder="<?php echo osc_esc_html(__('Start typing category...', 'epsilon')); ?>" value="<?php echo osc_esc_html(osc_item_category()); ?>" autocomplete="off"/>
                  <i class="clean fas fa-times-circle"></i>
                  <div class="results"></div>
                </div>
              </div>
              
              <?php if(!osc_selectable_parent_categories()) { ?>
                <style>.picker.category .option.parent {display:none;}</style>
              <?php } ?>
            <?php } else if($category_type == 2 || $category_type == '') { ?>
              <div class="row category multi">
                <label for="catId"><?php _e('Select category for your listing', 'epsilon'); ?> <span class="req">*</span></label>
                <?php ItemForm::category_multiple_selects(null, $prepare, __('Select a category', 'epsilon')); ?>
              </div>
            <?php } else if($category_type == 3) { ?>
              <div class="row category simple">
                <label for="catId"><?php _e('Select category for your listing', 'epsilon'); ?> <span class="req">*</span></label>
                <?php ItemForm::category_select(null, $prepare, __('Select a category', 'epsilon')); ?>
              </div>
            <?php } ?>
          </div>
          
          <div class="tip">
            <i class="fas fa-times close-tip"></i>
            <p><strong><?php _e('Category selection is important!', 'epsilon'); ?></strong></p>
            <p><?php _e('Selecting correct category for your item is essential part of selling process.', 'epsilon'); ?></p>
            <p><?php _e('If you select improper category, potentional buyers will not be able to find your item and it will take much more time to sell it.', 'epsilon'); ?></p>
          </div>
          
          <?php osc_run_hook('item_publish_category'); ?>
        </section>


        <section class="location">
          <h2><?php _e('Listing location', 'epsilon'); ?> <i class="show-tip fas fa-question-circle"></i></h2>

          <div class="in">
            <?php if($location_type == 0) { ?>
              <?php $countries = Country::newInstance()->listAll(); ?>
             
              <?php if(is_array($countries) && count($countries) > 1) { ?>
                <?php 
                  $regions = array();
                  if($prepare['fk_c_country_code'] <> '') {
                    $regions = Region::newInstance()->findByCountry($prepare['fk_c_country_code']); 
                  }
                ?>
                
                <div class="row country">
                  <label for="country"><?php _e('Country', 'epsilon'); ?></label>
                  <div class="input-box"><?php ItemForm::country_select($countries, $prepare); ?></div>
                </div>
                
                <div class="row region">
                  <label for="regionId"><?php _e('Region', 'epsilon'); ?></label>
                  <div class="input-box"><?php ItemForm::region_select($regions, $prepare); ?></div>
                </div>
              <?php } else { ?>
                <?php 
                  $country_code = $countries[0]['pk_c_code'];
                  $regions = array();
                  
                  if($country_code <> '') {
                    $regions = Region::newInstance()->listAll(); 
                  } else {
                    $regions = Region::newInstance()->findByCountry($country_code); 
                  }
                ?>
                <input type="hidden" id="countryId" name="countryId" value="<?php echo osc_esc_html($country_code); ?>"/>
                
                <div class="row region">
                  <label for="region"><?php _e('Region', 'epsilon'); ?></label>
                  <div class="input-box"><?php ItemForm::region_select($regions, $prepare); ?></div>
                </div>
              <?php } ?>

              <?php 
                $cities = array();
                if($prepare['fk_i_region_id'] > 0) { 
                  $cities = City::newInstance()->findByRegion($prepare['fk_i_region_id']);
                }
              ?>

              <div class="row city">
                <label for="city"><?php _e('City', 'epsilon'); ?></label>
                <div class="input-box"><?php ItemForm::city_select($cities, $prepare); ?></div>
              </div>
              
            <?php } else if($location_type == 1) { ?>
              <input type="hidden" name="countryId" id="sCountry" value="<?php echo osc_esc_html($prepare['fk_c_country_code']); ?>"/>
              <input type="hidden" name="regionId" id="sRegion" value="<?php echo osc_esc_html($prepare['fk_i_region_id']); ?>"/>
              <input type="hidden" name="cityId" id="sCity" value="<?php echo osc_esc_html($prepare['fk_i_city_id']); ?>"/>
              
              <div class="row">
                <label for="sLocation" class="auto-width"><?php _e('Location', 'epsilon'); ?></label>

                <div class="input-box picker location only-search is-publish">
                  <input name="sLocation" type="text" class="location-pick" id="sLocation" placeholder="<?php echo osc_esc_html(__('Start typing region, city...', 'epsilon')); ?>" value="<?php echo osc_esc_html($location_text); ?>" autocomplete="off"/>
                  <i class="clean fas fa-times-circle"></i>
                  <div class="results"></div>
                </div>
              </div>
            <?php } ?>

            <div class="row address">
              <label for="address"><?php _e('Address', 'epsilon'); ?></label>
              <div class="input-box"><?php ItemForm::address_text($prepare); ?></div>
            </div>
            
            <div class="row zip">
              <label for="zip"><?php _e('ZIP', 'epsilon'); ?></label>
              <div class="input-box"><?php ItemForm::zip_text($prepare); ?></div>
            </div>
            
            <div class="row location-link">
              <a class="link-update location" href="#"><?php echo (@$loc_cook['success'] == 1 ? __('Are you in different city? Update location', 'epsilon') : __('Want to sell faster? Set your preferred location', 'epsilon')); ?> &#8594;</a>
            </div>
          </div>

          <div class="tip">
            <i class="fas fa-times close-tip"></i>
            <p><strong><?php _e('Localize item', 'epsilon'); ?></strong></p>
            <p><?php _e('You should at least select region & city for your listing, so customers those search only offers in their city or region can find your listings.', 'epsilon'); ?></p>
          </div>
          
          <?php osc_run_hook('item_publish_location'); ?>
        </section>
        

        <section class="about">
          <h2><?php _e('Seller\'s information', 'epsilon'); ?> <i class="show-tip fas fa-question-circle"></i></h2>

          <div class="in">
            <div class="seller<?php if(osc_is_web_user_logged_in() ) { ?> logged<?php } ?>">
              <div class="row name">
                <a href="<?php echo (!osc_is_web_user_logged_in() ? osc_user_login_url() : osc_user_profile_url()); ?>" class="img-container" target="_blank" title="<?php echo osc_esc_html(__('Upload profile picture', 'epsilon')); ?>">
                  <img src="<?php echo eps_profile_picture(osc_is_web_user_logged_in() ? osc_logged_user_id() : NULL, 'medium'); ?>" alt="<?php echo osc_esc_html(osc_logged_user_name() <> '' ? osc_logged_user_name() : __('Non-logged user', 'epsilon')); ?>" width="36" height="36"/>
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 408c-66.2 0-120-53.8-120-120s53.8-120 120-120 120 53.8 120 120-53.8 120-120 120zm0-192c-39.7 0-72 32.3-72 72s32.3 72 72 72 72-32.3 72-72-32.3-72-72-72zm-24 72c0-13.2 10.8-24 24-24 8.8 0 16-7.2 16-16s-7.2-16-16-16c-30.9 0-56 25.1-56 56 0 8.8 7.2 16 16 16s16-7.2 16-16zm110.7-145H464v288H48V143h121.3l24-64h125.5l23.9 64zM324.3 31h-131c-20 0-37.9 12.4-44.9 31.1L136 95H48c-26.5 0-48 21.5-48 48v288c0 26.5 21.5 48 48 48h416c26.5 0 48-21.5 48-48V143c0-26.5-21.5-48-48-48h-88l-14.3-38c-5.8-15.7-20.7-26-37.4-26z"/></svg>
                </a>
                
                <label for="contactName"><?php _e('Contact Name', 'epsilon'); ?><?php if(strpos($required_fields, 'name') !== false) { ?><span class="req">*</span><?php } ?></label>
                <div class="input-box"><?php ItemForm::contact_name_text($prepare); ?></div>
              </div>
            
              <div class="row phone">
                <label for="phone"><?php _e('Phone Number', 'epsilon'); ?><?php if(strpos($required_fields, 'phone') !== false) { ?><span class="req">*</span><?php } ?></label>
                <div class="input-box">
                  <?php if(method_exists('ItemForm', 'contact_phone_text')) { ?>
                    <?php ItemForm::contact_phone_text($prepare); ?>
                  <?php } else { ?>
                    <input type="tel" id="sPhone" name="sPhone" value="<?php echo $prepare['s_phone']; ?>" />
                  <?php } ?>
                </div>
                
                <?php if(method_exists('ItemForm', 'show_phone_checkbox')) { ?>
                  <div class="mail-show">
                    <div class="input-box-check">
                      <?php ItemForm::show_phone_checkbox() ; ?>
                      <label for="showPhone" class="label-mail-show"><?php _e('Phone visible on ad', 'epsilon'); ?></label>
                    </div>
                  </div>
                <?php } ?>
              </div>

              <div class="row user-email">
                <label for="contactEmail"><?php _e('E-mail', 'epsilon'); ?> <span class="req">*</span></label>
                <div class="input-box"><?php ItemForm::contact_email_text($prepare); ?></div>

                <div class="mail-show">
                  <div class="input-box-check">
                    <?php ItemForm::show_email_checkbox() ; ?>
                    <label for="showEmail" class="label-mail-show"><?php _e('Email visible on ad', 'epsilon'); ?></label>
                  </div>
                </div>
              </div>
            </div>

            <div class="row user-link">
              <?php if(osc_is_web_user_logged_in()) { ?>
                <a class="link-update" target="_blank" href="<?php echo osc_user_profile_url(); ?>"><?php _e('Update your profile here', 'epsilon'); ?> &#8594;</a>
              <?php } else { ?>
                <a class="link-update" target="_blank" href="<?php echo osc_register_account_url(); ?>"><?php _e('Not registered yet? Create an account', 'epsilon'); ?> &#8594;</a>
              <?php } ?>
            </div>
          </div>
          
          <div class="tip">
            <i class="fas fa-times close-tip"></i>
            <p><strong><?php _e('Trusted and open seller', 'epsilon'); ?></strong></p>
            <p><?php _e('You should enter phone number, as most of solid customers prefer phone contact way.', 'epsilon'); ?></p>
            <p><?php _e('For faster listing publishing and contacting with customers, it is recommended to create and account, logged-in users are more trusted.', 'epsilon'); ?></p>
          </div>
          
          <?php osc_run_hook('item_publish_seller'); ?>
        </section>


        <section class="s4">
          <h2><?php _e('Pricing options & status', 'epsilon'); ?> <i class="show-tip fas fa-question-circle"></i></h2>

          <div class="in">
            <?php if(osc_price_enabled_at_items()) { ?>
              <label for="price"><?php _e('Price', 'epsilon'); ?> <span class="req">*</span></label>

              <div class="enter<?php if($price_type == 'FREE' || $price_type == 'CHECK') { ?> disable<?php } ?>">
                <div class="input-box">
                  <?php ItemForm::price_input_text(); ?>
                  <?php echo eps_simple_currency(); ?>
                </div>

                <div class="or"><span><?php _e('or', 'epsilon'); ?></span></div>
              </div>
              
              <div class="selection">
                <a href="#" data-price="0" class="btn btn-secondary<?php if($price_type == 'FREE') { ?> active<?php } ?>" title="<?php echo osc_esc_html(__('Item is offered for free', 'epsilon')); ?>"><i class="fas fa-hand-holding-usd"></i> <?php _e('Item for free', 'epsilon'); ?></a>
                <a href="#" data-price="" class="btn btn-secondary<?php if($price_type == 'CHECK') { ?> active<?php } ?>" title="<?php echo osc_esc_html(__('Based on agreement with seller', 'epsilon')); ?>"><i class="far fa-handshake"></i> <?php _e('Check with seller', 'epsilon'); ?></a>
              </div>
            <?php } ?>


            <!-- CONDITION & TRANSACTION -->
            <div class="status-wrap">
              <div class="transaction">
                <label for="sTransaction"><?php _e('Transaction', 'epsilon'); ?></label>
                <?php echo eps_simple_transaction(true); ?>
              </div>

              <div class="condition">
                <label for="sCondition"><?php _e('Condition', 'epsilon'); ?></label>
                <?php echo eps_simple_condition(true); ?>
              </div>
            </div>
          </div>
          
          <div class="tip">
            <i class="fas fa-times close-tip"></i>
            <p><strong><?php _e('Wisely select price', 'epsilon'); ?></strong></p>
            <p><?php _e('Price should reflect real status and properties of your listing. If you select too high price, it may take much longer to sell your product.', 'epsilon'); ?></p>
            <p><?php _e('You can also select transaction and condition to better describe status of your item and transaction you are looking for.', 'epsilon'); ?></p>
          </div>
          
          <?php osc_run_hook('item_publish_price'); ?>
        </section>


        <section class="upload-photos">
          <h2><?php _e('Photos', 'epsilon'); ?> <i class="show-tip fas fa-question-circle"></i></h2>

          <div class="box photos photoshow drag_drop in">
            <div id="photos">
              <div class="sub-label"><?php echo sprintf(__('You can upload up to %d pictures per listing', 'epsilon'), osc_max_images_per_item()); ?></div>

              <?php 
                if(osc_images_enabled_at_items()) { 
                  if(eps_ajax_image_upload()) { 
                    ItemForm::ajax_photos();
                  } 
                } 
              ?>
            </div>
          </div>
          
          <div class="tip">
            <i class="fas fa-times close-tip"></i>
            <p><strong><?php _e('Photos are selling!', 'epsilon'); ?></strong></p>
            <p><?php _e('Did you know that listing with photos sells 7x faster than one with no photo?', 'epsilon'); ?></p>
            <p><?php _e('Real product photos are key element for fast selling and helps customer to decide if product is really what they are looking for.', 'epsilon'); ?></p>
          </div>
          
          <?php osc_run_hook('item_publish_images'); ?>
        </section>


        <section class="info">
          <h2><?php _e('Listing description and attributes', 'epsilon'); ?> <i class="show-tip fas fa-question-circle"></i></h2>

          <div class="in">
            <div class="row ttle">
              <label for="title[<?php echo osc_current_user_locale(); ?>]"><?php _e('Title', 'epsilon'); ?> *</label>
              <div class="input-box">
                <?php ItemForm::title_input('title', osc_current_user_locale(), osc_esc_html(eps_post_item_title())); ?>
              </div>
            </div>
            
            <div class="row dsc">
              <label for="description[<?php echo osc_current_user_locale(); ?>]"><?php _e('Description', 'epsilon'); ?> *</label>
              <div class="td-wrap d1 input-box">
                <?php ItemForm::description_textarea('description', osc_current_user_locale(), osc_esc_html(eps_post_item_description())); ?>
              </div>
            </div>
            
            <?php osc_run_hook('item_publish_description'); ?>
          
            <div id="post-hooks" class="hooks-block"><?php if($edit) { ItemForm::plugin_edit_item(); } else { ItemForm::plugin_post_item(); } ?></div>

            <?php osc_run_hook('item_publish_hook'); ?>
          </div>
          
          <div class="tip">
            <i class="fas fa-times close-tip"></i>
            <p><strong><?php _e('Additional decription', 'epsilon'); ?></strong></p>
            <p><?php _e('Title is used in search so it is recommended to use keywords of your listing in title.', 'epsilon'); ?></p>
            <p><?php _e('Detail description will also provide customers all information they need and reduce need to contact you.', 'epsilon'); ?></p>
          </div>
        </section>


        <section class="buttons-block">
          <div class="row captcha"><?php osc_run_hook('item_publish_bottom'); eps_show_recaptcha(); ?></div>

          <button type="submit" class="btn"><?php _e('Submit', 'epsilon'); ?></button>
          
          <?php osc_run_hook('item_publish_buttons'); ?>
        </section>
        
        <?php osc_run_hook('item_publish_after'); ?>
      </form>
    </div>
  </div>

  <?php if(strpos($required_fields, 'region') !== false) { ?>
    <style>form[name="item"] .picker.location .option.country {display:none;}</style>
  <?php } ?>

  <?php if(strpos($required_fields, 'city') !== false) { ?>
    <style>form[name="item"] .picker.location .option.region, form[name="item"] .picker.location .option.country {display:none;}</style>
  <?php } ?>
  
  <script type="text/javascript">
  $(document).ready(function(){ 
    if($('select[name="countryId"]').val() == '') {
      $('select[name="regionId"]').attr('disabled', 'disabled');
    }
    
    if($('select[name="regionId"]').val() == '') {
      $('select[name="cityId"]').attr('disabled', 'disabled');
    }
    
    $('body').on('click', '.qq-upload-rotate', function(e){
      e.preventDefault();

      var img = $(this).parent().find('.ajax_preview_img img');
      var url = '<?php echo osc_current_web_theme_url('ajax-rotate.php'); ?>',
      angle = parseInt(img.attr('data-angle'));

      var imgWidth = img.width();
      var imgHeight = img.height();
      var ratio = imgWidth/imgHeight;
      var boxWidth = img.parent().width();

      if(!img.hasClass('disabled')) {

        if(isNaN(angle)){
          angle = 0
        }
         
        angle += 90;
      
        img.addClass('disabled');

        img.rotate({ 
          animateTo: angle,
          duration: 300,
          callback: function() {
            $.ajax({
              url: url,
              type: 'POST',
              data: {
                'action': 'rotate', 
                'file_name' : img.attr('alt')
              },
              success: function(response) {
                //console.log(response);
                img.removeClass('disabled');
              },
              error: function(response) {
                //console.log(response);
                img.removeClass('disabled');
              }
            });
          }
        });
      }
      
      img.attr('data-angle', angle);
    });
  });
  </script>

  <script type="text/javascript">
  $(document).ready(function(){
    $('.item-publish input[name^="title"]').attr('placeholder', '<?php echo osc_esc_js(__('Summarize your offer', 'epsilon')); ?>');
    $('.item-publish textarea[name^="description"]').attr('placeholder', '<?php echo osc_esc_js(__('Detail description of your offer', 'epsilon')); ?>');
    $('.item-publish input[name="contactPhone"]').prop('type', 'tel');

    // HIDE THEME EXTRA FIELDS (Transaction, Condition, Status) ON EXCLUDED CATEGORIES 
    var catExtraHide = new Array();
    <?php 
      $e_array = eps_extra_fields_hide();

      if(!empty($e_array) && count($e_array) > 0) {
        foreach($e_array as $e) {
          if(is_numeric($e)) {
            echo 'catExtraHide[' . $e . '] = 1;';
          }
        }
      }
    ?>

    <?php if(!$edit) { ?>
      $('input[name="showPhone"]').prop('checked', true);
    <?php } ?>
    
    <?php if(osc_is_web_user_logged_in()) { ?>
      // SET READONLY FOR EMAIL AND NAME FOR LOGGED IN USERS
      $('input[name="contactName"]').attr('readonly', true);
      $('input[name="contactEmail"]').attr('readonly', true);
    <?php } ?>


    <?php if ($edit && !osc_item_category_price_enabled(osc_item_category_id())) { ?>
       $('.post-edit .price-wrap').hide(0).addClass('hidden');
       $('#price').val('') ;
    <?php } ?>



    // JAVASCRIPT FOR PRICE ALTERNATIVES
    $('input#price').attr('autocomplete', 'off');         // Disable autocomplete for price field
    $('input#price').attr('placeholder', '<?php echo osc_esc_js(__('Price', 'epsilon')); ?>');         


    // LANGUAGE TABS
    tabberAutomatic();
    
    // Hide price on page load
    <?php if($edit) { ?>
      $('input[name="catId"], select#catId').change();
    <?php } ?>
    
    // Trigger click when category selected via flat category select
    $('body').on('click change', 'input[name="catId"], select#catId', function() {
      var cat_id = $(this).val();
      var url = '<?php echo osc_base_url(); ?>index.php';
      var result = '';

      if(cat_id > 0) {
        if(catPriceEnabled[cat_id] == 1) {
          $('.item-publish section.s4').show(0).removeClass('hidden');
        } else {
          $('.item-publish section.s4').hide(0).addClass('hidden');
          $('#price').val('') ;
        }
       

        if(catExtraHide[cat_id] == 1) {
          $('.item-publish .status-wrap').fadeOut(200).addClass('hidden');
          $('select[name="sTransaction"], select[name="sCondition"]').val('');
          $('select[name="sTransaction"] option:selected, select[name="sCondition"] option:selected').prop('selected', false)
        } else {
          $('.item-publish .status-wrap').fadeIn(200).removeClass('hidden');
        }

        // Unify selected locale for plugin data
        window.setTimeout(function() {
          var locale = currentLocaleCode;
          var localeText = currentLocale;

          if($('#plugin-hook .tabbertab').length > 0) {
            $('#plugin-hook .tabbertab').each(function() {
              if($(this).find('[id*="' + locale + '"]').length || $(this).find('h2').text() == localeText) {
                $(this).removeClass('tabbertabhide').show(0);
              } else {
                $(this).addClass('tabbertabhide').hide(0);
              }
            });
          }
        }, 200);
        
        <?php 
          // AJAX DISABLED
          // Osclass already execute these hooks as part of functions 
          // ItemForm::plugin_edit_item(), temForm::plugin_post_item()
        ?>
        <?php if(1==2 && $category_type == 1) { ?>
        <?php if($edit) { ?>
          var data = 'page=ajax&action=runhook&hook=item_edit&itemId=<?php echo osc_item_id(); ?>&catId=' + cat_id;
        <?php } else { ?>
          var data = 'page=ajax&action=runhook&hook=item_form&catId=' + cat_id;
        <?php } ?>
        
        $.ajax({
          type: "POST",
          url: url,
          data: data,
          dataType: 'html',
          success: function(data){
            $('#plugin-hook').html(data);

            // unify selected locale for plugin data
            var locale = currentLocaleCode;
            var localeText = currentLocale;

            if($('#plugin-hook .tabbertab').length > 0) {
              $('#plugin-hook .tabbertab').each(function() {
                if($(this).find('[id*="' + locale + '"]').length || $(this).find('h2').text() == localeText) {
                  $(this).removeClass('tabbertabhide').show(0);
                } else {
                  $(this).addClass('tabbertabhide').hide(0);
                }
              });
            }
          }
        });
        <?php } ?>
      }
    });



    // FIX QQ FANCY IMAGE UPLOADER BUGS
    setInterval(function(){ 
      $('input[name="qqfile"]').prop('accept', 'image/*');
      $("img[src$='uploads/temp/']").closest('.qq-upload-success').remove();

      <?php if(!function_exists('osc_image_upload_library') || osc_image_upload_library() != 'UPPY') { ?>
      $('#restricted-fine-uploader li.qq-upload-success').each(function() {
        if(!$(this).find('.qq-upload-rotate').length) {
          $(this).append('<a class="qq-upload-rotate btn" href="#" title="<?php echo osc_esc_js(__('Rotate image', 'epsilon')); ?>"><i class="fas fa-undo fa-flip-horizontal"></i></a>');
        }
      });
      <?php } ?>

      if( !$('#photos > .qq-upload-list > li').length ) {
        $('#photos > .qq-upload-list').remove();
        $('#photos > h3').remove();
      }

      if($('#post-hooks #plugin-hook').text().trim() == '') {
        $('fieldset.hook-block').hide(0);
      } else {
        $('fieldset.hook-block').show(0);
      }
      
      $('#error_list li label[for="qqfile"]').each(function() {
        if($(this).text().trim() == '<?php echo osc_esc_js(__('Please enter a value with a valid extension.')); ?>') {
          $(this).parent().remove();
        }
      });
    }, 250);



    // CATEGORY CHECK IF PARENT
    <?php if(!osc_selectable_parent_categories()) { ?>
      if(typeof window['categories_' + $('#catId').val()] !== 'undefined'){
        if(eval('categories_' + $('#catId').val()) != '') {
          $('#catId').val('');
        }
      }

      $('body').on('change', '#catId', function(){
        if(typeof window['categories_' + $(this).val()] !== 'undefined'){
          if(eval('categories_' + $(this).val()) != '') {
            $(this).val('');
          }
        }
      });
    <?php } ?>



    // Set forms to active language
    var post_timer = setInterval(eps_check_lang, 250);

    function eps_check_lang() {
      if($('.tabbertab').length > 1 && $('.tabbertab.tabbertabhide').length) {
        var l_active = currentLocale;
        l_active = l_active.trim();

        $('.tabbernav > li > a:contains("' + l_active + '")').click();

        clearInterval(post_timer);
        return;
      }
    }
  

    // Code for form validation
    $("form[name=item]").validate({
      rules: {
        "title[<?php echo osc_current_user_locale(); ?>]": {
          required: true,
          minlength: 5
        },

        "description[<?php echo osc_current_user_locale(); ?>]": {
          required: true,
          minlength: 10
        },

        <?php if(strpos($required_fields, 'country') !== false || strpos($required_fields, 'region') !== false || strpos($required_fields, 'city') !== false) { ?>
        sLocation: {
          required: true
        },
        <?php } ?>
        
        <?php if(strpos($required_fields, 'country') !== false) { ?>
        countryId: {
          required: true
        },
        <?php } ?>

        <?php if(strpos($required_fields, 'region') !== false) { ?>
        regionId: {
          required: true
        },
        <?php } ?>

        <?php if(strpos($required_fields, 'city') !== false) { ?>
        cityId: {
          required: true
        },
        <?php } ?>

        <?php if(function_exists('ir_get_min_img')) { ?>
        ir_image_check: {
          required: true,
          min: <?php echo ir_get_min_img(); ?>
        },
        <?php } ?>

        catId: {
          required: true,
          digits: true
        },
        
        <?php if($category_type == 1) { ?>
        sCategoryTerm: {
          required: true
        },
        <?php } ?>

        "photos[]": {
          accept: "png,gif,jpg,jpeg"
        },

        <?php if(strpos($required_fields, 'name') !== false) { ?>
        contactName: {
          required: true,
          minlength: 3
        },
        <?php } ?>

        <?php if(strpos($required_fields, 'transaction') !== false) { ?>
        sTransaction: {
          required: true
        },
        <?php } ?>
        
        <?php if(strpos($required_fields, 'condition') !== false) { ?>
        sCondition: {
          required: true
        },
        <?php } ?>
        
        <?php if(strpos($required_fields, 'phone') !== false) { ?>
        <?php if(method_exists('ItemForm', 'contact_phone_text')) { ?>contactPhone<?php } else { ?>sPhone<?php } ?>: {
          required: true,
          minlength: 6
        },
        <?php } ?>

        contactEmail: {
          required: true,
          email: true
        }
      },

      messages: {
        "title[<?php echo osc_current_user_locale(); ?>]": {
          required: '<?php echo osc_esc_js(__('Title: this field is required.', 'epsilon')); ?>',
          minlength: '<?php echo osc_esc_js(__('Title: enter at least 5 characters.', 'epsilon')); ?>'
        },

        "description[<?php echo osc_current_user_locale(); ?>]": {
          required: '<?php echo osc_esc_js(__('Description: this field is required.', 'epsilon')); ?>',
          minlength: '<?php echo osc_esc_js(__('Description: enter at least 10 characters.', 'epsilon')); ?>'
        },

        <?php if(strpos($required_fields, 'country') !== false || strpos($required_fields, 'region') !== false || strpos($required_fields, 'city') !== false) { ?>
        sLocation: {
          required: '<?php echo osc_esc_js(__('Location: select country, region or city from location select box.', 'epsilon')); ?>'
        },
        <?php } ?>
        
        <?php if(strpos($required_fields, 'country') !== false) { ?>
        countryId: {
          required: '<?php echo osc_esc_js(__('Location: select country from location field.', 'epsilon')); ?>'
        },
        <?php } ?>

        <?php if(strpos($required_fields, 'region') !== false) { ?>
        regionId: {
          required: '<?php echo osc_esc_js(__('Location: select region from location field.', 'epsilon')); ?>'
        },
        <?php } ?>

        <?php if(strpos($required_fields, 'city') !== false) { ?>
        cityId: {
          required: '<?php echo osc_esc_js(__('Location: select city from location field.', 'epsilon')); ?>'
        },
        <?php } ?>

        <?php if(function_exists('ir_get_min_img')) { ?>
        ir_image_check: {
          required: '<?php echo osc_esc_js(__('Pictures: you need to upload pictures.', 'epsilon')); ?>',
          min: '<?php echo osc_esc_js(sprintf(__('Pictures: upload at least %d picture(s)', 'epsilon'), ir_get_min_img())); ?>'
        },
        <?php } ?>

        catId: '<?php echo osc_esc_js(__('Category: this field is required.', 'epsilon')); ?>',

        <?php if($category_type == 1) { ?>
          sCategoryTerm: '<?php echo osc_esc_js(__('Category: this field is required.', 'epsilon')); ?>',
        <?php } ?>
        
        "photos[]": {
          accept: '<?php echo osc_esc_js(__('Photo: must be png,gif,jpg,jpeg.', 'epsilon')); ?>'
        },

        <?php if(strpos($required_fields, 'transaction') !== false) { ?>
        sTransaction: {
          required: '<?php echo osc_esc_js(__('Transaction: this field is required.', 'epsilon')); ?>'
        },
        <?php } ?>
        
        <?php if(strpos($required_fields, 'condition') !== false) { ?>
        sCondition: {
          required: '<?php echo osc_esc_js(__('Condition: this field is required.', 'epsilon')); ?>'
        },
        <?php } ?>
        
        <?php if(strpos($required_fields, 'phone') !== false) { ?>
        <?php if(method_exists('ItemForm', 'contact_phone_text')) { ?>contactPhone<?php } else { ?>sPhone<?php } ?>: {
          required: '<?php echo osc_esc_js(__('Phone: this field is required.', 'epsilon')); ?>',
          minlength: '<?php echo osc_esc_js(__('Phone: enter at least 6 characters.', 'epsilon')); ?>'
        },
        <?php } ?>

        <?php if(strpos($required_fields, 'name') !== false) { ?>
        contactName: {
          required: '<?php echo osc_esc_js(__('Your Name: this field is required.', 'epsilon')); ?>',
          minlength: '<?php echo osc_esc_js(__('Your Name: enter at least 3 characters.', 'epsilon')); ?>'
        },
        <?php } ?>

        contactEmail: {
          required: '<?php echo osc_esc_js(__('Email: this field is required.', 'epsilon')); ?>',
          email: '<?php echo osc_esc_js(__('Email: invalid format of email address.', 'epsilon')); ?>'
        }
      }, 

      ignore: ":disabled, :hidden, .ignore",
      ignoreTitle: false,
      errorLabelContainer: "#error_list",
      wrapper: "li",
      invalidHandler: function(form, validator) {
        $('html,body').animate({ scrollTop: $('body').offset().top}, { duration: 250, easing: 'swing'});
      },
      submitHandler: function(form){
        $('button[type=submit], input[type=submit]').attr('disabled', 'disabled');
        form.submit();
      }
    });
  });
  </script>


  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>	