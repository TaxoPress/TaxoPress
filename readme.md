# Simple Tags #

**Contributors:** momo360modena  
**Donate link:** http://beapi.fr/donate/  
**Tags:** tag, posts, tags, admin, administration, tagging, navigation, terms, taxonomy  
**Requires at least:** 3.3
**Tested up to:** 4.0
**Stable tag:** 2.4.1

**Add some tools for taxonomies :** Terms suggestion, Mass Edit Terms, Auto link Terms, Ajax Autocompletion, Click Terms, Auto terms, Advanced manage terms, Advanced Post Terms, Related Posts by Terms, etc.  

## Description ##

**I do not offer any support for this plugin. I do not participate in any subject on the WordPress.org support forum. If you find a bug, thank you for the report on the Github repository, and make a exhaustive request (plugin installed / error message / PHP function called). Thank you.**

Simple Tags is the successor of Simple Tagging Plugin
**This is THE perfect tool to manage perfectly your WP terms for any taxonomy**

**It was written with this philosophy :** best performances, more secured and brings a lot of new functions  

This plugin is developped on WordPress 3.3, with the constant WP_DEBUG to TRUE.

* Administration
	* Tags suggestion from Yahoo! Content Analysis, OpenCalais, Alchemy, Zemanta, Tag The Net, Tag4Site, dataTXT and local terms with AJAX request 
		* Compatible with TinyMCE, FCKeditor, WYMeditor and QuickTags
	* tags management (rename, delete, merge, search and add tags, edit tags ID)
	* **Edit mass tags (more than 50 posts once)**
	* Auto link tags in post content
	* Auto tags !
	* Type-ahead input tags / Autocompletion Ajax
	* Click tags
	* Possibility to tag pages (not only posts) and include them inside the tags results
	* **Easy configuration ! (in WP admin)**

* Public
	* Technorati, Flickr and Delicious tags
	* Dynamic Tag Clouds with colors with Widgets (random order, etc)

And more...

## Installation ##

**Required PHP5.**

The Simple Tags can be installed in 3 easy steps:

1. Unzip "Simple tags" archive and put all files into a folder like "/wp-content/plugins/simple-tags/"
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Inside the Wordpress admin, go to Options > Simple Tags, adjust the parameters according to your needs, and save them.

## Frequently Asked Questions ##

### Simple Tags is compatible with which WordPress versions ? ###

* 2.4 and upper are compatible only with WordPress 4.0 !
* 2.3 and upper are compatible only with WordPress 3.5 !
* 2.0 and upper are compatible only with WordPress 3.0 & 3.1 & 3.2 & 3.3 !
* 1.7 and upper are compatible only with WordPress 2.8, 2.9 !
* 1.6.7 and before are compatible with WordPress 2.3, 2.5, 2.6, 2.7
* Before WP 2.3, you must use the plugin Simple Tagging.

## Screenshots ##

###1. A example tag cloud (with dynamic color and size)###
![A example tag cloud (with dynamic color and size)](http://s.wordpress.org/extend/plugins/simple-tags/screenshot-1.png)

**2. Do you have a not yet tagged blog ? Edit mass tags options is perfect for you :** tag 20, 30 or 50 articles with autocompletion in one step !  
###2. Autotagging your content !###
![Autotagging your content !](http://s.wordpress.org/extend/plugins/simple-tags/screenshot-2.png)

###3. Add tags easily with click tags !###
![Add tags easily with click tags !](http://s.wordpress.org/extend/plugins/simple-tags/screenshot-3.png)

**3. To help you to add easily tags, Simple Tags has an autocompletion script. When you begin to tape a letter or more, a list a tags appears :** you have only to choose ! You can choose the down direction to see all the tags.  
###4. You also can suggest tags from lot's of service (Yahoo! Content Analysis, OpenCalais, Alchemy, Zemanta, Tag The Net, Tag4Site, dataTXT and local terms)###
![You also can suggest tags from lot's of service (Yahoo! Content Analysis, OpenCalais, Alchemy, Zemanta, Tag The Net, Tag4Site, dataTXT and local terms)](http://s.wordpress.org/extend/plugins/simple-tags/screenshot-4.png)


## Changelog ##

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
**	* Fix conflict with plugin using Google Library Javascript for jQuery. Example :** "Use Google Libraries"  
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
**	* Restore custom post tags feature :** st_the_tags()  
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
**	* Improve memory consommation :** all feature can be deactived.  
**	* Improve memory consommation :** Stop use class variables for options  
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
*** Version 1.7.1-rc1.2 :** RC 1.2 (this version must be test !)  
	* Fix compatibility PHP4
*** Version 1.7.1-rc1.1 :** RC 1.1 (this version must be test !)  
	* Fix a bug with activation hook.
*** Version 1.7.1-rc1 :** RC 1 (this version must be test !)  
	* Somes ajust for Wordpress 2.9
	* Remove 99,99% notices PHP from ST
	* Clean some part of the plugin.
*** Version 1.7.1-a1 :** Alpha 1  
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
**	* Add the marker:** %relatedtags% for Related Posts => Display tag shared between 2 posts.  
	* True random for tag cloud
	* Tag cloud can generate class level for CSS size/color
	* Add Order to Tag Cloud Widgets
	* Add 2 functions
**		* st_meta_keywords() :** Display keywords for manual insert in header  
**		* st_the_tags() :** Improved the official the_tags() functions.  
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
**		* New class for tag UL:** "st-related-posts"  
	* Tag cloud
		* Possibility to randomize tags
**		* New class for tag UL:** "st-tag-cloud"  
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
	
## Upgrade Notice ##

Nothing to say...
