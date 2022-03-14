<?php

class SimpleTags_Client_Autolinks {

	public static $posts = array();
	public static $link_tags = array();
	public static $tagged_link_count = 0;

	/**
	 * Constructor
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public function __construct() {

		if ( 1 === (int) SimpleTags_Plugin::get_option_value( 'active_auto_links' ) ) {

    		$auto_link_priority = SimpleTags_Plugin::get_option_value( 'auto_link_priority' );
	    	if ( 0 === (int) $auto_link_priority ) {
		    	$auto_link_priority = 12;
		    }
            
		    // Auto link tags
		    add_filter( 'the_posts', array( __CLASS__, 'the_posts' ), 10 );

            //legacy
	    	if ( 'no' !== SimpleTags_Plugin::get_option_value( 'auto_link_views' )  && (int)SimpleTags_Plugin::get_option_value( 'auto_link_tags' ) > 0 ) {
		    	add_filter( 'the_content', array( __CLASS__, 'the_content' ), $auto_link_priority );
			    add_filter( 'the_title', array( __CLASS__, 'the_title' ) );
		    }

            //new UI
            add_filter('the_content', array( __CLASS__, 'taxopress_autolinks_the_content'), 12);
            add_filter('the_title', array( __CLASS__, 'taxopress_autolinks_the_title'), 12);
    }

	}

	/**
	 * Stock posts ID as soon as possible
	 * TODO: test if post_type allow post_tag before keep post ID
	 *
	 * @param array $posts
	 *
	 * @return array
	 */
	public static function the_posts( $posts ) {
		if ( ! empty( $posts ) && is_array( $posts ) ) {
			foreach ( (array) $posts as $post ) {
				self::$posts[] = (int) $post->ID;
			}

			self::$posts = array_unique( self::$posts );
		}

		return $posts;
	}

	/**
	 * Get tags from current post views
	 *
	 * @return array
	 */
	public static function get_tags_from_current_posts($options = false) {

		if ( is_array( self::$posts ) && count( self::$posts ) > 0 ) {
			// Generate SQL from post id
			$postlist = implode( "', '", self::$posts );

			// Generate key cache
			$key = md5( maybe_serialize( $postlist ) );

			$results = array();

            if($options){
                $term_taxonomy = $options['taxonomy'];
            }else{
                $term_taxonomy = 'post_tag';
            }

			// Get cache if exist
			$cache = wp_cache_get( 'generate_keywords', 'simple-tags' );
			if ( $options || false === $cache )
            {
				foreach ( self::$posts as $object_id ) {
					// Get terms
					$terms = get_object_term_cache( $object_id, $term_taxonomy );
					if ( false === $terms || is_wp_error( $terms ) ) {
						$terms = wp_get_object_terms( $object_id, $term_taxonomy );
					}

					if ( false !== $terms && ! is_wp_error( $terms ) ) {
						$results = array_merge( $results, $terms );
					}
				}

				$cache[ $key ] = $results;
				wp_cache_set( 'generate_keywords', $cache, 'simple-tags' );
			} else {
				if ( isset( $cache[ $key ] ) ) {
					return $cache[ $key ];
				}
			}

			return $results;
		}

		return array();
	}

	/**
	 * Get all available local tags
	 *
	 * @return array
	 */
	public static function get_all_post_tags($options = false) {
		if ( is_array( self::$posts ) && count( self::$posts ) > 0 ) {
			// Generate SQL from post id
			$postlist = implode( "', '", self::$posts );

			// Generate key cache
			$key = md5( maybe_serialize( $postlist ) );

            if($options){
                $term_taxonomy = $options['taxonomy'];
            }else{
                $term_taxonomy = 'post_tag';
            }
            $results = get_tags(['taxonomy' => $term_taxonomy]);
			// Get cache if exist
			$cache = wp_cache_get( 'generate_keywords', 'simple-tags' );
			if ( $options || false === $cache ) {
				foreach ( self::$posts as $object_id ) {
					// Get terms
					$terms = get_object_term_cache( $object_id, $term_taxonomy );
					if ( false === $terms || is_wp_error( $terms ) ) {
						$terms = wp_get_object_terms( $object_id, $term_taxonomy );
					}

					if ( false !== $terms && ! is_wp_error( $terms ) ) {
						$results = array_merge( $results, $terms );
					}
				}

				$cache[ $key ] = $results;
				wp_cache_set( 'generate_keywords', $cache, 'simple-tags' );
			} else {
				if ( isset( $cache[ $key ] ) ) {
					return $cache[ $key ];
				}
			}

			return $results;
		}

		return array();
	}

