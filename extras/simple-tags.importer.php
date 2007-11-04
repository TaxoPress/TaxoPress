<?php
/*
Plugin Name: Embedded Tags Importer
Plugin URI: http://wordpress.org/extend/plugins/simple-tags/
Description: Import embedded tags into the new WordPress native tagging structure.
Version: 1.2
Author: Amaury BALMER
Author URI: http://www.herewithme.fr

&copy; Copyright 2007 Amaury BALMER (balmer.amaury@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
*/

// Simple Tags exists ?
if ( !class_exists('SimpleTags') ) {
	return;
}

Class EmbeddedImporter {
	function EmbeddedImporter () {}

	function dispatch () {
		$step = ( empty($_GET['step']) ) ? 0 : (int) $_GET['step'];

		// load the header
		$this->header();
		switch ( $step ) {
			case 0 :
				$this->greet();
				break;
			case 1 :
				$this->importer();
				break;
			case 2:
				$this->cleanup_import();
				break;
		}
		// load the footer
		$this->footer();
	}

	function header()  {
		echo '<div class="wrap">';
		echo '<h2>'.__('Import Embedded Tags', 'simpletags').'</h2>';
		echo '<p>'.__('Steps may take a few minutes depending on the size of your database. Please be patient.', 'simpletags').'</p>';
	}

	function greet() {
		echo '<div class="narrow">';
		echo '<p>'.__('Howdy! This imports tags from embedded tags into this blog using the new WordPress native tagging structure.', 'simpletags').'</p>';
		echo '<p>'.__('To accommodate larger databases for those tag-crazy authors out there, we have made this into an easy 4-step program to help you kick that nasty Embedded Tags habit. Just keep clicking along and we will let you know when you are in the clear!', 'simpletags').'</p>';
		echo '<p><strong>'.__('Don&#8217;t be stupid - backup your database before proceeding!', 'simpletags').'</strong></p>';
		echo '<form action="admin.php?import=simple-tags.importer&amp;step=1" method="post">';
		echo '<p class="submit"><input type="submit" name="submit" value="'.__('Step 1 &raquo;', 'simpletags').'" /></p>';
		echo '</form>';
		echo '</div>';
	}

	function importer() {
		global $simple_tags, $simple_tags_admin;

		$action = false;
		if ( $_GET['action'] == 'import_embedded_tag' ) {
			$action = true;

			// Get values
			$n = ( isset($_GET['n']) ) ? intval($_GET['n']) : 0;
			$start = $_GET['start'];
			$end = $_GET['end'];
			$clean = ( isset($_GET['clean']) ) ? 1 : 0;

			if ( empty($_GET['start']) || empty($_GET['end']) ) {
				wp_die(__('Missing parameters in URL. (Start or End)', 'simpletags'));
			}
		}

		?>
		<p><?php _e('Visit the <a href="http://www.herewithme.fr/wordpress-plugins/simple-tags">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags'); ?></p>

		<?php if ( $action === false ) : ?>

			<div class="narrow">
				<h3><?php _e('Configure and add tags to posts&#8230;', 'simpletags'); ?></h3>

				<form action="admin.php" method="get">
					<input type="hidden" name="import" value="simple-tags.importer" />
					<input type="hidden" name="step" value="1" />
					<input type="hidden" name="action" value="import_embedded_tag" />

					<p><label for="start"><?php _e('Start marker', 'simpletags'); ?></label><br />
						<input type="text" value="[tags]" name="start" id="start" size="10" /></p>

					<p><label for="end"><?php _e('End marker', 'simpletags'); ?></label><br />
						<input type="text" value="[/tags]" name="end" id="end" size="10" /></p>

					<p><input type="checkbox" value="1" id="clean" name="clean" /> <label for="clean"><?php _e('Delete embedded tags once imported ?', 'simpletags'); ?></label></p>

					<p class="submit">
						<input type="submit" name="submit" value="<?php _e('Start import &raquo;', 'simpletags'); ?>" /></p>
				</form>
			</div>

		<?php else:
			echo '<div class="narrow">';

			// Page or not ?
			$post_type_sql = ( $simple_tags->options['use_tag_pages'] == '1' ) ? "post_type IN('page', 'post')" : "post_type = 'post'";

			global $wpdb;
			$objects = $wpdb->get_results( "SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE {$post_type_sql} ORDER BY ID DESC LIMIT {$n}, 20" );

			if( !empty($objects) ) {
				echo '<ul>';
				foreach( (array) $objects as $object ) {
					// Return Tags
					preg_match_all('/(' . $simple_tags_admin->regexEscape($start) . '(.*?)' . $simple_tags_admin->regexEscape($end) . ')/is', $object->post_content, $matches);

					$tags = array();
					foreach ( (array) $matches[2] as $match) {
						foreach( (array) explode(',', $match) as $tag) {
							$tags[] = $tag;
						}
					}

					if( !empty($tags) ) {
						// Remove empty and duplicate elements
						$tags = array_filter($tags, array(&$simple_tags, 'deleteEmptyElement'));
						$tags = array_unique($tags);

						wp_set_post_tags( $object->ID, $tags, true ); // Append tags

						if ( $clean == '1' ) {
							// remove embedded tags
							$new_content = preg_replace('/(' . $simple_tags_admin->regexEscape($start) . '(.*?)' . $simple_tags_admin->regexEscape($end) . ')/is', '', $object->post_content);
							$new_content = addslashes_gpc($new_content);
							$wpdb->query("UPDATE {$wpdb->posts} SET post_content = '{$new_content}' WHERE ID = {$object->ID} LIMIT 1");
						}
					}

					echo '<li>#'. $object->ID .' '. $object->post_title .'</li>';
					unset($tags, $object, $matches, $match, $new_content);
				}
				echo '</ul>';
				?>
				<p><?php _e("If your browser doesn't start loading the next page automatically click this link:", 'simpletags'); ?> <a href="admin.php?import=simple-tags.importer&amp;step=1&amp;action=import_embedded_tag&amp;start=<?php echo $start; ?>&amp;end=<?php echo $end; ?>&amp;clean=<?php echo $clean; ?>&amp;n=<?php echo ($n + 20) ?>"><?php _e('Next content', 'simpletags'); ?></a></p>
				<script type="text/javascript">
					<!--
					function nextpage() {
						location.href = '<?php get_option('siteurl'); ?>/wp-admin/admin.php?import=simple-tags.importer&step=1&action=import_embedded_tag&start=<?php echo $start; ?>&end=<?php echo $end; ?>&clean=<?php echo $clean; ?>&n=<?php echo ($n + 20) ?>';
					}
					setTimeout( 'nextpage()', 250 );
					//-->
				</script>
				<?php
			} else {
				echo '<p><strong>'.__('Done!', 'simpletags').'</strong><br /></p>';
				echo '<form action="admin.php?import=simple-tags.importer&amp;step=2" method="post">';
				echo '<p class="submit"><input type="submit" name="submit" value="'.__('Step 2 &raquo;', 'simpletags').'" /></p>';
				echo '</form>';
			}
			echo '</div>';
		endif; ?>
		</div>
		<?php
	}

	function cleanup_import () {
		$this->done();
	}

	function done() {
		global $simple_tags_admin;
		echo '<div class="narrow">';
		echo '<p><h3>'.__('Import Complete!', 'simpletags').'</h3></p>';
		echo '<p>' . __('OK, so we lied about this being a 4-step program! You&#8217;re done!', 'simpletags') . '</p>';
		echo '<p>' . __('Now wasn&#8217;t that easy?', 'simpletags') . '</p>';
		echo '<p><strong>' . __('You can manage tags now !', 'simpletags') . '</strong></p>';
		echo '</div>';
	}

	function footer() {
		echo '</div>';
	}
}

// create the import object
$embedded_importer = new EmbeddedImporter();

// add it to the import page!
register_importer('simple-tags.importer', __('Embedded Tags', 'simpletags'), __('Import Embedded Tags into the new WordPress native tagging structure.', 'simpletags'), array(&$embedded_importer, 'dispatch'));
?>