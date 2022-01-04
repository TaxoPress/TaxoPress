<?php

class SimpleTags_Client_Autoterms {
	/**
	 * Constructor
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public function __construct() {
		if ( 1 === (int) SimpleTags_Plugin::get_option_value( 'active_auto_terms' ) ) {
		    add_action( 'save_post', array( __CLASS__, 'save_post' ), 12, 2 );
		    add_action( 'post_syndicated_item', array( __CLASS__, 'save_post' ), 12, 2 );
        }
	}

	/**
	 * Check post/page content for auto terms
	 *
	 * @param integer $post_id
	 * @param object $object
	 *
	 * @return boolean
	 */
	public static function save_post( $post_id = null, $object = null ) {

		// Get options
		$options = get_option( STAGS_OPTIONS_NAME_AUTO );

		// user preference for this post ?
		$meta_value = isset($_POST['exclude_autotags']) ? sanitize_text_field($_POST['exclude_autotags']) : false;
		if ( $meta_value ) {
			return false;
		}

        if(!is_object($object)){
            return;
        }

        if(!isset($object->post_type)){
            return;
        }

		// Loop option for find if autoterms is actived on any taxonomy and post type
        $current_post_type = $object->post_type;
        $current_post_status = $object->post_status;
        $autoterms = taxopress_get_autoterm_data();
        $flag = false;
        foreach($autoterms as $autoterm_key => $autoterm_data){
            $eligible_post_status = isset($autoterm_data['post_status']) && is_array($autoterm_data['post_status']) ? $autoterm_data['post_status'] : ['publish'];
            $eligible_post_types = isset($autoterm_data['post_types']) && is_array($autoterm_data['post_types']) ? $autoterm_data['post_types'] : [];
            $eligible_post_types = array_filter($eligible_post_types);

            if(count($eligible_post_types) === 0){
                break;
            }
            if(!in_array($current_post_type, $eligible_post_types)){
                continue;
            }
            if(!in_array($current_post_status, $eligible_post_status)){
                continue;
            }

            self::auto_terms_post( $object, $autoterm_data['taxonomy'], $autoterm_data );
			$flag = true;
        }

		if ( $flag == true ) { // Clean cache ?
			clean_post_cache( $post_id );
		}

		return true;
	}

	/**
	 * Automatically tag a post/page from the database terms for the taxonomy specified
	 *
	 * @param object $object
	 * @param string $taxonomy
	 * @param array $options
	 * @param boolean $counter
	 *
	 * @return boolean
	 * @author WebFactory Ltd
	 */
	public static function auto_terms_post( $object, $taxonomy = 'post_tag', $options = array(), $counter = false ) {
		global $wpdb;

		// Option exists ?
		if ( $options == false || empty( $options ) ) {
			return false;
		}

		if ( get_the_terms( $object->ID, $taxonomy ) != false && (int)$options['autoterm_target'] === 1 ) {
			return false; // Skip post with terms, if term only empty post option is checked
		}

		$autoterm_exclude =  isset($options['autoterm_exclude']) ? taxopress_change_to_array($options['autoterm_exclude']) : [];

		$terms_to_add = array();

        
        if( isset($options['autoterm_from']) && $options['autoterm_from'] === 'post_title' ){
            $content = $object->post_title;
        }elseif( isset($options['autoterm_from']) && $options['autoterm_from'] === 'post_content' ){
            $content = $object->post_content;
        }else{
		    $content = $object->post_content . ' ' . $object->post_title;
        }

		if ( isset( $object->post_excerpt ) ) {
			$content .= ' ' . $object->post_excerpt;
		}

		$content = trim( strip_tags( $content ) );
		if ( empty( $content ) ) {
			return false;
		}

        $autoterm_use_dandelion  = isset($options['autoterm_use_dandelion']) ? (int)$options['autoterm_use_dandelion'] : 0;
        $autoterm_use_opencalais = isset($options['autoterm_use_opencalais']) ? (int)$options['autoterm_use_opencalais'] : 0;

		//Autoterm with Dandelion
        if ( $autoterm_use_dandelion > 0 && $options['terms_datatxt_access_token'] !== '' ) {
			$request_ws_args = array();
            $request_ws_args['text'] = $content;
			// Custom confidence ?
			$request_ws_args['min_confidence'] = 0.6;
			if ( $options['terms_datatxt_min_confidence'] != "" ) {
				$request_ws_args['min_confidence'] = $options['terms_datatxt_min_confidence'];
			}
			$request_ws_args['token'] = $options['terms_datatxt_access_token'];
			// Build params
			$response = wp_remote_post( 'https://api.dandelion.eu/datatxt/nex/v1', array(
				'user-agent' => 'WordPress simple-tags',
				'body'       => $request_ws_args
			) );
			$data = false;
			if ( ! is_wp_error( $response ) && $response != null ) {
				if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
					$data = wp_remote_retrieve_body( $response );
				}
			}
			if($data){
				$data = json_decode( $data );
				$data = is_object($data) ? $data->annotations : '';
				if ( !empty( $data ) ) {
					foreach ( (array) $data as $term ) {
						$term = $term->title;
						$term = stripslashes( $term );

						if ( ! is_string( $term ) ) {
							continue;
						}
		
						$term = trim( $term );
						if ( empty( $term ) ) {
							continue;
						}
						
						//check if term belong to the post already
						if(has_term( $term, $taxonomy, $object )){
							continue;
						}
		
						//exclude if name found in exclude terms
						if(in_array($term, $autoterm_exclude)){
							continue;
						}

						// Whole word ?
						if ( isset( $options['autoterm_word'] ) && (int) $options['autoterm_word'] == 1 ) {
							if(strpos($content, ' '.$term.' ') !== FALSE)
							{
								$terms_to_add[] = $term;
							}
		
							//make exception for hashtag special character
							if (substr($term, 0, strlen('#')) === '#') {
								$trim_term = ltrim($term, '#');
								if ( preg_match( "/\B(\#+$trim_term\b)(?!;)/i", $content ) ) {
									$terms_to_add[] = $term;
								}
							}
		
							if ( isset( $options['autoterm_hash'] ) && (int) $options['autoterm_hash'] == 1 && stristr( $content, '#' . $term ) ) {
								$terms_to_add[] = $term;
							}
						} elseif ( stristr( $content, $term ) ) {
							$terms_to_add[] = $term;
						}
					}
				}
			}
        }

