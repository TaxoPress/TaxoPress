<?php

class SimpleTags_Admin_Autocomplete {

	public function __construct() {
		// Ajax action, JS Helper and admin action
		add_action( 'wp_ajax_simpletags_autocomplete', array( __CLASS__, 'ajax_check' ) );

		// TaxoPress hook
		add_action( 'simpletags-auto_terms', array( __CLASS__, 'auto_terms_js' ) );
		add_action( 'simpletags-manage_terms', array( __CLASS__, 'manage_terms_js' ) );
		add_action( 'simpletags-mass_terms', array( __CLASS__, 'mass_terms_js' ) );
		add_action( 'simpletags-autolinks', array( __CLASS__, 'autolinks_js' ) );

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
				'st-admin-js'
			),
			STAGS_VERSION
		);

		// Declare locations
		$wp_post_pages = array( 'post.php', 'post-new.php' );
		$wp_page_pages = array( 'page.php', 'page-new.php' );
		$st_pages      = array( 'st_autoterms', 'st_mass_terms', 'st_manage', 'st_autolinks' );

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
		// Check if nonce is set and valid
		if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'st-admin-js')) {
			wp_send_json_error(array('message' => esc_html__('Security check failed.', 'simple-tags')));
		}

		// Check if taxonomy is provided and exists
		$taxonomy = isset($_REQUEST['taxonomy']) ? sanitize_text_field($_REQUEST['taxonomy']) : 'post_tag';
		if (!taxonomy_exists($taxonomy)) {
			wp_send_json_error(array('message' => esc_html__('Invalid taxonomy.', 'simple-tags')));
		}

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
		if ( isset($_REQUEST['taxonomy']) && !empty($_REQUEST['taxonomy']) ) {//  && 
			$taxonomy = sanitize_text_field($_REQUEST['taxonomy']);
		}
		if (taxonomy_exists($taxonomy) && (int) wp_count_terms( $taxonomy, array( 'hide_empty' => false ) ) === 0 ) { // No tags to suggest
			echo wp_json_encode( array() );
			exit();
		}

		// Prepare search
		$search = ( isset( $_GET['term'] ) ) ? trim( stripslashes( sanitize_text_field($_GET['term']) )) : '';
		$exclude_term = isset($_REQUEST['exclude_term']) ? (int) $_REQUEST['exclude_term'] : 0;

		// Get all terms, or filter with search
		$terms = SimpleTags_Admin::getTermsForAjax( $taxonomy, $search );

		if ( empty( $terms ) ) {
			echo wp_json_encode( array() );
			exit();
		}

		// Format 
		$results = array();
		foreach ( (array) $terms as $term ) {
			if ((int)$term->term_id === $exclude_term) {
				continue;
			}
			$term->name = stripslashes( $term->name );
			$original_name = $term->name;
			if ($taxonomy == 'linked_term_taxonomies') {
				$term->name = $term->name . ' ('. $term->taxonomy .')';
			}
			$term->name = str_replace( array( "\r\n", "\r", "\n" ), '', $term->name );

			$results[] = array(
				'id'    => $term->term_id,
				'label' => $term->name,
				'value' => $term->name,
				'taxonomy' => $term->taxonomy,
				'name' => $original_name,
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
          st_init_autocomplete('#adv-tags-input', '<?php echo esc_url_raw(admin_url( "admin-ajax.php?action=simpletags_autocomplete&stags_action=helper_js_collection&nonce=" . wp_create_nonce( 'st-admin-js' ) )); ?>', <?php echo (int)$autocomplete_min; ?>)
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
          st_init_autocomplete('#auto_list', "<?php echo esc_url_raw(admin_url( 'admin-ajax.php?action=simpletags_autocomplete&stags_action=helper_js_collection&taxonomy=' . $taxonomy . '&nonce=' . wp_create_nonce( 'st-admin-js' ) )); ?>", <?php echo (int)$autocomplete_min; ?>)
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
          st_init_autocomplete('.autocomplete-input', "<?php echo esc_url_raw(admin_url( 'admin-ajax.php?action=simpletags_autocomplete&stags_action=helper_js_collection&taxonomy=' . $taxonomy . '&nonce=' . wp_create_nonce( 'st-admin-js' ) )); ?>", <?php echo (int)$autocomplete_min; ?>)
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
          st_init_autocomplete('.autocomplete-input', "<?php echo esc_url_raw(admin_url( 'admin-ajax.php?action=simpletags_autocomplete&stags_action=helper_js_collection&taxonomy=' . esc_attr($taxonomy) ) . '&nonce=' . wp_create_nonce( 'st-admin-js' )); ?>", <?php echo (int)$autocomplete_min; ?>)
          -->
		</script>
		<?php
	}

	/**
	 * public static function called on autolinks page
	 *
	 * @param string $taxonomy
	 *
	 * @return void
	 * @author ojopaul
	 */
	public static function autolinks_js() {
		// Get option
		$autocomplete_min = 0
		?>
		<script type="text/javascript">
          <!--
          st_init_autocomplete('.autocomplete-input', "<?php echo esc_url_raw(admin_url( 'admin-ajax.php?action=simpletags_autocomplete&stags_action=helper_js_collection&taxonomy=&nonce=' . wp_create_nonce( 'st-admin-js' ) )); ?>", <?php echo (int)$autocomplete_min; ?>, '.taxopress-dynamic-taxonomy')
          -->
		</script>
		<?php
	}
}