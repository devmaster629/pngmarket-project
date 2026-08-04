<?php
class ModelATR extends DAO {
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


public function getTable_attribute() {
  return DB_TABLE_PREFIX.'t_attribute';
}

public function getTable_attribute_value() {
  return DB_TABLE_PREFIX.'t_attribute_value';
}

public function getTable_attribute_item() {
  return DB_TABLE_PREFIX.'t_item_attribute';
}

public function getTable_attribute_locale() {
  return DB_TABLE_PREFIX.'t_attribute_locale';
}

public function getTable_attribute_value_locale() {
  return DB_TABLE_PREFIX.'t_attribute_value_locale';
}

public function getTable_item() {
  return DB_TABLE_PREFIX.'t_item';
}

public function getTable_user() {
  return DB_TABLE_PREFIX.'t_user';
}

public function getTable_category() {
  return DB_TABLE_PREFIX.'t_category';
}


public function import($file) {
  $path = osc_plugin_resource($file);
  $sql = file_get_contents($path);

  if(!$this->dao->importSQL($sql) ){
    throw new Exception("Error importSQL::ModelATR<br>" . $file . "<br>" . $this->dao->getErrorLevel() . " - " . $this->dao->getErrorDesc() );
  }
}


public function install($version = '') {
  if($version == '') {
    $this->import('attributes/model/struct.sql');
    osc_set_preference('version', 106, 'plugin-attributes', 'INTEGER');
  }
}

public function versionUpdate() {
  $version = (atr_param('version') <> '' ? atr_param('version') : 100);    // v100 is initial

  if($version < 101) { 
    $this->dao->query(sprintf("ALTER TABLE %st_attribute ADD COLUMN s_search_type VARCHAR(20) DEFAULT NULL;", DB_TABLE_PREFIX));
    $this->dao->query(sprintf("ALTER TABLE %st_attribute ADD COLUMN b_search_range TINYINT(1) DEFAULT 0;", DB_TABLE_PREFIX));
    osc_set_preference('version', 101, 'plugin-attributes', 'INTEGER');
  }

  if($version < 102) { 
    $this->dao->query(sprintf("ALTER TABLE %st_attribute ADD COLUMN b_check_single TINYINT(1) DEFAULT 0;", DB_TABLE_PREFIX));
    osc_set_preference('version', 102, 'plugin-attributes', 'INTEGER');
  }

  if($version < 103) { 
    $this->dao->query(sprintf("ALTER TABLE %st_attribute ADD COLUMN s_search_engine VARCHAR(20) DEFAULT 'AND';", DB_TABLE_PREFIX));
    $this->dao->query(sprintf("ALTER TABLE %st_attribute ADD COLUMN s_search_values_all TINYINT(1) DEFAULT 0;", DB_TABLE_PREFIX));
    osc_set_preference('version', 103, 'plugin-attributes', 'INTEGER');
  }

  if($version < 104) { 
    $this->dao->query(sprintf("ALTER TABLE %st_attribute ADD COLUMN s_icon VARCHAR(200) DEFAULT NULL;", DB_TABLE_PREFIX));
    osc_set_preference('version', 104, 'plugin-attributes', 'INTEGER');
  }
  
  if($version < 105) { 
    $this->dao->query(sprintf("ALTER TABLE %st_attribute ADD COLUMN fk_i_linked_to_attr_id INT DEFAULT NULL;", DB_TABLE_PREFIX));
    osc_set_preference('version', 105, 'plugin-attributes', 'INTEGER');
  }
  
  if($version < 106) { 
    $this->dao->query(sprintf("ALTER TABLE %st_attribute ADD COLUMN s_restrict_type VARCHAR(20) DEFAULT NULL;", DB_TABLE_PREFIX));
    osc_set_preference('version', 106, 'plugin-attributes', 'INTEGER');
  }
}


public function uninstall() {
  // DELETE ALL TABLES
  $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_attribute()));
  $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_attribute_value()));
  $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_attribute_item()));
  $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_attribute_locale()));
  $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_attribute_value_locale()));


  // DELETE ALL PREFERENCES
  $db_prefix = DB_TABLE_PREFIX;
  $query = "DELETE FROM {$db_prefix}t_preference WHERE s_section = 'plugin-attributes'";
  $this->dao->query($query);
}



// GET ATTRIBUTES
public function getAttributes($enabled = 0, $category_id = '') {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAttributes::' . (string)$enabled . (string)$category_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select();
    $this->dao->from($this->getTable_attribute() . ' as a');
    $this->dao->orderby('a.i_order ASC, a.pk_i_id ASC');

    if($enabled == 1) {
      $this->dao->where('a.b_enabled', 1);
    }

    if($category_id > 0) {
      $root = Category::newInstance()->toRootTree($category_id);
      $root_ids = array_column($root, 'pk_i_id');
      $root_id = $root_ids[0];

      $root_id = ',' . $root_id . ',';
      $category_id = ',' . $category_id . ',';

      $this->dao->where('((concat(concat(",", s_category_id), ",") like "%' . $root_id . '%") OR (concat(concat(",", s_category_id), ",") like "%' . $category_id . '%") OR coalesce(s_category_id, 0) = 0)');
    } else if ($category_id == -1) {
      $this->dao->where('concat(concat(",", a.s_category_id), ",") like "%,-1,%"');      // Show if category is not selected
    }
    
    $result = $this->dao->get();
    
    
    if($result) {
      $attributes = $result->result();
      $locales = $this->getAllAttributeLocales();

      $j = 0;

      if(is_array($attributes) && count($attributes) > 0) {
        foreach($attributes as $a) {
          $attributes[$j]['s_name'] = @$locales[$a['pk_i_id']][atr_get_locale()];
          $attributes[$j]['locales'] = @$locales[$a['pk_i_id']];
          $attributes[$j]['values'] = $this->getAllAttributeValues($a, 0, false);
          $attributes[$j]['values_count'] = $this->countAllAttributeValues($a['pk_i_id']);
          $j++;
        }
      }

      osc_cache_set($cache_key, $attributes, atr_cache_ttl());
      return $attributes;
    }

    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  } 
}


