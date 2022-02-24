<?php
/**
 * Fetch our TAXOPRESS Autolinks option.
 *
 * @return mixed
 */
function taxopress_get_autolink_data()
{
    return array_filter((array)apply_filters('taxopress_get_autolink_data', get_option('taxopress_autolinks', []),
        get_current_blog_id()));
}

/**
 * Get the selected autolink from the $_POST global.
 *
 * @return bool|string False on no result, sanitized autolink if set.
 * @internal
 *
 */
function taxopress_get_current_autolink()
{

    $autolinks = false;

    if (!empty($_GET) && isset($_GET['taxopress_autolinks'])) {
        $autolinks = sanitize_text_field($_GET['taxopress_autolinks']);
    } else {
        $autolinks = taxopress_get_autolink_data();
        if (!empty($autolinks)) {
            // Will return the first array key.
            $autolinks = key($autolinks);
        }
    }

    /**
     * Filters the current autolink to edit.
     *
     * @param string $autolinks autolink slug.
     */
    return apply_filters('taxopress_current_autolink', $autolinks);
}

/**
 * Handle the save and deletion of autolink data.
 */
function taxopress_process_autolink()
{

    if (wp_doing_ajax()) {
        return;
    }

    if (!is_admin()) {
        return;
    }

    if(!current_user_can('simple_tags')){
        return;
    }

    if (empty($_GET)) {
        return;
    }

    if (!isset($_GET['page'])) {
        return;
    }
    if ('st_autolinks' !== $_GET['page']) {
        return;
    }

    if (isset($_GET['new_autolink'])) {
        if ((int)$_GET['new_autolink'] === 1) {
            add_action('admin_notices', "taxopress_autolinks_update_success_admin_notice");
            add_filter('removable_query_args', 'taxopress_saved_autolink_filter_removable_query_args');
        }
    }

    if (isset($_GET['deleted_autolink'])) {
        if ((int)$_GET['deleted_autolink'] === 1) {
            add_action('admin_notices', "taxopress_autolinks_delete_success_admin_notice");
            add_filter('removable_query_args', 'taxopress_deleted_autolink_filter_removable_query_args');
        }
    }


    if (!empty($_POST) && isset($_POST['autolink_submit'])) {
        $result = '';
        if (isset($_POST['autolink_submit'])) {
            check_admin_referer('taxopress_addedit_autolink_nonce_action',
                'taxopress_addedit_autolink_nonce_field');
            $result = taxopress_update_autolink($_POST);
        }

        if ($result) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page'                => 'st_autolinks',
                        'add'                 => 'new_item',
                        'action'              => 'edit',
                        'taxopress_autolinks' => $result,
                        'new_autolink'        => 1,
                    ],
                    taxopress_admin_url('admin.php')
                )
            );

            exit();
        }
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] === 'taxopress-delete-autolink') {
        $nonce = sanitize_text_field($_REQUEST['_wpnonce']);
        if (wp_verify_nonce($nonce, 'autolink-action-request-nonce')) {
            taxopress_action_delete_autolink(sanitize_text_field($_REQUEST['taxopress_autolinks']));
        }
        add_filter('removable_query_args', 'taxopress_delete_autolink_filter_removable_query_args');
    }
}

add_action('admin_init', 'taxopress_process_autolink', 8);


/**
 * Create default autolink.
 */
function taxopress_create_default_autolink()
{

    if (wp_doing_ajax()) {
        return;
    }

    if (!is_admin()) {
        return;
    }

    if(!current_user_can('simple_tags')){
        return;
    }

    if ((int)get_option('taxopress_default_autolinks') > 0) {
        return;
    }

    if (count(taxopress_get_autolink_data()) > 0) {
        return;
    }

    $default                                                   = [];
    $default['taxopress_autolink']['title']                    = 'Auto link';
    $default['taxopress_autolink']['taxonomy']                 = 'post_tag';
    $default['taxopress_autolink']['autolink_case']            = 'none';
    $default['taxopress_autolink']['autolink_display']         = 'post_content';
    $default['taxopress_autolink']['autolink_title_attribute'] = __('Posts tagged with %s', 'simple-tags');
    $default['taxopress_autolink']['autolink_usage_min']       = '1';
    $default['taxopress_autolink']['auto_link_exclude']        = '';
    $default['taxopress_autolink']['autolink_usage_max']       = '10';
    $default['taxopress_autolink']['autolink_same_usage_max']  = '1';
    $default['taxopress_autolink']['autolink_min_char']        = '';
    $default['taxopress_autolink']['autolink_max_char']        = '';
    $default['taxopress_autolink']['autolink_exclude_class']   = '';
    $default['taxopress_autolink']['hook_priority']            = '12';
    $default['taxopress_autolink']['embedded']                 = [];
    $default['taxopress_autolink']['html_exclusion']           = [];
    $default['taxopress_autolink']['unattached_terms']         = '0';
    $default['taxopress_autolink']['ignore_case']              = '1';
    $default['taxopress_autolink']['ignore_attached']          = '0';
    $default['taxopress_autolink']['autolink_dom']             = '1';
    $default['autolink_submit']                                = 'Add Auto Links';
    $default['cpt_tax_status']                                 = 'new';
    $result                                                    = taxopress_update_autolink($default);
    update_option('taxopress_default_autolinks', $result);
}

