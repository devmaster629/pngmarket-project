<?php
/**
 * Messages thread — chat pane (mockup right column) + list on desktop.
 * Included from instant_messenger/user/messages.php after business logic.
 *
 * Expected vars from messages.php: $thread, $thread_id, $secret, $messages,
 * $thread_target_name, $thread_target_id, $logged_user_*, $item, $item_details,
 * $last_seen, $target_is_null, $blocked_*, $att_enable, $message_delete,
 * $type, $offer, $ajax, $t_title, $from_public_url, $to_public_url, ...
 */

if (!defined('ABS_PATH')) {
    exit;
}

require_once dirname(__FILE__) . '/im_ui.php';

$user_id = (int) osc_logged_user_id();
$list_rows = pngm_im_prepare_conversations($user_id, 50, 0);
$hero = pngm_im_listing_hero(is_array($item) ? $item : array());
$peer_avatar = function_exists('im_profile_img_url')
    ? im_profile_img_url((int) $thread_target_id, (string) $thread_target_name)
    : '';
$peer_initials = pngm_ua_user_initials($thread_target_name);
$status_line = $last_seen !== ''
    ? sprintf(__('Last online %s', 'instant_messenger'), $last_seen)
    : __('Active now', 'epsilon');
$back_url = osc_route_url('im-threads');
$can_send = (im_param('only_logged') != 1 || osc_is_web_user_logged_in())
    && $target_is_null === false
    && $blocked_by_you != 0
    && $blocked_you != 0;
?>

<link href="<?php echo osc_base_url(); ?>oc-content/plugins/instant_messenger/css/tipped.css" rel="stylesheet" type="text/css" />
<script src="<?php echo osc_base_url(); ?>oc-content/plugins/instant_messenger/js/tipped.js"></script>
<script src="<?php echo osc_base_url(); ?>oc-content/plugins/instant_messenger/js/user.js?v=<?php echo date('Ymdhis'); ?>"></script>

