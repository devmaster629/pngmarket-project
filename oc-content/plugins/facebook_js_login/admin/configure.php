<?php
  // Create menu
  $title = __('Configure', 'facebook_js_login');
  fjl_menu($title);


  // GET & UPDATE PARAMETERS
  // $variable = mb_param_update('param_name', 'form_name', 'input_type', 'plugin_var_name');
  // input_type: check or value

  $enabled = mb_param_update('enabled', 'plugin_action', 'check', 'plugin-facebook_js_login');
  $enable_autologin = mb_param_update('enable_autologin', 'plugin_action', 'check', 'plugin-facebook_js_login');
  $hook_button_top = mb_param_update('hook_button_top', 'plugin_action', 'check', 'plugin-facebook_js_login');
  $hook_button_bot = mb_param_update('hook_button_bot', 'plugin_action', 'check', 'plugin-facebook_js_login');
  $app_id = mb_param_update('app_id', 'plugin_action', 'value', 'plugin-facebook_js_login');
  $app_secret = mb_param_update('app_secret', 'plugin_action', 'value', 'plugin-facebook_js_login');
  $exclude_pages = mb_param_update('exclude_pages', 'plugin_action', 'value', 'plugin-facebook_js_login');
  $custom_selector = mb_param_update('custom_selector', 'plugin_action', 'value', 'plugin-facebook_js_login');
 
  $disabled_pages_array = array('LOGIN','LOGIN-RECOVER','REGISTER-REGISTER');
  $exclude_pages_array = explode(',', $exclude_pages);

  if(Params::getParam('plugin_action') == 'done') {
    osc_add_flash_ok_message(__('Settings were successfully saved.', 'facebook_js_login'), 'admin');
    header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=facebook_js_login/admin/configure.php');
    exit;
  }
  
  $tutorial_img_path = 'https://osclasspoint.com/images/facebook-js-login/';
?>


