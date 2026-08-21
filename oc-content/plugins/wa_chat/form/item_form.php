<?php
$item_id = Params::getParam('itemId');

if(isset($item_id) && $item_id > 0) {
  $data = ModelWAC::newInstance()->getData($item_id); 
  $enabled = isset($data['b_enable']) ? $data['b_enable'] : 0;
} else {
  $enabled = 1;
}

if(Params::existParam('wac_enable')) {
  $enabled = (Params::getParam('wac_enable') == 'on' ? 1 : 0);
}
?>

<?php if(wac_param('ask_seller') == 1 && wac_param('enable_items') == 1) { ?>
  <?php if(defined('OC_ADMIN') && OC_ADMIN == true) { ?>
    <style>
      #wac-check label {width:auto;max-width:calc(100% - 24px);margin: -1px 0 0 0; float: left;}
    </style>
  <?php } ?>
  
  <div class="control-group" id="wac-check">
    <div class="controls checkbox">
      <div class="input-box-check">
        <input id="wac_enable" type="checkbox" name="wac_enable" value="1" <?php echo ($enabled == 1 ? 'checked' : ''); ?>>
        <label class="control-label" for="wac_enable"><?php _e('Enable WhatsApp chat with me on this item', 'wa_chat');?></label>
      </div>
    </div>
  </div>
<?php } ?>