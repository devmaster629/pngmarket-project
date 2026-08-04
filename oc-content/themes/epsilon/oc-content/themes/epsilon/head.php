<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<title><?php echo meta_title(); ?></title>
<meta name="title" content="<?php echo osc_esc_html(meta_title()); ?>" />
<?php if(meta_description() != '') { ?><meta name="description" content="<?php echo osc_esc_html(meta_description()); ?>" /><?php } ?>
<?php if(osc_get_canonical() != '') { ?><link rel="canonical" href="<?php echo osc_get_canonical(); ?>"/><?php } ?>
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Cache-Control" content="no-cache" />
<meta http-equiv="Expires" content="Mon, 01 Jul 1970 00:00:00 GMT" />
<?php if(!osc_is_search_page()) { ?><meta name="robots" content="index, follow" /><?php } ?>
<?php if(!osc_is_search_page()) { ?><meta name="googlebot" content="index, follow" /><?php } ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
<?php 
  if(eps_param('generate_favicons') == 1 && !function_exists('pwa_call_after_install')) {
    osc_current_web_theme_path('head-favicon.php');
  }
  
  $current_locale = osc_get_current_user_locale();
  $dimNormal = explode('x', osc_get_preference('dimNormal', 'osclass')); 
  
  if (!defined('JQUERY_VERSION') || JQUERY_VERSION == '1') {
    $jquery_version = '1';
  } else {
    $jquery_version = JQUERY_VERSION;
  }
?>

<script type="text/javascript">
  var currentLocaleCode = '<?php echo osc_esc_js($current_locale['pk_c_code'] ?? 'en_US'); ?>';
  var currentLocale = '<?php echo osc_esc_js($current_locale['s_name'] ?? 'English'); ?>';
  var fileDefaultText = '<?php echo osc_esc_js(__('No file selected', 'epsilon')); ?>';
  var fileBtnText = '<?php echo osc_esc_js(__('Choose File', 'epsilon')); ?>';
  var baseDir = '<?php echo osc_base_url(); ?>';
  var baseSearchUrl = '<?php echo osc_search_url(array('page' => 'search')); ?>';
  var baseAjaxUrl = '<?php echo eps_ajax_url(); ?>';
  var currentLocation = '<?php echo osc_get_osclass_location(); ?>';
  var currentSection = '<?php echo osc_get_osclass_section(); ?>';
  var userLogged = '<?php echo osc_is_web_user_logged_in() ? 1 : 0; ?>';
  var adminLogged = '<?php echo osc_is_admin_user_logged_in() ? 1 : 0; ?>';
  var epsLazy = '<?php echo (eps_is_lazy() ? 1 : 0); ?>';
  var darkMode = '<?php echo (eps_is_dark_mode() ? 1 : 0); ?>';
  var imgPreviewRatio = <?php echo round($dimNormal[0]/$dimNormal[1], 3); ?>;
  var searchRewrite = '/<?php echo osc_get_preference('rewrite_search_url', 'osclass'); ?>';
  var ajaxSearch = '<?php echo (eps_param('search_ajax') == 1 ? '1' : '0'); ?>';
  var ajaxForms = '<?php echo (eps_param('forms_ajax') == 1 ? '1' : '0'); ?>';
  var locationPick = '<?php echo (eps_param('location_pick') == 1 ? '0' : '0'); ?>';
  var delTitleNc = '<?php echo osc_esc_js(__('Parent category cannot be selected', 'epsilon')); ?>';
  var jqueryVersion = '<?php echo $jquery_version; ?>';
  var isRtl = <?php echo (eps_is_rtl() ? 'true' : 'false'); ?>;
</script>
<?php

osc_enqueue_style('style', osc_current_web_theme_url('css/style.css' . eps_asset_version()));
osc_enqueue_style('responsive', osc_current_web_theme_url('css/responsive.css' . eps_asset_version()));

