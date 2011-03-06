<?php
class SimpleTags_Admin {
	// CPT and Taxonomy support
	var $post_type 			= 'post';
	var $post_type_name		= '';
	var $taxonomy 			= '';
	var $taxo_name			= '';
	
	// Error management
	var $message = '';
	var $status = '';
	
	/**
	 * PHP4 Constructor - Initialize Admin
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function SimpleTags_Admin() {
		global $simple_tags;
		
		// DB Upgrade ?
		$this->upgrade();
		
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		
		// Which taxo ?
		$this->registerDetermineTaxonomy();
		
		// Admin menu
		add_action('admin_menu', array(&$this, 'adminMenu'));
		add_action('admin_notices', array(&$this, 'displayMessage'));
		
		// Load JavaScript and CSS
		$this->initJavaScript();
		
		// Load custom part of plugin depending option
		if ( isset($options['use_suggested_tags']) && $options['use_suggested_tags'] == 1 ) {
			require( STAGS_DIR . '/inc/class.admin.suggest.php');
			$simple_tags['admin-suggest'] = new SimpleTags_Admin_Suggest();
		}
		
		if ( isset($options['use_click_tags']) && $options['use_click_tags'] == 1 ) {
			require( STAGS_DIR . '/inc/class.admin.clickterms.php');
			$simple_tags['admin-clicktags'] = new SimpleTags_Admin_ClickTags();
		}
		
		if ( isset($options['use_autocompletion']) && $options['use_autocompletion'] == 1 ) {
			require( STAGS_DIR . '/inc/class.admin.autocomplete.php');
			$simple_tags['admin-autocomplete'] = new SimpleTags_Admin_Autocomplete();
		}
		
		if ( isset($options['active_mass_edit']) && $options['active_mass_edit'] == 1 ) {
			require( STAGS_DIR . '/inc/class.admin.mass.php');
			$simple_tags['admin-mass'] = new SimpleTags_Admin_Mass();
		}
		
		if ( isset($options['active_manage']) && $options['active_manage'] == 1 ) {
			require( STAGS_DIR . '/inc/class.admin.manage.php');
			$simple_tags['admin-manage'] = new SimpleTags_Admin_Manage();
		}
		
		if ( isset($options['active_autotags']) && $options['active_autotags'] == 1 ) {
			require( STAGS_DIR . '/inc/class.admin.autoterms.php');
			$simple_tags['admin-autotags'] = new SimpleTags_Admin_AutoTags();
		}
		
		if ( (isset($options['active_autotags']) && $options['active_autotags'] == 1) || (isset($options['auto_link_tags']) && $options['auto_link_tags'] == '1') ) {
			require( STAGS_DIR . '/inc/class.admin.post.php');
			$simple_tags['admin-post_settings'] = new SimpleTags_Admin_Post_Settings();
		}
	}
	
	/**
	 * Init taxonomy class variable, load this action after all actions on init !
	 * Make a function for call it from children class...
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function registerDetermineTaxonomy() {
		add_action( 'init', array(&$this, 'determineTaxonomy'), 99999999 );
	}
	
	/**
	 * Put in var class the current taxonomy choose by the user
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function determineTaxonomy() {
		$this->taxo_name = __('Post tags', 'simpletags');
		$this->post_type_name = __('Posts', 'simpletags');
		
		// Custom CPT ?
		if ( isset($_GET['cpt']) && !empty($_GET['cpt']) && post_type_exists($_GET['cpt']) ) {
			$cpt = get_post_type_object($_GET['cpt']);
			$this->post_type 		= $cpt->name;
			$this->post_type_name 	= $cpt->labels->name;
		}
		
		// Get compatible taxo for current post type
		$compatible_taxonomies = get_object_taxonomies( $this->post_type );
		
		// Custom taxo ?
		if ( isset($_GET['taxo']) && !empty($_GET['taxo']) && taxonomy_exists($_GET['taxo']) ) {
			$taxo = get_taxonomy($_GET['taxo']);
			
			// Taxo is compatible ?
			if ( in_array( $taxo->name, $compatible_taxonomies ) ) {
				$this->taxonomy 	= $taxo->name;
				$this->taxo_name 	= $taxo->labels->name;
			} else {
				unset($taxo);
			}
		}
		
		// Default taxo from CPT...
		if ( !isset($taxo) && is_array($compatible_taxonomies) && !empty($compatible_taxonomies) ) {
			// Take post_tag before category
			if ( in_array('post_tag', $compatible_taxonomies) ) {
				$taxo = get_taxonomy( 'post_tag' );
			} else {
				$taxo = get_taxonomy( current($compatible_taxonomies) );
			}
			
			$this->taxonomy 	= $taxo->name;
			$this->taxo_name 	= $taxo->labels->name;
			
			// TODO: Redirect for help user that see the URL...
		} elseif( !isset($taxo) ) {
			wp_die( __('This custom post type not have taxonomies.', 'simpletags') );
		}
		
		// Free memory
		unset($cpt, $taxo);
	}
	
	/**
	 * Build HTML form for allow user to change taxonomy for the current page.
	 *
	 * @param string $page_value
	 * @return void
	 * @author Amaury Balmer
	 */
	function boxSelectorTaxonomy( $page_value = '' ) {
		echo '<div class="box-selector-taxonomy">' . "\n";
			echo '<p class="current-taxonomy">'.sprintf(__('You currently use the custom post type "<span>%s</span>" and the taxonomy "<span>%s</span>"', 'simpletags'), $this->post_type_name, $this->taxo_name).'</p>' . "\n";
			
			echo '<div class="change-taxo">' . "\n";
				echo '<form action="" method="get">' . "\n";
					if ( !empty($page_value) ) {
						echo '<input type="hidden" name="page" value="'.$page_value.'" />' . "\n";
					}
					
					echo '<select name="cpt" id="cpt-select">' . "\n";
						foreach ( get_post_types( array('show_ui' => true ), 'objects') as $post_type ) {
							echo '<option '.selected( $post_type->name, $this->post_type, false ).' value="'.esc_attr($post_type->name).'">'.esc_html($post_type->labels->name).'</option>' . "\n";
						}
					echo '</select>' . "\n";
					
					echo '<select name="taxo" id="taxonomy-select">' . "\n";
						foreach ( get_object_taxonomies($this->post_type) as $tax_name ) {
							$taxonomy = get_taxonomy($tax_name);
							if ( $taxonomy->show_ui == false )
								continue;
							
							echo '<option '.selected( $tax_name, $this->taxonomy, false ).' value="'.esc_attr($tax_name).'">'.esc_html($taxonomy->labels->name).'</option>' . "\n";
						}
					echo '</select>' . "\n";
					
					echo '<input type="submit" class="button" id="submit-change-taxo" value="'.__('Change selection', 'simpletags').'" />' . "\n";
				echo '</form>' . "\n";
			echo '</div>' . "\n";
		echo '</div>' . "\n";
	}
	
