<?php
/**
 * Unauthorized conversation screen (mobile/desktop mockup).
 */
if (!defined('ABS_PATH')) {
    exit;
}
$back = osc_is_web_user_logged_in() ? osc_route_url('im-threads') : osc_base_url();
?>
<div class="pngm-im pngm-im-unauthorized">
  <div class="pngm-im-unauthorized-card">
    <i class="fas fa-lock" aria-hidden="true"></i>
    <h1><?php _e('Unauthorized Thread', 'epsilon'); ?></h1>
    <p><?php _e('You’re not authorized to view this conversation.', 'epsilon'); ?></p>
    <a class="pngm-ua-btn" href="<?php echo osc_esc_html($back); ?>"><?php _e('Back to Messages', 'epsilon'); ?></a>
  </div>
</div>
