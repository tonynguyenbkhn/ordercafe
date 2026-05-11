=== WooCommerce Restaurant Ordering ===
Contributors: barn2media
Tags: woocommerce, restaurant, order, ordering, menu, food, takeaway, delivery, collection, list, grid
Requires at least: 6.1
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.1.12
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A restaurant ordering plugin for WooCommerce.

== Description ==

A restaurant ordering plugin for WooCommerce.

== Installation ==

1. Download the plugin from the Order Confirmation page or using the link in your confirmation email.
1. Go to Plugins -> Add New -> Upload, and select the zip file you downloaded.
1. Once installed, click to Activate the plugin.
1. In the dashboard, go to WooCommerce -> Settings -> Restaurant Ordering and enter your License Key in the box at the top. The license key can be found in your order confirmation email.
1. The plugin will automatically create a page called 'Restaurant Order' which contains your food menu based on your product categories.
1. On the same settings page, change any of the order form options as required.
1. Test the Restaurant Order page by viewing it on your website.
1. See the [full documentation](https://barn2.com/kb-categories/wro-kb/) for further details and instructions.

== Frequently Asked Questions ==

Please refer to [our support page](https://barn2.com/support-center/).

== Changelog ==

= 2.1.12 =
Release date 3 February 2026

* Fix: WP Bakery page builder content was not rendered in the product modal
* Dev: Updated internal libraries and tested up to WordPress 6.9 and WooCommerce 10.4.3

<!--more-->

= 2.1.11 =
Release date 10 September 2025

* Dev: Updated the internal libraries and tested up to WooCommerce 10.1.2
* New: Added the translation files

= 2.1.10 =
Release date 22 April 2024

* Dev: Updated the internal libraries
* Dev: Tested up to PHP 8.4, WordPress 6.8 and WooCommerce 9.8.2

= 2.1.9 =
Release date 26 November 2024

* Dev: Better accessibility for the restaurant menu page
* Dev: Added the uninstall file
* Dev: Updated internal libraries and tested up to WordPress 6.7.1 and WooCommerce 9.4.2

= 2.1.8 =
Release date 17 June 2024

* Dev: Upgraded the internal libraries and barn2-lib
* Dev: Tested up to WordPress 6.5.4 and WooCommerce 8.9.2
* Dev: Added visual effects when menu items are focused
* Fix: Don't validate the Product Option fields

= 2.1.7 =
Release date 05 February 2024

* New: Added Dutch translation files
* Dev: Tested up to WordPress 6.4.2 and WooCommerce 8.5.2
* Dev: Added WPML config file
* Dev: Added a filter for WDM compatibility
* Fix: Quick adding to cart doesn't work properly
* Fix: Spinner is not showing when opening modal

= 2.1.6 =
Release date 3 November 2023

* Fix: Product container remained blocked after quick adding to the cart
* Fix: Missing timepicker on the settings page
* Dev: Updated internal libraries and main class
* Dev: Tested up to WordPress 6.3.2 and WooCommerce 8.2.1

= 2.1.5 =
Release date 28 September 2023

* Dev: Added some hooks to the modal template
* Dev: Upgraded to the composer version of barn2-lib
* Dev: Tested up to WordPress 6.3.1 and WooCommerce 8.1.1
* Fix: Fixed some accessibility issues
* Fix: There couldn't be one product with different options in the cart

= 2.1.4 =
Release date 14 August 2023

* Fix: Price on modal is not correct when changing default quantity with WooCommerce Quantity Manager
* Dev: Tested up to WooCommerce 8.0.1


= 2.1.3 =
Release date 2 August 2023

* New: Added quantity increase and decrease feature 
* Fix: Flat Fee (from WPO) calculation is incorrect when using quantity
* Fix: Missing variation_id class in product modal for variable products
* Dev: Tested up to WordPress 6.2.2 and WooCommerce 7.9.0

= 2.1.2 =
Release date 15 December 2022

 * Dev: Updated internal setup wizard library.

= 2.1.1 =
Release date 19 August 2022

 * Fix: Regular price not being displayed for products on sale
 * Dev: Updated compatibility with WordPress 6.0.1 and WooCommerce 6.8.0

= 2.1 =
Release date 5 July 2022

 * Fix: Prevent restaurant menu being displayed twice on the Shop page, when using the Shop page as the restaurant page.
 * Fix: Prevent quantity picker and buy button in modal wrapping on two lines.
 * Fix: Prevent the restaurant menu showing in product search results.
 * Fix: Improve menu navigation scroll when the Admin Bar is displayed.
 * Fix: Prevent conflict with WooCommerce Product Table when using a table on the main Shop page.
 * Fix: Various theme compatibility fixes.
 * Tweak: Improvements to ordering on mobile, especially in Safari browser.
 * Tweak: Improve wording on settings page.
 * Tweak: Indicate the main Restaurant Page in the admin Pages menu.
 * Dev: Tested up to WordPress 6.0 and WooCommerce 6.6.1.
 * Dev: Update internal libraries and settings page styling.
 * Dev: Remove wc_restaurant_ordering_before_menu_navigation and wc_restaurant_ordering_after_menu_navigation hooks.

= 2.0 =
Release date 21 March 2022

 * New: Restaurant opening hours - the restaurant can now be open or closed based on selected opening times.
 * New: WooCommerce Shop page - select your main Shop page as your restuarant order page.
 * New: Product category archives - display restaurant order forms on all WooCommerce category pages.
 * New: Menu category navigation - the food menu can be browsed quickly using a category navigation menu.
 * New: Restaurant information - add your restaurant name, address, opening times and delivery information above the food menu.
 * New: Order form image size option.
 * New: Sort products by name, menu order, price, date, and popularity.
 * New: Show stock status and variation description in the product lightbox.
 * New: Show or hide the cart confirmation message.
 * New: Added plugin setup wizard.
 * New: Support for WooCommerce Fast Cart.
 * New: Support for required checkboxes in WooCommerce Product Addons.
 * Tweak: Support for multiple order forms on one page.
 * Tweak: The 'Show buy button' is now available for the lightbox order method.
 * Tweak: Prevent password protected products being opened in the lightbox.
 * Fix: Prevent conflict with WooCommerce Product Table.
 * Fix: Products with a visibility of 'Shop only' were not visible in the restaurant order form.
 * Fix: Compatibility with Product Addons 4.7.0.
 * Fix: Adding a product to the cart from the lightbox on iOS/Safari triggered the accidental display of Safari's navigation menu.
 * Fix: Remove out of stock message for variations when an in-stock variation is selected.
 * Fix: Product categories were displayed randomly when no categories were selected in the plugin settings.
 * Dev: Various code improvements, improved error handling, new hooks and filters added.
 * Dev: Tested up to WordPress 5.9.2 and WooCommerce 6.3.1.

= 1.2.3 =
Release date 30 November 2021

 * Fix: Compatibility with WooCommerce Product Addons 4.4.
 * Tested up to WooCommerce 5.9 and WordPress 5.8.2
 * Minor improvements to settings page.

= 1.2.2 =
Release date 2 April 2021

 * Added compatibility with WooCommerce Quantity Manager plugin.
 * Added German translation (credit: Stefan Butz).
 * Added support for updated navigation menus in WooCommerce Admin plugin.
 * Tested up to WordPress 5.7 and WooCommerce 5.1.
 * Fix: Prevent REST authentication errors when loading or adding products to order.

= 1.2.1 =
Release date 14 September 2020

 * Changed design of product lightbox on mobiles to better accommodate mobile browsers, especially Safari on iOS.
 * Various CSS improvements, including more consistent styling across themes.
 * Added POT file for translation.
 * Fix: Fixed an issue on iOS Safari which caused the buy button to be hidden when browser navigation UI was shown.
 * Fix: CSS issue with Flatsome theme.

= 1.2 =
Release date 4 August 2020

* The product lightbox now displays the short description if the product doesn't have a long description.
* Improved the display of product lightbox on mobile.
* Improved error messages when a product cannot be ordered.
* Added support for WooCommerce Product Table in the product lightbox.
* Ensure full support for WooCommerce Protected Categories.
* Dev: added filter to allow hidden products to be displayed in order form (default: false).
* Dev: added filters for the product name, modal price, image and modal data.

= 1.1 =
Release date 24 July 2020

 * Changed the display of images in order form to prevent stretching or distortion for products with very small thumbnails or sites where thumbnails are
not cropped to the exact size.

= 1.0 =
Release date 23 July 2020

* Initial release.