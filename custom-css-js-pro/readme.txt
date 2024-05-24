=== Simple Custom CSS and JS PRO ===
Created: 06/12/2015
Contributors: diana_burduja
Email: diana@burduja.eu
Tags: CSS, JS, javascript, custom CSS, custom JS, custom style, site css, add style, customize theme, custom code, external css, css3, style, styles, stylesheet, theme, editor, design, admin
Requires at least: 3.0.1
Tested up to: 6.3
Stable tag: 4.34
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Requires PHP: 5.2.4

Easily add Custom CSS or JS to your website with an awesome editor.

== Description ==

Customize your WordPress site's appearance by easily adding custom CSS and JS code without even having to modify your theme or plugin files. This is perfect for adding custom CSS tweaks to your site.

= Features =
* **Text editor** with syntax highlighting
* Print the code **inline** or included into an **external file**
* Print the code in the **header** or the **footer**
* Add CSS or JS to the **frontend** or the **admin side**
* Add as many codes as you want
* Keep your changes also when you change the theme

= Frequently Asked Questions =
* **Can I recover the codes if I previous uninstalled the plugin?**
No, on the `Custom CSS and JS` plugin's uninstall all the added code will be removed. Before uninstalling make sure you don't need the codes anymore.

* **What if I want to add multiple external CSS codes?**
If you write multiple codes of the same type (for example: two external CSS codes), then all of them will be printed one after another

* **Will this plugin affect the loading time?**
When you click the `Save` button the codes will be cached in files, so there are no tedious database queries.

* **Does the plugin modify the code I write in the editor?**
No, the code is printed exactly as in the editor. It is not modified/checked/validated in any way. You take the full responsability for what is written in there.

* **My code doesn't show on the website**
Try one of the following:
1. If you are using any caching plugin (like "W3 Total Cache" or "WP Fastest Cache"), then don't forget to delete the cache before seing the code printed on the website.
2. Make sure the code is in **Published** state (not **Draft** or **in Trash**).
3. Check if the `wp-content/uploads/custom-css-js` folder exists and is writable

* **Does it work with a Multisite Network?**
Yes.

* **What if I change the theme?**
The CSS and JS are independent of the theme and they will persist through a theme change. This is particularly useful if you apply CSS and JS for modifying a plugin's output.

* **Can I use a CSS preprocesor like LESS or Sass?**
No, for the moment only plain CSS is supported.

* **Can I upload images for use with my CSS?**
Yes. You can upload an image to your Media Library, then refer to it by its direct URL from within the CSS stylesheet. For example:
`div#content {
    background-image: url('http://example.com/wp-content/uploads/2015/12/image.jpg');
}`

* **Can I use CSS rules like @import and @font-face?**
Yes.

