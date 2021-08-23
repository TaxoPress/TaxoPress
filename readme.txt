=== TaxoPress is the Tag, Category, and Taxonomy Manager ===
Contributors: publishpress, kevinB, stevejburge, andergmartins, olatechpro
Tags: category, tag, taxonomy, related posts, tag cloud, terms, tagging, navigation, tag manager, tags manager, term manager, terms manager
Requires at least: 3.3
Tested up to: 5.8
Stable tag: 3.2.1
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

TaxoPress enables you to manage Tags, Categories and all your WordPress taxonomy terms.

== Description ==

TaxoPress allows you to create and manage Tags, Categories and all your WordPress taxonomy terms. With the TaxoPress plugin, you can build new taxonomies, and add them to any post type. 

In TaxoPress, you can manage taxonomies, and also terms. There are tools for you to add, rename, remove, delete and even merge terms. TaxoPress also enables to use your terms for advanced features such as Related Posts and Tag Clouds.

= Key Features in TaxoPress =

1. **Manage taxomomies**: You can create new taxonomies and edit all the settings for each taxonomy.
2. **Related Posts**: Shows lists of posts with similar tags and categories
3. **Tag Clouds**: Create dynamic tag clouds with many configuration options.
4. **Manage terms**: Quickly add, rename, remove, delete and even merge terms.
5. **Automatically create terms from posts**: TaxoPress can analyze your posts and automatically create relevant Tags and Categories.
6. **Automatically suggest terms from posts**: TaxoPress can use the Dandelion API and OpenCalais API to analyze your WordPress content and suggest Tags that you can add to your post.
7. **Automatically link words to terms**: If you have a term called “WordPress”, the Auto Links feature will find any instances of “WordPress” in your content and add link to the archive page for that tag.

= Feature #1. Create and Manage Taxonomies =

TaxoPress can all the taxonomies created by WordPress and your plugins and themes. You can change the settings for each taxonomy. For example, you can change the visual labels, and control whether the taxonomy is organized in parent-child relationships. You can also assign your taxonomies to different post types.

