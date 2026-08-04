<?php
  $user_id = osc_logged_user_id();
  $user = User::newInstance()->findByPrimaryKey($user_id);
  $seller = ModelBPR::newInstance()->getSellerByUserId($user_id);


  // ONLY COMPANY USERS CAN HAVE PROFILE
  if(bpr_param('only_company_users') == 1 && @$user['b_company'] != 1) {
    osc_add_flash_info_message(__('Business profile is enabled only for company users', 'business_profile'));
    header('Location:' . osc_user_dashboard_url());
    exit;
  }

  // REMOVE IMAGE
  if(Params::getParam('removeImageType') != '' && !bpr_is_demo()) {
    $img_type = Params::getParam('removeImageType');
    
    if($img_type == 'icon' || $img_type == 'logo' || $img_type == 'cover') {
      bpr_remove_user_image($seller, $img_type);
    }

    osc_add_flash_ok_message(__('Image has been successfully removed', 'business_profile'));
    header('Location:' . osc_route_url('bpr-profile'));
    exit;
  } 

  // REMOVE GALLERY IMAGE
  if(Params::getParam('removeGalleryImage') != '' && !bpr_is_demo()) {
    $img = rawurldecode(Params::getParam('removeGalleryImage'));

    $result = bpr_remove_user_gallery_image($seller, $img);

    if($result === true) {
      osc_add_flash_ok_message(__('Gallery image has been successfully removed', 'business_profile'));
    } else {
      osc_add_flash_error_message(__('Gallery image could not be removed (error)', 'business_profile'));
    }
    
    header('Location:' . osc_route_url('bpr-profile'));
    exit;
  }
  
  // REMOVE BUSINESS PROFILE
  if(Params::getParam('route') == 'bpr-profile-remove' && !bpr_is_demo() && osc_is_web_user_logged_in()) {
    bpr_removed_user(osc_logged_user_id(), false);

    osc_add_flash_ok_message(__('Your business profile has been successfully removed', 'business_profile'));
    header('Location:' . osc_user_dashboard_url());
    exit;
  } 
  

  // PROCESS UPDATE
  if(Params::getParam('what') == 'profile' && Params::getParam('user_id') > 0 && !bpr_is_demo()) {
    $post = Params::getParamsAsArray();

    $identifier = (osc_sanitizeString($user['s_name']) <> '' ? osc_sanitizeString($user['s_name']) : $user_id);
    $check = ModelBPR::newInstance()->checkIdentifier($identifier);

    if(isset($check['s_identifier']) && $check['pk_i_id'] <> @$seller['pk_i_id']) {
      $identifier = $identifier . $user_id;
    }

    $enabled = isset($seller['b_enabled']) ? $seller['b_enabled'] : (bpr_param('require_validation') == 1 ? 0 : 1);
    $verified = isset($seller['b_verified']) ? $seller['b_verified'] : 0;
    $type = isset($seller['i_type']) ? $seller['i_type'] : 1;
    $category = isset($post['bpr-category']) ? $post['bpr-category'] : '';
    $color = isset($post['bpr-color']) ? $post['bpr-color'] : '';
    $legal_notice = trim(isset($post['bpr-legal_notice']) ? strip_tags($post['bpr-legal_notice']) : '');

    if($category <> '') {
      $category = str_replace(',', ';', $category);
    }


    $update = array(
      'pk_i_id' => @$seller['pk_i_id'],
      'fk_i_user_id' => $user_id,
      's_identifier' => $identifier,
      'b_enabled' => $enabled,
      'b_verified' => $verified,
      'i_type' => $type,
      's_category_ids' => $category,
      's_color' => $color,
      's_icon' => @$seller['s_icon'],
      's_logo' => @$seller['s_logo'],
      's_cover' => @$seller['s_cover']
    );

    if(bpr_param('legal_notice') == 1) {
      $update['s_legal_notice'] = $legal_notice;
    }

    // Opening hours
    $u_hours = '';
    foreach($post as $n => $v) {
      $data = explode('_', $n);
      
      if($data[0] == 'bpr-oh' && $v <> '') {
        $u_hours .= $data[1] . '[x]' . $v . '[y]';
      }
    }
 
    $update['s_hours'] = $u_hours;


    // Social networks
    $u_social = '';
    foreach($post as $n => $v) {
      $data = explode('_', $n);
      
      if($data[0] == 'bpr-soc' && $v <> '') {
        $u_social .= $data[1] . '[x]' . $v . '[y]';
      }
    }

    $update['s_socials'] = $u_social;



    // Features
    $u_feat = '';
    foreach($post as $n => $v) {
      $data = explode('_', $n);
      
      if($data[0] == 'bpr-feat' && $v <> '') {
        $u_feat .= $data[1] . ',';
      }
    }

    $update['s_features'] = $u_feat;


    // Payments
    $u_pay = '';
    foreach($post as $n => $v) {
      $data = explode('_', $n);
      
      if($data[0] == 'bpr-pay' && $v <> '') {
        $u_pay .= $data[1] . ',';
      }
    }

    $update['s_payments'] = $u_pay;


    // Youtube videos
    if(bpr_param('video') == 1 && bpr_param('video') > 0) {
      $u_video = array();
      foreach($post as $n => $v) {
        $data = explode('_', $n);
        
        if($data[0] == 'bpr-video' && $v <> '') {
          $vid = '';
          
          if(strpos($v, 'https://www.youtube.com/embed/') !== false || strpos($v, 'https://youtu.be/') !== false) {
            $ua = str_replace(array('https://www.youtube.com/embed/', 'https://youtu.be/'), '', $v);
            
            if($ua <> '') {
              $ua = explode('?', $ua)[0];
              
              if($ua <> '' && strlen((string)$ua) > 8 && strlen((string)$ua) < 20) {
                $vid = $ua;
              }
            }
          } else if (strpos($v, 'youtube.com/watch') !== false) {
            $ua = parse_url($v);
            parse_str($ua['query'], $up);

            if(isset($up['v']) && $up['v'] <> '') {
              $can = explode('?', $up['v'])[0];

              if(strlen((string)$can) > 8 && strlen((string)$can) < 20) {
                $vid = $can; 
              }
            }
          } else {
            if(!filter_var((string)$v, FILTER_VALIDATE_URL) && strlen((string)$v) > 8 && strlen((string)$v) < 20) {
              $vid = explode('?', $v)[0];
            }
          }
          
          if(trim((string)$vid) <> '') {
            $u_video[] = trim($vid);
          } else {
            osc_add_flash_error_message(sprintf(__('Wrong youtube video (%s). It must be share or embedded url, or video ID.', 'business_profile'), $v));
          }
        }
      }

      $u_video = array_filter(array_unique($u_video));
      $update['s_videos'] = implode(',', $u_video);
    }


    $existing = bpr_prepare_user_gallery($seller);

    $icon_uploads_path = UPLOADS_PATH . '/business_profile/' . $user_id . '/icon/';
    $logo_uploads_path = UPLOADS_PATH . '/business_profile/' . $user_id . '/logo/';
    $cover_uploads_path = UPLOADS_PATH . '/business_profile/' . $user_id . '/cover/';
    $gallery_uploads_path = UPLOADS_PATH . '/business_profile/' . $user_id . '/gallery/';
    
    bpr_check_upload_dirs($user_id);



    // Old Upload icon
    /*
    $file_icon = Params::getFiles('bpr-file-icon');
    $allowed_extensions = array('png');

    $extension = strtolower(pathinfo($file_icon['name'], PATHINFO_EXTENSION));
    $max_file_size = 1024 * 1000;  //(in bytes)
    $file_size = $file_icon['size'];
    $file_name = $user_id . '.' . $extension;
    $file_name_profile = 'profile' . $user_id . '.' . $extension;
    $file_name_db = $user_id . '.' . $extension . '?v=' . date('Ymdhis');

    $update_file_name = '';
    if($file_icon['name'] <> '') {
      if( $file_icon['error'] == UPLOAD_ERR_OK) {
        if(in_array($extension, $allowed_extensions)) {
          if( $file_size < $max_file_size ) {
            if( move_uploaded_file($file_icon['tmp_name'], $icon_uploads_path . $file_name ) ) {
              $update_file_name = $file_name;
              $update['s_icon'] = $file_name_db;

              if(function_exists('profile_picture_install')) {
                if(copy($icon_uploads_path . $file_name, osc_content_path() . 'plugins/profile_picture/images/' . $file_name_profile)) {
                  ModelBPR::newInstance()->updateProfilePicture($user_id);
                }
              }
            } else {
              osc_add_flash_error_message(__('An error with logo upload has occurred, please try again', 'business_profile') );
            }
          } else {
            osc_add_flash_error_message( __('Logo is too big and was not uploaded. Maximum file size is:', 'business_profile') . ' ' . round($max_file_size/1000) . 'kb' );
          }
        } else {
          osc_add_flash_error_message( __('Logo extension is not allowed, file was not uploaded. Only files with following extensions are allowed', 'business_profile') . ': ' . implode(', ', $allowed_extensions) );
        }
      } else {
        osc_add_flash_error_message( __('An error with logo upload has occurred, please try again.', 'business_profile') );
      }
    }
    */
    
    
    // New upload icon
    $file_icon = Params::getFiles('bpr-file-icon');
    $allowed_extensions = array('png','jpg','jpeg','gif','webp');

    $extension = strtolower(pathinfo($file_icon['name'], PATHINFO_EXTENSION));
    $max_file_size = 2048 * 1000;  //(in bytes)
    $file_size = $file_icon['size'];
    $file_name = $user_id . '_' . mb_generate_rand_string(6) . '.' . $extension;
    $file_name_profile = 'profile' . $user_id . '.' . $extension;
    // $file_name_db = $user_id . '.' . $extension . '?v=' . date('Ymdhis');
    $icon_uploads_path = UPLOADS_PATH . '/business_profile/' . $user_id . '/icon/';

    $update_file_name = '';
    if($file_icon['name'] <> '') {
      if($file_icon['error'] == UPLOAD_ERR_OK) {
        if(in_array($extension, $allowed_extensions)) {
          if( $file_size < $max_file_size ) {
            if(move_uploaded_file($file_icon['tmp_name'], $icon_uploads_path . $file_name ) ) {
              $update_file_name = $file_name;
              // $update['s_icon'] = $file_name_db;
              $update['s_icon'] = $file_name;

              if(function_exists('profile_picture_install')) {
                if(copy(osc_content_path() . 'plugins/business_profile/img/icon/' . $file_name, osc_content_path() . 'plugins/profile_picture/images/' . $file_name_profile)) {
                  ModelBPR::newInstance()->updateProfilePicture($user_id);
                }
              }

            } else {
              osc_add_flash_error_message(__('An error with square logo upload has occurred, please try again', 'business_profile'));
            }
          } else {
            osc_add_flash_error_message(__('Square logo is too big and was not uploaded. Maximum file size is:', 'business_profile') . ' ' . round($max_file_size/1000) . 'kb');
          }
        } else {
          osc_add_flash_error_message(__('Square logo extension is not allowed, file was not uploaded. Only files with following extensions are allowed', 'business_profile') . ': ' . implode(', ', $allowed_extensions));
        }
      } else {
        osc_add_flash_error_message(__('An error with square logo upload has occurred, please try again.', 'business_profile'));
      }
    }


    // New upload logo
    $file_logo = Params::getFiles('bpr-file-logo');
    $allowed_extensions = array('png','jpg','jpeg','gif','webp');

    $extension = strtolower(pathinfo($file_logo['name'], PATHINFO_EXTENSION));
    $max_file_size = 2048 * 1000;  //(in bytes)
    $file_size = $file_logo['size'];
    $file_name = $user_id . '_' . mb_generate_rand_string(6) . '.' . $extension;
    $file_name_profile = 'profile' . $user_id . '.' . $extension;
    // $file_name_db = $user_id . '.' . $extension . '?v=' . date('Ymdhis');
    $logo_uploads_path = UPLOADS_PATH . '/business_profile/' . $user_id . '/logo/';

    $update_file_name = '';
    if($file_logo['name'] <> '') {
      if($file_logo['error'] == UPLOAD_ERR_OK) {
        if(in_array($extension, $allowed_extensions)) {
          if( $file_size < $max_file_size ) {
            if(move_uploaded_file($file_logo['tmp_name'], $logo_uploads_path . $file_name ) ) {
              $update_file_name = $file_name;
              // $update['s_logo'] = $file_name_db;
              $update['s_logo'] = $file_name;

            } else {
              osc_add_flash_error_message(__('An error with logo upload has occurred, please try again', 'business_profile'));
            }
          } else {
            osc_add_flash_error_message(__('Logo is too big and was not uploaded. Maximum file size is:', 'business_profile') . ' ' . round($max_file_size/1000) . 'kb');
          }
        } else {
          osc_add_flash_error_message(__('Logo extension is not allowed, file was not uploaded. Only files with following extensions are allowed', 'business_profile') . ': ' . implode(', ', $allowed_extensions));
        }
      } else {
        osc_add_flash_error_message(__('An error with logo upload has occurred, please try again.', 'business_profile'));
      }
    }

    // Old Upload cover
    /*
    $file_cover = Params::getFiles('bpr-file-cover');
    $allowed_extensions = array('jpg');

    $extension = strtolower(pathinfo($file_cover['name'], PATHINFO_EXTENSION));
    $max_file_size = 8096 * 1000;  //(in bytes)
    $file_size = $file_cover['size'];
    $file_name = $user_id . '.' . $extension;
    $file_name_db = $user_id . '.' . $extension . '?v=' . date('Ymdhis');

    $update_file_name = '';
    if($file_cover['name'] <> '') {
      if( $file_cover['error'] == UPLOAD_ERR_OK) {
        if(in_array($extension, $allowed_extensions)) {
          if($file_size < $max_file_size) {
            if(move_uploaded_file($file_cover['tmp_name'], $cover_uploads_path . $file_name)) {
              $update_file_name = $file_name;
              $update['s_cover'] = $file_name_db;

            } else {
              osc_add_flash_error_message(__('An error with cover upload has occurred, please try again', 'business_profile') );
            }
          } else {
            osc_add_flash_error_message( __('Cover is too big and was not uploaded. Maximum file size is:', 'business_profile') . ' ' . round($max_file_size/1000) . 'kb' );
          }
        } else {
          osc_add_flash_error_message( __('Cover extension is not allowed, file was not uploaded. Only files with following extensions are allowed', 'business_profile') . ': ' . implode(', ', $allowed_extensions) );
        }
      } else {
        osc_add_flash_error_message( __('An error with cover upload has occurred, please try again.', 'business_profile') );
      }
    }
    */
    

    // New upload cover
    $file_cover = Params::getFiles('bpr-file-cover');
    $allowed_extensions = array('png','jpg','jpeg','gif','webp');

    $extension = strtolower(pathinfo($file_cover['name'], PATHINFO_EXTENSION));
    $max_file_size = 16096 * 1000;  //(in bytes)
    $file_size = $file_cover['size'];
    $file_name = $user_id . '_' . mb_generate_rand_string(6) . '.' . $extension;
    // $file_name_db = $user_id . '.' . $extension . '?v=' . date('Ymdhis');
    $cover_uploads_path = UPLOADS_PATH . '/business_profile/' . $user_id . '/cover/';

    $update_file_name = '';
    if($file_cover['name'] <> '') {
      if( $file_cover['error'] == UPLOAD_ERR_OK) {
        if(in_array($extension, $allowed_extensions)) {
          if($file_size < $max_file_size) {
            if(move_uploaded_file($file_cover['tmp_name'], $cover_uploads_path . $file_name)) {
              $update_file_name = $file_name;
              // $update['s_cover'] = $file_name_db;
              $update['s_cover'] = $file_name;

            } else {
              osc_add_flash_error_message(__('An error with cover upload has occurred, please try again', 'business_profile'));
            }
          } else {
            osc_add_flash_error_message(__('Cover is too big and was not uploaded. Maximum file size is:', 'business_profile') . ' ' . round($max_file_size/1000) . 'kb');
          }
        } else {
          osc_add_flash_error_message(__('Cover extension is not allowed, file was not uploaded. Only files with following extensions are allowed', 'business_profile') . ': ' . implode(', ', $allowed_extensions));
        }
      } else {
        osc_add_flash_error_message(__('An error with cover upload has occurred, please try again.', 'business_profile'));
      }
    }



    // UPLOAD GALLERY IMAGES
    if(bpr_param('gallery') == 1) {
      $files = (isset($_FILES['bpr-files-gallery']) ? $_FILES['bpr-files-gallery'] : array());
      $count = count(is_array(@$files['name']) ? $files['name'] : array());
      $ok = array();
      $max = (bpr_param('gallery_limit') > 0 ? bpr_param('gallery_limit') : 0);
      $uploaded = 0;
      $update['s_gallery'] = $seller['s_gallery'];

      if($max > 0) {
        $uploaded = count($existing);
      }
      
      if($count > 0) {
        for($i=0;$i<$count;$i++) {
          // Upload image
          $allowed_extensions = array('png','jpg','jpeg','gif','webp','avif','svg','ico');
          $extension = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
          $max_file_size = 8096 * 1000;  //(in bytes)
          $file_size = $files['size'][$i];
          $file_name = $files['name'][$i];
          $file_name_saved = mb_generate_rand_string(6) . '.' . $extension;
          
          if($i >= $max - $uploaded && $max > 0) {
            osc_add_flash_error_message(sprintf(__('Limit reached, you can only upload %d images. Only first %d images were uploaded.', 'business_profile'), $max - $uploaded, $max - $uploaded));
            break;
            
          } else if($files['name'][$i] <> '') {
            if($files['error'][$i] == UPLOAD_ERR_OK) {
              if(in_array($extension, $allowed_extensions)) {
                if($file_size < $max_file_size) {
                  if(move_uploaded_file($files['tmp_name'][$i], $gallery_uploads_path . $file_name_saved ) ) {
                    $ok[] = $files['name'][$i];
                    $update['s_gallery'] .= ($update['s_gallery'] <> '' ? ',' : '') . $file_name_saved;
                  } else {
                    osc_add_flash_error_message(sprintf(__('An error with gallery image upload has occurred, please try again (%s)', 'business_profile'), $file_name));
                  }
                } else {
                  osc_add_flash_error_message(sprintf(__('Gallery image (%s) is too big and was not uploaded. Maximum file size is: %s', 'business_profile'), $file_name, round($max_file_size/1000) . 'kb'));
                }
              } else {
                osc_add_flash_error_message(sprintf(__('Gallery image extension is not allowed, file was not uploaded (%s). Only files with following extensions are allowed: %s', 'business_profile'), $file_name, implode(',', $allowed_extensions)));
              }
            } else {
              osc_add_flash_error_message(sprintf(__('An error with gallery image upload has occurred, please try again (%s).', 'business_profile'), $file_name));
            }
          }
        }
      }
      
      if(count($ok) > 0) {
        osc_add_flash_ok_message(sprintf(__('Gallery images successfully uploaded: %s', 'business_profile'), implode(', ', $ok)));
      }
    }


    ModelBPR::newInstance()->updateSellerData($update);
    osc_add_flash_ok_message(__('Business profile updated', 'business_profile'));
    header('Location:' . osc_route_url('bpr-profile'));
    exit;
  }



  // SHOW DATA
  View::newInstance()->_exportVariableToView('user', $user); 
  $location = implode(', ', array_filter(array($user['s_country'], $user['s_region'], $user['s_city'], $user['s_zip'], $user['s_address'])));
  $phone = implode('<br/>', array_filter(array(osc_user_phone_land(), osc_user_phone_mobile())));

  $icon = bpr_get_img($user_id, @$seller['s_icon'], 'icon');
  $logo = bpr_get_img($user_id, @$seller['s_logo'], 'logo');
  $cover = bpr_get_img($user_id, @$seller['s_cover'], 'cover');

  if(!isset($seller['pk_i_id'])) {
    $exists = false;
  } else {
    $exists = true;
  }

  // OPENING HOURS
  // [x] delimit value of parameter, [y] delimit rows
  $hours = array();
  $hrs = array_filter(explode('[y]', isset($seller['s_hours']) ? (string)$seller['s_hours'] : ''));

  if(count($hrs) > 0) {
    foreach($hrs as $h) {
      $data = array_filter(explode('[x]', $h));

      if(isset($data[0]) && isset($data[1])) {
        $hours[$data[0]] = $data[1];
      }
    }
  }


  // SOCIAL NETWORKS
  // [x] delimit value of parameter, [y] delimit rows
  $soc_list = array_filter(array_map('trim', explode(';', BPR_SOCIALS)));
  $socials = array();
  $soc = array_filter(array_map('trim', explode('[y]', isset($seller['s_socials']) ? (string)$seller['s_socials'] : '')));

  if(count($soc) > 0) {
    foreach($soc as $s) {
      $data = array_filter(explode('[x]', $s));

      if(isset($data[0]) && isset($data[1])) {
        $socials[$data[0]] = $data[1];
      }
    }
  }

  
  // VIDEO LIST
  $video_list = array_filter(array_map('trim', explode(',', isset($seller['s_videos']) ? (string)$seller['s_videos'] : '')));

  // PRIMARY CATEGORIES
  $categories = array_filter(array_map('trim', explode(';', isset($seller['s_category_ids']) ? (string)$seller['s_category_ids'] : '')));


  // FEATURES
  $feat_list = ModelBPR::newInstance()->getValues('feature');
  $features = array_filter(array_map('trim', explode(',', isset($seller['s_features']) ? (string)$seller['s_features'] : '')));



  // PAYMENTS
  $pay_list = ModelBPR::newInstance()->getValues('payment');
  $payments = array_filter(array_map('trim', explode(',', isset($seller['s_payments']) ? (string)$seller['s_payments'] : '')));

