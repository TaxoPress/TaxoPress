<?php

class SimpleTags_Client {
	/**
	 * Initialize TaxoPress client
	 *
	 * @return boolean
	 */
	public function __construct() {
		// Load translation
		add_action( 'init', array( __CLASS__, 'init_translation' ) );

		// Register media tags taxonomy
		add_action( 'init', array( $this, 'simple_tags_register_media_tag' ) );

        require( STAGS_DIR . '/inc/class.client.autolinks.php' );
        new SimpleTags_Client_Autolinks();

		// Call tag clouds ?
        if ( 1 === (int) SimpleTags_Plugin::get_option_value( 'active_terms_display' ) ) {
            require_once STAGS_DIR . '/inc/tag-clouds-action.php';
        }

		// Call post tags ?
        if ( 1 === (int) SimpleTags_Plugin::get_option_value( 'active_post_tags' ) ) {
            require_once STAGS_DIR . '/inc/post-tags-action.php';
        }

		// Call related posts ?
		if ( (int) SimpleTags_Plugin::get_option_value( 'active_related_posts_new' ) === 1 ) {
            require_once STAGS_DIR . '/inc/related-posts-action.php';
        }
		if ( (int) SimpleTags_Plugin::get_option_value( 'active_related_posts' ) === 1 || (int) SimpleTags_Plugin::get_option_value( 'active_related_posts_new' ) === 1 ) {
			require( STAGS_DIR . '/inc/class.client.related_posts.php' );
			new SimpleTags_Client_RelatedPosts();
		}

		// Call auto terms ?
		require( STAGS_DIR . '/inc/class.client.autoterms.php' );
		new SimpleTags_Client_Autoterms();

		// Call post tags ?
		require( STAGS_DIR . '/inc/class.client.post_tags.php' );
		new SimpleTags_Client_PostTags();


		if ( (int)SimpleTags_Plugin::get_option_value( 'active_taxonomies' ) === 1 ) {
            require_once STAGS_DIR . '/inc/taxonomies-action.php';
            add_action( 'parse_query', array( __CLASS__, 'cpt_taxonomy_parse_query' ) );
		    if (defined('STAGS_OPTIONS_NAME')) {
         	    $saved_option = (array)get_option( STAGS_OPTIONS_NAME );
        	    if ((array_key_exists('use_tag_pages', $saved_option))) {
				    if((int)$saved_option['use_tag_pages'] === 1){
                	    if ( !array_key_exists('post_tag', taxopress_get_extername_taxonomy_data()) ) {
			        	    add_action( 'init', array( __CLASS__, 'init' ), 11 );
			        	    add_action( 'parse_query', array( __CLASS__, 'parse_query' ) );
                	    }
		    	    }
        	    }
		    }
        }

		return true;
	}


	/**
	 * Add cpt to taxonomy during the query
	 *
	 * @param WP_Query $query
	 *
	 * @return void
	 * @author Olatechpro
	 */
	public static function cpt_taxonomy_parse_query( $query ) {
        if(function_exists('get_current_screen')){
            $screen = get_current_screen();
        }

        if(is_admin()){
            return $query;
        }
        if(isset($screen->id) && $screen->id == 'edit-post'){
            return $query;
        }
        if ( $query->is_category == true || $query->is_tag == true || $query->is_tax == true ) {
            $get_queried_object = @get_queried_object();
            if(is_object($get_queried_object)){
				if(!isset($get_queried_object->taxonomy)){
                    return $query;
				}
                if(!taxopress_show_all_cpt_in_archive_result($get_queried_object->taxonomy)){
                    return $query;
                }
                $get_taxonomy = get_taxonomy( $get_queried_object->taxonomy );
                if(is_object($get_taxonomy)){
                    $post_types = $get_taxonomy->object_type;
                        if ( isset( $query->query_vars['post_type'] )){
                            if(is_array( $query->query_vars['post_type'] )){
                                $post_types = array_filter(array_merge($query->query_vars['post_type'],$post_types));
                            }elseif(is_string( $query->query_vars['post_type'] )){
                                $original_post_type = $query->query_vars['post_type'];
                                $get_post_type_object = get_post_type_object( $original_post_type );
                                if(is_object($get_post_type_object)){
                                    if((int)$get_post_type_object->public === 0){
                                        return $query;
                                    }
                                }
                                $post_types[] = $query->query_vars['post_type'];
                            }
                            $new_post_object = $post_types;
                        }else{
                            $new_post_object = $post_types;
                        }
                        $query->query_vars['post_type'] = $new_post_object;
                }
            }
		}
        return $query;
	}

