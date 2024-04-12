=== Cooked - Recipe Management ===
Contributors: boxystudio, xjsv
Tags: recipe, recipes, food, cooking, nutrition
Requires at least: 4.7
Tested up to: 6.5.2
Stable tag: 1.7.15.2
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cooked is the absolute best way to create & display recipes with WordPress. SEO optimized, galleries, timers, and much more.

== Description ==

Cooked is the absolute best way to create & display recipes with WordPress. SEO optimized (rich snippets), galleries, cooking timers, printable recipes and much more. Check out the full list below.

Be sure to check out the **[online demo](https://demos.boxystudio.com/cooked/)** as well as the **[Cooked Documentation](http://docs.cooked.pro/)** if you need some help!

= Quality design & usability =

Using the drag & drop recipe builder, you can create your recipes quickly and without limitations. Add ingredients, directions—and then add a gallery, nutrition facts, cooking times and much more.

= Google-friendly =

Cooked automatically includes semantic structure and schema.org microdata into each and every recipe you publish. This allows Google to display your recipes across a variety of device sizes and platforms.

= Many premium features already included =

Most recipe plugins require that you purchase a PRO version for features like nutrition facts, galleries, powerful searching, timers, etc. The standard version of Cooked includes all of these. Here's what you get out of the box:

* Drag & drop ingredients and directions.
* SEO Optimized - Google Structured Data and Schema.org support.
* Beautiful grid-based masonry recipe lists.
* Prep & Cooking Times
* Photo Galleries
* Nutrition Facts
* Difficulty Levels
* Powerful recipe search with a text search, categories & sorting options.
* Author template to list recipes by a single author.
* Cooking times with clickable, interactive timers.
* Very developer-friendly with loads of hooks & filters.
* Servings switcher to adjust ingredient amounts.
* And more to come...

Of course, if you want even more, you can always check out the [PRO](https://cooked.pro) version of Cooked. It adds features like ratings & favorites, recipe submissions, and so much more.

= Developers love it =

Cooked has a whole bunch of actions and filters to customize Cooked as much as you need to. Be sure to check out the [Developer Documentation](http://docs.cooked.pro/collection/31-developers).

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/cooked` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Recipes > Settings screen to configure the plugin.
4. Go to Recipes > Add New to start adding your recipes!
5. Head over to the [Cooked Documentation](http://docs.cooked.pro/collection/1-cooked) for more help.

== Frequently Asked Questions ==

= Having issues with the plugin? =

Be sure to check the [Cooked Documentation](http://docs.cooked.pro/collection/1-cooked) for guides and documentation. If you're still having issues, create a new support topic and let me know what's going on. I'm happy to help! Please don't post a bad review without discussing here first, I really appreciate it!

== Screenshots ==

1. Recipe Display
2. Adding Ingredients
3. Adding Directions
4. Recipe Template
5. Nutrition Facts
6. Cooking Timers
7. Gallery Builder
8. Recipe Shortcodes

== Upgrade Notice ==

Version 1.7.15.2 is a hotfix to correct the composer platform error issue.

== Changelog ==

= 1.7.15.2 =
* **FIX:** Composer detected issues in your platform error discovered by @ianrlp.
* **FIX:** PHP undefined variable $hours_left discovered and fixed by @addyh.
* **TWEAK:** Security improvements thanks to @addyh.

= 1.7.15.1 =
* **FIX:** Addressed the CVE-2023-44477 security vulnerability.
* **FIX:** Added html lang attribute to html tag in print view.
* **FIX:** Added alt text to gallery images.

= 1.7.13 =
* **FIX:** Fixes an XSS vulnerability within the recipe search box.

= 1.7.12 =
* **FIX:** Fixes an issue with the Servings Switcher not being displayed correctly.

= 1.7.11 =
* **FIX:** Fixes an issue with the Servings Switcher not being displayed correctly.

= 1.7.10 =
* **FIX:** Fixes multiple vulnerabilities throughout the plugin. Updating is highly recommended!

= 1.7.9.1 =
* **FIX:** Fixes an XSS vulnerability with the Serving Size picker.

= 1.7.9 =
** **NEW:** Added an `inline_browse` option to the `[cooked-browse]` and `[cooked-search]` shortcodes. Ex. `[cooked-browse inline_browse="true"]` This will show the taxonomies inline and not require the "Browse" button being clicked to view them.

= 1.7.8.5 =
* **FIX:** Fixed an issue with large spaces between the recipe template shortcodes
* **FIX:** Fixed an issue with Vimeo videos in the gallery

= 1.7.8.4 =
* **FIX:** Removed "Section Headings" from recipe schema output.

= 1.7.8.3 =
* **FIX:** Another patch release for taxonomy title bugs (sorry!).
* **FIX:** This particular patch prevents the navigation items from getting renamed.

= 1.7.8.2 =
* **FIX:** Another patch release for taxonomy titles.

= 1.7.8.1 =
* **FIX:** Patch release for some quick taxonomy title fixes.

= 1.7.8 =
* **FIX:** Page titles are now updated on taxonomy pages (categories) when Browse Recipes page is used.
* **FIX:** An additional quick PHP 7.4 fix.

= 1.7.7 =
* **FIX:** Support for PHP 7.4 and WordPress 5.5.

= 1.7.6 =
* **NEW:** An optional recipe field has been added for "SEO Description". In the Recipe Schema output, this field will take precedence over the "Excerpt" field.
* **NEW:** Added a new "Advanced" option to disable the recipe schema output that Cooked generates.
* **FIX:** Fixed the 404 errors on Fotorama PNGs.
* **FIX:** Fixed the issues with videos not playing in the Cooked Gallery.
* **FIX:** If Servings are set to "1", you can now Half and Quarter them in the Servings Switcher.
* **TWEAK:** Widgets with images now load thumbnail sizes instead of the larger ones.

= 1.7.5.2 =
* **TWEAK:** Adds support for Cooked Pro 1.7.

= 1.7.4 =
* **TWEAK:** Moved Fotorama assets into plugin instead of relying on a CDN connection.
* **TWEAK:** Removed "imagesLoaded" script (no longer needed).
* **TWEAK:** Added new filter to single ingredient output (cooked_single_ingredient_html)

= 1.7.3 =
* **FIX:** A minor fix (thank you to @zorkman777)

= 1.7.2 =
* **FIX:** Adds support for Cooked Pro v1.6 (redirect fixes)

= 1.7.1 =
* **TWEAK:** WordPress 5.2 support
* **FIX:** Fixed an issue with the recipe list style changing back to default when using the "Load More on Scroll" and "Load More Button" pagination types.

= 1.7 =
* **NEW:** Design tweaks throughout.
* **NEW:** Removed masonry javascript and let the recipe grid line up automatically with CSS.
* **NEW:** Browse dropdown now includes both parent and child taxonomies. Long lists get a scrollable area.
* **NEW:** Added an "exclude" property to the `[cooked-browse]` shortcode. You can now exclude specific recipes by their ID. [Learn More](https://demos.boxystudio.com/cooked/)
* **NEW:** Added a "ding" sound to the end of timers. Use the "cooked_timer_sound_mp3" filter to change the MP3 file to anything you'd like (needs to be a publically available URL). [Learn More](https://demos.boxystudio.com/cooked/)
* **FIX:** Fixed some missing ingredient fractions.
* **FIX:** Fixed some minor PHP warnings.

= 1.6.4 =
* **NEW:** Added a widget to display a list of Recipe Categories.

= 1.6.3 =
* **FIX:** Minor fixes for recipe "Browse Page" breadcrumbs.
* **TWEAK:** Added new icon for BigOven save button (Pro feature).

= 1.6.2 =
* **FIX:** Added support for custom permalinks with slashes (i.e. "our-food/recipes").

= 1.6.1 =
* **FIX:** Fixed a bug with the search form when multiple searh forms are on one page.

= 1.6 =
* **NEW:** WordPress 5.0 support.
* **NEW:** Added option under "General" to show Carbs as "Total Carbs" or "Net Carbs".

= 1.5.5 =
* **FIX:** Recipe excerpt now allows for basic, safe HTML (links, bold, italic, etc.).
* **FIX:** Added actions to the recipe card shortcode so ratings/favorites will show up.

= 1.5.4 =
* **FIX:** Some minor "difficulty level" label fixes.
* **FIX:** Nutrition Facts now accepts "0" as an amount.
* **FIX:** Fixed the annoying "Settings Page Disappearing" issue!

= 1.5.3 =
* **NEW:** Elementor support. Now you can create your recipe templates with Elementor!

= 1.5.2 =
* **NEW:** Added a new "cooked_show_difficulty_level" filter so you can show whatever you want.
* **FIX:** Fixed an issue with the "Save as Default" and "Apply to All" feature.
* **FIX:** Added a fix to prevent Gutenberg from breaking the recipe edit screen.
* **FIX:** Fixed an unintentional redirect issue with the recipe RSS feed.

= 1.5.1 =
* **FIX:** Fixed a JavaScript error with fullscreen mode.
* **FIX:** Swapped the mismatched "servings" and "serving size" for recipe schema data.
* **FIX:** Fixed a filter issue that was preventing custom taxonomy queries in the `[cooked-browse]` shortcode.
* **TWEAK:** Welcome screen style adjustments.
* **TWEAK:** Dropped support for PHP 5.6 (still works for now, just not testing with it anymore).

= 1.5 =
* **NEW:** New `[cooked-title]` shortcode to display the recipe title (for recipe template).
* **NEW:** Added option to hide the Author images (avatars) throughout the site.
* **NEW:** Added option to disable the Author link (linking to the author page).
* **NEW:** Moved "Total Time" into its own field. Will show "Prep Time + Cook Time" by default.
* **FIX:** Fixed issues with editing recipe ingredients, directions, etc. on iPads (and other tablets).
* **FIX:** Fixed and issue with times not showing up correctly if over 1,440 minutes.
* **FIX:** Fixed some issues with Recipe Schema output.

= 1.4.2 =
* **NEW:** Improved support for Yoast SEO (and other SEO plugins).
* **NEW:** French translation added.
* **FIX:** Fixed an issue with some incorrect Percent Daily Values on the front-end.
* **FIX:** Fixed a padding issue on the recipe grid (on smaller screens).
* **FIX:** Bug fixes for the WooCommerce Memberships' "Restrict Content" feature.

= 1.4.1 =
* **NEW:** Added support for WooCommerce Memberships' "Restrict Content" feature.
* **FIX:** Fixed some issues with the [cooked-browse] shortcode.

= 1.4.0.3 =
* **FIX:** Fixed an issue with pagination on recipe taxonomy templates.

= 1.4.0.2 =
* **NEW:** When viewing a parent category, it will now display sub-category items instead of recipes. This allows you to nicely nest your categories if desired.
* **TWEAK:** Added some adjustments for "Dark Mode".
* **TWEAK:** Added some adjustments to fix a few TwentySeventeen CSS conflicts.

= 1.4.0.1 =
* **FIX:** Fixed a major layout issue, sorry about that everyone!

= 1.4 =
* **NEW:** Added a "Dark Mode" setting for sites with dark backgrounds.
* **NEW:** Added a `[cooked-recipe-categories]` shortcode to display all categories in a beautiful, visual grid.
* **NEW:** The recipe list style now uses the same design as Recipe Cards.
* **TWEAK:** Removed "Garnish" as an option. This was just confusing to most users who saw it.
* **FIX:** Fixed some styling issues with the search bar.

= 1.3.05 =
* **TWEAK:** Minor adjustments to support the new Cooked Pro 1.1.

= 1.3.04 =
* **FIX:** Fixed some issues with the Settings page on some servers.
* **TWEAK:** Now loading the dynamic CSS files as inline to fix caching/loading issues.
* **TWEAK:** Tweaked the migration feature to support MUCH larger recipe collections.

= 1.3.03 =
* **FIX:** Fixes conflicts with the Cooked Pro plugin.
* **TWEAK:** Updated the language template file.

= 1.3.02 =
* **FIX:** Fixes a PHP error occuring on a large number of servers.

= 1.3.01 =
* **FIX:** Fixed a few issues with the new shortcodes displaying strangely.

= 1.3.0 =
* **NEW:** **"Cooked - Recipe List" Widget** — Display a list of recipes.
* **NEW:** **"Cooked - Recipe Card" Widget** — Display a fancy recipe card.
* **NEW:** `[cooked-recipe-list]` — Display a list of recipes.
* **NEW:** `[cooked-recipe-card]` — Display a fancy recipe card.
* **NEW:** Added "Nutrition" to the print options.
* **NEW:** Added a complete migration solution to update recipes from Cooked Classic.
* **TWEAK:** Added a progress bar to the "Apply to All" recipe template updater.
* **TWEAK:** Added an option to disable the "Servings Switcher".
* **TWEAK:** Full-screen mode has been refreshed a little bit. Mostly in the fact that the tabs are now at the top to avoid conflicts with the iPhone X.
* **FIX:** Fixed issues with slow loading times on recipe list pages.
* **FIX:** Fixed issues with the "Apply to All" template update feature.
* **FIX:** Fixed issues with the Default Template saving/loading buttons.
* **FIX:** Fixed an issue where "Authors" could not edit recipes.
* **FIX:** Fixed an issue with WPML not being able to translate recipe information.

= 1.2.0 =
* **NEW:** **"Cooked - Recipe Search" Widget** — Display the recipe search form.
* **NEW:** `[cooked-search]` — Display the recipe search form.
* **NEW:** Added REST API support to recipes and recipe categories.
* **TWEAK:** Added the same "search" shortcode options to `[cooked-browse]` so you can customize the recipe search bar from that shortcode as well. See the documentation for more shortcode options.
* **TWEAK:** Added some hooks and filters to the welcome screen to add the ability to include the Cooked Pro changelog information there as well.
* **TWEAK:** Direction images are formatted much better now (inline with the text and some margin below).
* **TWEAK:** Added an option to disable the "Servings Switcher".
* **TWEAK:** Converted all CSS "em" values to "rem" values.
* **FIX:** Fixed a bug where posts were being duplicated when embedding "draft" recipes using the shortcode.
* **FIX:** Disabling Public Recipes will now work as intended. Recipes will be hidden from search results, recipe URLs redirected to the homepage, etc.
* **FIX:** Added some missing language strings.

= 1.1.13 =
* **NEW:** Added kg (kilograms) as a measurement option.
* **FIX:** Fixed an issue where zeros were being removed from large numbers.
* **FIX:** Recipes will now 404 if "Disable Public Recipes" is active.
* **FIX:** Minor CSS adjustments throughout.

= 1.1.12 =
* Adjusted some code to support the upcoming Cooked Pro features.
* Some minor text changes in the Settings panel.

= 1.1.11 =
* **FIX:** Fixed an issue with ingredient amounts getting rounded up to 1.
* **FIX:** Fixed some theme compatibiltiy issues.
* **FIX:** Re-enabled structured data for recipes. Didn't mean to disable this, sorry!

= 1.1.10 =
* **NEW:** Ingredient amounts will now display as entered (fractions or decimals) in the number format based on your language settings.
* **NEW:** Added taxonomy filter dropdowns to the admin recipe list page.
* **NEW:** Added developer filters for customizing the "Percent Daily Value" calculations.
* **FIX:** Added compatibility for the "Bridge" theme.

= 1.1.9 =
* **FIX:** Added "1/5" support to measurements.
* **FIX:** Other minor bug fixes throughout.
* **FIX:** Fixed an edge-case issue where private Vimeo videos would not show up within recipe content.

= 1.1.8 =
* **NEW:** HTML is allowed in all ingredient/direction fields.
* **FIX:** Fixed some redirect issues.
* **FIX:** Some adjustments to support the upcoming Cooked Pro.

= 1.1.7 =
* **FIX:** Fixed an issue with the Cooked settings screens if a non-English language is enabled.
* **FIX:** Fixed an issue for when the "Browse Recipe Page" and "Single Recipe Post" slugs were the same (i.e. /recipes/). You can now use the same slug for both!

= 1.1.6 =
* **NEW:** Tested and working in WordPress 4.8!
* **NEW:** Custom checkbox toggles on the Settings page.
* **FIX:** Fixed an issue with category redirects. There was a double slash being added that has now been resolved. Huge thanks to **@travelnlass** and **@kitcatsz** for finding this one!

= 1.1.5 =
* **FIX:** A lot more fixes for the [cooked-recipe] shortcode. Huge thanks to Zoe and Mariana for donating their time and websites to help me work out these issues!
* **NEW:** Added an advanced ability to "Disable Cooked `<meta>` Tags" when needed.
* **NEW:** Added an advanced ability to "Disable Public Recipes" when needed.

= 1.1.4 =
* **FIX:** Several fixes for the `[cooked-recipe]` shortcode.
* **FIX:** Fixed some issue with printing recipes.
* **FIX:** Applied selected servings to print view.

= 1.1.3 =
* **FIX:** Fixed an issue with using decimals on Nutrition Facts.

= 1.1.2 =
* **FIX:** Fixed an error on the recipe author template.
* **FIX:** More minor tweaks to support the upcoming Cooked Pro plugin.

= 1.1.1 =
* **FIX:** Compatibility improvements with the Yoast SEO plugin.
* **FIX:** Some minor tweaks to support the upcoming Cooked Pro plugin.

= 1.1.0 =
* **NEW:** **Full-Screen Mode:** Just include "fullscreen" in the `[cooked-info]` shortcode. Really shines on mobile devices!
* **NEW:** **Printable Recipes:** Just include "print" in the `[cooked-info]`shortcode. Includes some handy "a-la-carte" print options.
* **FIX:** Some adjustments for layouts on smaller devices (responsive fixes).
* **FIX:** Fixed an issue where quantities and amounts would not show up without a "Servings" setting. Now it works no matter what!
* **FIX:** Minor code adjustments to better support Cooked Pro.

= 1.0.0 =
* **NEW:** *Everything is new!*
* **NEW:** Drag & drop ingredients and directions.
* **NEW:** Beautiful grid-based masonry recipe lists.
* **NEW:** Powerful recipe search with a text search, categories & sorting options.
* **NEW:** Author template to list recipes by a single author.
* **NEW:** Cooking times with clickable, interactive timers.
* **NEW:** Very developer-friendly with loads of hooks & filters.
* **NEW:** Servings switcher to adjust ingredient amounts.
* **NEW:** SEO Optimized - Google Structured Data and Schema support.
* **NEW:** Prep & cooking times.
* **NEW:** Nutrition facts.
* **NEW:** Difficulty levels.
* **NEW:** Photo galleries.
