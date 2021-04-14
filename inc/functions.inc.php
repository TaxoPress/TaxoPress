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


/**
 * Change menu item order
 */
//add_filter( 'custom_menu_order', 'submenu_order' );
/*
function submenu_order( $menu_order ) {
    # Get submenu key location based on slug
    global $submenu;


    $settings = $submenu['st_options'];
    $unset_settings = false;
    $unset_taxonomies = false;
    foreach ( $settings as $key => $details ) {
        if ( $details[2] == 'st_options' ) {
            $index = $key;
        }
        if ( $details[2] == 'st_taxonomies' ) {
            $index = $key;
        }
    }
    # Set the 'Blogging' menu below 'General'
    //$submenu['options-general.php'][11] = $submenu['options-general.php'][$index];
    unset( $submenu['st_options'][$index] );
    # Reorder the menu based on the keys in ascending order
    ksort( $submenu['st_options'] );
    # Return the new submenu order
    return $menu_order;
}*/

add_action('custom_menu_order', 'acmeReorderSubmenuItems');	
function acmeReorderSubmenuItems()	{	    
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