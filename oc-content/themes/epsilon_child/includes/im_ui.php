<?php
/**
 * Shared Messages UI helpers (list + chat) for PNG Market mockup.
 */

if (!defined('ABS_PATH')) {
    exit;
}

if (!function_exists('pngm_ua_user_initials')) {
    require_once dirname(__FILE__) . '/account_ua.php';
}

/**
 * Rough mobile/tablet request check (matches list-first Messages flow).
 *
 * @return bool
 */
function pngm_im_is_mobile_request()
{
    if (isset($_COOKIE['pngm_im_mobile']) && $_COOKIE['pngm_im_mobile'] === '1') {
        return true;
    }
    if (isset($_COOKIE['pngm_im_mobile']) && $_COOKIE['pngm_im_mobile'] === '0') {
        return false;
    }
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
    if ($ua === '') {
        return false;
    }
    return (bool) preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile/i', $ua);
}

/**
 * Remember the original upload name for a stored IM attachment.
 *
 * @param int    $message_id
 * @param string $original_name
 */
function pngm_im_set_file_label($message_id, $original_name)
{
    $message_id = (int) $message_id;
    $original_name = basename(trim(strip_tags((string) $original_name)));
    if ($message_id <= 0 || $original_name === '') {
        return;
    }
    osc_set_preference('file_' . $message_id, $original_name, 'pngm_im_files', 'STRING');
    if (class_exists('Preference')) {
        Preference::newInstance()->set('file_' . $message_id, $original_name, 'pngm_im_files');
    }
}

/**
 * Human label for an IM attachment (original name when known).
 *
 * @param int    $message_id
 * @param string $stored_file
 * @return string
 */
function pngm_im_file_label($message_id, $stored_file)
{
    $message_id = (int) $message_id;
    $stored_file = basename((string) $stored_file);
    if ($message_id > 0) {
        $saved = osc_get_preference('file_' . $message_id, 'pngm_im_files');
        if (is_string($saved) && trim($saved) !== '') {
            return trim($saved);
        }
    }
    if ($stored_file !== '') {
        return $stored_file;
    }
    return __('Attachment', 'epsilon');
}

/**
 * Unread inbound message count for one thread.
 *
 * @param int $thread_id
 * @param int $user_id
 * @return int
 */
function pngm_im_thread_unread_count($thread_id, $user_id)
{
    $thread_id = (int) $thread_id;
    $user_id = (int) $user_id;
    if ($thread_id <= 0 || $user_id <= 0 || !class_exists('ModelIM')) {
        return 0;
    }

    $model = ModelIM::newInstance();
    $thread = $model->getThreadById($thread_id);
    if (!is_array($thread)) {
        return 0;
    }

    // i_type 0 = from initiator; i_type 1 = from recipient.
    if ((int) $thread['i_from_user_id'] === $user_id) {
        $type = 1;
    } elseif ((int) $thread['i_to_user_id'] === $user_id) {
        $type = 0;
    } else {
        return 0;
    }

    $dao = $model->dao;
    $dao->select('COUNT(pk_i_id) AS i_count');
    $dao->from($model->getTable_messages());
    $dao->where('fk_i_thread_id', $thread_id);
    $dao->where('i_type', $type);
    $dao->where('i_read', 0);
    $result = $dao->get();
    if (!$result) {
        return 0;
    }
    $row = $result->row();
    return isset($row['i_count']) ? (int) $row['i_count'] : 0;
}

/**
 * Map of thread_id => unread count for the logged-in user (capped).
 *
 * @param int $user_id
 * @param int $limit
 * @return array
 */
function pngm_im_unread_thread_map($user_id, $limit = 50)
{
    $user_id = (int) $user_id;
    $map = array();
    if ($user_id <= 0 || !class_exists('ModelIM')) {
        return $map;
    }

    $threads = ModelIM::newInstance()->getThreadsByUserId($user_id, (int) $limit, 0);
    if (!is_array($threads)) {
        return $map;
    }

    foreach ($threads as $t) {
        if (!is_array($t) || empty($t['i_thread_id'])) {
            continue;
        }
        $tid = (int) $t['i_thread_id'];
        $n = pngm_im_thread_unread_count($tid, $user_id);
        if ($n > 0) {
            $map[(string) $tid] = $n;
        }
    }

    return $map;
}

