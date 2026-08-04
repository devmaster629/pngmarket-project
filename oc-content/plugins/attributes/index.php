<?php
/*
  Plugin Name: Attributes Osclass Plugin
  Plugin URI: https://osclasspoint.com/osclass-plugins/extra-fields-and-other/attributes-osclass-plugin-i77
  Description: Create custom attributes like radios, checkboxes, cascading dropdowns or input fields with rich features
  Version: 2.8.9
  Author: MB Themes
  Author URI: https://osclasspoint.com
  Author Email: info@osclasspoint.com
  Short Name: attributes
  Plugin update URI: attributes
  Support URI: https://forums.osclasspoint.com/multilanguage-attributes-plugin/
  Product Key: tJvtsgwVfHh2iNVrUKZm
*/

define('ATR_HTML5_VALIDATION', false);

require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'model/ModelATR.php';
require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'functions.php';


osc_enqueue_style('atr-user-style', osc_base_url() . 'oc-content/plugins/attributes/css/user.css?v=' . date('YmdHis'));
osc_register_script('atr-user', osc_base_url() . 'oc-content/plugins/attributes/js/user.js?v=' . date('YmdHis'), 'jquery');
osc_register_script('atr-nestedsortable', 'https://cdnjs.cloudflare.com/ajax/libs/nestedSortable/2.0.0/jquery.mjs.nestedSortable.min.js', array('jquery'));

osc_enqueue_script('atr-user');
osc_enqueue_script('jquery-ui');
//osc_enqueue_script('jquery-nested');  // not funcitonal anymore


// Remove osclass nested jQuery script
function atr_remove_nested() {
  if(Params::getParam('page') == 'plugins' && Params::getParam('action') == 'renderplugin') {
    $file = explode('/', Params::getParam('file'));
    
    if(isset($file[0]) && $file[0] == 'attributes') {
      osc_remove_script('jquery-nested');
      osc_enqueue_script('atr-nestedsortable');
    }
  }
}

osc_add_hook('init', 'atr_remove_nested', 5);

// Add javascript variables to footer
function atr_js() {
?><script type="text/javascript">var atr_select_url="<?php echo osc_base_url(true); ?>?page=ajax&action=runhook&hook=atr_select_url";</script><?php
}

osc_add_hook('footer', 'atr_js');
osc_add_hook('admin_footer', 'atr_js');


// CLEAN CACHE ON EDITED ITEM
function atr_clean_item_cache($item) {
  if(atr_param('enable_cache') == 1) {
    if($item !== false && isset($item['pk_i_id'])) {
      $item_id = $item['pk_i_id'];
      $category_id = $item['fk_i_item_id'];
      
      $cache_key = array();
      $cache_key[] = md5(osc_base_url() . 'Plugin::Attributes::getItemAttributes::' . (string)$item_id . (string)$category_id);
      //$cache_key[] = md5(osc_base_url() . 'Plugin::Attributes::getItemAttributeValues::' . (string)$item_id . (string)$attribute_id);
      $cache_key[] = md5(osc_base_url() . 'Plugin::Attributes::getAllItemAttributeValues::' . (string)$item_id);
      $cache_key[] = md5(osc_base_url() . 'Plugin::Attributes::getAllItemAttributeValuesWithLocale::' . (string)$item_id);
      $cache_key[] = md5(osc_base_url() . 'Plugin::Attributes::getAllItemAttributeValues::' . (string)$item_id);
      //$cache_key[] = md5(osc_base_url() . 'Plugin::Attributes::getItemAttributeValueRowsNotSelected::' . (string)$item_id . (string)$attribute_id);
      //$cache_key[] = md5(osc_base_url() . 'Plugin::Attributes::getItemAttributeRaw::' . (string)$item_id . (string)$attribute_id . (string)$attribute_value_id);

      foreach($cache_key as $ck) {
        osc_cache_delete($ck);
      }
    }
  }
}

osc_add_hook('edited_item', 'atr_clean_item_cache');