if(eps_is_dark_mode()) {
  osc_enqueue_style('darkmode', osc_current_web_theme_url('css/dark.css' . eps_asset_version()));
}

if(eps_is_rtl()) { 
  osc_enqueue_style('rtl', osc_current_web_theme_url('css/rtl.css' . eps_asset_version()));
}

osc_enqueue_style('font-awesome5', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');

if ($jquery_version == '1') {
  osc_enqueue_style('jquery-ui', osc_current_web_theme_url('css/jquery-ui.min.css'));
} else {
  osc_enqueue_style('jquery-ui', osc_assets_url('js/jquery3/jquery-ui/jquery-ui.min.css'));
}

if(osc_is_ad_page() || (osc_get_osclass_location() == 'item' && osc_get_osclass_section() == 'send_friend')) {
  osc_enqueue_style('swiper', 'https://cdnjs.cloudflare.com/ajax/libs/Swiper/8.1.0/swiper-bundle.min.css');
  osc_enqueue_style('lightgallery', 'https://cdnjs.cloudflare.com/ajax/libs/lightgallery/1.10.0/css/lightgallery.min.css');
}

if(eps_ajax_image_upload() && (osc_is_publish_page() || osc_is_edit_page())) {
  osc_enqueue_style('fine-uploader-css', osc_assets_url('js/fineuploader/fineuploader.css'));
}

osc_register_script('global', osc_current_web_theme_js_url('global.js' . eps_asset_version()), array('jquery'));

if ($jquery_version == '1') {
  osc_register_script('validate', osc_current_web_theme_js_url('jquery.validate.min.js'), array('jquery'));
} else {
  osc_register_script('validate', osc_assets_url('js/jquery.validate.min.js'), array('jquery'));
}

osc_register_script('lazyload', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.lazy/1.7.9/jquery.lazy.min.js');
osc_register_script('swiper', 'https://cdnjs.cloudflare.com/ajax/libs/Swiper/8.1.0/swiper-bundle.min.js');
osc_register_script('lightgallery', 'https://cdnjs.cloudflare.com/ajax/libs/lightgallery/1.10.0/js/lightgallery-all.min.js');
osc_register_script('date', osc_base_url() . 'oc-includes/osclass/assets/js/date.js');
osc_register_script('rotate', osc_current_web_theme_js_url('jquery.rotate.js'), array('jquery'));

osc_enqueue_script('jquery');

if(eps_param('lazy_load') == 1) {
  osc_enqueue_script('lazyload');
}

osc_remove_script('jquery-validate');

if(!osc_is_search_page() && !osc_is_home_page()) {
  osc_enqueue_script('validate');
}

if(osc_is_ad_page() || (osc_get_osclass_location() == 'item' && osc_get_osclass_section() == 'send_friend')) {
  osc_enqueue_script('swiper');
  osc_enqueue_script('lightgallery');
}

if(!osc_is_search_page() && !osc_is_home_page() && !osc_is_ad_page()) {
  osc_enqueue_script('tabber');
}

if(eps_ajax_image_upload() && (osc_is_publish_page() || osc_is_edit_page())) {
  osc_enqueue_script('jquery-fineuploader');
  osc_enqueue_script('rotate');
}

if(osc_is_publish_page() || osc_is_edit_page() || osc_is_search_page()) {
  osc_enqueue_script('date');
}

osc_enqueue_script('jquery-ui');
osc_enqueue_script('global');

osc_run_hook('header'); 

if(eps_param('enable_custom_font') == 1 && eps_param('font_name') != '' && eps_param('font_url') != '') {
  osc_current_web_theme_path('head-font.php');
}

if(eps_param('enable_custom_color') == 1 && ((eps_get_theme_color() != '' && eps_get_theme_color() != '#3b49df') || (eps_is_dark_mode() && eps_param('color_dark') != ''))) {
  osc_current_web_theme_path('head-color.php');
}
?>