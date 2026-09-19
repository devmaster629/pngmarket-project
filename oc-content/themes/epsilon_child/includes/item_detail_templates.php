<?php
/**
 * P2-005 / QA-008 — Category-specific item detail templates.
 *
 * Reusable layouts for Vehicles, Phones, Home, Fashion, Sports.
 * Other categories fall back to the generic detail layout.
 *
 * Osclass hook priorities must stay 0–10.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'item_detail_templates.php'
) {
    exit;
}

/**
 * Root-category → template key map (by known root ids and name keywords).
 *
 * @return array<string,string>
 */
function pngm_item_tpl_map()
{
    return array(
        '1' => 'vehicles',
        '2' => 'phones',
        '3' => 'home',
        '4' => 'fashion',
        '6' => 'sports',
    );
}

/**
 * Resolve detail template key for a category id.
 *
 * @param int $category_id
 * @return string vehicles|phones|home|fashion|sports|default
 */
function pngm_item_tpl_key($category_id = 0)
{
    $category_id = (int) $category_id;
    if ($category_id <= 0 && function_exists('osc_item_category_id')) {
        $category_id = (int) osc_item_category_id();
    }
    if ($category_id <= 0) {
        return 'default';
    }

    $root_id = $category_id;
    $root_name = '';
    if (class_exists('Category')) {
        $root = Category::newInstance()->findRootCategory($category_id);
        if (is_array($root) && !empty($root['pk_i_id'])) {
            $root_id = (int) $root['pk_i_id'];
            if (!empty($root['s_name'])) {
                $root_name = (string) $root['s_name'];
            } elseif (!empty($root['locale']) && is_array($root['locale'])) {
                $locale = function_exists('osc_current_user_locale') ? osc_current_user_locale() : '';
                if ($locale !== '' && !empty($root['locale'][$locale]['s_name'])) {
                    $root_name = (string) $root['locale'][$locale]['s_name'];
                } else {
                    $first = reset($root['locale']);
                    if (is_array($first) && !empty($first['s_name'])) {
                        $root_name = (string) $first['s_name'];
                    }
                }
            }
        }
    }

    $map = pngm_item_tpl_map();
    if (isset($map[(string) $root_id])) {
        return $map[(string) $root_id];
    }

    $hay = strtolower($root_name);
    if ($hay === '') {
        return 'default';
    }
    if (strpos($hay, 'vehicle') !== false || strpos($hay, 'car') !== false || strpos($hay, 'motor') !== false) {
        return 'vehicles';
    }
    if (strpos($hay, 'phone') !== false || strpos($hay, 'electronic') !== false) {
        return 'phones';
    }
    if (strpos($hay, 'home') !== false || strpos($hay, 'furniture') !== false || strpos($hay, 'garden') !== false) {
        return 'home';
    }
    if (strpos($hay, 'fashion') !== false || strpos($hay, 'beauty') !== false || strpos($hay, 'clothing') !== false) {
        return 'fashion';
    }
    if (strpos($hay, 'sport') !== false || strpos($hay, 'hobb') !== false || strpos($hay, 'leisure') !== false) {
        return 'sports';
    }

    return 'default';
}

/**
 * Human label for a template key.
 *
 * @param string $key
 * @return string
 */
function pngm_item_tpl_label($key)
{
    $labels = array(
        'vehicles' => __('Vehicle details', 'epsilon'),
        'phones' => __('Device details', 'epsilon'),
        'home' => __('Home & living details', 'epsilon'),
        'fashion' => __('Fashion details', 'epsilon'),
        'sports' => __('Sports & leisure details', 'epsilon'),
        'default' => __('Listing details', 'epsilon'),
    );
    $key = (string) $key;
    return isset($labels[$key]) ? $labels[$key] : $labels['default'];
}

/**
 * Highlight field patterns per template (matched against attribute name/identifier).
 *
 * @param string $key
 * @return array<int,array{keys:string[],label:string,icon:string}>
 */