	/**
	 * Get links for each tag for auto link feature
	 *
	 */
	public static function prepare_auto_link_tags($options = false) {
        global $post;
        if($options){
		    $auto_link_min = (int) $options['autolink_usage_min'];
            $unattached_terms = (int) $options['unattached_terms'];
            $autolink_min_char = (int) $options['autolink_min_char'];
            $autolink_max_char = (int) $options['autolink_max_char'];
            $ignore_attached = (int) $options['ignore_attached'];
            $term_taxonomy = $options['taxonomy'];
        }else{
		    $auto_link_min = (int) SimpleTags_Plugin::get_option_value( 'auto_link_min' );
		    $unattached_terms  = (int) SimpleTags_Plugin::get_option_value( 'auto_link_all' );
            $autolink_min_char = 0;
            $autolink_max_char = 0;
            $ignore_attached   = 0;
            $term_taxonomy = 'post_tag';
        }

		if ( 0 === $auto_link_min ) {
			$auto_link_min = 1;
		}
		if( 1 === $unattached_terms ){
			$terms = self::get_all_post_tags($options);
		}else{
			$terms = self::get_tags_from_current_posts($options);
		}

		foreach ( (array) $terms as $term ) {
            if($ignore_attached > 0){
                if(has_term( $term->term_id, $term_taxonomy, $post )){
                    continue;
                }
            }

                //min character check
                $min_char_pass = true;
                if($autolink_min_char > 0){
                    $min_char_pass = strlen($term->name) >= $autolink_min_char ? true : false;
                }
                //max character check
                $max_char_pass = true;
                if($autolink_max_char > 0){
                    $max_char_pass = strlen($term->name) <= $autolink_max_char ? true : false;
                }

			if ( $term->count >= $auto_link_min  && $min_char_pass && $max_char_pass ) {
				self::$link_tags[ $term->name ] = esc_url( get_term_link( $term, $term->taxonomy ) );
			}
		}

		return true;
	}

