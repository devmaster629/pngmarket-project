<?php
/**
 * PNG location helpers — main cities first, provincial capitals / towns first.
 * LOCATION-01 / LOCATION-02 (sorting & presentation only; no DB deletes).
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'locations.php'
) {
    exit;
}

/**
 * Priority list of PNG main cities (lower = higher priority).
 * LOCATION-01 order: Port Moresby, Lae, Mount Hagen, Madang, Kokopo, Goroka, Wewak, …
 *
 * @return array name(lowercase) => priority
 */
function pngm_main_city_priorities()
{
    static $map = null;

    if ($map !== null) {
        return $map;
    }

    $ordered = array(
        // LOCATION-01 featured list (exact order).
        'Port Moresby',
        'Lae',
        'Mount Hagen',
        'Madang',
        'Kokopo',
        'Goroka',
        'Wewak',
        // Other provincial capitals / major towns.
        'Kimbe',
        'Vanimo',
        'Alotau',
        'Kavieng',
        'Mendi',
        'Kundiawa',
        'Popondetta',
        'Daru',
        'Kerema',
        'Buka',
        'Arawa',
        'Lorengau',
        'Tari',
        'Wabag',
        'Minj',
        'Bulolo',
        'Wau',
        'Kiunga',
        'Tabubil',
        'Rabaul',
        'Maprik',
    );

    $map = array();
    $i = 1;

    foreach ($ordered as $name) {
        $map[pngm_location_key($name)] = $i++;
    }

    return $map;
}

/**
 * Most-used PNG provinces first when posting an ad.
 *
 * @return array lowercase name => priority (lower = higher)
 */
function pngm_popular_province_priorities()
{
    static $map = null;

    if ($map !== null) {
        return $map;
    }

    $ordered = array(
        'National Capital District',
        'Morobe Province',
        'Western Highlands Province',
        'Madang Province',
        'Eastern Highlands Province',
        'East New Britain Province',
        'East Sepik Province',
        'Central Province',
        'West New Britain Province',
        'Southern Highlands Province',
        'West Sepik Province',
        'Sandaun Province',
        'Milne Bay Province',
        'New Ireland Province',
        'Chimbu Province',
        'Simbu Province',
        'Enga Province',
        'Northern Province',
        'Oro Province',
        'Western Province',
        'Gulf Province',
        'Bougainville',
        'Autonomous Region of Bougainville',
        'Manus Province',
        'Hela Province',
        'Jiwaka Province',
    );

    $map = array();
    $i = 1;

    foreach ($ordered as $name) {
        $map[pngm_location_key($name)] = $i++;
    }

    return $map;
}

/**
 * @param array $regions
 * @param string $name_key
 * @return array
 */
function pngm_sort_regions_popular_first($regions, $name_key = 's_name')
{
    if (!is_array($regions) || count($regions) === 0) {
        return is_array($regions) ? $regions : array();
    }

    $priorities = pngm_popular_province_priorities();

    usort($regions, function ($a, $b) use ($priorities, $name_key) {
        $an = pngm_location_key(isset($a[$name_key]) ? $a[$name_key] : (isset($a['s_name']) ? $a['s_name'] : ''));
        $bn = pngm_location_key(isset($b[$name_key]) ? $b[$name_key] : (isset($b['s_name']) ? $b['s_name'] : ''));
        $ap = isset($priorities[$an]) ? $priorities[$an] : 1000;
        $bp = isset($priorities[$bn]) ? $priorities[$bn] : 1000;

        if ($ap !== $bp) {
            return ($ap < $bp) ? -1 : 1;
        }

        return strcasecmp($an, $bn);
    });

    return $regions;
}

/**
 * If a province capital is missing from that region's city list, prepend it
 * from the national city table (e.g. Port Moresby for Central Province).
 *
 * @param array  $cities
 * @param string $region_name
 * @param string $name_key
 * @return array
 */