	/**
	 * Init somes JS and CSS need for simple tags.
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function initJavaScript() {
		global $pagenow;
		
		// Library JS
		wp_register_script('jquery-cookie', STAGS_URL.'/ressources/jquery.cookie.min.js', array('jquery'), '1.0.0');
		
		// Helper simple tags
		wp_register_script('st-helper-add-tags', STAGS_URL.'/inc/js/helper-add-tags.min.js', array('jquery'), STAGS_VERSION);
		wp_register_script('st-helper-options', STAGS_URL.'/inc/js/helper-options.min.js', array('jquery'), STAGS_VERSION);
		
		// Register CSS
		wp_register_style('st-admin', STAGS_URL.'/inc/css/admin.css', array(), STAGS_VERSION, 'all' );
		
		// Register location
		$wp_post_pages = array('post.php', 'post-new.php');
		$wp_page_pages = array('page.php', 'page-new.php');
		
		// Common Helper for Post, Page and Plugin Page
		if (
			in_array($pagenow, $wp_post_pages) ||
			( in_array($pagenow, $wp_page_pages) && is_page_have_tags() ) ||
			( isset($_GET['page']) && in_array($_GET['page'], array('st_mass_terms', 'st_auto', 'st_options', 'st_manage')) )
		) {
			wp_enqueue_style ('st-admin');
		}
		
		// add jQuery tabs for options page. Use jQuery UI Tabs from WP
		if ( isset($_GET['page']) && $_GET['page'] == 'st_options' ) {
			wp_enqueue_script('jquery-ui-tabs');
			wp_enqueue_script('jquery-cookie');
			wp_enqueue_script('st-helper-options');
		}
	}
	
	/**
	 * Add settings page on WordPress admin menu
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function adminMenu() {
		add_options_page( __('Simple Tags: Options', 'simpletags'), __('Simple Tags', 'simpletags'), 'admin_simple_tags', 'st_options', array(&$this, 'pageOptions'));
	}
	
	/**
	 * Build HTML for page options, manage also save/reset settings
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function pageOptions() {
		// Get default & current options and merge
		$default_options = (array) include( dirname(__FILE__) . '/helper.options.default.php' );
		$options = (array) get_option( STAGS_OPTIONS_NAME );
		$options = array_merge( $default_options, $options );
		
		// Update or reset options
		if ( isset($_POST['updateoptions']) ) {
			foreach( (array) $options as $key => $value) {
				$newval = ( isset($_POST[$key]) ) ? stripslashes($_POST[$key]) : '0';
				if ( $newval != $value ) {
					$options[$key] = $newval;
				}
			}
			update_option( STAGS_OPTIONS_NAME, $options );
			$this->message = __('Options saved', 'simpletags');
			$this->status = 'updated';
		} elseif ( isset($_POST['reset_options']) ) {
			$options = (array) include( dirname(__FILE__) . '/helper.options.default.php' );
			update_option( STAGS_OPTIONS_NAME, $options );
			$this->message = __('Simple Tags options resetted to default options!', 'simpletags');
		}
		
		$this->displayMessage();
		?>
		<div class="wrap st_wrap">
			<h2><?php _e('Simple Tags: Options', 'simpletags'); ?></h2>
			
			<div style="float:right">
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="L9QU9QT9R5FQS">
					<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG_global.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
					<img alt="" border="0" src="https://www.paypal.com/fr_FR/i/scr/pixel.gif" width="1" height="1" />
				</form>
			</div>
			
			<p><?php _e('Visit the <a href="http://redmine.beapi.fr/projects/show/simple-tags/">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags'); ?></p>
			<form action="" method="post">
				<p>
					<input class="button-primary" type="submit" name="updateoptions" value="<?php _e('Update options &raquo;', 'simpletags'); ?>" />
					<input class="button" type="submit" name="reset_options" onclick="return confirm('<?php _e('Do you really want to restore the default options?', 'simpletags'); ?>');" value="<?php _e('Reset Options', 'simpletags'); ?>" /></p>
				
				<div id="printOptions">
					<ul class="st_submenu">
						<?php
						// Get array options/description
						$option_data = (array) include( dirname(__FILE__) . '/helper.options.admin.php' );
						foreach ( $option_data as $key => $val ) {
							$style = '';
							
							// Deactive tabs if feature not actived
							if ( isset($options['active_related_posts']) && (int) $options['active_related_posts'] == 0 && $key == 'relatedposts' )
								$style = 'display:none;';
								
							// Deactive tabs if feature not actived
							if ( isset($options['auto_link_tags']) && (int) $options['auto_link_tags'] == 0 && $key == 'auto-links' )
								$style = 'display:none;';
								
							echo '<li style="'.$style.'"><a href="#'. sanitize_title ( $key ) .'">'.$this->getNiceTitleOptions($key).'</a></li>';
						}
						?>
					</ul>
					<div class="clear"></div>
					
					<?php echo $this->printOptions( $option_data ); ?>
				</div>
				
				<p>
					<input class="button-primary" type="submit" name="updateoptions" value="<?php _e('Update options &raquo;', 'simpletags'); ?>" />
					<input class="button" type="submit" name="reset_options" onclick="return confirm('<?php _e('Do you really want to restore the default options?', 'simpletags'); ?>');" value="<?php _e('Reset Options', 'simpletags'); ?>" />
				</p>
			</form>
		<?php $this->printAdminFooter(); ?>
		</div>
		<?php
	}
	
	/**
	 * Get terms for a post, format terms for input and autocomplete usage
	 *
	 * @param string $taxonomy 
	 * @param integer $post_id 
	 * @return string
	 * @author Amaury Balmer
	 */
	function getTermsToEdit( $taxonomy = 'post_tag', $post_id = 0 ) {
		$post_id = (int) $post_id;
		if ( !$post_id )
			return false;
		
		$terms = wp_get_post_terms( $post_id, $taxonomy, array('fields' => 'names') );
		if ( $terms == false )
			return false;
		
		$terms = array_unique( $terms ); // Remove duplicate
		$terms = join( ', ', $terms );
		$terms = esc_attr( $terms );
		$terms = apply_filters( 'tags_to_edit', $terms );
		
		return $terms;
	}
	