[Click here to read about managing taxonomies](https://taxopress.com/docs/introduction-taxonomies-screen/).

= Feature #2. How to Show Related Posts =

TaxoPress can display posts that are related to the current post. This feature works by checking for shared taxonomy terms. If your post has the terms “Vegetables” and “Food”, then this feature will likely display other posts that also have the terms “Vegetables” and “Food”. The more terms that are shared, the more likely a post is to show.

[Click here to read about Related Posts](https://taxopress.com/docs/introduction-to-related-posts/).

= Feature #3. How to Add Tag Clouds =

TaxoPress allows you to show a cloud of the Tags used on your site. The most popular tags are shown in a large font. The Tag Cloud can be show in one of three ways:

* With a shortcode.
* With the "Tag Cloud" widget.
* With PHP code in your template files.

[Click here to read about Tag Clouds](https://taxopress.com/docs/introduction-to-tag-clouds/).

= Feature #4. How to Manage Terms =

The “Manage Terms” screen in TaxoPress provides you with several useful tools to manage the terms on your site. These tools can be used with any taxonomy. Here

* **Add terms**: Quickly add terms to your content.
* **Rename terms**: Change the name of your terms in bulk.
* **Merge terms**: Combine existing terms together. Very useful for fixing typos in your terms.
* **Remove terms**: Remove terms from all posts, without deleting those terms.
* **Delete terms**: Delete terms in bulk.
* **Delete unused terms**: Delete any terms that are rarely used.

[Click here to read about managing terms](https://taxopress.com/docs/introduction-to-manage-terms/).

= Feature #5. How to Automatically Create Terms =

This feature allows WordPress to examine your post content and title for specified terms and automatically add those terms as Tags. Here’s an example of how it works:

* You add “WordPress” to the keywords list in TaxoPress.
* If your post content or title contains the word “WordPress”, then TaxoPress will automatically add “WordPress” as a term for this post.

[Click here to read about creating terms](https://taxopress.com/docs/introduction-to-auto-terms/).

= Feature #6. How to Automatically Suggest Terms =

The Suggested Tags feature in TaxoPress will analyze your WordPress content and suggest Tags that you can add to your post. The default option is “Local Tags”. These are Tags that have already been created on your site. It is possible to use APIs to automatically suggest Tags for your content. The Dandelion API and OpenCalais API integrations can analyze your Posts and Pages and automatically make suggestions for new Tags.

[Click here to read about suggesting terms](https://taxopress.com/docs/introduction-to-suggested-tags/).

= Feature #7. How to Link Words to Terms =

The Auto Links feature in TaxoPress will automatically add links to your content. If you have a term called “WordPress”, the Auto Links feature will find any instances of “WordPress” in your content and add a link to the archive page for that tag. So any instances of “WordPress” will link to /tag/wordpress.

[Click here to read about Auto Links](https://taxopress.com/docs/introduction-to-auto-links/).

== Installation ==

**Requires PHP 5.6 or ideally PHP 7.**

TaxoPress can be installed in 3 easy steps:

1. Unzip the TaxoPress archive and put all files into a folder like "/wp-content/plugins/simple-tags/"
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Inside the WordPress admin, go to the 'TaxoPress' mennu.

== Frequently Asked Questions ==

= TaxoPress is compatible with which WordPress versions? =

* 2.5 and up are compatible with WordPress 4.x and 5.x
* 2.4 and up are compatible only with WordPress 4.x
* 2.3 and up are compatible only with WordPress 3.5
* 2.0 and up are compatible only with WordPress 3.0 & 3.1 & 3.2 & 3.3
* 1.7 and up are compatible only with WordPress 2.8, 2.9
* 1.6.7 and before are compatible with WordPress 2.3, 2.5, 2.6, 2.7
* Before WP 2.3, you must use the plugin Simple Tagging.

== Screenshots ==

1. You can create new taxonomies and edit all the settings for each taxonomy.
2. With TaxoPress you can show all the terms in one taxonomy. You can build dynamic tag clouds and lists with many configuration options. 
3. TaxoPress has tools to help you manage terms on a busy site. You can quickly add, rename, remove, delete and even merge terms.
4. TaxoPress can analyze your posts and automatically create relevant Tags and Categories.
5. TaxoPress can use the Dandelion API and OpenCalais APIs to analyze your WordPress content and suggest terms that you can add to your post.
6. If you have a term called “WordPress”, the Auto Links feature will find any instances of “WordPress” in your content and add a link to the archive page for that tag.

== Changelog ==

v3.2.1- 2021-08-23
* Fixed: Notice: Undefined property: WP_Post_Type::$taxonomy on plugin activation #756
* Fixed: Couldn't save the related posts 'Title header' with 'None' #697
* Fixed: Auto link showing in WordPress admin #772
* Fixed: Remove unused option in TaxoPress screen edit pages. #709
* Fixed: Added exclusion for <style> elements in auto link #774
* Fixed: Imporove taxonomy page on small screen sizes #749
* Fixed: Add limit to all taxopress number input. #727
* Fixed: Limit taxonomy screen plural label input to 100 characters. #745
* Fixed: Remove slug edit tab for external taxonomies #755
* Fixed: Issue with 'e' character being typed in editor #770
* Fixed: Issue with '&' in auto link #770
* Fixed: Prevent usage of integers(number) only as taxonomy slug #744
* Fixed: Add an option to hide the display if result is empty in "Related Posts", "Terms Display" and "Terms for Current Post" #662
* Fixed: Many other screen improvement to taxopress pages

v3.2.0- 2021-08-09
* Added: Autolink screen with improved features such as:
* Autolink: New autolink UI #501
* Autolink: Option to set autolink terms to lowercase, uppercase or retain content case #161
* Autolink: Option to exlude autolink if the tag is already applied to the article #147
* Autolink: Option to set minimum and maximum character length for autolink condition #132
* Autolink: Support for more all taxonomies #268
* Autolink: Option to restrict Autolink instance to a post type.
* Autolink: Option to exclude autolinks in certain classes/HTML Tags like headers, div class/id etc #160
* Deprecated: Move old Autolink settings to legacy #491

v3.1.2- 2021-07-19
* Fixed: Term display font color empty value error notification #628
* Fixed: Improve settings success/error notification display #629
* Fixed: Free version promo banner color #647
* Fixed: Updated 'Mass edit terms', 'Manage Terms' and 'Auto Terms' dropdown filter taxonomy selection change #661
* Fixed: Limit terms display and related posts taxonomy options to the selected post type taxonomies #681
* Fixed: Terms display showing more than shortcode maximum value #653

v3.1.1- 2021-07-14
* Fixed: TaxoPress related posts block compatibility with WordPress 5.8 #644
* Fixed: Related posts shortcode not working in Gutenberg #646
* Fixed: Undefined array key notice when 'Automatically fill colors between maximum and minimum' is unchecked in Terms Display edit #642
* Fixed: TaxoPress number input accepting negative value #658
* Fixed: Media tag taxonomy still remain attached to a post type after deselecting it #660
* Fixed: TaxoPress widgets compatibility with WordPress 5.8 #666

v3.1.0- 2021-07-12
* Added: Add Related Posts screen #491
* Added: Add Related Posts widget #264
* Added: Add Related Posts Gutenberg block #328
* Deprecated: Move old Related Posts settings to legacy #491
* Fixed: Manage terms 'remove terms' not working when term is number only #610
* Fixed: Failed redirection with header error when adding/editing taxonomies #617
* Fixed: Categories and Tags showing on both Public and Private Taxonomies #621
* Fixed: Improve taxonomy edit screen error messages popup design #618

v3.0.7.2- 2021-06-29
* Added: Add taxonomy privacy filter to taxonomy screen #599
* Fixed: Fixed bugs

v3.0.7.1 - 2021-06-23
* Fixed: Legacy setting showing on all settings pages #571
* Fixed: "Terms for current post" includes all post types #580
* Fixed: Terms been added automatically even when "disable auto tags" is selected #581
* Fixed: "Keywords list" is always added even when the option is unchecked in Auto Terms #575
* Fixed: Added support for hashtag when "whole word" option is selected in auto terms #586
* Removed: Remove "Tag cloud Shortcode" setting #570
* Removed: Remove "Term Group" from "Terms Display" method for choosing terms from the database options #567


v3.0.7 - 2021-06-15
* Added New Terms for Current Post menu
* Added New Terms for Current Post widget
* Deprecated Old Tags for Current Post settings
* Fixed bugs

v3.0.6.1 - 2021-06-03
* Bugs fix and screen tide up

v3.0.6 - 2021-06-01
* Added New Terms display menu
* Added New Terms display widget
* Deprecated Old Tag Cloud settings
* Deprecated Old Tag Cloud widget
* Fixed taxonomy archive page to include all asigned post type posts

v3.0.5.2 - 2021-05-18
* Fixed bugs

v3.0.5.1 - 2021-05-13
* Fixed bugs

v3.0.5 - 2021-05-10
* Introduced pro version
* Fixed bugs

v3.0.4.3 - 2021-04-29
* Fixed bugs

v3.0.4.2 - 2021-04-29
* Fixed Mass Edit Terms Bugs when media is selected
* Updated Taxonomy edit screen
* Updated default taxonomy list sort by name
* Updated to redirect to edit screen after creating new taxonomy
* Fixed other miscellaneous bugs


v3.0.4.1 - 2021-04-21
* Fixed last release bugs

v3.0.4 - 2021-04-21
* Added taxonomy manage page
* Added media tags taxonomy for media page
* Added option to add new taxonomy
* Added option to edit inbuilt and custom taxonomy
* Added option to assign taxonomy to custom post(s)
* Added option to activate and deactivate taxonomy

v3.0.3 - 2021-03-22
* Improved the "Manage Terms" page
* Added feature to untag all posts with specific tag to "Manage Terms" page
* Removed "Autocompletion with old input"
* Small adjustment to the AutoTerms screen
* Small improvements to metaboxes
* Added search to clicktags

v3.0.2 - 2021-03-10
* Restore Dandelion API
* Fixed Tags gets split when having an '&' in name
* Removed Tag4Site API
* Update Opencalais API URL
* Clean up the Auto Terms screen and change suggestion tag list to repeated field

v3.0.1 - 2021-03-03
* Fixed issue with the "tag link format" been stripped out in widgets
* Fixed input display for "Mass Edit Terms" screen going beyond wrapper
* Added valid color picker to color select field in TaxoPress > Settings > Tag cloud
* Updated OpenCalais broken API documentation link
* Removed Alchemy API
* Removed Zemanta API
* Removed Dandelion API
* Removed Proxem API
* Removed Yahoo API
* Hide suggested services in post if there is no active API key
* Delete "Technical informations" in manage terms
* Added auto links for all tags even if post doesn't contain tags
* Updated Auto terms filter layout
* Fixed "Click Tags" for the new input(Gutenberg) not working
* Fixed auto tag still been added after been disabled in general settings
* Updated plugin logo and banner for WordPress.org

v3.0.0 - 2021-02-24
* Enable 'Advanced Manage Terms', 'Related posts by terms' and 'Tag cloud Shortcode' by default
* Removed review request box and link
* Fixed metabox showing up empty if not used
* Removed unused 'Meta keyword' and 'Related Tags' tabs
* Organized all plugin options under one admin menu
* Update footer copyright text
* Changed plugin name to "TaxoPress"

v2.63 - 2021-01-14
* fixed an unescaped database query
* fixed a bug with custom post type name in URL when bulk editing posts

v2.62 - 2020-10-27
* removed all tracking code

v2.61 - 2020-09-30
* fixed issue with Maximum number of links for the same tag
* added dismissible request to rate
* added promo campaign for WP 301 Redirects

v2.6 - 2020-09-10
* fixes for WP v5.5
* WebFactory took over maintenance of the plugin
* 80,000 installs; 2,203,335 downloads

* Version 2.5.8
	* Fix issue that prevent Suggested Tags from working
  * Fix OpenCalais issue (thanks @timbearcub for the help)
* Version 2.5.6
	* Github actions...
* Version 2.5.6
	* Change: remove support of CKEditor for WordPress plugin (not maintained)
	* Bug: Try to fix compat with Classic Editor
	* Bug: Related posts do not appear in the RSS feed.
* Version 2.5.5
    * Bug: Improves the deactivation of the plugin when the PHP version is too old.
* Version 2.5.4
    * Bug #114 : fix tag disappear with quick edit
* Version 2.5.3
    * Feature: Add tracking feature
    * Bug #98 : Fix a PHP notice with link manager
    * Bug: Fix a PHP notice with $post global
    * Feature #113 : Add single option for auto-links
    * Feature #70 : Add a visibility option for click tags feature
    * Bug #35 : Fix a bug with multiple calls of the_content hook
    * Code refactoring in many files, remove old cleanup variables
* Version 2.5.2
    * Improve Gutenberg detection, fix bug with tag suggestion caused by some 3rd party plugins
* Version 2.5.1
    * WP.org bumping
* Version 2.5.0
    * Add PHP7.3 compatibility
    * Preparing compatibility with Gutenberg (Does not work with all features due to a bug into Gutenberg https://github.com/WordPress/gutenberg/issues/15147 )
    * Minor code refactoring
* Version 2.4.7
    * Compatibility WP 4.8
    * Fix bug for self-closing tags (https://github.com/BeAPI/simple-tags/pull/67)
    * Updated spanish translation (https://github.com/BeAPI/simple-tags/pull/73)
    * Fix WPalchemy suggestion for latest API version
    * Code refactoring for JS tags suggest
    * Fix JS bug with tabs and firefox (https://github.com/BeAPI/simple-tags/issues/58)
    * Fix autolinks for "number tags" / only numeric characters term (https://github.com/BeAPI/simple-tags/issues/72)
    * Add "access token" field settings for Dandelion suggestion API
    * Improve "proxem" error message
* Version 2.4.6
    * Compatibility WP 4.5
* Version 2.4.5
    * Add shortcode [st-the-tags] and [st_the_tags]
    * Fix array_flip wp_get_object_terms
* Version 2.4.4
    * Fix error upload WordPress.org
* Version 2.4.3
    * Fix metabox title WP 4.4
* Version 2.4.2
    * Make tags all same count mid-sized (pull request #29) (from Github contribution Sophist-UK)
    * Fix options Advanced Usage on widgets (pull request #28) (from Github contribution Sophist-UK)
* Version 2.4.1
	* Fix possible warning with preg_match function
	* Specify user-agent for some provider
* Version 2.4
    * Test OK vs WP 4.0.x
    * Fix Yahoo terms suggestion (use new API)
    * Fix conflict with ShareThis plugin
    * Add option for autolink title attribute
    * Add current state for "click tags" feature (opacity changed if tags is already selected)
    * Fix order by tag cloud
    * Implement dataTXT provider for suggest terms feature (from Github contribution SpazioDati/master)
    * Implement Tag4Site provider for suggest terms feature (from Sergey Zobin)
    * Fix shortcode usage with &
    * Add support of category name into tag cloud function
* Version 2.3.2
	* Move all get_option request into plugin class, use static
	* Add option for limit autolinks to singular view (default setting, you need change this setting in DB for enable autolinks into all views)
	* Replace old library autocomplete by new jQuery UI autocomplete
	* Add an alternative ENGINE text replacement for autolinks feature, use DOMdocument php extension
	* Fix tags autosuggestion from alchemy API
* Version 2.3.1
	* Rename jQuery autocomplete library, add prefix for fix JS conflict (event manager)
	* Fix OpenCalais suggest tags
	* Replace clean_page_cache by clean_post_tag
* Version 2.3
	* Convert all class to STATIC methods
	* Use error message API (self::displayMessage();)
	* Refresh admin UI (settings)
	* Upgrade JS librairies
	* Try to improve performance, use more WP functions instead custom SQL queries
	* Add UNINSTALL method for delete options created by plugins
* Version 2.2 :
	* Add compatibility with WP3.3
	* Move JavaScript register/enqueue to dedicated hook
* Version 2.1.2 :
	* Add some nonces for improve security for settings panel
* Version 2.1.1 :
	* Add a feature that allow deleting rarely used terms. (based on counter term)
	* Fix bug for allow Suggested Tags for page CPT.
* Version 2.1 :
	* Add compatibility with WP 3.2
	* Fix bug with autocompletion. (jQuery want now a strict content type text/plain)
	* Upgrade JS Libary (jQuery Autocomplete, jQuery Cookie, jQuery bgIframe)
* Version 2.0-beta9 :
	* Fix conflict with plugin using Google Library Javascript for jQuery. Example : "Use Google Libraries"
	* Add an option for choose input text or textarea for old tags field
	* Add an option for min chars autocompletion
* Version 2.0-beta8 :
	* Update POT.
	* Update french translation.
* Version 2.0-beta7 :
	* Add a metabox on write page that allow to deactive autolinks or autotags for a specific post
	* Add an option for restore old feature "Manage terms"
	* Add an option for restore old feature "Related posts"
	* Remove "Clean DB" and "Rename slugs" in manage terms
	* Remove "include cat" on "Related Posts" settings
	* Optimize function "Related posts"
	* Reorganize admin for have a tab features with all features available
	* Use a input text for Simple Tags autocompletion field
	* Remove ID of term from autocompletion
	* Fix autocompletion on mange terms page
	* Add a field for advanced usage on tagcloud widget
* Version 2.0-beta6 :
	* Add Japanese translation (thanks - kazuhisa)
	* Fix a bug with search and taxonomy param for mass edit terms. (ticket #233)
	* Fix a bug with auto tags and whole word option. (ticket #232)
	* Fix a bug with tag/pages for include pages in WP_Query for a tag
	* Improve performance of auto link
	* Fix a bug with max links by posts for auto links
	* Add an option for choose priority hook of auto links
* Version 2.0-beta5 :
	* Fix a bug and a notice with st_the_tags() that not display any tags...
* Version 2.0-beta4 :
	* Fix a fatal with error with autoterms.
	* Fix a bug with autoterms when saving post, somes times called twice...
	* Improve performance when saving posts.
	* Allow old tags field for each custom post type that use post tags.
* Version 2.0-beta3 :
	* Fix a bug when the st_the_tags() function is called.
* Version 2.0-beta2 :
	* Restore empty templates functions for skip errors
	* Fix a bug with autolinks
	* Restore custom post tags feature : st_the_tags()
	* Fix a bug with widget taxo option saving
* Version 2.0-beta1 :
	* This version is a back to fondamentals. Somes features are removed !
	* This version need testing !
	* Remove old marker <!--st_tag_cloud-->
	* Remove related posts
	* Remove related tags
	* Remove tags as HTML keywords
	* Remove nofollow options
	* Remove custom function for display current post tags
	* Remove auto-add post tags
	* Remove embedded tags
	* Change method for tags for page.
	* Improve memory consommation : all feature can be deactived.
	* Improve memory consommation : Stop use class variables for options
	* Support mass edit with CPT/CT
	* Improve AJAX call by using WordPress hook
* Version 1.8.1 :
	* Improve uninstaller
	* Improve code widgets
	* Improve support of custom taxonomies for simple tags features
* Version 1.8.0 :
	* Compatibility 3.0
	* Add an option for auto-tags
	* Remove somes notices
	* Fix a bug with tag cloud and empty terms
	* Fix a bug with comma on font-size CSS depending locales
* Version 1.7.5 :
	* Fix a bug with auto links terms. (type input)
	* Fix cache conflict key
	* Fix a opencalais bug on firt pass integration
	* Fix a bug with limit days/category filter for tag cloud.
	* Fix javascript autocomplete for allow enter new tag.
	* Re-add list of tags on manage page.
* Version 1.7.4.4 :
	* Fix a stupid during the first activation. (PHP4/PHP5)
* Version 1.7.4.3 :
	* Fix a bug with PHP 4.4 and plugin activation. (PHP4 suck...)
	* Remove base class for compatibility PHP4.
	* Fix sanitized HTML on admin.
	* Fix random order for tag cloud.
	* Try a fix for support WP-O-Matic cron.
* Version 1.7.4.2 :
	* Updated Traditional Chinese translation (thank to Neil Lin)
	* Fix a bug with selector taxonomy and user taxonomy. (thank to Nicolas Furno)
* Version 1.7.4.1 :
	* Updated italian translation
	* Fix a bug with old random value for widget/tag cloud call (thank to marc@gregel.com)
* Version 1.7.4 :
	* Release of version 1.7.4, to avoid confusion with the borked version 1.7.2 published in error.
	* Externalize options array on external file for diminue memory consommation. (call only on options page)
	* Fix importers embedded tags
* Version 1.7.2 :
	* Add taxonomy support
	* Rewrite manage page
	* New JS for autocomplete, use AJAX for better performance
	* Fix PHP4.
	* Fix bug with Yahoo API/Tag the net for long post
	* Add OpenCalais, Alchemy and Zemanta (first pass, API offers much more possibilities!)
* Version 1.7.1-rc1.2 : RC 1.2 (this version must be test !)
	* Fix compatibility PHP4
* Version 1.7.1-rc1.1 : RC 1.1 (this version must be test !)
	* Fix a bug with activation hook.
* Version 1.7.1-rc1 : RC 1 (this version must be test !)
	* Somes ajust for Wordpress 2.9
	* Remove 99,99% notices PHP from ST
	* Clean some part of the plugin.
* Version 1.7.1-a1 : Alpha 1
	* Check compatibility WP 2.9 ( fix somes UI bugs )
	* Fix a bug with save Widget options
* Version 1.7b1.1
	* Add exclude option in auto tags link.
* Version 1.7b1
	* Add compatibily WP 2.8
	* Remove support old WP versions
	* Change some things on architecture for optimize performance on admin. (stop copy class variables)
	* Use new API for Widgets
	* Use new API for Taxonomy
	* Remove somes "notices" PHP
	* Fix old cache method.
* Version 1.6.6
	* Add Belorussian translation
* Version 1.6.5
	* Fix redeclare class "Services_JSON_Error" bug
* Version 1.6.4
	* Fix a stupid bug with JavaScript add tags.
* Version 1.6.3
	* Fix a small bug with JSON class.
* Version 1.6.2
	* Click tags and suggested tags can work with default WordPress Tags.
	* The Simple Tags auto completion replace default WordPress Tags input.
	* Restore administration options.
	* Better management of Simple Tags with WP_Scripts and WP_Styles
	* Add an option for auto link tags.
	* Fix a potential error with compatibility old markers.
	* Update french and chineese translation.
* Version 1.6.1
	* Fix a JavaScript error for Suggested Tags.
* Version 1.6.0
	* Add compatibility with WordPress 2.7, use new API for HTTP and Admin. No new features !
* Version 1.5.7
	* Move autolink after ShortCodes
	* Add Italian and German translation
	* Fix a potential bug during the plugin activation.
* Version 1.5.6
	* Improve performance of MetaKeywords and Autolink
	* Conform HTML W3C
* Version 1.5.5
	* Restore full manage page
	* Fix duplicate tags for click tags and autocompletion
	* Lot's of optimization...
	* Update translations
	* Add spanish translation
* Version 1.5.3
	* Fix counter for tags. (you must re-save your posts)
	* Update translations (ja, ru)
* Version 1.5.2.1
	* Fix internationalization with Gengo
	* Update zh_CN translation
* Version 1.5.2
	* Fix widgets
	* Edit priority
	* Fix internationalization
* Version 1.5.1
	* Wait plugin_init for start ST :)
* Version 1.5
	* Add compatibility with WordPress 2.5
* Version 1.3.9
	* Fix nofollow rel feature
	* Remove warning with keywords feature
* Version 1.3.8
	* Fix internationalization when reset plugin options (and installation)
	* Fix auto link feature (word replaced keep original case)
* Version 1.3.7
	* Improved performance of Simple Tags (specially Related Posts)
* Version 1.3.6
	* Fix auto tags with all DB and this feature work also for auto tags save feature
	* Clean lot's url
	* Update japan language
* Version 1.3.5
	* Add an option for auto link case
	* Fix rel HTML for auto link
	* Restict tag cloud for published tag (exclude programmed)
* Version 1.3.4
	* Fix autolink (new algo)
* Version 1.3.3
	* Fix link to new page
	* Update languages
* Version 1.3.2
	* Update japan translation
* Version 1.3.1
	* Plugin compatible with mu-plugins without modifications (NOT TAGS SITE WIDE) (for truth !)
	* Include categories into Tag Cloud and Tag list
	* Improve performance with a better WP Object Cache management
	* Add a global option for rel="nofollow" attribute
	* Add an option to limit keywords in HTML header
	* Desactivate keywords generation if All In One SEO Pack is actived.
	* st_the_tags work outside loop now...
	* Add an option to uninstall all ST options
	* Add 2 options for auto tags features
	* Fix javascript autotag features
	* Fix lot's bugs in admin ;)
* Version 1.3
	* Plugin compatible with mu-plugins without modifications (NOT TAGS SITE WIDE)
	* Fix auto link tags now working without meta-keywords
	* New parameter for auto link tags (min usage)
	* Fix auto link PREG_REPLACE error.
	* New parameter for related posts (min tags shared)
	* Add marker %post_title_attribute% for related posts, to use into title attribute
	* Add Related Tags
	* Add Remove Related Tags
	* Fix alphabetical order in tag cloud with accent
	* New interface for Tags options, more options for easily configuration
	* AJAX  admin
		* AJAX Pagination for Manage Tags pages
	* Fix excessive memory consomation
	* Tags suggestion from Yahoo Term Extractions API, Tag The Net and Local Tags with AJAX request
		* Compatible with TinyMCE, FCKeditor, WYMeditor and quicktags
	* Click tags with AJAX request
	* Preview color in Tag Cloud Admin Options
* Version 1.2.4
	* Fix a bug with inline tags post
* Version 1.2.3
	* Fix Widgets Order/Selection
	* Improve auto link feature
	* Add a param to desactive font-size during tags cloud generation
	* Add a param "min_usage" to display a tag in tags cloud
	* Smallest size and largest size can be the same...
	* Update translation
* Version 1.2.2
	* Add more options for inline related posts / tags
	* Fix empty title bug
	* Add maximum param for the function st_the_tags()
	* Update translation
* Version 1.2.1
	* Fix limit for tag cloud
* Version 1.2
    * New features
		* Auto link tags in post content
		* Auto tags (new and old content)
		* Importer for embedded tags
	* Tag Cloud
		* Fix Tag Cloud Widgets
		* New way for order tags cloud ( 2 steps )
		* Allow embedded tag cloud
		* Add tag cloud for a specific category
	* Related Posts
		* New options for automatic insertion
		* Add a marker for post_excerpt
		* Fixed display nothing when no related posts
	* Current tags posts
		* New options for automatic insertion
	* Administration
		* Check WP version
		* Mass edit tags improved (search terms, filter untagged)
		* Removed untagged page
		* Improve special characters management (& ...)
		* Uniformize alert message
		* Autocompletion improved !
	* Others
		* Improved plugin cache
* Version 1.1.1
	* Fix compatibily with MySQL < 4.1 (Marker "related_tags" of Related Posts is desactived.)
* Version 1.1
	* Fix XML-RPC and Embedded Tags
	* Improve compatibility JS with others plugins (calendar)
	* Minor improve in administration (order keeping on update)
	* Cleaner for empty terms
	* Add limit days for Tag Clouds and Related Posts
	* Add the marker: %relatedtags% for Related Posts => Display tag shared between 2 posts.
	* True random for tag cloud
	* Tag cloud can generate class level for CSS size/color
	* Add Order to Tag Cloud Widgets
	* Add 2 functions
		* st_meta_keywords() : Display keywords for manual insert in header
		* st_the_tags() : Improved the official the_tags() functions.
	* Add marker for Technorati, Flickr and Delicious
* Version 1.0.4a (1.0.4a fix a small bug introduced in 1.0.4)
	* Update 3 translations for 1.0.4
	* Add Japanese translation
	* Fix "exclude tags" in related posts
* Version 1.0.3
	* Add 3 translations (german and chineses (zh_TW and zh_CN))
	* Fix a potential bug with posts relateds and WP Object Cache
	* Fix a bug with autocompletion JavaScript (tag not escape)
	* Possibility to customize date format in related posts
	* Optimization in meta keywords
	* New markers to related posts (see advanced usage), old markers are still available
* Version 1.0.2
	* Keywords
		* Fix a rare bug with problem encoding
		* Delete duplicate keywords
	* Related posts
		* Possibility to randomize post
		* New class for tag UL: "st-related-posts"
	* Tag cloud
		* Possibility to randomize tags
		* New class for tag UL: "st-tag-cloud"
	* Administration
		* Use WP roles instead old levels
		* Possibility to order "Mass edit Tags" by Date or ID
		* Counter now works on "Untagged" page"
		* Fix one bug on Mass Edit Tags, he was impossible to delete all tags for a post.
		* You can desactivate Widgets without have a fatal error
* Version 1.0.1
	* Fixes 2-3 minors bugs
* Version 1.0
	* Initial version