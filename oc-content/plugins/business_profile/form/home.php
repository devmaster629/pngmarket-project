<?php
  $pattern = Params::getParam('bpWord');
  $city = Params::getParam('bpCity');
  $category = Params::getParam('bpCategory');

  $categories = ModelBPR::newInstance()->getSearchCategories();
  $cities = ModelBPR::newInstance()->getSearchCities();

  $current_page = Params::getParam('iPage');
  $per_page = (bpr_param('comp_per_page') > 0 ? bpr_param('comp_per_page') : 24);
  $limit = array($per_page, $current_page);

  $count_sellers = ModelBPR::newInstance()->countSellers(1, -1, -1, $pattern, $city, $category);
  $sellers = ModelBPR::newInstance()->getSellers(1, -1, -1, $limit, $pattern, $city, $category);
?>

<div id="bpr-seller" class="bpr-body bpr-list">
  <div class="bpr-inside-all">
    <?php echo bpr_banner('companies_top'); ?>
    
    <div class="bpr-top-search">
      <form action="<?php echo osc_route_url('bpr-list'); ?>" method="POST" class="nocsrf">
        <input type="hidden" name="bprAction" value="search" />

        <div class="bpr-search-left">
          <input type="text" name="bpWord" id="bpr-word" value="<?php echo osc_esc_html($pattern); ?>" placeholder="<?php echo osc_esc_html(__('Search company', 'business_profile')); ?>" />
          <button id="bpr-submit" type="submit"><i class="fa fa-search"></i></button>
        </div>

        <div class="bpr-search-right">
          <select class="bpr-city" name="bpCity">
            <option value=""><?php _e('All cities', 'business_profile'); ?></option>

            <?php foreach($cities as $c) { ?>
              <option value="<?php echo $c['pk_i_id']; ?>" <?php if($city == $c['pk_i_id']) { ?>selected="selected"<?php } ?>><?php echo bpr_city_name($c); ?></option>
            <?php } ?>
          </select>

          <select class="bpr-category" name="bpCategory">
            <option value=""><?php _e('All categories', 'business_profile'); ?></option>

            <?php foreach($categories as $ct) { ?>
              <option value="<?php echo $ct['fk_i_category_id']; ?>" <?php if($category == $ct['fk_i_category_id']) { ?>selected="selected"<?php } ?>><?php echo $ct['s_name']; ?></option>
            <?php } ?>
          </select>
        </div>
      </form>
    </div>
    
    <?php echo bpr_banner('companies_search_bottom'); ?>

    <?php if(count($sellers) <= 0) { ?>
      <div class="bpr-empty"><?php _e('There are no companies registered yet', 'business_profile'); ?></div>
    <?php } else { ?>
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

      <?php if($count_sellers > $per_page) { ?>
        <div id="bpr-pagination">
          <?php   
            $page_id = ((int)$current_page > 1 ? $current_page : 1);
            echo bpr_paginate_sellers(bpr_search_url_extra(), $page_id, $per_page, $count_sellers);
          ?>
        </div>
      <?php } ?>

    <?php } ?>
    
    <?php echo bpr_banner('companies_bottom'); ?>
  </div>
</div>