</div>

<?php 
  $location_cookie = eps_location_from_cookies();
  $mes_counter = eps_count_messages(osc_logged_user_id()); 
  $fav_counter = eps_count_favorite();
  
  $indicator = '<svg class="indicator" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512" width="20px" height="20px"><path d="M24.707 38.101L4.908 57.899c-4.686 4.686-4.686 12.284 0 16.971L185.607 256 4.908 437.13c-4.686 4.686-4.686 12.284 0 16.971L24.707 473.9c4.686 4.686 12.284 4.686 16.971 0l209.414-209.414c4.686-4.686 4.686-12.284 0-16.971L41.678 38.101c-4.687-4.687-12.285-4.687-16.971 0z"/></svg>';
?>

<?php if(!osc_is_web_user_logged_in()) { ?>
  <section class="promo">
    <div class="container">
      <span><?php _e('Are you a professional seller?', 'epsilon'); ?></span>
      <a href="<?php echo osc_register_account_url(); ?>" class="btn btn-transparent"><?php _e('Create an account', 'epsilon'); ?></a>
    </div>
  </section>
<?php } ?>

<?php osc_run_hook('footer_pre'); ?>

<footer class="pngm-footer">
  <?php osc_run_hook('footer_top'); ?>

  <?php
    $pngm_contact = function_exists('pngm_footer_contact') ? pngm_footer_contact() : array(
      'name' => 'PNGMarket',
      'email' => 'info@pngmarket.online',
      'phone' => '',
      'tel' => '',
      'address' => 'Port Moresby, Papua New Guinea',
      'tagline' => '',
    );
    $pngm_socials = function_exists('pngm_footer_social_links') ? pngm_footer_social_links() : array();
    $pngm_pages = function_exists('pngm_footer_info_pages') ? pngm_footer_info_pages() : array();
    $pngm_social_labels = array(
      'whatsapp'  => __('WhatsApp', 'epsilon'),
      'facebook'  => __('Facebook', 'epsilon'),
      'instagram' => __('Instagram', 'epsilon'),
      'x'         => __('X (Twitter)', 'epsilon'),
      'linkedin'  => __('LinkedIn', 'epsilon'),
      'pinterest' => __('Pinterest', 'epsilon'),
    );
  ?>
  
  <div class="container">
    <section class="one">
      <div class="col contact">
        <h4><?php _e('Contact', 'epsilon'); ?></h4>

        <p class="logo"><?php echo eps_logo(); ?></p>
        <p class="company"><strong><?php echo osc_esc_html($pngm_contact['name']); ?></strong></p>
        <?php if ($pngm_contact['tagline'] !== '') { ?>
          <p class="pngm-footer-tagline"><?php echo osc_esc_html($pngm_contact['tagline']); ?></p>
        <?php } ?>
        <?php if ($pngm_contact['phone'] !== '') { ?>
          <p class="pngm-footer-line"><?php _e('Phone', 'epsilon'); ?>: <a href="tel:<?php echo osc_esc_html($pngm_contact['tel']); ?>"><?php echo osc_esc_html($pngm_contact['phone']); ?></a></p>
        <?php } ?>
        <?php if ($pngm_contact['email'] !== '') { ?>
          <p class="pngm-footer-line"><?php _e('Email', 'epsilon'); ?>: <a href="mailto:<?php echo osc_esc_html($pngm_contact['email']); ?>"><?php echo osc_esc_html($pngm_contact['email']); ?></a></p>
        <?php } ?>
        <?php if ($pngm_contact['address'] !== '') { ?>
          <p><?php echo osc_esc_html($pngm_contact['address']); ?></p>
        <?php } ?>

        <div class="quick-links">
          <?php if (getBoolPreference('web_contact_form_disabled') != 1) { ?>
            <a class="btn-mini" href="<?php echo osc_contact_url(); ?>"><?php _e('Contact Us', 'epsilon'); ?></a>
          <?php } ?>

          <?php if (function_exists('im_messages') && osc_is_web_user_logged_in()) { ?>
            <a class="btn-mini" href="<?php echo osc_route_url('im-threads'); ?>"><?php _e('Messages', 'epsilon'); ?></a>
          <?php } ?>

          <?php if (function_exists('fi_make_favorite')) { ?>
            <a class="btn-mini" href="<?php echo osc_route_url('favorite-lists'); ?>"><?php _e('Favourites', 'epsilon'); ?></a>
          <?php } ?>

          <?php if (function_exists('bpr_companies_url')) { ?>
            <a class="btn-mini" href="<?php echo bpr_companies_url(); ?>"><?php _e('Companies', 'epsilon'); ?></a>
          <?php } ?>

          <?php osc_run_hook('footer_links'); ?>
        </div>
      </div>
      
      <?php if (count($pngm_socials) > 0) { ?>
        <div class="col socialx">
          <h4><?php _e('Follow us', 'epsilon'); ?></h4>

          <?php foreach ($pngm_socials as $type => $url) {
            $label = isset($pngm_social_labels[$type]) ? $pngm_social_labels[$type] : ucfirst($type);
            $icon = 'fab fa-' . ($type === 'x' ? 'twitter' : ($type === 'whatsapp' ? 'whatsapp' : $type));
            if ($type === 'facebook') { $icon = 'fab fa-facebook-f'; }
            if ($type === 'linkedin') { $icon = 'fab fa-linkedin'; }
            if ($type === 'pinterest') { $icon = 'fab fa-pinterest-p'; }
          ?>
            <a class="<?php echo osc_esc_html($type === 'x' ? 'twitter' : $type); ?>" href="<?php echo osc_esc_html($url); ?>" target="_blank" rel="noopener noreferrer">
              <?php if ($type === 'x') { ?>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="15px" height="15px"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>
              <?php } else { ?>
                <i class="<?php echo osc_esc_html($icon); ?>"></i>
              <?php } ?>
              <?php echo osc_esc_html($label); ?>
            </a>
          <?php } ?>
        </div>
      <?php } ?>
      
      <div class="col pages">
        <h4><?php _e('Information', 'epsilon'); ?></h4>

        <?php foreach ($pngm_pages as $page) { ?>
          <a href="<?php echo osc_esc_html($page['url']); ?>"><?php echo osc_esc_html($page['title']); ?></a>
        <?php } ?>

        <?php if (getBoolPreference('web_contact_form_disabled') != 1) { ?>
          <a href="<?php echo osc_contact_url(); ?>"><?php _e('Contact Us', 'epsilon'); ?></a>
        <?php } ?>
      </div>

      <?php if (osc_count_web_enabled_locales() > 1) { ?>
        <div class="col locale">
          <h4><?php _e('Change language', 'epsilon'); ?></h4>
          <?php osc_goto_first_locale(); ?>

          <?php while (osc_has_web_enabled_locales()) { ?>
            <a class="lang <?php if (osc_locale_code() == osc_current_user_locale()) { ?>active<?php } ?>" href="<?php echo osc_change_language_url(osc_locale_code()); ?>">
              <img src="<?php echo eps_country_flag_image(strtolower(substr(osc_locale_code(), 3))); ?>" alt="<?php echo osc_esc_html(__('Country flag', 'epsilon')); ?>" />
              <span><?php echo osc_locale_name(); ?>&#x200E;</span>
            </a>
          <?php } ?>
        </div>
      <?php } ?>
      
      <div class="footer-hook"><?php osc_run_hook('footer'); ?></div>
      <div class="footer-widgets"><?php osc_show_widgets('footer'); ?></div>
    </section>
    
    <section class="two">
      <?php if (getBoolPreference('web_contact_form_disabled') != 1) { ?>
        <a href="<?php echo osc_contact_url(); ?>"><?php _e('Contact Us', 'epsilon'); ?></a>
      <?php } ?>

      <?php foreach ($pngm_pages as $page) { ?>
        <a href="<?php echo osc_esc_html($page['url']); ?>"><?php echo osc_esc_html($page['title']); ?></a>
      <?php } ?>
      
      <span><?php _e('Copyright', 'epsilon'); ?> &copy; <?php echo date('Y'); ?> <?php echo osc_esc_html($pngm_contact['name']); ?>. <?php _e('All rights reserved', 'epsilon'); ?>.</span>
    </section>
  </div>
</footer>

<?php osc_run_hook('footer_after'); ?>

