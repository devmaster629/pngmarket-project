<?php

// CACHE TTL (expiration in seconds)
function atr_cache_ttl() {
  return OSC_CACHE_TTL;
}


// ATTRIBUTES TO SEARCH FORM
function atr_search_form($catId = '') {
  $html = '';
  $cat_id = isset($catId[0]) ? $catId[0] : '';

  if($catId <= 0) {
    $cat_id = -1;     // no category selected
  }

  $attributes = ModelATR::newInstance()->getSearchAttributes2($cat_id);


  //$attributes = ModelATR::newInstance()->getSearchAttributes($cat_id);    // old slow version
  //$attributes = ModelATR::newInstance()->getAttributes(1, $cat_id);

  if(is_array($attributes) && count($attributes) > 0) {
    $html = '<div class="atr-search atr-theme-' . osc_current_web_theme() . ' ' . (atr_param('styled') == 1 ? 'atr-styled' : '') . '" id="atr-search">';

    foreach($attributes as $a) {
      $html .= atr_generate_search_elem($a);
    }

    $html .= '</div>';
  }

  echo $html;
}

osc_add_hook('search_form', 'atr_search_form');


function atr_generate_search_elem($a, $linked = false) {
  $html = '';
  
  if($a === false || !isset($a['pk_i_id'])) {
    return false;
  }
  
  $name = atr_name($a['locales']);
  $type = $a['s_type'];

  // Divider - separator - grouping element
  if($type == 'DIVIDER') {
    $html .= '<div class="control-group atr-type-' . strtolower($type) . '" id="atr-' . ($a['s_identifier'] <> '' ? $a['s_identifier'] : 'id' . $a['pk_i_id']) . '">';
    $html .= '<strong class="atr-divider-label">' . $name . '</strong>';
    $html .= '</div>';
    return $html;
  }


  $has_values = ModelATR::newInstance()->getItemValuesList($a['pk_i_id']);
  $values_list = ModelATR::newInstance()->getAttributeValuesByParent($a['pk_i_id']);
  $linked_attr = ModelATR::newInstance()->getLinkedAttribute($a['pk_i_id']);
  
  $has_linked = false;
  if($linked_attr !== false && isset($linked_attr['pk_i_id']) && $linked_attr['b_enabled'] == 1 && $linked_attr['b_search'] == 1 && $a['fk_i_linked_to_attr_id'] <= 0) {
    if(in_array($a['s_type'], array('SELECT','TEXT','NUMBER','DATE','URL','PHONE','EMAIL'))) {
      if(in_array($linked_attr['s_type'], array('SELECT','TEXT','NUMBER','DATE','URL','PHONE','EMAIL'))) {
        $has_linked = true;
      }
    }
  }
  
  if($a['s_search_values_all'] == 1) {
    if(empty($has_values)) {
      return '';
    }
  } 

  $subtype = '';

  if($a['s_search_type'] == 'BOXED' && ($a['s_type'] == 'RADIO' || $a['s_type'] == 'CHECKBOX')) {
    $type = $a['s_type'];
    $subtype = 'BOXED';
  } else {
    $type = ($a['s_search_type'] == '' ? $a['s_type'] : $a['s_search_type']);
    $type = ($a['b_search_range'] == 1 ? 'RANGE' : $type);
  }

  $label_for = 'atr_' . $a['pk_i_id'];
  if($type == 'RANGE') {
    $label_for .= '_from';
  }

  if($linked === false) {
    $html .= '<div class="control-group atr-type-' . strtolower($type) . ' atr-subtype-' . strtolower($subtype) . ' ' . ($has_linked ? 'atr-has-linked' : 'atr-no-linked') . '" id="atr-' . ($a['s_identifier'] <> '' ? $a['s_identifier'] : 'id' . $a['pk_i_id']) . '">';
    $html .= '<label class="control-label" for="' . $label_for . '">' . ($name <> '' ? $name : __('New attribute', 'attributes')) . '</label>';
    $html .= '<div class="controls">';
  } else {
    $html .= '<div class="atr-linked">';
  }
  
  $item_atr = ModelATR::newInstance()->getItemAttributeRaw(-1, $a['pk_i_id']);
  $item_atr['fk_i_attribute_value_id'] = Params::getParam('atr_' . $a['pk_i_id']);
  $item_atr['s_value'] = (isset($item_atr['s_value']) ? $item_atr['s_value'] : '');



  if($type == 'RANGE') {
    if(($a['s_type'] == 'SELECT' || $a['s_type'] == 'CHECKBOX' || $a['s_type'] == 'RADIO') && count($values_list) > 0) {
      $html .= '<span class="atr-range-search-select">';
      $html .= '<select id="atr_' . $a['pk_i_id'] . '_from" name="atr_' . $a['pk_i_id'] . '_from" class="no-refresh">';
      $html .= '<option value=""' . (Params::getParam('atr_' . $a['pk_i_id'] . '_from') == '' ? ' selected="selected"' : '') . '>' . __('From', 'attributes') . '</option>';

      foreach($values_list as $v) {
        $html .= '<option value="' . $v['pk_i_id'] . '"' . ($v['pk_i_id'] == Params::getParam('atr_' . $a['pk_i_id'] . '_from') ? ' selected="selected"' : '') . '>' . atr_name($v['locales']) . '</option>';
      }
      
      $html .= '</select>';
      $html .= '</span>';

      $html .= '<span class="atr-date-del">-</span>';

      $html .= '<span class="atr-range-search-select">';
      $html .= '<select id="atr_' . $a['pk_i_id'] . '_to" name="atr_' . $a['pk_i_id'] . '_to" class="no-refresh">';
      $html .= '<option value=""' . (Params::getParam('atr_' . $a['pk_i_id'] . '_to') == '' ? ' selected="selected"' : '') . '>' . __('To', 'attributes') . '</option>';

      foreach($values_list as $v) {
        $html .= '<option value="' . $v['pk_i_id'] . '"' . ($v['pk_i_id'] == Params::getParam('atr_' . $a['pk_i_id'] . '_to') ? ' selected="selected"' : '') . '>' . atr_name($v['locales']) . '</option>';
      }
      
      $html .= '</select>';
      $html .= '</span>';

    } else {
      $html .= '<input type="number" step="any" id="atr_' . $a['pk_i_id'] . '_from" name="atr_' . $a['pk_i_id'] . '_from" value="' . Params::getParam('atr_' . $a['pk_i_id'] . '_from') . '" placeholder="' . osc_esc_html(__('Min.', 'attributes')) . '"/>';
      $html .= '<span class="atr-date-del">-</span>';
      $html .= '<input type="number" step="any" id="atr_' . $a['pk_i_id'] . '_to" name="atr_' . $a['pk_i_id'] . '_to" value="' . Params::getParam('atr_' . $a['pk_i_id'] . '_to') . '" placeholder="' . osc_esc_html(__('Max.', 'attributes')) . '"/>';
    }

  } else if ($type == 'SELECT') {
    $html .= '<input type="hidden" id="atr_' . $a['pk_i_id'] . '" name="atr_' . $a['pk_i_id'] . '" value="' . $item_atr['fk_i_attribute_value_id'] . '">';

    if($item_atr['fk_i_attribute_value_id'] > 0) {
      // We have selected ID already
      $hierarchy = atr_attribute_value_hierarchy($item_atr['fk_i_attribute_value_id']);
      $data = atr_attribute_value_siblings($item_atr['fk_i_attribute_id'], $item_atr['fk_i_attribute_value_id']);

      if(is_array($data) && count($data) > 0) {
        $j = 0;

        foreach($data as $d) {
          $selected_val = $d['selected_id'];
          $siblings = $d['siblings'];

          if(is_array($siblings) && count($siblings) > 0) {
            $html .= '<select data-level="' . ($j + 1) . '" data-atr-id="' . $a['pk_i_id'] . '" data-val-id="' . @$hierarchy[$j-1] . '" ' . ($j == 0 ? 'id="atr_select_' . $a['pk_i_id'] . '"' : '') . '>';
            //$html .= '<option value="">' . __('Select value ...', 'attributes') . '</option>';

            if($linked === false) {
              $html .= '<option value="">' . ($name <> '' ? sprintf(__('Select %s ...', 'attributes'), strtolower($name)) : __('Select value ...', 'attributes')) . '</option>';
            }
            
            foreach($siblings as $s) {
              if($a['s_search_values_all'] == 1 && !in_array($s['pk_i_id'], $has_values)) {
                continue;
              }

              $html .= '<option value="' . $s['pk_i_id'] . '" ' . ($s['pk_i_id'] == $selected_val ? 'selected="selected"' : '') . '>' . atr_name($s['locales']) . '</option>';
            }

            $html .= '</select>';
          }

          $j++;
        }

        // we have parents, build now children
        $children = ModelATR::newInstance()->getAttributeValuesByParent($item_atr['fk_i_attribute_id'], $item_atr['fk_i_attribute_value_id']);
       
        if(is_array($children) && count($children) > 0) {
          $html .= '<select data-level="' . ($j + 1) . '" data-atr-id="' . $a['pk_i_id'] . '" data-val-id="' . $item_atr['fk_i_attribute_value_id'] . '">';
          $html .= '<option value="">' . __('Select value ...', 'attributes') . '</option>';

          foreach($children as $c) {
            if($a['s_search_values_all'] == 1 && !in_array($c['pk_i_id'], $has_values)) {
              continue;
            }

            $html .= '<option value="' . $c['pk_i_id'] . '">' . atr_name($c['locales']) . '</option>';
          }

          $html .= '</select>';
        }
      }

    } else {
      // Generate first select only
      $html .= '<select data-level="1" data-atr-id="' . $a['pk_i_id'] . '" data-val-id="" id="atr_select_' . $a['pk_i_id'] . '">';
      //$html .= '<option value="">' . __('Select value ...', 'attributes') . '</option>';
      
      if($linked === false) {
        $html .= '<option value="">' . ($name <> '' ? sprintf(__('Select %s ...', 'attributes'), strtolower($name)) : __('Select value ...', 'attributes')) . '</option>';
      }
      
      if(is_array($a['values']) && count($a['values']) > 0) {
        foreach($a['values'] as $v) {
          if($a['s_search_values_all'] == 1 && !in_array($v['pk_i_id'], $has_values)) {
            continue;
          }

          $html .= '<option value="' . $v['pk_i_id'] . '">' . atr_name($v['locales']) . '</option>';
        }
      } 

      $html .= '</select>';
    }

  } else if($type == 'CHECKBOX' || $type == 'RADIO') {
    if(is_array($a['values']) && count($a['values']) > 0) {

      if($subtype == 'BOXED') {
        $html .= '<div class="atr-box" data-empty="' . osc_esc_html($name <> '' ? sprintf(__('Select %s ...', 'attributes'), strtolower($name)) : __('Select value ...', 'attributes')) . '"><div class="atr-holder">';

        $selected_vals = array();
        //$vals = ModelATR::newInstance()->getAttributeValues($type, $a['pk_i_id'], NULL, true);
        $vals = $a['values'];
        $params = Params::getParamsAsArray();

        foreach($params as $n => $v) {
          $param = explode('_', $n);

          if(@$param[0] == 'atr' && @$param[1] == $a['pk_i_id'] && ($v == 'on' || $v == 1)) {
            $selected_vals[] = @$param[2];
          }
        }

        $val_names = array();
        $selected_vals = array_filter($selected_vals);

        $found_val = false;
        if(is_array($selected_vals) && count($selected_vals) > 0) {
          foreach($selected_vals as $sv) {
            if(@$vals[$sv]['s_name'] <> '') {
              $html .= '<span data-id="' . $sv . '">' . $vals[$sv]['s_name'] . '</span>';
              $found_val = true;
            }
          }
        }

        $val_names = array_filter($val_names);
        $val_string = implode(', ', $val_names);


        if(!$found_val) {
          $html .= ($name <> '' ? sprintf(__('Select %s ...', 'attributes'), strtolower($name)) : __('Select value ...', 'attributes'));
        }

        $html .= '</div></div>';
      }

      $html .= '<ul class="atr-ul atr-ul-' . strtolower($type) . '">';

      if(($type == 'CHECKBOX' && ($a['s_search_type'] == '' || $a['s_search_type'] == 'BOXED')) || ($a['s_search_type'] == 'CHECKBOX')) {
        $html .= '<a href="' . atr_select_all_url($a) . '" class="atr-select-deselect atr-select-all mb-ajax-search-link" id="atr-select-all-' . $a['pk_i_id'] . '" ' . (atr_select_or_deselect($a) ? 'style="display:none;"' : '') . '>' . __('Select all', 'attributes') . '</a>';
        $html .= '<a href="' . atr_deselect_all_url($a) . '" class="atr-select-deselect atr-deselect-all mb-ajax-search-link" id="atr-deselect-all-' . $a['pk_i_id'] . '" ' . (!atr_select_or_deselect($a) ? 'style="display:none;"' : '') . '>' . __('Deselect all', 'attributes') . '</a>';
      }

      foreach($a['values'] as $v) {
        if($a['s_search_values_all'] == 1 && !in_array($v['pk_i_id'], $has_values)) {
          continue;
        }

        $idc = 'atr_' . $a['pk_i_id'] . '_' . $v['pk_i_id'];
        $checked = (Params::getParam($idc) == 'on' ? 'checked="checked"' : '');
        $checked = (Params::getParam($idc) == 1 ? 'checked="checked"' : $checked);

        $html .= '<li data-level="0"><div class="atr-input-box atr-' . strtolower($type) . '"><input type="' . strtolower($type) . '" id="' . $idc . '" name="' . $idc . '" ' . $checked . '/> <label for="' . $idc . '">' . atr_name($v['locales']) . '</div></label></li>';

        $html .= atr_loop_value_option($v, strtolower($type), 1, $a, $has_values);
      }

      $html .= '</ul>';
    }

  } else if ($type == 'TEXT' || $type == 'PHONE' || $type == 'EMAIL' || $type == 'URL' || $type == 'NUMBER') {
    $html .= '<input type="' . strtolower($type) . '" id="atr_' . $a['pk_i_id'] . '" name="atr_' . $a['pk_i_id'] . '" placeholder="' . atr_input_placeholder($type) . '" value="' . $item_atr['s_value'] . '"/>';

  } else if ($type == 'TEXTAREA') {
    $html .= '<textarea id="atr_' . $a['pk_i_id'] . '" name="atr_' . $a['pk_i_id'] . '" placeholder="' . atr_input_placeholder($type) . '">' . $item_atr['s_value'] . '</textarea>';

  } else if ($type == 'DATE') {
    $html .= '<input type="date" id="atr_' . $a['pk_i_id'] . '" name="atr_' . $a['pk_i_id'] . '" value="' . $item_atr['s_value'] . '"/>';

  } else if ($type == 'DATERANGE') {
    $range = explode('|', $item_atr['s_value']);

    $html .= '<input type="date" id="atr_' . $a['pk_i_id'] . '_start" name="atr_' . $a['pk_i_id'] . '_start" value="' . @$range[0] . '"/>';
    $html .= '<span class="atr-date-del">-</span>';
    $html .= '<input type="date" id="atr_' . $a['pk_i_id'] . '_end" name="atr_' . $a['pk_i_id'] . '_end" value="' . @$range[1] . '"/>';

  }
  
  
  if($linked === false) {
    if($has_linked) {
      $html .= atr_generate_search_elem($linked_attr, true);
    }

    $html .= '</div>';
  }

  $html .= '</div>';

  return $html;
}


