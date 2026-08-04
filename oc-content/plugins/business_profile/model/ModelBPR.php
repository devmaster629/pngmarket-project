<?php
class ModelBPR extends DAO {
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

public function getTable_bp() {
  return DB_TABLE_PREFIX.'t_user_business_profile';
}

public function getTable_bp_value() {
  return DB_TABLE_PREFIX.'t_bpr_values';
}

public function getTable_item() {
  return DB_TABLE_PREFIX.'t_item';
}

public function getTable_city() {
  return DB_TABLE_PREFIX.'t_city';
}

public function getTable_item_location() {
  return DB_TABLE_PREFIX.'t_item_location';
}

public function getTable_profile_picture() {
  return DB_TABLE_PREFIX.'t_profile_picture';
}

public function getTable_user() {
  return DB_TABLE_PREFIX.'t_user';
}

public function getTable_category() {
  return DB_TABLE_PREFIX.'t_category';
}

public function getTable_category_desc() {
  return DB_TABLE_PREFIX.'t_category_description';
}


public function import($file) {
  $path = osc_plugin_resource($file);
  $sql = file_get_contents($path);

  if(!$this->dao->importSQL($sql) ){
    throw new Exception("Error importSQL::ModelBPR<br>" . $file . "<br>" . $this->dao->getErrorLevel() . " - " . $this->dao->getErrorDesc() );
  }
}


public function install() {
  $this->import('business_profile/model/struct.sql');

  $locales = OSCLocale::newInstance()->listAllEnabled();

  // CONTACT COMPANY
  foreach($locales as $l) {
    $email_text  = '<p>Hi {USER_NAME}</p>';
    $email_text .= '<p>You have recieved new message from {FROM_NAME} ({PHONE}):</p>';
    $email_text .= '<p>{MESSAGE}</p>';

    $email_text .= '<p><br/></p>';
    $email_text .= '<p>Thank you, <br />{WEB_TITLE}</p>';

    $bpr_mail_seller = array();
    $bpr_mail_seller[$l['pk_c_code']]['s_title'] = '{WEB_TITLE} - New message from customer';
    $bpr_mail_seller[$l['pk_c_code']]['s_text'] = $email_text;
  }

  Page::newInstance()->insert( array('s_internal_name' => 'bpr_mail_seller', 'b_indelible' => '1'), $bpr_mail_seller);

}


public function uninstall() {
  // DELETE ALL TABLES
  $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_bp()));
  $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_bp_value()));


  // DELETE ALL PREFERENCES
  $db_prefix = DB_TABLE_PREFIX;
  $query = "DELETE FROM {$db_prefix}t_preference WHERE s_section = 'plugin-business_profile'";
  $this->dao->query($query);


  // DELETE MAILS
  $page_seller = Page::newInstance()->findByInternalName('bpr_mail_seller');
  Page::newInstance()->deleteByPrimaryKey($page_seller['pk_i_id']);
}


