<?php
class ModelEPS extends DAO {
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

public function getTable_item() {
  return DB_TABLE_PREFIX.'t_item';
}

public function getTable_item_epsilon() {
  return DB_TABLE_PREFIX.'t_item_epsilon';
}

public function getTable_category() {
  return DB_TABLE_PREFIX.'t_category';
}

public function getTable_category_epsilon() {
  return DB_TABLE_PREFIX.'t_category_epsilon';
}

public function getTable_category_description() {
  return DB_TABLE_PREFIX.'t_category_description';
}

public function getTable_latest_searches() {
  return DB_TABLE_PREFIX.'t_latest_searches';
}

public function getTable_city() {
  return DB_TABLE_PREFIX.'t_city';
}

public function getTable_city_stats() {
  return DB_TABLE_PREFIX.'t_city_stats';
}

public function getTable_region() {
  return DB_TABLE_PREFIX.'t_region';
}

public function getTable_region_stats() {
  return DB_TABLE_PREFIX.'t_region_stats';
}

public function getTable_country() {
  return DB_TABLE_PREFIX.'t_country';
}

public function getTable_country_stats() {
  return DB_TABLE_PREFIX.'t_country_stats';
}

public function import($file) {
  $path = osc_base_path() . $file;
  $sql = file_get_contents($path);

  if(!$this->dao->importSQL($sql) ){
    throw new Exception("Error importSQL::ModelEPS<br>" . $file . "<br>" . $this->dao->getErrorLevel() . " - " . $this->dao->getErrorDesc() );
  }
}


public function install() {
  $this->import('oc-content/themes/epsilon/model/struct.sql');
}


// GET USERS LIST
public function getUsers($limit = 12, $order_col = 'i_items', $order_type = 'DESC', $with_picture = true) {
  $this->dao->select();
  $this->dao->from($this->getTable_user());

  $this->dao->where('b_active', 1);
  $this->dao->where('b_enabled', 1);

  if($with_picture) {
    $this->dao->where('coalesce(s_profile_img, "") <> ""');
    $this->dao->where('s_profile_img <> "default-user-image.png"');
  }
  
  $this->dao->orderBy($order_col, $order_type);
  $this->dao->limit($limit);

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->result();
    return $data;
  }

  return array();
}


// GET ITEM EXTRA
public function getItemExtra($item_id) {
  if($item_id <= 0) { return false; }
  
  $key = md5(osc_base_url().'ModelEPS:getItemExtra:'.$item_id);
  $found = null;
  $cache = osc_cache_get($key, $found);
  
  if($cache === false) {
    $this->dao->select();
    $this->dao->from($this->getTable_item_epsilon());
    $this->dao->where('fk_i_item_id', $item_id);

    $result = $this->dao->get();
    
    if($result) {
      $data = $result->row();
      osc_cache_set($key, $data, OSC_CACHE_TTL);

      return $data;
    }
    
    return false;
  } else {
    return $cache;
  }
}


// GET CLOSEST CITY
public function findClosestCity($lat, $lon) {
  //$distance_select_col = sprintf('SQRT(POWER(c.d_coord_lat - %f, 2) + POWER(c.d_coord_long - %f, 2)) as d_distance', (float)$lat, (float)$lon);

  $measurement = 6371;  // 3959 for miles 
  $distance_select_col = sprintf('(%d * acos(cos(radians(%f)) * cos(radians(coalesce(c.d_coord_lat, 0))) * cos(radians(coalesce(c.d_coord_long, 0)) - radians(%f)) + sin(radians(%f)) * sin(radians(coalesce(c.d_coord_lat, 0))))) as d_distance', (int)$measurement, (float)$lat, (float)$lon, (float)$lat);
  
  if(osc_get_current_user_locations_native() == 1) {
    $this->dao->select('c.pk_i_id as fk_i_city_id, c.fk_i_region_id, c.fk_c_country_code, c.s_name as s_city, c.s_name_native as s_city_native, r.s_name as s_region, r.s_name_native as s_region_native, c.d_coord_lat, c.d_coord_long, ' . $distance_select_col);
  } else {
    $this->dao->select('c.pk_i_id as fk_i_city_id, c.fk_i_region_id, c.fk_c_country_code, c.s_name as s_city, r.s_name as s_region, c.d_coord_lat, c.d_coord_long, ' . $distance_select_col);
  }

  $this->dao->from($this->getTable_city() . ' as c');
  $this->dao->join($this->getTable_region() . ' as r', 'c.fk_i_region_id = r.pk_i_id', 'INNER');

  //$this->dao->where('');
  $this->dao->orderby('d_distance', 'ASC');
  $this->dao->limit(1);

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->row();
    
    if(isset($data['fk_i_city_id']) && $data['fk_i_city_id'] > 0) {
      $data['d_distance_precise'] = epsCalcCordDistance($lat, $lon, $data['d_coord_lat'], $data['d_coord_long']);
    }
    
    return $data;
  }

  return false;
}