// ATTRIBUTES TO SEARCH ENGINE
function atr_search_extend() {
  $used_ids = array();

  $params = Params::getParamsAsArray();
  $processed = array();

  Search::newInstance()->addGroupBy(DB_TABLE_PREFIX.'t_item.pk_i_id');
  
  
  // Find search categories
  $category = array();
  $p_sCategory = Params::getParam('sCategory');
  if(!is_array($p_sCategory)) {
    if($p_sCategory == '') {
      $p_sCategory = array();
    } else {
      $p_sCategory = explode(',' , $p_sCategory);
    }
  }

  if(is_array($p_sCategory) && count($p_sCategory) > 0) {
    foreach($p_sCategory as $cat) {
      if(is_numeric($cat)) {
        $tmp = Category::newInstance()->findByPrimaryKey($cat);
        if(isset($tmp['pk_i_id'])) { 
          $category[] = $tmp['pk_i_id']; 
        }
      } else {
        $slug_cat = explode( "/", trim($cat, "/") );
        $tmp = Category::newInstance()->findBySlug($slug_cat[count($slug_cat)-1]);
        if(isset($tmp['pk_i_id'])) { 
          $category[] = $tmp['pk_i_id']; 
        }
      }
    }
  }
  

  foreach($params as $p => $d) {
    $atr = explode('_', $p);
    $attribute_value_id = '';

    if(@$atr[0] == 'atr' && @$atr[1] > 0) {
      $attribute_id = (isset($atr[1]) ? $atr[1] : 0);
      $attribute_value_id = (isset($atr[2]) ? $atr[2] : null);
      $value = (strtolower($d) == 'on' ? 1 : $d);

      $attribute = ModelATR::newInstance()->getAttribute($attribute_id);
      //$type = ($attribute['s_search_type'] == '' ? $attribute['s_type'] : $attribute['s_search_type']);

      if(isset($attribute['pk_i_id']) && $attribute['b_search'] == 1 && $attribute['b_enabled'] == 1) {
        $atr_cats = array_filter(explode(',', $attribute['s_category_id']));
        $cat_ok = false;
        
        if(empty($atr_cats) || count($atr_cats) <= 0) {
          $cat_ok = true;
        } else if(count($category) > 0) {
          foreach($category as $c_id) {
            if(in_array($c_id, $atr_cats)) {
              $cat_ok = true;
              break;
            }
          }
        } else if(in_array('-1', $atr_cats)) {
          $cat_ok = true;
        }

        // Attribute is enabled in category
        if($cat_ok === true) {
          $type = $attribute['s_type'];
          $type = ($attribute['b_search_range'] == 1 ? 'RANGE' : $type);
          $search_type = $attribute['s_search_type'];


          // MANAGE ATTRIBUTES WHERE "OR" CONDITION SHOULD BE APPLIED
          if($attribute['s_search_engine'] == 'OR' && ($type == 'SELECT' || $type == 'RADIO' || $type == 'CHECKBOX')) {
            if(!in_array($attribute_id, $processed)) {
              $val_array = array();

              foreach($params as $p1 => $d1) {
                $process_atr = explode('_', $p1);
                $process_atr_id = (isset($process_atr[1]) ? $process_atr[1] : 0);
                $process_atr_value_id = (isset($process_atr[2]) ? $process_atr[2] : null);
                $process_value = (strtolower($d1) == 'on' ? 1 : $d1);

                if($process_atr_id == $attribute_id) {
                  $process_value = ($process_value > 0 ? $process_value : null);
                  $val_array[] = $process_atr_value_id;
                }
              }


              $conds_array = array();

              if(!empty($val_array)) {
                $tid1 = $attribute_id . '_xx';
                $val_i1 = '(' . implode(',', $val_array) . ')';

                Search::newInstance()->addJoinTable( 'tbl_at_' . $tid1. '.fk_i_item_id', DB_TABLE_PREFIX.'t_item_attribute as tbl_at_' . $tid1, DB_TABLE_PREFIX.'t_item.pk_i_id = tbl_at_' . $tid1 . '.fk_i_item_id', 'INNER' ) ; 
                Search::newInstance()->addConditions(sprintf("(tbl_at_%s.fk_i_attribute_id = %d AND tbl_at_%s.fk_i_attribute_value_id in %s)", $tid1, $tid1, $tid1, $val_i1));
              }
         

              $processed[] = $attribute_id;

            } else {
              // do nothing, attribute was processed to search already
            }

          } else if($type == 'SELECT' || $type == 'RADIO' || $type == 'CHECKBOX') {
            $value = ($value > 0 ? $value : null);
          
            if($type == 'SELECT' && ($search_type <> 'RADIO' && $search_type <> 'CHECKBOX')) {
              $attribute_value_id = $d;
            }

          } else if($type == 'TEXT' || $type == 'TEXTAREA' || $type == 'URL' || $type == 'EMAIL' || $type == 'PHONE' || $type == 'DATE') {

          } else if($type == 'DATERANGE' && !in_array($attribute_id, $used_ids)) {
            $start = $params['atr_' . $attribute_id . '_start'];
            $end = $params['atr_' . $attribute_id . '_end'];
            $value = $start . '|' . $end;

            //ModelATR::newInstance()->updateItemAttributeValue($item_id, $attribute_id, null, $range);

            $used_ids[] = $attribute_id;
          } else if($type == 'RANGE') {
            $from = @$params['atr_' . $attribute_id . '_from'];
            $to = @$params['atr_' . $attribute_id . '_to'];

          }

          
          // to engine
          if(($value <> '' || $attribute_value_id <> '' || @$from <> '' || @$to <> '') && !in_array($attribute_id, $processed)) {
            $val_s = strval($value);

            if($type == 'SELECT' ) { 
              $val_ids = atr_attribute_value_children($type, $attribute_id, $attribute_value_id);
              $val_ids[] = $attribute_value_id;

              $val_i = '(' . implode(',', $val_ids) . ')';
            } else if ($search_type == 'SELECT') {
              $val_i = '(' . $val_s . ')';

            } else {
              $val_i = '(' . $attribute_value_id . ')';
            }

            $tid = $attribute_id . '_' . ($attribute_value_id <> '' ? $attribute_value_id : 0);

            Search::newInstance()->addJoinTable( 'tbl_at_' . $tid. '.fk_i_item_id', DB_TABLE_PREFIX.'t_item_attribute as tbl_at_' . $tid, DB_TABLE_PREFIX.'t_item.pk_i_id = tbl_at_' . $tid . '.fk_i_item_id', 'INNER' ) ; 
            //Search::newInstance()->addConditions(sprintf("(tbl_at_%s.fk_i_attribute_id = %d AND (tbl_at_%s.fk_i_attribute_value_id in %s OR tbl_at_%s.s_value like \"%%%s%%\"))", $attribute_id, $attribute_id, $attribute_id, $val_i, $attribute_id, $val_s));

            if($type == 'SELECT' || $type == 'RADIO' || $type == 'CHECKBOX') {
              Search::newInstance()->addConditions(sprintf("(tbl_at_%s.fk_i_attribute_id = %d AND tbl_at_%s.fk_i_attribute_value_id in %s)", $tid, $tid, $tid, $val_i));
            } else if($type == 'RANGE') {
              if($from <> '') {
                Search::newInstance()->addConditions(sprintf("(tbl_at_%s.fk_i_attribute_id = %d AND (tbl_at_%s.fk_i_attribute_value_id >= %d OR cast(tbl_at_%s.s_value as UNSIGNED) >= %d))", $tid, $tid, $tid, intval($from), $tid, intval($from)));
              }

              if($to <> '') {
                Search::newInstance()->addConditions(sprintf("(tbl_at_%s.fk_i_attribute_id = %d AND (tbl_at_%s.fk_i_attribute_value_id <= %d OR cast(tbl_at_%s.s_value as UNSIGNED) <= %d))", $tid, $tid, $tid, intval($to), $tid, intval($to)));
              }


            } else {
              Search::newInstance()->addConditions(sprintf("(tbl_at_%s.fk_i_attribute_id = %d AND tbl_at_%s.s_value like \"%%%s%%\")", $tid, $tid, $tid, $val_s));
            }
          }
        }
      }
    }
  }
}

