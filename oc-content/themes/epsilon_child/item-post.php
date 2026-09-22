<?php
/**
 * Child theme item-post / item-edit — 6-step PNG Market wizard.
 * Keeps Osclass field names so item_add_post / item_edit_post still work.
 */
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
  <?php if (osc_images_enabled_at_items()) { ItemForm::photos_javascript(); } ?>
</head>
<?php
  if (!function_exists('pngm_post_wizard_steps')) {
    require_once dirname(__FILE__) . '/includes/post_wizard.php';
  }

  $action = 'item_add_post';
  $edit = false;
  if (Params::getParam('action') == 'item_edit') {
    $action = 'item_edit_post';
    $edit = true;
  }

  $user = array();
  if (osc_is_web_user_logged_in()) {
    $user = osc_user();
  }

  if (!$edit) {
    $loc_cook = eps_location_from_cookies();
    $prepare = array();
    $prepare['s_contact_name'] = osc_user_name();
    $prepare['s_contact_email'] = osc_user_email();
    $prepare['s_zip'] = osc_user_zip();
    $prepare['s_city_area'] = osc_user_city_area();
    $prepare['s_address'] = osc_user_address();
    $prepare['fk_c_country_code'] = eps_get_session('countryId') <> '' ? eps_get_session('countryId') : (@$loc_cook['fk_c_country_code'] <> '' ? @$loc_cook['fk_c_country_code'] : @$user['fk_c_country_code']);
    $prepare['fk_i_region_id'] = eps_get_session('regionId') <> '' ? eps_get_session('regionId') : (@$loc_cook['fk_i_region_id'] > 0 ? @$loc_cook['fk_i_region_id'] : @$user['fk_i_region_id']);
    $prepare['fk_i_city_id'] = eps_get_session('cityId') <> '' ? eps_get_session('cityId') : (@$loc_cook['fk_i_city_id'] > 0 ? @$loc_cook['fk_i_city_id'] : @$user['fk_i_city_id']);
    $prepare['s_country'] = eps_get_session('sCountry') <> '' ? eps_get_session('sCountry') : osc_user_field('s_country');
    $prepare['s_region'] = eps_get_session('sRegion') <> '' ? eps_get_session('sRegion') : osc_user_region();
    $prepare['s_city'] = eps_get_session('sCity') <> '' ? eps_get_session('sCity') : osc_user_city();
    $prepare['s_phone'] = eps_get_session('sPhone') <> '' ? eps_get_session('sPhone') : osc_user_phone();
    $prepare['s_contact_phone'] = $prepare['s_phone'];
    $prepare['i_category'] = eps_get_session('catId') <> '' ? eps_get_session('catId') : Params::getParam('catId');
  } else {
    $item_extra = eps_item_extra(osc_item_id());
    $prepare = osc_item();
    $prepare['fk_c_country_code'] = eps_get_session('countryId') <> '' ? eps_get_session('countryId') : osc_item_country_code();
    $prepare['fk_i_region_id'] = eps_get_session('regionId') <> '' ? eps_get_session('regionId') : osc_item_region_id();
    $prepare['fk_i_city_id'] = eps_get_session('cityId') <> '' ? eps_get_session('cityId') : osc_item_city_id();
    $prepare['s_country'] = eps_get_session('sCountry') <> '' ? eps_get_session('sCountry') : osc_item_country();
    $prepare['s_region'] = eps_get_session('sRegion') <> '' ? eps_get_session('sRegion') : osc_item_region();
    $prepare['s_city'] = eps_get_session('sCity') <> '' ? eps_get_session('sCity') : osc_item_city();
    $prepare['s_phone'] = eps_get_session('sPhone') <> '' ? eps_get_session('sPhone') : @$item_extra['s_phone'];
    $prepare['i_category'] = eps_get_session('catId') <> '' ? eps_get_session('catId') : osc_item_category_id();
    $prepare['s_zip'] = eps_get_session('sZip') <> '' ? eps_get_session('sZip') : osc_item_zip();
    $prepare['s_address'] = eps_get_session('sAddress') <> '' ? eps_get_session('sAddress') : osc_item_address();
  }

  $prepare['fk_i_category_id'] = $prepare['i_category'];
  $cat_path = pngm_post_category_path((int) $prepare['i_category']);
  $required_fields = strtolower(eps_param('post_required'));
  $location_type = eps_param('publish_location');
  $price_type = '';
  if ($edit) {
    if (osc_item_price() === null) {
      $price_type = 'CHECK';
    } elseif (osc_item_price() == 0) {
      $price_type = 'FREE';
    } else {
      $price_type = 'PAID';
    }
  }

  if ($location_type == 0) {
    eps_location_javascript();
  }

  $root_cats = pngm_post_root_categories();
  $subcat_map = array();
  foreach ($root_cats as $rc) {
    $rid = (int) $rc['pk_i_id'];
    $subcat_map[$rid] = array();
    foreach (pngm_post_subcategories($rid) as $sc) {
      $subcat_map[$rid][] = array(
        'id'   => (int) $sc['pk_i_id'],
        'name' => isset($sc['s_name']) ? $sc['s_name'] : '',
      );
    }
  }

  $steps = pngm_post_wizard_steps();
  $tx_pills = pngm_post_transaction_pills();
  $tx_current = '';
  if ($edit && !empty($item_extra['i_transaction'])) {
    $tx_current = (string) $item_extra['i_transaction'];
  } elseif (eps_get_session('sTransaction') <> '') {
    $tx_current = (string) eps_get_session('sTransaction');
  }
  if ($price_type === 'FREE') {
    $tx_current = '0';
  }

  $locale = osc_current_user_locale();
  $max_imgs = (int) osc_max_images_per_item();