function pngm_inject_province_capital($cities, $region_name, $name_key = 's_name')
{
    if (!is_array($cities)) {
        $cities = array();
    }

    $region_name = trim((string) $region_name);
    if ($region_name === '') {
        return $cities;
    }

    $capital_name = pngm_capital_for_region($region_name);

    if ($capital_name === '') {
        return $cities;
    }

    foreach ($cities as $city) {
        $name = isset($city[$name_key]) ? $city[$name_key] : (isset($city['s_name']) ? $city['s_name'] : '');
        if (pngm_city_is_capital($name, $capital_name)) {
            return $cities;
        }
    }

    if (!class_exists('City')) {
        return $cities;
    }

    $region_id = 0;
    foreach ($cities as $city) {
        if (!empty($city['fk_i_region_id'])) {
            $region_id = (int) $city['fk_i_region_id'];
            break;
        }
    }

    $found = array();
    if ($region_id > 0) {
        $found = City::newInstance()->findByName($capital_name, $region_id);
    }
    if (!is_array($found) || empty($found['pk_i_id'])) {
        $found = City::newInstance()->findByName($capital_name);
    }
    if (is_array($found) && !empty($found['pk_i_id'])) {
        $found['pngm_tier'] = 'main';
        array_unshift($cities, $found);
    }

    return $cities;
}

/**
 * Map province/region name → preferred capital city name.
 *
 * @return array
 */
function pngm_province_capitals()
{
    return array(
        'national capital district' => 'Port Moresby',
        'morobe province'           => 'Lae',
        'morobe'                    => 'Lae',
        'western highlands province'=> 'Mount Hagen',
        'western highlands'         => 'Mount Hagen',
        'madang province'           => 'Madang',
        'madang'                    => 'Madang',
        'eastern highlands province'=> 'Goroka',
        'eastern highlands'         => 'Goroka',
        'east new britain province' => 'Kokopo',
        'east new britain'          => 'Kokopo',
        'west new britain province' => 'Kimbe',
        'west new britain'          => 'Kimbe',
        'east sepik province'       => 'Wewak',
        'east sepik'                => 'Wewak',
        'west sepik province'       => 'Vanimo',
        'west sepik'                => 'Vanimo',
        'sandaun province'          => 'Vanimo',
        'sandaun'                   => 'Vanimo',
        'milne bay province'        => 'Alotau',
        'milne bay'                 => 'Alotau',
        'new ireland province'      => 'Kavieng',
        'new ireland'               => 'Kavieng',
        'southern highlands province'=> 'Mendi',
        'southern highlands'        => 'Mendi',
        'chimbu province'           => 'Kundiawa',
        'chimbu'                    => 'Kundiawa',
        'simbu province'            => 'Kundiawa',
        'simbu'                     => 'Kundiawa',
        'northern province'         => 'Popondetta',
        'oro province'              => 'Popondetta',
        'oro'                       => 'Popondetta',
        'western province'          => 'Daru',
        'western'                   => 'Daru',
        'gulf province'             => 'Kerema',
        'gulf'                      => 'Kerema',
        'bougainville'              => 'Buka',
        'autonomous region of bougainville' => 'Buka',
        'manus province'            => 'Lorengau',
        'manus'                     => 'Lorengau',
        'enga province'             => 'Wabag',
        'enga'                      => 'Wabag',
        'hela province'             => 'Tari',
        'hela'                      => 'Tari',
        'jiwaka province'           => 'Minj',
        'jiwaka'                    => 'Minj',
        'central province'          => 'Port Moresby',
        'central'                   => 'Port Moresby',
    );
}

/**
 * Extra main towns per province (beyond the capital).
 * Villages not listed here appear under “Other locations”.
 *
 * @return array region_key => list of town names
 */
