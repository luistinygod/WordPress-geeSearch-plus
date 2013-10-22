=== geeSearch Plus, improved WordPress search ===
Contributors: luistinygod, geethemes
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=paypal%40geethemes%2ecom&item_name=geeSearch%20Plus%20plugin&no_shipping=1&cn=Donation%20Notes&tax=0&currency_code=EUR&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: search, relevance, improve search, custom post types search, custom taxonomies search, custom fields, stopwords, stella multi-language, highlight search terms
Requires at least: 3.5
Tested up to: 3.7
Stable tag: 1.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Improves the WordPress search engine without messing with the database, sorts results by relevance or date, and more. Simple and clean!

== Description ==

geeSearch extends the WordPress search engine without messing with the database, sorts results by relevance (or date), and more. Simple and clean!

By default WordPress searches all terms through all the posts' titles & contents and orders the results by date (up to 3.6.1) or by relevance (from 3.7). 

= Tired of complex plugins to improve the default WordPress search? =
geeSearch Plus plugin improves WordPress' default mechanism by searching through custom taxonomies (including category and tags) and custom fields.  It orders the results by date or relevance, calculating the search words hits through the title and content, taxonomies and custom fields of each post. 
This plugin also includes a stopwords mechanism to remove the non-relevant words from the query, thus increasing the relevance of the results. Stopwords can be defined manually (using the settings page) or by using the default files provided with the plugin package.

= New Features (since 1.3.0) =
* Order results by Relevance or by Date
* Define matching rules: at least one search term (OR query) or require all terms (AND query)
* Define your own highlight style (CSS class: gee-search-highlight)
* i18n ready


= Features =
* Searches through all the posts, pages and custom post types, by title and content
* Extends search to all custom taxonomies, category and tags
* Extends search to custom fields
* Highlights searched terms (optional)
* Limit highlight feature to specific html elements
* Removes stopwords from search query (merged with native WordPress stopwords mechanism)
* Integrates the stopwords mechanism with Stella multi-language plugin, by removing the current language stopwords from the search query

Very easy to use and setup! No new database tables or complex configurations. geeSearch Plus uses only WordPress' APIs and functions! Simple and Clean!

= Available translations =
* Portuguese


= Notes =
* This release is compatible with multisite single blog installation
* This release is compatible with all WordPress versions since 3.5. If you are still using an older one, upgrade your WordPress **NOW!**


== Installation ==

1. Upload the `gsearch-plus` folder to the `/wp-content/plugins/` directory
1. Activate the **geeSearch Plus** plugin through the 'Plugins' menu in WordPress
1. Configure the plugin by going to the **geeSearch Plus** submenu that appears under your *Settings* menu

That’s all! We hope that you like our plugin. Suggestions, questions and other feedback are welcome. [twitter: @luistinygod](http://twitter.com/luistinygod)

== Frequently Asked Questions ==

If your question isn't listed here, please open a new topic at the [Support tab](http://wordpress.org/support/plugin/gsearch-plus "geeSearch Plus support").

= I would like to have feature XYZ. What should I do? =
Let us know if you'd like to have a special feature implemented in this plugin. Please open a new topic in the plugin [Support tab](http://wordpress.org/support/plugin/gsearch-plus "geeSearch Plus support").

= How do I install it in certain page using it as a widget? =
geeSearch Plus plugin works behind the scenes by optimizing and changing the default WordPress search query. If you would like to have a search box on a sidebar or as a widget, just use the WordPress default search widget and geeSearch plus will do the hard work! 

= What do I need to know if I'm using Stella multi-language plugin? =
If you'd like to remove the correct language stopwords from your search query when using the Stella plugin, then go to **geeSearch Plus** settings and change the *Remove Stopwords by language* to the option *Use stopwords files according to Stella languages*.
In order get this working properly you need to make sure there is a stopwords file per each configured language on your site. Check at the `/wp-content/plugins/gsearch-plus/stop` directory for the files. You'll find there are already several files pre-loaded with the geeSearch Plus plugin (English, Spanish, Portuguese, Italian, Czech, German, Finnish, French, Polish, Dutch and more). You may edit those files to include/remove stopwords, or add new files. When adding new stopwords files name them as *stopwords-[LANGUAGE].php*, where LANGUAGE is a two letter code representing the Stella language (en -> English, de -> German, and so on).


== Screenshots ==

1. Plugin settings page




== Changelog ==
= 1.3.0 =
* Full review for WP 3.7
* Order by relevance or by date
* Allow OR and AND query type
* Highlight class instead of inline styles
* New plugin hooks for better control
* Merge stopwords mechanism with WordPress new native stopwords mechanism
* Prepared for i18n 


= 1.2.0 =
* Re-named from gSearch to geeSearch ( affected functions, hooks and classes )
* New relevance engine
* Corrected wp_title bug

= 1.1.8 =
* Load frontend script on footer
* Default highlight area div#content

= 1.1.7 =
* Admin: Replaced color picker - using WP default
* Admin: New setting 'Highlight allowed areas' uses valid jQuery selectors
* jQuery scripts enqueued differently on frontend thus solving Jetpack conflicts

= 1.1.6 =
* Revision and testing for WP 3.6

= 1.1.5 =
* Optimized queries to reduce needed memory

= 1.1.4 =
* Less strict when searching custom fields (LIKE compare)

= 1.1.3 =
* Corrects issue regarding spaces in the middle of searched terms

= 1.1.2 =
* Corrects issue regarding spaces on beginning and end of the search sentence.

= 1.1.1 =
* Minor corrections on jshighlight script

= 1.1.0 =
* New features: custom fields search & highlight searched terms. 
* Includes two new js: jscolor plugin and jshighlight

= 1.0.0 =
* Initial release. 
* No multisite compatibility. More to come shortly.

== Upgrade Notice ==

Nothing to declare
