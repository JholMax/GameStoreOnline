=== GitHub Release Downloads ===
Contributors: ivanrf
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=RFBN78SQEZR4E
Tags: github, release, releases, download, downloads, download count, download url, download link, download list, release downloads, latest version, shortcode, shortcodes
Requires at least: 3.1
Tested up to: 5.5.3
Stable tag: 2.3.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Get the download count, links and more information for releases of GitHub repositories.

== Description ==

[GitHub Release Downloads](https://ivanrf.com/en/github-release-downloads/) allows you to get the download count, links and more information for releases of GitHub repositories.

= Download count =

The shortcode is `[grd_count]`. It returns a number corresponding to the sum of all download count values for the selected releases. For instance, you can get the number of total downloads for one GitHub repository.

Attributes

* `user`: the GitHub username.
* `repo`: the GitHub repository name.
* `latest`: only gets information about the latest published release for the repository.
* `tag`: only gets information about the release with the specified tag name.
* `prereleases`: includes draft releases and prereleases. Default value is `false`.

Examples

* All repository downloads:	  `[grd_count user="IvanRF" repo="MassiveFileRenamer"]`
* Latest release downloads:	  `[grd_count user="IvanRF" repo="MassiveFileRenamer" latest="true"]`
* Specific release downloads: `[grd_count user="IvanRF" repo="MassiveFileRenamer" tag="v1.6.0"]`

Note: `user` and/or `repo` can be omitted if default values are set.

= Release downloads list =

The shortcode is `[grd_list]`. It returns an HTML list for the selected releases including the release tag name, the release description and the list of files that can be downloaded, like release assets or the source code.
For styling, class selectors are provided (e.g.: `release-downloads-header`, `release-name`, etc.).

Attributes

* `user`, `repo`, `latest`, `tag`, `prereleases`: same as above.
* `hide_description`: hides the release description.
* `hide_size`: hides information about the file size.
* `hide_downloads`: hides information about the download count.
* `downloads_suffix`: use it for internationalization. Default value is `" downloads"`.
* `hide_source_code`: hides the download link for the source code.

Examples

* All repository downloads:   `[grd_list user="IvanRF" repo="MassiveFileRenamer"]`
* Latest release downloads:   `[grd_list user="IvanRF" repo="MassiveFileRenamer" latest="true"]`
* Specific release downloads: `[grd_list user="IvanRF" repo="MassiveFileRenamer" tag="v1.5.6"]`
* Hide file size:             `[grd_list user="IvanRF" repo="MassiveFileRenamer" hide_size="true"]`
* Hide downloads count:       `[grd_list user="IvanRF" repo="MassiveFileRenamer" hide_downloads="true"]`
* Downloads suffix change:    `[grd_list user="IvanRF" repo="MassiveFileRenamer" downloads_suffix="descargas"]`

Boolean attributes can take any of this values: "1", "true", "on" and "yes"; or "0", "false", "off" and "no".

= Latest version =

The shortcode is `[grd_latest_version]`. It returns the tag name of the latest published full release.
For tag names like "v1.6.0", it returns "1.6.0" as the version number.

Attributes

* `user`, `repo`, `prereleases`: same as above.

= Settings =

Under WordPress **Settings** menu you will find the **GitHub Release Downloads** options page. In this page you can set values for the GitHub username and the repository name to use by default in the shortcodes.

If both values are set, the shortcodes can be used without attributes (e.g.: `[grd_count]`) since default values for `user` and `repo` will be used.
Attribute values take precedence over default values. For example, `[grd_count repo="MyRepo"]` will use the username default value and the repository specified in the shortcode. 

== Installation ==

1. Upload the `github-release-downloads` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

You'll find more information at [ivanrf.com](https://ivanrf.com/en/github-release-downloads/).

== Screenshots ==

1. Example of `[grd_count]`. Here is used to get the number for *total downloads*.
2. Example of `[grd_list]`. Here it lists all available releases and downloads for a repository.
3. GitHub Release Downloads options page.

== Changelog ==

= 2.3.1 =
* Added Authorization HTTP header, since access_token query parameter is deprecated.
* Minor fix for a PHP warning.

= 2.3.0 =
* Added release description, with Markdown support.
* Added download link for release source code.
* Added attribute to include draft and prereleases.
* Improved dismissible admin notices.

= 2.2.0 =
* Ignore draft and prereleases for latest version. Thanks @john30 for reporting this.
* Improved admin notices.

= 2.1.0 =
* Improved downloads list.

= 2.0.1 =
* Removed scopes for access token.
* Added scopes notes.
* Added admin notices.

= 2.0.0.1 =
* Fixed some localized strings.

= 2.0.0 =
* Added Access Token to deal with API rate limit.
* Improved error handling. External errors are shown in order to identify root causes.
* Added help notes.
* Moved translations to [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/github-release-downloads).

= 1.1.0 =
* Added i18n support for strings and download count.
* Added Spanish translation.
* Added Settings link in the Plugins list.
* Added Donate and Follow Me links.

= 1.0.1 =
* Minor fix for a PHP warning.

= 1.0 =
* Initial release.

== Upgrade Notice ==

You'll find more information at [ivanrf.com](https://ivanrf.com/en/github-release-downloads/).
