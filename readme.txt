=== Image Converter for WebP ===
Contributors: badasswp
Tags: webp, image, photo, picture, jpeg, png, gif, bmp, convert.
Requires at least: 4.0
Tested up to: 6.5.3
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Convert your WordPress JPG/PNG images to WebP formats.

== Installation ==

1. Go to 'Plugins > Add New' on your WordPress admin dashboard.
2. Search for 'Image Converter for WebP' plugin from the official WordPress plugin repository.
3. Click 'Install Now' and then 'Activate'.

== Description ==

As an internet user, you already know images can be the difference between a great website experience and a terrible one! Think about how often you've landed on a website and hit the back button because the home page was too busy or the banner image was taking so much time to load due to its size.

You may not realize it, but imagery is a large part of it. This plugin helps take care of all those concerns, by converting your WordPress images to WebP format during page load so that your site loads extremely fast, without any disruptions or downtime.

== Changelog ==

= 1.0.2 =
* Add `webp_img_delete` and `webp_img_metadata_delete` hooks.
* Add Settings page for plugin options.
* Add WebP field on WP attachment modal.
* Add new class methods.
* Fix Bugs and Linting issues within class methods.
* Add more Unit tests & Code coverage.
* Update README notes.

= 1.0.1 =
* Refactor hook webp_img_convert to placement within convert public method.
* Add more Unit tests & Code coverage.
* Update README notes.

= 1.0.0 =
* Initial release
* WebP image conversion for any type of image.
* Custom Hooks - webp_img_options, webp_img_convert, webp_img_attachment_html, webp_img_thumbnail_html.
* Unit Tests coverage.
* Tested up to WP 6.5.3.

== Contribute ==

If you'd like to contribute to the development of this plugin, you can find it on [GitHub](https://github.com/badasswp/webp-image-converter).
