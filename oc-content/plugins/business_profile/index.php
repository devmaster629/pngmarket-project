<?php
/*
  Plugin Name: Business Profile Plugin
  Plugin URI: https://osclasspoint.com/osclass-plugins/design-and-appearance/business-profile-osclass-plugin-i89
  Description: Turns oslcass into business directory allowing sellers to have own profile page and increase their engagement on your classifieds.
  Version: 1.9.8
  Author: MB Themes
  Author URI: https://osclasspoint.com
  Author Email: info@osclasspoint.com
  Short Name: business_profile
  Plugin update URI: business_profile
  Support URI: https://forums.osclasspoint.com/business-profile-plugin/
  Product Key: VnwC5tcwCuXffWIOZiEZ
*/


define('BPR_VERSION_ID', 105);        // Version of DB state
define('BPR_SOCIALS', 'fb;tw;vm;yt;li;ig;pn;ot;');


require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'model/ModelBPR.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'functions.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'email.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'sitemap.php';


osc_enqueue_style('bpr-user-style', osc_base_url() . 'oc-content/plugins/business_profile/css/user.css?v=' . date('YmdHis'));
osc_enqueue_style('tipped', osc_base_url() . 'oc-content/plugins/business_profile/css/tipped.css');

osc_register_script('bpr-user', osc_base_url() . 'oc-content/plugins/business_profile/js/user.js?v=' . date('YmdHis'), array('jquery'));
osc_register_script('tipped', osc_base_url() . 'oc-content/plugins/business_profile/js/tipped.js', 'jquery');

osc_enqueue_script('bpr-user');
osc_enqueue_script('tipped');



osc_add_route('bpr-list-filter', 'companies/(.+)/', 'companies/{iPage}/', osc_plugin_folder(__FILE__).'form/home.php', false, 'custom', 'bpr-home', __('Companies', 'business_profile'));
osc_add_route('bpr-list', 'companies', 'companies', osc_plugin_folder(__FILE__).'form/home.php', false, 'custom', 'bpr-home', __('Companies', 'business_profile'));
// osc_add_route('bpr-list-filter', 'browse-companies/(.+)', 'browse-companies/{params}', osc_plugin_folder(__FILE__).'form/home.php', false, 'custom', 'bpr-home', __('Companies', 'business_profile'));
osc_add_route('bpr-seller', 'company/(.+)', 'company/{identifier}', osc_plugin_folder(__FILE__).'form/seller.php', false, 'custom', 'bpr-seller', __('Company profile', 'business_profile'));
osc_add_route('bpr-seller-filter', 'company-filter/(.+)/(.+)', 'company-filter/{identifier}/{params}', osc_plugin_folder(__FILE__).'form/seller.php', false, 'custom', 'bpr-seller', __('Business profile', 'business_profile'));
osc_add_route('bpr-profile-remove-gallery-img', 'user/business-profile/remove-gallery-image/(.+)', 'user/business-profile/remove-gallery-image/{removeGalleryImage}', osc_plugin_folder(__FILE__).'user/profile.php', true, 'custom', 'bpr-profile', __('Business profile', 'business_profile'));
osc_add_route('bpr-profile-remove-img', 'user/business-profile/remove-image/(.+)', 'user/business-profile/remove-image/{removeImageType}', osc_plugin_folder(__FILE__).'user/profile.php', true, 'custom', 'bpr-profile', __('Business profile', 'business_profile'));
osc_add_route('bpr-profile-remove', 'user/business-profile/remove', 'user/business-profile/remove', osc_plugin_folder(__FILE__).'user/profile.php', true, 'custom', 'bpr-profile', __('Business profile', 'business_profile'));
osc_add_route('bpr-profile', 'user/business-profile', 'user/business-profile', osc_plugin_folder(__FILE__).'user/profile.php', true, 'custom', 'bpr-profile', __('Business profile', 'business_profile'));


function bpr_extra_scripts() {
  if(in_array(osc_get_osclass_section(), array('bpr-seller', 'bpr-profile')) || in_array(Params::getParam('route'), array('bpr-seller', 'bpr-profile'))) {
    osc_enqueue_style('lightgallery', 'https://cdnjs.cloudflare.com/ajax/libs/lightgallery/1.10.0/css/lightgallery.min.css');
    osc_register_script('lightgallery', 'https://cdnjs.cloudflare.com/ajax/libs/lightgallery/1.10.0/js/lightgallery-all.min.js');
    osc_enqueue_script('lightgallery');
  }
}

osc_add_hook('header', 'bpr_extra_scripts');



// ADD NOTIFICATION TO ADMIN TOOLBAR MENU
function bpr_admin_toolbar_profiles(){
  if( !osc_is_moderator() ) {
    $total = ModelBPR::newInstance()->countSellers(0);

    if($total > 0) {
      $title = '<i class="circle circle-red">'.$total.'</i>' . ($total == 1 ? __('Profile', 'blog') : __('Profiles', 'blog'));
      AdminToolbar::newInstance()->add_menu(
        array(
          'id' => 'bpr_unvalidated',
          'title' => $title,
          'href'  => osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/profiles.php',
          'meta'  => array('class' => 'action-btn action-btn-black')
        )
      );
    }
  }
}