	/**
	 * Taxonomy: Media Tags.
	 */
	public function simple_tags_register_media_tag() {

    if((int)get_option('taxopress_media_tag_deleted') === 0){
	$labels = [
		"name" => __( "Media Tags", "simple-tags" ),
		"singular_name" => __( "Media Tag", "simple-tags" ),
		"menu_name" => __( "Media Tags", "simple-tags" ),
		"all_items" => __( "All Media Tags", "simple-tags" ),
		"edit_item" => __( "Edit Media Tag", "simple-tags" ),
		"view_item" => __( "View Media Tag", "simple-tags" ),
		"update_item" => __( "Update Media Tag name", "simple-tags" ),
		"add_new_item" => __( "Add new Media Tag", "simple-tags" ),
		"new_item_name" => __( "New Media Tag name", "simple-tags" ),
		"parent_item" => __( "Parent Media Tag", "simple-tags" ),
		"parent_item_colon" => __( "Parent Media Tag:", "simple-tags" ),
		"search_items" => __( "Search Media Tags", "simple-tags" ),
		"popular_items" => __( "Popular Media Tags", "simple-tags" ),
		"separate_items_with_commas" => __( "Separate Media Tags with commas", "simple-tags" ),
		"add_or_remove_items" => __( "Add or remove Media Tags", "simple-tags" ),
		"choose_from_most_used" => __( "Choose from the most used Media Tags", "simple-tags" ),
		"not_found" => __( "No Media Tags found", "simple-tags" ),
		"no_terms" => __( "No Media Tags", "simple-tags" ),
		"items_list_navigation" => __( "Media Tags list navigation", "simple-tags" ),
		"items_list" => __( "Media Tags list", "simple-tags" ),
		"back_to_items" => __( "Back to Media Tags", "simple-tags" ),
	];

	$args = [
		"label" => __( "Media Tags", "simple-tags" ),
		"labels" => $labels,
		"public" => true,
		"publicly_queryable" => true,
		"hierarchical" => false,
		"show_ui" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"query_var" => true,
		"update_count_callback" => '_update_generic_term_count',
		"rewrite" => [ 'slug' => 'media_tag', 'with_front' => true, ],
		"show_admin_column" => false,
		"show_in_rest" => true,
		"rest_base" => "media_tag",
		"rest_controller_class" => "WP_REST_Terms_Controller",
		"show_in_quick_edit" => false,
	];
	register_taxonomy( "media_tag", [ "attachment" ], $args );
    }
    }

	/**
	 * Load translations
	 */
	public static function init_translation() {
		load_plugin_textdomain( 'simple-tags', false, basename( STAGS_DIR ) . '/languages' );
	}

	/**
	 * Register taxonomy post_tags for page post type
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function init() {
		register_taxonomy_for_object_type( 'post_tag', 'page' );
	}

	/**
	 * Add page post type during the query
	 *
	 * @param WP_Query $query
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function parse_query( $query ) {
        if(function_exists('get_current_screen')){
            $screen = get_current_screen();
        }

        if(isset($screen->id) && $screen->id == 'edit-post'){
            return $query;
        }

        if ( $query->is_tag == true ) {
			if ( isset( $query->query_vars['post_type'] ) && is_array( $query->query_vars['post_type'] ) ) {
				$query->query_vars['post_type'][] = 'page';
			}else{
                $query->query_vars['post_type'] = array( 'post', 'page' );
            }
		}
	}

	/**
	 * Randomize an array and keep association
	 *
	 * @param array $array
	 *
	 * @return boolean
	 */
	public static function random_array( &$array ) {
		if ( ! is_array( $array ) || empty( $array ) ) {
			return false;
		}

		$keys = array_keys( $array );
		shuffle( $keys );

		$new = array();
		foreach ( (array) $keys as $key ) {
			$new[ $key ] = $array[ $key ];
		}

		$array = $new;

		return true;
	}

