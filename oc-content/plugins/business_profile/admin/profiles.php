<?php
  // Create menu
  $title = __('Profiles List', 'business_profile');
  bpr_menu($title);


  // GET & UPDATE PARAMETERS
  // $variable = mb_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check or value


  $def_per_page = 25;
  $params = Params::getParamsAsArray();
  $page = (isset($params['pageId']) ? $params['pageId'] : 0);
  $per_page = (isset($params['per_page']) ? $params['per_page'] : $def_per_page);
  

  // DELETE PROFILE AND ALL ITS FILES (images)
  if(Params::getParam('what') == 'remove' && Params::getParam('id') > 0 && !bpr_is_demo()) { 
    $profile = ModelBPR::newInstance()->getSeller(Params::getParam('id'));
    
    if(isset($profile['fk_i_user_id']) && $profile['fk_i_user_id'] > 0) {
      bpr_removed_user($profile['fk_i_user_id'], false);
    }
    
    ModelBPR::newInstance()->removeProfile(Params::getParam('id'));
    osc_add_flash_ok_message(__('Business profile successfully removed', 'business_profile'), 'admin');
    header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/profiles.php');
    exit;
  }


  // UPDATE
  if(Params::getParam('plugin_action') == 'done') {
    $profiles = ModelBPR::newInstance()->getSellers(-1,-1,-1,array($per_page, $page));
    $post = Params::getParamsAsArray();

    if(count($profiles) > 0) {
      foreach($profiles as $p) {
        $update = $p;

        if(isset($post['exists_' . $p['pk_i_id']])) {
          if(isset($post['enabled_' . $p['pk_i_id']])) {
            $update['b_enabled'] = ($post['enabled_' . $p['pk_i_id']] == 'on' ? 1 : 0);
          } else {
            $update['b_enabled'] = 0;
          }

          if(isset($post['verified_' . $p['pk_i_id']])) {
            $update['b_verified'] = ($post['verified_' . $p['pk_i_id']] == 'on' ? 1 : 0);
          } else {
            $update['b_verified'] = 0;
          }

          if(isset($post['type_' . $p['pk_i_id']])) {
            $update['i_type'] = $post['type_' . $p['pk_i_id']];
          }

          ModelBPR::newInstance()->updateSellerData($update);
        }
      }
    }

    osc_add_flash_ok_message(__('Business profile successfully updated', 'business_profile'), 'admin');
    header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/profiles.php&pageId=' . $page);
    exit;
  }
  

  $profiles = ModelBPR::newInstance()->getSellers(-1,-1,-1,array($per_page, $page));
  $count_all = ModelBPR::newInstance()->countSellers();
?>

