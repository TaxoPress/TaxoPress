<?php

class SimpleTags_Admin {
	// CPT and Taxonomy support
	public static $post_type = 'post';
	public static $post_type_name = '';
	public static $taxonomy = '';
	public static $taxo_name = '';
	public static $admin_url = '';

	const MENU_SLUG = 'st_options';

	/**
	 * Initialize Admin
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public function __construct() {
		// DB Upgrade ?
		self::upgrade();

		// Which taxo ?
		self::register_taxonomy();

		// Admin menu
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		// Log cpt
		add_action( 'init', array( __CLASS__, 'taxopress_log_post_type' ) );

        //Admin footer credit
        add_action( 'in_admin_footer', array( __CLASS__, 'taxopress_admin_footer') );

		// Load JavaScript and CSS
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

		//ui class is used accross many pages. So, it should be here
		require STAGS_DIR . '/inc/class.admin.taxonomies.ui.php';

		//terms
		require STAGS_DIR . '/inc/terms-table.php';
        require STAGS_DIR . '/inc/terms.php';
        SimpleTags_Terms::get_instance();

        //tag clouds/ terms display
        $active_terms_display = isset($_POST['updateoptions']) &&
            (
                !isset($_POST['active_terms_display']) ||
                (isset($_POST['active_terms_display']) && (int)$_POST['active_terms_display'] === 0)
             ) ? 0 : 1;
		if ( (1 === (int) SimpleTags_Plugin::get_option_value( 'active_terms_display' ) || (isset($_POST['active_terms_display']) && (int)$_POST['active_terms_display'] === 1)) && $active_terms_display === 1 ) {
            require_once STAGS_DIR . '/inc/tag-clouds-action.php';
            require STAGS_DIR . '/inc/tag-clouds-table.php';
            require STAGS_DIR . '/inc/tag-clouds.php';
            SimpleTags_Tag_Clouds::get_instance();
        }

        //post tags
        $active_post_tags = isset($_POST['updateoptions']) &&
            (
                !isset($_POST['active_post_tags']) ||
                (isset($_POST['active_post_tags']) && (int)$_POST['active_post_tags'] === 0)
             ) ? 0 : 1;
		if ( (1 === (int) SimpleTags_Plugin::get_option_value( 'active_post_tags' ) || (isset($_POST['active_post_tags']) && (int)$_POST['active_post_tags'] === 1)) && $active_post_tags === 1 ) {
            require_once STAGS_DIR . '/inc/post-tags-action.php';
            require STAGS_DIR . '/inc/post-tags-table.php';
            require STAGS_DIR . '/inc/post-tags.php';
            SimpleTags_Post_Tags::get_instance();
        }

        //Related Posts
        $active_related_posts_new = isset($_POST['updateoptions']) &&
            (
                !isset($_POST['active_related_posts_new']) ||
                (isset($_POST['active_related_posts_new']) && (int)$_POST['active_related_posts_new'] === 0)
             ) ? 0 : 1;
		if ( (1 === (int) SimpleTags_Plugin::get_option_value( 'active_related_posts_new' ) || (isset($_POST['active_related_posts_new']) && (int)$_POST['active_related_posts_new'] === 1)) && $active_related_posts_new === 1 ) {
            require_once STAGS_DIR . '/inc/related-posts-action.php';
            require STAGS_DIR . '/inc/related-posts-table.php';
            require STAGS_DIR . '/inc/related-posts.php';
            SimpleTags_Related_Post::get_instance();
        }

        //Auto Links
        $active_auto_links = isset($_POST['updateoptions']) &&
            (
                !isset($_POST['active_auto_links']) ||
                (isset($_POST['active_auto_links']) && (int)$_POST['active_auto_links'] === 0)
             ) ? 0 : 1;
		if ( (1 === (int) SimpleTags_Plugin::get_option_value( 'active_auto_links' ) || (isset($_POST['active_auto_links']) && (int)$_POST['active_auto_links'] === 1)) && $active_auto_links === 1 ) {
            require STAGS_DIR . '/inc/autolinks-table.php';
            require STAGS_DIR . '/inc/autolinks.php';
            SimpleTags_Autolink::get_instance();
        }

        //Auto Terms
        $active_auto_terms = isset($_POST['updateoptions']) &&
            (
                !isset($_POST['active_auto_terms']) ||
                (isset($_POST['active_auto_terms']) && (int)$_POST['active_auto_terms'] === 0)
             ) ? 0 : 1;
		if ( (1 === (int) SimpleTags_Plugin::get_option_value( 'active_auto_terms' ) || (isset($_POST['active_auto_terms']) && (int)$_POST['active_auto_terms'] === 1)) && $active_auto_terms === 1 ) {
            require STAGS_DIR . '/inc/autoterms-table.php';
            require STAGS_DIR . '/inc/autoterms-logs-table.php';
            require STAGS_DIR . '/inc/autoterms.php';
            SimpleTags_Autoterms::get_instance();
        }

        //Suggest Terms
        $active_suggest_terms = isset($_POST['updateoptions']) &&
            (
                !isset($_POST['active_suggest_terms']) ||
                (isset($_POST['active_suggest_terms']) && (int)$_POST['active_suggest_terms'] === 0)
             ) ? 0 : 1;
		if ( (1 === (int) SimpleTags_Plugin::get_option_value( 'active_suggest_terms' ) || (isset($_POST['active_suggest_terms']) && (int)$_POST['active_suggest_terms'] === 1)) && $active_suggest_terms === 1 ) {
            require STAGS_DIR . '/inc/suggestterms-table.php';
            require STAGS_DIR . '/inc/suggestterms.php';
            SimpleTags_SuggestTerms::get_instance();
        }

		//suggest terms option
        $active_mass_edit = isset($_POST['updateoptions']) &&
            (
                !isset($_POST['active_mass_edit']) ||
                (isset($_POST['active_mass_edit']) && (int)$_POST['active_mass_edit'] === 0)
             ) ? 0 : 1;
		if ( (1 === (int) SimpleTags_Plugin::get_option_value( 'active_mass_edit' ) || (isset($_POST['active_mass_edit']) && (int)$_POST['active_mass_edit'] === 1)) && $active_mass_edit === 1 ) {
		require STAGS_DIR . '/inc/class.admin.suggest.php';
		new SimpleTags_Admin_Suggest();
        }

        //click terms option
		require STAGS_DIR . '/inc/class.admin.clickterms.php';
		new SimpleTags_Admin_ClickTags();

        require STAGS_DIR . '/inc/class.admin.autocomplete.php';
        new SimpleTags_Admin_Autocomplete();

        $active_mass_edit = isset($_POST['updateoptions']) &&
            (
                !isset($_POST['active_mass_edit']) ||
                (isset($_POST['active_mass_edit']) && (int)$_POST['active_mass_edit'] === 0)
             ) ? 0 : 1;
		if ( (1 === (int) SimpleTags_Plugin::get_option_value( 'active_mass_edit' ) || (isset($_POST['active_mass_edit']) && (int)$_POST['active_mass_edit'] === 1)) && $active_mass_edit === 1 ) {
			require STAGS_DIR . '/inc/class.admin.mass.php';
			new SimpleTags_Admin_Mass();
		}

        $active_manage = isset($_POST['updateoptions']) &&
            (
                !isset($_POST['active_manage']) ||
                (isset($_POST['active_manage']) && (int)$_POST['active_manage'] === 0)
             ) ? 0 : 1;
		if ( (1 === (int) SimpleTags_Plugin::get_option_value( 'active_manage' ) || (isset($_POST['active_manage']) && (int)$_POST['active_manage'] === 1)) && $active_manage === 1 ) {
			require STAGS_DIR . '/inc/class-tag-table.php';
			require STAGS_DIR . '/inc/class.admin.manage.php';
			SimpleTags_Admin_Manage::get_instance();
		}

		require STAGS_DIR . '/inc/class.admin.post.php';
		new SimpleTags_Admin_Post_Settings();

        //taxonomies
        $active_taxonomies = isset($_POST['updateoptions']) &&
            (
                !isset($_POST['active_taxonomies']) ||
                (isset($_POST['active_taxonomies']) && (int)$_POST['active_taxonomies'] === 0)
             ) ? 0 : 1;
		if ( (1 === (int) SimpleTags_Plugin::get_option_value( 'active_taxonomies' ) || (isset($_POST['active_taxonomies']) && (int)$_POST['active_taxonomies'] === 1)) && $active_taxonomies === 1 ) {
            require_once STAGS_DIR . '/inc/taxonomies-action.php';
            require STAGS_DIR . '/inc/class-taxonomies-table.php';
            require STAGS_DIR . '/inc/taxonomies.php';
            SimpleTags_Admin_Taxonomies::get_instance();
        }

		do_action('taxopress_admin_class_after_includes');

		// Ajax action, JS Helper and admin action
		add_action( 'wp_ajax_simpletags', array( __CLASS__, 'ajax_check' ) );

	}

	/**
	 * Ajax Dispatcher
	 */
	public static function ajax_check() {
		if ( isset( $_GET['stags_action'] ) && 'maybe_create_tag' === $_GET['stags_action'] && isset( $_GET['tag'] ) ) {
			self::maybe_create_tag( wp_unslash( sanitize_text_field($_GET['tag']) ) );
		}
	}

