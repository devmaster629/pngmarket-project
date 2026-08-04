<?php
// MAKE SURE OSC_PLUGIN_PATH FUNCTION EXISTS
if( !function_exists('osc_plugin_path') ) {
  function osc_plugin_path($file) {
    $file = preg_replace('|/+|','/', str_replace('\\','/',$file));
    $plugin_path = preg_replace('|/+|','/', str_replace('\\','/', PLUGINS_PATH));
    $file = $plugin_path . preg_replace('#^.*oc-content\/plugins\/#','',$file);
    return $file;
  }
}


// GENERATE SITEMAP
function bpr_generate_sitemap() {
  $start_time = microtime(true);

  $sellers = ModelBPR::newInstance()->getAllSeller();
  $locales = osc_get_locales();

  $filename = osc_base_path() . 'sitemap_business.xml';
  @unlink($filename); 
  
  $start_xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
  file_put_contents($filename, $start_xml);


  // INDEX
  bpr_sitemap_add_url(osc_route_url('bpr-list'), date('Y-m-d'), 'always');


  // ADD SELLERS
  if(count($sellers) > 0) {
    foreach($sellers as $seller) {
      if($seller['s_identifier'] != '' && $seller['b_enabled'] == 1) {
        bpr_sitemap_add_url(osc_route_url('bpr-seller', array('identifier' => $seller['s_identifier'])), date('Y-m-d'), 'daily');
      }
    }
  }

  $end_xml = '</urlset>';
  file_put_contents($filename, $end_xml, FILE_APPEND);
  

  // PING SEARCH ENGINES
  bpr_sitemap_ping_engines();
  
  // CALCULATE GENERATION TIME
  $time_elapsed = microtime(true) - $start_time;
  return $time_elapsed;
}



// ADD URL TO SITEMAP - HELP FUNCTION
function bpr_sitemap_add_url($url = '', $date = '', $freq = 'daily') {
  if( preg_match('|\?(.*)|', $url, $match) ) {
    $sub_url = $match[1];
    $param = explode('&', $sub_url);
    foreach($param as &$p) {
      list($key, $value) = explode('=', $p);
      $p = $key . '=' . urlencode($value);
    }
    $sub_url = implode('&', $param);
    $url = preg_replace('|\?.*|', '?' . $sub_url, $url);
  } else {
    $help = $url; 
    $help_encode = urlencode($help);
    $help_fix = str_replace('%2C', ',', $help_encode);
    $help_fix = str_replace('%2F', '/', $help_fix);
    $help_fix = str_replace('%3A', ':', $help_fix);
    $url = $help_fix;     
  }

  $filename = osc_base_path() . 'sitemap_business.xml';
  $xml  = '  <url>' . PHP_EOL;
  $xml .= '    <loc>' . htmlentities($url, ENT_QUOTES, "UTF-8") . '</loc>' . PHP_EOL;
  $xml .= '    <lastmod>' . $date . '</lastmod>' . PHP_EOL;
  $xml .= '    <changefreq>' . $freq . '</changefreq>' . PHP_EOL;
  $xml .= '  </url>' . PHP_EOL;
  file_put_contents($filename, $xml, FILE_APPEND);
}



// PING SEARCH ENGINES WITH NEW SITEMAP - HELP FUNCTION
function bpr_sitemap_ping_engines() {
  $sitemap = osc_base_url() . 'sitemap_business.xml';

  osc_doRequest( 'https://www.google.com/webmasters/sitemaps/ping?sitemap='.urlencode($sitemap), array());
  osc_doRequest( 'https://www.bing.com/webmaster/ping.aspx?siteMap='.urlencode($sitemap), array());
  //osc_doRequest( 'https://search.yahooapis.com/SiteExplorerService/V1/updateNotification?appid='.osc_page_title().'&url='.urlencode($sitemap), array());
}



osc_add_hook('cron_daily', 'bpr_generate_sitemap');

?>