<div class="mb-body">
  <!-- LIST SECTION -->
  <div class="mb-box mb-bp">
    <div class="mb-head"><i class="fa fa-wrench"></i> <?php _e('Profiles List', 'business_profile'); ?></div>

    <div class="mb-inside">
      <form name="promo_form" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
        <input type="hidden" name="page" value="plugins" />
        <input type="hidden" name="action" value="renderplugin" />
        <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>profiles.php" />
        <input type="hidden" name="plugin_action" value="done" />
        <input type="hidden" name="pageId" value="<?php echo osc_esc_html($page); ?>" />

        <div class="mb-table mb-table-bp">
          <div class="mb-table-head">
            <div class="mb-col-2 mb-align-left"><?php _e('Logo', 'business_profile');?></div>
            <div class="mb-col-4 mb-align-left"><?php _e('User', 'business_profile');?></div>
            <div class="mb-col-3 mb-align-left"><?php _e('Identifier', 'business_profile'); ?></div>
            <div class="mb-col-2 mb-align-left"><?php _e('Enabled', 'business_profile'); ?></div>
            <div class="mb-col-2 mb-align-left"><?php _e('Verified', 'business_profile'); ?></div>
            <div class="mb-col-2 mb-align-left"><?php _e('Type', 'business_profile'); ?></div>
            <div class="mb-col-2"><?php _e('Items', 'business_profile'); ?></div>
            <div class="mb-col-2"><?php _e('Registered', 'business_profile'); ?></div>
            <div class="mb-col-5 mb-align-right">&nbsp;</div>
          </div>

          <?php if(count($profiles) <= 0) { ?>
            <div class="mb-table-row mb-row-empty">
              <i class="fa fa-warning"></i><span><?php _e('No business profiles has been found', 'business_profile'); ?></span>
            </div>
          <?php } else { ?>
            <?php foreach($profiles as $p) { ?>
              <?php $user = User::newInstance()->findByPrimaryKey($p['fk_i_user_id']); ?>

              <div class="mb-table-row">
                <div class="mb-col-2 mb-col-logo mb-align-left">
                  <div class="mb-logo-wrap">
                    <?php if(bpr_validate_color($p['s_color'])) { ?>
                      <div class="mb-color bpr-has-tooltip" title="<?php echo osc_esc_html(sprintf(__('Company brand color: %s', 'business_profile'), bpr_validate_color($p['s_color']))); ?>" style="background:<?php echo bpr_validate_color($p['s_color']); ?>"></div>
                    <?php } ?>

                    <img src="<?php echo bpr_get_user_img($p['fk_i_user_id']); ?>" />
                  </div>
                </div>
                <div class="mb-col-4 mb-align-left mb-col-name <?php if(bpr_param('require_validation') == 1 && bpr_check_premium()) { ?>mb-vldt<?php } ?>">
                  <input name="exists_<?php echo $p['pk_i_id']; ?>" type="hidden" value="1"/>
                  <a target="_blank" href="<?php echo bpr_company_url($user['pk_i_id']); ?>"><?php echo $user['s_name']; ?> (<?php echo sprintf(__('id: %s', 'business_profile'), $user['pk_i_id']); ?>)</a>

                  <?php if(bpr_param('require_validation') == 1 && bpr_check_premium()) { ?>
                    <?php if(bpr_control_user($p)) { ?>
                      <?php $grp = ModelOSP::newInstance()->getGroup(osp_get_user_group($p['fk_i_user_id'])); ?>
                      <div style="display:block;clear:both;width:100%;height:1px;">&nbsp;</div>
                      <div class="mb-lb-paid mb-is-ok mb-has-tooltip-light" title="<?php echo osc_esc_html(sprintf(__('User is member of group %s', 'business_profile'), '<u>' . $grp['s_name'] . '</u>')); ?>"><?php _e('Paid', 'business_profile'); ?></div>

                    <?php } else { ?>
                      <div style="display:block;clear:both;width:100%;height:1px;">&nbsp;</div>
                      <div class="mb-lb-paid mb-is-fail mb-has-tooltip-light" title="<?php echo osc_esc_html(__('User is not member of any premium group', 'business_profile')); ?>"><?php _e('Unpaid', 'business_profile'); ?></div>

                    <?php } ?>
                  <?php } ?>
                </div>
                <div class="mb-col-3 mb-col-iden mb-align-left"><?php echo $p['s_identifier']; ?></div>
                <div class="mb-col-2 mb-col-chk mb-align-left"><input name="enabled_<?php echo $p['pk_i_id']; ?>" id="enabled" type="checkbox" class="element-slide" <?php echo ($p['b_enabled'] == 1 ? 'checked' : ''); ?> /></div>
                <div class="mb-col-2 mb-col-chk mb-align-left"><input name="verified_<?php echo $p['pk_i_id']; ?>" id="verified" type="checkbox" class="element-slide" <?php echo ($p['b_verified'] == 1 ? 'checked' : ''); ?> /></div>
                <div class="mb-col-2 mb-col-sel mb-align-left">
                  <select id="type" name="type_<?php echo $p['pk_i_id']; ?>">
                    <option value="1" <?php if($p['i_type'] == 1) { ?>selected="selected"<?php } ?>><?php _e('Basic', 'business_profile'); ?></option>
                    <option value="2" <?php if($p['i_type'] == 2) { ?>selected="selected"<?php } ?>><?php _e('Pro', 'business_profile'); ?></option>
                    <option value="3" <?php if($p['i_type'] == 3) { ?>selected="selected"<?php } ?>><?php _e('VIP', 'business_profile'); ?></option>
                  </select>
                </div>
                <div class="mb-col-2 mb-col-itm"><?php echo sprintf(__('%s items', 'business_profile'), ModelBPR::newInstance()->countItems($user['pk_i_id'])); ?></div>
                <div class="mb-col-2 mb-col-reg"><?php echo date('d. M Y', strtotime($user['dt_reg_date'])); ?></div>
                <div class="mb-col-5 mb-col-del mb-align-right">
                  <a href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=business_profile/admin/profile_edit.php&profileId=<?php echo $p['pk_i_id']; ?>" class="mb-bp-edit mb-btn mb-button-blue"><i class="fa fa-pencil"></i> <?php _e('Edit', 'business_profile'); ?></a>

                  <?php if(bpr_is_demo()) { ?>
                    <a href="#" class="mb-bp-remove mb-btn mb-button-red mb-has-tooltip-light mb-disabled" disabled title="This is demo site, you cannot remove profile"><i class="fa fa-trash"></i></a>
                  <?php } else { ?>
                    <a href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=business_profile/admin/profiles.php&what=remove&id=<?php echo $p['pk_i_id']; ?>" class="mb-bp-remove mb-btn mb-button-red mb-has-tooltip-light" title="<?php echo osc_esc_html(__('Remove profile', 'business_profile')); ?>" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to remove this profile? Action cannot be undone.', 'business_profile')); ?>')"><i class="fa fa-trash"></i></a>
                  <?php } ?>
                </div>
              </div>
            <?php } ?>
            
          <?php 
            $param_string = '';
            echo bpr_admin_paginate('business_profile/admin/profiles.php', Params::getParam('pageId'), (Params::getParam('per_page') > 0 ? Params::getParam('per_page') : $def_per_page), $count_all, '', $param_string); 
          ?>
          <?php } ?>
        </div>

        <div class="mb-row"></div>
        
        <a href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=business_profile/admin/profile_edit.php" class="mb-button-green mb-add"><i class="fa fa-plus-circle"></i><?php _e('Create a new profile', 'business_profile'); ?></a>

 
        <div class="mb-row">&nbsp;</div>

        <div class="mb-foot">
          <?php if(bpr_is_demo()) { ?>
            <a class="mb-button mb-has-tooltip mb-disabled" onclick="return false;" style="cursor:not-allowed;opacity:0.5;" title="<?php echo osc_esc_html(__('This is demo site', 'business_profile')); ?>"><?php _e('Update', 'business_profile');?></a>
          <?php } else { ?>
            <button type="submit" class="mb-button"><?php _e('Update', 'business_profile');?></button>
          <?php } ?>
        </div>
      </form>
    </div>
  </div>


</div>


<?php echo bpr_footer(); ?>