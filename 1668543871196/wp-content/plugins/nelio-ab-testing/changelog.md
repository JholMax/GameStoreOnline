= 5.0.18 (April 14, 2021) =
* **Improvement.** Add confirmation dialog before applying a variant.
* **Improvement.** Remove old jQuery dependency.
* **Improvement.** Show error message in UI when heatmap can’t be rendered.
* **Improvement.** Allow wildcards in IP address segments.
* **Bug Fix.** Tweak component to return empty array when post type has no templates.

= 5.0.17 (March 22, 2021) =
* **Bug Fix.** Use home URL in the _Account_ screen when showing “this site.”
* **Improvement.** Do not minify/combine Nelio A/B Testing scripts when using Siteground Optimizer plugin.

= 5.0.16 (March 3, 2021) =
* **Bug Fix.** Track events properly on the homepage when using Polylang.
* **Bug Fix.** Track events properly on the homepage when using WPML.
* **Bug Fix.** Add defaults sanitization in alternative attributes to remove some PHP notices.
* **Improvement.** Tweak the code to show the reason why a test can’t be started.
* **Improvement.** Add new filter to toggle short description replacement in WC product variations.
* **Improvement.** Add helper functions `nab_is_preview` and `nab_is_heatmap`.
* **Improvement.** Try to purge cache of NitroPack when needed.

= 5.0.15 (February 1, 2021) =
* **Improvement.** Modify account page to display agency subscriptions.

= 5.0.14 (January 15, 2021) =
* **Bug Fix.** Fix tested visitors range selector to allow value updates.
* **Bug Fix.** Maintain new conversion action and new segmentation rule panels open while editing them.

= 5.0.13 (December 14, 2020) =
* **Compatible with WordPress 5.6.**

= 5.0.12 (October 26, 2020) =
* **Fix.** Add timeout in _kickoff script_ to delay the window load fix implemented in previous version.

= 5.0.11 (October 9, 2020) =
* **Improvement.** Exclude Nelio scripts from WPRocket combination/minification processes to avoid incompatibilities.
* **Fix.** Add a call to `nabDoSingleAction( 'valid-content' )` in _kickoff script_ on window load to avoid possible WSOD.
* **Fix.** Add missing return statement in `nabAddSingleAction` helper function.
* **Fix.** Escape values in `add_query_arg` to fix bug when retrieving results from Nelio’s cloud.

= 5.0.10 (September 30, 2020) =
* **Improvement.** Extend custom event actions with a name to reuse them. You can now name a custom event conversion action. This way, triggering the custom action by its name will result in a conversion on all tests listening to it.
* **Improvement.** Modify alternative loader script to hide content using an overlay. This way, if there are any script on the page that need to know the size of the rendered DOM elements, said elements would be properly rendered behind the overlay (whereas, in previous versions, elements were actually hidden).
* **Improvement.** The plugin is now able to load alternative content during a post request. This way, when the browser receives the response, there’s no need to perform a JavaScript redirection, as this response already contains the content the visitor is supposed to see. You can disable this behavior using the `nab_can_load_alternative_content_on_post_request` filter.
* **Improvement.** Add new column in experiment list to show page views.
* **Fix.** Tweak alternative loader script to prevent infinite redirect loops.
* **Fix.** Edit heatmap script to set body’s `height` to `auto`. This guarantees that Nelio A/B Testing will be able to compute the body’s height in pixels and use that information to render the heatmap.
* **Fix.** Edit alternative loader script to support URLs with special characters.

= 5.0.9 (September 14, 2020) =
* **New Feature.** Plugin subscribers can now add segmentation rules to their tests, so that only specific segments are under test.
* **Fix.** When using WooCommerce’s shop page in a page view conversion action, visiting the shop page should trigger a conversion. Previous versions of the plugin didn’t, because the shop page wasn't detected as such by Nelio A/B Testing. This has now been fixed.
* **Fix.** When creating a CSS test, only administrators were able to edit CSS variants, even though editors should also be able to. This has now been fixed.
* **Fix.** Some click events couldn’t be tracked because the element they were listening to already had an event listener that prevented the event from reaching Nelio’s. This occurred because Nelio A/B Testing listeners were added to `document` instead of the element itself and relied on the even bubbling up. To overcome this limitation, the plugin now also tries to add a direct listener to the element itself.
* **Fix.** The plugin should always use original SEO data. This wasn’t the case when a page was built using page builders such as Divi or Elementor. (Note: the plugin is only compatible with Yoast SEO).
* **Fix.** Applying a winning variant didn’t work as expected if Polylang was enabled, as our plugin overwrote some Polylang taxonomies. This has now been fixed and Nelio A/B Testing ignores those taxonomies so that everything works as expected.
* **Fix.** The cookie `nabIsVisitorExcluded` wasn’t always properly set, which resulted in editors and admins participating in tests randomly. This cookie is now set during WordPress’ `set_logged_in_cookie` action, which occurs when the visitor is logging in and, therefore, when we know our own cookie can be properly set.
* **Fix.** Nelio A/B Testing’s `nab/data` store has a selector named `getActivePlugins`. This method should return an array with the list of active plugins, but sometimes it didn’t and it returned an object instead. This has now been fixed.
* **Improvement.** Test previews when using partial URLs in your scope select better candidates and thus show better previews.
* **Improvement.** When editing a CSS variant, you’re able to browse to any page on your site to see how the CSS snippet you write looks like. In the previous version, however, the preview was reset to the original page every time you saved the variant. This has now been fixed and saving a variant doesn’t reset the preview.
* **Improvement.** Added a new button in results page of a CSS test so that you can see the CSS snippet you created in each variant.
* **Improvement.** Flush cache of major systems when changing the status of a test.

