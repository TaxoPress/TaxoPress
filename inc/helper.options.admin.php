<?php
return array(
    'features'       => array(
        array(
            'active_taxonomies',
            __('Taxonomies', 'simple-tags'),
            'checkbox',
            '1',
            __('This feature allows you to create new taxonomies and edit all the settings for each taxonomy.', 'simple-tags')
        ),
        array(
            'active_terms_display',
            __('Terms Display', 'simple-tags'),
            'checkbox',
            '1',
            __('This feature allows you to create a customizable display of all the terms in one taxonomy.', 'simple-tags')
        ),
        array(
            'active_post_tags',
            __('Terms for Current Post', 'simple-tags'),
            'checkbox',
            '1',
            __('This feature allows you create a customizable display of all the terms assigned to the current post.', 'simple-tags')
        ),
        array(
            'active_related_posts_new',
            __('Related Posts', 'simple-tags'),
            'checkbox',
            '1',
            __('The Related Posts feature works by checking for shared taxonomy terms. If your post has the terms “WordPress” and “Website”, then Related Posts will display other posts that also have the terms “WordPress” and “Website”.', 'simple-tags')
        ),
        array(
            'active_auto_links',
            __('Auto Links', 'simple-tags'),
            'checkbox',
            '1',
            __('Auto Links can automatically create links to your defined terms. For example, if you have a term called “WordPress”, the Auto Links feature can find the word “WordPress” in your content and add links to the archive page for that term.', 'simple-tags')
        ),
        array(
            'active_auto_terms',
            __('Auto Terms', 'simple-tags'),
            'checkbox',
            '1',
            __('Auto Terms can scan your content and automatically assign terms. For example, you have a term called "WordPress". Auto Terms can analyze your posts and when it finds the word "WordPress", it can add that term to your post.', 'simple-tags')
        ),
        array(
            'active_suggest_terms',
            __('Suggest Terms', 'simple-tags'),
            'checkbox',
            '1',
            __('This feature helps when you\'re writing content. "Suggest Terms" can show a metabox where you can browse all your existing terms. "Suggest Terms" can also analyze your content and find new ideas for terms.', 'simple-tags')
        ),
        array(
            'active_mass_edit',
            __('Mass Edit terms', 'simple-tags'),
            'checkbox',
            '1',
            __('This feature allows you to edit the terms of any taxonomy for multiple posts at the same time.', 'simple-tags')
        ),
        array(
            'active_manage',
            __('Advanced Manage Terms', 'simple-tags'),
            'checkbox',
            '1',
            __('This feature allows you to edit, merge, delete and add terms for any taxonomy.', 'simple-tags')
        )
    ),

    'legacy'       => array(


        //tag cloud legecy
        array(
            'text_helper',
            'text_helper',
            'helper',
            '',
            '<p class="taxopress-warning"><strong>'.__('These settings are no longer being updated. Please use the "Terms Display" screen instead.', 'simple-tags').'</strong></p><br /><br /><ul style="list-style:square;margin-left:20px;">
				<li>' . __('<strong>&#8216;Order tags selection&#8217;</strong> is the first step during tag\'s cloud generation, corresponding to collect tags.', 'simple-tags') . '</li>
				<li>' . __('<strong>&#8216;Order tags display&#8217;</strong> is the second. Once tags choosen, you can reorder them before display.', 'simple-tags') . '</li>
			</ul>' .
                __('<strong>Example:</strong> You want display randomly the 100 tags most popular.<br />', 'simple-tags') .
                __('You must set &#8216;Order tags selection&#8217; to <strong>count-desc</strong> for retrieve the 100 tags most popular and &#8216;Order tags display&#8217; to <strong>random</strong> for randomize cloud.', 'simple-tags'),
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_selectionby',
            __('Order by for tags selection:', 'simple-tags'),
            'radio',
            array(
                'count'  => __('<code>count</code> &ndash; Counter. (default)', 'simple-tags'),
                'name'   => __('<code>name</code> &ndash; Name.', 'simple-tags'),
                'random' => __('<code>random</code> &ndash; Random.', 'simple-tags'),
            ),
            '',
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_selection',
            __('Order tags selection:', 'simple-tags'),
            'radio',
            array(
                'asc'  => __('<code>asc</code> &ndash; Ascending.', 'simple-tags'),
                'desc' => __('<code>desc</code> &ndash; Descending.', 'simple-tags'),
            ),
            '',
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_orderby',
            __('Order by for tags display:', 'simple-tags'),
            'radio',
            array(
                'count'  => __('<code>count</code> &ndash; Counter.', 'simple-tags'),
                'name'   => __('<code>name</code> &ndash; Name.', 'simple-tags'),
                'random' => __('<code>random</code> &ndash; Random. (default)', 'simple-tags'),
            ),
            '',
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_order',
            __('Order tags display:', 'simple-tags'),
            'radio',
            array(
                'asc'  => __('<code>asc</code> &ndash; Ascending.', 'simple-tags'),
                'desc' => __('<code>desc</code> &ndash; Descending.', 'simple-tags'),
            ),
            '',
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_format',
            __('Tags cloud type format:', 'simple-tags'),
            'radio',
            array(
                'list' => __('<code>list</code> &ndash; Display a formatted list (ul/li).', 'simple-tags'),
                'flat' => __('<code>flat</code> &ndash; Display inline (no list, just a div)', 'simple-tags'),
            ),
            '',
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_xformat',
            __('Tag link format:', 'simple-tags'),
            'text',
            'widefat',
            __('You can find markers and explanations <a href="https://github.com/WebFactoryLtd/simple-tags/wiki">in the online documentation.</a>', 'simple-tags'),
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_limit_qty',
            __('Maximum number of tags to display: (default: 45)', 'simple-tags'),
            ['type' => 'number', 'attr' => 'min="0" max=""'],
            'small-text',
            '',
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_notagstext',
            __('Enter the text to show when there is no tag:', 'simple-tags'),
            'text',
            'widefat',
            '',
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_title',
            __('Enter the positioned title before the list, leave blank for no title:', 'simple-tags'),
            'text',
            'widefat',
            '',
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_max_color',
            __('Most popular color:', 'simple-tags'),
            'text-color',
            'medium-text st-color-field',
            __("The colours are hexadecimal colours,  and need to have the full six digits (#eee is the shorthand version of #eeeeee).", 'simple-tags'),
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array('cloud_min_color', __('Least popular color:', 'simple-tags'), 'text-color', 'medium-text st-color-field',
            '',
            'legacy-tab-content legacy-tag-cloud-content'),
        array(
            'cloud_max_size',
            __('Most popular font size:', 'simple-tags'),
            ['type' => 'number', 'attr' => 'min="0" max=""'],
            'small-text',
            __("The two font sizes are the size of the largest and smallest tags.", 'simple-tags'),
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array('cloud_min_size', __('Least popular font size:', 'simple-tags'), ['type' => 'number', 'attr' => 'min="0" max=""'], 'small-text',
            '',
            'legacy-tab-content legacy-tag-cloud-content'),
        array(
            'cloud_unit',
            __('The units to display the font sizes with, on tag clouds:', 'simple-tags'),
            'dropdown',
            'pt/px/em/%',
            __("The font size units option determines the units that the two font sizes use.", 'simple-tags'),
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'cloud_adv_usage',
            __('<strong>Advanced usage</strong>:', 'simple-tags'),
            'text',
            'widefat',
            __('You can use the same syntax as <code>st_tag_cloud()</code> public static function to customize display. See <a href="https://github.com/WebFactoryLtd/simple-tags/wiki">documentation</a> for more details.', 'simple-tags'),
            'legacy-tab-content legacy-tag-cloud-content'
        ),
        array(
            'allow_embed_tcloud',
            __('Tag cloud Shortcode', 'simple-tags'),
            'checkbox',
            '1',
            __('Enabling this will allow Wordpress to look for tag cloud shortcode <code>[st_tag_cloud]</code> or <code>[st-tag-cloud]</code> when displaying posts. WordPress replace this shortcode by a tag cloud.', 'simple-tags'),
            'legacy-tab-content legacy-tag-cloud-content'
        ),



        //tags for current post legacy
        array(
            'text_helper',
            'text_helper',
            'helper',
            '',
            '<p class="taxopress-warning"><strong>'.__('These settings are no longer being updated. Please use the "Terms for Current Post" screen instead.', 'simple-tags').'</strong></p><br /><br />',
            'legacy-tab-content legacy-post-tags-content st-hide-content'
        ),
        array('tt_feed', __('Automatically display tags list into feeds', 'simple-tags'), 'checkbox', '1',
            '',
            'legacy-tab-content legacy-post-tags-content st-hide-content'),
        array(
            'tt_embedded',
            __('Automatically display tags list into post content:', 'simple-tags'),
            'radio',
            array(
                'no'           => __('<code>no</code> &ndash; Nowhere (default)', 'simple-tags'),
                'all'          => __('<code>all</code> &ndash; On your blog and feeds.', 'simple-tags'),
                'blogonly'     => __('<code>blogonly</code> &ndash; Only on your blog.', 'simple-tags'),
                'homeonly'     => __('<code>homeonly</code> &ndash; Only on your home page.', 'simple-tags'),
                'singularonly' => __('<code>singularonly</code> &ndash; Only on your singular view (single & page).', 'simple-tags'),
                'singleonly'   => __('<code>singleonly</code> &ndash; Only on your single view.', 'simple-tags'),
                'pageonly'     => __('<code>pageonly</code> &ndash; Only on your page view.', 'simple-tags'),
            ),
            '',
            'legacy-tab-content legacy-post-tags-content st-hide-content'
        ),
        array('tt_separator', __('Post tag separator string:', 'simple-tags'), 'text', 'regular-text',
            '',
            'legacy-tab-content legacy-post-tags-content st-hide-content'),
        array('tt_before', __('Text to display before tags list:', 'simple-tags'), 'text', 'regular-text',
            '',
            'legacy-tab-content legacy-post-tags-content st-hide-content'),
        array('tt_after', __('Text to display after tags list:', 'simple-tags'), 'text', 'regular-text',
            '',
            'legacy-tab-content legacy-post-tags-content st-hide-content'),
        array(
            'tt_number',
            __('Max tags display:', 'simple-tags'),
            ['type' => 'number', 'attr' => 'min="0" max=""'],
            'small-text',
            __('You must set zero (0) for display all tags.', 'simple-tags'),
            'legacy-tab-content legacy-post-tags-content st-hide-content'
        ),
        array('tt_inc_cats', __('Include categories in result ?', 'simple-tags'), 'checkbox', '1',
            '',
            'legacy-tab-content legacy-post-tags-content st-hide-content'),
        array(
            'tt_xformat',
            __('Tag link format:', 'simple-tags'),
            'text',
            'widefat',
            __('You can find markers and explanations <a href="https://github.com/WebFactoryLtd/simple-tags/wiki">in the online documentation.</a>', 'simple-tags'),
            'legacy-tab-content legacy-post-tags-content st-hide-content'
        ),
        array('tt_notagstext', __('Text to display if no tags found:', 'simple-tags'), 'text', 'widefat',
            '',
            'legacy-tab-content legacy-post-tags-content st-hide-content'),
        array(
            'tt_adv_usage',
            __('<strong>Advanced usage</strong>:', 'simple-tags'),
            'text',
            'widefat',
            __('You can use the same syntax as <code>st_the_tags()</code> public static function to customize display. See <a href="https://github.com/WebFactoryLtd/simple-tags/wiki">documentation</a> for more details.', 'simple-tags'),
            'legacy-tab-content legacy-post-tags-content st-hide-content'
        ),




        //related posts legacy
        array(
            'text_helper',
            'text_helper',
            'helper',
            '',
            '<p class="taxopress-warning"><strong>'.__('These settings are no longer being updated. Please use the "Related Posts" screen instead.', 'simple-tags').'</strong></p><br /><br />',
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array(
            'rp_taxonomy',
            __('Taxonomy:', 'simple-tags'),
            'text',
            'widefat',
            __('By default, related posts work with post tags, but you can use a custom taxonomy. Default value : post_tag', 'simple-tags'),
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array('rp_feed', __('Automatically display related posts into feeds', 'simple-tags'), 'checkbox', '1', '',
            'legacy-tab-content legacy-related-posts-content st-hide-content'),
        array(
            'rp_embedded',
            __('Automatically display related posts into post content', 'simple-tags'),
            'dropdown',
            'no/all/blogonly/feedonly/homeonly/singularonly/pageonly/singleonly',
            '<ul>
				<li>' . __('<code>no</code> &ndash; Nowhere (default)', 'simple-tags') . '</li>
				<li>' . __('<code>all</code> &ndash; On your blog and feeds.', 'simple-tags') . '</li>
				<li>' . __('<code>blogonly</code> &ndash; Only on your blog.', 'simple-tags') . '</li>
				<li>' . __('<code>homeonly</code> &ndash; Only on your home page.', 'simple-tags') . '</li>
				<li>' . __('<code>singularonly</code> &ndash; Only on your singular view (single & page).', 'simple-tags') . '</li>
				<li>' . __('<code>singleonly</code> &ndash; Only on your single view.', 'simple-tags') . '</li>
				<li>' . __('<code>pageonly</code> &ndash; Only on your page view.', 'simple-tags') . '</li>
			</ul>',
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array(
            'rp_order',
            __('Related Posts Order:', 'simple-tags'),
            'dropdown',
            'count-asc/count-desc/date-asc/date-desc/name-asc/name-desc/random',
            '<ul>
				<li>' . __('<code>date-asc</code> &ndash; Older Entries.', 'simple-tags') . '</li>
				<li>' . __('<code>date-desc</code> &ndash; Newer Entries.', 'simple-tags') . '</li>
				<li>' . __('<code>count-asc</code> &ndash; Least common tags between posts', 'simple-tags') . '</li>
				<li>' . __('<code>count-desc</code> &ndash; Most common tags between posts (default)', 'simple-tags') . '</li>
				<li>' . __('<code>name-asc</code> &ndash; Alphabetical.', 'simple-tags') . '</li>
				<li>' . __('<code>name-desc</code> &ndash; Inverse Alphabetical.', 'simple-tags') . '</li>
				<li>' . __('<code>random</code> &ndash; Random.', 'simple-tags') . '</li>
			</ul>',
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array(
            'rp_xformat',
            __('Post link format:', 'simple-tags'),
            'text',
            'widefat',
            __('You can find markers and explanations <a href="https://github.com/WebFactoryLtd/simple-tags/wiki">in the online documentation.</a>', 'simple-tags'),
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array(
            'rp_limit_qty',
            __('Maximum number of related posts to display: (default: 5)', 'simple-tags'),
            ['type' => 'number', 'attr' => 'min="0" max=""'],
            'regular-text',
            '',
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array(
            'rp_notagstext',
            __('Enter the text to show when there is no related post:', 'simple-tags'),
            'text',
            'widefat',
            '',
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array(
            'rp_title',
            __('Enter the positioned title before the list, leave blank for no title:', 'simple-tags'),
            'text',
            'widefat',
            '',
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array(
            'rp_adv_usage',
            __('<strong>Advanced usage</strong>:', 'simple-tags'),
            'text',
            'widefat',
            __('You can use the same syntax as <code>st_related_posts()</code>public static function to customize display. See <a href="https://github.com/WebFactoryLtd/simple-tags/wiki">documentation</a> for more details.', 'simple-tags'),
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),
        array(
            'active_related_posts',
            __('Related posts by terms', 'simple-tags'),
            'checkbox',
            '1',
            __('This feature allows you to display related posts based on terms relation.', 'simple-tags'),
            'legacy-tab-content legacy-related-posts-content st-hide-content'
        ),


        //auto link legacy
        array(
            'auto_link_tags',
            __('Auto links tags', 'simple-tags'),
            'checkbox',
            '1',
            __('Example: You have a tag called "WordPress" and your post content contains "wordpress", this feature will replace "wordpress" by a link to "wordpress" tags page. (http://myblog.net/tag/wordpress/)', 'simple-tags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
        array(
            'auto_link_min',
            __('Minimum usage for terms', 'simple-tags'),
            ['type' => 'number', 'attr' => 'min="1" max="" required'],
            'small-text',
            __('This setting helps prevent rarely used terms from being used by Auto Links. Default: 1.', 'simple-tags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
        array(
            'auto_link_max_by_post',
            __('Maximum number of links per article:', 'simple-tags'),
            ['type' => 'number', 'attr' => 'min="1" max="" required'],
            'small-text',
            __('This setting determines the maximum number of links created by article. Default: 10.', 'simple-tags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
        array(
            'auto_link_max_by_tag',
            __('Maximum number of links for the same tag:', 'simple-tags'),
            ['type' => 'number', 'attr' => 'min="1" max="" required'],
            'small-text',
            __('This setting determines the maximum number of links created by article for the same tag. Default: 1.', 'simple-tags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
	    array(
		    'auto_link_all',
		    __('Add links for unattached terms', 'simple-tags'),
		    'checkbox',
		    '1',
		    __('By default, TaxoPress will only add Auto Links for terms that are attached to the post. If this box is checked, TaxoPress will add links for all terms', 'simple-tags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
	    ),
        array(
            'auto_link_case',
            __('Ignore case for auto link feature ?', 'simple-tags'),
            'checkbox',
            '1',
            __('Example: If you ignore case, auto link feature will replace the word "wordpress" by the tag link "WordPress".', 'simple-tags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
        array(
            'auto_link_exclude',
            __('Exclude some terms from tag link. For Ads Link subtition, etc.', 'simple-tags'),
            'text',
            'regular-text',
            __('Example: If you enter the term "Paris", the auto link tags feature will never replace this term by this link.', 'simple-tags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
        array(
            'auto_link_priority',
            __('Priority on hook the_content', 'simple-tags'),
            ['type' => 'number', 'attr' => 'min="1" max="" required'],
            'small-text',
            __('For expert, possibility to change the priority of autolinks functions on the_content hook. Useful for fix a conflict with an another plugin. Default: 12.', 'simple-tags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
        array(
            'auto_link_views',
            __('Enable autolinks into post content for theses views:', 'simple-tags'),
            'radio',
            array(
                'no'       => __('<code>no</code> &ndash; Nowhere', 'simple-tags'),
                'all'      => __('<code>all</code> &ndash; On your blog and feeds.', 'simple-tags'),
                'single'   => __('<code>single</code> &ndash; Only on your single post view.', 'simple-tags'),
                'singular' => __('<code>singular</code> &ndash; Only on your singular view (single post & page) (default).', 'simple-tags'),
            ),
            '',
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
        array(
            'auto_link_dom',
            __('Try new engine replacement ?', 'simple-tags'),
            'checkbox',
            '1',
            __('An engine replacement alternative uses DOMDocument PHP class and theoretically offers better performance. If your server does not offer the functionality, the plugin will use the usual engine.', 'simple-tags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
        array(
            'auto_link_title',
            __('Text to display into title attribute for links:', 'simple-tags'),
            'text',
            'regular-text',
            '',
            'legacy-tab-content legacy-auto-link-content st-hide-content'
        ),
	    array(
		    'auto_link_title_excl',
		    __('Add links for post title', 'simple-tags'),
		    'checkbox',
		    '1',
		    __('By default, TaxoPress will exclude Auto Links for terms that are attached to the post title.', 'simple-tags'),
            'legacy-tab-content legacy-auto-link-content st-hide-content'
	    ),


    ),
);
