<?php
  // Create menu
  $title = __('Configure', 'attributes');
  atr_menu($title);


  // GET & UPDATE PARAMETERS
  // $variable = mb_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check or value
  $styled = mb_param_update('styled', 'plugin_action', 'check', 'plugin-attributes');
  $attribute_icon = mb_param_update('attribute_icon', 'plugin_action', 'check', 'plugin-attributes');
  $enable_cache = mb_param_update('enable_cache', 'plugin_action', 'check', 'plugin-attributes');


  if(Params::getParam('plugin_action') == 'done') {
    osc_add_flash_ok_message(__('Settings were successfully saved', 'attributes'), 'admin');
    header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=attributes/admin/configure.php');
    exit;
  }


  if(Params::getParam('plugin_action') == 'attribute') {
    $attribute_id = Params::getParam('attribute_id');

    if($attribute_id > 0) {
      $params = Params::getParamsAsArray();

      $error = ModelATR::newInstance()->updateAttribute($params);

      if(count($params) > 0) {
        $updated_ids = array();

        foreach($params as $p => $d) { 
          $value = explode('-', $p);

          if(@$value[0] == 'val' && !in_array(@$value[1], $updated_ids)) {
            $data = array();

            $id = @$value[1];

            $data['pk_i_id'] = $id;
            $data['s_name'] = @$params['val-' . $id . '-s_name'];
            $data['s_image'] = @$params['val-' . $id . '-s_image'];
            $data['fk_c_locale_code'] = $params['fk_c_locale_code'];
            
            ModelATR::newInstance()->updateAttributeValue($data);

            $updated_ids[] = $id;
          }
        }
      }

      if(@$error['code'] > 0 && @$error['message'] <> '') {
        osc_add_flash_error_message(__('There was problem updating attribute, database structure for table t_attribute does not match! Disable/Enable plugin and if error still persist, reinstall plugin.', 'attributes') . '<br/>' . $error['code'] . ': ' . $error['message'], 'admin');
      } else {
        osc_add_flash_ok_message( __('Attribute successfully updated', 'attributes') . ' (' . atr_get_locale() . ')', 'admin');
      }
      
      header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=attributes/admin/edit.php&id=' . $attribute_id);
      exit;
      
      ?>
        <script>
          $(document).ready(function(){ 
            $('.mb-field[data-id="<?php echo $attribute_id; ?>"] .mb-top-line').click(); 
            $('html, body').animate({ scrollTop: $('.mb-field[data-id="<?php echo $attribute_id; ?>"]').offset().top - 60 }, 0);
          });         
        </script>
      <?php
    }
  }

  // REMOVE ATTRIBUTE
  if(Params::getParam('remove') == '1') {
    if(Params::getParam('attributeId') > 0) {
      ModelATR::newInstance()->removeAttribute(Params::getParam('attributeId'));
      osc_add_flash_ok_message(__('Attribute removed successfully', 'attributes'), 'admin');
      header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=attributes/admin/configure.php');
      exit;
    }
  }

  // ADD ATTRIBUTE
  if(Params::getParam('new') == '1') {
    $id = ModelATR::newInstance()->insertAttribute();
    
    if($id > 0) {
      osc_add_flash_ok_message(__('Attribute created successfully', 'attributes'), 'admin');
      
      header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=attributes/admin/edit.php&id=' . $id);
      exit;
    } else {
      osc_add_flash_error_message(__('New attribute could not be created', 'attributes'), 'admin');
      header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=attributes/admin/configure.php');
      exit;
    }
  }

  $category_all = Category::newInstance()->listAll();

?>