function pngm_province_main_towns()
{
    return array(
        'national capital district' => array('Port Moresby'),
        'morobe province'           => array('Lae', 'Bulolo', 'Wau'),
        'morobe'                    => array('Lae', 'Bulolo', 'Wau'),
        'western highlands province'=> array('Mount Hagen'),
        'western highlands'         => array('Mount Hagen'),
        'madang province'           => array('Madang'),
        'madang'                    => array('Madang'),
        'eastern highlands province'=> array('Goroka'),
        'eastern highlands'         => array('Goroka'),
        'east new britain province' => array('Kokopo', 'Rabaul'),
        'east new britain'          => array('Kokopo', 'Rabaul'),
        'west new britain province' => array('Kimbe'),
        'west new britain'          => array('Kimbe'),
        'east sepik province'       => array('Wewak', 'Maprik'),
        'east sepik'                => array('Wewak', 'Maprik'),
        'west sepik province'       => array('Vanimo'),
        'west sepik'                => array('Vanimo'),
        'sandaun province'          => array('Vanimo'),
        'sandaun'                   => array('Vanimo'),
        'milne bay province'        => array('Alotau'),
        'milne bay'                 => array('Alotau'),
        'new ireland province'      => array('Kavieng'),
        'new ireland'               => array('Kavieng'),
        'southern highlands province'=> array('Mendi'),
        'southern highlands'        => array('Mendi'),
        'chimbu province'           => array('Kundiawa'),
        'chimbu'                    => array('Kundiawa'),
        'simbu province'            => array('Kundiawa'),
        'simbu'                     => array('Kundiawa'),
        'northern province'         => array('Popondetta'),
        'oro province'              => array('Popondetta'),
        'oro'                       => array('Popondetta'),
        'western province'          => array('Daru', 'Kiunga', 'Tabubil'),
        'western'                   => array('Daru', 'Kiunga', 'Tabubil'),
        'gulf province'             => array('Kerema'),
        'gulf'                      => array('Kerema'),
        'bougainville'              => array('Buka', 'Arawa'),
        'autonomous region of bougainville' => array('Buka', 'Arawa'),
        'manus province'            => array('Lorengau'),
        'manus'                     => array('Lorengau'),
        'enga province'             => array('Wabag'),
        'enga'                      => array('Wabag'),
        'hela province'             => array('Tari'),
        'hela'                      => array('Tari'),
        'jiwaka province'           => array('Minj', 'Kurumul'),
        'jiwaka'                    => array('Minj', 'Kurumul'),
        'central province'          => array('Port Moresby'),
        'central'                   => array('Port Moresby'),
    );
}

/**
 * @param string $name
 * @return string
 */
function pngm_location_key($name)
{
    $name = function_exists('mb_strtolower') ? mb_strtolower(trim((string) $name), 'UTF-8') : strtolower(trim((string) $name));
    $name = preg_replace('/\s+/u', ' ', $name);
    $name = str_replace(array('mt.', 'mt '), 'mount ', $name);
    $name = preg_replace('/\s+/u', ' ', $name);

    return $name;
}

/**
 * Lookup keys for a province label from the DB / select (with/without "Province").
 *
 * @param string $name
 * @return array
 */
function pngm_region_lookup_keys($name)
{
    $key = pngm_location_key($name);
    $keys = array();

    if ($key === '') {
        return $keys;
    }

    $keys[$key] = true;
    $stripped = preg_replace('/\s+(province|district)$/u', '', $key);

    if ($stripped !== '' && $stripped !== $key) {
        $keys[$stripped] = true;
    }

    if ($stripped !== '' && substr($key, -9) !== 'province' && substr($key, -8) !== 'district') {
        $keys[$stripped . ' province'] = true;
    }

    return array_keys($keys);
}

/**
 * Capital city name for a province label, or empty string.
 *
 * @param string $region_name
 * @return string
 */
function pngm_capital_for_region($region_name)
{
    $caps = pngm_province_capitals();

    foreach (pngm_region_lookup_keys($region_name) as $key) {
        if (isset($caps[$key])) {
            return $caps[$key];
        }
    }

    return '';
}

/**
 * Whether a city label is the province capital (exact or "Kerema Town" style).
 *
 * @param string $city_name
 * @param string $capital_name
 * @return bool
 */
function pngm_city_is_capital($city_name, $capital_name)
{
    $city = pngm_location_key($city_name);
    $cap = pngm_location_key($capital_name);

    if ($city === '' || $cap === '') {
        return false;
    }

    if ($city === $cap) {
        return true;
    }

    return (bool) preg_match('/(^|[\s,\-\/])' . preg_quote($cap, '/') . '($|[\s,\-\/])/u', $city);
}