// INSTALL FUNCTION - DEFINE VARIABLES
function atr_call_after_install() {
  osc_set_preference('styled', 1, 'plugin-attributes', 'INTEGER');
  osc_set_preference('attribute_icon', 1, 'plugin-attributes', 'INTEGER');
  osc_set_preference('enable_cache', 1, 'plugin-attributes', 'INTEGER');

  ModelATR::newInstance()->install();
}


function atr_call_after_uninstall() {
  ModelATR::newInstance()->uninstall();
}


// PLUGIN UPDATE
function atr_update_version() {
  ModelATR::newInstance()->versionUpdate();
}

osc_add_hook(osc_plugin_path(__FILE__) . '_enable', 'atr_update_version');



// ADMIN MENU
function atr_menu($title = NULL) {
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/attributes/css/admin.css?v=' . date('YmdHis') . '" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/attributes/css/bootstrap-switch.css" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/plugins/attributes/css/tipped.css" rel="stylesheet" type="text/css" />';
  echo '<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/attributes/js/admin.js?v=' . date('YmdHis') . '"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/attributes/js/tipped.js"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/plugins/attributes/js/bootstrap-switch.js"></script>';



  if( $title == '') { $title = __('Configure', 'attributes'); }

  $text  = '<div class="mb-head">';
  $text .= '<div class="mb-head-left">';
  $text .= '<h1>' . $title . '</h1>';
  $text .= '<h2>Attributes Plugin</h2>';
  $text .= '</div>';
  $text .= '<div class="mb-head-right">';
  $text .= '<ul class="mb-menu">';
  $text .= '<li><a href="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=attributes/admin/configure.php"><i class="fa fa-wrench"></i><span>' . __('Configure', 'attributes') . '</span></a></li>';
  $text .= '</ul>';
  $text .= '</div>';
  $text .= '</div>';

  echo $text;
}



// ADMIN FOOTER
function atr_footer() {
  $pluginInfo = osc_plugin_get_info('attributes/index.php');
  $text  = '<div class="mb-footer">';
  $text .= '<a target="_blank" class="mb-developer" href="https://osclasspoint.com"><img src="https://osclasspoint.com/favicon.ico" alt="OsclassPoint" /> OsclassPoint.com</a>';
  $text .= '<a target="_blank" href="' . $pluginInfo['support_uri'] . '"><i class="fa fa-bug"></i> ' . __('Report Bug', 'attributes') . '</a>';
  $text .= '<a target="_blank" href="https://forums.osclasspoint.com/"><i class="fa fa-handshake-o"></i> ' . __('Support Forums', 'attributes') . '</a>';
  $text .= '<a target="_blank" class="mb-last" href="mailto:info@osclasspoint.com"><i class="fa fa-envelope"></i> ' . __('Contact Us', 'attributes') . '</a>';
  $text .= '<span class="mb-version">v' . $pluginInfo['version'] . '</span>';
  $text .= '</div>';

  return $text;
}



// ADD MENU LINK TO PLUGIN LIST
function atr_admin_menu() {
echo '<h3><a href="#">Attributes Plugin</a></h3>
<ul> 
  <li><a style="color:#2eacce;" href="' . osc_admin_render_plugin_url(osc_plugin_path(dirname(__FILE__)) . '/admin/configure.php') . '">&raquo; ' . __('Configure', 'attributes') . '</a></li>
</ul>';
}


// ADD MENU TO PLUGINS MENU LIST
osc_add_hook('admin_menu','atr_admin_menu', 1);



// DISPLAY CONFIGURE LINK IN LIST OF PLUGINS
function atr_conf() {
  osc_admin_render_plugin( osc_plugin_path( dirname(__FILE__) ) . '/admin/configure.php' );
}

osc_add_hook( osc_plugin_path( __FILE__ ) . '_configure', 'atr_conf' );	


// CALL WHEN PLUGIN IS ACTIVATED - INSTALLED
osc_register_plugin(osc_plugin_path(__FILE__), 'atr_call_after_install');

// SHOW UNINSTALL LINK
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'atr_call_after_uninstall');

?>