osc_add_hook('search_conditions', 'atr_search_extend');




// SHOW ON ITEM PAGE
function atr_show_item($item) {
  $html = '';
  $attributes = ModelATR::newInstance()->getItemAttributes($item['pk_i_id'], $item['fk_i_category_id']);

  if(is_array($attributes) && count($attributes) > 0) {
    $html .= '<ul id="atr-item" class="atr-theme-' . osc_current_web_theme() . ' ' . (atr_param('styled') == 1 ? 'atr-styled' : '') . '">';
    $html .= '<h3 id="atr-title">' . __('Attributes', 'attributes') . '</h3>';

    foreach($attributes as $a) {
      if($a['b_hook'] == 1 && $a['fk_i_linked_to_attr_id'] <= 0) {
        $html .= atr_single_attribute($a, $item['pk_i_id']);
      }
    }

    $html .= '</ul>';

  }

  echo $html;
} 

osc_add_hook('item_detail', 'atr_show_item');


// GET CORRECT SELECT/DESELECT URL
function atr_select_deselect_url($attribute) {
  if(!atr_select_or_deselect($attribute)) {
    return atr_select_all_url($attribute);
  } else {
    return atr_deselect_all_url($attribute);
  }
}


// CHECK IF SELECT OR DESELECT OPTION SHOULD BE USED
function atr_select_or_deselect($attribute) {
  $params = Params::getParamsAsArray();
  $atr_id = $attribute['pk_i_id'];
  $values = ModelATR::newInstance()->getAllAttributeValuesFlat($atr_id);

  if(is_array($values) && count($values) > 0) {
    foreach($values as $v) {
      if(!isset($params['atr_' . $atr_id . '_' . $v['pk_i_id']])) {
        return false;  // use select
      }
    }
  }

  return true; // use deselect
}


// PREPARE URL FOR "SELECT ALL" OPTION
function atr_select_all_url($attribute) {
  $params = Params::getParamsAsArray();
  $atr_id = $attribute['pk_i_id'];
  $values = ModelATR::newInstance()->getAllAttributeValuesFlat($atr_id);

  if(is_array($values) && count($values) > 0) {
    foreach($values as $v) {
      $params['atr_' . $atr_id . '_' . $v['pk_i_id']] = 'on';
    }
  }

  return osc_search_url($params);
}


// PREPARE URL FOR "DELESECT ALL" OPTION
function atr_deselect_all_url($attribute) {
  $params = Params::getParamsAsArray();
  $atr_id = $attribute['pk_i_id'];

  if(is_array($params) && count($params) > 0) {
    foreach($params as $n => $v) {
      $name = explode('_', $n);

      if(@$name[0] == 'atr' && @$name[1] == $atr_id) {
        unset($params[$n]);
      }
    }
  }

  return osc_search_url($params);
}



// SHOW ATTRIBUTE BASED ON ID
function atr_show_attribute($attribute_id, $label = true) {
  $attribute = ModelATR::newInstance()->getAttributeDetail($attribute_id);

  $item = osc_item();
  return atr_single_attribute($attribute, $item['pk_i_id'], $label);
}

// SHOW ATTRIBUTE BASED ON ID v2
function atr_show_attribute2($attribute_id, $label = false) {
  $attribute = ModelATR::newInstance()->getAttributeDetail($attribute_id);

  $cats = array_filter(explode(',', $attribute['s_category_id']));

  $item = osc_item();

  if(empty($cats) || in_array($item['fk_i_category_id'], $cats)) {
    return atr_single_attribute($attribute, $item['pk_i_id'], $label);
  }
}



