=== eCommerce Dashboard ===
Contributors: lordspace,orbisius
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7APYDVPBCSY9A
Tags: wp,orbisius,eCommerce,e-Commerce,mobile stats,android,blackberry,iphone,App,cart66,commerce,ecommerce, iOS, ios7, iphone, mobile, piggy, sales, shopp, touch, web app, woo, woocommerce, wp ecommerce
Requires at least: 3.0
Tested up to: 4.1
Stable tag: 1.0.8
License: GPLv2 or later

This plugin allows you to see your e-commerce sale stats on your mobile device.

== Description ==

This plugin allows you to see your e-commerce sale stats on your mobile device. Currently, it supports WooCommerce but more will be added in the future.
Calculates and shows daily, weekly, monthly and all sales.

= Usage =
* Install and Activate the plugin
* Go to plugin's Settings page by clicking on eCommerce Dashboard menu
* Enable the plugin
* Set the Stats password
* Save Changes

Now, you can access the stats by appending **?ed_stats** parameter to your site link.
You should be prompted to enter password and after you enter the correct one you will see the stats.
The stats link is shown in the plugin's settings page as well.

= Features / Benefits =
* Allows you to see your sale stats right from your mobile device
* Uses internal WordPress caching mechanism to store data (transient api) and therefore faster data retrieval
* Uses a QR code to access the stats page.
* Easy to use

= Demo =
http://www.youtube.com/watch?v=23nUeBGB0Ac


= Support =
> Support is handled on our site: <a href="http://club.orbisius.com/" target="_blank" title="[new window]">http://club.orbisius.com/</a>
> Please do NOT use the WordPress forums or other places to seek support.

= Author =

Svetoslav Marinov (Slavi) | <a href="http://orbisius.com" title="Custom Web Programming, Web Design, e-commerce, e-store, Wordpress Plugin Development, Facebook and Mobile App Development in Niagara Falls, St. Catharines, Ontario, Canada" target="_blank">Custom Web and Mobile Programming by Orbisius.com</a>

== Upgrade Notice ==
n/a

== TODO ==
Aaron: It'd be good to know when most sales were occurring, which hour and which day of the week.

== Screenshots ==
1. Plugin's Settings Page
2. Live demo (password prompt)
2. Live demo (show sale stats)

== Installation ==

1. Unzip the package, and upload `ecommerce-dashboard` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How to use this plugin? =
* Install and Activate the plugin
* Go to plugin's Settings page by clicking on eCommerce Dashboard menu
* Enable the plugin
* Set the Stats password
* Save Changes

= How to access stats? =
You can access the stats by appending **?ed_stats** parameter to your site link.
You should be prompted to enter password and after you enter the correct one you will see the stats.
The stats link is shown in the plugin's settings page as well.

== Changelog ==

= 1.0.8 =
* Tested with WP 4.1

= 1.0.7 =
* Showing Yearly sales
* Tested with WP 4.0.1
* Added some initializations
* Fixed some notices.
* Refactored code to use WooCommerce's report classes
* Added an error message at the top when no ecommerce platform/plugin was detected.
* Focused on the pwd box for easy typing
* Made sure that public side the 'shop_order' parameter is added on the public side so stats can work.

= 1.0.6 =
* Tested with WP 3.8.1

= 1.0.5 =
* Improved the admin's settings page
* Updated the Setting's screenshot to WP 3.8
* Tested with WP 3.8

= 1.0.4 =
* Fixes in FAQ
* Demo video wasn't showing up in the main description

= 1.0.3 =
* Updated FAQ
* Fix: sales stats to be refreshed after 1 hour.
* Added a button to refresh stats
* Added demo video
* Added a link to suggest an idea (mobile page)
* This will stop truncating the app title

= 1.0.2 =
* Fixed bug in Daily/Weekly sales calculations
* Improved the caching logic so it doesn't cache when total is 0

= 1.0.1 =
* Fixed Typo and corrected some description

= 1.0.0 =
* Initial release