function pngm_item_tpl_highlight_defs($key)
{
    $defs = array(
        'vehicles' => array(
            array('keys' => array('make', 'brand'), 'label' => __('Make', 'epsilon'), 'icon' => 'car'),
            array('keys' => array('model'), 'label' => __('Model', 'epsilon'), 'icon' => 'car-side'),
            array('keys' => array('year'), 'label' => __('Year', 'epsilon'), 'icon' => 'calendar'),
            array('keys' => array('mileage', 'odometer', 'km'), 'label' => __('Mileage', 'epsilon'), 'icon' => 'gauge'),
            array('keys' => array('fuel'), 'label' => __('Fuel', 'epsilon'), 'icon' => 'fuel'),
            array('keys' => array('transmission', 'gear'), 'label' => __('Transmission', 'epsilon'), 'icon' => 'cog'),
            array('keys' => array('condition'), 'label' => __('Condition', 'epsilon'), 'icon' => 'clock'),
            array('keys' => array('body'), 'label' => __('Body', 'epsilon'), 'icon' => 'car'),
        ),
        'phones' => array(
            array('keys' => array('brand', 'make'), 'label' => __('Brand', 'epsilon'), 'icon' => 'phone'),
            array('keys' => array('model'), 'label' => __('Model', 'epsilon'), 'icon' => 'info'),
            array('keys' => array('storage', 'memory', 'rom', 'gb'), 'label' => __('Storage', 'epsilon'), 'icon' => 'list'),
            array('keys' => array('ram'), 'label' => __('RAM', 'epsilon'), 'icon' => 'cog'),
            array('keys' => array('condition'), 'label' => __('Condition', 'epsilon'), 'icon' => 'clock'),
            array('keys' => array('network', 'sim', 'carrier'), 'label' => __('Network', 'epsilon'), 'icon' => 'phone'),
            array('keys' => array('color', 'colour'), 'label' => __('Colour', 'epsilon'), 'icon' => 'tint'),
        ),
        'home' => array(
            array('keys' => array('condition'), 'label' => __('Condition', 'epsilon'), 'icon' => 'clock'),
            array('keys' => array('material'), 'label' => __('Material', 'epsilon'), 'icon' => 'tag'),
            array('keys' => array('brand', 'make'), 'label' => __('Brand', 'epsilon'), 'icon' => 'tag'),
            array('keys' => array('dimension', 'size', 'width', 'height'), 'label' => __('Size', 'epsilon'), 'icon' => 'list'),
            array('keys' => array('color', 'colour'), 'label' => __('Colour', 'epsilon'), 'icon' => 'tint'),
            array('keys' => array('room', 'type'), 'label' => __('Type', 'epsilon'), 'icon' => 'info'),
        ),
        'fashion' => array(
            array('keys' => array('brand', 'make'), 'label' => __('Brand', 'epsilon'), 'icon' => 'tag'),
            array('keys' => array('size'), 'label' => __('Size', 'epsilon'), 'icon' => 'list'),
            array('keys' => array('condition'), 'label' => __('Condition', 'epsilon'), 'icon' => 'clock'),
            array('keys' => array('color', 'colour'), 'label' => __('Colour', 'epsilon'), 'icon' => 'tint'),
            array('keys' => array('material', 'fabric'), 'label' => __('Material', 'epsilon'), 'icon' => 'tag'),
            array('keys' => array('gender', 'style'), 'label' => __('Style', 'epsilon'), 'icon' => 'info'),
        ),
        'sports' => array(
            array('keys' => array('brand', 'make'), 'label' => __('Brand', 'epsilon'), 'icon' => 'tag'),
            array('keys' => array('condition'), 'label' => __('Condition', 'epsilon'), 'icon' => 'clock'),
            array('keys' => array('size'), 'label' => __('Size', 'epsilon'), 'icon' => 'list'),
            array('keys' => array('type', 'sport'), 'label' => __('Type', 'epsilon'), 'icon' => 'info'),
            array('keys' => array('material'), 'label' => __('Material', 'epsilon'), 'icon' => 'tag'),
            array('keys' => array('color', 'colour'), 'label' => __('Colour', 'epsilon'), 'icon' => 'tint'),
        ),
    );

    return isset($defs[$key]) ? $defs[$key] : array();
}

