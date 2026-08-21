<?php
class ModelWAC extends DAO {
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


public function getTable_item() {
  return DB_TABLE_PREFIX.'t_wac_item';
}


public function import($file) {
  $path = osc_plugin_resource($file);
  $sql = file_get_contents($path);

  if(!$this->dao->importSQL($sql) ){
    throw new Exception("Error importSQL::ModelWAC<br>" . $file . "<br>" . $this->dao->getErrorLevel() . " - " . $this->dao->getErrorDesc() );
  }
}


public function install($version = '') {
  if($version == '') {
    $this->import('wa_chat/model/struct.sql');
    osc_set_preference('version', 100, 'plugin-wa_chat', 'INTEGER');
  }

}


public function uninstall() {
  // DELETE ALL TABLES
  $this->dao->query(sprintf('DROP TABLE %s', $this->getTable_item()));


  // DELETE ALL PREFERENCES
  $db_prefix = DB_TABLE_PREFIX;
  $query = "DELETE FROM {$db_prefix}t_preference WHERE s_section = 'plugin-wa_chat'";
  $this->dao->query($query);
}



// GET ITEM PASSWORD
public function getData($item_id) {
  if($item_id <= 0) {
    return false;
  }
  
  $this->dao->select();
  $this->dao->from($this->getTable_item());
  $this->dao->where('fk_i_item_id', $item_id);
  $result = $this->dao->get();
  
  if($result) {
    $data = $result->row();
    
    if(isset($data['fk_i_item_id'])) {
      return $data;
    }
  }
  
  return false;
}


// INSERT ITEM DATA
public function insertData($data) {
  $this->dao->insert($this->getTable_item(), $data);
}


// UPDATE ITEM DATA
public function updateData($id, $data) {
  $this->dao->update($this->getTable_item(), $data, array('fk_i_item_id' => $id));
}


// REPLACE ITEM DATA
public function replaceData($data) {
  $this->dao->replace($this->getTable_item(), $data);
}


// UPDATE CLICKS
public function updateClicks($id) {
  if($id > 0) {
    return $this->dao->query('UPDATE '.$this->getTable_item() . ' SET i_clicks=coalesce(i_clicks, 0)+1 WHERE fk_i_item_id='.$id);
  }
}

// UPDATE VIEWS
public function updateViews($id) {
  if($id > 0) {
    return $this->dao->query('UPDATE '.$this->getTable_item() . ' SET i_views=coalesce(i_views, 0)+1 WHERE fk_i_item_id='.$id);
  }
}


// CHECK IF THEME TABLE EXISTS
public function checkTable($theme) {
  $this->dao->select();
  $this->dao->from('information_schema.tables');
  $this->dao->where('table_name', DB_TABLE_PREFIX . 't_item_' . $theme);
  $this->dao->limit(1);

  $result = $this->dao->get();

  if($result) { 
    $data = $result->row();

    if(@$data['TABLE_NAME'] == DB_TABLE_PREFIX . 't_item_' . $theme) {
      return true;
    }
  }

  return false;
}


// GET THEME NUMBER
public function getItemThemeNumber($item_id, $theme) {
  if($this->checkTable($theme) && $item_id > 0) {
    $this->dao->select();
    $this->dao->from(DB_TABLE_PREFIX . 't_item_' . $theme);
    $this->dao->where('fk_i_item_id', $item_id);
    
    $result = $this->dao->get();

    if($result) { 
      $data = $result->row();
      
      if(trim(@$data['s_phone']) <> '') {
        return trim($data['s_phone']);
      }
    }
  }

  return false;
}


}
?>