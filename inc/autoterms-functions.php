<?php
/**
 * Fetch our TAXOPRESS Autoterms option.
 *
 * @return mixed
 */
function taxopress_get_autoterm_data()
{
    return array_filter((array)apply_filters('taxopress_get_autoterm_data', get_option('taxopress_autoterms', []),
        get_current_blog_id()));
}

/**
 * Get the selected autoterm from the $_POST global.
 *
 * @return bool|string False on no result, sanitized autoterm if set.
 * @internal
 *
 */
function taxopress_get_current_autoterm()
{

    $autoterms = false;

    if (!empty($_GET) && isset($_GET['taxopress_autoterms'])) {
        $autoterms = sanitize_text_field($_GET['taxopress_autoterms']);
    } else {
        $autoterms = taxopress_get_autoterm_data();
        if (!empty($autoterms)) {
            // Will return the first array key.
            $autoterms = key($autoterms);
        }
    }

    /**
     * Filters the current autoterm to edit.
     *
     * @param string $autoterms autoterm slug.
     */
    return apply_filters('taxopress_current_autoterm', $autoterms);
}

/**
 * Handle the save and deletion of autoterm data.
 */
function taxopress_process_autoterm()
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
    if ('st_autoterms' !== $_GET['page']) {
        return;
    }

    if (isset($_GET['new_autoterm'])) {
        if ((int)$_GET['new_autoterm'] === 1) {
            add_action('admin_notices', "taxopress_autoterms_update_success_admin_notice");
            add_filter('removable_query_args', 'taxopress_saved_autoterm_filter_removable_query_args');
        }
    }

    if (isset($_GET['deleted_autoterm'])) {
        if ((int)$_GET['deleted_autoterm'] === 1) {
            add_action('admin_notices', "taxopress_autoterms_delete_success_admin_notice");
            add_filter('removable_query_args', 'taxopress_deleted_autoterm_filter_removable_query_args');
        }
    }


    if (!empty($_POST) && isset($_POST['autoterm_submit'])) {
        $result = '';
        if (isset($_POST['autoterm_submit'])) {
            check_admin_referer('taxopress_addedit_autoterm_nonce_action',
                'taxopress_addedit_autoterm_nonce_field');
            $result = taxopress_update_autoterm($_POST);
        }

        if ($result) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page'                => 'st_autoterms',
                        'add'                 => 'new_item',
                        'action'              => 'edit',
                        'taxopress_autoterms' => $result,
                        'new_autoterm'        => 1,
                    ],
                    taxopress_admin_url('admin.php')
                )
            );

            exit();
        }
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] === 'taxopress-delete-autoterm') {
        $nonce = sanitize_text_field($_REQUEST['_wpnonce']);
        if (wp_verify_nonce($nonce, 'autoterm-action-request-nonce')) {
            taxopress_action_delete_autoterm(sanitize_text_field($_REQUEST['taxopress_autoterms']));
        }
        add_filter('removable_query_args', 'taxopress_delete_autoterm_filter_removable_query_args');
    }
}

add_action('admin_init', 'taxopress_process_autoterm', 8);


/**
 * Create default autoterm.
 */
function taxopress_create_default_autoterm()
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

    if ((int)get_option('taxopress_default_autoterms') > 0) {
       return;
    }

    if (count(taxopress_get_autoterm_data()) > 0) {
        return;
    }

    //create default or export previous
    $create_default = true;

    $options = get_option( STAGS_OPTIONS_NAME_AUTO );
    if(is_array($options) && count($options) > 0 ){
        foreach($options as $options_post_type => $options_taxdata){
            if(is_array($options_taxdata) && count($options_taxdata) > 0){
                $create_default = false;
                foreach($options_taxdata as $options_taxonomy => $options_taxonomy_data){
                    $taxonomy = get_taxonomy($options_taxonomy);
                    $default                                                   = [];
                    $default['taxopress_autoterm']['autoterm_from']            = 'posts';
                    $default['taxopress_autoterm']['title']                    = ''. (is_object($taxonomy) ? $taxonomy->labels->name : $options_taxonomy ) .' '. ucwords($options_post_type) .' Auto term';
                    $default['taxopress_autoterm']['taxonomy']                 = $options_taxonomy;
                    $default['post_types']                                     = [$options_post_type];
                    $default['post_status']                                    = ['publish'];
                    $default['taxopress_autoterm']['autoterm_useall']          = isset($options_taxonomy_data['at_all']) ? $options_taxonomy_data['at_all'] : 0;
                    $default['taxopress_autoterm']['autoterm_useonly']         = isset($options_taxonomy_data['at_all_no']) ? $options_taxonomy_data['at_all_no'] : 0;
                    $default['taxopress_autoterm']['autoterm_target']          = isset($options_taxonomy_data['at_empty']) ? $options_taxonomy_data['at_empty'] : 0;
                    $default['taxopress_autoterm']['autoterm_word']            = isset($options_taxonomy_data['only_full_word']) ? $options_taxonomy_data['only_full_word'] : 0;
                    $default['taxopress_autoterm']['autoterm_hash']            = isset($options_taxonomy_data['allow_hashtag_format']) ? $options_taxonomy_data['allow_hashtag_format'] : 0;
                    $default['specific_terms']            = isset($options_taxonomy_data['auto_list']) ? (array) maybe_unserialize($options_taxonomy_data['auto_list']) : [];
                    $default['taxopress_autoterm']['terms_limit']              = '0';

                    $result                                                    = taxopress_update_autoterm($default);
                }
            }
        }
    }

    if($create_default){
        $default                                                   = [];
        $default['taxopress_autoterm']['title']                    = 'Auto term';
        $default['taxopress_autoterm']['taxonomy']                 = 'post_tag';
        $default['post_types']                                     = ['post'];
        $default['post_status']                                    = ['publish'];
        $default['taxopress_autoterm']['autoterm_from']            = 'posts';
        $default['taxopress_autoterm']['autoterm_useall']          = '0';
        $default['taxopress_autoterm']['autoterm_useall']          = '0';
        $default['taxopress_autoterm']['autoterm_useonly']         = '0';
        $default['taxopress_autoterm']['autoterm_target']          = '0';
        $default['taxopress_autoterm']['autoterm_word']            = '0';
        $default['taxopress_autoterm']['autoterm_hash']            = '0';
        $default['taxopress_autoterm']['terms_limit']              = '0';
        $default['specific_terms']                                  = [];
        $result                                                    = taxopress_update_autoterm($default);
    }
    update_option('taxopress_default_autoterms', $result);
}