<div id="navi-bar" class="isMobile">
  <a href="<?php echo osc_base_url(); ?>" class="l1 <?php if(osc_is_home_page()) { ?>active<?php } ?>">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="20" height="20"><path d="M570.24 247.41L323.87 45a56.06 56.06 0 0 0-71.74 0L5.76 247.41a16 16 0 0 0-2 22.54L14 282.25a16 16 0 0 0 22.53 2L64 261.69V448a32.09 32.09 0 0 0 32 32h128a32.09 32.09 0 0 0 32-32V344h64v104a32.09 32.09 0 0 0 32 32h128a32.07 32.07 0 0 0 32-31.76V261.67l27.53 22.62a16 16 0 0 0 22.53-2L572.29 270a16 16 0 0 0-2.05-22.59zM463.85 432H368V328a32.09 32.09 0 0 0-32-32h-96a32.09 32.09 0 0 0-32 32v104h-96V222.27L288 77.65l176 144.56z"/></svg>
    <span><?php _e('Home', 'epsilon'); ?></span>
  </a>

  <?php if(function_exists('im_messages')) { ?>
    <a href="<?php echo osc_route_url('im-threads'); ?>" class="l2 <?php if(osc_get_osclass_location() == 'im') { ?>active<?php } ?>">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20"><path d="M448 0H64C28.7 0 0 28.7 0 64v288c0 35.3 28.7 64 64 64h96v84c0 7.1 5.8 12 12 12 2.4 0 4.9-.7 7.1-2.4L304 416h144c35.3 0 64-28.7 64-64V64c0-35.3-28.7-64-64-64zm16 352c0 8.8-7.2 16-16 16H288l-12.8 9.6L208 428v-60H64c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16h384c8.8 0 16 7.2 16 16v288zm-96-216H144c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h224c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16zm-96 96H144c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h128c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16z"/></svg>

      <?php if($mes_counter > 0) { ?>
        <span class="counter"><?php echo $mes_counter; ?></span>
      <?php } ?>    
      
      <span><?php _e('Messages', 'epsilon'); ?></span>
    </a>
  <?php } else { ?>
    <a href="<?php echo osc_search_url(array('page' => 'search')); ?>" class="l2 <?php if(osc_is_search_page()) { ?>active<?php } ?>">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20"><path d="M508.5 468.9L387.1 347.5c-2.3-2.3-5.3-3.5-8.5-3.5h-13.2c31.5-36.5 50.6-84 50.6-136C416 93.1 322.9 0 208 0S0 93.1 0 208s93.1 208 208 208c52 0 99.5-19.1 136-50.6v13.2c0 3.2 1.3 6.2 3.5 8.5l121.4 121.4c4.7 4.7 12.3 4.7 17 0l22.6-22.6c4.7-4.7 4.7-12.3 0-17zM208 368c-88.4 0-160-71.6-160-160S119.6 48 208 48s160 71.6 160 160-71.6 160-160 160z"/></svg>
      <span><?php _e('Search', 'epsilon'); ?></span>
    </a>
  <?php } ?>

  <a href="<?php echo osc_item_post_url(); ?>" class="post l3 <?php if(osc_is_publish_page() || osc_is_edit_page()) { ?>active<?php } ?>">
    <?php if(osc_is_publish_page() || osc_is_edit_page()) { ?>
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20"><path d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"/></svg>
    <?php } else { ?>
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="20" height="20"><path d="M416 208H272V64c0-17.67-14.33-32-32-32h-32c-17.67 0-32 14.33-32 32v144H32c-17.67 0-32 14.33-32 32v32c0 17.67 14.33 32 32 32h144v144c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32V304h144c17.67 0 32-14.33 32-32v-32c0-17.67-14.33-32-32-32z"/></svg>
    <?php } ?>
  </a>
  
  <?php if(function_exists('fi_make_favorite')) { ?>
    <a href="<?php echo osc_route_url('favorite-lists'); ?>" class="l4 favorite <?php if(osc_get_osclass_location() == 'fi') { ?>active<?php } ?>">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" height="20" width="20"><path d="M287.9 0C297.1 0 305.5 5.25 309.5 13.52L378.1 154.8L531.4 177.5C540.4 178.8 547.8 185.1 550.7 193.7C553.5 202.4 551.2 211.9 544.8 218.2L433.6 328.4L459.9 483.9C461.4 492.9 457.7 502.1 450.2 507.4C442.8 512.7 432.1 513.4 424.9 509.1L287.9 435.9L150.1 509.1C142.9 513.4 133.1 512.7 125.6 507.4C118.2 502.1 114.5 492.9 115.1 483.9L142.2 328.4L31.11 218.2C24.65 211.9 22.36 202.4 25.2 193.7C28.03 185.1 35.5 178.8 44.49 177.5L197.7 154.8L266.3 13.52C270.4 5.249 278.7 0 287.9 0L287.9 0zM287.9 78.95L235.4 187.2C231.9 194.3 225.1 199.3 217.3 200.5L98.98 217.9L184.9 303C190.4 308.5 192.9 316.4 191.6 324.1L171.4 443.7L276.6 387.5C283.7 383.7 292.2 383.7 299.2 387.5L404.4 443.7L384.2 324.1C382.9 316.4 385.5 308.5 391 303L476.9 217.9L358.6 200.5C350.7 199.3 343.9 194.3 340.5 187.2L287.9 78.95z"/></svg>

      <?php if($fav_counter > 0) { ?>
        <span class="counter"><?php echo $fav_counter; ?></span>
      <?php } ?>    
      
      <span><?php _e('Favorite', 'epsilon'); ?></span>
    </a>
    
  <?php } else if (function_exists('svi_save_btn')) { ?>
    <a href="#" class="l4 favorite svi-show-saved">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" height="20" width="20"><path d="M287.9 0C297.1 0 305.5 5.25 309.5 13.52L378.1 154.8L531.4 177.5C540.4 178.8 547.8 185.1 550.7 193.7C553.5 202.4 551.2 211.9 544.8 218.2L433.6 328.4L459.9 483.9C461.4 492.9 457.7 502.1 450.2 507.4C442.8 512.7 432.1 513.4 424.9 509.1L287.9 435.9L150.1 509.1C142.9 513.4 133.1 512.7 125.6 507.4C118.2 502.1 114.5 492.9 115.1 483.9L142.2 328.4L31.11 218.2C24.65 211.9 22.36 202.4 25.2 193.7C28.03 185.1 35.5 178.8 44.49 177.5L197.7 154.8L266.3 13.52C270.4 5.249 278.7 0 287.9 0L287.9 0zM287.9 78.95L235.4 187.2C231.9 194.3 225.1 199.3 217.3 200.5L98.98 217.9L184.9 303C190.4 308.5 192.9 316.4 191.6 324.1L171.4 443.7L276.6 387.5C283.7 383.7 292.2 383.7 299.2 387.5L404.4 443.7L384.2 324.1C382.9 316.4 385.5 308.5 391 303L476.9 217.9L358.6 200.5C350.7 199.3 343.9 194.3 340.5 187.2L287.9 78.95z"/></svg>

      <?php if($fav_counter > 0) { ?>
        <span class="counter"><?php echo $fav_counter; ?></span>
      <?php } ?>    
      
      <span><?php _e('Saved', 'epsilon'); ?></span>
    </a>

  <?php } else { ?>
    <a href="<?php echo osc_is_web_user_logged_in() ? osc_user_dashboard_url() : osc_user_login_url(); ?>" class="l6 <?php if(in_array(osc_get_osclass_location(), array('user','login','recover','forgot','register'))) { ?>active<?php } ?>">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="20" height="20"><path d="M313.6 304c-28.7 0-42.5 16-89.6 16-47.1 0-60.8-16-89.6-16C60.2 304 0 364.2 0 438.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-25.6c0-74.2-60.2-134.4-134.4-134.4zM400 464H48v-25.6c0-47.6 38.8-86.4 86.4-86.4 14.6 0 38.3 16 89.6 16 51.7 0 74.9-16 89.6-16 47.6 0 86.4 38.8 86.4 86.4V464zM224 288c79.5 0 144-64.5 144-144S303.5 0 224 0 80 64.5 80 144s64.5 144 144 144zm0-240c52.9 0 96 43.1 96 96s-43.1 96-96 96-96-43.1-96-96 43.1-96 96-96z"/></svg>
      <span><?php echo osc_is_web_user_logged_in() ? __('My Account', 'epsilon') : __('Sign in', 'epsilon'); ?></span>
    </a>
  <?php } ?>

  <?php if(eps_param('default_location') == 1) { ?>
    <a class="l6 location <?php if(@$location_cookie['success'] === true) { ?>selected<?php } ?>" href="#">
      <?php if(@$location_cookie['success'] === true) { ?><i class="mark fas fa-map-marker-alt"></i><?php } ?>
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512" width="18" height="18"><path d="M347.94 129.86L203.6 195.83a31.938 31.938 0 0 0-15.77 15.77l-65.97 144.34c-7.61 16.65 9.54 33.81 26.2 26.2l144.34-65.97a31.938 31.938 0 0 0 15.77-15.77l65.97-144.34c7.61-16.66-9.54-33.81-26.2-26.2zm-77.36 148.72c-12.47 12.47-32.69 12.47-45.16 0-12.47-12.47-12.47-32.69 0-45.16 12.47-12.47 32.69-12.47 45.16 0 12.47 12.47 12.47 32.69 0 45.16zM248 8C111.03 8 0 119.03 0 256s111.03 248 248 248 248-111.03 248-248S384.97 8 248 8zm0 448c-110.28 0-200-89.72-200-200S137.72 56 248 56s200 89.72 200 200-89.72 200-200 200z"/></svg>

      <span>
        <?php 
          if(@$location_cookie['success'] !== true) {
            _e('Location', 'epsilon');
          } else {
            echo @$location_cookie['s_location'] <> '' ? osc_location_native_name_selector($location_cookie, 's_name') : __('Location', 'epsilon');
          }
        ?>
      </span>
    </a>
  <?php } else { ?>
    <?php if(getBoolPreference('web_contact_form_disabled') != 1) { ?>
      <a href="<?php echo osc_contact_url(); ?>" class="l4 <?php if(osc_is_contact_page()) { ?>active<?php } ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20"><path d="M494.586 164.516c-4.697-3.883-111.723-89.95-135.251-108.657C337.231 38.191 299.437 0 256 0c-43.205 0-80.636 37.717-103.335 55.859-24.463 19.45-131.07 105.195-135.15 108.549A48.004 48.004 0 0 0 0 201.485V464c0 26.51 21.49 48 48 48h416c26.51 0 48-21.49 48-48V201.509a48 48 0 0 0-17.414-36.993zM464 458a6 6 0 0 1-6 6H54a6 6 0 0 1-6-6V204.347c0-1.813.816-3.526 2.226-4.665 15.87-12.814 108.793-87.554 132.364-106.293C200.755 78.88 232.398 48 256 48c23.693 0 55.857 31.369 73.41 45.389 23.573 18.741 116.503 93.493 132.366 106.316a5.99 5.99 0 0 1 2.224 4.663V458zm-31.991-187.704c4.249 5.159 3.465 12.795-1.745 16.981-28.975 23.283-59.274 47.597-70.929 56.863C336.636 362.283 299.205 400 256 400c-43.452 0-81.287-38.237-103.335-55.86-11.279-8.967-41.744-33.413-70.927-56.865-5.21-4.187-5.993-11.822-1.745-16.981l15.258-18.528c4.178-5.073 11.657-5.843 16.779-1.726 28.618 23.001 58.566 47.035 70.56 56.571C200.143 320.631 232.307 352 256 352c23.602 0 55.246-30.88 73.41-45.389 11.994-9.535 41.944-33.57 70.563-56.568 5.122-4.116 12.601-3.346 16.778 1.727l15.258 18.526z"/></svg>
        <span><?php _e('Contact us', 'epsilon'); ?></span>
      </a>
    <?php } else { ?>
      <a href="<?php echo osc_base_url(); ?>" class="l4">
        <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M459.7 194.6C482 174.1 496 144.7 496 112 496 50.1 445.9 0 384 0c-45.3 0-84.3 26.8-101.9 65.5-17.3-2-34.9-2-52.2 0C212.3 26.8 173.3 0 128 0 66.1 0 16 50.1 16 112c0 32.7 14 62.1 36.3 82.6C39.3 223 32 254.7 32 288c0 53.2 18.6 102.1 49.5 140.5L39 471c-9.4 9.4-9.4 24.6 0 33.9 9.4 9.4 24.6 9.4 33.9 0l42.5-42.5c81.5 65.7 198.7 66.4 281 0L439 505c9.4 9.4 24.6 9.4 33.9 0 9.4-9.4 9.4-24.6 0-33.9l-42.5-42.5c31-38.4 49.5-87.3 49.5-140.5.1-33.4-7.2-65.1-20.2-93.5zM384 48c35.3 0 64 28.7 64 64 0 15.1-5.3 29-14 39.9-26.2-34.2-62-60.6-103.3-75.2C342.1 59.4 361.7 48 384 48zM64 112c0-35.3 28.7-64 64-64 22.3 0 41.9 11.4 53.4 28.7-41.4 14.6-77.2 41-103.3 75.2C69.3 141 64 127.1 64 112zm192 352c-97.3 0-176-78.7-176-176 0-97 78.4-176 176-176 97.4 0 176 78.8 176 176 0 97.3-78.7 176-176 176zm46.2-95.7l-69-47.5c-3.3-2.2-5.2-5.9-5.2-9.9V180c0-6.6 5.4-12 12-12h32c6.6 0 12 5.4 12 12v107.7l50 34.4c5.5 3.8 6.8 11.2 3.1 16.7L319 365.2c-3.8 5.4-11.3 6.8-16.8 3.1z"/></svg>
        <span><?php _e('Subscriptions', 'epsilon'); ?></span>
      </a>
    <?php } ?>
  <?php } ?>