/**
 * Main-town name keys for a province (capital + listed towns).
 * When no region is given, returns the national main-city set (LOCATION-01).
 *
 * @param string|null $region_name
 * @return array name_key => true
 */
function pngm_main_town_keys_for_region($region_name = null)
{
    $keys = array();

    if ($region_name === null || $region_name === '') {
        foreach (array_keys(pngm_main_city_priorities()) as $key) {
            $keys[$key] = true;
        }

        return $keys;
    }

    $rk_keys = pngm_region_lookup_keys($region_name);
    $caps = pngm_province_capitals();
    $towns = pngm_province_main_towns();

    foreach ($rk_keys as $rk) {
        if (isset($caps[$rk])) {
            $keys[pngm_location_key($caps[$rk])] = true;
        }

        if (isset($towns[$rk]) && is_array($towns[$rk])) {
            foreach ($towns[$rk] as $town) {
                $keys[pngm_location_key($town)] = true;
            }
        }
    }

    // Unmapped province: fall back to national main-city names so villages
    // still do not dominate when those names appear in the list.
    if (count($keys) === 0) {
        foreach (array_keys(pngm_main_city_priorities()) as $key) {
            $keys[$key] = true;
        }
    }

    return $keys;
}

/**
 * Whether a city name is a main town for the given province.
 *
 * @param string      $city_name
 * @param string|null $region_name
 * @return bool
 */
function pngm_is_main_town($city_name, $region_name = null)
{
    $key = pngm_location_key($city_name);

    if ($key === '') {
        return false;
    }

    $keys = pngm_main_town_keys_for_region($region_name);

    return isset($keys[$key]);
}

/**
 * Sort city rows so main cities / capitals come first.
 *
 * @param array       $cities
 * @param string|null $region_name Optional region name to boost that province's capital.
 * @param string      $name_key    Field holding city name (s_name or name).
 *
 * @return array
 */
function pngm_sort_cities_main_first($cities, $region_name = null, $name_key = 's_name')
{
    if (!is_array($cities) || count($cities) === 0) {
        return is_array($cities) ? $cities : array();
    }

    $priorities = pngm_main_city_priorities();
    $capital_key = '';
    $main_keys = pngm_main_town_keys_for_region($region_name);

    if ($region_name !== null && $region_name !== '') {
        $capital_name = pngm_capital_for_region($region_name);
        if ($capital_name !== '') {
            $capital_key = pngm_location_key($capital_name);
        }
    }

    usort($cities, function ($a, $b) use ($priorities, $capital_key, $name_key, $main_keys) {
        $an_raw = isset($a[$name_key]) ? $a[$name_key] : (isset($a['s_name']) ? $a['s_name'] : '');
        $bn_raw = isset($b[$name_key]) ? $b[$name_key] : (isset($b['s_name']) ? $b['s_name'] : '');
        $an = pngm_location_key($an_raw);
        $bn = pngm_location_key($bn_raw);

        $a_cap = ($capital_key !== '' && pngm_city_is_capital($an_raw, $capital_key));
        $b_cap = ($capital_key !== '' && pngm_city_is_capital($bn_raw, $capital_key));

        if ($a_cap && !$b_cap) {
            return -1;
        }
        if ($b_cap && !$a_cap) {
            return 1;
        }

        $a_main = isset($main_keys[$an]) || $a_cap;
        $b_main = isset($main_keys[$bn]) || $b_cap;

        if ($a_main && !$b_main) {
            return -1;
        }

        if ($b_main && !$a_main) {
            return 1;
        }

        $ap = isset($priorities[$an]) ? $priorities[$an] : 1000;
        $bp = isset($priorities[$bn]) ? $priorities[$bn] : 1000;

        if ($ap !== $bp) {
            return ($ap < $bp) ? -1 : 1;
        }

        return strcasecmp($an, $bn);
    });

    return $cities;
}

/**
 * Tag cities with pngm_tier = main|other and sort main first.
 *
 * @param array       $cities
 * @param string|null $region_name
 * @param string      $name_key
 * @return array
 */
