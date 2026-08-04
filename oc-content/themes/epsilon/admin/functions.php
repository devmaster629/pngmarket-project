<?php

// ADMIN MENU
function eps_menu($title = NULL) {
  echo '<link href="' . osc_base_url() . 'oc-content/themes/epsilon/admin/css/admin.css?v=' . date('YmdHis') . '" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/themes/epsilon/admin/css/bootstrap-switch.css" rel="stylesheet" type="text/css" />';
  echo '<link href="' . osc_base_url() . 'oc-content/themes/epsilon/admin/css/tipped.css" rel="stylesheet" type="text/css" />';
//  echo '<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" type="text/css" />';

  echo '<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />';
  echo '<script src="' . osc_base_url() . 'oc-content/themes/epsilon/admin/js/admin.js?v=' . date('YmdHis') . '"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/themes/epsilon/admin/js/tipped.js"></script>';
  echo '<script src="' . osc_base_url() . 'oc-content/themes/epsilon/admin/js/bootstrap-switch.js"></script>';


  $current = basename(Params::getParam('file'));

  $links = array();
  $links[] = array('file' => 'configure.php', 'icon' => 'fa-wrench', 'title' => __('Configure', 'epsilon'));
  $links[] = array('file' => 'banner.php', 'icon' => 'fa-clone', 'title' => __('Advertisement', 'epsilon'));
  $links[] = array('file' => 'category.php', 'icon' => 'fa-cogs', 'title' => __('Category Icons', 'epsilon'));
  $links[] = array('file' => 'logo.php', 'icon' => 'fa-desktop', 'title' => __('Header Logo', 'epsilon'));
  $links[] = array('file' => 'plugins.php', 'icon' => 'fa-puzzle-piece', 'title' => __('Plugins', 'epsilon'));

  if( $title == '') { $title = __('Configure', 'epsilon'); }

  $text  = '<div class="mb-head">';
  $text .= '<div class="mb-head-left">';
  $text .= '<h1>' . $title . '</h1>';
  $text .= '<h2>Epsilon Osclass Theme</h2>';
  $text .= '</div>';
  $text .= '<div class="mb-head-right">';
  $text .= '<ul class="mb-menu">';

  foreach($links as $l) {
    $text .= '<li><a href="' . osc_admin_base_url(true) . '?page=appearance&action=render&file=oc-content/themes/epsilon/admin/' . $l['file'] . '" class="' . ($l['file'] == $current ? 'active' : '') . '"><i class="fa ' . $l['icon'] . '"></i><span>' . $l['title'] . '</span></a></li>';
  }

  $text .= '</ul>';
  $text .= '</div>';
  $text .= '</div>';

  echo $text;
}


// ADMIN FOOTER
function eps_footer() {
  $theme_info = eps_theme_info();

  $text  = '<div class="mb-footer">';
  $text .= '<a target="_blank" class="mb-developer" href="https://osclasspoint.com"><img src="https://osclasspoint.com/favicon.ico" alt="OsclassPoint Market" /> OsclassPoint Market</a>';

  if(isset($theme_info['support_uri']) && $theme_info['support_uri'] != '') {
    $text .= '<a target="_blank" href="' . $theme_info['support_uri'] . '"><i class="fa fa-bug"></i> ' . __('Report Bug', 'cdn') . '</a>';
  }
  
  $text .= '<a target="_blank" href="https://forums.osclasspoint.com/"><i class="fa fa-handshake-o"></i> ' . __('Support Forums', 'cdn') . '</a>';
  $text .= '<a target="_blank" class="mb-last" href="mailto:info@osclasspoint.com"><i class="fa fa-envelope"></i> ' . __('Contact Us', 'cdn') . '</a>';
  $text .= '<span class="mb-version">v' . $theme_info['version'] . '</span>';
  $text .= '</div>';

  return $text;
}


if(!function_exists('message_ok')) {
  function message_ok( $text ) {
    $final  = '<div class="flashmessage flashmessage-ok flashmessage-inline">';
    $final .= $text;
    $final .= '</div>';
    echo $final;
  }
}


