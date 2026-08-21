<?php
  // Create menu
  $title = __('Configure', 'google_login');
  ggl_menu($title);


  // GET & UPDATE PARAMETERS
  // $variable = mb_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check or value

  $client_id = mb_param_update('client_id', 'plugin_action', 'value', 'plugin-google_login');
  $client_secret = mb_param_update('client_secret', 'plugin_action', 'value', 'plugin-google_login');
 

  if(Params::getParam('plugin_action') == 'done') {
    message_ok( __('Settings were successfully saved', 'google_login') );
  }
?>


<div class="mb-body">

  <div class="mb-notes">
    <div class="mb-line"><?php _e('Please read how to get API keys in section bellow.', 'google_login'); ?></div>
  </div>

  <!-- CONFIGURE SECTION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-wrench"></i> <?php _e('Configure', 'google_login'); ?></div>

    <div class="mb-inside mb-minify">
      <form name="promo_form" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
        <?php if(!ggl_is_demo()) { ?>
        <input type="hidden" name="page" value="plugins" />
        <input type="hidden" name="action" value="renderplugin" />
        <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>configure.php" />
        <input type="hidden" name="plugin_action" value="done" />
        <?php } ?>

        <div class="mb-row">
          <label for="client_id" class="h1"><span><?php _e('Client Id', 'google_login'); ?></span></label> 
          <input name="client_id" size="90" type="text" value="<?php echo (ggl_is_demo() ? '' : $client_id); ?>" />
        </div>
        
        <div class="mb-row">
          <label for="client_secret" class="h1"><span><?php _e('Client Secret', 'google_login'); ?></span></label> 
          <input name="client_secret" size="50" type="password" value="<?php echo (ggl_is_demo() ? '' : $client_secret); ?>" />
        </div>


        <div class="mb-row">&nbsp;</div>

        <div class="mb-foot">
          <?php if(ggl_is_demo()) { ?>
            <a class="mb-button mb-has-tooltip disabled" onclick="return false;" style="cursor:not-allowed;opacity:0.5;" title="<?php echo osc_esc_html(__('This is demo site', 'google_login')); ?>"><?php _e('Save', 'google_login');?></a>
          <?php } else { ?>
            <button type="submit" class="mb-button"><?php _e('Save', 'google_login');?></button>
          <?php } ?>
        </div>
      </form>
    </div>
  </div>


  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-key"></i> <?php _e('Create Google API Console Project', 'google_login'); ?></div>

    <div class="mb-inside">
      <div class="mb-row">
        <ul class="mb-ul-num">
          <li><?php _e('Go to the', 'google_login'); ?> <a href="https://console.developers.google.com/"><?php _e('Google API Console.', 'google_login'); ?></a>.</li>

          <li><?php _e('Select an existing project from the projects list, or click <b>NEW PROJECT</b> to create a new project:', 'google_login'); ?></li>
 
          <ul class="mb-ul-txt">
            <li><?php _e('Enter the <b>Project Name</b>.', 'google_login'); ?></li>
            <li><?php _e('Under the Project Name, you will see the Google API console automatically creates a project ID. Optionally you can change this project ID by the <b>Edit</b> link. But project ID must be unique worldwide.', 'google_login'); ?></li>
            <li><?php _e('Click on the <b>CREATE</b> button and the project will be created in some seconds.', 'google_login'); ?></li>
          </ul>

          <li><?php _e('In the left side navigation panel ("APIs and services" section), select <b>Credentials</b>.', 'google_login'); ?></li>

          <li><?php _e('Select the <b>OAuth consent screen</b> tab, specify the consent screen settings.', 'google_login'); ?></li>
 
          <ul class="mb-ul-txt">
            <li><?php _e('If this is first time you specify consent screen, click on button <b>Get started</b> to initiate process.', 'google_login'); ?></li>
            <li><?php _e('In <b>Application name</b> field, enter the name of your Application.', 'google_login'); ?></li>
            <li><?php _e('In <b>Support email</b> field, choose an email address for user support.', 'google_login'); ?></li>
            <li><?php _e('In the <b>Authorized domains</b>, specify the domains which will be allowed to authenticate using OAuth.', 'google_login'); ?></li>
            <li><?php _e('Click the <b>Save</b> button.', 'google_login'); ?></li>
          </ul>

          <li><?php _e('In left side navigation panel ("APIs and services" section) select the <b>Credentials</b> tab, click the <b>Create credentials</b> drop-down and select <b>OAuth client ID</b>.', 'google_login'); ?></li>
 
          <ul class="mb-ul-txt">
            <li><?php _e('In the <b>Application type</b> section, select <b>Web application</b>.', 'google_login'); ?></li>
            <li>
              <?php _e('In the <b>Authorized redirect URIs</b> field, enter the following redirect URLs:', 'google_login'); ?><br/>
              <span class="mb-gray"><?php echo osc_base_url(); ?>ggl/1</span><br/>
              <span class="mb-gray"><?php echo osc_base_url(); ?>index.php?page=custom&route=ggl-redirect&gglLogin=1</span>
              
              <br/><br/><?php _e('If you use <u>language code in url</u>, you must also enter all possible variations, example:', 'google_login'); ?><br/>
              <span class="mb-gray"><?php echo osc_base_url(); ?>en/ggl/1</span><br/>
              <span class="mb-gray"><?php echo osc_base_url(); ?>de/ggl/1</span><br/>
              <span class="mb-gray"><?php echo osc_base_url(); ?>ru/ggl/1</span><br/>
              <span class="mb-gray">...</span><br/>
              <span class="mb-gray"><?php echo osc_base_url(); ?>xy/ggl/1</span><br/>
            </li>
            <li><?php _e('Click the <b>Create button</b>.', 'google_login'); ?></li>
          </ul>
          
          <br/><?php _e('Note that if you see error "redirect_uri_mismatch", you have not added all required redirect URLs in your google application!', 'google_login'); ?>

          <li><?php _e('A dialog box will appear with OAuth client details, note the <b>Client ID</b> and <b>Client secret</b>. This Client ID and Client secret allow you to access the Google APIs.', 'google_login'); ?></li>
        </ul>
      </div>

    </div>
  </div>


  <!-- PLUGIN INTEGRATION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-wrench"></i> <?php _e('Plugin Setup', 'google_login'); ?></div>

    <div class="mb-inside">
      <div class="mb-row">
        <div class="mb-line"><?php _e('Some themes from OsclassPoint.com may already have plugin pre-integrated, in that case no theme modifications are required.', 'google_login'); ?></div>
        
        <div class="mb-row">
          <div class="mb-line"><?php _e('To get login button, place anywhere into theme files following code:', 'google_login'); ?></div>
          <span class="mb-code">&lt;?php if(function_exists('ggl_login_button')) { echo ggl_login_button(); } ?&gt;</span>
        </div>

        <div class="mb-row">
          <div class="mb-line"><?php _e('To get raw login link, place anywhere into theme files following code:', 'google_login'); ?></div>
          <span class="mb-code">&lt;?php if(function_exists('ggl_login_link')) { echo ggl_login_link(); } ?&gt;</span>
        </div>

      </div>
    </div>
  </div>
</div>

<?php echo ggl_footer(); ?>