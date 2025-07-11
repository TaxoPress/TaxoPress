<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Taxopress_Terms_List extends WP_List_Table
{

    /** Class constructor */
    public function __construct()
    {

        parent::__construct([
            'singular' => 'Term', //singular name of the listed records
            'plural'   => 'Terms', //plural name of the listed records
            'ajax'     => true //does this table support ajax?
        ]);
    }

    /**
     * Flatten a hierarchical terms array into a flat array.
     * 
     * @param array $terms Array of term objects.
     * @param int $max_depth Maximum depth to traverse (default 10).
     * @return array Flattened array of term objects.
    */
     function taxopress_flatten_terms_tree($terms, $max_depth = 7) {
        $flat = [];
        $map = [];
        $children_map = [];
        $visited = [];

        // Build a map of terms by ID and their children
        foreach ($terms as $term) {
            $map[$term->term_id] = $term;
            $children_map[$term->parent][] = $term;
        }

        // Initialize stack with root-level terms (no valid parent)
        $stack = [];
        foreach ($terms as $term) {
            if ($term->parent === 0 || !isset($map[$term->parent])) {
                $stack[] = ['term' => $term, 'depth' => 0];
            }
        }

        // Iterative depth-first traversal
        while (!empty($stack)) {
            $node = array_pop($stack);
            $term = $node['term'];
            $depth = $node['depth'];

            if ($depth > $max_depth) {
                continue;
            }
            if (isset($visited[$term->term_id])) {
                continue; // Prevent cycles
            }
            $visited[$term->term_id] = true;

            $flat[] = $term;

            // Use pre-indexed children map
            if (!empty($children_map[$term->term_id])) {
                foreach (array_reverse($children_map[$term->term_id]) as $child) {
                    $stack[] = ['term' => $child, 'depth' => $depth + 1];
                }
            }
        }

        return $flat;
    }

    /**
     * Arrange terms in hierarchical order with depth info for dash prefixing.
    */
    function taxopress_arrange_terms_hierarchically($terms) {
        $terms_by_id = [];
        $children = [];
        foreach ($terms as $term) {
            $terms_by_id[$term->term_id] = $term;
            $children[$term->parent][] = $term->term_id;
        }

        $ordered = [];
        $add_term = function($term_id, $depth) use (&$add_term, &$terms_by_id, &$children, &$ordered) {
            $term = $terms_by_id[$term_id];
            $term->taxopress_depth = $depth;
            $ordered[] = $term;
            if (!empty($children[$term_id])) {
                foreach ($children[$term_id] as $child_id) {
                    $add_term($child_id, $depth + 1);
                }
            }
        };

        // Start with root terms
        foreach ($terms as $term) {
            if ($term->parent == 0 || !isset($terms_by_id[$term->parent])) {
                $add_term($term->term_id, 0);
            }
        }
        return $ordered;
    }

    public function get_all_terms($count = false)
    {

        $taxonomies = array_keys(get_all_taxopress_taxonomies_request());
        $taxonomy_settings = taxopress_get_all_edited_taxonomy_data();

        $search = (!empty($_REQUEST['s'])) ? sanitize_text_field($_REQUEST['s']) : '';

        $items_per_page = $this->get_items_per_page('st_terms_per_page', 20);
        $page           = $this->get_pagenum();
        $offset         = ($page - 1) * $items_per_page;

        $selected_post_type = (!empty($_REQUEST['terms_filter_post_type'])) ? [sanitize_text_field($_REQUEST['terms_filter_post_type'])] : '';
        $selected_taxonomy = (!empty($_REQUEST['terms_filter_taxonomy'])) ? sanitize_text_field($_REQUEST['terms_filter_taxonomy']) : '';

        $order_setting = isset($taxonomy_settings[$selected_taxonomy]['order']) ? $taxonomy_settings[$selected_taxonomy]['order'] : 'desc';
        $orderby_setting = isset($taxonomy_settings[$selected_taxonomy]['orderby']) ? $taxonomy_settings[$selected_taxonomy]['orderby'] : 'ID';

        // If viewing via taxopress_terms_taxonomy, override to show all terms in that taxonomy
        if (!empty($_REQUEST['taxopress_terms_taxonomy'])) {
            $selected_taxonomy = sanitize_text_field($_REQUEST['taxopress_terms_taxonomy']);
            $taxonomies = [$selected_taxonomy];
            $show_all_terms_in_taxonomy = false;
        } else {
            $show_all_terms_in_taxonomy = false;
            if (!empty($selected_taxonomy)) {
                $taxonomies = [$selected_taxonomy];
            }
        }

        // Check if any taxonomy uses manual ordering
        $manual_order = false;
        foreach ($taxonomies as $taxonomy) {
            $order_setting = isset($taxonomy_settings[$taxonomy]['order']) ? $taxonomy_settings[$taxonomy]['order'] : 'desc';
            $orderby_setting = isset($taxonomy_settings[$selected_taxonomy]['orderby']) ? $taxonomy_settings[$selected_taxonomy]['orderby'] : 'ID';
            if ($order_setting === 'taxopress_term_order') {
                $manual_order = true;
                break;
            }
        }

        // If any taxonomy uses manual order, use the original per-taxonomy logic
        if ($manual_order) {
            $terms = [];
            foreach ($taxonomies as $taxonomy) {
                $custom_order = get_option('taxopress_term_order_' . $taxonomy, []);
                $order_setting = isset($taxonomy_settings[$taxonomy]['order']) ? $taxonomy_settings[$taxonomy]['order'] : 'desc';
                $orderby_setting = isset($taxonomy_settings[$taxonomy]['orderby']) ? $taxonomy_settings[$taxonomy]['orderby'] : 'ID';
                $use_custom_order = ($order_setting === 'taxopress_term_order');

                $terms_attr = [
                    'taxonomy' => [$taxonomy],
                    'post_types' => $selected_post_type,
                    'hide_empty' => false,
                    'pad_counts' => true,
                    'update_term_meta_cache' => true,
                    'search' => $search,
                    'include' => 'all',
                ];

                if (!$use_custom_order) {
                    $terms_attr['orderby'] = $orderby_setting;
                    $terms_attr['order'] = $order_setting;
                }

                // Only paginate after merging all terms
                $taxonomy_terms = get_terms($terms_attr);

                if (empty($taxonomy_terms) || is_wp_error($taxonomy_terms)) {
                    continue;
                }

                if ($use_custom_order) {
                    // Manual custom ordering
                    $terms_by_id = [];
                    $new_terms = [];
                    $ordered_terms = [];

                    foreach ($taxonomy_terms as $term) {
                        $terms_by_id[$term->term_id] = $term;
                    }

                    // Terms not in custom order
                    foreach ($terms_by_id as $term_id => $term) {
                        if (!in_array($term_id, $custom_order)) {
                            $new_terms[] = $term;
                        }
                    }

                    // Ordered terms
                    foreach ($custom_order as $term_id) {
                        if (isset($terms_by_id[$term_id])) {
                            $ordered_terms[] = $terms_by_id[$term_id];
                        }
                    }

                    // Merge: new (unordered) terms first, then custom-ordered ones
                    $terms = array_merge($terms, $new_terms, $ordered_terms);
                } else {
                    if ($orderby_setting === 'random') {
                        shuffle($taxonomy_terms);
                        if ($order_setting === 'desc') {
                            $taxonomy_terms = array_reverse($taxonomy_terms);
                        }
                    }
                    $terms = array_merge($terms, $taxonomy_terms);
                }
            }

            // Paginate after merging, unless showing all terms in taxonomy
            if (!$count) {
                $terms = array_slice($terms, $offset, $items_per_page);
            }

            // HIERARCHY SUPPORT
            if (!empty($selected_taxonomy)) {
                $terms = $this->taxopress_arrange_terms_hierarchically($terms);
            } else {
                $terms = $this->taxopress_flatten_terms_tree($terms);
            }

            return $terms;
        }

        // If no manual ordering, use the efficient all-in-one get_terms
        $terms_attr = [
            'taxonomy' => $taxonomies,
            'post_types' => $selected_post_type,
            'orderby' => $orderby_setting,
            'order' => $order_setting,
            'search' => $search,
            'hide_empty' => false,
            'include' => 'all',
            'pad_counts' => true,
            'update_term_meta_cache' => true,
        ];
        if ($count || $show_all_terms_in_taxonomy) {
            $terms_attr['number'] = 0;
        } else {
            $terms_attr['offset'] = $offset;
            $terms_attr['number'] = $items_per_page;
        }

        $terms = get_terms($terms_attr);

        if (empty($terms) || is_wp_error($terms)) {
            return [];
        }

        if ($orderby_setting === 'random') {
            shuffle($terms);
            if ($order_setting === 'desc') {
                $terms = array_reverse($terms);
            }
        }

        // HIERARCHY SUPPORT
        if (!empty($selected_taxonomy)) {
            $terms = $this->taxopress_arrange_terms_hierarchically($terms);
        } else {
            $terms = $this->taxopress_flatten_terms_tree($terms);
        }

        return $terms;
    }

    /**
     * Retrieve st_Terms data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public function get_st_Terms()
    {
        return $this->get_all_terms();
    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public function record_count()
    {
        return count($this->get_all_terms(true));
    }

    /**
     * Show single row item
     *
     * @param array $item
     */
    public function single_row($item)
    {
        $class = ['st-terms-tr'];
        $id    = 'term-' . $item->term_id . '';
        echo sprintf('<tr id="%s" class="%s">', esc_attr($id), esc_attr(implode(' ', $class)));
        $this->single_row_columns($item);
        echo '</tr>';
    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns()
    {

        if (!empty($_REQUEST['taxopress_terms_taxonomy'])) {
            return [
                'name'        => esc_html__('Title', 'simple-tags'),
                'description' => esc_html__('Description', 'simple-tags'),
                'count'       => esc_html__('Count', 'simple-tags'),
            ];
        }
        $columns = [
            'cb'      => '<input type="checkbox" />',
            'name'     => esc_html__('Title', 'simple-tags'),
            'slug'     => esc_html__('Slug', 'simple-tags'),
            'description'     => esc_html__('Description', 'simple-tags'),
            'taxonomy'  => esc_html__('Taxonomy', 'simple-tags'),
            'posttypes'  => esc_html__('Post Types', 'simple-tags'),
            'taxopress_custom_url'  => esc_html__('Custom URL', 'simple-tags'),
            'synonyms'  => esc_html__('Synonyms', 'simple-tags'),
            'linked_terms'  => esc_html__('Linked Terms', 'simple-tags'),
            'hidden_status' => esc_html__('Status', 'simple-tags'),
            'count'  => esc_html__('Count', 'simple-tags')
        ];

        if (!taxopress_is_pro_version()) {
            unset($columns['synonyms']);
            unset($columns['linked_terms']);
        }

        if (!(int) SimpleTags_Plugin::get_option_value('enable_hidden_terms')) {
            unset($columns['hidden_status']);
        }

        return $columns;
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    protected function get_sortable_columns()
    {
        $sortable_columns = [
            'name'      => ['name', true],
            'slug'      => ['slug', true],
            'taxonomy'  => ['taxonomy', true],
            'count'     => ['count', true],
        ];

        return $sortable_columns;
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />', 'taxopress_terms', $item->term_id);
    }

    /**
     * Get the bulk actions to show in the top page dropdown
     *
     * @return array
     */
    protected function get_bulk_actions()
    {
        $actions = [
            'taxopress-terms-delete-terms' => esc_html__('Delete', 'simple-tags'),
            'taxopress-terms-copy-terms'   => esc_html__('Copy', 'simple-tags')
        ];

        return $actions;
    }

    /**
     * Add custom filter to tablenav
     *
     * @param string $which
     */
    protected function extra_tablenav($which)
    {
        // Hide filters if taxopress_show_all=1
        if ('top' === $which && empty($_REQUEST['taxopress_show_all'])) {

            $post_types = get_post_types(['public' => true], 'objects');

            $taxonomies = get_all_taxopress_taxonomies_request();

            $selected_post_type = (!empty($_REQUEST['terms_filter_post_type'])) ? sanitize_text_field($_REQUEST['terms_filter_post_type']) : '';
            $selected_taxonomy = (!empty($_REQUEST['terms_filter_taxonomy'])) ? sanitize_text_field($_REQUEST['terms_filter_taxonomy']) : '';
            $selected_post = (!empty($_REQUEST['destination_post'])) ? sanitize_text_field($_REQUEST['destination_post']) : '';

            $selected_option = 'public';
            if (isset($_GET['taxonomy_type']) && $_GET['taxonomy_type'] === 'all') {
                $selected_option = 'all';
            } elseif (isset($_GET['taxonomy_type']) && $_GET['taxonomy_type'] === 'private') {
                $selected_option = 'private';
            }
?>

            <div class="alignleft actions autoterms-terms-table-copy" id="taxopress-copy-selection-boxes" style="display: none;">
                <select class="auto-terms-terms-copy-select" name="taxopress_destination_taxonomy" id="terms_copy_select_destination_taxonomy">
                    <option value=""><?php esc_html_e('Select Destination Taxonomy', 'simple-tags'); ?></option> <?php
                    foreach ($taxonomies as $taxonomy) {
                        echo '<option value="' . esc_attr($taxonomy->name) . '">' . esc_html($taxonomy->labels->name) . '</option>';
                    } ?>
                </select>

                <select class="auto-terms-terms-copy-select" name="taxopress_destination_post_type" id="terms_copy_select_destination_post">
                    <?php
                        $post_type_label = !empty($selected_post_type) ? esc_html($selected_post_type) : esc_html__('post type', 'simple-tags');
                        ?>
                        <option value=""><?php printf(esc_html__('Select Destination %s', 'simple-tags'), $post_type_label); ?></option>
                        <option value="all" <?php selected($selected_post, 'all'); ?>><?php printf(esc_html__('All %s', 'simple-tags'), $post_type_label); ?></option>.
                        <?php  
                        // I want to show all posts when no post type is selected
                        if (empty($selected_post_type)) {
                            $all_posts = get_posts(['post_type' => 'any', 'numberposts' => -1]);
                            foreach ($all_posts as $post): ?>
                            <option value="<?php echo esc_attr($post->ID); ?>" <?php selected($selected_post, $post->ID); ?>>
                            <?php echo esc_html($post->post_title); ?>
                            </option>
                            <?php endforeach; 
                        } else {
                            // Show posts only for selected post type
                            $posts = get_posts(['post_type' => $selected_post_type, 'numberposts' => -1]);
                            foreach ($posts as $post): ?>
                            <option value="<?php echo esc_attr($post->ID); ?>" <?php selected($selected_post, $post->ID); ?>>
                            <?php echo esc_html($post->post_title); ?>
                            </option>
                            <?php endforeach; 
                        }
                            ?>
                </select>
            </div>
            <div class="alignleft actions autoterms-terms-table-filter">

                <select class="auto-terms-terms-filter-select" name="terms_filter_select_post_type" id="terms_filter_select_post_type">
                    <option value=""><?php esc_html_e('Post type', 'simple-tags'); ?></option>
                    <?php
                    foreach ($post_types as $post_type) {
                        echo '<option value="' . esc_attr($post_type->name) . '" ' . selected($selected_post_type, $post_type->name, false) . '>' . esc_html($post_type->label) . '</option>';
                    }
                    ?>
                </select>

                <select class="auto-terms-terms-filter-select" name="terms_filter_select_taxonomy" id="terms_filter_select_taxonomy">
                    <option value=""><?php esc_html_e('Taxonomy', 'simple-tags'); ?></option>
                    <?php
                    foreach ($taxonomies as $taxonomy) {
                        echo '<option value="' . esc_attr($taxonomy->name) . '" ' . selected($selected_taxonomy, $taxonomy->name, false) . '>' . esc_html($taxonomy->labels->name) . '</option>';
                    }
                    ?>
                </select>

                <select class="auto-terms-terms-filter-select" name="terms_filter_select_taxonomy_type" id="terms_filter_select_taxonomy_type">
                    <option value="all" <?php echo ($selected_option === 'all' ? 'selected="selected"' : ''); ?>><?php echo esc_html__('All Taxonomies', 'simple-tags'); ?></option>
                    <option value="public" <?php echo ($selected_option === 'public' ? 'selected="selected"' : ''); ?>><?php echo esc_html__('Public Taxonomies', 'simple-tags'); ?></option>
                    <option value="private" <?php echo ($selected_option === 'private' ? 'selected="selected"' : ''); ?>><?php echo esc_html__('Private Taxonomies', 'simple-tags'); ?></option>
                </select>
                
                <a href="javascript:void(0)" class="taxopress-terms-tablenav-filter button"><?php esc_html_e('Filter', 'simple-tags'); ?></a>

            </div>
        <?php
        }
    }

    /**
     * Process bulk actions
     */
    public function process_bulk_action()
    {

        $query_arg = '_wpnonce';
        $action = 'bulk-' . $this->_args['plural'];
        $checked = isset($_REQUEST[$query_arg]) ? wp_verify_nonce(sanitize_key($_REQUEST[$query_arg]), $action) : false;

        if (!$checked || !current_user_can('simple_tags')) {
            return;
        }

        if ($this->current_action() === 'taxopress-terms-delete-terms') {
            $taxopress_terms = array_map('sanitize_text_field', (array)$_REQUEST['taxopress_terms']);
            if (!empty($taxopress_terms)) {
                foreach ($taxopress_terms as $taxopress_term) {
                    $term = get_term($taxopress_term);
                    wp_delete_term($term->term_id, $term->taxonomy);
                }
                if (count($taxopress_terms) > 1) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo taxopress_admin_notices_helper(esc_html__('Terms deleted successfully.', 'simple-tags'), false);
                } else {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo taxopress_admin_notices_helper(esc_html__('Term deleted successfully.', 'simple-tags'), false);
                }
            }
        }
        if ($this->current_action() === 'taxopress-terms-copy-terms') {
            $taxopress_terms = array_map('sanitize_text_field', (array)$_REQUEST['taxopress_terms']);
            $destination_taxonomy = sanitize_text_field($_REQUEST['taxopress_destination_taxonomy']);
            $destination_post = sanitize_text_field($_REQUEST['taxopress_destination_post_type']);

            if (!empty($taxopress_terms) && !empty($destination_taxonomy)) {
                foreach ($taxopress_terms as $taxopress_term) {
                    $term = get_term($taxopress_term);
                    wp_insert_term($term->name, $destination_taxonomy, [
                        'slug' => $term->slug,
                        'description' => $term->description,
                    ]);
                                if (!empty($destination_post) && $destination_post !== 'all') {
                                    wp_set_object_terms($destination_post, [$term->term_id], $destination_taxonomy, true);
                                }
                
                                if ($destination_post === 'all') {
                                    $all_posts = get_posts(['post_type' => 'any', 'numberposts' => -1]);
                                    foreach ($all_posts as $post) {
                                        wp_set_object_terms($post->ID, [$term->term_id], $destination_taxonomy, true);
                                    }
                                }
                            }
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo taxopress_admin_notices_helper(esc_html__('Term(s) copied successfully.', 'simple-tags'), true);
            }
        }
    }

    protected function column_taxopress_custom_url($item) {
        $taxopress_custom_url = get_term_meta($item->term_id, 'taxopress_custom_url', true);
        return (!empty($taxopress_custom_url) && filter_var($taxopress_custom_url, FILTER_VALIDATE_URL)) 
            ? sprintf('<a href="%s" target="_blank">%s</a>', esc_url($taxopress_custom_url), esc_html($taxopress_custom_url)) 
            : '-';
    }

    protected function column_hidden_status($item) {
        $hidden_terms = get_transient('taxopress_hidden_terms_' . $item->taxonomy);
    
        if (!empty($hidden_terms) && in_array($item->term_id, $hidden_terms)) {
            return esc_html__('Hidden', 'simple-tags');
        } 
    
        return esc_html__('Live', 'simple-tags');
    }  

    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        return !empty($item->$column_name) ? $item->$column_name : '&mdash;';
    }

    /** Text displayed when no stterm data is available */
    public function no_items()
    {
        esc_html_e('No terms found.', 'simple-tags');
    }

    /**
     * Displays the search box.
     *
     * @param string $text The 'submit' button label.
     * @param string $input_id ID attribute value for the search input field.
     *
     *
     */
    public function search_box($text, $input_id)
    {
        // Hide search box if taxopress_show_all=1
        if ((!empty($_REQUEST['taxopress_show_all']) && $_REQUEST['taxopress_show_all'] == '1')) {
            return;
        }

        if (empty($_REQUEST['s']) && !$this->has_items()) {
            //return;
        }

        $input_id = $input_id . '-search-input';

        if (!empty($_REQUEST['orderby'])) {
            echo '<input type="hidden" name="orderby" value="' . esc_attr(sanitize_text_field($_REQUEST['orderby'])) . '" />';
        }
        if (!empty($_REQUEST['order'])) {
            echo '<input type="hidden" name="order" value="' . esc_attr(sanitize_text_field($_REQUEST['order'])) . '" />';
        }
        if (!empty($_REQUEST['page'])) {
            echo '<input type="hidden" name="page" value="' . esc_attr(sanitize_text_field($_REQUEST['page'])) . '" />';
        }

        $custom_filters = ['terms_filter_post_type', 'terms_filter_taxonomy', 'taxonomy_type'];

        foreach ($custom_filters as  $custom_filter) {
            $filter_value = !empty($_REQUEST[$custom_filter]) ? sanitize_text_field($_REQUEST[$custom_filter]) : '';
            echo '<input type="hidden" name="' . esc_attr($custom_filter) . '" value="' . esc_attr($filter_value) . '" />';
        }
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($text); ?>:</label>
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s" value="<?php _admin_search_query(); ?>" />
            <?php submit_button($text, '', '', false, ['id' => 'taxopress-terms-search-submit']); ?>
        </p>
    <?php
    }

    /**
     * Sets up the items (roles) to list.
     */
    public function prepare_items()
    {

        $this->_column_headers = $this->get_column_info();
        $this->process_bulk_action();

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = $this->get_items_per_page('st_terms_per_page', 20);

        /**
         * Fetch the data
         */
        $data = $this->get_st_Terms();

        /**
         * Pagination.
         */
        $current_page = $this->get_pagenum();
        $total_items  = $this->record_count();

        /**
         * Now we can add the data to the items property, where it can be used by the rest of the class.
         */
        $this->items = $data;

        /**
         * We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args([
            'total_items' => $total_items,                      //calculate the total number of items
            'per_page'    => $per_page,                         //determine how many items to show on a page
            'total_pages' => ceil($total_items / $per_page)   //calculate the total number of pages
        ]);
    }

    /**
     * Generates and display row actions links for the list table.
     *
     * @param object $item The item being acted upon.
     * @param string $column_name Current column name.
     * @param string $primary Primary column name.
     *
     * @return string The row actions HTML, or an empty string if the current column is the primary column.
     */
    protected function handle_row_actions($item, $column_name, $primary)
    {
        $taxonomy = get_taxonomy($item->taxonomy);

        //Build row actions
        $actions = [];

        if (current_user_can('edit_term', $item->term_id)) {
            $actions['edit'] = sprintf(
                '<a href="%s">%s</a>',
                add_query_arg(
                    [
                        'taxonomy'      => $item->taxonomy,
                        'tag_ID'        => $item->term_id,
                        'post_type'     => isset($taxonomy->object_type[0]) ? $taxonomy->object_type[0] : 'post',
                    ],
                    admin_url('term.php')
                ),
                esc_html__('Edit', 'simple-tags')
            );
        }

        // Only add other actions if not viewing via taxopress_terms_taxonomy
        if (empty($_REQUEST['taxopress_terms_taxonomy'])) {
            if (current_user_can('edit_term', $item->term_id)) {
                $actions['inline hide-if-no-js'] = sprintf(
                    '<button type="button" class="button-link editinline" aria-label="%s" aria-expanded="false" data-taxonomy="' . $taxonomy->name . '" data-term-id="' . $item->term_id . '">%s</button>',
                    /* translators: %s: Taxonomy term name. */
                    esc_attr(sprintf(esc_html__('Quick edit &#8220;%s&#8221; inline', 'simple-tags'), $item->name)),
                    esc_html__('Quick&nbsp;Edit', 'simple-tags')
                );
            }

            if (current_user_can('edit_term', $item->term_id)) {
                $actions['remove_posts'] = sprintf(
                    '<a href="%s">%s</a>',
                    add_query_arg(
                        [
                            'page'                   => 'st_terms',
                            'action'                 => 'taxopress-remove-from-posts',
                            'taxopress_terms'        => esc_attr($item->term_id),
                            '_wpnonce'               => wp_create_nonce('terms-action-request-nonce')
                        ],
                        admin_url('admin.php')
                    ),
                    esc_html__('Remove From All Posts', 'simple-tags')
                );
            }

            if (current_user_can('delete_term', $item->term_id)) {
                $actions['delete'] = sprintf(
                    '<a href="%s" class="delete-terms">%s</a>',
                    add_query_arg(
                        [
                            'page'                   => 'st_terms',
                            'action'                 => 'taxopress-delete-terms',
                            'taxopress_terms'        => esc_attr($item->term_id),
                            '_wpnonce'               => wp_create_nonce('terms-action-request-nonce')
                        ],
                        admin_url('admin.php')
                    ),
                    esc_html__('Delete', 'simple-tags')
                );
            }

            if (is_taxonomy_viewable($item->taxonomy)) {
                $actions['view'] = sprintf(
                    '<a href="%s">%s</a>',
                    get_term_link($item->term_id),
                    esc_html__('View', 'simple-tags')
                );
            }

            $actions['copy_term'] = sprintf(
                '<a href="%s">%s</a>',
                add_query_arg(
                    [
                        'page'                   => 'st_terms',
                        'action'                 => 'taxopress-copy-term',
                        'taxopress_terms'        => esc_attr($item->term_id),
                        '_wpnonce'               => wp_create_nonce('terms-action-request-nonce')
                    ],
                    admin_url('admin.php')
                ),
                esc_html__('Copy', 'simple-tags')
            );
            $actions = apply_filters('taxopress_terms_row_actions', $actions, $item);
        }
        return $column_name === $primary ? $this->row_actions($actions, false) : '';
    }

    /**
     * Method for synonyms column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_synonyms($item)
    {
        $term_synonyms = taxopress_get_term_synonyms($item->term_id);
        if (!empty($term_synonyms)) {
            return join(', ', $term_synonyms);
        } else {
            return '-';
        }
    }

    /**
     * Method for linked_terms column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_linked_terms($item)
    {
        $term_linked_terms = taxopress_get_linked_terms($item->term_id);
        if (!empty($term_linked_terms)) {
            $term_linked_term_names = [];
            foreach ($term_linked_terms as $term_linked_term) {
                $linked_term_data = taxopress_get_linked_term_data($term_linked_term, $item->term_id);
                $term_linked_term_names[] = $linked_term_data->term_name . ' ('. $linked_term_data->term_taxonomy .')';
            }
            return join(', ', $term_linked_term_names);
        } else {
            return '-';
        }
    }

    /**
     * Method for name column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_name($item)
    {
        $taxonomy = get_taxonomy($item->taxonomy);

        // Add dashes for hierarchy
        $depth = isset($item->taxopress_depth) ? (int)$item->taxopress_depth : 0;
        $dash = $depth > 0 ? str_repeat('&mdash; ', $depth) : '';

        $title = sprintf(
            '<a href="%1$s"><strong><span class="row-title">%2$s%3$s</span></strong></a>',
            add_query_arg(
                [
                    'taxonomy' => $item->taxonomy,
                    'tag_ID' => $item->term_id,
                    'post_type' => isset($taxonomy->object_type[0]) ? $taxonomy->object_type[0] : 'post',
                ],
                admin_url('term.php')
            ),
            $dash,
            esc_html($item->name)
        );

        $title .= ' <span class="taxopress-term-spinner" style="display:none;vertical-align:middle;"><span class="spinner is-active"></span></span>';

        //for inline edit
        $qe_data = get_term($item->term_id, $item->taxonomy, OBJECT, 'edit');

        $title .= '<div class="hidden" id="inline_' . $qe_data->term_id . '">';
        $title .= '<div class="taxonomy">' . $item->taxonomy . '</div>';
        $title .= '<div class="name">' . $qe_data->name . '</div>';

        $title .= '<div class="slug">' . apply_filters('editable_slug', $qe_data->slug, $qe_data) . '</div>';
        $title .= '<div class="parent">' . $qe_data->parent . '</div>
        </div>';

        return $title;
    }

    /**
     * The action column
     *
     * @param $item
     *
     * @return string
     */
    protected function column_slug($item)
    {
        return !empty($item->slug) ? $item->slug : '&mdash;';
    }

    /**
     * The action column
     *
     * @param $item
     *
     * @return string
     */
    protected function column_posttypes($item)
    {
        $posttype = '';
        $sn = 0;
        $taxonomy = get_taxonomy($item->taxonomy);
        foreach ($taxonomy->object_type as $objecttype) {
            $sn++;
            $post_type_object = get_post_type_object($objecttype);
            if (is_object($post_type_object)) {
                $posttype .= $post_type_object->label;
                if ($sn < count($taxonomy->object_type)) {
                    $posttype .= ', ';
                }
            }
        }

        return $posttype;
    }

    /**
     * The action column
     *
     * @param $item
     *
     * @return string
     */
    protected function column_count($item)
    {
        $term_counts = $this->count_posts_by_term($item->term_id, $item->taxonomy);

        return sprintf(
            '<a href="%s" class="">%s</a>',
            add_query_arg(
                [
                    'page' => 'st_posts',
                    'posts_term_filter' => (int) $item->term_id,
                ],
                admin_url('admin.php')
            ),
            number_format_i18n($term_counts)
        );
    }

    protected function count_posts_by_term($term_id, $taxonomy) {
        
        $args = array(
            'post_type' => array_keys(get_post_types(array('public' => true), 'names')),
            'post_status' => 'any',
            'posts_per_page' => 1,
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => $taxonomy,
                    'field' => 'id',
                    'terms' => $term_id,
                ),
            ),
        );
    
        $term_count = new WP_Query($args);

        if ($term_count->have_posts()) {
            return $term_count->found_posts;
        } else {
            return 0;
        }
    }
    

    /**
     * The action column
     *
     * @param $item
     *
     * @return string
     */
    protected function column_description($item)
    {

        return term_description($item->term_id);
    }

    /**
     * Method for taxonomy column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_taxonomy($item)
    {
        $taxonomy = get_taxonomy($item->taxonomy);

        if ($taxonomy) {
            $return = sprintf(
                '<a href="%1$s">%2$s</a>',
                add_query_arg(
                    [
                        'page' => 'st_taxonomies',
                        'add' => 'taxonomy',
                        'action' => 'edit',
                        'taxopress_taxonomy' => $taxonomy->name,
                    ],
                    taxopress_admin_url('admin.php')
                ),
                esc_html($taxonomy->labels->name)
            );
        } else {
            $return = '&mdash;';
        }

        return $return;
    }

    /**
     * Outputs the hidden row displayed when inline editing
     *
     * @since 3.1.0
     */
    public function inline_edit()
    {
    ?>

        <form method="get">
            <table style="display: none">
                <tbody id="inlineedit">

                    <tr id="inline-edit" class="inline-edit-row" style="display: none">
                        <td colspan="<?php echo esc_attr($this->get_column_count()); ?>" class="colspanchange">

                            <fieldset>
                                <legend class="inline-edit-legend"><?php esc_html_e('Quick Edit', 'simple-tags'); ?></legend>
                                <div class="inline-edit-col">
                                    <label>
                                        <span class="title"><?php _ex('Name', 'term name', 'simple-tags'); ?></span>
                                        <span class="input-text-wrap"><input type="text" name="name" class="ptitle" value="" /></span>
                                    </label>

                                    <label>
                                        <span class="title"><?php esc_html_e('Slug', 'simple-tags'); ?></span>
                                        <span class="input-text-wrap"><input type="text" name="slug" class="ptitle" value="" /></span>
                                    </label>
                                    <label>
                                        <span class="taxonomy"><?php _ex('Taxonomy', 'term name', 'simple-tags'); ?></span>

                                        <?php $taxonomies = get_all_taxopress_taxonomies(); ?>
                                        <select class="input-text-wrap edit-tax edit_taxonomy" name="edit_taxonomy">
                                            <?php
                                            foreach ($taxonomies as $taxonomy) {
                                                echo '<option value="' . esc_attr($taxonomy->name) . '">' . esc_html($taxonomy->labels->name) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </label>
                                </div>
                            </fieldset>

                            <div class="inline-edit-save submit">
                                <button type="button" class="cancel button alignleft"><?php esc_html_e('Cancel', 'simple-tags'); ?></button>
                                <button type="button" class="taxopress-save button button-primary alignright"><?php esc_html_e('Update', 'simple-tags'); ?></button>
                                <span class="spinner"></span>

                                <?php wp_nonce_field('taxinlineeditnonce', '_inline_edit', false); ?>
                                <br class="clear" />

                                <div class="notice notice-error notice-alt inline hidden taxopress-notice">
                                    <p class="error"></p>
                                </div>
                            </div>

                        </td>
                    </tr>

                </tbody>
            </table>
        </form>
<?php
    }
}