// GET ATTRIBUTES
public function getAttributesForLinkTo() {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAttributesForLinkTo');
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select();
    $this->dao->from($this->getTable_attribute() . ' as a');
    $this->dao->where('a.s_type in ("SELECT","TEXT","NUMBER","DATE","URL","PHONE","EMAIL")');
    $this->dao->orderby('a.i_order ASC, a.pk_i_id ASC');
    $result = $this->dao->get();

    if($result) {
      $attributes = $result->result();
      $locales = $this->getAllAttributeLocales();

      $j = 0;

      if(count($attributes) > 0) {
        foreach($attributes as $a) {
          $attributes[$j]['s_name'] = @$locales[$a['pk_i_id']][atr_get_locale()];
          $attributes[$j]['locales'] = @$locales[$a['pk_i_id']];
          
          $j++;
        }
      }

      osc_cache_set($cache_key, $attributes, atr_cache_ttl());
      return $attributes;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  } 
}


// GET LINKED ATTRIBUTE
public function getLinkedAttribute($attribute_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getLinkedAttribute::' . (string)$attribute_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select();
    $this->dao->from($this->getTable_attribute() . ' as a');
    $result = $this->dao->where('a.fk_i_linked_to_attr_id', $attribute_id);
    $result = $this->dao->get();
    
    if($result) {
      $attribute = $result->row();
      $locales = $this->getAllAttributeLocales();

      if(isset($attribute['pk_i_id'])) {
        $attribute['s_name'] = @$locales[$attribute['pk_i_id']][atr_get_locale()];
        $attribute['locales'] = @$locales[$attribute['pk_i_id']];
        $attribute['values'] = $this->getAllAttributeValues($attribute);
      }

      osc_cache_set($cache_key, $attribute, atr_cache_ttl());
      return $attribute;
    }
    
    osc_cache_set($cache_key, false, atr_cache_ttl());
    return false;
  } 
}


// GET ATTRIBUTE WITH DETAILS
public function getAttributeDetail($attribute_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAttributeDetail::' . (string)$attribute_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select();
    $this->dao->from($this->getTable_attribute() . ' as a');
    $result = $this->dao->where('a.pk_i_id', $attribute_id);
    $result = $this->dao->get();
    
    if($result) {
      $attribute = $result->row();
      $locales = $this->getAllAttributeLocales();

      if(isset($attribute['pk_i_id'])) {
        $attribute['s_name'] = @$locales[$attribute['pk_i_id']][atr_get_locale()];
        $attribute['locales'] = @$locales[$attribute['pk_i_id']];
        $attribute['values'] = $this->getAllAttributeValues($attribute);
      }

      osc_cache_set($cache_key, $attribute, atr_cache_ttl());
      return $attribute;
    }
    
    osc_cache_set($cache_key, false, atr_cache_ttl());
    return false;
  } 
}




// GET REQUIRED ATTRIBUTES
public function getRequiredAttributes($category_id = '') {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getRequiredAttributes::' . (string)$category_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select();
    $this->dao->from($this->getTable_attribute() . ' as a');
    $this->dao->where('a.b_enabled', 1);
    $this->dao->where('a.b_required', 1);

    if($category_id > 0) {
      $root = Category::newInstance()->toRootTree($category_id);
      $root_ids = array_column($root, 'pk_i_id');
      $root_id = $root_ids[0];

      $root_id = ',' . $root_id . ',';
      $category_id = ',' . $category_id . ',';

      $this->dao->where('((concat(concat(",", a.s_category_id), ",") like "%' . $root_id . '%") OR (concat(concat(",", a.s_category_id), ",") like "%' . $category_id . '%") OR a.s_category_id is null OR a.s_category_id = "")');
    } else if ($category_id == -1) {
      $this->dao->where('concat(concat(",", a.s_category_id), ",") like "%,-1,%"');      // Show if category is not selected
    }  

    $result = $this->dao->get();
    
    if($result) {
      $attributes = $result->result();
      $locales = $this->getAllAttributeLocales();

      $j = 0;

      if(count($attributes) > 0) {
        foreach($attributes as $a) {

          $attributes[$j]['s_name'] = @$locales[$a['pk_i_id']][atr_get_locale()];
          $attributes[$j]['locales'] = @$locales[$a['pk_i_id']];
          $attributes[$j]['values'] = $this->getAllAttributeValues($a, 0, false);
          $j++;
        }
      }

      osc_cache_set($cache_key, $attributes, atr_cache_ttl());
      return $attributes;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  } 
}


// GET SEARCH ATTRIBUTES v2
public function getSearchAttributes2($category_id = '') {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getSearchAttributes2::' . (string)$category_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select();
    $this->dao->from($this->getTable_attribute() . ' as a');
    $this->dao->where('a.b_enabled', 1);
    $this->dao->where('a.b_search', 1);
    $this->dao->where('coalesce(a.fk_i_linked_to_attr_id, 0) <= 0');
    $this->dao->orderby('a.i_order ASC, a.pk_i_id ASC');

    if($category_id > 0) {
      $root = Category::newInstance()->toRootTree($category_id);
      $root_ids = array_column($root, 'pk_i_id');
      $root_id = $root_ids[0];

      $root_id = ',' . $root_id . ',';
      $category_id = ',' . $category_id . ',';

      $this->dao->where('((concat(concat(",", a.s_category_id), ",") like "%' . $root_id . '%") OR (concat(concat(",", a.s_category_id), ",") like "%' . $category_id . '%") OR a.s_category_id is null OR a.s_category_id = "")');
    } else if ($category_id == -1) {
      $this->dao->where('concat(concat(",", a.s_category_id), ",") like "%,-1,%"');      // Show if category is not selected
    }
    

    $result = $this->dao->get();
    
    if($result) {
      $attributes = $result->result();
      $locales = $this->getAllAttributeLocales();

      $j = 0;

      if(count($attributes) > 0) {
        foreach($attributes as $a) {
          $attributes[$j]['s_name'] = @$locales[$a['pk_i_id']][atr_get_locale()];
          $attributes[$j]['locales'] = @$locales[$a['pk_i_id']];
          $attributes[$j]['values'] = $this->getAllAttributeValues($a);
          $j++;
        }
      }

      osc_cache_set($cache_key, $attributes, atr_cache_ttl());
      return $attributes;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  } 
}



