<?php
  // Create menu
  $title = __('Banners', 'business_profile');
  bpr_menu($title);


  // GET & UPDATE PARAMETERS
  // $variable = mb_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check or value

  $enable_banners = mb_param_update('enable_banners', 'plugin_action', 'check', 'plugin-business_profile');
  $banner_optimize_adsense = mb_param_update('banner_optimize_adsense', 'plugin_action', 'check', 'plugin-business_profile');

  $banner_values = array();
  
  foreach(bpr_banner_list() as $b) {
    $banner_values[$b['id']] = mb_param_update_code($b['id'], 'plugin_action', 'code', 'plugin-business_profile');
  }


  if(Params::getParam('plugin_action') == 'done') {
    message_ok( __('Banners were successfully updated', 'business_profile') );
  }


?>



<div class="mb-body">
  <div class="mb-notes">
    <div class="mb-line"><?php _e('If you use Banner Ads Plugin, you can use banner or advert directly in theme banner space by defining its ID in bellow form.', 'business_profile'); ?></div>
    <div class="mb-line"><?php _e('Usage examples: {{BANNER-ADS-PLUGIN-HOOK: my_custom_hook}}, {{BANNER-ADS-PLUGIN-BANNER: 123}}, {{BANNER-ADS-PLUGIN-ADVERT: 987}}', 'business_profile'); ?></div>
  </div>
  
  
  <!-- BANNERS SECTION -->
  <div class="mb-box">
    <div class="mb-head">
      <i class="fa fa-bullhorn"></i> <?php _e('Banners', 'business_profile'); ?>
    </div>

    <div class="mb-inside">
      <form name="promo_form" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
        <input type="hidden" name="page" value="plugins" />
        <input type="hidden" name="action" value="renderplugin" />
        <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>banner.php" />
        <input type="hidden" name="plugin_action" value="done" />


        <div class="mb-row">
          <label for="enable_banners"><span><?php _e('Enable Banners', 'business_profile'); ?></span></label> 
          <input type="checkbox" name="enable_banners" id="enable_banners" class="element-slide" <?php echo ($enable_banners == 1 ? 'checked' : ''); ?>/>
          
          <div class="mb-explain"><?php _e('When enabled, banners will be shown in business_profile.', 'business_profile'); ?></div>
        </div>

        <div class="mb-row">
          <label for="banner_optimize_adsense"><span><?php _e('Optimize for Google Adsense', 'business_profile'); ?></span></label> 
          <input type="checkbox" name="banner_optimize_adsense" id="banner_optimize_adsense" class="element-slide" <?php echo ($banner_optimize_adsense == 1 ? 'checked' : ''); ?>/>
          
          <div class="mb-explain"><?php _e('When enabled, banner sizes will be optimized for responsive google adsense ads.', 'business_profile'); ?></div>
        </div>


        <?php foreach(bpr_banner_list() as $b) { ?>
          <div class="mb-row">
            <label for="<?php echo $b['id']; ?>"><span><?php echo $b['name']; ?></span></label> 
            <textarea name="<?php echo $b['id']; ?>" id="<?php echo $b['id']; ?>"><?php echo stripslashes($banner_values[$b['id']]); ?></textarea>

            <div class="mb-explain"><?php echo sprintf(__('Will be shown on %s', 'business_profile'), $b['position']); ?></div>
          </div>
        <?php } ?>


        <div class="mb-row">&nbsp;</div>

        <div class="mb-foot">
          <?php if(!bpr_is_demo()) { ?><button type="submit" class="mb-button"><?php _e('Save', 'business_profile');?></button><?php } ?>
        </div>
      </form>
    </div>
  </div>



</div>

<?php echo bpr_footer(); ?>