	/**
	 * Replace text by link to tag
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public static function the_content( $content = '' ) {
		global $post;

		// Show only on singular view ? Check context
		if ( 'singular' === SimpleTags_Plugin::get_option_value( 'auto_link_views' ) && ! is_singular() ) {
			return $content;
		}

		// Show only on single view ? Check context
		if ( 'single' === SimpleTags_Plugin::get_option_value( 'auto_link_views' ) && ! is_single() ) {
			return $content;
		}

		// user preference for this post ?
		$meta_value = get_post_meta( $post->ID, '_exclude_autolinks', true );
		if ( ! empty( $meta_value )  || is_admin() ) {
			return $content;
		}

		// Get currents tags if no exists
		self::prepare_auto_link_tags();

		// Shuffle array
		SimpleTags_Client::random_array( self::$link_tags );

		// HTML Rel (tag/no-follow)
		$rel = SimpleTags_Client::get_rel_attribut();

		// only continue if the database actually returned any links
		if ( ! isset( self::$link_tags ) || ! is_array( self::$link_tags ) || empty( self::$link_tags ) ) {
			return $content;
		}

		// Case option ?
		$case       = ( 1 === (int) SimpleTags_Plugin::get_option_value( 'auto_link_case' ) ) ? 'i' : '';
		$strpos_fnc = ( 'i' === $case ) ? 'stripos' : 'strpos';

		// Prepare exclude terms array
		$excludes_terms = explode( ',', SimpleTags_Plugin::get_option_value( 'auto_link_exclude' ) );
		if ( empty( $excludes_terms ) ) {
			$excludes_terms = array();
		} else {
			$excludes_terms = array_filter( $excludes_terms, '_delete_empty_element' );
			$excludes_terms = array_unique( $excludes_terms );
		}

		$z = 0;
		foreach ( (array) self::$link_tags as $term_name => $term_link ) {
			// Force string for tags "number"
			$term_name = (string) $term_name;

			// Exclude terms ? next...
			if ( in_array( $term_name, (array) $excludes_terms, true ) ) {
				continue;
			}

			// Make a first test with PHP function, economize CPU with regexp
			if ( false === $strpos_fnc( $content, $term_name ) ) {
				continue;
			}

			if ( 1 === (int) SimpleTags_Plugin::get_option_value( 'auto_link_dom' ) && class_exists( 'DOMDocument' ) && class_exists( 'DOMXPath' ) ) {
				self::replace_by_links_dom( $content, $term_name, $term_link, $case, $rel );
			} else {
				self::replace_by_links_regexp( $content, $term_name, $term_link, $case, $rel );


			}

			$z ++;

			if ( $z > (int) SimpleTags_Plugin::get_option_value( 'auto_link_max_by_post' ) ) {
				break;
			}
		}


		return $content;
	}

	/**
	 * Replace text by link, except HTML tag, and already text into link, use DOMdocument.
	 * https://stackoverflow.com/questions/4044812/regex-domdocument-match-and-replace-text-not-in-a-link
	 *  
	 * @param string $content
	 * @param string $search
	 * @param string $replace
	 * @param string $case
	 * @param string $rel
	 *
	 * @return void
	 */
	private static function replace_by_links_dom( &$content, $search = '', $replace = '', $case = '', $rel = '', $options = false ) {
		$dom = new DOMDocument();
        
        //replace html entity with their entity code
        foreach(taxopress_html_character_and_entity() as $enity => $code){
           $content = str_replace($enity, $code,$content);
        }
		$content = str_replace('&#','|--|',$content);//https://github.com/TaxoPress/TaxoPress/issues/824
        $content = str_replace('&','&#38;',$content); //https://github.com/TaxoPress/TaxoPress/issues/770*/
		//$content = utf8_decode($content);

        libxml_use_internal_errors(true);
		// loadXml needs properly formatted documents, so it's better to use loadHtml, but it needs a hack to properly handle UTF-8 encoding
		$result = $dom->loadHtml( mb_convert_encoding( $content, 'HTML-ENTITIES', "UTF-8" ) );

		if ( false === $result ) {
			return;
		}

		if($options){
            $autolink_case = $options['autolink_case'];
            $html_exclusion = $options['html_exclusion'];
            $exclude_class = $options['autolink_exclude_class'];
            $title_attribute = $options['autolink_title_attribute'];
            $same_usage_max = $options['autolink_same_usage_max'];
            $max_by_post = $options['autolink_usage_max'];
            $link_class = isset($options['link_class']) ? taxopress_format_class($options['link_class']) : '';
        }else{
            $autolink_case = 'lowercase';
            $html_exclusion = [];
            $exclude_class = '';
            $title_attribute = SimpleTags_Plugin::get_option_value( 'auto_link_title' );
            $same_usage_max = SimpleTags_Plugin::get_option_value( 'auto_link_max_by_tag' );
            $max_by_post = SimpleTags_Plugin::get_option_value( 'auto_link_max_by_post' );
            $link_class = '';
        }

        $html_exclusion[] = 'meta';
        $html_exclusion[] = 'link';
        $html_exclusion[] = 'head';

        //auto link exclusion
        $exclusion = '[not(ancestor::a)]';
        if(count($html_exclusion) > 0){
            foreach($html_exclusion as $exclude_ancestor){
                $exclusion .= '[not(ancestor::'.strtolower($exclude_ancestor).')]';
            }
        }

		// Prepare exclude terms array
		$excludes_class = explode( ',', $exclude_class );
		if ( !empty( $excludes_class ) ) {
			$excludes_class = array_filter( $excludes_class );
			$excludes_class = array_unique( $excludes_class );
            if(count($excludes_class) > 0){
                foreach($excludes_class as $idclass ){
                    if(substr( trim($idclass), 0, 1 ) === "#"){
                        $div_id = ltrim(trim($idclass), "#");
                        $exclusion .= "[not(ancestor::div/@id='$div_id')]";
                    }else{
                        $div_class = ltrim(trim($idclass), ".");
                        $exclusion .= "[not(ancestor::div/@class='$div_class')]";
                    }
                }
            }
		}


		$xpath = new DOMXPath( $dom );
		$j        = 0;
        $replaced_count = 0;
		foreach ( $xpath->query( '//text()'.$exclusion.'' ) as $node ) {

			$substitute = '<a href="' . $replace . '" class="st_tag internal_tag '.$link_class.'" ' . $rel . ' title="' . esc_attr( sprintf( $title_attribute, $search ) ) . "\">$search</a>";
			$link_openeing = '<a href="' . $replace . '" class="st_tag internal_tag '.$link_class.'" ' . $rel . ' title="' . esc_attr( sprintf( $title_attribute, $search ) ) . "\">";
            $link_closing = '</a>';
            $upperterm = strtoupper($search);
            $lowerterm = strtolower($search);

            $remaining_usage = $max_by_post-self::$tagged_link_count;
            if( $same_usage_max > $remaining_usage){
                $same_usage_max = $remaining_usage;
            }

            if($same_usage_max > 0 ){
			if ( 'i' === $case ) {
                if($autolink_case === 'none'){//retain case
                    $replaced = preg_replace('/(?<!\w)' . preg_quote($search, "/") . '(?!\w)/i', "$link_openeing$0$link_closing", $node->wholeText, $same_usage_max, $rep_count);
                }elseif($autolink_case === 'uppercase'){//uppercase
                    $replaced = preg_replace('/(?<!\w)' . preg_quote($search, "/") . '(?!\w)/i', "$link_openeing$upperterm$link_closing", $node->wholeText, $same_usage_max, $rep_count);
                }elseif($autolink_case === 'termcase'){//termcase
                    $replaced = preg_replace('/(?<!\w)' . preg_quote($search, "/") . '(?!\w)/i', "$link_openeing$search$link_closing", $node->wholeText, $same_usage_max, $rep_count);
                }else {//lowercase
                    $replaced = preg_replace('/(?<!\w)' . preg_quote($search, "/") . '(?!\w)/i', "$link_openeing$lowerterm$link_closing", $node->wholeText, $same_usage_max, $rep_count);
                }
			} else {
				$replaced = str_replace( $search, $substitute, $node->wholeText );
			}

            if($replaced && !empty(trim($replaced))){
                $j ++;
               if($rep_count > 0){
                 $replaced_count = $replaced_count+$rep_count;
                 self::$tagged_link_count = self::$tagged_link_count+$rep_count;
               }
            }
			$newNode = $dom->createDocumentFragment();
			$newNode->appendXML( $replaced );
			$node->parentNode->replaceChild( $newNode, $node );
            if ( $replaced_count >= $same_usage_max || 0 === (int) $same_usage_max ) {// Limit replacement at 1 by default, or options value !
               break;
            }
            }else{
                break;
            }
		}

		// get only the body tag with its contents, then trim the body tag itself to get only the original content
		$content = mb_substr( $dom->saveHTML( $xpath->query( '//body' )->item( 0 ) ), 6, - 7, "UTF-8" );
		$content = str_replace('|--|','&#',$content);//https://github.com/TaxoPress/TaxoPress/issues/824
		$content = str_replace('&#60;','<',$content);
		$content = str_replace('&#62;','>',$content);
        foreach(taxopress_html_character_and_entity(true) as $enity => $code){
          $content = str_replace($enity, $code,$content);
        }
        $content = str_replace('&#38;','&',$content); //https://github.com/TaxoPress/TaxoPress/issues/770
        $content = str_replace(';amp;',';',$content); //https://github.com/TaxoPress/TaxoPress/issues/810
		
	}

