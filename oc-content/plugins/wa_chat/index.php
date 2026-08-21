<?php
/*
  Plugin Name: WhatsApp Chat Plugin
  Plugin URI: https://osclasspoint.com/
  Description: Add convenient way for communication between buyer and seller using WhatsApp.
  Version: 1.0.4
  Author: MB Themes
  Author URI: https://osclasspoint.com
  Author Email: info@osclasspoint.com
  Short Name: wa_chat
  Plugin update URI: wa-chat
  Support URI: https://forums.osclasspoint.com/
  Product Key: QWwL6TbKi4DGf2FyZwtl
*/

require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'model/ModelWAC.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'functions.php';


osc_enqueue_style('wac-user-style', osc_base_url() . 'oc-content/plugins/wa_chat/css/user.css?v=' . date('YmdHis'));
osc_register_script('wac-user', osc_base_url() . 'oc-content/plugins/wa_chat/js/user.js?v=' . date('YmdHis'), array('jquery'));
osc_enqueue_script('wac-user');


// INSTALL FUNCTION - DEFINE VARIABLES
function wac_call_after_install() {
  osc_set_preference('enable_items', 1, 'plugin-wa_chat', 'INTEGER');
  osc_set_preference('only_logged', 0, 'plugin-wa_chat', 'INTEGER');
  osc_set_preference('stats', 1, 'plugin-wa_chat', 'INTEGER');
  osc_set_preference('ask_seller', 0, 'plugin-wa_chat', 'INTEGER');
  osc_set_preference('enable_existing', 1, 'plugin-wa_chat', 'INTEGER');
  osc_set_preference('user_profile_phone', 1, 'plugin-wa_chat', 'INTEGER');
  osc_set_preference('use_phone', 'OSCLASS', 'plugin-wa_chat', 'STRING');
  osc_set_preference('custom_field_slug', '', 'plugin-wa_chat', 'STRING');      // For custom fields
  osc_set_preference('default_country_code', '', 'plugin-wa_chat', 'STRING');
  osc_set_preference('sanitize_phone', 0, 'plugin-wa_chat', 'INTEGER');
  osc_set_preference('phone_length', 10, 'plugin-wa_chat', 'INTEGER');
  // osc_set_preference('hook', 0, 'plugin-wa_chat', 'INTEGER');
  osc_set_preference('hooks', 'item_sidebar_user,item_detail', 'plugin-wa_chat', 'INTEGER');
  osc_set_preference('icon', 1, 'plugin-wa_chat', 'INTEGER');
  osc_set_preference('button_text', '', 'plugin-wa_chat', 'STRING');
  osc_set_preference('style', 'BUTTON_ROUNDED', 'plugin-wa_chat', 'STRING');
  osc_set_preference('size', 'MEDIUM', 'plugin-wa_chat', 'STRING');
  osc_set_preference('category', '', 'plugin-wa_chat', 'STRING');
  osc_set_preference('custom_css', '', 'plugin-wa_chat', 'STRING');
  osc_set_preference('button_bg_color', '', 'plugin-wa_chat', 'STRING');
  osc_set_preference('button_font_color', '', 'plugin-wa_chat', 'STRING');
  osc_set_preference('button_padding', 3, 'plugin-wa_chat', 'INTEGER');
  osc_set_preference('phone_in_button', '', 'plugin-wa_chat', 'INTEGER');
  osc_set_preference('icon_bg_color', '', 'plugin-wa_chat', 'STRING');
  osc_set_preference('icon_padding', 0, 'plugin-wa_chat', 'INTEGER');

  osc_set_preference('web_enable', 1, 'plugin-wa_chat', 'INTEGER');
  osc_set_preference('web_only_logged', 0, 'plugin-wa_chat', 'INTEGER');
  osc_set_preference('web_phone', 'OSCLASS', 'plugin-wa_chat', 'STRING');
  osc_set_preference('web_icon', 1, 'plugin-wa_chat', 'INTEGER');
  osc_set_preference('web_button_text', '', 'plugin-wa_chat', 'STRING');
  osc_set_preference('web_position', 'bottom-left', 'plugin-wa_chat', 'STRING');
  osc_set_preference('web_style', 'ICON', 'plugin-wa_chat', 'STRING');
  osc_set_preference('web_size', 'MEDIUM', 'plugin-wa_chat', 'STRING');  
  osc_set_preference('web_hook', 1, 'plugin-wa_chat', 'INTEGER');  
  osc_set_preference('web_icon', 1, 'plugin-wa_chat', 'INTEGER');  
  osc_set_preference('web_phone_in_button', '', 'plugin-wa_chat', 'STRING');  
  osc_set_preference('web_margin', 15, 'plugin-wa_chat', 'INTEGER');
  osc_set_preference('web_button_bg_color', '', 'plugin-wa_chat', 'STRING');  
  osc_set_preference('web_button_font_color', '', 'plugin-wa_chat', 'STRING');  
  osc_set_preference('web_button_padding', 0, 'plugin-wa_chat', 'INTEGER');  
  osc_set_preference('web_icon_padding', 0, 'plugin-wa_chat', 'INTEGER');  
  osc_set_preference('web_icon_bg_color', '', 'plugin-wa_chat', 'STRING');  
  osc_set_preference('web_custom_css', '', 'plugin-wa_chat', 'STRING');  

  ModelWAC::newInstance()->install();
}