// SHOW ONE ATTRIBUTE
function atr_single_attribute($a, $item_id = '', $label = true, $linked = false) {
  $html = '';
  $show_icon = (atr_param('attribute_icon') == 1 ? true : false);

  if($item_id == '') {
    $item_id = osc_item_id();
  }

  if($item_id <= 0) {
    return false;
  }

  if($a === false || !isset($a['pk_i_id'])) {
    return false;
  }
  
  $type = $a['s_type'];
  $name = atr_name($a['locales']);
  
  // Divider - separator - grouping element
  if($type == 'DIVIDER') {
    $html .= '<div class="atr-type-' . strtolower($type) . '" id="atr-' . ($a['s_identifier'] <> '' ? $a['s_identifier'] : 'id' . $a['pk_i_id']) . '">';
    $html .= '<strong class="atr-divider-label">' . $name . '</strong>';
    $html .= '</div>';
    return $html;
  }

  $value_row_all = ModelATR::newInstance()->getAllItemAttributeValues($item_id);
  $linked_attr = ModelATR::newInstance()->getLinkedAttribute($a['pk_i_id']);

  $has_linked = false;
  if($linked_attr !== false && isset($linked_attr['pk_i_id']) && $linked_attr['b_enabled'] == 1 && $linked_attr['b_hook'] == 1) {
    if(in_array($a['s_type'], array('SELECT','TEXT','NUMBER','DATE','URL','PHONE','EMAIL'))) {
      if(in_array($linked_attr['s_type'], array('SELECT','TEXT','NUMBER','DATE','URL','PHONE','EMAIL'))) {
        $has_linked = true;
      }
    }
  }
  
  if($linked === false) {
    $html .= '<li class="atr-line atr-type-' . strtolower($a['s_type']) . ' ' . ($show_icon ? 'atr-has-icon' : 'atr-no-icon') . ' ' . ($has_linked ? 'atr-has-linked' : 'atr-no-linked') . '" id="atr-' . ($a['s_identifier'] <> '' ? $a['s_identifier'] : 'id' . $a['pk_i_id']) . '" data-id="' . $a['pk_i_id'] . '" data-count="' . (is_array(@$a['values']) ? count(@$a['values']) : 0) . '">';
  }
  
  if($label && $linked === false) {
    $html .= '<div class="atr-name">';
    
    if($show_icon && @$a['s_icon'] != '') { 
      if (filter_var($a['s_icon'], FILTER_VALIDATE_URL)) { 
        $html .= '<img src="' . $a['s_icon'] . '" class="atr-icon"/>';
      } else if(strpos($a['s_icon'], "default/") === 0) {
        $html .= '<img src="' . osc_base_url() . 'oc-content/plugins/attributes/img/' . $a['s_icon'] . '" class="atr-icon" />';
      }
    }
    
    $html .= '<span>' . ($name <> '' ? $name : __('New attribute', 'attributes')) . '</span>';
    $html .= '</div>';
  }

  if($linked === false) {
    $html .= '<div class="atr-value">';
  } else {
    $html .= '<div class="atr-linked-value">';
  }
  

  if($type == 'TEXT' || $type == 'TEXTAREA' || $type == 'PHONE' || $type == 'EMAIL' || $type == 'URL' || $type == 'DATE' || $type == 'DATERANGE' || $type == 'NUMBER') {
    //$value_row = ModelATR::newInstance()->getItemAttributeValues($item_id, $a['pk_i_id']);
    $value_row = @$value_row_all[$a['pk_i_id']];
    
    // do not show empty attribute
    if($value_row === false || !isset($value_row['s_value']) || trim((string)$value_row['s_value']) == '') {
      return false;
    } else {
      $value_row['s_value'] = osc_apply_filter('atr_item_value_pre_filter', $value_row['s_value'], $value_row, $a); 
    }

    if($type == 'DATERANGE') {
      $value = implode(' ' . __('to', 'attributes') . ' ', explode('|', $value_row['s_value']));

    } else if($type == 'URL') {
      if (filter_var($value_row['s_value'], FILTER_VALIDATE_URL)) { 
        $value = '<a href="' . $value_row['s_value'] . '" rel="noreferrer noopener nofollow" target="_blank">' . osc_esc_html($value_row['s_value']) . '</a>';
      } else {
        $value = osc_esc_html($value_row['s_value']);
      }

    } else if($type == 'EMAIL') {
      if (filter_var($value_row['s_value'], FILTER_VALIDATE_EMAIL)) { 
        $value = '<a href="mailto:' . $value_row['s_value'] . '" rel="noreferrer noopener nofollow">' . osc_esc_html($value_row['s_value']) . '</a>';
      } else {
        $value = osc_esc_html($value_row['s_value']);
      }

    } else if($type == 'PHONE') {
      $value = '<a href="tel:' . osc_esc_html($value_row['s_value']) . '" rel="noreferrer noopener nofollow">' . osc_esc_html($value_row['s_value']) . '</a>';

    } else {
      $value = osc_esc_html($value_row['s_value']);
    }


    $html .= '<span class="atr-value-single">' . $value . '</span>';

  } else if($type == 'CHECKBOX' || $type == 'RADIO') {
    $values_selected = ModelATR::newInstance()->getItemAttributeValueRows($item_id, $a['pk_i_id']);   // selected values
    $values_all = ModelATR::newInstance()->getAttributeValues($a['s_type'], $a['pk_i_id']);
    //$values_all = $a['values'];
    $selected_ids = array_column($values_selected, 'pk_i_id');

    if($a['b_values_all'] == 1) {
      $values = $values_all;
    } else {
      $values = $values_selected;
    }


    if(is_array($values) && count($values) > 0) {
      $html .= '<span class="atr-value-check">';

      foreach($values as $v) {
        $html .= '<span class="atr-value-single ' . (!in_array($v['pk_i_id'], $selected_ids) ? 'atr-disabled' : '') . '" data-id="' . $v['pk_i_id'] . '">';

        if (isset($v['s_image']) && filter_var($v['s_image'], FILTER_VALIDATE_URL)) { 
          $html .= '<img src="' . $v['s_image'] . '" class="atr-img atr-img-ext"/>';
          
        } else if(isset($v['s_image']) && strpos($v['s_image'], "default/") === 0) {
          $html .= '<img src="' . osc_base_url() . 'oc-content/plugins/attributes/img/' . $v['s_image'] . '" class="atr-img atr-img-plug" />';
     
        } else {
          if(in_array($v['pk_i_id'], $selected_ids)) {
            $html .= '<img src="' . osc_base_url() . 'oc-content/plugins/attributes/img/check.png" class="atr-img atr-img-def atr-img-check" />';
          } else {
            $html .= '<img src="' . osc_base_url() . 'oc-content/plugins/attributes/img/cross.png" class="atr-img atr-img-def atr-img-cross" />';
          }
        }

        $html .= '<span>' . atr_name($v['locales']) . '</span>';
        $html .= '</span>';
      }

      $html .= '</span>';

    } else {
      if($a['b_values_all'] == 1) {
        $html .= '<span class="atr-value-single atr-empty">' . __('- no selection - ', 'attributes') . '</span>';
      } else {
        // do not show anything, when no value was selected for checkbox/radio 
        return '';
      }
    }

  } else if($type == 'SELECT') {
    //$value_row = ModelATR::newInstance()->getItemAttributeValues($item_id, $a['pk_i_id']);
    $value_row = @$value_row_all[$a['pk_i_id']];
    
    if($value_row === false || !isset($value_row['fk_i_attribute_value_id']) || $value_row['fk_i_attribute_value_id'] <= 0) {
      return '';
    }
    
    $selected_val = array($value_row['fk_i_attribute_value_id']);
    $hierarchy = atr_attribute_value_hierarchy($value_row['fk_i_attribute_value_id']);

    if($a['b_values_all'] == 1) {
      $value_ids = $hierarchy;
    } else {
      $value_ids = $selected_val;
    }

    if(is_array($value_ids) && count($value_ids)) {
      $html .= '<span class="atr-value-select">';

      foreach($value_ids as $id) {
        $v = ModelATR::newInstance()->getAttributeValue($id);

        if(isset($v['pk_i_id']) && $v['pk_i_id'] > 0) {
          $html .= '<span class="atr-value-single" data-id="' . $v['pk_i_id'] . '">';

          if (isset($v['s_image']) && filter_var($v['s_image'], FILTER_VALIDATE_URL)) { 
            $html .= '<img src="' . $v['s_image'] . '" class="atr-img atr-img-ext"/>';
          } else if(isset($v['s_image']) && strpos($v['s_image'], "default/") === 0) {
            $html .= '<img src="' . osc_base_url() . 'oc-content/plugins/attributes/img/' . $v['s_image'] . '" class="atr-img atr-img-plug" />';
          }

          $html .= '<span>' . atr_name($v['locales']) . '</span>';

          $html .= '</span>';
        }
      }

      $html .= '</span>';
    }
  }
  

  
  if($has_linked) {
    $html .= atr_single_attribute($linked_attr, $item_id, false, true);
  }

  $html .= '</div>';
  
  if($linked === false) {
    $html .= '</li>';
  }
  
  return $html;
}


