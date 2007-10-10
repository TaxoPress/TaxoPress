=== Plugin Name ===
Contributors: momo360modena
Donate link: http://www.herewithme.fr/wordpress-plugins/simple-tags#donation
Tags: tag, posts, tags, admin
Requires at least: 2.3
Tested up to: 2.3
Stable tag: 1.0.4a

Simple Tags is the successor of Simple Tagging Plugin and is THE perfect tool to manage perfeclty your WP 2.3 tags

== Description ==

It was written with this philosophy : best performances, more secured and brings a lot of new functions.

    * type-ahead input tags
    * auto suggestion of tags
    * tags management (rename, delete, amalgamate, search and add tags, edit tags ID)
    * List of non tagged contents
    * Edit mass tags
    * Possibility to tag pages (not only posts) and include them inside the tags results
    * Related content since common tags
    * Possibility to add related posts inside RSS
    * Dynamic Tag Clouds with colors with Widgets
    * Tags inside your header's blog
    * Embedded tags ([tags]tag1, tag2[/tags]) for retro compatibility

And more...

== Installation ==

The Simple Tags can be installed in 3 easy steps:

   1. Unzip "Simple tags" archive and put all files into your "plugins" folder (/wp-content/plugins/) or to create a sub directory into the plugins folder (recommanded), like /wp-content/plugins/simple-tags/
   2. Activate the plugin
   3. Inside the Wordpress admin, go to tags > tags options, adjust the parameters according to your needs, and save them.

== Frequently Asked Questions ==

= How import tags from my old tags plugin ? =

Yes, with official default importer. Manage -> Import
Compatible with UTW, Simple Tagging, Etc.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the directory of the stable readme.txt, so in this case, `/tags/4.3/screenshot-1.png` (or jpg, jpeg, gif)
2. This is the second screen shot

== Arbitrary section ==
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