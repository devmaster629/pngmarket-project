<?php
  // Create menu
  $title = __('Configure', 'wa_chat');
  wac_menu($title);
 

  // GET & UPDATE PARAMETERS
  // $variable = mb_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check or value
  $enable_items = mb_param_update('enable_items', 'plugin_action', 'check', 'plugin-wa_chat');
  $only_logged = mb_param_update('only_logged', 'plugin_action', 'check', 'plugin-wa_chat');
  $enable_stats = mb_param_update('enable_stats', 'plugin_action', 'check', 'plugin-wa_chat');
  $ask_seller = mb_param_update('ask_seller', 'plugin_action', 'check', 'plugin-wa_chat');
  $enable_existing = mb_param_update('enable_existing', 'plugin_action', 'check', 'plugin-wa_chat');
  $user_profile_phone = mb_param_update('user_profile_phone', 'plugin_action', 'check', 'plugin-wa_chat');
  $use_phone = mb_param_update('use_phone', 'plugin_action', 'value', 'plugin-wa_chat');
  $custom_field_slug = mb_param_update('custom_field_slug', 'plugin_action', 'value', 'plugin-wa_chat');
  $default_country_code = mb_param_update('default_country_code', 'plugin_action', 'value', 'plugin-wa_chat');
  $sanitize_phone = mb_param_update('sanitize_phone', 'plugin_action', 'check', 'plugin-wa_chat');
  $phone_length = mb_param_update('phone_length', 'plugin_action', 'value', 'plugin-wa_chat');
  // $hook = mb_param_update('hook', 'plugin_action', 'check', 'plugin-wa_chat');
  $hooks = mb_param_update('hooks', 'plugin_action', 'value', 'plugin-wa_chat');
  $icon = mb_param_update('icon', 'plugin_action', 'value', 'plugin-wa_chat');
  $style = mb_param_update('style', 'plugin_action', 'value', 'plugin-wa_chat');
  $size = mb_param_update('size', 'plugin_action', 'value', 'plugin-wa_chat');
  $category = mb_param_update('category', 'plugin_action', 'value', 'plugin-wa_chat');
  $phone_in_button = mb_param_update('phone_in_button', 'plugin_action', 'value', 'plugin-wa_chat');
  $button_text = mb_param_update('button_text', 'plugin_action', 'value', 'plugin-wa_chat');
  $button_bg_color = mb_param_update('button_bg_color', 'plugin_action', 'value', 'plugin-wa_chat');
  $button_font_color = mb_param_update('button_font_color', 'plugin_action', 'value', 'plugin-wa_chat');
  $button_padding = mb_param_update('button_padding', 'plugin_action', 'value', 'plugin-wa_chat');
  $icon_padding = mb_param_update('icon_padding', 'plugin_action', 'value', 'plugin-wa_chat');
  $icon_bg_color = mb_param_update('icon_bg_color', 'plugin_action', 'value', 'plugin-wa_chat');
  $custom_css = mb_param_update('custom_css', 'plugin_action', 'value', 'plugin-wa_chat');
  
  $web_enable = mb_param_update('web_enable', 'plugin_action', 'check', 'plugin-wa_chat');
  $web_only_logged = mb_param_update('web_only_logged', 'plugin_action', 'check', 'plugin-wa_chat');
  $web_phone = mb_param_update('web_phone', 'plugin_action', 'value', 'plugin-wa_chat');
  $web_button_text = mb_param_update('web_button_text', 'plugin_action', 'value', 'plugin-wa_chat');
  $web_position = mb_param_update('web_position', 'plugin_action', 'value', 'plugin-wa_chat');
  $web_style = mb_param_update('web_style', 'plugin_action', 'value', 'plugin-wa_chat');
  $web_size = mb_param_update('web_size', 'plugin_action', 'value', 'plugin-wa_chat');
  $web_margin = mb_param_update('web_margin', 'plugin_action', 'value', 'plugin-wa_chat');
  $web_button_bg_color = mb_param_update('web_button_bg_color', 'plugin_action', 'value', 'plugin-wa_chat');
  $web_button_font_color = mb_param_update('web_button_font_color', 'plugin_action', 'value', 'plugin-wa_chat');
  $web_button_padding = mb_param_update('web_button_padding', 'plugin_action', 'value', 'plugin-wa_chat');
  $web_icon_padding = mb_param_update('web_icon_padding', 'plugin_action', 'value', 'plugin-wa_chat');
  $web_icon_bg_color = mb_param_update('web_icon_bg_color', 'plugin_action', 'value', 'plugin-wa_chat');
  $web_custom_css = mb_param_update('web_custom_css', 'plugin_action', 'value', 'plugin-wa_chat');
  $web_phone_in_button = mb_param_update('web_phone_in_button', 'plugin_action', 'value', 'plugin-wa_chat');
  $web_hook = mb_param_update('web_hook', 'plugin_action', 'check', 'plugin-wa_chat');
  $web_icon = mb_param_update('web_icon', 'plugin_action', 'value', 'plugin-wa_chat');


  $category_array = array_filter(explode(',', $category));
  $category_all = Category::newInstance()->listAll();

  if(Params::getParam('plugin_action') == 'done') {
    message_ok(__('Settings were successfully saved', 'wa_chat'));
  }
