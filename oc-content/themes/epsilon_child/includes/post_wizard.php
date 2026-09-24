<?php
/**
 * PNG Market — 6-step post listing wizard helpers.
 */

if (isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'post_wizard.php'
) {
    exit;
}

/**
 * Wizard step definitions (label + short key).
 *
 * @return array
 */
function pngm_post_wizard_steps()
{
    return array(
        1 => array(
            'key'   => 'category',
            'label' => __('Category', 'epsilon'),
            'title' => __('1. Category / Subcategory', 'epsilon'),
            'sub'   => __('Choose what you are listing.', 'epsilon'),
        ),
        2 => array(
            'key'   => 'details',
            'label' => __('Details', 'epsilon'),
            'title' => __('2. Item Details', 'epsilon'),
            'sub'   => __('Tell buyers what you are offering.', 'epsilon'),
        ),
        3 => array(
            'key'   => 'photos',
            'label' => __('Photos', 'epsilon'),
            'title' => __('3. Add Photos', 'epsilon'),
            'sub'   => __('Photos are optional. Good photos help your listing sell faster.', 'epsilon'),
        ),
        4 => array(
            'key'   => 'location',
            'label' => __('Location', 'epsilon'),
            'title' => __('4. Location', 'epsilon'),
            'sub'   => __('Where is your item located?', 'epsilon'),
        ),
        5 => array(
            'key'   => 'contact',
            'label' => __('Contact', 'epsilon'),
            'title' => __('5. Contact', 'epsilon'),
            'sub'   => __('How buyers can reach you.', 'epsilon'),
        ),
        6 => array(
            'key'   => 'review',
            'label' => __('Review & Publish', 'epsilon'),
            'title' => __('6. Review & Publish', 'epsilon'),
            'sub'   => __('Preview and publish your listing.', 'epsilon'),
        ),
    );
}

/**
 * Root categories for step-1 cards.
 *
 * @return array
 */
function pngm_post_root_categories()
{
    if (!class_exists('Category')) {
        return array();
    }
    $rows = Category::newInstance()->findRootCategoriesEnabled();
    return is_array($rows) ? $rows : array();
}

/**
 * Subcategories for a root category.
 *
 * @param int $parent_id
 * @return array
 */
function pngm_post_subcategories($parent_id)
{
    $parent_id = (int) $parent_id;
    if ($parent_id <= 0 || !class_exists('Category')) {
        return array();
    }
    $rows = Category::newInstance()->findSubcategoriesEnabled($parent_id);
    return is_array($rows) ? $rows : array();
}

/**
 * Resolve root + leaf category ids from a selected category id.
 *
 * @param int $cat_id
 * @return array{root:int,leaf:int,root_name:string,leaf_name:string}
 */
function pngm_post_category_path($cat_id)
{
    $out = array(
        'root'      => 0,
        'leaf'      => 0,
        'root_name' => '',
        'leaf_name' => '',
    );
    $cat_id = (int) $cat_id;
    if ($cat_id <= 0 || !class_exists('Category')) {
        return $out;
    }

    $row = Category::newInstance()->findByPrimaryKey($cat_id);
    if (!is_array($row)) {
        return $out;
    }

    $out['leaf'] = $cat_id;
    $out['leaf_name'] = isset($row['s_name']) ? (string) $row['s_name'] : '';

    if (empty($row['fk_i_parent_id'])) {
        $out['root'] = $cat_id;
        $out['root_name'] = $out['leaf_name'];
        return $out;
    }

    $parent = Category::newInstance()->findByPrimaryKey((int) $row['fk_i_parent_id']);
    if (is_array($parent)) {
        $out['root'] = (int) $parent['pk_i_id'];
        $out['root_name'] = isset($parent['s_name']) ? (string) $parent['s_name'] : '';
    }

    return $out;
}

/**
 * Transaction pill options matching mockup labels → theme option ids.
 *
 * @return array id => label
 */
function pngm_post_transaction_pills()
{
    return array(
        1 => __('For Sale', 'epsilon'),
        2 => __('Wanted', 'epsilon'),
        3 => __('For Rent', 'epsilon'),
        0 => __('Free', 'epsilon'), // special: sets price free
    );
}

/**
 * Render horizontal stepper.
 *
 * @param int $current
 */
function pngm_post_render_stepper($current = 1)
{
    $steps = pngm_post_wizard_steps();
    $current = (int) $current;
    echo '<nav class="pngm-post-stepper" aria-label="' . osc_esc_html(__('Listing steps', 'epsilon')) . '">';
    echo '<ol class="pngm-post-stepper-list">';
    foreach ($steps as $num => $step) {
        $cls = 'pngm-post-step';
        if ($num < $current) {
            $cls .= ' is-done';
        } elseif ($num === $current) {
            $cls .= ' is-active';
        }
        echo '<li class="' . $cls . '" data-step="' . (int) $num . '">';
        echo '<span class="pngm-post-step-dot" aria-hidden="true">';
        if ($num < $current) {
            echo '<i class="fas fa-check"></i>';
        } else {
            echo (int) $num;
        }
        echo '</span>';
        echo '<span class="pngm-post-step-label">' . osc_esc_html($step['label']) . '</span>';
        echo '</li>';
        if ($num < count($steps)) {
            echo '<li class="pngm-post-step-line' . ($num < $current ? ' is-done' : '') . '" aria-hidden="true"></li>';
        }
    }
    echo '</ol></nav>';
}

/**
 * Sidebar tip card.
 *
 * @param string $title
 * @param array  $bullets
 */
function pngm_post_tip_card($title, $bullets)
{
    echo '<aside class="pngm-post-card pngm-post-tips">';
    echo '<h3>' . osc_esc_html($title) . '</h3>';
    echo '<ul class="pngm-post-tips-list">';
    foreach ($bullets as $b) {
        echo '<li><i class="fas fa-check" aria-hidden="true"></i><span>' . osc_esc_html($b) . '</span></li>';
    }
    echo '</ul></aside>';
}