	/**
	 * Default content for meta box of Simple Tags
	 *
	 * @return string
	 * @author Amaury Balmer
	 */
	function getDefaultContentBox() {
		if ( (int) wp_count_terms('post_tag', 'ignore_empty=false') == 0 ) { // TODO: Custom taxonomy
			return __('This feature requires at least 1 tag to work. Begin by adding tags!', 'simpletags');
		} else {
			return __('This feature works only with activated JavaScript. Activate it in your Web browser so you can!', 'simpletags');
		}
	}
		
	/**
	 * A short function for display the same copyright on all admin pages
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function printAdminFooter() {
		?>
		<p class="footer_st"><?php printf(__('&copy; Copyright 2007-2011 <a href="http://www.herewithme.fr/" title="Here With Me">Amaury Balmer</a> | <a href="http://wordpress.org/extend/plugins/simple-tags">Simple Tags</a> | Version %s', 'simpletags'), STAGS_VERSION); ?></p>
		<?php
	}
	
	/**
	 * Display WP alert using class var
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function displayMessage() {
		if ( $this->message != '') {
			$message = $this->message;
			$status = $this->status;
			$this->message = $this->status = ''; // Reset
		}
		
		if ( isset($message) && !empty($message) ) {
		?>
			<div id="message" class="<?php echo ($status != '') ? $status :'updated'; ?> fade">
				<p><strong><?php echo $message; ?></strong></p>
			</div>
		<?php
		}
	}
	
	/**
	 * Ouput formatted options
	 *
	 * @param array $option_data 
	 * @return string
	 * @author Amaury Balmer
	 */
	function printOptions( $option_data ) {
		// Get options
		$option_actual = (array) get_option( STAGS_OPTIONS_NAME );
		
		// Generate output
		$output = '';
		foreach( $option_data as $section => $options) {
			$output .= "\n" . '<div id="'. sanitize_title($section) .'"><fieldset class="options"><legend>' . $this->getNiceTitleOptions($section) . '</legend><table class="form-table">' . "\n";
			foreach((array) $options as $option) {
				// Helper
				if (  $option[2] == 'helper' ) {
					$output .= '<tr style="vertical-align: middle;"><td class="helper" colspan="2">' . stripslashes($option[4]) . '</td></tr>' . "\n";
					continue;
				}
				
				// Fix notices
				if ( !isset($option_actual[ $option[0] ]) )
					$option_actual[ $option[0] ] = '';
				
				switch ( $option[2] ) {
					case 'checkbox':
						$input_type = '<input type="checkbox" id="' . $option[0] . '" name="' . $option[0] . '" value="' . esc_attr($option[3]) . '" ' . ( ($option_actual[ $option[0] ]) ? 'checked="checked"' : '') . ' />' . "\n";
						break;
					
					case 'dropdown':
						$selopts = explode('/', $option[3]);
						$seldata = '';
						foreach( (array) $selopts as $sel) {
							$seldata .= '<option value="' . esc_attr($sel) . '" ' .((isset($option_actual[ $option[0] ]) &&$option_actual[ $option[0] ] == $sel) ? 'selected="selected"' : '') .' >' . ucfirst($sel) . '</option>' . "\n";
						}
						$input_type = '<select id="' . $option[0] . '" name="' . $option[0] . '">' . $seldata . '</select>' . "\n";
						break;
					
					case 'text-color':
						$input_type = '<input type="text" ' . ((isset($option[3]) && $option[3]>50) ? ' style="width: 95%" ' : '') . 'id="' . $option[0] . '" name="' . $option[0] . '" value="' . esc_attr($option_actual[ $option[0] ]) . '" size="' . $option[3] .'" /><div class="box_color ' . $option[0] . '"></div>' . "\n";
						break;
					
					case 'text':
					default:
						$input_type = '<input type="text" ' . ((isset($option[3]) && $option[3]>50) ? ' style="width: 95%" ' : '') . 'id="' . $option[0] . '" name="' . $option[0] . '" value="' . esc_attr($option_actual[ $option[0] ]) . '" size="' . $option[3] .'" />' . "\n";
						break;
				}
				
				// Additional Information
				$extra = '';
				if( !empty($option[4]) ) {
					$extra = '<div class="stpexplan">' . __($option[4]) . '</div>' . "\n";
				}
				
				// Output
				$output .= '<tr style="vertical-align: top;"><th scope="row"><label for="'.$option[0].'">' . __($option[1]) . '</label></th><td>' . $input_type . '	' . $extra . '</td></tr>' . "\n";
			}
			$output .= '</table>' . "\n";
			$output .= '</fieldset></div>' . "\n";
		}
		
		return $output;
	}
	