function pngm_prepare_cities_for_posting($cities, $region_name = null, $name_key = 's_name')
{
    if (!is_array($cities)) {
        return array();
    }

    $cities = pngm_inject_province_capital($cities, $region_name, $name_key);
    $cities = pngm_sort_cities_main_first($cities, $region_name, $name_key);
    $main_keys = pngm_main_town_keys_for_region($region_name);

    foreach ($cities as $i => $city) {
        $name = isset($city[$name_key]) ? $city[$name_key] : (isset($city['s_name']) ? $city['s_name'] : '');
        $key = pngm_location_key($name);
        $capital_name = pngm_capital_for_region((string) $region_name);
        $is_cap = ($capital_name !== '' && pngm_city_is_capital($name, $capital_name));
        $cities[$i]['pngm_tier'] = (isset($main_keys[$key]) || $is_cap) ? 'main' : 'other';
    }

    return array_values($cities);
}

/**
 * Approximate GPS for PNG main cities (used when DB city coords are empty).
 *
 * @return array name-key => array(lat, lon)
 */
function pngm_main_city_coords()
{
    return array(
        'port moresby'  => array(-9.4438, 147.1803),
        'lae'           => array(-6.7333, 146.9833),
        'mount hagen'   => array(-5.8581, 144.2325),
        'madang'        => array(-5.2219, 145.7869),
        'kokopo'        => array(-4.3520, 152.2633),
        'goroka'        => array(-6.0817, 145.3878),
        'wewak'         => array(-3.5500, 143.6333),
        'kimbe'         => array(-5.5500, 150.1430),
        'vanimo'        => array(-2.6741, 141.3028),
        'alotau'        => array(-10.3167, 150.4667),
        'kavieng'       => array(-2.5667, 150.8000),
        'mendi'         => array(-6.1478, 143.6560),
        'kundiawa'      => array(-6.0167, 144.9667),
        'popondetta'    => array(-8.7667, 148.2333),
        'daru'          => array(-9.0833, 143.2000),
        'kerema'        => array(-7.9631, 145.7785),
        'buka'          => array(-5.4300, 154.6700),
    );
}

/**
 * Nearest known PNG city to a GPS point.
 *
 * @param float $lat
 * @param float $lon
 * @return array|null
 */
function pngm_nearest_city_from_coords($lat, $lon)
{
    $lat = (float) $lat;
    $lon = (float) $lon;
    $best = null;
    $best_d = null;

    $cities = pngm_get_popular_cities(20);
    $coords = pngm_main_city_coords();

    if (!is_array($cities)) {
        $cities = array();
    }

    foreach ($cities as $c) {
        $key = function_exists('pngm_location_key') ? pngm_location_key($c['s_name']) : strtolower($c['s_name']);
        $clat = isset($c['d_coord_lat']) ? (float) $c['d_coord_lat'] : 0;
        $clon = isset($c['d_coord_long']) ? (float) $c['d_coord_long'] : 0;

        if (($clat == 0.0 && $clon == 0.0) && isset($coords[$key])) {
            $clat = $coords[$key][0];
            $clon = $coords[$key][1];
            $c['d_coord_lat'] = $clat;
            $c['d_coord_long'] = $clon;
        }

        if ($clat == 0.0 && $clon == 0.0) {
            continue;
        }

        $dlat = deg2rad($clat - $lat);
        $dlon = deg2rad($clon - $lon);
        $a = sin($dlat / 2) * sin($dlat / 2) + cos(deg2rad($lat)) * cos(deg2rad($clat)) * sin($dlon / 2) * sin($dlon / 2);
        $km = 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));

        if ($best_d === null || $km < $best_d) {
            $best_d = $km;
            $best = $c;
            $best['d_distance'] = $km;
            $best['d_distance_precise'] = $km;
            $best['s_city'] = $c['s_name'];
            $best['s_region'] = isset($c['s_name_top']) ? $c['s_name_top'] : '';
            $best['s_city_native'] = isset($c['s_name_native']) ? $c['s_name_native'] : '';
            $best['s_region_native'] = isset($c['s_name_top_native']) ? $c['s_name_top_native'] : '';
        }
    }

    return $best;
}

/**
 * Popular / main cities for location picker (LOCATION-01).
 *
 * @param int $limit
 *
 * @return array
 */
