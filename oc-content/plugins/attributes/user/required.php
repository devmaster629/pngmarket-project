<?php
  if($cat_id <= 0) {
    $cat_id = -9; //-1;
  }
  
  $attributes = ModelATR::newInstance()->getRequiredAttributes($cat_id);
  $fields = array();
  $used_ids = array();

  if(count($attributes) > 0) { 
    foreach($attributes as $a) { 
      if(!in_array($a['pk_i_id'], $used_ids)) { 
    
        $type = $a['s_type'];
        $name = ($a['s_name'] <> '' ? $a['s_name'] : ($a['s_identifier'] <> '' ? $a['s_identifier'] : __('Field ID:', 'attributes') . ' ' . $a['pk_i_id']));

        if($type == 'TEXT' || $type == 'TEXTAREA' || $type == 'DATE' || $type == 'EMAIL' || $type == 'URL' || $type == 'PHONE' || $type == 'NUMBER') {
          $fields[] = array('identifier' => '#atr_' . $a['pk_i_id'], 'name' => $name);

        } else if ($type == 'DATERANGE') {
          $fields[] = array('identifier' => '#atr_' . $a['pk_i_id'] . '_start', 'name' => $name . ' ' . __('(Start)', 'attributes'));
          $fields[] = array('identifier' => '#atr_' . $a['pk_i_id'] . '_end', 'name' => $name . ' ' . __('(End)', 'attributes'));

        } else if ($type == 'SELECT') {
          if(isset($a['values']) && count($a['values']) > 0) {
            //$identifier = 'input[name="atr_' . $a['pk_i_id'] . '"] + select';
            //$identifier = '#select_' . $a['pk_i_id'];    // both problematic, it shows message multiple times
            // $fields[] = array('identifier' => 'input[name="atr_' . $a['pk_i_id'] . '"]', 'name' => $name);
            
            // Required condition for every value level
            for($i=0; $i <= atr_max_values_depth($a); $i++) {
              $fields[] = array('identifier' => 'select[name="atr_select_' . $a['pk_i_id'] . '_' . ($i+1) . '"]', 'name' => $name . ($i > 0 ? ' (' . sprintf(__('level %d', 'attributes'), $i+1) . ')': ''));
            }
          }
        } else if ($type == 'RADIO' || $type == 'CHECKBOX') {
          if(isset($a['values']) && count($a['values']) > 0) {
            $fields[] = array('type' => $type, 'identifier' => 'atr_' . $a['pk_i_id'] . '_', 'name' => $name);
          }
        }

        $used_ids[] = $a['pk_i_id'];
      }
    }
  } 
?>

<!-- ATTRIBUTES PLUGIN - REQUIRE FIELDS JQUERY VALIDATION - <?php echo count($fields); ?> FIELDS ARE REQUIRED -->
<?php if(count($fields) > 0) { ?>
<script type="text/javascript">
  $(document).ready(function(){
    $.validator.addMethod("require_group", function(value, elem, options) {
      var selector = options[0];

      if($(selector + ":checked").length > 0){
        return true;
      } else {
        return false;
      }
    }, "{1}: <?php echo osc_esc_html(__('this field is required. Select at least one option.', 'attributes')); ?>");


    // Uncaught TypeError: Cannot read property 'settings' of undefined  ==> means rule is added on element that does not exist
    setTimeout(function(){
      setInterval(function(){
        if($.validator || $('form[name="item"]').data('validator')) {
          <?php foreach($fields as $f) { ?>
            <?php if(isset($f['type']) && ($f['type'] == 'RADIO' || $f['type'] == 'CHECKBOX')) { ?>
              if($('input[name^="<?php echo $f['identifier']; ?>"]').length) {
                $('input[name^="<?php echo $f['identifier']; ?>"]').rules('add', {
                  require_group: ['input[name^="<?php echo $f['identifier']; ?>"]', '<?php echo osc_esc_js($f['name']); ?>']
                });
              }
            <?php } else { ?>
              if($('<?php echo $f['identifier']; ?>').length) {
                $('<?php echo $f['identifier']; ?>').rules('add', {
                  required: true,
                  messages: {required: '<?php echo osc_esc_js(sprintf(__('Required field: %s', 'attributes'), $f['name'])); ?>'}
                });
              }
            <?php } ?>
          <?php } ?>

        }
      }, 500);
    }, 1500);
  });
</script>
<?php } ?>