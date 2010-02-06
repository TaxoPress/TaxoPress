<?php
/*
&copy; Copyright 2010 Amaury BALMER (amaury@balmer.fr)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
*/

class EmbeddedImporter {
	/**
	 * Constructor = nothing
	 *
	 */
	function EmbeddedImporter () {}
	
	/**
	 * Select good page depending URL
	 *
	 */
	function dispatch () {
		$step = ( empty($_GET['step']) ) ? 0 : (int) $_GET['step'];
		
		// load the header
		$this->header();
		
		// load the page
		switch ( $step ) {
			case 0 :
				$this->greet();
				break;
			case 1 :
				$this->importer();
				break;
			case 2:
				$this->done();
				break;
		}
		
		// load the footer
		$this->footer();
	}
	
	/**
	 * Print header importer
	 *
	 */
	function header()  {
		echo '<div class="wrap">';
		echo '<h2>'.__('Import Embedded Tags', 'simpletags').'</h2>';
		echo '<p>'.__('Steps may take a few minutes depending on the size of your database. Please be patient.', 'simpletags').'</p>';
		echo '<p>'.__('Visit the <a href="http://www.herewithme.fr/wordpress-plugins/simple-tags">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags').'</p>';
	}
	
	/**
	 * Print avertissement before import (backup DB !)
	 *
	 */
	function greet() {
		echo '<div class="narrow">';
			echo '<p>'.__('Howdy! This imports tags from embedded tags into this blog using the new WordPress native tagging structure.', 'simpletags').'</p>';
			echo '<p>'.__('To accommodate larger databases for those tag-crazy authors out there, we have made this into an easy 4-step program to help you kick that nasty Embedded Tags habit. Just keep clicking along and we will let you know when you are in the clear!', 'simpletags').'</p>';
			echo '<p><strong>'.__('Don&#8217;t be stupid - backup your database before proceeding!', 'simpletags').'</strong></p>';
			echo '<form action="'.admin_url('admin.php').'?import=simple-tags.importer&amp;step=1" method="post">';
				echo '<p class="submit"><input type="submit" name="submit" value="'.__('Step 1 &raquo;', 'simpletags').'" /></p>';
			echo '</form>';
		echo '</div>';
	}
	