= 5.0.8 (June 3, 2020) =
* **Improvement.** Added a new filter (`nab_ignore_trailing_slash_in_alternative_loading`) to select how our plugin checks if the current URL is equivalent to the URL the visitor is supposed to see. In particular, it tells our plugin if trailing slashes should be considered or not (namely, if `https://example.com/tested-page` is the same page as `https://example.com/tested-page/`).
* **Fix.** Form submissions and WooCommerce orders should only track a conversion for any given test if the visitor had seen the tested element of said test first. If they didn’t, the action shouldn’t become a conversion. The previous version failed to consider this.

= 5.0.7 (June 2, 2020) =
* **Improvement.** Sometimes, Nelio A/B Testing’s logo was retrieved using a GET request instead of accessing the file system directly. This has now been fixed.
* **Improvement.** This version includes a new filter to manage whether control ID should be used when loading alternative content built with Elementor: `nab_use_control_id_in_elementor_alternative`.
* **Improvement.** This version includes a new filter to prevent tests from running on certain URLs programmatically: `nab_{$experiment_type}_disable_experiment_in_url`.
* **Fix.** The previous version stopped the execution of our scripts on IE. Unfortunately, some scripts still triggered an error in IE. We fixed this by adding a polyfill for `Symbol`.
* **Fix.** Theme testing couldn’t load widgets properly. This has now been fixed.
* **Fix.** Line breaks in variants were automatically removed when using the Classic Editor. Apparently, this occurred because one of our admin scripts used `@wordpress/edit-post` as a dependency (in Gutenberg). This has now been fixed.
* **Fix.** Single custom meta fields that turned out to be an array didn’t work in variants, because of how WordPress manages “single” meta fields. To fix the issue, the filter that retrieves an alternative meta value should always retrieve the “full” (non-single) value and return it.
* **Fix.** Users with the free plan only see the plan info in the account page.
* **Fix.** Fixed warning when using `nab_woocommerce_sync_alternative` AJAX callback.
* **Fix.** Template tests didn’t always work. For instance, classnames included in the `body` tag weren’t correct. We’ve reimplemented the function that switches the tested template with one of its variants.
* **Fix.** Sometimes, the set of available test actions in _Tests_ screen was not correct. For instance, when a test was _Paused_, it was possible to either _Resume_ or _Start_ it. Only the first action made sense and the latter resulted in an error, which means it shouldn't be there in the first place. We’ve fixed these situations.

= 5.0.6 (April 14, 2020) =
* **Improvement.** Alternative loader script is no longer enqueued unless the current page is under test. This makes site loading a little bit faster.
* **Fix.** Template tests didn’t always work with custom post types when the tested template was the default template and said default template was `single.php` or `singular.php`. This has now been fixed.
* **Fix.** The plugin is now compatible with Instabuilder2. In previous versions, there were issues when creating variants from Instabuilder2 pages. This has now been fixed and alternative content is properly created.
* **Fix.** The plugin does no longer add `utm_referrer` param in URLs when redirecting the visitor from a tested page to the variant they’re supposed to see if said referrer is the same site.
* **Fix.** Improved management of URL parameters when loading alternative content.
* **Fix.** When duplicating a test, its description is also duplicated.
* **Fix.** Status icon in Dashboard is visible even when tests have no winner.
* **Fix.** Nelio A/B Testing doesn’t support IE and, therefore, none of our testing scripts should run on IE. We added a guard that checks if the browser is IE and, if it is, it stops the execution of our scripts.