function pngm_get_popular_cities($limit = 12)
{
    $limit = max(1, (int) $limit);
    $out = array();
    $seen = array();

    // LOCATION-01 featured names first (exact order).
    $featured = array(
        'Port Moresby', 'Lae', 'Mount Hagen', 'Madang', 'Kokopo', 'Goroka', 'Wewak',
        'Kimbe', 'Vanimo', 'Alotau', 'Kavieng', 'Mendi', 'Kundiawa',
        'Popondetta', 'Daru', 'Kerema', 'Buka',
    );

    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_TABLE_PREFIX')) {
        try {
            $m = @new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

            if ($m && !$m->connect_error) {
                $m->set_charset('utf8mb4');
                $prefix = DB_TABLE_PREFIX;

                $in = array();

                foreach ($featured as $n) {
                    $in[] = "'" . $m->real_escape_string($n) . "'";
                }

                $sql = "SELECT c.pk_i_id AS fk_i_city_id, c.fk_i_region_id, c.fk_c_country_code,
                               c.s_name, r.s_name AS s_name_top, c.s_name_native,
                               r.s_name_native AS s_name_top_native, c.s_slug,
                               COALESCE(s.i_num_items, 0) AS i_num_items,
                               c.d_coord_lat, c.d_coord_long
                        FROM {$prefix}t_city c
                        INNER JOIN {$prefix}t_region r ON r.pk_i_id = c.fk_i_region_id
                        LEFT JOIN {$prefix}t_city_stats s ON s.fk_i_city_id = c.pk_i_id
                        WHERE c.s_name IN (" . implode(',', $in) . ")
                          AND c.b_active = 1";
                $r = $m->query($sql);
                $by_name = array();

                if ($r) {
                    while ($row = $r->fetch_assoc()) {
                        $key = pngm_location_key($row['s_name']);

                        // Prefer canonical province for known ambiguous names.
                        if ($key === 'kokopo' && stripos($row['s_name_top'], 'East New Britain') === false) {
                            continue;
                        }

                        if ($key === 'buka' && stripos($row['s_name_top'], 'Bougainville') === false) {
                            continue;
                        }

                        if ($key === 'mendi' && stripos($row['s_name_top'], 'Southern Highlands') === false) {
                            continue;
                        }

                        if ($key === 'port moresby' && stripos($row['s_name_top'], 'National Capital') === false
                            && stripos($row['s_name_top'], 'Central') === false) {
                            continue;
                        }

                        if (!isset($by_name[$key])) {
                            $by_name[$key] = $row;
                        }
                    }
                }

                // Emit in LOCATION-01 order.
                foreach ($featured as $name) {
                    $key = pngm_location_key($name);

                    if (!isset($by_name[$key]) || isset($seen[$key])) {
                        continue;
                    }

                    $seen[$key] = true;
                    $out[] = $by_name[$key];

                    if (count($out) >= $limit) {
                        break;
                    }
                }

                $m->close();
            }
        } catch (Throwable $e) {
            // Fall through to ModelEPS popular list.
        }
    }

    if (count($out) >= $limit) {
        return array_slice($out, 0, $limit);
    }

    if (!class_exists('ModelEPS')) {
        return array_slice($out, 0, $limit);
    }

    $extra = ModelEPS::newInstance()->getPopularCities($limit * 2, 0);

    if (is_array($extra)) {
        foreach ($extra as $c) {
            $key = pngm_location_key(isset($c['s_name']) ? $c['s_name'] : '');

            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $out[] = $c;

            if (count($out) >= $limit) {
                break;
            }
        }
    }

    return array_slice($out, 0, $limit);
}

/**
 * Sort ajaxLoc mixed results: keep type order country → region → city,
 * but within cities put main cities first.
 *
 * @param array $data
 *
 * @return array
 */