// GET SEARCH ATTRIBUTES
public function getSearchAttributes($category_id = '') {
  return $this->getSearchAttributes2($category_id);
}


// GET ATTRIBUTE VALUES
public function getAttributeValues($type, $attribute_id, $parent_id = NULL, $id_mod = false) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAttributeValues::' . (string)$type . (string)$attribute_id . (string)$parent_id . (string)$id_mod);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select();
    $this->dao->from($this->getTable_attribute_value() . ' as v');
    $this->dao->where('v.fk_i_attribute_id', $attribute_id);

    if($type == 'SELECT') {
      if($parent_id > 0) {
        $this->dao->where('v.fk_i_parent_id', $parent_id);
      } else {
        $this->dao->where('v.fk_i_parent_id is null');
      }
    }

    $this->dao->orderby('v.i_order ASC, v.pk_i_id ASC');

    $result = $this->dao->get();

    $output = array();
    
    if($result) { 
      $values = $result->result();
      $values_nested = $this->prepareNestedValues($values);
      $locales = $this->getAllAttributeValueLocales($attribute_id);

      if(is_array($values) && count($values) > 0) {
        foreach($values as $v) {
          $output[$v['pk_i_id']] = $v;
          $output[$v['pk_i_id']]['s_name'] = @$locales[$v['pk_i_id']][atr_get_locale()];
          $output[$v['pk_i_id']]['locales'] = @$locales[$v['pk_i_id']];

          if($type == 'SELECT') {
            $output[$v['pk_i_id']]['values'] = $this->loopAttributeValues($values_nested[$attribute_id], $locales, $v['pk_i_id']);
          }

          if($type == 'SELECT' && $v['fk_i_parent_id'] > 0 && $parent_id == NULL) {
            unset($output[$v['pk_i_id']]);     // remove non-root values for dropdowns
          }
        }
      }
      
      osc_cache_set($cache_key, $output, atr_cache_ttl());
      return $output;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  } 
}


// GET ALL ATTRIBUTE VALUES v2
public function getAllAttributeValues($attribute, $parent_id = 0) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAttributes::' . (string)(isset($attribute['pk_i_id']) ? $attribute['pk_i_id'] : 0) . (string)$parent_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select();
    $this->dao->from($this->getTable_attribute_value() . ' as v');
    $this->dao->where('v.fk_i_attribute_id', $attribute['pk_i_id']);
    $this->dao->orderby('v.i_order ASC, v.pk_i_id ASC');

    $result = $this->dao->get();
    $output = array();

    if($result) {
      $values = $result->result();
      $values_nested = $this->prepareNestedValues($values);
      $locales = $this->getAllAttributeValueLocales($attribute['pk_i_id']);

      $i = 0;
      if(count($values) > 0) {
        foreach($values as $v) {
          $output[$v['pk_i_id']] = $v;
          @$output[$v['pk_i_id']]['s_name'] = @$locales[$v['pk_i_id']][atr_get_locale()];
          @$output[$v['pk_i_id']]['locales'] = @$locales[$v['pk_i_id']];

          if($attribute['s_type'] == 'SELECT') {
            $output[$v['pk_i_id']]['values'] = $this->loopAttributeValues($values_nested[$attribute['pk_i_id']], $locales, $v['pk_i_id']);
          }

          if($parent_id > 0 && $v['fk_i_parent_id'] <> $parent_id) {
            unset($output[$v['pk_i_id']]);     // remove non-root values for dropdowns
          } else if($attribute['s_type'] == 'SELECT' && $v['fk_i_parent_id'] > 0) {
            unset($output[$v['pk_i_id']]);     // remove non-root values for dropdowns
          }

          $i++;
        }
      }

      osc_cache_set($cache_key, $output, atr_cache_ttl());
      return $output;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  } 
}

// GET ALL ATTRIBUTE VALUES AS FLAT FILE
public function getAllAttributeValuesFlat($attribute_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAttributes::' . (string)$attribute_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select();
    $this->dao->from($this->getTable_attribute_value() . ' as v');
    $this->dao->where('v.fk_i_attribute_id', $attribute_id);
    $this->dao->orderby('v.i_order ASC, v.pk_i_id ASC');

    $result = $this->dao->get();
    $output = array();

    if($result) {
      $output = array();
      $values = $result->result();

      foreach($values as $v) {
        $output[$v['pk_i_id']] = $v;
      }

      osc_cache_set($cache_key, $output, atr_cache_ttl());
      return $output;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  } 
}


// COUNT ALL ATTRIBUTE VALUES
public function countAllAttributeValues($attribute_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::countAllAttributeValues::' . (string)$attribute_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('count(distinct v.pk_i_id) as i_count');
    $this->dao->from($this->getTable_attribute_value() . ' as v');
    $this->dao->where('v.fk_i_attribute_id', $attribute_id);

    $result = $this->dao->get();

    if($result) {
      $data = $result->row();
      $count = ($data['i_count'] > 0 ? $data['i_count'] : 0);

      osc_cache_set($cache_key, $count, atr_cache_ttl());
      return $count;
    }
    
    osc_cache_set($cache_key, 0, atr_cache_ttl());
    return 0;
  } 
}


public function prepareNestedValues($values) {
  $values_nested = array();
  
  if(is_array($values) && count($values) > 0) {
    foreach($values as $v) {
      if(!isset($values_nested[$v['fk_i_attribute_id']])) {
        $values_nested[$v['fk_i_attribute_id']] = array();
      }
      
      $parent = ($v['fk_i_parent_id'] > 0 ? $v['fk_i_parent_id'] : 0);
      
      if(!isset($values_nested[$v['fk_i_attribute_id']][$parent])) {
        $values_nested[$v['fk_i_attribute_id']][$parent] = array();
      }
      
      $values_nested[$v['fk_i_attribute_id']][$parent][] = $v;
    }
  }
  
  return $values_nested;
}


