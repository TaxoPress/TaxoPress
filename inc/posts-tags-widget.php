<?php

/**
 * TaxoPress widget class
 *
 */
class SimpleTags_PostTags_Widget extends WP_Widget {
	/**
	 * Constructor widget
	 *
	 * @return void
	 * @author Olatechpro
	 */
	public function __construct() {
		parent::__construct( 'simpletags-posttags', __( 'Terms for Current Post (TaxoPress)', 'simple-tags' ),
			array(
				'classname'   => 'widget-simpletags-posttags',
				'description' => __( 'Taxopress Terms for Current Post Shortcode', 'simple-tags' )
			)
		);
	}

	/**
	 * Default settings for widget
	 *
	 * @return array
	 * @author Olatechpro
	 */
	public static function get_fields() {
		return array(
			'posttags_id'       => 0,
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

		// Set values and clean it
		foreach ( (array) self::get_fields() as $field => $field_value ) {
            ${$field} = isset($instance[$field]) ? trim($instance[$field]) : '';
		}//$posttags_id;

		echo $before_widget;
		echo do_shortcode('[taxopress_postterms id="'.$posttags_id.'"]');
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

        $posttags_data = taxopress_get_posttags_data();
        if(count($posttags_data) > 0){
            $shortcode_page = sprintf(
                '<a href="%s">%s</a>',
                add_query_arg(
                    [
                        'page'               => 'st_post_tags',
                    ],
                    admin_url('admin.php')
                ),
                __('this page.', 'simple-tags')
            );

            echo '<p>'.__( 'Terms for Current Post are added on ', 'simple-tags' );
            echo $shortcode_page;
            echo '</p>'

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'posttags_id' ); ?>">
            <?php _e( 'Select widget terms for current post.', 'simple-tags' ); ?>
				<select id="<?php echo $this->get_field_id( 'posttags_id' ); ?>"
				        name="<?php echo $this->get_field_name( 'posttags_id' ); ?>">
                        <?php foreach($posttags_data as $key => $value ){   ?>
					            <option <?php selected( $instance['posttags_id'], $key ); ?>
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
                        'page'               => 'st_post_tags',
                    ],
                    admin_url('admin.php')
                ),
                __('Here', 'simple-tags')
            );

            echo '<br />'.__( 'No terms for current post shortcode available. Add new shortcode ', 'simple-tags' );
            echo $shortcode_page;
            echo '<br /><br />';
        }
	}
}