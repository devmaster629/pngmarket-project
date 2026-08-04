<?php
  // Create menu
  $title = __('Configure', 'instant_messenger');
  im_menu($title);


  // GET & UPDATE PARAMETERS
  // $variable = mb_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check or value
  $limit_enabled = mb_param_update('limit_enabled', 'plugin_action', 'check', 'plugin-instant_messenger');
  $limit_max_messages = mb_param_update('limit_max_messages', 'plugin_action', 'value', 'plugin-instant_messenger');
  $limit_max_users = mb_param_update('limit_max_users', 'plugin_action', 'value', 'plugin-instant_messenger');
  $limit_period_hours = mb_param_update('limit_period_hours', 'plugin_action', 'value', 'plugin-instant_messenger');
  $limit_disable_after_messages = mb_param_update('limit_disable_after_messages', 'plugin_action', 'value', 'plugin-instant_messenger');
  $limit_disable_after_users = mb_param_update('limit_disable_after_users', 'plugin_action', 'value', 'plugin-instant_messenger');
  $limit_disable_after_days_from_reg = mb_param_update('limit_disable_after_days_from_reg', 'plugin_action', 'value', 'plugin-instant_messenger');

  $button_hooks = mb_param_update('button_hooks', 'plugin_action', 'value', 'plugin-instant_messenger');
  $button_hooks_user = mb_param_update('button_hooks_user', 'plugin_action', 'value', 'plugin-instant_messenger');
  $one_thread_per_user = mb_param_update('one_thread_per_user', 'plugin_action', 'check', 'plugin-instant_messenger');
  $hook_header_links = mb_param_update('hook_header_links', 'plugin_action', 'check', 'plugin-instant_messenger');
  $contact_seller = mb_param_update('contact_seller', 'plugin_action', 'check', 'plugin-instant_messenger');
  $notify_once = mb_param_update('notify_once', 'plugin_action', 'check', 'plugin-instant_messenger');
  $email_deferred = mb_param_update('email_deferred', 'plugin_action', 'check', 'plugin-instant_messenger');
  $email_deferred_minutes = mb_param_update('email_deferred_minutes', 'plugin_action', 'value', 'plugin-instant_messenger');
  $att_enable = mb_param_update('att_enable', 'plugin_action', 'check', 'plugin-instant_messenger');
  $att_max_size = mb_param_update('att_max_size', 'plugin_action', 'value', 'plugin-instant_messenger');
  $att_extension = mb_param_update('att_extension', 'plugin_action', 'value', 'plugin-instant_messenger');
  $message_delete = mb_param_update('message_delete', 'plugin_action', 'check', 'plugin-instant_messenger');
  $threads_per_page = mb_param_update('threads_per_page', 'plugin_action', 'value', 'plugin-instant_messenger');
  $link_reg_only = mb_param_update('link_reg_only', 'plugin_action', 'check', 'plugin-instant_messenger');
  $only_logged = mb_param_update('only_logged', 'plugin_action', 'check', 'plugin-instant_messenger');
  $remove_thread = mb_param_update('remove_thread', 'plugin_action', 'check', 'plugin-instant_messenger');
  $generate_avatars = mb_param_update('generate_avatars', 'plugin_action', 'check', 'plugin-instant_messenger');
  $autogenerate_title = mb_param_update('autogenerate_title', 'plugin_action', 'check', 'plugin-instant_messenger');

  $ajax = mb_param_update('ajax', 'plugin_action', 'check', 'plugin-instant_messenger');
  $interval = mb_param_update('interval', 'plugin_action', 'value', 'plugin-instant_messenger');


  if(Params::getParam('plugin_action') == 'done') {
    message_ok( __('Settings were successfully saved', 'instant_messenger') );
  }
?>