* **CSS Help.**
If you are just starting with CSS, then here you'll find some resources:
* [codecademy.com - Learn HTML & CSS](https://www.codecademy.com/learn/web)
* [Wordpress.org - Finding Your CSS Styles](https://codex.wordpress.org/Finding_Your_CSS_Styles)

== Installation ==

* From the WP admin panel, click "Plugins" -> "Add new".
* In the browser input box, type "Simple Custom CSS and JS".
* Select the "Simple Custom CSS and JS" plugin and click "Install".
* Activate the plugin.

OR...

* Download the plugin from this page.
* Save the .zip file to a location on your computer.
* Open the WP admin panel, and click "Plugins" -> "Add new".
* Click "upload".. then browse to the .zip file downloaded from this page.
* Click "Install".. and then "Activate plugin".

OR...

* Download the plugin from this page.
* Extract the .zip file to a location on your computer.
* Use either FTP or your hosts cPanel to gain access to your website file directories.
* Browse to the `wp-content/plugins` directory.
* Upload the extracted `custom-css-js-pro` folder to this directory location.
* Open the WP admin panel.. click the "Plugins" page.. and click "Activate" under the newly added "Simple Custom CSS and JS Pro" plugin.

== Frequently Asked Questions ==

= Small incompatibilities =
* If the [qTranslate X](https://wordpress.org/plugins/qtranslate-x/) plugin is adding some `[:]` or `[:en]` characters to your code, then you need to remove the `custom-css-js` post type from the qTranslate settings. Check out [this screenshot](https://www.silkypress.com/wp-content/uploads/2016/08/ccj_qtranslate_compatibility.png) on how to do that.

* The [HTML Editor Syntax Highlighter](https://wordpress.org/plugins/html-editor-syntax-highlighter/) plugin will make the Beautify and Fullscreen editor buttons not work properly.


== Changelog ==

= 4.34 =
* 06/07/2023
* Feature: add the Wikimedia's library for preprocessing the Less code
* Compatibility with the WooCommerce "Custom Order Tables" feature

= 4.33 =
* 05/03/2023
* Fix: custom codes don't show up on frontend if a "network-wide" option is enabled

= 4.32 =
* 03/14/2023
* Fix: build the "custom-css-js-urls" array also after the license key was deactivated
* Fix: PHP8.1 deprecation notices
* Fix: after adding a JS/HTML custom code with empty content will show the CSS default message in the editor

= 4.31 =
* 01/17/2023
* Feature: multiple values for the "Where in site" option
* Fix: show the completion hints also when the editor is in the fullscreen mode
* Fix: the "LH Archived Post Status" plugin was removing the "Publish" button on the add/edit custom code page

= 4.30 =
* 10/12/2022
* Feature: enqueue the jQuery library if one of the JS custom codes requires it
* Feature: code folding in the editor
* Tweak: update the "scssphp/scssphp" library to the latest 1.11.0 version

= 4.29 =
* 06/14/2022
* Fix: if the "File Renaming on Upload" plugin is installed, then don't rename the files with the 'css', 'js', 'html' extensions
* Fix: update CSS linter to allow comma in CSS pseudo-selectors and allow custom properties
* Feature: save the custom code upon "Ctrl-S" in the editor

= 4.28 =
* 03/22/2022
* Tweak: update the EDD Plugin Updater library
* Tweak: add instructions about the "JS Linting Options" to the Help screen
* Fix: check for the scssphp library's version. If another plugin loads an older version of scssphp, where the 'compileString' method is missing, then an error is shown

= 4.27 =
* 02/05/2022
* Tweak: compile SASS code with the "scssphp/scssphp" library for PHP>7.2
* Fix: show the SASS/LESS compiling errors after clicking the "Publish/Update" button
* Fix: allow a query component in the preview page's URL

= 4.26 =
* 11/24/2021
* Fix: escape labels on the "Add new custom code" page
* Fix: the URL matching is now done on the encoded and decoded version of the URL
* Tweak: update the matthiasmullie/Minify library to the latest Jul 2021 commit
* Feature: Keep the last cursor position in the editor and let the editor get focus when the page loads

= 4.25 =
* 06/07/2021
* Fix: linting SASS - allow "!important" rule and tabs as indentation
* Fix: catch and show the throwable errors and exceptions from WP Conditional Tags in the admin
* Tweak: when a SASS partial is saved, the SASS code that imports the partial needs to be compiled to CSS
* Tweak: add the "ccj_code_editor_settings" filter for modifying the editor's options

= 4.24 =
* 03/11/2021
* Fix: allow the TablePress plugin to load its JS files on the "Add custom code" page in admin
* Fix: fatal error with PHP8.0
* Update the JSHint library to to v2.12.0

= 4.23 =
* 02/01/2021
* Feature: add "Allow custom JS codes to the login page in subsites" option on multisite WP installations for the super admin
* Tweak: small adjustments for compatibility with PHP 8.0 and jQuery 3.5.1
* Fix: before loading the Minify class check if it already loaded 

= 4.22 =
* 11/07/2020
* Fix: add SameSite attribute to the theme cookie
* Fix: don't load the theme.css file in the backend
* Fix: the "Apply only on these Pages" rules are case-insensitive, just like the WordPress permalinks

= 4.21.4 =
* 10/02/2020
* Fix: error when filtering the custom codes
* Fix: incompatibility with the Max Mega Menu plugin

= 4.21.3 =
* 08/20/2020
* Fix: remove the "variable-no-property" and "no-ids" rules from SASS linting
* Fix: add "Cmd + " editor shortcuts for MacOS computers
* Fix: the user language preferrence was ignored in favor of the site defined language
* Fix: allow the jQuery library added by plugins like Enable jQuery Migrate Helper or Test jQuery Updates
* Fix: permalink was not editable with WordPress 5.5

= 4.21.2 =
* 07/14/2020
* Fix: use file_get_contents instead of include_once to load the custom codes

= 4.21 =
* 07/08/2020
* Feature: "Ctrl + /" in the editor will comment out the code
* Feature: order custom codes table by "type" and "active" state
* Fix: shortcodes not working on subsites from multisite installations

= 4.20.3 =
* 06/06/2020
* Fix: PHP warning if empty string is used in a "URL starts with ..." rule

= 4.20.2 =
* 05/31/2020
* Fix: compatibility issue with the Product Slider for WooCommerce by ShapedPlugin
* Fix: PHP warning in case the $_SERVER['REQUEST_URI'] variable is missing

= 4.20.1 =
* 05/07/2020
* Fix: HTML code set to "Both" devices doesn't show up on mobile devices
* Check and declare compatibility with WC4.1

= 4.20 =
* 04/24/2020
* Feature: don't show type attribute for script and style tags if the theme adds html5 support for it
* Code refactory
* Fix: the permalink was mistakingly showing a ".css" file extension when being edited

= 4.19 =
* 03/19/2020
* Check and declare compatibility with WC4.0
* Check and declare compatibility with WP5.4

= 4.18 =
* 02/02/2020
* Feature: color the matching brackets in the editor
* Fix: date Published and Modified date wasn't shown in Japanese

= 4.17 =
* 12/19/2019
* Fix: codes limited only to homepage were showing on all the pages
* Feature: editor autocomplete on keyup
* Feature: add "After <body> tag" option for HTML codes, if the theme allows it 

= 4.16.1 =
* 11/05/2019
* Declare compatibility with WP5.3 and WC3.8

= 4.16 =
* 10/23/2019
* Fix: preview wasn't working under certain conditions

= 4.15 = 
* 10/02/2019
* Feature: Linting for SASS code
* Feature: permalink slug for custom codes

= 4.14 =
* 09/08/2019
* Compatibility with the "CMSMasters Content Composer" plugin
* Option: remove the comments from the HTML

= 4.13 =
* 05/08/2019
* Fix: remove the CodeMirror library added from the WP Core
* Tweak: use protocol relative urls for custom code linked file
* Declare compatibility with WordPress 5.2

= 4.12 =
* 04/21/2019
* Tweak: rename "First Page" to "Homepage" to avoid misunderstandings 
* Add CCJ_WP_CONDITIONALS constant that turns off the WP Conditional Tags from being executed
* Fix: update the Bootstrap library used in the admin side to 3.4.1 version

= 4.11 =
*  03/09/2019
* Fix: avoid conflicts with other plugins that use CodeMirror as their editor

= 4.10 =
* 12/07/2018
* Fix: the Edit Custom Code page was blank for WordPress 5.0 and the Classic Editor enabled

= 4.9 =
* 09/10/2018
* 10/09/2018
* Feature: add the add/edit/delete custom post capabilities to the admin and 'css_js_designer' roles on plugin activation
* Fix: add to the admin the capabilities of the custom-css-js custom post

= 4.8 =
* 09/04/2018
* Fix: rebuild the custom-css-js-tree when activating the pro version, otherwise some HTML codes don't show up in the frontend
* Fix: keep the editor LTR even on RTL websites
* Fix: catch the syntax errors from WP Conditional Tags
* Fix: on multi-site installations any HTML code from the subsite was replaced with one from the main site 

= 4.7 =
* 07/13/2018
* New: allow importing one custom code from another for Less/SASS CSS codes
* Fix: PHP warning at "Apply only on these Pages" if the JSON is not properly formatted
* Fix: the default comment for JS for other locales than "en_" was removing the <scripts> tags
* Tweak: add some missing translations
* Tweak: make the search dialog persistent
* Tweak: use default codemirror.js scrollbar style instead of "simple" in order to avoid conflicts with other plugins
* New: allow shortcodes in a custom HTML code

= 4.6 =
* 05/15/2018
* Tweak: hide the license key on the Settings page
* Tweak: design the custom codes table for screens smaller than 786px
* Fix: check all the functions if exists before declaring them
* Fix: the time accounts also for the timezone

= 4.5 =
* 04/15/2018
* Fix: PHP warning when displaying time for PHP 7.2.x
* Tweak: use the CSS and JS minifier from Matthias Mullie 
* Fix: remove ";" character from the "WP Conditional Tags"
* Tweak: compatibility with WP Quads Pro plugin

= 4.4 =
* 03/28/2018
* Fix: the codes table was squeezed to 70% of the screen width
* Fix: it was impossible to turn off the "Apply network wide" option 
* Change: check the option name against an array of allowed values
* Tweak: tinyMCE menu, add "No shortcodes defined" message
* Tweak: show the network defined shortcodes in the tinyMCE menu
* Tweak: show an "Network wide" icon in the table of custom codes

= 4.3 =
* 03/10/2018
* Fix: deleting a rule from "Apply on these pages" was mistakenly deleting two rule
* Fix: date format on the Custom Codes table list
* Tweak: redesign the Settings page
* Tweak: show more information about the license

= 4.2 =
* 02/20/2018
* Fix: error undefined ccj_editor_theme
* Tweak: show notice to uninstall the free version

= 4.1 =
* 02/16/2018
* Fix: use the `login_init` hook for the login page

= 4.0 =
* 02/11/2018
* Feature: Network-wide custom codes
* Option "Use HTTPS for the main site" for MultiSite installations
* Fix: change syntax highlighting for Less and Sass
* Tweak: for post.php and post-new.php page show code's title in the page title
* Fix: allow admin stylesheets from ACF plugin, otherwise it breaks the post.php page
* Feature: lint on editor

= 3.15 =
* 01/23/2018
* Tweak: "Apply only on these Pages" accepts also absolute URLs
* Tweak: add Editor Shortcuts to the Help area
* Feature: auto close brackets for the editor
* Feature: autocomplete on Ctrl + Space

= 3.14 =
* 01/15/2018
* Feature: add the "Encode the HTML tags" option

= 3.13 =
* 01/12/2018
* Feature: add the "Keep the HTML entities, don't convert to its character" option

= 3.12 =
* 01/08/2018
* Fix: https://wordpress.org/support/topic/footer-code-position-before-external-scripts-is-overridden/
* Fix: https://wordpress.org/support/topic/annoying-bug-in-text-editor/

= 3.11 =
* 03/01/2018
* 01/03/2018
* Feature: add filter by code type 
* Feature: make the 'Modified' column sortable
* Fix: https://wordpress.org/support/topic/broken-layout-of-code-snippet-type-color-tag-css-html-js-on-main-list-table/
* Fix: if the default comment remains in the "Add Custom JS", then there was no 'script' tags added to the code, as the comment contained a 'script' tag

= 3.10 =
* 11/14/2017
* Fix: change the ids of the loaded assets in admin in order to avoid conflicts
* Fix: remove the iframe footer hook in order to avoid conflict with the `HTML Editor Syntax Highlighter` plugin
* Fix: for revisions set the modal with `top` instead of `margin-top`

= 3.9 =
* 10/19/2017
* Declare compatibility with WooCommerce 3.2 (https://woocommerce.wordpress.com/2017/08/28/new-version-check-in-woocommerce-3-2/)
* Fix: avoid conflicts with other plugins that implement the CodeMirror editor
* Update the CodeMirror library to the 5.28 version

= 3.8 =
* 09/29/2017
* Fix: "Apply only on these Pages: First Page" needs to use home_url() instead of get_option('home') for multi-site installations
* Fix: allow the "Please activate the license" meta box

= 3.7 =
* 09/07/2017
* Fix: compatibility with the CSS Plus plugin

= 3.6 =
* 08/06/2017
* Fix: show the preview also when there are no other codes defined
* Tweak: comment about linking an external JS

= 3.5 =
* 07/27/2017
* Feature: prepare the plugin for translation
* Tweak: show date according to the get_option('date_format')
* Fix: the Custom Codes table is responsive for narrower screens
* Fix: initialize the codes with `wp` on frontend and `init` on backend.

= 3.4 =
* 07/15/2017
* Fix: rename the EDD_SL_Plugin_Updater class to avoid conflicts with other plugins that update with this class
* Security fix according to VN: JVN#31459091 / TN: JPCERT#91837758
* Add activate/deactivate link to row actions and in Publish box
* Make the activate/deactivate links work with AJAX
* Feature: option for adding Codes to the Login Page

= 3.3 =
* 06/13/2017
* Fix: compatibility issue with the HTML Editor Syntax Highlighter plugin
* Fix: remove htmlentities in the editor

= 3.2 =
* 04/04/2017
* Fix: allow codes in the backend

= 3.1 =
* 12/22/2016
* Feature: use WP Conditional Tags to restrict code
* Feature: wrap the lines in the editor
* Feature: Beautify Code editor button
* Feature: Fullscreen editor button
* Fix: cURL error because the activation license was done on silkypress.com:80

= 3.0 =
* 11/07/2016
* Feature: shortcodes functionality for the HTML codes
* Check: compatibility with WordPress 4.7

= 2.9 =
* 11/03/2016
* Fix: when Toolset Types plugin was enabled, the editor lost the colors

= 2.8 =
* 10/16/2016
* Fix: add stripslashes before preprocessors or minifiers, not after
* Fix: on activation along the free version there was a warning because both plugins used the same CCJ_VERSION constant

= 2.7 =
* 10/14/2016
* Feature: keep the cursor position after saving
* Fix: for empty allowed_codes() the search_tree() was still printed

= 2.6 =
* 08/24/2016
* Feature: add HTML code
* Feature: choose priority for the codes

= 2.5 =
* 08/03/2016
* Feature: Search functionality for the editor
* Feature: add "Add CSS Code" and "Add JS Code" buttons in order to be consequent with the WP admin interface
* Fix: make the editor wrapper adapt as width to the editor's line numbers gutter
* Fix: adapt the editor wrapper theme to the editor's theme
* Fix: incompatibility with other plugins that load CodeMirror (https://wordpress.org/support/topic/almost-everything-disabled)
* Fix: show warning when qTranslate is activated (https://wordpress.org/support/topic/conflict-with-qtranslate-x-2)
* Compatibility with WP 4.6: the "Modified" column in the Codes listing was empty

= 2.4 =
* 07/01/2016
* Fix: PHP warning for no codes in the $allowed_codes array
* Feature: change the editor's scrollbar so it can be caught with the mouse

= 2.3 =
* 06/24/2016
* Feature: "Debug Info" tab to the settings in order to help debugging
* Fix: when the Allowed Codes is empty, the Search Tree was not filtered

= 2.2 =
* 06/12/2016
* Initial commit

== Upgrade Notice ==

Nothing at the moment
