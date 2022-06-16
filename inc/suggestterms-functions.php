<?php
/**
 * Fetch our TAXOPRESS SuggestTerms option.
 *
 * @return mixed
 */
function taxopress_get_suggestterm_data()
{
    return array_filter((array)apply_filters('taxopress_get_suggestterm_data', get_option('taxopress_suggestterms', []),
        get_current_blog_id()));
}

/**
 * Get the selected suggestterm from the $_POST global.
 *
 * @return bool|string False on no result, sanitized suggestterm if set.
 * @internal
 *
 */
function taxopress_get_current_suggestterm()
{

    $suggestterms = false;

    if (!empty($_GET) && isset($_GET['taxopress_suggestterms'])) {
        $suggestterms = sanitize_text_field($_GET['taxopress_suggestterms']);
    } else {
        $suggestterms = taxopress_get_suggestterm_data();
        if (!empty($suggestterms)) {
            // Will return the first array key.
            $suggestterms = key($suggestterms);
        }
    }

    /**
     * Filters the current suggestterm to edit.
     *
     * @param string $suggestterms suggestterm slug.
     */
    return apply_filters('taxopress_current_suggestterm', $suggestterms);
}

/**
 * Handle the save and deletion of suggestterm data.
 */
function taxopress_process_suggestterm()
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
    if ('st_suggestterms' !== $_GET['page']) {
        return;
    }

    if (isset($_GET['new_suggestterm'])) {
        if ((int)$_GET['new_suggestterm'] === 1) {
            add_action('admin_notices', "taxopress_suggestterms_update_success_admin_notice");
            add_filter('removable_query_args', 'taxopress_saved_suggestterm_filter_removable_query_args');
        }
    }

    if (isset($_GET['deleted_suggestterm'])) {
        if ((int)$_GET['deleted_suggestterm'] === 1) {
            add_action('admin_notices', "taxopress_suggestterms_delete_success_admin_notice");
            add_filter('removable_query_args', 'taxopress_deleted_suggestterm_filter_removable_query_args');
        }
    }


    if (!empty($_POST) && isset($_POST['suggestterm_submit'])) {
        $result = '';
        if (isset($_POST['suggestterm_submit'])) {
            check_admin_referer('taxopress_addedit_suggestterm_nonce_action',
                'taxopress_addedit_suggestterm_nonce_field');
            $result = taxopress_update_suggestterm($_POST);
        }

        if ($result) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page'                => 'st_suggestterms',
                        'add'                 => 'new_item',
                        'action'              => 'edit',
                        'taxopress_suggestterms' => $result,
                        'new_suggestterm'        => 1,
                    ],
                    taxopress_admin_url('admin.php')
                )
            );

            exit();
        }
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] === 'taxopress-delete-suggestterm') {
        $nonce = sanitize_text_field($_REQUEST['_wpnonce']);
        if (wp_verify_nonce($nonce, 'suggestterm-action-request-nonce')) {
            taxopress_action_delete_suggestterm(sanitize_text_field($_REQUEST['taxopress_suggestterms']));
        }
        add_filter('removable_query_args', 'taxopress_delete_suggestterm_filter_removable_query_args');
    }
}

add_action('admin_init', 'taxopress_process_suggestterm', 8);


/**
 * Create default suggestterm.
 */
function taxopress_create_default_suggestterm()
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

    if ((int)get_option('taxopress_default_suggestterms') > 0) {
       return;
    }

    if (count(taxopress_get_suggestterm_data()) > 0) {
        return;
    }


    $default                                                            = [];
    $default['taxopress_suggestterm']['title']                          = 'Suggest Term';
    $default['taxopress_suggestterm']['taxonomy']                       = 'post_tag';
    $default['post_types']                                              = ['post'];
    $default['taxopress_suggestterm']['number']                         = '100';
    $default['taxopress_suggestterm']['orderby']                        = 'count';
    $default['taxopress_suggestterm']['order']                          = 'desc';
    $default['taxopress_suggestterm']['enable_existing_terms']          = '1';
    $default['taxopress_suggestterm']['suggest_term_use_local']         = '1';
    $default['taxopress_suggestterm']['suggest_term_use_dandelion']     = '0';
    $default['taxopress_suggestterm']['suggest_term_use_opencalais']    = '0';
    $default['taxopress_suggestterm']['terms_opencalais_key']           = SimpleTags_Plugin::get_option_value( 'opencalais_key' );
    $default['taxopress_suggestterm']['terms_datatxt_access_token']     = SimpleTags_Plugin::get_option_value( 'datatxt_access_token' );
    $default['taxopress_suggestterm']['terms_datatxt_min_confidence']    = SimpleTags_Plugin::get_option_value( 'datatxt_min_confidence' );
    $result                                                             = taxopress_update_suggestterm($default);

    update_option('taxopress_default_suggestterms', $result);
}

add_action('admin_init', 'taxopress_create_default_suggestterm', 8);


/**
 * Add to or update our TAXOPRESS option with new data.
 *
 *
 * @param array $data Array of suggestterm data to update. Optional.
 * @return bool|string False on failure, string on success.
 * @internal
 *
 */
