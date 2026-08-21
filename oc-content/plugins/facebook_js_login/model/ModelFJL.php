<?php
class ModelFJL extends DAO {
private static $instance;

public static function newInstance() {
  if(!self::$instance instanceof self) {
    self::$instance = new self ;
  }
  return self::$instance ;
}

function __construct() {
  parent::__construct();
}

public function getTable_user() {
  return DB_TABLE_PREFIX.'t_user';
}

public function getTable_facebook() {
  return DB_TABLE_PREFIX.'t_user_facebook_js_login';
}

public function getTable_profile_picture() {
  return DB_TABLE_PREFIX.'t_profile_picture';
}

public function import($file) {
  $path = osc_plugin_resource($file);
  $sql = file_get_contents($path);
  if(!$this->dao->importSQL($sql)){ throw new Exception("Error importSQL::ModelFJL<br>".$file.'<br>'.$path.'<br><br>Please check your database for if there are no plugin tables. <br>If any of those tables exists in your database, drop them!');} 
}

public function install($version = '') {
  if($version == '') {
    $this->import('facebook_js_login/model/struct.sql');

    osc_set_preference('version', 100, 'plugin-facebook_js_login', 'INTEGER');
  }
}
 
public function uninstall() {
  $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_facebook()));

  // DELETE ALL PREFERENCES
  $db_prefix = DB_TABLE_PREFIX;
  $query = "DELETE FROM {$db_prefix}t_preference WHERE s_section = 'plugin-facebook_js_login'";
  $this->dao->query($query);
}

public function getUserFBDataByUserId($user_id) {
  if($user_id <= 0) {
    return false;
  }
  
  $this->dao->select();
  $this->dao->from($this->getTable_facebook());
  $this->dao->where('fk_i_user_id', (int)$user_id);

  $result = $this->dao->get();
  
  if($result) { 
    return $result->row();
  }

  return false;
}


public function getUserFBDataByAuthId($oauth_uid) {
  if($oauth_uid == '') {
    return false;
  }
  
  $this->dao->select();
  $this->dao->from($this->getTable_facebook());
  $this->dao->where('s_oauth_uid', (string)$oauth_uid);

  $result = $this->dao->get();
  
  if($result) { 
    return $result->row();
  }

  return false;
}

public function getUserByEmail($email) {
  if($email == '') {
    return false;
  }
  
  $this->dao->select();
  $this->dao->from($this->getTable_user());
  $this->dao->where('s_email', (string)$email);

  $result = $this->dao->get();
  
  if($result) { 
    return $result->row();
  }

  return false;
}


public function updateUserFBData($user_data) {
  $user_id = '';
  $user = $this->getUserFBDataByAuthId($user_data['s_oauth_uid']);

  if($user !== false && isset($user['fk_i_user_id'])) {
    $user_id = $user['fk_i_user_id'];
  } else {
    $user = $this->getUserByEmail($user_data['s_email']);
    
    if($user !== false && isset($user['pk_i_id'])) {
      $user_id = $user['pk_i_id'];
    }
  }

  $user_id = (int)$user_id;
  
  if($user_id > 0) {
    $user_FB_data = ModelFJL::newInstance()->getUserFBDataByUserId($user_id);

    $value_google = array(
      'fk_i_user_id' => $user_id,
      's_oauth_provider' => $user_data['s_oauth_provider'],
      's_oauth_uid' => $user_data['s_oauth_uid'],
      's_name' => $user_data['s_name'],
      's_email' => $user_data['s_email'],
      's_picture' => $user_data['s_picture'],
      'dt_modified' => date('Y-m-d H:i:s'),
      'dt_created' => (isset($user_FB_data['dt_created']) ? $user_FB_data['dt_created'] : date('Y-m-d H:i:s'))
    );

    $this->dao->replace($this->getTable_facebook(), $value_google);    

    $this->updateProfilePicture($user_id, $user_data['s_picture']);
    return $user_id;
    
  } else {
    $pass = osc_genRandomPassword();
    $value_user = array(
      's_name' => $user_data['s_name'],
      's_email' => $user_data['s_email'],
      's_secret' => osc_genRandomPassword(),
      's_password' => osc_hash_password($pass),
      'b_enabled' => 1,
      'b_active' => 1,
      'dt_access_date' => date("Y-m-d H:i:s"),
      'dt_mod_date' => date("Y-m-d H:i:s"),
      'dt_reg_date' => date("Y-m-d H:i:s")
    );

    $this->dao->insert($this->getTable_user(), $value_user);
    $user_id = (int)$this->dao->insertedId();
    
    $username = (@$user_data['s_username'] != '' ? $user_data['s_username'] : $user_id);

    $this->dao->update($this->getTable_user(), array('s_username' => $username), array('pk_i_id' => $user_id));

    if(osc_notify_new_user()) {
      osc_run_hook('hook_email_admin_new_user', User::newInstance()->findByPrimaryKey($user_id));
    }

    osc_run_hook('user_register_completed', $user_id);

    $value_google = array(
      'fk_i_user_id' => $user_id,
      's_oauth_provider' => $user_data['s_oauth_provider'],
      's_oauth_uid' => $user_data['s_oauth_uid'],
      's_name' => $user_data['s_name'],
      's_email' => $user_data['s_email'],
      's_picture' => $user_data['s_picture'],
      'dt_modified' => date('Y-m-d H:i:s'),
      'dt_created' => date('Y-m-d H:i:s')
    );

    $this->dao->replace($this->getTable_facebook(), $value_google);

    $this->updateProfilePicture($user_id, $user_data['s_picture']);

    return $user_id;
  }
  
  return false;
}


