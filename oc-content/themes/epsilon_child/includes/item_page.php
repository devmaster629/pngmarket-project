<?php
/**
 * ITEM-01..04 — Listing gallery helpers, seller contacts, similar listings.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'item_page.php'
) {
    exit;
}

/**
 * Whether the current viewer may see seller contact channels (phone, WhatsApp, chat).
 * Guests see the same contact details as signed-in users.
 *
 * @return bool
 */
function pngm_viewer_can_contact_seller()
{
    return true;
}

/**
 * Digits-only phone for WhatsApp (PNG defaults to country code 675).
 *
 * @param string $phone
 * @return string empty if unusable
 */
function pngm_whatsapp_digits($phone)
{
    $digits = preg_replace('/\D+/', '', (string) $phone);

    if ($digits === '' || strlen($digits) < 7) {
        return '';
    }

    // Already international.
    if (strpos($digits, '675') === 0 && strlen($digits) >= 10) {
        return $digits;
    }

    // Local PNG mobile often starts with 7.
    if ($digits[0] === '0') {
        $digits = substr($digits, 1);
    }

    if (strpos($digits, '675') !== 0) {
        $digits = '675' . $digits;
    }

    return $digits;
}

/**
 * Preference key for listing WhatsApp opt-in (QD-004).
 *
 * @param int $item_id
 * @return string
 */
function pngm_item_whatsapp_pref_key($item_id)
{
    return 'wa_' . (int) $item_id;
}

/**
 * Whether the seller opted in to show WhatsApp on this listing.
 * Default is off — no public wa.me link without consent.
 *
 * @param int $item_id
 * @return bool
 */
function pngm_item_whatsapp_enabled($item_id = 0)
{
    $item_id = (int) $item_id;
    if ($item_id <= 0 && function_exists('osc_item_id')) {
        $item_id = (int) osc_item_id();
    }
    if ($item_id <= 0) {
        return false;
    }

    // Theme preference (source of truth for epsilon_child UI).
    if (function_exists('osc_get_preference')) {
        $pref = osc_get_preference(pngm_item_whatsapp_pref_key($item_id), 'pngm_whatsapp');
        if ($pref !== '' && $pref !== null && $pref !== false) {
            return ((string) $pref === '1' || (int) $pref === 1);
        }
    }

    // Fallback: wa_chat plugin row when present.
    if (class_exists('ModelWAC')) {
        try {
            $data = ModelWAC::newInstance()->getData($item_id);
            if (is_array($data) && isset($data['b_enable'])) {
                return ((int) $data['b_enable'] === 1);
            }
        } catch (Throwable $e) {
            // Plugin table may be missing.
        }
    }

    return false;
}

/**
 * Persist WhatsApp opt-in from the post/edit form checkbox.
 *
 * @param array $item
 */
function pngm_item_whatsapp_save($item)
{
    $item_id = 0;
    if (is_array($item) && isset($item['pk_i_id'])) {
        $item_id = (int) $item['pk_i_id'];
    }
    if ($item_id <= 0) {
        return;
    }

    $enabled = 0;
    if (isset($_POST['pngm_whatsapp']) && (string) $_POST['pngm_whatsapp'] !== '') {
        $enabled = 1;
    } elseif (class_exists('Params') && Params::getParam('pngm_whatsapp') !== '') {
        $enabled = 1;
    }

    if (function_exists('osc_set_preference')) {
        osc_set_preference(pngm_item_whatsapp_pref_key($item_id), (string) $enabled, 'pngm_whatsapp', 'BOOLEAN');
        if (class_exists('Preference')) {
            Preference::newInstance()->set(pngm_item_whatsapp_pref_key($item_id), (string) $enabled, 'pngm_whatsapp');
        }
    }

    // Keep wa_chat plugin row in sync when available.
    if (class_exists('ModelWAC')) {
        try {
            $model = ModelWAC::newInstance();
            $data = $model->getData($item_id);
            if (is_array($data) && isset($data['fk_i_item_id'])) {
                $model->updateData($item_id, array('b_enable' => $enabled));
            } else {
                $model->insertData(array(
                    'fk_i_item_id' => $item_id,
                    'b_enable' => $enabled,
                ));
            }
        } catch (Throwable $e) {
            // Ignore plugin sync failures.
        }
    }
}

if (function_exists('osc_add_hook')) {
    // Run after wa_chat's posted_item/edited_item (default priority 5) so ModelWAC stays in sync
    // with the contact-section checkbox, not a hidden plugin field.
    osc_add_hook('posted_item', 'pngm_item_whatsapp_save', 9);
    osc_add_hook('edited_item', 'pngm_item_whatsapp_save', 9);
    osc_add_hook('posted_item', 'pngm_item_contact_pref_save', 9);
    osc_add_hook('edited_item', 'pngm_item_contact_pref_save', 9);
    osc_add_hook('posted_item', 'pngm_item_call_availability_save', 9);
    osc_add_hook('edited_item', 'pngm_item_call_availability_save', 9);
    osc_add_hook('posted_item', 'pngm_item_message_prefs_save', 9);
    osc_add_hook('edited_item', 'pngm_item_message_prefs_save', 9);
}