// GET SINGLE ATTRIBUTE VALUE ENTERED BY USER (NOT PREDEFINED)
function atr_item_attribute_value_by_user($attribute_id, $item_id = '') {
  if($item_id <= 0) {
    $item_id = osc_item_id();
  }

  $attribute = ModelATR::newInstance()->getAttributeDetail($attribute_id);
  $value = ModelATR::newInstance()->getItemAttributeValues($item_id, $attribute_id);

  if($value['fk_i_attribute_value_id'] > 0) {
    $val_row = ModelATR::newInstance()->getAttributeValue($value['fk_i_attribute_value_id']);
    $val = atr_name($val_row['locales']);

  } else {
    $val = $value['s_value'];
  }

  return array(
    'id' => $attribute['pk_i_id'],
    'name' => $attribute['s_name'],
    'value' => $val
  );
}

// LOOP VALUE
function atr_loop_value_option($a, $type, $level = 1, $attribute = array(), $has_values = array()) {
  $html = '';

  if(isset($a['values']) && is_array($a['values']) && count($a['values']) > 0) {
    foreach($a['values'] as $v) {
      if($attribute['s_search_values_all'] == 1 && !in_array($v['pk_i_id'], $has_values)) {
        continue;
      }

      $idc = 'atr_' . $v['fk_i_attribute_id'] . '_' . $v['pk_i_id'];
      $checked = (Params::getParam($idc) == 'on' ? 'checked="checked"' : '');
      $checked = (Params::getParam($idc) == 1 ? 'checked="checked"' : $checked);

      $html .= '<li data-level="' . $level . '"><div class="atr-input-box atr-lvl-' . $level . ' atr-' . strtolower($type) . '"><input type="' . strtolower($type) . '" id="' . $idc . '" name="' . $idc . '" ' . $checked . '/> <label for="' . $idc . '">' . atr_name($v['locales']) . '</div></label></li>';

      $html .= atr_loop_value_option($v, $type, $level + 1, $attribute, $has_values);
    }
  }

  return $html;
}



// REQUIRED FIELDS
function atr_post_required($cat_id = NULL) {
  if(osc_is_publish_page() || osc_is_edit_page()) {
    require_once 'user/required.php';
  }
}

function atr_edit_required($cat_id, $item_id = NULL) {
  atr_post_required($cat_id);
}


osc_add_hook('footer', 'atr_post_required');


// SAVE DATA POSTED-EDITED ITEM
function atr_posted($item) {
  $item_id = $item['pk_i_id'];
  $used_ids = array();

  // delete existing item attributes first
  ModelATR::newInstance()->removeItemAttributes($item_id);

  $params = Params::getParamsAsArray();

  foreach($params as $p => $d) {
    $atr = explode('_', $p);

    if(@$atr[0] == 'atr' && @$atr[1] > 0) {
      $attribute_id = (isset($atr[1]) ? $atr[1] : 0);
      $attribute = ModelATR::newInstance()->getAttribute($attribute_id);
      
      if($attribute === false || !isset($attribute['pk_i_id'])) {
        continue;
      }

      $type = $attribute['s_type'];
      $attribute_value_id = (isset($atr[2]) ? $atr[2] : null);
      $value = (($type == 'CHECKBOX' && $d == 'on') ? 1 : $d);

      atr_check_required($item['pk_i_id'], $attribute, $attribute_value_id, $value);

      if($type == 'SELECT' || $type == 'RADIO' || $type == 'CHECKBOX') {
        if($type == 'CHECKBOX') {
          $value = ($value == 1 ? 1 : null);
        } else {
          $attribute_value_id = $d;
        }

        ModelATR::newInstance()->updateItemAttributeValue($item_id, $attribute_id, $attribute_value_id, $value);

      } else if($type == 'TEXT' || $type == 'TEXTAREA' || $type == 'URL' || $type == 'EMAIL' || $type == 'PHONE' || $type == 'DATE' || $type == 'NUMBER') {
        ModelATR::newInstance()->updateItemAttributeValue($item_id, $attribute_id, null, osc_esc_html($value));

      } else if($type == 'DATERANGE' && !in_array($attribute_id, $used_ids)) {
        $start = osc_esc_html($params['atr_' . $attribute_id . '_start']);
        $end = osc_esc_html($params['atr_' . $attribute_id . '_end']);
        $range = $start . '|' . $end;

        ModelATR::newInstance()->updateItemAttributeValue($item_id, $attribute_id, null, $range);

        $used_ids[] = $attribute_id;
      }
    }
  }
}

osc_add_hook('posted_item', 'atr_posted');
osc_add_hook('edited_item', 'atr_posted');


// PRESERVE VALUES IN FORM
function atr_post_preserve() {
  $attributes = ModelATR::newInstance()->getAttributes(1);

  foreach($attributes as $a) {
    $names = atr_get_attribute_names($a['pk_i_id']);

    if(is_array($names) && count($names) > 0) {
      foreach($names as $name) {
        Session::newInstance()->_setForm($name, Params::getParam($name));
        Session::newInstance()->_keepForm($name);
      }
    }
  }
}

osc_add_hook('pre_item_post', 'atr_post_preserve');


// GET NAMES OF ATTRIBUTE INPUTS
function atr_get_attribute_names($attribute_id, $attribute_value_id = -1) {
  $names = array();
  $attribute = ModelATR::newInstance()->getAttribute($attribute_id);
  
  if($attribute === false || !isset($attribute['pk_i_id'])) {
    return false;
  }

  $type = $attribute['s_type'];
  $values = @$attribute['values'];

  if($type == 'SELECT') {
    $names[] = 'atr_' . $attribute_id;

  } else if($type == 'RADIO' || $type == 'CHECKBOX') {
    if(is_array($values) && count($values) > 0 && $attribute_value_id == -1) {
      foreach($values as $v) {
        $names[] = 'atr_' . $attribute_id . '_' . $v['pk_i_id'];
      }
    } else if ($attribute_value_id > 0) {
      $names[] = 'atr_' . $attribute_id . '_' . $attribute_value_id;
    }
  } else if($type == 'DATERANGE') {
    $names[] = 'atr_' . $attribute_id . '_start';
    $names[] = 'atr_' . $attribute_id . '_end';

  } else if($type == 'TEXT' || $type == 'TEXTAREA' || $type == 'DATE' || $type == 'NUMBER') {
    $names[] = 'atr_' . $attribute_id;
  } 

  return $names;
}


// CHECK IF REQUIRED WAS FILLED - PHP
function atr_check_required($item_id = '', $attribute = array(), $attribute_value_id = 0, $value = '') {
  if(osc_is_publish_page()) {
    $url = osc_item_post_url();
  } else {
    $url = osc_item_edit_url('', $item_id);
  }

  if(isset($attribute['pk_i_id']) && $attribute['b_required'] == 1 && ($attribute_value_id == '' && $value == '')) {
    $type = $attribute['s_type'];
    $identifier = ($attribute['s_identifier'] == '' ? 'id' . $attribute['pk_i_id'] : $attribute['s_identifier']);

    if($type == 'SELECT' || $type == 'RADIO' || $type == 'CHECKBOX') {
      if(isset($attribute['values']) && is_array($attribute['values']) && count($attribute['values']) > 0) {
        osc_add_flash_error_message(sprintf(__('You have not filled required field: %s', 'attributes'), $identifier)) ;
        header('Location: ' . $url);
        exit;
      }
    } else {
      osc_add_flash_error_message(sprintf(__('You have not filled required field: %s', 'attributes'), $identifier)) ;
      header('Location: ' . $url);
      exit;
    }
  }
}


// SHOW DATA ON ITEM FORM
function atr_post_form($cat_id = NULL, $item_id = NULL) {
  if($cat_id <= 0) {
    $cat_id = -1;
  }
  
  $html = '';
  $attributes = ModelATR::newInstance()->getAttributes(1, $cat_id);

  if(is_array($attributes) && count($attributes) > 0) {
    $html = '<div class="atr-form atr-theme-' . osc_current_web_theme() . ' ' . (atr_param('styled') == 1 ? 'atr-styled' : '') . '" id="atr-form">';

    foreach($attributes as $a) {
      if($a['fk_i_linked_to_attr_id'] > 0) {
        continue;
      }

      $html .= atr_generate_post_elem($a, $item_id);
      

    }

    $html .= '</div>';
  }

  echo $html;
}