/**
 * Build conversation rows for the logged-in user.
 *
 * @param int $user_id
 * @param int $limit
 * @param int $offset
 * @return array
 */
function pngm_im_prepare_conversations($user_id, $limit = 50, $offset = 0)
{
    $user_id = (int) $user_id;
    $out = array();
    if ($user_id <= 0 || !class_exists('ModelIM')) {
        return $out;
    }

    $threads = ModelIM::newInstance()->getThreadsByUserId($user_id, (int) $limit, (int) $offset);
    if (!is_array($threads)) {
        return $out;
    }

    foreach ($threads as $t) {
        if (!is_array($t) || empty($t['i_thread_id'])) {
            continue;
        }

        $thread_id = (int) $t['i_thread_id'];
        if ((int) $t['i_from_user_id'] === $user_id) {
            $peer_id = (int) $t['i_to_user_id'];
            $peer_name = (string) $t['s_to_user_name'];
        } else {
            $peer_id = (int) $t['i_from_user_id'];
            $peer_name = (string) $t['s_from_user_name'];
        }
        if ($peer_name === '') {
            $peer_name = __('User', 'epsilon');
        }

        $unread_n = pngm_im_thread_unread_count($thread_id, $user_id);
        $unread = ($unread_n > 0);

        $last = ModelIM::newInstance()->getLastMessageByThreadId($thread_id);
        $snippet = '';
        $time_label = '';
        if (is_array($last)) {
            $snippet = trim(strip_tags((string) @$last['s_message']));
            if (function_exists('mb_substr')) {
                $snippet = mb_substr($snippet, 0, 80);
            } else {
                $snippet = substr($snippet, 0, 80);
            }
            $dt = (string) @$last['d_datetime'];
            if ($dt === '') {
                $dt = (string) @$t['d_datetime'];
            }
            if ($dt !== '' && function_exists('im_get_time_diff')) {
                $time_label = im_get_time_diff($dt);
            }
        } elseif (!empty($t['d_datetime']) && function_exists('im_get_time_diff')) {
            $time_label = im_get_time_diff($t['d_datetime']);
        }

        $avatar = function_exists('im_profile_img_url') ? im_profile_img_url($peer_id, $peer_name) : '';
        $url = osc_route_url('im-messages', array('thread-id' => $thread_id, 'secret' => 'n'));

        $hay = strtolower($peer_name . ' ' . $snippet . ' ' . (string) @$t['s_title']);

        $out[] = array(
            'thread_id' => $thread_id,
            'peer_id' => $peer_id,
            'peer_name' => $peer_name,
            'initials' => pngm_ua_user_initials($peer_name),
            'avatar' => $avatar,
            'snippet' => $snippet !== '' ? $snippet : __('No messages yet', 'epsilon'),
            'time' => $time_label,
            'unread' => $unread,
            'unread_count' => $unread_n,
            'url' => $url,
            'search_hay' => $hay,
            'flagged' => ((int) @$t['i_flag'] === 1),
            'offer' => (!empty($t['i_offer_id'])),
        );
    }

    return $out;
}

/**
 * Banner for profile / no-listing threads (QD-006).
 *
 * @return string
 */
function pngm_im_render_general_inquiry()
{
    $label = __('General seller inquiry', 'epsilon');
    $html  = '<div class="im-row im-item-context im-body pngm-im-general-inquiry" role="status">';
    $html .= '<div class="pngm-im-general-inquiry-inner">';
    $html .= '<span class="pngm-im-general-inquiry-icon" aria-hidden="true"><i class="fas fa-comments"></i></span>';
    $html .= '<span class="pngm-im-general-inquiry-text">';
    $html .= '<span class="im-line im-item-label">' . osc_esc_html(__('Conversation', 'epsilon')) . '</span>';
    $html .= '<span class="im-line im-item-title">' . osc_esc_html($label) . '</span>';
    $html .= '</span></div></div>';
    return $html;
}

/**
 * Listing hero data for a thread item.
 *
 * @param array $item
 * @return array
 */
