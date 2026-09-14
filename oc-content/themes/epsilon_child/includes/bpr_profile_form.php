<?php
if (!defined('ABS_PATH')) {
  exit;
}

if (!function_exists('pngm_ua_render_sidebar')) {
  require_once dirname(__FILE__) . '/account_ua.php';
}

$user_row = is_array($user) ? $user : osc_user();
$about_text = function_exists('osc_user_info') ? (string) osc_user_info() : '';
$about_len = function_exists('mb_strlen') ? mb_strlen(strip_tags($about_text)) : strlen(strip_tags($about_text));
$phone_val = '';
if (function_exists('osc_user_phone_mobile') && osc_user_phone_mobile() !== '') {
  $phone_val = (string) osc_user_phone_mobile();
} elseif (is_array($user_row) && !empty($user_row['s_phone_mobile'])) {
  $phone_val = (string) $user_row['s_phone_mobile'];
}
$phone_parts = function_exists('pngm_ua_phone_split') ? pngm_ua_phone_split($phone_val) : array('dial' => '675', 'local' => preg_replace('/\D+/', '', $phone_val), 'iso' => 'PG');
$phone_codes = function_exists('pngm_ua_phone_dial_codes') ? pngm_ua_phone_dial_codes() : array();

$biz_name = is_array($user_row) && !empty($user_row['s_name']) ? (string) $user_row['s_name'] : (string) osc_user_name();
$website = is_array($user_row) && isset($user_row['s_website']) ? (string) $user_row['s_website'] : '';
$address = is_array($user_row) && isset($user_row['s_address']) ? (string) $user_row['s_address'] : '';
$zip = is_array($user_row) && isset($user_row['s_zip']) ? (string) $user_row['s_zip'] : '';
$email = (string) osc_user_email();
$email_verified = is_array($user_row) && !empty($user_row['b_active']);

$bank_raw = osc_get_preference('bank_' . $user_id, 'plugin-business_profile');
$bank = array('bank_name' => '', 'account_name' => '', 'account_number' => '');
if (is_string($bank_raw) && $bank_raw !== '') {
  $decoded = json_decode($bank_raw, true);
  if (is_array($decoded)) {
    $bank = array_merge($bank, $decoded);
  }
}

$primary_cat = '';
if (!empty($categories)) {
  $primary_cat = (string) reset($categories);
}

$preview_url = function_exists('bpr_company_url') ? bpr_company_url($user_id) : false;
$remove_url = osc_route_url('bpr-profile-remove');

$gallery = (bpr_param('gallery') == 1) ? bpr_prepare_user_gallery($seller) : array();
$gallery_limit = (int) bpr_param('gallery_limit');
if ($gallery_limit < 1) {
  $gallery_limit = 8;
}

$name_parts = preg_split('/\s+/', trim($biz_name));
$initials = '';
if (is_array($name_parts)) {
  foreach (array_slice($name_parts, 0, 2) as $np) {
    $initials .= function_exists('mb_substr') ? mb_strtoupper(mb_substr($np, 0, 1)) : strtoupper(substr($np, 0, 1));
  }
}
if ($initials === '') {
  $initials = 'BP';
}

$has_icon = bpr_check_img($user_id, @$seller['s_icon'], 'icon');
$has_cover = bpr_check_img($user_id, @$seller['s_cover'], 'cover');

$pngm_bpr_parse_hours = function ($raw) {
  $raw = trim((string) $raw);
  if ($raw === '' || stripos($raw, 'closed') !== false) {
    return array('open' => false, 'from' => '09:00', 'to' => '17:00');
  }
  if (preg_match('/(\d{1,2}:\d{2})\s*[-–to]+\s*(\d{1,2}:\d{2})/i', $raw, $m)) {
    return array('open' => true, 'from' => $m[1], 'to' => $m[2]);
  }
  return array('open' => true, 'from' => '09:00', 'to' => '17:00');
};

$time_opts = array();
for ($h = 6; $h <= 22; $h++) {
  foreach (array('00', '30') as $m) {
    if ($h === 22 && $m === '30') {
      continue;
    }
    $val = sprintf('%02d:%s', $h, $m);
    $h12 = $h % 12;
    if ($h12 === 0) {
      $h12 = 12;
    }
    $ampm = ($h < 12) ? 'AM' : 'PM';
    $label = $h12 . ':' . $m . ' ' . $ampm;
    $time_opts[] = array('value' => $val, 'label' => $label);
  }
}

$normalize_time = function ($t) {
  $t = trim((string) $t);
  if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $t, $m)) {
    $h = (int) $m[1];
    $min = $m[2];
    $ap = strtoupper($m[3]);
    if ($ap === 'AM') {
      if ($h === 12) {
        $h = 0;
      }
    } else {
      if ($h !== 12) {
        $h += 12;
      }
    }
    return sprintf('%02d:%s', $h, $min);
  }
  if (preg_match('/^(\d{1,2}):(\d{2})$/', $t, $m)) {
    return sprintf('%02d:%s', (int) $m[1], $m[2]);
  }
  return $t;
};