function pngm_sort_ajax_loc_results($data)
{
    if (!is_array($data) || count($data) === 0) {
        return is_array($data) ? $data : array();
    }

    $countries = array();
    $regions = array();
    $cities = array();
    $other = array();

    foreach ($data as $row) {
        $type = isset($row['type']) ? $row['type'] : '';

        if ($type === 'country') {
            $countries[] = $row;
        } elseif ($type === 'region') {
            $regions[] = $row;
        } elseif ($type === 'city') {
            $cities[] = $row;
        } else {
            $other[] = $row;
        }
    }

    $cities = pngm_sort_cities_main_first($cities, null, 'name');
    $regions = pngm_sort_regions_popular_first($regions, 'name');

    if (count($regions) > 0 && count($cities) > 0) {
        $caps = pngm_province_capitals();
        $boost = array();

        foreach ($regions as $region) {
            $rk = pngm_location_key(isset($region['name']) ? $region['name'] : '');

            if (isset($caps[$rk])) {
                $boost[pngm_location_key($caps[$rk])] = true;
            }
        }

        if (count($boost) > 0) {
            usort($cities, function ($a, $b) use ($boost) {
                $an = pngm_location_key(isset($a['name']) ? $a['name'] : '');
                $bn = pngm_location_key(isset($b['name']) ? $b['name'] : '');
                $ab = isset($boost[$an]);
                $bb = isset($boost[$bn]);

                if ($ab && !$bb) {
                    return -1;
                }

                if ($bb && !$ab) {
                    return 1;
                }

                return 0;
            });
        }
    }

    return array_merge($countries, $regions, $cities, $other);
}

/**
 * Config exposed to front-end for city select regrouping.
 *
 * @return array
 */
function pngm_location_js_config()
{
    $main = array();

    foreach (array_keys(pngm_main_city_priorities()) as $key) {
        $main[] = $key;
    }

    $provinces = array();
    foreach (array_keys(pngm_popular_province_priorities()) as $key) {
        $provinces[] = $key;
    }

    return array(
        'mainCities' => $main,
        'popularProvinces' => $provinces,
        'capitals'   => pngm_province_capitals(),
        'mainTowns'  => pngm_province_main_towns(),
        'labels'     => array(
            'main'          => __('Main towns', 'epsilon'),
            'other'         => __('Other locations', 'epsilon'),
            'select'        => __('Select a city...', 'epsilon'),
            'popular'       => __('Main cities', 'epsilon'),
            'searchRegion'  => __('Type to search province...', 'epsilon'),
            'searchCity'    => __('Type to search city...', 'epsilon'),
            'noMatch'       => __('No matching locations', 'epsilon'),
        ),
    );
}

/**
 * Print JS config for LOCATION-01/02 (header).
 */
function pngm_print_location_js_config()
{
    echo '<script>window.pngmLocationConfig=' . json_encode(pngm_location_js_config()) . ';</script>' . "\n";
}

/**
 * Intercept core ajax cities list — main towns first + tier tags (LOCATION-02).
 */
function pngm_ajax_cities_capital_first()
{
    if (Params::getParam('action') !== 'cities') {
        return;
    }

    $region_id = (int) Params::getParam('regionId');

    if ($region_id <= 0 || !class_exists('City') || !class_exists('Region')) {
        return;
    }

    $cities = City::newInstance()->findByRegion($region_id);
    $region = Region::newInstance()->findByPrimaryKey($region_id);
    $region_name = is_array($region) && isset($region['s_name']) ? $region['s_name'] : '';
    $cities = pngm_prepare_cities_for_posting($cities, $region_name, 's_name');

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($cities);
    exit;
}

function pngm_ajax_regions_popular_first()
{
    if (Params::getParam('page') !== 'ajax' || Params::getParam('action') !== 'regions') {
        return;
    }

    $country_id = Params::getParam('countryId');

    if ($country_id === '' || $country_id === null || !class_exists('Region')) {
        return;
    }

    $regions = Region::newInstance()->findByCountry($country_id);
    $regions = pngm_sort_regions_popular_first($regions, 's_name');

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($regions);
    exit;
}

if (function_exists('osc_add_hook')) {
    osc_add_hook('init_ajax', 'pngm_ajax_cities_capital_first', 1);
    osc_add_hook('init_ajax', 'pngm_ajax_regions_popular_first', 1);
    osc_add_hook('header', 'pngm_print_location_js_config', 9);
}
