<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>

<body id="user-alerts" class="body-ua">
  <?php osc_current_web_theme_path('header.php') ; ?>

  <?php
    $alerts = array();

    while(osc_has_alerts()) { 
      $alert = View::newInstance()->_current('alerts');
      $alert_details = (array)json_decode($alert['s_search'], true);

      if(isset($alert['i_num_items'])) {
        $count_items = $alert['i_num_items'];
      } else {
        $new_search = new Search();
        $new_search->setJsonAlert($alert_details, $alert['s_email'], $alert['fk_i_user_id']);
        $count_items = $new_search->count();
      }

      $search_details = array('page' => 'search');
      $search_details['sPriceMin'] = @$alert_details['price_min'];
      $search_details['sPriceMax'] = @$alert_details['price_max'];
      $search_details['sCategory'] = @$alert_details['aCategories'];
      $search_details['sOrder'] = @$alert_details['order_column'];
      $search_details['sOrderType'] = @$alert_details['order_direction'];
      $search_details['sUser'] = @$alert_details['user_ids'];
      $search_details['withPicture'] = @$alert_details['withPicture'];
      $search_details['onlyPremium'] = @$alert_details['onlyPremium'];
      $search_details['sCityArea'] = @$alert_details['city_areas'];
      $search_details['sCity'] = @$alert_details['cities'];
      $search_details['sRegion'] = @$alert_details['regions'];
      $search_details['sCountry'] = @$alert_details['countries'];
      //$search_details['notFromUserId'] = osc_logged_user_id();
      $search_details['extraTables'] = eps_base64url_encode(json_encode($alert_details['no_catched_tables']));
      $search_details['extraConditions'] = eps_base64url_encode(json_encode($alert_details['no_catched_conditions']));

      
      if(is_array($search_details['sCountry']) && count($search_details['sCountry']) > 0) {
        $data_new = array();
        foreach($search_details['sCountry'] as $d) {
          $d = trim(str_replace(array('\'', '"'), '', str_replace('oc_t_item_location.s_country = ', '', $d)));
          $data_new[] = trim(str_replace(array('\'', '"'), '', str_replace('oc_t_item_location.fk_c_country_code = ', '', $d)));
        }
        
        $search_details['sCountry'] = $data_new;
      }
      
      if(is_array($search_details['sRegion']) && count($search_details['sRegion']) > 0) {
        $data_new = array();
        foreach($search_details['sRegion'] as $d) {
          $d = trim(str_replace(array('\'', '"'), '', str_replace('oc_t_item_location.s_region = ', '', $d)));
          $data_new[] = trim(str_replace(array('\'', '"'), '', str_replace('oc_t_item_location.fk_i_region_id = ', '', $d)));
        }
        
        $search_details['sRegion'] = $data_new;
      }
      
      if(is_array($search_details['sCity']) && count($search_details['sCity']) > 0) {
        $data_new = array();
        foreach($search_details['sCity'] as $d) {
          $d = trim(str_replace(array('\'', '"'), '', str_replace('oc_t_item_location.s_city = ', '', $d)));
          $data_new[] = trim(str_replace(array('\'', '"'), '', str_replace('oc_t_item_location.fk_i_city_id = ', '', $d)));
        }
        
        $search_details['sCity'] = $data_new;
      }
      
      if(is_array($search_details['sCityArea']) && count($search_details['sCityArea']) > 0) {
        $data_new = array();
        foreach($search_details['sCityArea'] as $d) {
          $data_new[] = trim(str_replace(array('\'', '"'), '', str_replace('oc_t_item_location.s_city_area = ', '', $d)));
        }
        
        $search_details['sCityArea'] = $data_new;
      }
      
      $search_details = array_filter($search_details);
      //print_r($search_details);
      
      $raw_data = osc_get_raw_search($alert_details);
      
     
      $alerts[$alert['pk_i_id']] = $alert;
      
      // CONNECTION & DB INFO
      $conn = DBConnectionClass::newInstance();
      $data = $conn->getOsclassDb();
      $comm = new DBCommandClass($data);
      $db_prefix = DB_TABLE_PREFIX;


      // COUNTRIES
      $c_filter = $alert_details['countries'];
      $c_filter = isset($c_filter[0]) ? $c_filter[0] : '';
      $c_filter = str_replace('item_location.fk_c_country_code', 'country.pk_c_code', $c_filter);

      if($c_filter != '') {
        $c_query = "SELECT * FROM {$db_prefix}t_country WHERE " . $c_filter;
        $c_result = $comm->query($c_query);

        if( !$c_result ) { 
          $c_prepare = array();
        } else {
          $c_prepare = $c_result->result();
        }
      }


      // REGIONS
      $r_filter = $alert_details['regions'];
      $r_filter = isset($r_filter[0]) ? $r_filter[0] : '';
      $r_filter = str_replace('item_location.fk_i_region_id', 'region.pk_i_id', $r_filter);

      if($r_filter != '') {
        $r_query = "SELECT * FROM {$db_prefix}t_region WHERE " . $r_filter;
        $r_result = $comm->query($r_query);

        if( !$r_result ) { 
          $r_prepare = array();
        } else {
          $r_prepare = $r_result->result();
        }
      }
      

      // CITIES
      $t_filter = $alert_details['cities'];
      $t_filter = isset($t_filter[0]) ? $t_filter[0] : '';
      $t_filter = str_replace('item_location.fk_i_city_id', 'city.pk_i_id', $t_filter);

      if($t_filter != '') {
        $t_query = "SELECT * FROM {$db_prefix}t_city WHERE " . $t_filter;
        $t_result = $comm->query($t_query);

        if( !$t_result ) { 
          $t_prepare = array();
        } else {
          $t_prepare = $t_result->result();
        }
      }


      // CATEGORIES
      if(isset($raw_data['aCategories']) && count($raw_data['aCategories']) > 0) {
        $cat_name = implode(', ', array_filter($raw_data['aCategories']));
      } else {
        $cat_list = $alert_details['aCategories'];
        $cat_list = implode(', ', $cat_list);
        $locale = osc_current_user_locale();

        if($cat_list != '') {
          $cat_query = "SELECT * FROM {$db_prefix}t_category_description WHERE fk_i_category_id IN (" . $cat_list . ") AND fk_c_locale_code = '" . $locale . "'";
          $cat_result = $comm->query($cat_query);

          if(!$cat_result) { 
            $cat_name = '';
          } else {
            $cat_prepare = $cat_result->result();
            $cat_name = implode(', ', array_column($cat_prepare, 's_name'));
          }
        } else {
          $cat_name = '';
        }
      }
      
      $country_name = @$c_prepare[0]['s_name'];
      $region_name = @$r_prepare[0]['s_name'];
      $city_name = @$t_prepare[0]['s_name'];
      $city_area_name = implode(', ', array_filter($alert_details['city_areas']));
      
      if(trim($cat_name) <> '') {
        $cat = '<span class="alert-title" title="' . osc_esc_html($cat_name) . '">' . osc_highlight($cat_name, 100) . '</span>';
      } else {
        $cat = '';
      }
      
      $loc = implode(', ', array_filter(array($country_name, $region_name, $city_name, $city_area_name)));
      
      if(trim($loc) != '') {
        $loc = '<span class="alert-title" title="' . osc_esc_html($loc) . '">' . osc_highlight($loc, 100) . '</span>';
      } else {
        $loc = '';
      }
      
      $name = sprintf(__('%s items in %s', 'epsilon'), ($cat <> '' ? $cat : __('All categories', 'epsilon')), ($loc <> '' ? $loc : __('all locations', 'epsilon')));

      $alerts[$alert['pk_i_id']]['name'] = $name;
      $alerts[$alert['pk_i_id']]['count'] = ($count_items >= 100 ? '100+' : $count_items);
      $alerts[$alert['pk_i_id']]['unsubscribe_url'] = osc_user_unsubscribe_alert_url();
      $alerts[$alert['pk_i_id']]['search_url'] = osc_search_url($search_details);
    }
  ?>

  <div class="container primary">
    <div id="user-menu"><?php eps_user_menu(); ?></div>

    <div id="user-main">
      <h1><?php _e('Subscriptions', 'epsilon'); ?></h1>
      <h2><?php _e('Receive email notification when new item, that match your search criteria, is published.', 'epsilon'); ?></h2>
      
      <div class="alerts-box">
        <?php if(is_array($alerts) && count($alerts) > 0) { ?>
          <?php foreach($alerts as $a) { ?>
            <?php View::newInstance()->_exportVariableToView('items', isset($a['items']) ? $a['items'] : array()); ?>
            
            <div class="alert">
              <div class="head-row">
                <div class="data">
                  <strong>
                    <?php 
                      if(function_exists('osc_alert_name') && isset($a['s_name']) && $a['s_name'] != '') {
                        echo $a['s_name'];
                      } else {
                        echo $a['name'];
                      }
                    ?>
                  </strong>
                  
                  <div>
                    <span><?php echo sprintf(__('%s listings match subscription conditions, %s notifications, created on %s, id: #%s. Your listings are excluded.', 'epsilon'), $a['count'], eps_alert_frequency($a['e_type']), date('j. M Y', strtotime($a['dt_date'])), $a['pk_i_id']); ?></span>
                    <a href="#" class="show-technical-details"><?php _e('Technical details', 'epsilon'); ?></a>  
                  </div>
                  
                  <?php
                    if(function_exists('osc_alert_change_frequency')) {
                      echo osc_alert_change_frequency($a);
                    }
                  ?>
                </div>
                
                <a href="<?php echo $a['unsubscribe_url']; ?>" class="btn btn-secondary mini" onclick="javascript:return confirm('<?php echo osc_esc_js(__('This action can\'t be undone. Are you sure you want to continue?', 'epsilon')); ?>');"><?php _e('Unsubscribe', 'epsilon'); ?></a>
              </div>

              <div class="details"><?php _e('Technical details', 'epsilon'); ?>:<br/><?php echo $a['s_search']; ?></div>
              
              <div class="items">
                <a href="<?php echo $a['search_url']; ?>" class="show-all"><?php _e('View all listings in search', 'epsilon'); ?> &#8594;</a>

                <?php if(osc_count_items() <= 0) { ?>
                  <div class="empty"><?php _e('No items match alert conditions. You will receive email notification once there is new listing published matching alert conditions.', 'epsilon'); ?></div>
                <?php } else { ?>
                  <div id="alert-items" class="products grid nice-scroll">
                    <?php
                      $c = 1; 
                      
                      while(osc_has_items()) {
                        eps_draw_item($c, false, 'tall');
                        $c++;
                      }
                    ?>
                  </div>
                <?php } ?>
              </div>
            </div>

          <?php } ?>
        <?php } else { ?>
          <div class="empty"><?php _e('No alerts has been created yet.', 'epsilon'); ?></div>
        <?php } ?>
      </div>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>