	/**
	 * Build rel for tag link
	 *
	 * @return string
	 */
	public static function get_rel_attribut() {
		global $wp_rewrite;
		$rel = ( is_object( $wp_rewrite ) && $wp_rewrite->using_permalinks() ) ? 'tag' : ''; // Tag ?
		if ( ! empty( $rel ) ) {
			$rel = 'rel="' . $rel . '"'; // Add HTML Tag
		}

		return $rel;
	}

	/**
	 * Format data for output
	 *
	 * @param string $html_class
	 * @param string $format
	 * @param string $title
	 * @param string $content
	 * @param boolean $copyright
	 * @param string $separator
	 *
	 * @return string|array
	 */
	public static function output_content( $html_class = '', $format = 'list', $title = '', $content = '', $copyright = true, $separator = '', $div_class = '', $a_class = '' ) {
		if ( empty( $content ) ) {
			return ''; // return nothing
		}

		if ( $format == 'array' && is_array( $content ) ) {
			return $content; // Return PHP array if format is array
		}

		if ( is_array( $content ) ) {
			switch ( $format ) {
				case 'list' :
					$output = '<ul class="' . $html_class . '">' . "\n\t" . '<li>' . implode( "</li>\n\t<li>", $content ) . "</li>\n</ul>\n";
					break;
				default :
					$output = '<div class="' . $html_class . '">' . "\n\t" . implode( "{$separator}\n", $content ) . "</div>\n";
					break;
			}
		} else {
			$content = trim( $content );
			switch ( $format ) {
				case 'string' :
					$output = $content;
					break;
				case 'list' :
					$output = '<ul class="' . $html_class . '">' . "\n\t" . '<li>' . $content . "</li>\n\t" . "</ul>\n";
					break;
				default :
					$output = '<div class="' . $html_class . '">' . "\n\t" . $content . "</div>\n";
					break;
			}
		}

		//wrap class
		if(!empty(trim($div_class))){
			$wrap_div_class_open = '<div class="'.taxopress_format_class($div_class).'">';
			$wrap_div_class_close = '</div>';
		}else{
			$wrap_div_class_open = '';
			$wrap_div_class_close = '';
		}
		// Replace false by empty
		$title = trim( $title );
		if ( strtolower( $title ) == 'false' ) {
			$title = '';
		}

		// Put title if exist
		if ( ! empty( $title ) ) {
			$title .= "\n\t";
		}

		if ( $copyright === true ) {
			return "\n" . '<!-- Generated by TaxoPress ' . STAGS_VERSION . ' - https://wordpress.org/plugins/simple-tags/ -->' . "\n\t" . $wrap_div_class_open . $title . $output . $wrap_div_class_close . "\n";
		} else {
			return "\n\t" . $wrap_div_class_open . $title . $output . $wrap_div_class_close . "\n";
		}
	}

	/**
	 * Remplace marker by dynamic values (use for related tags, current tags and tag cloud)
	 *
	 * @param string $element_loop
	 * @param object $term
	 * @param string $rel
	 * @param integer $scale_result
	 * @param integer $scale_max
	 * @param integer $scale_min
	 * @param integer $largest
	 * @param integer $smallest
	 * @param string $unit
	 * @param string $maxcolor
	 * @param string $mincolor
	 *
	 * @return string
	 */
	public static function format_internal_tag( $element_loop = '', $term = null, $rel = '', $scale_result = 0, $scale_max = null, $scale_min = 0, $largest = 0, $smallest = 0, $unit = '', $maxcolor = '', $mincolor = '' ) {
		// Need term object
		$element_loop = str_replace( '%tag_link%', esc_url( get_term_link( $term, $term->taxonomy ) ), $element_loop );
		$element_loop = str_replace( '%tag_feed%', esc_url( get_term_feed_link( $term->term_id, $term->taxonomy, '' ) ), $element_loop );

		$element_loop = str_replace( '%tag_name%', esc_html( $term->name ), $element_loop );
		$element_loop = str_replace( '%tag_name_attribute%', esc_html( strip_tags( $term->name ) ), $element_loop );
		$element_loop = str_replace( '%tag_id%', $term->term_id, $element_loop );
		$element_loop = str_replace( '%tag_count%', (int) $term->count, $element_loop );

		// Need rel
		$element_loop = str_replace( '%tag_rel%', $rel, $element_loop );

		// Need max/min/scale and other :)
		if ( $scale_result !== null ) {
			$element_loop = str_replace( '%tag_size%', 'font-size:' . self::round( ( $scale_result - $scale_min ) * ( $largest - $smallest ) / ( $scale_max - $scale_min ) + $smallest, 2 ) . $unit . ';', $element_loop );
			$element_loop = str_replace( '%tag_color%', 'color:' . self::get_color_by_scale( self::round( ( $scale_result - $scale_min ) * ( 100 ) / ( $scale_max - $scale_min ), 2 ), $mincolor, $maxcolor ) . ';', $element_loop );
			$element_loop = str_replace( '%tag_scale%', $scale_result, $element_loop );
		}

		// External link
		$element_loop = str_replace( '%tag_technorati%', self::format_external_tag( 'technorati', $term->name ), $element_loop );
		$element_loop = str_replace( '%tag_flickr%', self::format_external_tag( 'flickr', $term->name ), $element_loop );
		$element_loop = str_replace( '%tag_delicious%', self::format_external_tag( 'delicious', $term->name ), $element_loop );

		return $element_loop;
	}

