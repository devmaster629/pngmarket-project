<?php
/**
 * Listing detail attributes: visual icon+label+value grid; hide empty / unselected values.
 */

if (!defined('ABS_PATH')) {
    exit;
}

/**
 * FA icon class for an attribute identifier / type.
 *
 * @param array $a
 * @return string
 */
function pngm_atr_fa_icon($a)
{
    $id = isset($a['s_identifier']) ? strtolower(trim((string) $a['s_identifier'])) : '';
    $type = isset($a['s_type']) ? strtoupper((string) $a['s_type']) : '';
    $name = '';
    if (function_exists('atr_name') && isset($a['locales'])) {
        $name = strtolower((string) atr_name($a['locales']));
    }

    $map = array(
        'make' => 'fas fa-car',
        'model' => 'fas fa-car-side',
        'year' => 'far fa-calendar',
        'condition' => 'fas fa-tachometer-alt',
        'body' => 'fas fa-car',
        'body_type' => 'fas fa-car',
        'fuel' => 'fas fa-gas-pump',
        'fuel_type' => 'fas fa-gas-pump',
        'transmission' => 'fas fa-cog',
        'mileage' => 'fas fa-tachometer-alt',
        'odometer' => 'fas fa-tachometer-alt',
        'engine' => 'fas fa-cogs',
        'drive' => 'fas fa-dharmachakra',
        'drive_type' => 'fas fa-dharmachakra',
        'color' => 'fas fa-tint',
        'exterior_color' => 'fas fa-tint',
        'doors' => 'fas fa-door-closed',
        'seats' => 'fas fa-couch',
        'registration' => 'fas fa-id-card',
        'reg' => 'fas fa-id-card',
        'registration_expiry' => 'far fa-calendar-check',
        'import' => 'fas fa-anchor',
        'import_status' => 'fas fa-anchor',
        'accessories' => 'fas fa-plus-square',
        'features' => 'fas fa-plus-square',
        'phone' => 'fas fa-phone',
    );

    if ($id !== '' && isset($map[$id])) {
        return $map[$id];
    }

    foreach ($map as $key => $icon) {
        if ($id !== '' && strpos($id, $key) !== false) {
            return $icon;
        }
        if ($name !== '' && strpos($name, str_replace('_', ' ', $key)) !== false) {
            return $icon;
        }
    }

    $type_map = array(
        'CHECKBOX' => 'fas fa-check-circle',
        'RADIO' => 'fas fa-dot-circle',
        'SELECT' => 'fas fa-list',
        'NUMBER' => 'fas fa-hashtag',
        'DATE' => 'far fa-calendar-alt',
        'DATERANGE' => 'far fa-calendar-alt',
        'PHONE' => 'fas fa-phone',
        'EMAIL' => 'fas fa-envelope',
        'URL' => 'fas fa-link',
        'TEXT' => 'fas fa-info-circle',
        'TEXTAREA' => 'fas fa-align-left',
    );

    if (isset($type_map[$type])) {
        return $type_map[$type];
    }

    return 'fas fa-tag';
}

/**
 * Section title for attributes block.
 *
 * @param array $item
 * @return string
 */
function pngm_atr_section_title($item)
{
    $cat_id = isset($item['fk_i_category_id']) ? (int) $item['fk_i_category_id'] : 0;
    if ($cat_id > 0 && function_exists('pngm_item_is_vehicle_category') && pngm_item_is_vehicle_category($cat_id)) {
        return __('Vehicle details', 'epsilon');
    }
    if ($cat_id > 0 && class_exists('Category')) {
        $cat = Category::newInstance()->findByPrimaryKey($cat_id);
        $cat_name = '';
        if (is_array($cat)) {
            if (!empty($cat['s_name'])) {
                $cat_name = (string) $cat['s_name'];
            } elseif (isset($cat['locale']) && is_array($cat['locale'])) {
                $loc = osc_current_user_locale();
                if (!empty($cat['locale'][$loc]['s_name'])) {
                    $cat_name = (string) $cat['locale'][$loc]['s_name'];
                }
            }
        }
        if ($cat_name !== '') {
            $lower = strtolower($cat_name);
            if (strpos($lower, 'vehicle') !== false || strpos($lower, 'car') !== false || strpos($lower, 'auto') !== false) {
                return __('Vehicle details', 'epsilon');
            }
            return sprintf(__('%s details', 'epsilon'), $cat_name);
        }
    }
    return __('Details', 'epsilon');
}

/**
 * Replace plugin item_detail attributes with visual, values-only output.
 *
 * @param array $item
 */
function pngm_atr_show_item($item)
{
    if (!function_exists('ModelATR') || !function_exists('atr_single_attribute')) {
        return;
    }
    if (!is_array($item) || empty($item['pk_i_id'])) {
        return;
    }

    $attributes = ModelATR::newInstance()->getItemAttributes($item['pk_i_id'], $item['fk_i_category_id']);
    if (!is_array($attributes) || count($attributes) === 0) {
        return;
    }

    $lines = '';
    foreach ($attributes as $a) {
        if (!is_array($a) || (int) @$a['b_hook'] !== 1 || (int) @$a['fk_i_linked_to_attr_id'] > 0) {
            continue;
        }
        if (strtoupper((string) @$a['s_type']) === 'DIVIDER') {
            continue;
        }

        // Checkbox/radio: only selected values (no red-X placeholders)
        $type = strtoupper((string) @$a['s_type']);
        if ($type === 'CHECKBOX' || $type === 'RADIO') {
            $a['b_values_all'] = 0;
        }

        $chunk = atr_single_attribute($a, $item['pk_i_id']);
        if (!is_string($chunk) || trim($chunk) === '') {
            continue;
        }

        // Drop check/cross images and empty value chips (avoids ", Automatic")
        $chunk = preg_replace('/<img\b[^>]*class="[^"]*atr-img[^"]*"[^>]*>/i', '', $chunk);
        $chunk = preg_replace('/<span class="atr-value-single[^"]*"[^>]*>\s*<\/span>/i', '', $chunk);

        // Inject FA icon as first child of the attribute row
        $icon = pngm_atr_fa_icon($a);
        $icon_html = '<span class="pngm-atr-ico" aria-hidden="true"><i class="' . osc_esc_html($icon) . '"></i></span>';
        $chunk = preg_replace('/(<li\b[^>]*>)/', '$1' . $icon_html, $chunk, 1);

        $lines .= $chunk;
    }

    if (trim($lines) === '') {
        return;
    }

    $title = pngm_atr_section_title($item);
    echo '<ul id="atr-item" class="pngm-atr-visual atr-theme-' . osc_esc_html(osc_current_web_theme()) . '">';
    echo '<h3 id="atr-title">' . osc_esc_html($title) . '</h3>';
    echo $lines;
    echo '</ul>';
}

/**
 * Swap Attributes plugin hook for our visual renderer.
 */
function pngm_atr_boot_item_display()
{
    if (!function_exists('atr_show_item')) {
        return;
    }
    osc_remove_hook('item_detail', 'atr_show_item');
    osc_add_hook('item_detail', 'pngm_atr_show_item');
}

osc_add_hook('init', 'pngm_atr_boot_item_display', 20);