/**
 * Collect filled attribute rows for the current (or given) item.
 *
 * @param int $item_id
 * @param int $category_id
 * @return array<int,array{id:int,identifier:string,name:string,value:string,type:string}>
 */
function pngm_item_tpl_collect_attrs($item_id = 0, $category_id = 0)
{
    $out = array();
    $item_id = (int) $item_id;
    $category_id = (int) $category_id;
    if ($item_id <= 0 && function_exists('osc_item_id')) {
        $item_id = (int) osc_item_id();
    }
    if ($category_id <= 0 && function_exists('osc_item_category_id')) {
        $category_id = (int) osc_item_category_id();
    }
    if ($item_id <= 0 || !class_exists('ModelATR')) {
        return $out;
    }

    try {
        $model = ModelATR::newInstance();
        $attributes = $model->getItemAttributes($item_id, $category_id);
        if (!is_array($attributes) || empty($attributes)) {
            return $out;
        }
        $all_values = method_exists($model, 'getAllItemAttributeValues')
            ? $model->getAllItemAttributeValues($item_id)
            : array();

        foreach ($attributes as $a) {
            if (!is_array($a) || empty($a['pk_i_id'])) {
                continue;
            }
            if (isset($a['s_type']) && strtoupper((string) $a['s_type']) === 'DIVIDER') {
                continue;
            }
            if (isset($a['b_hook']) && (int) $a['b_hook'] !== 1) {
                continue;
            }

            $name = '';
            if (!empty($a['s_name'])) {
                $name = (string) $a['s_name'];
            } elseif (function_exists('atr_name') && !empty($a['locales'])) {
                $name = (string) atr_name($a['locales']);
            }
            $identifier = !empty($a['s_identifier']) ? (string) $a['s_identifier'] : '';
            $type = isset($a['s_type']) ? strtoupper((string) $a['s_type']) : '';
            $aid = (int) $a['pk_i_id'];
            $value = '';

            if (in_array($type, array('TEXT', 'TEXTAREA', 'PHONE', 'EMAIL', 'URL', 'DATE', 'DATERANGE', 'NUMBER'), true)) {
                $row = isset($all_values[$aid]) ? $all_values[$aid] : null;
                if (is_array($row) && isset($row['s_value']) && trim((string) $row['s_value']) !== '') {
                    $value = trim((string) $row['s_value']);
                    if ($type === 'DATERANGE') {
                        $value = implode(' – ', array_filter(explode('|', $value)));
                    }
                }
            } elseif ($type === 'SELECT') {
                $row = isset($all_values[$aid]) ? $all_values[$aid] : null;
                $vid = is_array($row) && !empty($row['fk_i_attribute_value_id'])
                    ? (int) $row['fk_i_attribute_value_id']
                    : 0;
                if ($vid > 0 && method_exists($model, 'getAttributeValue')) {
                    $v = $model->getAttributeValue($vid);
                    if (is_array($v)) {
                        if (function_exists('atr_name') && !empty($v['locales'])) {
                            $value = trim((string) atr_name($v['locales']));
                        } elseif (!empty($v['s_name'])) {
                            $value = trim((string) $v['s_name']);
                        }
                    }
                }
            } elseif ($type === 'CHECKBOX' || $type === 'RADIO') {
                if (method_exists($model, 'getItemAttributeValueRows')) {
                    $rows = $model->getItemAttributeValueRows($item_id, $aid);
                    $parts = array();
                    if (is_array($rows)) {
                        foreach ($rows as $v) {
                            if (!is_array($v)) {
                                continue;
                            }
                            $label = '';
                            if (function_exists('atr_name') && !empty($v['locales'])) {
                                $label = trim((string) atr_name($v['locales']));
                            } elseif (!empty($v['s_name'])) {
                                $label = trim((string) $v['s_name']);
                            }
                            if ($label !== '') {
                                $parts[] = $label;
                            }
                        }
                    }
                    if (!empty($parts)) {
                        $value = implode(', ', $parts);
                    }
                }
            }

            if ($value === '') {
                continue;
            }

            $out[] = array(
                'id' => $aid,
                'identifier' => $identifier,
                'name' => $name !== '' ? $name : $identifier,
                'value' => $value,
                'type' => $type,
            );
        }
    } catch (Exception $e) {
        return array();
    }

    return $out;
}

