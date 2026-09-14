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
    // Sync core user fields from redesigned form (name, about, contact, address)
    if (Params::existParam('bpr-business-name') || Params::existParam('bpr-about') || Params::existParam('pngm_phone_local')) {
      $user_upd = array();
      $name = trim((string) Params::getParam('bpr-business-name'));
      if ($name !== '') {
        $user_upd['s_name'] = $name;
      }
      $phone_dial = preg_replace('/\D+/', '', (string) Params::getParam('pngm_phone_dial'));
      $phone_local = preg_replace('/\D+/', '', (string) Params::getParam('pngm_phone_local'));
      if ($phone_dial === '') {
        $phone_dial = '675';
      }
      if (Params::existParam('pngm_phone_local')) {
        $user_upd['s_phone_mobile'] = ($phone_local !== '' ? ('+' . $phone_dial . $phone_local) : '');
      }
      $website = trim((string) Params::getParam('bpr-website'));
      if (Params::existParam('bpr-website')) {
        $user_upd['s_website'] = $website;
      }
      if (Params::existParam('bpr-address')) {
        $user_upd['s_address'] = trim((string) Params::getParam('bpr-address'));
      }
      if (Params::existParam('bpr-zip')) {
        $user_upd['s_zip'] = trim((string) Params::getParam('bpr-zip'));
      }
      if (Params::existParam('countryId') && Params::getParam('countryId') !== '') {
        $user_upd['fk_c_country_code'] = Params::getParam('countryId');
        $country = Country::newInstance()->findByCode(Params::getParam('countryId'));
        if (is_array($country) && isset($country['s_name'])) {
          $user_upd['s_country'] = $country['s_name'];
        }
      }
      if (Params::existParam('regionId')) {
        $user_upd['fk_i_region_id'] = (int) Params::getParam('regionId');
        $reg = Region::newInstance()->findByPrimaryKey((int) Params::getParam('regionId'));
        if (is_array($reg) && isset($reg['s_name'])) {
          $user_upd['s_region'] = $reg['s_name'];
          if (!empty($reg['fk_c_country_code'])) {
            $user_upd['fk_c_country_code'] = $reg['fk_c_country_code'];
          }
        }
      }
      if (Params::existParam('cityId')) {
        $user_upd['fk_i_city_id'] = (int) Params::getParam('cityId');
        $city = City::newInstance()->findByPrimaryKey((int) Params::getParam('cityId'));
        if (is_array($city) && isset($city['s_name'])) {
          $user_upd['s_city'] = $city['s_name'];
        }
      }
      if (!empty($user_upd)) {
        User::newInstance()->update($user_upd, array('pk_i_id' => $user_id));
      }
      $about = trim((string) Params::getParam('bpr-about'));
      if (Params::existParam('bpr-about')) {
        $locale = osc_current_user_locale();
        User::newInstance()->updateDescription($user_id, $locale, $about);
      }
      // Bank details (no native BPR columns)
      $bank = array(
        'bank_name' => trim((string) Params::getParam('bpr-bank-name')),
        'account_name' => trim((string) Params::getParam('bpr-account-name')),
        'account_number' => trim((string) Params::getParam('bpr-account-number')),
      );
      osc_set_preference('bank_' . $user_id, json_encode($bank), 'plugin-business_profile', 'STRING');
    }

    // Redesigned hours: compose from from/to/open (+ Mon-Fri shared) fields when present
    $pngm_bpr_hours_composed = null;
    if (Params::existParam('bpr-oh-mode')) {
      if (Params::existParam('bpr-oh-from_mf')) {
        $mf_open = Params::getParam('bpr-oh-open_mf');
        $mf_from = Params::getParam('bpr-oh-from_mf');
        $mf_to = Params::getParam('bpr-oh-to_mf');
        for ($d = 1; $d <= 5; $d++) {
          Params::setParam('bpr-oh-open_' . $d, $mf_open);
          Params::setParam('bpr-oh-from_' . $d, $mf_from);
          Params::setParam('bpr-oh-to_' . $d, $mf_to);
        }
      }
      $pngm_bpr_hours_composed = '';
      for ($d = 1; $d <= 7; $d++) {
        $open = Params::getParam('bpr-oh-open_' . $d);
        $from = Params::getParam('bpr-oh-from_' . $d);
        $to = Params::getParam('bpr-oh-to_' . $d);
        if ((string) $open !== '1' || (string) $from === 'Closed') {
          $pngm_bpr_hours_composed .= $d . '[x]Closed[y]';
        } elseif ($from !== '' && $to !== '') {
          $pngm_bpr_hours_composed .= $d . '[x]' . $from . ' - ' . $to . '[y]';
        }
      }
    }

    $post = Params::getParamsAsArray();

    $identifier_source = Params::existParam('bpr-business-name') ? Params::getParam('bpr-business-name') : $user['s_name'];
    $identifier = (osc_sanitizeString($identifier_source) <> '' ? osc_sanitizeString($identifier_source) : $user_id);
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

    if ($pngm_bpr_hours_composed !== null) {
      $u_hours = $pngm_bpr_hours_composed;
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
    if (!is_array($file_icon)) {
      $file_icon = array();
    }
    $allowed_extensions = array('png','jpg','jpeg','jfif','jpe','pjpeg','pjp','gif','webp','bmp','avif');
    $extension = strtolower(pathinfo(isset($file_icon['name']) ? $file_icon['name'] : '', PATHINFO_EXTENSION));
    if (in_array($extension, array('jfif', 'jpe', 'pjpeg', 'pjp'), true)) {
      $extension = 'jpg';
    }
    $max_file_size = 2048 * 1000;  //(in bytes)
    $file_size = isset($file_icon['size']) ? $file_icon['size'] : 0;
    $file_name = $user_id . '_' . mb_generate_rand_string(6) . '.' . $extension;
    $file_name_profile = 'profile' . $user_id . '.' . $extension;
    // $file_name_db = $user_id . '.' . $extension . '?v=' . date('Ymdhis');
    $icon_uploads_path = UPLOADS_PATH . '/business_profile/' . $user_id . '/icon/';

    $update_file_name = '';
    if (!empty($file_icon['name'])) {
      if ($file_icon['error'] == UPLOAD_ERR_OK) {
        if (in_array(strtolower(pathinfo($file_icon['name'], PATHINFO_EXTENSION)), array('png','jpg','jpeg','jfif','jpe','pjpeg','pjp','gif','webp','bmp','avif'), true)) {
          if ($file_size < $max_file_size) {
            if (move_uploaded_file($file_icon['tmp_name'], $icon_uploads_path . $file_name)) {
              $update_file_name = $file_name;
              // $update['s_icon'] = $file_name_db;
              $update['s_icon'] = $file_name;

              if (function_exists('profile_picture_install')) {
                if (copy($icon_uploads_path . $file_name, osc_content_path() . 'plugins/profile_picture/images/' . $file_name_profile)) {
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
    if (!is_array($file_logo)) {
      $file_logo = array();
    }
    $allowed_extensions = array('png','jpg','jpeg','jfif','jpe','pjpeg','pjp','gif','webp','bmp','avif');

    $extension = strtolower(pathinfo(isset($file_logo['name']) ? $file_logo['name'] : '', PATHINFO_EXTENSION));
    if (in_array($extension, array('jfif', 'jpe', 'pjpeg', 'pjp'), true)) {
      $extension = 'jpg';
    }
    $max_file_size = 2048 * 1000;  //(in bytes)
    $file_size = isset($file_logo['size']) ? $file_logo['size'] : 0;
    $file_name = $user_id . '_' . mb_generate_rand_string(6) . '.' . $extension;
    $file_name_profile = 'profile' . $user_id . '.' . $extension;
    // $file_name_db = $user_id . '.' . $extension . '?v=' . date('Ymdhis');
    $logo_uploads_path = UPLOADS_PATH . '/business_profile/' . $user_id . '/logo/';

    $update_file_name = '';
    if (!empty($file_logo['name'])) {
      if ($file_logo['error'] == UPLOAD_ERR_OK) {
        if (in_array(strtolower(pathinfo($file_logo['name'], PATHINFO_EXTENSION)), array('png','jpg','jpeg','jfif','jpe','pjpeg','pjp','gif','webp','bmp','avif'), true)) {
          if ($file_size < $max_file_size) {
            if (move_uploaded_file($file_logo['tmp_name'], $logo_uploads_path . $file_name)) {
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
    if (!is_array($file_cover)) {
      $file_cover = array();
    }
    $allowed_extensions = array('png','jpg','jpeg','jfif','jpe','pjpeg','pjp','gif','webp','bmp','avif');

    $extension_raw = strtolower(pathinfo(isset($file_cover['name']) ? $file_cover['name'] : '', PATHINFO_EXTENSION));
    $extension = $extension_raw;
    if (in_array($extension, array('jfif', 'jpe', 'pjpeg', 'pjp'), true)) {
      $extension = 'jpg';
    }
    $max_file_size = 16096 * 1000;  //(in bytes)
    $file_size = isset($file_cover['size']) ? $file_cover['size'] : 0;
    $file_name = $user_id . '_' . mb_generate_rand_string(6) . '.' . $extension;
    // $file_name_db = $user_id . '.' . $extension . '?v=' . date('Ymdhis');
    $cover_uploads_path = UPLOADS_PATH . '/business_profile/' . $user_id . '/cover/';

    $update_file_name = '';
    if (!empty($file_cover['name'])) {
      if ($file_cover['error'] == UPLOAD_ERR_OK) {
        if (in_array($extension_raw, $allowed_extensions, true)) {
          if ($file_size < $max_file_size) {
            if (!is_dir($cover_uploads_path)) {
              @mkdir($cover_uploads_path, 0755, true);
            }
            if (move_uploaded_file($file_cover['tmp_name'], $cover_uploads_path . $file_name)) {
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
    if (bpr_param('gallery') == 1) {
      $files = (isset($_FILES['bpr-files-gallery']) ? $_FILES['bpr-files-gallery'] : array());
      $count = count(is_array(@$files['name']) ? $files['name'] : array());
      $ok = array();
      $max = (bpr_param('gallery_limit') > 0 ? bpr_param('gallery_limit') : 0);
      $uploaded = 0;
      $update['s_gallery'] = isset($seller['s_gallery']) ? $seller['s_gallery'] : '';

      if ($max > 0) {
        $uploaded = count($existing);
      }

      if (!is_dir($gallery_uploads_path)) {
        @mkdir($gallery_uploads_path, 0755, true);
      }
      
      if ($count > 0) {
        for ($i = 0; $i < $count; $i++) {
          // Upload image
          $allowed_extensions = array('png','jpg','jpeg','jfif','jpe','pjpeg','pjp','gif','webp','avif','bmp','svg','ico');
          $extension_raw = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
          $extension = $extension_raw;
          if (in_array($extension, array('jfif', 'jpe', 'pjpeg', 'pjp'), true)) {
            $extension = 'jpg';
          }
          $max_file_size = 8096 * 1000;  //(in bytes)
          $file_size = $files['size'][$i];
          $file_name = $files['name'][$i];
          $file_name_saved = mb_generate_rand_string(6) . '.' . $extension;
          
          if ($i >= $max - $uploaded && $max > 0) {
            osc_add_flash_error_message(sprintf(__('Limit reached, you can only upload %d images. Only first %d images were uploaded.', 'business_profile'), $max - $uploaded, $max - $uploaded));
            break;
            
          } else if ($files['name'][$i] <> '') {
            if ($files['error'][$i] == UPLOAD_ERR_OK) {
              if (in_array($extension_raw, $allowed_extensions, true)) {
                if ($file_size < $max_file_size) {
                  if (move_uploaded_file($files['tmp_name'][$i], $gallery_uploads_path . $file_name_saved)) {
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
      
      if (count($ok) > 0) {
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

<?php
$pngm_bpr_form = WebThemes::newInstance()->getCurrentThemePath() . 'includes/bpr_profile_form.php';
if (file_exists($pngm_bpr_form)) {
  include $pngm_bpr_form;
} else {
  echo '<div class="bpr-msg-wrap"><div class="bpr-msg">Business profile form is unavailable.</div></div>';
}

