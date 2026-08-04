<?php
  require_once 'functions.php';

  // Create menu
  $title = __('Configure', 'epsilon');
  eps_menu($title);


  // GET & UPDATE PARAMETERS
  // $variable = eps_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check, value or code

  $default_location = eps_param_update('default_location', 'theme_action', 'check', 'theme-epsilon');

  $enable_custom_color = eps_param_update('enable_custom_color', 'theme_action', 'check', 'theme-epsilon');
  $enable_dark_mode = eps_param_update('enable_dark_mode', 'theme_action', 'check', 'theme-epsilon');
  $default_mode = eps_param_update('default_mode', 'theme_action', 'value', 'theme-epsilon');
  $color = eps_param_update('color', 'theme_action', 'value', 'theme-epsilon');
  $color_dark = eps_param_update('color_dark', 'theme_action', 'value', 'theme-epsilon');
  
  $enable_custom_font = eps_param_update('enable_custom_font', 'theme_action', 'check', 'theme-epsilon');
  $font_name = eps_param_update('font_name', 'theme_action', 'value', 'theme-epsilon');
  $font_url = eps_param_update('font_url', 'theme_action', 'value', 'theme-epsilon');

  $publish_category = eps_param_update('publish_category', 'theme_action', 'value', 'theme-epsilon');
  $publish_location = eps_param_update('publish_location', 'theme_action', 'value', 'theme-epsilon');
  $profile_location = eps_param_update('profile_location', 'theme_action', 'value', 'theme-epsilon');
  $site_phone = eps_param_update('site_phone', 'theme_action', 'value', 'theme-epsilon');
  $site_email = eps_param_update('site_email', 'theme_action', 'value', 'theme-epsilon');
  $site_name = eps_param_update('site_name', 'theme_action', 'value', 'theme-epsilon');
  $site_address = eps_param_update('site_address', 'theme_action', 'value', 'theme-epsilon');
  $def_view = eps_param_update('def_view', 'theme_action', 'value', 'theme-epsilon');
  $def_design = eps_param_update('def_design', 'theme_action', 'value', 'theme-epsilon');
  $enable_day_offer = eps_param_update('enable_day_offer', 'theme_action', 'check', 'theme-epsilon');
  $generate_favicons = eps_param_update('generate_favicons', 'theme_action', 'check', 'theme-epsilon');
  $sample_favicons = eps_param_update('sample_favicons', 'theme_action', 'check', 'theme-epsilon');
  $day_offer_admin_id = eps_param_update('day_offer_admin_id', 'theme_action', 'value', 'theme-epsilon');
  $search_premium_promote_url = eps_param_update('search_premium_promote_url', 'theme_action', 'value', 'theme-epsilon');
  
  if(Params::getParam('theme_action') == 'done') {
    //osc_set_preference('day_offer_id', '', 'theme-epsilon');
    eps_manage_day_offer();
  }    
  
  $categories_new = eps_param_update('categories_new', 'theme_action', 'value', 'theme-epsilon');
  $categories_hot = eps_param_update('categories_hot', 'theme_action', 'value', 'theme-epsilon');

  $premium_home = eps_param_update('premium_home', 'theme_action', 'check', 'theme-epsilon');
  $location_home = eps_param_update('location_home', 'theme_action', 'check', 'theme-epsilon');

  $premium_home_count = eps_param_update('premium_home_count', 'theme_action', 'value', 'theme-epsilon');
  $premium_search = eps_param_update('premium_search', 'theme_action', 'check', 'theme-epsilon');
  $premium_search_count = eps_param_update('premium_search_count', 'theme_action', 'value', 'theme-epsilon');
  $premium_home_design = eps_param_update('premium_home_design', 'theme_action', 'value', 'theme-epsilon');
  $premium_search_design = eps_param_update('premium_search_design', 'theme_action', 'value', 'theme-epsilon');
  $premium_home_category = eps_param_update('premium_home_category', 'theme_action', 'value', 'theme-epsilon');
  $premium_home_sort = eps_param_update('premium_home_sort', 'theme_action', 'value', 'theme-epsilon');

  $footer_link = eps_param_update('footer_link', 'theme_action', 'check', 'theme-epsilon');
  $def_cur = eps_param_update('def_cur', 'theme_action', 'value', 'theme-epsilon');
  $latest_random = eps_param_update('latest_random', 'theme_action', 'check', 'theme-epsilon');
  $latest_picture = eps_param_update('latest_picture', 'theme_action', 'check', 'theme-epsilon');
  $latest_premium = eps_param_update('latest_premium', 'theme_action', 'check', 'theme-epsilon');
  $latest_category = eps_param_update('latest_category', 'theme_action', 'value', 'theme-epsilon');
  $latest_design = eps_param_update('latest_design', 'theme_action', 'value', 'theme-epsilon');

  $search_ajax = eps_param_update('search_ajax', 'theme_action', 'check', 'theme-epsilon');
  $post_required = eps_param_update('post_required', 'theme_action', 'value', 'theme-epsilon');
  $post_extra_exclude = eps_param_update('post_extra_exclude', 'theme_action', 'value', 'theme-epsilon');

  $lazy_load = eps_param_update('lazy_load', 'theme_action', 'value', 'theme-epsilon');
  $location_pick = eps_param_update('location_pick', 'theme_action', 'check', 'theme-epsilon');
  $public_items = eps_param_update('public_items', 'theme_action', 'value', 'theme-epsilon');
  $alert_items = eps_param_update('alert_items', 'theme_action', 'value', 'theme-epsilon');
  $preview = eps_param_update('preview', 'theme_action', 'check', 'theme-epsilon');
  $def_locations = eps_param_update('def_locations', 'theme_action', 'value', 'theme-epsilon');
  $shorten_description = eps_param_update('shorten_description', 'theme_action', 'check', 'theme-epsilon');
  $interactive_title = eps_param_update('interactive_title', 'theme_action', 'check', 'theme-epsilon');
  $gallery_ratio = eps_param_update('gallery_ratio', 'theme_action', 'value', 'theme-epsilon');

  $users_home = eps_param_update('users_home', 'theme_action', 'check', 'theme-epsilon');
  $users_home_count = eps_param_update('users_home_count', 'theme_action', 'value', 'theme-epsilon');
  $asset_versioning = eps_param_update('asset_versioning', 'theme_action', 'check', 'theme-epsilon');

  $footer_social_define = eps_param_update('footer_social_define', 'theme_action', 'check', 'theme-epsilon');
  $footer_social_whatsapp = eps_param_update('footer_social_whatsapp', 'theme_action', 'value', 'theme-epsilon');
  $footer_social_facebook = eps_param_update('footer_social_facebook', 'theme_action', 'value', 'theme-epsilon');
  $footer_social_pinterest = eps_param_update('footer_social_pinterest', 'theme_action', 'value', 'theme-epsilon');
  $footer_social_instagram = eps_param_update('footer_social_instagram', 'theme_action', 'value', 'theme-epsilon');
  $footer_social_x = eps_param_update('footer_social_x', 'theme_action', 'value', 'theme-epsilon');
  $footer_social_linkedin = eps_param_update('footer_social_linkedin', 'theme_action', 'value', 'theme-epsilon');


  if(Params::getParam('theme_action') == 'done') {
    osc_add_flash_ok_message(__('Settings were successfully saved','epsilon'), 'admin');
    header('Location:' . osc_admin_render_theme_url('oc-content/themes/epsilon/admin/configure.php'));
    exit;
  }


  $latest_category_array = explode(',', $latest_category);
  $premium_home_category_array = explode(',', $premium_home_category);
  $post_extra_exclude_array = explode(',', $post_extra_exclude);
  $post_required_array = explode(',', $post_required);
  $categories_new_array = explode(',', $categories_new);
  $categories_hot_array = explode(',', $categories_hot);

