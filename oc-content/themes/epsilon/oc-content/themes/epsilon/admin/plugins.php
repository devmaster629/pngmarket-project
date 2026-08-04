<?php
  require_once 'functions.php';


  // Create menu
  $title = __('Plugins', 'epsilon');
  eps_menu($title);


  // GET & UPDATE PARAMETERS
  // $variable = eps_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check, value or code

  $scrolltop = eps_param_update('scrolltop', 'theme_action', 'check', 'theme-epsilon');
  
  $related = eps_param_update('related', 'theme_action', 'check', 'theme-epsilon');
  $related_count = eps_param_update('related_count', 'theme_action', 'value', 'theme-epsilon');
  $related_design = eps_param_update('related_design', 'theme_action', 'value', 'theme-epsilon');

  $recent_home = eps_param_update('recent_home', 'theme_action', 'check', 'theme-epsilon');
  $recent_item = eps_param_update('recent_item', 'theme_action', 'check', 'theme-epsilon');
  $recent_search = eps_param_update('recent_search', 'theme_action', 'check', 'theme-epsilon');
  $recent_count = eps_param_update('recent_count', 'theme_action', 'value', 'theme-epsilon');
  $recent_design = eps_param_update('recent_design', 'theme_action', 'value', 'theme-epsilon');


  $blog_home = eps_param_update('blog_home', 'theme_action', 'check', 'theme-epsilon');
  $blog_home_count = eps_param_update('blog_home_count', 'theme_action', 'value', 'theme-epsilon');

  $company_home = eps_param_update('company_home', 'theme_action', 'check', 'theme-epsilon');
  $company_home_count = eps_param_update('company_home_count', 'theme_action', 'value', 'theme-epsilon');
  
  $favorite_home = eps_param_update('favorite_home', 'theme_action', 'check', 'theme-epsilon');
  $favorite_design = eps_param_update('favorite_design', 'theme_action', 'value', 'theme-epsilon');
  $favorite_count = eps_param_update('favorite_count', 'theme_action', 'value', 'theme-epsilon');

  $messenger_replace_button = eps_param_update('messenger_replace_button', 'theme_action', 'check', 'theme-epsilon');


  if(Params::getParam('theme_action') == 'done') {
    osc_add_flash_ok_message(__('Settings were successfully saved','epsilon'), 'admin');
    header('Location:' . osc_admin_render_theme_url('oc-content/themes/epsilon/admin/plugins.php'));
    exit;
  }
?>