/**
 * Pick highlight facts for a template from collected attributes.
 *
 * @param string $key
 * @param array  $attrs
 * @return array<int,array{label:string,value:string,icon:string}>
 */
function pngm_item_tpl_pick_highlights($key, $attrs)
{
    $defs = pngm_item_tpl_highlight_defs($key);
    if (empty($defs) || !is_array($attrs) || empty($attrs)) {
        return array();
    }

    $used = array();
    $facts = array();

    foreach ($defs as $def) {
        $keys = isset($def['keys']) && is_array($def['keys']) ? $def['keys'] : array();
        foreach ($attrs as $attr) {
            $aid = isset($attr['id']) ? (int) $attr['id'] : 0;
            if ($aid > 0 && isset($used[$aid])) {
                continue;
            }
            $hay = strtolower(
                (isset($attr['identifier']) ? $attr['identifier'] : '')
                . ' '
                . (isset($attr['name']) ? $attr['name'] : '')
            );
            $match = false;
            foreach ($keys as $needle) {
                $needle = strtolower(trim((string) $needle));
                if ($needle !== '' && strpos($hay, $needle) !== false) {
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                continue;
            }
            $facts[] = array(
                'label' => isset($def['label']) ? (string) $def['label'] : (string) $attr['name'],
                'value' => (string) $attr['value'],
                'icon' => isset($def['icon']) ? (string) $def['icon'] : 'tag',
            );
            if ($aid > 0) {
                $used[$aid] = true;
            }
            break;
        }
        if (count($facts) >= 6) {
            break;
        }
    }

    return $facts;
}

/**
 * SVG icon for a highlight (reuses attributes_display icons when available).
 *
 * @param string $icon_key
 * @return string
 */
function pngm_item_tpl_fact_icon($icon_key)
{
    if (function_exists('pngm_atr_svg_icon')) {
        return pngm_atr_svg_icon(array(
            's_identifier' => (string) $icon_key,
            's_name' => (string) $icon_key,
            's_type' => 'TEXT',
        ));
    }
    return '';
}

/**
 * Body class fragment for the current item template.
 *
 * @return string
 */
function pngm_item_tpl_body_class()
{
    $key = pngm_item_tpl_key();
    return 'pngm-item-tpl pngm-item-tpl-' . preg_replace('/[^a-z0-9_-]/i', '', $key);
}

/**
 * Render the category highlight panel — disabled.
 * Specs table is the primary layout; the green key-facts board was removed.
 *
 * @param string $key
 */
function pngm_item_tpl_render_highlights($key = '')
{
    // Intentionally empty (P2-005 UX: keep Full specifications table only).
}

/**
 * Section heading shown above the attributes grid for specialty templates.
 *
 * @param string $key
 */
function pngm_item_tpl_render_specs_heading($key = '')
{
    if ($key === '') {
        $key = pngm_item_tpl_key();
    }
    if ($key === 'default') {
        return;
    }

    $headings = array(
        'vehicles' => __('Full specifications', 'epsilon'),
        'phones' => __('Full specifications', 'epsilon'),
        'home' => __('Product details', 'epsilon'),
        'fashion' => __('Product details', 'epsilon'),
        'sports' => __('Product details', 'epsilon'),
    );
    $heading = isset($headings[$key]) ? $headings[$key] : __('Details', 'epsilon');

    echo '<div class="pngm-item-specs-head pngm-item-specs-head--' . osc_esc_html($key) . '">';
    echo '<h2><i class="fas fa-list-ul" aria-hidden="true"></i> ' . osc_esc_html($heading) . '</h2>';
    echo '</div>';
}