	/**
	 * This is pretty filthy. Doing math in hex is much too weird. It's more likely to work, this way!
	 * Provided from UTW. Thanks.
	 *
	 * @param integer $scale_color
	 * @param string $min_color
	 * @param string $max_color
	 *
	 * @return string
	 */
	public static function get_color_by_scale( $scale_color, $min_color, $max_color ) {
		$scale_color = $scale_color / 100;

		$minr = hexdec( substr( $min_color, 1, 2 ) );
		$ming = hexdec( substr( $min_color, 3, 2 ) );
		$minb = hexdec( substr( $min_color, 5, 2 ) );

		$maxr = hexdec( substr( $max_color, 1, 2 ) );
		$maxg = hexdec( substr( $max_color, 3, 2 ) );
		$maxb = hexdec( substr( $max_color, 5, 2 ) );

		$r = dechex( intval( ( ( $maxr - $minr ) * $scale_color ) + $minr ) );
		$g = dechex( intval( ( ( $maxg - $ming ) * $scale_color ) + $ming ) );
		$b = dechex( intval( ( ( $maxb - $minb ) * $scale_color ) + $minb ) );

		if ( strlen( $r ) == 1 ) {
			$r = '0' . $r;
		}
		if ( strlen( $g ) == 1 ) {
			$g = '0' . $g;
		}
		if ( strlen( $b ) == 1 ) {
			$b = '0' . $b;
		}

		return '#' . $r . $g . $b;
	}

	/**
	 * Extend the round PHP public static function for force a dot for all locales instead the comma.
	 *
	 * @param string $value
	 * @param string $approximation
	 *
	 * @return float
	 * @author WebFactory Ltd
	 */
	public static function round( $value, $approximation ) {
		$value = round( $value, $approximation );
		$value = str_replace( ',', '.', $value ); // Fixes locale comma
		$value = str_replace( ' ', '', $value ); // No space

		return $value;
	}

	/**
	 * Format nice URL depending service
	 *
	 * @param string $type
	 * @param string $term_name
	 *
	 * @return string
	 */
	public static function format_external_tag( $type = '', $term_name = '' ) {
		if ( empty( $term_name ) ) {
			return '';
		}

		$term_name = esc_html( $term_name );
		switch ( $type ) {
			case 'technorati':
				return '<a class="tag_technorati" href="' . esc_url( 'http://technorati.com/tag/' . str_replace( ' ', '+', $term_name ) ) . '" rel="tag">' . $term_name . '</a>';
				break;
			case 'flickr':
				return '<a class="tag_flickr" href="' . esc_url( 'http://www.flickr.com/photos/tags/' . preg_replace( '/[^a-zA-Z0-9]/', '', strtolower( $term_name ) ) . '/' ) . '" rel="tag">' . $term_name . '</a>';
				break;
			case 'delicious':
				return '<a class="tag_delicious" href="' . esc_url( 'http://del.icio.us/popular/' . strtolower( str_replace( ' ', '', $term_name ) ) ) . '" rel="tag">' . $term_name . '</a>';
				break;
			default:
				return '';
				break;
		}
	}
}
