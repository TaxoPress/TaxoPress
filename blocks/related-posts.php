<?php

/**
 * Register our block.
 */
function st_related_posts_block_init()
{
    global $post, $pagenow;

    if (!function_exists('register_block_type')) {
        return;
    }

    if($pagenow === 'widgets.php'){
        return;
    }

    // Register our block editor script.
    wp_register_script(
        'st-block-related-posts', STAGS_URL . '/blocks/src/related-posts.js',
        ['wp-blocks', 'wp-element', 'wp-components', 'wp-editor']
    );

    // Register our block, and explicitly define the attributes we accept.
    $relatedposts_data = taxopress_get_relatedpost_data();

    $options = [];
    $default = '';
    if (count($relatedposts_data) > 0) {
        $sn = 0;
        foreach ($relatedposts_data as $key => $value) {
            $sn++;
            if ($sn === 1) {
                $default = $key;
            }
            $options[] = ['label' => $value['title'], 'value' => $key];
        }
    }

    register_block_type('taxopress/related-posts', [
        'attributes'      => [
            'relatedpost_id' => [
                'type'    => 'string',
                'default' => $default,
            ],
            'post_id'        => [
                'type' => 'string',
            ],
        ],
        'editor_script'   => 'st-block-related-posts',
        'render_callback' => 'st_related_posts_render',
    ]);

    $shortcode_page = sprintf(
        '<a href="%s">%s</a>',
        add_query_arg(
            [
                'page' => 'st_related_posts',
            ],
            admin_url('admin.php')
        ),
        __('this page.', 'simple-tags')
    );

    $select_desc  = __('Related Posts shortcode are added on Related Post screen', 'simple-tags');
    $select_label = __('Select related post shortcode', 'simple-tags');
    $panel_title  = __('Related Posts (TaxoPress)', 'simple-tags');

    wp_localize_script('st-block-related-posts', 'ST_RELATED_POST', [
        'options'      => $options,
        'panel_title'  => $panel_title,
        'select_label' => $select_label,
        'select_desc'  => $select_desc
    ]);

}

add_action('init', 'st_related_posts_block_init');

/**
 * Our combined block and shortcode renderer.
 *
 * @param array $attributes The attributes that were set on the block or shortcode.
 */
function st_related_posts_render($attributes)
{
    global $post;
    ob_start();
    echo do_shortcode('[taxopress_relatedposts id="' . $attributes['relatedpost_id'] . '"]');

    return ob_get_clean();
}