	/**
	 * Replace text by link, except HTML tag, and already text into link, use PregEXP.
	 *
	 * @param string $content
	 * @param string $search
	 * @param string $replace
	 * @param string $case
	 * @param string $rel
	 */
	private static function replace_by_links_regexp( &$content, $search = '', $replace = '', $case = '', $rel = '', $options = false ) {

        if($options){
            $autolink_case = $options['autolink_case'];
            $html_exclusion = $options['html_exclusion'];
            $exclude_class = $options['autolink_exclude_class'];
            $title_attribute = $options['autolink_title_attribute'];
            $same_usage_max = $options['autolink_same_usage_max'];
            $max_by_post = $options['autolink_usage_max'];
            $link_class = isset($options['link_class']) ? taxopress_format_class($options['link_class']) : '';
        }else{
            $autolink_case = 'lowercase';
            $html_exclusion = [];
            $exclude_class = '';
            $title_attribute = SimpleTags_Plugin::get_option_value( 'auto_link_title' );
            $same_usage_max = SimpleTags_Plugin::get_option_value( 'auto_link_max_by_tag' );
            $max_by_post = SimpleTags_Plugin::get_option_value( 'auto_link_max_by_post' );
            $link_class = '';
        }

		$must_tokenize = true; // will perform basic tokenization
		$tokens        = null; // two kinds of tokens: markup and text

		$j        = 0;
		$filtered = ''; // will filter text token by token

		$match      = '/(\PL|\A)(' . preg_quote( $search, '/' ) . ')(\PL|\Z)\b/u' . $case;
		$substitute = '$1<a href="' . $replace . '" class="st_tag internal_tag '.$link_class.'" ' . $rel . ' title="' . esc_attr( sprintf( $title_attribute, $search ) ) . "\">$2</a>$3";

		//$match = "/\b" . preg_quote($search, "/") . "\b/".$case;
		//$substitute = '<a href="'.$replace.'" class="st_tag internal_tag '.$link_class.'" '.$rel.' title="'. esc_attr( sprintf( __('Posts tagged with %s', 'simple-tags'), $search ) )."\">$0</a>";
		// for efficiency only tokenize if forced to do so
		if ( $must_tokenize ) {
			// this regexp is taken from PHP Markdown by Michel Fortin: http://www.michelf.com/projects/php-markdown/
			$comment                = '(?s:<!(?:--.*?--\s*)+>)|';
			$processing_instruction = '(?s:<\?.*?\?>)|';
			$tag                    = '(?:<[/!$]?[-a-zA-Z0-9:]+\b(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*>)';

			$markup        = $comment . $processing_instruction . $tag;
			$flags         = PREG_SPLIT_DELIM_CAPTURE;
			$tokens        = preg_split( "{($markup)}", $content, - 1, $flags );
			$must_tokenize = false;

		}

		// there should always be at least one token, but check just in case
		$anchor_level = 0;

		if ( isset( $tokens ) && is_array( $tokens ) && count( $tokens ) > 0 ) {
			$i = 0;
            $ancestor = '';
			foreach ( $tokens as $token ) {
                if ( ++ $i % 2 && $token !== '' )
                { // this token is (non-markup) text


                    $pass_check = true;

                    if(!empty(trim($ancestor))){

                    //auto link exclusion
                    if(count($html_exclusion) > 0){
                        foreach($html_exclusion as $exclude_ancestor){
                            if(taxopress_starts_with( $ancestor, '<'.strtolower($exclude_ancestor).'' )){
                                $pass_check = false;
                                break;
                            }
                        }
                    }


		            // Prepare exclude terms array
		            $excludes_class = explode( ',', $exclude_class );
		            if ( !empty( $excludes_class ) ) {
			            $excludes_class = array_filter( $excludes_class );
			            $excludes_class = array_unique( $excludes_class );
                        if(count($excludes_class) > 0){
                            foreach($excludes_class as $idclass ){
                                if(substr( trim($idclass), 0, 1 ) === "#"){
                                    $div_id = ltrim(trim($idclass), "#");
                                    if ( preg_match_all('/<[a-z \'"]*id="'.$div_id.'"/i', $ancestor, $matches) || preg_match_all('/<[a-z \'"]*id=\''.$div_id.'\'/i', $ancestor, $matches)) {
                                        $pass_check = false;
                                        break;
                                    }
                                }else{
                                    $div_class = ltrim(trim($idclass), ".");
                                    if ( preg_match_all('/<[a-z ]*class="'.$div_class.'"/i', $ancestor, $matches) || preg_match_all('/<[a-z ]*class=\''.$div_class.'\'/i', $ancestor, $matches)) {
                                        $pass_check = false;
                                        break;
                                    }
                                }
                            }
                        }
		            }


                    }
					if ( $anchor_level === 0 && $pass_check ) { // linkify if not inside anchor tags
						if ( preg_match( $match, $token ) ) { // use preg_match for compatibility with PHP 4
							$j ++;


                            $remaining_usage = $max_by_post-self::$tagged_link_count;
                            if( $same_usage_max > $remaining_usage){
                                $same_usage_max = $remaining_usage;
                            }


							if ( $same_usage_max > 0 ) {// Limit replacement at 1 by default, or options value !
								$token = preg_replace( $match, $substitute, $token, $same_usage_max, $rep_count ); // only PHP 5 supports calling preg_replace with 5 arguments
                                self::$tagged_link_count = self::$tagged_link_count+$rep_count;
							}
							$must_tokenize = true; // re-tokenize next time around
						}
                }
				} else { // this token is markup
					if ( preg_match( "#<\s*a\s+[^>]*>#i", $token ) ) { // found <a ...>
                        $ancestor = $token;
						$anchor_level ++;
					} elseif ( preg_match( "#<\s*/\s*a\s*>#i", $token ) ) { // found </a>
						$anchor_level --;
					}elseif(taxopress_starts_with( $token, "</" )){
                        $ancestor = '';
                    }else{
                        $ancestor = $token;
                    }
				}
				$filtered .= $token; // this token has now been filtered
			}
			$content = $filtered; // filtering completed for this link
		}
	}