<div class="mb-body">
  <!-- CONFIGURE SECTION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-cog"></i> <?php _e('Configure', 'instant_messenger'); ?></div>

    <div class="mb-inside">
      <form name="promo_form" id="promo_form" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
        <?php if(!im_is_demo()) { ?>
        <input type="hidden" name="page" value="plugins" />
        <input type="hidden" name="action" value="renderplugin" />
        <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>configure.php" />
        <input type="hidden" name="plugin_action" value="done" />
        <?php } ?>
        
        <div class="mb-row">
          <label for="contact_seller" class="h1"><span><?php _e('Replace Contact Seller Functionality', 'instant_messenger'); ?></span></label> 
          <input name="contact_seller" id="contact_seller" class="element-slide" type="checkbox" <?php echo ($contact_seller == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('Contact seller functionality will be replaced with instant messages.', 'instant_messenger'); ?></div>
        </div>

        <div class="mb-row">
          <label for="link_reg_only"><span><?php _e('Show Header Link to Registered Only', 'instant_messenger'); ?></span></label> 
          <input name="link_reg_only" id="link_reg_only" class="element-slide" type="checkbox" <?php echo ($link_reg_only == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('When enabled, link in header to show messages will be shown to logged in users only. <br/>Link is shown via code', 'instant_messenger'); ?> &lt;?php if(function_exists('im_messages')) { echo im_messages(); } ?&gt;</div>
        </div>

        <div class="mb-row">
          <label for="autogenerate_title"><span><?php _e('Generate Conversation Title', 'instant_messenger'); ?></span></label> 
          <input name="autogenerate_title" id="autogenerate_title" class="element-slide" type="checkbox" <?php echo ($autogenerate_title == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('When enabled, conversation title is not shown to user when creating thread. Instead, plugin generate conversation thread based on item title.', 'instant_messenger'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="generate_avatars"><span><?php _e('Generate Avatars', 'instant_messenger'); ?></span></label> 
          <input name="generate_avatars" id="generate_avatars" class="element-slide" type="checkbox" <?php echo ($generate_avatars == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('When user has no avatar uploaded, generate avatar out of user name. If disabled, default avatar picture is used for all users without profile picture.', 'instant_messenger'); ?> &lt;?php if(function_exists('im_messages')) { echo im_messages(); } ?&gt;</div>
        </div>

        <div class="mb-row">
          <label for="notify_once" class="h2"><span><?php _e('Notify User only Once', 'instant_messenger'); ?></span></label> 
          <input name="notify_once" id="notify_once" class="element-slide" type="checkbox" <?php echo ($notify_once == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('User will not be notified about new messages only if previous messages in same conversation has been read.', 'instant_messenger'); ?></div>
        </div>


        <div class="mb-row">
          <label for="email_deferred" class="h2"><span><?php _e('Deferred Email Notifications', 'instant_messenger'); ?></span></label> 
          <input name="email_deferred" id="email_deferred" class="element-slide" type="checkbox" <?php echo ($email_deferred == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain">
            <div class="mb-line"><?php _e('Queue notification emails and send them via cron_minutely. One email per thread is sent after user stops messaging for configured delay. Requires Osclass cron to be configured on hosting.', 'instant_messenger'); ?></div>
            <div class="mb-line"><a href="https://docs.osclass-classifieds.com/cron-setup-i82" target="_blank" rel="noopener noreferrer"><?php _e('Osclass cron setup guide', 'instant_messenger'); ?></a></div>
          </div>
        </div>

        <div class="mb-row">
          <label for="email_deferred_minutes" class="h2"><span><?php _e('Deferred Email Delay', 'instant_messenger'); ?></span></label> 
          <input size="6" name="email_deferred_minutes" id="email_deferred_minutes" class="mb-short" type="text" value="<?php echo ($email_deferred_minutes <> '' ? (int)$email_deferred_minutes : 5); ?>" />
          <div class="mb-input-desc"><?php _e('minutes', 'instant_messenger'); ?></div>

          <div class="mb-explain"><?php _e('Wait this many minutes after the last unread message in thread before sending notification email. Default: 5 minutes.', 'instant_messenger'); ?></div>
        </div>
        <div class="mb-row">
          <label for="att_enable" class="h4"><span><?php _e('Enable Attachments in Messages', 'instant_messenger'); ?></span></label> 
          <input name="att_enable" id="att_enable" class="element-slide" type="checkbox" <?php echo ($att_enable == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('Users will be able to upload attachment when sending message.', 'instant_messenger'); ?></div>
        </div>

        <div class="mb-row">
          <label for="att_max_size" class="h5"><span><?php _e('Attachment Maximum Size', 'instant_messenger'); ?></span></label> 
          <input size="6" name="att_max_size" id="att_max_size" class="mb-short" type="text" value="<?php echo $att_max_size; ?>" />
          <div class="mb-input-desc"><?php _e('kb', 'instant_messenger'); ?></div>

          <div class="mb-explain"><?php _e('When attachment is larger, it will not be sent.', 'instant_messenger'); ?></div>
        </div>

        <div class="mb-row">
          <label for="att_extension" class="h6"><span><?php _e('Allowed File Extensions in Attachment', 'instant_messenger'); ?></span></label> 
          <input size="100" name="att_extension" id="att_extension" type="text" value="<?php echo $att_extension; ?>" />

          <div class="mb-explain"><?php _e('Delimit extensions with comma. Example: png, jpg, gif', 'instant_messenger'); ?></div>
        </div>

        <div class="mb-row">
          <label for="message_delete" class="h7"><span><?php _e('Allow Users to Remove Messages', 'instant_messenger'); ?></span></label> 
          <input name="message_delete" id="message_delete" class="element-slide" type="checkbox" <?php echo ($message_delete == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('Users will be able to remove their own messages from conversation, but still will not be able to remove whole conversation.', 'instant_messenger'); ?></div>
        </div>

        <div class="mb-row">
          <label for="threads_per_page" class="h8"><span><?php _e('Threads per Page', 'instant_messenger'); ?></span></label> 
          <input size="6" name="threads_per_page" id="att_max_size" class="mb-short" type="text" value="<?php echo $threads_per_page; ?>" />
          <div class="mb-input-desc"><?php _e('threads', 'instant_messenger'); ?></div>

          <div class="mb-explain"><?php _e('Set how many threads are shown on 1 page in user profile (pagination).', 'instant_messenger'); ?></div>
        </div>

        <div class="mb-row">
          <label for="only_logged" class="h9"><span><?php _e('Login Required', 'instant_messenger'); ?></span></label> 
          <input name="only_logged" id="only_logged" class="element-slide" type="checkbox" <?php echo ($only_logged == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Login is required for users to use messenger functionality - send messages.', 'instant_messenger'); ?></div>
        </div>

        <div class="mb-row">
          <label for="ajax" class="h10"><span><?php _e('Enable auto-refresh of messages', 'instant_messenger'); ?></span></label> 
          <input name="ajax" id="ajax" class="element-slide" type="checkbox" <?php echo ($ajax == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('When enabled, messages in thread are refreshed without need of reload page.', 'instant_messenger'); ?></div>
        </div>

        <div class="mb-row">
          <label for="interval" class="h11"><span><?php _e('Ajax refresh frequency', 'instant_messenger'); ?></span></label> 
          <input size="6" name="interval" id="interval" class="mb-short" type="text" value="<?php echo $interval; ?>" />
          <div class="mb-input-desc"><?php _e('miliseconds', 'instant_messenger'); ?></div>

          <div class="mb-explain"><?php _e('Frequency of message refresh using ajax, not recommended to go under 1000 miliseconds, ideal should be 3000 - 6000 miliseconds (once per 3-6 seconds).', 'instant_messenger'); ?></div>
        </div>

        <div class="mb-row">
          <label for="remove_thread"><span><?php _e('User can Remove Thread', 'instant_messenger'); ?></span></label> 
          <input name="remove_thread" id="remove_thread" class="element-slide" type="checkbox" <?php echo ($remove_thread == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('When enabled, users can remove their threads. Thread is initially removed only from account of user that wants to remove it. If also second user wants to remove thread, it is completely removed from site.', 'instant_messenger'); ?></div>
        </div>

        <div class="mb-row">
          <label for="hook_header_links"><span><?php _e('Add Button to Header', 'instant_messenger'); ?></span></label> 
          <input name="hook_header_links" id="hook_header_links" class="element-slide" type="checkbox" <?php echo ($hook_header_links == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('When enabled, "Messenger" button/link is added into header. Require Osclass 8.2 theme hooks support.', 'instant_messenger'); ?></div>
        </div>


        <div class="mb-row">
          <label for="one_thread_per_user"><span><?php _e('One Thread Per User', 'instant_messenger'); ?></span></label>
          <input name="one_thread_per_user" id="one_thread_per_user" class="element-slide" type="checkbox" <?php echo ($one_thread_per_user == 1 ? 'checked' : ''); ?> />
          <div class="mb-explain"><?php _e('Only one conversation between two registered users. Reuses existing thread. Item contact for registered sellers uses user route.', 'instant_messenger'); ?></div>
        </div>

        <div class="mb-row">
          <label for="button_hooks" class=""><span><?php _e('Contact item seller — button hooks', 'instant_messenger'); ?></span></label> 
          <input name="button_hooks" id="button_hooks" size="80" type="text" value="<?php echo $button_hooks; ?>" />
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Osclass hooks where the contact seller button is output (listing / item pages). Comma-separated hook names.', 'instant_messenger'); ?></div>
            <div class="mb-line"><?php _e('Uses im_contact_button: recipient is the seller of the current item (item ID). Not for public profile pages.', 'instant_messenger'); ?></div>
            <div class="mb-line"><?php _e('Default: item_contact', 'instant_messenger'); ?></div>
          </div>
        </div>

        <div class="mb-row">
          <label for="button_hooks_user" class=""><span><?php _e('Contact user — button hooks', 'instant_messenger'); ?></span></label> 
          <input name="button_hooks_user" id="button_hooks_user" size="80" type="text" value="<?php echo $button_hooks_user; ?>" />
          <div class="mb-explain">
            <div class="mb-line"><?php _e('Osclass hooks where the contact user button is output (public user profile). Comma-separated hook names.', 'instant_messenger'); ?></div>
            <div class="mb-line"><?php _e('Uses im_contact_user_button: recipient is resolved from the profile user ID (public profile). Not tied to a listing.', 'instant_messenger'); ?></div>
            <div class="mb-line"><?php _e('Default: user_public_profile_sidebar_bottom', 'instant_messenger'); ?></div>
          </div>
        </div>

        <hr/>
        
        
        <div class="mb-row">
          <label for="limit_enabled"><span><?php _e('Enable Messages Limits', 'instant_messenger'); ?></span></label> 
          <input name="limit_enabled" id="limit_enabled" class="element-slide" type="checkbox" <?php echo ($limit_enabled == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('When enabled, plugin will check number of send messages by user in given period. In case limit is reached, user will not be able to send another message until limits are met.', 'instant_messenger'); ?></div>
        </div>


        <div class="mb-row">
          <label for="limit_max_messages"><span><?php _e('Limit User When', 'instant_messenger'); ?></span></label> 
          
          <div class="mb-in-cover">
            <span class="mb-in-txt"><?php _e('User can send max.', 'instant_messenger'); ?></span>
            <input size="4" name="limit_max_messages" class="mb-short" type="number" step=1 value="<?php echo (int)$limit_max_messages; ?>" />

            <span class="mb-in-txt"><?php _e('messages to max.', 'instant_messenger'); ?></span>
            <input size="4" name="limit_max_users" class="mb-short" type="number" step=1 value="<?php echo (int)$limit_max_users; ?>" />

            <span class="mb-in-txt"><?php _e('users in period of', 'instant_messenger'); ?></span>
            <input size="4" name="limit_period_hours" class="mb-short" type="number" step=1 value="<?php echo (int)$limit_period_hours; ?>" />
   
            <span class="mb-in-txt"><?php _e('hours.', 'instant_messenger'); ?></span>
          </div>

          <div class="mb-explain">
            <div class="mb-line"><?php _e('Define limits that helps to protect your site from spammers and abusive usage of messenger.', 'instant_messenger'); ?></div>
          </div>
        </div>

        <div class="mb-row">
          <label for="limit_disable_after_messages"><span><?php _e('Disable Limit When', 'instant_messenger'); ?></span></label> 
          
          <div class="mb-in-cover">
            <span class="mb-in-txt"><?php _e('User already sent at least', 'instant_messenger'); ?></span>
            <input size="4" name="limit_disable_after_messages" class="mb-short" type="number" step=1 value="<?php echo (int)$limit_disable_after_messages; ?>" />

            <span class="mb-in-txt"><?php _e('messages to at least', 'instant_messenger'); ?></span>
            <input size="4" name="limit_disable_after_users" class="mb-short" type="number" step=1 value="<?php echo (int)$limit_disable_after_users; ?>" />

            <span class="mb-in-txt"><?php _e('users and is registered for at least', 'instant_messenger'); ?></span>
            <input size="4" name="limit_disable_after_days_from_reg" class="mb-short" type="number" step=1 value="<?php echo (int)$limit_disable_after_days_from_reg; ?>" />
   
            <span class="mb-in-txt"><?php _e('days.', 'instant_messenger'); ?></span>
          </div>

          <div class="mb-explain">
            <div class="mb-line"><?php _e('When user meets these criteria, it\'s considered as reliable user and limits will not be applied anymore.', 'instant_messenger'); ?></div>
            <div class="mb-line"><?php _e('Note: For unregistered users, condition with registration date is replaced with date of first message sent.', 'instant_messenger'); ?></div>
          </div>
        </div>

      </div>

      <div class="mb-foot">
        <?php if(!im_is_demo()) { ?>
          <button type="submit" class="mb-button"><?php _e('Save', 'instant_messenger');?></button>
        <?php } else { ?>
          <a href="#" onclick="return false" class="mb-button"><?php _e('Save (demo - disabled)', 'instant_messenger');?></button>
        <?php } ?>
      </div>
    </form>
  </div>



  <!-- PLUGIN INTEGRATION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-wrench"></i> <?php _e('Plugin Setup', 'instant_messenger'); ?></div>

    <div class="mb-inside">

      <div class="mb-row">
        <div class="mb-line"><?php _e('To show link to message center and count of unread messages of user, place following link to your theme files', 'instant_messenger'); ?>:</div>
        <span class="mb-code">&lt;?php if(function_exists('im_messages')) { echo im_messages(); } ?&gt;</span>
      </div>

      <div class="mb-row">&nbsp;</div>

      <div class="mb-row">
        <div class="mb-line"><?php _e('To show "Send message" button, place following link to your theme files (item.php, search-list.php, ...)', 'instant_messenger'); ?>:</div>
        <span class="mb-code">&lt;?php if(function_exists('im_contact_button')) { im_contact_button(); } ?&gt;</span>
      </div>

      <div class="mb-row">&nbsp;</div>

      <div class="mb-row">
        <div class="mb-line"><?php _e('Contact registered user on public profile', 'instant_messenger'); ?>:</div>
        <span class="mb-code">&lt;?php if(function_exists('im_contact_user_button')) { im_contact_user_button(); } ?&gt;</span>
      </div>
    </div>
  </div>



  <!-- HELP TOPICS -->
  <div class="mb-box" id="mb-help">
    <div class="mb-head"><i class="fa fa-question-circle"></i> <?php _e('Help', 'instant_messenger'); ?></div>

    <div class="mb-inside">
      <div class="mb-row mb-help"><div><?php _e('Each user can access messages in user account clicking on "Message center".', 'instant_messenger'); ?></div></div>
      <div class="mb-row mb-help"><div><?php _e('Each thread can be flagged by user (for personal preference - as important or not solved yet). Flag is shared between both users in conversation.', 'instant_messenger'); ?></div></div>
      <div class="mb-row mb-help"><div><?php _e('On each conversation can be set if user want to be notified by email or not.', 'instant_messenger'); ?></div></div>

      <div class="mb-row">&nbsp;</div>

      <div class="mb-row mb-help"><span class="sup">(1)</span> <div class="h1"><?php _e('Original "Contact Seller" functionality will be replaced with Instant Messenger plugin. This means that instead of sending mail to seller, message will be sent and seller can see this message in it\'s profile.', 'instant_messenger'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(2)</span> <div class="h2"><?php _e('It is highly recommended to enable this. When disabled, each time there is new message, user will receive email notification, no matter if previous message was read or not. When enabled, user will receive email notification only in case when previous message was read. This avoid sending multiple emails when sending more messages at once. This also avoid spamming your users.', 'instant_messenger'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(3)</span> <div class="h3"><?php _e('Enable to add "Send message" button to listing page via hook. This means you do not need to modify theme files. Button will be added to item_detail hook.', 'instant_messenger'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(4)</span> <div class="h4"><?php _e('Allow or deny sending attachments in messages. When enabled, user can upload files and send it with message. Note that files are stored on your server in folder oc-content/uploads/instant_messenger/{thread_id}/.', 'instant_messenger'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(5)</span> <div class="h5"><?php _e('Set what is maximum allowed file size of attachment send in message. Note that attachments are stored on your server so it is recommended to set this to acceptable value, i.e. 1024kb.', 'instant_messenger'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(6)</span> <div class="h6"><?php _e('Choose which kind of files can be sent in attachments. It is recommended not to allow sending PHP file as this might be security risk for your site (script could be executed).', 'instant_messenger'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(7)</span> <div class="h7"><?php _e('Enable if you want to allow users to remove their messages in threads. Note that users will not be able to remove threads. In case you want to use this plugin as way of support delivery, this option should be disabled.', 'instant_messenger'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(8)</span> <div class="h8"><?php _e('Set how many threads should be shown on 1 page. It is recommended to set not more than 50 threads to avoid server performance issues.', 'instant_messenger'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(9)</span> <div class="h9"><?php _e('When enabled, visitors will have to login in order to be able to send instant messages.', 'instant_messenger'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(10)</span> <div class="h10"><?php _e('When enabled, messages in thread are refreshed to user using ajax, plugin then acts as chat. Sound will be played to user when there is new message as well as task bar will flash.', 'instant_messenger'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(11)</span> <div class="h11"><?php _e('Enter value between 1000 to 10000 (1 - 10 seconds), by default there is 3000.', 'instant_messenger'); ?></div></div>
    </div>
  </div>
</div>

<?php echo im_footer(); ?>