public function loopAttributeValues($values, $locales, $parent_id = 0, $level = 1) {
  $output = array();

  if(isset($values[$parent_id]) && is_array($values[$parent_id]) && count($values[$parent_id]) > 0) {
    foreach($values[$parent_id] as $v) {
      if($v['fk_i_parent_id'] == $parent_id && $v['fk_i_parent_id'] <> $v['pk_i_id']) {
        $output[$v['pk_i_id']] = $v;
        $output[$v['pk_i_id']]['s_name'] = @$locales[$v['pk_i_id']][atr_get_locale()];
        $output[$v['pk_i_id']]['locales'] = @$locales[$v['pk_i_id']];
        $output[$v['pk_i_id']]['values'] = $this->loopAttributeValues($values, $locales, $v['pk_i_id'], $level + 1);
      }
    }
  }

  return $output;
}




// GET ATTRIBUTE VALUE CHILDREN
public function getAttributeValueChildren($type, $attribute_id, $attribute_value_id = '') {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAttributeValueChildren::' . (string)$type . (string)$attribute_id . (string)$attribute_value_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('*');
    $this->dao->from($this->getTable_attribute_value());

    $this->dao->where('fk_i_attribute_id', $attribute_id);

    if($type == 'SELECT') {
      if($attribute_value_id > 0) {
        $this->dao->where('fk_i_parent_id', $attribute_value_id);
      } else {
        $this->dao->where('fk_i_parent_id is null');
      }
    }

    $result = $this->dao->get();
    
    if($result) {
      $values = $result->result();

      if(count($values) > 0 && $type == 'SELECT') {
        foreach($values as $v) {
          $values = array_merge($values, $this->getAttributeValueChildren($type, $v['fk_i_attribute_id'], $v['pk_i_id']));
        }
      }

      osc_cache_set($cache_key, $values, atr_cache_ttl());
      return $values;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  } 
}


// GET ATTRIBUTE VALUE
public function getAttributeValue($attribute_value_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAttributeValue::' . (string)$attribute_value_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('DISTINCT v.*, vl.s_name, vl.fk_c_locale_code');
    $this->dao->from($this->getTable_attribute_value() . ' as v');
    $this->dao->join($this->getTable_attribute_value_locale() . ' as vl', '(v.pk_i_id = vl.fk_i_attribute_value_id AND vl.fk_c_locale_code = "' . atr_get_locale() . '")', 'LEFT OUTER');

    $this->dao->where('v.pk_i_id', $attribute_value_id);

    $result = $this->dao->get();
    
    if($result) {
      $value = $result->row();
      $value['locales'] = $this->getAttributeValueLocales($attribute_value_id);

      osc_cache_set($cache_key, $value, atr_cache_ttl());
      return $value;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  } 
}


// GET ATTRIBUTE VALUES BY PARENT
public function getAttributeValuesByParent($attribute_id, $attribute_value_parent_id = 0) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAttributeValuesByParent::' . (string)$attribute_id . (string)$attribute_value_parent_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select();
    $this->dao->from($this->getTable_attribute_value() . ' as v');
    $this->dao->where('v.fk_i_attribute_id', $attribute_id);

    if($attribute_value_parent_id > 0) {
      $this->dao->where('v.fk_i_parent_id', $attribute_value_parent_id);
    } else {
      $this->dao->where('v.fk_i_parent_id is null');
    }

    $this->dao->orderby('v.i_order ASC, v.pk_i_id ASC');

    $result = $this->dao->get();
    
    if($result) {
      $values = $result->result();
      $locales = $this->getAllAttributeValueLocales($attribute_id);

      if(count($values) > 0) {

        $i = 0;
        foreach($values as $v) {
          $values[$i]['locales'] = @$locales[$v['pk_i_id']];
          $i++;
        }
      }

      osc_cache_set($cache_key, $values, atr_cache_ttl());
      return $values;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  } 
}


// GET ATTRIBUTE VALUE ROW
public function getAttributeValueRow($attribute_value_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAttributeValueRow::' . (string)$attribute_value_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('*');
    $this->dao->from($this->getTable_attribute_value());
    $this->dao->where('pk_i_id', $attribute_value_id);

    $result = $this->dao->get();
    
    if($result) {
      $data = $result->row();

      osc_cache_set($cache_key, $data, atr_cache_ttl());
      return $data;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  } 
}


// GET ALL ATTRIBUTE VALUE ROW
public function getAllAttributeValueRows($attribute_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAllAttributeValueRows::' . (string)$attribute_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('*');
    $this->dao->from($this->getTable_attribute_value());
    $this->dao->where('fk_i_attribute_id', $attribute_id);

    $result = $this->dao->get();
    $output = array();

    if($result) {
      $data = $result->result();

      if(count($data) > 0) {
        foreach($data as $d) {
          $output[$d['pk_i_id']] = $d;
        }
      }

      osc_cache_set($cache_key, $output, atr_cache_ttl());
      return $output;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  } 
}


// GET HIERARCHY OF ATTRIBUTE VALUE
public function getAttributeValueHierarchy($attribute_value_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAttributeValueHierarchy::' . (string)$attribute_value_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $hierarchy = array();

    $this->dao->select('*');
    $this->dao->from($this->getTable_attribute_value());
    $this->dao->where('pk_i_id', $attribute_value_id);

    $result = $this->dao->get();
    
    $hierarchy[] = $attribute_value_id;

    if($result) {
      $value = $result->row();

      if(isset($value['fk_i_parent_id']) && $value['fk_i_parent_id'] > 0) {
        $hierarchy = array_merge($hierarchy, $this->getAttributeValueHierarchy($value['fk_i_parent_id']));
      }

      osc_cache_set($cache_key, $hierarchy, atr_cache_ttl());
      return $hierarchy;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  } 
}

