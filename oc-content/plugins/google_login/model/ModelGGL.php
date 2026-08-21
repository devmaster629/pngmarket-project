<?php
class ModelGGL extends DAO {
private static $instance;

public static function newInstance() {
  if( !self::$instance instanceof self ) {
    self::$instance = new self;
  }
  return self::$instance;
}

function __construct() {
  parent::__construct();
}


public function getTable_user() {
  return DB_TABLE_PREFIX.'t_user';
}

public function getTable_user_ggl() {
  return DB_TABLE_PREFIX.'t_user_google_login';
}

public function getTable_profile_picture() {
  return DB_TABLE_PREFIX.'t_profile_picture';
}


public function import($file) {
  $path = osc_plugin_resource($file);
  $sql = file_get_contents($path);

  if(!$this->dao->importSQL($sql) ){
    throw new Exception("Error importSQL::ModelOSM<br>" . $file . "<br>" . $this->dao->getErrorLevel() . " - " . $this->dao->getErrorDesc() );
  }
}


public function install($version = '') {
  if($version == '') {
    $this->import('google_login/model/struct.sql');

    osc_set_preference('version', 100, 'plugin-google_login', 'INTEGER');
  }
}


public function uninstall() {
  // DELETE ALL TABLES
  $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_user_ggl()));


  // DELETE ALL PREFERENCES
  $db_prefix = DB_TABLE_PREFIX;
  $query = "DELETE FROM {$db_prefix}t_preference WHERE s_section = 'plugin-google_login'";
  $this->dao->query($query);
}


public function getUser($user_id) {
  $this->dao->select();
  $this->dao->from($this->getTable_user_ggl());
  $this->dao->where('fk_i_user_id', $user_id);

  $result = $this->dao->get();
  
  if($result) { 
    return $result->row();
  }

  return false;
}


public function getUserByAuthId($user_auth_id) {
  $this->dao->select();
  $this->dao->from($this->getTable_user_ggl());
  $this->dao->where('s_oauth_uid', $user_auth_id);

  $result = $this->dao->get();
  
  if($result) { 
    return $result->row();
  }

  return false;
}


public function getUserByEmail($email) {
  $this->dao->select();
  $this->dao->from($this->getTable_user());
  $this->dao->where('s_email', $email);

  $result = $this->dao->get();
  
  if($result) { 
    return $result->row();
  }

  return false;
}



public function updateUser($user_data) {
  $user = $this->getUserByAuthId($user_data['s_oauth_uid']);
  $user_id = @$user['fk_i_user_id'];

  if(!$user || @$user['fk_i_user_id'] <= 0) {
    $user = $this->getUserByEmail($user_data['s_email']);
    $user_id = @$user['pk_i_id'];
  }


  if($user_id > 0) {
    $user_google = ModelGGL::newInstance()->getUser($user_id);

    $value_google = array(
      'fk_i_user_id' => $user_id,
      's_oauth_provider' => @$user_data['s_oauth_provider'],
      's_oauth_uid' => @$user_data['s_oauth_uid'],
      's_first_name' => @$user_data['s_first_name'],
      's_last_name' => @$user_data['s_last_name'],
      's_email' => @$user_data['s_email'],
      's_gender' => @$user_data['s_gender'],
      's_locale' => @$user_data['s_locale'],
      's_picture' => @$user_data['s_picture'],
      's_link' => @$user_data['s_link'],
      'dt_modified' => date('Y-m-d H:i:s'),
      'dt_created' => @$user_google['dt_created']
    );

    $this->dao->replace($this->getTable_user_ggl(), $value_google);    

    $this->updateProfilePicture($user_id, @$user_data['s_picture']);

    return $user_id;
    
  } else {
    $pass = osc_genRandomPassword();
    $value_user = array(
      's_name' => @$user_data['s_first_name'] . ' ' . @$user_data['s_last_name'],
      's_email' => @$user_data['s_email'],
      's_secret' => osc_genRandomPassword(),
      's_password' => osc_hash_password($pass),
      'b_enabled' => 1,
      'b_active' => 1,
      'dt_access_date' => date("Y-m-d H:i:s"),
      'dt_mod_date' => date("Y-m-d H:i:s"),
      'dt_reg_date' => date("Y-m-d H:i:s")
    );

    $this->dao->insert($this->getTable_user(), $value_user);
    $user_id = $this->dao->insertedId();
    $username = (@$user_data['s_username'] != '' ? $user_data['s_username'] : $user_id);
    
    $this->dao->update($this->getTable_user(), array('s_username' => $username), array('pk_i_id' => $user_id));

    if(osc_notify_new_user()) {
      osc_run_hook('hook_email_admin_new_user', User::newInstance()->findByPrimaryKey($user_id));
    }

    osc_run_hook('user_register_completed', $user_id);

    $value_google = array(
      'fk_i_user_id' => @$user_id,
      's_oauth_provider' => @$user_data['s_oauth_provider'],
      's_oauth_uid' => @$user_data['s_oauth_uid'],
      's_first_name' => @$user_data['s_first_name'],
      's_last_name' => @$user_data['s_last_name'],
      's_email' => @$user_data['s_email'],
      's_gender' => @$user_data['s_gender'],
      's_locale' => @$user_data['s_locale'],
      's_picture' => @$user_data['s_picture'],
      's_link' => @$user_data['s_link']
    );

    $this->dao->replace($this->getTable_user_ggl(), $value_google);

    $this->updateProfilePicture($user_id, @$user_data['s_picture']);

    return $user_id;
  }
}


public function updateUserSecret($user_id, $secret) {
  return $this->dao->update($this->getTable_user(), array('s_secret'  => $secret), array('pk_i_id'  => $user_id));
}


// GET PROFILE PICTURE
public function getProfilePicture($user_id) {
  $this->dao->select();
  $this->dao->from($this->getTable_profile_picture());

  $this->dao->where('user_id', $user_id);

  $result = $this->dao->get();
  
  if($result) {
    return $result->row();
  }

  return false;
}


// UPDATE PROFILE PICTURE
public function updateProfilePicture($user_id, $img_url) {
  $ext = pathinfo($img_url, PATHINFO_EXTENSION);

  if($ext == '') {
    //return false;
    $ext = 'png';
  }
 
  // Profile Picture Plugin
  if(function_exists('profile_picture_upload')) {
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
      
      if($res) {
        $this->dao->insert($this->getTable_profile_picture(), $update);
      }
    }
  }

  // Osclass Profile Picture
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

    //$res = move_uploaded_file($file['tmp_name'], $image_path);

    $res = copy($img_url, $image_path);
    
    if($res) {
      User::newInstance()->updateProfileImg($user_id, $image_name);
    }
  }  
}



}
?>