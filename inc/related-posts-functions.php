<?php
/**
 * Fetch our TAXOPRESS Related Posts option.
 *
 * @return mixed
 */
function taxopress_get_relatedpost_data()
{
    return array_filter((array)apply_filters('taxopress_get_relatedpost_data', get_option('taxopress_relatedposts', []),
        get_current_blog_id()));
}

/**
 * Get the selected relatedpost from the $_POST global.
 *
 * @return bool|string False on no result, sanitized relatedpost if set.
 * @internal
 *
 */
function taxopress_get_current_relatedpost()
{

    $relatedposts = false;

    if (!empty($_GET) && isset($_GET['taxopress_relatedposts'])) {
        $relatedposts = sanitize_text_field($_GET['taxopress_relatedposts']);
    } else {
        $relatedposts = taxopress_get_relatedpost_data();
        if (!empty($relatedposts)) {
            // Will return the first array key.
            $relatedposts = key($relatedposts);
        }
    }

    /**
     * Filters the current relatedpost to edit.
     *
     * @param string $relatedposts relatedpost slug.
     */
    return apply_filters('taxopress_current_relatedpost', $relatedposts);
}

/**
 * Handle the save and deletion of relatedpost data.
 */
function taxopress_process_relatedpost()
{

    if (wp_doing_ajax()) {
        return;
    }

    if (!is_admin()) {
        return;
    }

    if (empty($_GET)) {
        return;
    }

    if (!isset($_GET['page'])) {
        return;
    }
    if ('st_related_posts' !== $_GET['page']) {
        return;
    }

    if(!current_user_can('simple_tags')){
        return;
    }

    if (isset($_GET['new_relatedpost'])) {
        if ((int)$_GET['new_relatedpost'] === 1) {
            add_action('admin_notices', "taxopress_relatedposts_update_success_admin_notice");
            add_filter('removable_query_args', 'taxopress_saved_relatedpost_filter_removable_query_args');
        }
    }

    if (isset($_GET['deleted_relatedpost'])) {
        if ((int)$_GET['deleted_relatedpost'] === 1) {
            add_action('admin_notices', "taxopress_relatedposts_delete_success_admin_notice");
            add_filter('removable_query_args', 'taxopress_deleted_relatedpost_filter_removable_query_args');
        }
    }


    if (!empty($_POST) && isset($_POST['relatedpost_submit'])) {
        $result = '';
        if (isset($_POST['relatedpost_submit'])) {
            check_admin_referer('taxopress_addedit_relatedpost_nonce_action',
                'taxopress_addedit_relatedpost_nonce_field');
            $result = taxopress_update_relatedpost($_POST);
        }

        if ($result) {
            wp_safe_redirect(
                add_query_arg(
                    [
                        'page'                   => 'st_related_posts',
                        'add'                    => 'new_item',
                        'action'                 => 'edit',
                        'taxopress_relatedposts' => $result,
                        'new_relatedpost'        => 1,
                    ],
                    taxopress_admin_url('admin.php')
                )
            );

            exit();
        }
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] === 'taxopress-delete-relatedpost') {
        $nonce = sanitize_text_field($_REQUEST['_wpnonce']);
        if (wp_verify_nonce($nonce, 'relatedpost-action-request-nonce')) {
            taxopress_action_delete_relatedpost(sanitize_text_field($_REQUEST['taxopress_relatedposts']));
        }
        add_filter('removable_query_args', 'taxopress_delete_relatedpost_filter_removable_query_args');
    }
}


/**
 * Create default related post.
 */
function taxopress_create_default_related_post()
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

    if ((int)get_option('taxopress_default_relatedposts') > 0) {
        return;
    }

    if (count(taxopress_get_relatedpost_data()) > 0) {
        return;
    }

    $default                                           = [];
    $default['taxopress_related_post']['title']        = esc_html__('Related Posts', 'simple-tags');
    $default['taxopress_related_post']['title_header'] = 'h4';
    $default['taxopress_related_post']['before']       = '';
    $default['taxopress_related_post']['after']        = '';
    $default['taxopress_related_post']['post_types']   = ['post'];
    $default['taxopress_related_post']['taxonomy']     = 'post_tag';
    $default['taxopress_related_post']['number']       = 5;
    $default['taxopress_related_post']['limit_days']   = 0;
    $default['taxopress_related_post']['order']        = 'count-desc';
    $default['taxopress_related_post']['nopoststext']  = __('No related posts.', 'simple-tags');
    $default['taxopress_related_post']['xformat']      = '<a href="%post_permalink%" title="%post_title% (%post_date%)">%post_title%</a>';
    $default['relatedpost_submit']                     = 'Add Related Posts';
    $default['cpt_tax_status']                         = 'new';
    $result                                            = taxopress_update_relatedpost($default);
    update_option('taxopress_default_relatedposts', $result);
}


/**
 * Add to or update our TAXOPRESS option with new data.
 *
 *
 * @param array $data Array of relatedpost data to update. Optional.
 * @return bool|string False on failure, string on success.
 * @internal
 *
 */
