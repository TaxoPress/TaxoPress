<?php
function widget_st_tag_cloud_init() {
	// Widgets exists ?
	if ( !function_exists('wp_register_sidebar_widget') || !function_exists('wp_register_widget_control') ) {
		return;
	}

	// Simple Tags exists ?
	if ( !class_exists('SimpleTags') ) {
		return;
	}

	function widget_st_tag_cloud( $widget_args, $number = 1 ) {
		extract($widget_args);
		$options = get_option('widget_stags_cloud');

		// Use Widgets title and no ST title !!
		$args = 'title=';

		// Selection
		$selection = trim(strtolower($options[$number]['selection']));
		if ( !empty($selection) ) {
			$args .= '&cloud_selection='.$selection;
		} else {
			$args .= '&cloud_selection=count-desc';
		}

		// Order
		$order = trim(strtolower($options[$number]['order']));
		if ( !empty($order) ) {
			$args .= '&cloud_sort='.$order;
		} else {
			$args .= '&cloud_sort=random';
		}

		// Max tags
		$max = (int) $options[$number]['max'];
		if ( $max != 0 ) {
			$args .= '&number='.$max;
		}

		// Size Mini
		$smini = (int) $options[$number]['smini'];
		if ( $smini != 0 ) {
			$args .= '&smallest='.$smini;
		}

		// Size Maxi
		$smax = (int) $options[$number]['smax'];
		if ( $smax != 0 ) {
			$args .= '&largest='.$smax;
		}

		// Unit
		$unity = trim($options[$number]['unit']);
		if ( !empty($unity) ) {
			$args .= '&unit='.$unity;
		}

		// Format
		$format = trim($options[$number]['format']);
		if ( !empty($format) ) {
			$args .= '&format='.$format;
		}

		// Use color ?
		$color = (int) $options[$number]['color'];
		if ( $color == 0 ) {
			$args .= '&color=false';
		}

		// Color mini
		$cmini = trim($options[$number]['cmini']);
		if ( !empty($cmini) ) {
			$args .= '&mincolor='.$cmini;
		}

		// Color Max
		$cmax = trim($options[$number]['cmax']);
		if ( !empty($cmax) ) {
			$args .= '&maxcolor='.$cmax;
		}

		// Xformat
		$xformat = trim($options[$number]['xformat']);
		if ( !empty($xformat) ) {
			$args .= '&xformat='.$xformat;
		}

		// Use custom title with Widgets Title
		$title = trim($options[$number]['title']);
		
		echo $before_widget;
		echo $before_title . $title . $after_title;
		st_tag_cloud($args);
		echo $after_widget;
	}

	function widget_st_tag_cloud_control( $number ) {
		// Get actual options
		$options = $newoptions = get_option('widget_stags_cloud');
		if ( !is_array($options) ) {
			$options = $newoptions = array();
		}

		// Post to new options array
		if ( isset($_POST['widget-stags-submit-'.$number]) ) {
			$newoptions[$number]['title'] = strip_tags(stripslashes($_POST['widget-stags-title-'.$number]));
			$newoptions[$number]['max'] = (int) stripslashes($_POST['widget-stags-max-'.$number]);
			$newoptions[$number]['selection'] = stripslashes($_POST['widget-stags-selection-'.$number]);
			$newoptions[$number]['order'] = stripslashes($_POST['widget-stags-order-'.$number]);
			$newoptions[$number]['smini'] = (int) stripslashes($_POST['widget-stags-smini-'.$number]);
			$newoptions[$number]['smax'] = (int) stripslashes($_POST['widget-stags-smax-'.$number]);
			$newoptions[$number]['unit'] = stripslashes($_POST['widget-stags-unit-'.$number]);
			$newoptions[$number]['format'] = stripslashes($_POST['widget-stags-format-'.$number]);
			$newoptions[$number]['color'] = (int) stripslashes($_POST['widget-stags-color-'.$number]);
			$newoptions[$number]['cmini'] = stripslashes($_POST['widget-stags-cmini-'.$number]);
			$newoptions[$number]['cmax'] = stripslashes($_POST['widget-stags-cmax-'.$number]);
			$newoptions[$number]['xformat'] = stripslashes($_POST['widget-stags-xformat-'.$number]);
		}

		// Update if new options
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_stags_cloud', $options);
		}

		// Prepare data for display
		$title = htmlspecialchars($options[$number]['title'], ENT_QUOTES);
		$max = htmlspecialchars($options[$number]['max'], ENT_QUOTES);
		$selection = htmlspecialchars($options[$number]['selection'], ENT_QUOTES);
		$order = htmlspecialchars($options[$number]['order'], ENT_QUOTES);
		$smini = htmlspecialchars($options[$number]['smini'], ENT_QUOTES);
		$smax = htmlspecialchars($options[$number]['smax'], ENT_QUOTES);
		$unit = htmlspecialchars($options[$number]['unit'], ENT_QUOTES);
		$format = htmlspecialchars($options[$number]['format'], ENT_QUOTES);
		$color = htmlspecialchars($options[$number]['color'], ENT_QUOTES);
		$cmini = htmlspecialchars($options[$number]['cmini'], ENT_QUOTES);
		$cmax = htmlspecialchars($options[$number]['cmax'], ENT_QUOTES);
		$xformat= htmlspecialchars($options[$number]['xformat'], ENT_QUOTES);
		?>
		<div>
			<p><?php _e('Empty field will use default value.', 'simpletags'); ?></p>

			<label for="widget-stags-title-<?php echo $number; ?>" style="line-height:35px;display:block;">
				<?php _e('Title:', 'simpletags'); ?><br />
				<input style="width: 100% !important;" type="text" id="widget-stags-title-<?php echo $number; ?>" name="widget-stags-title-<?php echo $number; ?>" value="<?php echo $title; ?>" />
			</label>

			<label for="widget-stags-max-<?php echo $number; ?>" style="line-height:35px;display:block;">
				<?php _e('Max tags to display: (default: 45)', 'simpletags'); ?>
				<input size="20" type="text" id="widget-stags-max-<?php echo $number; ?>" name="widget-stags-max-<?php echo $number; ?>" value="<?php echo $max; ?>" />
			</label>

			<label for="widget-stags-selection-<?php echo $number; ?>" style="line-height:35px;display:block;">
				<?php _e('Tags selection:', 'simpletags'); ?>
				<select id="widget-stags-selection-<?php echo $number; ?>" name="widget-stags-selection-<?php echo $number; ?>">
					<option <?php if ( $selection == 'name-asc' ) echo 'selected="selected"'; ?> value="name-asc"><?php _e('Alphabetical', 'simpletags'); ?></option>
					<option <?php if ( $selection == 'name-desc' ) echo 'selected="selected"'; ?> value="name-desc"><?php _e('Inverse Alphabetical', 'simpletags'); ?></option>
					<option <?php if ( $selection == 'count-desc' ) echo 'selected="selected"'; ?> value="count-desc"><?php _e('Most popular (default)', 'simpletags'); ?></option>
					<option <?php if ( $selection == 'count-asc' ) echo 'selected="selected"'; ?> value="count-asc"><?php _e('Least used', 'simpletags'); ?></option>
					<option <?php if ( $selection == 'random' ) echo 'selected="selected"'; ?> value="random"><?php _e('Random', 'simpletags'); ?></option>
				</select>
			</label>

			<label for="widget-stags-order-<?php echo $number; ?>" style="line-height:35px;display:block;">
				<?php _e('Order tags display:', 'simpletags'); ?>
				<select id="widget-stags-order-<?php echo $number; ?>" name="widget-stags-order-<?php echo $number; ?>">
					<option <?php if ( $order == 'name-asc' ) echo 'selected="selected"'; ?> value="name-asc"><?php _e('Alphabetical', 'simpletags'); ?></option>
					<option <?php if ( $order == 'name-desc' ) echo 'selected="selected"'; ?> value="name-desc"><?php _e('Inverse Alphabetical', 'simpletags'); ?></option>
					<option <?php if ( $order == 'count-desc' ) echo 'selected="selected"'; ?> value="count-desc"><?php _e('Most popular', 'simpletags'); ?></option>
					<option <?php if ( $order == 'count-asc' ) echo 'selected="selected"'; ?> value="count-asc"><?php _e('Least used', 'simpletags'); ?></option>
					<option <?php if ( $order == 'random' ) echo 'selected="selected"'; ?> value="random"><?php _e('Random (default)', 'simpletags'); ?></option>
				</select>
			</label>

			<label for="widget-stags-smini-<?php echo $number; ?>" style="line-height:35px;display:block;">
				<?php _e('Font size mini: (default: 8)', 'simpletags'); ?>
				<input size="20"  type="text" id="widget-stags-smini-<?php echo $number; ?>" name="widget-stags-smini-<?php echo $number; ?>" value="<?php echo $smini; ?>" />
			</label>

			<label for="widget-stags-smax-<?php echo $number; ?>" style="line-height:35px;display:block;">
				<?php _e('Font size max: (default: 22)', 'simpletags'); ?>
				<input size="20" type="text" id="widget-stags-smax-<?php echo $number; ?>" name="widget-stags-smax-<?php echo $number; ?>" value="<?php echo $smax; ?>" />
			</label>

			<label for="widget-stags-unit-<?php echo $number; ?>" style="line-height:35px;display:block;">
				<?php _e('Unit font size:', 'simpletags'); ?>
				<select id="widget-stags-unit-<?php echo $number; ?>" name="widget-stags-unit-<?php echo $number; ?>">
					<option <?php if ( $unit == 'pt' ) echo 'selected="selected"'; ?> value="pt"><?php _e('Point (default)', 'simpletags'); ?></option>
					<option <?php if ( $unit == 'px' ) echo 'selected="selected"'; ?> value="px"><?php _e('Pixel', 'simpletags'); ?></option>
					<option <?php if ( $unit == 'em' ) echo 'selected="selected"'; ?> value="em"><?php _e('Em', 'simpletags'); ?></option>
					<option <?php if ( $unit == '%' ) echo 'selected="selected"'; ?> value="%"><?php _e('Pourcent', 'simpletags'); ?></option>
				</select>
			</label>

			<label for="widget-stags-format-<?php echo $number; ?>" style="line-height:35px;display:block;">
				<?php _e('Format:', 'simpletags'); ?>
				<select id="widget-stags-format-<?php echo $number; ?>" name="widget-stags-format-<?php echo $number; ?>">
					<option <?php if ( $format == 'flat' ) echo 'selected="selected"'; ?> value="flat"><?php _e('Flat (default)', 'simpletags'); ?></option>
					<option <?php if ( $format == 'list' ) echo 'selected="selected"'; ?> value="list"><?php _e('List (UL/LI)', 'simpletags'); ?></option>
				</select>
			</label>

			<label for="widget-stags-color-<?php echo $number; ?>" style="line-height:35px;display:block;">
				<input type="checkbox" id="widget-stags-color-<?php echo $number; ?>" name="widget-stags-color-<?php echo $number; ?>" <?php if ( $color == 1 ) echo 'checked="checked"'; ?> value="1" />
				<?php _e('Use auto color cloud:', 'simpletags'); ?>
			</label>

			<label for="widget-stags-cmini-<?php echo $number; ?>" style="line-height:35px;display:block;">
				<?php _e('Font color mini: (default: #CCCCCC)', 'simpletags'); ?>
				<input type="text" id="widget-stags-cmini-<?php echo $number; ?>" name="widget-stags-cmini-<?php echo $number; ?>" value="<?php echo $cmini; ?>" />
			</label>

			<label for="widget-stags-cmax-<?php echo $number; ?>" style="line-height:35px;display:block;">
				<?php _e('Font color max: (default: #000000)', 'simpletags'); ?>
				<input type="text" id="widget-stags-cmax-<?php echo $number; ?>" name="widget-stags-cmax-<?php echo $number; ?>" value="<?php echo $cmax; ?>" />
			</label>

			<label for="widget-stags-xformat-<?php echo $number; ?>" style="line-height:35px;display:block;">
				<?php _e('Extended format: (advanced usage)', 'simpletags'); ?><br />
				<input style="width: 100% !important;" type="text" id="widget-stags-xformat-<?php echo $number; ?>" name="widget-stags-xformat-<?php echo $number; ?>" value="<?php echo $xformat; ?>" />
			</label>

			<input type="hidden" name="widget-stags-submit-<?php echo $number; ?>" id="widget-stags-submit-<?php echo $number; ?>" value="1" />
		</div>
		<?php
	}

	function st_tag_cloud_setup() {
		$options = $newoptions = get_option('widget_stags_cloud');
		if ( isset($_POST['stags_cloud-number-submit']) ) {
			$newoptions['number'] = (int) $_POST['stags_cloud-number'];
			if ( $newoptions['number'] > 9 ) {
				$newoptions['number'] = 9;
			} elseif ( $newoptions['number'] < 1 ) {
				$newoptions['number'] = 1;
			}
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_stags_cloud', $options);
			widget_st_tag_cloud_register($options['number']);
		}
	}

	function st_tag_cloud_page() {
		$options = get_option('widget_stags_cloud');
		?>
			<div class="wrap">
				<form method="post">
					<h2><?php _e('Tag Cloud Widgets', 'simpletags'); ?></h2>
					<p style="line-height: 30px;"><?php _e('How many tag cloud widgets would you like?', 'simpletags'); ?>
						<select id="stags_cloud-number" name="stags_cloud-number" value="<?php echo $options['number']; ?>">
							<?php for ( $i = 1; $i < 10; ++$i ) echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>"; ?>
						</select>
						<span class="submit"><input type="submit" name="stags_cloud-number-submit" id="stags_cloud-number-submit" value="<?php echo attribute_escape(__('Save', 'simpletags')); ?>" /></span></p>
				</form>
			</div>
		<?php
	}

	function widget_st_tag_cloud_register() {
		$options = get_option('widget_stags_cloud');

		$number = (int) $options['number'];
		if ( $number < 1 ) {
			$number = 1;
		} elseif ( $number > 9 ) {
			$number = 9;
		}

		for ( $i = 1; $i <= 9; $i++ ) {
			wp_register_sidebar_widget('widget_stags-'.$i, sprintf(__('Extended Tag Cloud %d', 'simpletags'), $i), $i <= $number ? 'widget_st_tag_cloud' : '', array('classname' => 'widget_stags_cloud'), $i);
			wp_register_widget_control('widget_stags-'.$i, sprintf(__('Extended Tag Cloud %d', 'simpletags'), $i), $i <= $number ? 'widget_st_tag_cloud_control' : '', array('width' => 460, 'height' => 520), $i);
		}

		add_action('sidebar_admin_setup', 'st_tag_cloud_setup');
		add_action('sidebar_admin_page', 'st_tag_cloud_page');
	}

	// Launch Widgets
	widget_st_tag_cloud_register();
}

// Initialize !
widget_st_tag_cloud_init();
?>