<?php
/**
 * Listing detail attributes: visual icon+label+value grid; hide empty / unselected values.
 *
 * Important: Osclass only executes hook priorities 0–10.
 * We keep the plugin's atr_show_item() renderer (proven output) and enhance its HTML.
 */

if (!defined('ABS_PATH')) {
    exit;
}

/**
 * Inline SVG icon for an attribute (always visible, no FA dependency).
 *
 * @param array $a
 * @return string
 */
function pngm_atr_svg_icon($a)
{
    $id = isset($a['s_identifier']) ? strtolower(trim((string) $a['s_identifier'])) : '';
    $type = isset($a['s_type']) ? strtoupper((string) $a['s_type']) : '';
    $name = '';
    if (!empty($a['s_name'])) {
        $name = strtolower((string) $a['s_name']);
    } elseif (function_exists('atr_name') && isset($a['locales'])) {
        $name = strtolower((string) atr_name($a['locales']));
    }

    $hay = $id . ' ' . $name;

    // Compact stroke SVGs (24x24 viewBox), currentColor — avoid "$" in markup (preg backrefs)
    $svgs = array(
        'car' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M5 16l1.5-6h11L19 16"/><path d="M3 16h18v2a1 1 0 0 1-1 1h-1a2 2 0 0 1-4 0H9a2 2 0 0 1-4 0H4a1 1 0 0 1-1-1v-2z"/><circle cx="7.5" cy="16" r="1.2"/><circle cx="16.5" cy="16" r="1.2"/><path d="M7 10l1.2-3.5A2 2 0 0 1 10.1 5h3.8a2 2 0 0 1 1.9 1.5L17 10"/></svg>',
        'car-side' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 14l1.2-4.2A3 3 0 0 1 7.1 8h6.2a3 3 0 0 1 2.9 2.1L18 14"/><path d="M3 14h18v2a1 1 0 0 1-1 1h-1.2a2.2 2.2 0 0 1-4.2 0H9.4a2.2 2.2 0 0 1-4.2 0H4a1 1 0 0 1-1-1v-2z"/><circle cx="7.3" cy="16" r="1.15"/><circle cx="16.7" cy="16" r="1.15"/><path d="M9 8l1-2h3l1 2"/></svg>',
        'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M8 3.5V7M16 3.5V7M3.5 10h17"/></svg>',
        'clock' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 8v4.5l3 1.5"/></svg>',
        'gauge' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19a8 8 0 1 1 8-8"/><path d="M12 11l4-3"/><circle cx="12" cy="11" r="1.2" fill="currentColor" stroke="none"/></svg>',
        'fuel' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="10" height="16" rx="1.5"/><path d="M14 8h2.5a2 2 0 0 1 2 2v5.5a1.5 1.5 0 0 0 3 0V9.5L19 7"/><path d="M7 8h4M7 12h4"/></svg>',
        'cog' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 3.5v2.2M12 18.3v2.2M4.9 6.5l1.6 1.6M17.5 15.9l1.6 1.6M3.5 12h2.2M18.3 12h2.2M4.9 17.5l1.6-1.6M17.5 8.1l1.6-1.6"/></svg>',
        'engine' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M6 10h2l1-2h4l1 2h2v5H6v-5z"/><path d="M8 15v3M16 15v3M4 12h2M18 11h2v3h-2M10 8V6h4v2"/></svg>',
        'drive' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2.2"/><path d="M12 4v4M12 16v4M4 12h4M16 12h4M6.5 6.5l2.8 2.8M14.7 14.7l2.8 2.8M17.5 6.5l-2.8 2.8M9.3 14.7l-2.8 2.8"/></svg>',
        'tint' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3.5S6.5 10 6.5 14.2a5.5 5.5 0 0 0 11 0C17.5 10 12 3.5 12 3.5z"/></svg>',
        'door' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M7 21V5a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v16"/><path d="M5 21h14"/><circle cx="14.2" cy="12" r="0.9" fill="currentColor" stroke="none"/></svg>',
        'seats' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="7" r="2"/><circle cx="16" cy="7" r="2"/><path d="M4.5 19v-1.2A3.3 3.3 0 0 1 7.8 14.5h.4A3.3 3.3 0 0 1 11.5 17.8V19"/><path d="M12.5 19v-1.2A3.3 3.3 0 0 1 15.8 14.5h.4A3.3 3.3 0 0 1 19.5 17.8V19"/></svg>',
        'id' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="12" r="2"/><path d="M13.5 10.5h4M13.5 13.5h3"/></svg>',
        'import' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17M12 3.5c2.5 2.7 3.8 5.5 3.8 8.5S14.5 17.8 12 20.5C9.5 17.8 8.2 15 8.2 12S9.5 6.2 12 3.5z"/></svg>',
        'plus' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M12 8v8M8 12h8"/></svg>',
        'list' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7h12M8 12h12M8 17h12"/><circle cx="4.5" cy="7" r="1" fill="currentColor" stroke="none"/><circle cx="4.5" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="4.5" cy="17" r="1" fill="currentColor" stroke="none"/></svg>',
        'info' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/></svg>',
        'phone' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3.5h3.2l1.1 3.3-2 1.4a12 12 0 0 0 5.5 5.5l1.4-2 3.3 1.1V16a2 2 0 0 1-2.2 2A15 15 0 0 1 5 5.7 2 2 0 0 1 7 3.5z"/></svg>',
        'tag' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 12.5V5.8A2.3 2.3 0 0 1 5.8 3.5h6.7L20.5 11.5l-7.8 7.8L3.5 12.5z"/><circle cx="8.2" cy="8.2" r="1.1"/></svg>',
    );

    $key = 'tag';
    if (strpos($hay, 'make') !== false || strpos($hay, 'brand') !== false) {
        $key = 'car';
    } elseif (strpos($hay, 'model') !== false) {
        $key = 'car-side';
    } elseif (strpos($hay, 'year') !== false) {
        $key = 'calendar';
    } elseif (strpos($hay, 'condition') !== false) {
        $key = 'clock';
    } elseif (strpos($hay, 'mileage') !== false || strpos($hay, 'odometer') !== false) {
        $key = 'gauge';
    } elseif (strpos($hay, 'body') !== false) {
        $key = 'car';
    } elseif (strpos($hay, 'fuel') !== false) {
        $key = 'fuel';
    } elseif (strpos($hay, 'transmission') !== false || strpos($hay, 'gear') !== false) {
        $key = 'cog';
    } elseif (strpos($hay, 'engine') !== false) {
        $key = 'engine';
    } elseif (strpos($hay, 'drive') !== false) {
        $key = 'drive';
    } elseif (strpos($hay, 'color') !== false || strpos($hay, 'colour') !== false) {
        $key = 'tint';
    } elseif (strpos($hay, 'door') !== false) {
        $key = 'door';
    } elseif (strpos($hay, 'seat') !== false) {
        $key = 'seats';
    } elseif (strpos($hay, 'registr') !== false || $id === 'reg') {
        $key = 'id';
    } elseif (strpos($hay, 'import') !== false) {
        $key = 'import';
    } elseif (strpos($hay, 'accessor') !== false || strpos($hay, 'feature') !== false || $type === 'CHECKBOX') {
        $key = 'plus';
    } elseif (strpos($hay, 'phone') !== false || $type === 'PHONE') {
        $key = 'phone';
    } elseif ($type === 'SELECT' || $type === 'RADIO') {
        $key = 'list';
    } elseif ($type === 'TEXT' || $type === 'TEXTAREA' || $type === 'NUMBER') {
        $key = 'info';
    } elseif ($type === 'DATE' || $type === 'DATERANGE') {
        $key = 'calendar';
    }

    return isset($svgs[$key]) ? $svgs[$key] : $svgs['tag'];
}

