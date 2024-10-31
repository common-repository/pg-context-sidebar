 === PG Context Sidebar ===
Contributors: peoplesgeek
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=BQNMJDVQXFSG2
Tags: post sidebar, page sidebar, context sidebar, custom sidebars, content aware sidebar, content sidebar
Requires at least: 3.3
Tested up to: 5.2.1
Stable tag: 2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Show different content in the sidebar for each page or post - great for emphasising related offers, ideas, or quotes

== Description ==
Use this widget to show content in any sidebar that is related to the current page or post. Have a targeted message or call too action that relates to this page.
 
* If the page is advertising a product put details of special deals.
* If post is a tutorial then you could put details of prerequisites.
* Add a quote relevant to your article that displays in the sidebar.

Sometimes you want to display a promotion in the sidebar when you display a particular page, or highlight a quote or reference that is particularly relevant - but only to that page.

Simply enter the extra content on your page when you create or edit it. Then place the widget into your sidebar where you want it to display.

If you don't have content to display for a particular page then the sidebar widget is not shown.

= Features =
* Add extra content to any custom post type - select the types where you want to be able to enter information.
* Use html in your content for emphasis, images and links.
* Easily copy the content of one context sensitive sidebar page to another
* Use one context sensitive sidebar page as a template for others so that changes are instantly reflected everywhere it is used.
* Easily identify pages that have context sensitive sidebar information from the 'all pages/posts' view

Localisation: if you can provide a translation for the administration text then please get in touch and I will add a translation for the language you provide.


== Installation ==

1. Upload all the files into your plugins directory
1. Activate the plugin at the plugin administration page
1. On each page or post that you want to display context related information enter this into the Context Sidebar meta-box
1. Add the PG Context Sidebar widget to the sidebar in your theme
1. Go to settings and select the page types that you want to use with Context Sidebar

== Frequently Asked Questions ==

= What if I don't have sidebar content for every page =
The sidebar is only shown if there is content to be displayed. If you have no content for the sidebar on a particular page then leave the fields empty

= How do I hide the meta-box for some post types =
The Context Sensitive Sidebar meta box will show in all post types by default. If you want to hide the meta-box for a particular post type then go to the admin page located under Settings and un-tic that post type.

== Screenshots ==

1. Shows new fields for your sidebar content on the post page
2. Shows the resulting content in the sidebar for the page
3. Shows the ability to select the page or post types that allow context sensitive content

== Changelog ==

= Version 2.1 =
* Updated Widget constructor to newer PHP version to prevent depreciation warnings in debug log
* Tested with WordPress 4.7.5
* Bumped and tested with WordPress 5.2.1

= Version 2.0.1 =
* Tested compatibility with WordPress 3.8.1
* Added logic to stop the sidebar icon showing in post types where it had not been selected.
* Added the ability to exclude the copy functionality from some post types (was failing for some blogs with a large number of posts)

= Version 2.0.0 =
* Added the ability to copy from another page/post
* Added the ability to use a page/post as a template that automatically updates another page/post
* Allow an icon to be shown on the 'all pages' / 'all posts' pages so it is easy to see which ones have content entered
* Added support for admin language translations (Localization)

= Version 1.0.1 =
* Fixed filter on 'widget_text' for content and allow line breaks and html in the content

= Version 1.0.0 =
* Initial Release

== Upgrade Notice ==

= 1.0.1 =
Upgrade to be able to add html markup in the content of the sidebar display

= 1.0.0 =
This initial version gets you started and simplifies adding custom content to a sidebar