public function updateUserSecret($user_id, $secret) {
  if($user_id <= 0 || $secret == '') {
    return false; 
  }
  
  return $this->dao->update($this->getTable_user(), array('s_secret' => (string)$secret), array('pk_i_id' => (int)$user_id));
}


// GET PROFILE PICTURE
public function getProfilePicture($user_id) {
  if($user_id <= 0) {
    return false;
  }
  
  if(function_exists('profile_picture_install')) {
    $this->dao->select();
    $this->dao->from($this->getTable_profile_picture());

    $this->dao->where('user_id', $user_id);

    $result = $this->dao->get();
    
    if($result) {
      return $result->row();
    }
  }

  return false;
}


// UPDATE PROFILE PICTURE
public function updateProfilePicture($user_id, $img_url) {
  if($user_id <= 0 || $img_url == '') {
    return false;
  }

  $ext = pathinfo($img_url, PATHINFO_EXTENSION);
  
  if($ext == '') {
    $info = getimagesize($img_url);
    
    if(isset($info['mime']) && $info['mime'] <> '') {
      $mi = strtolower((string)$info['mime']);
      if($mi == 'image/jpeg' || $mi == 'image/jpg') {
        $ext = 'jpg';
      } else if($mi == 'image/x-png' || $mi == 'image/png') {
        $ext = 'png';
      } else {
        return false;
      }
    }
  }
  
  if($ext == '') {
    return false;
  }

  if(osc_version() >= 420) {
    $user = User::newInstance()->findByPrimaryKey($user_id);

    // Do not update profile picture, if one is already defined.
    if($user['s_profile_img'] <> '') {
      //@unlink(osc_content_path() . 'uploads/user-images/' . $user['s_profile_img']);
      return false;
    }

    $image_name = $user_id . '_' . osc_generate_rand_string(5) . '_' . date('Ymd') . '.' . $ext; 
    $image_url = osc_content_url() . 'uploads/user-images/' . $image_name;
    $image_path = osc_content_path() . 'uploads/user-images/' . $image_name;

    //file_put_contents($image_path, $data);
    $res = copy($img_url, $image_path);

    if($res !== false) {
      $this->dao->update($this->getTable_user(), array('s_profile_img' => $image_name), array('pk_i_id' => $user_id));
    }
  }
  
  if(function_exists('profile_picture_install')) {
    $img = $this->getProfilePicture($user_id);
   
    $update = array(
      'user_id' => $user_id,
      'pic_ext' => '.' . $ext
    );

    // clear old image if exists
    if($img && @$img['user_id'] > 0) {
      @unlink(osc_plugins_path() . 'profile_picture/images/profile' . $img['user_id'] . $img['pic_ext']);
      $this->dao->delete($this->getTable_profile_picture(), array('pk_i_id' => $img['id']));
    }

    if(file_exists(osc_plugins_path() . 'profile_picture/images/')) {
      $res = copy($img_url, osc_plugins_path() . 'profile_picture/images/profile' . $user_id . '.' . $ext);
    }

    if($res !== false) {
      $this->dao->insert($this->getTable_profile_picture(), $update);
    }
  }
}



}
?>