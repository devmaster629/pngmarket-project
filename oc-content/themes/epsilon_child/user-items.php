<?php Params::setParam('itemsPerPage', 12); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>
<body id="user-items" class="body-ua pngm-ua">
  <?php osc_current_web_theme_path('header.php') ; ?>

  <?php
    $param = (osc_version() >= 830 ? 'sItemType' : 'itemType');
    $current_type = (Params::getParam($param) <> '' ? Params::getParam($param) : 'all');

    // Define item types
    $types = array(
      'all' => __('All items', 'epsilon'),
      'active' => __('Active items', 'epsilon'),
      'pending_validate' => __('Pending items', 'epsilon'),
      'expired' => __('Expired items', 'epsilon')
    );

    // Page subtitles
    $titles = array(
      'all' => __('List of all your listings, including active, inactive and expired items', 'epsilon'),
      'active' => __('Listings those are active and visible on site', 'epsilon'),
      'pending_validate' => __('Listings pending approval. Unapproved listings are not visible on site.', 'epsilon'),
      'expired' => __('Listings those has expired and are now not visible in site', 'epsilon')
    );
  ?>
  
  <div class="container primary">
    <div id="user-menu" class="pngm-user-menu"><?php eps_user_menu(); ?></div>

    <div id="user-main">
      <?php osc_run_hook('user_items_top'); ?>
      
      <div class="pngm-ua-pagehead">
        <h1><?php echo $types[$current_type] ?? __('Items', 'epsilon'); ?></h1>
        <h2><?php echo $titles[$current_type] ?? ''; ?></h2>
        <a class="btn btn-primary pngm-ua-place" href="<?php echo osc_item_post_url(); ?>"><?php _e('Place an ad', 'epsilon'); ?></a>
      </div>

      <?php if(osc_version() >= 830) { ?>
        <form name="user-items-search" action="<?php echo osc_base_url(true); ?>" method="get" class="user-items-search-form nocsrf">
          <input type="hidden" name="page" value="user"/>
          <input type="hidden" name="action" value="items"/>

          <?php osc_run_hook('user_items_search_form_top'); ?>
          
          <div class="control-group">
            <label class="control-label" for="sItemType"><?php _e('Item type', 'epsilon'); ?></label>
            
            <div class="controls">
              <?php UserForm::search_item_type_select(); ?>
            </div>
          </div>
          
          <div class="control-group">
            <label class="control-label" for="sPattern"><?php _e('Keyword', 'epsilon'); ?></label>
            
            <div class="controls">
              <?php UserForm::search_pattern_text(); ?>
            </div>
          </div>
          
          <div class="control-group">
            <label class="control-label" for="sCategory"><?php _e('Category', 'epsilon'); ?></label>
            
            <div class="controls">
              <?php UserForm::search_category_select(); ?>
            </div>
          </div>

          
          <?php osc_run_hook('user_items_search_form_bottom'); ?>
          
          <div class="actions">
            <button type="submit" class="btn btn-primary"><?php _e('Apply', 'epsilon'); ?></button>
          </div>
        </form>
      <?php } ?>

      <div class="items-box <?php echo osc_esc_html($current_type); ?>">
        <?php if(osc_count_items() > 0) { ?>
          <?php while(osc_has_items()) { ?> 
            <?php $item_extra = eps_item_extra(osc_item_id()); ?>
          
            <div class="item<?php if(osc_item_is_inactive()) { ?> inactive<?php } ?><?php if(osc_item_is_expired()) { ?> inactive<?php } ?> <?php osc_run_hook('highlight_class'); ?>">
              <?php if(osc_images_enabled_at_items()) { ?>
                <a href="<?php echo osc_item_url(); ?>" class="image">
                  <?php if(osc_count_item_resources() > 0) { ?>
                    <img class="<?php echo (eps_is_lazy() ? 'lazy' : ''); ?>" <?php echo (eps_is_lazy_browser() ? 'loading="lazy"' : ''); ?> src="<?php echo (eps_is_lazy() ? eps_get_load_image() : osc_resource_thumbnail_url()); ?>" data-src="<?php echo osc_resource_thumbnail_url(); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" />
                  <?php } else { ?>
                    <img class="<?php echo (eps_is_lazy() ? 'lazy' : ''); ?>" <?php echo (eps_is_lazy_browser() ? 'loading="lazy"' : ''); ?> src="<?php echo (eps_is_lazy() ? eps_get_load_image() : eps_get_noimage()); ?>" data-src="<?php echo eps_get_noimage(); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" />
                  <?php } ?>
                  
                  <?php if(osc_item_is_premium()) { ?>
                    <div class="label-premium"><?php _e('Premium', 'epsilon'); ?></div>
                  <?php } ?>
                  
                  <?php if(osc_item_is_inactive()) { ?>
                    <div class="label-inactive"><?php _e('Pending validation', 'epsilon'); ?></div>
                  <?php } else if(osc_item_is_expired()) { ?>
                    <div class="label-expired"><?php _e('Expired listing', 'epsilon'); ?></div>
                  <?php } ?>
                  
                  <?php if(!in_array(osc_item_category_id(), eps_extra_fields_hide())) { ?>
                    <?php if(@$item_extra['i_sold'] == 1) { ?>
                      <div class="label-sold"><?php _e('Sold!', 'epsilon'); ?></div>
                    <?php } else if (@$item_extra['i_sold'] == 2) { ?>
                      <div class="label-reserved"><?php _e('Reserved!', 'epsilon'); ?></div>
                    <?php } ?>
                  <?php } ?>
                  
                  <div class="image-counter"><i class="fas fa-camera"></i> <?php echo osc_count_item_resources(); ?></div>
                </a>
              <?php } ?>
              
              <div class="body">
                <?php if(eps_check_category_price(osc_item_category_id())) { ?>
                  <div class="price"><?php echo osc_item_formated_price(); ?></div>
                <?php } ?>
                
                <div class="top">
                  <span><?php echo osc_format_date(osc_item_pub_date()); ?></span>
                  <span><?php echo osc_item_category(); ?></span>
                  <span><?php echo (osc_item_mod_date() <> '' ? osc_format_date(osc_item_mod_date()) : ''); ?></span>
                  <span><?php echo (eps_user_item_location() <> '' ? eps_user_item_location() : __('Location not set', 'epsilon')); ?></span>

                  <?php if(!in_array(osc_item_category_id(), eps_extra_fields_hide())) { ?>
                    <?php if(eps_get_simple_name($item_extra['i_condition'], 'condition', false) <> '') { ?>
                      <span><?php echo eps_get_simple_name($item_extra['i_condition'], 'condition', false); ?></span>
                    <?php } ?>

                    <?php if(eps_get_simple_name($item_extra['i_transaction'], 'transaction', false) <> '') { ?>
                      <span><?php echo eps_get_simple_name($item_extra['i_transaction'], 'transaction', false); ?></span>
                    <?php } ?>          
                  <?php } ?>
                </div>
                
                <a class="title" href="<?php echo osc_item_url(); ?>"><?php echo osc_item_title(); ?></a>

                <div class="description"><?php echo osc_highlight(osc_item_description(), 240); ?></div>
                
                <?php osc_run_hook('user_items_body', osc_item_id()); ?>
                
                <div class="buttons pngm-item-actions">
                  <?php if(osc_item_can_renew()) { ?>
                    <a class="renew" href="<?php echo osc_item_renew_url();?>" ><?php _e('Renew', 'epsilon'); ?></a>
                  <?php } ?>
          
                  <?php if(osc_item_is_active() && osc_can_deactivate_items()) {?>
                    <a class="deactivate" href="<?php echo osc_item_deactivate_url();?>" ><?php _e('Deactivate', 'epsilon'); ?></a>
                  <?php } ?>
                  
                  <?php if(osc_item_is_inactive()) { ?>
                    <?php if((function_exists('iv_add_item') && osc_get_preference('enable','plugin-item_validation') <> 1) || !function_exists('iv_add_item')) { ?>
                      <a class="activate" target="_blank" href="<?php echo osc_item_activate_url(); ?>"><?php _e('Validate', 'epsilon'); ?></a>
                    <?php } ?>
                  <?php } else { ?>
                    <?php if(!in_array(osc_item_category_id(), eps_extra_fields_hide())) { ?>
                      <a class="sold round2 tr1" href="<?php echo eps_item_sold_reserved_url('sold', $item_extra); ?>"><?php echo (@$item_extra['i_sold'] == 1 ? __('Unmark sold', 'epsilon') : __('Mark sold', 'epsilon')); ?></a>
                      <a class="reserved" href="<?php echo eps_item_sold_reserved_url('reserved', $item_extra); ?>"><?php echo (@$item_extra['i_sold'] == 2 ? __('Unmark reserved', 'epsilon') : __('Mark reserved', 'epsilon')); ?></a>
                    <?php } ?>                  
                  <?php } ?>
                  
                  <a class="edit" target="_blank" href="<?php echo osc_item_edit_url(); ?>" rel="nofollow"><?php _e('Edit', 'epsilon'); ?></a>

                  <?php if(function_exists('republish_link_raw') && republish_link_raw(osc_item_id())) { ?>
                    <a class="republish" href="<?php echo republish_link_raw(osc_item_id()); ?>" rel="nofollow"><?php _e('Republish', 'epsilon'); ?></a>
                  <?php } ?>

                  <a class="delete" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to delete this listing? This action cannot be undone.', 'epsilon')); ?>')" href="<?php echo osc_item_delete_url(); ?>"><i class="fas fa-trash"></i> <?php _e('Delete', 'epsilon'); ?></a>

                  <?php osc_run_hook('user_items_action', osc_item_id()); ?>
                </div>
              </div>
            </div>
          <?php } ?>

          <div class="paginate">
            <?php echo eps_fix_arrow(osc_pagination_items()); ?>
          </div>
        <?php } else { ?>
          <div class="empty"><?php _e('No listings found', 'epsilon'); ?></div>
        <?php } ?>
      </div>
      
      <?php osc_run_hook('user_items_bottom'); ?>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>