<?php
/**
 * Plugin Name: GitHub Release Downloads
 * Version: 2.3.1
 * Plugin URI: https://ivanrf.com/en/github-release-downloads/
 * Description: Get the download count, links and more information for releases of GitHub repositories.
 * Author: Ivan Ridao Freitas
 * Author URI: https://ivanrf.com/en/
 * Text Domain: github-release-downloads
 * Domain Path: /languages
 * License: GPL2
 */
 
/*  Copyright 2015  Ivan Ridao Freitas

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Make sure we don't expose any info if called directly
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if (!defined( 'GRD_PLUGIN_DIR' ))
	define( 'GRD_PLUGIN_DIR', plugin_dir_path(__FILE__) );

/**
 * Add Settings link in the Plugins list
 */	 
function add_settings_link( $links ) {
	array_unshift( $links, get_settings_link( __( 'Settings', 'github-release-downloads' ) ) );
	return $links;
}
function get_settings_link( $txt ) {
	return '<a href="' . esc_url( get_admin_url( null, 'options-general.php?page=github-release-downloads' ) ) . '">' . $txt . '</a>';
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_settings_link' );

/**
 * Load Text Domain
 */
function grd_load_plugin_textdomain() {
	load_plugin_textdomain( 'github-release-downloads', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'grd_load_plugin_textdomain' );

/**
 * Show admin notices on plugin activation and Settings page
 */
function grd_admin_notices() {
	if (!current_user_can( 'manage_options' ))
		return;
	
	// Check if current screen is my Settings page
	global $grd_admin_page;
	$screen = get_current_screen();
    $is_admin_page = ( $screen->id == $grd_admin_page );
	
	$notice_id = 'grd_notice_settings';
	if (!$is_admin_page && !get_option($notice_id)) {
		$link = get_settings_link( __( 'GitHub Release Downloads Settings', 'github-release-downloads' ) );
		$msg = sprintf( __( 'Go to %s to set default values, an access token and get help.', 'github-release-downloads' ), $link );
		echo '<div id="' . $notice_id . '" class="notice notice-success is-dismissible"><p>' . $msg . '</p></div>';
	}
	
	$notice_id = 'grd_notice_update';
	if (!get_option($notice_id)) {
		$msg = '<strong style="font-size: 1.3em;">GitHub Release Downloads - ' . __( 'Upgrade Notice', 'github-release-downloads' ) . '</strong><br/>';
		$msg .= __( 'Release descriptions and source code links were added to the latest version.<br/>' .
					'To remove them use <code>hide_description="true"</code> or <code>hide_source_code="true"</code> in your shortcode.<br/>' .
					'Also, to include draft or prereleases you must add <code>prereleases="true"</code>.', 'github-release-downloads' );
		echo '<div id="' . $notice_id . '" class="notice notice-warning is-dismissible"><p>' . $msg . '</p></div>';
	}
	
	if ($is_admin_page && !get_option('grd_token')) {
		$msg = __( 'Generate an access token to avoid rate limiting on the GitHub API.', 'github-release-downloads' );
		echo '<div class="notice notice-error"><p>' . $msg . '</p></div>';
	}
}
add_action('admin_notices', 'grd_admin_notices');

add_action( 'admin_enqueue_scripts', 'grd_enqueue_scripts' );
function grd_enqueue_scripts() {
	wp_enqueue_script( 'grd-admin-notices', plugins_url( 'js/admin-notices.js', __FILE__ ), array( 'jquery' ), false, true );
}

add_action( 'wp_ajax_grd_dismiss_notice', 'grd_dismiss_notice' );
function grd_dismiss_notice() {
	if ( !isset( $_POST['grd_dismiss_notice_id'] ) || !current_user_can( 'manage_options' ))
		wp_die( -30 );
	
	$notice_id = sanitize_key( $_POST['grd_dismiss_notice_id'] );
	update_option($notice_id, 'true');
	wp_die();
}

//** Add plugin shortcodes **//
add_shortcode( 'grd_count', 'grd_download_count_func' );
add_shortcode( 'grd_list', 'grd_download_list_func' );
add_shortcode( 'grd_latest_version', 'grd_latest_version_func' );

function grd_download_count_func( $atts ) {
	$releases = get_release_contents($atts);
	
	$releases_error = grd_check_releases( $releases, true );
	if (!empty($releases_error))
		return $releases_error;
	
	$total_downloads = 0;
	foreach ($releases as $release)
		$total_downloads += get_release_download_count($release);
	return $total_downloads;
}

function get_release_download_count( $release ) {
	$total_downloads = 0;
	foreach ((array) $release->assets as $asset)
		$total_downloads += $asset->download_count;
	return $total_downloads;
}

function grd_download_list_func( $atts ) {
	$a = shortcode_atts( array(
		'hide_description'	=> false,
		'hide_size'			=> false,
		'hide_downloads'	=> false,
		'downloads_suffix'	=> '',
		'hide_source_code'	=> false,
	), $atts );
	$a['hide_description']	= filter_var( $a['hide_description'], FILTER_VALIDATE_BOOLEAN );
	$a['hide_size']			= filter_var( $a['hide_size'], FILTER_VALIDATE_BOOLEAN );
	$a['hide_downloads']	= filter_var( $a['hide_downloads'], FILTER_VALIDATE_BOOLEAN );
	$a['hide_source_code']	= filter_var( $a['hide_source_code'], FILTER_VALIDATE_BOOLEAN );
	
	$releases = get_release_contents($atts);
	
	$releases_error = grd_check_releases( $releases );
	if (!empty($releases_error))
		return $releases_error;
	
	$html = '';
	foreach ($releases as $release)
		$html .= get_release_download_list($release, $a);
	return $html;
}

function get_release_download_list( $release, $atts ) {
	$hide_description = $atts['hide_description'];
	$hide_size = $atts['hide_size'];
	$hide_downloads = $atts['hide_downloads'];
	$downloads_suffix = $atts['downloads_suffix'];
	$hide_source_code = $atts['hide_source_code'];
	
	$assets = array();
	if (isset($release->assets) && !empty($release->assets))
		$assets = (array) $release->assets;
	else if ($hide_source_code)
		return ''; // No assets or source code
	
	$name = (!empty($release->name)) ? $release->name : $release->tag_name;
	$html = '<h2 class="release-downloads-header">' . $name . '</h2>';
	if (!$hide_description && !empty($release->body))
		$html .= '<div class="release-description">' . parseMarkdown($release->body) . '</div>';
	$html .= '<ul class="release-downloads">';
	foreach ($assets as $asset) {
		$html .= '<li>';
		$html .= '<a href="' . $asset->browser_download_url . '" rel="nofollow">';
		$html .= '<strong class="release-name">' . $asset->name . '</strong> ';
		if (!$hide_size)
			$html .= '<small class="release-size">' . formatBytes($asset->size) . '</small> ';
		if (!$hide_downloads) {
			$formatted = number_format_i18n( $asset->download_count );
			if (!empty($downloads_suffix))
				$downloads = $formatted . ' ' . $downloads_suffix;
			else
				$downloads = sprintf( _n( '1 download', '%s downloads', $asset->download_count, 'github-release-downloads' ), $formatted );
            $html .= '<small class="release-download-count">' . $downloads . '</small>';
		}
		$html .= '</a>';
		$html .= '</li>';
	}
	if (!$hide_source_code) {
		// Link on $release->zipball_url differs from the one used by GitHub on Releases page
		$url = $release->zipball_url;
		$url = str_replace('https://api.github.com/repos', 'https://github.com', $url);
		$url = str_replace('/zipball/', '/archive/', $url) . '.zip';
		$html .= '<li><a href="' . $url . '" rel="nofollow"><strong class="release-source">' . __( 'Source code', 'github-release-downloads' ) . '</strong></a></li>';
	}
	$html .= '</ul>';
	
	return $html;
}

function grd_latest_version_func( $atts ) {
	if (empty($atts))
		$atts = array();
	// $atts['latest'] = true; // Unnecessary, this way allows using the cached data
	$atts['tag'] = ''; // Avoid confusion
	
	$releases = get_release_contents($atts);
	
	$releases_error = grd_check_releases( $releases );
	if (!empty($releases_error))
		return $releases_error;
	
	foreach ($releases as $release) {
		$latest_tag = $release->tag_name;
		
		// Remove 'v' from the start, e.g. v1.6.0 => 1.6.0
		$latest_tag = preg_replace('/^v(\d)/', '\1', $latest_tag, 1);
		
		return $latest_tag;
	}
	return grd_no_full_releases_error_msg();
}

/**
 * Checks if the Releases content is valid.
 * 
 * @return string with the error message. NULL otherwise.
 */
function grd_check_releases( $releases, $check_assets = false ) {
	if (is_wp_error( $releases ))
		return $releases->get_error_message();
	
	if (empty($releases))
		return grd_no_releases_error_msg();
	
	if ($check_assets && !grd_has_assets($releases))
		return grd_no_release_assets_error_msg();
	
	return null; // OK
}

function grd_has_assets( $releases ) {
	foreach ($releases as $release) {
		if (isset($release->assets) && !empty($release->assets))
			return true;
	}
	return false;
}

/**
 * Filters draft or prereleases
 */
function grd_filter_releases( $releases, $atts ) {
	$latest = $atts['latest'];
	$prereleases = $atts['prereleases'];
	if ($latest && !$prereleases || !$latest && $prereleases || empty($releases)) {
		// GitHub API default behaviour
		return $releases;
	} else if ($latest) {
		// Take first release or prerelease
		return array($releases[0]);
	} else {
		// Ignore prereleases
		return array_filter($releases, "grd_is_full_release");
	}
}

/**
 * Returns true for draft or prereleases
 */
function grd_is_full_release( $release ) {
	return !($release->draft || $release->prerelease);
}

/**
 * Gets repository contents through a connection to the GitHub API.
 *  
 * @param array $atts The attributes passed to the shortcodes.
 * @return string|WP_Error
 */
function get_release_contents( &$atts ) {
	$atts = shortcode_atts( array(
		'user'   		=> get_option( 'grd_user' ),
		'repo'   		=> get_option( 'grd_repo' ),
		'latest' 		=> false,
		'tag'    		=> '',
		'prereleases'	=> false,
	), $atts );
	$atts['latest']			= filter_var( $atts['latest'], FILTER_VALIDATE_BOOLEAN );
	$atts['prereleases']	= filter_var( $atts['prereleases'], FILTER_VALIDATE_BOOLEAN );
	
	// Check attributes
	if (empty($atts['user'])) {
		$msg = __( 'GitHub username can not be empty', 'github-release-downloads' );
		return new WP_Error('shortcode_error', grd_sc_error_msg( $msg ));
	} else if (empty($atts['repo'])) {
		$msg = __( 'GitHub repository name can not be empty', 'github-release-downloads' );
		return new WP_Error('shortcode_error', grd_sc_error_msg( $msg ));
	}
	
	// Build URL
	$latest			= $atts['latest'];
	$tag			= $atts['tag'];
	$prereleases	= $atts['prereleases'];
	
	$url = "https://api.github.com/repos/" . $atts['user'] . "/" . $atts['repo'] . "/releases";
	if ($latest && !$prereleases) {
		// Draft releases and prereleases are not returned by /releases/latest
		$url .= "/latest";
	} else if (!empty($tag))
		$url .= "/tags/" . $tag;
	
	// Check the cache
	$rel_cache = wp_cache_get($url, 'github-release-downloads');
	if ($rel_cache !== false)
		return grd_filter_releases($rel_cache, $atts);
	
	$response = get_github_contents($url);
	if (is_wp_error( $response ))
		return $response;
	
	if ($latest && !$prereleases || !empty($tag))
		$response = '[' . $response . ']'; // Unifies different responses
	
	// Decode the JSON string
	$releases = json_decode($response);
	
	// Cache the result for future queries
	wp_cache_add($url, $releases, 'github-release-downloads');
	
	return grd_filter_releases($releases, $atts);
}

/**
 * Returns GitHub response.
 * 
 * @return string|WP_Error
 */
function get_github_contents( $url ) {
	$token = get_option( 'grd_token' );
	$args = array();
	if (!empty($token)) {
		// Adds OAuth2 Token
		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token
			)
		);
	}
	
	$response = wp_remote_get( $url, $args );
	// Check external errors (eg: 'User has blocked requests through HTTP.')
	if (is_wp_error( $response ))
		return $response;
	
	// Get body content
	$response_body = wp_remote_retrieve_body( $response );
	
	// Check response code
	$response_code = wp_remote_retrieve_response_code( $response );
	if ($response_code == '404') { // Not Found
		$msg = __( 'GitHub repository not found', 'github-release-downloads' );
		return new WP_Error('shortcode_error', grd_sc_error_msg( $msg ));
	} else if ($response_code == '403') { // Forbidden
		$msg = __( 'GitHub request forbidden', 'github-release-downloads' );
		return new WP_Error('shortcode_error', grd_decode_github_msg( $response_body, $msg ));
	} else if (empty( $response_body )) {
		$msg = __( 'GitHub response is empty', 'github-release-downloads' );
		return new WP_Error('shortcode_error', grd_sc_error_msg( $msg ));
	}
	return $response_body;
}

