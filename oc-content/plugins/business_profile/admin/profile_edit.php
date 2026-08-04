<?php
  $id = Params::getParam('profileId');

  // Create menu
  $title = ($id > 0 ? __('Edit profile', 'business_profile') : __('Create profile', 'business_profile'));
  bpr_menu($title);

  
  if(Params::getParam('plugin_action') == 'done') {
    $profile = ModelBPR::newInstance()->getSeller($id);
    $user_id = (isset($profile['fk_i_user_id']) ? $profile['fk_i_user_id'] : 0);

    $identifier = (osc_sanitizeString(Params::getParam('s_identifier')) <> '' ? osc_sanitizeString(Params::getParam('s_identifier')) : ($user_id > 0 ? $user_id : $id));
    $check = ModelBPR::newInstance()->checkIdentifier($identifier);

    if(isset($check['s_identifier']) && $check['fk_i_user_id'] <> $user_id) {
      $identifier = $identifier . ($user_id > 0 ? $user_id : $id);
    }
    
    if($identifier == '') {
      $identifier = $id;
    }
    
    $check = ModelBPR::newInstance()->getSellerByIdentifier($identifier);
    
    if(isset($check['pk_i_id']) && $check['pk_i_id'] != $id) {
      $identifier = $id;
      osc_add_flash_warning_message(__('Identifier was not unique and changed to profile ID.', 'business_profile'), 'admin');
    } else if(isset($check['pk_i_id'])) {
      $user_id = $check['fk_i_user_id'];
    }
    
    $check2 = ModelBPR::newInstance()->getSellerByUserId(Params::getParam('fk_i_user_id'));
    
    if(isset($check2['pk_i_id']) && $check2['pk_i_id'] != $id) {
      osc_add_flash_warning_message(__('This osclass user already has business profile. You have been redirected to this profile. No updates has been performed.', 'business_profile'), 'admin');
      header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/profile_edit.php&profileId=' . $check2['pk_i_id']);
      exit;
    }
    
    
    $post = Params::getParamsAsArray();
    
    if(Params::getParam('fk_i_user_id') > 0) {
      $user_id = Params::getParam('fk_i_user_id');
    }

    if($user_id <= 0) {
      osc_add_flash_error_message(__('Osclass user ID is invalid, user was not found. Please select valid Osclass user from "User" field.', 'business_profile'), 'admin');
      header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/profile_edit.php&profileId=' . $id);
      exit;
    }
    
    $data = array(
      'fk_i_user_id' => $user_id,
      's_identifier' => $identifier,
      'b_enabled' => (Params::getParam('b_enabled') == 'on' ? 1 : 0),
      'b_verified' => (Params::getParam('b_verified') == 'on' ? 1 : 0),
      'i_type' => Params::getParam('i_type'),
      's_color' => Params::getParam('s_color'),
      's_features' => Params::getParam('s_features'),
      's_payments' => Params::getParam('s_payments'),
      's_category_ids' => Params::getParam('s_category_ids'),
      's_legal_notice' => strip_tags(Params::getParam('s_legal_notice'))
    );


    // Opening hours
    $u_hours = '';
    foreach($post as $n => $v) {
      $param = explode('_', $n);
      
      if($param[0] == 'oh' && $v <> '') {
        $u_hours .= $param[1] . '[x]' . $v . '[y]';
      }
    }
 
    $data['s_hours'] = $u_hours;


    // Social networks
    $u_social = '';
    foreach($post as $n => $v) {
      $param = explode('_', $n);
      
      if($param[0] == 'soc' && $v <> '') {
        $u_social .= $param[1] . '[x]' . $v . '[y]';
      }
    }

    $data['s_socials'] = $u_social;
    

    // Youtube videos
    if(bpr_param('video') == 1 && bpr_param('video') > 0) {
      $u_video = array();
      foreach($post as $n => $v) {
        $param = explode('_', $n);

        if($param[0] == 'bpr-video' && $v <> '') {
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
            osc_add_flash_error_message(sprintf(__('Wrong youtube video (%s). It must be share or embeded url, or video ID.', 'business_profile'), $v), 'admin');
          }
        }
      }

      $u_video = array_filter(array_unique($u_video));
      $data['s_videos'] = implode(',', $u_video);
    }
    
    
    // Upload icon
    $file_icon = Params::getFiles('s_icon');
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
          if($file_size < $max_file_size) {
            if(move_uploaded_file($file_icon['tmp_name'], $icon_uploads_path . $file_name)) {
              $update_file_name = $file_name;
              // $data['s_icon'] = $file_name_db;
              $data['s_icon'] = $file_name;

              if(function_exists('profile_picture_install')) {
                if(copy(osc_content_path() . 'plugins/business_profile/img/icon/' . $file_name, osc_content_path() . 'plugins/profile_picture/images/' . $file_name_profile)) {
                  ModelBPR::newInstance()->updateProfilePicture($user_id);
                }
              }

            } else {
              osc_add_flash_error_message(__('An error with square logo upload has occurred, please try again', 'business_profile'), 'admin');
            }
          } else {
            osc_add_flash_error_message(__('Square logo is too big and was not uploaded. Maximum file size is:', 'business_profile') . ' ' . round($max_file_size/1000) . 'kb', 'admin');
          }
        } else {
          osc_add_flash_error_message(__('Square logo extension is not allowed, file was not uploaded. Only files with following extensions are allowed', 'business_profile') . ': ' . implode(', ', $allowed_extensions), 'admin');
        }
      } else {
        osc_add_flash_error_message(__('An error with square logo upload has occurred, please try again.', 'business_profile'), 'admin');
      }
    }


    // Upload logo
    $file_logo = Params::getFiles('s_logo');
    $allowed_extensions = array('png','jpg','jpeg','gif','webp');

    $extension = strtolower(pathinfo($file_logo['name'], PATHINFO_EXTENSION));
    $max_file_size = 2048 * 1000;  //(in bytes)
    $file_size = $file_logo['size'];
    $file_name = $user_id . '_' . mb_generate_rand_string(6) . '.' . $extension;
    // $file_name_db = $user_id . '.' . $extension . '?v=' . date('Ymdhis');
    $logo_uploads_path = UPLOADS_PATH . '/business_profile/' . $user_id . '/logo/';

    $update_file_name = '';
    if($file_logo['name'] <> '') {
      if($file_logo['error'] == UPLOAD_ERR_OK) {
        if(in_array($extension, $allowed_extensions)) {
          if($file_size < $max_file_size) {
            if(move_uploaded_file($file_logo['tmp_name'], $logo_uploads_path . $file_name)) {
              $update_file_name = $file_name;
              // $data['s_logo'] = $file_name_db;
              $data['s_logo'] = $file_name;

            } else {
              osc_add_flash_error_message(__('An error with logo upload has occurred, please try again', 'business_profile'), 'admin');
            }
          } else {
            osc_add_flash_error_message(__('Logo is too big and was not uploaded. Maximum file size is:', 'business_profile') . ' ' . round($max_file_size/1000) . 'kb', 'admin');
          }
        } else {
          osc_add_flash_error_message(__('Logo extension is not allowed, file was not uploaded. Only files with following extensions are allowed', 'business_profile') . ': ' . implode(', ', $allowed_extensions), 'admin');
        }
      } else {
        osc_add_flash_error_message(__('An error with logo upload has occurred, please try again.', 'business_profile'), 'admin');
      }
    }
    

    // Upload cover
    $file_cover = Params::getFiles('s_cover');
    $allowed_extensions = array('png','jpg','jpeg','gif','webp');

    $extension = strtolower(pathinfo($file_cover['name'], PATHINFO_EXTENSION));
    $max_file_size = 16096 * 1000;  //(in bytes)
    $file_size = $file_cover['size'];
    $file_name = $user_id . '_' . mb_generate_rand_string(6) . '.' . $extension;
    // $file_name_db = $user_id . '.' . $extension . '?v=' . date('Ymdhis');
    $cover_uploads_path = UPLOADS_PATH . '/business_profile/' . $user_id . '/cover/';

    $update_file_name = '';
    if($file_cover['name'] <> '') {
      if($file_cover['error'] == UPLOAD_ERR_OK) {
        if(in_array($extension, $allowed_extensions)) {
          if($file_size < $max_file_size) {
            if(move_uploaded_file($file_cover['tmp_name'], $cover_uploads_path . $file_name)) {
              $update_file_name = $file_name;
              // $data['s_cover'] = $file_name_db;
              $data['s_cover'] = $file_name;

            } else {
              osc_add_flash_error_message(__('An error with cover upload has occurred, please try again', 'business_profile'), 'admin');
            }
          } else {
            osc_add_flash_error_message(__('Cover is too big and was not uploaded. Maximum file size is:', 'business_profile') . ' ' . round($max_file_size/1000) . 'kb', 'admin');
          }
        } else {
          osc_add_flash_error_message(__('Cover extension is not allowed, file was not uploaded. Only files with following extensions are allowed', 'business_profile') . ': ' . implode(', ', $allowed_extensions), 'admin');
        }
      } else {
        osc_add_flash_error_message(__('An error with cover upload has occurred, please try again.', 'business_profile'), 'admin');
      }
    }


    // Upload gallery images
    $files = (isset($_FILES['s_gallery']) ? $_FILES['s_gallery'] : array());
    $count = count(is_array(@$files['name']) ? $files['name'] : array());
    $ok = array();
    $max = (bpr_param('gallery_limit') > 0 ? bpr_param('gallery_limit') : 0);
    $uploaded = 0;

    bpr_check_upload_dirs($user_id);
    
    $gallery_uploads_path = UPLOADS_PATH . '/business_profile/' . $user_id . '/gallery/';

    $profile = array();
    if($id > 0) {
      $profile = ModelBPR::newInstance()->getSeller($id);
    }

    $data['s_gallery'] = @$profile['s_gallery'];
    
    $existing = bpr_prepare_user_gallery($profile);

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
          osc_add_flash_error_message(sprintf(__('Limit reached, you can only upload %d images. Only first %d images were uploaded.', 'business_profile'), $max - $uploaded, $max - $uploaded), 'admin');
          break;
          
        } else if($files['name'][$i] <> '') {
          if($files['error'][$i] == UPLOAD_ERR_OK) {
            if(in_array($extension, $allowed_extensions)) {
              if($file_size < $max_file_size) {
                if(move_uploaded_file($files['tmp_name'][$i], $gallery_uploads_path . $file_name_saved ) ) {
                  $ok[] = $files['name'][$i];
                  $data['s_gallery'] .= ($data['s_gallery'] <> '' ? ',' : '') . $file_name_saved;
                } else {
                  osc_add_flash_error_message(sprintf(__('An error with gallery image upload has occurred, please try again (%s)', 'business_profile'), $file_name), 'admin');
                }
              } else {
                osc_add_flash_error_message(sprintf(__('Gallery image (%s) is too big and was not uploaded. Maximum file size is: %s', 'business_profile'), $file_name, round($max_file_size/1000) . 'kb'), 'admin');
              }
            } else {
              osc_add_flash_error_message(sprintf(__('Gallery image extension is not allowed, file was not uploaded (%s). Only files with following extensions are allowed: %s', 'business_profile'), $file_name, implode(',', $allowed_extensions)), 'admin');
            }
          } else {
            osc_add_flash_error_message(sprintf(__('An error with gallery image upload has occurred, please try again (%s).', 'business_profile'), $file_name), 'admin');
          }
        }
      }
    }
    
    if(count($ok) > 0) {
      osc_add_flash_ok_message(sprintf(__('Gallery images successfully uploaded: %s', 'business_profile'), implode(', ', $ok)), 'admin');
    }


    if($id == '' || $id == 0) {
      $id = ModelBPR::newInstance()->insertProfile($data);
      osc_add_flash_ok_message(__('Business profile successfully created.', 'business_profile'), 'admin');
    } else {
      ModelBPR::newInstance()->updateProfile($id, $data);
      osc_add_flash_ok_message(__('Business profile successfully updated.', 'business_profile'), 'admin');
    }
    
    
    header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/profile_edit.php&profileId=' . $id);
    exit;
  }
  
  
  // GET PROFILE
  $profile = array();
  if($id <> '' && $id > 0) {
    $profile = ModelBPR::newInstance()->getSeller($id);
  }

  $user_id = @$profile['fk_i_user_id'];


  // REMOVE ICON
  if(Params::getParam('what') == 'removeIcon' && $id > 0 && !bpr_is_demo()) {
    if($profile['s_icon'] != '' && bpr_check_img(@$profile['fk_i_user_id'], @$profile['s_icon'], 'icon')) {
      bpr_remove_user_image($profile, 'icon');
      osc_add_flash_ok_message(__('Square logo successfully removed', 'business_profile'), 'admin');
      header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/profile_edit.php&profileId=' . $id);
      exit;
      
    } else {
      osc_add_flash_error_message(__('Square logo could not be removed', 'business_profile'), 'admin');
      header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/profile_edit.php&profileId=' . $id);
      exit;
    }
  }
  
  
  // REMOVE LOGO
  if(Params::getParam('what') == 'removeLogo' && $id > 0 && !bpr_is_demo()) {
    if($profile['s_logo'] != '' && bpr_check_img(@$profile['fk_i_user_id'], @$profile['s_logo'], 'logo')) {
      bpr_remove_user_image($profile, 'logo');
      osc_add_flash_ok_message(__('Regular logo successfully removed', 'business_profile'), 'admin');
      header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/profile_edit.php&profileId=' . $id);
      exit;
      
    } else {
      osc_add_flash_error_message(__('Regular logo could not be removed', 'business_profile'), 'admin');
      header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/profile_edit.php&profileId=' . $id);
      exit;
    }
  }
  
  
  // REMOVE COVER
  if(Params::getParam('what') == 'removeCover' && $id > 0 && !bpr_is_demo()) {
    if($profile['s_cover'] != '' && bpr_check_img(@$profile['fk_i_user_id'], @$profile['s_cover'], 'cover')) {
      bpr_remove_user_image($profile, 'cover');
      osc_add_flash_ok_message(__('Cover successfully removed', 'business_profile'), 'admin');
      header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/profile_edit.php&profileId=' . $id);
      exit;
      
    } else {
      osc_add_flash_error_message(__('Cover could not be removed', 'business_profile'), 'admin');
      header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/profile_edit.php&profileId=' . $id);
      exit;
    }
  }


  // REMOVE GALLERY IMAGE
  if(Params::getParam('what') == 'removeGalleryImage' && Params::getParam('removeImage') <> '' && $id > 0 && !bpr_is_demo()) {
    $status = bpr_remove_user_gallery_image($profile, Params::getParam('removeImage'));
    
    if($status) {
      osc_add_flash_ok_message(__('Gallery image successfully removed', 'business_profile'), 'admin');
    } else {
      osc_add_flash_error_message(__('Gallery image could not be removed (error)', 'business_profile'), 'admin');
    }
    
    header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/profile_edit.php&profileId=' . $id);
    exit;
  }


  // REMOVE PROFILE
  if(Params::getParam('what') == 'delete' && $id > 0 && !bpr_is_demo()) { 
    $profile = ModelBPR::newInstance()->getSeller($id);
    
    if(isset($profile['fk_i_user_id']) && $profile['fk_i_user_id'] > 0) {
      bpr_removed_user($profile['fk_i_user_id'], false);
    }
    
    ModelBPR::newInstance()->removeProfile($id);
    osc_add_flash_ok_message(__('Business profile successfully removed', 'business_profile'), 'admin');
    header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=business_profile/admin/profiles.php');
    exit;
  }
  
  // OPENING HOURS
  // [x] delimit value of parameter, [y] delimit rows
  $hours = array();
  $hrs = array_filter(explode('[y]', $profile['s_hours'] ?? ''));

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
  $soc = array_filter(array_map('trim', explode('[y]', $profile['s_socials'] ?? '')));

  if(count($soc) > 0) {
    foreach($soc as $s) {
      $data = array_filter(explode('[x]', $s));

      if(isset($data[0]) && isset($data[1])) {
        $socials[$data[0]] = $data[1];
      }
    }
  }
  
  $user = User::newInstance()->findByPrimaryKey(@$profile['fk_i_user_id']);
  
  $categories_all = Category::newInstance()->listAll();
  $categories_array = array_filter(array_map('trim', explode(';', $profile['s_category_ids'] ?? '')));

  $features_all = ModelBPR::newInstance()->getValues('feature');
  $features_array = array_filter(array_map('trim', explode(',', $profile['s_features'] ?? '')));

  $payments_all = ModelBPR::newInstance()->getValues('payment');
  $payments_array = array_filter(array_map('trim', explode(',', $profile['s_payments'] ?? '')));
  
  $icon = bpr_get_img($user_id, @$profile['s_icon'], 'icon');
  $logo = bpr_get_img($user_id, @$profile['s_logo'], 'logo');
  $cover = bpr_get_img($user_id, @$profile['s_cover'], 'cover');
  
  $video_list = array_filter(array_map('trim', explode(',', $profile['s_videos'] ?? '')));
