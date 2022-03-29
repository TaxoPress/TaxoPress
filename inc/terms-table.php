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
            'singular' => esc_html__('Term', 'simple-tags'), //singular name of the listed records
            'plural'   => esc_html__('Terms', 'simple-tags'), //plural name of the listed records
            'ajax'     => false //does this table support ajax?
        ]);

    }

    public function get_all_terms($count = false){
        
        $taxonomies = array_keys(get_all_taxopress_taxonomies());
        $search = (!empty($_REQUEST['s'])) ? sanitize_text_field($_REQUEST['s']) : '';

        $orderby        = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'ID';
        $order          = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'desc';
        $items_per_page = $this->get_items_per_page('st_Terms_per_page', 20);
        $page           = $this->get_pagenum();
        $offset         = ($page - 1) * $items_per_page;


        $terms_attr = array (
            'taxonomy' => $taxonomies,
            'orderby' => $orderby,
            'order' => $order,
            'search' => $search,
            'offset' => $offset,
            'hide_empty' => false,
            'include' => 'all',
            'number' => $items_per_page,
            'pad_counts' => true,
            'update_term_meta_cache' => true,
        );

        $terms = get_terms($terms_attr);

        if(empty($terms) || is_wp_error($terms)){
            return [];
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
    public static function record_count()
    {
        $taxonomies = array_keys(get_all_taxopress_taxonomies());
        $search = (!empty($_REQUEST['s'])) ? sanitize_text_field($_REQUEST['s']) : '';
        
        return wp_count_terms(['hide_empty' => false, 'taxonomy' => $taxonomies, 'search' => $search]);
    }

    /**
     * Show single row item
     *
     * @param array $item
     */
    public function single_row($item)
    {
        $class = ['st-terms-tr'];
        $id    = 'st-terms-' . md5($item->term_id);
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
        $columns = [
			'cb'      => '<input type="checkbox" />',
            'name'     => esc_html__('Title', 'simple-tags'),
            'slug'     => esc_html__('Slug', 'simple-tags'),
            'taxonomy'  => esc_html__('Taxonomy', 'simple-tags'),
            'posttypes'  => esc_html__('Post Types', 'simple-tags'),
            'count'  => esc_html__('Count', 'simple-tags')
        ];

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
            'slug'      => ['taxonomy', true],
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
	function column_cb( $item ) {
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
            'taxopress-terms-delete-terms' => esc_html__('Delete', 'simple-tags')
        ];

        return $actions;
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

        if($this->current_action() === 'taxopress-terms-delete-terms'){
            $taxopress_terms = array_map('sanitize_text_field', (array)$_REQUEST['taxopress_terms']);
            if (!empty($taxopress_terms)) {
                foreach($taxopress_terms as $taxopress_term){
                    $term = get_term( $taxopress_term );
                    wp_delete_term( $term->term_id, $term->taxonomy );
                }
                if(count($taxopress_terms) > 1){
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo taxopress_admin_notices_helper(esc_html__('Terms deleted successfully.', 'simple-tags'), false);
                }else{
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo taxopress_admin_notices_helper(esc_html__('Term deleted successfully.', 'simple-tags'), false);
                }
            }
        }

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
        esc_html_e('No item avaliable.', 'simple-tags');
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
        if (empty($_REQUEST['s']) && !$this->has_items()) {
            return;
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
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($text); ?>:</label>
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s"
                   value="<?php _admin_search_query(); ?>"/>
            <?php submit_button($text, '', '', false, ['id' => 'search-submit']); ?>
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
        $per_page = $this->get_items_per_page('st_Terms_per_page', 20);

        /**
         * Fetch the data
         */
        $data = $this->get_st_Terms();

        /**
         * Pagination.
         */
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();

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
        $actions = [
            'edit'   => sprintf(
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
            ),
            'delete' => sprintf(
                '<a href="%s" class="delete-terms">%s</a>',
                add_query_arg([
                    'page'                   => 'st_terms',
                    'action'                 => 'taxopress-delete-terms',
                    'taxopress_terms'        => esc_attr($item->term_id),
                    '_wpnonce'               => wp_create_nonce('terms-action-request-nonce')
                ],
                    admin_url('admin.php')),
                esc_html__('Delete', 'simple-tags')
            ),
        ];

        return $column_name === $primary ? $this->row_actions($actions, false) : '';
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
        
        $title = sprintf(
            '<a href="%1$s"><strong><span class="row-title">%2$s</span></strong></a>',
            add_query_arg(
                [
                    'taxonomy'      => $item->taxonomy,
                    'tag_ID'        => $item->term_id,
                    'post_type'     => isset($taxonomy->object_type[0]) ? $taxonomy->object_type[0] : 'post',
                ],
                admin_url('term.php')
            ),
            esc_html($item->name)
        );

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
        $sn       = 0;
        $taxonomy = get_taxonomy($item->taxonomy);
        foreach ($taxonomy->object_type as $objecttype) {
            $sn++;
            $post_type_object = get_post_type_object($objecttype);
            if(is_object($post_type_object)){
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

        $taxonomy = get_taxonomy($item->taxonomy);
        
        if($taxonomy->query_var){
            return sprintf('<a href="%s" class="">%s</a>', 
            add_query_arg(
                [
                    $taxonomy->query_var      => esc_attr($item->slug),
                    'post_type'     => isset($taxonomy->object_type[0]) ? $taxonomy->object_type[0] : 'post',
                ],
                admin_url('edit.php')
            ),
                number_format_i18n($item->count));
        }else{
            return number_format_i18n($item->count);
        }

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

        if($taxonomy){
            $return = $taxonomy->labels->name;
        }else{
            $return = '&mdash;';
        }

        return $return;
    }


}
