<?php
  // Create menu
  $title = __('Configure', 'business_profile');
  bpr_menu($title);

  $locale = (Params::getParam('bprLocale') <> '' ? Params::getParam('bprLocale') : bpr_get_locale());


  // GET & UPDATE PARAMETERS
  // $variable = mb_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check or value

  $hook_header_links = mb_param_update('hook_header_links', 'plugin_action', 'check', 'plugin-business_profile');
  $require_validation = mb_param_update('require_validation', 'plugin_action', 'check', 'plugin-business_profile');
  $auto_validate = mb_param_update('auto_validate', 'plugin_action', 'check', 'plugin-business_profile');
  $features = mb_param_update('features', 'plugin_action', 'value', 'plugin-business_profile');
  $payments = mb_param_update('payments', 'plugin_action', 'value', 'plugin-business_profile');
  $per_page = mb_param_update('per_page', 'plugin_action', 'value', 'plugin-business_profile');
  $comp_per_page = mb_param_update('comp_per_page', 'plugin_action', 'value', 'plugin-business_profile');
  $selectors = mb_param_update('selectors', 'plugin_action', 'value', 'plugin-business_profile');
  $premium_groups = mb_param_update('premium_groups', 'plugin_action', 'value', 'plugin-business_profile');
  $phone_only_logged = mb_param_update('phone_only_logged', 'plugin_action', 'check', 'plugin-business_profile');
  $mask_phone = mb_param_update('mask_phone', 'plugin_action', 'check', 'plugin-business_profile');
  $gallery = mb_param_update('gallery', 'plugin_action', 'check', 'plugin-business_profile');
  $gallery_limit = mb_param_update('gallery_limit', 'plugin_action', 'value', 'plugin-business_profile');
  $only_company_users = mb_param_update('only_company_users', 'plugin_action', 'check', 'plugin-business_profile');
  $user_can_remove_profile = mb_param_update('user_can_remove_profile', 'plugin_action', 'check', 'plugin-business_profile');
  $video = mb_param_update('video', 'plugin_action', 'check', 'plugin-business_profile');
  $video_limit = mb_param_update('video_limit', 'plugin_action', 'value', 'plugin-business_profile');
  $video_url = mb_param_update('video_url', 'plugin_action', 'value', 'plugin-business_profile');
  $legal_notice = mb_param_update('legal_notice', 'plugin_action', 'check', 'plugin-business_profile');
  $apply_subdomain_filter = mb_param_update('apply_subdomain_filter', 'plugin_action', 'check', 'plugin-business_profile');
  $video_layout = mb_param_update('video_layout', 'plugin_action', 'value', 'plugin-business_profile');


  $premium_groups_array = explode(',', $premium_groups);

  $premium_profile = false;
  if(function_exists('osp_param')) {
    if(osp_param('groups_enabled') == 1) {
      $osp_groups = ModelOSP::newInstance()->getGroups();
      $premium_profile = true;
    }
  }

  if(Params::getParam('plugin_action') == 'done') {
    message_ok( __('Settings were successfully saved', 'business_profile') );
  }

  if(Params::getParam('plugin_action') == 'values') {
    $post = Params::getParamsAsArray();


    // REMOVE REMOVED
    $ids = array();
    foreach($post as $n => $v) {
      if(substr($n, 0, 7) === "bpr-fp_") {
        $name = explode('_', $n);
        
        if(@$name[1] > 0) {
          $ids[] = $name[1];
        }
      }
    }

    ModelBPR::newInstance()->deleteValuesByIds(implode(',', $ids), strtoupper(Params::getParam('what')));


    foreach($post as $n => $v) {
      if(substr($n, 0, 7) === "bpr-fp_") {
        $name = explode('_', $n);

        if(@$name[1] <> '') {
          $data = array(
            'fk_c_locale_code' => $locale,
            's_name' => $v,
            's_type' => strtoupper(Params::getParam('what'))
          );

          if(@$name[1] > 0) {
            $data['pk_i_id'] = $name[1];
          }

          ModelBPR::newInstance()->updateValue($data);
        }
      }
    }



    if(Params::getParam('what') == 'feature') {
      message_ok(__('Features were successfully updated', 'business_profile'));
    } else {
      message_ok(__('Payments were successfully updated', 'business_profile'));
    }
  }

  // GENERATE SITEMAP
  if(Params::getParam('bprSitemap') == 'generate') {
    $execution_time = bpr_generate_sitemap();
    message_ok(__('Sitemap generated correctly in', 'business_profile') . ' ' . round($execution_time, 2) . ' ' . __('seconds', 'business_profile') . '. <br/>' . osc_base_url() . 'sitemap_business.xml');
  }

  $features_list = ModelBPR::newInstance()->getValues('feature');
  $payments_list = ModelBPR::newInstance()->getValues('payment');

