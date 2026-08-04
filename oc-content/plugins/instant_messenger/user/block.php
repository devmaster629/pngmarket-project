<?php $blocks = ModelIM::newInstance()->getUserBlocks(osc_logged_user_id()); ?>

<?php if(osc_is_web_user_logged_in()) { ?>
  <div class="im-table im-block im-body">
    <div class="im-table-head">
      <div class="im-col-22 im-align-left">
        <strong><?php _e('Blocked Users', 'instant_messenger'); ?></strong>
      </div>
      <?php if(count($blocks) > 0) { ?>
        <div class="im-col-2"><?php _e('Remove', 'instant_messenger'); ?></div>
      <?php } ?>
    </div>

    <?php if(count($blocks) > 0) { ?>
      <?php foreach($blocks as $b) { ?>
        <div class="im-table-row" data-id="<?php echo $b['pk_i_id']; ?>">
          <div class="im-col-22 im-align-left">
            <?php
              $user = User::newInstance()->findByEmail($b['s_block_email']);

              if(isset($user['s_name'])) { 
                echo $user['s_name'];
              } else {
                echo im_mask_email($b['s_block_email']);
              }
            ?>
          </div>
          <div class="im-col-2"><a href="<?php echo osc_route_url('im-remove-ban', array('remove-id' => $b['pk_i_id'])); ?>" class="im-remove-block" data-id="<?php echo $b['pk_i_id']; ?>"><i class="fa fa-trash-o"></i></a></div>
        </div>
      <?php } ?>
    <?php } else { ?>
      <div class="im-table-row im-empty im-align-center"><?php _e('There are no active blocks', 'instant_messenger'); ?></div>
    <?php } ?>
  </div>
<?php } ?>