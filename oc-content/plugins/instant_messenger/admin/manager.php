<?php
  // Create menu
  $title = __('Manager', 'instant_messenger');
  im_menu($title);
  
  // GET & UPDATE PARAMETERS
  // $variable = mb_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check or value

  if(Params::getParam('what') == 'remove' && Params::getParam('id') > 0) {
    im_remove_message(Params::getParam('id'));
    
    osc_add_flash_ok_message(__('Message has been removed', 'instant_messenger'), 'admin');
    header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=instant_messenger/admin/manager.php');
    exit;    
  }

  $params = Params::getParamsAsArray();
  $messages = ModelIM::newInstance()->getMessages($params);
  $count_all = ModelIM::newInstance()->getMessages($params, true);
?>



<div class="mb-body">
  
  <!-- REMOVE THREADS -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-list"></i> <?php _e('Messages list', 'instant_messenger'); ?></div>

    <div class="mb-inside">
      <form name="promo_form" id="promo_form" action="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=instant_messenger/admin/manager.php" method="POST" enctype="multipart/form-data" >
        <div id="mb-search-table">
          <div class="mb-col-2">
            <label for="messageId"><?php _e('ID', 'instant_messenger'); ?></label>
            <input type="text" name="messageId" value="<?php echo Params::getParam('messageId'); ?>" />
          </div>
          
          <div class="mb-col-4">
            <label for="thread"><?php _e('Thread', 'instant_messenger'); ?></label>
            <input type="text" name="thread" value="<?php echo Params::getParam('thread'); ?>" />
          </div>
          
          <div class="mb-col-6">
            <label for="message"><?php _e('Message', 'instant_messenger'); ?></label>
            <input type="text" name="message" value="<?php echo Params::getParam('message'); ?>"/>
          </div>
          
          <div class="mb-col-3">
            <label for="from"><?php _e('From', 'instant_messenger'); ?></label>
            <input type="text" name="from" value="<?php echo Params::getParam('from'); ?>" />
          </div>

          <div class="mb-col-3">
            <label for="to"><?php _e('To', 'instant_messenger'); ?></label>
            <input type="text" name="to" value="<?php echo Params::getParam('to'); ?>" />
          </div>
          
          <div class="mb-col-3">
            <label for="">&nbsp;</label>
            <button type="submit" class="mb-button mb-button-black"><i class="fa fa-search"></i> <?php _e('Search', 'instant_messenger'); ?></button>
          </div>
        </div>
      </form>
      
      
      <div class="mb-table mb-table-messages">
        <div class="mb-table-head">
          <div class="mb-col-2"><?php _e('ID', 'instant_messenger');?></div>
          <div class="mb-col-3 mb-align-left"><?php _e('Thread', 'instant_messenger');?></div>
          <div class="mb-col-7 mb-align-left"><?php _e('Message', 'instant_messenger'); ?></div>
          <div class="mb-col-3 mb-align-left"><?php _e('From', 'instant_messenger'); ?></div>
          <div class="mb-col-3 mb-align-left"><?php _e('To', 'instant_messenger'); ?></div>
          <div class="mb-col-3"><?php _e('Date', 'instant_messenger'); ?></div>
          <div class="mb-col-3 mb-align-right">&nbsp;</div>
        </div>

        <?php if(count($messages) <= 0) { ?>
          <div class="mb-table-row mb-row-empty">
            <i class="fa fa-warning"></i><span><?php _e('No messages has been found', 'instant_messenger'); ?></span>
          </div>
        <?php } else { ?>
          <?php foreach($messages as $m) { ?>
            <div class="mb-table-row">
              <div class="mb-col-2"><?php echo $m['pk_i_id']; ?></div>
              <div class="mb-col-3 mb-align-left"><?php echo $m['s_title']; ?></div>
              <div class="mb-col-7 mb-align-left"><?php echo $m['s_message']; ?></div>
              <div class="mb-col-3 mb-align-left">
                <?php if($m['i_from_user_id'] > 0) { ?>
                  <a href="<?php echo osc_admin_base_url(true); ?>?page=users&action=edit&id=<?php echo $m['i_from_user_id']; ?>" target="_blank">
                <?php } ?>
                
                <?php echo $m['s_from_user_name']; ?>

                <?php if($m['i_from_user_id'] > 0) { ?>
                  </a>
                <?php } ?>
              </div>

              <div class="mb-col-3 mb-align-left">
                <?php if($m['i_to_user_id'] > 0) { ?>
                  <a href="<?php echo osc_admin_base_url(true); ?>?page=users&action=edit&id=<?php echo $m['i_to_user_id']; ?>" target="_blank">
                <?php } ?>
                
                <?php echo $m['s_to_user_name']; ?>

                <?php if($m['i_to_user_id'] > 0) { ?>
                  </a>
                <?php } ?>
              </div>

              <div class="mb-col-3 mb-gray mb-i"><?php echo im_get_time_diff($m['d_datetime']); ?></div>
              <div class="mb-col-3 mb-align-right">
                <a href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=instant_messenger/admin/message_edit.php&id=<?php echo $m['pk_i_id']; ?>" class="mb-inv-edit mb-button-blue mb-btn"><i class="fa fa-pencil"></i> <?php _e('Edit', 'invoice'); ?></a>
                <a href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=instant_messenger/admin/manager.php&what=remove&id=<?php echo $m['pk_i_id']; ?>" class="mb-inv-remove mb-btn mb-button-red mb-has-tooltip-light" title="<?php echo osc_esc_html(__('Remove message', 'instant_messenger')); ?>" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to remove this message? Action cannot be undone.', 'instant_messenger')); ?>')"><i class="fa fa-trash"></i></a>
              </div>
            </div>
          <?php } ?>

          <?php 
            $param_string = '&messageId=' . Params::getParam('messageId') . '&from=' . Params::getParam('from') . '&to=' . Params::getParam('to') . '&thread=' . Params::getParam('thread') . '&message=' . Params::getParam('message');
            echo im_admin_paginate('instant_messenger/admin/manager.php', Params::getParam('pageId'), 25, $count_all, '', $param_string); 
          ?>
          
        <?php } ?>
      </div>
    </div>
  </div>
</div>

<?php echo im_footer(); ?>
