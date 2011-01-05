<?php
class SimpleTags_Admin {
	var $options;
	var $options_base_url = '';
	
	// Taxonomy support
	var $taxonomy 			= 'post_tag';
	var $taxo_name			= '';
	
	// Error management
	var $message = '';
	var $status = '';
	
	/**
	 * Put in var class the current taxonomy choose by the user
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function determineTaxonomy() {
		$this->taxo_name = __('Post tags', 'simpletags');
		
		if ( isset($_GET['taxonomy']) && !empty($_GET['taxonomy']) && taxonomy_exists($_GET['taxonomy']) ) {
			$taxo = get_taxonomy($_GET['taxonomy']);
			$this->taxonomy = $taxo->name;
			$this->taxo_name = $taxo->label;
			unset($taxo);
		}
	}
	
	/**
	 * PHP4 Constructor - Intialize Admin
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
		
		// Admin URL for Pagination and target
		$this->options_base_url = admin_url('options-general.php') . '?page=';
		
		// Init taxonomy class variable, load this action after all actions on init !
		add_action( 'init', array(&$this, 'determineTaxonomy'), 99999999 );
		
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
			require( STAGS_DIR . '/inc/class.admin.clicktags.php');
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
			require( STAGS_DIR . '/inc/class.admin.autotags.php');
			$simple_tags['admin-autotags'] = new SimpleTags_Admin_AutoTags();
		}
	}
	
	/**
	 * Init somes JS and CSS need for simple tags.
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function initJavaScript() {
		// Library JS
		wp_register_script('jquery-cookie', 		STAGS_URL.'/inc/js/jquery.cookie.min.js', array('jquery'), '1.0.0');
		
		// Helper simple tags
		wp_register_script('st-helper-add-tags', 	STAGS_URL.'/inc/js/helper-add-tags.min.js', array('jquery'), STAGS_VERSION);
		wp_register_script('st-helper-options', 	STAGS_URL.'/inc/js/helper-options.min.js', array('jquery'), STAGS_VERSION);

		// Register CSS
		wp_register_style('st-admin', 				STAGS_URL.'/inc/css/admin.css', array(), STAGS_VERSION, 'all' );

		// Register location
		global $pagenow;
		$wp_post_pages = array('post.php', 'post-new.php');
		$wp_page_pages = array('page.php', 'page-new.php');
		
		// Common Helper for Post, Page and Plugin Page
		if (
			in_array($pagenow, $wp_post_pages) ||
			( in_array($pagenow, $wp_page_pages) && is_page_have_tags() ) ||
			( isset($_GET['page']) && in_array($_GET['page'], array('st_manage', 'st_mass_tags', 'st_auto', 'st_options')) )
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
	 * Add WP admin menu for Tags
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function adminMenu() {
		add_options_page( __('Simple Tags: Options', 'simpletags'), __('Simple Tags', 'simpletags'), 'admin_simple_tags', 'st_options', array(&$this, 'pageOptions'));
	}
	
	/**
	 * WP Page - Tags options
	 *
	 */
	function pageOptions() {
		// Get current options
		$options = (array) get_option( STAGS_OPTIONS_NAME );
		
		// Update or reset options
		if ( isset($_POST['updateoptions']) ) {
			foreach((array) $options as $key => $value) {
				$newval = ( isset($_POST[$key]) ) ? stripslashes($_POST[$key]) : '0';
				if ( $newval != $value && !in_array($key, array('use_auto_tags', 'auto_list')) ) {
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
		
		// Get array options/description
		$option_data = (array) include( dirname(__FILE__) . '/helper.options.admin.php' );
	    ?>
		<div class="wrap st_wrap">
			<h2><?php _e('Simple Tags: Options', 'simpletags'); ?></h2>
			<p><?php _e('Visit the <a href="http://redmine.beapi.fr/projects/show/simple-tags/">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags'); ?></p>
			<form action="<?php echo $this->options_base_url.'st_options'; ?>" method="post">
				<p>
					<input class="button" type="submit" name="updateoptions" value="<?php _e('Update options &raquo;', 'simpletags'); ?>" />
					<input class="button" type="submit" name="reset_options" onclick="return confirm('<?php _e('Do you really want to restore the default options?', 'simpletags'); ?>');" value="<?php _e('Reset Options', 'simpletags'); ?>" /></p>
				
				<div id="printOptions">
					<ul class="st_submenu">
						<?php
						foreach ( $option_data as $key => $val ) {
							echo '<li><a href="#'. sanitize_title ( $key ) .'">'.$this->getNiceTitleOptions($key).'</a></li>';
						}
						?>
					</ul>
					
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
	 * General features for tags
	 *
	 */
	function getDefaultContentBox() {
		if ( (int) wp_count_terms('post_tag', 'ignore_empty=false') == 0 ) {
			return __('This feature requires at least 1 tag to work. Begin by adding tags!', 'simpletags');
		} else {
			return __('This feature works only with activated JavaScript. Activate it in your Web browser so you can!', 'simpletags');
		}
	}
		
	/**
	 * Display plugin Copyright
	 *
	 */
	function printAdminFooter() {
		?>
		<p class="footer_st"><?php printf(__('&copy; Copyright 2010 <a href="http://www.herewithme.fr/" title="Here With Me">Amaury Balmer</a> | <a href="http://wordpress.org/extend/plugins/simple-tags">Simple Tags</a> | Version %s', 'simpletags'), STAGS_VERSION); ?></p>
		<?php
	}
	
	/**
	 * Display WP alert
	 *
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
			case 'general':
				return __('General', 'simpletags');
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