/**
 * Preference key: allow buyers to message the seller about this listing.
 *
 * @param int $item_id
 * @return string
 */
function pngm_item_allow_messages_key($item_id)
{
    return 'allow_msg_' . (int) $item_id;
}

/**
 * Whether buyers may start a site messenger chat for this listing.
 * Missing value means yes (older listings).
 *
 * @param int $item_id
 * @return bool
 */
function pngm_item_allow_messages_enabled($item_id)
{
    $item_id = (int) $item_id;
    if ($item_id <= 0 || !function_exists('osc_get_preference')) {
        return true;
    }
    $key = pngm_item_allow_messages_key($item_id);
    $raw = osc_get_preference($key, 'pngm_contact');
    // Missing key → legacy listings keep messaging on.
    if ($raw === '' || $raw === null) {
        return true;
    }
    // Explicit off: 0 / false / "false" / "off"
    if ($raw === false || $raw === 0 || $raw === 0.0) {
        return false;
    }
    $s = strtolower(trim((string) $raw));
    if ($s === '0' || $s === 'false' || $s === 'off' || $s === 'no') {
        return false;
    }
    return $s === '1' || $s === 'true' || $s === 'on' || $s === 'yes';
}

/**
 * Preference key: email the seller when a buyer messages this listing.
 *
 * @param int $item_id
 * @return string
 */
function pngm_item_message_email_key($item_id)
{
    return 'msg_email_' . (int) $item_id;
}

/**
 * Whether the seller asked for email on new messages for this listing.
 * Missing value means yes (older listings).
 *
 * @param int $item_id
 * @return bool
 */
function pngm_item_message_email_enabled($item_id)
{
    $item_id = (int) $item_id;
    if ($item_id <= 0 || !function_exists('osc_get_preference')) {
        return true;
    }
    $raw = osc_get_preference(pngm_item_message_email_key($item_id), 'pngm_contact');
    if ($raw === '' || $raw === null || $raw === false) {
        return true;
    }
    return (string) $raw === '1';
}

/**
 * Persist message preference checkboxes from the post form.
 * Unchecked boxes are omitted from POST, so the hidden pngm_msg_prefs marker
 * is required before we treat a missing checkbox as opt-out.
 *
 * @param array $item
 */
function pngm_item_message_prefs_save($item)
{
    $item_id = 0;
    if (is_array($item) && isset($item['pk_i_id'])) {
        $item_id = (int) $item['pk_i_id'];
    }
    if ($item_id <= 0) {
        return;
    }

    $marker = '';
    if (class_exists('Params')) {
        $marker = (string) Params::getParam('pngm_msg_prefs');
    }
    if ($marker === '' && isset($_POST['pngm_msg_prefs'])) {
        $marker = (string) $_POST['pngm_msg_prefs'];
    }
    if ($marker !== '1') {
        return;
    }

    $allow = '0';
    $allow_posted = '';
    if (isset($_POST['pngm_allow_messages'])) {
        $allow_posted = $_POST['pngm_allow_messages'];
        if (is_array($allow_posted)) {
            $allow_posted = end($allow_posted);
        }
        $allow_posted = (string) $allow_posted;
    } elseif (class_exists('Params')) {
        $allow_posted = (string) Params::getParam('pngm_allow_messages');
    }
    if ($allow_posted === '1') {
        $allow = '1';
    }

    $email_on = '0';
    $email_posted = '';
    if (isset($_POST['pngm_email_notify'])) {
        $email_posted = $_POST['pngm_email_notify'];
        if (is_array($email_posted)) {
            $email_posted = end($email_posted);
        }
        $email_posted = (string) $email_posted;
    } elseif (class_exists('Params')) {
        $email_posted = (string) Params::getParam('pngm_email_notify');
    }
    if ($email_posted === '1') {
        $email_on = '1';
    }

    // Messaging off also turns off message email alerts.
    if ($allow !== '1') {
        $email_on = '0';
    }

    // STRING keeps "0" reliably (BOOLEAN false can look like "missing" on read).
    if (function_exists('osc_set_preference')) {
        osc_set_preference(pngm_item_allow_messages_key($item_id), $allow, 'pngm_contact', 'STRING');
        osc_set_preference(pngm_item_message_email_key($item_id), $email_on, 'pngm_contact', 'STRING');
    }
    if (class_exists('Preference')) {
        Preference::newInstance()->set(pngm_item_allow_messages_key($item_id), $allow, 'pngm_contact');
        Preference::newInstance()->set(pngm_item_message_email_key($item_id), $email_on, 'pngm_contact');
    }

    if ($allow !== '1') {
        // Preferred contact cannot stay on Message when chat is disabled.
        $pref = function_exists('pngm_item_contact_pref') ? pngm_item_contact_pref($item_id) : 'message';
        if ($pref === 'message' && function_exists('osc_set_preference')) {
            $fallback = 'call';
            if (function_exists('pngm_item_whatsapp_enabled') && pngm_item_whatsapp_enabled($item_id)) {
                $fallback = 'whatsapp';
            }
            osc_set_preference(pngm_item_contact_pref_key($item_id), $fallback, 'pngm_contact', 'STRING');
            if (class_exists('Preference')) {
                Preference::newInstance()->set(pngm_item_contact_pref_key($item_id), $fallback, 'pngm_contact');
            }
        }
    }

    if ($email_on === '0') {
        pngm_item_silence_owner_message_email($item_id);
    }
}

