<?php
  // Create menu
  $title = __('Edit Message', 'instant_messenger');
  im_menu($title);
  
  $id = (int)Params::getParam('id');
  if($id <= 0) {
    $id = (int)Params::getParam('pk_i_id');
  }

  // UPDATE PROFILE
  if(Params::getParam('plugin_action') == 'done') { 
    $data = array(
      's_message' => Params::getParam('s_message'),
    );
    
    ModelIM::newInstance()->updateMessage($data, $id);
    osc_add_flash_ok_message(__('Message has been successfully updated', 'instant_messenger'), 'admin');
    header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=instant_messenger/admin/message_edit.php&id=' . $id);
    exit;
  }
  
  $message = ModelIM::newInstance()->getMessageById($id);

?>


<div class="mb-body">

  <!-- USER PROFILE SECTION -->
  <div class="mb-box mb-bp">
    <div class="mb-head"><i class="fa fa-edit"></i> <?php _e('Edit Message', 'instant_messenger'); ?></div>

    <form name="promo_form" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
      <input type="hidden" name="page" value="plugins" />
      <input type="hidden" name="action" value="renderplugin" />
      <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>message_edit.php" />
      <input type="hidden" name="plugin_action" value="done" />

      <div class="mb-inside">
        <div class="mb-row">
          <label for="pk_i_id"><?php _e('ID', 'instant_messenger'); ?></label>
          <div class="mb-input-box">
            <input type="text" readonly size="8" name="pk_i_id" value="<?php echo $id; ?>"/>
          </div>
        </div>
        
        <div class="mb-row">
          <label for="s_message"><?php _e('Message', 'instant_messenger'); ?></label>
          <div class="mb-input-box">
            <textarea name="s_message" style="min-width:240px;width:360px;"><?php echo $message['s_message']; ?></textarea>
          </div>
        </div>

        <div class="mb-row">&nbsp;</div>
        
        <div class="mb-foot">
          <?php if(im_is_demo()) { ?>
            <a class="mb-button mb-has-tooltip disabled" onclick="return false;" style="cursor:not-allowed;opacity:0.5;" title="<?php echo osc_esc_html(__('This is demo site', 'instant_messenger')); ?>"><?php _e('Save', 'instant_messenger');?></a>
          <?php } else { ?>
            <button type="submit" class="mb-button"><?php _e('Save', 'instant_messenger');?></button>
          <?php } ?>
        </div>
      </form>
    </div>
  </div>


</div>


<?php echo im_footer(); ?>
