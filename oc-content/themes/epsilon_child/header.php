<?php 
  osc_goto_first_locale(); 
  
  $loc = (osc_get_osclass_location() == '' ? 'home' : osc_get_osclass_location());
  $sec = (osc_get_osclass_section() == '' ? 'default' : osc_get_osclass_section());
  
  $location_cookie = eps_location_from_cookies();

  $mes_counter = eps_count_messages(osc_logged_user_id()); 
  $fav_counter = eps_count_favorite();
?>

<!-- PNGMARKET STAGE HEADER TEST -->

<header>
  <?php osc_run_hook('header_top'); ?>
  
  <div class="container cmain">
    <a class="menu btn btn-white isMobile" href="#">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="20" height="20"><path d="M0 96C0 78.33 14.33 64 32 64H416C433.7 64 448 78.33 448 96C448 113.7 433.7 128 416 128H32C14.33 128 0 113.7 0 96zM0 256C0 238.3 14.33 224 32 224H416C433.7 224 448 238.3 448 256C448 273.7 433.7 288 416 288H32C14.33 288 0 273.7 0 256zM416 448H32C14.33 448 0 433.7 0 416C0 398.3 14.33 384 32 384H416C433.7 384 448 398.3 448 416C448 433.7 433.7 448 416 448z"/></svg>
    </a>
    
   <a href="<?php echo osc_base_url(); ?>" class="logo"><?php echo eps_logo(); ?></a>

    <div class="links">
      <a class="publish btn" href="<?php echo osc_item_post_url(); ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="24" height="24"><path d="M352 240v32c0 6.6-5.4 12-12 12h-88v88c0 6.6-5.4 12-12 12h-32c-6.6 0-12-5.4-12-12v-88h-88c-6.6 0-12-5.4-12-12v-32c0-6.6 5.4-12 12-12h88v-88c0-6.6 5.4-12 12-12h32c6.6 0 12 5.4 12 12v88h88c6.6 0 12 5.4 12 12zm96-160v352c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V80c0-26.5 21.5-48 48-48h352c26.5 0 48 21.5 48 48zm-48 346V86c0-3.3-2.7-6-6-6H54c-3.3 0-6 2.7-6 6v340c0 3.3 2.7 6 6 6h340c3.3 0 6-2.7 6-6z"/></svg>
        <?php _e('Place an ad', 'epsilon'); ?>
      </a>
      
      <a class="publish btn mini" href="<?php echo osc_item_post_url(); ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="20" height="20"><path d="M416 208H272V64c0-17.67-14.33-32-32-32h-32c-17.67 0-32 14.33-32 32v144H32c-17.67 0-32 14.33-32 32v32c0 17.67 14.33 32 32 32h144v144c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32V304h144c17.67 0 32-14.33 32-32v-32c0-17.67-14.33-32-32-32z"/></svg>
      </a>

      <?php if (osc_is_web_user_logged_in()) { ?>
        <?php $pngm_notif_count = function_exists('pngm_notification_count') ? pngm_notification_count() : 0; ?>
        <a class="pngm-notify btn btn-white isMobile isTablet isDesktop" href="<?php echo function_exists('pngm_notification_url') ? pngm_notification_url() : osc_user_alerts_url(); ?>" title="<?php echo osc_esc_html(__('Activity', 'epsilon')); ?>" aria-label="<?php echo osc_esc_html(__('Activity', 'epsilon')); ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="18" height="18" aria-hidden="true"><path d="M224 0c-17.7 0-32 14.3-32 32V51.2C119 66 64 130.6 64 208v18.8c0 47-17.3 92.4-48.5 127.6l-7.4 8.3c-8.4 9.4-10.4 22.9-5.3 34.4S19.3 416 32 416H416c12.7 0 24.2-7.7 29.2-19.3s3.1-25-5.3-34.4l-7.4-8.3C401.3 319.2 384 273.9 384 226.8V208c0-77.4-55-142-128-156.8V32c0-17.7-14.3-32-32-32zm45.3 493.3c12-12 18.7-28.3 18.7-45.3H160c0 17 6.7 33.3 18.7 45.3s28.3 18.7 45.3 18.7s33.3-6.7 45.3-18.7z"/></svg>
          <span class="counter" data-pngm-badge="notifications"<?php echo $pngm_notif_count > 0 ? '' : ' hidden'; ?>><?php echo (int) $pngm_notif_count; ?></span>
        </a>
      <?php } ?>
      
      <a class="account btn btn-white" href="<?php echo (!osc_is_web_user_logged_in() ? osc_user_login_url() : osc_user_dashboard_url()); ?>">
        <?php if(!osc_is_web_user_logged_in()) { ?>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512" width="18" height="18"><path d="M248 104c-53 0-96 43-96 96s43 96 96 96 96-43 96-96-43-96-96-96zm0 144c-26.5 0-48-21.5-48-48s21.5-48 48-48 48 21.5 48 48-21.5 48-48 48zm0-240C111 8 0 119 0 256s111 248 248 248 248-111 248-248S385 8 248 8zm0 448c-49.7 0-95.1-18.3-130.1-48.4 14.9-23 40.4-38.6 69.6-39.5 20.8 6.4 40.6 9.6 60.5 9.6s39.7-3.1 60.5-9.6c29.2 1 54.7 16.5 69.6 39.5-35 30.1-80.4 48.4-130.1 48.4zm162.7-84.1c-24.4-31.4-62.1-51.9-105.1-51.9-10.2 0-26 9.6-57.6 9.6-31.5 0-47.4-9.6-57.6-9.6-42.9 0-80.6 20.5-105.1 51.9C61.9 339.2 48 299.2 48 256c0-110.3 89.7-200 200-200s200 89.7 200 200c0 43.2-13.9 83.2-37.3 115.9z"/></svg>
        <?php } else { ?>
          <img src="<?php echo eps_profile_picture(osc_logged_user_id(), 'medium'); ?>" alt="<?php echo osc_esc_html(osc_logged_user_name()); ?>" width="32" height="32"/>
        <?php } ?>
        
        <?php echo (osc_is_web_user_logged_in() ? __('My Account', 'epsilon') : __('Sign in / Register', 'epsilon')); ?>
      </a>

      <a class="maccount btn btn-white isMobile" href="<?php echo (!osc_is_web_user_logged_in() ? osc_user_login_url() : osc_user_dashboard_url()); ?>">
        <img src="<?php echo eps_profile_picture(osc_is_web_user_logged_in() ? osc_logged_user_id() : NULL, 'small'); ?>" alt="<?php echo osc_esc_html(osc_logged_user_name() <> '' ? osc_logged_user_name() : __('Non-logged user', 'epsilon')); ?>" width="36" height="36"/>
      </a>
      
      <?php if(eps_param('default_location') == 1) { ?>
        <a class="location btn btn-white<?php echo ($location_cookie['success'] === true ? ' active' : ''); ?>" href="#" title="<?php
          if(@$location_cookie['success'] !== true) {
            echo osc_esc_html(__('Location', 'epsilon'));
          } else {
            echo osc_esc_html(@$location_cookie['s_location'] <> '' ? osc_location_native_name_selector($location_cookie, 's_name') : __('Location', 'epsilon'));
          }
        ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512" width="18" height="18"><path d="M347.94 129.86L203.6 195.83a31.938 31.938 0 0 0-15.77 15.77l-65.97 144.34c-7.61 16.65 9.54 33.81 26.2 26.2l144.34-65.97a31.938 31.938 0 0 0 15.77-15.77l65.97-144.34c7.61-16.66-9.54-33.81-26.2-26.2zm-77.36 148.72c-12.47 12.47-32.69 12.47-45.16 0-12.47-12.47-12.47-32.69 0-45.16 12.47-12.47 32.69-12.47 45.16 0 12.47 12.47 12.47 32.69 0 45.16zM248 8C111.03 8 0 119.03 0 256s111.03 248 248 248 248-111.03 248-248S384.97 8 248 8zm0 448c-110.28 0-200-89.72-200-200S137.72 56 248 56s200 89.72 200 200-89.72 200-200 200z"/></svg>
          <span class="pngm-loc-label">
            <?php 
              if(@$location_cookie['success'] !== true) {
                _e('Location', 'epsilon');
              } else {
                echo @$location_cookie['s_location'] <> '' ? osc_location_native_name_selector($location_cookie, 's_name') : __('Location', 'epsilon');
              }
            ?>
          </span>
        </a>
      <?php } ?>
      
      <a class="search btn btn-white isMobile" href="<?php echo osc_search_page(array('page' => 'search')); ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="18" height="18"><path d="M500.3 443.7l-119.7-119.7c27.22-40.41 40.65-90.9 33.46-144.7C401.8 87.79 326.8 13.32 235.2 1.723C99.01-15.51-15.51 99.01 1.724 235.2c11.6 91.64 86.08 166.7 177.6 178.9c53.8 7.189 104.3-6.236 144.7-33.46l119.7 119.7c15.62 15.62 40.95 15.62 56.57 0C515.9 484.7 515.9 459.3 500.3 443.7zM79.1 208c0-70.58 57.42-128 128-128s128 57.42 128 128c0 70.58-57.42 128-128 128S79.1 278.6 79.1 208z"/></svg>
      </a>
      
      <?php if(function_exists('im_messages')) { ?>
        <a class="messages btn btn-white" href="<?php echo osc_route_url('im-threads'); ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="18" height="18"><path d="M448 0H64C28.7 0 0 28.7 0 64v288c0 35.3 28.7 64 64 64h96v84c0 7.1 5.8 12 12 12 2.4 0 4.9-.7 7.1-2.4L304 416h144c35.3 0 64-28.7 64-64V64c0-35.3-28.7-64-64-64zm16 352c0 8.8-7.2 16-16 16H288l-12.8 9.6L208 428v-60H64c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16h384c8.8 0 16 7.2 16 16v288zm-96-216H144c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h224c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16zm-96 96H144c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h128c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16z"/></svg>
          <?php _e('Messages', 'epsilon'); ?>

          <span class="counter" data-pngm-badge="messages"<?php echo $mes_counter > 0 ? '' : ' hidden'; ?>><?php echo (int) $mes_counter; ?></span>
        </a>
      <?php } ?>

      <?php if(function_exists('fi_make_favorite')) { ?>
        <a class="favorite btn btn-white" href="<?php echo osc_route_url('favorite-lists'); ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" height="18" width="18"><path d="M287.9 0C297.1 0 305.5 5.25 309.5 13.52L378.1 154.8L531.4 177.5C540.4 178.8 547.8 185.1 550.7 193.7C553.5 202.4 551.2 211.9 544.8 218.2L433.6 328.4L459.9 483.9C461.4 492.9 457.7 502.1 450.2 507.4C442.8 512.7 432.1 513.4 424.9 509.1L287.9 435.9L150.1 509.1C142.9 513.4 133.1 512.7 125.6 507.4C118.2 502.1 114.5 492.9 115.1 483.9L142.2 328.4L31.11 218.2C24.65 211.9 22.36 202.4 25.2 193.7C28.03 185.1 35.5 178.8 44.49 177.5L197.7 154.8L266.3 13.52C270.4 5.249 278.7 0 287.9 0L287.9 0zM287.9 78.95L235.4 187.2C231.9 194.3 225.1 199.3 217.3 200.5L98.98 217.9L184.9 303C190.4 308.5 192.9 316.4 191.6 324.1L171.4 443.7L276.6 387.5C283.7 383.7 292.2 383.7 299.2 387.5L404.4 443.7L384.2 324.1C382.9 316.4 385.5 308.5 391 303L476.9 217.9L358.6 200.5C350.7 199.3 343.9 194.3 340.5 187.2L287.9 78.95z"/></svg>
          <?php _e('Favorite', 'epsilon'); ?>

          <?php if($fav_counter > 0) { ?>
            <span class="counter"><?php echo $fav_counter; ?></span>
          <?php } ?>
        </a>
        
      <?php } else if (function_exists('svi_save_btn')) { ?>
        <a class="favorite btn btn-white svi-show-saved" href="#">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" height="18" width="18"><path d="M287.9 0C297.1 0 305.5 5.25 309.5 13.52L378.1 154.8L531.4 177.5C540.4 178.8 547.8 185.1 550.7 193.7C553.5 202.4 551.2 211.9 544.8 218.2L433.6 328.4L459.9 483.9C461.4 492.9 457.7 502.1 450.2 507.4C442.8 512.7 432.1 513.4 424.9 509.1L287.9 435.9L150.1 509.1C142.9 513.4 133.1 512.7 125.6 507.4C118.2 502.1 114.5 492.9 115.1 483.9L142.2 328.4L31.11 218.2C24.65 211.9 22.36 202.4 25.2 193.7C28.03 185.1 35.5 178.8 44.49 177.5L197.7 154.8L266.3 13.52C270.4 5.249 278.7 0 287.9 0L287.9 0zM287.9 78.95L235.4 187.2C231.9 194.3 225.1 199.3 217.3 200.5L98.98 217.9L184.9 303C190.4 308.5 192.9 316.4 191.6 324.1L171.4 443.7L276.6 387.5C283.7 383.7 292.2 383.7 299.2 387.5L404.4 443.7L384.2 324.1C382.9 316.4 385.5 308.5 391 303L476.9 217.9L358.6 200.5C350.7 199.3 343.9 194.3 340.5 187.2L287.9 78.95z"/></svg>
          <?php _e('Saved', 'epsilon'); ?>

          <?php if($fav_counter > 0) { ?>
            <span class="counter"><?php echo $fav_counter; ?></span>
          <?php } ?>
        </a>
        
      <?php } ?>
      
      <div class="divider">&nbsp;</div>
      
      <?php if(function_exists('bpr_companies_url')) { ?>
        <a class="company btn btn-white" href="<?php echo bpr_companies_url(); ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="18" height="18"><path d="M436 480h-20V24c0-13.255-10.745-24-24-24H56C42.745 0 32 10.745 32 24v456H12c-6.627 0-12 5.373-12 12v20h448v-20c0-6.627-5.373-12-12-12zM128 76c0-6.627 5.373-12 12-12h40c6.627 0 12 5.373 12 12v40c0 6.627-5.373 12-12 12h-40c-6.627 0-12-5.373-12-12V76zm0 96c0-6.627 5.373-12 12-12h40c6.627 0 12 5.373 12 12v40c0 6.627-5.373 12-12 12h-40c-6.627 0-12-5.373-12-12v-40zm84.273 268c-5.898 2.806-12.402 2.198-17.724-1.598L128 386.77V480H96V305.94l113.448-65.444c9.023-5.205 20.555-2.11 25.76 6.913l33.073 57.294 70.218-40.505c8.783-5.066 20.124-2.052 25.19 6.731l28.465 49.321V480h-32V388.23l-47.515 27.4-64.212 37.37zM320 268c0-6.627 5.373-12 12-12h40c6.627 0 12 5.373 12 12v40c0 6.627-5.373 12-12 12h-40c-6.627 0-12-5.373-12-12v-40zm0-96c0-6.627 5.373-12 12-12h40c6.627 0 12 5.373 12 12v40c0 6.627-5.373 12-12 12h-40c-6.627 0-12-5.373-12-12v-40zm0-96c0-6.627 5.373-12 12-12h40c6.627 0 12 5.373 12 12v40c0 6.627-5.373 12-12 12h-40c-6.627 0-12-5.373-12-12V76z"/></svg>
          <?php _e('Companies', 'epsilon'); ?>
        </a>
      <?php } ?>

      <?php if(function_exists('frm_home')) { ?>
        <a class="forum btn btn-white noicon" href="<?php echo frm_home(); ?>"><?php _e('Forums', 'epsilon'); ?></a>
      <?php } ?>
    
      <?php if(function_exists('blg_home_link')) { ?>
        <a class="blog btn btn-white noicon" href="<?php echo blg_home_link(); ?>"><?php _e('Blog', 'epsilon'); ?></a>
      <?php } ?>
     
      <?php if(function_exists('faq_home_link')) { ?>
        <a class="faq btn btn-white noicon" href="<?php echo faq_home_link(); ?>"><?php _e('FAQ', 'epsilon'); ?></a>
      <?php } ?>
      
      <?php osc_run_hook('header_links'); ?>
    </div>
  </div>
  
  <div class="container alt csearch" style="display:none;">
    <a href="#" class="back btn btn-white"><i class="fas fa-chevron-left"></i></a>
    
    <form action="<?php echo osc_base_url(true); ?>" method="GET" class="nocsrf">
      <input type="hidden" name="page" value="search" />

      <?php // Keyword search is always nationwide — default location is not applied here. ?>

      <div class="picker pattern mobile">
        <div class="input-box">
          <input type="text" name="sPattern" class="pattern" placeholder="<?php _e('Enter keyword...', 'epsilon'); ?>" value="<?php echo osc_esc_html(Params::getParam('sPattern')); ?>" autocomplete="off"/>
          <i class="clean fas fa-times-circle"></i>
        </div>
        
        <div class="results">
          <div class="loaded"></div>
          <div class="default"><?php eps_default_pattern_content(); ?></div>
        </div>
      </div>
      
      <button class="btn" type="submit"><i class="fa fa-search"></i></button>
    </form>
  </div>
  
 <?php if(osc_is_ad_page()) { ?>
    <div class="container alt citem hidden" style="display:none;">
      <!--<a href="#" class="back btn btn-white" onclick="history.back();"><i class="fas fa-chevron-left"></i></a>-->
      <img class="img" src="<?php echo (osc_count_item_resources() > 0 ? osc_resource_thumbnail_url() : eps_get_noimage()); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" />

      <div class="data">
        <strong>
          <span class="title"><?php echo osc_item_title(); ?></span>
          <span class="price"><?php echo osc_item_formated_price(); ?></span>
        </strong>
        <div>
          <span><?php echo sprintf(__('Posted %s', 'epsilon'), eps_smart_date(osc_item_pub_date())); ?></span>
          <span><?php echo osc_item_category(); ?></span>
          <span><?php echo eps_item_location(); ?></span>
        </div>
      </div>
    </div>
  <?php } ?>

  <?php if(osc_is_search_page()) { ?>
    <div class="container alt cresults hidden" style="display:none;">
      <a href="#" class="action open-filters btn btn-white"><i class="fas fa-sliders-h"></i></a>

      <div class="data">
        <strong><?php echo sprintf(__('%s results found', 'epsilon'), osc_search_total_items()); ?></strong>
        <div class="filts">
          <?php $params = eps_search_param_remove(); ?>
          <?php if(is_array($params) && count($params) > 0) { ?>
            <?php foreach($params as $p) { ?>
              <?php if(trim((string)$p['name']) != '') { ?>
                <span><?php echo $p['name']; ?></span>
              <?php } ?>
            <?php } ?>
          <?php } ?>
        </div>
      </div>
    </div>
  <?php } ?>
  
  <?php osc_run_hook('header_bottom'); ?>