function wac_call_after_uninstall() {
  ModelWAC::newInstance()->uninstall();
}


// ADMIN MENU
function wac_menu($title = NULL) {
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/wa_chat/css/admin.css?v=' . date('YmdHis') . '" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/wa_chat/css/bootstrap-switch.css" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/wa_chat/css/tipped.css" rel="stylesheet" type="text/css" />';
  echo '<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/wa_chat/js/admin.js?v=' . date('YmdHis') . '"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/wa_chat/js/tipped.js"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/wa_chat/js/bootstrap-switch.js"></script>';

  if( $title == '') { $title = __('Configure', 'wa_chat'); }

  $text  = '<div class="mb-head">';
  $text .= '<div class="mb-head-left">';
  $text .= '<h1>' . $title . '</h1>';
  $text .= '<h2>WhatsApp Chat Plugin</h2>';
  $text .= '</div>';
  $text .= '<div class="mb-head-right">';
  $text .= '<ul class="mb-menu">';
  $text .= '<li><a href="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=wa_chat/admin/configure.php"><i class="fa fa-wrench"></i><span>' . __('Configure', 'wa_chat') . '</span></a></li>';
  $text .= '</ul>';
  $text .= '</div>';
  $text .= '</div>';

  echo $text;
}



// ADMIN FOOTER
function wac_footer() {
  $pluginInfo = osc_plugin_get_info('wa_chat/index.php');
  $text  = '<div class="mb-footer">';
  $text .= '<a target="_blank" class="mb-developer" href="https://osclasspoint.com"><img src="https://osclasspoint.com/favicon.ico" alt="OsclassPoint Market" /> OsclassPoint Market</a>';
  $text .= '<a target="_blank" href="' . $pluginInfo['support_uri'] . '"><i class="fa fa-bug"></i> ' . __('Report Bug', 'wa_chat') . '</a>';
  $text .= '<a target="_blank" href="https://forums.osclasspoint.com/"><i class="fa fa-handshake-o"></i> ' . __('Support Forums', 'wa_chat') . '</a>';
  $text .= '<a target="_blank" class="mb-last" href="mailto:info@osclasspoint.com"><i class="fa fa-envelope"></i> ' . __('Contact Us', 'wa_chat') . '</a>';
  $text .= '<span class="mb-version">v' . $pluginInfo['version'] . '</span>';
  $text .= '</div>';

  return $text;
}



// ADD MENU LINK TO PLUGIN LIST
function wac_admin_menu() {
echo '<h3><a href="#">WhatsApp Chat Plugin</a></h3>
<ul> 
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/configure.php') . '">&raquo; ' . __('Configure', 'wa_chat') . '</a></li>
</ul>';
}


// ADD MENU TO PLUGINS MENU LIST
osc_add_hook('admin_menu','wac_admin_menu', 1);



// DISPLAY CONFIGURE LINK IN LIST OF PLUGINS
function wac_conf() {
  osc_admin_render_plugin( osc_plugin_path( dirname(__FILE__) ) . '/admin/configure.php' );
}

osc_add_hook( osc_plugin_path( __FILE__ ) . '_configure', 'wac_conf' );	


// CALL WHEN PLUGIN IS ACTIVATED - INSTALLED
osc_register_plugin(osc_plugin_path(__FILE__), 'wac_call_after_install');

// SHOW UNINSTALL LINK
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'wac_call_after_uninstall');

?>