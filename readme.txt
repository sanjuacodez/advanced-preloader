=== Advanced Preloader ===
Contributors: sanju-shankar
Tags: preloader, loading, animation, UX, performance
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 7.0
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A customizable WordPress preloader plugin that enhances user experience with beautiful loading animations while your site content loads.

== Description ==

The Advanced Preloader plugin adds customizable loading animations to your WordPress site, offering flexibility to match your design and branding needs.
= Key Features =

* Built-in CSS Loaders (new):
  - 6 animated loaders: Spinner, Dual Ring, Bouncing Dots, Bars, Pulse and Progress Bar
  - Pick one visually, set its color and size, and optionally add text
  - Pure CSS, no image upload needed, and gentler for visitors who prefer reduced motion

* Display Rules (new):
  - Show on every page, the homepage only, only on selected pages, or everywhere except selected pages
  - Show on every page load, once per visit, or on the first visit only
  - Optionally hide it for logged-in users
  - Works with page caching plugins

* Multiple Display Options:
  - Choose between Image, Text, Image + Text, Loader, or Loader + Text
  - Four layout options: Image Over Text, Image Left to Text, Image Right to Text, Image Below Text

* Customizable Design:
  - Background and text color customization
  - Upload your own preloader image
  - Add custom HTML text with multiple lines
  - Random text line display option

* Performance Optimization:
  - Lightweight and fast loading
  - CSS animations for smooth performance
  - Customizable animation speed
  - Delay time configuration

* Responsive Preview:
  - Live preview in admin panel
  - Laptop-style responsive preview window
  - Real-time updates as you customize

* Advanced Controls:
  - Enable/disable preloader with one click
  - Media uploader integration
  - Color picker for easy color selection

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/advanced-preloader` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to **Preloader** in the admin menu to configure the plugin
4. Customize your preloader settings and enjoy!

== Frequently Asked Questions ==

= Can I use a loader animation instead of an image? =
Yes. Set Preloader Type to "Loader Animation" (or "Loader Animation + Text") and pick one of the six built-in loaders. Its color and size are in the Design tab.

= Can I show the preloader only on some pages? =
Yes. In the Display Rules tab, choose Homepage only, Only on selected pages, or Every page except selected pages, and tick the pages.

= Can visitors see it only once? =
Yes. In Display Rules → How Often, choose "Once per visit" or "First visit only". This is remembered in the visitor's browser, so it keeps working with page caching.

= Can I use my own image for the preloader? =
Yes! The plugin includes a media uploader where you can upload and use your custom preloader image.

= Is this plugin mobile-friendly? =
Absolutely! The preloader is fully responsive and works perfectly on all devices.

= Can I add HTML content in the text area? =
Yes, the text field supports basic HTML tags for formatting your preloader text.

= How do I change the animation speed? =
In the Animation tab, set Animation Speed (how long the fade-out takes) and Delay Time (how long to wait after the page loads). Both accept values like 1s, 0.5s or 500ms.

= Can I use different text each time the preloader appears? =
Yes! Enable the "Random Line" display mode to show a different line from your text content each time.

== Screenshots ==

1. General Settings Screen
2. Design Customization Options
3. Animation Settings
4. Live Preview in Admin Panel

== Changelog ==

= 1.4.0 =
* New: Six built-in CSS loaders (Spinner, Dual Ring, Bouncing Dots, Bars, Pulse, Progress Bar) with a visual picker, color and size
* New: Display Rules tab: homepage only, selected pages, excluded pages, once per visit, first visit only, hide for logged-in users
* Improved: The preloader now appears right after the page starts loading (wp_body_open) and sits above other overlays
* Improved: Live preview shows loaders and no longer covers the settings form
* Improved: Screen readers announce the loading state
* Tested with WordPress 7.1
* Fixed PHP warnings when the preloader is enabled before the type and layout settings are saved
* Fixed the Animation Speed setting, which was saved but never applied to the fade-out
* Delay Time and Animation Speed now accept values like 1s, 0.5s or 500ms

= 1.3.2 =
* Added WordPress 6.8.1 support

= 1.3.1 =
* Fixed Security bugs

= 1.3 =
* Improved UX

= 1.2 =
* Added random text line display mode
* Improved responsive preview system
* Enhanced admin interface
* Fixed tab navigation issues
* Added more layout options

= 1.1 =
* Improved media uploader integration
* Fixed color picker implementation
* Enhanced mobile responsiveness

= 1.0 =
* Initial release of Advanced Preloader

== Upgrade Notice ==

= 1.4.0 =
Adds six built-in CSS loaders and display rules (choose pages, once per visit, hide for logged-in users). Your existing settings are kept.

= 1.2 =
This version includes new random text display mode and improved preview system. Update recommended for all users.

== Contributing ==

We welcome contributions from the WordPress community! If you'd like to contribute to this plugin, please visit our GitHub repository:

[Advanced Preloader on GitHub](https://github.com/sanjuacodez/advanced-preloader)

1. Fork the repository
2. Create a feature branch
3. Make your improvements
4. Submit a pull request

Please follow WordPress coding standards and include documentation for any new features.

== Support ==

For support, feature requests, or bug reports, please:
* Visit our [GitHub repository](https://github.com/sanjuacodez/advanced-preloader)
* Open an issue on GitHub
* Contact us through our website

Let's make WordPress better together!

== License ==

This plugin is licensed under the GPLv2 or later. This means you're free to use, modify, and distribute this software as long as you preserve the GPL license terms.

Source code available on GitHub: [https://github.com/sanjuacodez/advanced-preloader](https://github.com/sanjuacodez/advanced-preloader)