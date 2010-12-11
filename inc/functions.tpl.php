<?php
/**
 * Generate extended tag cloud
 *
 * @param string $args
 * @return string|array
 */
function st_get_tag_cloud( $args = '' ) {
	global $simple_tags;
	return $simple_tags['client']->extendedTagCloud( $args );
}

/**
 *  Display extended tag cloud
 *
 * @param string $args
 */
function st_tag_cloud( $args = '' ) {
	echo st_get_tag_cloud( $args );
}
?>