function pngm_im_listing_hero($item)
{
    $hero = array(
        'ok' => false,
        'title' => '',
        'url' => '',
        'thumb' => function_exists('eps_get_noimage') ? eps_get_noimage() : '',
        'price' => '',
        'location' => '',
    );

    if (!is_array($item) || empty($item['pk_i_id'])) {
        return $hero;
    }

    View::newInstance()->_erase('resources');
    View::newInstance()->_exportVariableToView('item', $item);

    $hero['ok'] = true;
    $hero['title'] = trim((string) osc_item_title());
    if ($hero['title'] === '' && !empty($item['s_title'])) {
        $hero['title'] = (string) $item['s_title'];
    }
    $hero['url'] = osc_item_url();
    $hero['location'] = function_exists('pngm_ua_item_location_short')
        ? pngm_ua_item_location_short()
        : trim(osc_item_city() . (osc_item_region() ? ', ' . osc_item_region() : ''));

    if (function_exists('eps_check_category_price') && eps_check_category_price(osc_item_category_id())) {
        $hero['price'] = function_exists('pngm_format_price')
            ? (string) pngm_format_price()
            : (string) osc_item_formated_price();
    }

    $resources = ItemResource::newInstance()->getAllResourcesFromItem((int) $item['pk_i_id']);
    if (is_array($resources) && !empty($resources)) {
        View::newInstance()->_exportVariableToView('resources', $resources);
        osc_reset_resources();
        if (osc_has_item_resources()) {
            $thumb = (string) osc_resource_thumbnail_url();
            if ($thumb === '') {
                $thumb = (string) osc_resource_preview_url();
            }
            if ($thumb === '') {
                $thumb = (string) osc_resource_url();
            }
            if ($thumb !== '') {
                $hero['thumb'] = $thumb;
            }
        }
    }

    View::newInstance()->_erase('resources');
    View::newInstance()->_erase('item');

    return $hero;
}

/**
 * Render conversation list markup (middle column).
 *
 * @param array $rows
 * @param int   $active_thread_id
 */
function pngm_im_render_conversation_list($rows, $active_thread_id = 0)
{
    $active_thread_id = (int) $active_thread_id;
    ?>
  <div class="pngm-im-list-pane">
    <div class="pngm-im-list-head">
      <h1><?php _e('Messages', 'epsilon'); ?></h1>
    </div>

    <form class="pngm-im-search" action="#" method="get" role="search" data-pngm-im-search="1">
      <i class="fas fa-search" aria-hidden="true"></i>
      <input type="text" id="pngm-im-search-input" placeholder="<?php echo osc_esc_html(__('Search conversations…', 'epsilon')); ?>" autocomplete="off" />
    </form>

    <div class="pngm-im-convo-list" id="pngm-messages-list">
      <?php if (is_array($rows) && count($rows) > 0) { ?>
        <?php foreach ($rows as $row) {
            $is_active = ($active_thread_id > 0 && (int) $row['thread_id'] === $active_thread_id);
            ?>
          <a class="pngm-im-convo<?php echo $row['unread'] ? ' is-unread' : ''; ?><?php echo $is_active ? ' is-active' : ''; ?>"
             href="<?php echo osc_esc_html($row['url']); ?>"
             data-thread-id="<?php echo (int) $row['thread_id']; ?>"
             data-unread="<?php echo (int) (!empty($row['unread_count']) ? $row['unread_count'] : ($row['unread'] ? 1 : 0)); ?>"
             data-search="<?php echo osc_esc_html($row['search_hay']); ?>">
            <span class="pngm-im-convo-av">
              <?php if ($row['avatar'] !== '') { ?>
                <img src="<?php echo osc_esc_html($row['avatar']); ?>" alt="" width="48" height="48" loading="lazy" />
              <?php } else { ?>
                <span class="pngm-im-convo-initials"><?php echo osc_esc_html($row['initials']); ?></span>
              <?php } ?>
            </span>
            <span class="pngm-im-convo-body">
              <span class="pngm-im-convo-top">
                <strong><?php echo osc_esc_html($row['peer_name']); ?></strong>
                <?php if ($row['time'] !== '') { ?>
                  <time><?php echo osc_esc_html($row['time']); ?></time>
                <?php } ?>
              </span>
              <span class="pngm-im-convo-bottom">
                <em><?php echo osc_esc_html($row['snippet']); ?></em>
                <?php
                  $unread_n = !empty($row['unread_count']) ? (int) $row['unread_count'] : ($row['unread'] ? 1 : 0);
                  if ($unread_n > 0 && !$is_active) {
                ?>
                  <span class="pngm-im-unread-dot" aria-label="<?php echo osc_esc_html(__('Unread', 'epsilon')); ?>"><?php echo $unread_n > 99 ? '99+' : (int) $unread_n; ?></span>
                <?php } ?>
              </span>
            </span>
          </a>
        <?php } ?>
      <?php } ?>

      <div class="pngm-im-empty" id="pngm-messages-empty"<?php echo (is_array($rows) && count($rows) > 0) ? ' hidden' : ''; ?>>
        <i class="fas fa-comments" aria-hidden="true"></i>
        <p><?php _e('No conversations found', 'epsilon'); ?></p>
      </div>
    </div>
  </div>
    <?php
}

