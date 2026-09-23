<?php
/**
 * Post-form attributes: match the selected leaf category only.
 *
 * Core Attributes plugin also matches on the root (Vehicles), so Cars fields
 * (Body type, Seats, …) appeared under Motorcycles. Search filters still use
 * the plugin’s root-aware query via getSearchAttributes2().
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'attributes_post_form.php'
) {
    exit;
}

/**
 * Whether an attribute applies to this exact category id (no root inheritance).
 *
 * @param array $attr
 * @param int   $cat_id
 * @return bool
 */
function pngm_atr_applies_to_leaf($attr, $cat_id)
{
    $cat_id = (int) $cat_id;
    if ($cat_id <= 0) {
        return true;
    }
    if (!is_array($attr)) {
        return false;
    }
    $raw = isset($attr['s_category_id']) ? trim((string) $attr['s_category_id']) : '';
    if ($raw === '' || $raw === '0') {
        return true;
    }
    $ids = array_filter(array_map('intval', explode(',', $raw)));
    if (empty($ids)) {
        return true;
    }
    return in_array($cat_id, $ids, true);
}

/**
 * Replacement for atr_post_form / atr_edit_form with leaf-exact category filter.
 *
 * @param int      $cat_id
 * @param int|null $item_id
 */
function pngm_atr_post_form($cat_id = null, $item_id = null)
{
    if (!function_exists('atr_generate_post_elem') || !class_exists('ModelATR')) {
        if (function_exists('atr_post_form')) {
            atr_post_form($cat_id, $item_id);
        }
        return;
    }

    $cat_id = (int) $cat_id;
    if ($cat_id <= 0) {
        $cat_id = -1;
    }

    $attributes = ModelATR::newInstance()->getAttributes(1, $cat_id > 0 ? $cat_id : -1);
    if (!is_array($attributes) || empty($attributes)) {
        return;
    }

    $html = '<div class="atr-form atr-theme-' . osc_current_web_theme() . ' '
        . ((function_exists('atr_param') && atr_param('styled') == 1) ? 'atr-styled' : '')
        . '" id="atr-form">';

    foreach ($attributes as $a) {
        if (!is_array($a) || empty($a['pk_i_id'])) {
            continue;
        }
        if (!empty($a['fk_i_linked_to_attr_id']) && (int) $a['fk_i_linked_to_attr_id'] > 0) {
            continue;
        }
        // Phone belongs on Contact (step 5) via Osclass contactPhone — not Item details.
        if (isset($a['s_type']) && strtoupper((string) $a['s_type']) === 'PHONE') {
            continue;
        }
        // Theme already has Condition (eps_simple_condition) — skip Attributes duplicate.
        $ident = isset($a['s_identifier']) ? strtolower(trim((string) $a['s_identifier'])) : '';
        if ($ident === 'condition') {
            continue;
        }
        if ($cat_id > 0 && !pngm_atr_applies_to_leaf($a, $cat_id)) {
            continue;
        }
        $html .= atr_generate_post_elem($a, $item_id);
    }

    $html .= '</div>';
    echo $html;
}

/**
 * @param int      $cat_id
 * @param int|null $item_id
 */
function pngm_atr_edit_form($cat_id, $item_id = null)
{
    pngm_atr_post_form($cat_id, $item_id);
}

/**
 * Swap Attributes plugin form hooks for leaf-exact versions.
 * Must run after the plugin registers (init priority ≥ plugin, ≤ 10).
 */
function pngm_atr_boot_post_form()
{
    if (!function_exists('atr_post_form')) {
        return;
    }
    osc_remove_hook('item_form', 'atr_post_form');
    osc_remove_hook('item_edit', 'atr_edit_form');
    osc_remove_hook('item_form', 'pngm_atr_post_form');
    osc_remove_hook('item_edit', 'pngm_atr_edit_form');
    osc_add_hook('item_form', 'pngm_atr_post_form', 5);
    osc_add_hook('item_edit', 'pngm_atr_edit_form', 5);
}

pngm_atr_boot_post_form();
osc_add_hook('init', 'pngm_atr_boot_post_form', 10);