</header>

<!-- Global search lives OUTSIDE <header> (SEARCH-02 / SEARCH-03). -->
<div class="pngm-global-search<?php if(osc_is_home_page()) { ?> is-home<?php } ?>">
  <div class="container">
    <form action="<?php echo osc_base_url(true); ?>" method="GET" class="nocsrf global-search-form">
      <input type="hidden" name="page" value="search" />

      <?php // Keyword search is always nationwide — default location is not applied here. ?>

      <div class="picker pattern global">
        <div class="input-box">
          <svg class="global-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="18" height="18" aria-hidden="true">
            <path d="M500.3 443.7l-119.7-119.7c27.22-40.41 40.65-90.9 33.46-144.7C401.8 87.79 326.8 13.32 235.2 1.723C99.01-15.51-15.51 99.01 1.724 235.2c11.6 91.64 86.08 166.7 177.6 178.9c53.8 7.189 104.3-6.236 144.7-33.46l119.7 119.7c15.62 15.62 40.95 15.62 56.57 0C515.9 484.7 515.9 459.3 500.3 443.7zM79.1 208c0-70.58 57.42-128 128-128s128 57.42 128 128c0 70.58-57.42 128-128 128S79.1 278.6 79.1 208z"/>
          </svg>

          <input
            type="text"
            name="sPattern"
            class="pattern"
            placeholder="<?php _e('Search cars, phones, furniture...', 'epsilon'); ?>"
            value="<?php echo osc_esc_html(Params::getParam('sPattern')); ?>"
            autocomplete="off"
            aria-label="<?php echo osc_esc_html(__('Search listings', 'epsilon')); ?>"
          />

          <i class="clean fas fa-times-circle"></i>
        </div>

        <div class="results">
          <div class="loaded"></div>
          <div class="default"><?php eps_default_pattern_content(); ?></div>
        </div>
      </div>

      <button class="btn global-search-submit" type="submit" aria-label="<?php echo osc_esc_html(__('Search', 'epsilon')); ?>">
        <i class="fa fa-search"></i>
        <span><?php _e('Search', 'epsilon'); ?></span>
      </button>
    </form>
  </div>