/** @deprecated Use pngm_item_message_prefs_save() */
function pngm_item_message_email_save($item)
{
    pngm_item_message_prefs_save($item);
}

/**
 * Turn off IM email alerts for the listing owner on every thread about this item.
 *
 * @param int $item_id
 */
function pngm_item_silence_owner_message_email($item_id)
{
    $item_id = (int) $item_id;
    if ($item_id <= 0 || !class_exists('ModelIM') || !class_exists('Item')) {
        return;
    }
    $item = Item::newInstance()->findByPrimaryKey($item_id);
    $owner_id = (is_array($item) && isset($item['fk_i_user_id'])) ? (int) $item['fk_i_user_id'] : 0;
    if ($owner_id <= 0) {
        return;
    }

    $model = ModelIM::newInstance();
    $table = $model->getTable_threads();
    $model->dao->query(
        'UPDATE ' . $table . ' SET i_to_user_notify = 0 WHERE fk_i_item_id = ' . $item_id
        . ' AND i_to_user_id = ' . $owner_id
    );
    $model->dao->query(
        'UPDATE ' . $table . ' SET i_from_user_notify = 0 WHERE fk_i_item_id = ' . $item_id
        . ' AND i_from_user_id = ' . $owner_id
    );
}

/**
 * Preference key for preferred contact method.
 *
 * @param int $item_id
 * @return string
 */
function pngm_item_contact_pref_key($item_id)
{
    return 'pref_' . (int) $item_id;
}

/**
 * Allowed preferred contact values.
 *
 * @return array
 */
function pngm_item_contact_pref_allowed()
{
    return array('call', 'whatsapp', 'message');
}

/**
 * Persist preferred contact method from the post/edit pills.
 *
 * @param array $item
 */
function pngm_item_contact_pref_save($item)
{
    $item_id = 0;
    if (is_array($item) && isset($item['pk_i_id'])) {
        $item_id = (int) $item['pk_i_id'];
    }
    if ($item_id <= 0) {
        return;
    }

    $pref = '';
    if (isset($_POST['pngm_contact_pref'])) {
        $pref = strtolower(trim((string) $_POST['pngm_contact_pref']));
    } elseif (class_exists('Params')) {
        $pref = strtolower(trim((string) Params::getParam('pngm_contact_pref')));
    }

    if (!in_array($pref, pngm_item_contact_pref_allowed(), true)) {
        $pref = 'message';
    }

    // Choosing WhatsApp as preferred also turns the public WhatsApp button on.
    if ($pref === 'whatsapp') {
        if (function_exists('osc_set_preference')) {
            osc_set_preference(pngm_item_whatsapp_pref_key($item_id), '1', 'pngm_whatsapp', 'BOOLEAN');
        }
        if (class_exists('Preference')) {
            Preference::newInstance()->set(pngm_item_whatsapp_pref_key($item_id), '1', 'pngm_whatsapp');
        }
        if (class_exists('ModelWAC')) {
            try {
                $model = ModelWAC::newInstance();
                $data = $model->getData($item_id);
                if (is_array($data) && isset($data['fk_i_item_id'])) {
                    $model->updateData($item_id, array('b_enable' => 1));
                } else {
                    $model->insertData(array(
                        'fk_i_item_id' => $item_id,
                        'b_enable' => 1,
                    ));
                }
            } catch (Throwable $e) {
                // Ignore plugin sync failures.
            }
        }
    }

    if (function_exists('osc_set_preference')) {
        osc_set_preference(pngm_item_contact_pref_key($item_id), $pref, 'pngm_contact', 'STRING');
        if (class_exists('Preference')) {
            Preference::newInstance()->set(pngm_item_contact_pref_key($item_id), $pref, 'pngm_contact');
        }
    }
}

/**
 * Seller preferred contact method for a listing.
 *
 * @param int $item_id
 * @return string call|whatsapp|message
 */
function pngm_item_contact_pref($item_id = 0)
{
    $item_id = (int) $item_id;
    if ($item_id <= 0 && function_exists('osc_item_id')) {
        $item_id = (int) osc_item_id();
    }
    if ($item_id <= 0) {
        return 'message';
    }

    $pref = '';
    if (function_exists('osc_get_preference')) {
        $pref = strtolower(trim((string) osc_get_preference(pngm_item_contact_pref_key($item_id), 'pngm_contact')));
    }

    if (!in_array($pref, pngm_item_contact_pref_allowed(), true)) {
        $pref = 'message';
    }

    if ($pref === 'whatsapp' && !pngm_item_whatsapp_enabled($item_id)) {
        $pref = 'message';
    }

    if ($pref === 'message' && !pngm_item_allow_messages_enabled($item_id)) {
        if (pngm_item_whatsapp_enabled($item_id)) {
            return 'whatsapp';
        }
        return 'call';
    }

    return $pref;
}

