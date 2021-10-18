<?php 
add_shortcode('taxopress_relatedposts', 'taxopress_relatedposts_shortcode');
add_filter('the_content', 'taxopress_relatedposts_the_content', 999992);
?>