function taxopress_update_suggestterm($data = [])
{
    foreach ($data as $key => $value) {

        if (is_string($value)) {
            $data[$key] = sanitize_text_field($value);
        } else {
            array_map('sanitize_text_field', $data[$key]);
        }
    }

    $suggestterms = taxopress_get_suggestterm_data();

    $title                               = $data['taxopress_suggestterm']['title'];
    $title                               = str_replace('"', '', htmlspecialchars_decode($title));
    $title                               = htmlspecialchars($title, ENT_QUOTES);
    $title                               = trim($title);
    $data['taxopress_suggestterm']['title'] = stripslashes_deep($title);


    //update other post post
    $data['taxopress_suggestterm']['post_types']          = isset($data['post_types']) ? $data['post_types'] : [];

    //update our custom checkbox value if not checked
    if (!isset($data['taxopress_suggestterm']['enable_existing_terms'])) {
        $data['taxopress_suggestterm']['enable_existing_terms'] = 0;
    }
    if (!isset($data['taxopress_suggestterm']['suggest_term_use_local'])) {
        $data['taxopress_suggestterm']['suggest_term_use_local'] = 0;
    }
    if (!isset($data['taxopress_suggestterm']['suggest_term_use_dandelion'])) {
        $data['taxopress_suggestterm']['suggest_term_use_dandelion'] = 0;
    }
    if (!isset($data['taxopress_suggestterm']['suggest_term_use_opencalais'])) {
        $data['taxopress_suggestterm']['suggest_term_use_opencalais'] = 0;
    }
    
    if (isset($data['edited_suggestterm'])) {
        $suggestterm_id             = $data['edited_suggestterm'];
        $suggestterms[$suggestterm_id] = $data['taxopress_suggestterm'];
        $success                 = update_option('taxopress_suggestterms', $suggestterms);
        //return 'update_success';
    } else {
        $suggestterm_id                      = (int)get_option('taxopress_suggestterm_ids_increament') + 1;
        $data['taxopress_suggestterm']['ID'] = $suggestterm_id;
        $suggestterms[$suggestterm_id]          = $data['taxopress_suggestterm'];
        $success                          = update_option('taxopress_suggestterms', $suggestterms);
        $update_id                        = update_option('taxopress_suggestterm_ids_increament', $suggestterm_id);
        //return 'add_success';
    }

    return $suggestterm_id;
}

/**
 * Successful update callback.
 */
function taxopress_suggestterms_update_success_admin_notice()
{
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo taxopress_admin_notices_helper(esc_html__('Settings updated successfully.', 'simple-tags'));
}

/**
 * Successful deleted callback.
 */
function taxopress_suggestterms_delete_success_admin_notice()
{
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo taxopress_admin_notices_helper(esc_html__('Suggest Terms successfully deleted.', 'simple-tags'), false);
}

/**
 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
 *
 * @link https://core.trac.wordpress.org/ticket/23367
 *
 * @param string[] $args Array of removable query arguments.
 * @return string[] Updated array of removable query arguments.
 */
function taxopress_saved_suggestterm_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'new_suggestterm',
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
function taxopress_deleted_suggestterm_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'deleted_suggestterm',
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
function taxopress_delete_suggestterm_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'action',
        'taxopress_suggestterms',
        '_wpnonce',
    ]);
}

/**
 * Delete our custom suggestterm from the array of suggestterms.
 * @return bool|string False on failure, string on success.
 */
function taxopress_action_delete_suggestterm($suggestterm_id)
{
    $suggestterms = taxopress_get_suggestterm_data();

    if (array_key_exists($suggestterm_id, $suggestterms)) {
        unset($suggestterms[$suggestterm_id]);
        $success = update_option('taxopress_suggestterms', $suggestterms);
    }

    if (isset($success)) {
        add_action('admin_notices', "taxopress_taxdeleted_admin_notice");
        wp_safe_redirect(
            add_query_arg(
                [
                    'page'             => 'st_suggestterms',
                    'deleted_suggestterm' => 1,
                ],
                taxopress_admin_url('admin.php')
            )
        );
        exit();
    }
}

/**
 * Get click tags for current post
 *
 *
 * @return mixed
 */
function taxopress_current_post_suggest_terms($source = false, $local_check = false, $single_result = true)
{
    global $pagenow;

    $allowed_pages = ['post-new.php', 'post.php', 'page.php', 'page-new.php'];
    if(!in_array($pagenow, $allowed_pages)){
        return false;
    }

    $suggested_terms = taxopress_get_suggestterm_data();
    $all_result = [];
    if (count($suggested_terms) > 0) {
        foreach ($suggested_terms as $suggested_term) {

            // Get option
            $post_types = (isset($suggested_term['post_types']) && is_array($suggested_term['post_types']) && count($suggested_term['post_types']) > 0) ? $suggested_term['post_types'] : false;

            if (!$post_types) {
                continue;
            }

            $enable_existing_terms      = isset($suggested_term['enable_existing_terms']) ? (int)$suggested_term['enable_existing_terms'] : 0;
            $suggest_term_use_local      = isset($suggested_term['suggest_term_use_local']) ? (int)$suggested_term['suggest_term_use_local'] : 0;
            $suggest_term_use_dandelion  = isset($suggested_term['suggest_term_use_dandelion']) ? (int)$suggested_term['suggest_term_use_dandelion'] : 0;
            $suggest_term_use_opencalais = isset($suggested_term['suggest_term_use_opencalais']) ? (int)$suggested_term['suggest_term_use_opencalais'] : 0;

            if($source && $source !== 'existing_terms' && $suggest_term_use_local === 0 && $suggest_term_use_dandelion === 0 && $suggest_term_use_opencalais === 0){
                continue;
            }

            if($source && $source === 'existing_terms' && $enable_existing_terms === 0){
                continue;
            }

            if (in_array(get_post_type(), $post_types)) {
                if ($single_result) {
                    return $suggested_term;
                } else {
                    $all_result[] = $suggested_term;
                }
            }

        }
    }

    return empty($all_result) ? false : $all_result;
}
?>