</div>

<?php if(eps_banner('body_left') !== false) { ?>
  <div id="body-banner" class="bleft">
    <?php echo eps_banner('body_left'); ?>
  </div>
<?php } ?>

<?php if(eps_banner('body_right') !== false) { ?>
  <div id="body-banner" class="bright">
    <?php echo eps_banner('body_right'); ?>
  </div>
<?php } ?>

<?php if(eps_param('scrolltop') == 1) { ?>
  <a id="scroll-to-top"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="20" height="20"><path d="M24 32h336c13.3 0 24 10.7 24 24v24c0 13.3-10.7 24-24 24H24C10.7 104 0 93.3 0 80V56c0-13.3 10.7-24 24-24zm232 424V320h87.7c17.8 0 26.7-21.5 14.1-34.1L205.7 133.7c-7.5-7.5-19.8-7.5-27.3 0L26.1 285.9C13.5 298.5 22.5 320 40.3 320H128v136c0 13.3 10.7 24 24 24h80c13.3 0 24-10.7 24-24z"/></svg></a>
<?php } ?>

<div id="menu-cover" class="mobile-box">
  <svg class="close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="32px" height="32px"><path fill="currentColor" d="M193.94 256L296.5 153.44l21.15-21.15c3.12-3.12 3.12-8.19 0-11.31l-22.63-22.63c-3.12-3.12-8.19-3.12-11.31 0L160 222.06 36.29 98.34c-3.12-3.12-8.19-3.12-11.31 0L2.34 120.97c-3.12 3.12-3.12 8.19 0 11.31L126.06 256 2.34 379.71c-3.12 3.12-3.12 8.19 0 11.31l22.63 22.63c3.12 3.12 8.19 3.12 11.31 0L160 289.94 262.56 392.5l21.15 21.15c3.12 3.12 8.19 3.12 11.31 0l22.63-22.63c3.12-3.12 3.12-8.19 0-11.31L193.94 256z" class=""></path></svg>
</div>

