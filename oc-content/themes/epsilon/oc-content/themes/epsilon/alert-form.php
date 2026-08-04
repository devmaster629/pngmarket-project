<?php 
  $active = false;
  if(function_exists('osc_search_alert_subscribed') && osc_search_alert_subscribed()) { 
    $active = true;
  }
?>

<a class="open-alert-box btn<?php echo ($active ? ' active' : ''); ?>" href="#" title="<?php echo osc_esc_html($active ? __('You have already subscribed to this search', 'epsilon') : __('You will receive email notification once new listing matching search criteria is published', 'epsilon')); ?>">
  <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M459.7 194.6C482 174.1 496 144.7 496 112 496 50.1 445.9 0 384 0c-45.3 0-84.3 26.8-101.9 65.5-17.3-2-34.9-2-52.2 0C212.3 26.8 173.3 0 128 0 66.1 0 16 50.1 16 112c0 32.7 14 62.1 36.3 82.6C39.3 223 32 254.7 32 288c0 53.2 18.6 102.1 49.5 140.5L39 471c-9.4 9.4-9.4 24.6 0 33.9 9.4 9.4 24.6 9.4 33.9 0l42.5-42.5c81.5 65.7 198.7 66.4 281 0L439 505c9.4 9.4 24.6 9.4 33.9 0 9.4-9.4 9.4-24.6 0-33.9l-42.5-42.5c31-38.4 49.5-87.3 49.5-140.5.1-33.4-7.2-65.1-20.2-93.5zM384 48c35.3 0 64 28.7 64 64 0 15.1-5.3 29-14 39.9-26.2-34.2-62-60.6-103.3-75.2C342.1 59.4 361.7 48 384 48zM64 112c0-35.3 28.7-64 64-64 22.3 0 41.9 11.4 53.4 28.7-41.4 14.6-77.2 41-103.3 75.2C69.3 141 64 127.1 64 112zm192 352c-97.3 0-176-78.7-176-176 0-97 78.4-176 176-176 97.4 0 176 78.8 176 176 0 97.3-78.7 176-176 176zm46.2-95.7l-69-47.5c-3.3-2.2-5.2-5.9-5.2-9.9V180c0-6.6 5.4-12 12-12h32c6.6 0 12 5.4 12 12v107.7l50 34.4c5.5 3.8 6.8 11.2 3.1 16.7L319 365.2c-3.8 5.4-11.3 6.8-16.8 3.1z"></path></svg>
  <?php if($active) { ?><i class="fas fa-check active-badge"></i><?php } ?>
  <span><?php _e('Create alert', 'epsilon'); ?></span>
</a>

<div class="alert-box <?php if(osc_is_web_user_logged_in()) { ?>logged<?php } else { ?>nonlogged<?php } ?>" style="display:none;">
  <form action="<?php echo osc_base_url(true); ?>" method="post" name="sub_alert" id="alert-form" class="nocsrf">
    <?php AlertForm::page_hidden(); ?>
    <?php AlertForm::alert_hidden(); ?>
    <?php AlertForm::user_id_hidden(); ?>
    
    <img src="<?php echo osc_current_web_theme_url('images/reminder.png'); ?>" alt="<?php echo osc_esc_html(__('Subscriptions', 'epsilon')); ?>" />
    <div class="header"><?php _e('Subscribe to this search', 'epsilon'); ?></div>
    <div class="text">
      <?php _e('Create alert to receive email notification when new listing matching search criteria is published.', 'epsilon'); ?>
      <a href="<?php echo osc_user_alerts_url(); ?>"><?php _e('Manage subscriptions', 'epsilon'); ?>.</a>
    </div>

    <div class="inputs" <?php if($active) { ?>style="display:none;"<?php } ?>>
      <input id="alert_email" type="email" name="alert_email" required value="<?php echo osc_esc_html(osc_is_web_user_logged_in() ? osc_logged_user_email() : ''); ?>" placeholder="<?php echo osc_esc_html(__('Enter your email...', 'epsilon')); ?>" <?php echo osc_is_web_user_logged_in() ? 'readonly' : ''; ?>/>
      <button type="submit" class="btn create-alert"><?php _e('Create alert', 'epsilon'); ?></button>
    </div>
    
    <div class="response ok" <?php if($active) { ?>style="display:block;"<?php } ?>><?php echo sprintf(__('You have successfully create alert for this search! You will receive %s notifications to email %s.', 'epsilon'), '<strong class="res-frequency">' . __('daily', 'epsilon') . '</strong>', '<strong class="res-email"></strong>'); ?></div>
    <div class="response duplicate"><?php echo sprintf(__('You already have active alert created on this search for email %s.', 'epsilon'), '<strong class="res-email"></strong>'); ?></div>
    <div class="response error"><?php echo __('Error: Alert was not created, please try it again later.', 'epsilon'); ?></div>
  </form>
</div>

<script type="text/javascript">
$(document).on('submit', 'form[name="sub_alert"]', function(e){
  e.preventDefault();

  var form = $(this);
  var button = $(this).find('button');
  
  form.addClass('loading');
  
  $.ajax({
    url: form.attr('action'),
    type: "POST",
    data: {
      email: form.find('input[name="alert_email"]').val(), 
      userid: form.find('input[name="alert_userId"]').val(), 
      alert: form.find('input[name="alert"]').val(), 
      page: 'ajax', 
      action: 'alerts'
    },
    success: function(response){
      form.removeClass('loading');
      form.find('.inputs').hide(0);
      form.find('.response .res-email').text(form.find('input[name="alert_email"]').val());
      form.find('.response').hide(0);
      
      if(response == 1) {
        form.find('.response.ok').show(0);
      } else if (response == 0) {
        form.find('.response.duplicate').show(0);
      } else {  // response == -1
        form.find('.response.error').show(0);
      }
    },
    error: function(response) {
      console.log(response);

      form.removeClass('loading');
      form.find('.inputs').hide(0);
      form.find('.response.error').show(0);
    }
  });
});
</script>