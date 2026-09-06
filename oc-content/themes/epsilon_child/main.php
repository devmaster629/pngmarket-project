<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()) ; ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
</head>

<body id="home" class="layout-<?php echo (eps_param('home_layout') <> '' ? eps_param('home_layout') : 'default'); ?><?php if(eps_device() <> '') { echo ' dvc-' . eps_device(); } ?>">
  <?php osc_current_web_theme_path('header.php'); ?>
  
  <?php osc_run_hook('home_top'); ?>

  <?php $pngm_banner_top = pngm_banner('home_top'); if($pngm_banner_top !== false) { ?>
    <div class="container banner-box<?php if(eps_is_demo()) { ?> is-demo<?php } ?>"><div class="inside"><?php echo $pngm_banner_top; ?></div></div>
  <?php } ?>
  
  <?php $location_cookie = eps_location_from_cookies(); ?>

  <?php osc_run_hook('home_search_pre'); ?>
  
  <section class="home-search">
    <div class="container pngm-home-banner">
      <div class="box">
        <h1 class="pngm-title isDesktop"><span class="pngm-w-buy"><?php _e('Buy', 'epsilon'); ?></span> <span class="pngm-sep">&ndash;</span> <span class="pngm-w-sell"><?php _e('Sell', 'epsilon'); ?></span> <span class="pngm-sep">&ndash;</span> <span class="pngm-w-find"><?php _e('Find Anything', 'epsilon'); ?></span></h1>

        <form action="<?php echo osc_base_url(true); ?>" method="GET" class="nocsrf">
          <input type="hidden" name="page" value="search" />
          
          <?php osc_run_hook('home_search_top'); ?>

          <?php // Keyword search is always nationwide — default location only affects browse sections. ?>
          
          <div class="input-box picker pattern">
            <input type="text" name="sPattern" class="pattern" placeholder="<?php _e('Search cars, phones, furniture, and more...', 'epsilon'); ?>" value="<?php echo osc_esc_html(Params::getParam('sPattern')); ?>" autocomplete="off"/>
            <i class="clean fas fa-times-circle"></i>
            <div class="results">
              <div class="loaded"></div>
              <div class="default"><?php eps_default_pattern_content(); ?></div>
            </div>
          </div>
          
          <button class="btn" type="submit"><i class="fa fa-search"></i> <span><?php _e('Search', 'epsilon'); ?></span></button>

          <?php osc_run_hook('home_search_bottom'); ?>
        </form>

        <h2 class="pngm-cats-heading"><?php _e('Categories', 'epsilon'); ?></h2>
        
        <div id="home-cat">
          <?php 
            osc_goto_first_category(); 
            $new_categories = explode(',', eps_param('categories_new'));
            $hot_categories = explode(',', eps_param('categories_hot'));
          ?>
          
          <div class="pngm-cat-item">
          <a href="<?php echo osc_search_url(array('page' => 'search')); ?>" class="all">
            <div>
              <svg class="pngm-cat-svg pngm-cat-all" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="32" height="32" aria-hidden="true"><rect x="5" y="5" width="16" height="16" rx="4" fill="currentColor"/><rect x="27" y="5" width="16" height="16" rx="4" fill="currentColor"/><rect x="5" y="27" width="16" height="16" rx="4" fill="currentColor"/><rect x="27" y="27" width="16" height="16" rx="4" fill="currentColor"/></svg>
            </div>

            <h3><span><?php _e('All', 'epsilon'); ?></span></h3>
          </a>
          </div>
            
          <?php while(osc_has_categories()) { ?>
            <?php 
              $color = eps_get_cat_color(osc_category_id(), osc_category()); 
              $subcategories = pngm_current_subcategories(8);
              $category_url = osc_search_url(array('page' => 'search', 'sCategory' => osc_category_id()));
            ?>
     
            <div class="pngm-cat-item<?php echo (count($subcategories) > 0 ? ' has-sub' : ''); ?>">
              <a href="<?php echo $category_url; ?>" data-id="<?php echo osc_category_id(); ?>">
                <div>
                  <?php if(in_array(osc_category_id(), $new_categories)) { ?>
                    <span class="lab new"><?php _e('New', 'epsilon'); ?></span>
                  <?php } ?>
                  
                  <?php
                    // Prefer photographic covers (PNG), then SVG, then FA fallback.
                    $pngm_cat_img = function_exists('pngm_get_cat_image') ? pngm_get_cat_image(osc_category_id()) : '';
                    $pngm_is_svg = ($pngm_cat_img !== '' && stripos($pngm_cat_img, '.svg') !== false);
                    $pngm_is_photo = ($pngm_cat_img !== '' && !$pngm_is_svg);
                  ?>
                  <?php if ($pngm_is_photo) { ?>
                    <img src="<?php echo $pngm_cat_img; ?>" alt="<?php echo osc_esc_html(osc_category_name()); ?>" class="pngm-cat-cover<?php echo (eps_is_lazy() ? ' lazy' : ''); ?>"/>
                  <?php } elseif ($pngm_is_svg) { ?>
                    <img src="<?php echo $pngm_cat_img; ?>" alt="<?php echo osc_esc_html(osc_category_name()); ?>" class="pngm-cat-svg<?php echo (eps_is_lazy() ? ' lazy' : ''); ?>"/>
                  <?php } elseif (function_exists('pngm_render_category_icon')) { ?>
                    <?php echo pngm_render_category_icon(osc_category_id(), osc_category()); ?>
                  <?php } elseif (eps_param('cat_icons') == 1) { ?>
                    <?php
                      $icon = eps_get_cat_icon(osc_category_id(), osc_category(), true);
                      $icon_ = explode(' ', $icon);
                      $has_type = false;
                      if (in_array($icon_, array('fas', 'far', 'fab'))) {
                        $has_type = true;
                      }
                    ?>
                    <i class="<?php echo ($has_type ? '' : 'fas'); ?> <?php echo $icon; ?>" <?php if($color <> '') { ?>style="color:<?php echo $color; ?>;"<?php } ?>></i>
                  <?php } else { ?>
                    <img src="<?php echo eps_get_cat_image(osc_category_id()); ?>" alt="<?php echo osc_esc_html(osc_category_name()); ?>" class="<?php echo (eps_is_lazy() ? 'lazy' : ''); ?>"/>
                  <?php } ?>
                </div>

                <h3><span><?php echo osc_category_name(); ?></span></h3>
              </a>

              <?php if(count($subcategories) > 0) { ?>
                <button type="button" class="pngm-sub-toggle" aria-expanded="false" aria-label="<?php echo osc_esc_html(sprintf(__('Show subcategories of %s', 'epsilon'), osc_category_name())); ?>"><i class="fas fa-chevron-down"></i></button>

                <div class="pngm-subcat">
                  <ul>
                    <li class="pngm-subcat-all"><a href="<?php echo $category_url; ?>"><?php _e('View all', 'epsilon'); ?></a></li>
                    <?php foreach($subcategories as $subcategory) { ?>
                      <li><a href="<?php echo $subcategory['url']; ?>"><?php echo osc_esc_html($subcategory['name']); ?></a></li>
                    <?php } ?>
                  </ul>
                </div>
              <?php } ?>
            </div>
          <?php } ?>
        </div>
      </div>

      <div class="pngm-hero-art" aria-hidden="true">
        <img src="<?php echo osc_current_web_theme_url('images/home-hero.png'); ?>?v=<?php echo defined('PNGM_CHILD_VERSION') ? PNGM_CHILD_VERSION : '1'; ?>" alt="" width="1003" height="1350" decoding="async" />
      </div>
    </div>
  </section>

  <?php osc_run_hook('home_search_after'); ?>

  <?php if(eps_param('location_home') == 1 && $location_cookie['success'] === true) { ?>
    <?php
      $default_items = View::newInstance()->_get('items'); 
      View::newInstance()->_exportVariableToView('items', eps_location_items($location_cookie));
    ?>
    
    <section class="home-location">
      <div class="container">
        <div class="block">
          <?php
            $pngm_near_items = View::newInstance()->_get('items');
            $pngm_near_shown = is_array($pngm_near_items) ? count($pngm_near_items) : 0;
            $pngm_near_total = function_exists('pngm_location_items_total') ? pngm_location_items_total($location_cookie) : $pngm_near_shown;
            $pngm_near_city = function_exists('pngm_city_only') ? pngm_city_only($location_cookie) : osc_location_native_name_selector($location_cookie, 's_name');
            $pngm_near_see_all = ($pngm_near_total > $pngm_near_shown);
          ?>
          <div class="pngm-section-head">
            <div class="pngm-section-head-left">
              <h2 class="pngm-section-title">
                <span class="pngm-section-label"><?php _e('Near You', 'epsilon'); ?></span>
                <span class="pngm-near-meta">
                  <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                  <span><?php echo osc_esc_html($pngm_near_city); ?></span>
                  <a href="#" class="change-location pngm-change-link"><?php _e('Change', 'epsilon'); ?></a>
                </span>
              </h2>
            </div>
            <?php if ($pngm_near_see_all) { ?>
              <a class="pngm-see-all" href="<?php echo function_exists('pngm_location_search_url') ? pngm_location_search_url($location_cookie) : osc_search_url(array('page' => 'search')); ?>"><?php _e('See all', 'epsilon'); ?></a>
            <?php } ?>
          </div>

          <?php if(osc_count_items() > 0) { ?>
            <div class="nice-scroll-wrap">
              <div class="nice-scroll-prev"><i class="fas fa-caret-left"></i></div>
              
              <div id="location-items" class="products grid nice-scroll">
                <?php 
                  $c = 1; 
                  
                  while(osc_has_items()) {
                    eps_draw_item($c, false, 'pngm-card');
                    $c++;
                  }
                  
                  View::newInstance()->_erase('items');
                ?>
              </div>
              
              <div class="nice-scroll-next"><i class="fas fa-caret-right"></i></div>
            </div>
          <?php } else { ?>
            <div class="empty-alt pngm-empty-near">
              <strong><?php _e('No exact results found', 'epsilon'); ?></strong>
              <span><?php _e('Try another area, or browse the newest listings below.', 'epsilon'); ?></span>
              <a href="#" class="change-location btn btn-secondary mini"><?php _e('Change location', 'epsilon'); ?></a>
            </div>
          <?php } ?>
        </div>
      </div>
    </section>
    
    <?php View::newInstance()->_exportVariableToView('items', $default_items); ?>
  <?php } ?>
  

  <?php 
    $has_day_offer = 0;
    if(eps_param('enable_day_offer') == 1 && eps_param('day_offer_id') > 0) {
      $day_offer = Item::newInstance()->findByPrimaryKey(eps_param('day_offer_id'));
      
      if($day_offer !== false && isset($day_offer['pk_i_id'])) {
        $has_day_offer = 1;
      }
    }

    //osc_get_premiums(eps_param('premium_home_count') - $has_day_offer); 
    $premium_items = eps_premium_items(eps_param('premium_home_count') - $has_day_offer, @$day_offer['pk_i_id']);
  ?>

  <?php if(eps_param('premium_home') == 1 && count($premium_items) > 0) { ?>
    <?php
      $default_items = View::newInstance()->_get('items'); 
      View::newInstance()->_exportVariableToView('items', $premium_items);
    ?>
    <section class="home-premium">
      <div class="container">
        <div class="block">
          <h2><?php _e('Today\'s premium selection', 'epsilon'); ?></h2>

          <div class="nice-scroll-wrap">
            <div class="nice-scroll-prev"><i class="fas fa-caret-left"></i></div>
            
            <div id="premium-items" class="products grid nice-scroll no-visible-scroll">
              <?php 
                $c = 1; 

                if($has_day_offer == 1) {
                  View::newInstance()->_exportVariableToView('item', $day_offer);
                  eps_draw_item($c, false, 'pngm-card');
                  $c++;
                }
                
                while(osc_has_items()) {
                  eps_draw_item($c, false, 'pngm-card');
                  $c++;
                }
              ?>
            </div>
            
            <div class="nice-scroll-next"><i class="fas fa-caret-right"></i></div>
          </div>
        </div>
      </div>
    </section>
    
    <?php View::newInstance()->_exportVariableToView('items', $default_items); ?>
  <?php } ?>

  <?php osc_run_hook('home_premium'); ?>

  <?php if(function_exists('blg_param') && eps_param('blog_home') == 1) { ?>
    <?php $blogs = ModelBLG::newInstance()->getActiveBlogs(); ?>

    <?php if(is_array($blogs) && count($blogs) > 0) { ?>
      <?php $i = 1; ?>
      <?php $blog_limit = eps_param('blog_home_count'); ?>

      <section class="home-blog">
        <div class="container">
          <div class="block">
            <h2>
              <span><?php _e('News on blog', 'epsilon'); ?></span>
              <a href="<?php echo blg_home_link(); ?>" class="btn btn-secondary mini">
                <svg width="18" height="17" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M448 0H64C28.7 0 0 28.7 0 64v288c0 35.3 28.7 64 64 64h96v84c0 7.1 5.8 12 12 12 2.4 0 4.9-.7 7.1-2.4L304 416h144c35.3 0 64-28.7 64-64V64c0-35.3-28.7-64-64-64zm16 352c0 8.8-7.2 16-16 16H288l-12.8 9.6L208 428v-60H64c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16h384c8.8 0 16 7.2 16 16v288zM164.9 243.2l-4.8 42.8c-.6 5.7 4.2 10.6 10 10l42.8-4.8 85.5-85.5-48-48-85.5 85.5zm159.3-133.9c-7-7-18.4-7-25.4 0l-28.3 28.3 48 48 28.3-28.3c7-7 7-18.4 0-25.4l-22.6-22.6z"></path></svg>
                <?php _e('Explore blog', 'epsilon'); ?>
              </a>  
            </h2>

            <div class="blog-box <?php echo (eps_param('blog_home_design') <> 'grid' ? 'list' : 'grid'); ?>">
              <?php foreach($blogs as $b) { ?>
                <?php if($i <= $blog_limit) { ?>
                  <a href="<?php echo osc_route_url('blg-post', array('blogSlug' => osc_sanitizeString(blg_get_slug($b)), 'blogId' => $b['pk_i_id'])); ?>">
                    <img src="<?php echo blg_img_link($b['s_image']); ?>" alt="<?php echo osc_esc_html(strip_tags(blg_get_title($b))); ?>"/>

                    <div class="data">
                      <h3><?php echo strip_tags(blg_get_title($b)); ?></h3>
                      <div class="desc"><?php echo strip_tags(osc_highlight(blg_get_subtitle($b) <> '' ? blg_get_subtitle($b) : blg_get_description($b), 250)); ?></div>
                    </div>
                  </a>
                <?php } ?>

                <?php $i++; ?>
              <?php } ?>
            </div>
          </div>
        </div>
      </section>
    <?php } ?>
  <?php } ?>

  <?php $pngm_banner_middle = pngm_banner('home_middle'); if($pngm_banner_middle !== false) { ?>
    <div class="container banner-box<?php if(eps_is_demo()) { ?> is-demo<?php } ?>"><div class="inside"><?php echo $pngm_banner_middle; ?></div></div>
  <?php } ?>
  
  <?php if(function_exists('fi_most_favorited_items') && eps_param('favorite_home') == 1) { ?>
    <?php $favorite_items = eps_favorited_items(4); ?>
    
    <?php if(count($favorite_items) > 0) { ?>
      <?php
        $default_items = View::newInstance()->_get('items'); 
        View::newInstance()->_exportVariableToView('items', $favorite_items);
      ?>
      
      <section class="home-favorite">
        <div class="container">
          <div class="block">
            <h2>
              <span><?php _e('Most favorited listings', 'epsilon'); ?></span>
              
              <a href="<?php echo osc_route_url('favorite-lists'); ?>" class="btn btn-secondary mini">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="18" height="18"><path d="M528.1 171.5L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6zM388.6 312.3l23.7 138.4L288 385.4l-124.3 65.3 23.7-138.4-100.6-98 139-20.2 62.2-126 62.2 126 139 20.2-100.6 98z"/></svg>
                <?php _e('Manage your favorites', 'epsilon'); ?> (<?php echo eps_count_favorite(); ?>)
              </a>
            </h2>

            <div class="nice-scroll-wrap">
              <div class="nice-scroll-prev"><i class="fas fa-caret-left"></i></div>
              
              <div id="favorite-items" class="products grid nice-scroll no-visible-scroll">
                <?php 
                  $c = 1; 
                  
                  while(osc_has_items()) {
                    eps_draw_item($c, false, 'pngm-card');
                    $c++;
                  }
                ?>
              </div>
              
              <div class="nice-scroll-next"><i class="fas fa-caret-right"></i></div>
            </div>
          </div>
        </div>
      </section>
      
      <?php View::newInstance()->_exportVariableToView('items', $default_items); ?>
    <?php } ?>
  <?php } ?>
  

  <?php if(function_exists('bpr_companies_block') && eps_param('company_home') == 1) { ?>
    <?php $sellers = ModelBPR::newInstance()->getSellers(1, -1, -1, 10, '', '', '', 'NEW'); ?>
    
    <?php if(is_array($sellers) && count($sellers) > 0) { ?>
      <section class="home-business">
        <div class="container">
          <div class="block">
            <h2>
              <span><?php _e('Recommended companies', 'epsilon'); ?></span>

              <a href="<?php echo osc_route_url('bpr-list'); ?>" class="btn btn-secondary mini">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="18" height="18"><path d="M464 128h-80V80c0-26.51-21.49-48-48-48H176c-26.51 0-48 21.49-48 48v48H48c-26.51 0-48 21.49-48 48v256c0 26.51 21.49 48 48 48h416c26.51 0 48-21.49 48-48V176c0-26.51-21.49-48-48-48zM176 80h160v48H176V80zM54 176h404c3.31 0 6 2.69 6 6v74H48v-74c0-3.31 2.69-6 6-6zm404 256H54c-3.31 0-6-2.69-6-6V304h144v24c0 13.25 10.75 24 24 24h80c13.25 0 24-10.75 24-24v-24h144v122c0 3.31-2.69 6-6 6z"></path></svg>
                <?php _e('Explore companies', 'epsilon'); ?>
              </a>
            </h2>
            
            <div class="business-box">
              <?php echo bpr_companies_block(eps_param('company_home_count'), 'NEW'); ?>
            </div>
          </div>
        </div>
      </section>
    <?php } ?>
  <?php } ?>  
  
  
  

  <?php if(eps_param('users_home') == 1) { ?>
    <?php $users = eps_get_users('by_items', eps_param('users_home_count')); ?>
    <?php if(is_array($users) && count($users) > 0) { ?>
      <section class="home-users">
        <div class="container">
          <div id="users-list-block" class="block">
            <h2>
              <span><?php _e('Best sellers', 'epsilon'); ?></span>
            </h2>

            <div class="nice-scroll-wrap">
              <div class="nice-scroll-prev"><i class="fas fa-caret-left"></i></div>
              
              <div id="users-list" class="nice-scroll no-visible-scroll">
                <?php 
                  foreach($users as $user) {
                    ?>
                    <a href="<?php echo eps_user_public_profile_url($user['pk_i_id']); ?>" class="user">
                      <div class="img">
                        <img class="<?php echo (eps_is_lazy() ? 'lazy' : ''); ?>" src="<?php echo (eps_is_lazy() ? eps_get_load_image() : eps_profile_picture($user['pk_i_id'], 'small')); ?>" data-src="<?php echo eps_profile_picture($user['pk_i_id'], 'small'); ?>" alt="<?php echo osc_esc_html($user['s_name']); ?>" />

                        <?php if(eps_user_is_online($user['pk_i_id'])) { ?>
                          <div class="online" title="<?php echo osc_esc_html(__('User is online', 'epsilon')); ?>"></div>
                        <?php } else { ?>
                          <div class="online off" title="<?php echo osc_esc_html(__('User is offline', 'epsilon')); ?>"></div>
                        <?php } ?>
                      </div>

                      <?php if($user['b_company'] == 1) { ?>
                        <span class="business" title="<?php echo osc_esc_html(__('Professional seller', 'epsilon')); ?>"><?php _e('Pro', 'epsilon'); ?></span>
                      <?php } ?>
          
                      <strong class="name"><?php echo $user['s_name']; ?></strong>
                      <span class="items"><?php echo sprintf(__('%d items', 'epsilon'), $user['i_items']); ?></span>
                    </a>
                    <?php
                  }
                ?>
              </div>
              
              <div class="nice-scroll-next"><i class="fas fa-caret-right"></i></div>
            </div>
          </div>
        </div>
      </section>
    <?php } ?>
  <?php } ?>
  

  <?php View::newInstance()->_exportVariableToView('latestItems', eps_random_items()); ?>
  
  <?php if(osc_count_latest_items() > 0) { ?>
    <?php
      $pngm_latest_shown = osc_count_latest_items();
      $pngm_latest_total = function_exists('pngm_total_active_items') ? pngm_total_active_items() : $pngm_latest_shown;
      $pngm_latest_see_all = ($pngm_latest_total > $pngm_latest_shown);
    ?>
    <section class="home-latest">
      <div class="container">
        <div class="block">
          <div class="pngm-section-head">
            <div class="pngm-section-head-left">
              <h2 class="pngm-section-title"><?php _e('Latest Listings', 'epsilon'); ?></h2>
            </div>
            <?php if ($pngm_latest_see_all) { ?>
              <a class="pngm-see-all" href="<?php echo osc_search_url(array('page' => 'search')); ?>"><?php _e('See all', 'epsilon'); ?></a>
            <?php } ?>
          </div>

          <div id="latest-items" class="products grid">
            <?php 
              $c = 1; 
              
              while(osc_has_latest_items()) {
                eps_draw_item($c, false, 'pngm-card');
                $c++;
              }
            ?>
          </div>
        </div>
      </div>
    </section>
  <?php } ?>
  
  <?php osc_run_hook('home_latest'); ?>


  <?php if(eps_param('recent_home') == 1) { ?>
    <?php $recent_items = eps_recent_ads(eps_param('recent_design'), eps_param('recent_count'), 'onhome', true); ?>
    
    <?php if(is_array($recent_items) && count($recent_items) > 0) { ?>
      <?php
        $default_items = View::newInstance()->_get('items'); 
        View::newInstance()->_exportVariableToView('items', $recent_items);
      ?>
      
      <section class="home-recent">
        <div class="container">
          <div id="recent-ads" class="block onhome">
            <div class="pngm-section-head">
              <div class="pngm-section-head-left">
                <h2 class="pngm-section-title"><?php _e('Recently Viewed', 'epsilon'); ?></h2>
              </div>
            </div>

            <div class="nice-scroll-wrap">
              <div class="nice-scroll-prev"><i class="fas fa-caret-left"></i></div>
              
              <div id="recent-items" class="products grid nice-scroll no-visible-scroll">
                <?php 
                  $c = 1; 
                  
                  while(osc_has_items()) {
                    eps_draw_item($c, false, 'pngm-card');
                    $c++;
                  }
                ?>
              </div>
              
              <div class="nice-scroll-next"><i class="fas fa-caret-right"></i></div>
            </div>
          </div>
        </div>
      </section>
      
      <?php View::newInstance()->_exportVariableToView('items', $default_items); ?>
    <?php } ?>
  <?php } ?>



  <?php $pngm_banner_bottom = pngm_banner('home_bottom'); if($pngm_banner_bottom !== false) { ?>
    <div class="container banner-box<?php if(eps_is_demo()) { ?> is-demo<?php } ?>"><div class="inside"><?php echo $pngm_banner_bottom; ?></div></div>
  <?php } ?>
  
  <?php osc_run_hook('home_bottom'); ?>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>	