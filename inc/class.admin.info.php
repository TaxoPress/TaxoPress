<?php

class SimpleTags_Notification {
	/**
	 * Initialize Admin
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {

		// Admin menu
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );

		add_action( 'admin_init', array( __CLASS__, 'admin_init_ina' ) );

		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );


		add_action( 'wp_ajax_dismissed_notice_handler', array( __CLASS__, 'ajax_notice_handler') );

		// Load JavaScript and CSS
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

	}

	/**
	 * Init somes JS and CSS need for simple tags.
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function admin_enqueue_scripts() {

		global $pagenow;

		// Helper simple tags
		wp_register_script( 'st-helper-notification', STAGS_URL . '/assets/js/helper-notification.js', array( 'jquery' ), STAGS_VERSION );

		// Register location
		$wp_post_pages = array( 'post.php', 'post-new.php' );
		$wp_page_pages = array( 'page.php', 'page-new.php' );


			wp_enqueue_script( 'jquery-ui-tabs' );
			wp_enqueue_script( 'st-helper-notification' );

	}

	public static function admin_init() {


		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'simple-tags-active-tracking') {
			return;
		}


		check_admin_referer( 'st-active-tracking' );



		$options = SimpleTags_Plugin::get_option();

        $options['use_tracking'] = 1;

		SimpleTags_Plugin::set_option( $options );

		update_option('dismissed-prefix_deprecated', TRUE);

	}

	public static function admin_init_ina() {


		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'simple-tags-inactive-tracking') {
			return;
		}
		check_admin_referer( 'st-inactive-tracking' );


		update_option('dismissed-prefix_deprecated', TRUE);

	}


	public static function admin_notices() {

		global $pagenow;


		$active_url = wp_nonce_url( add_query_arg( array( 'action' => 'simple-tags-active-tracking' ) ), 'st-active-tracking' );

		$inactive_url = wp_nonce_url( add_query_arg( array( 'action' => 'simple-tags-inactive-tracking' ) ), 'st-inactive-tracking' );

		add_option('dismissed-prefix_deprecated');


		if ( $pagenow == 'index.php' ) {

			if ( current_user_can( 'manage_options' ) ) {


				//if ( has_filter( 'prefix_filter' ) ) {
					// Check if it's been dismissed

					if (! get_option( 'dismissed-prefix_deprecated', false ) && (int) SimpleTags_Plugin::get_option_value( 'use_tracking' ) != 1 ) {

						?>

                        <div id="my-notice" class=" updated notice notice-my-class is-dismissible" data-notice="prefix_deprecated">
                            <h1>Simple tags - Suivi d'utilisation</h1>
                            <p>Cette nouvelle fonctionnalité nous permets de récuperer vos données d'utilisation afin de
                                vous
                                fournir de meilleur service</p>
                            <br>
                            <a class="button" href="<?php echo esc_url( $active_url ); ?>">Activer</a>
                            <a class="button" href="<?php echo esc_url( $inactive_url ); ?>">Ne pas activer</a>

                            <br><br>
                        </div>
						<?php


					}
				}
			}

		//}
	}

public static function ajax_notice_handler() {

	// Pick up the notice "type" - passed via jQuery (the "data-notice" attribute on the notice)
	$type = $_POST['type'];
	// Store it in the options table
	update_option( 'dismissed-' . $type, TRUE );

	}
}