?>


<div id="bpr-prof" class="bpr-body bpr-prof">
  <?php if(!isset($seller['pk_i_id']) || $seller['pk_i_id'] <= 0) { ?>
    <div class="bpr-msg-wrap"><div class="bpr-msg"><?php _e('Create and submit your business profile to attract more customers. It just take 3 minutes!', 'business_profile'); ?></div></div>
  <?php } else if(bpr_param('require_validation') == 1 && bpr_param('auto_validate') != 1) { ?>
    <?php if(!isset($seller['pk_i_id']) || $seller['pk_i_id'] <= 0) { ?>
      <div class="bpr-msg-wrap"><div class="bpr-msg"><?php _e('When your business profile is submitted, it must be approved by admin', 'business_profile'); ?></div></div>
    <?php } else if($seller['b_enabled'] == 0) { ?>
      <div class="bpr-msg-wrap"><div class="bpr-msg"><?php _e('Your business profile is pending validation by admin', 'business_profile'); ?></div></div>
    <?php } ?>
  <?php } ?>

  <div class="bpr-inside">
    <h2><?php _e('Business profile', 'business_profile'); ?></h2>

    <?php if(bpr_company_url($user_id) !== false) { ?>
      <a class="bpr-show-profile" href="<?php echo bpr_company_url($user_id); ?>"><?php _e('Show profile', 'business_profile'); ?></a>
    <?php } else { ?>
      <a class="bpr-show-profile bpr-disabled bpr-has-tooltip" href="#" title="<?php echo osc_esc_html(__('You have not submitted your profile yet', 'business_profile')); ?>"><?php _e('Show profile', 'business_profile'); ?> (<?php _e('not available', 'business_profile'); ?>)</a>
    <?php } ?>
    
    <form class="nocsrf" method="POST" name="bpr_profile" id="bpr_profile" action="<?php echo osc_route_url('bpr-profile'); ?>" enctype="multipart/form-data">
      <input type="hidden" name="what" value="profile" />
      <input type="hidden" name="user_id" value="<?php echo osc_logged_user_id(); ?>" />
      <input type="hidden" name="seller_id" value="<?php echo @$seller['pk_i_id']; ?>" />


      <?php if(isset($seller['pk_i_id']) && $seller['pk_i_id'] > 0 && $seller['b_enabled'] != 1 && !bpr_control_user($seller) && bpr_premium_groups() !== false && is_array(bpr_premium_groups()) && count(bpr_premium_groups()) > 0) { ?>
        <div class="bpr-is-premium">
          <div class="bpr-p-title"><?php _e('In order to enable your profile, you must purchase membership in one of the following groups:', 'business_profile'); ?></div>

          <div class="bpr-grp-wrap">
            <?php foreach(bpr_premium_groups() as $g) { ?>
              <a class="bpr-grp bpr-has-tooltip" href="<?php echo osp_cart_add(OSP_TYPE_MEMBERSHIP, 1, $g['pk_i_id'], $g['i_days']); ?>" title="<?php echo osc_esc_html(__('Click to add membership to cart', 'business_profile')); ?>">
                <div class="bpr-grp-i" style="background:<?php echo $g['s_color']; ?>"></div>
                <i class="bpr-grp-fa fa fa-shopping-basket"></i>
                <div class="bpr-grp-t"><?php echo $g['s_name']; ?></div>
                <div class="bpr-grp-d"><?php echo sprintf(__('%s for %s days', 'business_profile'), osp_format_price($g['f_price']), $g['i_days']); ?></div>
              </a>
            <?php } ?>
          </div>

          <a class="bpr-prem-more" href="<?php echo osc_route_url('osp-membership'); ?>"><?php _e('More options', 'business_profile'); ?> <i class="fa fa-angle-double-right"></i></a>

          <?php if(bpr_param('auto_validation') == 0) { ?>
            <div class="bpr-p-note">* <?php _e('Profile must be validated by admin before it is public', 'business_profile'); ?></div>
          <?php } ?>
        </div>
      <?php } ?>


      <div class="bpr-row-wrap">
        <div class="bpr-row bpr-row-inf">
          <label>
            <span><?php _e('Name', 'business_profile'); ?></span>
            <i class="fa fa-info-circle bpr-has-tooltip" title="<?php echo osc_esc_html(__('This value can be changed in your basic profile', 'business_profile')); ?>"></i>
          </label>

          <strong><?php echo osc_user_name(); ?></strong>
        </div>

        <div class="bpr-row bpr-row-inf">
          <label>
            <span><?php _e('Location', 'business_profile'); ?></span>
            <i class="fa fa-info-circle bpr-has-tooltip" title="<?php echo osc_esc_html(__('This value can be changed in your basic profile', 'business_profile')); ?>"></i>
          </label>

          <strong><?php echo ($location == '' ? __('Not filled', 'business_profile') : $location); ?></strong>
        </div>

        <div class="bpr-row bpr-row-inf">
          <label>
            <span><?php _e('Phone', 'business_profile'); ?></span>
            <i class="fa fa-info-circle bpr-has-tooltip" title="<?php echo osc_esc_html(__('This value can be changed in your basic profile', 'business_profile')); ?>"></i>
          </label>

          <strong><?php echo ($phone == '' ? __('Not filled', 'business_profile') : $phone); ?></strong>
        </div>

        <div class="bpr-row bpr-row-inf">
          <label>
            <span><?php _e('About', 'business_profile'); ?></span>
            <i class="fa fa-info-circle bpr-has-tooltip" title="<?php echo osc_esc_html(__('This value can be changed in your basic profile', 'business_profile')); ?>"></i>
          </label>

          <strong><?php echo (osc_user_info() == '' ? __('Not filled', 'business_profile') : osc_user_info()); ?></strong>
        </div>

        <div class="bpr-row bpr-row-inf">
          <label>
            <span><?php _e('Type', 'business_profile'); ?></span>
            <i class="fa fa-info-circle bpr-has-tooltip" title="<?php echo osc_esc_html(__('This value can be change only admin or by membership in group', 'business_profile')); ?>"></i>
          </label>

          <strong><?php echo bpr_user_type($seller); ?></strong>
        </div>

        <div class="bpr-row bpr-row-inf">
          <label>
            <span><?php _e('Verified', 'business_profile'); ?></span>
            <i class="fa fa-info-circle bpr-has-tooltip" title="<?php echo osc_esc_html(__('This value can be change only admin', 'business_profile')); ?>"></i>
          </label>

          <strong><?php echo (@$seller['b_verified'] == 1 ? __('Yes', 'business_profile') : __('No', 'business_profile')); ?></strong>
        </div>
      </div>


      <div class="bpr-row bpr-row-icon">
        <div class="bpr-utitle"><?php _e('Square logo (100x100 px)', 'business_profile'); ?></div>

        <div class="bpr-inputs">
          <div class="bpr-preview">
            <img src="<?php echo $icon; ?>" />
          </div>

          <div class="bpr-attachment">
            <div class="bpr-att-box">
              <label class="bpr-status">
                <span class="bpr-wrap"><i class="fa fa-paperclip"></i> <span><?php _e('Upload your square logo', 'business_profile'); ?></span></span>
                <input type="file" name="bpr-file-icon" id="bpr-file-icon" class="bpr-file-icon" />
              </label>
            </div>
          </div>

          <?php if(bpr_check_img($user_id, @$seller['s_icon'], 'icon')) { ?>
            <a href="<?php echo osc_route_url('bpr-profile-remove-img', array('removeImageType' => 'icon')); ?>" class="bpr-rem-img"><?php _e('Remove', 'business_profile'); ?></a>
          <?php } ?>
        </div>
      </div>


      <div class="bpr-row bpr-row-logo">
        <div class="bpr-utitle"><?php _e('Regular logo (300x100 px)', 'business_profile'); ?></div>

        <div class="bpr-inputs">
          <div class="bpr-preview">
            <img src="<?php echo $logo; ?>" />
          </div>

          <div class="bpr-attachment">
            <div class="bpr-att-box">
              <label class="bpr-status">
                <span class="bpr-wrap"><i class="fa fa-paperclip"></i> <span><?php _e('Upload your regular logo', 'business_profile'); ?></span></span>
                <input type="file" name="bpr-file-logo" id="bpr-file-logo" class="bpr-file-logo" />
              </label>
            </div>
          </div>

          <?php if(bpr_check_img($user_id, @$seller['s_logo'], 'logo')) { ?>
            <a href="<?php echo osc_route_url('bpr-profile-remove-img', array('removeImageType' => 'logo')); ?>" class="bpr-rem-img"><?php _e('Remove', 'business_profile'); ?></a>
          <?php } ?>
        </div>
      </div>
      
      
      <div class="bpr-row bpr-row-cover">
        <div class="bpr-utitle"><?php _e('Company cover (1600x600 px)', 'business_profile'); ?></div>

        <div class="bpr-inputs">
          <div class="bpr-preview">
            <img src="<?php echo $cover; ?>" />
          </div>

          <div class="bpr-attachment">
            <div class="bpr-att-box">
              <label class="bpr-status">
                <span class="bpr-wrap"><i class="fa fa-paperclip"></i> <span><?php _e('Upload your cover', 'business_profile'); ?></span></span>
                <input type="file" name="bpr-file-cover" id="bpr-file-cover" class="bpr-file-cover" />
              </label>
            </div>
          </div>

          <?php if(bpr_check_img($user_id, @$seller['s_cover'], 'cover')) { ?>
            <a href="<?php echo osc_route_url('bpr-profile-remove-img', array('removeImageType' => 'cover')); ?>" class="bpr-rem-img"><?php _e('Remove', 'business_profile'); ?></a>
          <?php } ?>
        </div>
      </div>


      <?php if(bpr_param('gallery') == 1) { ?>
        <?php
          $gallery = bpr_prepare_user_gallery($seller);
        ?>
        <div class="bpr-row bpr-row-gallery">
          <div class="bpr-utitle"><?php echo sprintf(__('Gallery images (max. %d)', 'business_profile'), bpr_param('gallery_limit')); ?></div>

          <div class="bpr-inputs">
            <div class="bpr-preview bpr-gal">
              <?php if(count($gallery) > 0) { ?>
                <?php foreach($gallery as $img) { ?>
                  <div class="img">
                    <a href="<?php echo osc_route_url('bpr-profile-remove-gallery-img', array('removeGalleryImage' => $img['name'])); ?>" class="del"><i class="fa fa-trash"></i></a>
                    <a class="limg" href="<?php echo $img['url']; ?>" ><img src="<?php echo $img['url']; ?>" alt="<?php echo osc_esc_html($img['title']); ?>"/></a>
                  </div>
                <?php } ?>
              <?php } else { ?>
                <em><?php _e('No gallery images has been uploaded yet.', 'business_profile'); ?></em>
              <?php } ?>
            </div>
            
            <div class="bpr-attachment">
              <div class="bpr-att-box">
                <label class="bpr-status">
                  <span class="bpr-wrap"><i class="fa fa-paperclip"></i> <span><?php _e('Upload new gallery images', 'business_profile'); ?></span></span>
                  <input type="file" name="bpr-files-gallery[]" id="bpr-files-gallery" class="bpr-files-gallery" multiple/>
                </label>
              </div>
            </div>
          </div>
        </div>
      <?php } ?>
      

      <?php if(bpr_param('video') == 1 && bpr_param('video_limit') > 0) { ?>
        <div class="bpr-row bpr-row-vd">
          <div class="bpr-utitle"><?php _e('Youtube Videos', 'business_profile'); ?></div>
          <div class="bpr-usubtitle"><?php _e('Add URLs (share/embedded) or IDs of your Youtube videos to your profile', 'business_profile'); ?></div>

          <div class="bpr-inputs">
            <?php for($i=1;$i<=bpr_param('video_limit');$i++) { ?>
              <div class="bpr-line">
                <span><?php echo sprintf(__('Video #%d', 'business_profile'), $i); ?></span>
                <input type="text" class="bpr-input bpr-video" name="bpr-video_<?php echo $i; ?>" value="<?php echo (isset($video_list[$i-1]) ? $video_list[$i-1] : ''); ?>" placeholder="https://www.youtube.com/embed/xxyyzz"/>
              </div>
            <?php } ?>
          </div>
        </div>
      <?php } ?>
      
      
      <div class="bpr-row bpr-row-clr">
        <div class="bpr-utitle"><?php _e('Company color (hex)', 'business_profile'); ?></div>

        <div class="bpr-inputs">
          <input type="text" name="bpr-color" id="bpr-color" value="<?php echo @$seller['s_color']; ?>" placeholder="#03a9f4"/>
        </div>
      </div>


      <div class="bpr-row bpr-row-cat">
        <div class="bpr-utitle"><?php _e('Primary categories', 'business_profile'); ?></div>

        <div class="bpr-row-select-multiple">
          <input type="hidden" name="bpr-category" id="bpr-category" value="<?php echo @$seller['s_category_ids']; ?>"/>

          <div class="bpr-select-cat">
            <?php osc_goto_first_category(); ?>
            <?php while(osc_has_categories()) { ?>

              <div class="bpr-box-check bpr-check-parent" data-parent="0">
                <input type="checkbox" id="bpr-cat-<?php echo osc_category_id(); ?>" value="<?php echo osc_category_id(); ?>" <?php if(in_array(osc_category_id(), $categories)) { ?>checked<?php } ?>>
                <label for="bpr-cat-<?php echo osc_category_id(); ?>"><?php echo osc_category_name(); ?></label>
              </div>

              <?php $parent_id = osc_category_id(); ?>


              <?php while(osc_has_subcategories()) { ?>
                <div class="bpr-box-check bpr-check-child" data-parent="<?php echo $parent_id; ?>">
                  <input type="checkbox" id="bpr-cat-<?php echo osc_category_id(); ?>" value="<?php echo osc_category_id(); ?>" <?php if(in_array(osc_category_id(), $categories)) { ?>checked<?php } ?>>
                  <label for="bpr-cat-<?php echo osc_category_id(); ?>"><?php echo osc_category_name(); ?></label>
                </div>
              <?php } ?>
            <?php } ?>
          </div>
        </div>
      </div>


      <div class="bpr-row bpr-row-oh">
        <div class="bpr-utitle"><?php _e('Opening hours', 'business_profile'); ?></div>

        <div class="bpr-inputs">
          <?php for ($d = 1; $d <= 7; $d++) { ?>
            <div class="bpr-line">
              <span><?php echo bpr_day($d, 'long'); ?></span>
              <input type="text" class="bpr-input bpr-oh" name="bpr-oh_<?php echo $d; ?>" value="<?php echo (isset($hours[$d]) ? $hours[$d] : ''); ?>" placeholder="<?php echo osc_esc_html(__('From - To', 'business_profile')); ?>"/>
            </div>
          <?php } ?>
        </div>
      </div>

      <div class="bpr-row bpr-row-sc">
        <div class="bpr-utitle"><?php _e('Social network links', 'business_profile'); ?></div>

        <div class="bpr-inputs">
          <?php foreach($soc_list as $s) { ?>
            <div class="bpr-line">
              <span><?php echo bpr_soc_icon($s, 'text'); ?></span>
              <input type="text" class="bpr-input bpr-soc" name="bpr-soc_<?php echo $s; ?>" value="<?php echo (isset($socials[$s]) ? $socials[$s] : ''); ?>" placeholder="http://"/>
            </div>
          <?php } ?>
        </div>
      </div>

      <div class="bpr-row">
        <div class="bpr-utitle"><?php _e('Features provided by you', 'business_profile'); ?></div>

        <div class="bpr-inputs">
          <?php foreach($feat_list as $f) { ?>
            <div class="bpr-box-check">
              <input type="checkbox" class="bpr-input-check bpr-feat" name="bpr-feat_<?php echo $f['pk_i_id']; ?>" id="bpr-feat_<?php echo $f['pk_i_id']; ?>" value="1" <?php if(in_array($f['pk_i_id'], $features)) { ?>checked="checked"<?php } ?>/>
              <label for="bpr-feat_<?php echo $f['pk_i_id']; ?>"><?php echo bpr_field($f['locales']); ?></label>
            </div>
          <?php } ?>
        </div>
      </div>

      <div class="bpr-row">
        <div class="bpr-utitle"><?php _e('Supported payments', 'business_profile'); ?></div>

        <div class="bpr-inputs">
          <?php foreach($pay_list as $p) { ?>
            <div class="bpr-box-check">
              <input type="checkbox" class="bpr-input-check bpr-pay" name="bpr-pay_<?php echo $p['pk_i_id']; ?>" id="bpr-pay_<?php echo $p['pk_i_id']; ?>" value="1" <?php if(in_array($p['pk_i_id'], $payments)) { ?>checked="checked"<?php } ?>/>
              <label for="bpr-pay_<?php echo $p['pk_i_id']; ?>"><?php echo bpr_field($p['locales']); ?></label>
            </div>
          <?php } ?>
        </div>
      </div>

      <?php if(bpr_param('legal_notice') == 1) { ?>
        <div class="bpr-row bpr-row-clr">
          <div class="bpr-utitle"><?php _e('Legal notice', 'business_profile'); ?></div>
          <div class="bpr-usubtitle"><?php _e('Shown under each listing you publish', 'business_profile'); ?></div>

          <div class="bpr-inputs">
            <textarea name="bpr-legal_notice" id="bpr-legal_notice"><?php echo @$seller['s_legal_notice']; ?></textarea>
          </div>
        </div>
      <?php } ?>
      
      <div class="bpr-row bpr-row-buttons">
        <?php if(bpr_is_demo()) { ?>
          <button class="bpr-btn bpr-disabled" disabled type="submit" onclick="return false;" title="This is demo site, you cannot update profile"><?php _e('Submit', 'business_profile'); ?></button>
        <?php } else { ?>
          <button class="bpr-btn" type="submit"><?php _e('Submit', 'business_profile'); ?></button>
          
          <?php if(bpr_param('user_can_remove_profile') == 1) { ?>
            <a href="<?php echo osc_route_url('bpr-profile-remove'); ?>" class="bpr-btn bpr-btn-alt" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to remove your business profile? Action cannot be undone')); ?>');"><?php _e('Remove', 'business_profile'); ?></a>
          <?php } ?>
        <?php } ?>
      </div>      
    </form>
  </div>
</div>