?>


<div class="mb-body">
  <div class="mb-notes">
    <div class="mb-line"><?php _e('User labels "Basic, Pro & VIP" will be automatically overriden by Osclass Pay Membership Group Name, if seller is member of group. Color of group auto-apply as well to label. Auto-validation setting has no impact on this.', 'business_profile'); ?></div>
    
  </div>

  <!-- CONFIGURE SECTION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-wrench"></i> <?php _e('Configure', 'business_profile'); ?></div>

    <div class="mb-inside mb-minify">
      <form name="promo_form" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
        <?php if(!bpr_is_demo()) { ?>
          <input type="hidden" name="page" value="plugins" />
          <input type="hidden" name="action" value="renderplugin" />
          <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>configure.php" />
          <input type="hidden" name="plugin_action" value="done" />
        <?php } ?>

        <div class="mb-row">
          <label for="require_validation" class="h1"><span><?php _e('Require validation', 'business_profile'); ?></span></label> 
          <input name="require_validation" id="require_validation" type="checkbox" class="element-slide" <?php echo ($require_validation == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('When enabled, each business profile must be validated by admin after submission. Unvalidated profile is visible just to owner and admin.', 'business_profile'); ?></div>
        </div>

        <div class="mb-row">
          <label for="hook_header_links"><span><?php _e('Hook Button to Header', 'business_profile'); ?></span></label> 
          <input name="hook_header_links" id="hook_header_links" type="checkbox" class="element-slide" <?php echo ($hook_header_links == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('When enabled, "Companies" link/button is added to header. Require theme support for Osclass hooks 8.2.', 'business_profile'); ?></div>
        </div>


        <div class="mb-row">
          <label for="per_page" class="h4"><span><?php _e('Items per page', 'business_profile'); ?></span></label> 
          <input name="per_page" size="10" id="per_page" type="text" class="" value="<?php echo $per_page; ?>" />
          
          <div class="mb-explain"><?php _e('How many items show at once on business profile. Note that there is pagination. Default: 24', 'business_profile'); ?></div>
        </div>

        <div class="mb-row">
          <label for="comp_per_page" class="h5"><span><?php _e('Companies per page', 'business_profile'); ?></span></label> 
          <input name="comp_per_page" size="10" id="comp_per_page" type="text" class="" value="<?php echo $comp_per_page; ?>" />
          
          <div class="mb-explain"><?php _e('How many companies show at once companies page. Note that there is pagination. Default: 24', 'business_profile'); ?></div>
        </div>

        <div class="mb-row">
          <label for="selectors" class="h6"><span><?php _e('Color selectors', 'business_profile'); ?></span></label> 
          <input name="selectors" size="100" id="selectors" type="text" class="" value="<?php echo $selectors; ?>" />
          
          <div class="mb-explain"><?php _e('Company can choose brand color. Enter css selectors on those will be applied background color of company. These must be valid CSS selectors. Example: #header-bar,#footer-partner,#footer,#top-bar,#footer-contact,#header', 'business_profile'); ?></div>
        </div>

        <div class="mb-row mb-row-select-multiple h7">
          <label for="premium_groups_multiple"><span><?php _e('Profile Groups', 'business_profile'); ?></span></label> 

          <input type="hidden" name="premium_groups" id="premium_groups" value="<?php echo $premium_groups; ?>"/>
          <select id="premium_groups_multiple" name="premium_groups_multiple" multiple>
            <?php if(!$premium_profile || count($osp_groups) <= 0) { ?>
              <option value="" selected="selected"><?php _e('No groups in Osclass Pay Plugin', 'business_profile'); ?></option>
            <?php } else { ?>
              <?php foreach($osp_groups as $g) { ?>
                <option value="<?php echo $g['pk_i_id']; ?>" <?php if(in_array($g['pk_i_id'], $premium_groups_array)) { ?>selected="selected"<?php } ?>><?php echo $g['s_name']; ?></option>
              <?php } ?>
            <?php } ?>
          </select>

          <div class="mb-explain">
            <?php _e('Select user groups from Osclass Pay Plugin that is required in order to keep business profile active. If user is not member of any of groups, business profile of this user will be disabled.', 'business_profile'); ?><br/>
            <?php _e('When enabled and you choose any group, profiles of users that are not in selected group will be deactivated!', 'business_profile'); ?>
          </div>
        </div>

        <div class="mb-row">
          <label for="auto_validate" class="h8"><span><?php _e('Auto validation', 'business_profile'); ?></span></label> 
          <input name="auto_validate" id="auto_validate" type="checkbox" class="element-slide" <?php echo ($auto_validate == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain">
            <?php _e('When enabled and Osclass Pay groups are required for business profile, once user pay for membership in group, business profile will be activated/validated automatically.', 'business_profile'); ?><br/>
            <?php _e('If you have setup attr field on group in Osclass Pay and is 1,2 or 3, profile type will be updated as well (1-Basic, 2-Pro, 3-VIP).', 'business_profile'); ?>
          </div>
        </div>

        <div class="mb-row">
          <label for="mask_phone"><span><?php _e('Mask Phone Number', 'business_profile'); ?></span></label> 
          <input name="mask_phone" id="mask_phone" type="checkbox" class="element-slide" <?php echo ($mask_phone == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain">
            <?php _e('When enabled, phone number is masked and user must click on it to show it.', 'business_profile'); ?><br/>
          </div>
        </div>

        <div class="mb-row">
          <label for="phone_only_logged"><span><?php _e('Mask Phone for Unlogged', 'business_profile'); ?></span></label> 
          <input name="phone_only_logged" id="phone_only_logged" type="checkbox" class="element-slide" <?php echo ($phone_only_logged == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain">
            <?php _e('When enabled, phone number is masked for non-logged users and user must login in order to be able to see phone number.', 'business_profile'); ?><br/>
          </div>
        </div>

        <div class="mb-row">
          <label for="gallery"><span><?php _e('Photos/Images Gallery', 'business_profile'); ?></span></label> 
          <input name="gallery" id="gallery" type="checkbox" class="element-slide" <?php echo ($gallery == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain">
            <?php _e('When enabled, business users can upload photos/images into their profile. Maximum size of 1 picture is 4MB. Allowed extensions are: jpg, jpeg, gif, png, svg, webp, avif.', 'business_profile'); ?><br/>
          </div>
        </div>

        <div class="mb-row">
          <label for="gallery_limit"><span><?php _e('Maximum Photos in Gallery', 'business_profile'); ?></span></label> 
          <input name="gallery_limit" size="10" min="0" max="100" id="gallery_limit" type="number" class="" value="<?php echo $gallery_limit; ?>" />
          <div class="mb-input-desc"><?php _e('photos', 'business_profile'); ?></div>
          
          <div class="mb-explain"><?php _e('Enter how many photos/images can business user upload into it\'s gallery. Default value: 10', 'business_profile'); ?></div>
        </div>
        

        <div class="mb-row">
          <label for="only_company_users"><span><?php _e('Only Enable to Companies', 'business_profile'); ?></span></label> 
          <input name="only_company_users" id="only_company_users" type="checkbox" class="element-slide" <?php echo ($only_company_users == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain">
            <?php _e('When enabled, only company users will be able to create business profile. Changing this option has no impact on existing profiles.', 'business_profile'); ?><br/>
          </div>
        </div>

        <div class="mb-row">
          <label for="user_can_remove_profile"><span><?php _e('User can Remove Profile', 'business_profile'); ?></span></label> 
          <input name="user_can_remove_profile" id="user_can_remove_profile" type="checkbox" class="element-slide" <?php echo ($user_can_remove_profile == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain">
            <?php _e('When enabled, user can remove its business profile.', 'business_profile'); ?><br/>
          </div>
        </div>


        <div class="mb-row">
          <label for="video"><span><?php _e('Youtube Video', 'business_profile'); ?></span></label> 
          <input name="video" id="video" type="checkbox" class="element-slide" <?php echo ($video == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain">
            <?php _e('When enabled, business users can add their Youtube video links to their profile.', 'business_profile'); ?><br/>
          </div>
        </div>

        <div class="mb-row">
          <label for="video_limit"><span><?php _e('Youtube Video Limit', 'business_profile'); ?></span></label> 
          <input name="video_limit" size="10" min="0" max="20" id="video_limit" type="number" class="" value="<?php echo $video_limit; ?>" />
          <div class="mb-input-desc"><?php _e('videos', 'business_profile'); ?></div>
          
          <div class="mb-explain"><?php _e('Enter how many Youtube video links can business user set for it\'s profile. It is not recommended to go above 10 videos.', 'business_profile'); ?></div>
        </div>

        <div class="mb-row">
          <label for="video_url"><span><?php _e('Youtube Video URL', 'business_profile'); ?></span></label> 
          <input name="video_url" size="40" id="video_url" type="text" class="" value="<?php echo $video_url; ?>" />
          
          <div class="mb-explain"><?php _e('Enter base video URL. This URL will be used as source URL in embedded videos. By default, standard Youtube URL will be used (https://youtube.com/).', 'business_profile'); ?></div>
        </div>

        <div class="mb-row">
          <label for="video_layout"><span><?php _e('Video Gallery Layout', 'business_profile'); ?></span></label> 
          <select name="video_layout">
            <option value="" <?php if($video_layout == '') { ?>selected="selected"<?php } ?>><?php _e('List - One video per line', 'business_profile'); ?></option>
            <option value="GRID" <?php if($video_layout == 'GRID') { ?>selected="selected"<?php } ?>><?php _e('Grid - Two videos per line', 'business_profile'); ?></option>
            <option value="MIXED" <?php if($video_layout == 'MIXED') { ?>selected="selected"<?php } ?>><?php _e('Mixed - Based on number of videos', 'business_profile'); ?></option>
          </select>
          
          <div class="mb-explain"><?php _e('Select layout for video gallery on seller profile page. Mixed will show list for 1-2 videos, grid for 3 and more videos.', 'business_profile'); ?></div>
        </div>

        <div class="mb-row">
          <label for="legal_notice"><span><?php _e('Enable Legal Notice', 'business_profile'); ?></span></label> 
          <input name="legal_notice" id="legal_notice" type="checkbox" class="element-slide" <?php echo ($legal_notice == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain">
            <?php _e('When enabled, users can define legal notice in their profile. These information will be shown under each listing created by user.', 'business_profile'); ?>
          </div>
        </div>

        <div class="mb-row">
          <label for="apply_subdomain_filter"><span><?php _e('Enable Subdomain Filter', 'business_profile'); ?></span></label> 
          <input name="apply_subdomain_filter" id="apply_subdomain_filter" type="checkbox" class="element-slide" <?php echo ($apply_subdomain_filter == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('When enabled, business profiles are filtered on background by subdomain filter (category, country, region, city).', 'business_profile'); ?></div>
            <div class="mb-line"><?php _e('Recommended to use on Osclass 8.3 or higher, will not work on Osclass 8.2 or lower.', 'business_profile'); ?></div>
          </div>
        </div>


        <div class="mb-subtitle-end"></div>


        <div class="mb-row">
          <label><span><?php _e('Sitemap', 'business_profile'); ?></span></label> 
          <a class="mb-button-green mb-regenerate" href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=business_profile/admin/configure.php&bprSitemap=generate"><?php _e('Regenerate sitemap now', 'business_profile'); ?></a>

          <div class="mb-explain"><?php _e('Sitemap is regenerated automatically via cron daily.', 'business_profile'); ?></div>
        </div>
        
        <div class="mb-row">&nbsp;</div>

        <div class="mb-foot">
          <?php if(bpr_is_demo()) { ?>
            <a class="mb-button mb-has-tooltip disabled" onclick="return false;" style="cursor:not-allowed;opacity:0.5;" title="<?php echo osc_esc_html(__('This is demo site', 'business_profile')); ?>"><?php _e('Save', 'business_profile');?></a>
          <?php } else { ?>
            <button type="submit" class="mb-button"><?php _e('Save', 'business_profile');?></button>
          <?php } ?>
        </div>
      </form>
    </div>
  </div>


  <!-- FEATURES SECTION -->
  <div class="mb-box mb-box-features">
    <div class="mb-head">
      <i class="fa fa-bars"></i> <?php _e('Features', 'business_profile'); ?>
      <?php echo bpr_locale_box('configure.php'); ?>
    </div>

    <div class="mb-inside mb-fp">
      <div class="mb-row mb-notes">
        <div class="mb-line"><?php _e('Add all features those business users can choose. Example: Free Wi-Fi, Parking, Coffe;Credit card, Near metro, Non-smoking, ...', 'osclass_pay'); ?></div>
      </div>

      <form name="promo_form" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
        <input type="hidden" name="page" value="plugins" />
        <input type="hidden" name="action" value="renderplugin" />
        <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>configure.php" />
        <input type="hidden" name="plugin_action" value="values" />
        <input type="hidden" name="what" value="feature" />
        <input type="hidden" name="fk_c_locale_code" value="<?php echo $locale; ?>" />
        <input type="hidden" name="bprLocale" value="<?php echo $locale; ?>" />


        <div class="mb-row-placeholder" style="display:none;">
          <div class="mb-row">
            <input type="text" name="bpr-fp_" value="" placeholder="<?php echo osc_esc_html(__('Feature', 'business_profile')); ?>"/>
            <i class="mb-remove-line fa fa-trash-o"></i>
          </div>
        </div>

        <div class="mb-fp-list">
          <?php if(count($features_list) > 0) { ?>
            <?php foreach($features_list as $f) { ?>
              <div class="mb-row">
                <input type="text" value="<?php echo @$f['locales'][$locale]; ?>" name="bpr-fp_<?php echo @$f['pk_i_id']; ?>" placeholder="<?php echo osc_esc_html(__('Feature', 'business_profile')); ?>" required/>
                <i class="mb-remove-line fa fa-trash-o"></i>
              </div>
            <?php } ?>
          <?php } else { ?>
            <div class="mb-row">
              <input type="text" name="bpr-fp_0" value="" placeholder="<?php echo osc_esc_html(__('Feature', 'business_profile')); ?>" required/>
              <i class="mb-remove-line fa fa-trash-o"></i>
            </div>
          <?php } ?>
        </div>

        <a href="#" class="mb-add-new mb-add-feature mb-button-green" data-id="1"><i class="fa fa-plus-circle"></i> <?php _e('Add new feature', 'business_profile'); ?></a>

        <div class="mb-row">&nbsp;</div>

        <div class="mb-foot">
          <?php if(bpr_is_demo()) { ?>
            <a class="mb-button mb-has-tooltip disabled" onclick="return false;" style="cursor:not-allowed;opacity:0.5;" title="<?php echo osc_esc_html(__('This is demo site', 'business_profile')); ?>"><?php _e('Update', 'business_profile');?></a>
          <?php } else { ?>
            <button type="submit" class="mb-button"><?php _e('Update', 'business_profile');?></button>
          <?php } ?>
        </div>
      </form>
    </div>
  </div>


  <!-- PAYMENTS SECTION -->
  <div class="mb-box mb-box-payments">
    <div class="mb-head">
      <i class="fa fa-credit-card"></i> <?php _e('Payments', 'business_profile'); ?>
      <?php echo bpr_locale_box('configure.php'); ?>
    </div>

    <div class="mb-inside mb-fp">
      <div class="mb-row mb-notes">
        <div class="mb-line"><?php _e('Add all payments those business users can choose. Example: Cash, Mastercard, Visa, Paypal, Cash on Delivery, ...', 'osclass_pay'); ?></div>
      </div>

      <form name="promo_form" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
        <input type="hidden" name="page" value="plugins" />
        <input type="hidden" name="action" value="renderplugin" />
        <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>configure.php" />
        <input type="hidden" name="plugin_action" value="values" />
        <input type="hidden" name="what" value="payment" />
        <input type="hidden" name="fk_c_locale_code" value="<?php echo $locale; ?>" />
        <input type="hidden" name="bprLocale" value="<?php echo $locale; ?>" />


        <div class="mb-row-placeholder" style="display:none;">
          <div class="mb-row">
            <input type="text" name="bpr-fp_" value="" placeholder="<?php echo osc_esc_html(__('Payment', 'business_profile')); ?>"/>
            <i class="mb-remove-line fa fa-trash-o"></i>
          </div>
        </div>

        <div class="mb-fp-list">
          <?php if(count($payments_list) > 0) { ?>
            <?php foreach($payments_list as $f) { ?>
              <div class="mb-row">
                <input type="text" value="<?php echo $f['locales'][$locale]; ?>" name="bpr-fp_<?php echo $f['pk_i_id']; ?>" placeholder="<?php echo osc_esc_html(__('Feature', 'business_profile')); ?>" required/>
                <i class="mb-remove-line fa fa-trash-o"></i>
              </div>
            <?php } ?>
          <?php } else { ?>
            <div class="mb-row">
              <input type="text" name="bpr-fp_0" value="" placeholder="<?php echo osc_esc_html(__('Payment', 'business_profile')); ?>" required/>
              <i class="mb-remove-line fa fa-trash-o"></i>
            </div>
          <?php } ?>
        </div>

        <a href="#" class="mb-add-new mb-add-payment mb-button-green" data-id="1"><i class="fa fa-plus-circle"></i> <?php _e('Add new payment', 'business_profile'); ?></a>

        <div class="mb-row">&nbsp;</div>

        <div class="mb-foot">
          <?php if(bpr_is_demo()) { ?>
            <a class="mb-button mb-has-tooltip disabled" onclick="return false;" style="cursor:not-allowed;opacity:0.5;" title="<?php echo osc_esc_html(__('This is demo site', 'business_profile')); ?>"><?php _e('Update', 'business_profile');?></a>
          <?php } else { ?>
            <button type="submit" class="mb-button"><?php _e('Update', 'business_profile');?></button>
          <?php } ?>
        </div>
      </form>
    </div>
  </div>




  <!-- PLUGIN INTEGRATION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-wrench"></i> <?php _e('Plugin Setup', 'business_profile'); ?></div>

    <div class="mb-inside">

      <div class="mb-row">
        <div class="mb-line"><?php _e('Custom functions that can be used:', 'business_profile'); ?>:</div>
        <div class="mb-line"><br/></div>

        <div class="mb-line"><strong><?php echo __('Get links to companies board', 'business_profile'); ?></strong></div>
        <span class="mb-code">&lt;?php if(function_exists('bpr_companies_url')) { echo bpr_companies_url(); } ?&gt;</span>
        <div class="mb-line"><br/></div>

        <div class="mb-line"><strong><?php echo __('Get link to user\'s company profile', 'business_profile'); ?></strong></div>
        <span class="mb-code">&lt;?php if(function_exists('bpr_company_url')) { echo bpr_company_url($user_id); } ?&gt;</span>
        <div class="mb-line"><br/></div>

        <div class="mb-line"><strong><?php echo __('Get user\'s company logo/icon', 'business_profile'); ?></strong></div>
        <span class="mb-code">&lt;?php if(function_exists('bpr_get_user_img')) { echo bpr_get_user_img($user_id); } ?&gt;</span>
        <div class="mb-line"><br/></div>

        <div class="mb-line"><strong><?php echo __('Get company\'s listings on search page', 'business_profile'); ?></strong></div>
        <span class="mb-code">&lt;?php if(function_exists('bpr_company_items_url')) { echo bpr_company_items_url($user_id); } ?&gt;</span>
        <div class="mb-line"><br/></div>

        <div class="mb-line"><strong><?php echo __('Get company\'s brand color', 'business_profile'); ?></strong></div>
        <span class="mb-code">&lt;?php if(function_exists('bpr_company_color')) { echo bpr_company_color($user_id); } ?&gt;</span>
        <div class="mb-line"><br/></div>

        <div class="mb-line"><strong><?php echo __('Get companies block (on home, search or item page)', 'business_profile'); ?></strong></div>
        <span class="mb-code">&lt;?php if(function_exists('bpr_companies_block')) { echo bpr_companies_block($limit, $order); } ?&gt;</span>
        <div class="mb-line"><strong>$limit</strong> - <?php _e('enter number how many profiles should be shown at once', 'business_profile'); ?></div>
        <div class="mb-line"><strong>$order</strong> - <?php _e('there are multiple sorting types for companies', 'business_profile'); ?>:
          <br/>'NEW' - <?php _e('from newest to oldest', 'business_profile'); ?>
          <br/>'ITEMS' - <?php _e('based on number of items specific user has published', 'business_profile'); ?>
          <br/>'RANDOM' - <?php _e('shown in completely random order each time page is refreshed', 'business_profile'); ?>
          <br/>'SEARCH' - <?php _e('consider city & category on search page and show only profiles matching these criteria', 'business_profile'); ?>
        </div>

        <div class="mb-line"><br/></div>


      </div>
    </div>
  </div>
</div>


<?php echo bpr_footer(); ?>