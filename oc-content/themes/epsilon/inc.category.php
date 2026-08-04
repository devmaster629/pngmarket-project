<?php $search_params = eps_search_params_all(); ?>

<?php
  // CURRENT CATEGORY
  $search_cat_id = osc_search_category_id();
  $search_cat_id = isset($search_cat_id[0]) ? $search_cat_id[0] : 0;
  $search_cat_full = Category::newInstance()->findByPrimaryKey($search_cat_id);

  // ROOT CATEGORY
  $root_cat_id = Category::newInstance()->findRootCategory($search_cat_id);
  $root_cat_id = isset($root_cat_id['pk_i_id']) ? $root_cat_id['pk_i_id'] : null;
   
  // HIERARCHY OF SEARCH CATEGORY
  $hierarchy = Category::newInstance()->toRootTree($search_cat_id);

  // SUBCATEGORIES OF SEARCH CATEGORY
  $subcats = Category::newInstance()->findSubcategoriesEnabled($search_cat_id);

  if(empty($subcats)) {
    $is_subcat = false;
    $subcats = Category::newInstance()->findSubcategoriesEnabled(isset($search_cat_full['fk_i_parent_id']) ? $search_cat_full['fk_i_parent_id'] : null);
  } else {
    $is_subcat = true;
  }
?>


<?php if(osc_is_home_page()) { ?>
  <div id="home-cat2">
    <div class="inside">
      <h2><?php _e('Discover our categories', 'epsilon'); ?></h2>

      <div class="box">
        <div class="wrap">
          <?php
            if(osc_is_search_page()) {
              $width = 90;
            } elseif(osc_is_home_page()) {
              $width = 96;
            }
          ?>

          <div class="line">
            <?php osc_goto_first_category(); ?>
            <?php while(osc_has_categories()) { ?>
              <?php 
                $search_params['sCategory'] = osc_category_id(); 
                $color = eps_get_cat_color(osc_category_id());
              ?>
       
              <a href="<?php echo osc_search_url($search_params); ?>">
                <div>
                  <?php if(eps_param('cat_icons') == 1) { ?>
                    <i class="fas <?php echo eps_get_cat_icon( osc_category_id(), true ); ?>" <?php if($color <> '') { ?>style="color:<?php echo $color; ?>;"<?php } ?>></i>
                  <?php } else { ?>
                    <img src="<?php echo eps_get_cat_image(osc_category_id()); ?>" alt="<?php echo osc_esc_html(osc_category_name()); ?>" />
                  <?php } ?>
                </div>

                <h3><span><?php echo osc_category_name(); ?></span></h3>
              </a>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php } ?>



<?php if(1==2 && osc_is_search_page() && $search_cat_id > 0) { ?>

  <!-- SUBCATEGORIES -->
  <?php if(count($subcats) > 0) { ?>
    <div class="search-top-cat">
    <div id="sub-cat">
      <div class="inside">
        <div class="navi">
          <?php unset($search_params['sCategory']); ?>
          <a href="<?php echo osc_search_url($search_params); ?>"><?php _e('All categories', 'epsilon'); ?></a>
          <svg style="transform:rotate(-90deg);" viewBox="0 0 32 32" width="20px" height="20px"><defs><path id="mbIconAngle" d="M12.147 25.2c-.462 0-.926-.185-1.285-.556L.57 14.024A2.05 2.05 0 010 12.586c0-.543.206-1.061.571-1.436L10.864.553a1.765 1.765 0 012.62.06c.71.795.683 2.057-.055 2.817l-8.9 9.16 8.902 9.183c.738.76.761 2.024.052 2.815a1.78 1.78 0 01-1.336.612"></path></defs><use fill="currentColor" transform="matrix(0 -1 -1 0 29 24)" xlink:href="#mbIconAngle" fill-rule="evenodd"></use></svg>

          <?php foreach($hierarchy as $h) { ?>
            <?php $search_params['sCategory'] = $h['pk_i_id']; ?>

            <?php if($h['pk_i_id'] <> $search_cat_id) { ?>
              <a href="<?php echo osc_search_url($search_params); ?>"">
                <span class="name"><?php echo $h['s_name']; ?></span>
              </a>

              <svg style="transform:rotate(-90deg);" viewBox="0 0 32 32" width="20px" height="20px"><defs><path id="mbIconAngle" d="M12.147 25.2c-.462 0-.926-.185-1.285-.556L.57 14.024A2.05 2.05 0 010 12.586c0-.543.206-1.061.571-1.436L10.864.553a1.765 1.765 0 012.62.06c.71.795.683 2.057-.055 2.817l-8.9 9.16 8.902 9.183c.738.76.761 2.024.052 2.815a1.78 1.78 0 01-1.336.612"></path></defs><use fill="currentColor" transform="matrix(0 -1 -1 0 29 24)" xlink:href="#mbIconAngle" fill-rule="evenodd"></use></svg>

            <?php } else { ?>
              <span><?php echo $h['s_name']; ?></span>

            <?php } ?>
          <?php } ?>
        </div>


        <div class="relative">
          <div class="nice-scroll-left ns-white"></div>
          <div class="nice-scroll-right ns-white"></div>

          <div class="list nice-scroll">
            <?php $i = 1; ?>
            <?php foreach($subcats as $c) { ?>
              <?php $search_params['sCategory'] = $c['pk_i_id']; ?>

              <div class="link<?php if($i > 11 && count($subcats) > 14) { ?> hidden<?php } ?>">
                <a href="<?php echo osc_search_url($search_params); ?>" class="<?php if($c['pk_i_id'] == $search_cat_id) { ?> active<?php } ?>">
                  <span class="name"><?php echo $c['s_name']; ?></span>

                  <?php if($c['i_num_items'] > 0) { ?>
                    <em>(<?php echo $c['i_num_items']; ?>)</em>
                  <?php } ?>
                </a>
              </div>


              <?php $i++; ?>
            <?php } ?>

            <?php if(count($subcats) > 14) { ?>
              <div class="link show-all">
                <a href="#">
                  <span class="name"><?php echo __('Show all', 'epsilon'); ?></span>
                  <svg viewBox="0 0 32 32" width="20px" height="20px"><defs><path id="mbIconAngle" d="M12.147 25.2c-.462 0-.926-.185-1.285-.556L.57 14.024A2.05 2.05 0 010 12.586c0-.543.206-1.061.571-1.436L10.864.553a1.765 1.765 0 012.62.06c.71.795.683 2.057-.055 2.817l-8.9 9.16 8.902 9.183c.738.76.761 2.024.052 2.815a1.78 1.78 0 01-1.336.612"></path></defs><use fill="currentColor" transform="matrix(0 -1 -1 0 29 24)" xlink:href="#mbIconAngle" fill-rule="evenodd"></use></svg>
                </a>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
    </div>
  <?php } ?>

<?php } ?>