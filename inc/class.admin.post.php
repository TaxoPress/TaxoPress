<?php

class SimpleTags_Admin_Post_Settings {
	/**
	 * Constructor
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public function __construct() {

        if( (1 === (int) SimpleTags_Plugin::get_option_value( 'active_auto_links' )) || (1 === (int) SimpleTags_Plugin::get_option_value( 'active_auto_terms') )  ){
		    // Save tags from advanced input
		    add_action( 'save_post', array( __CLASS__, 'save_post' ), 10, 1 );
    		// Box for advanced tags
	    	add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 10, 1 );
        }
	}

	/**
	 * Register a new box for TaxoPress settings
	 *
	 * @param string $post_type
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function add_meta_boxes( $post_type ) {
		// Get auto options
		$auto_options = get_option( STAGS_OPTIONS_NAME_AUTO );
		$taxonomies = get_object_taxonomies( $post_type );
		// Auto terms for this CPT ?
		add_meta_box( 'simpletags-settings', __( 'TaxoPress - Settings', 'simple-tags' ), array(
			__CLASS__,
			'metabox'
		), $post_type, 'side', 'low' );
	}

	/**
	 * Build HTML of form
	 *
	 * @param object $post
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function metabox( $post ) {
		if ( ! isset( $post->post_type ) ) {
			return;
		}

		// Get auto options
		$auto_options = get_option( STAGS_OPTIONS_NAME_AUTO );

		// Auto terms for this CPT ?
            if(1 === (int) SimpleTags_Plugin::get_option_value( 'active_auto_terms' )){
			    $meta_value = get_post_meta( $post->ID, '_exclude_autotags', true );
			    echo '<p>' . "\n";
			    echo '<label><input type="checkbox" name="exclude_autotags" value="true" ' . checked( $meta_value, true, false ) . ' /> ' . __( 'Disable Auto Terms', 'simple-tags' ) . '</label><br />' . "\n";
			    echo '</p>' . "\n";
			    echo '<input type="hidden" name="_meta_autotags" value="true" />';
            }

            if(1 === (int) SimpleTags_Plugin::get_option_value( 'active_auto_links' )){
    			$meta_value = get_post_meta( $post->ID, '_exclude_autolinks', true );
	    		echo '<p>' . "\n";
		    	echo '<label><input type="checkbox" name="exclude_autolinks" value="true" ' . checked( $meta_value, true, false ) . ' /> ' . __( 'Disable Auto Links', 'simple-tags' ) . '</label><br />' . "\n";
			    echo '</p>' . "\n";
			    echo '<input type="hidden" name="_meta_autolink" value="true" />';
            }
	}

	/**
	 * Save this settings in post meta, delete if no exclude, clean DB :)
	 *
	 * @param integer $object_id
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function save_post( $object_id = 0 ) {
		if ( isset( $_POST['_meta_autotags'] ) && 'true' === $_POST['_meta_autotags'] ) {
			if ( isset( $_POST['exclude_autotags'] ) ) {
				update_post_meta( $object_id, '_exclude_autotags', true );
			} else {
				delete_post_meta( $object_id, '_exclude_autotags' );
			}
		}

		if ( isset( $_POST['_meta_autolink'] ) && 'true' === $_POST['_meta_autolink'] ) {
			if ( isset( $_POST['exclude_autolinks'] ) ) {
				update_post_meta( $object_id, '_exclude_autolinks', true );
			} else {
				delete_post_meta( $object_id, '_exclude_autolinks' );
			}
		}
	}
}
