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

/**
 * Generate extended current tags post
 *
 * @param string $args 
 * @return string
 * @author Amaury Balmer
 */
function st_get_the_tags( $args = '' ) {
	global $simple_tags;

	if ( isset($simple_tags['client-post_tags']) )
		return $simple_tags['client-post_tags']->extendedPostTags( $args );
		
	return '';
}

/**
 * Display extended current tags post
 *
 * @param string $args 
 * @return void
 * @author Amaury Balmer
 */
function st_the_tags( $args = '' ) {
	echo st_get_the_tags( $args );
}

/**
 * Generate related posts for a post in WP loop
 *
 * @param string $args 
 * @return string|array
 * @author Amaury Balmer
 */
function st_get_related_posts( $args = '' ) {
	global $simple_tags;
	
	if ( isset($simple_tags['client-related_posts']) )
		return $simple_tags['client-related_posts']->relatedPosts( $args );
		
	return '';
}

/**
 * Display related posts for a post in WP loop
 *
 * @param string $args 
 * @return void
 * @author Amaury Balmer
 */
function st_related_posts( $args = '' ) {
	echo st_get_related_posts( $args );
}
?>