function grd_no_releases_error_msg() {
	$msg = __( 'GitHub repository has no releases', 'github-release-downloads' );
	return grd_sc_error_msg( $msg );
}

function grd_no_release_assets_error_msg() {
	$msg = __( 'GitHub repository has no release assets. Currently, the download count stored on GitHub is only available for assets.', 'github-release-downloads' );
	return grd_sc_error_msg( $msg );
}

function grd_no_full_releases_error_msg() {
	$msg = __( 'GitHub repository has no published full releases, only draft releases or prereleases were found', 'github-release-downloads' );
	return grd_sc_error_msg( $msg );
}

function grd_sc_error_msg( $msg ) {
	return sprintf( __( 'Shortcode Error: %s', 'github-release-downloads' ), $msg );
}

function grd_decode_github_msg( $response_body, $msg ) {
	$decoded = json_decode($response_body);
	if (isset($decoded->message))
		$msg = $decoded->message;
	if (isset($decoded->documentation_url))
		$msg .= ' - ' . $decoded->documentation_url;
	return $msg;
}

function parseMarkdown($markdown_body) {
	require_once( GRD_PLUGIN_DIR  . 'includes/parsedown/Parsedown.php' );
	return Parsedown::instance()->text($markdown_body);
}

function formatBytes($bytes, $precision = 2) {
	$units = array('B', 'KB', 'MB', 'GB', 'TB');
	$bytes = max($bytes, 0);
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	$pow = min($pow, count($units) - 1);
	$bytes /= pow(1024, $pow);
	return round($bytes, $precision) . ' ' . $units[$pow];
}

