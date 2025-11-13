=== ResetCraft ===
Contributors: sreemonkavungal
Donate link: https://paypal.me/sreemonkavungal
Tags: reset, database reset, cleanup, developer tools, debug, maintenance
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple and safe reset tool for WordPress developers. Quickly clear posts, pages, media, comments, taxonomies, custom tables and more — all with clean, controlled deletion.

== Description ==

ResetCraft is a lightweight WordPress reset tool built especially for developers, testers, and staging environments.

It allows you to quickly reset selected data types without touching core files or reinstalling WordPress.

Whether you're testing themes, building plugins, or cleaning up demo content, ResetCraft ensures you reset only what you choose — safely and instantly.

=== 🔧 Key Features ===
- Reset posts, pages, media, categories, tags  
- Reset comments & comment meta  
- Reset taxonomies  
- Reset custom post types  
- Reset custom tables (optional)  
- Reset users (optional)  
- Clear transients & cache  
- Clean WordPress options created by plugins  
- One-click reset panel in WP Admin  
- Full permission checks & nonce validation  
- Developer-friendly hooks for extensions  
- No core files or user login removed unless chosen  
- Works on local, staging, and production (with capability checks)

ResetCraft is built with security-first design and follows WordPress coding standards.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`  
2. Activate **ResetCraft** from the Plugins page  
3. Go to **WP Admin → Tools → ResetCraft**  
4. Select the sections you want to reset  
5. Click **Run Reset**

== Screenshots ==

1. Main reset panel in WP Admin  
2. Selection options for resetting data  
3. Success message & summary report  

(Place your images as: `/assets/screenshot-1.png`, `/assets/screenshot-2.png`, etc.)

== Frequently Asked Questions ==

= Will ResetCraft delete my theme, plugins, or users? =
No. ResetCraft resets only what you select. It does not touch core WordPress files or deactivate plugins unless you choose user reset.

= Is this safe for live websites? =
Yes, but use with caution. Only users with correct capabilities (e.g., `manage_options`) can run reset actions.

= Does it remove plugin options? =
Yes, it can remove autoloaded & non-autoloaded options, depending on your settings.

= Does it delete media files from the server? =
Yes. When you select "Media Reset", attachments and the actual files are deleted safely.

= Will it reset WooCommerce or other plugin data? =
If a plugin uses custom post types, taxonomies, or custom DB tables, ResetCraft can delete them depending on your selections.

= Is there an undo option? =
No. Please create a backup before running a reset.

== Changelog ==

= 1.0.0 =
* Initial release of ResetCraft.
* Admin panel for reset tools.
* Reset posts, pages, media, comments, terms.
* Reset taxonomies & custom post types.
* Option to clear transients & caches.
* Capability checks & security improvements.

== Upgrade Notice ==

= 1.0.0 =
Initial stable release.

== License ==

This plugin is released under the GPLv2 or later.  
You are free to modify and redistribute it following the GPL license terms.