/**
 * Client-side search + AJAX board swap for IM pages.
 */
function pngm_im_ui_script()
{
    ?>
<script>
(function () {
  var form = document.querySelector('[data-pngm-im-search="1"]');
  var input = document.getElementById('pngm-im-search-input');
  var list = document.getElementById('pngm-messages-list');
  var empty = document.getElementById('pngm-messages-empty');
  var boardPane = document.querySelector('.pngm-im-board-pane');
  var loadReq = null;
  var loadingUrl = '';

  if (form && input && list) {
    function applySearch() {
      var q = String(input.value || '').toLowerCase().replace(/\s+/g, ' ').trim();
      var cards = list.querySelectorAll('.pngm-im-convo');
      var shown = 0;
      var i;
      for (i = 0; i < cards.length; i += 1) {
        var hay = String(cards[i].getAttribute('data-search') || cards[i].textContent || '').toLowerCase();
        var match = (q === '' || hay.indexOf(q) !== -1);
        cards[i].style.display = match ? '' : 'none';
        if (match) shown += 1;
      }
      if (empty) {
        empty.hidden = shown > 0;
      }
    }
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      applySearch();
    });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        applySearch();
      }
    });
    input.addEventListener('input', applySearch);
  }

  document.querySelectorAll('.pngm-im-more-btn').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var menu = btn.parentNode.querySelector('.pngm-im-more-menu');
      var open = menu && !menu.hidden;
      document.querySelectorAll('.pngm-im-more-menu').forEach(function (m) { m.hidden = true; });
      if (menu && !open) {
        menu.hidden = false;
      }
    });
  });
  document.addEventListener('click', function () {
    document.querySelectorAll('.pngm-im-more-menu').forEach(function (m) { m.hidden = true; });
  });

  function setActiveConvo(url) {
    if (!list) return;
    var cards = list.querySelectorAll('.pngm-im-convo');
    var i;
    for (i = 0; i < cards.length; i += 1) {
      var active = cards[i].getAttribute('href') === url;
      cards[i].classList.toggle('is-active', active);
      if (active) {
        cards[i].classList.remove('is-unread');
        var dot = cards[i].querySelector('.pngm-im-unread-dot');
        if (dot && dot.parentNode) {
          dot.parentNode.removeChild(dot);
        }
      }
    }
  }

  function extractImMessageUrl(doc) {
    var scripts = doc.querySelectorAll('script');
    var i;
    for (i = 0; i < scripts.length; i += 1) {
      var text = scripts[i].textContent || '';
      var match = text.match(/var\s+imMessageUrl\s*=\s*["']([^"']+)["']/);
      if (match && match[1]) {
        return match[1];
      }
    }
    return '';
  }

  function afterBoardSwap() {
    if (typeof window.imShowOlder !== 'undefined') {
      window.imShowOlder = 0;
    }
    if (typeof window.Tipped !== 'undefined' && typeof window.Tipped.create === 'function') {
      try {
        window.Tipped.create('.im-tooltip, .im-has-tooltip');
      } catch (err) {}
    }
    if (window.jQuery) {
      var $form = window.jQuery('#im-message-form.im-form-validate');
      if ($form.length && typeof $form.validate === 'function') {
        try {
          if ($form.data('validator')) {
            $form.removeData('validator');
            $form.unbind('validate').unbind('submit.validate');
          }
          $form.validate();
        } catch (err2) {}
      }
    }
    if (typeof window.pngmLayoutChat === 'function') {
      window.pngmLayoutChat({ pinBottom: true });
    } else {
      var board = document.querySelector('.im-table.im-messages');
      if (board) {
        board.scrollTop = board.scrollHeight;
      }
    }
    if (typeof window.pngmImAfterBoardSwap === 'function') {
      window.pngmImAfterBoardSwap();
    }
  }

  function loadBoard(url, push) {
    if (!boardPane || !url || !window.jQuery) {
      window.location.href = url;
      return;
    }
    if (loadingUrl === url) {
      return;
    }
    loadingUrl = url;
    boardPane.classList.add('is-loading');

    if (loadReq && typeof loadReq.abort === 'function') {
      loadReq.abort();
    }

    loadReq = window.jQuery.ajax({
      url: url,
      type: 'GET',
      dataType: 'html',
      cache: false
    }).done(function (html) {
      var parser = new DOMParser();
      var doc = parser.parseFromString(html, 'text/html');
      var nextBoard = doc.querySelector('.pngm-im-board-pane');
      var nextMessages = doc.querySelector('.im-file-messages');
      if (!nextBoard && !nextMessages) {
        window.location.href = url;
        return;
      }
      boardPane.innerHTML = nextBoard ? nextBoard.innerHTML : nextMessages.outerHTML;

      var refreshUrl = extractImMessageUrl(doc);
      if (refreshUrl) {
        window.imMessageUrl = refreshUrl;
      }

      setActiveConvo(url);
      if (push !== false && window.history && typeof window.history.pushState === 'function') {
        window.history.pushState({ pngmImBoard: 1, url: url }, '', url);
      }

      var titleEl = doc.querySelector('title');
      if (titleEl && titleEl.textContent) {
        document.title = titleEl.textContent;
      }

      afterBoardSwap();
    }).fail(function (xhr, status) {
      if (status !== 'abort') {
        window.location.href = url;
      }
    }).always(function () {
      loadingUrl = '';
      boardPane.classList.remove('is-loading');
      loadReq = null;
    });
  }

  function isMobileImLayout() {
    return window.matchMedia && window.matchMedia('(max-width: 980px)').matches;
  }

  try {
    document.cookie = 'pngm_im_mobile=' + (isMobileImLayout() ? '1' : '0') + '; path=/; max-age=31536000; SameSite=Lax';
  } catch (cookieErr) {}

  if (list && boardPane) {
    list.addEventListener('click', function (e) {
      var link = e.target.closest ? e.target.closest('a.pngm-im-convo') : null;
      if (!link || !list.contains(link)) {
        return;
      }
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button === 1) {
        return;
      }
      // Mobile: full page navigation to chat details
      if (isMobileImLayout()) {
        return;
      }
      var url = link.getAttribute('href');
      if (!url) {
        return;
      }
      if (link.classList.contains('is-active') && !boardPane.classList.contains('is-loading')) {
        e.preventDefault();
        return;
      }
      e.preventDefault();
      loadBoard(url, true);
    });

    window.addEventListener('popstate', function () {
      if (!document.querySelector('.pngm-im-split') || isMobileImLayout()) {
        return;
      }
      loadBoard(window.location.href, false);
    });
  }

  /**
   * Optimistic send: paint the bubble + clear the input immediately, then POST
   * to a light AJAX endpoint. The plugin path waits on a full-page POST (and
   * often sync SMTP), which feels like a 1–2s stall.
   */
  function initOptimisticSend() {
    if (!window.jQuery) {
      return;
    }
    var $ = window.jQuery;
    var sendUrl = (typeof window.baseAjaxUrl === 'string' && window.baseAjaxUrl)
      ? String(window.baseAjaxUrl).replace(/ajaxRequest=1.*/, 'page=ajax&action=runhook&hook=pngm_im_send')
      : (window.location.origin + window.location.pathname + '?page=ajax&action=runhook&hook=pngm_im_send');
    var sending = false;

    function escapeHtml(text) {
      var d = document.createElement('div');
      d.textContent = text == null ? '' : String(text);
      return d.innerHTML;
    }

    function threadMeta($form) {
      var action = $form.attr('action') || window.location.href;
      var threadId = 0;
      var secret = 'n';
      var m = String(action).match(/thread-id[=\/](\d+)/i) || String(window.location.href).match(/thread-id[=\/](\d+)/i);
      if (m) {
        threadId = parseInt(m[1], 10) || 0;
      }
      var s = String(action).match(/secret[=\/]([^\/&#?]+)/i) || String(window.location.href).match(/secret[=\/]([^\/&#?]+)/i);
      if (s) {
        secret = decodeURIComponent(s[1]);
      }
      var hiddenSecret = $form.find('input[name="secret"]').val();
      if (hiddenSecret) {
        secret = hiddenSecret;
      }
      return { threadId: threadId, secret: secret };
    }

    function pinBoard($board) {
      var $b = ($board && $board.jquery) ? $board : $('.im-table.im-messages').first();
      if (typeof window.pngmLayoutChat === 'function') {
        window.pngmLayoutChat({ pinBottom: true });
      } else if (typeof window.imStickChatToBottom === 'function') {
        window.imStickChatToBottom($b);
      } else if ($b[0]) {
        $b[0].scrollTop = $b[0].scrollHeight;
      }
    }

    /**
     * Clone a real outgoing row so plugin CSS (blue bubble + avatar) applies
     * immediately — our pngm-im-bubble markup was fighting .im-from styles.
     */
    function appendOptimistic(text) {
      var $board = $('.im-table.im-messages').first();
      if (!$board.length) {
        return $();
      }
      $board.find('.pngm-im-board-empty, .im-empty').remove();

      var id = 'pending-' + Date.now();
      var bodyHtml = escapeHtml(text).replace(/\n/g, '<br>');
      var $tpl = $board.find('.im-table-row.im-from').last();
      var $row;

      if ($tpl.length) {
        $row = $tpl.clone(false);
        $row.attr('data-message-id', id);
        $row.removeClass('hidden is-failed').addClass('is-pending');
        $row.find('.im-message-content .im-align-left, .im-message-content .im-col-24').first().html(bodyHtml);
        $row.find('.im-message-content .im-unsafe-info').remove();
        $row.find('.im-message-extra').removeClass('im-box-gray').addClass('im-box-empty')
          .find('.im-download, a.im-download').remove();
        $row.find('.im-date span, .im-date > span, .pngm-im-bubble-meta time').first()
          .text('<?php echo osc_esc_js(__('Just now', 'epsilon')); ?>');
        $row.find('.im-date .fa-check, .im-date .fa-check-double, .pngm-im-bubble-meta .fa-check-double').remove();
        $row.find('.pngm-im-bubble-text').html(bodyHtml);
        $row.find('.im-del-mes-box, .pngm-im-del').remove();
      } else {
        // First message in the thread — match plugin outgoing markup.
        var avatar = '';
        var $formImg = $('#im-message-form img.im-logged-user-img').first();
        if ($formImg.length) {
          avatar = '<img src="' + escapeHtml($formImg.attr('src') || '') + '" alt="" />';
        }
        $row = $(
          '<div class="im-table-row im-from is-pending" data-message-id="' + id + '">'
          +   '<div class="im-horizontal"><span class="left"></span><span class="right">' + avatar + '</span></div>'
          +   '<div class="im-line im-name-top">'
          +     '<div class="im-col-12 im-name im-align-left"><strong></strong></div>'
          +     '<div class="im-col-12 im-date im-align-right im-i im-gray">'
          +       '<span><?php echo osc_esc_js(__('Just now', 'epsilon')); ?></span>'
          +     '</div>'
          +   '</div>'
          +   '<div class="im-line im-message-content"><div class="im-col-24 im-align-left">' + bodyHtml + '</div></div>'
          +   '<div class="im-line im-message-extra im-box-empty"></div>'
          + '</div>'
        );
      }

      $board.append($row);
      pinBoard($board);
      return $row;
    }

    function markRowTime($row, label) {
      if (!$row || !$row.length) {
        return;
      }
      var $t = $row.find('.im-date span, .pngm-im-bubble-meta time').first();
      if ($t.length) {
        $t.text(label);
      }
    }

    function doSend($form) {
      if (sending || !$form.length) {
        return;
      }
      var $ta = $form.find('textarea[name="im-message"], #im-message');
      var text = String($ta.val() || '').replace(/^\s+|\s+$/g, '');
      var files = (typeof window.imGetComposerFiles === 'function')
        ? window.imGetComposerFiles()
        : [];
      if (!files.length) {
        var fileInput = $form.find('input[type="file"]')[0];
        if (fileInput && fileInput.files && fileInput.files.length) {
          files = Array.prototype.slice.call(fileInput.files);
        }
      }
      var hasFile = files.length > 0;
      if (!text && !hasFile) {
        return;
      }

      var meta = threadMeta($form);
      if (!meta.threadId) {
        return;
      }

      sending = true;
      var $btn = $form.find('button[type="submit"]').prop('disabled', true);
      var $row = $();

      try {
        if (text) {
          $row = appendOptimistic(text);
        }

        // Clear the composer immediately so the next message can be typed.
        $ta.val('');
        if (typeof window.imResetComposerHeight === 'function') {
          window.imResetComposerHeight();
        }

        var data = new FormData();
        data.append('thread-id', String(meta.threadId));
        data.append('secret', meta.secret);
        data.append('im-message', text);
        data.append('im-action', 'send_message');
        if (hasFile) {
          var i;
          for (i = 0; i < files.length; i += 1) {
            data.append('im-file[]', files[i]);
          }
        }
        // Always clear pending after capture — X-removed files must not linger.
        if (typeof window.imResetComposerFiles === 'function') {
          window.imResetComposerFiles();
        } else {
          $form.find('input[type="file"]').val('');
        }

        $.ajax({
          url: sendUrl,
          type: 'POST',
          data: data,
          processData: false,
          contentType: false,
          dataType: 'json'
        }).done(function (res) {
          if (res && res.ok) {
            if ($row.length) {
              $row.attr('data-message-id', res.id || $row.attr('data-message-id'));
              $row.removeClass('is-pending');
              markRowTime($row, res.time || '<?php echo osc_esc_js(__('Just now', 'epsilon')); ?>');
            }
            // Only re-pull the board when an attachment needs server HTML.
            if (hasFile && typeof window.imRefreshMessages === 'function') {
              window.setTimeout(function () {
                window.imRefreshMessages(true);
              }, 300);
            }
          } else if ($row.length) {
            $row.addClass('is-failed');
            markRowTime($row, '<?php echo osc_esc_js(__('Not sent', 'epsilon')); ?>');
          }
        }).fail(function () {
          if ($row.length) {
            $row.addClass('is-failed');
            markRowTime($row, '<?php echo osc_esc_js(__('Not sent', 'epsilon')); ?>');
          }
        }).always(function () {
          sending = false;
          $btn.prop('disabled', false);
        });
      } catch (err) {
        sending = false;
        $btn.prop('disabled', false);
        if (window.console && console.error) {
          console.error('pngm im send', err);
        }
      }
    }

    // Drop the plugin's blocking click handler, then take over send.
    $(function () {
      $('body').off('click', '#im-message-form button');
      $('body').on('click.pngmImSend', '#im-message-form button[type="submit"]', function (e) {
        if ($(this).closest('.im-file-list, .im-file-chip').length) {
          return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();
        doSend($(this).closest('form'));
        return false;
      });
      $('body').on('submit.pngmImSend', '#im-message-form', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        doSend($(this));
        return false;
      });

      // Enter = send; Ctrl/Cmd+Enter = new line. Allow 1-character messages.
      function relaxMessageRules($form) {
        if (!$form.length || typeof $form.validate !== 'function') {
          return;
        }
        try {
          if ($form.data('validator')) {
            $form.find('[name="im-message"]').rules('remove', 'minlength');
            $form.find('[name="im-message"]').rules('add', {
              required: {
                depends: function () {
                  var fileInput = document.getElementById('im-file');
                  return !(fileInput && fileInput.files && fileInput.files.length);
                }
              },
              messages: {
                required: '<?php echo osc_esc_js(__('Message: please enter your message.', 'epsilon')); ?>'
              }
            });
          }
        } catch (err) {}
        $('#im-error-list').empty().hide();
      }

      function ensureSendHint($form) {
        var tipText = '<?php echo osc_esc_js(__('Enter to send · Ctrl+Enter for a new line', 'epsilon')); ?>';
        var formEl = $form[0];
        if (!formEl) {
          return;
        }

        // Legacy in-form hint fights too many !important rules — remove it.
        $form.find('.im-send-hint, .pngm-im-send-hint').remove();

        var dock = formEl.closest('.pngm-composer-dock');
        if (!dock) {
          dock = document.createElement('div');
          dock.className = 'pngm-composer-dock';
          if (formEl.parentNode) {
            formEl.parentNode.insertBefore(dock, formEl);
            dock.appendChild(formEl);
          }
        } else if (formEl.parentNode !== dock) {
          dock.appendChild(formEl);
        }

        var tip = document.getElementById('pngm-composer-tip');
        if (!tip) {
          tip = document.createElement('p');
          tip.id = 'pngm-composer-tip';
          tip.className = 'pngm-composer-tip';
        }
        // Keep tip BELOW the input (after the form), same on desktop + mobile.
        if (tip.parentNode !== dock || formEl.nextSibling !== tip) {
          if (formEl.nextSibling) {
            dock.insertBefore(tip, formEl.nextSibling);
          } else {
            dock.appendChild(tip);
          }
        }
        tip.textContent = tipText;
        tip.removeAttribute('hidden');
        tip.setAttribute('aria-hidden', 'false');

        tip.style.setProperty('display', 'block', 'important');
        tip.style.setProperty('visibility', 'visible', 'important');
        tip.style.setProperty('opacity', '1', 'important');
        tip.style.setProperty('color', '#6b7785', 'important');
        tip.style.setProperty('-webkit-text-fill-color', '#6b7785', 'important');
        tip.style.setProperty('background', 'transparent', 'important');
        tip.style.setProperty('background-color', 'transparent', 'important');
        tip.style.setProperty('font-size', '12px', 'important');
        tip.style.setProperty('line-height', '16px', 'important');
        tip.style.setProperty('font-weight', '500', 'important');
        tip.style.setProperty('margin', '0', 'important');
        tip.style.setProperty('padding', '6px 14px 8px', 'important');
        tip.style.setProperty('width', '100%', 'important');
        tip.style.setProperty('max-width', '100%', 'important');
        tip.style.setProperty('box-sizing', 'border-box', 'important');
        tip.style.setProperty('position', 'relative', 'important');
        tip.style.setProperty('height', 'auto', 'important');
        tip.style.setProperty('min-height', '0', 'important');
        tip.style.setProperty('overflow', 'visible', 'important');
        tip.style.setProperty('z-index', '6', 'important');
        tip.style.setProperty('flex', '0 0 auto', 'important');
        tip.style.setProperty('float', 'none', 'important');
        tip.style.setProperty('clear', 'both', 'important');
        tip.style.setProperty('text-indent', '0', 'important');
        tip.style.setProperty('transform', 'none', 'important');
        tip.style.setProperty('clip-path', 'none', 'important');
      }

      function enhanceAttachmentLinks($root) {
        var $scope = ($root && $root.jquery) ? $root : $(document);
        $scope.find('a.im-download').each(function () {
          var $a = $(this);
          $a.addClass('pngm-im-attach');
          if (!$a.find('.pngm-im-attach-name').length) {
            var label = $.trim($a.text());
            if (!label || /^attachment$/i.test(label)) {
              var href = $a.attr('href') || '';
              var parts = href.split('/');
              label = decodeURIComponent(parts[parts.length - 1] || '') || label || 'Attachment';
            }
            $a.contents().filter(function () { return this.nodeType === 3; }).remove();
            if (!$a.find('i.fa-paperclip, i.fas.fa-paperclip').length) {
              $a.prepend('<i class="fas fa-paperclip" aria-hidden="true"></i> ');
            }
            $a.find('img.im-att-icon').remove();
            $a.append($('<span class="pngm-im-attach-name"></span>').text(label));
          }
        });
      }

      function wireComposer($form) {
        if (!$form.length) {
          return;
        }
        relaxMessageRules($form);
        ensureSendHint($form);
        enhanceAttachmentLinks($(document));
        if (typeof window.pngmLayoutChat === 'function') {
          window.pngmLayoutChat({ pinBottom: true });
        }
      }

      // Replace plugin Ctrl+Enter=send with Enter=send / Ctrl+Enter=newline.
      $('body').off('keydown', 'textarea#im-message');
      $('body').off('keydown.pngmImKeys', 'textarea#im-message');
      $('body').on('keydown.pngmImKeys', 'textarea#im-message', function (e) {
        var isEnter = (e.key === 'Enter' || e.keyCode === 13);
        if (!isEnter) {
          return;
        }
        if (e.ctrlKey || e.metaKey) {
          // New line — let the browser insert it.
          return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();
        doSend($(this).closest('form'));
        return false;
      });

      wireComposer($('#im-message-form'));

      // Re-apply after AJAX board swaps.
      var prevAfter = window.pngmImAfterBoardSwap;
      window.pngmImAfterBoardSwap = function () {
        if (typeof prevAfter === 'function') {
          prevAfter();
        }
        wireComposer($('#im-message-form'));
        enhanceAttachmentLinks($(document));
      };
    });
  }

  initOptimisticSend();
})();
</script>
    <?php
}