/**
 * Preference key for call availability.
 *
 * @param int $item_id
 * @return string
 */
function pngm_item_call_availability_key($item_id)
{
    return 'call_avail_' . (int) $item_id;
}

/**
 * Allowed call availability values.
 *
 * @return array
 */
function pngm_item_call_availability_allowed()
{
    return array('anytime', 'weekdays', 'evenings', 'weekends');
}

/**
 * Persist call availability from the contact step.
 *
 * @param array $item
 */
function pngm_item_call_availability_save($item)
{
    $item_id = 0;
    if (is_array($item) && isset($item['pk_i_id'])) {
        $item_id = (int) $item['pk_i_id'];
    }
    if ($item_id <= 0) {
        return;
    }

    $avail = '';
    // Params is the Osclass source of truth after request bootstrap.
    if (class_exists('Params')) {
        $avail = strtolower(trim((string) Params::getParam('pngm_call_availability')));
    }
    if ($avail === '' && isset($_POST['pngm_call_availability'])) {
        $avail = strtolower(trim((string) $_POST['pngm_call_availability']));
    }
    if ($avail === '' && isset($_REQUEST['pngm_call_availability'])) {
        $avail = strtolower(trim((string) $_REQUEST['pngm_call_availability']));
    }

    if (!in_array($avail, pngm_item_call_availability_allowed(), true)) {
        return;
    }

    if (function_exists('osc_set_preference')) {
        osc_set_preference(pngm_item_call_availability_key($item_id), $avail, 'pngm_contact', 'STRING');
    }
    if (class_exists('Preference')) {
        Preference::newInstance()->set(pngm_item_call_availability_key($item_id), $avail, 'pngm_contact');
    }
}

/**
 * Seller call availability for a listing.
 *
 * @param int $item_id
 * @return string anytime|weekdays|evenings|weekends|''
 */
function pngm_item_call_availability($item_id = 0)
{
    $item_id = (int) $item_id;
    if ($item_id <= 0 && function_exists('osc_item_id')) {
        $item_id = (int) osc_item_id();
    }
    if ($item_id <= 0) {
        return '';
    }

    $avail = '';
    if (function_exists('osc_get_preference')) {
        $avail = strtolower(trim((string) osc_get_preference(pngm_item_call_availability_key($item_id), 'pngm_contact')));
    }

    if (!in_array($avail, pngm_item_call_availability_allowed(), true)) {
        return '';
    }

    return $avail;
}

/**
 * Buyer-facing label for preferred contact method.
 *
 * @param string $pref
 * @return string
 */
function pngm_item_contact_pref_label($pref)
{
    $map = array(
        'call' => __('Call', 'epsilon'),
        'whatsapp' => __('WhatsApp', 'epsilon'),
        'message' => __('Message', 'epsilon'),
    );
    $pref = (string) $pref;
    return isset($map[$pref]) ? $map[$pref] : $map['message'];
}

/**
 * Collect seller contact channels available for the current item.
 *
 * @return array
 */