add_action('admin_init', 'taxopress_create_default_autoterm', 8);


/**
 * Add to or update our TAXOPRESS option with new data.
 *
 *
 * @param array $data Array of autoterm data to update. Optional.
 * @return bool|string False on failure, string on success.
 * @internal
 *
 */
function taxopress_update_autoterm($data = [])
{
    foreach ($data as $key => $value) {

        if (is_string($value)) {
            $data[$key] = sanitize_text_field($value);
        } else {
            array_map('sanitize_text_field', $data[$key]);
        }
    }

    $autoterms = taxopress_get_autoterm_data();

    $title                               = $data['taxopress_autoterm']['title'];
    $title                               = str_replace('"', '', htmlspecialchars_decode($title));
    $title                               = htmlspecialchars($title, ENT_QUOTES);
    $title                               = trim($title);
    $data['taxopress_autoterm']['title'] = stripslashes_deep($title);


    //update other post post
    $data['taxopress_autoterm']['specific_terms']      = isset($data['specific_terms']) ? $data['specific_terms'] : '';
    $data['taxopress_autoterm']['post_types']          = isset($data['post_types']) ? $data['post_types'] : [];
    $data['taxopress_autoterm']['post_status']         = isset($data['post_status']) ? $data['post_status'] : [];
    
    //update our custom checkbox value if not checked
    if (!isset($data['taxopress_autoterm']['autoterm_useall'])) {
        $data['taxopress_autoterm']['autoterm_useall'] = 0;
    }
    if (!isset($data['taxopress_autoterm']['autoterm_useonly'])) {
        $data['taxopress_autoterm']['autoterm_useonly'] = 0;
    }
    if (!isset($data['taxopress_autoterm']['autoterm_target'])) {
        $data['taxopress_autoterm']['autoterm_target'] = 0;
    }
    if (!isset($data['taxopress_autoterm']['autoterm_word'])) {
        $data['taxopress_autoterm']['autoterm_word'] = 0;
    }
    if (!isset($data['taxopress_autoterm']['autoterm_hash'])) {
        $data['taxopress_autoterm']['autoterm_hash'] = 0;
    }

    if (isset($data['edited_autoterm'])) {
        $autoterm_id             = $data['edited_autoterm'];
        $autoterms[$autoterm_id] = $data['taxopress_autoterm'];
        $success                 = update_option('taxopress_autoterms', $autoterms);
        //return 'update_success';
    } else {
        $autoterm_id                      = (int)get_option('taxopress_autoterm_ids_increament') + 1;
        $data['taxopress_autoterm']['ID'] = $autoterm_id;
        $autoterms[$autoterm_id]          = $data['taxopress_autoterm'];
        $success                          = update_option('taxopress_autoterms', $autoterms);
        $update_id                        = update_option('taxopress_autoterm_ids_increament', $autoterm_id);
        //return 'add_success';
    }

    return $autoterm_id;
}

/**
 * Successful update callback.
 */
function taxopress_autoterms_update_success_admin_notice()
{
    echo taxopress_admin_notices_helper(__('Settings updated successfully.', 'simple-tags'));
}

/**
 * Successful deleted callback.
 */
function taxopress_autoterms_delete_success_admin_notice()
{
    echo taxopress_admin_notices_helper(__('Auto terms successfully deleted.', 'simple-tags'), false);
}

/**
 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
 *
 * @link https://core.trac.wordpress.org/ticket/23367
 *
 * @param string[] $args Array of removable query arguments.
 * @return string[] Updated array of removable query arguments.
 */
function taxopress_saved_autoterm_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'new_autoterm',
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
function taxopress_deleted_autoterm_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'deleted_autoterm',
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
function taxopress_delete_autoterm_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'action',
        'taxopress_autoterms',
        '_wpnonce',
    ]);
}

/**
 * Delete our custom autoterm from the array of autoterms.
 * @return bool|string False on failure, string on success.
 */
function taxopress_action_delete_autoterm($autoterm_id)
{
    $autoterms = taxopress_get_autoterm_data();

    if (array_key_exists($autoterm_id, $autoterms)) {
        unset($autoterms[$autoterm_id]);
        $success = update_option('taxopress_autoterms', $autoterms);
    }

    if (isset($success)) {
        add_action('admin_notices', "taxopress_taxdeleted_admin_notice");
        wp_safe_redirect(
            add_query_arg(
                [
                    'page'             => 'st_autoterms',
                    'deleted_autoterm' => 1,
                ],
                taxopress_admin_url('admin.php')
            )
        );
        exit();
    }
}
?>