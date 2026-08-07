<?php
/**
 * VEHICLE-01 / VEHICLE-02 / VEHICLE-03
 *
 * Seed PNG-common vehicle makes & models under the Attributes "make" SELECT,
 * add Other options, optional free-text field, and drop incomplete 3rd level.
 *
 * Idempotent — safe to run on every request via init hook.
 */

// Prevent direct web access to this include.
if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'vehicle_makes.php'
) {
    exit;
}

/**
 * Brand => models map for PNG market + keep/extend EU seed brands.
 *
 * @return array
 */
function pngm_vehicle_makes_catalog()
{
    return array(
        'Toyota' => array(
            'Hilux', 'Land Cruiser', 'Prado', 'Fortuner', 'Hiace', 'Corolla',
            'Camry', 'RAV4', 'Yaris', 'Rush', 'Avanza', 'Coaster', 'Other',
        ),
        'Nissan' => array(
            'Navara', 'Patrol', 'X-Trail', 'Tiida', 'Sunny', 'Dualis', 'Note',
            'Pathfinder', 'Urvan', 'Other',
        ),
        'Mitsubishi' => array(
            'Triton', 'Pajero', 'Lancer', 'Outlander', 'ASX', 'L200', 'Canter', 'Other',
        ),
        'Honda' => array(
            'Civic', 'CR-V', 'Accord', 'Jazz', 'City', 'HR-V', 'Freed', 'Other',
        ),
        'Hyundai' => array(
            'Tucson', 'Santa Fe', 'i30', 'Accent', 'Elantra', 'Starex', 'Other',
        ),
        'Kia' => array(
            'Sportage', 'Sorento', 'Rio', 'Cerato', 'Carnival', 'Other',
        ),
        'Ford' => array(
            'Ranger', 'Everest', 'Focus', 'Escape', 'Territory', 'Courier', 'Other',
        ),
        'Mazda' => array(
            'BT-50', 'CX-5', 'CX-3', 'Mazda3', 'Mazda6', 'CX-9', 'Other',
        ),
        'Isuzu' => array(
            'D-Max', 'MU-X', 'NPR', 'NQR', 'Other',
        ),
        'Subaru' => array(
            'Forester', 'Outback', 'Impreza', 'XV', 'Legacy', 'Other',
        ),
        'Suzuki' => array(
            'Jimny', 'Swift', 'Vitara', 'Alto', 'Carry', 'Other',
        ),
        'Holden' => array(
            'Colorado', 'Commodore', 'Captiva', 'Rodeo', 'Other',
        ),
        'Mercedes' => array(
            'A-Class', 'B-Class', 'C-Class', 'E-Class', 'GLS-Class', 'Sprinter', 'Other',
        ),
        'Volkswagen' => array(
            'Golf', 'Passat', 'Amarok', 'Polo', 'Tiguan', 'Transporter', 'Other',
        ),
        'BMW' => array(
            '1 Series', '3 Series', '5 Series', 'X3', 'X5', 'Other',
        ),
        'Land Rover' => array(
            'Defender', 'Discovery', 'Range Rover', 'Freelander', 'Other',
        ),
        'Hino' => array(
            '300 Series', '500 Series', 'Dutro', 'Other',
        ),
        'Fuso' => array(
            'Canter', 'Fighter', 'Other',
        ),
        'Seat' => array(
            'Alhambra', 'Leon', 'Altea', 'Ibiza', 'Other',
        ),
        'Opel' => array(
            'Astra', 'Insignia', 'Grand Tour', 'Corsa', 'Other',
        ),
        'Skoda' => array(
            'Fabia', 'Octavia', 'Superb', 'Kodiaq', 'Other',
        ),
        'Other' => array(),
    );
}


/**
 * @return mysqli|null
 */
function pngm_vehicle_db()
{
    if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_NAME')) {
        return null;
    }

    $m = @new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

    if (!$m || $m->connect_error) {
        return null;
    }

    $m->set_charset('utf8mb4');

    return $m;
}


