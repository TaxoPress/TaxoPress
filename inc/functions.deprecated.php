<?php
/**
 * Deprecated - Generate related posts for a post in WP loop
 *
 * @param string $args
 * @return string|array
 */
function st_get_related_posts( $args = '' ) {
	return '';
}

/**
 * Deprecated - Display related posts for a post in WP loop
 *
 * @param string $args
 */
function st_related_posts( $args = '' ) {
	echo st_get_related_posts( $args );
}

/**
 * Deprecated - Generate meta keywords for HTML header 
 *
 * @return string
 */
function st_get_meta_keywords() {
	return '';
}

/**
 * Deprecated - Display meta keywords for HTML header
 *
 */
function st_meta_keywords() {
	echo st_get_meta_keywords();
}

/**
 * Deprecated - Display related tags
 *
 * @param string $args
 */
function st_related_tags( $args = '' ) {
	echo st_get_related_tags( $args );
}

/**
 * Deprecated - Get related tags
 *
 * @param string $args
 * @return string|array
 */
function st_get_related_tags( $args = '' ) {
	return '';
}

/**
 * Deprecated - Display remove related tags
 *
 * @param string $args
 */
function st_remove_related_tags( $args = '' ) {
	echo st_get_remove_related_tags( $args );
}

/**
 * Deprecated - Get remove related tags
 *
 * @param string $args
 * @return string|array
 */
function st_get_remove_related_tags( $args = '' ) {
	return '';
}
?>