?>
<body id="body-item-post" class="item-publish pngm-post-wizard">
  <?php osc_current_web_theme_path('header.php'); ?>

  <div class="pngm-post-wrap">
    <div class="pngm-post-top">
      <h1 class="pngm-post-page-title"><?php echo (!$edit ? __('Post an item', 'epsilon') : __('Edit your listing', 'epsilon')); ?></h1>
      <?php pngm_post_render_stepper(1); ?>
    </div>

    <ul id="error_list" class="new-item pngm-post-errors"></ul>

    <form name="item" class="pngm-post-form" action="<?php echo osc_base_url(true); ?>" method="post" enctype="multipart/form-data" data-pngm-wizard="1">
      <input type="hidden" name="action" value="<?php echo osc_esc_html($action); ?>" />
      <input type="hidden" name="page" value="item" />
      <?php if ($edit) { ?><input type="hidden" name="id" value="<?php echo osc_item_id(); ?>" /><?php } ?>
      <?php if ($edit) { ?><input type="hidden" name="secret" value="<?php echo osc_item_secret(); ?>" /><?php } ?>
      <input type="hidden" name="catId" id="catId" value="<?php echo (int) $prepare['i_category']; ?>" />
      <input type="hidden" name="pngm_root_cat" id="pngm_root_cat" value="<?php echo (int) $cat_path['root']; ?>" />

      <?php osc_run_hook('item_publish_top'); ?>

      <!-- ========== STEP 1: CATEGORY ========== -->
      <section class="pngm-post-step-panel is-active" data-step="1" id="pngm-step-category">
        <div class="pngm-post-step-head">
          <h2><?php echo osc_esc_html($steps[1]['title']); ?></h2>
          <p><?php echo osc_esc_html($steps[1]['sub']); ?></p>
        </div>

        <div class="pngm-post-card">
          <h3 class="pngm-post-card-title"><?php _e('Select Category', 'epsilon'); ?></h3>
          <div class="pngm-post-cat-grid" role="listbox" aria-label="<?php echo osc_esc_html(__('Categories', 'epsilon')); ?>">
            <?php foreach ($root_cats as $rc) {
              $rid = (int) $rc['pk_i_id'];
              $rname = isset($rc['s_name']) ? $rc['s_name'] : '';
              $active = ($cat_path['root'] === $rid) ? ' is-selected' : '';
            ?>
              <button type="button" class="pngm-post-cat-card<?php echo $active; ?>" data-root-id="<?php echo $rid; ?>" data-root-name="<?php echo osc_esc_html($rname); ?>" role="option" aria-selected="<?php echo $active ? 'true' : 'false'; ?>">
                <span class="pngm-post-cat-ico">
                  <?php if (function_exists('pngm_render_category_visual')) {
                    echo pngm_render_category_visual($rid, $rc, 0);
                  } elseif (function_exists('pngm_render_category_icon')) {
                    echo pngm_render_category_icon($rid, $rc, true);
                  } ?>
                </span>
                <span class="pngm-post-cat-name"><?php echo osc_esc_html($rname); ?></span>
              </button>
            <?php } ?>
          </div>
          <p class="pngm-post-field-error is-hidden" data-for="category" hidden><?php _e('Please select a category.', 'epsilon'); ?></p>

          <div class="pngm-post-field pngm-post-subcat-wrap<?php echo $cat_path['root'] ? '' : ' is-hidden'; ?>">
            <label for="pngm_subcategory"><?php _e('Subcategory', 'epsilon'); ?> <span class="req">*</span></label>
            <select id="pngm_subcategory" class="pngm-post-select">
              <option value=""><?php _e('Select a subcategory', 'epsilon'); ?></option>
            </select>
            <p class="pngm-post-field-error is-hidden" data-for="catId" hidden><?php _e('Please select a subcategory.', 'epsilon'); ?></p>
          </div>
        </div>
        <?php osc_run_hook('item_publish_category'); ?>
      </section>

      <!-- ========== STEP 2: DETAILS ========== -->
      <section class="pngm-post-step-panel" data-step="2" id="pngm-step-details" hidden>
        <div class="pngm-post-step-head">
          <h2><?php echo osc_esc_html($steps[2]['title']); ?></h2>
          <p><?php echo osc_esc_html($steps[2]['sub']); ?></p>
        </div>
        <div class="pngm-post-layout">
          <div class="pngm-post-main">
            <div class="pngm-post-card">
              <h3 class="pngm-post-card-title"><?php _e('Item Details', 'epsilon'); ?></h3>

              <div class="pngm-post-field">
                <label for="title[<?php echo $locale; ?>]"><?php _e('Title', 'epsilon'); ?> <span class="req">*</span></label>
                <div class="input-box">
                  <?php ItemForm::title_input('title', $locale, osc_esc_html(eps_post_item_title())); ?>
                </div>
                <p class="pngm-post-field-error is-hidden" data-for="title" hidden><?php _e('Please enter a title.', 'epsilon'); ?></p>
                <div class="pngm-post-counter" data-counter-for="title"><span>0</span>/100</div>
              </div>

              <div class="pngm-post-field">
                <label for="description[<?php echo $locale; ?>]"><?php _e('Description', 'epsilon'); ?> <span class="req">*</span></label>
                <div class="input-box">
                  <?php ItemForm::description_textarea('description', $locale, osc_esc_html(eps_post_item_description())); ?>
                </div>
                <p class="pngm-post-field-error is-hidden" data-for="description" hidden><?php _e('Please enter a description.', 'epsilon'); ?></p>
                <div class="pngm-post-counter" data-counter-for="description"><span>0</span>/5000</div>
              </div>

              <?php if (osc_price_enabled_at_items()) { ?>
              <div class="pngm-post-field pngm-post-price-field">
                <div class="pngm-post-label"><?php _e('Price', 'epsilon'); ?> <span class="req">*</span></div>
                <div class="pngm-post-price-mode" role="radiogroup" aria-label="<?php echo osc_esc_html(__('Price', 'epsilon')); ?>">
                  <label class="pngm-post-price-option">
                    <input type="radio" name="pngm_price_mode" value="PAID" <?php echo ($price_type === '' || $price_type === 'PAID') ? 'checked' : ''; ?> />
                    <span class="pngm-post-price-option-copy">
                      <span class="pngm-post-price-option-text"><?php _e('Set a price', 'epsilon'); ?></span>
                    </span>
                  </label>
                  <label class="pngm-post-price-option">
                    <input type="radio" name="pngm_price_mode" value="CHECK" <?php echo ($price_type === 'CHECK') ? 'checked' : ''; ?> />
                    <span class="pngm-post-price-option-copy">
                      <span class="pngm-post-price-option-text"><?php _e('Check with seller', 'epsilon'); ?></span>
                      <span class="pngm-post-price-option-hint"><?php _e('Buyers will contact you for the price.', 'epsilon'); ?></span>
                    </span>
                  </label>
                </div>
                <div class="pngm-post-price-enter" id="pngm-price-enter"<?php echo ($price_type === 'CHECK' || $price_type === 'FREE') ? ' hidden' : ''; ?>>
                  <div class="pngm-post-price-box">
                    <?php echo eps_simple_currency(); ?>
                    <?php ItemForm::price_input_text(); ?>
                  </div>
                </div>
                <p class="pngm-post-field-error is-hidden" data-for="price" hidden><?php _e('Please enter a price, or choose Check with seller.', 'epsilon'); ?></p>
              </div>
              <?php } ?>

              <div class="pngm-post-field status-wrap">
                <label for="sCondition"><?php _e('Condition', 'epsilon'); ?></label>
                <?php echo eps_simple_condition(true); ?>
              </div>

              <div class="pngm-post-field">
                <span class="pngm-post-label"><?php _e('Transaction Type', 'epsilon'); ?></span>
                <div class="pngm-post-pills" data-pills="transaction">
                  <?php foreach ($tx_pills as $tid => $tlabel) {
                    $sel = ((string) $tx_current === (string) $tid) ? ' is-selected' : '';
                  ?>
                    <button type="button" class="pngm-post-pill<?php echo $sel; ?>" data-value="<?php echo osc_esc_html($tid); ?>"><?php echo osc_esc_html($tlabel); ?></button>
                  <?php } ?>
                </div>
                <div class="pngm-post-tx-native is-sr-only"><?php echo eps_simple_transaction(true); ?></div>
              </div>

              <?php osc_run_hook('item_publish_description'); ?>
              <div id="post-hooks" class="hooks-block pngm-post-attrs"><?php if ($edit) { ItemForm::plugin_edit_item(); } else { ItemForm::plugin_post_item(); } ?></div>
              <p class="pngm-post-muted pngm-post-make-hint"><?php _e('Tip: choose “Other” under Make / Brand to type a custom make or model.', 'epsilon'); ?></p>
              <?php osc_run_hook('item_publish_hook'); ?>
              <?php osc_run_hook('item_publish_price'); ?>
            </div>
          </div>

          <aside class="pngm-post-side">
            <div class="pngm-post-card pngm-post-summary" data-summary="details">
              <h3><?php _e('Summary', 'epsilon'); ?></h3>
              <dl>
                <div><dt><?php _e('Category', 'epsilon'); ?></dt><dd data-sum="root"><?php echo osc_esc_html($cat_path['root_name'] ?: '—'); ?></dd></div>
                <div><dt><?php _e('Subcategory', 'epsilon'); ?></dt><dd data-sum="leaf"><?php echo osc_esc_html($cat_path['leaf_name'] ?: '—'); ?></dd></div>
              </dl>
            </div>
            <?php pngm_post_tip_card(__('Tips for better listings', 'epsilon'), array(
              __('Use a clear title with brand and model.', 'epsilon'),
              __('Describe condition and what’s included.', 'epsilon'),
              __('Honest details build more trust.', 'epsilon'),
            )); ?>
          </aside>
        </div>
      </section>

      <!-- ========== STEP 3: PHOTOS ========== -->
      <section class="pngm-post-step-panel" data-step="3" id="pngm-step-photos" hidden>
        <div class="pngm-post-step-head">
          <h2><?php echo osc_esc_html($steps[3]['title']); ?></h2>
          <p><?php echo osc_esc_html($steps[3]['sub']); ?></p>
        </div>
        <div class="pngm-post-layout">
          <div class="pngm-post-main">
            <div class="pngm-post-card upload-photos pngm-post-upload-card">
              <h3 class="pngm-post-card-title"><?php _e('Add Photos', 'epsilon'); ?> <span class="req">*</span></h3>
              <p class="pngm-post-muted pngm-post-card-intro"><?php _e('Upload clear photos of your item. First photo is the cover. At least one photo is required.', 'epsilon'); ?></p>
              <div class="box photos photoshow drag_drop in" id="photos">
                <?php
                  if (osc_images_enabled_at_items()) {
                    if (eps_ajax_image_upload()) {
                      ItemForm::ajax_photos();
                    }
                  }
                ?>
              </div>
              <div class="pngm-post-field-error" data-for="photos" hidden></div>
              <p class="pngm-post-upload-meta"><?php echo sprintf(__('Up to %d photos · JPG/PNG · max 10MB each', 'epsilon'), $max_imgs); ?></p>
              <?php osc_run_hook('item_publish_images'); ?>
            </div>
          </div>
          <aside class="pngm-post-side">
            <?php pngm_post_tip_card(__('Photo tips', 'epsilon'), array(
              __('First photo is your cover image.', 'epsilon'),
              __('Use bright, clear photos from multiple angles.', 'epsilon'),
              __('Show any damage honestly.', 'epsilon'),
            )); ?>
            <div class="pngm-post-card">
              <h3><?php _e('You can reorder photos', 'epsilon'); ?></h3>
              <p class="pngm-post-muted"><?php _e('Drag thumbnails in the upload list to change order.', 'epsilon'); ?></p>
            </div>
          </aside>
        </div>
      </section>

      <!-- ========== STEP 4: LOCATION ========== -->
      <section class="pngm-post-step-panel" data-step="4" id="pngm-step-location" hidden>
        <div class="pngm-post-step-head">
          <h2><?php echo osc_esc_html($steps[4]['title']); ?></h2>
          <p><?php echo osc_esc_html($steps[4]['sub']); ?></p>
        </div>
        <div class="pngm-post-layout">
          <div class="pngm-post-main">
            <div class="pngm-post-card location">
              <h3 class="pngm-post-card-title"><?php _e('Set Location', 'epsilon'); ?></h3>
              <p class="pngm-post-muted pngm-post-card-intro"><?php _e('Add the location where your item is available.', 'epsilon'); ?></p>
              <?php if ($location_type == 0) {
                $countries = Country::newInstance()->listAll();
                if (is_array($countries) && count($countries) > 1) {
                  $regions = array();
                  if ($prepare['fk_c_country_code'] <> '') {
                    $regions = Region::newInstance()->findByCountry($prepare['fk_c_country_code']);
                  }
              ?>
                <div class="pngm-post-field row country">
                  <label for="countryId"><?php _e('Country', 'epsilon'); ?></label>
                  <div class="input-box"><?php ItemForm::country_select($countries, $prepare); ?></div>
                </div>
                <div class="pngm-post-field row region">
                  <label for="regionId"><?php _e('Province / Region', 'epsilon'); ?></label>
                  <div class="input-box"><?php ItemForm::region_select($regions, $prepare); ?></div>
                </div>
              <?php } else {
                  $country_code = $countries[0]['pk_c_code'];
                  $regions = Region::newInstance()->listAll();
              ?>
                <input type="hidden" id="countryId" name="countryId" value="<?php echo osc_esc_html($country_code); ?>"/>
                <div class="pngm-post-field row region">
                  <label for="regionId"><?php _e('Province / Region', 'epsilon'); ?></label>
                  <div class="input-box"><?php ItemForm::region_select($regions, $prepare); ?></div>
                </div>
              <?php }
                $cities = array();
                if ($prepare['fk_i_region_id'] > 0) {
                  $cities = City::newInstance()->findByRegion($prepare['fk_i_region_id']);
                }
              ?>
                <div class="pngm-post-field row city">
                  <label for="cityId"><?php _e('City / Town', 'epsilon'); ?></label>
                  <div class="input-box"><?php ItemForm::city_select($cities, $prepare); ?></div>
                </div>
              <?php } else { ?>
                <input type="hidden" name="countryId" id="sCountry" value="<?php echo osc_esc_html($prepare['fk_c_country_code']); ?>"/>
                <input type="hidden" name="regionId" id="sRegion" value="<?php echo osc_esc_html($prepare['fk_i_region_id']); ?>"/>
                <input type="hidden" name="cityId" id="sCity" value="<?php echo osc_esc_html($prepare['fk_i_city_id']); ?>"/>
                <div class="pngm-post-field row">
                  <label for="sLocation"><?php _e('Location', 'epsilon'); ?></label>
                  <div class="input-box picker location only-search is-publish">
                    <input name="sLocation" type="text" class="location-pick" id="sLocation" placeholder="<?php echo osc_esc_html(__('Start typing region, city...', 'epsilon')); ?>" value="" autocomplete="off"/>
                    <i class="clean fas fa-times-circle"></i>
                    <div class="results"></div>
                  </div>
                </div>
              <?php } ?>

              <div class="pngm-post-field row address">
                <label for="address"><?php _e('Detailed Location', 'epsilon'); ?></label>
                <div class="input-box"><?php ItemForm::address_text($prepare); ?></div>
              </div>

              <div class="pngm-post-map-block">
                <div class="pngm-post-label"><?php _e('Map', 'epsilon'); ?> <span class="pngm-post-optional">(<?php _e('optional', 'epsilon'); ?>)</span></div>
                <div class="pngm-post-map" id="pngm-post-map" aria-label="<?php echo osc_esc_html(__('Map preview', 'epsilon')); ?>">
                  <iframe id="pngm-post-map-frame" class="pngm-post-map-frame" title="<?php echo osc_esc_html(__('Map preview', 'epsilon')); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen hidden></iframe>
                  <div class="pngm-post-map-placeholder" id="pngm-post-map-placeholder">
                    <div class="pngm-post-map-pin"><i class="fas fa-map-marker-alt"></i></div>
                    <p><?php _e('Map preview updates from your selected city.', 'epsilon'); ?></p>
                  </div>
                </div>
                <button type="button" class="pngm-post-geo-btn" id="pngm-use-location">
                  <i class="fas fa-crosshairs" aria-hidden="true"></i>
                  <?php _e('Use current location', 'epsilon'); ?>
                </button>
              </div>

              <div class="pngm-post-field row zip is-optional is-sr-only" aria-hidden="true">
                <label for="zip"><?php _e('ZIP', 'epsilon'); ?></label>
                <div class="input-box"><?php ItemForm::zip_text($prepare); ?></div>
              </div>
              <?php osc_run_hook('item_publish_location'); ?>
            </div>
          </div>
          <aside class="pngm-post-side">
            <div class="pngm-post-card pngm-post-summary">
              <h3><?php _e('Summary', 'epsilon'); ?></h3>
              <dl>
                <div><dt><?php _e('Category', 'epsilon'); ?></dt><dd data-sum="root">—</dd></div>
                <div><dt><?php _e('Subcategory', 'epsilon'); ?></dt><dd data-sum="leaf">—</dd></div>
              </dl>
            </div>
            <?php pngm_post_tip_card(__('Why location matters', 'epsilon'), array(
              __('Local buyers find nearby listings first.', 'epsilon'),
              __('Accurate city improves search ranking.', 'epsilon'),
              __('You can keep your exact street private.', 'epsilon'),
            )); ?>
          </aside>
        </div>
      </section>

      <!-- ========== STEP 5: CONTACT ========== -->
      <section class="pngm-post-step-panel" data-step="5" id="pngm-step-contact" hidden>
        <div class="pngm-post-step-head">
          <h2><?php echo osc_esc_html($steps[5]['title']); ?></h2>
          <p><?php echo osc_esc_html($steps[5]['sub']); ?></p>
        </div>
        <div class="pngm-post-layout">
          <div class="pngm-post-main">
            <div class="pngm-post-card about pngm-post-contact-card">
              <h3 class="pngm-post-card-title"><?php _e('Contact Information', 'epsilon'); ?></h3>
              <p class="pngm-post-muted pngm-post-card-intro"><?php _e('Add your contact details so buyers can reach you.', 'epsilon'); ?></p>

              <div class="pngm-post-field row name">
                <label for="contactName"><?php _e('Full Name', 'epsilon'); ?><?php if (strpos($required_fields, 'name') !== false) { ?> <span class="req">*</span><?php } ?></label>
                <div class="input-box"><?php ItemForm::contact_name_text($prepare); ?></div>
                <p class="pngm-post-field-error is-hidden" data-for="contactName" hidden><?php _e('Please enter your full name.', 'epsilon'); ?></p>
              </div>

              <div class="pngm-post-field row phone">
                <label for="contactPhone"><?php _e('Phone Number', 'epsilon'); ?><?php if (strpos($required_fields, 'phone') !== false) { ?> <span class="req">*</span><?php } ?></label>
                <div class="pngm-post-phone-row">
                  <span class="pngm-post-phone-prefix" aria-hidden="true">+675</span>
                  <div class="pngm-post-phone-input">
                    <?php if (method_exists('ItemForm', 'contact_phone_text')) {
                      ItemForm::contact_phone_text($prepare);
                    } else { ?>
                      <input type="tel" id="sPhone" name="sPhone" value="<?php echo osc_esc_html($prepare['s_phone']); ?>" />
                    <?php } ?>
                  </div>
                </div>
                <p class="pngm-post-field-error is-hidden" data-for="phone" hidden><?php _e('Please enter a valid phone number.', 'epsilon'); ?></p>
                <?php if (method_exists('ItemForm', 'show_phone_checkbox')) { ?>
                  <label class="pngm-post-check is-sr-only" aria-hidden="true">
                    <?php ItemForm::show_phone_checkbox(); ?>
                    <span><?php _e('Show phone on listing', 'epsilon'); ?></span>
                  </label>
                <?php } ?>
              </div>

              <div class="pngm-post-field">
                <label for="pngm_call_availability"><?php _e('Call Availability', 'epsilon'); ?> <span class="req">*</span></label>
                <div class="input-box">
                  <select name="pngm_call_availability" id="pngm_call_availability" class="pngm-post-select">
                    <option value=""><?php _e('Select call availability', 'epsilon'); ?></option>
                    <option value="anytime"><?php _e('Anytime', 'epsilon'); ?></option>
                    <option value="weekdays"><?php _e('Weekdays', 'epsilon'); ?></option>
                    <option value="evenings"><?php _e('Evenings', 'epsilon'); ?></option>
                    <option value="weekends"><?php _e('Weekends', 'epsilon'); ?></option>
                  </select>
                </div>
                <p class="pngm-post-field-error is-hidden" data-for="pngm_call_availability" hidden><?php _e('Please select call availability.', 'epsilon'); ?></p>
              </div>

              <div class="pngm-post-field pngm-post-whatsapp-field">
                <?php
                  $pngm_wa_checked = false;
                  if (function_exists('osc_item_id') && (int) osc_item_id() > 0 && function_exists('pngm_item_whatsapp_enabled')) {
                      $pngm_wa_checked = pngm_item_whatsapp_enabled((int) osc_item_id());
                  }
                ?>
                <label class="pngm-post-whatsapp-card" for="pngm_whatsapp">
                  <input type="checkbox" name="pngm_whatsapp" id="pngm_whatsapp" value="1"<?php echo $pngm_wa_checked ? ' checked="checked"' : ''; ?> />
                  <span class="pngm-post-whatsapp-ico" aria-hidden="true"><i class="fab fa-whatsapp"></i></span>
                  <span class="pngm-post-whatsapp-text">
                    <span class="pngm-post-whatsapp-copy"><?php _e('Yes, I\'m available on WhatsApp', 'epsilon'); ?></span>
                    <span class="pngm-post-whatsapp-consent"><?php _e('If enabled, buyers can open WhatsApp using the phone number on this listing. That number will be included in the public WhatsApp link.', 'epsilon'); ?></span>
                  </span>
                </label>
              </div>

              <div class="pngm-post-field">
                <span class="pngm-post-label"><?php _e('Preferred Contact Method', 'epsilon'); ?> <span class="req">*</span></span>
                <?php
                  $pngm_contact_pref = 'message';
                  if (function_exists('osc_item_id') && (int) osc_item_id() > 0 && function_exists('pngm_item_contact_pref')) {
                      $pngm_contact_pref = pngm_item_contact_pref((int) osc_item_id());
                  }
                ?>
                <div class="pngm-post-pills pngm-post-contact-pref" data-pills="contact-pref" role="radiogroup">
                  <button type="button" class="pngm-post-pill<?php echo $pngm_contact_pref === 'call' ? ' is-selected' : ''; ?>" data-value="call"><?php _e('Call', 'epsilon'); ?></button>
                  <button type="button" class="pngm-post-pill<?php echo $pngm_contact_pref === 'whatsapp' ? ' is-selected' : ''; ?>" data-value="whatsapp"><?php _e('WhatsApp', 'epsilon'); ?></button>
                  <button type="button" class="pngm-post-pill<?php echo ($pngm_contact_pref === 'message' || !in_array($pngm_contact_pref, array('call', 'whatsapp', 'message'), true)) ? ' is-selected' : ''; ?>" data-value="message"><?php _e('Message', 'epsilon'); ?></button>
                </div>
                <p class="pngm-post-muted pngm-post-field-hint"><?php _e('Shown to buyers as a small Preferred badge on your listing.', 'epsilon'); ?></p>
                <input type="hidden" name="pngm_contact_pref" id="pngm_contact_pref" value="<?php echo osc_esc_html($pngm_contact_pref); ?>" />
              </div>

              <div class="pngm-post-field pngm-post-msg-prefs">
                <span class="pngm-post-label"><?php _e('Message Preferences', 'epsilon'); ?></span>
                <label class="pngm-post-check">
                  <input type="checkbox" name="pngm_allow_messages" id="pngm_allow_messages" value="1" checked />
                  <span><?php _e('Allow buyers to send me messages on PNGMarket', 'epsilon'); ?></span>
                </label>
                <p class="pngm-post-muted pngm-post-field-hint"><?php _e('Lets buyers contact you through the site messenger.', 'epsilon'); ?></p>
                <label class="pngm-post-check">
                  <input type="checkbox" name="pngm_email_notify" id="pngm_email_notify" value="1" checked />
                  <span><?php _e('Email me when I receive a new message', 'epsilon'); ?></span>
                </label>
                <p class="pngm-post-muted pngm-post-field-hint"><?php _e('Send a notification to your email for each new buyer message.', 'epsilon'); ?></p>
                <span class="is-sr-only" aria-hidden="true"><?php ItemForm::show_email_checkbox(); ?></span>
              </div>

              <div class="pngm-post-field row user-email" id="pngm-email-field">
                <label for="contactEmail"><?php _e('E-mail', 'epsilon'); ?> <span class="req">*</span></label>
                <div class="input-box"><?php ItemForm::contact_email_text($prepare); ?></div>
              </div>
              <?php osc_run_hook('item_publish_seller'); ?>
            </div>
          </div>
          <aside class="pngm-post-side">
            <div class="pngm-post-card pngm-post-summary">
              <h3><?php _e('Summary', 'epsilon'); ?></h3>
              <dl>
                <div><dt><?php _e('Category', 'epsilon'); ?></dt><dd data-sum="root">—</dd></div>
                <div><dt><?php _e('Subcategory', 'epsilon'); ?></dt><dd data-sum="leaf">—</dd></div>
              </dl>
            </div>
            <?php pngm_post_tip_card(__('Your privacy', 'epsilon'), array(
              __('Your phone number is only shared with serious buyers.', 'epsilon'),
              __('You can update your contact details anytime.', 'epsilon'),
            )); ?>
          </aside>
        </div>
      </section>

      <!-- ========== STEP 6: REVIEW ========== -->
      <section class="pngm-post-step-panel" data-step="6" id="pngm-step-review" hidden>
        <div class="pngm-post-step-head">
          <h2><?php echo osc_esc_html($steps[6]['title']); ?></h2>
          <p><?php echo osc_esc_html($steps[6]['sub']); ?></p>
        </div>
        <div class="pngm-post-layout">
          <div class="pngm-post-main">
            <div class="pngm-post-card pngm-post-review">
              <h3 class="pngm-post-card-title"><?php _e('Review Your Listing', 'epsilon'); ?></h3>
              <p class="pngm-post-muted pngm-post-card-intro"><?php _e('Please review your details before publishing.', 'epsilon'); ?></p>
              <div class="pngm-post-review-preview" id="pngm-review-preview">
                <div class="pngm-post-review-hero">
                  <div class="pngm-post-review-cover" id="pngm-review-cover">
                    <span class="pngm-post-review-placeholder"><?php _e('No photo yet', 'epsilon'); ?></span>
                  </div>
                  <span class="pngm-post-review-badge" id="pngm-review-price">—</span>
                </div>
                <h3 class="pngm-post-review-title" id="pngm-review-title">—</h3>
                <dl class="pngm-post-review-meta" id="pngm-review-meta"></dl>
                <div class="pngm-post-review-thumbs" id="pngm-review-thumbs"></div>
              </div>
            </div>

            <div class="pngm-post-terms-wrap">
              <label class="pngm-post-check pngm-post-terms">
                <input type="checkbox" name="pngm_terms" id="pngm_terms" value="1" required />
                <span><?php printf(__('I confirm that this listing complies with %s Terms of Use.', 'epsilon'), osc_page_title()); ?></span>
              </label>
              <p class="pngm-post-field-error" data-for="pngm_terms" hidden><?php _e('Please accept the Terms of Use.', 'epsilon'); ?></p>
              <div class="row captcha pngm-post-captcha"><?php
                osc_run_hook('item_publish_bottom');
                // Do not call eps_show_recaptcha() here: it injects google api.js without
                // render=explicit, which auto-renders inside the hidden Review step and
                // throws "reCAPTCHA Timeout". Placeholder only — footer JS renders when
                // the step becomes visible (same pattern as login).
                if (function_exists('osc_recaptcha_items_enabled') && osc_recaptcha_items_enabled()
                    && function_exists('osc_recaptcha_public_key')
                ) {
                    $pngm_post_captcha_key = trim((string) osc_recaptcha_public_key(true));
                    if ($pngm_post_captcha_key !== '') {
                        echo '<div class="g-recaptcha pngm-g-recaptcha" data-sitekey="'
                            . osc_esc_html($pngm_post_captcha_key)
                            . '" data-pngm-recaptcha="1" data-pngm-defer="1"></div>';
                    }
                }
              ?></div>
            </div>
          </div>
          <aside class="pngm-post-side">
            <div class="pngm-post-card pngm-post-missing" id="pngm-missing-box" hidden>
              <h3><?php _e('Missing or incomplete', 'epsilon'); ?></h3>
              <ul id="pngm-missing-list"></ul>
            </div>
            <?php pngm_post_tip_card(__('Publishing tips', 'epsilon'), array(
              __('Double-check price and location.', 'epsilon'),
              __('Cover photo should be your best shot.', 'epsilon'),
              __('You can edit the listing after publishing.', 'epsilon'),
            )); ?>
          </aside>
        </div>
      </section>

      <div class="pngm-post-nav">
        <button type="button" class="pngm-post-btn pngm-post-btn-ghost" id="pngm-post-cancel"><?php _e('Cancel', 'epsilon'); ?></button>
        <button type="button" class="pngm-post-btn pngm-post-btn-ghost" id="pngm-post-back" hidden><?php _e('Back', 'epsilon'); ?></button>
        <div class="pngm-post-nav-spacer"></div>
        <button type="button" class="pngm-post-btn pngm-post-btn-secondary" id="pngm-post-preview" hidden><?php _e('Preview', 'epsilon'); ?></button>
        <button type="button" class="pngm-post-btn pngm-post-btn-primary" id="pngm-post-next"><?php _e('Next', 'epsilon'); ?></button>
        <button type="submit" class="pngm-post-btn pngm-post-btn-primary" id="pngm-post-publish" hidden><?php _e('Publish', 'epsilon'); ?></button>
      </div>

      <?php osc_run_hook('item_publish_buttons'); ?>
      <?php osc_run_hook('item_publish_after'); ?>
    </form>
  </div>

  <div class="pngm-post-preview-modal" id="pngm-preview-modal" hidden>
    <div class="pngm-post-preview-backdrop" data-preview-close="1"></div>
    <div class="pngm-post-preview-dialog pngm-post-preview-dialog--public" role="dialog" aria-modal="true" aria-labelledby="pngm-preview-heading">
      <div class="pngm-post-preview-dialog-head">
        <h3 id="pngm-preview-heading"><?php _e('Listing preview', 'epsilon'); ?></h3>
        <button type="button" class="pngm-post-preview-close" data-preview-close="1" aria-label="<?php echo osc_esc_html(__('Close', 'epsilon')); ?>">&times;</button>
      </div>
      <div class="pngm-post-preview-dialog-body" id="pngm-preview-modal-body"></div>
    </div>
  </div>

  <script type="application/json" id="pngm-post-subcats"><?php echo json_encode($subcat_map); ?></script>
  <script type="application/json" id="pngm-post-boot"><?php echo json_encode(array(
    'root' => (int) $cat_path['root'],
    'leaf' => (int) $cat_path['leaf'],
    'edit' => (bool) $edit,
    'priceType' => $price_type,
    'locale' => $locale,
    'homeUrl' => osc_base_url(),
    'ajaxUrl' => osc_base_url(true),
    'itemId' => ($edit ? (int) osc_item_id() : 0),
    'labels' => array(
      'category' => __('Category', 'epsilon'),
      'subcategory' => __('Subcategory', 'epsilon'),
      'title' => __('Title', 'epsilon'),
      'price' => __('Price', 'epsilon'),
      'condition' => __('Condition', 'epsilon'),
      'transaction' => __('Transaction', 'epsilon'),
      'location' => __('Location', 'epsilon'),
      'checkSeller' => __('Check with seller', 'epsilon'),
      'free' => __('Free', 'epsilon'),
      'selectSub' => __('Please select a subcategory.', 'epsilon'),
      'selectCategory' => __('Please select a category.', 'epsilon'),
      'fixErrors' => __('Please fix the following:', 'epsilon'),
      'needTitle' => __('Please enter a title (at least 3 characters).', 'epsilon'),
      'dupTitleCheck' => __('Checking title…', 'epsilon'),
      'dupTitleFail' => __('A listing with this title already exists. Please choose a different title.', 'epsilon'),
      'needDesc' => __('Please enter a description.', 'epsilon'),
      'needPrice' => __('Please enter a price, or choose Check with seller.', 'epsilon'),
      'needAttr' => __('Please complete: %s', 'epsilon'),
      'needMakeOther' => __('Please specify make / model.', 'epsilon'),
      'needPhoto' => __('Please upload at least one photo.', 'epsilon'),
      'requiredField' => __('Required field', 'epsilon'),
      'needTerms' => __('Please accept the Terms of Use.', 'epsilon'),
      'needRegion' => __('Please select a province / region.', 'epsilon'),
      'needCity' => __('Please select a city / town.', 'epsilon'),
      'needName' => __('Please enter your full name.', 'epsilon'),
      'needPhone' => __('Please enter a phone number.', 'epsilon'),
      'needAvail' => __('Please select call availability.', 'epsilon'),
      'needEmail' => __('Please enter a valid email.', 'epsilon'),
      'postedBy' => __('Posted by', 'epsilon'),
      'phone' => __('Phone', 'epsilon'),
      'whatsapp' => __('WhatsApp', 'epsilon'),
      'yes' => __('Yes', 'epsilon'),
      'no' => __('No', 'epsilon'),
      'noPhoto' => __('No photo yet', 'epsilon'),
      'message' => __('Chat', 'epsilon'),
      'call' => __('Call', 'epsilon'),
      'contactSeller' => __('Contact Seller', 'epsilon'),
      'description' => __('Description', 'epsilon'),
      'preferred' => __('PREFERRED', 'epsilon'),
      'viewOnMap' => __('View on map', 'epsilon'),
      'unknownLocation' => __('Unknown location', 'epsilon'),
      'sellerProfile' => __('Seller\'s profile', 'epsilon'),
      'previewMeta' => __('Listing preview', 'epsilon'),
      'untitled' => __('Untitled listing', 'epsilon'),
      'noContact' => __('No contact options selected', 'epsilon'),
      'publishing' => __('Publishing…', 'epsilon'),
      'publishingHint' => __('Publishing your listing, please wait…', 'epsilon'),
    ),
  )); ?></script>

  <script type="text/javascript">
  // Parent publish helpers (price toggles, FineUploader rotate, readonly logged-in fields)
  $(document).ready(function(){
    // Init jquery.validate early so Attributes plugin .rules('add') does not throw
    // "Cannot read properties of undefined (reading 'settings')"
    if ($.fn.validate && !$('form[name="item"]').data('validator')) {
      $('form[name="item"]').validate({
        // Do NOT ignore :hidden — wizard steps use display:none but must validate on Publish
        ignore: 'input[type=hidden], .is-sr-only, .is-sr-only *, .pngm-post-tx-native, .pngm-post-tx-native *',
        errorLabelContainer: '#error_list',
        wrapper: 'li',
        onkeyup: false,
        onclick: false,
        onfocusout: false,
        invalidHandler: function (event, validator) {
          if (!validator || !validator.errorList || !validator.errorList.length) {
            return;
          }
          var $first = $(validator.errorList[0].element);
          var $panel = $first.closest('.pngm-post-step-panel');
          if ($panel.length && !$panel.hasClass('is-active')) {
            var stepNum = parseInt($panel.data('step'), 10) || 1;
            var $go = $('#pngm-post-stepper [data-step="' + stepNum + '"], .pngm-post-step[data-step="' + stepNum + '"]').first();
            // Reveal the step that owns the first error.
            $panel.prop('hidden', false).removeAttr('hidden').addClass('is-active').show();
            $panel.siblings('.pngm-post-step-panel').removeClass('is-active').prop('hidden', true).attr('hidden', 'hidden').hide();
            document.body.className = document.body.className.replace(/\bpngm-wizard-step-\d+\b/g, '').replace(/\s+/g, ' ').trim();
            document.body.classList.add('pngm-wizard-step-' + stepNum);
          }
          window.setTimeout(function () {
            var el = $first.get(0);
            if (el && typeof el.scrollIntoView === 'function') {
              el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            try { $first.trigger('focus'); } catch (e) { /* ignore */ }
          }, 80);
        },
        rules: {
          "title[<?php echo osc_esc_js(osc_current_user_locale()); ?>]": { required: true, minlength: 3 },
          "description[<?php echo osc_esc_js(osc_current_user_locale()); ?>]": { required: true },
          contactEmail: { required: true, email: true },
          contactName: { required: true, minlength: 2 }
        },
        messages: {
          "title[<?php echo osc_esc_js(osc_current_user_locale()); ?>]": {
            required: '<?php echo osc_esc_js(__('Please enter a title (at least 3 characters).', 'epsilon')); ?>',
            minlength: '<?php echo osc_esc_js(__('Please enter a title (at least 3 characters).', 'epsilon')); ?>'
          },
          "description[<?php echo osc_esc_js(osc_current_user_locale()); ?>]": {
            required: '<?php echo osc_esc_js(__('Please enter a description.', 'epsilon')); ?>'
          },
          contactEmail: { required: '<?php echo osc_esc_js(__('Please enter a valid email.', 'epsilon')); ?>', email: '<?php echo osc_esc_js(__('Please enter a valid email.', 'epsilon')); ?>' },
          contactName: { required: '<?php echo osc_esc_js(__('Please enter your full name.', 'epsilon')); ?>' }
        }
      });
    }

    if($('select[name="countryId"]').val() == '') { $('select[name="regionId"]').attr('disabled', 'disabled'); }
    if($('select[name="regionId"]').val() == '') { $('select[name="cityId"]').attr('disabled', 'disabled'); }

    $('body').on('click', '.qq-upload-rotate', function(e){
      e.preventDefault();
      var img = $(this).parent().find('.ajax_preview_img img');
      var url = '<?php echo osc_current_web_theme_url('ajax-rotate.php'); ?>', angle = parseInt(img.attr('data-angle'));
      if(isNaN(angle)){ angle = 0; }
      angle += 90;
      if(!img.hasClass('disabled')) {
        img.addClass('disabled');
        img.rotate({ animateTo: angle, duration: 300, callback: function() {
          $.ajax({ url: url, type: 'POST', data: { 'action': 'rotate', 'file_name' : img.attr('alt') },
            complete: function(){ img.removeClass('disabled'); } });
        }});
      }
      img.attr('data-angle', angle);
    });

    $('.item-publish input[name^="title"]').attr('placeholder', '<?php echo osc_esc_js(__('Summarize your offer', 'epsilon')); ?>');
    $('.item-publish textarea[name^="description"]').attr('placeholder', '<?php echo osc_esc_js(__('Detail description of your offer', 'epsilon')); ?>');
    $('.item-publish input#address, .item-publish input[name="address"]').attr('placeholder', '<?php echo osc_esc_js(__('e.g. Boroko, Section 54', 'epsilon')); ?>');
    $('.item-publish input[name="contactPhone"], .item-publish input[name="sPhone"], #contactPhone, #sPhone').prop('type', 'tel').attr('placeholder', '7XX XXX XXX');
    $('input#price').attr('autocomplete', 'off').attr('placeholder', '<?php echo osc_esc_js(__('Price', 'epsilon')); ?>');
    $('input[name="showPhone"]').prop('checked', true);
    $('input[name="showEmail"]').prop('checked', false);
    <?php if (osc_is_web_user_logged_in()) { ?>
      $('input[name="contactName"]').attr('readonly', true);
      $('input[name="contactEmail"]').attr('readonly', true);
    <?php } ?>

    setInterval(function(){
      $('input[name="qqfile"]').prop('accept', 'image/*');
      $("img[src$='uploads/temp/']").closest('.qq-upload-success').remove();
      <?php if (!function_exists('osc_image_upload_library') || osc_image_upload_library() != 'UPPY') { ?>
      $('#restricted-fine-uploader li.qq-upload-success').each(function() {
        if(!$(this).find('.qq-upload-rotate').length) {
          $(this).append('<a class="qq-upload-rotate btn" href="#" title="<?php echo osc_esc_js(__('Rotate image', 'epsilon')); ?>"><i class="fas fa-undo fa-flip-horizontal"></i></a>');
        }
      });
      <?php } ?>
    }, 250);

    if (typeof tabberAutomatic === 'function') { tabberAutomatic(); }
  });
  </script>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