// GET ITEM ATTRIBUTE VALUES
public function getItemAttributes($item_id, $category_id = NULL) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getItemAttributes::' . (string)$item_id . (string)$category_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('DISTINCT a.*');
    //$this->dao->from($this->getTable_attribute_item() . ' as i, ' . $this->getTable_attribute() . ' as a');
    $this->dao->from($this->getTable_attribute() . ' as a');
    
    //$this->dao->join( $this->getTable_attribute_item() . ' as i', '(a.pk_i_id = i.fk_i_attribute_id AND ((i.fk_i_item_id = ' . $item_id . ' AND (i.fk_i_attribute_value_id > 0 OR trim(s_value) <> "")) OR a.s_type = "DIVIDER"))', 'LEFT OUTER');
    $this->dao->join( $this->getTable_attribute_item() . ' as i', '(a.pk_i_id = i.fk_i_attribute_id AND (i.fk_i_item_id = ' . $item_id . ' OR a.s_type = "DIVIDER"))', 'LEFT OUTER');

    //$this->dao->where('i.fk_i_item_id', $item_id);
    //$this->dao->where('a.pk_i_id = i.fk_i_attribute_id');
    $this->dao->where('a.b_enabled = 1');
    $this->dao->orderby('a.i_order ASC, a.pk_i_id ASC');
    
    if($category_id > 0) {
      $root = Category::newInstance()->toRootTree($category_id);
      $root_ids = array_column($root, 'pk_i_id');
      $root_id = $root_ids[0];

      $root_id = ',' . $root_id . ',';
      $category_id = ',' . $category_id . ',';

      $this->dao->where('((concat(concat(",", a.s_category_id), ",") like "%' . $root_id . '%") OR (concat(concat(",", a.s_category_id), ",") like "%' . $category_id . '%") OR a.s_category_id is null OR a.s_category_id = "")');
    } else if ($category_id == -1) {
      $this->dao->where('concat(concat(",", a.s_category_id), ",") like "%,-1,%"');      // Show if category is not selected
    }

    $result = $this->dao->get();
    
    if($result) {
      $attributes = $result->result();
      $locales = $this->getAllAttributeLocales();

      if(count($attributes) > 0) {

        $i = 0;
        foreach($attributes as $a) {
          $attributes[$i]['s_name'] = @$locales[$a['pk_i_id']][atr_get_locale()];
          $attributes[$i]['locales'] = @$locales[$a['pk_i_id']];

          $i++;
        }
      }

      osc_cache_set($cache_key, $attributes, atr_cache_ttl());
      return $attributes;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  }
}


// GET ITEM ATTRIBUTE VALUES
public function getItemAttributeValues($item_id, $attribute_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getItemAttributeValues::' . (string)$item_id . (string)$attribute_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('DISTINCT i.pk_i_id, i.fk_i_item_id, i.fk_i_attribute_id, i.fk_i_attribute_value_id, i.s_value');
    $this->dao->from($this->getTable_attribute_item() . ' as i');
    $this->dao->where('i.fk_i_item_id', $item_id);
    $this->dao->where('i.fk_i_attribute_id', $attribute_id);

    $result = $this->dao->get();
    
    if($result) {
      $data = $result->row();

      osc_cache_set($cache_key, $data, atr_cache_ttl());
      return $data;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  }
}




// GET EXISTING ITEM ATTRIBUTE VALUES
public function getItemValuesList($attribute_id) {
  // Add join to t_item table and filter by category ID in order to show values by category on search page
  // $cat_id = osc_search_category_id()[0] ?? 0;
  
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getItemValuesList::' . (string)$attribute_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('DISTINCT i.fk_i_attribute_value_id');
    $this->dao->from($this->getTable_attribute_item() . ' as i');
    $this->dao->where('i.fk_i_attribute_id', $attribute_id);

    $result = $this->dao->get();
    
    if($result) {
      $data = $result->result();
      $values = $this->getAllAttributeValuesFlat($attribute_id);
      $output = array();

      foreach($data as $d) {
        $output = $this->getValueToRoot($d['fk_i_attribute_value_id'], $values, $output);
      }

      $output = array_filter(array_unique($output));

      osc_cache_set($cache_key, $output, atr_cache_ttl());
      return $output;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  }
}


// GENERATE HIERARCHY
public function getValueToRoot($value_id, $values, $data) {
  $data[] = $value_id;

  if(@$values[$value_id]['fk_i_parent_id'] > 0) {
    $data[] = $values[$value_id]['fk_i_parent_id'];
    return $this->getValueToRoot($values[$value_id]['fk_i_parent_id'], $values, $data);
  }

  return $data;
}


// GET ITEM ATTRIBUTE VALUES ALL
public function getAllItemAttributeValues($item_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAllItemAttributeValues::' . (string)$item_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('DISTINCT i.pk_i_id, i.fk_i_item_id, i.fk_i_attribute_id, i.fk_i_attribute_value_id, i.s_value');
    $this->dao->from($this->getTable_attribute_item() . ' as i');
    $this->dao->where('i.fk_i_item_id', $item_id);

    $result = $this->dao->get();
    
    if($result) {
      $data = $result->result();
      $locales = $this->getAllAttributeLocales();
      $output = array();

      if(count($data) > 0) {
        foreach($data as $d) {
          $output[$d['fk_i_attribute_id']] = $d;
          $output[$d['fk_i_attribute_id']]['s_name'] = @$locales[$d['fk_i_attribute_id']][atr_get_locale()];
          $output[$d['fk_i_attribute_id']]['locales'] = @$locales[$d['fk_i_attribute_id']];
        }
      }

      osc_cache_set($cache_key, $output, atr_cache_ttl());
      return $output;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  }
}