// EXECUTE QUERIES ON VERSION UPDATE
public function versionUpdate($ignore_error = false) {
  $version = (int)bpr_param('version');     // v100 is initial
  $version = ($version >= 100 ? $version : 0);
  $plugin = 'business_profile';
  
  // Version not yet available - it's installation process now
  if($version == 0) {
    return true;
  }
  
  $queries = array(
    array('version' => 101, 'query' => sprintf("CREATE TABLE %st_bpr_values (pk_i_id INT NOT NULL AUTO_INCREMENT, fk_c_locale_code CHAR(5) NOT NULL, s_type VARCHAR(20), s_name VARCHAR(100) NOT NULL, PRIMARY KEY(pk_i_id, fk_c_locale_code)) ENGINE=InnoDB DEFAULT CHARACTER SET 'UTF8' COLLATE 'UTF8_GENERAL_CI';", DB_TABLE_PREFIX)),
    array('version' => 102, 'query' => sprintf("ALTER TABLE %st_user_business_profile ADD COLUMN s_gallery VARCHAR(2000);", DB_TABLE_PREFIX)),
    array('version' => 103, 'query' => sprintf("ALTER TABLE %st_user_business_profile ADD COLUMN s_videos VARCHAR(2000);", DB_TABLE_PREFIX)),
    array('version' => 104, 'query' => sprintf("ALTER TABLE %st_user_business_profile ADD COLUMN s_legal_notice VARCHAR(2000);", DB_TABLE_PREFIX)),
    array('version' => 105, 'query' => sprintf("ALTER TABLE %st_user_business_profile ADD COLUMN s_logo VARCHAR(50) AFTER s_icon;", DB_TABLE_PREFIX))
  );
  
  if(is_array($queries) && count($queries) > 0) {
    foreach($queries as $query) {
      if($version < $query['version'] && $query['version'] <= BPR_VERSION_ID) {
        $result = $this->dao->query($query['query']);
        
        if($result === false && $ignore_error !== true) {
          $message  = sprintf(__('Update of plugin "%s" failed on DB version "%s". Please enable %s to see error details. Failed query is listed below.', 'business_profile'), __('Business Profile Plugin', 'business_profile'), $query['version'], '<a href="https://docs.osclasspoint.com/debug-mode" target="_blank">' . __('DB debug mode', 'business_profile') . '</a>');
          $message .= '<pre style="font-size:11px;">' . $query['query'] . '</pre>';
          $message .= '<a href="' . osc_admin_base_url(true) . '?page=plugins&forceupdateplugin=' . $plugin . '">' . __('Ignore error and force plugin update', 'business_profile') . '</a>. ';
          $message .= __('Never force update plugin until you are sure that your database structure match to model/struct.sql file! It may lead to unexpected plugin functionality. Try to reinstall plugin.', 'business_profile');

          osc_add_flash_error_message($message, 'admin');
          return false;
        }
      }
    }
  }
  
  return true;
}



// CHECK IDENTIFIER AVAILABILITY
public function checkIdentifier($id) {
  $this->dao->select();
  $this->dao->from($this->getTable_bp());

  $this->dao->where('s_identifier', $id);

  $result = $this->dao->get();
  
  if($result) {
    $row = $result->row();

    if(isset($row['s_identifier']) && $row['s_identifier'] <> '') {
      return $row;
    }
  }

  return true;
}



// GET FEATURES / PAYMENTS
public function getValues($type = '') {
  $this->dao->select();
  $this->dao->from($this->getTable_bp_value());

  if($type <> '') {
    $this->dao->where('s_type', strtoupper($type));
  }


  $result = $this->dao->get();
  
  if($result) {
    $data = $result->result();
    $output = array();

    if(count($data) > 0) {
      foreach($data as $d) {
        $output[$d['pk_i_id']]['pk_i_id'] = $d['pk_i_id'];
        $output[$d['pk_i_id']]['s_type'] = $d['s_type'];

        $output[$d['pk_i_id']]['locales'][$d['fk_c_locale_code']] = $d['s_name'];
      }
    }

    return $output;
  }

  return array();
}


// GET FEATURE / PAYMENT
public function getValue($id, $locale = '') {
  $this->dao->select();
  $this->dao->from($this->getTable_bp_value());

  $this->dao->where('pk_i_id', $id);

  if($locale == '') {
    $locale = osc_current_user_locale();
  }

  $this->dao->where('fk_c_locale_code', $locale);


  $result = $this->dao->get();
  
  if($result) {
    return $result->row();
  }

  return false;
}


// GET FEATURES / PAYMENTS
public function getValueLocale($id) {
  $this->dao->select();
  $this->dao->from($this->getTable_bp_value());
  $this->dao->where('pk_i_id', $id);

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->result();
    $output = array();

    if(count($data) > 0) {
      foreach($data as $d) {
        $output['pk_i_id'] = $d['pk_i_id'];
        $output['s_type'] = $d['s_type'];

        $output['locales'][$d['fk_c_locale_code']] = $d['s_name'];
      }
    }

    return $output;
  }

  return false;
}