osc_add_hook( 'add_admin_toolbar_menus', 'bpr_admin_toolbar_profiles', 1 );


// INSTALL FUNCTION - DEFINE VARIABLES
function bpr_call_after_install() {
  osc_set_preference('features', 'Free Wi-Fi;Parking;Coffe;Credit card;Near metro;Non-smoking;', 'plugin-business_profile', 'STRING');
  osc_set_preference('payments', 'Cash;Credit card;Cash on delivery;Paypal;Bank transfer;', 'plugin-business_profile', 'STRING');
  osc_set_preference('per_page', 24, 'plugin-business_profile', 'INTEGER');
  osc_set_preference('comp_per_page', 24, 'plugin-business_profile', 'INTEGER');
  osc_set_preference('require_validation', 1, 'plugin-business_profile', 'INTEGER');
  osc_set_preference('auto_validation', 1, 'plugin-business_profile', 'INTEGER');
  osc_set_preference('hook_header_links', 0, 'plugin-business_profile', 'INTEGER');
  osc_set_preference('selectors', '#header-bar,#footer-partner,#footer,#top-bar,#footer-contact,#header', 'plugin-business_profile', 'STRING');
  osc_set_preference('premium_groups', '', 'plugin-business_profile', 'STRING');
  osc_set_preference('phone_only_logged', 0, 'plugin-business_profile', 'INTEGER');
  osc_set_preference('mask_phone', 1, 'plugin-business_profile', 'INTEGER');
  osc_set_preference('gallery', 1, 'plugin-business_profile', 'INTEGER');
  osc_set_preference('gallery_limit', 10, 'plugin-business_profile', 'INTEGER');
  osc_set_preference('only_company_users', 0, 'plugin-business_profile', 'INTEGER');
  osc_set_preference('user_can_remove_profile', 0, 'plugin-business_profile', 'INTEGER');
  osc_set_preference('video', 1, 'plugin-business_profile', 'INTEGER');
  osc_set_preference('video_limit', 4, 'plugin-business_profile', 'INTEGER');
  osc_set_preference('video_url', 'https://www.youtube.com/', 'plugin-business_profile', 'STRING');
  osc_set_preference('legal_notice', 1, 'plugin-business_profile', 'INTEGER');
  osc_set_preference('video_layout', '', 'plugin-business_profile', 'STRING');
  osc_set_preference('apply_subdomain_filter', 1, 'plugin-business_profile', 'INTEGER');

  osc_set_preference('version', BPR_VERSION_ID, 'plugin-business_profile', 'INTEGER');

  ModelBPR::newInstance()->install();
}



// AUTOMATIC PLUGIN UPDATE
// Version ID is number greater than 100 and reference to "version of database state" for plugin
function bpr_install_plugin_update() {
  $plugin = 'business_profile';
  
  if(!in_array(Params::getParam('action'), array('widget','add_post','add','enable','disable','install','uninstall')) && !in_array(Params::getParam('page'), array('ajax','login','market','upgrade','appearance'))) { 
    $installed_version = (int)bpr_param('version');
    $current_version = (int)BPR_VERSION_ID;
    
    if($installed_version > 0 && $current_version > $installed_version) {
      $ignore_error = (Params::getParam('forceupdateplugin') == $plugin ? true : false);
      bpr_update_version($ignore_error);
    }
  }
}

osc_add_hook('init_admin', 'bpr_install_plugin_update', 10);


// PLUGIN UPDATE
function bpr_update_version($ignore_error = false) {
  $result = ModelBPR::newInstance()->versionUpdate($ignore_error);
  
  // if failed, do not update version and try DB update again
  if($result !== false || $ignore_error !== false) {
    osc_set_preference('version', BPR_VERSION_ID, 'plugin-business_profile', 'INTEGER');
    osc_reset_preferences();
  }
  
  // ignore error and force version update of plugin
  if($ignore_error === true) {
    osc_add_flash_ok_message(sprintf(__('Force update of "%s" completed! Verify plugin functionality, in case of problems reinstall plugin.', 'business_profile'), __('Business Profile Plugin', 'business_profile')), 'admin');
    header('Location:' . osc_admin_base_url(true) . '?page=plugins');
    exit;
  }
}

osc_add_hook(osc_plugin_path(__FILE__) . '_enable', 'bpr_update_version');


// UNINSTALL PLUGIN
function bpr_call_after_uninstall() {
  ModelBPR::newInstance()->uninstall();
}


// REVALIDATE USER PROFILE
function bpr_revalidate_user_profile($log_id) {
  if($log_id > 0 && osc_plugin_is_enabled('osclass_pay/index.php')) {
    $user_id = @$log['fk_i_user_id'];
    
    if($user_id > 0) {
      $seller = ModelBPR::newInstance()->getSellerByUserId($user_id);
      bpr_control_premium($seller);
    }
  }
}

osc_add_hook('osp_log_saved', 'bpr_revalidate_user_profile');