/**
 * @param mysqli $m
 * @param string $prefix
 * @return int
 */
function pngm_vehicle_make_attr_id($m, $prefix)
{
    $r = $m->query(
        "SELECT pk_i_id FROM {$prefix}t_attribute
         WHERE s_identifier = 'make' AND s_type = 'SELECT'
         LIMIT 1"
    );

    if (!$r || $r->num_rows === 0) {
        return 0;
    }

    $row = $r->fetch_assoc();

    return (int) $row['pk_i_id'];
}


/**
 * Find or create an attribute value under a parent (NULL = root brand).
 *
 * @param mysqli      $m
 * @param string      $prefix
 * @param int         $attr_id
 * @param int|null    $parent_id
 * @param string      $name
 * @param int         $order
 * @param string      $locale
 *
 * @return int
 */
function pngm_vehicle_ensure_value($m, $prefix, $attr_id, $parent_id, $name, $order, $locale)
{
    $name = trim($name);

    if ($name === '') {
        return 0;
    }

    if ($parent_id === null) {
        $sql = "SELECT v.pk_i_id
                FROM {$prefix}t_attribute_value v
                INNER JOIN {$prefix}t_attribute_value_locale vl
                  ON vl.fk_i_attribute_value_id = v.pk_i_id AND vl.fk_c_locale_code = ?
                WHERE v.fk_i_attribute_id = ?
                  AND v.fk_i_parent_id IS NULL
                  AND vl.s_name = ?
                LIMIT 1";
        $stmt = $m->prepare($sql);
        $stmt->bind_param('sis', $locale, $attr_id, $name);
    } else {
        $sql = "SELECT v.pk_i_id
                FROM {$prefix}t_attribute_value v
                INNER JOIN {$prefix}t_attribute_value_locale vl
                  ON vl.fk_i_attribute_value_id = v.pk_i_id AND vl.fk_c_locale_code = ?
                WHERE v.fk_i_attribute_id = ?
                  AND v.fk_i_parent_id = ?
                  AND vl.s_name = ?
                LIMIT 1";
        $stmt = $m->prepare($sql);
        $stmt->bind_param('siis', $locale, $attr_id, $parent_id, $name);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if ($row && (int) $row['pk_i_id'] > 0) {
        return (int) $row['pk_i_id'];
    }

    if ($parent_id === null) {
        $ins = $m->prepare(
            "INSERT INTO {$prefix}t_attribute_value (fk_i_attribute_id, fk_i_parent_id, s_image, i_order)
             VALUES (?, NULL, '', ?)"
        );
        $ins->bind_param('ii', $attr_id, $order);
    } else {
        $ins = $m->prepare(
            "INSERT INTO {$prefix}t_attribute_value (fk_i_attribute_id, fk_i_parent_id, s_image, i_order)
             VALUES (?, ?, '', ?)"
        );
        $ins->bind_param('iii', $attr_id, $parent_id, $order);
    }

    if (!$ins->execute()) {
        $ins->close();
        return 0;
    }

    $value_id = (int) $m->insert_id;
    $ins->close();

    if ($value_id <= 0) {
        return 0;
    }

    $loc = $m->prepare(
        "INSERT INTO {$prefix}t_attribute_value_locale (fk_i_attribute_value_id, fk_c_locale_code, s_name)
         VALUES (?, ?, ?)"
    );
    $loc->bind_param('iss', $value_id, $locale, $name);
    $loc->execute();
    $loc->close();

    return $value_id;
}


/**
 * VEHICLE-03 — remove inconsistent third-level make values (trim/body noise).
 * Brand → model remains; Body/Fuel fields already cover style details.
 *
 * @param mysqli $m
 * @param string $prefix
 * @param int    $attr_id
 */
function pngm_vehicle_prune_third_level($m, $prefix, $attr_id)
{
    $sql = "SELECT v3.pk_i_id
            FROM {$prefix}t_attribute_value v3
            INNER JOIN {$prefix}t_attribute_value v2 ON v3.fk_i_parent_id = v2.pk_i_id
            INNER JOIN {$prefix}t_attribute_value v1 ON v2.fk_i_parent_id = v1.pk_i_id
            WHERE v3.fk_i_attribute_id = {$attr_id}
              AND v1.fk_i_parent_id IS NULL";

    $r = $m->query($sql);

    if (!$r || $r->num_rows === 0) {
        return;
    }

    $ids = array();

    while ($row = $r->fetch_assoc()) {
        $ids[] = (int) $row['pk_i_id'];
    }

    if (count($ids) === 0) {
        return;
    }

    $list = implode(',', $ids);
    $m->query("DELETE FROM {$prefix}t_attribute_value_locale WHERE fk_i_attribute_value_id IN ({$list})");
    $m->query("DELETE FROM {$prefix}t_attribute_value WHERE pk_i_id IN ({$list})");
}


/**
 * Ensure free-text companion field for Other make/model.
 *
 * @param mysqli $m
 * @param string $prefix
 * @param int    $make_attr_id
 * @param string $locale
 */
function pngm_vehicle_ensure_other_text_field($m, $prefix, $make_attr_id, $locale)
{
    $check = $m->query(
        "SELECT pk_i_id FROM {$prefix}t_attribute WHERE s_identifier = 'make_other' LIMIT 1"
    );

    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $id = (int) $row['pk_i_id'];
        $m->query(
            "UPDATE {$prefix}t_attribute
             SET s_category_id = '1', b_enabled = 1, b_required = 0, b_hook = 1, b_search = 0,
                 s_type = 'TEXT', fk_i_linked_to_attr_id = NULL
             WHERE pk_i_id = {$id}"
        );

        $loc_check = $m->prepare(
            "SELECT pk_i_id FROM {$prefix}t_attribute_locale
             WHERE fk_i_attribute_id = ? AND fk_c_locale_code = ? LIMIT 1"
        );
        $loc_check->bind_param('is', $id, $locale);
        $loc_check->execute();
        $existing = $loc_check->get_result()->fetch_assoc();
        $loc_check->close();

        $label = 'Specify make / model';

        if ($existing) {
            $upd = $m->prepare(
                "UPDATE {$prefix}t_attribute_locale SET s_name = ? WHERE pk_i_id = ?"
            );
            $pk = (int) $existing['pk_i_id'];
            $upd->bind_param('si', $label, $pk);
            $upd->execute();
            $upd->close();
        } else {
            $ins = $m->prepare(
                "INSERT INTO {$prefix}t_attribute_locale (fk_i_attribute_id, fk_c_locale_code, s_name)
                 VALUES (?, ?, ?)"
            );
            $ins->bind_param('iss', $id, $locale, $label);
            $ins->execute();
            $ins->close();
        }

        return;
    }

    // Place just after Car Make in the form.
    $order = 2;
    $ord = $m->query("SELECT i_order FROM {$prefix}t_attribute WHERE pk_i_id = {$make_attr_id} LIMIT 1");

    if ($ord && $ord->num_rows > 0) {
        $order = (int) $ord->fetch_assoc()['i_order'] + 1;
    }

    $ins = $m->prepare(
        "INSERT INTO {$prefix}t_attribute
         (s_identifier, b_enabled, b_required, b_search, b_hook, b_values_all, s_category_id, i_order,
          s_type, s_search_type, b_search_range, b_check_single, s_search_engine, s_search_values_all,
          s_icon, fk_i_linked_to_attr_id, s_restrict_type)
         VALUES
         ('make_other', 1, 0, 0, 1, 0, '1', ?,
          'TEXT', NULL, 0, 0, 'AND', 0,
          NULL, NULL, NULL)"
    );
    $ins->bind_param('i', $order);

    if (!$ins->execute()) {
        $ins->close();
        return;
    }

    $attr_id = (int) $m->insert_id;
    $ins->close();

    if ($attr_id <= 0) {
        return;
    }

    $label = 'Specify make / model';
    $loc = $m->prepare(
        "INSERT INTO {$prefix}t_attribute_locale (fk_i_attribute_id, fk_c_locale_code, s_name)
         VALUES (?, ?, ?)"
    );
    $loc->bind_param('iss', $attr_id, $locale, $label);
    $loc->execute();
    $loc->close();
}


