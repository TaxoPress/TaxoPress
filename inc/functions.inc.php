<?php
/**
 * trim and remove empty element
 *
 * @param string $element
 *
 * @return string
 */
function _delete_empty_element( &$element ) {
	$element = stripslashes( $element );
	$element = trim( $element );
	if ( ! empty( $element ) ) {
		return $element;
	}
}

/**
 * Test if page have tags or not...
 *
 * @return boolean
 * @author Amaury Balmer
 */
function is_page_have_tags() {
	$taxonomies = get_object_taxonomies( 'page' );
	if ( in_array( 'post_tag', $taxonomies ) ) {
		return true;
	}

	return false;
}