</div>

<?php osc_run_hook('header_after'); ?>

<?php
  osc_show_widgets('header');
  $breadcrumb = osc_breadcrumb('>', false);
  $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" height="14" width="16"><path d="M575.8 255.5C575.8 273.5 560.8 287.6 543.8 287.6H511.8L512.5 447.7C512.5 450.5 512.3 453.1 512 455.8V472C512 494.1 494.1 512 472 512H456C454.9 512 453.8 511.1 452.7 511.9C451.3 511.1 449.9 512 448.5 512H392C369.9 512 352 494.1 352 472V384C352 366.3 337.7 352 320 352H256C238.3 352 224 366.3 224 384V472C224 494.1 206.1 512 184 512H128.1C126.6 512 125.1 511.9 123.6 511.8C122.4 511.9 121.2 512 120 512H104C81.91 512 64 494.1 64 472V360C64 359.1 64.03 358.1 64.09 357.2V287.6H32.05C14.02 287.6 0 273.5 0 255.5C0 246.5 3.004 238.5 10.01 231.5L266.4 8.016C273.4 1.002 281.4 0 288.4 0C295.4 0 303.4 2.004 309.5 7.014L564.8 231.5C572.8 238.5 576.9 246.5 575.8 255.5L575.8 255.5z"/></svg>';
  $breadcrumb = str_replace('<span itemprop="title">' . osc_page_title() . '</span>', '<span itemprop="title" class="home">' . $svg . ' <span>' . __('Home', 'epsilon') . '</span></span>', $breadcrumb);
  $breadcrumb = str_replace('<span itemprop="name">' . osc_page_title() . '</span>', '<span itemprop="name" class="home">' . $svg . ' <span>' . __('Home', 'epsilon') . '</span></span>', $breadcrumb);

  if(osc_is_ad_page()) {
    $breadcrumb = str_replace('<span itemprop="name">' . osc_item_title() . '</span>', '<span itemprop="name">' . osc_item_title() . ', <b>' . osc_item_formated_price() . '</b></span>', $breadcrumb);
  }