<div id="side-menu" class="mobile-box<?php if(osc_is_web_user_logged_in()) { ?> logged<?php } ?>">
  <div class="wrap">
    <div class="section lead">
      <a href="<?php echo (osc_is_web_user_logged_in() ? osc_user_profile_url() : osc_user_login_url()); ?>" class="img-container" target="_blank" title="<?php echo osc_esc_html(__('Upload profile picture', 'epsilon')); ?>">
        <img src="<?php echo eps_profile_picture(osc_is_web_user_logged_in() ? osc_logged_user_id() : NULL, 'medium'); ?>" alt="<?php echo osc_esc_html(osc_logged_user_name() <> '' ? osc_logged_user_name() : __('Non-logged user', 'epsilon')); ?>" width="36" height="36"/>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 408c-66.2 0-120-53.8-120-120s53.8-120 120-120 120 53.8 120 120-53.8 120-120 120zm0-192c-39.7 0-72 32.3-72 72s32.3 72 72 72 72-32.3 72-72-32.3-72-72-72zm-24 72c0-13.2 10.8-24 24-24 8.8 0 16-7.2 16-16s-7.2-16-16-16c-30.9 0-56 25.1-56 56 0 8.8 7.2 16 16 16s16-7.2 16-16zm110.7-145H464v288H48V143h121.3l24-64h125.5l23.9 64zM324.3 31h-131c-20 0-37.9 12.4-44.9 31.1L136 95H48c-26.5 0-48 21.5-48 48v288c0 26.5 21.5 48 48 48h416c26.5 0 48-21.5 48-48V143c0-26.5-21.5-48-48-48h-88l-14.3-38c-5.8-15.7-20.7-26-37.4-26z"/></svg>
      </a>
      
      <div class="line1"><?php _e('Hello', 'epsilon'); ?> <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEgAAABICAMAAABiM0N1AAAAV1BMVEVHcEwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/yD3UjQH3wDcw0fbemg4jGwfjsjbtsCUln7uvgx8acIMFEBJKOhEtweMJKjHHmy5sUxcOPUiRcB9IUWvxAAAACnRSTlMAMRr/81Lhpcd5+GBt8QAAAwBJREFUWMPtmNd2pSAUhqOiWOCIvb7/cw7tKCItOncz+yJxmfCt3YB/+/Pz3/6SpTlIQA6L+CWnSL4Go1f+JIrBF17lKigB6WMQoMtbPLX9LFDFOxCzlqNA8Sq0YesnjHv+/Nvo0gJCmJ7JHnrq1EAfsl9lPJJJzs7qJ/OEW167p0U/bJhEdEH9FME8y+EV0OK2H7hPGM9hLsUKYuy69QvCeNrYUy+C82Ypyk5OV1IjtUwPZW0iuDmkcApnLUuVRH2ZBv6rD4itEBGRZZUOqaSWIzYRG8icO5g5NCK2vEu6j0YSiAFPxw62+sNbh4jl3V5pJInA+Ig+N9dLdGDdyOVLpZPa9vxp9ymWeR6rL6mpjscjT8LqDqGutnSm9KeuKy+p5llE9BWA5i0x0vyQzkuS9eyMWWINPYp/+FQVcpLkXxHrAmPhlzKMVJ6g5AZiL4/lHpITxA7U6hNGcoJY0dZAkhPEd1kXRnKCYsAbLYjkBAmXrKQviJPcIHnWm0mKUZITxDt7JUjZqhZSVXuTzTsbeUj0nRMEjz2EPi7S5wzdB9JJKgYpHF9oGqlpbBwLKFIujgvJEpen/G6S9tICEkft6CA1WuZNIKpbgTizR2QlnRvFClKUi0pCmkMa+Q66SpiDdOugxge66taDdEs18oGAdvmZSZUfdF7E8jquiRFUhng04YELjtlKCggt54jt1GNGkjHZ4LZhhX76ik0TCenlJ0zz3so/Cz22MX0/mEl6Qy63Kzs+9ViyTWxWMJL0jtzvugYKX2ah79tJJKpetNi0XTPeJ5xIEZuqPF9cWSImfQRFcNOsKf2ltNtuUjX8ehyolO4PpzIPiZhnt1RKaTz1m2DlhSqq7jbeiq+eJLOc8HirOUm7dZiUZ9JMiyZBLlJnV8c/KbjkOT/gu8UfEAcMRnJsEalbtd1KRt+8HZ2oQi3CsCsosiYBk22cQv79ItUDHveFIESWfXw6/6sTnJLBJx83CqBjsoejf1xcvMoff4tgARY8d3RqLl59sfmH7Q97YGQsT2rqKAAAAABJRU5ErkJggg==" alt="wave"/></div>
      <strong class="line2"><?php echo osc_is_web_user_logged_in() ? osc_logged_user_name() : __('Welcome!', 'epsilon'); ?></strong>

      <a class="line3" href="<?php echo (osc_is_web_user_logged_in() ? osc_user_profile_url() : osc_user_login_url()); ?>"><?php echo !osc_is_web_user_logged_in() ? __('Sign in or register', 'epsilon') : __('View and edit profile', 'epsilon'); ?></a>
    </div>


    <div class="section delim-top">
      <a href="<?php echo osc_item_post_url(); ?>" class="publish">
        <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M352 240v32c0 6.6-5.4 12-12 12h-88v88c0 6.6-5.4 12-12 12h-32c-6.6 0-12-5.4-12-12v-88h-88c-6.6 0-12-5.4-12-12v-32c0-6.6 5.4-12 12-12h88v-88c0-6.6 5.4-12 12-12h32c6.6 0 12 5.4 12 12v88h88c6.6 0 12 5.4 12 12zm96-160v352c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V80c0-26.5 21.5-48 48-48h352c26.5 0 48 21.5 48 48zm-48 346V86c0-3.3-2.7-6-6-6H54c-3.3 0-6 2.7-6 6v340c0 3.3 2.7 6 6 6h340c3.3 0 6-2.7 6-6z"/></svg>
        <?php _e('Post an ad', 'epsilon'); ?>
      </a>
      
      <a href="<?php echo osc_search_url(array('page' => 'search')); ?>" class="search">
        <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M508.5 468.9L387.1 347.5c-2.3-2.3-5.3-3.5-8.5-3.5h-13.2c31.5-36.5 50.6-84 50.6-136C416 93.1 322.9 0 208 0S0 93.1 0 208s93.1 208 208 208c52 0 99.5-19.1 136-50.6v13.2c0 3.2 1.3 6.2 3.5 8.5l121.4 121.4c4.7 4.7 12.3 4.7 17 0l22.6-22.6c4.7-4.7 4.7-12.3 0-17zM208 368c-88.4 0-160-71.6-160-160S119.6 48 208 48s160 71.6 160 160-71.6 160-160 160z"/></svg>
        <?php _e('Search', 'epsilon'); ?>
      </a>
    </div>
      
    <div class="section delim-top">
      <?php if(!osc_is_web_user_logged_in()) { ?>
        <a href="<?php echo osc_user_login_url(); ?>" class="login">
          <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M144 112v51.6H48c-26.5 0-48 21.5-48 48v88.6c0 26.5 21.5 48 48 48h96v51.6c0 42.6 51.7 64.2 81.9 33.9l144-143.9c18.7-18.7 18.7-49.1 0-67.9l-144-144C195.8 48 144 69.3 144 112zm192 144L192 400v-99.7H48v-88.6h144V112l144 144zm80 192h-84c-6.6 0-12-5.4-12-12v-24c0-6.6 5.4-12 12-12h84c26.5 0 48-21.5 48-48V160c0-26.5-21.5-48-48-48h-84c-6.6 0-12-5.4-12-12V76c0-6.6 5.4-12 12-12h84c53 0 96 43 96 96v192c0 53-43 96-96 96z"/></svg>
          <?php _e('Log in', 'epsilon'); ?>
        </a>
        
        <a href="<?php echo osc_register_account_url(); ?>" class="register">
          <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M224 288c79.5 0 144-64.5 144-144S303.5 0 224 0 80 64.5 80 144s64.5 144 144 144zm0-240c52.9 0 96 43.1 96 96s-43.1 96-96 96-96-43.1-96-96 43.1-96 96-96zm89.6 256c-28.7 0-42.5 16-89.6 16-47.1 0-60.8-16-89.6-16C60.2 304 0 364.2 0 438.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-25.6c0-74.2-60.2-134.4-134.4-134.4zM400 464H48v-25.6c0-47.6 38.8-86.4 86.4-86.4 14.6 0 38.3 16 89.6 16 51.7 0 74.9-16 89.6-16 47.6 0 86.4 38.8 86.4 86.4V464zm224-248h-72v-72c0-8.8-7.2-16-16-16h-16c-8.8 0-16 7.2-16 16v72h-72c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h72v72c0 8.8 7.2 16 16 16h16c8.8 0 16-7.2 16-16v-72h72c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16z"/></svg>
          <?php _e('Register account', 'epsilon'); ?>
        </a>
      <?php } else { ?>
        <a href="<?php echo osc_user_public_profile_url(osc_logged_user_id()); ?>" class="public">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="24" height="24"><path d="M528 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h480c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zm0 400H48V80h480v352zM208 256c35.3 0 64-28.7 64-64s-28.7-64-64-64-64 28.7-64 64 28.7 64 64 64zm-89.6 128h179.2c12.4 0 22.4-8.6 22.4-19.2v-19.2c0-31.8-30.1-57.6-67.2-57.6-10.8 0-18.7 8-44.8 8-26.9 0-33.4-8-44.8-8-37.1 0-67.2 25.8-67.2 57.6v19.2c0 10.6 10 19.2 22.4 19.2zM360 320h112c4.4 0 8-3.6 8-8v-16c0-4.4-3.6-8-8-8H360c-4.4 0-8 3.6-8 8v16c0 4.4 3.6 8 8 8zm0-64h112c4.4 0 8-3.6 8-8v-16c0-4.4-3.6-8-8-8H360c-4.4 0-8 3.6-8 8v16c0 4.4 3.6 8 8 8zm0-64h112c4.4 0 8-3.6 8-8v-16c0-4.4-3.6-8-8-8H360c-4.4 0-8 3.6-8 8v16c0 4.4 3.6 8 8 8z"/></svg>
          <?php _e('Public profile', 'epsilon'); ?>
        </a>

        <a href="<?php echo osc_user_dashboard_url(); ?>" class="dash">
          <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M568 368c-19.1 0-36.3 7.6-49.2 19.7L440.6 343c4.5-12.2 7.4-25.2 7.4-39 0-61.9-50.1-112-112-112-8.4 0-16.6 1.1-24.4 2.9l-32.2-69c15-13.2 24.6-32.3 24.6-53.8 0-39.8-32.2-72-72-72s-72 32.2-72 72 32.2 72 72 72c.9 0 1.8-.2 2.7-.3l33.5 71.7C241.5 235.9 224 267.8 224 304c0 61.9 50.1 112 112 112 30.7 0 58.6-12.4 78.8-32.5l82.2 47c-.4 3.1-1 6.3-1 9.5 0 39.8 32.2 72 72 72s72-32.2 72-72-32.2-72-72-72zM232 96c-13.2 0-24-10.8-24-24s10.8-24 24-24 24 10.8 24 24-10.8 24-24 24zm104 272c-35.3 0-64-28.7-64-64s28.7-64 64-64 64 28.7 64 64-28.7 64-64 64zm232 96c-13.2 0-24-10.8-24-24s10.8-24 24-24 24 10.8 24 24-10.8 24-24 24zm-54.4-261.2l-19.2-25.6-48 36 19.2 25.6 48-36zM576 192c35.3 0 64-28.7 64-64s-28.7-64-64-64-64 28.7-64 64 28.7 64 64 64zM152 320h48v-32h-48v32zm-88-80c-35.3 0-64 28.7-64 64s28.7 64 64 64 64-28.7 64-64-28.7-64-64-64z"/></svg>
          <?php _e('Dashboard', 'epsilon'); ?>
        </a>
        
        <a href="<?php echo eps_user_items_url('active'); ?>" class="items">
          <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M296 400h-80c-4.42 0-8 3.58-8 8v16c0 4.42 3.58 8 8 8h80c4.42 0 8-3.58 8-8v-16c0-4.42-3.58-8-8-8zM80 240v96c0 8.84 7.16 16 16 16h192c8.84 0 16-7.16 16-16v-96c0-8.84-7.16-16-16-16H96c-8.84 0-16 7.16-16 16zm32 16h160v64H112v-64zM369.83 97.98L285.94 14.1c-9-9-21.2-14.1-33.89-14.1H47.99C21.5.1 0 21.6 0 48.09v415.92C0 490.5 21.5 512 47.99 512h287.94c26.5 0 48.07-21.5 48.07-47.99V131.97c0-12.69-5.17-24.99-14.17-33.99zM255.95 51.99l76.09 76.08h-76.09V51.99zM336 464.01H47.99V48.09h159.97v103.98c0 13.3 10.7 23.99 24 23.99H336v287.95zM88 112h80c4.42 0 8-3.58 8-8V88c0-4.42-3.58-8-8-8H88c-4.42 0-8 3.58-8 8v16c0 4.42 3.58 8 8 8zm0 64h80c4.42 0 8-3.58 8-8v-16c0-4.42-3.58-8-8-8H88c-4.42 0-8 3.58-8 8v16c0 4.42 3.58 8 8 8z"/></svg>
          <?php _e('Active listings', 'epsilon'); ?>
          <span class="counter"><?php echo Item::newInstance()->countItemTypesByUserID(osc_logged_user_id(), 'all'); ?></span>
        </a>

        <a href="<?php echo osc_user_alerts_url(); ?>" class="alert">
          <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M459.7 194.6C482 174.1 496 144.7 496 112 496 50.1 445.9 0 384 0c-45.3 0-84.3 26.8-101.9 65.5-17.3-2-34.9-2-52.2 0C212.3 26.8 173.3 0 128 0 66.1 0 16 50.1 16 112c0 32.7 14 62.1 36.3 82.6C39.3 223 32 254.7 32 288c0 53.2 18.6 102.1 49.5 140.5L39 471c-9.4 9.4-9.4 24.6 0 33.9 9.4 9.4 24.6 9.4 33.9 0l42.5-42.5c81.5 65.7 198.7 66.4 281 0L439 505c9.4 9.4 24.6 9.4 33.9 0 9.4-9.4 9.4-24.6 0-33.9l-42.5-42.5c31-38.4 49.5-87.3 49.5-140.5.1-33.4-7.2-65.1-20.2-93.5zM384 48c35.3 0 64 28.7 64 64 0 15.1-5.3 29-14 39.9-26.2-34.2-62-60.6-103.3-75.2C342.1 59.4 361.7 48 384 48zM64 112c0-35.3 28.7-64 64-64 22.3 0 41.9 11.4 53.4 28.7-41.4 14.6-77.2 41-103.3 75.2C69.3 141 64 127.1 64 112zm192 352c-97.3 0-176-78.7-176-176 0-97 78.4-176 176-176 97.4 0 176 78.8 176 176 0 97.3-78.7 176-176 176zm46.2-95.7l-69-47.5c-3.3-2.2-5.2-5.9-5.2-9.9V180c0-6.6 5.4-12 12-12h32c6.6 0 12 5.4 12 12v107.7l50 34.4c5.5 3.8 6.8 11.2 3.1 16.7L319 365.2c-3.8 5.4-11.3 6.8-16.8 3.1z"/></svg>
          <?php _e('Subscriptions', 'epsilon'); ?>
          <span class="counter"><?php echo count(Alerts::newInstance()->findByUser(osc_logged_user_id())); ?></span>
        </a>
        
        <a href="<?php echo osc_user_profile_url(); ?>" class="profile">
          <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M358.9 433.3l-6.8 61c-1.1 10.2 7.5 18.8 17.6 17.6l60.9-6.8 137.9-137.9-71.7-71.7-137.9 137.8zM633 268.9L595.1 231c-9.3-9.3-24.5-9.3-33.8 0l-41.8 41.8 71.8 71.7 41.8-41.8c9.2-9.3 9.2-24.4-.1-33.8zM223.9 288c79.6.1 144.2-64.5 144.1-144.1C367.9 65.6 302.4.1 224.1 0 144.5-.1 79.9 64.5 80 144.1c.1 78.3 65.6 143.8 143.9 143.9zm-4.4-239.9c56.5-2.6 103 43.9 100.4 100.4-2.3 49.2-42.1 89.1-91.4 91.4-56.5 2.6-103-43.9-100.4-100.4 2.3-49.3 42.2-89.1 91.4-91.4zM134.4 352c14.6 0 38.3 16 89.6 16 51.7 0 74.9-16 89.6-16 16.7 0 32.2 5 45.5 13.3l34.4-34.4c-22.4-16.7-49.8-26.9-79.9-26.9-28.7 0-42.5 16-89.6 16-47.1 0-60.8-16-89.6-16C60.2 304 0 364.2 0 438.4V464c0 26.5 21.5 48 48 48h258.3c-3.8-14.6-2.2-20.3.9-48H48v-25.6c0-47.6 38.8-86.4 86.4-86.4z"/></svg>
          <?php _e('My Profile', 'epsilon'); ?>
        </a>
      <?php } ?>
    </div>
    
    <div class="section delim-top">
      <?php if(function_exists('fi_make_favorite')) { ?>
        <a href="<?php echo osc_route_url('favorite-lists'); ?>" class="favorite">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" height="18" width="18"><path d="M287.9 0C297.1 0 305.5 5.25 309.5 13.52L378.1 154.8L531.4 177.5C540.4 178.8 547.8 185.1 550.7 193.7C553.5 202.4 551.2 211.9 544.8 218.2L433.6 328.4L459.9 483.9C461.4 492.9 457.7 502.1 450.2 507.4C442.8 512.7 432.1 513.4 424.9 509.1L287.9 435.9L150.1 509.1C142.9 513.4 133.1 512.7 125.6 507.4C118.2 502.1 114.5 492.9 115.1 483.9L142.2 328.4L31.11 218.2C24.65 211.9 22.36 202.4 25.2 193.7C28.03 185.1 35.5 178.8 44.49 177.5L197.7 154.8L266.3 13.52C270.4 5.249 278.7 0 287.9 0L287.9 0zM287.9 78.95L235.4 187.2C231.9 194.3 225.1 199.3 217.3 200.5L98.98 217.9L184.9 303C190.4 308.5 192.9 316.4 191.6 324.1L171.4 443.7L276.6 387.5C283.7 383.7 292.2 383.7 299.2 387.5L404.4 443.7L384.2 324.1C382.9 316.4 385.5 308.5 391 303L476.9 217.9L358.6 200.5C350.7 199.3 343.9 194.3 340.5 187.2L287.9 78.95z"/></svg>
          <?php _e('Favorite listings', 'epsilon'); ?>

          <?php if($fav_counter > 0) { ?>
            <span class="counter"><?php echo $fav_counter; ?></span>
          <?php } ?> 
        </a>
      <?php } ?>

      <?php if(osc_is_web_user_logged_in()) { ?>
        <?php if(function_exists('im_messages')) { ?>
          <a href="<?php echo osc_route_url('im-threads'); ?>" class="messenger">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="24" height="24"><path d="M448 0H64C28.7 0 0 28.7 0 64v288c0 35.3 28.7 64 64 64h96v84c0 7.1 5.8 12 12 12 2.4 0 4.9-.7 7.1-2.4L304 416h144c35.3 0 64-28.7 64-64V64c0-35.3-28.7-64-64-64zm16 352c0 8.8-7.2 16-16 16H288l-12.8 9.6L208 428v-60H64c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16h384c8.8 0 16 7.2 16 16v288zm-96-216H144c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h224c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16zm-96 96H144c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h128c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16z"/></svg>
            <?php _e('Messages', 'epsilon'); ?>

            <?php if($mes_counter > 0) { ?>
              <span class="counter"><?php echo $mes_counter; ?></span>
            <?php } ?> 
          </a>
        <?php } ?>

        <?php if(function_exists('osp_user_sidebar')) { ?>
          <a href="<?php echo osc_route_url('osp-item'); ?>" class="pay">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="24" height="24"><path d="M168 296h-16c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h16c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16zm-32-48c0-8.8-7.2-16-16-16h-16c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h16c8.8 0 16-7.2 16-16v-16zm96 0c0-8.8-7.2-16-16-16h-16c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h16c8.8 0 16-7.2 16-16v-16zm128 48h-16c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h16c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16zm48-64h-16c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h16c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16zm103.4 147.5l-25.5-178.3c-3.4-23.6-23.6-41.2-47.5-41.2H208v-32h96c8.8 0 16-7.2 16-16V16c0-8.8-7.2-16-16-16H48c-8.8 0-16 7.2-16 16v96c0 8.8 7.2 16 16 16h96v32H73.6c-23.9 0-44.1 17.6-47.5 41.2L.6 379.5c-.4 3-.6 6-.6 9.1V464c0 26.5 21.5 48 48 48h416c26.5 0 48-21.5 48-48v-75.5c0-3-.2-6-.6-9zM80 80V48h192v32H80zm-6.4 128h364.7l22.9 160H50.8l22.8-160zM464 464H48v-48h416v48zM328 248c0-8.8-7.2-16-16-16h-16c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h16c8.8 0 16-7.2 16-16v-16zm-64 48h-16c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h16c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16z"/></svg>
            <?php _e('Promotions', 'epsilon'); ?>
          </a>
        <?php } ?>

        <?php if(function_exists('bpr_companies_url')) { ?>
          <a class="company" href="<?php echo bpr_companies_url(); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="24" height="24"><path d="M464 128h-80V80c0-26.51-21.49-48-48-48H176c-26.51 0-48 21.49-48 48v48H48c-26.51 0-48 21.49-48 48v256c0 26.51 21.49 48 48 48h416c26.51 0 48-21.49 48-48V176c0-26.51-21.49-48-48-48zM176 80h160v48H176V80zM54 176h404c3.31 0 6 2.69 6 6v74H48v-74c0-3.31 2.69-6 6-6zm404 256H54c-3.31 0-6-2.69-6-6V304h144v24c0 13.25 10.75 24 24 24h80c13.25 0 24-10.75 24-24v-24h144v122c0 3.31-2.69 6-6 6z"/></svg>
            <?php _e('Companies', 'epsilon'); ?>
          </a>
        <?php } ?>
        
        <?php if(function_exists('bpr_companies_url')) { ?>
          <a class="your-business-profile" href="<?php echo osc_route_url('bpr-profile'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="24" height="24"><path d="M528 32H48C21.5 32 0 53.5 0 80v16h576V80c0-26.5-21.5-48-48-48zM0 432c0 26.5 21.5 48 48 48h480c26.5 0 48-21.5 48-48V128H0v304zm352-232c0-4.4 3.6-8 8-8h144c4.4 0 8 3.6 8 8v16c0 4.4-3.6 8-8 8H360c-4.4 0-8-3.6-8-8v-16zm0 64c0-4.4 3.6-8 8-8h144c4.4 0 8 3.6 8 8v16c0 4.4-3.6 8-8 8H360c-4.4 0-8-3.6-8-8v-16zm0 64c0-4.4 3.6-8 8-8h144c4.4 0 8 3.6 8 8v16c0 4.4-3.6 8-8 8H360c-4.4 0-8-3.6-8-8v-16zM176 192c35.3 0 64 28.7 64 64s-28.7 64-64 64-64-28.7-64-64 28.7-64 64-64zM67.1 396.2C75.5 370.5 99.6 352 128 352h8.2c12.3 5.1 25.7 8 39.8 8s27.6-2.9 39.8-8h8.2c28.4 0 52.5 18.5 60.9 44.2 3.2 9.9-5.2 19.8-15.6 19.8H82.7c-10.4 0-18.8-10-15.6-19.8z"/></svg>
            <?php _e('Your business profile', 'epsilon'); ?>
          </a>
        <?php } ?>
        
        <?php if(function_exists('frm_home')) { ?>
          <a class="forum" href="<?php echo frm_home(); ?>">
            <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M512 160h-96V64c0-35.3-28.7-64-64-64H64C28.7 0 0 28.7 0 64v160c0 35.3 28.7 64 64 64h32v52c0 7.1 5.8 12 12 12 2.4 0 4.9-.7 7.1-2.4l76.9-43.5V384c0 35.3 28.7 64 64 64h96l108.9 61.6c2.2 1.6 4.7 2.4 7.1 2.4 6.2 0 12-4.9 12-12v-52h32c35.3 0 64-28.7 64-64V224c0-35.3-28.7-64-64-64zM96 240H64c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16h288c8.8 0 16 7.2 16 16v160c0 8.8-7.2 16-16 16H211.4l-11 6.2-56.4 31.9V240H96zm432 144c0 8.8-7.2 16-16 16h-80v38.1l-56.4-31.9-11-6.2H256c-8.8 0-16-7.2-16-16v-96h112c35.3 0 64-28.7 64-64v-16h96c8.8 0 16 7.2 16 16v160z"/></svg>
            <?php _e('Forums', 'epsilon'); ?>
          </a>
        <?php } ?>
      
        <?php if(function_exists('blg_home_link')) { ?>
          <a class="blog" href="<?php echo blg_home_link(); ?>">
            <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M448 0H64C28.7 0 0 28.7 0 64v288c0 35.3 28.7 64 64 64h96v84c0 7.1 5.8 12 12 12 2.4 0 4.9-.7 7.1-2.4L304 416h144c35.3 0 64-28.7 64-64V64c0-35.3-28.7-64-64-64zm16 352c0 8.8-7.2 16-16 16H288l-12.8 9.6L208 428v-60H64c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16h384c8.8 0 16 7.2 16 16v288zM164.9 243.2l-4.8 42.8c-.6 5.7 4.2 10.6 10 10l42.8-4.8 85.5-85.5-48-48-85.5 85.5zm159.3-133.9c-7-7-18.4-7-25.4 0l-28.3 28.3 48 48 28.3-28.3c7-7 7-18.4 0-25.4l-22.6-22.6z"/></svg>
            <?php _e('Blog', 'epsilon'); ?>
          </a>
        <?php } ?>
       
        <?php if(function_exists('faq_home_link')) { ?>
          <a class="faq" href="<?php echo faq_home_link(); ?>">
            <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M199.65 0C125.625 0 69.665 30.187 27.21 92.51c-19.17 28.15-12.94 66.3 14.17 86.86l36.73 27.85c10.81 8.2 24.19 12.79 37.74 12.96-11.84 19-17.82 40.61-17.82 64.55v11.43c0 16.38 6.2 31.34 16.38 42.65C97.99 357.2 88 381.45 88 408c0 57.35 46.65 104 104 104s104-46.65 104-104c0-26.55-9.99-50.8-26.41-69.19 8.66-9.62 14.43-21.87 15.97-35.38 28.287-16.853 96-48.895 96-138.21C381.56 71.151 290.539 0 199.65 0zM192 464c-30.88 0-56-25.12-56-56 0-30.873 25.118-56 56-56 30.887 0 56 25.132 56 56 0 30.88-25.12 56-56 56zm45.97-176.21v8.37c0 8.788-7.131 15.84-15.84 15.84h-60.26c-8.708 0-15.84-7.051-15.84-15.84v-11.43c0-47.18 35.77-66.04 62.81-81.2 23.18-13 37.39-21.83 37.39-39.04 0-22.77-29.04-37.88-52.52-37.88-30.61 0-44.74 14.49-64.6 39.56-5.365 6.771-15.157 8.01-22 2.8l-36.73-27.85c-6.74-5.11-8.25-14.6-3.49-21.59C98.08 73.73 137.8 48 199.65 48c64.77 0 133.91 50.56 133.91 117.22 0 88.51-95.59 89.87-95.59 122.57z"/></svg>
            <?php _e('FAQ', 'epsilon'); ?>
          </a>
        <?php } ?>
      
        <div class="menu-hooks">
          <?php eps_user_menu_side(); ?>
        </div>
      <?php } ?>
    </div>

    <div class="section delim-top">
      <?php if(osc_count_web_enabled_locales() > 1) { ?>
        <a href="#" class="open-box language" data-box="language">
          <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512"><path d="M248 8C111 8 0 119 0 256s111 248 248 248 248-111 248-248S385 8 248 8zm-32 50.8v11.3c0 11.9-12.5 19.6-23.2 14.3l-24-12c14.9-6.4 30.7-10.9 47.2-13.6zm32 369.8V456c-110.3 0-200-89.7-200-200 0-29.1 6.4-56.7 17.6-81.7 9.9 14.7 25.2 37.4 34.6 51.1 5.2 7.6 11.2 14.6 18.1 20.7l.8.7c9.5 8.6 20.2 16 31.6 21.8 14 7 34.4 18.2 48.8 26.1 10.2 5.6 16.5 16.3 16.5 28v32c0 8.5 3.4 16.6 9.4 22.6 15 15.1 24.3 38.7 22.6 51.3zm42.7 22.7l17.4-46.9c2-5.5 3.3-11.2 4.8-16.9 1.1-4 3.2-7.7 6.2-10.7l11.3-11.3c8.8-8.7 13.7-20.6 13.7-33 0-8.1-3.2-15.9-8.9-21.6l-13.7-13.7c-6-6-14.1-9.4-22.6-9.4H232c-9.4-4.7-21.5-32-32-32s-20.9-2.5-30.3-7.2l-11.1-5.5c-4-2-6.6-6.2-6.6-10.7 0-5.1 3.3-9.7 8.2-11.3l31.2-10.4c5.4-1.8 11.3-.6 15.5 3.1l9.3 8.1c1.5 1.3 3.3 2 5.2 2h5.6c6 0 9.8-6.3 7.2-11.6l-15.6-31.2c-1.6-3.1-.9-6.9 1.6-9.3l9.9-9.6c1.5-1.5 3.5-2.3 5.6-2.3h9c2.1 0 4.2-.8 5.7-2.3l8-8c3.1-3.1 3.1-8.2 0-11.3l-4.7-4.7c-3.1-3.1-3.1-8.2 0-11.3L264 112l4.7-4.7c6.2-6.2 6.2-16.4 0-22.6l-28.3-28.3c2.5-.1 5-.4 7.6-.4 78.2 0 145.8 45.2 178.7 110.7l-13 6.5c-3.7 1.9-6.9 4.7-9.2 8.1l-19.6 29.4c-5.4 8.1-5.4 18.6 0 26.6l18 27c3.3 5 8.4 8.5 14.1 10l29.2 7.3c-10.8 84-73.9 151.9-155.5 169.7z"/></svg>
          <?php _e('Select language', 'epsilon'); ?>
          <?php echo $indicator; ?>
        </a>
      <?php } ?>
      
      <?php if(eps_param('default_location') == 1) { ?>
        <a href="#" class="open-box location" data-box="location">
          <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512"><path d="M347.94 129.86L203.6 195.83a31.938 31.938 0 0 0-15.77 15.77l-65.97 144.34c-7.61 16.65 9.54 33.81 26.2 26.2l144.34-65.97a31.938 31.938 0 0 0 15.77-15.77l65.97-144.34c7.61-16.66-9.54-33.81-26.2-26.2zm-77.36 148.72c-12.47 12.47-32.69 12.47-45.16 0-12.47-12.47-12.47-32.69 0-45.16 12.47-12.47 32.69-12.47 45.16 0 12.47 12.47 12.47 32.69 0 45.16zM248 8C111.03 8 0 119.03 0 256s111.03 248 248 248 248-111.03 248-248S384.97 8 248 8zm0 448c-110.28 0-200-89.72-200-200S137.72 56 248 56s200 89.72 200 200-89.72 200-200 200z"/></svg>
          <?php _e('Change location', 'epsilon'); ?>
          <?php echo $indicator; ?>
        </a>
      <?php } ?>
      
      <a href="#" class="open-box pages" data-box="pages">
        <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 8C119.043 8 8 119.083 8 256c0 136.997 111.043 248 248 248s248-111.003 248-248C504 119.083 392.957 8 256 8zm0 448c-110.532 0-200-89.431-200-200 0-110.495 89.472-200 200-200 110.491 0 200 89.471 200 200 0 110.53-89.431 200-200 200zm0-338c23.196 0 42 18.804 42 42s-18.804 42-42 42-42-18.804-42-42 18.804-42 42-42zm56 254c0 6.627-5.373 12-12 12h-88c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h12v-64h-12c-6.627 0-12-5.373-12-12v-24c0-6.627 5.373-12 12-12h64c6.627 0 12 5.373 12 12v100h12c6.627 0 12 5.373 12 12v24z"/></svg>
        <?php _e('Help', 'epsilon'); ?>
        <?php echo $indicator; ?>
      </a>
    </div>

    <?php if(eps_param('enable_dark_mode') == 1) { ?>
      <div class="section delim-top dark-mode">
        <a href="#" class="disable-dark-mode" <?php if(@$_COOKIE['epsDarkMode'] == 'disable') { ?>style="display:none;"<?php } ?>>
          <svg width="24" height="24"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M494.2 221.9l-59.8-40.5 13.7-71c2.6-13.2-1.6-26.8-11.1-36.4-9.6-9.5-23.2-13.7-36.2-11.1l-70.9 13.7-40.4-59.9c-15.1-22.3-51.9-22.3-67 0l-40.4 59.9-70.8-13.7C98 60.4 84.5 64.5 75 74.1c-9.5 9.6-13.7 23.1-11.1 36.3l13.7 71-59.8 40.5C6.6 229.5 0 242 0 255.5s6.7 26 17.8 33.5l59.8 40.5-13.7 71c-2.6 13.2 1.6 26.8 11.1 36.3 9.5 9.5 22.9 13.7 36.3 11.1l70.8-13.7 40.4 59.9C230 505.3 242.6 512 256 512s26-6.7 33.5-17.8l40.4-59.9 70.9 13.7c13.4 2.7 26.8-1.6 36.3-11.1 9.5-9.5 13.6-23.1 11.1-36.3l-13.7-71 59.8-40.5c11.1-7.5 17.8-20.1 17.8-33.5-.1-13.6-6.7-26.1-17.9-33.7zm-112.9 85.6l17.6 91.2-91-17.6L256 458l-51.9-77-90.9 17.6 17.6-91.2-76.8-52 76.8-52-17.6-91.2 91 17.6L256 53l51.9 76.9 91-17.6-17.6 91.1 76.8 52-76.8 52.1zM256 152c-57.3 0-104 46.7-104 104s46.7 104 104 104 104-46.7 104-104-46.7-104-104-104zm0 160c-30.9 0-56-25.1-56-56s25.1-56 56-56 56 25.1 56 56-25.1 56-56 56z"/></svg>
          <?php _e('Disable dark mode', 'epsilon'); ?>
        </a>
        
        <a href="#" class="enable-dark-mode" <?php if(@$_COOKIE['epsDarkMode'] != 'disable') { ?>style="display:none;"<?php } ?>>
          <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M279.135 512c78.756 0 150.982-35.804 198.844-94.775 28.27-34.831-2.558-85.722-46.249-77.401-82.348 15.683-158.272-47.268-158.272-130.792 0-48.424 26.06-92.292 67.434-115.836 38.745-22.05 28.999-80.788-15.022-88.919A257.936 257.936 0 0 0 279.135 0c-141.36 0-256 114.575-256 256 0 141.36 114.576 256 256 256zm0-464c12.985 0 25.689 1.201 38.016 3.478-54.76 31.163-91.693 90.042-91.693 157.554 0 113.848 103.641 199.2 215.252 177.944C402.574 433.964 344.366 464 279.135 464c-114.875 0-208-93.125-208-208s93.125-208 208-208z"/></svg>
          <?php _e('Enable dark mode', 'epsilon'); ?>
        </a>
      </div>
    <?php } ?>
    
    <?php if(osc_is_web_user_logged_in()) { ?>
      <div class="section delim-top">
        <a class="logout" href="<?php echo osc_user_logout_url(); ?>">
          <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M272 112v51.6h-96c-26.5 0-48 21.5-48 48v88.6c0 26.5 21.5 48 48 48h96v51.6c0 42.6 51.7 64.2 81.9 33.9l144-143.9c18.7-18.7 18.7-49.1 0-67.9l-144-144C323.8 48 272 69.3 272 112zm192 144L320 400v-99.7H176v-88.6h144V112l144 144zM96 64h84c6.6 0 12 5.4 12 12v24c0 6.6-5.4 12-12 12H96c-26.5 0-48 21.5-48 48v192c0 26.5 21.5 48 48 48h84c6.6 0 12 5.4 12 12v24c0 6.6-5.4 12-12 12H96c-53 0-96-43-96-96V160c0-53 43-96 96-96z"/></svg>
          <?php _e('Log out', 'epsilon'); ?>
        </a>
      </div>
    <?php } ?>
  </div>
  
  <div class="box pages" data-box="pages">
    <div class="nav">
      <a href="#" class="back"><i class="fas fa-chevron-left"></i></a>
      <span><?php _e('Support pages', 'epsilon'); ?></span>
    </div>
    
    <?php osc_reset_static_pages(); ?>
   
    <div class="section">
      <?php while(osc_has_static_pages()) { ?>
        <a href="<?php echo osc_static_page_url(); ?>"><?php echo osc_static_page_title();?></a>
      <?php } ?>
    </div>
  </div>
  
  <?php if(eps_param('default_location') == 1) { ?>
    <div class="box location" data-box="location">
      <div class="nav">
        <a href="#" class="back"><i class="fas fa-chevron-left"></i></a>
        <span><?php _e('Change location', 'epsilon'); ?></span>
      </div>
    
      <div class="section">
        <div class="head isDesktop isTablet"><?php _e('Default location', 'epsilon'); ?></div>
        <div class="subhead isDesktop isTablet"><?php _e('Select your preferred location to search and sell faster.', 'epsilon'); ?></div>

        <?php if(@$location_cookie['s_location'] <> '') { ?>
          <div class="row current">
            <strong><?php _e('Your location', 'epsilon'); ?>:</strong> <?php echo $location_cookie['s_location']; ?>
          </div>
        <?php } ?>
          
        <div class="row picker">
          <div class="input-box picker location">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M508.5 468.9L387.1 347.5c-2.3-2.3-5.3-3.5-8.5-3.5h-13.2c31.5-36.5 50.6-84 50.6-136C416 93.1 322.9 0 208 0S0 93.1 0 208s93.1 208 208 208c52 0 99.5-19.1 136-50.6v13.2c0 3.2 1.3 6.2 3.5 8.5l121.4 121.4c4.7 4.7 12.3 4.7 17 0l22.6-22.6c4.7-4.7 4.7-12.3 0-17zM208 368c-88.4 0-160-71.6-160-160S119.6 48 208 48s160 71.6 160 160-71.6 160-160 160z"/></svg>
            <input name="location-pick" class="location-pick" type="text" placeholder="<?php echo osc_esc_html(__('Search location...', 'epsilon')); ?>" autocomplete="off"/>
            <i class="clean fas fa-times-circle"></i>
            <div class="results"></div>
          </div>
        </div>
        
        <div class="row navigator">
          <a href="#" class="locate-me">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 168c-48.6 0-88 39.4-88 88s39.4 88 88 88 88-39.4 88-88-39.4-88-88-88zm0 128c-22.06 0-40-17.94-40-40s17.94-40 40-40 40 17.94 40 40-17.94 40-40 40zm240-64h-49.66C435.49 145.19 366.81 76.51 280 65.66V16c0-8.84-7.16-16-16-16h-16c-8.84 0-16 7.16-16 16v49.66C145.19 76.51 76.51 145.19 65.66 232H16c-8.84 0-16 7.16-16 16v16c0 8.84 7.16 16 16 16h49.66C76.51 366.81 145.19 435.49 232 446.34V496c0 8.84 7.16 16 16 16h16c8.84 0 16-7.16 16-16v-49.66C366.81 435.49 435.49 366.8 446.34 280H496c8.84 0 16-7.16 16-16v-16c0-8.84-7.16-16-16-16zM256 400c-79.4 0-144-64.6-144-144s64.6-144 144-144 144 64.6 144 144-64.6 144-144 144z"/></svg>
            <strong data-alt-text="<?php echo osc_esc_html(__('Click to refresh', 'epsilon')); ?>"><?php _e('Use current location', 'epsilon'); ?></strong>
            <span class="status">
              <span class="init"><?php _e('Click to find closest city to your location', 'epsilon'); ?></span>
              <span class="not-supported" style="display:none;"><?php _e('Geolocation is not supported by your browser', 'epsilon'); ?></span>
              <span class="failed" style="display:none;"><?php _e('Unable to retrieve your location, it may be blocked', 'epsilon'); ?></span>
              <span class="failed-unfound" style="display:none;"><?php _e('Unable to retrieve your location, no close city found', 'epsilon'); ?></span>
              <span class="loading" style="display:none;"><?php _e('Locating...', 'epsilon'); ?></span>
              <span class="success" style="display:none;"></span>
              <span class="refresh" style="display:none;"><?php _e('Refresh page to take effect', 'epsilon'); ?></span>
            </span>
          </a>
        </div>
        
        <?php $recent = array_reverse(eps_get_recent_locations());?>
        
        <?php if(is_array($recent) && count($recent) > 0) { ?>
          <div class="row recent">
            <div class="lead"><?php _e('Recent locations', 'epsilon'); ?></div>

            <?php foreach($recent as $p) { ?>
              <?php $hash = rawurlencode(base64_encode(json_encode(array('fk_i_city_id' => @$p['fk_i_city_id'], 'fk_i_region_id' => @$p['fk_i_region_id'], 'fk_c_country_code' => @$p['fk_c_country_code'], 's_name' => @$p['s_name'], 's_name_native' => @$p['s_name_native'], 's_name_top' => @$p['s_name_top'], 's_name_top_native' => @$p['s_name_top_native'], 'd_coord_lat' => @$p['d_coord_lat'], 'd_coord_long' => @$p['d_coord_long'])))); ?>
              <a href="<?php echo eps_create_url(array('manualCookieLocation' => 1, 'hash' => $hash)); ?>" class="location-elem"><?php echo osc_location_native_name_selector($p, 's_name'); ?></a>
            <?php } ?>
          </div>
        <?php } ?>
        
        <?php
          $cities = function_exists('pngm_get_popular_cities')
            ? pngm_get_popular_cities(7)
            : ModelEPS::newInstance()->getPopularCities(7, 0);
        ?>

        <?php if(is_array($cities) && count($cities) > 0) { ?>
          <div class="row popular">
            <div class="lead"><?php _e('Main cities', 'epsilon'); ?></div>

            <?php foreach($cities as $c) { ?>
              <?php $hash = rawurlencode(base64_encode(json_encode(array('fk_i_city_id' => $c['fk_i_city_id'], 'fk_i_region_id' => $c['fk_i_region_id'], 'fk_c_country_code' => $c['fk_c_country_code'], 's_name' => $c['s_name'], 's_name_native' => @$c['s_name_native'], 's_name_top' => @$c['s_name_top'], 's_name_top_native' => @$c['s_name_top_native'], 'd_coord_lat' => @$c['d_coord_lat'], 'd_coord_long' => @$c['d_coord_long'])))); ?>
              <a href="<?php echo eps_create_url(array('manualCookieLocation' => 1, 'hash' => $hash)); ?>" class="location-elem"><?php echo osc_location_native_name_selector($c, 's_name') . (osc_location_native_name_selector($c, 's_name_top') <> '' ? ', ' . osc_location_native_name_selector($c, 's_name_top') : '') . ($c['i_num_items'] > 0 ? ' <em>' . $c['i_num_items'] . ' ' . ($c['i_num_items'] == 1 ? __('item', 'epsilon') : __('items', 'epsilon')) . '</em>' : ''); ?></a>
            <?php } ?>
          </div>
        <?php } ?>
        
        <div class="row buttons">
          <a class="btn btn-secondary" href="<?php echo eps_create_url(array('cleanCookieLocation' => 1)); ?>"><?php _e('Clean default location', 'epsilon'); ?></a>
        </div>
      </div>
    </div>
  <?php } ?>

  <?php if(osc_count_web_enabled_locales() > 1) { ?>
    <div class="box language" data-box="language">
      <div class="nav">
        <a href="#" class="back"><i class="fas fa-chevron-left"></i></a>
        <span><?php _e('Select language', 'epsilon'); ?></span>
      </div>
      
      <?php osc_goto_first_locale(); ?>

      <div class="section">
        <?php while(osc_has_web_enabled_locales()) { ?>
          <a class="lang <?php if (osc_locale_code() == osc_current_user_locale()) { ?>active<?php } ?>" href="<?php echo osc_change_language_url(osc_locale_code()); ?>">
            <img src="<?php echo eps_country_flag_image(strtolower(substr(osc_locale_code(), 3))); ?>" alt="<?php echo osc_esc_html(__('Country flag', 'epsilon')); ?>" />
            <span><?php echo osc_locale_name(); ?>&#x200E;</span>
          </a>
        <?php } ?>
      </div>
    </div>
  <?php } ?>
  
  <div class="box filter" data-box="filter">
    <div class="nav">
      <a href="#" class="back close"><i class="fas fa-chevron-left"></i></a>
      <span><?php _e('Filter results', 'epsilon'); ?></span>
    </div>
  
    <div class="section filter-menu"></div>
  </div>