	/**
	 * Maybe create a tag, and return the term_id
	 *
	 * @param string $tag_name
	 */
	public static function maybe_create_tag( $tag_name = '' ) {
		$term_id     = 0;
		//restore & in tag post
		$tag_name = sanitize_text_field(str_replace("simpletagand", "&", $tag_name));
		$result_term = term_exists( $tag_name, 'post_tag', 0 );
		if ( empty( $result_term ) ) {
			$result_term = wp_insert_term(
				$tag_name,
				'post_tag'
			);

			if ( ! is_wp_error( $result_term ) ) {
				$term_id = (int) $result_term['term_id'];
			}
		} else {
			$term_id = (int) $result_term['term_id'];
		}

		wp_send_json_success( [ 'term_id' => $term_id ] );
	}

	/**
	 * Test if current URL is not a DEV environnement
	 *
	 * Copy from monsterinsights_is_dev_url(), thanks !
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	private static function is_dev_url( $url = '' ) {
		$is_local_url = false;

		// Trim it up
		$url = strtolower( trim( $url ) );
		// Need to get the host...so let's add the scheme so we can use parse_url
		if ( false === strpos( $url, 'http://' ) && false === strpos( $url, 'https://' ) ) {
			$url = 'http://' . $url;
		}

		$url_parts = wp_parse_url( $url );
		$host      = ! empty( $url_parts['host'] ) ? $url_parts['host'] : false;
		if ( ! empty( $url ) && ! empty( $host ) ) {
			if ( false !== ip2long( $host ) ) {
				if ( ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					$is_local_url = true;
				}
			} elseif ( 'localhost' === $host ) {
				$is_local_url = true;
			}

			$tlds_to_check = array( '.local', ':8888', ':8080', ':8081', '.invalid', '.example', '.test' );
			foreach ( $tlds_to_check as $tld ) {
				if ( false !== strpos( $host, $tld ) ) {
					$is_local_url = true;
					break;
				}
			}

			if ( substr_count( $host, '.' ) > 1 ) {
				$subdomains_to_check = array( 'dev.', '*.staging.', 'beta.', 'test.' );
				foreach ( $subdomains_to_check as $subdomain ) {
					$subdomain = str_replace( '.', '(.)', $subdomain );
					$subdomain = str_replace( array( '*', '(.)' ), '(.*)', $subdomain );
					if ( preg_match( '/^(' . $subdomain . ')/', $host ) ) {
						$is_local_url = true;
						break;
					}
				}
			}
		}

		return $is_local_url;
	}

	/**
	 * Init taxonomy class variable, load this action after all actions on init !
	 * Make a public static function for call it from children class...
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function register_taxonomy() {
		add_action( 'init', array( __CLASS__, 'init' ), 99999999 );
	}

	/**
	 * Put in var class the current taxonomy choose by the user
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function init() {
		self::$taxo_name      = esc_html__( 'Post tags', 'simple-tags' );
		self::$post_type_name = esc_html__( 'Posts', 'simple-tags' );

		// Custom CPT ?
		if ( isset( $_GET['cpt'] ) && ! empty( $_GET['cpt'] ) && post_type_exists( sanitize_text_field($_GET['cpt']) ) ) {
			$cpt                  = get_post_type_object( sanitize_text_field($_GET['cpt']) );
			self::$post_type      = $cpt->name;
			self::$post_type_name = $cpt->labels->name;
		}

		// Get compatible taxo for current post type
		$compatible_taxonomies = get_object_taxonomies( self::$post_type );

		// Custom taxo ?
		if ( isset( $_GET['taxo'] ) && ! empty( $_GET['taxo'] ) && taxonomy_exists( sanitize_text_field($_GET['taxo']) ) ) {
			$taxo = get_taxonomy( sanitize_text_field($_GET['taxo']) );

			// Taxo is compatible ?
			if ( in_array( $taxo->name, $compatible_taxonomies ) ) {
				self::$taxonomy  = $taxo->name;
				self::$taxo_name = $taxo->labels->name;
			} else {
				unset( $taxo );
			}
		}

		// Default taxo from CPT...
		if ( ! isset( $taxo ) && is_array( $compatible_taxonomies ) && ! empty( $compatible_taxonomies ) ) {
			// Take post_tag before category
			if ( in_array( 'post_tag', $compatible_taxonomies, true ) ) {
				$taxo = get_taxonomy( 'post_tag' );
			} else {
				$taxo = get_taxonomy( current( $compatible_taxonomies ) );
			}

			self::$taxonomy  = $taxo->name;
			self::$taxo_name = $taxo->labels->name;

			// TODO: Redirect for help user that see the URL...
		} elseif ( ! isset( $taxo ) ) {
			wp_die( esc_html__( 'This custom post type not have taxonomies.', 'simple-tags' ) );
		}

		// Free memory
		unset( $cpt, $taxo );
	}

	/**
	 * Build HTML form for allow user to change taxonomy for the current page.
	 *
	 * @param string $page_value
	 *
	 * @return void
	 * @author Olatechpro
	 */
	public static function boxSelectorTaxonomy( $page_value = '' ) {
		echo '<div class="box-selector-taxonomy">' . PHP_EOL;

		echo '<div class="change-taxo">' . PHP_EOL;
		echo '<form action="" method="get">' . PHP_EOL;
		if ( ! empty( $page_value ) ) {
			echo '<input type="hidden" name="page" value="' . esc_attr($page_value) . '" />' . PHP_EOL;
		}
		$taxonomies = [];
		echo '<select name="cpt" id="cpt-select" class="st-cpt-select">' . PHP_EOL;
		foreach ( get_post_types( array( 'show_ui' => true ), 'objects' ) as $post_type ) {
			$taxonomies_children = get_object_taxonomies( $post_type->name );
			if ( empty( $taxonomies_children ) ) {
				continue;
			}
			$taxonomies[$post_type->name] = $taxonomies_children;
			echo '<option ' . selected( $post_type->name, self::$post_type, false ) . ' value="' . esc_attr( $post_type->name ) . '">' . esc_html( $post_type->labels->name ) . '</option>' . PHP_EOL;
		}
		echo '</select>' . PHP_EOL;

		echo '<select name="taxo" id="taxonomy-select" class="st-taxonomy-select">' . PHP_EOL;
		foreach ( $taxonomies as $parent_post => $taxonomy ) {
			if ( count($taxonomy) > 0){
				foreach($taxonomy as $tax_name){
			$taxonomy = get_taxonomy( $tax_name );
			if ( false === (bool) $taxonomy->show_ui ) {
				continue;
			}

			if(  self::$post_type == $parent_post){
				 $class = "";
			}else{
				$class="st-hide-content";
			}

			echo '<option ' . selected( $tax_name, self::$taxonomy, false ) . ' value="' . esc_attr( $tax_name ) . '" data-post="'. esc_attr($parent_post) .'" class="'. esc_attr($class) .'">' . esc_html( $taxonomy->labels->name ) . '</option>' . PHP_EOL;
			}
		}
		}
		echo '</select>' . PHP_EOL;

		echo '<input type="submit" class="button" id="submit-change-taxo" value="' . esc_attr__( 'Change selection', 'simple-tags' ) . '" />' . PHP_EOL;
		echo '</form>' . PHP_EOL;
		echo '</div>' . PHP_EOL;
		echo '</div>' . PHP_EOL;

	}

