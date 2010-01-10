<?php
// Future of WP ?
if ( !function_exists('add_filters') ) :
function add_filters($tags, $function_to_add, $priority = 10, $accepted_args = 1) {
	if ( is_array($tags) ) {
		foreach ( (array) $tags as $tag ) {
			add_filter($tag, $function_to_add, $priority, $accepted_args);
		}
		return true;
	} else {
		return add_filter($tags, $function_to_add, $priority, $accepted_args);
	}
}
endif;

if ( !function_exists('add_actions') ) :
function add_actions($tags, $function_to_add, $priority = 10, $accepted_args = 1) {
	return add_filters($tags, $function_to_add, $priority, $accepted_args);
}
endif;
?>