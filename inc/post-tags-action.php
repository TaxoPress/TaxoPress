<?php 
add_shortcode('taxopress_postterms', 'taxopress_posttags_shortcode');
add_filter('the_content', 'taxopress_posttags_the_content', 999992);
?>