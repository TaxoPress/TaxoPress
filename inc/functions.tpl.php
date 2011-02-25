<?php
/**
 * Generate HTML extended tag cloud
 *
 * @param string $args
 * @return string
 * @author Amaury Balmer
 */
function st_get_tag_cloud( $args = '' ) {
	global $simple_tags;
	return $simple_tags['client-cloud']->extendedTagCloud( $args );
}

/**
 * Display extended tag cloud
 *
 * @param string $args
 * @return void
 * @author Amaury Balmer
 */
function st_tag_cloud( $args = '' ) {
	echo st_get_tag_cloud( $args );
}
?>