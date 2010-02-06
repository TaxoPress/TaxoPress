<?php
if (isset($_SERVER['SCRIPT_FILENAME']) && 'default.options.php' == basename($_SERVER['SCRIPT_FILENAME']))
	die ('Please do not load this page directly. Thanks!');

return array(
	// General
	'use_tag_pages' 		=> 1,
	'allow_embed_tcloud' 	=> 0,
	'no_follow' 			=> 0,
	
	// Auto link
	'auto_link_tags' 		=> 0,
	'auto_link_min' 		=> 1,
	'auto_link_case' 		=> 1,
	'auto_link_exclude' 	=> '',
	'auto_link_max_by_post' => 10,
	
	// Administration
	'use_click_tags' 	 => 1,
	'use_suggested_tags' => 1,
	'opencalais_key' 	 => '',
	'alchemy_api' 		 => '',
	'zemanta_key' 		 => '',
	'use_autocompletion' => 1,
	
	// Embedded Tags
	'use_embed_tags' 	=> 0,
	'start_embed_tags' 	=> '[tags]',
	'end_embed_tags' 	=> '[/tags]',
	
	// Related Posts
	'rp_feed' 		=> 0,
	'rp_embedded' 	=> 'no',
	'rp_order' 		=> 'count-desc',
	'rp_limit_qty' 	=> 5,
	'rp_notagstext' => __('No related posts.', 'simpletags'),
	'rp_title' 		=> __('<h4>Related posts</h4>', 'simpletags'),
	'rp_xformat' 	=> __('<a href="%post_permalink%" title="%post_title% (%post_date%)">%post_title%</a> (%post_comment%)', 'simpletags'),
	'rp_adv_usage' 	=> '',
	
	// Tag cloud
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
	'cloud_inc_cats' 	=> 0,
	'cloud_adv_usage' 	=> '',
	
	// The tags
	'tt_feed' 		=> 0,
	'tt_embedded' 	=> 'no',
	'tt_separator' 	=> ', ',
	'tt_before' 	=> __('Tags: ', 'simpletags'),
	'tt_after' 		=> '<br />',
	'tt_notagstext' => __('No tags for this post.', 'simpletags'),
	'tt_number' 	=> 0,
	'tt_inc_cats' 	=> 0,
	'tt_xformat' 	=> __('<a href="%tag_link%" title="%tag_name%" %tag_rel%>%tag_name%</a>', 'simpletags'),
	'tt_adv_usage' 	=> '',
	
	// Related tags
	'rt_number' 	=> 5,
	'rt_order' 		=> 'count-desc',
	'rt_separator' 	=> ' ',
	'rt_format' 	=> 'list',
	'rt_method' 	=> 'OR',
	'rt_title' 		=> __('<h4>Related tags</h4>', 'simpletags'),
	'rt_notagstext' => __('No related tags found.', 'simpletags'),
	'rt_xformat' 	=> __('<span>%tag_count%</span> <a href="%tag_link_add%">+</a> <a href="%tag_link%">%tag_name%</a>', 'simpletags'),
	
	// Remove related tags
	'rt_remove_separator' 	=> ' ',
	'rt_remove_format' 		=> 'list',
	'rt_remove_notagstext' 	=> ' ',
	'rt_remove_xformat' 	=> __('&raquo; <a href="%tag_link_remove%" title="Remove %tag_name_attribute% from search">Remove %tag_name%</a>', 'simpletags'),
	
	// Meta keywords
	'meta_autoheader' 		=> 1,
	'meta_always_include' 	=> '',
	'meta_keywords_qty' 	=> 0,
	
	// Auto tags
	'use_auto_tags' => 0,
	'at_all' 		=> 0,
	'at_empty' 		=> 0,
	'auto_list' 	=> ''
);
?>