$mf = isset($hours[1]) ? $pngm_bpr_parse_hours($hours[1]) : array('open' => true, 'from' => '08:00', 'to' => '17:00');
$sat = isset($hours[6]) ? $pngm_bpr_parse_hours($hours[6]) : array('open' => true, 'from' => '08:00', 'to' => '12:00');
$sun = isset($hours[7]) ? $pngm_bpr_parse_hours($hours[7]) : array('open' => false, 'from' => '09:00', 'to' => '17:00');
$mf['from'] = $normalize_time($mf['from']);
$mf['to'] = $normalize_time($mf['to']);
$sat['from'] = $normalize_time($sat['from']);
$sat['to'] = $normalize_time($sat['to']);
$sun['from'] = $normalize_time($sun['from']);
$sun['to'] = $normalize_time($sun['to']);

$soc_preferred = array('fb', 'ig', 'li', 'tw');
$soc_show = array();
foreach ($soc_preferred as $sid) {
  if (in_array($sid, $soc_list, true)) {
    $soc_show[] = $sid;
  }
}
foreach ($soc_list as $sid) {
  if (!in_array($sid, $soc_show, true) && count($soc_show) < 4) {
    $soc_show[] = $sid;
  }
}

$soc_fa = array(
  'fb' => 'fab fa-facebook-f',
  'ig' => 'fab fa-instagram',
  'li' => 'fab fa-linkedin-in',
  'tw' => 'fab fa-twitter',
  'yt' => 'fab fa-youtube',
  'vm' => 'fab fa-vimeo-v',
  'pn' => 'fab fa-pinterest-p',
  'ot' => 'fas fa-share-alt',
);

$countries = osc_get_countries();
$hide_country = !is_array($countries) || count($countries) <= 1;

UserForm::location_javascript();
?>

