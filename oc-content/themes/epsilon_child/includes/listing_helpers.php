<?php
/**
 * Listing display helpers — price format, city-only labels.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'listing_helpers.php'
) {
    exit;
}

/**
 * PNG price: "K 1,000" (no decimals, symbol first).
 *
 * @return string
 */
function pngm_format_price($price = null, $currency = null)
{
    if ($price === null && function_exists('osc_item_price')) {
        $price = osc_item_price();
        $currency = function_exists('osc_item_currency') ? osc_item_currency() : $currency;
    }

    if ($price === '' || $price === null || (float) $price <= 0) {
        if (function_exists('osc_item_formated_price')) {
            return osc_item_formated_price();
        }
        return '';
    }

    $amount = ((float) $price) / 1000000;
    $code = strtoupper(trim((string) $currency));
    $symbol = ($code === 'PGK' || $code === 'K' || $code === '') ? 'K' : $code;

    if ($amount == floor($amount)) {
        $num = number_format($amount, 0, '.', ',');
    } else {
        $num = number_format($amount, 2, '.', ',');
    }

    return $symbol . ' ' . $num;
}

/**
 * City name only — no province (Port Moresby stays Port Moresby).
 *
 * @param array|string $row
 * @return string
 */
function pngm_city_only($row = null)
{
    if (is_string($row) && $row !== '') {
        $name = $row;
    } elseif (is_array($row)) {
        if (function_exists('osc_location_native_name_selector')) {
            $name = osc_location_native_name_selector($row, 's_name');
        } else {
            $name = isset($row['s_name']) ? $row['s_name'] : '';
        }
    } elseif (function_exists('osc_item_city')) {
        $name = osc_item_city();
    } else {
        $name = '';
    }

    $name = trim((string) $name);
    if ($name === '') {
        return '';
    }

    // Strip trailing province if a cookie/location string includes it.
    $name = preg_replace('/,\s*(National Capital District|NCD|Morobe Province|.*Province)$/i', '', $name);

    return $name;
}

/**
 * Label for a main-city picker row (city only, optional item count).
 *
 * @param array $c
 * @return string
 */
function pngm_main_city_label($c)
{
    $city = pngm_city_only($c);
    $count = isset($c['i_num_items']) ? (int) $c['i_num_items'] : 0;
    $html = osc_esc_html($city);

    if ($count > 0) {
        $html .= ' <em>' . $count . ' ' . ($count === 1 ? __('item', 'epsilon') : __('items', 'epsilon')) . '</em>';
    }

    return $html;
}