// GET SELLER ITEM CITIES
public function getCities($user_id) {
  if(osc_get_current_user_locations_native() == 1) {
    $this->dao->select('DISTINCT l.fk_i_city_id as city_id, l.s_city as city_name, l.s_city_native as city_name_native');
  } else {
    $this->dao->select('DISTINCT l.fk_i_city_id as city_id, l.s_city as city_name');
  }

  $this->dao->from($this->getTable_item() . ' as i, ' . $this->getTable_item_location() . ' as l');

  $this->dao->where('l.fk_i_item_id = i.pk_i_id');
  $this->dao->where('i.fk_i_user_id', (int)$user_id);
  $this->dao->where('l.fk_i_city_id > 0');
  $this->dao->where('l.s_city <> ""');

  $result = $this->dao->get();
  
  if($result) {
    return $result->result();
  }

  return array();
}


// COUNT SELLER ITEMS
public function countItems($user_id, $category_id = 0, $city_id = 0) {
  $this->dao->select('count(*) as i_count');
  $this->dao->from($this->getTable_item() . ' as i, ' . $this->getTable_item_location() . ' as l');

  $this->dao->where('i.pk_i_id = l.fk_i_item_id');
  $this->dao->where('i.fk_i_user_id', $user_id);
  $this->dao->where('i.b_enabled', 1);
  $this->dao->where('i.b_active', 1);
  $this->dao->where('i.b_spam', 0);

  if($category_id > 0) {
    $this->dao->where('i.fk_i_category_id', $category_id);
  }

  if($city_id > 0) {
    $this->dao->where('l.fk_i_city_id', $city_id);
  }


  $result = $this->dao->get();
  
  if($result) {
    return $result->row()['i_count'];
  }

  return 0;
}


// GET SELLER ITEM CATEGORIES
public function getCategories($user_id) {
  $this->dao->select('DISTINCT c.fk_i_category_id as category_id, c.s_name as category_name');
  $this->dao->from($this->getTable_item() . ' as i, ' . $this->getTable_category_desc() . ' as c');

  $this->dao->where('c.fk_i_category_id= i.fk_i_category_id');
  $this->dao->where('i.fk_i_user_id', $user_id);
  $this->dao->where('c.fk_c_locale_code', osc_current_user_locale());

  $result = $this->dao->get();
  
  if($result) {
    return $result->result();
  }

  return array();
}



// GET SEARCH CATEGORIES
public function getSearchCategories() {
  $this->dao->select('s_category_ids');
  $this->dao->from($this->getTable_bp());

  // Subdomain filter
  if(bpr_param('apply_subdomain_filter') == 1 && osc_subdomain_enabled() && osc_is_subdomain() && osc_subdomain_param() != '' && osc_subdomain_id() != '') {
    if(osc_is_frontoffice() && in_array(osc_subdomain_type(), array('country','region','city','category'))) {
      switch(osc_subdomain_param()) {
        case 'sCategory':
          $category_id = ';' . osc_subdomain_id() . ';';
          $this->dao->where('concat(concat(";", s_category_ids), ";") like "%' . $category_id . '%"');
          break;
      }
    }
  }

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->result();
    $ids = array();

    if(count($data) > 0) {
      foreach($data as $d) {
        $ids = array_merge($ids, explode(';', $d['s_category_ids']));
      }
    }

    $ids = array_unique(array_filter($ids));
    $ids_list = implode(',', $ids);

    if($ids_list <> '') {
      $this->dao->select('distinct *');
      $this->dao->from($this->getTable_category_desc());
      $this->dao->where('fk_i_category_id in (' . $ids_list . ')');
      $this->dao->where('fk_c_locale_code', osc_current_user_locale());
      $result = $this->dao->get();
  
      if($result) {
        return $result->result();
      }

      return array();
    }
  }

  return array();
}


