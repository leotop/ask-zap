<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

use Tygh\Registry;
use Tygh\Enum\ProductFeatures;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_define('KEEP_UPLOADED_FILES', true);
fn_define('NEW_FEATURE_GROUP_ID', 'OG');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    fn_trusted_vars ('feature_data');

    // Update features
    if ($mode == 'update') {
        $feature_id = fn_update_product_feature($_REQUEST['feature_data'], $_REQUEST['feature_id'], DESCR_SL);

        if ($_REQUEST['feature_data']['feature_type'] == ProductFeatures::EXTENDED) {
            return array(CONTROLLER_STATUS_OK, 'product_features.update?feature_id=' . $feature_id);
        }
    }

    if ($mode == 'update_status') {

        fn_tools_update_status($_REQUEST);

        if (!empty($_REQUEST['status']) && $_REQUEST['status'] == 'D') {
            $filter_ids = db_get_fields("SELECT filter_id FROM ?:product_filters WHERE feature_id = ?i AND status = 'A'", $_REQUEST['id']);
            if (!empty($filter_ids)) {
                db_query("UPDATE ?:product_filters SET status = 'D' WHERE filter_id IN (?n)", $filter_ids);
                $filter_names_array = db_get_fields("SELECT filter FROM ?:product_filter_descriptions WHERE filter_id IN (?n) AND lang_code = ?s", $filter_ids, DESCR_SL);

                fn_set_notification('W', __('warning'), __('text_product_filters_were_disabled', array(
                    '[url]' => fn_url('product_filters.manage'),
                    '[filters_list]' => implode(', ', $filter_names_array)
                )));
            }
        }

        exit;
    }

    if ($mode == 'delete') {

        if (!empty($_REQUEST['feature_id'])) {
            fn_delete_feature($_REQUEST['feature_id']);
        }
    }

    return array(CONTROLLER_STATUS_OK, 'product_features.manage');
}

if ($mode == 'update') {

    $selected_section = (empty($_REQUEST['selected_section']) ? 'detailed' : $_REQUEST['selected_section']);

    $feature = fn_get_product_feature_data($_REQUEST['feature_id'], false, false, DESCR_SL);
    Tygh::$app['view']->assign('feature', $feature);
    list($group_features) = fn_get_product_features(array('feature_types' => ProductFeatures::GROUP), 0, DESCR_SL);
    Tygh::$app['view']->assign('group_features', $group_features);

    if (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.company_id')) {
        Tygh::$app['view']->assign('picker_selected_companies', fn_ult_get_controller_shared_companies($_REQUEST['feature_id']));
    }

    $params = array(
        'feature_id' => $feature['feature_id'],
        'feature_type' => $feature['feature_type'],
        'get_images' => true,
        'page' => !empty($_REQUEST['page']) ? $_REQUEST['page'] : 1,
        'items_per_page' => !empty($_REQUEST['items_per_page']) ? $_REQUEST['items_per_page'] : Registry::get('settings.Appearance.admin_elements_per_page'),
    );

    list($variants, $search) = fn_get_product_feature_variants($params, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);

    Tygh::$app['view']->assign('feature_variants', $variants);
    Tygh::$app['view']->assign('search', $search);

} elseif ($mode == 'manage') {

    $params = $_REQUEST;
    $params['get_descriptions'] = true;
    $params['search_in_subcats'] = true;
    list($features, $search, $has_ungroupped) = fn_get_product_features($params, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);

    Tygh::$app['view']->assign('features', $features);
    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('has_ungroupped', $has_ungroupped);

    if (empty($features) && defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('force_redirection', fn_url('product_features.manage'));
    }

    list($group_features) = fn_get_product_features(array('feature_types' => ProductFeatures::GROUP), 0, DESCR_SL);
    Tygh::$app['view']->assign('group_features', $group_features);

} elseif ($mode == 'get_feature_variants_list') {
    if (empty($_REQUEST['feature_id'])) {
        exit;
    }

    $pattern = !empty($_REQUEST['pattern']) ? $_REQUEST['pattern'] : '';
    $start = !empty($_REQUEST['start']) ? $_REQUEST['start'] : 0;
    $limit = (!empty($_REQUEST['limit']) ? $_REQUEST['limit'] : 10) + 1;
    $sorting = db_quote("?:product_feature_variants.position, ?:product_feature_variant_descriptions.variant");

    $join = db_quote(" LEFT JOIN ?:product_feature_variant_descriptions ON ?:product_feature_variant_descriptions.variant_id = ?:product_feature_variants.variant_id AND ?:product_feature_variant_descriptions.lang_code = ?s", DESCR_SL);
    $condition = db_quote(" AND ?:product_feature_variants.feature_id = ?i", $_REQUEST['feature_id']);

    fn_set_hook('get_feature_variants_list', $condition, $join, $pattern, $start, $limit);

    $objects = db_get_hash_array("SELECT SQL_CALC_FOUND_ROWS ?:product_feature_variants.variant_id AS value, ?:product_feature_variant_descriptions.variant AS name FROM ?:product_feature_variants $join WHERE 1 $condition AND ?:product_feature_variant_descriptions.variant LIKE ?l ORDER BY ?p LIMIT ?i, ?i", 'value', '%' . $pattern . '%', $sorting, $start, $limit);

    if (defined('AJAX_REQUEST') && sizeof($objects) < $limit) {
        Tygh::$app['ajax']->assign('completed', true);
    } else {
        array_pop($objects);
    }

    if (empty($_REQUEST['enter_other']) || $_REQUEST['enter_other'] != 'N') {
        $total = db_get_found_rows();
        if (!Registry::get('runtime.company_id') || (fn_allowed_for('ULTIMATE') && fn_check_company_id('product_features', 'feature_id', $_REQUEST['feature_id']))) {
            if ($start + $limit >= $total + 1) {
                $objects[] = array('value' => 'disable_select', 'name' => '-' . __('enter_other') . '-');
            }
        }
    }

    if (!$start) {
        array_unshift($objects, array('value' => '', 'name' => '-' . __('none') . '-'));
    }

    Tygh::$app['view']->assign('objects', $objects);

    Tygh::$app['view']->assign('id', $_REQUEST['result_ids']);
    Tygh::$app['view']->display('common/ajax_select_object.tpl');

    exit;
}
