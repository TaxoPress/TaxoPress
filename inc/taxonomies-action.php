<?php 
add_action('admin_init', 'taxopress_flush_rewrite_rules');
add_action('admin_init', 'taxopress_process_taxonomy', 8);
add_action('init', 'taxopress_do_convert_taxonomy_terms');
add_action('init', 'taxopress_create_custom_taxonomies', 9);  // Leave on standard init for legacy purposes.
add_action('init', 'unregister_tags', 999);
add_action('init', 'taxopress_recreate_custom_taxonomies', 999);
?>