		//Autoterm with OpenCalais
		if ( $autoterm_use_opencalais > 0 && $options['terms_opencalais_key'] !== '' ) {
			$response = wp_remote_post( 'https://api-eit.refinitiv.com/permid/calais', array(
				'timeout' => 30,
				'headers' => array(
					'X-AG-Access-Token' => $options['terms_opencalais_key'],
					'Content-Type'      => 'text/html',
					'outputFormat'      => 'application/json'
				),
				'body'    => $content
			) );
			$data = false;
			if ( ! is_wp_error( $response ) && $response != null ) {
				if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
					$data_raw = json_decode( wp_remote_retrieve_body( $response ), true );
					$data = array();
					if ( isset( $data_raw ) && is_array( $data_raw ) ) {
						foreach ( $data_raw as $_data_raw ) {
							if ( isset( $_data_raw['_typeGroup'] ) && $_data_raw['_typeGroup'] == 'socialTag' ) {
								$data[] = $_data_raw['name'];
							}
						}
					}
				}
			}
			if($data){
				if (!empty( $data )){
					// Remove empty terms
					$data = array_filter( $data, '_delete_empty_element' );
					$data = array_unique( $data );
			
					foreach ( (array) $data as $term ) {
						$term = stripslashes( $term );

						if ( ! is_string( $term ) ) {
							continue;
						}
		
						$term = trim( $term );
						if ( empty( $term ) ) {
							continue;
						}
						
						//check if term belong to the post already
						if(has_term( $term, $taxonomy, $object )){
							continue;
						}
		
						//exclude if name found in exclude terms
						if(in_array($term, $autoterm_exclude)){
							continue;
						}

						// Whole word ?
						if ( isset( $options['autoterm_word'] ) && (int) $options['autoterm_word'] == 1 ) {
							if(strpos($content, ' '.$term.' ') !== FALSE)
							{
								$terms_to_add[] = $term;
							}
		
							//make exception for hashtag special character
							if (substr($term, 0, strlen('#')) === '#') {
								$trim_term = ltrim($term, '#');
								if ( preg_match( "/\B(\#+$trim_term\b)(?!;)/i", $content ) ) {
									$terms_to_add[] = $term;
								}
							}
		
							if ( isset( $options['autoterm_hash'] ) && (int) $options['autoterm_hash'] == 1 && stristr( $content, '#' . $term ) ) {
								$terms_to_add[] = $term;
							}
						} elseif ( stristr( $content, $term ) ) {
							$terms_to_add[] = $term;
						}
					}
				}
			}

        }

		// Auto term with specific auto terms list
		if ( isset( $options['specific_terms'] ) && isset( $options['autoterm_useonly'] ) && (int)$options['autoterm_useonly'] === 1 ) {
			$terms = maybe_unserialize( $options['specific_terms'] );
            $terms = taxopress_change_to_array($terms);
			foreach ( $terms as $term ) {
				if ( ! is_string( $term ) ) {
					continue;
				}

				$term = trim( $term );
				if ( empty( $term ) ) {
					continue;
				}

				//check if term belong to the post already
				if(has_term( $term, $taxonomy, $object )){
					continue;
				}

                //exclude if name found in exclude terms
                if(in_array($term, $autoterm_exclude)){
					continue;
                }

				// Whole word ?
				if ( isset( $options['autoterm_word'] ) && (int)$options['autoterm_word'] === 1 ) {
					if(strpos($content, ' '.$term.' ') !== FALSE)
					{
						$terms_to_add[] = $term;
					}

                    //make exception for hashtag special character
                    if (substr($term, 0, strlen('#')) === '#') {
                        $trim_term = ltrim($term, '#');
					    if ( preg_match( "/\B(\#+$trim_term\b)(?!;)/i", $content ) ) {
						    $terms_to_add[] = $term;
					    }
                    }

					if ( isset( $options['autoterm_hash'] ) && (int)$options['autoterm_hash'] === 1 && stristr( $content, '#' . $term ) ) {
						$terms_to_add[] = $term;
					}
				} elseif ( stristr( $content, $term ) ) {
					$terms_to_add[] = $term;
				}
			}
			unset( $terms, $term );
		}

		// Auto terms with all terms
		if ( isset( $options['autoterm_useall'] ) && (int)$options['autoterm_useall'] === 1 ) {
			// Get all terms
			$terms = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT name
				FROM {$wpdb->terms} AS t
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
				WHERE tt.taxonomy = %s", $taxonomy ) );

			$terms = array_unique( $terms );

			foreach ( $terms as $term ) {
				$term = stripslashes( $term );

				if ( ! is_string( $term ) ) {
					continue;
				}

				$term = trim( $term );
				if ( empty( $term ) ) {
					continue;
				}
				
				//check if term belong to the post already
				if(has_term( $term, $taxonomy, $object )){
					continue;
				}

                //exclude if name found in exclude terms
                if(in_array($term, $autoterm_exclude)){
					continue;
                }

				// Whole word ?
				if ( isset( $options['autoterm_word'] ) && (int) $options['autoterm_word'] == 1 ) {
					if(strpos($content, ' '.$term.' ') !== FALSE)
					{
						$terms_to_add[] = $term;
					}

                    //make exception for hashtag special character
                    if (substr($term, 0, strlen('#')) === '#') {
                        $trim_term = ltrim($term, '#');
					    if ( preg_match( "/\B(\#+$trim_term\b)(?!;)/i", $content ) ) {
						    $terms_to_add[] = $term;
					    }
                    }

					if ( isset( $options['autoterm_hash'] ) && (int) $options['autoterm_hash'] == 1 && stristr( $content, '#' . $term ) ) {
						$terms_to_add[] = $term;
					}
				} elseif ( stristr( $content, $term ) ) {
					$terms_to_add[] = $term;
				}
			}
		}

		// Append terms if terms to add
		if ( ! empty( $terms_to_add ) ) {
			// Remove empty and duplicate elements
			$terms_to_add = array_filter( $terms_to_add, '_delete_empty_element' );
			$terms_to_add = array_unique( $terms_to_add );

			//auto terms limit
			$terms_limit = isset($options['terms_limit']) ? (int)$options['terms_limit'] : 0;
			if($terms_limit > 0 && count($terms_to_add) > $terms_limit){
				$terms_to_add = array_slice($terms_to_add, 0, $terms_limit);
			}

			if ( $counter == true ) {
				// Increment counter
				$counter = ( (int) get_option( 'tmp_auto_terms_st' ) ) + count( $terms_to_add );
				update_option( 'tmp_auto_terms_st', $counter );
			}

			// Add terms to posts
			wp_set_object_terms( $object->ID, $terms_to_add, $taxonomy, true );

			// Clean cache
			clean_post_cache( $object->ID );

			return true;
		}

		return false;
	}

}