if(!function_exists('message_error')) {
  function message_error( $text ) {
    $final  = '<div class="flashmessage flashmessage-error flashmessage-inline">';
    $final .= $text;
    $final .= '</div>';
    echo $final;
  }
}


// List of categories
function eps_has_subcategories_special($categories, $deep = 0) {
  $upload_dir_small = osc_themes_path() . osc_current_web_theme() . '/images/small_cat/';
  $upload_dir_small_sample = osc_themes_path() . osc_current_web_theme() . '/images/small_cat/sample/';
  // $upload_dir_large = osc_themes_path() . osc_current_web_theme() . '/images/large_cat/';

  $i = 1;
  foreach($categories as $c) {
    $extra = ModelEPS::newInstance()->getCategoryExtra($c['pk_i_id']);
    ?>
    
    <div class="mb-table-row <?php echo ($deep == 0 ? 'parent' . ' o' . $i : ''); ?>">
      <div class="mb-col-1 id"><?php echo $c['pk_i_id']; ?></div>
      <div class="mb-col-5 mb-align-left sub<?php echo $deep; ?> name"><?php echo $c['s_name']; ?></div>
      <div class="mb-col-3 mb-align-left icon">
        <?php if(file_exists($upload_dir_small . $c['pk_i_id'] . '.png')) { ?>
          <img class="icon" src="<?php echo osc_base_url() . 'oc-content/themes/' . osc_current_web_theme() . '/images/small_cat/' . $c['pk_i_id'] . '.png'; ?>" alt=""/>
        <?php } else if(eps_param('sample_images') == 1 && file_exists($upload_dir_small_sample . $c['pk_i_id'] . '.png')) { ?>
          <img class="icon" src="<?php echo osc_base_url() . 'oc-content/themes/' . osc_current_web_theme() . '/images/small_cat/sample/' . $c['pk_i_id'] . '.png'; ?>" alt=""/>
        <?php } else { ?>
          <i class="fa fa-times mb-no"></i>
        <?php } ?>
      </div>
      
      <div class="mb-col-3 mb-align-left">
        <a class="add_img" id="category[<?php echo $c['pk_i_id']; ?>][icon]" href="#"><?php _e('Upload icon', 'epsilon'); ?></a>
      </div>

      <div class="mb-col-3 mb-align-left fa-icon">
        <?php if(@$extra['s_icon'] != '') { ?>
          <?php
            $parts = array_filter(explode(' ', @$extra['s_icon']));
            
            if(in_array($parts[0], array('fa','fas','far','fab'))) {
              $itype = $parts[0];
            } else {
              $itype = 'fa';
            }
            
            $iname = (@$parts[1] != '' ? $parts[1] : $parts[0]);
          ?>
          <i class="<?php echo $itype . ' ' . $iname; ?>"></i> <span class="mb-icon-name"><?php echo $extra['s_icon']; ?></span>
        <?php } else { ?>
          <i class="fa fa-times mb-no"></i>
        <?php } ?>
      </div>

      <div class="mb-col-3 mb-align-left">
        <a class="add_fa" id="category[<?php echo $c['pk_i_id']; ?>][faicon]" href="#" title="To remove icon click on link and leave input empty."><?php _e('Add icon', 'epsilon'); ?></a>
      </div>


      <div class="mb-col-3 mb-align-left color">
        <?php if(@$extra['s_color'] != '') { ?>
          <div class="mb-color-circle" style="background-color:<?php echo $extra['s_color']; ?>;"></div>
          <span class="mb-color-name"><?php echo $extra['s_color']; ?></span>
        <?php } else { ?>
          <i class="fa fa-times mb-no"></i>
        <?php } ?>
      </div>
      
      <div class="mb-col-3 mb-align-left">
        <a class="add_color" id="category[<?php echo $c['pk_i_id']; ?>][color]" href="#" title="To remove color click on link and leave input empty."><?php _e('Add color', 'epsilon'); ?></a>
      </div>
    </div>

    <?php
    if(isset($c['categories']) && is_array($c['categories']) && !empty($c['categories'])) {
      eps_has_subcategories_special($c['categories'], $deep+1);
    }   

    $i++;
  }
}

?>