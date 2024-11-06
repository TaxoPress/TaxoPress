<?php
return array(
    // Features
    'active_taxonomies'      => 1,
    'active_terms_display'   => 1,
    'active_post_tags'       => 1,
    'active_related_posts_new' => 1,
    'active_auto_links'      => 1,
    'active_auto_terms'      => 1,
    'active_suggest_terms'   => 1,
    'active_mass_edit'       => 1,
    'active_manage'          => 1,
    'active_related_posts'   => 1,
    'active_autotags'        => 1,
    'active_st_terms'        => 1,
    'active_features_synonyms'      => 1,
    'active_features_linked_terms'  => 1,
    'active_st_posts'        => 1,

    // post terms filter format
    'post_terms_filter_format'  => 'term_name',
    'post_terms_taxonomy_type' => 'public',

    // linked terms
    'linked_terms_taxonomies' => ['category', 'post_tag'],
    'linked_terms_type'       => 'main',

    // term synonyms
    'synonyms_taxonomies' => ['category', 'post_tag'],

    // taxopress ai
    'enable_taxopress_ai_post_metabox' => 1,
    'enable_taxopress_ai_post_post_terms_tab' => 1,
    'enable_taxopress_ai_post_suggest_local_terms_tab' => 1,
    'enable_taxopress_ai_post_existing_terms_tab' => 1,
    'taxopress_ai_post_metabox_default_taxonomy' => 'post_tag',

    // metabox
    'enable_administrator_metabox' => 1,
    'enable_editor_metabox' => 1,
    'enable_author_metabox' => 1,
    'enable_contributor_metabox' => 1,

    'enable_metabox_administrator' => ['category', 'post_tag'],
    'enable_metabox_editor' => ['category', 'post_tag'],
    'enable_metabox_author' => ['category', 'post_tag'],
    'enable_metabox_contributor' => ['category', 'post_tag'],

    'allow_embed_tcloud'     => 1,
    // Auto link
    'auto_link_tags'         => 0,
    'auto_link_min'          => 1,
    'auto_link_all'          => 1,
    'auto_link_case'         => 1,
    'auto_link_exclude'      => '',
    'auto_link_max_by_post'  => 10,
    'auto_link_max_by_tag'   => 1,
    'auto_link_priority'     => 12,
    'auto_link_views'        => 'singular',
    'auto_link_dom'          => 0,
    'auto_link_title'        => __('Posts tagged with %s', 'simple-tags'),
    'auto_link_title_excl'   => 0,
    // The tags
    'tt_feed'                => 0,
    'tt_embedded'            => 'no',
    'tt_separator'           => ', ',
    'tt_before'              => __('Tags: ', 'simple-tags'),
    'tt_after'               => '<br />',
    'tt_notagstext'          => __('No tags for this post.', 'simple-tags'),
    'tt_number'              => 0,
    'tt_inc_cats'            => 0,
    'tt_xformat'             => __('<a href="%tag_link%" title="%tag_name%" %tag_rel%>%tag_name%</a>', 'simple-tags'),
    'tt_adv_usage'           => '',
    // Related Posts
    'rp_taxonomy'            => 'post_tag',
    'rp_feed'                => 0,
    'rp_embedded'            => 'no',
    'rp_order'               => 'count-desc',
    'rp_limit_qty'           => 5,
    'rp_notagstext'          => __('No related posts.', 'simple-tags'),
    'rp_title'               => __('<h4>Related posts</h4>', 'simple-tags'),
    'rp_xformat'             => __('<a href="%post_permalink%" title="%post_title% (%post_date%)">%post_title%</a> (%post_comment%)', 'simple-tags'),
    'rp_adv_usage'           => '',
    // Tag cloud
    'cloud_taxonomy'         => 'post_tag',
    'cloud_selectionby'      => 'count',
    'cloud_selection'        => 'desc',
    'cloud_orderby'          => 'random',
    'cloud_order'            => 'asc',
    'cloud_limit_qty'        => 45,
    'cloud_notagstext'       => __('No tags.', 'simple-tags'),
    'cloud_title'            => __('<h4>Tag Cloud</h4>', 'simple-tags'),
    'cloud_format'           => 'flat',
    'cloud_xformat'          => __('<a href="%tag_link%" id="tag-link-%tag_id%" class="st-tags t%tag_scale%" title="%tag_count% topics" %tag_rel% style="%tag_size% %tag_color%">%tag_name%</a>', 'simple-tags'),
    'cloud_max_color'        => '#000000',
    'cloud_min_color'        => '#CCCCCC',
    'cloud_max_size'         => 22,
    'cloud_min_size'         => 8,
    'cloud_unit'             => 'pt',
    'cloud_adv_usage'        => '',
);