?>


<div class="mb-body">
  <!-- CONFIGURE SECTION -->
  
  <div class="mb-notes">
    <div class="mb-line"><?php _e('Plugin will enable to add WhatsApp Chat Button on Listing page or for Web contact.', 'wa_chat'); ?></div>
  </div>

  <form name="promo_form" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
    <input type="hidden" name="page" value="plugins" />
    <input type="hidden" name="action" value="renderplugin" />
    <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>configure.php" />
    <input type="hidden" name="plugin_action" value="done" />
        
    <div class="mb-box">
      <div class="mb-head">
        <i class="fa fa-wrench"></i> <?php _e('Configure Item Contact Button', 'wa_chat'); ?>
      </div>

      <div class="mb-inside">


        <div class="mb-row">
          <label for="enable_items"><span><?php _e('Enable Button on Items', 'wa_chat'); ?></span></label> 
          <input name="enable_items" id="enable_items" type="checkbox" class="element-slide" <?php echo ($enable_items == 1 ? 'checked' : ''); ?>/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('When enabled, WhatsApp contact button on listings will be created.', 'wa_chat'); ?></div>
          </div>
        </div>

        <div class="mb-row">
          <label for="only_logged"><span><?php _e('Only for Logged-in Users', 'wa_chat'); ?></span></label> 
          <input name="only_logged" id="only_logged" type="checkbox" class="element-slide" <?php echo ($only_logged == 1 ? 'checked' : ''); ?>/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('When enabled, only logged-in users can use contact button.', 'wa_chat'); ?></div>
          </div>
        </div>

        <div class="mb-row">
          <label for="enable_stats"><span><?php _e('Stats Collecting', 'wa_chat'); ?></span></label> 
          <input name="enable_stats" id="enable_stats" type="checkbox" class="element-slide" <?php echo ($enable_stats == 1 ? 'checked' : ''); ?>/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('When enabled, usage of contact button will be collected. This includes views and clicks of button.', 'wa_chat'); ?></div>
          </div>
        </div>
        
        <div class="mb-row">
          <label for="ask_seller"><span><?php _e('Ask Seller', 'wa_chat'); ?></span></label> 
          <input name="ask_seller" id="ask_seller" type="checkbox" class="element-slide" <?php echo ($ask_seller == 1 ? 'checked' : ''); ?>/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('When enabled, seller must consent with using contact button on its listing.', 'wa_chat'); ?></div>
          </div>
        </div>

        <div class="mb-row">
          <label for="enable_existing"><span><?php _e('Enable on Existing Items', 'wa_chat'); ?></span></label> 
          <input name="enable_existing" id="enable_existing" type="checkbox" class="element-slide" <?php echo ($enable_existing == 1 ? 'checked' : ''); ?>/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('When enabled, contact button will be added automatically to existing listings. If you have enabled "Ask seller" option, this setting has no effect.', 'wa_chat'); ?></div>
          </div>
        </div>
        
        <div class="mb-row">
          <label for="user_profile_phone"><span><?php _e('Use User Phone', 'wa_chat'); ?></span></label> 
          <input name="user_profile_phone" id="user_profile_phone" type="checkbox" class="element-slide" <?php echo ($user_profile_phone == 1 ? 'checked' : ''); ?>/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('When enabled and listing phone number is not available, plugin will try to use user mobile or land phone number (if filled in profile).', 'wa_chat'); ?></div>
          </div>
        </div>        
        

        <div class="mb-row">
          <label for="phone_in_button"><span><?php _e('Add Phone Number to Button', 'wa_chat'); ?></span></label> 
          <select name="phone_in_button" id="phone_in_button">
            <option value="" <?php if($phone_in_button == '') { ?>selected="selected"<?php } ?>><?php _e('Do not add', 'wa_chat'); ?></option>
            <option value="SAME_LINE" <?php if($phone_in_button == 'SAME_LINE') { ?>selected="selected"<?php } ?>><?php _e('Add to same line', 'wa_chat'); ?></option>
            <option value="NEW_LINE" <?php if($phone_in_button == 'NEW_LINE') { ?>selected="selected"<?php } ?>><?php _e('Add on new line', 'wa_chat'); ?></option>
          </select>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Select if you want to add phone number to WhatsApp button, and if yes if it should be on same line as is text, or into new line.', 'wa_chat'); ?></div>
            <div class="mb-line"><?php _e('"Add on new line" option is recommended only for buttons of size ', 'wa_chat'); ?></div>
          </div>
        </div>
        
        
        <div class="mb-row">
          <label for="use_phone"><span><?php _e('Use Phone from Field', 'wa_chat'); ?></span></label> 
          <select name="use_phone" id="use_phone">
            <option value="OSCLASS_PHONE" <?php if($use_phone == '' || $use_phone == 'OSCLASS_PHONE') { ?>selected="selected"<?php } ?>><?php _e('Osclass Item Phone (v4.x or higher)', 'wa_chat'); ?></option>
            <option value="OSCLASS_OTHER" <?php if($use_phone == 'OSCLASS_OTHER') { ?>selected="selected"<?php } ?>><?php _e('Osclass Item Other Field (v4.x or higher)', 'wa_chat'); ?></option>
            <option value="CUSTOM_FIELD" <?php if($use_phone == 'CUSTOM_FIELD') { ?>selected="selected"<?php } ?>><?php _e('Custom Field Value', 'wa_chat'); ?></option>
            <option value="CITY_AREA" <?php if($use_phone == 'CITY_AREA') { ?>selected="selected"<?php } ?>><?php _e('City Area', 'wa_chat'); ?></option>
            <option value="THEME_FIELD" <?php if($use_phone == 'THEME_FIELD') { ?>selected="selected"<?php } ?>><?php _e('Theme Phone Field', 'wa_chat'); ?></option>
            <option value="TELEPHONE_PLUGIN" <?php if($use_phone == 'TELEPHONE_PLUGIN') { ?>selected="selected"<?php } ?>><?php _e('Telephone Plugin', 'wa_chat'); ?></option>
          </select>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Select where from where plugin should use phone number. Custom field - you must define custom field slug to be used in setting bellow. City area - some themes (like Zara) use city area to store phone number. Theme Phone Field - themes like Veronika, Stela, Alpha - Delta use own tables to store phone number.', 'wa_chat'); ?></div>
          </div>
        </div>
        
        <div class="mb-row">
          <label for="custom_field_slug"><span><?php _e('Custom Field Slug', 'wa_chat'); ?></span></label> 
          <input type="text" name="custom_field_slug" id="custom_field_slug" value="<?php echo osc_esc_html($custom_field_slug); ?>" size="40"/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Enter slug/identifier of custom field that is used to retrieve phone number. This field has no effect, if "Custom Field Value" is not selected in "Use Phone from Field" setting.', 'wa_chat'); ?></div>
          </div>
        </div>
        
        <div class="mb-row">
          <label for="sanitize_phone"><span><?php _e('Sanitize Phone Number', 'wa_chat'); ?></span></label> 
          <input name="sanitize_phone" id="sanitize_phone" type="checkbox" class="element-slide" <?php echo ($sanitize_phone == 1 ? 'checked' : ''); ?>/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('When enabled, plugin will sanitize phone number and will try to fix leading zeros, plus sign, country code and remove unwanted characters from phone number.', 'wa_chat'); ?></div>
          </div>
        </div>
        
        <div class="mb-row">
          <label for="default_country_code"><span><?php _e('Default Phone Country Code', 'wa_chat'); ?></span></label> 
          <input type="text" name="default_country_code" id="default_country_code" value="<?php echo osc_esc_html($default_country_code); ?>" size="20"/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Enter default phone country code to be added into phone number if it does not meet minimum length setting. This setting has no effect when "Sanitize Phone Number" is disabled. Examples: +421, +1, +92, ...', 'wa_chat'); ?></div>
          </div>
        </div>
        
        <div class="mb-row">
          <label for="phone_length"><span><?php _e('Minimum Phone Length', 'wa_chat'); ?></span></label> 
          <input type="text" name="phone_length" id="phone_length" value="<?php echo osc_esc_html($phone_length); ?>" size="20"/>
          <div class="mb-input-desc"><?php _e('chars', 'wa_chat'); ?></div>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Enter minimum phone length that is expected. This setting has no effect when "Sanitize Phone Number" is disabled.', 'wa_chat'); ?></div>
          </div>
        </div>

        <div class="mb-row mb-row-select-multiple">
          <label for="category_multiple"><span><?php _e('Category Restriction', 'wa_chat'); ?></span></label> 

          <input type="hidden" name="category" id="category" value="<?php echo $category; ?>"/>
          <select id="category_multiple" name="category_multiple" multiple>
            <?php echo wac_cat_list($category_array, $category_all); ?>
          </select>

          <div class="mb-explain">
            <div class="mb-line"><?php _e('If no category selected, button will be shown in all categories.', 'wa_chat'); ?></div>
          </div>
        </div>

        <div class="mb-row">
          <label for="hooks"><span><?php _e('Hook Button', 'wa_chat'); ?></span></label> 
          <input name="hooks" id="hooks" type="text" size="60" value="<?php echo osc_esc_html($hooks); ?>"/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Enter hooks where to add whatsapp button. Delimit by comma. No white spaces. Default: item_detail', 'wa_chat'); ?></div>
          </div>
        </div>


        <div class="mb-subtitle"><?php _e('Button Customization', 'wa_chat'); ?></div>

        <div class="mb-row">
          <label for="style"><span><?php _e('Button Style/Type', 'wa_chat'); ?></span></label> 
          <select name="style" id="style">
            <option value="BUTTON_ROUNDED" <?php if($style == '' || $style == 'BUTTON_ROUNDED') { ?>selected="selected"<?php } ?>><?php _e('Rounded button', 'wa_chat'); ?></option>
            <option value="BUTTON_SQUARE" <?php if($style == 'BUTTON_SQUARE') { ?>selected="selected"<?php } ?>><?php _e('Square button', 'wa_chat'); ?></option>
            <option value="BUTTON_BUBBLE" <?php if($style == 'BUTTON_BUBBLE') { ?>selected="selected"<?php } ?>><?php _e('Bubble button', 'wa_chat'); ?></option>
            <option value="BUTTON_ICON_ROUNDED" <?php if($style == 'BUTTON_ICON_ROUNDED') { ?>selected="selected"<?php } ?>><?php _e('Rounded icon button', 'wa_chat'); ?></option>
            <option value="BUTTON_ICON_SQUARE" <?php if($style == 'BUTTON_ICON_SQUARE') { ?>selected="selected"<?php } ?>><?php _e('Square icon button', 'wa_chat'); ?></option>
            <option value="BUTTON_ICON_CIRCLE" <?php if($style == 'BUTTON_ICON_CIRCLE') { ?>selected="selected"<?php } ?>><?php _e('Circle icon button', 'wa_chat'); ?></option>
          </select>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Select button style/type', 'wa_chat'); ?></div>
          </div>
        </div>
        
        <div class="mb-row">
          <label for="size"><span><?php _e('Button Size', 'wa_chat'); ?></span></label> 
          <?php if(1==2) { ?>
          <select name="size" id="size">
            <option value="MICRO" <?php if($size == 'MICRO') { ?>selected="selected"<?php } ?>><?php _e('Very small', 'wa_chat'); ?></option>
            <option value="MINI" <?php if($size == 'MINI') { ?>selected="selected"<?php } ?>><?php _e('Small', 'wa_chat'); ?></option>
            <option value="MEDIUM" <?php if($size == '' || $size == 'MEDIUM') { ?>selected="selected"<?php } ?>><?php _e('Medium', 'wa_chat'); ?></option>
            <option value="LARGE" <?php if($size == 'LARGE') { ?>selected="selected"<?php } ?>><?php _e('Large', 'wa_chat'); ?></option>
            <option value="EXTRA" <?php if($size == 'EXTRA') { ?>selected="selected"<?php } ?>><?php _e('Very large', 'wa_chat'); ?></option>
          </select>
          <?php } ?>
          
          <div class="mb-sizes-list">
            <label class="mb-elem">
              <strong>
                <input type="radio" name="size" value="MICRO" <?php if($size == 'MICRO') { ?>checked<?php } ?>/>
                <?php echo sprintf(__('Button size %s', 'wa_chat'), __('Very small', 'wa_chat')); ?>
              </strong>
              
              <div class="mb-bat mb-micro">XX XXXX XXXXX</div>
            </label>
            
            <label class="mb-elem">
              <strong>
                <input type="radio" name="size" value="MINI" <?php if($size == 'MINI') { ?>checked<?php } ?>/>
                <?php echo sprintf(__('Button size %s', 'wa_chat'), __('Small', 'wa_chat')); ?>
              </strong>
              
              <div class="mb-bat mb-mini">XX XXXX XXXXX</div>
            </label>
            
            <label class="mb-elem">
              <strong>
                <input type="radio" name="size" value="MEDIUM" <?php if($size == 'MEDIUM') { ?>checked<?php } ?>/>
                <?php echo sprintf(__('Button size %s', 'wa_chat'), __('Medium', 'wa_chat')); ?>
              </strong>
              
              <div class="mb-bat mb-medium">XX XXXX XXXXX</div>
            </label>
            
            <label class="mb-elem">
              <strong>
                <input type="radio" name="size" value="LARGE" <?php if($size == 'LARGE') { ?>checked<?php } ?>/>
                <?php echo sprintf(__('Button size %s', 'wa_chat'), __('Large', 'wa_chat')); ?>
              </strong>
              
              <div class="mb-bat mb-large">XX XXXX XXXXX</div>
            </label>
            
            <label class="mb-elem">
              <strong>
                <input type="radio" name="size" value="EXTRA" <?php if($size == 'EXTRA') { ?>checked<?php } ?>/>
                <?php echo sprintf(__('Button size %s', 'wa_chat'), __('Very large', 'wa_chat')); ?>
              </strong>
              
              <div class="mb-bat mb-extra">XX XXXX XXXXX</div>
            </label>
          </div>
        </div>

        <div class="mb-row">
          <label for="icon"><span><?php _e('Icon Image', 'wa_chat'); ?></span></label> 
          
          <?php if(1==2) { ?>
          <select name="icon" id="icon">
            <?php for($i = 1; $i <= 13; $i++) { ?>
              <option value="<?php echo $i; ?>" <?php if($icon == $i) { ?>selected="selected"<?php } ?>><?php echo sprintf(__('Icon image #%d', 'wa_chat'), $i); ?></option>
            <?php } ?>          
          </select>
          <?php } ?>
          
          <div class="mb-icons-list">
            <?php for($i = 1; $i <= 13; $i++) { ?>
              <label class="mb-elem">
                <strong>
                  <input type="radio" name="icon" value="<?php echo $i; ?>" <?php if($icon == $i) { ?>checked<?php } ?>/>
                  <?php echo sprintf(__('Icon image #%d', 'wa_chat'), $i); ?>
                </strong>
                
                <img src="<?php echo osc_base_url() . 'oc-content/plugins/wa_chat/img/' . $i . '.svg'; ?>" width=32 height=32/>
              </label>
            <?php } ?>
          </div>
        </div>
        
        <div class="mb-row"> 
          <label for="button_text"><span><?php _e('Custom Text in Button', 'wa_chat'); ?></span></label> 
          <input type="text" name="button_text" id="button_text" value="<?php echo osc_esc_html($button_text); ?>" size="40"/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Enter own text to shown in button. If no text is entered, default text is used.', 'wa_chat'); ?></div>
          </div>
        </div>

        <div class="mb-row"> 
          <label for="button_padding"><span><?php _e('Button Padding', 'wa_chat'); ?></span></label> 
          <input type="text" name="button_padding" id="button_padding" value="<?php echo osc_esc_html($button_padding); ?>" size="20"/>
          <div class="mb-input-desc">px</div>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Enter padding for button.', 'wa_chat'); ?></div>
          </div>
        </div>

        <div class="mb-row"> 
          <label for="icon_padding"><span><?php _e('Icon Image Padding', 'wa_chat'); ?></span></label> 
          <input type="text" name="icon_padding" id="icon_padding" value="<?php echo osc_esc_html($icon_padding); ?>" size="20"/>
          <div class="mb-input-desc">px</div>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Enter padding for icon image.', 'wa_chat'); ?></div>
          </div>
        </div>

        <div class="mb-row mb-color-box">
          <label for="button_bg_color"><span><?php _e('Button Background Color', 'wa_chat'); ?></span></label> 
      
          <input name="button_bg_color" id="button_bg_color" size="20" type="text" value="<?php echo osc_esc_html($button_bg_color); ?>" />
          <span class="color-wrap">
            <input name="color-picker" id="" type="color" value="<?php echo osc_esc_html($button_bg_color); ?>" />
          </span>
          <div class="mb-explain"><?php _e('Enter color in HEX format or select color with picker. Example: #0d9ecc. Keep blank to use default color.', 'wa_chat'); ?></div>
        </div>
        
        <div class="mb-row mb-color-box">
          <label for="button_font_color"><span><?php _e('Button Font Color', 'wa_chat'); ?></span></label> 
      
          <input name="button_font_color" id="button_font_color" size="20" type="text" value="<?php echo osc_esc_html($button_font_color); ?>" />
          <span class="color-wrap">
            <input name="color-picker" id="" type="color" value="<?php echo osc_esc_html($button_font_color); ?>" />
          </span>
          <div class="mb-explain"><?php _e('Enter color in HEX format or select color with picker. Example: #333333. Keep blank to use default color.', 'wa_chat'); ?></div>
        </div>
        
        <div class="mb-row mb-color-box">
          <label for="icon_bg_color"><span><?php _e('Icon Background Color', 'wa_chat'); ?></span></label> 
      
          <input name="icon_bg_color" id="icon_bg_color" size="20" type="text" value="<?php echo osc_esc_html($icon_bg_color); ?>" />
          <span class="color-wrap">
            <input name="color-picker" id="" type="color" value="<?php echo osc_esc_html($icon_bg_color); ?>" />
          </span>
          <div class="mb-explain"><?php _e('Enter color in HEX format or select color with picker. Example: #ffffff. Keep blank to use default color.', 'wa_chat'); ?></div>
        </div>

        <div class="mb-row">
          <label for="custom_css"><span><?php _e('Custom CSS for Button', 'wa_chat'); ?></span></label> 
          <textarea name="custom_css" id="custom_css"><?php echo osc_esc_html($custom_css); ?></textarea>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Enter your own CSS code that will be added next to button.', 'wa_chat'); ?></div>
          </div>
        </div>
        

        <div class="mb-row">&nbsp;</div>

        <div class="mb-foot">
          <?php if(wac_is_demo()) { ?>
            <a class="mb-button mb-has-tooltip disabled" onclick="return false;" style="cursor:not-allowed;opacity:0.5;" title="<?php echo osc_esc_html(__('This is demo site', 'wa_chat')); ?>"><?php _e('Save', 'wa_chat');?></a>
          <?php } else { ?>
            <button type="submit" class="mb-button"><?php _e('Save', 'wa_chat');?></button>
          <?php } ?>
        </div>
      </div>
    </div>


    <!-- WEB BUTTON SETTINGS -->
    <div class="mb-box">
      <div class="mb-head">
        <i class="fa fa-wrench"></i> <?php _e('Configure Web Contact Button', 'wa_chat'); ?>
      </div>

      <div class="mb-inside">
        <div class="mb-row">
          <label for="web_enable"><span><?php _e('Enable Web Contact Button', 'wa_chat'); ?></span></label> 
          <input name="web_enable" id="web_enable" type="checkbox" class="element-slide" <?php echo ($web_enable == 1 ? 'checked' : ''); ?>/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('When enabled, WhatsApp contact button on website will be created.', 'wa_chat'); ?></div>
          </div>
        </div>

        <div class="mb-row">
          <label for="web_only_logged"><span><?php _e('Only for Logged-in Users', 'wa_chat'); ?></span></label> 
          <input name="web_only_logged" id="web_only_logged" type="checkbox" class="element-slide" <?php echo ($web_only_logged == 1 ? 'checked' : ''); ?>/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('When enabled, only logged-in users can use contact button.', 'wa_chat'); ?></div>
          </div>
        </div>

        <div class="mb-row">
          <label for="web_phone"><span><?php _e('Contact Phone', 'wa_chat'); ?></span></label> 
          <input type="text" name="web_phone" id="web_phone" value="<?php echo osc_esc_html($web_phone); ?>" size="40"/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Enter site phone number in international format without using ()/- signs and white spaces. Example: +4201223321321.', 'wa_chat'); ?></div>
          </div>
        </div>

        <div class="mb-row">
          <label for="web_phone_in_button"><span><?php _e('Add Phone Number to Button', 'wa_chat'); ?></span></label> 
          <select name="web_phone_in_button" id="web_phone_in_button">
            <option value="" <?php if($web_phone_in_button == '') { ?>selected="selected"<?php } ?>><?php _e('Do not add', 'wa_chat'); ?></option>
            <option value="SAME_LINE" <?php if($web_phone_in_button == 'SAME_LINE') { ?>selected="selected"<?php } ?>><?php _e('Add to same line', 'wa_chat'); ?></option>
            <option value="NEW_LINE" <?php if($web_phone_in_button == 'NEW_LINE') { ?>selected="selected"<?php } ?>><?php _e('Add on new line', 'wa_chat'); ?></option>
          </select>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Select if you want to add phone number to WhatsApp button, and if yes if it should be on same line as is text, or into new line.', 'wa_chat'); ?></div>
            <div class="mb-line"><?php _e('"Add on new line" option is recommended only for buttons of size ', 'wa_chat'); ?></div>
          </div>
        </div>
        
        <div class="mb-row">
          <label for="web_position"><span><?php _e('Position of Button', 'wa_chat'); ?></span></label> 
          <select name="web_position" id="web_position">
            <option value="bottom-left" <?php if($web_position == '' || $web_position == 'bottom-left') { ?>selected="selected"<?php } ?>><?php _e('Bottom left (Box)', 'wa_chat'); ?></option>
            <option value="bottom-right" <?php if($web_position == 'bottom-right') { ?>selected="selected"<?php } ?>><?php _e('Bottom right (Box)', 'wa_chat'); ?></option>
            <option value="top-left" <?php if($web_position == 'top-left') { ?>selected="selected"<?php } ?>><?php _e('Top left (Box)', 'wa_chat'); ?></option>
            <option value="top-right" <?php if($web_position == 'top-right') { ?>selected="selected"<?php } ?>><?php _e('Top right (Box)', 'wa_chat'); ?></option>
          </select>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Select where contact button will be displayed.', 'wa_chat'); ?></div>
          </div>
        </div>
        
        <div class="mb-row">
          <label for="web_margin"><span><?php _e('Margin from Borders', 'wa_chat'); ?></span></label> 
          <input type="number" name="web_margin" id="web_margin" value="<?php echo osc_esc_html($web_margin); ?>" size="20"/>
          <div class="mb-input-desc">px</div>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Enter margin of button from browser borders.', 'wa_chat'); ?></div>
          </div>
        </div>
        
        <div class="mb-row">
          <label for="web_hook"><span><?php _e('Auto-Hook Button', 'wa_chat'); ?></span></label> 
          <input name="web_hook" id="web_hook" type="checkbox" class="element-slide" <?php echo ($web_hook == 1 ? 'checked' : ''); ?>/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('When enabled, button is automatically added to item page using footer hook.', 'wa_chat'); ?></div>
          </div>
        </div>


        <div class="mb-subtitle"><?php _e('Button Customization', 'wa_chat'); ?></div>

        <div class="mb-row">
          <label for="web_style"><span><?php _e('Button Style/Type', 'wa_chat'); ?></span></label> 
          <select name="web_style" id="web_style">
            <option value="BUTTON_ROUNDED" <?php if($web_style == '' || $web_style == 'BUTTON_ROUNDED') { ?>selected="selected"<?php } ?>><?php _e('Rounded button', 'wa_chat'); ?></option>
            <option value="BUTTON_SQUARE" <?php if($web_style == 'BUTTON_SQUARE') { ?>selected="selected"<?php } ?>><?php _e('Square button', 'wa_chat'); ?></option>
            <option value="BUTTON_BUBBLE" <?php if($web_style == 'BUTTON_BUBBLE') { ?>selected="selected"<?php } ?>><?php _e('Bubble button', 'wa_chat'); ?></option>
            <option value="BUTTON_ICON_ROUNDED" <?php if($web_style == 'BUTTON_ICON_ROUNDED') { ?>selected="selected"<?php } ?>><?php _e('Rounded icon button', 'wa_chat'); ?></option>
            <option value="BUTTON_ICON_SQUARE" <?php if($web_style == 'BUTTON_ICON_SQUARE') { ?>selected="selected"<?php } ?>><?php _e('Square icon button', 'wa_chat'); ?></option>
            <option value="BUTTON_ICON_CIRCLE" <?php if($web_style == 'BUTTON_ICON_CIRCLE') { ?>selected="selected"<?php } ?>><?php _e('Circle icon button', 'wa_chat'); ?></option>
          </select>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Select button style/type', 'wa_chat'); ?></div>
          </div>
        </div>
        
        <div class="mb-row">
          <label for="web_size"><span><?php _e('Button Size', 'wa_chat'); ?></span></label> 
          
          <div class="mb-sizes-list">
            <label class="mb-elem">
              <strong>
                <input type="radio" name="web_size" value="MICRO" <?php if($web_size == 'MICRO') { ?>checked<?php } ?>/>
                <?php echo sprintf(__('Button size %s', 'wa_chat'), __('Very small', 'wa_chat')); ?>
              </strong>
              
              <div class="mb-bat mb-micro">XX XXXX XXXXX</div>
            </label>
            
            <label class="mb-elem">
              <strong>
                <input type="radio" name="web_size" value="MINI" <?php if($web_size == 'MINI') { ?>checked<?php } ?>/>
                <?php echo sprintf(__('Button size %s', 'wa_chat'), __('Small', 'wa_chat')); ?>
              </strong>
              
              <div class="mb-bat mb-mini">XX XXXX XXXXX</div>
            </label>
            
            <label class="mb-elem">
              <strong>
                <input type="radio" name="web_size" value="MEDIUM" <?php if($web_size == 'MEDIUM') { ?>checked<?php } ?>/>
                <?php echo sprintf(__('Button size %s', 'wa_chat'), __('Medium', 'wa_chat')); ?>
              </strong>
              
              <div class="mb-bat mb-medium">XX XXXX XXXXX</div>
            </label>
            
            <label class="mb-elem">
              <strong>
                <input type="radio" name="web_size" value="LARGE" <?php if($web_size == 'LARGE') { ?>checked<?php } ?>/>
                <?php echo sprintf(__('Button size %s', 'wa_chat'), __('Large', 'wa_chat')); ?>
              </strong>
              
              <div class="mb-bat mb-large">XX XXXX XXXXX</div>
            </label>
            
            <label class="mb-elem">
              <strong>
                <input type="radio" name="web_size" value="EXTRA" <?php if($web_size == 'EXTRA') { ?>checked<?php } ?>/>
                <?php echo sprintf(__('Button size %s', 'wa_chat'), __('Very large', 'wa_chat')); ?>
              </strong>
              
              <div class="mb-bat mb-extra">XX XXXX XXXXX</div>
            </label>
          </div>
        </div>

        <div class="mb-row">
          <label for="web_icon"><span><?php _e('Icon Image', 'wa_chat'); ?></span></label> 
          
          <div class="mb-icons-list">
            <?php for($i = 1; $i <= 13; $i++) { ?>
              <label class="mb-elem">
                <strong>
                  <input type="radio" name="web_icon" value="<?php echo $i; ?>" <?php if($web_icon == $i) { ?>checked<?php } ?>/>
                  <?php echo sprintf(__('Icon image #%d', 'wa_chat'), $i); ?>
                </strong>
                
                <img src="<?php echo osc_base_url() . 'oc-content/plugins/wa_chat/img/' . $i . '.svg'; ?>" width=32 height=32/>
              </label>
            <?php } ?>
          </div>
        </div>
        
        <div class="mb-row"> 
          <label for="web_button_text"><span><?php _e('Custom Text in Button', 'wa_chat'); ?></span></label> 
          <input type="text" name="web_button_text" id="web_button_text" value="<?php echo osc_esc_html($web_button_text); ?>" size="40"/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Enter own text to shown in button. If no text is entered, default text is used.', 'wa_chat'); ?></div>
          </div>
        </div>

        <div class="mb-row"> 
          <label for="web_button_padding"><span><?php _e('Button Padding', 'wa_chat'); ?></span></label> 
          <input type="text" name="web_button_padding" id="web_button_padding" value="<?php echo osc_esc_html($web_button_padding); ?>" size="20"/>
          <div class="mb-input-desc">px</div>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Enter padding for button.', 'wa_chat'); ?></div>
          </div>
        </div>

        <div class="mb-row"> 
          <label for="web_icon_padding"><span><?php _e('Icon Image Padding', 'wa_chat'); ?></span></label> 
          <input type="text" name="web_icon_padding" id="web_icon_padding" value="<?php echo osc_esc_html($web_icon_padding); ?>" size="20"/>
          <div class="mb-input-desc">px</div>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Enter padding for icon image.', 'wa_chat'); ?></div>
          </div>
        </div>

        <div class="mb-row mb-color-box">
          <label for="web_button_bg_color"><span><?php _e('Button Background Color', 'wa_chat'); ?></span></label> 
      
          <input name="web_button_bg_color" id="web_button_bg_color" size="20" type="text" value="<?php echo osc_esc_html($web_button_bg_color); ?>" />
          <span class="color-wrap">
            <input name="color-picker" id="" type="color" value="<?php echo osc_esc_html($web_button_bg_color); ?>" />
          </span>
          <div class="mb-explain"><?php _e('Enter color in HEX format or select color with picker. Example: #0d9ecc. Keep blank to use default color.', 'wa_chat'); ?></div>
        </div>
        
        <div class="mb-row mb-color-box">
          <label for="web_button_font_color"><span><?php _e('Button Font Color', 'wa_chat'); ?></span></label> 
      
          <input name="web_button_font_color" id="web_button_font_color" size="20" type="text" value="<?php echo osc_esc_html($web_button_font_color); ?>" />
          <span class="color-wrap">
            <input name="color-picker" id="" type="color" value="<?php echo osc_esc_html($web_button_font_color); ?>" />
          </span>
          <div class="mb-explain"><?php _e('Enter color in HEX format or select color with picker. Example: #333333. Keep blank to use default color.', 'wa_chat'); ?></div>
        </div>
        
        <div class="mb-row mb-color-box">
          <label for="web_icon_bg_color"><span><?php _e('Icon Background Color', 'wa_chat'); ?></span></label> 
      
          <input name="web_icon_bg_color" id="web_icon_bg_color" size="20" type="text" value="<?php echo osc_esc_html($web_icon_bg_color); ?>" />
          <span class="color-wrap">
            <input name="color-picker" id="" type="color" value="<?php echo osc_esc_html($web_icon_bg_color); ?>" />
          </span>
          <div class="mb-explain"><?php _e('Enter color in HEX format or select color with picker. Example: #ffffff. Keep blank to use default color.', 'wa_chat'); ?></div>
        </div>

        <div class="mb-row">
          <label for="web_custom_css"><span><?php _e('Custom CSS for Button', 'wa_chat'); ?></span></label> 
          <textarea name="web_custom_css" id="web_custom_css"><?php echo osc_esc_html($web_custom_css); ?></textarea>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Enter your own CSS code that will be added next to button.', 'wa_chat'); ?></div>
          </div>
        </div>
        

        <div class="mb-row">&nbsp;</div>

        <div class="mb-foot">
          <?php if(wac_is_demo()) { ?>
            <a class="mb-button mb-has-tooltip disabled" onclick="return false;" style="cursor:not-allowed;opacity:0.5;" title="<?php echo osc_esc_html(__('This is demo site', 'wa_chat')); ?>"><?php _e('Save', 'wa_chat');?></a>
          <?php } else { ?>
            <button type="submit" class="mb-button"><?php _e('Save', 'wa_chat');?></button>
          <?php } ?>
        </div>
      </div>
    </div>

  </form>


  <!-- PLUGIN INTEGRATION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-wrench"></i> <?php _e('Plugin Setup', 'wa_chat'); ?></div>

    <div class="mb-inside">
      <div class="mb-row"><?php _e('No theme modification are required to use all functions of plugin, but following functions may be useful to use plugin in customized way.', 'wa_chat'); ?></div>

      <div class="mb-row">
        <strong><?php _e('Web Chat Button', 'wa_chat'); ?></strong>
        <span class="mb-code">&lt;?php if(function_exists('wac_web_chat_button')) { wac_web_chat_button(); } ?&gt;</span>
      </div>
      
      <div class="mb-row">
        <strong><?php _e('Item Chat Button', 'wa_chat'); ?></strong>
        <span class="mb-code">&lt;?php if(function_exists('wac_item_chat_button')) { wac_item_chat_button($item_id = NULL); } ?&gt;</span>
      </div>
      
      <div class="mb-row">
        <strong><?php _e('Item Chat Button views count', 'wa_chat'); ?></strong>
        <span class="mb-code">&lt;?php if(function_exists('wac_get_views')) { echo wac_get_views($item_id); } ?&gt;</span>
      </div>

      <div class="mb-row">
        <strong><?php _e('Item Chat Button clicks count', 'wa_chat'); ?></strong>
        <span class="mb-code">&lt;?php if(function_exists('wac_get_clicks')) { echo wac_get_clicks($item_id); } ?&gt;</span>
      </div>
      
    </div>
  </div>
</div>


<?php echo wac_footer(); ?>