	/**
	 * Replace text of title by link to tag
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public static function the_title( $title = '' ) {
		global $post;

		if ( 0 === (int) SimpleTags_Plugin::get_option_value( 'auto_link_title_excl' ) ) {
			return $title;
		}

		// Show only on singular view ? Check context
		if ( 'singular' === SimpleTags_Plugin::get_option_value( 'auto_link_views' ) && ! is_singular() ) {
			return $title;
		}

		// Show only on single view ? Check context
		if ( 'single' === SimpleTags_Plugin::get_option_value( 'auto_link_views' ) && ! is_single() ) {
			return $title;
		}

		// user preference for this post ?
		$meta_value = get_post_meta( $post->ID, '_exclude_autolinks', true );
		if ( ! empty( $meta_value ) || is_admin() ) {
			return $title;
		}

		// Get currents tags if no exists
		self::prepare_auto_link_tags();

		// Shuffle array
		SimpleTags_Client::random_array( self::$link_tags );

		// HTML Rel (tag/no-follow)
		$rel = SimpleTags_Client::get_rel_attribut();

		// only continue if the database actually returned any links
		if ( ! isset( self::$link_tags ) || ! is_array( self::$link_tags ) || empty( self::$link_tags ) ) {
			return $title;
		}

		// Case option ?
		$case       = ( 1 === (int) SimpleTags_Plugin::get_option_value( 'auto_link_case' ) ) ? 'i' : '';
		$strpos_fnc = ( 'i' === $case ) ? 'stripos' : 'strpos';

		// Prepare exclude terms array
		$excludes_terms = explode( ',', SimpleTags_Plugin::get_option_value( 'auto_link_exclude' ) );
		if ( empty( $excludes_terms ) ) {
			$excludes_terms = array();
		} else {
			$excludes_terms = array_filter( $excludes_terms, '_delete_empty_element' );
			$excludes_terms = array_unique( $excludes_terms );
		}


		$z = 0;
		foreach ( (array) self::$link_tags as $term_name => $term_link ) {
			// Force string for tags "number"
			$term_name = (string) $term_name;

			// Exclude terms ? next...
			if ( in_array( $term_name, (array) $excludes_terms, true ) ) {
				continue;
			}

			// Make a first test with PHP function, economize CPU with regexp
			if ( false === $strpos_fnc( $title, $term_name ) ) {
				continue;
			}

			if ( 1 === (int) SimpleTags_Plugin::get_option_value( 'auto_link_dom' ) && class_exists( 'DOMDocument' ) && class_exists( 'DOMXPath' ) ) {
				self::replace_by_links_dom( $title, $term_name, $term_link, $case, $rel );
			} else {
				self::replace_by_links_regexp( $title, $term_name, $term_link, $case, $rel );
			}

			$z ++;

			if ( $z > (int) SimpleTags_Plugin::get_option_value( 'auto_link_max_by_post' ) ) {
				break;
			}
		}

		return $title;
	}


	/**
	 * Replace text by link to tag
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public static function taxopress_autolinks_the_content( $content = '' ) {
		global $post;


		if(!is_object($post) || is_admin() ){
			return $content;
		}

        $post_tags = taxopress_get_autolink_data();


		// user preference for this post ?
		$meta_value = get_post_meta( $post->ID, '_exclude_autolinks', true );
		if ( ! empty( $meta_value ) ) {
			return $content;
		}

        if (count($post_tags) > 0) {

        foreach ($post_tags as $post_tag) {

            // Get option
            $embedded = (isset($post_tag['embedded']) && is_array($post_tag['embedded']) && count($post_tag['embedded']) > 0) ? $post_tag['embedded'] : false;

            if (!$embedded) {
                continue;
            }

            if (!in_array($post->post_type, $embedded )) {
                continue;
            }

            if ($post_tag['autolink_display'] === 'post_title') {
                continue;
            }

        //reset added article link count
        self::$tagged_link_count = 0;
        //reset tags just in case
        self::$link_tags = [];
		// Get currents tags if no exists
		self::prepare_auto_link_tags($post_tag);

		// Shuffle array
		SimpleTags_Client::random_array( self::$link_tags );

		// HTML Rel (tag/no-follow)
		$rel = SimpleTags_Client::get_rel_attribut();

		// only continue if the database actually returned any links
		if ( ! isset( self::$link_tags ) || ! is_array( self::$link_tags ) || empty( self::$link_tags ) ) {
			$can_continue = false;
		}else{
			$can_continue = true;
        }

        if( $can_continue ){

		// Case option ?
		$case       = ( 1 === (int) $post_tag['ignore_case'] ) ? 'i' : '';
		$strpos_fnc = ( 'i' === $case ) ? 'stripos' : 'strpos';

		// Prepare exclude terms array
		$excludes_terms = explode( ',', $post_tag['auto_link_exclude'] );
		if ( empty( $excludes_terms ) ) {
			$excludes_terms = array();
		} else {
			$excludes_terms = array_filter( $excludes_terms, '_delete_empty_element' );
			$excludes_terms = array_unique( $excludes_terms );
		}

		$z = 0;
		foreach ( (array) self::$link_tags as $term_name => $term_link ) {
			$z ++;
			// Force string for tags "number"
			$term_name = (string) $term_name;

			// Exclude terms ? next...
			if ( in_array( $term_name, (array) $excludes_terms, true ) ) {
				continue;
			}

			// Make a first test with PHP function, economize CPU with regexp
			if ( false === $strpos_fnc( $content, $term_name ) ) {
				continue;
			}

			if ( 1 === (int) $post_tag['autolink_dom'] && class_exists( 'DOMDocument' ) && class_exists( 'DOMXPath' ) ) {
				self::replace_by_links_dom( $content, $term_name, $term_link, $case, $rel, $post_tag );
			}else if ( class_exists( 'DOMDocument' ) && class_exists( 'DOMXPath' ) ) {//force php dom if class exist for optimization purpose
				self::replace_by_links_dom( $content, $term_name, $term_link, $case, $rel, $post_tag );
			} else {
				self::replace_by_links_regexp( $content, $term_name, $term_link, $case, $rel, $post_tag );
			}
			if ( self::$tagged_link_count >= (int) $post_tag['autolink_usage_max'] ) {
				break;
			}
		}
    }
    }

    }

		return $content;
	}




	/**
	 * Replace text by link to tag
	 *
	 * @param string $title
	 *
	 * @return string
	 */
	public static function taxopress_autolinks_the_title( $title = '' ) {
		global $post;

		if(!is_object($post) || is_admin() ){
			return $title;
		}

        $post_tags = taxopress_get_autolink_data();


		// user preference for this post ?
		$meta_value = get_post_meta( $post->ID, '_exclude_autolinks', true );
		if ( ! empty( $meta_value ) ) {
			return $title;
		}

        if (count($post_tags) > 0) {

        foreach ($post_tags as $post_tag) {

            // Get option
            $embedded = (isset($post_tag['embedded']) && is_array($post_tag['embedded']) && count($post_tag['embedded']) > 0) ? $post_tag['embedded'] : false;

            if (!$embedded) {
                continue;
            }

            if (!in_array($post->post_type, $embedded )) {
                continue;
            }

            if ($post_tag['autolink_display'] === 'post_content') {
                continue;
            }
        //reset added article link count
        self::$tagged_link_count = 0;
        //reset tags just in case
        self::$link_tags = [];
		// Get currents tags if no exists
		self::prepare_auto_link_tags($post_tag);

		// Shuffle array
		SimpleTags_Client::random_array( self::$link_tags );

		// HTML Rel (tag/no-follow)
		$rel = SimpleTags_Client::get_rel_attribut();

		// only continue if the database actually returned any links
		if ( ! isset( self::$link_tags ) || ! is_array( self::$link_tags ) || empty( self::$link_tags ) ) {
			$can_continue = false;
		}else{
			$can_continue = true;
        }

        if( $can_continue ){

		// Case option ?
		$case       = ( 1 === (int) $post_tag['ignore_case'] ) ? 'i' : '';
		$strpos_fnc = ( 'i' === $case ) ? 'stripos' : 'strpos';

		// Prepare exclude terms array
		$excludes_terms = explode( ',', $post_tag['auto_link_exclude'] );
		if ( empty( $excludes_terms ) ) {
			$excludes_terms = array();
		} else {
			$excludes_terms = array_filter( $excludes_terms, '_delete_empty_element' );
			$excludes_terms = array_unique( $excludes_terms );
		}

		$z = 0;
		foreach ( (array) self::$link_tags as $term_name => $term_link ) {
			$z ++;
			// Force string for tags "number"
			$term_name = (string) $term_name;

			// Exclude terms ? next...
			if ( in_array( $term_name, (array) $excludes_terms, true ) ) {
				continue;
			}

			// Make a first test with PHP function, economize CPU with regexp
			if ( false === $strpos_fnc( $title, $term_name ) ) {
				continue;
			}

			if ( 1 === (int) $post_tag['autolink_dom'] && class_exists( 'DOMDocument' ) && class_exists( 'DOMXPath' ) ) {
				self::replace_by_links_dom( $title, $term_name, $term_link, $case, $rel, $post_tag );
			}else if ( class_exists( 'DOMDocument' ) && class_exists( 'DOMXPath' ) ) {//force php dom if class exist for optimization purpose
				self::replace_by_links_dom( $title, $term_name, $term_link, $case, $rel, $post_tag );
			} else {
				self::replace_by_links_regexp( $title, $term_name, $term_link, $case, $rel, $post_tag );
			}

			if ( self::$tagged_link_count >= (int) $post_tag['autolink_usage_max'] ) {
				break;
			}
		}
    }
    }

    }


		return $title;
	}

}