// GET SEARCH CITIES
public function getSearchCities() {
  $this->dao->select('distinct c.*');
  $this->dao->from($this->getTable_city() . ' as c, ' . $this->getTable_bp() . ' as b,' . $this->getTable_user() . ' as u');
  $this->dao->where('b.fk_i_user_id = u.pk_i_id');
  $this->dao->where('c.pk_i_id = u.fk_i_city_id');


  // Subdomain filter
  if(bpr_param('apply_subdomain_filter') == 1 && osc_subdomain_enabled() && osc_is_subdomain() && osc_subdomain_param() != '' && osc_subdomain_id() != '') {
    if(osc_is_frontoffice() && in_array(osc_subdomain_type(), array('country','region','city','category'))) {
      switch(osc_subdomain_param()) {
        case 'sCountry':
          $this->dao->where('u.fk_c_country_code', osc_subdomain_id());
          break;
        
        case 'sRegion':
          $this->dao->where('u.fk_i_region_id', osc_subdomain_id());
          break;

        case 'sCity':
          $this->dao->where('u.fk_i_city_id', osc_subdomain_id());
          break;
      }
    }
  }


  $result = $this->dao->get();
  
  if($result) {
    return $result->result();
  }

  return array();
}


// GET RANDOM IDS
public function getIds($limit) {
  $this->dao->select('pk_i_id');
  $this->dao->from($this->getTable_bp());
  $this->dao->where('b_enabled', 1);

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->result();

    $output = array_column($data, 'pk_i_id');

    shuffle($output);
    $output = array_slice($output, 0, $limit);

    return $output;
  }

  return array();
}  




// GET ALL SELLERS
public function getSellers($enabled = -1, $verified = -1, $type = -1, $limit = array(), $pattern = '', $city = '', $category = '', $order = '', $ids = array()) {
  $this->dao->select('b.*');
  $this->dao->from($this->getTable_bp() . ' as b');

  if($enabled <> -1) {
    $this->dao->where('b.b_enabled', $enabled);
  }

  if($verified <> -1) {
    $this->dao->where('b.b_verified', $verified);
  }

  if($type <> -1) {
    $this->dao->where('b.i_type', $type);
  }

  if($order == 'RANDOM' && is_array($ids) && !empty($ids)) {
    $ids = implode(',', $ids);
    $this->dao->where('b.pk_i_id in (' . $ids . ')');
  }

  if($order == 'NEW') {
    $this->dao->orderby('b.pk_i_id DESC');
  }

  if($order == 'ITEMS') {
    $this->dao->join($this->getTable_user() . ' as uv', '(b.fk_i_user_id = uv.pk_i_id)', 'INNER');
    $this->dao->orderby('uv.i_items DESC');
  }


  if($order == 'SEARCH') {
    if(osc_is_search_page()) {
      $city = osc_search_city();

    } else if (osc_is_ad_page()) {
      $city = osc_item_city();

    } else {
      $city = '';
    }


    if(osc_is_search_page()) {
      $search_cat_id = osc_search_category_id();
      $category = isset($search_cat_id[0]) ? $search_cat_id[0] : '';

    } else if (osc_is_ad_page()) {
      $item = osc_item(); 

      if(isset($item['fk_i_category_id']) && $item['fk_i_category_id'] > 0) {
        $category = $item['fk_i_category_id'];
      }

    } else {
      $category = '';
    }
  }

  if($category <> '') {
    $category_id = ';' . $category . ';';
    $this->dao->where('concat(concat(";", s_category_ids), ";") like "%' . $category_id . '%"');
  }

  if($city <> '' || $pattern <> '') {
    $this->dao->join($this->getTable_user() . ' as u', '(b.fk_i_user_id = u.pk_i_id)', 'INNER');
  }
  
  if($city <> '') {
    if(is_numeric($city)) {
      $this->dao->where('u.fk_i_city_id', $city);
    } else {
      $this->dao->where('u.s_city', $city);
    }
  }


  if($pattern <> '') {
    $this->dao->where('(u.s_name like "%' . $pattern . '%" OR b.s_identifier like "%' . $pattern . '%")');
  }


  // $limit[0] == limit; $limit[1] == page
  $page = (isset($limit[1]) ? $limit[1] : 0);
  $per_page = (isset($limit[0]) ? $limit[0] : 24);

  if($page > 0) {
    $this->dao->limit(($page-1)*$per_page, $per_page);
  } else {
    $this->dao->limit($per_page);
  }


  // Subdomain filter
  if(bpr_param('apply_subdomain_filter') == 1 && osc_subdomain_enabled() && osc_is_subdomain() && osc_subdomain_param() != '' && osc_subdomain_id() != '') {
    if(osc_is_frontoffice() && in_array(osc_subdomain_type(), array('country','region','city','category'))) {
      switch(osc_subdomain_param()) {
        case 'sCategory':
          $category_id = ';' . osc_subdomain_id() . ';';
          $this->dao->where('concat(concat(";", s_category_ids), ";") like "%' . $category_id . '%"');
          break;

        case 'sCountry':
          $this->dao->join($this->getTable_user() . ' as u9', '(b.fk_i_user_id = u9.pk_i_id)', 'INNER');
          $this->dao->where('u9.fk_c_country_code', osc_subdomain_id());
          break;
        
        case 'sRegion':
          $this->dao->join($this->getTable_user() . ' as u9', '(b.fk_i_user_id = u9.pk_i_id)', 'INNER');
          $this->dao->where('u9.fk_i_region_id', osc_subdomain_id());
          break;

        case 'sCity':
          $this->dao->join($this->getTable_user() . ' as u9', '(b.fk_i_user_id = u9.pk_i_id)', 'INNER');
          $this->dao->where('u9.fk_i_city_id', osc_subdomain_id());
          break;
      }
    }
  }

  $result = $this->dao->get();
  
  if($result) {
    return $result->result();
  }

  return array();
}