= 5.0.5 (March 23, 2020) =
* **New Feature.** The plugin can now be used for free too!
* **Fix.** WooCommerce Order Completed actions didn’t work as expected. Under certain circumstances, the same order could trigger multiple conversions in a test, which was invalid. The plugin now keeps track of the already-synched orders to prevent this issue.

= 5.0.4 (March 16, 2020) =
* **New Features.** You can now tweak the minimum number of page views and confidence values required to call a test winner.
* **Improvement.** Tested in WordPress 5.4 and styles adapted to it.
* **Fix.** The plugin is now compatible with Elementor. In previous versions, there were issues with styling and CSS. Nelio A/B Testing can now detect when a page is built using Elementor and switches to the appropriate CSS if needed.
* **Fix.** The plugin is now compatible with Divi. In previous versions, there were issues with styling and CSS. Nelio A/B Testing can now detect when a page is built using Divi and switches to the appropriate CSS if needed.
* **Fix.** The plugin is now compatible with OptimizePress. In previous versions, an OptimizePress original page ended up looking like one of its variants because OptimizePress ended up caching alternative content as if it were the original copy. Nelio now detects when OptimizePress is caching a page and makes sure the cache is properly set. Just keep in mind you have to enable Nelio A/B Testing’s scripts and styles in OptimizePress’ settings.
* **Fix.** The plugin is now compatible with Leadpages (but it requires some tweaking). In previous versions, the plugin was unable to load alternative content and/or track any events from a Leadpage. This is because Leadpages doesn’t use anyof WordPress’ functions and simply replaces the final HTML with the HTML generated in their platform. To overcome this issue, edit Leadpages’ plugin and add an `apply_filters('leadpages_html', $html)` in `App/Helpers/LeadpageType.php` when it’s echoing the `$html`.
* **Fix.** Custom meta fields that were an array didn’t work in variants, because our plugin wrapped the original array in a new array. This has now been fixed.
* **Fix.** When running template tests, Nelio A/B Testing tried to load alternative content on all pages of your website, because it didn’t know if a certain page used the tested template or not. With this new version, the plugin is now able to determine whether the requested page uses the tested template, so alternative content loading is more precise.
* **Fix.** When Nelio A/B Testing is installed, it temporarily hides the content of a page to check if it’s under test and, if it is, redirect the visitor to the appropriate variation with no screen flickering. If the page contained a YouTube video or any other element that needs the content to be visible, this might be an issue and the element might not have the correct dimensions. To fix this, the plugin now triggers a `resize` event once the content is visible again, so that these elements know they should probably fix their sizes.

= 5.0.3 (February 24, 2020) =
* **Improvement.** The plugin now uses a cookie to determine whether logged-in users participate in a test or not, instead of relying on including or excluding the tracking script in the final HTML.
* **Fix.** Remove warning when calling `column_date` method statically.
* **Fix.** Given any page or post, there should be at most one test running testing it. In the previous version, it was possible to have more than one test testing the same element. This has now been fixed.
* **Fix.** When retrieving alternative post metas, results where inconsistent if the post meta was an array. This has now been fixed.
* **Fix.** Gravity Form Conversion Actions didn’t track form submissions properly (apparently, the ID of the submitted form was a `string` instead of an `int`). This has now been fixed.
* **Fix.** Gravity Form Conversion Actions should show the name of the selected form, but they didn’t in the results page of a test (resulting in users not being able to tell what they were actually testing).

= 5.0.2 (February 3, 2020) =
* **Improvement.** If the plugin is running on a staging site (and it detects the site as such), the plugin doesn’t track any events. This is the intended behavior, but users might not be aware of it. This version adds a warning in the plugin’s UI letting them know that the current site has been identified as a staging site.
* **Fix.** While editing a variant, test icons in the post editor screen looked stretched sometimes. This has now been fixed.
* **Fix.** Fixed a PHP Notice when trying to index a char in an empty string.

= 5.0.1 (January 23, 2020) =
* **Fix.** When using Beaver Builder, some variants used styles defined in control version. This has now been fixed.
* **Fix.** A hook in the `admin_body_class` filter didn’t always return a valid list of `$classes`, which could break the UI. This has now been fixed.

= 5.0.0 (January 14, 2020) =
* **Complete Redesign of Nelio A/B Testing!**
* **Improved UI and UX.** Nelio A/B Testing 5.0 uses the new technologies included in WordPress 5.3, offering a more elegant, intuitive, reponsive, and easier-to-use interface.
* **Under the hood.** The plugin has been completely rewritten to make it more reliable and faster.
* **New cloud.** We’ve moved our cloud from Google AppEngine to Amazon Web Services.