add_action('admin_init', 'taxopress_create_default_autolink', 8);


/**
 * Add to or update our TAXOPRESS option with new data.
 *
 *
 * @param array $data Array of autolink data to update. Optional.
 * @return bool|string False on failure, string on success.
 * @internal
 *
 */
function taxopress_update_autolink($data = [])
{
    foreach ($data as $key => $value) {

        if (is_string($value)) {
            $data[$key] = sanitize_text_field($value);
        } else {
            array_map('sanitize_text_field', $data[$key]);
        }
    }

    $autolinks = taxopress_get_autolink_data();

    $title                               = $data['taxopress_autolink']['title'];
    $title                               = str_replace('"', '', htmlspecialchars_decode($title));
    $title                               = htmlspecialchars($title, ENT_QUOTES);
    $title                               = trim($title);
    $data['taxopress_autolink']['title'] = stripslashes_deep($title);

    //update seperate post
    $data['taxopress_autolink']['embedded']       = isset($data['embedded']) ? $data['embedded'] : [];
    $data['taxopress_autolink']['html_exclusion'] = isset($data['html_exclusion']) ? $data['html_exclusion'] : [];

    //update our custom checkbox value if not checked
    if (!isset($data['taxopress_autolink']['unattached_terms'])) {
        $data['taxopress_autolink']['unattached_terms'] = 0;
    }
    if (!isset($data['taxopress_autolink']['ignore_case'])) {//auto set ignore case to true
        $data['taxopress_autolink']['ignore_case'] = 1;
    }
    if (!isset($data['taxopress_autolink']['ignore_attached'])) {
        $data['taxopress_autolink']['ignore_attached'] = 0;
    }
    if (!isset($data['taxopress_autolink']['autolink_dom'])) {
        $data['taxopress_autolink']['autolink_dom'] = 0;
    }


    if (isset($data['edited_autolink'])) {
        $autolink_id             = $data['edited_autolink'];
        $autolinks[$autolink_id] = $data['taxopress_autolink'];
        $success                 = update_option('taxopress_autolinks', $autolinks);
    } else {
        $autolink_id                      = (int)get_option('taxopress_autolink_ids_increament') + 1;
        $data['taxopress_autolink']['ID'] = $autolink_id;
        $autolinks[$autolink_id]          = $data['taxopress_autolink'];
        $success                          = update_option('taxopress_autolinks', $autolinks);
        $update_id                        = update_option('taxopress_autolink_ids_increament', $autolink_id);
    }

    return $autolink_id;

}

/**
 * Successful update callback.
 */
function taxopress_autolinks_update_success_admin_notice()
{
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo taxopress_admin_notices_helper(esc_html__('Settings updated successfully.', 'simple-tags'));
}

/**
 * Successful deleted callback.
 */
function taxopress_autolinks_delete_success_admin_notice()
{
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo taxopress_admin_notices_helper(esc_html__('Auto Links successfully deleted.', 'simple-tags'), false);
}

/**
 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
 *
 * @link https://core.trac.wordpress.org/ticket/23367
 *
 * @param string[] $args Array of removable query arguments.
 * @return string[] Updated array of removable query arguments.
 */
function taxopress_saved_autolink_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'new_autolink',
    ]);
}

/**
 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
 *
 * @link https://core.trac.wordpress.org/ticket/23367
 *
 * @param string[] $args Array of removable query arguments.
 * @return string[] Updated array of removable query arguments.
 */
function taxopress_deleted_autolink_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'deleted_autolink',
    ]);
}

/**
 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
 *
 * @link https://core.trac.wordpress.org/ticket/23367
 *
 * @param string[] $args Array of removable query arguments.
 * @return string[] Updated array of removable query arguments.
 */
function taxopress_delete_autolink_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'action',
        'taxopress_autolinks',
        '_wpnonce',
    ]);
}

/**
 * Delete our custom autolink from the array of autolinks.
 * @return bool|string False on failure, string on success.
 */
function taxopress_action_delete_autolink($autolink_id)
{
    $autolinks = taxopress_get_autolink_data();

    if (array_key_exists($autolink_id, $autolinks)) {
        unset($autolinks[$autolink_id]);
        $success = update_option('taxopress_autolinks', $autolinks);
    }

    if (isset($success)) {
        add_action('admin_notices', "taxopress_taxdeleted_admin_notice");
        wp_safe_redirect(
            add_query_arg(
                [
                    'page'             => 'st_autolinks',
                    'deleted_autolink' => 1,
                ],
                taxopress_admin_url('admin.php')
            )
        );
        exit();
    }
}

/**
 * Get auto link for current post
 *
 *
 * @return mixed
 */
function taxopress_post_type_autolink_autolink()
{
    global $pagenow;

    $allowed_pages = ['post-new.php', 'post.php', 'page.php', 'page-new.php'];
    if(!in_array($pagenow, $allowed_pages)){
        return false;
    }

    $autolinks = taxopress_get_autolink_data();

    if (count($autolinks) > 0) {
        foreach ($autolinks as $autolink) {

            // Get option
            $post_types = (isset($autolink['embedded']) && is_array($autolink['embedded']) && count($autolink['embedded']) > 0) ? $autolink['embedded'] : false;

            if (!$post_types) {
                continue;
            }

            if (in_array(get_post_type(), $post_types)) {
                return $autolink;
            }

        }
    }

    return false;
}