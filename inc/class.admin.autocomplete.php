<?php

class SimpleTags_Admin_Autocomplete {

	public function __construct() {
		// Ajax action, JS Helper and admin action
		add_action( 'wp_ajax_simpletags', array( __CLASS__, 'ajax_check' ) );

		// TaxoPress hook
		add_action( 'simpletags-auto_terms', array( __CLASS__, 'auto_terms_js' ) );
		add_action( 'simpletags-manage_terms', array( __CLASS__, 'manage_terms_js' ) );
		add_action( 'simpletags-mass_terms', array( __CLASS__, 'mass_terms_js' ) );

		// Javascript
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ), 11 );
	}

	/**
	 * Init some JS and CSS need for this feature
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function admin_enqueue_scripts() {
		global $pagenow;

		// Register JS/CSS
		wp_register_script(
			'st-helper-autocomplete',
			STAGS_URL . '/assets/js/helper-autocomplete.js',
			array(
				'jquery',
				'jquery-ui-autocomplete',
			),
			STAGS_VERSION
		);

		// Declare locations
		$wp_post_pages = array( 'post.php', 'post-new.php' );
		$wp_page_pages = array( 'page.php', 'page-new.php' );
		$st_pages      = array( 'st_autoterms', 'st_mass_terms', 'st_manage' );

		// Helper for posts/pages and for Auto Tags, Mass Edit Tags and Manage tags !
		if ( ( in_array( $pagenow, $wp_post_pages, true ) || ( in_array( $pagenow, $wp_page_pages, true ) && is_page_have_tags() ) ) || ( isset( $_GET['page'] ) && in_array( $_GET['page'], $st_pages, true ) ) ) {
			wp_enqueue_script( 'st-helper-autocomplete' );
		}
	}

	/**
	 * Ajax Dispatcher
	 *
	 */
	public static function ajax_check() {
		if ( isset( $_GET['stags_action'] ) && 'helper_js_collection' === $_GET['stags_action'] ) {
			self::ajax_local_tags();
		}
	}

	/**
	 * Display a javascript collection for autocompletion script !
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function ajax_local_tags() {
		status_header( 200 ); // Send good header HTTP
		header( 'Content-Type: application/json; charset=' . get_bloginfo( 'charset' ) );

		$taxonomy = 'post_tag';
		if ( isset( $_REQUEST['taxonomy'] ) && taxonomy_exists( sanitize_text_field($_REQUEST['taxonomy']) ) ) {
			$taxonomy = sanitize_text_field($_REQUEST['taxonomy']);
		}

		if ( (int) wp_count_terms( $taxonomy, array( 'hide_empty' => false ) ) === 0 ) { // No tags to suggest
			echo wp_json_encode( array() );
			exit();
		}

		// Prepare search
		$search = ( isset( $_GET['term'] ) ) ? trim( stripslashes( sanitize_text_field($_GET['term']) )) : '';

		// Get all terms, or filter with search
		$terms = SimpleTags_Admin::getTermsForAjax( $taxonomy, $search );
		if ( empty( $terms ) ) {
			echo wp_json_encode( array() );
			exit();
		}

		// Format 
		$results = array();
		foreach ( (array) $terms as $term ) {
			$term->name = stripslashes( $term->name );
			$term->name = str_replace( array( "\r\n", "\r", "\n" ), '', $term->name );

			$results[] = array(
				'id'    => $term->term_id,
				'label' => $term->name,
				'value' => $term->name,
			);
		}

		echo wp_json_encode( $results );
		exit();
	}

	/**
	 * Content of custom meta box of TaxoPress
	 *
	 * @param object $post
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function metabox( $post ) {
		// Get options
		$autocomplete_min = 0
		?>
		<p>
			<?php wp_nonce_field( 'update-simple-tags', 'nonce-simple-tags' ); ?>
			<input type="hidden" name="adv-tags-input-here" value="1"/>
				<input type="text" class="widefat" name="adv-tags-input" id="adv-tags-input"
					   value="<?php echo esc_attr( SimpleTags_Admin::getTermsToEdit( 'post_tag', $post->ID ) ); ?>"/>

			<?php esc_html_e( 'Separate tags with commas', 'simple-tags' ); ?>
		</p>
		<script type="text/javascript">
          <!--
          st_init_autocomplete('#adv-tags-input', '<?php echo admin_url( "admin-ajax.php?action=simpletags&stags_action=helper_js_collection" ); ?>', <?php echo $autocomplete_min; ?>)
          -->
		</script>
		<?php
	}

	/**
	 * public static function called on auto terms page
	 *
	 * @param string $taxonomy
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function auto_terms_js( $taxonomy = '' ) {
		// Get option
		$autocomplete_min = 0
		?>
		<script type="text/javascript">
          <!--
          st_init_autocomplete('#auto_list', "<?php echo admin_url( 'admin-ajax.php?action=simpletags&stags_action=helper_js_collection&taxonomy=' . $taxonomy ); ?>", <?php echo $autocomplete_min; ?>)
          -->
		</script>
		<?php
	}

	/**
	 * public static function called on manage terms page
	 *
	 * @param string $taxonomy
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function manage_terms_js( $taxonomy = '' ) {
		// Get option
		$autocomplete_min = 0
		?>
		<script type="text/javascript">
          <!--
          st_init_autocomplete('.autocomplete-input', "<?php echo admin_url( 'admin-ajax.php?action=simpletags&stags_action=helper_js_collection&taxonomy=' . $taxonomy ); ?>", <?php echo $autocomplete_min; ?>)
          -->
		</script>
		<?php
	}

	/**
	 * public static function called on mass terms page
	 *
	 * @param string $taxonomy
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function mass_terms_js( $taxonomy = '' ) {
		// Get option
		$autocomplete_min = 0
		?>
		<script type="text/javascript">
          <!--
          st_init_autocomplete('.autocomplete-input', "<?php echo admin_url( 'admin-ajax.php?action=simpletags&stags_action=helper_js_collection&taxonomy=' . $taxonomy ); ?>", <?php echo $autocomplete_min; ?>)
          -->
		</script>
		<?php
	}
}
