<?php
  require_once 'functions.php';


  // Create menu
  $title = __('Category', 'epsilon');
  eps_menu($title);


  // GET & UPDATE PARAMETERS
  // $variable = eps_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check, value or code

  $cat_icons = eps_param_update('cat_icons', 'theme_action', 'check', 'theme-epsilon');
  $sample_images = eps_param_update('sample_images', 'theme_action', 'check', 'theme-epsilon');

  if(Params::getParam('theme_action') == 'done') { 
    // $upload_dir_small = osc_themes_path() . osc_current_web_theme() . '/images/small_cat/';
    $upload_dir_small = osc_themes_path() . 'epsilon/images/small_cat/';      // child theme cause issue
    $data = Params::getParam('category');

    if(is_array($data) && count($data) > 0) {
      foreach($data as $id => $fields) {
        $array = array();
        
        if(isset($fields['faicon'])) {
          $array['s_icon'] = $fields['faicon'];
        }
        
        if(isset($fields['color'])) {
          $array['s_color'] = $fields['color'];
        }
        

        if(!empty($array)) {
          if(ModelEPS::newInstance()->getCategoryExtraRaw($id) !== false) {
            ModelEPS::newInstance()->updateCategoryExtra($id, $array);
          } else {
            $array['fk_i_category_id'] = $id;
            ModelEPS::newInstance()->insertCategoryExtra($array);
          }
        }

      }
    }
    
    $files = Params::getFiles('category');


    // Upload icons
    if(!empty($files)) {
      if(isset($files['name']) && is_array($files['name']) && count($files['name']) > 0) {
        foreach($files['name'] as $id => $val) { 
          if(@$files['name'][$id]['icon'] != '') {
            $ext = pathinfo($files['name'][$id]['icon'], PATHINFO_EXTENSION);
            $name  = $id . '.' . $ext;
            $allowed_ext = array('png');

            if(!in_array($ext, $allowed_ext)) {
              osc_add_flash_error_message(sprintf(__('There was error when uploading icon for category #%s: %s','epsilon'), $id, __('Extension not allowed, only allowed extension is .png','epsilon')), 'admin');
            } else if(move_uploaded_file($files['tmp_name'][$id]['icon'], $upload_dir_small . $name)) {
              // Everything alright
            } else {
              osc_add_flash_error_message(sprintf(__('It was not possible to upload icon file for category #%s','epsilon'), $id), 'admin');
            }
          } else if(isset($files['name'][$id]['icon']) && $files['name'][$id]['icon'] == '') {
            @unlink($upload_dir_small . $id . '.png');
          }
        }
      }
    }
      

    osc_add_flash_ok_message(__('Settings were successfully saved','epsilon'), 'admin');
    header('Location:' . osc_admin_render_theme_url('oc-content/themes/epsilon/admin/category.php'));
    exit;
  }
  

  if(Params::getParam('theme_action') == 'done') {
    osc_add_flash_ok_message(__('Settings were successfully saved','epsilon'), 'admin');
    header('Location:' . osc_admin_render_theme_url('oc-content/themes/epsilon/admin/category.php'));
    exit;
  }
?>


<div class="mb-body">

  <!-- CATEGORY SECTION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-cogs"></i> <?php _e('Category', 'epsilon'); ?></div>

    <div class="mb-inside mb-minify">
      <form action="<?php echo osc_admin_render_theme_url('oc-content/themes/epsilon/admin/category.php'); ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="theme_action" value="done" />

        <div class="mb-row">
          <label for="cat_icons" class="h1"><span><?php _e('Font Awesome Category Icons', 'epsilon'); ?></span></label> 
          <input name="cat_icons" id="cat_icons" class="element-slide" type="checkbox" <?php echo (eps_param('cat_icons') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain">
            <div class="mb-line"><?php _e('Check to ON if you want to use Font-Awesome icons instead of Small images for categories.', 'epsilon'); ?></div>
            <a href="https://fontawesome.com/v5/search?m=free&s=brands%2Csolid%2Cregular" target="_blank"><?php _e('Font-Awesome v5 icons', 'epsilon'); ?></a>
          </div>
        </div>

        <div class="mb-row">
          <label for="sample_images" class=""><span><?php _e('Use Sample Image Icons', 'epsilon'); ?></span></label> 
          <input name="sample_images" id="sample_images" class="element-slide" type="checkbox" <?php echo (eps_param('sample_images') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php echo sprintf(__('When enabled and theme use image icons, sample icon images from theme folder (%s) will be used.', 'epsilon'), 'oc-content/themes/epsilon/images/small_cat/sample/'); ?></div>
        </div>
        
        

        <div class="mb-row"><h3 class="mb-subtitle" style="padding-left:20px;"><?php _e('Configure categories', 'epsilon'); ?></h3></div>

        <div class="mb-table mb-table-cats">
          <div class="mb-table-head">
            <div class="mb-col-1 id"><?php _e('ID', 'epsilon'); ?></div>
            <div class="mb-col-5 mb-align-left name"><?php _e('Category', 'epsilon'); ?></div>
            <div class="mb-col-3 mb-align-left icon"><?php _e('Icon', 'epsilon'); ?></div>
            <div class="mb-col-3 mb-align-left"><?php _e('Upload icon', 'epsilon'); ?></div>
            <div class="mb-col-3 mb-align-left fa-icon"><a href="https://fontawesome.com/v5/search?m=free&s=brands%2Csolid%2Cregular" target="_blank"><?php _e('FA icon', 'epsilon'); ?></a></div>
            <div class="mb-col-3 mb-align-left"><?php _e('Add FA icon', 'epsilon'); ?></div>
            <div class="mb-col-3 mb-align-left color"><?php _e('Color', 'epsilon'); ?></div>
            <div class="mb-col-3 mb-align-left "><?php _e('Add color', 'epsilon'); ?></div>
          </div>

          <?php eps_has_subcategories_special(Category::newInstance()->toTree(),  0); ?> 
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