function atr_generate_post_elem($a, $item_id, $linked = false) {
  $html = '';

  if($a === false || !isset($a['pk_i_id'])) {
    return false;
  }
  
  $required = ((ATR_HTML5_VALIDATION === true && isset($a['b_required']) && $a['b_required'] == 1) ? 'required' : '');

  $name = atr_name($a['locales']);
  $type = $a['s_type'];
  $restrict_type = $a['s_restrict_type'];

  // Check if user is eligible for attribute
  if($restrict_type != '') {
    if($restrict_type == 'ADMIN') {
      if(!osc_is_admin_user_logged_in()) {
        return '';
      }
      
    } else if($restrict_type == 'LOGGED') {
      if(!osc_is_web_user_logged_in()) {
        return '';
      }
      
    } else if($restrict_type == 'COMPANY') {
      $user = User::newInstance()->findByPrimaryKey(osc_logged_user_id());
      
      if($user === false || !isset($user['b_company']) || $user['b_company'] != 1) {
        return '';
      }
      
    } else if($restrict_type == 'PERSONAL') {
      $user = User::newInstance()->findByPrimaryKey(osc_logged_user_id());
      
      if($user === false || !isset($user['b_company']) || $user['b_company'] != 0) {
        return '';
      }
      
    } else if($restrict_type == 'MEMBERSHIP') {
      if(function_exists('osp_get_user_group')) {
        if(osc_logged_user_id() <= 0 || osp_get_user_group(osc_logged_user_id()) <= 0) {
          return '';
        }
      }
      
    } else if($restrict_type == 'BUSINESS') {
      if(function_exists('bpr_is_business')) {
        if(osc_logged_user_id() <= 0 || bpr_is_business(osc_logged_user_id()) !== true) {
          return '';
        }
      }
    
    } else if($restrict_type == 'BUSINESS_VERIF') {
      if(function_exists('bpr_is_business')) {
        if(osc_logged_user_id() <= 0) {
          return '';
        } else {
          $seller = ModelBPR::newInstance()->getSellerByUserId(osc_logged_user_id());

          if(!isset($seller['pk_i_id']) || $seller['b_enabled'] != 1 || $seller['b_verified'] != 1) {
            return '';
          }
        }
      }
    
    }
  }


  // Divider - separator - grouping element
  if($type == 'DIVIDER') {
    $html .= '<div class="control-group atr-field atr-type-' . strtolower($type) . '" id="atr-' . ($a['s_identifier'] <> '' ? $a['s_identifier'] : 'id' . $a['pk_i_id']) . '">';
    $html .= '<label class="control-label">&nbsp;</label>';
    $html .= '<strong class="atr-divider-label">' . $name . '</strong>';
    $html .= '</div>';
    return $html;
  }
  
  if($linked === false) {
    $html .= '<div class="control-group atr-field atr-type-' . strtolower($a['s_type']) . ' ' . ($a['s_type'] == 'CHECKBOX' ? 'atr-check-options-' . ($a['b_check_single'] == 1 ? 'single' : 'multi') : '') . '" id="atr-' . ($a['s_identifier'] <> '' ? $a['s_identifier'] : 'id' . $a['pk_i_id']) . '">';
    $html .= '<label class="control-label" for="atr_' . $a['pk_i_id'] . '">' . ($name <> '' ? $name : __('New attribute', 'attributes')) . ($a['b_required'] == 1 ? '<span class="req">*</span>' : '') . '</label>';
    $html .= '<div class="controls">';
  } else {
    $html .= '<div class="atr-linked">';
  }
  
  $item_atr = ModelATR::newInstance()->getItemAttributeRaw($item_id, $a['pk_i_id']);
  $item_atr['fk_i_attribute_value_id'] = (isset($item_atr['fk_i_attribute_value_id']) ? $item_atr['fk_i_attribute_value_id'] : '');
  $item_atr['s_value'] = (isset($item_atr['s_value']) ? $item_atr['s_value'] : '');

  if($a['s_type'] == 'SELECT') {
    if(is_array($a['values']) && count($a['values']) == 0) {
      $required = '';
    }

    $def_val = '';
    if($linked === true && is_array($a['values'])) {
      $def_val = @$a['values'][key($a['values'])]['pk_i_id'];
    }
    
    $html .= '<input type="hidden" id="atr_' . $a['pk_i_id'] . '" name="atr_' . $a['pk_i_id'] . '" value="' . ($item_atr['fk_i_attribute_value_id'] > 0 ? $item_atr['fk_i_attribute_value_id'] : $def_val) . '">';

    if($item_atr['fk_i_attribute_value_id'] > 0) {
      // We have selected ID already
      $hierarchy = atr_attribute_value_hierarchy($item_atr['fk_i_attribute_value_id']);
      $data = atr_attribute_value_siblings($item_atr['fk_i_attribute_id'], $item_atr['fk_i_attribute_value_id']);

      if(is_array($data) && count($data) > 0) {
        $j = 0;

        foreach($data as $d) {
          $selected_val = $d['selected_id'];
          $siblings = $d['siblings'];

          if(is_array($siblings) && count($siblings) > 0) {
            $inp_name = 'atr_select_' . $a['pk_i_id'] . '_' . ($j + 1);
            
            $html .= '<select data-level="' . ($j + 1) . '" data-atr-id="' . $a['pk_i_id'] . '" data-val-id="' . @$hierarchy[$j-1] . '" ' . ($j == 0 ? $required : '') . ' id="' . $inp_name . '" name="' . $inp_name . '">';
            $html .= '<option value="">' . __('Select value ...', 'attributes') . '</option>';
            
            foreach($siblings as $s) {
              $html .= '<option value="' . $s['pk_i_id'] . '" ' . ($s['pk_i_id'] == $selected_val ? 'selected="selected"' : '') . '>' . atr_name($s['locales']) . '</option>';
            }

            $html .= '</select>';
          }

          $j++;
        }

        // we have parents, build now children
        $children = ModelATR::newInstance()->getAttributeValuesByParent($item_atr['fk_i_attribute_id'], $item_atr['fk_i_attribute_value_id']);
       
        if(is_array($children) && count($children) > 0) {
          $inp_name = 'atr_select_' . $a['pk_i_id'] . '_' . ($j + 1);

          $html .= '<select data-level="' . ($j + 1) . '" data-atr-id="' . $a['pk_i_id'] . '" data-val-id="' . $item_atr['fk_i_attribute_value_id'] . '" id="' . $inp_name . '" name="' . $inp_name . '">';
          $html .= '<option value="">' . __('Select value ...', 'attributes') . '</option>';

          foreach($children as $c) {
            $html .= '<option value="' . $c['pk_i_id'] . '">' . atr_name($c['locales']) . '</option>';
          }

          $html .= '</select>';
        }
      }

    } else {
      // Generate first select only
      $inp_name = 'atr_select_' . $a['pk_i_id'] . '_1';
      $html .= '<select data-level="1" data-atr-id="' . $a['pk_i_id'] . '" data-val-id="" ' . $required . ' id="' . $inp_name . '" name="' . $inp_name . '">';

      if($linked === false) {
        $html .= '<option value="">' . __('Select value ...', 'attributes') . '</option>';
      }
      
      if(is_array($a['values']) && count($a['values']) > 0) {
        foreach($a['values'] as $v) {
          $html .= '<option value="' . $v['pk_i_id'] . '">' . atr_name($v['locales']) . '</option>';
        }
      } 

      $html .= '</select>';
    }

  } else if($a['s_type'] == 'CHECKBOX' || $a['s_type'] == 'RADIO') {
    if(is_array($a['values']) && count($a['values']) > 0) {
      $html .= '<ul class="atr-ul atr-ul-' . strtolower($a['s_type']) . '">';

      $k = 0;
      foreach($a['values'] as $v) {
        $item_atr_val = ModelATR::newInstance()->getItemAttributeRaw($item_id, $a['pk_i_id'], $v['pk_i_id']);

        if($type == 'CHECKBOX') {
          $inp_meta = (@$item_atr_val['s_value'] == 1 ? 'checked' : '');
          $inp_name = 'atr_' . $a['pk_i_id'] . '_' . $v['pk_i_id'];
          $inp_val = 'on';

          // Not supported !!! Inputs would need to use same name
          // Disabled - jQuery validation in place!!
          // if($required != '') {
            // $inp_meta .= ' ' . $required;
          // }
        } else {
          $inp_meta = (@$item_atr_val['fk_i_attribute_value_id'] == $v['pk_i_id'] ? 'checked' : '');
          $inp_name = 'atr_' . $a['pk_i_id'];
          $inp_val = $v['pk_i_id'];
          
          // Only add required to first radio
          // Disabled - jQuery validation in place!!
          // if($required != '' && $k == 0) {
            // $inp_meta .= ' ' . $required;
          // }
        }
        
        $inp_id = 'atr_' . $a['pk_i_id'] . '_' . $v['pk_i_id'];

        
        $html .= '<li>';
        $html .= '<div class="atr-input-box atr-' . strtolower($a['s_type']) . '">';
        $html .= '<input type="' . strtolower($a['s_type']) . '" id="' . $inp_id . '" name="' . $inp_name . '" value="' . $inp_val . '" ' . $inp_meta . '/> ';
        $html .= '<label for="' . $inp_id . '">' . atr_name($v['locales']) . '</label>';
        $html .= '</div>';
        $html .= '</li>';
        
        $k++;
      }

      if($a['s_type'] == 'CHECKBOX') {
        $html .= '<a href="#" class="atr-select-deselect atr-select-all">' . __('Select all', 'attributes') . '</a>';
        $html .= '<a href="#" class="atr-select-deselect atr-deselect-all" style="display:none;">' . __('Deselect all', 'attributes') . '</a>';
      }

      $html .= '</ul>';
    }

  } else if ($a['s_type'] == 'TEXT' || $a['s_type'] == 'PHONE' || $a['s_type'] == 'EMAIL' || $a['s_type'] == 'URL' || $a['s_type'] == 'NUMBER') {
    $input_type = strtolower($a['s_type'] == 'PHONE' ? 'TEL' : $a['s_type']);
    $html .= '<input type="' . $input_type . '" id="atr_' . $a['pk_i_id'] . '" name="atr_' . $a['pk_i_id'] . '" placeholder="' . atr_input_placeholder($a['s_type']) . '" value="' . $item_atr['s_value'] . '" ' . $required . '/>';

  } else if ($a['s_type'] == 'TEXTAREA') {
    $html .= '<textarea id="atr_' . $a['pk_i_id'] . '" name="atr_' . $a['pk_i_id'] . '" ' . $required . ' placeholder="' . atr_input_placeholder($a['s_type']) . '">' . $item_atr['s_value'] . '</textarea>';

  } else if ($a['s_type'] == 'DATE') {
    $html .= '<input type="date" id="atr_' . $a['pk_i_id'] . '" name="atr_' . $a['pk_i_id'] . '" value="' . $item_atr['s_value'] . '" ' . $required . '/>';

  } else if ($a['s_type'] == 'DATERANGE') {
    $range = explode('|', $item_atr['s_value']);

    $html .= '<input type="date" id="atr_' . $a['pk_i_id'] . '_start" name="atr_' . $a['pk_i_id'] . '_start" value="' . @$range[0] . '" ' . $required . '/>';
    $html .= '<span class="atr-date-del">-</span>';
    $html .= '<input type="date" id="atr_' . $a['pk_i_id'] . '_end" name="atr_' . $a['pk_i_id'] . '_end" value="' . @$range[1] . '" ' . $required . '/>';

  }
  
  if($linked === false) {
    $linked_attr = ModelATR::newInstance()->getLinkedAttribute($a['pk_i_id']);
    
    if($linked_attr !== false && isset($linked_attr['pk_i_id']) && $linked_attr['b_enabled'] == 1 && $a['fk_i_linked_to_attr_id'] <= 0) {
      if(in_array($a['s_type'], array('SELECT','TEXT','NUMBER','DATE','URL','PHONE','EMAIL'))) {
        if(in_array($linked_attr['s_type'], array('SELECT','TEXT','NUMBER','DATE','URL','PHONE','EMAIL'))) {
          $html .= atr_generate_post_elem($linked_attr, $item_id, true);
        }
      }
    }

    $html .= '</div>';
  }

  $html .= '</div>';

  return $html;     
}