</div>

<?php if(eps_is_demo()) { ?>
  <a id="showcase-button" href="#" class="isMobile">
    <i class="fas fa-cogs"></i>
    <span><?php _e('CONFIG', 'epsilon'); ?></span>
  </a>
  
  <div id="showcase-box">
    <div class="container nice-scroll no-visible-scroll">
      <a target="_blank" href="<?php echo osc_admin_render_theme_url('oc-content/themes/epsilon/admin/configure.php'); ?>"><?php _e('Backoffice', 'epsilon'); ?></a>
      <a href="#" class="show-banners" data-alt-text="<?php echo osc_esc_html(__('Hide Banners', 'epsilon')); ?>"><?php _e('Show Banners', 'epsilon'); ?></a>

      <div class="switch-color">
        <?php $colors = array('3b49df','3bdfc0','ad3bdf','df3b54','5dcd0b','d99f0c','171717','c3c400'); ?>
        <?php foreach($colors as $c) { ?>
          <a href="<?php echo osc_base_url(true); ?>?setCustomColor=1&customColor=<?php echo $c; ?>" style="background-color:#<?php echo $c; ?>;" class="<?php if(str_replace('#', '', eps_get_theme_color()) == $c || eps_get_theme_color() == '' && $c == '3b49df') { ?> active<?php } ?>"></a>
        <?php } ?>
      </div>
    </div>
  </div>
<?php } ?>

<?php if (osc_is_admin_user_logged_in() && ((defined('OSC_DEBUG') && OSC_DEBUG == true) || (defined('OSC_DEBUG_DB') && OSC_DEBUG_DB == true))) { ?>
  <div id="debug-mode" class="noselect"><?php _e('Debug mode enabled in config.php.', 'epsilon'); ?></div>
<?php } ?>

<style>
a.fi_img-link.fi-no-image > img {content:url("<?php echo osc_base_url(); ?>/oc-content/themes/epsilon/images/no-image.png");}
</style>