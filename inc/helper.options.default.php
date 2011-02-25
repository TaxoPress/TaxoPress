<?php
return array(
	// General
	'use_tag_pages' 		=> 1,
	'active_mass_edit' 		=> 1,
	'active_autotags' 		=> 1,
	'allow_embed_tcloud' 	=> 0,
	
	// Auto link
	'auto_link_tags' 		=> 0,
	'auto_link_min' 		=> 1,
	'auto_link_case' 		=> 1,
	'auto_link_exclude' 	=> '',
	'auto_link_max_by_post' => 10,
	'auto_link_max_by_tag'  => 1,
	
	// Administration
	'use_click_tags' 	 => 1,
	'order_click_tags'	 => 'name-asc',
	'use_suggested_tags' => 1,
	'opencalais_key' 	 => '',
	'alchemy_api' 		 => '',
	'zemanta_key' 		 => '',
	'use_autocompletion' => 1,
	
	// Tag cloud
	'cloud_taxonomy' 	=> 'post_tag',
	'cloud_selectionby' => 'count',
	'cloud_selection' 	=> 'desc',
	'cloud_orderby' 	=> 'random',
	'cloud_order' 		=> 'asc',
	'cloud_limit_qty' 	=> 45,
	'cloud_notagstext' 	=> __('No tags.', 'simpletags'),
	'cloud_title' 		=> __('<h4>Tag Cloud</h4>', 'simpletags'),
	'cloud_format' 		=> 'flat',
	'cloud_xformat' 	=> __('<a href="%tag_link%" id="tag-link-%tag_id%" class="st-tags t%tag_scale%" title="%tag_count% topics" %tag_rel% style="%tag_size% %tag_color%">%tag_name%</a>', 'simpletags'),
	'cloud_max_color' 	=> '#000000',
	'cloud_min_color' 	=> '#CCCCCC',
	'cloud_max_size' 	=> 22,
	'cloud_min_size' 	=> 8,
	'cloud_unit' 		=> 'pt',
	'cloud_adv_usage' 	=> ''
);
?>