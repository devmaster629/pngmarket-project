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

  // Clear-all keeps current category when browsing a category page.
  $pngm_clear_filters = array('page' => 'search');
  if ($search_cat_id > 0) {
    $pngm_clear_filters['sCategory'] = $search_cat_id;
  }

  $pngm_keyword_ph = __('Word, title or description...', 'epsilon');
  if (is_array($category) && @$category['s_name'] <> '') {
    $pngm_keyword_ph = sprintf(__('Search in %s', 'epsilon'), $category['s_name']);
  }

  $pngm_total_results = (int) osc_search_total_items();
?>

<div class="container primary">
  <div id="search-menu" class="filter-menu">
    <?php osc_run_hook('search_sidebar_pre'); ?>
    
    <div class="wrap">
      <div class="pngm-filters-head">
        <h2><?php _e('Filters', 'epsilon'); ?></h2>
        <a class="pngm-filters-clear" href="<?php echo osc_search_url($pngm_clear_filters); ?>"><?php _e('Clear all', 'epsilon'); ?></a>
      </div>

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

          <div class="input-box picker pattern only-search pngm-no-suggest">
            <input type="text" name="sPattern" id="sPattern" class="pattern" placeholder="<?php echo osc_esc_html($pngm_keyword_ph); ?>" value="<?php echo osc_esc_html(Params::getParam('sPattern')); ?>" autocomplete="off"/>
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
        
        <div class="row buttons srch pngm-filter-apply-only">
          <button type="submit" class="btn mbBg init-search" id="search-button">
            <span><?php _e('Show results', 'epsilon'); ?></span>
          </button>
        </div>
        
        <?php osc_run_hook('search_sidebar_bottom'); ?>
      </form>
    </div>
    
    <div id="search-category-box" class="pngm-desktop-hide">
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
                <?php
                  $pngm_img = function_exists('pngm_get_cat_image') ? pngm_get_cat_image($parent['pk_i_id']) : eps_get_cat_image($parent['pk_i_id']);
                ?>
                <img src="<?php echo $pngm_img; ?>" alt="<?php echo osc_esc_html($parent['s_name']); ?>" class="<?php echo (stripos($pngm_img, '.svg') !== false ? 'pngm-cat-svg' : ''); ?>" />
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
                  <?php
                    $pngm_img = function_exists('pngm_get_cat_image') ? pngm_get_cat_image($c['pk_i_id']) : eps_get_cat_image($c['pk_i_id']);
                  ?>
                  <img src="<?php echo $pngm_img; ?>" alt="<?php echo osc_esc_html($c['s_name']); ?>" class="<?php echo (stripos($pngm_img, '.svg') !== false ? 'pngm-cat-svg' : ''); ?>" />
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


  <div id="search-main" class="<?php echo $view; ?><?php if($search_cat_id > 0) { ?> pngm-has-category<?php } ?>">
    <?php osc_run_hook('search_items_top'); ?>

    <?php
      // CATEGORY-01 — Subcategories before listings (visible on mobile too).
      $pngm_subcats = array();
      $pngm_subcat_parent = null;

      if ($search_cat_id > 0 && function_exists('pngm_subcategories_for')) {
        $pngm_subcats = pngm_subcategories_for($search_cat_id);
        $pngm_subcat_parent = is_array($category) ? $category : eps_get_category($search_cat_id);

        // On a leaf category, show sibling subcategories under the parent.
        if (count($pngm_subcats) === 0 && is_array($pngm_subcat_parent) && @$pngm_subcat_parent['fk_i_parent_id'] > 0) {
          $pngm_subcats = pngm_subcategories_for((int) $pngm_subcat_parent['fk_i_parent_id']);
          $pngm_subcat_parent = eps_get_category((int) $pngm_subcat_parent['fk_i_parent_id']);
        }
      }
    ?>

    <?php if(is_array($pngm_subcats) && count($pngm_subcats) > 0) { ?>
      <div id="pngm-search-subcats" class="pngm-search-subcats">
        <div class="pngm-search-subcats-scroll">
          <div class="pngm-search-subcats-list">
            <?php
              $pngm_all_params = $params_spec;
              if (is_array($pngm_subcat_parent) && @$pngm_subcat_parent['pk_i_id'] > 0) {
                $pngm_all_params['sCategory'] = $pngm_subcat_parent['pk_i_id'];
              }
              $pngm_all_label = __('All', 'epsilon');
              if (is_array($pngm_subcat_parent) && @$pngm_subcat_parent['s_name'] <> '') {
                $pngm_all_label = sprintf(__('All %s', 'epsilon'), $pngm_subcat_parent['s_name']);
              }
            ?>
            <a class="pngm-search-subcat pngm-search-subcat-all<?php if($search_cat_id == @$pngm_subcat_parent['pk_i_id']) { ?> is-active<?php } ?>" href="<?php echo osc_search_url($pngm_all_params); ?>">
              <?php if (function_exists('pngm_render_category_visual') && is_array($pngm_subcat_parent) && @$pngm_subcat_parent['pk_i_id'] > 0) { ?>
                <span class="pngm-search-subcat-ico"><?php echo pngm_render_category_visual($pngm_subcat_parent['pk_i_id'], $pngm_subcat_parent, 0); ?></span>
              <?php } elseif (function_exists('pngm_render_category_icon') && is_array($pngm_subcat_parent) && @$pngm_subcat_parent['pk_i_id'] > 0) { ?>
                <span class="pngm-search-subcat-ico"><?php echo pngm_render_category_icon($pngm_subcat_parent['pk_i_id'], $pngm_subcat_parent, false); ?></span>
              <?php } ?>
              <span class="pngm-search-subcat-name"><?php echo osc_esc_html($pngm_all_label); ?></span>
              <?php if (is_array($pngm_subcat_parent) && @$pngm_subcat_parent['i_num_items'] > 0) { ?>
                <em><?php echo (int) $pngm_subcat_parent['i_num_items']; ?></em>
              <?php } ?>
            </a>
            <?php foreach($pngm_subcats as $pngm_sc) {
              $pngm_sc_params = $params_spec;
              $pngm_sc_params['sCategory'] = $pngm_sc['id'];
              $pngm_sc_parent_id = is_array($pngm_subcat_parent) ? (int) @$pngm_subcat_parent['pk_i_id'] : 0;
            ?>
              <a class="pngm-search-subcat<?php if($search_cat_id == $pngm_sc['id']) { ?> is-active<?php } ?>" href="<?php echo osc_search_url($pngm_sc_params); ?>">
                <?php if (function_exists('pngm_render_category_visual')) { ?>
                  <span class="pngm-search-subcat-ico"><?php echo pngm_render_category_visual($pngm_sc['id'], array('s_name' => $pngm_sc['name']), $pngm_sc_parent_id); ?></span>
                <?php } elseif (function_exists('pngm_render_category_icon')) { ?>
                  <span class="pngm-search-subcat-ico"><?php echo pngm_render_category_icon($pngm_sc['id'], array('s_name' => $pngm_sc['name']), false); ?></span>
                <?php } ?>
                <span class="pngm-search-subcat-name"><?php echo osc_esc_html($pngm_sc['name']); ?></span>
                <?php if($pngm_sc['count'] > 0) { ?>
                  <em><?php echo (int) $pngm_sc['count']; ?></em>
                <?php } ?>
              </a>
            <?php } ?>
          </div>
        </div>
      </div>
    <?php } ?>
    
    <?php osc_run_hook('search_items_filter'); ?>

    <?php if(false && $filter_check > 0) { ?>
      <div id="search-filters" class="pngm-search-chips">
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

            <a class="pngm-chip" href="<?php echo osc_search_url($rem_param); ?>" data-type="<?php echo osc_esc_html(strtolower($v['type'])); ?>" data-param="<?php echo osc_esc_html($v['param']); ?>" title="<?php echo osc_esc_html($v['title'] . ': ' . $v['name']); ?>">
              <span><?php echo osc_esc_html($v['name']); ?></span>
              <i class="fas fa-times" aria-hidden="true"></i>
            </a>
          <?php } ?>
        <?php } ?>

        <a class="pngm-chips-clear" href="<?php echo osc_search_url($pngm_clear_filters); ?>"><?php _e('Clear all', 'epsilon'); ?></a>
      </div>
    <?php } ?>
    
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

    <div class="ajax-load-failed flashmessage flashmessage-error" style="display:none;">
      <p><?php _e('There was problem loading your listings, please try to refresh this page', 'epsilon'); ?></p>
      <a class="btn mini" onClick="window.location.reload();"><i class="fas fa-redo"></i> <?php _e('Refresh', 'epsilon'); ?></a>
    </div>
    
    <div id="search-quick-bar" class="pngm-search-toolbar">
      <div class="pngm-search-count">
        <?php echo sprintf(__('%s results', 'epsilon'), number_format($pngm_total_results)); ?>
      </div>
      <div class="pngm-search-toolbar-actions">
        <a href="#" id="open-search-filters" class="btn pngm-filter-btn isMobile">
          <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M496 384H160v-16c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v16H16c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h80v16c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16v-16h336c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16zm0-160h-80v-16c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v16H16c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h336v16c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16v-16h80c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16zm0-160H288V48c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v16H16C7.2 64 0 71.2 0 80v32c0 8.8 7.2 16 16 16h208v16c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16v-16h208c8.8 0 16-7.2 16-16V80c0-8.8-7.2-16-16-16z"/></svg>
          <span><?php _e('Filter', 'epsilon'); ?></span>
        </a>
        <div class="sort-type pngm-sort-control" data-pngm-sort="1">
          <label class="pngm-sr-only" for="orderSelect"><?php _e('Sort by', 'epsilon'); ?></label>
          <?php echo eps_simple_sort(); ?>
        </div>
      </div>
    </div>

    <?php
      $p1 = $params_all; $p1['sCompany'] = null;
      $p2 = $params_all; $p2['sCompany'] = 0;
      $p3 = $params_all; $p3['sCompany'] = 1;

      $us_type = Params::getParam('sCompany');
    ?>
    
    <div id="filter-user-type" class="pngm-phase-hidden">
      <a class="all<?php if(Params::getParam('sCompany') === '' || Params::getParam('sCompany') === null) { ?> active<?php } ?>" href="<?php echo osc_search_url($p1); ?>"><?php _e('All listings', 'epsilon'); ?></a>
      <a class="personal<?php if(Params::getParam('sCompany') === '0') { ?> active<?php } ?>" href="<?php echo osc_search_url($p2); ?>"><?php _e('Personal', 'epsilon'); ?></a>
      <a class="company<?php if(Params::getParam('sCompany') === '1') { ?> active<?php } ?>" href="<?php echo osc_search_url($p3); ?>"><?php _e('Company', 'epsilon'); ?></a>
    </div>
    
    <div id="search-items">     
      <?php if(osc_count_items() == 0) { ?>
        <div class="pngm-no-results">
          <div class="pngm-no-results-illu" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 160" width="160" height="128" fill="none">
              <rect x="28" y="28" width="110" height="88" rx="10" fill="#EEF2F5" stroke="#D5DEE6" stroke-width="2"/>
              <rect x="38" y="38" width="90" height="10" rx="3" fill="#DDE5EB"/>
              <circle cx="48" cy="43" r="2.5" fill="#C5D0D9"/>
              <circle cx="56" cy="43" r="2.5" fill="#C5D0D9"/>
              <circle cx="64" cy="43" r="2.5" fill="#C5D0D9"/>
              <path d="M48 98c12-18 28-28 44-28s28 8 36 18v8H48v2z" fill="#D7E8DE"/>
              <path d="M62 92c6-10 14-16 24-16 8 0 14 4 20 10" stroke="#B7CFC0" stroke-width="3" stroke-linecap="round" fill="none"/>
              <circle cx="78" cy="72" r="6" fill="#C9D9D0"/>
              <circle cx="102" cy="68" r="8" fill="#C9D9D0"/>
              <circle cx="128" cy="78" r="5" fill="#C9D9D0"/>
              <circle cx="132" cy="108" r="34" fill="#F4F7F9" stroke="#5C6570" stroke-width="8"/>
              <circle cx="132" cy="108" r="22" fill="#fff" stroke="#5C6570" stroke-width="3"/>
              <path d="M156 132l22 22" stroke="#5C6570" stroke-width="10" stroke-linecap="round"/>
            </svg>
          </div>
          <h2 class="pngm-no-results-title"><?php _e('No listings found', 'epsilon'); ?></h2>
          <p class="pngm-no-results-text"><?php _e('Try changing your keyword or filters.', 'epsilon'); ?></p>
          <div class="pngm-no-results-actions">
            <a class="pngm-no-results-btn pngm-no-results-btn-outline" href="<?php echo osc_search_url($pngm_clear_filters); ?>"><?php _e('Clear filters', 'epsilon'); ?></a>
            <a class="pngm-no-results-btn pngm-no-results-btn-solid" href="<?php echo osc_search_url(array('page' => 'search')); ?>"><?php _e('Browse all listings', 'epsilon'); ?></a>
          </div>
        </div>
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

      <div class="paginate"><?php echo eps_fix_arrow(osc_search_pagination()); ?></div>
      
      <?php /* "Other people searched" removed — listings stay higher. */ ?>

      <?php 
        if(eps_param('recent_search') == 1) {
          // Match home dashboard: same count + pngm-card size/design
          eps_recent_ads('pngm-card', eps_param('recent_count'), 'onsearch');
        }
      ?>
      
      <?php osc_run_hook('search_items_bottom'); ?>
    </div>
  </div>
</div>

<?php osc_current_web_theme_path('footer.php') ; ?>

</body>
</html>