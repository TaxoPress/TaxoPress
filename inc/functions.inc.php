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

	return false;
}

/**
 * Test if page have tags or not...
 *
 * @return boolean
 * @author WebFactory Ltd
 */
function is_page_have_tags() {
	$taxonomies = get_object_taxonomies( 'page' );

	return in_array( 'post_tag', $taxonomies, true );
}

/**
 * Register widget on WP
 */
function st_register_widget() {
	register_widget( 'SimpleTags_Widget' );
}

add_action('custom_menu_order', 'taxopress_re_order_menu');	
function taxopress_re_order_menu()	{	    
    global $submenu;	    
    $newSubmenu = [];	    
    foreach ($submenu as $menuName => $menuItems) {	        
        if ('st_options' === $menuName) {	            
            $newSubmenu[0] = $menuItems[4];	            
            $newSubmenu[1] = $menuItems[1];	          
            $newSubmenu[2] = $menuItems[2];	          
            $newSubmenu[3] = $menuItems[3];	        
            $newSubmenu[4] = $menuItems[0];	            
            $submenu['st_options'] = $newSubmenu;	            
           break;	        
        }	    
    }	
}