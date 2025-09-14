=== Musahimoun – Multiple Authors, Guest Authors & Contributors for WordPress Block Themes ===
Contributors: shadialaghbari
Donate link: 
Tags: contributors, guest author, authors, mutli-author, profile
Requires at least: 6.4
Tested up to: 6.8
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin allows you to choose an author, create a guest author or choose multiple authors and contributors..

== Description ==

This plugin allows you to seamlessly add guest authors or contributors to your WordPress posts, specifically within block themes (Full Site Editing). Guest authors/contributors created with this plugin function identically to regular user authors, complete with dedicated archive pages showcasing all their content.

= Key Features =
✅ **Effortless Guest Contributor Integration:** Add guest authors/contributors to your posts without creating user accounts. They'll function just like regular authors.
✅ **Dedicated Author Archives:** Each guest author will have their own archive page displaying all their published posts.
✅ **Multiple Post Guest Author or Contributor Support:** Have a variety of guest authors/contributors? No problem! This plugin allows for the addition of multiple guest authors/contributors.
✅ **Multiple Roles Support:** Have a variety of post contributtor (author, fact-checker, etc)? No problem! This plugin allows for the assingment of multiple roles for contributors.
✅ **Ability to support any post type:** You can chose which post type to add this feature to.
✅ **One click migratiion from PublisherPress Authors:** You can migrate on one click from PublisherPress Authors. Migration from other plugins will be included in new releases soon.

= Othor =
You can use the `mshmn_all_post_author_names` custom field (post meta) to retrieve all primary authors or contributors assigned under the default role.
A common use case is when you want to define a custom schema.org configuration for a post type, and want to set the post author attribute.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/musahimoun` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Musahimoun menu settings to configure the plugin
1. Go to appearance->editor and use the blocks provided by this plugin instead of WordPress default ones or you can use our ready patterns for quick start under "Contributors" pattern category, you can use them in any post single template or author archive template.
1. All Musahimoun blocks must be wrapped by "Musahimoun: contributor query loop" block.

== Frequently Asked Questions ==
= Will Musahimoun work with my theme? =
Only if it was a block theme, Musahimoun developped specifically for block themes. 

== Screenshots ==
1.This is How it will look in front end.

== Changelog ==
= 1.7.1 (2024-06-28) =
* Fix front page 404 error when homepage is set to static.
* Add `mshmn_all_post_author_names` post meta.
* Add ability to make a default role.
* Add default role assingment for new posts.
* Add ability to set a list of included real user roles to be shown in contirbutors table, and to be avaiable when setting a contributor in post.

== Upgrade Notice ==
= 1.7.1 =
This update fixes a front page 404 error when the homepage is set to static, adds the `mshmn_all_post_author_names` post meta, introduces default role assignment for new posts, and allows setting included real user roles for contributors. Please review your settings after updating.