function atr_edit_form($cat_id, $item_id = NULL) {
  atr_post_form($cat_id, $item_id);
}


osc_add_hook('item_form', 'atr_post_form');
osc_add_hook('item_edit', 'atr_edit_form');


// CALCULATE MAX DEPTH OF NESTED VALUES
function atr_max_values_depth($arr) {
  $max = 1;
  
  if(isset($arr['values']) && is_array($arr['values'])) {
    foreach ($arr['values'] as $v) {
      $depth = 1 + atr_max_values_depth($v);
      if ($depth > $max) $max = $depth;
    }
  }
  
  return $max;
}


function atr_attribute_value_siblings($attribute_id, $attribute_value_id) {
  $data = array();
  $values = atr_attribute_value_hierarchy($attribute_value_id);
  $rows = ModelATR::newInstance()->getAllAttributeValueRows($attribute_id);

  if(is_array($values) && count($values) > 0) {
    foreach($values as $v) {
      //$value_row = ModelATR::newInstance()->getAttributeValueRow($v);
      $value_row = @$rows[$v];
      
      if(isset($value_row['fk_i_attribute_id'])) {
        $sibling_values = ModelATR::newInstance()->getAttributeValuesByParent($value_row['fk_i_attribute_id'], $value_row['fk_i_parent_id']);

        $data[] = array(
          'selected_id' => $v,
          'siblings' => $sibling_values
        );
      }
    }
  }

  return $data;
}


function atr_attribute_value_hierarchy($attribute_value_id) {
  $array = ModelATR::newInstance()->getAttributeValueHierarchy($attribute_value_id);

  if(is_array($array) && !empty($array)) {
    return array_reverse($array);
  }

  return array();
}


function atr_attribute_value_children($type, $attribute_id, $attribute_value_id) {
  if($attribute_id > 0 && $attribute_value_id > 0) {
    $array = ModelATR::newInstance()->getAttributeValueChildren($type, $attribute_id, $attribute_value_id);

    if(is_array($array) && !empty($array)) {
      return array_unique(array_column($array, 'pk_i_id'));
    }

    return $array;
  }

  return array();
}


function atr_input_placeholder($type) {
  if($type == 'TEXT' || $type == 'TEXTAREA') {
    return 'abc';
  } else if ($type == 'NUMBER') {
    return '123';
  } else if ($type == 'PHONE') {
    return '+';
  } else if ($type == 'EMAIL') {
    return '@';
  } else if ($type == 'URL') {
    return 'https://';
  }
}


// UPDATE ATTRIBUTE VALUES POSITION - AJAX
function atr_val_position() {
  $order = json_decode(Params::getParam('list'), true);

  if(is_array($order) && count($order) > 0) {
    $i = 0;
    foreach($order as $o) {
      if(isset($o['c']) && $o['c'] > 0) {
        ModelATR::newInstance()->updateAttributeValuePosition($o['c'], $o['p'], $i+1);
        $i++;
      }
    }
  }

  exit;
}

osc_add_hook('ajax_admin_atr_val_position', 'atr_val_position');



// UPDATE ATTRIBUTES POSITION - AJAX
function atr_position() {
  $order = Params::getParam('atr');

  if(is_array($order) && count($order) > 0) {
    $i = 0;
    foreach($order as $o) {
      ModelATR::newInstance()->updateAttributePosition($o, $i+1);
      $i++;
    }
  }

  exit;
}

osc_add_hook('ajax_admin_atr_position', 'atr_position');


// REMOVE ATTRIBUTE VALUE - AJAX
function atr_remove_value() {
  $id = Params::getParam('id');

  ModelATR::newInstance()->removeAttributeValue($id);
  exit;
}

osc_add_hook('ajax_admin_atr_remove_value', 'atr_remove_value');


// ADD ATTRIBUTE VALUE - AJAX
function atr_add_value() {
  $attribute_id = Params::getParam('attributeId');
  $name = Params::getParam('name');     // urldecode ?
  $locale = Params::getParam('locale');

  if($attribute_id > 0) {
    $value = array('s_name' => $name);
    $value['pk_i_id'] = ModelATR::newInstance()->insertAttributeValue($attribute_id, $name, $locale);

    if($name != '') {
      ModelATR::newInstance()->updateAttributeValueLocale($value['pk_i_id'], $name, $locale);
    }

    echo '<li class="mb-val mb-val-new" id="val_' . $value['pk_i_id'] . '">';
    atr_div_value($value);
    echo '</li>';
  }

  exit;
}

osc_add_hook('ajax_admin_atr_add_value', 'atr_add_value');


// GET CASCADE
function atr_select_url() {
  $html = '';
  $level = Params::getParam('atrLevel');
  $attribute_id = Params::getParam('atrId');
  $attribute_value_id = Params::getParam('atrValId');
  $is_search = Params::getParam('isSearch');
  $is_publish = Params::getParam('isPublish');
  $has_values = ModelATR::newInstance()->getItemValuesList($attribute_id);
 
  if($attribute_id > 0) {
    $values = ModelATR::newInstance()->getAttributeValues('SELECT', $attribute_id, $attribute_value_id);
    $attribute = ModelATR::newInstance()->getAttribute(Params::getParam('atrId'));
    
    $meta = $inp_name = '';
    
    if($is_publish == 1) {
      $meta = ((ATR_HTML5_VALIDATION === true && $attribute['b_required'] == 1) ? ' required' : '');
      $inp_name = 'atr_select_' . $attribute_id . '_' . $level;
      $meta .= ' id="' . $inp_name . '" name="' . $inp_name . '"';
    }
    
    if(is_array($values) && count($values) > 0) {
      $html .= '<select data-level="' . $level . '" data-atr-id="' . $attribute_id . '" data-parent-id="' . $attribute_value_id . '"' . $meta . '>';
      $html .= '<option value="">' . __('Select value ...', 'attributes') . '</option>';

      $counter = 0;

      foreach($values as $v) {
        if($attribute['s_search_values_all'] == 1 && !in_array($v['pk_i_id'], $has_values) && $is_search == 1) {
          continue;
        }

        $html .= '<option value="' . $v['pk_i_id'] . '">' . atr_name($v['locales']) . '</option>';
        $counter++;
      }

      $html .= '</select>';

      if($counter == 0) {
        echo false;
        exit;
      }
    } else {
      echo false;
      exit;
    }
  }

  echo $html;
  exit;
}

