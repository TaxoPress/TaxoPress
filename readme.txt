=== Simple Tags ===
Contributors: momo360modena
Donate link: http://www.herewithme.fr/wordpress-plugins/simple-tags#donation
Tags: tag, posts, tags, admin, administration, tagging, navigation, import
Requires at least: 2.8
Tested up to: 2.9.1
Stable tag: 1.7.4.4

Simple Tags is THE perfect tool to manage perfectly your WP 2.8 and 2.9 tags (Tags suggestion, Mass Edit Terms (Tags and Category), Auto link tags, Ajax Autocompletion, Click tags, Related Posts, Related Tags, Advanced edit tags, etc.)

== Description ==

Simple Tags is the successor of Simple Tagging Plugin
**This is THE perfect tool to manage perfeclty your WP 2.8 and 2.9 tags**

It was written with this philosophy : best performances, more secured and brings a lot of new functions

* Administration
	* Tags suggestion from Yahoo! Term Extraction API, OpenCalais, Alchemy, Zemanta, Tag The Net, Local DB with AJAX request
		* Compatible with TinyMCE, FCKeditor, WYMeditor and QuickTags
	* tags management (rename, delete, merge, search and add tags, edit tags ID)
	* **Edit mass tags (more than 50 posts once)**
	* Auto link tags in post content
	* Auto tags !
	* Type-ahead input tags / Autocompletion Ajax
	* Click tags
	* Related tags !
	* Importer for embedded tags
	* Possibility to tag pages (not only posts) and include them inside the tags results
	* Embedded tags ([tags]tag1, tag2[/tags]) for compatibility with old plugins
	* **Easy configuration ! (in WP admin)**

* Blog
	* Meta keywords generate from tags in your header's blog
	* Technorati, Flickr and Delicious tags
	* Dynamic Tag Clouds with colors with Widgets (random order, etc)
	* Related content since common tags
	* Possibility to add related posts inside RSS
	* Extended the_tags function (outside the loop, technorati, etc)

And more...

== Installation ==

The Simple Tags can be installed in 4 easy steps:
	
	1. Unzip "Simple tags" archive and put all files into your "plugins" folder (/wp-content/plugins/) or to create a sub directory into the plugins folder (recommanded), like /wp-content/plugins/simple-tags/
	
	2. Activate the plugin
	
	3. Inside the Wordpress admin, go to Options > Simple Tags, adjust the parameters according to your needs, and save them.
	
	4. You can start advanced tag edit under Manage menu.

== Frequently Asked Questions ==

= Simple Tags is compatible with which WordPress versions ? =

1.7   and upper are compatible only with WordPress 2.8, 2.9 and upper !
1.6.7 and before are compatible with WordPress 2.3 to 2.7
Before WP 2.3, you must use the plugin Simple Tagging.

= How import tags from my old tags plugin ? =

Yes, with official default importer. Manage -> Import
Compatible with UTW, Simple Tagging, Etc.

= Simple Tags is compatible with WPmu ? =

Yes, but it not allow tag cloud site. Soon !

= Simple Tags can import Embedded Tags from old plugins =

Yes, copy importer "simple-tags/extras/simple-tags.importer.php" into "wp-admin/import/simple-tags.importer.php"
Then: Manage - Import - Embedded Tags

== Screenshots ==

1. A example tag cloud (with dynamic color and size)
2. Do you have a not yet tagged blog ? Edit mass tags options is perfect for you : tag 20, 30 or 50 articles with autocompletion in one step !
3. Autotagging your content !
4. Add tags easily with click tags !
3. To help you to add easily tags, Simple Tags has an autocompletion script. When you begin to tape a letter or more, a list a tags appears : you have only to choose ! You can choose the down direction to see all the tags.
6. You also can suggest tags from lot's of service (local, tag the net, yahoo!)

== Changelog ==

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