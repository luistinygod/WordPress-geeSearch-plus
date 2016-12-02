WordPress-geeSearch-plus
======================

**gee Search Plus** extends the WordPress search engine without messing with the database, sorts results by relevance (or date), and more.

## New Features (since 1.4)
* Extend search to Media

## New Features (since 1.3.0)
* Order results by Relevance or by Date
* Define matching rules: at least one search term (OR query) or require all terms (AND query)
* Define your own highlight style (CSS class: gee-search-highlight)
* i18n ready

## Features

* Searches through all the posts, pages and custom post types, by title and content (AND query)
* Extends search to all custom taxonomies, category and tags (AND query)
* Extends search to custom fields (AND query)
* Sorts results by relevance
* Highlights searched terms (optional)
* Removes stopwords from search query
* Integrates the stopwords mechanism with Stella multi-language plugin, by removing the current language stopwords from the search query

Very easy to use and setup! No new database tables or complex configurations. gSearch Plus uses only WordPress' APIs and functions! Simple and Clean!

## Usage

1. Upload the `gsearch-plus` folder to the `/wp-content/plugins/` directory
2. Activate the **gSearch Plus** plugin through the 'Plugins' menu in WordPress
3. Configure the plugin by going to the **gSearch Plus** menu that appears in your *Settings* menu

That’s all! We hope that you like our plugin. Suggestions, questions and other feedback are welcome. [twitter: @luistinygod](http://twitter.com/luistinygod)

## License

The WordPress gSearch Plus is licensed under the GPL v2 or later.

> This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

> This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

> You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

## Changelog

### 1.4.4
* [GOMO](http://gomo.pt/) replaces geeThemes brand.
* Tested against WP version 4.6.1

### 1.4.2
* Fixed: Convert specific stopwords to lowercase on save
* Fixed: Issue when searching on Tags (post_tag taxonomy) - conflict with plugins that inject specific post_types on query_vars without checking if 'any' is already selected.

### 1.4.1
* Tested up to WP 4.0
* Fix division by zero warning

### 1.4.0
* Extend search to media

### 1.3.1
* fix pagination on search results

### 1.3.0
* Full review for WP 3.7
* Order by relevance or by date
* Allow OR and AND query type
* Highlight class instead of inline styles
* New plugin hooks for better control
* Merge stopwords mechanism with WordPress new native stopwords mechanism
* Prepared for i18n

### 1.2.0
* Re-named from gSearch to geeSearch ( affected functions, hooks and classes )
* New relevance engine
* Corrected wp_title bug

### 1.1.8
* Load frontend script on footer
* Default highlight area div#content

### 1.1.7
* Admin: Replaced color picker - using WP default
* Admin: New setting 'Highlight allowed areas' uses valid jQuery selectors
* jQuery scripts enqueued differently on frontend thus solving Jetpack conflicts

### 1.1.6
* Revision and testing for WP 3.6

### 1.1.5
* Optimized queries to reduce needed memory

### 1.1.4
* Less strict when searching custom fields (LIKE compare)

### 1.1.3
* Corrects issue regarding spaces in the middle of searched terms

### 1.1.2
* Corrects issue regarding spaces on beginning and end of the search sentence.

### 1.1.1
* Minor corrections on jshighlight script

### 1.1.0
* New features: custom fields search & highlight searched terms.
* Includes two new js: jscolor plugin and jshighlight

### 1.0.0
* Initial release.
* No multisite compatibility. More to come shortly.

## Author Information

The WordPress gSearch Plus plugin was originally started and is maintained by [Luis Godinho](https://twitter.com/luistinygod) and it's part of [GOMO](http://www.gomo.pt/) portfolio.

The project is open-source and receives contributions from awesome WordPress Developers throughout the world.
