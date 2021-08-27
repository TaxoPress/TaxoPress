<?php

/**
 * TaxoPress widget class
 *
 */
class SimpleTags_Shortcode_Widget extends WP_Widget {
	/**
	 * Constructor widget
	 *
	 * @return void
	 * @author Olatechpro
	 */
	public function __construct() {
		parent::__construct( 'simpletags-shortcode', __( 'Terms Display (TaxoPress Shortcode)', 'simple-tags' ),
			array(
				'classname'   => 'widget-simpletags-shortcode',
				'description' => __( 'Taxopress Terms Display Shortcode', 'simple-tags' )
			)
		);
	}

	/**
	 * Check if taxonomy exist and return it, otherwise return default post tags.
	 *
	 * @param array $instance
	 *
	 * @return string
	 * @author Olatechpro
	 */
	public static function _get_current_taxonomy( $instance ) {
		if ( ! empty( $instance['taxonomy'] ) && taxonomy_exists( $instance['taxonomy'] ) ) {
			return $instance['taxonomy'];
		}

		return 'post_tag';
	}

	/**
	 * Default settings for widget
	 *
	 * @return array
	 * @author Olatechpro
	 */
	public static function get_fields() {
		return array(
			'tagcloud_id'       => 0,
		);
	}

	/**
	 * Method for theme render
	 *
	 * @param array $args
	 * @param array $instance
	 *
	 * @return void
	 * @author Olatechpro
	 */
	public function widget( $args, $instance ) {
		extract( $args );

		$current_taxonomy = self::_get_current_taxonomy( $instance );


		// Set values and clean it
		foreach ( (array) self::get_fields() as $field => $field_value ) {
            ${$field} = isset($instance[$field]) ? trim($instance[$field]) : '';
		}//$tagcloud_id;

		echo $before_widget;
		echo do_shortcode('[taxopress_termsdisplay id="'.$tagcloud_id.'"]');
		echo $after_widget;
	}

	/**
	 * Update widget settings
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 * @author Olatechpro
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		foreach ( (array) self::get_fields() as $field => $field_value ) {
			$instance[ $field ] = $new_instance[ $field ];
		}

		return $instance;
	}

	/**
	 * Admin form for widgets
	 *
	 * @param array $instance
	 *
	 * @return void
	 * @author Olatechpro
	 */
	public function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, self::get_fields() );

        $tagcloud_data = taxopress_get_tagcloud_data();
        if(count($tagcloud_data) > 0){
            $shortcode_page = sprintf(
                '<a href="%s">%s</a>',
                add_query_arg(
                    [
                        'page'               => 'st_terms_display',
                    ],
                    admin_url('admin.php')
                ),
                __('this page.', 'simple-tags')
            );

            echo '<p>'.__( 'Terms Display are added on ', 'simple-tags' );
            echo $shortcode_page;
            echo '</p>'

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'tagcloud_id' ); ?>">
            <?php _e( 'Select widget terms display.', 'simple-tags' ); ?>
				<select id="<?php echo $this->get_field_id( 'tagcloud_id' ); ?>"
				        name="<?php echo $this->get_field_name( 'tagcloud_id' ); ?>">
                        <?php foreach($tagcloud_data as $key => $value ){   ?>
					            <option <?php selected( $instance['tagcloud_id'], $key ); ?>
                                value="<?php echo $key; ?>"><?php echo $value['title']; ?></option>
                        <?php } ?>
				</select>
			</label>
		</p>

		<?php
        }else{
            $shortcode_page = sprintf(
                '<a href="%s">%s</a>',
                add_query_arg(
                    [
                        'page'               => 'st_terms_display',
                    ],
                    admin_url('admin.php')
                ),
                __('Here', 'simple-tags')
            );

            echo '<br />'.__( 'No terms display shortcode available. Add new shortcode ', 'simple-tags' );
            echo $shortcode_page;
            echo '<br /><br />';
        }
	}
}