<div class="im-html im-file-messages im-theme-<?php echo osc_esc_html(osc_current_web_theme()); ?> pngm-im pngm-im-chat">
  <div class="pngm-im-chat-layout">
    <aside class="pngm-im-list-side">
      <?php pngm_im_render_conversation_list($list_rows, (int) $thread_id); ?>
    </aside>

    <section class="pngm-im-thread-pane">
      <header class="pngm-im-thread-head">
        <a class="pngm-im-back" href="<?php echo osc_esc_html($back_url); ?>" aria-label="<?php echo osc_esc_html(__('Back to Messages', 'epsilon')); ?>">
          <i class="fas fa-arrow-left" aria-hidden="true"></i>
        </a>
        <span class="pngm-im-thread-av">
          <?php if ($peer_avatar !== '') { ?>
            <img src="<?php echo osc_esc_html($peer_avatar); ?>" alt="" width="40" height="40" />
          <?php } else { ?>
            <span class="pngm-im-convo-initials"><?php echo osc_esc_html($peer_initials); ?></span>
          <?php } ?>
        </span>
        <span class="pngm-im-thread-meta">
          <strong><?php echo osc_esc_html($thread_target_name); ?></strong>
          <small><?php echo osc_esc_html($status_line); ?></small>
        </span>
        <div class="pngm-im-more">
          <button type="button" class="pngm-im-more-btn" aria-label="<?php echo osc_esc_html(__('More', 'epsilon')); ?>">
            <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
          </button>
          <div class="pngm-im-more-menu" hidden>
            <?php if (im_param('remove_thread') == 1) { ?>
              <a href="<?php echo osc_esc_html(osc_route_url('im-thread-remove', array('thread-remove-id' => $thread_id, 'secret' => $secret))); ?>"
                 onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to remove this thread? Action cannot be undone!', 'instant_messenger')); ?>');">
                <?php _e('Remove conversation', 'epsilon'); ?>
              </a>
            <?php } ?>
            <a href="<?php echo osc_esc_html($back_url); ?>"><?php _e('Back to Messages', 'epsilon'); ?></a>
          </div>
        </div>
      </header>

      <?php if ($hero['ok']) { ?>
        <a class="pngm-im-listing-hero" href="<?php echo osc_esc_html($hero['url']); ?>" target="_blank" rel="noopener">
          <img src="<?php echo osc_esc_html($hero['thumb']); ?>" alt="" width="72" height="56" loading="lazy" />
          <span class="pngm-im-listing-hero-body">
            <strong><?php echo osc_esc_html($hero['title']); ?></strong>
            <?php if ($hero['price'] !== '') { ?>
              <em class="pngm-im-listing-price"><?php echo osc_esc_html($hero['price']); ?></em>
            <?php } ?>
            <?php if ($hero['location'] !== '') { ?>
              <span class="pngm-im-listing-loc"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php echo osc_esc_html($hero['location']); ?></span>
            <?php } ?>
            <span class="pngm-im-listing-view"><?php _e('View listing', 'epsilon'); ?> →</span>
          </span>
        </a>
      <?php } ?>

      <?php if ($offer) { ?>
        <a href="<?php echo osc_route_url('mo-show-offers', array('offerId' => $thread['i_offer_id'])); ?>" class="pngm-im-offer-banner">
          <?php echo sprintf(__('Related offer: %sx %s for %s%s', 'instant_messenger'), $offer['i_quantity'], @$offer_item['s_title'], $offer['i_price'] / 1000000, @$currency_desc); ?>
        </a>
      <?php } ?>

      <?php if ($target_is_null) { ?>
        <div class="pngm-im-notice"><?php echo sprintf(__('%s has removed this thread, you cannot reply back.', 'instant_messenger'), osc_esc_html($thread_target_name)); ?></div>
      <?php } ?>

      <ul id="im-error-list" class="error-list im-error-list pngm-im-errors"></ul>

      <div class="im-table im-messages im-body pngm-im-board">
        <?php if (is_array($messages) && count($messages) > 0) { ?>
          <?php
            $i = 1;
            $show_last = 10;
            if (count($messages) > $show_last) { ?>
              <div class="im-show-older"><span><?php _e('Show older messages', 'instant_messenger'); ?></span></div>
            <?php }

            foreach ($messages as $m) {
                if ((osc_is_web_user_logged_in() && (osc_logged_user_id() == $thread['i_from_user_id'] && $m['i_type'] == 0 || osc_logged_user_id() == $thread['i_to_user_id'] && $m['i_type'] == 1)) || ($secret == $thread['s_from_secret'] && $m['i_type'] == 0 || $secret == $thread['s_to_secret'] && $m['i_type'] == 1)) {
                    $logged_is_owner = true;
                } else {
                    $logged_is_owner = false;
                }
                $hidden = (count($messages) - $i >= $show_last) ? ' hidden' : '';
                ?>
            <div class="im-table-row pngm-im-bubble-row<?php echo $logged_is_owner ? ' im-from is-mine' : ' im-to is-theirs'; ?><?php echo $hidden; ?>" data-message-id="<?php echo (int) $m['pk_i_id']; ?>">
              <div class="pngm-im-bubble">
                <?php // Contact details are allowed on this marketplace — no "unsafe" warning. ?>
                <div class="pngm-im-bubble-text"><?php echo $m['s_message']; ?></div>
                <?php if ($m['s_file'] <> '' && $att_enable == 1) { ?>
                  <?php
                    $att_label = function_exists('pngm_im_file_label')
                      ? pngm_im_file_label((int) $m['pk_i_id'], $m['s_file'])
                      : basename((string) $m['s_file']);
                  ?>
                  <a class="im-download pngm-im-attach" href="<?php echo im_attachment_url($thread['i_thread_id'], $m['s_file']); ?>" target="_blank" title="<?php echo osc_esc_html($att_label); ?>">
                    <i class="fas fa-paperclip" aria-hidden="true"></i>
                    <span class="pngm-im-attach-name"><?php echo osc_esc_html($att_label); ?></span>
                  </a>
                <?php } ?>
                <div class="pngm-im-bubble-meta">
                  <time title="<?php echo osc_esc_html(date('d/m/Y H:i:s', strtotime($m['d_datetime']))); ?>"><?php echo im_get_time_diff($m['d_datetime']); ?></time>
                  <?php if ($logged_is_owner && (int) $m['i_read'] === 1) { ?>
                    <i class="fas fa-check-double" aria-hidden="true" title="<?php echo osc_esc_html(__('Read', 'epsilon')); ?>"></i>
                  <?php } ?>
                </div>
              </div>
              <?php if ($logged_is_owner && $message_delete == 1) { ?>
                <a class="pngm-im-del" href="<?php echo osc_route_url('im-delete-message', array('thread-id' => $thread['i_thread_id'], 'del-message-id' => $m['pk_i_id'], 'secret' => $secret)); ?>" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to delete this message', 'instant_messenger')); ?>?')"><i class="fas fa-trash" aria-hidden="true"></i></a>
              <?php } ?>
            </div>
                <?php
                $i++;
            }
            ?>
        <?php } else { ?>
          <div class="pngm-im-board-empty"><?php _e('You do not have any messages', 'instant_messenger'); ?></div>
        <?php } ?>

        <?php if ($blocked_by_you == 0) { ?>
          <div class="im-err im-err-blocked-by-you"><?php echo $blocked_by_you_msg; ?></div>
        <?php } elseif ($blocked_you == 0) { ?>
          <div class="im-err im-err-blocked-you"><?php echo $blocked_you_msg; ?></div>
        <?php } ?>
      </div>

      <?php if (im_param('only_logged') == 1 && !osc_is_web_user_logged_in()) { ?>
        <div class="pngm-im-notice"><?php _e('Please login to send messages', 'instant_messenger'); ?></div>
      <?php } elseif ($can_send) { ?>
        <form id="im-message-form" class="pngm-im-composer im-row im-body im-form-validate" action="<?php echo osc_route_url('im-messages', array('thread-id' => $thread['i_thread_id'], 'secret' => $secret)); ?>" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="im-action" id="im-action" value="send_message" />
          <?php if ($att_enable == 1) { ?>
            <label class="pngm-im-attach-btn im-attachment" title="<?php echo osc_esc_html(__('Upload file', 'instant_messenger')); ?>">
              <i class="fas fa-paperclip" aria-hidden="true"></i>
              <input type="file" name="im-file[]" id="im-file" class="im-file" multiple />
            </label>
          <?php } ?>
          <textarea name="im-message" id="im-message" class="im-textarea" rows="1" placeholder="<?php echo osc_esc_html(__('Type a message…', 'epsilon')); ?>"></textarea>
          <button type="submit" class="im-button-green pngm-im-send">
            <i class="fas fa-paper-plane" aria-hidden="true"></i>
            <span><?php _e('Send', 'epsilon'); ?></span>
          </button>
          <button type="submit" class="im-button-green im-button-alt" style="display:none;"><i class="fa fa-paper-plane"></i></button>
          <div class="im-file-list" id="im-file-list" hidden></div>
          <div class="im-send-hint pngm-im-send-hint"><?php _e('Enter to send · Ctrl+Enter for a new line', 'epsilon'); ?></div>
        </form>
      <?php } ?>
    </section>
  </div>
</div>

<?php pngm_im_ui_script(); ?>