<div class="mb-body">

  <div class="mb-message-js"></div>

  <!-- ATTRIBUTES SECTION -->
  <div class="mb-box">
    <div class="mb-head">
      <i class="fa fa-list"></i> <?php _e('Attributes', 'attributes'); ?>
    </div>

    <div class="mb-inside mb-attributes">
      <?php $attributes = ModelATR::newInstance()->getAttributes(); ?>

      <div id="mb-attr">
        <?php if(count($attributes) > 0) { ?>
          <?php foreach($attributes as $a) { ?>
            <?php $category_array = explode(',', $a['s_category_id']); ?>

            <form name="promo_form" id="atr_<?php echo $a['pk_i_id']; ?>" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
              <input type="hidden" name="page" value="plugins" />
              <input type="hidden" name="action" value="renderplugin" />
              <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>configure.php" />
              <input type="hidden" name="plugin_action" value="attribute" />
              <input type="hidden" name="attribute_id" value="<?php echo $a['pk_i_id']; ?>" />
              <input type="hidden" name="pk_i_id" value="<?php echo $a['pk_i_id']; ?>" />
              <input type="hidden" name="fk_c_locale_code" value="<?php echo atr_get_locale(); ?>" />
              <input type="hidden" name="atrLocale" value="<?php echo Params::getParam('atrLocale'); ?>" />

              <div class="mb-field" data-id="<?php echo $a['pk_i_id']; ?>">
                <div class="mb-top-line">
                  <span class="move"><i class="fa fa-arrows"></i></span>
                  <span class="name">
                    <?php echo ($a['s_name'] <> '' ? $a['s_name'] : __('New attribute', 'attributes')); ?>
                    <?php if($a['b_enabled'] == 1) { ?>
                      <i class="fa fa-check-circle enabled"></i>
                    <?php } else { ?>
                      <i class="fa fa-times-circle disabled"></i>
                    <?php } ?>
                  </span>
                  <span class="type"><?php echo ucfirst($a['s_type']); ?>&nbsp;</span>
                  <span class="count"><?php echo $a['values_count'] . ' ' . __('values', 'attributes'); ?></span>
                  <span class="mb-buttons">
                    <?php if(!atr_is_demo()) { ?>
                      <a href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=attributes/admin/configure.php&attributeId=<?php echo $a['pk_i_id']; ?>&remove=1" class="mb-btn mb-button-white remove" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to remove this attribute? Action cannot be undone', 'attributes')); ?>')"><i class="fa fa-trash"></i><span><?php _e('Delete', 'attributes'); ?></span></a>
                    <?php } ?>
                    
                    <a href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=attributes/admin/edit.php&id=<?php echo $a['pk_i_id']; ?>" class="mb-btn mb-button-green"><i class="fa fa-pencil"></i><span><?php _e('Edit', 'attributes'); ?></span></a>
                  </span>
                </div>

              </div>
            </form>

          <?php } ?>
        <?php } else { ?>
          <div class="mb-no-attributes"><?php _e('You have not created any attributes yet', 'attributes'); ?></div>
        <?php } ?>

        <a href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=attributes/admin/configure.php&new=1" class="mb-add-attribute"><i class="fa fa-plus-circle"></i><?php _e('Add new attribute', 'attributes'); ?></a>

      </div>
    </div>
  </div>


  <!-- CONFIGURE SECTION -->
  <div class="mb-box">
    <div class="mb-head">
      <i class="fa fa-wrench"></i> <?php _e('Configure', 'attributes'); ?>
    </div>

    <div class="mb-inside">
      <form name="promo_form" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
        <input type="hidden" name="page" value="plugins" />
        <input type="hidden" name="action" value="renderplugin" />
        <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>configure.php" />
        <input type="hidden" name="plugin_action" value="done" />
       
        <div class="mb-row">
          <label for="styled"><span><?php _e('Styled Inputs', 'attributes'); ?></span></label> 
          <input name="styled" id="styled" type="checkbox" class="element-slide" <?php echo ($styled == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('When enabled, all inputs (input, select, textarea, ...) will be styled by plugin. Disable this if your theme style plugin inputs in acceptable way.', 'attributes'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="attribute_icon"><span><?php _e('Enable Attribute Icons', 'attributes'); ?></span></label> 
          <input name="attribute_icon" id="attribute_icon" type="checkbox" class="element-slide" <?php echo ($attribute_icon == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('When enabled, icons are available on attributes (does not affect attribute values icons).', 'attributes'); ?></div>
        </div>

        <div class="mb-row">
          <label for="enable_cache"><span><?php _e('Enable Cache', 'attributes'); ?></span></label> 
          <input name="enable_cache" id="enable_cache" type="checkbox" class="element-slide" <?php echo ($enable_cache == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php echo sprintf(__('When enabled, all database queries in front-office will be cached for %d seconds.', 'attributes'), atr_cache_ttl()); ?></div>
        </div>

        <div class="mb-row">&nbsp;</div>

        <div class="mb-foot">
          <?php if(atr_is_demo()) { ?>
            <a class="mb-button mb-has-tooltip disabled" onclick="return false;" style="cursor:not-allowed;opacity:0.5;" title="<?php echo osc_esc_html(__('This is demo site', 'attributes')); ?>"><?php _e('Save', 'attributes');?></a>
          <?php } else { ?>
            <button type="submit" class="mb-button"><?php _e('Save', 'attributes');?></button>
          <?php } ?>
        </div>
      </form>
    </div>
  </div>
  

  <!-- PLUGIN INTEGRATION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-wrench"></i> <?php _e('Plugin Setup', 'attributes'); ?></div>

    <div class="mb-inside">

      <div class="mb-row"><?php _e('No theme modification are required to use all functions of plugin on 100%, however if you want to place some attributes on item page (or loops) to other than hook position, you can show each field calling following function.', 'attributes'); ?></div>
      <div class="mb-row">
        <span class="mb-code">&lt;?php if(function_exists('atr_show_attribute')) { echo atr_show_attribute( {attribute id} ); } ?&gt;</span>
      </div>
      <div class="mb-row"><?php _e('{attribute id} replace with ID of attribute. Example: atr_show_attribute( 7 );', 'attributes'); ?></div>
    </div>
  </div>

</div>


<script type="text/javascript">
  var atr_position_url = "<?php echo osc_admin_base_url(true); ?>?page=ajax&action=runhook&hook=atr_position";


  var atr_message_ok = "<?php echo osc_esc_html(__('Success!', 'attributes')); ?>";
  var atr_message_wait = "<?php echo osc_esc_html(__('Updating, please wait...', 'attributes')); ?>";
  var atr_message_error = "<?php echo osc_esc_html(__('Error!', 'attributes')); ?>";



  // SORTABLE VALUES
  $(document).ready(function(){
    var attr_list = '';

    $('#mb-attr').sortable({
      axis: "y",
      forcePlaceholderSize: true,
      handle: '.mb-top-line',
      helper: 'clone',
      items: 'form',
      opacity: .8,
      placeholder: 'placeholder',
      revert: 100,
      tabSize: 5,
      tolerance: 'intersect',
      start: function(event, ui) {
        attr_list = $(this).sortable('serialize');
      },
      stop: function (event, ui) {
        var c_attr_list = $(this).sortable('serialize');

        atr_message(atr_message_wait, 'info');

        if(attr_list != c_attr_list) {
          $.ajax({
            url: atr_position_url,
            type: "POST",
            data: c_attr_list,
            success: function(response){
              //console.log(response);
              atr_message(atr_message_ok, 'ok');
            },
            error: function(response) {
              atr_message(atr_message_error, 'error');
              console.log(response);
            }
          });
        }
      }
    });

  });
</script>


<?php echo atr_footer(); ?>