osc_add_hook('ajax_atr_select_url', 'atr_select_url');


function atr_name($field, $locale = '') {
  if ($locale == '') {
    $locale = osc_current_user_locale();
  }
 
  $name = @$field[$locale];

  if($name == '') {
    $name = @$field[osc_language()];

    if($name == '') {
      $aLocales = osc_get_locales();
      foreach($aLocales as $locale) {
        $name = @$field[@$locale['pk_c_code']];
        if($name != '') {
          break;
        }
      }
    }
  }

  return (string) $name;
}


// CATEGORIES WORK
function atr_cat_tree($list = array()) {
  if(!is_array($list) || empty($list)) {
    $list = Category::newInstance()->listAll();
  }

  $array = array();
  //$root = Category::newInstance()->findRootCategoriesEnabled();

  foreach($list as $c) {
    if($c['fk_i_parent_id'] <= 0) {
      $array[$c['pk_i_id']] = array('pk_i_id' => $c['pk_i_id'], 's_name' => $c['s_name']);
      $array[$c['pk_i_id']]['sub'] = atr_cat_sub($list, $c['pk_i_id']);
    }
  }

  return $array;
}

function atr_cat_sub($list, $parent_id) {
  $array = array();
  //$cats = Category::newInstance()->findSubcategories($id);

  if(is_array($list) && count($list) > 0) {
    foreach($list as $c) {
      if($c['fk_i_parent_id'] == $parent_id) {  echo $c['s_name'];
        $array[$c['pk_i_id']] = array('pk_i_id' => $c['pk_i_id'], 's_name' => $c['s_name']);
        $array[$c['pk_i_id']]['sub'] = atr_cat_sub($list, $c['pk_i_id']);
      }
    }
  }
      
  return $array;
}


function atr_cat_list($selected = array(), $categories = '', $level = 0) {
  if($categories == '' || $level == 0) {
    $categories = atr_cat_tree($categories);
  }

  // Show if no category is selected on search
  if($level == 0) {
    echo '<option value="-1" ' . (in_array(-1, $selected) ? 'selected="selected"' : '') . '>' . __('Without category (show if no category is selected)', 'attributes') . '</option>';
  }
  
  foreach($categories as $c) {
    echo '<option value="' . $c['pk_i_id'] . '" ' . (in_array($c['pk_i_id'], $selected) ? 'selected="selected"' : '') . '>' . str_repeat('-', $level) . ($level > 0 ? ' ' : '') . $c['s_name'] . '</option>';

    if(is_array($c['sub']) && count($c['sub']) > 0) {
      atr_cat_list($selected, $c['sub'], $level + 1);
    }
  }
}


function atr_list_values_ol($values) {
 if(is_array($values) && count($values) > 0) {
    foreach($values as $v) {
      ?>

      <li class="mb-val" id="val_<?php echo $v['pk_i_id']; ?>">
        <?php atr_div_value($v); ?>
      
        <ol>
          <?php 
            if(isset($v['values']) && is_array($v['values']) && count($v['values']) > 0) { 
              atr_list_values_ol($v['values']); 
            }
          ?>
        </ol>
      </li>
    <?php
    }
  }
}


function atr_div_value($value) {
?>
  <div>
    <i class="fa fa-arrows move"></i>
    <input name="val-<?php echo @$value['pk_i_id']; ?>-s_name" type="text" class="val-field val-name" value="<?php echo @$value['s_name']; ?>" placeholder="<?php echo osc_esc_html(__('Value', 'attributes')); ?>" />
    <input name="val-<?php echo @$value['pk_i_id']; ?>-s_image" type="text" class="val-field val-image" value="<?php echo @$value['s_image']; ?>" placeholder="<?php echo osc_esc_html(__('Icon link', 'attributes')); ?>" />
    <a href="#" data-id="<?php echo @$value['pk_i_id']; ?>" class="remove" title="<?php echo osc_esc_html(__('Remove', 'attributes')); ?>"><i class="fa fa-times"></i></a>
    <span data-id="<?php echo @$value['pk_i_id']; ?>" class="show-hide"><i class="fa fa-angle-down"></i></span>
  </div>
<?php
}


function atr_list_values($values, $level = 0) {
  $array = array();
  $level++;

  if(is_array($values) && count($values) > 0) {
    foreach($values as $v) {
      $a = $v;
      unset($a['values']);
      $a['i_level'] = $level;

      $array[] = $a;

      $array = array_merge($array, atr_list_values($v['values'], $level));
    }
  }

  return $array;
}



// GET CURRENT OR DEFAULT ADMIN LOCALE
function atr_get_locale() {
  $locales = osc_get_locales();

  if(empty($locales)) {
    $locales = OSCLocale::newInstance()->listAllEnabled();
  }

  if(Params::getParam('atrLocale') <> '') {
    $current = Params::getParam('atrLocale');
  } else {
    $current = (osc_current_user_locale() <> '' ? osc_current_user_locale() : osc_current_admin_locale());
    $current_exists = false;

    // check if current locale exist in front-office
    foreach( $locales as $l ) {
      if($current == $l['pk_c_code']) {
        $current_exists = true;
      }
    }

    if( !$current_exists ) {
      $i = 0;
      foreach( $locales as $l ) {
        if( $i==0 ) {
          $current = $l['pk_c_code'];
        }

        $i++;
      }
    }
  }

  return $current;
}


// CREATE LOCALE SELECT BOX
function atr_locale_box($file, $attribute_id = -1) {
  $html = '';
  $locales = OSCLocale::newInstance()->listAllEnabled();
  $current = atr_get_locale();

  $atr_id_string = ($attribute_id > 0 ? '&id=' . $attribute_id : '');

  $html .= '<select rel="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=attributes/admin/' . $file . $atr_id_string . '" class="mb-select mb-select-locale" id="atrLocale" name="atrLocale">';

  foreach( $locales as $l ) {
    $html .= '<option value="' . $l['pk_c_code'] . '" ' . ($current == $l['pk_c_code'] ? 'selected="selected"' : '') . '>' . $l['s_name'] . '</option>';
  }
 
  $html .= '</select>';
  return $html;
}


// CHECK IF RUNNING ON DEMO
function atr_is_demo() {
  if(osc_logged_admin_username() == 'admin') {
    return false;
  } else if(isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'],'mb-themes') !== false || strpos($_SERVER['HTTP_HOST'],'abprofitrade') !== false)) {
    return true;
  } else {
    return false;
  }
}


// CORE FUNCTIONS
function atr_param($name) {
  return osc_get_preference($name, 'plugin-attributes');
}


if(!function_exists('mb_param_update')) {
  function mb_param_update( $param_name, $update_param_name, $type = NULL, $plugin_var_name = NULL ) {
  
    $val = '';
    if( $type == 'check') {

      // Checkbox input
      if( Params::getParam( $param_name ) == 'on' ) {
        $val = 1;
      } else {
        if( Params::getParam( $update_param_name ) == 'done' ) {
          $val = 0;
        } else {
          $val = ( osc_get_preference( $param_name, $plugin_var_name ) != '' ) ? osc_get_preference( $param_name, $plugin_var_name ) : '';
        }
      }
    } else {

      // Other inputs (text, password, ...)
      if( Params::getParam( $update_param_name ) == 'done' && Params::existParam($param_name)) {
        $val = Params::getParam( $param_name );
      } else {
        $val = ( osc_get_preference( $param_name, $plugin_var_name) != '' ) ? osc_get_preference( $param_name, $plugin_var_name ) : '';
      }
    }


    // If save button was pressed, update param
    if( Params::getParam( $update_param_name ) == 'done' ) {

      if(osc_get_preference( $param_name, $plugin_var_name ) == '') {
        osc_set_preference( $param_name, $val, $plugin_var_name, 'STRING');  
      } else {
        $dao_preference = new Preference();
        $dao_preference->update( array( "s_value" => $val ), array( "s_section" => $plugin_var_name, "s_name" => $param_name ));
        osc_reset_preferences();
        unset($dao_preference);
      }
    }

    return $val;
  }
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


if( !function_exists('osc_is_contact_page') ) {
  function osc_is_contact_page() {
    $location = Rewrite::newInstance()->get_location();
    $section = Rewrite::newInstance()->get_section();
    if( $location == 'contact' ) {
      return true ;
    }

    return false ;
  }
}


// COOKIES WORK
if(!function_exists('mb_set_cookie')) {
  function mb_set_cookie($name, $val) {
    Cookie::newInstance()->set_expires( 86400 * 30 );
    Cookie::newInstance()->push($name, $val);
    Cookie::newInstance()->set();
  }
}


if(!function_exists('mb_get_cookie')) {
  function mb_get_cookie($name) {
    return Cookie::newInstance()->get_value($name);
  }
}

if(!function_exists('mb_drop_cookie')) {
  function mb_drop_cookie($name) {
    Cookie::newInstance()->pop($name);
  }
}


// CURRENT URL
function atr_current_url() {
  $pageURL = (isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on') ? "https://" : "http://";
  $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

  return $pageURL;
}

?>