/**
 * Meta bar under price: Posted · views · Listing ID (plain text, no box).
 */
function pngm_render_item_meta_bar()
{
    if (!function_exists('osc_item_id') || (int) osc_item_id() <= 0) {
        return;
    }

    $posted = function_exists('eps_smart_date')
        ? eps_smart_date(osc_item_pub_date())
        : osc_format_date(osc_item_pub_date());
    $views = function_exists('osc_item_views') ? (int) osc_item_views() : 0;
    $id = (int) osc_item_id();

    $parts = array();
    $parts[] = sprintf(__('Posted %s', 'epsilon'), $posted);
    $parts[] = sprintf(__('%d views', 'epsilon'), $views);
    $parts[] = sprintf(__('Listing ID: %s', 'epsilon'), $id);

    echo '<p class="pngm-item-meta-bar">'
        . osc_esc_html($parts[0])
        . ' &middot; '
        . osc_esc_html($parts[1])
        . ' &middot; '
        . osc_esc_html($parts[2])
        . '</p>';
}

/**
 * Inject SVG icons into plugin attribute rows and mark the block as visual.
 *
 * @param string $html
 * @return string
 */
function pngm_atr_enhance_html($html)
{
    if (!is_string($html) || trim($html) === '') {
        return '';
    }

    // Mark for our CSS (keep existing theme/styled classes)
    if (strpos($html, 'pngm-atr-visual') === false) {
        $html = preg_replace('/(<ul\b[^>]*\bid=["\']atr-item["\'][^>]*\bclass=["\'])/', '$1pngm-atr-visual ', $html, 1);
        if (strpos($html, 'pngm-atr-visual') === false) {
            $html = preg_replace('/(<ul\b[^>]*\bid=["\']atr-item["\'])/', '$1 class="pngm-atr-visual"', $html, 1);
        }
    }

    // Drop check/cross images and empty value chips (avoids ", Automatic")
    $html = preg_replace('/<img\b[^>]*class="[^"]*atr-img[^"]*"[^>]*>/i', '', $html);
    $html = preg_replace('/<span class="atr-value-single[^"]*"[^>]*>\s*<\/span>/i', '', $html);

    // Inject one SVG icon after each attribute <li ...>
    $html = preg_replace_callback(
        '/<li\b([^>]*)>/i',
        function ($m) {
            $attrs = isset($m[1]) ? $m[1] : '';
            // Only attribute detail rows
            if (stripos($attrs, 'atr-line') === false) {
                return $m[0];
            }
            if (stripos($attrs, 'pngm-atr-ico') !== false) {
                return $m[0];
            }

            $id = '';
            if (preg_match('/\bid=["\']atr-([^"\']+)["\']/i', $attrs, $im)) {
                $id = $im[1];
            }

            $type = '';
            if (preg_match('/\batr-type-([a-z0-9_-]+)/i', $attrs, $tm)) {
                $type = strtoupper(str_replace('-', '', $tm[1]));
            }

            $a = array(
                's_identifier' => $id,
                's_type' => $type,
                's_name' => str_replace(array('-', '_'), ' ', $id),
            );

            $icon = '<span class="pngm-atr-ico" aria-hidden="true">' . pngm_atr_svg_icon($a) . '</span>';
            return '<li' . $attrs . '>' . $icon;
        },
        $html
    );

    return $html;
}

/**
 * Replace plugin item_detail attributes with enhanced visual output.
 *
 * Uses the plugin's atr_show_item() so attribute data always renders the same
 * way as before; we only enhance markup (icons + CSS class).
 *
 * @param array $item
 */
function pngm_atr_show_item($item)
{
    if (!function_exists('atr_show_item')) {
        return;
    }
    if (!is_array($item) || empty($item['pk_i_id'])) {
        return;
    }

    ob_start();
    atr_show_item($item);
    $html = ob_get_clean();

    $html = pngm_atr_enhance_html($html);
    if ($html === '') {
        return;
    }

    echo $html;
}

/**
 * Swap Attributes plugin hook for our visual renderer.
 * Osclass only runs hook priorities 0–10 — never use >10.
 */
function pngm_atr_boot_item_display()
{
    if (!function_exists('atr_show_item')) {
        return;
    }
    osc_remove_hook('item_detail', 'atr_show_item');
    osc_remove_hook('item_detail', 'pngm_atr_show_item');
    osc_add_hook('item_detail', 'pngm_atr_show_item', 5);
}

// Plugins load before the theme, so atr_show_item already exists here.
pngm_atr_boot_item_display();
osc_add_hook('init', 'pngm_atr_boot_item_display', 10);