	/**
	 * Init somes JS and CSS need for TaxoPress.
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function admin_enqueue_scripts() {
		global $pagenow;


		do_action('taxopress_admin_class_before_assets_register');

		//color picker style
  		wp_enqueue_style( 'wp-color-picker' );

		// Helper TaxoPress
		wp_register_script( 'st-helper-add-tags', STAGS_URL . '/assets/js/helper-add-tags.js', array( 'jquery' ), STAGS_VERSION );
		wp_register_script( 'st-helper-options', STAGS_URL . '/assets/js/helper-options.js', array( 'jquery', 'wp-color-picker' ), STAGS_VERSION );

		// Register CSS
		wp_register_style( 'st-admin', STAGS_URL . '/assets/css/admin.css', array(), STAGS_VERSION, 'all' );

		//Register and enqueue admin js
		wp_register_script( 'st-admin-js', STAGS_URL . '/assets/js/admin.js', array( 'jquery' ), STAGS_VERSION );
		wp_enqueue_script( 'st-admin-js' );
        //localize script
        wp_localize_script( 'st-admin-js', 'st_admin_localize', [
            'ajaxurl'     => admin_url('admin-ajax.php'),
            'select_valid'=> esc_html__( 'Please select a valid', 'simple-tags' ),
            'check_nonce' => wp_create_nonce('st-admin-js'),
        ]);


        //Register remodal assets
		wp_register_script( 'st-remodal-js', STAGS_URL . '/assets/js/remodal.min.js', array( 'jquery' ), STAGS_VERSION );
		wp_register_style( 'st-remodal-css', STAGS_URL . '/assets/css/remodal.css', array(), STAGS_VERSION, 'all' );
		wp_register_style( 'st-remodal-default-theme-css', STAGS_URL . '/assets/css/remodal-default-theme.css', array(), STAGS_VERSION, 'all' );

		// Register location
		$wp_post_pages = array( 'post.php', 'post-new.php' );
		$wp_page_pages = array( 'page.php', 'page-new.php' );

		$taxopress_pages = taxopress_admin_pages();

		// Common Helper for Post, Page and Plugin Page
		if (
			in_array( $pagenow, $wp_post_pages ) ||
			( in_array( $pagenow, $wp_page_pages ) && is_page_have_tags() ) ||
			( isset( $_GET['page'] ) && in_array( $_GET['page'], $taxopress_pages ) )
		) {
			wp_enqueue_script( 'st-remodal-js' );
			wp_enqueue_style( 'st-remodal-css' );
			wp_enqueue_style( 'st-remodal-default-theme-css' );
			wp_enqueue_style( 'st-admin' );

			do_action('taxopress_admin_class_after_styles_enqueue');
		}

		// add jQuery tabs for options page. Use jQuery UI Tabs from WP
		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], array('st_options','st_terms_display') ) ) {
			wp_enqueue_script( 'jquery-ui-tabs' );
			wp_enqueue_script( 'st-helper-options' );
		}

		do_action('taxopress_admin_class_after_assets_enqueue');
	}

	/**
	 * Register log post type.
	 *
	 * @return void
	 * @author olatechpro
	 */
	public static function taxopress_log_post_type() {

        // set up labels
        $labels = array(
            'name' => __('TaxoPress Logs', 'simple-tags'),
            'singular_name' => __('TaxoPress Logs', 'simple-tags'),
            'search_items' => __('Search TaxoPress Logs', 'simple-tags'),
            'all_items' => __('TaxoPress Logs', 'simple-tags'),
            'edit_item' => __('Edit TaxoPress Logs', 'simple-tags'),
            'update_item' => __('Update TaxoPress Logs', 'simple-tags'),
            'add_new_item' => __('Add New TaxoPress Logs', 'simple-tags'),
            'new_item_name' => __('New TaxoPress Logs', 'simple-tags'),
            'menu_name' => __('TaxoPress Logs', 'simple-tags')
        );

        register_post_type('taxopress_logs', array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => false,
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => array('slug' => 'taxopress_logs'),
            'query_var' => false,
            'show_in_nav_menus' => false,
            'menu_icon' => 'dashicons-editor-justify',
            'supports' => array(
                'title',
								'editor',
                'author',
            ),
        ));
    }

	/**
	 * Add settings page on WordPress admin menu
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function admin_menu() {
		self::$admin_url = admin_url( 'admin.php?page=' . self::MENU_SLUG );

		add_menu_page(
			__( 'TaxoPress: Options', 'simple-tags' ),
			__( 'TaxoPress', 'simple-tags' ),
			'admin_simple_tags',
			self::MENU_SLUG,
			array(
				__CLASS__,
				'page_options',
			),
			'dashicons-tag',
			69
		);
		add_submenu_page(
			self::MENU_SLUG,
			__( 'TaxoPress: Options', 'simple-tags' ),
			__( 'Settings', 'simple-tags' ),
			'admin_simple_tags',
			self::MENU_SLUG,
			array(
				__CLASS__,
				'page_options',
			)
		);
	}

	/**
	 * Build HTML for page options, manage also save/reset settings
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function page_options() {
		// Get options
		$options = SimpleTags_Plugin::get_option();

        if(current_user_can('admin_simple_tags')){
            // Update or reset options
            if ( isset( $_POST['updateoptions'] ) ) {
                check_admin_referer( 'updateresetoptions-simpletags' );

                foreach ( (array) $options as $key => $value ) {
                    $newval = ( isset( $_POST[ $key ] ) ) ? stripslashes( sanitize_text_field($_POST[ $key ]) ) : '0';
                    if ( $newval != $value ) {
                        $options[ $key ] = $newval;
                    }
                }
                SimpleTags_Plugin::set_option( $options );

                do_action( 'simpletags_settings_save_general_end' );

                add_settings_error( __CLASS__, __CLASS__, esc_html__( 'Options saved', 'simple-tags' ), 'updated' );
            } elseif ( isset( $_POST['reset_options'] ) ) {
                check_admin_referer( 'updateresetoptions-simpletags' );

                SimpleTags_Plugin::set_default_option();

                add_settings_error( __CLASS__, __CLASS__, esc_html__( 'TaxoPress options resetted to default options!', 'simple-tags' ), 'updated' );
            }

        }

		settings_errors( __CLASS__ );
		include STAGS_DIR . '/views/admin/page-settings.php';
	}

	/**
	 * Get terms for a post, format terms for input and autocomplete usage
	 *
	 * @param string $taxonomy
	 * @param integer $post_id
	 *
	 * @return string
	 * @author WebFactory Ltd
	 */
	public static function getTermsToEdit( $taxonomy = 'post_tag', $post_id = 0 ) {
		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return '';
		}

		$terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		$terms = array_unique( $terms ); // Remove duplicate
		$terms = join( ', ', $terms );
		$terms = esc_attr( $terms );
		$terms = apply_filters( 'tags_to_edit', $terms );

		return $terms;
	}

	/**
	 * Default content for meta box of TaxoPress
	 *
	 * @return string
	 * @author WebFactory Ltd
	 */
	public static function getDefaultContentBox() {
		if ( (int) wp_count_terms( 'post_tag', array( 'hide_empty' => false ) ) == 0 ) { // TODO: Custom taxonomy
			return esc_html__( 'This feature requires at least 1 tag to work. Begin by adding tags!', 'simple-tags' );
		} else {
			return esc_html__( 'This feature works only with activated JavaScript. Activate it in your Web browser so you can!', 'simple-tags' );
		}
	}

	/**
	 * A short public static function for display the same copyright on all admin pages
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function printAdminFooter() {
		/* ?>
		<p class="footer_st"><?php printf( __( 'Thanks for using TaxoPress | <a href="https://taxopress.com/">TaxoPress.com</a> | Version %s', 'simple-tags' ), STAGS_VERSION ); ?></p>
		<?php */
	}

	/**
	 * A short public static function for display the same copyright on all taxopress admin pages
	 *
	 * @return void
	 * @author Olatechpro
	 */
	public static function taxopress_admin_footer() {

        $taxopress_pages = taxopress_admin_pages();

		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $taxopress_pages )) {
		?>
		<p class="footer_st">
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            printf( __( 'Thanks for using TaxoPress | %1sTaxoPress.com%2s | Version %3s', 'simple-tags' ), '<a href="https://taxopress.com/">', '</a>', esc_html(STAGS_VERSION) ); ?>
        </p>
		<?php
        }
	}

	/**
	 * Ouput formatted options
	 *
	 * @param array $option_data
	 *
	 * @return string
	 * @author WebFactory Ltd
	 */
	public static function print_options( $option_data ) {
		// Get options
		$option_actual = SimpleTags_Plugin::get_option();

		// Generate output
		$output = '';
		foreach ( $option_data as $section => $options ) {
			$colspan       = count( $options ) > 1 ? 'colspan="2"' : '';
			$desc_html_tag = 'div';

            if($section === 'legacy'){
                $table_sub_tab = '<div class="st-legacy-subtab">
                <span class="active" data-content=".legacy-tag-cloud-content">Tag Cloud</span> |
                <span data-content=".legacy-post-tags-content">Tags for Current Post</span> |
                <span data-content=".legacy-related-posts-content">Related Posts</span> |
                <span data-content=".legacy-auto-link-content">Auto link</span>
                </div>' . PHP_EOL;
            }else{
                $table_sub_tab = '';
            }

			$output .= '<div class="group" id="' . sanitize_title( $section ) . '">' . PHP_EOL;
            $output .= $table_sub_tab;
			$output .= '<fieldset class="options">' . PHP_EOL;
			$output .= '<legend>' . self::getNiceTitleOptions( $section ) . '</legend>' . PHP_EOL;
			$output .= '<table class="form-table">' . PHP_EOL;
			foreach ( (array) $options as $option ) {

                $class = '';
                if($section === 'legacy'){
                    $class = $option[5];
                }

				// Helper
				if ( $option[2] == 'helper' ) {
					$output .= '<tr style="vertical-align: middle;" class="'.$class.'"><td class="helper" ' . $colspan . '>' . stripslashes( $option[4] ) . '</td></tr>' . PHP_EOL;
					continue;
				}

				// Fix notices
				if ( ! isset( $option_actual[ $option[0] ] ) ) {
					$option_actual[ $option[0] ] = '';
				}

				$input_type = '';
				switch ( $option[2] ) {
					case 'radio':
						$input_type = array();
						foreach ( $option[3] as $value => $text ) {
							$input_type[] = '<label><input type="radio" id="' . $option[0] . '" name="' . $option[0] . '" value="' . esc_attr( $value ) . '" ' . checked( $value, $option_actual[ $option[0] ], false ) . ' /> ' . $text . '</label>' . PHP_EOL;
						}
						$input_type = implode( '<br />', $input_type );
						break;

					case 'checkbox':
						$desc_html_tag = 'span';
						$input_type    = '<input type="checkbox" id="' . $option[0] . '" name="' . $option[0] . '" value="' . esc_attr( $option[3] ) . '" ' . ( ( $option_actual[ $option[0] ] ) ? 'checked="checked"' : '' ) . ' />' . PHP_EOL;
						break;

					case 'dropdown':
						$selopts = explode( '/', $option[3] );
						$seldata = '';
						foreach ( (array) $selopts as $sel ) {
							$seldata .= '<option value="' . esc_attr( $sel ) . '" ' . ( ( isset( $option_actual[ $option[0] ] ) && $option_actual[ $option[0] ] == $sel ) ? 'selected="selected"' : '' ) . ' >' . ucfirst( $sel ) . '</option>' . PHP_EOL;
						}
						$input_type = '<select id="' . $option[0] . '" name="' . $option[0] . '">' . $seldata . '</select>' . PHP_EOL;
						break;

					case 'text-color':
						$input_type = '<input type="text" id="' . $option[0] . '" name="' . $option[0] . '" value="' . esc_attr( $option_actual[ $option[0] ] ) . '" class="text-color ' . $option[3] . '" />' . PHP_EOL;
						break;

					case 'text':
						$input_type = '<input type="text" id="' . $option[0] . '" name="' . $option[0] . '" value="' . esc_attr( $option_actual[ $option[0] ] ) . '" class="' . $option[3] . '" />' . PHP_EOL;
						break;

					case 'number':
						$input_type = '<input type="number" id="' . $option[0] . '" name="' . $option[0] . '" value="' . esc_attr( $option_actual[ $option[0] ] ) . '" class="' . $option[3] . '" />' . PHP_EOL;
						break;
				}

				if( is_array($option[2]) ){
					$input_type = '<input type="'.$option[2]["type"].'" id="' . $option[0] . '" name="' . $option[0] . '" value="' . esc_attr( $option_actual[ $option[0] ] ) . '" class="' . $option[3] . '" '.$option[2]["attr"].' />' . PHP_EOL;
				}

				// Additional Information
				$extra = '';
				if ( ! empty( $option[4] ) ) {
					$extra = '<' . $desc_html_tag . ' class="stpexplan">' . __( $option[4] ) . '</' . $desc_html_tag . '>' . PHP_EOL;
				}

				// Output
				$output .= '<tr style="vertical-align: top;" class="'.$class.'"><th scope="row"><label for="' . $option[0] . '">' . __( $option[1] ) . '</label></th><td>' . $input_type . '	' . $extra . '</td></tr>' . PHP_EOL;
			}
			$output .= '</table>' . PHP_EOL;
			$output .= '</fieldset>' . PHP_EOL;
			$output .= '</div>' . PHP_EOL;
		}

		return $output;
	}

	/**
	 * Get nice title for tabs title option
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public static function getNiceTitleOptions( $id = '' ) {
		switch ( $id ) {
			case 'administration':
				return esc_html__( 'Administration', 'simple-tags' );
			case 'auto-links':
				return esc_html__( 'Auto link', 'simple-tags' );
			case 'features':
				return esc_html__( 'Features', 'simple-tags' );
			case 'embeddedtags':
				return esc_html__( 'Embedded Tags', 'simple-tags' );
			case 'tagspost':
				return esc_html__( 'Tags for Current Post', 'simple-tags' );
			case 'relatedposts':
				return esc_html__( 'Related Posts', 'simple-tags' );
			case 'legacy':
				return esc_html__( 'Legacy', 'simple-tags' );
		}

		return '';
	}

	/**
	 * This method allow to check if the DB is up to date, and if a upgrade is need for options
	 * TODO, useful or delete ?
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function upgrade() {
		// Get current version number
		$current_version = get_option( STAGS_OPTIONS_NAME . '-version' );

		// Upgrade needed ?
		if ( $current_version == false || version_compare( $current_version, STAGS_VERSION, '<' ) ) {
			$current_options = get_option( STAGS_OPTIONS_NAME );
			$default_options = (array) include( STAGS_DIR . '/inc/helper.options.default.php' );

			// Add new options
			foreach ( $default_options as $key => $default_value ) {
				if ( ! isset( $current_options[ $key ] ) ) {
					$current_options[ $key ] = $default_value;
				}
			}

			// Remove old options
			foreach ( $current_options as $key => $current_value ) {
				if ( ! isset( $default_options[ $key ] ) ) {
					unset( $current_options[ $key ] );
				}
			}

			update_option( STAGS_OPTIONS_NAME . '-version', STAGS_VERSION );
			update_option( STAGS_OPTIONS_NAME, $current_options );
		}
	}

	/**
	 * Make a simple SQL query with some args for get terms for ajax display
	 *
	 * @param string $taxonomy
	 * @param string $search
	 * @param string $order_by
	 * @param string $order
	 *
	 * @return array
	 * @author WebFactory Ltd
	 */
	public static function getTermsForAjax( $taxonomy = 'post_tag', $search = '', $order_by = 'name', $order = 'ASC', $limit = '' ) {
		global $wpdb;

		if ( ! empty( $search ) ) {
			return $wpdb->get_results( $wpdb->prepare( "
				SELECT DISTINCT t.name, t.term_id
				FROM {$wpdb->terms} AS t
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
				WHERE tt.taxonomy = %s
				AND t.name LIKE %s
				ORDER BY $order_by $order $limit
			", $taxonomy, '%' . $wpdb->esc_like($search) . '%' ) );
		} else {
			return $wpdb->get_results( $wpdb->prepare( "
				SELECT DISTINCT t.name, t.term_id
				FROM {$wpdb->terms} AS t
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
				WHERE tt.taxonomy = %s
				ORDER BY $order_by $order $limit
			", $taxonomy ) );
		}
	}
}
