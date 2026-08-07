<?php
/**
 * PNG location helpers — main cities first, provincial capitals first.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'locations.php'
) {
    exit;
}

/**
 * Priority list of PNG main cities / provincial capitals (lower = higher priority).
 *
 * @return array name(lowercase) => priority
 */
function pngm_main_city_priorities()
{
    static $map = null;

    if ($map !== null) {
        return $map;
    }

    // National capital first, then major centres / provincial capitals.
    $ordered = array(
        'Port Moresby',
        'Lae',
        'Mount Hagen',
        'Madang',
        'Goroka',
        'Kokopo',
        'Kimbe',
        'Wewak',
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
        'Bulolo',
        'Kiunga',
        'Tabubil',
        'Wau',
        'Rabaul',
        'Lorengau',
        'Tari',
        'Wabag',
        'Minj',
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

    return $name;
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

    if ($region_name !== null && $region_name !== '') {
        $caps = pngm_province_capitals();
        $rk = pngm_location_key($region_name);

        if (isset($caps[$rk])) {
            $capital_key = pngm_location_key($caps[$rk]);
        }
    }

    usort($cities, function ($a, $b) use ($priorities, $capital_key, $name_key) {
        $an = pngm_location_key(isset($a[$name_key]) ? $a[$name_key] : (isset($a['s_name']) ? $a['s_name'] : ''));
        $bn = pngm_location_key(isset($b[$name_key]) ? $b[$name_key] : (isset($b['s_name']) ? $b['s_name'] : ''));

        $ap = isset($priorities[$an]) ? $priorities[$an] : 1000;
        $bp = isset($priorities[$bn]) ? $priorities[$bn] : 1000;

        // Province capital always wins within that region's city list.
        if ($capital_key !== '') {
            if ($an === $capital_key && $bn !== $capital_key) {
                return -1;
            }
            if ($bn === $capital_key && $an !== $capital_key) {
                return 1;
            }
        }

        if ($ap !== $bp) {
            return ($ap < $bp) ? -1 : 1;
        }

        return strcasecmp($an, $bn);
    });

    return $cities;
}

/**
 * Popular cities for location picker: main PNG cities first, then by listings.
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

    // Pull known main cities from DB (may have 0 listings).
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_TABLE_PREFIX')) {
        try {
            $m = @new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

            if ($m && !$m->connect_error) {
                $m->set_charset('utf8mb4');
                $prefix = DB_TABLE_PREFIX;

                // Query by exact main city names.
                $names = array(
                    'Port Moresby', 'Lae', 'Mount Hagen', 'Madang', 'Goroka', 'Kokopo',
                    'Kimbe', 'Wewak', 'Vanimo', 'Alotau', 'Kavieng', 'Mendi', 'Kundiawa',
                    'Popondetta', 'Daru', 'Kerema', 'Buka',
                );
                $in = array();

                foreach ($names as $n) {
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
                $rows = array();

                if ($r) {
                    while ($row = $r->fetch_assoc()) {
                        $rows[] = $row;
                    }
                }

                $rows = pngm_sort_cities_main_first($rows, null, 's_name');

                foreach ($rows as $row) {
                    // Prefer the canonical region match for duplicate city names
                    // (e.g. Kokopo in East New Britain over wrong duplicates).
                    $key = pngm_location_key($row['s_name']);

                    if (isset($seen[$key])) {
                        continue;
                    }

                    // Skip known wrong-province duplicates for shared names.
                    if ($key === 'kokopo' && stripos($row['s_name_top'], 'East New Britain') === false) {
                        continue;
                    }

                    if ($key === 'buka' && stripos($row['s_name_top'], 'Bougainville') === false) {
                        continue;
                    }

                    if ($key === 'mendi' && stripos($row['s_name_top'], 'Southern Highlands') === false) {
                        continue;
                    }

                    $seen[$key] = true;
                    $out[] = $row;

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

    // If a region is in the results, also try to surface its capital city first.
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
 * Intercept core ajax cities list — capital / main city first for the province.
 */
function pngm_ajax_cities_capital_first()
{
    if (Params::getParam('page') !== 'ajax' || Params::getParam('action') !== 'cities') {
        return;
    }

    $region_id = (int) Params::getParam('regionId');

    if ($region_id <= 0 || !class_exists('City') || !class_exists('Region')) {
        return;
    }

    $cities = City::newInstance()->findByRegion($region_id);
    $region = Region::newInstance()->findByPrimaryKey($region_id);
    $region_name = is_array($region) && isset($region['s_name']) ? $region['s_name'] : '';
    $cities = pngm_sort_cities_main_first($cities, $region_name, 's_name');

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_values($cities));
    exit;
}

if (function_exists('osc_add_hook')) {
    osc_add_hook('init_ajax', 'pngm_ajax_cities_capital_first', 1);
}