// GET ITEM ATTRIBUTE VALUES ALL WITH VALUE LOCALES
public function getAllItemAttributeValuesWithLocale($item_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAllItemAttributeValuesWithLocale::' . (string)$item_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('DISTINCT i.pk_i_id, i.fk_i_item_id, i.fk_i_attribute_id, i.fk_i_attribute_value_id, i.s_value');
    $this->dao->from($this->getTable_attribute_item() . ' as i');
    $this->dao->where('i.fk_i_item_id', $item_id);

    $result = $this->dao->get();
    
    if($result) {
      $data = $result->result();
      $atr_locales = $this->getAllAttributeLocales();
      $output = array();

      if(count($data) > 0) {
        foreach($data as $d) {
          $val_locales = $this->getAllAttributeValueLocales($d['fk_i_attribute_id']);

          if(!isset($output[$d['fk_i_attribute_id']])) {
            $output[$d['fk_i_attribute_id']] = array();
            $output[$d['fk_i_attribute_id']]['pk_i_id'] = $d['pk_i_id'];
            $output[$d['fk_i_attribute_id']]['fk_i_attribute_id'] = $d['fk_i_attribute_id'];
            $output[$d['fk_i_attribute_id']]['fk_i_item_id'] = $d['fk_i_item_id'];
            $output[$d['fk_i_attribute_id']]['s_name'] = @$atr_locales[$d['fk_i_attribute_id']][atr_get_locale()];
            $output[$d['fk_i_attribute_id']]['locales'] = @$atr_locales[$d['fk_i_attribute_id']];
            $output[$d['fk_i_attribute_id']]['values'] = array();
          }
          
          $value_id = (@$d['fk_i_attribute_value_id'] > 0 ? $d['fk_i_attribute_value_id'] : 0);
          $output[$d['fk_i_attribute_id']]['values'][$value_id] = array();
          $output[$d['fk_i_attribute_id']]['values'][$value_id]['pk_i_id'] = ($value_id > 0 ? $d['fk_i_attribute_value_id'] : 0);
          $output[$d['fk_i_attribute_id']]['values'][$value_id]['s_value'] = $d['s_value'];
          $output[$d['fk_i_attribute_id']]['values'][$value_id]['s_name'] = ($value_id > 0 ? @$val_locales[$d['fk_i_attribute_value_id']][atr_get_locale()] : '');
          $output[$d['fk_i_attribute_id']]['values'][$value_id]['locales'] = ($value_id > 0 ? @$val_locales[$d['fk_i_attribute_value_id']] : array());
   
          $hierarchy = ($value_id > 0 ? atr_attribute_value_hierarchy($value_id) : array());
          
          $hier_array = array();
          if(count($hierarchy) > 0) {
            foreach($hierarchy as $h) {
              $hier_array[$h] = array();
              $hier_array[$h]['pk_i_id'] = $h;
              $hier_array[$h]['s_name'] = @$val_locales[$h][atr_get_locale()];
              $hier_array[$h]['locales'] = @$val_locales[$h];
            }
          }

          $output[$d['fk_i_attribute_id']]['values'][$value_id]['hierarchy'] = $hier_array;

        }
      }

      osc_cache_set($cache_key, $output, atr_cache_ttl());
      return $output;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  }
}

// GET ITEM ATTRIBUTE VALUE ROWS (checkbox / radio)
public function getItemAttributeValueRows($item_id, $attribute_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getItemAttributeValueRows::' . (string)$item_id . (string)$attribute_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('v.*, vl.s_name, vl.fk_c_locale_code, i.s_value');
    $this->dao->from($this->getTable_attribute_item() . ' as i');
    $this->dao->where('i.fk_i_item_id', $item_id);
    $this->dao->where('i.fk_i_attribute_id', $attribute_id);

    $this->dao->join( $this->getTable_attribute_value() . ' as v', 'v.pk_i_id = i.fk_i_attribute_value_id', 'INNER');
    $this->dao->join( $this->getTable_attribute_value_locale() . ' as vl', '(v.pk_i_id = vl.fk_i_attribute_value_id AND vl.fk_c_locale_code = "' . atr_get_locale() . '")', 'LEFT OUTER');

    $this->dao->orderby('v.i_order ASC, v.pk_i_id ASC');

    $result = $this->dao->get();

    if($result) {
      $values = $result->result();
      $locales = $this->getAllAttributeValueLocales($attribute_id);

      if(count($values) > 0) {

        $i = 0;
        foreach($values as $v) {
          $values[$i]['locales'] = @$locales[$v['pk_i_id']];
          $i++;
        }
      }

      osc_cache_set($cache_key, $values, atr_cache_ttl());
      return $values;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  }
}


// GET ITEM ATTRIBUTE VALUE ROWS THAT WERE NOT SELECTED(checkbox / radio)
public function getItemAttributeValueRowsNotSelected($item_id, $attribute_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getItemAttributeValueRowsNotSelected::' . (string)$item_id . (string)$attribute_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('v.*, vl.s_name, vl.fk_c_locale_code, i.s_value');
    $this->dao->from($this->getTable_attribute_value() . ' as v');
    $this->dao->where('i.fk_i_item_id', $item_id);
    $this->dao->where('i.fk_i_attribute_id', $attribute_id);

    $this->dao->join( $this->getTable_attribute_item() . ' as i', '(v.pk_i_id = i.fk_i_attribute_value_id AND i.fk_i_item_id = ' . $item_id . ' AND i.fk_i_attribute_id = ' . $attribute_id . ')', 'LEFT OUTER');
    $this->dao->join( $this->getTable_attribute_value_locale() . ' as vl', '(v.pk_i_id = vl.fk_i_attribute_value_id AND vl.fk_c_locale_code = "' . atr_get_locale() . '")', 'LEFT OUTER');

    $this->dao->where('v.pk_i_id != coalesce(i.fk_i_attribute_value_id, -1)');


    $this->dao->orderby('v.i_order ASC, v.pk_i_id ASC');

    $result = $this->dao->get();
    
    if($result) {
      $values = $result->result();
      $locales = $this->getAllAttributeValueLocales($attribute_id);

      if(count($values) > 0) {

        $i = 0;
        foreach($values as $v) {
          $values[$i]['locales'] = @$locales[$v['pk_i_id']];
          $i++;
        }
      }

      osc_cache_set($cache_key, $values, atr_cache_ttl());
      return $values;
    }
    
    osc_cache_set($cache_key, array(), atr_cache_ttl());
    return array();
  }
}


