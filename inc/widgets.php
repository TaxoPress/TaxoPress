<?php
/**
 * Simple Tags widget class
 *
 */
class SimpleTags_Widget extends WP_Widget {
	function SimpleTags_Widget() {
		$this->WP_Widget(
			'simpletags',
			__( 'Tag Cloud (Simple Tags)', 'simpletags' ),
			array( 'classname' => 'widget-simpletags', 'description' => __( "Your most used tags in cloud format with dynamic color and many options", 'simpletags' ) )
		);
	}
	
	function getFields() {
		return array(
			'title' 		=> __('Tag cloud', 'simpletags'),
			'max'			=> 45,
			'selectionby' 	=> 'count',
			'selection' 	=> 'desc',
			'orderby'		=> 'random',
			'order'			=> 'asc',
			'smini'			=> 8,
			'smax'			=> 22,
			'unit' 			=> 'pt',
			'format'		=> 'flat',
			'color' 		=> 1,
			'cmini' 		=> '#CCCCCC',
			'cmax'			=> '#000000',
			'xformat'		=> ''
		);
	}
	
	function widget( $args, $instance ) {
		extract( $args );
		
		// Set values and clean it
		foreach ( (array) $this->getFields() as $field => $field_value ) {
			${$field} = trim( $instance[$field] );
		}
		
		$param = '';
		
		// Selection
		$param .= ( !empty($selectionby) ) ? '&selectionby='.$selectionby : '&selectionby=count';
		$param .= ( !empty($selection) )   ? '&selection='.$selection	 : '&selection=desc';
		
		// Order
		$param .= ( !empty($orderby) ) ? '&orderby='.$orderby : '&orderby=random';
		$param .= ( !empty($order) )   ? '&order='.$order	 : '&order=asc';
		
		// Max tags
		if ( (int) $max != 0 ) $param .= '&number='.$max;
		
		// Size Mini
		if ( (int) $smini != 0 ) $param .= '&smallest='.$smini;
		
		// Size Maxi
		if ( (int) $smax != 0 ) $param .= '&largest='.$smax;
		
		// Unit
		if ( !empty($unit) ) $param .= '&unit='.$unit;
		
		// Format
		if ( !empty($format) ) $param .= '&format='.$format;
		
		// Use color ?
		if ( (int) $color == 0 ) $param .= '&color=false';
		
		// Color mini
		if ( !empty($cmini) ) $param .= '&mincolor='.$cmini;
		
		// Color Max
		if ( !empty($cmax) ) $param .= '&maxcolor='.$cmax;
		
		// Xformat
		if ( !empty($xformat) ) $param .= '&xformat='.$xformat;
		
		echo $before_widget;
		echo $before_title . apply_filters( 'widget_title', $title ) . $after_title;
			st_tag_cloud( apply_filters( 'simple-tags-widget', 'title='.$param, $instance ) ); // Use Widgets title and no ST title !!
		echo $after_widget;
	}
	
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		
		foreach ( (array) $this->getFields() as $field => $field_value ) {
			$instance[$field] = strip_tags($new_instance[$field]);
		}
		
