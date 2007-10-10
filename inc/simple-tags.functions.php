<?php
/**
 * Generate related posts for a post in WP loop
 *
 * @param string $args
 * @return string|array
 */
function st_get_related_posts( $args = '' ) {
	global $simple_tags;
	return $simple_tags->relatedPosts( $args );
}

/**
 * Display related posts for a post in WP loop
 *
 * @param string $args
 */
function st_related_posts( $args = '' ) {
	echo st_get_related_posts( $args );
}

/**
 * Generate extended tag cloud
 *
 * @param string $args
 * @return string|array
 */
function st_get_tag_cloud( $args = '' ) {
	global $simple_tags;
	return $simple_tags->extendedTagCloud( $args );
}

/**
 *  Display extended tag cloud
 *
 * @param string $args
 */
function st_tag_cloud( $args = '' ) {
	echo st_get_tag_cloud( $args );
}

/* Future */
function get_st_related_links( $args = '' ) {
	global $simple_tags;
	return $simple_tags->relatedLinks( $args );
}

function st_related_links( $args = '' ) {
	echo get_st_related_links( $args );
}
?>