=== Plugin Name ===
Contributors: cxThemes
Tags: woocommerce, account, shop, profile, customer, edit, admin, switch, permission, testing, rights
Stable tag: 2.16
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Shop as Customer is a plugin for WooCommerce that let's you easily switch and use your store as any Customer.

== Description ==

What it Does
Shop as Customer allows a store Administrator or Shop Manager to shop the front-end of the store as another User, allowing all functionality such as plugins that only work on the product or cart pages and not the Admin Order page, to function normally as if they were that Customer. 

Once installed, the Administrator can easily switch to another user with 2 clicks, then shop the store as that user would see it, with all functionality and plugins that would only be visible to that user, working perfectly. Such as those that work with User roles, inputting custom variables (meta) on product order that are not supported in the Admin Orders screen, etc. This enables incredibly quick and simple manual order creation. And with another click they can switch back to the Administrator or Shop Manager.

Once a cart is created for a User while shopping as them, instead of seeing only the pay button on the checkout page, the Administrator or Shop Manager now has convenient buttons to also email the invoice directly to that User for payment or link to the Order created in the Admin section. 

Features:
* Quickly switch to any Customer
* View and use your store as that Customer
* Quickly switch back to Administrator
* Works with plugins specific to Product & Cart pages or Users.
* Create and send Invoices easily and quickly
* No more fighting with the Admin Orders screen!

Happy Conversions!

== Documentation ==

Please see the included PDF for full instructions on how to use this plugin.

== Changelog ==

= 2.16 =
* Make sure Select2 dropdown z-index is always above all other modals, so it does not get erroneously hidden.

= 2.15 =
* Load our own version Select2 so we are not reliant on WooCommerce loading it.
* Changed our minimum WooCommerce version support to 3

= 2.14 =
* Updated our plugin-update-checker script.

= 2.13 =
* Add 'WC requires' and 'WC tested up to' tags.

= 2.12 =
* Fixed our helper buttons not showing on the Checkout > Thank You page - We separated the checkout JS into it's own file so that it is never erroneously omitted by any dependencies.

= 2.11 =
* We've changed so that when you're on the front-end of your site and click 'Switch To Customer' from the Admin Bar User fly-out menu we first switch back to the Admin > Users page, then open the Shop As Customer modal for you to begin your switch. If you dismiss the modal then you'll be able to find Users using the WP search and click 'Switch To' under any chosen user. Technically we had to remove the Switch modal as it was adversely interfering with all the enhanced select inputs on the front end.
* Updated our Plugin Update Checker.

= 2.10 =
* Updated our Plugin Update Checker.

= 2.09 =
* Replace deprecated `woocommerce_get_page_id()` with `wc_get_page_id()`.

= 2.08 =
* Fix the the Select2 to Customer Search to work with the WC3.0 changes.
* Use WC Order object methods instead of accessing properties directly (WC3.0 compatibility).
* Add helper functions for WC backwards compat.

= 2.07 =
* Fixed 'wp_login' action call - added required second arg to the action call `$user object`.
* Fix modal display CSS using Shop as Customer from the front end of some themes (e.g. The Retailer).

= 2.06 =
* Fixed issue where Switch Back bar was not showing in some themes e.g. Divi.
* Added Switch Back link next to the users name in the top right of the admin bar (if the admin bar is showing).
* Added a setting to enable/disable the Switch Back bar.

= 2.05 =
* Revert back to switching on `init` so that we switch as early as possible.
* Add `do_action( 'wp_login' )` in case other plugins have hooked to this and needs to do custom actions.
* Rather use `get_total()`, not `calculate_totals()` - the totals should already be calculated at that point as the order is already created. Using `calculate_totals()` may cause problems.
* Always check if 'woocommerce_admin_styles' is loaded and if it's not then make sure we load our own WooCommerce Select2 CSS so that the search interface is not initialized with no CSS to style it.

= 2.04 =
* Shop as Customer now works with our other useful plugin - Create Customer on Order - which means you can Create a New Customer and instantly switch to, and shop as, that new customer.
* Fixed issue where some html elements would appear un-styled on the products page when logged-in with a high enough user-role to be able to use Shop as Customer.
* Fixed issue where even though the main user had checked "Remember Me" during login, after switching to and back form a user you may not have your login remembered so would have to log back in after a few short days.
* Added class `.cxsac-shop-as-customer` that will call the switch-to-customer modal from any element that has the classname. This allows you to popup the modal from more places.
* Change the switch action to happen on `init` which causes notices when DEBUG is on, because the WC doesn't like the cart being used too early in WP execution.

= 2.03 =
* Added an option to "Autofocus Customer Search - Autofocus the Customer Search field as the Switch To Customer modal opens (can speed up usability)". This functionality was part of the initial 2.0 release but we've decided to make it optional and turn it off by default.

= 2.02 =
* Fixed php notice about unset $screen->id on line 273.
* Check $wc_shop_as_customer exists before calling it's methods to avoid bug in checkout-functions.php
* Fixed modal breaking down to bottom of window on very narrow displays.
* Added a close button to the switch modal for usability on mobile.