<div id="bpr-prof" class="bpr-body bpr-prof pngm-bpr">
  <?php if (!isset($seller['pk_i_id']) || $seller['pk_i_id'] <= 0) { ?>
    <div class="bpr-msg-wrap pngm-bpr-flash"><div class="bpr-msg"><?php _e('Create and submit your business profile to attract more customers. It just take 3 minutes!', 'business_profile'); ?></div></div>
  <?php } else if (bpr_param('require_validation') == 1 && bpr_param('auto_validate') != 1 && @$seller['b_enabled'] == 0) { ?>
    <div class="bpr-msg-wrap pngm-bpr-flash"><div class="bpr-msg"><?php _e('Your business profile is pending validation by admin', 'business_profile'); ?></div></div>
  <?php } ?>

  <div class="pngm-bpr-head">
    <h1><?php _e('Business Profile', 'epsilon'); ?></h1>
    <p><?php _e('Manage your business information and settings', 'epsilon'); ?></p>
  </div>

  <form class="nocsrf pngm-bpr-form" method="POST" name="bpr_profile" id="bpr_profile" action="<?php echo osc_route_url('bpr-profile'); ?>" enctype="multipart/form-data">
    <input type="hidden" name="what" value="profile" />
    <input type="hidden" name="user_id" value="<?php echo (int) osc_logged_user_id(); ?>" />
    <input type="hidden" name="seller_id" value="<?php echo (int) @$seller['pk_i_id']; ?>" />
    <input type="hidden" name="bpr-oh-mode" value="1" />
    <input type="hidden" name="bpr-color" id="bpr-color" value="<?php echo osc_esc_html(@$seller['s_color']); ?>" />

    <?php if (isset($seller['pk_i_id']) && $seller['pk_i_id'] > 0 && $seller['b_enabled'] != 1 && !bpr_control_user($seller) && bpr_premium_groups() !== false && is_array(bpr_premium_groups()) && count(bpr_premium_groups()) > 0) { ?>
      <div class="bpr-is-premium pngm-bpr-premium">
        <div class="bpr-p-title"><?php _e('In order to enable your profile, you must purchase membership in one of the following groups:', 'business_profile'); ?></div>
        <div class="bpr-grp-wrap">
          <?php foreach (bpr_premium_groups() as $g) { ?>
            <a class="bpr-grp bpr-has-tooltip" href="<?php echo osp_cart_add(OSP_TYPE_MEMBERSHIP, 1, $g['pk_i_id'], $g['i_days']); ?>" title="<?php echo osc_esc_html(__('Click to add membership to cart', 'business_profile')); ?>">
              <div class="bpr-grp-i" style="background:<?php echo $g['s_color']; ?>"></div>
              <i class="bpr-grp-fa fa fa-shopping-basket"></i>
              <div class="bpr-grp-t"><?php echo $g['s_name']; ?></div>
              <div class="bpr-grp-d"><?php echo sprintf(__('%s for %s days', 'business_profile'), osp_format_price($g['f_price']), $g['i_days']); ?></div>
            </a>
          <?php } ?>
        </div>
      </div>
    <?php } ?>

    <section class="pngm-bpr-sec">
      <h2 class="pngm-bpr-h2"><?php _e('Business Information', 'epsilon'); ?></h2>
      <div class="pngm-bpr-grid pngm-bpr-grid-2">
        <div class="pngm-bpr-field">
          <label for="bpr-business-name"><?php _e('Business Name', 'epsilon'); ?> <span class="req">*</span></label>
          <input type="text" name="bpr-business-name" id="bpr-business-name" value="<?php echo osc_esc_html($biz_name); ?>" required />
        </div>
        <div class="pngm-bpr-field">
          <label for="bpr-category-select"><?php _e('Category', 'epsilon'); ?> <span class="req">*</span></label>
          <div class="pngm-bpr-select">
            <input type="hidden" name="bpr-category" id="bpr-category" value="<?php echo osc_esc_html($primary_cat); ?>" />
            <select id="bpr-category-select" aria-label="<?php echo osc_esc_html(__('Category', 'epsilon')); ?>">
              <option value=""><?php _e('Select category', 'epsilon'); ?></option>
              <?php osc_goto_first_category(); ?>
              <?php while (osc_has_categories()) { ?>
                <option value="<?php echo (int) osc_category_id(); ?>"<?php echo ((string) osc_category_id() === (string) $primary_cat) ? ' selected' : ''; ?>>
                  <?php echo osc_esc_html(osc_category_name()); ?>
                </option>
                <?php while (osc_has_subcategories()) { ?>
                  <option value="<?php echo (int) osc_category_id(); ?>"<?php echo ((string) osc_category_id() === (string) $primary_cat) ? ' selected' : ''; ?>>
                    - <?php echo osc_esc_html(osc_category_name()); ?>
                  </option>
                <?php } ?>
              <?php } ?>
            </select>
          </div>
        </div>
      </div>

      <div class="pngm-bpr-about-block">
        <div class="pngm-bpr-field pngm-bpr-about">
          <label for="bpr-about"><?php _e('Short Description', 'epsilon'); ?></label>
          <div class="pngm-bpr-textarea-wrap">
            <textarea name="bpr-about" id="bpr-about" rows="4" maxlength="500" placeholder="<?php echo osc_esc_html(__('Briefly describe your business...', 'epsilon')); ?>"><?php echo osc_esc_html($about_text); ?></textarea>
            <span class="pngm-bpr-counter" id="bpr-about-counter"><?php echo (int) min($about_len, 500); ?>/500</span>
          </div>
        </div>
      </div>

      <div class="pngm-bpr-media-block">
        <div class="pngm-bpr-media">
          <div class="pngm-bpr-logo">
            <label><?php _e('Logo', 'epsilon'); ?></label>
            <div class="pngm-bpr-logo-row">
              <div class="pngm-bpr-logo-preview" id="pngm-bpr-logo-preview">
                <?php if ($has_icon) { ?>
                  <img src="<?php echo osc_esc_html($icon); ?>" alt="" />
                <?php } else { ?>
                  <span class="pngm-bpr-initials"><?php echo osc_esc_html($initials); ?></span>
                <?php } ?>
              </div>
              <div class="pngm-bpr-logo-meta">
                <label class="pngm-bpr-btn-outline" for="bpr-file-icon"><?php _e('Change', 'epsilon'); ?></label>
                <input type="file" name="bpr-file-icon" id="bpr-file-icon" class="bpr-file-icon pngm-bpr-file-hidden" accept="image/*,.jpg,.jpeg,.jfif,.png,.gif,.webp,.bmp,.avif" />
                <p class="pngm-bpr-hint"><?php _e('JPG, PNG. Max 2MB.', 'epsilon'); ?></p>
                <?php if ($has_icon) { ?>
                  <a class="pngm-bpr-rem" href="<?php echo osc_route_url('bpr-profile-remove-img', array('removeImageType' => 'icon')); ?>"><?php _e('Remove', 'epsilon'); ?></a>
                <?php } ?>
              </div>
            </div>
          </div>

          <div class="pngm-bpr-cover">
            <label><?php _e('Cover Image', 'epsilon'); ?></label>
            <div class="pngm-bpr-cover-box">
              <div class="pngm-bpr-cover-preview" id="pngm-bpr-cover-preview">
                <?php if ($has_cover) { ?>
                  <img src="<?php echo osc_esc_html($cover); ?>" alt="" />
                <?php } else { ?>
                  <span class="pngm-bpr-cover-ph"><?php _e('No cover yet', 'epsilon'); ?></span>
                <?php } ?>
              </div>
              <label class="pngm-bpr-cover-upload" for="bpr-file-cover" title="<?php echo osc_esc_html(__('Upload cover', 'epsilon')); ?>">
                <i class="fas fa-camera" aria-hidden="true"></i>
              </label>
              <input type="file" name="bpr-file-cover" id="bpr-file-cover" class="bpr-file-cover pngm-bpr-file-hidden" accept="image/*,.jpg,.jpeg,.jfif,.png,.gif,.webp,.bmp,.avif" />
            </div>
            <p class="pngm-bpr-hint"><?php _e('JPG, PNG. Max 5MB.', 'epsilon'); ?></p>
            <?php if ($has_cover) { ?>
              <a class="pngm-bpr-rem" href="<?php echo osc_route_url('bpr-profile-remove-img', array('removeImageType' => 'cover')); ?>"><?php _e('Remove', 'epsilon'); ?></a>
            <?php } ?>
          </div>
        </div>
      </div>
    </section>

    <section class="pngm-bpr-sec pngm-bpr-sec-contact">
      <h2 class="pngm-bpr-h2 pngm-bpr-h2-desk"><?php _e('Contact Details', 'epsilon'); ?></h2>
      <details class="pngm-bpr-accord" open>
        <summary class="pngm-bpr-summary"><span><?php _e('Contact Details', 'epsilon'); ?></span><i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
        <div class="pngm-bpr-accord-body">
          <div class="pngm-bpr-grid pngm-bpr-grid-2 pngm-bpr-contact-row">
            <div class="pngm-bpr-field">
              <label for="pngm_phone_local"><?php _e('Phone Number', 'epsilon'); ?></label>
              <div class="pngm-bpr-control pngm-bpr-phone">
                <label class="pngm-bpr-phone-cc" for="pngm_phone_dial">
                  <span class="pngm-bpr-phone-flag" id="pngm-bpr-phone-flag" aria-hidden="true"><?php
                    $cur_flag = '🇵🇬';
                    foreach ($phone_codes as $cc) {
                      if ((string) $cc['dial'] === (string) $phone_parts['dial']) {
                        $cur_flag = $cc['flag'];
                        break;
                      }
                    }
                    echo $cur_flag;
                  ?></span>
                  <select name="pngm_phone_dial" id="pngm_phone_dial" aria-label="<?php echo osc_esc_html(__('Country calling code', 'epsilon')); ?>">
                    <?php foreach ($phone_codes as $cc) {
                      $sel = ((string) $cc['dial'] === (string) $phone_parts['dial']) ? ' selected' : '';
                    ?>
                      <option value="<?php echo osc_esc_html($cc['dial']); ?>" data-iso="<?php echo osc_esc_html($cc['iso']); ?>" data-flag="<?php echo osc_esc_html($cc['flag']); ?>"<?php echo $sel; ?>>
                        <?php echo osc_esc_html($cc['flag'] . ' +' . $cc['dial']); ?>
                      </option>
                    <?php } ?>
                  </select>
                </label>
                <span class="pngm-bpr-phone-prefix" id="pngm-bpr-phone-prefix">+<?php echo osc_esc_html($phone_parts['dial']); ?></span>
                <input type="text" name="pngm_phone_local" id="pngm_phone_local" inputmode="tel" autocomplete="tel-national" value="<?php echo osc_esc_html($phone_parts['local']); ?>" placeholder="<?php echo osc_esc_html(__('Phone number', 'epsilon')); ?>" />
              </div>
            </div>
            <div class="pngm-bpr-field">
              <label for="bpr-email"><?php _e('Email', 'epsilon'); ?></label>
              <div class="pngm-bpr-control<?php echo $email_verified ? ' has-badge' : ''; ?>">
                <input type="email" id="bpr-email" value="<?php echo osc_esc_html($email); ?>" disabled />
                <?php if ($email_verified) { ?>
                  <span class="pngm-profile-badge"><i class="fas fa-check" aria-hidden="true"></i> <?php _e('Verified', 'epsilon'); ?></span>
                <?php } ?>
              </div>
            </div>
          </div>
          <div class="pngm-bpr-field">
            <label for="bpr-website"><?php _e('Website', 'epsilon'); ?></label>
            <div class="pngm-bpr-control">
              <input type="url" name="bpr-website" id="bpr-website" value="<?php echo osc_esc_html($website); ?>" placeholder="https://" />
            </div>
          </div>
        </div>
      </details>
    </section>

    <section class="pngm-bpr-sec">
      <h2 class="pngm-bpr-h2 pngm-bpr-h2-desk"><?php _e('Address & Location', 'epsilon'); ?></h2>
      <details class="pngm-bpr-accord" open>
        <summary class="pngm-bpr-summary"><span><?php _e('Address & Location', 'epsilon'); ?></span><i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
        <div class="pngm-bpr-accord-body">
          <div class="pngm-bpr-field<?php echo $hide_country ? ' is-country-auto' : ''; ?>">
            <label for="countryId"><?php _e('Country', 'epsilon'); ?></label>
            <div class="pngm-bpr-select"><?php UserForm::country_select($countries, $user_row); ?></div>
          </div>
          <div class="pngm-bpr-grid pngm-bpr-grid-2">
            <div class="pngm-bpr-field">
              <label for="regionId"><?php _e('Province / Region', 'epsilon'); ?></label>
              <div class="pngm-bpr-select"><?php UserForm::region_select(osc_get_regions(), $user_row); ?></div>
            </div>
            <div class="pngm-bpr-field">
              <label for="cityId"><?php _e('City / Location', 'epsilon'); ?></label>
              <div class="pngm-bpr-select"><?php UserForm::city_select(osc_get_cities(), $user_row); ?></div>
            </div>
          </div>
          <div class="pngm-bpr-grid pngm-bpr-grid-address">
            <div class="pngm-bpr-field">
              <label for="bpr-address"><?php _e('Street Address', 'epsilon'); ?></label>
              <input type="text" name="bpr-address" id="bpr-address" value="<?php echo osc_esc_html($address); ?>" />
            </div>
            <div class="pngm-bpr-field">
              <label for="bpr-zip"><?php _e('ZIP / Postcode', 'epsilon'); ?></label>
              <input type="text" name="bpr-zip" id="bpr-zip" value="<?php echo osc_esc_html($zip); ?>" />
            </div>
          </div>
        </div>
      </details>
    </section>

    <section class="pngm-bpr-sec">
      <h2 class="pngm-bpr-h2 pngm-bpr-h2-desk"><?php _e('Business Hours', 'epsilon'); ?></h2>
      <details class="pngm-bpr-accord" open>
        <summary class="pngm-bpr-summary"><span><?php _e('Business Hours', 'epsilon'); ?></span><i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
        <div class="pngm-bpr-accord-body">
          <?php
            $hour_rows = array(
              array('key' => 'mf', 'label' => __('Mon - Fri', 'epsilon'), 'data' => $mf, 'days' => range(1, 5)),
              array('key' => '6', 'label' => __('Saturday', 'epsilon'), 'data' => $sat, 'days' => array(6)),
              array('key' => '7', 'label' => __('Sunday', 'epsilon'), 'data' => $sun, 'days' => array(7)),
            );
            foreach ($hour_rows as $row) {
              $k = $row['key'];
              $d = $row['data'];
              $is_open = !empty($d['open']);
              $from_name = ($k === 'mf') ? 'bpr-oh-from_mf' : ('bpr-oh-from_' . $k);
              $to_name = ($k === 'mf') ? 'bpr-oh-to_mf' : ('bpr-oh-to_' . $k);
              $open_name = ($k === 'mf') ? 'bpr-oh-open_mf' : ('bpr-oh-open_' . $k);
          ?>
            <div class="pngm-bpr-hours-row<?php echo $is_open ? ' is-open' : ' is-closed'; ?>" data-hours-row="<?php echo osc_esc_html($k); ?>">
              <div class="pngm-bpr-hours-day"><?php echo osc_esc_html($row['label']); ?></div>
              <div class="pngm-bpr-hours-times">
                <div class="pngm-bpr-select pngm-bpr-hours-from">
                  <select name="<?php echo osc_esc_html($from_name); ?>" <?php echo $is_open ? '' : 'disabled'; ?>>
                    <?php if (!$is_open && $k === '7') { ?>
                      <option value="Closed" selected><?php _e('Closed', 'epsilon'); ?></option>
                    <?php } ?>
                    <?php foreach ($time_opts as $t) { ?>
                      <option value="<?php echo osc_esc_html($t['value']); ?>"<?php echo ($is_open && $t['value'] === $d['from']) ? ' selected' : ''; ?>><?php echo osc_esc_html($t['label']); ?></option>
                    <?php } ?>
                  </select>
                </div>
                <span class="pngm-bpr-hours-dash">–</span>
                <div class="pngm-bpr-select pngm-bpr-hours-to">
                  <select name="<?php echo osc_esc_html($to_name); ?>" <?php echo $is_open ? '' : 'disabled'; ?>>
                    <?php foreach ($time_opts as $t) { ?>
                      <option value="<?php echo osc_esc_html($t['value']); ?>"<?php echo ($is_open && $t['value'] === $d['to']) ? ' selected' : ''; ?>><?php echo osc_esc_html($t['label']); ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <label class="pngm-bpr-switch">
                <input type="checkbox" class="pngm-bpr-oh-toggle" name="<?php echo osc_esc_html($open_name); ?>" value="1" <?php echo $is_open ? 'checked' : ''; ?> />
                <span class="pngm-bpr-switch-ui" aria-hidden="true"></span>
                <span class="pngm-ua-sr"><?php _e('Open', 'epsilon'); ?></span>
              </label>
            </div>
          <?php } ?>
        </div>
      </details>
    </section>

    <section class="pngm-bpr-sec">
      <h2 class="pngm-bpr-h2 pngm-bpr-h2-desk"><?php _e('Payment & Business Details', 'epsilon'); ?></h2>
      <details class="pngm-bpr-accord" open>
        <summary class="pngm-bpr-summary"><span><?php _e('Payment & Business Details', 'epsilon'); ?></span><i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
        <div class="pngm-bpr-accord-body">
          <div class="pngm-bpr-field">
            <label><?php _e('Payment Methods', 'epsilon'); ?></label>
            <div class="pngm-bpr-pay-chips" id="pngm-bpr-pay-chips">
              <?php foreach ($pay_list as $p) {
                $pid = $p['pk_i_id'];
                $plabel = bpr_field($p['locales']);
                $checked = in_array($pid, $payments) || in_array((string) $pid, $payments, true);
              ?>
                <label class="pngm-bpr-chip<?php echo $checked ? ' is-on' : ''; ?>">
                  <input type="checkbox" class="bpr-input-check bpr-pay" name="bpr-pay_<?php echo (int) $pid; ?>" id="bpr-pay_<?php echo (int) $pid; ?>" value="1" <?php echo $checked ? 'checked' : ''; ?> />
                  <span><?php echo osc_esc_html($plabel); ?></span>
                  <i class="fas fa-times" aria-hidden="true"></i>
                </label>
              <?php } ?>
            </div>
          </div>

          <div class="pngm-bpr-grid pngm-bpr-grid-bank">
            <div class="pngm-bpr-field">
              <label for="bpr-bank-name"><?php _e('Bank Name', 'epsilon'); ?></label>
              <input type="text" name="bpr-bank-name" id="bpr-bank-name" value="<?php echo osc_esc_html($bank['bank_name']); ?>" />
            </div>
            <div class="pngm-bpr-field">
              <label for="bpr-account-name"><?php _e('Account Name', 'epsilon'); ?></label>
              <input type="text" name="bpr-account-name" id="bpr-account-name" value="<?php echo osc_esc_html($bank['account_name']); ?>" />
            </div>
            <div class="pngm-bpr-field">
              <label for="bpr-account-number"><?php _e('Account Number', 'epsilon'); ?></label>
              <input type="text" name="bpr-account-number" id="bpr-account-number" value="<?php echo osc_esc_html($bank['account_number']); ?>" />
            </div>
          </div>

          <?php if (!empty($feat_list)) { ?>
            <div class="pngm-bpr-field">
              <label><?php _e('Features', 'epsilon'); ?></label>
              <div class="pngm-bpr-pay-chips">
                <?php foreach ($feat_list as $f) {
                  $fid = $f['pk_i_id'];
                  $flabel = bpr_field($f['locales']);
                  $fchecked = in_array($fid, $features) || in_array((string) $fid, $features, true);
                ?>
                  <label class="pngm-bpr-chip<?php echo $fchecked ? ' is-on' : ''; ?>">
                    <input type="checkbox" class="bpr-input-check bpr-feat" name="bpr-feat_<?php echo (int) $fid; ?>" id="bpr-feat_<?php echo (int) $fid; ?>" value="1" <?php echo $fchecked ? 'checked' : ''; ?> />
                    <span><?php echo osc_esc_html($flabel); ?></span>
                    <i class="fas fa-times" aria-hidden="true"></i>
                  </label>
                <?php } ?>
              </div>
            </div>
          <?php } ?>

          <?php if (bpr_param('legal_notice') == 1) { ?>
            <div class="pngm-bpr-field">
              <label for="bpr-legal_notice"><?php _e('Legal notice', 'business_profile'); ?></label>
              <textarea name="bpr-legal_notice" id="bpr-legal_notice" rows="3"><?php echo osc_esc_html(@$seller['s_legal_notice']); ?></textarea>
            </div>
          <?php } ?>
        </div>
      </details>
    </section>

    <?php if (bpr_param('gallery') == 1) { ?>
      <section class="pngm-bpr-sec">
        <h2 class="pngm-bpr-h2 pngm-bpr-h2-desk"><?php _e('Gallery', 'epsilon'); ?></h2>
        <details class="pngm-bpr-accord" open>
          <summary class="pngm-bpr-summary"><span><?php _e('Gallery', 'epsilon'); ?></span><i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
          <div class="pngm-bpr-accord-body">
            <div class="pngm-bpr-gallery" id="pngm-bpr-gallery">
              <?php foreach ($gallery as $img) { ?>
                <div class="pngm-bpr-gal-item">
                  <img src="<?php echo osc_esc_html($img['url']); ?>" alt="<?php echo osc_esc_html($img['title']); ?>" />
                  <a class="pngm-bpr-gal-del" href="<?php echo osc_route_url('bpr-profile-remove-gallery-img', array('removeGalleryImage' => $img['name'])); ?>" title="<?php echo osc_esc_html(__('Remove', 'epsilon')); ?>"><i class="fas fa-trash"></i></a>
                </div>
              <?php } ?>
              <?php if (count($gallery) < $gallery_limit) { ?>
                <label class="pngm-bpr-gal-add" for="bpr-files-gallery">
                  <i class="fas fa-plus" aria-hidden="true"></i>
                  <span><?php _e('Add', 'epsilon'); ?></span>
                </label>
                <input type="file" name="bpr-files-gallery[]" id="bpr-files-gallery" class="bpr-files-gallery pngm-bpr-file-hidden" multiple accept="image/*,.jpg,.jpeg,.jfif,.png,.gif,.webp,.bmp,.avif" />
              <?php } ?>
            </div>
            <p class="pngm-bpr-hint pngm-bpr-gal-hint" id="pngm-bpr-gal-hint" hidden></p>
          </div>
        </details>
      </section>
    <?php } ?>

    <?php if (bpr_param('video') == 1 && bpr_param('video_limit') > 0) { ?>
      <section class="pngm-bpr-sec pngm-bpr-sec-extra">
        <h2 class="pngm-bpr-h2 pngm-bpr-h2-desk"><?php _e('Youtube Videos', 'business_profile'); ?></h2>
        <details class="pngm-bpr-accord">
          <summary class="pngm-bpr-summary"><span><?php _e('Youtube Videos', 'business_profile'); ?></span><i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
          <div class="pngm-bpr-accord-body">
            <?php for ($i = 1; $i <= bpr_param('video_limit'); $i++) { ?>
              <div class="pngm-bpr-field">
                <label for="bpr-video_<?php echo $i; ?>"><?php echo sprintf(__('Video #%d', 'business_profile'), $i); ?></label>
                <input type="text" class="bpr-input bpr-video" name="bpr-video_<?php echo $i; ?>" id="bpr-video_<?php echo $i; ?>" value="<?php echo osc_esc_html(isset($video_list[$i - 1]) ? $video_list[$i - 1] : ''); ?>" placeholder="https://www.youtube.com/embed/xxyyzz" />
              </div>
            <?php } ?>
          </div>
        </details>
      </section>
    <?php } ?>

    <section class="pngm-bpr-sec">
      <h2 class="pngm-bpr-h2"><?php _e('Social Accounts', 'epsilon'); ?> <span class="pngm-bpr-optional">(<?php _e('Optional', 'epsilon'); ?>)</span></h2>
      <div class="pngm-bpr-grid pngm-bpr-grid-2 pngm-bpr-social">
        <?php foreach ($soc_show as $s) {
          $icon_class = isset($soc_fa[$s]) ? $soc_fa[$s] : 'fas fa-link';
          $val = isset($socials[$s]) ? $socials[$s] : '';
        ?>
          <div class="pngm-bpr-field">
            <label class="pngm-ua-sr" for="bpr-soc_<?php echo osc_esc_html($s); ?>"><?php echo osc_esc_html(bpr_soc_icon($s, 'text')); ?></label>
            <div class="pngm-bpr-social-input">
              <span class="pngm-bpr-social-ico" aria-hidden="true"><i class="<?php echo osc_esc_html($icon_class); ?>"></i></span>
              <input type="text" class="bpr-input bpr-soc" name="bpr-soc_<?php echo osc_esc_html($s); ?>" id="bpr-soc_<?php echo osc_esc_html($s); ?>" value="<?php echo osc_esc_html($val); ?>" placeholder="<?php echo osc_esc_html(bpr_soc_icon($s, 'text')); ?>" />
            </div>
          </div>
        <?php } ?>
        <?php foreach ($soc_list as $s) {
          if (in_array($s, $soc_show, true)) {
            continue;
          }
          $val = isset($socials[$s]) ? $socials[$s] : '';
        ?>
          <input type="hidden" name="bpr-soc_<?php echo osc_esc_html($s); ?>" value="<?php echo osc_esc_html($val); ?>" />
        <?php } ?>
      </div>
    </section>

    <div class="pngm-bpr-actions">
      <?php if (bpr_is_demo()) { ?>
        <button class="pngm-bpr-btn pngm-bpr-btn-primary" type="button" disabled><?php _e('Save Changes', 'epsilon'); ?></button>
      <?php } else { ?>
        <button class="pngm-bpr-btn pngm-bpr-btn-primary" type="submit"><?php _e('Save Changes', 'epsilon'); ?></button>
      <?php } ?>

      <?php if ($preview_url !== false) { ?>
        <a class="pngm-bpr-btn pngm-bpr-btn-secondary" href="<?php echo osc_esc_html($preview_url); ?>" target="_blank" rel="noopener"><?php _e('Preview Public Profile', 'epsilon'); ?></a>
      <?php } else { ?>
        <a class="pngm-bpr-btn pngm-bpr-btn-secondary is-disabled" href="#" aria-disabled="true" title="<?php echo osc_esc_html(__('You have not submitted your profile yet', 'business_profile')); ?>"><?php _e('Preview Public Profile', 'epsilon'); ?></a>
      <?php } ?>

      <?php if (bpr_param('user_can_remove_profile') == 1 && !bpr_is_demo()) { ?>
        <a class="pngm-bpr-btn pngm-bpr-btn-danger" href="<?php echo osc_esc_html($remove_url); ?>" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to remove your business profile? Action cannot be undone')); ?>');"><?php _e('Delete Business Profile', 'epsilon'); ?></a>
      <?php } ?>
    </div>
  </form>
