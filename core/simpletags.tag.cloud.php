<?php
Class SimpleTags_TagCloud {
	var $options = array();
	var $options_name = 'st_tag_cloud';
	
	function SimpleTags_TagCloud() {
		// Load default options
		$this->options = array(
			'allow_embed_tcloud' => 0,
			'cloud_selection' => 'count-desc',
			'cloud_sort' => 'random',
			'cloud_limit_qty' => 45,
			'cloud_notagstext' => __('No tags.', 'simpletags'),
			'cloud_title' => __('<h4>Tag Cloud</h4>', 'simpletags'),
			'cloud_format' => 'flat',
			'cloud_xformat' => __('<a href="%tag_link%" id="tag-link-%tag_id%" class="st-tags t%tag_scale%" title="%tag_count% topics" %tag_rel% style="%tag_size% %tag_color%">%tag_name%</a>', 'simpletags'),
			'cloud_max_color' => '#000000',
			'cloud_min_color' => '#CCCCCC',
			'cloud_max_size' => 22,
			'cloud_min_size' => 8,
			'cloud_unit' => 'pt',
			'cloud_inc_cats' => 0,
			'cloud_adv_usage' => ''
		);
		
		// Load options from DB
		$this->options = SimpleTags_Utils::loadOptionsFromDB( $this->options_name, $this->options );
		
		// Embedded tag cloud
		if ( $this->options['allow_embed_tcloud'] == 1 ) {
			add_filter('the_content', array(&$this, 'inlineTagCloud'));	
		}		
	}
	
	/**
	 * Replace marker by a tag cloud in post content
	 *
	 * @param string $content
	 * @return string
	 */
	function inlineTagCloud( $content = '' ) {
		if ( strpos($content, '<!--st_tag_cloud-->') ) {
			$content = str_replace('<!--st_tag_cloud-->', $this->extendedTagCloud( '', false ), $content);
		}
		return $content;
	}
	
	/**
	 * Generate extended tag cloud
	 *
	 * @param string $args
	 * @return string|array
	 */
	function extendedTagCloud( $args = '', $copyright = true ) {
		$defaults = array(
			'size' => 'true',
			'smallest' => 8,
			'largest' => 22,
			'unit' => 'pt',
			'color' => 'true',
			'maxcolor' => '#000000',
			'mincolor' => '#CCCCCC',
			'number' => 45,
			'format' => 'flat',
			'cloud_selection' => 'count-desc',
			'cloud_sort' => 'random',
			'exclude' => '',
			'include' => '',
			'no_follow' => 0,
			'limit_days' => 0,
			'min_usage' => 0,
			'inc_cats' => 0,
			'notagstext' => __('No tags.', 'simpletags'),
			'xformat' => __('<a href="%tag_link%" id="tag-link-%tag_id%" class="st-tags t%tag_scale%" title="%tag_count% topics" %tag_rel% style="%tag_size% %tag_color%">%tag_name%</a>', 'simpletags'),
			'title' => __('<h4>Tag Cloud</h4>', 'simpletags'),
			'category' => 0
		);

		// Get values in DB
		$defaults['no_follow'] = $this->options['no_follow'];
		$defaults['cloud_selection'] = $this->options['cloud_selection'];
		$defaults['cloud_sort'] = $this->options['cloud_sort'];
		$defaults['number'] = $this->options['cloud_limit_qty'];
		$defaults['notagstext'] = $this->options['cloud_notagstext'];
		$defaults['title'] = $this->options['cloud_title'];
		$defaults['maxcolor'] = $this->options['cloud_max_color'];
		$defaults['mincolor'] = $this->options['cloud_min_color'];
		$defaults['largest'] = $this->options['cloud_max_size'];
		$defaults['smallest'] = $this->options['cloud_min_size'];
		$defaults['unit'] = $this->options['cloud_unit'];
		$defaults['xformat'] = $this->options['cloud_xformat'];
		$defaults['format'] = $this->options['cloud_format'];
		$defaults['inc_cats'] = $this->options['cloud_inc_cats'];

		if ( empty($args) ) {
			$args = $this->options['cloud_adv_usage'];
		}
		$args = wp_parse_args( $args, $defaults );

		// Get categories ?
		$taxonomy = ( (int) $args['inc_cats'] == 0 ) ? 'post_tag' : array('post_tag', 'category');

		// Get terms
		global $wp_object_cache;
		$terms = $this->getTags( $args, $wp_object_cache->cache_enabled, $taxonomy );
		extract($args); // Params to variables

		// If empty use default xformat !
		if ( empty($xformat) ) {
			$xformat = $defaults['xformat'];
		}

		// Clean memory
		$args = array();
		$defaults = array();
		unset($args, $defaults);

		if ( empty($terms) ) {
			return $this->outputContent( 'st-tag-cloud', $format, $title, $notagstext, $copyright );
		}

		$counts = $terms_data = array();
		foreach ( (array) $terms as $term ) {
			$counts[$term->name] = $term->count;
			$terms_data[$term->name] = $term;
		}

		// Remove temp data from memory
		$terms = array();
		unset($terms);

		// Use full RBG code
		if ( strlen($maxcolor) == 4 ) {
			$maxcolor = $maxcolor . substr($maxcolor, 1, strlen($maxcolor));
		}
		if ( strlen($mincolor) == 4 ) {
			$mincolor = $mincolor . substr($mincolor, 1, strlen($mincolor));
		}

		// Check as smallest inferior or egal to largest
		if ( $smallest > $largest ) {
			$smallest = $largest;
		}

		// Scaling - Hard value for the moment
		$scale_min = 1;
		$scale_max = 10;

		$minval = min($counts);
		$maxval = max($counts);;

		$minout = max($scale_min, 0);
		$maxout = max($scale_max, $minout);

		$scale = ($maxval > $minval) ? (($maxout - $minout) / ($maxval - $minval)) : 0;

		// HTML Rel (tag/no-follow)
		$rel = $this->buildRel( $no_follow );

		// Remove color marquer if color = false
		if ( $color == 'false' ) {
			$xformat = str_replace('%tag_color%', '', $xformat);
		}

		// Remove size marquer if size = false
		if ( $size == 'false' ) {
			$xformat = str_replace('%tag_size%', '', $xformat);
		}

		// Order terms before output
		// count-asc/count-desc/name-asc/name-desc/random
		$cloud_sort = strtolower($cloud_sort);
		switch ( $cloud_sort ) {
			case 'count-asc':
				asort($counts);
				break;
			case 'count-desc':
				arsort($counts);
				break;
			case 'name-asc':
				uksort($counts, array( 'SimpleTags_Utils', 'uksortByName'));
				break;
			case 'name-desc':
				uksort($counts, array( 'SimpleTags_Utils', 'uksortByName'));
				array_reverse($counts);
				break;
			default: // random
			$counts = $this->randomArray($counts);
			break;
		}

		$output = array();
		foreach ( (array) $counts as $term_name => $count ) {
			if ( !is_object($terms_data[$term_name]) ) {
				continue;
			}

			$term = $terms_data[$term_name];
			$scale_result = (int) (($term->count - $minval) * $scale + $minout);
			$output[] = $this->formatInternalTag( $xformat, $term, $rel, $scale_result, $scale_max, $scale_min, $largest, $smallest, $unit, $maxcolor, $mincolor );
		}

		// Remove unused variables
		$counts = array();
		$terms = array();
		unset($counts, $terms, $element_loop, $term);

		return $this->outputContent( 'st-tag-cloud', $format, $title, $output, $copyright );
	}

	/**
	 * Randomize an array and keep association
	 *
	 * @param array $data
	 * @return array
	 */
	function randomArray( $data_in = array() ) {
		if ( empty($data_in) ) {
			return $data_in;
		}

		srand( (float) microtime() * 1000000 ); // For PHP < 4.2
		$rand_keys = array_rand($data_in, count($data_in));

		foreach( (array) $rand_keys as $key ) {
			$data_out[$key] = $data_in[$key];
		}

		return $data_out;
	}
	
	/**
	 * This is pretty filthy. Doing math in hex is much too weird. It's more likely to work, this way!
	 * Provided from UTW. Thanks.
	 *
	 * @param integer $scale_color
	 * @param string $min_color
	 * @param string $max_color
	 * @return string
	 */
	function getColorByScale($scale_color, $min_color, $max_color) {
		$scale_color = $scale_color / 100;

		$minr = hexdec(substr($min_color, 1, 2));
		$ming = hexdec(substr($min_color, 3, 2));
		$minb = hexdec(substr($min_color, 5, 2));

		$maxr = hexdec(substr($max_color, 1, 2));
		$maxg = hexdec(substr($max_color, 3, 2));
		$maxb = hexdec(substr($max_color, 5, 2));

		$r = dechex(intval((($maxr - $minr) * $scale_color) + $minr));
		$g = dechex(intval((($maxg - $ming) * $scale_color) + $ming));
		$b = dechex(intval((($maxb - $minb) * $scale_color) + $minb));

		if (strlen($r) == 1) $r = '0'.$r;
		if (strlen($g) == 1) $g = '0'.$g;
		if (strlen($b) == 1) $b = '0'.$b;

		return '#'.$r.$g.$b;
	}
}
?>