function taxopress_update_relatedpost($data = [])
{
    $sanitized_data = [];
    foreach ($data as $key => $value) {
        if (!is_array($value)) {
            $sanitized_data[$key] = taxopress_sanitize_text_field($value);
        } else {
            $new_value = [];
            foreach ($data[$key] as $option_key => $option_value) {
                if ($option_key === 'xformat') {
                    $new_value[$option_key] = taxopress_sanitize_text_field($option_value);
                } else {
                    $new_value[$option_key] = taxopress_sanitize_text_field($option_value);
                }
            }
            $sanitized_data[$key] = $new_value;
        }
    }
    $data = $sanitized_data;

    $relatedposts = taxopress_get_relatedpost_data();

    $title                                   = $data['taxopress_related_post']['title'];
    $title                                   = str_replace('"', '', htmlspecialchars_decode($title));
    $title                                   = htmlspecialchars($title, ENT_QUOTES);
    $title                                   = trim($title);
    $data['taxopress_related_post']['title'] = stripslashes_deep($title);

    $xformat                                    = $data['taxopress_related_post']['xformat'];
    $data['taxopress_related_post']['xformat']  = stripslashes_deep($xformat);
    $data['taxopress_related_post']['post_types'] = isset($data['post_types']) ? $data['post_types'] : [];


    if (isset($data['edited_relatedpost'])) {
        $relatedpost_id                = $data['edited_relatedpost'];
        $relatedposts[$relatedpost_id] = $data['taxopress_related_post'];
        $success                       = update_option('taxopress_relatedposts', $relatedposts);
        //return 'update_success';
    } else {
        $relatedpost_id                       = (int)get_option('taxopress_relatedpost_ids_increament') + 1;
        $data['taxopress_related_post']['ID'] = $relatedpost_id;
        $relatedposts[$relatedpost_id]        = $data['taxopress_related_post'];
        $success                              = update_option('taxopress_relatedposts', $relatedposts);
        $update_id                            = update_option('taxopress_relatedpost_ids_increament', $relatedpost_id);
        //return 'add_success';
    }

    return $relatedpost_id;

}

/**
 * Successful update callback.
 */
function taxopress_relatedposts_update_success_admin_notice()
{
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo taxopress_admin_notices_helper(esc_html__('Settings updated successfully.', 'simple-tags'));
}

/**
 * Successful deleted callback.
 */
function taxopress_relatedposts_delete_success_admin_notice()
{
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo taxopress_admin_notices_helper(esc_html__('Related Posts successfully deleted.', 'simple-tags'), false);
}

/**
 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
 *
 * @link https://core.trac.wordpress.org/ticket/23367
 *
 * @param string[] $args Array of removable query arguments.
 * @return string[] Updated array of removable query arguments.
 */
function taxopress_saved_relatedpost_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'new_relatedpost',
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
function taxopress_deleted_relatedpost_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'deleted_relatedpost',
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
function taxopress_delete_relatedpost_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'action',
        'taxopress_relatedposts',
        '_wpnonce',
    ]);
}

/**
 * Delete our custom related post from the array of related posts.
 * @return bool|string False on failure, string on success.
 */
function taxopress_action_delete_relatedpost($relatedpost_id)
{
    $relatedposts = taxopress_get_relatedpost_data();

    if (array_key_exists($relatedpost_id, $relatedposts)) {
        unset($relatedposts[$relatedpost_id]);
        $success = update_option('taxopress_relatedposts', $relatedposts);
    }

    if (isset($success)) {
        add_action('admin_notices', "taxopress_taxdeleted_admin_notice");
        wp_safe_redirect(
            add_query_arg(
                [
                    'page'                => 'st_related_posts',
                    'deleted_relatedpost' => 1,
                ],
                taxopress_admin_url('admin.php')
            )
        );
        exit();
    }
}

function taxopress_relatedposts_shortcode($atts)
{
    extract(shortcode_atts([
        'id' => 0
    ], $atts));

    $relatedpost_id = $id;
    $relatedposts   = taxopress_get_relatedpost_data();

    ob_start();
    if (array_key_exists($relatedpost_id, $relatedposts)) {
        $related_post_array = $relatedposts[$relatedpost_id];
        $relatedpost_arg    = build_query($related_post_array);

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo SimpleTags_Client_RelatedPosts::get_related_posts($relatedpost_arg);

    } else {
        echo esc_html__('Related Posts not found.', 'simple-tags');
    }
    
    $html = ob_get_clean();

    return $html;


}

/**
 * Auto add related post to post content
 *
 * @param string $content
 *
 * @return string
 */
function taxopress_relatedposts_the_content($content = '')
{

    $post_tags = taxopress_get_relatedpost_data();

    if (count($post_tags) > 0) {
        foreach ($post_tags as $post_tag) {

            // Get option
            $post_types = (isset($post_tag['post_types']) && is_array($post_tag['post_types']) && count($post_tag['post_types']) > 0) ? $post_tag['post_types'] : false;

            if (!$post_types) {
                continue;
            }

            $marker = false;
            if (is_singular() && in_array(get_post_type(), $post_types)) {
                $marker = true;
            }
            if (true === $marker) {
                $relatedpost_arg = build_query($post_tag);
                $content         .= SimpleTags_Client_RelatedPosts::get_related_posts($relatedpost_arg);
            }
        }
    }

    return $content;
}