</div>

<script>
(function ($) {
  if (!$ || !$.fn) return;

  $('#bpr-category-select').on('change', function () {
    $('#bpr-category').val($(this).val() || '');
  }).trigger('change');

  $('#bpr-about').on('input', function () {
    var n = String($(this).val() || '').length;
    $('#bpr-about-counter').text(Math.min(n, 500) + '/500');
  });

  function syncBprPhoneDial() {
    var $opt = $('#pngm_phone_dial option:selected');
    var dial = String($opt.val() || '675').replace(/\D+/g, '') || '675';
    var flag = $opt.attr('data-flag') || '🇵🇬';
    $('#pngm-bpr-phone-flag').text(flag);
    $('#pngm-bpr-phone-prefix').text('+' + dial);
  }
  $('#pngm_phone_dial').on('change', syncBprPhoneDial);
  syncBprPhoneDial();

  function previewFile(input, $wrap) {
    var file = input.files && input.files[0];
    if (!file || !window.FileReader) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      $wrap.html('<img src="' + e.target.result + '" alt="" />');
    };
    reader.readAsDataURL(file);
  }

  $('#bpr-file-icon').on('change', function () {
    previewFile(this, $('#pngm-bpr-logo-preview'));
  });
  $('#bpr-file-cover').on('change', function () {
    previewFile(this, $('#pngm-bpr-cover-preview'));
  });

  $('#bpr-files-gallery').on('change', function () {
    var files = this.files || [];
    var $gal = $('#pngm-bpr-gallery');
    $gal.find('.pngm-bpr-gal-pending').remove();
    var names = [];
    for (var i = 0; i < files.length; i++) {
      names.push(files[i].name);
      (function (file) {
        if (!window.FileReader || !file.type || file.type.indexOf('image/') !== 0) {
          var $item = $('<div class="pngm-bpr-gal-item pngm-bpr-gal-pending"><span class="pngm-bpr-gal-pending-name"></span></div>');
          $item.find('.pngm-bpr-gal-pending-name').text(file.name);
          $gal.find('.pngm-bpr-gal-add').before($item);
          return;
        }
        var reader = new FileReader();
        reader.onload = function (e) {
          var $item = $('<div class="pngm-bpr-gal-item pngm-bpr-gal-pending"><img alt="" /><em><?php echo osc_esc_js(__('Pending save', 'epsilon')); ?></em></div>');
          $item.find('img').attr('src', e.target.result);
          $gal.find('.pngm-bpr-gal-add').before($item);
        };
        reader.readAsDataURL(file);
      })(files[i]);
    }
    var $hint = $('#pngm-bpr-gal-hint');
    if (names.length) {
      $hint.text(names.length + ' <?php echo osc_esc_js(__('file(s) selected — click Save Changes to upload', 'epsilon')); ?>').prop('hidden', false);
    } else {
      $hint.prop('hidden', true).text('');
    }
  });

  $('.pngm-bpr-pay-chips').on('change', 'input[type="checkbox"]', function () {
    $(this).closest('.pngm-bpr-chip').toggleClass('is-on', this.checked);
  });

  function syncHoursRow($row) {
    var on = $row.find('.pngm-bpr-oh-toggle').is(':checked');
    $row.toggleClass('is-open', on).toggleClass('is-closed', !on);
    $row.find('.pngm-bpr-hours-from select, .pngm-bpr-hours-to select').prop('disabled', !on);
    var $from = $row.find('.pngm-bpr-hours-from select');
    if (!on && $row.data('hours-row') === 7) {
      if ($from.find('option[value="Closed"]').length === 0) {
        $from.prepend('<option value="Closed"><?php echo osc_esc_js(__('Closed', 'epsilon')); ?></option>');
      }
      $from.val('Closed').prop('disabled', false);
      $row.find('.pngm-bpr-hours-to select').prop('disabled', true);
    } else if (on) {
      $from.find('option[value="Closed"]').remove();
      if (!$from.val() || $from.val() === 'Closed') {
        $from.val('08:00');
      }
    }
  }

  $('.pngm-bpr-hours-row').each(function () { syncHoursRow($(this)); });
  $('.pngm-bpr-oh-toggle').on('change', function () {
    syncHoursRow($(this).closest('.pngm-bpr-hours-row'));
  });

  $('#bpr_profile').on('submit', function () {
    $('.pngm-bpr-hours-row').each(function () {
      var $row = $(this);
      var on = $row.find('.pngm-bpr-oh-toggle').is(':checked');
      $row.find('select').prop('disabled', false);
      if (!on) {
        $row.find('.pngm-bpr-hours-from select').val('Closed');
      }
    });
  });

  if (window.matchMedia('(max-width: 767px)').matches) {
    $('.pngm-bpr-accord').each(function () { this.open = false; });
  }

  $('.pngm-bpr-accord > summary').on('click', function () {
    $(this).closest('details').data('user-toggled', true);
  });
})(window.jQuery);
</script>
