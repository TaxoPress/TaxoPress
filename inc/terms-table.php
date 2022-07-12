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
            'ajax'     => true //does this table support ajax?
        ]);

    }

    public function get_all_terms($count = false){

        $taxonomies = array_keys(get_all_taxopress_taxonomies_request());
        
        $search = (!empty($_REQUEST['s'])) ? sanitize_text_field($_REQUEST['s']) : '';

        $orderby        = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'ID';
        $order          = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'desc';
        $items_per_page = $this->get_items_per_page('st_Terms_per_page', 20);
        $page           = $this->get_pagenum();
        $offset         = ($page - 1) * $items_per_page;

        $selected_post_type = (!empty($_REQUEST['terms_filter_post_type'])) ? [sanitize_text_field($_REQUEST['terms_filter_post_type'])] : '';
        $selected_taxonomy = (!empty($_REQUEST['terms_filter_taxonomy'])) ? sanitize_text_field($_REQUEST['terms_filter_taxonomy']) : '';
        if(!empty($selected_taxonomy)){
            $taxonomies = [$selected_taxonomy];
        }

        $terms_attr = array (
            'taxonomy' => $taxonomies,
            'post_types' => $selected_post_type,
            'orderby' => $orderby,
            'order' => $order,
            'search' => $search,
            'hide_empty' => false,
            'include' => 'all',
            'pad_counts' => true,
            'update_term_meta_cache' => true,
        );
        if($count){
            $terms_attr['number'] = 0;
        } else {
            $terms_attr['offset'] = $offset;
            $terms_attr['number'] = $items_per_page;
        }

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
     * Add custom filter to tablenav
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {

		if ( 'top' === $which ) {

            $post_types = get_post_types(['public' => true], 'objects');

            $taxonomies = get_all_taxopress_public_taxonomies();

            $selected_post_type = (!empty($_REQUEST['terms_filter_post_type'])) ? sanitize_text_field($_REQUEST['terms_filter_post_type']) : '';
            $selected_taxonomy = (!empty($_REQUEST['terms_filter_taxonomy'])) ? sanitize_text_field($_REQUEST['terms_filter_taxonomy']) : '';

                $selected_option = 'public';
                if ( isset($_GET['taxonomy_type']) && $_GET['taxonomy_type'] === 'all') {
                    $selected_option = 'all';
                }elseif ( isset($_GET['taxonomy_type']) && $_GET['taxonomy_type'] === 'private') {
                    $selected_option = 'private';
                }
             ?>


            <div class="alignleft actions autoterms-terms-table-filter">

                <select class="auto-terms-terms-filter-select"  name="terms_filter_select_post_type" id="terms_filter_select_post_type">
                    <option value=""><?php esc_html_e('Post type', 'simple-tags'); ?></option>
                    <?php
                    foreach ( $post_types as $post_type ) {
                        echo '<option value="'. esc_attr($post_type->name) .'" '.selected($selected_post_type, $post_type->name, false).'>'. esc_html($post_type->label) .'</option>';
                    }
                    ?>
                </select>

                <select class="auto-terms-terms-filter-select"  name="terms_filter_select_taxonomy" id="terms_filter_select_taxonomy">
                    <option value=""><?php esc_html_e('Taxonomy', 'simple-tags'); ?></option>
                    <?php
                    foreach ( $taxonomies as $taxonomy ) {
                        echo '<option value="'. esc_attr($taxonomy->name) .'" '.selected($selected_taxonomy, $taxonomy->name, false).'>'. esc_html($taxonomy->labels->name) .'</option>';
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
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s"
                   value="<?php _admin_search_query(); ?>"/>
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
        $per_page = $this->get_items_per_page('st_Terms_per_page', 20);

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

		if ( current_user_can( 'edit_term', $item->term_id ) ) {
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
			$actions['inline hide-if-no-js'] = sprintf(
				'<button type="button" class="button-link editinline" aria-label="%s" aria-expanded="false">%s</button>',
				/* translators: %s: Taxonomy term name. */
				esc_attr( sprintf( esc_html__( 'Quick edit &#8220;%s&#8221; inline', 'simple-tags'), $item->name ) ),
				esc_html__('Quick&nbsp;Edit', 'simple-tags')
			);
		}

		if ( current_user_can( 'delete_term', $item->term_id ) ) {
			$actions['delete'] = sprintf(
                '<a href="%s" class="delete-terms">%s</a>',
                add_query_arg([
                    'page'                   => 'st_terms',
                    'action'                 => 'taxopress-delete-terms',
                    'taxopress_terms'        => esc_attr($item->term_id),
                    '_wpnonce'               => wp_create_nonce('terms-action-request-nonce')
                ],
                    admin_url('admin.php')),
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

        //for inline edit
		$qe_data = get_term( $item->term_id, $item->taxonomy, OBJECT, 'edit');

		$title .= '<div class="hidden" id="inline_' . $qe_data->term_id . '">';
		$title .= '<div class="taxonomy">'. $item->taxonomy .'</div>';
		$title .= '<div class="name">' . $qe_data->name . '</div>';

		$title .= '<div class="slug">' . apply_filters( 'editable_slug', $qe_data->slug, $qe_data ) . '</div>';
		$title .= '<div class="parent">' . $qe_data->parent . '</div></div>';

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
            $return = sprintf(
                '<a href="%1$s">%2$s</a>',
                add_query_arg(
                    [
                        'page'               => 'st_taxonomies',
                        'add'                => 'taxonomy',
                        'action'             => 'edit',
                        'taxopress_taxonomy' => $taxonomy->name,
                    ],
                    taxopress_admin_url('admin.php')
                ),
                esc_html($taxonomy->labels->name)
            );
        }else{
            $return = '&mdash;';
        }

        return $return;
    }

	/**
	 * Outputs the hidden row displayed when inline editing
	 *
	 * @since 3.1.0
	 */
	public function inline_edit() {
		?>

		<form method="get">
		<table style="display: none"><tbody id="inlineedit">

			<tr id="inline-edit" class="inline-edit-row" style="display: none">
			<td colspan="<?php echo esc_attr($this->get_column_count()); ?>" class="colspanchange">

			<fieldset>
				<legend class="inline-edit-legend"><?php esc_html_e('Quick Edit', 'simple-tags'); ?></legend>
				<div class="inline-edit-col">
				<label>
					<span class="title"><?php _ex( 'Name', 'term name', 'simple-tags'); ?></span>
					<span class="input-text-wrap"><input type="text" name="name" class="ptitle" value="" /></span>
				</label>

				<?php if ( ! global_terms_enabled() ) : ?>
					<label>
						<span class="title"><?php esc_html_e('Slug', 'simple-tags'); ?></span>
						<span class="input-text-wrap"><input type="text" name="slug" class="ptitle" value="" /></span>
					</label>
				<?php endif; ?>
				<label>
					<span class="taxonomy"><?php _ex( 'Taxonomy', 'term name', 'simple-tags'); ?></span>

                    <?php $taxonomies = get_all_taxopress_taxonomies(); ?>
                    <select class="input-text-wrap edit-tax edit_taxonomy"  name="edit_taxonomy">
                        <?php
                        foreach ( $taxonomies as $taxonomy ) {
                            echo '<option value="'. esc_attr($taxonomy->name) .'">'. esc_html($taxonomy->labels->name) .'</option>';
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

				<?php wp_nonce_field( 'taxinlineeditnonce', '_inline_edit', false ); ?>
				<br class="clear" />

				<div class="notice notice-error notice-alt inline hidden">
					<p class="error"></p>
				</div>
			</div>

			</td></tr>

		</tbody></table>
		</form>
		<?php
	}


}