//** Add plugin administration menu **//
add_action( 'admin_init', 'grd_register_settings' );
add_action( 'admin_menu', 'grd_menu' );

function grd_register_settings() {
	add_option( 'grd_user', '');
	add_option( 'grd_repo', '');
	add_option( 'grd_token', '');
	register_setting( 'grd_settings', 'grd_user' );
	register_setting( 'grd_settings', 'grd_repo' );
	register_setting( 'grd_settings', 'grd_token' );
}

function grd_menu() {
	global $grd_admin_page;
	$page_title = __( 'GitHub Release Downloads Options', 'github-release-downloads' );
	$menu_title = 'GitHub Release Downloads';
	$grd_admin_page = add_options_page( $page_title, $menu_title, 'manage_options', 'github-release-downloads', 'grd_options' );
}

function grd_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
?>
<div class="wrap">
	<h2><?php _e( 'GitHub Release Downloads Settings', 'github-release-downloads' ); ?></h2>
	<form method="post" action="options.php"> 
		<?php settings_fields( 'grd_settings' ); ?>
			<p><?php _e( 'Set values for the GitHub username and the repository name to use by default in the shortcodes.', 'github-release-downloads' ); ?></p>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="grd_user"><?php _e( 'User', 'github-release-downloads' ); ?></label></th>
					<td><input type="text" id="grd_user" name="grd_user" value="<?php grd_echo_option( 'grd_user' ); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="grd_repo"><?php _e( 'Repository', 'github-release-downloads' ); ?></label></th>
					<td><input type="text" id="grd_repo" name="grd_repo" value="<?php grd_echo_option( 'grd_repo' ); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="grd_token"><?php _e( 'Access Token', 'github-release-downloads' ); ?></label></th>
					<td>
						<input type="text" id="grd_token" name="grd_token" value="<?php grd_echo_option( 'grd_token' ); ?>" size="40" />
						<a class="button button-secondary" href="https://github.com/settings/tokens/new?scopes=&description=GitHub Release Downloads - WP Plugin" target="_blank">🔑 <?php _e( 'Generate token', 'github-release-downloads' ); ?></a>
						<p class="description"><?php _e( 'Create a new token to make up to 5,000 requests per hour.', 'github-release-downloads' ); ?></p>
					</td>
				</tr>
			</table>
		<?php submit_button(); ?>
	</form>
	<hr/>
	<h3><?php _e( 'Need help?', 'github-release-downloads' ); ?></h3>
	<p><?php printf( __( 'Learn how to use the plugin at %s.', 'github-release-downloads' ), '<strong><a href="https://ivanrf.com/en/github-release-downloads/" target="_blank">ivanrf.com</a></strong>' ); ?></p>
	
	<h4><?php _e( 'Release Assets', 'github-release-downloads' ); ?> <a href="https://github.blog/2013-07-02-release-your-software/" target="_blank">🔗</a></h4>
	<p><?php _e( 'The download counts are not available for the ZIP or TAR archives of source code. They are only available for the release assets which are uploaded for the release. Providing download counts for the archives of source code is on GitHub wishlist, but is currently not supported.', 'github-release-downloads' ); ?></p>
	
	<h4><?php _e( 'Rate Limiting', 'github-release-downloads' ); ?> <a href="https://developer.github.com/v3/#rate-limiting" target="_blank">🔗</a></h4>
	<p><?php _e( 'For requests using an access token, you can make up to 5,000 requests per hour. For unauthenticated requests, the rate limit allows you to make up to 60 requests per hour. Unauthenticated requests are associated with your server IP address, and not the user making requests.', 'github-release-downloads' ); ?></p>
	<p><?php _e( '<strong>Important:</strong> If your website is on a shared hosting, you should set the Access Token.', 'github-release-downloads' ); ?></p>
	
	<h4><?php _e( 'Scopes', 'github-release-downloads' ); ?> <a href="https://developer.github.com/apps/building-oauth-apps/understanding-scopes-for-oauth-apps/" target="_blank">🔗</a></h4>
	<p><?php _e( 'Scopes limit access for tokens. If you want to read releases from private repositories, select <code>repo</code> scope when creating a token.', 'github-release-downloads' ); ?></p>
	
	<p>
		<a class="button button-secondary" href="https://ivanrf.com/en/github-release-downloads/" target="_blank">💡 <?php _e( 'Help', 'github-release-downloads' ); ?></a>
		<a class="button button-secondary" href="https://wordpress.org/support/plugin/github-release-downloads/" target="_blank">💬 <?php _e( 'Support', 'github-release-downloads' ); ?></a>
	</p>
	<hr/>
	<h3><?php _e( 'Donate', 'github-release-downloads' ); ?></h3>
	<p><?php _e( 'If you want to do something really nice for me...', 'github-release-downloads' ); ?> <a class="button button-primary" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=RFBN78SQEZR4E" target="_blank">🍺 <?php _e( 'Buy me a beer', 'github-release-downloads' ); ?></a></p>
		<hr/>
	<h3><?php _e( 'Review', 'github-release-downloads' ); ?></h3>
	<p><?php _e( 'Your feedback is important!', 'github-release-downloads' ); ?> <a class="button button-secondary" href="https://wordpress.org/support/plugin/github-release-downloads/reviews/" target="_blank">⭐ <?php _e( 'Rate this plugin', 'github-release-downloads' ); ?></a></p>
	<hr/>
	<h3><?php _e( 'Follow Me', 'github-release-downloads' ); ?></h3>
	<p>
		<a class="button button-secondary" href="https://twitter.com/ivanrfcom" target="_blank">💙 Twitter</a>
		<a class="button button-secondary" href="https://www.facebook.com/ivanrfcom/" target="_blank">💜 Facebook</a>
		<a class="button button-secondary" href="https://github.com/IvanRF" target="_blank">🖤 GitHub</a>
	</p>
	<hr/>
</div>
<?php
}

function grd_echo_option( $option ) {
	echo esc_attr(get_option( $option ));
}
?>