?>

<div class="mb-body">
  <!-- CONFIGURE SECTION -->
  <div class="mb-box mb-profile-detail">
    <div class="mb-head">
      <i class="fa fa-address-card-o"></i> <?php _e('Edit profile', 'business_profile'); ?>
      
      <?php if(@$profile['s_identifier'] != '') { ?>
        <a class="view-front" href="<?php echo osc_route_url('bpr-seller', array('identifier' => $profile['s_identifier'])); ?>" target="_blank"><?php _e('View profile in front', 'business_profile'); ?></a>
      <?php } ?>
    </div>

    <div class="mb-inside">
      <form name="promo_form" id="promo_form" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
        <input type="hidden" name="page" value="plugins" />
        <input type="hidden" name="action" value="renderplugin" />
        <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>profile_edit.php" />
        <input type="hidden" name="plugin_action" value="done" />
        <input type="hidden" name="profileId" value="<?php echo $id; ?>" />

        
        <?php if($id > 0) { ?>
          <div class="mb-row">
            <label for="pk_i_id"><span><?php _e('Profile ID', 'business_profile'); ?></span></label> 
            <input size="10" disabled id="pk_i_id" type="text" value="<?php echo $id; ?>" />

            <div class="mb-explain"><?php _e('Unique ID of this profile. Field is not editable.', 'business_profile'); ?></div>
          </div>
        <?php } ?>

        <div class="mb-row">
          <label for="name"><span><?php _e('User', 'business_profile'); ?></span></label> 
          <input type="hidden" name="fk_i_user_id" value="<?php echo isset($profile['fk_i_user_id']) ? $profile['fk_i_user_id'] : ''; ?>"/>
          <input required size="70" name="name" class="mb-user-lookup" id="name" type="text" value="<?php echo isset($user['s_name']) ? $user['s_name'] : ''; ?>" />

          <div class="mb-explain"><?php _e('Enter user name or email to link this profile to user account.', 'business_profile'); ?></div>
        </div>

        <div class="mb-row">
          <label for="s_identifier"><span><?php _e('Identifier', 'business_profile'); ?></span></label> 
          <input required size="50" name="s_identifier" id="s_identifier" type="text" value="<?php echo isset($profile['s_identifier']) ? $profile['s_identifier'] : ''; ?>" />

          <div class="mb-explain"><?php _e('Unique identifier slug for this profile. Will be used in profile url.', 'business_profile'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="b_enabled"><span><?php _e('Enabled', 'business_profile'); ?></span></label> 
          <input name="b_enabled" id="b_enabled" type="checkbox" class="element-slide" <?php echo (@$profile['b_enabled'] == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('When enabled, profile will be visibile in front.', 'business_profile'); ?></div>
        </div>

        <div class="mb-row">
          <label for="b_verified"><span><?php _e('Verified', 'business_profile'); ?></span></label> 
          <input name="b_verified" id="b_verified" type="checkbox" class="element-slide" <?php echo (@$profile['b_verified'] == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('Mark profile as verified. This information is visible in front and profile get verified badge.', 'business_profile'); ?></div>
        </div>

        <div class="mb-row">
          <label for="i_type"><span><?php _e('Type', 'business_profile'); ?></span></label> 
          <select id="i_type" name="i_type">
            <option value="1" <?php if(@$profile['i_type'] == 1) { ?>selected="selected"<?php } ?>><?php _e('Basic', 'business_profile'); ?></option>
            <option value="2" <?php if(@$profile['i_type'] == 2) { ?>selected="selected"<?php } ?>><?php _e('Pro', 'business_profile'); ?></option>
            <option value="3" <?php if(@$profile['i_type'] == 3) { ?>selected="selected"<?php } ?>><?php _e('VIP', 'business_profile'); ?></option>
          </select>

          <div class="mb-explain"><?php _e('Select type/level for profile.', 'business_profile'); ?></div>
        </div>

        <div class="mb-row mb-color-box">
          <label for="s_color"><span><?php _e('Color', 'business_profile'); ?></span></label> 
      
          <input name="s_color" id="s_color" size="20" type="text" value="<?php echo osc_esc_html(@$profile['s_color']); ?>" />
          <span class="color-wrap">
            <input name="color-picker" id="" type="color" value="<?php echo osc_esc_html(@$profile['s_color']); ?>" />
          </span>
          <div class="mb-explain"><?php _e('Enter company brand color. Enter color in HEX format or select color with picker. Example: #f29c12', 'business_profile'); ?></div>
        </div>


        <div class="mb-row">
          <label for="s_icon"><span><?php _e('Square logo', 'business_profile'); ?></span></label> 
          <div class="mb-elems">
            <div class="img-wrap">
              <img src="<?php echo $icon; ?>" class="img-logo img-lc"/>
              
              <?php if(!bpr_is_demo() && bpr_check_img(@$profile['fk_i_user_id'], @$profile['s_icon'], 'icon')) { ?>
                <a class="mb-rem mb-has-tooltip-light" href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=business_profile/admin/profile_edit.php&what=removeIcon&profileId=<?php echo $id; ?>" title="<?php echo osc_esc_html(__('Remove', 'business_profile')); ?>"><i class="fa fa-trash-o"></i></a>
              <?php } ?>
            </div>
            <input type="file" name="s_icon" id="s_icon" class="icon-img" />
          </div>
          
          <div class="mb-explain"><?php _e('Recommended size is 100x100 px. Allowed extensions: jpg, jpeg, png, gif, webp.', 'business_profile'); ?></div>
        </div>
        

        <div class="mb-row">
          <label for="s_logo"><span><?php _e('Regular logo', 'business_profile'); ?></span></label> 
          <div class="mb-elems">
            <div class="img-wrap">
              <img src="<?php echo $logo; ?>" class="img-logo img-lc"/>
              
              <?php if(!bpr_is_demo() && bpr_check_img(@$profile['fk_i_user_id'], @$profile['s_logo'], 'logo')) { ?>
                <a class="mb-rem mb-has-tooltip-light" href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=business_profile/admin/profile_edit.php&what=removeLogo&profileId=<?php echo $id; ?>" title="<?php echo osc_esc_html(__('Remove', 'business_profile')); ?>"><i class="fa fa-trash-o"></i></a>
              <?php } ?>
            </div>
            <input type="file" name="s_logo" id="s_logo" class="logo-img" />
          </div>
          
          <div class="mb-explain"><?php _e('Recommended size is 300x100 px. Allowed extensions: jpg, jpeg, png, gif, webp.', 'business_profile'); ?></div>
        </div>
        
        
        <div class="mb-row">
          <label for="s_cover"><span><?php _e('Cover image', 'business_profile'); ?></span></label> 

          <div class="mb-elems">
            <div class="img-wrap">
              <img src="<?php echo $cover; ?>" class="img-cover img-lc" />

              <?php if(!bpr_is_demo() && bpr_check_img(@$profile['fk_i_user_id'], @$profile['s_cover'], 'cover')) { ?>
                <a class="mb-rem mb-has-tooltip-light" href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=business_profile/admin/profile_edit.php&what=removeCover&profileId=<?php echo $id; ?>" title="<?php echo osc_esc_html(__('Remove', 'business_profile')); ?>"><i class="fa fa-trash-o"></i></a>
              <?php } ?>
            </div>
            <input type="file" name="s_cover" id="s_cover" class="cover-img" />
          </div>
          
          <div class="mb-explain"><?php _e('Recommended size is 1600x600 px. Allowed extensions: jpg, jpeg, png, gif, webp.', 'business_profile'); ?></div>
        </div>
        
        
        <div class="mb-row">
          <label for="s_gallery"><span><?php _e('Gallery images', 'business_profile'); ?></span></label> 

          <div class="mb-elems">
            <div id="mb-gallery">
              <?php $gallery = bpr_prepare_user_gallery($profile); ?>
              
              <?php if(count($gallery) > 0) { ?>
                <?php foreach($gallery as $img) { ?>
                  <div class="img">
                    <?php if(!bpr_is_demo()) { ?>
                      <a href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=business_profile/admin/profile_edit.php&profileId=<?php echo $id; ?>&what=removeGalleryImage&removeImage=<?php echo $img['name']; ?>" class="del"><i class="fa fa-trash"></i></a>
                    <?php } ?>
    
                    <a class="limg" href="<?php echo $img['url']; ?>" ><img src="<?php echo $img['url']; ?>" alt="<?php echo osc_esc_html($img['title']); ?>"/></a>
                  </div>
                <?php } ?>
              <?php } else { ?>
                <em><?php _e('No gallery images has been uploaded yet.', 'business_profile'); ?></em>
              <?php } ?>
            </div>
            <input type="file" name="s_gallery[]" id="s_gallery" class="cover-img" multiple/>
          </div>
          
          <div class="mb-explain"><?php _e('Allowed extensions: jpg, jpeg, png, gif, webp...', 'business_profile'); ?></div>
        </div>


        <div class="mb-row mb-row-select-multiple">
          <label for="s_category_ids_multiple"><span><?php _e('Primary Categories', 'business_profile'); ?></span></label> 

          <input type="hidden" name="s_category_ids" id="s_category_ids" value="<?php echo @$profile['s_category_ids']; ?>"/>
          <select id="s_category_ids_multiple" name="s_category_ids_multiple" multiple>
            <?php echo bpr_cat_list($categories_array, $categories_all); ?>
          </select>

          <div class="mb-explain">
            <div class="mb-line"><?php _e('Select list of primary business categories of this company. You can select one or more categories (hold CTRL key).', 'business_profile'); ?></div>
          </div>
        </div>
        
        
        <div class="mb-row mb-row-select-multiple">
          <label for="s_features_multiple"><span><?php _e('Features', 'business_profile'); ?></span></label> 

          <input type="hidden" name="s_features" id="s_features" value="<?php echo @$profile['s_features']; ?>"/>
          <select id="s_features_multiple" name="s_features_multiple" multiple>
            <?php foreach($features_all as $f) { ?>
              <option value="<?php echo $f['pk_i_id']; ?>" <?php if(in_array($f['pk_i_id'], $features_array)) { ?>selected="selected"<?php } ?>><?php echo (bpr_field($f['locales']) <> '' ? bpr_field($f['locales']) : array_shift(array_values($f['locales']))); ?></option>
            <?php } ?>
          </select>

          <div class="mb-explain">
            <div class="mb-line"><?php _e('Select list of features available for this company. You can select one or more features (hold CTRL key).', 'business_profile'); ?></div>
          </div>
        </div>


        <div class="mb-row mb-row-select-multiple">
          <label for="s_payments_multiple"><span><?php _e('Payments', 'business_profile'); ?></span></label> 

          <input type="hidden" name="s_payments" id="s_payments" value="<?php echo @$profile['s_payments']; ?>"/>
          <select id="s_payments_multiple" name="s_payments_multiple" multiple>
            <?php foreach($payments_all as $p) { ?>
              <option value="<?php echo $p['pk_i_id']; ?>" <?php if(in_array($p['pk_i_id'], $payments_array)) { ?>selected="selected"<?php } ?>><?php echo (bpr_field($p['locales']) <> '' ? bpr_field($p['locales']) : array_shift(array_values($p['locales']))); ?></option>
            <?php } ?>
          </select>

          <div class="mb-explain">
            <div class="mb-line"><?php _e('Select list of payments available for this company. You can select one or more payments (hold CTRL key).', 'business_profile'); ?></div>
          </div>
        </div>


        <?php if(bpr_param('video') == 1 && bpr_param('video_limit') > 0) { ?>
          <div class="mb-row">
            <label for="s_video_1"><span><?php _e('Youtube Videos', 'business_profile'); ?></span></label> 

            <div class="mb-elems">
              <?php for($i=1;$i<=bpr_param('video_limit');$i++) { ?>
                <div class="mb-line">
                  <span><?php echo sprintf(__('Video #%d', 'business_profile'), $i); ?></span>
                  <input type="text" size="80" class="bpr-input bpr-video" name="bpr-video_<?php echo $i; ?>" value="<?php echo (isset($video_list[$i-1]) ? $video_list[$i-1] : ''); ?>" placeholder="https://www.youtube.com/embed/xxyyzz"/>
                </div>
              <?php } ?>
            </div>
          </div>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Add share URLs of your Youtube videos shown in profile.', 'business_profile'); ?></div>
          </div>
        <?php } ?>
        
        <div class="mb-row">
          <label for="oh_1"><span><?php _e('Opening Hours', 'business_profile'); ?></span></label>
          <div class="mb-elems">
            <?php for ($d = 1; $d <= 7; $d++) { ?>
              <div class="mb-line">
                <span><?php echo bpr_day($d, 'long'); ?></span>
                <input type="text" name="oh_<?php echo $d; ?>" id="oh_<?php echo $d; ?>" value="<?php echo (isset($hours[$d]) ? $hours[$d] : ''); ?>" placeholder="<?php echo osc_esc_html(__('From - To', 'business_profile')); ?>"/>
              </div>
            <?php } ?>
          </div>
          
          <div class="mb-explain"><?php _e('Enter opening hours for this company.', 'business_profile'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="soc_1"><span><?php _e('Social Networks', 'business_profile'); ?></span></label>
          <div class="mb-elems">
            <?php foreach($soc_list as $s) { ?>
              <div class="mb-line">
                <span><?php echo bpr_soc_icon($s, 'text'); ?></span>
                <input type="text" size=80 name="soc_<?php echo $s; ?>" id="soc_<?php echo $s; ?>" value="<?php echo (isset($socials[$s]) ? $socials[$s] : ''); ?>" placeholder="http://"/>
              </div>
            <?php } ?>
          </div>
          
          <div class="mb-explain"><?php _e('Enter social network links for this company.', 'business_profile'); ?></div>
        </div>

        <div class="mb-row">
          <label for="s_legal_notice"><span><?php _e('Legal Notice', 'business_profile'); ?></span></label> 
      
          <textarea name="s_legal_notice" id="s_legal_notice"><?php echo osc_esc_html(@$profile['s_legal_notice']); ?></textarea>

          <div class="mb-explain"><?php _e('Enter company legal notice shown under each listing of company.', 'business_profile'); ?></div>
        </div>

        
        <div class="mb-row">&nbsp;</div>

        <div class="mb-foot">
          <?php if(bpr_is_demo()) { ?>
            <a class="mb-button mb-has-tooltip disabled" onclick="return false;" style="cursor:not-allowed;opacity:0.5;" title="<?php echo osc_esc_html(__('This is demo site', 'business_profile')); ?>"><?php _e('Update', 'business_profile');?></a>
          <?php } else { ?>
            <?php if($id == '' || $id == 0) { ?>
              <button type="submit" class="mb-button"><?php _e('Create', 'business_profile');?></button>
            <?php } else { ?>
              <button type="submit" class="mb-button"><?php _e('Update', 'business_profile');?></button>
            <?php } ?>
          <?php } ?>
        </div>
      </form>
    </div>
  </div>
</div>

<script type="text/javascript">
  var user_lookup_error = "<?php echo osc_esc_js(__('Error getting data, user not found', 'business_profile')); ?>";
  var user_lookup_base = "<?php echo osc_admin_base_url(true); ?>?page=ajax&action=userajax";
</script>

<?php echo bpr_footer(); ?>