// COUNT ALL SELLERS
public function countSellers($enabled = -1, $verified = -1, $type = -1, $pattern = '', $city = '', $category = '') {
  $this->dao->select('count(b.pk_i_id) as i_count');
  $this->dao->from($this->getTable_bp() . ' as b');

  if($enabled <> -1) {
    $this->dao->where('b.b_enabled', $enabled);
  }

  if($verified <> -1) {
    $this->dao->where('b.b_verified', $verified);
  }

  if($type <> -1) {
    $this->dao->where('b.i_type', $type);
  }
  
  if($category <> '') {
    $category_id = ';' . $category . ';';
    $this->dao->where('concat(concat(";", s_category_ids), ";") like "%' . $category_id . '%"');
  }
  
  if($city <> '' || $pattern <> '') {
    $this->dao->join($this->getTable_user() . ' as u', '(b.fk_i_user_id = u.pk_i_id)', 'INNER');
  }

  if($city <> '') {
    if(is_numeric($city)) {
      $this->dao->where('u.fk_i_city_id', $city);
    } else {
      $this->dao->where('u.s_city', $city);
    }
  }

  if($pattern <> '') {
    $this->dao->where('(u.s_name like "%' . $pattern . '%" OR b.s_identifier like "%' . $pattern . '%")');
  }

  // Subdomain filter
  if(bpr_param('apply_subdomain_filter') == 1 && osc_subdomain_enabled() && osc_is_subdomain() && osc_subdomain_param() != '' && osc_subdomain_id() != '') {
    if(osc_is_frontoffice() && in_array(osc_subdomain_type(), array('country','region','city','category'))) {
      switch(osc_subdomain_param()) {
        case 'sCategory':
          $category_id = ';' . osc_subdomain_id() . ';';
          $this->dao->where('concat(concat(";", s_category_ids), ";") like "%' . $category_id . '%"');
          break;

        case 'sCountry':
          $this->dao->join($this->getTable_user() . ' as u9', '(b.fk_i_user_id = u9.pk_i_id)', 'INNER');
          $this->dao->where('u9.fk_c_country_code', osc_subdomain_id());
          break;
        
        case 'sRegion':
          $this->dao->join($this->getTable_user() . ' as u9', '(b.fk_i_user_id = u9.pk_i_id)', 'INNER');
          $this->dao->where('u9.fk_i_region_id', osc_subdomain_id());
          break;

        case 'sCity':
          $this->dao->join($this->getTable_user() . ' as u9', '(b.fk_i_user_id = u9.pk_i_id)', 'INNER');
          $this->dao->where('u9.fk_i_city_id', osc_subdomain_id());
          break;
      }
    }
  }

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->row();
    return $data['i_count'];
  }

  return 0;
}