	/**
	 * Get nice title for tabs title option
	 *
	 * @param string $id
	 * @return string
	 */
	function getNiceTitleOptions( $id = '' ) {
		switch ( $id ) {
			case 'administration':
				return __('Administration', 'simpletags');
				break;
			case 'auto-links':
				return __('Auto link', 'simpletags');
				break;
			case 'features':
				return __('Features', 'simpletags');
				break;
			case 'metakeywords':
				return __('Meta Keyword', 'simpletags');
				break;
			case 'embeddedtags':
				return __('Embedded Tags', 'simpletags');
				break;
			case 'tagspost':
				return __('Tags for Current Post', 'simpletags');
				break;
			case 'relatedposts':
				return __('Related Posts', 'simpletags');
				break;
			case 'relatedtags':
				return __('Related Tags', 'simpletags');
				break;
			case 'tagcloud':
				return __('Tag cloud', 'simpletags');
				break;
		}
		return '';
	}
	
	/**
	 * This method allow to check if the DB is up to date, and if a upgrade is need for options
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function upgrade() {
		// Get current version number
		$current_version = get_option( STAGS_OPTIONS_NAME . '-version' );
		
		// Upgrade needed ?
		if ( $current_version == false || version_compare($current_version, STAGS_VERSION, '<') ) {
			$current_options = get_option( STAGS_OPTIONS_NAME );
			$default_options = (array) include( dirname(__FILE__) . '/helper.options.default.php' );
			
			// Add new options
			foreach( $default_options as $key => $default_value ) {
				if ( !isset($current_options[$key]) ) {
					$current_options[$key] = $default_value;
				}
			}
			
			// Remove old options
			foreach( $current_options as $key => $current_value ) {
				if ( !isset($default_options[$key]) ) {
					unset($current_options[$key]);
				}
			}
			
			update_option( STAGS_OPTIONS_NAME . '-version', STAGS_VERSION );
			update_option( STAGS_OPTIONS_NAME, $current_options );
		}
		
		return true;
	}
	
	/**
	 * Make a simple SQL query with some args for get terms for ajax display
	 *
	 * @param string $taxonomy 
	 * @param string $search 
	 * @param string $order_by 
	 * @param string $order 
	 * @return array
	 * @author Amaury Balmer
	 */
	function getTermsForAjax( $taxonomy = 'post_tag', $search = '', $order_by = 'name', $order = 'ASC' ) {
		global $wpdb;
		
		if ( !empty($search) ) {
			return $wpdb->get_results( $wpdb->prepare("
				SELECT DISTINCT t.name, t.term_id
				FROM {$wpdb->terms} AS t
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
				WHERE tt.taxonomy = %s
				AND name LIKE %s
				ORDER BY $order_by $order
			", $taxonomy, '%'.$search.'%' ) );
		} else {
			return $wpdb->get_results( $wpdb->prepare("
				SELECT DISTINCT t.name, t.term_id
				FROM {$wpdb->terms} AS t
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
				WHERE tt.taxonomy = %s
				ORDER BY $order_by $order
			", $taxonomy) );
		}
	}
}
?>