/**
 * Main seed entry.
 */
function pngm_seed_vehicle_makes()
{
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    if (!defined('DB_TABLE_PREFIX')) {
        return;
    }

    // Skip AJAX noise; run on normal page loads (incl. item post).
    if (defined('OC_ADMIN') && OC_ADMIN) {
        // Still allow admin so seeds apply when logging into oc-admin.
    }

    try {
        $m = pngm_vehicle_db();

        if (!$m) {
            return;
        }

        $prefix = DB_TABLE_PREFIX;
        $attr_id = pngm_vehicle_make_attr_id($m, $prefix);

        if ($attr_id <= 0) {
            $m->close();
            return;
        }

        $locale = 'en_US';

        try {
            $loc_r = $m->query(
                "SELECT pk_c_code FROM {$prefix}t_locale
                 WHERE b_enabled = 1
                 ORDER BY b_enabled_bo DESC, pk_c_code ASC
                 LIMIT 1"
            );

            if ($loc_r && $loc_r->num_rows > 0) {
                $code = trim((string) $loc_r->fetch_assoc()['pk_c_code']);

                if ($code !== '') {
                    $locale = $code;
                }
            }
        } catch (Exception $e) {
            // Keep en_US fallback.
        }

        // Rename label for clarity (VEHICLE-01).
        $m->query(
            "UPDATE {$prefix}t_attribute_locale
             SET s_name = 'Make / Brand'
             WHERE fk_i_attribute_id = {$attr_id}
               AND s_name IN ('Car Make', 'Make', 'Make / Brand')"
        );

        // Keep make on Vehicles only.
        $m->query(
            "UPDATE {$prefix}t_attribute
             SET s_category_id = '1', b_required = 1, b_enabled = 1
             WHERE pk_i_id = {$attr_id}"
        );

        // VEHICLE-03 — drop incomplete third level first.
        pngm_vehicle_prune_third_level($m, $prefix, $attr_id);

        $catalog = pngm_vehicle_makes_catalog();
        $brand_order = 10;

        foreach ($catalog as $brand => $models) {
            $brand_id = pngm_vehicle_ensure_value(
                $m,
                $prefix,
                $attr_id,
                null,
                $brand,
                $brand_order,
                $locale
            );
            $brand_order += 10;

            if ($brand_id <= 0) {
                continue;
            }

            // Root "Other" has no forced model list — seller uses free text.
            if ($brand === 'Other' || !is_array($models) || count($models) === 0) {
                continue;
            }

            $model_order = 10;

            foreach ($models as $model) {
                pngm_vehicle_ensure_value(
                    $m,
                    $prefix,
                    $attr_id,
                    $brand_id,
                    $model,
                    $model_order,
                    $locale
                );
                $model_order += 10;
            }

            // Guarantee Other under every brand (VEHICLE-02).
            pngm_vehicle_ensure_value(
                $m,
                $prefix,
                $attr_id,
                $brand_id,
                'Other',
                9000,
                $locale
            );
        }

        // Root Other brand (VEHICLE-01).
        pngm_vehicle_ensure_value($m, $prefix, $attr_id, null, 'Other', 9000, $locale);

        pngm_vehicle_ensure_other_text_field($m, $prefix, $attr_id, $locale);

        // Clear Attributes plugin cache keys if present (best-effort).
        if (function_exists('osc_cache_flush')) {
            @osc_cache_flush();
        }

        $m->close();
    } catch (Throwable $e) {
        // Attributes plugin missing / DB unavailable.
    }
}

if (function_exists('osc_add_hook')) {
    osc_add_hook('init', 'pngm_seed_vehicle_makes', 9);
}