// GET POPULAR CITIES
function getPopularCities($limit = 6, $min = 0) {
  if(osc_get_current_user_locations_native() == 1) {
    $this->dao->select('c.pk_i_id as fk_i_city_id, c.fk_i_region_id, c.fk_c_country_code, c.s_name, r.s_name as s_name_top, c.s_name_native, r.s_name_native as s_name_top_native, c.s_slug, s.i_num_items, c.d_coord_lat, c.d_coord_long');
  } else {
    $this->dao->select('c.pk_i_id as fk_i_city_id, c.fk_i_region_id, c.fk_c_country_code, c.s_name, r.s_name as s_name_top, c.s_slug, s.i_num_items, c.d_coord_lat, c.d_coord_long');
  }
  
  $this->dao->from($this->getTable_city_stats() . ' as s');
  $this->dao->join($this->getTable_city() . ' as c', 's.fk_i_city_id = c.pk_i_id', 'INNER');
  $this->dao->join($this->getTable_region() . ' as r', 'c.fk_i_region_id = r.pk_i_id', 'INNER');
  $this->dao->where('i_num_items >= ' . $min);
  $this->dao->orderBy('i_num_items', 'DESC');
  $this->dao->limit($limit);


  $result = $this->dao->get();
  
  if($result) {
    $data = $result->result();
    return $data;
  }
  
  return array();
}


// SEARCH FOR CATEGORIES
public function findCategories($pattern, $limit = 6) {
  $this->dao->select('c.pk_i_id, c.fk_i_parent_id, d.s_name, p.s_name as s_name_parent');
  $this->dao->from($this->getTable_category() . ' as c');
  $this->dao->join($this->getTable_category_description() . ' as d', '(c.pk_i_id = d.fk_i_category_id AND d.fk_c_locale_code = "' . osc_current_user_locale() . '")', 'INNER');
  $this->dao->join($this->getTable_category_description() . ' as p', '(c.fk_i_parent_id = p.fk_i_category_id AND p.fk_c_locale_code = "' . osc_current_user_locale() . '")', 'LEFT OUTER');

  $this->dao->where('c.b_enabled', 1);
  
  if($pattern != '') {
    $this->dao->where(sprintf('d.s_name like "%%%s%%"', osc_esc_html($pattern)));
  }

  $this->dao->limit($limit);

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->result();
    return $data;
  }

  return array();
}


// SEARCH FOR LATEST SEARCHES
public function findLatestSearches($pattern, $limit = 6) {
  $this->dao->select('s_search, count(d_date) as i_count');
  $this->dao->from($this->getTable_latest_searches());

  if($pattern != '') {
    $this->dao->where('s_search like "%' . osc_esc_html($pattern) . '%"');
  }
  
  $this->dao->groupBy('s_search');
  $this->dao->orderBy('i_count', 'DESC');
  $this->dao->limit($limit*2);

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->result();
    $output = array();
    $stop_words = strtolower(@osc_get_current_user_locale()['s_stop_words']);
    $stop_words = array_filter(array_map('trim', explode(',', $stop_words)));
    
    if(is_array($data) && count($data) > 0) {
      foreach($data as $d) {
        if(mb_strlen($d['s_search']) >= 4 && strlen($d['s_search']) <= 20 && !in_array(strtolower($d['s_search']), $stop_words)) {
          $output[] = $d;
        }
      }
    }
    
    if(count($output) > $limit) {
      $output = array_slice($output, 0, $limit, false);
    }
    
    return $output;
  }

  return array();
}


// SEARCH FOR CITIES
public function findCities($pattern, $limit = 6) {
  if(osc_get_current_user_locations_native() == 1) {
    $this->dao->select('c.pk_i_id as fk_i_city_id, c.fk_i_region_id, c.fk_c_country_code, c.s_name, c.s_name_native, r.s_name as s_name_top, r.s_name_native as s_name_top_native, c.d_coord_lat, c.d_coord_long');
  } else {
    $this->dao->select('c.pk_i_id as fk_i_city_id, c.fk_i_region_id, c.fk_c_country_code, c.s_name, r.s_name as s_name_top, c.d_coord_lat, c.d_coord_long');
  }

  $this->dao->from($this->getTable_city() . ' as c');
  $this->dao->join($this->getTable_region() . ' as r', 'c.fk_i_region_id = r.pk_i_id', 'INNER');

  if($pattern != '') {
    if(osc_get_current_user_locations_native() == 1) {
      $this->dao->where(sprintf('(c.s_name like "%%%s%%" OR c.s_name_native like "%%%s%%")', osc_esc_html($pattern), osc_esc_html($pattern)));
    } else {
      $this->dao->where(sprintf('c.s_name like "%%%s%%"', osc_esc_html($pattern)));
    }
  }
  
  $this->dao->limit($limit);

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->result();
    return $data;
  }

  return array();
}