// GET ITEM ATTRIBUTE RAW
public function getItemAttributeRaw($item_id, $attribute_id, $attribute_value_id = '') {
  if($item_id > 0) {
    $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getItemAttributeRaw::' . (string)$item_id . (string)$attribute_id . (string)$attribute_value_id);
    $found = null;
    $cache = osc_cache_get($cache_key, $found);

    if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
      return $cache;
    } else {
      $this->dao->select('*');
      $this->dao->from($this->getTable_attribute_item());
      $this->dao->where('fk_i_item_id', $item_id);
      $this->dao->where('fk_i_attribute_id', $attribute_id);

      if($attribute_value_id > 0) {
        $this->dao->where('fk_i_attribute_value_id', $attribute_value_id);
      }

      $result = $this->dao->get();
    
      if($result) {
        $data = $result->row();

        osc_cache_set($cache_key, $data, atr_cache_ttl());
        return $data;
      }
      
      osc_cache_set($cache_key, array(), atr_cache_ttl());
      return array();
    }
  } else {
    $get_param = atr_get_attribute_names($attribute_id, $attribute_value_id);
    $param_name = @$get_param[0];

    if($param_name <> '') {
      $param_value = Params::getParam($param_name);
      $session_value = Session::newInstance()->_getForm($param_name);
      $value = ($param_value <> '' ? $param_value : $session_value);
      $value = ($value == 'on' ? 1 : $value);
    } else {
      $value = '';
    }

    return array(
      'fk_i_attribute_id' => $attribute_id,
      'fk_i_attribute_value_id' => $attribute_value_id,
      's_value' => $value
    );
  }  
}


// UPDATE ITEM ATTRIBUTE VALUE
public function updateItemAttributeValue($item_id, $attribute_id, $attribute_value_id = '', $value = '') {
  if($attribute_value_id > 0 || $value <> '') {
    $values = array(
      'fk_i_item_id' => $item_id,
      'fk_i_attribute_id' => $attribute_id,
      'fk_i_attribute_value_id' => $attribute_value_id,
      's_value' => $value
    );

    $this->dao->insert($this->getTable_attribute_item(), $values);
  }
}



// REMOVE ATTRIBUTE
public function removeAttribute($id) {
  return $this->dao->delete($this->getTable_attribute(), array('pk_i_id' => $id));
}

// REMOVE ATTRIBUTE VALUE
public function removeAttributeValue($id) {
  return $this->dao->delete($this->getTable_attribute_value(), array('pk_i_id' => $id));
}

// REMOVE ITEM ATTRIBUTES
public function removeItemAttributes($item_id) {
  return $this->dao->delete($this->getTable_attribute_item(), array('fk_i_item_id' => $item_id));
}

// INSERT NEW ATTRIBUTE VALUE
public function insertAttributeValue($attribute_id, $name = '', $locale = '') {
  $this->dao->insert($this->getTable_attribute_value(), array('fk_i_attribute_id' => $attribute_id, 'i_order' => 9999));
  return $this->dao->insertedId();
}


// INSERT NEW ATTRIBUTE
public function insertAttribute() {
  $this->dao->insert($this->getTable_attribute(), array('s_type' => 'SELECT', 'i_order' => 9999));
  return $this->dao->insertedId();
}



// UPDATE ATTRIBUTE POSITION
public function updateAttributePosition($attribute_id, $order) {
  $this->dao->update($this->getTable_attribute(), array('i_order' => $order), array('pk_i_id' => $attribute_id));
}


// UPDATE ATTRIBUTE VALUE POSITION
public function updateAttributeValuePosition($value_id, $parent_id, $order) {
  if($parent_id <= 0 || $parent_id == '' || !$parent_id) {
    $parent_id = null;
  }

  $this->dao->update($this->getTable_attribute_value(), array('fk_i_parent_id' => $parent_id, 'i_order' => $order), array('pk_i_id' => $value_id));
}


// UPDATE ATTRIBUTE
public function updateAttribute($data) {
  if(!isset($data['pk_i_id']) || $data['pk_i_id'] <= 0) {
    return false;
  }

  $values = array(
    's_identifier' => @$data['s_identifier'],
    'b_enabled' => (@$data['b_enabled'] == 'on' ? 1 : @$data['b_enabled']),
    'b_required' => (@$data['b_required'] == 'on' ? 1 : @$data['b_required']),
    'b_search' => (@$data['b_search'] == 'on' ? 1 : @$data['b_search']),
    'b_hook' => (@$data['b_hook'] == 'on' ? 1 : @$data['b_hook']),
    'b_values_all' => (@$data['b_values_all'] == 'on' ? 1 : @$data['b_values_all']),
    's_category_id' => @$data['s_category_id'],
    's_type' => @$data['s_type'],
    's_search_type' => @$data['s_search_type'],
    's_search_engine' => @$data['s_search_engine'],
    's_search_values_all' => @$data['s_search_values_all'],
    'b_search_range' => (@$data['b_search_range'] == 'on' ? 1 : @$data['b_search_range']),
    'b_check_single' => (@$data['b_check_single'] == 'on' ? 1 : @$data['b_check_single']),
    's_icon' => @$data['s_icon'],
    'fk_i_linked_to_attr_id' => @$data['fk_i_linked_to_attr_id'],
    's_restrict_type' => @$data['s_restrict_type']
  );

  $where = array(
    'pk_i_id' => $data['pk_i_id']
  );


  $this->dao->update($this->getTable_attribute(), $values, $where);
  $error = array('code' => $this->dao->getErrorLevel(), 'message' => $this->dao->getErrorDesc());

  $this->updateAttributeLocale($data['pk_i_id'], @$data['s_name'], @$data['fk_c_locale_code']);

  return $error;
}


// UPDATE ATTRIBUTE LOCALE
public function updateAttributeLocale($id, $name, $locale) {
  $values = array(
    'fk_i_attribute_id' => $id,
    'fk_c_locale_code' => $locale,
    's_name' => $name
  );

  $where = array(
    'fk_i_attribute_id' => $id,
    'fk_c_locale_code' => $locale
  );


  $check = $this->getAttributeLocale($id, $locale);
  if(@$check['pk_i_id'] > 0) {
    $this->dao->update($this->getTable_attribute_locale(), $values, $where);
  } else {
    $this->dao->insert($this->getTable_attribute_locale(), $values);
  }
}



