=== Recursive Shortcode ===
Contributors: kgitthoene
Tags: wordpress, plugin, shortcode, recursive
Requires at least: 3.9
Tested up to: 4.0
Stable tag: 1.0
License: MIT
License URI: https://en.wikipedia.org/wiki/MIT_License

Allows to use shortcodes that include shortcodes, and so on.


== Description ==

The Recursive Shortcode plugin provides the `[recursive-shortcode]` shortcode for Wordpress to use shortcodes in shortcodes (nested shortcodes).


== Documentation ==

Just write: `[recursive-shortcode]CONTENT[/recursive-shortcode]`

The `CONTENT` is read by the parser, found shortcodes are properly executed.

[The documentation, more examples and clues can be found on the project page.](https://github.com/kgitthoene/wordpress-plugin-recursive-shortcode/blob/master/README.md#usage)

=== Example ===

(From the banner. See above.)

`[recursive-shortcode][icon name="[urlparam param="icon"]"][/recursive-shortcode]`

First the inner shortcode (urlparam) is evaluated. The result inserted as parameter for the icon-shortcode. `urlparam` is from plugin [URL Params](https://wordpress.org/plugins/url-params/). `icon` is from plugin [Better Font Awesome](https://wordpress.org/plugins/better-font-awesome/).

If you open: https://yourlocation.lan/page-with-recursive-shortcode?icon=file-pdf

You'll get the icon `file-pdf`.

== Installation ==

= Via Wordpress Dashboard (recommended)  =

1. Navigate to Dashboard – Plugins – Add New;
2. Search for Recursive Shortcode;
3. Click Install, then Activate.

= Manual installation =

1. Download the plugin as a .zip file;
2. Unzip downloaded archive and upload the recursive-shortcode folder under your /wp-content/plugins/ directory (resulted plugin path should be /wp-content/plugins/recursive-shortcode/);
3. Navigate to Dashboard – Plugins and activate the plugin.


== Issues ==

[Post your issues on the project page.](https://github.com/kgitthoene/wordpress-plugin-recursive-shortcode/issues)


== Frequently Asked Questions ==

= What does the plugin do? =

It allows you to use nested schortcodes in shortcodes in a propper way.

= What is this plugin? =

Basically a parser for nested shortcodes. With an analysing tool to understand the internal evaluation hierarchy.

= Who should use it? =

Everyone who wants the output of a shortcode as content or parameter of another shortcode.
Simple example: Inject the output from Plugin [URL Params](https://wordpress.org/plugins/url-params/) into [Display Posts](https://wordpress.org/plugins/display-posts-shortcode/).

= Do I need to have coding skills to use this plugin? =

No, just writing shortcodes.

= How do I upgrade? Or get a paid version? =

No upgrade. No payments. It's FOSS.

= My question is not listed. =

[Open an issue on the project page.](https://github.com/kgitthoene/wordpress-plugin-recursive-shortcode/issues)


== Further Information ==

[Please read the README.md of this project.](https://github.com/kgitthoene/wordpress-plugin-recursive-shortcode/blob/master/README.md)


== Screenshots ==

1. Single nested, shortcode.
2. Double nested, shortcode.


== Changelog ==

= 1.0.1 (2020-08-12) =
- Sanitize fields

= 1.0.0 (2020-08-10) =
- Initial release


== Upgrade Notice ==

= 1.0.1 (2020-08-12) =
- Sanitize fields