= 2.01 =
* Fixed bug where searching for a user in any of the WooCommerce Select2 search inputs would switch to that user.

= 2.00 =
* We've re-designed the switch-to-customer process - we've turned it into a popup modal so that you can switch to a customer from anywhere, without needing to have the admin-bar enabled. We've left a "Switch to Customer" link in the same place, under the User menu in the top admin-bar, and we've added a new "Shop as Customer" link under WooCommerce in the main left admin-menu that you can also use to call the modal popup modal. If you're not showing the admin-bar and you want to switch to a user on the front-end of your site you can call the popup modal by deep linking using a hash like this: http://yoursite.com/#shop-as-customer.
* We've added a setting 'User Role Hierarchy' that you can use to define the hierarchy of any custom user roles, to prevent less privileged users switching to more privileged users.
* Changed the user search to used the WooCommerce Select2 user search - it's more reliable and allows us to remove the old Chosen search technology.
* Moved the "Shop as Customer Settings" to under Settings in the left admin-menu.
* Changed the Settings Page to use then WooCommerce settings API so it looks simpler and familiar.

= 1.17 =
* We've refreshed the Checkout Process User-Interface. Out aim is to make things more explanatory and user-friendly, with better helpful hints, nicer looking action buttons and a layout that will look better in all themes.

= 1.16 =
* We've changed the way we do plugin auto-updates so we can better manage the demand for our plugins and updates. You will now be notified - as usual - about new available plugin updates. Then we'll require you to save your CodeCanyon purchase-code for our plugin - first time only - which will enable this and any future auto-updates. If you're not sure where to get your purchase code - don't worry, it will be explained in the plugin.
* Added our own 'chosen' jquery. WooCommerce removed the copy they left for backwards compat.
* Fixed reported display issue in IE - 'chosen' dropdown will be vertically stacked behind other elements, and inaccessible.
* Orders created by admins that have a zero total (free) are automatically set to 'processing' and the New Order email sent to the admin. No email is sent to the customer - as usual - as this is an optional action on the Order Complete page.

= 1.15 =
* Fixed security flaw that would allow Admins to switch to Super Admins.
* Fixed stock not reducing on stock managed products when using 'Create this Order'.
* Added more insistent/secure capability testing when attempting to switch user.

= 1.14 =
* Fixed issue where-request-to-pay email could send twice if the order-complete page is refreshed after the email has sent.
* Changed so the cart is linked to the current shopping-as user, it does not persist across switches.
* Fixed so is_woocommerce_active() check does also works for multisite installations.

= 1.13 =
* Refactor the plugin class so plugin is initialized as early as possible. Please let us know if any problems.
* Changed behaviour to switch immediately on choosing the user to switch to without the need to click the switch button.
* Added a check to see if the user still exists before returning list of previously switched users in the UI, so you don't switch to a non-existent user.
* Fixed broken image refs in the CSS.

= 1.12 =
* Fixed 'Invalid payment type' error when trying to create an order in WC 2.4.

= 1.11 =
* Removed unused CSS classes with images causing errors with mod_pagespeed.

= 1.10 =
* Added username to order note when order was created using Shop as Customer.
* Add additional Shop as Customer notice at the top of the edit order page if order was created using Shop as Customer.
* Remove jquery autocomplete from enqueued scripts - Fixes Revolution slider clash.
* Security upgrades.

= 1.09 =
* Added Internationalization how-to to the the docs.
* Updated the language files.
* Changes to the order and priority of the loaded language files. Will not effect anyone who is already using internationalization.
* Changed where in the code the WooCommerce and version number checking is done.
* Made more strings translatable.
* Escaped all add_query_args and remove_query_args for security.
* Updated PluginUpdateChecker class.

= 1.08 =
* Moved ajax checkout actions into shop-as-customer conditional check in order to prevent it taking effect on non logged in users.
* Ensure our ajax override checkout has priority 1 to override default WooCommerce.

= 1.07 =
* Changed our WooCommerce version support - you can read all about it here https://helpcx.zendesk.com/hc/en-us/articles/202241041/
* Change the way emails are sent to stop double emails in WC2.3
* Remove legacy code.
* Fixed possible non static notice.
* CSS styling tweaks.

= 1.06 =
* Fixed bug in debug mode causing notice output before header.

= 1.05 =
* Fixed notice about version constant.

= 1.04 =
* Fixed security allowing shop_manager to switch to administrator.

= 1.03 =
* Added note to Order showing which admin placed the order on behalf of the customer.
* Improved user search speed.
* Fixed warnings from deprecated functions.
* Fixed broken Chozen image path in css.

= 1.02 =
* Updated language files with all the new UI text.

= 1.01 =
* Added ability to Checkout through Payment Gateway on behalf of Customer.
* Changed front-end Checkout UI design and added tooltips to explain each step of the process.
* Changed front-end Checkout so the Invoice Email doesn't send automatically - you now do so by clicking the button on the second page if you choose to.

= 1.0 =
* Initial release.