		return $instance;
	}
	
	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, $this->getFields() );
		foreach ( (array) $this->getFields() as $field => $field_value ) {
			${$field} = esc_attr( $instance[$field] );
		}
		?>
		<p><?php _e('Empty field will use default value.', 'simpletags'); ?></p>
		
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">
				<?php _e('Title:', 'simpletags'); ?>
				<input class="widefat" type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>" />
			</label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('max'); ?>">
				<?php _e('Max tags to display: (default: 45)', 'simpletags'); ?>
				<input class="widefat" size="20" type="text" id="<?php echo $this->get_field_id('max'); ?>" name="<?php echo $this->get_field_name('max'); ?>" value="<?php echo $max; ?>" />
			</label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('selectionby'); ?>">
				<?php _e('Order by for DB selection tags:', 'simpletags'); ?>
				<select id="<?php echo $this->get_field_id('selectionby'); ?>" name="<?php echo $this->get_field_name('selectionby'); ?>">
					<option <?php if ( $selectionby == 'name' ) echo 'selected="selected"'; ?> value="name"><?php _e('Name', 'simpletags'); ?></option>
					<option <?php if ( $selectionby == 'slug' ) echo 'selected="selected"'; ?> value="slug"><?php _e('Slug', 'simpletags'); ?></option>
					<option <?php if ( $selectionby == 'term_group' ) echo 'selected="selected"'; ?> value="term_group"><?php _e('Term group', 'simpletags'); ?></option>
					<option <?php if ( $selectionby == 'count' ) echo 'selected="selected"'; ?> value="count"><?php _e('Counter', 'simpletags'); ?></option>
					<option <?php if ( $selectionby == 'random' ) echo 'selected="selected"'; ?> value="random"><?php _e('Random (default)', 'simpletags'); ?></option>
				</select>
			</label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('selection'); ?>">
				<?php _e('Order for DB selection tags:', 'simpletags'); ?>
				<select id="<?php echo $this->get_field_id('selection'); ?>" name="<?php echo $this->get_field_name('selection'); ?>">
					<option <?php if ( $selection == 'asc' ) echo 'selected="selected"'; ?> value="asc"><?php _e('ASC', 'simpletags'); ?></option>
					<option <?php if ( $selection == 'desc' ) echo 'selected="selected"'; ?> value="desc"><?php _e('DESC (default)', 'simpletags'); ?></option>				</select>
			</label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('orderby'); ?>">
				<?php _e('Order by for display tags:', 'simpletags'); ?>
				<select id="<?php echo $this->get_field_id('orderby'); ?>" name="<?php echo $this->get_field_name('orderby'); ?>">
					<option <?php if ( $orderby == 'name' ) echo 'selected="selected"'; ?> value="name"><?php _e('Name', 'simpletags'); ?></option>
					<option <?php if ( $orderby == 'count' ) echo 'selected="selected"'; ?> value="count"><?php _e('Counter', 'simpletags'); ?></option>
					<option <?php if ( $orderby == 'random' ) echo 'selected="selected"'; ?> value="random"><?php _e('Random (default)', 'simpletags'); ?></option>
				</select>
			</label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('order'); ?>">
				<?php _e('Order for display tags:', 'simpletags'); ?>
				<select id="<?php echo $this->get_field_id('order'); ?>" name="<?php echo $this->get_field_name('order'); ?>">
					<option <?php if ( $order == 'asc' ) echo 'selected="selected"'; ?> value="asc"><?php _e('ASC', 'simpletags'); ?></option>
					<option <?php if ( $order == 'desc' ) echo 'selected="selected"'; ?> value="desc"><?php _e('DESC (default)', 'simpletags'); ?></option>				</select>
			</label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('smini'); ?>">
				<?php _e('Font size mini: (default: 8)', 'simpletags'); ?>
				<input class="widefat" size="20"  type="text" id="<?php echo $this->get_field_id('smini'); ?>" name="<?php echo $this->get_field_name('smini'); ?>" value="<?php echo $smini; ?>" />
			</label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('smax'); ?>">
				<?php _e('Font size max: (default: 22)', 'simpletags'); ?>
				<input class="widefat" size="20" type="text" id="<?php echo $this->get_field_id('smax'); ?>" name="<?php echo $this->get_field_name('smax'); ?>" value="<?php echo $smax; ?>" />
			</label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('unit'); ?>">
				<?php _e('Unit font size:', 'simpletags'); ?>
				<select id="<?php echo $this->get_field_id('unit'); ?>" name="<?php echo $this->get_field_name('unit'); ?>">
					<option <?php if ( $unit == 'pt' ) echo 'selected="selected"'; ?> value="pt"><?php _e('Point (default)', 'simpletags'); ?></option>
					<option <?php if ( $unit == 'px' ) echo 'selected="selected"'; ?> value="px"><?php _e('Pixel', 'simpletags'); ?></option>
					<option <?php if ( $unit == 'em' ) echo 'selected="selected"'; ?> value="em"><?php _e('Em', 'simpletags'); ?></option>
					<option <?php if ( $unit == '%' ) echo 'selected="selected"'; ?> value="%"><?php _e('Pourcent', 'simpletags'); ?></option>
				</select>
			</label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('format'); ?>">
				<?php _e('Format:', 'simpletags'); ?>
				<select id="<?php echo $this->get_field_id('format'); ?>" name="<?php echo $this->get_field_name('format'); ?>">
					<option <?php if ( $format == 'flat' ) echo 'selected="selected"'; ?> value="flat"><?php _e('Flat (default)', 'simpletags'); ?></option>
					<option <?php if ( $format == 'list' ) echo 'selected="selected"'; ?> value="list"><?php _e('List (UL/LI)', 'simpletags'); ?></option>
				</select>
			</label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('color'); ?>">
				<input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id('color'); ?>" name="<?php echo $this->get_field_name('color'); ?>" <?php if ( $color == 1 ) echo 'checked="checked"'; ?> value="1" />
				<?php _e('Use auto color cloud:', 'simpletags'); ?>
			</label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('cmini'); ?>">
				<?php _e('Font color mini: (default: #CCCCCC)', 'simpletags'); ?>
				<input class="widefat" type="text" id="<?php echo $this->get_field_id('cmini'); ?>" name="<?php echo $this->get_field_name('cmini'); ?>" value="<?php echo $cmini; ?>" />
			</label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('cmax'); ?>">
				<?php _e('Font color max: (default: #000000)', 'simpletags'); ?>
				<input class="widefat" type="text" id="<?php echo $this->get_field_id('cmax'); ?>" name="<?php echo $this->get_field_name('cmax'); ?>" value="<?php echo $cmax; ?>" />
			</label>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('xformat'); ?>">
				<?php _e('Extended format: (advanced usage)', 'simpletags'); ?><br />
				<input class="widefat" style="width: 100% !important;" type="text" id="<?php echo $this->get_field_id('xformat'); ?>" name="<?php echo $this->get_field_name('xformat'); ?>" value="<?php echo $xformat; ?>" />
			</label>
		</p>
		<?php
	}
}
?>