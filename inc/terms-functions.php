<?php

/**
 * Handle the save and deletion of terms data.
 */
function taxopress_process_terms()
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
    if ('st_terms' !== $_GET['page']) {
        return;
    }

    if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'taxopress-delete-terms') {
        $nonce = sanitize_text_field($_REQUEST['_wpnonce']);
        if (wp_verify_nonce($nonce, 'terms-action-request-nonce')) {
            $term = get_term(sanitize_text_field($_REQUEST['taxopress_terms']));
            wp_delete_term( $term->term_id, $term->taxonomy );
        }
        add_action('admin_notices', "taxopress_term_delete_success_admin_notice");
        add_filter('removable_query_args', 'taxopress_delete_terms_filter_removable_query_args');
    }
}
add_action('admin_init', 'taxopress_process_terms', 8);

/**
 * Successful deleted callback.
 */
function taxopress_term_delete_success_admin_notice()
{
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo taxopress_admin_notices_helper(esc_html__('Term deleted successfully.', 'simple-tags'), false);
}

/**
 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
 *
 * @link https://core.trac.wordpress.org/ticket/23367
 *
 * @param string[] $args Array of removable query arguments.
 * @return string[] Updated array of removable query arguments.
 */
function taxopress_delete_terms_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'action',
        'taxopress_terms',
        '_wpnonce',
    ]);
}