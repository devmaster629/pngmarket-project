<?php
  require_once 'functions.php';


  // Create menu
  $title = __('Category', 'epsilon');
  eps_menu($title);


  // GET & UPDATE PARAMETERS
  // $variable = eps_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check, value or code
  $default_logo = eps_param_update('default_logo', 'theme_action', 'check', 'theme-epsilon');

  $allowed_ext = array('webp', 'jpg', 'jpeg', 'png', 'gif');


  switch( Params::getParam('theme_action') ) {
    case('upload_logo'):
      $file = Params::getFiles('logo');
      
      if($file['error'] == UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        if(!in_array($ext, $allowed_ext)) {
          osc_add_flash_error_message(sprintf(__('Image extension is not allowed. Allowed extensions are: %s', 'epsilon'), implode(',', $allowed_ext)), 'admin');
        } else {
          if(move_uploaded_file($file['tmp_name'], WebThemes::newInstance()->getCurrentThemePath() . 'images/logo.' . $ext)) {

            // Upload was successful, remove other logo extensions
            foreach($allowed_ext as $e) {
              if($e != $ext) {
                if(file_exists(WebThemes::newInstance()->getCurrentThemePath() . 'images/logo.' . $e)) { 
                  @unlink(WebThemes::newInstance()->getCurrentThemePath() . 'images/logo.' . $e);
                }
              }
            }
            
            osc_add_flash_ok_message(__('The logo image has been uploaded correctly', 'epsilon'), 'admin');
          } else {
            osc_add_flash_error_message(__('An error has occurred, please try again', 'epsilon'), 'admin');
          }
        }
      } else {
        osc_add_flash_error_message(__('An error has occurred, please try again', 'epsilon'), 'admin');
      }
      
      header('Location: ' . osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/logo.php'));
      exit;
      break;

    case('remove'):
      foreach($allowed_ext as $e) {
        if(file_exists(WebThemes::newInstance()->getCurrentThemePath() . 'images/logo.' . $e)) { 
          @unlink( WebThemes::newInstance()->getCurrentThemePath() . 'images/logo.' . $e);
        }
      }

      @unlink( WebThemes::newInstance()->getCurrentThemePath() . 'images/' . eps_logo_is_uploaded());
      osc_add_flash_ok_message(__('The logo image has been removed', 'epsilon'), 'admin');
      
      header('Location: ' . osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/logo.php')); 
      exit;
      break;
  } 

  if(Params::getParam('theme_action') == 'done') {
    osc_add_flash_ok_message(__('Settings were successfully saved','epsilon'), 'admin');
    header('Location:' . osc_admin_render_theme_url('oc-content/themes/epsilon/admin/logo.php'));
    exit;
  }
?>

<div class="mb-body">

  <!-- LOGO PREVIEW -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-display"></i> <?php _e('Logo preview', 'epsilon'); ?></div>

    <div class="mb-inside">
      <form action="<?php echo osc_admin_render_theme_url('oc-content/themes/epsilon/admin/logo.php');?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="theme_action" value="done" />

        <div class="mb-row">
          <label for="default_logo" class=""><span><?php _e('Use Default Logo', 'epsilon'); ?></span></label> 
          <input name="default_logo" id="default_logo" class="element-slide" type="checkbox" <?php echo (eps_param('default_logo') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('If you did not upload any logo yet, osclass default logo will be used.', 'epsilon'); ?></div>
        </div>
      
        <div class="mb-row">
          <label><?php _e('Current logo', 'epsilon'); ?></label> 

          <div class="mb-image-preview">
            <?php if(eps_logo_is_uploaded(false)) { ?>
              <?php echo eps_logo(); ?>
            <?php } else { ?>
              <span><?php _e('No logo found, website title will be used instead.', 'epsilon'); ?></span>
            <?php } ?>
          </div>
        </div>

        <div class="mb-foot">
          <?php if(eps_logo_is_uploaded()) { ?>
            <a href="<?php echo osc_admin_render_theme_url('oc-content/themes/epsilon/admin/logo.php?theme_action=remove');?>" class="mb-button remove"><?php _e('Remove logo', 'epsilon');?></a>
          <?php } ?>
      
          <button type="submit" class="mb-button"><?php _e('Save', 'epsilon');?></button>
        </div>
      </form>

    </div>
  </div>


  <!-- LOGO UPLOAD -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-upload"></i> <?php _e('Logo upload', 'epsilon'); ?></div>

    <form action="<?php echo osc_admin_render_theme_url('oc-content/themes/epsilon/admin/logo.php'); ?>" method="post" enctype="multipart/form-data">
      <input type="hidden" name="theme_action" value="upload_logo" />

      <div class="mb-inside">
        <?php if(is_writable( WebThemes::newInstance()->getCurrentThemePath() . 'images/')) { ?>
          <div class="mb-points">
            <div class="mb-row">- <strong><?php _e('When new logo is uploaded, do not forget to clean your browser cache (CTRL + R or CTRL + F5)', 'epsilon'); ?></strong></div>
            <div class="mb-row">- <?php _e('The preferred size of the logo is 240x64px.', 'epsilon'); ?></div>
            <div class="mb-row">- <?php echo sprintf(__('Following formats are allowed: %s', 'epsilon'), implode(',', $allowed_ext)); ?></div>

            <?php if(eps_logo_is_uploaded()) { ?>
              <div class="mb-row">- <?php _e('Uploading another logo will overwrite the current logo.', 'epsilon'); ?></div>
            <?php } ?>
          </div>

          <input type="file" name="logo" id="package" />
        <?php } else { ?>
          <div class="mb-warning">
            <div class="mb-row">
              <?php
                $msg  = sprintf(__('The images folder <strong>%s</strong> is not writable on your server', 'epsilon'), WebThemes::newInstance()->getCurrentThemePath() ."images/" ) .", ";
                $msg .= __("OSClass can't upload the logo image from the administration panel.", 'epsilon') . ' ';
                $msg .= __('Please make the aforementioned image folder writable.', 'epsilon') . ' ';
                echo $msg;
              ?>
            </div>

            <div class="mb-row">
              <?php _e('To make a directory writable under UNIX execute this command from the shell:','epsilon'); ?>
            </div>

            <div class="mb-row">
              chmod a+w <?php echo WebThemes::newInstance()->getCurrentThemePath() . 'images/'; ?>
            </div>
          </div>
        <?php } ?>
      </div>

      <?php if(!eps_is_demo() || osc_logged_admin_username() == 'admin') { ?>
        <div class="mb-foot">
          <button type="submit" class="mb-button"><?php _e('Upload', 'epsilon');?></button>
        </div>
      <?php } ?>
    </form>
  </div>
</div>


<?php echo eps_footer(); ?>