?>

<div class="content loc-<?php echo $loc; ?> sec-<?php echo $sec; ?><?php if($breadcrumb == '') { ?> no-breadcrumbs<?php } ?>">

<?php if($breadcrumb != '') { ?>
  <div id="breadcrumbs" class="container">
    <div class="bread-text"><?php echo $breadcrumb; ?></div>
    
    <?php if(osc_is_ad_page()) { ?>
      <?php
        $next_link = eps_next_prev_item('next', osc_item_category_id(), osc_item_id());
        $prev_link = eps_next_prev_item('prev', osc_item_category_id(), osc_item_id());
      ?>
      
      <div class="navlinks">
        <?php if($prev_link !== false) { ?><a href="<?php echo $prev_link; ?>" class="prev"><i class="fas fa-angle-left"></i> <?php _e('Previous', 'epsilon'); ?></a><?php } ?>
        <?php if($next_link !== false) { ?><a href="<?php echo $next_link; ?>" class="next"><?php _e('Next', 'epsilon'); ?> <i class="fas fa-angle-right"></i></a><?php } ?>
      </div>
    <?php } else if(osc_get_osclass_location() == 'user' && osc_get_osclass_section() == 'pub_profile') { ?>
      <?php
        $next_link = eps_next_prev_user('next', osc_user_id());
        $prev_link = eps_next_prev_user('prev', osc_user_id());
      ?>
      
      <div class="navlinks">
        <?php if($prev_link !== false) { ?><a href="<?php echo $prev_link; ?>" class="prev"><i class="fas fa-angle-left"></i> <?php _e('Previous', 'epsilon'); ?></a><?php } ?>
        <?php if($next_link !== false) { ?><a href="<?php echo $next_link; ?>" class="next"><?php _e('Next', 'epsilon'); ?> <i class="fas fa-angle-right"></i></a><?php } ?>
      </div>
    <?php } ?>
  </div>
<?php } ?>

<div id="flashbox" class="container"><div class="wrap"><div class="wrap2"><?php osc_show_flash_message(); ?></div></div></div>