// SEARCH FOR REGIONS
public function findRegions($pattern, $limit = 6) {
  if(osc_get_current_user_locations_native() == 1) {
    $this->dao->select('NULL as fk_i_city_id, pk_i_id as fk_i_region_id, fk_c_country_code, s_name, s_name_native');
  } else {
    $this->dao->select('NULL as fk_i_city_id, pk_i_id as fk_i_region_id, fk_c_country_code, s_name');
  }

  $this->dao->from($this->getTable_region());

  if($pattern != '') {
    if(osc_get_current_user_locations_native() == 1) {
      $this->dao->where(sprintf('(s_name like "%%%s%%" OR s_name_native like "%%%s%%")', osc_esc_html($pattern), osc_esc_html($pattern)));
    } else {
      $this->dao->where(sprintf('s_name like "%%%s%%"', osc_esc_html($pattern)));
    }
  }
  
  $this->dao->limit($limit);

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->result();
    return $data;
  }

  return array();
}


// SEARCH FOR COUNTRIES
public function findCountries($pattern, $limit = 6) {
  if(osc_get_current_user_locations_native() == 1) {
    $this->dao->select('NULL as fk_i_city_id, NULL as fk_i_region_id, pk_c_code as fk_c_country_code, s_name, s_name_native');
  } else {
    $this->dao->select('NULL as fk_i_city_id, NULL as fk_i_region_id, pk_c_code as fk_c_country_code, s_name');
  }

  $this->dao->from($this->getTable_country());

  if($pattern != '') {
    if(osc_get_current_user_locations_native() == 1) {
      $this->dao->where(sprintf('(s_name like "%%%s%%" OR s_name_native like "%%%s%%")', osc_esc_html($pattern), osc_esc_html($pattern)));
    } else {
      $this->dao->where(sprintf('s_name like "%%%s%%"', osc_esc_html($pattern)));
    }
  }
  
  $this->dao->limit($limit);

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->result();
    return $data;
  }

  return array();
}


// INSERT ITEM DATA
public function insertItemExtra($data) {
  return $this->dao->insert($this->getTable_item_epsilon(), $data);
}

// UPDATE ITEM EXTRA
public function updateItemExtra($id, $data) {
  return $this->dao->update($this->getTable_item_epsilon(), $data, array('fk_i_item_id' => $id));
}

// REPLACE ITEM EXTRA
public function replaceItemExtra($data) {
  return $this->dao->replace($this->getTable_item_epsilon(), $data);
}


// GET CATEGORY EXTRA
public function getCategoryExtra($category_id) {
  if($category_id <= 0) { return false; }
  
  $key = md5(osc_base_url().'ModelEPS:getCategoryExtra:'.$category_id);
  $found = null;
  $cache = osc_cache_get($key, $found);
  
  if($cache === false) {
    $this->dao->select();
    $this->dao->from($this->getTable_category_epsilon());
    $this->dao->where('fk_i_category_id', (int)$category_id);

    $result = $this->dao->get();
    
    if($result) {
      $data = $result->row();
      osc_cache_set($key, $data, OSC_CACHE_TTL);

      return $data;
    }
    
    return false;
  } else {
    return $cache;
  }
}


// GET CATEGORY EXTRA CHECK
public function getCategoryExtraRaw($category_id) {
  if($category_id <= 0) { return false; }

  $this->dao->select();
  $this->dao->from($this->getTable_category_epsilon());
  $this->dao->where('fk_i_category_id', $category_id);

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->row();
    return isset($data['fk_i_category_id']);
  }
  
  return false;
}


// INSERT ITEM DATA
public function insertCategoryExtra($data) {
  return $this->dao->insert($this->getTable_category_epsilon(), $data);
}

// UPDATE ITEM EXTRA
public function updateCategoryExtra($id, $data) {
  return $this->dao->update($this->getTable_category_epsilon(), $data, array('fk_i_category_id' => $id));
}

// REPLACE ITEM EXTRA
public function replaceCategoryExtra($data) {
  return $this->dao->replace($this->getTable_category_epsilon(), $data);
}

// GET CITIES
public function getCities($country_code, $limit = 200, $not_empty = 1) {
  if(osc_get_current_user_locations_native() == 1) {
    $this->dao->select('c.*, r.s_name as s_region_name, r.s_name_native as s_region_name_native, s.i_num_items');
  } else {
    $this->dao->select('c.*, r.s_name as s_region_name, s.i_num_items');
  }
  
  $this->dao->from($this->getTable_city() . ' as c, ' . $this->getTable_city_stats() . ' as s, ' . $this->getTable_region() . ' as r');
  $this->dao->where('c.pk_i_id = s.fk_i_city_id');
  $this->dao->where('c.fk_i_region_id = r.pk_i_id');

  if($not_empty == 1) {
    $this->dao->where('s.i_num_items > 0');
  }

  $this->dao->where('c.fk_c_country_code', strtolower($country_code));
  $this->dao->orderBy('c.s_name', 'ASC');
  $this->dao->limit($limit);

  $result = $this->dao->get();
  
  if($result) {
    $data = $result->result();
    return $data;
  }

  return array();

}


}
?>