// GET SELLER BY ID
public function getSeller($id) {
  $this->dao->select();
  $this->dao->from($this->getTable_bp());

  $this->dao->where('pk_i_id', $id);

  $result = $this->dao->get();
  
  if($result) {
    return $result->row();
  }

  return array();
}


// GET ALL SELLERS
public function getAllSeller() {
  $this->dao->select();
  $this->dao->from($this->getTable_bp());

  $result = $this->dao->get();
  
  if($result) {
    return $result->result();
  }

  return array();
}



// GET SELLER BY USER ID
public function getSellerByUserId($user_id) {
  $this->dao->select();
  $this->dao->from($this->getTable_bp());

  $this->dao->where('fk_i_user_id', $user_id);

  $result = $this->dao->get();
  
  if($result) {
    return $result->row();
  }

  return array();
}


// GET SELLER BY IDENTIFIER
public function getSellerByIdentifier($identifier) {
  $this->dao->select();
  $this->dao->from($this->getTable_bp());

  $this->dao->where('s_identifier', $identifier);

  $result = $this->dao->get();
  
  if($result) {
    return $result->row();
  }

  return array();
}


// UPDATE SELLER DATA
public function updateSellerData($params) {
  return $this->dao->replace($this->getTable_bp(), $params);
}

// UPDATE SELLER FIELD
public function updateSellerField($id, $params) {
  return $this->dao->update($this->getTable_bp(), $params, array('pk_i_id' => $id));
}

// UPDATE VALUE
public function updateValue($params) {
  return $this->dao->replace($this->getTable_bp_value(), $params);
}

// DELETE VALUE
public function deleteValue($id) {
  return $this->dao->delete($this->getTable_bp_value(), array('pk_i_id' => $id));
}

// DELETE VALUES
public function deleteValues($type, $locale) {
  return $this->dao->delete($this->getTable_bp_value(), array('s_type' => strtoupper($type), 'fk_c_locale_code' => $locale));
}

// DELETE VALUES BY IDS
public function deleteValuesByIds($ids, $type) {
  if($ids <> '') {
    return $this->dao->query(sprintf('DELETE FROM %s WHERE pk_i_id not in (%s) AND s_type = "' . strtoupper($type) . '"', $this->getTable_bp_value(), $ids));
  }

}

// INSERT PROFILE
public function insertProfile($data) {
  $this->dao->insert($this->getTable_bp(), $data);
  return $this->dao->insertedId();
}

// UPDATE PROFILE
public function updateProfile($id, $data) {
  return $this->dao->update($this->getTable_bp(), $data, array('pk_i_id' => $id));
}


// ACTIVATE PROFILE
public function activateProfile($id) {
  return $this->dao->update($this->getTable_bp(), array('b_enabled' => 1), array('pk_i_id' => $id));
}


// DEACTIVATE PROFILE
public function deactivateProfile($id) {
  return $this->dao->update($this->getTable_bp(), array('b_enabled' => 0), array('pk_i_id' => $id));
}


// UPDATE USER TYPE
public function updateProfileType($id, $type) {
  return $this->dao->update($this->getTable_bp(), array('i_type' => $type), array('pk_i_id' => $id));
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

  return array();
}


// UPDATE PROFILE PICTURE
public function updateProfilePicture($user_id) {
  $img = $this->getProfilePicture($user_id);

  $update = array(
    'user_id' => $user_id,
    'pic_ext' => '.png'
  );

  if(isset($img['user_id']) && $img['user_id'] > 0) {
    $this->dao->delete($this->getTable_profile_picture(), array('pk_i_id' => $img['id']));
  }

  $this->dao->insert($this->getTable_profile_picture(), $update);
  return $this->dao->insertedId();
}


// REMOVE PROFILE
public function removeProfile($id) {
  return $this->dao->delete($this->getTable_bp(), array('pk_i_id' => $id));
}


// REMOVE PROFILE BY USER ID
public function removeProfileByUserId($id) {
  return $this->dao->delete($this->getTable_bp(), array('fk_i_user_id' => $id));
}

}
?>