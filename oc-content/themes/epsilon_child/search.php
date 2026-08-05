<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="index, follow" />
  <meta name="googlebot" content="index, follow" />
</head>
<body id="search" class="<?php if(eps_device() <> '') { echo 'dvc-' . eps_device(); } ?>">
<?php osc_current_web_theme_path('header.php') ; ?>

<?php 
  if(trim(Params::getParam('sPattern')) != '') {
    eps_pattern_to_cookies(trim(osc_esc_html(Params::getParam('sPattern'))));
  }

  $params_spec = eps_search_params();
  $params_all = eps_search_params_all();

  $search_cat_id = osc_search_category_id();
  $search_cat_id = isset($search_cat_id[0]) ? $search_cat_id[0] : '';

  $category = eps_get_category($search_cat_id);
  $def_cur = (eps_param('def_cur') <> '' ? eps_param('def_cur') : '$');
  $search_params_remove = eps_search_param_remove();
  $exclude_tr_con = explode(',', eps_param('post_extra_exclude'));

  $view = eps_get_search_view();

  // Count usable params for removal
  $filter_check = 0;
  if(is_array($search_params_remove) && count($search_params_remove) > 0) {
    foreach($search_params_remove as $n => $v) { 
      if($v['name'] <> '' && $v['title'] <> '' && $v['to_remove'] === true) { 
        $filter_check++;
      }
    }
  }

  // Get search hooks
  GLOBAL $search_hooks;
  ob_start(); 

  if(osc_search_category_id()) { 
    osc_run_hook('search_form', osc_search_category_id());
  } else { 
    osc_run_hook('search_form');
  }

  //$search_hooks = trim(ob_get_clean());
  //ob_end_flush();

  $search_hooks = trim(ob_get_contents());
  ob_end_clean();

  $search_hooks = trim($search_hooks);
  
  $price_selected = '';
  
  if(Params::getParam('sPriceMin') != '' || Params::getParam('sPriceMax') != '') {
    $price_selected = 'VALUE';
  } else if (Params::getParam('bPriceCheckWithSeller') == 1) {
    $price_selected = 'CHECK';
  } else if (Params::getParam('bPriceFree') == 1) {
    $price_selected = 'FREE';
  }
  
  $search_location = '';
  $search_location_array = array_values(array_filter(array(osc_search_city(), osc_search_region(), osc_search_country())));
  
  if(isset($search_location_array[0]) && $search_location_array[0] != '') {
    $search_location = $search_location_array[0];
  }
  
  if($search_location == '') { //Params::getParam('sLocation') != '') {
    $search_location = Params::getParam('sLocation');
  }
?>