<div class="mb-body">
 
  <!-- PLUGINS SECTION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-puzzle-piece"></i> <?php _e('Plugin settings', 'epsilon'); ?></div>

    <div class="mb-inside mb-minify">
      <form action="<?php echo osc_admin_render_theme_url('oc-content/themes/epsilon/admin/plugins.php'); ?>" method="POST">
        <input type="hidden" name="theme_action" value="done" />

        <div class="mb-row">
          <label for="scrolltop" class=""><span><?php _e('Enable Scroll to Top', 'epsilon'); ?></span></label> 
          <input name="scrolltop" id="scrolltop" class="element-slide" type="checkbox" <?php echo (eps_param('scrolltop') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('When enabled, button that enables scroll to top will be added.', 'epsilon'); ?></div>
        </div>


        <div class="mb-subtitle"><?php _e('Related Listings', 'epsilon'); ?></div>

        <div class="mb-row">
          <label for="related" class=""><span><?php _e('Enable Related Listings', 'epsilon'); ?></span></label> 
          <input name="related" id="related" class="element-slide" type="checkbox" <?php echo (eps_param('related') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('When enabled, related listings will be shown at listing page.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="related_count" class=""><span><?php _e('Number of Related Items', 'epsilon'); ?></span></label> 
          <input name="related_count" id="related_count" type="number" min="1" value="<?php echo eps_param('related_count'); ?>" />

          <div class="mb-explain"><?php _e('Enter how many related listings will be shown on item page.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="related_design" class=""><span><?php _e('Related Items Card Design', 'epsilon'); ?></span></label> 
          <select name="related_design" id="related_design">
            <option value="" <?php echo (eps_param('related_design') == '' ? 'selected="selected"' : ''); ?>><?php _e('Default', 'epsilon'); ?></option>
            
            <?php foreach(eps_card_designs() as $key => $name) { ?>
              <option value="<?php echo $key; ?>" <?php echo (eps_param('related_design') == $key ? 'selected="selected"' : ''); ?>><?php echo $name; ?></option>
            <?php } ?>
          </select>

          <div class="mb-explain"><?php _e('Specify default image aspect ratio on listings cards (loop) in grid layout.', 'epsilon'); ?></div>
        </div>


        <div class="mb-subtitle"><?php _e('Recent Listings', 'epsilon'); ?></div>

        <div class="mb-row">
          <label for="recent_home" class=""><span><?php _e('Enable Recent Listings on Home Page', 'epsilon'); ?></span></label> 
          <input name="recent_home" id="recent_home" class="element-slide" type="checkbox" <?php echo (eps_param('recent_home') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('When enabled, recent listings will be shown at home page.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="recent_item" class=""><span><?php _e('Enable Recent Listings on Item Page', 'epsilon'); ?></span></label> 
          <input name="recent_item" id="recent_item" class="element-slide" type="checkbox" <?php echo (eps_param('recent_item') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('When enabled, recent listings will be shown at listing page.', 'epsilon'); ?></div>
        </div>

        <div class="mb-row">
          <label for="recent_search" class=""><span><?php _e('Enable Recent Listings on Search Page', 'epsilon'); ?></span></label> 
          <input name="recent_search" id="recent_search" class="element-slide" type="checkbox" <?php echo (eps_param('recent_search') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('When enabled, recent listings will be shown at search page.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="recent_count" class=""><span><?php _e('Number of Recent Items', 'epsilon'); ?></span></label> 
          <input name="recent_count" id="recent_count" type="number" min="1" value="<?php echo eps_param('recent_count'); ?>" />

          <div class="mb-explain"><?php _e('Enter how many recent listings will be shown in recent items block.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="recent_design" class=""><span><?php _e('Recent Items Card Design', 'epsilon'); ?></span></label> 
          <select name="recent_design" id="recent_design">
            <option value="" <?php echo (eps_param('recent_design') == '' ? 'selected="selected"' : ''); ?>><?php _e('Default', 'epsilon'); ?></option>
            
            <?php foreach(eps_card_designs() as $key => $name) { ?>
              <option value="<?php echo $key; ?>" <?php echo (eps_param('recent_design') == $key ? 'selected="selected"' : ''); ?>><?php echo $name; ?></option>
            <?php } ?>
          </select>

          <div class="mb-explain"><?php _e('Specify default image aspect ratio on listings cards (loop) in grid layout.', 'epsilon'); ?></div>
        </div>
        

        <div class="mb-subtitle"><?php _e('Blog plugin', 'epsilon'); ?></div>

        <div class="mb-row">
          <label for="blog_home" class=""><span><?php _e('Show Blog Box on Home', 'epsilon'); ?></span></label> 
          <input name="blog_home" id="blog_home" class="element-slide" type="checkbox" <?php echo (eps_param('blog_home') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain">
            <div class="mb-line"><?php _e('Show blog articles box on home page. Osclass Blog plugin must be installed.', 'epsilon'); ?></div>
            <div class="mb-line"><a href="https://osclasspoint.com/osclass-plugins/messaging-and-communication/osclass-blog-and-news-plugin-i84"><?php _e('View plugin', 'epsilon'); ?></a></div>
          </div>
        </div>

        <div class="mb-row">
          <label for="blog_home_count" class=""><span><?php _e('Number of Blog Articles on Home', 'epsilon'); ?></span></label> 
          <input size="8" name="blog_home_count" id="blog_home_count" type="number" value="<?php echo $blog_home_count; ?>" />

          <div class="mb-explain"><?php _e('How many blog articles will be shown on home page.', 'epsilon'); ?></div>
        </div>
        

        <div class="mb-subtitle"><?php _e('Business profile plugin', 'epsilon'); ?></div>

        <div class="mb-row">
          <label for="company_home" class=""><span><?php _e('Show Companies Box on Home', 'epsilon'); ?></span></label> 
          <input name="company_home" id="company_home" class="element-slide" type="checkbox" <?php echo (eps_param('company_home') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain">
            <div class="mb-line"><?php _e('Show companies box on home page. Business profile plugin must be installed', 'epsilon'); ?></div>
            <div class="mb-line"><a href="https://osclasspoint.com/osclass-plugins/design-and-appearance/business-profile-osclass-plugin-i89"><?php _e('View plugin', 'epsilon'); ?></a></div>
          </div>
        </div>

        <div class="mb-row">
          <label for="company_home_count" class=""><span><?php _e('Number of Companies on Home', 'epsilon'); ?></span></label> 
          <input size="8" name="company_home_count" id="company_home_count" type="number" value="<?php echo $company_home_count; ?>" />

          <div class="mb-explain"><?php _e('How many companies will be shown on home page.', 'epsilon'); ?></div>
        </div>
        
        
        <div class="mb-subtitle"><?php _e('Favorite items plugin', 'epsilon'); ?></div>
        
        <div class="mb-row">
          <label for="favorite_home" class=""><span><?php _e('Show Favorite Items on Home', 'epsilon'); ?></span></label> 
          <input name="favorite_home" id="favorite_home" class="element-slide" type="checkbox" <?php echo (eps_param('favorite_home') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Show users most favorited listings block on home page. Favorite items plugin must be installed.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="favorite_count" class=""><span><?php _e('Number of Favorite items on Home', 'epsilon'); ?></span></label> 
          <input size="8" name="favorite_count" id="favorite_count" type="number" value="<?php echo osc_esc_html(eps_param('favorite_count')); ?>" />

          <div class="mb-explain"><?php _e('Most favorited listings count shown on home page. Favorite items plugin must be installed.', 'epsilon'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="favorite_design" class=""><span><?php _e('Favorite Items Card Design', 'epsilon'); ?></span></label> 
          <select name="favorite_design" id="favorite_design">
            <option value="" <?php echo (eps_param('favorite_design') == '' ? 'selected="selected"' : ''); ?>><?php _e('Default', 'epsilon'); ?></option>

            <?php foreach(eps_card_designs() as $key => $name) { ?>
              <option value="<?php echo $key; ?>" <?php echo (eps_param('favorite_design') == $key ? 'selected="selected"' : ''); ?>><?php echo $name; ?></option>
            <?php } ?>
          </select>

          <div class="mb-explain"><?php _e('Specify which card design will be used.', 'epsilon'); ?></div>
        </div>


        <div class="mb-subtitle"><?php _e('Other plugins', 'epsilon'); ?></div>
        
        <div class="mb-row">
          <label for="messenger_replace_button" class=""><span><?php _e('Replace Send Message Button', 'epsilon'); ?></span></label> 
          <input name="messenger_replace_button" id="messenger_replace_button" class="element-slide" type="checkbox" <?php echo (eps_param('messenger_replace_button') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('When enabled and Instant messenger plugin is installed, "Send message" button on listing page will be replaced with button to create message.', 'epsilon'); ?></div>
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