// UPDATE ATTRIBUTE
public function updateAttributeValue($data) {
  if(!isset($data['pk_i_id']) || $data['pk_i_id'] <= 0) {
    return false;
  }


  $values = array(
    's_image' => @$data['s_image']
  );

  $where = array(
    'pk_i_id' => $data['pk_i_id']
  );


  $this->dao->update($this->getTable_attribute_value(), $values, $where);
  $this->updateAttributeValueLocale($data['pk_i_id'], @$data['s_name'], @$data['fk_c_locale_code']);
}



// UPDATE ATTRIBUTE VALUE LOCALE
public function updateAttributeValueLocale($id, $name, $locale) {
  $values = array(
    'fk_i_attribute_value_id' => $id,
    'fk_c_locale_code' => $locale,
    's_name' => $name
  );

  $where = array(
    'fk_i_attribute_value_id' => $id,
    'fk_c_locale_code' => $locale
  );

  $check = $this->getAttributeValueLocale($id, $locale);
  if(@$check['pk_i_id'] > 0) {
    $this->dao->update($this->getTable_attribute_value_locale(), $values, $where);
  } else {
    $this->dao->insert($this->getTable_attribute_value_locale(), $values);
  }
}



// GET ATTRIBUTE LOCALE
public function getAttributeLocale($attribute_id, $locale = '') {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAttributeLocale::' . (string)$attribute_id . (string)$locale);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('*');
    $this->dao->from($this->getTable_attribute_locale());
    $this->dao->where('fk_i_attribute_id', $attribute_id);

    if($locale <> '') {
      $this->dao->where('fk_c_locale_code', $locale);
    }

    $this->dao->limit(1);

    $result = $this->dao->get();
    
    if($result) {
      $data = $result->row();

      osc_cache_set($cache_key, $data, atr_cache_ttl());
      return $data;
    }
    
    osc_cache_set($cache_key, false, atr_cache_ttl());
    return false;
  } 
}


// GET ATTRIBUTE LOCALES
public function getAttributeLocales($attribute_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAttributeLocales::' . (string)$attribute_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('*');
    $this->dao->from($this->getTable_attribute_locale());
    $this->dao->where('fk_i_attribute_id', $attribute_id);

    $result = $this->dao->get();
    $array = array();

    if($result) {
      $locales = $result->result();

      if(count($locales) > 0) {
        foreach($locales as $l) {
          $array[$l['fk_c_locale_code']] = $l['s_name'];
        }
      }
    }
    
    osc_cache_set($cache_key, $array, atr_cache_ttl());
    return $array;
  } 
}


// GET ATTRIBUTE LOCALES ALL
public function getAllAttributeLocales() {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAllAttributeLocales');
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('*');
    $this->dao->from($this->getTable_attribute_locale());

    $result = $this->dao->get();
    $array = array();

    if($result) {
      $locales = $result->result();

      if(count($locales) > 0) {
        foreach($locales as $l) {
          $array[$l['fk_i_attribute_id']][$l['fk_c_locale_code']] = $l['s_name'];
        }
      }
    }

    osc_cache_set($cache_key, $array, atr_cache_ttl());
    return $array;
  } 
}


// GET ATTRIBUTE LOCALE
public function getAttributeValueLocales($attribute_value_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAttributeValueLocales::' . (string)$attribute_value_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('*');
    $this->dao->from($this->getTable_attribute_value_locale());
    $this->dao->where('fk_i_attribute_value_id', $attribute_value_id);

    $result = $this->dao->get();
    $array = array();

    if($result) {
      $locales = $result->result();

      if(count($locales) > 0) {
        foreach($locales as $l) {
          $array[$l['fk_c_locale_code']] = $l['s_name'];
        }
      }
    }

    osc_cache_set($cache_key, $array, atr_cache_ttl());
    return $array;
  } 
}


// GET ALL ATTRIBUTE VALUE LOCALE v2
public function getAllAttributeValueLocales($attribute_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAllAttributeValueLocales::' . (string)$attribute_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('l.*');
    $this->dao->from($this->getTable_attribute_value_locale() . ' as l,' . $this->getTable_attribute_value() . ' as v');
    $this->dao->where('l.fk_i_attribute_value_id = v.pk_i_id');
    $this->dao->where('v.fk_i_attribute_id', $attribute_id);

    $result = $this->dao->get();
    $array = array();

    if($result) {
      $locales = $result->result();

      if(count($locales) > 0) {
        foreach($locales as $l) {
          $array[$l['fk_i_attribute_value_id']][$l['fk_c_locale_code']] = $l['s_name'];
        }
      }
    }

    osc_cache_set($cache_key, $array, atr_cache_ttl());
    return $array;
  } 
}


// GET ATTRIBUTE
public function getAttribute($attribute_id) {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAttribute::' . (string)$attribute_id);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('*');
    $this->dao->from($this->getTable_attribute());
    $this->dao->where('pk_i_id', $attribute_id);

    $result = $this->dao->get();
    
    if($result) {
      $data = $result->row();
      
      osc_cache_set($cache_key, $data, atr_cache_ttl());
      return $data;
    }
    
    osc_cache_set($cache_key, false, atr_cache_ttl());
    return false;
  } 
}



// GET ATTRIBUTE VALUE LOCALE
public function getAttributeValueLocale($attribute_value_id, $locale = '') {
  $cache_key = md5(osc_base_url() . 'Plugin::Attributes::getAttributeValueLocale::' . (string)$attribute_value_id . (string)$locale);
  $found = null;
  $cache = osc_cache_get($cache_key, $found);

  if(atr_param('enable_cache') == 1 && $cache !== false && OC_ADMIN === false) {
    return $cache;
  } else {
    $this->dao->select('*');
    $this->dao->from($this->getTable_attribute_value_locale());
    $this->dao->where('fk_i_attribute_value_id', $attribute_value_id);

    if($locale <> '') {
      $this->dao->where('fk_c_locale_code', $locale);
    }

    $this->dao->limit(1);

    $result = $this->dao->get();
    
    if($result) {
      $data = $result->row();
      
      osc_cache_set($cache_key, $data, atr_cache_ttl());
      return $data;
    }
    
    osc_cache_set($cache_key, false, atr_cache_ttl());
    return false;
  } 
}


}
?>