// ADMIN MENU
function bpr_menu($title = NULL) {
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/business_profile/css/admin.css?v=' . date('YmdHis') . '" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/business_profile/css/bootstrap-switch.css" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/business_profile/css/tipped.css" rel="stylesheet" type="text/css" />';
  echo '<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/business_profile/js/admin.js?v=' . date('YmdHis') . '"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/business_profile/js/tipped.js"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/business_profile/js/bootstrap-switch.js"></script>';



  if( $title == '') { $title = __('Configure', 'business_profile'); }

  $text  = '<div class="mb-head">';
  $text .= '<div class="mb-head-left">';
  $text .= '<h1>' . $title . '</h1>';
  $text .= '<h2>Business Profile Plugin</h2>';
  $text .= '</div>';
  $text .= '<div class="mb-head-right">';
  $text .= '<ul class="mb-menu">';
  $text .= '<li><a href="' . osc_route_url('bpr-list') . '" target="_blank"><i class="fa fa-external-link-square"></i><span>' . __('Front', 'business_profile') . '</span></a></li>';
  $text .= '<li><a href="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/configure.php"><i class="fa fa-wrench"></i><span>' . __('Configure', 'business_profile') . '</span></a></li>';
  $text .= '<li><a href="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/profiles.php"><i class="fa fa-briefcase"></i><span>' . __('Profiles', 'business_profile') . '</span></a></li>';
  $text .= '<li><a href="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/banner.php"><i class="fa fa-bullhorn"></i><span>' . __('Banners', 'business_profile') . '</span></a></li>';
  $text .= '</ul>';
  $text .= '</div>';
  $text .= '</div>';

  echo $text;
}



// ADMIN FOOTER
function bpr_footer() {
  $pluginInfo = osc_plugin_get_info('business_profile/index.php');
  $text  = '<div class="mb-footer">';
  $text .= '<a target="_blank" class="mb-developer" href="https://osclasspoint.com"><img src="https://osclasspoint.com/favicon.ico" alt="OsclassPoint Market" /> OsclassPoint Market</a>';
  $text .= '<a target="_blank" href="' . $pluginInfo['support_uri'] . '"><i class="fa fa-bug"></i> ' . __('Report Bug', 'business_profile') . '</a>';
  $text .= '<a target="_blank" href="https://forums.osclasspoint.com/"><i class="fa fa-handshake-o"></i> ' . __('Support Forums', 'business_profile') . '</a>';
  $text .= '<a target="_blank" class="mb-last" href="mailto:info@osclasspoint.com"><i class="fa fa-envelope"></i> ' . __('Contact Us', 'business_profile') . '</a>';
  $text .= '<span class="mb-version">v' . $pluginInfo['version'] . '</span>';
  $text .= '</div>';

  return $text;
}


// CREATE LINK IN USER MENU
function bpr_user_sidebar() {
  $user = array();
  
  if(osc_is_web_user_logged_in()) {
    $user = User::newInstance()->findByPrimaryKey(osc_logged_user_id());
  }

  if((bpr_param('only_company_users') == 1 && osc_is_web_user_logged_in() && @$user['b_company'] == 1) || bpr_param('only_company_users') != 1) {
    if(osc_current_web_theme() == 'veronika' || osc_current_web_theme() == 'stela' || osc_current_web_theme() == 'starter' || osc_current_web_theme() == 'careerjob' || (defined('USER_MENU_ICONS') && USER_MENU_ICONS == 1) ) {
      echo '<li class="opt_bpr_profile"><a href="' . osc_route_url('bpr-profile') . '" ><i class="fa fa-briefcase"></i> ' . __('Business profile', 'business_profile') . '</a></li>';
    } else {
      echo '<li class="opt_bpr_profile"><a href="' . osc_route_url('bpr-profile') . '" >' . __('Business profile', 'business_profile') . '</a></li>';
    }
  }
}

osc_add_hook('user_menu', 'bpr_user_sidebar');



// ADD MENU LINK TO PLUGIN LIST
function bpr_admin_menu() {
echo '<h3><a href="#">Business Profile Plugin</a></h3>
<ul> 
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/configure.php') . '">&raquo; ' . __('Configure', 'business_profile') . '</a></li>
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/profiles.php') . '">&raquo; ' . __('Profiles', 'business_profile') . '</a></li>
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/banner.php') . '">&raquo; ' . __('Banners', 'business_profile') . '</a></li>
</ul>';
}


// ADD MENU TO PLUGINS MENU LIST
osc_add_hook('admin_menu','bpr_admin_menu', 1);



// DISPLAY CONFIGURE LINK IN LIST OF PLUGINS
function bpr_conf() {
  osc_admin_render_plugin( osc_plugin_path( dirname(__FILE__) ) . '/admin/configure.php' );
}

osc_add_hook( osc_plugin_path( __FILE__ ) . '_configure', 'bpr_conf' );	


// CALL WHEN PLUGIN IS ACTIVATED - INSTALLED
osc_register_plugin(osc_plugin_path(__FILE__), 'bpr_call_after_install');

// SHOW UNINSTALL LINK
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'bpr_call_after_uninstall');

?>