<div class="container primary">
  <div id="search-menu" class="filter-menu">
    <?php osc_run_hook('search_sidebar_pre'); ?>
    
    <div class="wrap">
      <form action="<?php echo osc_base_url(true); ?>" method="GET" class="search-side-form nocsrf">
        <input type="hidden" class="ajaxRun" value=""/>
        <input type="hidden" name="page" value="search"/>
        <input type="hidden" name="sOrder" value="<?php echo osc_esc_html(osc_search_order()); ?>"/>
        <input type="hidden" name="iOrderType" value="<?php $allowedTypesForSorting = Search::getAllowedTypesForSorting(); echo isset($allowedTypesForSorting[osc_search_order_type()]) ? $allowedTypesForSorting[osc_search_order_type()] : ''; ?>" />
        <input type="hidden" name="sCountry" id="sCountry" value="<?php echo osc_esc_html(Params::getParam('sCountry')); ?>"/>
        <input type="hidden" name="sRegion" id="sRegion" value="<?php echo osc_esc_html(Params::getParam('sRegion')); ?>"/>
        <input type="hidden" name="sCity" id="sCity" value="<?php echo osc_esc_html(Params::getParam('sCity')); ?>"/>
        <input type="hidden" name="iPage" id="iPage" value=""/>
        <input type="hidden" name="sShowAs" id="sShowAs" value="<?php echo osc_esc_html(Params::getParam('sShowAs')); ?>"/>
        <input type="hidden" name="userId" value="<?php echo osc_esc_html(Params::getParam('userId')); ?>"/>
        <input type="hidden" name="notFromUserId" value="<?php echo osc_esc_html(Params::getParam('notFromUserId')); ?>"/>

        <?php osc_run_hook('search_sidebar_top'); ?>
        
        <div class="row">
          <label for="sPattern"><?php _e('Keyword', 'epsilon'); ?></label>

          <div class="input-box">
            <input type="text" name="sPattern" id="sPattern" placeholder="<?php echo osc_esc_html(__('Keyword...', 'epsilon')); ?>" value="<?php echo osc_esc_html(Params::getParam('sPattern')); ?>" autocomplete="off"/>
            <i class="clean fas fa-times-circle"></i>
          </div>
        </div>

        <div class="row isMobile">
          <label for="sCategory"><?php _e('Category', 'epsilon'); ?></label>

          <div class="input-box">
            <?php osc_categories_select('sCategory', $category, __('Category...', 'epsilon')) ; ?>
          </div>
        </div>
        
        <div class="row">
          <label for="sLocation"><?php _e('Location', 'epsilon'); ?></label>

          <div class="input-box picker location only-search">
            <input name="sLocation" type="text" class="location-pick" id="sLocation" placeholder="<?php echo osc_esc_html(__('Region, city...', 'epsilon')); ?>" value="<?php echo osc_esc_html($search_location); ?>" autocomplete="off"/>
            <i class="clean fas fa-times-circle"></i>
            <div class="results"></div>
          </div>
        </div>
        
        <?php echo osc_run_hook('search_sidebar_location'); ?>


        <!-- CONDITION --> 
        <?php if($search_cat_id <= 0 || @!in_array($search_cat_id, $exclude_tr_con)) { ?>
          <div class="row condition">
            <label for=""><?php _e('Condition', 'epsilon'); ?></label>
            <div class="input-box"><?php echo eps_simple_condition(); ?></div>
          </div>
        <?php } ?>


        <!-- TRANSACTION --> 
        <?php if($search_cat_id <= 0 || @!in_array($search_cat_id, $exclude_tr_con)) { ?>
          <div class="row transaction">
            <label for=""><?php _e('Transaction', 'epsilon'); ?></label>
            <div class="input-box"><?php echo eps_simple_transaction(); ?></div>
          </div>
        <?php } ?>


        <!-- PRICE -->
        <?php if(eps_check_category_price($search_cat_id)) { ?>
          <div class="row price">
            <label for="sPriceMin"><?php _e('Price range', 'epsilon'); ?> (<?php echo $def_cur; ?>)</label>

            <div class="line input-box">
              <input type="number" class="priceMin" name="sPriceMin" id="sPriceMin" value="<?php echo osc_esc_html(Params::getParam('sPriceMin')); ?>" size="6" maxlength="6" placeholder="<?php echo osc_esc_js(__('Min', 'epsilon')); ?>"/>
              <span class="delim"></span>
              <input type="number" class="priceMax" name="sPriceMax" id="sPriceMax" value="<?php echo osc_esc_html(Params::getParam('sPriceMax')); ?>" size="6" maxlength="6" placeholder="<?php echo osc_esc_js(__('Max', 'epsilon')); ?>"/>
            </div>
            
            <div class="row check-only checkboxes">
              <div class="input-box-check">
                <input type="checkbox" name="bPriceCheckWithSeller" id="bPriceCheckWithSeller" value="1" <?php echo ($price_selected == 'CHECK' ? 'checked="checked"' : ''); ?> />
                <label for="bPriceCheckWithSeller" class="only-check-label"><?php _e('Check with seller', 'epsilon'); ?></label>
              </div>
            </div>
            
            <div class="row free-only checkboxes">
              <div class="input-box-check">
                <input type="checkbox" name="bPriceFree" id="bPriceFree" value="1" <?php echo ($price_selected == 'FREE' ? 'checked="checked"' : ''); ?> />
                <label for="bPriceFree" class="only-free-label"><?php _e('Free', 'epsilon'); ?></label>
              </div>
            </div>
          </div>
        <?php } ?>


        <!-- PERIOD--> 
        <div class="row period">
          <label for="sPriceMin"><?php _e('Period', 'epsilon'); ?></label>
          <div class="input-box"><?php echo eps_simple_period(); ?></div>
        </div>

        <!-- COMPANY --> 
        <div class="row company isMobile">
          <label for="sCompany"><?php _e('Seller type', 'epsilon'); ?></label>
          <div class="input-box"><?php echo eps_simple_seller(); ?></div>
        </div>


        <?php if(osc_images_enabled_at_items()) { ?>
          <div class="row with-picture checkboxes">
            <div class="input-box-check">
              <input type="checkbox" name="bPic" id="bPic" value="1" <?php echo (osc_search_has_pic() ? 'checked="checked"' : ''); ?> />
              <label for="bPic" class="only-picture-label"><?php _e('With picture only', 'epsilon'); ?></label>
            </div>
          </div>
        <?php } ?>

        <div class="row premiums-only checkboxes">
          <div class="input-box-check">
            <input type="checkbox" name="bPremium" id="bPremium" value="1" <?php echo (Params::getParam('bPremium') == 1 ? 'checked="checked"' : ''); ?> />
            <label for="bPremium" class="only-premium-label"><?php _e('Premium items only', 'epsilon'); ?></label>
          </div>
        </div>

        <?php if(1==1) { ?>
          <div class="row phone-only checkboxes">
            <div class="input-box-check">
              <input type="checkbox" name="bPhone" id="bPhone" value="1" <?php echo (Params::getParam('bPhone') == 1 ? 'checked="checked"' : ''); ?> />
              <label for="bPhone" class="only-phone-label"><?php _e('With phone number', 'epsilon'); ?></label>
            </div>
          </div>
        <?php } ?>

        <?php if($search_hooks <> '') { ?>
          <div class="row sidebar-hooks"><?php echo $search_hooks; ?></div>
        <?php } ?>
        
        <div class="row buttons srch">
          <button type="submit" class="btn mbBg init-search" id="search-button">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="18" height="18"><path d="M508.5 468.9L387.1 347.5c-2.3-2.3-5.3-3.5-8.5-3.5h-13.2c31.5-36.5 50.6-84 50.6-136C416 93.1 322.9 0 208 0S0 93.1 0 208s93.1 208 208 208c52 0 99.5-19.1 136-50.6v13.2c0 3.2 1.3 6.2 3.5 8.5l121.4 121.4c4.7 4.7 12.3 4.7 17 0l22.6-22.6c4.7-4.7 4.7-12.3 0-17zM208 368c-88.4 0-160-71.6-160-160S119.6 48 208 48s160 71.6 160 160-71.6 160-160 160z"/></svg>
            <span><?php _e('Search', 'epsilon'); ?></span>
          </button>
        </div>
        
        <?php osc_run_hook('search_sidebar_bottom'); ?>
      </form>
    </div>
    
    <div id="search-category-box">
      <h3><?php _e('Select category', 'epsilon'); ?></h3>
      <div class="wrap">
        <?php
          $search_params = $params_spec;
          $only_root = false;

          if($search_cat_id <= 0) {
            $parent = false;
            $categories = Category::newInstance()->findRootCategoriesEnabled();
            $children = false;
          } else {
            $parent = eps_get_category($search_cat_id);
            $categories = Category::newInstance()->findSubcategoriesEnabled($search_cat_id);

            if(count($categories) <= 0) {
              if($parent['fk_i_parent_id'] > 0) {
                $parent = eps_get_category($parent['fk_i_parent_id']);
                $categories = Category::newInstance()->findSubcategoriesEnabled($parent['pk_i_id']);

              } else {  // only parent categories exists
                $parent = false;
                $categories = Category::newInstance()->findRootCategoriesEnabled();
                $only_root = true;
              }
            }
          }          
        ?>

        <div class="catbox <?php if($search_cat_id <= 0 || $only_root) { ?>root<?php } else { ?>notroot<?php } ?> nice-scroll">
          <?php if($parent) { ?>
            <?php $search_params['sCategory'] = $parent['pk_i_id']; ?>
            <a href="<?php echo osc_search_url($search_params); ?>" class="parent active" data-name="sCategory" data-val="<?php echo $parent['pk_i_id']; ?>">
              <?php $color = eps_get_cat_color($parent['pk_i_id'], $parent); ?>
              
              <div class="icon">
                <?php if(eps_param('cat_icons') == 1) { ?>
                  <?php 
                    $icon = eps_get_cat_icon($parent['pk_i_id'], $parent, true);
                    $icon_ = explode(' ', $icon);
                    
                    $has_type = false;
                    if(in_array($icon_, array('fas', 'far', 'fab'))) {
                      $has_type = true;
                    }
                  ?>
                  <i class="<?php echo ($has_type ? '' : 'fas'); ?> <?php echo $icon; ?>" <?php if($color <> '') { ?>style="color:<?php echo $color; ?>;"<?php } ?>></i>
                <?php } else { ?>
                  <img src="<?php echo eps_get_cat_image($parent['pk_i_id']); ?>" alt="<?php echo osc_esc_html($parent['s_name']); ?>" />
                <?php } ?>
              </div>

              <div>
                <span class="name"><?php echo $parent['s_name']; ?></span>
                <?php echo ($parent['i_num_items'] > 0 ? '<em>' . $parent['i_num_items'] . '</em>' : ''); ?>
              </div>
            </a>
          <?php } ?>

          <?php foreach($categories as $c) { ?>
            <?php $search_params['sCategory'] = $c['pk_i_id']; ?>

            <a href="<?php echo osc_search_url($search_params); ?>" class="child<?php if($c['pk_i_id'] == $search_cat_id) { ?> active<?php } ?>" data-name="sCategory" data-val="<?php echo $c['pk_i_id']; ?>">
              <?php if($search_cat_id <= 0 || $only_root) { ?>
                <?php $color = eps_get_cat_color($c['pk_i_id'], $c); ?>
              
                <div class="icon">
                  <?php if(eps_param('cat_icons') == 1) { ?>
                    <?php 
                      $icon = eps_get_cat_icon($c['pk_i_id'], $c, true);
                      $icon_ = explode(' ', $icon);
                      
                      $has_type = false;
                      if(in_array($icon_, array('fas', 'far', 'fab'))) {
                        $has_type = true;
                      }
                    ?>
                    <i class="<?php echo ($has_type ? '' : 'fas'); ?> <?php echo $icon; ?>" <?php if($color <> '') { ?>style="color:<?php echo $color; ?>;"<?php } ?>></i>
                  <?php } else { ?>
                    <img src="<?php echo eps_get_cat_image($c['pk_i_id']); ?>" alt="<?php echo osc_esc_html($c['s_name']); ?>" />
                  <?php } ?>
                </div>
              <?php } ?>
              
              <div>
                <span class="name"><?php echo $c['s_name']; ?></span>
                <?php echo ($c['i_num_items'] > 0 ? '<em>' . $c['i_num_items'] . '</em>' : ''); ?>
              </div>              
            </a>
          <?php } ?>

        </div>
        
        <?php if($search_cat_id > 0 && !$only_root) { ?>  
          <?php $search_params['sCategory'] = (@$parent['pk_i_id'] <> $search_cat_id ? @$parent['pk_i_id'] : @$parent['fk_i_parent_id']); ?>
          <a href="<?php echo osc_search_url($search_params); ?>" class="gotop" data-name="sCategory" data-val="<?php echo $parent['fk_i_parent_id']; ?>"><i class="fas fa-level-up-alt fa-flip-horizontal"></i> <?php _e('One level up', 'epsilon'); ?></a>
        <?php } ?>
      </div>
    </div>
    
    <?php echo eps_banner('search_sidebar'); ?>
    <?php osc_run_hook('search_sidebar_after'); ?>
  </div>


  <div id="search-main" class="<?php echo $view; ?>">
    <?php osc_run_hook('search_items_top'); ?>
    
    <div class="top-bar">
      <h1>
        <?php 
          $loc = @array_values(array_filter(array(osc_search_city(), osc_search_region(), osc_search_country())))[0];
          $cat = (isset($category['s_name']) ? $category['s_name'] : '');
          $tit = implode(', ', array_filter(array($cat, $loc)));

          if(osc_search_total_items() <= 0) { 
            echo __('No exact results found', 'epsilon');
            
          } elseif($tit != '') {
            echo sprintf(__('%s results found in %s', 'epsilon'), osc_search_total_items(), $tit);
          } else {
            echo sprintf(__('%s results found', 'epsilon'), osc_search_total_items());
          }
        ?>
      </h1>
    </div>
    
    <?php osc_run_hook('search_items_filter'); ?>
    
    <?php
      osc_get_premiums(20); //eps_param('premium_search_count')
    ?>

    <?php if(osc_count_premiums() > 0 && eps_param('premium_search') == 1) { ?>
      <div id="search-premium-items">
        <h2><?php echo __('Premium listings', 'epsilon'); ?></h2>

        <?php
          $default_items = View::newInstance()->_get('items'); 
          View::newInstance()->_exportVariableToView('items', View::newInstance()->_get('premiums'));
        ?>
        
        <div class="nice-scroll-wrap">
          <div class="nice-scroll-prev"><i class="fas fa-caret-left"></i></div>
          
          <div class="products grid nice-scroll no-visible-scroll">
            <?php 
              $c = 1;

              while(osc_has_items()) {
                eps_draw_item($c, false, 'premium-loop ' . eps_param('premium_search_design'));
                $c++;
              }
              
              if(eps_param('search_premium_promote_url') != '') { 
                eps_draw_placeholder_item($c, 'premium-loop ' . eps_param('premium_search_design')); 
              }
            ?>
          </div>
          
          <div class="nice-scroll-next"><i class="fas fa-caret-right"></i></div>
        </div>
        
        <?php View::newInstance()->_exportVariableToView('items', $default_items); ?>
      </div>
    <?php } ?>

    <a href="#" id="open-search-filters" class="btn isMobile">
      <div class="svg-wrap"><svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M496 384H160v-16c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v16H16c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h80v16c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16v-16h336c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16zm0-160h-80v-16c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v16H16c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h336v16c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16v-16h80c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16zm0-160H288V48c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v16H16C7.2 64 0 71.2 0 80v32c0 8.8 7.2 16 16 16h208v16c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16v-16h208c8.8 0 16-7.2 16-16V80c0-8.8-7.2-16-16-16z"/></svg></div>
      <span><?php _e('Filter results', 'epsilon'); ?></span>
    </a>

    <div class="ajax-load-failed flashmessage flashmessage-error" style="display:none;">
      <p><?php _e('There was problem loading your listings, please try to refresh this page', 'epsilon'); ?></p>
      <a class="btn mini" onClick="window.location.reload();"><i class="fas fa-redo"></i> <?php _e('Refresh', 'epsilon'); ?></a>
    </div>
    
    <div id="search-quick-bar">
      <?php eps_save_search_section('top'); ?>

      <?php if(osc_count_items() > 0) { ?>
        <div class="view-type">
          <a href="<?php echo osc_update_search_url(array('sShowAs' => 'grid')); ?>" title="<?php echo osc_esc_html(__('Grid view', 'epsilon')); ?>" class="<?php echo ($view == 'grid' ? 'active' : ''); ?> grid" data-view="grid">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="18" height="18"><path d="M120 0H24C10.75 0 0 10.74 0 24v96c0 13.25 10.75 24 24 24h96c13.26 0 24-10.75 24-24V24c0-13.26-10.74-24-24-24zM96 96H48V48h48v48zM296 0h-96c-13.25 0-24 10.74-24 24v96c0 13.25 10.75 24 24 24h96c13.26 0 24-10.75 24-24V24c0-13.26-10.74-24-24-24zm-24 96h-48V48h48v48zM120 368H24c-13.25 0-24 10.74-24 24v96c0 13.25 10.75 24 24 24h96c13.26 0 24-10.75 24-24v-96c0-13.26-10.74-24-24-24zm-24 96H48v-48h48v48zm200-96h-96c-13.25 0-24 10.74-24 24v96c0 13.25 10.75 24 24 24h96c13.26 0 24-10.75 24-24v-96c0-13.26-10.74-24-24-24zm-24 96h-48v-48h48v48zM120 184H24c-13.25 0-24 10.74-24 24v96c0 13.25 10.75 24 24 24h96c13.26 0 24-10.75 24-24v-96c0-13.26-10.74-24-24-24zm-24 96H48v-48h48v48zm200-96h-96c-13.25 0-24 10.74-24 24v96c0 13.25 10.75 24 24 24h96c13.26 0 24-10.75 24-24v-96c0-13.26-10.74-24-24-24zm-24 96h-48v-48h48v48z"/></svg>
            <span><?php _e('Grid', 'epsilon'); ?></span>
          </a>
          
          <a href="<?php echo osc_update_search_url(array('sShowAs' => 'list')); ?>" title="<?php echo osc_esc_html(__('List view', 'epsilon')); ?>" class="<?php echo ($view == 'list' ? 'active' : ''); ?> list" data-view="list">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="18" height="18"><path d="M436 124H12c-6.627 0-12-5.373-12-12V80c0-6.627 5.373-12 12-12h424c6.627 0 12 5.373 12 12v32c0 6.627-5.373 12-12 12zm0 160H12c-6.627 0-12-5.373-12-12v-32c0-6.627 5.373-12 12-12h424c6.627 0 12 5.373 12 12v32c0 6.627-5.373 12-12 12zm0 160H12c-6.627 0-12-5.373-12-12v-32c0-6.627 5.373-12 12-12h424c6.627 0 12 5.373 12 12v32c0 6.627-5.373 12-12 12z"/></svg>
            <span><?php _e('List', 'epsilon'); ?></span>
          </a>
          
          <a href="<?php echo osc_update_search_url(array('sShowAs' => 'detail')); ?>" title="<?php echo osc_esc_html(__('Detail view', 'epsilon')); ?>" class="<?php echo ($view == 'detail' ? 'active' : ''); ?> detail" data-view="detail">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="18" height="18"><path d="M288 48v32c0 6.627-5.373 12-12 12H12C5.373 92 0 86.627 0 80V48c0-6.627 5.373-12 12-12h264c6.627 0 12 5.373 12 12zM12 220h424c6.627 0 12-5.373 12-12v-32c0-6.627-5.373-12-12-12H12c-6.627 0-12 5.373-12 12v32c0 6.627 5.373 12 12 12zm0 256h424c6.627 0 12-5.373 12-12v-32c0-6.627-5.373-12-12-12H12c-6.627 0-12 5.373-12 12v32c0 6.627 5.373 12 12 12zm264-184H12c-6.627 0-12 5.373-12 12v32c0 6.627 5.373 12 12 12h264c6.627 0 12-5.373 12-12v-32c0-6.627-5.373-12-12-12z"/></svg>
            <span><?php _e('Detail', 'epsilon'); ?></span>
          </a>
        </div>
        
        <div class="sort-type">
          <label for="orderSelect"><?php _e('Sort', 'epsilon'); ?></label>
          <?php echo eps_simple_sort(); ?>
        </div>
      <?php } ?>
    </div>

    <?php
      $p1 = $params_all; $p1['sCompany'] = null;
      $p2 = $params_all; $p2['sCompany'] = 0;
      $p3 = $params_all; $p3['sCompany'] = 1;

      $us_type = Params::getParam('sCompany');
    ?>
    
    <div id="filter-user-type">
      <a class="all<?php if(Params::getParam('sCompany') === '' || Params::getParam('sCompany') === null) { ?> active<?php } ?>" href="<?php echo osc_search_url($p1); ?>"><?php _e('All listings', 'epsilon'); ?></a>
      <a class="personal<?php if(Params::getParam('sCompany') === '0') { ?> active<?php } ?>" href="<?php echo osc_search_url($p2); ?>"><?php _e('Personal', 'epsilon'); ?></a>
      <a class="company<?php if(Params::getParam('sCompany') === '1') { ?> active<?php } ?>" href="<?php echo osc_search_url($p3); ?>"><?php _e('Company', 'epsilon'); ?></a>
    </div>

    <?php if($filter_check > 0) { ?>
      <div id="search-filters">
        <?php foreach($search_params_remove as $n => $v) { ?>
          <?php if($v['name'] <> '' && $v['title'] <> '' && $v['to_remove'] === true) { ?>
            <?php
              $rem_param = $params_all;

              if($v['is_meta'] === true) {
                unset($rem_param['meta'][$v['field_id']]);
              } else {
                unset($rem_param[$n]);
              }
              
              if(in_array($n, array('sCity','city','sRegion','region','sCountry','country'))) {
                unset($rem_param['sLocation']);
              }
            ?>

            <a href="<?php echo osc_search_url($rem_param); ?>" data-type="<?php echo osc_esc_html(strtolower($v['type'])); ?>" data-param="<?php echo osc_esc_html($v['param']); ?>" title="<?php echo osc_esc_html($v['title'] . ': ' . $v['name']); ?>"><?php echo $v['title'] . ': ' . $v['name']; ?></a>
          <?php } ?>
        <?php } ?>

        <?php if($filter_check >= 2) { ?>
          <a class="bold remove-all-filters" href="<?php echo osc_search_url(array('page' => 'search')); ?>"><?php _e('Remove all', 'epsilon'); ?></a>
        <?php } ?>
      </div>
    <?php } ?>
    
    <div id="search-items">     
      <?php if(osc_count_items() == 0) { ?>
        <?php
          $pngm_location_label = function_exists('pngm_active_location_label') ? pngm_active_location_label() : '';
          $pngm_clear_params = array('page' => 'search', 'pngmClearLocation' => 1);
          if(Params::getParam('sPattern') <> '') {
            $pngm_clear_params['sPattern'] = Params::getParam('sPattern');
          }
          if(Params::getParam('sCategory') <> '') {
            $pngm_clear_params['sCategory'] = Params::getParam('sCategory');
          }
          $pngm_clear_location_url = osc_search_url($pngm_clear_params);
          $pngm_fallback_items = function_exists('pngm_newest_listings_nearby') ? pngm_newest_listings_nearby(12) : array();
        ?>
        <div class="list-empty round3 pngm-empty-search">
          <span class="titles"><?php _e('No exact results found', 'epsilon'); ?></span>
          <p class="pngm-empty-lead"><?php _e('We could not find listings that match your search. Try a different keyword, or browse the newest ads below.', 'epsilon'); ?></p>

          <div class="pngm-empty-location">
            <?php if($pngm_location_label <> '') { ?>
              <span class="pngm-empty-loc-label">
                <i class="fas fa-map-marker-alt"></i>
                <?php echo sprintf(__('Showing suggestions for %s', 'epsilon'), osc_esc_html($pngm_location_label)); ?>
              </span>
            <?php } else { ?>
              <span class="pngm-empty-loc-label">
                <i class="fas fa-map-marker-alt"></i>
                <?php _e('Showing the newest listings', 'epsilon'); ?>
              </span>
            <?php } ?>

            <div class="pngm-empty-loc-actions">
              <a href="#" class="btn btn-secondary mini change-location change-search-location"><?php _e('Change location', 'epsilon'); ?></a>
              <?php if($pngm_location_label <> '') { ?>
                <a href="<?php echo $pngm_clear_location_url; ?>" class="btn btn-white mini"><?php _e('Clear location', 'epsilon'); ?></a>
              <?php } ?>
            </div>
          </div>

          <div class="tips">
            <div class="row"><?php _e('Tips for better results', 'epsilon'); ?></div>
            <div class="row"><i class="fa fa-circle"></i><?php _e('Use more general keywords', 'epsilon'); ?></div>
            <div class="row"><i class="fa fa-circle"></i><?php _e('Check spelling', 'epsilon'); ?></div>
            <div class="row"><i class="fa fa-circle"></i><?php _e('Reduce filters, use less of them', 'epsilon'); ?></div>
            <div class="row last"><a href="<?php echo osc_search_url(array('page' => 'search'));?>"><?php _e('Reset filter', 'epsilon'); ?> &#8594;</a></div>
          </div>
        </div>

        <?php if(is_array($pngm_fallback_items) && count($pngm_fallback_items) > 0) { ?>
          <?php
            $pngm_default_items = View::newInstance()->_get('items');
            View::newInstance()->_exportVariableToView('items', $pngm_fallback_items);
          ?>
          <div class="pngm-empty-suggestions">
            <h2>
              <?php
                if($pngm_location_label <> '') {
                  echo sprintf(__('Newest listings near %s', 'epsilon'), osc_esc_html($pngm_location_label));
                } else {
                  _e('Newest listings', 'epsilon');
                }
              ?>
            </h2>
            <div class="products grid">
              <?php
                $c = 1;
                while(osc_has_items()) {
                  eps_draw_item($c, false, 'pngm-card');
                  $c++;
                }
              ?>
            </div>
          </div>
          <?php View::newInstance()->_exportVariableToView('items', $pngm_default_items); ?>
        <?php } ?>

      <?php } else { ?>
        <?php echo eps_banner('search_top'); ?>

        <div class="products <?php echo $view; ?>">
          <?php 
            $c = 1; 
            while(osc_has_items()) {
              eps_draw_item($c, false, eps_param('def_design'));

              if($c == 3 && osc_count_items() > 3) {
                echo eps_banner('search_middle');
              }

              $c++;
            } 
          ?>
        </div>
      <?php } ?>
      
      <?php echo eps_banner('search_bottom'); ?>
      
      <?php if(osc_count_items() > 0) { ?>
        <?php eps_get_latest_searches(32) ?>
        <?php if(osc_count_latest_searches() > 0) { ?>
          <div id="latest-search">
            <h3><?php _e('Other people searched', 'epsilon'); ?></h3>
            <div class="wrap">
              <?php $i = 0; ?>
              <?php while(osc_has_latest_searches()) { ?>
                <?php 
                  if($i > 16) { break; } 
                  $i++;
                ?>
               
                <a href="<?php echo osc_search_url(array('page' => 'search', 'sPattern' => osc_latest_search_text())); ?>"><?php echo osc_highlight(osc_latest_search_text(), 20); ?></a>
              <?php } ?>
            </div>
          </div>
        <?php } ?>
      <?php } ?>

      <?php 
        if(eps_param('recent_search') == 1) {
          eps_recent_ads(eps_param('recent_design'), eps_param('recent_count'), 'onsearch');
        }
      ?>
      
      <?php osc_run_hook('search_items_bottom'); ?>
      
      <div class="paginate"><?php echo eps_fix_arrow(osc_search_pagination()); ?></div>
    </div>
  </div>
</div>

<?php osc_current_web_theme_path('footer.php') ; ?>

</body>
</html>