	/**
	 * Importer with dynamic pages for skip timeout
	 *
	 */
	function importer() {
		$action = false;
		if ( $_GET['action'] == 'import_embedded_tag' ) {
			$action = true;
			
			// Get values
			$n = ( isset($_GET['n']) ) ? intval($_GET['n']) : 0;
			$start = $_GET['start'];
			$end = $_GET['end'];
			$clean = ( isset($_GET['clean']) ) ? 1 : 0;
			$typep = ( isset($_GET['typep']) ) ? 1 : 0;
			$type_posts = ( isset($_GET['typep']) ) ? "post_type IN('page', 'post')" : "post_type = 'post'";
			
			if ( empty($_GET['start']) || empty($_GET['end']) ) {
				wp_die(__('Missing parameters in URL. (Start or End)', 'simpletags'));
			}
			
			// Get datas
			global $wpdb;
			$objects = $wpdb->get_results( "SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE {$type_posts} ORDER BY ID DESC LIMIT {$n}, 20" );
		}
		 
		 // First step
		if ( $action === false ) : ?>
			
			<div class="narrow">
				<h3><?php _e('Configure and add tags to posts&#8230;', 'simpletags'); ?></h3>
				
				<form action="<?php echo admin_url('admin.php'); ?>" method="get">
					<input type="hidden" name="import" value="simple-tags.importer" />
					<input type="hidden" name="step" value="1" />
					<input type="hidden" name="action" value="import_embedded_tag" />
					
					<p><label for="start"><?php _e('Start marker', 'simpletags'); ?></label><br />
						<input type="text" value="[tags]" name="start" id="start" size="10" /></p>
					
					<p><label for="end"><?php _e('End marker', 'simpletags'); ?></label><br />
						<input type="text" value="[/tags]" name="end" id="end" size="10" /></p>
					
					<p><input type="checkbox" value="1" id="clean" name="clean" /> <label for="clean"><?php _e('Delete embedded tags once imported ?', 'simpletags'); ?></label></p>
					
					<p><input type="checkbox" value="1" id="typep" name="typep" /> <label for="typep"><?php _e('Import also embedded tags from page ?', 'simpletags'); ?></label></p>
					
					<p class="submit">
						<input type="submit" name="submit" value="<?php _e('Start import &raquo;', 'simpletags'); ?>" /></p>
				</form>
			</div>
		
		<?php else: // Dynamic pages
			
			echo '<div class="narrow">';
			
			if( !empty($objects) ) {
				
				echo '<ul>';
				foreach( (array) $objects as $object ) {
					// Return Tags
					preg_match_all('/(' . $this->regexEscape($start) . '(.*?)' . $this->regexEscape($end) . ')/is', $object->post_content, $matches);
					
					$tags = array();
					foreach ( (array) $matches[2] as $match) {
						foreach( (array) explode(',', $match) as $tag) {
							$tags[] = $tag;
						}
					}
					
					if( !empty($tags) ) {
						// Remove empty and duplicate elements
						$tags = array_filter($tags, array(&$this, 'deleteEmptyElement'));
						$tags = array_unique($tags);
						
						wp_set_post_tags( $object->ID, $tags, true ); // Append tags
						
						if ( $clean == '1' ) {
							// remove embedded tags
							$new_content = preg_replace('/(' . $this->regexEscape($start) . '(.*?)' . $this->regexEscape($end) . ')/is', '', $object->post_content);
							$wpdb->update( $wpdb->posts, array('post_content' => $new_content), array('ID' => $object->ID) );
						}
					}
					
					echo '<li>#'. $object->ID .' '. $object->post_title .'</li>';
					unset($tags, $object, $matches, $match, $new_content);
				}
				echo '</ul>';
				?>
				<p><?php _e("If your browser doesn't start loading the next page automatically click this link:", 'simpletags'); ?> <a href="<?php echo admin_url('admin.php'); ?>?import=simple-tags.importer&amp;step=1&amp;action=import_embedded_tag&amp;typep=<?php echo $typep; ?>&amp;start=<?php echo $start; ?>&amp;end=<?php echo $end; ?>&amp;clean=<?php echo $clean; ?>&amp;n=<?php echo ($n + 20) ?>"><?php _e('Next content', 'simpletags'); ?></a></p>
				<script type="text/javascript">
					<!--
					function nextPage() {
						location.href = '<?php echo admin_url('admin.php'); ?>?import=simple-tags.importer&step=1&action=import_embedded_tag&typep=<?php echo $typep; ?>&start=<?php echo $start; ?>&end=<?php echo $end; ?>&clean=<?php echo $clean; ?>&n=<?php echo ($n + 20) ?>';
					}
					setTimeout( 'nextPage', 250 );
					//-->
				</script>
				<?php
			
			} else { // end
				
				echo '<p><strong>'.__('Done!', 'simpletags').'</strong><br /></p>';
				echo '<form action="'.admin_url('admin.php').'?import=simple-tags.importer&amp;step=2" method="post">';
					echo '<p class="submit"><input type="submit" name="submit" value="'.__('Step 2 &raquo;', 'simpletags').'" /></p>';
				echo '</form>';
			
			}
			echo '</div>';
		
		endif; ?>
		</div>
		<?php
	}
	
	/**
	 * Print end message importer
	 *
	 */
	function done() {
		echo '<div class="narrow">';
			echo '<p><h3>'.__('Import Complete!', 'simpletags').'</h3></p>';
			echo '<p>' . __('OK, so we lied about this being a 4-step program! You&#8217;re done!', 'simpletags') . '</p>';
			echo '<p>' . __('Now wasn&#8217;t that easy?', 'simpletags') . '</p>';
			echo '<p><strong>' . __('You can manage tags now !', 'simpletags') . '</strong></p>';
		echo '</div>';
	}
	
	/**
	 * Print footer importer
	 *
	 */
	function footer() {
		echo '</div>';
	}
	
	/**
	 * Escape string so that it can used in Regex. E.g. used for [tags]...[/tags]
	 *
	 * @param string $content
	 * @return string
	 */
	function regexEscape( $content ) {
		return strtr($content, array("\\" => "\\\\", "/" => "\\/", "[" => "\\[", "]" => "\\]"));
	}
	
	/**
	 * trim and remove empty element
	 *
	 * @param string $element
	 * @return string
	 */
	function deleteEmptyElement( &$element ) {
		$element = trim($element);
		if ( !empty($element) ) {
			return $element;
		}
	}
}

// create the import object
$embedded_importer = new EmbeddedImporter();

// add it to the import page!
register_importer('simple-tags.importer', __('Embedded Tags', 'simpletags'), __('Import Embedded Tags into the new WordPress native tagging structure.', 'simpletags'), array(&$embedded_importer, 'dispatch'));
?>