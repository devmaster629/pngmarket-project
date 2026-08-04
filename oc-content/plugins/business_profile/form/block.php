<?php
  if(isset($limit) && $limit > 0) {
    $per_page = $limit;
  } else {
    $per_page = 5;
  }

  if(!(isset($order) && $order <> '')) {
    $order = 'RANDOM';
  }


  $limit = array($per_page, 0);

  if($order == 'RANDOM') {
    $ids = ModelBPR::newInstance()->getIds($per_page);
  } else {
    $ids = array();
  }

  $sellers = ModelBPR::newInstance()->getSellers(1, -1, -1, $limit, '', '', '', $order, $ids);
?>

<?php if(count($sellers) > 0) { ?>
  <div id="bpr-seller" class="bpr-body bpr-list bpr-block">
    <div class="bpr-title"><?php _e('Recommended companies', 'business_profile'); ?></div>

    <div class="bpr-inside-all">
      <?php foreach($sellers as $seller) { ?>
        <?php 
          $identifier = $seller['s_identifier'];
          $user = User::newInstance()->findByPrimaryKey($seller['fk_i_user_id']); 
          View::newInstance()->_exportVariableToView('user', $user); 

          //$location = implode(', ', array_filter(array($user['s_city'], $user['s_region'], $user['fk_c_country_code'])));
          $location = bpr_user_location($user);

          $count_items = ModelBPR::newInstance()->countItems($user['pk_i_id']);
          $link = osc_route_url('bpr-seller', array('identifier' => $identifier));
        ?>
 
        <a class="bpr-comp" href="<?php echo $link; ?>">
          <div class="bpr-cover">
            <div class="bpr-wrap">
              <div class="bpr-wrap-img" style="background-image:url('<?php echo bpr_get_img($seller['fk_i_user_id'], $seller['s_cover'], 'cover'); ?>');"></div>
              <div class="bpr-elip"></div>
            </div>
          </div>

          <div class="bpr-icon-wrap">
            <div class="bpr-icon">
              <div style="background-image:url('<?php echo bpr_get_img($seller['fk_i_user_id'], $seller['s_icon'], 'icon'); ?>');"></div>
            </div>
          </div>

          <div class="bpr-info">
            <div class="bpr-title"><?php echo $user['s_name']; ?></div>
            <div class="bpr-loc"><?php echo $location; ?></div>

            <div class="bpr-labels">
              <?php echo bpr_user_label($seller); ?>

              <div class="bpr-count"><?php echo sprintf(__('%s listings', 'business_profile'), $count_items); ?></div>
            </div>
          </div>
        </a>
      <?php } ?>
    </div>
  </div>
<?php } ?>