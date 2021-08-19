<?php
return array(
    'features'       => array(
        array(
            'active_mass_edit',
            __('Mass edit terms', 'simpletags'),
            'checkbox',
            '1',
            __('This feature allows you to edit the terms of any taxonomy for multiple posts at the same time.', 'simpletags')
        ),
        array(
            'use_click_tags',
            __('Click tags feature', 'simpletags'),
            'checkbox',
            '1',
            __('This feature will allow you to load all the local tags in your database inside the Suggested Tags meta box inside the editor. Once displayed, you can click to add tags to your post.', 'simpletags')
        ),
        array(
            'use_suggested_tags',
            __('Suggested tags feature', 'simpletags'),
            'checkbox',
            '1',
            __('This feature adds a meta box in the editor which will display suggested tags obtained by comparing your post content with various sources of tags. (Yahoo! Term Extraction API, OpenCalais, Tag The Net, Local DB)', 'simpletags')
        ),
        array(
            'active_manage',
            __('Advanced Manage Terms', 'simpletags'),
            'checkbox',
            '1',
            __('This feature allows you to edit, merge, delete and add terms for any taxonomy.', 'simpletags')
        ),
        array(
            'active_autotags',
            __('Auto terms posts', 'simpletags'),
            'checkbox',
            '1',
            __('This feature allow automatically add terms on a post by proceding to a search into content.', 'simpletags')
        )
    ),
    'administration' => array(
        array(
            'order_click_tags',
            __('Click tags order', 'simpletags'),
            'radio',
            array(
                'count-asc'  => __('<code>count-asc</code> &ndash; Least used.', 'simpletags'),
                'count-desc' => __('<code>count-desc</code> &ndash; Most popular.', 'simpletags'),
                'name-asc'   => __('<code>name-asc</code> &ndash; Alphabetical. (default)', 'simpletags'),
                'name-desc'  => __('<code>name-desc</code> &ndash; Inverse Alphabetical.', 'simpletags'),
                'random'     => __('<code>random</code> &ndash; Random.', 'simpletags'),
            )
        ),
        array(
            'click_tags_limit',
            __('Click tags limit', 'simpletags'),
            ['type' => 'number', 'attr' => 'min="0" max=""'],
            'regular-text',
            __('Click tags limit on post screen', 'simpletags')
        ),
        array(
            'opencalais_key',
            __('OpenCalais API Key', 'simpletags'),
            'text',
            'regular-text',
            __('You can create an API key from <a href="https://developers.refinitiv.com/en/api-catalog/open-perm-id/intelligent-tagging-restful-api/documentation">service website</a>', 'simpletags')
        ),
        array(
            'datatxt_access_token',
            __('Dandelion API token', 'simpletags'),
            'text',
            'regular-text',
            __('You can create an API key from <a href="https://dandelion.eu/">service website</a>', 'simpletags')
        ),
        array(
            'datatxt_min_confidence',
            __('Dandelion API confidence value', 'simpletags'),
            'text',
            'regular-text',
            __('Choose a value between 0 and 1. A high value such as 0.8 will provide a few, accurate suggestions. A low value such as 0.2 will produce more suggestions, but they may be less accurate.', 'simpletags')
        ),
    ),


    'legacy'       => array(


        //tag cloud legecy
        array(
            'text_helper',
            'text_helper',
            'helper',
            '',
            '<p class="taxopress-warning"><strong>'.__('These settings are no longer being updated. Please use the "Terms Display" screen instead.', 'simpletags').'</strong></p><br /><br /><ul style="list-style:square;margin-left:20px;">
				<li>' . __('<strong>&#8216;Order tags selection&#8217;</strong> is the first step during tag\'s cloud generation, corresponding to collect tags.', 'simpletags') . '</li>
				<li>' . __('<strong>&#8216;Order tags display&#8217;</strong> is the second. Once tags choosen, you can reorder them before display.', 'simpletags') . '</li>
			</ul>' .
                __('<strong>Example:</strong> You want display randomly the 100 tags most popular.<br />', 'simpletags') .
                __('You must set &#8216;Order tags selection&#8217; to <strong>count-desc</strong> for retrieve the 100 tags most popular and &#8216;Order tags display&#8217; to <strong>random</strong> for randomize cloud.', 'simpletags'),
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_selectionby',
            __('Order by for tags selection:', 'simpletags'),
            'radio',
            array(
                'count'  => __('<code>count</code> &ndash; Counter. (default)', 'simpletags'),
                'name'   => __('<code>name</code> &ndash; Name.', 'simpletags'),
                'random' => __('<code>random</code> &ndash; Random.', 'simpletags'),
            ),
            '',
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_selection',
            __('Order tags selection:', 'simpletags'),
            'radio',
            array(
                'asc'  => __('<code>asc</code> &ndash; Ascending.', 'simpletags'),
                'desc' => __('<code>desc</code> &ndash; Descending.', 'simpletags'),
            ),
            '',
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_orderby',
            __('Order by for tags display:', 'simpletags'),
            'radio',
            array(
                'count'  => __('<code>count</code> &ndash; Counter.', 'simpletags'),
                'name'   => __('<code>name</code> &ndash; Name.', 'simpletags'),
                'random' => __('<code>random</code> &ndash; Random. (default)', 'simpletags'),
            ),
            '',
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_order',
            __('Order tags display:', 'simpletags'),
            'radio',
            array(
                'asc'  => __('<code>asc</code> &ndash; Ascending.', 'simpletags'),
                'desc' => __('<code>desc</code> &ndash; Descending.', 'simpletags'),
            ),
            '',
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_format',
            __('Tags cloud type format:', 'simpletags'),
            'radio',
            array(
                'list' => __('<code>list</code> &ndash; Display a formatted list (ul/li).', 'simpletags'),
                'flat' => __('<code>flat</code> &ndash; Display inline (no list, just a div)', 'simpletags'),
            ),
            '',
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_xformat',
            __('Tag link format:', 'simpletags'),
            'text',
            'widefat',
            __('You can find markers and explanations <a href="https://github.com/WebFactoryLtd/simple-tags/wiki">in the online documentation.</a>', 'simpletags'),
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_limit_qty',
            __('Maximum number of tags to display: (default: 45)', 'simpletags'),
            ['type' => 'number', 'attr' => 'min="0" max=""'],
            'small-text',
            '',
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_notagstext',
            __('Enter the text to show when there is no tag:', 'simpletags'),
            'text',
            'widefat',
            '',
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_title',
            __('Enter the positioned title before the list, leave blank for no title:', 'simpletags'),
            'text',
            'widefat',
            '',
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_max_color',
            __('Most popular color:', 'simpletags'),
            'text-color',
            'medium-text st-color-field',
            __("The colours are hexadecimal colours,  and need to have the full six digits (#eee is the shorthand version of #eeeeee).", 'simpletags'),
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array('cloud_min_color', __('Least popular color:', 'simpletags'), 'text-color', 'medium-text st-color-field',
            '',
            'legacy-tab-content legacy-tag-cloud-content'),
        array(
            'cloud_max_size',
            __('Most popular font size:', 'simpletags'),
            ['type' => 'number', 'attr' => 'min="0" max=""'],
            'small-text',
            __("The two font sizes are the size of the largest and smallest tags.", 'simpletags'),
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array('cloud_min_size', __('Least popular font size:', 'simpletags'), ['type' => 'number', 'attr' => 'min="0" max=""'], 'small-text',
            '',
            'legacy-tab-content legacy-tag-cloud-content'),
        array(
            'cloud_unit',
            __('The units to display the font sizes with, on tag clouds:', 'simpletags'),
            'dropdown',
            'pt/px/em/%',
            __("The font size units option determines the units that the two font sizes use.", 'simpletags'),
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_adv_usage',
            __('<strong>Advanced usage</strong>:', 'simpletags'),
            'text',
            'widefat',
            __('You can use the same syntax as <code>st_tag_cloud()</code> public static function to customize display. See <a href="https://github.com/WebFactoryLtd/simple-tags/wiki">documentation</a> for more details.', 'simpletags'),
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'allow_embed_tcloud',
            __('Tag cloud Shortcode', 'simpletags'),
            'checkbox',
            '1',
            __('Enabling this will allow Wordpress to look for tag cloud shortcode <code>[st_tag_cloud]</code> or <code>[st-tag-cloud]</code> when displaying posts. WordPress replace this shortcode by a tag cloud.', 'simpletags'),
            'legacy-tab-content legacy-tag-cloud-content'
        ),



        //tags for current post legacy
        array(
            'text_helper',
            'text_helper',
            'helper',
            '',
            '<p class="taxopress-warning"><strong>'.__('These settings are no longer being updated. Please use the "Terms for Current Post" screen instead.', 'simpletags').'</strong></p><br /><br />',
            'legacy-tab-content legacy-post-tags-content st-hide-content'
        ),
        array('tt_feed', __('Automatically display tags list into feeds', 'simpletags'), 'checkbox', '1',
            '',
            'legacy-tab-content legacy-post-tags-content st-hide-content'),
        array(
            'tt_embedded',
            __('Automatically display tags list into post content:', 'simpletags'),
            'radio',
            array(
                'no'           => __('<code>no</code> &ndash; Nowhere (default)', 'simpletags'),
                'all'          => __('<code>all</code> &ndash; On your blog and feeds.', 'simpletags'),
                'blogonly'     => __('<code>blogonly</code> &ndash; Only on your blog.', 'simpletags'),
                'homeonly'     => __('<code>homeonly</code> &ndash; Only on your home page.', 'simpletags'),
                'singularonly' => __('<code>singularonly</code> &ndash; Only on your singular view (single & page).', 'simpletags'),
                'singleonly'   => __('<code>singleonly</code> &ndash; Only on your single view.', 'simpletags'),
                'pageonly'     => __('<code>pageonly</code> &ndash; Only on your page view.', 'simpletags'),
            ),
            '',
            'legacy-tab-content legacy-post-tags-content st-hide-content'
        ),
        array('tt_separator', __('Post tag separator string:', 'simpletags'), 'text', 'regular-text',
            '',
            'legacy-tab-content legacy-post-tags-content st-hide-content'),
        array('tt_before', __('Text to display before tags list:', 'simpletags'), 'text', 'regular-text',
            '',
            'legacy-tab-content legacy-post-tags-content st-hide-content'),
        array('tt_after', __('Text to display after tags list:', 'simpletags'), 'text', 'regular-text',
            '',
            'legacy-tab-content legacy-post-tags-content st-hide-content'),
        array(
            'tt_number',
            __('Max tags display:', 'simpletags'),
            ['type' => 'number', 'attr' => 'min="0" max=""'],
            'small-text',
            __('You must set zero (0) for display all tags.', 'simpletags'),
            'legacy-tab-content legacy-post-tags-content st-hide-content'
        ),
        array('tt_inc_cats', __('Include categories in result ?', 'simpletags'), 'checkbox', '1',
            '',
            'legacy-tab-content legacy-post-tags-content st-hide-content'),
        array(
            'tt_xformat',
            __('Tag link format:', 'simpletags'),
            'text',
            'widefat',
            __('You can find markers and explanations <a href="https://github.com/WebFactoryLtd/simple-tags/wiki">in the online documentation.</a>', 'simpletags'),
            'legacy-tab-content legacy-post-tags-content st-hide-content'
        ),
        array('tt_notagstext', __('Text to display if no tags found:', 'simpletags'), 'text', 'widefat',
            '',
            'legacy-tab-content legacy-post-tags-content st-hide-content'),
        array(
            'tt_adv_usage',
            __('<strong>Advanced usage</strong>:', 'simpletags'),
            'text',
            'widefat',
            __('You can use the same syntax as <code>st_the_tags()</code> public static function to customize display. See <a href="https://github.com/WebFactoryLtd/simple-tags/wiki">documentation</a> for more details.', 'simpletags'),
            'legacy-tab-content legacy-post-tags-content st-hide-content'
        ),




        //related posts legacy
        array(
            'text_helper',
            'text_helper',
            'helper',
            '',
            '<p class="taxopress-warning"><strong>'.__('These settings are no longer being updated. Please use the "Related Posts" screen instead.', 'simpletags').'</strong></p><br /><br />',
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array(
            'rp_taxonomy',
            __('Taxonomy:', 'simpletags'),
            'text',
            'widefat',
            __('By default, related posts work with post tags, but you can use a custom taxonomy. Default value : post_tag', 'simpletags'),
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array('rp_feed', __('Automatically display related posts into feeds', 'simpletags'), 'checkbox', '1', '',
            'legacy-tab-content legacy-related-posts-content st-hide-content'),
        array(
            'rp_embedded',
            __('Automatically display related posts into post content', 'simpletags'),
            'dropdown',
            'no/all/blogonly/feedonly/homeonly/singularonly/pageonly/singleonly',
            '<ul>
				<li>' . __('<code>no</code> &ndash; Nowhere (default)', 'simpletags') . '</li>
				<li>' . __('<code>all</code> &ndash; On your blog and feeds.', 'simpletags') . '</li>
				<li>' . __('<code>blogonly</code> &ndash; Only on your blog.', 'simpletags') . '</li>
				<li>' . __('<code>homeonly</code> &ndash; Only on your home page.', 'simpletags') . '</li>
				<li>' . __('<code>singularonly</code> &ndash; Only on your singular view (single & page).', 'simpletags') . '</li>
				<li>' . __('<code>singleonly</code> &ndash; Only on your single view.', 'simpletags') . '</li>
				<li>' . __('<code>pageonly</code> &ndash; Only on your page view.', 'simpletags') . '</li>
			</ul>',
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array(
            'rp_order',
            __('Related Posts Order:', 'simpletags'),
            'dropdown',
            'count-asc/count-desc/date-asc/date-desc/name-asc/name-desc/random',
            '<ul>
				<li>' . __('<code>date-asc</code> &ndash; Older Entries.', 'simpletags') . '</li>
				<li>' . __('<code>date-desc</code> &ndash; Newer Entries.', 'simpletags') . '</li>
				<li>' . __('<code>count-asc</code> &ndash; Least common tags between posts', 'simpletags') . '</li>
				<li>' . __('<code>count-desc</code> &ndash; Most common tags between posts (default)', 'simpletags') . '</li>
				<li>' . __('<code>name-asc</code> &ndash; Alphabetical.', 'simpletags') . '</li>
				<li>' . __('<code>name-desc</code> &ndash; Inverse Alphabetical.', 'simpletags') . '</li>
				<li>' . __('<code>random</code> &ndash; Random.', 'simpletags') . '</li>
			</ul>',
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array(
            'rp_xformat',
            __('Post link format:', 'simpletags'),
            'text',
            'widefat',
            __('You can find markers and explanations <a href="https://github.com/WebFactoryLtd/simple-tags/wiki">in the online documentation.</a>', 'simpletags'),
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array(
            'rp_limit_qty',
            __('Maximum number of related posts to display: (default: 5)', 'simpletags'),
            ['type' => 'number', 'attr' => 'min="0" max=""'],
            'regular-text',
            '',
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array(
            'rp_notagstext',
            __('Enter the text to show when there is no related post:', 'simpletags'),
            'text',
            'widefat',
            '',
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array(
            'rp_title',
            __('Enter the positioned title before the list, leave blank for no title:', 'simpletags'),
            'text',
            'widefat',
            '',
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array(
            'rp_adv_usage',
            __('<strong>Advanced usage</strong>:', 'simpletags'),
            'text',
            'widefat',
            __('You can use the same syntax as <code>st_related_posts()</code>public static function to customize display. See <a href="https://github.com/WebFactoryLtd/simple-tags/wiki">documentation</a> for more details.', 'simpletags'),
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array(
            'active_related_posts',
            __('Related posts by terms', 'simpletags'),
            'checkbox',
            '1',
            __('This feature allows you to display related posts based on terms relation.', 'simpletags'),
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),


        //auto link legacy
        array(
            'auto_link_tags',
            __('Auto links tags', 'simpletags'),
            'checkbox',
            '1',
            __('Example: You have a tag called "WordPress" and your post content contains "wordpress", this feature will replace "wordpress" by a link to "wordpress" tags page. (http://myblog.net/tag/wordpress/)', 'simpletags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
        array(
            'auto_link_min',
            __('Minimum usage for terms', 'simpletags'),
            ['type' => 'number', 'attr' => 'min="1" max="" required'],
            'small-text',
            __('This setting helps prevent rarely used terms from being used by Auto Links. Default: 1.', 'simpletags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
        array(
            'auto_link_max_by_post',
            __('Maximum number of links per article:', 'simpletags'),
            ['type' => 'number', 'attr' => 'min="1" max="" required'],
            'small-text',
            __('This setting determines the maximum number of links created by article. Default: 10.', 'simpletags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
        array(
            'auto_link_max_by_tag',
            __('Maximum number of links for the same tag:', 'simpletags'),
            ['type' => 'number', 'attr' => 'min="1" max="" required'],
            'small-text',
            __('This setting determines the maximum number of links created by article for the same tag. Default: 1.', 'simpletags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
	    array(
		    'auto_link_all',
		    __('Add links for unattached terms', 'simpletags'),
		    'checkbox',
		    '1',
		    __('By default, TaxoPress will only add Auto Links for terms that are attached to the post. If this box is checked, TaxoPress will add links for all terms', 'simpletags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
	    ),
        array(
            'auto_link_case',
            __('Ignore case for auto link feature ?', 'simpletags'),
            'checkbox',
            '1',
            __('Example: If you ignore case, auto link feature will replace the word "wordpress" by the tag link "WordPress".', 'simpletags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
        array(
            'auto_link_exclude',
            __('Exclude some terms from tag link. For Ads Link subtition, etc.', 'simpletags'),
            'text',
            'regular-text',
            __('Example: If you enter the term "Paris", the auto link tags feature will never replace this term by this link.', 'simpletags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
        array(
            'auto_link_priority',
            __('Priority on hook the_content', 'simpletags'),
            ['type' => 'number', 'attr' => 'min="1" max="" required'],
            'small-text',
            __('For expert, possibility to change the priority of autolinks functions on the_content hook. Useful for fix a conflict with an another plugin. Default: 12.', 'simpletags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
        array(
            'auto_link_views',
            __('Enable autolinks into post content for theses views:', 'simpletags'),
            'radio',
            array(
                'no'       => __('<code>no</code> &ndash; Nowhere', 'simpletags'),
                'all'      => __('<code>all</code> &ndash; On your blog and feeds.', 'simpletags'),
                'single'   => __('<code>single</code> &ndash; Only on your single post view.', 'simpletags'),
                'singular' => __('<code>singular</code> &ndash; Only on your singular view (single post & page) (default).', 'simpletags'),
            ),
            '',
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
        array(
            'auto_link_dom',
            __('Try new engine replacement ?', 'simpletags'),
            'checkbox',
            '1',
            __('An engine replacement alternative uses DOMDocument PHP class and theoretically offers better performance. If your server does not offer the functionality, the plugin will use the usual engine.', 'simpletags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
        array(
            'auto_link_title',
            __('Text to display into title attribute for links:', 'simpletags'),
            'text',
            'regular-text',
            '',
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
	    array(
		    'auto_link_title_excl',
		    __('Add links for post title', 'simpletags'),
		    'checkbox',
		    '1',
		    __('By default, TaxoPress will exclude Auto Links for terms that are attached to the post title.', 'simpletags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
	    ),


    ),
);