?>


<div class="mb-body">

 
  <!-- CONFIGURE SECTION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-wrench"></i> <?php _e('Configure', 'epsilon'); ?></div>

    <div class="mb-inside mb-minify">
      <form action="<?php echo osc_admin_render_theme_url('oc-content/themes/epsilon/admin/configure.php'); ?>" method="POST">
        <input type="hidden" name="theme_action" value="done" />


        <div class="mb-row">
          <label for="lazy_load" class=""><span><?php _e('Images Lazy Load', 'epsilon'); ?></span></label> 

          <select name="lazy_load" id="lazy_load">
            <option value="0" <?php echo (eps_param('lazy_load') == 0 ? 'selected="selected"' : ''); ?>><?php _e('Disabled', 'epsilon'); ?></option>
            <option value="1" <?php echo (eps_param('lazy_load') == 1 ? 'selected="selected"' : ''); ?>><?php _e('Lazy load based on library', 'epsilon'); ?></option>
            <option value="2" <?php echo (eps_param('lazy_load') == 2 ? 'selected="selected"' : ''); ?>><?php _e('Lazy load based on browser support', 'epsilon'); ?></option>
          </select>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Enable to deffer images loading. Images will be loaded when get into viewable area. This may rapidly improve seo rating of your site.', 'epsilon'); ?></div>
            <div class="mb-line"><?php _e('Lazy load should not be disabled for any reason', 'epsilon'); ?></div>
          </div>
        </div>

        <div class="mb-row">
          <label for="default_location" class=""><span><?php _e('Default Location Selection', 'epsilon'); ?></span></label> 
          <input name="default_location" id="default_location" class="element-slide" type="checkbox" <?php echo (eps_param('default_location') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Enable users to select their default location and use it in search & publish.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="def_design" class=""><span><?php _e('Default Item Card Design', 'epsilon'); ?></span></label> 

          <select name="def_design" id="def_design">
            <option value="" <?php echo (eps_param('def_design') == '' ? 'selected="selected"' : ''); ?>><?php _e('Default', 'epsilon'); ?></option>
            
            <?php foreach(eps_card_designs() as $key => $name) { ?>
              <option value="<?php echo $key; ?>" <?php echo (eps_param('def_design') == $key ? 'selected="selected"' : ''); ?>><?php echo $name; ?></option>
            <?php } ?>
          </select>
          
          <div class="mb-explain"><?php _e('Specify default image aspect ratio on listings cards (loop) in grid layout.', 'epsilon'); ?></div>
        </div>


        <div class="mb-row"><h3 class="mb-subtitle"><?php _e('Custom color settings', 'epsilon'); ?></h3></div>

        <div class="mb-row">
          <label for="enable_custom_color" class=""><span><?php _e('Use Custom Color', 'epsilon'); ?></span></label> 
          <input name="enable_custom_color" id="enable_custom_color" class="element-slide" type="checkbox" <?php echo (eps_param('enable_custom_color') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Theme will use bellow defined colors.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row mb-color-box">
          <label for="color" class=""><span><?php _e('Theme Primary Color', 'epsilon'); ?></span></label> 
      
          <input name="color" id="color" size="20" minlength="7" maxlength="7" type="text" value="<?php echo osc_esc_html($color); ?>" />
          <span class="color-wrap">
            <input name="color-picker" id="" type="color" value="<?php echo osc_esc_html($color); ?>" />
          </span>
          <div class="mb-explain"><?php _e('Enter color in HEX format or select color with picker. Theme will use this color for buttons, borders, ... Default: #3b49df', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="enable_dark_mode" class=""><span><?php _e('Enable Dark Mode', 'epsilon'); ?></span></label> 
          <input name="enable_dark_mode" id="enable_dark_mode" class="element-slide" type="checkbox" <?php echo (eps_param('enable_dark_mode') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain">
            <div class="mb-line"><?php _e('Dark mode will be enabled by default (automatic dark mode) in case device is using dark mode in system.', 'epsilon'); ?></div>
            <div class="mb-line"><?php _e('Dark mode will not be available on device that is not using dark mode in system.', 'epsilon'); ?></div>
            <div class="mb-line"><?php _e('Dark mode will can be disabled on devices those are using dark mode in system. This settings persists in cookies.', 'epsilon'); ?></div>
          </div>
        </div>

        <div class="mb-row">
          <label for="default_mode" class=""><span><?php _e('Default Mode', 'epsilon'); ?></span></label> 
          <select name="default_mode" id="default_mode">
            <option value="DARK" <?php echo (eps_param('default_mode') == 'DARK' ? 'selected="selected"' : ''); ?>><?php _e('Dark mode', 'epsilon'); ?></option>
            <option value="LIGHT" <?php echo (eps_param('default_mode') == 'LIGHT' ? 'selected="selected"' : ''); ?>><?php _e('Light mode', 'epsilon'); ?></option>
          </select>
          
          <div class="mb-explain"><?php _e('Select default mode when dark mode is enabled on device. If you select dark mode, it will be default only on devices with active dark mode (theme).', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row mb-color-box">
          <label for="color_dark" class=""><span><?php _e('Theme Dark Mode Color', 'epsilon'); ?></span></label> 
      
          <input name="color_dark" id="color_dark" size="20" minlength="7" maxlength="7" type="text" value="<?php echo osc_esc_html($color_dark); ?>" />
          <span class="color-wrap">
            <input name="color-picker" id="" type="color" value="<?php echo osc_esc_html($color_dark); ?>" />
          </span>
          <div class="mb-explain"><?php _e('Enter color in HEX format or select color with picker. Theme will use this color in dark mode for buttons, borders, ... Default: #3b49df', 'epsilon'); ?></div>
        </div>

        <div class="mb-row"><h3 class="mb-subtitle"><?php _e('Custom font settings', 'epsilon'); ?></h3></div>

        <div class="mb-row">
          <label for="enable_custom_font" class=""><span><?php _e('Use Custom Font', 'epsilon'); ?></span></label> 
          <input name="enable_custom_font" id="enable_custom_font" class="element-slide" type="checkbox" <?php echo (eps_param('enable_custom_font') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Theme will use bellow defined font as default theme font (instead of System-UI font).', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="font_name" class=""><span><?php _e('Font Family Name', 'epsilon'); ?></span></label> 
          <input size="30" name="font_name" id="font_name" type="text" value="<?php echo osc_esc_html(eps_param('font_name')); ?>" />

          <div class="mb-explain"><?php _e('Enter font family name that will be used in CSS to set this font. Examples: Roboto, "Open Sans", Tapestry, Montserrat, ...', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="font_url" class=""><span><?php _e('Font URL', 'epsilon'); ?></span></label> 
          <input size="100" name="font_url" id="font_url" type="text" value="<?php echo osc_esc_html(eps_param('font_url')); ?>" />

          <div class="mb-explain"><?php _e('Enter URL to your font. You can place multiple URLs delimited by comma when needed.', 'epsilon'); ?></div>
        </div>
        
        

        <div class="mb-row"><h3 class="mb-subtitle"><?php _e('About your site', 'epsilon'); ?></h3></div>

        <div class="mb-row">
          <label for="site_name" class=""><span><?php _e('Site Name', 'epsilon'); ?></span></label> 
          <input size="40" name="site_name" id="site_name" type="text" value="<?php echo osc_esc_html(eps_param('site_name')); ?>" placeholder="<?php echo osc_esc_html(__('Website Name', 'epsilon')); ?>" />

          <div class="mb-explain"><?php _e('Leave blank to disable, will be shown in footer', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="site_phone" class=""><span><?php _e('Site Phone Number', 'epsilon'); ?></span></label> 
          <input size="40" name="site_phone" id="site_phone" type="text" value="<?php echo osc_esc_html(eps_param('site_phone')); ?>" placeholder="<?php echo osc_esc_html(__('Site Phone Number', 'epsilon')); ?>" />

          <div class="mb-explain"><?php _e('Leave blank to disable, will be shown in footer', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="site_email" class=""><span><?php _e('Site Support Email', 'epsilon'); ?></span></label> 
          <input size="40" name="site_email" id="site_email" type="text" value="<?php echo osc_esc_html(eps_param('site_email')); ?>" placeholder="<?php echo osc_esc_html(__('Site Support Email', 'epsilon')); ?>" />

          <div class="mb-explain"><?php _e('Leave blank to disable, will be shown in footer', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="site_address" class=""><span><?php _e('Site Address', 'epsilon'); ?></span></label> 
          <textarea name="site_address" id="site_address"><?php echo osc_esc_html(eps_param('site_address')); ?></textarea>

          <div class="mb-explain"><?php _e('Enter contact address that will be used in footer', 'epsilon'); ?></div>
        </div>



        <div class="mb-row"><h3 class="mb-subtitle"><?php _e('Promotion settings', 'epsilon'); ?></h3></div>

        
        <div class="mb-row">
          <label for="enable_day_offer" class=""><span><?php _e('Enable Offer of the Day', 'epsilon'); ?></span></label> 
          <input name="enable_day_offer" id="enable_day_offer" class="element-slide" type="checkbox" <?php echo (eps_param('enable_day_offer') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Enable offer of the day functionality. You can select promoted item in bellow box. If no item is selected, random premium item will be picked via daily cron.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="day_offer_admin_id" class=""><span><?php _e('Offer of the Day Item ID', 'epsilon'); ?></span></label> 
          <input size="20" name="day_offer_admin_id" id="day_offer_admin_id" type="text" value="<?php echo osc_esc_html(eps_param('day_offer_admin_id')); ?>"/>

          <div class="mb-explain"><?php _e('Enter item ID that will be promoted as "Offer of the day". Leave blank to use random premium item.', 'epsilon'); ?></div>
        </div>
        
        

        <div class="mb-row"><h3 class="mb-subtitle"><?php _e('Home page settings', 'epsilon'); ?></h3></div>

        <div class="mb-row">
          <label for="interactive_title" class=""><span><?php _e('Enable Interactive Title', 'epsilon'); ?></span></label> 
          <input name="interactive_title" id="interactive_title" class="element-slide" type="checkbox" <?php echo (eps_param('interactive_title') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Enable interactive title on home page (rotating sell, buy, rent, ...).', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="location_home" class=""><span><?php _e('Show Location Block on Home', 'epsilon'); ?></span></label> 
          <input name="location_home" id="location_home" class="element-slide" type="checkbox" <?php echo (eps_param('location_home') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Show block with listings close to user default location (if defined and enabled).', 'epsilon'); ?></div>
        </div>

        
        <div class="mb-row">
          <label for="premium_home" class=""><span><?php _e('Show Premiums Block on Home', 'epsilon'); ?></span></label> 
          <input name="premium_home" id="premium_home" class="element-slide" type="checkbox" <?php echo (eps_param('premium_home') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Show premium listings block on home page.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="premium_home_count" class=""><span><?php _e('Number of Premiums on Home', 'epsilon'); ?></span></label> 
          <input size="8" name="premium_home_count" id="premium_home_count" type="number" value="<?php echo osc_esc_html(eps_param('premium_home_count')); ?>" />

          <div class="mb-explain"><?php _e('How many premium listings will be shown on home page.', 'epsilon'); ?></div>
        </div>





        <div class="mb-row mb-row-select-multiple">
          <label for="premium_home_category" class=""><span><?php _e('Category for Home Premium Items', 'epsilon'); ?></span></label> 
  
          <input type="hidden" name="premium_home_category" id="premium_home_category" value="<?php echo $premium_home_category; ?>"/>
          <select id="premium_home_category_multiple" name="premium_home_category_multiple" multiple>
            <?php echo eps_cat_list($premium_home_category_array); ?>
          </select>
          
          <div class="mb-explain"><?php _e('Select categories that will be used to feed listings into premium items section on home page.', 'epsilon'); ?></div>
        </div>


        <div class="mb-row">
          <label for="premium_home_sort" class=""><span><?php _e('Premium Home Sort', 'epsilon'); ?></span></label> 
          <select name="premium_home_sort" id="premium_home_sort">
            <option value="" <?php echo (eps_param('premium_home_sort') == '' ? 'selected="selected"' : ''); ?>><?php _e('Default', 'epsilon'); ?></option>
            <option value="DT_PUB_DATE/ASC" <?php echo (eps_param('premium_home_sort') == 'DT_PUB_DATE/ASC' ? 'selected="selected"' : ''); ?>><?php _e('Publish Date - Ascending', 'epsilon'); ?></option>
            <option value="DT_PUB_DATE/DESC" <?php echo (eps_param('premium_home_sort') == 'DT_PUB_DATE/DESC' ? 'selected="selected"' : ''); ?>><?php _e('Publish Date - Descending', 'epsilon'); ?></option>
            <option value="RAND()/DESC" <?php echo (eps_param('premium_home_sort') == 'RAND()/DESC' ? 'selected="selected"' : ''); ?>><?php _e('Random', 'epsilon'); ?></option>
          </select>
          
          <div class="mb-explain"><?php _e('Select how to sort premium items on home page.', 'epsilon'); ?></div>
        </div>
        
        
        <hr/>


        <?php if(1==2) { ?>
        <div class="mb-row">
          <label for="premium_home_design" class=""><span><?php _e('Premium Items Card Design (home)', 'epsilon'); ?></span></label> 
          <select name="premium_home_design" id="premium_design">
            <option value="" <?php echo (eps_param('premium_home_design') == '' ? 'selected="selected"' : ''); ?>><?php _e('Standard', 'epsilon'); ?></option>

            <?php foreach(eps_card_designs() as $key => $name) { ?>
              <option value="<?php echo $key; ?>" <?php echo (eps_param('premium_home_design') == $key ? 'selected="selected"' : ''); ?>><?php echo $name; ?></option>
            <?php } ?>
          </select>

          <div class="mb-explain"><?php _e('Specify which card design will be used.', 'epsilon'); ?></div>
        </div>
        <?php } ?>

        <div class="mb-row mb-row-select-multiple">
          <label for="categories_new" class=""><span><?php _e('Categories with New label', 'epsilon'); ?></span></label> 

          <input type="hidden" name="categories_new" id="categories_new" value="<?php echo $categories_new; ?>"/>
          <select id="categories_new_multiple" name="categories_new_multiple" multiple>
            <option value="" <?php if($categories_new == '') { ?>selected="selected"<?php } ?>><?php _e('None', 'epsilon'); ?></option>
          
            <?php 
              osc_get_categories(); 
              osc_goto_first_category();
            ?>
            <?php while(osc_has_categories()) { ?>
              <option value="<?php echo osc_category_id(); ?>" <?php if(in_array(osc_category_id(), $categories_new_array)) { ?>selected="selected"<?php } ?>><?php echo osc_category_name(); ?></option>
            <?php } ?>
          </select>

          <div class="mb-explain"><?php _e('Select one or more categories those will have label "NEW" on home page.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row mb-row-select-multiple">
          <label for="categories_hot" class=""><span><?php _e('Categories with Hot label', 'epsilon'); ?></span></label> 

          <input type="hidden" name="categories_hot" id="categories_hot" value="<?php echo $categories_hot; ?>"/>
          <select id="categories_hot_multiple" name="categories_hot_multiple" multiple>
            <option value="" <?php if($categories_hot == '') { ?>selected="selected"<?php } ?>><?php _e('None', 'epsilon'); ?></option>
          
            <?php 
              osc_get_categories(); 
              osc_goto_first_category();
            ?>
            <?php while(osc_has_categories()) { ?>
              <option value="<?php echo osc_category_id(); ?>" <?php if(in_array(osc_category_id(), $categories_hot_array)) { ?>selected="selected"<?php } ?>><?php echo osc_category_name(); ?></option>
            <?php } ?>
          </select>

          <div class="mb-explain"><?php _e('Select one or more categories those will have label "HOT" on home page.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="users_home" class=""><span><?php _e('Show Users Block on Home', 'epsilon'); ?></span></label> 
          <input name="users_home" id="users_home" class="element-slide" type="checkbox" <?php echo (eps_param('users_home') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Show users block on home page.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="users_home_count" class=""><span><?php _e('Number of Users on Home', 'epsilon'); ?></span></label> 
          <input size="8" name="users_home_count" id="users_home_count" type="number" value="<?php echo osc_esc_html(eps_param('users_home_count')); ?>" />

          <div class="mb-explain"><?php _e('How many premium users will be shown in users block on home page.', 'epsilon'); ?></div>
        </div>
        
        

        <div class="mb-row"><h3 class="mb-subtitle"><?php _e('Latest items settings', 'epsilon'); ?></h3></div>

        <div class="mb-row">
          <label for="latest_random" class=""><span><?php _e('Show Latest Items in Random Order', 'epsilon'); ?></span></label> 
          <input name="latest_random" id="latest_random" class="element-slide" type="checkbox" <?php echo (eps_param('latest_random') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Enable to show latest items in ranodm order each time page is refreshed.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="latest_picture" class=""><span><?php _e('Latest Items Picture Only', 'epsilon'); ?></span></label> 
          <input name="latest_picture" id="latest_picture" class="element-slide" type="checkbox" <?php echo (eps_param('latest_picture') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Enable to show in latest section on home page only listings those has at least 1 picture.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="latest_premium" class=""><span><?php _e('Latest Premium Items', 'epsilon'); ?></span></label> 
          <input name="latest_premium" id="latest_premium" class="element-slide" type="checkbox" <?php echo (eps_param('latest_premium') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Enable to show in latest section on home page only listings those are premium.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row mb-row-select-multiple">
          <label for="latest_category" class=""><span><?php _e('Category for Latest Items', 'epsilon'); ?></span></label> 
  
          <input type="hidden" name="latest_category" id="latest_category" value="<?php echo $latest_category; ?>"/>
          <select id="latest_category_multiple" name="latest_category_multiple" multiple>
            <?php echo eps_cat_list($latest_category_array); ?>
          </select>
          
          <div class="mb-explain"><?php _e('Select categories that will be used to feed listings into latest items section on home page.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="latest_design" class=""><span><?php _e('Latest items card design', 'epsilon'); ?></span></label> 
          <select name="latest_design" id="latest_design">
            <option value="" <?php echo (eps_param('latest_design') == '' ? 'selected="selected"' : ''); ?>><?php _e('Standard', 'epsilon'); ?></option>

            <?php foreach(eps_card_designs() as $key => $name) { ?>
              <option value="<?php echo $key; ?>" <?php echo (eps_param('latest_design') == $key ? 'selected="selected"' : ''); ?>><?php echo $name; ?></option>
            <?php } ?>
          </select>

          <div class="mb-explain"><?php _e('Specify which card design will be used.', 'epsilon'); ?></div>
        </div>



        <div class="mb-row"><h3 class="mb-subtitle"><?php _e('Search page settings', 'epsilon'); ?></h3></div>

        <div class="mb-row">
          <label for="search_ajax" class=""><span><?php _e('Live Search using Ajax', 'epsilon'); ?></span></label> 
          <input name="search_ajax" id="search_ajax" class="element-slide" type="checkbox" <?php echo (eps_param('search_ajax') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Enable live realtime search without reloading of search page.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="def_view" class=""><span><?php _e('Default View on Search Page', 'epsilon'); ?></span></label> 
          <select name="def_view" id="def_view">
            <option value="0" <?php echo (eps_param('def_view') == 0 ? 'selected="selected"' : ''); ?>><?php _e('Gallery view', 'epsilon'); ?></option>
            <option value="1" <?php echo (eps_param('def_view') == 1 ? 'selected="selected"' : ''); ?>><?php _e('List view', 'epsilon'); ?></option>
            <option value="2" <?php echo (eps_param('def_view') == 2 ? 'selected="selected"' : ''); ?>><?php _e('Detail view', 'epsilon'); ?></option>
          </select>
          
          <div class="mb-explain"><?php _e('Select default design of listing cards on search page.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="premium_search" class=""><span><?php _e('Show Premiums Block on Search', 'epsilon'); ?></span></label> 
          <input name="premium_search" id="premium_search" class="element-slide" type="checkbox" <?php echo (eps_param('premium_search') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Show Premium Listings block on Search Page.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="premium_search_count" class=""><span><?php _e('Number of Premiums on Search', 'epsilon'); ?></span></label> 
          <input size="8" name="premium_search_count" id="premium_search_count" type="number" value="<?php echo osc_esc_html(eps_param('premium_search_count') ); ?>" />

          <div class="mb-explain"><?php _e('How many premium listings will be shown on Search page.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="search_premium_promote_url" class=""><span><?php _e('Premium Placeholder Target URL', 'epsilon'); ?></span></label> 
          <input size="60" name="search_premium_promote_url" id="search_premium_promote_url" type="text" value="<?php echo osc_esc_html(eps_param('search_premium_promote_url') ); ?>" />

          <div class="mb-explain"><?php _e('Define URL where user is redirected when clicked on "Your listing here" placeholder on search page (premiums block). If no URL is defined, placeholder is not shown.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="premium_search_design" class=""><span><?php _e('Premium Items Card Design (search)', 'epsilon'); ?></span></label> 
          <select name="premium_search_design" id="premium_design">
            <option value="" <?php echo (eps_param('premium_search_design') == '' ? 'selected="selected"' : ''); ?>><?php _e('Standard', 'epsilon'); ?></option>

            <?php foreach(eps_card_designs() as $key => $name) { ?>
              <option value="<?php echo $key; ?>" <?php echo (eps_param('premium_search_design') == $key ? 'selected="selected"' : ''); ?>><?php echo $name; ?></option>
            <?php } ?>
          </select>

          <div class="mb-explain"><?php _e('Specify which card design will be used.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="def_cur" class=""><span><?php _e('Currency in Search Box', 'epsilon'); ?></span></label> 
          <select name="def_cur" id="def_cur">
            <?php foreach(osc_get_currencies() as $c) { ?>
              <option value="<?php echo $c['s_description']; ?>" <?php echo (eps_param('def_cur') == $c['s_description'] ? 'selected="selected"' : ''); ?>><?php echo $c['s_description']; ?></option>
            <?php } ?>
          </select>

          <div class="mb-explain"><?php _e('Select currency symbol that will be used on search page for min & max price fields.', 'epsilon'); ?></div>
        </div>



        <div class="mb-row"><h3 class="mb-subtitle"><?php _e('Publish listing settings', 'epsilon'); ?></h3></div>

        <div class="mb-row">
          <label for="publish_category" class=""><span><?php _e('Category selection on Publish page', 'epsilon'); ?></span></label> 
          <select name="publish_category" id="publish_category">
            <option value="1" <?php echo (eps_param('publish_category') == 1 ? 'selected="selected"' : ''); ?>><?php _e('Auto-complete box', 'epsilon'); ?></option>
            <option value="2" <?php echo (eps_param('publish_category') == 2 ? 'selected="selected"' : ''); ?>><?php _e('Cascading dropdowns', 'epsilon'); ?></option>
            <option value="3" <?php echo (eps_param('publish_category') == 3 ? 'selected="selected"' : ''); ?>><?php _e('One select box', 'epsilon'); ?></option>
          </select>

          <div class="mb-explain"><?php _e('Select what type of category selection (box) will be used on publish/edit page.', 'epsilon'); ?></div>
        </div>
        

        <div class="mb-row">
          <label for="publish_location" class=""><span><?php _e('Location selection on Publish page', 'epsilon'); ?></span></label> 
          <select name="publish_location" id="publish_location">
            <option value="0" <?php echo (eps_param('publish_location') == 0 ? 'selected="selected"' : ''); ?>><?php _e('Cascading dropdowns (Country > Region > City)', 'epsilon'); ?></option>
            <option value="1" <?php echo (eps_param('publish_location') == 1 ? 'selected="selected"' : ''); ?>><?php _e('Auto-complete box', 'epsilon'); ?></option>
          </select>
          
          <div class="mb-explain"><?php _e('Auto-complete box can be right choice in case your regions has thousands of cities and it is hard to scroll to proper city.', 'epsilon'); ?></div>
        </div>


        <div class="mb-row mb-row-select-multiple">
          <label for="post_required" class=""><span><?php _e('Required Fields on Publish', 'epsilon'); ?></span></label> 

          <input type="hidden" name="post_required" id="post_required" value="<?php echo $post_required; ?>"/>
          <select id="post_required_multiple" name="post_required_multiple" multiple>
            <option value="" <?php if($post_required == '') { ?>selected="selected"<?php } ?>><?php _e('None', 'epsilon'); ?></option>
            <option value="country" <?php if(in_array('country', $post_required_array)) { ?>selected="selected"<?php } ?>><?php _e('Country', 'epsilon'); ?></option>
            <option value="region" <?php if(in_array('region', $post_required_array)) { ?>selected="selected"<?php } ?>><?php _e('Region', 'epsilon'); ?></option>
            <option value="city" <?php if(in_array('city', $post_required_array)) { ?>selected="selected"<?php } ?>><?php _e('City', 'epsilon'); ?></option>
            <option value="name" <?php if(in_array('name', $post_required_array)) { ?>selected="selected"<?php } ?>><?php _e('Contact Name', 'epsilon'); ?></option>
            <option value="phone" <?php if(in_array('phone', $post_required_array)) { ?>selected="selected"<?php } ?>><?php _e('Phone', 'epsilon'); ?></option>
            <option value="transaction" <?php if(1==2 && $post_extra_exclude_array != '') { ?> disabled<?php } ?> <?php if(in_array('transaction', $post_required_array)) { ?>selected="selected"<?php } ?>><?php _e('Transaction', 'epsilon'); ?></option>
            <option value="condition" <?php if(1==2 && $post_extra_exclude_array != '') { ?> disabled<?php } ?> <?php if(in_array('condition', $post_required_array)) { ?>selected="selected"<?php } ?>><?php _e('Condition', 'epsilon'); ?></option>
          </select>

          <div class="mb-explain"><?php _e('If you select Location as required, it means that one of following fields must be filled: Country, Region or City', 'epsilon'); ?></div>
        </div>

        <div class="mb-row mb-row-select-multiple">
          <label for="post_extra_exclude" class="h26"><span><?php _e('Extra Fields exclude Categories', 'epsilon'); ?></span></label> 
  
          <input type="hidden" name="post_extra_exclude" id="post_extra_exclude" value="<?php echo $post_extra_exclude; ?>"/>
          <select id="post_extra_exclude_multiple" name="post_extra_exclude_multiple" multiple>
            <?php echo eps_cat_list($post_extra_exclude_array); ?>
          </select>

          <div class="mb-explain"><?php _e('Select categories where you do not want to show Transaction and Condition on listing publish/edit page', 'epsilon'); ?></div>
        </div>



        <div class="mb-row"><h3 class="mb-subtitle"><?php _e('Other settings', 'epsilon'); ?></h3></div>

        <div class="mb-row">
          <label for="gallery_ratio" class=""><span><?php _e('Image Gallery Aspect Ratio', 'epsilon'); ?></span></label> 
          <select name="gallery_ratio" id="gallery_ratio">
            <option value="" <?php echo (eps_param('gallery_ratio') == '' ? 'selected="selected"' : ''); ?>><?php _e('Calculated based on Image Normal Size', 'epsilon'); ?></option>
            <option value="1:1" <?php echo (eps_param('gallery_ratio') == '1:1' ? 'selected="selected"' : ''); ?>><?php _e('Square (1:1)', 'epsilon'); ?></option>
            <option value="4:3" <?php echo (eps_param('gallery_ratio') == '4:3' ? 'selected="selected"' : ''); ?>><?php _e('Normal (4:3)', 'epsilon'); ?></option>
            <option value="16:9" <?php echo (eps_param('gallery_ratio') == '16:9' ? 'selected="selected"' : ''); ?>><?php _e('Wide (16:9)', 'epsilon'); ?></option>
            <option value="2:1" <?php echo (eps_param('gallery_ratio') == '2:1' ? 'selected="selected"' : ''); ?>><?php _e('Very wide (2:1)', 'epsilon'); ?></option>
          </select>
          
          <div class="mb-explain"><?php _e('Select image gallery aspect ratio shown on listing page.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="footer_link" class=""><span><?php _e('Footer Link', 'epsilon'); ?></span></label> 
          <input name="footer_link" id="footer_link" class="element-slide" type="checkbox" <?php echo (eps_param('footer_link') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Link to osclass will be shown in footer to support our project.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="public_items" class=""><span><?php _e('Number of Items on Public Profile', 'epsilon'); ?></span></label> 
          <input size="8" name="public_items" id="public_items" type="number" value="<?php echo eps_param('public_items'); ?>" />

          <div class="mb-explain"><?php _e('How many listings will be shown on user public profile. Keep in mind that pagination is not available on public profile.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="alert_items" class=""><span><?php _e('Number of Items in Alerts section', 'epsilon'); ?></span></label> 
          <input size="8" name="alert_items" id="alert_items" type="number" value="<?php echo eps_param('alert_items'); ?>" />

          <div class="mb-explain"><?php _e('How many listings will be shown under each alert in alerts section of user account. Keep in mind that pagination is not available in alerts section.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="profile_location" class=""><span><?php _e('Location selection on Profile page', 'epsilon'); ?></span></label> 
          <select name="profile_location" id="profile_location">
            <option value="0" <?php echo (eps_param('profile_location') == 0 ? 'selected="selected"' : ''); ?>><?php _e('Cascading dropdowns (Country > Region > City)', 'epsilon'); ?></option>
            <option value="1" <?php echo (eps_param('profile_location') == 1 ? 'selected="selected"' : ''); ?>><?php _e('Auto-complete box', 'epsilon'); ?></option>
          </select>
          
          <div class="mb-explain"><?php _e('Auto-complete box can be right choice in case your regions has thousands of cities and it is hard to scroll to proper city.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="generate_favicons" class=""><span><?php _e('Generate Favicons', 'epsilon'); ?></span></label> 
          <input name="generate_favicons" id="generate_favicons" class="element-slide" type="checkbox" <?php echo (eps_param('generate_favicons') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain">
            <div class="mb-line"><?php _e('When enabled, favicons are generated in head of website. In some cases you may want to disable this, i.e. if you have it generated by plugin or manually.', 'epsilon'); ?></div>
            <div class="mb-line"><?php echo sprintf(__('You can upload your favicons into folder %s. Sample data can be found in /sample. Keep naming convention.', 'epsilon'), 'oc-content/themes/epsilon/images/favicons/'); ?></div>
          </div>
        </div>

        <div class="mb-row">
          <label for="sample_favicons" class=""><span><?php _e('Use Sample Favicons', 'epsilon'); ?></span></label> 
          <input name="sample_favicons" id="sample_favicons" class="element-slide" type="checkbox" <?php echo (eps_param('sample_favicons') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php echo sprintf(__('When enabled, sample favicons from theme folder (%s) will be used.', 'epsilon'), 'oc-content/themes/epsilon/images/favicons/sample/'); ?></div>
        </div>

        <div class="mb-row">
          <label for="shorten_description" class=""><span><?php _e('Shorten Item Description', 'epsilon'); ?></span></label> 
          <input name="shorten_description" id="shorten_description" class="element-slide" type="checkbox" <?php echo (eps_param('shorten_description') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('When enabled, on listing page only first 720 characters of description will be shown. If description is longer, it will be truncated and "Read more" button added.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="asset_versioning" class=""><span><?php _e('Assets Versioning', 'epsilon'); ?></span></label> 
          <input name="asset_versioning" id="asset_versioning" class="element-slide" type="checkbox" <?php echo (eps_param('asset_versioning') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('When enabled, version based on current timestamp will be added to each style & script link in order to remove caching. No effect on plugins/osclass scripts & styles.', 'epsilon'); ?> (https://yoursite.com/style.css?v=<?php echo date('YmdHis'); ?>)</div>
        </div>




        <div class="mb-row"><h3 class="mb-subtitle"><?php _e('Social Network Links', 'epsilon'); ?></h3></div>

        <div class="mb-row">
          <label for="footer_social_define" class=""><span><?php _e('Define Social Links', 'epsilon'); ?></span></label> 
          <input name="footer_social_define" id="footer_social_define" class="element-slide" type="checkbox" <?php echo (eps_param('footer_social_define') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('When enabled, social links (in footer) will point to URLs defined here. If empty, link to that network will be hidden. Otherwise link is auto-generated (share link).', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="footer_social_whatsapp" class=""><span><?php _e('Whatsapp', 'epsilon'); ?></span></label> 
          <input size="80" name="footer_social_whatsapp" id="footer_social_whatsapp" type="text" value="<?php echo osc_esc_html(eps_param('footer_social_whatsapp') ); ?>" />

          <div class="mb-explain"><?php _e('Define URL that points to your company URL on network. Keep blank to hide network link.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="footer_social_facebook" class=""><span><?php _e('Facebook', 'epsilon'); ?></span></label> 
          <input size="80" name="footer_social_facebook" id="footer_social_facebook" type="text" value="<?php echo osc_esc_html(eps_param('footer_social_facebook') ); ?>" />

          <div class="mb-explain"><?php _e('Define URL that points to your company URL on network. Keep blank to hide network link.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="footer_social_pinterest" class=""><span><?php _e('Pinterest', 'epsilon'); ?></span></label> 
          <input size="80" name="footer_social_pinterest" id="footer_social_pinterest" type="text" value="<?php echo osc_esc_html(eps_param('footer_social_pinterest') ); ?>" />

          <div class="mb-explain"><?php _e('Define URL that points to your company URL on network. Keep blank to hide network link.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="footer_social_instagram" class=""><span><?php _e('Instagram', 'epsilon'); ?></span></label> 
          <input size="80" name="footer_social_instagram" id="footer_social_instagram" type="text" value="<?php echo osc_esc_html(eps_param('footer_social_instagram') ); ?>" />

          <div class="mb-explain"><?php _e('Define URL that points to your company URL on network. Keep blank to hide network link.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="footer_social_x" class=""><span><?php _e('X (Twitter)', 'epsilon'); ?></span></label> 
          <input size="80" name="footer_social_x" id="footer_social_x" type="text" value="<?php echo osc_esc_html(eps_param('footer_social_x') ); ?>" />

          <div class="mb-explain"><?php _e('Define URL that points to your company URL on network. Keep blank to hide network link.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="footer_social_linkedin" class=""><span><?php _e('Linkedin', 'epsilon'); ?></span></label> 
          <input size="80" name="footer_social_linkedin" id="footer_social_linkedin" type="text" value="<?php echo osc_esc_html(eps_param('footer_social_linkedin') ); ?>" />

          <div class="mb-explain"><?php _e('Define URL that points to your company URL on network. Keep blank to hide network link.', 'epsilon'); ?></div>
        </div>

        
        <div class="mb-row">&nbsp;</div>

        <?php if(!eps_is_demo() || osc_logged_admin_username() == 'admin') { ?>
          <div class="mb-foot">
            <button type="submit" class="mb-button"><?php _e('Save', 'epsilon');?></button>
          </div>
        <?php } ?>
      </form>
    </div>
  </div>

</div>


<?php echo eps_footer(); ?>	