<div class="mb-body">
  <div class="mb-notes">
    <div class="mb-line"><?php _e('Please read how to get API keys in section below.', 'facebook_js_login'); ?></div>
    <div class="mb-line"><?php _e('Client secret is not needed.', 'facebook_js_login'); ?></div>
  </div>

  <!-- CONFIGURE SECTION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-cog"></i> <?php _e('Configure', 'facebook_js_login'); ?></div>

    <div class="mb-inside">
      <form name="promo_form" id="promo_form" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
        <input type="hidden" name="page" value="plugins" />
        <input type="hidden" name="action" value="renderplugin" />
        <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>configure.php" />
        <input type="hidden" name="plugin_action" value="done" />

        <div class="mb-row">
          <label for="enabled" class=""><span><?php _e('Enable Facebook JS Login', 'facebook_js_login'); ?></span></label> 
          <input name="enabled" id="enabled" type="checkbox" class="element-slide" <?php echo ($enabled == 1 ? 'checked' : ''); ?> />
          <div class="mb-explain"><?php _e('When enabled, floating login box will be shown in front for non-logged users.', 'facebook_js_login'); ?></div>
        </div>
        
       
        <div class="mb-row">
          <label for="app_id" class=""><span><?php _e('Facebook App ID', 'facebook_js_login'); ?></span></label> 
          <input name="app_id" size="60" type="text" value="<?php echo (fjl_is_demo() ? '***********' : $app_id); ?>" />
          <div class="mb-explain"><?php _e('Follow instructions in section below.', 'facebook_js_login'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="app_secret" class=""><span><?php _e('Facebook App Secret', 'facebook_js_login'); ?></span></label> 
          <input name="app_secret" size="60" type="text" value="<?php echo (fjl_is_demo() ? '**********************' : $app_secret); ?>" />
          <div class="mb-explain"><?php _e('Follow instructions in section below.', 'facebook_js_login'); ?></div>
        </div>


        <div class="mb-row">
          <label for="enable_autologin" class=""><span><?php _e('Enable Autologin Login', 'facebook_js_login'); ?></span></label> 
          <input name="enable_autologin" id="enable_autologin" type="checkbox" class="element-slide" <?php echo ($enable_autologin == 1 ? 'checked' : ''); ?> />
          <div class="mb-explain"><?php _e('When enabled, user will be automatically logged in to your site, if this user already logged-in to your site before and this login is still valid.', 'facebook_js_login'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="hook_button_top" class=""><span><?php _e('Auto-Hook Button to Top', 'facebook_js_login'); ?></span></label> 
          <input name="hook_button_top" id="hook_button_top" type="checkbox" class="element-slide" <?php echo ($hook_button_top == 1 ? 'checked' : ''); ?> />
          <div class="mb-explain"><?php _e('When enabled, Facebook login button will be automatically added to top of user login and registration pages (if theme supports these hooks).', 'facebook_js_login'); ?></div>
        </div>

        <div class="mb-row">
          <label for="hook_button_bot" class=""><span><?php _e('Auto-Hook Button to Bottom', 'facebook_js_login'); ?></span></label> 
          <input name="hook_button_bot" id="hook_button_bot" type="checkbox" class="element-slide" <?php echo ($hook_button_bot == 1 ? 'checked' : ''); ?> />
          <div class="mb-explain"><?php _e('When enabled, Facebook login button will be automatically added to bottom of user login and registration pages (if theme supports these hooks).', 'facebook_js_login'); ?></div>
        </div>

        <div class="mb-row">
          <label for="custom_selector" class=""><span><?php _e('Custom Login Button Selector', 'facebook_js_login'); ?></span></label> 
          <input name="custom_selector" size="100" type="text" value="<?php echo $custom_selector; ?>" />
          <div class="mb-explain"><?php _e('Enter custom button selector that should trigger Facebook login. Example: #social a.facebook, .login-section button#fb-login', 'facebook_js_login'); ?></div>
        </div>

        <div class="mb-row mb-row-select-multiple">
          <label for="exclude_pages_multiple"><span><?php _e('Exclude Scripts & Auto-login', 'auto_renewal'); ?></span></label> 

          <input type="hidden" name="exclude_pages" id="exclude_pages" value="<?php echo $exclude_pages; ?>"/>
          <select id="exclude_pages_multiple" name="exclude_pages_multiple" multiple>
            <?php foreach(fjl_pages() as $key => $val) { ?>
              <option value="<?php echo $key; ?>" <?php if(in_array($key, $exclude_pages_array)) { ?>selected="selected"<?php } ?> <?php if(in_array($key, $disabled_pages_array)) { ?>disabled<?php } ?>><?php echo $val; ?></option>
            <?php } ?>
          </select>

          <div class="mb-explain">
            <?php _e('Select pages where login popup will not be shown. Note that login popup is never shown to logged-in user.', 'auto_renewal'); ?><br/>
          </div>
        </div>
        
        <?php if(!fjl_is_demo()) { ?>
          <div class="mb-foot">
            <button type="submit" class="mb-button"><?php _e('Save', 'facebook_js_login');?></button>
          </div>
        <?php } ?>
      </form>
    </div>
  </div>

  
  <!-- PLUGIN INTEGRATION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-wrench"></i> <?php _e('Plugin Setup', 'facebook_js_login'); ?></div>

    <div class="mb-inside">
      <div class="mb-row"><?php _e('Place login button to theme files.', 'facebook_js_login'); ?></div>
      <div class="mb-code">&lt;?php echo (function_exists('fjl_login_button') ? fjl_login_button() : ''); ?&gt;</div>
    </div>
  </div>


  <!-- FACEBOOK APPLICATION SETUP -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-key"></i> <?php _e('Create Facebook API keys', 'facebook_js_login'); ?></div>

    <div class="mb-inside">
      <div class="mb-row">
        <ul class="mb-ul-num">
          <li><?php _e('Go to the', 'facebook_js_login'); ?> <a href="https://developers.facebook.com/apps/"><?php _e('Facebook Developer Apps.', 'facebook_js_login'); ?></a>.</li>

          <li><?php _e('Click on "Create App" button.', 'facebook_js_login'); ?></li>
          <li><?php _e('Select "Consumer" app type.', 'facebook_js_login'); ?></li>
          <img class="mb-tutorial-img" alt="<?php echo osc_esc_html(__('App type', 'facebook_js_login')); ?>" src="<?php echo $tutorial_img_path; ?>1-consumer-app-type.jpg" />

          <li><?php _e('Fill app details like name, contact email or link it to business account and hit "Create App" button.', 'facebook_js_login'); ?></li>
          <img class="mb-tutorial-img" alt="<?php echo osc_esc_html(__('App details', 'facebook_js_login')); ?>" src="<?php echo $tutorial_img_path; ?>2-app-details.jpg" />
 
          <li><?php _e('You have now been redirected to app dashboard. Go to Settings > Basic. Copy "App ID" and "App secret" into plugin.', 'facebook_js_login'); ?></li>
          <li><?php _e('You also need to configure application details to be able to use it for login. First enter basic info like URLs, logo etc.', 'facebook_js_login'); ?></li>
          <img class="mb-tutorial-img" alt="<?php echo osc_esc_html(__('App settings', 'facebook_js_login')); ?>" src="<?php echo $tutorial_img_path; ?>3-app-basic-settings.jpg" />

          <li><?php _e('Go to Settings > Advanced. Use latest available Graph API version.', 'facebook_js_login'); ?></li>
          <img class="mb-tutorial-img" alt="<?php echo osc_esc_html(__('App settings', 'facebook_js_login')); ?>" src="<?php echo $tutorial_img_path; ?>4-app-advanced-settings.jpg" />

          <li><?php _e('Now let\'s add new product - Facebook login, to our App.', 'facebook_js_login'); ?></li>
          <li><?php _e('Click on "Add product" button, find "Facebook login" and hit "Set up" button to configure login.', 'facebook_js_login'); ?></li>
          <img class="mb-tutorial-img" alt="<?php echo osc_esc_html(__('Add product', 'facebook_js_login')); ?>" src="<?php echo $tutorial_img_path; ?>5-add-product.jpg" />

          <li><?php echo sprintf(__('Select "Web" as platform. Enter your URL %s into "Site URL" and click on "Save" button.', 'facebook_js_login'), osc_base_url()); ?></li>
          <img class="mb-tutorial-img" alt="<?php echo osc_esc_html(__('Add product', 'facebook_js_login')); ?>" src="<?php echo $tutorial_img_path; ?>6-facebook-login-quickstart.jpg" />

          <li><?php _e('You do not need to continue with quickstart guide and go to Facebook login > Settings in left menu.', 'facebook_js_login'); ?></li>
          <li><?php _e('Scroll down to and at bottom of "Client OAuth settings", find "Login with the JavaScript SDK" and enable it.', 'facebook_js_login'); ?></li>
          <li><?php echo sprintf(__('Add your domain %s into "Allowed Domains for the JavaScript SDK" and hit "Save changes".', 'facebook_js_login'), @parse_url(osc_base_url())['host']); ?></li>

          <img class="mb-tutorial-img" alt="<?php echo osc_esc_html(__('Add product', 'facebook_js_login')); ?>" src="<?php echo $tutorial_img_path; ?>7-facebook-login-settings.jpg" />

          <li><?php _e('That\'s it, your app should be setup and your plugin as well! Note you may need to verify your app after these settings or provide additional details, but these are not covered by this guide.', 'facebook_js_login'); ?></li>
          <li><?php _e('Note: plugin use only scopes email,public_profile and using only email,name,picture,id fields from Graph API.', 'facebook_js_login'); ?></li>
        </ul>
      </div>

    </div>
  </div>  
  

</div>

<?php echo fjl_footer(); ?>