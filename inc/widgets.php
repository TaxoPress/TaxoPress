<?php
/**
 * Simple Tags widget class
 *
 */
class SimpleTags_Widget extends WP_Widget {

	var $fields = array( 'title', 'max', 'selection', 'order', 'smini', 'smax', 'unit', 'format', 'color', 'cmini', 'cmax', 'xformat' );
	
	function SimpleTags_Widget() {
		$widget_ops = array( 'classname' => 'widget_simpletags', 'description' => __( "Your most used tags in cloud format with dynamic color and many options", 'simpletags' ) );
		$this->WP_Widget('simpletags', __('ST - Tag Cloud', 'simpletags'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract( $args );
		
		// Set values and clean it
		foreach ( (array) $this->fields as $field ) {
			${$field} = trim( $instance[$field] );
		}

		// Use Widgets title and no ST title !!
		$args = 'title=';

		// Selection
		if ( !empty($selection) ) {
			$args .= '&cloud_selection='.$selection;
		} else {
			$args .= '&cloud_selection=count-desc';
		}

		// Order
		if ( !empty($order) ) {
			$args .= '&cloud_sort='.$order;
		} else {
			$args .= '&cloud_sort=random';
		}

		// Max tags
		if ( $max != 0 ) {
			$args .= '&number='.$max;
		}

		// Size Mini
		if ( $smini != 0 ) {
			$args .= '&smallest='.$smini;
		}

		// Size Maxi
		if ( $smax != 0 ) {
			$args .= '&largest='.$smax;
		}

		// Unit
		if ( !empty($unit) ) {
			$args .= '&unit='.$unit;
		}

		// Format
		if ( !empty($format) ) {
			$args .= '&format='.$format;
		}

		// Use color ?
		if ( $color == 0 ) {
			$args .= '&color=false';
		}

		// Color mini
		if ( !empty($cmini) ) {
			$args .= '&mincolor='.$cmini;
		}

		// Color Max
		if ( !empty($cmax) ) {
			$args .= '&maxcolor='.$cmax;
		}

		// Xformat
		if ( !empty($xformat) ) {
			$args .= '&xformat='.$xformat;
		}
		
		$title = apply_filters( 'widget_title', $title );
		
		echo $before_widget;
		echo $before_title . $title . $after_title;
			st_tag_cloud($args);
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		foreach( (array) $this->fields as $field )
			$instance[$field] = strip_tags($new_instance[$field]);
			
		return $instance;
	}

	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, $fields );
		foreach ( (array) $this->fields as $field ) {
			${$field} = esc_attr( $instance[$field] );
		}
		?>
		<p><?php _e('Empty field will use default value.', 'simpletags'); ?></p>

		<p><label for="<?php echo $this->get_field_id('title'); ?>">
			<?php _e('Title:', 'simpletags'); ?>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_id('title'); ?>" value="<?php echo $title; ?>" />
		</label></p>

		<p><label for="<?php echo $this->get_field_id('max'); ?>">
			<?php _e('Max tags to display: (default: 45)', 'simpletags'); ?>
			<input class="widefat" size="20" type="text" id="<?php echo $this->get_field_id('max'); ?>" name="<?php echo $this->get_field_id('max'); ?>" value="<?php echo $max; ?>" />
		</label></p>

		<p><label for="<?php echo $this->get_field_id('selection'); ?>">
			<?php _e('Tags selection:', 'simpletags'); ?>
			<select id="<?php echo $this->get_field_id('selection'); ?>" name="<?php echo $this->get_field_id('selection'); ?>">
				<option <?php if ( $selection == 'name-asc' ) echo 'selected="selected"'; ?> value="name-asc"><?php _e('Alphabetical', 'simpletags'); ?></option>
				<option <?php if ( $selection == 'name-desc' ) echo 'selected="selected"'; ?> value="name-desc"><?php _e('Inverse Alphabetical', 'simpletags'); ?></option>
				<option <?php if ( $selection == 'count-desc' ) echo 'selected="selected"'; ?> value="count-desc"><?php _e('Most popular (default)', 'simpletags'); ?></option>
				<option <?php if ( $selection == 'count-asc' ) echo 'selected="selected"'; ?> value="count-asc"><?php _e('Least used', 'simpletags'); ?></option>
				<option <?php if ( $selection == 'random' ) echo 'selected="selected"'; ?> value="random"><?php _e('Random', 'simpletags'); ?></option>
			</select>
		</label></p>

		<p><label for="<?php echo $this->get_field_id('order'); ?>">
			<?php _e('Order tags display:', 'simpletags'); ?>
			<select id="<?php echo $this->get_field_id('order'); ?>" name="<?php echo $this->get_field_id('order'); ?>">
				<option <?php if ( $order == 'name-asc' ) echo 'selected="selected"'; ?> value="name-asc"><?php _e('Alphabetical', 'simpletags'); ?></option>
				<option <?php if ( $order == 'name-desc' ) echo 'selected="selected"'; ?> value="name-desc"><?php _e('Inverse Alphabetical', 'simpletags'); ?></option>
				<option <?php if ( $order == 'count-desc' ) echo 'selected="selected"'; ?> value="count-desc"><?php _e('Most popular', 'simpletags'); ?></option>
				<option <?php if ( $order == 'count-asc' ) echo 'selected="selected"'; ?> value="count-asc"><?php _e('Least used', 'simpletags'); ?></option>
				<option <?php if ( $order == 'random' ) echo 'selected="selected"'; ?> value="random"><?php _e('Random (default)', 'simpletags'); ?></option>
			</select>
		</label></p>

		<p><label for="<?php echo $this->get_field_id('smini'); ?>">
			<?php _e('Font size mini: (default: 8)', 'simpletags'); ?>
			<input class="widefat" size="20"  type="text" id="<?php echo $this->get_field_id('smini'); ?>" name="<?php echo $this->get_field_id('smini'); ?>" value="<?php echo $smini; ?>" />
		</label></p>

		<p><label for="<?php echo $this->get_field_id('smax'); ?>">
			<?php _e('Font size max: (default: 22)', 'simpletags'); ?>
			<input class="widefat" size="20" type="text" id="<?php echo $this->get_field_id('smax'); ?>" name="<?php echo $this->get_field_id('smax'); ?>" value="<?php echo $smax; ?>" />
		</label></p>

		<p><label for="<?php echo $this->get_field_id('unit'); ?>">
			<?php _e('Unit font size:', 'simpletags'); ?>
			<select id="<?php echo $this->get_field_id('unit'); ?>" name="<?php echo $this->get_field_id('unit'); ?>">
				<option <?php if ( $unit == 'pt' ) echo 'selected="selected"'; ?> value="pt"><?php _e('Point (default)', 'simpletags'); ?></option>
				<option <?php if ( $unit == 'px' ) echo 'selected="selected"'; ?> value="px"><?php _e('Pixel', 'simpletags'); ?></option>
				<option <?php if ( $unit == 'em' ) echo 'selected="selected"'; ?> value="em"><?php _e('Em', 'simpletags'); ?></option>
				<option <?php if ( $unit == '%' ) echo 'selected="selected"'; ?> value="%"><?php _e('Pourcent', 'simpletags'); ?></option>
			</select>
		</label></p>

		<p><label for="<?php echo $this->get_field_id('format'); ?>">
			<?php _e('Format:', 'simpletags'); ?>
			<select id="<?php echo $this->get_field_id('format'); ?>" name="<?php echo $this->get_field_id('format'); ?>">
				<option <?php if ( $format == 'flat' ) echo 'selected="selected"'; ?> value="flat"><?php _e('Flat (default)', 'simpletags'); ?></option>
				<option <?php if ( $format == 'list' ) echo 'selected="selected"'; ?> value="list"><?php _e('List (UL/LI)', 'simpletags'); ?></option>
			</select>
		</label></p>

		<p><label for="<?php echo $this->get_field_id('color'); ?>">
			<input class="widefat" type="checkbox" id="<?php echo $this->get_field_id('color'); ?>" name="<?php echo $this->get_field_id('color'); ?>" <?php if ( $color == 1 ) echo 'checked="checked"'; ?> value="1" />
			<?php _e('Use auto color cloud:', 'simpletags'); ?>
		</label></p>

		<p><label for="<?php echo $this->get_field_id('cmini'); ?>">
			<?php _e('Font color mini: (default: #CCCCCC)', 'simpletags'); ?>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id('cmini'); ?>" name="<?php echo $this->get_field_id('cmini'); ?>" value="<?php echo $cmini; ?>" />
		</label></p>

		<p><label for="<?php echo $this->get_field_id('cmax'); ?>">
			<?php _e('Font color max: (default: #000000)', 'simpletags'); ?>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id('cmax'); ?>" name="<?php echo $this->get_field_id('cmax'); ?>" value="<?php echo $cmax; ?>" />
		</label></p>

		<p>
			<label for="<?php echo $this->get_field_id('xformat'); ?>">
				<?php _e('Extended format: (advanced usage)', 'simpletags'); ?><br />
				<input class="widefat" style="width: 100% !important;" type="text" id="<?php echo $this->get_field_id('xformat'); ?>" name="<?php echo $this->get_field_id('xformat'); ?>" value="<?php echo $xformat; ?>" />
			</label>
		</p>
		<?php
	}
}
?>