function pngm_seller_contact_channels()
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $channels = array(
        'message'   => null,
        'whatsapp'  => null,
        'facebook'  => null,
        'messenger' => null,
        'profile'   => null,
        'listings'  => null,
    );

    if (!function_exists('osc_item_id') || (int) osc_item_id() <= 0) {
        $cache = $channels;
        return $cache;
    }

    $item_id = (int) osc_item_id();
    $user_id = (int) osc_item_user_id();
    $item_count = 0;

    if ($user_id > 0 && class_exists('User')) {
        $user = User::newInstance()->findByPrimaryKey($user_id);
        if (is_array($user) && isset($user['i_items'])) {
            $item_count = (int) $user['i_items'];
        }
    }

    // Instant Messenger owns chat. Do not duplicate the standard Message button.

    // WhatsApp only when seller opted in (QD-004) and viewer is logged in.
    if (pngm_viewer_can_contact_seller() && pngm_item_whatsapp_enabled($item_id)) {
        $phones = array();

        if (function_exists('eps_get_item_phone')) {
            $p = eps_get_item_phone();
            if (!empty($p['found']) && empty($p['login_required']) && !empty($p['phone'])) {
                $phones[] = $p['phone'];
            }
        }

        // Listing contact phone may still exist when show_phone is off for Call UI.
        if (function_exists('osc_item_contact_phone') && osc_item_contact_phone() !== '') {
            $phones[] = osc_item_contact_phone();
        }
        if (empty($phones) && function_exists('eps_item_extra')) {
            $extra = eps_item_extra($item_id);
            if (is_array($extra) && !empty($extra['s_phone'])) {
                $phones[] = $extra['s_phone'];
            }
        }

        foreach ($phones as $phone) {
            $digits = pngm_whatsapp_digits($phone);
            if ($digits !== '') {
                $text = rawurlencode(sprintf(
                    __('Hi, I am interested in your listing: %s', 'epsilon'),
                    osc_item_url()
                ));
                $channels['whatsapp'] = array(
                    'url'   => 'https://wa.me/' . $digits . '?text=' . $text,
                    'label' => __('WhatsApp', 'epsilon'),
                    'class' => 'pngm-contact-whatsapp',
                );
                break;
            }
        }
    }

    // Facebook / Messenger from website, user info, or Business Profile socials.
    $candidates = array();

    if ($user_id > 0 && function_exists('osc_user_website') && osc_user_website() !== '') {
        $candidates[] = osc_user_website();
    }

    if ($user_id > 0 && function_exists('osc_user_info') && osc_user_info() !== '') {
        if (preg_match_all('#https?://[^\s<>"\']+#i', osc_user_info(), $m)) {
            foreach ($m[0] as $u) {
                $candidates[] = $u;
            }
        }
    }

    if ($user_id > 0 && class_exists('ModelBPR')) {
        try {
            $seller = ModelBPR::newInstance()->getSellerByUserId($user_id);
            if (is_array($seller) && !empty($seller['s_socials'])) {
                $rows = array_filter(explode('[y]', $seller['s_socials']));
                foreach ($rows as $row) {
                    $parts = explode('[x]', $row);
                    if (count($parts) >= 2 && trim($parts[1]) !== '') {
                        $type = trim($parts[0]);
                        $url = trim($parts[1]);
                        if ($type === 'fb' || stripos($url, 'facebook.com') !== false || stripos($url, 'fb.com') !== false || stripos($url, 'm.me/') !== false) {
                            $candidates[] = $url;
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            // Plugin table may be missing.
        }
    }

    foreach ($candidates as $url) {
        $url = trim($url);
        if ($url === '') {
            continue;
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        $is_fb = (bool) preg_match('#(facebook\.com|fb\.com|fb\.me|m\.me)/#i', $url);
        if (!$is_fb) {
            continue;
        }

        if ($channels['facebook'] === null) {
            $channels['facebook'] = array(
                'url'   => $url,
                'label' => __('Facebook', 'epsilon'),
                'class' => 'pngm-contact-facebook',
            );
        }

        // Prefer m.me for Messenger; otherwise derive from profile username when possible.
        if ($channels['messenger'] === null) {
            if (preg_match('#m\.me/([^/?#]+)#i', $url, $mm)) {
                $channels['messenger'] = array(
                    'url'   => 'https://m.me/' . rawurlencode($mm[1]),
                    'label' => __('Messenger', 'epsilon'),
                    'class' => 'pngm-contact-messenger',
                );
            } elseif (preg_match('#facebook\.com/(?:profile\.php\?id=(\d+)|([^/?#]+))#i', $url, $fm)) {
                $handle = !empty($fm[1]) ? $fm[1] : $fm[2];
                if ($handle !== '' && !preg_match('/^(pages|groups|events|watch|share|sharer)$/i', $handle)) {
                    $channels['messenger'] = array(
                        'url'   => 'https://m.me/' . rawurlencode($handle),
                        'label' => __('Messenger', 'epsilon'),
                        'class' => 'pngm-contact-messenger',
                    );
                }
            }
        }
    }

    if ($user_id > 0 && function_exists('eps_user_public_profile_url')) {
        $channels['profile'] = array(
            'url'   => eps_user_public_profile_url($user_id),
            'label' => __('Seller profile', 'epsilon'),
            'class' => 'pngm-contact-profile',
        );

        $channels['listings'] = array(
            'url'   => osc_search_url(array('page' => 'search', 'userId' => $user_id)),
            'label' => $item_count > 0
                ? sprintf(__('Other listings (%d)', 'epsilon'), $item_count)
                : __('Other listings', 'epsilon'),
            'class' => 'pngm-contact-listings',
        );
    }

    $cache = $channels;
    return $cache;
}

/**
 * Render listing contact actions: Call | WhatsApp | Chat (mockup row).
 * Guests see the same contact details as signed-in users.
 */
function pngm_render_seller_contact_buttons()
{
    if (!function_exists('osc_is_ad_page') || !osc_is_ad_page()) {
        return;
    }

    if (!pngm_viewer_can_contact_seller()) {
        return;
    }

    $channels = pngm_seller_contact_channels();
    $item_id = (int) osc_item_id();
    $pref = function_exists('pngm_item_contact_pref') ? pngm_item_contact_pref($item_id) : 'message';

    $call = null;
    if (function_exists('eps_get_item_phone')) {
        $phone_data = eps_get_item_phone();
        if (!empty($phone_data['found']) && empty($phone_data['login_required'])) {
            $phone_class = !empty($phone_data['class']) ? trim((string) $phone_data['class']) : 'masked';
            $call = array(
                'key'   => 'call',
                'url'   => !empty($phone_data['url']) ? $phone_data['url'] : '#',
                'label' => __('Call', 'epsilon'),
                'class' => trim('pngm-action-call phone ' . $phone_class),
                'icon'  => 'fas fa-phone-alt',
                'attrs' => array(
                    'data-prefix' => 'tel',
                    'data-part1'  => isset($phone_data['part1']) ? $phone_data['part1'] : '',
                    'data-part2'  => isset($phone_data['part2']) ? $phone_data['part2'] : '',
                    'title'       => isset($phone_data['title']) ? $phone_data['title'] : __('Call', 'epsilon'),
                ),
            );
        }
    }

    $whatsapp = null;
    if (!empty($channels['whatsapp']['url'])) {
        $whatsapp = array(
            'key'   => 'whatsapp',
            'url'   => $channels['whatsapp']['url'],
            'label' => __('WhatsApp', 'epsilon'),
            'class' => 'pngm-action-whatsapp',
            'icon'  => 'fab fa-whatsapp',
            'attrs' => array(
                'target' => '_blank',
                'rel'    => 'noopener noreferrer',
                'title'  => __('WhatsApp', 'epsilon'),
            ),
        );
    }

    $chat = null;
    $item_row = function_exists('osc_item') ? osc_item() : array();
    $seller_id = is_array($item_row) && isset($item_row['fk_i_user_id']) ? (int) $item_row['fk_i_user_id'] : (int) osc_item_user_id();
    $is_own_listing = function_exists('osc_is_web_user_logged_in')
        && osc_is_web_user_logged_in()
        && $seller_id > 0
        && $seller_id === (int) osc_logged_user_id();
    $messages_allowed = pngm_item_allow_messages_enabled($item_id);

    if ($messages_allowed && function_exists('im_contact_button')) {
        if ($is_own_listing) {
            $chat = array(
                'key'   => 'message',
                'url'   => '#',
                'label' => __('Chat', 'epsilon'),
                'class' => 'pngm-action-chat',
                'icon'  => 'fas fa-comment-dots',
                'attrs' => array(
                    'title' => __('This is your listing. You cannot message yourself.', 'epsilon'),
                    'role' => 'button',
                    'data-pngm-chat-own' => '1',
                ),
            );
        } else {
            $im_url = im_contact_button($item_row, true);
            if ($im_url !== false && $im_url !== null && trim((string) $im_url) !== '' && trim((string) $im_url) !== '#') {
                $chat = array(
                    'key'   => 'message',
                    'url'   => $im_url,
                    'label' => __('Chat', 'epsilon'),
                    'class' => 'pngm-action-chat',
                    'icon'  => 'fas fa-comment-dots',
                    'attrs' => array(
                        'title' => __('Chat with seller', 'epsilon'),
                    ),
                );
            }
        }
    }

    $by_key = array();
    if ($call) {
        $by_key['call'] = $call;
    }
    if ($whatsapp) {
        $by_key['whatsapp'] = $whatsapp;
    }
    if ($chat) {
        $by_key['message'] = $chat;
    }

    if (empty($by_key)) {
        return;
    }

    // Put preferred channel first when available.
    $actions = array();
    if (isset($by_key[$pref])) {
        $actions[] = $by_key[$pref];
        unset($by_key[$pref]);
    }
    foreach (array('call', 'whatsapp', 'message') as $k) {
        if (isset($by_key[$k])) {
            $actions[] = $by_key[$k];
        }
    }

    $count = count($actions);

    echo '<div class="pngm-contact-panel pngm-item-detail-block">';
    echo '<h2 class="pngm-contact-title">' . osc_esc_html(__('Contact Seller', 'epsilon')) . '</h2>';
    echo '<div class="pngm-contact-actions pngm-contact-count-' . (int) $count . '">';

    foreach ($actions as $action) {
        $is_pref = (!empty($action['key']) && $action['key'] === $pref);
        $attr_html = '';
        if (!empty($action['attrs']) && is_array($action['attrs'])) {
            foreach ($action['attrs'] as $ak => $av) {
                $attr_html .= ' ' . $ak . '="' . osc_esc_html($av) . '"';
            }
        }
        $cls = 'pngm-contact-action ' . $action['class'] . ($is_pref ? ' is-preferred' : '');
        echo '<a class="' . osc_esc_html($cls) . '" href="' . osc_esc_html($action['url']) . '"' . $attr_html . '>';
        echo '<i class="' . osc_esc_html($action['icon']) . '" aria-hidden="true"></i>';
        echo '<span>' . osc_esc_html($action['label']) . '</span>';
        if ($is_pref) {
            echo '<em class="pngm-contact-action-badge">' . osc_esc_html(__('Preferred', 'epsilon')) . '</em>';
        }
        echo '</a>';
    }

    echo '</div>';
    echo '</div>';
}

/**
 * Extract simple keywords from listing title for similar search.
 *
 * @param string $title
 * @return string
 */
function pngm_similar_keywords($title)
{
    $title = function_exists('mb_strtolower')
        ? mb_strtolower(trim((string) $title), 'UTF-8')
        : strtolower(trim((string) $title));

    $title = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $title);
    $parts = preg_split('/\s+/u', $title, -1, PREG_SPLIT_NO_EMPTY);
    $stop = array(
        'the', 'and', 'for', 'with', 'from', 'this', 'that', 'your', 'you',
        'are', 'was', 'new', 'used', 'sale', 'sell', 'buy', 'png', 'only',
        'good', 'best', 'free', 'price', 'urgent',
    );

    $words = array();

    foreach ($parts as $w) {
        if (strlen($w) < 3 || in_array($w, $stop, true)) {
            continue;
        }
        $words[] = $w;
        if (count($words) >= 4) {
            break;
        }
    }

    return implode(' ', $words);
}

/**
 * Run a scoped item search and return rows.
 *
 * @param array $args
 * @return array
 */
function pngm_run_item_search($args)
{
    if (!class_exists('Search')) {
        return array();
    }

    $limit = isset($args['limit']) ? (int) $args['limit'] : 12;
    $exclude = isset($args['exclude']) ? (int) $args['exclude'] : 0;
    $mSearch = new Search();

    if (!empty($args['category'])) {
        $mSearch->addCategory((int) $args['category']);
    }

    if (!empty($args['city'])) {
        $mSearch->addCity((int) $args['city']);
    } elseif (!empty($args['region'])) {
        $mSearch->addRegion((int) $args['region']);
    }

    if (!empty($args['pattern'])) {
        $mSearch->addPattern($args['pattern']);
    }

    if (!empty($args['user'])) {
        $mSearch->fromUser((int) $args['user']);
    }

    if ($exclude > 0) {
        $mSearch->addItemConditions(sprintf('%st_item.pk_i_id <> %d', DB_TABLE_PREFIX, $exclude));
    }

    $mSearch->limit(0, max(1, $limit));
    $rows = $mSearch->doSearch();

    return is_array($rows) ? $rows : array();
}

/**
 * Merge unique item rows up to $limit.
 *
 * @param array $dest
 * @param array $src
 * @param int   $limit
 * @param int   $exclude_id
 * @return array
 */
function pngm_merge_items($dest, $src, $limit, $exclude_id)
{
    $seen = array();

    foreach ($dest as $row) {
        if (isset($row['pk_i_id'])) {
            $seen[(int) $row['pk_i_id']] = true;
        }
    }

    foreach ($src as $row) {
        if (!isset($row['pk_i_id'])) {
            continue;
        }

        $id = (int) $row['pk_i_id'];

        if ($id === (int) $exclude_id || isset($seen[$id])) {
            continue;
        }

        $seen[$id] = true;
        $dest[] = $row;

        if (count($dest) >= $limit) {
            break;
        }
    }

    return $dest;
}

/**
 * Similar listings: subcategory → category+city/region → keywords (ITEM-04).
 *
 * @param int $limit
 * @return array
 */
function pngm_get_similar_listings($limit = 12)
{
    $limit = max(1, (int) $limit);
    $item_id = (int) osc_item_id();
    $cat_id = (int) osc_item_category_id();
    $city_id = (int) osc_item_city_id();
    $region_id = (int) osc_item_region_id();
    $parent_id = 0;
    $keywords = pngm_similar_keywords(osc_item_title());

    if ($cat_id > 0 && class_exists('Category')) {
        $cat = Category::newInstance()->findByPrimaryKey($cat_id);
        if (is_array($cat) && !empty($cat['fk_i_parent_id'])) {
            $parent_id = (int) $cat['fk_i_parent_id'];
        }
    }

    $items = array();

    // 1) Same subcategory + same city
    if ($cat_id > 0 && $city_id > 0) {
        $items = pngm_merge_items($items, pngm_run_item_search(array(
            'category' => $cat_id,
            'city'     => $city_id,
            'exclude'  => $item_id,
            'limit'    => $limit,
        )), $limit, $item_id);
    }

    // 2) Same subcategory + same region
    if (count($items) < $limit && $cat_id > 0 && $region_id > 0) {
        $items = pngm_merge_items($items, pngm_run_item_search(array(
            'category' => $cat_id,
            'region'   => $region_id,
            'exclude'  => $item_id,
            'limit'    => $limit,
        )), $limit, $item_id);
    }

    // 3) Same subcategory only
    if (count($items) < $limit && $cat_id > 0) {
        $items = pngm_merge_items($items, pngm_run_item_search(array(
            'category' => $cat_id,
            'exclude'  => $item_id,
            'limit'    => $limit,
        )), $limit, $item_id);
    }

    // 4) Parent category + location
    if (count($items) < $limit && $parent_id > 0) {
        $args = array(
            'category' => $parent_id,
            'exclude'  => $item_id,
            'limit'    => $limit,
        );
        if ($city_id > 0) {
            $args['city'] = $city_id;
        } elseif ($region_id > 0) {
            $args['region'] = $region_id;
        }
        $items = pngm_merge_items($items, pngm_run_item_search($args), $limit, $item_id);
    }

    // 5) Keyword / pattern fallback within parent or category
    if (count($items) < $limit && $keywords !== '') {
        $args = array(
            'pattern' => $keywords,
            'exclude' => $item_id,
            'limit'   => $limit,
        );
        if ($parent_id > 0) {
            $args['category'] = $parent_id;
        } elseif ($cat_id > 0) {
            $args['category'] = $cat_id;
        }
        $items = pngm_merge_items($items, pngm_run_item_search($args), $limit, $item_id);
    }

    return array_slice($items, 0, $limit);
}

/**
 * Render similar listings block (ITEM-04).
 *
 * @param string $card_type
 * @param int    $limit
 */
function pngm_similar_ads($card_type = 'normal', $limit = 0)
{
    if ($limit <= 0) {
        $limit = (function_exists('eps_param') && eps_param('related_count') > 0)
            ? (int) eps_param('related_count')
            : 12;
    }

    if ($card_type === '' && function_exists('eps_param')) {
        $card_type = eps_param('related_design') !== '' ? eps_param('related_design') : 'tall';
    }

    $aItems = pngm_get_similar_listings($limit);
    $default_items = View::newInstance()->_get('items');
    View::newInstance()->_exportVariableToView('items', $aItems);

    if (osc_count_items() > 0 && function_exists('eps_draw_item')) {
        ?>
        <div id="rel-block" class="related type-category pngm-similar">
          <h2><?php _e('Similar listings', 'epsilon'); ?></h2>
          <div class="nice-scroll-wrap nice-scroll-have-overflow">
            <div class="nice-scroll-prev"><span class="mover"><i class="fas fa-caret-left"></i></span></div>
            <div class="products grid nice-scroll no-visible-scroll">
              <?php
                $c = 1;
                while (osc_has_items()) {
                    eps_draw_item($c, false, $card_type);
                    $c++;
                }
              ?>
            </div>
            <div class="nice-scroll-next"><span class="mover"><i class="fas fa-caret-right"></i></span></div>
          </div>
        </div>
        <?php
    }

    View::newInstance()->_exportVariableToView('items', $default_items);
}

/**
 * Other listings from the same seller (ITEM-03).
 *
 * @param string $card_type
 * @param int    $limit
 */
function pngm_seller_other_ads($card_type = 'normal', $limit = 8)
{
    $user_id = (int) osc_item_user_id();

    if ($user_id <= 0 || !function_exists('eps_related_ads')) {
        return;
    }

    eps_related_ads('user', $card_type, $limit, 'pngm-seller-other');
}

/* Contact row is rendered directly in item.php after Location (mockup order). */

/**
 * Block starting a new IM thread when the seller disabled messages for the listing.
 */
function pngm_im_guard_messages_disabled()
{
    if (!function_exists('pngm_item_allow_messages_enabled') || !class_exists('Params')) {
        return;
    }

    $item_id = (int) Params::getParam('item-id');
    if ($item_id <= 0) {
        $item_id = (int) Params::getParam('itemId');
    }
    if ($item_id <= 0) {
        return;
    }
    if (pngm_item_allow_messages_enabled($item_id)) {
        return;
    }

    $is_create = false;
    $route = (string) Params::getParam('route');
    if ($route === 'im-create-thread') {
        $is_create = true;
    }
    if (class_exists('Rewrite')) {
        $rw = Rewrite::newInstance();
        $loc = method_exists($rw, 'get_location') ? (string) $rw->get_location() : '';
        $sec = method_exists($rw, 'get_section') ? (string) $rw->get_section() : '';
        if ($loc === 'im' && $sec === 'create-thread') {
            $is_create = true;
        }
    }
    $file = (string) Params::getParam('file');
    if (!$is_create && strpos($file, 'instant_messenger/user/create_thread.php') !== false) {
        $is_create = true;
    }
    // Also catch POST create on the same route.
    if (!$is_create && (string) Params::getParam('im-action') === 'create_thread') {
        $is_create = true;
    }
    if (!$is_create) {
        return;
    }

    osc_add_flash_error_message(__('The seller does not accept messages for this listing.', 'epsilon'));
    if (class_exists('Item') && function_exists('osc_item_url_from_item')) {
        $item = Item::newInstance()->findByPrimaryKey($item_id);
        if (is_array($item) && !empty($item['pk_i_id'])) {
            osc_redirect_to(osc_item_url_from_item($item));
        }
    }
    osc_redirect_to(osc_base_url());
}
osc_add_hook('init', 'pngm_im_guard_messages_disabled', 7);
osc_add_hook('before_html', 'pngm_im_guard_messages_disabled', 1);

/**
 * Block Instant Messenger contact-seller shortcut when messages are disabled.
 *
 * @param array $aItem
 */
function pngm_im_block_contact_seller_when_disabled($aItem)
{
    if (!is_array($aItem) || !function_exists('pngm_item_allow_messages_enabled')) {
        return;
    }
    $item_id = isset($aItem['id']) ? (int) $aItem['id'] : 0;
    if ($item_id <= 0) {
        return;
    }
    if (pngm_item_allow_messages_enabled($item_id)) {
        return;
    }

    osc_add_flash_error_message(__('The seller does not accept messages for this listing.', 'epsilon'));
    if (class_exists('Item') && function_exists('osc_item_url_from_item')) {
        $item = Item::newInstance()->findByPrimaryKey($item_id);
        if (is_array($item) && !empty($item['pk_i_id'])) {
            osc_redirect_to(osc_item_url_from_item($item));
        }
    }
    osc_redirect_to(osc_base_url());
}
osc_add_hook('hook_email_item_inquiry', 'pngm_im_block_contact_seller_when_disabled', 0);
